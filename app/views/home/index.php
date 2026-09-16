<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Inicial — Demonstração do Núcleo Arquitetural MVC (Fase 2)
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
    <title><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?> — Núcleo MVC</title>
    <!-- Bootstrap Local -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <!-- Design System Institucional - docs/DESIGN.md -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css">
</head>
<body class="bg-surface d-flex flex-column min-vh-100">
    <!-- Barra de Topo Institucional -->
    <header class="app-header">
        <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" class="header-logo">
        <div class="d-flex flex-column">
            <span class="fw-bold" style="font-size: 14px; color: var(--primary-blue);">Colégio Monteiro Lobato</span>
            <span class="text-microcopy">Cantina Web — Gestão Operacional e Nutricional</span>
        </div>
    </header>

    <main class="container my-4" style="max-width: 960px;">
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-status badge-success">✓ Operacional</span>
                    <span>Núcleo Arquitetural MVC e Mecanismo de Logs/Contingência</span>
                </div>
                <span class="text-microcopy">Fase 2 Implementada</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label">Ambiente do Sistema</label>
                        <div class="fw-semibold text-capitalize"><?php echo htmlspecialchars($environment, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label">Versão do Interpretador PHP</label>
                        <div class="fw-semibold font-tabular"><?php echo htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label">Controle de Sessão e CSRF</label>
                        <div class="fw-semibold text-success font-tabular">Ativa (Token Gerado)</div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Componentes Core -->
            <div class="table-responsive mb-4">
                <table class="table-data-grid">
                    <thead>
                        <tr>
                            <th class="cell-left">Componente Arquitetural</th>
                            <th class="cell-center">Classe</th>
                            <th class="cell-center">Status</th>
                            <th class="cell-left">Responsabilidade / Funcionalidade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Roteador</strong></td>
                            <td class="cell-center"><code>App\Core\Router</code></td>
                            <td class="cell-center"><span class="badge-status badge-success">Ativo</span></td>
                            <td>Resolução HTTP, suporte a rotas dinâmicas <code>{param}</code>, subpastas e middlewares</td>
                        </tr>
                        <tr>
                            <td><strong>Controlador Base</strong></td>
                            <td class="cell-center"><code>App\Core\Controller</code></td>
                            <td class="cell-center"><span class="badge-status badge-success">Ativo</span></td>
                            <td>Métodos <code>render()</code>, <code>json()</code>, <code>redirect()</code> e validações com CSRF</td>
                        </tr>
                        <tr>
                            <td><strong>Modelo Base</strong></td>
                            <td class="cell-center"><code>App\Core\Model</code></td>
                            <td class="cell-center"><span class="badge-status badge-success">Ativo</span></td>
                            <td>Prepared Statements nativos via PDO, transações atômicas e suporte a soft delete</td>
                        </tr>
                        <tr>
                            <td><strong>Sessão Segura</strong></td>
                            <td class="cell-center"><code>App\Core\Session</code></td>
                            <td class="cell-center"><span class="badge-status badge-success">Ativo</span></td>
                            <td>Cookies HttpOnly/SameSite, timeout de 60min, tokens CSRF e proteção contra fixação</td>
                        </tr>
                        <tr>
                            <td><strong>Tratamento de Erros</strong></td>
                            <td class="cell-center"><code>App\Core\ErrorHandler</code></td>
                            <td class="cell-center"><span class="badge-status badge-success">Ativo</span></td>
                            <td>Captura de exceções, resposta segura com UUID e fallback com rotação em 5 MB no <code>error.log</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Painel de Validação Interativa -->
            <div class="card p-3 mb-4 bg-light border">
                <h2 class="h6 fw-bold mb-3" style="color: var(--primary-blue);">Validação Interativa do Núcleo MVC</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-white border rounded">
                            <label class="form-label fw-bold">1. Teste de Rota Dinâmica</label>
                            <p class="text-secondary-label" style="font-size: 12px;">Testa o roteamento dinâmico com extração de parâmetro nomeado pelo Router.</p>
                            <a href="<?php echo $baseUrl; ?>/teste/9876" target="_blank" class="btn-enterprise btn-secondary btn-sm">
                                Testar Rota Dinâmica (/teste/9876) ↗
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white border rounded">
                            <label class="form-label fw-bold">2. Teste do Endpoint JSON</label>
                            <p class="text-secondary-label" style="font-size: 12px;">Retorna payload estruturado via método <code>$this->json()</code>.</p>
                            <a href="<?php echo $baseUrl; ?>/api/status" target="_blank" class="btn-enterprise btn-secondary btn-sm">
                                Visualizar API Status (/api/status) ↗
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white border rounded">
                            <label class="form-label fw-bold">3. Teste de Proteção CSRF (POST)</label>
                            <form action="<?php echo $baseUrl; ?>/teste-csrf" method="POST" target="_blank">
                                <?php echo $csrf_field; ?>
                                <div class="mb-2">
                                    <input type="text" name="nome" value="Operador Teste" class="form-control form-control-sm" required>
                                </div>
                                <button type="submit" class="btn-enterprise btn-primary btn-sm">
                                    Submeter com Token Válido ↗
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-white border rounded">
                            <label class="form-label fw-bold">4. Teste de Contingência de Erros</label>
                            <p class="text-secondary-label" style="font-size: 12px;">Dispara exceção intencional para verificar a tela amigável (UUID) e gravação em <code>error.log</code>.</p>
                            <a href="<?php echo $baseUrl; ?>/teste-erro" target="_blank" class="btn-enterprise btn-danger btn-sm">
                                Disparar Erro de Simulação ↗
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Caixa de Informações de Próximos Passos -->
            <div class="p-3 bg-light rounded border">
                <p class="mb-1 text-secondary-label"><strong>Próximo Passo Recomendado:</strong></p>
                <p class="mb-0" style="font-size: 13px;">Avançar para a <strong>Fase 3 — Conexão de Banco de Dados e Migrações Estruturais/Cadastrais (001 a 012)</strong> conforme o plano de implementação em <code>docs/PLANO.md</code>.</p>
            </div>
        </div>
    </main>

    <footer class="text-center py-3 text-microcopy border-top bg-white mt-auto">
        &copy; <?php echo date('Y'); ?> Colégio Monteiro Lobato — Todos os direitos reservados.
    </footer>

    <!-- Bootstrap Bundle Local (Vanilla JS) -->
    <script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/app.js"></script>
</body>
</html>
