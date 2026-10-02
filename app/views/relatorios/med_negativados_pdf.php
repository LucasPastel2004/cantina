<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório A4 Timbrado de Famílias com Saldo Devedor / MED
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.3) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dadosMed ?? ($dados ?? []);
$familias = $dados['familias'] ?? [];
$totalDevedor = (float)($dados['total_devedor'] ?? 0.0);
$totalFamilias = (int)($dados['total_familias'] ?? count($familias));
$emissao = date('d/m/Y H:i:s');
$operador = $dados['operador'] ?? 'Administração Geral';
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Famílias Negativadas MED — Cantina Web</title>
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
            border-bottom: 2px solid #b91c1c;
            padding-bottom: 10px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .logo-img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
        .school-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0; }
        .report-title { font-size: 12px; font-weight: 600; color: #b91c1c; margin: 2px 0 0 0; }
        .report-sub { font-size: 9px; color: #64748b; margin: 1px 0 0 0; }
        .stat-grid {
            display: flex;
            justify-content: space-between;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 16px;
        }
        .stat-item { text-align: left; }
        .stat-label { font-size: 8.5px; font-weight: 600; color: #991b1b; text-transform: uppercase; }
        .stat-value { font-size: 12px; font-weight: 700; color: #b91c1c; font-variant-numeric: tabular-nums; }
        table {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }
        th {
            background: #f8fafc;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 8px;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            text-align: left;
        }
        th:first-child,
        td:first-child {
            border-left: 1px solid #000000 !important;
        }
        th:last-child,
        td:last-child {
            border-right: 1px solid #000000 !important;
        }
        td {
            padding: 5px 8px;
            border-bottom: 1px solid #000000 !important; /* Linha preta entre os itens das listas */
            background-color: #ffffff !important; /* Fundo branco puro nos itens das listas */
        }
        tr:nth-child(even) td { background-color: #ffffff !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td {
            background: #ffffff !important;
            font-weight: 700;
            border-top: 1px solid #000000 !important;
            border-bottom: 1px solid #000000 !important;
            font-size: 11px;
            color: #b91c1c;
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
        <span><strong>Relatório de Devedores / MED</strong> — <?php echo $totalFamilias; ?> família(s)</span>
        <div>
            <button onclick="window.print()" style="padding: 5px 12px; font-weight: bold; background: #b91c1c; color: #fff; border: 1px solid #991b1b; cursor: pointer; border-radius: 3px;">
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
                <div class="report-title">Relatório de Famílias com Saldo Devedor — Contestações MED / Inadimplência</div>
                <div class="report-sub">Emissão: <?php echo $emissao; ?> &bull; Responsável: <?php echo htmlspecialchars($operador); ?></div>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 13px; font-weight: 700; color: #b91c1c;">AUDITORIA MED</div>
            <div style="font-size: 9px; color: #991b1b; font-weight: 600; text-transform: uppercase;">Bloqueio Cautelar</div>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Famílias Negativadas</div>
            <div class="stat-value font-tabular"><?php echo $totalFamilias; ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Montante Total Devedor</div>
            <div class="stat-value font-tabular">R$ <?php echo number_format($totalDevedor, 2, ',', '.'); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Responsável Financeiro</th>
                <th style="width: 15%;">CPF</th>
                <th style="width: 15%;">WhatsApp</th>
                <th style="width: 25%;">Estudante(s) Vinculado(s)</th>
                <th style="width: 10%; text-align: center;">Status MED</th>
                <th style="width: 10%; text-align: right;">Saldo Devedor</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($familias)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #64748b; padding: 12px;">Não há famílias negativadas ou com bloqueio MED ativo no momento.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($familias as $f): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($f['nome'] ?? '-'); ?></strong></td>
                        <td class="font-tabular"><?php echo htmlspecialchars($f['cpf'] ?? '-'); ?></td>
                        <td class="font-tabular"><?php echo htmlspecialchars($f['whatsapp'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($f['alunos_str'] ?? '-'); ?></td>
                        <td class="text-center">
                            <?php if (!empty($f['bloqueado_med'])): ?>
                                <span style="color: #b91c1c; font-weight: bold;">BLOQUEADO</span>
                            <?php else: ?>
                                <span style="color: #475569;">Ativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-tabular" style="color: #b91c1c; font-weight: bold;">
                            R$ <?php echo number_format((float)($f['saldo_atual'] ?? 0), 2, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5">TOTAL CONSOLIDADO EM ABERTO (SALDO DEVEDOR)</td>
                    <td class="text-right font-tabular">R$ <?php echo number_format($totalDevedor, 2, ',', '.'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
