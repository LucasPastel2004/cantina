<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Livro Caixa Diário (Emissão sob Demanda)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrão de emissão direta de relatórios
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$fimSemana = date('Y-m-d', strtotime('friday this week'));
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');
$inicioMesAnterior = date('Y-m-d', strtotime('first day of previous month'));
$fimMesAnterior = date('Y-m-d', strtotime('last day of previous month'));
?>

<div class="container-fluid p-0">

    <!-- Topbar Institucional do Livro Caixa -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-secondary text-microcopy text-uppercase font-weight-bold">Módulo Financeiro & Escrituração Contábil</span>
            </div>
            <h1 class="h4 font-weight-bold mb-0 text-dark">Livro Caixa Diário</h1>
            <p class="text-secondary text-microcopy mb-0 mt-1">
                Registro cronológico detalhado de todas as entradas financeiras (Pix Online, PDV Pix e Dinheiro) e saídas (pagamentos a fornecedores e despesas) com saldo progressivo.
            </p>
        </div>
    </div>

    <!-- Filtro de Período & Emissão Direta do Relatório A4 / Planilha CSV -->
    <div class="card card-enterprise mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <div class="card-body p-3">
            <form method="GET" action="<?php echo $baseUrl; ?>/financeiro/livro-caixa/imprimir" target="_blank" id="formFiltroLivroCaixa">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                    
                    <!-- Intervalo de Datas e Botões de Emissão -->
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div>
                            <label for="dataInicio" class="form-label text-microcopy text-secondary text-uppercase fw-semibold mb-1 d-block">Data Inicial</label>
                            <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div>
                            <label for="dataFim" class="form-label text-microcopy text-secondary text-uppercase fw-semibold mb-1 d-block">Data Final</label>
                            <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" id="btnImprimirA4" class="btn btn-sm btn-enterprise btn-primary d-inline-flex align-items-center gap-2" title="Gerar e visualizar espelho oficial A4 Paisagem para conferência, impressão ou salvar em PDF">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                <span>Imprimir / Salvar PDF (A4)</span>
                            </button>
                            <button type="button" id="btnCsv" onclick="exportarCsv(event)" class="btn btn-sm btn-enterprise btn-secondary d-inline-flex align-items-center gap-2" title="Exportar dados do Livro Caixa para planilha Microsoft Excel">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.5 12.5A.5.5 0 0 1 5 12h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
                                <span>Exportar Planilha (CSV)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Atalhos Rápidos de Seleção (Padrão Corporativo Neutro) -->
                    <div>
                        <span class="text-microcopy text-secondary text-uppercase fw-semibold d-block mb-1">Atalhos de Período</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-enterprise btn-secondary btn-atalho-data" onclick="definirDatas(event, '<?php echo $hoje; ?>', '<?php echo $hoje; ?>')">Hoje</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-atalho-data" onclick="definirDatas(event, '<?php echo $ontem; ?>', '<?php echo $ontem; ?>')">Ontem</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-atalho-data" onclick="definirDatas(event, '<?php echo $inicioSemana; ?>', '<?php echo $fimSemana; ?>')">Esta Semana</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-atalho-data" onclick="definirDatas(event, '<?php echo $inicioMes; ?>', '<?php echo $fimMes; ?>')">Este Mês</button>
                            <button type="button" class="btn btn-enterprise btn-secondary btn-atalho-data" onclick="definirDatas(event, '<?php echo $inicioMesAnterior; ?>', '<?php echo $fimMesAnterior; ?>')">Mês Anterior</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- Bloco Informativo de Orientação -->
    <div class="card card-enterprise" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
        <div class="card-body p-4 text-center">
            <div class="d-inline-flex align-items-center justify-content-center mb-2" style="width: 42px; height: 42px; border-radius: 50%; background: #eff6ff; color: #1d4ed8;">
                <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                    <path d="M4.5 10.5A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </div>
            <h2 class="h6 font-weight-bold text-dark mb-1">Emissão Contábil do Livro Caixa sob Demanda</h2>
            <p class="text-secondary text-microcopy mb-0 mx-auto" style="max-width: 620px;">
                Selecione o período desejado nos campos acima e clique em <strong>Imprimir / Salvar PDF (A4)</strong> para gerar o espelho contábil oficial em formato Paisagem (13 colunas com saldo anterior, entradas detalhadas, pagamentos a fornecedores e saldo progressivo) ou em <strong>Exportar Planilha (CSV)</strong> para download direto para Excel.
            </p>
        </div>
    </div>

</div>

<script>
function exportarCsv(e) {
    if (e && e.preventDefault) e.preventDefault();
    var dataInicio = document.getElementById('dataInicio').value;
    var dataFim = document.getElementById('dataFim').value;
    var url = '<?php echo $baseUrl; ?>/financeiro/livro-caixa/csv?data_inicio=' + encodeURIComponent(dataInicio) + '&data_fim=' + encodeURIComponent(dataFim);
    window.location.href = url;
}

function definirDatas(e, inicio, fim) {
    if (e && e.preventDefault) e.preventDefault();
    var inputInicio = document.getElementById('dataInicio');
    var inputFim = document.getElementById('dataFim');
    if (inputInicio) inputInicio.value = inicio;
    if (inputFim) inputFim.value = fim;

    var atalhos = document.querySelectorAll('.btn-atalho-data');
    atalhos.forEach(function(btn) {
        btn.classList.remove('active');
    });
    if (e && e.currentTarget) {
        e.currentTarget.classList.add('active');
    }
}

window.exportarCsv = exportarCsv;
window.definirDatas = definirDatas;
</script>
