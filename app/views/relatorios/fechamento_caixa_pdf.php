<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Espelho Oficial A4 de Fechamento de Caixa (Padrão Enterprise Anti-Slop V2)
 * 
 * Conforme docs/alterações sistema guideline/28 setembro matutino.pdf e 28 setembro vespertino.pdf,
 * docs/DESIGN.md e motor de zoom/impressão oficial do sistema.
 * 
 * Contém as 4 seções escolares:
 * 1. Duplicatas (Recargas de alunos/responsáveis)
 * 2. Vendas (Balcão e Online com segregação e colaboradores unificados)
 * 3. Recebimento em Pix (Conferência bancária Nubank)
 * 4. Resumo do Caixa (Box contábil de Fundo, Entradas, Saídas e Saldo)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

if (!isset($baseUrl)) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $base = str_replace('\\', '/', $scriptDir);
    $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
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

// Totais do Resumo (exclusivamente dinheiro e Pix)
$fundo = $resumoCaixa['fundo'] ?? ['dinheiro' => $fundoCaixa, 'pix' => 0.0, 'total' => $fundoCaixa];
$entradas = $resumoCaixa['entradas'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$saidas = $resumoCaixa['saidas'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$saldo = $resumoCaixa['saldo'] ?? ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0];
$logoDataUri = \App\Services\PdfService::getLogoDataUri(true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Fechamento de Caixa #<?php echo $controleStr; ?> — Colégio Monteiro Lobato</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #0f172a;
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

        /* Barra de Controle Superior Padronizada (#1e293b) */
        .no-print-bar {
            background-color: #1e293b;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            width: 200mm;
            max-width: calc(100% - 32px);
            margin: 0 auto 16px auto;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            position: sticky;
            top: 10px;
            z-index: 1000;
        }
        .no-print-bar .zoom-controls {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            padding: 2px 6px;
            flex-shrink: 0;
            margin: 0 4px;
        }
        .no-print-bar .btn-zoom {
            background: transparent;
            color: #ffffff;
            border: none;
            width: 24px;
            height: 24px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: all 0.15s ease;
        }
        .no-print-bar .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .no-print-bar .btn-zoom-val {
            background: transparent;
            color: #ffffff;
            border: none;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            padding: 2px 6px;
            border-radius: 3px;
            font-variant-numeric: tabular-nums;
            min-width: 44px;
            text-align: center;
            transition: all 0.15s ease;
        }
        .no-print-bar .btn-zoom-val:hover {
            background: rgba(255, 255, 255, 0.2);
            text-decoration: underline;
        }
        .no-print-bar .btn-action {
            background: #ffffff;
            color: #0f172a;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease-in-out;
        }
        .no-print-bar .btn-action:hover {
            background: #f1f5f9;
            color: #1d4ed8;
        }
        .no-print-bar .btn-primary {
            background: #1d4ed8;
            color: #ffffff;
            border: 1px solid #1e40af;
        }
        .no-print-bar .btn-primary:hover {
            background: #1e40af;
            color: #ffffff;
        }

        /* Área de Zoom Dinâmico das Páginas A4 */
        .document-zoom-area {
            zoom: var(--doc-zoom, 1);
            transform-origin: top center;
            transition: zoom 0.1s ease-out;
        }

        @media screen {
            body {
                background-color: #cbd5e1;
                padding: 20px 10px 40px 10px;
            }
            .sheet-container.page-sheet, .container {
                width: 200mm;
                max-width: calc(100% - 24px);
                min-height: 280mm;
                margin: 0 auto;
                background: #ffffff;
                padding: 10mm 14mm;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
                box-sizing: border-box;
                border-radius: 2px;
                position: relative;
            }
        }

        @media print {
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .no-print-bar, .no-print {
                display: none !important;
            }
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
            .sheet-container.page-sheet, .container {
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 auto !important;
                padding: 10mm 14mm !important;
                box-shadow: none !important;
                border: none !important;
                background: #ffffff !important;
                box-sizing: border-box !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .doc-header, .params-strip, .resumo-box, .doc-footer, .signatures-box { page-break-inside: avoid; break-inside: avoid; }

        /* Cabeçalho Oficial do Colégio */
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .doc-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .doc-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
        }
        .doc-titles h1 {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .doc-titles h2 {
            font-size: 11px;
            font-weight: 700;
            color: #1d4ed8;
            margin: 2px 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .doc-meta {
            text-align: right;
            font-size: 8.5px;
            color: #334155;
            line-height: 1.35;
        }
        .doc-page-badge {
            font-weight: 700;
            color: #1d4ed8;
        }

        /* Faixa de Parâmetros e Informações da Sessão */
        .params-strip {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 10px;
            margin-bottom: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 8.5px;
        }
        .params-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .params-label {
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 8px;
        }
        .params-val {
            font-weight: 600;
            color: #0f172a;
        }

        /* Títulos de Seção Estilo Excel / Corporativo */
        .group-header {
            background-color: #e2e8f0;
            color: #0f172a;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 9.5px;
            padding: 4px 8px;
            border-left: 4px solid #1d4ed8;
            margin-top: 10px;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Tabelas Analíticas Oficiais (Estética Excel com Bordas Nítidas) */
        table.table-report {
            width: 100% !important;
            table-layout: fixed !important;
            box-sizing: border-box !important;
            border-collapse: collapse !important;
            margin-bottom: 6px;
            font-size: 8.5px;
            border: 1px solid #000000 !important;
        }
        table.table-report th {
            background-color: #ffffff !important;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.02em;
            padding: 3px 5px;
            border: 1px solid #000000 !important;
            text-align: left;
        }
        table.table-report td {
            background-color: #ffffff !important;
            padding: 3px 5px;
            border: 1px solid #000000 !important;
            vertical-align: middle;
            color: #0f172a;
        }
        .group-subtotal {
            background-color: #ffffff !important;
            border-top: 1px solid #000000 !important;
            border-bottom: 2px solid #000000 !important;
            font-weight: 700;
        }

        .text-center { text-align: center !important; }
        .text-right  { text-align: right !important; }

        /* Tags Discretas de Origem */
        .tag-origem {
            display: inline-block;
            font-size: 7px;
            padding: 1px 4px;
            border-radius: 3px;
            margin-left: 4px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .tag-online {
            background: #ffffff !important;
            border: 1px solid #1d4ed8;
            color: #1d4ed8;
        }
        .tag-balcao {
            background: #ffffff !important;
            border: 1px solid #64748b;
            color: #334155;
        }

        /* Box de Resumo do Caixa */
        .resumo-box {
            border: 1px solid #000000;
            margin-top: 6px;
            padding: 6px 8px;
            font-size: 8pt;
            background-color: #ffffff;
        }
        .resumo-table {
            width: 100%;
            border-collapse: collapse;
        }
        .resumo-table th {
            border-bottom: 1px solid #000000;
            padding: 3px 4px;
            font-weight: bold;
            font-size: 8.5px;
            text-align: left;
        }
        .resumo-table td {
            border: none;
            padding: 2px 4px;
            font-size: 8.5px;
        }

        /* Assinaturas */
        .signatures-box {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 12px;
        }
        .signature-line {
            width: 44%;
            text-align: center;
            border-top: 1px solid #000000;
            font-size: 8pt;
            padding-top: 4px;
            color: #0f172a;
        }

        /* Rodapé Oficial da Folha */
        .doc-footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px solid #cbd5e1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>
<body>

    <!-- Barra de Controle Superior Padronizada (#1e293b - Visível somente na tela) -->
    <div class="no-print-bar">
        <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                Fechamento de Caixa &bull; Sessão #<?php echo $controleStr; ?> &bull; Turno: <?php echo htmlspecialchars($turnoNome); ?>
            </div>
            <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">
                Total de Páginas: 1
            </div>
        </div>

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

        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-action btn-primary" onclick="window.print()">
                🖨 Imprimir Documento
            </button>
            <button type="button" class="btn-action" onclick="fecharJanela()">
                ✕ Fechar
            </button>
        </div>
    </div>

    <!-- ÁREA ESCALÁVEL COM ZOOM PARA CONFERÊNCIA -->
    <div id="document-zoom-area" class="document-zoom-area">
        <div class="sheet-container page-sheet container">

            <!-- CABEÇALHO INSTITUCIONAL TIMBRADO -->
            <header class="doc-header">
                <div class="doc-brand">
                    <?php if (!empty($logoDataUri)): ?>
                        <img src="<?php echo $logoDataUri; ?>" alt="Logotipo do Colégio" class="doc-logo">
                    <?php endif; ?>
                    <div class="doc-titles">
                        <h1>COLÉGIO MONTEIRO LOBATO</h1>
                        <h2>FECHAMENTO DE CAIXA OPERACIONAL</h2>
                    </div>
                </div>
                <div class="doc-meta font-tabular">
                    <div><strong>Emissão:</strong> <?php echo $emissaoDataHora; ?></div>
                    <div><strong>Operador:</strong> <?php echo htmlspecialchars($operadorTurno, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="doc-page-badge">Página 1 de 1</div>
                </div>
            </header>

            <!-- FAIXA DE PARÂMETROS E AUDITORIA DA SESSÃO -->
            <div class="params-strip font-tabular">
                <div class="params-item">
                    <span class="params-label">Controle:</span>
                    <span class="params-val">#<?php echo $controleStr; ?></span>
                </div>
                <div class="params-item">
                    <span class="params-label">Operador:</span>
                    <span class="params-val"><?php echo htmlspecialchars($operadorNome, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="params-item">
                    <span class="params-label">Turno:</span>
                    <span class="params-val"><?php echo htmlspecialchars($turnoNome, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="params-item">
                    <span class="params-label">Abertura:</span>
                    <span class="params-val"><?php echo $abertoEm; ?></span>
                </div>
                <div class="params-item">
                    <span class="params-label">Fechamento:</span>
                    <span class="params-val"><?php echo $fechadoEm; ?></span>
                </div>
                <div class="params-item">
                    <span class="params-label">Fundo de Troco:</span>
                    <span class="params-val">R$ <?php echo number_format($fundoCaixa, 2, ',', '.'); ?></span>
                </div>
            </div>

            <!-- SEÇÃO 1: DUPLICATAS (RECARGAS DE ALUNOS/RESPONSÁVEIS) -->
            <div class="group-header">
                <span>1. Duplicatas (Recargas de Alunos / Responsáveis)</span>
                <span class="font-tabular">Subtotal: R$ <?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></span>
            </div>
            <table class="table-report">
                <thead>
                    <tr>
                        <th style="width: 8%;">Duplicata</th>
                        <th style="width: 31%;">Aluno</th>
                        <th style="width: 8%;">Referência</th>
                        <th style="width: 8%;">Finalidade</th>
                        <th style="width: 8%;">Vencto</th>
                        <th style="width: 8%;" class="text-right">Vlr Nominal</th>
                        <th style="width: 5%;" class="text-right">Multa</th>
                        <th style="width: 5%;" class="text-right">Juros</th>
                        <th style="width: 5%;" class="text-right">Desconto</th>
                        <th style="width: 8%;" class="text-right">Vlr Recebido</th>
                        <th style="width: 6%;" class="text-center">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($duplicatas)): ?>
                        <tr>
                            <td colspan="11" class="text-center" style="color: #64748b; padding: 6px;">Nenhuma duplicata ou recarga registrada nesta sessão.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($duplicatas as $dup): ?>
                            <tr>
                                <td class="font-tabular"><?php echo htmlspecialchars($dup['numero'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dup['aluno'] ?? ''); ?></td>
                                <td class="font-tabular"><?php echo htmlspecialchars($dup['referencia'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($dup['finalidade'] ?? 'CANTINA'); ?></td>
                                <td class="font-tabular"><?php echo htmlspecialchars($dup['vencimento'] ?? ''); ?></td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($dup['valor_nominal'] ?? 0), 2, ',', '.'); ?></td>
                                <td class="font-tabular text-right">0,00</td>
                                <td class="font-tabular text-right">0,00</td>
                                <td class="font-tabular text-right">0,00</td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($dup['valor_recebido'] ?? 0), 2, ',', '.'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($dup['tipo'] ?? 'Dinheiro'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="group-subtotal">
                            <td colspan="5">Subtotal Duplicatas</td>
                            <td class="font-tabular text-right"><?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></td>
                            <td class="font-tabular text-right">0,00</td>
                            <td class="font-tabular text-right">0,00</td>
                            <td class="font-tabular text-right">0,00</td>
                            <td class="font-tabular text-right"><?php echo number_format($subtotalDuplicatas, 2, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- SEÇÃO 2: VENDAS CANTINA WEB (ONLINE) -->
            <div class="group-header">
                <span>2. Vendas Cantina Web (Agendamentos Online)</span>
                <span class="font-tabular">Subtotal: R$ <?php echo number_format($subtotalCantinaWeb, 2, ',', '.'); ?></span>
            </div>
            <table class="table-report">
                <thead>
                    <tr>
                        <th style="width: 32%;">Alimento</th>
                        <th style="width: 32%;">Aluno</th>
                        <th style="width: 10%;" class="text-right">PrcUnitário</th>
                        <th style="width: 6%;" class="text-center">Qtde</th>
                        <th style="width: 11%;" class="text-right">Vlr Pago</th>
                        <th style="width: 9%;" class="text-center">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendasCantinaWeb)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="color: #64748b; padding: 6px;">Nenhuma venda online do Cantina Web registrada nesta sessão.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendasCantinaWeb as $v): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($v['alimento'] ?? ($v['produto_descricao'] ?? '')); ?>
                                    <span class="tag-origem tag-online">Online</span>
                                </td>
                                <td><?php echo htmlspecialchars($v['pessoa'] ?? ($v['cliente_ident'] ?? '')); ?></td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($v['preco_unitario'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                                <td class="font-tabular text-center"><?php echo (int)($v['quantidade'] ?? 1); ?></td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($v['valor_pago'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($v['tipo'] ?? 'Saldo'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="group-subtotal">
                            <td colspan="4">Subtotal Cantina Web</td>
                            <td class="font-tabular text-right"><?php echo number_format($subtotalCantinaWeb, 2, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- SEÇÃO 3: VENDAS PRESENCIAIS NO BALCÃO -->
            <div class="group-header">
                <span>3. Vendas Presenciais no Balcão (PDV)</span>
                <span class="font-tabular">Subtotal: R$ <?php echo number_format($subtotalBalcao, 2, ',', '.'); ?></span>
            </div>
            <table class="table-report">
                <thead>
                    <tr>
                        <th style="width: 32%;">Alimento</th>
                        <th style="width: 32%;">Pessoa / Cliente</th>
                        <th style="width: 10%;" class="text-right">PrcUnitário</th>
                        <th style="width: 6%;" class="text-center">Qtde</th>
                        <th style="width: 11%;" class="text-right">Vlr Pago</th>
                        <th style="width: 9%;" class="text-center">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vendasBalcao)): ?>
                        <tr>
                            <td colspan="6" class="text-center" style="color: #64748b; padding: 6px;">Nenhuma venda presencial no balcão registrada nesta sessão.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendasBalcao as $v): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($v['alimento'] ?? ($v['produto_descricao'] ?? '')); ?>
                                    <span class="tag-origem tag-balcao">Balcão</span>
                                </td>
                                <td><?php echo htmlspecialchars($v['pessoa'] ?? ($v['cliente_ident'] ?? 'Balcão')); ?></td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($v['preco_unitario'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                                <td class="font-tabular text-center"><?php echo (int)($v['quantidade'] ?? 1); ?></td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($v['valor_pago'] ?? ($v['valor'] ?? 0)), 2, ',', '.'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($v['tipo'] ?? 'Dinheiro'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="group-subtotal">
                            <td colspan="4">Subtotal Balcão</td>
                            <td class="font-tabular text-right"><?php echo number_format($subtotalBalcao, 2, ',', '.'); ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- SEÇÃO 4: RECEBIMENTO EM PIX -->
            <div class="group-header">
                <span>4. Recebimento em Pix (Conferência Bancária Nubank)</span>
                <span class="font-tabular">Subtotal: R$ <?php echo number_format($totalPixRecebimentos, 2, ',', '.'); ?></span>
            </div>
            <table class="table-report">
                <thead>
                    <tr>
                        <th style="width: 25%;">Banco</th>
                        <th style="width: 45%;">Descrição</th>
                        <th style="width: 15%;">Observação</th>
                        <th style="width: 15%;" class="text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pixRecebimentos)): ?>
                        <tr>
                            <td colspan="4" class="text-center" style="color: #64748b; padding: 6px;">Nenhum recebimento Pix conciliado nesta sessão.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pixRecebimentos as $px): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($px['banco'] ?? 'NUBANK - CANTINA'); ?></td>
                                <td><?php echo htmlspecialchars($px['descricao'] ?? 'Credito transferência pix'); ?></td>
                                <td><?php echo htmlspecialchars($px['observacao'] ?? 'via pix'); ?></td>
                                <td class="font-tabular text-right"><?php echo number_format((float)($px['valor'] ?? 0), 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="group-subtotal">
                            <td colspan="3">Subtotal Pix</td>
                            <td class="font-tabular text-right"><?php echo number_format($totalPixRecebimentos, 2, ',', '.'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- SEÇÃO 5: RESUMO DO CAIXA -->
            <div class="group-header">
                <span>5. Resumo do Caixa — <?php echo htmlspecialchars($operadorTurno); ?></span>
                <span class="font-tabular">Saldo Total: R$ <?php echo number_format((float)($saldo['total'] ?? 0), 2, ',', '.'); ?></span>
            </div>
            <div class="resumo-box">
                <table class="resumo-table font-tabular">
                    <thead>
                        <tr>
                            <th style="width: 25%;">(+) Fundo de Caixa <?php echo number_format((float)($fundo['total'] ?? 0), 2, ',', '.'); ?></th>
                            <th style="width: 25%;">(+) Entradas <?php echo number_format((float)($entradas['total'] ?? 0), 2, ',', '.'); ?></th>
                            <th style="width: 25%;">(-) Saídas <?php echo number_format((float)($saidas['total'] ?? 0), 2, ',', '.'); ?></th>
                            <th style="width: 25%;">(=) Saldo de Caixa <?php echo number_format((float)($saldo['total'] ?? 0), 2, ',', '.'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Em Dinheiro <?php echo number_format((float)($fundo['dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                            <td>Em Dinheiro <?php echo number_format((float)($entradas['dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                            <td>Em Dinheiro <?php echo number_format((float)($saidas['dinheiro'] ?? 0), 2, ',', '.'); ?></td>
                            <td><strong>Em Dinheiro <?php echo number_format((float)($saldo['dinheiro'] ?? 0), 2, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>Em Pix <?php echo number_format((float)($entradas['pix'] ?? 0), 2, ',', '.'); ?></td>
                            <td>Em Pix <?php echo number_format((float)($saidas['pix'] ?? 0), 2, ',', '.'); ?></td>
                            <td><strong>Em Pix <?php echo number_format((float)($saldo['pix'] ?? 0), 2, ',', '.'); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- ASSINATURAS DO FECHAMENTO -->
            <div class="signatures-box">
                <div class="signature-line">
                    Assinatura do Operador de Caixa
                </div>
                <div class="signature-line">
                    Visto da Gerência / Supervisão
                </div>
            </div>

            <!-- RODAPÉ OFICIAL DA FOLHA -->
            <footer class="doc-footer">
                <span>Colégio Monteiro Lobato &bull; Sistema de Gestão Cantina Web</span>
                <span>Documento Oficial A4 de Prestação de Contas e Fechamento de Caixa</span>
            </footer>

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
