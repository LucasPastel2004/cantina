<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório A4 Timbrado de Desconto em Folha para RH
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.4) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dadosFolha ?? ($dados ?? []);
$competencia = $dados['competencia'] ?? date('m/Y');
$colaboradores = $dados['colaboradores'] ?? [];
$totais = $dados['totais'] ?? [];
$operador = $dados['operador'] ?? 'Administração Geral';
$emissao = date('d/m/Y H:i:s');

$totalColabs = (int)($totais['total_colaboradores'] ?? count($colaboradores));
$totalLanches = (int)($totais['total_lanches'] ?? 0);
$totalDesconto = (float)($totais['valor_total'] ?? 0.0);

$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Desconto em Folha RH — Competência <?php echo htmlspecialchars($competencia); ?></title>
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
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum" 1, "zero" 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10.5px;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }
        th {
            background: #f1f5f9;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 6px 8px;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            text-align: left;
        }
        th:first-child,
        td:first-child {
            border-left: 1px solid #000000;
        }
        th:last-child,
        td:last-child {
            border-right: 1px solid #000000;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #000000; /* Linha preta entre os itens das listas */
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
            font-size: 11.5px;
        }
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
        <span><strong>Desconto em Folha RH</strong> — Competência <?php echo htmlspecialchars($competencia); ?></span>
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
                <div class="report-title">Relatório de Desconto em Folha de Pagamento — Recursos Humanos</div>
                <div class="report-sub">Competência: <?php echo htmlspecialchars($competencia); ?> &bull; Emissão: <?php echo $emissao; ?> &bull; Responsável: <?php echo htmlspecialchars($operador); ?></div>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 13px; font-weight: 700; color: #0f172a;">RH &bull; CANTINA</div>
            <div style="font-size: 9px; color: #1d4ed8; font-weight: 600; text-transform: uppercase;">Mês <?php echo htmlspecialchars($competencia); ?></div>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Colaboradores com Consumo</div>
            <div class="stat-value font-tabular"><?php echo $totalColabs; ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Total de Itens / Lanches</div>
            <div class="stat-value font-tabular"><?php echo $totalLanches; ?> un</div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Total a Descontar em Folha</div>
            <div class="stat-value font-tabular" style="color: #1d4ed8;">R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Matrícula</th>
                <th style="width: 40%;">Colaborador / Professor</th>
                <th style="width: 20%;">Cargo / Função</th>
                <th style="width: 10%; text-align: center;">Lanches</th>
                <th style="width: 15%; text-align: right;">Valor a Descontar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($colaboradores)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="color: #64748b; padding: 12px;">Não há registros de consumo sob desconto em folha para esta competência.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($colaboradores as $c): ?>
                    <tr>
                        <td class="cell-code"><?php echo htmlspecialchars($c['matricula_funcional'] ?? '-'); ?></td>
                        <td><strong><?php echo htmlspecialchars($c['nome'] ?? '-'); ?></strong></td>
                        <td style="color: #475569;"><?php echo htmlspecialchars($c['cargo'] ?? 'Colaborador'); ?></td>
                        <td class="text-center font-tabular"><?php echo (int)($c['total_lanches'] ?? 0); ?> un</td>
                        <td class="text-right font-tabular">R$ <?php echo number_format((float)($c['valor_total'] ?? 0), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="3">TOTAL GERAL PARA FOLHA DE PAGAMENTO</td>
                    <td class="text-center font-tabular"><?php echo $totalLanches; ?> un</td>
                    <td class="text-right font-tabular">R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="sign-box">
        <div class="sign-line">
            Gestão da Cantina Escolar
        </div>
        <div class="sign-line">
            Recursos Humanos / Financeiro do Colégio
        </div>
    </div>
</div>
</body>
</html>
