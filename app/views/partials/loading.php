<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Componente Parcial: Estado de Carregamento (Loading & Skeleton State)
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 * 
 * Parâmetros esperados:
 * - $loadingText (string, opcional, padrão: "Carregando informações...")
 * - $isSkeleton (bool, opcional, padrão: false)
 * - $skeletonRows (int, opcional, padrão: 3)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$text = htmlspecialchars($loadingText ?? 'Carregando informações...', ENT_QUOTES, 'UTF-8');
$isSkeleton = !empty($isSkeleton);
$rows = (int)($skeletonRows ?? 3);
?>

<?php if ($isSkeleton): ?>
    <div class="skeleton-container" aria-busy="true" aria-label="<?php echo $text; ?>">
        <?php for ($i = 0; $i < $rows; $i++): ?>
            <div class="skeleton-row mb-2">
                <div class="skeleton-line" style="width: <?php echo rand(65, 95); ?>%;"></div>
            </div>
        <?php endfor; ?>
    </div>
<?php else: ?>
    <div class="loading-overlay-enterprise" aria-busy="true" aria-live="polite">
        <div class="spinner-blue" aria-hidden="true"></div>
        <span class="loading-text"><?php echo $text; ?></span>
    </div>
<?php endif; ?>
