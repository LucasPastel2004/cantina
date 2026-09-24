<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Extrato Analítico de Movimentações de Estoque
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Voltar ao Estoque
                </a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Extrato Cronológico de Movimentações</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/perdas" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M7.005 3.1a1 1 0 1 1 1.99 0l-.388 6.35a.61.61 0 0 1-1.214 0zM8 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/></svg>
                Lançar Perda
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">
                Ver Saldos Atuais
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card-enterprise p-3 mb-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/estoque/movimentacoes" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Busca:</label>
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Código, produto, justificativa..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Produto:</label>
                <select name="produto_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos os Produtos</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ((int)($filtros['produto_id'] ?? 0) === (int)$p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['codigo_interno'] . ' — ' . $p['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Tipo de Movimentação:</label>
                <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos os Tipos</option>
                    <?php foreach ($tiposMov as $kTipo => $lblTipo): ?>
                        <option value="<?php echo $kTipo; ?>" <?php echo (($filtros['tipo_movimentacao'] ?? '') === $kTipo) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lblTipo, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Data Início:</label>
                <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" onchange="this.form.submit()">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Data Fim:</label>
                <input type="date" name="data_fim" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" onchange="this.form.submit()">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                <?php if (!empty($filtros['busca']) || !empty($filtros['produto_id']) || !empty($filtros['tipo_movimentacao']) || !empty($filtros['data_inicio']) || !empty($filtros['data_fim'])): ?>
                    <a href="<?php echo $baseUrl; ?>/estoque/movimentacoes" class="btn btn-enterprise btn-secondary btn-sm">Limpar Filtros</a>
                <?php endif; ?>
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm">Filtrar Movimentações</button>
            </div>
        </form>
    </div>

    <!-- Tabela de Movimentações -->
    <div class="card-enterprise">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="card-enterprise-title">Lançamentos Históricos</span>
            <span class="text-muted small">Exibindo até <?php echo count($movimentacoes); ?> registros recentes</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 140px;">Data / Hora</th>
                        <th style="width: 100px;">Código</th>
                        <th>Produto</th>
                        <th style="width: 180px;">Tipo de Movimentação</th>
                        <th style="width: 110px;" class="text-end">Quantidade</th>
                        <th style="width: 150px;">Motivo / Origem</th>
                        <th>Justificativa / Detalhes</th>
                        <th style="width: 130px;">Operador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimentacoes)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <em>Não há nada para mostrar aqui</em>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movimentacoes as $mov): ?>
                            <?php 
                            $isEntrada = in_array($mov['tipo_movimentacao'], ['entrada_fornecedor', 'entrada_xml', 'aproveitamento_sobra']);
                            $sinal = $isEntrada ? '+' : '-';
                            $corClasse = $isEntrada ? 'text-success fw-bold' : ($mov['tipo_movimentacao'] === 'saida_perda' ? 'text-danger fw-bold' : 'text-slate-900');
                            ?>
                            <tr>
                                <td class="font-tabular text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?>
                                </td>
                                <td>
                                    <code class="code-tag"><?php echo htmlspecialchars($mov['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <span class="fw-semibold text-slate-900"><?php echo htmlspecialchars($mov['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-muted small d-block"><?php echo htmlspecialchars($mov['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td>
                                    <span class="small <?php echo $isEntrada ? 'text-success' : 'text-slate-700'; ?>">
                                        <?php echo htmlspecialchars($tiposMov[$mov['tipo_movimentacao']] ?? $mov['tipo_movimentacao'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="text-end font-tabular <?php echo $corClasse; ?>">
                                    <?php echo $sinal . (int)$mov['quantidade']; ?> un
                                </td>
                                <td>
                                    <?php if (!empty($mov['motivo_perda'])): ?>
                                        <span class="badge bg-danger-soft text-danger fw-normal" style="font-size: 11px;">
                                            <?php echo htmlspecialchars($motivosPerda[$mov['motivo_perda']] ?? $mov['motivo_perda'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php elseif (!empty($mov['numero_nota'])): ?>
                                        <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo (int)$mov['nota_fiscal_id']; ?>" class="small text-decoration-none">
                                            NF-e nº <?php echo htmlspecialchars($mov['numero_nota'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-secondary small">
                                        <?php echo !empty($mov['justificativa']) ? htmlspecialchars($mov['justificativa'], ENT_QUOTES, 'UTF-8') : '&mdash;'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="small text-slate-800"><?php echo htmlspecialchars($mov['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
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
