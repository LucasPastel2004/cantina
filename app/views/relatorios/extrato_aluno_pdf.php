<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Extrato Analítico de Consumo e Recargas do Aluno (A4)
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.5) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dadosExtrato ?? ($dados ?? []);
$aluno = $dados['aluno'] ?? [];
$periodo = $dados['periodo'] ?? (date('01/m/Y') . ' a ' . date('d/m/Y'));
$totais = $dados['totais'] ?? [];
$linhas = $dados['linhas'] ?? ($dados['movimentacoes'] ?? []);
$emissao = date('d/m/Y H:i:s');
$operador = $dados['operador'] ?? 'Administração Geral';

$saldoAtual = (float)($aluno['saldo_atual'] ?? ($aluno['saldo_carteira'] ?? 0.0));
$totalRecargas = (float)($totais['total_recargas'] ?? 0.0);
$totalGasto = (float)($totais['total_consumo'] ?? ($totais['total_gasto'] ?? 0.0));

$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato de Consumo — <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></title>
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
        .student-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .student-name { font-size: 13px; font-weight: 700; color: #0f172a; }
        .student-meta { font-size: 10px; color: #475569; margin-top: 2px; }
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
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
        <span><strong>Extrato do Aluno</strong> — <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></span>
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
                <div class="report-title">Extrato Analítico de Consumo e Recargas do Aluno</div>
                <div class="report-sub">Período: <?php echo htmlspecialchars($periodo); ?> &bull; Emissão: <?php echo $emissao; ?> &bull; Operador: <?php echo htmlspecialchars($operador); ?></div>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 13px; font-weight: 700; color: #0f172a;">CARTEIRA FAMILIAR</div>
            <div style="font-size: 9px; color: #1d4ed8; font-weight: 600; text-transform: uppercase;">Saldo Integrado</div>
        </div>
    </div>

    <div class="student-card">
        <div>
            <div class="student-name"><?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></div>
            <div class="student-meta">
                <strong>Matrícula:</strong> <?php echo htmlspecialchars($aluno['matricula'] ?? '-'); ?> &bull;
                <strong>Turma:</strong> <?php echo htmlspecialchars($aluno['nome_turma'] ?? ($aluno['turma'] ?? '-')); ?> &bull;
                <strong>Responsável:</strong> <?php echo htmlspecialchars($aluno['responsavel_nome'] ?? '-'); ?>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 9px; color: #64748b; text-transform: uppercase;">Saldo Atual da Carteira</div>
            <div style="font-size: 14px; font-weight: 700; color: #1d4ed8;" class="font-tabular">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></div>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-item">
            <div class="stat-label">Total Recarregado no Período</div>
            <div class="stat-value font-tabular" style="color: #16a34a;">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Total Consumido no Período</div>
            <div class="stat-value font-tabular" style="color: #b91c1c;">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-label">Saldo Disponível Hoje</div>
            <div class="stat-value font-tabular" style="color: #0f172a;">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Data/Hora</th>
                <th style="width: 10%;">Tipo</th>
                <th style="width: 44%;">Descrição / Alimento</th>
                <th style="width: 10%; text-align: center;">Qtd</th>
                <th style="width: 11%; text-align: right;">Vlr Unit</th>
                <th style="width: 11%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($linhas)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #64748b; padding: 12px;">Não há lançamentos de consumo ou recarga para o período selecionado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($linhas as $l): ?>
                    <tr>
                        <td class="font-tabular"><?php echo date('d/m/Y H:i', strtotime($l['data'] ?? ($l['created_at'] ?? 'now'))); ?></td>
                        <td>
                            <?php if (($l['tipo'] ?? '') === 'recarga' || ($l['tipo_transacao'] ?? '') === 'recarga_pix' || ($l['tipo_transacao'] ?? '') === 'recarga_dinheiro'): ?>
                                <span style="color: #16a34a; font-weight: bold;">Recarga</span>
                            <?php else: ?>
                                <span style="color: #475569;">Consumo</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($l['descricao'] ?? ($l['produto_nome'] ?? '-')); ?></strong></td>
                        <td class="text-center font-tabular"><?php echo (int)($l['quantidade'] ?? 1); ?> un</td>
                        <td class="text-right font-tabular">R$ <?php echo number_format((float)($l['valor_unitario'] ?? ($l['preco_unitario'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="text-right font-tabular">R$ <?php echo number_format((float)($l['valor_total'] ?? ($l['subtotal'] ?? 0)), 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
