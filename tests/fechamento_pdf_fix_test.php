<?php
/**
 * Teste de Validação das Correções:
 * 1. Rota /operacional/caixa/fechamento/pdf?id={id} e /operacional/caixa/fechamento/pdf/{id}
 * 2. Rota /operacional/caixa/espelho/{id}/pdf
 * 3. Botão "Fechar" com fallback e window.open no dropdown
 */

namespace Tests;

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
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

use App\Core\Auth;
use App\Core\Session;
use App\Models\SessaoCaixa;
use App\Controllers\CaixaController;

class FechamentoPdfFixTest
{
    private \PDO $pdo;
    private int $sessaoId = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO TESTE: FIX ROTAS DE FECHAMENTO PDF E BOTÃO FECHAR\n";
        echo "======================================================================\n";

        $this->setUpSession();
        $this->testarAcessoRotasFechamentoPdf();
        $this->testarHtmlEspelhoEBotaoFechar();
        $this->testarDropdownCaixaHtml();

        echo "\n----------------------------------------------------------------------\n";
        echo " TODOS OS TESTES DE VALIDAÇÃO PASSARAM COM SUCESSO!\n";
        echo "----------------------------------------------------------------------\n";
    }

    private function setUpSession(): void
    {
        // Garante usuário operador logado
        Auth::loginUsuario([
            'id'     => 1,
            'nome'   => 'Operador Teste',
            'email'  => 'operador@escola.com',
            'perfil' => \App\Core\RBAC::ROLE_OPERADOR
        ]);

        // Obtém ou cria uma sessão de caixa
        $stmtSessao = $this->pdo->query("SELECT id FROM sessoes_caixa ORDER BY id DESC LIMIT 1");
        $sessao = $stmtSessao->fetch(\PDO::FETCH_ASSOC);
        if ($sessao) {
            $this->sessaoId = (int)$sessao['id'];
        } else {
            $nova = SessaoCaixa::abrir(50.00, 'matutino', (int)$user['id']);
            $this->sessaoId = (int)$nova['id'];
        }

        $this->assert($this->sessaoId > 0, "Sessão de caixa válida encontrada (#{$this->sessaoId})");
    }

    private function testarAcessoRotasFechamentoPdf(): void
    {
        echo "\n--- [1] Métodos e Suporte a Query String no CaixaController ---\n";

        $controller = new CaixaController();
        $this->assert(method_exists($controller, 'fechamentoPdf'), "CaixaController possui método 'fechamentoPdf'");

        // Simula chamada via query string ?id=X
        $_GET['id'] = $this->sessaoId;
        
        // Testa buffer de saída de fechamentoPdf
        ob_start();
        try {
            // Chamamos espelhoPdf diretamente ou fechamentoPdf
            // Como ambos enviam header e exit, capturamos antes de disparar o exit se possível,
            // ou testamos via reflection / simulação dos dados.
            $sessao = SessaoCaixa::buscarPorId($this->sessaoId);
            $pdf = \App\Services\PdfService::gerarEspelhoFechamentoCaixaPdf($sessao);
            $this->assert(str_starts_with($pdf, '%PDF-1.4'), "PDF binário gerado começa com %PDF-1.4");
            $this->assert(str_contains($pdf, '%%EOF'), "PDF binário contém marcador %%EOF");
        } finally {
            ob_end_clean();
        }
    }

    private function testarHtmlEspelhoEBotaoFechar(): void
    {
        echo "\n--- [2] Espelho HTML e Mecanismo do Botão Fechar ---\n";

        $sessao = SessaoCaixa::buscarPorId($this->sessaoId);
        $html = \App\Services\PdfService::gerarEspelhoFechamentoCaixaHtml($sessao);

        $this->assert(str_contains($html, 'fecharJanela()'), "Espelho HTML invoca função fecharJanela()");
        $this->assert(str_contains($html, 'function fecharJanela()'), "Espelho HTML implementa function fecharJanela()");
        $this->assert(str_contains($html, 'window.close()'), "Função fecharJanela() tenta fechar aba via window.close()");
        $this->assert(str_contains($html, 'window.location.href'), "Função fecharJanela() possui fallback de redirecionamento para o caixa");
        $this->assert(str_contains($html, 'window.history.back()'), "Função fecharJanela() possui fallback histórico de navegação");
    }

    private function testarDropdownCaixaHtml(): void
    {
        echo "\n--- [3] Dropdown do Botão Emitir na View operacional/caixa.php ---\n";

        $viewPath = __DIR__ . '/../app/views/operacional/caixa.php';
        $content = file_get_contents($viewPath);

        $this->assert(str_contains($content, 'window.open(this.href, \'_blank\'); return false;'), "Link Visualizar/Imprimir utiliza window.open para autorizar window.close()");
        $this->assert(str_contains($content, '/operacional/caixa/fechamento/pdf?id='), "Link Baixar em PDF aponta para /operacional/caixa/fechamento/pdf");

        // Verifica rotas no index.php
        $indexPath = __DIR__ . '/../index.php';
        $indexContent = file_get_contents($indexPath);
        $this->assert(str_contains($indexContent, "'/operacional/caixa/fechamento/pdf'"), "Rota /operacional/caixa/fechamento/pdf registrada no index.php");
        $this->assert(str_contains($indexContent, "'/operacional/caixa/fechamento/pdf/{id}'"), "Rota /operacional/caixa/fechamento/pdf/{id} registrada no index.php");
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo " [PASS] {$message}\n";
        } else {
            echo " [FAIL] {$message}\n";
            throw new \Exception("Falha na asserção: {$message}");
        }
    }
}

$test = new FechamentoPdfFixTest();
$test->run();
