<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela de Erro 404 — Página Não Encontrada
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */
defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
$baseUrl = $baseUrl ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página Não Encontrada | Cantina Web</title>
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

    <main class="container my-auto py-5" style="max-width: 600px;">
        <div class="card-enterprise text-center p-4">
            <div class="mb-3">
                <span class="badge-status badge-warning" style="font-size: 16px; padding: 6px 16px;">Erro 404</span>
            </div>
            <h1 class="h4 fw-bold mb-2" style="color: var(--text-primary);">Página Não Encontrada</h1>
            <p class="text-secondary-label mb-4" style="font-size: 13px;">
                O endereço que você tentou acessar não existe, foi movido ou não está mais disponível no sistema.
            </p>

            <?php if (!empty($path)): ?>
                <div class="p-2 mb-4 bg-light rounded border font-tabular text-muted" style="font-size: 12px; word-break: break-all;">
                    URL requisitada: <code><?php echo htmlspecialchars($path, ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-center gap-2">
                <a href="javascript:history.back()" class="btn-enterprise btn-secondary">
                    ← Voltar
                </a>
                <a href="<?php echo $baseUrl; ?>/" class="btn-enterprise btn-primary">
                    Página Inicial
                </a>
            </div>
        </div>
    </main>

    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        &copy; <?php echo date('Y'); ?> Colégio Monteiro Lobato — Todos os direitos reservados.
    </footer>
</body>
</html>
