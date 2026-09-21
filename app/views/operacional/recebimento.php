<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Conferência e Recebimento de Fornecedor
 * 
 * Conforme docs/FSD.md (Área 2, Tela 3.2, Fluxo 5) e docs/DESIGN.md (Anti-Slop Enterprise)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dataConsumo = $dataConsumo ?? date('Y-m-d');
$turnoConsumo = $turnoConsumo ?? 'matutino';
$dadosCorte = $dadosCorte ?? [];
$itens = $dadosCorte['itens'] ?? [];
$totalPecas = (int)($dadosCorte['total_pecas'] ?? 0);
$statusRecebimento = $statusRecebimento ?? null;
$csrf_token = $csrf_token ?? '';

$dataFmt = date('d/m/Y', strtotime($dataConsumo));
$turnoNome = ($turnoConsumo === 'matutino') ? 'Matutino' : 'Vespertino';
$jaRecebido = !empty($statusRecebimento['recebido']);
?>

<!-- Alertas Flash do Sistema -->
<?php require __DIR__ . '/../partials/alert.php'; ?>

<!-- Topo da Página com Título e Ações Rápidas -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Conferência e Recebimento de Fornecedor</h1>
            <?php if ($jaRecebido): ?>
                <span class="status-indicator status-active" style="font-size: 12px;">
                    <span class="bullet">●</span> Recebimento Confirmado
                </span>
            <?php else: ?>
                <span class="badge bg-amber text-dark" style="background-color: #fef3c7; color: #92400e; font-size: 11px; font-weight: 600;">
                    Aguardando Conferência
                </span>
            <?php endif; ?>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Conferência da remessa física entregue na cantina, liquidação da demanda de pedidos e entrada no estoque.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo $baseUrl; ?>/operacional/corte?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           class="btn btn-enterprise btn-secondary">
            &larr; Painel de Corte
        </a>
    </div>
</div>

<!-- Barra de Filtros Operacionais (Data e Turno) -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/operacional/recebimento" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="inputDataRec" class="form-label text-microcopy font-weight-bold text-secondary mb-1 d-block">DATA:</label>
                <input type="date" id="inputDataRec" name="data" class="form-control form-control-sm font-tabular" 
                       style="width: 155px;" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>"
                       onchange="this.form.submit()">
            </div>
            <div class="col-auto">
                <label for="selectTurnoRec" class="form-label text-microcopy font-weight-bold text-secondary mb-1 d-block">TURNO:</label>
                <select id="selectTurnoRec" name="turno" class="form-select form-select-sm" style="width: 155px;"
                        onchange="this.form.submit()">
                    <option value="matutino" <?php echo ($turnoConsumo === 'matutino') ? 'selected' : ''; ?>>Matutino (Manhã)</option>
                    <option value="vespertino" <?php echo ($turnoConsumo === 'vespertino') ? 'selected' : ''; ?>>Vespertino (Tarde)</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm" style="height: 34px;">
                    Carregar Itens
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
// Cálculos de conferência prévia caso a remessa já tenha sido recebida
$itensEntreguesMap = $statusRecebimento['itens_entregues'] ?? [];
$totalEntregueAcum = 0;
$totalExcedenteAcum = 0;
$totalFaltaAcum = 0;
if ($jaRecebido) {
    foreach ($itens as $it) {
        $qEsp = (int)($it['quantidade_total'] ?? 0);
        $pId = (int)$it['produto_id'];
        $qEnt = $itensEntreguesMap[$pId] ?? $qEsp;
        $totalEntregueAcum += $qEnt;
        $d = $qEnt - $qEsp;
        if ($d > 0) {
            $totalExcedenteAcum += $d;
        } elseif ($d < 0) {
            $totalFaltaAcum += abs($d);
        }
    }
}
?>

