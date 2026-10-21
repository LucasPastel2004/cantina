<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Grade de Fichas/Tickets em Folha A4 (8 tickets por página para recorte)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16), docs/DESIGN.md e padrão corporativo Enterprise
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$tickets = $tickets ?? [];
if (isset($ticket) && !empty($ticket)) {
    $tickets = [$ticket];
}

$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
$chunks = array_chunk($tickets, 8);
$totalTickets = count($tickets);
$dataConsumoFmt = !empty($parametros['data']) ? date('d/m/Y', strtotime($parametros['data'])) : date('d/m/Y');
$turnoFmt = ucfirst($parametros['turno'] ?? 'matutino');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tickets em Folha A4 (Grade para Recorte) — Cantina Web</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #0f172a;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        /* Barra de Controle Superior Padronizada (#1e293b) */
        .no-print-bar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 200mm;
            max-width: calc(100% - 32px);
            margin: 10px auto 16px auto;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 10px;
            z-index: 1000;
        }
        .no-print-bar .zoom-controls {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            padding: 2px 6px;
        }
        .no-print-bar .btn-zoom {
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
        .no-print-bar .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .no-print-bar .btn-zoom-val {
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
        .no-print-bar .btn-zoom-val:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: underline;
        }
        .no-print-bar .btn-action {
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
        .no-print-bar .btn-action:hover {
            background: #f1f5f9;
            color: #1d4ed8;
        }
        .no-print-bar .btn-primary {
            background: #1d4ed8;
            color: #ffffff;
            border: 1px solid #1e40af;
        }
        .no-print-bar .btn-primary:hover {
            background: #1e40af;
            color: #ffffff;
        }
        .document-zoom-area {
            zoom: var(--doc-zoom, 1);
            transform-origin: top center;
            transition: zoom 0.1s ease-out;
        }

        /* Estrutura das Folhas A4 */
        .a4-sheet {
            width: 194mm;
            min-height: 275mm;
            max-height: 281mm;
            margin: 24px auto 36px auto;
            background: #ffffff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06);
            border-radius: 4px;
            page-break-after: always;
            break-after: page;
            padding: 2mm 0;
        }
        .a4-sheet:last-child {
            page-break-after: auto;
            break-after: auto;
            margin-bottom: 48px;
        }
        .a4-grid {
            display: grid;
            grid-template-columns: repeat(2, 94mm);
            grid-auto-rows: 66mm;
            gap: 4mm 6mm;
            justify-content: center;
        }
        .ticket-card {
            width: 94mm;
            height: 66mm;
            border: 1px dashed #64748b;
            border-radius: 4px;
            padding: 3mm 4mm;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .scissor-tag {
            position: absolute;
            top: -6px;
            right: 8px;
            font-size: 8pt;
            color: #94a3b8;
            background: #ffffff;
            padding: 0 4px;
            line-height: 1;
        }
        .card-header {
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 1.5mm;
        }
        .logo-img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }
        .header-titles {
            flex: 1;
            line-height: 1.15;
        }
        .school-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .school-sub {
            font-size: 6.5pt;
            color: #64748b;
        }
        .card-body {
            padding: 1.5mm 0;
            display: flex;
            flex-direction: column;
            gap: 1.5mm;
        }
        .code-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ticket-code {
            font-family: monospace;
            font-size: 8pt;
            font-weight: bold;
            color: #1e293b;
            letter-spacing: 0.5px;
        }
        .via-badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 1px 4px;
            border: 1px solid #0f172a;
            border-radius: 2px;
            text-transform: uppercase;
        }
        .student-row {
            line-height: 1.2;
        }
        .student-name {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .student-turma {
            font-size: 7.5pt;
            font-weight: 600;
            color: #475569;
        }
        .product-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 1.5mm 3mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 4px;
        }
        .product-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }
        .product-qty {
            font-size: 8pt;
            font-weight: bold;
            color: #1d4ed8;
            font-family: monospace;
            white-space: nowrap;
        }
        .alergeno-badge {
            border: 1px solid #b45309;
            background: #fef3c7;
            color: #92400e;
            font-size: 6.5pt;
            font-weight: bold;
            text-align: center;
            padding: 1px 3px;
            border-radius: 2px;
            text-transform: uppercase;
        }
        .card-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 1.5mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6pt;
            color: #64748b;
        }

        /* Configurações Estritas de Impressão (@media print) */
        @media print {
            .no-print-bar, .no-print {
                display: none !important;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            body {
                background-color: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .a4-sheet {
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                height: 297mm !important;
                margin: 0 auto !important;
                padding: 8mm 6mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
                page-break-after: always !important;
                break-after: page !important;
            }
            .a4-sheet:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }
    </style>
</head>
<body>

<!-- Barra de Controle Superior Padronizada (#1e293b) -->
<div class="no-print-bar">
    <div style="font-size: 12px; font-weight: 600;">
        Grade de Fichas (Folha A4) • <?php echo $totalTickets; ?> ticket(s) • <?php echo count($chunks); ?> folha(s) A4 • <?php echo htmlspecialchars($dataConsumoFmt); ?>
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
        <?php
            $query80mm = $_GET;
            $query80mm['formato'] = '80mm';
            $url80mm = '?' . http_build_query($query80mm);
        ?>
        <a href="<?php echo htmlspecialchars($url80mm); ?>" class="btn-action" title="Alternar para visualização em bobina térmica de 80mm">
            Alternar para Bobina 80mm
        </a>
        <button type="button" class="btn-action btn-primary" onclick="window.print()" title="Imprimir em folha A4 (Ctrl + P)">
            🖨 Imprimir em Folha A4
        </button>
        <button type="button" class="btn-action" onclick="fecharJanela()" title="Fechar Visualização">
            ✕ Fechar
        </button>
    </div>
</div>

<div id="document-zoom-area" class="document-zoom-area">

<?php if (empty($chunks)): ?>
    <div style="text-align: center; padding: 40px; color: #64748b;">
        Nenhum ticket encontrado para emissão.
    </div>
<?php else: ?>
    <?php foreach ($chunks as $chunkIndex => $chunk): ?>
        <div class="a4-sheet">
            <div class="a4-grid">
                <?php foreach ($chunk as $t): ?>
                    <?php
                        $codigoTicket = $t['codigo_ticket'] ?? ('TCK-' . ($t['id'] ?? 0));
                        $alunoNome = $t['aluno_nome'] ?? 'Aluno';
                        $turma = $t['nome_turma'] ?? ($t['serie'] ?? 'Turma Regular');
                        $produto = $t['produto_descricao'] ?? 'Lanche do Dia';
                        $qtd = (int)($t['item_quantidade'] ?? 1);
                        $ticketData = !empty($t['data_consumo']) ? date('d/m/Y', strtotime($t['data_consumo'])) : $dataConsumoFmt;
                        $ticketTurno = ucfirst($t['turno_consumo'] ?? $turnoFmt);
                        $alergenos = trim((string)($t['tag_alergenos'] ?? ''));
                        $via = (int)($t['numero_via'] ?? 1);
                    ?>
                    <div class="ticket-card">
                        <span class="scissor-tag">✂</span>

                        <div class="card-header">
                            <?php if (!empty($logoDataUri)): ?>
                                <img src="<?php echo $logoDataUri; ?>" alt="Logo" class="logo-img">
                            <?php endif; ?>
                            <div class="header-titles">
                                <div class="school-title">COLÉGIO MONTEIRO LOBATO</div>
                                <div class="school-sub">Cantina Escolar &bull; <?php echo $ticketData; ?> (<?php echo $ticketTurno; ?>)</div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="code-row">
                                <span class="ticket-code"><?php echo htmlspecialchars($codigoTicket); ?></span>
                                <?php if ($via > 1): ?>
                                    <span class="via-badge"><?php echo $via; ?>ª VIA</span>
                                <?php endif; ?>
                            </div>

                            <div class="student-row">
                                <div class="student-name" title="<?php echo htmlspecialchars($alunoNome); ?>">
                                    <?php echo htmlspecialchars($alunoNome); ?>
                                </div>
                                <div class="student-turma">
                                    <?php echo htmlspecialchars($turma); ?>
                                </div>
                            </div>

                            <div class="product-box">
                                <div class="product-name" title="<?php echo htmlspecialchars($produto); ?>">
                                    <?php echo htmlspecialchars($produto); ?>
                                </div>
                                <div class="product-qty">QTD: <?php echo $qtd; ?> UN</div>
                            </div>

                            <?php if (!empty($alergenos)): ?>
                                <div class="alergeno-badge">
                                    [ATENÇÃO: <?php echo htmlspecialchars($alergenos); ?>]
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer">
                            <span>Apresentar no recreio &bull; Descartar após o consumo</span>
                            <span style="font-family: monospace; font-size: 5.5pt;"><?php echo htmlspecialchars($codigoTicket); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
// =====================================================================
// CONTROLE DE ZOOM PARA CONFERÊNCIA VISUAL DE IMPRESSÃO / SALVAR EM PDF
// =====================================================================
let currentZoom = 1.0;
const minZoom = 0.5;
const maxZoom = 2.5;

function aplicarZoom(novoZoom) {
    currentZoom = Math.min(Math.max(novoZoom, minZoom), maxZoom);
    currentZoom = Math.round(currentZoom * 100) / 100;
    
    const zoomArea = document.getElementById('document-zoom-area');
    const zoomValueBtn = document.getElementById('btnZoomReset');
    
    if (zoomArea) {
        zoomArea.style.zoom = currentZoom;
        zoomArea.style.setProperty('--doc-zoom', currentZoom);
    }
    
    if (zoomValueBtn) {
        zoomValueBtn.textContent = Math.round(currentZoom * 100) + '%';
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

// Restaura zoom preferido da sessão ao carregar
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

// Atalhos de Teclado (Ctrl + / Ctrl - / Ctrl 0)
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
    } else if (e.key === 'Escape') {
        fecharJanela();
    }
});

// Atalho Ctrl + Roda do Mouse
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

// Proteção de Impressão: força 100% mandatória antes de abrir diálogo de impressão
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
}
</script>
</body>
</html>
