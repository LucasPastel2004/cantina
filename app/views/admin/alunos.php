<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão e Consulta de Alunos e Matrículas
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl      = $baseUrl ?? '';
$alunos       = $alunos ?? [];
$turmas       = $turmas ?? [];
$responsaveis = $responsaveis ?? [];
$alergenos    = $alergenos ?? [];
$filtros      = $filtros ?? ['busca' => '', 'turma_id' => 0, 'turno' => '', 'status' => 'ativos'];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-microcopy">
                <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>/admin">Administração</a></li>
                <li class="breadcrumb-item active" aria-current="page">Alunos & Matrículas</li>
            </ol>
        </nav>
        <h1 class="h5 font-weight-bold text-dark mb-0">Alunos e Matrículas Escolares</h1>
        <p class="text-secondary text-microcopy mb-0">Consulta cadastral, remanejamento de turmas, vínculo familiar e gestão de status.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/admin/importacao-escolar" class="btn btn-enterprise btn-secondary">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 1.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1-.708.708L8.5 2.707V11.5a.5.5 0 0 1-1 0V2.707L5.354 4.854a.5.5 0 1 1-.708-.708l3-3z"/></svg>
            Importação em Lote
        </a>
        <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModalAlunoNovo()">
            + Novo Aluno
        </button>
    </div>
</div>

