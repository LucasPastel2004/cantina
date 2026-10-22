<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório de Histórico de Eventos MED e Quitações
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$eventos = $eventos ?? [];
$busca = $busca ?? '';
$tipo = $tipo ?? 'todos';
$dataInicio = $dataInicio ?? '';
$dataFim = $dataFim ?? '';

$totalRegistros = (int)($totalRegistros ?? count($eventos));
$totalEstornosValor = (float)($totalEstornosValor ?? 0.0);
$totalQuitacoesValor = (float)($totalQuitacoesValor ?? 0.0);

$paginaAtual = (int)($paginaAtual ?? $pagina ?? 1);
$totalPaginas = (int)($totalPaginas ?? 1);
$itensPorPagina = (int)($itensPorPagina ?? 20);

$paramsUrl = [];
if (!empty($busca)) {
    $paramsUrl['busca'] = $busca;
}
if (!empty($tipo) && $tipo !== 'todos') {
    $paramsUrl['tipo'] = $tipo;
}
if (!empty($dataInicio)) {
    $paramsUrl['data_inicio'] = $dataInicio;
}
if (!empty($dataFim)) {
    $paramsUrl['data_fim'] = $dataFim;
}
$queryString = !empty($paramsUrl) ? '?' . http_build_query($paramsUrl) : '';
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

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
        <a href="<?php echo $baseUrl; ?>/relatorios/devedores/imprimir<?php echo $queryString; ?>" 
           target="_blank" 
           id="btnImprimirA4"
           class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" 
           title="Visualizar espelho oficial para conferência e impressão / salvar PDF em nova aba">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
            Imprimir / Salvar PDF (A4)
        </a>
    </div>
</div>

<!-- Filtros de Busca e Período -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/relatorios/devedores" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-4">
                <label for="inputBusca" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                    Localizar Titular, CPF ou Protocolo
                </label>
                <div class="search-input-wrapper">
                    <span class="search-icon">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="text" name="busca" id="inputBusca" class="form-control form-control-sm font-tabular" style="padding-left: 46px !important;" placeholder="Nome, CPF ou ref/protocolo..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="col-12 col-sm-6 col-md-3">
                <label for="filtroTipo" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Tipo de Operação</label>
                <select name="tipo" id="filtroTipo" class="form-select form-select-sm font-tabular">
                    <option value="todos" <?php echo ($tipo === 'todos') ? 'selected' : ''; ?>>Todas as Operações</option>
                    <option value="estornos" <?php echo ($tipo === 'estornos') ? 'selected' : ''; ?>>Apenas Estornos MED BACEN</option>
                    <option value="quitacoes" <?php echo ($tipo === 'quitacoes') ? 'selected' : ''; ?>>Apenas Quitações / Ajustes</option>
                </select>
            </div>

            <div class="col-6 col-sm-3 col-md-2">
                <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Início</label>
                <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-6 col-sm-3 col-md-2">
                <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Fim</label>
                <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-fill" title="Aplicar Filtros">
                    Filtrar
                </button>
                <?php if (!empty($busca) || ($tipo !== 'todos' && !empty($tipo)) || !empty($dataInicio) || !empty($dataFim)): ?>
                    <a href="<?php echo $baseUrl; ?>/relatorios/devedores" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">
                        ✕
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip: Indicadores do Histórico -->
<div class="stat-strip mb-4">
    <div class="stat-item">
        <span class="stat-label">Total de Eventos no Filtro</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo (int)$totalRegistros; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">registros</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Trilha de auditoria MED
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Total de Débitos Estorno MED</span>
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
        <span class="stat-label">Página Atual</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo $paginaAtual; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">de <?php echo max(1, $totalPaginas); ?></small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Exibindo até <?php echo $itensPorPagina; ?> por página
        </span>
    </div>
</div>

<!-- Tabela de Histórico de Eventos MED -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <h2 class="h6 font-weight-bold text-dark mb-0">Lançamentos Históricos de MED e Quitações</h2>
        <span class="badge bg-light text-secondary border font-tabular px-2 py-1">
            <?php echo count($eventos); ?> exibido(s) nesta página
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0">
            <thead>
                <tr>
                    <th class="cell-center" style="width: 140px;">Data/Hora</th>
                    <th class="cell-left" style="width: 250px;">Titular / Família</th>
                    <th class="cell-center" style="width: 160px;">Tipo de Operação</th>
                    <th class="cell-right" style="width: 130px;">Valor</th>
                    <th class="cell-right" style="width: 140px;">Saldo Pós-Operação</th>
                    <th class="cell-left">Motivo / Protocolo</th>
                    <th class="cell-left" style="width: 160px;">Operador</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventos)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-secondary text-microcopy">
                            Nenhum registro de evento MED ou quitação localizado para os filtros informados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($eventos as $e): ?>
                        <?php 
                        $isDebito = ($e['tipo'] === 'debito_contestacao_med');
                        $val = (float)$e['valor'];
                        $saldoPos = (float)$e['saldo_resultante'];
                        ?>
                        <tr>
                            <td class="cell-center font-tabular text-secondary" style="font-size: 11px;">
                                <?php echo date('d/m/Y H:i', strtotime($e['data_hora'])); ?>
                            </td>

                            <td class="cell-left">
                                <strong class="text-dark"><?php echo htmlspecialchars($e['titular'] ?? 'Desconhecido', ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="text-secondary text-microcopy font-tabular">
                                    <?php if (!empty($e['responsavel_cpf'])): ?>
                                        CPF: <?php echo htmlspecialchars($e['responsavel_cpf'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($e['responsavel_whatsapp'])): ?>
                                        &bull; <?php echo htmlspecialchars($e['responsavel_whatsapp'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td class="cell-center">
                                <span class="badge bg-light text-secondary border font-tabular px-2 py-1" style="font-size: 11px;">
                                    <?php echo $isDebito ? 'Estorno MED BACEN' : 'Quitação / Ajuste'; ?>
                                </span>
                            </td>

                            <td class="cell-right font-tabular font-weight-bold text-dark">
                                <?php echo $isDebito ? '-' : '+'; ?> R$ <?php echo number_format($val, 2, ',', '.'); ?>
                            </td>

                            <td class="cell-right font-tabular font-weight-bold text-dark">
                                R$ <?php echo number_format($saldoPos, 2, ',', '.'); ?>
                            </td>

                            <td class="cell-left text-secondary" style="font-size: 11px;">
                                <div class="text-truncate" style="max-width: 320px;" title="<?php echo htmlspecialchars($e['motivo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($e['motivo'] ?? 'Sem justificativa informada', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <?php if (!empty($e['referencia_id'])): ?>
                                    <div class="text-microcopy text-muted font-tabular">
                                        Ref: <code><?php echo htmlspecialchars($e['referencia_id'], ENT_QUOTES, 'UTF-8'); ?></code>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td class="cell-left text-secondary font-tabular" style="font-size: 11px;">
                                <?php echo htmlspecialchars($e['operador_nome'] ?? 'Sistema / Automático', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação Corporativa Oficial -->
    <?php require __DIR__ . '/../partials/pagination.php'; ?>
</div>

</div>
