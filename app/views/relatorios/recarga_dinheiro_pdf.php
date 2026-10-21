<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Comprovante Térmico de Recarga em Dinheiro (Bobina 80mm)
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$dados = $dadosRecarga ?? ($dados ?? []);
$transacaoId = (int)($dados['transacao_id'] ?? ($dados['id'] ?? 0));
$sessaoId = (int)($dados['sessao_caixa_id'] ?? ($dados['referencia_id'] ?? 0));
$dataHora = !empty($dados['created_at']) ? date('d/m/Y H:i:s', strtotime($dados['created_at'])) : date('d/m/Y H:i:s');
$operador = $dados['operador_nome'] ?? 'Operador de Caixa';
$responsavel = $dados['responsavel_nome'] ?? 'Responsável Titular';
$cpfRaw = preg_replace('/\D/', '', (string)($dados['responsavel_cpf'] ?? ''));
$cpfMascarado = (strlen($cpfRaw) === 11)
    ? substr($cpfRaw, 0, 3) . '.***.***-' . substr($cpfRaw, -2)
    : ($dados['responsavel_cpf'] ?? '');

$valor = (float)($dados['valor'] ?? 0.0);
$saldoAnterior = (float)($dados['saldo_anterior'] ?? 0.0);
$saldoResultante = (float)($dados['saldo_resultante'] ?? 0.0);
$dependentes = $dados['dependentes'] ?? [];
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
$urlPdf = (defined('CANTINA_URL') ? rtrim(CANTINA_URL, '/') : '') . '/operacional/caixa/comprovante-recarga/' . $transacaoId . '/pdf';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Recarga #<?php echo $transacaoId; ?> — Cantina Web</title>
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
        .line { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .highlight { font-size: 11pt; font-weight: bold; border-top: 1px solid #000000; border-bottom: 1px solid #000000; padding: 4px 0; margin: 4px 0; }
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

            .top-toolbar, .no-print-bar {
                position: sticky;
                top: 10px;
                max-width: calc(100% - 32px);
                width: 80mm;
                min-width: 340px;
                margin: 0 auto 16px auto;
                background-color: #1e293b;
                color: #ffffff;
                border-radius: 6px;
                padding: 8px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                z-index: 1000;
            }

            .toolbar-info {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .toolbar-title {
                font-size: 12px;
                font-weight: 700;
                color: #ffffff;
            }

            .top-toolbar .zoom-controls {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                padding: 2px 6px;
            }
            .top-toolbar .btn-zoom {
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
            .top-toolbar .btn-zoom:hover {
                background: rgba(255, 255, 255, 0.2);
                color: #93c5fd;
            }
            .top-toolbar .btn-zoom-val {
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
            .top-toolbar .btn-zoom-val:hover {
                background: rgba(255, 255, 255, 0.2);
                text-decoration: underline;
            }

            .toolbar-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .btn-action {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 5px 12px;
                font-size: 12px;
                font-weight: 600;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
                border: none;
                transition: all 0.15s ease-in-out;
            }

            .btn-print-primary {
                background: #1d4ed8;
                color: #ffffff;
                border: 1px solid #1e40af;
            }
            .btn-print-primary:hover {
                background: #1e40af;
            }

            .btn-action-secondary {
                background: #ffffff;
                color: #0f172a;
            }
            .btn-action-secondary:hover {
                background: #f1f5f9;
                color: #1d4ed8;
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
            .top-toolbar, .no-print {
                display: none !important;
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

<!-- Barra Superior de Controle Padronizada (#1e293b - Visível apenas na tela) -->
<div class="top-toolbar no-print-bar no-print">
    <div class="toolbar-info">
        <span class="toolbar-title">Recarga #<?php echo $transacaoId; ?></span>
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

    <div class="toolbar-actions">
        <button type="button" class="btn-action btn-print-primary" onclick="window.print();" title="Imprimir documento ou Salvar como PDF">
            🖨 Imprimir
        </button>
        <button type="button" class="btn-action btn-action-secondary" onclick="fecharJanela();" title="Fechar Janela">
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
            <div style="font-size: 7.5pt; color: #333; font-weight: bold;">COMPROVANTE DE RECARGA — Carteira Familiar</div>
        </div>

        <div class="divider"></div>

        <div style="font-size: 7.5pt;">
            <div><strong>Transação:</strong> #<?php echo $transacaoId; ?> &bull; <strong>Sessão:</strong> #<?php echo $sessaoId; ?></div>
            <div><strong>Data/Hora:</strong> <?php echo $dataHora; ?></div>
            <div><strong>Operador:</strong> <?php echo htmlspecialchars($operador); ?></div>
            <div><strong>Titular:</strong> <?php echo htmlspecialchars($responsavel); ?></div>
            <div><strong>CPF:</strong> <?php echo htmlspecialchars($cpfMascarado); ?></div>
        </div>

        <?php if (!empty($dependentes)): ?>
            <div class="divider"></div>
            <div style="font-size: 7pt; color: #333;">
                <strong>Estudante(s) Vinculado(s):</strong><br>
                <?php foreach ($dependentes as $dep): ?>
                    &bull; <?php echo htmlspecialchars($dep['nome'] ?? ''); ?> (<?php echo htmlspecialchars($dep['nome_turma'] ?? 'Turma'); ?>)<br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="divider"></div>

        <div class="line highlight">
            <span>VALOR CREDITADO:</span>
            <span class="font-tabular">R$ <?php echo number_format($valor, 2, ',', '.'); ?></span>
        </div>

        <div style="font-size: 7.5pt;">
            <div class="line font-tabular">
                <span>Saldo Anterior:</span>
                <span>R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?></span>
            </div>
            <div class="line font-tabular" style="font-weight: bold;">
                <span>Novo Saldo Disponível:</span>
                <span>R$ <?php echo number_format($saldoResultante, 2, ',', '.'); ?></span>
            </div>
            <div class="line" style="margin-top: 2px;">
                <span>Forma Pagamento:</span>
                <span><?php echo (($dados['tipo_transacao'] ?? '') === 'recarga_pix' || ($dados['forma_pagamento'] ?? '') === 'pix') ? 'PIX (BALCÃO)' : 'DINHEIRO (BALCÃO)'; ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <div class="footer-text">
            Guarde este comprovante para seu controle.<br>
            Valores creditados na carteira familiar são compartilhados entre os dependentes.
        </div>
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
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
        e.preventDefault();
        window.print();
    } else if (e.key === 'Escape') {
        fecharJanela();
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

window.addEventListener('load', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        setTimeout(function() {
            window.focus();
            window.print();
        }, 250);
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
                window.location.href = '<?php echo $baseUrl; ?>/operacional/caixa';
            }
        }
    }, 150);
}
</script>
</body>
</html>
