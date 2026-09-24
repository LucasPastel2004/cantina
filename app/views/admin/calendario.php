<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Calendário Escolar e Dias Letivos
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$diasSemana = $diasSemana ?? [];
$hoje = $hoje ?? date('Y-m-d');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Calendário Escolar</h1>
        <p class="text-secondary text-microcopy mb-0">Controle de dias letivos, feriados e períodos de funcionamento da cantina.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="btn btn-enterprise btn-secondary btn-sm">
            Semana Letiva Atual
        </span>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Dias da Semana Atual</span>
                <span class="status-indicator status-active"><span class="bullet">●</span> Em Funcionamento</span>
            </div>
            <div class="card-enterprise-body p-0">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-left">Data</th>
                            <th class="cell-left">Dia da Semana</th>
                            <th class="cell-center">Status</th>
                            <th class="cell-right">Operação Cantina</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($diasSemana as $dia): ?>
                            <?php 
                            $isHoje = ($dia['data'] === $hoje);
                            ?>
                            <tr class="<?php echo $isHoje ? 'table-active' : ''; ?>">
                                <td class="cell-left font-tabular font-weight-semibold">
                                    <?php echo date('d/m/Y', strtotime($dia['data'])); ?>
                                    <?php if ($isHoje): ?>
                                        <span class="badge bg-primary text-white ms-1" style="font-size: 10px;">Hoje</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-left text-dark">
                                    <?php echo htmlspecialchars($dia['dia_semana_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-center">
                                    <?php if (!empty($dia['is_letivo'])): ?>
                                        <span class="status-indicator status-active"><span class="bullet">●</span> Dia Letivo</span>
                                    <?php else: ?>
                                        <span class="status-indicator text-muted"><span class="bullet">○</span> Não Letivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right">
                                    <?php if (!empty($dia['is_letivo'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                            Matutino & Vespertino
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted text-microcopy">Fechado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Regras de Abertura e Corte</span>
            </div>
            <div class="card-enterprise-body">
                <div class="mb-3">
                    <span class="text-microcopy text-muted d-block">Liberação do Cardápio Semanal</span>
                    <strong class="text-dark">Sexta-feira às 18:00</strong>
                    <p class="text-microcopy text-secondary mt-1">Os pais podem agendar lanches para a semana seguinte a partir deste horário.</p>
                </div>

                <div class="mb-3">
                    <span class="text-microcopy text-muted d-block">Corte Turno Matutino</span>
                    <strong class="text-dark font-tabular">08:00</strong>
                    <p class="text-microcopy text-secondary mt-1">Encerramento pontual para pedidos e cancelamentos da manhã.</p>
                </div>

                <div>
                    <span class="text-microcopy text-muted d-block">Corte Turno Vespertino</span>
                    <strong class="text-dark font-tabular">13:30</strong>
                    <p class="text-microcopy text-secondary mt-1">Encerramento pontual para pedidos e cancelamentos da tarde.</p>
                </div>
            </div>
        </div>
    </div>
</div>
