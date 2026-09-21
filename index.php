<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Ponto Único de Entrada (Front Controller)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

// 1. Define a constante mestra de segurança do sistema
define('CANTINA_SISTEMA', true);

// 2. Registra o timestamp inicial para métricas de tempo de resposta
define('CANTINA_START', microtime(true));

// 3. Registra o Autoloader PSR-4 nativo para o namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 4. Carrega as configurações centrais da aplicação
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    die('Arquivo de configuração [config/config.php] não encontrado. Copie config/config.example.php.');
}
$config = require $configFile;

// 5. Configuração de fuso horário oficial
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

// 6. Ativa o Capturador Global de Erros e Exceções com contingência em arquivo
\App\Core\ErrorHandler::register($config['app']['debug'] ?? false);

// 7. Inicializa a Sessão Segura e proteção CSRF
\App\Core\Session::start($config);

// 8. Inicializa o Roteador e registra as rotas da aplicação
$router = new \App\Core\Router();

// Rotas do Módulo Inicial e de Status
$router->get('/', [\App\Controllers\HomeController::class, 'index']);
$router->get('/api/status', [\App\Controllers\HomeController::class, 'status']);
$router->get('/teste/{id}', [\App\Controllers\HomeController::class, 'testeParametro']);
$router->post('/teste-csrf', [\App\Controllers\HomeController::class, 'testeCsrf']);
$router->get('/teste-erro', [\App\Controllers\HomeController::class, 'testeErro']);

// Rotas de Demonstração e Homologação Visual (Fase 5 — DESIGN.md)
$router->get('/design/portal', [\App\Controllers\ShowcaseController::class, 'portal']);
$router->get('/design/admin', [\App\Controllers\ShowcaseController::class, 'admin']);
$router->get('/design/components', [\App\Controllers\ShowcaseController::class, 'components']);

// Rotas de Autenticação e Primeiro Acesso (Fase 6 — FSD Área 1 & RBAC)
$router->get('/login', [\App\Controllers\AuthController::class, 'login']);
$router->post('/login', [\App\Controllers\AuthController::class, 'loginPost']);
$router->get('/logout', [\App\Controllers\AuthController::class, 'logout']);
$router->post('/logout', [\App\Controllers\AuthController::class, 'logout']);

$router->get('/primeiro-acesso', [\App\Controllers\AuthController::class, 'primeiroAcesso']);
$router->post('/primeiro-acesso/solicitar', [\App\Controllers\AuthController::class, 'primeiroAcessoSolicitar']);
$router->get('/primeiro-acesso/codigo', [\App\Controllers\AuthController::class, 'primeiroAcessoCodigo']);
$router->post('/primeiro-acesso/validar-codigo', [\App\Controllers\AuthController::class, 'primeiroAcessoValidarCodigo']);
$router->post('/primeiro-acesso/reenviar', [\App\Controllers\AuthController::class, 'primeiroAcessoReenviar']);
$router->get('/primeiro-acesso/senha', [\App\Controllers\AuthController::class, 'primeiroAcessoSenha']);
$router->post('/primeiro-acesso/concluir', [\App\Controllers\AuthController::class, 'primeiroAcessoConcluir']);

$router->get('/recuperar-senha', [\App\Controllers\AuthController::class, 'recuperarSenha']);
$router->post('/recuperar-senha/solicitar', [\App\Controllers\AuthController::class, 'recuperarSenhaSolicitar']);
$router->get('/recuperar-senha/codigo', [\App\Controllers\AuthController::class, 'recuperarSenhaCodigo']);
$router->post('/recuperar-senha/redefinir', [\App\Controllers\AuthController::class, 'recuperarSenhaRedefinir']);

$router->get('/termos', [\App\Controllers\AuthController::class, 'termos']);

// 0.1. Integrações Externas e Webhooks Bancários (Público de Servidor para Servidor - Fase 9)
$router->post('/webhook/pix', [\App\Controllers\WebhookController::class, 'pix']);
$router->get('/webhook/pix', [\App\Controllers\WebhookController::class, 'pix']);

