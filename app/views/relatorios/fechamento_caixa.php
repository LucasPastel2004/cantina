<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Demonstrativo de Fechamento de Caixa & Prestação de Contas
 * 
 * Conforme docs/FSD.md (Área 4 / Tela 5.4, Área 6, Módulo 7.2) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Demonstrativo de Fechamento de Caixa</h1>
        <p class="text-secondary text-microcopy mb-0">
            Histórico consolidado de apurações por turno, contagem física de gaveta, faturamento em espécie, Pix balcão e conferência de sobras/quebras.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5" title="Ir para a Gestão Operacional de Caixa">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/></svg>
            Operação de Caixa Hoje
        </a>
    </div>
</div>

<!-- Filtros de Busca -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body">
        <form action="<?php echo $baseUrl; ?>/relatorios/fechamento-caixa" method="GET" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label for="filtroTurno" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Turno Escolar</label>
                <select name="turno" id="filtroTurno" class="form-select form-select-sm font-tabular">
                    <option value="">Todos os Turnos</option>
                    <option value="matutino" <?php echo ($turno === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="vespertino" <?php echo ($turno === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-6 col-md-3">
                <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-fill">
                    Filtrar
                </button>
                <a href="<?php echo $baseUrl; ?>/relatorios/fechamento-caixa" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">
                    Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip: Apuração Consolidada do Período -->
<div class="stat-strip mb-4">
    <div class="stat-item">
        <span class="stat-label">Faturamento Total Declarado</span>
        <span class="stat-value font-tabular text-primary">
            R$ <?php echo number_format((float)($totaisPeriodo['faturamento_total'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Dinheiro Espécie + Pix Balcão
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Total em Espécie (Gaveta)</span>
        <span class="stat-value font-tabular text-dark">
            R$ <?php echo number_format((float)($totaisPeriodo['faturamento_dinheiro'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Contagem física de numerário
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Total em Pix de Balcão</span>
        <span class="stat-value font-tabular text-dark">
            R$ <?php echo number_format((float)($totaisPeriodo['faturamento_pix'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Recebimentos diretos no balcão
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Apuração Sobra / Quebra Líquida</span>
        <span class="stat-value font-tabular <?php echo ($totaisPeriodo['saldo_quebra_sobra'] < 0) ? 'text-danger' : (($totaisPeriodo['saldo_quebra_sobra'] > 0) ? 'text-success' : 'text-secondary'); ?>">
            <?php echo ($totaisPeriodo['saldo_quebra_sobra'] > 0 ? '+' : '') . 'R$ ' . number_format((float)($totaisPeriodo['saldo_quebra_sobra'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            <?php echo (int)($totaisPeriodo['total_com_sobra'] ?? 0); ?> sobra(s) • <?php echo (int)($totaisPeriodo['total_com_quebra'] ?? 0); ?> quebra(s)
        </span>
    </div>
</div>

<!-- Tabela de Sessões de Caixa -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <h2 class="h6 font-weight-bold text-dark mb-0">Histórico de Sessões de Caixa e Fechamentos</h2>
        <span class="badge bg-light text-secondary border font-tabular">
            <?php echo (int)$totalHistorico; ?> sessão(ões) registrada(s)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 100px;">Sessão</th>
                    <th style="width: 110px;">Turno</th>
                    <th style="width: 180px;">Abertura / Operador</th>
                    <th style="width: 150px;">Fechamento</th>
                    <th class="text-end" style="width: 130px;">Dinheiro (R$)</th>
                    <th class="text-end" style="width: 130px;">Pix Balcão (R$)</th>
                    <th class="text-end" style="width: 140px;">Total Apurado</th>
                    <th class="text-end" style="width: 120px;">Diferença</th>
                    <th style="width: 100px;">Status</th>
                    <th class="text-center" style="width: 110px;">Comprovante</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sessoes)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-secondary text-microcopy">
                            Nenhuma sessão de caixa encontrada para os filtros selecionados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sessoes as $s): ?>
                        <tr>
                            <td class="font-tabular font-weight-bold text-dark">
                                #CX-<?php echo (int)$s['id']; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border font-tabular">
                                    <?php echo ucfirst($s['turno']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-tabular font-weight-bold text-dark">
                                    <?php echo date('d/m/Y H:i', strtotime($s['aberto_em'])); ?>
                                </div>
                                <div class="text-secondary text-microcopy font-tabular">
                                    <?php echo htmlspecialchars($s['operador_nome'] ?? $s['operador_login'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td class="font-tabular text-secondary">
                                <?php echo !empty($s['fechado_em']) ? date('d/m/Y H:i', strtotime($s['fechado_em'])) : '<span class="text-warning-emphasis font-weight-bold">Aberta</span>'; ?>
                            </td>
                            <td class="text-end font-tabular text-dark">
                                R$ <?php echo number_format((float)(($s['total_dinheiro_recargas'] ?? 0.0) + ($s['total_dinheiro_avulso'] ?? 0.0)), 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular text-dark">
                                R$ <?php echo number_format((float)($s['total_pix_balcao'] ?? 0.0), 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular font-weight-bold text-dark">
                                R$ <?php echo number_format((float)(($s['total_dinheiro_recargas'] ?? 0.0) + ($s['total_dinheiro_avulso'] ?? 0.0) + ($s['total_pix_balcao'] ?? 0.0)), 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular font-weight-bold <?php echo ((float)($s['diferenca_quebra_sobra'] ?? 0.0) < 0) ? 'text-danger' : (((float)($s['diferenca_quebra_sobra'] ?? 0.0) > 0) ? 'text-success' : 'text-secondary'); ?>">
                                <?php 
                                    $dif = (float)($s['diferenca_quebra_sobra'] ?? 0.0);
                                    echo ($dif > 0 ? '+' : '') . 'R$ ' . number_format($dif, 2, ',', '.');
                                ?>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'fechado'): ?>
                                    <span class="code-tag font-tabular">Fechada</span>
                                <?php else: ?>
                                    <span class="status-indicator status-active">
                                        <span class="bullet">●</span> Em Aberto
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($s['status'] === 'fechado'): ?>
                                    <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo (int)$s['id']; ?>/pdf" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" title="Baixar Comprovante Oficial A4 / Bobina" target="_blank">
                                        PDF A4
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted text-microcopy font-tabular">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação Corporativa Oficial (Padrão Enterprise Anti-Slop) -->
    <?php 
        $paginaAtual    = (int)($paginaAtual ?? $pagina ?? 1);
        $totalRegistros = (int)($totalRegistros ?? $totalHistorico ?? 0);
        $totalPaginas   = (int)($totalPaginas ?? 1);
        $itensPorPagina = (int)($itensPorPagina ?? 20);
        require __DIR__ . '/../partials/pagination.php'; 
    ?>
</div>

</div>
