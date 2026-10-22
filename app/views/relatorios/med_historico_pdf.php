<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório A4 Timbrado de Histórico de Eventos MED e Quitações
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dadosMed ?? ($dados ?? []);
$eventos = $dados['eventos'] ?? [];
$totalEventos = (int)($dados['total_eventos'] ?? count($eventos));
$totalEstornos = (float)($dados['total_estornos'] ?? 0.0);
$totalQuitacoes = (float)($dados['total_quitacoes'] ?? 0.0);
$filtros = $dados['filtros'] ?? [];
$emissao = date('d/m/Y H:i:s');
$operador = $dados['operador'] ?? 'Administração Geral';
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);

$infoFiltros = [];
if (!empty($filtros['busca'])) {
    $infoFiltros[] = 'Busca: "' . htmlspecialchars($filtros['busca'], ENT_QUOTES, 'UTF-8') . '"';
}
if (!empty($filtros['tipo']) && $filtros['tipo'] !== 'todos') {
    $infoFiltros[] = ($filtros['tipo'] === 'estornos') ? 'Tipo: Apenas Estornos MED BACEN' : 'Tipo: Apenas Quitações / Ajustes';
}
if (!empty($filtros['data_inicio']) || !empty($filtros['data_fim'])) {
    $dIni = !empty($filtros['data_inicio']) ? date('d/m/Y', strtotime($filtros['data_inicio'])) : 'início';
    $dFim = !empty($filtros['data_fim']) ? date('d/m/Y', strtotime($filtros['data_fim'])) : 'hoje';
    $infoFiltros[] = "Período: {$dIni} a {$dFim}";
}
$strFiltros = !empty($infoFiltros) ? implode(' &bull; ', $infoFiltros) : 'Todos os registros históricos';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico de Eventos MED & Quitações — Cantina Web</title>
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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #0f172a;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.35;
            width: 100%;
        }
        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }

        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 20px 10px 40px 10px;
            }
            .container {
                width: 200mm;
                max-width: calc(100% - 24px);
                min-height: 280mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 10mm 14mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        /* Barra de Controle Superior Padronizada (#1e293b) */
        .no-print-bar, .no-print {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            width: 200mm;
            max-width: calc(100% - 32px);
            margin: 0 auto 16px auto;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
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
            flex-shrink: 0;
            margin: 0 4px;
        }
        .no-print .btn-zoom {
            background: transparent;
            color: #ffffff;
            border: none;
            width: 20px;
            height: 20px;
            line-height: 18px;
            text-align: center;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            padding: 0;
            border-radius: 3px;
            transition: background 0.15s;
        }
        .no-print .btn-zoom:hover { background: rgba(255, 255, 255, 0.25); }
        .no-print .btn-zoom-val {
            font-size: 11px;
            font-weight: 600;
            min-width: 36px;
            text-align: center;
            background: transparent;
            color: #ffffff;
            border: none;
            cursor: pointer;
            padding: 1px 4px;
            border-radius: 3px;
        }
        .no-print .btn-zoom-val:hover { background: rgba(255, 255, 255, 0.2); }
        .no-print .btn-action {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.15s;
        }
        .no-print .btn-action:hover { background: rgba(255, 255, 255, 0.25); }
        .no-print .btn-action.btn-primary {
            background: #1d4ed8;
            border-color: #2563eb;
            font-weight: 600;
        }
        .no-print .btn-action.btn-primary:hover { background: #1e40af; }

        @media print {
            body { background: #ffffff !important; padding: 0 !important; }
            .no-print, .no-print-bar { display: none !important; }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 10mm 14mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .document-zoom-area { transform: none !important; }
        }

        .document-zoom-area {
            transform-origin: top center;
            transition: transform 0.15s ease-out;
        }

        .print-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .logo-img { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
        .school-title { font-size: 14px; font-weight: 700; color: #0f172a; margin: 0; }
        .report-title { font-size: 12px; font-weight: 600; color: #0f172a; margin: 2px 0 0 0; }
        .report-sub { font-size: 9px; color: #64748b; margin: 1px 0 0 0; }

        .stat-grid {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
        }
        .stat-item { text-align: left; }
        .stat-label { font-size: 8.5px; font-weight: 600; color: #475569; text-transform: uppercase; }
        .stat-value { font-size: 12px; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }

        table {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-bottom: 16px;
            font-size: 9.5px;
            border: 1px solid #000000 !important;
        }
        th {
            background: #f1f5f9 !important;
            color: #0f172a;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #000000 !important;
            text-align: left;
        }
        td {
            padding: 4px 6px;
            border: 1px solid #000000 !important;
            background-color: #ffffff !important;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .code-tag-sm {
            display: inline-block;
            font-family: monospace;
            font-size: 8.5px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 1px 4px;
            color: #334155;
        }
    </style>
</head>
<body>

    <!-- Barra de Controle Superior Padronizada (#1e293b - Visível apenas na tela) -->
    <div class="no-print-bar no-print">
        <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                Histórico de Eventos MED &bull; <?php echo $totalEventos; ?> registro(s)
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
            <button type="button" class="btn-action btn-primary" onclick="window.print()">
                🖨 Imprimir Documento
            </button>
            <button type="button" class="btn-action" onclick="window.close()">
                ✕ Fechar
            </button>
        </div>
    </div>

    <!-- ÁREA ESCALÁVEL COM ZOOM PARA CONFERÊNCIA -->
    <div id="document-zoom-area" class="document-zoom-area">
        <div class="container">

            <div class="print-header">
                <div class="header-left">
                    <?php if (!empty($logoDataUri)): ?>
                        <img src="<?php echo $logoDataUri; ?>" alt="Logo" class="logo-img">
                    <?php endif; ?>
                    <div>
                        <div class="school-title">COLÉGIO MONTEIRO LOBATO</div>
                        <div class="report-title">Relatório de Histórico de Eventos MED & Quitações</div>
                        <div class="report-sub">Emissão: <?php echo $emissao; ?> &bull; Responsável: <?php echo htmlspecialchars($operador); ?> &bull; <?php echo $strFiltros; ?></div>
                    </div>
                </div>
                <div style="text-align: right; padding-right: 2px;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">AUDITORIA MED</div>
                    <div style="font-size: 9px; color: #475569; font-weight: 600; text-transform: uppercase;">Trilha de Movimentações</div>
                </div>
            </div>

            <div class="stat-grid">
                <div class="stat-item">
                    <div class="stat-label">Total de Eventos</div>
                    <div class="stat-value font-tabular"><?php echo $totalEventos; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Débitos Estorno MED</div>
                    <div class="stat-value font-tabular">R$ <?php echo number_format($totalEstornos, 2, ',', '.'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Quitações / Ajustes</div>
                    <div class="stat-value font-tabular">R$ <?php echo number_format($totalQuitacoes, 2, ',', '.'); ?></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 14%; text-align: center;">Data/Hora</th>
                        <th style="width: 25%;">Titular / Família</th>
                        <th style="width: 16%; text-align: center;">Tipo de Operação</th>
                        <th style="width: 11%; text-align: right;">Valor</th>
                        <th style="width: 12%; text-align: right;">Saldo Pós</th>
                        <th style="width: 22%;">Motivo / Protocolo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventos)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="padding: 16px; color: #64748b;">
                                Nenhum evento de MED ou quitação localizado para os filtros informados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($eventos as $e): ?>
                            <?php 
                            $isDebito = ($e['tipo'] === 'debito_contestacao_med');
                            $val = (float)$e['valor'];
                            $saldoPos = (float)$e['saldo_resultante'];
                            ?>
                            <tr>
                                <td class="text-center font-tabular" style="font-size: 9px;">
                                    <?php echo date('d/m/Y H:i', strtotime($e['data_hora'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($e['titular'] ?? 'Desconhecido', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if (!empty($e['responsavel_cpf'])): ?>
                                        <div style="font-size: 8.5px; color: #475569;" class="font-tabular">
                                            CPF: <?php echo htmlspecialchars($e['responsavel_cpf'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="code-tag-sm">
                                        <?php echo $isDebito ? 'Estorno MED BACEN' : 'Quitação / Ajuste'; ?>
                                    </span>
                                </td>
                                <td class="text-right font-tabular" style="font-weight: 600;">
                                    <?php echo $isDebito ? '-' : '+'; ?> R$ <?php echo number_format($val, 2, ',', '.'); ?>
                                </td>
                                <td class="text-right font-tabular">
                                    R$ <?php echo number_format($saldoPos, 2, ',', '.'); ?>
                                </td>
                                <td style="font-size: 8.5px;">
                                    <div><?php echo htmlspecialchars($e['motivo'] ?? 'Sem descrição', ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if (!empty($e['referencia_id'])): ?>
                                        <div style="color: #64748b;" class="font-tabular">Ref: <?php echo htmlspecialchars($e['referencia_id'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 24px; padding-top: 10px; border-top: 1px solid #000000; font-size: 8.5px; color: #475569; display: flex; justify-content: space-between;">
                <div>Colégio Monteiro Lobato &bull; Sistema Cantina Web &bull; Módulo Financeiro & Auditoria</div>
                <div>Documento Interno de Conferência &bull; <?php echo date('d/m/Y H:i'); ?></div>
            </div>

        </div>
    </div>

    <script>
        var currentZoom = 1.0;
        function alterarZoom(delta) {
            currentZoom = Math.min(Math.max(currentZoom + delta, 0.5), 2.0);
            aplicarZoom();
        }
        function resetarZoom() {
            currentZoom = 1.0;
            aplicarZoom();
        }
        function aplicarZoom() {
            var area = document.getElementById('document-zoom-area');
            var btnVal = document.getElementById('btnZoomReset');
            if (area) {
                area.style.transform = 'scale(' + currentZoom + ')';
            }
            if (btnVal) {
                btnVal.textContent = Math.round(currentZoom * 100) + '%';
            }
        }
    </script>
</body>
</html>
