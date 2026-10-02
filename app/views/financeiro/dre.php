<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: DRE Gerencial Completo (Demonstração do Resultado do Exercício)
 * 
 * Comparativo Mês a Mês (Janeiro a Dezembro + Total do Ano) com apuração de Receitas,
 * Deduções, CMV, Lucro Bruto, Despesas Operacionais por Categoria, Resultado Líquido Real
 * e Painel de Gráficos Interativos locais.
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/alterações sistema guideline/Estrutura de uma DRE.pdf e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$anoAtual = $ano ?? (int)date('Y');
$meses = $dre['meses'] ?? [];
$totais = $dre['totais'] ?? [];
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1440px; margin: 0 auto;">
    <!-- Barra de Contexto da Página -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Controladoria & Finanças</span>
                <span class="code-tag font-tabular">DRE Gerencial Oficial</span>
            </div>
            <h1 class="h4 font-weight-bold text-dark mb-1" style="font-size: 18px; font-weight: 700; color: #0f172a;">
                Demonstração do Resultado do Exercício — Ano <?php echo $anoAtual; ?>
            </h1>
            <p class="text-secondary text-microcopy mb-0">
                Comparativo mensal contábil de receitas, deduções de vendas, CMV de mercadorias, despesas fixas/variáveis e lucro líquido real.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Alternador de Ano Letivo / Exercício -->
            <form action="<?php echo $baseUrl; ?>/financeiro/dre" method="GET" class="d-inline-flex align-items-center gap-1 m-0">
                <span class="text-microcopy text-secondary font-weight-bold text-uppercase me-1">Ano:</span>
                <select name="ano" class="form-select form-select-sm font-tabular" style="width: 100px;" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y') + 1; $y >= 2024; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y === $anoAtual) ? 'selected' : ''; ?>>
                            <?php echo $y; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>

            <a href="<?php echo $baseUrl; ?>/financeiro/despesas" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                <span>Lançar Despesas</span>
            </a>

            <!-- Trio de Saídas e Exportações -->
            <div class="btn-group">
                <a href="<?php echo $baseUrl; ?>/financeiro/dre/imprimir?ano=<?php echo $anoAtual; ?>" target="_blank" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Visualizar espelho A4 de impressão">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                    <span>Imprimir (A4)</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/financeiro/dre/pdf?ano=<?php echo $anoAtual; ?>" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Baixar relatório oficial em PDF compilado">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.603 14.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.127.24-.4.655-.657 1.2-.767.433-.088.94-.108 1.487-.066.36.027.734.076 1.11.144.33-.374.657-.79.967-1.233.568-.813.99-1.637 1.258-2.434.204-.606.326-1.144.37-1.597.042-.435-.02-.756-.18-.946-.145-.172-.37-.24-.653-.199-.333.048-.602.21-.79.479-.196.282-.294.67-.294 1.14 0 .363.073.742.217 1.126.155.412.378.835.66 1.254-.182.493-.388.976-.615 1.439-.24.492-.505.952-.788 1.368-.42.062-.84.103-1.25.122-.44.02-.857.01-1.24-.031a2.827 2.827 0 0 1-.58-.124zm2.84-2.82c-.3-.396-.54-.805-.72-1.21-.14-.325-.23-.623-.27-.887-.02-.12-.03-.223-.03-.306 0-.312.06-.523.18-.62.08-.065.18-.088.3-.067.14.024.25.105.32.234.07.13.1.32.1.56a4.2 4.2 0 0 1-.08.625 5.56 5.56 0 0 1-.22.68c-.06.15-.12.29-.18.423z"/></svg>
                    <span>Baixar PDF</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/financeiro/dre/csv?ano=<?php echo $anoAtual; ?>" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Exportar dados para Excel">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H9V3.5h.5zM3 11.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1zm4.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2z"/></svg>
                    <span>CSV (Excel)</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Stat-Strip: Síntese Financeira Consolidada do Exercício (Ano) -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Receita Bruta Total</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format((float)($totais['receita_bruta'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Online: R$ <?php echo number_format((float)($totais['receita_online'] ?? 0), 2, ',', '.'); ?> | Balcão: R$ <?php echo number_format((float)($totais['receita_balcao'] ?? 0), 2, ',', '.'); ?>
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Receita Líquida Real</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format((float)($totais['receita_liquida'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                (-) Estornos: R$ <?php echo number_format((float)($totais['total_deducoes'] ?? 0), 2, ',', '.'); ?>
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Lucro Bruto (Margem)</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format((float)($totais['lucro_bruto'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Margem Bruta: <?php echo number_format((float)($totais['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Despesas Operacionais</span>
            <span class="stat-value font-tabular text-dark" style="color: #b91c1c !important;">
                R$ <?php echo number_format((float)($totais['total_despesas'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Fixas + Variáveis no exercício
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Resultado Líquido (Exercício)</span>
            <span class="stat-value font-tabular <?php echo ($totais['lucro_positivo'] ?? true) ? 'text-success' : 'text-danger'; ?>" style="<?php echo ($totais['lucro_positivo'] ?? true) ? 'color: #15803d !important;' : 'color: #dc2626 !important;'; ?>">
                R$ <?php echo number_format((float)($totais['resultado_liquido'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Margem Líquida: <strong><?php echo number_format((float)($totais['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</strong>
            </span>
        </div>
    </div>

    <!-- Navegação por Abas: Tabela Comparativa vs Gráficos Interativos -->
    <ul class="nav nav-tabs nav-enterprise mb-3" id="dreTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active font-weight-bold" id="tab-tabela-btn" data-bs-toggle="tab" data-bs-target="#tab-tabela" type="button" role="tab" aria-selected="true">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h4v-3zM1 4h4v3H1V4zm0 4h4v3H1V8zm0 4h4v3H1v-3zm5-8h4v3H6V4zm0 4h4v3H6V8zm0 4h4v3H6v-3z"/></svg>
                Demonstrativo Mês a Mês (Planilha Oficial)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link font-weight-bold" id="tab-graficos-btn" data-bs-toggle="tab" data-bs-target="#tab-graficos" type="button" role="tab" aria-selected="false">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2zm1 12h2V2h-2v12zm-5 0h2V7H7v7zm-5 0h2v-3H2v3z"/></svg>
                Painel Visual & Gráficos Interativos
            </button>
        </li>
    </ul>

    <div class="tab-content" id="dreTabsContent">
        <!-- ABA 1: Tabela Comparativa Mês a Mês (Estilo Planilha Modelo Aethos) -->
        <div class="tab-pane fade show active" id="tab-tabela" role="tabpanel">
            <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table table-enterprise table-dre mb-0">
                        <thead>
                            <tr class="header-meses">
                                <th class="cell-left sticky-col" style="min-width: 250px; background: #f8fafc; z-index: 2;">Estrutura do DRE</th>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <th class="cell-right font-tabular" style="min-width: 82px;">
                                        <?php echo $mesesAbrev[$m]; ?>/<?php echo substr((string)$anoAtual, 2); ?>
                                    </th>
                                <?php endfor; ?>
                                <th class="cell-right font-tabular col-total-ano" style="min-width: 110px; background: #f1f5f9; font-weight: 700;">
                                    TOTAL <?php echo $anoAtual; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- SEÇÃO 1: RECEITA BRUTA -->
                            <tr class="dre-row-header-secao">
                                <td class="cell-left sticky-col font-weight-bold" style="color: #0f172a;">(+) RECEITA BRUTA DE VENDAS</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular font-weight-bold">
                                        <?php echo number_format((float)($meses[$m]['receita_bruta'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold col-total-ano">
                                    <?php echo number_format((float)($totais['receita_bruta'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Vendas Cantina Web (Online)</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['receita_online'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['receita_online'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Vendas Presenciais no Balcão</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['receita_balcao'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['receita_balcao'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <!-- SEÇÃO 2: DEDUÇÕES E ESTORNOS -->
                            <tr class="dre-row-header-secao">
                                <td class="cell-left sticky-col font-weight-bold text-danger">(-) DEDUÇÕES E ABATIMENTOS</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular font-weight-bold text-danger">
                                        <?php echo number_format((float)($meses[$m]['total_deducoes'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold text-danger col-total-ano">
                                    <?php echo number_format((float)($totais['total_deducoes'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Estornos de Balcão</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['estornos_balcao'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['estornos_balcao'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Estornos Pedidos Online</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['estornos_online'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['estornos_online'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <!-- SEÇÃO 3: RECEITA LÍQUIDA -->
                            <tr class="dre-total-destaque" style="background: #f8fafc; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                                <td class="cell-left sticky-col font-weight-bold" style="color: #1d4ed8;">(=) RECEITA LÍQUIDA DE VENDAS</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular font-weight-bold" style="color: #1d4ed8;">
                                        <?php echo number_format((float)($meses[$m]['receita_liquida'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold col-total-ano" style="color: #1d4ed8; background: #e0f2fe;">
                                    <?php echo number_format((float)($totais['receita_liquida'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <!-- SEÇÃO 4: CUSTO DE VENDAS (CMV) -->
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-danger ps-3">(-) CUSTO DAS MERCADORIAS VENDIDAS (CMV)</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-danger">
                                        <?php echo number_format((float)($meses[$m]['cmv'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-danger col-total-ano">
                                    <?php echo number_format((float)($totais['cmv'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <!-- SEÇÃO 5: LUCRO BRUTO -->
                            <tr class="dre-total-destaque" style="background: #f8fafc; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                                <td class="cell-left sticky-col font-weight-bold" style="color: #0f172a;">(=) LUCRO BRUTO</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular font-weight-bold">
                                        <?php echo number_format((float)($meses[$m]['lucro_bruto'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold col-total-ano" style="background: #f1f5f9;">
                                    <?php echo number_format((float)($totais['lucro_bruto'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-metric-row" style="font-size: 11px; color: #64748b;">
                                <td class="cell-left sticky-col text-secondary ps-4">Margem Bruta (%)</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular">
                                        <?php echo number_format((float)($meses[$m]['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold col-total-ano">
                                    <?php echo number_format((float)($totais['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%
                                </td>
                            </tr>

                            <!-- SEÇÃO 6: DESPESAS OPERACIONAIS -->
                            <tr class="dre-row-header-secao">
                                <td class="cell-left sticky-col font-weight-bold text-danger">(-) DESPESAS OPERACIONAIS</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular font-weight-bold text-danger">
                                        <?php echo number_format((float)($meses[$m]['total_despesas'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold text-danger col-total-ano">
                                    <?php echo number_format((float)($totais['total_despesas'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Despesas Administrativas</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['despesas_administrativas'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['despesas_administrativas'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Pessoal & Salários</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['despesas_pessoal'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['despesas_pessoal'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Vendas, Marketing & Embalagens</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['despesas_vendas'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['despesas_vendas'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Manutenção & Equipamentos</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['despesas_manutencao'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['despesas_manutencao'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Despesas Financeiras & Tarifas</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['despesas_financeiras'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['despesas_financeiras'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <tr class="dre-subitem">
                                <td class="cell-left sticky-col text-secondary ps-4">Outras Despesas</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular text-secondary">
                                        <?php echo number_format((float)($meses[$m]['despesas_outras'] ?? 0), 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular text-secondary col-total-ano">
                                    <?php echo number_format((float)($totais['despesas_outras'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <!-- SEÇÃO 7: RESULTADO LÍQUIDO DO EXERCÍCIO -->
                            <tr class="dre-resultado-final" style="background: #f8fafc; border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a;">
                                <td class="cell-left sticky-col font-weight-bold" style="font-size: 13px; color: #0f172a;">
                                    (=) RESULTADO LÍQUIDO DO EXERCÍCIO
                                </td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <?php $lucroM = (float)($meses[$m]['resultado_liquido'] ?? 0); ?>
                                    <td class="cell-right font-tabular font-weight-bold <?php echo ($lucroM >= 0) ? 'text-success' : 'text-danger'; ?>" style="<?php echo ($lucroM >= 0) ? 'color: #15803d !important;' : 'color: #dc2626 !important;'; ?>">
                                        <?php echo number_format($lucroM, 2, ',', '.'); ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold col-total-ano <?php echo ($totais['lucro_positivo'] ?? true) ? 'text-success' : 'text-danger'; ?>" style="font-size: 13px; <?php echo ($totais['lucro_positivo'] ?? true) ? 'color: #15803d !important; background: #dcfce7;' : 'color: #dc2626 !important; background: #fee2e2;'; ?>">
                                    R$ <?php echo number_format((float)($totais['resultado_liquido'] ?? 0), 2, ',', '.'); ?>
                                </td>
                            </tr>

                            <tr class="dre-metric-row" style="font-size: 11px; color: #475569; background: #f8fafc;">
                                <td class="cell-left sticky-col text-secondary ps-4">Margem Líquida Real (%)</td>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <td class="cell-right font-tabular font-weight-medium">
                                        <?php echo number_format((float)($meses[$m]['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%
                                    </td>
                                <?php endfor; ?>
                                <td class="cell-right font-tabular font-weight-bold col-total-ano">
                                    <?php echo number_format((float)($totais['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ABA 2: Painel de Gráficos Interativos (Visual Analytics) -->
        <div class="tab-pane fade" id="tab-graficos" role="tabpanel">
            <div class="row g-3">
                <!-- Gráfico 1: Evolução Mensal (Receita vs Despesas vs Lucro) -->
                <div class="col-12 col-lg-8">
                    <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Tendência Financeira</span>
                                <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Evolução Mensal: Receita Líquida vs Custo Total vs Lucro Real</h6>
                            </div>
                            <span class="code-tag font-tabular">Ano <?php echo $anoAtual; ?></span>
                        </div>
                        <div style="height: 280px; position: relative;">
                            <canvas id="chartEvolucaoMensal"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 2: Composição da Receita (Online vs Balcão) -->
                <div class="col-12 col-lg-4">
                    <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Canais de Venda</span>
                                <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Vendas Online vs Balcão</h6>
                            </div>
                            <span class="code-tag font-tabular">Foco Web</span>
                        </div>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartCanaisVenda"></canvas>
                        </div>
                        <div class="pt-2 text-center text-microcopy text-secondary border-top mt-2">
                            <span>Online: R$ <?php echo number_format((float)($totais['receita_online'] ?? 0), 2, ',', '.'); ?></span> &bull; 
                            <span>Balcão: R$ <?php echo number_format((float)($totais['receita_balcao'] ?? 0), 2, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 3: Despesas Operacionais por Categoria -->
                <div class="col-12 col-lg-5">
                    <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Estrutura de Custos</span>
                                <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Despesas Operacionais por Categoria</h6>
                            </div>
                            <span class="code-tag font-tabular">R$ <?php echo number_format((float)($totais['total_despesas'] ?? 0), 2, ',', '.'); ?></span>
                        </div>
                        <div style="height: 240px; position: relative;">
                            <canvas id="chartDespesasCat"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Gráfico 4: Ranking dos Produtos Mais Rentáveis -->
                <div class="col-12 col-lg-7">
                    <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Rentabilidade por Item</span>
                                <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Curva dos 8 Produtos Mais Rentáveis do Ano</h6>
                            </div>
                            <span class="code-tag font-tabular">Lucro Estimado</span>
                        </div>
                        <div style="height: 240px; position: relative;">
                            <canvas id="chartProdutosRentaveis"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script Local de Gráficos (Sem dependência de CDN/Internet) -->
<script src="<?php echo $baseUrl; ?>/assets/js/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dadosGraficos = <?php echo json_encode($graficos, JSON_UNESCAPED_UNICODE); ?>;

    if (!window.Chart) {
        console.warn('Chart.js local não carregado.');
        return;
    }

    // Configuração global de fontes
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#475569';

    // 1. Gráfico de Evolução Mensal
    const ctxEvolucao = document.getElementById('chartEvolucaoMensal');
    if (ctxEvolucao) {
        new Chart(ctxEvolucao, {
            type: 'line',
            data: {
                labels: dadosGraficos.labels,
                datasets: [
                    {
                        label: 'Receita Líquida (R$)',
                        data: dadosGraficos.receitas,
                        borderColor: '#1d4ed8',
                        backgroundColor: 'rgba(29, 78, 216, 0.08)',
                        fill: true,
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: 'Custos + Despesas (R$)',
                        data: dadosGraficos.despesas,
                        borderColor: '#dc2626',
                        backgroundColor: 'transparent',
                        borderDash: [4, 4],
                        borderWidth: 1.5,
                        pointRadius: 2,
                    },
                    {
                        label: 'Lucro Líquido Real (R$)',
                        data: dadosGraficos.lucros,
                        borderColor: '#15803d',
                        backgroundColor: 'rgba(21, 128, 61, 0.1)',
                        fill: true,
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return c.dataset.label + ': R$ ' + c.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Canais de Venda (Online vs Balcão)
    const ctxCanais = document.getElementById('chartCanaisVenda');
    if (ctxCanais) {
        const totalOnline = dadosGraficos.online.reduce((a, b) => a + b, 0);
        const totalBalcao = dadosGraficos.balcao.reduce((a, b) => a + b, 0);

        new Chart(ctxCanais, {
            type: 'doughnut',
            data: {
                labels: ['Vendas Cantina Web (Online)', 'Vendas Balcão (Presencial)'],
                datasets: [{
                    data: [totalOnline, totalBalcao],
                    backgroundColor: ['#1d4ed8', '#f59e0b'],
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                const val = c.parsed;
                                const tot = totalOnline + totalBalcao;
                                const pct = tot > 0 ? ((val / tot) * 100).toFixed(1) : 0;
                                return c.label + ': R$ ' + val.toLocaleString('pt-BR', {minimumFractionDigits: 2}) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Gráfico de Despesas por Categoria
    const ctxDesp = document.getElementById('chartDespesasCat');
    if (ctxDesp) {
        const catLabels = Object.keys(dadosGraficos.despesas_por_cat);
        const catValues = Object.values(dadosGraficos.despesas_por_cat);

        new Chart(ctxDesp, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Total no Ano (R$)',
                    data: catValues,
                    backgroundColor: '#64748b',
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return 'Total: R$ ' + c.parsed.x.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        }
                    }
                }
            }
        });
    }

    // 4. Gráfico dos Produtos Mais Rentáveis
    const ctxRentaveis = document.getElementById('chartProdutosRentaveis');
    if (ctxRentaveis && dadosGraficos.produtos_rentaveis.length > 0) {
        const prodLabels = dadosGraficos.produtos_rentaveis.map(p => p.descricao.substring(0, 18));
        const prodReceitas = dadosGraficos.produtos_rentaveis.map(p => p.receita);
        const prodLucros = dadosGraficos.produtos_rentaveis.map(p => p.lucro_estimado);

        new Chart(ctxRentaveis, {
            type: 'bar',
            data: {
                labels: prodLabels,
                datasets: [
                    {
                        label: 'Receita Total (R$)',
                        data: prodReceitas,
                        backgroundColor: '#cbd5e1',
                        borderRadius: 3,
                    },
                    {
                        label: 'Lucro Estimado (R$)',
                        data: prodLucros,
                        backgroundColor: '#15803d',
                        borderRadius: 3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12 } },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return c.dataset.label + ': R$ ' + c.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        }
                    }
                }
            }
        });
    }
});
</script>

<style>
.table-dre {
    font-size: 12px;
}
.table-dre thead th {
    font-size: 11px;
    padding: 8px 10px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 2px solid #cbd5e1;
}
.table-dre tbody td {
    padding: 6px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #e2e8f0;
}
.sticky-col {
    position: sticky;
    left: 0;
    background: #ffffff;
    z-index: 1;
    border-right: 1px solid #cbd5e1 !important;
}
.col-total-ano {
    border-left: 1px solid #cbd5e1 !important;
}
.dre-row-header-secao td {
    background-color: #f8fafc;
    font-weight: 700;
}
.dre-subitem td {
    font-size: 11.5px;
}
</style>
