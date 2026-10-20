<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Layout Mestre: Barra Lateral de Navegação (Dock de Ícones com Menus em Suspensão)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e solicitação de Interface Utilitária Estilo Escolar Manager
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$activeMenu = $activeMenu ?? '';
$user = $user ?? (\App\Core\Auth::check() ? \App\Core\Auth::user() : [
    'nome' => 'Administrador Geral',
    'usuario' => 'admin',
    'perfil' => 'administrador',
    'role' => 'administrador'
]);
$userRole = $user['role'] ?? $user['perfil'] ?? (\App\Core\Auth::role() ?? 'operador');
$isAdmin = ($userRole === 'administrador');

// Mapeamento de menus ativos para cada categoria do Dock
$isOperacionalActive = in_array($activeMenu, ['corte', 'recebimento', 'tickets', 'pdv', 'caixa', 'estornos'], true);
$isEstoqueActive = in_array($activeMenu, ['estoque', 'perdas', 'movimentacoes_estoque', 'entrada_estoque', 'importar_xml'], true);
$isFinanceiroActive = in_array($activeMenu, ['caixa', 'estornos', 'despesas', 'dre', 'arrecadacao', 'contestacoes'], true);
$isProdutosActive = ($activeMenu === 'produtos');
$isAdminActive = in_array($activeMenu, ['importacao', 'alunos', 'turmas', 'colaboradores', 'calendario', 'configuracoes'], true);
$isRelatoriosActive = in_array($activeMenu, ['relatorios', 'extrato_aluno', 'vendas_filtros', 'relatorio_alimento', 'fechamento_caixa_relatorio', 'devedores', 'movimentacoes_estoque'], true);
?>

<!-- Backdrop para fechar menus em suspensão e mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="fecharFlyouts()"></div>

<!-- Borda gatilho para compatibilidade -->
<div class="sidebar-edge-trigger" id="sidebarEdgeTrigger" style="display:none;" aria-hidden="true"></div>

