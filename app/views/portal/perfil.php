<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Perfil e Dados Cadastrais do Responsável Familiar
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$responsavel = $responsavel ?? [];
$dependentes = $dependentes ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Meus Dados Cadastrais</h1>
        <p class="text-secondary text-microcopy mb-0">Informações da conta familiar e dependentes vinculados na instituição.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/portal" class="btn btn-enterprise btn-secondary">
            &larr; Voltar ao Início
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Dados do Titular -->
    <div class="col-12 col-md-6">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Titular da Conta Familiar</span>
                <span class="status-indicator status-success"><span class="bullet">●</span> Ativo</span>
            </div>
            <div class="card-enterprise-body">
                <div class="mb-3">
                    <span class="text-microcopy text-muted d-block">Nome Completo</span>
                    <strong class="text-dark" style="font-size: 15px;"><?php echo htmlspecialchars($responsavel['nome'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <span class="text-microcopy text-muted d-block">CPF (Login)</span>
                        <span class="font-tabular text-dark fw-semibold">
                            <?php 
                            $cpf = preg_replace('/\D/', '', $responsavel['cpf'] ?? '');
                            if (strlen($cpf) === 11) {
                                echo substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                            } else {
                                echo htmlspecialchars($responsavel['cpf'] ?? '—', ENT_QUOTES, 'UTF-8');
                            }
                            ?>
                        </span>
                    </div>
                    <div class="col-6">
                        <span class="text-microcopy text-muted d-block">WhatsApp Transacional</span>
                        <span class="font-tabular text-dark fw-semibold"><?php echo htmlspecialchars($responsavel['whatsapp'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="text-microcopy text-muted d-block">E-mail de Contato</span>
                    <span class="text-dark"><?php echo htmlspecialchars($responsavel['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <div class="p-3 bg-light rounded-2 border">
                    <span class="text-microcopy text-muted d-block mb-1">Adesão aos Termos de Uso</span>
                    <div class="text-microcopy text-secondary">
                        <?php if (!empty($responsavel['termos_aceitos_em'])): ?>
                            <span class="text-success fw-semibold">&check; Termos e Políticas aceitos em <?php echo date('d/m/Y \à\s H:i', strtotime($responsavel['termos_aceitos_em'])); ?>.</span>
                        <?php else: ?>
                            <span class="text-muted">Termos ainda não formalizados.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dependentes Vinculados -->
    <div class="col-12 col-md-6">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Dependentes Vinculados</span>
                <span class="text-microcopy text-muted font-tabular"><?php echo count($dependentes); ?> aluno(s)</span>
            </div>
            <div class="card-enterprise-body">
                <?php if (empty($dependentes)): ?>
                    <div class="text-center py-4 text-muted text-microcopy">
                        <p class="mb-0">Nenhum dependente vinculado no momento.</p>
                        <small>A vinculação de alunos é realizada pela secretaria escolar.</small>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($dependentes as $dep): ?>
                            <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="text-microcopy text-muted font-tabular">
                                        Matrícula: <?php echo htmlspecialchars($dep['matricula'], ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turma: <?php echo htmlspecialchars($dep['nome_turma'] ?? 'A definir', ENT_QUOTES, 'UTF-8'); ?> &bull; 
                                        Turno: <?php echo ucfirst($dep['turno'] ?? 'matutino'); ?>
                                    </div>
                                </div>
                                <a href="<?php echo $baseUrl; ?>/portal/alergias?aluno=<?php echo $dep['id']; ?>" class="btn btn-enterprise btn-secondary btn-sm">
                                    Restrições
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-4 p-3 bg-light rounded-2 border">
                    <span class="text-microcopy text-muted d-block mb-1">Atualização Cadastral</span>
                    <p class="text-microcopy text-secondary mb-0">
                        Para inclusão de novos dependentes, transferência de turma ou alteração de CPF, favor procurar a secretaria do Colégio Monteiro Lobato.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Segunda Linha: Segurança de Acesso & Alteração de Senha -->
<div class="row g-4 mt-1">
    <!-- Card de Alteração de Senha -->
    <div class="col-12 col-md-6">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Segurança de Acesso</span>
                <span class="text-microcopy text-muted">Alteração de Senha</span>
            </div>
            <div class="card-enterprise-body">
                <form action="<?php echo $baseUrl; ?>/portal/perfil/senha" method="POST" id="form-alterar-senha">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label for="senha_atual" class="form-label text-microcopy text-muted mb-1">
                            Senha Atual <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control form-control-sm" 
                               id="senha_atual" 
                               name="senha_atual" 
                               required 
                               autocomplete="current-password" 
                               placeholder="Digite sua senha atual">
                    </div>

                    <div class="mb-3">
                        <label for="nova_senha" class="form-label text-microcopy text-muted mb-1">
                            Nova Senha <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control form-control-sm" 
                               id="nova_senha" 
                               name="nova_senha" 
                               required 
                               minlength="6" 
                               autocomplete="new-password" 
                               placeholder="Mínimo de 6 caracteres">
                        <small class="text-microcopy text-muted">Use no mínimo 6 caracteres alfanuméricos.</small>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar_senha" class="form-label text-microcopy text-muted mb-1">
                            Confirmar Nova Senha <span class="text-danger">*</span>
                        </label>
                        <input type="password" 
                               class="form-control form-control-sm" 
                               id="confirmar_senha" 
                               name="confirmar_senha" 
                               required 
                               minlength="6" 
                               autocomplete="new-password" 
                               placeholder="Repita a nova senha">
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="text-microcopy text-muted">
                            A alteração entra em vigor imediatamente.
                        </span>
                        <button type="submit" class="btn btn-enterprise btn-primary">
                            Atualizar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Informações sobre Políticas de Acesso -->
    <div class="col-12 col-md-6">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Como Funciona o Acesso</span>
                <span class="status-indicator status-active"><span class="bullet">●</span> Protegido</span>
            </div>
            <div class="card-enterprise-body">
                <div class="mb-3">
                    <strong class="text-dark d-block mb-1" style="font-size: 14px;">Autenticação e Senha</strong>
                    <p class="text-secondary text-microcopy mb-0">
                        O acesso ao Portal da Família é individual por responsável legal, autenticado pelo CPF e senha criptografada em padrão Bcrypt.
                    </p>
                </div>

                <div class="p-3 bg-light rounded-2 border mb-3">
                    <span class="text-microcopy text-dark d-block mb-1 fw-bold">Esqueceu sua senha?</span>
                    <p class="text-secondary text-microcopy mb-0">
                        Caso esqueça sua senha de acesso, na tela inicial de login clique em <strong>"Esqueci minha senha"</strong>. Um código temporário de verificação de 6 dígitos será despachado para o seu WhatsApp cadastrado, permitindo redefini-la rapidamente.
                    </p>
                </div>

                <div class="p-3 bg-light rounded-2 border">
                    <span class="text-microcopy text-dark d-block mb-1 fw-bold">Dicas de Segurança</span>
                    <ul class="text-secondary text-microcopy mb-0 ps-3">
                        <li>Não forneça sua senha ou códigos recebidos no WhatsApp para outras pessoas.</li>
                        <li>Ao utilizar computadores compartilhados, lembre-se de clicar em <strong>"Sair"</strong> ao concluir seus pedidos.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