// Rotas Protegidas por Perfil (RBAC)
// 1. Portal da Família: Acesso exclusivo do perfil 'responsavel'
$router->get('/portal', [\App\Controllers\PortalController::class, 'index'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/alergias', [\App\Controllers\PortalController::class, 'alergias'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/alergias/salvar', [\App\Controllers\PortalController::class, 'alergiasSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);

// 1.1. Agendamento Semanal de Lanches e Pedidos (Fase 10)
$router->get('/portal/agendamento', [\App\Controllers\PortalController::class, 'agendamento'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/agendamento/salvar', [\App\Controllers\PortalController::class, 'agendamentoSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/pedidos', [\App\Controllers\PortalController::class, 'pedidos'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/pedidos/cancelar', [\App\Controllers\PortalController::class, 'pedidoCancelar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/pedidos/editar/{id}', [\App\Controllers\PortalController::class, 'pedidoEditar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/pedidos/editar/{id}', [\App\Controllers\PortalController::class, 'pedidoEditarSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);

// 1.1. Carteira Digital Familiar e Recargas Pix (Fase 9)
$router->get('/portal/carteira', [\App\Controllers\CarteiraController::class, 'index'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/recarga/gerar', [\App\Controllers\CarteiraController::class, 'gerarPix'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/recarga/status/{txid}', [\App\Controllers\CarteiraController::class, 'statusPix'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/recarga/simular/{txid}', [\App\Controllers\CarteiraController::class, 'simularPagamento'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);

// 2. Painel Operacional: Acesso permitido para 'operador' e 'administrador'
$router->get('/admin', [\App\Controllers\AdminController::class, 'index'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 2.1. Corte do Turno e Gestão de Fornecedores (Fase 11)
$router->get('/operacional/corte', [\App\Controllers\PedidosController::class, 'corte'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/corte/pdf', [\App\Controllers\PedidosController::class, 'cortePdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/corte/imprimir', [\App\Controllers\PedidosController::class, 'corteImprimir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/recebimento', [\App\Controllers\PedidosController::class, 'recebimento'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/recebimento/confirmar', [\App\Controllers\PedidosController::class, 'recebimentoConfirmar'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 3. Área Administrativa e Cadastros Mestres (Fases 7 e 8 — FSD Módulos 6, 7 e 9)
// 3.1. Configurações Globais (Acesso exclusivo para 'administrador')
$router->get('/admin/configuracoes', [\App\Controllers\AdminController::class, 'configuracoes'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/configuracoes/salvar', [\App\Controllers\AdminController::class, 'configuracoesSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 3.2. Categorias de Cardápio (Acesso exclusivo para 'administrador')
$router->get('/admin/categorias', [\App\Controllers\AdminController::class, 'categorias'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/categorias/salvar', [\App\Controllers\AdminController::class, 'categoriasSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/categorias/excluir', [\App\Controllers\AdminController::class, 'categoriasExcluir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 3.3. Produtos e Cardápio (Consulta e Pausa para operador/admin; Criação/Edição/Exclusão para admin)
$router->get('/admin/produtos', [\App\Controllers\AdminController::class, 'produtos'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/admin/produtos/salvar', [\App\Controllers\AdminController::class, 'produtosSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/produtos/toggle-pausa', [\App\Controllers\AdminController::class, 'produtosTogglePausa'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/admin/produtos/excluir', [\App\Controllers\AdminController::class, 'produtosExcluir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 3.4. Colaboradores / Desconto em Folha (Acesso exclusivo para 'administrador')
$router->get('/admin/colaboradores', [\App\Controllers\AdminController::class, 'colaboradores'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/colaboradores/salvar', [\App\Controllers\AdminController::class, 'colaboradoresSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/colaboradores/toggle-ativo', [\App\Controllers\AdminController::class, 'colaboradoresToggleAtivo'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/colaboradores/excluir', [\App\Controllers\AdminController::class, 'colaboradoresExcluir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 3.5. Turmas Escolares (Acesso exclusivo para 'administrador')
$router->get('/admin/turmas', [\App\Controllers\AdminController::class, 'turmas'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/turmas/salvar', [\App\Controllers\AdminController::class, 'turmasSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/turmas/excluir', [\App\Controllers\AdminController::class, 'turmasExcluir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 3.6. Importação Escolar em Lote (Fase 8 — Acesso exclusivo para 'administrador')
$router->get('/admin/importacao-escolar', [\App\Controllers\AdminController::class, 'importacaoEscolar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/importacao-escolar/analisar', [\App\Controllers\AdminController::class, 'importacaoEscolarAnalisar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/importacao-escolar/processar', [\App\Controllers\AdminController::class, 'importacaoEscolarProcessar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/importacao-escolar/cancelar', [\App\Controllers\AdminController::class, 'importacaoEscolarCancelar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/admin/importacao-escolar/modelo', [\App\Controllers\AdminController::class, 'importacaoEscolarModelo'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 3.7. Gestão de Alunos e Matrículas (Fase 8 — Acesso exclusivo para 'administrador')
$router->get('/admin/alunos', [\App\Controllers\AdminController::class, 'alunos'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/alunos/salvar', [\App\Controllers\AdminController::class, 'alunosSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/alunos/inativar', [\App\Controllers\AdminController::class, 'alunosInativar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/alunos/reativar', [\App\Controllers\AdminController::class, 'alunosReativar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 9. Despacha a requisição HTTP atual
$router->dispatch();
