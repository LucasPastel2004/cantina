<?php
/**
 * Migration 029 — Criação da tabela despesas_operacionais e suporte a preço de custo em produtos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/alterações sistema guideline/Estrutura de uma DRE.pdf e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Tabela despesas_operacionais
        $sql = "CREATE TABLE IF NOT EXISTS `despesas_operacionais` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `categoria` ENUM('administrativa', 'pessoal', 'vendas', 'financeira', 'manutencao', 'outras') NOT NULL DEFAULT 'administrativa',
            `subcategoria` VARCHAR(60) NULL,
            `descricao` VARCHAR(150) NOT NULL,
            `valor` DECIMAL(10,2) NOT NULL,
            `competencia_mes_ano` VARCHAR(7) NOT NULL COMMENT 'Formato YYYY-MM (ex: 2026-09)',
            `data_vencimento` DATE NOT NULL,
            `data_pagamento` DATE NULL,
            `status` ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente',
            `forma_pagamento` VARCHAR(50) NULL COMMENT 'Pix, Transferência, Boleto, Dinheiro, Débito em Conta',
            `documento_referencia` VARCHAR(100) NULL COMMENT 'Nº NF, boleto, recibo ou comprovante',
            `observacoes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_despesas_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_despesas_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_despesas_competencia` (`competencia_mes_ano`),
            INDEX `idx_despesas_categoria` (`categoria`),
            INDEX `idx_despesas_status` (`status`),
            INDEX `idx_despesas_vencimento` (`data_vencimento`),
            INDEX `idx_despesas_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);

        // 2. Adiciona coluna preco_custo em produtos se ainda não existir
        $colunasProd = $pdo->query("SHOW COLUMNS FROM `produtos` LIKE 'preco_custo'")->fetchAll();
        if (empty($colunasProd)) {
            $pdo->exec("
                ALTER TABLE `produtos`
                ADD COLUMN `preco_custo` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `preco_venda`;
            ");
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `despesas_operacionais`;");

        $colunasProd = $pdo->query("SHOW COLUMNS FROM `produtos` LIKE 'preco_custo'")->fetchAll();
        if (!empty($colunasProd)) {
            $pdo->exec("ALTER TABLE `produtos` DROP COLUMN `preco_custo`;");
        }
    }
};
