<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Regras das Novas Formas de Pagamento por Aba no PDV e Remoção de "Em Folha"
 * 
 * Conforme solicitação:
 * - Remoção de 'desconto_folha' do sistema
 * - Aba Anônimo: Dinheiro e Pix no balcão
 * - Aba Aluno: Dinheiro, Pix no balcão e Saldo (com débito/estorno em carteira)
 * - Aba Colaborador: Dinheiro e Pix no balcão (avulso_colaborador)
 */

namespace Tests;

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\VendaBalcao;
use App\Models\TransacaoCarteira;
use App\Models\SessaoCaixa;
use App\Models\Aluno;
use App\Models\Colaborador;
use App\Models\Produto;

class PdvFormasPagamentoTest
{
    private \PDO $pdo;
    private int $total = 0;
    private int $aprovados = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO TESTES: REGRAS DE FORMAS DE PAGAMENTO NO PDV (SEM FOLHA)\n";
        echo "======================================================================\n\n";

        $this->testarIntegridadeHtmlPdvView();
        $this->testarRejeicaoDescontoFolha();
        $this->testarVendaAnonimo();
        $this->testarVendaColaborador();
        $this->testarVendaAlunoSaldoEDebitoCarteira();
        $this->testarEstornoVendaSaldo();

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->aprovados}/{$this->total} testes aprovados.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->aprovados !== $this->total) {
            exit(1);
        }
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->aprovados++;
            echo " [PASS] {$msg}\n";
        } else {
            echo " [FAIL] {$msg}\n";
        }
    }

    private function testarIntegridadeHtmlPdvView(): void
    {
        echo "--- [1] Verificação Visual e Estrutural da View pdv.php ---\n";
        $viewContent = file_get_contents(__DIR__ . '/../app/views/operacional/pdv.php');

        $this->assert(strpos($viewContent, 'btnPayFolha') === false, "Botão 'btnPayFolha' foi removido da view");
        $this->assert(strpos($viewContent, 'chkConfirmaVisual') === false, "Checkbox 'chkConfirmaVisual' foi removido do card colaborador");
        $this->assert(strpos($viewContent, 'btnPaySaldo') !== false, "Botão 'btnPaySaldo' existe na view");
        $this->assert(strpos($viewContent, 'payDetailsSaldo') !== false, "Container de detalhes de saldo 'payDetailsSaldo' existe");
        $this->assert(strpos($viewContent, 'alunoSelSaldo') !== false, "Elemento 'alunoSelSaldo' presente no card do aluno");
        $this->assert(strpos($viewContent, "total_saldo") !== false, "Stat-strip exibe métrica de total_saldo");
        $this->assert(strpos($viewContent, ".pdv-pay-methods.has-saldo") !== false, "CSS possui suporte a 3 colunas via .has-saldo");
    }

    private function testarRejeicaoDescontoFolha(): void
    {
        echo "\n--- [2] Rejeição Terminante de Pagamento 'desconto_folha' ---\n";

        // Obter ou criar sessão de caixa ativa
        $sessaoId = $this->obterOuCriarSessaoCaixa();
        $operadorId = $this->obterOperadorTeste();
        $produto = $this->obterProdutoTeste();

        $itens = [
            [
                'produto_id' => $produto['id'],
                'quantidade' => 1,
                'preco_unitario' => $produto['preco']
            ]
        ];

        $rejeitou = false;
        try {
            VendaBalcao::criarVenda([
                'tipo_venda'      => 'desconto_folha',
                'forma_pagamento' => 'desconto_folha',
                'colaborador_id'  => 1
            ], $itens, $operadorId, $sessaoId);
        } catch (\DomainException $e) {
            $rejeitou = true;
        } catch (\Exception $e) {
            $rejeitou = true;
        }

        $this->assert($rejeitou, "VendaBalcao::criarVenda rejeitou com sucesso forma de pagamento 'desconto_folha'");
    }

    private function testarVendaAnonimo(): void
    {
        echo "\n--- [3] Regras da Aba Venda Avulsa (Dinheiro e Pix Balcão) ---\n";

        $sessaoId = $this->obterOuCriarSessaoCaixa();
        $operadorId = $this->obterOperadorTeste();
        $produto = $this->obterProdutoTeste();

        $itens = [
            [
                'produto_id' => $produto['id'],
                'quantidade' => 1,
                'preco_unitario' => $produto['preco']
            ]
        ];

        // 1. Dinheiro Anônimo
        $vendaDinheiro = VendaBalcao::criarVenda([
            'tipo_venda'             => VendaBalcao::TIPO_AVULSO_ALUNO,
            'forma_pagamento'        => VendaBalcao::FORMA_DINHEIRO,
            'valor_dinheiro_recebido'=> $produto['preco'] + 5.0
        ], $itens, $operadorId, $sessaoId);
        $this->assert($vendaDinheiro['id'] > 0, "Venda anônima em dinheiro concluída com sucesso (#{$vendaDinheiro['id']})");
        $this->assert((float)$vendaDinheiro['troco_devolvido'] == 5.0, "Troco calculado corretamente (R$ 5,00)");

        // 2. Pix Balcão Anônimo
        $vendaPix = VendaBalcao::criarVenda([
            'tipo_venda'             => VendaBalcao::TIPO_AVULSO_ALUNO,
            'forma_pagamento'        => VendaBalcao::FORMA_PIX_BALCAO
        ], $itens, $operadorId, $sessaoId);
        $this->assert($vendaPix['id'] > 0, "Venda anônima em Pix Balcão concluída com sucesso (#{$vendaPix['id']})");

        // 3. Rejeição de Saldo sem aluno
        $rejeitouSaldo = false;
        try {
            VendaBalcao::criarVenda([
                'tipo_venda'      => VendaBalcao::TIPO_AVULSO_ALUNO,
                'forma_pagamento' => VendaBalcao::FORMA_SALDO,
                'aluno_id'        => null
            ], $itens, $operadorId, $sessaoId);
        } catch (\InvalidArgumentException|\DomainException $e) {
            $rejeitouSaldo = true;
        }
        $this->assert($rejeitouSaldo, "Venda com 'saldo' sem aluno selecionado foi rejeitada");
    }

    private function testarVendaColaborador(): void
    {
        echo "\n--- [4] Regras da Aba Colaborador (Dinheiro e Pix Balcão, avulso_colaborador) ---\n";

        $sessaoId = $this->obterOuCriarSessaoCaixa();
        $operadorId = $this->obterOperadorTeste();
        $produto = $this->obterProdutoTeste();
        $colaborador = $this->obterColaboradorTeste();

        $itens = [
            [
                'produto_id' => $produto['id'],
                'quantidade' => 1,
                'preco_unitario' => $produto['preco']
            ]
        ];

        // 1. Dinheiro Colaborador
        $vendaColabDinheiro = VendaBalcao::criarVenda([
            'tipo_venda'             => VendaBalcao::TIPO_AVULSO_COLABORADOR,
            'colaborador_id'         => $colaborador['id'],
            'forma_pagamento'        => VendaBalcao::FORMA_DINHEIRO,
            'valor_dinheiro_recebido'=> $produto['preco']
        ], $itens, $operadorId, $sessaoId);
        $this->assert($vendaColabDinheiro['id'] > 0, "Venda para colaborador em dinheiro criada com sucesso");
        $this->assert($vendaColabDinheiro['tipo_venda'] === VendaBalcao::TIPO_AVULSO_COLABORADOR, "Venda gravada com tipo 'avulso_colaborador'");

        // 2. Pix Balcão Colaborador
        $vendaColabPix = VendaBalcao::criarVenda([
            'tipo_venda'             => VendaBalcao::TIPO_AVULSO_COLABORADOR,
            'colaborador_id'         => $colaborador['id'],
            'forma_pagamento'        => VendaBalcao::FORMA_PIX_BALCAO
        ], $itens, $operadorId, $sessaoId);
        $this->assert($vendaColabPix['id'] > 0, "Venda para colaborador em Pix Balcão criada com sucesso");
        $this->assert($vendaColabPix['tipo_venda'] === VendaBalcao::TIPO_AVULSO_COLABORADOR, "Venda Pix do colaborador gravada com 'avulso_colaborador'");
    }

    private function testarVendaAlunoSaldoEDebitoCarteira(): void
    {
        echo "\n--- [5] Regras da Aba Aluno (Pagamento com Saldo da Carteira Familiar) ---\n";

        $sessaoId = $this->obterOuCriarSessaoCaixa();
        $operadorId = $this->obterOperadorTeste();
        $produto = $this->obterProdutoTeste();
        $aluno = $this->obterAlunoTesteComSaldo(50.0);

        $preco = (float)$produto['preco'];
        $saldoInicial = $this->obterSaldoAluno((int)$aluno['id']);

        $itens = [
            [
                'produto_id' => $produto['id'],
                'quantidade' => 1,
                'preco_unitario' => $preco
            ]
        ];

        // 1. Venda em Saldo com saldo suficiente
        $vendaSaldo = VendaBalcao::criarVenda([
            'tipo_venda'      => VendaBalcao::TIPO_AVULSO_ALUNO,
            'forma_pagamento' => VendaBalcao::FORMA_SALDO,
            'aluno_id'        => (int)$aluno['id']
        ], $itens, $operadorId, $sessaoId);

        $this->assert($vendaSaldo['id'] > 0, "Venda com forma 'saldo' criada com sucesso (#{$vendaSaldo['id']})");
        $this->assert($vendaSaldo['forma_pagamento'] === VendaBalcao::FORMA_SALDO, "Venda salva com forma_pagamento 'saldo'");

        $saldoAposVenda = $this->obterSaldoAluno((int)$aluno['id']);
        $diferenca = round($saldoInicial - $saldoAposVenda, 2);
        $this->assert($diferenca == round($preco, 2), "Saldo debitado atomicamente da carteira familiar (esperado: -R$ {$preco}, debitado: -R$ {$diferenca})");

        // Verificar registro em transacoes_carteira com tipo debito_balcao
        $stmtTrans = $this->pdo->prepare("
            SELECT * FROM transacoes_carteira 
            WHERE responsavel_id = ? AND tipo_transacao = 'debito_balcao'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtTrans->execute([(int)$aluno['responsavel_id']]);
        $transacao = $stmtTrans->fetch(\PDO::FETCH_ASSOC);
        $this->assert(!empty($transacao), "Registro em 'transacoes_carteira' com tipo 'debito_balcao' criado");
        $this->assert((float)$transacao['valor'] == round($preco, 2), "Valor da transação na carteira corresponde ao total da venda");

        // 2. Venda em Saldo quando o aluno não tem saldo suficiente
        $rejeitouInsuficiente = false;
        try {
            // Tentar comprar quantidade absurda
            $itensCaros = [
                [
                    'produto_id' => $produto['id'],
                    'quantidade' => 500,
                    'preco_unitario' => 100.0
                ]
            ];
            VendaBalcao::criarVenda([
                'tipo_venda'      => VendaBalcao::TIPO_AVULSO_ALUNO,
                'forma_pagamento' => VendaBalcao::FORMA_SALDO,
                'aluno_id'        => (int)$aluno['id']
            ], $itensCaros, $operadorId, $sessaoId);
        } catch (\DomainException $e) {
            $rejeitouInsuficiente = true;
        }
        $this->assert($rejeitouInsuficiente, "Tentativa de compra com saldo insuficiente foi rejeitada com DomainException");
    }

    private function testarEstornoVendaSaldo(): void
    {
        echo "\n--- [6] Estorno de Venda Realizada em Saldo (Devolução na Carteira) ---\n";

        $sessaoId = $this->obterOuCriarSessaoCaixa();
        $operadorId = $this->obterOperadorTeste();
        $produto = $this->obterProdutoTeste();
        $aluno = $this->obterAlunoTesteComSaldo(50.0);

        $preco = (float)$produto['preco'];
        $itens = [
            [
                'produto_id' => $produto['id'],
                'quantidade' => 1,
                'preco_unitario' => $preco
            ]
        ];

        // Criar a venda em saldo
        $venda = VendaBalcao::criarVenda([
            'tipo_venda'      => VendaBalcao::TIPO_AVULSO_ALUNO,
            'forma_pagamento' => VendaBalcao::FORMA_SALDO,
            'aluno_id'        => (int)$aluno['id']
        ], $itens, $operadorId, $sessaoId);

        $saldoAntesEstorno = $this->obterSaldoAluno((int)$aluno['id']);

        // Estornar a venda
        $sucessoEstorno = VendaBalcao::estornarVenda(
            (int)$venda['id'],
            $operadorId,
            'Erro de registro de teste operacional',
            '127.0.0.1'
        );

        $this->assert(!empty($sucessoEstorno) && ($sucessoEstorno['status'] ?? '') === 'estornada', "VendaBalcao::estornarVenda executado com sucesso e status 'estornada'");

        $saldoAposEstorno = $this->obterSaldoAluno((int)$aluno['id']);
        $diferencaEstorno = round($saldoAposEstorno - $saldoAntesEstorno, 2);
        $this->assert($diferencaEstorno == round($preco, 2), "Valor estornado creditado 100% de volta na carteira familiar (+R$ {$diferencaEstorno})");

        // Verificar registro de estorno_balcao na carteira
        $stmtEst = $this->pdo->prepare("
            SELECT * FROM transacoes_carteira 
            WHERE responsavel_id = ? AND tipo_transacao = 'estorno_balcao'
            ORDER BY id DESC LIMIT 1
        ");
        $stmtEst->execute([(int)$aluno['responsavel_id']]);
        $transacaoEstorno = $stmtEst->fetch(\PDO::FETCH_ASSOC);
        $this->assert(!empty($transacaoEstorno), "Transação com tipo 'estorno_balcao' registrada na carteira familiar");
    }

    private function obterSaldoAluno(int $alunoId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT r.saldo_atual 
            FROM alunos a 
            JOIN responsaveis r ON r.id = a.responsavel_id 
            WHERE a.id = ?
        ");
        $stmt->execute([$alunoId]);
        return (float)$stmt->fetchColumn();
    }

    private function obterOperadorTeste(): int
    {
        $id = $this->pdo->query("SELECT id FROM usuarios WHERE ativo = 1 AND deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetchColumn();
        if (!$id) {
            $id = $this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        }
        return (int)$id;
    }

    private function obterOuCriarSessaoCaixa(): int
    {
        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino') ?? SessaoCaixa::obterSessaoAberta('vespertino');
        if ($sessaoAberta) {
            return (int)$sessaoAberta['id'];
        }
        $operadorId = $this->obterOperadorTeste();
        return SessaoCaixa::abrir($operadorId, 'matutino', 100.0, 'Sessão aberta para teste automatizado');
    }

    private function obterProdutoTeste(): array
    {
        $stmt = $this->pdo->query("SELECT id, preco_venda, descricao FROM produtos WHERE status_pausa = 'ativo' AND deleted_at IS NULL AND estoque_atual > 5 LIMIT 1");
        $prod = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($prod) {
            return [
                'id' => (int)$prod['id'],
                'preco' => (float)$prod['preco_venda'],
                'descricao' => $prod['descricao']
            ];
        }

        // Criar produto se não houver
        $this->pdo->exec("
            INSERT INTO produtos (categoria_id, codigo_interno, descricao, preco_venda, tipo, turno_disponibilidade, estoque_atual, estoque_minimo, status_pausa)
            VALUES (1, 'TESTE-PDV', 'Lanche Teste PDV', 7.50, 'salgado_diario', 'ambos', 50, 5, 'ativo')
        ");
        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'preco' => 7.50,
            'descricao' => 'Lanche Teste PDV'
        ];
    }

    private function obterColaboradorTeste(): array
    {
        $stmt = $this->pdo->query("SELECT id, nome, matricula_funcional FROM colaboradores WHERE ativo = 1 LIMIT 1");
        $colab = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($colab) {
            return $colab;
        }

        $this->pdo->exec("
            INSERT INTO colaboradores (nome, matricula_funcional, cargo, ativo)
            VALUES ('Colaborador Teste', 'COLAB-TESTE-99', 'Professor', 1)
        ");
        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'nome' => 'Colaborador Teste',
            'matricula_funcional' => 'COLAB-TESTE-99'
        ];
    }

    private function obterAlunoTesteComSaldo(float $valorGarantido): array
    {
        $stmt = $this->pdo->query("
            SELECT a.id, a.nome, a.responsavel_id, r.saldo_atual 
            FROM alunos a
            JOIN responsaveis r ON r.id = a.responsavel_id
            WHERE a.ativo = 1
            LIMIT 1
        ");
        $aluno = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($aluno) {
            if ((float)$aluno['saldo_atual'] < $valorGarantido) {
                TransacaoCarteira::creditar(
                    (int)$aluno['responsavel_id'],
                    $valorGarantido,
                    TransacaoCarteira::TIPO_RECARGA_PIX,
                    'Garantia de saldo para teste automatizado',
                    null,
                    'TESTE-SALDO'
                );
            }
            return $aluno;
        }

        // Caso excepcional de banco limpo
        $this->pdo->exec("
            INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual)
            VALUES ('11122233344', 'Responsavel Teste', 'resp.teste@escola.com', '11999998888', 'hash', 100.00)
        ");
        $respId = (int)$this->pdo->lastInsertId();

        $this->pdo->exec("
            INSERT INTO alunos (responsavel_id, turma_id, nome, matricula, ativo)
            VALUES ($respId, 1, 'Aluno Teste PDV', 'ALU-PDV-99', 1)
        ");
        $alunoId = (int)$this->pdo->lastInsertId();

        return [
            'id' => $alunoId,
            'nome' => 'Aluno Teste PDV',
            'responsavel_id' => $respId,
            'saldo_atual' => 100.00
        ];
    }
}

$test = new PdvFormasPagamentoTest();
$test->run();
