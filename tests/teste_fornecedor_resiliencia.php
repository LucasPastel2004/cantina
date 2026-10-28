<?php
/**
 * Teste Automatizado de Resiliência: Fornecedor::salvar() e Reconexão de Conexão
 * 
 * Valida:
 * 1. Fornecedor::salvar() com usuário inexistente (não quebra por FK fk_fornecedores_updated_by)
 * 2. Fornecedor::salvar() com usuário existente
 * 3. Fornecedor::excluir() com usuário inexistente
 * 4. Model::reconnect() e resiliência
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Sanitizer.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Fornecedor.php';

use App\Models\Fornecedor;
use App\Core\Model;

echo "========================================================================\n";
echo "   TESTE DE RESILIÊNCIA: FORNECEDOR E RECONEXÃO DE BANCO DE DADOS\n";
echo "========================================================================\n\n";

$passou = 0;
$erros  = 0;

function assertOk(bool $cond, string $msg, &$passou, &$erros) {
    if ($cond) {
        echo "  [OK] {$msg}\n";
        $passou++;
    } else {
        echo "  [FALHA] {$msg}\n";
        $erros++;
    }
}

// 1. Cadastra fornecedor novo com ID de usuário inexistente (ex: 999999)
$novoId = Fornecedor::salvar([
    'nome_fantasia'       => 'Fornecedor Resiliente Teste',
    'razao_social'        => 'Fornecedor Resiliente Ltda',
    'cnpj_cpf'            => '12.345.678/0001-99',
    'telefone'            => '(31) 98888-7777',
    'email'               => 'contato@resiliente.com.br',
    'contato_responsavel' => 'João Resiliente',
    'endereco'            => 'Rua das Flores, 100',
    'observacoes'         => 'Teste de resiliência',
    'ativo'               => 1
], 999999);

assertOk($novoId > 0, "Fornecedor cadastrado com ID de usuário inexistente (FK protegida com fallback seguro): ID #{$novoId}", $passou, $erros);

// 2. Atualiza o fornecedor com ID de usuário inexistente (ex: 888888)
$updId = Fornecedor::salvar([
    'id'                  => $novoId,
    'nome_fantasia'       => 'Fornecedor Resiliente Atualizado',
    'razao_social'        => 'Fornecedor Resiliente Ltda ME',
    'cnpj_cpf'            => '12.345.678/0001-99',
    'telefone'            => '(31) 98888-7777',
    'email'               => 'contato@resiliente.com.br',
    'contato_responsavel' => 'Maria Resiliente',
    'endereco'            => 'Rua das Flores, 100',
    'observacoes'         => 'Atualização com sucesso',
    'ativo'               => 1
], 888888);

assertOk($updId === $novoId, "Fornecedor atualizado com sucesso sem erro 2006 ou 1452: ID #{$updId}", $passou, $erros);

// 3. Verifica se os dados atualizados foram persistidos
$fornConsultado = Fornecedor::buscarPorId($novoId);
assertOk($fornConsultado['nome_fantasia'] === 'Fornecedor Resiliente Atualizado', "Nome fantasia atualizado conferido no banco", $passou, $erros);
assertOk($fornConsultado['contato_responsavel'] === 'Maria Resiliente', "Contato atualizado conferido no banco", $passou, $erros);

// 4. Soft delete com ID de usuário inexistente
$excluiu = Fornecedor::excluir($novoId, 777777);
assertOk($excluiu === true, "Soft delete executado com sucesso mesmo com ID de usuário inexistente", $passou, $erros);

// 5. Teste de reconexão do Model e Database
$pdo1 = Model::getConnection();
$pdo2 = Model::reconnect();
assertOk($pdo2 instanceof \PDO, "Model::reconnect() reestabelece conexão PDO com sucesso", $passou, $erros);

echo "\n------------------------------------------------------------------------\n";
echo "Resumo: {$passou} aprovados, {$erros} falhas.\n";
echo "========================================================================\n";

if ($erros > 0) {
    exit(1);
}
