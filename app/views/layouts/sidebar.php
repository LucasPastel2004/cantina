<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Barra Lateral de Navegação Operacional (Enterprise Sidebar 240px)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$activeMenu = $activeMenu ?? '';
$user = $user ?? [
    'nome' => 'Operador Padrão',
    'usuario' => 'operador-balcao',
    'perfil' => 'operador'
];
$isAdmin = ($user['perfil'] ?? '') === 'administrador';
?>

<!-- Backdrop para Mobile (Offcanvas) -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<aside class="app-sidebar" id="appSidebar">
    <!-- Cabeçalho da Sidebar com Logotipo Oficial -->
    <div class="sidebar-header">
        <a href="<?php echo $baseUrl; ?>/operacional/corte" class="sidebar-brand">
            <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="38" height="38" class="header-logo" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;">
            <div class="sidebar-brand-text">
                <span class="sidebar-brand-name">Monteiro Lobato</span>
                <span class="sidebar-brand-sub">Painel da Cantina</span>
            </div>
        </a>
        <button type="button" class="btn-sidebar-close d-lg-none" onclick="toggleSidebar()" aria-label="Fechar menu">
            <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/></svg>
        </button>
    </div>

    <!-- Navegação Agrupada por Contexto Funcional -->
    <nav class="sidebar-nav">
        <!-- Grupo 1: Operação de Balcão e Turno -->
        <div class="nav-section-label">Operacional</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/corte" class="nav-link <?php echo ($activeMenu === 'corte') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Corte do Turno</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/recebimento" class="nav-link <?php echo ($activeMenu === 'recebimento') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Conferência Fornecedor</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/tickets" class="nav-link <?php echo ($activeMenu === 'tickets') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Tickets Térmicos</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="nav-link <?php echo ($activeMenu === 'pdv') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">PDV Balcão</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="nav-link <?php echo ($activeMenu === 'caixa') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Caixa do Turno</span>
                </a>
            </li>
        </ul>

        <!-- Grupo 2: Estoque e Mercadorias -->
        <div class="nav-section-label">Estoque</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque" class="nav-link <?php echo ($activeMenu === 'estoque') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Saldos & Pausa</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque/perdas" class="nav-link <?php echo ($activeMenu === 'perdas') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Lançamento de Perdas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque/importar-xml" class="nav-link <?php echo ($activeMenu === 'xml') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Entrada XML NF-e</span>
                </a>
            </li>
        </ul>

        <!-- Grupo 3: Administração e Relatórios -->
        <div class="nav-section-label">Administração</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/alunos" class="nav-link <?php echo ($activeMenu === 'alunos') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Alunos & Turmas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/colaboradores" class="nav-link <?php echo ($activeMenu === 'colaboradores') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Colaboradores</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/produtos" class="nav-link <?php echo ($activeMenu === 'produtos') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Cardápio & Preços</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/calendario" class="nav-link <?php echo ($activeMenu === 'calendario') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Calendário Escolar</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios" class="nav-link <?php echo ($activeMenu === 'relatorios') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Relatórios Gerenciais</span>
                </a>
            </li>
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a href="<?php echo $baseUrl; ?>/admin/configuracoes" class="nav-link <?php echo ($activeMenu === 'configuracoes') ? 'active' : ''; ?>">
                        <span class="nav-bullet">●</span>
                        <span class="nav-label">Configurações Globais</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Rodapé da Sidebar: Usuário Logado e Logout -->
    <div class="sidebar-user">
        <div class="sidebar-user-info">
            <span class="sidebar-user-name text-truncate"><?php echo htmlspecialchars($user['nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="sidebar-user-role"><?php echo htmlspecialchars(ucfirst($user['perfil'] ?? 'Operador'), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <form action="<?php echo $baseUrl; ?>/logout" method="POST" class="d-inline m-0">
            <?php if (!empty($csrf_field)) echo $csrf_field; ?>
            <button type="submit" class="btn-sidebar-logout" title="Encerrar Sessão" aria-label="Encerrar Sessão">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2 2.75C2 1.784 2.784 1 3.75 1h2.5a.75.75 0 0 1 0 1.5h-2.5a.25.25 0 0 0-.25.25v10.5c0 .138.112.25.25.25h2.5a.75.75 0 0 1 0 1.5h-2.5A1.75 1.75 0 0 1 2 13.25Zm10.44 4.5H6.75a.75.75 0 0 0 0 1.5h5.69l-1.72 1.72a.75.75 0 1 0 1.06 1.06l3-3a.75.75 0 0 0 0-1.06l-3-3a.75.75 0 1 0-1.06 1.06l1.72 1.72Z"/></svg>
            </button>
        </form>
    </div>
</aside>
