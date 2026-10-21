<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório e Extrato Analítico de Baixas de Mercadorias (Perdas & Descarte)
 * 
 * Conforme docs/FSD.md (RN-15 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$filtros = $filtros ?? [];
$categorias = $categorias ?? [];
$produtos = $produtos ?? [];
$operadores = $operadores ?? [];
$motivosPerda = $motivosPerda ?? [];
$movimentacoes = $movimentacoes ?? [];
$totais = $totais ?? [
    'total_unidades' => 0,
    'total_registros' => 0,
    'por_motivo' => []
];

$dataInicio  = $filtros['data_inicio'] ?? '';
$dataFim     = $filtros['data_fim'] ?? '';
$categoriaId = (int)($filtros['categoria_id'] ?? 0);
$produtoId   = (int)($filtros['produto_id'] ?? 0);
$motivoPerda = $filtros['motivo_perda'] ?? '';
$operadorId  = (int)($filtros['operador_id'] ?? 0);
$busca       = $filtros['busca'] ?? '';

// Monta query string para exportações mantendo os filtros aplicados
$queryParams = http_build_query([
    'data_inicio'  => $dataInicio,
    'data_fim'     => $dataFim,
    'categoria_id' => $categoriaId,
    'produto_id'   => $produtoId,
    'motivo_perda' => $motivoPerda,
    'operador_id'  => $operadorId,
    'busca'        => $busca
]);
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo e Ações -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque/perdas" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Voltar ao Registro de Perdas
                </a>
                <span class="text-muted small">|</span>
                <a href="<?php echo $baseUrl; ?>/relatorios" class="text-decoration-none text-muted small">Central de Relatórios</a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Relatório de Baixas de Mercadorias (Perdas & Descarte)</h1>
            <p class="text-secondary small mb-0">
                Auditoria cronológica e gerencial de todas as baixas por avaria, vencimento, produto não consumido e ajustes de inventário.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Imprimir / Salvar PDF (A4) -->
            <a href="<?php echo $baseUrl; ?>/relatorios/baixas-mercadorias/imprimir?<?php echo $queryParams; ?>" target="_blank" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Visualizar e Imprimir em Folha A4">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                <span>Imprimir / Salvar PDF (A4)</span>
            </a>

            <!-- Exportar Planilha (CSV) -->
            <a href="<?php echo $baseUrl; ?>/relatorios/baixas-mercadorias/csv?<?php echo $queryParams; ?>" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Exportar dados para Excel">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                    <path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/>
                </svg>
                <span>Exportar Planilha (CSV)</span>
            </a>

            <!-- Botão de Nova Baixa -->
            <a href="<?php echo $baseUrl; ?>/estoque/perdas" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/></svg>
                <span>Nova Baixa</span>
            </a>
        </div>
    </div>

    <!-- Barra de Filtros (Padrão Escolar Manager + Extrato de Movimentações) -->
    <div class="card-enterprise p-3 mb-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/relatorios/baixas-mercadorias" id="formFiltroBaixas">
            
            <!-- Linha 1: Período com Atalhos Rápidos -->
            <div class="row g-2 align-items-end mb-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="dataInicio" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">
                        Período: Data Inicial
                    </label>
                    <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="dataFim" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">
                        Período: Data Final
                    </label>
                    <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 col-md-6 d-flex align-items-end flex-wrap gap-1">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('hoje')">Hoje</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('ontem')">Ontem</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('esta_semana')">Esta Semana</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('este_mes')">Este Mês</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="definirPeriodo('mes_anterior')">Mês Anterior</button>
                </div>
            </div>

            <hr class="my-2 text-muted" style="opacity: 0.15;">

            <!-- Linha 2: Busca, Categoria, Produto, Motivo e Operador -->
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="inputBusca" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">Busca:</label>
                    <input type="text" name="busca" id="inputBusca" class="form-control form-control-sm" placeholder="Código, produto, justificativa..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 col-sm-6 col-md-2">
                    <label for="selectCategoria" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">Categoria:</label>
                    <select name="categoria_id" id="selectCategoria" class="form-select form-select-sm" onchange="filtrarProdutosPorCategoria()">
                        <option value="0">-- Todas --</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoriaId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-3">
                    <label for="selectProduto" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">Produto:</label>
                    <select name="produto_id" id="selectProduto" class="form-select form-select-sm">
                        <option value="0" data-categoria="0">-- Todos os Produtos --</option>
                        <?php foreach ($produtos as $p): ?>
                            <option value="<?php echo (int)$p['id']; ?>" data-categoria="<?php echo (int)($p['categoria_id'] ?? 0); ?>" <?php echo ($produtoId === (int)$p['id']) ? 'selected' : ''; ?>>
                                [<?php echo htmlspecialchars($p['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($p['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-2">
                    <label for="selectMotivo" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">Motivo da Perda:</label>
                    <select name="motivo_perda" id="selectMotivo" class="form-select form-select-sm">
                        <option value="">-- Todos os Motivos --</option>
                        <?php foreach ($motivosPerda as $kMotivo => $lblMotivo): ?>
                            <option value="<?php echo $kMotivo; ?>" <?php echo ($motivoPerda === $kMotivo) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lblMotivo, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-2">
                    <label for="selectOperador" class="form-label small fw-semibold text-muted mb-1 text-uppercase" style="font-size: 11px;">Operador:</label>
                    <select name="operador_id" id="selectOperador" class="form-select form-select-sm">
                        <option value="0">-- Todos --</option>
                        <?php foreach ($operadores as $op): ?>
                            <option value="<?php echo (int)$op['id']; ?>" <?php echo ($operadorId === (int)$op['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($op['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                    <?php if (!empty($busca) || !empty($categoriaId) || !empty($produtoId) || !empty($motivoPerda) || !empty($operadorId) || !empty($dataInicio) || !empty($dataFim)): ?>
                        <a href="<?php echo $baseUrl; ?>/relatorios/baixas-mercadorias" class="btn btn-enterprise btn-secondary btn-sm">Limpar Filtros</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        <span>Filtrar Baixas</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    <!-- Faixa de Resumo / Métricas Rápidas do Filtro -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card-enterprise p-3 bg-white">
                <span class="text-uppercase text-secondary fw-semibold" style="font-size: 11px;">Total de Unidades Baixadas</span>
                <div class="h4 fw-bold text-danger font-tabular mb-0 mt-1">
                    -<?php echo number_format($totais['total_unidades'] ?? 0, 0, ',', '.'); ?> un
                </div>
                <small class="text-muted" style="font-size: 11px;">Impacto físico no estoque</small>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card-enterprise p-3 bg-white">
                <span class="text-uppercase text-secondary fw-semibold" style="font-size: 11px;">Ocorrências Registradas</span>
                <div class="h4 fw-bold text-slate-900 font-tabular mb-0 mt-1">
                    <?php echo (int)($totais['total_registros'] ?? count($movimentacoes)); ?> lançamentos
                </div>
                <small class="text-muted" style="font-size: 11px;">Auditorias no período filtrado</small>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card-enterprise p-3 bg-white h-100 d-flex flex-column justify-content-center">
                <span class="text-uppercase text-secondary fw-semibold mb-2" style="font-size: 11px;">Distribuição por Motivo no Período</span>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    $dist = $totais['por_motivo'] ?? [];
                    if (empty($dist)): ?>
                        <span class="text-muted small">Nenhuma baixa computada no recorte.</span>
                    <?php else: ?>
                        <?php foreach ($dist as $motKey => $qtdMot): ?>
                            <span class="badge bg-light text-slate-800 border px-2 py-1 font-tabular" style="font-size: 11.5px;">
                                <strong><?php echo htmlspecialchars($motivosPerda[$motKey] ?? $motKey, ENT_QUOTES, 'UTF-8'); ?>:</strong>
                                <span class="text-danger fw-bold ms-1">-<?php echo (int)$qtdMot; ?> un</span>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Movimentações de Baixas -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="card-enterprise-title">Lançamentos Históricos de Baixas</span>
            <span class="text-muted small">Exibindo <?php echo count($movimentacoes); ?> registros nesta página</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 130px;">Data / Hora</th>
                        <th style="width: 100px;">Código</th>
                        <th>Produto</th>
                        <th style="width: 110px;" class="text-end">Quantidade</th>
                        <th style="width: 170px;">Motivo Padronizado</th>
                        <th>Justificativa Operacional</th>
                        <th style="width: 130px;">Operador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <em>Não há nada para mostrar aqui</em>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $mov): ?>
                            <tr>
                                <td class="font-tabular text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?>
                                </td>
                                <td>
                                    <code class="code-tag"><?php echo htmlspecialchars($mov['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <span class="fw-semibold text-slate-900 d-block small"><?php echo htmlspecialchars($mov['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-muted small" style="font-size: 11px;"><?php echo htmlspecialchars($mov['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end font-tabular text-danger fw-bold">
                                    -<?php echo (int)$mov['quantidade']; ?> un
                                </td>
                                <td>
                                    <span class="badge bg-danger-soft text-danger fw-medium" style="font-size: 11.5px;">
                                        <?php echo htmlspecialchars($motivosPerda[$mov['motivo_perda']] ?? $mov['motivo_perda'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-secondary small" style="line-height: 1.4;">
                                        <?php echo !empty($mov['justificativa']) ? htmlspecialchars($mov['justificativa'], ENT_QUOTES, 'UTF-8') : '&mdash;'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-slate-800 fw-medium"><?php echo htmlspecialchars($mov['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Paginação Dinâmica Conforme Configuração Oficial -->
        <?php require __DIR__ . '/../partials/pagination.php'; ?>
    </div>
</div>

<script>
// Atalhos rápidos de seleção de período
function definirPeriodo(tipo) {
    const hoje = new Date();
    let dInicio = new Date();
    let dFim = new Date();

    if (tipo === 'hoje') {
        // dInicio e dFim são hoje
    } else if (tipo === 'ontem') {
        dInicio.setDate(hoje.getDate() - 1);
        dFim.setDate(hoje.getDate() - 1);
    } else if (tipo === 'esta_semana') {
        const diaSemana = hoje.getDay();
        const diffSegunda = diaSemana === 0 ? -6 : 1 - diaSemana;
        dInicio.setDate(hoje.getDate() + diffSegunda);
    } else if (tipo === 'este_mes') {
        dInicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    } else if (tipo === 'mes_anterior') {
        dInicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
        dFim = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
    }

    const fmt = (d) => {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    };

    document.getElementById('dataInicio').value = fmt(dInicio);
    document.getElementById('dataFim').value = fmt(dFim);
}

// Filtra produtos na lista conforme a categoria selecionada
function filtrarProdutosPorCategoria() {
    const catId = document.getElementById('selectCategoria').value;
    const selectProd = document.getElementById('selectProduto');
    const options = selectProd.querySelectorAll('option');

    let encontrouSelecionado = false;

    options.forEach(opt => {
        const optCat = opt.getAttribute('data-categoria');
        if (catId === '0' || optCat === '0' || optCat === catId) {
            opt.style.display = '';
            if (opt.selected) encontrouSelecionado = true;
        } else {
            opt.style.display = 'none';
            if (opt.selected) opt.selected = false;
        }
    });

    if (!encontrouSelecionado) {
        selectProd.value = '0';
    }
}
</script>
