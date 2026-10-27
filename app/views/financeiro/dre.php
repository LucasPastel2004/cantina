<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: DRE Gerencial Unificado (Emissão Sob Demanda: Relatório Texto, Relatório Gráfico & CSV)
 * 
 * Tela limpa de filtro de período para emissão sob demanda do DRE Unificado:
 * 1. Relatório Texto (DRE Contábil em Grade Mensal A4 Paisagem)
 * 2. Relatório Gráfico (DRE Visual Unificado + Lista dos Produtos Mais Rentáveis A4)
 * 3. Exportação para Planilha Excel (CSV)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl    = $baseUrl ?? '';
$anoAtual   = $ano ?? (int)date('Y');
$dataInicio = $dataInicio ?? sprintf('%04d-01-01', $anoAtual);
$dataFim    = $dataFim ?? date('Y-m-d');

$hoje              = date('Y-m-d');
$ontem             = date('Y-m-d', strtotime('-1 day'));
$inicioSemana      = date('Y-m-d', strtotime('monday this week'));
$fimSemana         = date('Y-m-d');
$inicioMes         = date('Y-m-01');
$fimMes            = date('Y-m-d');
$inicioMesAnterior = date('Y-m-01', strtotime('first day of last month'));
$fimMesAnterior    = date('Y-m-t', strtotime('last month'));
$inicioAno         = date('Y-01-01');
$fimAno            = date('Y-12-31');
?>

