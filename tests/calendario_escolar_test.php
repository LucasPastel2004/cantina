<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Testes Unitários e de Integração: Gestão de Calendário Escolar
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

\App\Core\Session::start();

echo "======================================================================\n";
echo " INICIANDO SUÍTE DE TESTES: GESTÃO DO CALENDÁRIO ESCOLAR\n";
echo "======================================================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCondition(bool $cond, string $msg, &$testsPassed, &$testsFailed): void
{
    if ($cond) {
        echo "  [PASS] {$msg}\n";
        $testsPassed++;
    } else {
        echo "  [FAIL] {$msg}\n";
        $testsFailed++;
    }
}

use App\Models\CalendarioEscolar;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\TransacaoCarteira;

// --- 1. Teste de Navegação de Semanas ---
echo "--- 1. Navegação e Obtenção de Semanas Letivas ---\n";
$diasAtual = CalendarioEscolar::obterDiasSemanaLetiva('atual');
assertCondition(count($diasAtual) === 5, 'Retornou exatamente 5 dias úteis (Segunda a Sexta)', $testsPassed, $testsFailed);
assertCondition($diasAtual[0]['dia_semana'] === 1, 'Primeiro dia é Segunda-feira', $testsPassed, $testsFailed);
assertCondition($diasAtual[4]['dia_semana'] === 5, 'Último dia é Sexta-feira', $testsPassed, $testsFailed);

// Navegação por dataBase específica (ex: 2026-10-14, uma quarta-feira)
$diasOutubro = CalendarioEscolar::obterDiasSemanaLetiva('atual', null, '2026-10-14');
assertCondition($diasOutubro[0]['data'] === '2026-10-12', 'Semana de 14/10 inicia na segunda 12/10/2026', $testsPassed, $testsFailed);
assertCondition($diasOutubro[4]['data'] === '2026-10-16', 'Semana de 14/10 encerra na sexta 16/10/2026', $testsPassed, $testsFailed);

// --- 2. Teste de Cadastro de Feriado ---
echo "\n--- 2. Cadastro e Persistência de Feriado ---\n";
$dataFeriado = '2026-10-12'; // Dia de N. Sra. Aparecida
$resFeriado = CalendarioEscolar::salvarExcecao($dataFeriado, CalendarioEscolar::TIPO_FERIADO, 'Nossa Senhora Aparecida / Dia das Crianças', 1);
assertCondition($resFeriado['sucesso'] === true, 'Feriado salvo com sucesso', $testsPassed, $testsFailed);
assertCondition(!CalendarioEscolar::isDiaLetivo($dataFeriado), 'Data 12/10/2026 não é mais dia letivo (Feriado)', $testsPassed, $testsFailed);

$infoFeriado = CalendarioEscolar::obterInfoData($dataFeriado);
assertCondition($infoFeriado['tipo'] === 'feriado', 'Tipo identificado como feriado', $testsPassed, $testsFailed);
assertCondition(str_contains($infoFeriado['descricao'], 'Aparecida'), 'Descrição do feriado recuperada com sucesso', $testsPassed, $testsFailed);

// --- 3. Teste de Cadastro de Recesso Escolar ---
echo "\n--- 3. Cadastro e Persistência de Recesso Escolar ---\n";
$dataRecesso = '2026-10-13'; // Emenda
$resRecesso = CalendarioEscolar::salvarExcecao($dataRecesso, CalendarioEscolar::TIPO_RECESSO, 'Emenda de Feriado Escolar', 1);
assertCondition($resRecesso['sucesso'] === true, 'Recesso salvo com sucesso', $testsPassed, $testsFailed);
assertCondition(!CalendarioEscolar::isDiaLetivo($dataRecesso), 'Data 13/10/2026 não é dia letivo (Recesso)', $testsPassed, $testsFailed);

// --- 4. Listagem e Métricas Anuais ---
echo "\n--- 4. Listagem de Exceções e Métricas ---\n";
$excecoes = CalendarioEscolar::listarExcecoes(2026);
assertCondition(count($excecoes) >= 2, 'Listou ao menos 2 exceções para o ano de 2026', $testsPassed, $testsFailed);

