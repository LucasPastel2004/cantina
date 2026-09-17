<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Cardápio e Produtos
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$produtos = $produtos ?? [];
$categorias = $categorias ?? [];
$alergenos = $alergenos ?? [];
$filtros = $filtros ?? [];
$csrfField = $csrf_field ?? '';
$csrfToken = $csrf_token ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Cardápio & Produtos</h1>
        <p class="text-secondary text-microcopy mb-0">
            Cadastro de alimentos, preços de venda, alérgenos declarados e pausa rápida de vendas.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/admin/categorias" class="btn btn-enterprise btn-secondary">
            Gerenciar Categorias
        </a>
        <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModalProduto()">
            + Novo Produto
        </button>
    </div>
</div>

<!-- Barra de Filtros e Busca -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/admin/produtos" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-3">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Busca</label>
                <input type="text" class="form-control form-control-sm" id="busca" name="busca" 
                       placeholder="Código ou descrição..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label for="filtro_categoria" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Categoria</label>
                <select class="form-select form-select-sm" id="filtro_categoria" name="categoria_id">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ((string)($filtros['categoria_id'] ?? '') === (string)$cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filtro_tipo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Tipo</label>
                <select class="form-select form-select-sm" id="filtro_tipo" name="tipo">
                    <option value="">Todos</option>
                    <option value="salgado_diario" <?php echo (($filtros['tipo'] ?? '') === 'salgado_diario') ? 'selected' : ''; ?>>Salgado Diário</option>
                    <option value="industrializado_continuo" <?php echo (($filtros['tipo'] ?? '') === 'industrializado_continuo') ? 'selected' : ''; ?>>Industrializado</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filtro_status" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Pausa</label>
                <select class="form-select form-select-sm" id="filtro_status" name="status_pausa">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo (($filtros['status_pausa'] ?? '') === 'ativo') ? 'selected' : ''; ?>>Ativos</option>
                    <option value="pausado" <?php echo (($filtros['status_pausa'] ?? '') === 'pausado') ? 'selected' : ''; ?>>Pausados</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm flex-grow-1">
                    Filtrar
                </button>
                <a href="<?php echo $baseUrl; ?>/admin/produtos" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">
                    Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Produtos -->
<div class="card-enterprise">
    <div class="card-enterprise-body p-0">
        <?php if (empty($produtos)): ?>
            <?php 
            $emptyMessage = 'Nenhum produto cadastrado ou encontrado com os filtros aplicados.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 44px;">Foto</th>
                            <th class="cell-center" style="width: 90px;">Código</th>
                            <th class="cell-left">Descrição & Alérgenos</th>
                            <th class="cell-left" style="width: 140px;">Categoria</th>
                            <th class="cell-center" style="width: 110px;">Turno</th>
                            <th class="cell-right" style="width: 100px;">Preço</th>
                            <th class="cell-center" style="width: 110px;">Estoque</th>
                            <th class="cell-center" style="width: 130px;">Disponibilidade</th>
                            <th class="cell-right" style="width: 130px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $prod): ?>
                            <tr>
                                <td class="cell-center py-1">
                                    <?php if (!empty($prod['foto_url'])): ?>
                                        <img src="<?php echo $baseUrl . '/' . htmlspecialchars($prod['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             width="28" height="28" style="width: 28px; height: 28px; border-radius: 4px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 28px; height: 28px; border-radius: 4px; background: #f1f5f9; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; color: #94a3b8;">
                                            &mdash;
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-center">
                                    <code class="code-tag font-tabular"><?php echo htmlspecialchars($prod['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td class="cell-left">
                                    <div class="font-weight-semibold text-dark">
                                        <?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($prod['alergenos_nomes'])): ?>
                                        <div class="d-inline-flex flex-wrap gap-1 mt-1">
                                            <?php 
                                            $nomesAlergenos = explode(', ', $prod['alergenos_nomes']);
                                            foreach ($nomesAlergenos as $al): ?>
                                                <span class="tag-alergeno"><?php echo htmlspecialchars($al, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-left text-secondary" style="font-size: 12px;">
                                    <?php echo htmlspecialchars($prod['categoria_nome'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-center text-secondary" style="font-size: 12px;">
                                    <?php 
                                    $turnos = [
                                        'ambos' => 'Ambos',
                                        'matutino' => 'Manhã',
                                        'vespertino' => 'Tarde'
                                    ];
                                    echo $turnos[$prod['turno_disponibilidade']] ?? ucfirst($prod['turno_disponibilidade']);
                                    ?>
                                </td>
                                <td class="cell-right font-tabular font-weight-semibold text-dark">
                                    R$ <?php echo number_format((float)$prod['preco_venda'], 2, ',', '.'); ?>
                                </td>
                                <td class="cell-center font-tabular" style="font-size: 12px;">
                                    <?php if ($prod['tipo'] === 'salgado_diario'): ?>
                                        <span class="text-muted" title="Demanda consolidada diária">Diário</span>
                                    <?php else: ?>
                                        <?php 
                                        $alertaEstoque = ((int)$prod['estoque_atual'] <= (int)$prod['estoque_minimo']);
                                        ?>
                                        <span class="<?php echo $alertaEstoque ? 'text-danger font-weight-bold' : 'text-dark'; ?>" 
                                              title="Estoque Mínimo: <?php echo (int)$prod['estoque_minimo']; ?>">
                                            <?php echo (int)$prod['estoque_atual']; ?> un
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-center">
                                    <!-- Botão de Alternância Rápida: Ativo / Pausado (Padrão Corporativo Neutro) -->
                                    <form action="<?php echo $baseUrl; ?>/admin/produtos/toggle-pausa" method="POST" class="d-inline m-0 form-toggle-pausa">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$prod['id']; ?>">
                                        <button type="submit" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1"
                                                title="Clique para alternar o status de pausa deste item">
                                            <?php if ($prod['status_pausa'] === 'ativo'): ?>
                                                <span class="status-indicator status-active" style="padding: 0;">
                                                    <span class="bullet">●</span> Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="status-indicator status-inactive" style="padding: 0;">
                                                    <span class="bullet">●</span> Pausado
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="cell-right">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" 
                                                onclick='editarProduto(<?php echo json_encode($prod, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                title="Editar Produto">
                                            Editar
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/produtos/excluir" method="POST" class="d-inline m-0"
                                              onsubmit="return confirm('Deseja realmente inativar este produto do cardápio? Ele será mantido no histórico contábil.');">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$prod['id']; ?>">
                                            <button type="submit" class="btn btn-enterprise btn-danger btn-sm" title="Excluir Produto">
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

<!-- Modal de Cadastro e Edição de Produto -->
<div class="modal fade" id="modalProduto" tabindex="-1" aria-labelledby="modalProdutoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <form action="<?php echo $baseUrl; ?>/admin/produtos/salvar" method="POST" enctype="multipart/form-data" class="m-0">
                <?php echo $csrfField; ?>
                <input type="hidden" name="id" id="prod_id" value="0">

                <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <h5 class="modal-title font-weight-bold text-dark" id="modalProdutoLabel" style="font-size: 15px;">
                        Novo Produto do Cardápio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-4">
                            <label for="prod_codigo_interno" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Código Interno <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" id="prod_codigo_interno" name="codigo_interno" 
                                   required maxlength="30" placeholder="Ex: SAL-01, BEB-02">
                        </div>
                        <div class="col-12 col-sm-8">
                            <label for="prod_descricao" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Descrição do Produto <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="prod_descricao" name="descricao" 
                                   required maxlength="100" placeholder="Ex: Esfiha de Frango com Catupiry">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="prod_categoria_id" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Categoria <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="prod_categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="prod_preco_venda" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Preço de Venda (R$) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular text-end" id="prod_preco_venda" name="preco_venda" 
                                   required placeholder="0,00">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-4">
                            <label for="prod_tipo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Tipo de Demanda
                            </label>
                            <select class="form-select form-select-sm" id="prod_tipo" name="tipo">
                                <option value="salgado_diario">Salgado Diário (Demanda)</option>
                                <option value="industrializado_continuo">Industrializado Contínuo</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label for="prod_turno" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Turno de Disponibilidade
                            </label>
                            <select class="form-select form-select-sm" id="prod_turno" name="turno_disponibilidade">
                                <option value="ambos">Ambos os Turnos</option>
                                <option value="matutino">Apenas Matutino</option>
                                <option value="vespertino">Apenas Vespertino</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label for="prod_status_pausa" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Status Inicial
                            </label>
                            <select class="form-select form-select-sm" id="prod_status_pausa" name="status_pausa">
                                <option value="ativo">Ativo (Liberado)</option>
                                <option value="pausado">Pausado (Oculto)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="prod_estoque_atual" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Estoque Físico Atual (Unidades)
                            </label>
                            <input type="number" class="form-control form-control-sm font-tabular" id="prod_estoque_atual" name="estoque_atual" value="0" min="0">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="prod_estoque_minimo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Ponto de Estoque Mínimo
                            </label>
                            <input type="number" class="form-control form-control-sm font-tabular" id="prod_estoque_minimo" name="estoque_minimo" value="5" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="prod_foto" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Foto do Produto (Opcional)
                        </label>
                        <input type="file" class="form-control form-control-sm" id="prod_foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
                        <span class="text-muted" style="font-size: 11px;">Formatos JPG, PNG ou WebP (máx. 10 MB).</span>
                    </div>

                    <!-- Alérgenos Declarados -->
                    <div class="pt-3 border-top">
                        <label class="form-label font-weight-semibold text-secondary mb-2 d-block" style="font-size: 11px; text-transform: uppercase;">
                            Restrições Alimentares / Alérgenos Contidos
                        </label>
                        <div class="row g-2">
                            <?php foreach ($alergenos as $al): ?>
                                <div class="col-6 col-sm-4 col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input check-alergeno" type="checkbox" name="alergenos[]" 
                                               value="<?php echo (int)$al['id']; ?>" id="al_<?php echo (int)$al['id']; ?>">
                                        <label class="form-check-label text-dark" for="al_<?php echo (int)$al['id']; ?>" style="font-size: 12px;">
                                            <?php echo htmlspecialchars($al['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-4" style="border-top: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Salvar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalProduto() {
    document.getElementById('prod_id').value = '0';
    document.getElementById('prod_codigo_interno').value = '';
    document.getElementById('prod_descricao').value = '';
    document.getElementById('prod_categoria_id').value = '';
    document.getElementById('prod_preco_venda').value = '0,00';
    document.getElementById('prod_tipo').value = 'salgado_diario';
    document.getElementById('prod_turno').value = 'ambos';
    document.getElementById('prod_status_pausa').value = 'ativo';
    document.getElementById('prod_estoque_atual').value = '0';
    document.getElementById('prod_estoque_minimo').value = '5';
    document.getElementById('prod_foto').value = '';
    
    // Desmarca todos os checkboxes de alérgenos
    document.querySelectorAll('.check-alergeno').forEach(function(cb) {
        cb.checked = false;
    });

    document.getElementById('modalProdutoLabel').innerText = 'Novo Produto do Cardápio';
    var modalEl = document.getElementById('modalProduto');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function editarProduto(prod) {
    document.getElementById('prod_id').value = prod.id;
    document.getElementById('prod_codigo_interno').value = prod.codigo_interno;
    document.getElementById('prod_descricao').value = prod.descricao;
    document.getElementById('prod_categoria_id').value = prod.categoria_id;
    
    var precoFormatado = parseFloat(prod.preco_venda).toFixed(2).replace('.', ',');
    document.getElementById('prod_preco_venda').value = precoFormatado;
    
    document.getElementById('prod_tipo').value = prod.tipo;
    document.getElementById('prod_turno').value = prod.turno_disponibilidade;
    document.getElementById('prod_status_pausa').value = prod.status_pausa;
    document.getElementById('prod_estoque_atual').value = prod.estoque_atual;
    document.getElementById('prod_estoque_minimo').value = prod.estoque_minimo;
    document.getElementById('prod_foto').value = '';

    // Marca os checkboxes dos alérgenos vinculados
    var idsVinculados = (prod.alergenos_ids || '').split(',').map(function(item) { return parseInt(item, 10); });
    document.querySelectorAll('.check-alergeno').forEach(function(cb) {
        cb.checked = idsVinculados.indexOf(parseInt(cb.value, 10)) !== -1;
    });

    document.getElementById('modalProdutoLabel').innerText = 'Editar Produto: ' + prod.descricao;
    var modalEl = document.getElementById('modalProduto');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}
</script>
