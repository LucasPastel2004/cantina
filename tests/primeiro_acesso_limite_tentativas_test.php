<?php
/**
 * Teste Específico: Primeiro Acesso — Limite de Tentativas, Cooldown e Validação de Código
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Responsavel;
use App\Controllers\AuthController;

echo "=== TESTE: PRIMEIRO ACESSO - LIMITE DE 5 TENTATIVAS E COOLDOWN ===" . PHP_EOL;

Session::start();

// Garante token CSRF
$csrfToken = Session::generateCsrfToken();

// 1. Simula solicitação inicial de primeiro acesso para um CPF pendente de teste
$cpfTeste = '555.666.777-88';
$cleanCpf = '55566677788';

// Garante que o responsável está pendente de primeiro acesso no banco
$db = \Database::getInstance();
$db->query("UPDATE responsaveis SET senha_hash = '', termos_aceitos_em = NULL WHERE cpf = '{$cleanCpf}'");

$_POST = [
    'csrf_token' => $csrfToken,
    'cpf'        => $cpfTeste
];

$authController = new AuthController();

// Limpa sessão de primeiro acesso
Session::remove('_pa_responsavel_id');
Session::remove('_pa_codigo');
Session::remove('_pa_expira_em');
Session::remove('_pa_tentativas');
Session::remove('_pa_ultimo_envio');

// Executa solicitação inicial
ob_start();
$authController->primeiroAcessoSolicitar();
ob_end_clean();

$codigoInicial = Session::get('_pa_codigo');
$assert1 = !empty($codigoInicial) && strlen($codigoInicial) === 6;
echo "1. Geração de código inicial: " . ($assert1 ? "PASS ($codigoInicial)" : "FAIL") . PHP_EOL;

// 2. Simula 5 tentativas erradas consecutivas
for ($i = 1; $i <= 5; $i++) {
    $_POST = [
        'csrf_token' => $csrfToken,
        'codigo'     => '000000' // Código sabidamente errado
    ];
    ob_start();
    $authController->primeiroAcessoValidarCodigo();
    ob_end_clean();
}

// Após 5 tentativas, o código é invalidado mas o usuário permanece na sessão com código bloqueado
$assert2 = !Session::has('_pa_codigo') 
        && Session::get('_pa_codigo_bloqueado') === true 
        && Session::has('_pa_responsavel_id');
echo "2. Código invalidado e marcado como bloqueado (mantendo sessão ativa): " . ($assert2 ? "PASS" : "FAIL") . PHP_EOL;

// 3. Renderização da tela de confirmação exibe o estado de código bloqueado
ob_start();
$authController->primeiroAcessoCodigo();
$htmlCodigo = ob_get_clean();
$assert3 = str_contains($htmlCodigo, 'Código Bloqueado') 
        && str_contains($htmlCodigo, 'Solicitar Novo Código por WhatsApp');
echo "3. Tela de confirmação exibe botão de solicitar novo código sem chutar para login: " . ($assert3 ? "PASS" : "FAIL") . PHP_EOL;

// 4. Tentativa de validar código enquanto bloqueado é rejeitada
$_POST = [
    'csrf_token' => $csrfToken,
    'codigo'     => '123456'
];
ob_start();
$authController->primeiroAcessoValidarCodigo();
ob_end_clean();

$flashesBloqueio = Session::getFlash('danger');
$assert4 = !empty($flashesBloqueio) 
        && str_contains(implode(' ', (array)$flashesBloqueio), 'cancelado');
echo "4. Tentativa de envio com código bloqueado é rejeitada: " . ($assert4 ? "PASS" : "FAIL") . PHP_EOL;

// 5. Reenvio direto do novo código pelo botão na tela de confirmação (simulando 61s após o envio anterior)
Session::set('_pa_ultimo_envio', time() - 65);
$_POST = [
    'csrf_token' => $csrfToken
];
ob_start();
$authController->primeiroAcessoReenviar();
ob_end_clean();

$novoCodigo = Session::get('_pa_codigo');
$bloqueadoAposReenvio = Session::get('_pa_codigo_bloqueado', false);
$assert5 = !empty($novoCodigo) && strlen($novoCodigo) === 6 && !$bloqueadoAposReenvio && $novoCodigo !== $codigoInicial;
echo "5. Reenvio gera novo código limpo e desbloqueia o formulário: " . ($assert5 ? "PASS ($novoCodigo)" : "FAIL") . PHP_EOL;

// 6. Validar o novo código recém-gerado com sucesso
$_POST = [
    'csrf_token' => $csrfToken,
    'codigo'     => $novoCodigo
];
ob_start();
$authController->primeiroAcessoValidarCodigo();
ob_end_clean();

$assert6 = Session::get('_pa_verificado') === true;
echo "6. Validação do novo código com sucesso: " . ($assert6 ? "PASS" : "FAIL") . PHP_EOL;

// Teardown: limpa sessão
Session::remove('_pa_responsavel_id');
Session::remove('_pa_codigo');
Session::remove('_pa_expira_em');
Session::remove('_pa_tentativas');
Session::remove('_pa_ultimo_envio');
Session::remove('_pa_codigo_bloqueado');
Session::remove('_pa_verificado');

if ($assert1 && $assert2 && $assert3 && $assert4 && $assert5 && $assert6) {
    echo PHP_EOL . "STATUS: TODAS AS 6 ASSERÇÕES PASSARAM COM 100% DE SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "STATUS: FALHA NO TESTE!" . PHP_EOL;
    exit(1);
}
