<?php
/**
 * Migration 014 — Criação da tabela itens_nota_fiscal_xml
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `itens_nota_fiscal_xml` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nota_fiscal_id` INT UNSIGNED NOT NULL,
            `codigo_produto_fornecedor` VARCHAR(50) NOT NULL,
            `descricao_fornecedor` VARCHAR(150) NOT NULL,
            `unidade_fornecedor` VARCHAR(10) NOT NULL,
            `quantidade_fornecedor` DECIMAL(10,2) NOT NULL,
            `valor_unitario_fornecedor` DECIMAL(10,2) NOT NULL,
            `produto_id` INT UNSIGNED NOT NULL,
            `fator_conversao` INT NOT NULL DEFAULT 1,
            `quantidade_convertida` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_inf_nfe` FOREIGN KEY (`nota_fiscal_id`) REFERENCES `notas_fiscais_xml` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_inf_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT,
            INDEX `idx_inf_nfe` (`nota_fiscal_id`),
            INDEX `idx_inf_prod` (`produto_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `itens_nota_fiscal_xml`;");
    }
};
