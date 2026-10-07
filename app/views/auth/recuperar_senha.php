<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Recuperação de Senha - Solicitação de Código via WhatsApp
 * 
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="auth-header-block text-center mb-3">
    <h2 class="h6 font-weight-bold text-dark mb-1">Recuperar Senha de Acesso</h2>
    <p class="text-secondary text-microcopy mb-0">
        Informe o CPF do titular cadastrado. Um código de segurança de 6 dígitos será enviado ao WhatsApp registrado.
    </p>
</div>

<form action="<?php echo $baseUrl; ?>/recuperar-senha/solicitar" method="POST" autocomplete="off" class="auth-form" id="form-recuperar-senha">
    <?php echo $csrf_field ?? ''; ?>

    <div class="mb-3">
        <label for="cpf" class="form-label-enterprise">CPF do Titular</label>
        <input type="text" 
               class="form-control form-control-enterprise font-tabular" 
               id="cpf" 
               name="cpf" 
               placeholder="000.000.000-00" 
               maxlength="14" 
               required>
    </div>

    <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
        Enviar Código de Recuperação
    </button>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <a href="<?php echo $baseUrl; ?>/login?tab=familia" class="text-microcopy text-secondary text-decoration-none">
            &larr; Voltar para o Login
        </a>
        <a href="<?php echo $baseUrl; ?>/primeiro-acesso" class="text-microcopy text-secondary text-decoration-none">
            Primeiro Acesso &rarr;
        </a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            e.target.value = v;
        });
    }
});
</script>
