<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Colaboradores e Professores (Desconto em Folha)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$colaboradores = $colaboradores ?? [];
$busca = $busca ?? '';
$csrfField = $csrf_field ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Colaboradores & Professores</h1>
        <p class="text-secondary text-microcopy mb-0">
            Profissionais autorizados ao consumo na cantina sob a modalidade Desconto em Folha.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModalColaborador()">
            + Novo Colaborador
        </button>
    </div>
</div>

<!-- Barra de Busca -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/admin/colaboradores" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-8 col-md-6">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Buscar por Nome, Matrícula ou Cargo
                </label>
                <input type="text" class="form-control form-control-sm" id="busca" name="busca" 
                       placeholder="Digite o nome, matrícula ou cargo..." value="<?php echo htmlspecialchars($busca, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12 col-sm-4 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm flex-grow-1">
                    Buscar
                </button>
                <?php if (!empty($busca)): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/colaboradores" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Busca">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Colaboradores -->
<div class="card-enterprise">
    <div class="card-enterprise-body p-0">
        <?php if (empty($colaboradores)): ?>
            <?php 
            $emptyMessage = 'Nenhum colaborador encontrado no momento.';
            require __DIR__ . '/../partials/empty_state.php'; 
            ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 48px;">Foto</th>
                            <th class="cell-center" style="width: 120px;">Matrícula</th>
                            <th class="cell-left">Nome Completo</th>
                            <th class="cell-left">Cargo / Função</th>
                            <th class="cell-center" style="width: 110px;">Status</th>
                            <th class="cell-right" style="width: 140px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colaboradores as $c): ?>
                            <tr>
                                <td class="cell-center py-1">
                                    <?php if (!empty($c['foto_url'])): ?>
                                        <img src="<?php echo $baseUrl . '/' . htmlspecialchars($c['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="<?php echo htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8'); ?>" 
                                             width="32" height="32" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; box-shadow: 0 0 0 1px #cbd5e1;">
                                    <?php else: ?>
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #64748b;">
                                            <?php echo mb_strtoupper(mb_substr($c['nome'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-center">
                                    <code class="code-tag font-tabular"><?php echo htmlspecialchars($c['matricula_funcional'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td class="cell-left font-weight-semibold text-dark">
                                    <?php echo htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-left text-secondary" style="font-size: 12px;">
                                    <?php echo htmlspecialchars($c['cargo'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="cell-center">
                                    <?php if (!empty($c['ativo'])): ?>
                                        <span class="status-indicator status-active">
                                            <span class="bullet" aria-hidden="true">●</span> Ativo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-indicator status-inactive">
                                            <span class="bullet" aria-hidden="true">●</span> Inativo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" 
                                                onclick='editarColaborador(<?php echo json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                title="Editar Colaborador">
                                            Editar
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/colaboradores/excluir" method="POST" class="d-inline m-0"
                                              onsubmit="return confirm('Deseja realmente inativar este colaborador? O histórico de compras em folha será preservado.');">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                                            <button type="submit" class="btn btn-enterprise btn-danger btn-sm" title="Excluir Colaborador">
                                                Excluir
                                            </button>
                                        </form>
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

<!-- Modal de Cadastro e Edição de Colaborador -->
<div class="modal fade" id="modalColaborador" tabindex="-1" aria-labelledby="modalColaboradorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 6px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <form action="<?php echo $baseUrl; ?>/admin/colaboradores/salvar" method="POST" enctype="multipart/form-data" class="m-0">
                <?php echo $csrfField; ?>
                <input type="hidden" name="id" id="colab_id" value="0">

                <div class="modal-header py-3 px-4" style="border-bottom: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <h5 class="modal-title font-weight-bold text-dark" id="modalColaboradorLabel" style="font-size: 15px;">
                        Novo Colaborador
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="colab_matricula" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Matrícula Funcional <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" id="colab_matricula" name="matricula_funcional" 
                                   required maxlength="30" placeholder="Ex: FUNC-104">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="colab_ativo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Status no Sistema
                            </label>
                            <select class="form-select form-select-sm" id="colab_ativo" name="ativo">
                                <option value="1">Ativo (Permite compra em folha)</option>
                                <option value="0">Inativo (Bloqueado no balcão)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="colab_nome" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Nome Completo do Colaborador <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="colab_nome" name="nome" 
                               required maxlength="100" placeholder="Ex: Maria Clara Fernandes">
                    </div>

                    <div class="mb-3">
                        <label for="colab_cargo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Cargo / Função na Escola <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="colab_cargo" name="cargo" 
                               required maxlength="50" placeholder="Ex: Professora de Matemática, Inspetor...">
                    </div>

                    <div class="mb-2">
                        <label for="colab_foto" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Foto de Identificação (Para conferência no PDV)
                        </label>
                        <input type="file" class="form-control form-control-sm" id="colab_foto" name="foto" accept=".jpg,.jpeg,.png,.webp">
                        <span class="text-muted" style="font-size: 11px;">Exibida no caixa do balcão para prevenir fraudes presenciais.</span>
                    </div>
                </div>

                <div class="modal-footer py-2 px-4" style="border-top: 1px solid #e2e8f0; background-color: #f8fafc;">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Salvar Colaborador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalColaborador() {
    document.getElementById('colab_id').value = '0';
    document.getElementById('colab_matricula').value = '';
    document.getElementById('colab_nome').value = '';
    document.getElementById('colab_cargo').value = '';
    document.getElementById('colab_ativo').value = '1';
    document.getElementById('colab_foto').value = '';
    document.getElementById('modalColaboradorLabel').innerText = 'Novo Colaborador';
    
    var modalEl = document.getElementById('modalColaborador');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function editarColaborador(c) {
    document.getElementById('colab_id').value = c.id;
    document.getElementById('colab_matricula').value = c.matricula_funcional;
    document.getElementById('colab_nome').value = c.nome;
    document.getElementById('colab_cargo').value = c.cargo;
    document.getElementById('colab_ativo').value = c.ativo;
    document.getElementById('colab_foto').value = '';
    document.getElementById('modalColaboradorLabel').innerText = 'Editar Colaborador: ' + c.nome;
    
    var modalEl = document.getElementById('modalColaborador');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}
</script>
