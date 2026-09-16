<?php
/**
 * Migration 021 — Criação da tabela itens_venda_balcao
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `itens_venda_balcao` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `venda_balcao_id` INT UNSIGNED NOT NULL,
            `produto_id` INT UNSIGNED NOT NULL,
            `quantidade` INT NOT NULL,
            `preco_unitario` DECIMAL(10,2) NOT NULL,
            `subtotal` DECIMAL(10,2) NOT NULL,
            CONSTRAINT `fk_ivb_venda` FOREIGN KEY (`venda_balcao_id`) REFERENCES `vendas_balcao` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_ivb_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT,
            INDEX `idx_ivb_venda` (`venda_balcao_id`),
            INDEX `idx_ivb_produto` (`produto_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `itens_venda_balcao`;");
    }
};
