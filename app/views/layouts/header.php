<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Cabeçalho Base HTML5 e Topbar Institucional
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$title = !empty($pageTitle) 
    ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — Cantina Web' 
    : 'Cantina Web — Colégio Monteiro Lobato';
$appName = $appName ?? 'Cantina Web';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="Cantina Web — Colégio Monteiro Lobato. Gestão Operacional, Financeira e Nutricional.">
    <title><?php echo $title; ?></title>

    <!-- Bootstrap 5 Compilado Localmente (Sem CDN Externa) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">

    <!-- Design System Corporativo Anti-Slop (Enterprise High-Density) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=<?php echo $assetVersion ?? '7.1.0'; ?>">

    <!-- Favicon com Logotipo Oficial -->
    <link rel="icon" type="image/jpeg" href="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg">
</head>
<body class="bg-surface d-flex flex-column min-vh-100">
