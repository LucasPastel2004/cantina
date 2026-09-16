<?php
/**
 * Migration 008 — Criação da tabela aluno_restricoes (Pivot)
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `aluno_restricoes` (
            `aluno_id` INT UNSIGNED NOT NULL,
            `restricao_id` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`aluno_id`, `restricao_id`),
            CONSTRAINT `fk_ar_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_ar_restricao` FOREIGN KEY (`restricao_id`) REFERENCES `restricoes_alimentares` (`id`) ON DELETE RESTRICT,
            INDEX `idx_ar_restricao` (`restricao_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `aluno_restricoes`;");
    }
};
