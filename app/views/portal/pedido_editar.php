<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Edição Autônoma de Pedido de Lanches pelo Responsável
 * 
 * Conforme docs/FSD.md (Área 1, Tela 2.4, Fluxo 4, RN-04),
 * docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfToken = \App\Core\Session::generateCsrfToken();
$totalAntigo = (float)$pedido['valor_total'];
$corte = $pedido['status_corte'];
?>

<style>
/* Remove terminantemente qualquer seta ou spinner nativo do navegador */
.input-qtd::-webkit-outer-spin-button,
.input-qtd::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
    display: none !important;
}
.input-qtd {
    -moz-appearance: textfield !important;
    appearance: textfield !important;
    text-align: center !important;
}
</style>

<div class="pedido-editar-container">
    <!-- Cabeçalho Executivo -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-4 border-bottom border-subtle gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="text-secondary text-decoration-none" title="Voltar para Meus Pedidos">
                    &larr; Voltar
                </a>
                <span class="text-muted">/</span>
                <h1 class="page-title mb-0" style="font-size: 18px;">
                    Editar Pedido <code class="code-tag font-tabular" style="font-size: 14px;"><?php echo htmlspecialchars($pedido['codigo_pedido'], ENT_QUOTES, 'UTF-8'); ?></code>
                </h1>
            </div>
            <p class="text-secondary mb-0" style="font-size: 13px;">
                Adicione ou remova itens do pedido. A diferença financeira será compensada automaticamente na sua Carteira Familiar.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-secondary" style="font-size: 12px;">Saldo Disponível:</span>
            <strong class="font-tabular text-dark" style="font-size: 13px; color: #0f172a;">R$ <?php echo number_format($saldoDisponivel, 2, ',', '.'); ?></strong>
        </div>
    </div>

    <!-- Identificação do Pedido e Trava de Corte -->
    <div class="card-enterprise p-3 mb-4 bg-light border-subtle d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <div class="fw-semibold text-dark mb-1" style="font-size: 14px;">
                <?php echo htmlspecialchars($pedido['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?>
                <span class="text-secondary fw-normal">(<?php echo htmlspecialchars($pedido['nome_turma'] ?? $pedido['serie'] ?? '', ENT_QUOTES, 'UTF-8'); ?> &bull; Matrícula: <?php echo htmlspecialchars($pedido['aluno_matricula'], ENT_QUOTES, 'UTF-8'); ?>)</span>
            </div>
            <div class="text-secondary" style="font-size: 12px;">
                Data de Consumo: <strong class="font-tabular text-dark"><?php echo date('d/m/Y', strtotime($pedido['data_consumo'])); ?></strong>
                &bull; Turno: <strong class="text-uppercase"><?php echo $pedido['turno_consumo']; ?></strong>
                &bull; Valor Atual: <strong class="font-tabular text-dark">R$ <?php echo number_format($totalAntigo, 2, ',', '.'); ?></strong>
            </div>
        </div>
        <div class="text-md-end">
            <code class="code-tag font-tabular" style="font-size: 11px;">
                Corte às <?php echo $corte['hora_corte']; ?>
            </code>
            <?php if ($corte['minutos_restantes'] !== null): ?>
                <div class="text-secondary mt-1 font-tabular" style="font-size: 11px;">
                    Restam aprox. <strong><?php echo $corte['minutos_restantes']; ?> minutos</strong> para editar
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Formulário de Edição -->
    <form action="<?php echo $baseUrl; ?>/portal/pedidos/editar/<?php echo $pedido['id']; ?>" method="POST" id="form-editar-pedido">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <div class="row g-4">
            <!-- Coluna de Produtos Disponíveis -->
            <div class="col-12 col-lg-8">
                <?php foreach ($produtosPorCategoria as $categoriaNome => $produtosCat): ?>
                    <div class="card-enterprise p-3 p-md-4 mb-4">
                        <h3 class="card-title pb-2 mb-3 border-bottom border-subtle d-flex justify-content-between align-items-center" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: #475569;">
                            <span><?php echo htmlspecialchars($categoriaNome, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">
                                <?php echo count($produtosCat); ?> opções
                            </span>
                        </h3>

                        <div class="row g-3">
                            <?php foreach ($produtosCat as $prod): ?>
                                <?php 
                                $qtdAtual = (int)($itensAtuais[(int)$prod['id']] ?? 0);
                                $esgotado = (bool)($prod['esgotado'] ?? false);
                                $temConflito = (bool)$prod['tem_conflito_alergia'];
                                $conflitosStr = implode(', ', $prod['conflitos_nomes']);
                                $maxPermitido = (int)($prod['estoque_max_edicao'] ?? ($prod['tipo'] === 'industrializado_continuo' ? ($prod['estoque_disponivel'] ?? 0) : 10));
                                $isItemEsgotado = ($esgotado && $qtdAtual === 0);
                                ?>
                                <div class="col-12 col-md-6">
                                    <div class="p-3 border rounded-3 h-100 d-flex flex-column justify-content-between transition-all product-card <?php 
                                        if ($isItemEsgotado) {
                                            echo 'bg-light border-subtle';
                                        } elseif ($qtdAtual > 0) {
                                            echo 'border-secondary bg-light';
                                        } else {
                                            echo 'bg-white';
                                        }
                                    ?>"
                                         id="product-box-<?php echo $prod['id']; ?>"
                                         style="<?php echo $isItemEsgotado ? 'opacity: 0.55; cursor: not-allowed;' : ''; ?>">
                                        
                                        <div>
                                            <div class="d-flex align-items-start gap-2 mb-2">
                                                <div class="product-thumb flex-shrink-0" style="width: 44px; height: 44px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                    <?php if (!empty($prod['foto_url'])): ?>
                                                        <img src="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($prod['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                    <?php else: ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#94a3b8" viewBox="0 0 16 16">
                                                            <path d="M8 1a5 5 0 0 0-5 5v1h10V6a5 5 0 0 0-5-5m4 7H4c-.55 0-1 .45-1 1v4a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9c0-.55-.45-1-1-1"/>
                                                        </svg>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h4 class="mb-0 text-dark fw-semibold" style="font-size: 13px; line-height: 1.3;">
                                                        <?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </h4>
                                                    <div class="text-secondary mt-1" style="font-size: 11px;">
                                                        Código: <code class="code-tag font-tabular" style="font-size: 10px;"><?php echo htmlspecialchars($prod['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?></code>
                                                        &bull; <?php echo ($prod['tipo'] === 'salgado_diario') ? 'Salgado Diário' : 'Industrializado'; ?>
                                                        <?php if ($prod['tipo'] === 'industrializado_continuo'): ?>
                                                            &bull; <span class="font-tabular text-muted">Disp: <?php echo $maxPermitido; ?> un</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php if ($temConflito): ?>
                                                <div class="mb-2 p-1 px-2 rounded" style="background: #fffbeb; border: 1px solid #fde68a; font-size: 11px; color: #92400e;">
                                                    <strong>Contém:</strong> <?php echo htmlspecialchars($conflitosStr, ENT_QUOTES, 'UTF-8'); ?> (incompatível)
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-subtle mt-2">
                                            <div class="font-tabular" style="font-size: 15px; font-weight: 700; color: #0f172a;">
                                                R$ <?php echo number_format($prod['preco_venda'], 2, ',', '.'); ?>
                                            </div>

                                            <?php if ($isItemEsgotado): ?>
                                                <code class="code-tag text-uppercase font-tabular" style="font-size: 10px; color: #64748b;">Esgotado</code>
                                            <?php else: ?>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" 
                                                            class="btn btn-enterprise btn-secondary btn-sm btn-qtd-diminuir" 
                                                            data-id="<?php echo $prod['id']; ?>"
                                                            style="width: 28px; height: 28px; padding: 0;"
                                                            <?php echo ($qtdAtual <= 0) ? 'disabled' : ''; ?>>-</button>
                                                    
                                                    <input type="text" 
                                                           inputmode="numeric" 
                                                           pattern="[0-9]*" 
                                                           name="itens[<?php echo $prod['id']; ?>]" 
                                                           id="qtd-<?php echo $prod['id']; ?>" 
                                                           value="<?php echo $qtdAtual; ?>" 
                                                           min="0" 
                                                           max="<?php echo $maxPermitido; ?>" 
                                                           class="form-control form-control-sm text-center font-tabular input-qtd" 
                                                           style="width: 44px; height: 28px; font-weight: 600; padding: 0 4px; text-align: center; -moz-appearance: textfield; appearance: textfield;"
                                                           data-id="<?php echo $prod['id']; ?>"
                                                           data-nome="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                                                           data-preco="<?php echo $prod['preco_venda']; ?>"
                                                           data-conflito="<?php echo $temConflito ? '1' : '0'; ?>"
                                                           data-conflitos-msg="<?php echo htmlspecialchars($conflitosStr, ENT_QUOTES, 'UTF-8'); ?>"
                                                           data-max="<?php echo $maxPermitido; ?>"
                                                           readonly>

                                                    <button type="button" 
                                                            class="btn btn-enterprise btn-secondary btn-sm btn-qtd-aumentar" 
                                                            data-id="<?php echo $prod['id']; ?>"
                                                            style="width: 28px; height: 28px; padding: 0;"
                                                            <?php echo ($qtdAtual >= $maxPermitido) ? 'disabled' : ''; ?>>+</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Coluna Lateral: Resumo de Compensação Contábil -->
            <div class="col-12 col-lg-4">
                <div class="card-enterprise p-3 p-md-4 sticky-top" style="top: 20px; z-index: 10;">
                    <h3 class="card-title pb-2 mb-3 border-bottom border-subtle" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: #475569;">
                        Compensação Financeira
                    </h3>

                    <!-- Itens Selecionados -->
                    <div class="border rounded p-2 mb-3 bg-light" id="comanda-itens-container" style="min-height: 120px; max-height: 250px; overflow-y: auto;">
                        <ul class="list-unstyled mb-0" id="comanda-lista">
                            <!-- Preenchido via JS -->
                        </ul>
                    </div>

                    <!-- Confronto de Valores -->
                    <div class="pt-2 border-top border-subtle mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1 text-secondary" style="font-size: 12px;">
                            <span>Valor Original do Pedido:</span>
                            <span class="font-tabular fw-medium text-dark">R$ <?php echo number_format($totalAntigo, 2, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 15px; font-weight: 700; color: #0f172a;">
                            <span>Novo Total:</span>
                            <span class="font-tabular text-dark" style="color: #0f172a;" id="display-novo-total">R$ <?php echo number_format($totalAntigo, 2, ',', '.'); ?></span>
                        </div>

                        <!-- Caixa de Destaque da Diferença -->
                        <div class="p-2 rounded border mb-2 text-center" id="box-diferenca" style="background: #f8fafc;">
                            <div class="text-secondary" style="font-size: 11px; text-transform: uppercase; font-weight: 600;" id="label-diferenca">
                                Nenhuma alteração de valor
                            </div>
                            <div class="font-tabular my-1" style="font-size: 16px; font-weight: 700;" id="display-diferenca">
                                R$ 0,00
                            </div>
                            <div class="text-muted" style="font-size: 10px;" id="msg-diferenca">
                                Mesmo valor do pedido original.
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center text-secondary mt-2" style="font-size: 11px;">
                            <span>Saldo Livre Atual:</span>
                            <span class="font-tabular fw-medium">R$ <?php echo number_format($saldoDisponivel, 2, ',', '.'); ?></span>
                        </div>
                    </div>

                    <!-- Alerta de Saldo Insuficiente -->
                    <div class="alert alert-danger p-2 mb-3 d-none" id="alerta-saldo-insuficiente" style="font-size: 11px;">
                        <strong>Saldo Insuficiente:</strong> A diferença complementar a pagar excede o saldo livre disponível na sua carteira.
                    </div>

                    <!-- Botões de Ação -->
                    <button type="submit" 
                            class="btn btn-enterprise btn-primary w-100 py-2 mb-2 d-flex align-items-center justify-content-center gap-2" 
                            id="btn-gravar-edicao"
                            style="font-size: 13px; font-weight: 600;">
                        Salvar Alterações do Pedido
                    </button>
                    <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="btn btn-enterprise btn-secondary w-100 btn-sm">
                        Cancelar e Voltar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Alérgeno na Edição -->
<div class="modal fade" id="modal-alergia-edicao" tabindex="-1" aria-labelledby="modalAlergiaEdicaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header border-0 pb-0" style="background: #fffbeb;">
                <h5 class="modal-title fw-bold" id="modalAlergiaEdicaoLabel" style="font-size: 15px; color: #92400e;">
                    Aviso de Restrição Alimentar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-3 text-secondary" style="font-size: 13px; line-height: 1.5;" id="modal-edicao-texto">
                    <!-- Preenchido via JS -->
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-enterprise btn-primary btn-sm" id="btn-edicao-confirmar" style="background: #d97706; border-color: #b45309;">
                    Confirmar Inclusão
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalAntigo = <?php echo (float)$totalAntigo; ?>;
    const saldoDisponivel = <?php echo (float)$saldoDisponivel; ?>;
    const nomeAluno = <?php echo json_encode($pedido['aluno_nome'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const modalAlergiaEl = document.getElementById('modal-alergia-edicao');
    const modalAlergia = modalAlergiaEl ? new bootstrap.Modal(modalAlergiaEl) : null;
    const modalTexto = document.getElementById('modal-edicao-texto');
    const btnAlergiaConfirmar = document.getElementById('btn-edicao-confirmar');

    let produtoPendente = null;

    function formatarMoeda(val) {
        return 'R$ ' + val.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    }

    function recalcularEdicao() {
        const inputs = document.querySelectorAll('.input-qtd');
        let novoTotal = 0;
        let totalItens = 0;
        const itens = [];

        inputs.forEach(input => {
            const qtd = parseInt(input.value) || 0;
            if (qtd > 0) {
                const id = input.dataset.id;
                const nome = input.dataset.nome;
                const preco = parseFloat(input.dataset.preco) || 0;
                const subtotal = qtd * preco;

                novoTotal += subtotal;
                totalItens += qtd;
                itens.push({ id, nome, qtd, subtotal });
            }
        });

        // Atualiza exibição dos totais
        const displayNovoTotal = document.getElementById('display-novo-total');
        const displayDiferenca = document.getElementById('display-diferenca');
        const labelDiferenca = document.getElementById('label-diferenca');
        const msgDiferenca = document.getElementById('msg-diferenca');
        const boxDiferenca = document.getElementById('box-diferenca');
        const alertaSaldo = document.getElementById('alerta-saldo-insuficiente');
        const btnGravar = document.getElementById('btn-gravar-edicao');
        const comandaLista = document.getElementById('comanda-lista');

        if (!displayNovoTotal) return;

        displayNovoTotal.textContent = formatarMoeda(novoTotal);

        const diferenca = novoTotal - totalAntigo;

        if (diferenca > 0.001) {
            // Mais caro: diferença a pagar
            labelDiferenca.textContent = 'Diferença a Pagar';
            displayDiferenca.textContent = '+' + formatarMoeda(diferenca);
            displayDiferenca.style.color = '#dc2626';
            msgDiferenca.textContent = 'Será debitado da sua Carteira Familiar.';
            boxDiferenca.style.background = '#fef2f2';
            boxDiferenca.style.borderColor = '#fecaca';

            if (diferenca > saldoDisponivel) {
                alertaSaldo.classList.remove('d-none');
                btnGravar.disabled = true;
            } else {
                alertaSaldo.classList.add('d-none');
                btnGravar.disabled = (totalItens === 0);
            }
        } else if (diferenca < -0.001) {
            // Mais barato: estorno a receber
            const estorno = Math.abs(diferenca);
            labelDiferenca.textContent = 'Estorno Imediato';
            displayDiferenca.textContent = '-' + formatarMoeda(estorno);
            displayDiferenca.style.color = '#16a34a';
            msgDiferenca.textContent = 'Será creditado de volta na sua Carteira.';
            boxDiferenca.style.background = '#f0fdf4';
            boxDiferenca.style.borderColor = '#bbf7d0';
            alertaSaldo.classList.add('d-none');
            btnGravar.disabled = (totalItens === 0);
        } else {
            // Mesmo valor
            labelDiferenca.textContent = 'Mesmo Valor';
            displayDiferenca.textContent = 'R$ 0,00';
            displayDiferenca.style.color = '#0f172a';
            msgDiferenca.textContent = 'Nenhuma alteração de saldo necessária.';
            boxDiferenca.style.background = '#f8fafc';
            boxDiferenca.style.borderColor = '#e2e8f0';
            alertaSaldo.classList.add('d-none');
            btnGravar.disabled = (totalItens === 0);
        }

        // Renderiza itens da lista
        let html = '';
        itens.forEach(it => {
            html += `
                <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-subtle" style="font-size: 12px;">
                    <div><strong>${it.qtd}x</strong> ${it.nome}</div>
                    <span class="font-tabular fw-semibold text-dark">${formatarMoeda(it.subtotal)}</span>
                </li>
            `;
        });
        comandaLista.innerHTML = html;
    }

    function atualizarEstadoBotoes(id) {
        const input = document.getElementById('qtd-' + id);
        if (!input) return;

        const val = parseInt(input.value) || 0;
        const max = parseInt(input.getAttribute('max')) || 0;
        const btnDim = document.querySelector(`.btn-qtd-diminuir[data-id="${id}"]`);
        const btnAum = document.querySelector(`.btn-qtd-aumentar[data-id="${id}"]`);
        const box = document.getElementById('product-box-' + id);

        if (btnDim) {
            btnDim.disabled = (val <= 0);
        }
        if (btnAum) {
            btnAum.disabled = (val >= max);
        }
        if (box) {
            if (val > 0) {
                box.classList.add('border-secondary', 'bg-light');
                box.classList.remove('bg-white');
            } else {
                box.classList.remove('border-secondary', 'bg-light');
                box.classList.add('bg-white');
            }
        }
    }

    document.querySelectorAll('.btn-qtd-aumentar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const input = document.getElementById('qtd-' + id);
            if (!input) return;

            const max = parseInt(input.getAttribute('max')) || 0;
            let current = parseInt(input.value) || 0;

            if (current >= max) {
                alert('Limite máximo disponível em estoque atingido para este item (' + max + ' un).');
                return;
            }

            if (current === 0 && input.dataset.conflito === '1') {
                produtoPendente = input;
                const conflitos = input.dataset.conflitosMsg || 'ingredientes restritos';
                modalTexto.innerHTML = `Atenção: Este produto contém <strong>${conflitos}</strong>, incompatível com o cadastro de <strong>${nomeAluno}</strong>. Deseja realmente incluir este item?`;
                modalAlergia.show();
                return;
            }

            input.value = current + 1;
            atualizarEstadoBotoes(id);
            recalcularEdicao();
        });
    });

    document.querySelectorAll('.btn-qtd-diminuir').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const input = document.getElementById('qtd-' + id);
            if (!input) return;

            let current = parseInt(input.value) || 0;
            if (current > 0) {
                input.value = current - 1;
                atualizarEstadoBotoes(id);
                recalcularEdicao();
            }
        });
    });

    if (btnAlergiaConfirmar) {
        btnAlergiaConfirmar.addEventListener('click', function() {
            if (produtoPendente) {
                const max = parseInt(produtoPendente.getAttribute('max')) || 0;
                if (max >= 1) {
                    produtoPendente.value = 1;
                    atualizarEstadoBotoes(produtoPendente.dataset.id);
                    recalcularEdicao();
                } else {
                    alert('Item esgotado no estoque.');
                }
                produtoPendente = null;
            }
            modalAlergia.hide();
        });
    }

    // Inicializa o estado dos botões conforme as quantidades e limites atuais
    document.querySelectorAll('.input-qtd').forEach(input => {
        atualizarEstadoBotoes(input.dataset.id);
    });

    recalcularEdicao();
});
</script>
