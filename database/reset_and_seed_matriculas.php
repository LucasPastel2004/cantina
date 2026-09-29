<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Script de Limpeza Total e Carga Exclusiva dos Dados Reais da Escola
 * 
 * Remove todos os dados legados/falsos de alunos, turmas e responsáveis,
 * mantendo as tabelas do sistema intactas e rodando o seeder oficial.
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance();

echo "===============================================================\n";
echo " LIMPEZA COMPLETA DE DADOS FALSOS E CARGA OFICIAL DE MATRÍCULAS\n";
echo "===============================================================\n\n";

// 1. Desativar verificação de chaves estrangeiras temporariamente
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

echo "1. Limpando tabelas de alunos, responsáveis, turmas e vínculos...\n";
$tabelasParaLimpar = [
    'aluno_restricoes',
    'tickets',
    'itens_pedido',
    'pedidos',
    'transacoes_carteira',
    'alunos',
    'responsaveis',
    'turmas'
];

foreach ($tabelasParaLimpar as $tabela) {
    $pdo->exec("TRUNCATE TABLE `{$tabela}`;");
    echo "  - Tabela `{$tabela}` limpa com sucesso (TRUNCATE).\n";
}

// 2. Reativar verificação de chaves estrangeiras
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "Verificação de integridade referencial reativada (FOREIGN_KEY_CHECKS = 1).\n\n";

// 3. Executar o seeder oficial de matrículas reais
echo "2. Executando seeder oficial de matrículas reais (seed_matriculas_reais.php)...\n";
require __DIR__ . '/seed_matriculas_reais.php';

echo "\n3. Conferência final do banco de dados:\n";
$totalTurmas = (int)$pdo->query("SELECT COUNT(*) FROM turmas WHERE deleted_at IS NULL")->fetchColumn();
$totalResponsaveis = (int)$pdo->query("SELECT COUNT(*) FROM responsaveis")->fetchColumn();
$totalAlunos = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE deleted_at IS NULL")->fetchColumn();

echo "  - Total de Turmas Oficiais:      {$totalTurmas} (Esperado: 19)\n";
echo "  - Total de Responsáveis Reais:   {$totalResponsaveis} (Esperado: 195)\n";
echo "  - Total de Alunos Reais:         {$totalAlunos} (Esperado: 232)\n";

if ($totalTurmas === 19 && $totalResponsaveis === 195 && $totalAlunos === 232) {
    echo "\n>>> SUCESSO TOTAL: Todos os dados falsos foram eliminados e apenas os dados oficiais estão presentes! <<<\n";
} else {
    echo "\n>>> ATENÇÃO: Contagens divergentes após o seeder! <<<\n";
}
