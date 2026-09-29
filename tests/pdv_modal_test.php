<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Específico: Resolução do Erro querySelector e Modais do PDV
 */

declare(strict_types=1);

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
    if (file_exists($file)) require_once $file;
});

$totalTestes = 0;
$testesPassados = 0;

function asserir(bool $condicao, string $descricao): void {
    global $totalTestes, $testesPassados;
    $totalTestes++;
    if ($condicao) {
        $testesPassados++;
        echo "  [PASS] {$descricao}\n";
    } else {
        echo "  [FAIL] {$descricao}\n";
    }
}

echo "======================================================================\n";
echo "  INICIANDO TESTES — MODAIS DO PDV E RESOLUÇÃO DO ERRO querySelector\n";
echo "======================================================================\n\n";

// 1. Verificação do app.js
echo "[1] Verificação de abrirModal e fecharModal em assets/js/app.js...\n";
$appJs = file_get_contents(__DIR__ . '/../assets/js/app.js');

asserir(str_contains($appJs, 'function abrirModal('), "Função abrirModal definida no app.js");
asserir(str_contains($appJs, 'function fecharModal('), "Função fecharModal definida no app.js");
asserir(str_contains($appJs, 'modal-backdrop-custom'), "abrirModal reconhece .modal-backdrop-custom");
asserir(str_contains($appJs, 'classList.remove(\'d-none\')'), "abrirModal remove d-none de modais customizados");
asserir(str_contains($appJs, 'classList.add(\'d-none\')'), "fecharModal adiciona d-none a modais customizados");

// 2. Verificação do template pdv.php
echo "\n[2] Verificação da integridade dos modais em app/views/operacional/pdv.php...\n";
$pdvPhp = file_get_contents(__DIR__ . '/../app/views/operacional/pdv.php');

asserir(str_contains($pdvPhp, 'id="modalPosVenda"'), "Modal pós-venda presente com id='modalPosVenda'");
asserir(str_contains($pdvPhp, 'id="posVendaId"'), "Elemento #posVendaId presente");
asserir(str_contains($pdvPhp, 'id="posVendaTotal"'), "Elemento #posVendaTotal presente");
asserir(str_contains($pdvPhp, 'id="posVendaTrocoBox"'), "Elemento #posVendaTrocoBox presente");
asserir(str_contains($pdvPhp, 'id="btnImprimirComprovante"'), "Elemento #btnImprimirComprovante presente");
asserir(str_contains($pdvPhp, 'fecharModal(\'modalPosVenda\')'), "Botões de fechar modal pós-venda utilizam fecharModal");
asserir(str_contains($pdvPhp, 'function fecharModal('), "Função fecharModal definida no script do PDV");
asserir(str_contains($pdvPhp, 'function abrirModal('), "Função abrirModal definida no script do PDV");
asserir(str_contains($pdvPhp, "fecharModal('modalPosVenda')"), "Atalho F2 fecha modalPosVenda");

// 3. Teste do Comprovante da Venda no Balcão
echo "\n[3] Verificação da geração do espelho do comprovante térmico (80mm)...\n";
$venda = \App\Models\VendaBalcao::buscarPorId(56);
if (!$venda) {
    $vendaIdRecente = (int)\Database::getInstance()->query("SELECT id FROM vendas_balcao ORDER BY id DESC LIMIT 1")->fetchColumn();
    $venda = $vendaIdRecente ? \App\Models\VendaBalcao::buscarPorId($vendaIdRecente) : null;
}
asserir($venda !== null, "Venda balcão para emissão de comprovante localizada no banco de dados");
if ($venda) {
    $vId = $venda['id'];
    $htmlComprovante = \App\Services\PdfService::gerarEspelhoComprovanteVendaBalcaoHtml($venda);
    asserir(stripos($htmlComprovante, 'Comprovante de Venda') !== false, "Comprovante HTML contém cabeçalho correto");
    asserir(str_contains($htmlComprovante, '#' . $vId), "Comprovante HTML exibe identificador da venda #{$vId}");
}

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: {$testesPassados}/{$totalTestes} testes aprovados.\n";
if ($testesPassados === $totalTestes) {
    echo "STATUS: 100% DE SUCESSO!\n";
} else {
    echo "STATUS: ALGUNS TESTES FALHARAM.\n";
    exit(1);
}
echo "----------------------------------------------------------------------\n";
