<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Demonstrativo Individual de Consumo para Professor/Colaborador (A4)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrão oficial de relatórios A4
 * Padrão Excel Corporativo com divisórias pretas e fundo branco puro, banners azul marinho (#0f172a)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
if (!isset($dados) || !is_array($dados)) {
    $dados = get_defined_vars();
}
$dados = $dados['dados'] ?? $dados;

$colaborador = $dados['colaborador'] ?? [];
$competencia = $dados['competencia'] ?? date('m/Y');
$operador = $dados['operador'] ?? 'Administração Geral';
$emissao = $dados['emissao'] ?? date('d/m/Y H:i:s');
$itens = $dados['itens'] ?? ($dados['lancamentos'] ?? []);
$totais = $dados['totais'] ?? [];
$isPdf = $isPdf ?? false;

$totalDesconto = (float)($totais['total_desconto'] ?? ($dados['total_valor'] ?? 0.0));
$totalLanches = (int)($totais['total_lanches'] ?? ($dados['total_itens'] ?? 0));
$totalCompras = (int)($totais['total_compras'] ?? count($itens));

$logoDataUri = \App\Services\PdfService::getLogoDataUri(false);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Demonstrativo Individual — <?php echo htmlspecialchars($colaborador['nome'] ?? 'Colaborador'); ?> — Colégio Monteiro Lobato</title>
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
            font-weight: 700;
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
            .colab-banner,
            .param-bar,
            .footer-assinaturas-box,
            .row-geral-total {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }

        /* Quebra de páginas adequada em tabelas longas */
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; break-inside: avoid; }
        .school-header, .colab-banner, .param-bar, .footer-assinaturas-box { page-break-inside: avoid; break-inside: avoid; }

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

        /* Banner de Identificação do Colaborador */
        .colab-banner {
            background: #f8fafc;
            border: 1px solid #000000;
            padding: 6px 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .colab-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: #000000;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.2;
        }
        .code-tag {
            display: inline-block;
            background: #ffffff;
            border: 1px solid #000000;
            border-radius: 2px;
            padding: 1px 5px;
            font-size: 7pt;
            font-weight: 700;
            color: #000000;
            margin-left: 4px;
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
            font-weight: 700 !important;
            border: 1px solid #000000 !important;
        }
        table.table-doc-excel th,
        table.table-doc-excel td {
            border: 1px solid #000000 !important;
            padding: 3px 4px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: 700 !important;
            word-break: break-word;
        }
        table.table-doc-excel thead th {
            font-weight: 700 !important;
            text-align: center !important;
            font-size: 7.2pt;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            background-color: #f8fafc !important;
            color: #000000 !important;
        }
        table.table-doc-excel tfoot td {
            font-weight: 700 !important;
            background-color: #f8fafc !important;
            color: #000000 !important;
            border-top: 2px solid #000000 !important;
        }

        .text-start { text-align: left !important; }
        .text-center { text-align: center !important; }
        .text-end { text-align: center !important; }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            color: #000000;
            line-height: 1.25;
        }
        .doc-footer-meta {
            margin-top: 10px;
            font-size: 7pt;
            font-weight: 700;
            color: #000000;
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #000000;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff;">
            Demonstrativo Individual &bull; <?php echo htmlspecialchars($colaborador['nome'] ?? 'Colaborador'); ?>
        </div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">
            Competência: <?php echo htmlspecialchars($competencia); ?> &bull; Total: R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?>
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
            <div class="report-title-main">DEMONSTRATIVO INDIVIDUAL DE CONSUMO — DESCONTO EM FOLHA DE PAGAMENTO</div>
            <div style="font-size: 8pt; color: #334155;">Sistema de Gestão Cantina Web • Módulo de Recursos Humanos & Pessoal</div>
        </div>
        <div class="school-meta-col font-tabular">
            <div><strong>Competência:</strong> <?php echo htmlspecialchars($competencia, ENT_QUOTES, 'UTF-8'); ?></div>
            <div><strong>Emissão:</strong> <?php echo $emissao; ?></div>
            <div><strong>Operador:</strong> <?php echo htmlspecialchars($operador, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
    </div>

    <!-- Banner de Identificação do Colaborador -->
    <div class="colab-banner">
        <div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <span class="colab-name"><?php echo htmlspecialchars(mb_strtoupper($colaborador['nome'] ?? 'COLABORADOR', 'UTF-8')); ?></span>
                <span class="code-tag font-tabular">Matrícula: <?php echo htmlspecialchars($colaborador['matricula'] ?? ($colaborador['codigo'] ?? '-')); ?></span>
                <?php if (!empty($colaborador['cpf'])): ?>
                    <span class="code-tag font-tabular">CPF: <?php echo htmlspecialchars($colaborador['cpf']); ?></span>
                <?php endif; ?>
            </div>
            <div style="font-size: 7.5pt; color: #334155; margin-top: 2px;">
                Cargo / Departamento: <strong><?php echo htmlspecialchars($colaborador['cargo'] ?? ($colaborador['departamento'] ?? 'Docente / Administrativo')); ?></strong>
            </div>
        </div>
        <div style="text-align: right;" class="font-tabular">
            <div style="font-size: 7pt; font-weight: 700; color: #475569; text-transform: uppercase;">Total a Descontar</div>
            <div style="font-size: 11pt; font-weight: 800; color: #000000;">R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?></div>
        </div>
    </div>

    <!-- Faixa de Parâmetros e Indicadores -->
    <div class="param-bar font-tabular">
        <div>
            <strong>Competência:</strong> <?php echo htmlspecialchars($competencia); ?> &bull; 
            <strong>Total de Compras:</strong> <?php echo $totalCompras; ?>
        </div>
        <div>
            <strong>Total de Lanches / Itens:</strong> <?php echo $totalLanches; ?> un &bull; 
            <strong>Valor Total Averbação:</strong> R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?>
        </div>
    </div>

    <!-- Banner de Seção Escuro -->
    <div class="section-banner">
        <span>DISCRIMINAÇÃO ANALÍTICA DOS CONSUMOS DA COMPETÊNCIA</span>
        <span class="font-tabular"><?php echo count($itens); ?> lançamento(s)</span>
    </div>

    <!-- Tabela Estilo Excel Corporativo -->
    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 14%;" class="text-center">Data / Hora</th>
                <th style="width: 42%;" class="text-start">Descrição do Alimento / Produto</th>
                <th style="width: 8%;" class="text-center">Qtd.</th>
                <th style="width: 18%;" class="text-center">Valor Unitário</th>
                <th style="width: 18%;" class="text-center">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($itens)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 12px; color: #000000;">
                        Não há consumos registrados para este colaborador no período selecionado.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($itens as $it): 
                    $dataFmt = htmlspecialchars($it['data_formatada'] ?? (!empty($it['data']) ? date('d/m/Y H:i', strtotime($it['data'])) : '-'), ENT_QUOTES, 'UTF-8');
                    $desc = htmlspecialchars($it['descricao'] ?? ($it['produto_nome'] ?? '-'), ENT_QUOTES, 'UTF-8');
                    $qtd = (int)($it['quantidade'] ?? 1);
                    $unit = (float)($it['valor_unitario'] ?? ($it['preco_unitario'] ?? 0.0));
                    $sub = (float)($it['valor_total'] ?? ($it['subtotal'] ?? ($qtd * $unit)));
                ?>
                    <tr>
                        <td class="text-center font-tabular"><?php echo $dataFmt; ?></td>
                        <td class="text-start"><strong><?php echo $desc; ?></strong></td>
                        <td class="text-center font-tabular"><?php echo $qtd; ?> un</td>
                        <td class="text-center font-tabular">R$ <?php echo number_format($unit, 2, ',', '.'); ?></td>
                        <td class="text-center font-tabular">
                            <strong>R$ <?php echo number_format($sub, 2, ',', '.'); ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="row-geral-total">
                <td colspan="2" class="text-end">TOTAL DESTE DEMONSTRATIVO INDIVIDUAL:</td>
                <td class="text-center font-tabular"><?php echo $totalLanches; ?> UN</td>
                <td class="text-center font-tabular" colspan="2">R$ <?php echo number_format($totalDesconto, 2, ',', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Assinaturas e Rodapé -->
    <div class="footer-assinaturas-box">
        <div class="signature-line">
            <strong><?php echo htmlspecialchars($colaborador['nome'] ?? 'Colaborador(a)'); ?></strong><br>
            Ciente do Consumo / Assinatura
        </div>
        <div class="signature-line">
            <strong>Cantina Escolar / Recursos Humanos</strong><br>
            Conferência e Averbação
        </div>
    </div>

    <div class="doc-footer-meta">
        <div>Cantina Web &bull; Sistema Integrado de Gestão Escolar &bull; Módulo de Pessoal</div>
        <div>Página 1 de 1 &bull; Documento Oficial de Averbação em Folha de Pagamento</div>
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
</body>
</html>
