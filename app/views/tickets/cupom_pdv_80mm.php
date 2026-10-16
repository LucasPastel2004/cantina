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
        <span class="toolbar-title">Venda PDV #<?php echo $id; ?></span>
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
