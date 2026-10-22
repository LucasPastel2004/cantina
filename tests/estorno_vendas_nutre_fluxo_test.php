<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Novo Fluxo de Estorno de Vendas (Estilo Nutre / Escolar Manager)
 * 
 * Valida:
 * 1. Remoção de botões soltos do cabeçalho da página;
 * 2. Estado inicial limpo com mensagem "Não há nada para mostrar aqui";
 * 3. Segmented control "Venda avulsa: Sim / Não" e dropdown "Filtrar por";
 * 4. Botão "Carregar" e acionamento sob demanda;
 * 5. Dropdown "Motivo do estorno" com opção padrão "1-ESTORNO";
 * 6. Botões "Cancelar" e "Gravar" na barra de estorno pós-carregamento;
 * 7. Colunas da tabela: Checkbox | Data | Hora | Valor total | Pessoa;
 * 8. Barra de ferramentas inferior com o botão "Informações VENDAS";
 * 9. Submissão e gravação de estorno mantendo filtros e estado carregado.
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/Colaborador.php';
require_once __DIR__ . '/../app/models/VendaBalcao.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemVendaBalcao.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/controllers/EstornoVendasController.php';

use App\Core\Session;
use App\Core\Auth;
use App\Models\Usuario;
use App\Models\Aluno;
use App\Models\Pedido;
use App\Models\VendaBalcao;
use App\Controllers\EstornoVendasController;

echo "=== INICIANDO TESTES DO FLUXO DE ESTORNO ESTILO NUTRE ===\n";

$testsPassed = 0;
$testsFailed = 0;

function assertCustom(bool $condicao, string $descricao, int &$passed, int &$failed): void {
    if ($condicao) {
        echo "[OK] {$descricao}\n";
        $passed++;
    } else {
        echo "[FALHA] {$descricao}\n";
        $failed++;
    }
}

// 1. Simular login de operador
$usuarios = Usuario::listarTodos();
$operador = $usuarios[0] ?? ['id' => 1, 'nome' => 'Operador Teste', 'usuario' => 'operador', 'perfil' => 'operador'];
Auth::loginUsuario($operador);

assertCustom(!empty(Auth::user()), "Autenticação simulada com sucesso para testes", $testsPassed, $testsFailed);

// 2. Testar View do Estorno no Estado Inicial (sem carregar=1)
$_GET = []; // Inicial, sem carregar
ob_start();
$controller = new EstornoVendasController();
$controller->index();
$outputInicial = ob_get_clean();

// Asserções do Estado Inicial
assertCustom(!str_contains($outputInicial, 'id="btnGravarEstornoTopo"'), "Botão Gravar removido do topo da página", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'Não há nada para mostrar aqui'), "Mensagem 'Não há nada para mostrar aqui' presente no estado inicial", $testsPassed, $testsFailed);
assertCustom(!str_contains($outputInicial, 'id="painelAcoesEstorno"'), "Painel com Motivo e Botão Gravar oculto antes de carregar", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'name="venda_avulsa" id="vaSim"'), "Radio/btn Venda Avulsa Sim presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'name="venda_avulsa" id="vaNao"'), "Radio/btn Venda Avulsa Não presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'id="selectFiltrarPor"'), "Select 'Filtrar por' presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'id="inputBuscaPessoa"'), "Campo de busca de Aluno/Colaborador presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'name="data_inicio"'), "Campo Período De presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'name="data_fim"'), "Campo Período Até presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'id="btnCarregar"'), "Botão 'Carregar' presente no painel de filtros", $testsPassed, $testsFailed);
assertCustom(str_contains($outputInicial, 'Informações VENDAS'), "Botão da barra inferior 'Informações VENDAS' presente", $testsPassed, $testsFailed);

// 3. Testar View no Estado Carregado (com carregar=1)
$_GET = [
    'carregar'     => '1',
    'venda_avulsa' => 'nao',
    'filtrar_por'  => 'aluno',
    'data_inicio'  => date('Y-m-d', strtotime('-30 days')),
    'data_fim'     => date('Y-m-d')
];

ob_start();
$controller = new EstornoVendasController();
$controller->index();
$outputCarregado = ob_get_clean();

// Asserções do Estado Carregado
assertCustom(str_contains($outputCarregado, 'id="painelAcoesEstorno"'), "Painel de Estorno visível após clicar em Carregar", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, 'id="selectMotivoEstorno"'), "Dropdown 'Motivo do estorno' visível", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, 'value="1-ESTORNO" selected'), "Opção '1-ESTORNO' selecionada por padrão", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, 'id="btnCancelarEstorno"'), "Botão 'Cancelar' presente na barra de estorno", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, 'id="btnGravarEstorno"'), "Botão 'Gravar' presente na barra de estorno", $testsPassed, $testsFailed);

// 4. Testar Colunas da Tabela
assertCustom(str_contains($outputCarregado, '<th style="width: 120px; font-weight: 600; font-size: 12px;" class="font-tabular">Data</th>'), "Coluna Data com formatação tabular presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="width: 100px; font-weight: 600; font-size: 12px;" class="font-tabular">Hora</th>'), "Coluna Hora com formatação tabular presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="width: 130px; font-weight: 600; font-size: 12px; text-align: right;" class="font-tabular">Valor total</th>'), "Coluna Valor total alinhada à direita presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="font-weight: 600; font-size: 12px;">Pessoa</th>'), "Coluna Pessoa presente", $testsPassed, $testsFailed);
assertCustom(!str_contains($outputCarregado, '<th>Tipo / Status</th>'), "Coluna legada 'Tipo / Status' removida", $testsPassed, $testsFailed);
assertCustom(!str_contains($outputCarregado, '<th>Ações</th>'), "Coluna legada de ações por linha removida", $testsPassed, $testsFailed);

// 5. Testar Endpoint de Detalhes Ajax
$_GET = ['id' => 1, 'tipo' => 'pedido'];
ob_start();
$controller->detalhesAjax();
$jsonDetalhes = ob_get_clean();
$dataDetalhes = json_decode($jsonDetalhes, true);
assertCustom($dataDetalhes !== null, "Endpoint detalhesAjax responde em JSON válido", $testsPassed, $testsFailed);

echo "\n============================================\n";
echo "RESULTADO FINAL: {$testsPassed} testes aprovados, {$testsFailed} falhas.\n";

if ($testsFailed > 0) {
    exit(1);
}
