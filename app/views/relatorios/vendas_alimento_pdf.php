<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial de Emissão de Vendas da Cantina por Alimento (A4)
 * 
 * Conforme docs/alterações sistema guideline/relatorio de vendas por período.pdf,
 * Filtros de vendas.pdf e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

if (!isset($baseUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
}

$dados = $dadosVendas ?? ($dados ?? []);
$periodoOficial = $dados['periodo_oficial'] ?? ($dados['periodo'] ?? (date('d/m/Y') . ' - ' . date('d/m/Y')));
$dias = $dados['dias'] ?? [];
$totais = $dados['totais'] ?? [];
$filtros = $dados['filtros'] ?? [];
$alunos = $dados['alunos'] ?? [];

$emitirObs    = ($filtros['emitir_observacao'] ?? 'nao') === 'sim';
$emitirForma  = ($filtros['emitir_forma_pagamento'] ?? 'nao') === 'sim';
$emitirHora   = ($filtros['emitir_horario'] ?? 'nao') === 'sim';
$agruparTipo  = ($filtros['agrupar_tipo_pessoa'] ?? 'nao') === 'sim';
$agruparPer   = ($filtros['agrupar_periodo'] ?? 'sim') === 'sim';
$emitirAluno  = ($filtros['emitir_vendas_aluno'] ?? 'nao') === 'sim';

$operador = $dados['operador'] ?? 'Administração';
$emissao = date('d/m/Y H:i');

$qtdTotal = (int)($totais['quantidade_total'] ?? 0);
$fatTotal = (float)($totais['faturamento_total'] ?? 0.0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Emissão de Vendas da Cantina por Alimento — Colégio Monteiro Lobato</title>
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

        /* Barra de Ações para Navegador (Oculta na Impressão e no PDF) */
        .no-print {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            border-radius: 6px;
        }
        .no-print button, .no-print a {
            cursor: pointer;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-print-action {
            background: #1d4ed8;
            color: #ffffff !important;
            border: 1px solid #1e40af;
        }
        .btn-print-action:hover {
            background: #1e40af;
        }
        .btn-back-action {
            background: #ffffff;
            color: #1d4ed8 !important;
            border: 1px solid #cbd5e1;
            margin-right: 6px;
        }
        .btn-back-action:hover {
            background: #f1f5f9;
        }
        .btn-close-action {
            background: #ffffff;
            color: #475569 !important;
            border: 1px solid #cbd5e1;
            margin-left: 6px;
        }
        .btn-close-action:hover {
            background: #f1f5f9;
        }

        /* Visualização de Folha A4 em Tela */
        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 24px 0 40px 0;
            }
            .no-print {
                width: 200mm;
                max-width: calc(100% - 32px);
                margin: 0 auto 16px auto;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            }
            .container {
                width: 200mm;
                max-width: calc(100% - 32px);
                min-height: 280mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 12mm 15mm 15mm 15mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.08);
                border-radius: 2px;
            }
        }

        /* Impressão Direta (Física / Browser Print / PDF) */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8pt;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }

        /* Cabeçalho Oficial Escolar */
        .school-header {
            margin-bottom: 8px;
            line-height: 1.25;
        }
        .school-name {
            font-size: 10.5pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .report-emission-date {
            font-size: 8pt;
            color: #000000;
            margin-top: 1px;
            margin-bottom: 4px;
        }
        .report-title-main {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000000;
            margin-top: 3px;
            margin-bottom: 2px;
        }
        .report-period-badge {
            font-size: 8.5pt;
            font-weight: bold;
            color: #000000;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Tabela de Vendas Oficial */
        table.tabela-relatorio {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        table.tabela-relatorio th {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            padding: 3px 4px;
            font-weight: bold;
            text-align: left;
            color: #000000;
            font-size: 8pt;
        }
        table.tabela-relatorio td {
            padding: 2.5px 4px;
            vertical-align: middle;
            border-bottom: 1px dotted #e2e8f0;
            color: #000000;
        }
        table.tabela-relatorio tr:last-child td {
            border-bottom: none;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }

        /* Linhas de Totalização Oficial */
        .row-total-dia td {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            font-weight: bold;
            padding: 3.5px 4px;
            color: #000000;
        }
        .row-total-periodo td {
            border-top: 1px solid #000000;
            border-bottom: 2px solid #000000;
            font-weight: bold;
            padding: 4px 4px;
            color: #000000;
            font-size: 8.5pt;
        }

        /* Rodapé Institucional do Colégio */
        .footer-relatorio {
            margin-top: 16px;
            padding-top: 4px;
            border-top: 1px solid #000000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5pt;
            color: #000000;
        }
        .footer-relatorio .brand {
            font-weight: normal;
        }
        .footer-relatorio .brand strong {
            font-weight: bold;
        }
        .footer-relatorio .page-num {
            font-weight: normal;
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Barra de Controle de Impressão (Apenas na Visualização em Tela) -->
    <div class="no-print">
        <div>
            <strong>Relatório de Emissão de Vendas</strong> &bull; Período: <?php echo htmlspecialchars($periodoOficial); ?>
        </div>
        <div>
            <a href="<?php echo htmlspecialchars($baseUrl); ?>/relatorios/vendas-filtros" class="btn-back-action">
                &larr; Voltar aos Filtros
            </a>
            <button type="button" class="btn-print-action" onclick="window.print()">
                Imprimir Documento
            </button>
            <button type="button" class="btn-close-action" onclick="window.close()">
                Fechar Janela
            </button>
        </div>
    </div>

    <!-- Cabeçalho Oficial do Colégio Monteiro Lobato -->
    <div class="school-header">
        <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
        <div class="report-emission-date font-tabular"><?php echo $emissao; ?></div>
        <div class="report-title-main">Relatório de Emissão de Vendas da Cantina por Alimento</div>
        <div class="report-period-badge font-tabular">PERÍODO ENTRE DATAS : <?php echo htmlspecialchars($periodoOficial); ?></div>
    </div>

    <!-- Tabela Principal de Vendas -->
    <table class="tabela-relatorio">
        <thead>
            <tr>
                <th style="width: 14%;">Data</th>
                <?php if ($emitirHora): ?>
                    <th style="width: 10%;" class="text-center">Horário</th>
                <?php endif; ?>
                <?php if ($agruparTipo): ?>
                    <th style="width: 14%;">Tipo Pessoa</th>
                <?php endif; ?>
                <th style="<?php echo ($emitirHora || $agruparTipo || $emitirForma || $emitirObs) ? 'width: 32%;' : 'width: 44%;'; ?>">Alimento</th>
                <th style="width: 12%;" class="text-right">Quantidade</th>
                <th style="width: 15%;" class="text-right">Valor Unitário</th>
                <th style="width: 15%;" class="text-right">Valor Total</th>
                <?php if ($emitirForma): ?>
                    <th style="width: 14%;">Forma Pagto</th>
                <?php endif; ?>
                <?php if ($emitirObs): ?>
                    <th style="width: 16%;">Observação</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dias)): ?>
                <tr>
                    <td colspan="<?php echo 5 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0) + ($emitirForma ? 1 : 0) + ($emitirObs ? 1 : 0); ?>" class="text-center" style="padding: 18px; color: #64748b;">
                        Não há registros de vendas no período e filtros informados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($dias as $dataKey => $dia): ?>
                    <!-- Alimentos do Dia -->
                    <?php foreach ($dia['alimentos'] as $item): ?>
                        <tr>
                            <td class="font-tabular"><?php echo htmlspecialchars($item['data_fmt'] ?? $dia['data_fmt']); ?></td>
                            <?php if ($emitirHora): ?>
                                <td class="text-center font-tabular"><?php echo htmlspecialchars($item['hora_venda'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <?php if ($agruparTipo): ?>
                                <td><?php echo htmlspecialchars($item['tipo_pessoa'] ?? 'Aluno'); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($item['descricao'] ?? ($item['produto_nome'] ?? '-')); ?></td>
                            <td class="text-right font-tabular"><?php echo (int)($item['quantidade'] ?? 0); ?></td>
                            <td class="text-right font-tabular"><?php echo number_format((float)($item['preco_unitario'] ?? 0.0), 2, ',', ''); ?></td>
                            <td class="text-right font-tabular"><?php echo number_format((float)($item['subtotal'] ?? ($item['valor_total'] ?? 0.0)), 2, ',', ''); ?></td>
                            <?php if ($emitirForma): ?>
                                <td><?php echo htmlspecialchars($item['forma_pagamento'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <?php if ($emitirObs): ?>
                                <td><?php echo htmlspecialchars($item['observacao'] ?? '-'); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>

                    <!-- Total do Dia Conforme o Espelho Oficial -->
                    <?php if ($agruparPer): ?>
                        <tr class="row-total-dia">
                            <td colspan="<?php echo 2 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0); ?>">Total do dia :</td>
                            <td class="text-right font-tabular"><?php echo (int)($dia['qtd_dia'] ?? ($dia['quantidade_total'] ?? 0)); ?></td>
                            <td class="text-right font-tabular"></td>
                            <td class="text-right font-tabular"><?php echo number_format((float)($dia['total_dia'] ?? ($dia['subtotal'] ?? 0.0)), 2, ',', ''); ?></td>
                            <?php if ($emitirForma): ?><td></td><?php endif; ?>
                            <?php if ($emitirObs): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endif; ?>

                <?php endforeach; ?>

                <!-- Total do Período Conforme o Espelho Oficial -->
                <tr class="row-total-periodo">
                    <td colspan="<?php echo 2 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0); ?>">Total do período :</td>
                    <td class="text-right font-tabular"><?php echo $qtdTotal; ?></td>
                    <td class="text-right font-tabular"></td>
                    <td class="text-right font-tabular"><?php echo number_format($fatTotal, 2, ',', ''); ?></td>
                    <?php if ($emitirForma): ?><td></td><?php endif; ?>
                    <?php if ($emitirObs): ?><td></td><?php endif; ?>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Demonstrativo por Aluno (Se Solicitado nos Filtros) -->
    <?php if ($emitirAluno && !empty($alunos)): ?>
        <div style="margin-top: 14px; margin-bottom: 4px; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; border-bottom: 1px solid #000;">
            Demonstrativo de Consumo por Aluno
        </div>
        <table class="tabela-relatorio">
            <thead>
                <tr>
                    <th style="width: 14%;">Matrícula</th>
                    <th style="width: 46%;">Nome do Aluno</th>
                    <th style="width: 20%;">Série / Turma</th>
                    <th style="width: 10%;" class="text-right">Qtd</th>
                    <th style="width: 10%;" class="text-right">Total (R$)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $al): ?>
                    <tr>
                        <td class="font-tabular"><?php echo htmlspecialchars($al['matricula']); ?></td>
                        <td><?php echo htmlspecialchars($al['nome']); ?></td>
                        <td><?php echo htmlspecialchars($al['serie'] . ' ' . $al['turma']); ?></td>
                        <td class="text-right font-tabular"><?php echo (int)$al['quantidade']; ?></td>
                        <td class="text-right font-tabular"><?php echo number_format((float)$al['total_consumo'], 2, ',', ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Rodapé Institucional com Identificação Oficial -->
    <div class="footer-relatorio">
        <div class="brand">
            <strong>Colégio Monteiro Lobato</strong> &bull; Sistema Cantina Web
        </div>
        <div class="page-num font-tabular">
            1/1
        </div>
    </div>

</div>
</body>
</html>
