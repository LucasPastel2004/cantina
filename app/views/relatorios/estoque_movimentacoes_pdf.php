<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Gerencial de Movimentações de Estoque (Retrato e Paisagem)
 * 
 * Conforme docs/alterações sistema guideline/filtro movimentação estoque.pdf,
 * docs/DESIGN.md e padrões arquiteturais do sistema.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl         = $baseUrl ?? '';
$periodoOficial  = $periodo_oficial ?? (date('d/m/Y') . ' - ' . date('d/m/Y'));
$dataInicio      = $data_inicio ?? date('Y-m-d');
$dataFim         = $data_fim ?? date('Y-m-d');
$centroCusto     = $centro_custo ?? 'Cantina Geral';
$catFiltro       = $categoria_filtro ?? 'Todas as Categorias';
$prodFiltro      = $produto_filtro ?? 'Todos os Produtos';

$formatoLayout   = $formato_layout ?? $formato_leiaute ?? 'retrato';
$formatoLeiaute  = $formatoLayout;
$isPaisagem      = ($formatoLayout === 'paisagem');

$agruparGrupo    = !empty($agrupar_grupo);
$agruparProduto  = !empty($agrupar_produto);
$quebraPagina    = !empty($quebra_pagina);

$movimentacoes   = $movimentacoes ?? [];
$gruposCategoria = $grupos_categoria ?? [];
$gruposProduto   = $grupos_produto ?? [];
$totais          = $totais ?? [
    'total_entradas'      => 0,
    'total_saidas'        => 0,
    'total_vendas'        => 0,
    'total_perdas'        => 0,
    'balanco_periodo'     => 0,
    'total_movimentacoes' => 0
];

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);

// =============================================================================
// MOTOR DE PAGINAÇÃO EM FOLHAS A4 FÍSICAS (VISUALIZAÇÃO EM TELA E IMPRESSÃO)
// =============================================================================
$limiteP1      = $isPaisagem ? 11 : 16;
$limiteOutras  = $isPaisagem ? 14 : 20;
$reservaTotais = $isPaisagem ? 3 : 4;

$totalItens = count($movimentacoes);
$paginas = [];

