<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Auto-Dismiss de Alertas (5 segundos)
 */

define('CANTINA_SISTEMA', true);

echo "=== TESTE DE AUTO-DISMISS DE ALERTAS E TRANSIÇÃO SUAVE ===" . PHP_EOL;

// 1. Verificar funções no JavaScript (assets/js/app.js)
$appJs = file_get_contents(__DIR__ . '/../assets/js/app.js');

$hasAutoDismissFunc = str_contains($appJs, 'function iniciarAutoDismissAlertas()');
$hasFecharAlertaFunc = str_contains($appJs, 'function fecharAlerta(');
$hasTimeout5000 = str_contains($appJs, '5000');
$hasPauseOnHover = str_contains($appJs, 'mouseenter') && str_contains($appJs, 'mouseleave');

echo "1.1 app.js contém iniciarAutoDismissAlertas(): " . ($hasAutoDismissFunc ? "PASS" : "FAIL") . PHP_EOL;
echo "1.2 app.js contém fecharAlerta(): " . ($hasFecharAlertaFunc ? "PASS" : "FAIL") . PHP_EOL;
echo "1.3 app.js define timeout padrão de 5000ms: " . ($hasTimeout5000 ? "PASS" : "FAIL") . PHP_EOL;
echo "1.4 app.js implementa pausa ao passar o mouse (pause on hover): " . ($hasPauseOnHover ? "PASS" : "FAIL") . PHP_EOL;

// 2. Verificar regras no CSS (assets/css/custom.css)
$customCss = file_get_contents(__DIR__ . '/../assets/css/custom.css');

$hasDismissingClass = str_contains($customCss, '.alert-enterprise.alert-dismissing');
$hasTransition = str_contains($customCss, 'transition:') && str_contains($customCss, 'opacity 0.35s');

echo "2.1 custom.css contém .alert-enterprise.alert-dismissing: " . ($hasDismissingClass ? "PASS" : "FAIL") . PHP_EOL;
echo "2.2 custom.css contém transição suave de fade out / slide up: " . ($hasTransition ? "PASS" : "FAIL") . PHP_EOL;

// 3. Verificar renderização do partial alert.php
$type = 'success';
$title = 'Sucesso';
$message = 'Turma [6º B] removida com sucesso (histórico preservado).';
$dismissible = true;

ob_start();
require __DIR__ . '/../app/views/partials/alert.php';
$alertHtml = ob_get_clean();

$hasFecharAlertaCall = str_contains($alertHtml, 'fecharAlerta');
$hasMsg = str_contains($alertHtml, 'Turma [6º B] removida com sucesso');

echo "3.1 alert.php renderiza chamada a fecharAlerta(): " . ($hasFecharAlertaCall ? "PASS" : "FAIL") . PHP_EOL;
echo "3.2 alert.php renderiza mensagem operacional corretamente: " . ($hasMsg ? "PASS" : "FAIL") . PHP_EOL;

$todosPassaram = $hasAutoDismissFunc && $hasFecharAlertaFunc && $hasTimeout5000 
              && $hasPauseOnHover && $hasDismissingClass && $hasTransition 
              && $hasFecharAlertaCall && $hasMsg;

if ($todosPassaram) {
    echo "STATUS: TODOS OS 8 CRITÉRIOS DE AUTO-DISMISS FORAM APROVADOS COM SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo "STATUS: FALHA EM UM OU MAIS CRITÉRIOS DE AUTO-DISMISS." . PHP_EOL;
    exit(1);
}
