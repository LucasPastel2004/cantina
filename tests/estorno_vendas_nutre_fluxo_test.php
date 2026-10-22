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
define('CANTINA_TEST_MODE', true);

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
require_once __DIR__ . '/../app/models/Auditoria.php';
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
assertCustom(str_contains($outputInicial, 'Informações'), "Coluna 'Informações' presente no cabeçalho da tabela", $testsPassed, $testsFailed);

assertCustom(str_contains($outputInicial, '<option value="colaborador" >Colaborador</option>') || str_contains($outputInicial, 'value="colaborador"'), "Opção Colaborador presente no select Filtrar por", $testsPassed, $testsFailed);
assertCustom(!str_contains($outputInicial, '<option value="colaborador" disabled>'), "Opção Colaborador NÃO está desabilitada quando Venda Avulsa é Não", $testsPassed, $testsFailed);

// 3. Testar View no Estado Carregado com Aluno (venda_avulsa = nao)
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

// 3.1. Testar View no Estado Carregado com Colaborador (venda_avulsa = nao)
$_GET = [
    'carregar'     => '1',
    'venda_avulsa' => 'nao',
    'filtrar_por'  => 'colaborador',
    'data_inicio'  => date('Y-m-d', strtotime('-30 days')),
    'data_fim'     => date('Y-m-d')
];
ob_start();
$controller = new EstornoVendasController();
$controller->index();
$outputColab = ob_get_clean();
assertCustom(str_contains($outputColab, 'id="painelAcoesEstorno"'), "Painel de Estorno visível ao carregar Colaborador", $testsPassed, $testsFailed);

// 3.2. Testar View no Estado Carregado com Venda Avulsa (venda_avulsa = sim: externo / anônimo / sobra)
$_GET = [
    'carregar'     => '1',
    'venda_avulsa' => 'sim',
    'data_inicio'  => date('Y-m-d', strtotime('-30 days')),
    'data_fim'     => date('Y-m-d')
];
ob_start();
$controller = new EstornoVendasController();
$controller->index();
$outputAvulsa = ob_get_clean();
assertCustom(str_contains($outputAvulsa, 'id="painelAcoesEstorno"'), "Painel de Estorno visível ao carregar Venda Avulsa", $testsPassed, $testsFailed);

// 4. Testar Colunas da Tabela
assertCustom(str_contains($outputCarregado, '<th style="width: 120px; font-weight: 600; font-size: 12px;" class="font-tabular">Data</th>'), "Coluna Data com formatação tabular presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="width: 100px; font-weight: 600; font-size: 12px;" class="font-tabular">Hora</th>'), "Coluna Hora com formatação tabular presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="width: 130px; font-weight: 600; font-size: 12px; text-align: right;" class="font-tabular">Valor total</th>'), "Coluna Valor total alinhada à direita presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="font-weight: 600; font-size: 12px;">Pessoa</th>'), "Coluna Pessoa presente", $testsPassed, $testsFailed);
assertCustom(str_contains($outputCarregado, '<th style="width: 130px; font-weight: 600; font-size: 12px; text-align: center;">Informações</th>'), "Coluna Informações presente", $testsPassed, $testsFailed);
assertCustom(!str_contains($outputCarregado, '<th>Tipo / Status</th>'), "Coluna legada 'Tipo / Status' removida", $testsPassed, $testsFailed);
assertCustom(!str_contains($outputCarregado, '<th>Ações</th>'), "Coluna legada de ações por linha removida", $testsPassed, $testsFailed);

// 5. Testar Endpoint de Detalhes Ajax
$_GET = ['id' => 1, 'tipo' => 'pedido'];
ob_start();
$controller->detalhesAjax();
$jsonDetalhes = ob_get_clean();
$dataDetalhes = json_decode($jsonDetalhes, true);
assertCustom($dataDetalhes !== null, "Endpoint detalhesAjax responde em JSON válido", $testsPassed, $testsFailed);

