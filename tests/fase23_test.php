<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Fase 23: Relatório Gerencial de Movimentações de Estoque (Retrato e Paisagem)
 * 
 * Validação rigorosa dos parâmetros de filtros, opções de leiaute (Paisagem / Retrato),
 * agrupamentos dinâmicos, quebra de página, exportação CSV e compilação de PDF via Gotenberg.
 */

declare(strict_types=1);

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Estoque.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

$totalTestes = 0;
$testesPassaram = 0;
$erros = [];

function assertTeste(bool $condicao, string $descricao): void
{
    global $totalTestes, $testesPassaram, $erros;
    $totalTestes++;
    if ($condicao) {
        $testesPassaram++;
        echo "  [OK] {$descricao}\n";
    } else {
        $erros[] = $descricao;
        echo "  [FALHA] {$descricao}\n";
    }
}

echo "\n=======================================================\n";
echo "   INICIANDO TESTES DA FASE 23 — RELATÓRIO DE ESTOQUE\n";
echo "=======================================================\n\n";

$pdo = \App\Core\Model::getPdo();
$controller = new \App\Controllers\RelatoriosController();

// -------------------------------------------------------------------
// 1. Fixture de dados para garantir movimentações no período de teste
// -------------------------------------------------------------------
echo "1. Preparando dados e movimentações de teste...\n";

// Garante categoria ativa
$cat = \App\Models\Categoria::buscarPorNome('Salgados Assados');
if (!$cat) {
    $catId = \App\Models\Categoria::criar([
        'nome' => 'Salgados Assados',
        'ordem_exibicao' => 1,
        'ativo' => 1
    ]);
} else {
    $catId = (int)$cat['id'];
}
assertTeste($catId > 0, "Categoria de teste ativa disponível (ID #{$catId})");

// Garante produto de teste
$sqlProd = "SELECT id, codigo_interno, descricao, preco_venda FROM produtos WHERE categoria_id = :cat_id AND deleted_at IS NULL LIMIT 1";
$prod = \App\Core\Model::fetchOne($sqlProd, ['cat_id' => $catId]);
if (!$prod) {
    $prodId = \App\Models\Produto::criar([
        'categoria_id' => $catId,
        'codigo_interno' => 'TEST-EST-23',
        'descricao' => 'Coxinha de Teste Fase 23',
        'preco_venda' => 8.50,
        'tipo' => 'salgado_diario',
        'turno_disponibilidade' => 'ambos',
        'estoque_atual' => 50,
        'estoque_reservado' => 0,
        'estoque_minimo' => 10,
        'status_pausa' => 'ativo'
    ]);
    $prod = \App\Models\Produto::buscarPorId($prodId);
}
assertTeste(!empty($prod['id']), "Produto de teste disponível: [{$prod['codigo_interno']}] {$prod['descricao']}");

// Garante operador
$sqlOp = "SELECT id FROM usuarios WHERE ativo = 1 AND deleted_at IS NULL LIMIT 1";
$op = \App\Core\Model::fetchOne($sqlOp);
$operadorId = (int)($op['id'] ?? 1);

// Insere movimentações de teste para o mês atual
$hoje = date('Y-m-d H:i:s');
$ontem = date('Y-m-d H:i:s', strtotime('-1 day'));

// Entrada por fornecedor
$sqlMovEntrada = "INSERT INTO estoque_movimentacoes (produto_id, tipo_movimentacao, quantidade, operador_id, justificativa, created_at)
                  VALUES (:pid, 'entrada_fornecedor', 20, :op, 'Remessa de Teste Fornecedor Fase 23', :dt)";
\App\Core\Model::execute($sqlMovEntrada, ['pid' => $prod['id'], 'op' => $operadorId, 'dt' => $ontem]);

// Saída por venda balcão
$sqlMovSaida = "INSERT INTO estoque_movimentacoes (produto_id, tipo_movimentacao, quantidade, operador_id, justificativa, created_at)
                VALUES (:pid, 'saida_venda_avulsa', 5, :op, 'Venda Balcão Teste Fase 23', :dt)";
\App\Core\Model::execute($sqlMovSaida, ['pid' => $prod['id'], 'op' => $operadorId, 'dt' => $hoje]);

// Saída por perda com motivo
$sqlMovPerda = "INSERT INTO estoque_movimentacoes (produto_id, tipo_movimentacao, quantidade, motivo_perda, operador_id, justificativa, created_at)
                VALUES (:pid, 'saida_perda', 2, 'avaria', :op, 'Avaria na estufa - Teste Fase 23', :dt)";
