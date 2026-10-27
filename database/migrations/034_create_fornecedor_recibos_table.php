<?php
/**
 * Migration 034 — Criação da tabela fornecedor_recibos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Módulo Financeiro & Gestão de Fornecedores: Emissão e Controle de Recibos de Pagamento
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fornecedor_recibos` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `numero_recibo` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Identificador único do recibo, ex: REC-20261027-0001',
            `fornecedor_id` INT UNSIGNED NOT NULL,
            `valor` DECIMAL(10,2) NOT NULL,
            `data_pagamento` DATE NOT NULL,
            `forma_pagamento` ENUM('pix', 'dinheiro') NOT NULL DEFAULT 'pix',
            `descricao` VARCHAR(200) NOT NULL COMMENT 'Descrição do fornecimento/serviço, ex: Pagamento Salgado, Pagamento Cupcake',
            `observacoes` TEXT NULL COMMENT 'Dados adicionais, chave Pix, número de NF ou anotações internas',
            `created_by` INT UNSIGNED NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_fornecedor_recibos_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_fornecedor_recibos_usuario` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_fornecedor_recibos_data` (`data_pagamento`),
            INDEX `idx_fornecedor_recibos_fornecedor` (`fornecedor_id`),
            INDEX `idx_fornecedor_recibos_forma` (`forma_pagamento`),
            INDEX `idx_fornecedor_recibos_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `fornecedor_recibos`;");
    }
};
