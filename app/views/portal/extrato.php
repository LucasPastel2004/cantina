<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Extrato Analítico de Consumo do Aluno (Portal da Família)
 * 
 * Conforme docs/alterações sistema guideline/Relatório Extrato Consumo Aluno.pdf,
 * FSD Área 1 & Área 6 e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dependentes = $dependentes ?? [];
$alunoSelecionado = $alunoSelecionado ?? ($dependentes[0] ?? null);
$alunoId = (int)($alunoSelecionado['id'] ?? 0);
$dataInicio = $dataInicio ?? date('Y-m-01');
$dataFim = $dataFim ?? date('Y-m-d');

$saldoInicial = (float)($dadosExtrato['saldo_inicial'] ?? 0.0);
$saldoAtual = (float)($dadosExtrato['saldo_atual'] ?? ($saldoAtual ?? 0.0));
$totalRecargas = (float)($dadosExtrato['total_recargas'] ?? 0.0);
$totalGasto = (float)($dadosExtrato['total_gasto'] ?? ($dadosExtrato['total_consumo'] ?? 0.0));
$totalLanches = (int)($dadosExtrato['total_lanches'] ?? 0);
$linhas = $dadosExtrato['linhas'] ?? ($dadosExtrato['movimentacoes'] ?? []);
?>

