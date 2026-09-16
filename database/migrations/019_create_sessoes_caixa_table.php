<?php
/**
 * Migration 019 — Criação da tabela sessoes_caixa
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `sessoes_caixa` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `operador_id` INT UNSIGNED NOT NULL,
            `turno` ENUM('matutino', 'vespertino') NOT NULL,
            `aberto_em` DATETIME NOT NULL,
            `fechado_em` DATETIME NULL,
            `fundo_troco_inicial` DECIMAL(10,2) NOT NULL,
            `total_dinheiro_recargas` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_dinheiro_avulso` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_pix_balcao` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `total_informado_gaveta` DECIMAL(10,2) NULL,
            `diferenca_quebra_sobra` DECIMAL(10,2) NULL,
            `status` ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
            `observacoes` TEXT NULL,
            CONSTRAINT `fk_caixa_usuario` FOREIGN KEY (`operador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            INDEX `idx_caixa_data_turno` (`aberto_em`, `turno`, `status`),
            INDEX `idx_caixa_operador` (`operador_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `sessoes_caixa`;");
    }
};
