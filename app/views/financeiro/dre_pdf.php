<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Timbrado — DRE Gerencial Comparativo Anual
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrões homologados na Fase 23
 * Orientação: A4 Paisagem (Landscape) com grade tipo Excel e fundo 100% branco
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$anoAtual = $ano ?? (int)date('Y');
$meses = $dre['meses'] ?? [];
$totais = $totais ?? ($drePeriodo ?? ($dre['totais'] ?? []));
$razaoSocial = $config->razao_social ?? 'COLÉGIO MONTEIRO LOBATO';
$cnpj = $config->cnpj ?? '';
$isPdf = $isPdf ?? false;
$regime = $regime ?? 'unificado';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRE Gerencial Unificado <?php echo $anoAtual; ?> — Colégio Monteiro Lobato</title>
    <style>
        /* ==========================================================================
           1. Diretivas Oficiais de Página A4 Paisagem (Landscape)
           ========================================================================== */
        @page {
            size: A4 landscape;
            margin: 0;
        }

        *, *:before, *:after {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8.5pt;
            font-weight: 700;
            line-height: 1.25;
            color: #000000;
            margin: 0;
            padding: 0;
            background-color: #cbd5e1;
        }

        .font-tabular {
            font-feature-settings: 'tnum' 1, 'zero' 1;
            font-variant-numeric: tabular-nums;
        }

        /* ==========================================================================
           2. Contêiner de Folha A4 Paisagem
           ========================================================================== */
        .doc-page {
            background-color: #ffffff;
            width: 280mm;
            max-width: calc(100% - 24px);
            min-height: auto;
            margin: 14px auto;
            padding: 5mm 10mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
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
            .doc-page {
                width: 297mm !important;
                max-width: 297mm !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 auto !important;
                padding: 4mm 10mm !important;
                box-shadow: none !important;
                border: none !important;
                box-sizing: border-box !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
        }

        /* ==========================================================================
           3. Barra Superior de Controle Padronizada (#1e293b - Apenas em Tela)
           ========================================================================== */
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
            width: 280mm;
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
            flex-shrink: 0;
            margin: 0 4px;
        }
        .no-print .btn-zoom {
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
        .no-print .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .no-print .btn-zoom-val {
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
        .no-print .btn-zoom-val:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: underline;
        }
        .btn-print-action {
            background: #1d4ed8;
            color: #ffffff !important;
            border: 1px solid #1e40af;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease-in-out;
        }
        .btn-print-action:hover {
            background: #1e40af;
        }
        .btn-close-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease-in-out;
        }
        .btn-close-action:hover {
            background: #f1f5f9;
        }

        .document-zoom-area {
            zoom: var(--doc-zoom, 1);
            transform-origin: top center;
            transition: zoom 0.1s ease-out;
        }
        @media print {
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
        }

        /* ==========================================================================
           4. Cabeçalho Escolar Oficial (Compactado no Topo)
           ========================================================================== */
        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .school-logo-col {
            width: 38px;
            margin-right: 8px;
        }
        .school-logo-col img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #000000;
        }
        .school-title-col {
            flex: 1;
            line-height: 1.15;
        }
        .school-name {
            font-size: 9.8pt;
            font-weight: 800;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .report-title-main {
            font-size: 8.4pt;
            font-weight: 800;
            color: #000000;
            margin-top: 1px;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 7.2pt;
            font-weight: 700;
            color: #000000;
            margin-top: 0;
        }
        .school-meta-col {
            text-align: right;
            font-size: 6.9pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.2;
            padding-right: 2px;
        }

        /* Faixa de Parâmetros */
        .param-bar {
            font-size: 7.4pt;
            font-weight: 700;
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 2.5px 6px;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Banners de Seção */
        .section-banner {
            font-weight: 800;
            font-size: 7.4pt;
            text-transform: uppercase;
            background: #0f172a;
            color: #ffffff !important;
            padding: 2.5px 6px;
            margin-top: 3px;
            margin-bottom: 1.5px;
            border-radius: 2px;
        }
        @media print {
            .section-banner {
                color: #ffffff !important;
                background: #0f172a !important;
            }
        }

        /* ==========================================================================
           5. Tabela DRE — Estética de Grade Excel com Linhas Pretas e Fundo 100% Branco
           ========================================================================== */
        table.table-dre-doc {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            font-size: 6.7pt;
            font-weight: 700 !important;
            border: 1px solid #000000 !important;
            margin-top: 1.5px;
        }

        table.table-dre-doc th,
        table.table-dre-doc td {
            border: 1px solid #000000 !important;
            padding: 1.0px 2.5px !important;
            line-height: 1.12 !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: 700 !important;
        }

        table.table-dre-doc thead th {
            font-weight: 800 !important;
            text-align: center !important;
            font-size: 6.6pt;
            text-transform: uppercase;
            background-color: #f8fafc !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
            padding: 1.5px 2px !important;
        }

        table.table-dre-doc thead {
            display: table-header-group;
        }

        table.table-dre-doc tfoot {
            display: table-footer-group;
        }

        table.table-dre-doc tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .col-desc {
            width: 16.5%;
            text-align: left !important;
            padding: 1.2px 3.5px !important;
            font-size: 6.5pt;
            font-weight: 700 !important;
            word-break: break-word;
        }
        .col-mes {
            width: 6.25%;
            text-align: center !important;
            padding: 1.2px 1.5px !important;
            white-space: nowrap !important;
            font-size: 6.3pt !important;
            font-weight: 700 !important;
            letter-spacing: -0.015em;
        }
        .col-total {
            width: 8.5%;
            text-align: center !important;
            font-weight: 700 !important;
            font-size: 6.4pt !important;
        }
        th.col-total {
            text-align: center !important;
            white-space: normal !important;
            line-height: 1.05 !important;
            font-size: 6.3pt !important;
            padding: 1.2px 1.5px !important;
            font-weight: 800 !important;
        }
        td.col-total {
            text-align: center !important;
            white-space: nowrap !important;
            padding: 1.2px 2px !important;
            font-size: 6.4pt !important;
            font-weight: 700 !important;
            letter-spacing: -0.015em;
        }

        .row-secao td {
            font-weight: 800 !important;
            background-color: #f8fafc !important;
            border-top: 1.5px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
        }
        .row-subitem td.col-desc {
            padding-left: 10px !important;
        }
        .row-total-destaque td {
            font-weight: 800 !important;
        }
        .row-total-destaque td.col-mes,
        .row-total-destaque td.col-total {
            font-size: 6.4pt !important;
            text-align: center !important;
            font-weight: 800 !important;
        }
        .row-resultado-final td {
            font-weight: 800 !important;
            border-top: 1.5px solid #000000 !important;
            border-bottom: 1.5px solid #000000 !important;
        }
        .row-resultado-final td.col-mes,
        .row-resultado-final td.col-total {
            font-size: 6.4pt !important;
            text-align: center !important;
            font-weight: 800 !important;
        }

        /* ==========================================================================
           6. Bloco de Assinaturas e Rodapé (Espaço amplo para assinar na Página 1)
           ========================================================================== */
        .footer-signatures {
            margin-top: 26px;
            display: flex;
            justify-content: space-between;
            padding-top: 0;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .signature-line {
            width: 42%;
            border-top: 1.5px solid #000000;
            text-align: center;
            font-size: 7.5pt;
            font-weight: 700 !important;
            color: #000000;
            padding-top: 3px;
            line-height: 1.2;
        }
        .footer-doc {
            margin-top: 4px;
            display: flex;
            justify-content: space-between;
            font-size: 6.6pt;
            font-weight: 700;
            color: #000000;
            border-top: 1px solid #000000;
            padding-top: 2px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff; font-weight: 700; font-size: 12px;">Cantina Web — Relatório Oficial DRE Unificado (Vendas, Custódia de Carteira & Caixa Real)</div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">Período: <?php echo date('d/m/Y', strtotime($dataInicio ?? date('Y-01-01'))); ?> a <?php echo date('d/m/Y', strtotime($dataFim ?? date('Y-m-d'))); ?> &bull; Formato A4 Paisagem</div>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
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
            🖨 Imprimir / Salvar PDF (A4)
        </button>
        <button type="button" class="btn-close-action" onclick="fecharJanela()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="doc-page">
    <!-- Cabeçalho Oficial Escolar -->
    <div class="school-header">
        <?php if (!empty($logoBase64)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoBase64; ?>" alt="Logotipo Oficial">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="report-title-main">DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO UNIFICADO (DRE) — EXERCÍCIO <?php echo $anoAtual; ?></div>
            <div class="report-subtitle">Sistema de Gestão Cantina Web • Apuração Integrada de Vendas, Consumo de Saldo & Caixa Real (Zero Duplicidade)</div>
        </div>
        <div class="school-meta-col">
            <?php if (!empty($cnpj)): ?>
                <div><strong>CNPJ:</strong> <?php echo htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div><strong>Emissão:</strong> <?php echo $dataEmissao; ?></div>
            <div><strong>Unidade:</strong> Cantina Escolar Geral</div>
            <div><strong>Documento:</strong> A4 Paisagem</div>
        </div>
    </div>

    <!-- Faixa de Parâmetros e Filtros -->
    <div class="param-bar">
        <div><strong>Período Apurado:</strong> <?php echo date('d/m/Y', strtotime($dataInicio ?? date('Y-01-01'))); ?> a <?php echo date('d/m/Y', strtotime($dataFim ?? date('Y-m-d'))); ?> (Exercício <?php echo $anoAtual; ?>)</div>
        <div><strong>Regime Contábil:</strong> Unificado Integrado (Competência de Vendas + Conciliação de Carteira & Caixa Real)</div>
        <div><strong>Formato:</strong> A4 Paisagem Oficial</div>
    </div>

    <!-- Banner de Seção Oficial -->
    <div class="section-banner">
        1. Demonstrativo Comparativo Mensal — DRE Unificado: Vendas, Consumo de Saldo & Caixa Real (Ano <?php echo $anoAtual; ?>)
    </div>

    <!-- Tabela Comparativa DRE Unificado (Grade Estilo Excel) -->
    <table class="table-dre-doc">
        <thead>
            <tr>
                <th class="col-desc">Estrutura DRE Unificado</th>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <th class="col-mes font-tabular"><?php echo $mesesAbrev[$m]; ?>/<?php echo substr((string)$anoAtual, 2); ?></th>
                <?php endfor; ?>
                <th class="col-total font-tabular">TOTAL DO<br>PERÍODO</th>
            </tr>
        </thead>
        <tbody>
            <!-- BLOCO 1: APURAÇÃO OPERACIONAL DE VENDAS E CONSUMO -->
            <tr class="row-secao">
                <td class="col-desc">(+) RECEITA BRUTA DE VENDAS (LANCHES)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Consumo de Saldo Online (Pedidos Web)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['consumo_saldo_online'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['consumo_saldo_online'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Consumo de Saldo no Balcão (PDV)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['consumo_saldo_balcao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['consumo_saldo_balcao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Diretas Balcão (Dinheiro + Pix)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['vendas_diretas_balcao'] ?? (($meses[$m]['vendas_dinheiro'] ?? 0) + ($meses[$m]['vendas_pix'] ?? 0))), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['vendas_diretas_balcao'] ?? (($totais['vendas_dinheiro'] ?? 0) + ($totais['vendas_pix'] ?? 0))), 2, ',', '.'); ?></td>
            </tr>

            <!-- 2. DEDUÇÕES -->
            <tr class="row-secao">
                <td class="col-desc">(-) DEDUÇÕES E ABATIMENTOS (ESTORNOS)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_deducoes'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_deducoes'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Estornos Balcão & Pedidos Online</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)(($meses[$m]['estornos_balcao'] ?? 0) + ($meses[$m]['estornos_online'] ?? 0)), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)(($totais['estornos_balcao'] ?? 0) + ($totais['estornos_online'] ?? 0)), 2, ',', '.'); ?></td>
            </tr>

            <!-- 3. RECEITA LÍQUIDA -->
            <tr class="row-total-destaque">
                <td class="col-desc">(=) RECEITA LÍQUIDA DE VENDAS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_liquida'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_liquida'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 4. CMV -->
            <tr class="row-subitem">
                <td class="col-desc">(-) CUSTO DAS MERCADORIAS VENDIDAS (CMV)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['cmv'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['cmv'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 5. LUCRO BRUTO -->
            <tr class="row-total-destaque">
                <td class="col-desc">(=) LUCRO BRUTO OPERACIONAL</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['lucro_bruto'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['lucro_bruto'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-size: 6.5pt;">
                <td class="col-desc">Margem Bruta Operacional (%)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</td>
            </tr>

            <!-- 5.1 PAGAMENTO DE FORNECEDORES (RECIBOS EMITIDOS — SEPARADO DO BLOCO DE DESPESAS) -->
            <tr class="row-secao">
                <td class="col-desc">(-) PAGAMENTO DE FORNECEDORES (RECIBOS)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_fornecedores'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_fornecedores'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 6. DESPESAS OPERACIONAIS -->
            <tr class="row-secao">
                <td class="col-desc">(-) DESPESAS OPERACIONAIS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_operacionais_puras'] ?? ($meses[$m]['total_despesas'] ?? 0)), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_operacionais_puras'] ?? ($totais['total_despesas'] ?? 0)), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Despesas Administrativas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_administrativas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_administrativas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Pessoal & Salários</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_pessoal'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_pessoal'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas, Marketing & Embalagens</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_vendas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_vendas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Manutenção & Equipamentos</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_manutencao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_manutencao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Financeiras, Tarifas & Outras Despesas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)(($meses[$m]['despesas_financeiras'] ?? 0) + ($meses[$m]['despesas_outras'] ?? 0)), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)(($totais['despesas_financeiras'] ?? 0) + ($totais['despesas_outras'] ?? 0)), 2, ',', '.'); ?></td>
            </tr>

            <!-- 7. RESULTADO LÍQUIDO OPERACIONAL (VENDAS) -->
            <tr class="row-resultado-final">
                <td class="col-desc">(=) RESULTADO LÍQUIDO DO EXERCÍCIO (VENDAS)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['resultado_liquido'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['resultado_liquido'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-size: 6.5pt; color: #000000; font-weight: 700;">
                <td class="col-desc">Margem Líquida Operacional (%)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</td>
            </tr>

            <!-- BLOCO 2: CONCILIAÇÃO DE CARTEIRA PRÉ-PAGA & RESULTADO REAL EM CONTA (SEM DUPLICIDADE) -->
            <tr class="row-secao">
                <td class="col-desc" colspan="14" style="font-weight: 700; text-transform: uppercase; font-size: 6.7pt; color: #ffffff !important; background-color: #0f172a !important; border-top: 2px solid #000000 !important;">
                    2. CONCILIAÇÃO DE CARTEIRA PRÉ-PAGA & RESULTADO FINANCEIRO REAL EM CONTA (RECARGAS RECEBIDAS − SALDO GASTO EM VENDAS = ZERO DUPLICIDADE)
                </td>
            </tr>
            <tr class="row-secao">
                <td class="col-desc">(+) RECARGAS RECEBIDAS NA CONTA</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_recargas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_recargas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Recargas Pix Online (Portal Família)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['recargas_pix_online'] ?? ($meses[$m]['recargas_pix'] ?? 0)), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['recargas_pix_online'] ?? ($totais['recargas_pix'] ?? 0)), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Recargas no Balcão PDV (Pix + Dinheiro)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['recargas_balcao'] ?? (($meses[$m]['recargas_pix_pdv'] ?? 0) + ($meses[$m]['recargas_dinheiro'] ?? 0))), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['recargas_balcao'] ?? (($totais['recargas_pix_pdv'] ?? 0) + ($totais['recargas_dinheiro'] ?? 0))), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">(-) Abatimento do Saldo Gasto em Vendas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['consumo_saldo_total'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['consumo_saldo_total'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-weight: 700;">
                <td class="col-desc">(=) Variação Líquida de Saldo em Conta</td>
                <?php for ($m = 1; $m <= 12; $m++): 
                    $difM = (float)($meses[$m]['diferenca_recarga_consumo'] ?? 0);
                ?>
                    <td class="col-mes font-tabular">
                        <?php echo ($difM > 0 ? '+' : '') . number_format($difM, 2, ',', '.'); ?>
                    </td>
                <?php endfor; 
                $difT = (float)($totais['diferenca_recarga_consumo'] ?? 0);
                ?>
                <td class="col-total font-tabular">
                    <?php echo ($difT > 0 ? '+' : '') . number_format($difT, 2, ',', '.'); ?>
                </td>
            </tr>
            <tr class="row-total-destaque">
                <td class="col-desc">(=) ARRECADAÇÃO LÍQUIDA NA CONTA</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['arrecadacao_liquida'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['arrecadacao_liquida'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-resultado-final">
                <td class="col-desc">(=) RESULTADO FINANCEIRO EM CONTA</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['resultado_caixa'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['resultado_caixa'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Assinaturas e Rodapé -->
    <div class="footer-signatures">
        <div class="signature-line">
            <strong>Gerente Operacional / Financeiro</strong><br>
            Cantina Web — Colégio Monteiro Lobato
        </div>
        <div class="signature-line">
            <strong>Direção Administrativa / Escolar</strong><br>
            Colégio Monteiro Lobato
        </div>
    </div>

    <div class="footer-doc">
        <div>Colégio Monteiro Lobato • Sistema Integrado Cantina Web • Módulo Financeiro & DRE Unificado</div>
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

function fecharJanela() {
    window.close();
    setTimeout(function() {
        if (!window.closed) {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?php echo $baseUrl; ?>/financeiro/dre';
            }
        }
    }, 150);
}
</script>
</body>
</html>
