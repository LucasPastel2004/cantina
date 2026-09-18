<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Máscaras e Formatação Automática (CNPJ, Telefone e CPF)
 */

echo "=== TESTE DE MÁSCARAS E FORMATAÇÃO AUTOMÁTICA ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCond(bool $cond, string $msg, &$testsPassed, &$testsFailed): void
{
    if ($cond) {
        echo "  [PASS] {$msg}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$msg}\n";
        $testsFailed++;
    }
}

// 1. Teste da lógica PHP de formatação de CNPJ
function formatCnpjPHP(string $cnpj): string
{
    $clean = preg_replace('/\D/', '', $cnpj);
    if (strlen($clean) === 14) {
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $clean);
    }
    return $cnpj;
}

$cnpjExemplo = '12234343453534';
$cnpjFormatado = formatCnpjPHP($cnpjExemplo);
assertCond($cnpjFormatado === '12.234.343/4535-34', "CNPJ formatado: {$cnpjFormatado} === 12.234.343/4535-34", $testsPassed, $testsFailed);

// 2. Teste da lógica PHP de formatação de Celular (11 dígitos)
function formatTelefonePHP(string $tel): string
{
    $clean = preg_replace('/\D/', '', $tel);
    if (strlen($clean) === 11) {
        return preg_replace('/^(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $clean);
    } elseif (strlen($clean) === 10) {
        return preg_replace('/^(\d{2})(\d{4})(\d{4})$/', '($1) $2-$3', $clean);
    }
    return $tel;
}

$telCelular = '62993392113';
$telFormatado = formatTelefonePHP($telCelular);
assertCond($telFormatado === '(62) 99339-2113', "Telefone Celular formatado: {$telFormatado} === (62) 99339-2113", $testsPassed, $testsFailed);

$telFixo = '6232221100';
$fixoFormatado = formatTelefonePHP($telFixo);
assertCond($fixoFormatado === '(62) 3222-1100', "Telefone Fixo formatado: {$fixoFormatado} === (62) 3222-1100", $testsPassed, $testsFailed);

// 3. Verifica se app.js contém as funções de máscara
$jsContent = file_get_contents(__DIR__ . '/../assets/js/app.js');
assertCond(str_contains($jsContent, 'function formatarCnpj'), 'app.js contém formatarCnpj()', $testsPassed, $testsFailed);
assertCond(str_contains($jsContent, 'function formatarTelefone'), 'app.js contém formatarTelefone()', $testsPassed, $testsFailed);
assertCond(str_contains($jsContent, 'function formatarCpf'), 'app.js contém formatarCpf()', $testsPassed, $testsFailed);
assertCond(str_contains($jsContent, 'cnpjInputs.forEach'), 'app.js vincula inputs de CNPJ', $testsPassed, $testsFailed);
assertCond(str_contains($jsContent, 'phoneInputs.forEach'), 'app.js vincula inputs de Telefone', $testsPassed, $testsFailed);

// 4. Verifica se a view configuracoes.php possui classes e scripts de máscara
$viewContent = file_get_contents(__DIR__ . '/../app/views/admin/configuracoes.php');
assertCond(str_contains($viewContent, 'input-cnpj'), 'configuracoes.php possui classe input-cnpj', $testsPassed, $testsFailed);
assertCond(str_contains($viewContent, 'input-phone'), 'configuracoes.php possui classe input-phone', $testsPassed, $testsFailed);
assertCond(str_contains($viewContent, '00.000.000/0000-00'), 'configuracoes.php possui placeholder de CNPJ', $testsPassed, $testsFailed);
assertCond(str_contains($viewContent, '(00) 00000-0000'), 'configuracoes.php possui placeholder de WhatsApp', $testsPassed, $testsFailed);
assertCond(str_contains($viewContent, 'aplicarMascaraCnpj'), 'configuracoes.php possui script defensivo de CNPJ', $testsPassed, $testsFailed);

// 5. Verifica versão atualizada do app.js no layout admin
$adminLayout = file_get_contents(__DIR__ . '/../app/views/layouts/admin.php');
assertCond(preg_match('/app\.js\?v=5\.0\.[6-9]/', $adminLayout) === 1, 'admin.php possui app.js com cache busting >= 5.0.6', $testsPassed, $testsFailed);

echo "\nRESUMO: {$testsPassed} testes passaram, {$testsFailed} falharam.\n";
exit($testsFailed > 0 ? 1 : 0);
