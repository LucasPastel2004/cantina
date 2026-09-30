<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Tela e Controle de Caixa do Turno (/operacional/caixa)
 * 
 * Valida:
 * 1. Estrutura e métodos do CaixaController;
 * 2. Permissões RBAC para gestão de caixa;
 * 3. Abertura de sessão com Fundo de Troco e bloqueio de duplicidade;
 * 4. Apuração de totais esperados (Fundo + Dinheiro Avulso + Recargas);
 * 5. Fechamento com contagem física, cálculo de Quebra/Sobra e trilha em logs_auditoria;
 * 6. Histórico de sessões de caixa;
 * 7. Geração de espelho HTML e PDF A4 timbrado para assinatura;
 * 8. Resposta HTTP direta via Front Controller.
 */

namespace Tests;

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\RBAC;
use App\Core\Auth;
use App\Core\Session;
use App\Models\SessaoCaixa;
use App\Models\Auditoria;
use App\Controllers\CaixaController;
use App\Services\PdfService;

class CaixaTest
{
    private \PDO $pdo;
    private int $total = 0;
    private int $aprovados = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO SUÍTE DE TESTES: GESTÃO DE CAIXA DE TURNO (/operacional/caixa)\n";
        echo "======================================================================\n\n";

        $this->testarEstruturaController();
        $this->testarPermissoesRbac();
        $this->testarCicloAberturaEFechamento();
        $this->testarCalculosQuebraESobra();
        $this->testarEspelhoHtmlEPdf();
        $this->testarIntegridadeViewCaixa();
        $this->testarRenderizacaoControllerIndex();

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->aprovados}/{$this->total} asserções aprovadas.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->aprovados !== $this->total) {
            exit(1);
        }
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->aprovados++;
            echo " [PASS] {$msg}\n";
        } else {
            echo " [FAIL] {$msg}\n";
        }
    }

    private function testarEstruturaController(): void
    {
        echo "--- [1] Estrutura e Métodos do CaixaController ---\n";
        $controller = new CaixaController();

        $this->assert(method_exists($controller, 'index'), "CaixaController possui action 'index'");
        $this->assert(method_exists($controller, 'abrir'), "CaixaController possui action 'abrir'");
        $this->assert(method_exists($controller, 'fechar'), "CaixaController possui action 'fechar'");
        $this->assert(method_exists($controller, 'espelhoHtml'), "CaixaController possui action 'espelhoHtml'");
        $this->assert(method_exists($controller, 'espelhoPdf'), "CaixaController possui action 'espelhoPdf'");
    }

    private function testarPermissoesRbac(): void
    {
        echo "\n--- [2] Permissões RBAC para Caixa do Turno ---\n";

        Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'operador-caixa',
            'nome'    => 'Operador Teste',
            'email'   => 'operador@escola.com',
            'perfil'  => RBAC::ROLE_OPERADOR
        ]);
        $this->assert(RBAC::can('abertura_fechamento_caixa') === true, "Perfil 'operador' tem permissão 'abertura_fechamento_caixa'");

        Auth::loginUsuario([
            'id'      => 2,
            'usuario' => 'admin-geral',
            'nome'    => 'Admin Teste',
            'email'   => 'admin@escola.com',
            'perfil'  => RBAC::ROLE_ADMINISTRADOR
        ]);
        $this->assert(RBAC::can('abertura_fechamento_caixa') === true, "Perfil 'administrador' tem permissão 'abertura_fechamento_caixa'");

        Auth::loginResponsavel([
            'id'            => 10,
            'cpf'           => '12345678901',
            'nome'          => 'Pai Bloqueado',
            'email'         => 'pai@teste.com',
            'whatsapp'      => '11999999999',
            'saldo_atual'   => 50.00,
            'bloqueado_med' => 0
        ]);
        $this->assert(RBAC::can('abertura_fechamento_caixa') === false, "Perfil 'responsavel' é terminantemente bloqueado");
        Auth::logout();
    }

    private function testarCicloAberturaEFechamento(): void
    {
        echo "\n--- [3] Ciclo de Vida: Abertura e Fechamento com Auditoria ---\n";

        $operadorId = $this->obterOperadorId();
        $hoje = date('Y-m-d');

        // Garante que não haja sessão aberta no turno vespertino para testar
        $this->pdo->exec("UPDATE sessoes_caixa SET status = 'fechado', fechado_em = NOW() WHERE turno = 'vespertino' AND status = 'aberto'");

        // Abertura de sessão
        $sessaoId = SessaoCaixa::abrir($operadorId, 'vespertino', 60.00, 'Abertura para teste de fechamento');
        $this->assert($sessaoId > 0, "Sessão de caixa aberta com ID #{$sessaoId}");

        $sessao = SessaoCaixa::buscarPorId($sessaoId);
        $this->assert($sessao['status'] === 'aberto', "Status da sessão é 'aberto'");
        $this->assert((float)$sessao['fundo_troco_inicial'] === 60.00, "Fundo de troco inicial gravado como R$ 60,00");

        // Simula vendas: R$ 40 em dinheiro avulso e R$ 25 em Pix
        SessaoCaixa::incrementarTotais($sessaoId, 40.00, 25.00, 0.00);

        // Totais esperados: 60 (fundo) + 40 (avulso) = R$ 100,00 na gaveta
        $sessaoAtualizada = SessaoCaixa::buscarPorId($sessaoId);
        $totais = SessaoCaixa::obterTotaisEsperados($sessaoAtualizada);
        $this->assert($totais['total_esperado_gaveta'] === 100.00, "Total esperado na gaveta calculado com exatidão como R$ 100,00");
        $this->assert($totais['total_pix_balcao'] === 25.00, "Total em Pix apurado como R$ 25,00");

        // Fechamento exato: operador contou exatamente R$ 100,00
        $sessaoFechada = SessaoCaixa::fechar($sessaoId, 100.00, 'Fechamento sem divergência', $operadorId, '127.0.0.1');
        $this->assert($sessaoFechada['status'] === 'fechado', "Status atualizado para 'fechado'");
        $this->assert(!empty($sessaoFechada['fechado_em']), "Data e hora de fechamento registradas");
        $this->assert((float)$sessaoFechada['total_informado_gaveta'] === 100.00, "Total informado gravado como R$ 100,00");
        $this->assert((float)$sessaoFechada['diferenca_quebra_sobra'] === 0.00, "Diferença calculada como R$ 0,00 (Exato)");

        // Verifica gravação em logs_auditoria
        $stmtAud = $this->pdo->prepare("SELECT * FROM logs_auditoria WHERE tabela_afetada = 'sessoes_caixa' AND registro_id = :id AND acao = 'fechamento_caixa'");
        $stmtAud->execute(['id' => $sessaoId]);
        $aud = $stmtAud->fetch(\PDO::FETCH_ASSOC);
        $this->assert($aud !== false, "Fechamento de caixa registrado na trilha de auditoria (logs_auditoria)");
    }

    private function testarCalculosQuebraESobra(): void
    {
        echo "\n--- [4] Apuração de Sobra e Quebra/Falta de Caixa ---\n";

        $operadorId = $this->obterOperadorId();

        // 1. Teste de Sobra: Fundo 50 + Dinheiro 20 = Esperado 70. Contado 75 -> Sobra +5.00
        $sessaoSobraId = SessaoCaixa::abrir($operadorId, 'vespertino', 50.00, 'Teste sobra');
        SessaoCaixa::incrementarTotais($sessaoSobraId, 20.00, 0.00, 0.00);
        $fechadaSobra = SessaoCaixa::fechar($sessaoSobraId, 75.00, 'Sobra de troco', $operadorId);
        $this->assert((float)$fechadaSobra['diferenca_quebra_sobra'] === 5.00, "Sobra de caixa calculada com precisão (+ R$ 5,00)");

        // 2. Teste de Quebra: Fundo 50 + Dinheiro 30 = Esperado 80. Contado 76 -> Quebra -4.00
        $sessaoQuebraId = SessaoCaixa::abrir($operadorId, 'vespertino', 50.00, 'Teste quebra');
        SessaoCaixa::incrementarTotais($sessaoQuebraId, 30.00, 0.00, 0.00);
        $fechadaQuebra = SessaoCaixa::fechar($sessaoQuebraId, 76.00, 'Falta de moedas', $operadorId);
        $this->assert((float)$fechadaQuebra['diferenca_quebra_sobra'] === -4.00, "Quebra/Falta de caixa calculada com precisão (- R$ 4,00)");
    }

    private function testarEspelhoHtmlEPdf(): void
    {
        echo "\n--- [5] Emissão de Espelho de Fechamento (HTML e PDF A4) ---\n";

        $stmt = $this->pdo->query("SELECT * FROM sessoes_caixa WHERE status = 'fechado' ORDER BY id DESC LIMIT 1");
        $sessao = $stmt->fetch(\PDO::FETCH_ASSOC);
        $sessao['operador_nome'] = 'Maria Operadora';

        // Teste Espelho HTML
        $html = PdfService::gerarEspelhoFechamentoCaixaHtml($sessao);
        $this->assert(str_contains($html, "Fechamento de Caixa #{$sessao['id']}"), "HTML contém identificador da sessão");
        $this->assert(str_contains($html, "Fundo de Troco Inicial"), "HTML discrimina Fundo de Troco");
        $this->assert(str_contains($html, "size: A4 portrait"), "CSS de impressão configura folha A4");
        $this->assert(str_contains($html, "window.print()"), "HTML possui acionamento nativo de impressão");

        // Teste PDF Binário A4
        $pdf = PdfService::gerarEspelhoFechamentoCaixaPdf($sessao);
        $this->assert(!empty($pdf), "PDF binário gerado com sucesso");
        $this->assert(str_starts_with($pdf, "%PDF-1.4"), "Assinatura do cabeçalho é %PDF-1.4");
        $this->assert(str_contains($pdf, "%%EOF"), "Marcador de encerramento %%EOF presente no PDF");
    }

    private function testarIntegridadeViewCaixa(): void
    {
        echo "\n--- [6] Integridade do Template da View operacional/caixa.php ---\n";

        $viewPath = __DIR__ . '/../app/views/operacional/caixa.php';
        $this->assert(file_exists($viewPath), "Arquivo operacional/caixa.php existe");

        $content = file_get_contents($viewPath);
        $this->assert(str_contains($content, 'Gestão de Caixa e Prestação de Contas'), "View contém título institucional");
        $this->assert(str_contains($content, 'fundo_troco_inicial'), "View contém campo de Fundo de Troco");
        $this->assert(str_contains($content, 'total_informado_gaveta'), "View contém campo de contagem física de gaveta");
        $this->assert(str_contains($content, 'calcularQuebraSobra()'), "View contém script interativo de cálculo de quebra/sobra");
        $this->assert(str_contains($content, 'Histórico de Sessões de Caixa'), "View contém tabela de histórico de sessões");

        // Asserções do Grid Oficial Institucional (Escolar Manager)
        $this->assert(str_contains($content, 'Operador'), "View contém campo oficial 'Operador'");
        $this->assert(str_contains($content, 'Situação'), "View contém campo oficial 'Situação'");
        $this->assert(str_contains($content, 'Fundo de caixa'), "View contém campo oficial 'Fundo de caixa'");
        $this->assert(str_contains($content, 'Data e hora de abertura'), "View contém campo oficial 'Data e hora de abertura'");
        $this->assert(str_contains($content, 'Entrada dinheiro'), "View contém campo oficial 'Entrada dinheiro'");
        $this->assert(str_contains($content, 'Entrada pix'), "View contém campo oficial 'Entrada pix'");
        $this->assert(str_contains($content, 'Total saídas'), "View contém campo oficial 'Total saídas'");
        $this->assert(str_contains($content, 'Total entradas'), "View contém campo oficial 'Total entradas'");
        $this->assert(str_contains($content, 'Total caixa'), "View contém campo oficial 'Total caixa'");
        $this->assert(str_contains($content, 'Emitir'), "View contém botão de ação rápida 'Emitir' relatório");
        $this->assert(!str_contains(strtolower($content), 'entrada cheque'), "View NÃO contém campo 'entrada cheque'");
        $this->assert(!str_contains(strtolower($content), 'entrada cartão'), "View NÃO contém campo 'entrada cartão'");
    }

    private function testarRenderizacaoControllerIndex(): void
    {
        echo "\n--- [7] Renderização Completa via CaixaController::index() ---\n";

        Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'operador-caixa',
            'nome'    => 'Operador Teste',
            'email'   => 'operador@escola.com',
            'perfil'  => RBAC::ROLE_OPERADOR
        ]);

        $_GET['turno'] = 'matutino';
        $controller = new CaixaController();

        ob_start();
        $controller->index();
        $output = ob_get_clean();

        $this->assert(strlen($output) > 1000, "CaixaController::index() renderizou HTML com sucesso (> 1000 bytes)");
        $this->assert(str_contains($output, 'Gestão de Caixa e Prestação de Contas'), "HTML contém título da tela");
        $this->assert(str_contains($output, 'Matutino (Manhã)'), "HTML renderizou seletor de turno matutino");
        $this->assert(str_contains($output, 'Vespertino (Tarde)'), "HTML renderizou seletor de turno vespertino");
        $this->assert(str_contains($output, 'Histórico de Sessões de Caixa'), "HTML renderizou seção de histórico");
        $this->assert(str_contains($output, 'Entrada dinheiro'), "HTML renderizou campo 'Entrada dinheiro'");
        $this->assert(str_contains($output, 'Entrada pix'), "HTML renderizou campo 'Entrada pix'");
        $this->assert(str_contains($output, 'Total caixa'), "HTML renderizou campo 'Total caixa'");

        Auth::logout();
    }

    private function obterOperadorId(): int
    {
        $stmt = $this->pdo->query("SELECT id FROM usuarios WHERE perfil = 'operador' AND ativo = 1 AND deleted_at IS NULL LIMIT 1");
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;

        $stmtAdmin = $this->pdo->query("SELECT id FROM usuarios WHERE perfil = 'administrador' AND ativo = 1 AND deleted_at IS NULL LIMIT 1");
        return (int)$stmtAdmin->fetchColumn();
    }
}

$teste = new CaixaTest();
$teste->run();
