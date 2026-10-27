<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Validação da Migração de Movimentações de Estoque e Fornecedores para o Flyout de Estoque
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';

use App\Core\Session;
use App\Core\Auth;

Session::start();

echo "========================================================================\n";
echo "   TESTE: MOVIMENTAÇÕES DE ESTOQUE E FORNECEDORES NA ABA DE ESTOQUE\n";
echo "========================================================================\n\n";

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

$renderSidebar = function(string $activeMenu, string $role = 'administrador'): string {
    $baseUrl = '';
    $user = [
        'id'      => 1,
        'nome'    => ($role === 'administrador') ? 'Administrador Geral' : 'Operadora Balcão',
        'usuario' => ($role === 'administrador') ? 'admin' : 'operador',
        'role'    => $role,
        'perfil'  => $role
    ];
    $userRole = $role;
    $isAdmin = ($role === 'administrador');
    ob_start();
    require __DIR__ . '/../app/views/layouts/sidebar.php';
    return ob_get_clean();
};

function extrairBlocoFlyout(string $html, string $idFlyout): string {
    $pos = strpos($html, 'id="' . $idFlyout . '"');
    if ($pos === false) return '';
    $resto = substr($html, $pos);
    $fim = strpos($resto, '</aside>');
    $proximo = strpos($resto, 'class="sidebar-flyout"');
    if ($proximo !== false && $proximo > 0) {
        return substr($resto, 0, $proximo);
    }
    return ($fim !== false) ? substr($resto, 0, $fim) : $resto;
}

function botaoTemClasse(string $html, string $btnId, string $classe): bool {
    return (bool)preg_match('/<button[^>]*id="' . preg_quote($btnId, '/') . '"[^>]*class="[^"]*' . preg_quote($classe, '/') . '[^"]*"|<button[^>]*class="[^"]*' . preg_quote($classe, '/') . '[^"]*"[^>]*id="' . preg_quote($btnId, '/') . '"/s', $html);
}

// 1. Validação com activeMenu = 'movimentacoes_estoque'
$htmlMov = $renderSidebar('movimentacoes_estoque', 'administrador');

assertTeste(botaoTemClasse($htmlMov, 'btnCategoryEstoque', 'is-current'), "Dock: Botão de Estoque tem classe 'is-current' quando activeMenu='movimentacoes_estoque'");
assertTeste(!botaoTemClasse($htmlMov, 'btnCategoryRelatorios', 'is-current'), "Dock: Botão de Relatórios NÃO tem 'is-current' quando activeMenu='movimentacoes_estoque'");

$corpoEstoque = extrairBlocoFlyout($htmlMov, 'flyoutEstoque');
$corpoRelatorios = extrairBlocoFlyout($htmlMov, 'flyoutRelatorios');

assertTeste(
    str_contains($corpoEstoque, '/relatorios/estoque-filtros'),
    "Flyout Estoque: Contém o link para Movimentações de Estoque (/relatorios/estoque-filtros)"
);

assertTeste(
    (bool)preg_match('/<a\s+href="[^"]*\/relatorios\/estoque-filtros"[^>]*class="[^"]*nav-link\s+active[^"]*"/s', $corpoEstoque),
    "Flyout Estoque: Link de Movimentações de Estoque recebe classe 'nav-link active'"
);

assertTeste(
    !str_contains($corpoRelatorios, '/relatorios/estoque-filtros'),
    "Flyout Relatórios: NÃO contém mais o link de Movimentações de Estoque (removido com sucesso)"
);

// 2. Validação com activeMenu = 'fornecedores'
$htmlForn = $renderSidebar('fornecedores', 'administrador');

assertTeste(botaoTemClasse($htmlForn, 'btnCategoryEstoque', 'is-current'), "Dock: Botão de Estoque tem classe 'is-current' quando activeMenu='fornecedores'");
assertTeste(!botaoTemClasse($htmlForn, 'btnCategoryAdmin', 'is-current'), "Dock: Botão de Administração NÃO tem 'is-current' quando activeMenu='fornecedores'");

$corpoEstoqueForn = extrairBlocoFlyout($htmlForn, 'flyoutEstoque');
$corpoAdmin = extrairBlocoFlyout($htmlForn, 'navSectionAdmin');

assertTeste(
    str_contains($corpoEstoqueForn, '/admin/fornecedores'),
    "Flyout Estoque: Contém o link para Fornecedores (/admin/fornecedores)"
);

assertTeste(
    (bool)preg_match('/<a\s+href="[^"]*\/admin\/fornecedores"[^>]*class="[^"]*nav-link\s+active[^"]*"/s', $corpoEstoqueForn),
    "Flyout Estoque: Link de Fornecedores recebe classe 'nav-link active'"
);

assertTeste(
    !str_contains($corpoAdmin, '/admin/fornecedores'),
    "Flyout Administração: NÃO contém mais o link de Fornecedores (removido com sucesso)"
);

// 3. Validação de RBAC para perfil Operador
$htmlOperador = $renderSidebar('estoque', 'operador');
$corpoEstoqueOp = extrairBlocoFlyout($htmlOperador, 'flyoutEstoque');

assertTeste(
    str_contains($corpoEstoqueOp, '/relatorios/estoque-filtros'),
    "RBAC Operador: Movimentações de Estoque está visível para Operador no Flyout de Estoque"
);

assertTeste(
    !str_contains($corpoEstoqueOp, '/admin/fornecedores'),
    "RBAC Operador: Fornecedores está devidamente OCULTO para Operador no Flyout de Estoque"
);

// 4. Validação de todos os 5 itens de Estoque para Administrador
$htmlAdmin = $renderSidebar('estoque', 'administrador');
$corpoEstoqueAdm = extrairBlocoFlyout($htmlAdmin, 'flyoutEstoque');

assertTeste(str_contains($corpoEstoqueAdm, 'Saldos & Pausa'), "Flyout Estoque: Contém 'Saldos & Pausa'");
assertTeste(str_contains($corpoEstoqueAdm, 'Lançamento de Perdas'), "Flyout Estoque: Contém 'Lançamento de Perdas'");
assertTeste(str_contains($corpoEstoqueAdm, 'Entrada de Estoque'), "Flyout Estoque: Contém 'Entrada de Estoque'");
assertTeste(str_contains($corpoEstoqueAdm, 'Movimentações de Estoque'), "Flyout Estoque: Contém 'Movimentações de Estoque'");
assertTeste(str_contains($corpoEstoqueAdm, 'Fornecedores'), "Flyout Estoque: Contém 'Fornecedores'");

echo "\n------------------------------------------------------------------------\n";
echo "Resumo: {$passou} aprovados, {$falhou} falhas.\n";
echo "========================================================================\n";

if ($falhou > 0) {
    exit(1);
}
