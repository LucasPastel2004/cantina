<?php
/**
 * Teste de Validação dos Comprovantes Térmicos e Fluxo de Impressão (80mm)
 * 
 * Valida:
 * 1. Template de caixa.php com baseUrl declarada e função de impressão assíncrona
 * 2. Template de pdv.php com baseUrl e função de impressão assíncrona
 * 3. Renderização de comprovante de recarga (HTML) com top-toolbar de 100%, visualizador centralizado e script de autoprint
 * 4. Renderização de comprovante de venda PDV (HTML) com top-toolbar de 100%, visualizador centralizado e script de autoprint
 */

define('CANTINA_SISTEMA', true);

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

echo "\n======================================================================";
echo "\n TESTE: COMPROVANTES TÉRMICOS E FLUXO DE IMPRESSÃO (80mm)";
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

// 1. Validar caixa.php
$caixaView = file_get_contents(__DIR__ . '/../app/views/operacional/caixa.php');
assercao(str_contains($caixaView, 'const baseUrl = "<?php echo $baseUrl; ?>";'), "caixa.php declara 'const baseUrl' no JavaScript");
assercao(str_contains($caixaView, 'iframe.onload = function()'), "caixa.php possui tratamento de 'iframe.onload' para disparo de impressão");
assercao(str_contains($caixaView, 'iframe.contentWindow.print()'), "caixa.php aciona 'iframe.contentWindow.print()' na impressão direta");
assercao(str_contains($caixaView, 'Visualizar comprovante em tela cheia'), "caixa.php possui rótulo claro 'Visualizar comprovante em tela cheia'");

// 2. Validar pdv.php
$pdvView = file_get_contents(__DIR__ . '/../app/views/operacional/pdv.php');
assercao(str_contains($pdvView, 'let baseUrl = "<?php echo $baseUrl; ?>"'), "pdv.php declara baseUrl no JavaScript");
assercao(str_contains($pdvView, 'iframe.onload = function()'), "pdv.php possui tratamento de 'iframe.onload' para disparo de impressão");
assercao(str_contains($pdvView, 'iframe.contentWindow.print()'), "pdv.php aciona 'iframe.contentWindow.print()' na impressão direta");
assercao(str_contains($pdvView, 'Visualizar comprovante em tela cheia'), "pdv.php possui rótulo claro 'Visualizar comprovante em tela cheia'");

// 3. Validar renderização do comprovante de recarga em dinheiro
$dadosRecargaMock = [
    'transacao_id' => 9999,
    'id' => 9999,
    'sessao_caixa_id' => 123,
    'referencia_id' => 123,
    'created_at' => date('Y-m-d H:i:s'),
    'operador_nome' => 'Operador Teste',
    'responsavel_nome' => 'Responsavel Teste',
    'responsavel_cpf' => '12345678901',
    'valor' => 50.00,
    'saldo_anterior' => 20.00,
    'saldo_resultante' => 70.00,
    'tipo_transacao' => 'recarga_dinheiro',
    'forma_pagamento' => 'dinheiro',
    'dependentes' => [
        ['nome' => 'Aluno Teste', 'nome_turma' => 'Turma 5A']
    ]
];

$htmlRecarga = \App\Services\PdfService::gerarComprovanteRecargaDinheiroHtml($dadosRecargaMock);
assercao(!empty($htmlRecarga), "Comprovante de recarga HTML gerado");
assercao(str_contains($htmlRecarga, 'top-toolbar'), "Comprovante de recarga contém barra superior de ferramentas (.top-toolbar)");
assercao(str_contains($htmlRecarga, 'receipt-screen-wrapper'), "Comprovante de recarga contém wrapper de visualização centralizado (.receipt-screen-wrapper)");
assercao(str_contains($htmlRecarga, 'autoprint'), "Comprovante de recarga contém suporte ao parâmetro autoprint");
assercao(str_contains($htmlRecarga, 'window.print()'), "Comprovante de recarga contém acionamento de window.print()");
assercao(str_contains($htmlRecarga, 'Baixar PDF'), "Comprovante de recarga contém botão direto 'Baixar PDF'");
assercao(str_contains($htmlRecarga, '@media screen'), "Comprovante de recarga contém estilos responsivos de tela (@media screen)");
assercao(str_contains($htmlRecarga, '@media print'), "Comprovante de recarga contém regras estritas de impressão de bobina (@media print)");

// 4. Validar renderização do comprovante de venda PDV
$dadosVendaMock = [
    'id' => 8888,
    'created_at' => date('Y-m-d H:i:s'),
    'operador_nome' => 'Operador Caixa',
    'forma_pagamento' => 'dinheiro',
    'tipo_venda' => 'avulsa',
    'valor_total' => 15.00,
    'valor_dinheiro_recebido' => 20.00,
    'troco_devolvido' => 5.00,
    'itens' => [
        ['produto_descricao' => 'Pão de Queijo', 'quantidade' => 2, 'preco_unitario' => 5.00, 'subtotal' => 10.00],
        ['produto_descricao' => 'Suco Natural', 'quantidade' => 1, 'preco_unitario' => 5.00, 'subtotal' => 5.00]
    ]
];

$htmlVenda = \App\Services\PdfService::gerarEspelhoComprovanteVendaBalcaoHtml($dadosVendaMock);
assercao(!empty($htmlVenda), "Comprovante de venda PDV HTML gerado");
assercao(str_contains($htmlVenda, 'top-toolbar'), "Comprovante de venda PDV contém barra superior de ferramentas (.top-toolbar)");
assercao(str_contains($htmlVenda, 'receipt-screen-wrapper'), "Comprovante de venda PDV contém wrapper centralizado (.receipt-screen-wrapper)");
assercao(str_contains($htmlVenda, 'autoprint'), "Comprovante de venda PDV contém suporte ao parâmetro autoprint");
assercao(str_contains($htmlVenda, 'window.print()'), "Comprovante de venda PDV contém acionamento de window.print()");

echo "\n----------------------------------------------------------------------";
echo "\n RESULTADO: {$passCount}/" . ($passCount + $failCount) . " asserções aprovadas.";
echo "\n----------------------------------------------------------------------\n\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
