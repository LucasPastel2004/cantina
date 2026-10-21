<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Validação de Larguras, Abreviações Elegantes e Blindagem Anti-Quebra no Relatório de Estoque
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
echo " TESTE: LARGURAS, ABREVIAÇÕES & BLINDAGEM NO RELATÓRIO DE ESTOQUE\n";
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

$viewPath = __DIR__ . '/../app/views/relatorios/estoque_movimentacoes_pdf.php';
assertCheck(file_exists($viewPath), 'Arquivo app/views/relatorios/estoque_movimentacoes_pdf.php existe', $passou, $total);

$conteudo = file_get_contents($viewPath);

// 1. Regra CSS de contenção celular e nowrap
echo ">> 1. Verificando CSS de contenção celular e nowrap...\n";
assertCheck(
    strpos($conteudo, '.table-estoque th.col-qtd') !== false &&
    strpos($conteudo, 'white-space: nowrap !important;') !== false,
    'CSS define .table-estoque th.col-qtd com white-space: nowrap !important',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '.table-estoque th.th-nowrap') !== false,
    'CSS define .table-estoque th.th-nowrap para impedir quebra em títulos como Operador e Data/Hora',
    $passou,
    $total
);

// 2. Modo Retrato (Portrait) - Abreviações e Larguras
echo "\n>> 2. Verificando cabeçalhos abreviados e larguras no modo Retrato...\n";
assertCheck(
    strpos($conteudo, '<th style="width: 20%;">Produto</th>') !== false,
    'Cabeçalho "DESCRIÇÃO DO PRODUTO" abreviado para "Produto" com 20% de espaço',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 12%;">Tipo Mov.</th>') !== false,
    'Cabeçalho "TIPO MOVIMENTAÇÃO" abreviado para "Tipo Mov." com 12% de espaço',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 10.5%;" class="th-nowrap">Operador</th>') !== false,
    'Coluna Operador expandida para 10.5% (era 8.5%) com th-nowrap anti-quebra do "R"',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 14.5%;">Doc / Justif.</th>') !== false,
    'Cabeçalho "DOC / JUSTIFICATIVA" abreviado para "Doc / Justif." com 14.5%',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 8%;" class="text-right col-qtd">Entrada</th>') !== false,
    'Coluna Entrada em Retrato preservada com 8% e classe col-qtd',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 7.5%;" class="text-right col-qtd">Saída</th>') !== false,
    'Coluna Saída em Retrato preservada com 7.5% e classe col-qtd',
    $passou,
    $total
);

// Somatório das colunas no Retrato
$largurasRetrato = [10.5, 9.5, 7.5, 20, 12, 8, 7.5, 10.5, 14.5];
$somaRetrato = array_sum($largurasRetrato);
assertCheck(
    abs($somaRetrato - 100.0) < 0.001,
    "Soma das colunas em Retrato totaliza exatamente 100% ({$somaRetrato}%)",
    $passou,
    $total
);

// 3. Modo Paisagem (Landscape) - Abreviações e Larguras
echo "\n>> 3. Verificando cabeçalhos abreviados e larguras no modo Paisagem...\n";
assertCheck(
    strpos($conteudo, '<th style="width: 9.5%;" class="th-nowrap">Operador</th>') !== false,
    'Coluna Operador expandida em Paisagem para 9.5% com th-nowrap',
    $passou,
    $total
);

$largurasPaisagem = [8.5, 7, 9, 7, 20, 11.5, 7.5, 7, 9.5, 13];
$somaPaisagem = array_sum($largurasPaisagem);
assertCheck(
    abs($somaPaisagem - 100.0) < 0.001,
    "Soma das colunas em Paisagem totaliza exatamente 100% ({$somaPaisagem}%)",
    $passou,
    $total
);

// 4. Modos Agrupados por Categoria
echo "\n>> 4. Verificando larguras nos modos Agrupados por Categoria...\n";
assertCheck(
    strpos($conteudo, '<th style="width: 13%;" class="th-nowrap">Operador</th>') !== false,
    'Coluna Operador nos agrupamentos de categoria expandida para 13% com th-nowrap',
    $passou,
    $total
);

$largurasCategoria = [10.5, 8, 22, 13.5, 8.5, 8.5, 13, 16];
$somaCategoria = array_sum($largurasCategoria);
assertCheck(
    abs($somaCategoria - 100.0) < 0.001,
    "Soma das colunas em Agrupamento por Categoria totaliza exatamente 100% ({$somaCategoria}%)",
    $passou,
    $total
);

// 5. Renderização e Compilação via Gotenberg
echo "\n>> 5. Testando Renderização Real e Compilação Gotenberg...\n";
$relCtrl = new RelatoriosController();

// 5.1 Teste Retrato
$dadosRetrato = $relCtrl->obterDadosMovimentacoesEstoqueAvancadas([
    'data_inicio'    => date('Y-m-01'),
    'data_fim'       => date('Y-m-d'),
    'formato_layout' => 'retrato'
]);
$htmlRetrato = PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosRetrato);

assertCheck(
    strpos($htmlRetrato, 'col-qtd') !== false &&
    strpos($htmlRetrato, 'th-nowrap') !== false &&
    strpos($htmlRetrato, 'Produto') !== false &&
    strpos($htmlRetrato, 'Tipo Mov.') !== false &&
    strpos($htmlRetrato, 'Operador') !== false,
    'HTML Retrato renderizado com abreviações elegantes e classes anti-quebra',
    $passou,
    $total
);

$pdfRetrato = PdfService::htmlToPdf($htmlRetrato, ['landscape' => false, 'preferCssPageSize' => 'true']);
assertCheck(
    $pdfRetrato !== null && str_starts_with($pdfRetrato, '%PDF'),
    'Gotenberg compila PDF Retrato com sucesso (%PDF válido)',
    $passou,
    $total
);

// 5.2 Teste Paisagem
$dadosPaisagem = $relCtrl->obterDadosMovimentacoesEstoqueAvancadas([
    'data_inicio'    => date('Y-m-01'),
    'data_fim'       => date('Y-m-d'),
    'formato_layout' => 'paisagem'
]);
$htmlPaisagem = PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosPaisagem);

assertCheck(
    strpos($htmlPaisagem, 'width: 9.5%;" class="th-nowrap">Operador') !== false,
    'HTML Paisagem renderizado com Operador em 9.5% e th-nowrap',
    $passou,
    $total
);

$pdfPaisagem = PdfService::htmlToPdf($htmlPaisagem, ['landscape' => true, 'preferCssPageSize' => 'true']);
assertCheck(
    $pdfPaisagem !== null && str_starts_with($pdfPaisagem, '%PDF'),
    'Gotenberg compila PDF Paisagem com sucesso (%PDF válido)',
    $passou,
    $total
);

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO: $passou / $total asserções aprovadas\n";
if ($passou === $total) {
    echo " >>> SUCESSO TOTAL: TODAS AS ASSERÇÕES PASSARAM! <<<\n";
    exit(0);
} else {
    echo " >>> ERRO: UMA OU MAIS ASSERÇÕES FALHARAM! <<<\n";
    exit(1);
}
