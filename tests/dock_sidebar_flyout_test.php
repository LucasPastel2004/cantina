<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Dock de Ícones e Menus em Suspensão (Estilo Escolar Manager)
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

echo "======================================================================\n";
echo " TESTES DO NOVO DOCK DE ÍCONES E MENUS EM SUSPENSÃO (FLYOUTS)        \n";
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

$css = file_get_contents(__DIR__ . '/../assets/css/custom.css');
$js = file_get_contents(__DIR__ . '/../assets/js/app.js');

// 1. Validação dos estilos CSS do Dock e Flyouts
$assertCustom(str_contains($css, '.dock-btn'), "custom.css define botões do dock (.dock-btn)");
$assertCustom(str_contains($css, '.sidebar-flyout'), "custom.css define painel flutuante (.sidebar-flyout)");
$assertCustom(str_contains($css, 'background-color: #ffffff !important;') && str_contains($css, '.dock-btn.active'), "Botão ativo do dock adota fundo branco destacado estilo Escolar Manager");
$assertCustom(str_contains($css, 'margin-left: 60px !important;'), "Área principal ajustada com margin-left: 60px liberando espaço máximo na tela");
$assertCustom(str_contains($css, 'z-index: 1060;') || str_contains($css, 'z-index: 1050;'), "Flyout possui camada de elevação superior (z-index)");

// 2. Validação das funções JavaScript no app.js
$assertCustom(str_contains($js, 'function toggleCategoryFlyout'), "app.js possui função toggleCategoryFlyout()");
$assertCustom(str_contains($js, 'function fecharFlyouts'), "app.js possui função fecharFlyouts()");
$assertCustom(str_contains($js, 'Escape') && str_contains($js, 'fecharFlyouts'), "app.js fecha flyouts ao pressionar a tecla Escape");

// 3. Renderização com perfil Administrador
$baseUrl = '';
$user = [
    'nome' => 'Gestor Administrador',
    'usuario' => 'admin',
    'perfil' => 'administrador',
    'role' => 'administrador'
];
$activeMenu = 'produtos';

ob_start();
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlAdmin = ob_get_clean();

$assertCustom(str_contains($htmlAdmin, 'data-category="operacional"'), "Dock contém botão da categoria Operacional");
$assertCustom(str_contains($htmlAdmin, 'data-category="estoque"'), "Dock contém botão da categoria Estoque");
$assertCustom(str_contains($htmlAdmin, 'data-category="financeiro"'), "Dock contém botão da categoria Financeiro (Saco de dinheiro)");
$assertCustom(str_contains($htmlAdmin, 'id="btnDirectProdutos"') && str_contains($htmlAdmin, 'href="/admin/produtos"'), "Cardápio & Preços possui ícone próprio com link direto para /admin/produtos");
$assertCustom(str_contains($htmlAdmin, 'data-category="admin"'), "Dock contém botão da categoria Administração");
$assertCustom(str_contains($htmlAdmin, 'data-category="relatorios"'), "Dock contém botão da categoria Relatórios");

// Validação dos conteúdos dos Flyouts
$assertCustom(str_contains($htmlAdmin, 'id="flyoutOperacional"'), "Flyout Operacional presente");
$assertCustom(str_contains($htmlAdmin, '/operacional/corte') && str_contains($htmlAdmin, '/operacional/pdv'), "Flyout Operacional contém Corte e PDV");
$assertCustom(str_contains($htmlAdmin, 'id="flyoutEstoque"'), "Flyout Estoque presente");
$assertCustom(str_contains($htmlAdmin, 'id="flyoutFinanceiro"'), "Flyout Financeiro presente");
$assertCustom(str_contains($htmlAdmin, '/financeiro/despesas') && str_contains($htmlAdmin, '/financeiro/dre'), "Flyout Financeiro contém Despesas e DRE");
$assertCustom(str_contains($htmlAdmin, 'id="flyoutFinanceiro"') && str_contains($htmlAdmin, '/operacional/estornos'), "Flyout Financeiro contém opção de Estorno de Vendas");
$assertCustom(str_contains($htmlAdmin, 'id="flyoutFinanceiro"') && str_contains($htmlAdmin, '/operacional/caixa'), "Flyout Financeiro contém opção de Caixa do Turno");
$assertCustom(str_contains($htmlAdmin, 'id="navSectionAdmin"') && str_contains($htmlAdmin, '/admin/turmas'), "Flyout Administração contém Turmas Escolares");
$assertCustom(str_contains($htmlAdmin, 'id="flyoutRelatorios"') && str_contains($htmlAdmin, '/relatorios/vendas-filtros'), "Flyout Relatórios contém Filtros de Vendas");

// 4. Renderização com perfil Operador (Sanitização e RBAC)
$user = [
    'nome' => 'Operadora Balcão',
    'usuario' => 'operador-maria',
    'perfil' => 'operador',
    'role' => 'operador'
];
$activeMenu = 'corte';

ob_start();
require __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlOperador = ob_get_clean();

$assertCustom(str_contains($htmlOperador, 'data-category="operacional"'), "Dock Operador contém Operacional");
$assertCustom(str_contains($htmlOperador, 'data-category="estoque"'), "Dock Operador contém Estoque");
$assertCustom(str_contains($htmlOperador, 'data-category="financeiro"'), "Dock Operador contém Financeiro");
$assertCustom(!str_contains($htmlOperador, 'id="btnDirectProdutos"'), "Dock Operador NÃO contém Cardápio & Preços");
$assertCustom(!str_contains($htmlOperador, 'data-category="admin"'), "Dock Operador NÃO contém Administração");
$assertCustom(!str_contains($htmlOperador, 'data-category="relatorios"'), "Dock Operador NÃO contém Relatórios");
$assertCustom(!str_contains($htmlOperador, 'id="navSectionAdmin"'), "Flyout Administração oculto para Operador");
$assertCustom(!str_contains($htmlOperador, 'id="flyoutRelatorios"'), "Flyout Relatórios oculto para Operador");

echo "\n=======================================================\n";
echo " RESULTADO: {$passou} / {$total} testes passaram!\n";
echo "=======================================================\n\n";

if ($passou === $total) {
    echo ">>> TODOS OS TESTES DO DOCK E FLYOUTS PASSARAM COM 100%! <<<\n";
    exit(0);
} else {
    echo ">>> ALGUNS TESTES FALHARAM! <<<\n";
    exit(1);
}
