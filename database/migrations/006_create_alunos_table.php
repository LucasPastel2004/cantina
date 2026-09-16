<?php
/**
 * Migration 006 — Criação da tabela alunos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `alunos` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `responsavel_id` INT UNSIGNED NOT NULL,
            `turma_id` INT UNSIGNED NOT NULL,
            `matricula` VARCHAR(30) NOT NULL UNIQUE,
            `nome` VARCHAR(100) NOT NULL,
            `foto_url` VARCHAR(255) NULL,
            `turno` ENUM('matutino', 'vespertino') NOT NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_alunos_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `responsaveis` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_alunos_turma` FOREIGN KEY (`turma_id`) REFERENCES `turmas` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_alunos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_alunos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_alunos_resp` (`responsavel_id`),
            INDEX `idx_alunos_turma` (`turma_id`),
            INDEX `idx_alunos_nome` (`nome`),
            INDEX `idx_alunos_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `alunos`;");
    }
};
