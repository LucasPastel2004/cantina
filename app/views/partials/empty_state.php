<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Componente Parcial: Estado Vazio Padronizado (Enterprise Empty State)
 * 
 * Conforme docs/DESIGN.md, docs/FSD.md e skill design-taste-frontend
 * Padrão obrigatório: "Não há nada para mostrar aqui"
 * 
 * Parâmetros esperados:
 * - $emptyMessage (string, opcional, padrão: "Não há nada para mostrar aqui")
 * - $emptyDescription (string, opcional)
 * - $emptyActionText (string, opcional)
 * - $emptyActionUrl (string, opcional)
 * - $emptyActionClass (string, opcional, padrão: "btn-primary")
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$emptyTitle = htmlspecialchars($emptyMessage ?? 'Não há nada para mostrar aqui', ENT_QUOTES, 'UTF-8');
$emptyDesc = !empty($emptyDescription) ? htmlspecialchars($emptyDescription, ENT_QUOTES, 'UTF-8') : null;
$emptyActText = !empty($emptyActionText) ? htmlspecialchars($emptyActionText, ENT_QUOTES, 'UTF-8') : null;
$emptyActUrl = !empty($emptyActionUrl) ? htmlspecialchars($emptyActionUrl, ENT_QUOTES, 'UTF-8') : null;
$emptyActClass = !empty($emptyActionClass) ? htmlspecialchars($emptyActionClass, ENT_QUOTES, 'UTF-8') : 'btn-primary';
?>

<div class="empty-state-enterprise">
    <div class="empty-state-icon" aria-hidden="true">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="4" width="20" height="16" rx="3"></rect>
            <path d="M7 15h10"></path>
            <path d="M7 9h6"></path>
        </svg>
    </div>
    <p class="empty-state-title"><?php echo $emptyTitle; ?></p>
    <?php if ($emptyDesc): ?>
        <p class="empty-state-subtitle"><?php echo $emptyDesc; ?></p>
    <?php endif; ?>
    <?php if ($emptyActText && $emptyActUrl): ?>
        <div class="empty-state-action">
            <a href="<?php echo $emptyActUrl; ?>" class="btn btn-enterprise <?php echo $emptyActClass; ?>">
                <?php echo $emptyActText; ?>
            </a>
        </div>
    <?php endif; ?>
</div>
<?php
unset($emptyTitle, $emptyDesc, $emptyActText, $emptyActUrl, $emptyActClass, $emptyMessage, $emptyDescription, $emptyActionText, $emptyActionUrl, $emptyActionClass);
?>
