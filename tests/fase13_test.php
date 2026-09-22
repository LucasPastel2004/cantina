<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Bateria de Testes Automatizados da Fase 13: Ponto de Venda (PDV) e Operação de Balcão
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace Tests;

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\SessaoCaixa;
use App\Models\VendaBalcao;
use App\Models\ItemVendaBalcao;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Colaborador;
use App\Models\Aluno;
use App\Models\Responsavel;
use App\Models\Turma;
use App\Models\Pedido;
use App\Models\ItemPedido;
use App\Models\Ticket;
use App\Models\TransacaoCarteira;
use App\Models\Auditoria;
use App\Services\PixService;
use App\Services\PdfService;

class Fase13Test
{
    private \PDO $pdo;
    private int $totalAssercoes = 0;
    private int $assercoesAprovadas = 0;
    private array $falhas = [];

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO SUÍTE DE TESTES DA FASE 13 — PDV E OPERAÇÃO DE BALCÃO\n";
        echo "======================================================================\n\n";

        $this->testarSessaoCaixaAberturaETotais();
        $this->testarRegraCaixaFechado();
        $this->testarVendaAvulsaDinheiroComTrocoEEstoque();
        $this->testarVendaAvulsaPixBalcao();
        $this->testarConsumoColaboradorDescontoFolha();
        $this->testarListagemPdvEBuscaColaborador();
        $this->testarEstornoBalcaoComCreditoImediato();
        $this->testarAproveitamentoSobrasRecreio();
        $this->testarComprovanteTermicoPdfEHtml();

        // Encerra sessões de caixa abertas durante os testes para manter ambiente limpo
        $this->pdo->exec("UPDATE sessoes_caixa SET status = 'fechado', fechado_em = NOW(), total_informado_gaveta = (fundo_troco_inicial + total_dinheiro_avulso + total_dinheiro_recargas), diferenca_quebra_sobra = 0.00 WHERE observacoes = 'Abertura para testes' AND status = 'aberto'");

        echo "\n----------------------------------------------------------------------\n";
        echo sprintf(" RESULTADO FINAL: %d/%d asserções aprovadas (%.1f%%)\n",
            $this->assercoesAprovadas,
            $this->totalAssercoes,
            ($this->totalAssercoes > 0) ? ($this->assercoesAprovadas / $this->totalAssercoes) * 100 : 0
        );
        echo "----------------------------------------------------------------------\n\n";

