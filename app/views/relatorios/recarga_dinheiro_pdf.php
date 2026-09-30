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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Recarga #<?php echo $transacaoId; ?> — Cantina Web</title>
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
        .line { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .highlight { font-size: 11pt; font-weight: bold; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 4px 0; }
        .footer-text { font-size: 6.5pt; color: #444; margin-top: 8px; text-align: center; }
        .no-print { background: #f8fafc; padding: 6px 10px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; width: 100%; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="no-print">
    <span style="font-size: 8.5pt;"><strong>Recarga #<?php echo $transacaoId; ?></strong></span>
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

</body>
</html>
