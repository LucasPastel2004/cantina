<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Timbrado — Baixas de Mercadorias (Perdas & Descarte)
 * 
 * Conforme docs/FSD.md (RN-15), docs/DESIGN.md e padrões oficiais de relatórios A4
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
if (!isset($dados) || !is_array($dados)) {
    $dados = get_defined_vars();
}

$periodoOficial = $dados['periodo_oficial'] ?? (date('d/m/Y') . ' a ' . date('d/m/Y'));
$dataInicio = $dados['data_inicio'] ?? date('Y-m-d');
$dataFim = $dados['data_fim'] ?? date('Y-m-d');
$filtrosDesc = $dados['filtros_desc'] ?? [];
$movimentacoes = $dados['movimentacoes'] ?? [];
$totais = $dados['totais'] ?? [
    'total_unidades' => 0,
    'total_registros' => 0,
    'por_motivo' => []
];
$motivosPerda = $dados['motivos_perda'] ?? [
    'avaria' => 'Avaria / Descarte',
    'vencimento' => 'Vencimento',
    'sobra_producao' => 'Produto Não Consumido',
    'ajuste_contagem' => 'Ajuste de Contagem'
];

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Baixas de Mercadorias — Colégio Monteiro Lobato</title>
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
        .btn-back-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            margin-right: 6px;
        }
        .btn-back-action:hover {
            background: #f1f5f9;
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
            .page-sheet {
                background: #ffffff;
                width: 210mm;
                min-height: 297mm;
                margin: 0 auto;
                padding: 14mm 14mm 14mm 14mm;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
                border-radius: 2px;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .page-sheet {
                padding: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
            }
        }

        /* Cabeçalho Oficial Timbrado */
        .report-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .report-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .report-header-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #cbd5e1;
        }
        .report-title-block h1 {
            font-size: 14pt;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .report-title-block .subtitle {
            font-size: 9pt;
            font-weight: 600;
            color: #475569;
            margin: 2px 0 0 0;
        }
        .report-header-right {
            text-align: right;
            font-size: 8pt;
            color: #334155;
            line-height: 1.35;
        }

        /* Resumo Estatístico */
        .stat-grid {
            display: flex;
            gap: 8px;
            margin-bottom: 14px;
        }
        .stat-box {
            flex: 1;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            background: #f8fafc;
            border-radius: 4px;
        }
        .stat-box-title {
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: 700;
            color: #475569;
            display: block;
            margin-bottom: 3px;
        }
        .stat-box-value {
            font-size: 12pt;
            font-weight: 800;
            color: #0f172a;
        }
        .stat-box-value.text-danger {
            color: #b91c1c;
        }

        /* Tabela Oficial de Auditoria */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 16px;
        }
        .report-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7.5pt;
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            letter-spacing: 0.02em;
        }
        .report-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            vertical-align: middle;
        }
        .report-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .cell-center { text-align: center; }
        .cell-right { text-align: right; }
        .cell-left { text-align: left; }

        /* Rodapé de Página e Auditoria */
        .report-footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Barra Superior de Controle para Navegador -->
    <div class="no-print">
        <div>
            <a href="<?php echo $baseUrl; ?>/relatorios/baixas-mercadorias" class="btn-back-action">← Voltar ao Filtro</a>
            <span style="color: #94a3b8;">Visualização Oficial A4 (Anti-Clipping)</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn-print-action">Imprimir / Salvar PDF</button>
            <button onclick="window.close()" class="btn-close-action">Fechar</button>
        </div>
    </div>

    <!-- Folha Física A4 -->
    <div class="page-sheet">

        <!-- Cabeçalho Oficial -->
        <header class="report-header">
            <div class="report-header-left">
                <?php if (!empty($logoDataUri)): ?>
                    <img src="<?php echo $logoDataUri; ?>" alt="Colégio Monteiro Lobato" class="report-header-logo">
                <?php endif; ?>
                <div class="report-title-block">
                    <h1>COLÉGIO MONTEIRO LOBATO</h1>
                    <div class="subtitle">Relatório Oficial de Baixas de Mercadorias (Perdas & Descarte)</div>
                </div>
            </div>
            <div class="report-header-right font-tabular">
                <div><strong>Período:</strong> <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Emissão:</strong> <?php echo date('d/m/Y H:i:s'); ?></div>
                <div><strong>Módulo:</strong> Gestão de Estoque & Auditoria (RN-15)</div>
            </div>
        </header>

        <!-- Filtros Aplicados (Se Houver) -->
        <?php if (!empty($filtrosDesc)): ?>
            <div style="font-size: 7.5pt; color: #475569; margin-bottom: 10px; padding: 4px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 3px;">
                <strong>Filtros aplicados:</strong> <?php echo htmlspecialchars(implode(' | ', $filtrosDesc), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <!-- Resumo Estatístico em Blocos -->
        <div class="stat-grid">
            <div class="stat-box">
                <span class="stat-box-title">Total de Baixas (Unidades)</span>
                <span class="stat-box-value text-danger font-tabular">-<?php echo number_format($totais['total_unidades'] ?? 0, 0, ',', '.'); ?> un</span>
            </div>
            <div class="stat-box">
                <span class="stat-box-title">Ocorrências / Lançamentos</span>
                <span class="stat-box-value font-tabular"><?php echo (int)($totais['total_registros'] ?? count($movimentacoes)); ?></span>
            </div>
            <div class="stat-box" style="flex: 2;">
                <span class="stat-box-title">Distribuição por Motivo no Período</span>
                <div style="font-size: 8pt; margin-top: 2px;">
                    <?php 
                    $dist = $totais['por_motivo'] ?? [];
                    if (empty($dist)): ?>
                        <span style="color: #64748b;">Nenhuma ocorrência computada.</span>
                    <?php else: 
                        $itensMot = [];
                        foreach ($dist as $mot => $qtd) {
                            $nomeMot = $motivosPerda[$mot] ?? $mot;
                            $itensMot[] = "<strong>{$nomeMot}:</strong> -{$qtd} un";
                        }
                        echo implode(' &nbsp;|&nbsp; ', $itensMot);
                    endif; ?>
                </div>
            </div>
        </div>

        <!-- Tabela Analítica -->
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 105px;" class="cell-center">Data / Hora</th>
                    <th style="width: 75px;" class="cell-center">Código</th>
                    <th class="cell-left">Produto</th>
                    <th style="width: 65px;" class="cell-right">Qtd</th>
                    <th style="width: 130px;" class="cell-left">Motivo Padronizado</th>
                    <th class="cell-left">Justificativa Operacional</th>
                    <th style="width: 100px;" class="cell-left">Operador</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimentacoes)): ?>
                    <tr>
                        <td colspan="7" class="cell-center" style="padding: 16px; color: #64748b;">
                            Nenhum registro de perda localizado para os parâmetros informados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimentacoes as $mov): ?>
                        <tr>
                            <td class="cell-center font-tabular" style="font-size: 7.5pt; color: #334155;">
                                <?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?>
                            </td>
                            <td class="cell-center font-tabular" style="font-size: 7.5pt; font-weight: 600;">
                                <?php echo htmlspecialchars($mov['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="cell-left">
                                <strong style="color: #0f172a;"><?php echo htmlspecialchars($mov['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span style="font-size: 7pt; color: #64748b; display: block;"><?php echo htmlspecialchars($mov['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td class="cell-right font-tabular" style="font-weight: 700; color: #b91c1c;">
                                -<?php echo (int)$mov['quantidade']; ?> un
                            </td>
                            <td class="cell-left" style="font-weight: 600; color: #475569;">
                                <?php echo htmlspecialchars($motivosPerda[$mov['motivo_perda']] ?? $mov['motivo_perda'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="cell-left" style="color: #334155; font-size: 7.5pt;">
                                <?php echo !empty($mov['justificativa']) ? htmlspecialchars($mov['justificativa'], ENT_QUOTES, 'UTF-8') : '—'; ?>
                            </td>
                            <td class="cell-left" style="font-size: 7.5pt; color: #334155;">
                                <?php echo htmlspecialchars($mov['operador_nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Rodapé do Relatório -->
        <footer class="report-footer">
            <div>
                Cantina Web — Colégio Monteiro Lobato &bull; Documento de Auditoria e Controle Físico de Estoque
            </div>
            <div class="font-tabular">
                Página 1 de 1 &bull; Sistema Cantina Web V2
            </div>
        </footer>

    </div>

</body>
</html>
