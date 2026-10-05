<?php
/**
 * Migration 030 — Adiciona forma de pagamento 'saldo' em vendas_balcao e tipo 'debito_balcao' em transacoes_carteira
 * 
 * Cantina Web — Colégio Monteiro Lobato
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Atualiza ENUM de forma_pagamento em vendas_balcao
        $pdo->exec("
            ALTER TABLE `vendas_balcao`
            MODIFY COLUMN `forma_pagamento` ENUM('dinheiro', 'pix_balcao', 'saldo', 'desconto_folha') NOT NULL DEFAULT 'dinheiro',
            MODIFY COLUMN `tipo_venda` ENUM('avulso_aluno', 'avulso_colaborador', 'desconto_folha') NOT NULL DEFAULT 'avulso_aluno';
        ");

        // 2. Atualiza ENUM de tipo_transacao em transacoes_carteira
        $pdo->exec("
            ALTER TABLE `transacoes_carteira`
            MODIFY COLUMN `tipo_transacao` ENUM(
                'recarga_pix',
                'recarga_dinheiro',
                'debito_pedido',
                'debito_balcao',
                'estorno_cancelamento',
                'debito_contestacao_med',
                'estorno_balcao',
                'ajuste_manual'
            ) NOT NULL;
        ");
    }

    public function down(\PDO $pdo): void
    {
        // Reverte ENUMs
        $pdo->exec("
            ALTER TABLE `vendas_balcao`
            MODIFY COLUMN `forma_pagamento` ENUM('dinheiro', 'pix_balcao', 'desconto_folha') NOT NULL DEFAULT 'dinheiro',
            MODIFY COLUMN `tipo_venda` ENUM('avulso_aluno', 'desconto_folha') NOT NULL DEFAULT 'avulso_aluno';
        ");

        $pdo->exec("
            ALTER TABLE `transacoes_carteira`
            MODIFY COLUMN `tipo_transacao` ENUM(
                'recarga_pix',
                'recarga_dinheiro',
                'debito_pedido',
                'estorno_cancelamento',
                'debito_contestacao_med',
                'estorno_balcao',
                'ajuste_manual'
            ) NOT NULL;
        ");
    }
};
