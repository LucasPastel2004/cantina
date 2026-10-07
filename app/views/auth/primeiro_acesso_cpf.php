<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Primeiro Acesso - Etapa 1: Inserção do CPF
 * 
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$cpfPreenchido = $cpfPreenchido ?? '';
?>

<div class="auth-header-block text-center mb-3">
    <h2 class="h6 font-weight-bold text-dark mb-1">Primeiro Acesso ao Portal</h2>
    <p class="text-secondary text-microcopy mb-0">
        Informe o CPF do titular cadastrado na matrícula escolar para receber o código de 6 dígitos via WhatsApp.
    </p>
</div>

<form action="<?php echo $baseUrl; ?>/primeiro-acesso/solicitar" method="POST" autocomplete="off" class="auth-form" id="form-primeiro-acesso-cpf">
    <?php echo $csrf_field ?? ''; ?>

    <div class="mb-3">
        <label for="cpf" class="form-label-enterprise">CPF do Responsável</label>
        <input type="text" 
               class="form-control form-control-enterprise font-tabular" 
               id="cpf" 
               name="cpf" 
               value="<?php echo $cpfPreenchido; ?>" 
               placeholder="000.000.000-00" 
               maxlength="14" 
               required>
        <div class="text-microcopy text-muted mt-1">Apenas os números ou no formato com pontos e traço.</div>
    </div>

    <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
        Enviar Código por WhatsApp
    </button>

    <div class="text-center mt-3">
        <a href="<?php echo $baseUrl; ?>/login?tab=familia" class="text-microcopy text-secondary text-decoration-none">
            &larr; Voltar para o Login
        </a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        function formatarCpf(val) {
            let v = val.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            return v;
        }

        cpfInput.addEventListener('input', function(e) {
            e.target.value = formatarCpf(e.target.value);
        });

        if (cpfInput.value) {
            cpfInput.value = formatarCpf(cpfInput.value);
        }
    }
});
</script>