// 6. Testar Texto do Modal de Confirmação (Alinhado à Perda de Estoque e Vendas Retroativas)
assertCustom(
    str_contains($outputCarregado, 'Vendas de datas anteriores (salgados ou industrializados) <u>não retornam ao estoque físico</u> e são registradas automaticamente como <strong>perda de estoque</strong>'),
    "Texto do modal de confirmação esclarece que vendas anteriores de salgados ou industrializados não retornam ao estoque e viram perda de estoque",
    $testsPassed,
    $testsFailed
);

// 7. Testar Regra de Negócio: Estorno de Venda Retroativa NÃO Reintegra Estoque Físico e LOGA COMO PERDA DE ESTOQUE
$pdo = \App\Core\Model::getPdo();

// Obtém um produto salgado diário e um produto industrializado
$stmtProdSalgado = $pdo->query("SELECT id, estoque_atual FROM produtos WHERE tipo = 'salgado_diario' AND deleted_at IS NULL LIMIT 1");
$prodSalgado = $stmtProdSalgado->fetch(\PDO::FETCH_ASSOC);

$stmtProdInd = $pdo->query("SELECT id, estoque_atual FROM produtos WHERE tipo = 'industrializado_continuo' AND deleted_at IS NULL LIMIT 1");
$prodInd = $stmtProdInd->fetch(\PDO::FETCH_ASSOC);

