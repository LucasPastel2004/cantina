<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório A4 Timbrado de Corte do Turno para Fornecedores
 * 
 * Conforme docs/FSD.md (Área 2, Tela 3.1) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dataConsumo = $dadosCorte['data_consumo'] ?? date('Y-m-d');
$dataFmt = date('d/m/Y', strtotime($dataConsumo));
$turno = ucfirst($dadosCorte['turno_consumo'] ?? 'matutino');
$itens = $dadosCorte['itens'] ?? [];
$totalPecas = (int)($dadosCorte['total_pecas'] ?? 0);
$totalPedidos = (int)($dadosCorte['total_pedidos'] ?? 0);
$totalAlunos = (int)($dadosCorte['total_alunos'] ?? 0);
$statusCorte = $dadosCorte['status_corte']['rotulo'] ?? 'Corte Fechado';
$emissao = date('d/m/Y H:i:s');
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedido Fornecedor — <?php echo htmlspecialchars($dataFmt . ' ' . $turno); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 14mm 10mm 14mm;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
            width: 100%;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }

        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 20px 10px;
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
                position: relative;
            }
            .no-print {
                margin-bottom: 15px;
                background: #f8fafc;
                padding: 8px 12px;
                border: 1px solid #cbd5e1;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 4px;
            }
        }

        @media print {
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .print-header, .stat-grid, .sign-box { page-break-inside: avoid; break-inside: avoid; }

        .print-header {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 10px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .logo-img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
        .school-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0; }
        .report-title { font-size: 12px; font-weight: 600; color: #1d4ed8; margin: 2px 0 0 0; }
        .report-sub { font-size: 9px; color: #64748b; margin: 1px 0 0 0; }
        .stat-grid {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 16px;
        }
        .stat-item { text-align: left; }
        .stat-label { font-size: 8.5px; font-weight: 600; color: #64748b; text-transform: uppercase; }
        .stat-value { font-size: 12px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
        .data-table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            margin-bottom: 20px;
            font-size: 10.5px;
            border: 1px solid #000000 !important;
            box-sizing: border-box !important;
        }
        .data-table th {
            background: #ffffff !important;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #000000 !important;
            text-align: left;
        }
        .data-table th:first-child,
        .data-table td:first-child {
            border-left: 1px solid #000000 !important;
        }
        .data-table th:last-child,
        .data-table td:last-child {
            border-right: 1px solid #000000 !important;
        }
        .data-table tbody tr {
            background: #ffffff !important;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #000000 !important; /* Linhas pretas estilo Excel */
            font-size: 10.5px;
            background: #ffffff !important;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .data-table tr:nth-child(even) { background: #ffffff !important; }
        .cell-code { font-family: monospace; font-weight: 600; color: #334155; }
        .cell-right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        .total-row { background: #ffffff !important; font-weight: 700; }
        .total-row td { border: 1.5px solid #000000 !important; font-size: 12px; background: #ffffff !important; }
        .sign-box {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
        }
        .sign-line {
            width: 45%;
            text-align: center;
            border-top: 1px solid #000000;
            padding-top: 4px;
            font-size: 9px;
            color: #475569;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="no-print">
        <span><strong>Relatório de Corte do Turno</strong> — <?php echo htmlspecialchars($dataFmt . ' ' . $turno); ?></span>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button onclick="window.print()" style="padding: 6px 14px; font-weight: bold; background: #1d4ed8; color: #fff; border: 1px solid #1e40af; cursor: pointer; border-radius: 4px; display: inline-flex; align-items: center; gap: 6px; font-size: 11px;" title="Imprimir em folha A4 ou Salvar como PDF">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir ou Salvar em PDF
            </button>
            <button onclick="window.close()" style="padding: 6px 12px; background: #fff; color: #334155; border: 1px solid #cbd5e1; cursor: pointer; border-radius: 4px; font-size: 11px;" title="Fechar visualização">
                Fechar
            </button>
        </div>
    </div>

    <div class="print-header">
        <div class="header-left">
            <?php if (!empty($logoDataUri)): ?>
                <img src="<?php echo $logoDataUri; ?>" alt="Logo" class="logo-img">
            <?php endif; ?>
            <div>
                <div class="school-title">Colégio Monteiro Lobato — Cantina Escolar</div>
                <div class="report-title">Consolidação de Compra de Lanches — Fornecedor Parceiro</div>
                <div class="report-sub">Emissão do Sistema Cantina Web &bull; <?php echo $emissao; ?></div>
            </div>
        </div>
        <div style="text-align: right; padding-right: 2px;">
            <div style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo $dataFmt; ?> &bull; <?php echo $turno; ?></div>
            <div style="font-size: 9px; color: #16a34a; font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($statusCorte); ?></div>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Total de Peças</div>
            <div class="stat-value"><?php echo $totalPecas; ?> un</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Pedidos Encomendados</div>
            <div class="stat-value"><?php echo $totalPedidos; ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Alunos Atendidos</div>
            <div class="stat-value"><?php echo $totalAlunos; ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Turno Letivo</div>
            <div class="stat-value"><?php echo $turno; ?></div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Código</th>
                <th style="width: 50%;">Descrição do Alimento</th>
                <th style="width: 20%;">Classificação</th>
                <th style="width: 15%; text-align: right;">Qtd Encomendar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($itens)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; padding: 12px;">Não há encomendas consolidadas para este turno.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($itens as $it): ?>
                    <tr>
                        <td class="cell-code font-tabular"><?php echo htmlspecialchars($it['codigo_interno'] ?? ($it['produto_codigo'] ?? '-')); ?></td>
                        <td><strong><?php echo htmlspecialchars($it['descricao'] ?? ($it['produto_nome'] ?? '-')); ?></strong></td>
                        <td style="color: #64748b;"><?php echo htmlspecialchars($it['categoria_nome'] ?? 'Geral'); ?></td>
                        <td class="cell-right font-tabular"><?php echo (int)($it['quantidade_total'] ?? 0); ?> un</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">TOTAL GERAL DE SALGADOS A FORNECER</td>
                    <td class="cell-right font-tabular"><?php echo $totalPecas; ?> PEÇAS</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="sign-box">
        <div class="sign-line">
            Responsável pelo Recebimento na Cantina
        </div>
        <div class="sign-line">
            Fornecedor Parceiro / Entregador
        </div>
    </div>
</div>
</body>
</html>
