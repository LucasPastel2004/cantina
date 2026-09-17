<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Inicial — Demonstração do Núcleo Arquitetural MVC (Fase 2)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */
defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
$baseUrl = $baseUrl ?? '';
$shortCsrf = substr($csrfToken ?? '', 0, 16) . '...';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?> — Núcleo Arquitetural</title>
    <!-- Bootstrap Compilado Localmente -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <!-- Design System Institucional e Anti-Slop Guidelines -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css?v=5.0.1">
</head>
<body class="bg-surface d-flex flex-column min-vh-100">
    <!-- Barra Superior Institucional Compacta (56px) -->
    <header class="app-header">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3" style="max-width: 1080px;">
            <div class="d-flex align-items-center">
                <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" width="38" height="38" class="header-logo" style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;">
                <div class="d-flex flex-column">
                    <span class="fw-bold" style="font-size: 13px; color: var(--text-main); letter-spacing: -0.01em;">Colégio Monteiro Lobato</span>
                    <span class="text-microcopy">Cantina Web — Gestão Operacional e Nutricional</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="status-indicator status-success">
                    <span class="bullet" aria-hidden="true">●</span> Sistema Operacional
                </span>
            </div>
        </div>
    </header>

    <main class="container my-4" style="max-width: 1080px;">
        <!-- Faixa de Métricas Executivas de Alta Densidade (Stat Strip) -->
        <section class="stat-strip" aria-label="Métricas de Infraestrutura">
            <div class="stat-strip-item">
                <span class="stat-strip-label">Ambiente Ativo</span>
                <span class="stat-strip-value text-capitalize"><?php echo htmlspecialchars($environment, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">Interpretador</span>
                <span class="stat-strip-value font-tabular">PHP <?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">Sessão / CSRF</span>
                <span class="stat-strip-value font-tabular text-success" title="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    Ativa (<?php echo htmlspecialchars($shortCsrf, ENT_QUOTES, 'UTF-8'); ?>)
                </span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">Fase Vigente</span>
                <span class="stat-strip-value text-primary font-tabular"><?php echo htmlspecialchars($faseAtual ?? 'Fase 6 Concluída', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
        </section>

        <!-- Card Corporativo: Componentes Estruturais da Arquitetura -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold" style="font-size: 13px; color: var(--text-main);">Componentes Fundamentais do Núcleo MVC</span>
                    <span class="text-microcopy">&bull; Homologado</span>
                </div>
                <span class="text-microcopy font-tabular"><?php echo htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>

            <!-- Grade de Dados Operacionais (Data Grid) -->
            <div class="table-responsive mb-2">
                <table class="table-data-grid">
                    <thead>
                        <tr>
                            <th class="cell-left" style="width: 20%;">Módulo</th>
                            <th class="cell-left" style="width: 25%;">Classe Implementada</th>
                            <th class="cell-center" style="width: 12%;">Status</th>
                            <th class="cell-left" style="width: 43%;">Responsabilidade e Regras de Negócio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Roteador</strong></td>
                            <td><code>App\Core\Router</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Roteamento HTTP nativo com parâmetros nomeados <code>{param}</code>, subpastas e 404 seguro.</td>
                        </tr>
                        <tr>
                            <td><strong>Controlador Base</strong></td>
                            <td><code>App\Core\Controller</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Métodos <code>render()</code> com XSS escape, <code>json()</code>, <code>redirect()</code> e validações com CSRF.</td>
                        </tr>
                        <tr>
                            <td><strong>Modelo Base</strong></td>
                            <td><code>App\Core\Model</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Prepared Statements nativos via PDO, transações atômicas e soft delete com <code>deleted_at</code>.</td>
                        </tr>
                        <tr>
                            <td><strong>Sessão Segura</strong></td>
                            <td><code>App\Core\Session</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Cookies HttpOnly/SameSite, controle de inatividade (60 min), antifixação e tokens CSRF.</td>
                        </tr>
                        <tr>
                            <td><strong>Tratamento de Erros</strong></td>
                            <td><code>App\Core\ErrorHandler</code></td>
                            <td class="cell-center"><span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Ativo</span></td>
                            <td>Captura de exceções, tela segura com UUID e contingência com rotação de 5 MB em <code>error.log</code>.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção Bento Grid: Testes e Validação Interativa -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div>
                    <span class="fw-semibold" style="font-size: 13px; color: var(--text-main);">Validação Interativa do Núcleo Arquitetural</span>
                    <span class="text-microcopy d-block">Aferição de integridade em tempo real dos serviços fundamentais</span>
                </div>
            </div>

            <div class="row g-3">
                <!-- Bento Item 1: Rota Dinâmica -->
                <div class="col-md-6 col-lg-3">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">Roteamento</span>
                                <code class="code-tag">GET</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Parâmetro Dinâmico</h3>
                            <p class="text-secondary-label mb-3" style="font-size: 12px; line-height: 1.4;">
                                Captura de parâmetros dinâmicos na URL com tipagem e injeção no controlador.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/teste/1542" target="_blank" class="btn-enterprise btn-secondary btn-sm w-100">
                            Testar Rota (/teste/1542) ↗
                        </a>
                    </div>
                </div>

                <!-- Bento Item 2: Resposta JSON -->
                <div class="col-md-6 col-lg-3">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">API REST</span>
                                <code class="code-tag">JSON</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Endpoint de Status</h3>
                            <p class="text-secondary-label mb-3" style="font-size: 12px; line-height: 1.4;">
                                Retorno serializado via <code>$this->json()</code> com cabeçalho <code>application/json</code>.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/api/status" target="_blank" class="btn-enterprise btn-secondary btn-sm w-100">
                            Abrir Endpoint (/api/status) ↗
                        </a>
                    </div>
                </div>

                <!-- Bento Item 3: Formulário POST e CSRF -->
                <div class="col-md-6 col-lg-3">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">Segurança</span>
                                <code class="code-tag">POST</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Validação CSRF</h3>
                            <p class="text-secondary-label mb-2" style="font-size: 12px; line-height: 1.4;">
                                Submissão segura com verificação criptográfica estrita contra falsificação.
                            </p>
                            <form action="<?php echo $baseUrl; ?>/teste-csrf" method="POST" target="_blank" id="form-csrf-teste">
                                <?php echo $csrf_field; ?>
                                <input type="hidden" name="nome" value="Operador Homologado">
                                <button type="submit" class="btn-enterprise btn-primary btn-sm w-100 mt-2">
                                    Submeter POST Seguro ↗
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Bento Item 4: Simulação de Contingência -->
                <div class="col-md-6 col-lg-3">
                    <div class="bento-card">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="stat-strip-label">Auditoria</span>
                                <code class="code-tag">500</code>
                            </div>
                            <h3 class="h2-title mb-1" style="font-size: 13px;">Contingência de Erro</h3>
                            <p class="text-secondary-label mb-3" style="font-size: 12px; line-height: 1.4;">
                                Simulação de exceção não tratada com interceptação e gravação em disco.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/teste-erro" target="_blank" class="btn-enterprise btn-danger btn-sm w-100">
                            Disparar Erro de Teste ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Demonstração da Fase 5: Identidade Visual e Layouts Base -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold" style="font-size: 13px; color: var(--text-main);">Fase 5 — Identidade Visual Institucional e Layouts Base</span>
                    <span class="status-indicator status-success"><span class="bullet" aria-hidden="true">●</span> Concluída</span>
                </div>
                <span class="text-microcopy">docs/DESIGN.md</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong style="font-size: 13px;">Portal da Família</strong>
                                <code class="code-tag" style="font-size: 10px;">Responsáveis</code>
                            </div>
                            <p class="text-muted" style="font-size: 12px; line-height: 1.4;">
                                Layout completo para pais e responsáveis com saldo familiar em destaque, dependentes vinculados e agendamentos.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/design/portal" class="btn btn-enterprise btn-primary btn-sm w-100">
                            Ver Portal da Família ↗
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong style="font-size: 13px;">Painel da Cantina</strong>
                                <code class="code-tag" style="font-size: 10px;">Sidebar 240px</code>
                            </div>
                            <p class="text-muted" style="font-size: 12px; line-height: 1.4;">
                                Layout operacional com barra lateral de alta densidade, topbar com badge de turno e consolidação de salgados.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/design/admin" class="btn btn-enterprise btn-primary btn-sm w-100">
                            Ver Painel da Cantina ↗
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <strong style="font-size: 13px;">Galeria de Componentes</strong>
                                <span class="status-indicator status-neutral"><span class="bullet" aria-hidden="true">●</span> Anti-Slop</span>
                            </div>
                            <p class="text-muted" style="font-size: 12px; line-height: 1.4;">
                                Demonstração de alertas acessíveis, modais corporativos, empty states, loading states e botões táteis.
                            </p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/design/components" class="btn btn-enterprise btn-secondary btn-sm w-100">
                            Ver Componentes ↗
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próxima Fase do Projeto -->
        <div class="p-3 bg-white rounded border d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <span class="status-indicator status-neutral font-tabular" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em;">Próxima Etapa</span>
                <span class="text-secondary-label" style="font-size: 12px;">
                    <strong>Fase 6:</strong> Autenticação, Sessão Segura e Controle de Acesso (RBAC).
                </span>
            </div>
            <a href="https://github.com/LucasPastel2004/cantina" target="_blank" class="btn-enterprise btn-secondary btn-sm">
                Repositório GitHub ↗
            </a>
        </div>
    </main>

    <!-- Rodapé Corporativo Sóbrio -->
    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        Colégio Monteiro Lobato &copy; <?php echo date('Y'); ?> — Todos os direitos reservados.
    </footer>

    <!-- Bootstrap Bundle Local (Vanilla JS) -->
    <script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/app.js"></script>
</body>
</html>
