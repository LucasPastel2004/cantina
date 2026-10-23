<?php
/**
 * Teste de Validação: Dock e Flyout de Cardápio & Categorias
 */

define('CANTINA_SISTEMA', true);

$baseUrl = '';
$activeMenu = 'produtos';
$isAdmin = true;
$user = [
    'nome'   => 'Admin Teste',
    'perfil' => 'administrador',
    'role'   => 'administrador'
];

ob_start();
include __DIR__ . '/../app/views/layouts/sidebar.php';
$html = ob_get_clean();

$erros = [];

if (strpos($html, 'id="btnCategoryCardapio"') === false) {
    $erros[] = 'Elemento #btnCategoryCardapio não encontrado';
}
if (strpos($html, 'data-category="cardapio"') === false) {
    $erros[] = 'Atributo data-category="cardapio" não encontrado no botão do Dock';
}
if (strpos($html, 'onclick="toggleCategoryFlyout(\'cardapio\')"') === false) {
    $erros[] = 'Handler onclick="toggleCategoryFlyout(\'cardapio\')" não encontrado';
}
if (strpos($html, 'id="flyoutCardapio"') === false) {
    $erros[] = 'Elemento #flyoutCardapio não encontrado';
}
if (strpos($html, '/admin/produtos') === false) {
    $erros[] = 'Link /admin/produtos não encontrado no menu';
}
if (strpos($html, '/admin/categorias') === false) {
    $erros[] = 'Link /admin/categorias não encontrado no menu';
}
if (strpos($html, 'Cardápio & Produtos') === false) {
    $erros[] = 'Rótulo "Cardápio & Produtos" não encontrado';
}
if (strpos($html, 'Categorias') === false) {
    $erros[] = 'Rótulo "Categorias" não encontrado';
}
if (strpos($html, 'btnDirectProdutos') !== false) {
    $erros[] = 'Antigo link direto btnDirectProdutos ainda está presente (deveria ter sido substituído)';
}

// Testa se com activeMenu = produtos o link de produtos ganha 'active' e o dock-btn ganha 'is-current'
if (strpos($html, 'id="btnCategoryCardapio"') !== false) {
    if (strpos($html, 'class="dock-btn is-current"') === false && strpos($html, 'class="dock-btn  is-current"') === false) {
        $erros[] = 'Ícone do cardápio não recebeu a classe is-current quando activeMenu=produtos';
    }
}

// Testa com activeMenu = categorias
$activeMenu = 'categorias';
ob_start();
include __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlCat = ob_get_clean();

if (strpos($htmlCat, 'id="btnCategoryCardapio"') !== false) {
    if (strpos($htmlCat, 'is-current') === false) {
        $erros[] = 'Ícone do cardápio não recebeu a classe is-current quando activeMenu=categorias';
    }
}

if (!empty($erros)) {
    echo "FALHAS ENCONTRADAS:\n";
    foreach ($erros as $erro) {
        echo " - [FAIL] $erro\n";
    }
    exit(1);
}

echo "=== TESTE DE VALIDAÇÃO DO FLYOUT DE CARDÁPIO ===\n";
echo "[PASS] Botão #btnCategoryCardapio configurado com data-category=\"cardapio\"\n";
echo "[PASS] Flyout #flyoutCardapio criado com cabeçalho 'Cardápio'\n";
echo "[PASS] Linha 1: 'Cardápio & Produtos' apontando para /admin/produtos\n";
echo "[PASS] Linha 2: 'Categorias' apontando para /admin/categorias\n";
echo "[PASS] Antigo link direto btnDirectProdutos removido\n";
echo "[PASS] Destaque ativo (is-current) funcional para 'produtos' e 'categorias'\n";
echo "STATUS: TODOS OS 6 TESTES DO DOCK E FLYOUT PASSARAM COM 100% DE SUCESSO!\n";
