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
        @page { size: A4 portrait; margin: 12mm 15mm; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.4;
        }
        .container { max-width: 180mm; margin: 0 auto; }
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
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }
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
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background: #f1f5f9;
            color: #475569;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10.5px;
        }
        .data-table tr:nth-child(even) { background: #fafafa; }
        .cell-code { font-family: monospace; font-weight: 600; color: #334155; }
        .cell-right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        .total-row { background: #eff6ff !important; font-weight: 700; }
        .total-row td { border-top: 1px solid #1d4ed8; border-bottom: 1px solid #1d4ed8; font-size: 12px; }
        .sign-box {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
        }
        .sign-line {
            width: 45%;
            text-align: center;
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
            font-size: 9px;
            color: #475569;
        }
        .no-print {
            margin-bottom: 15px;
            background: #f8fafc;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="container">
    <div class="no-print">
        <span><strong>Relatório de Corte do Turno</strong> — <?php echo htmlspecialchars($dataFmt . ' ' . $turno); ?></span>
        <div>
            <button onclick="window.print()" style="padding: 5px 12px; font-weight: bold; background: #1d4ed8; color: #fff; border: 1px solid #1e40af; cursor: pointer; border-radius: 3px;">
                Imprimir Documento
            </button>
            <button onclick="window.close()" style="padding: 5px 10px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer; border-radius: 3px; margin-left: 6px;">
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
        <div style="text-align: right;">
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
