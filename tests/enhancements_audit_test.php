<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Melhorias e Auditoria ECC (Enhancements & Hardening Test)
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_RUN', true);

echo "=======================================================\n";
echo "   TESTE DE MELHORIAS: AUDITORIA ECC & HARDENING\n";
echo "=======================================================\n\n";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/ErrorHandler.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';

use App\Core\Model;
use App\Core\ErrorHandler;
use App\Models\Responsavel;
use App\Models\TransacaoCarteira;

$pdo = Model::getPdo();
$passados = 0;
$total = 0;

function assertCheck(bool $condition, string $msg, &$passados, &$total): void {
    $total++;
    if ($condition) {
        echo "  [OK] $msg\n";
        $passados++;
    } else {
        echo "  [FALHA] $msg\n";
    }
}

// 1. Testando ErrorHandler::tryLogToDatabase
echo "1. Testando persistência em logs_erros via ErrorHandler...\n";
$stmtCountBefore = $pdo->query("SELECT COUNT(*) FROM logs_erros");
$countBefore = (int)$stmtCountBefore->fetchColumn();

// Simula captura de exceção
$testCode = 'TEST-' . bin2hex(random_bytes(4));
$testEx = new \RuntimeException("Teste de auditoria ErrorHandler", 500);

// Invocação via reflexão pois o método é privado
$refMethod = new \ReflectionMethod(ErrorHandler::class, 'tryLogToDatabase');
$refMethod->setAccessible(true);
$refMethod->invoke(null, $testCode, 'ERROR', $testEx);

$stmtCountAfter = $pdo->query("SELECT COUNT(*) FROM logs_erros");
$countAfter = (int)$stmtCountAfter->fetchColumn();
assertCheck($countAfter === $countBefore + 1, "Registro inserido com sucesso na tabela logs_erros", $passados, $total);

$stmtLast = $pdo->prepare("SELECT * FROM logs_erros WHERE mensagem LIKE :code LIMIT 1");
$stmtLast->execute([':code' => "%{$testCode}%"]);
$logGravado = $stmtLast->fetch(\PDO::FETCH_ASSOC);

assertCheck(!empty($logGravado), "Log localizado no banco pelo código do erro", $passados, $total);
assertCheck($logGravado['nivel'] === 'error', "Nível do erro registrado como 'error'", $passados, $total);
assertCheck(str_contains($logGravado['mensagem'], $testCode), "Mensagem contém o identificador único", $passados, $total);
assertCheck(!empty($logGravado['arquivo']) && !empty($logGravado['linha']), "Arquivo e linha registrados corretamente", $passados, $total);

// 2. Testando Desbloqueio Automático por Recarga Pix/Dinheiro em Conta Negativada MED
echo "\n2. Testando Desbloqueio Automático em TransacaoCarteira::creditar para Conta com MED...\n";

// Cria responsável negativado por MED
$cpfTeste = '888' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
$stmtResp = $pdo->prepare("
    INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, bloqueado_med, termos_aceitos_em, created_at)
    VALUES (:cpf, 'Responsavel Teste MED', 'teste.med@exemplo.com', '62988887777', 'hash', -30.00, 1, NOW(), NOW())
");
$stmtResp->execute([':cpf' => $cpfTeste]);
$respId = (int)$pdo->lastInsertId();

assertCheck($respId > 0, "Responsável de teste criado com saldo negativo (-R$ 30,00) e bloqueado_med = 1", $passados, $total);

// 2.1. Crédito insuficiente (ainda negativo): não deve desbloquear
$transInsuficiente = TransacaoCarteira::creditar($respId, 10.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Recarga Parcial');
$respAposParcial = Responsavel::buscarPorId($respId);

assertCheck((float)$respAposParcial['saldo_atual'] === -20.00, "Saldo após recarga parcial: -R$ 20,00", $passados, $total);
assertCheck((int)$respAposParcial['bloqueado_med'] === 1, "Conta permanece bloqueada pois saldo ainda é negativo (-R$ 20,00)", $passados, $total);

// 2.2. Crédito suficiente (regulariza saldo): deve desbloquear automaticamente
$transSuficiente = TransacaoCarteira::creditar($respId, 50.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Recarga Quitação Total');
$respAposTotal = Responsavel::buscarPorId($respId);

assertCheck((float)$respAposTotal['saldo_atual'] === 30.00, "Saldo após recarga de quitação: +R$ 30,00", $passados, $total);
assertCheck((int)$respAposTotal['bloqueado_med'] === 0, "Conta foi AUTOMATICAMENTE DESBLOQUEADA após saldo ficar positivo (bloqueado_med = 0)", $passados, $total);

// 3. Testando Template do Extrato do Aluno (Card Overflow Visible e Dropdown Flutuante)
echo "\n3. Testando Template do Extrato do Aluno para Dropdown Flutuante...\n";
$extratoHtml = file_get_contents(__DIR__ . '/../app/views/relatorios/extrato_aluno.php');
assertCheck(str_contains($extratoHtml, 'card-overflow-visible'), "Card de filtros do extrato possui classe card-overflow-visible", $passados, $total);
assertCheck(str_contains($extratoHtml, 'overflow: visible !important;'), "Card de filtros possui style overflow: visible !important para permitir dropdown flutuante", $passados, $total);
assertCheck(str_contains($extratoHtml, 'id="autocompleteDropdown"'), "Elemento #autocompleteDropdown presente na view", $passados, $total);
assertCheck(str_contains($extratoHtml, 'top: calc(100% + 4px)'), "Dropdown configurado com top: calc(100% + 4px) para flutuar abaixo do input", $passados, $total);
assertCheck(str_contains($extratoHtml, 'z-index: 1080'), "Dropdown possui z-index elevado (1080) para sobrepor outros cards", $passados, $total);

// 4. Limpeza dos dados de teste
echo "\n4. Limpando dados temporários...\n";
$pdo->prepare("DELETE FROM transacoes_carteira WHERE responsavel_id = :id")->execute([':id' => $respId]);
$pdo->prepare("DELETE FROM responsaveis WHERE id = :id")->execute([':id' => $respId]);
$pdo->prepare("DELETE FROM logs_erros WHERE mensagem LIKE :code")->execute([':code' => "%{$testCode}%"]);
echo "  [OK] Dados de teste limpos com sucesso.\n";

echo "\n-------------------------------------------------------\n";
echo " RESULTADO DOS TESTES DE MELHORIAS:\n";
echo " Asserções Totais    : $total\n";
echo " Asserções com Sucesso: $passados\n";
echo " Falhas Encontradas  : " . ($total - $passados) . "\n";
echo "-------------------------------------------------------\n";

exit(($total === $passados) ? 0 : 1);
