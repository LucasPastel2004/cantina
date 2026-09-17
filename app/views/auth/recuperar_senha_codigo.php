<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Recuperação de Senha - Digitação de Código e Nova Senha
 * 
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="auth-header-block text-center mb-3">
    <h2 class="h6 font-weight-bold text-dark mb-1">Redefinir Senha</h2>
    <p class="text-secondary text-microcopy mb-0">
        Digite o código de 6 dígitos recebido no seu WhatsApp e crie uma nova senha.
    </p>
</div>

<form action="<?php echo $baseUrl; ?>/recuperar-senha/redefinir" method="POST" autocomplete="off" class="auth-form" id="form-recuperar-senha-redefinir">
    <?php echo $csrf_field ?? ''; ?>

    <div class="mb-3 text-center">
        <label for="codigo" class="form-label-enterprise d-block">Código de 6 Dígitos</label>
        <input type="text" 
               class="form-control form-control-enterprise font-tabular text-center mx-auto" 
               id="codigo" 
               name="codigo" 
               placeholder="000000" 
               maxlength="6" 
               style="letter-spacing: 8px; font-size: 20px; font-weight: 700; max-width: 220px;" 
               required>
    </div>

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
        <label for="senha_confirmacao" class="form-label-enterprise">Confirmar Nova Senha</label>
        <input type="password" 
               class="form-control form-control-enterprise" 
               id="senha_confirmacao" 
               name="senha_confirmacao" 
               placeholder="Repita a nova senha" 
               minlength="6" 
               required>
    </div>

    <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
        Gravar Nova Senha
    </button>

    <div class="text-center mt-3">
        <a href="<?php echo $baseUrl; ?>/login?tab=familia" class="text-microcopy text-secondary text-decoration-none">
            Cancelar e voltar ao Login
        </a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const codInput = document.getElementById('codigo');
    if (codInput) {
        codInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }
});
</script>
