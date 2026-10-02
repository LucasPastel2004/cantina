<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Oficial A4 Timbrado — DRE Gerencial Comparativo Anual
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrões homologados na Fase 23
 * Orientação: A4 Paisagem (Landscape) com grade tipo Excel e fundo 100% branco
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$anoAtual = $ano ?? (int)date('Y');
$meses = $dre['meses'] ?? [];
$totais = $dre['totais'] ?? [];
$razaoSocial = $config->razao_social ?? 'COLÉGIO MONTEIRO LOBATO';
$cnpj = $config->cnpj ?? '';
$isPdf = $isPdf ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DRE Gerencial <?php echo $anoAtual; ?> — Colégio Monteiro Lobato</title>
    <style>
        /* ==========================================================================
           1. Diretivas Oficiais de Página A4 Paisagem (Landscape)
           ========================================================================== */
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }

        *, *:before, *:after {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 8.5pt;
            line-height: 1.25;
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
           2. Contêiner de Folha A4 Paisagem
           ========================================================================== */
        .doc-page {
            background-color: #ffffff;
            width: 280mm;
            max-width: calc(100% - 24px);
            min-height: 195mm;
            margin: 20px auto;
            padding: 8mm 10mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .doc-page {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }

        /* ==========================================================================
           3. Barra Superior de Controle (Apenas em Tela)
           ========================================================================== */
        .action-bar-top {
            background: #0f172a;
            color: #ffffff;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .action-bar-top .btn-action-nav {
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .action-bar-top .btn-action-nav:hover {
            background: #f1f5f9;
        }

        /* ==========================================================================
           4. Cabeçalho Escolar Oficial
           ========================================================================== */
        .header-box {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header-logo {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }
        .header-text {
            flex: 1;
        }
        .header-text h1 {
            font-size: 13pt;
            font-weight: 700;
            margin: 0 0 2px 0;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .header-text p {
            margin: 0;
            font-size: 8.5pt;
            color: #334155;
        }
        .header-meta {
            text-align: right;
            font-size: 8pt;
            color: #475569;
        }

        /* ==========================================================================
           5. Tabela DRE — Estética de Grade Excel com Linhas Pretas e Fundo 100% Branco
           ========================================================================== */
        table.table-dre-doc {
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
            font-size: 7.8pt;
            border: 1px solid #000000 !important;
            margin-top: 4px;
        }

        table.table-dre-doc th,
        table.table-dre-doc td {
            border: 1px solid #000000 !important;
            padding: 3.5px 5px !important;
            background-color: #ffffff !important;
            color: #000000 !important;
            word-break: break-word;
        }

        table.table-dre-doc thead th {
            font-weight: 700 !important;
            text-align: center;
            font-size: 7.5pt;
            text-transform: uppercase;
        }

        table.table-dre-doc thead {
            display: table-header-group;
        }

        table.table-dre-doc tfoot {
            display: table-footer-group;
        }

        table.table-dre-doc tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .col-desc {
            width: 22%;
            text-align: left !important;
        }
        .col-mes {
            width: 6%;
            text-align: right !important;
        }
        .col-total {
            width: 8%;
            text-align: right !important;
            font-weight: 700 !important;
        }

        .row-secao {
            font-weight: 700 !important;
        }
        .row-subitem td.col-desc {
            padding-left: 14px !important;
        }
        .row-total-destaque td {
            font-weight: 700 !important;
        }
        .row-resultado-final td {
            font-weight: 700 !important;
            border-top: 2px solid #000000 !important;
            border-bottom: 2px solid #000000 !important;
        }

        /* ==========================================================================
           6. Bloco de Assinaturas e Rodapé
           ========================================================================== */
        .footer-signatures {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            padding-top: 25px;
            page-break-inside: avoid;
        }
        .signature-line {
            width: 42%;
            border-top: 1px solid #000000;
            text-align: center;
            font-size: 8pt;
            color: #000000;
            padding-top: 4px;
        }
        .footer-doc {
            margin-top: 12px;
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            color: #475569;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }
    </style>
</head>
<body>

<?php if (!$isPdf): ?>
<div class="action-bar-top no-print">
    <div>
        <span style="font-weight: 700; font-size: 13px;">Cantina Web — Relatório Oficial DRE <?php echo $anoAtual; ?></span>
        <span style="color: #94a3b8; font-size: 11px; margin-left: 10px;">Visualização em Formato Paisagem A4</span>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="<?php echo $baseUrl; ?>/financeiro/dre?ano=<?php echo $anoAtual; ?>" class="btn-action-nav">
            ← Voltar ao DRE
        </a>
        <button type="button" class="btn-action-nav" onclick="window.print()">
            🖨 Imprimir Documento
        </button>
        <button type="button" class="btn-action-nav" onclick="fecharJanela()">
            ✕ Fechar Janela
        </button>
    </div>
</div>
<?php endif; ?>

<div class="doc-page">
    <!-- Cabeçalho Oficial -->
    <div class="header-box">
        <?php if (!empty($logoBase64)): ?>
            <img src="<?php echo $logoBase64; ?>" alt="Logotipo Oficial" class="header-logo">
        <?php endif; ?>
        <div class="header-text">
            <h1><?php echo htmlspecialchars($razaoSocial, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p>DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO (DRE) — EXERCÍCIO FISCAL <?php echo $anoAtual; ?></p>
        </div>
        <div class="header-meta">
            <?php if (!empty($cnpj)): ?>
                <div><strong>CNPJ:</strong> <?php echo htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div><strong>Emissão:</strong> <?php echo $dataEmissao; ?></div>
            <div><strong>Unidade:</strong> Cantina Escolar Geral</div>
        </div>
    </div>

    <!-- Tabela Comparativa DRE (Grade Estilo Excel) -->
    <table class="table-dre-doc">
        <thead>
            <tr>
                <th class="col-desc">Estrutura DRE</th>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <th class="col-mes font-tabular"><?php echo $mesesAbrev[$m]; ?>/<?php echo substr((string)$anoAtual, 2); ?></th>
                <?php endfor; ?>
                <th class="col-total font-tabular">TOTAL <?php echo $anoAtual; ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- 1. RECEITA BRUTA -->
            <tr class="row-secao">
                <td class="col-desc">(+) RECEITA BRUTA DE VENDAS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_bruta'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Cantina Web (Online)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_online'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_online'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas Presenciais no Balcão</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_balcao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_balcao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 2. DEDUÇÕES -->
            <tr class="row-secao">
                <td class="col-desc">(-) DEDUÇÕES E ABATIMENTOS (ESTORNOS)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_deducoes'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_deducoes'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Estornos de Balcão</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['estornos_balcao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['estornos_balcao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Estornos Pedidos Online</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['estornos_online'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['estornos_online'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 3. RECEITA LÍQUIDA -->
            <tr class="row-total-destaque">
                <td class="col-desc">(=) RECEITA LÍQUIDA DE VENDAS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['receita_liquida'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['receita_liquida'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 4. CMV -->
            <tr class="row-subitem">
                <td class="col-desc">(-) CUSTO DAS MERCADORIAS VENDIDAS (CMV)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['cmv'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['cmv'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 5. LUCRO BRUTO -->
            <tr class="row-total-destaque">
                <td class="col-desc">(=) LUCRO BRUTO</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['lucro_bruto'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['lucro_bruto'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-size: 7.2pt; color: #475569;">
                <td class="col-desc">Margem Bruta (%)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</td>
            </tr>

            <!-- 6. DESPESAS OPERACIONAIS -->
            <tr class="row-secao">
                <td class="col-desc">(-) DESPESAS OPERACIONAIS</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['total_despesas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['total_despesas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Despesas Administrativas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_administrativas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_administrativas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Pessoal & Salários</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_pessoal'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_pessoal'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Vendas, Marketing & Embalagens</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_vendas'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_vendas'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Manutenção & Equipamentos</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_manutencao'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_manutencao'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Despesas Financeiras & Tarifas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_financeiras'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_financeiras'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem">
                <td class="col-desc">Outras Despesas</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['despesas_outras'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['despesas_outras'] ?? 0), 2, ',', '.'); ?></td>
            </tr>

            <!-- 7. RESULTADO LÍQUIDO DO EXERCÍCIO -->
            <tr class="row-resultado-final">
                <td class="col-desc">(=) RESULTADO LÍQUIDO DO EXERCÍCIO</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['resultado_liquido'] ?? 0), 2, ',', '.'); ?></td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['resultado_liquido'] ?? 0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="row-subitem" style="font-size: 7.2pt; color: #000000; font-weight: 700;">
                <td class="col-desc">Margem Líquida Real (%)</td>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <td class="col-mes font-tabular"><?php echo number_format((float)($meses[$m]['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</td>
                <?php endfor; ?>
                <td class="col-total font-tabular"><?php echo number_format((float)($totais['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</td>
            </tr>
        </tbody>
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
        <div>Colégio Monteiro Lobato • Sistema Integrado Cantina Web • Módulo Financeiro & DRE</div>
        <div>Página 1 de 1</div>
    </div>
</div>

<script>
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
</script>
</body>
</html>