if ($totalItens === 0) {
    $paginas[] = [
        'modo'        => 'vazio',
        'is_primeira' => true,
        'is_ultima'   => true,
        'tem_totais'  => false
    ];
} elseif ($agruparGrupo) {
    // -------------------------------------------------------------------------
    // CENÁRIO 1: AGRUPADO POR GRUPO / CATEGORIA
    // -------------------------------------------------------------------------
    if ($quebraPagina) {
        foreach ($gruposCategoria as $grp) {
            $itensGrp = $grp['itens'];
            $totalGrp = count($itensGrp);
            $offset = 0;
            $subPag = 0;

            while ($offset < $totalGrp) {
                $isPrimeiraRelatorio = empty($paginas);
                $cap = $isPrimeiraRelatorio ? ($limiteP1 - 3) : ($limiteOutras - 3);
                $restantes = $totalGrp - $offset;
                $qtd = min($restantes, $cap);
                $fatia = array_slice($itensGrp, $offset, $qtd);
                $offset += $qtd;
                $isUltimaFatia = ($offset >= $totalGrp);

                $paginas[] = [
                    'modo'               => 'categoria',
                    'is_primeira'        => $isPrimeiraRelatorio,
                    'is_ultima'          => false,
                    'tem_totais'         => false,
                    'categoria_nome'     => $grp['categoria_nome'],
                    'is_continua'        => ($subPag > 0),
                    'itens'              => $fatia,
                    'tem_subtotal'       => $isUltimaFatia,
                    'total_entradas'     => $grp['total_entradas'],
                    'total_saidas'       => $grp['total_saidas'],
                    'balanco'            => $grp['balanco'],
                    'total_lancamentos'  => $totalGrp
                ];
                $subPag++;
            }
        }
    } else {
        $paginaAtual = [
            'modo'         => 'categoria_continuo',
            'is_primeira'  => true,
            'is_ultima'    => false,
            'tem_totais'   => false,
            'blocos'       => []
        ];
        $itensNaPagina = 0;
        $capAtual = $limiteP1;

        foreach ($gruposCategoria as $grp) {
            $bloco = [
                'categoria_nome'    => $grp['categoria_nome'],
                'itens'             => [],
                'tem_subtotal'      => false,
                'total_entradas'    => $grp['total_entradas'],
                'total_saidas'      => $grp['total_saidas'],
                'balanco'           => $grp['balanco'],
                'total_lancamentos' => count($grp['itens']),
                'is_continua'       => false
            ];

            if ($itensNaPagina + 3 > $capAtual) {
                $paginas[] = $paginaAtual;
                $paginaAtual = [
                    'modo'        => 'categoria_continuo',
                    'is_primeira' => false,
                    'is_ultima'   => false,
                    'tem_totais'  => false,
                    'blocos'      => []
                ];
                $itensNaPagina = 0;
                $capAtual = $limiteOutras;
            }
            $itensNaPagina += 2;

            foreach ($grp['itens'] as $m) {
                if ($itensNaPagina >= $capAtual) {
                    $paginaAtual['blocos'][] = $bloco;
                    $paginas[] = $paginaAtual;

                    $paginaAtual = [
                        'modo'        => 'categoria_continuo',
                        'is_primeira' => false,
                        'is_ultima'   => false,
                        'tem_totais'  => false,
                        'blocos'      => []
                    ];
                    $itensNaPagina = 2;
                    $capAtual = $limiteOutras;

                    $bloco = [
                        'categoria_nome'    => $grp['categoria_nome'],
                        'itens'             => [],
                        'tem_subtotal'      => false,
                        'total_entradas'    => $grp['total_entradas'],
                        'total_saidas'      => $grp['total_saidas'],
                        'balanco'           => $grp['balanco'],
                        'total_lancamentos' => count($grp['itens']),
                        'is_continua'       => true
                    ];
                }
                $bloco['itens'][] = $m;
                $itensNaPagina++;
            }

            $bloco['tem_subtotal'] = true;
            $itensNaPagina += 1;
            $paginaAtual['blocos'][] = $bloco;
        }

        if (!empty($paginaAtual['blocos'])) {
            $paginas[] = $paginaAtual;
        }
    }

    if (!empty($paginas)) {
        $paginas[count($paginas) - 1]['tem_totais'] = true;
        $paginas[count($paginas) - 1]['is_ultima']  = true;
    }
} elseif ($agruparProduto) {
    // -------------------------------------------------------------------------
    // CENÁRIO 2: AGRUPADO POR PRODUTO
    // -------------------------------------------------------------------------
    if ($quebraPagina) {
        foreach ($gruposProduto as $pGrp) {
            $itensGrp = $pGrp['itens'];
            $totalGrp = count($itensGrp);
            $offset = 0;
            $subPag = 0;

            while ($offset < $totalGrp) {
                $isPrimeiraRelatorio = empty($paginas);
                $cap = $isPrimeiraRelatorio ? ($limiteP1 - 3) : ($limiteOutras - 3);
                $restantes = $totalGrp - $offset;
                $qtd = min($restantes, $cap);
                $fatia = array_slice($itensGrp, $offset, $qtd);
                $offset += $qtd;
                $isUltimaFatia = ($offset >= $totalGrp);

                $paginas[] = [
                    'modo'               => 'produto',
                    'is_primeira'        => $isPrimeiraRelatorio,
                    'is_ultima'          => false,
                    'tem_totais'         => false,
                    'produto_codigo'     => $pGrp['produto_codigo'],
                    'produto_descricao'  => $pGrp['produto_descricao'],
                    'categoria_nome'     => $pGrp['categoria_nome'],
                    'is_continua'        => ($subPag > 0),
                    'itens'              => $fatia,
                    'tem_subtotal'       => $isUltimaFatia,
                    'total_entradas'     => $pGrp['total_entradas'],
                    'total_saidas'       => $pGrp['total_saidas'],
                    'balanco'            => $pGrp['balanco'],
                    'total_lancamentos'  => $totalGrp
                ];
                $subPag++;
            }
        }
    } else {
        $paginaAtual = [
            'modo'         => 'produto_continuo',
            'is_primeira'  => true,
            'is_ultima'    => false,
            'tem_totais'   => false,
            'blocos'       => []
        ];
        $itensNaPagina = 0;
        $capAtual = $limiteP1;

        foreach ($gruposProduto as $pGrp) {
            $bloco = [
                'produto_codigo'    => $pGrp['produto_codigo'],
                'produto_descricao' => $pGrp['produto_descricao'],
                'categoria_nome'    => $pGrp['categoria_nome'],
                'itens'             => [],
                'tem_subtotal'      => false,
                'total_entradas'    => $pGrp['total_entradas'],
                'total_saidas'      => $pGrp['total_saidas'],
                'balanco'           => $pGrp['balanco'],
                'total_lancamentos' => count($pGrp['itens']),
                'is_continua'       => false
            ];

            if ($itensNaPagina + 3 > $capAtual) {
                $paginas[] = $paginaAtual;
                $paginaAtual = [
                    'modo'        => 'produto_continuo',
                    'is_primeira' => false,
                    'is_ultima'   => false,
                    'tem_totais'  => false,
                    'blocos'      => []
                ];
                $itensNaPagina = 0;
                $capAtual = $limiteOutras;
            }
            $itensNaPagina += 2;

            foreach ($pGrp['itens'] as $m) {
                if ($itensNaPagina >= $capAtual) {
                    $paginaAtual['blocos'][] = $bloco;
                    $paginas[] = $paginaAtual;

                    $paginaAtual = [
                        'modo'        => 'produto_continuo',
                        'is_primeira' => false,
                        'is_ultima'   => false,
                        'tem_totais'  => false,
                        'blocos'      => []
                    ];
                    $itensNaPagina = 2;
                    $capAtual = $limiteOutras;

                    $bloco = [
                        'produto_codigo'    => $pGrp['produto_codigo'],
                        'produto_descricao' => $pGrp['produto_descricao'],
                        'categoria_nome'    => $pGrp['categoria_nome'],
                        'itens'             => [],
                        'tem_subtotal'      => false,
                        'total_entradas'    => $pGrp['total_entradas'],
                        'total_saidas'      => $pGrp['total_saidas'],
                        'balanco'           => $pGrp['balanco'],
                        'total_lancamentos' => count($pGrp['itens']),
                        'is_continua'       => true
                    ];
                }
                $bloco['itens'][] = $m;
                $itensNaPagina++;
            }

            $bloco['tem_subtotal'] = true;
            $itensNaPagina += 1;
            $paginaAtual['blocos'][] = $bloco;
        }

        if (!empty($paginaAtual['blocos'])) {
            $paginas[] = $paginaAtual;
        }
    }

    if (!empty($paginas)) {
        $paginas[count($paginas) - 1]['tem_totais'] = true;
        $paginas[count($paginas) - 1]['is_ultima']  = true;
    }
} else {
    // -------------------------------------------------------------------------
    // CENÁRIO 3: LISTAGEM GERAL CRONOLÓGICA (PAGINAÇÃO AUTOMÁTICA EM FOLHAS)
    // -------------------------------------------------------------------------
    if ($totalItens <= ($limiteP1 - $reservaTotais)) {
        $paginas[] = [
            'modo'        => 'geral',
            'is_primeira' => true,
            'is_ultima'   => true,
            'tem_totais'  => true,
            'itens'       => $movimentacoes
        ];
    } else {
        $offset = 0;
        $qtdP1 = min($totalItens, $limiteP1);
        $paginas[] = [
            'modo'        => 'geral',
            'is_primeira' => true,
            'is_ultima'   => false,
            'tem_totais'  => false,
            'itens'       => array_slice($movimentacoes, 0, $qtdP1)
        ];
        $offset += $qtdP1;

        while ($offset < $totalItens) {
            $restantes = $totalItens - $offset;
            if ($restantes <= ($limiteOutras - $reservaTotais)) {
                $paginas[] = [
                    'modo'        => 'geral',
                    'is_primeira' => false,
                    'is_ultima'   => true,
                    'tem_totais'  => true,
                    'itens'       => array_slice($movimentacoes, $offset, $restantes)
                ];
                $offset += $restantes;
                break;
            } elseif ($restantes <= $limiteOutras) {
                $qtd = (int)ceil($restantes / 2);
                $paginas[] = [
                    'modo'        => 'geral',
                    'is_primeira' => false,
                    'is_ultima'   => false,
                    'tem_totais'  => false,
                    'itens'       => array_slice($movimentacoes, $offset, $qtd)
                ];
                $offset += $qtd;

                $paginas[] = [
                    'modo'        => 'geral',
                    'is_primeira' => false,
                    'is_ultima'   => true,
                    'tem_totais'  => true,
                    'itens'       => array_slice($movimentacoes, $offset)
                ];
                $offset = $totalItens;
                break;
            } else {
                $paginas[] = [
                    'modo'        => 'geral',
                    'is_primeira' => false,
                    'is_ultima'   => false,
                    'tem_totais'  => false,
                    'itens'       => array_slice($movimentacoes, $offset, $limiteOutras)
                ];
                $offset += $limiteOutras;
            }
        }
    }
}

