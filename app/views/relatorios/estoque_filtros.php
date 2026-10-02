<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Painel de Opções de Layout e Filtros de Movimentações de Estoque
 * 
 * Conforme docs/alterações sistema guideline/filtro movimentação estoque.pdf e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$filtros = $filtros ?? [];
$categorias = $categorias ?? [];
$produtos = $produtos ?? [];

$dataInicio  = $filtros['data_inicio'] ?? date('Y-m-01');
$dataFim     = $filtros['data_fim'] ?? date('Y-m-d');
$categoriaId = (int)($filtros['categoria_id'] ?? 0);
$produtoId   = (int)($filtros['produto_id'] ?? 0);
$tipoMov     = $filtros['tipo_movimentacao'] ?? '';
$motivoPerda = $filtros['motivo_perda'] ?? '';

$formatoLayout  = $filtros['formato_layout'] ?? $filtros['formato_leiaute'] ?? 'retrato';
$formatoLeiaute = $formatoLayout; // alias de compatibilidade
$agruparGrupo   = $filtros['agrupar_grupo'] ?? 'ocultar';
$agruparProduto = $filtros['agrupar_produto'] ?? 'ocultar';
$quebraPagina   = $filtros['quebra_pagina'] ?? 'ocultar';
?>

<style>
/* Estilos corporativos específicos do painel de emissão Escolar Manager */
.nav-tabs-enterprise {
    border-bottom: 2px solid #e2e8f0;
    gap: 4px;
}
.nav-tabs-enterprise .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    background: transparent;
    border-radius: 4px 4px 0 0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease-in-out;
}
.nav-tabs-enterprise .nav-link:hover {
    color: #1d4ed8;
    background: #f8fafc;
}
.nav-tabs-enterprise .nav-link.active {
    color: #ffffff;
    background: #1d4ed8;
    border-color: #1d4ed8;
    border-radius: 6px 6px 0 0;
    box-shadow: 0 1px 3px rgba(29, 78, 216, 0.25);
}

/* Grupo de botões segmentados com destaque ativo azul corporativo */
.btn-toggle-group-escolar {
    display: inline-flex;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    overflow: hidden;
}
.btn-toggle-group-escolar .btn-check:checked + .btn-escolar-segment {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
    color: #ffffff !important;
    font-weight: 600;
}
.btn-toggle-group-escolar .btn-escolar-segment {
    background-color: #ffffff;
    border: none;
    border-right: 1px solid #e2e8f0;
    color: #475569;
    font-size: 12px;
    padding: 6px 16px;
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    border-radius: 0;
}
.btn-toggle-group-escolar .btn-escolar-segment:last-child {
    border-right: none;
}
.btn-toggle-group-escolar .btn-escolar-segment:hover:not(.disabled) {
    background-color: #f1f5f9;
    color: #0f172a;
}
.btn-toggle-group-escolar .btn-escolar-segment.disabled,
.btn-toggle-group-escolar .btn-escolar-segment:disabled {
    background-color: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
    opacity: 0.7;
}

