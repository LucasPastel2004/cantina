<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela 6.3: Lançamento Manual de Perdas de Mercadorias
 * 
 * Conforme docs/FSD.md (RN-15 e Módulo 6) e docs/DESIGN.md
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
            <h1 class="h5 fw-bold text-slate-900 mb-0">Registro Operacional de Perdas & Descarte</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/movimentacoes?tipo=saida_perda" class="btn btn-enterprise btn-primary btn-sm">
                Histórico Completo de Perdas
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-primary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Coluna Esquerda: Formulário de Registro de Perda -->
        <div class="col-12 col-lg-5">
            <div class="card-enterprise p-3">
                <div class="border-bottom pb-2 mb-3">
                    <h2 class="h6 fw-bold text-slate-900 mb-0">Nova Baixa de Mercadoria</h2>
                    <span class="text-muted" style="font-size: 12px;">Conforme RN-15 do FSD, toda perda exige justificativa e gera auditoria técnica imediata.</span>
                </div>

                <form method="POST" action="<?php echo $baseUrl; ?>/estoque/perdas/salvar" id="formPerda" onsubmit="return validarEnvioPerda()">
                    <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">

                    <!-- Seleção do Produto -->
                    <div class="mb-3">
                        <label for="selectProduto" class="form-label small fw-semibold text-muted">Produto Avariado / Descartado: *</label>
                        <select name="produto_id" id="selectProduto" class="form-select form-select-sm" required onchange="atualizarEstoqueDisponivel(this)">
                            <option value="">Selecione o produto...</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-estoque="<?php echo (int)$p['estoque_atual']; ?>"
                                        data-preco="<?php echo (float)$p['preco_venda']; ?>"
                                        <?php echo ((int)$produtoPreSel === (int)$p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['codigo_interno'] . ' — ' . $p['descricao'] . ' (Estoque: ' . (int)$p['estoque_atual'] . ' un)', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="infoSaldoAtual" class="form-text small mt-1 text-slate-700 font-tabular d-none">
                            Saldo físico atual no sistema: <strong id="lblSaldoFisico">0</strong> unidades.
                        </div>
                    </div>

                    <!-- Quantidade Baixada -->
                    <div class="mb-3">
                        <label for="inputQuantidade" class="form-label small fw-semibold text-muted">Quantidade a Baixar (unidades): *</label>
                        <input type="number" name="quantidade" id="inputQuantidade" class="form-control form-control-sm font-tabular" min="1" step="1" required placeholder="Ex: 2">
                        <div class="form-text" style="font-size: 11px;">A quantidade não pode ultrapassar o saldo físico existente do produto.</div>
                    </div>

                    <!-- Motivo Padronizado Obrigatório (RN-15) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Motivo Padronizado da Perda: *</label>
                        <div class="d-flex flex-column gap-1 p-2 rounded border bg-light">
                            <?php foreach ($motivosPerda as $kMotivo => $lblMotivo): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="motivo_perda" id="motivo_<?php echo $kMotivo; ?>" value="<?php echo $kMotivo; ?>" required>
                                    <label class="form-check-label small text-slate-800" for="motivo_<?php echo $kMotivo; ?>">
                                        <?php echo htmlspecialchars($lblMotivo, ENT_QUOTES, 'UTF-8'); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Justificativa Obrigatória -->
                    <div class="mb-3">
                        <label for="textareaJustificativa" class="form-label small fw-semibold text-muted">Justificativa Operacional: *</label>
                        <textarea name="justificativa" id="textareaJustificativa" rows="3" class="form-control form-control-sm" required minlength="5" placeholder="Descreva o ocorrido (ex: Pacote avariado no descarregamento, produto caiu no chão durante o intervalo, data de validade vencida)..."></textarea>
                        <div class="form-text" style="font-size: 11px;">Mínimo de 5 caracteres. Ficará registrado permanentemente no log de auditoria.</div>
                    </div>

                    <div class="pt-2 border-top d-flex justify-content-end gap-2">
                        <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">Cancelar</a>
                        <button type="submit" class="btn btn-enterprise btn-danger btn-sm d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                            Confirmar Baixa de Perda
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Coluna Direita: Histórico Recente de Perdas e Diretrizes -->
        <div class="col-12 col-lg-7">
            <!-- Diretrizes de Auditoria e Fechamento -->
            <div class="card-enterprise p-3 mb-3 bg-light border-0" style="border-left: 3px solid #b91c1c !important;">
                <span class="small fw-bold text-danger d-block mb-1">Diretriz Operacional — Prevenção de Desperdícios & Auditoria</span>
                <p class="text-secondary small mb-0" style="line-height: 1.45;">
                    As baixas de perda afetam imediatamente o inventário físico da cantina e são confrontadas nos relatórios gerenciais e na apuração de quebras e sobras de caixa. Lance as perdas no exato momento em que forem identificadas (avarias, vencimentos ou sobras não consumidas de produção).
                </p>
            </div>

            <!-- Tabela de Últimas Perdas Registradas -->
            <div class="card-enterprise">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                    <span class="card-enterprise-title">Últimas Perdas Registradas</span>
                    <span class="text-muted small">Mostrando até 20 registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-enterprise table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Data/Hora</th>
                                <th>Produto</th>
                                <th style="width: 80px;" class="text-end">Qtd</th>
                                <th style="width: 150px;">Motivo</th>
                                <th>Justificativa</th>
                                <th style="width: 110px;">Operador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimasPerdas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <em>Não há nada para mostrar aqui</em>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ultimasPerdas as $lp): ?>
                                    <tr>
                                        <td class="font-tabular text-muted small">
                                            <?php echo date('d/m/Y H:i', strtotime($lp['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-slate-900 small d-block"><?php echo htmlspecialchars($lp['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <code class="code-tag" style="font-size: 10px;"><?php echo htmlspecialchars($lp['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></code>
                                        </td>
                                        <td class="text-end font-tabular text-danger fw-bold">
                                            -<?php echo (int)$lp['quantidade']; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-soft text-danger fw-normal" style="font-size: 11px;">
                                                <?php echo htmlspecialchars($motivosPerda[$lp['motivo_perda']] ?? $lp['motivo_perda'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-secondary small">
                                                <?php echo htmlspecialchars($lp['justificativa'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="small text-slate-700"><?php echo htmlspecialchars($lp['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
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
</div>

<script>
function atualizarEstoqueDisponivel(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const infoBox = document.getElementById('infoSaldoAtual');
    const lblSaldo = document.getElementById('lblSaldoFisico');
    const inputQtd = document.getElementById('inputQuantidade');

    if (!opt || !opt.value) {
        infoBox.classList.add('d-none');
        inputQtd.removeAttribute('max');
        return;
    }

    const estoque = parseInt(opt.getAttribute('data-estoque'), 10) || 0;
    lblSaldo.textContent = estoque;
    infoBox.classList.remove('d-none');
    inputQtd.setAttribute('max', estoque);
}

function validarEnvioPerda() {
    const selectEl = document.getElementById('selectProduto');
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt || !opt.value) {
        alert('Selecione o produto.');
        return false;
    }

    const estoque = parseInt(opt.getAttribute('data-estoque'), 10) || 0;
    const inputQtd = document.getElementById('inputQuantidade');
    const qtd = parseInt(inputQtd.value, 10) || 0;

    if (qtd <= 0) {
        alert('A quantidade a baixar deve ser maior que zero.');
        inputQtd.focus();
        return false;
    }

    if (qtd > estoque) {
        alert('A quantidade informada (' + qtd + ' un) excede o saldo físico atual em estoque (' + estoque + ' un).');
        inputQtd.focus();
        return false;
    }

    const justif = document.getElementById('textareaJustificativa').value.trim();
    if (justif.length < 5) {
        alert('A justificativa deve conter no mínimo 5 caracteres.');
        document.getElementById('textareaJustificativa').focus();
        return false;
    }

    return confirm('Confirmar o registro da perda de ' + qtd + ' un deste produto? Esta ação debitará o estoque e gravará registro de auditoria.');
}

// Dispara no carregamento se houver pré-seleção
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('selectProduto');
    if (sel && sel.value) {
        atualizarEstoqueDisponivel(sel);
    }
});
</script>
