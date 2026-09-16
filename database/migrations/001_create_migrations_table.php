<?php
/**
 * Migration 001 — Criação da tabela migrations_controle
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations_controle` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration_arquivo` VARCHAR(191) NOT NULL UNIQUE,
            `executado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `migrations_controle`;");
    }
};
