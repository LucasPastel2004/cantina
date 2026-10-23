<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial de Emissão de Vendas da Cantina por Alimento & Aluno (A4)
 * 
 * Conforme docs/alterações sistema guideline/relatorio de vendas por período.pdf,
 * Filtros de vendas.pdf e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

if (!isset($baseUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
}

$dados = $dadosVendas ?? ($dados ?? []);
$periodoOficial = $dados['periodo_oficial'] ?? ($dados['periodo'] ?? (date('d/m/Y') . ' - ' . date('d/m/Y')));
$dias = $dados['dias'] ?? [];
$totais = $dados['totais'] ?? [];
$filtros = $dados['filtros'] ?? [];
$alunos = $dados['alunos'] ?? [];
$turmas = $dados['turmas'] ?? [];
$alimentosConsolidados = $dados['alimentos_consolidados'] ?? [];
$itensGerais = $dados['itens'] ?? [];

// Leitura rigorosa de todos os 15 toggles e parâmetros de formatação
$emitirObs    = ($filtros['emitir_observacao'] ?? 'nao') === 'sim';
$emitirForma  = ($filtros['emitir_forma_pagamento'] ?? 'nao') === 'sim';
$emitirHora   = ($filtros['emitir_horario'] ?? 'nao') === 'sim';
$agruparTipo  = ($filtros['agrupar_tipo_pessoa'] ?? 'nao') === 'sim';
$agruparPer   = ($filtros['agrupar_periodo'] ?? 'sim') === 'sim';
$incluirEst   = ($filtros['incluir_estornos'] ?? 'nao') === 'sim';

$emitirAlim   = ($filtros['emitir_vendas_alimento'] ?? 'sim') !== 'nao';
$agruparAlim  = ($filtros['agrupar_alimento'] ?? 'sim') !== 'nao';
$ordenarAlim  = $filtros['ordenar_alimentos'] ?? 'mais_vendidos';

$emitirAluno  = ($filtros['emitir_vendas_aluno'] ?? 'nao') === 'sim';
$agruparAluno = ($filtros['agrupar_vendas_aluno'] ?? 'sim') !== 'nao';
$agruparTurma = ($filtros['agrupar_serie_turma'] ?? 'nao') === 'sim';
$quebrarTurma = ($filtros['quebrar_pagina_turma'] ?? 'nao') === 'sim';
$ordConsumo   = ($filtros['ordenar_consumo_aluno'] ?? 'nao') === 'sim';
$ordPor       = $filtros['ordenar_por'] ?? 'maior_consumo';

$operador = $dados['operador'] ?? 'Administração';
$emissao = date('d/m/Y H:i');
$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);

$qtdTotal = (int)($totais['quantidade_total'] ?? 0);
$fatTotal = (float)($totais['faturamento_total'] ?? 0.0);

