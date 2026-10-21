<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela: Entrada de Estoque & Mercadorias (Manual sem NF-e)
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md (Anti-Slop Enterprise Edition)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$produtos = $produtos ?? [];
$ultimasEntradas = $ultimasEntradas ?? [];
$fornecedores = $fornecedores ?? [];
$produtoPreSel = $produtoPreSel ?? null;
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
            <h1 class="h5 fw-bold text-slate-900 mb-0">Entrada de Mercadorias em Estoque</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-primary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Coluna Esquerda: Formulário de Entrada de Estoque -->
        <div class="col-12 col-lg-5">
            <div class="card-enterprise p-3">
                <div class="border-bottom pb-2 mb-3">
                    <h2 class="h6 fw-bold text-slate-900 mb-0">Lançamento de Entrada</h2>
                    <span class="text-muted" style="font-size: 12px;">Dê entrada física direta em qualquer produto do cardápio informando o fornecedor e o quantitativo.</span>
                </div>

                <form method="POST" action="<?php echo $baseUrl; ?>/estoque/entrada/salvar" id="formEntrada" onsubmit="return validarEnvioEntrada(event)">
                    <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">

                    <!-- Seleção do Produto -->
                    <div class="mb-3">
                        <label for="selectProduto" class="form-label small fw-semibold text-muted">Produto a Abastecer: *</label>
                        <select name="produto_id" id="selectProduto" class="form-select form-select-sm" required onchange="atualizarDadosProduto(this)">
                            <option value="">Selecione o produto...</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" 
                                        data-estoque="<?php echo (int)$p['estoque_atual']; ?>"
                                        data-preco="<?php echo (float)$p['preco_venda']; ?>"
                                        data-custo="<?php echo (float)($p['preco_custo'] ?? 0); ?>"
                                        <?php echo ((int)$produtoPreSel === (int)$p['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['codigo_interno'] . ' — ' . $p['descricao'] . ' (Estoque atual: ' . (int)$p['estoque_atual'] . ' un)', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="infoSaldoAtual" class="form-text small mt-1 text-slate-700 font-tabular d-none">
                            Saldo físico atual no sistema: <strong id="lblSaldoFisico" class="text-primary">0</strong> unidades.
                        </div>
                    </div>

                    <!-- Fornecedor com Autocomplete (Datalist) -->
                    <div class="mb-3">
                        <label for="inputFornecedor" class="form-label small fw-semibold text-muted">Fornecedor / Distribuidor: *</label>
                        <input type="text" 
                               name="fornecedor" 
                               id="inputFornecedor" 
                               class="form-control form-control-sm" 
                               list="listaFornecedores" 
                               required 
                               autocomplete="off"
                               placeholder="Digite ou escolha o fornecedor...">
                        <datalist id="listaFornecedores">
                            <?php foreach ($fornecedores as $forn): ?>
                                <option value="<?php echo htmlspecialchars($forn, ENT_QUOTES, 'UTF-8'); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <div class="form-text" style="font-size: 11px;">Você pode escolher um fornecedor frequente da lista ou digitar um novo.</div>
                    </div>

                    <!-- Quantidade de Itens -->
                    <div class="mb-3">
                        <label for="inputQuantidade" class="form-label small fw-semibold text-muted">Quantidade a Adicionar (unidades): *</label>
                        <input type="number" 
                               name="quantidade" 
                               id="inputQuantidade" 
                               class="form-control form-control-sm font-tabular" 
                               min="1" 
                               step="1" 
                               required 
                               placeholder="Ex: 50">
                        <div class="form-text" style="font-size: 11px;">Esta quantidade será somada imediatamente ao saldo físico disponível para venda.</div>
                    </div>

                    <!-- Preço de Custo Unitário (Opcional) -->
                    <div class="mb-3">
                        <label for="inputPrecoCusto" class="form-label small fw-semibold text-muted">Preço de Custo Unitário (R$) &mdash; Opcional:</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">R$</span>
                            <input type="text" 
                                   name="preco_custo" 
                                   id="inputPrecoCusto" 
                                   class="form-control form-control-sm font-tabular input-money" 
                                   placeholder="0,00">
                        </div>
                        <div class="form-text" style="font-size: 11px;">Se informado, atualiza a referência de custo do item no cálculo de margens e DRE.</div>
                    </div>

                    <!-- Observações / Comprovante (Opcional) -->
                    <div class="mb-3">
                        <label for="textareaObservacoes" class="form-label small fw-semibold text-muted">Observações / Recibo / Documento &mdash; Opcional:</label>
                        <textarea name="observacoes" 
                                  id="textareaObservacoes" 
                                  rows="2" 
                                  class="form-control form-control-sm" 
                                  placeholder="Ex: Pedido nº 1234, recibo manual, entrega do turno matutino..."></textarea>
                    </div>

                    <div class="pt-2 border-top d-flex justify-content-end gap-2">
                        <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">Cancelar</a>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/></svg>
                            Confirmar Entrada em Estoque
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Coluna Direita: Diretrizes Operacionais e Histórico Recente -->
        <div class="col-12 col-lg-7">
            <!-- Diretrizes de Entrada e Auditoria -->
            <div class="card-enterprise p-3 mb-3 bg-light border-0" style="border-left: 3px solid #1d4ed8 !important;">
                <span class="small fw-bold text-primary d-block mb-1">Diretriz Operacional — Gestão Direta de Estoque</span>
                <p class="text-secondary small mb-0" style="line-height: 1.45;">
                    As entradas de estoque incrementam em tempo real o saldo disponível na grade do PDV e nas reservas de pedidos online. Todos os lançamentos registram o operador logado, data, hora e fornecedor para fins de controle e auditoria contábil.
                </p>
            </div>

            <!-- Tabela de Últimas Entradas Registradas -->
            <div class="card-enterprise">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                    <span class="card-enterprise-title">Últimas Entradas Registradas</span>
                    <span class="text-muted small">Mostrando até 20 registros</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-enterprise table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 125px;">Data / Hora</th>
                                <th>Produto</th>
                                <th style="width: 90px;" class="text-end">Qtd</th>
                                <th style="width: 160px;">Fornecedor</th>
                                <th style="width: 110px;">Operador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimasEntradas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <em>Não há nada para mostrar aqui</em>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ultimasEntradas as $le): ?>
                                    <tr>
                                        <td class="font-tabular text-muted small">
                                            <?php echo date('d/m/Y H:i', strtotime($le['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-slate-900 small d-block"><?php echo htmlspecialchars($le['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <code class="code-tag" style="font-size: 10px;"><?php echo htmlspecialchars($le['produto_codigo'], ENT_QUOTES, 'UTF-8'); ?></code>
                                        </td>
                                        <td class="text-end font-tabular text-success fw-bold">
                                            +<?php echo (int)$le['quantidade']; ?> un
                                        </td>
                                        <td>
                                            <span class="small text-slate-800 fw-semibold d-block">
                                                <?php echo !empty($le['fornecedor']) ? htmlspecialchars($le['fornecedor'], ENT_QUOTES, 'UTF-8') : 'Fornecedor'; ?>
                                            </span>
                                            <?php if (!empty($le['justificativa'])): ?>
                                                <span class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($le['justificativa'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="small text-slate-700"><?php echo htmlspecialchars($le['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
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
function atualizarDadosProduto(sel) {
    var opt = sel.options[sel.selectedIndex];
    var infoBox = document.getElementById('infoSaldoAtual');
    var lblSaldo = document.getElementById('lblSaldoFisico');
    var inputCusto = document.getElementById('inputPrecoCusto');

    if (!opt || !opt.value) {
        infoBox.classList.add('d-none');
        return;
    }

    var estoque = opt.getAttribute('data-estoque') || 0;
    var custo = parseFloat(opt.getAttribute('data-custo') || 0);

    lblSaldo.textContent = estoque;
    infoBox.classList.remove('d-none');

    if (custo > 0 && inputCusto && !inputCusto.value) {
        inputCusto.value = custo.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function validarEnvioEntrada(e) {
    var selProd = document.getElementById('selectProduto');
    var inputQtd = document.getElementById('inputQuantidade');
    var inputForn = document.getElementById('inputFornecedor');

    if (!selProd || !selProd.value) {
        alert('Por favor, selecione o produto.');
        selProd.focus();
        return false;
    }

    var forn = inputForn ? inputForn.value.trim() : '';
    if (forn.length < 2) {
        alert('Por favor, informe o nome do fornecedor (mínimo 2 caracteres).');
        inputForn.focus();
        return false;
    }

    var qtd = inputQtd ? parseInt(inputQtd.value, 10) : 0;
    if (isNaN(qtd) || qtd <= 0) {
        alert('Por favor, informe uma quantidade válida maior que zero.');
        inputQtd.focus();
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    var selProd = document.getElementById('selectProduto');
    if (selProd && selProd.value) {
        atualizarDadosProduto(selProd);
    }
});
</script>
