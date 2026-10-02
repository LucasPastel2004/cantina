<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Extrato Oficial de Consumo do Aluno (A4)
 * 
 * Conforme docs/alterações sistema guideline/Relatório Extrato Consumo Aluno.pdf,
 * filtro Extrato Consumo Aluno.pdf, FSD Área 6 e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

if (!isset($baseUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
}

$dados = $dadosExtrato ?? ($dados ?? []);
$aluno = $dados['aluno'] ?? ($aluno ?? []);
$periodo = $dados['periodo'] ?? ($periodo ?? (date('01/m/Y') . ' - ' . date('d/m/Y')));
$dataInicio = $dados['data_inicio'] ?? ($dataInicio ?? date('Y-m-01'));
$dataFim = $dados['data_fim'] ?? ($dataFim ?? date('Y-m-d'));
$apenasSaldo = !empty($dados['apenas_saldo']) || !empty($apenasSaldo);
$saldoApenasPeriodo = !empty($dados['saldo_apenas_periodo']) || !empty($saldoApenasPeriodo);

$saldoInicial = (float)($dados['saldo_inicial'] ?? ($saldoInicial ?? 0.0));
$saldoAtual = (float)($dados['saldo_atual'] ?? ($saldoAtual ?? ($aluno['saldo_atual'] ?? ($aluno['responsavel_saldo'] ?? 0.0))));
$totalRecargas = (float)($dados['total_recargas'] ?? ($totalRecargas ?? 0.0));
$totalGasto = (float)($dados['total_gasto'] ?? ($totalGasto ?? ($dados['total_consumo'] ?? 0.0)));
$totalLanches = (int)($dados['total_lanches'] ?? ($totalLanches ?? 0));

$linhas = $dados['linhas'] ?? ($dados['movimentacoes'] ?? ($dados['lancamentos'] ?? ($lancamentos ?? ($linhas ?? []))));
$emissao = date('d/m/Y H:i');
$operador = $dados['operador'] ?? ($operador ?? 'Atendimento Cantina');

$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
$periodoOficial = date('d/m/Y', strtotime($dataInicio)) . ' - ' . date('d/m/Y', strtotime($dataFim));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato de Consumo — <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
            @bottom-right {
                content: "1/1";
                font-family: Arial, sans-serif;
                font-size: 8pt;
                color: #64748b;
            }
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
            line-height: 1.35;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }
        
        /* Simulação de Folha Física A4 na Tela */
        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 20px 10px;
            }
            .top-actions-bar {
                max-width: 200mm;
                margin: 0 auto 14px auto;
                background: #ffffff;
                border: 1px solid #94a3b8;
                border-radius: 6px;
                padding: 10px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
            }
            .top-actions-bar .btn-action {
                font-family: inherit;
                font-size: 12px;
                font-weight: 600;
                padding: 6px 14px;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .btn-action-primary {
                background: #1d4ed8;
                color: #ffffff;
                border: 1px solid #1e40af;
            }
            .btn-action-primary:hover {
                background: #1e40af;
            }
            .btn-action-secondary {
                background: #ffffff;
                color: #1e293b;
                border: 1px solid #cbd5e1;
            }
            .btn-action-secondary:hover {
                background: #f8fafc;
            }
            .doc-page {
                width: 200mm;
                max-width: calc(100% - 24px);
                min-height: 285mm;
                background: #ffffff;
                padding: 10mm 12mm;
                margin: 0 auto;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                position: relative;
            }
        }

        @media print {
            .top-actions-bar {
                display: none !important;
            }
            .doc-page {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }

        /* Cabeçalho Oficial do Relatório Escolar */
        .report-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }
        .school-title {
            font-size: 13pt;
            font-weight: 800;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin: 0;
            line-height: 1.1;
        }
        .report-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1d4ed8;
            margin: 3px 0 0 0;
            line-height: 1.2;
        }
        .report-subtitle {
            font-size: 8.5pt;
            font-weight: 700;
            color: #334155;
            margin: 2px 0 0 0;
            text-transform: uppercase;
        }
        .header-meta {
            text-align: right;
            font-size: 8pt;
            color: #334155;
            line-height: 1.35;
        }

        /* Bloco de Identificação do Aluno (Fiel ao Relatório Oficial) */
        .student-info-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 1fr 2fr 1.5fr 1.2fr;
            gap: 8px 16px;
            align-items: center;
        }
        .info-group {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 7.5pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 9.5pt;
            font-weight: 700;
            color: #0f172a;
        }
        .info-value.balance {
            color: #1d4ed8;
            font-size: 11pt;
        }

        /* Tabela Oficial de Movimentações (Estética Excel com Laterais e Linhas Pretas entre Itens) */
        .table-extrato {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 14px;
            border-left: 1px solid #000000;
            border-right: 1px solid #000000;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            box-sizing: border-box;
        }
        .table-extrato thead th {
            background-color: #ffffff;
            color: #0f172a;
            font-weight: 700;
            font-size: 8pt;
            padding: 5px 6px;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            text-transform: none;
            letter-spacing: normal;
        }
        .table-extrato thead th:first-child,
        .table-extrato tbody td:first-child,
        .table-extrato tfoot td:first-child {
            border-left: 1px solid #000000 !important;
        }
        .table-extrato thead th:last-child,
        .table-extrato tbody td:last-child,
        .table-extrato tfoot td:last-child {
            border-right: 1px solid #000000 !important;
        }
        .table-extrato tbody tr {
            background-color: #ffffff !important;
        }
        .table-extrato tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #000000; /* Linha preta entre os itens das listas */
            vertical-align: middle;
            color: #0f172a;
            background-color: #ffffff !important;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .table-extrato tbody tr:nth-child(even) {
            background-color: #ffffff !important;
        }
        .table-extrato tbody tr.row-initial {
            background-color: #ffffff !important;
            font-weight: 700;
        }
        .table-extrato tbody tr.row-recarga {
            background-color: #ffffff !important;
        }
        .table-extrato tfoot td {
            padding: 6px;
            font-weight: 800;
            font-size: 9pt;
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            background-color: #ffffff !important;
        }

        .cell-left { text-align: left; }
        .cell-center { text-align: center; }
        .cell-right { text-align: right; }

        /* Resumo Sintético (quando apenas_saldo = sim) */
        .resumo-saldo-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .resumo-title {
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .resumo-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .resumo-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
        }
        .resumo-item-label {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .resumo-item-val {
            font-size: 12pt;
            font-weight: 800;
            color: #0f172a;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-around;
            padding-top: 20px;
        }
        .signature-box {
            text-align: center;
            width: 220px;
        }
        .signature-line {
            border-top: 1px solid #000000;
            margin-bottom: 4px;
        }
        .signature-label {
            font-size: 8pt;
            color: #475569;
        }

        /* Rodapé Oficial da Instituição */
        .report-footer {
            margin-top: 24px;
            padding-top: 6px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>

<!-- Barra Superior de Controle na Visualização de Tela -->
<div class="top-actions-bar no-print">
    <div style="font-size: 12px; font-weight: 600; color: #0f172a;">
        <span>Documento Oficial A4</span> &bull; 
        <span class="text-muted">Extrato de Consumo: <strong><?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?></strong></span>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo $baseUrl; ?>/relatorios/extrato-aluno?aluno_id=<?php echo (int)($aluno['id'] ?? 0); ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" class="btn-action btn-action-secondary">
            &larr; Voltar aos Filtros
        </a>
        <button onclick="window.print()" class="btn-action btn-action-primary">
            &#128438; Imprimir Documento
        </button>
        <button onclick="fecharJanela()" class="btn-action btn-action-secondary">
            &#10005; Fechar Janela
        </button>
    </div>
</div>

<!-- Folha Física A4 -->
<div class="doc-page">

    <!-- Cabeçalho Oficial -->
    <div class="report-header">
        <div class="header-brand">
            <?php if (!empty($logoDataUri)): ?>
                <img src="<?php echo $logoDataUri; ?>" alt="Colégio Monteiro Lobato" class="header-logo">
            <?php endif; ?>
            <div>
                <h1 class="school-title">COLÉGIO MONTEIRO LOBATO</h1>
                <div class="report-title">Extrato Analítico de Consumo do Aluno</div>
                <div class="report-subtitle">PERÍODO ENTRE DATAS: <?php echo htmlspecialchars($periodoOficial); ?></div>
            </div>
        </div>
        <div class="header-meta">
            <div><strong>Emissão:</strong> <?php echo $emissao; ?></div>
            <div><strong>Operador:</strong> <?php echo htmlspecialchars($operador); ?></div>
        </div>
    </div>

    <!-- Bloco de Identificação do Aluno (Conforme Relatório Extrato Consumo Aluno.pdf) -->
    <div class="student-info-box">
        <div class="info-group">
            <span class="info-label">Matrícula:</span>
            <span class="info-value font-tabular"><?php echo htmlspecialchars($aluno['matricula'] ?? '-'); ?></span>
        </div>
        <div class="info-group">
            <span class="info-label">Aluno(a):</span>
            <span class="info-value"><?php echo htmlspecialchars(mb_strtoupper($aluno['nome'] ?? 'ALUNO NÃO IDENTIFICADO', 'UTF-8')); ?></span>
        </div>
        <div class="info-group">
            <span class="info-label">Série / Turma:</span>
            <span class="info-value">
                <?php 
                    $turmaDesc = trim(($aluno['serie'] ?? ($aluno['turma_nome'] ?? '')) . ' ' . ($aluno['nome_turma'] ?? ''));
                    if (!empty($aluno['turma_turno'])) {
                        $turmaDesc .= ' - ' . strtoupper(substr($aluno['turma_turno'], 0, 3));
                    }
                    echo htmlspecialchars(!empty(trim($turmaDesc)) ? trim($turmaDesc) : '-'); 
                ?>
            </span>
        </div>
        <div class="info-group">
            <span class="info-label">Saldo Atual:</span>
            <span class="info-value balance font-tabular">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></span>
        </div>
    </div>

    <?php if ($apenasSaldo): ?>
        <!-- Modo Sintético: Emitir Apenas Saldo do Aluno -->
        <div class="resumo-saldo-card">
            <div class="resumo-title">Demonstrativo Sintético de Saldo e Consumo da Carteira Familiar</div>
            
            <div class="resumo-grid">
                <div class="resumo-item">
                    <div class="resumo-item-label">Saldo Inicial</div>
                    <div class="resumo-item-val font-tabular">R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?></div>
                </div>
                <div class="resumo-item">
                    <div class="resumo-item-label">Total Recarregado</div>
                    <div class="resumo-item-val font-tabular" style="color: #16a34a;">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></div>
                </div>
                <div class="resumo-item">
                    <div class="resumo-item-label">Total Consumido</div>
                    <div class="resumo-item-val font-tabular" style="color: #b91c1c;">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
                </div>
                <div class="resumo-item" style="border-left: 2px solid #1d4ed8;">
                    <div class="resumo-item-label">Saldo Atual</div>
                    <div class="resumo-item-val font-tabular" style="color: #1d4ed8;">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></div>
                </div>
            </div>

            <p style="font-size: 8.5pt; color: #475569; margin: 0; line-height: 1.4;">
                Certificamos para os devidos fins que a conta da carteira familiar vinculada ao aluno <strong><?php echo htmlspecialchars($aluno['nome'] ?? ''); ?></strong> (Matrícula: <strong><?php echo htmlspecialchars($aluno['matricula'] ?? ''); ?></strong>) registrou no período compreendido entre <strong><?php echo htmlspecialchars($periodoOficial); ?></strong> um consumo total de <strong class="font-tabular">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></strong> (distribuído em <?php echo $totalLanches; ?> lanches e produtos), totalizando <strong class="font-tabular">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></strong> em créditos aportados, resultando no saldo disponível de <strong class="font-tabular">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></strong>.
            </p>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Operador do Caixa / Atendente</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Gerência Cantina Monteiro Lobato</div>
            </div>
        </div>

    <?php else: ?>
        <!-- Modo Analítico Completo: Tabela Oficial com Saldo Progressivo Linha a Linha -->
        <table class="table-extrato">
            <thead>
                <tr>
                    <th class="cell-left" style="width: 12%;">Data</th>
                    <th class="cell-left" style="width: 33%;">Descrição</th>
                    <th class="cell-center" style="width: 6%;">Qtd.</th>
                    <th class="cell-right" style="width: 12%;">V. Unitário</th>
                    <th class="cell-right" style="width: 12%;">V. Total</th>
                    <th class="cell-center" style="width: 11%;">Forma Pgto.</th>
                    <th class="cell-right" style="width: 14%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <!-- Linha Oficial de Saldo Inicial -->
                <tr class="row-initial">
                    <td class="cell-left"></td>
                    <td class="cell-left">Saldo inicial</td>
                    <td class="cell-center"></td>
                    <td class="cell-right"></td>
                    <td class="cell-right"></td>
                    <td class="cell-center"></td>
                    <td class="cell-right font-tabular"><?php echo number_format($saldoInicial, 2, ',', '.'); ?></td>
                </tr>

                <?php if (empty($linhas)): ?>
                    <tr>
                        <td colspan="7" class="cell-center" style="padding: 16px; color: #64748b;">
                            Nenhum lançamento de consumo ou recarga registrado para este aluno no período especificado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): ?>
                        <?php 
                            $isRecarga = ($l['tipo'] === 'recarga');
                            $rowClass = $isRecarga ? 'row-recarga' : '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td class="cell-left font-tabular"><?php echo htmlspecialchars($l['data_formatada'] ?? date('d/m/Y', strtotime($l['data']))); ?></td>
                            <td class="cell-left">
                                <strong><?php echo htmlspecialchars($l['descricao'] ?? '-'); ?></strong>
                            </td>
                            <td class="cell-center font-tabular">
                                <?php echo htmlspecialchars((string)($l['quantidade'] ?? '1')); ?>
                            </td>
                            <td class="cell-right font-tabular">
                                <?php 
                                    if (is_numeric($l['valor_unitario'])) {
                                        echo number_format((float)$l['valor_unitario'], 2, ',', '.');
                                    } else {
                                        echo htmlspecialchars((string)$l['valor_unitario']);
                                    }
                                ?>
                            </td>
                            <td class="cell-right font-tabular">
                                <?php echo number_format((float)($l['valor_total'] ?? 0.0), 2, ',', '.'); ?>
                            </td>
                            <td class="cell-center font-tabular" style="font-size: 8pt;">
                                <?php echo htmlspecialchars($l['forma_pagamento'] ?? 'Saldo'); ?>
                            </td>
                            <td class="cell-right font-tabular" style="font-weight: 600;">
                                <?php echo number_format((float)($l['saldo'] ?? 0.0), 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="cell-left">Total gasto no período</td>
                    <td class="cell-right font-tabular"><?php echo number_format($totalGasto, 2, ',', '.'); ?></td>
                    <td colspan="2" class="cell-right font-tabular" style="color: #1d4ed8;">
                        Saldo Final: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Rodapé Oficial do Colégio Monteiro Lobato (Sem Menção a Terceiros) -->
    <div class="report-footer">
        <div>Colégio Monteiro Lobato &bull; Sistema Cantina Web</div>
        <div>Página 1/1</div>
    </div>

</div>

<script>
function fecharJanela() {
    window.close();
    setTimeout(function() {
        if (!window.closed) {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?php echo $baseUrl; ?>/relatorios/extrato-aluno';
            }
        }
    }, 150);
}
</script>

</body>
</html>
