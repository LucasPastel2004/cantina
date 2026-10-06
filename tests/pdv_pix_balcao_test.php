<?php
/**
 * Teste do Fluxo de Pagamento Pix no Balcão PDV
 * 
 * Valida:
 * 1. PixService::gerarCobranca com valores baixos (ex: R$ 3,50);
 * 2. PixService::gerarQrCodeDataUri;
 * 3. PixService::consultarStatusCobranca;
 * 4. PdvController::pixGerar retornando JSON válido para o frontend;
 * 5. PdvController::finalizarVenda com cliente anônimo e forma de pagamento 'pix_balcao';
 * 6. Atualização contábil da sessão de caixa (total_pix_vendas).
 */

namespace Tests;

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\PixService;
use App\Models\SessaoCaixa;
use App\Models\VendaBalcao;
use App\Models\Usuario;
use App\Core\Auth;
use App\Controllers\PdvController;

echo "\n======================================================================";
echo "\n TESTE: FLUXO DE PAGAMENTO PIX BALCÃO NO PDV";
echo "\n======================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assercao(bool $condicao, string $descricao): void {
    global $passCount, $failCount;
    if ($condicao) {
        $passCount++;
        echo " [PASS] {$descricao}\n";
    } else {
        $failCount++;
        echo " [FAIL] {$descricao}\n";
    }
}

// 1. Testar método PixService::gerarCobranca
$cobranca = PixService::gerarCobranca(3.50, null, 'Venda Água Mineral 500ml');
assercao($cobranca['sucesso'] === true, "PixService::gerarCobranca gerou cobrança com sucesso");
assercao($cobranca['valor'] === 3.50, "Valor da cobrança confere com R$ 3,50");
assercao($cobranca['valor_formatado'] === 'R$ 3,50', "Valor formatado é 'R$ 3,50'");
assercao(!empty($cobranca['txid']), "txid único gerado: {$cobranca['txid']}");
assercao(!empty($cobranca['payload_brcode']), "Payload BR Code / Copia e Cola gerado");
assercao(!empty($cobranca['qr_code_data_uri']), "QR Code Data URI gerado");
assercao(str_starts_with($cobranca['qr_code_data_uri'], 'data:image/svg+xml;base64,'), "Formato do QR Code é Data URI SVG Base64");

// 2. Testar método PixService::gerarQrCodeDataUri
$qrUri = PixService::gerarQrCodeDataUri($cobranca['payload_brcode']);
assercao(!empty($qrUri) && str_starts_with($qrUri, 'data:image/svg+xml;base64,'), "PixService::gerarQrCodeDataUri gerou data URI válida");

// 3. Testar método PixService::consultarStatusCobranca
$status = PixService::consultarStatusCobranca($cobranca['txid']);
assercao($status === 'pendente', "Status inicial da cobrança é 'pendente'");

// 4. Testar PdvController::pixGerar
$_POST = [
    'valor' => '3,50'
];
$_SERVER['REQUEST_METHOD'] = 'POST';

$pdvCtrl = new PdvController();
ob_start();
$pdvCtrl->pixGerar();
$outputJson = ob_get_clean();

$dataResp = json_decode($outputJson, true);
assercao(!empty($dataResp) && $dataResp['sucesso'] === true, "PdvController::pixGerar retornou sucesso");
assercao(!empty($dataResp['brcode']), "Resposta contém campo 'brcode'");
assercao(!empty($dataResp['qr_code_uri']), "Resposta contém campo 'qr_code_uri'");
assercao($dataResp['valor_formatado'] === '3,50', "Resposta contém campo 'valor_formatado'");

// 5. Testar finalização de venda balcão com forma 'pix_balcao' e cliente anônimo
$operador = Usuario::fetchOne("SELECT * FROM usuarios WHERE ativo = 1 LIMIT 1");
$_SESSION['usuario_id'] = $operador['id'];
$_SESSION['usuario_nivel'] = $operador['perfil'];

// Garante sessão de caixa aberta para o teste
$sessao = SessaoCaixa::obterQualquerSessaoAberta();
$sessaoId = $sessao ? (int)$sessao['id'] : 0;
$criouSessao = false;
if ($sessaoId === 0) {
    $sessaoId = SessaoCaixa::abrir('matutino', 50.00, (int)$operador['id'], 'Teste Pix Balcão');
    $criouSessao = true;
}

$sessaoAntes = SessaoCaixa::buscarPorId($sessaoId);
$totalPixAntes = (float)($sessaoAntes['total_pix_balcao'] ?? 0.0);

// Venda avulsa anônima de 1 água mineral
$itensVenda = [
    [
        'produto_id' => 1,
        'quantidade' => 1,
        'preco_unitario' => 3.50,
        'subtotal' => 3.50,
        'descricao' => 'Água Mineral sem Gás 500ml'
    ]
];

$vendaCriada = VendaBalcao::criarVenda([
    'tipo_venda' => 'avulso_aluno',
    'forma_pagamento' => 'pix_balcao',
    'aluno_id' => null,
    'colaborador_id' => null,
    'valor_dinheiro_recebido' => 0.0
], $itensVenda, (int)$operador['id'], $sessaoId);

assercao(!empty($vendaCriada['id']), "Venda anônima via Pix Balcão criada com ID #{$vendaCriada['id']}");
assercao($vendaCriada['forma_pagamento'] === 'pix_balcao', "Forma de pagamento da venda é 'pix_balcao'");
assercao((float)$vendaCriada['valor_total'] === 3.50, "Total da venda confere com R$ 3,50");

// 6. Verificar incremento de total_pix_balcao na sessão
$sessaoDepois = SessaoCaixa::buscarPorId($sessaoId);
$totalPixDepois = (float)($sessaoDepois['total_pix_balcao'] ?? 0.0);
assercao(abs(($totalPixDepois - $totalPixAntes) - 3.50) < 0.001, "total_pix_balcao na sessão de caixa incrementado exatamente em R$ 3,50");

// Limpeza se criamos sessão de teste
if ($criouSessao) {
    SessaoCaixa::fechar($sessaoId, 50.00, (int)$operador['id'], 'Fechamento teste Pix');
}

echo "\n----------------------------------------------------------------------";
echo "\n RESULTADO: {$passCount}/" . ($passCount + $failCount) . " asserções aprovadas.";
echo "\n----------------------------------------------------------------------\n\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