if ($prodSalgado && $prodInd) {
    $salgadoId = (int)$prodSalgado['id'];
    $salgadoEstoqueAntes = (int)$prodSalgado['estoque_atual'];

    $indId = (int)$prodInd['id'];
    $indEstoqueAntes = (int)$prodInd['estoque_atual'];

    // Inserir venda balcão retroativa de 15 dias atrás contendo ambos
    $dataRetroativa = date('Y-m-d H:i:s', strtotime('-15 days'));
    $stmtVendaMock = $pdo->prepare("
        INSERT INTO vendas_balcao (
            sessao_caixa_id, operador_id, tipo_venda, aluno_id, forma_pagamento,
            valor_total, status, created_at
        ) VALUES (
            1, :op_id, 'avulso_aluno', 1, 'dinheiro',
            15.00, 'concluida', :created_at
        )
    ");
    $stmtVendaMock->execute([
        'op_id'      => $operador['id'],
        'created_at' => $dataRetroativa
    ]);
    $vendaIdMock = (int)$pdo->lastInsertId();

    // Item 1: 2 salgados
    $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 2, 5.00, 10.00)")
        ->execute([$vendaIdMock, $salgadoId]);

    // Item 2: 1 industrializado
    $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 1, 5.00, 5.00)")
        ->execute([$vendaIdMock, $indId]);

    // Executar estorno da venda de 15 dias atrás
    VendaBalcao::estornarVenda($vendaIdMock, (int)$operador['id'], 'Estorno teste retroativo 15 dias');

    // 1. Verificar se o estoque físico de salgado diário permaneceu inalterado
    $salgadoEstoqueDepois = (int)$pdo->query("SELECT estoque_atual FROM produtos WHERE id = {$salgadoId}")->fetchColumn();
    assertCustom(
        $salgadoEstoqueAntes === $salgadoEstoqueDepois,
        "Estoque físico de salgado diário permanece inalterado ({$salgadoEstoqueAntes} un) sem adição indevida",
        $testsPassed,
        $testsFailed
    );

    // 2. Verificar se o estoque físico de industrializado contínuo permaneceu inalterado
    $indEstoqueDepois = (int)$pdo->query("SELECT estoque_atual FROM produtos WHERE id = {$indId}")->fetchColumn();
    assertCustom(
        $indEstoqueAntes === $indEstoqueDepois,
        "Estoque físico de produto industrializado permanece inalterado ({$indEstoqueAntes} un) após venda retroativa",
        $testsPassed,
        $testsFailed
    );

    // 3. Verificar se foram criados registros de perda de estoque em estoque_movimentacoes para ambos os itens
    $stmtPerdas = $pdo->prepare("
        SELECT produto_id, tipo_movimentacao, quantidade, motivo_perda, justificativa
        FROM estoque_movimentacoes
        WHERE tipo_movimentacao = 'saida_perda'
          AND justificativa LIKE :busca
        ORDER BY id DESC
    ");
    $stmtPerdas->execute(['busca' => "%Venda #{$vendaIdMock}%"]);
    $perdasRegistradas = $stmtPerdas->fetchAll(\PDO::FETCH_ASSOC);

    assertCustom(
        count($perdasRegistradas) === 2,
        "Exatamente 2 registros de perda de estoque gerados para os itens da venda retroativa",
        $testsPassed,
        $testsFailed
    );

    $motivoPerdaValido = true;
    foreach ($perdasRegistradas as $p) {
        if ($p['motivo_perda'] !== 'sobra_producao' || $p['tipo_movimentacao'] !== 'saida_perda') {
            $motivoPerdaValido = false;
        }
    }
    assertCustom($motivoPerdaValido, "Itens estornados foram devidamente logados com tipo 'saida_perda' e motivo 'sobra_producao'", $testsPassed, $testsFailed);

    // 8. Testar Presença do Campo 'Produto consumido?' Exclusivamente no Modal de Confirmação (Oculto por padrão, ativado apenas para vendas de hoje)
    assertCustom(
        str_contains($outputCarregado, 'Produto consumido?'),
        "Campo 'Produto consumido?' presente na interface de estorno",
        $testsPassed,
        $testsFailed
    );
    assertCustom(
        !str_contains($outputCarregado, 'id="boxProdutoConsumidoBarra"'),
        "Campo 'Produto consumido?' removido da barra superior de estorno (estilo limpo do Nutre)",
        $testsPassed,
        $testsFailed
    );
    assertCustom(
        str_contains($outputCarregado, 'id="boxProdutoConsumidoModal" style="display: none !important;"'),
        "Campo 'Produto consumido?' restrito ao modal e inicia oculto para não aparecer em vendas anteriores",
        $testsPassed,
        $testsFailed
    );
    assertCustom(
        !str_contains($outputCarregado, 'name="csrf_token" value=""'),
        "Token CSRF preenchido corretamente no formulário formGravarEstorno (sem valor vazio)",
        $testsPassed,
        $testsFailed
    );

    // 9. Testar Venda de HOJE com produtoConsumido = true (Já consumido no recreio: NÃO devolve ao estoque e vira perda)
    $stmtSalgHoje = $pdo->query("SELECT id, estoque_atual FROM produtos WHERE tipo = 'salgado_diario' AND deleted_at IS NULL LIMIT 1");
    $salgHoje = $stmtSalgHoje->fetch(\PDO::FETCH_ASSOC);
    if ($salgHoje) {
        $sId = (int)$salgHoje['id'];
        $estAntesConsumido = (int)$salgHoje['estoque_atual'];

        $stmtVendaHoje1 = $pdo->prepare("
            INSERT INTO vendas_balcao (
                sessao_caixa_id, operador_id, tipo_venda, aluno_id, forma_pagamento,
                valor_total, status, created_at
            ) VALUES (
                1, :op_id, 'avulso_aluno', 1, 'dinheiro',
                6.00, 'concluida', NOW()
            )
        ");
        $stmtVendaHoje1->execute(['op_id' => $operador['id']]);
        $vIdHoje1 = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 1, 6.00, 6.00)")
            ->execute([$vIdHoje1, $sId]);

        // Estornar indicando produtoConsumido = true (aluno já consumiu)
        VendaBalcao::estornarVenda($vIdHoje1, (int)$operador['id'], 'Estorno lanche consumido', '127.0.0.1', true);

        $estDepoisConsumido = (int)$pdo->query("SELECT estoque_atual FROM produtos WHERE id = {$sId}")->fetchColumn();
        assertCustom(
            $estAntesConsumido === $estDepoisConsumido,
            "Venda de hoje com produto consumido = true NÃO incrementa o estoque físico (permaneceu {$estAntesConsumido} un)",
            $testsPassed,
            $testsFailed
        );

        $stmtPerdaHoje = $pdo->prepare("SELECT id FROM estoque_movimentacoes WHERE tipo_movimentacao = 'saida_perda' AND justificativa LIKE :j");
        $stmtPerdaHoje->execute(['j' => "%Venda #{$vIdHoje1}%"]);
        assertCustom(!empty($stmtPerdaHoje->fetch()), "Venda de hoje com produto consumido = true gera perda de estoque em estoque_movimentacoes", $testsPassed, $testsFailed);

        $pdo->exec("DELETE FROM estoque_movimentacoes WHERE justificativa LIKE '%Venda #{$vIdHoje1}%'");
        $pdo->exec("DELETE FROM itens_venda_balcao WHERE venda_balcao_id = {$vIdHoje1}");
        $pdo->exec("DELETE FROM vendas_balcao WHERE id = {$vIdHoje1}");
    }

    // 10. Testar Venda de HOJE com produtoConsumido = false (Desistiu antes de pegar em mãos: REINTEGRA o estoque físico)
    if ($salgHoje) {
        $sId = (int)$salgHoje['id'];
        $estAntesNaoConsumido = (int)$pdo->query("SELECT estoque_atual FROM produtos WHERE id = {$sId}")->fetchColumn();

        $stmtVendaHoje2 = $pdo->prepare("
            INSERT INTO vendas_balcao (
                sessao_caixa_id, operador_id, tipo_venda, aluno_id, forma_pagamento,
                valor_total, status, created_at
            ) VALUES (
                1, :op_id, 'avulso_aluno', 1, 'dinheiro',
                6.00, 'concluida', NOW()
            )
        ");
        $stmtVendaHoje2->execute(['op_id' => $operador['id']]);
        $vIdHoje2 = (int)$pdo->lastInsertId();

        $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 2, 3.00, 6.00)")
            ->execute([$vIdHoje2, $sId]);

        // Estornar indicando produtoConsumido = false (não consumido, devolvido antes de pegar)
        VendaBalcao::estornarVenda($vIdHoje2, (int)$operador['id'], 'Estorno antes de pegar salgado', '127.0.0.1', false);

        $estDepoisNaoConsumido = (int)$pdo->query("SELECT estoque_atual FROM produtos WHERE id = {$sId}")->fetchColumn();
        assertCustom(
            $estDepoisNaoConsumido === ($estAntesNaoConsumido + 2),
            "Venda de hoje com produto consumido = false REINTEGRA fisicamente o estoque (+2 un, total: {$estDepoisNaoConsumido})",
            $testsPassed,
            $testsFailed
        );

        // Reverte as 2 unidades do teste
        $pdo->exec("UPDATE produtos SET estoque_atual = estoque_atual - 2 WHERE id = {$sId}");
        $pdo->exec("DELETE FROM estoque_movimentacoes WHERE justificativa LIKE '%Venda de Balcão #{$vIdHoje2}%'");
        $pdo->exec("DELETE FROM itens_venda_balcao WHERE venda_balcao_id = {$vIdHoje2}");
        $pdo->exec("DELETE FROM vendas_balcao WHERE id = {$vIdHoje2}");
    }
}

// 11. Testar Submissão do Formulário de Gravação com CSRF e Redirecionamento sem Rota Duplicada
$tokenCsrf = Session::getCsrfToken();
$_SERVER['HTTP_REFERER'] = 'http://localhost:8080/operacional/estornos?carregar=1';
$_POST = [
    'csrf_token'        => $tokenCsrf,
    'venda_avulsa'      => 'nao',
    'filtrar_por'       => 'aluno',
    'selecionados'      => [], // vazio para testar fluxo defensivo de redirect sem 404
    'motivo_estorno'    => '1-ESTORNO',
    'produto_consumido' => 'sim'
];

ob_start();
$controller = new EstornoVendasController();
$controller->gravar();
$outputGravar = ob_get_clean();

assertCustom(
    !str_contains($outputGravar, '/http://localhost:8080') && !str_contains($outputGravar, '404'),
    "Redirecionamento do controller trata URL absoluta do HTTP_REFERER sem prefixar indevidamente /http://...",
    $testsPassed,
    $testsFailed
);

echo "\n============================================\n";
echo "RESULTADO FINAL: {$testsPassed} testes aprovados, {$testsFailed} falhas.\n";

if ($testsFailed > 0) {
    exit(1);
}