<!-- Dock de Navegação Principal (Barra Estreita de 60px) -->
<aside class="app-sidebar app-sidebar-dock" id="appSidebar">
    <!-- Topo do Dock com Logotipo Oficial (Redireciona para a Visão Geral /admin) -->
    <div class="dock-header sidebar-header">
        <a href="<?php echo $baseUrl; ?>/admin" class="dock-brand sidebar-brand" title="Monteiro Lobato — Visão Geral da Cantina" aria-label="Visão Geral da Cantina">
            <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="36" height="36" class="header-logo" style="width: 36px; height: 36px; max-width: 36px; max-height: 36px; border-radius: 50%; object-fit: cover;">
            <div class="sidebar-brand-text d-none" aria-hidden="true">
                <span class="sidebar-brand-name">Monteiro Lobato</span>
                <span class="sidebar-brand-sub">Painel da Cantina</span>
            </div>
        </a>
        <button type="button" class="btn-sidebar-collapse d-none" style="display:none;" title="Travar barra lateral recolhida" aria-label="Travar barra lateral recolhida">
        </button>
    </div>

    <!-- Lista Vertical de Ícones de Categoria -->
    <nav class="dock-nav sidebar-nav" aria-label="Navegação Principal">
        <!-- 1. Categoria: Operacional -->
        <button type="button" 
                class="dock-btn <?php echo $isOperacionalActive ? 'is-current' : ''; ?>" 
                id="btnCategoryOperacional"
                data-category="operacional" 
                onclick="toggleCategoryFlyout('operacional')" 
                title="Operacional" 
                aria-label="Operacional"
                aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
        </button>

        <!-- 2. Categoria: Estoque -->
        <button type="button" 
                class="dock-btn <?php echo $isEstoqueActive ? 'is-current' : ''; ?>" 
                id="btnCategoryEstoque"
                data-category="estoque" 
                onclick="toggleCategoryFlyout('estoque')" 
                title="Estoque" 
                aria-label="Estoque"
                aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                <line x1="12" y1="22.08" x2="12" y2="12"></line>
            </svg>
        </button>

        <!-- 3. Categoria: Financeiro -->
        <button type="button" 
                class="dock-btn <?php echo $isFinanceiroActive ? 'is-current' : ''; ?>" 
                id="btnCategoryFinanceiro"
                data-category="financeiro" 
                onclick="toggleCategoryFlyout('financeiro')" 
                title="Financeiro" 
                aria-label="Financeiro"
                aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 7a4 4 0 0 0-4-4H9A4 4 0 0 0 5 7c0 1.25.57 2.37 1.46 3.1A8 8 0 1 0 17.54 10.1C18.43 9.37 19 8.25 19 7z"></path>
                <path d="M12 10v6"></path>
                <path d="M9.5 12h5a1.5 1.5 0 0 1 0 3h-5"></path>
            </svg>
        </button>

        <?php if ($isAdmin): ?>
        <!-- 4. Categoria: Relatórios (Posição elevada para o menu não cortar no rodapé) -->
        <button type="button" 
                class="dock-btn <?php echo $isRelatoriosActive ? 'is-current' : ''; ?>" 
                id="btnCategoryRelatorios"
                data-category="relatorios" 
                onclick="toggleCategoryFlyout('relatorios')" 
                title="Relatórios" 
                aria-label="Relatórios"
                aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
        </button>

        <!-- 5. Categoria: Administração -->
        <button type="button" 
                class="dock-btn <?php echo $isAdminActive ? 'is-current' : ''; ?>" 
                id="btnCategoryAdmin"
                data-category="admin" 
                onclick="toggleCategoryFlyout('admin')" 
                title="Administração" 
                aria-label="Administração"
                aria-expanded="false">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
            </svg>
        </button>

        <!-- 6. Cardápio & Preços: Ícone Único com Redirecionamento Direto (Sem Flyout) -->
        <a href="<?php echo $baseUrl; ?>/admin/produtos" 
           class="dock-btn <?php echo $isProdutosActive ? 'is-current' : ''; ?>" 
           id="btnDirectProdutos"
           title="Cardápio & Preços" 
           aria-label="Cardápio & Preços">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                <line x1="6" y1="1" x2="6" y2="4"></line>
                <line x1="10" y1="1" x2="10" y2="4"></line>
                <line x1="14" y1="1" x2="14" y2="4"></line>
            </svg>
        </a>
        <?php endif; ?>
    </nav>

    <!-- Rodapé do Dock: Botão de Encerramento de Sessão (Logout) -->
    <div class="dock-footer sidebar-user">
        <form action="<?php echo $baseUrl; ?>/logout" method="POST" class="d-inline m-0">
            <?php if (!empty($csrf_field)) echo $csrf_field; ?>
            <button type="submit" class="dock-btn btn-dock-logout btn-sidebar-logout" title="Encerrar Sessão (<?php echo htmlspecialchars($user['nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?>)" aria-label="Encerrar Sessão">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </button>
        </form>
    </div>

    <!-- =========================================================================
         MENUS EM SUSPENSÃO (FLYOUTS) — PAINÉIS FLUTUANTES À DIREITA DO ÍCONE
         ========================================================================= -->

    <!-- Flyout 1: Operacional -->
    <div class="sidebar-flyout" id="flyoutOperacional" data-category="operacional" role="menu" aria-label="Menu Operacional">
        <div class="flyout-header">
            <span class="flyout-title">Operacional</span>
            <button type="button" class="btn-flyout-close" onclick="fecharFlyouts()" aria-label="Fechar menu">✕</button>
        </div>
        <ul class="flyout-list nav-list">
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/corte" class="nav-link <?php echo ($activeMenu === 'corte') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Corte do Turno</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/recebimento" class="nav-link <?php echo ($activeMenu === 'recebimento') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Conferência Fornecedor</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/tickets" class="nav-link <?php echo ($activeMenu === 'tickets') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Tickets Térmicos</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="nav-link <?php echo ($activeMenu === 'pdv') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">PDV Balcão</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="nav-link <?php echo ($activeMenu === 'caixa') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Caixa do Turno</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/estornos" class="nav-link <?php echo ($activeMenu === 'estornos') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Estorno de Vendas</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Flyout 2: Estoque -->
    <div class="sidebar-flyout" id="flyoutEstoque" data-category="estoque" role="menu" aria-label="Menu Estoque">
        <div class="flyout-header">
            <span class="flyout-title">Estoque</span>
            <button type="button" class="btn-flyout-close" onclick="fecharFlyouts()" aria-label="Fechar menu">✕</button>
        </div>
        <ul class="flyout-list nav-list">
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque" class="nav-link <?php echo ($activeMenu === 'estoque') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Saldos & Pausa</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque/perdas" class="nav-link <?php echo ($activeMenu === 'perdas') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Lançamento de Perdas</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/estoque-filtros" class="nav-link <?php echo ($activeMenu === 'movimentacoes_estoque') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Movimentações</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque/entrada" class="nav-link <?php echo ($activeMenu === 'entrada_estoque') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Entrada de Estoque</span>
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/estoque/importar-xml" class="nav-link <?php echo ($activeMenu === 'importar_xml') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Entrada XML NF-e</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Flyout 3: Financeiro -->
    <div class="sidebar-flyout" id="flyoutFinanceiro" data-category="financeiro" role="menu" aria-label="Menu Financeiro">
        <div class="flyout-header">
            <span class="flyout-title">Financeiro</span>
            <button type="button" class="btn-flyout-close" onclick="fecharFlyouts()" aria-label="Fechar menu">✕</button>
        </div>
        <ul class="flyout-list nav-list">
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="nav-link <?php echo ($activeMenu === 'caixa') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Caixa do Turno</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/estornos" class="nav-link <?php echo ($activeMenu === 'estornos') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Estorno de Vendas</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/financeiro/despesas" class="nav-link <?php echo ($activeMenu === 'despesas') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Despesas da Cantina</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/financeiro/dre" class="nav-link <?php echo ($activeMenu === 'dre') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">DRE Gerencial</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao" class="nav-link <?php echo ($activeMenu === 'arrecadacao') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Arrecadação & Entradas</span>
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/contestacoes" class="nav-link <?php echo ($activeMenu === 'contestacoes') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Contestações MED</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Flyout 4: Administração -->
    <div class="sidebar-flyout" id="navSectionAdmin" data-category="admin" role="menu" aria-label="Menu Administração">
        <div class="flyout-header">
            <span class="flyout-title">Administração</span>
            <button type="button" class="btn-flyout-close" onclick="fecharFlyouts()" aria-label="Fechar menu">✕</button>
        </div>
        <ul class="flyout-list nav-list">
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/importacao-escolar" class="nav-link <?php echo ($activeMenu === 'importacao') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Importação Escolar</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/alunos" class="nav-link <?php echo ($activeMenu === 'alunos') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Alunos & Matrículas</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/turmas" class="nav-link <?php echo ($activeMenu === 'turmas') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Turmas Escolares</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/colaboradores" class="nav-link <?php echo ($activeMenu === 'colaboradores') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Colaboradores</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/calendario" class="nav-link <?php echo ($activeMenu === 'calendario') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Calendário Escolar</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/admin/configuracoes" class="nav-link <?php echo ($activeMenu === 'configuracoes') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Configurações Globais</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Flyout 5: Relatórios -->
    <div class="sidebar-flyout" id="flyoutRelatorios" data-category="relatorios" role="menu" aria-label="Menu Relatórios">
        <div class="flyout-header">
            <span class="flyout-title">Relatórios</span>
            <button type="button" class="btn-flyout-close" onclick="fecharFlyouts()" aria-label="Fechar menu">✕</button>
        </div>
        <ul class="flyout-list nav-list">
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios" class="nav-link <?php echo ($activeMenu === 'relatorios') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Central de Relatórios</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/extrato-aluno" class="nav-link <?php echo ($activeMenu === 'extrato_aluno') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Extrato do Aluno</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" class="nav-link <?php echo ($activeMenu === 'vendas_filtros') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Vendas por Alimento</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao" class="nav-link <?php echo ($activeMenu === 'arrecadacao') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Arrecadação & Entradas</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/fechamento-caixa" class="nav-link <?php echo ($activeMenu === 'fechamento_caixa_relatorio') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Fechamento de Caixa</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/devedores" class="nav-link <?php echo ($activeMenu === 'devedores') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Inadimplência & MED</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/operacional/corte" class="nav-link <?php echo ($activeMenu === 'corte') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Corte do Turno</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/relatorios/estoque-filtros" class="nav-link <?php echo ($activeMenu === 'movimentacoes_estoque') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">Movimentações de Estoque</span>
                </a>
            </li>
            <li class="flyout-item nav-item">
                <a href="<?php echo $baseUrl; ?>/financeiro/dre" class="nav-link <?php echo ($activeMenu === 'dre') ? 'active' : ''; ?>">
                    <span class="nav-bullet">●</span>
                    <span class="nav-label">DRE Gerencial</span>
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</aside>
