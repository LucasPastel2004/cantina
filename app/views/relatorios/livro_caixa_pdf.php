<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Paisagem — Livro Caixa Diário
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrões oficiais de relatórios A4
 * Grade estilo Excel com divisórias pretas, fundo branco e barra de zoom com suporte a Ctrl+Wheel
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dados = $dados ?? [];
$linhas = $linhas ?? ($dados['linhas'] ?? []);
$totais = $totais ?? ($dados['totais'] ?? []);
$periodoOficial = $periodoOficial ?? ($dados['periodo_oficial'] ?? (date('d/m/Y') . ' a ' . date('d/m/Y')));
$saldoAnterior = (float)($saldoAnterior ?? ($dados['saldo_anterior'] ?? 0.0));
$saldoFinal = (float)($saldoFinal ?? ($dados['saldo_final'] ?? 0.0));
$dataInicio = $dados['data_inicio'] ?? date('Y-m-d');
$isPdf = $isPdf ?? false;

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Livro Caixa Diário — <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm 10mm 10mm 10mm;
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
            font-size: 8pt;
            font-weight: 700;
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
                padding: 20px 0 40px 0;
            }
            .no-print {
                width: 297mm;
                max-width: calc(100% - 32px);
                margin: 0 auto 14px auto;
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
                width: 297mm;
                max-width: calc(100% - 24px);
                min-height: 210mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 9mm 12mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        /* Impressão Direta (Física / Browser Print to PDF) */
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm 10mm 10mm;
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
                font-size: 7.2pt;
                font-weight: 700 !important;
                color: #000000 !important;
            }
            body, table, th, td, div, span, p {
                font-weight: 700 !important;
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

            /* Quebras de páginas limpas em tabelas oficiais */
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
            table.table-doc-excel thead {
                display: table-header-group !important;
            }
            table.table-doc-excel thead tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.table-doc-excel tfoot {
                display: table-footer-group !important;
            }
            table.table-doc-excel tbody tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
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
            .footer-assinaturas-box {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .school-header, .param-bar, .section-banner, .footer-assinaturas-box { page-break-inside: avoid; break-inside: avoid; }

        /* Cabeçalho Oficial Escolar */
        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .school-logo-col {
            width: 48px;
            margin-right: 10px;
        }
        .school-logo-col img {
            width: 44px;
            height: 44px;
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
        }
        .report-title-main {
            font-size: 9.5pt;
            font-weight: 700;
            color: #000000;
            margin-top: 2px;
        }
        .school-meta-col {
            text-align: right;
            font-size: 7.2pt;
            color: #334155;
            line-height: 1.3;
            padding-right: 2px;
        }

        /* Faixa de Parâmetros */
        .param-bar {
            font-size: 7.5pt;
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 4px 8px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Banners de Seção */
        .section-banner {
            font-weight: 700;
            font-size: 7.5pt;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            background: #0f172a;
            color: #ffffff;
            padding: 3px 8px;
            margin-top: 6px;
            margin-bottom: 4px;
            border-radius: 2px;
        }

        /* Tabela Estilo Excel Oficial */
        table.table-doc-excel {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-top: 2px;
            margin-bottom: 8px;
            font-size: 7.2pt;
            font-weight: 700 !important;
            border: 1px solid #000000 !important;
        }
        table.table-doc-excel th,
        table.table-doc-excel td {
            border: 1px solid #000000 !important;
            padding: 2.5px 3.5px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: 700 !important;
            word-break: break-word;
        }
        table.table-doc-excel thead th {
            font-weight: 700 !important;
            text-align: center !important;
            font-size: 6.8pt;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            background-color: #f8fafc !important;
            color: #000000 !important;
        }
        table.table-doc-excel tfoot td {
            font-weight: 700 !important;
            background-color: #f8fafc !important;
            color: #000000 !important;
            border-top: 2px solid #000000 !important;
        }

        .text-start { text-align: left !important; }
        .text-center { text-align: center !important; }
        .text-end { text-align: center !important; }

        /* Assinaturas */
        .footer-assinaturas-box {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1.5px solid #000000;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 42%;
            border-top: 1.5px solid #000000;
            padding-top: 4px;
            text-align: center;
            font-size: 7.5pt;
            font-weight: 700 !important;
            color: #000000;
            line-height: 1.25;
        }
        .doc-footer-meta {
            margin-top: 10px;
            font-size: 7pt;
            font-weight: 700;
            color: #000000;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #000000;
            padding-top: 3px;
        }
            font-size: 6.8pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;">Cantina Web — Livro Caixa Diário</div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">Período: <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
        <!-- Controles de Zoom para Conferência -->
        <div class="zoom-controls" title="Ajuste o zoom para conferir detalhes antes de imprimir ou salvar (Ctrl + Scroll do Mouse)">
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
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" style="margin-right: 4px; vertical-align: -1px;"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
            Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-close-action" onclick="window.close()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="container">

    <!-- Cabeçalho Institucional Oficial -->
    <div class="school-header">
        <?php if (!empty($logoDataUri)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoDataUri; ?>" alt="Logo Colégio Monteiro Lobato">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="report-title-main">LIVRO CAIXA DIÁRIO — ESCRITURAÇÃO FINANCEIRA</div>
            <div style="font-size: 7.2pt; color: #334155; margin-top: 1px;">Sistema de Gestão Cantina Web • Módulo Financeiro & Fluxo de Caixa</div>
        </div>
        <div class="school-meta-col">
            <div><strong>Emissão:</strong> <?php echo date('d/m/Y H:i'); ?></div>
            <div><strong>Unidade:</strong> Cantina Geral</div>
            <div><strong>Documento:</strong> A4 Paisagem Oficial</div>
        </div>
    </div>

    <!-- Faixa de Parâmetros e Posição do Livro Caixa -->
    <div class="param-bar">
        <div><strong>Período Apurado:</strong> <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Saldo Anterior:</strong> R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?></div>
        <div><strong>(+) Total Entradas:</strong> R$ <?php echo number_format((float)($totais['credito'] ?? 0), 2, ',', '.'); ?></div>
        <div><strong>(-) Total Saídas:</strong> R$ <?php echo number_format((float)($totais['debito'] ?? 0), 2, ',', '.'); ?></div>
        <div><strong>(=) Saldo Final:</strong> R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?></div>
    </div>

    <!-- Banner de Seção -->
    <div class="section-banner">1. ESCRITURAÇÃO CRONOLÓGICA DAS ENTRADAS, SAÍDAS E SALDO PROGRESSIVO</div>

    <!-- Tabela Oficial do Livro Caixa (13 Colunas) -->
    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 6.5%;" class="text-center">Data</th>
                <th style="width: 5.5%;" class="text-center">Dia</th>
                <th style="width: 23%;" class="text-start">Descrição / Histórico</th>
                <th style="width: 7.0%;" class="text-center">Pix Online</th>
                <th style="width: 6.8%;" class="text-center">Rec. PDV Pix</th>
                <th style="width: 6.8%;" class="text-center">Rec. PDV Din</th>
                <th style="width: 6.8%;" class="text-center">Venda Pix</th>
                <th style="width: 6.8%;" class="text-center">Venda Din</th>
                <th style="width: 6.8%;" class="text-center">Pgto Pix</th>
                <th style="width: 6.8%;" class="text-center">Pgto Din</th>
                <th style="width: 7.2%;" class="text-center">Crédito</th>
                <th style="width: 7.2%;" class="text-center">Débito</th>
                <th style="width: 8.6%;" class="text-center">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <!-- Saldo Anterior -->
            <tr style="background-color: #f8fafc; font-weight: 700;">
                <td class="text-center font-tabular">—</td>
                <td class="text-start">—</td>
                <td class="text-start">SALDO ANTERIOR (Posição em <?php echo date('d/m/Y', strtotime($dataInicio)); ?>)</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular">—</td>
                <td class="text-end font-tabular"><strong>R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?></strong></td>
            </tr>

            <?php if (empty($linhas)): ?>
                <tr>
                    <td colspan="13" class="text-center" style="padding: 10px;">Sem movimentações financeiras registradas no período informado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($linhas as $l): ?>
                    <tr>
                        <td class="text-center font-tabular"><?php echo htmlspecialchars($l['data'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-start"><?php echo htmlspecialchars($l['dia_semana'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-start">
                            <?php if ($l['tipo'] === 'credito'): ?>
                                <strong><?php echo htmlspecialchars($l['descricao'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php else: ?>
                                <span><?php echo htmlspecialchars($l['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Entradas -->
                        <td class="text-end font-tabular"><?php echo $l['recargas_pix_online'] > 0 ? number_format($l['recargas_pix_online'], 2, ',', '.') : '-'; ?></td>
                        <td class="text-end font-tabular"><?php echo $l['recargas_pix_pdv'] > 0 ? number_format($l['recargas_pix_pdv'], 2, ',', '.') : '-'; ?></td>
                        <td class="text-end font-tabular"><?php echo $l['recargas_dinheiro'] > 0 ? number_format($l['recargas_dinheiro'], 2, ',', '.') : '-'; ?></td>
                        <td class="text-end font-tabular"><?php echo $l['vendas_pix_pdv'] > 0 ? number_format($l['vendas_pix_pdv'], 2, ',', '.') : '-'; ?></td>
                        <td class="text-end font-tabular"><?php echo $l['vendas_dinheiro_pdv'] > 0 ? number_format($l['vendas_dinheiro_pdv'], 2, ',', '.') : '-'; ?></td>

                        <!-- Saídas -->
                        <td class="text-end font-tabular"><?php echo $l['pagamento_pix'] > 0 ? number_format($l['pagamento_pix'], 2, ',', '.') : '-'; ?></td>
                        <td class="text-end font-tabular"><?php echo $l['pagamento_dinheiro'] > 0 ? number_format($l['pagamento_dinheiro'], 2, ',', '.') : '-'; ?></td>

                        <!-- Crédito / Débito / Saldo -->
                        <td class="text-end font-tabular" style="font-weight: 700;">
                            <?php echo $l['credito'] > 0 ? number_format($l['credito'], 2, ',', '.') : '-'; ?>
                        </td>
                        <td class="text-end font-tabular" style="font-weight: 700;">
                            <?php echo $l['debito'] > 0 ? number_format($l['debito'], 2, ',', '.') : '-'; ?>
                        </td>
                        <td class="text-end font-tabular" style="font-weight: 700;">
                            R$ <?php echo number_format($l['saldo'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-start text-uppercase font-weight-bold">
                    TOTAIS DO PERÍODO
                </td>
                <td class="text-end font-tabular"><?php echo number_format($totais['recargas_pix_online'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format($totais['recargas_pix_pdv'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format($totais['recargas_dinheiro'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format($totais['vendas_pix_pdv'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format($totais['vendas_dinheiro_pdv'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format($totais['pagamento_pix'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format($totais['pagamento_dinheiro'] ?? 0, 2, ',', '.'); ?></td>
                <td class="text-end font-tabular" style="font-size: 7.5pt;">
                    R$ <?php echo number_format($totais['credito'] ?? 0, 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular" style="font-size: 7.5pt;">
                    R$ <?php echo number_format($totais['debito'] ?? 0, 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular" style="font-size: 7.8pt; font-weight: 800;">
                    R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Assinaturas Formais -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong>Operador / Gerente Financeiro</strong><br>
            Cantina Web • Colégio Monteiro Lobato
        </div>
        <div class="signature-line">
            <strong>Direção Administrativa / Financeira</strong><br>
            Colégio Monteiro Lobato
        </div>
    </div>

    <!-- Rodapé Documental -->
    <div class="doc-footer-meta">
        <div>Colégio Monteiro Lobato • Sistema Integrado Cantina Web • Livro Caixa Diário Autenticado</div>
        <div>Emissão: <?php echo date('d/m/Y H:i:s'); ?></div>
    </div>

</div>
</div> <!-- /#document-zoom-area -->

<?php if (!$isPdf): ?>
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
<?php endif; ?>

</body>
</html>
