<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Espelho Oficial A4 de Fechamento de Caixa (Padrão Enterprise Anti-Slop V2)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrão oficial de relatórios A4
 * Padrão Excel Corporativo com divisórias pretas e fundo branco puro, banners azul marinho (#0f172a)
 * 
 * Contém as seções escolares:
 * 1. Duplicatas (Recargas de alunos/responsáveis)
 * 2. Vendas Cantina Web (Agendamentos Online)
 * 3. Vendas Presenciais no Balcão (PDV)
 * 4. Recebimento em Pix (Conferência Bancária Nubank)
 * 5. Resumo do Caixa (Box contábil de Fundo, Entradas, Saídas e Saldo)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
if (!isset($dadosFechamento) && isset($dados) && is_array($dados)) {
    $dadosFechamento = $dados;
}

$sessao = $dadosFechamento['sessao'] ?? ($sessao ?? []);
$duplicatas = $dadosFechamento['duplicatas'] ?? ($duplicatas ?? []);
$subtotalDuplicatas = (float)($dadosFechamento['subtotal_duplicatas'] ?? ($subtotalDuplicatas ?? 0.0));
$vendas = $dadosFechamento['vendas'] ?? ($vendas ?? []);
$vendasCantinaWeb = $dadosFechamento['vendas_cantina_web'] ?? [];
$vendasBalcao = $dadosFechamento['vendas_balcao'] ?? [];
$subtotalCantinaWeb = (float)($dadosFechamento['subtotal_cantina_web'] ?? 0.0);
$subtotalBalcao = (float)($dadosFechamento['subtotal_balcao'] ?? 0.0);
$subtotalVendas = (float)($dadosFechamento['subtotal_vendas'] ?? ($subtotalVendas ?? 0.0));

// Fallback defensivo para segregar vendas caso não fornecidas previamente separadas
if (empty($vendasCantinaWeb) && empty($vendasBalcao) && !empty($vendas)) {
    foreach ($vendas as $v) {
        $val = (float)($v['valor_pago'] ?? ($v['valor'] ?? 0));
        if (($v['origem'] ?? '') === 'online' || ($v['tipo'] ?? '') === 'Saldo') {
            $vendasCantinaWeb[] = $v;
            $subtotalCantinaWeb += $val;
        } else {
            $vendasBalcao[] = $v;
            $subtotalBalcao += $val;
        }
    }
}
if ($subtotalVendas <= 0.0) {
    $subtotalVendas = $subtotalCantinaWeb + $subtotalBalcao;
}

$pixRecebimentos = $dadosFechamento['pix_recebimentos'] ?? ($pixRecebimentos ?? []);
$totalPixRecebimentos = (float)($dadosFechamento['total_pix_recebimentos'] ?? ($totalPixRecebimentos ?? 0.0));
$resumoCaixa = $dadosFechamento['resumo_caixa'] ?? ($resumoCaixa ?? []);

$sessaoId = (int)($sessao['id'] ?? 0);
$controleStr = sprintf('%06d', $sessaoId);
$operadorNome = mb_strtoupper($sessao['operador_nome'] ?? 'OPERADOR');
$turnoNome = mb_strtoupper($sessao['turno'] ?? 'MATUTINO');
$operadorTurno = "{$operadorNome} - {$turnoNome}";

$abertoEm = !empty($sessao['aberto_em']) ? date('d/m/Y H:i', strtotime($sessao['aberto_em'])) : '-';
$fechadoEm = !empty($sessao['fechado_em']) ? date('d/m/Y H:i', strtotime($sessao['fechado_em'])) : 'Em aberto';
$fundoCaixa = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
$emissaoDataHora = date('d/m/Y H:i:s');
$isPdf = $isPdf ?? false;

// Totais do Resumo (exclusivamente dinheiro e Pix)
$fundo = $resumoCaixa['fundo'] ?? ['dinheiro' => $fundoCaixa, 'pix' => 0.0, 'total' => $fundoCaixa];
$entradas = $resumoCaixa['entradas'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$saidas = $resumoCaixa['saidas'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$saldo = $resumoCaixa['saldo'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa #<?php echo $controleStr; ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 14mm 14mm 14mm;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            font-weight: 700;
            color: #000000;
            background: #ffffff;
            margin: 0;
            padding: 0;
            line-height: 1.35;
            width: 100%;
        }

        .font-tabular {
            font-variant-numeric: tabular-nums;
            font-feature-settings: 'tnum' 1, 'zero' 1;
        }

        /* Barra de Ações para Navegador (Oculta na Impressão e no PDF) */
        .no-print {
            background-color: #1e293b;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            font-size: 11px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .no-print button, .no-print a {
            cursor: pointer;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.15s ease-in-out;
        }
        .btn-print-action {
            background: #1d4ed8;
            color: #ffffff !important;
            border: 1px solid #1e40af;
        }
        .btn-print-action:hover {
            background: #1e40af;
        }
        .btn-close-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            margin-left: 6px;
        }
        .btn-close-action:hover {
            background: #f1f5f9;
        }

        /* Visualização de Folha A4 em Tela */
        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 24px 0 40px 0;
            }
            .no-print {
                width: 210mm;
                max-width: calc(100% - 32px);
                margin: 0 auto 16px auto;
                position: sticky;
                top: 10px;
                z-index: 1000;
            }
            .no-print .zoom-controls {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                background: rgba(255, 255, 255, 0.12);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                padding: 2px 6px;
                margin-right: 8px;
            }
            .no-print .btn-zoom {
                background: transparent;
                color: #ffffff;
                border: none;
                width: 22px;
                height: 22px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                border-radius: 3px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                transition: all 0.15s ease;
            }
            .no-print .btn-zoom:hover {
                background: rgba(255, 255, 255, 0.25);
                color: #93c5fd;
            }
            .no-print .btn-zoom-val {
                background: transparent;
                color: #ffffff;
                border: none;
                font-size: 11px;
                font-weight: 700;
                cursor: pointer;
                padding: 2px 5px;
                border-radius: 3px;
                min-width: 40px;
                text-align: center;
                font-variant-numeric: tabular-nums;
                transition: all 0.15s ease;
            }
            .no-print .btn-zoom-val:hover {
                background: rgba(255, 255, 255, 0.25);
                text-decoration: underline;
            }
            .document-zoom-area {
                zoom: var(--doc-zoom, 1);
                transform-origin: top center;
                transition: zoom 0.1s ease-out;
            }
            .container {
                width: 210mm;
                max-width: calc(100% - 24px);
                min-height: 297mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 10mm 14mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        /* Impressão Direta (Física / Browser Print to PDF) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 14mm 14mm 14mm;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            html, body {
                width: 100% !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8pt;
                font-weight: 700 !important;
                color: #000000 !important;
            }
            body, table, th, td, div, span, p {
                font-weight: 700 !important;
                color: #000000 !important;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }

            /* Quebras de páginas limpas em tabelas oficiais */
            table.table-doc-excel {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
                page-break-inside: auto !important;
                break-inside: auto !important;
                margin-top: 4px !important;
                margin-bottom: 6px !important;
                border: 1px solid #000000 !important;
                border-bottom: none !important;
                border-right: none !important;
            }
            table.table-doc-excel thead {
                display: table-header-group !important;
            }
            table.table-doc-excel thead tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.table-doc-excel tfoot {
                display: table-footer-group !important;
            }
            table.table-doc-excel tbody tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            table.table-doc-excel th,
            table.table-doc-excel td {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                border-right: 1px solid #000000 !important;
                border-bottom: 1px solid #000000 !important;
            }
            .school-header,
            .param-bar,
            .resumo-box,
            .footer-assinaturas-box {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .school-header, .param-bar, .resumo-box, .footer-assinaturas-box { page-break-inside: avoid; break-inside: avoid; }

        /* Cabeçalho Oficial Escolar */
        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .school-logo-col {
            width: 52px;
            margin-right: 12px;
        }
        .school-logo-col img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #cbd5e1;
        }
        .school-title-col {
            flex: 1;
            line-height: 1.25;
        }
        .school-name {
            font-size: 10.5pt;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
        }
        .report-title-main {
            font-size: 9.5pt;
            font-weight: 700;
            color: #000000;
            margin-top: 2px;
        }
        .school-meta-col {
            text-align: right;
            font-size: 7.5pt;
            color: #334155;
            line-height: 1.3;
            padding-right: 2px;
        }

        /* Faixa de Parâmetros */
        .param-bar {
            font-size: 8pt;
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 4px 8px;
            margin-bottom: 8px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px;
        }

        /* Banners de Seção Escuros */
        .section-banner {
            font-weight: 700;
            font-size: 8pt;
            text-transform: uppercase;
            background: #0f172a;
            color: #ffffff;
            padding: 3.5px 8px;
            margin-top: 8px;
            margin-bottom: 2px;
            border-radius: 2px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Tabelas Analíticas Oficiais (Estética Excel com Bordas Nítidas) */
        table.table-doc-excel {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-top: 2px;
            margin-bottom: 6px;
            font-size: 7.8pt;
            font-weight: 700 !important;
            border: 1px solid #000000 !important;
        }
        table.table-doc-excel th,
        table.table-doc-excel td {
            border: 1px solid #000000 !important;
            padding: 2.5px 3.5px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: 700 !important;
            word-break: break-word;
        }
        table.table-doc-excel thead th {
            font-weight: 700 !important;
            text-align: center !important;
            font-size: 7.0pt;
            letter-spacing: -0.015em;
            text-transform: uppercase;
            background-color: #f8fafc !important;
            color: #000000 !important;
        }
        table.table-doc-excel tfoot td,
        table.table-doc-excel tr.group-subtotal td {
            font-weight: 700 !important;
            background-color: #f8fafc !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 2px solid #000000 !important;
            color: #000000 !important;
        }

        .text-start { text-align: left !important; }
        .text-center { text-align: center !important; }
        .text-end { text-align: center !important; }

        /* Tags Discretas de Origem em Preto/Sóbrio */
        .tag-origem {
            display: inline-block;
            font-size: 6.8pt;
            padding: 0.5px 4px;
            border-radius: 2px;
            margin-left: 3px;
            text-transform: uppercase;
            font-weight: 700;
            border: 1px solid #000000;
            background: #ffffff;
            color: #000000;
        }

        /* Box de Resumo do Caixa */
        .resumo-box {
            border: 1px solid #000000;
            margin-top: 4px;
            padding: 4px 6px;
            font-size: 7.8pt;
            font-weight: 700;
            background-color: #ffffff;
        }
        .resumo-table {
            width: 100%;
            border-collapse: collapse;
        }
        .resumo-table th {
            border-bottom: 1px solid #000000;
            padding: 3px 6px;
            font-weight: 700;
            font-size: 7.8pt;
            text-align: left;
            text-transform: uppercase;
            background-color: #f8fafc;
            color: #000000;
        }
        .resumo-table td {
            border: none;
            padding: 2.5px 6px;
            font-size: 7.8pt;
            font-weight: 700;
            color: #000000;
        }

        /* Assinaturas */
        .footer-assinaturas-box {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1.5px solid #000000;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 44%;
            border-top: 1.5px solid #000000;
            padding-top: 4px;
            text-align: center;
            font-size: 7.8pt;
            font-weight: 700 !important;
            line-height: 1.25;
            color: #000000;
        }

        .doc-footer-meta {
            margin-top: 10px;
            font-size: 7pt;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;">
            Fechamento de Caixa &bull; Sessão #<?php echo $controleStr; ?> &bull; Turno: <?php echo htmlspecialchars($turnoNome); ?>
        </div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">
            Operador: <?php echo htmlspecialchars($operadorNome, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
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

        <button type="button" class="btn-print-action" onclick="window.print()" title="Imprimir documento ou Salvar como PDF pelo navegador">
            🖨 Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-close-action" onclick="fecharJanela()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="container">

    <!-- CABEÇALHO INSTITUCIONAL TIMBRADO -->
    <div class="school-header">
        <?php if (!empty($logoDataUri)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoDataUri; ?>" alt="Logotipo Oficial">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="report-title-main">DEMONSTRATIVO DE FECHAMENTO DE CAIXA OPERACIONAL</div>
            <div style="font-size: 8pt; color: #334155;">Sistema de Gestão Cantina Web • Módulo Operacional & Prestação de Contas</div>
        </div>
        <div class="school-meta-col font-tabular">
            <div><strong>Emissão:</strong> <?php echo $emissaoDataHora; ?></div>
            <div><strong>Operador:</strong> <?php echo htmlspecialchars($operadorTurno, ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Autenticação:</strong> #<?php echo $controleStr; ?>-<?php echo strtoupper(substr(md5('CAIXA_' . $sessaoId . '_' . $emissaoDataHora), 0, 8)); ?></div>
        </div>
    </div>

    <!-- FAIXA DE PARÂMETROS E AUDITORIA DA SESSÃO -->
    <div class="param-bar font-tabular">
        <div>
            <strong>Controle:</strong> #<?php echo $controleStr; ?> &bull; 
            <strong>Operador:</strong> <?php echo htmlspecialchars($operadorNome, ENT_QUOTES, 'UTF-8'); ?> &bull; 
            <strong>Turno:</strong> <?php echo htmlspecialchars($turnoNome, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div>
            <strong>Abertura:</strong> <?php echo $abertoEm; ?> &bull; 
            <strong>Fechamento:</strong> <?php echo $fechadoEm; ?> &bull; 
            <strong>Fundo de Troco:</strong> R$ <?php echo number_format($fundoCaixa, 2, ',', '.'); ?>
        </div>
    </div>

    <!-- SEÇÃO 1: DUPLICATAS (RECARGAS DE ALUNOS/RESPONSÁVEIS) -->
    <div class="section-banner">
        <span>1. Duplicatas (Recargas de Alunos / Responsáveis)</span>
        <span class="font-tabular">Subtotal: R$ <?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></span>
    </div>
    <table class="table-doc-excel font-tabular">
        <thead>
            <tr>
                <th style="width: 9%;">Duplicata</th>
                <th style="width: 22%;" class="text-start">Aluno / Titular</th>
                <th style="width: 6%;" class="text-center">Ref.</th>
                <th style="width: 9%;" class="text-start">Finalidade</th>
                <th style="width: 8%;" class="text-center">Vencto</th>
                <th style="width: 8%;" class="text-end">Nominal</th>
                <th style="width: 6%;" class="text-end">Multa</th>
                <th style="width: 6%;" class="text-end">Juros</th>
                <th style="width: 6%;" class="text-end">Desc.</th>
                <th style="width: 12%;" class="text-end">Recebido</th>
                <th style="width: 8%;" class="text-center">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($duplicatas)): ?>
                <tr>
                    <td colspan="11" class="text-center" style="color: #000000; padding: 6px;">Nenhuma duplicata ou recarga registrada nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($duplicatas as $dup): ?>
                    <tr>
                        <td class="text-center"><?php echo htmlspecialchars($dup['numero'] ?? ''); ?></td>
                        <td class="text-start"><?php echo htmlspecialchars($dup['aluno'] ?? ''); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($dup['referencia'] ?? ''); ?></td>
                        <td class="text-start"><?php echo htmlspecialchars($dup['finalidade'] ?? 'CANTINA'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($dup['vencimento'] ?? ''); ?></td>
                        <td class="text-end"><?php echo number_format((float)($dup['valor_nominal'] ?? 0), 2, ',', '.'); ?></td>
                        <td class="text-end">0,00</td>
                        <td class="text-end">0,00</td>
                        <td class="text-end">0,00</td>
                        <td class="text-end"><strong><?php echo number_format((float)($dup['valor_recebido'] ?? 0), 2, ',', '.'); ?></strong></td>
                        <td class="text-center"><?php echo htmlspecialchars($dup['tipo'] ?? 'Dinheiro'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group-subtotal">
                    <td colspan="5" class="text-end">Subtotal Duplicatas:</td>
                    <td class="text-end"><?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></td>
                    <td class="text-end">0,00</td>
                    <td class="text-end">0,00</td>
                    <td class="text-end">0,00</td>
                    <td class="text-end"><?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 2: VENDAS CANTINA WEB (ONLINE) -->
    <div class="section-banner">
        <span>2. Vendas Cantina Web (Agendamentos Online)</span>
        <span class="font-tabular">Subtotal: R$ <?php echo number_format($subtotalCantinaWeb, 2, ',', '.'); ?></span>
    </div>
    <table class="table-doc-excel font-tabular">
        <thead>
            <tr>
                <th style="width: 32%;" class="text-start">Alimento / Produto</th>
                <th style="width: 24%;" class="text-start">Aluno</th>
                <th style="width: 14%;" class="text-end">Prc. Unit.</th>
                <th style="width: 7%;" class="text-center">Qtd</th>
                <th style="width: 14%;" class="text-end">Vlr. Pago</th>
                <th style="width: 9%;" class="text-center">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendasCantinaWeb)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #000000; padding: 6px;">Nenhuma venda online do Cantina Web registrada nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vendasCantinaWeb as $v): ?>
                    <tr>
                        <td class="text-start">
                            <?php echo htmlspecialchars($v['alimento'] ?? ($v['produto_descricao'] ?? '')); ?>
                            <span class="tag-origem">Online</span>
                        </td>
                        <td class="text-start"><?php echo htmlspecialchars($v['pessoa'] ?? ($v['cliente_ident'] ?? '')); ?></td>
                        <td class="text-end"><?php echo number_format((float)($v['preco_unitario'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo (int)($v['quantidade'] ?? 1); ?></td>
                        <td class="text-end"><strong><?php echo number_format((float)($v['valor_pago'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></strong></td>
                        <td class="text-center"><?php echo htmlspecialchars($v['tipo'] ?? 'Saldo'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group-subtotal">
                    <td colspan="4" class="text-end">Subtotal Cantina Web:</td>
                    <td class="text-end"><?php echo number_format($subtotalCantinaWeb, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 3: VENDAS PRESENCIAIS NO BALCÃO -->
    <div class="section-banner">
        <span>3. Vendas Presenciais no Balcão (PDV)</span>
        <span class="font-tabular">Subtotal: R$ <?php echo number_format($subtotalBalcao, 2, ',', '.'); ?></span>
    </div>
    <table class="table-doc-excel font-tabular">
        <thead>
            <tr>
                <th style="width: 32%;" class="text-start">Alimento / Produto</th>
                <th style="width: 24%;" class="text-start">Pessoa / Cliente</th>
                <th style="width: 14%;" class="text-end">Prc. Unit.</th>
                <th style="width: 7%;" class="text-center">Qtd</th>
                <th style="width: 14%;" class="text-end">Vlr. Pago</th>
                <th style="width: 9%;" class="text-center">Tipo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($vendasBalcao)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="color: #000000; padding: 6px;">Nenhuma venda presencial no balcão registrada nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($vendasBalcao as $v): ?>
                    <tr>
                        <td class="text-start">
                            <?php echo htmlspecialchars($v['alimento'] ?? ($v['produto_descricao'] ?? '')); ?>
                            <span class="tag-origem">Balcão</span>
                        </td>
                        <td class="text-start"><?php echo htmlspecialchars($v['pessoa'] ?? ($v['cliente_ident'] ?? 'Balcão')); ?></td>
                        <td class="text-end"><?php echo number_format((float)($v['preco_unitario'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                        <td class="text-center"><?php echo (int)($v['quantidade'] ?? 1); ?></td>
                        <td class="text-end"><strong><?php echo number_format((float)($v['valor_pago'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></strong></td>
                        <td class="text-center"><?php echo htmlspecialchars($v['tipo'] ?? 'Dinheiro'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group-subtotal">
                    <td colspan="4" class="text-end">Subtotal Balcão:</td>
                    <td class="text-end"><?php echo number_format($subtotalBalcao, 2, ',', '.'); ?></td>
                    <td></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 4: RECEBIMENTO EM PIX -->
    <div class="section-banner">
        <span>4. Recebimento em Pix (Conferência Bancária Nubank)</span>
        <span class="font-tabular">Subtotal: R$ <?php echo number_format($totalPixRecebimentos, 2, ',', '.'); ?></span>
    </div>
    <table class="table-doc-excel font-tabular">
        <thead>
            <tr>
                <th style="width: 22%;" class="text-start">Banco</th>
                <th style="width: 46%;" class="text-start">Descrição</th>
                <th style="width: 16%;" class="text-center">Observação</th>
                <th style="width: 16%;" class="text-end">Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pixRecebimentos)): ?>
                <tr>
                    <td colspan="4" class="text-center" style="color: #000000; padding: 6px;">Nenhum recebimento Pix conciliado nesta sessão.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pixRecebimentos as $px): ?>
                    <tr>
                        <td class="text-start"><?php echo htmlspecialchars($px['banco'] ?? 'NUBANK - CANTINA'); ?></td>
                        <td class="text-start"><?php echo htmlspecialchars($px['descricao'] ?? 'Credito transferência pix'); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($px['observacao'] ?? 'via pix'); ?></td>
                        <td class="text-end font-tabular"><strong><?php echo number_format((float)($px['valor'] ?? 0), 2, ',', '.'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="group-subtotal">
                    <td colspan="3" class="text-end">Subtotal Pix:</td>
                    <td class="text-end font-tabular"><?php echo number_format($totalPixRecebimentos, 2, ',', '.'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SEÇÃO 5: RESUMO DO CAIXA -->
    <div class="section-banner">
        <span>5. Resumo do Caixa — <?php echo htmlspecialchars($operadorTurno); ?></span>
        <span class="font-tabular">Saldo Total: R$ <?php echo number_format((float)($saldo['total'] ?? 0), 2, ',', '.'); ?></span>
    </div>
    <div class="resumo-box">
        <table class="resumo-table font-tabular">
            <thead>
                <tr>
                    <th style="width: 25%;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>(+) Fundo Caixa</span>
                            <span class="font-tabular"><?php echo number_format((float)($fundo['total'] ?? 0), 2, ',', '.'); ?></span>
                        </div>
                    </th>
                    <th style="width: 25%;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>(+) Entradas</span>
                            <span class="font-tabular"><?php echo number_format((float)($entradas['total'] ?? 0), 2, ',', '.'); ?></span>
                        </div>
                    </th>
                    <th style="width: 25%;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>(-) Saídas</span>
                            <span class="font-tabular"><?php echo number_format((float)($saidas['total'] ?? 0), 2, ',', '.'); ?></span>
                        </div>
                    </th>
                    <th style="width: 25%;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>(=) Saldo Caixa</span>
                            <span class="font-tabular"><?php echo number_format((float)($saldo['total'] ?? 0), 2, ',', '.'); ?></span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><div style="display: flex; justify-content: space-between;"><span>Em Dinheiro:</span> <span class="font-tabular"><?php echo number_format((float)($fundo['dinheiro'] ?? 0), 2, ',', '.'); ?></span></div></td>
                    <td><div style="display: flex; justify-content: space-between;"><span>Em Dinheiro:</span> <span class="font-tabular"><?php echo number_format((float)($entradas['dinheiro'] ?? 0), 2, ',', '.'); ?></span></div></td>
                    <td><div style="display: flex; justify-content: space-between;"><span>Em Dinheiro:</span> <span class="font-tabular"><?php echo number_format((float)($saidas['dinheiro'] ?? 0), 2, ',', '.'); ?></span></div></td>
                    <td><div style="display: flex; justify-content: space-between; font-weight: bold;"><span>Em Dinheiro:</span> <span class="font-tabular"><?php echo number_format((float)($saldo['dinheiro'] ?? 0), 2, ',', '.'); ?></span></div></td>
                </tr>
                <tr>
                    <td></td>
                    <td><div style="display: flex; justify-content: space-between;"><span>Em Pix:</span> <span class="font-tabular"><?php echo number_format((float)($entradas['pix'] ?? 0), 2, ',', '.'); ?></span></div></td>
                    <td><div style="display: flex; justify-content: space-between;"><span>Em Pix:</span> <span class="font-tabular"><?php echo number_format((float)($saidas['pix'] ?? 0), 2, ',', '.'); ?></span></div></td>
                    <td><div style="display: flex; justify-content: space-between; font-weight: bold;"><span>Em Pix:</span> <span class="font-tabular"><?php echo number_format((float)($saldo['pix'] ?? 0), 2, ',', '.'); ?></span></div></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ASSINATURAS DO FECHAMENTO -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong><?php echo htmlspecialchars($operadorNome); ?></strong><br>
            Operador(a) de Caixa Responsável
        </div>
        <div class="signature-line">
            <strong>Gerência / Supervisão da Cantina</strong><br>
            Visto de Conferência e Prestação de Contas
        </div>
    </div>

    <!-- RODAPÉ OFICIAL DA FOLHA -->
    <div class="doc-footer-meta">
        <div>Cantina Web &bull; Sistema Integrado de Gestão Escolar &bull; Prestação de Contas</div>
        <div>Página 1 de 1 &bull; Documento Oficial de Fechamento de Caixa A4</div>
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
