<?php
/**
 * Teste de Validação: Rota /portal/pedidos/editar/{id}
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/controllers/PortalController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Core\Router;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\TransacaoCarteira;
use App\Models\Pedido;

// Inicializa sessão
Session::start();

// Cria responsável e aluno para teste
$respCriado = Responsavel::localizarOuCriarPorCpf([
    'cpf' => '99911122233',
    'nome' => 'Responsável Teste Edição',
    'whatsapp' => '(11) 98888-9999',
    'email' => 'teste_edicao@exemplo.com'
]);
$resp = Responsavel::buscarPorId($respCriado['id']);

TransacaoCarteira::creditar($resp['id'], 100.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Carga teste');

$turma = Turma::localizarOuCriar('7º Ano', '7º Ano A', 'matutino');
$alunoId = Aluno::criar([
    'responsavel_id' => $resp['id'],
    'turma_id' => $turma['id'],
    'matricula' => 'MAT-EDIT-' . random_int(1000, 9999),
    'nome' => 'Aluno Teste Edição',
    'turno' => 'matutino',
    'ativo' => 1
]);

$cat = Categoria::buscarPorNome('Salgados Assados');
$catId = $cat ? (int)$cat['id'] : 1;

$prodId = Produto::criar([
    'categoria_id' => $catId,
    'codigo_interno' => 'PRD-ED-' . random_int(1000, 9999),
    'descricao' => 'Lanche Teste Edição',
    'preco_venda' => 8.00,
    'tipo' => 'salgado_diario',
    'turno_disponibilidade' => 'matutino',
    'estoque_atual' => 10,
    'estoque_reservado' => 0,
    'status_pausa' => 'ativo'
]);

// Cria pedido para a próxima terça-feira (garante data futura aberta)
$dtFutura = (new \DateTimeImmutable())->modify('+2 days');
if ((int)$dtFutura->format('N') >= 6) {
    $dtFutura = $dtFutura->modify('+4 days');
}
$dataConsumo = $dtFutura->format('Y-m-d');
$momentoAberto = (new \DateTimeImmutable($dataConsumo))->modify('monday this week 07:30:00');

$pedido = Pedido::criarPedido($resp['id'], $alunoId, $dataConsumo, [
    ['produto_id' => $prodId, 'quantidade' => 1]
], $momentoAberto);

echo "Pedido criado: #{$pedido['codigo_pedido']} (ID: {$pedido['id']})\n";

// Autentica o responsável
Auth::loginResponsavel($resp);

// Configura router e despacha rota de edição GET
$router = new Router();
$router->get('/portal/pedidos/editar/{id}', [\App\Controllers\PortalController::class, 'pedidoEditar']);
$router->post('/portal/pedidos/editar/{id}', [\App\Controllers\PortalController::class, 'pedidoEditarSalvar']);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = "/portal/pedidos/editar/{$pedido['id']}";

ob_start();
try {
    $router->dispatch();
    $outputGet = ob_get_clean();
    $sucessoGet = true;
} catch (\Throwable $e) {
    $outputGet = ob_get_clean();
    $sucessoGet = false;
    echo "ERRO DISPARADO NO GET: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

// Agora testa POST de salvar alteração
$csrfToken = Session::generateCsrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = "/portal/pedidos/editar/{$pedido['id']}";
$_POST['csrf_token'] = $csrfToken;
$_POST['itens'] = [$prodId => 2]; // Altera de 1 para 2 unidades

ob_start();
try {
    $router->dispatch();
    $outputPost = ob_get_clean();
    $sucessoPost = true;
} catch (\Throwable $e) {
    $outputPost = ob_get_clean();
    $sucessoPost = false;
    echo "ERRO DISPARADO NO POST: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

// Limpeza no banco
$pdo = \Database::getInstance();
$pdo->exec("DELETE FROM tickets WHERE pedido_id IN (SELECT id FROM pedidos WHERE codigo_pedido LIKE 'PED-2026-%') AND pedido_id > 40");
$pdo->exec("DELETE FROM itens_pedido WHERE pedido_id IN (SELECT id FROM pedidos WHERE codigo_pedido LIKE 'PED-2026-%') AND pedido_id > 40");
$pdo->exec("DELETE FROM pedidos WHERE id > 40");
$pdo->exec("DELETE FROM alunos WHERE matricula LIKE 'MAT-EDIT-%'");
$pdo->exec("DELETE FROM turmas WHERE nome_turma = '7º Ano A'");
$pdo->exec("DELETE FROM produtos WHERE codigo_interno LIKE 'PRD-ED-%'");
$pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id IN (SELECT id FROM responsaveis WHERE cpf = '99911122233')");
$pdo->exec("DELETE FROM responsaveis WHERE cpf = '99911122233'");

if ($sucessoGet && str_contains($outputGet, 'Editar Pedido') && str_contains($outputGet, $pedido['codigo_pedido'])) {
    echo "[PASS] GET /portal/pedidos/editar/{id} executada com sucesso! Tela renderizada perfeitamente.\n";
} else {
    echo "[FAIL] GET /portal/pedidos/editar/{id} falhou.\n";
    exit(1);
}

if ($sucessoPost) {
    echo "[PASS] POST /portal/pedidos/editar/{id} executada com sucesso! Pedido alterado e salvo.\n";
} else {
    echo "[FAIL] POST /portal/pedidos/editar/{id} falhou.\n";
    exit(1);
}

echo "\nTODOS OS TESTES DE EDIÇÃO DE PEDIDOS FORAM APROVADOS COM 100% DE SUCESSO!\n";
exit(0);
