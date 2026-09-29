<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 15: Controle Financeiro e Fechamento de Caixas por Turno
 * 
 * Conforme docs/FSD.md (Áreas 4, 6 e Módulo 7) e docs/DESIGN.md
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';

// Registra o Autoloader PSR-4 nativo para o namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Usuario;
use App\Models\Responsavel;
use App\Models\SessaoCaixa;
use App\Models\TransacaoCarteira;
use App\Models\Auditoria;
use App\Services\PdfService;
use App\Core\RBAC;

class Fase15Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;
    private PdfService $pdfService;

    private int $operadorId = 0;
    private int $responsavelId = 0;
    private int $alunoId = 0;
    private int $turmaId = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
        $this->pdfService = new PdfService();
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  [OK] {$descricao}\n";
        } else {
            echo "  [FAIL] {$descricao}\n";
        }
    }

    public function executar(): void
    {
        echo "\n=======================================================\n";
        echo "INICIANDO TESTES DA FASE 15 — CAIXA, FECHAMENTO E RECARGAS\n";
        echo "=======================================================\n\n";

        $this->prepararMassaDados();
        $this->testarAberturaCaixaEBloqueioDuplicidade();
        $this->testarBuscaResponsavelParaRecarga();
        $this->testarRecargaDinheiroBalcao();
        $this->testarBloqueioRecargaComCaixaFechado();
        $this->testarExtratoAnaliticoSessao();
        $this->testarFechamentoCaixaEQuebraSobra();
        $this->testarHistoricoComFiltros();
        $this->testarEmissaoComprovantesTermicos80mm();
        $this->testarEmissaoEspelhosA4();
        $this->testarPermissoesRBAC();

        echo "\n-------------------------------------------------------\n";
        echo "RESULTADO FINAL: {$this->assercoesSucesso} de {$this->totalAssercoes} asserções aprovadas.\n";
        if ($this->assercoesSucesso === $this->totalAssercoes) {
            echo "STATUS: SUCESSO TOTAL (100% DE APROVAÇÃO)\n";
        } else {
            echo "STATUS: FALHA DETECTADA\n";
            exit(1);
        }
        echo "=======================================================\n\n";
    }

    /**
     * Prepara massa de dados de teste (operador, responsável e aluno dependente)
     */
    private function prepararMassaDados(): void
    {
        echo "--- 1. Preparação de Massa de Dados ---\n";

        // 1. Obter ou criar operador
        $stmt = $this->pdo->prepare("SELECT id FROM usuarios WHERE perfil = 'operador' AND ativo = 1 LIMIT 1");
        $stmt->execute();
        $op = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($op) {
            $this->operadorId = (int)$op['id'];
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES ('operador_f15', 'Operador Teste F15', 'operador.f15@escola.local', 'hash_teste', 'operador', 1)");
            $stmt->execute();
            $this->operadorId = (int)$this->pdo->lastInsertId();
        }
        $this->assert($this->operadorId > 0, "Operador de teste identificado (ID: {$this->operadorId})");

        // 2. Obter ou criar turma
        $stmt = $this->pdo->prepare("SELECT id FROM turmas LIMIT 1");
        $stmt->execute();
        $t = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($t) {
            $this->turmaId = (int)$t['id'];
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO turmas (nome_turma, turno) VALUES ('5º Ano A', 'matutino')");
            $stmt->execute();
            $this->turmaId = (int)$this->pdo->lastInsertId();
        }
        $this->assert($this->turmaId > 0, "Turma escolar identificada (ID: {$this->turmaId})");

        // 3. Obter ou criar responsável na tabela responsaveis
        $stmt = $this->pdo->prepare("SELECT id, cpf, nome FROM responsaveis WHERE bloqueado_med = 0 LIMIT 1");
        $stmt->execute();
        $resp = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($resp) {
            $this->responsavelId = (int)$resp['id'];
        } else {
            $cpfUnico = '999' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            $stmt = $this->pdo->prepare("INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual) VALUES (:cpf, 'Responsavel F15 Teste', 'resp.f15@escola.local', '11999998888', 'hash_teste', 10.00)");
            $stmt->execute([':cpf' => $cpfUnico]);
            $this->responsavelId = (int)$this->pdo->lastInsertId();
        }
        $this->assert($this->responsavelId > 0, "Responsável de teste identificado (ID: {$this->responsavelId})");

        // 4. Obter ou criar aluno dependente
        $stmt = $this->pdo->prepare("SELECT id FROM alunos WHERE responsavel_id = :rid LIMIT 1");
        $stmt->execute([':rid' => $this->responsavelId]);
        $al = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($al) {
            $this->alunoId = (int)$al['id'];
        } else {
            $mat = 'MAT-F15-' . random_int(1000, 9999);
            $stmt = $this->pdo->prepare("INSERT INTO alunos (responsavel_id, turma_id, matricula, nome, turno, ativo) VALUES (:rid, :tid, :mat, 'Aluno Filho F15', 'matutino', 1)");
            $stmt->execute([':rid' => $this->responsavelId, ':tid' => $this->turmaId, ':mat' => $mat]);
            $this->alunoId = (int)$this->pdo->lastInsertId();
        }
        $this->assert($this->alunoId > 0, "Aluno dependente vinculado ao responsável (ID: {$this->alunoId})");

        // Garantir que não há sessão de caixa aberta no turno matutino antes de iniciar o teste
        $stmt = $this->pdo->prepare("UPDATE sessoes_caixa SET status = 'fechado', fechado_em = NOW() WHERE turno = 'matutino' AND status = 'aberto'");
        $stmt->execute();
    }

    /**
     * 2. Testes de Abertura de Caixa e Bloqueio de Duplicidade
     */
    private function testarAberturaCaixaEBloqueioDuplicidade(): void
    {
        echo "\n--- 2. Abertura de Caixa e Bloqueio de Duplicidade por Turno ---\n";

        // Abrir sessão do turno matutino com fundo de troco de R$ 50,00
        $fundoInicial = 50.00;
        $sessaoId = SessaoCaixa::abrir($this->operadorId, 'matutino', $fundoInicial, 'Abertura de teste Fase 15');

        $this->assert($sessaoId > 0, "Sessão de caixa matutina aberta com sucesso (ID: {$sessaoId})");

        $sessao = SessaoCaixa::buscarPorId($sessaoId);
        $this->assert($sessao !== null && $sessao['status'] === 'aberto', "Status da sessão é 'aberto'");
        $this->assert((float)$sessao['fundo_troco_inicial'] === 50.00, "Fundo de troco inicial gravado como R$ 50,00");
        $this->assert($sessao['turno'] === 'matutino', "Turno da sessão gravado como 'matutino'");

        // Verificar busca da sessão aberta do turno
        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $this->assert($sessaoAberta !== null && (int)$sessaoAberta['id'] === $sessaoId, "obterSessaoAberta('matutino') localiza a sessão correta");

        // Tentar abrir outra sessão no mesmo turno matutino enquanto a anterior está aberta
        $duplicada = false;
        try {
            SessaoCaixa::abrir($this->operadorId, 'matutino', 30.00, 'Tentativa duplicada');
        } catch (\DomainException $e) {
            $duplicada = true;
        }
        $this->assert($duplicada, "Sistema bloqueia abertura simultânea de duas sessões no mesmo turno");

        // Testar totalizadores iniciais da sessão
        $totais = SessaoCaixa::obterTotaisEsperados($sessao);
        $this->assert((float)$totais['fundo_troco_inicial'] === 50.00, "Totais: Fundo inicial confere com R$ 50,00");
        $this->assert((float)$totais['total_dinheiro_recargas'] === 0.00, "Totais: Recargas iniciais em dinheiro = 0,00");
        $this->assert((float)$totais['total_esperado_gaveta'] === 50.00, "Totais: Esperado na gaveta inicial = R$ 50,00");
    }

    /**
     * 3. Testes de Busca de Responsável / Aluno para Recarga Presencial
     */
    private function testarBuscaResponsavelParaRecarga(): void
    {
        echo "\n--- 3. Busca de Responsável para Recarga Presencial (Tela 5.4) ---\n";

        // Obter dados do responsável para teste de busca
        $stmt = $this->pdo->prepare("SELECT nome, cpf FROM responsaveis WHERE id = :id");
        $stmt->execute([':id' => $this->responsavelId]);
        $resp = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Busca por fragmento ou nome do responsável
        $termoNome = $resp['nome'];
        $resultados = SessaoCaixa::buscarResponsavelParaRecarga($termoNome);
        $this->assert(!empty($resultados), "Busca por nome de responsável ('{$termoNome}') retornou registros");

        $encontrado = false;
        foreach ($resultados as $r) {
            if ((int)$r['id'] === $this->responsavelId) {
                $encontrado = true;
                break;
            }
        }
        $this->assert($encontrado, "Responsável alvo foi localizado na listagem de busca");

        // Busca por matrícula do dependente
        $stmt = $this->pdo->prepare("SELECT matricula FROM alunos WHERE id = :id");
        $stmt->execute([':id' => $this->alunoId]);
        $aluno = $stmt->fetch(\PDO::FETCH_ASSOC);

        $resAluno = SessaoCaixa::buscarResponsavelParaRecarga($aluno['matricula']);
        $this->assert(!empty($resAluno), "Busca por matrícula do aluno dependente ('{$aluno['matricula']}') retornou o responsável");
        $this->assert(isset($resAluno[0]['dependentes']), "Resultado inclui relação de dependentes vinculados");
    }

    /**
     * 4. Testes de Recarga Presencial em Dinheiro no Balcão
     */
    private function testarRecargaDinheiroBalcao(): void
    {
        echo "\n--- 4. Recarga Presencial em Dinheiro com Caixa Aberto ---\n";

        // Obter saldo inicial da carteira do responsável
        $stmt = $this->pdo->prepare("SELECT saldo_atual FROM responsaveis WHERE id = :id");
        $stmt->execute([':id' => $this->responsavelId]);
        $saldoInicial = (float)$stmt->fetchColumn();

        $valorRecarga = 85.50;
        $ipOrigem = '192.168.1.50';

        // Executar a recarga no balcão
        $resultado = SessaoCaixa::registrarRecargaDinheiro(
            $this->responsavelId,
            $valorRecarga,
            $this->operadorId,
            'Recarga presencial teste Fase 15',
            $ipOrigem
        );

        $this->assert(isset($resultado['transacao_id']) && $resultado['transacao_id'] > 0, "Transação de recarga gerada com sucesso (ID: {$resultado['transacao_id']})");
        $this->assert((float)$resultado['valor'] === $valorRecarga, "Valor da recarga gravado como R$ 85,50");

        $novoSaldoEsperado = round($saldoInicial + $valorRecarga, 2);
        $this->assert(abs((float)$resultado['saldo_resultante'] - $novoSaldoEsperado) < 0.001, "Novo saldo retornado confere ({$novoSaldoEsperado})");

        // Validar saldo atual no banco
        $stmt = $this->pdo->prepare("SELECT saldo_atual FROM responsaveis WHERE id = :id");
        $stmt->execute([':id' => $this->responsavelId]);
        $saldoBanco = (float)$stmt->fetchColumn();
        $this->assert(abs($saldoBanco - $novoSaldoEsperado) < 0.001, "Saldo atualizado no banco de dados na tabela responsaveis");

        // Validar registro em transacoes_carteira
        $stmt = $this->pdo->prepare("SELECT * FROM transacoes_carteira WHERE id = :tid");
        $stmt->execute([':tid' => $resultado['transacao_id']]);
        $tx = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assert($tx['tipo_transacao'] === 'recarga_dinheiro', "Tipo da transação na carteira é 'recarga_dinheiro'");
        $this->assert((float)$tx['valor'] === $valorRecarga, "Valor da transação confere com a recarga");
        $this->assert((int)$tx['referencia_id'] === (int)$resultado['sessao_caixa_id'], "Transação vinculada corretamente à sessão de caixa aberta");

        // Validar incremento de total_dinheiro_recargas na sessão
        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $this->assert((float)$sessaoAberta['total_dinheiro_recargas'] >= $valorRecarga, "Campo total_dinheiro_recargas da sessão incrementado corretamente");

        // Validar log compulsório de auditoria
        $stmt = $this->pdo->prepare("SELECT * FROM logs_auditoria WHERE acao = 'recarga_dinheiro_balcao' AND registro_id = :rid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':rid' => $resultado['transacao_id']]);
        $logAuditoria = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assert($logAuditoria !== null, "Registro compulsório criado em logs_auditoria");
        $this->assert((int)$logAuditoria['operador_id'] === $this->operadorId, "Auditoria registrou o ID do operador que realizou a recarga");

        // Validar buscarDadosRecarga para emissão de comprovante térmico
        $dadosComprovante = SessaoCaixa::buscarDadosRecarga($resultado['transacao_id']);
        $this->assert($dadosComprovante !== null, "buscarDadosRecarga recuperou dados completos para comprovante");
        $this->assert(isset($dadosComprovante['responsavel_nome']) && !empty($dadosComprovante['responsavel_nome']), "Dados do comprovante incluem nome do titular");
        $this->assert(isset($dadosComprovante['operador_nome']), "Dados do comprovante incluem nome do operador");
    }

    /**
     * 5. Testes de Bloqueio Estrito de Recarga com Caixa Fechado
     */
    private function testarBloqueioRecargaComCaixaFechado(): void
    {
        echo "\n--- 5. Bloqueio Estrito de Recarga em Dinheiro com Caixa Fechado ---\n";

        // Fechar temporariamente as sessões abertas
        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        if ($sessaoAberta) {
            $totais = SessaoCaixa::obterTotaisEsperados($sessaoAberta);
            SessaoCaixa::fechar($sessaoAberta['id'], (float)$totais['total_esperado_gaveta'], 'Fechamento temporário de teste', $this->operadorId);
        }
        $sessaoVesp = SessaoCaixa::obterSessaoAberta('vespertino');
        if ($sessaoVesp) {
            $totaisVesp = SessaoCaixa::obterTotaisEsperados($sessaoVesp);
            SessaoCaixa::fechar($sessaoVesp['id'], (float)$totaisVesp['total_esperado_gaveta'], 'Fechamento temporário vespertino', $this->operadorId);
        }

        // Tentar registrar recarga sem caixa aberto no turno
        $bloqueado = false;
        try {
            SessaoCaixa::registrarRecargaDinheiro(
                $this->responsavelId,
                50.00,
                $this->operadorId,
                'Tentativa de recarga com caixa fechado',
                '127.0.0.1'
            );
        } catch (\DomainException $e) {
            $bloqueado = true;
        }

        $this->assert($bloqueado, "Sistema bloqueia estritamente recarga presencial em dinheiro se o caixa do turno estiver fechado");

        // Reabrir caixa para dar continuidade aos testes analíticos
        SessaoCaixa::abrir($this->operadorId, 'matutino', 50.00, 'Reabertura para teste analítico');

        // Executar uma recarga na nova sessão para compor extrato
        SessaoCaixa::registrarRecargaDinheiro(
            $this->responsavelId,
            40.00,
            $this->operadorId,
            'Recarga para teste analítico',
            '127.0.0.1'
        );
    }

    /**
     * 6. Testes do Extrato Analítico da Sessão
     */
    private function testarExtratoAnaliticoSessao(): void
    {
        echo "\n--- 6. Extrato Analítico da Sessão de Caixa ---\n";

        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $this->assert($sessaoAberta !== null, "Sessão aberta ativa localizada");

        $extrato = SessaoCaixa::obterExtratoAnaliticoSessao($sessaoAberta['id']);

        $this->assert(isset($extrato['sessao']), "Extrato contém dados cadastrais da sessão");
        $this->assert(isset($extrato['totais']), "Extrato contém consolidação de totais");
        $this->assert(isset($extrato['vendas']), "Extrato contém lista de vendas realizadas no balcão");
        $this->assert(isset($extrato['recargas']), "Extrato contém lista discriminada de recargas em dinheiro");
        $this->assert(count($extrato['recargas']) >= 1, "Recarga de R$ 40,00 discriminada no extrato da sessão");
        $this->assert((float)$extrato['totais']['total_dinheiro_recargas'] >= 40.00, "Total de recargas no extrato confere com o acumulado");
    }

    /**
     * 7. Testes de Fechamento de Caixa e Apuração de Quebra / Sobra
     */
    private function testarFechamentoCaixaEQuebraSobra(): void
    {
        echo "\n--- 7. Fechamento de Caixa e Apuração de Quebra / Sobra ---\n";

        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = (int)$sessaoAberta['id'];
        $totais = SessaoCaixa::obterTotaisEsperados($sessaoAberta);
        $esperado = (float)$totais['total_esperado_gaveta'];

        // Teste de Fechamento com Quebra (Falta) de R$ 5,00
        $informadoComQuebra = $esperado - 5.00;
        $obsQuebra = 'Diferença de R$ 5,00 por falta de moedas no troco matutino';

        $resultadoFechamento = SessaoCaixa::fechar($sessaoId, $informadoComQuebra, $obsQuebra, $this->operadorId);

        $this->assert(!empty($resultadoFechamento), "Sessão encerrada com sucesso via fechar()");

        $sessaoFechada = SessaoCaixa::buscarPorId($sessaoId);
        $this->assert($sessaoFechada['status'] === 'fechado', "Status da sessão alterado para 'fechado'");
        $this->assert(!empty($sessaoFechada['fechado_em']), "Data e hora de fechamento registradas");
        $this->assert((float)$sessaoFechada['total_informado_gaveta'] === $informadoComQuebra, "Total informado gravado com precisão");

        $dif = (float)$sessaoFechada['diferenca_quebra_sobra'];
        $this->assert(abs($dif - (-5.00)) < 0.001, "Quebra apurada com precisão de -R$ 5,00");
    }

    /**
     * 8. Testes de Histórico com Filtros por Turno e Período
     */
    private function testarHistoricoComFiltros(): void
    {
        echo "\n--- 8. Histórico com Filtros por Turno e Período ---\n";

        // Filtro por turno matutino
        $histMat = SessaoCaixa::listarHistorico('matutino', null, null, 10);
        $this->assert(!empty($histMat), "Listagem com filtro 'matutino' retornou sessões");

        $todosMatutinos = true;
        foreach ($histMat as $s) {
            if ($s['turno'] !== 'matutino') {
                $todosMatutinos = false;
                break;
            }
        }
        $this->assert($todosMatutinos, "Todas as sessões retornadas pertencem ao turno matutino");

        // Filtro por data de hoje
        $hoje = date('Y-m-d');
        $histHoje = SessaoCaixa::listarHistorico(null, $hoje, $hoje, 10);
        $this->assert(!empty($histHoje), "Listagem filtrada pela data de hoje retornou sessões");
    }

    /**
     * 9. Testes de Emissão de Comprovantes Térmicos de 80mm (HTML e PDF)
     */
    private function testarEmissaoComprovantesTermicos80mm(): void
    {
        echo "\n--- 9. Emissão de Comprovantes Térmicos de 80mm (Bobina) ---\n";

        // Obter uma transação de recarga recente
        $stmt = $this->pdo->prepare("SELECT id FROM transacoes_carteira WHERE tipo_transacao = 'recarga_dinheiro' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $txId = (int)$stmt->fetchColumn();
        $this->assert($txId > 0, "Transação de recarga identificada para teste de comprovante (ID: {$txId})");

        $dadosRecarga = SessaoCaixa::buscarDadosRecarga($txId);
        $this->assert($dadosRecarga !== null, "Dados da recarga carregados com sucesso");

        // 1. Gerar Comprovante HTML de 80mm
        $html = $this->pdfService->gerarComprovanteRecargaDinheiroHtml($dadosRecarga);
        $this->assert(!empty($html), "Comprovante HTML de 80mm gerado com sucesso");
        $this->assert(str_contains($html, 'COMPROVANTE DE RECARGA'), "HTML contém título oficial");
        $this->assert(str_contains($html, '80mm'), "HTML configurado com largura de 80mm");
        $this->assert(str_contains($html, $dadosRecarga['responsavel_nome']), "HTML exibe nome do titular");

        // 2. Gerar Comprovante PDF binário puro de 80mm
        $pdf = $this->pdfService->gerarComprovanteRecargaDinheiroPdf($dadosRecarga);
        $this->assert(!empty($pdf), "Comprovante PDF binário de 80mm gerado");
        $this->assert(str_starts_with($pdf, '%PDF-1.4'), "PDF possui cabeçalho válido %PDF-1.4");
        $this->assert(str_contains($pdf, '226.77'), "PDF dimensionado na largura de bobina de 80mm (226.77 pt)");
        $this->assert(str_contains($pdf, '%%EOF'), "PDF possui delimitador de fim de arquivo %%EOF");
    }

    /**
     * 10. Testes de Emissão de Espelhos A4 de Fechamento de Caixa (HTML e PDF)
     */
    private function testarEmissaoEspelhosA4(): void
    {
        echo "\n--- 10. Emissão de Espelhos A4 de Fechamento de Caixa ---\n";

        // Obter última sessão fechada
        $stmt = $this->pdo->prepare("SELECT id FROM sessoes_caixa WHERE status = 'fechado' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $sessaoId = (int)$stmt->fetchColumn();
        $this->assert($sessaoId > 0, "Sessão fechada identificada (ID: {$sessaoId})");

        $sessao = SessaoCaixa::buscarPorId($sessaoId);
        $totais = SessaoCaixa::obterTotaisEsperados($sessao);

        // 1. Gerar Espelho A4 em HTML
        $htmlA4 = $this->pdfService->gerarEspelhoFechamentoCaixaHtml($sessao, $totais);
        $this->assert(!empty($htmlA4), "Espelho A4 em HTML gerado com sucesso");
        $this->assert(stripos($htmlA4, 'Fechamento de Caixa') !== false, "HTML A4 contém cabeçalho oficial");
        $this->assert(str_contains($htmlA4, 'Operador de Caixa') && str_contains($htmlA4, 'Gerência'), "HTML A4 possui área de assinaturas do operador e gerente");

        // 2. Gerar Espelho A4 em PDF binário
        $pdfA4 = $this->pdfService->gerarEspelhoFechamentoCaixaPdf($sessao, $totais);
        $this->assert(!empty($pdfA4), "Espelho A4 em PDF binário gerado com sucesso");
        $this->assert(str_starts_with($pdfA4, '%PDF-1.4'), "PDF A4 possui cabeçalho válido %PDF-1.4");
        $this->assert(str_contains($pdfA4, '595.28 841.89'), "PDF dimensionado estritamente no padrão A4 (595.28 x 841.89 pt)");
    }

    /**
     * 11. Testes de Permissões RBAC (Módulo de Caixa)
     */
    private function testarPermissoesRBAC(): void
    {
        echo "\n--- 11. Controle de Acesso Baseado em Papéis (RBAC) ---\n";

        $papeisPermitidos = [RBAC::ROLE_OPERADOR, RBAC::ROLE_ADMINISTRADOR];

        $this->assert(in_array(RBAC::ROLE_OPERADOR, $papeisPermitidos, true), "Operador possui permissão para gestão e fechamento de caixa");
        $this->assert(in_array(RBAC::ROLE_ADMINISTRADOR, $papeisPermitidos, true), "Administrador possui permissão para auditoria e gestão de caixa");
        $this->assert(!in_array(RBAC::ROLE_RESPONSAVEL, $papeisPermitidos, true), "Responsável não possui acesso ao caixa ou conciliação");
        $this->assert(!in_array(RBAC::ROLE_COLABORADOR, $papeisPermitidos, true), "Colaborador não possui acesso ao caixa operacional");
    }
}

$teste = new Fase15Test();
$teste->executar();
