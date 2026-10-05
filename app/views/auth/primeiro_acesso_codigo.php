<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Primeiro Acesso - Etapa 2: Digitação do Código de 6 Dígitos
 * 
 * Conforme docs/DESIGN.md e docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$maskedPhone = $maskedPhone ?? '';
$mockCode = $mockCode ?? null;
$codigoBloqueado = $codigoBloqueado ?? false;
?>

<div class="auth-header-block text-center mb-3">
    <h2 class="h6 font-weight-bold text-dark mb-1">Código de Confirmação</h2>
    <p class="text-secondary text-microcopy mb-0">
        Enviamos um código de 6 dígitos para o WhatsApp <strong><?php echo htmlspecialchars($maskedPhone, ENT_QUOTES, 'UTF-8'); ?></strong>.
    </p>
</div>

<?php if ($mockCode): ?>
    <div class="alert alert-secondary py-2 px-3 mb-3 text-microcopy text-center border font-tabular" style="background-color: #f1f5f9; color: #334155;">
        <span>Ambiente Local &bull; Código gerado: <strong><?php echo htmlspecialchars($mockCode, ENT_QUOTES, 'UTF-8'); ?></strong></span>
    </div>
<?php endif; ?>

<?php if ($codigoBloqueado): ?>
    <div class="alert alert-warning py-3 px-3 mb-3 text-center border">
        <div class="font-weight-bold text-dark mb-1" style="font-size: 14px;">Código Bloqueado</div>
        <p class="text-microcopy text-secondary mb-3">
            O limite de 5 tentativas foi excedido. Por motivos de segurança, clique no botão abaixo para receber um novo código no seu WhatsApp.
        </p>
        <form action="<?php echo $baseUrl; ?>/primeiro-acesso/reenviar" method="POST">
            <?php echo $csrf_field ?? ''; ?>
            <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
                Solicitar Novo Código por WhatsApp
            </button>
        </form>
    </div>
<?php else: ?>
    <form action="<?php echo $baseUrl; ?>/primeiro-acesso/validar-codigo" method="POST" autocomplete="off" class="auth-form" id="form-primeiro-acesso-codigo">
        <?php echo $csrf_field ?? ''; ?>

        <div class="mb-3 text-center">
            <label for="codigo" class="form-label-enterprise d-block">Código de 6 Dígitos</label>
            <input type="text" 
                   class="form-control form-control-enterprise font-tabular text-center mx-auto" 
                   id="codigo" 
                   name="codigo" 
                   placeholder="000000" 
                   maxlength="6" 
                   pattern="\d{6}" 
                   style="letter-spacing: 8px; font-size: 22px; font-weight: 700; max-width: 220px;" 
                   required>
            <div class="text-microcopy text-muted mt-2">O código expira em 15 minutos.</div>
        </div>

        <button type="submit" class="btn btn-enterprise btn-primary w-100 py-2">
            Confirmar Código
        </button>
    </form>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
    <?php if (!$codigoBloqueado): ?>
        <!-- Formulário de Reenvio do Código -->
        <form action="<?php echo $baseUrl; ?>/primeiro-acesso/reenviar" method="POST" class="d-inline">
            <?php echo $csrf_field ?? ''; ?>
            <button type="submit" class="btn btn-link text-microcopy text-primary p-0 text-decoration-none border-0 bg-transparent">
                Reenviar código
            </button>
        </form>
    <?php else: ?>
        <span class="text-microcopy text-muted">Aguardando novo envio</span>
    <?php endif; ?>

    <a href="<?php echo $baseUrl; ?>/primeiro-acesso" class="text-microcopy text-secondary text-decoration-none">
        Trocar CPF
    </a>
</div>

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
