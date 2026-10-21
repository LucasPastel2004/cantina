<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Comprovante Térmico de Venda no PDV (Bobina 80mm)
 * 
 * Conforme docs/FSD.md (Área 4, PDV Balcão) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$venda = $venda ?? [];
$id = $venda['id'] ?? 0;
$dataHora = !empty($venda['created_at']) ? date('d/m/Y H:i:s', strtotime($venda['created_at'])) : date('d/m/Y H:i:s');
$operador = $venda['operador_nome'] ?? 'Operador Caixa';
$formaPagto = strtoupper($venda['forma_pagamento'] ?? 'DINHEIRO');
$tipoVenda = $venda['tipo_venda'] ?? 'avulsa';
$colaboradorNome = $venda['colaborador_nome'] ?? null;
$colaboradorMatricula = $venda['colaborador_matricula'] ?? null;
$alunoNome = $venda['aluno_nome'] ?? null;
$alunoTurma = $venda['aluno_turma'] ?? null;

$itens = $venda['itens'] ?? [];
$valorTotal = (float)($venda['valor_total'] ?? 0.0);
$dinheiroRecebido = (float)($venda['valor_dinheiro_recebido'] ?? $valorTotal);
$troco = (float)($venda['troco_devolvido'] ?? 0.0);

$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
$urlPdf = (defined('CANTINA_URL') ? rtrim(CANTINA_URL, '/') : '') . '/operacional/pdv/comprovante/' . $id . '/pdf';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante Balcão #<?php echo $id; ?> — Cantina Web</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        /* Layout Geral e Tipografia do Cupom */
        .wrapper {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000000;
            line-height: 1.25;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .logo-img { width: 34px; height: 34px; border-radius: 50%; display: block; margin: 0 auto 3px auto; }
        .divider { border-top: 1px dashed #000000; margin: 4px 0; }
        .font-tabular { font-variant-numeric: tabular-nums; font-feature-settings: "tnum" 1, "zero" 1; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; margin: 4px 0; }
        th { border-bottom: 1px solid #000000; padding: 2px 0; text-align: left; font-size: 7.5pt; }
        td { padding: 2px 0; border-bottom: 1px dotted #cccccc; }
        .total-box { margin-top: 4px; font-size: 9pt; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .total-destaque { font-size: 11pt; font-weight: bold; border-top: 1px solid #000000; padding-top: 3px; }
        .footer-text { font-size: 6.5pt; color: #444444; margin-top: 8px; text-align: center; }

        /* Estilos na Tela do Computador (Visualizador de Comprovante) */
        @media screen {
            html, body {
                background-color: #f1f5f9;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                width: 100%;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
                margin: 0 auto 16px auto;
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

            .receipt-screen-wrapper {
                display: flex;
                justify-content: center;
                align-items: flex-start;
                padding: 28px 16px 48px 16px;
            }

            .wrapper {
                width: 80mm;
                background: #ffffff;
                padding: 6mm 4mm;
                border-radius: 4px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(0, 0, 0, 0.05);
            }
        }

        /* Estilos na Impressão Física / Bobina Térmica */
        @media print {
            .no-print-bar, .no-print {
                display: none !important;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            html, body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 80mm !important;
            }
            .receipt-screen-wrapper {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .wrapper {
                width: 74mm !important;
                margin: 0 auto !important;
                padding: 3mm 1.5mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

<!-- Barra de Controle Superior Padronizada (#1e293b) -->
<div class="no-print-bar">
    <div style="font-size: 12px; font-weight: 600;">
        Venda PDV #<?php echo $id; ?> • Bobina Térmica 80mm
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
        <button type="button" class="btn-action btn-primary" onclick="window.print()" title="Imprimir Cupom (Ctrl + P)">
            🖨 Imprimir Cupom
        </button>
        <button type="button" class="btn-action" onclick="fecharJanela()" title="Fechar Visualização">
            ✕ Fechar
        </button>
    </div>
</div>

<div id="document-zoom-area" class="document-zoom-area">
<div class="receipt-screen-wrapper">
    <div class="wrapper">
    <div class="text-center">
        <?php if (!empty($logoDataUri)): ?>
            <img src="<?php echo $logoDataUri; ?>" alt="Logo" class="logo-img">
        <?php endif; ?>
        <div style="font-weight: bold; font-size: 9pt;">COLÉGIO MONTEIRO LOBATO</div>
        <div style="font-size: 7.5pt; color: #333;">Comprovante de Venda #<?php echo $id; ?> — Balcão Cantina</div>
    </div>

    <div class="divider"></div>

    <div style="font-size: 7.5pt;">
        <div><strong>Venda:</strong> #<?php echo $id; ?> &bull; <strong>Data:</strong> <?php echo $dataHora; ?></div>
        <div><strong>Operador:</strong> <?php echo htmlspecialchars($operador); ?></div>
        <?php if (!empty($alunoNome)): ?>
            <div><strong>Estudante:</strong> <?php echo htmlspecialchars($alunoNome); ?><?php echo !empty($alunoTurma) ? ' (' . htmlspecialchars($alunoTurma) . ')' : ''; ?></div>
        <?php endif; ?>
        <?php if (!empty($colaboradorNome)): ?>
            <div style="margin-top: 2px; padding: 2px 4px; background: #f8fafc; border: 1px solid #ccc;">
                <strong>Colaborador:</strong> <?php echo htmlspecialchars($colaboradorNome); ?><?php echo !empty($colaboradorMatricula) ? ' (Matr: ' . htmlspecialchars($colaboradorMatricula) . ')' : ''; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th style="width: 50%;">Item</th>
                <th style="width: 15%;" class="text-center">Qtd</th>
                <th style="width: 15%;" class="text-right">Unit</th>
                <th style="width: 20%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['produto_descricao'] ?? ($item['produto_nome'] ?? 'Produto')); ?></td>
                    <td class="text-center font-tabular"><?php echo (int)($item['quantidade'] ?? 1); ?></td>
                    <td class="text-right font-tabular"><?php echo number_format((float)($item['preco_unitario'] ?? 0), 2, ',', '.'); ?></td>
                    <td class="text-right font-tabular"><?php echo number_format((float)($item['subtotal'] ?? 0), 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-line total-destaque">
            <span>TOTAL:</span>
            <span class="font-tabular">R$ <?php echo number_format($valorTotal, 2, ',', '.'); ?></span>
        </div>
        <?php
        $formaDesc = match($venda['forma_pagamento'] ?? '') {
            'saldo' => 'SALDO CARTEIRA',
            'pix_balcao' => 'PIX BALCÃO',
            'dinheiro' => 'DINHEIRO',
            'desconto_folha' => 'DESCONTO EM FOLHA',
            default => strtoupper($venda['forma_pagamento'] ?? 'DINHEIRO')
        };
        ?>
        <div class="total-line" style="margin-top: 3px; font-size: 7.5pt;">
            <span>Forma Pagamento:</span>
            <span><strong><?php echo $formaDesc; ?></strong></span>
        </div>
        <?php if ($formaPagto === 'DINHEIRO'): ?>
            <div class="total-line font-tabular" style="font-size: 7.5pt;">
                <span>Valor Recebido:</span>
                <span>R$ <?php echo number_format($dinheiroRecebido, 2, ',', '.'); ?></span>
            </div>
            <div class="total-line font-tabular" style="font-size: 7.5pt;">
                <span>Troco:</span>
                <span>R$ <?php echo number_format($troco, 2, ',', '.'); ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="divider"></div>

    <div class="footer-text">
        Colégio Monteiro Lobato &bull; Valores para o Futuro!<br>
        Obrigado pela preferência e bom lanche!
    </div>
</div>
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

window.addEventListener('load', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        setTimeout(function() {
            window.focus();
            window.print();
        }, 250);
    }
});
</script>

</body>
</html>
