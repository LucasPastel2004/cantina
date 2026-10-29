<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Extrato Oficial de Consumo do Aluno (A4)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrão oficial de relatórios A4
 * Padrão Excel Corporativo com divisórias pretas e fundo branco puro, banners azul marinho (#0f172a)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dados = $dadosExtrato ?? ($dados['dadosExtrato'] ?? ($dados ?? []));
if (!is_array($dados)) {
    $dados = get_defined_vars();
}

$aluno = $dados['aluno'] ?? ($aluno ?? []);
$periodo = $dados['periodo'] ?? ($periodo ?? (date('01/m/Y') . ' - ' . date('d/m/Y')));
$dataInicio = $dados['data_inicio'] ?? ($dataInicio ?? date('Y-m-01'));
$dataFim = $dados['data_fim'] ?? ($dataFim ?? date('Y-m-d'));
$apenasSaldo = !empty($dados['apenas_saldo']) || !empty($apenasSaldo);
$saldoApenasPeriodo = !empty($dados['saldo_apenas_periodo']) || !empty($saldoApenasPeriodo);

$saldoInicial = (float)($dados['saldo_inicial'] ?? ($dados['saldoInicial'] ?? ($saldoInicial ?? 0.0)));
$saldoAtual = (float)($dados['saldo_atual'] ?? ($dados['saldoAtual'] ?? ($saldoAtual ?? ($aluno['saldo_atual'] ?? ($aluno['responsavel_saldo'] ?? 0.0)))));
$totalRecargas = (float)($dados['total_recargas'] ?? ($dados['totalRecargas'] ?? ($totalRecargas ?? 0.0)));
$totalGasto = (float)($dados['total_gasto'] ?? ($dados['totalGasto'] ?? ($totalGasto ?? ($dados['total_consumo'] ?? 0.0))));
$totalLanches = (int)($dados['total_lanches'] ?? ($dados['totalLanches'] ?? ($totalLanches ?? 0)));

$linhas = $dados['linhas'] ?? ($dados['movimentacoes'] ?? ($dados['lancamentos'] ?? ($lancamentos ?? ($linhas ?? []))));
$emissao = date('d/m/Y H:i');
$operador = $dados['operador'] ?? ($operador ?? 'Atendimento Cantina');
$isPdf = $isPdf ?? false;

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
$periodoOficial = date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim));

