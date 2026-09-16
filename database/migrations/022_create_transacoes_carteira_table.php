<?php
/**
 * Migration 022 — Criação da tabela transacoes_carteira
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `transacoes_carteira` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `responsavel_id` INT UNSIGNED NOT NULL,
            `aluno_id` INT UNSIGNED NULL,
            `operador_id` INT UNSIGNED NULL,
            `tipo_transacao` ENUM(
                'recarga_pix',
                'recarga_dinheiro',
                'debito_pedido',
                'estorno_cancelamento',
                'debito_contestacao_med',
                'estorno_balcao',
                'ajuste_manual'
            ) NOT NULL,
            `valor` DECIMAL(10,2) NOT NULL,
            `saldo_anterior` DECIMAL(10,2) NOT NULL,
            `saldo_resultante` DECIMAL(10,2) NOT NULL,
            `referencia_id` VARCHAR(50) NULL,
            `descricao` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_tc_responsavel` FOREIGN KEY (`responsavel_id`) REFERENCES `responsaveis` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_tc_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_tc_operador` FOREIGN KEY (`operador_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_tc_resp_data` (`responsavel_id`, `created_at`),
            INDEX `idx_tc_aluno` (`aluno_id`),
            INDEX `idx_tc_operador` (`operador_id`),
            INDEX `idx_tc_tipo` (`tipo_transacao`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `transacoes_carteira`;");
    }
};
