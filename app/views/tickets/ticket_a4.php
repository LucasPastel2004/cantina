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
            margin: 8mm;
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
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .a4-sheet {
                margin: 0 auto !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <div class="no-print-info">
        <span class="no-print-status-dot">●</span>
        <strong>Grade de Fichas (Folha A4)</strong> &bull; 
        <span><?php echo $totalTickets; ?> ticket(s)</span> &bull; 
        <span><?php echo count($chunks); ?> folha(s) A4</span> &bull; 
        <span style="color: #64748b;"><?php echo htmlspecialchars($dataConsumoFmt); ?> (<?php echo htmlspecialchars($turnoFmt); ?>)</span>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button onclick="window.print()" class="btn-print" title="Disparar impressão em Folha A4">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir Folha A4
        </button>

        <?php
            $queryPdf = $_GET;
            $queryPdf['formato'] = 'a4';
            $urlPdf = 'pdf?' . http_build_query($queryPdf);

            $query80mm = $_GET;
            $query80mm['formato'] = '80mm';
            $url80mm = '?' . http_build_query($query80mm);
        ?>
        <a href="<?php echo htmlspecialchars($urlPdf); ?>" class="btn-action-neutral" title="Baixar grade de tickets em PDF A4">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg>
            PDF A4
        </a>

        <a href="<?php echo htmlspecialchars($url80mm); ?>" class="btn-action-neutral" title="Alternar para visualização em bobina térmica de 80mm">
            Alternar para Bobina 80mm
        </a>

        <button onclick="window.close()" class="btn-action-neutral">
            Fechar
        </button>
    </div>
</div>

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

</body>
</html>
