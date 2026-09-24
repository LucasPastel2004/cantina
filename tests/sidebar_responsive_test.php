<?php
/**
 * Teste automatizado para a responsividade da barra lateral (contração e esticamento),
 * mecanismo de trava/destrava pelas setinhas, remoção de botão excessivo na topbar,
 * abertura por hover na borda esquerda e visibilidade do dropdown de busca.
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

echo "=================================================================\n";
echo " TESTES DE SIDEBAR: TRAVA, HOVER PEEK E LAYOUT RESPONSIVO\n";
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
$viewAdmin = file_get_contents(__DIR__ . '/../app/views/layouts/admin.php');

// 1. Validação de overflow visível para dropdown
$assertCustom(str_contains($css, '.card-overflow-visible') && str_contains($css, 'overflow: visible !important;'), "custom.css define classe .card-overflow-visible com overflow: visible !important");
$assertCustom(str_contains($viewContestacoes, 'card-overflow-visible') && str_contains($viewContestacoes, 'style="overflow: visible !important;"'), "Card de busca em contestacoes.php configurado com overflow visível");
$assertCustom(str_contains($viewContestacoes, 'top: calc(100% + 4px);'), "Dropdown #buscaSugestoesDropdown posicionado abaixo do input com top: calc(100% + 4px)");

// 2. Validação da Sidebar no Desktop e Notebooks (Breakpoint >= 768px)
$assertCustom(str_contains($css, '@media (min-width: 768px)'), "Breakpoint da sidebar configurado para 768px (suporte a notebooks com scaling)");
$assertCustom(str_contains($css, 'margin-left: 240px;') && str_contains($css, 'width: calc(100% - 240px);'), "Ao expandir a sidebar: área principal contrai com margin-left: 240px e width: calc(100% - 240px)");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .app-sidebar') && str_contains($css, 'transform: translateX(-240px);'), "Ao colapsar a sidebar: barra se esconde com transform: translateX(-240px)");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .app-main-area') && str_contains($css, 'margin-left: 0;') && str_contains($css, 'width: 100%;'), "Ao colapsar a sidebar: área principal se estica para 100% da tela");

// 3. Validação de remoção do botão excessivo na topbar quando expandida (Anexo 3)
$assertCustom(str_contains($css, '.app-admin-wrapper:not(.sidebar-collapsed) .btn-sidebar-toggle') && str_contains($css, 'display: none !important;'), "Topbar oculta botão excessivo btn-sidebar-toggle quando sidebar está expandida (Anexo 3)");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .btn-sidebar-toggle') && str_contains($css, 'display: inline-flex !important;'), "Topbar exibe btn-sidebar-toggle quando colapsada para travar aberta");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .btn-sidebar-toggle .icon-toggle-arrow') && str_contains($css, 'display: inline-block !important;'), "Botão toggle exibe setinha '>' quando colapsada");

// 4. Validação do Hover Peek na Borda Esquerda (Anexo 2)
$assertCustom(str_contains($viewSidebar, 'id="sidebarEdgeTrigger"'), "sidebar.php contém gatilho de borda #sidebarEdgeTrigger na extrema esquerda");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed .sidebar-edge-trigger') && str_contains($css, 'width: 20px;'), "custom.css ativa faixa invisível de 20px na extrema esquerda apenas quando colapsada");
$assertCustom(str_contains($css, '.app-admin-wrapper.sidebar-collapsed.sidebar-peek-open .app-sidebar') && str_contains($css, 'transform: translateX(0) !important;'), "custom.css translada barra lateral suavemente no hover peek (.sidebar-peek-open)");

// 5. Validação da Lógica JavaScript de Trava (Pin/Unpin) e Hover no app.js
$assertCustom(str_contains($js, 'function abrirSidebarPeek()'), "app.js possui função abrirSidebarPeek para hover na borda esquerda");
$assertCustom(str_contains($js, 'function fecharSidebarPeekComDelay()'), "app.js possui fechamento suave com delay ao retirar o cursor da sidebar");
$assertCustom(str_contains($js, "localStorage.setItem('cantina_sidebar_preference'"), "app.js persiste preferência de trava (pinned_open vs pinned_collapsed)");
$assertCustom(str_contains($js, 'sidebarEdgeTrigger'), "app.js conecta ouvintes mouseenter/mouseleave no gatilho de borda");
$assertCustom(str_contains($viewSidebar, 'title="Travar barra lateral recolhida"'), "sidebar.php botão da setinha configurado com título semântico de trava");

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
