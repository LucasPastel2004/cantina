<?php
/**
 * Teste de Validação de Sintaxe de Exportação CSV para Microsoft Excel
 * Verifica se nenhuma célula gera erro de fórmula (#NAME?, #VALUE! ou popup de sintaxe do Excel)
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';
require_once __DIR__ . '/../app/controllers/DreController.php';

echo "=== INICIANDO TESTE DE SINTAXE CSV EXCEL ===\n";

$rc = new \ReflectionClass(\App\Controllers\RelatoriosController::class);
$method = $rc->getMethod('sanitizarCsvCelula');
$method->setAccessible(true);

$testCases = [
    '(+) RECEITA BRUTA' => '[+] RECEITA BRUTA',
    '(-) DEDUCOES' => '[-] DEDUCOES',
    '(=) RECEITA LIQUIDA' => '[=] RECEITA LIQUIDA',
    '+150,00' => '150,00',
    '+7.602,50' => '7.602,50',
    '+50 un' => '50 un',
    '-50,00' => '-50,00',
    '-1.234,56' => '-1.234,56',
    '=SOMA(A1:A2)' => ' =SOMA(A1:A2)',
    '- Produto Teste' => '– Produto Teste',
];

$erros = 0;
foreach ($testCases as $entrada => $esperado) {
    $saida = $method->invoke(null, $entrada);
    if ($saida !== $esperado) {
        echo " [FAIL] Entrada '{$entrada}' gerou '{$saida}' (Esperado: '{$esperado}')\n";
        $erros++;
    } else {
        echo " [PASS] '{$entrada}' => '{$saida}'\n";
    }
}

if ($erros === 0) {
    echo "\n>>> TODOS OS TESTES DE SINTAXE CSV EXCEL PASSARAM COM SUCESSO! <<<\n";
    exit(0);
} else {
    echo "\n>>> FALHAS ENCONTRADAS: {$erros} <<<\n";
    exit(1);
}
