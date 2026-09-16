<?php
/**
 * Migration 025 — Criação da tabela logs_erros
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `logs_erros` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nivel` ENUM('error', 'critical') NOT NULL DEFAULT 'error',
            `mensagem` TEXT NOT NULL,
            `arquivo` VARCHAR(255) NOT NULL,
            `linha` INT NOT NULL,
            `stack_trace` MEDIUMTEXT NULL,
            `url_requisicao` VARCHAR(255) NULL,
            `usuario_id` VARCHAR(50) NULL,
            `ip_origem` VARCHAR(45) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_logs_erros_nivel` (`nivel`),
            INDEX `idx_logs_erros_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `logs_erros`;");
    }
};
