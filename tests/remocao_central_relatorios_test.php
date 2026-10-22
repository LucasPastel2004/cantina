<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Validação da Remoção da Central de Relatórios e de seus Botões
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

echo "=======================================================\n";
echo "   TESTE: REMOÇÃO DA CENTRAL DE RELATÓRIOS E ATALHOS  \n";
echo "=======================================================\n\n";

$passou = 0;
$falhou = 0;

function assertTeste(bool $condicao, string $descricao): void {
    global $passou, $falhou;
    if ($condicao) {
        echo "  [OK] " . $descricao . "\n";
        $passou++;
    } else {
        echo "  [FALHA] " . $descricao . "\n";
        $falhou++;
    }
}

// 1. Validação da Sidebar (app/views/layouts/sidebar.php)
$sidebarContent = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
assertTeste(!str_contains($sidebarContent, 'Central de Relatórios'), "Sidebar NÃO contém o item 'Central de Relatórios'");
assertTeste(!str_contains($sidebarContent, 'href="<?php echo $baseUrl; ?>/relatorios"'), "Sidebar NÃO contém link para a rota genérica '/relatorios'");

// Validar que os 7 relatórios oficiais continuam presentes no menu flyout
assertTeste(str_contains($sidebarContent, '/relatorios/extrato-aluno'), "Sidebar contém link para Extrato do Aluno");
assertTeste(str_contains($sidebarContent, '/relatorios/vendas-filtros'), "Sidebar contém link para Vendas por Alimento");
assertTeste(str_contains($sidebarContent, '/relatorios/arrecadacao'), "Sidebar contém link para Arrecadação & Entradas");
assertTeste(str_contains($sidebarContent, '/relatorios/fechamento-caixa'), "Sidebar contém link para Fechamento de Caixa");
assertTeste(str_contains($sidebarContent, '/relatorios/devedores'), "Sidebar contém link para Histórico MED & Quitações");
assertTeste(str_contains($sidebarContent, '/relatorios/estoque-filtros'), "Sidebar contém link para Movimentações de Estoque");
assertTeste(str_contains($sidebarContent, '/relatorios/corte'), "Sidebar contém link para Corte do Turno");

// 2. Validação da View de Arrecadação (app/views/relatorios/arrecadacao.php)
$arrecadacaoContent = file_get_contents(__DIR__ . '/../app/views/relatorios/arrecadacao.php');
assertTeste(!str_contains($arrecadacaoContent, 'href="<?php echo $baseUrl; ?>/relatorios"'), "Arrecadação NÃO contém link para '/relatorios'");
assertTeste(!str_contains($arrecadacaoContent, 'Hub de Relatórios'), "Arrecadação NÃO contém menção a 'Hub de Relatórios'");
assertTeste(!str_contains($arrecadacaoContent, '← Relatórios'), "Arrecadação NÃO contém botão '← Relatórios'");

// 3. Validação da View de Filtros de Estoque (app/views/relatorios/estoque_filtros.php)
$estoqueFiltrosContent = file_get_contents(__DIR__ . '/../app/views/relatorios/estoque_filtros.php');
assertTeste(!str_contains($estoqueFiltrosContent, 'href="<?php echo $baseUrl; ?>/relatorios"'), "Filtros de Estoque NÃO contém link para '/relatorios'");
assertTeste(!str_contains($estoqueFiltrosContent, 'Hub de Relatórios'), "Filtros de Estoque NÃO contém botão 'Hub de Relatórios'");
assertTeste(!str_contains($estoqueFiltrosContent, 'Voltar aos Relatórios'), "Filtros de Estoque NÃO contém botão 'Voltar aos Relatórios'");

// 4. Validação da View de Baixas de Mercadorias (app/views/relatorios/baixas_mercadorias.php)
$baixasContent = file_get_contents(__DIR__ . '/../app/views/relatorios/baixas_mercadorias.php');
assertTeste(!str_contains($baixasContent, 'Central de Relatórios'), "Baixas de Mercadorias NÃO contém link 'Central de Relatórios'");
assertTeste(!str_contains($baixasContent, 'href="<?php echo $baseUrl; ?>/relatorios"'), "Baixas de Mercadorias NÃO contém link para '/relatorios'");

// 5. Validação da View PDF de Baixas (app/views/relatorios/baixas_mercadorias_pdf.php)
$baixasPdfContent = file_get_contents(__DIR__ . '/../app/views/relatorios/baixas_mercadorias_pdf.php');
assertTeste(!str_contains($baixasPdfContent, "window.location.href = '<?php echo \$baseUrl; ?>/relatorios';"), "Baixas PDF não faz fallback para '/relatorios'");
assertTeste(str_contains($baixasPdfContent, "/relatorios/baixas-mercadorias"), "Baixas PDF faz fallback para o relatório específico");

// 6. Validação do Controller RelatoriosController
$relatoriosControllerContent = file_get_contents(__DIR__ . '/../app/controllers/RelatoriosController.php');
assertTeste(!str_contains($relatoriosControllerContent, "\$this->render('relatorios/index'"), "RelatoriosController::index NÃO renderiza a view 'relatorios/index'");
assertTeste(str_contains($relatoriosControllerContent, "\$this->redirect('/admin');"), "RelatoriosController::index redireciona para '/admin'");

// 7. Validação do Controller AdminController
$adminControllerContent = file_get_contents(__DIR__ . '/../app/controllers/AdminController.php');
assertTeste(!str_contains($adminControllerContent, "\$this->render('admin/relatorios'"), "AdminController::relatorios NÃO renderiza a view 'admin/relatorios'");

echo "\n-------------------------------------------------------\n";
echo "Resumo: {$passou} aprovados, {$falhou} falhas.\n";
echo "=======================================================\n";

if ($falhou > 0) {
    exit(1);
}
