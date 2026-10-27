<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Timbrado — Entradas (Cash-In)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrões oficiais de relatórios A4
 * Grade estilo Excel com divisórias pretas e fundo 100% branco
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
if (!isset($dados) || !is_array($dados)) {
    $dados = get_defined_vars();
}
$periodoOficial = $dados['periodo_oficial'] ?? $periodo_oficial ?? (date('d/m/Y') . ' a ' . date('d/m/Y'));
$dataInicio = $dados['data_inicio'] ?? $data_inicio ?? date('Y-m-d');
$dataFim = $dados['data_fim'] ?? $data_fim ?? date('Y-m-d');
$canais = $dados['canais'] ?? $canais ?? [];
$dias = $dados['dias'] ?? $dias ?? [];
$isPdf = $isPdf ?? false;

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Demonstrativo Financeiro - Canais de Entrada — Colégio Monteiro Lobato</title>
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
            color: #000000;
            margin: 0;
            padding: 0;
            font-size: 8.5pt;
            line-height: 1.35;
            background: #ffffff;
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
        .btn-back-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            margin-right: 6px;
        }
        .btn-back-action:hover {
            background: #f1f5f9;
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
                margin-bottom: 8px !important;
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
            .resumo-cards-box,
            .footer-assinaturas-box,
            .row-dia-total,
            .row-geral-total {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .school-header, .resumo-cards-box, .footer-assinaturas-box { page-break-inside: avoid; break-inside: avoid; }

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
            justify-content: space-between;
        }

        /* Tabela Estilo Excel Oficial */
        table.table-doc-excel {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-top: 4px;
            margin-bottom: 8px;
            font-size: 7.8pt;
            border: 1px solid #000000 !important;
        }
        table.table-doc-excel th,
        table.table-doc-excel td {
            border: 1px solid #000000 !important;
            padding: 3px 4px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            word-break: break-word;
        }
        table.table-doc-excel thead th {
            font-weight: 700 !important;
            text-align: center;
            font-size: 7.2pt;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            background-color: #f8fafc !important;
        }
        table.table-doc-excel tfoot td {
            font-weight: 700 !important;
            background-color: #f8fafc !important;
            border-top: 2px solid #000000 !important;
        }

        .text-start { text-align: left !important; }
        .text-center { text-align: center !important; }
        .text-end { text-align: right !important; }

        /* Banners de Seção */
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
        }

        /* Assinaturas */
        .footer-assinaturas-box {
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid #000000;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 44%;
            border-top: 1px solid #000000;
            padding-top: 4px;
            text-align: center;
            font-size: 7.5pt;
            line-height: 1.25;
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
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;">Cantina Web — Demonstrativo Financeiro - Canais de Entrada</div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">Período: <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
        <!-- Controles de Zoom para Conferência -->
        <div class="zoom-controls" title="Ajuste o zoom para conferir detalhes antes de imprimir ou salvar">
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
        <button type="button" class="btn-close-action" onclick="window.close()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="container">

    <!-- Cabeçalho Oficial Escolar -->
    <div class="school-header">
        <?php if (!empty($logoDataUri)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoDataUri; ?>" alt="Logotipo Oficial">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name">COLÉGIO MONTEIRO LOBATO</div>
            <div class="report-title-main">DEMONSTRATIVO FINANCEIRO - CANAIS DE ENTRADA</div>
            <div style="font-size: 8pt; color: #334155;">Sistema de Gestão Cantina Web • Módulo Financeiro & Fluxo de Caixa</div>
        </div>
        <div class="school-meta-col">
            <div><strong>Emissão:</strong> <?php echo date('d/m/Y H:i'); ?></div>
            <div><strong>Unidade:</strong> Cantina Geral</div>
            <div><strong>Documento:</strong> A4 Oficial</div>
        </div>
    </div>

    <!-- Faixa de Parâmetros e Filtros -->
    <div class="param-bar">
        <div><strong>Período Apurado:</strong> <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></div>
        <div><strong>Critério:</strong> Entradas Financeiras Reais (Cash-In) & Conciliação de Carteira</div>
    </div>

    <!-- SEÇÃO 1: SÍNTESE DOS INDICADORES FINANCEIROS DO PERÍODO -->
    <div class="section-banner">
        1. Síntese dos Indicadores Financeiros do Período
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 30%; text-align: left;">Indicador Financeiro</th>
                <th style="width: 40%; text-align: left;">Detalhamento / Discriminação</th>
                <th style="width: 14%; text-align: right;">Movimentações</th>
                <th style="width: 16%; text-align: right;">Total Consolidado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start">
                    <strong>Total Arrecadado (Cash-In Real)</strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    Total de dinheiro novo que ingressou no caixa físico e na conta bancária no período
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)($dados['total_transacoes_arrecadacao'] ?? 0), 0, ',', '.'); ?> entradas
                </td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    <strong>R$ <?php echo number_format((float)($dados['total_arrecadado_bruto'] ?? 0), 2, ',', '.'); ?></strong>
                </td>
            </tr>
            <tr>
                <td class="text-start">
                    <strong>Recargas Pix Online (Portal da Família)</strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    Recargas de carteira efetuadas pelas famílias via QR Code dinâmico no Portal
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)($dados['recargas_pix_qtd'] ?? 0), 0, ',', '.'); ?> recargas
                </td>
                <td class="text-end font-tabular">
                    R$ <?php echo number_format((float)($dados['recargas_pix_total'] ?? 0), 2, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="text-start">
                    <strong>Recargas Balcão (Dinheiro Físico)</strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    Inserções manuais de saldo na carteira familiar pagas em espécie diretamente na cantina
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)($dados['recargas_dinheiro_qtd'] ?? 0), 0, ',', '.'); ?> inserções
                </td>
                <td class="text-end font-tabular">
                    R$ <?php echo number_format((float)($dados['recargas_dinheiro_total'] ?? 0), 2, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="text-start">
                    <strong>Vendas Presenciais Pix</strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    Vendas diretas no PDV balcão pagas via QR Code / Pix
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)($dados['vendas_pix_qtd'] ?? 0), 0, ',', '.'); ?> vendas
                </td>
                <td class="text-end font-tabular">
                    R$ <?php echo number_format((float)($dados['vendas_pix_total'] ?? 0), 2, ',', '.'); ?>
                </td>
            </tr>
            <tr>
                <td class="text-start">
                    <strong>Vendas Presenciais Dinheiro</strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    Vendas diretas no PDV balcão pagas em espécie (dinheiro físico)
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)($dados['vendas_dinheiro_qtd'] ?? 0), 0, ',', '.'); ?> vendas
                </td>
                <td class="text-end font-tabular">
                    R$ <?php echo number_format((float)($dados['vendas_dinheiro_total'] ?? 0), 2, ',', '.'); ?>
                </td>
            </tr>
            <tr style="background-color: #f8fafc !important;">
                <td class="text-start">
                    <strong>Consumo de Saldo (Lanches no Período)</strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    Total de lanches entregues debitados de carteiras (Variação líquida: <strong><?php echo (($dados['variacao_carteira'] ?? 0) >= 0 ? '+' : '') . 'R$ ' . number_format((float)($dados['variacao_carteira'] ?? 0), 2, ',', '.'); ?></strong>)
                </td>
                <td class="text-end font-tabular" style="color: #64748b;">
                    -
                </td>
                <td class="text-end font-tabular">
                    R$ <?php echo number_format((float)($dados['total_consumo_saldo'] ?? 0), 2, ',', '.'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- SEÇÃO 2: RESUMO POR CANAIS DE ENTRADAS -->
    <div class="section-banner">
        2. Resumo por Canais de Entrada Financeira (Cash-In Real)
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 30%; text-align: left;">Canal / Meio de Pagamento</th>
                <th style="width: 27%; text-align: left;">Origem / Ambiente</th>
                <th style="width: 12%; text-align: right;">Transações</th>
                <th style="width: 16%; text-align: right;">Total de Entradas</th>
                <th style="width: 15%; text-align: right;">Participação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($canais as $c): ?>
            <tr>
                <td class="text-start">
                    <strong><?php echo htmlspecialchars($c['canal'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </td>
                <td class="text-start" style="font-size: 7.2pt; color: #334155;">
                    <?php echo htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)$c['quantidade'], 0, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular">
                    <strong>R$ <?php echo number_format((float)$c['total'], 2, ',', '.'); ?></strong>
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((float)$c['percentual'], 1, ',', '.'); ?>%
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-start text-uppercase">
                    TOTAL GERAL DE ENTRADAS (CASH-IN LÍQUIDO)
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)($dados['total_transacoes_arrecadacao'] ?? 0), 0, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    R$ <?php echo number_format((float)($dados['total_arrecadado_bruto'] ?? 0), 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular">
                    100.0%
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- SEÇÃO 3: DEMONSTRATIVO DE CONTROLE DA CARTEIRA DIGITAL -->
    <div class="section-banner">
        3. Demonstrativo de Controle e Conciliação da Carteira Digital
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 70%; text-align: left;">Item de Conciliação Contábil</th>
                <th style="width: 30%; text-align: right;">Valor Consolidado (R$)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-start">(+) Recargas Pix no Portal da Família (Entrada via Banco)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)($dados['recargas_pix_total'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td class="text-start">(+) Recargas no Balcão em Dinheiro (Entrada Física na Gaveta)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)($dados['recargas_dinheiro_total'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr style="font-weight: 700; background-color: #f8fafc !important;">
                <td class="text-start">(=) Total de Recargas Adicionadas na Carteira no Período</td>
                <td class="text-end font-tabular"><strong>R$ <?php echo number_format((float)($dados['total_recargas'] ?? 0), 2, ',', '.'); ?></strong></td>
            </tr>
            <tr>
                <td class="text-start">(-) Lanches Consumidos em Saldo no Balcão (PDV Presencial)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)($dados['vendas_saldo_total'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td class="text-start">(-) Lanches Consumidos em Pedidos Online (Agendamentos da Semana)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)($dados['pedidos_saldo_total'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr style="font-weight: 700; background-color: #f8fafc !important;">
                <td class="text-start">(=) Total de Lanches Entregues com Saldo (Consumo Real)</td>
                <td class="text-end font-tabular"><strong>R$ <?php echo number_format((float)($dados['total_consumo_saldo'] ?? 0), 2, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="text-start text-uppercase">
                    (=) Variação Líquida de Saldo em Custódia (Recargas - Consumo)
                </td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    <?php echo (($dados['variacao_carteira'] ?? 0) >= 0 ? '+' : '') . 'R$ ' . number_format((float)($dados['variacao_carteira'] ?? 0), 2, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- SEÇÃO 4: DETALHAMENTO DIÁRIO -->
    <div class="section-banner">
        4. Detalhamento Cronológico Diário de Entradas e Consumo
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 11%; text-align: center;">Data</th>
                <th style="width: 10%; text-align: left;">Dia</th>
                <th style="width: 13%; text-align: right;">Recarga Pix</th>
                <th style="width: 12%; text-align: right;">Recarga Din</th>
                <th style="width: 12%; text-align: right;">Venda Din</th>
                <th style="width: 12%; text-align: right;">Venda Pix</th>
                <th style="width: 15%; text-align: right; background-color: #f1f5f9 !important;">Entradas do Dia</th>
                <th style="width: 15%; text-align: right;">Consumo Saldo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($dias)): ?>
            <tr>
                <td colspan="8" class="text-center" style="padding: 10px;">Sem movimentações registradas no período.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($dias as $d): ?>
                <tr>
                    <td class="text-center font-tabular"><?php echo htmlspecialchars($d['data_formatada'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-start" style="font-size: 7.2pt;"><?php echo htmlspecialchars($d['dia_semana'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="text-end font-tabular"><?php echo $d['recargas_pix'] > 0 ? number_format((float)$d['recargas_pix'], 2, ',', '.') : '-'; ?></td>
                    <td class="text-end font-tabular"><?php echo $d['recargas_dinheiro'] > 0 ? number_format((float)$d['recargas_dinheiro'], 2, ',', '.') : '-'; ?></td>
                    <td class="text-end font-tabular"><?php echo $d['vendas_dinheiro'] > 0 ? number_format((float)$d['vendas_dinheiro'], 2, ',', '.') : '-'; ?></td>
                    <td class="text-end font-tabular"><?php echo $d['vendas_pix'] > 0 ? number_format((float)$d['vendas_pix'], 2, ',', '.') : '-'; ?></td>
                    <td class="text-end font-tabular" style="font-weight: 700; background-color: #f8fafc !important;">
                        <?php echo number_format((float)$d['total_arrecadado'], 2, ',', '.'); ?>
                    </td>
                    <td class="text-end font-tabular"><?php echo $d['consumo_saldo'] > 0 ? number_format((float)$d['consumo_saldo'], 2, ',', '.') : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-start text-uppercase">TOTAIS DO PERÍODO</td>
                <td class="text-end font-tabular"><?php echo number_format((float)($dados['recargas_pix_total'] ?? 0), 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format((float)($dados['recargas_dinheiro_total'] ?? 0), 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format((float)($dados['vendas_dinheiro_total'] ?? 0), 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format((float)($dados['vendas_pix_total'] ?? 0), 2, ',', '.'); ?></td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    R$ <?php echo number_format((float)($dados['total_arrecadado_bruto'] ?? 0), 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular"><?php echo number_format((float)($dados['total_consumo_saldo'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Assinaturas Formais -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong>Operador / Gerente Financeiro</strong><br>
            Cantina Web • Colégio Monteiro Lobato
        </div>
        <div class="signature-line">
            <strong>Direção Administrativa / Financeira</strong><br>
            Colégio Monteiro Lobato
        </div>
    </div>

    <!-- Rodapé Documental -->
    <div class="doc-footer-meta">
        <div>Colégio Monteiro Lobato • Sistema Integrado Cantina Web • Documento Contábil Autenticado</div>
        <div>Emissão: <?php echo date('d/m/Y H:i:s'); ?></div>
</div>
</div> <!-- /#document-zoom-area -->

<?php if (!$isPdf): ?>
<script>
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
</script>
<?php endif; ?>
</body>
</html>
