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
$jaFiltrado = $jaFiltrado ?? false;
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
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-enterprise btn-secondary" onclick="abrirModalHistoricoRecibos()" title="Consultar recibos emitidos para fornecedores">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.5 12.5A.5.5 0 0 1 5 12h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
            Recibos Emitidos
        </button>
        <button type="button" class="btn btn-enterprise btn-secondary" onclick="abrirModalRecibo(0, '')" title="Emitir recibo de pagamento avulso">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v13a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5v-13zM2 2v12h12V2H2z"/><path d="M4 4.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5z"/></svg>
            + Emitir Recibo
        </button>
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
            <input type="hidden" name="filtrar" value="1">
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
                <?php if (!empty($jaFiltrado)): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/fornecedores" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros e Ocultar Lista">
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
            <?php if (!empty($jaFiltrado)): ?>
                <span class="badge-count"><?php echo $totalRegistros; ?> <?php echo $totalRegistros === 1 ? 'registro' : 'registros'; ?></span>
            <?php endif; ?>
        </div>
        <div class="text-secondary font-microcopy d-none d-sm-block">
            <?php if (!empty($jaFiltrado)): ?>
                Sincronizado com abastecimento e controle de estoque
            <?php else: ?>
                Aguardando filtragem para exibição
            <?php endif; ?>
        </div>
    </div>
    <div class="card-enterprise-body p-0">
        <?php if (empty($jaFiltrado)): ?>
            <?php 
            $emptyMessage = 'Não há nada para mostrar aqui';
            $emptyDescription = 'Utilize os filtros acima e clique em "Filtrar" para pesquisar fornecedores específicos ou clique em "Filtrar" para carregar a lista geral com todos os itens.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php elseif (empty($fornecedores)): ?>
            <?php 
            $emptyMessage = 'Nenhum fornecedor encontrado';
            $emptyDescription = 'Nenhum resultado corresponde aos critérios de pesquisa informados.';
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
                                                class="btn-action text-primary" 
                                                title="Emitir Recibo de Pagamento" 
                                                onclick="abrirModalRecibo(<?php echo (int)$f['id']; ?>, '<?php echo addslashes(htmlspecialchars($f['nome_fantasia'], ENT_QUOTES, 'UTF-8')); ?>')">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                                <polyline points="10 9 9 9 8 9"></polyline>
                                            </svg>
                                        </button>
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
                                              data-confirm="Deseja realmente remover o fornecedor [<?php echo htmlspecialchars($f['nome_fantasia'], ENT_QUOTES, 'UTF-8'); ?>]? O histórico de movimentações anteriores permanecerá preservado para auditoria contábil."
                                              data-confirm-title="Remover Fornecedor"
                                              data-confirm-type="danger"
                                              data-confirm-btn="Sim, Remover">
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
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/fornecedores?filtrar=1&pagina=<?php echo ($paginaAtual - 1); ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>">Anterior</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                                <li class="page-item <?php echo ($p === $paginaAtual) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/fornecedores?filtrar=1&pagina=<?php echo $p; ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($paginaAtual < $totalPaginas): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/fornecedores?filtrar=1&pagina=<?php echo ($paginaAtual + 1); ?>&busca=<?php echo urlencode($busca); ?>&status=<?php echo urlencode($status); ?>">Próxima</a>
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


