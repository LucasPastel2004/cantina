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
        <a href="<?php echo $baseUrl; ?>/admin/categorias" class="btn btn-enterprise btn-primary">
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
            <div class="col-12 col-md-4">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Buscar Produto</label>
                <div class="search-input-wrapper">
                    <span class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" class="form-control form-control-sm" id="busca" name="busca" 
                           placeholder="Código interno ou descrição..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label for="filtro_categoria" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Categoria</label>
                <select class="form-select form-select-sm" id="filtro_categoria" name="categoria_id" style="border-color: #cbd5e1;">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ((string)($filtros['categoria_id'] ?? '') === (string)$cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filtro_tipo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Tipo de Item</label>
                <select class="form-select form-select-sm" id="filtro_tipo" name="tipo" style="border-color: #cbd5e1;">
                    <option value="">Todos os tipos</option>
                    <option value="salgado_diario" <?php echo (($filtros['tipo'] ?? '') === 'salgado_diario') ? 'selected' : ''; ?>>Salgado Diário</option>
                    <option value="industrializado_continuo" <?php echo (($filtros['tipo'] ?? '') === 'industrializado_continuo') ? 'selected' : ''; ?>>Industrializado</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label for="filtro_status" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Status Pausa</label>
                <select class="form-select form-select-sm" id="filtro_status" name="status_pausa" style="border-color: #cbd5e1;">
                    <option value="">Todos</option>
                    <option value="ativo" <?php echo (($filtros['status_pausa'] ?? '') === 'ativo') ? 'selected' : ''; ?>>Ativos para venda</option>
                    <option value="pausado" <?php echo (($filtros['status_pausa'] ?? '') === 'pausado') ? 'selected' : ''; ?>>Venda pausada</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1" style="height: 31px;">
                    Filtrar
                </button>
                <a href="<?php echo $baseUrl; ?>/admin/produtos" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros" style="height: 31px; padding: 0 10px;">
                    Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Produtos -->
<div class="card-enterprise">
    <div class="card-enterprise-header">
        <div class="d-flex align-items-center gap-2">
            <span>Itens do Cardápio</span>
            <span class="badge-count font-tabular"><?php echo count($produtos); ?> de <?php echo $totalRegistros ?? count($produtos); ?> itens</span>
            <?php if (!empty($totalPaginas) && $totalPaginas > 1): ?>
                <span class="text-muted small ms-1">(Pág. <span class="font-tabular"><?php echo $paginaAtual; ?></span> de <span class="font-tabular"><?php echo $totalPaginas; ?></span>)</span>
            <?php endif; ?>
        </div>
        <div class="text-secondary font-microcopy d-none d-sm-block">
            Clique no status para pausar ou liberar um item instantaneamente
        </div>
    </div>
    <div class="card-enterprise-body p-0">
        <?php if (empty($produtos)): ?>
            <?php 
            $emptyMessage = 'Nenhum produto cadastrado ou encontrado com os filtros aplicados.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-data-grid table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 52px;">Foto</th>
                            <th class="cell-center" style="width: 120px;">Código</th>
                            <th class="cell-left" style="min-width: 220px;">Descrição & Alérgenos</th>
                            <th class="cell-left" style="width: 150px;">Categoria</th>
                            <th class="cell-center" style="width: 90px;">Turno</th>
                            <th class="cell-right" style="width: 100px;">Preço</th>
                            <th class="cell-center" style="width: 100px;">Estoque</th>
                            <th class="cell-center" style="width: 130px;">Disponibilidade</th>
                            <th class="cell-right" style="width: 130px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $prod): ?>
                            <tr>
                                <td class="cell-center py-2">
                                    <div class="product-thumb">
                                        <?php if (!empty($prod['foto_url'])): ?>
                                            <img src="<?php echo $baseUrl . '/' . htmlspecialchars($prod['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                 alt="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                                <line x1="6" y1="1" x2="6" y2="4"></line>
                                                <line x1="10" y1="1" x2="10" y2="4"></line>
                                                <line x1="14" y1="1" x2="14" y2="4"></line>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="cell-center">
                                    <code class="code-tag font-tabular"><?php echo htmlspecialchars($prod['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td class="cell-left">
                                    <div class="font-weight-semibold text-dark" style="font-size: 13px;">
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
                                <td class="cell-right font-tabular font-weight-bold text-dark">
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
                                    <!-- Botão de Alternância Rápida de Pausa (Padrão Corporativo Neutro) -->
                                    <form action="<?php echo $baseUrl; ?>/admin/produtos/toggle-pausa" method="POST" class="d-inline m-0 form-toggle-pausa">
                                        <?php echo $csrfField; ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$prod['id']; ?>">
                                        <button type="submit" class="btn-status-toggle"
                                                title="Clique para alternar: <?php echo ($prod['status_pausa'] === 'ativo') ? 'Pausar venda' : 'Liberar para venda'; ?>">
                                            <?php if ($prod['status_pausa'] === 'ativo'): ?>
                                                <span class="status-indicator status-active">
                                                    <span class="bullet" aria-hidden="true">●</span> Ativo
                                                </span>
                                            <?php else: ?>
                                                <span class="status-indicator status-inactive">
                                                    <span class="bullet" aria-hidden="true">●</span> Pausado
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="cell-right">
                                    <div class="btn-action-group">
                                        <button type="button" class="btn-action" 
                                                onclick='editarProduto(<?php echo json_encode($prod, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                title="Editar Produto">
                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                            </svg>
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/produtos/excluir" method="POST" class="d-inline m-0"
                                              onsubmit="return confirm('Deseja realmente inativar este produto do cardápio? Ele será mantido no histórico contábil.');">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$prod['id']; ?>">
                                            <button type="submit" class="btn-action btn-action-delete" title="Excluir Produto">
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
            <!-- Paginação Dinâmica Conforme Configuração Oficial -->
            <?php require __DIR__ . '/../partials/pagination.php'; ?>
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