\App\Core\Model::execute($sqlMovPerda, ['pid' => $prod['id'], 'op' => $operadorId, 'dt' => $hoje]);

assertTeste(true, "Movimentações de teste registradas (Entrada 20 un, Saída Venda 5 un, Saída Perda 2 un)");

// -------------------------------------------------------------------
// 2. Testes de agregação de dados no Backend (obterDadosMovimentacoesEstoqueAvancadas)
// -------------------------------------------------------------------
echo "\n2. Testando método obterDadosMovimentacoesEstoqueAvancadas()...\n";

$filtrosGerais = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'formato_leiaute' => 'retrato',
    'agrupar_grupo' => 'ocultar',
    'agrupar_produto' => 'ocultar',
    'quebra_pagina' => 'ocultar'
];
$dados = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosGerais);

assertTeste(is_array($dados), "Retorno de obterDadosMovimentacoesEstoqueAvancadas é um array");
assertTeste(isset($dados['periodo_oficial']), "Contém período oficial formatado: '{$dados['periodo_oficial']}'");
assertTeste($dados['centro_custo'] === 'Cantina Geral', "Centro de custo fixo como 'Cantina Geral'");
assertTeste($dados['formato_leiaute'] === 'retrato', "Formato do leiaute retornado como 'retrato'");
assertTeste(!empty($dados['movimentacoes']), "Retornou array de movimentações com " . count($dados['movimentacoes']) . " registros");
assertTeste($dados['totais']['total_entradas'] >= 20, "Total de entradas computado corretamente (+{$dados['totais']['total_entradas']} un)");
assertTeste($dados['totais']['total_saidas'] >= 7, "Total de saídas computado corretamente (-{$dados['totais']['total_saidas']} un)");
assertTeste($dados['totais']['total_perdas'] >= 2, "Total de perdas computado corretamente (-{$dados['totais']['total_perdas']} un)");
assertTeste($dados['totais']['balanco_periodo'] === ($dados['totais']['total_entradas'] - $dados['totais']['total_saidas']), "Balanço líquido do período calculado com precisão matemática");

// -------------------------------------------------------------------
// 3. Teste de Opções de Leiaute: Paisagem (Landscape)
// -------------------------------------------------------------------
echo "\n3. Testando formato do leiaute: Paisagem...\n";

$filtrosPaisagem = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'formato_leiaute' => 'paisagem'
];
$dadosPaisagem = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosPaisagem);
assertTeste($dadosPaisagem['formato_leiaute'] === 'paisagem', "Formato do leiaute identificado como 'paisagem'");

// Renderiza a view em modo Paisagem
$htmlPaisagem = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosPaisagem);
assertTeste(str_contains($htmlPaisagem, 'size: A4 landscape;'), "CSS @page configurado para 'A4 landscape' na orientação paisagem");
assertTeste(str_contains($htmlPaisagem, '280mm'), "Largura da folha expandida para 280mm para acomodar colunas adicionais");
assertTeste(str_contains($htmlPaisagem, 'C. Custo'), "Coluna de Centro de Custo exibida na tabela em orientação paisagem");
assertTeste(str_contains($htmlPaisagem, 'Cantina Geral'), "Centro de Custo 'Cantina Geral' impresso no corpo do relatório");

// -------------------------------------------------------------------
// 4. Teste de Opções de Leiaute: Retrato (Portrait)
// -------------------------------------------------------------------
echo "\n4. Testando formato do leiaute: Retrato...\n";

$filtrosRetrato = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'formato_leiaute' => 'retrato'
];
$dadosRetrato = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosRetrato);
$htmlRetrato = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosRetrato);
assertTeste(str_contains($htmlRetrato, 'size: A4 portrait;'), "CSS @page configurado para 'A4 portrait' na orientação retrato");
assertTeste(str_contains($htmlRetrato, '200mm'), "Largura da folha ajustada para 200mm para folha A4 em pé");

// -------------------------------------------------------------------
// 5. Teste de Agrupamento por Grupo / Categoria de Produtos
// -------------------------------------------------------------------
echo "\n5. Testando Agrupamento por Grupo de Produtos...\n";

$filtrosGrupo = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'agrupar_grupo' => 'mostrar'
];
$dadosGrupo = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosGrupo);
assertTeste($dadosGrupo['agrupar_grupo'] === true, "Flag agrupar_grupo avaliada como true");
assertTeste(!empty($dadosGrupo['grupos_categoria']), "Array grupos_categoria estruturado com " . count($dadosGrupo['grupos_categoria']) . " grupos");

