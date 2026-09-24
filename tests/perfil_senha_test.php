<?php
/**
 * Teste unitário e de integração: Alteração de Senha no Portal da Família
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Carrega autoload do app
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Session;
use App\Core\Auth;
use App\Models\Responsavel;
use App\Controllers\PortalController;

echo "=== TESTE: ALTERAÇÃO DE SENHA NO PORTAL DA FAMÍLIA ===\n\n";

Session::start();
$respModel = new Responsavel();
$cpfTeste = '11122233344';
$resp = $respModel->findByCpf($cpfTeste);

if (!$resp) {
    echo "[-] ERRO: Responsável de teste não encontrado.\n";
    exit(1);
}

// 1. Simula login do responsável
Auth::loginResponsavel($resp);
echo "[+] 1. Login simulado para {$resp['nome']} (ID: {$resp['id']})\n";

// 2. Testa validação: Senha atual errada
$tokenCsrf = Session::generateCsrfToken();
$_POST = [
    'csrf_token'      => $tokenCsrf,
    'senha_atual'     => 'SenhaIncorreta@999',
    'nova_senha'      => 'NovaSenha@123',
    'confirmar_senha' => 'NovaSenha@123'
];
$_SERVER['REQUEST_METHOD'] = 'POST';

ob_start();
$controller = new PortalController();
$controller->alterarSenha();
ob_end_clean();

$flash = Session::getFlash('danger');
if (($flash[0] ?? '') === 'A senha atual informada está incorreta.') {
    echo "[+] 2. Validação de senha atual incorreta: OK ('{$flash[0]}')\n";
} else {
    echo "[-] ERRO no teste 2. Flash retornado: " . var_export($flash, true) . "\n";
    exit(1);
}

// 3. Testa validação: Confirmação de senha divergente
$tokenCsrf = Session::generateCsrfToken();
$_POST = [
    'csrf_token'      => $tokenCsrf,
    'senha_atual'     => 'Familia@123',
    'nova_senha'      => 'NovaSenha@123',
    'confirmar_senha' => 'OutraSenha@456'
];

ob_start();
$controller = new PortalController();
$controller->alterarSenha();
ob_end_clean();

$flashWarn = Session::getFlash('warning');
if (strpos($flashWarn[0] ?? '', 'não coincide') !== false) {
    echo "[+] 3. Validação de confirmação divergente: OK ('{$flashWarn[0]}')\n";
} else {
    echo "[-] ERRO no teste 3. Flash retornado: " . var_export($flashWarn, true) . "\n";
    exit(1);
}

// 4. Testa alteração com sucesso
$tokenCsrf = Session::generateCsrfToken();
$_POST = [
    'csrf_token'      => $tokenCsrf,
    'senha_atual'     => 'Familia@123',
    'nova_senha'      => 'Familia@Nova456',
    'confirmar_senha' => 'Familia@Nova456'
];

ob_start();
$controller = new PortalController();
$controller->alterarSenha();
ob_end_clean();

$flashSuccess = Session::getFlash('success');
if (strpos($flashSuccess[0] ?? '', 'sucesso') !== false) {
    echo "[+] 4. Alteração de senha bem-sucedida: OK ('{$flashSuccess[0]}')\n";
} else {
    echo "[-] ERRO no teste 4. Flash retornado: " . var_export($flashSuccess, true) . "\n";
    exit(1);
}

// 5. Valida se o login com a senha antiga falha e com a nova funciona
$testeAntiga = Auth::attemptResponsavel($cpfTeste, 'Familia@123');
if (!$testeAntiga['success']) {
    echo "[+] 5. Login com a senha antiga rejeitado: OK\n";
} else {
    echo "[-] ERRO: Senha antiga ainda está funcionando!\n";
    exit(1);
}

$testeNova = Auth::attemptResponsavel($cpfTeste, 'Familia@Nova456');
if ($testeNova['success']) {
    echo "[+] 6. Login com a nova senha aprovado: OK\n";
} else {
    echo "[-] ERRO: Nova senha não foi aceita no login!\n";
    exit(1);
}

// 7. Restaura a senha para Familia@123 para manter integridade dos outros testes
$respModel->updatePassword((int)$resp['id'], 'Familia@123');
$testeRestaurada = Auth::attemptResponsavel($cpfTeste, 'Familia@123');
if ($testeRestaurada['success']) {
    echo "[+] 7. Senha restaurada para Familia@123 com sucesso: OK\n";
} else {
    echo "[-] ERRO ao restaurar senha original!\n";
    exit(1);
}

echo "\n>>> TODOS OS TESTES DE ALTERAÇÃO DE SENHA PASSARAM COM SUCESSO! <<<\n";
