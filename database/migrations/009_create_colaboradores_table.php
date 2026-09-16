<?php
/**
 * Migration 009 — Criação da tabela colaboradores
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `colaboradores` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `matricula_funcional` VARCHAR(30) NOT NULL UNIQUE,
            `nome` VARCHAR(100) NOT NULL,
            `cargo` VARCHAR(50) NOT NULL,
            `foto_url` VARCHAR(255) NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_colaboradores_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_colaboradores_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_colaboradores_mat` (`matricula_funcional`),
            INDEX `idx_colaboradores_nome` (`nome`),
            INDEX `idx_colaboradores_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `colaboradores`;");
    }
};
