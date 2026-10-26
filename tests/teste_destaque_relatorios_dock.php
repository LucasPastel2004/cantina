<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Validação do Destaque Ativo (Contorno Azul / is-current / active) nos Relatórios do Dock
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

use App\Core\Session;
use App\Core\Auth;

Session::start();
$_SESSION['usuario_logado'] = [
    'id'      => 1,
    'nome'    => 'Administrador Geral',
    'usuario' => 'admin',
    'role'    => 'administrador',
    'perfil'  => 'administrador'
];

echo "=======================================================\n";
echo "   TESTE: DESTAQUE ATIVO DOS RELATÓRIOS NO DOCK SIDEBAR\n";
echo "=======================================================\n\n";

$passou = 0;
$falhou = 0;

function assertTeste(bool $condicao, string $descricao): void {
    global $passou, $falhou;
    if ($condicao) {
        echo "  [OK] " . $descricao . "\n";
        $passou++;
    } else {
        echo "  [FALHA] " . $descricao . "\n";
        $falhou++;
    }
}

// 1. Validação estática no RelatoriosController.php
$controllerCode = file_get_contents(__DIR__ . '/../app/controllers/RelatoriosController.php');

assertTeste(
    (bool)preg_match("/'activeMenu'\s*=>\s*'extrato_aluno'/", $controllerCode),
    "RelatoriosController::extratoAluno define 'activeMenu' => 'extrato_aluno'"
);

assertTeste(
    (bool)preg_match("/'activeMenu'\s*=>\s*'vendas_filtros'/", $controllerCode),
    "RelatoriosController::vendasFiltros define 'activeMenu' => 'vendas_filtros'"
);

assertTeste(
    (bool)preg_match("/'activeMenu'\s*=>\s*'fechamento_caixa_relatorio'/", $controllerCode),
    "RelatoriosController::fechamentoCaixa define 'activeMenu' => 'fechamento_caixa_relatorio'"
);

// 2. Renderização do sidebar com cada um dos 7 relatórios
$relatorios = [
    'extrato_aluno' => [
        'nome' => 'Extrato do Aluno',
        'href' => '/relatorios/extrato-aluno'
    ],
    'vendas_filtros' => [
        'nome' => 'Vendas por Alimento',
        'href' => '/relatorios/vendas-filtros'
    ],
    'arrecadacao' => [
        'nome' => 'Arrecadação & Entradas',
        'href' => '/relatorios/arrecadacao'
    ],
    'fechamento_caixa_relatorio' => [
        'nome' => 'Fechamento de Caixa',
        'href' => '/relatorios/fechamento-caixa'
    ],
    'devedores' => [
        'nome' => 'Histórico MED & Quitações',
        'href' => '/relatorios/devedores'
    ],
    'movimentacoes_estoque' => [
        'nome' => 'Movimentações de Estoque',
        'href' => '/relatorios/estoque-filtros'
    ],
    'corte_relatorio' => [
        'nome' => 'Corte do Turno',
        'href' => '/relatorios/corte'
    ]
];

$renderSidebar = function(string $activeMenu): string {
    $baseUrl = '';
    $user = Auth::user();
    $userRole = 'administrador';
    $isAdmin = true;
    ob_start();
    require __DIR__ . '/../app/views/layouts/sidebar.php';
    return ob_get_clean();
};

foreach ($relatorios as $menuKey => $info) {
    $html = $renderSidebar($menuKey);
    
    // O botão principal do dock para a categoria 'relatorios' deve estar com 'is-current'
    $temDockAtivo = preg_match('/<button[^>]*class="[^"]*is-current[^"]*"[^>]*id="btnCategoryRelatorios"|<button[^>]*id="btnCategoryRelatorios"[^>]*class="[^"]*is-current[^"]*"/', $html);
    assertTeste($temDockAtivo === 1, "Dock: Botão de Relatórios tem classe 'is-current' quando activeMenu='{$menuKey}'");

    // O item específico no flyout deve ter 'nav-link active'
    $padraoLinkAtivo = '/<a\s+href="[^"]*' . preg_quote($info['href'], '/') . '"\s+class="nav-link\s+active"/';
    $temLinkAtivo = preg_match($padraoLinkAtivo, $html);
    assertTeste($temLinkAtivo === 1, "Flyout: Item '{$info['nome']}' tem classe 'nav-link active' com contorno azul");
}

// 3. Validação de alias defensivos (fechamento_caixa e relatorio_alimento)
$htmlAliasCaixa = $renderSidebar('fechamento_caixa');
assertTeste(
    (bool)preg_match('/<a\s+href="[^"]*\/relatorios\/fechamento-caixa"\s+class="nav-link\s+active"/', $htmlAliasCaixa),
    "Flyout: Alias 'fechamento_caixa' ativa corretamente o link Fechamento de Caixa"
);

$htmlAliasAlimento = $renderSidebar('relatorio_alimento');
assertTeste(
    (bool)preg_match('/<a\s+href="[^"]*\/relatorios\/vendas-filtros"\s+class="nav-link\s+active"/', $htmlAliasAlimento),
    "Flyout: Alias 'relatorio_alimento' ativa corretamente o link Vendas por Alimento"
);

echo "\n-------------------------------------------------------\n";
echo "Resumo: {$passou} aprovados, {$falhou} falhas.\n";
echo "=======================================================\n";

if ($falhou > 0) {
    exit(1);
}
