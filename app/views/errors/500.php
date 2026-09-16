<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela de Erro 500 — Falha Operacional Interceptada
 * 
 * Conforme docs/FSD.md (Seção 19), docs/DESIGN.md e skill design-taste-frontend
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
    <title>Falha Operacional Interceptada | Cantina Web</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-surface">
    <header class="app-header">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3" style="max-width: 1080px;">
            <div class="d-flex align-items-center">
                <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" class="header-logo">
                <div class="d-flex flex-column">
                    <span class="fw-bold" style="font-size: 13px; color: var(--text-main);">Colégio Monteiro Lobato</span>
                    <span class="text-microcopy">Cantina Web — Gestão Operacional e Nutricional</span>
                </div>
            </div>
            <a href="<?php echo $baseUrl; ?>/" class="btn-enterprise btn-secondary btn-sm">
                Início
            </a>
        </div>
    </header>

    <main class="container my-auto py-5" style="max-width: 580px;">
        <div class="card-enterprise p-4" style="border-top: 3px solid var(--action-danger); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="status-indicator status-danger font-tabular">
                    <span class="bullet" aria-hidden="true">●</span> Incidente Operacional
                </span>
                <span class="text-microcopy">Transação Cancelada por Segurança</span>
            </div>

            <h1 class="h1-title mb-2" style="font-size: 16px;">Ocorreu uma falha no processamento</h1>
            
            <p class="text-secondary-label mb-3" style="font-size: 13px; line-height: 1.5;">
                Nossa equipe técnica foi notificada e o incidente registrado com contingência em auditoria. Nenhuma cobrança indevida foi concluída.
            </p>

            <!-- Caixa de Identificador do Incidente com Microinteração Tátil de Cópia -->
            <div class="p-3 mb-4 rounded border bg-light d-flex align-items-center justify-content-between">
                <div>
                    <span class="stat-strip-label d-block mb-1" style="font-size: 10px;">Identificador do Incidente:</span>
                    <code class="font-tabular fw-bold" style="font-size: 14px; color: var(--text-primary);"><?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
                <button type="button" class="btn-enterprise btn-secondary btn-sm" id="btn-copiar-codigo" onclick="copiarCodigoErro('<?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?>')">
                    Copiar Código
                </button>
            </div>

            <!-- Botões de Ação com Microinterações Táteis -->
            <div class="d-flex gap-2 pt-2 border-top">
                <button type="button" onclick="history.back()" class="btn-enterprise btn-secondary flex-grow-1">
                    ← Voltar à Tela Anterior
                </button>
                <a href="<?php echo $baseUrl; ?>/" class="btn-enterprise btn-primary flex-grow-1">
                    Retornar ao Início
                </a>
            </div>

            <!-- Diagnóstico de Desenvolvimento Seguro (Colapsável) -->
            <?php if (!empty($debug) && isset($exception) && $exception instanceof \Throwable): ?>
                <details class="mt-4 pt-3 border-top" style="cursor: pointer;">
                    <summary class="text-danger fw-bold" style="font-size: 11px; outline: none; user-select: none;">
                        ► Informações Técnicas de Depuração (Ambiente Local)
                    </summary>
                    <div class="p-3 mt-2 bg-white border rounded font-tabular" style="font-size: 11px; white-space: pre-wrap; word-break: break-all; max-height: 220px; overflow-y: auto; background-color: #fffaf0;">
<strong>Exceção:</strong> <?php echo htmlspecialchars(get_class($exception), ENT_QUOTES, 'UTF-8'); ?>

<strong>Mensagem:</strong> <?php echo htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'); ?>

<strong>Origem:</strong> <?php echo htmlspecialchars($exception->getFile() . ':' . $exception->getLine(), ENT_QUOTES, 'UTF-8'); ?>

<strong>Stack Trace:</strong>
<?php echo htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </main>

    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        Colégio Monteiro Lobato &copy; <?php echo date('Y'); ?> — Todos os direitos reservados.
    </footer>

    <script>
        function copiarCodigoErro(codigo) {
            const btn = document.getElementById('btn-copiar-codigo');
            navigator.clipboard.writeText(codigo).then(() => {
                const originalText = btn.textContent;
                btn.textContent = '✓ Copiado!';
                btn.classList.replace('btn-secondary', 'btn-success');
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.classList.replace('btn-success', 'btn-secondary');
                }, 2000);
            }).catch(() => {
                btn.textContent = 'Erro ao copiar';
            });
        }
    </script>
</body>
</html>
