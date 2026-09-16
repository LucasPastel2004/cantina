<?php
/**
 * Migration 010 — Criação da tabela categorias
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `categorias` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome` VARCHAR(50) NOT NULL UNIQUE,
            `ordem_exibicao` INT NOT NULL DEFAULT 0,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_categorias_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_categorias_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_categorias_ordem` (`ordem_exibicao`),
            INDEX `idx_categorias_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);

        // Insere as 4 categorias oficiais de cardápio especificadas no FSD
        $count = $pdo->query("SELECT COUNT(*) FROM `categorias`")->fetchColumn();
        if ($count == 0) {
            $categorias = [
                ['Salgados Assados', 1],
                ['Sucos Naturais', 2],
                ['Industrializados', 3],
                ['Sobremesas', 4],
            ];

            $stmt = $pdo->prepare("INSERT INTO `categorias` (`nome`, `ordem_exibicao`, `ativo`) VALUES (?, ?, 1)");
            foreach ($categorias as $cat) {
                $stmt->execute([$cat[0], $cat[1]]);
            }
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `categorias`;");
    }
};