<div class="portal-extrato-container">

    <!-- Cabeçalho Executivo do Portal -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-4 border-bottom border-subtle gap-3">
        <div>
            <h1 class="page-title mb-1" style="font-size: 18px; font-weight: 700; color: #0f172a;">Extrato Analítico do Aluno</h1>
            <p class="text-secondary mb-0" style="font-size: 13px;">
                Acompanhamento analítico e auditável de compras diárias, recargas Pix e evolução do saldo da carteira familiar.
            </p>
        </div>
        <?php if ($alunoSelecionado): ?>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?php echo $baseUrl; ?>/portal/extrato/imprimir?aluno_id=<?php echo $alunoId; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
                   target="_blank" 
                   onclick="window.open(this.href, '_blank'); return false;" 
                   class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5"
                   title="Visualizar espelho para impressão A4">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                    Visualizar / Imprimir (A4)
                </a>
                <a href="<?php echo $baseUrl; ?>/portal/extrato/pdf?aluno_id=<?php echo $alunoId; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
                   class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5"
                   title="Baixar Extrato Oficial em PDF Timbrado A4">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                    Baixar Extrato Oficial (PDF)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seletor de Dependentes (caso haja mais de um filho vinculado) -->
    <?php if (count($dependentes) > 1): ?>
        <div class="mb-4">
            <span class="text-secondary text-microcopy text-uppercase font-weight-bold d-block mb-2">Filho(a) / Dependente:</span>
            <div class="d-flex gap-2 flex-wrap">
                <?php foreach ($dependentes as $dep): ?>
                    <?php $isActive = ((int)$dep['id'] === $alunoId); ?>
                    <a href="<?php echo $baseUrl; ?>/portal/extrato?aluno_id=<?php echo (int)$dep['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
                       class="btn btn-enterprise <?php echo $isActive ? 'btn-primary' : 'btn-secondary'; ?> btn-sm d-inline-flex align-items-center gap-2">
                        <span><?php echo htmlspecialchars($dep['nome']); ?></span>
                        <span class="badge <?php echo $isActive ? 'bg-white text-primary' : 'bg-light text-secondary border'; ?> font-tabular" style="font-size: 11px;">
                            <?php echo htmlspecialchars($dep['serie'] ?? ($dep['nome_turma'] ?? '')); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Barra de Filtros de Período -->
    <div class="card-enterprise p-3 mb-4">
        <form action="<?php echo $baseUrl; ?>/portal/extrato" method="GET" class="row g-3 align-items-end" id="formFiltroExtrato">
            <input type="hidden" name="aluno_id" value="<?php echo $alunoId; ?>">

            <div class="col-12 col-md-4">
                <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio); ?>">
            </div>

            <div class="col-12 col-md-4">
                <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim); ?>">
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1">
                    Filtrar Período
                </button>
            </div>
        </form>

        <!-- Atalhos de Período -->
        <div class="d-flex align-items-center gap-1.5 mt-2 pt-2 border-top flex-wrap">
            <span class="text-secondary text-microcopy me-1">Atalhos:</span>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('hoje')">Hoje</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('semana')">Esta Semana</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('mes_atual')">Este Mês</button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" onclick="definirPeriodo('mes_anterior')">Mês Anterior</button>
        </div>
    </div>

    <?php if ($alunoSelecionado): ?>
        <!-- Card de Identificação do Filho -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-body d-flex align-items-center justify-content-between flex-wrap gap-3 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary font-weight-bold font-tabular" style="width: 44px; height: 44px; font-size: 16px;">
                        <?php echo htmlspecialchars(strtoupper(substr($alunoSelecionado['nome'] ?? 'A', 0, 1))); ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h2 class="h6 font-weight-bold text-dark mb-0"><?php echo htmlspecialchars(mb_strtoupper($alunoSelecionado['nome'], 'UTF-8')); ?></h2>
                            <span class="code-tag font-tabular" style="font-size: 11px;">
                                Matrícula: <?php echo htmlspecialchars($alunoSelecionado['matricula'] ?? '-'); ?>
                            </span>
                            <span class="code-tag font-tabular" style="font-size: 11px;">
                                <?php echo htmlspecialchars(($alunoSelecionado['serie'] ?? '') . ' ' . ($alunoSelecionado['nome_turma'] ?? '')); ?> (<?php echo htmlspecialchars(ucfirst($alunoSelecionado['turma_turno'] ?? ($alunoSelecionado['turno'] ?? 'matutino'))); ?>)
                            </span>
                        </div>
                        <div class="text-secondary text-microcopy mt-1">
                            Período: <strong class="font-tabular text-dark"><?php echo date('d/m/Y', strtotime($dataInicio)); ?></strong> a <strong class="font-tabular text-dark"><?php echo date('d/m/Y', strtotime($dataFim)); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Saldo Atual da Família</span>
                    <span class="font-tabular h5 font-weight-bold text-primary mb-0">
                        R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                    </span>
                    <span class="text-microcopy text-muted d-block">Disponível para agendamentos</span>
                </div>
            </div>
        </div>

        <!-- Stat-Strip: Resumo Financeiro do Período -->
        <div class="stat-strip mb-4">
            <div class="stat-item">
                <span class="stat-label">Saldo Inicial do Período</span>
                <span class="stat-value font-tabular text-dark">
                    R$ <?php echo number_format($saldoInicial, 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">
                    Antes de <?php echo date('d/m/Y', strtotime($dataInicio)); ?>
                </span>
            </div>

            <div class="stat-item">
                <span class="stat-label">Total Recarregado</span>
                <span class="stat-value font-tabular text-success">
                    R$ <?php echo number_format($totalRecargas, 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">
                    Créditos Pix / Depósitos
                </span>
            </div>

            <div class="stat-item">
                <span class="stat-label">Total Gasto no Período</span>
                <span class="stat-value font-tabular text-danger">
                    R$ <?php echo number_format($totalGasto, 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">
                    <?php echo $totalLanches; ?> lanche(s) consumido(s)
                </span>
            </div>

            <div class="stat-item">
                <span class="stat-label">Saldo Final Resultante</span>
                <span class="stat-value font-tabular text-primary">
                    R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">
                    Posição contábil hoje
                </span>
            </div>
        </div>

        <!-- Tabela Analítica Oficial com Saldo Progressivo Linha a Linha -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-0">Extrato Analítico Linha a Linha</h2>
                    <span class="text-secondary text-microcopy">
                        Acompanhe cada débito de lanche e recarga com a posição de saldo resultante.
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
                        <!-- Linha Inicial de Saldo -->
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
                                    Nenhuma compra ou recarga registrada para este aluno no período especificado.
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
                                Saldo Final: R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
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
        const diaSemana = hoje.getDay();
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
</script>
