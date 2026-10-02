<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Emissão de Extrato de Consumo do Aluno (Cantina)
 * 
 * Conforme docs/alterações sistema guideline/filtro Extrato Consumo Aluno.pdf,
 * Relatório Extrato Consumo Aluno.pdf, FSD Área 6 e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$aluno = $dadosExtrato['aluno'] ?? null;
$turmas = $turmas ?? [];
$turmaId = (int)($turmaId ?? 0);
$dataInicio = $dataInicio ?? date('Y-m-01');
$dataFim = $dataFim ?? date('Y-m-d');
$apenasSaldo = !empty($apenasSaldo);
$saldoApenasPeriodo = !empty($saldoApenasPeriodo);
$abaAtiva = $abaAtiva ?? 'filtros';

$saldoInicial = (float)($dadosExtrato['saldo_inicial'] ?? 0.0);
$saldoAtual = (float)($dadosExtrato['saldo_atual'] ?? ($aluno['saldo_atual'] ?? ($aluno['responsavel_saldo'] ?? 0.0)));
$totalRecargas = (float)($dadosExtrato['total_recargas'] ?? 0.0);
$totalGasto = (float)($dadosExtrato['total_gasto'] ?? ($dadosExtrato['total_consumo'] ?? 0.0));
$totalLanches = (int)($dadosExtrato['total_lanches'] ?? 0);
$linhas = $dadosExtrato['linhas'] ?? ($dadosExtrato['movimentacoes'] ?? []);
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

