<?php
/**
 * Teste automatizado para a responsividade da barra lateral (contratação e esticamento)
 * e visibilidade do dropdown de busca fora da card-box
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

echo "=================================================================\n";
echo " TESTES DE RESPONSIVIDADE DA SIDEBAR E DROPDOWN DE BUSCA\n";
echo "=================================================================\n\n";

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

$css = file_get_contents(__DIR__ . '/../assets/css/custom.css');
$js = file_get_contents(__DIR__ . '/../assets/js/app.js');
$viewContestacoes = file_get_contents(__DIR__ . '/../app/views/admin/contestacoes.php');
$viewSidebar = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');

// 1. Validação de overflow visível para dropdown
$assertCustom(str_contains($css, '.card-overflow-visible') && str_contains($css, 'overflow: visible !important;'), "custom.css define classe .card-overflow-visible com overflow: visible !important");
$assertCustom(str_contains($viewContestacoes, 'card-overflow-visible') && str_contains($viewContestacoes, 'style="overflow: visible !important;"'), "Card de busca em contestacoes.php configurado com overflow visível");
$assertCustom(str_contains($viewContestacoes, 'top: calc(100% + 4px);'), "Dropdown #buscaSugestoesDropdown posicionado abaixo do input com top: calc(100% + 4px)");

// 2. Validação da Sidebar (Breakpoint 768px para Notebooks e Desktops)
$assertCustom(str_contains($css, '@media (min-width: 768px)'), "Breakpoint da sidebar ajustado para 768px (suporte a notebooks com scaling de 125%/150%)");
$assertCustom(str_contains($css, 'margin-left: 240px;') && str_contains($css, 'width: calc(100% - 240px);'), "Ao expandir a sidebar: parte principal contrai com margin-left: 240px e width: calc(100% - 240px)");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .app-sidebar') && str_contains($css, 'transform: translateX(-240px);'), "Ao colapsar a sidebar: barra se esconde com transform: translateX(-240px)");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .app-main-area') && str_contains($css, 'margin-left: 0;') && str_contains($css, 'width: 100%;'), "Ao colapsar a sidebar: parte principal se estica para 100% da tela");

// 3. Validação de eliminação de gaveta flutuante por hover
$assertCustom(!str_contains($js, 'abrirSidebarFlutuante'), "app.js não possui mais função abrirSidebarFlutuante (sem hover drawer invasivo)");
$assertCustom(str_contains($js, 'window.innerWidth >= 768'), "app.js utiliza breakpoint 768px para alternar colapso");
$assertCustom(str_contains($viewSidebar, 'd-none d-md-inline-flex'), "sidebar.php utiliza classe d-md-inline-flex para o botão de recolhimento");

echo "\n=======================================================\n";
echo " RESULTADO: {$passou} / {$total} testes passaram!\n";
echo "=======================================================\n\n";

if ($passou === $total) {
    echo ">>> TODOS OS TESTES PASSARAM COM SUCESSO! <<<\n";
    exit(0);
} else {
    echo ">>> ALGUNS TESTES FALHARAM! <<<\n";
    exit(1);
}
