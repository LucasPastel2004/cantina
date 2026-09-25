<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório de Inadimplência e Bloqueios MED
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.4) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$familias = $familias ?? [];
?>

<div class="container-fluid px-0" style="max-width: 1400px; margin: 0 auto;">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Relatório de Inadimplência & Bloqueios MED</h1>
        <p class="text-secondary text-microcopy mb-0">
            Controle analítico de contas a receber, saldo devedor na carteira familiar e bloqueios decorrentes de contestações MED Pix.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo $baseUrl; ?>/admin/contestacoes/pdf" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Baixar Relatório Executivo em PDF A4 Timbrado">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
            Exportar PDF (A4 Timbrado)
        </a>
    </div>
</div>

<!-- Filtros de Busca -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body">
        <form action="<?php echo $baseUrl; ?>/relatorios/devedores" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label for="inputBusca" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                    Localizar Responsável ou Aluno
                </label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="#64748b"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="text" name="busca" id="inputBusca" class="form-control form-control-sm border-start-0 font-tabular" placeholder="Nome, CPF ou dependente..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <div class="col-12 col-md-4">
                <label for="filtroStatus" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Situação da Conta</label>
                <select name="status" id="filtroStatus" class="form-select form-select-sm font-tabular">
                    <option value="todos" <?php echo ($status === 'todos') ? 'selected' : ''; ?>>Todas as Restrições (Saldo < 0 ou Bloqueadas MED)</option>
                    <option value="bloqueados" <?php echo ($status === 'bloqueados') ? 'selected' : ''; ?>>Apenas Bloqueados Preventivamente por MED</option>
                    <option value="negativos" <?php echo ($status === 'negativos') ? 'selected' : ''; ?>>Apenas Saldo Devedor (< R$ 0,00)</option>
                </select>
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm flex-fill">
                    Filtrar
                </button>
                <a href="<?php echo $baseUrl; ?>/relatorios/devedores" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">
                    Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip: Indicadores de Inadimplência -->
<div class="stat-strip mb-4">
    <div class="stat-item">
        <span class="stat-label">Montante Total em Aberto (Débito)</span>
        <span class="stat-value font-tabular text-danger">
            R$ <?php echo number_format($totalDevedor, 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Contas a regularizar na cantina
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Famílias sob Restrição</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo (int)$totalDevedoresContagem; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">famílias</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Total cadastrado no banco de dados
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Registros Listados</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo (int)$totalRegistros; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">no filtro</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Página <?php echo $pagina; ?> de <?php echo max(1, $totalPaginas); ?>
        </span>
    </div>
</div>

<!-- Tabela de Titulares Devedores -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <h2 class="h6 font-weight-bold text-dark mb-0">Relação Nominal de Famílias Inadimplentes</h2>
        <span class="badge bg-light text-secondary border font-tabular">
            <?php echo (int)$totalRegistros; ?> família(s)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0">
            <thead>
                <tr>
                    <th>Responsável Titular</th>
                    <th style="width: 150px;">Contato</th>
                    <th>Dependentes Vinculados</th>
                    <th class="text-end" style="width: 140px;">Saldo da Carteira</th>
                    <th style="width: 140px;">Situação MED</th>
                    <th class="text-center" style="width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($familias)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-secondary text-microcopy">
                            Nenhuma família localizada para os critérios informados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($familias as $f): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($f['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <div class="text-secondary text-microcopy font-tabular">
                                    CPF: <?php echo htmlspecialchars($f['cpf_formatado'] ?? $f['cpf'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td>
                                <div class="font-tabular text-dark" style="font-size: 13px;">
                                    <?php echo htmlspecialchars($f['whatsapp'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="text-secondary text-microcopy text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($f['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($f['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td>
                                <span class="text-dark" style="font-size: 13px;">
                                    <?php echo htmlspecialchars($f['dependentes_str'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td class="text-end font-tabular font-weight-bold <?php echo ((float)$f['saldo_atual'] < 0) ? 'text-danger' : 'text-dark'; ?>" style="font-size: 14px;">
                                R$ <?php echo number_format((float)$f['saldo_atual'], 2, ',', '.'); ?>
                            </td>
                            <td>
                                <?php if (!empty($f['bloqueado_med'])): ?>
                                    <span class="code-tag font-tabular">
                                        Bloqueado MED
                                    </span>
                                <?php elseif ((float)$f['saldo_atual'] < 0): ?>
                                    <span class="code-tag font-tabular">
                                        Saldo Negativo
                                    </span>
                                <?php else: ?>
                                    <span class="code-tag font-tabular">Regular</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?php echo $baseUrl; ?>/admin/contestacoes?busca=<?php echo urlencode($f['cpf']); ?>" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" title="Gerenciar contestação ou quitar débito">
                                    Painel MED
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
        <div class="card-enterprise-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-secondary text-microcopy font-tabular">
                Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?> (<?php echo $totalRegistros; ?> registros)
            </span>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                    <li class="page-item <?php echo ($p === $pagina) ? 'active' : ''; ?>">
                        <a class="page-link font-tabular" href="<?php echo $baseUrl; ?>/relatorios/devedores?pagina=<?php echo $p; ?>&status=<?php echo urlencode($status); ?>&busca=<?php echo urlencode($busca); ?>">
                            <?php echo $p; ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

</div>
