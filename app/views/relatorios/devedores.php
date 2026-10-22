<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório de Histórico de Eventos MED e Quitações
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$busca = $busca ?? '';
$tipo = $tipo ?? 'todos';
$dataInicio = $dataInicio ?? '';
$dataFim = $dataFim ?? '';

$totalRegistros = (int)($totalRegistros ?? 0);
$totalEstornosValor = (float)($totalEstornosValor ?? 0.0);
$totalQuitacoesValor = (float)($totalQuitacoesValor ?? 0.0);
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

    <!-- Cabeçalho Institucional da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h5 font-weight-bold text-dark mb-1">Histórico de Eventos MED & Quitações</h1>
            <p class="text-secondary text-microcopy mb-0">
                Trilha de auditoria das movimentações bancárias, débitos por estorno MED BACEN e quitações manuais de carteira.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo $baseUrl; ?>/admin/contestacoes" 
               class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" 
               title="Acessar gestão operacional e dívidas ativas no painel financeiro">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                    <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                </svg>
                Ver Dívidas Ativas no Financeiro
            </a>
        </div>
    </div>

    <!-- Bloco de Filtros com Emissão Direta em PDF A4 -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-body p-3">
            <form action="<?php echo $baseUrl; ?>/relatorios/devedores/imprimir" method="GET" target="_blank" class="row g-2 align-items-end" id="formFiltroHistoricoMed">
                <div class="col-12 col-md-4">
                    <label for="inputBusca" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                        Localizar Titular, CPF ou Protocolo
                    </label>
                    <div class="search-input-wrapper">
                        <span class="search-icon">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </span>
                        <input type="text" name="busca" id="inputBusca" class="form-control form-control-sm font-tabular" style="padding-left: 46px !important;" placeholder="Nome, CPF ou ref/protocolo..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtroTipo" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                        Tipo de Operação
                    </label>
                    <select name="tipo" id="filtroTipo" class="form-select form-select-sm font-tabular">
                        <option value="todos" <?php echo ($tipo === 'todos') ? 'selected' : ''; ?>>Todas as Operações</option>
                        <option value="estornos" <?php echo ($tipo === 'estornos') ? 'selected' : ''; ?>>Apenas Estornos MED BACEN</option>
                        <option value="quitacoes" <?php echo ($tipo === 'quitacoes') ? 'selected' : ''; ?>>Apenas Quitações / Ajustes</option>
                    </select>
                </div>

                <div class="col-6 col-sm-3 col-md-2">
                    <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                        Data Início
                    </label>
                    <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-6 col-sm-3 col-md-2">
                    <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                        Data Fim
                    </label>
                    <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Botão azul de Imprimir / Salvar PDF no lugar do botão filtrar -->
                <div class="col-12 col-md d-flex gap-1">
                    <button type="submit" 
                            id="btnGerarPdfMed"
                            class="btn btn-enterprise btn-primary btn-sm flex-grow-1 d-inline-flex align-items-center justify-content-center gap-1.5" 
                            title="Gerar e abrir espelho oficial A4 para conferência e impressão / salvar PDF">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                        </svg>
                        <span>Imprimir / Salvar PDF (A4)</span>
                    </button>
                    <button type="button" 
                            class="btn btn-enterprise btn-secondary btn-sm" 
                            onclick="limparFiltrosMed()" 
                            title="Limpar parâmetros de busca">
                        Limpar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stat-Strip: Indicadores do Histórico Geral -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Total de Eventos Registrados</span>
            <span class="stat-value font-tabular text-dark">
                <?php echo (int)$totalRegistros; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">registros</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Trilha de auditoria MED
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total Débitos Estorno MED</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format($totalEstornosValor, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Movimentações contestadas BACEN
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total Quitações e Baixas</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format($totalQuitacoesValor, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Valores regularizados em carteira
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Modo de Visualização</span>
            <span class="stat-value font-tabular text-dark" style="font-size: 16px;">
                PDF A4 Timbrado
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Espelho oficial com zoom milimétrico
            </span>
        </div>
    </div>

    <!-- Bloco Informativo Corporativo de Emissão (Anti-Slop Enterprise) -->
    <div class="card-enterprise p-5 text-center">
        <div class="text-secondary mb-3">
            <svg width="44" height="44" viewBox="0 0 16 16" fill="currentColor" class="text-secondary opacity-75">
                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                <path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/>
            </svg>
        </div>
        <h2 class="h6 font-weight-bold text-dark mb-1">Geração sob Demanda do Relatório Histórico MED</h2>
        <p class="text-secondary text-microcopy mb-0" style="max-width: 620px; margin: 0 auto; line-height: 1.6;">
            Ajuste os filtros acima (nome do responsável, CPF, código de protocolo, tipo de operação ou período de datas) e clique no botão azul <strong>Imprimir / Salvar PDF (A4)</strong> para gerar e abrir o documento oficial timbrado formatado especificamente para os parâmetros informados.
        </p>
    </div>

</div>

<script>
function limparFiltrosMed() {
    var inputBusca = document.getElementById('inputBusca');
    var filtroTipo = document.getElementById('filtroTipo');
    var dataInicio = document.getElementById('dataInicio');
    var dataFim = document.getElementById('dataFim');

    if (inputBusca) inputBusca.value = '';
    if (filtroTipo) filtroTipo.value = 'todos';
    if (dataInicio) dataInicio.value = '';
    if (dataFim) dataFim.value = '';
}
</script>
