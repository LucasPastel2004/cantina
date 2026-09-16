<?php
/**
 * Migration 004 — Criação da tabela responsaveis
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `responsaveis` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `cpf` VARCHAR(11) NOT NULL UNIQUE,
            `nome` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `whatsapp` VARCHAR(20) NOT NULL,
            `senha_hash` VARCHAR(255) NOT NULL,
            `saldo_atual` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `termos_aceitos_em` DATETIME NULL,
            `bloqueado_med` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            CONSTRAINT `fk_responsaveis_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_responsaveis_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_responsaveis_cpf` (`cpf`),
            INDEX `idx_responsaveis_bloqueado` (`bloqueado_med`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `responsaveis`;");
    }
};
