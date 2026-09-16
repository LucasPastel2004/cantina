<?php
/**
 * Migration 023 — Criação da tabela calendario_letivo
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `calendario_letivo` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `data` DATE NOT NULL UNIQUE,
            `tipo` ENUM('letivo', 'feriado', 'recesso', 'ferias', 'cancelado_emergencial') NOT NULL,
            `descricao` VARCHAR(100) NOT NULL,
            `cancelado_em` DATETIME NULL,
            `cancelado_por` INT UNSIGNED NULL,
            CONSTRAINT `fk_cal_usuario` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_cal_data_tipo` (`data`, `tipo`),
            INDEX `idx_cal_cancelado_por` (`cancelado_por`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `calendario_letivo`;");
    }
};
