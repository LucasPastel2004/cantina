<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Executor Seguro de Migrações do Banco de Dados
 * 
 * Pode ser executado via linha de comando (CLI):
 *   php database/runner.php
 * 
 * Ou via requisição HTTP autenticada por token:
 *   GET /database/runner.php?token=SEU_TOKEN
 */

// Define constante de segurança se executado via CLI
if (php_sapi_name() === 'cli' && !defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

// Carrega a configuração
$configFile = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configFile)) {
    die("Arquivo de configuração não encontrado.\n");
}
$config = require $configFile;

// Se for via HTTP, valida token de segurança
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    $expectedToken = $config['security']['migration_token'] ?? '';
    if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        die("Acesso negado: Token de migração inválido.\n");
    }
}

echo "=== Cantina Web — Executor de Migrações ===\n";
echo "Ambiente: " . $config['app']['env'] . "\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "Status: Estrutura de migrações pronta para a Fase 3 (Criação das Migrações 001 a 026).\n";
