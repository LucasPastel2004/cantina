<?php
/**
 * Teste de Validação: Paginação Limpa e Anti-Fatiamento de Tabelas na Impressão A4
 */

$rootDir = dirname(__DIR__);

echo "====================================================================\n";
echo " TESTE: PAGINAÇÃO LIMPA & ANTI-FATIAMENTO DE TABELAS NA IMPRESSÃO\n";
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

$views = [
    'vendas_alimento_pdf' => $rootDir . '/app/views/relatorios/vendas_alimento_pdf.php',
    'arrecadacao_pdf'     => $rootDir . '/app/views/relatorios/arrecadacao_pdf.php',
];

foreach ($views as $nome => $caminho) {
    echo ">> Testando regras de impressão em $nome...\n";
    assertCheck("Arquivo $nome existe", file_exists($caminho));
    $conteudo = file_get_contents($caminho);

    // 1. @page com margens seguras (evita corte nas bordas físicas de 297mm)
    assertCheck("$nome possui @page com margens físicas definidas",
        strpos($conteudo, 'margin: 12mm 14mm 14mm 14mm;') !== false
    );

    // 2. @media print zera padding e min-height do container para usar as margens do @page
    assertCheck("$nome zera padding e libera min-height do container na impressão",
        strpos($conteudo, 'padding: 0 !important;') !== false &&
        strpos($conteudo, 'min-height: auto !important;') !== false
    );

    // 3. border-collapse: separate com border-spacing: 0 para evitar bug Chromium 814041 (fatiamento de tr)
    assertCheck("$nome usa border-collapse: separate com spacing 0 na impressão",
        strpos($conteudo, 'border-collapse: separate !important;') !== false &&
        strpos($conteudo, 'border-spacing: 0 !important;') !== false
    );

    // 4. thead com table-header-group para repetição no início de cada página
    assertCheck("$nome possui thead com table-header-group na impressão",
        strpos($conteudo, 'display: table-header-group !important;') !== false
    );

    // 5. tr com break-inside: avoid !important para evitar corte horizontal
    assertCheck("$nome possui tr com break-inside: avoid na impressão",
        strpos($conteudo, 'page-break-inside: avoid !important;') !== false &&
        strpos($conteudo, 'break-inside: avoid !important;') !== false
    );

    // 6. Botão de Baixar PDF direto presente na barra
    assertCheck("$nome possui botão direto para Baixar PDF oficial",
        strpos($conteudo, 'Baixar PDF') !== false
    );
}

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO: $passed / $assertions asserções aprovadas\n";

if ($passed === $assertions) {
    echo " >>> SUCESSO TOTAL: TODAS AS ASSERÇÕES PASSARAM! <<<\n";
    exit(0);
} else {
    echo " >>> ERRO: UMA OU MAIS ASSERÇÕES FALHARAM! <<<\n";
    exit(1);
}
