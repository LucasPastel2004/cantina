<?php
/**
 * Teste de Validação das Views de Cardápio & Produtos e Categorias
 */

define('CANTINA_SISTEMA', true);

$baseUrl = '';
$produtos = [];
$categorias = [
    ['id' => 1, 'nome' => 'Salgados Assados', 'ordem' => 1, 'ativo' => 1, 'total_produtos' => 4],
    ['id' => 2, 'nome' => 'Sucos Naturais', 'ordem' => 2, 'ativo' => 1, 'total_produtos' => 2]
];
$alergenos = [];
$filtros = [];
$csrf_field = '<input type="hidden" name="csrf_token" value="abc">';
$csrf_token = 'abc';
$totalPaginas = 1;
$paginaAtual = 1;
$totalRegistros = 0;
$itensPorPagina = 20;

// Teste da view admin/produtos
ob_start();
include __DIR__ . '/../app/views/admin/produtos.php';
$htmlProdutos = ob_get_clean();

// Teste da view admin/categorias
ob_start();
include __DIR__ . '/../app/views/admin/categorias.php';
$htmlCategorias = ob_get_clean();

$erros = [];

// Em produtos: não deve ter botão 'Gerenciar Categorias'
if (strpos($htmlProdutos, 'Gerenciar Categorias') !== false) {
    $erros[] = 'Botão redundante "Gerenciar Categorias" ainda presente em produtos.php';
}
if (strpos($htmlProdutos, '+ Novo Produto') === false) {
    $erros[] = 'Botão "+ Novo Produto" ausente em produtos.php';
}

// Em categorias: não deve ter botão '&larr; Cardápio'
if (strpos($htmlCategorias, '&larr; Cardápio') !== false || strpos($htmlCategorias, '← Cardápio') !== false) {
    $erros[] = 'Botão redundante "← Cardápio" ainda presente em categorias.php';
}
if (strpos($htmlCategorias, '+ Nova Categoria') === false) {
    $erros[] = 'Botão "+ Nova Categoria" ausente em categorias.php';
}

if (!empty($erros)) {
    echo "FALHAS NAS VIEWS:\n";
    foreach ($erros as $erro) {
        echo " - [FAIL] $erro\n";
    }
    exit(1);
}

echo "=== TESTE DE VALIDAÇÃO DAS VIEWS DE CARDÁPIO E CATEGORIAS ===\n";
echo "[PASS] View admin/produtos renderizada perfeitamente sem o botão redundante 'Gerenciar Categorias'\n";
echo "[PASS] Botão '+ Novo Produto' preservado com integridade visual\n";
echo "[PASS] View admin/categorias renderizada perfeitamente sem o botão redundante '← Cardápio'\n";
echo "[PASS] Botão '+ Nova Categoria' preservado com integridade visual\n";
echo "STATUS: VIEWS 100% VALIDADAS E DESACOPLADAS COM SUCESSO!\n";
