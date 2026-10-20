<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Refinamento de Simetria dos Filtros, Ampliação da Caixa do PDV e Ordem dos Ícones do Dock
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

echo "======================================================================\n";
echo " TESTES DE REFINAMENTO: SIMETRIA DOS FILTROS, PDV E ORDEM DO DOCK     \n";
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

// 1. Validação da Ordem dos Ícones no Dock (sidebar.php)
$sidebarContent = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
$posRelatorios = strpos($sidebarContent, 'data-category="relatorios"');
$posProdutos = strpos($sidebarContent, 'id="btnDirectProdutos"');

$assertCustom($posRelatorios !== false && $posProdutos !== false, "Dock possui botão de Relatórios e botão de Cardápio");
$assertCustom($posRelatorios < $posProdutos, "Ícone de Relatórios (posição 4) precede o de Cardápio & Preços (posição 6) para folga vertical");

// 2. Validação da Altura Dinâmica e Anti-Overflow do Flyout no app.js e custom.css
$appJs = file_get_contents(__DIR__ . '/../assets/js/app.js');
$customCss = file_get_contents(__DIR__ . '/../assets/css/custom.css');

$assertCustom(str_contains($appJs, 'realFlyoutHeight') && str_contains($appJs, 'targetFlyout.offsetHeight'), "app.js calcula dinamicamente a altura real do flyout");
$assertCustom(str_contains($appJs, 'window.innerHeight - realFlyoutHeight'), "app.js previne corte inferior do menu na barra de tarefas");
$assertCustom(str_contains($customCss, 'max-height: calc(100vh - 24px)'), "custom.css limita altura máxima do flyout com folga da viewport");
$assertCustom(str_contains($customCss, 'max-height: calc(100vh - 74px)') && str_contains($customCss, 'overflow-y: auto'), "custom.css garante scroll interno limpo em telas menores");

// 3. Validação da Simetria Perfeita em 3 Colunas dos Filtros de Vendas (vendas_filtros.php)
$filtrosView = file_get_contents(__DIR__ . '/../app/views/relatorios/vendas_filtros.php');

$assertCustom(substr_count($filtrosView, '<div class="col-12 col-md-4">') >= 15, "Formulário de filtros de vendas possui pelo menos 15 blocos simétricos 'col-12 col-md-4'");
$assertCustom(!str_contains($filtrosView, 'col-lg-2'), "Removida a linha desproporcional com 6 colunas (col-lg-2)");
$assertCustom(str_contains($filtrosView, 'Redefinir Padrões'), "Botão de Redefinir Padrões integrado de forma limpa");
$assertCustom(str_contains($filtrosView, 'id="obs_sim"') && str_contains($filtrosView, 'id="obs_nao"'), "Toggles de Observação presentes");
$assertCustom(str_contains($filtrosView, 'id="forma_sim"') && str_contains($filtrosView, 'id="forma_nao"'), "Toggles de Forma de Pagamento presentes");
$assertCustom(str_contains($filtrosView, 'id="estorno_sim"') && str_contains($filtrosView, 'id="estorno_nao"'), "Toggles de Estorno presentes");
$assertCustom(str_contains($filtrosView, 'id="alim_sim"') && str_contains($filtrosView, 'id="alim_nao"'), "Toggles de Alimentos presentes");
$assertCustom(str_contains($filtrosView, 'id="aluno_sim"') && str_contains($filtrosView, 'id="aluno_nao"'), "Toggles de Aluno presentes");

// 4. Validação da Ampliação do PDV (pdv.php)
$pdvView = file_get_contents(__DIR__ . '/../app/views/operacional/pdv.php');

$assertCustom(str_contains($pdvView, 'grid-template-columns: 1fr minmax(460px, 500px);'), "PDV amplia coluna do caixa para minmax(460px, 500px)");
$assertCustom(str_contains($pdvView, 'grid-template-columns: 1fr 520px;'), "PDV em telas >= 1440px amplia coluna do caixa para 520px");
$assertCustom(str_contains($pdvView, 'font-size: 26px;') && str_contains($pdvView, '.pdv-total-val'), "Valor total da venda com fonte ampliada para 26px");
$assertCustom(str_contains($pdvView, 'width: 28px;') && str_contains($pdvView, 'height: 28px;') && str_contains($pdvView, '.pdv-qty-btn'), "Botões de quantidade do carrinho ampliados para 28x28px");
$assertCustom(str_contains($pdvView, 'font-15 font-tabular font-bold text-success" id="displayTroco"'), "Troco sugerido ampliado com destaque visual");

echo "\n=======================================================\n";
echo " RESULTADO: {$passou} / {$total} testes passaram!\n";
echo "=======================================================\n";

if ($passou === $total) {
    echo "\n>>> TODOS OS TESTES DE REFINAMENTO PASSARAM COM 100%! <<<\n";
    exit(0);
} else {
    echo "\n>>> HOUVE FALHA NOS TESTES. <<<\n";
    exit(1);
}
