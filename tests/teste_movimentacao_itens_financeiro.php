<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Validação da Movimentação dos Itens para a Aba Financeira e Renomeação para Entradas
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/Router.php';
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

echo "======================================================================\n";
echo " TESTES: MOVIMENTAÇÃO DE ITENS PARA ABA FINANCEIRA & RENOMEAÇÃO ENTRADAS\n";
echo "======================================================================\n\n";

$passou = 0;
$falhou = 0;

function assertCustom(bool $condicao, string $descricao): void {
    global $passou, $falhou;
    if ($condicao) {
        echo "  [OK] " . $descricao . "\n";
        $passou++;
    } else {
        echo "  [FALHA] " . $descricao . "\n";
        $falhou++;
    }
}

// 1. Renderização do Sidebar no perfil Administrador
$baseUrl = '';
$user = Auth::user();
$userRole = 'administrador';
$isAdmin = true;
$activeMenu = '';

ob_start();
require __DIR__ . '/../app/views/layouts/sidebar.php';
$html = ob_get_clean();

// Isolamento dos blocos dos flyouts
$opBloco = explode('<!-- Flyout 2:', explode('id="flyoutOperacional"', $html)[1] ?? '')[0] ?? '';
$estBloco = explode('<!-- Flyout 3:', explode('id="flyoutEstoque"', $html)[1] ?? '')[0] ?? '';
$finHtmlCompleto = explode('id="flyoutFinanceiro"', $html)[1] ?? '';
$finCorpoCompleto = explode('<!-- Flyout 4:', $finHtmlCompleto)[0] ?? '';
$relBloco = explode('<!-- Flyout 6:', explode('id="flyoutRelatorios"', $html)[1] ?? '')[0] ?? '';

// Validação Operacional
assertCustom(str_contains($opBloco, '/operacional/corte'), "Operacional contém Corte do Turno");
assertCustom(str_contains($opBloco, '/operacional/recebimento'), "Operacional contém Conferência Fornecedor");
assertCustom(str_contains($opBloco, '/operacional/tickets'), "Operacional contém Tickets Térmicos");
assertCustom(!str_contains($opBloco, '/operacional/caixa'), "Operacional NÃO contém Caixa do Turno (removido)");
assertCustom(!str_contains($opBloco, '/operacional/estornos'), "Operacional NÃO contém Estorno de Vendas (removido)");
assertCustom(!str_contains($opBloco, '/operacional/pdv'), "Operacional NÃO contém PDV Balcão (removido)");

// Validação Relatórios
assertCustom(str_contains($relBloco, '/relatorios/extrato-aluno'), "Relatórios contém Extrato do Aluno");
assertCustom(str_contains($relBloco, '/relatorios/vendas-filtros'), "Relatórios contém Vendas por Alimento");
assertCustom(str_contains($relBloco, '/relatorios/devedores'), "Relatórios contém Histórico MED & Quitações");
assertCustom(!str_contains($relBloco, '/relatorios/estoque-filtros'), "Relatórios NÃO contém Movimentações de Estoque (movido para Estoque)");
assertCustom(str_contains($html, '/relatorios/estoque-filtros'), "Sidebar contém Movimentações de Estoque");
assertCustom(str_contains($relBloco, '/relatorios/corte'), "Relatórios contém Corte do Turno");
assertCustom(!str_contains($relBloco, '/relatorios/fechamento-caixa'), "Relatórios NÃO contém Fechamento de Caixa (removido)");
assertCustom(!str_contains($relBloco, '/relatorios/arrecadacao'), "Relatórios NÃO contém Arrecadação/Entradas (removido)");

// Validação Financeiro
assertCustom(str_contains($finCorpoCompleto, '/operacional/caixa') && str_contains($finCorpoCompleto, 'Caixa do Turno'), "Financeiro contém Caixa do Turno");
assertCustom(str_contains($finCorpoCompleto, '/operacional/estornos') && str_contains($finCorpoCompleto, 'Estorno de Vendas'), "Financeiro contém Estorno de Vendas");
assertCustom(str_contains($finCorpoCompleto, '/operacional/pdv') && str_contains($finCorpoCompleto, 'PDV Balcão'), "Financeiro contém PDV Balcão");
assertCustom(str_contains($finCorpoCompleto, '/relatorios/fechamento-caixa') && str_contains($finCorpoCompleto, 'Fechamento de Caixa'), "Financeiro contém Fechamento de Caixa");
assertCustom(str_contains($finCorpoCompleto, '/relatorios/arrecadacao') && str_contains($finCorpoCompleto, 'Entradas'), "Financeiro contém Entradas");
assertCustom(!str_contains($finCorpoCompleto, 'Arrecadação & Entradas'), "Financeiro NÃO usa rótulo antigo 'Arrecadação & Entradas'");
assertCustom(str_contains($finCorpoCompleto, '/financeiro/despesas') && str_contains($finCorpoCompleto, 'Despesas da Cantina'), "Financeiro contém Despesas da Cantina");
assertCustom(str_contains($finCorpoCompleto, '/financeiro/dre') && str_contains($finCorpoCompleto, 'DRE Gerencial'), "Financeiro contém DRE Gerencial");

