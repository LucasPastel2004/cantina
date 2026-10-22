<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Visibilidade de Opções da Sidebar (Item Configurações Globais)
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Carrega classes do Core
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

echo "=== TESTE DE VISIBILIDADE DA SIDEBAR (CONFIGURAÇÕES GLOBAIS) ===\n\n";

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

// 1. Renderiza a sidebar com usuário Administrador
ob_start();
$baseUrl = '';
$activeMenu = '';
$user = [
    'nome'   => 'Administrador Geral',
    'usuario'=> 'admin',
    'perfil' => 'administrador',
    'role'   => 'administrador'
];
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlAdmin = ob_get_clean();

assertCondition(
    str_contains($htmlAdmin, 'id="navSectionAdmin"'),
    'Seção navSectionAdmin presente para Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdmin, '/admin/configuracoes'),
    'Link /admin/configuracoes presente na sidebar para Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdmin, '/admin/colaboradores'),
    'Link /admin/colaboradores presente na sidebar para Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlAdmin, '/estoque/importar-xml'),
    'Link Entrada XML NF-e (/estoque/importar-xml) REMOVIDO da sidebar para Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdmin, '/estoque/entrada'),
    'Link Entrada de Estoque (/estoque/entrada) presente na sidebar para Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdmin, 'Administrador'),
    'Badge/perfil "Administrador" exibido no rodapé da sidebar',
    $testsPassed,
    $testsFailed
);

// 2. Renderiza a sidebar com usuário Operador (Maria Atendente / Balcão / Caixa)
ob_start();
$user = [
    'nome'   => 'Maria Atendente (Operador do Caixa)',
    'usuario'=> 'operador-maria',
    'perfil' => 'operador',
    'role'   => 'operador'
];
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlOperador = ob_get_clean();

assertCondition(
    !str_contains($htmlOperador, 'id="navSectionAdmin"'),
    'Seção navSectionAdmin TOTALMENTE OCULTA para perfil Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlOperador, '/admin/configuracoes'),
    'Link /admin/configuracoes OCULTO para perfil Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlOperador, '/admin/colaboradores'),
    'Link /admin/colaboradores OCULTO para perfil Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlOperador, '/admin/produtos'),
    'Link /admin/produtos OCULTO para perfil Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    !str_contains($htmlOperador, '/estoque/importar-xml'),
    'Link Entrada XML NF-e OCULTO para perfil Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlOperador, '/operacional/corte') &&
    str_contains($htmlOperador, '/operacional/pdv') &&
    str_contains($htmlOperador, '/operacional/caixa') &&
    str_contains($htmlOperador, '/operacional/tickets') &&
    str_contains($htmlOperador, '/operacional/estornos'),
    'Módulos operacionais (Corte, PDV, Caixa, Tickets, Estornos) visíveis normalmente para Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlOperador, '/estoque') &&
    str_contains($htmlOperador, '/estoque/perdas'),
    'Módulos de estoque diário (Saldos e Perdas) visíveis para Operador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlOperador, 'Operador'),
    'Badge/perfil "Operador" exibido no rodapé da sidebar',
    $testsPassed,
    $testsFailed
);

// 3. Simula autenticação real na Session como operador e valida renderização via Auth::user()
\App\Core\Session::set('_auth_logged', true);
\App\Core\Session::set('_auth_type', 'usuario');
\App\Core\Session::set('_auth_id', 2);
\App\Core\Session::set('_auth_username', 'operador-maria');
\App\Core\Session::set('_auth_nome', 'Maria Atendente');
\App\Core\Session::set('_auth_role', 'operador');

ob_start();
unset($user);
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlAuthOperador = ob_get_clean();

assertCondition(
    !str_contains($htmlAuthOperador, 'navSectionAdmin') && !str_contains($htmlAuthOperador, 'importar-xml'),
    'Sidebar consumindo Auth::user() em sessão real oculta bloco admin e XML para operador',
    $testsPassed,
    $testsFailed
);

// Limpeza de sessão
\App\Core\Session::destroy();

echo "\nRESUMO: {$testsPassed} passaram, {$testsFailed} falharam.\n";
exit($testsFailed > 0 ? 1 : 0);
