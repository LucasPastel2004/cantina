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

            .top-toolbar {
                position: sticky;
                top: 0;
                left: 0;
                right: 0;
                width: 100%;
                background: #ffffff;
                border-bottom: 1px solid #e2e8f0;
                padding: 10px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                z-index: 1000;
            }

            .toolbar-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .toolbar-title {
                font-size: 14px;
                font-weight: 700;
                color: #0f172a;
            }

            .toolbar-tag {
                font-size: 11px;
                font-weight: 600;
                color: #475569;
                background: #f1f5f9;
                padding: 2px 8px;
                border-radius: 4px;
                border: 1px solid #e2e8f0;
            }

            .toolbar-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .btn-action {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 14px;
                font-size: 12px;
                font-weight: 600;
                border-radius: 4px;
                cursor: pointer;
                text-decoration: none;
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
                color: #334155;
                border: 1px solid #cbd5e1;
            }
            .btn-action-secondary:hover {
                background: #f8fafc;
                border-color: #94a3b8;
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

<div class="top-toolbar no-print">
    <div class="toolbar-info">
        <span class="toolbar-title">Recarga #<?php echo $transacaoId; ?></span>
        <span class="toolbar-tag">Bobina Térmica 80mm</span>
    </div>
    <div class="toolbar-actions">
        <button type="button" class="btn-action btn-print-primary" onclick="window.print();" title="Imprimir ou Salvar PDF (Ctrl + P)">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-action btn-action-secondary" onclick="window.close();" title="Fechar Janela">
            Fechar
        </button>
    </div>
</div>

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

<script>
window.addEventListener('load', function() {
    const params = new URLSearchParams(window.location.search);
    if (params.get('autoprint') === '1') {
        setTimeout(function() {
            window.focus();
            window.print();
        }, 250);
    }
});
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
        e.preventDefault();
        window.print();
    } else if (e.key === 'Escape') {
        window.close();
    }
});
</script>

</body>
</html>
