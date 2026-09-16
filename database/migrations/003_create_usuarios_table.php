<?php
/**
 * Migration 003 — Criação da tabela usuarios
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `usuarios` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `usuario` VARCHAR(50) NOT NULL UNIQUE,
            `nome` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `senha_hash` VARCHAR(255) NOT NULL,
            `perfil` ENUM('operador', 'administrador') NOT NULL DEFAULT 'operador',
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_usuarios_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_usuarios_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_usuarios_perfil` (`perfil`),
            INDEX `idx_usuarios_ativo` (`ativo`),
            INDEX `idx_usuarios_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);

        // Insere o usuário administrador inicial para testes e primeiro acesso
        $count = $pdo->query("SELECT COUNT(*) FROM `usuarios` WHERE `usuario` = 'admin'")->fetchColumn();
        if ($count == 0) {
            $senhaHash = password_hash('Admin@123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `usuarios` (
                `usuario`, `nome`, `email`, `senha_hash`, `perfil`, `ativo`
            ) VALUES (?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                'admin',
                'Administrador Geral',
                'admin@colegiomonteirolobato.com.br',
                $senhaHash,
                'administrador',
                1
            ]);
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `usuarios`;");
    }
};
