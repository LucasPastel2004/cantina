<?php
/**
 * Migration 028 — Adiciona campos de rastreamento de estorno nas tabelas vendas_balcao e pedidos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Fase 19 — Módulo Completo (CRUD) de Estorno de Vendas e Invalidação
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Tabela vendas_balcao
        $colunasVb = $pdo->query("SHOW COLUMNS FROM `vendas_balcao` LIKE 'status'")->fetchAll();
        if (empty($colunasVb)) {
            $pdo->exec("
                ALTER TABLE `vendas_balcao`
                ADD COLUMN `status` ENUM('concluida', 'estornada') NOT NULL DEFAULT 'concluida' AFTER `troco_devolvido`,
                ADD COLUMN `estornado_em` DATETIME NULL AFTER `status`,
                ADD COLUMN `estornado_por` INT UNSIGNED NULL AFTER `estornado_em`,
                ADD COLUMN `motivo_estorno` VARCHAR(255) NULL AFTER `estornado_por`,
                ADD CONSTRAINT `fk_vb_estornado_por` FOREIGN KEY (`estornado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
                ADD INDEX `idx_vb_status` (`status`);
            ");
        }

        // 2. Tabela pedidos
        // Altera ENUM para incluir 'estornado' se ainda não estiver presente
        $colunaStatus = $pdo->query("SHOW COLUMNS FROM `pedidos` LIKE 'status'")->fetch(\PDO::FETCH_ASSOC);
        if ($colunaStatus && !str_contains($colunaStatus['Type'], 'estornado')) {
            $pdo->exec("
                ALTER TABLE `pedidos`
                MODIFY COLUMN `status` ENUM('agendado', 'editado', 'cancelado', 'entregue', 'estornado') NOT NULL DEFAULT 'agendado';
            ");
        }

        $colunasPedidos = $pdo->query("SHOW COLUMNS FROM `pedidos` LIKE 'estornado_em'")->fetchAll();
        if (empty($colunasPedidos)) {
            $pdo->exec("
                ALTER TABLE `pedidos`
                ADD COLUMN `estornado_em` DATETIME NULL AFTER `status`,
                ADD COLUMN `estornado_por` INT UNSIGNED NULL AFTER `estornado_em`,
                ADD COLUMN `motivo_estorno` VARCHAR(255) NULL AFTER `estornado_por`,
                ADD CONSTRAINT `fk_pedidos_estornado_por` FOREIGN KEY (`estornado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
            ");
        }

        // 3. Tabela estoque_movimentacoes: adiciona 'entrada_estorno' ao ENUM de tipo_movimentacao
        $colunaMov = $pdo->query("SHOW COLUMNS FROM `estoque_movimentacoes` LIKE 'tipo_movimentacao'")->fetch(\PDO::FETCH_ASSOC);
        if ($colunaMov && !str_contains($colunaMov['Type'], 'entrada_estorno')) {
            $pdo->exec("
                ALTER TABLE `estoque_movimentacoes`
                MODIFY COLUMN `tipo_movimentacao` ENUM(
                    'entrada_fornecedor',
                    'entrada_xml',
                    'aproveitamento_sobra',
                    'saida_venda_avulsa',
                    'saida_encomenda',
                    'saida_perda',
                    'entrada_estorno'
                ) NOT NULL;
            ");
        }
    }

    public function down(\PDO $pdo): void
    {
        // Reverte vendas_balcao
        $colunasVb = $pdo->query("SHOW COLUMNS FROM `vendas_balcao` LIKE 'status'")->fetchAll();
        if (!empty($colunasVb)) {
            try {
                $pdo->exec("ALTER TABLE `vendas_balcao` DROP FOREIGN KEY `fk_vb_estornado_por`;");
            } catch (\Throwable $e) {}
            try {
                $pdo->exec("ALTER TABLE `vendas_balcao` DROP INDEX `idx_vb_status`;");
            } catch (\Throwable $e) {}
            $pdo->exec("
                ALTER TABLE `vendas_balcao`
                DROP COLUMN `status`,
                DROP COLUMN `estornado_em`,
                DROP COLUMN `estornado_por`,
                DROP COLUMN `motivo_estorno`;
            ");
        }

        // Reverte pedidos
        $colunasPedidos = $pdo->query("SHOW COLUMNS FROM `pedidos` LIKE 'estornado_em'")->fetchAll();
        if (!empty($colunasPedidos)) {
            try {
                $pdo->exec("ALTER TABLE `pedidos` DROP FOREIGN KEY `fk_pedidos_estornado_por`;");
            } catch (\Throwable $e) {}
            $pdo->exec("
                ALTER TABLE `pedidos`
                DROP COLUMN `estornado_em`,
                DROP COLUMN `estornado_por`,
                DROP COLUMN `motivo_estorno`;
            ");
            $pdo->exec("
                ALTER TABLE `pedidos`
                MODIFY COLUMN `status` ENUM('agendado', 'editado', 'cancelado', 'entregue') NOT NULL DEFAULT 'agendado';
            ");
        }
    }
};