<!-- Filtros de Busca e Alta Densidade -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/admin/alunos" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar por aluno, matrícula, responsável ou CPF..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-3">
                <select name="turma_id" class="form-select form-select-sm">
                    <option value="">Todas as Turmas</option>
                    <?php foreach ($turmas as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo ((int)($filtros['turma_id'] ?? 0) === (int)$t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nome_turma'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo ucfirst($t['turno']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="turno" class="form-select form-select-sm">
                    <option value="">Todos os Turnos</option>
                    <option value="matutino" <?php echo (($filtros['turno'] ?? '') === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="vespertino" <?php echo (($filtros['turno'] ?? '') === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="ativos" <?php echo (($filtros['status'] ?? '') === 'ativos') ? 'selected' : ''; ?>>Apenas Ativos</option>
                    <option value="inativos" <?php echo (($filtros['status'] ?? '') === 'inativos') ? 'selected' : ''; ?>>Inativos (Bloqueados)</option>
                    <option value="todos" <?php echo (($filtros['status'] ?? '') === 'todos') ? 'selected' : ''; ?>>Todos os Registros</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm w-100" title="Filtrar">
                    Filtrar
                </button>
                <?php if (!empty($filtros['busca']) || !empty($filtros['turma_id']) || !empty($filtros['turno']) || ($filtros['status'] ?? '') !== 'ativos'): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/alunos" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">
                        ✕
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabela Data-Grid de Alunos -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <h2 class="h6 font-weight-bold text-dark mb-0">Estudantes Matriculados</h2>
        <span class="text-microcopy text-muted font-tabular"><?php echo count($alunos); ?> aluno(s) localizado(s)</span>
    </div>
    <div class="card-enterprise-body p-0">
        <?php if (empty($alunos)): ?>
            <div class="empty-state py-5 text-center">
                <p class="empty-state-text text-muted mb-0">Não há nada para mostrar aqui</p>
                <small class="text-microcopy text-secondary">Nenhum aluno localizado com os filtros selecionados ou nenhuma carga realizada.</small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-enterprise table-data-grid mb-0">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Matrícula</th>
                            <th>Aluno</th>
                            <th>Turma / Turno</th>
                            <th>Responsável Familiar</th>
                            <th>Restrições Alimentares</th>
                            <th class="cell-center" style="width: 90px;">Status</th>
                            <th class="cell-right" style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                            <?php 
                            $isAtivo = ((int)$aluno['ativo'] === 1 && empty($aluno['deleted_at']));
                            ?>
                            <tr class="<?php echo !$isAtivo ? 'opacity-75 bg-light' : ''; ?>">
                                <td>
                                    <code class="code-tag"><?php echo htmlspecialchars($aluno['matricula'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <div class="font-weight-semibold text-dark"><?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy text-muted">ID: #<?php echo $aluno['id']; ?></div>
                                </td>
                                <td>
                                    <div class="font-weight-medium text-dark"><?php echo htmlspecialchars($aluno['nome_turma'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy text-muted"><?php echo ucfirst($aluno['turno']); ?></div>
                                </td>
                                <td>
                                    <div class="text-dark font-weight-medium"><?php echo htmlspecialchars($aluno['responsavel_nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy text-muted font-tabular">
                                        CPF: <?php echo htmlspecialchars($aluno['responsavel_cpf'], ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Whats: <?php echo htmlspecialchars($aluno['responsavel_whatsapp'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($aluno['alergenos_resumo'])): ?>
                                        <span class="tag-alergeno" title="Alérgenos diagnosticados"><?php echo htmlspecialchars($aluno['alergenos_resumo'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        <span class="text-microcopy text-muted">Nenhuma restrição</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-center">
                                    <?php if ($isAtivo): ?>
                                        <span class="status-indicator status-active"><span class="bullet">●</span> Ativo</span>
                                    <?php else: ?>
                                        <span class="status-indicator status-danger"><span class="bullet">●</span> Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Botão Editar -->
                                        <button type="button" class="btn-action" title="Editar dados do aluno" onclick='abrirModalAlunoEditar(<?php echo json_encode($aluno, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/></svg>
                                        </button>

                                        <!-- Botão Inativar / Reativar -->
                                        <?php if ($isAtivo): ?>
                                            <form action="<?php echo $baseUrl; ?>/admin/alunos/inativar" method="POST" class="d-inline m-0" onsubmit="return confirm('Deseja realmente inativar o aluno <?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?>? Novos agendamentos serão bloqueados, mas o histórico permanece intacto.');">
                                                <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                                                <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                                                <button type="submit" class="btn-action btn-action-delete" title="Inativar aluno">
                                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="<?php echo $baseUrl; ?>/admin/alunos/reativar" method="POST" class="d-inline m-0">
                                                <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                                                <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                                                <button type="submit" class="btn-action text-success" title="Reativar aluno">
                                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL DE CADASTRO / EDIÇÃO DE ALUNO -->
<div class="modal fade" id="modalAluno" tabindex="-1" aria-labelledby="modalAlunoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-sm" style="border-radius: 6px;">
            <div class="modal-header border-bottom py-3 px-4">
                <h3 class="modal-title h6 font-weight-bold text-dark mb-0" id="modalAlunoLabel">Cadastrar Aluno</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/alunos/salvar" method="POST">
                <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                <input type="hidden" name="id" id="alunoId" value="0">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="alunoMatricula" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Matrícula Escolar *</label>
                            <input type="text" name="matricula" id="alunoMatricula" required class="form-control form-control-sm font-tabular" placeholder="Ex: MAT-2026-042">
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="alunoNome" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Nome Completo do Estudante *</label>
                            <input type="text" name="nome" id="alunoNome" required class="form-control form-control-sm" placeholder="Nome completo do aluno">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="alunoResponsavelId" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Responsável Familiar (Titular) *</label>
                            <select name="responsavel_id" id="alunoResponsavelId" required class="form-select form-select-sm">
                                <option value="">Selecione o Responsável...</option>
                                <?php foreach ($responsaveis as $resp): ?>
                                    <option value="<?php echo $resp['id']; ?>">
                                        <?php echo htmlspecialchars($resp['nome'], ENT_QUOTES, 'UTF-8'); ?> (CPF: <?php echo htmlspecialchars($resp['cpf'], ENT_QUOTES, 'UTF-8'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="alunoTurmaId" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Turma *</label>
                            <select name="turma_id" id="alunoTurmaId" required class="form-select form-select-sm">
                                <option value="">Selecione a Turma...</option>
                                <?php foreach ($turmas as $t): ?>
                                    <option value="<?php echo $t['id']; ?>">
                                        <?php echo htmlspecialchars($t['nome_turma'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo ucfirst($t['turno']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label for="alunoTurno" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Turno *</label>
                            <select name="turno" id="alunoTurno" required class="form-select form-select-sm">
                                <option value="matutino">Matutino</option>
                                <option value="vespertino">Vespertino</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary d-block mb-1">
                                Restrições Alimentares Diagnosticadas
                            </label>
                            <div class="p-3 border rounded bg-light">
                                <div class="row g-2">
                                    <?php foreach ($alergenos as $alergen): ?>
                                        <div class="col-6 col-md-3">
                                            <div class="form-check">
                                                <input class="form-check-input check-alergeno" type="checkbox" name="alergenos[]" value="<?php echo $alergen['id']; ?>" id="alergenCheck_<?php echo $alergen['id']; ?>">
                                                <label class="form-check-label text-microcopy text-dark" for="alergenCheck_<?php echo $alergen['id']; ?>">
                                                    <?php echo htmlspecialchars($alergen['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <small class="text-microcopy text-muted mt-1 d-block">
                                A seleção dessas opções dispara alertas preventivos nos agendamentos de lanches e na emissão dos tickets térmicos.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top py-2 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-enterprise btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary">Gravar Aluno</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalAlunoNovo() {
    document.getElementById('modalAlunoLabel').innerText = 'Cadastrar Aluno';
    document.getElementById('alunoId').value = '0';
    document.getElementById('alunoMatricula').value = '';
    document.getElementById('alunoNome').value = '';
    document.getElementById('alunoResponsavelId').value = '';
    document.getElementById('alunoTurmaId').value = '';
    document.getElementById('alunoTurno').value = 'matutino';

    document.querySelectorAll('.check-alergeno').forEach(function(chk) {
        chk.checked = false;
    });

    var modal = new bootstrap.Modal(document.getElementById('modalAluno'));
    modal.show();
}

function abrirModalAlunoEditar(aluno) {
    document.getElementById('modalAlunoLabel').innerText = 'Editar Aluno — ' + aluno.nome;
    document.getElementById('alunoId').value = aluno.id;
    document.getElementById('alunoMatricula').value = aluno.matricula;
    document.getElementById('alunoNome').value = aluno.nome;
    document.getElementById('alunoResponsavelId').value = aluno.responsavel_id;
    document.getElementById('alunoTurmaId').value = aluno.turma_id;
    document.getElementById('alunoTurno').value = aluno.turno;

    // Desmarca todos e marca os do aluno
    document.querySelectorAll('.check-alergeno').forEach(function(chk) {
        chk.checked = false;
    });

    if (aluno.alergenos_ids && Array.isArray(aluno.alergenos_ids)) {
        aluno.alergenos_ids.forEach(function(aid) {
            var el = document.getElementById('alergenCheck_' + aid);
            if (el) el.checked = true;
        });
    }

    var modal = new bootstrap.Modal(document.getElementById('modalAluno'));
    modal.show();
}
</script>
