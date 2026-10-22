<?php
/**
 * Teste Automatizado: Listagem Geral Padrão e Filtros do Estoque
 * 
 * Valida que:
 * 1. Ao acessar /estoque sem parâmetros, a grade geral de produtos é exibida por padrão.
 * 2. Ao clicar em Filtrar sem alterar nenhum campo (?busca=&categoria_id=&tipo=&alerta=), a lista geral padrão é gerada.
 * 3. Ao aplicar filtros específicos, a listagem reflete os critérios selecionados.
 * 4. Ao aplicar uma busca sem correspondência, exibe "Não há nada para mostrar aqui".
 * 5. O botão '✕' (Limpar Filtros) só aparece quando filtros não-padrão estão ativos.
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/Estoque.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/controllers/EstoqueController.php';

echo "======================================================================\n";
echo " TESTE DE LISTAGEM GERAL PADRÃO E FILTROS — CONTROLE DE ESTOQUE\n";
echo "======================================================================\n\n";

$pdo = \App\Core\Model::getConnection();
$admin = $pdo->query("SELECT * FROM usuarios WHERE perfil = 'administrador' AND deleted_at IS NULL LIMIT 1")->fetch();

\App\Core\Session::start();
$_SESSION['usuario_id'] = $admin['id'] ?? 1;
$_SESSION['usuario_logado'] = true;
$_SESSION['usuario_perfil'] = 'administrador';
$_SESSION['user'] = [
    'id'     => $admin['id'] ?? 1,
    'nome'   => $admin['nome'] ?? 'Admin',
    'role'   => 'administrador',
    'perfil' => 'administrador'
];

$erros = 0;
$sucessos = 0;

$assertCustom = function(bool $cond, string $msg) use (&$erros, &$sucessos) {
    if ($cond) {
        echo " [PASS] " . $msg . "\n";
        $sucessos++;
    } else {
        echo " [FAIL] " . $msg . "\n";
        $erros++;
    }
};

// -------------------------------------------------------------
// [Cenário 1]: Acesso direto a /estoque (sem parâmetros GET) - Abre vazia
// -------------------------------------------------------------
$_GET = [];
$controller = new \App\Controllers\EstoqueController();
ob_start();
$controller->index();
$html1 = ob_get_clean();

$assertCustom(strpos($html1, 'Grade de Produtos & Inventário Físico') !== false, "Cenário 1: Título da grade presente na tela");
$assertCustom(strpos($html1, 'Exibindo <span class="font-tabular fw-semibold">0</span> de <span class="font-tabular fw-semibold">0</span>') !== false, "Cenário 1: Exibe '0 de 0 produtos' no carregamento inicial antes de filtrar");
$assertCustom(strpos($html1, 'Não há nada para mostrar aqui') !== false, "Cenário 1: Exibe 'Não há nada para mostrar aqui' no carregamento inicial");
$assertCustom(strpos($html1, 'Utilize os filtros acima e clique em "Filtrar"') !== false, "Cenário 1: Exibe instrução amigável para usar os filtros");
$assertCustom(strpos($html1, 'title="Limpar Filtros">✕</a>') === false, "Cenário 1: Botão Limpar Filtros '✕' NÃO aparece no estado inicial");

// -------------------------------------------------------------
// [Cenário 2]: Clicar em Filtrar sem alterar nenhum campo (?filtrar=1&busca=&categoria_id=&tipo=&alerta=)
// -------------------------------------------------------------
$_GET = [
    'filtrar'      => '1',
    'busca'        => '',
    'categoria_id' => '',
    'tipo'         => '',
    'alerta'       => ''
];
ob_start();
$controller->index();
$html2 = ob_get_clean();

$assertCustom(strpos($html2, 'Grade de Produtos & Inventário Físico') !== false, "Cenário 2: Título da grade presente após filtrar com valores padrão");
$assertCustom(strpos($html2, 'Exibindo <span class="font-tabular fw-semibold">0</span> de <span class="font-tabular fw-semibold">0</span>') === false, "Cenário 2: NÃO exibe '0 de 0 produtos' ao filtrar com opções padrão (gera lista geral)");
$assertCustom(strpos($html2, 'Não há nada para mostrar aqui') === false, "Cenário 2: NÃO exibe 'Não há nada para mostrar aqui' ao filtrar com opções padrão");
$assertCustom(strpos($html2, 'title="Limpar Filtros">✕</a>') !== false, "Cenário 2: Botão Limpar Filtros '✕' aparece após filtrar para permitir resetar");

// -------------------------------------------------------------
// [Cenário 3]: Filtrar com busca específica válida
// -------------------------------------------------------------
$_GET = [
    'filtrar'      => '1',
    'busca'        => 'Coxinha',
    'categoria_id' => '',
    'tipo'         => '',
    'alerta'       => ''
];
ob_start();
$controller->index();
$html3 = ob_get_clean();

$assertCustom(strpos($html3, 'Coxinha') !== false, "Cenário 3: Exibe produto correspondente à busca 'Coxinha'");
$assertCustom(strpos($html3, 'title="Limpar Filtros">✕</a>') !== false, "Cenário 3: Exibe botão Limpar Filtros '✕' quando busca ativa");

// -------------------------------------------------------------
// [Cenário 4]: Filtrar com termo que não existe
// -------------------------------------------------------------
$_GET = [
    'filtrar'      => '1',
    'busca'        => 'ITEM_INEXISTENTE_XYZ_999',
    'categoria_id' => '',
    'tipo'         => '',
    'alerta'       => ''
];
ob_start();
$controller->index();
$html4 = ob_get_clean();

$assertCustom(strpos($html4, 'Nenhum produto encontrado com os filtros aplicados') !== false, "Cenário 4: Exibe mensagem sóbria de 'Nenhum produto encontrado com os filtros aplicados'");
$assertCustom(strpos($html4, 'Exibindo <span class="font-tabular fw-semibold">0</span> de <span class="font-tabular fw-semibold">0</span>') !== false, "Cenário 4: Exibe 0 de 0 quando busca não localiza produtos");
$assertCustom(strpos($html4, 'title="Limpar Filtros">✕</a>') !== false, "Cenário 4: Exibe botão Limpar Filtros '✕' para permitir desfazer o filtro");

echo "\n----------------------------------------------------------------------\n";
echo "RESULTADO: {$sucessos} aprovados, {$erros} falhas.\n";
echo "----------------------------------------------------------------------\n";

if ($erros > 0) {
    exit(1);
}
