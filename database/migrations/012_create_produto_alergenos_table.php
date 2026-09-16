<?php
/**
 * Migration 012 — Criação da tabela produto_alergenos (Pivot)
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `produto_alergenos` (
            `produto_id` INT UNSIGNED NOT NULL,
            `restricao_id` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`produto_id`, `restricao_id`),
            CONSTRAINT `fk_pa_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_pa_restricao` FOREIGN KEY (`restricao_id`) REFERENCES `restricoes_alimentares` (`id`) ON DELETE RESTRICT,
            INDEX `idx_pa_restricao` (`restricao_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `produto_alergenos`;");
    }
};
