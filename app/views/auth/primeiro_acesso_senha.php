<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Primeiro Acesso - Etapa 3: Definição de Senha e Aceite dos Termos
 * 
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$nome = $nome ?? 'Responsável';
$primeiroNome = explode(' ', trim($nome))[0];
?>

<div class="auth-header-block text-center mb-3">
    <h2 class="h6 font-weight-bold text-dark mb-1">Crie sua Senha de Acesso</h2>
    <p class="text-secondary text-microcopy mb-0">
        Olá, <strong><?php echo htmlspecialchars($primeiroNome, ENT_QUOTES, 'UTF-8'); ?></strong>! Defina a senha para acessar o Portal da Família.
    </p>
</div>

<form action="<?php echo $baseUrl; ?>/primeiro-acesso/concluir" method="POST" autocomplete="off" class="auth-form" id="form-primeiro-acesso-senha">
    <?php echo $csrf_field ?? ''; ?>

    <div class="mb-3">
        <label for="senha" class="form-label-enterprise">Nova Senha</label>
        <input type="password" 
               class="form-control form-control-enterprise" 
               id="senha" 
               name="senha" 
               placeholder="Mínimo 6 caracteres" 
               minlength="6" 
               required>
    </div>

    <div class="mb-3">
        <label for="senha_confirmacao" class="form-label-enterprise">Confirmar Senha</label>
        <input type="password" 
               class="form-control form-control-enterprise" 
               id="senha_confirmacao" 
               name="senha_confirmacao" 
               placeholder="Repita a nova senha" 
               minlength="6" 
               required>
    </div>

    <!-- Aceite Formal dos Termos e Políticas da Cantina -->
    <div class="card p-3 mb-3 bg-light border" style="border-radius: 4px;">
        <div class="form-check d-flex align-items-start gap-2 mb-0">
            <input class="form-check-input mt-1" type="checkbox" name="aceite_termos" id="aceite_termos" value="1" required>
            <label class="form-check-label text-microcopy text-dark" for="aceite_termos">
                Declaro que li e concordo integralmente com os 
                <a href="<?php echo $baseUrl; ?>/termos" target="_blank" class="text-primary font-weight-semibold text-decoration-none">
                    Termos de Uso e Políticas da Cantina
                </a> do Colégio Monteiro Lobato.
            </label>
        </div>
    </div>

    <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
        Concluir Ativação e Entrar
    </button>
</form>

<div class="text-center mt-3">
    <a href="<?php echo $baseUrl; ?>/login?tab=familia" class="text-microcopy text-secondary text-decoration-none">
        Cancelar e voltar ao Login
    </a>
</div>
