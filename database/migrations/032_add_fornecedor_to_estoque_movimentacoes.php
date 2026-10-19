<?php
/**
 * Migration 032 — Adiciona coluna fornecedor na tabela estoque_movimentacoes
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Suporte a Entrada Manual de Mercadorias sem necessidade de XML NF-e
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $colunas = $pdo->query("SHOW COLUMNS FROM `estoque_movimentacoes` LIKE 'fornecedor'")->fetchAll();
        if (empty($colunas)) {
            $pdo->exec("
                ALTER TABLE `estoque_movimentacoes`
                ADD COLUMN `fornecedor` VARCHAR(150) NULL AFTER `motivo_perda`,
                ADD INDEX `idx_mov_fornecedor` (`fornecedor`);
            ");
        }
    }

    public function down(\PDO $pdo): void
    {
        $colunas = $pdo->query("SHOW COLUMNS FROM `estoque_movimentacoes` LIKE 'fornecedor'")->fetchAll();
        if (!empty($colunas)) {
            $pdo->exec("
                ALTER TABLE `estoque_movimentacoes`
                DROP INDEX `idx_mov_fornecedor`,
                DROP COLUMN `fornecedor`;
            ");
        }
    }
};
