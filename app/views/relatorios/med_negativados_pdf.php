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
            font-size: 11px;
            line-height: 1.4;
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
        }
        .no-print .btn-zoom {
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
        .no-print .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .no-print .btn-zoom-val {
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
        .no-print .btn-zoom-val:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: underline;
        }
        .no-print .btn-action {
            background: #ffffff;
            color: #0f172a;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease-in-out;
        }
        .no-print .btn-action:hover {
            background: #f1f5f9;
            color: #1d4ed8;
        }
        .no-print .btn-primary {
            background: #1d4ed8;
            color: #ffffff;
            border: 1px solid #1e40af;
        }
        .no-print .btn-primary:hover {
            background: #1e40af;
            color: #ffffff;
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
            .no-print-bar, .no-print {
                display: none !important;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            .container {
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
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .print-header, .stat-grid { page-break-inside: avoid; break-inside: avoid; }

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
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-bottom: 20px;
            font-size: 10px;
            border: 1px solid #000000 !important;
        }
        th {
            background: #ffffff !important;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 5px 8px;
            border: 1px solid #000000 !important;
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
            border: 1px solid #000000 !important; /* Linhas pretas estilo Excel */
            background-color: #ffffff !important; /* Fundo branco puro nos itens das listas */
            overflow-wrap: break-word;
            word-break: break-word;
        }
        tr:nth-child(even) td { background-color: #ffffff !important; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row td {
            background: #ffffff !important;
            font-weight: 700;
            border: 1.5px solid #000000 !important;
            font-size: 11px;
            color: #b91c1c;
        }
    </style>
</head>
<body>

    <!-- Barra de Controle Superior Padronizada (#1e293b - Visível apenas na tela) -->
    <div class="no-print-bar no-print">
        <div style="font-size: 12px; font-weight: 600;">
            Relatório de Devedores / MED • <?php echo $totalFamilias; ?> família(s) • Total de Páginas: 1
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
            <button type="button" class="btn-action" onclick="fecharJanela()">
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
                <div class="report-title">Relatório de Famílias com Saldo Devedor — Contestações MED / Inadimplência</div>
                <div class="report-sub">Emissão: <?php echo $emissao; ?> &bull; Responsável: <?php echo htmlspecialchars($operador); ?></div>
            </div>
        </div>
        <div style="text-align: right; padding-right: 2px;">
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
                <th style="width: 17%;">Contato</th>
                <th style="width: 23%;">Estudante(s) Vinculado(s)</th>
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
                        <td><strong><?php echo htmlspecialchars($f['nome'] ?? ($f['responsavel_nome'] ?? '-')); ?></strong></td>
                        <td class="font-tabular"><?php echo htmlspecialchars($f['cpf_formatado'] ?? ($f['cpf'] ?? ($f['responsavel_cpf'] ?? '-'))); ?></td>
                        <td class="font-tabular" style="word-break: break-all;"><?php echo htmlspecialchars(!empty($f['whatsapp']) ? $f['whatsapp'] : (!empty($f['email']) ? $f['email'] : (!empty($f['telefone']) ? $f['telefone'] : ($f['responsavel_telefone'] ?? '-')))); ?></td>
                        <td><?php echo htmlspecialchars($f['dependentes_str'] ?? ($f['alunos_str'] ?? (is_array($f['dependentes'] ?? null) ? '' : ($f['dependentes'] ?? '-')))); ?></td>
                        <td class="text-center">
                            <?php if (!empty($f['bloqueado_med'])): ?>
                                <span style="color: #b91c1c; font-weight: bold;">BLOQUEADO</span>
                            <?php elseif (($f['saldo_atual'] ?? 0) < 0): ?>
                                <span style="color: #b91c1c; font-weight: 600;">Saldo Devedor</span>
                            <?php else: ?>
                                <span style="color: #15803d; font-weight: 600;">Regularizado</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-tabular" style="color: <?php echo (($f['saldo_atual'] ?? 0) < 0) ? '#b91c1c' : '#0f172a'; ?>; font-weight: bold;">
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
                    window.location.href = '<?php echo $baseUrl; ?>/relatorios/devedores';
                }
            }
        }, 150);
    }
</script>
</body>
</html>
