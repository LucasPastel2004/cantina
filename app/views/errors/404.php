<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela de Erro 404 — Recurso Não Encontrado
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */
defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
$baseUrl = $baseUrl ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Recurso Não Encontrado | Cantina Web</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=5.0.1">
</head>
<body class="d-flex flex-column min-vh-100 bg-surface">
    <header class="app-header">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3" style="max-width: 1080px;">
            <div class="d-flex align-items-center">
                <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="38" height="38" class="header-logo" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;">
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

    <main class="container my-auto py-5" style="max-width: 520px;">
        <div class="card-enterprise p-4" style="border-top: 3px solid var(--primary-blue); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <code class="code-tag font-tabular">HTTP 404</code>
                <span class="text-microcopy">Operação Interrompida</span>
            </div>

            <h1 class="h1-title mb-2" style="font-size: 16px;">404 — Página Não Encontrada</h1>
            
            <p class="text-secondary-label mb-3" style="font-size: 13px; line-height: 1.5;">
                O recurso solicitado não existe, foi realocado ou você não possui permissão para acessá-lo diretamente.
            </p>

            <?php if (!empty($path)): ?>
                <div class="p-2 mb-4 bg-light rounded border font-tabular" style="font-size: 12px; color: var(--text-secondary); word-break: break-all;">
                    <span class="text-microcopy d-block text-uppercase fw-bold" style="font-size: 10px; color: #64748b;">Rota Solicitada:</span>
                    <code><?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
            <?php endif; ?>

            <div class="d-flex gap-2 pt-2 border-top">
                <button type="button" onclick="history.back()" class="btn-enterprise btn-secondary flex-grow-1">
                    ← Voltar à Tela Anterior
                </button>
                <a href="<?php echo $baseUrl; ?>/" class="btn-enterprise btn-primary flex-grow-1">
                    Painel Principal
                </a>
            </div>
        </div>
    </main>

    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        Colégio Monteiro Lobato &copy; <?php echo date('Y'); ?> — Todos os direitos reservados.
    </footer>
</body>
</html>
