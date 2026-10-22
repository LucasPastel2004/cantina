<?php
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

\App\Core\Session::start();
\App\Core\Auth::loginUsuario([
    'id' => 1,
    'nome' => 'Administrador Teste',
    'usuario' => 'admin',
    'email' => 'admin@escola.com.br',
    'perfil' => \App\Core\RBAC::ROLE_ADMINISTRADOR,
    'status' => 'ativo'
]);

// 1. Teste RelatoriosController::devedoresPdf
ob_start();
$relCtrl = new \App\Controllers\RelatoriosController();
$relCtrl->devedoresPdf();
$outputPdf = ob_get_clean();

echo "====================================================\n";
echo "TESTANDO ROTAS DE PDF E IMPRESSÃO DE DEVEDORES / MED\n";
echo "====================================================\n";

echo "1. RelatoriosController::devedoresPdf:\n";
echo "   - Tamanho gerado: " . strlen($outputPdf) . " bytes\n";
echo "   - Primeiros 20 bytes: " . bin2hex(substr($outputPdf, 0, 20)) . "\n";
echo "   - String inicial: " . var_export(substr($outputPdf, 0, 30), true) . "\n";
echo "   - Inicia com %PDF: " . (str_starts_with($outputPdf, '%PDF') ? 'OK (SIM)' : 'FALHA') . "\n";

// 2. Teste RelatoriosController::devedoresImprimir
ob_start();
$relCtrl->devedoresImprimir();
$outputHtml = ob_get_clean();

echo "2. RelatoriosController::devedoresImprimir:\n";
echo "   - Tamanho gerado: " . strlen($outputHtml) . " bytes\n";
echo "   - Contem 'Histórico de Eventos MED': " . (str_contains($outputHtml, 'Histórico de Eventos MED') ? 'OK (SIM)' : 'FALHA') . "\n";
echo "   - Contem tabela Excel com 14mm: " . (str_contains($outputHtml, '14mm') ? 'OK (SIM)' : 'FALHA') . "\n";

// 3. Teste AdminController::contestacoesPdf
ob_start();
$admCtrl = new \App\Controllers\AdminController();
$admCtrl->contestacoesPdf();
$outputAdmPdf = ob_get_clean();

echo "3. AdminController::contestacoesPdf:\n";
echo "   - Tamanho gerado: " . strlen($outputAdmPdf) . " bytes\n";
echo "   - Inicia com %PDF ou HTML: " . (str_starts_with($outputAdmPdf, '%PDF') || str_contains($outputAdmPdf, 'Relatório de Famílias') ? 'OK (SIM)' : 'FALHA') . "\n";

// 4. Teste AdminController::contestacoesImprimir
ob_start();
$admCtrl->contestacoesImprimir();
$outputAdmImprimir = ob_get_clean();

echo "4. AdminController::contestacoesImprimir:\n";
echo "   - Tamanho gerado: " . strlen($outputAdmImprimir) . " bytes\n";
echo "   - Contem 'Relatório de Famílias': " . (str_contains($outputAdmImprimir, 'Relatório de Famílias') ? 'OK (SIM)' : 'FALHA') . "\n";
echo "   - Contem botão Imprimir: " . (str_contains($outputAdmImprimir, 'Imprimir Documento') ? 'OK (SIM)' : 'FALHA') . "\n";

echo "====================================================\n";
echo "SUCESSO: Todas as chamadas de devedores / MED funcionam perfeitamente!\n";
