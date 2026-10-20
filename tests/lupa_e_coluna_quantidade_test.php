<?php
/**
 * Teste de Validação: Ícone de Lupa nas Barras de Impressão e Largura da Coluna Quantidade
 */

$rootDir = dirname(__DIR__);

echo "====================================================================\n";
echo " TESTE: ÍCONE DE LUPA & AJUSTE DA COLUNA QUANTIDADE EM VENDAS\n";
echo "====================================================================\n\n";

$assertions = 0;
$passed = 0;

function assertCheck($desc, $cond) {
    global $assertions, $passed;
    $assertions++;
    if ($cond) {
        echo "  [OK] $desc\n";
        $passed++;
    } else {
        echo "  [FALHA] $desc\n";
    }
}

// 1. Verificar contraste e presença do SVG de lupa nas views de impressão
echo ">> 1. Verificando contraste e ícone de lupa nas views de relatório...\n";

$views = [
    'vendas_alimento_pdf' => $rootDir . '/app/views/relatorios/vendas_alimento_pdf.php',
    'arrecadacao_pdf'     => $rootDir . '/app/views/relatorios/arrecadacao_pdf.php',
    'estoque_mov_pdf'     => $rootDir . '/app/views/relatorios/estoque_movimentacoes_pdf.php',
    'dre_pdf'             => $rootDir . '/app/views/financeiro/dre_pdf.php',
];

foreach ($views as $nome => $caminho) {
    assertCheck("Arquivo $nome existe", file_exists($caminho));
    $conteudo = file_get_contents($caminho);
    
    // Contém SVG com viewBox do ícone de lupa
    assertCheck("$nome possui SVG de lupa com viewBox=\"0 0 16 16\"", 
        strpos($conteudo, 'viewBox="0 0 16 16"') !== false && strpos($conteudo, 'M11.742 10.344') !== false
    );
    
    // Barra superior tem fundo escuro (#1e293b ou #0f172a) para alto contraste com lupa e texto branco
    $temFundoEscuro = (strpos($conteudo, '#1e293b') !== false || strpos($conteudo, '#0f172a') !== false);
    assertCheck("$nome possui barra superior com fundo escuro de alto contraste", $temFundoEscuro);
    
    // Controles de zoom presentes
    assertCheck("$nome possui botões de zoom (- / reset / +)",
        strpos($conteudo, 'btnZoomOut') !== false && 
        strpos($conteudo, 'btnZoomReset') !== false && 
        strpos($conteudo, 'btnZoomIn') !== false
    );
}

// 2. Verificar dimensionamento da coluna Quantidade em vendas_alimento_pdf.php
echo "\n>> 2. Verificando dimensionamento da coluna Quantidade...\n";

$vendasConteudo = file_get_contents($views['vendas_alimento_pdf']);

// $wQtd configurada com no mínimo 11% (12% no padrão)
preg_match('/\$wQtd\s*=\s*[^;]+;/', $vendasConteudo, $matchQtd);
assertCheck("Definição de \$wQtd encontrada no código", !empty($matchQtd));

assertCheck("\$wQtd expandida para garantir espaço para a palavra 'Quantidade'", 
    strpos($vendasConteudo, '$wQtd   = $temExtras ? 11 : 12;') !== false
);

// table.tabela-relatorio th possui contenção celular (white-space: nowrap e overflow: hidden)
assertCheck("table.tabela-relatorio th possui white-space: nowrap", 
    strpos($vendasConteudo, 'white-space: nowrap;') !== false
);
assertCheck("table.tabela-relatorio th possui overflow: hidden", 
    strpos($vendasConteudo, 'overflow: hidden;') !== false
);

// 3. Resultado final
echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO: $passed / $assertions asserções aprovadas\n";

if ($passed === $assertions) {
    echo " >>> SUCESSO TOTAL: TODAS AS ASSERÇÕES PASSARAM! <<<\n";
    exit(0);
} else {
    echo " >>> ERRO: UMA OU MAIS ASSERÇÕES FALHARAM! <<<\n";
    exit(1);
}
