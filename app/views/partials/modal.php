<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Componente Parcial: Modal Corporativo Acessível (Enterprise Modal)
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 * 
 * Parâmetros esperados:
 * - $modalId (string, obrigatório)
 * - $modalTitle (string, obrigatório)
 * - $modalBody (string, opcional se renderizado inline)
 * - $modalSize (string, opcional: 'sm', 'lg', 'xl')
 * - $confirmText (string, opcional)
 * - $confirmClass (string, opcional: 'btn-primary', 'btn-danger', 'btn-success')
 * - $confirmId (string, opcional)
 * - $cancelText (string, opcional, padrão: 'Cancelar')
 * - $showFooter (bool, opcional, padrão: true)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$mId = htmlspecialchars($modalId ?? 'modalEnterpriseDefault', ENT_QUOTES, 'UTF-8');
$mTitle = htmlspecialchars($modalTitle ?? 'Confirmação', ENT_QUOTES, 'UTF-8');
$mSizeClass = !empty($modalSize) ? 'modal-' . htmlspecialchars($modalSize, ENT_QUOTES, 'UTF-8') : '';
$confirmText = !empty($confirmText) ? htmlspecialchars($confirmText, ENT_QUOTES, 'UTF-8') : null;
$confirmClass = !empty($confirmClass) ? htmlspecialchars($confirmClass, ENT_QUOTES, 'UTF-8') : 'btn-primary';
$confirmId = !empty($confirmId) ? htmlspecialchars($confirmId, ENT_QUOTES, 'UTF-8') : null;
$cancelText = !empty($cancelText) ? htmlspecialchars($cancelText, ENT_QUOTES, 'UTF-8') : 'Cancelar';
$showFooter = $showFooter ?? true;
?>

<div class="modal fade" id="<?php echo $mId; ?>" tabindex="-1" aria-labelledby="<?php echo $mId; ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered <?php echo $mSizeClass; ?>">
        <div class="modal-content modal-enterprise">
            <div class="modal-header">
                <h2 class="modal-title h2-title mb-0" id="<?php echo $mId; ?>Label"><?php echo $mTitle; ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($modalBody)): ?>
                    <?php echo $modalBody; ?>
                <?php endif; ?>
            </div>
            <?php if ($showFooter): ?>
                <div class="modal-footer">
                    <button type="button" class="btn btn-enterprise btn-secondary" data-bs-dismiss="modal">
                        <?php echo $cancelText; ?>
                    </button>
                    <?php if ($confirmText): ?>
                        <button type="button" class="btn btn-enterprise <?php echo $confirmClass; ?>" <?php echo $confirmId ? 'id="' . $confirmId . '"' : ''; ?>>
                            <?php echo $confirmText; ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
