<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Timbrado — DRE Gráfico (DRE Visual Unificado & Produtos Mais Rentáveis)
 * 
 * Apresentação executiva e visual do DRE (unindo a proporção circular de Vendas Online vs Presencial,
 * Conciliação de Carteira Pré-Paga, Custos CMV, Despesas Operacionais por Categoria e Resultado Líquido),
 * acompanhada abaixo pela Lista de Detalhamento dos Produtos Mais Rentáveis do Período.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$anoAtual = $ano ?? (int)date('Y');
$dataInicio = $dataInicio ?? sprintf('%04d-01-01', $anoAtual);
$dataFim    = $dataFim ?? date('Y-m-d');
$totais = $totais ?? ($drePeriodo ?? []);
$produtosRentaveis = $graficos['produtos_rentaveis'] ?? [];
$despesasPorCat = $graficos['despesas_por_cat'] ?? [];
$razaoSocial = $config->razao_social ?? 'COLÉGIO MONTEIRO LOBATO';
$cnpj = $config->cnpj ?? '';
$isPdf = $isPdf ?? false;

// Métricas contábeis unificadas
$arrecadacaoBruta        = (float)($totais['arrecadacao_bruta'] ?? 0.00);
$arrecadacaoLiquida      = (float)($totais['arrecadacao_liquida'] ?? 0.00);
$totalRecargas           = (float)($totais['total_recargas'] ?? 0.00);
$recargasPixOnline       = (float)($totais['recargas_pix_online'] ?? 0.00);
$recargasPixPdv          = (float)($totais['recargas_pix_pdv'] ?? 0.00);
$recargasDinheiro        = (float)($totais['recargas_dinheiro'] ?? 0.00);
$recargasBalcao          = (float)($totais['recargas_balcao'] ?? ($recargasPixPdv + $recargasDinheiro));
$consumoSaldoOnline      = (float)($totais['consumo_saldo_online'] ?? 0.00);
$consumoSaldoBalcao      = (float)($totais['consumo_saldo_balcao'] ?? 0.00);
$consumoSaldoTotal       = (float)($totais['consumo_saldo_total'] ?? 0.00);
$vendasDinheiro          = (float)($totais['vendas_dinheiro'] ?? 0.00);
$vendasPix               = (float)($totais['vendas_pix'] ?? 0.00);
$vendasDiretasBalcao     = (float)($totais['vendas_diretas_balcao'] ?? ($vendasDinheiro + $vendasPix));
$receitaOnline           = (float)($totais['receita_online'] ?? $consumoSaldoOnline);
$receitaBalcao           = (float)($totais['receita_balcao'] ?? ($consumoSaldoBalcao + $vendasDiretasBalcao));
$receitaBruta            = (float)($totais['receita_bruta'] ?? ($receitaOnline + $receitaBalcao));
$totalDeducoes           = (float)($totais['total_deducoes'] ?? 0.00);
$receitaLiquida          = (float)($totais['receita_liquida'] ?? 0.00);
$cmv                     = (float)($totais['cmv'] ?? 0.00);
$lucroBruto              = (float)($totais['lucro_bruto'] ?? 0.00);
$margemBrutaPercent      = (float)($totais['margem_bruta_percent'] ?? 0.0);
$totalDespesas            = (float)($totais['total_despesas'] ?? 0.00);
$despesasFornecedores     = (float)($totais['despesas_fornecedores'] ?? 0.00);
$despesasAdmin            = (float)($totais['despesas_administrativas'] ?? 0.00);
$despesasPessoal          = (float)($totais['despesas_pessoal'] ?? 0.00);
$despesasVendas           = (float)($totais['despesas_vendas'] ?? 0.00);
$despesasManutencao       = (float)($totais['despesas_manutencao'] ?? 0.00);
$despesasFinanceiras      = (float)($totais['despesas_financeiras'] ?? 0.00);
$despesasOutras           = (float)($totais['despesas_outras'] ?? 0.00);
$despesasOperacionaisPuras = (float)($totais['despesas_operacionais_puras'] ?? ($despesasAdmin + $despesasPessoal + $despesasVendas + $despesasManutencao + $despesasFinanceiras + $despesasOutras));
$resultadoLiquido         = (float)($totais['resultado_liquido'] ?? 0.00);
$margemLiquidaPercent     = (float)($totais['margem_liquida_percent'] ?? 0.0);
$diferencaRecargaConsumo  = (float)($totais['diferenca_recarga_consumo'] ?? ($totalRecargas - $consumoSaldoTotal));
$resultadoCaixa           = (float)($totais['resultado_caixa'] ?? ($resultadoLiquido + $diferencaRecargaConsumo));
$margemCaixaPercent       = (float)($totais['margem_caixa_percent'] ?? 0.0);

