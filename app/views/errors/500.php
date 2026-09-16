<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela de Erro 500 — Falha de Processamento
 * 
 * Conforme docs/FSD.md (Seção 19) e docs/DESIGN.md
 */
defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base = str_replace('\\', '/', $scriptDir);
$baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
$errorCode = $errorCode ?? 'ERR-UNKNOWN';
$debug = $debug ?? false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Falha no Processamento | Cantina Web</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-surface">
    <header class="app-header">
        <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" class="header-logo">
        <div class="d-flex flex-column">
            <span class="fw-bold" style="font-size: 14px; color: var(--primary-blue);">Colégio Monteiro Lobato</span>
            <span class="text-microcopy">Cantina Web — Gestão Operacional e Nutricional</span>
        </div>
    </header>

    <main class="container my-auto py-5" style="max-width: 640px;">
        <div class="card-enterprise p-4" style="border-top: 4px solid var(--danger-red);">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge-status badge-danger" style="font-size: 14px; padding: 4px 12px;">⚠ Falha Operacional</span>
            </div>

            <h1 class="h4 fw-bold mb-2" style="color: var(--text-primary);">Ocorreu uma falha no processamento</h1>
            
            <p class="text-secondary-label mb-3" style="font-size: 13px; line-height: 1.5;">
                Nossa equipe técnica já foi automaticamente notificada e o incidente registrado para auditoria. Nenhuma alteração financeira foi concluída incorretamente.
            </p>

            <div class="p-3 mb-4 rounded border bg-light d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary-label d-block" style="font-size: 11px;">Identificador do Incidente:</span>
                    <strong class="font-tabular text-dark" style="font-size: 15px;"><?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <button type="button" class="btn-enterprise btn-secondary btn-sm" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?>'); alert('Código copiado!');">
                    Copiar Código
                </button>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <a href="javascript:history.back()" class="btn-enterprise btn-secondary">
                    ← Voltar à Tela Anterior
                </a>
                <a href="<?php echo $baseUrl; ?>/" class="btn-enterprise btn-primary">
                    Página Inicial
                </a>
            </div>

            <?php if (!empty($debug) && isset($exception) && $exception instanceof \Throwable): ?>
                <div class="mt-4 pt-3 border-top">
                    <div class="text-danger fw-bold mb-2" style="font-size: 12px;">Informações de Diagnóstico Local (Modo Debug):</div>
                    <div class="p-3 bg-white border border-danger-subtle rounded font-tabular" style="font-size: 11px; white-space: pre-wrap; word-break: break-all; max-height: 250px; overflow-y: auto;">
<strong>Exceção:</strong> <?php echo htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8'); ?>

<strong>Mensagem:</strong> <?php echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'); ?>

<strong>Arquivo:</strong> <?php echo htmlspecialchars($exception->getFile() . ':' . $exception->getLine(), ENT_QUOTES, 'UTF-8'); ?>

<strong>Stack Trace:</strong>
<?php echo htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        &copy; <?php echo date('Y'); ?> Colégio Monteiro Lobato — Todos os direitos reservados.
    </footer>
</body>
</html>
