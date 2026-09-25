<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão Ativa de Restrições Alimentares dos Filhos
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl           = $baseUrl ?? '';
$dependentes       = $dependentes ?? [];
$alunoSelecionado  = $alunoSelecionado ?? [];
$catalogoAlergenos = $catalogoAlergenos ?? [];
$alergenosDoAluno  = $alergenosDoAluno ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-microcopy">
                <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>/portal">Portal da Família</a></li>
                <li class="breadcrumb-item active" aria-current="page">Restrições Alimentares</li>
            </ol>
        </nav>
        <h1 class="h5 font-weight-bold text-dark mb-0">Restrições Alimentares e Alergias</h1>
        <p class="text-secondary text-microcopy mb-0">Informe os ingredientes ou alérgenos diagnosticados dos seus dependentes para prevenção automática.</p>
    </div>
    <div>
        <a href="<?php echo $baseUrl; ?>/portal" class="btn btn-enterprise btn-primary">
            &larr; Voltar ao Início
        </a>
    </div>
</div>

<!-- Informativo de Segurança Nutricional -->
<div class="alert alert-warning py-3 px-4 mb-4" style="background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e;">
    <div class="d-flex align-items-start gap-2">
        <svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" class="mt-1 flex-shrink-0">
            <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
            <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
        </svg>
        <div>
            <strong class="d-block mb-1">Segurança Alimentar da Cantina</strong>
            <p class="mb-0 text-microcopy" style="font-size: 13px;">
                Os alérgenos assinalados abaixo acionam <strong>avisos preventivos automáticos</strong> ao tentar agendar produtos incompatíveis no cardápio e geram a identificação indelével <code>[ATENÇÃO: CONTÉM ...]</code> impressa no ticket térmico entregue no balcão.
            </p>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Seletor de Dependente (Caso haja mais de um) -->
    <div class="col-12 col-md-4">
        <div class="card-enterprise mb-3">
            <div class="card-enterprise-header">
                <h2 class="h6 font-weight-bold text-dark mb-0">Seus Dependentes</h2>
            </div>
            <div class="card-enterprise-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($dependentes as $dep): ?>
                        <?php $isSel = ((int)$dep['id'] === (int)$alunoSelecionado['id']); ?>
                        <a href="<?php echo $baseUrl; ?>/portal/alergias?aluno=<?php echo $dep['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3 <?php echo $isSel ? 'bg-light border-start border-primary border-3' : ''; ?>">
                            <div>
                                <div class="font-weight-semibold <?php echo $isSel ? 'text-primary' : 'text-dark'; ?>">
                                    <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="text-microcopy text-muted font-tabular">
                                    Turma: <?php echo htmlspecialchars($dep['nome_turma'] ?? 'Turma', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                    Matrícula: <?php echo htmlspecialchars($dep['matricula'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <?php if ($isSel): ?>
                                <span class="badge bg-primary text-white" style="font-size: 11px;">Selecionado</span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Alérgenos do Dependente Selecionado -->
    <div class="col-12 col-md-8">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-0">
                        Alérgenos de: <?php echo htmlspecialchars($alunoSelecionado['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <span class="text-microcopy text-muted">
                        Turma: <?php echo htmlspecialchars($alunoSelecionado['nome_turma'] ?? '', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                        Turno: <?php echo ucfirst($alunoSelecionado['turno'] ?? 'matutino'); ?>
                    </span>
                </div>
                <div>
                    <span class="status-indicator <?php echo !empty($alergenosDoAluno) ? 'status-warning' : 'status-active'; ?>">
                        <span class="bullet">●</span> 
                        <?php echo count($alergenosDoAluno); ?> alérgeno(s) ativo(s)
                    </span>
                </div>
            </div>
            <div class="card-enterprise-body p-4">
                <form action="<?php echo $baseUrl; ?>/portal/alergias/salvar" method="POST">
                    <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                    <input type="hidden" name="aluno_id" value="<?php echo $alunoSelecionado['id']; ?>">

                    <div class="row g-3 mb-4">
                        <?php foreach ($catalogoAlergenos as $alerg): ?>
                            <?php 
                            $marcado = in_array((int)$alerg['id'], $alergenosDoAluno, true);
                            ?>
                            <div class="col-12 col-sm-6">
                                <label class="p-3 border rounded d-flex align-items-start gap-3 h-100 cursor-pointer transition-all" style="background-color: <?php echo $marcado ? '#fffbeb' : '#ffffff'; ?>; border-color: <?php echo $marcado ? '#fde68a' : '#e2e8f0'; ?> !important;" for="alerg_<?php echo $alerg['id']; ?>">
                                    <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="alergenos[]" value="<?php echo $alerg['id']; ?>" id="alerg_<?php echo $alerg['id']; ?>" <?php echo $marcado ? 'checked' : ''; ?>>
                                    <div class="flex-grow-1">
                                        <div class="font-weight-semibold text-dark mb-1">
                                            <?php echo htmlspecialchars($alerg['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                        <div class="text-microcopy text-muted" style="font-size: 12px; line-height: 1.4;">
                                            <?php echo htmlspecialchars($alerg['descricao'] ?? 'Alergia ou intolerância alimentar diagnosticada.', ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="text-microcopy text-muted">
                            As alterações entram em vigor imediatamente para os próximos agendamentos.
                        </span>
                        <button type="submit" class="btn btn-enterprise btn-primary">
                            Salvar Restrições Alimentares
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
