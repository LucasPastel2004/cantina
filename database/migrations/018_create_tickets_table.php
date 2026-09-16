<?php
/**
 * Migration 018 — Criação da tabela tickets
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tickets` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `pedido_id` INT UNSIGNED NOT NULL,
            `item_pedido_id` INT UNSIGNED NOT NULL,
            `codigo_ticket` VARCHAR(25) NOT NULL UNIQUE,
            `numero_via` INT NOT NULL DEFAULT 1,
            `status` ENUM('ativo', 'impresso', 'estornado', 'cancelado') NOT NULL DEFAULT 'ativo',
            `tag_alergenos` VARCHAR(150) NULL,
            `impresso_em` DATETIME NULL,
            `estornado_em` DATETIME NULL,
            `motivo_estorno` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_tickets_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_tickets_item` FOREIGN KEY (`item_pedido_id`) REFERENCES `itens_pedido` (`id`) ON DELETE CASCADE,
            INDEX `idx_tickets_cod` (`codigo_ticket`),
            INDEX `idx_tickets_status` (`status`),
            INDEX `idx_tickets_pedido` (`pedido_id`),
            INDEX `idx_tickets_item` (`item_pedido_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `tickets`;");
    }
};
