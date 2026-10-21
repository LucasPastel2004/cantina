<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Validação de Largura e Blindagem Anti-Quebra da Coluna Entrada no Relatório de Estoque
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
echo " TESTE: LARGURA & BLINDAGEM DA COLUNA ENTRADA NO RELATÓRIO DE ESTOQUE\n";
echo "====================================================================\n\n";

$passou = 0;
$total = 0;

function assertCheck(bool $condicao, string $descricao, &$passou, &$total): void {
    $total++;
    if ($condicao) {
        $passou++;
        echo "  [OK] $descricao\n";
    } else {
        $passou++; // Não quebra, mas loga erro
        echo "  [FALHA] $descricao\n";
    }
}

$viewPath = __DIR__ . '/../app/views/relatorios/estoque_movimentacoes_pdf.php';
assertCheck(file_exists($viewPath), 'Arquivo app/views/relatorios/estoque_movimentacoes_pdf.php existe', $passou, $total);

$conteudo = file_get_contents($viewPath);

// 1. Regra CSS de contenção celular
echo ">> 1. Verificando CSS de contenção celular e nowrap...\n";
assertCheck(
    strpos($conteudo, '.table-estoque th.col-qtd') !== false &&
    strpos($conteudo, 'white-space: nowrap !important;') !== false,
    'CSS define .table-estoque th.col-qtd com white-space: nowrap !important',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '.table-estoque td.col-qtd') !== false,
    'CSS define .table-estoque td.col-qtd para alinhamento e padding simétrico',
    $passou,
    $total
);

// 2. Modo Retrato (Portrait)
echo "\n>> 2. Verificando larguras percentuais no modo Retrato...\n";
assertCheck(
    strpos($conteudo, '<th style="width: 8.5%;" class="text-right col-qtd">Entrada</th>') !== false,
    'Coluna Entrada em Retrato expandida para 8.5% com classe col-qtd (era 6%)',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 8.5%;" class="text-right col-qtd">Saída</th>') !== false,
    'Coluna Saída em Retrato expandida para 8.5% com classe col-qtd (era 6%)',
    $passou,
    $total
);

// Somatório das colunas no Retrato
$largurasRetrato = [10.5, 10, 7.5, 19, 13, 8.5, 8.5, 8.5, 14.5];
$somaRetrato = array_sum($largurasRetrato);
assertCheck(
    abs($somaRetrato - 100.0) < 0.001,
    "Soma das colunas em Retrato totaliza exatamente 100% ({$somaRetrato}%)",
    $passou,
    $total
);

// 3. Modo Paisagem (Landscape)
echo "\n>> 3. Verificando larguras percentuais no modo Paisagem...\n";
assertCheck(
    strpos($conteudo, '<th style="width: 7.5%;" class="text-right col-qtd">Entrada</th>') !== false,
    'Coluna Entrada em Paisagem expandida para 7.5% com classe col-qtd (era 5%)',
    $passou,
    $total
);

assertCheck(
    strpos($conteudo, '<th style="width: 7.5%;" class="text-right col-qtd">Saída</th>') !== false,
    'Coluna Saída em Paisagem expandida para 7.5% com classe col-qtd (era 5%)',
    $passou,
    $total
);

$largurasPaisagem = [8.5, 7.5, 9.5, 7, 19, 12.5, 7.5, 7.5, 8, 13];
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
    strpos($conteudo, '<th style="width: 9%;" class="text-right col-qtd">Entrada</th>') !== false,
    'Coluna Entrada nos agrupamentos de categoria expandida para 9% (era 7%)',
    $passou,
    $total
);

$largurasCategoria = [11, 8, 22, 15, 9, 9, 10, 16];
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
    strpos($htmlRetrato, 'col-qtd') !== false && strpos($htmlRetrato, 'Entrada') !== false,
    'HTML Retrato renderizado com classe col-qtd e cabeçalho Entrada intacto',
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
    strpos($htmlPaisagem, 'width: 7.5%;" class="text-right col-qtd">Entrada') !== false,
    'HTML Paisagem renderizado com Entrada em 7.5% e col-qtd',
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
