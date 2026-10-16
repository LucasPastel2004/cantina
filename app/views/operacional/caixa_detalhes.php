<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Extrato Analítico da Sessão de Caixa
 * 
 * Conforme docs/FSD.md (Área 4, Área 6, Módulo 7) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$sessao = $sessao ?? [];
$totais = $totais ?? [];
$vendas = $vendas ?? [];
$recargas = $recargas ?? [];

$id = (int)($sessao['id'] ?? 0);
$turno = ucfirst($sessao['turno'] ?? 'matutino');
$operador = $sessao['operador_nome'] ?? 'Operador';
$abertoEm = !empty($sessao['aberto_em']) ? date('d/m/Y H:i', strtotime($sessao['aberto_em'])) : '-';
$fechadoEm = !empty($sessao['fechado_em']) ? date('d/m/Y H:i', strtotime($sessao['fechado_em'])) : 'Em aberto';
$isFechado = (($sessao['status'] ?? '') === 'fechado');
$diferenca = ($sessao['diferenca_quebra_sobra'] !== null) ? (float)$sessao['diferenca_quebra_sobra'] : null;

$fundo = (float)($totais['fundo_troco_inicial'] ?? 0.0);
$avulso = (float)($totais['total_dinheiro_avulso'] ?? 0.0);
$recargasTotal = (float)($totais['total_dinheiro_recargas'] ?? 0.0);
$pix = (float)($totais['total_pix_balcao'] ?? 0.0);
$esperadoGaveta = (float)($totais['total_esperado_gaveta'] ?? 0.0);
$totalFaturado = (float)($totais['total_geral_faturado'] ?? 0.0);
?>

<!-- Cabeçalho e Ações -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Extrato Analítico — Sessão #<?php echo $id; ?></h1>
            <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600;">
                Turno <?php echo $turno; ?>
            </span>
            <?php if ($isFechado): ?>
                <span class="status-indicator status-inactive"><span class="bullet">●</span> Fechado</span>
            <?php else: ?>
                <span class="status-indicator status-active"><span class="bullet">●</span> Aberto</span>
            <?php endif; ?>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Aberto por <strong><?php echo htmlspecialchars($operador, ENT_QUOTES, 'UTF-8'); ?></strong> em <?php echo $abertoEm; ?> 
            <?php if ($isFechado): ?> • Fechado em <?php echo $fechadoEm; ?><?php endif; ?>
        </p>
    </div>

    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo $id; ?>" target="_blank" class="btn btn-enterprise btn-secondary btn-sm">
            Imprimir / Salvar PDF (A4)
        </a>
        <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo strtolower($sessao['turno'] ?? 'matutino'); ?>" class="btn btn-enterprise btn-secondary btn-sm">
            ← Voltar ao Caixa
        </a>
    </div>
</div>

