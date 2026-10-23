<?php
/**
 * Suíte de Testes Automatizados: CRUD de Fornecedores e Integração com Estoque
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Fornecedor.php';
require_once __DIR__ . '/../app/models/Estoque.php';

use App\Models\Fornecedor;
use App\Models\Estoque;

$pdo = Database::getInstance();
$erros = [];

echo "======================================================\n";
echo "   Bateria de Testes Automatizados — CRUD Fornecedores\n";
echo "======================================================\n\n";

// 1. Testando Existência da Tabela
$tabCheck = $pdo->query("SHOW TABLES LIKE 'fornecedores'")->fetchAll();
if (empty($tabCheck)) {
    $erros[] = "Tabela 'fornecedores' não encontrada no banco de dados.";
} else {
    echo "[PASS] Tabela 'fornecedores' verificada com sucesso no MySQL.\n";
}

// 2. Testando Criação de Novo Fornecedor via Model
$adminUser = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$userId = $adminUser ? (int)$adminUser['id'] : null;

$nomeTeste = 'Distribuidora Teste QA ' . time();
$cnpjTeste = '12.345.678/0001-99';
$idCriado = Fornecedor::salvar([
    'nome_fantasia'       => $nomeTeste,
    'razao_social'        => $nomeTeste . ' Alimentos EIRELI',
    'cnpj_cpf'            => $cnpjTeste,
    'telefone'            => '(11) 98765-4321',
    'email'               => 'contato@distribuidorateste.com.br',
    'contato_responsavel' => 'Carlos Vendedor',
    'endereco'            => 'Av. Brasil, 1500 - Galpão 3',
    'observacoes'         => 'Entrega toda terça e quinta pela manhã',
    'ativo'               => 1
], $userId);

if ($idCriado <= 0) {
    $erros[] = "Falha ao criar fornecedor de teste via Fornecedor::salvar().";
} else {
    echo "[PASS] Fornecedor de teste inserido com ID #{$idCriado}.\n";
}

// 3. Testando Busca por ID
$fornRecuperado = Fornecedor::buscarPorId($idCriado);
if (!$fornRecuperado || $fornRecuperado['nome_fantasia'] !== $nomeTeste) {
    $erros[] = "Fornecedor::buscarPorId() não retornou o registro correto.";
} else {
    echo "[PASS] Fornecedor::buscarPorId() localizou os dados com fidelidade.\n";
}

// 4. Testando Busca com Filtro
$listaBusca = Fornecedor::listarTodos(false, 'Distribuidora Teste QA');
if (empty($listaBusca)) {
    $erros[] = "Fornecedor::listarTodos() com busca não localizou o fornecedor recém-criado.";
} else {
    echo "[PASS] Fornecedor::listarTodos() filtrou com sucesso por termo textual.\n";
}

// 5. Testando Atualização (Edição)
$nomeAtualizado = $nomeTeste . ' [Atualizado]';
Fornecedor::salvar([
    'id'                  => $idCriado,
    'nome_fantasia'       => $nomeAtualizado,
    'razao_social'        => $nomeAtualizado . ' Alimentos S/A',
    'cnpj_cpf'            => $cnpjTeste,
    'telefone'            => '(11) 99999-8888',
    'email'               => 'financeiro@distribuidorateste.com.br',
    'contato_responsavel' => 'Mariana Gerente',
    'endereco'            => 'Rua Nova, 50',
    'observacoes'         => 'Alterado prazo de pagamento para 30 dias',
    'ativo'               => 1
], $userId);

$fornAtualizado = Fornecedor::buscarPorId($idCriado);
if (!$fornAtualizado || $fornAtualizado['nome_fantasia'] !== $nomeAtualizado || $fornAtualizado['contato_responsavel'] !== 'Mariana Gerente') {
    $erros[] = "Fornecedor::salvar() com ID existente não atualizou os dados corretamente.";
} else {
    echo "[PASS] Fornecedor::salvar() atualizou os campos cadastrais com sucesso.\n";
}

// 6. Testando Integração com Estoque::listarFornecedoresSugestao()
$sugestoes = Estoque::listarFornecedoresSugestao();
if (!in_array($nomeAtualizado, $sugestoes, true)) {
    $erros[] = "Estoque::listarFornecedoresSugestao() não incluiu o novo fornecedor ativo cadastrado.";
} else {
    echo "[PASS] Estoque::listarFornecedoresSugestao() integrou perfeitamente o fornecedor cadastrado no autocomplete.\n";
}

// 7. Testando Soft Delete (Exclusão Segura)
$excluido = Fornecedor::excluir($idCriado, $userId);
if (!$excluido) {
    $erros[] = "Fornecedor::excluir() retornou false.";
}

$fornPosExclusao = Fornecedor::buscarPorId($idCriado);
if ($fornPosExclusao !== null) {
    $erros[] = "Fornecedor::buscarPorId() retornou registro após soft delete (deveria retornar null).";
} else {
    echo "[PASS] Fornecedor::excluir() aplicou soft delete: registro oculto nas buscas regulares.\n";
}

// Verifica integridade física no banco (registro físico preservado com deleted_at preenchido)
$checkDb = $pdo->query("SELECT id, deleted_at FROM fornecedores WHERE id = {$idCriado}")->fetch(PDO::FETCH_ASSOC);
if (!$checkDb || empty($checkDb['deleted_at'])) {
    $erros[] = "Registro físico do fornecedor não possui deleted_at preenchido no MySQL.";
} else {
    echo "[PASS] Auditoria e integridade preservadas: deleted_at gravado em {$checkDb['deleted_at']}.\n";
}

// 8. Testando Renderização da View (Padrão de Ouro: Carga Sob Demanda)
$baseUrl = '';
$csrf_field = '<input type="hidden" name="csrf_token" value="abc">';
$csrf_token = 'abc';
$totalAtivos = 1;
$totalGeral = 1;

// 8.1. Testando Estado Inicial (Lista Vazia Sem Filtro)
$fornecedores = [];
$busca = '';
$status = '';
$jaFiltrado = false;
$totalRegistros = 0;
$totalPaginas = 1;
$paginaAtual = 1;

ob_start();
include __DIR__ . '/../app/views/admin/fornecedores.php';
$htmlInicial = ob_get_clean();

if (strpos($htmlInicial, 'Não há nada para mostrar aqui') === false) {
    $erros[] = "Estado inicial não exibiu a mensagem 'Não há nada para mostrar aqui'.";
} else {
    echo "[PASS] Estado inicial exibe lista vazia com mensagem orientativa padronizada.\n";
}

if (strpos($htmlInicial, 'title="Limpar Filtros e Ocultar Lista"') !== false) {
    $erros[] = "Botão 'Limpar' apareceu no estado inicial (só deve aparecer com lista ativa).";
} else {
    echo "[PASS] Botão 'Limpar' permanece oculto no estado inicial sem lista ativa.\n";
}

// 8.2. Testando Estado Filtrado (Lista Ativa com Botão Limpar)
$fornecedores = [
    [
        'id'                  => 1,
        'nome_fantasia'       => 'Fornecedor Demonstração',
        'razao_social'        => 'Fornecedor Demo Ltda',
        'cnpj_cpf'            => '11.222.333/0001-44',
        'telefone'            => '(11) 4002-8922',
        'email'               => 'contato@demo.com',
        'contato_responsavel' => 'João Representante',
        'ativo'               => 1
    ]
];
$jaFiltrado = true;
$totalRegistros = 1;

ob_start();
include __DIR__ . '/../app/views/admin/fornecedores.php';
$htmlFiltrado = ob_get_clean();

if (strpos($htmlFiltrado, 'Fornecedor Demonstração') === false) {
    $erros[] = "Estado filtrado não exibiu a tabela com o fornecedor.";
} else {
    echo "[PASS] Estado filtrado renderizou os dados do fornecedor na tabela.\n";
}

if (strpos($htmlFiltrado, 'Limpar') === false || strpos($htmlFiltrado, 'title="Limpar Filtros e Ocultar Lista"') === false) {
    $erros[] = "Botão 'Limpar' não apareceu com a lista ativa na tela.";
} else {
    echo "[PASS] Botão 'Limpar' visível quando há lista ativa na tela.\n";
}

// 9. Testando se o item Fornecedores está no Sidebar
$activeMenu = 'fornecedores';
$isAdmin = true;
$user = ['nome' => 'Admin Teste', 'perfil' => 'administrador', 'role' => 'administrador'];
ob_start();
include __DIR__ . '/../app/views/layouts/sidebar.php';
$htmlSidebar = ob_get_clean();

if (strpos($htmlSidebar, '/admin/fornecedores') === false) {
    $erros[] = "Link /admin/fornecedores não encontrado no menu do Sidebar.";
}
if (strpos($htmlSidebar, 'Fornecedores') === false) {
    $erros[] = "Rótulo 'Fornecedores' não encontrado no Sidebar.";
}

if (!empty($erros)) {
    echo "\nFALHAS ENCONTRADAS:\n";
    foreach ($erros as $e) {
        echo " - [FAIL] $e\n";
    }
    exit(1);
}

echo "\n------------------------------------------------------\n";
echo "STATUS: TODOS OS 9 TESTES DO CRUD DE FORNECEDORES PASSARAM COM 100% DE SUCESSO!\n";
echo "------------------------------------------------------\n";