        if (!empty($this->falhas)) {
            echo "OCORRERAM FALHAS:\n";
            foreach ($this->falhas as $falha) {
                echo " [FAIL] {$falha}\n";
            }
            exit(1);
        } else {
            echo ">>> TODOS OS TESTES DA FASE 13 FORAM APROVADOS COM SUCESSO! <<<\n\n";
        }
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesAprovadas++;
            echo " [PASS] {$descricao}\n";
        } else {
            $this->falhas[] = $descricao;
            echo " [FAIL] {$descricao}\n";
        }
    }

    private function obterOperadorId(): int
    {
        $stmt = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL LIMIT 1");
        $id = (int)$stmt->fetchColumn();
        if (!$id) {
            $this->pdo->exec("INSERT INTO usuarios (nome, usuario, email, senha_hash, perfil, ativo, created_at) VALUES ('Operador Teste', 'op_f13', 'op13@cantina.com', 'hash', 'operador', 1, NOW())");
            $id = (int)$this->pdo->lastInsertId();
        }
        return $id;
    }

    private function obterCategoriaId(): int
    {
        $stmt = $this->pdo->query("SELECT id FROM categorias WHERE deleted_at IS NULL LIMIT 1");
        $id = (int)$stmt->fetchColumn();
        if (!$id) {
            $this->pdo->exec("INSERT INTO categorias (nome, ordem_exibicao, ativo, created_at) VALUES ('Geral PDV', 1, 1, NOW())");
            $id = (int)$this->pdo->lastInsertId();
        }
        return $id;
    }

    private function obterTurmaId(): int
    {
        $stmt = $this->pdo->query("SELECT id FROM turmas WHERE deleted_at IS NULL LIMIT 1");
        $id = (int)$stmt->fetchColumn();
        if (!$id) {
            $this->pdo->exec("INSERT INTO turmas (nome_turma, serie, turno, ordem_impressao, ativo, created_at) VALUES ('6º Ano A', '6º Ano', 'matutino', 1, 1, NOW())");
            $id = (int)$this->pdo->lastInsertId();
        }
        return $id;
    }

    private function testarSessaoCaixaAberturaETotais(): void
    {
        echo "\n--- [1] Teste de Sessão de Caixa (Abertura, Status e Totais) ---\n";

        $operadorId = $this->obterOperadorId();
        $turno = 'matutino';

        // Fecha qualquer sessão aberta de matutino hoje para começar teste limpo
        $this->pdo->exec("UPDATE sessoes_caixa SET status = 'fechado', fechado_em = NOW() WHERE turno = 'matutino' AND DATE(aberto_em) = CURDATE() AND status = 'aberto'");

        $sessaoAbertaInicial = SessaoCaixa::obterSessaoAberta($turno);
        $this->assert($sessaoAbertaInicial === null, "Nenhuma sessão de caixa aberta para matutino antes da abertura");

        // Abre caixa com R$ 50,00 de fundo
        $sessaoId = SessaoCaixa::abrir($operadorId, $turno, 50.00, 'Abertura para testes');
        $this->assert($sessaoId > 0, "Sessão de caixa aberta com ID #{$sessaoId}");

        $sessaoAberta = SessaoCaixa::obterSessaoAberta($turno);
        $this->assert($sessaoAberta !== null && (int)$sessaoAberta['id'] === $sessaoId, "obterSessaoAberta localizou a sessão ativa");
        $this->assert((float)$sessaoAberta['fundo_troco_inicial'] === 50.00, "Fundo de troco inicial gravado corretamente como R$ 50,00");
        $this->assert(SessaoCaixa::isCaixaAberto($turno) === true, "isCaixaAberto retorna true");

        // Tentativa de abrir novamente no mesmo turno deve disparar DomainException
        $disparouExcecaoDuplicada = false;
        try {
            SessaoCaixa::abrir($operadorId, $turno, 30.00);
        } catch (\DomainException $e) {
            $disparouExcecaoDuplicada = true;
        }
        $this->assert($disparouExcecaoDuplicada, "Abertura duplicada de caixa no mesmo turno bloqueada com DomainException");

        // Incremento de totais
        SessaoCaixa::incrementarTotais($sessaoId, 25.50, 40.00, 0.00);
        $sessaoAtualizada = SessaoCaixa::buscarPorId($sessaoId);
        $this->assert((float)$sessaoAtualizada['total_dinheiro_avulso'] === 25.50, "Total de dinheiro avulso incrementado para R$ 25,50");
        $this->assert((float)$sessaoAtualizada['total_pix_balcao'] === 40.00, "Total de Pix balcão incrementado para R$ 40,00");
    }

    private function testarRegraCaixaFechado(): void
    {
        echo "\n--- [2] Teste de Bloqueio em Caixa Fechado ---\n";

        $operadorId = $this->obterOperadorId();
        $catId = $this->obterCategoriaId();

        // Cria produto de teste
        $cod = 'PDV-BLOQ-' . rand(100, 999);
        $this->pdo->exec("INSERT INTO produtos (codigo_interno, descricao, categoria_id, tipo, preco_venda, estoque_atual, estoque_minimo, status_pausa, created_at) VALUES ('{$cod}', 'Item Teste Bloq', {$catId}, 'industrializado_continuo', 5.00, 10, 2, 'ativo', NOW())");
        $prodId = (int)$this->pdo->lastInsertId();

        // Usa ID de sessão inexistente ou fechada
        $disparouBloqueio = false;
        try {
            VendaBalcao::criarVenda([
                'tipo_venda'              => 'avulso_aluno',
                'forma_pagamento'         => 'dinheiro',
                'valor_dinheiro_recebido' => 10.00
            ], [
                ['produto_id' => $prodId, 'quantidade' => 1, 'preco_unitario' => 5.00]
            ], $operadorId, 999999);
        } catch (\DomainException $e) {
            $disparouBloqueio = true;
        }

        $this->assert($disparouBloqueio, "Venda no PDV rejeitada se a sessão de caixa estiver fechada ou inexistente");
    }

    private function testarVendaAvulsaDinheiroComTrocoEEstoque(): void
    {
        echo "\n--- [3] Teste de Venda Avulsa em Dinheiro com Troco e Baixa de Estoque ---\n";

        $operadorId = $this->obterOperadorId();
        $catId = $this->obterCategoriaId();
        $sessao = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = (int)$sessao['id'];

        $dinheiroAntes = (float)$sessao['total_dinheiro_avulso'];

        // Cria produto com 20 unidades em estoque
        $cod = 'PDV-DIN-' . rand(100, 999);
        $this->pdo->exec("INSERT INTO produtos (codigo_interno, descricao, categoria_id, tipo, preco_venda, estoque_atual, estoque_minimo, status_pausa, created_at) VALUES ('{$cod}', 'Pão de Queijo PDV', {$catId}, 'industrializado_continuo', 6.50, 20, 2, 'ativo', NOW())");
        $prodId = (int)$this->pdo->lastInsertId();

        // Realiza venda de 2 unidades = R$ 13,00, entregando nota de R$ 20,00 (troco esperado = R$ 7,00)
        $venda = VendaBalcao::criarVenda([
            'tipo_venda'              => 'avulso_aluno',
            'forma_pagamento'         => 'dinheiro',
            'valor_dinheiro_recebido' => 20.00
        ], [
            ['produto_id' => $prodId, 'quantidade' => 2, 'preco_unitario' => 6.50]
        ], $operadorId, $sessaoId);

        $this->assert($venda['id'] > 0, "Venda em dinheiro processada com ID #{$venda['id']}");
        $this->assert((float)$venda['valor_total'] === 13.00, "Valor total calculado como R$ 13,00");
        $this->assert((float)$venda['valor_dinheiro_recebido'] === 20.00, "Valor recebido gravado como R$ 20,00");
        $this->assert((float)$venda['troco_devolvido'] === 7.00, "Troco sugerido calculado com precisão como R$ 7,00");
        $this->assert(count($venda['itens']) === 1, "Itens da venda gravados com sucesso");

        // Verifica baixa física de estoque (20 - 2 = 18)
        $prodAtualizado = Produto::buscarPorId($prodId);
        $this->assert((int)$prodAtualizado['estoque_atual'] === 18, "Estoque físico baixado de 20 para 18 unidades");

        // Verifica movimentação de estoque
        $stmtMov = $this->pdo->prepare("SELECT * FROM estoque_movimentacoes WHERE produto_id = :prod_id AND tipo_movimentacao = 'saida_venda_avulsa' ORDER BY id DESC LIMIT 1");
        $stmtMov->execute(['prod_id' => $prodId]);
        $mov = $stmtMov->fetch(\PDO::FETCH_ASSOC);
        $this->assert($mov !== false && (int)$mov['quantidade'] === 2, "Movimentação em estoque_movimentacoes gravada com tipo saida_venda_avulsa");

        // Verifica incremento no caixa
        $sessaoApos = SessaoCaixa::buscarPorId($sessaoId);
        $this->assert((float)$sessaoApos['total_dinheiro_avulso'] === round($dinheiroAntes + 13.00, 2), "Total em dinheiro da sessão de caixa incrementado exatamente pelo total da venda");

        // Tentativa de comprar quantidade superior ao estoque deve falhar
        $falhouPorEstoque = false;
        try {
            VendaBalcao::criarVenda([
                'tipo_venda'              => 'avulso_aluno',
                'forma_pagamento'         => 'dinheiro',
                'valor_dinheiro_recebido' => 200.00
            ], [
                ['produto_id' => $prodId, 'quantidade' => 50, 'preco_unitario' => 6.50]
            ], $operadorId, $sessaoId);
        } catch (\DomainException $e) {
            $falhouPorEstoque = true;
        }
        $this->assert($falhouPorEstoque, "Tentativa de venda acima do saldo de estoque físico bloqueada com sucesso");
    }

    private function testarVendaAvulsaPixBalcao(): void
    {
        echo "\n--- [4] Teste de Venda Avulsa em Pix Balcão ---\n";

        $operadorId = $this->obterOperadorId();
        $catId = $this->obterCategoriaId();
        $sessao = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = (int)$sessao['id'];
        $pixAntes = (float)$sessao['total_pix_balcao'];

        $cod = 'PDV-PIX-' . rand(100, 999);
        $this->pdo->exec("INSERT INTO produtos (codigo_interno, descricao, categoria_id, tipo, preco_venda, estoque_atual, estoque_minimo, status_pausa, created_at) VALUES ('{$cod}', 'Suco Uva PDV', {$catId}, 'industrializado_continuo', 7.00, 15, 2, 'ativo', NOW())");
        $prodId = (int)$this->pdo->lastInsertId();

        $venda = VendaBalcao::criarVenda([
            'tipo_venda'      => 'avulso_aluno',
            'forma_pagamento' => 'pix_balcao'
        ], [
            ['produto_id' => $prodId, 'quantidade' => 2, 'preco_unitario' => 7.00]
        ], $operadorId, $sessaoId);

        $this->assert($venda['id'] > 0, "Venda em Pix Balcão processada com sucesso");
        $this->assert($venda['forma_pagamento'] === 'pix_balcao', "Forma de pagamento gravada como pix_balcao");
        $this->assert((float)$venda['valor_total'] === 14.00, "Valor total R$ 14,00");

        // Verifica caixa atualizado
        $sessaoApos = SessaoCaixa::buscarPorId($sessaoId);
        $this->assert((float)$sessaoApos['total_pix_balcao'] === round($pixAntes + 14.00, 2), "Total Pix da sessão de caixa atualizado para R$ " . number_format($sessaoApos['total_pix_balcao'], 2, ',', '.'));
    }

    private function testarConsumoColaboradorDescontoFolha(): void
    {
        echo "\n--- [5] Teste de Consumo de Colaborador (Desconto em Folha) ---\n";

        $operadorId = $this->obterOperadorId();
        $catId = $this->obterCategoriaId();
        $sessao = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = (int)$sessao['id'];

        // Cria colaborador
        $mat = 'COL-' . rand(1000, 9999);
        $this->pdo->exec("INSERT INTO colaboradores (matricula_funcional, nome, cargo, foto_url, ativo, created_at) VALUES ('{$mat}', 'Prof. Carlos Eduardo', 'Professor de Matemática', 'storage/uploads/public/prof_carlos.jpg', 1, NOW())");
        $colabId = (int)$this->pdo->lastInsertId();

        // Cria produto
        $cod = 'PDV-COL-' . rand(100, 999);
        $this->pdo->exec("INSERT INTO produtos (codigo_interno, descricao, categoria_id, tipo, preco_venda, estoque_atual, estoque_minimo, status_pausa, created_at) VALUES ('{$cod}', 'Café com Torrada', {$catId}, 'industrializado_continuo', 8.50, 10, 2, 'ativo', NOW())");
        $prodId = (int)$this->pdo->lastInsertId();

        // Tentativa sem colaborador informado deve falhar
        $falhouSemColab = false;
        try {
            VendaBalcao::criarVenda([
                'tipo_venda'      => 'desconto_folha',
                'forma_pagamento' => 'desconto_folha'
            ], [
                ['produto_id' => $prodId, 'quantidade' => 1, 'preco_unitario' => 8.50]
            ], $operadorId, $sessaoId);
        } catch (\InvalidArgumentException $e) {
            $falhouSemColab = true;
        }
        $this->assert($falhouSemColab, "Venda sob Desconto em Folha sem colaborador selecionado é bloqueada");

        // Venda com colaborador identificado
        $venda = VendaBalcao::criarVenda([
            'tipo_venda'      => 'desconto_folha',
            'forma_pagamento' => 'desconto_folha',
            'colaborador_id'  => $colabId
        ], [
            ['produto_id' => $prodId, 'quantidade' => 1, 'preco_unitario' => 8.50]
        ], $operadorId, $sessaoId);

        $this->assert($venda['id'] > 0, "Venda em folha concluída com ID #{$venda['id']}");
        $this->assert((int)$venda['colaborador_id'] === $colabId, "Vínculo de colaborador_id gravado corretamente");
        $this->assert($venda['colaborador_nome'] === 'Prof. Carlos Eduardo', "Nome do colaborador retornado na consulta detalhada");
        $this->assert($venda['colaborador_matricula'] === $mat, "Matrícula funcional retornada");

        // Métricas de vendas de hoje
        $metricas = VendaBalcao::obterMetricasHoje($sessaoId);
        $this->assert($metricas['total_folha'] >= 8.50, "Métrica de total em folha apurada com sucesso");
    }

    private function testarListagemPdvEBuscaColaborador(): void
    {
        echo "\n--- [6] Teste de Métodos de Consulta do PDV ---\n";

        // Produto::listarParaPdv
        $produtosPdv = Produto::listarParaPdv();
        $this->assert(!empty($produtosPdv), "Produto::listarParaPdv retornou produtos ativos");
        $primeiro = $produtosPdv[0];
        $this->assert(array_key_exists('esgotado', $primeiro), "Produto contém flag booleana de esgotado");
        $this->assert(array_key_exists('estoque_atual', $primeiro), "Produto contém estoque_atual");

        // Colaborador::buscarParaPdv
        $buscaColab = Colaborador::buscarParaPdv('Carlos');
        $this->assert(!empty($buscaColab), "Colaborador::buscarParaPdv localizou pelo menos um colaborador");
        $this->assert(isset($buscaColab[0]['matricula_funcional']), "Resultado contém matrícula funcional");
    }

    private function testarEstornoBalcaoComCreditoImediato(): void
    {
        echo "\n--- [7] Teste de Estorno de Balcão com Devolução Imediata à Carteira ---\n";

        $operadorId = $this->obterOperadorId();
        $catId = $this->obterCategoriaId();

        // Cria responsável com saldo inicial de R$ 20,00
        $cpf = sprintf('%011d', rand(10000000000, 99999999999));
        $this->pdo->exec("INSERT INTO responsaveis (nome, cpf, email, whatsapp, senha_hash, saldo_atual, created_at) VALUES ('Mãe Teste Estorno', '{$cpf}', 'mae{$cpf}@teste.com', '11988887777', 'hash', 20.00, NOW())");
        $respId = (int)$this->pdo->lastInsertId();

        // Cria aluno
        $turmaId = $this->obterTurmaId();
        $mat = 'ALU-' . rand(1000, 9999);
        $this->pdo->exec("INSERT INTO alunos (matricula, nome, responsavel_id, turma_id, turno, ativo, created_at) VALUES ('{$mat}', 'Filho Teste Estorno', {$respId}, {$turmaId}, 'matutino', 1, NOW())");
        $alunoId = (int)$this->pdo->lastInsertId();

        // Cria produto
        $codProdEst = 'EST-' . rand(1000, 9999);
        $this->pdo->exec("INSERT INTO produtos (codigo_interno, descricao, categoria_id, tipo, preco_venda, estoque_atual, estoque_minimo, status_pausa, created_at) VALUES ('{$codProdEst}', 'Coxinha de Frango', {$catId}, 'salgado_diario', 7.50, 10, 2, 'ativo', NOW())");
        $prodId = (int)$this->pdo->lastInsertId();

        // Cria pedido e item
        $codPed = 'PED-EST-' . rand(100, 999);
        $this->pdo->exec("INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at) VALUES ('{$codPed}', {$respId}, {$alunoId}, CURDATE(), 'matutino', 7.50, 'agendado', NOW())");
        $pedId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES ({$pedId}, {$prodId}, 1, 7.50, 7.50)");
        $itemId = (int)$this->pdo->lastInsertId();

        // Cria ticket de papel
        $codTicket = 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $this->pdo->exec("INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, created_at) VALUES ({$pedId}, {$itemId}, '{$codTicket}', 1, 'ativo', NOW())");
        $ticketId = (int)$this->pdo->lastInsertId();

        // Saldo do responsável antes do estorno: R$ 20,00
        $respAntes = Responsavel::buscarPorId($respId);
        $this->assert((float)$respAntes['saldo_atual'] === 20.00, "Saldo da família antes do estorno é R$ 20,00");

        // Executa estorno no balcão por Falta de Ingrediente/Produto
        $ticketEstornado = Ticket::estornarNoBalcao(
            $ticketId,
            $operadorId,
            'Falta de Ingrediente/Produto',
            'Fornecedor não entregou o produto encomendado',
            '127.0.0.1'
        );

        $this->assert($ticketEstornado['ticket_status'] === 'estornado', "Status do ticket atualizado para estornado");
        $this->assert(!empty($ticketEstornado['estornado_em']), "Data de estorno gravada");
        $this->assert(str_contains($ticketEstornado['motivo_estorno'], 'Falta de Ingrediente/Produto'), "Motivo do estorno registrado no ticket");

        // Verifica crédito na carteira da família (20.00 + 7.50 = 27.50)
        $respApos = Responsavel::buscarPorId($respId);
        $this->assert((float)$respApos['saldo_atual'] === 27.50, "Saldo da família creditado integralmente no mesmo instante para R$ 27,50");

        // Verifica transação na carteira
        $stmtTrans = $this->pdo->prepare("SELECT * FROM transacoes_carteira WHERE responsavel_id = :resp_id AND tipo_transacao = 'estorno_balcao' ORDER BY id DESC LIMIT 1");
        $stmtTrans->execute(['resp_id' => $respId]);
        $trans = $stmtTrans->fetch(\PDO::FETCH_ASSOC);
        $this->assert($trans !== false && (float)$trans['valor'] === 7.50, "Transação de estorno_balcao gravada no livro-razão com valor de R$ 7,50");

        // Verifica log de auditoria compulsório
        $stmtAud = $this->pdo->prepare("SELECT * FROM logs_auditoria WHERE tabela_afetada = 'tickets' AND registro_id = :ticket_id AND acao = 'estorno_balcao'");
        $stmtAud->execute(['ticket_id' => $ticketId]);
        $aud = $stmtAud->fetch(\PDO::FETCH_ASSOC);
        $this->assert($aud !== false, "Evento de estorno registrado na tabela logs_auditoria");
    }

    private function testarAproveitamentoSobrasRecreio(): void
    {
        echo "\n--- [8] Teste de Aproveitamento de Sobras do Recreio ---\n";

        $operadorId = $this->obterOperadorId();
        $catId = $this->obterCategoriaId();

        // Cria responsável, aluno, produto e ticket
        $cpf = sprintf('%011d', rand(10000000000, 99999999999));
        $this->pdo->exec("INSERT INTO responsaveis (nome, cpf, email, whatsapp, senha_hash, saldo_atual, created_at) VALUES ('Pai Sobra Teste', '{$cpf}', 'pai{$cpf}@teste.com', '11977776666', 'hash', 50.00, NOW())");
        $respId = (int)$this->pdo->lastInsertId();

        $turmaId = $this->obterTurmaId();
        $mat = 'ALU-SBR-' . bin2hex(random_bytes(4));
        $this->pdo->exec("INSERT INTO alunos (matricula, nome, responsavel_id, turma_id, turno, ativo, created_at) VALUES ('{$mat}', 'Aluno Ausente Recreio', {$respId}, {$turmaId}, 'matutino', 1, NOW())");
        $alunoId = (int)$this->pdo->lastInsertId();

        $codProd = 'SOB-' . bin2hex(random_bytes(4));
        $this->pdo->exec("INSERT INTO produtos (codigo_interno, descricao, categoria_id, tipo, preco_venda, estoque_atual, estoque_minimo, status_pausa, created_at) VALUES ('{$codProd}', 'Esfiha de Carne', {$catId}, 'salgado_diario', 6.00, 0, 2, 'ativo', NOW())");
        $prodId = (int)$this->pdo->lastInsertId();

        $codPed = 'PED-SBR-' . bin2hex(random_bytes(4));
        $hoje = date('Y-m-d');
        $this->pdo->exec("INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at) VALUES ('{$codPed}', {$respId}, {$alunoId}, '{$hoje}', 'matutino', 6.00, 'agendado', NOW())");
        $pedId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES ({$pedId}, {$prodId}, 3, 6.00, 18.00)");
        $itemId = (int)$this->pdo->lastInsertId();

        $codTicket = 'TCK-SBR-' . bin2hex(random_bytes(4));
        $this->pdo->exec("INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, created_at) VALUES ({$pedId}, {$itemId}, '{$codTicket}', 1, 'impresso', NOW())");
        $ticketId = (int)$this->pdo->lastInsertId();

        // Consulta lista de sobras elegíveis
        $sobras = Ticket::listarNaoRetiradosTurno($hoje, 'matutino');
        $encontrouTicket = false;
        foreach ($sobras as $s) {
            if ((int)$s['ticket_id'] === $ticketId) {
                $encontrouTicket = true;
                break;
            }
        }
        $this->assert($encontrouTicket, "Ticket de aluno ausente listado entre as sobras elegíveis do turno");

        // Transfere 3 unidades do ticket para o estoque físico do PDV
        $res = Ticket::transferirSobrasNaoRetiradasParaBalcao([$ticketId], $operadorId, '127.0.0.1');
        $this->assert($res['total_pecas_transferidas'] === 3, "Transferência registrou 3 peças aproveitadas");

        // Verifica estoque atualizado (0 + 3 = 3)
        $prodApos = Produto::buscarPorId($prodId);
        $this->assert((int)$prodApos['estoque_atual'] === 3, "Estoque físico do produto incrementado de 0 para 3 unidades para venda avulsa");

        // Verifica movimentação aproveitamento_sobra
        $stmtMov = $this->pdo->prepare("SELECT * FROM estoque_movimentacoes WHERE produto_id = :prod_id AND tipo_movimentacao = 'aproveitamento_sobra' LIMIT 1");
        $stmtMov->execute(['prod_id' => $prodId]);
        $mov = $stmtMov->fetch(\PDO::FETCH_ASSOC);
        $this->assert($mov !== false && (int)$mov['quantidade'] === 3, "Movimentação em estoque_movimentacoes gravada como aproveitamento_sobra");
    }

    private function testarComprovanteTermicoPdfEHtml(): void
    {
        echo "\n--- [9] Teste de Comprovante Térmico (80mm) em PDF e HTML ---\n";

        $vendaMock = [
            'id'                      => 1234,
            'created_at'              => date('Y-m-d H:i:s'),
            'operador_nome'           => 'Maria Atendente',
            'sessao_caixa_id'         => 5,
            'sessao_turno'            => 'matutino',
            'tipo_venda'              => 'avulso_aluno',
            'forma_pagamento'         => 'dinheiro',
            'valor_total'             => 15.00,
            'valor_dinheiro_recebido' => 20.00,
            'troco_devolvido'         => 5.00,
            'colaborador_nome'        => null,
            'aluno_nome'              => 'Lucas Ferreira',
            'aluno_turma'             => '6º Ano A',
            'itens'                   => [
                ['quantidade' => 2, 'produto_descricao' => 'Pão de Queijo Mineiro', 'subtotal' => 10.00],
                ['quantidade' => 1, 'produto_descricao' => 'Suco de Laranja 300ml', 'subtotal' => 5.00]
            ]
        ];

        // Geração HTML
        $html = PdfService::gerarEspelhoComprovanteVendaBalcaoHtml($vendaMock);
        $this->assert(!empty($html), "Espelho HTML do comprovante gerado");
        $this->assert(str_contains($html, 'Comprovante de Venda #1234'), "HTML contém identificador da venda #1234");
        $this->assert(str_contains($html, 'Pão de Queijo Mineiro'), "HTML discrimina os itens comprados");
        $this->assert(str_contains($html, 'Lucas Ferreira'), "HTML identifica o cliente aluno");
        $this->assert(str_contains($html, '80mm'), "HTML configurado com folha térmica de 80mm");

        // Geração PDF
        $pdfBin = PdfService::gerarComprovanteVendaBalcaoPdf($vendaMock);
        $this->assert(!empty($pdfBin), "Conteúdo binário do PDF gerado");
        $this->assert(str_starts_with($pdfBin, '%PDF-1.4'), "Assinatura do arquivo é um PDF 1.4 válido");
        $this->assert(str_contains($pdfBin, '%%EOF'), "PDF contém marcador de encerramento %%EOF");
    }
}

// Execução direta
$teste = new Fase13Test();
$teste->run();