// Título dinâmico conforme a combinação ativa
if ($emitirAlim && $emitirAluno) {
    $tituloRelatorio = 'Relatório de Emissão de Vendas da Cantina por Alimento & Aluno';
} elseif (!$emitirAlim && $emitirAluno) {
    $tituloRelatorio = 'Relatório de Emissão de Vendas da Cantina por Aluno / Turma';
} else {
    $tituloRelatorio = 'Relatório de Emissão de Vendas da Cantina por Alimento';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($tituloRelatorio); ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
            line-height: 1.35;
            background: #ffffff;
            width: 100%;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }

        /* Barra de Ações para Navegador (Oculta na Impressão e no PDF) */
        .no-print {
            background-color: #1e293b;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-size: 11px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .no-print button, .no-print a {
            cursor: pointer;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease-in-out;
        }
        .btn-print-action {
            background: #1d4ed8;
            color: #ffffff !important;
            border: 1px solid #1e40af;
        }
        .btn-print-action:hover {
            background: #1e40af;
        }
        .btn-back-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            margin-right: 6px;
        }
        .btn-back-action:hover {
            background: #f1f5f9;
        }
        .btn-close-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            margin-left: 6px;
        }
        .btn-close-action:hover {
            background: #f1f5f9;
        }

        /* Visualização de Folha A4 em Tela */
        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 24px 0 40px 0;
            }
            .no-print {
                width: 210mm;
                max-width: calc(100% - 32px);
                margin: 0 auto 16px auto;
                position: sticky;
                top: 10px;
                z-index: 1000;
            }
            .no-print .zoom-controls {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                padding: 2px 6px;
                margin-right: 8px;
            }
            .no-print .btn-zoom {
                background: transparent;
                color: #ffffff;
                border: none;
                width: 22px;
                height: 22px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                border-radius: 3px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                transition: all 0.15s ease;
            }
            .no-print .btn-zoom:hover {
                background: rgba(255, 255, 255, 0.25);
                color: #93c5fd;
            }
            .no-print .btn-zoom-val {
                background: transparent;
                color: #ffffff;
                border: none;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                padding: 2px 5px;
                border-radius: 3px;
                min-width: 40px;
                text-align: center;
                font-variant-numeric: tabular-nums;
                transition: all 0.15s ease;
            }
            .no-print .btn-zoom-val:hover {
                background: rgba(255, 255, 255, 0.25);
                text-decoration: underline;
            }
            .document-zoom-area {
                zoom: var(--doc-zoom, 1);
                transform-origin: top center;
                transition: zoom 0.1s ease-out;
            }
            .container {
                width: 210mm;
                max-width: calc(100% - 24px);
                min-height: 297mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 10mm 14mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        /* Impressão Direta (Física / Browser Print to PDF) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm 14mm 14mm;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            html, body {
                width: 100% !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8pt;
                color: #000000 !important;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }

            /* Quebras de páginas limpas: previne fatiamento de linhas na divisão da folha A4 */
            table.tabela-relatorio,
            table.table-doc-excel {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
                margin-top: 4px !important;
                margin-bottom: 8px !important;
                border: 1px solid #000000 !important;
                border-bottom: none !important;
                border-right: none !important;
            }
            table.tabela-relatorio thead,
            table.table-doc-excel thead {
                display: table-header-group !important;
            }
            table.tabela-relatorio thead tr,
            table.table-doc-excel thead tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.tabela-relatorio tfoot,
            table.table-doc-excel tfoot {
                display: table-footer-group !important;
            }
            table.tabela-relatorio tbody tr,
            table.table-doc-excel tbody tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.tabela-relatorio th,
            table.tabela-relatorio td,
            table.table-doc-excel th,
            table.table-doc-excel td {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border-right: 1px solid #000000 !important;
                border-bottom: 1px solid #000000 !important;
            }
            .school-header,
            .param-bar,
            .section-banner,
            .resumo-alimentos-card,
            .footer-assinaturas-box,
            .footer-relatorio,
            .doc-footer-meta,
            .row-total-dia,
            .row-total-periodo,
            .row-subtotal-turma {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .school-header, .param-bar, .section-banner, .resumo-alimentos-card, .footer-assinaturas-box, .doc-footer-meta { page-break-inside: avoid; break-inside: avoid; }

        /* Cabeçalho Oficial Escolar */
        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .school-logo-col {
            width: 52px;
            margin-right: 12px;
        }
        .school-logo-col img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #cbd5e1;
        }
        .school-title-col {
            flex: 1;
            line-height: 1.25;
        }
        .school-name {
            font-size: 10.5pt;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .report-title-main {
            font-size: 9.5pt;
            font-weight: 700;
            color: #000000;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 7.8pt;
            color: #334155;
            margin-top: 1px;
        }
        .school-meta-col {
            text-align: right;
            font-size: 7.5pt;
            color: #334155;
            line-height: 1.35;
            padding-right: 2px;
        }

        /* Faixa de Parâmetros */
        .param-bar {
            font-size: 8pt;
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 4px 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Banners de Seção */
        .section-banner {
            font-weight: 700;
            font-size: 8pt;
            text-transform: uppercase;
            background: #0f172a;
            color: #ffffff;
            padding: 3.5px 8px;
            margin-top: 8px;
            margin-bottom: 3px;
            border-radius: 2px;
        }

        /* Tabela de Vendas Oficial (Estética Excel com Divisórias Pretas e Cabeçalho Neutro) */
        table.tabela-relatorio,
        table.table-doc-excel {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-top: 4px;
            margin-bottom: 8px;
            font-size: 7.8pt;
            border: 1px solid #000000 !important;
        }
        table.tabela-relatorio th,
        table.table-doc-excel th {
            border: 1px solid #000000 !important;
            padding: 3px 4px !important;
            font-weight: 700 !important;
            text-align: left;
            color: #000000;
            font-size: 7.2pt !important;
            letter-spacing: -0.01em;
            text-transform: uppercase !important;
            background: #f8fafc !important;
            word-break: break-word;
        }
        table.tabela-relatorio th:first-child,
        table.tabela-relatorio td:first-child,
        table.table-doc-excel th:first-child,
        table.table-doc-excel td:first-child {
            border-left: 1px solid #000000 !important;
        }
        table.tabela-relatorio th:last-child,
        table.tabela-relatorio td:last-child,
        table.table-doc-excel th:last-child,
        table.table-doc-excel td:last-child {
            border-right: 1px solid #000000 !important;
        }
        table.tabela-relatorio td,
        table.table-doc-excel td {
            padding: 3px 5px !important;
            vertical-align: middle;
            border: 1px solid #000000 !important;
            color: #000000;
            background: #ffffff !important;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        table.tabela-relatorio tbody tr td,
        table.table-doc-excel tbody tr td {
            background: #ffffff !important;
        }

        .text-center { text-align: center !important; }
        .text-right, .text-end { text-align: right !important; }
        .text-left, .text-start { text-align: left !important; }

        /* Linhas de Totalização Oficial */
        .row-total-dia td {
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            font-weight: 700 !important;
            padding: 3.5px 5px !important;
            color: #000000;
            background: #f8fafc !important;
        }
        .row-total-periodo td {
            border-top: 1px solid #000000 !important;
            border-bottom: 2px solid #000000 !important;
            font-weight: 700 !important;
            padding: 4px 5px !important;
            color: #000000;
            font-size: 8.2pt !important;
            background: #f8fafc !important;
        }
        .row-subtotal-turma td {
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            font-weight: 700 !important;
            padding: 3.5px 5px !important;
            color: #000000;
            background: #f8fafc !important;
        }

        /* Banners de Turma e Quebras de Página */
        .turma-header-banner {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            font-size: 8pt;
            padding: 3.5px 8px;
            margin-top: 12px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border-radius: 2px;
        }
        .page-break-turma {
            page-break-before: always;
            break-before: page;
        }
        @media screen {
            .page-break-turma {
                border-top: 2px dashed #94a3b8;
                margin: 28px 0 16px 0;
                padding-top: 10px;
                text-align: center;
            }
            .page-break-turma::after {
                content: "--- Quebra de Página A4 (Próxima Turma) ---";
                font-size: 7.5pt;
                color: #64748b;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
        }

        /* Subtabelas de Compras Detalhadas por Aluno */
        .bloco-aluno-detalhe {
            margin-bottom: 8px;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 4px;
        }
        .aluno-titulo-linha {
            font-weight: 700;
            font-size: 8pt;
            background: #f1f5f9;
            padding: 3px 6px;
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tabela-compras-aluno {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        .tabela-compras-aluno th {
            border-bottom: 1px solid #cbd5e1;
            padding: 2px 4px;
            color: #475569;
            font-weight: 600;
        }
        .tabela-compras-aluno td {
            padding: 1.5px 4px;
            border-bottom: 1px dotted #f1f5f9;
        }

        /* Assinaturas e Rodapé Oficial */
        .footer-assinaturas-box {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #000000;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 44%;
            border-top: 1px solid #000000;
            padding-top: 4px;
            text-align: center;
            font-size: 7.5pt;
            line-height: 1.25;
        }
        .doc-footer-meta {
            margin-top: 10px;
            font-size: 7pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }

        /* Rodapé Institucional do Colégio */
        .footer-relatorio {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.2pt;
            color: #64748b;
        }
        .footer-relatorio .brand {
            font-weight: normal;
        }
        .footer-relatorio .brand strong {
            font-weight: bold;
        }
        .footer-relatorio .page-num {
            font-weight: normal;
        }
    </style>
</head>
<body>

<!-- Barra de Controle de Impressão (Apenas na Visualização em Tela) -->
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;">Cantina Web — Relatório Oficial de Emissão de Vendas</div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">Período: <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
        <!-- Controles de Zoom para Conferência -->
        <div class="zoom-controls" title="Ajuste o zoom para conferir detalhes antes de imprimir ou salvar">
            <span style="display: inline-flex; align-items: center; color: #cbd5e1; margin-right: 3px;" title="Zoom">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </span>
            <button type="button" class="btn-zoom" id="btnZoomOut" title="Diminuir Zoom (Ctrl -)" onclick="alterarZoom(-0.15)">−</button>
            <button type="button" class="btn-zoom-val" id="btnZoomReset" title="Clique para redefinir para 100% (Ctrl 0)" onclick="resetarZoom()">100%</button>
            <button type="button" class="btn-zoom" id="btnZoomIn" title="Aumentar Zoom (Ctrl +)" onclick="alterarZoom(0.15)">+</button>
        </div>

        <button type="button" class="btn-print-action" onclick="window.print()" title="Imprimir documento ou Salvar como PDF pelo navegador">
            🖨 Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-close-action" onclick="window.close()">
            ✕ Fechar
        </button>
    </div>
</div>

<div id="document-zoom-area" class="document-zoom-area">
<div class="container">

    <!-- Cabeçalho Oficial Escolar -->
    <div class="school-header">
        <?php if (!empty($logoDataUri)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoDataUri; ?>" alt="Logotipo Oficial">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="report-title-main"><?php echo htmlspecialchars($tituloRelatorio); ?></div>
            <div class="report-subtitle">Sistema de Gestão Cantina Web • Módulo Operacional & Relatórios de Vendas</div>
        </div>
        <div class="school-meta-col">
            <div><strong>Emissão:</strong> <?php echo $emissao; ?></div>
            <div><strong>Unidade:</strong> Cantina Geral</div>
            <div><strong>Documento:</strong> A4 Oficial</div>
        </div>
    </div>

    <!-- Faixa de Parâmetros e Filtros -->
    <div class="param-bar">
        <div><strong>Período Apurado:</strong> <?php echo htmlspecialchars($periodoOficial); ?> <span style="font-size: 7.2pt; color: #475569;">(PERÍODO ENTRE DATAS : <?php echo htmlspecialchars($periodoOficial); ?>)</span></div>
        <div><strong>Critério:</strong> <?php echo $agruparPer ? 'Vendas por Alimento & Agrupamento Diário' : 'Vendas por Alimento Consolidadas no Período'; ?></div>
    </div>

    <!-- SEÇÃO 1: TABELA DE VENDAS POR ALIMENTO (Emitida se $emitirAlim for true) -->
    <?php if ($emitirAlim): ?>
        <div class="section-banner">
            1. Demonstrativo de Vendas da Cantina por Alimento
        </div>

        <?php if ($agruparPer): ?>
            <!-- CASO 1.1: AGRUPADO POR DIA (Conforme relatorio de vendas por período.pdf) -->
            <?php
            $temExtras = ($emitirHora || $agruparTipo || $emitirForma || $emitirObs);
            $wData  = $temExtras ? 9 : 10;
            $wHora  = $emitirHora ? 7 : 0;
            $wTipo  = $agruparTipo ? 9 : 0;
            $wQtd   = $temExtras ? 11 : 12;
            $wUnit  = $temExtras ? 12 : 13;
            $wTot   = $temExtras ? 12 : 13;
            $wForma = $emitirForma ? 9 : 0;
            $wObs   = $emitirObs ? 11 : 0;
            $wAlim  = 100 - ($wData + $wHora + $wTipo + $wQtd + $wUnit + $wTot + $wForma + $wObs);
            ?>
            <table class="tabela-relatorio">
                <thead>
                    <tr>
                        <th style="width: <?php echo $wData; ?>%;">Data</th>
                        <?php if ($emitirHora): ?>
                            <th style="width: <?php echo $wHora; ?>%;" class="text-center">Horário</th>
                        <?php endif; ?>
                        <?php if ($agruparTipo): ?>
                            <th style="width: <?php echo $wTipo; ?>%;">Tipo Pessoa</th>
                        <?php endif; ?>
                        <th style="width: <?php echo $wAlim; ?>%;">Alimento</th>
                        <th style="width: <?php echo $wQtd; ?>%;" class="text-right">Quantidade</th>
                        <th style="width: <?php echo $wUnit; ?>%;" class="text-right">Valor Unitário</th>
                        <th style="width: <?php echo $wTot; ?>%;" class="text-right">Valor Total</th>
                        <?php if ($emitirForma): ?>
                            <th style="width: <?php echo $wForma; ?>%;">Forma Pagto</th>
                        <?php endif; ?>
                        <?php if ($emitirObs): ?>
                            <th style="width: <?php echo $wObs; ?>%;">Observação</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dias)): ?>
                        <tr>
                            <td colspan="<?php echo 5 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0) + ($emitirForma ? 1 : 0) + ($emitirObs ? 1 : 0); ?>" class="text-center" style="padding: 18px; color: #64748b;">
                                Não há registros de vendas no período e filtros informados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dias as $dataKey => $dia): ?>
                            <!-- Alimentos do Dia -->
                            <?php foreach ($dia['alimentos'] as $item): ?>
                                <tr>
                                    <td class="font-tabular"><?php echo htmlspecialchars($item['data_fmt'] ?? $dia['data_fmt']); ?></td>
                                    <?php if ($emitirHora): ?>
                                        <td class="text-center font-tabular"><?php echo htmlspecialchars($item['hora_venda'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <?php if ($agruparTipo): ?>
                                        <td><?php echo htmlspecialchars($item['tipo_pessoa'] ?? 'Aluno'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($item['descricao'] ?? ($item['produto_nome'] ?? '-')); ?></td>
                                    <td class="text-right font-tabular"><?php echo (int)($item['quantidade'] ?? 0); ?></td>
                                    <td class="text-right font-tabular"><?php echo number_format((float)($item['preco_unitario'] ?? 0.0), 2, ',', ''); ?></td>
                                    <td class="text-right font-tabular"><?php echo number_format((float)($item['subtotal'] ?? ($item['valor_total'] ?? 0.0)), 2, ',', ''); ?></td>
                                    <?php if ($emitirForma): ?>
                                        <td><?php echo htmlspecialchars($item['forma_pagamento'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                    <?php if ($emitirObs): ?>
                                        <td><?php echo htmlspecialchars($item['observacao'] ?? '-'); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Total do Dia Conforme o Espelho Oficial -->
                            <tr class="row-total-dia">
                                <td colspan="<?php echo 2 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0); ?>">Total do dia :</td>
                                <td class="text-right font-tabular"><?php echo (int)($dia['qtd_dia'] ?? ($dia['quantidade_total'] ?? 0)); ?></td>
                                <td class="text-right font-tabular"></td>
                                <td class="text-right font-tabular"><?php echo number_format((float)($dia['total_dia'] ?? ($dia['subtotal'] ?? 0.0)), 2, ',', ''); ?></td>
                                <?php if ($emitirForma): ?><td></td><?php endif; ?>
                                <?php if ($emitirObs): ?><td></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Total do Período Conforme o Espelho Oficial -->
                        <tr class="row-total-periodo">
                            <td colspan="<?php echo 2 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0); ?>">Total do período :</td>
                            <td class="text-right font-tabular"><?php echo $qtdTotal; ?></td>
                            <td class="text-right font-tabular"></td>
                            <td class="text-right font-tabular"><?php echo number_format($fatTotal, 2, ',', ''); ?></td>
                            <?php if ($emitirForma): ?><td></td><?php endif; ?>
                            <?php if ($emitirObs): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php else: ?>
            <!-- CASO 1.2: NÃO AGRUPADO POR PERÍODO (Consolidação Direta do Período Completo) -->
            <table class="tabela-relatorio">
                <thead>
                    <tr>
                        <th style="width: 11%;">Código</th>
                        <th style="width: 33%;">Alimento</th>
                        <th style="width: 15%;">Categoria</th>
                        <th style="width: 11%;" class="text-right">Qtd Total</th>
                        <th style="width: 15%;" class="text-right">Preço Médio</th>
                        <th style="width: 15%;" class="text-right">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alimentosConsolidados)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 18px; color: #64748b;">
                                Não há registros de vendas no período e filtros informados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($alimentosConsolidados as $item): ?>
                            <tr>
                                <td class="font-tabular"><?php echo htmlspecialchars($item['codigo_interno'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['descricao'] ?? ($item['produto_nome'] ?? '-')); ?></td>
                                <td><?php echo htmlspecialchars($item['categoria_nome'] ?? '-'); ?></td>
                                <td class="text-right font-tabular"><?php echo (int)($item['quantidade_total'] ?? $item['quantidade']); ?></td>
                                <td class="text-right font-tabular"><?php echo number_format((float)($item['preco_medio'] ?? 0.0), 2, ',', ''); ?></td>
                                <td class="text-right font-tabular"><?php echo number_format((float)($item['faturamento_total'] ?? ($item['valor_total'] ?? 0.0)), 2, ',', ''); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="row-total-periodo">
                            <td colspan="3">Total do período :</td>
                            <td class="text-right font-tabular"><?php echo $qtdTotal; ?></td>
                            <td class="text-right font-tabular"></td>
                            <td class="text-right font-tabular"><?php echo number_format($fatTotal, 2, ',', ''); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endif; ?>

    <!-- SEÇÃO 2: DEMONSTRATIVO DE VENDAS POR ALUNO & TURMAS (Emitida se $emitirAluno for true) -->
    <?php if ($emitirAluno): ?>
        <div class="section-banner" style="margin-top: <?php echo $emitirAlim ? '14px' : '8px'; ?>;">
            <?php echo $emitirAlim ? '2.' : '1.'; ?> Demonstrativo de Consumo por Aluno & Turmas
        </div>

        <?php if (empty($alunos)): ?>
            <div style="padding: 12px; color: #64748b; text-align: center; font-size: 8pt; border: 1px dotted #cbd5e1; margin-top: 4px;">
                Nenhum consumo de aluno registrado no período e filtros selecionados.
            </div>
        <?php else: ?>

            <?php if ($agruparTurma && !empty($turmas)): ?>
                <!-- CASO 2.1: AGRUPADO POR SÉRIE / TURMA ESCOLAR -->
                <?php foreach ($turmas as $idxTurma => $turma): ?>

                    <!-- Quebra de Página entre turmas se solicitada -->
                    <?php if ($quebrarTurma && $idxTurma > 0): ?>
                        <div class="page-break-turma"></div>
                    <?php endif; ?>

                    <div class="turma-header-banner">
                        TURMA: <?php echo htmlspecialchars($turma['turma_nome']); ?>
                    </div>

                    <?php if ($agruparAluno): ?>
                        <!-- Alunos da Turma em formato consolidado -->
                        <table class="tabela-relatorio">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Matrícula</th>
                                    <th style="width: 50%;">Nome do Aluno</th>
                                    <th style="width: 15%;" class="text-right">Quantidade</th>
                                    <th style="width: 20%;" class="text-right">Total Consumo (R$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($turma['alunos'] as $al): ?>
                                    <tr>
                                        <td class="font-tabular"><?php echo htmlspecialchars($al['matricula']); ?></td>
                                        <td><?php echo htmlspecialchars($al['nome']); ?></td>
                                        <td class="text-right font-tabular"><?php echo (int)$al['quantidade']; ?></td>
                                        <td class="text-right font-tabular"><?php echo number_format((float)$al['total_consumo'], 2, ',', ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Subtotal da Turma -->
                                <tr class="row-subtotal-turma">
                                    <td colspan="2">Subtotal da Turma (<?php echo htmlspecialchars($turma['turma_nome']); ?>) :</td>
                                    <td class="text-right font-tabular"><?php echo (int)$turma['total_quantidade']; ?></td>
                                    <td class="text-right font-tabular"><?php echo number_format((float)$turma['total_consumo'], 2, ',', ''); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Alunos da Turma em formato detalhado com compras linha a linha -->
                        <?php foreach ($turma['alunos'] as $al): ?>
                            <div class="bloco-aluno-detalhe">
                                <div class="aluno-titulo-linha">
                                    <span><strong><?php echo htmlspecialchars($al['matricula']); ?></strong> — <?php echo htmlspecialchars($al['nome']); ?></span>
                                    <span class="font-tabular"><?php echo (int)$al['quantidade']; ?> itens &bull; R$ <?php echo number_format((float)$al['total_consumo'], 2, ',', ''); ?></span>
                                </div>
                                <?php if (!empty($al['compras'])): ?>
                                    <table class="tabela-compras-aluno">
                                        <thead>
                                            <tr>
                                                <th style="width: 14%;">Data</th>
                                                <th style="width: 11%;">Horário</th>
                                                <th style="width: 41%;">Produto</th>
                                                <th style="width: 8%;" class="text-right">Qtd</th>
                                                <th style="width: 12%;" class="text-right">Preço</th>
                                                <th style="width: 14%;" class="text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($al['compras'] as $cp): ?>
                                                <tr>
                                                    <td class="font-tabular"><?php echo htmlspecialchars($cp['data_fmt']); ?></td>
                                                    <td class="font-tabular"><?php echo htmlspecialchars($cp['hora_venda']); ?></td>
                                                    <td><?php echo htmlspecialchars($cp['produto_nome']); ?></td>
                                                    <td class="text-right font-tabular"><?php echo (int)$cp['quantidade']; ?></td>
                                                    <td class="text-right font-tabular"><?php echo number_format((float)$cp['preco_unitario'], 2, ',', ''); ?></td>
                                                    <td class="text-right font-tabular"><?php echo number_format((float)$cp['subtotal'], 2, ',', ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- Subtotal da Turma no formato detalhado -->
                        <div style="font-weight: bold; font-size: 8pt; text-align: right; padding: 4px; background: #e2e8f0; margin-bottom: 10px;" class="font-tabular">
                            Subtotal da Turma: <?php echo (int)$turma['total_quantidade']; ?> itens &bull; R$ <?php echo number_format((float)$turma['total_consumo'], 2, ',', ''); ?>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            <?php else: ?>
                <!-- CASO 2.2: NÃO AGRUPADO POR TURMA (LISTA GERAL DE ALUNOS) -->
                <?php if ($agruparAluno): ?>
                    <table class="tabela-relatorio">
                        <thead>
                            <tr>
                                <th style="width: 14%;">Matrícula</th>
                                <th style="width: 42%;">Nome do Aluno</th>
                                <th style="width: 18%;">Série / Turma</th>
                                <th style="width: 10%;" class="text-right">Qtd</th>
                                <th style="width: 16%;" class="text-right">Total (R$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos as $al): ?>
                                <tr>
                                    <td class="font-tabular"><?php echo htmlspecialchars($al['matricula']); ?></td>
                                    <td><?php echo htmlspecialchars($al['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($al['serie_turma'] ?? ($al['serie'] . ' ' . $al['turma'])); ?></td>
                                    <td class="text-right font-tabular"><?php echo (int)$al['quantidade']; ?></td>
                                    <td class="text-right font-tabular"><?php echo number_format((float)$al['total_consumo'], 2, ',', ''); ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="row-total-periodo">
                                <td colspan="3">Total Geral dos Alunos :</td>
                                <td class="text-right font-tabular">
                                    <?php echo array_sum(array_column($alunos, 'quantidade')); ?>
                                </td>
                                <td class="text-right font-tabular">
                                    <?php echo number_format(array_sum(array_column($alunos, 'total_consumo')), 2, ',', ''); ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <!-- Lista Geral de Alunos com Compras Detalhadas -->
                    <?php foreach ($alunos as $al): ?>
                        <div class="bloco-aluno-detalhe">
                            <div class="aluno-titulo-linha">
                                <span><strong><?php echo htmlspecialchars($al['matricula']); ?></strong> — <?php echo htmlspecialchars($al['nome']); ?> (<?php echo htmlspecialchars($al['serie_turma'] ?? ($al['serie'] . ' ' . $al['turma'])); ?>)</span>
                                <span class="font-tabular"><?php echo (int)$al['quantidade']; ?> itens &bull; R$ <?php echo number_format((float)$al['total_consumo'], 2, ',', ''); ?></span>
                            </div>
                            <?php if (!empty($al['compras'])): ?>
                                <table class="tabela-compras-aluno">
                                    <thead>
                                        <tr>
                                            <th style="width: 16%;">Data</th>
                                            <th style="width: 12%;">Horário</th>
                                            <th style="width: 44%;">Produto</th>
                                            <th style="width: 8%;" class="text-right">Qtd</th>
                                            <th style="width: 10%;" class="text-right">Preço</th>
                                            <th style="width: 10%;" class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($al['compras'] as $cp): ?>
                                            <tr>
                                                <td class="font-tabular"><?php echo htmlspecialchars($cp['data_fmt']); ?></td>
                                                <td class="font-tabular"><?php echo htmlspecialchars($cp['hora_venda']); ?></td>
                                                <td><?php echo htmlspecialchars($cp['produto_nome']); ?></td>
                                                <td class="text-right font-tabular"><?php echo (int)$cp['quantidade']; ?></td>
                                                <td class="text-right font-tabular"><?php echo number_format((float)$cp['preco_unitario'], 2, ',', ''); ?></td>
                                                <td class="text-right font-tabular"><?php echo number_format((float)$cp['subtotal'], 2, ',', ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <div style="font-weight: bold; font-size: 8.5pt; text-align: right; padding: 4px; background: #e2e8f0; margin-top: 6px;" class="font-tabular">
                        Total Geral dos Alunos: <?php echo array_sum(array_column($alunos, 'quantidade')); ?> itens &bull; R$ <?php echo number_format(array_sum(array_column($alunos, 'total_consumo')), 2, ',', ''); ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        <?php endif; ?>

    <?php endif; ?>

    <!-- CASO NENHUMA SEÇÃO SEJA SELECIONADA -->
    <?php if (!$emitirAlim && !$emitirAluno): ?>
        <div style="padding: 24px; text-align: center; color: #475569; border: 1px dashed #cbd5e1; border-radius: 4px; margin: 15px 0;">
            <p style="font-weight: bold; font-size: 9pt; margin-bottom: 4px;">Nenhuma seção de detalhamento selecionada</p>
            <p style="font-size: 8pt; margin-bottom: 0;">
                Tanto a emissão por alimento quanto por aluno foram desmarcadas nos filtros.<br>
                Faturamento apurado no período: <strong>R$ <?php echo number_format($fatTotal, 2, ',', '.'); ?></strong> (<?php echo $qtdTotal; ?> produtos comercializados).
            </p>
        </div>
    <?php endif; ?>

    <!-- Bloco de Assinaturas e Conferência Oficial -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong><?php echo htmlspecialchars($operador); ?></strong><br>
            Emissor / Responsável pelo Relatório
        </div>
        <div class="signature-line">
            <strong>Diretoria / Gestão Financeira</strong><br>
            Colégio Monteiro Lobato
        </div>
    </div>

    <!-- Metadados Técnicos de Rodapé -->
    <div class="doc-footer-meta">
        <div>Sistema Cantina Web &bull; Colégio Monteiro Lobato &bull; Valores para o Futuro!</div>
        <div class="font-tabular">Documento A4 Oficial &bull; <?php echo $emissao; ?></div>
    </div>

    <!-- Rodapé Institucional Oficial do Colégio Monteiro Lobato -->
    <div class="footer-relatorio" style="display: none;">
        <div class="brand">
            <strong>Colégio Monteiro Lobato</strong> &bull; Sistema Cantina Web
        </div>
        <div class="page-num font-tabular">
            1/1
        </div>
    </div>
</div> <!-- /#document-zoom-area -->

<script>
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
</script>
</body>
</html>
