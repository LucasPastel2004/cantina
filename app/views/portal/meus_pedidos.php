<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Meus Pedidos e Histórico de Encomendas Familiares
 * 
 * Conforme docs/FSD.md (Área 1, Tela 2.4, Fluxo 4, RN-02 a RN-04),
 * docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfToken = \App\Core\Session::generateCsrfToken();
?>

<div class="pedidos-container">
    <!-- Cabeçalho Executivo -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-4 border-bottom border-subtle gap-3">
        <div>
            <h1 class="page-title mb-1">Meus Pedidos de Lanche</h1>
            <p class="text-secondary mb-0" style="font-size: 13px;">
                Histórico de encomendas escolares, status de preparação e gerenciamento autônomo com travas de corte.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary btn-sm">
                + Novo Agendamento
            </a>
            <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-primary btn-sm">
                Extrato Financeiro
            </a>
        </div>
    </div>

    <!-- Filtros de Consulta -->
    <div class="card-enterprise p-3 mb-4 bg-light border-subtle">
        <form action="<?php echo $baseUrl; ?>/portal/pedidos" method="GET" class="row g-2 align-items-end">
            <!-- Filtro por Dependente -->
            <div class="col-12 col-md-4">
                <label for="filtro-aluno" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    Dependente
                </label>
                <select name="aluno" id="filtro-aluno" class="form-select form-select-sm" style="font-size: 13px;">
                    <option value="0">Todos os Dependentes</option>
                    <?php foreach ($dependentes as $dep): ?>
                        <option value="<?php echo $dep['id']; ?>" <?php echo ((int)$dep['id'] === (int)$alunoFiltro) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($dep['nome_turma'] ?? $dep['serie'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Data Início -->
            <div class="col-6 col-md-3">
                <label for="filtro-data-inicio" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    De
                </label>
                <input type="date" name="data_inicio" id="filtro-data-inicio" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm font-tabular" style="font-size: 13px;">
            </div>

            <!-- Data Fim -->
            <div class="col-6 col-md-3">
                <label for="filtro-data-fim" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    Até
                </label>
                <input type="date" name="data_fim" id="filtro-data-fim" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm font-tabular" style="font-size: 13px;">
            </div>

            <!-- Botão Filtrar -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm w-100">
                    Filtrar
                </button>
                <?php if (!empty($alunoFiltro) || !empty($dataInicio) || !empty($dataFim)): ?>
                    <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">
                        &times;
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Listagem de Pedidos em Data-Grid -->
    <div class="card-enterprise overflow-hidden mb-4">
        <?php if (empty($pedidos)): ?>
            <div class="p-5 text-center">
                <div class="mb-3 text-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                        <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a1 1 0 0 1 .21.645v4.604a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V8.833a1 1 0 0 1 .21-.646z"/>
                    </svg>
                </div>
                <h4 class="fw-semibold mb-1" style="font-size: 15px; color: #475569;">Não há nada para mostrar aqui</h4>
                <p class="text-secondary mb-3" style="font-size: 13px;">
                    Nenhum pedido de lanche localizado para os critérios informados.
                </p>
                <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary btn-sm">
                    Agendar Lanche Agora
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-enterprise mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Código</th>
                            <th style="width: 120px;">Data Consumo</th>
                            <th>Aluno / Turma</th>
                            <th>Itens Encomendados</th>
                            <th class="text-end" style="width: 110px;">Total</th>
                            <th class="text-center" style="width: 110px;">Status</th>
                            <th class="text-end" style="width: 180px;">Ações / Corte</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p): ?>
                            <?php 
                            $corte = $p['status_corte']; 
                            $isAtivo = ($p['status'] === 'agendado' || $p['status'] === 'editado');
                            $dataConsumoFmt = date('d/m/Y', strtotime($p['data_consumo']));
                            $dataConsumoIso = $p['data_consumo'];
                            ?>
                            <tr>
                                <!-- Código do Pedido -->
                                <td>
                                    <code class="code-tag font-tabular" style="font-size: 12px; font-weight: 600;">
                                        <?php echo htmlspecialchars($p['codigo_pedido'], ENT_QUOTES, 'UTF-8'); ?>
                                    </code>
                                    <div class="text-muted" style="font-size: 10px;">
                                        <?php echo date('d/m H:i', strtotime($p['created_at'])); ?>
                                    </div>
                                </td>

                                <!-- Data de Consumo -->
                                <td>
                                    <span class="font-tabular fw-semibold text-dark" style="font-size: 13px;">
                                        <?php echo $dataConsumoFmt; ?>
                                    </span>
                                    <div class="text-secondary" style="font-size: 11px;">
                                        Turno <?php echo ucfirst($p['turno_consumo']); ?>
                                    </div>
                                </td>

                                <!-- Aluno / Turma -->
                                <td>
                                    <div class="fw-medium text-dark" style="font-size: 13px;">
                                        <?php echo htmlspecialchars($p['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-secondary" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($p['nome_turma'] ?? $p['serie'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        &bull; Matrícula: <span class="font-tabular"><?php echo htmlspecialchars($p['aluno_matricula'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </td>

                                <!-- Itens Encomendados -->
                                <td>
                                    <div style="font-size: 12px; max-width: 320px;">
                                        <?php foreach ($p['itens'] as $it): ?>
                                            <div>
                                                <strong><?php echo $it['quantidade']; ?>x</strong> <?php echo htmlspecialchars($it['produto_descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                                <span class="text-muted font-tabular" style="font-size: 11px;">(R$ <?php echo number_format($it['subtotal'], 2, ',', '.'); ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>

                                <!-- Valor Total -->
                                <td class="text-end font-tabular">
                                    <strong style="font-size: 14px; color: #0f172a;">
                                        R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?>
                                    </strong>
                                </td>

                                <!-- Status do Pedido -->
                                <td class="text-center">
                                    <?php if ($p['status'] === 'agendado'): ?>
                                        <span class="status-indicator status-active" style="font-size: 12px;">
                                            <span class="bullet">●</span> Agendado
                                        </span>
                                    <?php elseif ($p['status'] === 'editado'): ?>
                                        <span class="status-indicator" style="font-size: 12px; color: #1d4ed8;">
                                            <span class="bullet" style="color: #1d4ed8;">●</span> Editado
                                        </span>
                                    <?php elseif ($p['status'] === 'entregue'): ?>
                                        <span class="status-indicator" style="font-size: 12px; color: #15803d;">
                                            <span class="bullet" style="color: #15803d;">●</span> Entregue
                                        </span>
                                    <?php elseif ($p['status'] === 'cancelado'): ?>
                                        <span class="status-indicator" style="font-size: 12px; color: #b91c1c;">
                                            <span class="bullet" style="color: #b91c1c;">●</span> Cancelado
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Ações / Travas de Corte -->
                                <td class="text-end">
                                    <?php if ($isAtivo): ?>
                                        <?php if ($corte['pode_alterar']): ?>
                                            <!-- Corte Aberto: Permite Editar e Cancelar -->
                                            <div class="d-flex justify-content-end align-items-center gap-1">
                                                <a href="<?php echo $baseUrl; ?>/portal/pedidos/editar/<?php echo $p['id']; ?>" 
                                                   class="btn btn-enterprise btn-secondary btn-sm"
                                                   title="Editar lanches do pedido">
                                                    Editar
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-enterprise btn-secondary btn-sm text-danger btn-abrir-cancelamento"
                                                        data-id="<?php echo $p['id']; ?>"
                                                        data-codigo="<?php echo htmlspecialchars($p['codigo_pedido'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-aluno="<?php echo htmlspecialchars($p['aluno_nome'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-data="<?php echo $dataConsumoFmt; ?>"
                                                        data-total="R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?>"
                                                        title="Cancelar pedido com estorno de 100%">
                                                    Cancelar
                                                </button>
                                            </div>
                                            <?php if ($corte['minutos_restantes'] !== null && $corte['minutos_restantes'] <= 60): ?>
                                                <div class="text-warning text-end mt-1 font-tabular" style="font-size: 10px;">
                                                    Corte encerra em <?php echo $corte['minutos_restantes']; ?> min
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Corte Encerrado -->
                                            <span class="text-muted" style="font-size: 11px; font-weight: 500;" title="Horário de corte atingido às <?php echo $corte['hora_corte']; ?>">
                                                Em Preparação &bull; Corte Fechado
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($p['status'] === 'cancelado'): ?>
                                        <span class="text-muted font-tabular" style="font-size: 11px;">
                                            100% Estornado
                                        </span>
                                    <?php elseif ($p['status'] === 'entregue'): ?>
                                        <span class="text-secondary font-tabular" style="font-size: 11px;">
                                            Retirado no Recreio
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Corporativo de Cancelamento de Pedido com Estorno 100% (RN-03) -->
<div class="modal fade" id="modal-cancelar-pedido" tabindex="-1" aria-labelledby="modalCancelarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            <form action="<?php echo $baseUrl; ?>/portal/pedidos/cancelar" method="POST" id="form-cancelar-pedido">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="pedido_id" id="cancelar-pedido-id" value="">

                <div class="modal-header border-0 pb-0" style="background: #fef2f2;">
                    <div class="d-flex align-items-center gap-2 text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#dc2626" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z"/>
                        </svg>
                        <h5 class="modal-title fw-bold" id="modalCancelarLabel" style="font-size: 15px; color: #991b1b;">
                            Cancelar Pedido de Lanche
                        </h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body pt-3">
                    <p class="mb-3 text-secondary" style="font-size: 13px; line-height: 1.5;">
                        Deseja realmente cancelar o pedido <strong id="cancelar-codigo" class="font-tabular text-dark"></strong> de <strong id="cancelar-aluno" class="text-dark"></strong> para o dia <strong id="cancelar-data" class="font-tabular text-dark"></strong>?
                    </p>

                    <div class="p-3 rounded bg-light border mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary" style="font-size: 12px;">Valor total a ser estornado:</span>
                            <strong class="font-tabular text-dark" id="cancelar-total" style="font-size: 15px; color: #0f172a;"></strong>
                        </div>
                        <div class="text-muted mt-1" style="font-size: 11px;">
                            &bull; 100% do crédito será devolvido à sua Carteira Familiar no mesmo instante.
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Voltar / Não Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm">
                        Confirmar Cancelamento e Estornar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalCancelarEl = document.getElementById('modal-cancelar-pedido');
    const modalCancelar = modalCancelarEl ? new bootstrap.Modal(modalCancelarEl) : null;

    const inputId = document.getElementById('cancelar-pedido-id');
    const spanCodigo = document.getElementById('cancelar-codigo');
    const spanAluno = document.getElementById('cancelar-aluno');
    const spanData = document.getElementById('cancelar-data');
    const spanTotal = document.getElementById('cancelar-total');

    document.querySelectorAll('.btn-abrir-cancelamento').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!modalCancelar) return;

            inputId.value = this.dataset.id;
            spanCodigo.textContent = this.dataset.codigo;
            spanAluno.textContent = this.dataset.aluno;
            spanData.textContent = this.dataset.data;
            spanTotal.textContent = this.dataset.total;

            modalCancelar.show();
        });
    });
});
</script>
