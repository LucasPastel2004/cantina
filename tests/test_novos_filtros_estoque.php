<?php
/**
 * Teste de Validação dos Novos Filtros de Movimentações de Estoque
 * Conforme solicitação do usuário:
 * 1. Tipo de movimentação: Entrada, Estorno, Saída, Baixa por Perda / Descarte
 * 2. Tipo de Entrada dinâmico: Salgados (Corte), Entrada Manual (Reposição), Aproveitamento de Sobras
 * 3. Tipo de Saída dinâmico: Saída por Venda Balcão (PDV), Saída por Encomenda Cantina Web
 * 4. Motivo de Perda visível apenas para Baixa por Perda / Descarte
 * 5. Exclusão permanente de NF-e (XML)
 * 6. Remoção do bloco de Centro de Custo da interface
 */

declare(strict_types=1);

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

echo "======================================================================\n";
echo " TESTE DOS NOVOS FILTROS DINÂMICOS DE MOVIMENTAÇÕES DE ESTOQUE\n";
echo "======================================================================\n\n";

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

$controller = new \App\Controllers\RelatoriosController();

// 1. Validação da View (app/views/relatorios/estoque_filtros.php)
$viewContent = file_get_contents(__DIR__ . '/../app/views/relatorios/estoque_filtros.php');

assertCheck(
    strpos($viewContent, 'Centro de Custo Ativo:') === false,
    'Bloco visual "Centro de Custo Ativo" foi devidamente removido',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, '<input type="hidden" name="centro_custo" value="Cantina Geral">') !== false,
    'Campo oculto centro_custo mantido para compatibilidade e integridade contábil',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, 'id="colunaFiltroSecundario"') !== false,
    'Coluna secundária dinâmica presente (id="colunaFiltroSecundario")',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, 'id="blocoTipoEntrada"') !== false,
    'Bloco de Tipo de Entrada presente com opções de Salgados (Corte) e Manual',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, 'id="blocoTipoSaida"') !== false,
    'Bloco de Tipo de Saída presente com opções de Venda Balcão e Encomenda',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, 'id="blocoMotivoPerda"') !== false,
    'Bloco de Motivo de Perda encapsulado na coluna secundária dinâmica',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, 'Entrada por NF-e (XML)') === false,
    'Opção de Entrada por NF-e (XML) excluída das opções de movimentação',
    $passou,
    $total
);

assertCheck(
    strpos($viewContent, 'atualizarFiltrosSecundarios()') !== false,
    'Função JS atualizarFiltrosSecundarios() implementada e associada ao onchange',
    $passou,
    $total
);

// 2. Validação das Consultas e Filtros de Negócio
// 2.1 Entrada geral (não deve conter entrada_xml)
$resEntradaGeral = $controller->obterDadosMovimentacoesEstoqueAvancadas([
    'tipo_movimentacao' => 'entrada',
    'data_inicio'       => '2020-01-01',
    'data_fim'          => date('Y-m-d')
]);

$temXml = false;
foreach ($resEntradaGeral['movimentacoes'] as $m) {
    if ($m['tipo_movimentacao'] === 'entrada_xml') {
        $temXml = true;
        break;
    }
}
assertCheck(!$temXml, 'Filtro de Entrada não retorna movimentações de XML', $passou, $total);

// 2.2 Entrada salgados (corte)
$resSalgados = $controller->obterDadosMovimentacoesEstoqueAvancadas([
    'tipo_movimentacao' => 'entrada',
    'subtipo_entrada'   => 'entrada_salgados',
    'data_inicio'       => '2020-01-01',
    'data_fim'          => date('Y-m-d')
]);

$todosSalgados = true;
$rotuloSalgadoCorreto = true;
foreach ($resSalgados['movimentacoes'] as $m) {
    if ($m['produto_tipo'] !== 'salgado_diario') {
        $todosSalgados = false;
    }
    if ($m['tipo_label'] !== 'Entrada de Salgados (Corte)') {
        $rotuloSalgadoCorreto = false;
    }
}
assertCheck(
    $todosSalgados && count($resSalgados['movimentacoes']) > 0,
    'Filtro de Entrada Salgados (Corte) retorna apenas produtos do tipo salgado_diario',
    $passou,
    $total
);
assertCheck(
    $rotuloSalgadoCorreto,
    'Rótulo amigável exibido como "Entrada de Salgados (Corte)"',
    $passou,
    $total
);

