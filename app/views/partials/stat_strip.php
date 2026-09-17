<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Componente Parcial: Faixa de Métricas Executivas de Alta Densidade (Stat Strip)
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 * 
 * Parâmetros esperados:
 * - $statItems (array, obrigatório):
 *   [
 *     [
 *       'label' => 'Total de Pedidos',
 *       'value' => '142',
 *       'subtext' => 'Turno Matutino',
 *       'status' => 'success' (opcional)
 *     ], ...
 *   ]
 * - $ariaLabel (string, opcional, padrão: "Métricas Operacionais")
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$items = $statItems ?? [];
$label = htmlspecialchars($ariaLabel ?? 'Métricas Operacionais', ENT_QUOTES, 'UTF-8');

if (empty($items)) {
    return;
}
?>

<section class="stat-strip" aria-label="<?php echo $label; ?>">
    <?php foreach ($items as $item): 
        $itemLabel = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
        $itemValue = htmlspecialchars($item['value'] ?? '', ENT_QUOTES, 'UTF-8');
        $itemSubtext = !empty($item['subtext']) ? htmlspecialchars($item['subtext'], ENT_QUOTES, 'UTF-8') : null;
        $statusClass = !empty($item['status']) ? 'text-' . htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') : '';
    ?>
        <div class="stat-strip-item">
            <span class="stat-strip-label"><?php echo $itemLabel; ?></span>
            <span class="stat-strip-value font-tabular <?php echo $statusClass; ?>">
                <?php echo $itemValue; ?>
            </span>
            <?php if ($itemSubtext): ?>
                <span class="text-microcopy"><?php echo $itemSubtext; ?></span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</section>
