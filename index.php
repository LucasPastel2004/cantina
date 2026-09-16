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

// 9. Despacha a requisição HTTP atual
$router->dispatch();
