<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Espelho Oficial de Impressão do Livro Caixa Diário
 * 
 * Emissão nativa via navegador pelo recurso "Print to PDF" (window.print())
 * Padrão A4 Paisagem (Landscape) com cabeçalho timbrado, 13 colunas e precisão contábil.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dados ?? [];
$linhas = $linhas ?? [];
$totais = $totais ?? [];
$periodoOficial = $periodoOficial ?? '';
$saldoAnterior = (float)($saldoAnterior ?? 0.0);
$saldoFinal = (float)($saldoFinal ?? 0.0);
$logoDataUri = $logoDataUri ?? '';
$isPdf = $isPdf ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livro Caixa Diário — <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?> — Cantina Web</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Helvetica, Arial, sans-serif;
            background-color: #525659;
            color: #000000;
            font-size: 7.5pt;
            line-height: 1.35;
        }

        .no-print {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .btn-print-action {
            background-color: #1d4ed8;
            color: #ffffff;
            border: 1px solid #2563eb;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-print-action:hover {
            background-color: #1e40af;
        }

        .btn-close-action {
            background: transparent;
            color: #cbd5e1;
            border: 1px solid #475569;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 6px;
        }
        .btn-close-action:hover {
            background: #334155;
            color: #ffffff;
        }

        .zoom-controls {
            display: inline-flex;
            align-items: center;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 4px;
            padding: 2px;
            margin-right: 8px;
        }
        .btn-zoom {
            background: transparent;
            border: none;
            color: #e2e8f0;
            width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
            border-radius: 2px;
        }
        .btn-zoom:hover {
            background: #334155;
            color: #ffffff;
        }
        .btn-zoom-val {
            background: transparent;
            border: none;
            color: #e2e8f0;
            padding: 0 6px;
            font-size: 11px;
            font-family: monospace;
            cursor: pointer;
            min-width: 44px;
            text-align: center;
        }

        .document-zoom-area {
            transform-origin: top center;
            transition: transform 0.15s ease-out;
            width: 100%;
        }

        /* Container A4 Paisagem (Landscape) */
        @media screen {
            .container {
                width: 297mm;
                min-height: 210mm;
                margin: 48px auto 30px auto;
                padding: 10mm 12mm;
                background: #ffffff;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
                box-sizing: border-box;
                border-radius: 2px;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm 10mm 10mm;
            }
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 7pt;
            }
            .container {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
            }
        }

        .school-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .school-logo-col img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
        }
        .school-title-col {
            flex-grow: 1;
            padding-left: 12px;
        }
        .school-name {
            font-size: 11.5pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
        }
        .report-title-main {
            font-size: 10.5pt;
            font-weight: 700;
            color: #1d4ed8;
            letter-spacing: 0.02em;
        }
        .school-meta-col {
            text-align: right;
            font-size: 7.2pt;
            color: #475569;
        }

        .param-bar {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            font-size: 7.5pt;
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            align-items: center;
        }
        .param-bar strong {
            color: #0f172a;
        }

        /* Tabela Excel Contábil */
        table.table-doc-excel {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            border: 1px solid #000000 !important;
            border-bottom: none !important;
            border-right: none !important;
            font-size: 7.2pt;
            margin-bottom: 12px;
        }
        table.table-doc-excel thead tr {
            background-color: #f1f5f9 !important;
        }
        table.table-doc-excel th {
            border-right: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            padding: 4px 5px !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 6.8pt;
            letter-spacing: 0.02em;
            color: #0f172a !important;
        }
        table.table-doc-excel td {
            border-right: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            padding: 3px 5px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            color: #000000 !important;
        }
        table.table-doc-excel tfoot tr {
            background-color: #f1f5f9 !important;
            font-weight: 700;
        }
        table.table-doc-excel tfoot td {
            border-top: 1.5px solid #000000 !important;
            padding: 4px 5px !important;
        }

        .font-tabular {
            font-feature-settings: 'tnum' 1, 'zero' 1;
            font-variant-numeric: tabular-nums;
            font-family: 'Segoe UI', monospace;
        }
        .text-center { text-align: center !important; }
        .text-start  { text-align: left !important; }
        .text-end    { text-align: right !important; }

        .footer-assinaturas-box {
            display: flex;
            justify-content: space-between;
            margin-top: 16px;
            padding: 0 40px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-line {
            width: 38%;
            border-top: 1px solid #000000;
            text-align: center;
            padding-top: 3px;
            font-size: 7.2pt;
            color: #000000;
        }
        .doc-footer-meta {
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
            margin-top: 10px;
            font-size: 6.5pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 12px; font-weight: 600;">
        Cantina Web — Livro Caixa Diário (Período: <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?>)
    </div>
    <div style="display: flex; align-items: center;">
        <div class="zoom-controls">
            <button type="button" class="btn-zoom" onclick="alterarZoom(-0.15)">−</button>
            <button type="button" class="btn-zoom-val" id="btnZoomReset" onclick="resetarZoom()">100%</button>
            <button type="button" class="btn-zoom" onclick="alterarZoom(0.15)">+</button>
        </div>
        <button type="button" class="btn-print-action" onclick="window.print()">
            🖨 Imprimir / Salvar PDF
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
            <div style="font-size: 7.2pt; color: #475569;">Sistema de Gestão Cantina Web • Módulo Financeiro & Fluxo de Caixa</div>
        </div>
        <div class="school-meta-col">
            <div><strong>Emissão:</strong> <?php echo date('d/m/Y H:i'); ?></div>
            <div><strong>Unidade:</strong> Cantina Geral</div>
            <div><strong>Documento:</strong> A4 Paisagem Oficial</div>
        </div>
    </div>

    <!-- Faixa de Parâmetros e Posição do Livro Caixa -->
    <div class="param-bar">
        <div><strong>Período:</strong> <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Saldo Anterior:</strong> R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?></div>
        <div><strong>(+) Total Entradas:</strong> R$ <?php echo number_format((float)($totais['credito'] ?? 0), 2, ',', '.'); ?></div>
        <div><strong>(-) Total Saídas:</strong> R$ <?php echo number_format((float)($totais['debito'] ?? 0), 2, ',', '.'); ?></div>
        <div><strong>(=) Saldo Final:</strong> R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?></div>
    </div>

    <!-- Tabela Oficial do Livro Caixa (13 Colunas) -->
    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 6.5%; text-align: center;">Data</th>
                <th style="width: 5.5%; text-align: left;">Dia</th>
                <th style="width: 22%; text-align: left;">Descrição / Histórico</th>
                <th style="width: 6.8%; text-align: right;">Pix Online</th>
                <th style="width: 6.8%; text-align: right;">Rec. PDV Pix</th>
                <th style="width: 6.8%; text-align: right;">Rec. PDV Din</th>
                <th style="width: 6.8%; text-align: right;">Venda Pix</th>
                <th style="width: 6.8%; text-align: right;">Venda Din</th>
                <th style="width: 6.8%; text-align: right;">Pgto Pix</th>
                <th style="width: 6.8%; text-align: right;">Pgto Din</th>
                <th style="width: 7.2%; text-align: right;">Crédito</th>
                <th style="width: 7.2%; text-align: right;">Débito</th>
                <th style="width: 8.0%; text-align: right;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <!-- Saldo Anterior -->
            <tr style="background-color: #f8fafc; font-weight: 700;">
                <td class="text-center font-tabular">—</td>
                <td class="text-start">—</td>
                <td class="text-start">SALDO ANTERIOR (Posição em <?php echo date('d/m/Y', strtotime($dados['data_inicio'])); ?>)</td>
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
                <td class="text-end font-tabular" style="font-size: 7.8pt;">
                    R$ <?php echo number_format($totais['credito'] ?? 0, 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular" style="font-size: 7.8pt;">
                    R$ <?php echo number_format($totais['debito'] ?? 0, 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular" style="font-size: 8.2pt;">
                    <strong>R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?></strong>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Assinaturas Formais -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong>Operador / Tesouraria Financeira</strong><br>
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
</div>

<?php if (!$isPdf): ?>
<script>
    let currentZoom = 1.0;
    const minZoom = 0.5;
    const maxZoom = 2.0;

    function aplicarZoom(novoZoom) {
        currentZoom = Math.min(Math.max(novoZoom, minZoom), maxZoom);
        currentZoom = Math.round(currentZoom * 100) / 100;
        
        const zoomArea = document.getElementById('document-zoom-area');
        const zoomValueBtn = document.getElementById('btnZoomReset');
        
        if (zoomArea) {
            zoomArea.style.zoom = currentZoom;
        }
        
        if (zoomValueBtn) {
            zoomValueBtn.textContent = Math.round(currentZoom * 100) + '%';
        }
    }

    function alterarZoom(delta) {
        aplicarZoom(currentZoom + delta);
    }

    function resetarZoom() {
        aplicarZoom(1.0);
    }
</script>
<?php endif; ?>

</body>
</html>
