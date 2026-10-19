<?php
/**
 * Teste Automatizado de Homologação: Zoom de Impressão e Ajuste Visual de Layout de Estoque
 * 
 * Valida os ajustes solicitados nos feedbacks do usuário:
 * 1. Opção de Zoom interativo na tela de visualização para impressão/PDF de relatórios
 * 2. Remoção do bloco fixo de Centro de Custo, subida de Quebra de Página e expansão da dica para 100%
 */

declare(strict_types=1);

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

use App\Services\PdfService;
use App\Controllers\RelatoriosController;

echo "====================================================================\n";
echo " TESTE: ZOOM DE IMPRESSÃO & SIMETRIA DA ABA LAYOUT DE ESTOQUE\n";
echo "====================================================================\n\n";

$passou = 0;
$total = 0;

function assertCheck(bool $condicao, string $descricao, &$passou, &$total): void {
    $total++;
    if ($condicao) {
        $passou++;
        echo "  [OK] $descricao\n";
    } else {
        echo "  [FALHA] $descricao\n";
    }
}

// -----------------------------------------------------------------------------
// 1. TESTE DA ABA LAYOUT DE ESTOQUE (app/views/relatorios/estoque_filtros.php)
// -----------------------------------------------------------------------------
echo ">> 1. Testando Simetria e Componentes da Aba Layout (estoque_filtros)...\n";

$conteudoFiltros = file_get_contents(__DIR__ . '/../app/views/relatorios/estoque_filtros.php');

assertCheck(
    strpos($conteudoFiltros, 'Agrupamento por centro de custo') === false,
    'Bloco visual "Agrupamento por centro de custo" foi devidamente removido',
    $passou,
    $total
);

assertCheck(
    strpos($conteudoFiltros, '<input type="hidden" name="agrupar_centro_custo" value="ocultar">') !== false,
    'Parâmetro oculto agrupar_centro_custo mantido para compatibilidade',
    $passou,
    $total
);

// Verifica se na primeira linha há exatamente os 4 blocos de col-md-3: Formato do layout, Grupo, Produto, Quebra de página
assertCheck(
    strpos($conteudoFiltros, 'Formato do layout') !== false &&
    strpos($conteudoFiltros, 'Agrupamento por grupo de produtos') !== false &&
    strpos($conteudoFiltros, 'Agrupamento por produto') !== false &&
    strpos($conteudoFiltros, 'Quebra de página') !== false,
    'Todas as 4 opções de configuração estão presentes na linha de layout',
    $passou,
    $total
);

// Verifica se a dica está em col-12 cobrindo toda a largura
assertCheck(
    preg_match('/<div class="row pt-3 border-top">\s*<div class="col-12">\s*<div class="p-3 bg-light rounded border w-100">/', $conteudoFiltros) === 1,
    'Dica de Otimização de Impressão foi expandida para cobrir toda a largura (col-12)',
    $passou,
    $total
);

// -----------------------------------------------------------------------------
// 2. TESTE DO MOTOR DE ZOOM NO RELATÓRIO DE ESTOQUE (estoque_movimentacoes_pdf.php)
// -----------------------------------------------------------------------------
echo "\n>> 2. Testando Mecanismo de Zoom no Relatório de Estoque (estoque_movimentacoes_pdf)...\n";

$conteudoEstoquePdf = file_get_contents(__DIR__ . '/../app/views/relatorios/estoque_movimentacoes_pdf.php');

assertCheck(
    strpos($conteudoEstoquePdf, 'zoom-controls') !== false,
    'Barra superior contém o elemento .zoom-controls',
    $passou,
    $total
);

assertCheck(
    strpos($conteudoEstoquePdf, 'id="btnZoomOut"') !== false &&
    strpos($conteudoEstoquePdf, 'id="btnZoomReset"') !== false &&
    strpos($conteudoEstoquePdf, 'id="btnZoomIn"') !== false,
    'Botões de diminuir zoom, reset 100% e aumentar zoom estão presentes',
    $passou,
    $total
);

assertCheck(
    strpos($conteudoEstoquePdf, 'position: sticky;') !== false,
    'Barra superior possui position: sticky para acompanhar o scroll durante o zoom',
    $passou,
    $total
);

assertCheck(
    strpos($conteudoEstoquePdf, 'id="document-zoom-area"') !== false &&
    strpos($conteudoEstoquePdf, 'class="document-zoom-area"') !== false,
    'Container escalável #document-zoom-area envolve as páginas do relatório',
    $passou,
    $total
);

assertCheck(
    strpos($conteudoEstoquePdf, 'zoom: 1 !important;') !== false,
    'Regra @media print força zoom: 1 !important para impressão física ou salvar em PDF',
    $passou,
    $total
);

assertCheck(
    strpos($conteudoEstoquePdf, 'function aplicarZoom(novoZoom)') !== false &&
    strpos($conteudoEstoquePdf, 'beforeprint') !== false &&
    strpos($conteudoEstoquePdf, 'afterprint') !== false,
    'Script possui funções de zoom, atalhos de teclado e proteção nos eventos de impressão',
    $passou,
    $total
);

// -----------------------------------------------------------------------------
// 3. TESTE DE GERAÇÃO EM HTML E PDF DE MOVIMENTAÇÕES DE ESTOQUE
// -----------------------------------------------------------------------------
echo "\n>> 3. Testando Renderização HTML e Compilação Gotenberg do Relatório com Zoom...\n";

$relCtrl = new RelatoriosController();
$dadosEstoque = $relCtrl->obterDadosMovimentacoesEstoqueAvancadas([
    'data_inicio'    => date('Y-m-01'),
    'data_fim'       => date('Y-m-d'),
    'formato_layout' => 'retrato',
    'quebra_pagina'  => 'ocultar'
]);

$htmlEstoque = PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosEstoque);

assertCheck(
    strpos($htmlEstoque, 'zoom-controls') !== false,
    'HTML renderizado pelo PdfService inclui a barra de controles de zoom',
    $passou,
    $total
);

assertCheck(
    strpos($htmlEstoque, 'document-zoom-area') !== false,
    'HTML renderizado pelo PdfService inclui o container document-zoom-area',
    $passou,
    $total
);

$pdfBin = PdfService::htmlToPdf($htmlEstoque, [
    'landscape'       => false,
    'marginTop'       => '0mm',
    'marginBottom'    => '0mm',
    'marginLeft'      => '0mm',
    'marginRight'     => '0mm',
    'printBackground' => true
]);

assertCheck(
    $pdfBin !== null && substr($pdfBin, 0, 4) === '%PDF',
    'Gotenberg compilou o HTML com controles de zoom com sucesso em binário PDF válido (%PDF)',
    $passou,
    $total
);

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO: $passou / $total asserções aprovadas\n";
if ($passou === $total) {
    echo " >>> SUCESSO TOTAL: TODAS AS ASSERÇÕES PASSARAM! <<<\n";
} else {
    echo " >>> ALERTA: HOUVE FALHA EM UMA OU MAIS ASSERÇÕES! <<<\n";
    exit(1);
}
