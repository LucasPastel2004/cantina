<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo Base com Abstração PDO e Prepared Statements Estritos
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

require_once __DIR__ . '/Sanitizer.php';

abstract class Model
{
    /**
     * Instância única compartilhada da conexão PDO (Singleton)
     */
    protected static ?\PDO $pdo = null;

    /**
     * Nome da tabela associada ao modelo
     */
    protected string $table = '';

    /**
     * Chave primária da tabela
     */
    protected string $primaryKey = 'id';

    /**
     * Indica se a tabela possui controle de data de criação e atualização (created_at, updated_at)
     */
    protected bool $timestamps = true;

    /**
     * Indica se a tabela utiliza exclusão lógica (deleted_at)
     */
    protected bool $softDelete = true;

    /**
     * Obtém ou inicializa a conexão com o banco de dados MySQL via PDO
     */
    public static function getConnection(): \PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Se existir o arquivo central de conexão database.php, utiliza-o
        $databaseFactory = __DIR__ . '/../../config/database.php';
        if (file_exists($databaseFactory)) {
            $conn = require $databaseFactory;
            if ($conn instanceof \PDO) {
                self::$pdo = $conn;
                return self::$pdo;
            }
        }

        // Fallback: Constrói a conexão a partir do config.php
        $configFile = __DIR__ . '/../../config/config.php';
        if (!file_exists($configFile)) {
            throw new \RuntimeException("Arquivo de configuração [config/config.php] não encontrado.");
        }

        $config = require $configFile;
        $db = $config['database'] ?? $config['db'] ?? [];

        $host     = $db['host'] ?? '127.0.0.1';
        $port     = $db['port'] ?? 3306;
        $database = $db['database'] ?? 'cantina_db';
        $username = $db['username'] ?? 'root';
        $password = $db['password'] ?? '';
        $charset  = $db['charset'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false, // Prepared Statements nativos obrigatórios
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE " . ($db['collation'] ?? 'utf8mb4_unicode_ci')
        ];

        try {
            self::$pdo = new \PDO($dsn, $username, $password, $options);
            return self::$pdo;
        } catch (\PDOException $e) {
            // Lança exceção para que o ErrorHandler capture e registre no log de contingência
            throw new \RuntimeException("Falha ao estabelecer conexão com o banco de dados: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Alias para getConnection()
     */
    public static function getPdo(): \PDO
    {
        return self::getConnection();
    }

    /**
     * Define manualmente a instância PDO (útil para testes unitários e mocks)
     */
    public static function setConnection(?\PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Inicia uma transação com o banco de dados
     */
    public static function beginTransaction(): bool
    {
        $conn = self::getConnection();
        if (!$conn->inTransaction()) {
            return $conn->beginTransaction();
        }
        return false;
    }

    /**
     * Confirma a transação atual
     */
    public static function commit(): bool
    {
        $conn = self::getConnection();
        if ($conn->inTransaction()) {
            return $conn->commit();
        }
        return false;
    }

    /**
     * Reverte a transação atual
     */
    public static function rollBack(): bool
    {
        $conn = self::getConnection();
        if ($conn->inTransaction()) {
            return $conn->rollBack();
        }
        return false;
    }

    /**
     * Verifica se há uma transação ativa
     */
    public static function inTransaction(): bool
    {
        return self::getConnection()->inTransaction();
    }

    /**
     * Executa uma consulta preparada com parâmetros e retorna o PDOStatement
     *
     * @param string $sql Consulta SQL com marcadores nomeados ou posicionais
     * @param array $params Parâmetros para binding
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Executa uma consulta e retorna um único registro (ou null)
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        if (!$result) {
            return null;
        }
        return Sanitizer::repararUtf8($result);
    }

    /**
     * Executa uma consulta e retorna todos os registros
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetchAll();
        if (empty($result)) {
            return [];
        }
        return Sanitizer::repararUtf8($result);
    }

    /**
     * Executa uma instrução INSERT, UPDATE ou DELETE e retorna o número de linhas afetadas
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Retorna o ID gerado pelo último INSERT
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Busca um registro pelo ID
     */
    public function find(mixed $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        if ($this->softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Retorna todos os registros ativos da tabela
     */
    public function all(string $orderBy = 'id ASC', int $limit = 1000): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($this->softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
        }
        $sql .= " ORDER BY {$orderBy} LIMIT {$limit}";

        return self::fetchAll($sql);
    }

    /**
     * Insere um novo registro na tabela
     */
    public function create(array $data): int
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        }

        $fields = array_keys($data);
        $columns = implode(', ', $fields);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        self::execute($sql, $data);

        return (int)self::lastInsertId();
    }

    /**
     * Atualiza um registro existente
     */
    public function update(mixed $id, array $data): int
    {
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $setParts = [];
        foreach (array_keys($data) as $field) {
            $setParts[] = "{$field} = :{$field}";
        }
        $setString = implode(', ', $setParts);

        $data['id_param'] = $id;
        $sql = "UPDATE {$this->table} SET {$setString} WHERE {$this->primaryKey} = :id_param";

        return self::execute($sql, $data);
    }

    /**
     * Exclui um registro (ou aplica soft delete com preenchimento de deleted_at)
     */
    public function delete(mixed $id): int
    {
        if ($this->softDelete) {
            $sql = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE {$this->primaryKey} = :id";
            return self::execute($sql, [
                'deleted_at' => date('Y-m-d H:i:s'),
                'id'         => $id
            ]);
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return self::execute($sql, ['id' => $id]);
    }
}
