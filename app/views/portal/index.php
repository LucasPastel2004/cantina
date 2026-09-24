<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Início do Portal da Família
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$usuario = $usuario ?? [];
$saldoAtual = $saldoAtual ?? 0.00;
$dependentes = $dependentes ?? [];
$bloqueadoMed = $bloqueadoMed ?? false;
?>

<!-- Alerta Especial de Bloqueio MED (Se Houver) -->
<?php if ($bloqueadoMed): ?>
    <div class="alert alert-danger py-3 px-4 mb-4 border-left-danger" style="border-left: 4px solid #b91c1c; background-color: #fef2f2; color: #991b1b;">
        <h6 class="font-weight-bold mb-1">Atenção: Conta Temporariamente Suspensa (Notificação Bancária)</h6>
        <p class="mb-0 text-microcopy" style="font-size: 13px;">
            Identificamos uma solicitação de estorno Pix em sua carteira familiar. Novos agendamentos estão temporariamente bloqueados. Favor procurar a administração da cantina para regularização.
        </p>
    </div>
<?php endif; ?>

<!-- Boas-vindas e Resumo Rápido -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Olá, <?php echo htmlspecialchars($usuario['nome'] ?? 'Família', ENT_QUOTES, 'UTF-8'); ?>!</h1>
        <p class="text-secondary text-microcopy mb-0">Acompanhe a alimentação escolar e o saldo dos seus dependentes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary">
            + Agendar Lanche
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Card de Saldo e Carteira -->
    <div class="col-12 col-md-5">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Carteira Familiar</span>
                <span class="status-indicator status-success font-microcopy"><span class="bullet">●</span> Carteira Ativa</span>
            </div>
            <div class="card-enterprise-body">
                <span class="text-microcopy text-muted d-block mb-1">Saldo Disponível</span>
                <div class="font-tabular font-weight-bold mb-3" style="font-size: 28px; color: #0f172a;">
                    R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                </div>
                <p class="text-microcopy text-secondary mb-4">
                    Saldo unificado e compartilhado automaticamente entre todos os seus dependentes.
                </p>
                <div class="d-flex gap-2">
                    <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-secondary flex-fill text-center">
                        Recarregar Saldo
                    </a>
                    <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-secondary flex-fill text-center">
                        Ver Extrato
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Dependentes Vinculados -->
    <div class="col-12 col-md-7">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <h2 class="h6 font-weight-bold text-dark mb-0">Dependentes Cadastrados</h2>
                <span class="text-microcopy text-muted font-tabular"><?php echo count($dependentes); ?> aluno(s)</span>
            </div>
            <div class="card-enterprise-body">
                <?php if (empty($dependentes)): ?>
                    <div class="text-center py-4 text-muted text-microcopy">
                        <p class="mb-0">Nenhum dependente vinculado à sua conta no momento.</p>
                        <small>Seus dependentes serão vinculados pela secretaria escolar.</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dependentes as $dep): ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="font-weight-semibold text-dark"><?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy text-muted font-tabular">
                                        Matrícula: <?php echo htmlspecialchars($dep['matricula'], ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turma: <?php echo htmlspecialchars($dep['nome_turma'] ?? 'A definir', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turno: <?php echo ucfirst($dep['turno'] ?? 'matutino'); ?>
                                    </div>
                                </div>
                                <div>
                                    <a href="<?php echo $baseUrl; ?>/portal/alergias?aluno=<?php echo $dep['id']; ?>" class="btn btn-enterprise btn-secondary btn-sm">
                                        Restrições
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
