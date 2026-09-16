<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Executor Seguro e Idempotente de Migrações do Banco de Dados
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 * 
 * Modos de Execução:
 * 1. CLI (Linha de Comando):
 *    php database/runner.php                 # Aplica todas as migrações pendentes
 *    php database/runner.php --status        # Lista o status de cada migração
 *    php database/runner.php --rollback      # Reverte a última migração aplicada
 * 
 * 2. HTTP (Requisição Web Protegida):
 *    GET /database/runner.php?token=TOKEN
 *    GET /database/runner.php?token=TOKEN&action=status
 *    GET /database/runner.php?token=TOKEN&action=rollback
 */

// Define constante de segurança para carregamento interno
if (!defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

// Carrega configuração
$configFile = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die("Erro: Arquivo de configuração [config/config.php] não encontrado.\n");
}
$config = require $configFile;

$isCli = (php_sapi_name() === 'cli');

// Se for via HTTP, valida token de segurança antes de qualquer outra operação
if (!$isCli) {
    $token = $_GET['token'] ?? '';
    $expectedToken = $config['security']['migration_token'] ?? '';

    if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        die("Acesso negado: Token de migração inválido ou ausente.\n");
    }

    header('Content-Type: text/plain; charset=utf-8');
}

// Conecta ao banco via database.php singleton
try {
    /** @var \PDO $pdo */
    $pdo = require dirname(__DIR__) . '/config/database.php';
} catch (\Throwable $e) {
    die("Erro fatal de banco de dados: " . $e->getMessage() . "\n");
}

// Cria tabela migrations_controle antecipadamente de forma segura e idempotente
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `migrations_controle` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `migration_arquivo` VARCHAR(191) NOT NULL UNIQUE,
        `executado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Determina a ação solicitada
$action = 'run';
if ($isCli) {
    global $argv;
    if (in_array('--status', $argv ?? [])) {
        $action = 'status';
    } elseif (in_array('--rollback', $argv ?? [])) {
        $action = 'rollback';
    }
} else {
    $action = $_GET['action'] ?? 'run';
}

// Busca migrações já executadas
$stmt = $pdo->query("SELECT `migration_arquivo`, `executado_em` FROM `migrations_controle` ORDER BY `id` ASC");
$executedMigrations = [];
while ($row = $stmt->fetch()) {
    $executedMigrations[$row['migration_arquivo']] = $row['executado_em'];
}

// Escaneia a pasta database/migrations
$migrationsDir = __DIR__ . '/migrations';
if (!is_dir($migrationsDir)) {
    @mkdir($migrationsDir, 0755, true);
}

$allFiles = scandir($migrationsDir);
$migrationFiles = [];
foreach ($allFiles as $file) {
    if (preg_match('/^\d{3}_.*\.php$/', $file)) {
        $migrationFiles[] = $file;
    }
}
sort($migrationFiles, SORT_STRING);

echo "======================================================\n";
echo "   Cantina Web — Executor de Migrações (Hostnet/Local)\n";
echo "   Ambiente: " . ($config['app']['env'] ?? 'development') . "\n";
echo "   Data/Hora: " . date('Y-m-d H:i:s') . "\n";
echo "======================================================\n\n";

// Ação: STATUS
if ($action === 'status') {
    echo "Status das Migrações:\n";
    echo str_repeat('-', 70) . "\n";
    printf("%-45s | %-12s | %s\n", "Arquivo de Migração", "Status", "Executado Em");
    echo str_repeat('-', 70) . "\n";

    foreach ($migrationFiles as $file) {
        if (isset($executedMigrations[$file])) {
            printf("%-45s | [APLICADA]   | %s\n", $file, $executedMigrations[$file]);
        } else {
            printf("%-45s | [PENDENTE]   | -\n", $file);
        }
    }
    echo str_repeat('-', 70) . "\n";
    exit(0);
}

// Ação: ROLLBACK
if ($action === 'rollback') {
    if (empty($executedMigrations)) {
        echo "Nenhuma migração encontrada para reverter.\n";
        exit(0);
    }

    end($executedMigrations);
    $lastFile = key($executedMigrations);
    $filePath = $migrationsDir . '/' . $lastFile;

    echo "Revertendo migração: {$lastFile}...\n";

    if (!file_exists($filePath)) {
        die("Erro: Arquivo [{$lastFile}] não encontrado no disco para rollback.\n");
    }

    try {
        $migration = require $filePath;
        if (is_object($migration) && method_exists($migration, 'down')) {
            $migration->down($pdo);
        }

        $delStmt = $pdo->prepare("DELETE FROM `migrations_controle` WHERE `migration_arquivo` = ?");
        $delStmt->execute([$lastFile]);

        echo "=> Sucesso: Migração [{$lastFile}] revertida com êxito.\n";
    } catch (\Throwable $e) {
        echo "!! ERRO ao reverter [{$lastFile}]: " . $e->getMessage() . "\n";
        exit(1);
    }
    exit(0);
}

// Ação: RUN (Aplica migrações pendentes)
$pendingCount = 0;
foreach ($migrationFiles as $file) {
    if (!isset($executedMigrations[$file])) {
        $pendingCount++;
    }
}

if ($pendingCount === 0) {
    echo "Tudo atualizado! Nenhuma migração pendente encontrada.\n";
    echo "Total de migrações ativas: " . count($executedMigrations) . "\n";
    exit(0);
}

echo "Encontradas {$pendingCount} migração(ões) pendente(s) para aplicação.\n\n";

foreach ($migrationFiles as $file) {
    if (isset($executedMigrations[$file])) {
        continue;
    }

    $filePath = $migrationsDir . '/' . $file;
    echo ">> Aplicando: {$file}... ";

    try {
        $migration = require $filePath;

        if (is_object($migration) && method_exists($migration, 'up')) {
            $migration->up($pdo);
        } else {
            throw new \RuntimeException("O arquivo de migração não retornou um objeto válido com o método 'up(\$pdo)'.");
        }

        // Registra na tabela de controle
        $insStmt = $pdo->prepare("INSERT INTO `migrations_controle` (`migration_arquivo`, `executado_em`) VALUES (?, NOW())");
        $insStmt->execute([$file]);

        echo "[OK]\n";
    } catch (\Throwable $e) {
        echo "[FALHA]\n";
        echo "\n------------------------------------------------------\n";
        echo "ERRO FATAL na migração [{$file}]:\n";
        echo $e->getMessage() . "\n";
        echo "Linha: " . $e->getLine() . " | Arquivo: " . $e->getFile() . "\n";
        echo "------------------------------------------------------\n";
        echo "Execução de migrações interrompida para preservar a integridade do banco de dados.\n";
        exit(1);
    }
}

echo "\n=> Todas as {$pendingCount} migrações pendentes foram aplicadas com sucesso!\n";
