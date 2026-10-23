<?php
/**
 * Migration 033 — Criação da tabela fornecedores
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Módulo Administrativo: Cadastro e Gestão de Fornecedores Parceiros
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `fornecedores` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `nome_fantasia` VARCHAR(150) NOT NULL,
            `razao_social` VARCHAR(200) NULL,
            `cnpj_cpf` VARCHAR(20) NULL,
            `telefone` VARCHAR(30) NULL,
            `email` VARCHAR(100) NULL,
            `contato_responsavel` VARCHAR(100) NULL,
            `endereco` VARCHAR(255) NULL,
            `observacoes` TEXT NULL,
            `ativo` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `created_by` INT UNSIGNED NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            `updated_by` INT UNSIGNED NULL,
            `deleted_at` DATETIME NULL,
            CONSTRAINT `fk_fornecedores_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_fornecedores_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
            INDEX `idx_fornecedores_nome` (`nome_fantasia`),
            INDEX `idx_fornecedores_razao` (`razao_social`),
            INDEX `idx_fornecedores_cnpj` (`cnpj_cpf`),
            INDEX `idx_fornecedores_ativo` (`ativo`),
            INDEX `idx_fornecedores_deleted` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);

        // Carga inicial automática de fornecedores pré-existentes no histórico de estoque e NF-e
        try {
            $fornecedoresExistentes = [];

            // 1. Fornecedores de estoque_movimentacoes
            $colCheck = $pdo->query("SHOW COLUMNS FROM `estoque_movimentacoes` LIKE 'fornecedor'")->fetchAll();
            if (!empty($colCheck)) {
                $rows = $pdo->query("SELECT DISTINCT TRIM(fornecedor) AS f FROM `estoque_movimentacoes` WHERE `fornecedor` IS NOT NULL AND TRIM(`fornecedor`) != ''")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($rows as $r) {
                    $nome = trim((string)$r);
                    if ($nome !== '' && !in_array($nome, $fornecedoresExistentes, true)) {
                        $fornecedoresExistentes[] = $nome;
                    }
                }
            }

            // 2. Fornecedores de notas_fiscais_xml
            $tabelaXmlCheck = $pdo->query("SHOW TABLES LIKE 'notas_fiscais_xml'")->fetchAll();
            if (!empty($tabelaXmlCheck)) {
                $rowsXml = $pdo->query("SELECT DISTINCT TRIM(razao_social_emitente) AS f FROM `notas_fiscais_xml` WHERE `razao_social_emitente` IS NOT NULL AND TRIM(`razao_social_emitente`) != ''")->fetchAll(\PDO::FETCH_COLUMN);
                foreach ($rowsXml as $r) {
                    $nome = trim((string)$r);
                    if ($nome !== '' && !in_array($nome, $fornecedoresExistentes, true)) {
                        $fornecedoresExistentes[] = $nome;
                    }
                }
            }

            if (!empty($fornecedoresExistentes)) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO `fornecedores` (`nome_fantasia`, `razao_social`, `ativo`) 
                    VALUES (:nome, :razao, 1)
                ");

                foreach ($fornecedoresExistentes as $fn) {
                    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `fornecedores` WHERE `nome_fantasia` = :nome");
                    $stmtCheck->execute([':nome' => $fn]);
                    if ((int)$stmtCheck->fetchColumn() === 0) {
                        $stmtInsert->execute([
                            ':nome'  => $fn,
                            ':razao' => $fn
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Não interrompe a criação da tabela se não houver registros prévios
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `fornecedores`;");
    }
};
