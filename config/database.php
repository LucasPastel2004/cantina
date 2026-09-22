<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Fábrica Singleton de Conexão com Banco de Dados MySQL via PDO
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

if (!class_exists('Database')) {
    class Database
    {
        /**
         * Instância Singleton de PDO
         */
        private static ?\PDO $instance = null;

        /**
         * Construtor privado para impedir instanciação direta
         */
        private function __construct() {}

        /**
         * Impede clonagem
         */
        private function __clone() {}

        /**
         * Impede desserialização
         */
        public function __wakeup()
        {
            throw new \RuntimeException("Desserialização da conexão de banco de dados não é permitida.");
        }

        /**
         * Retorna a instância única de PDO
         *
         * @return \PDO
         * @throws \PDOException Em caso de falha na conexão
         */
        public static function getInstance(): \PDO
        {
            if (self::$instance !== null) {
                return self::$instance;
            }

            $configFile = __DIR__ . '/config.php';
            if (!file_exists($configFile)) {
                throw new \RuntimeException("Arquivo de configuração [config/config.php] não encontrado.");
            }

            $config = require $configFile;
            $dbConfig = $config['db'] ?? $config['database'] ?? [];

            $driver    = $dbConfig['driver'] ?? 'mysql';
            $host      = $dbConfig['host'] ?? '127.0.0.1';
            $port      = (int)($dbConfig['port'] ?? 3306);
            $database  = $dbConfig['database'] ?? 'cantina';
            $username  = $dbConfig['username'] ?? 'root';
            $password  = $dbConfig['password'] ?? '';
            $charset   = $dbConfig['charset'] ?? 'utf8mb4';
            $collation = $dbConfig['collation'] ?? 'utf8mb4_unicode_ci';

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";

            $options = [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false, // Prepared Statements nativos obrigatórios
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$collation}, time_zone = '-03:00'"
            ];

            // Mescla opções customizadas do config, se houver
            if (!empty($dbConfig['options']) && is_array($dbConfig['options'])) {
                foreach ($dbConfig['options'] as $optKey => $optVal) {
                    $options[$optKey] = $optVal;
                }
            }

            try {
                self::$instance = new \PDO($dsn, $username, $password, $options);
                return self::$instance;
            } catch (\PDOException $e) {
                // Grava no log de contingência em storage/logs/error.log
                $logFile = dirname(__DIR__) . '/storage/logs/error.log';
                $logDir  = dirname($logFile);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }

                $date = date('Y-m-d H:i:s');
                $msg  = "[{$date}] [CRITICAL] [DATABASE] Falha de conexão com o banco ({$dsn}): " . $e->getMessage() . PHP_EOL;
                @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);

                throw new \PDOException("Erro ao conectar ao banco de dados: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        /**
         * Define manualmente a instância PDO (útil para testes unitários com mock)
         */
        public static function setInstance(?\PDO $pdo): void
        {
            self::$instance = $pdo;
        }

        /**
         * Fecha a conexão atual liberando recursos
         */
        public static function disconnect(): void
        {
            self::$instance = null;
        }
    }
}

// Retorna a instância PDO para chamadas via require
return Database::getInstance();
