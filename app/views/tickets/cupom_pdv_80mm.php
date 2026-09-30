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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante Balcão #<?php echo $id; ?> — Cantina Web</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            width: 80mm;
        }
        .wrapper {
            width: 72mm;
            margin: 0 auto;
            padding: 5mm 2mm;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .logo-img { width: 34px; height: 34px; border-radius: 50%; display: block; margin: 0 auto 3px auto; }
        .divider { border-top: 1px dashed #000; margin: 4px 0; }
        .font-tabular { font-variant-numeric: tabular-nums; font-feature-settings: "tnum" 1, "zero" 1; }
        table { width: 100%; border-collapse: collapse; font-size: 8pt; margin: 4px 0; }
        th { border-bottom: 1px solid #000; padding: 2px 0; text-align: left; font-size: 7.5pt; }
        td { padding: 2px 0; border-bottom: 1px dotted #ccc; }
        .total-box { margin-top: 4px; font-size: 9pt; }
        .total-line { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .total-destaque { font-size: 11pt; font-weight: bold; border-top: 1px solid #000; padding-top: 3px; }
        .footer-text { font-size: 6.5pt; color: #444; margin-top: 8px; text-align: center; }
        .no-print { background: #f8fafc; padding: 6px 10px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; width: 100%; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="no-print">
    <span style="font-size: 8.5pt;"><strong>Comprovante #<?php echo $id; ?></strong></span>
    <div>
        <button onclick="window.print()" style="padding: 3px 8px; font-weight: bold; background: #1d4ed8; color: #fff; border: 1px solid #1e40af; cursor: pointer; border-radius: 3px;">Imprimir</button>
        <button onclick="window.close()" style="padding: 3px 6px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer; border-radius: 3px; margin-left: 4px;">Fechar</button>
    </div>
</div>

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
        <?php if ($tipoVenda === 'desconto_folha' && !empty($colaboradorNome)): ?>
            <div style="margin-top: 2px; padding: 2px 4px; background: #f0f0f0; border: 1px solid #000;">
                <strong>DESCONTO EM FOLHA (RH)</strong><br>
                <?php echo htmlspecialchars($colaboradorNome); ?> (Matr: <?php echo htmlspecialchars($colaboradorMatricula ?? ''); ?>)
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
        <div class="total-line" style="margin-top: 3px; font-size: 7.5pt;">
            <span>Forma Pagamento:</span>
            <span><strong><?php echo $formaPagto; ?></strong></span>
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

</body>
</html>