$turmaNome = trim(($aluno['serie'] ?? '') . ' ' . ($aluno['nome_turma'] ?? ($aluno['turma_nome'] ?? '')));
$turnoNome = ucfirst($aluno['turma_turno'] ?? ($aluno['turno'] ?? ''));
$turmaDesc = $turmaNome . (!empty($turnoNome) ? ' (' . $turnoNome . ')' : '');
if (empty(trim($turmaDesc))) {
    $turmaDesc = 'Turma não informada';
}
$inicialAluno = strtoupper(mb_substr($aluno['nome'] ?? 'A', 0, 1, 'UTF-8'));
$responsavelNome = $aluno['responsavel_nome'] ?? 'Não informado';
$responsavelWhatsapp = $aluno['responsavel_whatsapp'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Extrato de Consumo — <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
            font-weight: 700;
            line-height: 1.35;
            background: #ffffff;
            width: 100%;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }

        /* Barra de Ações para Navegador (Oculta na Impressão e no PDF) */
        .no-print {
            background-color: #1e293b;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-size: 11px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .no-print button, .no-print a {
            cursor: pointer;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease-in-out;
        }
        .btn-print-action {
            background: #1d4ed8;
            color: #ffffff !important;
            border: 1px solid #1e40af;
        }
        .btn-print-action:hover {
            background: #1e40af;
        }
        .btn-close-action {
            background: #ffffff;
            color: #0f172a !important;
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
                width: 210mm;
                max-width: calc(100% - 32px);
                margin: 0 auto 16px auto;
                position: sticky;
                top: 10px;
                z-index: 1000;
            }
            .no-print .zoom-controls {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                padding: 2px 6px;
                margin-right: 8px;
            }
            .no-print .btn-zoom {
                background: transparent;
                color: #ffffff;
                border: none;
                width: 22px;
                height: 22px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                border-radius: 3px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                transition: all 0.15s ease;
            }
            .no-print .btn-zoom:hover {
                background: rgba(255, 255, 255, 0.25);
                color: #93c5fd;
            }
            .no-print .btn-zoom-val {
                background: transparent;
                color: #ffffff;
                border: none;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                padding: 2px 5px;
                border-radius: 3px;
                min-width: 40px;
                text-align: center;
                font-variant-numeric: tabular-nums;
                transition: all 0.15s ease;
            }
            .no-print .btn-zoom-val:hover {
                background: rgba(255, 255, 255, 0.25);
                text-decoration: underline;
            }
            .document-zoom-area {
                zoom: var(--doc-zoom, 1);
                transform-origin: top center;
                transition: zoom 0.1s ease-out;
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
                border-radius: 2px;
                position: relative;
            }
        }

        /* Impressão Direta (Física / Browser Print to PDF) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm 14mm 14mm;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            html, body {
                width: 100% !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8pt;
                font-weight: 700 !important;
                color: #000000 !important;
            }
            body, table, th, td, div, span, p {
                font-weight: 700 !important;
                color: #000000 !important;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }

            /* Quebras de páginas limpas em tabelas oficiais */
            table.table-doc-excel {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
                margin-top: 4px !important;
                margin-bottom: 8px !important;
                border: 1px solid #000000 !important;
                border-bottom: none !important;
                border-right: none !important;
            }
            table.table-doc-excel thead {
                display: table-header-group !important;
            }
            table.table-doc-excel thead tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.table-doc-excel tfoot {
                display: table-footer-group !important;
            }
            table.table-doc-excel tbody tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.table-doc-excel th,
            table.table-doc-excel td {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border-right: 1px solid #000000 !important;
                border-bottom: 1px solid #000000 !important;
            }
            .school-header,
            .student-banner,
            .param-bar,
            .footer-assinaturas-box {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .school-header, .student-banner, .param-bar, .footer-assinaturas-box { page-break-inside: avoid; break-inside: avoid; }

        /* Cabeçalho Oficial Escolar */
        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .school-logo-col {
            width: 52px;
            margin-right: 12px;
        }
        .school-logo-col img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #cbd5e1;
        }
        .school-title-col {
            flex: 1;
            line-height: 1.25;
        }
        .school-name {
            font-size: 10.5pt;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
        }
        .report-title-main {
            font-size: 9.5pt;
            font-weight: 700;
            color: #000000;
            margin-top: 2px;
        }
        .school-meta-col {
            text-align: right;
            font-size: 7.5pt;
            color: #334155;
            line-height: 1.3;
            padding-right: 2px;
        }

        /* Banner de Identificação do Aluno */
        .student-banner {
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 6px 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .student-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .code-tag {
            display: inline-block;
            background: #ffffff;
            border: 1px solid #000000;
            border-radius: 2px;
            padding: 1px 5px;
            font-size: 7pt;
            font-weight: 700;
            color: #000000;
            margin-left: 4px;
        }

        /* Faixa de Parâmetros e Indicadores */
        .param-bar {
            font-size: 8pt;
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 4px 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        /* Tabela Estilo Excel Oficial */
        table.table-doc-excel {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-top: 4px;
            margin-bottom: 8px;
            font-size: 7.8pt;
            font-weight: 700 !important;
            border: 1px solid #000000 !important;
        }
        table.table-doc-excel th,
        table.table-doc-excel td {
            border: 1px solid #000000 !important;
            padding: 3px 4px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: 700 !important;
            word-break: break-word;
        }
        table.table-doc-excel thead th {
            font-weight: 700 !important;
            text-align: center !important;
            font-size: 7.2pt;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            background-color: #f8fafc !important;
            color: #000000 !important;
        }
        table.table-doc-excel tfoot td {
            font-weight: 700 !important;
            background-color: #f8fafc !important;
            color: #000000 !important;
            border-top: 2px solid #000000 !important;
        }

        .text-start { text-align: left !important; }
        .text-center { text-align: center !important; }
        .text-end { text-align: center !important; }

        /* Banners de Seção */
        .section-banner {
            font-weight: 700 !important;
            font-size: 8pt;
            text-transform: uppercase;
            background: #0f172a !important;
            color: #ffffff !important;
            padding: 3.5px 8px;
            margin-top: 8px;
            margin-bottom: 2px;
            border-radius: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-banner * {
            color: #ffffff !important;
            font-weight: 700 !important;
        }

        /* Assinaturas */
        .footer-assinaturas-box {
            margin-top: 32px;
            padding-top: 4px;
            border-top: none;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 44%;
            border-top: 1.5px solid #000000;
            padding-top: 4px;
            text-align: center;
            font-size: 7.8pt;
            font-weight: 700 !important;
            color: #000000;
            line-height: 1.25;
        }
        .doc-footer-meta {
            margin-top: 10px;
            font-size: 7pt;
            font-weight: 700;
            color: #000000;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #000000;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;">
            Extrato de Consumo &bull; <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?>
        </div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">
            Período: <?php echo htmlspecialchars($periodoOficial); ?> &bull; Saldo: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
        <!-- Controles de Zoom para Conferência -->
        <div class="zoom-controls" title="Ajuste o zoom para conferir detalhes e números antes de imprimir ou salvar">
            <span style="display: inline-flex; align-items: center; color: #cbd5e1; margin-right: 3px;" title="Zoom">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </span>
            <button type="button" class="btn-zoom" id="btnZoomOut" title="Diminuir Zoom (Ctrl -)" onclick="alterarZoom(-0.15)">−</button>
            <button type="button" class="btn-zoom-val" id="btnZoomReset" title="Clique para redefinir para 100% (Ctrl 0)" onclick="resetarZoom()">100%</button>
            <button type="button" class="btn-zoom" id="btnZoomIn" title="Aumentar Zoom (Ctrl +)" onclick="alterarZoom(0.15)">+</button>
        </div>

        <button type="button" class="btn-print-action" onclick="window.print()" title="Imprimir documento ou Salvar como PDF pelo navegador">
            🖨 Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-close-action" onclick="fecharJanela()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="container">

    <!-- Cabeçalho Oficial Escolar -->
    <div class="school-header">
        <?php if (!empty($logoDataUri)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoDataUri; ?>" alt="Logotipo Oficial">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="report-title-main">DEMONSTRATIVO DE EXTRATO ANALÍTICO DE CONSUMO & MOVIMENTAÇÕES <!-- Extrato Analítico de Consumo --></div>
            <div style="font-size: 8pt; color: #334155;">Sistema de Gestão Cantina Web • Módulo Financeiro & Carteira Familiar</div>
        </div>
        <div class="school-meta-col font-tabular">
            <div><strong>Emissão:</strong> <?php echo $emissao; ?></div>
            <div><strong>Operador:</strong> <?php echo htmlspecialchars($operador, ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Período:</strong> <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <!-- 1. Banner de Identificação do Aluno -->
    <div class="student-banner">
        <div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span class="student-name" title="Aluno(a)"><span style="display:none;">Aluno(a): </span><?php echo htmlspecialchars(mb_strtoupper($aluno['nome'] ?? 'ALUNO NÃO IDENTIFICADO', 'UTF-8')); ?></span>
                <span class="code-tag font-tabular">Matrícula: <?php echo htmlspecialchars($aluno['matricula'] ?? '-'); ?></span>
                <span class="code-tag font-tabular" title="Série / Turma"><span style="display:none;">Série / Turma: </span><?php echo htmlspecialchars($turmaDesc); ?></span>
            </div>
            <div style="font-size: 7.5pt; color: #334155; margin-top: 2px;">
                Responsável: <strong><?php echo htmlspecialchars($responsavelNome); ?></strong>
                <?php if (!empty($responsavelWhatsapp)): ?>
                    &bull; Contato: <span class="font-tabular"><?php echo htmlspecialchars($responsavelWhatsapp); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align: right;" class="font-tabular">
            <div style="font-size: 7pt; font-weight: 700; color: #475569; text-transform: uppercase;">Saldo Atual na Carteira <!-- Saldo Atual: --></div>
            <div style="font-size: 11pt; font-weight: 800; color: #000000;">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></div>
        </div>
    </div>

    <!-- 2. Faixa de Parâmetros e Indicadores Sintéticos -->
    <div class="param-bar font-tabular">
        <div>
            <strong>Saldo Inicial:</strong> R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?> &bull; 
            <strong>Total Recarregado:</strong> R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?> &bull; 
            <strong>Total Consumido:</strong> R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?> (<?php echo $totalLanches; ?> lanche(s))
        </div>
        <div>
            <strong>Saldo Disponível:</strong> R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
        </div>
    </div>

    <?php if ($apenasSaldo): ?>
        <!-- Modo Sintético: Declaração de Saldo -->
        <div class="section-banner">
            <span>DECLARAÇÃO SINTÉTICA DE SALDO E CONSUMO</span>
            <span class="font-tabular">Saldo Atual: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></span>
        </div>
        <div style="border: 1px solid #000000; padding: 12px; margin-top: 4px; font-size: 8.5pt; line-height: 1.5;">
            <p style="margin: 0;">
                Certificamos para os devidos fins que a conta da carteira familiar vinculada ao(à) aluno(a) <strong><?php echo htmlspecialchars($aluno['nome'] ?? ''); ?></strong> (Matrícula: <strong><?php echo htmlspecialchars($aluno['matricula'] ?? ''); ?></strong>) registrou no período de <strong><?php echo htmlspecialchars($periodoOficial); ?></strong> um consumo total de <strong class="font-tabular">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></strong> (distribuído em <?php echo $totalLanches; ?> lanches e itens de cantina), totalizando <strong class="font-tabular">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></strong> em recargas e créditos aportados, resultando no saldo atual de <strong class="font-tabular">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></strong>.
            </p>
        </div>
    <?php else: ?>
        <!-- Modo Analítico Completo -->
        <div class="section-banner">
            <span>HISTÓRICO ANALÍTICO DE MOVIMENTAÇÕES & CONSUMO NO PERÍODO</span>
            <span class="font-tabular"><?php echo count($linhas); ?> lançamento(s)</span>
        </div>

        <table class="table-doc-excel">
            <thead>
                <tr>
                    <th style="width: 10%;" class="text-center">Data</th>
                    <th style="width: 33%;" class="text-start">Descrição do Item / Operação</th>
                    <th style="width: 6.5%;" class="text-center">Qtd.</th>
                    <th style="width: 12%;" class="text-center">V. Unitário</th>
                    <th style="width: 11.5%;" class="text-center">V. Total</th>
                    <th style="width: 12%;" class="text-center">Forma Pgto.</th>
                    <th style="width: 15%;" class="text-center">Saldo Result.</th>
                </tr>
            </thead>
            <tbody>
                <!-- Linha Oficial de Saldo Inicial -->
                <tr>
                    <td class="text-center font-tabular">-</td>
                    <td class="text-start"><strong>SALDO INICIAL DO PERÍODO</strong> <!-- Saldo inicial --></td>
                    <td class="text-center font-tabular">-</td>
                    <td class="text-center font-tabular">-</td>
                    <td class="text-center font-tabular">-</td>
                    <td class="text-center font-tabular">-</td>
                    <td class="text-center font-tabular"><strong>R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?></strong></td>
                </tr>

                <?php if (empty($linhas)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 12px; color: #000000;">
                            Nenhum lançamento de consumo ou recarga registrado para este aluno no período especificado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($linhas as $l): 
                        $isRecarga = ($l['tipo'] === 'recarga');
                        $valUnit = is_numeric($l['valor_unitario'] ?? null) ? number_format((float)$l['valor_unitario'], 2, ',', '.') : ($l['valor_unitario'] ?? '-');
                        $valTot = (float)($l['valor_total'] ?? 0.0);
                        $saldoLinha = (float)($l['saldo'] ?? 0.0);
                        $dataLinha = htmlspecialchars($l['data_formatada'] ?? (!empty($l['data']) ? date('d/m/Y', strtotime($l['data'])) : '-'), ENT_QUOTES, 'UTF-8');
                    ?>
                        <tr>
                            <td class="text-center font-tabular"><?php echo $dataLinha; ?></td>
                            <td class="text-start">
                                <strong><?php echo htmlspecialchars($l['descricao'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <td class="text-center font-tabular">
                                <?php echo htmlspecialchars((string)($l['quantidade'] ?? '1'), ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-center font-tabular">
                                <?php echo is_numeric($l['valor_unitario'] ?? null) ? 'R$ ' . $valUnit : $valUnit; ?>
                            </td>
                            <td class="text-center font-tabular">
                                <strong>R$ <?php echo number_format($valTot, 2, ',', '.'); ?></strong>
                            </td>
                            <td class="text-center font-tabular">
                                <?php echo htmlspecialchars($l['forma_pagamento'] ?? 'Saldo', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-center font-tabular">
                                <strong>R$ <?php echo number_format($saldoLinha, 2, ',', '.'); ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="row-geral-total">
                    <td colspan="4" class="text-end">TOTAIS DO PERÍODO: <!-- Total gasto no período --></td>
                    <td class="text-center font-tabular">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></td>
                    <td colspan="2" class="text-center font-tabular">
                        Saldo Atual: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <!-- Assinaturas e Rodapé -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong>Operador(a) da Cantina</strong><br>
            Conferência e Atendimento
        </div>
        <div class="signature-line">
            <strong>Responsável Financeiro</strong><br>
            Colégio Monteiro Lobato
        </div>
    </div>

    <div class="doc-footer-meta">
        <div>Cantina Web &bull; Sistema Integrado de Gestão Escolar &bull; Módulo da Família & Extrato</div>
        <div>Página 1 de 1 &bull; Documento Oficial de Acompanhamento Financeiro</div>
    </div>

</div>
</div>

<script>
    let currentZoom = 1.0;
    const minZoom = 0.5;
    const maxZoom = 2.0;

    function aplicarZoom(novoZoom) {
        currentZoom = Math.min(Math.max(novoZoom, minZoom), maxZoom);
        currentZoom = Math.round(currentZoom * 100) / 100;

        const zoomArea = document.getElementById('document-zoom-area');
        if (zoomArea) {
            zoomArea.style.setProperty('--doc-zoom', currentZoom);
            zoomArea.style.zoom = currentZoom;
        }

        const resetBtn = document.getElementById('btnZoomReset');
        if (resetBtn) {
            resetBtn.textContent = Math.round(currentZoom * 100) + '%';
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
                if (window.opener) {
                    window.close();
                } else if (window.history.length > 1) {
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
