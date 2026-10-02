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
            margin: 8mm 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: <?php echo $isPaisagem ? '10.5px' : '10px'; ?>;
            line-height: 1.35;
            color: #0f172a;
            background-color: #cbd5e1;
            margin: 0;
            padding: 20px 0;
        }

        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1, "zero" 1;
        }

        /* Simulação de Folha A4 no Navegador */
        .sheet-container {
            background-color: #ffffff;
            width: <?php echo $isPaisagem ? '280mm' : '200mm'; ?>;
            max-width: calc(100% - 32px);
            min-height: <?php echo $isPaisagem ? '195mm' : '280mm'; ?>;
            margin: 0 auto;
            padding: 10mm 12mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-radius: 2px;
            position: relative;
        }

        /* Barra de Ações Superior (Não sai na impressão) */
        .no-print-bar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: <?php echo $isPaisagem ? '280mm' : '200mm'; ?>;
            margin: 0 auto 12px auto;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
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

        /* Cabeçalho Oficial do Colégio */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
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
        .doc-titles h1 {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .doc-titles h2 {
            font-size: 12px;
            font-weight: 700;
            color: #1d4ed8;
            margin: 2px 0 0 0;
            text-transform: uppercase;
        }
        .doc-meta {
            text-align: right;
            font-size: 9.5px;
            color: #475569;
        }

        /* Faixa de Parâmetros e Centro de Custo */
        .params-strip {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 10px;
        }
        .params-item {
            display: inline-flex;
            gap: 4px;
        }
        .params-label {
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
        }
        .params-val {
            color: #0f172a;
        }

        /* Tabelas Analíticas Oficiais (Estética Excel com Laterais e Linhas Pretas entre Itens) */
        .table-estoque {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: <?php echo $isPaisagem ? '9.5px' : '9px'; ?>;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }
        .table-estoque th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5px;
            letter-spacing: 0.03em;
            padding: 5px 6px;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            text-align: left;
        }
        .table-estoque th:first-child,
        .table-estoque td:first-child {
            border-left: 1px solid #000000;
        }
        .table-estoque th:last-child,
        .table-estoque td:last-child {
            border-right: 1px solid #000000;
        }
        .table-estoque td {
            padding: 4px 6px;
            border-bottom: 1px solid #000000; /* Linha preta entre os itens das listas */
            vertical-align: middle;
            color: #0f172a;
        }
        .table-estoque tr:hover td {
            background-color: #f8fafc;
        }

        .text-center { text-align: center !important; }
        .text-right  { text-align: right !important; }
        .text-nowrap { white-space: nowrap !important; }

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
            font-size: 10.5px;
            padding: 6px 8px;
            border-left: 4px solid #1d4ed8;
            margin-top: 16px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .group-subtotal {
            background-color: #f8fafc;
            border-top: 1px solid #cbd5e1;
            border-bottom: 2px solid #94a3b8;
            font-weight: 700;
        }

        /* Quebras de Página */
        .page-break {
            page-break-before: always !important;
            break-before: page !important;
        }

        /* Resumo de Fechamento / Totais Gerais */
        .totais-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 16px;
            page-break-inside: avoid;
        }
        .totais-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            text-align: center;
        }
        .totais-item {
            border-right: 1px solid #e2e8f0;
            padding: 0 8px;
        }
        .totais-item:last-child {
            border-right: none;
        }
        .totais-label {
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: 700;
            color: #475569;
            margin-bottom: 2px;
            display: block;
        }
        .totais-val {
            font-size: 13px;
            font-weight: 800;
        }

        /* Rodapé Oficial da Folha */
        .doc-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8.5px;
            color: #64748b;
            page-break-inside: avoid;
        }

        /* Reset em Impressão (@media print) */
        @media print {
            body {
                background: transparent !important;
                padding: 0 !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .sheet-container {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Controle Superior (visível somente na tela) -->
    <div class="no-print-bar">
        <div style="font-size: 12px; font-weight: 600;">
            Relatório de Estoque • Orientação: <?php echo $isPaisagem ? 'Paisagem (Landscape)' : 'Retrato (Portrait)'; ?>
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

    <!-- Contêiner de Simulação da Folha Física A4 -->
    <div class="sheet-container">

        <!-- Cabeçalho Oficial -->
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

        <?php if (empty($movimentacoes)): ?>
            <!-- Estado Vazio -->
            <div style="padding: 40px; text-align: center; color: #64748b; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px; margin: 20px 0;">
                <p style="font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #334155;">Não há movimentações de estoque registradas para o período e filtros selecionados.</p>
                <p style="font-size: 11px; margin: 0;">Altere a data inicial/final ou selecione outra categoria/produto para consultar.</p>
            </div>
        <?php else: ?>

            <?php if ($agruparGrupo): ?>
                <!-- CENÁRIO 1: AGRUPADO POR GRUPO / CATEGORIA -->
                <?php foreach ($gruposCategoria as $idxGrp => $grp): ?>
                    <div class="<?php echo ($quebraPagina && $idxGrp > 0) ? 'page-break' : ''; ?>">
                        <div class="group-header">
                            <span>CATEGORIA: <?php echo htmlspecialchars($grp['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="font-tabular" style="font-size: 9px; font-weight: normal;">
                                Total de Lançamentos: <?php echo count($grp['itens']); ?>
                            </span>
                        </div>

                        <table class="table-estoque">
                            <thead>
                                <tr>
                                    <th style="width: 75px;" class="text-center">Data / Hora</th>
                                    <th style="width: 65px;">Código</th>
                                    <th>Descrição do Produto</th>
                                    <th style="width: 130px;">Tipo Movimentação</th>
                                    <th style="width: 65px;" class="text-right">Entrada</th>
                                    <th style="width: 65px;" class="text-right">Saída</th>
                                    <th style="width: 90px;">Operador</th>
                                    <th style="width: 140px;">Doc / Observação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grp['itens'] as $m): ?>
                                    <tr>
                                        <td class="text-center font-tabular text-nowrap"><?php echo $m['data_formatada'] . ' ' . $m['hora_formatada']; ?></td>
                                        <td class="font-tabular text-nowrap"><?php echo htmlspecialchars($m['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($m['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-right font-tabular <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                        </td>
                                        <td class="text-right font-tabular <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                        </td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="group-subtotal font-tabular">
                                    <td colspan="4" class="text-right"><strong>Subtotal da Categoria:</strong></td>
                                    <td class="text-right qtd-entrada">+<?php echo $grp['total_entradas']; ?> un</td>
                                    <td class="text-right qtd-saida">-<?php echo $grp['total_saidas']; ?> un</td>
                                    <td colspan="2" class="text-right">
                                        <strong>Balanço:</strong> 
                                        <span class="<?php echo $grp['balanco'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                            <?php echo ($grp['balanco'] >= 0 ? '+' : '') . $grp['balanco']; ?> un
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($agruparProduto): ?>
                <!-- CENÁRIO 2: AGRUPADO POR PRODUTO -->
                <?php foreach ($gruposProduto as $idxPrd => $pGrp): ?>
                    <div class="<?php echo ($quebraPagina && $idxPrd > 0) ? 'page-break' : ''; ?>">
                        <div class="group-header">
                            <span>[<?php echo htmlspecialchars($pGrp['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($pGrp['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?> <small style="font-weight: normal; opacity: 0.85;">(<?php echo htmlspecialchars($pGrp['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>)</small></span>
                            <span class="font-tabular" style="font-size: 9px; font-weight: normal;">
                                Movimentações: <?php echo count($pGrp['itens']); ?>
                            </span>
                        </div>

                        <table class="table-estoque">
                            <thead>
                                <tr>
                                    <th style="width: 80px;" class="text-center">Data / Hora</th>
                                    <th style="width: 160px;">Tipo Movimentação</th>
                                    <th style="width: 75px;" class="text-right">Entrada</th>
                                    <th style="width: 75px;" class="text-right">Saída</th>
                                    <th style="width: 100px;">Operador</th>
                                    <th>Doc / Justificativa / NF</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pGrp['itens'] as $m): ?>
                                    <tr>
                                        <td class="text-center font-tabular text-nowrap"><?php echo $m['data_formatada'] . ' ' . $m['hora_formatada']; ?></td>
                                        <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="text-right font-tabular <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                        </td>
                                        <td class="text-right font-tabular <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                            <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                        </td>
                                        <td class="text-nowrap"><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="group-subtotal font-tabular">
                                    <td colspan="2" class="text-right"><strong>Total do Produto:</strong></td>
                                    <td class="text-right qtd-entrada">+<?php echo $pGrp['total_entradas']; ?> un</td>
                                    <td class="text-right qtd-saida">-<?php echo $pGrp['total_saidas']; ?> un</td>
                                    <td colspan="2" class="text-right">
                                        <strong>Variação no Período:</strong> 
                                        <span class="<?php echo $pGrp['balanco'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                                            <?php echo ($pGrp['balanco'] >= 0 ? '+' : '') . $pGrp['balanco']; ?> un
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <!-- CENÁRIO 3: LISTAGEM GERAL CRONOLÓGICA -->
                <table class="table-estoque">
                    <thead>
                        <tr>
                            <th style="width: 75px;" class="text-center">Data / Hora</th>
                            <?php if ($isPaisagem): ?>
                                <th style="width: 85px;">C. Custo</th>
                            <?php endif; ?>
                            <th style="width: 90px;">Categoria</th>
                            <th style="width: 65px;">Código</th>
                            <th>Descrição do Produto</th>
                            <th style="width: 140px;">Tipo Movimentação</th>
                            <th style="width: 60px;" class="text-right">Entrada</th>
                            <th style="width: 60px;" class="text-right">Saída</th>
                            <th style="width: 90px;">Operador</th>
                            <th style="<?php echo $isPaisagem ? 'width: 160px;' : 'width: 120px;'; ?>">Doc / Justificativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentacoes as $m): ?>
                            <tr>
                                <td class="text-center font-tabular text-nowrap"><?php echo $m['data_formatada'] . ' ' . $m['hora_formatada']; ?></td>
                                <?php if ($isPaisagem): ?>
                                    <td class="text-nowrap"><small><?php echo htmlspecialchars($m['centro_custo'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <?php endif; ?>
                                <td class="text-nowrap"><small><?php echo htmlspecialchars($m['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td class="font-tabular text-nowrap"><?php echo htmlspecialchars($m['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><strong><?php echo htmlspecialchars($m['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['tipo_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-right font-tabular <?php echo $m['qtd_entrada'] > 0 ? 'qtd-entrada' : 'text-muted'; ?>">
                                    <?php echo $m['qtd_entrada'] > 0 ? ('+' . $m['qtd_entrada']) : '—'; ?>
                                </td>
                                <td class="text-right font-tabular <?php echo $m['qtd_saida'] > 0 ? 'qtd-saida' : 'text-muted'; ?>">
                                    <?php echo $m['qtd_saida'] > 0 ? ('-' . $m['qtd_saida']) : '—'; ?>
                                </td>
                                <td class="text-nowrap"><?php echo htmlspecialchars($m['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><small><?php echo htmlspecialchars($m['justificativa_completa'] ?: $m['documento_ref'], ENT_QUOTES, 'UTF-8'); ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Caixa de Consolidação Geral e Totais do Período -->
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
                        <span class="totais-label">Balanço Líquido do Período</span>
                        <span class="totais-val <?php echo $totais['balanco_periodo'] >= 0 ? 'qtd-entrada' : 'qtd-saida'; ?>">
                            <?php echo ($totais['balanco_periodo'] >= 0 ? '+' : '') . number_format($totais['balanco_periodo'], 0, ',', '.'); ?> un
                        </span>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <!-- Rodapé do Documento -->
        <footer class="doc-footer">
            <div>Colégio Monteiro Lobato • Sistema Cantina Web — Relatório Gerencial de Estoque</div>
            <div class="font-tabular">Documento Oficial de Auditoria • <?php echo date('d/m/Y'); ?></div>
        </footer>

    </div>

    <script>
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