// 2. Validação da view web de Entradas (arrecadacao.php)
$viewArrecadacao = file_get_contents(__DIR__ . '/../app/views/relatorios/arrecadacao.php');
assertCustom(str_contains($viewArrecadacao, '<h1 class="h4 font-weight-bold mb-0 text-dark">Entradas</h1>'), "View arrecadacao.php possui título H1 'Entradas'");
assertCustom(!str_contains($viewArrecadacao, '<h1 class="h4 font-weight-bold mb-0 text-dark">Arrecadação Financeira'), "View arrecadacao.php removeu título antigo 'Arrecadação Financeira'");

// 3. Validação do espelho de impressão / PDF (arrecadacao_pdf.php)
$viewPdf = file_get_contents(__DIR__ . '/../app/views/relatorios/arrecadacao_pdf.php');
assertCustom(str_contains($viewPdf, '<title>Relatório de Entradas — Colégio Monteiro Lobato</title>') || str_contains($viewPdf, 'Entradas'), "View PDF possui title de Entradas");
assertCustom(str_contains($viewPdf, 'DEMONSTRATIVO FINANCEIRO - CANAIS DE ENTRADA'), "View PDF possui cabeçalho 'DEMONSTRATIVO FINANCEIRO - CANAIS DE ENTRADA'");
assertCustom(str_contains($viewPdf, 'Total Arrecadado (Cash-In Real)'), "View PDF possui indicador 'Total Arrecadado (Cash-In Real)'");
assertCustom(str_contains($viewPdf, 'Recarga PDV Pix'), "View PDF possui linha 'Recarga PDV Pix'");
assertCustom(str_contains($viewPdf, 'Recarga PDV Dinheiro'), "View PDF possui linha 'Recarga PDV Dinheiro'");
assertCustom(str_contains($viewPdf, 'Vendas PDV Pix'), "View PDF possui linha 'Vendas PDV Pix'");
assertCustom(str_contains($viewPdf, 'Vendas PDV Dinheiro'), "View PDF possui linha 'Vendas PDV Dinheiro'");
assertCustom(str_contains($viewPdf, 'TOTAL GERAL DE ENTRADAS (CASH-IN LÍQUIDO)'), "View PDF possui totalizador 'TOTAL GERAL DE ENTRADAS (CASH-IN LÍQUIDO)'");
assertCustom(str_contains($viewPdf, 'Entradas do Dia'), "View PDF possui coluna 'Entradas do Dia'");

// 4. Validação do RelatoriosController
$controllerCode = file_get_contents(__DIR__ . '/../app/controllers/RelatoriosController.php');
assertCustom(str_contains($controllerCode, "'title'       => 'Entradas — Colégio Monteiro Lobato'"), "RelatoriosController define título de página 'Entradas'");
assertCustom(str_contains($controllerCode, 'RELATÓRIO DE ENTRADAS (CASH-IN)'), "RelatoriosController exporta CSV com cabeçalho 'RELATÓRIO DE ENTRADAS (CASH-IN)'");
assertCustom(str_contains($controllerCode, 'RESUMO POR CANAIS DE ENTRADAS'), "RelatoriosController exporta CSV com seção 'RESUMO POR CANAIS DE ENTRADAS'");
assertCustom(str_contains($controllerCode, 'TOTAL GERAL DE ENTRADAS'), "RelatoriosController exporta CSV com linha 'TOTAL GERAL DE ENTRADAS'");
assertCustom(str_contains($controllerCode, '$filename = "entradas_{$dataInicio}_{$dataFim}.csv"'), "RelatoriosController exporta arquivo CSV com prefixo 'entradas_'");
assertCustom(str_contains($controllerCode, 'public function entradas()'), "RelatoriosController possui método alias entradas()");

// 5. Validação de rotas no index.php
$indexCode = file_get_contents(__DIR__ . '/../index.php');
assertCustom(str_contains($indexCode, "'/financeiro/entradas'"), "index.php define rota /financeiro/entradas");
assertCustom(str_contains($indexCode, "'/financeiro/fechamento-caixa'"), "index.php define rota /financeiro/fechamento-caixa");

echo "\n-------------------------------------------------------\n";
echo "Resumo: {$passou} aprovados, {$falhou} falhas.\n";
echo "=======================================================\n";

if ($falhou > 0) {
    exit(1);
}
