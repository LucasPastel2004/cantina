<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Componente Parcial: Alertas Corporativos (Enterprise Alert)
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 * Suporta passagem direta de variáveis ($type, $message, $title, $dismissible)
 * ou consumo automático de mensagens flash da Session.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Session;

// Lista de alertas a renderizar
$alertsToRender = [];

// 1. Alerta explícito passado como parâmetro
if (!empty($message)) {
    $explicitMessages = is_array($message) ? $message : [$message];
    foreach ($explicitMessages as $msg) {
        if (is_string($msg) && trim($msg) !== '') {
            $alertsToRender[] = [
                'type'        => $type ?? 'info',
                'title'       => $title ?? null,
                'message'     => $msg,
                'dismissible' => $dismissible ?? true,
            ];
        }
    }
}

// 2. Alertas provenientes de mensagens flash da Session (suporta 'success', 'danger', 'error', 'warning', 'info')
if (class_exists(Session::class)) {
    $flashCategories = [
        'success' => ['type' => 'success', 'title' => 'Sucesso'],
        'danger'  => ['type' => 'danger',  'title' => 'Atenção'],
        'error'   => ['type' => 'danger',  'title' => 'Atenção'],
        'warning' => ['type' => 'warning', 'title' => 'Alerta'],
        'info'    => ['type' => 'info',    'title' => 'Informação'],
    ];

    foreach ($flashCategories as $flashKey => $meta) {
        $flashMsgs = Session::getFlash($flashKey);
        if (!empty($flashMsgs)) {
            $items = is_array($flashMsgs) ? $flashMsgs : [$flashMsgs];
            foreach ($items as $msg) {
                if (is_string($msg) && trim($msg) !== '') {
                    $alertsToRender[] = [
                        'type'        => $meta['type'],
                        'title'       => $meta['title'],
                        'message'     => $msg,
                        'dismissible' => true,
                    ];
                }
            }
        }
    }
}

foreach ($alertsToRender as $alert):
    $alertType = htmlspecialchars((string)$alert['type'], ENT_QUOTES, 'UTF-8');
    $alertTitle = !empty($alert['title']) ? htmlspecialchars((string)$alert['title'], ENT_QUOTES, 'UTF-8') : null;
    $alertMsg = is_string($alert['message']) 
        ? htmlspecialchars($alert['message'], ENT_QUOTES, 'UTF-8') 
        : htmlspecialchars(json_encode($alert['message']), ENT_QUOTES, 'UTF-8');
    $isDismissible = !empty($alert['dismissible']);

    // Mapeamento semântico austero de cores e ícones discretos
    $role = ($alert['type'] === 'danger' || $alert['type'] === 'warning') ? 'alert' : 'status';
?>
    <div class="alert-enterprise alert-enterprise-<?php echo $alertType; ?>" role="<?php echo $role; ?>" aria-live="polite">
        <div class="alert-enterprise-icon" aria-hidden="true">
            <?php if ($alert['type'] === 'success'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"/></svg>
            <?php elseif ($alert['type'] === 'danger'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2.343 13.657A8 8 0 1 1 13.657 2.343 8 8 0 0 1 2.343 13.657ZM6.03 4.97a.75.75 0 0 0-1.06 1.06L6.94 8 4.97 9.97a.749.749 0 0 0 .326 1.275.749.749 0 0 0 .734-.215L8 9.06l1.97 1.97a.749.749 0 0 0 1.275-.326.749.749 0 0 0-.215-.734L9.06 8l1.97-1.97a.749.749 0 0 0-.326-1.275.749.749 0 0 0-.734.215L8 6.94 6.03 4.97Z"/></svg>
            <?php elseif ($alert['type'] === 'warning'): ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M6.457 1.047c.659-1.234 2.427-1.234 3.086 0l6.082 11.378A1.75 1.75 0 0 1 14.082 15H1.918a1.75 1.75 0 0 1-1.543-2.575Zm1.763.707a.25.25 0 0 0-.44 0L1.698 13.132a.25.25 0 0 0 .22.368h12.164a.25.25 0 0 0 .22-.368Zm.53 3.996v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
            <?php else: ?>
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13ZM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm6.5-.25A.75.75 0 0 1 7.25 7h1a.75.75 0 0 1 .75.75v2.75h.25a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1 0-1.5h.25v-2h-.25a.75.75 0 0 1-.75-.75ZM8 6a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/></svg>
            <?php endif; ?>
        </div>
        <div class="alert-enterprise-body">
            <?php if ($alertTitle): ?>
                <strong class="alert-enterprise-title"><?php echo $alertTitle; ?>:</strong>
            <?php endif; ?>
            <span class="alert-enterprise-message"><?php echo $alertMsg; ?></span>
        </div>
        <?php if ($isDismissible): ?>
            <button type="button" class="alert-enterprise-close" onclick="this.closest('.alert-enterprise').remove();" aria-label="Fechar alerta">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/></svg>
            </button>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
