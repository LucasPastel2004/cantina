<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Cupom Térmico 80mm — Tickets de Lanche de Alunos (Individual ou Lote)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$tickets = $tickets ?? [];
if (isset($ticket) && !empty($ticket)) {
    $tickets = [$ticket];
}

$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
$totalTickets = count($tickets);
$dataConsumoFmt = !empty($parametros['data']) ? date('d/m/Y', strtotime($parametros['data'])) : date('d/m/Y');
$turnoFmt = ucfirst($parametros['turno'] ?? 'matutino');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Tickets Térmicos 80mm — Cantina Web</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            font-weight: 700;
            color: #000000;
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

        /* Bobina Contínua 80mm */
        .receipt-roll {
            width: 80mm;
            max-width: 80mm;
            margin: 24px auto 48px auto;
            background: #ffffff;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12), 0 1px 3px rgba(0, 0, 0, 0.06);
            border-radius: 4px;
            padding: 4mm 0 8mm 0;
        }

        .ticket-wrapper {
            width: 72mm;
            margin: 0 auto;
            padding: 3mm 2mm 2mm 2mm;
            font-weight: 700;
            color: #000000;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .header-center {
            text-align: center;
            margin-bottom: 3px;
        }
        .logo-img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: block;
            margin: 0 auto 2px auto;
        }
        .school-name {
            font-size: 8.5pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            color: #000000;
        }
        .ticket-sub {
            font-size: 7.5pt;
            font-weight: 700;
            color: #000000;
        }
        .divider {
            border-top: 1.5px dashed #000000;
            margin: 3px 0;
        }
        .ticket-code {
            font-size: 9pt;
            font-weight: 800;
            text-align: center;
            font-family: monospace;
            letter-spacing: 0.5px;
            color: #000000;
        }
        .via-badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: 800;
            padding: 1px 4px;
            border: 1.5px solid #000000;
            border-radius: 2px;
            text-transform: uppercase;
            margin-left: 4px;
            color: #000000;
        }
        .student-box {
            margin: 3px 0;
            padding: 2px 0;
        }
        .student-label {
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #000000;
        }
        .student-name {
            font-size: 10pt;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
            color: #000000;
        }
        .student-turma {
            font-size: 8pt;
            font-weight: 700;
            color: #000000;
        }
        .product-box {
            background: #f4f4f4;
            border: 1.5px solid #000000;
            padding: 4px;
            margin: 4px 0;
            text-align: center;
        }
        .product-name {
            font-size: 9.5pt;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
            color: #000000;
        }
        .product-qty {
            font-size: 8.5pt;
            font-weight: 800;
            margin-top: 2px;
            color: #000000;
        }
        .alergeno-box {
            border: 1.5px solid #000000;
            padding: 2px 3px;
            margin: 3px 0;
            text-align: center;
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            background: #fff;
            color: #000000;
        }
        .footer-info {
            text-align: center;
            font-size: 6.5pt;
            font-weight: 700;
            color: #000000;
            margin-top: 3px;
        }
        .cut-separator {
            text-align: center;
            font-size: 7pt;
            color: #444444;
            margin: 6px 0 4px 0;
            letter-spacing: 1px;
            user-select: none;
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
                width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                font-weight: 700 !important;
                color: #000000 !important;
            }
            body, .ticket-wrapper, div, span, p, strong {
                font-weight: 700 !important;
                color: #000000 !important;
            }
            .receipt-roll {
                width: 80mm !important;
                max-width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
            }
            .ticket-wrapper {
                width: 72mm !important;
                margin: 0 auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                font-weight: 700 !important;
                color: #000000 !important;
            }
        }
    </style>
</head>
<body>

<!-- Barra de Controle Superior Padronizada (#1e293b) -->
<div class="no-print-bar">
    <div style="font-size: 12px; font-weight: 600;">
        Bobina Térmica (80mm) • <?php echo $totalTickets; ?> ticket(s) • <?php echo htmlspecialchars($dataConsumoFmt); ?> (<?php echo htmlspecialchars($turnoFmt); ?>)
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
            $queryA4 = $_GET;
            $queryA4['formato'] = 'a4';
            $urlA4 = '?' . http_build_query($queryA4);
        ?>
        <a href="<?php echo htmlspecialchars($urlA4); ?>" class="btn-action" title="Alternar para visualização em grade de 8 fichas por Folha A4">
            Alternar para Folha A4
        </a>
        <button type="button" class="btn-action btn-primary" onclick="window.print()" title="Imprimir na Bobina 80mm (Ctrl + P)">
            🖨 Imprimir na Bobina (80mm)
        </button>
        <button type="button" class="btn-action" onclick="fecharJanela()" title="Fechar Visualização">
            ✕ Fechar
        </button>
    </div>
</div>

<div id="document-zoom-area" class="document-zoom-area">
<div class="receipt-roll">
<?php foreach ($tickets as $index => $t): ?>
    <?php
        $codigoTicket = $t['codigo_ticket'] ?? ('TCK-' . ($t['id'] ?? 0));
        $alunoNome = $t['aluno_nome'] ?? 'Aluno';
        $turma = $t['nome_turma'] ?? ($t['serie'] ?? 'Turma Regular');
        $produto = $t['produto_descricao'] ?? 'Lanche do Dia';
        $qtd = (int)($t['item_quantidade'] ?? 1);
        $dataConsumo = !empty($t['data_consumo']) ? date('d/m/Y', strtotime($t['data_consumo'])) : $dataConsumoFmt;
        $turno = ucfirst($t['turno_consumo'] ?? $turnoFmt);
        $alergenos = trim((string)($t['tag_alergenos'] ?? ''));
        $via = (int)($t['numero_via'] ?? 1);
    ?>
    <div class="ticket-wrapper">
        <div class="header-center">
            <?php if (!empty($logoDataUri)): ?>
                <img src="<?php echo $logoDataUri; ?>" alt="Logo" class="logo-img">
            <?php endif; ?>
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="ticket-sub">Cantina Escolar &bull; <?php echo $dataConsumo; ?> (<?php echo $turno; ?>)</div>
        </div>

        <div class="divider"></div>

        <div class="ticket-code">
            <?php echo htmlspecialchars($codigoTicket); ?>
            <?php if ($via > 1): ?>
                <span class="via-badge"><?php echo $via; ?>ª VIA</span>
            <?php endif; ?>
        </div>

        <div class="student-box">
            <div class="student-label">Estudante / Turma:</div>
            <div class="student-name"><?php echo htmlspecialchars($alunoNome); ?></div>
            <div class="student-turma"><?php echo htmlspecialchars($turma); ?></div>
        </div>

        <div class="product-box">
            <div class="product-name"><?php echo htmlspecialchars($produto); ?></div>
            <div class="product-qty">QUANTIDADE: <?php echo $qtd; ?> UN</div>
        </div>

        <?php if (!empty($alergenos)): ?>
            <div class="alergeno-box">
                [ATENÇÃO: <?php echo htmlspecialchars($alergenos); ?>]
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <div class="footer-info">
            Entregar no balcão da cantina no recreio.<br>
            Uso individual e intransferível. Descartar após o consumo.
        </div>

        <?php if ($index < $totalTickets - 1): ?>
            <div class="cut-separator">
                ✂ - - - - - - - - - - - - - - - - - - - - - - - -
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
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
