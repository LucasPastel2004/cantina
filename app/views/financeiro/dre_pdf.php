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
$totais = $dre['totais'] ?? [];
$razaoSocial = $config->razao_social ?? 'COLÉGIO MONTEIRO LOBATO';
$cnpj = $config->cnpj ?? '';
$isPdf = $isPdf ?? false;
$regime = $regime ?? 'competencia';
$isCaixa = ($regime === 'caixa');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRE Gerencial <?php echo $anoAtual; ?> (<?php echo $isCaixa ? 'Regime de Caixa' : 'Regime de Competência'; ?>) — Colégio Monteiro Lobato</title>
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
            min-height: 195mm;
            margin: 20px auto;
            padding: 10mm 14mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .doc-page {
                width: 297mm !important;
                max-width: 297mm !important;
                min-height: 210mm !important;
                margin: 0 auto !important;
                padding: 10mm 14mm !important;
                box-shadow: none !important;
                border: none !important;
                box-sizing: border-box !important;
            }
        }

        /* ==========================================================================
           3. Barra Superior de Controle Padronizada (#1e293b - Apenas em Tela)
           ========================================================================== */
        .action-bar-top {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            width: 280mm;
            max-width: calc(100% - 32px);
            margin: 0 auto 16px auto;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 10px;
            z-index: 1000;
        }
        .action-bar-top .zoom-controls {
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
        .action-bar-top .btn-zoom {
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
        .action-bar-top .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .action-bar-top .btn-zoom-val {
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
        .action-bar-top .btn-zoom-val:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: underline;
        }
        .action-bar-top .btn-action-nav {
            background: #ffffff;
            color: #0f172a;
            border: none;
            border-radius: 4px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease-in-out;
        }
        .action-bar-top .btn-action-nav:hover {
            background: #f1f5f9;
            color: #1d4ed8;
        }
        .action-bar-top .btn-primary {
            background: #1d4ed8;
            color: #ffffff;
            border: 1px solid #1e40af;
        }
        .action-bar-top .btn-primary:hover {
            background: #1e40af;
            color: #ffffff;
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
           4. Cabeçalho Escolar Oficial
           ========================================================================== */
        .header-box {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }
        .header-text {
            flex: 1;
        }
        .header-text h1 {
            font-size: 13pt;
            font-weight: 700;
            margin: 0 0 2px 0;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .header-text p {
            margin: 0;
            font-size: 8.5pt;
            color: #334155;
        }
        .header-meta {
            text-align: right;
            font-size: 8pt;
            color: #475569;
            padding-right: 2px;
        }

        /* ==========================================================================
           5. Tabela DRE — Estética de Grade Excel com Linhas Pretas e Fundo 100% Branco
           ========================================================================== */
        table.table-dre-doc {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            font-size: 7.4pt;
            border: 1px solid #000000 !important;
            margin-top: 4px;
        }

        table.table-dre-doc th,
        table.table-dre-doc td {
            border: 1px solid #000000 !important;
            padding: 3px 4px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
        }

        table.table-dre-doc thead th {
            font-weight: 700 !important;
            text-align: center;
            font-size: 7.2pt;
            text-transform: uppercase;
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
            width: 15.1%;
            text-align: left !important;
            padding: 3px 5px !important;
            word-break: break-word;
        }
        .col-mes {
            width: 6.35%;
            text-align: right !important;
            padding: 3px 3px !important;
            white-space: nowrap !important;
        }
        .col-total {
            width: 8.7%;
            font-weight: 700 !important;
        }
        th.col-total {
            text-align: center !important;
            white-space: normal !important;
            line-height: 1.15 !important;
            font-size: 7.0pt !important;
            padding: 2px 2px !important;
        }
        td.col-total {
            text-align: right !important;
            white-space: nowrap !important;
            padding: 3px 4px !important;
        }

        .row-secao {
            font-weight: 700 !important;
        }
        .row-subitem td.col-desc {
            padding-left: 14px !important;
        }
        .row-total-destaque td {
            font-weight: 700 !important;
        }
        .row-resultado-final td {
            font-weight: 700 !important;
            border-top: 2px solid #000000 !important;
            border-bottom: 2px solid #000000 !important;
        }

        /* ==========================================================================
           6. Bloco de Assinaturas e Rodapé
           ========================================================================== */
        .footer-signatures {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            padding-top: 25px;
            page-break-inside: avoid;
        }
        .signature-line {
            width: 42%;
            border-top: 1px solid #000000;
            text-align: center;
            font-size: 8pt;
            color: #000000;
            padding-top: 4px;
        }
        .footer-doc {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            color: #475569;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="action-bar-top no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff; font-weight: 700; font-size: 12px;">Cantina Web — Relatório Oficial DRE (<?php echo $isCaixa ? 'Regime de Caixa' : 'Regime de Competência'; ?>)</div>
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

        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-action-nav btn-primary" onclick="window.print()">
                🖨 Imprimir Documento
            </button>
            <button type="button" class="btn-action-nav" onclick="fecharJanela()">
                ✕ Fechar
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="doc-page">
    <!-- Cabeçalho Oficial -->
    <div class="header-box">
        <?php if (!empty($logoBase64)): ?>
            <img src="<?php echo $logoBase64; ?>" alt="Logotipo Oficial" class="header-logo">
        <?php endif; ?>
        <div class="header-text">
            <h1><?php echo htmlspecialchars($razaoSocial, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>
                DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO (DRE) &bull; <?php echo $isCaixa ? 'REGIME DE CAIXA (RECARGAS & ARRECADAÇÃO)' : 'REGIME DE COMPETÊNCIA (VENDAS & CONSUMO)'; ?> — PERÍODO DE <?php echo date('d/m/Y', strtotime($dataInicio ?? date('Y-01-01'))); ?> A <?php echo date('d/m/Y', strtotime($dataFim ?? date('Y-m-d'))); ?>
            </p>
        </div>
        <div class="header-meta">
            <?php if (!empty($cnpj)): ?>
                <div><strong>CNPJ:</strong> <?php echo htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div><strong>Emissão:</strong> <?php echo $dataEmissao; ?></div>
            <div><strong>Unidade:</strong> Cantina Escolar Geral</div>
        </div>
    </div>

    <!-- Tabela Comparativa DRE (Grade Estilo Excel) -->
    <table class="table-dre-doc">
        <thead>
            <tr>
                <th class="col-desc">Estrutura DRE</th>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <th class="col-mes font-tabular"><?php echo $mesesAbrev[$m]; ?>/<?php echo substr((string)$anoAtual, 2); ?></th>
                <?php endfor; ?>
                <th class="col-total font-tabular">TOTAL DO<br>PERÍODO</th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. RECEITA BRUTA -->
            <?php if ($isCaixa): ?>
            <tr class="row-secao">
                <td class="col-desc">(+) RECEITA ARRECADADA / CASH-IN (RECARGAS & VENDAS DIRETAS)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Recargas Pix Online (Carteira Familiar)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['recargas_pix'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['recargas_pix'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Recargas no Balcão (Dinheiro)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['recargas_dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['recargas_dinheiro'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Balcão em Dinheiro</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['vendas_dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['vendas_dinheiro'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Balcão em Pix</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['vendas_pix'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['vendas_pix'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <?php else: ?>
            <tr class="row-secao">
                <td class="col-desc">(+) RECEITA BRUTA DE VENDAS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Cantina Web (Online)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_online'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_online'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Presenciais no Balcão</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_balcao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_balcao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <?php endif; ?>

            <!-- 2. DEDUÇÕES -->
            <tr class="row-secao">
                <td class="col-desc">(-) DEDUÇÕES E ABATIMENTOS (ESTORNOS)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_deducoes'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_deducoes'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Estornos de Balcão</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['estornos_balcao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['estornos_balcao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Estornos Pedidos Online</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['estornos_online'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['estornos_online'] ?? 0), 2, ',', '.'); ?></td>
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
                <td class="col-desc">(=) LUCRO BRUTO</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['lucro_bruto'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['lucro_bruto'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-size: 7.2pt; color: #475569;">
                <td class="col-desc">Margem Bruta (%)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</td>
            </tr>

            <!-- 6. DESPESAS OPERACIONAIS -->
            <tr class="row-secao">
                <td class="col-desc">(-) DESPESAS OPERACIONAIS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_despesas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_despesas'] ?? 0), 2, ',', '.'); ?></td>
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
                <td class="col-desc">Despesas Financeiras & Tarifas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_financeiras'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_financeiras'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Outras Despesas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_outras'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_outras'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 7. RESULTADO LÍQUIDO DO EXERCÍCIO -->
            <tr class="row-resultado-final">
                <td class="col-desc">(=) RESULTADO LÍQUIDO DO EXERCÍCIO</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['resultado_liquido'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['resultado_liquido'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-size: 7.2pt; color: #000000; font-weight: 700;">
                <td class="col-desc">Margem Líquida Real (%)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</td>
            </tr>

            <?php if ($isCaixa): ?>
            <!-- SUBSEÇÃO INFORMATIVA: CONTROLE DE CARTEIRA (RECARGAS VS CONSUMO) -->
            <tr class="row-secao" style="background-color: #f8fafc !important;">
                <td class="col-desc" colspan="14" style="font-weight: 700; text-transform: uppercase; font-size: 7.2pt; color: #0f172a; background-color: #f1f5f9 !important; border-top: 2px solid #000000 !important;">
                    INFORMATIVO DE CONTROLE DA CARTEIRA: RECARGAS VS CONSUMO REAL DE LANCHES POR SALDO
                </td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Total de Recargas da Carteira (Entrada Nova)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_recargas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_recargas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">(-) Total Consumido em Saldo (Pedidos + Balcão)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['consumo_saldo_total'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['consumo_saldo_total'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-weight: 700;">
                <td class="col-desc">(=) Variação Líquida de Saldo (Recargas - Consumo)</td>
                <?php for ($m = 1; $m <= 12; $m++): 
                    $difM = (float)($meses[$m]['diferenca_recarga_consumo'] ?? 0);
                ?>
                    <td class="col-mes font-tabular" style="<?php echo $difM < 0 ? 'color: #dc2626 !important;' : ''; ?>">
                        <?php echo ($difM > 0 ? '+' : '') . number_format($difM, 2, ',', '.'); ?>
                    </td>
                <?php endfor; 
                $difT = (float)($totais['diferenca_recarga_consumo'] ?? 0);
                ?>
                <td class="col-total font-tabular" style="<?php echo $difT < 0 ? 'color: #dc2626 !important;' : ''; ?>">
                    <?php echo ($difT > 0 ? '+' : '') . number_format($difT, 2, ',', '.'); ?>
                </td>
            </tr>
            <?php endif; ?>
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
        <div>Colégio Monteiro Lobato • Sistema Integrado Cantina Web • Módulo Financeiro & DRE</div>
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
