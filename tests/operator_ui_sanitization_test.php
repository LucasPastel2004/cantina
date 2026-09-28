<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste E2E de Sanitização Visual da Interface para o Perfil Operador
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

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

\App\Core\Session::start();

echo "=== TESTE E2E DE SANITIZAÇÃO DA INTERFACE DO OPERADOR ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCondition(bool $cond, string $msg, &$testsPassed, &$testsFailed): void
{
    if ($cond) {
        echo "  [PASS] {$msg}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$msg}\n";
        $testsFailed++;
    }
}

// 1. Simula login do Operador (Maria Atendente)
$loginRes = \App\Core\Auth::attemptUsuario('operador-maria', 'Operador@123');
assertCondition($loginRes['success'] === true, 'Login como operador-maria realizado com sucesso', $testsPassed, $testsFailed);

// 2. Renderiza a tela /admin logado como operador
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/admin';
ob_start();
$adminCtrl = new \App\Controllers\AdminController();
$adminCtrl->index();
$htmlAdmin = ob_get_clean();

assertCondition(
    !str_contains($htmlAdmin, 'id="navSectionAdmin"'),
    'Tela /admin NÃO contém a seção navSectionAdmin para o operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlAdmin, 'Entrada XML NF-e'),
    'Tela /admin NÃO contém "Entrada XML NF-e" para o operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlAdmin, '/admin/colaboradores'),
    'Tela /admin NÃO contém link para /admin/colaboradores na sidebar',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlAdmin, '/admin/importacao-escolar'),
    'Tela /admin NÃO contém link para /admin/importacao-escolar na sidebar',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlAdmin, '/admin/configuracoes'),
    'Tela /admin NÃO contém link para /admin/configuracoes na sidebar',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdmin, 'Operador') && str_contains($htmlAdmin, 'Maria Atendente'),
    'Tela /admin identifica corretamente a operadora Maria Atendente',
    $testsPassed,
    $testsFailed
);

// 3. Renderiza a tela /operacional/corte logado como operador
$_SERVER['REQUEST_URI'] = '/operacional/corte';
ob_start();
$pedidosCtrl = new \App\Controllers\PedidosController();
$pedidosCtrl->corte();
$htmlCorte = ob_get_clean();

assertCondition(
    !str_contains($htmlCorte, 'id="navSectionAdmin"'),
    'Tela /operacional/corte NÃO contém a seção navSectionAdmin para o operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlCorte, 'Entrada XML NF-e'),
    'Tela /operacional/corte NÃO contém "Entrada XML NF-e" para o operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlCorte, 'Corte do Turno'),
    'Tela /operacional/corte renderiza perfeitamente o módulo de corte para o operador',
    $testsPassed,
    $testsFailed
);

// 4. Renderiza a tela /estoque logado como operador
$_SERVER['REQUEST_URI'] = '/estoque';
ob_start();
$estoqueCtrl = new \App\Controllers\EstoqueController();
$estoqueCtrl->index();
$htmlEstoque = ob_get_clean();

assertCondition(
    !str_contains($htmlEstoque, 'id="navSectionAdmin"'),
    'Tela /estoque NÃO contém a seção navSectionAdmin para o operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlEstoque, 'Entrada XML NF-e'),
    'Tela /estoque NÃO contém "Entrada XML NF-e" nem na sidebar nem nos botões de topo para o operador',
    $testsPassed,
    $testsFailed
);

// 5. Logout do operador e login como Administrador
\App\Core\Auth::logout();
$loginAdmin = \App\Core\Auth::attemptUsuario('admin', 'Admin@123');
assertCondition($loginAdmin['success'] === true, 'Login como admin realizado com sucesso', $testsPassed, $testsFailed);

// 6. Renderiza a tela /admin logado como administrador
$_SERVER['REQUEST_URI'] = '/admin';
ob_start();
$adminCtrl->index();
$htmlAdminCompleto = ob_get_clean();

assertCondition(
    str_contains($htmlAdminCompleto, 'id="navSectionAdmin"'),
    'Tela /admin EXIBE a seção navSectionAdmin para o Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdminCompleto, 'Entrada XML NF-e'),
    'Tela /admin EXIBE "Entrada XML NF-e" para o Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdminCompleto, '/admin/colaboradores') &&
    str_contains($htmlAdminCompleto, '/admin/configuracoes') &&
    str_contains($htmlAdminCompleto, '/admin/produtos'),
    'Tela /admin EXIBE todos os atalhos de gestão para o Administrador',
    $testsPassed,
    $testsFailed
);

// Limpeza de sessão
\App\Core\Auth::logout();

echo "\nRESUMO: {$testsPassed} passaram, {$testsFailed} falharam.\n";
exit($testsFailed > 0 ? 1 : 0);
