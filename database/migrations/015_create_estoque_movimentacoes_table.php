<?php
/**
 * Migration 015 — Criação da tabela estoque_movimentacoes
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `estoque_movimentacoes` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `produto_id` INT UNSIGNED NOT NULL,
            `tipo_movimentacao` ENUM(
                'entrada_fornecedor',
                'entrada_xml',
                'aproveitamento_sobra',
                'saida_venda_avulsa',
                'saida_encomenda',
                'saida_perda'
            ) NOT NULL,
            `quantidade` INT NOT NULL,
            `motivo_perda` ENUM(
                'avaria',
                'vencimento',
                'sobra_producao',
                'ajuste_contagem'
            ) NULL,
            `nota_fiscal_id` INT UNSIGNED NULL,
            `operador_id` INT UNSIGNED NOT NULL,
            `justificativa` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_mov_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_mov_nf` FOREIGN KEY (`nota_fiscal_id`) REFERENCES `notas_fiscais_xml` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`operador_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            INDEX `idx_mov_prod_tipo` (`produto_id`, `tipo_movimentacao`),
            INDEX `idx_mov_data` (`created_at`),
            INDEX `idx_mov_operador` (`operador_id`),
            INDEX `idx_mov_nf` (`nota_fiscal_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `estoque_movimentacoes`;");
    }
};
