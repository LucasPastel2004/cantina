<?php
/**
 * Migration 013 — Criação da tabela notas_fiscais_xml
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `notas_fiscais_xml` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `chave_nfe` VARCHAR(44) NOT NULL UNIQUE,
            `numero_nota` VARCHAR(20) NOT NULL,
            `cnpj_emitente` VARCHAR(18) NOT NULL,
            `razao_social_emitente` VARCHAR(150) NOT NULL,
            `data_emissao` DATETIME NOT NULL,
            `valor_total` DECIMAL(10,2) NOT NULL,
            `arquivo_xml_path` VARCHAR(255) NOT NULL,
            `importado_por` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_nfe_usuario` FOREIGN KEY (`importado_por`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT,
            INDEX `idx_nfe_chave` (`chave_nfe`),
            INDEX `idx_nfe_cnpj` (`cnpj_emitente`),
            INDEX `idx_nfe_data_emissao` (`data_emissao`),
            INDEX `idx_nfe_importado_por` (`importado_por`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `notas_fiscais_xml`;");
    }
};
