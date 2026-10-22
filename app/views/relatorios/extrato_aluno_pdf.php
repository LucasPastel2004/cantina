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
            margin: 0;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Arial, Helvetica, sans-serif;
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
            line-height: 1.35;
            background: #ffffff;
            width: 100%;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }
        
        /* Simulação de Folha Física A4 na Tela */
        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 20px 10px 40px 10px;
            }
            .top-actions-bar, .no-print-bar {
                width: 200mm;
                max-width: calc(100% - 32px);
                margin: 0 auto 16px auto;
                background-color: #1e293b;
                color: #ffffff;
                border-radius: 6px;
                padding: 8px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                position: sticky;
                top: 10px;
                z-index: 1000;
            }
            .top-actions-bar .zoom-controls {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                padding: 2px 6px;
                flex-shrink: 0;
                margin: 0 4px;
            }
            .top-actions-bar .btn-zoom {
                background: transparent;
                color: #ffffff;
                border: none;
                width: 24px;
                height: 24px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                border-radius: 3px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                transition: all 0.15s ease;
            }
            .top-actions-bar .btn-zoom:hover {
                background: rgba(255, 255, 255, 0.2);
                color: #93c5fd;
            }
            .top-actions-bar .btn-zoom-val {
                background: transparent;
                color: #ffffff;
                border: none;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                padding: 2px 6px;
                border-radius: 3px;
                font-variant-numeric: tabular-nums;
                min-width: 44px;
                text-align: center;
                transition: all 0.15s ease;
            }
            .top-actions-bar .btn-zoom-val:hover {
                background: rgba(255, 255, 255, 0.2);
                text-decoration: underline;
            }
            .top-actions-bar .btn-action {
                font-family: inherit;
                font-size: 12px;
                font-weight: 600;
                padding: 5px 12px;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                border: none;
                transition: all 0.15s ease-in-out;
            }
            .btn-action-primary {
                background: #1d4ed8;
                color: #ffffff !important;
                border: 1px solid #1e40af !important;
            }
            .btn-action-primary:hover {
                background: #1e40af;
            }
            .btn-action-secondary {
                background: #ffffff;
                color: #0f172a !important;
            }
            .btn-action-secondary:hover {
                background: #f1f5f9;
                color: #1d4ed8 !important;
            }
            .doc-page {
                width: 200mm;
                max-width: calc(100% - 24px);
                min-height: 280mm;
                background: #ffffff;
                padding: 10mm 14mm;
                margin: 0 auto;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        .document-zoom-area {
            zoom: var(--doc-zoom, 1);
            transform-origin: top center;
            transition: zoom 0.1s ease-out;
        }

        @media print {
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .top-actions-bar, .no-print {
                display: none !important;
            }
            .doc-page {
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 auto !important;
                padding: 10mm 14mm !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead {
            display: table-header-group;
        }
        tfoot {
            display: table-footer-group;
        }
        tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .report-header, .student-banner, .stat-strip, .table-section-header, .resumo-saldo-card, .signature-section {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Cabeçalho Oficial do Relatório Escolar */
        .report-header {
            border-bottom: 2px solid #000000;
            padding-bottom: 6px;
            margin-bottom: 10px;
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
            font-size: 8pt;
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
            padding-right: 2px;
        }

        /* 1. Banner Completo de Identificação do Aluno (Fiel à Tela Web) */
        .student-banner {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .student-banner-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14pt;
            font-weight: 800;
            color: #1d4ed8;
            flex-shrink: 0;
        }
        .student-name-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 2px;
        }
        .student-name {
            font-size: 9.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .code-tag {
            display: inline-block;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 1px 6px;
            font-size: 7.5pt;
            font-weight: 600;
            color: #334155;
            line-height: 1.3;
        }
        .student-sub-row {
            font-size: 8pt;
            color: #64748b;
            line-height: 1.3;
        }
        .student-sub-row strong {
            color: #0f172a;
        }
        .student-banner-right {
            text-align: right;
            flex-shrink: 0;
            padding-left: 12px;
        }
        .wallet-label {
            font-size: 7pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 1px;
        }
        .wallet-val {
            font-size: 12pt;
            font-weight: 800;
            color: #1d4ed8;
            line-height: 1.1;
        }
        .wallet-sub {
            font-size: 7pt;
            color: #64748b;
            margin-top: 1px;
        }

        /* 2. Stat-Strip: 4 Cartões de Resumo do Período */
        .stat-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .stat-card-label {
            font-size: 7pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }
        .stat-card-val {
            font-size: 11pt;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
        }
        .stat-card-sub {
            font-size: 6.5pt;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.2;
        }

        /* 3. Cabeçalho da Seção Analítica de Movimentações */
        .table-section-header {
            background: #f8fafc;
            border: 1px solid #000000;
            border-bottom: none;
            padding: 5px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-section-title {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
        }
        .table-section-period {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
            line-height: 1.2;
        }
        .table-section-badge {
            font-size: 7.5pt;
            font-weight: 600;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 1px 6px;
            border-radius: 3px;
            color: #475569;
        }

        /* Tabela Oficial de Movimentações (Estética Excel com Linhas Pretas entre Itens) */
        .table-extrato {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            font-size: 8pt;
            margin-bottom: 12px;
            border: 1px solid #000000 !important;
            box-sizing: border-box !important;
        }
        .table-extrato thead th {
            background-color: #ffffff !important;
            color: #0f172a;
            font-weight: 700;
            font-size: 7.5pt;
            padding: 4px 6px;
            border: 1px solid #000000 !important;
            text-transform: uppercase;
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
            border: 1px solid #000000 !important;
            vertical-align: middle;
            color: #0f172a;
            background-color: #ffffff !important;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .table-extrato tbody tr.row-initial {
            background-color: #f8fafc !important;
            font-weight: 700;
        }
        .table-extrato tbody tr.row-recarga {
            background-color: #f0fdf4 !important;
        }
        .table-extrato tfoot td {
            padding: 5px 6px;
            font-weight: 800;
            font-size: 8.5pt;
            border: 1.5px solid #000000 !important;
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
            padding: 14px;
            margin-bottom: 14px;
        }
        .resumo-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .resumo-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 14px;
        }
        .resumo-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
        }
        .resumo-item-label {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
        }
        .resumo-item-val {
            font-size: 11pt;
            font-weight: 800;
            color: #0f172a;
        }

        .signature-section {
            margin-top: 36px;
            display: flex;
            justify-content: space-around;
            padding-top: 16px;
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
            margin-top: 20px;
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

<!-- Barra Superior de Controle Padronizada (#1e293b - Visível apenas na tela) -->
<div class="top-actions-bar no-print-bar no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
            Extrato de Consumo &bull; <?php echo htmlspecialchars($aluno['nome'] ?? 'Aluno'); ?>
        </div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">
            Total de Páginas: 1
        </div>
    </div>

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

    <div style="display: flex; gap: 8px;">
        <button type="button" onclick="window.print()" class="btn-action btn-action-primary">
            🖨 Imprimir Documento
        </button>
        <button type="button" onclick="fecharJanela()" class="btn-action btn-action-secondary">
            ✕ Fechar
        </button>
    </div>
</div>

<!-- ÁREA ESCALÁVEL COM ZOOM PARA CONFERÊNCIA -->
<div id="document-zoom-area" class="document-zoom-area">

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

    <!-- 1. Banner de Identificação do Aluno (Fiel à Tela Web) -->
    <div class="student-banner">
        <div class="student-banner-left">
            <div class="student-avatar font-tabular">
                <?php echo htmlspecialchars($inicialAluno, ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div>
                <div class="student-name-row">
                    <h2 class="student-name"><?php echo htmlspecialchars(mb_strtoupper($aluno['nome'] ?? 'ALUNO NÃO IDENTIFICADO', 'UTF-8')); ?></h2>
                    <span class="code-tag font-tabular">Matrícula: <?php echo htmlspecialchars($aluno['matricula'] ?? '-'); ?></span>
                    <span class="code-tag font-tabular"><?php echo htmlspecialchars($turmaDesc); ?></span>
                </div>
                <div class="student-sub-row">
                    Responsável: <strong><?php echo htmlspecialchars($responsavelNome); ?></strong>
                    <?php if (!empty($responsavelWhatsapp)): ?>
                        &bull; WhatsApp: <span class="font-tabular"><?php echo htmlspecialchars($responsavelWhatsapp); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="student-banner-right">
            <div class="wallet-label">Saldo Atual da Carteira</div>
            <div class="wallet-val font-tabular">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></div>
            <div class="wallet-sub">Saldo integrado familiar</div>
        </div>
    </div>

    <!-- 2. Stat-Strip: 4 Cartões de Resumo do Período -->
    <div class="stat-strip">
        <div class="stat-card">
            <div class="stat-card-label">Saldo Inicial</div>
            <div class="stat-card-val font-tabular">R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?></div>
            <div class="stat-card-sub font-tabular">
                <?php echo $saldoApenasPeriodo ? 'Período isolado (R$ 0,00)' : 'Anterior a ' . date('d/m/Y', strtotime($dataInicio)); ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-label">Total Recarregado</div>
            <div class="stat-card-val font-tabular" style="color: #16a34a;">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></div>
            <div class="stat-card-sub font-tabular">Créditos aportados no período</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-label">Total Consumido</div>
            <div class="stat-card-val font-tabular" style="color: #dc2626;">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></div>
            <div class="stat-card-sub font-tabular"><?php echo $totalLanches; ?> lanche(s) consumido(s)</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-label">Saldo Atual</div>
            <div class="stat-card-val font-tabular" style="color: #1d4ed8;">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></div>
            <div class="stat-card-sub font-tabular">Disponível na carteira</div>
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
        <!-- 3. Modo Analítico Completo: Tabela Oficial com Saldo Progressivo Linha a Linha -->
        <div class="table-section-header">
            <div>
                <div class="table-section-title">Extrato Analítico de Consumo e Recargas</div>
                <div class="table-section-period">
                    Período: <strong class="font-tabular"><?php echo date('d/m/Y', strtotime($dataInicio)); ?></strong> a <strong class="font-tabular"><?php echo date('d/m/Y', strtotime($dataFim)); ?></strong>
                </div>
            </div>
            <div class="table-section-badge font-tabular">
                <?php echo count($linhas); ?> lançamento(s)
            </div>
        </div>

        <table class="table-extrato">
            <thead>
                <tr>
                    <th class="cell-left" style="width: 11%;">Data</th>
                    <th class="cell-left" style="width: 36%;">Descrição</th>
                    <th class="cell-center" style="width: 6%;">Qtd.</th>
                    <th class="cell-right" style="width: 11%;">V. Unitário</th>
                    <th class="cell-right" style="width: 11%;">V. Total</th>
                    <th class="cell-center" style="width: 11%;">Forma Pgto.</th>
                    <th class="cell-right" style="width: 14%;">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <!-- Linha Oficial de Saldo Inicial -->
                <tr class="row-initial">
                    <td class="cell-left font-tabular" style="color: #64748b;">-</td>
                    <td class="cell-left"><strong>Saldo inicial</strong></td>
                    <td class="cell-center" style="color: #64748b;">-</td>
                    <td class="cell-right" style="color: #64748b;">-</td>
                    <td class="cell-right" style="color: #64748b;">-</td>
                    <td class="cell-center" style="color: #64748b;">-</td>
                    <td class="cell-right font-tabular" style="font-weight: 700;">R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?></td>
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
                        <tr class="<?php echo $rowClass; ?>" <?php echo $isRecarga ? 'style="background-color: #f0fdf4 !important;"' : ''; ?>>
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
                                        echo 'R$ ' . number_format((float)$l['valor_unitario'], 2, ',', '.');
                                    } else {
                                        echo htmlspecialchars((string)$l['valor_unitario']);
                                    }
                                ?>
                            </td>
                            <td class="cell-right font-tabular <?php echo $isRecarga ? 'font-weight-bold' : ''; ?>" <?php echo $isRecarga ? 'style="color: #16a34a;"' : ''; ?>>
                                R$ <?php echo number_format((float)($l['valor_total'] ?? 0.0), 2, ',', '.'); ?>
                            </td>
                            <td class="cell-center font-tabular" style="font-size: 7.5pt;">
                                <span class="code-tag font-tabular" style="font-size: 7.5pt;"><?php echo htmlspecialchars($l['forma_pagamento'] ?? 'Saldo'); ?></span>
                            </td>
                            <td class="cell-right font-tabular" style="font-weight: 700; <?php echo ((float)($l['saldo'] ?? 0.0) < 0) ? 'color: #dc2626;' : ''; ?>">
                                R$ <?php echo number_format((float)($l['saldo'] ?? 0.0), 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="cell-left">Total gasto no período</td>
                    <td class="cell-right font-tabular" style="color: #dc2626;">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></td>
                    <td colspan="2" class="cell-right font-tabular" style="color: #1d4ed8;">
                        Saldo Atual: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
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

    window.addEventListener('wheel', function(e) {
        if (e.ctrlKey) {
            e.preventDefault();
            if (e.deltaY < 0) {
                alterarZoom(0.1);
            } else {
                alterarZoom(-0.1);
            }
        }
    }, { passive: false });

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
