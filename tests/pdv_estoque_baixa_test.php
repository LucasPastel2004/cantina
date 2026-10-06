<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste: Verificação de Baixa Física de Estoque no PDV Balcão e Exibição de Itens Esgotados
 */

namespace Tests;

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/ItemVendaBalcao.php';
require_once __DIR__ . '/../app/models/VendaBalcao.php';
require_once __DIR__ . '/../app/models/SessaoCaixa.php';
require_once __DIR__ . '/../app/models/Usuario.php';

use App\Models\Produto;
use App\Models\VendaBalcao;
use App\Models\SessaoCaixa;

class PdvEstoqueBaixaTest
{
    private \PDO $pdo;
    private int $totalAssercoes = 0;
    private int $assercoesAprovadas = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " TESTE: BAIXA FÍSICA DE ESTOQUE NO PDV BALCÃO E EXIBIÇÃO DE ESGOTADOS\n";
        echo "======================================================================\n\n";

        // Garante sessão de caixa aberta para os testes
        $sessao = SessaoCaixa::obterSessaoAberta('matutino');
        if (!$sessao) {
            $operadorStmt = $this->pdo->query("SELECT id FROM usuarios WHERE perfil IN ('operador', 'administrador') AND deleted_at IS NULL LIMIT 1");
            $opId = (int)$operadorStmt->fetchColumn();
            $sessaoId = SessaoCaixa::abrir($opId, 'matutino', 50.00, 'Caixa teste estoque');
        } else {
            $sessaoId = (int)$sessao['id'];
        }

        $opStmt = $this->pdo->query("SELECT id FROM usuarios WHERE perfil IN ('operador', 'administrador') AND deleted_at IS NULL LIMIT 1");
        $operadorId = (int)$opStmt->fetchColumn();

        // 1. Cadastra um produto para o teste
        $catStmt = $this->pdo->query("SELECT id FROM categorias WHERE ativo = 1 AND deleted_at IS NULL LIMIT 1");
        $catId = (int)$catStmt->fetchColumn();

        $codTeste = 'TEST-ESTOQUE-' . bin2hex(random_bytes(4));
        $prodId = Produto::criar([
            'categoria_id'          => $catId,
            'codigo_interno'        => $codTeste,
            'descricao'             => 'Pão de Queijo Teste Baixa',
            'preco_venda'           => 6.50,
            'tipo'                  => 'industrializado_continuo',
            'turno_disponibilidade' => 'ambos',
            'estoque_atual'         => 18,
            'estoque_minimo'        => 2,
            'status_pausa'          => 'ativo'
        ]);

        $this->assert($prodId > 0, "Produto de teste criado com ID #{$prodId} e estoque inicial de 18 un");

        // 2. Realiza venda de 18 unidades (zerando o estoque)
        $venda = VendaBalcao::criarVenda([
            'tipo_venda'              => 'avulso_aluno',
            'forma_pagamento'         => 'dinheiro',
            'valor_dinheiro_recebido' => 120.00
        ], [
            ['produto_id' => $prodId, 'quantidade' => 18, 'preco_unitario' => 6.50]
        ], $operadorId, $sessaoId);

        $this->assert($venda['id'] > 0, "Venda #{$venda['id']} realizada com sucesso");

        // 3. Verifica que o estoque físico foi a ZERO no banco de dados
        $prodApos = Produto::buscarPorId($prodId);
        $this->assert((int)$prodApos['estoque_atual'] === 0, "Estoque físico do produto baixado com sucesso de 18 para 0 unidades");

        // 4. Verifica registro de movimentação rastreável em estoque_movimentacoes
        $stmtMov = $this->pdo->prepare("SELECT * FROM estoque_movimentacoes WHERE produto_id = :prod_id AND tipo_movimentacao = 'saida_venda_avulsa' ORDER BY id DESC LIMIT 1");
        $stmtMov->execute(['prod_id' => $prodId]);
        $mov = $stmtMov->fetch(\PDO::FETCH_ASSOC);
        $this->assert($mov !== false, "Movimentação rastreável gerada em 'estoque_movimentacoes'");
        $this->assert((int)$mov['quantidade'] === 18, "Quantidade baixada na movimentação confere (18 un)");

        // 5. Verifica que Produto::listarParaPdv identifica o produto como esgotado
        $listaPdv = Produto::listarParaPdv();
        $prodNoPdv = null;
        foreach ($listaPdv as $p) {
            if ((int)$p['id'] === $prodId) {
                $prodNoPdv = $p;
                break;
            }
        }
        $this->assert($prodNoPdv !== null, "Produto zerado permanece listado no PDV para visualização operacional");
        $this->assert($prodNoPdv['esgotado'] === true, "Produto com estoque 0 possui flag esgotado = true");
        $this->assert((int)$prodNoPdv['estoque_atual'] === 0, "Estoque retornado para o PDV é exatamente 0");

        // 6. Verifica tentativa de vender mais que o estoque (deve ser bloqueada)
        $bloqueouSemEstoque = false;
        try {
            VendaBalcao::criarVenda([
                'tipo_venda'              => 'avulso_aluno',
                'forma_pagamento'         => 'dinheiro',
                'valor_dinheiro_recebido' => 10.00
            ], [
                ['produto_id' => $prodId, 'quantidade' => 1, 'preco_unitario' => 6.50]
            ], $operadorId, $sessaoId);
        } catch (\DomainException $e) {
            $bloqueouSemEstoque = true;
        }
        $this->assert($bloqueouSemEstoque, "Tentativa de vender produto com estoque zerado é bloqueada no motor de vendas");

        // 7. Verifica template pdv.php
        $viewContent = file_get_contents(__DIR__ . '/../app/views/operacional/pdv.php');
        $this->assert(str_contains($viewContent, '0 un (Esgotado)'), "View pdv.php exibe explicitamente '0 un (Esgotado)' para itens com estoque zerado");
        $this->assert(str_contains($viewContent, 'Math.max(0, estoqueAtual - it.quantidade)'), "JavaScript do PDV atualiza o estoque dos cards no DOM em tempo real após a venda");

        // 8. Teardown: limpa a venda e inativa produtos de teste
        if (isset($venda['id'])) {
            $this->pdo->exec("DELETE FROM itens_venda_balcao WHERE venda_balcao_id = " . (int)$venda['id']);
            $this->pdo->exec("DELETE FROM vendas_balcao WHERE id = " . (int)$venda['id']);
        }
        $this->pdo->exec("DELETE FROM estoque_movimentacoes WHERE produto_id = {$prodId}");
        $this->pdo->exec("UPDATE produtos SET deleted_at = NOW() WHERE (codigo_interno LIKE 'TEST-ESTOQUE-%' OR id = {$prodId}) AND deleted_at IS NULL");

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->assercoesAprovadas}/{$this->totalAssercoes} asserções aprovadas.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->assercoesAprovadas !== $this->totalAssercoes) {
            exit(1);
        }
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->totalAssercoes++;
        if ($cond) {
            $this->assercoesAprovadas++;
            echo " [PASS] {$msg}\n";
        } else {
            echo " [FAIL] {$msg}\n";
        }
    }
}

$t = new PdvEstoqueBaixaTest();
$t->run();
