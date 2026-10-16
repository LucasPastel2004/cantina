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
            color: #000000;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        /* Barra de Controle no Topo (Visível apenas na tela) */
        .no-print {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #ffffff;
            padding: 10px 24px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            width: 100%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            font-size: 9pt;
        }
        .no-print-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0f172a;
        }
        .no-print-status-dot {
            color: #2563eb;
            font-size: 14px;
        }
        .btn-print {
            padding: 6px 14px;
            font-weight: bold;
            background: #1d4ed8;
            color: #ffffff;
            border: 1px solid #1e40af;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 9pt;
        }
        .btn-print:hover {
            background: #1e40af;
        }
        .btn-action-neutral {
            padding: 6px 12px;
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            font-size: 9pt;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-action-neutral:hover {
            background: #f8fafc;
            border-color: #94a3b8;
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
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .ticket-sub {
            font-size: 7pt;
            color: #333333;
        }
        .divider {
            border-top: 1px dashed #000000;
            margin: 3px 0;
        }
        .ticket-code {
            font-size: 8.5pt;
            font-weight: bold;
            text-align: center;
            font-family: monospace;
            letter-spacing: 0.5px;
        }
        .via-badge {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 1px 4px;
            border: 1px solid #000000;
            border-radius: 2px;
            text-transform: uppercase;
            margin-left: 4px;
        }
        .student-box {
            margin: 3px 0;
            padding: 2px 0;
        }
        .student-label {
            font-size: 6.5pt;
            text-transform: uppercase;
            color: #444444;
        }
        .student-name {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .student-turma {
            font-size: 8pt;
            font-weight: bold;
            color: #222222;
        }
        .product-box {
            background: #f4f4f4;
            border: 1px solid #000000;
            padding: 4px;
            margin: 4px 0;
            text-align: center;
        }
        .product-name {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }
        .product-qty {
            font-size: 8.5pt;
            font-weight: bold;
            margin-top: 2px;
        }
        .alergeno-box {
            border: 1.5px solid #000000;
            padding: 2px 3px;
            margin: 3px 0;
            text-align: center;
            font-size: 7pt;
            font-weight: bold;
            text-transform: uppercase;
            background: #fff;
        }
        .footer-info {
            text-align: center;
            font-size: 6.5pt;
            color: #555555;
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
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                width: 80mm !important;
                margin: 0 !important;
                padding: 0 !important;
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
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <div class="no-print-info">
        <span class="no-print-status-dot">●</span>
        <strong>Bobina Térmica (80mm)</strong> &bull; 
        <span><?php echo $totalTickets; ?> ticket(s)</span> &bull; 
        <span style="color: #64748b;"><?php echo htmlspecialchars($dataConsumoFmt); ?> (<?php echo htmlspecialchars($turnoFmt); ?>)</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button onclick="window.print()" class="btn-print" title="Imprimir em impressora térmica ou Salvar como PDF">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir ou Salvar em PDF
        </button>

        <?php
            $queryA4 = $_GET;
            $queryA4['formato'] = 'a4';
            $urlA4 = '?' . http_build_query($queryA4);
        ?>
        <a href="<?php echo htmlspecialchars($urlA4); ?>" class="btn-action-neutral" title="Alternar para visualização em grade de Folha A4">
            Alternar para Folha A4
        </a>

        <button onclick="window.close()" class="btn-action-neutral" title="Fechar visualização">
            Fechar
        </button>
    </div>
</div>

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

</body>
</html>
