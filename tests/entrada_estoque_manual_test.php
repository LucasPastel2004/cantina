<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado da Nova Aba e Fluxo de Entrada Manual de Estoque (sem NF-e)
 * 
 * Conforme solicitação do usuário e docs/DESIGN.md
 */

namespace Tests;

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\Estoque;
use App\Models\Produto;
use App\Models\Auditoria;
use App\Controllers\EstoqueController;

class EntradaEstoqueManualTest
{
    private \PDO $pdo;
    private int $total = 0;
    private int $aprovados = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    private function assert(string $descricao, bool $condicao): void
    {
        $this->total++;
        if ($condicao) {
            $this->aprovados++;
            echo "  [PASS] {$descricao}\n";
        } else {
            echo "  [FAIL] {$descricao}\n";
        }
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO TESTES — ENTRADA MANUAL DE ESTOQUE (SEM NOTA FISCAL)\n";
        echo "======================================================================\n\n";

        $this->testarColunaBancoDeDados();
        $this->testarModeloDarEntradaManual();
        $this->testarModeloDarEntradaLoteManual();
        $this->testarValidacoesEntrada();
        $this->testarListagemEntradasESugestoes();
        $this->testarControllerERotas();
        $this->testarViewsESidebar();

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->aprovados}/{$this->total} testes aprovados.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->aprovados !== $this->total) {
            exit(1);
        }
    }

    private function testarColunaBancoDeDados(): void
    {
        echo "1. Validando estrutura de dados (Migration 032)...\n";

        $col = $this->pdo->query("SHOW COLUMNS FROM `estoque_movimentacoes` LIKE 'fornecedor'")->fetch(\PDO::FETCH_ASSOC);
        $this->assert("Coluna 'fornecedor' existe na tabela estoque_movimentacoes", !empty($col));
        $this->assert("Coluna 'fornecedor' é do tipo VARCHAR", str_starts_with(strtolower($col['Type'] ?? ''), 'varchar'));

        $indices = $this->pdo->query("SHOW INDEX FROM `estoque_movimentacoes` WHERE Column_name = 'fornecedor'")->fetchAll();
        $this->assert("Índice de busca para fornecedor existe em estoque_movimentacoes", count($indices) > 0);
    }

    private function testarModeloDarEntradaManual(): void
    {
        echo "\n2. Validando processamento atômico de darEntradaManual...\n";

        // Cria ou localiza um produto para teste
        $produtos = Produto::listarTodos();
        if (empty($produtos)) {
            $this->assert("Existe produto para teste", false);
            return;
        }
        $produto = $produtos[0];
        $prodId = (int)$produto['id'];
        $saldoAntes = (int)$produto['estoque_atual'];

        $userDb = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $operadorIdReal = (int)($userDb['id'] ?? 1);

        $qtdEntrada = 15;
        $fornecedorTeste = 'Distribuidora Teste QA ' . date('His');
        $obsTeste = 'Recebimento de teste manual ' . date('d/m/Y');

        $sucesso = Estoque::darEntradaManual($prodId, $qtdEntrada, $fornecedorTeste, 3.50, $obsTeste, $operadorIdReal);
        $this->assert("darEntradaManual executou com sucesso retornando true", $sucesso === true);

        // Verifica saldo atualizado do produto
        $prodDepois = Produto::buscarPorId($prodId);
        $saldoDepois = (int)$prodDepois['estoque_atual'];
        $this->assert("Saldo físico do produto incrementado corretamente (+{$qtdEntrada})", $saldoDepois === ($saldoAntes + $qtdEntrada));

        // Verifica registro em estoque_movimentacoes
        $mov = $this->pdo->query("SELECT * FROM estoque_movimentacoes WHERE produto_id = {$prodId} AND fornecedor = '{$fornecedorTeste}' ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $this->assert("Movimentação gravada em estoque_movimentacoes com fornecedor correto", !empty($mov));
        $this->assert("Tipo de movimentação é entrada_fornecedor", ($mov['tipo_movimentacao'] ?? '') === 'entrada_fornecedor');
        $this->assert("Quantidade gravada confere", (int)($mov['quantidade'] ?? 0) === $qtdEntrada);
        $this->assert("Justificativa contém o nome do fornecedor e observações", str_contains($mov['justificativa'] ?? '', $fornecedorTeste));

        // Verifica registro em auditoria
        $audit = $this->pdo->query("SELECT * FROM logs_auditoria WHERE acao = 'entrada_estoque_manual' AND registro_id = {$prodId} ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $this->assert("Auditoria técnica registrada em logs_auditoria", !empty($audit));
    }

    private function testarModeloDarEntradaLoteManual(): void
    {
        echo "\n2.1 Validando processamento atômico em lote (darEntradaLoteManual)...\n";

        $produtos = Produto::listarTodos();
        if (count($produtos) < 2) {
            $this->assert("Existem ao menos 2 produtos para teste de lote", false);
            return;
        }

        $p1 = $produtos[0];
        $p2 = $produtos[1];

        $saldoAntes1 = (int)$p1['estoque_atual'];
        $saldoAntes2 = (int)$p2['estoque_atual'];

        $userDb = $this->pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $operadorIdReal = (int)($userDb['id'] ?? 1);

        $fornecedorLote = 'Distribuidora Lote QA ' . date('His');
        $docLote = 'NF-LOTE-' . rand(1000, 9999);
        $obsLote = 'Lote multi-itens conferido';

        $itensLote = [
            ['produto_id' => (int)$p1['id'], 'quantidade' => 10, 'preco_custo' => 4.25],
            ['produto_id' => (int)$p2['id'], 'quantidade' => 20, 'preco_custo' => 6.50],
        ];

        $res = Estoque::darEntradaLoteManual($itensLote, $fornecedorLote, $docLote, $obsLote, $operadorIdReal);

        $this->assert("darEntradaLoteManual retornou array com resumo", is_array($res));
        $this->assert("Total de produtos no lote confere (2)", ($res['total_produtos'] ?? 0) === 2);
        $this->assert("Total de unidades no lote confere (30)", ($res['total_unidades'] ?? 0) === 30);

        // Verifica saldos
        $p1Depois = Produto::buscarPorId((int)$p1['id']);
        $p2Depois = Produto::buscarPorId((int)$p2['id']);

        $this->assert("Produto 1 teve saldo incrementado (+10)", (int)$p1Depois['estoque_atual'] === ($saldoAntes1 + 10));
        $this->assert("Produto 2 teve saldo incrementado (+20)", (int)$p2Depois['estoque_atual'] === ($saldoAntes2 + 20));

        // Rejeição de lote vazio
        $rejeitouVazio = false;
        try {
            Estoque::darEntradaLoteManual([], $fornecedorLote);
        } catch (\DomainException $de) {
            $rejeitouVazio = true;
        }
        $this->assert("darEntradaLoteManual rejeita array vazio de itens", $rejeitouVazio);
    }

    private function testarValidacoesEntrada(): void
    {
        echo "\n3. Validando regras de validação e exceptions...\n";

        // Produto inválido
        $erroProduto = false;
        try {
            Estoque::darEntradaManual(0, 10, 'Fornecedor X');
        } catch (\DomainException $e) {
            $erroProduto = true;
        }
        $this->assert("Rejeita produto_id <= 0 com DomainException", $erroProduto);

        // Quantidade zero ou negativa
        $erroQtd = false;
        try {
            Estoque::darEntradaManual(1, 0, 'Fornecedor X');
        } catch (\DomainException $e) {
            $erroQtd = true;
        }
        $this->assert("Rejeita quantidade <= 0 com DomainException", $erroQtd);

        // Fornecedor vazio
        $erroForn = false;
        try {
            Estoque::darEntradaManual(1, 10, ' ');
        } catch (\DomainException $e) {
            $erroForn = true;
        }
        $this->assert("Rejeita fornecedor vazio ou < 2 caracteres com DomainException", $erroForn);
    }

    private function testarListagemEntradasESugestoes(): void
    {
        echo "\n4. Validando listagem de entradas e sugestões de fornecedores...\n";

        $entradas = Estoque::listarEntradasRecentes(5);
        $this->assert("listarEntradasRecentes retorna array", is_array($entradas));
        $this->assert("listarEntradasRecentes contém registros", count($entradas) > 0);
        $primeira = $entradas[0];
        $this->assert("Entrada recente contém campo 'fornecedor'", array_key_exists('fornecedor', $primeira));
        $this->assert("Entrada recente contém 'produto_descricao'", array_key_exists('produto_descricao', $primeira));

        $sugestoes = Estoque::listarFornecedoresSugestao();
        $this->assert("listarFornecedoresSugestao retorna lista de nomes de fornecedores", is_array($sugestoes) && count($sugestoes) > 0);

        $mapa = Estoque::obterMapaProdutosFornecedores();
        $this->assert("obterMapaProdutosFornecedores retorna array de mapeamento", is_array($mapa));
    }

    private function testarControllerERotas(): void
    {
        echo "\n5. Validando métodos no Controller e Rotas no index.php...\n";

        $this->assert("EstoqueController possui método entrada()", method_exists(EstoqueController::class, 'entrada'));
        $this->assert("EstoqueController possui método entradaSalvar()", method_exists(EstoqueController::class, 'entradaSalvar'));

        $indexPhp = file_get_contents(__DIR__ . '/../index.php');
        $this->assert("index.php registra rota GET /estoque/entrada", str_contains($indexPhp, "'/estoque/entrada'"));
        $this->assert("index.php registra rota POST /estoque/entrada/salvar", str_contains($indexPhp, "'/estoque/entrada/salvar'"));
    }

    private function testarViewsESidebar(): void
    {
        echo "\n6. Validando integridade visual e templates...\n";

        $viewEntrada = __DIR__ . '/../app/views/estoque/entrada.php';
        $this->assert("Arquivo app/views/estoque/entrada.php existe", file_exists($viewEntrada));

        $conteudoView = file_get_contents($viewEntrada);
        $this->assert("View de entrada possui formulário com action /estoque/entrada/salvar", str_contains($conteudoView, '/estoque/entrada/salvar'));
        $this->assert("View de entrada possui input name='fornecedor' com datalist", str_contains($conteudoView, 'name="fornecedor"') && str_contains($conteudoView, '<datalist'));
        $this->assert("View de entrada possui input hidden itens_json", str_contains($conteudoView, 'name="itens_json"'));
        $this->assert("View de entrada possui botão no topo redirecionando para relatório de movimentações de estoque", str_contains($conteudoView, '/relatorios/estoque-filtros?tipo_movimentacao=entrada'));
        $this->assert("Botão de relatório redireciona na mesma aba sem target='_blank'", !str_contains($conteudoView, 'relatorios/estoque-filtros?tipo_movimentacao=entrada" target="_blank"'));
        $this->assert("View de entrada possui botão 'Carregar Produtos' no Bloco 1 de Dados Gerais", str_contains($conteudoView, 'id="btnCarregarFornTopo"'));
        $this->assert("View de entrada possui modal pop-up centralizado de adição", str_contains($conteudoView, 'id="modalAdicionarProduto"'));
        $this->assert("View de entrada possui grade de itens do lote (Nutre/Escolar Manager)", str_contains($conteudoView, 'id="tabelaLoteItens"'));
        $this->assert("View de entrada possui botão 'Gravar Entrada em Estoque'", str_contains($conteudoView, 'Gravar Entrada em Estoque'));
        $this->assert("Tabela antiga de 'Últimas Entradas Registradas' foi removida conforme solicitação", !str_contains($conteudoView, 'Últimas Entradas Registradas'));

        $sidebar = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
        $this->assert("Sidebar contém opção 'Entrada de Estoque'", str_contains($sidebar, 'Entrada de Estoque'));
        $this->assert("Sidebar aponta para /estoque/entrada", str_contains($sidebar, '/estoque/entrada'));

        $indexEstoque = file_get_contents(__DIR__ . '/../app/views/estoque/index.php');
        $this->assert("Tela de saldos /estoque não possui botão 'Entrada de Estoque' no cabeçalho (removido conforme solicitação)", !str_contains($indexEstoque, 'Entrada de Estoque'));
    }
}

$test = new EntradaEstoqueManualTest();
$test->run();
