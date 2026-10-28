<?php
/**
 * Teste de Validação: Título da Aba e Cabeçalho de Entrada de Mercadorias
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($c) {
    if (str_starts_with($c, 'App\\')) {
        $file = __DIR__ . '/../app/' . str_replace('\\', '/', substr($c, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use App\Controllers\EstoqueController;
use App\Core\Auth;

$_SESSION = [];
// Simula login de administrador
$pdo = \Database::getInstance();
$adminUser = $pdo->query("SELECT * FROM usuarios WHERE perfil = 'administrador' AND deleted_at IS NULL LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
if (!$adminUser) {
    $adminUser = ['id' => 1, 'nome' => 'Admin Teste', 'perfil' => 'administrador', 'email' => 'admin@teste.local'];
}
$_SESSION['user_id'] = $adminUser['id'];
$_SESSION['user'] = $adminUser;

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/index.php';

ob_start();
$controller = new EstoqueController();
$controller->entrada();
$html = ob_get_clean();

$pass = true;

// 1. Testa tag <title>
if (preg_match('/<title>(.*?)<\/title>/s', $html, $matchesTitle)) {
    $titleContent = trim($matchesTitle[1]);
    if ($titleContent === 'Entrada de Mercadorias') {
        echo "[PASS] Tag <title> da aba do navegador é exatamente: 'Entrada de Mercadorias'\n";
    } else {
        echo "[FAIL] Tag <title> inesperada: '{$titleContent}' (esperado: 'Entrada de Mercadorias')\n";
        $pass = false;
    }
} else {
    echo "[FAIL] Tag <title> não encontrada no HTML gerado\n";
    $pass = false;
}

// 2. Testa tag <h1>
if (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $matchesH1)) {
    $h1Content = trim(strip_tags($matchesH1[1]));
    if ($h1Content === 'Entrada de Mercadorias') {
        echo "[PASS] Tag <h1> da página é exatamente: 'Entrada de Mercadorias'\n";
    } else {
        echo "[FAIL] Tag <h1> inesperada: '{$h1Content}' (esperado: 'Entrada de Mercadorias')\n";
        $pass = false;
    }
} else {
    echo "[FAIL] Tag <h1> não encontrada no HTML gerado\n";
    $pass = false;
}

// 3. Testa que 'Registro Operacional' não está presente no título ou cabeçalho
if (!str_contains($html, 'Registro Operacional de Entrada de Mercadorias')) {
    echo "[PASS] O texto antigo 'Registro Operacional de Entrada de Mercadorias' foi totalmente removido\n";
} else {
    echo "[FAIL] O texto 'Registro Operacional de Entrada de Mercadorias' ainda consta na página\n";
    $pass = false;
}

if (!$pass) {
    exit(1);
}

echo "\nTodos os testes de validação de títulos foram APROVADOS com sucesso!\n";