<div class="container-fluid p-0" style="max-width: 1440px; margin: 0 auto;">

    <!-- Topbar Institucional do DRE -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                <span class="text-secondary text-microcopy text-uppercase font-weight-bold">Controladoria & Finanças</span>
                <span class="code-tag font-tabular">DRE Unificado (Vendas, Custódia de Carteira & Caixa Real)</span>
            </div>
            <h1 class="h4 font-weight-bold mb-0 text-dark">Painel Financeiro & DRE Gerencial Unificado</h1>
            <p class="text-secondary text-microcopy mb-0 mt-1">
                Apuração unificada sem duplicidade: contabiliza recargas reais na conta, consumo de saldo em lanches, vendas diretas no balcão, custos (CMV) e despesas operacionais.
            </p>
        </div>
    </div>

    <!-- Bloco de Filtro de Período & Botão de Emissão com Escolha de Formato (Texto / Gráfico / CSV) -->
    <div class="card card-enterprise mb-4" id="secaoFiltrosDre" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: visible;">
        <div class="card-body p-3">
            <form action="<?php echo $baseUrl; ?>/financeiro/dre/imprimir" method="GET" target="_blank" id="formDre" onsubmit="abrirMenuEmissaoDre(event)">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">
                            Período: Data Inicial
                        </label>
                        <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">
                            Período: Data Final
                        </label>
                        <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <!-- Botão Imprimir / Salvar PDF (A4) com Seletor de Formato (Relatório Texto, Relatório Gráfico e CSV) -->
                    <div class="col-12 col-md-4 position-relative" id="wrapperMenuEmissaoDre">
                        <button type="button"
                                id="btnImprimirDre"
                                onclick="alternarMenuEmissaoDre(event)"
                                class="btn btn-enterprise btn-primary btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2"
                                style="height: 33px;"
                                aria-expanded="false">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                            <span>Imprimir / Salvar PDF (A4)</span>
                            <svg width="11" height="11" viewBox="0 0 16 16" fill="currentColor" class="ms-1"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
                        </button>

                        <!-- Menu Popover de Escolha do Tipo de Relatório DRE -->
                        <style>
                            .dre-format-item {
                                display: flex !important;
                                align-items: flex-start !important;
                                gap: 10px !important;
                                width: 100% !important;
                                height: auto !important;
                                min-height: 0 !important;
                                max-height: none !important;
                                padding: 10px 12px !important;
                                margin-bottom: 8px !important;
                                text-align: left !important;
                                white-space: normal !important;
                                background: #ffffff !important;
                                border: 1px solid #cbd5e1 !important;
                                border-radius: 6px !important;
                                cursor: pointer;
                                transition: background-color 0.15s ease, border-color 0.15s ease;
                                box-sizing: border-box !important;
                            }
                            .dre-format-item:last-child {
                                margin-bottom: 0 !important;
                            }
                            .dre-format-item:hover {
                                background: #f8fafc !important;
                                border-color: #94a3b8 !important;
                            }
                        </style>
                        <div id="menuOpcoesDre"
                             style="display: none; position: absolute; right: 0; left: 0; min-width: 340px; top: calc(100% + 6px); z-index: 1050; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15); padding: 10px;">
                            <div class="text-microcopy text-uppercase fw-bold text-secondary px-1 pb-2 border-bottom mb-2" style="font-size: 10.5px;">
                                Escolha o formato do DRE para o período:
                            </div>

                            <!-- Opção 1: Relatório Texto (Grade Contábil A4) -->
                            <button type="button"
                                    onclick="emitirDre('texto', event)"
                                    class="dre-format-item">
                                <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; border-radius: 6px; background: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; margin-top: 2px;">
                                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/><path d="M4.5 10.5A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
                                </span>
                                <div style="flex: 1; min-width: 0;">
                                    <div class="fw-bold text-dark" style="font-size: 12px; line-height: 1.3; margin-bottom: 3px;">Relatório Texto (DRE Contábil A4)</div>
                                    <div class="text-secondary" style="font-size: 11px; white-space: normal; line-height: 1.35; word-wrap: break-word;">
                                        Grade contábil tradicional mês a mês com Vendas, CMV, Despesas e Conciliação de Carteira.
                                    </div>
                                </div>
                            </button>

                            <!-- Opção 2: Relatório Gráfico (DRE Visual + Produtos Mais Rentáveis A4) -->
                            <button type="button"
                                    onclick="emitirDre('grafico', event)"
                                    class="dre-format-item">
                                <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; border-radius: 6px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; margin-top: 2px;">
                                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M7.5 1.018a7 7 0 0 0-4.79 11.566L7.5 7.793V1.018zm1 0V7.5h6.482A7.001 7.001 0 0 0 8.5 1.018zM14.982 8.5H8.207l-4.79 4.79A7 7 0 0 0 14.982 8.5zM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8z"/></svg>
                                </span>
                                <div style="flex: 1; min-width: 0;">
                                    <div class="fw-bold text-dark" style="font-size: 12px; line-height: 1.3; margin-bottom: 3px;">Relatório Gráfico (DRE Visual + Produtos Rentáveis A4)</div>
                                    <div class="text-secondary" style="font-size: 11px; white-space: normal; line-height: 1.35; word-wrap: break-word;">
                                        DRE Visual intuitivo (Vendas Online vs Presencial, Custos, Despesas) + Lista dos Produtos Mais Rentáveis.
                                    </div>
                                </div>
                            </button>

                            <!-- Opção 3: Exportar Planilha (CSV) -->
                            <button type="button"
                                    onclick="emitirDre('csv', event)"
                                    class="dre-format-item">
                                <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px; border-radius: 6px; background: #f8fafc; color: #15803d; border: 1px solid #cbd5e1; margin-top: 2px;">
                                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V9H9V3.5h.5zM3 11.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1zm4.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h2a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-2z"/></svg>
                                </span>
                                <div style="flex: 1; min-width: 0;">
                                    <div class="fw-bold text-dark" style="font-size: 12px; line-height: 1.3; margin-bottom: 3px;">Exportar Planilha (CSV)</div>
                                    <div class="text-secondary" style="font-size: 11px; white-space: normal; line-height: 1.35; word-wrap: break-word;">
                                        Baixar demonstrativo completo em formato CSV para Microsoft Excel.
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Atalhos rápidos de período -->
                <div class="d-flex align-items-center gap-2 mt-3 pt-2 border-top flex-wrap">
                    <span class="text-microcopy text-muted me-1">Atalhos rápidos:</span>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2 btn-atalho-dre" style="font-size: 11px;" onclick="definirDatasDre(event, '<?php echo $hoje; ?>', '<?php echo $hoje; ?>')">Hoje</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2 btn-atalho-dre" style="font-size: 11px;" onclick="definirDatasDre(event, '<?php echo $ontem; ?>', '<?php echo $ontem; ?>')">Ontem</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2 btn-atalho-dre" style="font-size: 11px;" onclick="definirDatasDre(event, '<?php echo $inicioSemana; ?>', '<?php echo $fimSemana; ?>')">Esta Semana</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2 btn-atalho-dre" style="font-size: 11px;" onclick="definirDatasDre(event, '<?php echo $inicioMes; ?>', '<?php echo $fimMes; ?>')">Este Mês</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2 btn-atalho-dre" style="font-size: 11px;" onclick="definirDatasDre(event, '<?php echo $inicioMesAnterior; ?>', '<?php echo $fimMesAnterior; ?>')">Mês Anterior</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2 btn-atalho-dre" style="font-size: 11px;" onclick="definirDatasDre(event, '<?php echo $inicioAno; ?>', '<?php echo $fimAno; ?>')">Ano Atual (<?php echo date('Y'); ?>)</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bloco Informativo de Orientação (Emissão sob Demanda) -->
    <div class="card card-enterprise" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
        <div class="card-body p-4 text-center">
            <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 42px; height: 42px; border-radius: 50%; background: #eff6ff; color: #1d4ed8;">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                    <path d="M4.5 10.5A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </div>
            <h2 class="h6 font-weight-bold text-dark mb-1">Geração do DRE Gerencial sob Demanda</h2>
            <p class="text-secondary text-microcopy mb-0 mx-auto" style="max-width: 640px;">
                Selecione o período desejado acima e clique em <strong>Imprimir / Salvar PDF (A4)</strong> para escolher entre o <strong>Relatório Texto (Grade Contábil A4)</strong>, o <strong>Relatório Gráfico (DRE Visual Unificado + Lista dos Produtos Mais Rentáveis)</strong> ou a <strong>Planilha Excel (CSV)</strong>.
            </p>
        </div>
    </div>

