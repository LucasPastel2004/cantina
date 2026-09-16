<?php
/**
 * Migration 002 — Criação da tabela configuracoes_globais
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `configuracoes_globais` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `razao_social` VARCHAR(150) NOT NULL,
            `nome_fantasia` VARCHAR(100) NOT NULL,
            `cnpj` VARCHAR(18) NOT NULL,
            `telefone_contato` VARCHAR(20) NOT NULL,
            `logo_path` VARCHAR(255) DEFAULT 'assets/img/logo-colegio.jpg',
            `recarga_minima_pix` DECIMAL(10,2) NOT NULL DEFAULT 10.00,
            `alerta_saldo_baixo` DECIMAL(10,2) NOT NULL DEFAULT 15.00,
            `timeout_sessao_minutos` INT NOT NULL DEFAULT 60,
            `limite_tentativas_login` INT NOT NULL DEFAULT 5,
            `itens_por_pagina` INT NOT NULL DEFAULT 25,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);

        // Insere os parâmetros padrão da instituição escolar se a tabela estiver vazia
        $count = $pdo->query("SELECT COUNT(*) FROM `configuracoes_globais`")->fetchColumn();
        if ($count == 0) {
            $stmt = $pdo->prepare("INSERT INTO `configuracoes_globais` (
                `razao_social`, `nome_fantasia`, `cnpj`, `telefone_contato`, `logo_path`,
                `recarga_minima_pix`, `alerta_saldo_baixo`, `timeout_sessao_minutos`,
                `limite_tentativas_login`, `itens_por_pagina`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                'Colégio Monteiro Lobato Ltda',
                'Cantina Web — Colégio Monteiro Lobato',
                '12.345.678/0001-90',
                '(11) 98765-4321',
                'assets/img/logo-colegio.jpg',
                10.00,
                15.00,
                60,
                5,
                25
            ]);
        }
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec("DROP TABLE IF EXISTS `configuracoes_globais`;");
    }
};
