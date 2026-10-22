<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Turmas Escolares
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$turmas = $turmas ?? [];
$turno = $turno ?? '';
$busca = $busca ?? '';
$csrfField = $csrf_field ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Turmas Escolares</h1>
        <p class="text-secondary text-microcopy mb-0">
            Agrupamento de estudantes e sequenciamento de impressão dos tickets térmicos na bobina de 80mm.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModalTurma()">
            + Nova Turma
        </button>
    </div>
</div>

<!-- Barra de Filtros -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/admin/turmas" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="filtrar" value="1">
            <div class="col-12 col-sm-6 col-md-5">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Buscar por Turma ou Série
                </label>
                <input type="text" class="form-control form-control-sm" id="busca" name="busca" 
                       placeholder="Ex: 6º Ano, Ensino Médio..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-sm-3 col-md-3">
                <label for="filtro_turno" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Turno
                </label>
                <select class="form-select form-select-sm" id="filtro_turno" name="turno">
                    <option value="">Todos os Turnos</option>
                    <option value="matutino" <?php echo ($turno === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="vespertino" <?php echo ($turno === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1">
                    Filtrar
                </button>
                <?php if (!empty($jaFiltrado)): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/turmas" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Turmas -->
<div class="card-enterprise">
    <div class="card-enterprise-body p-0">
        <?php if (empty($jaFiltrado)): ?>
            <?php 
            $emptyMessage = 'Não há nada para mostrar aqui';
            $emptyDescription = 'Utilize os filtros acima e clique em "Filtrar" para exibir as turmas escolares.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php elseif (empty($turmas)): ?>
            <?php 
            $emptyMessage = 'Nenhuma turma cadastrada ou encontrada';
            $emptyDescription = 'Nenhuma turma localizada com os filtros selecionados.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 80px;">Ordem</th>
                            <th class="cell-left" style="width: 200px;">Série / Segmento</th>
                            <th class="cell-left">Identificação da Turma</th>
                            <th class="cell-center" style="width: 120px;">Turno</th>
                            <th class="cell-right" style="width: 140px;">Alunos Ativos</th>
                            <th class="cell-right" style="width: 140px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($turmas as $t): ?>
                            <tr>
                                <td class="cell-center font-tabular text-muted">
                                    <?php echo (int)$t['ordem_impressao']; ?>
                                </td>
                                <td class="cell-left text-secondary" style="font-size: 12px;">
                                    <?php echo htmlspecialchars($t['serie'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-left font-weight-semibold text-dark">
                                    <?php echo htmlspecialchars($t['nome_turma'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-center">
                                    <span class="code-tag">
                                        <?php echo ($t['turno'] === 'matutino') ? 'Manhã' : 'Tarde'; ?>
                                    </span>
                                </td>
                                <td class="cell-right font-tabular">
                                    <?php echo (int)($t['total_alunos'] ?? 0); ?> aluno(s)
                                </td>
                                <td class="cell-right">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn-action" 
                                                onclick='editarTurma(<?php echo json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                title="Editar Turma">
                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                            </svg>
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/turmas/excluir" method="POST" class="d-inline m-0"
                                              data-confirm="Deseja realmente inativar esta turma? Os históricos escolares de matrículas serão mantidos."
                                              data-confirm-title="Inativar Turma"
                                              data-confirm-type="danger"
                                              data-confirm-btn="Sim, Inativar">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>">
                                            <button type="submit" class="btn-action btn-action-delete" title="Excluir Turma">
                                                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php require __DIR__ . '/../partials/pagination.php'; ?>
        <?php endif; ?>

    </div>
</div>

<!-- Modal de Cadastro e Edição de Turma -->
<div class="modal fade" id="modalTurma" tabindex="-1" aria-labelledby="modalTurmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <form action="<?php echo $baseUrl; ?>/admin/turmas/salvar" method="POST" class="m-0">
                <?php echo $csrfField; ?>
                <input type="hidden" name="id" id="turma_id" value="0">

                <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <h5 class="modal-title font-weight-bold text-dark" id="modalTurmaLabel" style="font-size: 15px;">
                        Nova Turma Escolar
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="turma_serie" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Série / Segmento Escolar <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="turma_serie" name="serie" 
                               required maxlength="50" placeholder="Ex: Ensino Fundamental II, 6º Ano...">
                    </div>

                    <div class="mb-3">
                        <label for="turma_nome" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Nome da Turma <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="turma_nome" name="nome_turma" 
                               required maxlength="50" placeholder="Ex: 6º Ano A, 1º Ano EM...">
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label for="turma_turno" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Turno Escolar <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="turma_turno" name="turno" required>
                                <option value="matutino">Matutino (Manhã)</option>
                                <option value="vespertino">Vespertino (Tarde)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="turma_ordem" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Ordem de Impressão (Bobina)
                            </label>
                            <input type="number" class="form-control form-control-sm font-tabular" id="turma_ordem" name="ordem_impressao" value="0" min="0">
                            <span class="text-muted" style="font-size: 10px;">Sequência de corte</span>
                        </div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-4" style="border-top: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Salvar Turma</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalTurma() {
    document.getElementById('turma_id').value = '0';
    document.getElementById('turma_serie').value = '';
    document.getElementById('turma_nome').value = '';
    document.getElementById('turma_turno').value = 'matutino';
    document.getElementById('turma_ordem').value = '0';
    document.getElementById('modalTurmaLabel').innerText = 'Nova Turma Escolar';
    
    var modalEl = document.getElementById('modalTurma');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function editarTurma(t) {
    document.getElementById('turma_id').value = t.id;
    document.getElementById('turma_serie').value = t.serie;
    document.getElementById('turma_nome').value = t.nome_turma;
    document.getElementById('turma_turno').value = t.turno;
    document.getElementById('turma_ordem').value = t.ordem_impressao;
    document.getElementById('modalTurmaLabel').innerText = 'Editar Turma: ' + t.nome_turma;
    
    var modalEl = document.getElementById('modalTurma');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}
</script>