</div>

<script>
function alternarMenuEmissaoDre(event) {
    if (event && event.preventDefault) event.preventDefault();
    if (event && event.stopPropagation) event.stopPropagation();
    const menu = document.getElementById('menuOpcoesDre');
    const btn  = document.getElementById('btnImprimirDre');
    if (!menu) return;
    const visivel = menu.style.display === 'block';
    menu.style.display = visivel ? 'none' : 'block';
    if (btn) btn.setAttribute('aria-expanded', visivel ? 'false' : 'true');
}

function abrirMenuEmissaoDre(event) {
    if (event && event.preventDefault) event.preventDefault();
    alternarMenuEmissaoDre(event);
    return false;
}

function emitirDre(formato, event) {
    if (event && event.preventDefault) event.preventDefault();
    if (event && event.stopPropagation) event.stopPropagation();

    const inputInicio = document.getElementById('dataInicio');
    const inputFim    = document.getElementById('dataFim');
    const ini = (inputInicio && inputInicio.value) ? inputInicio.value : '<?php echo $dataInicio; ?>';
    const fim = (inputFim && inputFim.value) ? inputFim.value : '<?php echo $dataFim; ?>';

    const menu = document.getElementById('menuOpcoesDre');
    if (menu) menu.style.display = 'none';

    const query = '?data_inicio=' + encodeURIComponent(ini) + '&data_fim=' + encodeURIComponent(fim);
    const baseUrl = '<?php echo $baseUrl; ?>';

    if (formato === 'grafico') {
        window.open(baseUrl + '/financeiro/dre/grafico' + query, '_blank');
    } else if (formato === 'csv') {
        window.location.href = baseUrl + '/financeiro/dre/csv' + query;
    } else {
        window.open(baseUrl + '/financeiro/dre/imprimir' + query, '_blank');
    }
    return false;
}

function definirDatasDre(event, inicio, fim) {
    if (event && event.preventDefault) event.preventDefault();
    const inputInicio = document.getElementById('dataInicio');
    const inputFim    = document.getElementById('dataFim');
    if (inputInicio) inputInicio.value = inicio;
    if (inputFim) inputFim.value = fim;

    const botoes = document.querySelectorAll('.btn-atalho-dre');
    botoes.forEach(function(b) {
        b.style.borderColor = '';
        b.style.backgroundColor = '';
        b.style.fontWeight = '';
    });
    if (event && event.currentTarget) {
        event.currentTarget.style.borderColor = '#1e293b';
        event.currentTarget.style.backgroundColor = '#f1f5f9';
        event.currentTarget.style.fontWeight = '700';
    }
}

document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('wrapperMenuEmissaoDre');
    const menu    = document.getElementById('menuOpcoesDre');
    if (menu && menu.style.display === 'block' && wrapper && !wrapper.contains(e.target)) {
        menu.style.display = 'none';
    }
});
</script>
