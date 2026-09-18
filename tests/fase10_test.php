<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Fase 10: Agendamento Semanal de Lanches e Regras Rígidas de Corte
 * 
 * Executa testes rigorosos e automatizados cobrindo:
 * 1. Calendário escolar, feriados e liberação da semana seguinte (Sexta 18:00);
 * 2. Travas rígidas de corte operacional (08:00 matutino e 13:30 vespertino);
 * 3. Criação atômica de pedido com débito compulsório na carteira familiar;
 * 4. Trava contra saldo insuficiente no agendamento (modelo pré-pago);
 * 5. Reserva física de estoque de industrializados e trava de esgotados;
 * 6. Cancelamento autônomo com estorno integral de 100% e liberação de estoque;
 * 7. Recusa terminante de cancelamento ou edição após o horário de corte;
 * 8. Edição de pedido com compensação financeira imediata (débito/estorno da diferença);
 * 9. Detecção preventiva de alérgenos e emissão de ticket térmico com tag obrigatória;
 * 10. Proteção de titularidade familiar e isolamento de dados LGPD.
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/RestricaoAlimentar.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Pedido.php';

use App\Models\Responsavel;
use App\Models\Turma;
use App\Models\Aluno;
use App\Models\RestricaoAlimentar;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\TransacaoCarteira;
use App\Models\CalendarioEscolar;
use App\Models\ItemPedido;
use App\Models\Pedido;

class Fase10Test
{
    private \PDO $pdo;
    private int $totalAssercoes = 0;
    private int $assercoesAprovadas = 0;
    private array $cleanups = [
        'tickets'            => [],
        'itens_pedido'       => [],
        'pedidos'            => [],
        'transacoes_carteira'=> [],
        'produtos'           => [],
        'alunos'             => [],
        'turmas'             => [],
        'responsaveis'       => [],
        'calendario_letivo'  => [],
    ];

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n====================================================================\n";
        echo " INICIANDO TESTES DA FASE 10 — AGENDAMENTO SEMANAL E TRAVAS DE CORTE\n";
        echo "====================================================================\n\n";

        try {
            $this->testarCalendarioEscolarEJanelas();
            $this->testarTravasRigidasDeCorte();
            $this->testarCriacaoPedidoEDebitoCarteira();
            $this->testarTravaSaldoInsuficiente();
            $this->testarReservaEstoqueEEsgotados();
            $this->testarCancelamentoComEstornoIntegral();
            $this->testarRecusaAposCorteOperacional();
            $this->testarEdicaoComCompensacaoFinanceira();
            $this->testarDeteccaoAlergenosETagTicket();
            $this->testarTitularidadeLGPDEIsolamento();
        } finally {
            $this->teardown();
        }

        echo "--------------------------------------------------------------------\n";
        echo " RESULTADO DOS TESTES DA FASE 10:\n";
        echo " Total de asserções: {$this->totalAssercoes}\n";
        echo " Aprovadas:          {$this->assercoesAprovadas}\n";
        $falhas = $this->totalAssercoes - $this->assercoesAprovadas;
        echo " Reprovadas:         {$falhas}\n";
        $taxa = $this->totalAssercoes > 0 ? round(($this->assercoesAprovadas / $this->totalAssercoes) * 100, 2) : 0;
        echo " Taxa de sucesso:    {$taxa}%\n";
        echo "--------------------------------------------------------------------\n\n";

