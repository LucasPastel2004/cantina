<?php
/**
 * Teste do Ciclo Completo de Login, Alertas e Logout
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_START', microtime(true));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

$config = require __DIR__ . '/../config/config.php';
\App\Core\Session::start($config);

echo "=== TESTE DE CICLO DE AUTENTICAÇÃO E ALERTAS ===" . PHP_EOL;

// 1. Alerta de sucesso (Login)
\App\Core\Session::setFlash('success', 'Bem-vindo(a) ao Portal da Família!');
ob_start();
require __DIR__ . '/../app/views/partials/alert.php';
$htmlSuccess = ob_get_clean();

$passouSuccess = str_contains($htmlSuccess, 'Bem-vindo(a) ao Portal da Família!') 
              && str_contains($htmlSuccess, 'alert-enterprise-success');
echo "1. Renderização de Flash Success -> " . ($passouSuccess ? "PASS" : "FAIL") . PHP_EOL;

// 2. Alerta de informação (Logout)
\App\Core\Session::setFlash('info', 'Você saiu da sua conta com segurança.');
ob_start();
require __DIR__ . '/../app/views/partials/alert.php';
$htmlInfo = ob_get_clean();

$passouInfo = str_contains($htmlInfo, 'Você saiu da sua conta com segurança.') 
           && str_contains($htmlInfo, 'alert-enterprise-info');
echo "2. Renderização de Flash Info (Logout) -> " . ($passouInfo ? "PASS" : "FAIL") . PHP_EOL;

// 3. Alerta de perigo (Erro de Login)
\App\Core\Session::setFlash('danger', 'Credenciais inválidas.');
ob_start();
require __DIR__ . '/../app/views/partials/alert.php';
$htmlDanger = ob_get_clean();

$passouDanger = str_contains($htmlDanger, 'Credenciais inválidas.') 
             && str_contains($htmlDanger, 'alert-enterprise-danger');
echo "3. Renderização de Flash Danger -> " . ($passouDanger ? "PASS" : "FAIL") . PHP_EOL;

// 4. Múltiplas mensagens flash
\App\Core\Session::setFlash('warning', 'Alerta 1');
\App\Core\Session::setFlash('warning', 'Alerta 2');
ob_start();
require __DIR__ . '/../app/views/partials/alert.php';
$htmlMulti = ob_get_clean();

$passouMulti = str_contains($htmlMulti, 'Alerta 1') && str_contains($htmlMulti, 'Alerta 2');
echo "4. Renderização de Múltiplos Alertas -> " . ($passouMulti ? "PASS" : "FAIL") . PHP_EOL;

// 5. Renderização completa do Portal da Família com Flash Message
\App\Core\Auth::attemptResponsavel('111.222.333-44', 'Familia@123');
\App\Core\Session::setFlash('success', 'Bem-vindo(a) ao Portal da Família!');

$portalController = new \App\Controllers\PortalController();
ob_start();
$portalController->index();
$htmlPortal = ob_get_clean();

$passouPortal = str_contains($htmlPortal, 'Portal da Família') 
             && str_contains($htmlPortal, 'Bem-vindo(a) ao Portal da Família!')
             && str_contains($htmlPortal, 'Saldo Disponível');
echo "5. Renderização Completa do Portal com Alerta -> " . ($passouPortal ? "PASS" : "FAIL") . PHP_EOL;

// 6. Teste de Logout
\App\Core\Auth::logout();
\App\Core\Session::setFlash('info', 'Você saiu da sua conta com segurança.');

$authController = new \App\Controllers\AuthController();
ob_start();
$authController->login();
$htmlLogin = ob_get_clean();

$passouLogin = str_contains($htmlLogin, 'Você saiu da sua conta com segurança.')
            && str_contains($htmlLogin, 'Colégio Monteiro Lobato');
echo "6. Renderização da Tela de Login pós Logout -> " . ($passouLogin ? "PASS" : "FAIL") . PHP_EOL;

if ($passouSuccess && $passouInfo && $passouDanger && $passouMulti && $passouPortal && $passouLogin) {
    echo PHP_EOL . "STATUS: TODAS AS 6 ETAPAS FORAM APROVADAS COM 100% DE SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "STATUS: FALHA NAS ETAPAS!" . PHP_EOL;
    exit(1);
}
