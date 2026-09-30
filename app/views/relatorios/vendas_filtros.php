<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Painel de Filtros Avançados de Vendas da Cantina
 * 
 * Conforme docs/alterações sistema guideline/Filtros de vendas.pdf, 
 * relatorio de vendas por período.pdf e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$filtros = $filtros ?? [];
$dadosRelatorio = $dadosRelatorio ?? [];
$categorias = $categorias ?? [];

$dataInicio = $filtros['data_inicio'] ?? date('Y-m-d');
$dataFim    = $filtros['data_fim'] ?? date('Y-m-d');
$turno      = $filtros['turno'] ?? '';
$categoriaId = (int)($filtros['categoria_id'] ?? 0);

$emitirObs    = ($filtros['emitir_observacao'] ?? 'nao') === 'sim';
$emitirForma  = ($filtros['emitir_forma_pagamento'] ?? 'nao') === 'sim';
$incluirEst   = ($filtros['incluir_estornos'] ?? 'nao') === 'sim';
$agruparTipo  = ($filtros['agrupar_tipo_pessoa'] ?? 'nao') === 'sim';
$agruparPer   = ($filtros['agrupar_periodo'] ?? 'sim') === 'sim';
$emitirHora   = ($filtros['emitir_horario'] ?? 'nao') === 'sim';

$emitirAlim   = ($filtros['emitir_vendas_alimento'] ?? 'sim') !== 'nao';
$agruparAlim  = ($filtros['agrupar_alimento'] ?? 'sim') !== 'nao';
$ordenarAlim  = $filtros['ordenar_alimentos'] ?? 'mais_vendidos';

$emitirAluno  = ($filtros['emitir_vendas_aluno'] ?? 'nao') === 'sim';
$agruparAluno = ($filtros['agrupar_vendas_aluno'] ?? 'nao') === 'sim';
$agruparTurma = ($filtros['agrupar_serie_turma'] ?? 'nao') === 'sim';
$quebrarTurma = ($filtros['quebrar_pagina_turma'] ?? 'nao') === 'sim';
$ordConsumo   = ($filtros['ordenar_consumo_aluno'] ?? 'nao') === 'sim';
$ordPor       = $filtros['ordenar_por'] ?? 'maior_consumo';

$totais = $dadosRelatorio['totais'] ?? [];
$dias   = $dadosRelatorio['dias'] ?? [];
$itens  = $dadosRelatorio['itens'] ?? [];
$alunos = $dadosRelatorio['alunos'] ?? [];

