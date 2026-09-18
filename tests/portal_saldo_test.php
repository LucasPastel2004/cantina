<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Validação de Exibição de Saldo no Portal da Família
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/RestricaoAlimentar.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';

function assertCondition(bool $cond, string $message, &$pass, &$fail): void {
    if ($cond) {
        echo "  [PASS] {$message}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$message}\n";
        $fail++;
    }
}

$pass = 0;
$fail = 0;

echo "======================================================================\n";
echo "  TESTE DE EXIBIÇÃO DE SALDO E NAVEGAÇÃO NO PORTAL DA FAMÍLIA        \n";
echo "======================================================================\n\n";

// 1. Localiza responsável com saldo para teste
$resp = \App\Models\Responsavel::fetchOne("SELECT * FROM responsaveis WHERE saldo_atual > 0 LIMIT 1");
if (!$resp) {
    // Garante um responsável com saldo para o teste
    $resp = \App\Models\Responsavel::fetchOne("SELECT * FROM responsaveis LIMIT 1");
    \App\Models\Responsavel::execute("UPDATE responsaveis SET saldo_atual = 75.50 WHERE id = :id", ['id' => $resp['id']]);
    $resp['saldo_atual'] = 75.50;
}

$saldoEsperado = number_format((float)$resp['saldo_atual'], 2, ',', '.');
echo "Responsável de teste: {$resp['nome']} (ID: {$resp['id']}, Saldo: R$ {$saldoEsperado})\n\n";

// 2. Simula login do responsável
\App\Core\Auth::loginResponsavel($resp);

assertCondition(\App\Core\Auth::check(), "Responsável autenticado com sucesso", $pass, $fail);
assertCondition((float)\App\Core\Session::get('_auth_saldo') === (float)$resp['saldo_atual'], "Saldo gravado na sessão confere com o banco", $pass, $fail);

// 3. Renderiza a tela de Alergias via PortalController e captura o HTML
$controller = new \App\Controllers\PortalController();

ob_start();
$controller->alergias();
$htmlAlergias = ob_get_clean();

assertCondition(!empty($htmlAlergias), "View /portal/alergias renderizada com sucesso", $pass, $fail);
assertCondition(str_contains($htmlAlergias, "R$ {$saldoEsperado}"), "HTML de /portal/alergias exibe o saldo correto (R$ {$saldoEsperado})", $pass, $fail);
assertCondition(!str_contains($htmlAlergias, "R$ 0,00") || $saldoEsperado === "0,00", "HTML de /portal/alergias NÃO exibe saldo zerado indevidamente", $pass, $fail);
assertCondition(str_contains($htmlAlergias, 'portal-nav-link active') && str_contains($htmlAlergias, 'Alergias & Restrições'), "Aba 'Alergias & Restrições' está devidamente marcada como active", $pass, $fail);

// 4. Renderiza a tela de Início via PortalController e captura o HTML
ob_start();
$controller->index();
$htmlInicio = ob_get_clean();

assertCondition(str_contains($htmlInicio, "R$ {$saldoEsperado}"), "HTML de /portal (Início) exibe o saldo correto (R$ {$saldoEsperado})", $pass, $fail);
assertCondition(str_contains($htmlInicio, 'portal-nav-link active') && str_contains($htmlInicio, 'Início'), "Aba 'Início' está devidamente marcada como active", $pass, $fail);

// 5. Teste de resiliência do layout portal.php com variável $saldoAtual omitida
$viewContentMock = "<p>Conteúdo de teste</p>";
$baseUrl = '';
$saldoAtual = null; // simulando view que não passou $saldoAtual

ob_start();
$content = $viewContentMock;
require __DIR__ . '/../app/views/layouts/portal.php';
$htmlFallback = ob_get_clean();

assertCondition(str_contains($htmlFallback, "R$ {$saldoEsperado}"), "Layout portal.php busca saldo automaticamente de Auth::user() em caso de omissão", $pass, $fail);

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: {$pass} passaram, {$fail} falharam.\n";
echo "----------------------------------------------------------------------\n";

if ($fail > 0) {
    exit(1);
}
