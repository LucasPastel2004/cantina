<?php
/**
 * Teste de Validação: Sistema Corporativo de Alertas (Eliminação de alert() nativo)
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Controller.php';

echo "=== TESTE DE VALIDAÇÃO DO MODAL CORPORATIVO DE ALERTAS ===\n\n";

// 1. Validar que assets/js/app.js contém a implementação de getOrCreateAlertModal e window.alertaAcao
$js = file_get_contents(__DIR__ . '/../assets/js/app.js');
$temModal = strpos($js, 'getOrCreateAlertModal') !== false;
$temAlertaAcao = strpos($js, 'window.alertaAcao = function') !== false;
$temOverrideAlert = strpos($js, 'window.alert = function') !== false;
$temDataAlert = strpos($js, 'data-alert') !== false;

echo "1. JS - getOrCreateAlertModal: " . ($temModal ? "OK" : "FALHA") . "\n";
echo "2. JS - window.alertaAcao: " . ($temAlertaAcao ? "OK" : "FALHA") . "\n";
echo "3. JS - Override window.alert: " . ($temOverrideAlert ? "OK" : "FALHA") . "\n";
echo "4. JS - Interceptor data-alert: " . ($temDataAlert ? "OK" : "FALHA") . "\n";

// 2. Validar que app/views/relatorios/extrato_aluno.php não contém alert( nativo
$view = file_get_contents(__DIR__ . '/../app/views/relatorios/extrato_aluno.php');
$temAlertNativo = preg_match('/\balert\s*\(/', $view);
$temAlertaAcaoView = strpos($view, 'window.alertaAcao') !== false;

echo "5. View extrato_aluno - Sem alert() nativo: " . (!$temAlertNativo ? "OK" : "FALHA") . "\n";
echo "6. View extrato_aluno - Com window.alertaAcao: " . ($temAlertaAcaoView ? "OK" : "FALHA") . "\n";

// 3. Validar assetVersion >= 7.2.0 em Controller.php
$ctrl = file_get_contents(__DIR__ . '/../app/core/Controller.php');
$temVersion = preg_match("/'7\.2\.[0-9]+'/", $ctrl) === 1;
echo "7. Controller - assetVersion 7.2.x: " . ($temVersion ? "OK" : "FALHA") . "\n";

$sucesso = $temModal && $temAlertaAcao && $temOverrideAlert && $temDataAlert && !$temAlertNativo && $temAlertaAcaoView && $temVersion;
echo "\nResultado: " . ($sucesso ? "TODOS OS TESTES APROVADOS!" : "FALHAS ENCONTRADAS!") . "\n";
exit($sucesso ? 0 : 1);
