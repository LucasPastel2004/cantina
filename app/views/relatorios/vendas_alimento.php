<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório de Vendas por Alimento
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.2 & Seção 22.2), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$itens = $dadosRelatorio['itens'] ?? [];
$totais = $dadosRelatorio['totais'] ?? [];
$topItem = !empty($itens) ? ($itens[0]['descricao'] ?? '-') : '-';
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Vendas por Alimento</h1>
        <p class="text-secondary text-microcopy mb-0">
            Totalização comercial de alimentos e produtos e ticket médio ponderado.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>&turno=<?php echo urlencode($turno); ?>&categoria_id=<?php echo (int)$categoriaId; ?>&tipo=<?php echo urlencode($tipo); ?>" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Abrir painel de filtros avançados com toggles e agrupamentos">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
            Filtros Avançados
        </a>
        <a href="<?php echo $baseUrl; ?>/relatorios/vendas-alimento/csv?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>&turno=<?php echo urlencode($turno); ?>&categoria_id=<?php echo (int)$categoriaId; ?>&tipo=<?php echo urlencode($tipo); ?>" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Baixar dados em formato CSV para Excel">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/></svg>
            Exportar CSV
        </a>
        <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>&turno=<?php echo urlencode($turno); ?>&categoria_id=<?php echo (int)$categoriaId; ?>&tipo=<?php echo urlencode($tipo); ?>" target="_blank" onclick="window.open(this.href, '_blank'); return false;" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Visualizar em Tela de Impressão e Salvar em PDF (A4)">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
            Imprimir / Salvar PDF (A4)
        </a>
    </div>
</div>

<!-- Filtros de Busca e Segmentação -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body">
        <form action="<?php echo $baseUrl; ?>/relatorios/vendas-alimento" method="GET" class="row g-3 align-items-end">
            <div class="col-6 col-md-2">
                <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-6 col-md-2">
                <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-6 col-md-2">
                <label for="filtroTurno" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Turno Escolar</label>
                <select name="turno" id="filtroTurno" class="form-select form-select-sm font-tabular">
                    <option value="">Todos os Turnos</option>
                    <option value="matutino" <?php echo ($turno === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="vespertino" <?php echo ($turno === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="filtroCategoria" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Categoria do Cardápio</label>
                <select name="categoria_id" id="filtroCategoria" class="form-select form-select-sm font-tabular">
                    <option value="0">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoriaId === (int)$cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label for="filtroTipo" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Tipo de Produto</label>
                <select name="tipo" id="filtroTipo" class="form-select form-select-sm font-tabular">
                    <option value="">Todos os Tipos</option>
                    <option value="lanche" <?php echo ($tipo === 'lanche') ? 'selected' : ''; ?>>Lanche</option>
                    <option value="almoco" <?php echo ($tipo === 'almoco') ? 'selected' : ''; ?>>Almoço</option>
                    <option value="bebida" <?php echo ($tipo === 'bebida') ? 'selected' : ''; ?>>Bebida</option>
                </select>
            </div>

            <div class="col-6 col-md-1 d-flex">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm w-100">
                    Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip: Totalizadores Consolidados -->
<div class="stat-strip mb-4">
    <div class="stat-item">
        <span class="stat-label">Faturamento Bruto Total</span>
        <span class="stat-value font-tabular text-primary">
            R$ <?php echo number_format((float)($totais['faturamento_total'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Receita total no período
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Peças Comercializadas</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo number_format((int)($totais['quantidade_total'] ?? 0), 0, ',', '.'); ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">un</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Volume físico de unidades vendidas
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Preço Médio Ponderado</span>
        <span class="stat-value font-tabular text-dark">
            R$ <?php echo number_format((float)($totais['ticket_medio'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Média por alimento vendido
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Item Líder em Receita</span>
        <span class="stat-value font-tabular text-success text-truncate" style="font-size: 16px; max-width: 200px;" title="<?php echo htmlspecialchars($topItem, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($topItem, ENT_QUOTES, 'UTF-8'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Maior faturamento individual
        </span>
    </div>
</div>

<!-- Tabela Analítica de Vendas -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <h2 class="h6 font-weight-bold text-dark mb-0">Relação Analítica de Itens Vendidos</h2>
        <span class="badge bg-light text-secondary border font-tabular">
            <?php echo count($itens); ?> produto(s) faturado(s)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 80px;">Código</th>
                    <th>Alimento / Produto</th>
                    <th style="width: 150px;">Categoria</th>
                    <th style="width: 100px;">Tipo</th>
                    <th class="text-end" style="width: 100px;">Qtd Vendida</th>
                    <th class="text-end" style="width: 120px;">Preço Médio</th>
                    <th class="text-end" style="width: 140px;">Faturamento Total</th>
                    <th class="text-end" style="width: 100px;">Part. %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-secondary text-microcopy">
                            Nenhuma venda registrada com os filtros selecionados no período informado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td class="font-tabular text-secondary text-microcopy">
                                <?php echo htmlspecialchars($item['codigo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border font-tabular">
                                    <?php echo htmlspecialchars($item['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-secondary text-microcopy text-uppercase font-tabular">
                                    <?php echo htmlspecialchars($item['tipo'] ?? 'lanche', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="text-end font-tabular font-weight-bold text-dark">
                                <?php echo number_format((int)$item['quantidade_total'], 0, ',', '.'); ?> un
                            </td>
                            <td class="text-end font-tabular text-secondary">
                                R$ <?php echo number_format((float)$item['preco_medio'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular font-weight-bold text-dark">
                                R$ <?php echo number_format((float)$item['faturamento_total'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular text-primary font-weight-bold">
                                <?php echo number_format((float)$item['participacao_percentual'], 1, ',', '.'); ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-active font-weight-bold">
                    <td colspan="4" class="text-end text-dark text-uppercase font-tabular">
                        Totais Consolidados no Período:
                    </td>
                    <td class="text-end font-tabular text-dark">
                        <?php echo number_format((int)($totais['quantidade_total'] ?? 0), 0, ',', '.'); ?> un
                    </td>
                    <td class="text-end font-tabular text-secondary">
                        R$ <?php echo number_format((float)($totais['ticket_medio'] ?? 0.0), 2, ',', '.'); ?>
                    </td>
                    <td class="text-end font-tabular text-dark" style="font-size: 14px;">
                        R$ <?php echo number_format((float)($totais['faturamento_total'] ?? 0.0), 2, ',', '.'); ?>
                    </td>
                    <td class="text-end font-tabular text-dark font-weight-bold">
                        100,0%
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</div>
