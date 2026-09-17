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
            <a href="<?php echo $baseUrl; ?>/admin/produtos" class="btn btn-enterprise btn-secondary btn-sm" title="Voltar ao Cardápio">
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

<div class="card-enterprise">
    <div class="card-enterprise-body p-0">
        <?php if (empty($categorias)): ?>
            <?php 
            $emptyMessage = 'Nenhuma categoria cadastrada no momento.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 80px;">Ordem</th>
                            <th class="cell-left">Nome da Categoria</th>
                            <th class="cell-right" style="width: 160px;">Itens Vinculados</th>
                            <th class="cell-center" style="width: 120px;">Status</th>
                            <th class="cell-right" style="width: 140px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td class="cell-center font-tabular text-muted">
                                    <?php echo (int)$cat['ordem_exibicao']; ?>
                                </td>
                                <td class="cell-left font-weight-semibold text-dark">
                                    <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-right font-tabular">
                                    <?php echo (int)($cat['total_produtos'] ?? 0); ?> produto(s)
                                </td>
                                <td class="cell-center">
                                    <?php if (!empty($cat['ativo'])): ?>
                                        <span class="status-indicator status-active">
                                            <span class="bullet" aria-hidden="true">●</span> Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-indicator status-inactive">
                                            <span class="bullet" aria-hidden="true">●</span> Inativo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" 
                                                onclick='editarCategoria(<?php echo json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                title="Editar Categoria">
                                            Editar
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/categorias/excluir" method="POST" class="d-inline m-0"
                                              onsubmit="return confirm('Deseja realmente excluir esta categoria? Ela será ocultada via exclusão lógica.');">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
                                            <button type="submit" class="btn btn-enterprise btn-danger btn-sm" title="Excluir Categoria">
                                                Excluir
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
