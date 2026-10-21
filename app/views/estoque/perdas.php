<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela 6.3: Lançamento Manual de Perdas de Mercadorias
 * 
 * Conforme docs/FSD.md (RN-15 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$produtos = $produtos ?? [];
$produtoPreSel = $produtoPreSel ?? null;
$motivosPerda = !empty($motivosPerda) ? $motivosPerda : \App\Models\Estoque::MOTIVOS_PERDA;
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
            <a href="<?php echo $baseUrl; ?>/relatorios/estoque-filtros?tipo_movimentacao=saida_perda" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5" title="Abrir Emissão de Relatório de Movimentações e Baixas">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                    <path d="M4.5 10.5A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/>
                </svg>
                <span>Relatório de Baixas de Mercadorias</span>
            </a>
        </div>
    </div>

    <!-- Diretriz Operacional — Prevenção de Desperdícios & Auditoria (Preenche toda a largura) -->
    <div class="card-enterprise p-3 mb-3 bg-light border-0 w-100" style="border-left: 4px solid #b91c1c !important;">
        <span class="small fw-bold text-danger d-block mb-1">Diretriz Operacional — Prevenção de Desperdícios & Auditoria</span>
        <p class="text-secondary small mb-0" style="line-height: 1.45;">
            As baixas de perda afetam imediatamente o inventário físico da cantina e são confrontadas nos relatórios gerenciais e na apuração de quebras e sobras de caixa. Lance as perdas no exato momento em que forem identificadas (avarias, vencimentos ou produtos não consumidos).
        </p>
    </div>

    <!-- Nova Baixa de Mercadoria (Preenche toda a largura) -->
    <div class="card-enterprise p-4 w-100 mb-4">
        <div class="border-bottom pb-2 mb-3">
            <h2 class="h6 fw-bold text-slate-900 mb-0">Nova Baixa de Mercadoria</h2>
            <span class="text-muted" style="font-size: 12px;">Conforme RN-15 do FSD, toda perda exige justificativa e gera auditoria técnica imediata.</span>
        </div>

        <form method="POST" action="<?php echo $baseUrl; ?>/estoque/perdas/salvar" id="formPerda" onsubmit="return validarEnvioPerda(event)">
            <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">

            <div class="row g-3">
                <!-- Seleção do Produto -->
                <div class="col-12 col-lg-8">
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
                <div class="col-12 col-lg-4">
                    <label for="inputQuantidade" class="form-label small fw-semibold text-muted">Quantidade a Baixar (unidades): *</label>
                    <input type="number" name="quantidade" id="inputQuantidade" class="form-control form-control-sm font-tabular" min="1" step="1" required placeholder="Ex: 2">
                    <div class="form-text" style="font-size: 11px;">A quantidade não pode ultrapassar o saldo físico existente.</div>
                </div>

                <!-- Motivo Padronizado Obrigatório (RN-15) -->
                <div class="col-12">
                    <label class="form-label small fw-semibold text-muted mb-1">Motivo Padronizado da Perda: *</label>
                    <div class="p-3 rounded border bg-light">
                        <div class="row g-2">
                            <?php foreach ($motivosPerda as $kMotivo => $lblMotivo): ?>
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <div class="form-check p-2 bg-white rounded border h-100 d-flex align-items-center gap-2">
                                        <input class="form-check-input ms-0 me-2 mt-0" type="radio" name="motivo_perda" id="motivo_<?php echo $kMotivo; ?>" value="<?php echo $kMotivo; ?>" required>
                                        <label class="form-check-label small text-slate-800 fw-medium cursor-pointer flex-fill mb-0" for="motivo_<?php echo $kMotivo; ?>">
                                            <?php echo htmlspecialchars($lblMotivo, ENT_QUOTES, 'UTF-8'); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Justificativa Obrigatória -->
                <div class="col-12">
                    <label for="textareaJustificativa" class="form-label small fw-semibold text-muted">Justificativa Operacional: *</label>
                    <textarea name="justificativa" id="textareaJustificativa" rows="3" class="form-control form-control-sm" required minlength="5" placeholder="Descreva o ocorrido (ex: Pacote avariado no descarregamento, produto caiu no chão durante o intervalo, data de validade vencida, produto sobrou de produção sem consumo)..."></textarea>
                    <div class="form-text" style="font-size: 11px;">Mínimo de 5 caracteres. Ficará registrado permanentemente no log de auditoria técnica.</div>
                </div>
            </div>

            <div class="pt-3 border-top mt-4 d-flex justify-content-end gap-2">
                <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">Cancelar</a>
                <button type="submit" class="btn btn-enterprise btn-danger btn-sm d-inline-flex align-items-center gap-1.5 px-3">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/></svg>
                    <span>Confirmar Baixa de Perda</span>
                </button>
            </div>
        </form>
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

function validarEnvioPerda(event) {
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

    if (event) {
        event.preventDefault();
    }
    const form = document.getElementById('formPerda');
    window.confirmarAcao({
        titulo: 'Registro de Perda de Estoque',
        mensagem: 'Confirmar o registro da perda de ' + qtd + ' un deste produto? Esta ação debitará o estoque e gravará registro de auditoria.',
        tipo: 'danger',
        textoConfirmar: 'Sim, Registrar Perda',
        textoCancelar: 'Cancelar'
    }).then(function(confirmado) {
        if (confirmado && form) {
            form.submit();
        }
    });
    return false;
}

// Dispara no carregamento se houver pré-seleção
document.addEventListener('DOMContentLoaded', function() {
    const sel = document.getElementById('selectProduto');
    if (sel && sel.value) {
        atualizarEstoqueDisponivel(sel);
    }
});
</script>
