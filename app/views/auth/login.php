<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Tela de Login Unificada (Portal da Família & Equipe da Cantina)
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$tab = $tab ?? 'familia';
?>

<!-- Alternador Sóbrio de Contexto (Família vs Equipe) -->
<div class="auth-tabs d-flex border-bottom mb-4">
    <a href="<?php echo $baseUrl; ?>/login?tab=familia" 
       class="auth-tab-item flex-fill text-center py-2 px-3 text-decoration-none font-weight-semibold <?php echo $tab === 'familia' ? 'active' : ''; ?>">
        <span class="d-inline-block py-1">Portal da Família</span>
    </a>
    <a href="<?php echo $baseUrl; ?>/login?tab=equipe" 
       class="auth-tab-item flex-fill text-center py-2 px-3 text-decoration-none font-weight-semibold <?php echo $tab === 'equipe' ? 'active' : ''; ?>">
        <span class="d-inline-block py-1">Equipe da Cantina</span>
    </a>
</div>

<?php if ($tab === 'familia'): ?>
    <!-- Formulário de Login do Responsável (Portal da Família) -->
    <form action="<?php echo $baseUrl; ?>/login" method="POST" autocomplete="off" class="auth-form" id="form-login-familia">
        <?php echo $csrf_field ?? ''; ?>
        <input type="hidden" name="tipo_acesso" value="familia">

        <div class="mb-3">
            <label for="cpf" class="form-label-enterprise">CPF do Responsável</label>
            <input type="text" 
                   class="form-control form-control-enterprise font-tabular" 
                   id="cpf" 
                   name="cpf" 
                   placeholder="000.000.000-00" 
                   maxlength="14" 
                   required 
                   autofocus>
            <div class="text-microcopy text-muted mt-1">Informe o CPF cadastrado na matrícula escolar.</div>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="senha" class="form-label-enterprise mb-0">Senha de Acesso</label>
                <a href="<?php echo $baseUrl; ?>/recuperar-senha" class="text-microcopy text-primary text-decoration-none">Esqueceu a senha?</a>
            </div>
            <input type="password" 
                   class="form-control form-control-enterprise" 
                   id="senha" 
                   name="senha" 
                   placeholder="••••••••" 
                   required>
        </div>

        <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2 mt-2">
            Entrar no Portal
        </button>

        <div class="auth-divider my-3 text-center position-relative">
            <span class="bg-white px-2 text-microcopy text-muted">Primeira vez na plataforma?</span>
        </div>

        <a href="<?php echo $baseUrl; ?>/primeiro-acesso" class="btn btn-enterprise btn-secondary w-100 py-2 text-center text-decoration-none">
            Ativar Primeiro Acesso com WhatsApp
        </a>
    </form>
<?php else: ?>
    <!-- Formulário de Login da Equipe Interna (Operador / Admin) -->
    <form action="<?php echo $baseUrl; ?>/login" method="POST" autocomplete="off" class="auth-form" id="form-login-equipe">
        <?php echo $csrf_field ?? ''; ?>
        <input type="hidden" name="tipo_acesso" value="equipe">

        <div class="mb-3">
            <label for="usuario" class="form-label-enterprise">Identificador de Usuário</label>
            <input type="text" 
                   class="form-control form-control-enterprise" 
                   id="usuario" 
                   name="usuario" 
                   placeholder="ex: operador-maria ou admin" 
                   required 
                   autofocus>
            <div class="text-microcopy text-muted mt-1">Identificador individual fornecido pela administração.</div>
        </div>

        <div class="mb-3">
            <label for="senha" class="form-label-enterprise">Senha do Operador</label>
            <input type="password" 
                   class="form-control form-control-enterprise" 
                   id="senha" 
                   name="senha" 
                   placeholder="••••••••" 
                   required>
        </div>

        <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2 mt-2">
            Acessar Painel Operacional
        </button>
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara automática de CPF no input
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
