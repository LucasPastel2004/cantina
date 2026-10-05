<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Timbrado — Arrecadação Financeira & Fluxo de Entradas (Cash-In)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrões oficiais de relatórios A4
 * Grade estilo Excel com divisórias pretas e fundo 100% branco
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$periodoOficial = $dados['periodo_oficial'] ?? (date('d/m/Y') . ' a ' . date('d/m/Y'));
$dataInicio = $dados['data_inicio'] ?? date('Y-m-d');
$dataFim = $dados['data_fim'] ?? date('Y-m-d');
$canais = $dados['canais'] ?? [];
$dias = $dados['dias'] ?? [];
$isPdf = $isPdf ?? false;

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Arrecadação Financeira — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
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
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            border-radius: 6px;
        }
        .no-print button, .no-print a {
            cursor: pointer;
            padding: 6px 14px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
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
            color: #1d4ed8 !important;
            border: 1px solid #cbd5e1;
            margin-right: 6px;
        }
        .btn-back-action:hover {
            background: #f1f5f9;
        }
        .btn-close-action {
            background: #ffffff;
            color: #475569 !important;
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
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            }
            .container {
                width: 210mm;
                max-width: calc(100% - 24px);
                min-height: 297mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 10mm 12mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        /* Impressão Direta (Física / Browser Print / Gotenberg PDF) */
        @media print {
            html, body {
                width: 100% !important;
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-size: 8pt;
            }
            .no-print {
                display: none !important;
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
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
            padding: 3.5px 5px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            word-break: break-word;
        }
        table.table-doc-excel thead th {
            font-weight: 700 !important;
            text-align: center;
            font-size: 7.5pt;
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
    <div>
        <span style="font-weight: 700; font-size: 12px; color: #0f172a;">Cantina Web — Relatório Oficial de Arrecadação</span>
        <span style="color: #64748b; margin-left: 8px;">Período: <?php echo htmlspecialchars($periodoOficial, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>
    <div>
        <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" class="btn-back-action">
            ← Voltar aos Filtros
        </a>
        <button type="button" class="btn-print-action" onclick="window.print()">
            🖨 Imprimir Documento
        </button>
        <button type="button" class="btn-close-action" onclick="window.close()">
            ✕ Fechar Janela
        </button>
    </div>
</div>
<?php endif; ?>

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
            <div class="report-title-main">DEMONSTRATIVO DE ARRECADAÇÃO FINANCEIRA & FLUXO DE ENTRADAS (CASH-IN)</div>
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

    <!-- SEÇÃO 1: RESUMO POR CANAIS DE ARRECADAÇÃO -->
    <div class="section-banner">
        1. Resumo por Canais de Entrada Financeira (Cash-In Real)
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 32%; text-align: left;">Canal / Meio de Pagamento</th>
                <th style="width: 28%; text-align: left;">Origem / Ambiente</th>
                <th style="width: 12%; text-align: right;">Transações</th>
                <th style="width: 16%; text-align: right;">Total Arrecadado</th>
                <th style="width: 12%; text-align: right;">Participação</th>
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
                    TOTAL GERAL ARRECADADO (CASH-IN LÍQUIDO)
                </td>
                <td class="text-end font-tabular">
                    <?php echo number_format((int)$dados['total_transacoes_arrecadacao'], 0, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    R$ <?php echo number_format((float)$dados['total_arrecadado_bruto'], 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular">
                    100.0%
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- SEÇÃO 2: DEMONSTRATIVO DE CONTROLE DA CARTEIRA DIGITAL -->
    <div class="section-banner">
        2. Demonstrativo de Controle e Conciliação da Carteira Digital
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
                <td class="text-end font-tabular">R$ <?php echo number_format((float)$dados['recargas_pix_total'], 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td class="text-start">(+) Recargas no Balcão em Dinheiro (Entrada Física na Gaveta)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)$dados['recargas_dinheiro_total'], 2, ',', '.'); ?></td>
            </tr>
            <tr style="font-weight: 700; background-color: #f8fafc !important;">
                <td class="text-start">(=) Total de Recargas Adicionadas na Carteira no Período</td>
                <td class="text-end font-tabular"><strong>R$ <?php echo number_format((float)$dados['total_recargas'], 2, ',', '.'); ?></strong></td>
            </tr>
            <tr>
                <td class="text-start">(-) Lanches Consumidos em Saldo no Balcão (PDV Presencial)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)$dados['vendas_saldo_total'], 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td class="text-start">(-) Lanches Consumidos em Pedidos Online (Agendamentos da Semana)</td>
                <td class="text-end font-tabular">R$ <?php echo number_format((float)$dados['pedidos_saldo_total'], 2, ',', '.'); ?></td>
            </tr>
            <tr style="font-weight: 700; background-color: #f8fafc !important;">
                <td class="text-start">(=) Total de Lanches Entregues com Saldo (Consumo Real)</td>
                <td class="text-end font-tabular"><strong>R$ <?php echo number_format((float)$dados['total_consumo_saldo'], 2, ',', '.'); ?></strong></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="text-start text-uppercase">
                    (=) Variação Líquida de Saldo em Custódia (Recargas - Consumo)
                </td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    <?php echo ($dados['variacao_carteira'] >= 0 ? '+' : '') . 'R$ ' . number_format((float)$dados['variacao_carteira'], 2, ',', '.'); ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- SEÇÃO 3: DETALHAMENTO DIÁRIO -->
    <div class="section-banner">
        3. Detalhamento Cronológico Diário de Arrecadação e Consumo
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 12%; text-align: center;">Data</th>
                <th style="width: 11%; text-align: left;">Dia</th>
                <th style="width: 13%; text-align: right;">Recarga Pix</th>
                <th style="width: 12%; text-align: right;">Recarga Din</th>
                <th style="width: 12%; text-align: right;">Venda Din</th>
                <th style="width: 12%; text-align: right;">Venda Pix</th>
                <th style="width: 14%; text-align: right; background-color: #f1f5f9 !important;">Arrecadado Dia</th>
                <th style="width: 14%; text-align: right;">Consumo Saldo</th>
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
                <td class="text-end font-tabular"><?php echo number_format((float)$dados['recargas_pix_total'], 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format((float)$dados['recargas_dinheiro_total'], 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format((float)$dados['vendas_dinheiro_total'], 2, ',', '.'); ?></td>
                <td class="text-end font-tabular"><?php echo number_format((float)$dados['vendas_pix_total'], 2, ',', '.'); ?></td>
                <td class="text-end font-tabular" style="font-size: 8.5pt;">
                    R$ <?php echo number_format((float)$dados['total_arrecadado_bruto'], 2, ',', '.'); ?>
                </td>
                <td class="text-end font-tabular"><?php echo number_format((float)$dados['total_consumo_saldo'], 2, ',', '.'); ?></td>
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

</div>

</body>
</html>
