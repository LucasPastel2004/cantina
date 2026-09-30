<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório A4 Timbrado de Vendas da Cantina por Alimento
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.2) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dadosVendas ?? ($dados ?? []);
$periodo = $dados['periodo'] ?? (date('d/m/Y') . ' a ' . date('d/m/Y'));
$turnoStr = $dados['turno'] ?? 'Todos os Turnos';
$categoriaStr = $dados['categoria'] ?? 'Todas as Categorias';
$itens = $dados['itens'] ?? [];
$totais = $dados['totais'] ?? [];
$operador = $dados['operador'] ?? 'Administração Geral';
$emissao = date('d/m/Y H:i:s');

$qtdTotal = (int)($totais['quantidade_total'] ?? 0);
$fatTotal = (float)($totais['faturamento_total'] ?? 0.0);
$ticketMedio = ($qtdTotal > 0) ? ($fatTotal / $qtdTotal) : 0.0;
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Vendas por Alimento — Cantina Web</title>
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
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }
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
        .filter-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 9.5px;
            color: #334155;
            margin-bottom: 12px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        th {
            background: #f1f5f9;
            color: #475569;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 5px 8px;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) { background: #fafafa; }
        .cell-code { font-family: monospace; font-weight: 600; color: #334155; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td {
            background: #eff6ff !important;
            font-weight: 700;
            border-top: 1px solid #1d4ed8;
            border-bottom: 1px solid #1d4ed8;
            font-size: 11px;
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
        <span><strong>Vendas por Alimento</strong> — Período: <?php echo htmlspecialchars($periodo); ?></span>
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
                <div class="school-title">COLÉGIO MONTEIRO LOBATO</div>
                <div class="report-title">Relatório de Emissão de Vendas da Cantina por Alimento</div>
                <div class="report-sub">Período: <?php echo htmlspecialchars($periodo); ?> &bull; Emissão: <?php echo $emissao; ?> &bull; Operador: <?php echo htmlspecialchars($operador); ?></div>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 13px; font-weight: 700; color: #0f172a;">CURVA ABC</div>
            <div style="font-size: 9px; color: #1d4ed8; font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($turnoStr); ?></div>
        </div>
    </div>

    <div class="filter-box">
        <strong>Filtros Aplicados:</strong> Período: <?php echo htmlspecialchars($periodo); ?> &bull; Turno: <?php echo htmlspecialchars($turnoStr); ?> &bull; Categoria: <?php echo htmlspecialchars($categoriaStr); ?>
    </div>

    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Itens Comercializados</div>
            <div class="stat-value font-tabular"><?php echo $qtdTotal; ?> un</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Faturamento Total</div>
            <div class="stat-value font-tabular" style="color: #1d4ed8;">R$ <?php echo number_format($fatTotal, 2, ',', '.'); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Preço Médio por Item</div>
            <div class="stat-value font-tabular">R$ <?php echo number_format($ticketMedio, 2, ',', '.'); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Código</th>
                <th style="width: 44%;">Descrição do Alimento</th>
                <th style="width: 16%;">Categoria</th>
                <th style="width: 12%; text-align: center;">Quantidade</th>
                <th style="width: 14%; text-align: right;">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($itens)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="color: #64748b; padding: 12px;">Não há vendas registradas no período filtrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($itens as $it): ?>
                    <tr>
                        <td class="cell-code"><?php echo htmlspecialchars($it['codigo_interno'] ?? ($it['codigo'] ?? '-')); ?></td>
                        <td><strong><?php echo htmlspecialchars($it['descricao'] ?? ($it['produto_nome'] ?? '-')); ?></strong></td>
                        <td style="color: #475569;"><?php echo htmlspecialchars($it['categoria_nome'] ?? 'Geral'); ?></td>
                        <td class="text-center font-tabular"><?php echo (int)($it['quantidade_total'] ?? ($it['quantidade'] ?? 0)); ?> un</td>
                        <td class="text-right font-tabular">R$ <?php echo number_format((float)($it['valor_total'] ?? 0), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">TOTAL GERAL DE VENDAS NO PERÍODO</td>
                    <td class="text-center font-tabular"><?php echo $qtdTotal; ?> un</td>
                    <td class="text-right font-tabular">R$ <?php echo number_format($fatTotal, 2, ',', '.'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
