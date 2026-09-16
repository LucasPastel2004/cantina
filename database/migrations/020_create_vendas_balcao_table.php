<?php
/**
 * Migration 020 — Criação da tabela vendas_balcao
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `vendas_balcao` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `sessao_caixa_id` INT UNSIGNED NOT NULL,
            `operador_id` INT UNSIGNED NOT NULL,
            `tipo_venda` ENUM('avulso_aluno', 'desconto_folha') NOT NULL,
            `aluno_id` INT UNSIGNED NULL,
            `colaborador_id` INT UNSIGNED NULL,
            `forma_pagamento` ENUM('dinheiro', 'pix_balcao', 'desconto_folha') NOT NULL,
            `valor_total` DECIMAL(10,2) NOT NULL,
            `valor_dinheiro_recebido` DECIMAL(10,2) NULL,
            `troco_devolvido` DECIMAL(10,2) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_vb_caixa` FOREIGN KEY (`sessao_caixa_id`) REFERENCES `sessoes_caixa` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_vb_usuario` FOREIGN KEY (`operador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_vb_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_vb_colaborador` FOREIGN KEY (`colaborador_id`) REFERENCES `colaboradores` (`id`) ON DELETE RESTRICT,
            INDEX `idx_vb_colab_data` (`colaborador_id`, `created_at`),
            INDEX `idx_vb_data` (`created_at`),
            INDEX `idx_vb_caixa` (`sessao_caixa_id`),
            INDEX `idx_vb_aluno` (`aluno_id`),
            INDEX `idx_vb_operador` (`operador_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `vendas_balcao`;");
    }
};
