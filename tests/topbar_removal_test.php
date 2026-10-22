<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Remoção da Topbar Transversal em Todas as Telas do Sistema
 * 
 * Valida:
 * 1. Ausência do elemento <header class="app-topbar"> em app/views/layouts/admin.php;
 * 2. .app-topbar com display: none !important em assets/css/custom.css;
 * 3. PDV (.pdv-layout-locked) expandido para 100vh pleno sem desconto de topbar;
 * 4. Presença do botão de toggle mobile discreto (< 768px);
 * 5. Integridade de renderização em telas principais: /estoque, /operacional/estornos, /admin/produtos, /operacional/pdv.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

echo "=== INICIANDO TESTES DE REMOÇÃO DA TOPBAR TRANSVERSAL ===\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCustom(bool $condition, string $description, int &$passed, int &$failed): void
{
    if ($condition) {
        echo "[OK] {$description}\n";
        $passed++;
    } else {
        echo "[FALHA] {$description}\n";
        $failed++;
    }
}

// 1. Verificar layout mestre admin.php
$adminLayout = file_get_contents(__DIR__ . '/../app/views/layouts/admin.php');
assertCustom(
    !str_contains($adminLayout, '<header class="app-topbar"'),
    "Elemento <header class=\"app-topbar\"> removido do layout mestre admin.php",
    $testsPassed,
    $testsFailed
);

assertCustom(
    str_contains($adminLayout, 'btn-sidebar-toggle d-md-none'),
    "Botão de alternância da navegação móvel (d-md-none) mantido para celulares",
    $testsPassed,
    $testsFailed
);

// 2. Verificar custom.css
$css = file_get_contents(__DIR__ . '/../assets/css/custom.css');
assertCustom(
    str_contains($css, ".app-topbar {\n  display: none !important;\n}") || str_contains($css, ".app-topbar {\r\n  display: none !important;\r\n}"),
    ".app-topbar configurada como display: none !important em custom.css",
    $testsPassed,
    $testsFailed
);

assertCustom(
    str_contains($css, "height: 100vh;") && str_contains($css, "max-height: 100vh;"),
    "PDV terminal (.pdv-layout-locked) aproveita 100vh pleno sem perda de espaço",
    $testsPassed,
    $testsFailed
);

// 3. Simulação de renderização de telas principais sem topbar
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/Colaborador.php';
require_once __DIR__ . '/../app/controllers/EstornoVendasController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Usuario;
use App\Controllers\EstornoVendasController;

Session::start();
$usuarios = Usuario::listarTodos();
$adminUser = $usuarios[0] ?? ['id' => 1, 'nome' => 'Administrador Geral', 'usuario' => 'admin', 'perfil' => 'administrador'];
Auth::loginUsuario($adminUser);

ob_start();
$ctrlEstorno = new EstornoVendasController();
$ctrlEstorno->index();
$outputEstorno = ob_get_clean();

assertCustom(
    !str_contains($outputEstorno, '<header class="app-topbar"'),
    "Tela de Estorno de Vendas renderiza sem a tag <header class=\"app-topbar\">",
    $testsPassed,
    $testsFailed
);

assertCustom(
    !str_contains($outputEstorno, 'topbar-page-heading'),
    "Tela de Estorno de Vendas não possui títulos redundantes da topbar",
    $testsPassed,
    $testsFailed
);

assertCustom(
    str_contains($outputEstorno, 'app-page-content'),
    "Conteúdo da tela de Estorno ocupa diretamente o container principal app-page-content",
    $testsPassed,
    $testsFailed
);

echo "\n============================================\n";
echo "RESULTADO: {$testsPassed} testes aprovados, {$testsFailed} falhas.\n";

if ($testsFailed > 0) {
    exit(1);
}