// Escala máxima para barras visuais do DRE
$maxReferenciaDre = max(
    1.0,
    $arrecadacaoLiquida,
    $receitaBruta,
    $receitaLiquida,
    $totalRecargas,
    $cmv + $totalDespesas,
    abs($resultadoLiquido),
    abs($resultadoCaixa)
);

// Percentuais de canais sobre a Receita Bruta de Vendas
$pctOnline       = $receitaBruta > 0 ? ($consumoSaldoOnline / $receitaBruta) * 100 : 0.0;
$pctBalcaoSaldo  = $receitaBruta > 0 ? ($consumoSaldoBalcao / $receitaBruta) * 100 : 0.0;
$pctBalcaoDireto = $receitaBruta > 0 ? ($vendasDiretasBalcao / $receitaBruta) * 100 : 0.0;
$pctBalcaoTotal  = $receitaBruta > 0 ? ($receitaBalcao / $receitaBruta) * 100 : 0.0;

// Despesas agrupadas para o Gráfico Circular Unificado do DRE
$despesasVendasFinOutras = $despesasVendas + $despesasFinanceiras + $despesasOutras;
$saldoRetidoPositivo     = max(0.0, $diferencaRecargaConsumo);

// Volume financeiro total movimentado nas fatias do Gráfico Circular Completo do DRE
$somaFatiasDreCircular = $consumoSaldoOnline
    + $consumoSaldoBalcao
    + $vendasDiretasBalcao
    + $saldoRetidoPositivo
    + $cmv
    + $despesasFornecedores
    + $despesasPessoal
    + $despesasAdmin
    + $despesasManutencao
    + $despesasVendasFinOutras;

$calcPctFatia = function (float $valor) use ($somaFatiasDreCircular): float {
    return $somaFatiasDreCircular > 0 ? (($valor / $somaFatiasDreCircular) * 100.0) : 0.0;
};

