<?php
/**
 * Teste Específico de Design para DRE e Estoque Movimentações
 */
define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/services/PdfService.php';

echo "=== TESTE DE PADRONIZAÇÃO VISUAL: DRE E ESTOQUE MOVIMENTAÇÕES ===\n\n";

// 1. DRE
$htmlDre = \App\Services\PdfService::renderView('financeiro/dre_pdf', [
    'ano' => 2026,
    'regime' => 'competencia',
    'dre' => [
        'meses' => [1 => ['receita_bruta' => 1000.0]],
        'totais' => ['receita_bruta' => 1000.0]
    ],
    'isPdf' => false
]);

$dreTemSchoolHeader = strpos($htmlDre, 'school-header') !== false;
$dreTemParamBar     = strpos($htmlDre, 'param-bar') !== false;
$dreTemBanner       = strpos($htmlDre, 'section-banner') !== false && strpos($htmlDre, '1. Demonstrativo Comparativo Mensal') !== false;
$dreTemBotaoPadrao  = strpos($htmlDre, 'Imprimir / Salvar PDF (A4)') !== false;
$dreTemAzulEscuro   = strpos($htmlDre, '#0f172a') !== false;

echo "DRE - school-header: " . ($dreTemSchoolHeader ? "OK" : "FALHA") . "\n";
echo "DRE - param-bar: " . ($dreTemParamBar ? "OK" : "FALHA") . "\n";
echo "DRE - section-banner: " . ($dreTemBanner ? "OK" : "FALHA") . "\n";
echo "DRE - Botão Imprimir / Salvar PDF (A4): " . ($dreTemBotaoPadrao ? "OK" : "FALHA") . "\n";
echo "DRE - Fundo Azul Escuro #0f172a: " . ($dreTemAzulEscuro ? "OK" : "FALHA") . "\n\n";

// 2. Estoque Movimentações
$htmlEstoque = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', [
    'movimentacoes' => [
        ['data_movimentacao' => '2026-10-01 08:00', 'tipo' => 'entrada', 'produto_codigo' => 'SAL01', 'produto_nome' => 'Coxinha', 'categoria_nome' => 'Salgados', 'quantidade' => 50, 'saldo_anterior' => 10, 'saldo_novo' => 60, 'usuario_nome' => 'Admin']
    ],
    'totais' => ['total_entradas' => 50, 'total_saidas' => 0, 'total_vendas' => 0, 'total_perdas' => 0, 'balanco_periodo' => 50, 'total_movimentacoes' => 1],
    'isPaisagem' => false,
    'agruparGrupo' => false,
    'quebraPagina' => false
]);

$estTemBanner1      = strpos($htmlEstoque, '1. Movimentações Analíticas de Estoque') !== false;
$estTemBanner2      = strpos($htmlEstoque, '2. Síntese e Totais Consolidados') !== false;
$estTemBotaoPadrao  = strpos($htmlEstoque, 'Imprimir / Salvar PDF (A4)') !== false;
$estTemAzulEscuro   = strpos($htmlEstoque, '#0f172a') !== false;

echo "Estoque - Banner Seção 1: " . ($estTemBanner1 ? "OK" : "FALHA") . "\n";
echo "Estoque - Banner Seção 2: " . ($estTemBanner2 ? "OK" : "FALHA") . "\n";
echo "Estoque - Botão Imprimir / Salvar PDF (A4): " . ($estTemBotaoPadrao ? "OK" : "FALHA") . "\n";
echo "Estoque - Fundo Azul Escuro #0f172a: " . ($estTemAzulEscuro ? "OK" : "FALHA") . "\n\n";

$passou = $dreTemSchoolHeader && $dreTemParamBar && $dreTemBanner && $dreTemBotaoPadrao && $dreTemAzulEscuro &&
          $estTemBanner1 && $estTemBanner2 && $estTemBotaoPadrao && $estTemAzulEscuro;

echo "Resultado: " . ($passou ? "TODOS OS ITENS APROVADOS!" : "FALHAS IDENTIFICADAS") . "\n";
exit($passou ? 0 : 1);
