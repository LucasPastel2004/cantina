<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Categorias de Cardápio
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$categorias = $categorias ?? [];
$csrfField = $csrf_field ?? '';
$csrfToken = $csrf_token ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?php echo $baseUrl; ?>/admin/produtos" class="btn btn-enterprise btn-primary btn-sm" title="Voltar ao Cardápio">
                &larr; Cardápio
            </a>
            <h1 class="h5 font-weight-bold text-dark mb-0">Categorias de Alimentos</h1>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Segmentação dos produtos para o cardápio familiar e telas de PDV.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModalCategoria()">
            + Nova Categoria
        </button>
    </div>
</div>

<?php
$totalCategorias = count($categorias);
$categoriasAtivas = count(array_filter($categorias, static function($c) {
    return !empty($c['ativo']);
}));
$totalProdutosVinculados = array_sum(array_map(static function($c) {
    return (int)($c['total_produtos'] ?? 0);
}, $categorias));
?>

<div class="row g-4">
    <!-- Coluna Principal: Listagem de Categorias -->
    <div class="col-12 col-xl-8">
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span>Categorias Cadastradas</span>
                    <span class="badge-count"><?php echo $totalCategorias; ?> <?php echo $totalCategorias === 1 ? 'categoria' : 'categorias'; ?></span>
                </div>
                <div class="text-secondary font-microcopy d-none d-sm-block">
                    <?php echo $categoriasAtivas; ?> ativas no cardápio
                </div>
            </div>
            <div class="card-enterprise-body p-0">
                <?php if (empty($categorias)): ?>
                    <?php 
                    $emptyMessage = 'Nenhuma categoria cadastrada no momento.';
                    require __DIR__ . '/../partials/empty_state.php'; 
                    ?>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-data-grid table-enterprise mb-0">
                            <thead>
                                <tr>
                                    <th class="cell-center" style="width: 70px;">Ordem</th>
                                    <th class="cell-left">Nome da Categoria</th>
                                    <th class="cell-center" style="width: 140px;">Itens Vinculados</th>
                                    <th class="cell-center" style="width: 110px;">Status</th>
                                    <th class="cell-right" style="width: 130px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categorias as $cat): ?>
                                    <tr>
                                        <td class="cell-center font-tabular text-muted">
                                            <code class="code-tag font-tabular"><?php echo (int)$cat['ordem_exibicao']; ?></code>
                                        </td>
                                        <td class="cell-left font-weight-semibold text-dark">
                                            <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cell-center font-tabular">
                                            <span class="badge-count">
                                                <?php echo (int)($cat['total_produtos'] ?? 0); ?> <?php echo ((int)($cat['total_produtos'] ?? 0) === 1) ? 'item' : 'itens'; ?>
                                            </span>
                                        </td>
                                        <td class="cell-center">
                                            <?php if (!empty($cat['ativo'])): ?>
                                                <span class="status-indicator status-active">
                                                    <span class="bullet" aria-hidden="true">●</span> Ativa
                                                </span>
                                            <?php else: ?>
                                                <span class="status-indicator status-inactive">
                                                    <span class="bullet" aria-hidden="true">●</span> Inativa
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-right">
                                            <div class="btn-action-group">
                                                <button type="button" class="btn-action" 
                                                        onclick='editarCategoria(<?php echo json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                        title="Editar Categoria">
                                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                                    </svg>
                                                </button>
                                                <form action="<?php echo $baseUrl; ?>/admin/categorias/excluir" method="POST" class="d-inline m-0"
                                                      data-confirm="Deseja realmente inativar/excluir esta categoria? Ela será mantida no histórico de produtos."
                                                      data-confirm-title="Inativar Categoria"
                                                      data-confirm-type="danger"
                                                      data-confirm-btn="Sim, Inativar">
                                                    <?php echo $csrfField; ?>
                                                    <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                                    <button type="submit" class="btn-action btn-action-delete" title="Excluir Categoria">
                                                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Lateral de Apoio: Métricas e Diretrizes Operacionais -->
    <div class="col-12 col-xl-4">
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <span>Resumo da Grade</span>
            </div>
            <div class="card-enterprise-body p-3">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary" style="font-size: 12px;">Categorias Ativas</span>
                    <span class="font-tabular font-weight-bold text-dark"><?php echo $categoriasAtivas; ?> / <?php echo $totalCategorias; ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-secondary" style="font-size: 12px;">Total de Produtos Vinculados</span>
                    <span class="font-tabular font-weight-bold text-dark"><?php echo $totalProdutosVinculados; ?> itens</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="text-secondary" style="font-size: 12px;">Visibilidade no PDV</span>
                    <span class="status-indicator status-active" style="font-size: 11px;">
                        <span class="bullet">●</span> Sincronizado
                    </span>
                </div>
            </div>
        </div>

        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <span>Diretrizes de Segmentação</span>
            </div>
            <div class="card-enterprise-body p-3">
                <div class="mb-3">
                    <div class="font-weight-semibold text-dark mb-1" style="font-size: 12px;">Ordem de Exibição no PDV</div>
                    <p class="text-secondary font-microcopy mb-0">
                        A ordem numérica define a disposição das abas rápidas nas telas do caixa e no aplicativo das famílias. Menores números aparecem primeiro na navegação.
                    </p>
                </div>
                <div class="border-top pt-3">
                    <div class="font-weight-semibold text-dark mb-1" style="font-size: 12px;">Integridade e Histórico</div>
                    <p class="text-secondary font-microcopy mb-0">
                        Categorias com produtos cadastrados não são excluídas fisicamente do banco de dados, protegendo a integridade de relatórios de fechamento de caixa e auditoria contábil.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cadastro e Edição de Categoria -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <form action="<?php echo $baseUrl; ?>/admin/categorias/salvar" method="POST" class="m-0">
                <?php echo $csrfField; ?>
                <input type="hidden" name="id" id="cat_id" value="0">

                <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <h5 class="modal-title font-weight-bold text-dark" id="modalCategoriaLabel" style="font-size: 15px;">
                        Nova Categoria
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="cat_nome" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                            Nome da Categoria <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="cat_nome" name="nome" required maxlength="50" placeholder="Ex: Salgados Assados, Bebidas...">
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label for="cat_ordem" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                Ordem de Exibição
                            </label>
                            <input type="number" class="form-control form-control-sm font-tabular" id="cat_ordem" name="ordem_exibicao" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label for="cat_ativo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                Status
                            </label>
                            <select class="form-select form-select-sm" id="cat_ativo" name="ativo">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-4" style="border-top: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Salvar Categoria</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalCategoria() {
    document.getElementById('cat_id').value = '0';
    document.getElementById('cat_nome').value = '';
    document.getElementById('cat_ordem').value = '0';
    document.getElementById('cat_ativo').value = '1';
    document.getElementById('modalCategoriaLabel').innerText = 'Nova Categoria';
    
    var modalEl = document.getElementById('modalCategoria');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function editarCategoria(cat) {
    document.getElementById('cat_id').value = cat.id;
    document.getElementById('cat_nome').value = cat.nome;
    document.getElementById('cat_ordem').value = cat.ordem_exibicao;
    document.getElementById('cat_ativo').value = cat.ativo;
    document.getElementById('modalCategoriaLabel').innerText = 'Editar Categoria: ' + cat.nome;
    
    var modalEl = document.getElementById('modalCategoria');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}
</script>
