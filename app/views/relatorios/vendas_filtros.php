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
?>

<style>
/* Estilização dos botões e toggles com destaque em Azul Corporativo */
.btn-toggle-blue .btn-check:checked + .btn-outline-custom {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
    color: #ffffff !important;
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(29, 78, 216, 0.25);
}
.btn-toggle-blue .btn-outline-custom {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: 12px;
    padding: 5px 12px;
    transition: all 0.15s ease-in-out;
}
.btn-toggle-blue .btn-outline-custom:hover {
    background-color: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}
.btn-toggle-blue .btn-check:focus + .btn-outline-custom {
    box-shadow: 0 0 0 0.2rem rgba(29, 78, 216, 0.25);
}
.btn-action-emitir {
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 14px;
}
</style>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

    <!-- Formulário Oficial de Filtros -->
    <form action="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir" method="GET" target="_blank" id="formFiltrosVendas">

        <!-- Cabeçalho Executivo e Barra de Ações -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Relatórios Gerenciais</span>
                    <span class="badge bg-light text-secondary border font-tabular">Padrão Escolar Manager</span>
                </div>
                <h1 class="h5 font-weight-bold text-dark mb-0">Filtros de Vendas & Relatório por Alimento</h1>
                <p class="text-secondary text-microcopy mb-0">
                    Defina os parâmetros de emissão para visualização em tela de impressão A4 (com opção de salvar PDF) ou exportação CSV.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Dropdown Emitir para compatibilidade e navegação rápida -->
                <div class="dropdown d-none d-md-inline-block">
                    <button class="btn btn-enterprise btn-primary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1.5" type="button" id="dropdownEmitir" data-bs-toggle="dropdown" aria-expanded="false">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                        </svg>
                        <span>Emitir</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border" aria-labelledby="dropdownEmitir" style="font-size: 13px;">
                        <li>
                            <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir" formtarget="_blank" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor" class="text-primary"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                                <div>
                                    <span class="d-block fw-semibold">Imprimir / Salvar PDF (A4)</span>
                                    <small class="text-muted" style="font-size: 11px;">Tela oficial de impressão e geração de PDF</small>
                                </div>
                            </button>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/csv" class="dropdown-item d-flex align-items-center gap-2 py-2">
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

        <!-- Bloco de Filtro de Período e Parâmetros Primários -->
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

        <!-- Painel de Toggles Idêntico ao Guideline Escolar Manager (Botões em Azul Monteiro Lobato) -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                <span class="fw-semibold text-dark text-microcopy text-uppercase">Parâmetros de Emissão & Formatação</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted text-microcopy d-none d-sm-inline">Selecione as opções desejadas para emitir o relatório</span>
                    <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;">
                        Redefinir Padrões
                    </a>
                </div>
            </div>
            <div class="card-enterprise-body p-3">

                <!-- Bloco Geral: 2 Linhas de 3 Colunas (Simetria Perfeita) -->
                <div class="row g-3 mb-3">
                    <!-- 1. Emitir observação -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir observação</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="emitir_observacao" id="obs_sim" value="sim" <?php echo $emitirObs ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="obs_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_observacao" id="obs_nao" value="nao" <?php echo !$emitirObs ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="obs_nao">Não</label>
                        </div>
                    </div>

                    <!-- 2. Emitir forma de pagamento -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir forma de pagamento</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="emitir_forma_pagamento" id="forma_sim" value="sim" <?php echo $emitirForma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="forma_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_forma_pagamento" id="forma_nao" value="nao" <?php echo !$emitirForma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="forma_nao">Não</label>
                        </div>
                    </div>

                    <!-- 3. Incluir estorno de vendas -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Incluir estorno de vendas</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="incluir_estornos" id="estorno_sim" value="sim" <?php echo $incluirEst ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="estorno_sim">Sim</label>
                            <input type="radio" class="btn-check" name="incluir_estornos" id="estorno_nao" value="nao" <?php echo !$incluirEst ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="estorno_nao">Não</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <!-- 4. Agrupar por tipo pessoa -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar por tipo pessoa</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="agrupar_tipo_pessoa" id="tipo_pessoa_sim" value="sim" <?php echo $agruparTipo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="tipo_pessoa_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_tipo_pessoa" id="tipo_pessoa_nao" value="nao" <?php echo !$agruparTipo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="tipo_pessoa_nao">Não</label>
                        </div>
                    </div>

                    <!-- 5. Agrupar consumo por período -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar consumo por período</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="agrupar_periodo" id="periodo_sim" value="sim" <?php echo $agruparPer ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="periodo_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_periodo" id="periodo_nao" value="nao" <?php echo !$agruparPer ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="periodo_nao">Não</label>
                        </div>
                    </div>

                    <!-- 6. Emitir horário da venda -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir horário da venda</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="emitir_horario" id="horario_sim" value="sim" <?php echo $emitirHora ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="horario_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_horario" id="horario_nao" value="nao" <?php echo !$emitirHora ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="horario_nao">Não</label>
                        </div>
                    </div>
                </div>

                <!-- Bloco Alimentos: 1 Linha de 3 Colunas (Simetria Perfeita) -->
                <div class="row g-3 mb-3 pt-3 border-top">
                    <!-- 7. Emitir vendas por alimento -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir vendas por alimento</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="emitir_vendas_alimento" id="alim_sim" value="sim" <?php echo $emitirAlim ? 'checked' : ''; ?> onchange="atualizarEstadoAlimento()">
                            <label class="btn btn-outline-custom" for="alim_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_vendas_alimento" id="alim_nao" value="nao" <?php echo !$emitirAlim ? 'checked' : ''; ?> onchange="atualizarEstadoAlimento()">
                            <label class="btn btn-outline-custom" for="alim_nao">Não</label>
                        </div>
                    </div>

                    <!-- 8. Agrupar por alimento -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar por alimento</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="agrupar_alimento" id="agrup_alim_sim" value="sim" <?php echo $agruparAlim ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="agrup_alim_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_alimento" id="agrup_alim_nao" value="nao" <?php echo !$agruparAlim ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="agrup_alim_nao">Não</label>
                        </div>
                    </div>

                    <!-- 9. Ordenar por alimentos -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Ordenar por alimentos</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="ordenar_alimentos" id="ord_alim_menos" value="menos_vendidos" <?php echo ($ordenarAlim === 'menos_vendidos') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="ord_alim_menos">Menos vendidos</label>
                            <input type="radio" class="btn-check" name="ordenar_alimentos" id="ord_alim_mais" value="mais_vendidos" <?php echo ($ordenarAlim === 'mais_vendidos') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="ord_alim_mais">Mais vendidos</label>
                        </div>
                    </div>
                </div>

                <!-- Bloco Alunos & Turmas: 2 Linhas de 3 Colunas (Simetria Perfeita) -->
                <div class="row g-3 mb-3 pt-3 border-top">
                    <!-- 10. Emitir vendas por aluno -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Emitir vendas por aluno</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="emitir_vendas_aluno" id="aluno_sim" value="sim" <?php echo $emitirAluno ? 'checked' : ''; ?> onchange="atualizarEstadoAluno()">
                            <label class="btn btn-outline-custom" for="aluno_sim">Sim</label>
                            <input type="radio" class="btn-check" name="emitir_vendas_aluno" id="aluno_nao" value="nao" <?php echo !$emitirAluno ? 'checked' : ''; ?> onchange="atualizarEstadoAluno()">
                            <label class="btn btn-outline-custom" for="aluno_nao">Não</label>
                        </div>
                    </div>

                    <!-- 11. Agrupar vendas por aluno -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar vendas por aluno</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="agrupar_vendas_aluno" id="agrup_aluno_sim" value="sim" <?php echo $agruparAluno ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="agrup_aluno_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_vendas_aluno" id="agrup_aluno_nao" value="nao" <?php echo !$agruparAluno ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="agrup_aluno_nao">Não</label>
                        </div>
                    </div>

                    <!-- 12. Agrupar por série/turma -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Agrupar por série/turma</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="agrupar_serie_turma" id="turma_sim" value="sim" <?php echo $agruparTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="turma_sim">Sim</label>
                            <input type="radio" class="btn-check" name="agrupar_serie_turma" id="turma_nao" value="nao" <?php echo !$agruparTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="turma_nao">Não</label>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- 13. Quebrar página por série/turma -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Quebrar página por turma</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="quebrar_pagina_turma" id="quebra_sim" value="sim" <?php echo $quebrarTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="quebra_sim">Sim</label>
                            <input type="radio" class="btn-check" name="quebrar_pagina_turma" id="quebra_nao" value="nao" <?php echo !$quebrarTurma ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="quebra_nao">Não</label>
                        </div>
                    </div>

                    <!-- 14. Ordenar por consumo do aluno -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Ordenar por consumo do aluno</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="ordenar_consumo_aluno" id="ord_consumo_sim" value="sim" <?php echo $ordConsumo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="ord_consumo_sim">Sim</label>
                            <input type="radio" class="btn-check" name="ordenar_consumo_aluno" id="ord_consumo_nao" value="nao" <?php echo !$ordConsumo ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="ord_consumo_nao">Não</label>
                        </div>
                    </div>

                    <!-- 15. Critério de ordenação -->
                    <div class="col-12 col-md-4">
                        <label class="form-label text-microcopy text-secondary d-block mb-1">Critério de Ordenação</label>
                        <div class="btn-group btn-group-sm w-100 btn-toggle-blue" role="group">
                            <input type="radio" class="btn-check" name="ordenar_por" id="ord_menor" value="menor_consumo" <?php echo ($ordPor === 'menor_consumo') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="ord_menor">Menor consumo</label>
                            <input type="radio" class="btn-check" name="ordenar_por" id="ord_maior" value="maior_consumo" <?php echo ($ordPor === 'maior_consumo') ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-custom" for="ord_maior">Maior consumo</label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Rodapé do Card com as Ações Oficiais de Emissão (Sem poluição de listas) -->
            <div class="card-footer bg-light border-top py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-secondary text-microcopy">
                    Selecione a forma de saída do relatório após configurar os parâmetros acima.
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Imprimir / Salvar PDF (A4) -->
                    <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/imprimir" formtarget="_blank" class="btn btn-enterprise btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1.5 btn-action-emitir">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                        </svg>
                        <span>Imprimir / Salvar PDF (A4)</span>
                    </button>

                    <!-- Exportar Planilha CSV -->
                    <button type="submit" formaction="<?php echo $baseUrl; ?>/relatorios/vendas-filtros/csv" class="btn btn-enterprise btn-secondary btn-sm px-3 d-inline-flex align-items-center gap-1.5 btn-action-emitir">
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                            <path d="M4 11.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5zm0-2a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5z"/>
                        </svg>
                        <span>Exportar Planilha (CSV)</span>
                    </button>
                </div>
            </div>
        </div>

    </form>

    <!-- Rodapé Institucional Enxuto -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 text-microcopy text-muted pt-2 border-top">
        <span>Cantina Web &bull; Colégio Monteiro Lobato &bull; Módulo de Relatórios de Vendas</span>
        <span class="font-tabular">Emissão Oficial A4 &bull; Impressão / Salvar PDF</span>
    </div>

</div>

<script>
function definirDatas(inicio, fim) {
    document.getElementById('dataInicio').value = inicio;
    document.getElementById('dataFim').value = fim;
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
