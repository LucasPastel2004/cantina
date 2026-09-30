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
            font-size: 9pt;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
            width: 80mm;
        }
        .ticket-wrapper {
            width: 72mm;
            margin: 0 auto;
            padding: 4mm 2mm 6mm 2mm;
            page-break-after: always;
            border-bottom: 1px dashed #000000;
        }
        .ticket-wrapper:last-child {
            border-bottom: none;
            page-break-after: auto;
        }
        .header-center {
            text-align: center;
            margin-bottom: 4px;
        }
        .logo-img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: block;
            margin: 0 auto 3px auto;
        }
        .school-name {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .ticket-sub {
            font-size: 7.5pt;
            color: #333333;
        }
        .divider {
            border-top: 1px dashed #000000;
            margin: 4px 0;
        }
        .ticket-code {
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            font-family: monospace;
            letter-spacing: 0.5px;
        }
        .via-badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 4px;
            border: 1px solid #000000;
            border-radius: 2px;
            text-transform: uppercase;
            margin-left: 4px;
        }
        .student-box {
            margin: 4px 0;
            padding: 3px 0;
        }
        .student-label {
            font-size: 7pt;
            text-transform: uppercase;
            color: #444444;
        }
        .student-name {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .student-turma {
            font-size: 8.5pt;
            font-weight: bold;
            color: #222222;
        }
        .product-box {
            background: #f4f4f4;
            border: 1px solid #000000;
            padding: 5px;
            margin: 5px 0;
            text-align: center;
        }
        .product-name {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .product-qty {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .alergeno-box {
            border: 1.5px solid #000000;
            padding: 3px;
            margin: 4px 0;
            text-align: center;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            background: #fff;
        }
        .footer-info {
            text-align: center;
            font-size: 6.5pt;
            color: #555555;
            margin-top: 4px;
        }
        .no-print {
            background: #f8fafc;
            padding: 8px 12px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <span style="font-size: 9pt;"><strong>Tickets Térmicos (80mm)</strong> — <?php echo count($tickets); ?> item(ns)</span>
    <div>
        <button onclick="window.print()" style="padding: 4px 10px; font-weight: bold; background: #1d4ed8; color: #fff; border: 1px solid #1e40af; cursor: pointer; border-radius: 3px;">
            Imprimir (80mm)
        </button>
        <button onclick="window.close()" style="padding: 4px 8px; background: #fff; border: 1px solid #cbd5e1; cursor: pointer; border-radius: 3px; margin-left: 4px;">
            Fechar
        </button>
    </div>
</div>

<div class="receipt-roll">
<?php foreach ($tickets as $t): ?>
    <?php
        $codigoTicket = $t['codigo_ticket'] ?? ('TCK-' . ($t['id'] ?? 0));
        $alunoNome = $t['aluno_nome'] ?? 'Aluno';
        $turma = $t['nome_turma'] ?? ($t['serie'] ?? 'Turma Regular');
        $produto = $t['produto_descricao'] ?? 'Lanche do Dia';
        $qtd = (int)($t['item_quantidade'] ?? 1);
        $dataConsumo = !empty($t['data_consumo']) ? date('d/m/Y', strtotime($t['data_consumo'])) : date('d/m/Y');
        $turno = ucfirst($t['turno_consumo'] ?? 'matutino');
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

        <div style="text-align: center; font-size: 7pt; margin-top: 6px; color: #444;">
            ✂ - - - - - - - - - - - - - - - - - - - -
        </div>
    </div>
<?php endforeach; ?>
</div>

</body>
</html>
