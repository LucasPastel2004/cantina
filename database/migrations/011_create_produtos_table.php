<?php
/**
 * Migration 011 — Criação da tabela produtos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `produtos` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `categoria_id` INT UNSIGNED NOT NULL,
            `codigo_interno` VARCHAR(30) NOT NULL UNIQUE,
            `descricao` VARCHAR(100) NOT NULL,
            `preco_venda` DECIMAL(10,2) NOT NULL,
            `tipo` ENUM('salgado_diario', 'industrializado_continuo') NOT NULL,
            `turno_disponibilidade` ENUM('matutino', 'vespertino', 'ambos') NOT NULL DEFAULT 'ambos',
            `foto_url` VARCHAR(255) NULL,
            `estoque_atual` INT NOT NULL DEFAULT 0,
            `estoque_reservado` INT NOT NULL DEFAULT 0,
            `estoque_minimo` INT NOT NULL DEFAULT 5,
            `status_pausa` ENUM('ativo', 'pausado') NOT NULL DEFAULT 'ativo',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_produtos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_produtos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_produtos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_produtos_cat` (`categoria_id`),
            INDEX `idx_produtos_tipo` (`tipo`),
            INDEX `idx_produtos_status` (`status_pausa`, `turno_disponibilidade`),
            INDEX `idx_produtos_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `produtos`;");
    }
};
