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

// 1. Renderiza a sidebar em cenário sem usuário na view (default)
ob_start();
$baseUrl = '';
$activeMenu = '';
$user = null;
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlDefault = ob_get_clean();

assertCondition(
    str_contains($htmlDefault, '/admin/configuracoes'),
    'Link /admin/configuracoes presente na sidebar por padrão',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlDefault, 'Configurações Globais'),
    'Texto "Configurações Globais" presente na sidebar por padrão',
    $testsPassed,
    $testsFailed
);

// 2. Renderiza a sidebar com usuário admin
ob_start();
$user = [
    'nome'   => 'Administrador Geral',
    'usuario'=> 'admin',
    'perfil' => 'administrador',
    'role'   => 'administrador'
];
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlAdmin = ob_get_clean();

assertCondition(
    str_contains($htmlAdmin, '/admin/configuracoes'),
    'Link /admin/configuracoes presente na sidebar para Administrador',
    $testsPassed,
    $testsFailed
);

assertCondition(
    str_contains($htmlAdmin, 'Administrador'),
    'Badge/perfil "Administrador" exibido no rodapé da sidebar',
    $testsPassed,
    $testsFailed
);

// 3. Renderiza a sidebar com usuário operador (como na tela de demonstração ou operador do balcão)
ob_start();
$user = [
    'nome'   => 'Carlos Alberto (Operador)',
    'usuario'=> 'operador-carlos',
    'perfil' => 'operador',
    'role'   => 'operador'
];
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlOperador = ob_get_clean();

assertCondition(
    str_contains($htmlOperador, '/admin/configuracoes'),
    'Link /admin/configuracoes visível na seção Administração para facilidade de navegação operacional',
    $testsPassed,
    $testsFailed
);

// 4. Verifica via requisição HTTP se a tela /design/admin exibe Configurações Globais
$ch = curl_init('http://localhost/design/admin');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resDesign = curl_exec($ch);
curl_close($ch);

assertCondition(
    str_contains($resDesign, 'Configurações Globais'),
    'Endpoint /design/admin exibe "Configurações Globais" no HTML entregue pelo servidor',
    $testsPassed,
    $testsFailed
);

// 5. Verifica via requisição HTTP se a home / exibe link para Configurações Globais
$ch = curl_init('http://localhost/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resHome = curl_exec($ch);
curl_close($ch);

assertCondition(
    str_contains($resHome, '/admin/configuracoes'),
    'Página inicial (/) possui atalho direto para Configurações Globais',
    $testsPassed,
    $testsFailed
);

echo "\nRESUMO: {$testsPassed} passaram, {$testsFailed} falharam.\n";
exit($testsFailed > 0 ? 1 : 0);
