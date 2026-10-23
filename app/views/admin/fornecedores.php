<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Fornecedores Parceiros
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$fornecedores = $fornecedores ?? [];
$busca = $busca ?? '';
$status = $status ?? '';
$totalRegistros = $totalRegistros ?? count($fornecedores);
$totalAtivos = $totalAtivos ?? 0;
$totalGeral = $totalGeral ?? $totalRegistros;
$totalPaginas = $totalPaginas ?? 1;
$paginaAtual = $paginaAtual ?? 1;
$csrfField = $csrf_field ?? '';
$csrfToken = $csrf_token ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Fornecedores Parceiros</h1>
        <p class="text-secondary text-microcopy mb-0">
            Cadastro de distribuidoras, fabricantes e parceiros comerciais vinculados ao suprimento e estoque da cantina.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModalFornecedor()">
            + Novo Fornecedor
        </button>
    </div>
</div>

<!-- Faixa de Métricas Rápidas (Stat-Strip) -->
<div class="stat-strip mb-4">
    <div class="stat-strip-item">
        <span class="stat-strip-label">Total Cadastrado</span>
        <span class="stat-strip-value font-tabular"><?php echo number_format($totalGeral, 0, ',', '.'); ?></span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Fornecedores Ativos</span>
        <span class="stat-strip-value font-tabular text-success"><?php echo number_format($totalAtivos, 0, ',', '.'); ?></span>
    </div>
    <div class="stat-strip-item" style="border-right: none;">
        <span class="stat-strip-label">Fornecedores Inativos</span>
        <span class="stat-strip-value font-tabular text-secondary"><?php echo number_format(max(0, $totalGeral - $totalAtivos), 0, ',', '.'); ?></span>
    </div>
</div>