        if ($falhas > 0) {
            exit(1);
        }
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesAprovadas++;
            echo "  [PASS] {$descricao}\n";
        } else {
            echo "  [FAIL] {$descricao}\n";
        }
    }

    /**
     * 1. Testando Calendário Escolar, Feriados e Janelas de Liberação (RN-01 e RN-06)
     */
    private function testarCalendarioEscolarEJanelas(): void
    {
        echo "1. Testando Calendário Escolar e Janelas de Liberação (RN-01 e RN-06):\n";

        // Segunda-feira normal é dia letivo
        $this->assert(CalendarioEscolar::isDiaLetivo('2026-09-21') === true, "Segunda-feira 2026-09-21 é dia letivo regular");

        // Sábado e domingo nunca são dias letivos
        $this->assert(CalendarioEscolar::isDiaLetivo('2026-09-26') === false, "Sábado não é dia letivo");
        $this->assert(CalendarioEscolar::isDiaLetivo('2026-09-27') === false, "Domingo não é dia letivo");

        // Insere feriado pontual em calendario_letivo
        $dataFeriado = '2026-10-12';
        $this->pdo->exec("INSERT INTO calendario_letivo (data, tipo, descricao) VALUES ('{$dataFeriado}', 'feriado', 'Nossa Senhora Aparecida') ON DUPLICATE KEY UPDATE tipo='feriado'");
        $this->cleanups['calendario_letivo'][] = $dataFeriado;

        $this->assert(CalendarioEscolar::isDiaLetivo($dataFeriado) === false, "Feriado cadastrado em calendario_letivo não é dia letivo");
        $infoFeriado = CalendarioEscolar::obterInfoData($dataFeriado);
        $this->assert($infoFeriado['tipo'] === 'feriado', "Identifica tipo feriado corretamente");

        // Teste de abertura da próxima semana às sextas-feiras 18:00 (RN-01)
        // Simulação 1: Quinta-feira 14:00 (bloqueada)
        $quinta14h = new \DateTimeImmutable('2026-09-24 14:00:00');
        $this->assert(CalendarioEscolar::isSemanaSeguinteLiberada($quinta14h) === false, "Quinta-feira 14:00 não libera semana seguinte");

        // Simulação 2: Sexta-feira 17:59 (bloqueada)
        $sexta1759 = new \DateTimeImmutable('2026-09-25 17:59:00');
        $this->assert(CalendarioEscolar::isSemanaSeguinteLiberada($sexta1759) === false, "Sexta-feira 17:59 ainda não libera semana seguinte");

        // Simulação 3: Sexta-feira 18:00 em ponto (liberada!)
        $sexta1800 = new \DateTimeImmutable('2026-09-25 18:00:00');
        $this->assert(CalendarioEscolar::isSemanaSeguinteLiberada($sexta1800) === true, "Sexta-feira pontualmente às 18:00 libera a próxima semana");

        // Simulação 4: Sábado qualquer (liberada)
        $sabado = new \DateTimeImmutable('2026-09-26 10:00:00');
        $this->assert(CalendarioEscolar::isSemanaSeguinteLiberada($sabado) === true, "Final de semana mantém semana seguinte liberada");

        // Dias da semana letiva retornam 5 itens (Segunda a Sexta)
        $diasSemana = CalendarioEscolar::obterDiasSemanaLetiva('atual', $quinta14h);
        $this->assert(count($diasSemana) === 5, "obterDiasSemanaLetiva retorna exatamente 5 dias úteis");
        $this->assert($diasSemana[0]['dia_semana'] === 1, "Primeiro dia é Segunda-feira");
        $this->assert($diasSemana[4]['dia_semana'] === 5, "Último dia é Sexta-feira");
        echo "\n";
    }

    /**
     * 2. Testando Travas Rígidas de Corte Operacional (RN-02)
     */
    private function testarTravasRigidasDeCorte(): void
    {
        echo "2. Testando Travas Rígidas de Corte Operacional (RN-02):\n";

        $dataConsumo = '2026-09-21';

        // Turno Matutino: Corte às 08:00
        $manhaAntes = new \DateTimeImmutable('2026-09-21 07:55:00');
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataConsumo, 'matutino', $manhaAntes) === true, "Matutino aberto antes das 08:00 (07:55)");

        $manhaNoCorte = new \DateTimeImmutable('2026-09-21 08:00:00');
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataConsumo, 'matutino', $manhaNoCorte) === false, "Matutino encerrado pontualmente às 08:00");

        $manhaApos = new \DateTimeImmutable('2026-09-21 08:01:00');
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataConsumo, 'matutino', $manhaApos) === false, "Matutino bloqueado após 08:00 (08:01)");

        // Turno Vespertino: Corte às 13:30
        $tardeAntes = new \DateTimeImmutable('2026-09-21 13:25:00');
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataConsumo, 'vespertino', $tardeAntes) === true, "Vespertino aberto antes das 13:30 (13:25)");

        $tardeNoCorte = new \DateTimeImmutable('2026-09-21 13:30:00');
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataConsumo, 'vespertino', $tardeNoCorte) === false, "Vespertino encerrado pontualmente às 13:30");

        $tardeApos = new \DateTimeImmutable('2026-09-21 13:35:00');
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataConsumo, 'vespertino', $tardeApos) === false, "Vespertino bloqueado após 13:30 (13:35)");

        // Datas futuras
        $dataFutura = '2026-09-22';
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataFutura, 'matutino', $manhaApos) === true, "Data futura permanece aberta para compras");

        // Datas passadas
        $dataPassada = '2026-09-20';
        $this->assert(Pedido::podeSerAlteradoOuCancelado($dataPassada, 'matutino', $manhaAntes) === false, "Data passada está bloqueada");

        // Detalhes do status de corte
        $statusHoje = Pedido::obterStatusCorte($dataConsumo, 'matutino', $manhaAntes);
        $this->assert($statusHoje['pode_alterar'] === true, "Status confirma que pode alterar");
        $this->assert($statusHoje['hora_corte'] === '08:00', "Identifica horário de corte 08:00 para matutino");
        $this->assert($statusHoje['minutos_restantes'] === 5, "Calcula exatamente 5 minutos restantes para as 08:00");
        echo "\n";
    }

    private function obterMomentoAberto(string $dataConsumo): \DateTimeImmutable
    {
        $dt = new \DateTimeImmutable($dataConsumo);
        $diaSemana = (int)$dt->format('N');
        $diasVoltar = $diaSemana - 1;
        return $dt->modify("-{$diasVoltar} days 07:30:00");
    }

    /**
     * 3. Testando Criação Atômica de Pedido com Débito e Reserva de Estoque
     */
    private function testarCriacaoPedidoEDebitoCarteira(): void
    {
        echo "3. Testando Criação Atômica de Pedido e Débito na Carteira:\n";

        $resp = $this->criarResponsavelTeste(80.00); // Saldo inicial R$ 80,00
        $aluno = $this->criarAlunoTeste($resp['id'], 'matutino');
        $prodSalgado = $this->criarProdutoTeste('salgado_diario', 7.50, 0, 'matutino');
        $prodInd = $this->criarProdutoTeste('industrializado_continuo', 4.50, 20, 'matutino');

        $dataConsumo = date('Y-m-d', strtotime('+2 days'));
        // Garante que é dia letivo
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+4 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        $itens = [
            ['produto_id' => $prodSalgado['id'], 'quantidade' => 1], // R$ 7,50
            ['produto_id' => $prodInd['id'], 'quantidade' => 2],     // R$ 9,00
        ]; // Total: R$ 16,50

        $pedido = Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, $itens, $momento);
        $this->cleanups['pedidos'][] = $pedido['id'];

        $this->assert(!empty($pedido['id']), "Pedido criado com ID persistido no banco");
        $this->assert(str_starts_with($pedido['codigo_pedido'], 'PED-'), "Código de pedido gerado com prefixo PED-");
        $this->assert($pedido['valor_total'] === 16.50, "Valor total do pedido calculado corretamente em R$ 16,50");
        $this->assert($pedido['status'] === 'agendado', "Status inicial do pedido é 'agendado'");

        // Verifica débito na carteira familiar (R$ 80 - R$ 16,50 = R$ 63,50)
        $saldoNovo = Responsavel::consultarSaldo($resp['id']);
        $this->assert($saldoNovo === 63.50, "Saldo da carteira debitado com precisão para R$ 63,50");

        // Verifica registro no livro-razão (transacoes_carteira)
        $tx = TransacaoCarteira::buscarPorReferencia($pedido['codigo_pedido']);
        $this->assert($tx !== null, "Lançamento contábil registrado no livro-razão com o código do pedido");
        $this->assert((float)$tx['valor'] === 16.50, "Valor do débito registrado em 16.50");
        $this->assert($tx['tipo_transacao'] === TransacaoCarteira::TIPO_DEBITO_PEDIDO, "Tipo de transação é 'debito_pedido'");
        $this->assert((float)$tx['saldo_resultante'] === 63.50, "Saldo resultante registrado linha a linha em 63.50");

        // Verifica itens do pedido e tickets
        $itensDb = ItemPedido::listarPorPedido($pedido['id']);
        $this->assert(count($itensDb) === 2, "2 itens detalhados gravados na tabela itens_pedido");

        $stmtTickets = $this->pdo->prepare("SELECT * FROM tickets WHERE pedido_id = :pid");
        $stmtTickets->execute(['pid' => $pedido['id']]);
        $tickets = $stmtTickets->fetchAll(\PDO::FETCH_ASSOC);
        // 1 salgado + 2 industrializados = 3 tickets térmicos
        $this->assert(count($tickets) === 3, "3 tickets gerados individualmente para bobina de 80mm");
        $this->assert($tickets[0]['status'] === 'ativo', "Tickets gerados com status inicial 'ativo'");

        // Verifica reserva de estoque de industrializados (RN-12)
        $prodIndDb = Produto::buscarPorId($prodInd['id']);
        $this->assert((int)$prodIndDb['estoque_reservado'] === 2, "Estoque reservado de industrializados incrementado em 2");
        echo "\n";
    }

    /**
     * 4. Testando Trava de Saldo Insuficiente no Agendamento (RN-09)
     */
    private function testarTravaSaldoInsuficiente(): void
    {
        echo "4. Testando Trava de Saldo Insuficiente no Agendamento (RN-09):\n";

        $resp = $this->criarResponsavelTeste(10.00); // Tem apenas R$ 10,00
        $aluno = $this->criarAlunoTeste($resp['id'], 'matutino');
        $prodCaro = $this->criarProdutoTeste('salgado_diario', 15.00, 0, 'matutino'); // Custa R$ 15,00

        $dataConsumo = date('Y-m-d', strtotime('+3 days'));
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+5 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        $bloqueou = false;
        $msgErro = '';
        try {
            Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, [
                ['produto_id' => $prodCaro['id'], 'quantidade' => 1]
            ], $momento);
        } catch (\DomainException $e) {
            $bloqueou = true;
            $msgErro = $e->getMessage();
        }

        $this->assert($bloqueou === true, "Recusa agendamento quando saldo da carteira é insuficiente");
        $this->assert(str_contains($msgErro, 'Saldo insuficiente'), "Mensagem de erro explicita insuficiência de saldo");
        $this->assert(Responsavel::consultarSaldo($resp['id']) === 10.00, "Saldo bancário permaneceu intacto em R$ 10,00");
        echo "\n";
    }

    /**
     * 5. Testando Reserva de Estoque e Itens Esgotados (RN-12 e RN-13)
     */
    private function testarReservaEstoqueEEsgotados(): void
    {
        echo "5. Testando Reserva de Estoque e Trava de Esgotados (RN-12 e RN-13):\n";

        $resp = $this->criarResponsavelTeste(100.00);
        $aluno = $this->criarAlunoTeste($resp['id'], 'matutino');
        // Produto com apenas 2 unidades físicas disponíveis
        $prodLimitado = $this->criarProdutoTeste('industrializado_continuo', 5.00, 2, 'matutino');

        $dataConsumo = date('Y-m-d', strtotime('+2 days'));
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+4 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        // Tenta comprar 3 unidades (disponível: 2) -> deve falhar
        $bloqueouExcesso = false;
        try {
            Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, [
                ['produto_id' => $prodLimitado['id'], 'quantidade' => 3]
            ], $momento);
        } catch (\DomainException $e) {
            $bloqueouExcesso = true;
        }
        $this->assert($bloqueouExcesso === true, "Bloqueia agendamento se quantidade requerida exceder estoque disponível");

        // Compra exatamente as 2 unidades -> deve ter sucesso
        $pedSucesso = Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, [
            ['produto_id' => $prodLimitado['id'], 'quantidade' => 2]
        ], $momento);
        $this->cleanups['pedidos'][] = $pedSucesso['id'];

        $prodAtualizado = Produto::buscarPorId($prodLimitado['id']);
        $this->assert((int)$prodAtualizado['estoque_reservado'] === 2, "Estoque reservado atingiu as 2 unidades");

        // Agora o item está esgotado (disponível = 0)
        $cardapio = Produto::listarParaCardapio('matutino');
        $itemCardapio = null;
        foreach ($cardapio as $p) {
            if ((int)$p['id'] === (int)$prodLimitado['id']) {
                $itemCardapio = $p;
                break;
            }
        }
        $this->assert($itemCardapio !== null, "Produto localizado no cardápio");
        $this->assert($itemCardapio['esgotado'] === true, "Produto marcado com flag 'esgotado = true' (RN-13)");
        $this->assert($itemCardapio['estoque_disponivel'] === 0, "Estoque disponível zerado");
        echo "\n";
    }

    /**
     * 6. Testando Cancelamento com Estorno Integral de 100% (Fluxo 4 Cenário A, RN-03)
     */
    private function testarCancelamentoComEstornoIntegral(): void
    {
        echo "6. Testando Cancelamento com Estorno Integral de 100% (RN-03):\n";

        $resp = $this->criarResponsavelTeste(50.00);
        $aluno = $this->criarAlunoTeste($resp['id'], 'matutino');
        $prodInd = $this->criarProdutoTeste('industrializado_continuo', 10.00, 10, 'matutino');

        $dataConsumo = date('Y-m-d', strtotime('+3 days'));
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+5 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        // Agenda pedido de R$ 20,00 (saldo vai para R$ 30,00, reserva vai para 2)
        $pedido = Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, [
            ['produto_id' => $prodInd['id'], 'quantidade' => 2]
        ], $momento);
        $this->cleanups['pedidos'][] = $pedido['id'];

        $this->assert(Responsavel::consultarSaldo($resp['id']) === 30.00, "Saldo após agendamento é R$ 30,00");
        $this->assert((int)Produto::buscarPorId($prodInd['id'])['estoque_reservado'] === 2, "Reserva de estoque inicial é 2");

        // Cancela antes do corte
        $resCancel = Pedido::cancelarPedido($pedido['id'], $resp['id'], $momento);
        $this->assert($resCancel['status'] === 'cancelado', "Status retornado é 'cancelado'");
        $this->assert($resCancel['valor_estorno'] === 20.00, "Valor do estorno integral é R$ 20,00");

        // Verifica estorno na carteira familiar (30 + 20 = 50.00)
        $saldoRestaurado = Responsavel::consultarSaldo($resp['id']);
        $this->assert($saldoRestaurado === 50.00, "Saldo na carteira foi 100% estornado para R$ 50,00 no MySQL");

        // Verifica lançamento contábil do estorno
        $stmtTx = $this->pdo->prepare("SELECT * FROM transacoes_carteira WHERE responsavel_id = :rid ORDER BY id DESC LIMIT 1");
        $stmtTx->execute(['rid' => $resp['id']]);
        $txEstorno = $stmtTx->fetch(\PDO::FETCH_ASSOC);
        $this->assert($txEstorno['tipo_transacao'] === TransacaoCarteira::TIPO_ESTORNO_CANCELAMENTO, "Tipo de transação gravado como 'estorno_cancelamento'");
        $this->assert((float)$txEstorno['valor'] === 20.00, "Valor creditado de 20.00");
        $this->assert((float)$txEstorno['saldo_resultante'] === 50.00, "Saldo resultante após estorno é 50.00");

        // Verifica liberação da reserva no estoque físico
        $prodPosEstorno = Produto::buscarPorId($prodInd['id']);
        $this->assert((int)$prodPosEstorno['estoque_reservado'] === 0, "Reserva de estoque foi liberada para 0");

        // Verifica status dos tickets
        $stmtTcks = $this->pdo->prepare("SELECT status FROM tickets WHERE pedido_id = :pid");
        $stmtTcks->execute(['pid' => $pedido['id']]);
        $statuses = $stmtTcks->fetchAll(\PDO::FETCH_COLUMN);
        $todosCancelados = !in_array('ativo', $statuses, true);
        $this->assert($todosCancelados === true, "Todos os tickets associados foram alterados para 'cancelado'");
        echo "\n";
    }

    /**
     * 7. Testando Recusa de Cancelamento e Edição Após o Corte (RN-02)
     */
    private function testarRecusaAposCorteOperacional(): void
    {
        echo "7. Testando Recusa de Cancelamento e Edição Após o Corte (RN-02):\n";

        $resp = $this->criarResponsavelTeste(40.00);
        $aluno = $this->criarAlunoTeste($resp['id'], 'matutino');
        $prod = $this->criarProdutoTeste('salgado_diario', 8.00, 0, 'matutino');

        // Pedido para hoje matutino criado antes do corte
        $hoje = date('Y-m-d');
        $manhaAntes = new \DateTimeImmutable("{$hoje} 07:30:00");
        $pedido = Pedido::criarPedido($resp['id'], $aluno['id'], $hoje, [
            ['produto_id' => $prod['id'], 'quantidade' => 1]
        ], $manhaAntes);
        $this->cleanups['pedidos'][] = $pedido['id'];

        // Simula tentativa de cancelamento após as 08:00 (ex: 08:05)
        $manhaApos = new \DateTimeImmutable("{$hoje} 08:05:00");
        $bloqueouCancelamento = false;
        $msgErroCancel = '';

        try {
            Pedido::cancelarPedido($pedido['id'], $resp['id'], $manhaApos);
        } catch (\DomainException $e) {
            $bloqueouCancelamento = true;
            $msgErroCancel = $e->getMessage();
        }

        $this->assert($bloqueouCancelamento === true, "Cancelamento após às 08:00 é terminantemente recusado pelo sistema");
        $this->assert(str_contains($msgErroCancel, '08:00'), "Mensagem de erro cita horário limite de corte das 08:00");

        // Simula tentativa de edição após as 08:00
        $bloqueouEdicao = false;
        try {
            Pedido::editarPedido($pedido['id'], $resp['id'], [
                ['produto_id' => $prod['id'], 'quantidade' => 2]
            ], $manhaApos);
        } catch (\DomainException $e) {
            $bloqueouEdicao = true;
        }

        $this->assert($bloqueouEdicao === true, "Edição após o horário de corte é terminantemente recusada");

        // Pedido e saldo permanecem intactos
        $pedDb = Pedido::buscarPorId($pedido['id']);
        $this->assert($pedDb['status'] === 'agendado', "Pedido permaneceu com status 'agendado'");
        $this->assert(Responsavel::consultarSaldo($resp['id']) === 32.00, "Saldo bancário permaneceu debitado em R$ 32,00 sem estorno indevido");
        echo "\n";
    }

    /**
     * 8. Testando Edição de Pedido com Compensação Financeira (RN-04)
     */
    private function testarEdicaoComCompensacaoFinanceira(): void
    {
        echo "8. Testando Edição de Pedido com Compensação Financeira (RN-04):\n";

        $resp = $this->criarResponsavelTeste(60.00);
        $aluno = $this->criarAlunoTeste($resp['id'], 'matutino');
        $prodA = $this->criarProdutoTeste('salgado_diario', 10.00, 0, 'matutino');
        $prodB = $this->criarProdutoTeste('industrializado_continuo', 5.00, 10, 'matutino');

        $dataConsumo = date('Y-m-d', strtotime('+2 days'));
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+4 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        // 1. Pedido original: 1x ProdA (R$ 10,00). Saldo vai de 60 para 50.
        $pedido = Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, [
            ['produto_id' => $prodA['id'], 'quantidade' => 1]
        ], $momento);
        $this->cleanups['pedidos'][] = $pedido['id'];
        $this->assert(Responsavel::consultarSaldo($resp['id']) === 50.00, "Saldo inicial após pedido original é R$ 50,00");

        // 2. Edição mais cara: altera para 1x ProdA (R$ 10) + 2x ProdB (R$ 10) = R$ 20,00 (Diferença: +R$ 10,00)
        $resEd1 = Pedido::editarPedido($pedido['id'], $resp['id'], [
            ['produto_id' => $prodA['id'], 'quantidade' => 1],
            ['produto_id' => $prodB['id'], 'quantidade' => 2],
        ], $momento);

        $this->assert($resEd1['status'] === 'editado', "Status do pedido atualizado para 'editado'");
        $this->assert($resEd1['diferenca'] === 10.00, "Diferença calculada com exatidão em +R$ 10,00");
        $this->assert(Responsavel::consultarSaldo($resp['id']) === 40.00, "Diferença adicional de R$ 10,00 foi debitada (saldo: R$ 40,00)");
        $this->assert((int)Produto::buscarPorId($prodB['id'])['estoque_reservado'] === 2, "Reserva de ProdB incrementada para 2");

        // 3. Edição mais barata: altera para apenas 1x ProdB (R$ 5,00). Total cai de 20 para 5 (Diferença: -R$ 15,00)
        $resEd2 = Pedido::editarPedido($pedido['id'], $resp['id'], [
            ['produto_id' => $prodB['id'], 'quantidade' => 1],
        ], $momento);

        $this->assert($resEd2['diferenca'] === -15.00, "Diferença negativa calculada em -R$ 15,00");
        $this->assert(Responsavel::consultarSaldo($resp['id']) === 55.00, "Estorno de R$ 15,00 creditado de volta na carteira (saldo: R$ 55,00)");
        $this->assert((int)Produto::buscarPorId($prodB['id'])['estoque_reservado'] === 1, "Reserva de ProdB reajustada para 1 unidade");
        echo "\n";
    }

    /**
     * 9. Testando Detecção Preventiva de Alérgenos e Tag no Ticket Térmico (RN-16 e RN-17)
     */
    private function testarDeteccaoAlergenosETagTicket(): void
    {
        echo "9. Testando Detecção de Alérgenos e Tag Obrigatória no Ticket (RN-16 e RN-17):\n";

        $resp = $this->criarResponsavelTeste(50.00);
        $aluno = $this->criarAlunoTeste($resp['id'], 'vespertino');

        // Vincula alérgeno Lactose ao aluno (ID 2 = Lactose no catálogo oficial)
        $alergLactose = RestricaoAlimentar::buscarPorNome('Lactose');
        $lactoseId = (int)$alergLactose['id'];
        RestricaoAlimentar::sincronizarAluno($aluno['id'], [$lactoseId]);

        // Cria produto que contém Lactose e Glúten
        $alergGluten = RestricaoAlimentar::buscarPorNome('Glúten');
        $glutenId = (int)$alergGluten['id'];
        $prodMisto = $this->criarProdutoTeste('salgado_diario', 8.50, 0, 'vespertino');
        Produto::sincronizarAlergenos($prodMisto['id'], [$lactoseId, $glutenId]);

        $dataConsumo = date('Y-m-d', strtotime('+3 days'));
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+5 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        // Agenda o lanche conflitante
        $pedido = Pedido::criarPedido($resp['id'], $aluno['id'], $dataConsumo, [
            ['produto_id' => $prodMisto['id'], 'quantidade' => 1]
        ], $momento);
        $this->cleanups['pedidos'][] = $pedido['id'];

        // Consulta o ticket emitido
        $stmt = $this->pdo->prepare("SELECT tag_alergenos FROM tickets WHERE pedido_id = :pid LIMIT 1");
        $stmt->execute(['pid' => $pedido['id']]);
        $tag = $stmt->fetchColumn();

        $this->assert(!empty($tag), "Ticket térmico possui tag_alergenos preenchida");
        $this->assert(str_contains($tag, 'ATENÇÃO: CONTÉM LACTOSE'), "Tag de alerta destaca a incompatibilidade com Lactose no ticket");
        echo "\n";
    }

    /**
     * 10. Testando Titularidade Familiar e Isolamento de Dados LGPD
     */
    private function testarTitularidadeLGPDEIsolamento(): void
    {
        echo "10. Testando Titularidade Familiar e Isolamento LGPD:\n";

        $familiaAlfa = $this->criarResponsavelTeste(50.00);
        $alunoAlfa = $this->criarAlunoTeste($familiaAlfa['id'], 'matutino');

        $familiaBeta = $this->criarResponsavelTeste(50.00);
        $alunoBeta = $this->criarAlunoTeste($familiaBeta['id'], 'matutino');

        $prod = $this->criarProdutoTeste('salgado_diario', 6.00, 0, 'matutino');

        $dataConsumo = date('Y-m-d', strtotime('+2 days'));
        if (date('N', strtotime($dataConsumo)) >= 6) {
            $dataConsumo = date('Y-m-d', strtotime('+4 days'));
        }

        $momento = $this->obterMomentoAberto($dataConsumo);

        // Família Alfa cria pedido
        $pedAlfa = Pedido::criarPedido($familiaAlfa['id'], $alunoAlfa['id'], $dataConsumo, [
            ['produto_id' => $prod['id'], 'quantidade' => 1]
        ], $momento);
        $this->cleanups['pedidos'][] = $pedAlfa['id'];

        // Família Beta tenta cancelar pedido da Família Alfa -> Deve bloquear!
        $bloqueouCancelTerceiro = false;
        try {
            Pedido::cancelarPedido($pedAlfa['id'], $familiaBeta['id'], $momento);
        } catch (\DomainException $e) {
            $bloqueouCancelTerceiro = true;
        }
        $this->assert($bloqueouCancelTerceiro === true, "Família Beta é categoricamente bloqueada de cancelar pedido da Família Alfa");

        // Família Beta tenta agendar para o aluno da Família Alfa -> Deve bloquear!
        $bloqueouAgendamentoTerceiro = false;
        try {
            Pedido::criarPedido($familiaBeta['id'], $alunoAlfa['id'], $dataConsumo, [
                ['produto_id' => $prod['id'], 'quantidade' => 1]
            ], $momento);
        } catch (\DomainException $e) {
            $bloqueouAgendamentoTerceiro = true;
        }
        $this->assert($bloqueouAgendamentoTerceiro === true, "Família Beta é bloqueada de agendar lanches para aluno da Família Alfa");
        echo "\n";
    }

    // ==========================================
    // MÉTODOS AUXILIARES DE CRIAÇÃO E TEARDOWN
    // ==========================================

    private function criarResponsavelTeste(float $saldoInicial = 0.00): array
    {
        $cpf = '999' . sprintf('%06d', random_int(100000, 999999)) . '10';
        $resp = Responsavel::localizarOuCriarPorCpf([
            'cpf'      => $cpf,
            'nome'     => 'Responsável Teste F10 ' . random_int(100, 999),
            'whatsapp' => '(11) 98888-7777',
            'email'    => "teste_f10_{$cpf}@exemplo.com"
        ]);
        $this->cleanups['responsaveis'][] = $resp['id'];

        if ($saldoInicial > 0) {
            $c = TransacaoCarteira::creditar($resp['id'], $saldoInicial, TransacaoCarteira::TIPO_RECARGA_PIX, 'Carga de teste F10');
            $this->cleanups['transacoes_carteira'][] = $c['id'];
        }

        return $resp;
    }

    private function criarAlunoTeste(int $respId, string $turno = 'matutino'): array
    {
        $matricula = 'MAT-F10-' . random_int(10000, 99999);
        $turma = Turma::localizarOuCriar('6º Ano', '6º Ano B - Teste', $turno);
        $this->cleanups['turmas'][] = $turma['id'];

        $alunoId = Aluno::criar([
            'responsavel_id' => $respId,
            'turma_id'       => $turma['id'],
            'matricula'      => $matricula,
            'nome'           => 'Aluno Teste F10 ' . random_int(100, 999),
            'turno'          => $turno,
            'ativo'          => 1
        ]);
        $this->cleanups['alunos'][] = $alunoId;

        return Aluno::buscarPorId($alunoId);
    }

    private function criarProdutoTeste(string $tipo, float $preco, int $estoque = 0, string $turno = 'ambos'): array
    {
        // Pega ou cria uma categoria
        $cat = Categoria::buscarPorNome('Salgados Assados');
        $catId = $cat ? (int)$cat['id'] : 1;

        $cod = 'PRF10-' . random_int(1000, 9999);
        $prodId = Produto::criar([
            'categoria_id'          => $catId,
            'codigo_interno'        => $cod,
            'descricao'             => "Produto Teste F10 {$cod}",
            'preco_venda'           => $preco,
            'tipo'                  => $tipo,
            'turno_disponibilidade' => $turno,
            'estoque_atual'         => $estoque,
            'estoque_reservado'     => 0,
            'status_pausa'          => 'ativo'
        ]);
        $this->cleanups['produtos'][] = $prodId;

        return Produto::buscarPorId($prodId);
    }

    private function teardown(): void
    {
        // Limpa registros criados durante os testes
        if (!empty($this->cleanups['pedidos'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['pedidos']));
            $this->pdo->exec("DELETE FROM tickets WHERE pedido_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM itens_pedido WHERE pedido_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM pedidos WHERE id IN ({$ids})");
        }

        if (!empty($this->cleanups['transacoes_carteira'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['transacoes_carteira']));
            $this->pdo->exec("DELETE FROM transacoes_carteira WHERE id IN ({$ids})");
        }

        if (!empty($this->cleanups['produtos'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['produtos']));
            $this->pdo->exec("DELETE FROM produto_alergenos WHERE produto_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM produtos WHERE id IN ({$ids})");
        }

        if (!empty($this->cleanups['alunos'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['alunos']));
            $this->pdo->exec("DELETE FROM aluno_restricoes WHERE aluno_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM alunos WHERE id IN ({$ids})");
        }

        if (!empty($this->cleanups['turmas'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['turmas']));
            $this->pdo->exec("DELETE FROM turmas WHERE id IN ({$ids})");
        }

        if (!empty($this->cleanups['responsaveis'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['responsaveis']));
            $this->pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM responsaveis WHERE id IN ({$ids})");
        }

        if (!empty($this->cleanups['calendario_letivo'])) {
            foreach ($this->cleanups['calendario_letivo'] as $dt) {
                $this->pdo->exec("DELETE FROM calendario_letivo WHERE data = '{$dt}'");
            }
        }
    }
}

$test = new Fase10Test();
$test->run();
