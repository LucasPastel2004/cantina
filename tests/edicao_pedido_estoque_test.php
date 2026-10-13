<?php
/**
 * Teste Automatizado de Regressão:
 * Validação de Controle Rigoroso de Estoque na Edição de Pedidos
 * 
 * Verifica que ao editar um pedido:
 * 1. O sistema não permite selecionar quantidades acima do estoque disponível
 * 2. O input numérico recebe o atributo max correspondente ao estoque real
 * 3. O botão de aumentar (+) é desabilitado ao atingir o limite
 * 4. Produtos esgotados (estoque 0) são exibidos com badge Esgotado e bloqueados
 * 5. O backend recusa tentativas de exceder o estoque com DomainException
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

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
use App\Models\CalendarioEscolar;
use App\Models\Pedido;

Session::start();

echo "====================================================================\n";
echo " TESTE DE CONTROLE DE ESTOQUE NA EDIÇÃO DE PEDIDOS (PORTAL FAMÍLIA) \n";
echo "====================================================================\n\n";

// 1. Cria Responsável e Aluno
$sufixo = random_int(100000, 999999);
$cpf = '888' . sprintf('%06d', $sufixo) . '22';
$respCriado = Responsavel::localizarOuCriarPorCpf([
    'cpf' => $cpf,
    'nome' => 'Responsável Teste Estoque Edição',
    'whatsapp' => '(11) 97777-6666',
    'email' => 'teste_estoque@exemplo.com'
]);
$resp = Responsavel::buscarPorId($respCriado['id']);

// Garante saldo suficiente na carteira
TransacaoCarteira::creditar($resp['id'], 500.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Carga de teste');

$turma = Turma::localizarOuCriar('8º Ano', '8º Ano B', 'matutino');
$alunoId = Aluno::criar([
    'responsavel_id' => $resp['id'],
    'turma_id' => $turma['id'],
    'matricula' => 'MAT-ESTQ-' . random_int(1000, 9999),
    'nome' => 'Aluno Teste Estoque',
    'turno' => 'matutino',
    'ativo' => 1
]);

$cat = Categoria::buscarPorNome('Industrializados');
$catId = $cat ? (int)$cat['id'] : 1;

// 2. Cria Produto A (Água Mineral) com exatamente 2 unidades em estoque
$prodAguaId = Produto::criar([
    'categoria_id' => $catId,
    'codigo_interno' => 'AGU-TEST-' . random_int(100, 999),
    'descricao' => 'Água Mineral Teste 500ml',
    'preco_venda' => 3.50,
    'tipo' => 'industrializado_continuo',
    'turno_disponibilidade' => 'ambos',
    'estoque_atual' => 2,
    'estoque_reservado' => 0,
    'status_pausa' => 'ativo'
]);

// Cria Produto B (Suco) com exatamente 0 unidades em estoque (Esgotado)
$prodSucoId = Produto::criar([
    'categoria_id' => $catId,
    'codigo_interno' => 'SUC-TEST-' . random_int(100, 999),
    'descricao' => 'Suco Teste Zero Estoque',
    'preco_venda' => 6.00,
    'tipo' => 'industrializado_continuo',
    'turno_disponibilidade' => 'ambos',
    'estoque_atual' => 0,
    'estoque_reservado' => 0,
    'status_pausa' => 'ativo'
]);

// 3. Localiza próxima data letiva válida
$dataConsumo = null;
for ($i = 1; $i <= 30; $i++) {
    $cand = (new \DateTimeImmutable())->modify("+{$i} days");
    $candStr = $cand->format('Y-m-d');
    $diaSemana = (int)$cand->format('N');
    if ($diaSemana <= 5 && CalendarioEscolar::isDiaLetivo($candStr)) {
        $dataConsumo = $candStr;
        break;
    }
}

if (!$dataConsumo) {
    die("[ERRO] Nenhuma data letiva encontrada nos próximos 30 dias.\n");
}

$momentoAberto = (new \DateTimeImmutable($dataConsumo))->modify('monday this week 07:30:00');

// Cria pedido com as 2 águas minerais (esgotando o estoque livre)
$pedido = Pedido::criarPedido($resp['id'], $alunoId, $dataConsumo, [
    ['produto_id' => $prodAguaId, 'quantidade' => 2]
], $momentoAberto);

echo "1. Pedido Criado com 2 unidades de Água Mineral #{$pedido['codigo_pedido']}\n";

// Verifica se o estoque foi reservado
$prodAguaDb = Produto::buscarPorId($prodAguaId);
assert((int)$prodAguaDb['estoque_atual'] === 2, "Estoque atual deve ser 2");
assert((int)$prodAguaDb['estoque_reservado'] === 2, "Estoque reservado deve ser 2");
echo "  [PASS] Estoque físico = 2, Estoque reservado = 2 (Estoque livre = 0)\n\n";

// 4. Testa renderização da tela de edição (GET /portal/pedidos/editar/{id})
Auth::loginResponsavel($resp);

$router = new Router();
$router->get('/portal/pedidos/editar/{id}', [\App\Controllers\PortalController::class, 'pedidoEditar']);
$router->post('/portal/pedidos/editar/{id}', [\App\Controllers\PortalController::class, 'pedidoEditarSalvar']);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = "/portal/pedidos/editar/{$pedido['id']}";

ob_start();
$router->dispatch('GET', '/portal/pedidos/editar/' . $pedido['id']);
$html = ob_get_clean();

// 5. Validações na View de Edição:
echo "2. Validando Renderização HTML da Tela de Edição:\n";

// O produto água mineral já está no pedido com 2 unidades
assert(strpos($html, 'value="2"') !== false, "A água mineral deve ter value=\"2\" no input");
echo "  [PASS] Água mineral possui quantidade atual = 2 no pedido\n";

// O input da água mineral deve ter max="2" (pois o estoque livre é 0, mas o pedido tem 2)
assert(strpos($html, 'id="qtd-' . $prodAguaId . '"') !== false, "Input da água deve estar presente");
assert(preg_match('/id="qtd-' . $prodAguaId . '"[^>]*max="2"/', $html) === 1, "Atributo max do input da água mineral deve ser exatamente 2");
echo "  [PASS] Atributo max=\"2\" gerado corretamente para a água mineral\n";

// Como quantidade atual é 2 e max é 2, o botão + deve vir desabilitado
assert(preg_match('/data-id="' . $prodAguaId . '"[^>]*disabled/', $html) === 1, "Botão + da água mineral deve vir desabilitado");
echo "  [PASS] Botão de aumentar (+) vem desabilitado no HTML por atingir o limite de estoque\n";

// O produto Suco (estoque 0 e não está no pedido) deve aparecer como Esgotado
assert(strpos($html, 'id="product-box-' . $prodSucoId . '"') !== false, "Card do suco deve estar presente");
assert(strpos($html, 'Esgotado') !== false, "Badge Esgotado deve estar presente no HTML");
assert(strpos($html, 'opacity: 0.55; cursor: not-allowed;') !== false, "Card com estoque zero deve ter estilo de esgotado");
echo "  [PASS] Produto com estoque 0 aparece devidamente como 'Esgotado' e desabilitado\n\n";

// 6. Validação no Backend ao tentar burlar e pedir 146 águas minerais (POST):
echo "3. Validando Bloqueio de Estoque no Backend ao Salvar Edição:\n";

$excedeuEstoque = false;
try {
    Pedido::editarPedido($pedido['id'], $resp['id'], [
        ['produto_id' => $prodAguaId, 'quantidade' => 146]
    ], $momentoAberto);
} catch (\DomainException $e) {
    $excedeuEstoque = true;
    echo "  [PASS] Backend bloqueou pedido com 146 unidades: \"" . $e->getMessage() . "\"\n";
}
assert($excedeuEstoque, "Backend deveria ter bloqueado pedido que excede estoque");

// Tentativa de pedir 3 águas (quando o estoque total é 2):
$tentou3Aguas = false;
try {
    Pedido::editarPedido($pedido['id'], $resp['id'], [
        ['produto_id' => $prodAguaId, 'quantidade' => 3]
    ], $momentoAberto);
} catch (\DomainException $e) {
    $tentou3Aguas = true;
    echo "  [PASS] Backend bloqueou pedido com 3 unidades: \"" . $e->getMessage() . "\"\n";
}
assert($tentou3Aguas, "Backend deveria ter bloqueado pedido de 3 águas");

// Tentativa de adicionar o produto com estoque 0:
$tentouProdutoZerado = false;
try {
    Pedido::editarPedido($pedido['id'], $resp['id'], [
        ['produto_id' => $prodSucoId, 'quantidade' => 1]
    ], $momentoAberto);
} catch (\DomainException $e) {
    $tentouProdutoZerado = true;
    echo "  [PASS] Backend bloqueou adição de produto esgotado: \"" . $e->getMessage() . "\"\n";
}
assert($tentouProdutoZerado, "Backend deveria ter bloqueado produto esgotado");

// 7. Edição válida: diminuir de 2 águas para 1 água
echo "\n4. Validando Edição Válida (Diminuindo de 2 para 1 unidade):\n";
$edicaoValida = Pedido::editarPedido($pedido['id'], $resp['id'], [
    ['produto_id' => $prodAguaId, 'quantidade' => 1]
], $momentoAberto);

assert($edicaoValida['diferenca'] < 0, "Deveria ter gerado estorno de saldo");
echo "  [PASS] Pedido atualizado com sucesso com 1 água mineral. Diferença estornada: R$ " . number_format(abs($edicaoValida['diferenca']), 2, ',', '.') . "\n";

$prodAguaDbApos = Produto::buscarPorId($prodAguaId);
assert((int)$prodAguaDbApos['estoque_reservado'] === 1, "Estoque reservado deve passar para 1");
echo "  [PASS] Estoque reservado atualizado para 1 un no banco de dados\n";

echo "\n====================================================================\n";
echo " TODOS OS TESTES DE CONTROLE DE ESTOQUE NA EDIÇÃO FORAM APROVADOS! \n";
echo "====================================================================\n";