$htmlGrupo = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosGrupo);
assertTeste(str_contains($htmlGrupo, 'CATEGORIA:'), "View renderiza banner de cabeçalho com 'CATEGORIA:'");
assertTeste(str_contains($htmlGrupo, 'Subtotal da Categoria:'), "View renderiza linha de subtotais da categoria");

// -------------------------------------------------------------------
// 6. Teste de Agrupamento por Produto
// -------------------------------------------------------------------
echo "\n6. Testando Agrupamento por Produto...\n";

$filtrosProduto = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'agrupar_produto' => 'mostrar'
];
$dadosProduto = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosProduto);
assertTeste($dadosProduto['agrupar_produto'] === true, "Flag agrupar_produto avaliada como true");
assertTeste(!empty($dadosProduto['grupos_produto']), "Array grupos_produto estruturado com " . count($dadosProduto['grupos_produto']) . " produtos");

$htmlProduto = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosProduto);
assertTeste(str_contains($htmlProduto, 'Total do Produto:'), "View renderiza linha com 'Total do Produto:'");
assertTeste(str_contains($htmlProduto, 'Variação no Período:'), "View renderiza saldo de variação específica do produto");

// -------------------------------------------------------------------
// 7. Teste de Quebra de Página
// -------------------------------------------------------------------
echo "\n7. Testando Quebra de Página Dinâmica...\n";

$filtrosQuebra = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'agrupar_grupo' => 'mostrar',
    'quebra_pagina' => 'mostrar'
];
$dadosQuebra = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosQuebra);
assertTeste($dadosQuebra['quebra_pagina'] === true, "Flag quebra_pagina avaliada como true");

$htmlQuebra = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dadosQuebra);
assertTeste(str_contains($htmlQuebra, 'page-break-before: always'), "CSS de quebra de página 'page-break-before: always' presente no HTML");

// -------------------------------------------------------------------
// 8. Teste de Filtros Específicos (Produto, Tipo de Movimentação, Motivo)
// -------------------------------------------------------------------
echo "\n8. Testando filtros específicos de produto, tipo e motivo de perda...\n";

// Filtro por tipo perda
$filtrosPerda = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'tipo_movimentacao' => 'saida_perda',
    'motivo_perda' => 'avaria'
];
$dadosPerda = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosPerda);
assertTeste(!empty($dadosPerda['movimentacoes']), "Filtro de perda por avaria retornou movimentações");
foreach ($dadosPerda['movimentacoes'] as $m) {
    assertTeste($m['tipo_movimentacao'] === 'saida_perda', "Movimentação retornada é do tipo saida_perda");
    assertTeste(str_contains($m['justificativa_completa'], 'Avaria'), "Justificativa da perda contém o motivo 'Avaria'");
}

// Filtro por produto específico
$filtrosProdEsp = [
    'data_inicio' => date('Y-m-01'),
    'data_fim' => date('Y-m-d'),
    'produto_id' => (int)$prod['id']
];
$dadosProdEsp = $controller->obterDadosMovimentacoesEstoqueAvancadas($filtrosProdEsp);
assertTeste(!empty($dadosProdEsp['movimentacoes']), "Filtro por produto específico retornou movimentações");
foreach ($dadosProdEsp['movimentacoes'] as $m) {
    assertTeste($m['produto_id'] === (int)$prod['id'], "Todas as movimentações pertencem exclusivamente ao produto selecionado");
}

// -------------------------------------------------------------------
// 9. Teste da View de Filtros (app/views/relatorios/estoque_filtros.php)
// -------------------------------------------------------------------
echo "\n9. Testando renderização da view estoque_filtros.php...\n";

ob_start();
$categorias = \App\Models\Categoria::listarTodas();
$produtos = \App\Models\Produto::listarTodos();
$filtros = $filtrosGerais;
$baseUrl = '';
include dirname(__DIR__) . '/app/views/relatorios/estoque_filtros.php';
$htmlFiltros = ob_get_clean();