<!-- Stat-Strip Executiva da Sessão -->
<div class="stat-strip mb-4">
    <div class="stat-strip-item">
        <span class="stat-strip-label">Fundo de Troco</span>
        <span class="stat-strip-value font-tabular">
            R$ <?php echo number_format($fundo, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Vendas Dinheiro</span>
        <span class="stat-strip-value font-tabular text-success">
            + R$ <?php echo number_format($avulso, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Recargas Dinheiro</span>
        <span class="stat-strip-value font-tabular text-success">
            + R$ <?php echo number_format($recargasTotal, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Esperado na Gaveta</span>
        <span class="stat-strip-value font-tabular fw-bold" style="color: #0f172a;">
            R$ <?php echo number_format($esperadoGaveta, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Pix no Balcão</span>
        <span class="stat-strip-value font-tabular text-primary">
            R$ <?php echo number_format($pix, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item" style="border-right: none;">
        <span class="stat-strip-label">Diferença / Quebra</span>
        <span class="stat-strip-value font-tabular">
            <?php if ($diferenca === null): ?>
                <span class="text-muted">Em Aberto</span>
            <?php elseif ($diferenca == 0.0): ?>
                <span class="text-success fw-bold">Exato (R$ 0,00)</span>
            <?php elseif ($diferenca > 0): ?>
                <span class="text-success fw-bold">+ R$ <?php echo number_format($diferenca, 2, ',', '.'); ?> (Sobra)</span>
            <?php else: ?>
                <span class="text-danger fw-bold">- R$ <?php echo number_format(abs($diferenca), 2, ',', '.'); ?> (Quebra)</span>
            <?php endif; ?>
        </span>
    </div>
</div>

<div class="row g-4">
    <!-- Tabela 1: Vendas Realizadas na Sessão -->
    <div class="col-12 col-lg-7">
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold text-dark">Vendas do Balcão Realizadas na Sessão (<?php echo count($vendas); ?>)</span>
                <span class="code-tag">PDV</span>
            </div>
            <div class="card-enterprise-body p-0">
                <div class="table-responsive">
                    <table class="table table-enterprise table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Venda</th>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Pagamento</th>
                                <th class="text-end">Valor Total</th>
                                <th class="text-center" style="width: 70px;">Cupom</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vendas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Nenhuma venda de balcão registrada nesta sessão.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vendas as $v): ?>
                                    <tr>
                                        <td class="font-tabular font-semibold">#<?php echo $v['id']; ?></td>
                                        <td class="font-tabular font-11 text-secondary"><?php echo date('H:i:s', strtotime($v['created_at'])); ?></td>
                                        <td class="font-12">
                                            <?php if (!empty($v['colaborador_nome'])): ?>
                                                <span class="badge bg-light text-dark border font-11">Colab: <?php echo htmlspecialchars($v['colaborador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php elseif (!empty($v['aluno_nome'])): ?>
                                                Aluno: <?php echo htmlspecialchars($v['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Consumidor Avulso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($v['forma_pagamento'] === 'dinheiro'): ?>
                                                <span class="badge bg-white text-success border border-success" style="font-size: 10px;">Dinheiro</span>
                                            <?php elseif ($v['forma_pagamento'] === 'pix_balcao'): ?>
                                                <span class="badge bg-white text-primary border border-primary" style="font-size: 10px;">Pix</span>
                                            <?php else: ?>
                                                <span class="badge bg-white text-dark border" style="font-size: 10px;">Folha</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end font-tabular font-semibold">
                                            R$ <?php echo number_format((float)$v['valor_total'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo $baseUrl; ?>/operacional/pdv/comprovante/<?php echo $v['id']; ?>" target="_blank" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;">
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela 2: Recargas em Dinheiro Creditadas -->
    <div class="col-12 col-lg-5">
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold text-dark">Recargas Presenciais em Dinheiro (<?php echo count($recargas); ?>)</span>
                <span class="code-tag">Carteira Familiar</span>
            </div>
            <div class="card-enterprise-body p-0">
                <div class="table-responsive">
                    <table class="table table-enterprise table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Horário</th>
                                <th>Responsável</th>
                                <th class="text-end">Valor Creditado</th>
                                <th class="text-center" style="width: 70px;">Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recargas)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Nenhuma recarga presencial em dinheiro vinculada a esta sessão.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recargas as $r): ?>
                                    <tr>
                                        <td class="font-tabular font-11 text-secondary"><?php echo date('H:i:s', strtotime($r['created_at'])); ?></td>
                                        <td class="font-12">
                                            <div class="font-semibold text-dark"><?php echo htmlspecialchars($r['responsavel_nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="text-muted font-11 font-tabular"><?php echo htmlspecialchars($r['responsavel_cpf'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td class="text-end font-tabular font-semibold text-success">
                                            + R$ <?php echo number_format((float)$r['valor'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?php echo $baseUrl; ?>/operacional/caixa/comprovante-recarga/<?php echo $r['id']; ?>" target="_blank" class="btn btn-enterprise btn-secondary btn-sm py-0 px-2" style="font-size: 11px;">
                                                80mm
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!empty($sessao['observacoes'])): ?>
            <div class="card-enterprise">
                <div class="card-enterprise-header">
                    <span class="font-weight-bold text-dark">Observações e Justificativas Registradas</span>
                </div>
                <div class="card-enterprise-body p-3 font-13 text-secondary" style="line-height: 1.5;">
                    <?php echo nl2br(htmlspecialchars($sessao['observacoes'], ENT_QUOTES, 'UTF-8')); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
