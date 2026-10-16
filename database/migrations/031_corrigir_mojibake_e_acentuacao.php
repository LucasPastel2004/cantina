<?php
/**
 * Migration 031 — Correção Universal de Mojibake e Normalização UTF-8
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * 
 * Finalidade:
 * Corrige permanentemente registros corrompidos por interpretação indevida de saída UTF-8
 * como página de código OEM CP850 do Windows (ex: '├║' -> 'ú', '├¡' -> 'í', '┬║' -> 'º')
 * e dupla codificação Latin1 em todas as tabelas mestres e transacionais.
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Correção canônica mandatória nas tabelas mestres de cardápio e segurança alimentar
        $pdo->exec("
            UPDATE `restricoes_alimentares` 
            SET `nome` = 'Glúten', 
                `descricao` = 'Presente em derivados de trigo, centeio, cevada e aveia contaminada.' 
            WHERE `id` = 1 OR `nome` LIKE '%ten%' OR `nome` LIKE '%Gl%';
        ");

        $pdo->exec("
            UPDATE `categorias` 
            SET `nome` = 'Lanches & Sanduíches' 
            WHERE `id` = 3 OR `nome` LIKE '%Sandu%' OR `nome` LIKE '%Lanches%';
        ");

        // 2. Mapeamento exato de substituição de sequências CP850 e Latin1
        $substituicoes = [
            // CP850 (PowerShell/CMD dump Windows)
            '├║' => 'ú',
            '├¡' => 'í',
            '├ú' => 'ã',
            '├®' => 'é',
            '├ó' => 'â',
            '├¬' => 'ê',
            '├º' => 'ç',
            '├│' => 'ó',
            '├┤' => 'ô',
            '├á' => 'à',
            '├í' => 'á',
            '┬║' => 'º',
            '┬¬' => 'ª',
            '├ü' => 'Á',
            '├Ç' => 'À',
            '├â' => 'Ã',
            '├é' => 'Â',
            '├ë' => 'É',
            '├è' => 'Ê',
            '├ì' => 'Í',
            '├ô' => 'Ó',
            '├ö' => 'Ô',
            '├ò' => 'Õ',
            '├Ü' => 'Ú',
            '├ç' => 'Ç',
            '├Á' => 'õ',

            // Latin1 / Windows-1252 (dupla codificação UTF-8)
            'Ãº' => 'ú',
            'Ã­' => 'í',
            'Ã£' => 'ã',
            'Ã©' => 'é',
            'Ã¢' => 'â',
            'Ãª' => 'ê',
            'Ã§' => 'ç',
            'Ã³' => 'ó',
            'Ã´' => 'ô',
            'Ã ' => 'à',
            'Ã¡' => 'á',
            'Âº' => 'º',
            'Âª' => 'ª',
            'Ã ' => 'Á',
            'Ã€' => 'À',
            'Ãƒ' => 'Ã',
            'Ã‚' => 'Â',
            'Ã‰' => 'É',
            'ÃŠ' => 'Ê',
            'Ã ' => 'Í',
            'Ã“' => 'Ó',
            'Ã”' => 'Ô',
            'Ã•' => 'Õ',
            'Ãš' => 'Ú',
            'Ã‡' => 'Ç',
            'Ãµ' => 'õ'
        ];

        // Tabelas e colunas textuais para varredura e sanitização
        $alvos = [
            'restricoes_alimentares' => ['nome', 'descricao'],
            'categorias'             => ['nome'],
            'produtos'               => ['descricao'],
            'turmas'                 => ['serie', 'nome_turma'],
            'alunos'                 => ['nome'],
            'responsaveis'           => ['nome'],
            'colaboradores'          => ['nome', 'cargo'],
            'tickets'                => ['tag_alergenos', 'motivo_estorno'],
            'configuracoes_globais'  => ['razao_social', 'nome_fantasia']
        ];

        foreach ($alvos as $tabela => $colunas) {
            // Verifica se a tabela existe antes de executar
            $existe = $pdo->query("SHOW TABLES LIKE '{$tabela}'")->fetchColumn();
            if (!$existe) {
                continue;
            }

            foreach ($colunas as $col) {
                foreach ($substituicoes as $de => $para) {
                    $stmt = $pdo->prepare("UPDATE `{$tabela}` SET `{$col}` = REPLACE(`{$col}`, ?, ?) WHERE `{$col}` LIKE ?");
                    $stmt->execute([$de, $para, "%{$de}%"]);
                }
            }
        }
    }

    public function down(\PDO $pdo): void
    {
        // Migração idempotente de correção de dados não necessita de rollback destrutivo
    }
};
