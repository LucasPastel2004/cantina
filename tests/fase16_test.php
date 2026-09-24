<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 16: Tratamento de Contestações Bancárias e Mecanismo Pix MED
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e docs/PLANO.md
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
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\TransacaoCarteira;
use App\Models\Pedido;
use App\Services\PixService;
use App\Services\WhatsAppService;
use App\Services\PdfService;

class Fase16Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;
    private PixService $pixService;
    private WhatsAppService $whatsAppService;
    private PdfService $pdfService;

    private int $adminId = 0;
    private int $responsavelId = 0;
    private int $alunoId = 0;
    private int $turmaId = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
        $this->pixService = new PixService();
        $this->whatsAppService = new WhatsAppService();
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
        echo " INICIANDO SUÍTE DE TESTES: FASE 16 — CONTESTAÇÕES E PIX MED\n";
        echo "=======================================================\n\n";

        try {
            $this->prepararCenario();

            $this->testarPixServiceDeteccaoEParseDevolucao();
            $this->testarEstornoMedComSaldoSuficiente();
            $this->testarEstornoMedComSaldoNegativoEBloqueio();
            $this->testarIdempotenciaEstornoMed();
            $this->testarBloqueioDeAgendamentoParaContaBloqueadaMed();
            $this->testarNotificacoesWhatsAppMed();
            $this->testarQuitacaoMedParcialETotalComDesbloqueio();
            $this->testarConsultasAdministrativasEHistoricoMed();
            $this->testarGeracaoRelatorioPdfMed();

            $this->limparCenario();

            echo "\n-------------------------------------------------------\n";
            echo " RESULTADO DOS TESTES DA FASE 16:\n";
            echo " Asserções Totais : {$this->totalAssercoes}\n";
            echo " Asserções com Sucesso: {$this->assercoesSucesso}\n";
            $falhas = $this->totalAssercoes - $this->assercoesSucesso;
            echo " Falhas Encontradas   : {$falhas}\n";
            echo "-------------------------------------------------------\n\n";

            if ($falhas > 0) {
                exit(1);
            }
        } catch (\Throwable $e) {
            echo "\n[ERRO CRÍTICO NA EXECUÇÃO DOS TESTES]: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
            $this->limparCenario();
            exit(1);
        }
    }

    private function prepararCenario(): void
    {
        echo "1. Preparando cenário de testes...\n";

        // Cria Administrador de teste
        $loginAdmin = 'admin_med_' . uniqid();
        $stmtAdmin = $this->pdo->prepare("
            INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo, created_at)
            VALUES (:login, 'Admin Teste MED', :email, :senha, 'administrador', 1, NOW())
        ");
        $stmtAdmin->execute([
            'login' => $loginAdmin,
            'email' => $loginAdmin . '@colegio.local',
            'senha' => password_hash('123456', PASSWORD_BCRYPT)
        ]);
        $this->adminId = (int)$this->pdo->lastInsertId();

        // Cria Turma de teste
        $this->turmaId = Turma::criar([
            'nome_turma' => '9º Ano A - MED',
            'serie'      => '9º EF',
            'turno'      => 'matutino'
        ]);

        // Cria Responsável de teste com CPF e WhatsApp únicos
        $cpfResp = '999' . str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $stmtResp = $this->pdo->prepare("
            INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, bloqueado_med, created_at)
            VALUES (:cpf, 'Responsável Familiar Teste MED', 'med_teste@colegio.com.br', '11988887777', 'hash_teste', 100.00, 0, NOW())
        ");
        $stmtResp->execute(['cpf' => $cpfResp]);
        $this->responsavelId = (int)$this->pdo->lastInsertId();

        // Cria Aluno dependente
        $matr = 'MED' . mt_rand(1000, 9999);
        $stmtAluno = $this->pdo->prepare("
            INSERT INTO alunos (responsavel_id, turma_id, nome, matricula, turno, ativo, created_at)
            VALUES (:resp_id, :turma_id, 'Aluno Dependente MED', :matr, 'matutino', 1, NOW())
        ");
        $stmtAluno->execute([
            'resp_id' => $this->responsavelId,
            'turma_id' => $this->turmaId,
            'matr' => $matr
        ]);
        $this->alunoId = (int)$this->pdo->lastInsertId();

        $this->assert($this->adminId > 0 && $this->responsavelId > 0 && $this->alunoId > 0, 'Cenário de teste (Admin, Responsável, Aluno, Turma) preparado com sucesso.');
    }

    private function testarPixServiceDeteccaoEParseDevolucao(): void
    {
        echo "\n2. Testando PixService — Detecção e Extração de Webhooks de Devolução MED...\n";

        // 1. Webhook de Pix Comum (sem devoluções)
        $payloadPixNormal = json_encode([
            'pix' => [
                [
                    'endToEndId' => 'E00000000202609240001',
                    'txid' => 'TXIDNORMAL123456789',
                    'valor' => '50.00',
                    'horario' => '2026-09-24T10:00:00Z'
                ]
            ]
        ]);
        $this->assert(!$this->pixService->isPayloadDevolucao($payloadPixNormal), 'PixService identifica corretamente que webhook de recarga comum NÃO é devolução.');

        // 2. Webhook BACEN / Efí com array devolucoes
        $rtrId = 'D00000000202609240001ABC';
        $payloadDevolucaoBacen = json_encode([
            'pix' => [
                [
                    'endToEndId' => 'E00000000202609240001',
                    'txid' => 'TXIDMED123456789',
                    'valor' => '100.00',
                    'devolucoes' => [
                        [
                            'id' => $rtrId,
                            'rtrId' => $rtrId,
                            'valor' => '30.00',
                            'motivo' => 'Mecanismo Especial de Devolucao - Fraude Detectada',
                            'status' => 'DEVOLVIDO',
                            'horario' => [
                                'solicitacao' => '2026-09-24T10:30:00Z'
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $this->assert($this->pixService->isPayloadDevolucao($payloadDevolucaoBacen), 'PixService identifica payload BACEN com array `devolucoes` como devolução MED.');

        $dadosParsed = $this->pixService->parseWebhookDevolucaoPayload($payloadDevolucaoBacen);
        $this->assert(!empty($dadosParsed), 'PixService realiza parse de devolução BACEN com sucesso.');
        $this->assert(($dadosParsed['rtrId'] ?? '') === $rtrId, 'rtrId extraído com precisão: ' . ($dadosParsed['rtrId'] ?? ''));
        $this->assert((float)($dadosParsed['valor'] ?? 0) === 30.00, 'Valor da devolução extraído com precisão (R$ 30,00).');
        $this->assert(str_contains($dadosParsed['motivo'] ?? '', 'Mecanismo Especial'), 'Motivo da devolução identificado corretamente.');

        // 3. Webhook REST genérico de devolução
        $payloadDevolucaoRest = json_encode([
            'evento' => 'pix.devolucao.concluida',
            'rtrId' => 'D999999999999999',
            'endToEndId' => 'E999999999999999',
            'valor' => 45.50,
            'motivo' => 'Contestação Bancária Solicitada pelo Titular'
        ]);
        $this->assert($this->pixService->isPayloadDevolucao($payloadDevolucaoRest), 'PixService identifica payload formato REST genérico como devolução.');
        $parsedRest = $this->pixService->parseWebhookDevolucaoPayload($payloadDevolucaoRest);
        $this->assert((float)($parsedRest['valor'] ?? 0) === 45.50, 'Valor de devolução formato REST extraído corretamente.');
    }

    private function testarEstornoMedComSaldoSuficiente(): void
    {
        echo "\n3. Testando Estorno MED com saldo suficiente (sem negativar e sem bloquear)...\n";

        // Saldo inicial é R$ 100,00
        $rtrId = 'RTR_TESTE_SUFICIENTE_' . uniqid();
        $resultado = TransacaoCarteira::processarEstornoMed(
            $this->responsavelId,
            40.00,
            $rtrId,
            'Contestação de teste amigável - saldo suficiente'
        );

        $this->assert($resultado['sucesso'] === true, 'Estorno MED processado com sucesso na carteira.');
        $this->assert((float)$resultado['saldo_anterior'] === 100.00, 'Saldo anterior correto (R$ 100,00).');
        $this->assert((float)$resultado['saldo_resultante'] === 60.00, 'Saldo resultante correto (R$ 60,00).');
        $this->assert($resultado['bloqueado_med'] === false, 'Conta NÃO foi bloqueada pois o saldo resultante permaneceu positivo (>= 0).');

        // Confere no banco
        $stmtResp = $this->pdo->prepare("SELECT saldo_atual, bloqueado_med FROM responsaveis WHERE id = :id");
        $stmtResp->execute(['id' => $this->responsavelId]);
        $dbResp = $stmtResp->fetch(\PDO::FETCH_ASSOC);

        $this->assert((float)$dbResp['saldo_atual'] === 60.00, 'Saldo atual persistido no banco com sucesso (R$ 60,00).');
        $this->assert((int)$dbResp['bloqueado_med'] === 0, 'Campo bloqueado_med permanece 0.');
    }

    private function testarEstornoMedComSaldoNegativoEBloqueio(): void
    {
        echo "\n4. Testando Estorno MED que deixa saldo negativo e aplica bloqueio compulsório...\n";

        // Saldo atual é R$ 60,00. Faremos um estorno de R$ 90,00 -> saldo resultante deve ser -R$ 30,00
        $rtrId = 'RTR_TESTE_NEGATIVO_' . uniqid();
        $resultado = TransacaoCarteira::processarEstornoMed(
            $this->responsavelId,
            90.00,
            $rtrId,
            'Contestação MED BACEN - Fraude alegada pelo pagador'
        );

        $this->assert($resultado['sucesso'] === true, 'Estorno MED de valor maior que o saldo processado com sucesso.');
        $this->assert((float)$resultado['saldo_anterior'] === 60.00, 'Saldo anterior registrado corretamente (R$ 60,00).');
        $this->assert((float)$resultado['saldo_resultante'] === -30.00, 'Saldo resultante ficou negativo (-R$ 30,00) conforme regra do MED.');
        $this->assert($resultado['bloqueado_med'] === true, 'Flag bloqueado_med ativada para true devido ao saldo negativo.');

        // Confere persistência no banco
        $stmtResp = $this->pdo->prepare("SELECT saldo_atual, bloqueado_med FROM responsaveis WHERE id = :id");
        $stmtResp->execute(['id' => $this->responsavelId]);
        $dbResp = $stmtResp->fetch(\PDO::FETCH_ASSOC);

        $this->assert((float)$dbResp['saldo_atual'] === -30.00, 'Saldo negativo persistido no MySQL: R$ ' . $dbResp['saldo_atual']);
        $this->assert((int)$dbResp['bloqueado_med'] === 1, 'Conta marcada como bloqueado_med = 1 no banco.');

        // Confere registro na tabela transacoes_carteira
        $stmtTrans = $this->pdo->prepare("
            SELECT tipo_transacao, valor, saldo_resultante, descricao, referencia_id 
            FROM transacoes_carteira 
            WHERE responsavel_id = :resp_id AND tipo_transacao = 'debito_contestacao_med'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtTrans->execute(['resp_id' => $this->responsavelId]);
        $t = $stmtTrans->fetch(\PDO::FETCH_ASSOC);

        $this->assert(!empty($t), 'Registro de transacao do tipo debito_contestacao_med criado.');
        $this->assert((float)$t['valor'] === 90.00, 'Valor registrado na transação: R$ 90,00.');
        $this->assert((float)$t['saldo_resultante'] === -30.00, 'Saldo resultante registrado na transação: -R$ 30,00.');
        $this->assert($t['referencia_id'] === $rtrId, 'Referência do estorno (rtrId) gravada na transação.');

        // Confere auditoria em logs_seguranca
        $stmtSeg = $this->pdo->prepare("
            SELECT evento, detalhes_json FROM logs_seguranca 
            WHERE evento = 'webhook_med_recebido'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtSeg->execute();
        $seg = $stmtSeg->fetch(\PDO::FETCH_ASSOC);
        $this->assert(!empty($seg), 'Trilha de auditoria [webhook_med_recebido] gravada com sucesso em logs_seguranca.');
    }

    private function testarIdempotenciaEstornoMed(): void
    {
        echo "\n5. Testando Idempotência Estrita de Devoluções MED (mesmo rtrId)...\n";

        // Saldo atual está em -R$ 30,00
        $rtrId = 'RTR_IDEMPOTENTE_001';

        // 1ª Execução
        $res1 = TransacaoCarteira::processarEstornoMed($this->responsavelId, 15.00, $rtrId, 'Teste idempotência primeira vez');
        $this->assert($res1['sucesso'] === true && $res1['idempotente'] === false, 'Primeira requisição com rtrId processa o débito normalmente.');
        $this->assert((float)$res1['saldo_resultante'] === -45.00, 'Saldo resultante após 1º débito: -R$ 45,00.');

        // 2ª Execução com os mesmos parâmetros
        $res2 = TransacaoCarteira::processarEstornoMed($this->responsavelId, 15.00, $rtrId, 'Teste idempotência segunda vez');
        $this->assert($res2['sucesso'] === true, 'Segunda requisição retorna sucesso.');
        $this->assert($res2['idempotente'] === true, 'Segunda requisição detecta que já foi processada anteriormente (idempotente = true).');
        $this->assert((float)$res2['saldo_resultante'] === -45.00, 'Saldo resultante NÃO foi debitado novamente, mantendo -R$ 45,00.');

        // Saldo no banco deve continuar -45.00
        $stmtResp = $this->pdo->prepare("SELECT saldo_atual FROM responsaveis WHERE id = :id");
        $stmtResp->execute(['id' => $this->responsavelId]);
        $saldoDb = (float)$stmtResp->fetchColumn();
        $this->assert($saldoDb === -45.00, 'Saldo no banco inalterado após reenvio do mesmo webhook: -R$ ' . $saldoDb);
    }

    private function testarBloqueioDeAgendamentoParaContaBloqueadaMed(): void
    {
        echo "\n6. Testando Bloqueio de Agendamento para Família Bloqueada MED...\n";

        $bloqueioDisparado = false;
        try {
            // Tenta debitar a carteira normalmente como se fosse um pedido
            TransacaoCarteira::debitar(
                $this->responsavelId,
                10.00,
                TransacaoCarteira::TIPO_DEBITO_PEDIDO,
                'Tentativa de agendamento indevida com conta bloqueada'
            );
        } catch (\DomainException $e) {
            $bloqueioDisparado = true;
            $this->assert(str_contains($e->getMessage(), 'suspensa'), 'Exceção lançada ao tentar debitar conta bloqueada MED: ' . $e->getMessage());
        }

        $this->assert($bloqueioDisparado, 'TransacaoCarteira::debitar rejeitou com sucesso a operação para responsável com bloqueado_med = 1.');
    }

    private function testarNotificacoesWhatsAppMed(): void
    {
        echo "\n7. Testando Serviços de Notificação WhatsApp para Notificação MED e Desbloqueio...\n";

        // Notificação de Estorno MED
        $resNotifMed = $this->whatsAppService->sendNotificacaoMed(
            '11988887777',
            105.00,
            -45.00,
            'Responsável Familiar Teste MED'
        );
        $this->assert($resNotifMed === true, 'WhatsAppService::sendNotificacaoMed executado com sucesso e logado.');

        // Notificação de Desbloqueio
        $resNotifDesbloqueio = $this->whatsAppService->sendNotificacaoDesbloqueioMed(
            '11988887777',
            5.00,
            'Responsável Familiar Teste MED'
        );
        $this->assert($resNotifDesbloqueio === true, 'WhatsAppService::sendNotificacaoDesbloqueioMed executado com sucesso e logado.');
    }

    private function testarQuitacaoMedParcialETotalComDesbloqueio(): void
    {
        echo "\n8. Testando Quitação de Débito MED (Parcial e Total com Desbloqueio)...\n";

        // Saldo atual está em -R$ 45,00 e bloqueado_med = 1
        // 1. Quitação Parcial de R$ 20,00 -> saldo resultante deve ser -R$ 25,00 (ainda negativo, não desbloqueia)
        $resParcial = TransacaoCarteira::processarQuitacaoMed(
            $this->responsavelId,
            20.00,
            'Quitação parcial presencial na tesouraria',
            $this->adminId,
            true // desbloquear se positivo
        );

        $this->assert($resParcial['sucesso'] === true, 'Quitação parcial processada com sucesso.');
        $this->assert((float)$resParcial['saldo_resultante'] === -25.00, 'Saldo resultante após quitação parcial: -R$ 25,00.');
        $this->assert($resParcial['desbloqueado'] === false, 'Conta permanece bloqueada pois o saldo ainda é negativo.');

        // Confere no banco
        $stmtResp = $this->pdo->prepare("SELECT saldo_atual, bloqueado_med FROM responsaveis WHERE id = :id");
        $stmtResp->execute(['id' => $this->responsavelId]);
        $resp = $stmtResp->fetch(\PDO::FETCH_ASSOC);
        $this->assert((float)$resp['saldo_atual'] === -25.00 && (int)$resp['bloqueado_med'] === 1, 'No banco: saldo -R$ 25,00 e bloqueado_med = 1.');

        // 2. Quitação Total / Excedente de R$ 35,00 -> saldo resultante deve ser +R$ 10,00 (positivo, deve desbloquear)
        $resTotal = TransacaoCarteira::processarQuitacaoMed(
            $this->responsavelId,
            35.00,
            'Quitação final e regularização completa',
            $this->adminId,
            true // desbloquear se positivo
        );

        $this->assert($resTotal['sucesso'] === true, 'Quitação final processada com sucesso.');
        $this->assert((float)$resTotal['saldo_resultante'] === 10.00, 'Saldo resultante após quitação final: +R$ 10,00.');
        $this->assert($resTotal['desbloqueado'] === true, 'Conta familiar foi DESBLOQUEADA com sucesso após regularização do saldo.');

        // Confere no banco
        $stmtResp->execute(['id' => $this->responsavelId]);
        $respTotal = $stmtResp->fetch(\PDO::FETCH_ASSOC);
        $this->assert((float)$respTotal['saldo_atual'] === 10.00, 'Saldo no banco agora é positivo: R$ ' . $respTotal['saldo_atual']);
        $this->assert((int)$respTotal['bloqueado_med'] === 0, 'bloqueado_med no banco retornou para 0.');

        // 3. Teste de bloqueio/desbloqueio manual direto via Responsavel
        Responsavel::bloquearMed($this->responsavelId);
        $this->assert(Responsavel::isBloqueadoMed($this->responsavelId), 'Responsavel::bloquearMed efetuou o bloqueio manual corretamente.');
        Responsavel::desbloquearMed($this->responsavelId);
        $this->assert(!Responsavel::isBloqueadoMed($this->responsavelId), 'Responsavel::desbloquearMed desbloqueou a conta familiar com sucesso.');
    }

    private function testarConsultasAdministrativasEHistoricoMed(): void
    {
        echo "\n9. Testando Métricas, Filtros e Trilha de Histórico MED...\n";

        // Limpa preventivamente e cria temporariamente um responsável com saldo negativo para validar métricas
        $this->pdo->exec("DELETE FROM responsaveis WHERE email = 'devedor@med.com'");
        $cpfDevedor = '888' . str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $stmtResp2 = $this->pdo->prepare("
            INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, bloqueado_med, created_at)
            VALUES (:cpf, 'Responsável Devedor Auditoria', 'devedor@med.com', '11977776666', 'hash_teste', -50.00, 1, NOW())
        ");
        $stmtResp2->execute(['cpf' => $cpfDevedor]);
        $respDevedorId = (int)$this->pdo->lastInsertId();

        $totalNegativados = Responsavel::contarNegativadosMed();
        $this->assert($totalNegativados >= 1, 'Responsavel::contarNegativadosMed retornou contagem >= 1: ' . $totalNegativados);

        $saldoTotalDevedor = Responsavel::somarSaldoDevedorMed();
        $this->assert($saldoTotalDevedor >= 50.00, 'Responsavel::somarSaldoDevedorMed somou o passivo devedor corretamente (>= R$ 50,00): R$ ' . $saldoTotalDevedor);

        // Listagem de negativados
        $lista = Responsavel::listarNegativadosMed(['busca' => 'Responsável Devedor', 'status' => 'todos'], 1, 10);
        $this->assert(!empty($lista['registros'] ?? $lista), 'Responsavel::listarNegativadosMed filtrou e retornou o registro buscado.');
        $registros = $lista['registros'] ?? $lista;
        $this->assert(($registros[0]['id'] ?? 0) === $respDevedorId, 'ID retornado corresponde ao devedor de teste.');

        // Histórico de auditoria MED
        $historico = TransacaoCarteira::listarHistoricoMed(10);
        $this->assert(!empty($historico), 'TransacaoCarteira::listarHistoricoMed retornou transações de estorno/quitação.');
        $this->assert(isset($historico[0]['tipo']) && isset($historico[0]['saldo_resultante']), 'Campos da trilha de auditoria formatados corretamente.');

        // Limpa o responsável temporário devedor
        $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$respDevedorId}");
    }

    private function testarGeracaoRelatorioPdfMed(): void
    {
        echo "\n10. Testando Geração de Relatório Oficial A4 em PDF Timbrado...\n";

        $dadosRelatorio = [
            'total_bloqueados'     => 1,
            'saldo_devedor_total'  => 45.00,
            'total_estornos'       => 2,
            'contas_regularizadas' => 1,
            'filtro_busca'         => '',
            'gerado_por'           => 'Admin Teste MED',
            'responsaveis'         => [
                [
                    'nome' => 'Responsável Familiar Teste MED',
                    'email' => 'med_teste@colegio.com.br',
                    'telefone' => '11988887777',
                    'saldo' => -45.00,
                    'bloqueado_med' => 1,
                    'data_estorno' => date('Y-m-d H:i:s'),
                    'dependentes' => [
                        [
                            'nome' => 'Aluno Dependente MED',
                            'turma_nome' => '9º Ano A - MED'
                        ]
                    ]
                ]
            ]
        ];

        $pdfBinario = $this->pdfService->gerarRelatorioNegativadosMedPdf($dadosRelatorio);

        $this->assert(!empty($pdfBinario), 'PDF gerado não está vazio.');
        $this->assert(str_starts_with($pdfBinario, '%PDF-1.4'), 'Arquivo gerado possui cabeçalho binário padrão PDF (%PDF-1.4).');
        $this->assert(str_contains($pdfBinario, '595.28 841.89'), 'PDF dimensionado no padrão A4 oficial (595.28 x 841.89 pt).');
        $this->assert(str_contains($pdfBinario, '/Type /Catalog'), 'PDF estruturado com dicionário /Catalog.');
        $this->assert(str_contains($pdfBinario, '/Type /Page'), 'PDF estruturado com elementos de página /Page.');
        $this->assert(str_contains($pdfBinario, '%%EOF'), 'PDF finalizado com token %%EOF.');
        $this->assert(strlen($pdfBinario) > 1000, 'Tamanho do PDF condizente com documento completo com brasão e tabela (' . strlen($pdfBinario) . ' bytes).');
    }

    private function limparCenario(): void
    {
        echo "\n11. Limpando dados de teste...\n";

        if ($this->alunoId > 0) {
            $this->pdo->exec("DELETE FROM alunos WHERE id = {$this->alunoId}");
        }
        if ($this->turmaId > 0) {
            $this->pdo->exec("DELETE FROM turmas WHERE id = {$this->turmaId}");
        }
        if ($this->responsavelId > 0) {
            $this->pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id = {$this->responsavelId}");
            $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$this->responsavelId}");
        }
        if ($this->adminId > 0) {
            $this->pdo->exec("DELETE FROM usuarios WHERE id = {$this->adminId}");
        }

        echo "Cenário limpo com sucesso.\n";
    }
}

// Execução da suíte
$teste = new Fase16Test();
$teste->executar();
