<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Painel Financeiro & DRE Gerencial
 * 
 * Painel Visual de Gráficos Interativos sempre aberto com filtros de período por datas
 * (data inicial e final com atalhos rápidos), stat-strip analítico e emissão oficial
 * do demonstrativo do DRE contábil em tela de impressão A4 Paisagem, PDF e CSV.
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/alterações sistema guideline/Estrutura de uma DRE.pdf e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$anoAtual = $ano ?? (int)date('Y');
$dataInicio = $dataInicio ?? sprintf('%04d-01-01', $anoAtual);
$dataFim    = $dataFim ?? date('Y-m-d');
$totais = $totais ?? ($drePeriodo ?? ($dre['totais'] ?? []));
$produtosRentaveis = $graficos['produtos_rentaveis'] ?? [];
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1440px; margin: 0 auto;">

    <!-- Formulário Oficial de Filtro de Período e Emissão do DRE -->
    <form action="<?php echo $baseUrl; ?>/financeiro/dre" method="GET" id="formDre">

        <!-- Cabeçalho Executivo e Barra de Ações -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Controladoria & Finanças</span>
                    <span class="code-tag font-tabular">DRE Gerencial & Gráficos</span>
                </div>
                <h1 class="h4 font-weight-bold text-dark mb-1" style="font-size: 18px; font-weight: 700; color: #0f172a;">
                    Painel Financeiro & DRE Gerencial
                </h1>
                <p class="text-secondary text-microcopy mb-0">
                    Acompanhamento visual interativo de faturamento, canais de venda, custos e despesas com emissão sob demanda do DRE.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?php echo $baseUrl; ?>/financeiro/despesas" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Lançar e gerenciar despesas da cantina">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                    <span>Lançar Despesas</span>
                </a>

                <!-- Trio Oficial de Emissão do DRE -->
                <button type="submit" formaction="<?php echo $baseUrl; ?>/financeiro/dre/imprimir" formtarget="_blank" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Visualizar e imprimir o DRE oficial A4">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                    <span>Visualizar / Imprimir (A4)</span>
                </button>

                <button type="submit" formaction="<?php echo $baseUrl; ?>/financeiro/dre/pdf" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Baixar relatório oficial DRE em PDF timbrado">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.603 14.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.127.24-.4.655-.657 1.2-.767.433-.088.94-.108 1.487-.066.36.027.734.076 1.11.144.33-.374.657-.79.967-1.233.568-.813.99-1.637 1.258-2.434.204-.606.326-1.144.37-1.597.042-.435-.02-.756-.18-.946-.145-.172-.37-.24-.653-.199-.333.048-.602.21-.79.479-.196.282-.294.67-.294 1.14 0 .363.073.742.217 1.126.155.412.378.835.66 1.254-.182.493-.388.976-.615 1.439-.24.492-.505.952-.788 1.368-.42.062-.84.103-1.25.122-.44.02-.857.01-1.24-.031a2.827 2.827 0 0 1-.58-.124zm2.84-2.82c-.3-.396-.54-.805-.72-1.21-.14-.325-.23-.623-.27-.887-.02-.12-.03-.223-.03-.306 0-.312.06-.523.18-.62.08-.065.18-.088.3-.067.14.024.25.105.32.234.07.13.1.32.1.56a4.2 4.2 0 0 1-.08.625 5.56 5.56 0 0 1-.22.68c-.06.15-.12.29-.18.423z"/></svg>
                    <span>Baixar em PDF (A4)</span>
                </button>

                <button type="submit" formaction="<?php echo $baseUrl; ?>/financeiro/dre/csv" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" title="Exportar dados do DRE para planilha Excel">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H9V3.5h.5zM3 11.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1zm4.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2z"/></svg>
                    <span>Exportar Planilha (CSV)</span>
                </button>
            </div>
        </div>

        <!-- Bloco de Filtro de Período (Padrão Idêntico às Demais Telas do Sistema) -->
        <div class="card-enterprise mb-4" id="secaoFiltrosDre">
            <div class="card-enterprise-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                            Período: Data Inicial
                        </label>
                        <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                            Período: Data Final
                        </label>
                        <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-fill d-inline-flex align-items-center justify-content-center gap-1.5" style="height: 33px;">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
                            <span>Filtrar / Atualizar Gráficos</span>
                        </button>
                    </div>
                </div>

                <!-- Atalhos rápidos de período -->
                <div class="d-flex align-items-center gap-1.5 mt-2.5 pt-2 border-top flex-wrap">
                    <span class="text-microcopy text-muted me-1">Atalhos rápidos:</span>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-d'); ?>', '<?php echo date('Y-m-d'); ?>')">Hoje</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-d', strtotime('-1 day')); ?>', '<?php echo date('Y-m-d', strtotime('-1 day')); ?>')">Ontem</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-d', strtotime('monday this week')); ?>', '<?php echo date('Y-m-d'); ?>')">Esta Semana</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-01'); ?>', '<?php echo date('Y-m-d'); ?>')">Este Mês</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-01', strtotime('first day of last month')); ?>', '<?php echo date('Y-m-t', strtotime('last month')); ?>')">Mês Anterior</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-01-01'); ?>', '<?php echo date('Y-12-31'); ?>')">Ano Atual (<?php echo date('Y'); ?>)</button>
                </div>
            </div>
        </div>

    </form>

    <!-- Stat-Strip: Síntese Financeira Consolidada do Período Filtrado -->
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
                Margem Bruta: <strong><?php echo number_format((float)($totais['margem_bruta_percent'] ?? 0), 1, ',', '.'); ?>%</strong>
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Despesas Operacionais</span>
            <span class="stat-value font-tabular text-danger" style="color: #dc2626 !important;">
                R$ <?php echo number_format((float)($totais['total_despesas'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Fixas + Variáveis no período
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Resultado Líquido do Período</span>
            <span class="stat-value font-tabular <?php echo ($totais['lucro_positivo'] ?? true) ? 'text-success' : 'text-danger'; ?>" style="<?php echo ($totais['lucro_positivo'] ?? true) ? 'color: #15803d !important;' : 'color: #dc2626 !important;'; ?>">
                R$ <?php echo number_format((float)($totais['resultado_liquido'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Margem Líquida: <strong><?php echo number_format((float)($totais['margem_liquida_percent'] ?? 0), 1, ',', '.'); ?>%</strong>
            </span>
        </div>
    </div>

    <!-- Painel Visual & Gráficos Interativos (SEMPRE ABERTO E DIRETO) -->
    <div class="row g-3 mb-4">
        <!-- Gráfico 1: Evolução Temporal (Receita Líquida, Custos e Lucro) -->
        <div class="col-12 col-lg-8">
            <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; height: 100%;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Desempenho Temporal</span>
                        <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Evolução de Receitas, Custos e Lucro Real (R$)</h6>
                    </div>
                    <span class="code-tag font-tabular">Exercício <?php echo $anoAtual; ?></span>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartEvolucaoMensal"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfico 2: Canais de Venda (Online vs Balcão) -->
        <div class="col-12 col-lg-4">
            <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; height: 100%;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Origem das Vendas</span>
                        <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Vendas Online vs Balcão</h6>
                    </div>
                    <span class="code-tag font-tabular">Período</span>
                </div>
                <div style="height: 200px; position: relative;">
                    <canvas id="chartCanaisVenda"></canvas>
                </div>
                <div class="pt-2 text-center text-microcopy text-secondary border-top mt-2 font-tabular">
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
                        <h6 class="font-weight-bold mb-0 text-dark" style="font-size: 14px;">Curva dos 8 Produtos Mais Rentáveis do Período</h6>
                    </div>
                    <span class="code-tag font-tabular">Lucro Estimado</span>
                </div>
                <div style="height: 240px; position: relative;">
                    <canvas id="chartProdutosRentaveis"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Resumo dos Produtos Mais Rentáveis no Período -->
    <?php if (!empty($produtosRentaveis)): ?>
    <div class="card card-enterprise mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
            <span class="fw-semibold text-dark text-microcopy text-uppercase">Detalhamento dos Produtos Mais Rentáveis do Período</span>
            <span class="text-muted text-microcopy">Ordenado pelo faturamento consolidado</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover mb-0">
                <thead>
                    <tr style="background: #f8fafc; font-size: 11px; text-transform: uppercase; color: #475569;">
                        <th style="padding: 10px 14px;">#</th>
                        <th style="padding: 10px 14px;">Produto</th>
                        <th class="text-end" style="padding: 10px 14px;">Qtd. Vendida</th>
                        <th class="text-end" style="padding: 10px 14px;">Receita Bruta</th>
                        <th class="text-end" style="padding: 10px 14px;">Custo Estimado</th>
                        <th class="text-end" style="padding: 10px 14px;">Lucro Estimado</th>
                    </tr>
                </thead>
                <tbody style="font-size: 12.5px;">
                    <?php foreach ($produtosRentaveis as $idx => $prod): ?>
                    <tr>
                        <td style="padding: 10px 14px;" class="text-secondary font-tabular"><?php echo $idx + 1; ?>º</td>
                        <td style="padding: 10px 14px;" class="fw-medium text-dark"><?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular"><?php echo number_format((int)$prod['quantidade'], 0, ',', '.'); ?> un</td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular">R$ <?php echo number_format((float)$prod['receita'], 2, ',', '.'); ?></td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular text-danger">R$ <?php echo number_format((float)$prod['custo'], 2, ',', '.'); ?></td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular fw-bold text-success" style="color: #15803d !important;">
                            R$ <?php echo number_format((float)$prod['lucro_estimado'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Aviso Informativo sobre a Emissão do DRE em PDF/Impressão -->
    <div class="card p-3" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
        <div class="d-flex align-items-center gap-2">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" class="text-primary flex-shrink-0"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
            <div class="text-microcopy text-secondary" style="line-height: 1.45;">
                <strong class="text-dark">Emissão Oficial do Demonstrativo Contábil (DRE):</strong>
                A tabela analítica de DRE contábil com apuração formal de Receita Bruta, Deduções, CMV, Lucro Bruto e Despesas Operacionais é gerada sob demanda para o período selecionado (<strong><?php echo date('d/m/Y', strtotime($dataInicio)); ?> a <?php echo date('d/m/Y', strtotime($dataFim)); ?></strong>) através dos botões <strong>Visualizar / Imprimir (A4)</strong> ou <strong>Baixar em PDF (A4)</strong> no topo da página.
            </div>
        </div>
    </div>

</div>

<!-- Script Local de Gráficos (Sem dependência de CDN/Internet) -->
<script src="<?php echo $baseUrl; ?>/assets/js/chart.min.js"></script>

<script>
// Função utilitária para os atalhos rápidos de datas
function definirDatas(inicio, fim) {
    document.getElementById('dataInicio').value = inicio;
    document.getElementById('dataFim').value = fim;
    document.getElementById('formDre').submit();
}

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
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12, padding: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return c.dataset.label + ': R$ ' + c.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Canais de Venda (Online vs Balcão)
    const ctxCanais = document.getElementById('chartCanaisVenda');
    if (ctxCanais) {
        const totalOnline = <?php echo (float)($totais['receita_online'] ?? 0); ?>;
        const totalBalcao = <?php echo (float)($totais['receita_balcao'] ?? 0); ?>;

        new Chart(ctxCanais, {
            type: 'doughnut',
            data: {
                labels: ['Cantina Web (Online)', 'Presencial (Balcão)'],
                datasets: [{
                    data: [totalOnline, totalBalcao],
                    backgroundColor: ['#2563eb', '#0ea5e9'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 10 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                const v = c.parsed;
                                const tot = totalOnline + totalBalcao;
                                const pct = tot > 0 ? ((v / tot) * 100).toFixed(1) : 0;
                                return c.label + ': R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 3. Gráfico de Despesas Operacionais por Categoria
    const ctxDespesas = document.getElementById('chartDespesasCat');
    if (ctxDespesas) {
        const cats = dadosGraficos.despesas_por_cat || {};
        const catLabels = Object.keys(cats);
        const catValores = Object.values(cats);

        new Chart(ctxDespesas, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Total (R$)',
                    data: catValores,
                    backgroundColor: [
                        '#64748b', '#0284c7', '#d97706', '#dc2626', '#7c3aed', '#475569'
                    ],
                    borderRadius: 4
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
                                return 'R$ ' + c.parsed.x.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 4. Gráfico de Ranking dos Produtos Mais Rentáveis
    const ctxProdutos = document.getElementById('chartProdutosRentaveis');
    if (ctxProdutos) {
        const prods = dadosGraficos.produtos_rentaveis || [];
        const prodLabels = prods.map(p => p.descricao.length > 20 ? p.descricao.substring(0, 18) + '...' : p.descricao);
        const prodLucros = prods.map(p => p.lucro_estimado);
        const prodReceitas = prods.map(p => p.receita);

        new Chart(ctxProdutos, {
            type: 'bar',
            data: {
                labels: prodLabels,
                datasets: [
                    {
                        label: 'Lucro Estimado (R$)',
                        data: prodLucros,
                        backgroundColor: '#16a34a',
                        borderRadius: 4
                    },
                    {
                        label: 'Receita Bruta (R$)',
                        data: prodReceitas,
                        backgroundColor: '#93c5fd',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { boxWidth: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(c) {
                                return c.dataset.label + ': R$ ' + c.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function(v) { return 'R$ ' + v.toLocaleString('pt-BR'); }
                        },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