$totalPaginas = count($paginas);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Movimentações de Estoque — Colégio Monteiro Lobato</title>

    <style>
        /* ==========================================================================
           CONFIGURAÇÕES DE PÁGINA E CSS PRINT (ESPECÍFICAS POR ORIENTAÇÃO)
           ========================================================================== */
        @page {
            size: <?php echo $isPaisagem ? 'A4 landscape' : 'A4 portrait'; ?>;
            margin: 0;
        }

        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: <?php echo $isPaisagem ? '9.5px' : '9px'; ?>;
            line-height: 1.35;
            color: #0f172a;
            background-color: #cbd5e1;
            margin: 0;
            padding: 20px 0 40px 0;
        }

        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1, "zero" 1;
        }

        /* Barra de Ações Superior (visível somente na tela) */
        .no-print-bar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: <?php echo $isPaisagem ? '280mm' : '200mm'; ?>;
            max-width: calc(100% - 32px);
            margin: 0 auto 16px auto;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 10px;
            z-index: 1000;
        }
        .no-print-bar .zoom-controls {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            padding: 2px 6px;
        }
        .no-print-bar .btn-zoom {
            background: transparent;
            color: #ffffff;
            border: none;
            width: 24px;
            height: 24px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: all 0.15s ease;
        }
        .no-print-bar .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .no-print-bar .btn-zoom-val {
            background: transparent;
            color: #ffffff;
            border: none;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 3px;
            font-variant-numeric: tabular-nums;
            min-width: 44px;
            text-align: center;
            transition: all 0.15s ease;
        }
        .no-print-bar .btn-zoom-val:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: underline;
        }
        .no-print-bar .btn-action {
            background: #ffffff;
            color: #0f172a;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease-in-out;
        }
        .no-print-bar .btn-action:hover {
            background: #f1f5f9;
            color: #1d4ed8;
        }
        .no-print-bar .btn-primary {
            background: #1d4ed8;
            color: #ffffff;
        }
        .no-print-bar .btn-primary:hover {
            background: #1e40af;
            color: #ffffff;
        }

        /* Área de Zoom Dinâmico das Páginas A4 */
        .document-zoom-area {
            zoom: var(--doc-zoom, 1);
            transform-origin: top center;
            transition: zoom 0.1s ease-out;
        }

        /* Folha Física A4 Simulada (com quebra visual e estrutural) */
        .sheet-container.page-sheet {
            background-color: #ffffff;
            width: <?php echo $isPaisagem ? '280mm' : '200mm'; ?>;
            max-width: calc(100% - 32px);
            min-height: <?php echo $isPaisagem ? '195mm' : '280mm'; ?>;
            margin: 0 auto 24px auto;
            padding: 10mm 14mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-radius: 2px;
            position: relative;
            box-sizing: border-box;
            overflow: hidden; /* Previne rigorosamente qualquer transbordo lateral */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Conteúdo Principal da Folha */
        .page-body {
            flex: 1 1 auto;
        }

        /* Cabeçalho Oficial do Colégio */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .doc-header.compact {
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #64748b;
        }
        .doc-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .doc-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
        }
        .doc-logo-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        .doc-titles h1 {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .doc-titles.compact h1 {
            font-size: 12px;
            font-weight: 700;
        }
        .doc-titles h2 {
            font-size: 11px;
            font-weight: 700;
            color: #1d4ed8;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .doc-meta {
            text-align: right;
            font-size: 8.5px;
            color: #334155;
            line-height: 1.35;
        }
        .doc-page-badge {
            font-weight: 700;
            color: #1d4ed8;
        }

        /* Faixa de Parâmetros */
        .params-strip {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 10px;
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 8.5px;
        }
        .params-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .params-label {
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 8px;
        }
        .params-val {
            font-weight: 600;
            color: #0f172a;
        }

        /* Tabelas Analíticas Oficiais (Estética Excel com Laterais e Linhas Pretas entre Itens) */
        .table-estoque {
            width: 100% !important;
            table-layout: fixed !important; /* Bloqueia rigorosamente transbordos */
            border-collapse: collapse !important;
            margin-bottom: 10px;
            font-size: <?php echo $isPaisagem ? '9px' : '8.5px'; ?>;
            border: 1px solid #000000 !important;
            box-sizing: border-box !important;
        }
        .table-estoque thead {
            display: table-header-group;
        }
        .table-estoque tfoot {
            display: table-footer-group;
        }
        .table-estoque tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .table-estoque th {
            background-color: #ffffff !important;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.02em;
            padding: 4px 5px;
            border: 1px solid #000000 !important;
            text-align: left;
            overflow: hidden;
            word-break: break-word;
        }
        .table-estoque th:first-child,
        .table-estoque td:first-child {
            border-left: 1px solid #000000 !important;
        }
        .table-estoque th:last-child,
        .table-estoque td:last-child {
            border-right: 1px solid #000000 !important;
        }
        .table-estoque td {
            background-color: #ffffff !important;
            padding: 3px 5px;
            border: 1px solid #000000 !important; /* Linhas pretas estilo Excel */
            vertical-align: middle;
            color: #0f172a;
            overflow: hidden;
            word-break: break-word;
            overflow-wrap: break-word;
            word-wrap: break-word;
            hyphens: auto;
        }
        .table-estoque tr td {
            background-color: #ffffff !important;
        }
        .table-estoque tr:hover td {
            background-color: #ffffff !important;
        }

        .text-center { text-align: center !important; }
        .text-right  { text-align: right !important; }

        /* Colunas de Quantidade Numérica com Blindagem Anti-Quebra */
        .table-estoque th.col-qtd,
        .table-estoque td.col-qtd {
            white-space: nowrap !important;
            text-align: right !important;
            padding-left: 3px !important;
            padding-right: 4px !important;
        }

        .qtd-entrada {
            color: #15803d;
            font-weight: 700;
        }
        .qtd-saida {
            color: #b91c1c;
            font-weight: 700;
        }

        /* Banners de Agrupamento */
        .group-header {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9.5px;
            padding: 5px 8px;
            border-left: 4px solid #1d4ed8;
            margin-top: 8px;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .group-subtotal {
            background-color: #ffffff !important;
            border-top: 1px solid #000000;
            border-bottom: 2px solid #000000;
            font-weight: 700;
        }
        .group-subtotal td {
            background-color: #ffffff !important;
        }

        /* Quebras de Página Físicas e Lógicas */
        .page-break {
            page-break-before: always !important;
            break-before: page !important;
        }

        /* Resumo de Fechamento / Totais Gerais */
        .totais-box {
            background-color: #f8fafc;
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 10px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .totais-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            text-align: center;
        }
        .totais-item {
            border-right: 1px solid #cbd5e1;
            padding: 0 6px;
        }
        .totais-item:last-child {
            border-right: none;
        }
        .totais-label {
            font-size: 8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #475569;
            margin-bottom: 2px;
            display: block;
        }
        .totais-val {
            font-size: 12px;
            font-weight: 800;
        }

        /* Rodapé Oficial da Folha */
        .doc-footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            color: #64748b;
            flex-shrink: 0;
        }

        /* Reset em Impressão (@media print) */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet-container.page-sheet {
                width: <?php echo $isPaisagem ? '297mm' : '210mm'; ?> !important;
                max-width: <?php echo $isPaisagem ? '297mm' : '210mm'; ?> !important;
                min-height: <?php echo $isPaisagem ? '210mm' : '297mm'; ?> !important;
                margin: 0 auto !important;
                padding: 10mm 14mm !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                display: block !important;
                box-sizing: border-box !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .sheet-container.page-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
            .page-body {
                display: block !important;
            }
            .doc-footer {
                margin-top: 10px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .table-estoque {
                table-layout: fixed !important;
                width: 100% !important;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            thead {
                display: table-header-group !important;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Controle Superior (visível somente na tela) -->
    <div class="no-print-bar">
        <div style="font-size: 12px; font-weight: 600;">
            Relatório de Estoque • Orientação: <?php echo $isPaisagem ? 'Paisagem (Landscape)' : 'Retrato (Portrait)'; ?> • Total de Páginas: <?php echo $totalPaginas; ?>
        </div>

        <!-- Controles de Zoom para Conferência -->
        <div class="zoom-controls" title="Ajuste o zoom para conferir detalhes e números antes de imprimir ou salvar">
            <span style="display: inline-flex; align-items: center; color: #cbd5e1; margin-right: 3px;" title="Zoom">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </span>
            <button type="button" class="btn-zoom" id="btnZoomOut" title="Diminuir Zoom (Ctrl -)" onclick="alterarZoom(-0.15)">−</button>
            <button type="button" class="btn-zoom-val" id="btnZoomReset" title="Clique para redefinir para 100% (Ctrl 0)" onclick="resetarZoom()">100%</button>
            <button type="button" class="btn-zoom" id="btnZoomIn" title="Aumentar Zoom (Ctrl +)" onclick="alterarZoom(0.15)">+</button>
        </div>

        <div style="display: flex; gap: 8px;">
            <a href="<?php echo $baseUrl; ?>/relatorios/estoque-filtros" class="btn-action">
                ← Voltar aos Filtros
            </a>
            <button type="button" class="btn-action btn-primary" onclick="window.print()">
                🖨 Imprimir Documento
            </button>
            <button type="button" class="btn-action" onclick="fecharJanela()">
                ✕ Fechar
            </button>
        </div>
    </div>

    <!-- ÁREA ESCALÁVEL COM ZOOM PARA CONFERÊNCIA -->
    <div id="document-zoom-area" class="document-zoom-area">

    <!-- ITERAÇÃO DAS FOLHAS A4 (CADA UMA É UMA PÁGINA FÍSICA) -->
    <?php foreach ($paginas as $indexPagina => $pag): 
        $numPagina = $indexPagina + 1;
        $isPrimeira = $pag['is_primeira'];
        $isUltima   = $pag['is_ultima'];
    ?>
        <div class="sheet-container page-sheet <?php echo ($indexPagina > 0) ? 'page-break' : ''; ?>">
            <div class="page-body">

                <!-- Cabeçalho Oficial do Colégio -->
                <?php if ($isPrimeira): ?>
                    <header class="doc-header">
                        <div class="doc-brand">
                            <?php if (!empty($logoDataUri)): ?>
                                <img src="<?php echo $logoDataUri; ?>" alt="Logotipo do Colégio" class="doc-logo">
                            <?php endif; ?>
                            <div class="doc-titles">
                                <h1>COLÉGIO MONTEIRO LOBATO</h1>
                                <h2>RELATÓRIO DE MOVIMENTAÇÕES DE ESTOQUE</h2>
                            </div>
                        </div>
                        <div class="doc-meta font-tabular">
                            <div><strong>Emissão:</strong> <?php echo date('d/m/Y H:i:s'); ?></div>
                            <div><strong>Centro de Custo:</strong> <?php echo htmlspecialchars($centroCusto, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="doc-page-badge">Página <?php echo $numPagina; ?> de <?php echo $totalPaginas; ?></div>
                        </div>
                    </header>

                    <!-- Faixa de Parâmetros e Filtros Aplicados -->
                    <div class="params-strip font-tabular">
                        <div class="params-item">
                            <span class="params-label">Período:</span>
                            <span class="params-val"><?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="params-item">
                            <span class="params-label">Grupo / Categoria:</span>
                            <span class="params-val"><?php echo htmlspecialchars($catFiltro, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="params-item">
                            <span class="params-label">Produto:</span>
                            <span class="params-val"><?php echo htmlspecialchars($prodFiltro, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="params-item">
                            <span class="params-label">Formato:</span>
                            <span class="params-val"><?php echo $isPaisagem ? 'Paisagem (A4)' : 'Retrato (A4)'; ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <header class="doc-header compact">
                        <div class="doc-brand">
                            <?php if (!empty($logoDataUri)): ?>
                                <img src="<?php echo $logoDataUri; ?>" alt="Logotipo do Colégio" class="doc-logo-small">
                            <?php endif; ?>
                            <div class="doc-titles compact">
                                <h1>COLÉGIO MONTEIRO LOBATO — RELATÓRIO DE ESTOQUE <span style="font-weight:normal; color:#475569; font-size:10px;">(Continuação)</span></h1>
                            </div>
                        </div>
                        <div class="doc-meta font-tabular">
                            <div><strong>Emissão:</strong> <?php echo date('d/m/Y H:i'); ?> | <strong>C. Custo:</strong> <?php echo htmlspecialchars($centroCusto, ENT_QUOTES, 'UTF-8'); ?> | <span class="doc-page-badge">Pág. <?php echo $numPagina; ?>/<?php echo $totalPaginas; ?></span></div>
                        </div>
                    </header>
                <?php endif; ?>

                <!-- CONTEÚDO DA PÁGINA CONFORME O MODO -->
                <?php if ($pag['modo'] === 'vazio'): ?>
                    <div style="padding: 40px; text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px; margin: 20px 0;">
                        <p style="font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Não há movimentações de estoque registradas para o período e filtros selecionados.</p>
                        <p style="font-size: 11px; margin: 0;">Altere a data inicial/final ou selecione outra categoria/produto para consultar.</p>
                    </div>

                <?php elseif ($pag['modo'] === 'geral'): ?>
                    <!-- TABELA GERAL CRONOLÓGICA -->
                    <table class="table-estoque">
                        <thead>
                            <tr>
                                <?php if ($isPaisagem): ?>
                                    <th style="width: 8.5%;" class="text-center">Data / Hora</th>
                                    <th style="width: 7.5%;">C. Custo</th>
                                    <th style="width: 9.5%;">Categoria</th>
                                    <th style="width: 7%;">Código</th>
                                    <th style="width: 19%;">Descrição do Produto</th>
                                    <th style="width: 12.5%;">Tipo Movimentação</th>
                                    <th style="width: 7.5%;" class="text-right col-qtd">Entrada</th>
                                    <th style="width: 7.5%;" class="text-right col-qtd">Saída</th>
                                    <th style="width: 8%;">Operador</th>
                                    <th style="width: 13%;">Doc / Justificativa</th>
                                <?php else: ?>
                                    <th style="width: 10.5%;" class="text-center">Data / Hora</th>
                                    <th style="width: 10%;">Categoria</th>
                                    <th style="width: 7.5%;">Código</th>
                                    <th style="width: 19%;">Descrição do Produto</th>
                                    <th style="width: 13%;">Tipo Movimentação</th>
                                    <th style="width: 8.5%;" class="text-right col-qtd">Entrada</th>
                                    <th style="width: 8.5%;" class="text-right col-qtd">Saída</th>
                                    <th style="width: 8.5%;">Operador</th>
                                    <th style="width: 14.5%;">Doc / Justificativa</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pag['itens'] as $m): ?>
                                <tr>
                                    <td class="text-center font-tabular" style="font-size: 8px; line-height: 1.2;">
                                        <?php echo $m['data_formatada']; ?><br>
                                        <span style="color: #64748b;"><?php echo $m['hora_formatada']; ?></span>
                                    </td>
                                    <?php if ($isPaisagem): ?>
                                        <td><small><?php echo htmlspecialchars($m['centro_custo'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    <?php endif; ?>
                                    <td><small><?php echo htmlspecialchars($m['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    <td class="font-tabular"><?php echo htmlspecialchars($m['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($m['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-right font-tabular col-qtd <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                        <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                    </td>
                                    <td class="text-right font-tabular col-qtd <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                        <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($pag['modo'] === 'categoria'): ?>
                    <!-- TABELA DE CATEGORIA ÚNICA NESTA FOLHA -->
                    <div class="group-header">
                        <span>CATEGORIA: <?php echo htmlspecialchars($pag['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $pag['is_continua'] ? '<small style="font-weight:normal; opacity:0.85;">(Continuação)</small>' : ''; ?></span>
                        <span class="font-tabular" style="font-size: 8.5px; font-weight: normal;">
                            Total: <?php echo $pag['total_lancamentos']; ?> lançamento(s)
                        </span>
                    </div>

                    <table class="table-estoque">
                        <thead>
                            <tr>
                                <th style="width: 11%;" class="text-center">Data / Hora</th>
                                <th style="width: 8%;">Código</th>
                                <th style="width: 22%;">Descrição do Produto</th>
                                <th style="width: 15%;">Tipo Movimentação</th>
                                <th style="width: 9%;" class="text-right col-qtd">Entrada</th>
                                <th style="width: 9%;" class="text-right col-qtd">Saída</th>
                                <th style="width: 10%;">Operador</th>
                                <th style="width: 16%;">Doc / Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pag['itens'] as $m): ?>
                                <tr>
                                    <td class="text-center font-tabular" style="font-size: 8px; line-height: 1.2;">
                                        <?php echo $m['data_formatada']; ?><br>
                                        <span style="color: #64748b;"><?php echo $m['hora_formatada']; ?></span>
                                    </td>
                                    <td class="font-tabular"><?php echo htmlspecialchars($m['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><strong><?php echo htmlspecialchars($m['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-right font-tabular col-qtd <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                        <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                    </td>
                                    <td class="text-right font-tabular col-qtd <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                        <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($pag['tem_subtotal']): ?>
                                <tr class="group-subtotal font-tabular">
                                    <td colspan="4" class="text-right"><strong>Subtotal da Categoria:</strong></td>
                                    <td class="text-right qtd-entrada">+<?php echo $pag['total_entradas']; ?> un</td>
                                    <td class="text-right qtd-saida">-<?php echo $pag['total_saidas']; ?> un</td>
                                    <td colspan="2" class="text-right">
                                        <strong>Balanço:</strong> 
                                        <span class="<?php echo $pag['balanco'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                            <?php echo ($pag['balanco'] >= 0 ? '+' : '') . $pag['balanco']; ?> un
                                        </span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($pag['modo'] === 'categoria_continuo'): ?>
                    <!-- CATEGORIAS CONTÍNUAS -->
                    <?php foreach ($pag['blocos'] as $bloco): ?>
                        <div class="group-header">
                            <span>CATEGORIA: <?php echo htmlspecialchars($bloco['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?> <?php echo $bloco['is_continua'] ? '<small style="font-weight:normal; opacity:0.85;">(Continuação)</small>' : ''; ?></span>
                            <span class="font-tabular" style="font-size: 8.5px; font-weight: normal;">
                                Total: <?php echo $bloco['total_lancamentos']; ?> lançamento(s)
                            </span>
                        </div>

                        <table class="table-estoque">
                            <thead>
                                <tr>
                                    <th style="width: 11%;" class="text-center">Data / Hora</th>
                                    <th style="width: 8%;">Código</th>
                                    <th style="width: 22%;">Descrição do Produto</th>
                                    <th style="width: 15%;">Tipo Movimentação</th>
                                    <th style="width: 9%;" class="text-right col-qtd">Entrada</th>
                                    <th style="width: 9%;" class="text-right col-qtd">Saída</th>
                                    <th style="width: 10%;">Operador</th>
                                    <th style="width: 16%;">Doc / Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bloco['itens'] as $m): ?>
                                    <tr>
                                        <td class="text-center font-tabular" style="font-size: 8px; line-height: 1.2;">
                                            <?php echo $m['data_formatada']; ?><br>
                                            <span style="color: #64748b;"><?php echo $m['hora_formatada']; ?></span>
                                        </td>
                                        <td class="font-tabular"><?php echo htmlspecialchars($m['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($m['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-right font-tabular col-qtd <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                        </td>
                                        <td class="text-right font-tabular col-qtd <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($bloco['tem_subtotal']): ?>
                                    <tr class="group-subtotal font-tabular">
                                        <td colspan="4" class="text-right"><strong>Subtotal da Categoria:</strong></td>
                                        <td class="text-right qtd-entrada">+<?php echo $bloco['total_entradas']; ?> un</td>
                                        <td class="text-right qtd-saida">-<?php echo $bloco['total_saidas']; ?> un</td>
                                        <td colspan="2" class="text-right">
                                            <strong>Balanço:</strong> 
                                            <span class="<?php echo $bloco['balanco'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                                <?php echo ($bloco['balanco'] >= 0 ? '+' : '') . $bloco['balanco']; ?> un
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>

                <?php elseif ($pag['modo'] === 'produto'): ?>
                    <!-- TABELA DE PRODUTO ÚNICO NESTA FOLHA -->
                    <div class="group-header">
                        <span>[<?php echo htmlspecialchars($pag['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($pag['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?> <small style="font-weight: normal; opacity: 0.85;">(<?php echo htmlspecialchars($pag['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>) <?php echo $pag['is_continua'] ? '(Continuação)' : ''; ?></small></span>
                        <span class="font-tabular" style="font-size: 8.5px; font-weight: normal;">
                            Movimentações: <?php echo $pag['total_lancamentos']; ?>
                        </span>
                    </div>

                    <table class="table-estoque">
                        <thead>
                            <tr>
                                <th style="width: 14%;" class="text-center">Data / Hora</th>
                                <th style="width: 24%;">Tipo Movimentação</th>
                                <th style="width: 10%;" class="text-right col-qtd">Entrada</th>
                                <th style="width: 10%;" class="text-right col-qtd">Saída</th>
                                <th style="width: 14%;">Operador</th>
                                <th style="width: 28%;">Doc / Justificativa / NF</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pag['itens'] as $m): ?>
                                <tr>
                                    <td class="text-center font-tabular" style="font-size: 8px; line-height: 1.2;">
                                        <?php echo $m['data_formatada']; ?><br>
                                        <span style="color: #64748b;"><?php echo $m['hora_formatada']; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-right font-tabular col-qtd <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                        <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                    </td>
                                    <td class="text-right font-tabular col-qtd <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                        <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($pag['tem_subtotal']): ?>
                                <tr class="group-subtotal font-tabular">
                                    <td colspan="2" class="text-right"><strong>Total do Produto:</strong></td>
                                    <td class="text-right qtd-entrada">+<?php echo $pag['total_entradas']; ?> un</td>
                                    <td class="text-right qtd-saida">-<?php echo $pag['total_saidas']; ?> un</td>
                                    <td colspan="2" class="text-right">
                                        <strong>Variação no Período:</strong> 
                                        <span class="<?php echo $pag['balanco'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                            <?php echo ($pag['balanco'] >= 0 ? '+' : '') . $pag['balanco']; ?> un
                                        </span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ($pag['modo'] === 'produto_continuo'): ?>
                    <!-- PRODUTOS CONTÍNUOS -->
                    <?php foreach ($pag['blocos'] as $bloco): ?>
                        <div class="group-header">
                            <span>[<?php echo htmlspecialchars($bloco['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($bloco['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?> <small style="font-weight: normal; opacity: 0.85;">(<?php echo htmlspecialchars($bloco['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>) <?php echo $bloco['is_continua'] ? '(Continuação)' : ''; ?></small></span>
                            <span class="font-tabular" style="font-size: 8.5px; font-weight: normal;">
                                Movimentações: <?php echo $bloco['total_lancamentos']; ?>
                            </span>
                        </div>

                        <table class="table-estoque">
                            <thead>
                                <tr>
                                    <th style="width: 14%;" class="text-center">Data / Hora</th>
                                    <th style="width: 24%;">Tipo Movimentação</th>
                                    <th style="width: 10%;" class="text-right col-qtd">Entrada</th>
                                    <th style="width: 10%;" class="text-right col-qtd">Saída</th>
                                    <th style="width: 14%;">Operador</th>
                                    <th style="width: 28%;">Doc / Justificativa / NF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bloco['itens'] as $m): ?>
                                    <tr>
                                        <td class="text-center font-tabular" style="font-size: 8px; line-height: 1.2;">
                                            <?php echo $m['data_formatada']; ?><br>
                                            <span style="color: #64748b;"><?php echo $m['hora_formatada']; ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-right font-tabular col-qtd <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                        </td>
                                        <td class="text-right font-tabular col-qtd <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($bloco['tem_subtotal']): ?>
                                    <tr class="group-subtotal font-tabular">
                                        <td colspan="2" class="text-right"><strong>Total do Produto:</strong></td>
                                        <td class="text-right qtd-entrada">+<?php echo $bloco['total_entradas']; ?> un</td>
                                        <td class="text-right qtd-saida">-<?php echo $bloco['total_saidas']; ?> un</td>
                                        <td colspan="2" class="text-right">
                                            <strong>Variação no Período:</strong> 
                                            <span class="<?php echo $bloco['balanco'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                                <?php echo ($bloco['balanco'] >= 0 ? '+' : '') . $bloco['balanco']; ?> un
                                            </span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Caixa de Consolidação Geral e Totais do Período (Apenas na Última Página) -->
                <?php if ($pag['tem_totais']): ?>
                    <div class="totais-box font-tabular">
                        <div class="totais-grid">
                            <div class="totais-item">
                                <span class="totais-label">Total Entradas</span>
                                <span class="totais-val qtd-entrada">+<?php echo number_format($totais['total_entradas'], 0, ',', '.'); ?> un</span>
                            </div>
                            <div class="totais-item">
                                <span class="totais-label">Saídas por Vendas</span>
                                <span class="totais-val qtd-saida">-<?php echo number_format($totais['total_vendas'], 0, ',', '.'); ?> un</span>
                            </div>
                            <div class="totais-item">
                                <span class="totais-label">Baixas por Perdas</span>
                                <span class="totais-val" style="color: #92400e;">-<?php echo number_format($totais['total_perdas'], 0, ',', '.'); ?> un</span>
                            </div>
                            <div class="totais-item">
                                <span class="totais-label">Balanço Líquido</span>
                                <span class="totais-val <?php echo $totais['balanco_periodo'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                    <?php echo ($totais['balanco_periodo'] >= 0 ? '+' : '') . number_format($totais['balanco_periodo'], 0, ',', '.'); ?> un
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div> <!-- /.page-body -->

            <!-- Rodapé Oficial da Folha Física -->
            <footer class="doc-footer">
                <div>Colégio Monteiro Lobato • Sistema Cantina Web — Relatório Gerencial de Estoque</div>
                <div class="font-tabular">Documento Oficial de Auditoria • <?php echo date('d/m/Y'); ?> • Página <?php echo $numPagina; ?> de <?php echo $totalPaginas; ?></div>
            </footer>

        </div> <!-- /.page-sheet -->
    <?php endforeach; ?>
    </div> <!-- /#document-zoom-area -->

    <script>
        // =====================================================================
        // CONTROLE DE ZOOM PARA CONFERÊNCIA VISUAL DE IMPRESSÃO / SALVAR EM PDF
        // =====================================================================
        let currentZoom = 1.0;
        const minZoom = 0.5;
        const maxZoom = 2.5;

        function aplicarZoom(novoZoom) {
            currentZoom = Math.min(Math.max(novoZoom, minZoom), maxZoom);
            currentZoom = Math.round(currentZoom * 100) / 100;
            
            const zoomArea = document.getElementById('document-zoom-area');
            const zoomValueBtn = document.getElementById('btnZoomReset');
            
            if (zoomArea) {
                zoomArea.style.zoom = currentZoom;
                zoomArea.style.setProperty('--doc-zoom', currentZoom);
            }
            
            if (zoomValueBtn) {
                zoomValueBtn.textContent = Math.round(currentZoom * 100) + '%';
            }

            try {
                localStorage.setItem('cantina_report_zoom', currentZoom);
            } catch(e) {}
        }

        function alterarZoom(delta) {
            aplicarZoom(currentZoom + delta);
        }

        function resetarZoom() {
            aplicarZoom(1.0);
        }

        // Restaura zoom preferido da sessão ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            try {
                const savedZoom = localStorage.getItem('cantina_report_zoom');
                if (savedZoom) {
                    const z = parseFloat(savedZoom);
                    if (!isNaN(z) && z >= minZoom && z <= maxZoom) {
                        aplicarZoom(z);
                    }
                }
            } catch(e) {}
        });

        // Atalhos de Teclado (Ctrl + / Ctrl - / Ctrl 0)
        window.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                alterarZoom(0.15);
            } else if ((e.ctrlKey || e.metaKey) && (e.key === '-' || e.key === '_')) {
                e.preventDefault();
                alterarZoom(-0.15);
            } else if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                e.preventDefault();
                resetarZoom();
            }
        });

        // Atalho Ctrl + Roda do Mouse
        window.addEventListener('wheel', function(e) {
            if (e.ctrlKey) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    alterarZoom(0.1);
                } else {
                    alterarZoom(-0.1);
                }
            }
        }, { passive: false });

        // Proteção de Impressão: força 100% mandatória antes de abrir diálogo de impressão
        window.addEventListener('beforeprint', function() {
            const zoomArea = document.getElementById('document-zoom-area');
            if (zoomArea) {
                zoomArea.style.zoom = 1;
            }
        });
        window.addEventListener('afterprint', function() {
            const zoomArea = document.getElementById('document-zoom-area');
            if (zoomArea) {
                zoomArea.style.zoom = currentZoom;
            }
        });

        function fecharJanela() {
            window.close();
            setTimeout(function() {
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.href = '<?php echo $baseUrl; ?>/relatorios/estoque-filtros';
                }
            }, 150);
        }
    </script>
</body>
</html>
