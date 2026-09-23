<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Listagem Histórica de Notas Fiscais de Entrada (NF-e)
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Voltar ao Estoque
                </a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Notas Fiscais de Entrada (NF-e)</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/importar-xml" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/></svg>
                Nova Entrada via XML
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <!-- Stat-Strip Fiscal -->
    <div class="stat-strip mb-3">
        <div class="stat-item">
            <span class="stat-label">Total de Notas</span>
            <span class="stat-value font-tabular"><?php echo number_format($metricas['total_notas'], 0, ',', '.'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Valor Total Acumulado</span>
            <span class="stat-value font-tabular">R$ <?php echo number_format($metricas['valor_acumulado'], 2, ',', '.'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Notas no Mês Atual</span>
            <span class="stat-value font-tabular text-primary"><?php echo number_format($metricas['notas_mes_atual'], 0, ',', '.'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Faturado no Mês</span>
            <span class="stat-value font-tabular">R$ <?php echo number_format($metricas['valor_mes_atual'], 2, ',', '.'); ?></span>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card-enterprise p-3 mb-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label small fw-semibold text-muted mb-1">Busca:</label>
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Número da nota, fornecedor, CNPJ ou chave de 44 dígitos..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Data Início:</label>
                <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" onchange="this.form.submit()">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Data Fim:</label>
                <input type="date" name="data_fim" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" onchange="this.form.submit()">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm w-100" title="Filtrar">Filtrar</button>
                <?php if (!empty($filtros['busca']) || !empty($filtros['data_inicio']) || !empty($filtros['data_fim'])): ?>
                    <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Notas Fiscais -->
    <div class="card-enterprise">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="card-enterprise-title">Documentos Fiscais Armazenados</span>
            <span class="text-muted small">Exibindo <?php echo count($notas); ?> notas importadas</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 110px;">Número NF-e</th>
                        <th style="width: 130px;">Data Emissão</th>
                        <th>Fornecedor / Emitente</th>
                        <th style="width: 120px;" class="text-end">Valor Total</th>
                        <th style="width: 140px;" class="text-end">Unid. Inseridas</th>
                        <th style="width: 140px;">Importado Em</th>
                        <th style="width: 160px;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <em>Não há nada para mostrar aqui</em>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notas as $nf): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-slate-900 font-tabular fs-6">Nº <?php echo htmlspecialchars($nf['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <code class="code-tag d-block mt-1" style="font-size: 10px;">Chave: ...<?php echo substr($nf['chave_nfe'], -8); ?></code>
                                </td>
                                <td class="font-tabular text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($nf['data_emissao'])); ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-slate-900 d-block"><?php echo htmlspecialchars($nf['razao_social_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-secondary small font-tabular">CNPJ: <?php echo htmlspecialchars($nf['cnpj_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end font-tabular fw-bold text-slate-900">
                                    R$ <?php echo number_format($nf['valor_total'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-end font-tabular text-success fw-bold">
                                    +<?php echo number_format($nf['total_unidades_adicionadas'], 0, ',', '.'); ?> un
                                </td>
                                <td class="small">
                                    <span class="text-slate-800 d-block"><?php echo date('d/m/Y H:i', strtotime($nf['created_at'])); ?></span>
                                    <span class="text-muted" style="font-size: 11px;">por <?php echo htmlspecialchars($nf['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nf['id']; ?>" class="btn btn-enterprise btn-secondary btn-sm" title="Ver Itens Mapeados e Detalhes">
                                            Detalhes
                                        </a>
                                        <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nf['id']; ?>/download" class="btn btn-enterprise btn-secondary btn-sm" title="Baixar Arquivo XML">
                                            XML
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
