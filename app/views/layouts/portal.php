<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Portal da Família (Pais e Responsáveis)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$title = !empty($pageTitle) 
    ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — Portal da Família' 
    : 'Portal da Família — Colégio Monteiro Lobato';
$activeMenu = $activeMenu ?? 'inicio';
$saldoAtual = $saldoAtual ?? 0.00;
$saldoReservado = $saldoReservado ?? 0.00;
$responsavelNome = $responsavelNome ?? 'Responsável';
$isSaldoBaixo = ($saldoAtual < 15.00);
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

    <!-- Design System Corporativo Anti-Slop -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=5.0.1">

    <!-- Favicon Oficial -->
    <link rel="icon" type="image/jpeg" href="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg">
</head>
<body class="bg-surface d-flex flex-column min-vh-100">

<!-- Topbar do Portal da Família -->
<header class="portal-header">
    <div class="container-fluid px-3 px-md-4 d-flex align-items-center justify-content-between" style="max-width: 1140px;">
        <!-- Identidade Oficial -->
        <a href="<?php echo $baseUrl; ?>/portal" class="d-flex align-items-center text-decoration-none">
            <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="38" height="38" class="header-logo" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;">
            <div class="d-flex flex-column">
                <span class="portal-brand-title">Monteiro Lobato</span>
                <span class="text-microcopy">Portal da Família</span>
            </div>
        </a>

        <!-- Widget de Saldo e Ações da Carteira Familiar -->
        <div class="d-flex align-items-center gap-3">
            <div class="portal-wallet-card <?php echo $isSaldoBaixo ? 'wallet-low' : ''; ?>">
                <div class="d-flex flex-column text-end">
                    <span class="wallet-label">Saldo Disponível</span>
                    <span class="wallet-value font-tabular">
                        R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?>
                    </span>
                </div>
                <a href="<?php echo $baseUrl; ?>/portal/recarga" class="btn btn-enterprise btn-sm btn-success" title="Recarregar via Pix">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    <span>Recarregar</span>
                </a>
            </div>

            <!-- Perfil do Responsável e Logout -->
            <div class="dropdown d-none d-md-block">
                <button class="btn btn-enterprise btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($responsavelNome, ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/portal/perfil">Meus Dados</a></li>
                    <li><a class="dropdown-item" href="<?php echo $baseUrl; ?>/portal/carteira">Extrato Financeiro</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="<?php echo $baseUrl; ?>/logout" method="POST" class="m-0">
                            <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                            <button type="submit" class="dropdown-item text-danger">Sair da Conta</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<!-- Barra de Navegação Primária do Portal -->
<nav class="portal-nav-bar">
    <div class="container-fluid px-3 px-md-4" style="max-width: 1140px;">
        <ul class="portal-nav-list">
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal" class="portal-nav-link <?php echo ($activeMenu === 'inicio') ? 'active' : ''; ?>">
                    Início
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/carteira" class="portal-nav-link <?php echo ($activeMenu === 'carteira') ? 'active' : ''; ?>">
                    Carteira Familiar
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="portal-nav-link <?php echo ($activeMenu === 'agendamento') ? 'active' : ''; ?>">
                    Agendamento Semanal
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="portal-nav-link <?php echo ($activeMenu === 'pedidos') ? 'active' : ''; ?>">
                    Meus Pedidos
                </a>
            </li>
            <li class="portal-nav-item">
                <a href="<?php echo $baseUrl; ?>/portal/alergias" class="portal-nav-link <?php echo ($activeMenu === 'alergias') ? 'active' : ''; ?>">
                    Alergias & Restrições
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Conteúdo Principal do Portal -->
<main class="container-fluid px-3 px-md-4 my-4 flex-grow-1" style="max-width: 1140px;">
    <!-- Alertas do Sistema / Feedback de Ações -->
    <?php require __DIR__ . '/../partials/alert.php'; ?>

    <!-- Conteúdo da View Específica -->
    <?php echo $content ?? ''; ?>
</main>

<!-- Rodapé Institucional do Portal -->
<footer class="portal-footer mt-auto py-3">
    <div class="container-fluid px-3 px-md-4 d-flex flex-column flex-md-row align-items-center justify-content-between text-muted font-tabular" style="max-width: 1140px; font-size: 12px;">
        <div class="d-flex align-items-center gap-2 mb-1 mb-md-0">
            <span class="fw-semibold">Colégio Monteiro Lobato</span>
            <span>&bull;</span>
            <span class="fst-italic">Valores para o Futuro!</span>
        </div>
        <div>
            <span>Cantina Web &bull; Portal da Família &copy; <?php echo date('Y'); ?></span>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle Local -->
<script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>

<!-- Scripts Vanilla JS Locais -->
<script src="<?php echo $baseUrl; ?>/assets/js/app.js"></script>
</body>
</html>