// Montagem da query string de parâmetros ativos para os botões de emissão
$queryParams = http_build_query($filtros);
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

    <!-- Cabeçalho Executivo e Barra de Ações -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Relatórios Oficiais</span>
                <span class="badge bg-light text-secondary border font-tabular">Padrão Escolar Manager</span>
            </div>
            <h1 class="h5 font-weight-bold text-dark mb-1">Filtros de Vendas & Relatório por Alimento</h1>
            <p class="text-secondary text-microcopy mb-0">
                Parametrização dinâmica de vendas, agrupamentos por dia e produto, e emissão oficial de relatórios da cantina.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Dropdown Emitir -->
            <div class="dropdown">
                <button class="btn btn-enterprise btn-primary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1.5" type="button" id="dropdownEmitir" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    <span>Emitir</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="dropdownEmitir" style="font-size: 13px;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir?<?php echo $queryParams; ?>" target="_blank">
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-primary"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                            <div>
                                <span class="d-block fw-semibold">Visualizar / Imprimir (A4)</span>
                                <small class="text-muted" style="font-size: 11px;">Espelho oficial formatado com impressão</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/pdf?<?php echo $queryParams; ?>">
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-danger"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.603 12.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.127.18-.302.476-.566.862-.774.455-.246.99-.413 1.57-.492a12.879 12.879 0 0 1 1.258-.093 18.06 18.06 0 0 1-.865-1.57c-.29-.63-.497-1.3-.59-1.954-.08-.553.003-1.04.25-1.378.18-.246.438-.37.74-.37.498 0 .844.334.975.875.112.46.04 1.05-.183 1.74a17.26 17.26 0 0 1-.77 1.83 23.97 23.97 0 0 0 2.636 1.488c.453-.274.877-.52 1.23-.74.68-.423 1.15-.71 1.43-.84.34-.15.65-.18.89-.09.28.1.44.35.44.68 0 .39-.23.77-.66 1.07-.35.25-.86.49-1.48.71a17.3 17.3 0 0 1-2.01.55c-.75.14-1.53.22-2.31.25-.56.44-1.12.86-1.67 1.25-.66.47-1.28.85-1.83 1.11-.34.16-.65.25-.92.25-.43 0-.75-.2-.87-.55z"/></svg>
                            <div>
                                <span class="d-block fw-semibold">Baixar em PDF (A4)</span>
                                <small class="text-muted" style="font-size: 11px;">Documento timbrado para arquivamento</small>
                            </div>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/csv?<?php echo $queryParams; ?>">
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-success"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/></svg>
                            <div>
                                <span class="d-block fw-semibold">Exportar Planilha (CSV)</span>
                                <small class="text-muted" style="font-size: 11px;">Compatível com Microsoft Excel</small>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Botão Voltar ao Hub -->
            <a href="<?php echo $baseUrl; ?>/relatorios" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                <span>Hub de Relatórios</span>
            </a>
        </div>
    </div>

    <!-- Abas de Navegação Estilo Escolar Manager -->
    <div class="d-flex align-items-center gap-2 mb-3">
        <button type="button" class="btn btn-enterprise btn-primary btn-sm px-3 fw-semibold active" id="tabBtnFiltro" onclick="alternarAbaFiltro('filtro')">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
            Filtro & Parâmetros
        </button>
        <button type="button" class="btn btn-enterprise btn-secondary btn-sm px-3 fw-semibold" id="tabBtnLeiaute" onclick="alternarAbaFiltro('leiaute')">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5h-3z"/></svg>
            Opções de Agrupamento
        </button>
    </div>

    <!-- Formulário Oficial de Filtros -->
    <form action="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" method="GET" id="formFiltrosVendas">
        <input type="hidden" name="aba" id="inputAbaAtiva" value="<?php echo htmlspecialchars($filtros['aba_ativa'] ?? 'filtro', ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Bloco 1: Filtro de Período e Parâmetros Primários -->
        <div class="card-enterprise mb-3" id="secaoFiltrosPrincipais">
            <div class="card-enterprise-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                            Período: Data Inicial
                        </label>
                        <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                            Período: Data Final
                        </label>
                        <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="filtroTurno" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                            Turno Escolar
                        </label>
                        <select name="turno" id="filtroTurno" class="form-select form-select-sm font-tabular">
                            <option value="">Todos os Turnos</option>
                            <option value="matutino" <?php echo ($turno === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                            <option value="vespertino" <?php echo ($turno === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="filtroCategoria" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                            Categoria do Cardápio
                        </label>
                        <select name="categoria_id" id="filtroCategoria" class="form-select form-select-sm font-tabular">
                            <option value="0">Todas as Categorias</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($categoriaId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Atalhos rápidos de período -->
                <div class="d-flex align-items-center gap-1.5 mt-2.5 pt-2 border-top flex-wrap">
                    <span class="text-microcopy text-muted me-1">Atalhos rápidos:</span>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-d'); ?>', '<?php echo date('Y-m-d'); ?>')">Hoje</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-d', strtotime('-1 day')); ?>', '<?php echo date('Y-m-d', strtotime('-1 day')); ?>')">Ontem</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-01'); ?>', '<?php echo date('Y-m-d'); ?>')">Este Mês</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;" onclick="definirDatas('<?php echo date('Y-m-01', strtotime('first day of last month')); ?>', '<?php echo date('Y-m-t', strtotime('last month')); ?>')">Mês Anterior</button>
                </div>
            </div>
        </div>

        <!-- Bloco 2: Painel de Toggles Idêntico ao Guideline Escolar Manager -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                <span class="fw-semibold text-dark text-microcopy text-uppercase">Parâmetros de Emissão & Formatação</span>
                <span class="text-muted text-microcopy">Selecione as opções desejadas para compor o espelho de vendas</span>
            </div>
            <div class="card-enterprise-body p-3">

                <!-- Linha 1 de Toggles -->
                <div class="row g-3 mb-3">
                    <!-- Emitir observação -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir observação</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="emitir_observacao" id="obs_sim" value="sim" <?php echo $emitirObs ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="obs_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_observacao" id="obs_nao" value="nao" <?php echo !$emitirObs ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="obs_nao">Não</label>
                        </div>
                    </div>

                    <!-- Emitir forma de pagamento -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir forma de pagamento</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="emitir_forma_pagamento" id="forma_sim" value="sim" <?php echo $emitirForma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="forma_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_forma_pagamento" id="forma_nao" value="nao" <?php echo !$emitirForma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="forma_nao">Não</label>
                        </div>
                    </div>

                    <!-- Incluir estorno de vendas -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Incluir estorno de vendas</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="incluir_estornos" id="estorno_sim" value="sim" <?php echo $incluirEst ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="estorno_sim">Sim</label>
                            <input type="radio" class="btn-check" name="incluir_estornos" id="estorno_nao" value="nao" <?php echo !$incluirEst ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="estorno_nao">Não</label>
                        </div>
                    </div>

                    <!-- Agrupar por tipo pessoa -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar por tipo pessoa</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="agrupar_tipo_pessoa" id="tipo_pessoa_sim" value="sim" <?php echo $agruparTipo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="tipo_pessoa_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_tipo_pessoa" id="tipo_pessoa_nao" value="nao" <?php echo !$agruparTipo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="tipo_pessoa_nao">Não</label>
                        </div>
                    </div>

                    <!-- Agrupar consumo por período -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar consumo por período</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="agrupar_periodo" id="periodo_sim" value="sim" <?php echo $agruparPer ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="periodo_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_periodo" id="periodo_nao" value="nao" <?php echo !$agruparPer ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="periodo_nao">Não</label>
                        </div>
                    </div>

                    <!-- Emitir horário da venda -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir horário da venda</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="emitir_horario" id="horario_sim" value="sim" <?php echo $emitirHora ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="horario_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_horario" id="horario_nao" value="nao" <?php echo !$emitirHora ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="horario_nao">Não</label>
                        </div>
                    </div>
                </div>

                <!-- Linha 2 de Toggles (Alimentos) -->
                <div class="row g-3 mb-3 pt-2 border-top">
                    <!-- Emitir vendas por alimento -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir vendas por alimento</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="emitir_vendas_alimento" id="alim_sim" value="sim" <?php echo $emitirAlim ? 'checked' : ''; ?> onchange="atualizarEstadoAlimento()">
                            <label class="btn btn-outline-secondary" for="alim_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_vendas_alimento" id="alim_nao" value="nao" <?php echo !$emitirAlim ? 'checked' : ''; ?> onchange="atualizarEstadoAlimento()">
                            <label class="btn btn-outline-secondary" for="alim_nao">Não</label>
                        </div>
                    </div>

                    <!-- Agrupar por alimento -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar por alimento</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="agrupar_alimento" id="agrup_alim_sim" value="sim" <?php echo $agruparAlim ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="agrup_alim_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_alimento" id="agrup_alim_nao" value="nao" <?php echo !$agruparAlim ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="agrup_alim_nao">Não</label>
                        </div>
                    </div>

                    <!-- Ordenar por alimentos -->
                    <div class="col-12 col-sm-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Ordenar por alimentos</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="ordenar_alimentos" id="ord_alim_menos" value="menos_vendidos" <?php echo ($ordenarAlim === 'menos_vendidos') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="ord_alim_menos">Menos vendidos</label>
                            <input type="radio" class="btn-check" name="ordenar_alimentos" id="ord_alim_mais" value="mais_vendidos" <?php echo ($ordenarAlim === 'mais_vendidos') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="ord_alim_mais">Mais vendidos</label>
                        </div>
                    </div>
                </div>

                <!-- Linha 3 de Toggles (Alunos & Turmas) -->
                <div class="row g-3 mb-3 pt-2 border-top">
                    <!-- Emitir vendas por aluno -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir vendas por aluno</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="emitir_vendas_aluno" id="aluno_sim" value="sim" <?php echo $emitirAluno ? 'checked' : ''; ?> onchange="atualizarEstadoAluno()">
                            <label class="btn btn-outline-secondary" for="aluno_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_vendas_aluno" id="aluno_nao" value="nao" <?php echo !$emitirAluno ? 'checked' : ''; ?> onchange="atualizarEstadoAluno()">
                            <label class="btn btn-outline-secondary" for="aluno_nao">Não</label>
                        </div>
                    </div>

                    <!-- Agrupar vendas por aluno -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar vendas por aluno</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="agrupar_vendas_aluno" id="agrup_aluno_sim" value="sim" <?php echo $agruparAluno ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="agrup_aluno_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_vendas_aluno" id="agrup_aluno_nao" value="nao" <?php echo !$agruparAluno ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="agrup_aluno_nao">Não</label>
                        </div>
                    </div>

                    <!-- Agrupar por série/turma -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar por série/turma</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="agrupar_serie_turma" id="turma_sim" value="sim" <?php echo $agruparTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="turma_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_serie_turma" id="turma_nao" value="nao" <?php echo !$agruparTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="turma_nao">Não</label>
                        </div>
                    </div>

                    <!-- Quebrar página por série/turma -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Quebrar página por turma</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="quebrar_pagina_turma" id="quebra_sim" value="sim" <?php echo $quebrarTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="quebra_sim">Sim</label>
                            <input type="radio" class="btn-check" name="quebrar_pagina_turma" id="quebra_nao" value="nao" <?php echo !$quebrarTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="quebra_nao">Não</label>
                        </div>
                    </div>
                </div>

                <!-- Linha 4 de Toggles (Ordenação por Aluno) -->
                <div class="row g-3 pt-2 border-top">
                    <!-- Ordenar por consumo do aluno -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Ordenar por consumo do aluno</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="ordenar_consumo_aluno" id="ord_consumo_sim" value="sim" <?php echo $ordConsumo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="ord_consumo_sim">Sim</label>
                            <input type="radio" class="btn-check" name="ordenar_consumo_aluno" id="ord_consumo_nao" value="nao" <?php echo !$ordConsumo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="ord_consumo_nao">Não</label>
                        </div>
                    </div>

                    <!-- Ordenar por (Menor / Maior consumo) -->
                    <div class="col-12 col-sm-6 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Critério de Ordenação</label>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <input type="radio" class="btn-check" name="ordenar_por" id="ord_menor" value="menor_consumo" <?php echo ($ordPor === 'menor_consumo') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="ord_menor">Menor consumo</label>
                            <input type="radio" class="btn-check" name="ordenar_por" id="ord_maior" value="maior_consumo" <?php echo ($ordPor === 'maior_consumo') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-secondary" for="ord_maior">Maior consumo</label>
                        </div>
                    </div>

                    <div class="col-12 col-md-4 d-flex align-items-end justify-content-end gap-2">
                        <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" class="btn btn-enterprise btn-secondary btn-sm">
                            Redefinir Filtros
                        </a>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm px-3">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/><path fill-rule="evenodd" d="M8 3c-1.552 0-2.94.707-3.857 1.818a.5.5 0 1 1-.771-.636A6.002 6.002 0 0 1 13.917 7H12.9A5.002 5.002 0 0 0 8 3zM3.1 9a5.002 5.002 0 0 0 8.757 2.182.5.5 0 1 1 .771.636A6.002 6.002 0 0 1 2.083 9H3.1z"/></svg>
                            Atualizar Visualização
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </form>

    <!-- Stat-Strip: Totalizadores Gerais do Período -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Total do Período</span>
            <span class="stat-value font-tabular text-primary">
                R$ <?php echo number_format((float)($totais['faturamento_total'] ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Faturamento bruto apurado
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Itens Vendidos</span>
            <span class="stat-value font-tabular text-dark">
                <?php echo number_format((int)($totais['quantidade_total'] ?? 0), 0, ',', '.'); ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">peças</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Volume físico de consumo
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Preço Médio Ponderado</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format((float)($totais['ticket_medio'] ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Ticket médio por item
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Dias com Vendas</span>
            <span class="stat-value font-tabular text-success">
                <?php echo count($dias); ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">dia(s)</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Período: <?php echo htmlspecialchars($dadosRelatorio['periodo_oficial'] ?? '-'); ?>
            </span>
        </div>
    </div>

    <!-- Tabela Oficial de Emissão de Vendas (Layout Fiel a relatorio de vendas por período.pdf) -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-white border-bottom">
            <div>
                <span class="code-tag font-tabular me-2">PERÍODO ENTRE DATAS: <?php echo htmlspecialchars($dadosRelatorio['periodo_oficial'] ?? '-'); ?></span>
                <span class="text-muted text-microcopy">&bull; <?php echo count($itens); ?> registro(s)</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-secondary border font-tabular">
                    Turno: <?php echo htmlspecialchars($dadosRelatorio['turno'] ?? 'Todos'); ?>
                </span>
                <span class="badge bg-light text-secondary border font-tabular">
                    Categoria: <?php echo htmlspecialchars($dadosRelatorio['categoria'] ?? 'Todas'); ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-enterprise table-hover mb-0" id="tabelaVendasOficial">
                <thead>
                    <tr>
                        <th style="width: 110px;">Data</th>
                        <?php if ($emitirHora): ?>
                            <th style="width: 80px;" class="text-center">Horário</th>
                        <?php endif; ?>
                        <?php if ($agruparTipo): ?>
                            <th style="width: 140px;">Tipo Pessoa</th>
                        <?php endif; ?>
                        <th>Alimento</th>
                        <th class="text-center" style="width: 100px;">Quantidade</th>
                        <th class="text-end" style="width: 120px;">Valor Unitário</th>
                        <th class="text-end" style="width: 130px;">Valor Total</th>
                        <?php if ($emitirForma): ?>
                            <th style="width: 140px;">Forma Pagto</th>
                        <?php endif; ?>
                        <?php if ($emitirObs): ?>
                            <th style="width: 180px;">Observação</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dias)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-secondary text-microcopy">
                                Não há nada para mostrar aqui no período e filtros informados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dias as $dataKey => $dia): ?>
                            <!-- Linhas de Alimentos do Dia -->
                            <?php foreach ($dia['alimentos'] as $alimento): ?>
                                <tr>
                                    <td class="font-tabular text-dark fw-medium">
                                        <?php echo htmlspecialchars($alimento['data_fmt'] ?? $dia['data_fmt']); ?>
                                    </td>
                                    <?php if ($emitirHora): ?>
                                        <td class="text-center font-tabular text-muted">
                                            <?php echo htmlspecialchars($alimento['hora_venda'] ?? '-'); ?>
                                        </td>
                                    <?php endif; ?>
                                    <?php if ($agruparTipo): ?>
                                        <td>
                                            <span class="badge bg-light text-secondary border font-tabular">
                                                <?php echo htmlspecialchars($alimento['tipo_pessoa'] ?? 'Aluno'); ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <strong class="text-dark"><?php echo htmlspecialchars($alimento['descricao'] ?? ($alimento['produto_nome'] ?? '-')); ?></strong>
                                        <?php if (!empty($alimento['codigo_interno'])): ?>
                                            <small class="text-muted ms-1 font-tabular">(<?php echo htmlspecialchars($alimento['codigo_interno']); ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-tabular fw-bold text-dark">
                                        <?php echo (int)($alimento['quantidade'] ?? 0); ?>
                                    </td>
                                    <td class="text-end font-tabular text-secondary">
                                        <?php echo number_format((float)($alimento['preco_unitario'] ?? 0.0), 2, ',', ''); ?>
                                    </td>
                                    <td class="text-end font-tabular fw-bold text-dark">
                                        <?php echo number_format((float)($alimento['subtotal'] ?? ($alimento['valor_total'] ?? 0.0)), 2, ',', ''); ?>
                                    </td>
                                    <?php if ($emitirForma): ?>
                                        <td class="text-microcopy text-secondary">
                                            <?php echo htmlspecialchars($alimento['forma_pagamento'] ?? '-'); ?>
                                        </td>
                                    <?php endif; ?>
                                    <?php if ($emitirObs): ?>
                                        <td class="text-microcopy text-muted">
                                            <?php echo htmlspecialchars($alimento['observacao'] ?? '-'); ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Subtotal do Dia Conforme o PDF Oficial -->
                            <?php if ($agruparPer): ?>
                                <tr style="background: #f8fafc; border-top: 1px solid #cbd5e1; border-bottom: 2px solid #e2e8f0;">
                                    <td colspan="<?php echo 2 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0); ?>" class="fw-bold text-dark">
                                        Total do dia (<?php echo htmlspecialchars($dia['data_fmt']); ?>)
                                    </td>
                                    <td class="text-center font-tabular fw-bold text-dark">
                                        <?php echo (int)$dia['qtd_dia']; ?>
                                    </td>
                                    <td class="text-end text-muted font-tabular">&mdash;</td>
                                    <td class="text-end font-tabular fw-bold text-dark" style="font-size: 13.5px;">
                                        <?php echo number_format((float)$dia['total_dia'], 2, ',', ''); ?>
                                    </td>
                                    <?php if ($emitirForma): ?><td></td><?php endif; ?>
                                    <?php if ($emitirObs): ?><td></td><?php endif; ?>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>

                        <!-- Total do Período Conforme o PDF Oficial -->
                        <tr style="background: #eff6ff; border-top: 2px solid #1d4ed8; border-bottom: 2px solid #1d4ed8;">
                            <td colspan="<?php echo 2 + ($emitirHora ? 1 : 0) + ($agruparTipo ? 1 : 0); ?>" class="fw-bold text-primary" style="font-size: 13px;">
                                Total do período
                            </td>
                            <td class="text-center font-tabular fw-bold text-primary" style="font-size: 13px;">
                                <?php echo (int)($totais['quantidade_total'] ?? 0); ?>
                            </td>
                            <td class="text-end text-muted font-tabular">&mdash;</td>
                            <td class="text-end font-tabular fw-bold text-primary" style="font-size: 14px;">
                                <?php echo number_format((float)($totais['faturamento_total'] ?? 0.0), 2, ',', ''); ?>
                            </td>
                            <?php if ($emitirForma): ?><td></td><?php endif; ?>
                            <?php if ($emitirObs): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabela Analítica Complementar de Alunos (Quando Ativada) -->
    <?php if ($emitirAluno && !empty($alunos)): ?>
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-white border-bottom">
                <span class="fw-bold text-dark text-microcopy text-uppercase">Demonstrativo por Aluno (<?php echo count($alunos); ?> dependentes)</span>
                <span class="code-tag font-tabular">Ordenado por: <?php echo ($ordPor === 'maior_consumo') ? 'Maior Consumo' : 'Menor Consumo'; ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-enterprise table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Matrícula</th>
                            <th>Nome do Aluno</th>
                            <th style="width: 160px;">Série / Turma</th>
                            <th class="text-center" style="width: 120px;">Qtd Lanches</th>
                            <th class="text-end" style="width: 140px;">Total Consumido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $al): ?>
                            <tr>
                                <td class="font-tabular text-muted"><?php echo htmlspecialchars($al['matricula']); ?></td>
                                <td><strong class="text-dark"><?php echo htmlspecialchars($al['nome']); ?></strong></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($al['serie'] . ' ' . $al['turma']); ?></td>
                                <td class="text-center font-tabular fw-semibold"><?php echo (int)$al['quantidade']; ?> un</td>
                                <td class="text-end font-tabular fw-bold text-dark">R$ <?php echo number_format((float)$al['total_consumo'], 2, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Rodapé Institucional da Tela de Filtros -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-microcopy text-muted pt-3 border-top">
        <span>Cantina Web &bull; Colégio Monteiro Lobato &bull; Módulo de Relatórios de Vendas</span>
        <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
            ▲ Topo da página
        </button>
    </div>

</div>

<script>
function definirDatas(inicio, fim) {
    document.getElementById('dataInicio').value = inicio;
    document.getElementById('dataFim').value = fim;
}

function alternarAbaFiltro(aba) {
    document.getElementById('inputAbaAtiva').value = aba;
    const btnFiltro = document.getElementById('tabBtnFiltro');
    const btnLeiaute = document.getElementById('tabBtnLeiaute');

    if (aba === 'leiaute') {
        btnFiltro.classList.remove('btn-primary', 'active');
        btnFiltro.classList.add('btn-secondary');
        btnLeiaute.classList.remove('btn-secondary');
        btnLeiaute.classList.add('btn-primary', 'active');
        // Rola suavemente até as opções de formatação
        const secaoFormatacao = document.querySelector('.card-enterprise-header');
        if (secaoFormatacao) {
            secaoFormatacao.scrollIntoView({ behavior: 'smooth' });
        }
    } else {
        btnLeiaute.classList.remove('btn-primary', 'active');
        btnLeiaute.classList.add('btn-secondary');
        btnFiltro.classList.remove('btn-secondary');
        btnFiltro.classList.add('btn-primary', 'active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function atualizarEstadoAlimento() {
    const alimSim = document.getElementById('alim_sim').checked;
    const agrupSim = document.getElementById('agrup_alim_sim');
    const agrupNao = document.getElementById('agrup_alim_nao');
    const ordMenos = document.getElementById('ord_alim_menos');
    const ordMais = document.getElementById('ord_alim_mais');

    agrupSim.disabled = !alimSim;
    agrupNao.disabled = !alimSim;
    ordMenos.disabled = !alimSim;
    ordMais.disabled = !alimSim;
}

function atualizarEstadoAluno() {
    const alunoSim = document.getElementById('aluno_sim').checked;
    const inputsAluno = [
        'agrup_aluno_sim', 'agrup_aluno_nao',
        'turma_sim', 'turma_nao',
        'quebra_sim', 'quebra_nao',
        'ord_consumo_sim', 'ord_consumo_nao',
        'ord_menor', 'ord_maior'
    ];

    inputsAluno.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !alunoSim;
    });
}

document.addEventListener('DOMContentLoaded', function() {
    atualizarEstadoAlimento();
    atualizarEstadoAluno();
});
</script>
