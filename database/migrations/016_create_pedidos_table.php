<?php
/**
 * Migration 016 — Criação da tabela pedidos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `pedidos` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `codigo_pedido` VARCHAR(20) NOT NULL UNIQUE,
            `responsavel_id` INT UNSIGNED NOT NULL,
            `aluno_id` INT UNSIGNED NOT NULL,
            `data_consumo` DATE NOT NULL,
            `turno_consumo` ENUM('matutino', 'vespertino') NOT NULL,
            `valor_total` DECIMAL(10,2) NOT NULL,
            `status` ENUM('agendado', 'editado', 'cancelado', 'entregue') NOT NULL DEFAULT 'agendado',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_pedidos_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `responsaveis` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_pedidos_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE RESTRICT,
            INDEX `idx_pedidos_corte` (`data_consumo`, `turno_consumo`, `status`),
            INDEX `idx_pedidos_aluno` (`aluno_id`, `data_consumo`),
            INDEX `idx_pedidos_resp` (`responsavel_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `pedidos`;");
    }
};
