<?php
/**
 * Migration 027 — Adiciona campo fracionado_salgado_grande na tabela produtos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Permite identificar salgados pequenos que são vendidos dividindo um salgado grande ao meio.
 * No corte do turno para o fornecedor: 2 salgados pequenos = 1 salgado grande (ex: 15 pedidos = 7 grandes e 1 pequeno).
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        $colunas = $pdo->query("SHOW COLUMNS FROM `produtos` LIKE 'fracionado_salgado_grande'")->fetchAll();
        if (empty($colunas)) {
            $sql = "ALTER TABLE `produtos` 
                    ADD COLUMN `fracionado_salgado_grande` TINYINT(1) NOT NULL DEFAULT 0 
                    AFTER `tipo`;";
            $pdo->exec($sql);
        }
    }

    public function down(\PDO $pdo): void
    {
        $colunas = $pdo->query("SHOW COLUMNS FROM `produtos` LIKE 'fracionado_salgado_grande'")->fetchAll();
        if (!empty($colunas)) {
            $sql = "ALTER TABLE `produtos` 
                    DROP COLUMN `fracionado_salgado_grande`;";
            $pdo->exec($sql);
        }
    }
};
