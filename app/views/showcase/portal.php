<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View de Homologação: Portal da Família
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
?>

<div class="row g-4 mb-4">
    <!-- Card 1: Resumo da Carteira Familiar -->
    <div class="col-12 col-md-5">
        <div class="bento-card">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary-label text-uppercase">Carteira Familiar</span>
                    <span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Saldo Regular</span>
                </div>
                <div class="mb-2">
                    <span class="text-microcopy d-block">Saldo Livre para Consumo</span>
                    <div class="value-highlight font-tabular text-success" style="font-size: 26px;">
                        R$ <?php echo number_format($saldoAtual ?? 0, 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between text-microcopy pt-2 border-top">
                    <span>Comprometido em Agendamentos:</span>
                    <strong class="font-tabular">R$ <?php echo number_format($saldoReservado ?? 0, 2, ',', '.'); ?></strong>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <a href="<?php echo $baseUrl; ?>/portal/recarga" class="btn btn-enterprise btn-primary w-100">
                    Recarregar Saldo
                </a>
                <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-secondary w-100">
                    Ver Extrato
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Dependentes Vinculados -->
    <div class="col-12 col-md-7">
        <div class="bento-card">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary-label text-uppercase">Estudantes Dependentes</span>
                    <span class="text-microcopy font-tabular"><?php echo count($dependentes ?? []); ?> Alunos Cadastrados</span>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($dependentes ?? [] as $dep): ?>
                        <div class="list-group-item px-0 py-2 border-bottom d-flex align-items-center justify-content-between bg-transparent">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-primary font-tabular" style="width: 36px; height: 36px; background-color: var(--brand-light-blue); font-size: 13px;">
                                    <?php echo mb_substr($dep['nome'], 0, 1); ?>
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy">
                                        <?php echo htmlspecialchars($dep['turma'], ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turno <?php echo htmlspecialchars($dep['turno'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <?php if (!empty($dep['alergias'])): ?>
                                    <?php foreach ($dep['alergias'] as $alergia): ?>
                                        <span class="fw-bold" style="font-size: 13px; color: #b45309;" title="Restrição alimentar declarada">
                                            <?php echo htmlspecialchars($alergia, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-microcopy text-muted">Sem restrições</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mt-3 text-end">
                <a href="<?php echo $baseUrl; ?>/portal/alergias" class="text-decoration-none" style="font-size: 12px; color: var(--primary-blue); font-weight: 500;">
                    Atualizar declaração de alergias &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Seção de Pedidos Agendados -->
<div class="card-enterprise">
    <div class="card-enterprise-header">
        <div class="d-flex align-items-center gap-2">
            <span class="h2-title mb-0">Próximos Lanches Agendados</span>
            <span class="text-microcopy">&bull; Trava de corte: 08:00 (Matutino) / 13:30 (Vespertino)</span>
        </div>
        <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary btn-sm">
            Novo Agendamento
        </a>
    </div>

    <div class="table-responsive">
        <table class="table-data-grid">
            <thead>
                <tr>
                    <th class="cell-left" style="width: 15%;">Data de Consumo</th>
                    <th class="cell-left" style="width: 25%;">Estudante</th>
                    <th class="cell-left" style="width: 35%;">Lanche Encomendado</th>
                    <th class="cell-right" style="width: 12%;">Valor</th>
                    <th class="cell-center" style="width: 13%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidosSemana ?? [] as $ped): ?>
                    <tr>
                        <td class="cell-left font-tabular"><?php echo htmlspecialchars($ped['data'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-left fw-semibold"><?php echo htmlspecialchars($ped['aluno'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-left"><?php echo htmlspecialchars($ped['item'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-right font-tabular">R$ <?php echo number_format($ped['valor'], 2, ',', '.'); ?></td>
                        <td class="cell-center">
                            <span class="status-indicator status-success">
                                <span class="bullet" aria-hidden="true">●</span> <?php echo htmlspecialchars($ped['status'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