// Percentuais de composição sobre a Receita Líquida
$pctCmv      = $receitaLiquida > 0 ? min(100.0, max(0.0, ($cmv / $receitaLiquida) * 100)) : 0.0;
$pctDespesas = $receitaLiquida > 0 ? min(100.0, max(0.0, ($totalDespesas / $receitaLiquida) * 100)) : 0.0;
$pctLucro    = $receitaLiquida > 0 && $resultadoLiquido > 0 ? min(100.0, ($resultadoLiquido / $receitaLiquida) * 100) : 0.0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRE Gráfico Visual (<?php echo date('d/m/Y', strtotime($dataInicio)); ?> a <?php echo date('d/m/Y', strtotime($dataFim)); ?>) — Colégio Monteiro Lobato</title>
    <style>
        /* ==========================================================================
           1. Diretivas Oficiais de Página A4 Retrato (Portrait)
           ========================================================================== */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        *, *:before, *:after {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8.5pt;
            font-weight: 700;
            line-height: 1.3;
            color: #000000;
            margin: 0;
            padding: 0;
            background-color: #cbd5e1;
        }

        .font-tabular {
            font-feature-settings: 'tnum' 1, 'zero' 1;
            font-variant-numeric: tabular-nums;
        }

        /* ==========================================================================
           2. Contêiner de Folha A4
           ========================================================================== */
        .doc-page {
            background-color: #ffffff;
            width: 210mm;
            max-width: calc(100% - 24px);
            min-height: auto;
            margin: 16px auto;
            padding: 6mm 10mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                font-weight: 700 !important;
                color: #000000 !important;
            }
            body, table, th, td, div, span, p, strong {
                font-weight: 700 !important;
                color: #000000 !important;
            }
            .section-banner, .section-banner * {
                color: #ffffff !important;
                background-color: #0f172a !important;
            }
            .no-print {
                display: none !important;
            }
            .doc-page {
                width: 210mm !important;
                max-width: 210mm !important;
                min-height: auto !important;
                margin: 0 auto !important;
                padding: 5mm 10mm !important;
                box-shadow: none !important;
                border: none !important;
            }
        }

        /* ==========================================================================
           3. Barra Superior de Controle Padronizada (#1e293b - Apenas em Tela)
           ========================================================================== */
        .no-print {
            background-color: #1e293b;
            color: #ffffff;
            border: 1px solid #0f172a;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            font-size: 11px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            width: 210mm;
            max-width: calc(100% - 32px);
            margin: 10px auto 16px auto;
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
            flex-shrink: 0;
        }
        .no-print .btn-zoom {
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
        }
        .no-print .btn-zoom:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #93c5fd;
        }
        .no-print .btn-zoom-val {
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
        }
        .btn-print-action {
            background: #1d4ed8;
            color: #ffffff !important;
            border: 1px solid #1e40af;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-print-action:hover {
            background: #1e40af;
        }
        .btn-close-action {
            background: #ffffff;
            color: #0f172a !important;
            border: 1px solid #cbd5e1;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-close-action:hover {
            background: #f1f5f9;
        }

        .document-zoom-area {
            zoom: var(--doc-zoom, 1);
            transform-origin: top center;
            transition: zoom 0.1s ease-out;
        }
        @media print {
            .document-zoom-area {
                zoom: 1 !important;
                transform: none !important;
            }
        }

        /* ==========================================================================
           4. Cabeçalho Escolar Oficial e Banners
           ========================================================================== */
        .school-header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .school-logo-col {
            width: 42px;
            margin-right: 10px;
        }
        .school-logo-col img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #000000;
        }
        .school-title-col {
            flex: 1;
            line-height: 1.2;
        }
        .school-name {
            font-size: 10.5pt;
            font-weight: 800;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .report-title-main {
            font-size: 8.8pt;
            font-weight: 800;
            color: #000000;
            margin-top: 1px;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 7.4pt;
            font-weight: 700;
            color: #000000;
            margin-top: 1px;
        }
        .school-meta-col {
            text-align: right;
            font-size: 7.1pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.25;
        }

        .param-bar {
            font-size: 7.5pt;
            font-weight: 700;
            color: #000000;
            background: #f8fafc;
            border: 1.5px solid #000000;
            padding: 3px 8px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-banner {
            font-weight: 800;
            font-size: 7.8pt;
            text-transform: uppercase;
            background: #0f172a !important;
            color: #ffffff !important;
            padding: 3.5px 8px;
            margin-top: 6px;
            margin-bottom: 5px;
            border-radius: 2px;
        }

        /* ==========================================================================
           5. Componentes do DRE Visual Unificado (Cards + Gráfico Circular Gigante)
           ========================================================================== */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            margin-bottom: 6px;
        }
        .kpi-card {
            border: 1.5px solid #000000;
            background: #ffffff;
            padding: 5px 7px;
            border-radius: 2px;
        }
        .kpi-card-highlight {
            background: #f8fafc;
            border-left: 4px solid #000000;
        }
        .kpi-title {
            font-size: 6.7pt;
            font-weight: 800 !important;
            text-transform: uppercase;
            color: #000000 !important;
            margin-bottom: 1.5px;
        }
        .kpi-val {
            font-size: 10.5pt;
            font-weight: 800 !important;
            color: #000000 !important;
        }
        .kpi-sub {
            font-size: 6.9pt;
            font-weight: 800 !important;
            color: #000000 !important;
            margin-top: 2px;
        }

        .visual-dre-container {
            display: grid;
            grid-template-columns: 42% 56.5%;
            gap: 1.5%;
            border: 1.5px solid #000000;
            padding: 8px;
            background: #ffffff;
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .visual-col-left {
            border-right: 1.5px solid #000000;
            padding-right: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }

        .visual-col-right {
            padding-left: 4px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .panel-subtitle {
            width: 100%;
            font-size: 7.4pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #000000;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 3px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .giant-circle-wrapper {
            width: 245px;
            height: 245px;
            position: relative;
            margin: 4px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .circle-center-info {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
            width: 118px;
            line-height: 1.15;
        }

        .legend-section-title {
            font-size: 6.8pt;
            font-weight: 800;
            text-transform: uppercase;
            background: #f1f5f9;
            border: 1px solid #000000;
            padding: 2px 5px;
            margin-top: 4px;
            margin-bottom: 3px;
            color: #000000;
            display: flex;
            justify-content: space-between;
        }

        .legend-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.85pt;
            font-weight: 700;
        }
        .legend-table td {
            padding: 2px 3px;
            border-bottom: 1px dotted #94a3b8;
            color: #000000;
            font-weight: 700;
        }
        .legend-table tr:last-child td {
            border-bottom: none;
        }

        .legend-dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 2px;
            margin-right: 4px;
            vertical-align: middle;
            border: 1px solid #000000;
        }

        .legend-explain-box {
            margin-top: 5px;
            border: 1.5px solid #000000;
            background: #f8fafc;
            padding: 5px 6px;
            border-radius: 2px;
            font-size: 6.65pt;
            font-weight: 700;
            color: #000000;
            line-height: 1.3;
        }
        .legend-explain-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #cbd5e1;
            padding: 1.5px 0;
        }
        .legend-explain-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        /* ==========================================================================
           6. Tabela de Produtos Mais Rentáveis (Estilo Grade Excel Homologada)
           ========================================================================== */
        table.table-doc-excel {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 7.4pt;
            font-weight: 700 !important;
            border: 1px solid #000000 !important;
            margin-top: 2px;
        }
        table.table-doc-excel th,
        table.table-doc-excel td {
            border: 1px solid #000000 !important;
            padding: 2.5px 5px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            font-weight: 700 !important;
        }
        table.table-doc-excel thead th {
            background-color: #f8fafc !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            font-size: 6.9pt;
        }
        table.table-doc-excel tfoot td {
            background-color: #f8fafc !important;
            font-weight: 800 !important;
            border-top: 1.5px solid #000000 !important;
        }

        /* ==========================================================================
           7. Assinaturas e Rodapé
           ========================================================================== */
        .footer-signatures {
            margin-top: 28px;
            display: flex;
            justify-content: space-between;
            padding-top: 0;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }
        .signature-line {
            width: 42%;
            border-top: 1.5px solid #000000;
            text-align: center;
            font-size: 7.5pt;
            font-weight: 700 !important;
            color: #000000;
            padding-top: 4px;
        }
        .footer-doc {
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 6.8pt;
            font-weight: 700;
            color: #000000;
            border-top: 1px solid #000000;
            padding-top: 3px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="no-print">
    <div style="font-size: 11.5px; line-height: 1.35; font-weight: 600; min-width: 0; flex-shrink: 1;">
        <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #ffffff; font-weight: 700; font-size: 12px;">Cantina Web — Relatório Oficial DRE Gráfico (DRE Visual & Produtos Rentáveis)</div>
        <div style="font-size: 11px; color: #cbd5e1; font-weight: 500; margin-top: 1px;">Período: <?php echo date('d/m/Y', strtotime($dataInicio)); ?> a <?php echo date('d/m/Y', strtotime($dataFim)); ?> &bull; Formato A4</div>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
        <a href="<?php echo $baseUrl; ?>/financeiro/dre/imprimir?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" class="btn-close-action" title="Alternar para o Relatório DRE em Texto (Grade Contábil de 12 Meses)">
            📄 Ver Relatório Texto
        </a>

        <!-- Controles de Zoom para Conferência -->
        <div class="zoom-controls" title="Ajuste o zoom para conferir detalhes antes de imprimir ou salvar">
            <button type="button" class="btn-zoom" id="btnZoomOut" title="Diminuir Zoom (Ctrl -)" onclick="alterarZoom(-0.15)">−</button>
            <button type="button" class="btn-zoom-val" id="btnZoomReset" title="Clique para redefinir para 100% (Ctrl 0)" onclick="resetarZoom()">100%</button>
            <button type="button" class="btn-zoom" id="btnZoomIn" title="Aumentar Zoom (Ctrl +)" onclick="alterarZoom(0.15)">+</button>
        </div>

        <button type="button" class="btn-print-action" onclick="window.print()" title="Imprimir documento ou Salvar como PDF pelo navegador">
            🖨 Imprimir / Salvar PDF (A4)
        </button>
        <button type="button" class="btn-close-action" onclick="fecharJanela()">
            ✕ Fechar
        </button>
    </div>
</div>
<?php endif; ?>

<div id="document-zoom-area" class="document-zoom-area">
<div class="doc-page">
    <!-- Cabeçalho Oficial Escolar -->
    <div class="school-header">
        <?php if (!empty($logoBase64)): ?>
        <div class="school-logo-col">
            <img src="<?php echo $logoBase64; ?>" alt="Logotipo Oficial">
        </div>
        <?php endif; ?>
        <div class="school-title-col">
            <div class="school-name"><?php echo htmlspecialchars($razaoSocial, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="report-title-main">DEMONSTRAÇÃO VISUAL DO RESULTADO DO EXERCÍCIO (DRE GRÁFICO UNIFICADO)</div>
            <div class="report-subtitle">Visão Executiva de Vendas Online vs Presencial, Carteira Pré-Paga, Despesas e Produtos Mais Rentáveis</div>
        </div>
        <div class="school-meta-col">
            <?php if (!empty($cnpj)): ?>
                <div><strong>CNPJ:</strong> <?php echo htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div><strong>Emissão:</strong> <?php echo $dataEmissao ?? date('d/m/Y H:i:s'); ?></div>
            <div><strong>Unidade:</strong> Cantina Escolar Geral</div>
            <div><strong>Formato:</strong> DRE Visual A4</div>
        </div>
    </div>

    <!-- Faixa de Parâmetros -->
    <div class="param-bar">
        <div><strong>Período Apurado:</strong> <?php echo date('d/m/Y', strtotime($dataInicio)); ?> a <?php echo date('d/m/Y', strtotime($dataFim)); ?></div>
        <div><strong>Modelo Contábil:</strong> DRE Unificado (Vendas, Custódia de Carteira & Caixa Real Sem Duplicidade)</div>
    </div>

    <!-- SEÇÃO 1: DRE VISUAL UNIFICADO -->
    <div class="section-banner">
        1. DRE Visual Unificado — Síntese Gráfica de Entradas, Vendas, Custos, Despesas e Resultado
    </div>

    <!-- Grade de 6 Cartões Executivos do Período -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-card-highlight">
            <div class="kpi-title">1. Arrecadação Real na Conta (Cash-In)</div>
            <div class="kpi-val font-tabular">R$ <?php echo number_format($arrecadacaoLiquida, 2, ',', '.'); ?></div>
            <div class="kpi-sub font-tabular">Recargas: R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?> | Direto: R$ <?php echo number_format($vendasDiretasBalcao, 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-card kpi-card-highlight">
            <div class="kpi-title">2. Faturamento Líquido (Vendas Lanches)</div>
            <div class="kpi-val font-tabular">R$ <?php echo number_format($receitaLiquida, 2, ',', '.'); ?></div>
            <div class="kpi-sub font-tabular">Online: R$ <?php echo number_format($receitaOnline, 2, ',', '.'); ?> | Balcão: R$ <?php echo number_format($receitaBalcao, 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">3. Custódia de Carteiras (Recarga − Gasto)</div>
            <div class="kpi-val font-tabular"><?php echo ($diferencaRecargaConsumo > 0 ? '+' : '') . 'R$ ' . number_format($diferencaRecargaConsumo, 2, ',', '.'); ?></div>
            <div class="kpi-sub font-tabular">Gasto de Saldo: R$ <?php echo number_format($consumoSaldoTotal, 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">4. Lucro Bruto (Após CMV)</div>
            <div class="kpi-val font-tabular">R$ <?php echo number_format($lucroBruto, 2, ',', '.'); ?></div>
            <div class="kpi-sub font-tabular">CMV: R$ <?php echo number_format($cmv, 2, ',', '.'); ?> | Margem Bruta: <?php echo number_format($margemBrutaPercent, 1, ',', '.'); ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">5. Despesas & Pagto. Fornecedores</div>
            <div class="kpi-val font-tabular">- R$ <?php echo number_format($totalDespesas, 2, ',', '.'); ?></div>
            <div class="kpi-sub font-tabular">Fornec.: R$ <?php echo number_format($despesasFornecedores, 2, ',', '.'); ?> | Desp.: R$ <?php echo number_format($despesasOperacionaisPuras, 2, ',', '.'); ?></div>
        </div>
        <div class="kpi-card kpi-card-highlight">
            <div class="kpi-title">6. Resultado Final em Conta & Vendas</div>
            <div class="kpi-val font-tabular">R$ <?php echo number_format($resultadoCaixa, 2, ',', '.'); ?></div>
            <div class="kpi-sub font-tabular">Lucro Vendas: R$ <?php echo number_format($resultadoLiquido, 2, ',', '.'); ?> (<?php echo number_format($margemLiquidaPercent, 1, ',', '.'); ?>%)</div>
        </div>
    </div>

    <!-- Painel Unificado: Único e Gigantesco Gráfico Circular Contendo Todo o DRE + Legenda Analítica Completa -->
    <div class="visual-dre-container">
        <!-- Coluna Esquerda: Gigantesco Gráfico Circular com Todo o DRE (Entradas, Vendas, CMV e Despesas) -->
        <div class="visual-col-left">
            <div class="panel-subtitle">
                <span>Gráfico Circular Integral do DRE</span>
                <span class="font-tabular">Entradas &times; Saídas</span>
            </div>

            <div class="giant-circle-wrapper">
                <canvas id="chartCanaisVenda"></canvas>
                <div class="circle-center-info">
                    <div style="font-size: 5.8pt; font-weight: 800; text-transform: uppercase; color: #000000;">Resultado em Conta</div>
                    <div class="font-tabular" style="font-size: 8.6pt; font-weight: 800; color: #000000; margin: 1px 0;">
                        R$ <?php echo number_format($resultadoCaixa, 2, ',', '.'); ?>
                    </div>
                    <div style="font-size: 5.6pt; font-weight: 800; color: #000000; border-top: 1px solid #000000; padding-top: 1px;">
                        Vendas: R$ <?php echo number_format($receitaLiquida, 2, ',', '.'); ?>
                    </div>
                </div>
            </div>

            <!-- Resumo Conciliado de Carteira Pré-Paga na Base do Gráfico Circular -->
            <div style="width: 100%; margin-top: 4px; padding-top: 4px; border-top: 1.5px solid #000000;">
                <div style="font-size: 6.7pt; font-weight: 800; text-transform: uppercase; display: flex; justify-content: space-between; margin-bottom: 2px;">
                    <span>Conciliação de Carteira Pré-Paga</span>
                    <span>Zero Duplicidade</span>
                </div>
                <table class="legend-table">
                    <tbody>
                        <tr>
                            <td>(+) Recargas Pix Online + Balcão</td>
                            <td style="text-align: center;" class="font-tabular">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td>(-) Saldo Gasto nas Vendas de Lanches</td>
                            <td style="text-align: center;" class="font-tabular">- R$ <?php echo number_format($consumoSaldoTotal, 2, ',', '.'); ?></td>
                        </tr>
                        <tr style="background: #f8fafc; border-top: 1px solid #000000;">
                            <td><strong>(=) Saldo Retido em Carteiras</strong></td>
                            <td style="text-align: center;" class="font-tabular"><strong><?php echo ($diferencaRecargaConsumo > 0 ? '+' : '') . 'R$ ' . number_format($diferencaRecargaConsumo, 2, ',', '.'); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Coluna Direita: Legenda Completa do Gráfico Circular + Explicação Contábil de Saldos e Equações -->
        <div class="visual-col-right">
            <div>
                <div class="panel-subtitle">
                    <span>Estrutura Visual do DRE & Despesas por Categoria (Legenda do Gráfico Circular)</span>
                </div>

                <!-- GRUPO 1 DA LEGENDA: FATIAS DE ENTRADAS E RECEITAS -->
                <div class="legend-section-title" style="margin-top: 0;">
                    <span>1. Fatias de Entradas: Vendas & Carteira no Gráfico</span>
                    <span class="font-tabular">Valor (R$) | % Giro</span>
                </div>
                <table class="legend-table">
                    <tbody>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #1d4ed8;"></span>
                                (+) Vendas Online (Pedidos Web — <?php echo number_format($pctOnline, 1, ',', '.'); ?>% das vendas)
                            </td>
                            <td style="text-align: center; width: 34%;" class="font-tabular">
                                R$ <?php echo number_format($consumoSaldoOnline, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($consumoSaldoOnline), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #0284c7;"></span>
                                (+) Vendas Balcão via Saldo PDV (<?php echo number_format($pctBalcaoSaldo, 1, ',', '.'); ?>% das vendas)
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                R$ <?php echo number_format($consumoSaldoBalcao, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($consumoSaldoBalcao), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #0d9488;"></span>
                                (+) Vendas Diretas Balcão Din/Pix (<?php echo number_format($pctBalcaoDireto, 1, ',', '.'); ?>% das vendas)
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                R$ <?php echo number_format($vendasDiretasBalcao, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($vendasDiretasBalcao), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #15803d;"></span>
                                (+) Saldo Líquido Retido em Carteiras (Recargas − Gasto)
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                +R$ <?php echo number_format($saldoRetidoPositivo, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($saldoRetidoPositivo), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- GRUPO 2 DA LEGENDA: CUSTOS (CMV) E PAGAMENTO DE FORNECEDORES (RECIBOS SEPARADOS DO BLOCO DE DESPESAS) -->
                <div class="legend-section-title">
                    <span>2. Custos (CMV) & Pagamento de Fornecedores (Recibos)</span>
                    <span class="font-tabular">Valor (R$) | % Giro</span>
                </div>
                <table class="legend-table">
                    <tbody>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #d97706;"></span>
                                (-) Custo das Mercadorias Vendidas (CMV — <?php echo number_format($pctCmv, 1, ',', '.'); ?>% das vendas)
                            </td>
                            <td style="text-align: center; width: 34%;" class="font-tabular">
                                - R$ <?php echo number_format($cmv, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($cmv), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #9f1239;"></span>
                                (-) Despesa: Pagamento de Fornecedores (Recibos Emitidos)
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                - R$ <?php echo number_format($despesasFornecedores, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($despesasFornecedores), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- GRUPO 3 DA LEGENDA: BLOCO DE DESPESAS OPERACIONAIS POR CATEGORIA -->
                <div class="legend-section-title">
                    <span>3. Bloco de Despesas Operacionais por Categoria</span>
                    <span class="font-tabular">Valor (R$) | % Giro</span>
                </div>
                <table class="legend-table">
                    <tbody>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #dc2626;"></span>
                                (-) Despesa: Pessoal & Salários
                            </td>
                            <td style="text-align: center; width: 34%;" class="font-tabular">
                                - R$ <?php echo number_format($despesasPessoal, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($despesasPessoal), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #475569;"></span>
                                (-) Despesa: Administrativas
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                - R$ <?php echo number_format($despesasAdmin, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($despesasAdmin), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #ea580c;"></span>
                                (-) Despesa: Manutenção & Equipamentos
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                - R$ <?php echo number_format($despesasManutencao, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($despesasManutencao), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="legend-dot" style="background: #7c3aed;"></span>
                                (-) Despesa: Vendas, Embalagens, Financeiras & Outras
                            </td>
                            <td style="text-align: center;" class="font-tabular">
                                - R$ <?php echo number_format($despesasVendasFinOutras, 2, ',', '.'); ?> (<?php echo number_format($calcPctFatia($despesasVendasFinOutras), 1, ',', '.'); ?>%)
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GRUPO 4 DA LEGENDA: EXPLICAÇÃO DOS SALDOS E RESULTADOS QUE NÃO SÃO FATIAS ISOLADAS -->
            <div class="legend-explain-box">
                <div style="font-size: 6.8pt; font-weight: 800; text-transform: uppercase; border-bottom: 1.5px solid #000000; padding-bottom: 2px; margin-bottom: 3px;">
                    4. Explicação da Legenda — Saldos, Subtrações e Resultados do DRE (Equações Contábeis):
                </div>
                <div style="margin-bottom: 3px; font-size: 6.45pt;">
                    * Como o gráfico circular representa proporções positivas de giro (Entradas vs Saídas), os totalizadores, deduções e resultados líquidos (inclusive saldos negativos) são demonstrados abaixo:
                </div>
                <div class="legend-explain-row font-tabular">
                    <span>(=) Arrecadação Real na Conta (Recargas R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?> + Direto R$ <?php echo number_format($vendasDiretasBalcao, 2, ',', '.'); ?>):</span>
                    <span>R$ <?php echo number_format($arrecadacaoLiquida, 2, ',', '.'); ?></span>
                </div>
                <div class="legend-explain-row font-tabular">
                    <span>(=) Receita Líquida de Vendas (Online + Balcão − Estornos R$ <?php echo number_format($totalDeducoes, 2, ',', '.'); ?>):</span>
                    <span>R$ <?php echo number_format($receitaLiquida, 2, ',', '.'); ?></span>
                </div>
                <div class="legend-explain-row font-tabular">
                    <span>(=) Lucro Bruto Operacional (Vendas Líquidas − CMV | Margem <?php echo number_format($margemBrutaPercent, 1, ',', '.'); ?>%):</span>
                    <span>R$ <?php echo number_format($lucroBruto, 2, ',', '.'); ?></span>
                </div>
                <div class="legend-explain-row font-tabular">
                    <span>(=) Resultado Líquido Vendas (Lucro Bruto − Fornec. R$ <?php echo number_format($despesasFornecedores, 2, ',', '.'); ?> − Desp. R$ <?php echo number_format($despesasOperacionaisPuras, 2, ',', '.'); ?>):</span>
                    <span>R$ <?php echo number_format($resultadoLiquido, 2, ',', '.'); ?></span>
                </div>
                <div class="legend-explain-row font-tabular" style="padding-top: 2px;">
                    <span>(=) RESULTADO FINANCEIRO LÍQUIDO EM CONTA (Lucro Vendas + Saldo Retido):</span>
                    <span>R$ <?php echo number_format($resultadoCaixa, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 2: DETALHAMENTO DOS PRODUTOS MAIS RENTÁVEIS DO PERÍODO -->
    <div class="section-banner">
        2. Detalhamento dos Produtos Mais Rentáveis do Período
    </div>

    <table class="table-doc-excel">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 34%; text-align: left;">Produto / Lanche</th>
                <th style="width: 12%; text-align: center;">Qtd. Vendida</th>
                <th style="width: 15%; text-align: center;">Receita Bruta</th>
                <th style="width: 15%; text-align: center;">Custo Estimado</th>
                <th style="width: 18%; text-align: center;">Lucro Estimado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($produtosRentaveis)): ?>
                <?php
                $somaQtd = 0;
                $somaRecProd = 0.0;
                $somaCustoProd = 0.0;
                $somaLucroProd = 0.0;
                foreach ($produtosRentaveis as $idx => $prod):
                    $qtdP   = (int)($prod['quantidade'] ?? 0);
                    $recP   = (float)($prod['receita'] ?? 0.0);
                    $custoP = (float)($prod['custo'] ?? 0.0);
                    $lucroP = (float)($prod['lucro_estimado'] ?? 0.0);
                    $somaQtd       += $qtdP;
                    $somaRecProd   += $recP;
                    $somaCustoProd += $custoP;
                    $somaLucroProd += $lucroP;
                ?>
                <tr>
                    <td style="text-align: center;" class="font-tabular"><?php echo $idx + 1; ?>º</td>
                    <td style="font-weight: 700;"><?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td style="text-align: center;" class="font-tabular"><?php echo number_format($qtdP, 0, ',', '.'); ?> un</td>
                    <td style="text-align: center;" class="font-tabular">R$ <?php echo number_format($recP, 2, ',', '.'); ?></td>
                    <td style="text-align: center;" class="font-tabular">R$ <?php echo number_format($custoP, 2, ',', '.'); ?></td>
                    <td style="text-align: center; font-weight: 700;" class="font-tabular">R$ <?php echo number_format($lucroP, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 12px !important;">Nenhuma venda de produto registrada no período selecionado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($produtosRentaveis)): ?>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;">TOTAL DOS PRODUTOS MAIS RENTÁVEIS LISTADOS:</td>
                <td style="text-align: center;" class="font-tabular"><?php echo number_format($somaQtd, 0, ',', '.'); ?> un</td>
                <td style="text-align: center;" class="font-tabular">R$ <?php echo number_format($somaRecProd, 2, ',', '.'); ?></td>
                <td style="text-align: center;" class="font-tabular">R$ <?php echo number_format($somaCustoProd, 2, ',', '.'); ?></td>
                <td style="text-align: center;" class="font-tabular">R$ <?php echo number_format($somaLucroProd, 2, ',', '.'); ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <!-- Assinaturas e Rodapé -->
    <div class="footer-signatures">
        <div class="signature-line">
            <strong>Gerente Operacional / Financeiro</strong><br>
            Cantina Web — Colégio Monteiro Lobato
        </div>
        <div class="signature-line">
            <strong>Direção Administrativa / Escolar</strong><br>
            Colégio Monteiro Lobato
        </div>
    </div>

    <div class="footer-doc">
        <div>Colégio Monteiro Lobato • Sistema Integrado Cantina Web • DRE Visual Unificado & Produtos Mais Rentáveis</div>
        <div class="font-tabular">Página 1 de 1</div>
    </div>
</div>
</div> <!-- /#document-zoom-area -->

<!-- Biblioteca Local de Gráficos -->
<script src="<?php echo $baseUrl; ?>/assets/js/chart.min.js"></script>
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
}
function alterarZoom(delta) { aplicarZoom(currentZoom + delta); }
function resetarZoom() { aplicarZoom(1.0); }

window.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
        e.preventDefault(); alterarZoom(0.15);
    } else if ((e.ctrlKey || e.metaKey) && (e.key === '-' || e.key === '_')) {
        e.preventDefault(); alterarZoom(-0.15);
    } else if ((e.ctrlKey || e.metaKey) && e.key === '0') {
        e.preventDefault(); resetarZoom();
    }
});

window.addEventListener('wheel', function(e) {
    if (e.ctrlKey) {
        e.preventDefault();
        alterarZoom(e.deltaY < 0 ? 0.1 : -0.1);
    }
}, { passive: false });

window.addEventListener('beforeprint', function() {
    const zoomArea = document.getElementById('document-zoom-area');
    if (zoomArea) zoomArea.style.zoom = 1;
});
window.addEventListener('afterprint', function() {
    const zoomArea = document.getElementById('document-zoom-area');
    if (zoomArea) zoomArea.style.zoom = currentZoom;
});

function fecharJanela() {
    window.close();
    setTimeout(function() {
        if (!window.closed) {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '<?php echo $baseUrl; ?>/financeiro/dre';
            }
        }
    }, 150);
}

document.addEventListener('DOMContentLoaded', function() {
    if (!window.Chart) return;

    const ctxCanais = document.getElementById('chartCanaisVenda');
    if (ctxCanais) {
        const valOnline       = <?php echo $consumoSaldoOnline; ?>;
        const valBalcaoSaldo  = <?php echo $consumoSaldoBalcao; ?>;
        const valBalcaoDir    = <?php echo $vendasDiretasBalcao; ?>;
        const valSaldoRetido  = <?php echo $saldoRetidoPositivo; ?>;
        const valCmv          = <?php echo $cmv; ?>;
        const valDespFornec   = <?php echo $despesasFornecedores; ?>;
        const valDespPessoal  = <?php echo $despesasPessoal; ?>;
        const valDespAdmin    = <?php echo $despesasAdmin; ?>;
        const valDespManut    = <?php echo $despesasManutencao; ?>;
        const valDespOutras   = <?php echo $despesasVendasFinOutras; ?>;

        const valoresDre = [
            valOnline,
            valBalcaoSaldo,
            valBalcaoDir,
            valSaldoRetido,
            valCmv,
            valDespFornec,
            valDespPessoal,
            valDespAdmin,
            valDespManut,
            valDespOutras
        ];

        const somaTotal = valoresDre.reduce(function(acc, v) { return acc + v; }, 0);

        new Chart(ctxCanais, {
            type: 'doughnut',
            data: {
                labels: [
                    '(+) Vendas Online (Cantina Web)',
                    '(+) Vendas Balcão (Saldo PDV)',
                    '(+) Vendas Diretas Balcão (Din/Pix)',
                    '(+) Saldo Retido em Carteiras',
                    '(-) Custo das Mercadorias (CMV)',
                    '(-) Despesa: Pagamento de Fornecedores (Recibos)',
                    '(-) Despesa: Pessoal & Salários',
                    '(-) Despesa: Administrativas',
                    '(-) Despesa: Manutenção & Equipamentos',
                    '(-) Despesa: Vendas, Embalagens, Fin. & Outras'
                ],
                datasets: [{
                    data: somaTotal > 0 ? valoresDre : [1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                    backgroundColor: [
                        '#1d4ed8',
                        '#0284c7',
                        '#0d9488',
                        '#15803d',
                        '#d97706',
                        '#9f1239',
                        '#dc2626',
                        '#475569',
                        '#ea580c',
                        '#7c3aed'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                if (somaTotal <= 0) return c.label + ': R$ 0,00 (0%)';
                                const v = c.parsed;
                                const pct = ((v / somaTotal) * 100).toFixed(1);
                                return c.label + ': R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + ' (' + pct + '% do giro DRE)';
                            }
                        }
                    }
                },
                cutout: '52%'
            }
        });
    }
});
</script>
</body>
</html>