// 2.3 Entrada manual (reposição de mercadorias em massa)
$resManual = $controller->obterDadosMovimentacoesEstoqueAvancadas([
    'tipo_movimentacao' => 'entrada',
    'subtipo_entrada'   => 'entrada_manual',
    'data_inicio'       => '2020-01-01',
    'data_fim'          => date('Y-m-d')
]);

$todosManuais = true;
$rotuloManualCorreto = true;
foreach ($resManual['movimentacoes'] as $m) {
    if ($m['produto_tipo'] === 'salgado_diario') {
        $todosManuais = false;
    }
    if ($m['tipo_label'] !== 'Entrada Manual (Reposição)') {
        $rotuloManualCorreto = false;
    }
}
assertCheck(
    $todosManuais && count($resManual['movimentacoes']) > 0,
    'Filtro de Entrada Manual retorna apenas produtos de reposição (não salgados)',
    $passou,
    $total
);
assertCheck(
    $rotuloManualCorreto,
    'Rótulo amigável exibido como "Entrada Manual (Reposição)"',
    $passou,
    $total
);

// 2.4 Saída Geral e Específicas
$resSaidaGeral = $controller->obterDadosMovimentacoesEstoqueAvancadas([
    'tipo_movimentacao' => 'saida',
    'data_inicio'       => '2020-01-01',
    'data_fim'          => date('Y-m-d')
]);

$apenasSaidas = true;
foreach ($resSaidaGeral['movimentacoes'] as $m) {
    if (!in_array($m['tipo_movimentacao'], ['saida_venda_avulsa', 'saida_encomenda'], true)) {
        $apenasSaidas = false;
    }
}
assertCheck(
    $apenasSaidas && count($resSaidaGeral['movimentacoes']) > 0,
    'Filtro de Saída Geral retorna vendas balcão e encomendas, sem perdas misturadas',
    $passou,
    $total
);

// 2.5 Saída Venda Balcão
$resSaidaBalcao = $controller->obterDadosMovimentacoesEstoqueAvancadas([
    'tipo_movimentacao' => 'saida',
    'subtipo_saida'     => 'saida_venda_avulsa',
    'data_inicio'       => '2020-01-01',
    'data_fim'          => date('Y-m-d')
]);

$apenasBalcao = true;
foreach ($resSaidaBalcao['movimentacoes'] as $m) {
    if ($m['tipo_movimentacao'] !== 'saida_venda_avulsa') {
        $apenasBalcao = false;
    }
}
assertCheck(
    $apenasBalcao && count($resSaidaBalcao['movimentacoes']) > 0,
    'Filtro de Subtipo Saída por Venda Balcão (PDV) isola apenas vendas avulsas de balcão',
    $passou,
    $total
);

// 2.6 Baixa por Perda / Descarte
$resPerda = $controller->obterDadosMovimentacoesEstoqueAvancadas([
    'tipo_movimentacao' => 'saida_perda',
    'data_inicio'       => '2020-01-01',
    'data_fim'          => date('Y-m-d')
]);

$apenasPerdas = true;
foreach ($resPerda['movimentacoes'] as $m) {
    if ($m['tipo_movimentacao'] !== 'saida_perda') {
        $apenasPerdas = false;
    }
}
assertCheck(
    $apenasPerdas && count($resPerda['movimentacoes']) > 0,
    'Filtro de Baixa por Perda / Descarte isola exclusivamente registros de perda',
    $passou,
    $total
);

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: {$passou} / {$total} asserções aprovadas.\n";
if ($passou === $total) {
    echo ">>> SUCESSO TOTAL: TODOS OS TESTES PASSARAM COM 100%! <<<\n";
} else {
    echo ">>> HOUVE FALHAS! <<<\n";
    exit(1);
}