.card-filtro-container {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.label-campo-escolar {
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 6px;
    display: block;
}
</style>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

    <!-- Formulário Oficial de Emissão -->
    <form action="<?php echo $baseUrl; ?>/relatorios/estoque-movimentacoes/imprimir" method="GET" id="formFiltrosEstoque" target="_blank">

        <!-- Cabeçalho Executivo e Barra de Ações -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Estoque & Armazenamento</span>
                    <span class="badge bg-light text-secondary border font-tabular">Padrão Escolar Manager</span>
                </div>
                <h1 class="h5 font-weight-bold text-dark mb-0">Emissão de Movimentações de Estoque</h1>
                <p class="text-secondary text-microcopy mb-0">
                    Parametrização dinâmica com orientações Retrato e Paisagem, agrupamentos dinâmicos e Centro de Custo Cantina Geral.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Dropdown Emitir Oficial -->
                <div class="dropdown d-inline-block">
                    <button class="btn btn-enterprise btn-primary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1.5" type="button" id="dropdownEmitirEstoque" data-bs-toggle="dropdown" aria-expanded="false">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                        </svg>
                        <span>Emitir</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="dropdownEmitirEstoque" style="font-size: 13px;">
                        <li>
                            <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/estoque-movimentacoes/imprimir" formtarget="_blank" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-primary"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                <div>
                                    <span class="d-block fw-semibold">Visualizar / Imprimir (A4)</span>
                                    <small class="text-muted" style="font-size: 11px;">Tela de impressão oficial do relatório</small>
                                </div>
                            </button>
                        </li>
                        <li>
                            <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/estoque-movimentacoes/pdf" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-danger"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.603 12.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.127.18-.302.476-.566.862-.774.455-.246.99-.413 1.57-.492a12.879 12.879 0 0 1 1.258-.093 18.06 18.06 0 0 1-.865-1.57c-.29-.63-.497-1.3-.59-1.954-.08-.553.003-1.04.25-1.378.18-.246.438-.37.74-.37.498 0 .844.334.975.875.112.46.04 1.05-.183 1.74a17.26 17.26 0 0 1-.77 1.83 23.97 23.97 0 0 0 2.636 1.488c.453-.274.877-.52 1.23-.74.68-.423 1.15-.71 1.43-.84.34-.15.65-.18.89-.09.28.1.44.35.44.68 0 .39-.23.77-.66 1.07-.35.25-.86.49-1.48.71a17.3 17.3 0 0 1-2.01.55c-.75.14-1.53.22-2.31.25-.56.44-1.12.86-1.67 1.25-.66.47-1.28.85-1.83 1.11-.34.16-.65.25-.92.25-.43 0-.75-.2-.87-.55z"/></svg>
                                <div>
                                    <span class="d-block fw-semibold">Baixar em PDF (A4)</span>
                                    <small class="text-muted" style="font-size: 11px;">Documento timbrado para arquivamento</small>
                                </div>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/estoque-movimentacoes/csv" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-success"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/></svg>
                                <div>
                                    <span class="d-block fw-semibold">Exportar Planilha (CSV)</span>
                                    <small class="text-muted" style="font-size: 11px;">Compatível com Microsoft Excel</small>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Botão Voltar ao Hub -->
                <a href="<?php echo $baseUrl; ?>/relatorios" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <span>Hub de Relatórios</span>
                </a>
            </div>
        </div>

        <!-- Abas de Navegação Estilo Escolar Manager: Filtro e Layout -->
        <ul class="nav nav-tabs nav-tabs-enterprise mb-3" id="tabsEstoque" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-filtro-btn" data-bs-toggle="tab" data-bs-target="#tab-filtro-pane" type="button" role="tab" aria-controls="tab-filtro-pane" aria-selected="true">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/>
                    </svg>
                    <span>Filtro</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-layout-btn" data-bs-toggle="tab" data-bs-target="#tab-layout-pane" type="button" role="tab" aria-controls="tab-layout-pane" aria-selected="false">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                    </svg>
                    <span>Layout</span>
                </button>
            </li>
        </ul>

        <!-- Conteúdo das Abas -->
        <div class="tab-content" id="tabsEstoqueContent">

            <!-- ABA 1: FILTROS DE DADOS -->
            <div class="tab-pane fade show active" id="tab-filtro-pane" role="tabpanel" aria-labelledby="tab-filtro-btn" tabindex="0">
                <div class="card-filtro-container mb-3">
                    
                    <!-- Linha 1: Período com Atalhos Rápidos -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="dataInicio" class="label-campo-escolar">
                                Período: Data Inicial
                            </label>
                            <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="dataFim" class="label-campo-escolar">
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

                    <hr class="my-3 text-muted" style="opacity: 0.15;">

                    <!-- Linha 2: Categoria, Produto, Tipo de Movimentação e Motivo de Perda -->
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="categoriaId" class="label-campo-escolar">
                                Grupo de Produtos / Categoria
                            </label>
                            <select name="categoria_id" id="categoriaId" class="form-select form-select-sm" onchange="filtrarProdutosPorCategoria()">
                                <option value="0">-- Todas as Categorias --</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoriaId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="produtoId" class="label-campo-escolar">
                                Produto Específico
                            </label>
                            <select name="produto_id" id="produtoId" class="form-select form-select-sm">
                                <option value="0" data-categoria="0">-- Todos os Produtos --</option>
                                <?php foreach ($produtos as $prod): ?>
                                    <option value="<?php echo (int)$prod['id']; ?>" data-categoria="<?php echo (int)$prod['categoria_id']; ?>" <?php echo ($produtoId === (int)$prod['id']) ? 'selected' : ''; ?>>
                                        [<?php echo htmlspecialchars($prod['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="tipoMovimentacao" class="label-campo-escolar">
                                Tipo de Movimentação
                            </label>
                            <select name="tipo_movimentacao" id="tipoMovimentacao" class="form-select form-select-sm">
                                <option value="">-- Todos os Tipos --</option>
                                <option value="entrada_fornecedor" <?php echo ($tipoMov === 'entrada_fornecedor') ? 'selected' : ''; ?>>Entrada por Fornecedor (Corte)</option>
                                <option value="entrada_xml" <?php echo ($tipoMov === 'entrada_xml') ? 'selected' : ''; ?>>Entrada por NF-e (XML)</option>
                                <option value="aproveitamento_sobra" <?php echo ($tipoMov === 'aproveitamento_sobra') ? 'selected' : ''; ?>>Aproveitamento de Sobra</option>
                                <option value="entrada_estorno" <?php echo ($tipoMov === 'entrada_estorno') ? 'selected' : ''; ?>>Reintegração por Estorno</option>
                                <option value="saida_venda_avulsa" <?php echo ($tipoMov === 'saida_venda_avulsa') ? 'selected' : ''; ?>>Saída por Venda Balcão (PDV)</option>
                                <option value="saida_encomenda" <?php echo ($tipoMov === 'saida_encomenda') ? 'selected' : ''; ?>>Saída por Encomenda Cantina Web</option>
                                <option value="saida_perda" <?php echo ($tipoMov === 'saida_perda') ? 'selected' : ''; ?>>Baixa por Perda / Descarte</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-3">
                            <label for="motivoPerda" class="label-campo-escolar">
                                Motivo de Perda (Se Houver)
                            </label>
                            <select name="motivo_perda" id="motivoPerda" class="form-select form-select-sm">
                                <option value="">-- Todos os Motivos --</option>
                                <option value="avaria" <?php echo ($motivoPerda === 'avaria') ? 'selected' : ''; ?>>Avaria / Descarte</option>
                                <option value="vencimento" <?php echo ($motivoPerda === 'vencimento') ? 'selected' : ''; ?>>Vencimento</option>
                                <option value="sobra_producao" <?php echo ($motivoPerda === 'sobra_producao') ? 'selected' : ''; ?>>Sobra de Produção / Não Retirado</option>
                                <option value="ajuste_contagem" <?php echo ($motivoPerda === 'ajuste_contagem') ? 'selected' : ''; ?>>Ajuste de Contagem</option>
                            </select>
                        </div>
                    </div>

                    <!-- Centro de Custo Informativo -->
                    <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary text-microcopy text-uppercase font-weight-bold">Centro de Custo Ativo:</span>
                            <span class="badge bg-light text-dark border px-2 py-1 font-tabular" style="font-size: 12px;">
                                🏢 Cantina Geral (Unidade Escolar Monteiro Lobato)
                            </span>
                        </div>
                        <small class="text-muted" style="font-size: 11.5px;">
                            * Opera sob centro de custo único institucional para todas as operações do turno.
                        </small>
                    </div>

                </div>
            </div>

            <!-- ABA 2: LAYOUT E FORMATAÇÃO (ESTILO ESCOLAR MANAGER) -->
            <div class="tab-pane fade" id="tab-layout-pane" role="tabpanel" aria-labelledby="tab-layout-btn" tabindex="0">
                <div class="card-filtro-container mb-3">
                    
                    <div class="row g-4 mb-4">
                        
                        <!-- 1. Formato do Layout: Paisagem / Retrato -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="label-campo-escolar">Formato do layout</label>
                            <div class="btn-toggle-group-escolar w-100">
                                <input type="radio" class="btn-check" name="formato_layout" id="layoutPaisagem" value="paisagem" <?php echo ($formatoLayout === 'paisagem') ? 'checked' : ''; ?> onchange="sincronizarFormatoLayout(this.value)">
                                <label class="btn btn-escolar-segment flex-fill text-center" for="layoutPaisagem">Paisagem</label>

                                <input type="radio" class="btn-check" name="formato_layout" id="layoutRetrato" value="retrato" <?php echo ($formatoLayout === 'retrato') ? 'checked' : ''; ?> onchange="sincronizarFormatoLayout(this.value)">
                                <label class="btn btn-escolar-segment flex-fill text-center" for="layoutRetrato">Retrato</label>

                                <!-- Compatibilidade com formato_leiaute -->
                                <input type="hidden" name="formato_leiaute" id="formatoLeiauteHidden" value="<?php echo htmlspecialchars($formatoLayout, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Orientação da folha física A4
                            </small>
                        </div>

                        <!-- 2. Agrupamento por centro de custo: Mostrar / Ocultar (desabilitado em Ocultar) -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="label-campo-escolar">Agrupamento por centro de custo</label>
                            <div class="btn-toggle-group-escolar w-100 opacity-75">
                                <button type="button" class="btn btn-escolar-segment flex-fill text-center disabled" disabled>Mostrar</button>
                                <button type="button" class="btn btn-escolar-segment flex-fill text-center disabled fw-bold text-secondary" style="background:#e2e8f0;" disabled>Ocultar</button>
                                <input type="hidden" name="agrupar_centro_custo" value="ocultar">
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Fixo (Centro único Cantina Geral)
                            </small>
                        </div>

                        <!-- 3. Agrupamento por grupo de produtos: Mostrar / Ocultar -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="label-campo-escolar">Agrupamento por grupo de produtos</label>
                            <div class="btn-toggle-group-escolar w-100">
                                <input type="radio" class="btn-check" name="agrupar_grupo" id="agruparGrupoMostrar" value="mostrar" <?php echo ($agruparGrupo === 'mostrar') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment flex-fill text-center" for="agruparGrupoMostrar">Mostrar</label>

                                <input type="radio" class="btn-check" name="agrupar_grupo" id="agruparGrupoOcultar" value="ocultar" <?php echo ($agruparGrupo === 'ocultar') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment flex-fill text-center" for="agruparGrupoOcultar">Ocultar</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Agrupa por categoria com subtotais
                            </small>
                        </div>

                        <!-- 4. Agrupamento por produto: Mostrar / Ocultar -->
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="label-campo-escolar">Agrupamento por produto</label>
                            <div class="btn-toggle-group-escolar w-100">
                                <input type="radio" class="btn-check" name="agrupar_produto" id="agruparProdutoMostrar" value="mostrar" <?php echo ($agruparProduto === 'mostrar') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment flex-fill text-center" for="agruparProdutoMostrar">Mostrar</label>

                                <input type="radio" class="btn-check" name="agrupar_produto" id="agruparProdutoOcultar" value="ocultar" <?php echo ($agruparProduto === 'ocultar') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment flex-fill text-center" for="agruparProdutoOcultar">Ocultar</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Agrupa e totaliza item a item
                            </small>
                        </div>
                    </div>

                    <!-- Linha 2 de Layout: Quebra de Página -->
                    <div class="row g-4 pt-3 border-top">
                        <div class="col-12 col-sm-6 col-md-3">
                            <label class="label-campo-escolar">Quebra de página</label>
                            <div class="btn-toggle-group-escolar w-100">
                                <input type="radio" class="btn-check" name="quebra_pagina" id="quebraPaginaMostrar" value="mostrar" <?php echo ($quebraPagina === 'mostrar') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment flex-fill text-center" for="quebraPaginaMostrar">Mostrar</label>

                                <input type="radio" class="btn-check" name="quebra_pagina" id="quebraPaginaOcultar" value="ocultar" <?php echo ($quebraPagina === 'ocultar') ? 'checked' : ''; ?>>
                                <label class="btn btn-escolar-segment flex-fill text-center" for="quebraPaginaOcultar">Ocultar</label>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                Insere nova página a cada grupo
                            </small>
                        </div>

                        <div class="col-12 col-md-9 d-flex align-items-center">
                            <div class="p-3 bg-light rounded border w-100">
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" class="text-primary"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
                                    <span class="text-dark fw-semibold" style="font-size: 12.5px;">Dica de Otimização de Impressão:</span>
                                </div>
                                <p class="text-secondary mb-0 mt-1" style="font-size: 12px; line-height: 1.4;">
                                    A orientação <strong>Paisagem</strong> é recomendada para demonstrativos detalhados com todas as colunas de documentos e justificativas de perdas. A orientação <strong>Retrato</strong> é compacta e ideal para arquivamento contábil e conferências rápidas.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Botões de Ação de Rodapé -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
            <a href="<?php echo $baseUrl; ?>/relatorios" class="btn btn-enterprise btn-secondary btn-sm">
                ← Voltar aos Relatórios
            </a>

            <div class="d-flex gap-2">
                <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/estoque-movimentacoes/imprimir" formtarget="_blank" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    <span>Emitir Relatório</span>
                </button>
            </div>
        </div>

    </form>
</div>

<script>
// Script interativo de atalhos rápidos de data
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
        const diaSemana = hoje.getDay(); // 0 domingo, 1 segunda
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
    const catId = document.getElementById('categoriaId').value;
    const selectProd = document.getElementById('produtoId');
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

// Sincroniza o valor de formato de layout com o input de compatibilidade
function sincronizarFormatoLayout(val) {
    const hidden = document.getElementById('formatoLeiauteHidden');
    if (hidden) {
        hidden.value = val;
    }
}
</script>