<!-- MODAL: EMISSÃO DE RECIBO DE PAGAMENTO A FORNECEDOR -->
<div class="modal fade" id="modalReciboFornecedor" tabindex="-1" aria-labelledby="modalReciboLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content card-enterprise border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header py-3 px-4 bg-light border-bottom">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark mb-0" id="modalReciboLabel" style="font-size: 15px;">
                        Emitir Recibo de Pagamento a Fornecedor
                    </h5>
                    <p class="text-secondary text-microcopy mb-0 mt-0.5">
                        Registra o pagamento a fornecedor parceiro, debita automaticamente no Livro Caixa e emite comprovante A4 timbrado.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <form action="<?php echo $baseUrl; ?>/admin/fornecedores/recibo/salvar" method="POST" id="formReciboFornecedor">
                <?php echo $csrfField; ?>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <label for="recibo_fornecedor_id" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Fornecedor Favorecido <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="recibo_fornecedor_id" name="fornecedor_id" required>
                                <option value="">Selecione o Fornecedor...</option>
                                <?php 
                                $todosFornecedores = \App\Models\Fornecedor::listarTodos('1', '');
                                foreach ($todosFornecedores as $forn): 
                                ?>
                                    <option value="<?php echo (int)$forn['id']; ?>">
                                        <?php echo htmlspecialchars($forn['nome_fantasia'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($forn['cnpj_cpf'])): ?> (<?php echo htmlspecialchars($forn['cnpj_cpf'], ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-5">
                            <label for="recibo_data" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Data do Pagamento <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm font-tabular" id="recibo_data" name="data_pagamento" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="recibo_valor" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Valor do Pagamento (R$) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-secondary font-tabular">R$</span>
                                <input type="text" class="form-control form-control-sm font-tabular fw-bold" id="recibo_valor" name="valor" 
                                       placeholder="0,00" required data-mask-moeda="true" style="font-size: 15px;">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="recibo_forma" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Forma de Liquidação <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="recibo_forma" name="forma_pagamento" required>
                                <option value="pix">Transferência Instantânea Pix</option>
                                <option value="dinheiro">Dinheiro em Espécie (Caixa / Gaveta)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="recibo_descricao" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Descrição / Referência do Pagamento <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="recibo_descricao" name="descricao" 
                                   maxlength="200" placeholder="Ex: Pagamento Salgado, Pagamento Cupcake, Fornecimento de Pães..." required>
                            
                            <!-- Botões de Atalhos Rápidos de Descrição -->
                            <div class="d-flex flex-wrap gap-1 mt-1.5">
                                <span class="text-microcopy text-secondary me-1 align-self-center">Atalhos:</span>
                                <button type="button" class="btn btn-xs btn-enterprise btn-secondary py-0 px-2" style="font-size: 10.5px;" onclick="aplicarAtalhoRecibo('Pagamento Salgado')">Pagamento Salgado</button>
                                <button type="button" class="btn btn-xs btn-enterprise btn-secondary py-0 px-2" style="font-size: 10.5px;" onclick="aplicarAtalhoRecibo('Pagamento Cupcake')">Pagamento Cupcake</button>
                                <button type="button" class="btn btn-xs btn-enterprise btn-secondary py-0 px-2" style="font-size: 10.5px;" onclick="aplicarAtalhoRecibo('Fornecimento de Pães')">Fornecimento de Pães</button>
                                <button type="button" class="btn btn-xs btn-enterprise btn-secondary py-0 px-2" style="font-size: 10.5px;" onclick="aplicarAtalhoRecibo('Bebidas & Sucos')">Bebidas & Sucos</button>
                                <button type="button" class="btn btn-xs btn-enterprise btn-secondary py-0 px-2" style="font-size: 10.5px;" onclick="aplicarAtalhoRecibo('Doces & Sobremesas')">Doces & Sobremesas</button>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="recibo_observacoes" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Observações / Número da NF (Opcional)
                            </label>
                            <textarea class="form-control form-control-sm" id="recibo_observacoes" name="observacoes" rows="2" 
                                      placeholder="Nº da Nota Fiscal, chave Pix de destino, lote de entrega ou anotações internas..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-check-sm">
                                <input class="form-check-input" type="checkbox" name="imprimir_direto" value="1" id="recibo_imprimir_direto" checked>
                                <label class="form-check-label text-secondary font-weight-medium" for="recibo_imprimir_direto" style="font-size: 12px;">
                                    Abrir espelho oficial A4 para impressão / salvar PDF imediatamente após salvar
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2.5 px-4 bg-light border-top d-flex justify-content-between align-items-center">
                    <span class="text-muted font-microcopy">Este pagamento alimentará automaticamente a coluna de Débito no Livro Caixa Diário.</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm px-3">Salvar e Emitir Recibo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: HISTÓRICO DE RECIBOS EMITIDOS -->
<div class="modal fade" id="modalHistoricoRecibos" tabindex="-1" aria-labelledby="modalHistoricoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content card-enterprise border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header py-3 px-4 bg-light border-bottom">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark mb-0" id="modalHistoricoLabel" style="font-size: 15px;">
                        Histórico de Recibos Emitidos para Fornecedores
                    </h5>
                    <p class="text-secondary text-microcopy mb-0 mt-0.5">
                        Relação de comprovantes de pagamentos emitidos e liquidados para controle financeiro e conferência.
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 480px;">
                    <table class="table-enterprise mb-0">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="cell-center" style="width: 130px;">Nº Recibo</th>
                                <th class="cell-center" style="width: 95px;">Data</th>
                                <th class="cell-left">Fornecedor Favorecido</th>
                                <th class="cell-left">Descrição / Referência</th>
                                <th class="cell-center" style="width: 100px;">Forma</th>
                                <th class="cell-right" style="width: 120px;">Valor</th>
                                <th class="cell-center" style="width: 80px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaHistoricoRecibosBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary">Carregando recibos emitidos...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer py-2.5 px-4 bg-light border-top d-flex justify-content-between align-items-center">
                <span class="text-muted font-microcopy">Todos os recibos ativos estão sincronizados com os lançamentos de débitos do Livro Caixa.</span>
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
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

function abrirModalRecibo(fornecedorId, fornecedorNome) {
    var select = document.getElementById('recibo_fornecedor_id');
    if (select && fornecedorId > 0) {
        select.value = fornecedorId;
    }
    document.getElementById('recibo_data').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('recibo_valor').value = '';
    document.getElementById('recibo_forma').value = 'pix';
    document.getElementById('recibo_descricao').value = '';
    document.getElementById('recibo_observacoes').value = '';
    
    var titleEl = document.getElementById('modalReciboLabel');
    if (titleEl) {
        titleEl.innerText = fornecedorNome ? 'Emitir Recibo de Pagamento — ' + fornecedorNome : 'Emitir Recibo de Pagamento a Fornecedor';
    }

    var modalEl = document.getElementById('modalReciboFornecedor');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    setTimeout(function() {
        var valInput = document.getElementById('recibo_valor');
        if (valInput) valInput.focus();
    }, 180);
}

function aplicarAtalhoRecibo(texto) {
    var descInput = document.getElementById('recibo_descricao');
    if (descInput) {
        descInput.value = texto;
        descInput.focus();
    }
}

function abrirModalHistoricoRecibos(fornecedorId) {
    var tbody = document.getElementById('tabelaHistoricoRecibosBody');
    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">Carregando recibos emitidos...</td></tr>';
    }

    var url = '<?php echo $baseUrl; ?>/admin/fornecedores/recibos/json';
    if (fornecedorId > 0) {
        url += '?fornecedor_id=' + fornecedorId;
    }

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!tbody) return;
            if (!data.sucesso || !data.recibos || data.recibos.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Nenhum recibo de pagamento localizado.</td></tr>';
                return;
            }

            var html = '';
            data.recibos.forEach(function(r) {
                var valorFmt = parseFloat(r.valor).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                var dataFmt = r.data_pagamento ? r.data_pagamento.split('-').reverse().join('/') : '—';
                var formaBadge = (r.forma_pagamento === 'pix') 
                    ? '<span class="status-indicator status-active"><span class="status-bullet"></span>Pix</span>'
                    : '<span class="status-indicator" style="color: #475569;"><span class="status-bullet" style="background-color: #64748b;"></span>Dinheiro</span>';

                html += '<tr>' +
                    '<td class="cell-center font-tabular" style="font-size: 11px; font-weight: 600; color: #1d4ed8;">' + (r.numero_recibo || '—') + '</td>' +
                    '<td class="cell-center font-tabular">' + dataFmt + '</td>' +
                    '<td class="cell-left font-weight-semibold">' + (r.fornecedor_nome || '—') + '</td>' +
                    '<td class="cell-left text-secondary" style="font-size: 12px;">' + (r.descricao || '—') + '</td>' +
                    '<td class="cell-center">' + formaBadge + '</td>' +
                    '<td class="cell-right font-tabular font-weight-bold" style="font-size: 13px;">R$ ' + valorFmt + '</td>' +
                    '<td class="cell-center">' +
                        '<div class="d-inline-flex gap-1">' +
                            '<a href="<?php echo $baseUrl; ?>/admin/fornecedores/recibo/' + r.id + '/imprimir" target="_blank" class="btn-action text-primary" title="Imprimir / Salvar PDF">' +
                                '<svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>' +
                            '</a>' +
                            '<form action="<?php echo $baseUrl; ?>/admin/fornecedores/recibo/excluir" method="POST" class="d-inline m-0" data-confirm="Deseja realmente estornar/cancelar este recibo [' + (r.numero_recibo || '') + '] de R$ ' + valorFmt + '?" data-confirm-title="Estornar Recibo" data-confirm-type="danger" data-confirm-btn="Sim, Estornar">' +
                                '<?php echo $csrfField; ?>' +
                                '<input type="hidden" name="id" value="' + r.id + '">' +
                                '<button type="submit" class="btn-action text-danger" title="Estornar recibo">' +
                                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>' +
                                '</button>' +
                            '</form>' +
                        '</div>' +
                    '</td>' +
                '</tr>';
            });
            tbody.innerHTML = html;
        })
        .catch(function(err) {
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Falha ao consultar recibos. Tente novamente.</td></tr>';
            }
        });

    var modalEl = document.getElementById('modalHistoricoRecibos');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}
</script>