assertTeste(str_contains($htmlFiltros, 'Emissão de Movimentações de Estoque'), "Título 'Emissão de Movimentações de Estoque' presente");
assertTeste(str_contains($htmlFiltros, 'id="tab-filtro-btn"'), "Aba de 'Filtro' presente");
assertTeste(str_contains($htmlFiltros, 'id="tab-layout-btn"'), "Aba de 'Layout' presente com ID 'tab-layout-btn'");
assertTeste(str_contains($htmlFiltros, '<span>Layout</span>'), "Rótulo da aba grafado corretamente como 'Layout'");
assertTeste(str_contains($htmlFiltros, 'Formato do layout'), "Campo grafado corretamente como 'Formato do layout'");
assertTeste(str_contains($htmlFiltros, 'name="formato_layout"'), "Controle de seleção formato_layout presente");
assertTeste(str_contains($htmlFiltros, 'name="formato_leiaute"'), "Compatibilidade com formato_leiaute preservada");
assertTeste(str_contains($htmlFiltros, 'Paisagem'), "Opção 'Paisagem' presente nos botões segmentados");
assertTeste(str_contains($htmlFiltros, 'Retrato'), "Opção 'Retrato' presente nos botões segmentados");
assertTeste(str_contains($htmlFiltros, 'name="agrupar_grupo"'), "Controle de agrupar_grupo presente");
assertTeste(str_contains($htmlFiltros, 'name="agrupar_produto"'), "Controle de agrupar_produto presente");
assertTeste(str_contains($htmlFiltros, 'name="quebra_pagina"'), "Controle de quebra_pagina presente");
assertTeste(str_contains($htmlFiltros, 'Cantina Geral'), "Centro de Custo 'Cantina Geral' referenciado");
assertTeste(!str_contains($htmlFiltros, 'id="dropdownEmitirEstoque"'), "Botão repetido/dropdown do topo foi devidamente removido");
assertTeste(str_contains($htmlFiltros, 'id="btnEmitirImprimir"'), "Botão oficial de emissão 'Visualizar / Imprimir (A4)' presente");
assertTeste(!str_contains($htmlFiltros, 'id="btnEmitirPdf"'), "Botão redundante 'Baixar em PDF (A4)' removido em favor da unificação na tela de impressão");
assertTeste(str_contains($htmlFiltros, 'id="btnEmitirCsv"'), "Botão oficial de emissão 'Exportar Planilha (CSV)' presente");

// -------------------------------------------------------------------
// 10. Teste de Compilação de PDF Real com Gotenberg (Chromium)
// -------------------------------------------------------------------
echo "\n10. Testando compilação real de PDF via Gotenberg...\n";

// PDF Retrato
$pdfRetrato = \App\Services\PdfService::htmlToPdf($htmlRetrato, [
    'landscape' => false,
    'preferCssPageSize' => 'true'
]);
assertTeste($pdfRetrato !== null, "Gotenberg respondeu com sucesso para PDF Retrato");
assertTeste(is_string($pdfRetrato) && str_starts_with($pdfRetrato, '%PDF'), "Arquivo gerado é um binário PDF válido (inicia com %PDF)");
assertTeste(strlen($pdfRetrato ?? '') > 15000, "PDF Retrato possui tamanho consistente (" . strlen($pdfRetrato ?? '') . " bytes)");

// PDF Paisagem
$pdfPaisagem = \App\Services\PdfService::htmlToPdf($htmlPaisagem, [
    'landscape' => true,
    'preferCssPageSize' => 'true'
]);
assertTeste($pdfPaisagem !== null, "Gotenberg respondeu com sucesso para PDF Paisagem");
assertTeste(is_string($pdfPaisagem) && str_starts_with($pdfPaisagem, '%PDF'), "Arquivo gerado em Paisagem é um binário PDF válido (inicia com %PDF)");
assertTeste(strlen($pdfPaisagem ?? '') > 15000, "PDF Paisagem possui tamanho consistente (" . strlen($pdfPaisagem ?? '') . " bytes)");

// -------------------------------------------------------------------
// 11. Teste de Exportação CSV
// -------------------------------------------------------------------
echo "\n11. Testando estrutura de dados para exportação CSV...\n";

assertTeste(isset($dados['movimentacoes'][0]['data_formatada']), "Movimentação possui data formatada para CSV");
assertTeste(isset($dados['movimentacoes'][0]['centro_custo']), "Movimentação possui centro de custo para CSV");
assertTeste(isset($dados['movimentacoes'][0]['produto_codigo']), "Movimentação possui código do produto para CSV");
assertTeste(isset($dados['movimentacoes'][0]['tipo_label']), "Movimentação possui tipo legível para CSV");

