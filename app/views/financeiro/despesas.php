<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Despesas Operacionais (Lançamentos e Controle Financeiro)
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">
    <!-- Barra de Contexto da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Módulo Financeiro</span>
                <span class="badge bg-light text-secondary border">Fase 24</span>
            </div>
            <h1 class="h4 font-weight-bold text-dark mb-1" style="font-size: 18px; font-weight: 700; color: #0f172a;">
                Despesas Operacionais da Cantina
            </h1>
            <p class="text-secondary text-microcopy mb-0">
                Lançamento, controle de vencimentos e classificação contábil das contas a pagar (administrativas, pessoal, vendas e manutenção).
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1" onclick="abrirModalNovaDespesa()">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8 2a.75.75 0 0 1 .75.75v4.5h4.5a.75.75 0 0 1 0 1.5h-4.5v4.5a.75.75 0 0 1-1.5 0v-4.5h-4.5a.75.75 0 0 1 0-1.5h4.5v-4.5A.75.75 0 0 1 8 2z"/></svg>
                <span>Nova Despesa</span>
            </button>
        </div>
    </div>

    <!-- Stat-Strip: Indicadores da Competência Selecionada -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Competência em Análise</span>
            <span class="stat-value font-tabular text-dark" style="font-size: 18px;">
                <?php echo htmlspecialchars($competenciaAtual !== 'todas' ? $competenciaAtual : 'Todas as Competências', ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                <?php echo (int)($resumo['total_lancamentos'] ?? 0); ?> lançamento(s) registrados
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total de Despesas</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format((float)($resumo['total_geral'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Valor bruto provisionado no mês
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total Pago / Liquidado</span>
            <span class="stat-value font-tabular text-success" style="color: #15803d !important;">
                R$ <?php echo number_format((float)($resumo['total_pago'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Contas já quitadas na conta bancária
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total a Pagar (Pendente)</span>
            <span class="stat-value font-tabular text-warning" style="color: #b45309 !important;">
                R$ <?php echo number_format((float)($resumo['total_pendente'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Vencimentos em aberto no período
            </span>
        </div>
    </div>

    <!-- Filtros de Pesquisa e Segmentação -->
    <div class="card card-enterprise mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <div class="card-body p-3">
            <form action="<?php echo $baseUrl; ?>/financeiro/despesas" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="filtrar" value="1">
                <div class="col-12 col-md-3">
                    <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Competência (Mês/Ano)</label>
                    <select name="competencia" class="form-select form-select-sm font-tabular">
                        <option value="todas" <?php echo ($competenciaAtual === 'todas') ? 'selected' : ''; ?>>Todas as Competências</option>
                        <?php foreach ($competenciasDisponiveis as $comp): ?>
                            <option value="<?php echo htmlspecialchars($comp, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($competenciaAtual === $comp) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($comp, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Categoria de Custo</label>
                    <select name="categoria" class="form-select form-select-sm">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ($categorias as $keyCat => $nomeCat): ?>
                            <option value="<?php echo htmlspecialchars($keyCat, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filtros['categoria'] === $keyCat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(\App\Models\DespesaOperacional::getNomeCategoria($keyCat), ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-2">
                    <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Situação</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Todos os Status</option>
                        <?php foreach ($statusList as $kStatus => $lblStatus): ?>
                            <option value="<?php echo htmlspecialchars($kStatus, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filtros['status'] === $kStatus) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lblStatus, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Buscar Conta / Doc</label>
                    <input type="text" name="busca" class="form-control form-control-sm" placeholder="Descrição, NF ou comprovante..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-enterprise btn-secondary btn-sm flex-fill" title="Filtrar">
                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </button>
                    <?php if (!empty($jaFiltrado)): ?>
                        <a href="<?php echo $baseUrl; ?>/financeiro/despesas" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">
                            ✕
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Despesas Operacionais em Alta Densidade -->
    <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-enterprise table-data-grid mb-0">
                <thead>
                    <tr>
                        <th class="cell-center" style="width: 95px;">Compet.</th>
                        <th class="cell-center" style="width: 105px;">Vencimento</th>
                        <th class="cell-left" style="width: 160px;">Categoria</th>
                        <th class="cell-left">Descrição da Despesa</th>
                        <th class="cell-left" style="width: 130px;">Referência / Doc</th>
                        <th class="cell-right" style="width: 120px;">Valor (R$)</th>
                        <th class="cell-center" style="width: 110px;">Situação</th>
                        <th class="cell-center" style="width: 130px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jaFiltrado)): ?>
                        <tr>
                            <td colspan="8" class="py-5 text-center text-muted">
                                <div class="empty-state-container text-center py-4">
                                    <svg width="32" height="32" viewBox="0 0 16 16" fill="#94a3b8" class="mb-2"><path d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/><path d="M2 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5z"/></svg>
                                    <p class="mb-1 text-secondary font-weight-bold">Não há nada para mostrar aqui</p>
                                    <span class="text-microcopy text-muted">Configure os filtros acima e clique no botão com a lupa para exibir as despesas operacionais.</span>
                                </div>
                            </td>
                        </tr>
                    <?php elseif (empty($despesas)): ?>
                        <tr>
                            <td colspan="8" class="py-5 text-center text-muted">
                                <div class="empty-state-container text-center py-4">
                                    <svg width="32" height="32" viewBox="0 0 16 16" fill="#94a3b8" class="mb-2"><path d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/><path d="M2 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5z"/></svg>
                                    <p class="mb-1 text-secondary font-weight-bold">Nenhuma despesa localizada</p>
                                    <span class="text-microcopy text-muted">Nenhuma despesa operacional localizada para os filtros selecionados.</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($despesas as $d): ?>
                            <tr>
                                <td class="cell-center font-tabular">
                                    <span class="code-tag"><?php echo htmlspecialchars($d['competencia_mes_ano'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="cell-center font-tabular">
                                    <?php echo date('d/m/Y', strtotime($d['data_vencimento'])); ?>
                                </td>
                                <td class="cell-left">
                                    <span class="text-truncate d-inline-block font-weight-medium" style="max-width: 150px; font-size: 12px; color: #334155;">
                                        <?php echo htmlspecialchars(\App\Models\DespesaOperacional::getNomeCategoria($d['categoria']), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="cell-left">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark font-weight-bold" style="color: #0f172a;">
                                            <?php echo htmlspecialchars($d['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php if (!empty($d['subcategoria']) || !empty($d['observacoes'])): ?>
                                            <span class="text-microcopy text-muted">
                                                <?php echo htmlspecialchars($d['subcategoria'] ?? $d['observacoes'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="cell-left font-tabular">
                                    <?php if (!empty($d['documento_referencia'])): ?>
                                        <span class="code-tag text-truncate d-inline-block" style="max-width: 120px;" title="<?php echo htmlspecialchars($d['documento_referencia'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($d['documento_referencia'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right font-tabular fw-bold" style="font-weight: 700; color: #0f172a;">
                                    R$ <?php echo number_format((float)$d['valor'], 2, ',', '.'); ?>
                                </td>
                                <td class="cell-center">
                                    <?php if ($d['status'] === 'pago'): ?>
                                        <span class="status-indicator status-active" title="Pago em <?php echo !empty($d['data_pagamento']) ? date('d/m/Y', strtotime($d['data_pagamento'])) : ''; ?>">
                                            <span class="bullet" style="color: #15803d;">●</span> Pago
                                        </span>
                                    <?php elseif ($d['status'] === 'pendente'): ?>
                                        <span class="status-indicator" style="color: #b45309;" title="Vencimento: <?php echo date('d/m/Y', strtotime($d['data_vencimento'])); ?>">
                                            <span class="bullet" style="color: #f59e0b;">●</span> Pendente
                                        </span>
                                    <?php else: ?>
                                        <span class="status-indicator" style="color: #64748b;">
                                            <span class="bullet" style="color: #94a3b8;">●</span> Cancelado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-center">
                                    <div class="d-flex justify-content-center align-items-center gap-1">
                                        <?php if ($d['status'] === 'pendente'): ?>
                                            <button type="button" class="btn btn-action btn-sm" style="color: #15803d;" title="Marcar como Pago" onclick="abrirModalPagar(<?php echo (int)$d['id']; ?>, '<?php echo htmlspecialchars(addslashes($d['descricao']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo number_format((float)$d['valor'], 2, ',', '.'); ?>')">
                                                <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/></svg>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-action btn-sm" title="Editar Despesa" onclick="editarDespesa(<?php echo (int)$d['id']; ?>)">
                                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                                        </button>
                                        <button type="button" class="btn btn-action btn-sm text-danger" title="Excluir" onclick="confirmarExclusao(<?php echo (int)$d['id']; ?>, '<?php echo htmlspecialchars(addslashes($d['descricao']), ENT_QUOTES, 'UTF-8'); ?>')">
                                            <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if (!empty($jaFiltrado) && $totalPaginas > 1): ?>
            <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-secondary text-microcopy font-tabular">
                    Exibindo <?php echo count($despesas); ?> de <?php echo $totalRegistros; ?> despesas registradas
                </span>
                <nav aria-label="Navegação de páginas">
                    <ul class="pagination pagination-sm m-0">
                        <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                            <li class="page-item <?php echo ($p === $paginaAtual) ? 'active' : ''; ?>">
                                <a class="page-link font-tabular" href="<?php echo $baseUrl; ?>/financeiro/despesas?filtrar=1&competencia=<?php echo urlencode($competenciaAtual); ?>&categoria=<?php echo urlencode($filtros['categoria']); ?>&status=<?php echo urlencode($filtros['status']); ?>&busca=<?php echo urlencode($filtros['busca']); ?>&pagina=<?php echo $p; ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal 1: Cadastro / Edição de Despesa Operacional -->
<div class="modal fade" id="modalDespesa" tabindex="-1" aria-labelledby="modalDespesaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius: 8px; border: 1px solid #cbd5e1;">
            <form id="formDespesa" action="<?php echo $baseUrl; ?>/financeiro/despesas/salvar" method="POST">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="id" id="despesaId" value="">

                <div class="modal-header py-3 px-4" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <h5 class="modal-title font-weight-bold" id="modalDespesaLabel" style="font-size: 15px; font-weight: 700; color: #0f172a;">
                        Lançamento de Despesa Operacional
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Descrição da Despesa *</label>
                            <input type="text" name="descricao" id="despesaDescricao" class="form-control form-control-sm" required placeholder="Ex: Conta de Energia Elétrica - Cantina" maxlength="150">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Valor (R$) *</label>
                            <input type="text" name="valor" id="despesaValor" class="form-control form-control-sm input-money font-tabular fw-bold" required placeholder="0,00">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Categoria Contábil (DRE) *</label>
                            <select name="categoria" id="despesaCategoria" class="form-select form-select-sm" required>
                                <option value="">Selecione a categoria...</option>
                                <?php foreach ($categorias as $keyCat => $nomeCat): ?>
                                    <option value="<?php echo htmlspecialchars($keyCat, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($nomeCat, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Subcategoria / Especificação</label>
                            <input type="text" name="subcategoria" id="despesaSubcategoria" class="form-control form-control-sm" placeholder="Ex: Energia, Água, Salário Cozinheira, Embalagens">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Competência (AAAA-MM) *</label>
                            <input type="text" name="competencia_mes_ano" id="despesaCompetencia" class="form-control form-control-sm font-tabular" required pattern="\d{4}-\d{2}" placeholder="AAAA-MM" value="<?php echo date('Y-m'); ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Data de Vencimento *</label>
                            <input type="date" name="data_vencimento" id="despesaDataVencimento" class="form-control form-control-sm font-tabular" required value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Situação da Conta</label>
                            <select name="status" id="despesaStatus" class="form-select form-select-sm" onchange="alternarCamposPagamento()">
                                <option value="pendente">Pendente (A Pagar)</option>
                                <option value="pago">Pago (Liquidado)</option>
                                <option value="cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4 campo-pagamento" style="display: none;">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Data do Pagamento</label>
                            <input type="date" name="data_pagamento" id="despesaDataPagamento" class="form-control form-control-sm font-tabular" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="col-12 col-md-4 campo-pagamento" style="display: none;">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Forma de Pagamento</label>
                            <select name="forma_pagamento" id="despesaFormaPagamento" class="form-select form-select-sm">
                                <option value="Pix">Pix / Chave</option>
                                <option value="Transferência">Transferência Bancária (TED)</option>
                                <option value="Boleto">Boleto Bancário</option>
                                <option value="Dinheiro">Dinheiro Físico (Caixa)</option>
                                <option value="Débito Automático">Débito em Conta</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Nº NF / Comprovante</label>
                            <input type="text" name="documento_referencia" id="despesaDoc" class="form-control form-control-sm" placeholder="Ex: NF 1492, Cod Barras, Recibo">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Observações Internas</label>
                            <textarea name="observacoes" id="despesaObservacoes" class="form-control form-control-sm" rows="2" placeholder="Anotações para a conferência financeira..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-4" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm" id="btnSalvarDespesa">Salvar Despesa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Quitação Rápida (Marcar como Pago) -->
<div class="modal fade" id="modalPagar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 8px;">
            <form action="<?php echo $baseUrl; ?>/financeiro/despesas/pagar" method="POST">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="id" id="pagarId" value="">
                <input type="hidden" name="competencia_redirect" value="<?php echo htmlspecialchars($competenciaAtual, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="modal-header py-2 px-3" style="background: #f0fdf4; border-bottom: 1px solid #bbf7d0;">
                    <h6 class="modal-title font-weight-bold text-success mb-0" style="font-size: 14px;">Confirmar Pagamento</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3">
                    <p class="text-secondary mb-2 text-microcopy" id="pagarDescricaoTexto"></p>
                    <div class="mb-2">
                        <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Data Pagamento *</label>
                        <input type="date" name="data_pagamento" class="form-control form-control-sm font-tabular" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Meio de Pagamento</label>
                        <select name="forma_pagamento" class="form-select form-select-sm">
                            <option value="Pix">Pix / Chave</option>
                            <option value="Transferência">Transferência Bancária</option>
                            <option value="Boleto">Boleto Bancário</option>
                            <option value="Dinheiro">Dinheiro Físico</option>
                            <option value="Débito Automático">Débito em Conta</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-success btn-sm">Confirmar Quitação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius: 8px;">
            <form action="<?php echo $baseUrl; ?>/financeiro/despesas/excluir" method="POST">
                <?php echo $csrf_field; ?>
                <input type="hidden" name="id" id="excluirId" value="">
                <input type="hidden" name="competencia_redirect" value="<?php echo htmlspecialchars($competenciaAtual, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="modal-header py-2 px-3" style="background: #fef2f2; border-bottom: 1px solid #fecaca;">
                    <h6 class="modal-title font-weight-bold text-danger mb-0" style="font-size: 14px;">Remover Despesa</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3">
                    <p class="text-secondary text-microcopy mb-0" id="excluirDescricaoTexto"></p>
                </div>

                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm">Excluir Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function alternarCamposPagamento() {
    const status = document.getElementById('despesaStatus').value;
    const campos = document.querySelectorAll('.campo-pagamento');
    campos.forEach(c => {
        c.style.display = (status === 'pago') ? 'block' : 'none';
    });
}

function abrirModalNovaDespesa() {
    const form = document.getElementById('formDespesa');
    form.reset();
    form.action = '<?php echo $baseUrl; ?>/financeiro/despesas/salvar';
    document.getElementById('despesaId').value = '';
    document.getElementById('modalDespesaLabel').innerText = 'Lançamento de Despesa Operacional';
    document.getElementById('despesaCompetencia').value = '<?php echo $competenciaAtual !== "todas" ? $competenciaAtual : date("Y-m"); ?>';
    document.getElementById('despesaDataVencimento').value = '<?php echo date("Y-m-d"); ?>';
    document.getElementById('despesaStatus').value = 'pendente';
    alternarCamposPagamento();

    const modal = new bootstrap.Modal(document.getElementById('modalDespesa'));
    modal.show();
}

function editarDespesa(id) {
    fetch('<?php echo $baseUrl; ?>/financeiro/despesas/' + id + '/json')
        .then(response => response.json())
        .then(data => {
            if (!data.sucesso || !data.despesa) {
                alert('Erro ao carregar dados da despesa.');
                return;
            }
            const d = data.despesa;
            const form = document.getElementById('formDespesa');
            form.action = '<?php echo $baseUrl; ?>/financeiro/despesas/atualizar';
            document.getElementById('modalDespesaLabel').innerText = 'Editar Despesa #' + d.id;
            document.getElementById('despesaId').value = d.id;
            document.getElementById('despesaDescricao').value = d.descricao;
            document.getElementById('despesaValor').value = parseFloat(d.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('despesaCategoria').value = d.categoria;
            document.getElementById('despesaSubcategoria').value = d.subcategoria || '';
            document.getElementById('despesaCompetencia').value = d.competencia_mes_ano;
            document.getElementById('despesaDataVencimento').value = d.data_vencimento;
            document.getElementById('despesaStatus').value = d.status;
            document.getElementById('despesaDataPagamento').value = d.data_pagamento || '<?php echo date("Y-m-d"); ?>';
            document.getElementById('despesaFormaPagamento').value = d.forma_pagamento || 'Pix';
            document.getElementById('despesaDoc').value = d.documento_referencia || '';
            document.getElementById('despesaObservacoes').value = d.observacoes || '';
            alternarCamposPagamento();

            const modal = new bootstrap.Modal(document.getElementById('modalDespesa'));
            modal.show();
        })
        .catch(err => {
            console.error(err);
            alert('Falha na comunicação ao buscar despesa.');
        });
}

function abrirModalPagar(id, descricao, valor) {
    document.getElementById('pagarId').value = id;
    document.getElementById('pagarDescricaoTexto').innerHTML = 'Confirmar quitação de <strong>' + descricao + '</strong> no valor de <strong>R$ ' + valor + '</strong>?';
    const modal = new bootstrap.Modal(document.getElementById('modalPagar'));
    modal.show();
}

function confirmarExclusao(id, descricao) {
    document.getElementById('excluirId').value = id;
    document.getElementById('excluirDescricaoTexto').innerHTML = 'Tem certeza que deseja remover a despesa <strong>' + descricao + '</strong>? O registro será arquivado com segurança.';
    const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
    modal.show();
}
</script>
