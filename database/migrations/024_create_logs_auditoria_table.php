<?php
/**
 * Migration 024 — Criação da tabela logs_auditoria
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `logs_auditoria` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `operador_id` INT UNSIGNED NOT NULL,
            `acao` VARCHAR(50) NOT NULL,
            `tabela_afetada` VARCHAR(50) NOT NULL,
            `registro_id` INT UNSIGNED NOT NULL,
            `justificativa` VARCHAR(255) NOT NULL,
            `ip_origem` VARCHAR(45) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`operador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            INDEX `idx_audit_acao` (`acao`, `created_at`),
            INDEX `idx_audit_operador` (`operador_id`),
            INDEX `idx_audit_tabela_reg` (`tabela_afetada`, `registro_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `logs_auditoria`;");
    }
};
