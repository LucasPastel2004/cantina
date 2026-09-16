<?php
/**
 * Migration 017 — Criação da tabela itens_pedido
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `itens_pedido` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `pedido_id` INT UNSIGNED NOT NULL,
            `produto_id` INT UNSIGNED NOT NULL,
            `quantidade` INT NOT NULL DEFAULT 1,
            `preco_unitario` DECIMAL(10,2) NOT NULL,
            `subtotal` DECIMAL(10,2) NOT NULL,
            CONSTRAINT `fk_itens_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_itens_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT,
            INDEX `idx_itens_pedido_pedido` (`pedido_id`),
            INDEX `idx_itens_pedido_produto` (`produto_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `itens_pedido`;");
    }
};