<?php if ($jaRecebido): ?>
    <!-- Aviso Informativo de Recebimento já Realizado -->
    <div class="alert alert-success d-flex align-items-center p-3 mb-4" style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 6px;">
        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" class="me-3 flex-shrink-0">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
        </svg>
        <div>
            <div class="font-weight-bold" style="font-size: 13px;">Recebimento Físico Concluído</div>
            <div class="text-microcopy" style="color: #15803d;">
                A entrega da remessa do turno <strong><?php echo $turnoNome; ?></strong> de <strong><?php echo $dataFmt; ?></strong> 
                foi recebida e conferida por <strong><?php echo htmlspecialchars($statusRecebimento['operador_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></strong> 
                em <strong><?php echo htmlspecialchars($statusRecebimento['data_recebimento_fmt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>.
                As demandas de tickets foram liquidadas e o estoque atualizado.
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Formulário de Conferência Física de Entrega -->
<form method="POST" action="<?php echo $baseUrl; ?>/operacional/recebimento/confirmar" id="formRecebimento">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="data_consumo" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="turno_consumo" value="<?php echo htmlspecialchars($turnoConsumo, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="card-enterprise">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <div>
                <span class="font-weight-bold text-dark">Conferência Quantitativa da Entrega</span>
                <span class="text-secondary text-microcopy ms-2">(Total Esperado no Corte: <strong class="font-tabular"><?php echo $totalPecas; ?></strong> peças)</span>
            </div>
            <?php if (!$jaRecebido && !empty($itens)): ?>
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="preencherTudoEsperado()">
                    Preencher com Valores Esperados
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table-enterprise align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 120px;" class="cell-center">Código</th>
                        <th>Produto / Salgado</th>
                        <th style="width: 180px;">Categoria</th>
                        <th style="width: 140px;" class="cell-right">Qtd Esperada</th>
                        <th style="width: 180px;" class="cell-center">Qtd Entregue</th>
                        <th style="width: 160px;" class="cell-right">Diferença / Sobra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($itens)): ?>
                        <tr>
                            <td colspan="6" class="p-0">
                                <?php
                                $emptyTitle = 'Não há nada para mostrar aqui';
                                $emptyDescription = 'Nenhum item demandado pelo corte para conferência neste turno e data.';
                                require __DIR__ . '/../partials/empty_state.php';
                                ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($itens as $idx => $item): ?>
                            <?php 
                            $qtdEsp = (int)($item['quantidade_total'] ?? 0);
                            $prodId = (int)$item['produto_id'];
                            $qtdEntregueEfetiva = $jaRecebido ? ($itensEntreguesMap[$prodId] ?? $qtdEsp) : $qtdEsp;
                            $diffEfetiva = $qtdEntregueEfetiva - $qtdEsp;
                            ?>
                            <tr>
                                <td class="cell-center">
                                    <code class="code-tag"><?php echo htmlspecialchars($item['codigo_interno'] ?? ('PRD-' . $prodId), ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($item['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <input type="hidden" name="itens[<?php echo $idx; ?>][produto_id]" value="<?php echo $prodId; ?>">
                                    <input type="hidden" name="itens[<?php echo $idx; ?>][qtd_esperada]" id="qtdEsp_<?php echo $idx; ?>" value="<?php echo $qtdEsp; ?>">
                                </td>
                                <td class="text-secondary">
                                    <?php echo htmlspecialchars($item['categoria_nome'] ?? 'Geral', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-right font-tabular font-weight-bold" style="font-size: 13px;">
                                    <?php echo $qtdEsp; ?> un
                                </td>
                                <td class="cell-center">
                                    <?php if ($jaRecebido): ?>
                                        <span class="font-tabular font-weight-bold" style="font-size: 14px; color: #166534;">
                                            <?php echo $qtdEntregueEfetiva; ?> un
                                        </span>
                                    <?php else: ?>
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <input type="number" 
                                                   name="itens[<?php echo $idx; ?>][qtd_entregue]" 
                                                   id="qtdEnt_<?php echo $idx; ?>" 
                                                   class="form-control form-control-sm font-tabular input-entregue text-center" 
                                                   style="width: 85px; font-weight: 600;" 
                                                   min="0" 
                                                   value="<?php echo $qtdEsp; ?>" 
                                                   oninput="recalcularLinha(<?php echo $idx; ?>)">
                                            <span class="text-microcopy text-secondary">un</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right font-tabular" id="diffCol_<?php echo $idx; ?>">
                                    <?php if ($jaRecebido): ?>
                                        <?php if ($diffEfetiva === 0): ?>
                                            <span class="text-muted">Exato (0)</span>
                                        <?php elseif ($diffEfetiva > 0): ?>
                                            <span class="font-weight-bold text-success">+<?php echo $diffEfetiva; ?> un (sobra)</span>
                                        <?php else: ?>
                                            <span class="font-weight-bold text-danger"><?php echo $diffEfetiva; ?> un (falta)</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Exato (0)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($itens)): ?>
                    <tfoot>
                        <tr style="background-color: #f8fafc; border-top: 2px solid #cbd5e1;">
                            <td colspan="3" class="text-end font-weight-bold text-dark">
                                TOTAIS CONSOLIDADOS:
                            </td>
                            <td class="cell-right font-tabular font-weight-bold text-dark" style="font-size: 14px; color: #0f172a;">
                                <?php echo $totalPecas; ?> un
                            </td>
                            <td class="cell-center font-tabular font-weight-bold text-dark" id="totalEntregueCol" style="font-size: 15px; color: #0f172a !important;">
                                <?php echo $jaRecebido ? $totalEntregueAcum : $totalPecas; ?> un
                            </td>
                            <td class="cell-right font-tabular font-weight-bold" id="totalExcedenteCol" style="font-size: 13px; color: <?php echo ($jaRecebido && $totalExcedenteAcum > 0) ? '#166534' : (($jaRecebido && $totalFaltaAcum > 0) ? '#b91c1c' : '#64748b'); ?>;">
                                <?php if ($jaRecebido): ?>
                                    <?php if ($totalExcedenteAcum > 0): ?>
                                        +<?php echo $totalExcedenteAcum; ?> un (excedente balcão)
                                    <?php elseif ($totalFaltaAcum > 0): ?>
                                        -<?php echo $totalFaltaAcum; ?> un (falta)
                                    <?php else: ?>
                                        0 sobra
                                    <?php endif; ?>
                                <?php else: ?>
                                    0 sobra
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <?php if (!$jaRecebido && !empty($itens)): ?>
            <div class="card-enterprise-body p-3 border-top">
                <div class="mb-3">
                    <label for="observacaoRecebimento" class="form-label text-microcopy font-weight-bold text-secondary">
                        OBSERVAÇÕES DA ENTREGA (OPCIONAL):
                    </label>
                    <input type="text" id="observacaoRecebimento" name="observacao" class="form-control form-control-sm" 
                           placeholder="Ex: Entregador João confirmou que 2 coxinhas adicionais foram enviadas como sobra de cortesia.">
                </div>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-microcopy text-muted">
                        Ao confirmar, o sistema dará baixa na demanda de tickets do turno e disponibilizará eventuais sobras para o balcão.
                    </div>
                    <button type="submit" class="btn btn-enterprise btn-success" id="btnConfirmarRecebimento">
                        Confirmar Recebimento e Dar Entrada
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</form>

<!-- Script de Cálculo Dinâmico de Diferenças e Sobras -->
<script>
function recalcularLinha(idx) {
    const inputEsp = document.getElementById('qtdEsp_' + idx);
    const inputEnt = document.getElementById('qtdEnt_' + idx);
    const diffCol = document.getElementById('diffCol_' + idx);

    if (!inputEsp || !inputEnt || !diffCol) return;

    const esp = parseInt(inputEsp.value, 10) || 0;
    const ent = parseInt(inputEnt.value, 10) || 0;
    const diff = ent - esp;

    if (diff === 0) {
        diffCol.innerHTML = '<span class="text-muted">Exato (0)</span>';
    } else if (diff > 0) {
        diffCol.innerHTML = '<span class="font-weight-bold text-success">+' + diff + ' un (sobra)</span>';
    } else {
        diffCol.innerHTML = '<span class="font-weight-bold text-danger">' + diff + ' un (falta)</span>';
    }

    recalcularTotais();
}

function recalcularTotais() {
    const inputs = document.querySelectorAll('.input-entregue');
    let totalEnt = 0;
    let totalEsp = <?php echo (int)$totalPecas; ?>;

    inputs.forEach(input => {
        totalEnt += (parseInt(input.value, 10) || 0);
    });

    const totalEntCol = document.getElementById('totalEntregueCol');
    const totalExcCol = document.getElementById('totalExcedenteCol');

    if (totalEntCol) {
        totalEntCol.textContent = totalEnt + ' un';
    }

    if (totalExcCol) {
        const diffTotal = totalEnt - totalEsp;
        if (diffTotal === 0) {
            totalExcCol.textContent = '0 sobra';
            totalExcCol.style.color = '#64748b';
        } else if (diffTotal > 0) {
            totalExcCol.textContent = '+' + diffTotal + ' un (excedente balcão)';
            totalExcCol.style.color = '#166534';
        } else {
            totalExcCol.textContent = diffTotal + ' un (falta)';
            totalExcCol.style.color = '#b91c1c';
        }
    }
}

function preencherTudoEsperado() {
    const inputs = document.querySelectorAll('.input-entregue');
    inputs.forEach((input, idx) => {
        const inputEsp = document.getElementById('qtdEsp_' + idx);
        if (inputEsp) {
            input.value = inputEsp.value;
            recalcularLinha(idx);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('.input-entregue');
    inputs.forEach((_, idx) => recalcularLinha(idx));
});
</script>