<!-- Barra de Filtros e Busca -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/admin/fornecedores" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Buscar Fornecedor</label>
                <div class="search-input-wrapper">
                    <span class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </span>
                    <input type="text" class="form-control form-control-sm" style="padding-left: 46px !important;" id="busca" name="busca" 
                           placeholder="Nome fantasia, razão social, CNPJ ou contato..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label for="status" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Situação</label>
                <select class="form-select form-select-sm" id="status" name="status" style="border-color: #cbd5e1;">
                    <option value="">Todas as situações</option>
                    <option value="1" <?php echo ($status === '1') ? 'selected' : ''; ?>>Apenas Ativos</option>
                    <option value="0" <?php echo ($status === '0') ? 'selected' : ''; ?>>Apenas Inativos</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1">
                    Filtrar
                </button>
                <?php if (!empty($busca) || $status !== ''): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/fornecedores" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Listagem Principal de Fornecedores -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-header">
        <div class="d-flex align-items-center gap-2">
            <span>Relação de Fornecedores</span>
            <span class="badge-count"><?php echo $totalRegistros; ?> <?php echo $totalRegistros === 1 ? 'registro' : 'registros'; ?></span>
        </div>
        <div class="text-secondary font-microcopy d-none d-sm-block">
            Sincronizado com abastecimento e controle de estoque
        </div>
    </div>
    <div class="card-enterprise-body p-0">
        <?php if (empty($fornecedores)): ?>
            <?php 
            $emptyMessage = 'Nenhum fornecedor localizado';
            $emptyDescription = !empty($busca) 
                ? 'Nenhum resultado corresponde aos critérios de pesquisa informados.' 
                : 'Cadastre o primeiro fornecedor clicando no botão "+ Novo Fornecedor" acima.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 60px;">ID</th>
                            <th class="cell-left">Fornecedor / Razão Social</th>
                            <th class="cell-left" style="width: 160px;">CNPJ / CPF</th>
                            <th class="cell-left" style="width: 170px;">Telefone / WhatsApp</th>
                            <th class="cell-left" style="width: 180px;">Contato Responsável</th>
                            <th class="cell-center" style="width: 100px;">Status</th>
                            <th class="cell-right" style="width: 120px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fornecedores as $f): ?>
                            <tr>
                                <td class="cell-center">
                                    <span class="text-secondary font-tabular" style="font-size: 11px;">#<?php echo (int)$f['id']; ?></span>
                                </td>
                                <td class="cell-left">
                                    <div class="font-weight-semibold text-dark" style="font-size: 13px;">
                                        <?php echo htmlspecialchars($f['nome_fantasia'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($f['razao_social']) && $f['razao_social'] !== $f['nome_fantasia']): ?>
                                        <div class="text-secondary text-microcopy" style="font-size: 11px;">
                                            <?php echo htmlspecialchars($f['razao_social'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($f['email'])): ?>
                                        <div class="text-muted" style="font-size: 11px;">
                                            <span style="font-size: 10px;">✉</span> <?php echo htmlspecialchars($f['email'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-left">
                                    <?php if (!empty($f['cnpj_cpf'])): ?>
                                        <code class="code-tag font-tabular"><?php echo htmlspecialchars($f['cnpj_cpf'], ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php else: ?>
                                        <span class="text-muted font-microcopy">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-left">
                                    <?php if (!empty($f['telefone'])): ?>
                                        <span class="text-dark font-tabular font-weight-medium" style="font-size: 12px;">
                                            <?php echo htmlspecialchars($f['telefone'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted font-microcopy">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-left">
                                    <?php if (!empty($f['contato_responsavel'])): ?>
                                        <span class="text-secondary" style="font-size: 12px;">
                                            <?php echo htmlspecialchars($f['contato_responsavel'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted font-microcopy">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-center">
                                    <?php if (!empty($f['ativo'])): ?>
                                        <span class="status-indicator status-active" title="Fornecedor ativo">
                                            <span class="status-bullet"></span>
                                            Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-indicator status-inactive" title="Fornecedor inativo">
                                            <span class="status-bullet"></span>
                                            Inativo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" 
                                                class="btn-action" 
                                                title="Editar fornecedor" 
                                                onclick='editarFornecedor(<?php echo json_encode($f, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/fornecedores/excluir" 
                                              method="POST" 
                                              class="d-inline m-0" 
                                              data-confirm="Deseja realmente remover o fornecedor [<?php echo htmlspecialchars($f['nome_fantasia'], ENT_QUOTES, 'UTF-8'); ?>]? O histórico de movimentações anteriores permanecerá preservado para auditoria contábil.">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$f['id']; ?>">
                                            <button type="submit" class="btn-action text-danger" title="Excluir fornecedor">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
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

            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
                <div class="card-enterprise-footer d-flex justify-content-between align-items-center py-2 px-3 border-top">
                    <span class="text-secondary font-microcopy">
                        Página <strong><?php echo $paginaAtual; ?></strong> de <strong><?php echo $totalPaginas; ?></strong> 
                        (<?php echo $totalRegistros; ?> fornecedores)
                    </span>
                    <nav aria-label="Navegação da paginação">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($paginaAtual > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/fornecedores?pagina=<?php echo ($paginaAtual - 1); ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                                <li class="page-item <?php echo ($p === $paginaAtual) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/fornecedores?pagina=<?php echo $p; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($paginaAtual < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/fornecedores?pagina=<?php echo ($paginaAtual + 1); ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>">Próxima</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Cadastro e Edição de Fornecedor -->
<div class="modal fade" id="modalFornecedor" tabindex="-1" aria-labelledby="modalFornecedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);">
            <form action="<?php echo $baseUrl; ?>/admin/fornecedores/salvar" method="POST" id="formFornecedor" class="m-0">
                <?php echo $csrfField; ?>
                <input type="hidden" name="id" id="forn_id" value="0">

                <div class="modal-header py-3 px-4" style="background-color: #0f172a; color: #ffffff;">
                    <div class="d-flex align-items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <h5 class="modal-title font-weight-bold text-white mb-0" id="modalFornecedorLabel" style="font-size: 14px; letter-spacing: 0.02em;">
                            Novo Fornecedor Parceiro
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label for="forn_nome_fantasia" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Nome Fantasia / Identificação Comercial <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="forn_nome_fantasia" name="nome_fantasia" 
                                   required maxlength="150" placeholder="Ex: Padaria Pão Dourado, Distribuidora Valle...">
                        </div>
                        <div class="col-12 col-md-5">
                            <label for="forn_ativo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Situação Operacional
                            </label>
                            <select class="form-select form-select-sm" id="forn_ativo" name="ativo">
                                <option value="1">Ativo (Permite compras e entradas no estoque)</option>
                                <option value="0">Inativo (Bloqueado para novas entradas)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-7">
                            <label for="forn_razao_social" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Razão Social Oficial
                            </label>
                            <input type="text" class="form-control form-control-sm" id="forn_razao_social" name="razao_social" 
                                   maxlength="200" placeholder="Ex: Panificadora Central Ltda ME">
                        </div>
                        <div class="col-12 col-md-5">
                            <label for="forn_cnpj_cpf" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                CNPJ ou CPF
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" id="forn_cnpj_cpf" name="cnpj_cpf" 
                                   maxlength="20" placeholder="00.000.000/0000-00">
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="forn_telefone" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Telefone / WhatsApp
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" id="forn_telefone" name="telefone" 
                                   maxlength="30" placeholder="(00) 00000-0000">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="forn_contato" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Contato / Vendedor
                            </label>
                            <input type="text" class="form-control form-control-sm" id="forn_contato" name="contato_responsavel" 
                                   maxlength="100" placeholder="Nome do representante...">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="forn_email" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                E-mail Comercial
                            </label>
                            <input type="email" class="form-control form-control-sm" id="forn_email" name="email" 
                                   maxlength="100" placeholder="pedidos@fornecedor.com.br">
                        </div>

                        <div class="col-12">
                            <label for="forn_endereco" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Endereço / Localização
                            </label>
                            <input type="text" class="form-control form-control-sm" id="forn_endereco" name="endereco" 
                                   maxlength="255" placeholder="Rua, número, bairro, cidade...">
                        </div>

                        <div class="col-12">
                            <label for="forn_observacoes" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Observações Internas & Condições Comerciais
                            </label>
                            <textarea class="form-control form-control-sm" id="forn_observacoes" name="observacoes" rows="2" 
                                      placeholder="Prazos de entrega, horários permitidos de recebimento, chave Pix de faturamento..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2.5 px-4 bg-light border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted font-microcopy">Os dados atualizados refletirão imediatamente na entrada de mercadorias.</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm px-3" id="btnSalvarFornecedor">Salvar Fornecedor</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalFornecedor() {
    document.getElementById('forn_id').value = '0';
    document.getElementById('forn_nome_fantasia').value = '';
    document.getElementById('forn_razao_social').value = '';
    document.getElementById('forn_cnpj_cpf').value = '';
    document.getElementById('forn_telefone').value = '';
    document.getElementById('forn_email').value = '';
    document.getElementById('forn_contato').value = '';
    document.getElementById('forn_endereco').value = '';
    document.getElementById('forn_observacoes').value = '';
    document.getElementById('forn_ativo').value = '1';
    document.getElementById('modalFornecedorLabel').innerText = 'Novo Fornecedor Parceiro';
    document.getElementById('btnSalvarFornecedor').innerText = 'Cadastrar Fornecedor';

    var modalEl = document.getElementById('modalFornecedor');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    setTimeout(function() {
        var input = document.getElementById('forn_nome_fantasia');
        if (input) input.focus();
    }, 180);
}

function editarFornecedor(f) {
    document.getElementById('forn_id').value = f.id;
    document.getElementById('forn_nome_fantasia').value = f.nome_fantasia || '';
    document.getElementById('forn_razao_social').value = f.razao_social || '';
    document.getElementById('forn_cnpj_cpf').value = f.cnpj_cpf || '';
    document.getElementById('forn_telefone').value = f.telefone || '';
    document.getElementById('forn_email').value = f.email || '';
    document.getElementById('forn_contato').value = f.contato_responsavel || '';
    document.getElementById('forn_endereco').value = f.endereco || '';
    document.getElementById('forn_observacoes').value = f.observacoes || '';
    document.getElementById('forn_ativo').value = (f.ativo == 1) ? '1' : '0';
    document.getElementById('modalFornecedorLabel').innerText = 'Editar Fornecedor: ' + (f.nome_fantasia || 'Parceiro');
    document.getElementById('btnSalvarFornecedor').innerText = 'Salvar Alterações';

    var modalEl = document.getElementById('modalFornecedor');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    setTimeout(function() {
        var input = document.getElementById('forn_nome_fantasia');
        if (input) input.focus();
    }, 180);
}
</script>
