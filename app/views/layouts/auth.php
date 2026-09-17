<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Telas de Autenticação e Primeiro Acesso (Auth Layout)
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$title = !empty($pageTitle) 
    ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — Cantina Web' 
    : 'Acesso ao Sistema — Cantina Web';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo $title; ?></title>

    <!-- Bootstrap 5 Compilado Localmente -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">

    <!-- Design System Corporativo Anti-Slop (Enterprise High-Density) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=5.0.3">

    <!-- Favicon Oficial -->
    <link rel="icon" type="image/jpeg" href="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg">
</head>
<body class="bg-surface d-flex flex-column min-vh-100 justify-content-between">

<div class="auth-wrapper flex-grow-1 d-flex align-items-center justify-content-center py-5 px-3">
    <div class="auth-card-container">
        <!-- Logotipo Oficial Centralizado (80px - 88px) -->
        <div class="text-center mb-4">
            <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="84" height="84" class="auth-logo mb-2" style="width: 84px; height: 84px; max-width: 84px; max-height: 84px; border-radius: 50%; object-fit: cover;">
            <h1 class="auth-brand-name mb-0">Colégio Monteiro Lobato</h1>
            <span class="auth-brand-sub">Cantina Web &bull; Valores para o Futuro!</span>
        </div>

        <!-- Alertas de Feedback / Erro de Autenticação -->
        <?php require __DIR__ . '/../partials/alert.php'; ?>

        <!-- Conteúdo Injetado do Formulário de Login / Primeiro Acesso -->
        <div class="card-enterprise auth-card">
            <?php echo $content ?? ''; ?>
        </div>

        <!-- Links e Orientações Rápidas -->
        <div class="text-center mt-3 text-microcopy text-muted">
            <span>Dúvidas ou suporte? Entre em contato com a secretaria da escola.</span>
        </div>
    </div>
</div>

<footer class="text-center py-3 text-muted font-tabular" style="font-size: 11px;">
    <span>Cantina Web &bull; Versão 2.0.0 &copy; <?php echo date('Y'); ?></span>
</footer>

<!-- Bootstrap 5 Bundle Local -->
<script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>

<!-- Scripts Vanilla JS Locais -->
<script src="<?php echo $baseUrl; ?>/assets/js/app.js"></script>
</body>
</html>
