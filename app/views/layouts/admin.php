<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Painel Operacional e Administrativo da Cantina
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$title = !empty($pageTitle) 
    ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — Painel da Cantina' 
    : 'Painel da Cantina — Colégio Monteiro Lobato';
if (!isset($turnoAtivo) || !isset($statusCaixa)) {
    try {
        if (class_exists(\App\Models\SessaoCaixa::class)) {
            $sessaoAbertaLayout = \App\Models\SessaoCaixa::obterQualquerSessaoAberta();
            if (!isset($statusCaixa)) {
                $statusCaixa = $sessaoAbertaLayout ? 'Aberto' : 'Fechado';
            }
            if (!isset($turnoAtivo)) {
                $turnoLayout = $sessaoAbertaLayout ? $sessaoAbertaLayout['turno'] : \App\Models\SessaoCaixa::inferirTurnoAtual();
                $turnoAtivo = ucfirst($turnoLayout);
            }
        }
    } catch (\Throwable $e) {
        $statusCaixa = $statusCaixa ?? 'Fechado';
        $turnoAtivo = $turnoAtivo ?? 'Matutino';
    }
}
$turnoAtivo = $turnoAtivo ?? 'Matutino';
$statusCaixa = $statusCaixa ?? 'Fechado';
$dataAtual = date('d/m/Y');
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
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=<?php echo $assetVersion ?? '7.0.9'; ?>">

    <!-- Favicon Oficial -->
    <link rel="icon" type="image/jpeg" href="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg">
</head>
<body class="bg-surface">

<?php $isPdvActive = ($activeMenu ?? '') === 'pdv'; ?>
<div class="app-admin-wrapper <?php echo $isPdvActive ? 'pdv-layout-locked' : ''; ?>">
    <!-- Barra Lateral de Navegação (Dock de Ícones com Menus em Suspensão) -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Área Principal de Conteúdo (Sem Topbar Transversal - Visual Limpo e Amplo) -->
    <div class="app-main-area" data-turno="<?php echo htmlspecialchars($turnoAtivo, ENT_QUOTES, 'UTF-8'); ?>" data-status-caixa="<?php echo htmlspecialchars($statusCaixa, ENT_QUOTES, 'UTF-8'); ?>">
        <!-- Botão Discreto de Alternância da Navegação para Dispositivos Móveis (< 768px) -->
        <button type="button" class="btn-sidebar-toggle d-md-none position-fixed top-0 start-0 m-2 z-3 shadow-sm bg-white border" onclick="toggleSidebar()" aria-label="Alternar navegação" title="Alternar navegação" style="width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
            <svg class="icon-toggle-bars" width="18" height="18" viewBox="0 0 16 16" fill="currentColor"><path d="M1 2.75A.75.75 0 0 1 1.75 2h12.5a.75.75 0 0 1 0 1.5H1.75A.75.75 0 0 1 1 2.75Zm0 5A.75.75 0 0 1 1.75 7h12.5a.75.75 0 0 1 0 1.5H1.75A.75.75 0 0 1 1 7.75Zm0 5a.75.75 0 0 1 1.75 0h12.5a.75.75 0 0 1 0 1.5H1.75a.75.75 0 0 1-.75-.75Z"/></svg>
            <svg class="icon-toggle-arrow" width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
        </button>

        <!-- Área Dinâmica de Conteúdo da View -->
        <main class="app-page-content" id="mainContent">
            <!-- Renderização Automática de Alertas / Mensagens Flash -->
            <?php require __DIR__ . '/../partials/alert.php'; ?>

            <!-- Conteúdo Injetado da View -->
            <?php echo $content ?? ''; ?>
        </main>

        <!-- Rodapé Operacional Compacto -->
        <footer class="app-admin-footer">
            <div class="container-fluid px-4 d-flex justify-content-between text-muted font-tabular" style="font-size: 11px;">
                <span>Colégio Monteiro Lobato &bull; Sistema Cantina Web</span>
                <span>Valores para o Futuro! &copy; <?php echo date('Y'); ?></span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle Local -->
<script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>

<!-- Utilitários e Scripts Vanilla JS -->
<script src="<?php echo $baseUrl; ?>/assets/js/app.js?v=<?php echo $assetVersion ?? '7.0.6'; ?>"></script>
</body>
</html>