<!-- Topbar da Página -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Emissão de Extrato de Consumo do Aluno (Cantina)</h1>
        <p class="text-secondary text-microcopy mb-0">
            Consulta transparente de recargas, saldo familiar compartilhado e histórico de consumo individual por período com saldo progressivo.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($aluno): ?>
            <!-- Menu Dropdown Corporativo Emitir -->
            <div class="dropdown">
                <button class="btn btn-enterprise btn-primary btn-sm dropdown-toggle d-inline-flex align-items-center gap-1.5" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="btnDropdownEmitir">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                    Emitir
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="btnDropdownEmitir" style="font-size: 13px;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" 
                           href="<?php echo $baseUrl; ?>/relatorios/extrato-aluno/imprimir?aluno_id=<?php echo (int)$aluno['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
                           target="_blank" 
                           onclick="window.open(this.href, '_blank'); return false;">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="text-primary"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                            Visualizar / Imprimir (A4)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" 
                           href="<?php echo $baseUrl; ?>/relatorios/extrato-aluno/pdf?aluno_id=<?php echo (int)$aluno['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="text-danger"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                            Baixar em PDF (A4)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" 
                           href="<?php echo $baseUrl; ?>/relatorios/extrato-aluno/csv?aluno_id=<?php echo (int)$aluno['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="text-success"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.5 12.5h7v-1h-7v1zm0-2h7v-1h-7v1zm0-2h7v-1h-7v1z"/></svg>
                            Exportar Planilha (CSV)
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Card de Filtros de Pesquisa -->
<div class="card-enterprise card-overflow-visible mb-4" style="overflow: visible !important; position: relative; z-index: 30;">
    <div class="card-enterprise-body p-3" style="overflow: visible !important;">
        <form action="<?php echo $baseUrl; ?>/relatorios/extrato-aluno" method="GET" id="formExtratoAluno" style="position: relative; overflow: visible !important;">
            <div class="row g-3 align-items-end">
                <!-- Localizar Aluno com Autocomplete -->
                <div class="col-12 col-md-5" style="position: relative; z-index: 40;">
                    <label for="aluno_busca" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                        Localizar Aluno (Nome ou Matrícula)
                    </label>
                    <div class="search-input-wrapper position-relative" style="position: relative;">
                        <span class="search-icon">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        </span>
                        <input type="text" id="aluno_busca" name="aluno_busca" class="form-control form-control-sm" style="padding-left: 36px !important;" placeholder="Digite para buscar aluno..." autocomplete="off" value="<?php echo htmlspecialchars($aluno['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="aluno_id" id="hiddenAlunoId" value="<?php echo (int)($alunoId ?? 0); ?>">

                        <!-- Dropdown flutuante de Autocomplete -->
                        <div id="autocompleteDropdown" class="list-group position-absolute rounded shadow-lg d-none" 
                             style="top: calc(100% + 4px); left: 0; right: 0; width: 100%; z-index: 1080; max-height: 280px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2), 0 8px 10px -6px rgba(15, 23, 42, 0.12) !important;">
                        </div>
                    </div>
                </div>

                <!-- Filtro Opcional de Turma -->
                <div class="col-12 col-md-3">
                    <label for="selectTurma" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Turma (Opcional)</label>
                    <select name="turma_id" id="selectTurma" class="form-select form-select-sm">
                        <option value="">Todas as Turmas...</option>
                        <?php foreach ($turmas as $t): ?>
                            <option value="<?php echo (int)$t['id']; ?>" <?php echo ($turmaId === (int)$t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['serie'] . ' ' . $t['nome_turma'] . ' (' . ucfirst($t['turno']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Período: Data Inicial -->
                <div class="col-6 col-md-2">
                    <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                    <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Período: Data Final -->
                <div class="col-6 col-md-2">
                    <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                    <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <!-- Botões Rápidos de Período e Botão de Filtrar -->
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="text-secondary text-microcopy me-1">Atalhos de Período:</span>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="hoje" onclick="definirPeriodo('hoje')">Hoje</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="semana" onclick="definirPeriodo('semana')">Esta Semana</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="mes" onclick="definirPeriodo('mes_atual')">Este Mês</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="mes_anterior" onclick="definirPeriodo('mes_anterior')">Mês Anterior</button>
                </div>
                <div>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        Filtrar / Carregar Extrato
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!$aluno): ?>
    <!-- Estado Inicial / Nenhum Aluno Selecionado -->
    <div class="card-enterprise p-5 text-center">
        <div class="text-secondary mb-3">
            <svg width="48" height="48" viewBox="0 0 16 16" fill="currentColor" class="text-muted"><path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/></svg>
        </div>
        <h2 class="h6 font-weight-bold text-dark mb-1">Localize um Aluno para Emitir o Extrato</h2>
        <p class="text-secondary text-microcopy mb-0" style="max-width: 480px; margin: 0 auto;">
            Utilize a caixa de busca acima para digitar o nome ou número de matrícula do estudante. Em seguida, clique sobre o aluno sugerido para carregar os lançamentos com saldo progressivo linha a linha.
        </p>
    </div>
<?php else: ?>
    <!-- 1. Banner de Identificação do Aluno -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-body d-flex align-items-center justify-content-between flex-wrap gap-3 p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary font-weight-bold font-tabular" style="width: 48px; height: 48px; font-size: 16px;">
                    <?php echo htmlspecialchars(strtoupper(substr($aluno['nome'] ?? 'A', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="h6 font-weight-bold text-dark mb-0"><?php echo htmlspecialchars(mb_strtoupper($aluno['nome'], 'UTF-8')); ?></h2>
                        <span class="code-tag font-tabular" style="font-size: 11px;">
                            Matrícula: <?php echo htmlspecialchars($aluno['matricula']); ?>
                        </span>
                        <span class="code-tag font-tabular" style="font-size: 11px;">
                            <?php echo htmlspecialchars(($aluno['serie'] ?? '') . ' ' . ($aluno['nome_turma'] ?? '')); ?> (<?php echo htmlspecialchars(ucfirst($aluno['turma_turno'] ?? ($aluno['turno'] ?? 'matutino'))); ?>)
                        </span>
                    </div>
                    <div class="text-secondary text-microcopy mt-1">
                        Responsável: <strong class="text-dark"><?php echo htmlspecialchars($aluno['responsavel_nome'] ?? 'Não informado'); ?></strong>
                        <?php if (!empty($aluno['responsavel_whatsapp'])): ?>
                            &bull; WhatsApp: <span class="font-tabular"><?php echo htmlspecialchars($aluno['responsavel_whatsapp']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Saldo Atual da Carteira</span>
                <span class="font-tabular h5 font-weight-bold text-primary mb-0">
                    R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                </span>
                <span class="text-microcopy text-muted d-block">Saldo integrado familiar</span>
            </div>
        </div>
    </div>

    <!-- 2. Stat-Strip: Resumo do Período -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Saldo Inicial</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                <?php echo $saldoApenasPeriodo ? 'Período isolado (R$ 0,00)' : 'Anterior a ' . date('d/m/Y', strtotime($dataInicio)); ?>
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total Recarregado</span>
            <span class="stat-value font-tabular text-success">
                R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Créditos aportados no período
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total Consumido</span>
            <span class="stat-value font-tabular text-danger">
                R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                <?php echo $totalLanches; ?> lanche(s) consumido(s)
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Saldo Atual</span>
            <span class="stat-value font-tabular text-primary">
                R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Disponível na carteira
            </span>
        </div>
    </div>

    <!-- 3. Demonstrativo Sintético ou Tabela Analítica -->
    <?php if ($apenasSaldo): ?>
        <div class="card-enterprise p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h6 font-weight-bold text-dark mb-0">Demonstrativo Sintético de Saldo do Aluno</h3>
                <span class="code-tag font-tabular">Opção Ativa: Apenas Saldo</span>
            </div>
            <p class="text-secondary text-microcopy mb-4">
                O extrato detalhado de itens individuais está oculto conforme a opção selecionada na aba <strong>Layout</strong>. Apenas os totais consolidados e o saldo oficial são emitidos.
            </p>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light border rounded">
                        <span class="text-microcopy text-uppercase text-secondary font-weight-bold d-block mb-1">Saldo Inicial</span>
                        <span class="h6 font-weight-bold font-tabular text-dark mb-0">R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?></span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light border rounded">
                        <span class="text-microcopy text-uppercase text-secondary font-weight-bold d-block mb-1">Total Recargas (+)</span>
                        <span class="h6 font-weight-bold font-tabular text-success mb-0">R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?></span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light border rounded">
                        <span class="text-microcopy text-uppercase text-secondary font-weight-bold d-block mb-1">Total Consumo (-)</span>
                        <span class="h6 font-weight-bold font-tabular text-danger mb-0">R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?></span>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-3 bg-light border rounded" style="border-left: 3px solid #1d4ed8 !important;">
                        <span class="text-microcopy text-uppercase text-secondary font-weight-bold d-block mb-1">Saldo Final</span>
                        <span class="h6 font-weight-bold font-tabular text-primary mb-0">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tabela Analítica Oficial com Saldo Progressivo Linha a Linha -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-0">Extrato Analítico de Consumo e Recargas</h2>
                    <span class="text-secondary text-microcopy">
                        Período: <strong class="font-tabular"><?php echo date('d/m/Y', strtotime($dataInicio)); ?></strong> a <strong class="font-tabular"><?php echo date('d/m/Y', strtotime($dataFim)); ?></strong>
                    </span>
                </div>
                <span class="badge bg-light text-secondary border font-tabular">
                    <?php echo count($linhas); ?> lançamento(s)
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-enterprise table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 12%;">Data</th>
                            <th style="width: 38%;">Descrição</th>
                            <th class="text-center" style="width: 8%;">Qtd</th>
                            <th class="text-end" style="width: 12%;">V. Unitário</th>
                            <th class="text-end" style="width: 12%;">V. Total</th>
                            <th class="text-center" style="width: 10%;">Forma Pgto</th>
                            <th class="text-end" style="width: 12%;">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Linha de Saldo Inicial -->
                        <tr style="background-color: #f8fafc; font-weight: 600;">
                            <td class="font-tabular text-muted">-</td>
                            <td><em>Saldo inicial</em></td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-end font-tabular text-dark">
                                R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?>
                            </td>
                        </tr>

                        <?php if (empty($linhas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-secondary text-microcopy">
                                    Nenhum lançamento de consumo ou recarga registrado para este aluno no período especificado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($linhas as $l): ?>
                                <?php 
                                    $isRec = ($l['tipo'] === 'recarga');
                                    $bgClass = $isRec ? 'style="background-color: #f0fdf4;"' : '';
                                ?>
                                <tr <?php echo $bgClass; ?>>
                                    <td class="font-tabular">
                                        <?php echo htmlspecialchars($l['data_formatada'] ?? date('d/m/Y', strtotime($l['data']))); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($l['descricao']); ?></strong>
                                    </td>
                                    <td class="text-center font-tabular">
                                        <?php echo htmlspecialchars((string)$l['quantidade']); ?>
                                    </td>
                                    <td class="text-end font-tabular">
                                        <?php 
                                            if (is_numeric($l['valor_unitario'])) {
                                                echo 'R$ ' . number_format((float)$l['valor_unitario'], 2, ',', '.');
                                            } else {
                                                echo htmlspecialchars((string)$l['valor_unitario']);
                                            }
                                        ?>
                                    </td>
                                    <td class="text-end font-tabular <?php echo $isRec ? 'text-success font-weight-bold' : ''; ?>">
                                        R$ <?php echo number_format((float)$l['valor_total'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="code-tag font-tabular" style="font-size: 11px;">
                                            <?php echo htmlspecialchars($l['forma_pagamento']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-tabular font-weight-bold <?php echo ($l['saldo'] < 0) ? 'text-danger' : 'text-dark'; ?>">
                                        R$ <?php echo number_format((float)$l['saldo'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc; font-weight: 800;">
                            <td colspan="4">Total gasto no periodo</td>
                            <td class="text-end font-tabular text-danger">
                                R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?>
                            </td>
                            <td colspan="2" class="text-end font-tabular text-primary">
                                Saldo Atual: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

</div>

<script>
// Atalhos rápidos de período
function definirPeriodo(tipo) {
    const hoje = new Date();
    const dataIni = document.getElementById('dataInicio');
    const dataFim = document.getElementById('dataFim');
    if (!dataIni || !dataFim) return;

    function formatar(d) {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    }

    if (tipo === 'hoje') {
        dataIni.value = formatar(hoje);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'semana') {
        const diaSemana = hoje.getDay(); // 0 = Domingo, 1 = Segunda
        const diffSegunda = (diaSemana === 0 ? -6 : 1) - diaSemana;
        const segunda = new Date(hoje);
        segunda.setDate(hoje.getDate() + diffSegunda);
        dataIni.value = formatar(segunda);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'mes_atual') {
        const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        dataIni.value = formatar(primeiroDia);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'mes_anterior') {
        const primeiroDiaAnt = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
        const ultimoDiaAnt = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
        dataIni.value = formatar(primeiroDiaAnt);
        dataFim.value = formatar(ultimoDiaAnt);
    }
}

// Autocomplete de Alunos
document.addEventListener('DOMContentLoaded', function() {
    const inputBusca = document.getElementById('aluno_busca') || document.getElementById('inputBuscaAluno');
    const hiddenAlunoId = document.getElementById('hiddenAlunoId');
    const dropdown = document.getElementById('autocompleteDropdown');
    const selectTurma = document.getElementById('selectTurma');
    const form = document.getElementById('formExtratoAluno');

    if (!inputBusca || !dropdown) return;

    let debounceTimer = null;

    inputBusca.addEventListener('input', function() {
        const termo = this.value.trim();
        clearTimeout(debounceTimer);

        if (termo.length < 2) {
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(function() {
            const url = '<?php echo $baseUrl; ?>/relatorios/buscar-alunos-ajax?q=' + encodeURIComponent(termo);
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (!data.sucesso || !data.alunos || data.alunos.length === 0) {
                        dropdown.innerHTML = '<div class="p-2 text-muted text-microcopy text-center">Nenhum aluno encontrado</div>';
                        dropdown.classList.remove('d-none');
                        return;
                    }

                    data.alunos.forEach(aluno => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action py-2 px-3 d-flex justify-content-between align-items-center';
                        item.style.fontSize = '12px';
                        item.innerHTML = `
                            <div>
                                <strong class="text-dark">${aluno.nome}</strong>
                                <span class="text-secondary text-microcopy d-block">Matrícula: ${aluno.matricula} &bull; ${aluno.turma || ''}</span>
                            </div>
                            <span class="badge bg-light text-primary border font-tabular">R$ ${parseFloat(aluno.saldo_atual || 0).toFixed(2).replace('.', ',')}</span>
                        `;

                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            inputBusca.value = aluno.nome;
                            hiddenAlunoId.value = aluno.id;
                            dropdown.classList.add('d-none');
                            form.submit();
                        });

                        dropdown.appendChild(item);
                    });

                    dropdown.classList.remove('d-none');
                })
                .catch(err => {
                    console.error('Erro na busca de alunos:', err);
                });
        }, 250);
    });

    // Fecha dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!inputBusca.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('d-none');
        }
    });
});
</script>