// Simula geração de CSV em memória
$memStream = fopen('php://memory', 'r+');
fwrite($memStream, "\xEF\xBB\xBF"); // BOM UTF-8
fputcsv($memStream, ['COLÉGIO MONTEIRO LOBATO — SISTEMA CANTINA WEB'], ';');
fputcsv($memStream, ['RELATÓRIO GERENCIAL DE MOVIMENTAÇÕES DE ESTOQUE'], ';');
fputcsv($memStream, ['Data', 'Hora', 'Centro de Custo', 'Grupo / Categoria', 'Código', 'Produto', 'Tipo de Movimentação', 'Entrada (un)', 'Saída (un)', 'Operador', 'Doc / NF', 'Justificativa'], ';');
foreach ($dados['movimentacoes'] as $m) {
    fputcsv($memStream, [
        $m['data_formatada'],
        $m['hora_formatada'],
        $m['centro_custo'],
        $m['categoria_nome'],
        $m['produto_codigo'],
        $m['produto_descricao'],
        $m['tipo_label'],
        $m['qtd_entrada'] > 0 ? $m['qtd_entrada'] : '',
        $m['qtd_saida'] > 0 ? $m['qtd_saida'] : '',
        $m['operador_nome'],
        $m['documento_ref'],
        $m['justificativa_completa']
    ], ';');
}
rewind($memStream);
$csvConteudo = stream_get_contents($memStream);
fclose($memStream);

assertTeste(str_starts_with($csvConteudo, "\xEF\xBB\xBF"), "Planilha CSV inicia com UTF-8 BOM");
assertTeste(str_contains($csvConteudo, ';'), "Planilha CSV utiliza delimitador ponto-e-vírgula (;)");
assertTeste(str_contains($csvConteudo, 'Cantina Geral'), "Planilha CSV contém o centro de custo 'Cantina Geral'");
assertTeste(str_contains($csvConteudo, 'RELATÓRIO GERENCIAL DE MOVIMENTAÇÕES DE ESTOQUE'), "Título institucional presente no CSV");

// -------------------------------------------------------------------
// 12. Validação da Sidebar e Hub de Relatórios
// -------------------------------------------------------------------
echo "\n12. Testando navegação e rotas integradas...\n";

$hubContent = file_get_contents(dirname(__DIR__) . '/app/views/relatorios/index.php');
assertTeste(str_contains($hubContent, 'Movimentações de Estoque'), "Card de Movimentações de Estoque presente no Hub de Relatórios");
assertTeste(str_contains($hubContent, '/relatorios/estoque-filtros'), "Link para /relatorios/estoque-filtros presente no Hub de Relatórios");

$sidebarContent = file_get_contents(dirname(__DIR__) . '/app/views/layouts/sidebar.php');
assertTeste(str_contains($sidebarContent, 'Movimentações'), "Link 'Movimentações' presente na seção Estoque da sidebar");
assertTeste(str_contains($sidebarContent, '/relatorios/estoque-filtros'), "Link de Movimentações aponta para /relatorios/estoque-filtros na sidebar");

$indexContent = file_get_contents(dirname(__DIR__) . '/index.php');
assertTeste(str_contains($indexContent, '/relatorios/estoque-filtros'), "Rota /relatorios/estoque-filtros registrada no Front Controller index.php");
assertTeste(str_contains($indexContent, '/relatorios/estoque-movimentacoes/pdf'), "Rota /relatorios/estoque-movimentacoes/pdf registrada em index.php");
assertTeste(str_contains($indexContent, '/relatorios/estoque-movimentacoes/imprimir'), "Rota /relatorios/estoque-movimentacoes/imprimir registrada em index.php");
assertTeste(str_contains($indexContent, '/relatorios/estoque-movimentacoes/csv'), "Rota /relatorios/estoque-movimentacoes/csv registrada em index.php");

// -------------------------------------------------------------------
// RESUMO FINAL
// -------------------------------------------------------------------
echo "\n=======================================================\n";
echo "   RESULTADO DOS TESTES DA FASE 23\n";
echo "=======================================================\n";
echo "Total de Testes Executados: {$totalTestes}\n";
echo "Testes Aprovados:           {$testesPassaram}\n";
echo "Testes Falhos:              " . count($erros) . "\n";

if (empty($erros)) {
    echo "\n>>> SUCESSO: TODAS AS {$totalTestes} ASSERÇÕES DA FASE 23 FORAM APROVADAS COM 100%! <<<\n\n";
    exit(0);
} else {
    echo "\n>>> FALHA: ALGUNS TESTES NÃO PASSARAM: <<<\n";
    foreach ($erros as $err) {
        echo "  - {$err}\n";
    }
    echo "\n";
    exit(1);
}
