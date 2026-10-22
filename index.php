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

// 3. Registra o Autoloader PSR-4 nativo para o namespace App\ (compatível com Linux case-sensitive e Windows)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $parts = explode('\\', $relativeClass);
    $className = array_pop($parts);
    $subDir = strtolower(implode('/', $parts));
    $file = $baseDir . ($subDir !== '' ? $subDir . '/' : '') . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
        return;
    }

    $fallbackFile = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($fallbackFile)) {
        require_once $fallbackFile;
    }
});

// 3.1 Registra helper global para reparação defensiva de UTF-8
if (!function_exists('repararUtf8')) {
    function repararUtf8(mixed $input): mixed
    {
        return \App\Core\Sanitizer::repararUtf8($input);
    }
}

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

// 0.1. Integrações Externas e Webhooks Bancários (Público de Servidor para Servidor - Fase 9 e Fase 16)
$router->post('/webhook/pix', [\App\Controllers\WebhookController::class, 'pix']);
$router->get('/webhook/pix', [\App\Controllers\WebhookController::class, 'pix']);
$router->post('/webhook/pix/devolucao', [\App\Controllers\WebhookController::class, 'devolucao']);
$router->post('/webhook/pix/med', [\App\Controllers\WebhookController::class, 'devolucao']);

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
$router->post('/portal/recarga/cancelar', [\App\Controllers\CarteiraController::class, 'cancelarPix'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/recarga/cancelar/{txid}', [\App\Controllers\CarteiraController::class, 'cancelarPix'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/extrato', [\App\Controllers\PortalController::class, 'extrato'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/extrato/pdf', [\App\Controllers\PortalController::class, 'extratoPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/extrato/imprimir', [\App\Controllers\PortalController::class, 'extratoImprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->get('/portal/perfil', [\App\Controllers\PortalController::class, 'perfil'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_RESPONSAVEL)
]);
$router->post('/portal/perfil/senha', [\App\Controllers\PortalController::class, 'alterarSenha'], [
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

// 2.3. Gestão e Impressão de Tickets Térmicos em Bobina de 80mm (Fase 12 — FSD Área 3 e Fluxos 6 e 7)
$router->get('/operacional/tickets', [\App\Controllers\TicketsController::class, 'index'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/tickets/lote', [\App\Controllers\TicketsController::class, 'lote'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/tickets/lote/pdf', [\App\Controllers\TicketsController::class, 'lotePdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/tickets/turma/{id}', [\App\Controllers\TicketsController::class, 'turma'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/tickets/turma/{id}/pdf', [\App\Controllers\TicketsController::class, 'turmaPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/tickets/segunda-via', [\App\Controllers\TicketsController::class, 'segundaVia'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/tickets/imprimir/{id}', [\App\Controllers\TicketsController::class, 'imprimirTicket'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/tickets/imprimir/{id}/pdf', [\App\Controllers\TicketsController::class, 'imprimirTicketPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 2.4. Ponto de Venda (PDV) e Operação de Balcão (Fase 13 — FSD Área 4 e Módulo 5)
$router->get('/operacional/pdv', [\App\Controllers\PdvController::class, 'index'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/pdv/caixa/abrir', [\App\Controllers\PdvController::class, 'abrirCaixaRapido'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/pdv/finalizar', [\App\Controllers\PdvController::class, 'finalizarVenda'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/pdv/pix/gerar', [\App\Controllers\PdvController::class, 'pixGerar'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/pdv/pix/status/{txid}', [\App\Controllers\PdvController::class, 'pixStatus'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/pdv/colaborador/buscar', [\App\Controllers\PdvController::class, 'buscarColaborador'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/pdv/aluno/buscar', [\App\Controllers\PdvController::class, 'buscarAluno'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/pdv/estorno', [\App\Controllers\PdvController::class, 'estornoBalcao'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/pdv/sobras', [\App\Controllers\PdvController::class, 'sobras'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/pdv/sobras/transferir', [\App\Controllers\PdvController::class, 'transferirSobras'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/pdv/comprovante/{id}', [\App\Controllers\PdvController::class, 'comprovanteHtml'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/pdv/comprovante/{id}/pdf', [\App\Controllers\PdvController::class, 'comprovantePdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 2.5. Controle Financeiro e Fechamento de Caixas por Turno (Fase 15 — FSD Área 4, Área 6 e Módulo 7)
$router->get('/operacional/caixa', [\App\Controllers\CaixaController::class, 'index'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/abertura', [\App\Controllers\CaixaController::class, 'abrirTela'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/caixa/abrir', [\App\Controllers\CaixaController::class, 'abrir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/fechamento', [\App\Controllers\CaixaController::class, 'fecharTela'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/caixa/fechar', [\App\Controllers\CaixaController::class, 'fechar'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/buscar-responsavel', [\App\Controllers\CaixaController::class, 'buscarResponsavel'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/caixa/recarga-dinheiro', [\App\Controllers\CaixaController::class, 'recargaDinheiro'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/detalhes/{id}', [\App\Controllers\CaixaController::class, 'detalhes'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/comprovante-recarga/{id}', [\App\Controllers\CaixaController::class, 'comprovanteRecargaHtml'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/comprovante-recarga/{id}/pdf', [\App\Controllers\CaixaController::class, 'comprovanteRecargaPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/espelho/{id}', [\App\Controllers\CaixaController::class, 'espelhoHtml'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/espelho/{id}/pdf', [\App\Controllers\CaixaController::class, 'espelhoPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/fechamento/pdf', [\App\Controllers\CaixaController::class, 'fechamentoPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/caixa/fechamento/pdf/{id}', [\App\Controllers\CaixaController::class, 'fechamentoPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 2.6. Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas (Fase 14 — FSD Área 5 e Módulo 6)
$router->get('/estoque', [\App\Controllers\EstoqueController::class, 'index'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/estoque/perdas', [\App\Controllers\EstoqueController::class, 'perdas'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/estoque/perdas/salvar', [\App\Controllers\EstoqueController::class, 'perdasSalvar'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/estoque/entrada', [\App\Controllers\EstoqueController::class, 'entrada'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/estoque/entrada/salvar', [\App\Controllers\EstoqueController::class, 'entradaSalvar'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/estoque/minimo', [\App\Controllers\EstoqueController::class, 'atualizarEstoqueMinimo'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/estoque/importar-xml', [\App\Controllers\EstoqueController::class, 'importarXml'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/estoque/importar-xml/analisar', [\App\Controllers\EstoqueController::class, 'importarXmlAnalisar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/estoque/importar-xml/processar', [\App\Controllers\EstoqueController::class, 'importarXmlProcessar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/estoque/importar-xml/cancelar', [\App\Controllers\EstoqueController::class, 'importarXmlCancelar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/estoque/notas-fiscais', [\App\Controllers\EstoqueController::class, 'notasFiscais'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/estoque/notas-fiscais/{id}', [\App\Controllers\EstoqueController::class, 'notaFiscalDetalhes'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/estoque/notas-fiscais/{id}/download', [\App\Controllers\EstoqueController::class, 'downloadXml'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/estoque/notas-fiscais/{id}/xml', [\App\Controllers\EstoqueController::class, 'visualizarXml'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
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

// 3.3. Produtos e Cardápio (Consulta, Criação, Edição e Exclusão exclusivo para admin; Pausa rápida para operador via POST)
$router->get('/admin/produtos', [\App\Controllers\AdminController::class, 'produtos'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
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

// 3.8. Calendário Escolar (Gestão de Dias Letivos, Feriados, Recessos e Suspensão Emergencial)
$router->get('/admin/calendario', [\App\Controllers\AdminController::class, 'calendario'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/calendario/salvar', [\App\Controllers\AdminController::class, 'calendarioSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/calendario/excluir', [\App\Controllers\AdminController::class, 'calendarioExcluir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/calendario/emergencial', [\App\Controllers\AdminController::class, 'calendarioEmergencial'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 4. Central de Relatórios Gerenciais, Extratos e Exportações (Fase 17 — FSD Área 6 & Módulo 8)
// 4.1. Hub e Relatórios Estratégicos (Acesso exclusivo 'administrador')
$router->get('/relatorios', [\App\Controllers\RelatoriosController::class, 'index'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-alimento', [\App\Controllers\RelatoriosController::class, 'vendasAlimento'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-alimento/pdf', [\App\Controllers\RelatoriosController::class, 'vendasAlimentoPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-alimento/csv', [\App\Controllers\RelatoriosController::class, 'vendasAlimentoCsv'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-filtros', [\App\Controllers\RelatoriosController::class, 'vendasFiltros'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-filtros/pdf', [\App\Controllers\RelatoriosController::class, 'vendasFiltrosPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-filtros/imprimir', [\App\Controllers\RelatoriosController::class, 'vendasFiltrosImprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/vendas-filtros/csv', [\App\Controllers\RelatoriosController::class, 'vendasFiltrosCsv'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha', [\App\Controllers\RelatoriosController::class, 'descontoFolha'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha/pdf', [\App\Controllers\RelatoriosController::class, 'descontoFolhaPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha/csv', [\App\Controllers\RelatoriosController::class, 'descontoFolhaCsv'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha/detalhes-ajax', [\App\Controllers\RelatoriosController::class, 'descontoFolhaDetalhesAjax'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha/individual-pdf', [\App\Controllers\RelatoriosController::class, 'descontoFolhaIndividualPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha/individual-imprimir', [\App\Controllers\RelatoriosController::class, 'descontoFolhaIndividualImprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/desconto-folha/lote-pdf', [\App\Controllers\RelatoriosController::class, 'descontoFolhaLotePdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/fechamento-caixa', [\App\Controllers\RelatoriosController::class, 'fechamentoCaixa'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/devedores', [\App\Controllers\RelatoriosController::class, 'devedores'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/devedores/pdf', [\App\Controllers\RelatoriosController::class, 'devedoresPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/devedores/imprimir', [\App\Controllers\RelatoriosController::class, 'devedoresImprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 4.x. Relatório Consolidado de Corte do Turno para Fornecedores
$router->get('/relatorios/corte', [\App\Controllers\RelatoriosController::class, 'corte'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/corte/pdf', [\App\Controllers\RelatoriosController::class, 'cortePdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/corte/imprimir', [\App\Controllers\RelatoriosController::class, 'corteImprimir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 4.2. Extrato Analítico do Aluno (Operador de Balcão e Administrador — FSD Tela 8.5 & Seção 8)
$router->get('/operacional/extrato-aluno', [\App\Controllers\RelatoriosController::class, 'extratoAluno'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/extrato-aluno', [\App\Controllers\RelatoriosController::class, 'extratoAluno'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/extrato-aluno/pdf', [\App\Controllers\RelatoriosController::class, 'extratoAlunoPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/extrato-aluno/pdf', [\App\Controllers\RelatoriosController::class, 'extratoAlunoPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/extrato-aluno/imprimir', [\App\Controllers\RelatoriosController::class, 'extratoAlunoImprimir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/extrato-aluno/imprimir', [\App\Controllers\RelatoriosController::class, 'extratoAlunoImprimir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/extrato-aluno/csv', [\App\Controllers\RelatoriosController::class, 'extratoAlunoCsv'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/extrato-aluno/csv', [\App\Controllers\RelatoriosController::class, 'extratoAlunoCsv'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/relatorios/buscar-alunos-ajax', [\App\Controllers\RelatoriosController::class, 'buscarAlunosAjax'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 4.3. Estorno de Vendas da Cantina e Invalidação de Tickets (Fase 19 — FSD Área 4 e Guideline Oficial)
$router->get('/operacional/estornos', [\App\Controllers\EstornoVendasController::class, 'index'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->get('/operacional/estornos/detalhes', [\App\Controllers\EstornoVendasController::class, 'detalhesAjax'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);
$router->post('/operacional/estornos/gravar', [\App\Controllers\EstornoVendasController::class, 'gravar'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_OPERADOR, \App\Core\RBAC::ROLE_ADMINISTRADOR])
]);

// 4.4. Relatório Gerencial de Movimentações de Estoque (Fase 23 — FSD Área 5, Módulo 6 & Guideline Escolar Manager)
$router->get('/relatorios/estoque-filtros', [\App\Controllers\RelatoriosController::class, 'estoqueFiltros'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/estoque-movimentacoes', [\App\Controllers\RelatoriosController::class, 'estoqueMovimentacoes'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/estoque-movimentacoes/pdf', [\App\Controllers\RelatoriosController::class, 'estoqueMovimentacoesPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/estoque-movimentacoes/imprimir', [\App\Controllers\RelatoriosController::class, 'estoqueMovimentacoesImprimir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/estoque-movimentacoes/csv', [\App\Controllers\RelatoriosController::class, 'estoqueMovimentacoesCsv'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);

// 4.5. Relatório de Arrecadação Financeira & Fluxo de Entradas (Cash-In Real)
$router->get('/relatorios/arrecadacao', [\App\Controllers\RelatoriosController::class, 'arrecadacao'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/arrecadacao/pdf', [\App\Controllers\RelatoriosController::class, 'arrecadacaoPdf'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/arrecadacao/imprimir', [\App\Controllers\RelatoriosController::class, 'arrecadacaoImprimir'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);
$router->get('/relatorios/arrecadacao/csv', [\App\Controllers\RelatoriosController::class, 'arrecadacaoCsv'], [
    \App\Core\RBAC::requireRole([\App\Core\RBAC::ROLE_ADMINISTRADOR, \App\Core\RBAC::ROLE_OPERADOR])
]);


// 5. Gestão de Contestações e Mecanismo Especial de Devolução - MED Pix (Fase 16 — Acesso exclusivo 'administrador')
$router->get('/admin/contestacoes', [\App\Controllers\AdminController::class, 'contestacoes'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/contestacoes/manual', [\App\Controllers\AdminController::class, 'contestacaoManualSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/contestacoes/quitar', [\App\Controllers\AdminController::class, 'quitacaoMedSalvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/admin/contestacoes/desbloquear', [\App\Controllers\AdminController::class, 'desbloquearContaMed'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/admin/contestacoes/sugestoes', [\App\Controllers\AdminController::class, 'contestacoesSugestoes'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/admin/contestacoes/pdf', [\App\Controllers\AdminController::class, 'contestacoesPdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/admin/contestacoes/imprimir', [\App\Controllers\AdminController::class, 'contestacoesImprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
// 6. Módulo Financeiro: Despesas Operacionais e DRE Gerencial Completo (Fase 24 — FSD, Diretrizes e Planilha Modelo Aethos)
$router->get('/financeiro/despesas', [\App\Controllers\DespesasController::class, 'index'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/financeiro/despesas/salvar', [\App\Controllers\DespesasController::class, 'salvar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/financeiro/despesas/{id}/json', [\App\Controllers\DespesasController::class, 'editarJson'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/financeiro/despesas/atualizar', [\App\Controllers\DespesasController::class, 'atualizar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/financeiro/despesas/pagar', [\App\Controllers\DespesasController::class, 'pagar'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->post('/financeiro/despesas/excluir', [\App\Controllers\DespesasController::class, 'excluir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// DRE Gerencial e Gráficos
$router->get('/financeiro/dre', [\App\Controllers\DreController::class, 'index'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/financeiro/dre/graficos-json', [\App\Controllers\DreController::class, 'graficosJson'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/financeiro/dre/imprimir', [\App\Controllers\DreController::class, 'imprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/financeiro/dre/pdf', [\App\Controllers\DreController::class, 'pdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/financeiro/dre/csv', [\App\Controllers\DreController::class, 'csv'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
// Atalhos compatíveis na Central de Relatórios
$router->get('/relatorios/dre', [\App\Controllers\DreController::class, 'index'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/dre/pdf', [\App\Controllers\DreController::class, 'pdf'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/dre/imprimir', [\App\Controllers\DreController::class, 'imprimir'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);
$router->get('/relatorios/dre/csv', [\App\Controllers\DreController::class, 'csv'], [
    \App\Core\RBAC::requireRole(\App\Core\RBAC::ROLE_ADMINISTRADOR)
]);

// 9. Despacha a requisição HTTP atual
$router->dispatch();

