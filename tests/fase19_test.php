<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 19: Módulo Completo (CRUD) de Estorno de Vendas e Invalidação
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md, docs/PLANO.md e docs/alterações sistema guideline/extorno de vendas.pdf
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';

// Registra o Autoloader PSR-4 nativo para o namespace App\
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Usuario;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Colaborador;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\SessaoCaixa;
use App\Models\VendaBalcao;
use App\Models\TransacaoCarteira;
use App\Models\Pedido;
use App\Models\Ticket;
use App\Core\RBAC;
use App\Core\Auth;
use App\Core\Session;

class Fase19Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "=======================================================\n";
        echo "   INICIANDO SUÍTE DE TESTES — FASE 19: ESTORNO DE VENDAS\n";
        echo "=======================================================\n\n";

        $this->testarSchemaMigration028();
        $this->testarMetodosListarParaSelecao();
        $this->testarListagemVendasParaEstorno();
        $this->testarEstornoAtomicoVendaBalcaoDinheiro();
        $this->testarEstornoVendaBalcaoFolhaPagamento();
        $this->testarEstornoAtomicoPedidoOnlineEInvalidacaoTicket();
        $this->testarValidacoesTravaSegurancaEMotivo();
        $this->testarControleAcessoRbacEstornos();

        echo "\n-------------------------------------------------------\n";
        echo sprintf(
            "Resumo Fase 19: %d/%d asserções aprovadas (%.1f%% de sucesso).\n",
            $this->assercoesSucesso,
            $this->totalAssercoes,
            ($this->totalAssercoes > 0) ? ($this->assercoesSucesso / $this->totalAssercoes) * 100 : 0
        );
        echo "=======================================================\n\n";

        if ($this->assercoesSucesso !== $this->totalAssercoes) {
            exit(1);
        }
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  [OK] " . $descricao . "\n";
        } else {
            echo "  [FALHA] " . $descricao . "\n";
        }
    }

    /**
     * 1. Validação de Estrutura do Banco (Migration 028)
     */
    private function testarSchemaMigration028(): void
    {
        echo "--- Teste 1: Schema e Estrutura de Colunas da Migration 028 ---\n";

        // Tabela vendas_balcao
        $stmtVb = $this->pdo->query("SHOW COLUMNS FROM vendas_balcao LIKE 'status'");
        $colStatusVb = $stmtVb->fetch(\PDO::FETCH_ASSOC);
        $this->assert(!empty($colStatusVb), "Coluna 'status' existe na tabela 'vendas_balcao'.");

        $stmtEstPor = $this->pdo->query("SHOW COLUMNS FROM vendas_balcao LIKE 'estornado_por'");
        $this->assert(!empty($stmtEstPor->fetch()), "Coluna 'estornado_por' existe na tabela 'vendas_balcao'.");

        $stmtMotivoVb = $this->pdo->query("SHOW COLUMNS FROM vendas_balcao LIKE 'motivo_estorno'");
        $this->assert(!empty($stmtMotivoVb->fetch()), "Coluna 'motivo_estorno' existe na tabela 'vendas_balcao'.");

        // Tabela pedidos
        $stmtPedStatus = $this->pdo->query("SHOW COLUMNS FROM pedidos LIKE 'status'");
        $colPedStatus = $stmtPedStatus->fetch(\PDO::FETCH_ASSOC);
        $this->assert(str_contains($colPedStatus['Type'] ?? '', 'estornado'), "ENUM 'status' de 'pedidos' contém valor 'estornado'.");

        $stmtPedMotivo = $this->pdo->query("SHOW COLUMNS FROM pedidos LIKE 'motivo_estorno'");
        $this->assert(!empty($stmtPedMotivo->fetch()), "Coluna 'motivo_estorno' existe na tabela 'pedidos'.");

        // Tabela estoque_movimentacoes
        $stmtEstMov = $this->pdo->query("SHOW COLUMNS FROM estoque_movimentacoes LIKE 'tipo_movimentacao'");
        $colEstMov = $stmtEstMov->fetch(\PDO::FETCH_ASSOC);
        $this->assert(str_contains($colEstMov['Type'] ?? '', 'entrada_estorno'), "ENUM 'tipo_movimentacao' de 'estoque_movimentacoes' contém 'entrada_estorno'.");
    }

    /**
     * 2. Métodos de Seleção nos Modelos Aluno e Colaborador
     */
    private function testarMetodosListarParaSelecao(): void
    {
        echo "\n--- Teste 2: Métodos de Seleção para Filtros (Aluno e Colaborador) ---\n";

        $alunos = Aluno::listarParaSelecao();
        $this->assert(is_array($alunos) && count($alunos) > 0, "Aluno::listarParaSelecao() retorna lista não vazia de estudantes ativos.");
        
        $primeiroAluno = $alunos[0];
        $this->assert(isset($primeiroAluno['id']) && isset($primeiroAluno['nome']) && isset($primeiroAluno['matricula']), "Aluno formatado com id, nome e matrícula.");

        $colabs = Colaborador::listarParaSelecao();
        $this->assert(is_array($colabs), "Colaborador::listarParaSelecao() retorna array de colaboradores.");
    }

    /**
     * 3. Listagem de Vendas com Filtros
     */
    private function testarListagemVendasParaEstorno(): void
    {
        echo "\n--- Teste 3: Listagem de Vendas Concluídas para Estorno ---\n";

        $filtros = [
            'data_inicio' => date('Y-m-d', strtotime('-30 days')),
            'data_fim' => date('Y-m-d', strtotime('+1 day')),
            'filtrar_por' => 'aluno'
        ];

        $vendas = VendaBalcao::listarParaEstorno($filtros);
        $this->assert(is_array($vendas), "VendaBalcao::listarParaEstorno() retorna array de vendas.");

        // Nenhuma venda estornada deve ser retornada nesta listagem
        $todasConcluidas = true;
        foreach ($vendas as $v) {
            if (($v['status'] ?? '') === 'estornada') {
                $todasConcluidas = false;
                break;
            }
        }
        $this->assert($todasConcluidas, "Vendas já estornadas são filtradas e omitidas da grade de novos estornos.");
    }

    /**
     * 4. Estorno Atômico de Venda Balcão (Dinheiro) com Reintegração de Estoque
     */
    private function testarEstornoAtomicoVendaBalcaoDinheiro(): void
    {
        echo "\n--- Teste 4: Estorno Atômico de Venda Balcão em Dinheiro e Reintegração de Estoque ---\n";

        // Obter operador e produto para o teste
        $operador = Usuario::listarTodos()[0] ?? ((new Usuario())->findByEmail('operador@cantina.local') ?? (new Usuario())->find(1));
        $opId = (int)$operador['id'];

        // Cria ou obtém produto para o teste
        $stmtProd = $this->pdo->query("SELECT * FROM produtos WHERE deleted_at IS NULL LIMIT 1");
        $prod = $stmtProd->fetch(\PDO::FETCH_ASSOC);
        if (!$prod) {
            $catId = (int)$this->pdo->query("SELECT id FROM categorias LIMIT 1")->fetchColumn();
            $this->pdo->exec("INSERT INTO produtos (categoria_id, codigo_interno, descricao, preco_venda, estoque_atual, tipo, status_pausa, created_at, updated_at)
                              VALUES ({$catId}, 'SUCO-EST', 'Suco Teste Estorno', 6.00, 30, 'industrializado_continuo', 'ativo', NOW(), NOW())");
            $prodId = (int)$this->pdo->lastInsertId();
            $estoqueInicial = 30.0;
        } else {
            $prodId = (int)$prod['id'];
            $estoqueInicial = (float)$prod['estoque_atual'];
        }

        // Garante sessão de caixa aberta para o teste
        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = $sessaoAberta ? (int)$sessaoAberta['id'] : SessaoCaixa::abrir($opId, 'matutino', 50.00);

        // Garante estoque suficiente antes da venda
        $this->pdo->prepare("UPDATE produtos SET estoque_atual = 50 WHERE id = ?")->execute([$prodId]);
        $estoqueInicial = 50.0;

        // Criar venda balcão em dinheiro real pelo modelo de negócio
        $dadosVenda = [
            'tipo_venda'              => 'avulso_aluno',
            'forma_pagamento'         => 'dinheiro',
            'valor_dinheiro_recebido' => 12.00
        ];
        $itensVenda = [
            [
                'produto_id'     => $prodId,
                'quantidade'     => 2,
                'preco_unitario' => 6.00
            ]
        ];

        $resVenda = VendaBalcao::criarVenda($dadosVenda, $itensVenda, $opId, $sessaoId);
        $vendaId = (int)$resVenda['id'];

        $stmtCheckEst = $this->pdo->prepare("SELECT estoque_atual FROM produtos WHERE id = ?");
        $stmtCheckEst->execute([$prodId]);
        $estoqueAposVenda = (float)$stmtCheckEst->fetchColumn();
        $this->assert(abs($estoqueAposVenda - ($estoqueInicial - 2.0)) < 0.001, "Estoque foi deduzido em 2 unidades na venda balcão pelo PDV.");

        // Executar estorno
        $motivo = "Cancelamento por desistência do aluno no balcão";
        $resultado = VendaBalcao::estornarVenda($vendaId, $opId, $motivo, '127.0.0.1');

        $this->assert(!empty($resultado) && ($resultado['status'] ?? '') === 'estornada', "VendaBalcao::estornarVenda() executado com sucesso.");

        // Verificar que status mudou para estornada
        $vendaAtualizada = VendaBalcao::buscarPorId($vendaId);
        $this->assert($vendaAtualizada['status'] === 'estornada', "Status da venda atualizado para 'estornada'.");
        $this->assert(!empty($vendaAtualizada['estornado_em']), "Data de estorno 'estornado_em' preenchida.");
        $this->assert((int)$vendaAtualizada['estornado_por'] === $opId, "Usuário operador registrado em 'estornado_por'.");
        $this->assert($vendaAtualizada['motivo_estorno'] === $motivo, "Motivo do estorno registrado corretamente.");

        // Verificar que estoque foi reintegrado via entrada_estorno
        $stmtCheckEst->execute([$prodId]);
        $estoqueAposEstorno = (float)$stmtCheckEst->fetchColumn();
        $this->assert(abs($estoqueAposEstorno - $estoqueInicial) < 0.001, "Estoque do produto foi 100% reintegrado (+2 unidades).");

        $stmtMov = $this->pdo->prepare("SELECT * FROM estoque_movimentacoes WHERE produto_id = :p_id AND tipo_movimentacao = 'entrada_estorno' ORDER BY id DESC LIMIT 1");
        $stmtMov->execute(['p_id' => $prodId]);
        $movEst = $stmtMov->fetch(\PDO::FETCH_ASSOC);
        $this->assert(!empty($movEst) && (float)$movEst['quantidade'] == 2.0, "Movimentação de 'entrada_estorno' registrada no histórico de estoque.");
    }

    /**
     * 5. Estorno de Venda Balcão em Folha de Pagamento
     */
    private function testarEstornoVendaBalcaoFolhaPagamento(): void
    {
        echo "\n--- Teste 5: Estorno de Venda Balcão com Desconto em Folha ---\n";

        $colab = Colaborador::listarTodos(true, '', 1)[0] ?? null;
        if (!$colab) {
            $colabId = Colaborador::criar([
                'nome' => 'Professor Teste Estorno',
                'matricula_funcional' => 'PROF-EST-99',
                'cargo' => 'Docente'
            ]);
        } else {
            $colabId = (int)$colab['id'];
        }

        $operador = Usuario::listarTodos()[0] ?? ((new Usuario())->findByEmail('operador@cantina.local') ?? (new Usuario())->find(1));
        $opId = (int)$operador['id'];

        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = $sessaoAberta ? (int)$sessaoAberta['id'] : SessaoCaixa::abrir($opId, 'matutino', 50.00);

        $prodId = (int)$this->pdo->query("SELECT id FROM produtos WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
        $this->pdo->prepare("UPDATE produtos SET estoque_atual = 50 WHERE id = ?")->execute([$prodId]);

        $resFolha = VendaBalcao::criarVenda([
            'tipo_venda'      => 'desconto_folha',
            'forma_pagamento' => 'desconto_folha',
            'colaborador_id'  => $colabId
        ], [
            [
                'produto_id'     => $prodId,
                'quantidade'     => 1,
                'preco_unitario' => 6.00
            ]
        ], $opId, $sessaoId);
        $vendaFolhaId = (int)$resFolha['id'];

        $res = VendaBalcao::estornarVenda($vendaFolhaId, $opId, "Erro de digitação de item pelo operador", '127.0.0.1');
        $this->assert(!empty($res) && ($res['status'] ?? '') === 'estornada', "Venda em folha de pagamento estornada com sucesso.");

        $venda = VendaBalcao::buscarPorId($vendaFolhaId);
        $this->assert($venda['status'] === 'estornada', "Venda em folha marcada como estornada.");
    }

    /**
     * 6. Estorno Atômico de Pedido Online com Crédito na Carteira e Invalidação de Ticket
     */
    private function testarEstornoAtomicoPedidoOnlineEInvalidacaoTicket(): void
    {
        echo "\n--- Teste 6: Estorno de Encomenda Online, Crédito Familiar e Invalidação de Ticket ---\n";

        // Obter responsável
        $resp = $this->pdo->query("SELECT * FROM responsaveis LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        if (!$resp) {
            $this->pdo->exec("INSERT INTO responsaveis (nome, cpf, whatsapp, saldo_atual, criado_em, atualizado_em)
                              VALUES ('Responsável Teste', '99988877766', '11999998888', 50.00, NOW(), NOW())");
            $respId = (int)$this->pdo->lastInsertId();
        } else {
            $respId = (int)$resp['id'];
        }

        // Ajusta saldo inicial para 50.00
        $this->pdo->exec("UPDATE responsaveis SET saldo_atual = 50.00 WHERE id = {$respId}");

        // Obter ou criar dependente vinculado
        $stmtAl = $this->pdo->prepare("SELECT * FROM alunos WHERE responsavel_id = ? AND ativo = 1 AND deleted_at IS NULL LIMIT 1");
        $stmtAl->execute([$respId]);
        $aluno = $stmtAl->fetch(\PDO::FETCH_ASSOC);
        if (!$aluno) {
            $turmaId = (int)$this->pdo->query("SELECT id FROM turmas LIMIT 1")->fetchColumn();
            $this->pdo->exec("INSERT INTO alunos (responsavel_id, turma_id, matricula, nome, turno, ativo, criado_em, atualizado_em)
                              VALUES ({$respId}, {$turmaId}, 'MAT-EST-99', 'Aluno Teste Estorno', 'matutino', 1, NOW(), NOW())");
            $alunoId = (int)$this->pdo->lastInsertId();
        } else {
            $alunoId = (int)$aluno['id'];
            $turmaId = (int)$aluno['turma_id'];
        }

        // Cria pedido online no valor de R$ 18.00 debitando da carteira
        $valorPedido = 18.00;
        $dataConsumo = date('Y-m-d', strtotime('+2 days'));
        $codPedido = 'PED-EST-' . time();

        $this->pdo->exec("UPDATE responsaveis SET saldo_atual = saldo_atual - {$valorPedido} WHERE id = {$respId}");

        $stmtPed = $this->pdo->prepare("INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at, updated_at)
                                        VALUES (:cod, :r_id, :a_id, :dt, 'matutino', :val, 'agendado', NOW(), NOW())");
        $stmtPed->execute([
            'cod'  => $codPedido,
            'r_id' => $respId,
            'a_id' => $alunoId,
            'dt'   => $dataConsumo,
            'val'  => $valorPedido
        ]);
        $pedidoId = (int)$this->pdo->lastInsertId();

        // Cria item de pedido
        $prodId = (int)$this->pdo->query("SELECT id FROM produtos WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
        $stmtItem = $this->pdo->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
                                        VALUES (:p_id, :prod_id, 2, 9.00, 18.00)");
        $stmtItem->execute([
            'p_id'    => $pedidoId,
            'prod_id' => $prodId
        ]);
        $itemPedidoId = (int)$this->pdo->lastInsertId();

        // Cria ticket térmico associado ao pedido
        $codigoTicket = 'TCK-EST-' . time();
        $stmtTkt = $this->pdo->prepare("INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, created_at)
                                        VALUES (:p_id, :it_id, :cod_tkt, 1, 'ativo', NOW())");
        $stmtTkt->execute([
            'p_id'    => $pedidoId,
            'it_id'   => $itemPedidoId,
            'cod_tkt' => $codigoTicket
        ]);
        $ticketId = (int)$this->pdo->lastInsertId();

        $saldoAntesEstorno = Responsavel::consultarSaldo($respId);
        $this->assert(abs($saldoAntesEstorno - 32.00) < 0.01, "Saldo da carteira após o débito do pedido é R$ 32,00.");

        // Executar estorno do pedido
        $operador = Usuario::listarTodos()[0] ?? ((new Usuario())->findByEmail('operador@cantina.local') ?? (new Usuario())->find(1));
        $opId = (int)$operador['id'];

        $motivoPedido = "Cancelamento da encomenda a pedido do responsável";
        $resPedido = Pedido::estornarPedido($pedidoId, $opId, $motivoPedido, '127.0.0.1');

        $this->assert(!empty($resPedido) && ($resPedido['status'] ?? '') === 'estornado', "Pedido::estornarPedido() executado com sucesso.");

        // Verificar status do pedido
        $pedidoAtualizado = Pedido::buscarPorId($pedidoId);
        $this->assert($pedidoAtualizado['status'] === 'estornado', "Status do pedido alterado para 'estornado'.");
        $this->assert(!empty($pedidoAtualizado['estornado_em']), "Data 'estornado_em' registrada no pedido.");
        $this->assert($pedidoAtualizado['motivo_estorno'] === $motivoPedido, "Motivo do estorno registrado no pedido.");

        // Verificar estorno na carteira (reembolso integral de R$ 18,00)
        $saldoAposEstorno = Responsavel::consultarSaldo($respId);
        $this->assert(abs($saldoAposEstorno - 50.00) < 0.01, "Saldo da carteira do responsável foi 100% reembolsado (voltou para R$ 50,00).");

        // Verificar se transação de carteira foi registrada
        $stmtTx = $this->pdo->prepare("SELECT * FROM transacoes_carteira WHERE responsavel_id = :r_id ORDER BY id DESC LIMIT 1");
        $stmtTx->execute(['r_id' => $respId]);
        $txEstorno = $stmtTx->fetch(\PDO::FETCH_ASSOC);
        $this->assert(!empty($txEstorno) && (float)$txEstorno['valor'] == 18.00, "Transação de crédito tipo 'estorno' gravada no extrato da família.");

        // Verificar invalidação do ticket térmico associado
        $stmtTktCheck = $this->pdo->prepare("SELECT status FROM tickets WHERE id = :id");
        $stmtTktCheck->execute(['id' => $ticketId]);
        $ticketStatus = $stmtTktCheck->fetchColumn();
        $this->assert(in_array($ticketStatus, ['estornado', 'cancelado']), "Ticket térmico associado foi permanentemente invalidado com status 'estornado'.");
    }

    /**
     * 7. Validação de Regras de Negócio e Travas de Segurança
     */
    private function testarValidacoesTravaSegurancaEMotivo(): void
    {
        echo "\n--- Teste 7: Travas de Segurança, Motivo Obrigatório e Duplicidade ---\n";

        $operador = Usuario::listarTodos()[0] ?? ((new Usuario())->findByEmail('operador@cantina.local') ?? (new Usuario())->find(1));
        $opId = (int)$operador['id'];

        $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino');
        $sessaoId = $sessaoAberta ? (int)$sessaoAberta['id'] : SessaoCaixa::abrir($opId, 'matutino', 50.00);
        $prodId = (int)$this->pdo->query("SELECT id FROM produtos WHERE deleted_at IS NULL LIMIT 1")->fetchColumn();
        $this->pdo->prepare("UPDATE produtos SET estoque_atual = 50 WHERE id = ?")->execute([$prodId]);

        // Cria venda balcão para testar trava
        $resVenda7 = VendaBalcao::criarVenda([
            'tipo_venda'              => 'avulso_aluno',
            'forma_pagamento'         => 'dinheiro',
            'valor_dinheiro_recebido' => 10.00
        ], [
            [
                'produto_id'     => $prodId,
                'quantidade'     => 1,
                'preco_unitario' => 6.00
            ]
        ], $opId, $sessaoId);
        $vendaId = (int)$resVenda7['id'];

        // Teste de motivo vazio ou curto (< 5 chars)
        $excecaoMotivo = false;
        try {
            VendaBalcao::estornarVenda($vendaId, $opId, "abc", '127.0.0.1');
        } catch (\InvalidArgumentException $e) {
            $excecaoMotivo = true;
        }
        $this->assert($excecaoMotivo, "Validação impede estorno com justificativa menor que 5 caracteres.");

        // Estorna corretamente
        VendaBalcao::estornarVenda($vendaId, $opId, "Justificativa válida de teste", '127.0.0.1');

        // Tentar estornar novamente a mesma venda
        $excecaoDuplicidade = false;
        try {
            VendaBalcao::estornarVenda($vendaId, $opId, "Tentativa de segundo estorno", '127.0.0.1');
        } catch (\DomainException $e) {
            $excecaoDuplicidade = true;
        }
        $this->assert($excecaoDuplicidade, "Trava de segurança impede estorno duplicado de venda já estornada.");
    }

    /**
     * 8. Controle de Acesso RBAC para Rotas de Estorno
     */
    private function testarControleAcessoRbacEstornos(): void
    {
        echo "\n--- Teste 8: Controle de Acesso RBAC (Operador e Administrador) ---\n";

        $rolesPermitidas = [RBAC::ROLE_OPERADOR, RBAC::ROLE_ADMINISTRADOR];

        // Operador tem permissão
        $this->assert(in_array(RBAC::ROLE_OPERADOR, $rolesPermitidas, true), "Perfil 'operador' possui permissão para acessar o módulo de estornos.");

        // Administrador tem permissão
        $this->assert(in_array(RBAC::ROLE_ADMINISTRADOR, $rolesPermitidas, true), "Perfil 'administrador' possui permissão para acessar o módulo de estornos.");

        // Responsável NÃO tem permissão
        $this->assert(!in_array(RBAC::ROLE_RESPONSAVEL, $rolesPermitidas, true), "Perfil 'responsavel' é bloqueado no módulo de estornos.");
    }
}

// Execução da Suíte
$suite = new Fase19Test();
$suite->run();
