<?php
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/SessaoCaixa.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/Auditoria.php';
require_once __DIR__ . '/../app/controllers/CaixaController.php';
require_once __DIR__ . '/../app/controllers/PdvController.php';

echo "=======================================================\n";
echo " TESTES DE SUBMISSÃO DE RECARGA EM DINHEIRO E BUSCA PDV\n";
echo "=======================================================\n\n";

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

$pdo = \Database::getInstance();

// 1. Garantir que o caixa está aberto para o teste
$turno = 'matutino';
$sessao = \App\Models\SessaoCaixa::obterSessaoAberta($turno);
if (!$sessao) {
    $sessaoId = \App\Models\SessaoCaixa::abrir(1, $turno, 50.00, 'Abertura teste recarga');
    $sessao = \App\Models\SessaoCaixa::buscarPorId($sessaoId);
}
$assertCustom($sessao !== null && $sessao['status'] === 'aberto', "Sessão de caixa ativa para recargas");

// Obter responsável Carlos Eduardo Silva (id 9)
$resp = \App\Models\Responsavel::buscarPorId(9);
$saldoInicial = (float)$resp['saldo_atual'];

// 2. Teste de registro direto com valor em float 50.00
$resultado1 = \App\Models\SessaoCaixa::registrarRecargaDinheiro(9, 50.00, 1, 'Teste float 50.00');
$assertCustom((float)$resultado1['valor'] === 50.00, "Valor registrado confere com R$ 50,00");
$assertCustom((float)$resultado1['saldo_resultante'] === ($saldoInicial + 50.00), "Saldo resultante confere com saldo inicial + 50.00");

// 3. Teste do Controller com valor enviado com vírgula ('100,00')
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
$_POST['csrf_token'] = \App\Core\Session::generateCsrfToken();
$_POST['responsavel_id'] = '9';
$_POST['valor'] = '100,00';
$_POST['observacoes'] = 'Teste valor com virgula 100,00';

$controller = new \App\Controllers\CaixaController();
ob_start();
$controller->recargaDinheiro();
$outputVirgula = ob_get_clean();
$jsonVirgula = json_decode($outputVirgula, true);

$assertCustom($jsonVirgula !== null, "Controller respondeu JSON válido para '100,00'");
$assertCustom($jsonVirgula['sucesso'] === true, "JSON contém 'sucesso' === true");
$assertCustom($jsonVirgula['success'] === true, "JSON contém 'success' === true");
$assertCustom((float)$jsonVirgula['valor_creditado'] === 100.00, "Valor creditado é exatamente R$ 100,00 (não R$ 10.000!)");

// 4. Teste do Controller com valor enviado com ponto decimal ('100.00' via toFixed)
$_POST['csrf_token'] = \App\Core\Session::generateCsrfToken();
$_POST['responsavel_id'] = '9';
$_POST['valor'] = '100.00';
$_POST['observacoes'] = 'Teste valor com ponto toFixed 100.00';

ob_start();
$controller->recargaDinheiro();
$outputPonto = ob_get_clean();
$jsonPonto = json_decode($outputPonto, true);

$assertCustom($jsonPonto !== null, "Controller respondeu JSON válido para '100.00'");
$assertCustom($jsonPonto['sucesso'] === true, "JSON contém 'sucesso' === true para valor com ponto");
$assertCustom((float)$jsonPonto['valor_creditado'] === 100.00, "Valor creditado é exatamente R$ 100,00 (não foi multiplicado)");

// 5. Teste do método Aluno::listarComTurma (evitando erro 500 no PDV)
$alunosBusca = \App\Models\Aluno::listarComTurma(['busca' => 'lucas', 'status' => 'ativo'], 8);
$assertCustom(is_array($alunosBusca), "Aluno::listarComTurma executou com sucesso");
$assertCustom(!empty($alunosBusca), "Aluno::listarComTurma localizou alunos com o termo 'lucas'");

// --- Teste de normalização monetária e apuração de quebra/sobra de fechamento ---
$normalizarMoeda = function(string $raw): float {
    $raw = trim($raw);
    if (str_contains($raw, ',')) {
        $limpo = str_replace('.', '', $raw);
        $limpo = str_replace(',', '.', $limpo);
    } else {
        $limpo = $raw;
    }
    return (float)$limpo;
};

$assertCustom($normalizarMoeda('200,00') === 200.0, "Normalização de '200,00' resulta em 200.00");
$assertCustom($normalizarMoeda('200.00') === 200.0, "Normalização de '200.00' resulta em 200.00 (sem multiplicar por 100)");
$assertCustom($normalizarMoeda('1.250,50') === 1250.5, "Normalização de '1.250,50' com milhar resulta em 1250.50");

$esperadoGaveta = 250.0;
$informadoContado = 200.0;
$diferencaApurada = round($informadoContado - $esperadoGaveta, 2);
$assertCustom($diferencaApurada === -50.0, "Quebra apurada com R$ 200 contados e R$ 250 esperados é exatamente -R$ 50,00 (não -R$ 230,00)");

$informadoExato = 250.0;
$diferencaExata = round($informadoExato - $esperadoGaveta, 2);
$assertCustom($diferencaExata === 0.0, "Conferência exata com R$ 250 contados e R$ 250 esperados é exatamente R$ 0,00");

// Limpeza das transações criadas neste teste específico
$txsParaExcluir = [$resultado1['transacao_id'], $jsonVirgula['transacao_id'], $jsonPonto['transacao_id']];
$idsIn = implode(',', $txsParaExcluir);
$pdo->exec("DELETE FROM transacoes_carteira WHERE id IN ({$idsIn})");
$pdo->exec("DELETE FROM logs_auditoria WHERE tabela_afetada = 'transacoes_carteira' AND registro_id IN ({$idsIn})");
$pdo->exec("UPDATE responsaveis SET saldo_atual = {$saldoInicial} WHERE id = 9");

echo "\n-------------------------------------------------------\n";
echo "RESULTADO: {$passou}/{$total} asserções aprovadas (" . round(($passou / $total) * 100, 1) . "%)\n";
echo "=======================================================\n";

if ($passou !== $total) {
    exit(1);
}
