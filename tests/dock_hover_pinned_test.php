<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Comportamento Híbrido Hover & Pinned do Dock Sidebar
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

echo "======================================================================\n";
echo " TESTES: COMPORTAMENTO HÍBRIDO HOVER & PINNED NO DOCK SIDEBAR        \n";
echo "======================================================================\n\n";

$passou = 0;
$total = 0;

$assertCustom = function(bool $condicao, string $msg) use (&$passou, &$total) {
    $total++;
    if ($condicao) {
        $passou++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
};

$sidebarContent = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
$appJs = file_get_contents(__DIR__ . '/../assets/js/app.js');
$customCss = file_get_contents(__DIR__ . '/../assets/css/custom.css');

// 1. Verificação do layout sidebar.php (amarração de categorias)
$assertCustom(str_contains($sidebarContent, 'id="flyoutOperacional" data-category="operacional"'), "Flyout Operacional possui data-category='operacional'");
$assertCustom(str_contains($sidebarContent, 'id="flyoutEstoque" data-category="estoque"'), "Flyout Estoque possui data-category='estoque'");
$assertCustom(str_contains($sidebarContent, 'id="flyoutFinanceiro" data-category="financeiro"'), "Flyout Financeiro possui data-category='financeiro'");
$assertCustom(str_contains($sidebarContent, 'id="navSectionAdmin" data-category="admin"'), "Flyout Admin possui data-category='admin'");
$assertCustom(str_contains($sidebarContent, 'id="flyoutRelatorios" data-category="relatorios"'), "Flyout Relatórios possui data-category='relatorios'");

// 2. Verificação da lógica JavaScript em app.js
$assertCustom(str_contains($appJs, 'let currentFlyoutCategory'), "app.js declara variável de controle currentFlyoutCategory");
$assertCustom(str_contains($appJs, 'let isFlyoutPinned'), "app.js declara variável de controle isFlyoutPinned");
$assertCustom(str_contains($appJs, 'function abrirFlyout('), "app.js implementa função abrirFlyout(category, pinned)");
$assertCustom(str_contains($appJs, 'function toggleCategoryFlyout('), "app.js implementa função toggleCategoryFlyout(category)");
$assertCustom(str_contains($appJs, 'function fecharFlyouts('), "app.js implementa função fecharFlyouts()");
$assertCustom(str_contains($appJs, 'function agendarFechamentoFlyoutHover()'), "app.js implementa agendamento de fechamento com buffer de hover");
$assertCustom(str_contains($appJs, 'function cancelarFechamentoFlyoutHover()'), "app.js implementa cancelamento de fechamento no hover");
$assertCustom(str_contains($appJs, 'function inicializarHoverDockSidebar()'), "app.js implementa listeners de hover nos botões e nos menus");
$assertCustom(str_contains($appJs, 'inicializarHoverDockSidebar();'), "app.js inicializa hover do dock no carregamento do DOM");
$assertCustom(str_contains($appJs, 'btn.addEventListener(\'mouseenter\''), "app.js escuta mouseenter nos botões do dock");
$assertCustom(str_contains($appJs, 'flyout.addEventListener(\'mouseenter\''), "app.js escuta mouseenter no corpo do flyout para mantê-lo aberto");
$assertCustom(str_contains($appJs, 'flyout.addEventListener(\'mouseleave\''), "app.js escuta mouseleave no corpo do flyout");

// 3. Verificação do CSS para estado travado (pinned), ausência de linha bugada e repouso neutro
$assertCustom(str_contains($customCss, '.dock-btn.flyout-pinned'), "custom.css define estado travado .dock-btn.flyout-pinned");
$assertCustom(str_contains($customCss, 'background-color: var(--surface-bg') && str_contains($customCss, '.dock-btn.flyout-pinned'), "custom.css adota a cor de fundo do site no botão pinned");
$assertCustom(!str_contains($customCss, '.dock-btn.is-current:not(.active):not(.flyout-open)::after'), "Linha de destaque antiga ::after foi completamente removida do dock");
$assertCustom(!str_contains($customCss, '.dock-btn.is-current:not(.active):not(.flyout-open):not(.flyout-pinned)'), "Removida regra que forçava ícone para azul em repouso");
$assertCustom(str_contains($customCss, 'color: #475569; /* Neutro uniforme para todos os ícones em repouso */'), "Todos os ícones do dock adotam cor neutra #475569 em repouso");
$assertCustom(str_contains($customCss, '.sidebar-flyout.flyout-pinned'), "custom.css define elevação do menu travado .sidebar-flyout.flyout-pinned");

echo "\n=======================================================\n";
echo " RESULTADO: {$passou} / {$total} testes passaram!\n";
echo "=======================================================\n";

if ($passou === $total) {
    echo "\n>>> TODOS OS TESTES DO HOVER & PINNED PASSARAM COM 100%! <<<\n";
    exit(0);
} else {
    echo "\n>>> HOUVE FALHA NOS TESTES. <<<\n";
    exit(1);
}
