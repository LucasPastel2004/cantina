<?php
/**
 * Migration 026 — Criação da tabela logs_seguranca
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `logs_seguranca` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `evento` VARCHAR(50) NOT NULL,
            `cpf_ou_usuario` VARCHAR(50) NULL,
            `detalhes_json` TEXT NULL,
            `ip_origem` VARCHAR(45) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_logs_seg_evento` (`evento`),
            INDEX `idx_logs_seg_identificador` (`cpf_ou_usuario`),
            INDEX `idx_logs_seg_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `logs_seguranca`;");
    }
};
