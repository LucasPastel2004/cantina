<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela de Conferência Prévia de NF-e e Fatores de Conversão
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque/importar-xml" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Cancelar e Enviar Outro Arquivo
                </a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Conferência de Entrada — NF-e nº <?php echo htmlspecialchars($nfe['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/importar-xml/cancelar" class="btn btn-enterprise btn-secondary btn-sm" onclick="return confirm('Deseja descartar a importação desta nota fiscal?')">
                Cancelar Importação
            </a>
        </div>
    </div>

    <!-- Metadados da Nota Fiscal em Bento Grid -->
    <div class="card-enterprise p-3 mb-3">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Emitente / Fornecedor</span>
                <span class="fw-bold text-slate-900 d-block"><?php echo htmlspecialchars($nfe['razao_social_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-secondary small font-tabular">CNPJ: <?php echo htmlspecialchars($nfe['cnpj_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Número / Série</span>
                <span class="fw-semibold text-slate-900 font-tabular fs-6"><?php echo htmlspecialchars($nfe['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-muted small d-block">Série <?php echo htmlspecialchars($nfe['serie'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Data de Emissão</span>
                <span class="fw-semibold text-slate-900 font-tabular"><?php echo date('d/m/Y H:i', strtotime($nfe['data_emissao'])); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Valor Total</span>
                <span class="fw-bold text-slate-900 font-tabular fs-6">R$ <?php echo number_format($nfe['valor_total'], 2, ',', '.'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Itens no XML</span>
                <span class="fw-bold text-slate-900 font-tabular fs-6"><?php echo $nfe['total_itens']; ?> itens</span>
            </div>
            <div class="col-12 pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Chave de Acesso:</span>
                    <code class="code-tag font-tabular" style="font-size: 11px;"><?php echo htmlspecialchars($nfe['chave_nfe'], ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Mapeamento dos Itens -->
    <form method="POST" action="<?php echo $baseUrl; ?>/estoque/importar-xml/processar" id="formProcessarXml" onsubmit="return validarMapeamentoItens()">
        <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">

        <div class="card-enterprise mb-3">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="card-enterprise-title">Mapeamento de Mercadorias & Fator Multiplicador</span>
                <span class="text-muted small">Associe cada item a um produto da cantina e ajuste o fator de conversão de embalagem</span>
            </div>
            <div class="table-responsive">
                <table class="table table-enterprise table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 45px;" class="text-center">#</th>
                            <th style="width: 110px;">Cód. Fornecedor</th>
                            <th>Descrição na Nota Fiscal</th>
                            <th style="width: 70px;" class="text-center">Unid.</th>
                            <th style="width: 90px;" class="text-end">Qtd NF</th>
                            <th style="width: 100px;" class="text-end">Valor Un.</th>
                            <th style="width: 280px;">Produto Correspondente na Cantina *</th>
                            <th style="width: 120px;" class="text-center">Fator Conversão *</th>
                            <th style="width: 130px;" class="text-end">Qtd Entrada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nfe['itens'] as $idx => $item): ?>
                            <?php $numItem = $item['numero_item']; ?>
                            <tr>
                                <td class="text-center font-tabular text-muted small">
                                    <?php echo $numItem; ?>
                                </td>
                                <td>
                                    <code class="code-tag"><?php echo htmlspecialchars($item['codigo_produto_fornecedor'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <span class="fw-semibold text-slate-900 small d-block"><?php echo htmlspecialchars($item['descricao_fornecedor'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (!empty($item['ncm'])): ?>
                                        <span class="text-muted" style="font-size: 10px;">NCM: <?php echo htmlspecialchars($item['ncm'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center font-tabular small text-muted">
                                    <?php echo htmlspecialchars($item['unidade_fornecedor'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="text-end font-tabular fw-semibold">
                                    <?php echo number_format($item['quantidade_fornecedor'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-end font-tabular text-muted small">
                                    R$ <?php echo number_format($item['valor_unitario_fornecedor'], 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <select name="itens[<?php echo $numItem; ?>][produto_id]" 
                                            class="form-select form-select-sm select-produto-mapeado" 
                                            required 
                                            id="select_prod_<?php echo $numItem; ?>"
                                            style="font-size: 12px;">
                                        <option value="">Selecione o produto interno...</option>
                                        <?php foreach ($produtos as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" <?php echo ((int)$item['produto_id_sugerido'] === (int)$p['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['codigo_interno'] . ' — ' . $p['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" 
                                               name="itens[<?php echo $numItem; ?>][fator_conversao]" 
                                               class="form-control form-control-sm text-center font-tabular input-fator" 
                                               id="fator_<?php echo $numItem; ?>"
                                               min="1" 
                                               step="1" 
                                               value="<?php echo (int)$item['fator_conversao_sugerido']; ?>" 
                                               data-item="<?php echo $numItem; ?>"
                                               data-qtd-nf="<?php echo (float)$item['quantidade_fornecedor']; ?>"
                                               oninput="recalcularItem(<?php echo $numItem; ?>)"
                                               required>
                                        <span class="input-group-text text-muted" style="font-size: 11px;">x un</span>
                                    </div>
                                </td>
                                <td class="text-end font-tabular text-success fw-bold">
                                    +<span id="qtd_convertida_<?php echo $numItem; ?>"><?php echo (int)$item['quantidade_convertida']; ?></span> un
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="7" class="text-end fw-semibold text-muted small">
                                Total de Peças / Unidades Adicionadas ao Estoque:
                            </td>
                            <td colspan="2" class="text-end font-tabular fs-6 fw-bold text-success" id="lblTotalGeralConvertido">
                                0 un
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Barra Inferior de Ação -->
        <div class="d-flex justify-content-between align-items-center p-3 card-enterprise bg-light">
            <a href="<?php echo $baseUrl; ?>/estoque/importar-xml/cancelar" class="btn btn-enterprise btn-secondary btn-sm" onclick="return confirm('Deseja cancelar o processo de entrada?')">
                Cancelar e Descartar
            </a>
            <button type="submit" id="btnConfirmarEntrada" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-2">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/></svg>
                Confirmar Entrada de Mercadorias e Abastecer Estoque
            </button>
        </div>
    </form>
</div>

<script>
function recalcularItem(numItem) {
    const inputFator = document.getElementById('fator_' + numItem);
    const qtdNf = parseFloat(inputFator.getAttribute('data-qtd-nf')) || 0;
    const fator = Math.max(1, parseInt(inputFator.value, 10) || 1);

    const convertida = Math.ceil(qtdNf * fator);
    document.getElementById('qtd_convertida_' + numItem).textContent = convertida;

    atualizarTotalGeral();
}

function atualizarTotalGeral() {
    let total = 0;
    document.querySelectorAll('.input-fator').forEach(el => {
        const item = el.getAttribute('data-item');
        const qtdEl = document.getElementById('qtd_convertida_' + item);
        if (qtdEl) {
            total += parseInt(qtdEl.textContent, 10) || 0;
        }
    });

    document.getElementById('lblTotalGeralConvertido').textContent = '+' + total + ' un';
}

function validarMapeamentoItens() {
    const selects = document.querySelectorAll('.select-produto-mapeado');
    let faltantes = 0;

    selects.forEach(s => {
        if (!s.value) {
            s.classList.add('border-danger');
            faltantes++;
        } else {
            s.classList.remove('border-danger');
        }
    });

    if (faltantes > 0) {
        alert('Atenção: Existem ' + faltantes + ' item(ns) da nota fiscal que ainda não foram associados a um produto da cantina. Selecione todos os produtos antes de confirmar.');
        return false;
    }

    const btn = document.getElementById('btnConfirmarEntrada');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Abastecendo estoque...';

    return true;
}

// Inicializa soma total no carregamento
document.addEventListener('DOMContentLoaded', function() {
    atualizarTotalGeral();
});
</script>
