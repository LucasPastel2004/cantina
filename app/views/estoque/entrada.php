<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela: Entrada de Estoque & Mercadorias (Manual sem NF-e em Lote por Fornecedor)
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md (Anti-Slop Enterprise Edition)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$produtos = $produtos ?? [];
$categorias = $categorias ?? [];
$fornecedores = $fornecedores ?? [];
$mapaFornecedores = $mapaFornecedores ?? [];
$produtoPreSel = $produtoPreSel ?? null;
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo (Padrão Unificado com Lançamento de Perdas - Redirecionamento na mesma aba) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Voltar ao Estoque
                </a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Registro Operacional de Entrada de Mercadorias</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/relatorios/estoque-filtros?tipo_movimentacao=entrada" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5" title="Abrir Relatório de Movimentações de Estoque">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                    <path d="M4.5 10.5A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                </svg>
                <span>Relatório de Movimentações de Estoque</span>
            </a>
        </div>
    </div>

    <!-- Diretriz Operacional — Abastecimento & Controle Físico de Estoque (Largura Total) -->
    <div class="card-enterprise p-3 mb-3 bg-light border-0 w-100" style="border-left: 4px solid #1d4ed8 !important;">
        <span class="small fw-bold text-primary d-block mb-1">Diretriz Operacional — Abastecimento & Controle Físico de Estoque</span>
        <p class="text-secondary small mb-0" style="line-height: 1.45;">
            As entradas de estoque incrementam em tempo real o saldo disponível na grade do PDV e nas reservas de pedidos online. Todos os lançamentos registram o operador logado, data da movimentação, documento e fornecedor para fins de controle e auditoria contábil. Informe o fornecedor da entrega, clique em <strong>"Carregar Produtos"</strong> para filtrar os itens específicos e monte o lote antes de gravar.
        </p>
    </div>

    <!-- Formulário Principal de Submissão do Lote -->
    <form method="POST" action="<?php echo $baseUrl; ?>/estoque/entrada/salvar" id="formEntradaLote" onsubmit="return validarGravacaoLote(event)">
        <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">
        <input type="hidden" name="itens_json" id="inputItensJson" value="[]">

        <!-- Bloco 1: Dados Gerais do Documento / Lote de Entrada com Botão Carregar no Topo -->
        <div class="card-enterprise p-3 mb-3 w-100">
            <div class="border-bottom pb-2 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h6 fw-bold text-slate-900 mb-0">1. Dados Gerais do Documento / Lote de Entrada</h2>
                    <span class="text-muted" style="font-size: 12px;">Identifique o fornecedor parceiro e as referências da entrega física.</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small me-2 d-none d-md-inline">
                        Operador: <strong class="text-slate-800"><?php echo htmlspecialchars(\App\Core\Auth::user()['nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></strong>
                    </span>
                    <button type="button" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3" onclick="carregarProdutosFornecedor()" id="btnCarregarFornTopo" title="Carregar os produtos vinculados a este fornecedor">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                        </svg>
                        <span>Carregar Produtos</span>
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <!-- Fornecedor com Autocomplete (Datalist) e Carregamento Rápido -->
                <div class="col-12 col-md-5">
                    <label for="inputFornecedor" class="form-label small fw-semibold text-muted mb-1">Fornecedor / Distribuidor: *</label>
                    <div class="input-group input-group-sm">
                        <input type="text" 
                               name="fornecedor" 
                               id="inputFornecedor" 
                               class="form-control form-control-sm" 
                               list="listaFornecedores" 
                               required 
                               autocomplete="off"
                               placeholder="Digite ou selecione o fornecedor..."
                               onkeydown="if(event.key === 'Enter'){ event.preventDefault(); carregarProdutosFornecedor(); }">
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="carregarProdutosFornecedor()" title="Carregar produtos deste fornecedor">
                            Carregar
                        </button>
                    </div>
                    <datalist id="listaFornecedores">
                        <?php foreach ($fornecedores as $forn): ?>
                            <option value="<?php echo htmlspecialchars($forn, ENT_QUOTES, 'UTF-8'); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <div class="form-text" style="font-size: 11px;">Digite ou selecione o fornecedor e clique em <strong>Carregar</strong> para listar os produtos dele.</div>
                </div>

                <!-- Data da Movimentação -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="inputDataMovimentacao" class="form-label small fw-semibold text-muted mb-1">Data da Movimentação: *</label>
                    <input type="date" 
                           name="data_movimentacao" 
                           id="inputDataMovimentacao" 
                           class="form-control form-control-sm font-tabular" 
                           value="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d'); ?>" 
                           required>
                    <div class="form-text" style="font-size: 11px;">Data do recebimento físico na cantina.</div>
                </div>

                <!-- Nº Documento / NF / Pedido -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label for="inputNumeroDocumento" class="form-label small fw-semibold text-muted mb-1">Nº Documento / Recibo / NF (Opcional):</label>
                    <input type="text" 
                           name="numero_documento" 
                           id="inputNumeroDocumento" 
                           class="form-control form-control-sm" 
                           placeholder="Ex: NF 1045, Pedido 382, Recibo...">
                    <div class="form-text" style="font-size: 11px;">Referência documental para conciliação contábil.</div>
                </div>

                <!-- Observações do Lote -->
                <div class="col-12">
                    <label for="inputObservacoes" class="form-label small fw-semibold text-muted mb-1">Observações da Entrega (Opcional):</label>
                    <input type="text" 
                           name="observacoes" 
                           id="inputObservacoes" 
                           class="form-control form-control-sm" 
                           placeholder="Ex: Entrega matutina antes do primeiro recreio, conferência realizada por atendente...">
                </div>
            </div>
        </div>

        <!-- Bloco 2: Catálogo de Produtos do Fornecedor (Inicialmente Vazio) -->
        <div class="card-enterprise p-3 mb-3 w-100">
            <div class="border-bottom pb-2 mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h6 fw-bold text-slate-900 mb-0">2. Produtos do Fornecedor para Entrada</h2>
                    <span class="text-muted" style="font-size: 12px;" id="subtituloCatalogo">Informe o fornecedor acima e clique em "Carregar Produtos" para exibir os itens vinculados.</span>
                </div>
                <div class="d-flex align-items-center gap-2" id="acoesCatalogoTopo" style="display: none !important;">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="exibirCatalogoCompleto()" id="btnVerCatalogoCompleto" style="font-size: 11.5px;">
                        Ver Catálogo Completo
                    </button>
                </div>
            </div>

            <!-- Barra de Filtros Adicionais (Ativa quando há produtos carregados) -->
            <div class="row g-2 align-items-end mb-3" id="barraFiltrosSecundarios" style="display: none;">
                <div class="col-12 col-md-4">
                    <label for="filtroCategoria" class="form-label small fw-semibold text-muted mb-1">Filtrar por Categoria:</label>
                    <select id="filtroCategoria" class="form-select form-select-sm" onchange="filtrarProdutosExibidos()">
                        <option value="0">-- Todas as Categorias --</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo (int)$cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label for="buscaProdutoInput" class="form-label small fw-semibold text-muted mb-1">Refinar Busca por Código ou Nome:</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        </span>
                        <input type="text" id="buscaProdutoInput" class="form-control form-control-sm" placeholder="Ex: Coxinha, Suco, 1001..." onkeyup="filtrarProdutosExibidos()">
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="limparBuscaProdutos()">Limpar</button>
                    </div>
                </div>
            </div>

            <!-- Tabela de Produtos do Fornecedor -->
            <div class="border rounded" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-enterprise table-hover align-middle mb-0" id="tabelaCatalogo">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th style="width: 120px;">Código</th>
                            <th>Produto / Descrição</th>
                            <th style="width: 160px;">Categoria</th>
                            <th style="width: 120px;" class="text-end">Estoque Atual</th>
                            <th style="width: 130px;" class="text-end">Custo Ref.</th>
                            <th style="width: 130px;" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="corpoCatalogo">
                        <!-- Inicialmente vazia conforme solicitação -->
                        <tr id="linhaInicialCatalogo">
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="py-2">
                                    <svg width="32" height="32" viewBox="0 0 16 16" fill="#94a3b8" class="mb-2">
                                        <path d="M0 1.5A1.5 1.5 0 0 1 1.5 0h13A1.5 1.5 0 0 1 16 1.5v2A1.5 1.5 0 0 1 14.5 5h-13A1.5 1.5 0 0 1 0 3.5v-2zM1.5 1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-13z"/>
                                        <path d="M2 5.5a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 .5.5v8.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 14V5.5zm1 .5v8a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V6H3z"/>
                                    </svg>
                                    <div class="fw-semibold text-slate-700 small">Nenhum produto listado no momento</div>
                                    <div class="text-secondary small" style="font-size: 11.5px;">Informe o fornecedor no Bloco 1 acima e clique no botão <strong>"Carregar Produtos"</strong> para filtrar os itens desta entrega.</div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 px-1 text-muted" style="font-size: 11px;">
                <span id="contagemCatalogo">0 produtos carregados</span>
                <span>Clique no botão <strong>"+ Adicionar"</strong> no canto da linha para abrir a janela de quantidade.</span>
            </div>
        </div>

        <!-- Bloco 3: Grade de Itens do Lote de Entrada (O Lançamento em Lote) -->
        <div class="card-enterprise w-100 mb-4">
            <div class="card-enterprise-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="card-enterprise-title">3. Itens do Lançamento de Entrada (Lote Atual)</span>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1 font-tabular" id="badgeTotalItensLote" style="font-size: 11px;">0 itens</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="limparListaLote()" id="btnLimparLista" disabled>
                        Limpar Lista
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-enterprise table-hover align-middle mb-0" id="tabelaLoteItens">
                    <thead>
                        <tr>
                            <th style="width: 45px;" class="text-center">#</th>
                            <th style="width: 120px;">Código</th>
                            <th>Produto</th>
                            <th style="width: 120px;" class="text-end">Estoque Atual</th>
                            <th style="width: 130px;" class="text-end">Qtd Entrada</th>
                            <th style="width: 130px;" class="text-end">Novo Estoque</th>
                            <th style="width: 140px;" class="text-end">Preço Custo (R$)</th>
                            <th style="width: 140px;" class="text-end">Subtotal Custo</th>
                            <th style="width: 70px;" class="text-center">Ação</th>
                        </tr>
                    </thead>
                    <tbody id="corpoLoteItens">
                        <tr id="linhaVaziaLote">
                            <td colspan="9" class="text-center py-4 text-muted">
                                <em>Nenhum produto adicionado ao lote. Selecione os produtos no catálogo acima para montar a entrega.</em>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Resumo e Totais do Lote -->
            <div class="p-3 bg-light border-top">
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-7">
                        <div class="d-flex flex-wrap gap-4 text-slate-800">
                            <div>
                                <span class="text-muted d-block small" style="font-size: 11px;">PRODUTOS NO LOTE</span>
                                <strong class="fs-6 font-tabular text-slate-900" id="resumoQtdProdutos">0</strong>
                            </div>
                            <div class="border-start ps-3">
                                <span class="text-muted d-block small" style="font-size: 11px;">VOLUME TOTAL (UNIDADES)</span>
                                <strong class="fs-6 font-tabular text-success" id="resumoVolumeUnidades">0 un</strong>
                            </div>
                            <div class="border-start ps-3">
                                <span class="text-muted d-block small" style="font-size: 11px;">VALOR TOTAL DO LOTE</span>
                                <strong class="fs-6 font-tabular text-slate-900" id="resumoValorTotal">R$ 0,00</strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-5 d-flex justify-content-md-end gap-2">
                        <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">Cancelar</a>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3" id="btnGravarLote" disabled>
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M11 2H9v3h2V2z"/>
                                <path d="M1.5 0A1.5 1.5 0 0 0 0 1.5v13A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5V2.914a1.5 1.5 0 0 0-.44-1.06L14.147.439A1.5 1.5 0 0 0 13.086 0H1.5zM4 1h5v4H4V1zM1 1.5a.5.5 0 0 1 .5-.5h1v4.5A1.5 1.5 0 0 0 4 7h8a1.5 1.5 0 0 0 1.5-1.5V1h.586a.5.5 0 0 1 .354.146l1.414 1.415a.5.5 0 0 1 .146.353V14.5a.5.5 0 0 1-.5.5H1.5a.5.5 0 0 1-.5-.5V1.5z"/>
                            </svg>
                            <span>Gravar Entrada em Estoque</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal Pop-up Centralizado de Adição de Produto ao Lote (Design Polido Anti-Slop Enterprise) -->
<div class="modal fade" id="modalAdicionarProduto" tabindex="-1" aria-labelledby="modalAdicionarProdutoTitulo" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 10px; overflow: hidden;">
            <!-- Cabeçalho Refinado -->
            <div class="modal-header border-bottom py-2.5 px-3 bg-light">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-inline-flex p-1.5 rounded bg-primary text-white" style="line-height: 0;">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title fs-6 fw-bold text-slate-900 mb-0" id="modalAdicionarProdutoTitulo">Adicionar Mercadoria ao Lote</h5>
                        <span class="text-muted" style="font-size: 11.5px;">Defina a quantidade de unidades físicas e o preço de custo.</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <!-- Corpo do Modal -->
            <div class="modal-body p-3.5">
                <form id="formModalItem" onsubmit="return confirmarItemModal(event)">
                    <input type="hidden" id="modalProdutoId" value="">
                    
                    <!-- Card de Identificação do Produto com Borda Esquerda de Acento -->
                    <div class="p-3 mb-3 bg-white rounded border" style="border-left: 3.5px solid #1d4ed8 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <span class="text-slate-900 fw-bold fs-6 mb-0" id="modalProdutoDescricao">-</span>
                            <code class="code-tag px-2 py-0.5" id="modalProdutoCodigo" style="font-size: 11px;">-</code>
                        </div>
                        <div class="d-flex flex-wrap justify-content-between align-items-center text-muted small pt-1 border-top" style="font-size: 11.5px;">
                            <span>Categoria: <strong class="text-slate-700" id="modalProdutoCategoria">-</strong></span>
                            <span class="d-inline-flex align-items-center gap-1.5">
                                <span class="d-inline-block rounded-circle bg-primary" style="width: 7px; height: 7px;"></span>
                                Saldo Físico Atual: <strong class="text-slate-900 font-tabular" id="modalProdutoEstoqueAtual">0 un</strong>
                            </span>
                        </div>
                    </div>

                    <!-- Campos de Entrada (Quantidade & Preço de Custo) -->
                    <div class="row g-3 mb-3">
                        <!-- Quantidade a Dar Entrada -->
                        <div class="col-12 col-sm-6">
                            <label for="modalInputQuantidade" class="form-label small fw-semibold text-slate-700 mb-1">
                                Qtd a Adicionar (un): <span class="text-danger">*</span>
                            </label>
                            <input type="number" 
                                   id="modalInputQuantidade" 
                                   class="form-control font-tabular fw-bold text-slate-900 text-center" 
                                   min="1" 
                                   step="1" 
                                   required 
                                   placeholder="Ex: 25" 
                                   style="font-size: 17px; height: 42px; border-color: #cbd5e1;"
                                   oninput="atualizarSimulacaoSaldoModal()">
                            <div class="form-text" style="font-size: 11px;">Unidades físicas recebidas.</div>
                        </div>

                        <!-- Preço de Custo Unitário -->
                        <div class="col-12 col-sm-6">
                            <label for="modalInputPrecoCusto" class="form-label small fw-semibold text-slate-700 mb-1">
                                Preço de Custo Unit. (R$):
                            </label>
                            <div class="input-group" style="height: 42px;">
                                <span class="input-group-text bg-light text-slate-600 fw-semibold" style="border-color: #cbd5e1; font-size: 13px;">R$</span>
                                <input type="text" 
                                       id="modalInputPrecoCusto" 
                                       class="form-control font-tabular input-money" 
                                       placeholder="0,00" 
                                       style="font-size: 15px; border-color: #cbd5e1;"
                                       oninput="atualizarSimulacaoSaldoModal()">
                            </div>
                            <div class="form-text" style="font-size: 11px;">Atualiza o custo de referência.</div>
                        </div>
                    </div>

                    <!-- Painel Polido de Simulação do Impacto no Saldo Físico -->
                    <div class="p-3 rounded border" style="background-color: #f8fafc; border-color: #e2e8f0;">
                        <span class="small fw-semibold text-muted d-block mb-2" style="font-size: 10.5px; letter-spacing: 0.04em;">
                            SIMULAÇÃO DO SALDO FÍSICO APÓS A ENTRADA
                        </span>
                        <div class="row g-2 text-center font-tabular align-items-center">
                            <!-- Saldo Atual -->
                            <div class="col-4">
                                <div class="p-2 rounded bg-white border">
                                    <span class="d-block text-muted" style="font-size: 10px;">SALDO ATUAL</span>
                                    <strong class="text-slate-800 fs-6" id="simulacaoAtual">0</strong> <span class="small text-muted">un</span>
                                </div>
                            </div>
                            <!-- Quantidade Entrada -->
                            <div class="col-4">
                                <div class="p-2 rounded border" style="background-color: #f0fdf4; border-color: #bbf7d0;">
                                    <span class="d-block text-success fw-semibold" style="font-size: 10px;">+ ENTRADA</span>
                                    <strong class="text-success fs-6">+<span id="simulacaoEntrada">0</span></strong> <span class="small text-success">un</span>
                                </div>
                            </div>
                            <!-- Novo Saldo -->
                            <div class="col-4">
                                <div class="p-2 rounded border" style="background-color: #eff6ff; border-color: #bfdbfe;">
                                    <span class="d-block text-primary fw-semibold" style="font-size: 10px;">= NOVO SALDO</span>
                                    <strong class="text-primary fs-6" id="simulacaoNovo">0</strong> <span class="small text-primary">un</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rodapé com Botões -->
                    <div class="d-flex justify-content-end gap-2 mt-3 pt-3 border-top">
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                            </svg>
                            <span>Adicionar ao Lote</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Dados Mestres Injetados para Autonomia Client-side -->
<script>
var catalogoProdutosCompleto = <?php echo json_encode(array_map(function($p) {
    return [
        'id'            => (int)$p['id'],
        'codigo'        => (string)$p['codigo_interno'],
        'descricao'     => (string)$p['descricao'],
        'categoria_id'  => (int)$p['categoria_id'],
        'categoria_nome'=> (string)($p['categoria_nome'] ?? 'Geral'),
        'estoque_atual' => (int)$p['estoque_atual'],
        'preco_custo'   => (float)($p['preco_custo'] ?? 0),
        'preco_venda'   => (float)$p['preco_venda']
    ];
}, $produtos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

var mapaFornecedoresProdutos = <?php echo json_encode(array_map(function($m) {
    return [
        'fornecedor_norm' => (string)$m['fornecedor_norm'],
        'fornecedor_nome' => (string)$m['fornecedor_nome'],
        'produto_id'      => (int)$m['produto_id']
    ];
}, $mapaFornecedores), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

var produtosExibidos = [];
var loteItens = [];
var modalInstance = null;
var fornecedorCarregadoAtual = '';

function formatarMoeda(val) {
    return 'R$ ' + parseFloat(val || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function parseMoedaBR(str) {
    if (!str) return 0;
    if (typeof str === 'number') return str;
    var limpo = str.replace(/[^\d,\.]/g, '');
    if (limpo.indexOf(',') !== -1) {
        limpo = limpo.replace(/\./g, '').replace(',', '.');
    }
    var num = parseFloat(limpo);
    return isNaN(num) ? 0 : num;
}

/**
 * Carrega exclusivamente os produtos vinculados ao fornecedor digitado
 */
function carregarProdutosFornecedor() {
    var inputForn = document.getElementById('inputFornecedor');
    var fornDigitado = (inputForn.value || '').trim();

    if (fornDigitado.length < 2) {
        alert('Por favor, informe ou selecione o nome do fornecedor antes de carregar os produtos.');
        inputForn.focus();
        return;
    }

    var fornNorm = fornDigitado.toLowerCase();
    fornecedorCarregadoAtual = fornDigitado;

    // Localiza os IDs de produtos associados a este fornecedor
    var idsAssociados = [];
    mapaFornecedoresProdutos.forEach(function(m) {
        if (m.fornecedor_norm === fornNorm || m.fornecedor_norm.indexOf(fornNorm) !== -1 || fornNorm.indexOf(m.fornecedor_norm) !== -1) {
            if (idsAssociados.indexOf(m.produto_id) === -1) {
                idsAssociados.push(m.produto_id);
            }
        }
    });

    var subtitulo = document.getElementById('subtituloCatalogo');
    var barraFiltros = document.getElementById('barraFiltrosSecundarios');
    var acaoTopo = document.getElementById('acoesCatalogoTopo');

    if (idsAssociados.length > 0) {
        // Encontrou produtos vinculados no histórico
        produtosExibidos = catalogoProdutosCompleto.filter(function(p) {
            return idsAssociados.indexOf(p.id) !== -1;
        });

        subtitulo.innerHTML = 'Mostrando <strong>' + produtosExibidos.length + ' produto(s)</strong> vinculados ao histórico do fornecedor <strong>' + escapeHtml(fornDigitado) + '</strong>.';
        barraFiltros.style.display = 'flex';
        acaoTopo.style.setProperty('display', 'flex', 'important');

        renderizarTabelaCatalogo(produtosExibidos);
    } else {
        // Nenhum produto associado previamente
        produtosExibidos = [];
        barraFiltros.style.display = 'none';
        acaoTopo.style.setProperty('display', 'flex', 'important');

        var corpo = document.getElementById('corpoCatalogo');
        var contagem = document.getElementById('contagemCatalogo');

        corpo.innerHTML = 
            '<tr><td colspan="6" class="text-center py-4 text-muted">' +
                '<div class="py-2">' +
                    '<div class="fw-semibold text-slate-800 small mb-1">Nenhum produto com histórico registrado para "' + escapeHtml(fornDigitado) + '"</div>' +
                    '<p class="text-secondary small mb-3" style="font-size: 12px;">Este fornecedor é novo ou ainda não possui itens associados a ele no sistema.</p>' +
                    '<button type="button" class="btn btn-enterprise btn-primary btn-sm px-3" onclick="exibirCatalogoCompleto()">' +
                        'Selecionar Produtos do Catálogo Geral' +
                    '</button>' +
                '</div>' +
            '</td></tr>';

        contagem.textContent = '0 produtos para este fornecedor';
        subtitulo.textContent = 'Fornecedor novo: selecione os produtos do cardápio para vinculá-los a esta entrega.';
    }
}

/**
 * Exibe o catálogo completo para vincular produtos a um novo fornecedor
 */
function exibirCatalogoCompleto() {
    produtosExibidos = catalogoProdutosCompleto;
    document.getElementById('barraFiltrosSecundarios').style.display = 'flex';
    document.getElementById('acoesCatalogoTopo').style.setProperty('display', 'flex', 'important');
    document.getElementById('subtituloCatalogo').innerHTML = 'Exibindo <strong>todo o catálogo geral</strong> da cantina para seleção e inclusão nesta entrega.';
    renderizarTabelaCatalogo(produtosExibidos);
}

function filtrarProdutosExibidos() {
    var catId = parseInt(document.getElementById('filtroCategoria').value, 10) || 0;
    var busca = (document.getElementById('buscaProdutoInput').value || '').trim().toLowerCase();

    var filtrados = produtosExibidos.filter(function(p) {
        if (catId > 0 && p.categoria_id !== catId) {
            return false;
        }
        if (busca !== '') {
            var termoCod = p.codigo.toLowerCase();
            var termoDesc = p.descricao.toLowerCase();
            if (termoCod.indexOf(busca) === -1 && termoDesc.indexOf(busca) === -1) {
                return false;
            }
        }
        return true;
    });

    renderizarTabelaCatalogo(filtrados);
}

function limparBuscaProdutos() {
    document.getElementById('buscaProdutoInput').value = '';
    document.getElementById('filtroCategoria').value = '0';
    renderizarTabelaCatalogo(produtosExibidos);
}

function renderizarTabelaCatalogo(lista) {
    var corpo = document.getElementById('corpoCatalogo');
    var contagem = document.getElementById('contagemCatalogo');
    corpo.innerHTML = '';

    if (!lista || lista.length === 0) {
        corpo.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><em>Nenhum produto localizado com os filtros selecionados.</em></td></tr>';
        contagem.textContent = 'Mostrando 0 produtos';
        return;
    }

    contagem.textContent = 'Mostrando ' + lista.length + ' produto(s)';

    lista.forEach(function(p) {
        var tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.onclick = function(e) {
            abrirModalAdicionar(p.id);
        };

        var custoFormatado = p.preco_custo > 0 ? formatarMoeda(p.preco_custo) : '<span class="text-muted">R$ 0,00</span>';

        tr.innerHTML = 
            '<td><code class="code-tag" style="font-size: 11px;">' + escapeHtml(p.codigo) + '</code></td>' +
            '<td><strong class="text-slate-900 small d-block">' + escapeHtml(p.descricao) + '</strong></td>' +
            '<td><span class="text-secondary small">' + escapeHtml(p.categoria_nome) + '</span></td>' +
            '<td class="text-end font-tabular fw-semibold text-slate-800">' + p.estoque_atual + ' un</td>' +
            '<td class="text-end font-tabular">' + custoFormatado + '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-enterprise btn-secondary btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1" style="font-size: 11.5px;" onclick="event.stopPropagation(); abrirModalAdicionar(' + p.id + ')">' +
                    '<svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/></svg>' +
                    '<span>+ Adicionar</span>' +
                '</button>' +
            '</td>';

        corpo.appendChild(tr);
    });
}

function abrirModalAdicionar(produtoId) {
    var produto = catalogoProdutosCompleto.find(function(p) { return p.id === produtoId; });
    if (!produto) return;

    document.getElementById('modalProdutoId').value = produto.id;
    document.getElementById('modalProdutoDescricao').textContent = produto.descricao;
    document.getElementById('modalProdutoCodigo').textContent = produto.codigo;
    document.getElementById('modalProdutoCategoria').textContent = produto.categoria_nome;
    document.getElementById('modalProdutoEstoqueAtual').textContent = produto.estoque_atual + ' un';

    var itemNoLote = loteItens.find(function(item) { return item.produto_id === produto.id; });
    var inputQtd = document.getElementById('modalInputQuantidade');
    var inputCusto = document.getElementById('modalInputPrecoCusto');

    if (itemNoLote) {
        inputQtd.value = itemNoLote.quantidade;
        inputCusto.value = itemNoLote.preco_custo > 0 ? itemNoLote.preco_custo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
    } else {
        inputQtd.value = '';
        inputCusto.value = produto.preco_custo > 0 ? produto.preco_custo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
    }

    atualizarSimulacaoSaldoModal();

    if (!modalInstance) {
        var modalEl = document.getElementById('modalAdicionarProduto');
        modalInstance = new bootstrap.Modal(modalEl);
    }
    modalInstance.show();

    setTimeout(function() {
        inputQtd.focus();
        inputQtd.select();
    }, 350);
}

function atualizarSimulacaoSaldoModal() {
    var prodId = parseInt(document.getElementById('modalProdutoId').value, 10) || 0;
    var produto = catalogoProdutosCompleto.find(function(p) { return p.id === prodId; });
    var estoqueAtual = produto ? produto.estoque_atual : 0;
    var qtdEntrada = parseInt(document.getElementById('modalInputQuantidade').value, 10) || 0;

    document.getElementById('simulacaoAtual').textContent = estoqueAtual;
    document.getElementById('simulacaoEntrada').textContent = qtdEntrada;
    document.getElementById('simulacaoNovo').textContent = (estoqueAtual + qtdEntrada);
}

function confirmarItemModal(e) {
    if (e && e.preventDefault) e.preventDefault();

    var prodId = parseInt(document.getElementById('modalProdutoId').value, 10);
    var produto = catalogoProdutosCompleto.find(function(p) { return p.id === prodId; });
    if (!produto) return false;

    var inputQtd = document.getElementById('modalInputQuantidade');
    var quantidade = parseInt(inputQtd.value, 10);

    if (isNaN(quantidade) || quantidade <= 0) {
        alert('Por favor, informe uma quantidade válida maior que zero.');
        inputQtd.focus();
        return false;
    }

    var inputCusto = document.getElementById('modalInputPrecoCusto');
    var precoCusto = parseMoedaBR(inputCusto.value);

    var idxExistente = loteItens.findIndex(function(item) { return item.produto_id === prodId; });

    if (idxExistente !== -1) {
        loteItens[idxExistente].quantidade = quantidade;
        loteItens[idxExistente].preco_custo = precoCusto;
    } else {
        loteItens.push({
            produto_id: produto.id,
            codigo: produto.codigo,
            descricao: produto.descricao,
            categoria_nome: produto.categoria_nome,
            estoque_atual: produto.estoque_atual,
            quantidade: quantidade,
            preco_custo: precoCusto
        });
    }

    if (modalInstance) {
        modalInstance.hide();
    }

    renderizarTabelaLote();
    return false;
}

function removerItemLote(produtoId) {
    loteItens = loteItens.filter(function(item) {
        return item.produto_id !== produtoId;
    });
    renderizarTabelaLote();
}

function limparListaLote() {
    if (loteItens.length === 0) return;
    if (confirm('Tem certeza de que deseja limpar todos os produtos adicionados a esta lista de entrada?')) {
        loteItens = [];
        renderizarTabelaLote();
    }
}

function renderizarTabelaLote() {
    var corpo = document.getElementById('corpoLoteItens');
    var badgeTotal = document.getElementById('badgeTotalItensLote');
    var resumoQtd = document.getElementById('resumoQtdProdutos');
    var resumoVolume = document.getElementById('resumoVolumeUnidades');
    var resumoValor = document.getElementById('resumoValorTotal');
    var btnLimpar = document.getElementById('btnLimparLista');
    var btnGravar = document.getElementById('btnGravarLote');
    var inputHiddenJson = document.getElementById('inputItensJson');

    corpo.innerHTML = '';

    if (loteItens.length === 0) {
        corpo.innerHTML = '<tr id="linhaVaziaLote"><td colspan="9" class="text-center py-4 text-muted"><em>Nenhum produto adicionado ao lote. Selecione os produtos no catálogo acima para montar a entrega.</em></td></tr>';
        badgeTotal.textContent = '0 itens';
        resumoQtd.textContent = '0';
        resumoVolume.textContent = '0 un';
        resumoValor.textContent = 'R$ 0,00';
        btnLimpar.disabled = true;
        btnGravar.disabled = true;
        inputHiddenJson.value = '[]';
        return;
    }

    var totalVolume = 0;
    var totalValor = 0;

    loteItens.forEach(function(item, index) {
        totalVolume += item.quantidade;
        var subtotalItem = item.quantidade * (item.preco_custo || 0);
        totalValor += subtotalItem;
        var novoEstoque = item.estoque_atual + item.quantidade;

        var tr = document.createElement('tr');
        tr.innerHTML = 
            '<td class="text-center font-tabular text-muted small">' + (index + 1) + '</td>' +
            '<td><code class="code-tag" style="font-size: 11px;">' + escapeHtml(item.codigo) + '</code></td>' +
            '<td><strong class="text-slate-900 small d-block">' + escapeHtml(item.descricao) + '</strong><span class="text-muted" style="font-size: 11px;">' + escapeHtml(item.categoria_nome) + '</span></td>' +
            '<td class="text-end font-tabular text-slate-700">' + item.estoque_atual + ' un</td>' +
            '<td class="text-end font-tabular text-success fw-bold">+' + item.quantidade + ' un</td>' +
            '<td class="text-end font-tabular text-primary fw-bold">' + novoEstoque + ' un</td>' +
            '<td class="text-end font-tabular">' + (item.preco_custo > 0 ? formatarMoeda(item.preco_custo) : '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-end font-tabular fw-semibold text-slate-900">' + (subtotalItem > 0 ? formatarMoeda(subtotalItem) : '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-center">' +
                '<button type="button" class="btn btn-enterprise btn-danger btn-sm p-1" title="Remover item da lista" onclick="removerItemLote(' + item.produto_id + ')">' +
                    '<svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>' +
                '</button>' +
            '</td>';

        corpo.appendChild(tr);
    });

    badgeTotal.textContent = loteItens.length + (loteItens.length === 1 ? ' item' : ' itens');
    resumoQtd.textContent = loteItens.length;
    resumoVolume.textContent = totalVolume + ' un';
    resumoValor.textContent = formatarMoeda(totalValor);
    btnLimpar.disabled = false;
    btnGravar.disabled = false;

    inputHiddenJson.value = JSON.stringify(loteItens.map(function(item) {
        return {
            produto_id: item.produto_id,
            quantidade: item.quantidade,
            preco_custo: item.preco_custo
        };
    }));
}

function validarGravacaoLote(e) {
    var inputForn = document.getElementById('inputFornecedor');
    var forn = inputForn ? inputForn.value.trim() : '';

    if (forn.length < 2) {
        alert('Por favor, informe o nome do fornecedor (mínimo de 2 caracteres).');
        inputForn.focus();
        return false;
    }

    if (!loteItens || loteItens.length === 0) {
        alert('Nenhum produto foi adicionado à lista. Inclua ao menos um produto no lote antes de gravar.');
        return false;
    }

    return confirm('Confirmar gravação da entrada de ' + loteItens.length + ' produto(s) no estoque físico?');
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', function() {
    // Inicialmente a tabela de catálogo permanece vazia com a mensagem orientadora

    // Se veio um produto pré-selecionado via URL (?produto_id=X)
    <?php if (!empty($produtoPreSel)): ?>
        exibirCatalogoCompleto();
        abrirModalAdicionar(<?php echo (int)$produtoPreSel; ?>);
    <?php endif; ?>
});
</script>