$metricas = CalendarioEscolar::contarMetricasAno(2026);
assertCondition($metricas['feriados'] >= 1, 'Métrica de feriados contabilizou ao menos 1', $testsPassed, $testsFailed);
assertCondition($metricas['recessos'] >= 1, 'Métrica de recessos contabilizou ao menos 1', $testsPassed, $testsFailed);

// --- 5. Remoção de Exceção e Retorno a Dia Letivo Regular ---
echo "\n--- 5. Remoção de Exceção e Restauração de Dia Letivo ---\n";
$removido = CalendarioEscolar::removerExcecao($dataRecesso);
assertCondition($removido === true, 'Exceção removida com sucesso', $testsPassed, $testsFailed);
assertCondition(CalendarioEscolar::isDiaLetivo($dataRecesso), 'Data 13/10/2026 voltou a ser dia letivo normal de segunda a sexta', $testsPassed, $testsFailed);

// --- 6. Teste de Suspensão Emergencial em Lote com Estorno Automático ---
echo "\n--- 6. Suspensão Emergencial em Lote com Estorno Automático ---\n";
$dataEmergencial = '2026-11-25'; // Quarta-feira letiva
// Limpa eventual resíduo
CalendarioEscolar::removerExcecao($dataEmergencial);

// Cria responsável e aluno com saldo para simulação de pedido
$cpfResp = '88877766655';
$respData = Responsavel::localizarOuCriarPorCpf([
    'cpf' => $cpfResp,
    'nome' => 'Família Teste Emergencial',
    'whatsapp' => '11999998888',
    'email' => 'emerg@teste.com'
]);
$respId = (int)$respData['id'];
TransacaoCarteira::creditar($respId, 50.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Recarga para teste emergencial');
$saldoAntes = Responsavel::consultarSaldo($respId);

$aluno = Aluno::buscarPorMatricula('MAT-EMERG-01');
if (!$aluno) {
    Aluno::criar([
        'responsavel_id' => $respId,
        'turma_id'       => 1,
        'matricula'      => 'MAT-EMERG-01',
        'nome'           => 'Aluno Teste Emergencial',
        'turno'          => 'matutino',
        'ativo'          => 1
    ]);
    $aluno = Aluno::buscarPorMatricula('MAT-EMERG-01');
}

// Cria um produto para o pedido
$produtos = Produto::listarParaCardapio('matutino');
$prodId = $produtos[0]['id'];
$precoUnit = (float)$produtos[0]['preco_venda'];

// Cria pedido para a data emergencial
$pedidoCriado = Pedido::criarPedido(
    responsavelId: $respId,
    alunoId: $aluno['id'],
    dataConsumo: $dataEmergencial,
    itens: [
        ['produto_id' => $prodId, 'quantidade' => 2]
    ],
    agora: new \DateTimeImmutable("2026-11-23 07:00:00")
);

assertCondition(!empty($pedidoCriado['id']), 'Pedido agendado criado com sucesso para ' . $dataEmergencial, $testsPassed, $testsFailed);
$pedidoId = (int)$pedidoCriado['id'];
$valorPedido = (float)$pedidoCriado['valor_total'];

$saldoAposPedido = Responsavel::consultarSaldo($respId);
assertCondition(round($saldoAposPedido, 2) === round($saldoAntes - $valorPedido, 2), 'Saldo da carteira debitado corretamente pelo pedido', $testsPassed, $testsFailed);

// Executa a suspensão emergencial
$resEmergencial = CalendarioEscolar::cancelamentoEmergencial(
    $dataEmergencial,
    'Falta de energia e alagamento na rua da escola',
    1,
    '127.0.0.1'
);

assertCondition($resEmergencial['sucesso'] === true, 'Rotina de cancelamento emergencial executada com sucesso', $testsPassed, $testsFailed);
assertCondition($resEmergencial['pedidos_cancelados'] >= 1, 'Ao menos 1 pedido cancelado na data', $testsPassed, $testsFailed);
assertCondition($resEmergencial['total_estornado'] >= $valorPedido, 'Valor total estornado confere', $testsPassed, $testsFailed);

// Valida que o pedido e tickets foram cancelados
$pedidoCheck = Pedido::buscarPorId($pedidoId);
assertCondition($pedidoCheck['status'] === 'cancelado', 'Status do pedido alterado para "cancelado"', $testsPassed, $testsFailed);

// Valida estorno integral de 100% no saldo do responsável
$saldoAposEstorno = Responsavel::consultarSaldo($respId);
assertCondition(round($saldoAposEstorno, 2) === round($saldoAntes, 2), 'Saldo familiar 100% estornado e restaurado na carteira (R$ ' . $saldoAposEstorno . ')', $testsPassed, $testsFailed);

// Valida que a data agora é identificada como cancelado_emergencial
assertCondition(!CalendarioEscolar::isDiaLetivo($dataEmergencial), 'Data ' . $dataEmergencial . ' agora é não letiva', $testsPassed, $testsFailed);
$infoEmerg = CalendarioEscolar::obterInfoData($dataEmergencial);
assertCondition($infoEmerg['tipo'] === CalendarioEscolar::TIPO_CANCELADO_EMERGENCIAL, 'Tipo gravado como cancelado_emergencial', $testsPassed, $testsFailed);

// Limpeza de testes
CalendarioEscolar::removerExcecao($dataFeriado);
CalendarioEscolar::removerExcecao($dataEmergencial);
$pdoTest = \App\Core\Model::getPdo();
$pdoTest->exec("DELETE FROM tickets WHERE pedido_id = {$pedidoId}");
$pdoTest->exec("DELETE FROM itens_pedido WHERE pedido_id = {$pedidoId}");
$pdoTest->exec("DELETE FROM pedidos WHERE id = {$pedidoId}");
$pdoTest->exec("DELETE FROM transacoes_carteira WHERE responsavel_id = {$respId}");
$pdoTest->exec("DELETE FROM alunos WHERE id = {$aluno['id']}");
$pdoTest->exec("DELETE FROM responsaveis WHERE id = {$respId}");

// --- 7. Teste de Renderização da View de Calendário ---
echo "\n--- 7. Renderização da View admin/calendario.php ---\n";
ob_start();
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/admin/calendario';
\App\Core\Session::set('_auth_logged', true);
\App\Core\Session::set('_auth_type', 'usuario');
\App\Core\Session::set('_auth_id', 1);
\App\Core\Session::set('_auth_username', 'admin');
\App\Core\Session::set('_auth_nome', 'Administrador Geral');
\App\Core\Session::set('_auth_role', 'administrador');

$adminCtrl = new \App\Controllers\AdminController();
$adminCtrl->calendario();
$htmlCal = ob_get_clean();

assertCondition(str_contains($htmlCal, 'Calendário Escolar & Dias Letivos'), 'View contém título institucional completo', $testsPassed, $testsFailed);
assertCondition(str_contains($htmlCal, '+ Novo Feriado / Recesso'), 'View contém botão de novo feriado/recesso', $testsPassed, $testsFailed);
assertCondition(str_contains($htmlCal, 'Suspensão Emergencial'), 'View contém botão de suspensão emergencial', $testsPassed, $testsFailed);
assertCondition(str_contains($htmlCal, 'modalExcecao'), 'View contém modal de cadastro de exceção', $testsPassed, $testsFailed);
assertCondition(str_contains($htmlCal, 'modalEmergencial'), 'View contém modal de suspensão emergencial', $testsPassed, $testsFailed);
assertCondition(str_contains($htmlCal, 'semanaPicker'), 'View contém seletor rápido de semanas', $testsPassed, $testsFailed);

echo "\n======================================================================\n";
echo " RESUMO: {$testsPassed} passaram, {$testsFailed} falharam.\n";
echo "======================================================================\n";
exit($testsFailed > 0 ? 1 : 0);
