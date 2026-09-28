<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Função de Salgados Fracionados de Salgado Grande
 * 
 * Validação rigorosa da regra de negócio:
 * 1 Salgado Grande = 2 Salgados Pequenos (15 pequenos = 7 grandes e 1 pequeno para o fornecedor).
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Ticket.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';

use App\Core\Model;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Responsavel;
use App\Models\Turma;
use App\Models\Aluno;
use App\Models\TransacaoCarteira;
use App\Models\CalendarioEscolar;
use App\Models\Configuracao;
use App\Models\Pedido;
use App\Services\PdfService;
use App\Services\WhatsAppService;

class SalgadosFracionadosTest
{
    private \PDO $pdo;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private array $produtosCriados = [];
    private array $pedidosCriados = [];
    private ?int $responsavelId = null;
    private ?int $alunoId = null;

    public function __construct()
    {
        $this->pdo = Model::getPdo();
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passedTests++;
            echo "  [PASS] {$message}\n";
        } else {
            $this->failedTests++;
            echo "  [FAIL] {$message}\n";
        }
    }

    public function runAll(): void
    {
        echo "======================================================\n";
        echo "   Iniciando Testes: Salgados Fracionados (1 Grande = 2 Pequenos)\n";
        echo "======================================================\n\n";

        try {
            $this->testarColunaBanco();
            $this->testarCrudProdutoFracionado();
            $this->testarCenario15PequenosViram7Grandes1Pequeno();
            $this->testarCenarioPar14PequenosViram7Grandes();
            $this->testarCenario1Pequeno();
            $this->testarMensagemWhatsApp();
            $this->testarGeracaoPdfEHtml();
            $this->testarRecebimentoFornecedorFracionado();
        } finally {
            $this->limparDadosTeste();
        }

        echo "\n======================================================\n";
        echo "   Resultado Final dos Testes:\n";
        echo "   Passou: {$this->passedTests} | Falhou: {$this->failedTests}\n";
        echo "======================================================\n";

        if ($this->failedTests > 0) {
            exit(1);
        }
    }

    /**
     * Teste 1: Verifica existência da coluna fracionado_salgado_grande no MySQL
     */
    private function testarColunaBanco(): void
    {
        echo "1. Validando estrutura da tabela produtos no MySQL...\n";
        $stmt = $this->pdo->query("SHOW COLUMNS FROM produtos LIKE 'fracionado_salgado_grande'");
        $col = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assert(!empty($col), "Coluna fracionado_salgado_grande existe na tabela produtos");
        $this->assert(str_contains(strtolower($col['Type'] ?? ''), 'tinyint'), "Tipo da coluna é TINYINT");
        $this->assert(($col['Default'] ?? null) === '0', "Valor padrão da coluna é 0");
    }

    /**
     * Teste 2: Criação e atualização de produto com a checkbox de fracionado
     */
    private function testarCrudProdutoFracionado(): void
    {
        echo "\n2. Validando CRUD de produto com fracionamento ativo...\n";

        $cod = 'TEST-FRAC-' . strtoupper(substr(uniqid(), -6));
        $dados = [
            'categoria_id'              => 1, // Salgados Assados
            'codigo_interno'            => $cod,
            'descricao'                 => 'Esfiha de Frango Pequena Teste',
            'preco_venda'               => 4.50,
            'tipo'                      => 'salgado_diario',
            'fracionado_salgado_grande' => 1,
            'turno_disponibilidade'     => 'ambos',
            'estoque_atual'             => 0,
            'estoque_minimo'            => 5,
            'status_pausa'              => 'ativo'
        ];

        $prodId = Produto::criar($dados, [], 1);
        $this->produtosCriados[] = $prodId;

        $p = Produto::buscarPorId($prodId);
        $this->assert($p !== null, "Produto criado com ID #{$prodId}");
        $this->assert((int)($p['fracionado_salgado_grande'] ?? 0) === 1, "Campo fracionado_salgado_grande gravado como 1");

        // Atualização para 0
        $dados['fracionado_salgado_grande'] = 0;
        Produto::atualizar($prodId, $dados, [], 1);
        $p2 = Produto::buscarPorId($prodId);
        $this->assert((int)($p2['fracionado_salgado_grande'] ?? 0) === 0, "Campo fracionado_salgado_grande desativado para 0");

        // Reativa para 1
        $dados['fracionado_salgado_grande'] = 1;
        Produto::atualizar($prodId, $dados, [], 1);
        $p3 = Produto::buscarPorId($prodId);
        $this->assert((int)($p3['fracionado_salgado_grande'] ?? 0) === 1, "Campo fracionado_salgado_grande reativado como 1");
    }

    /**
     * Configura dependências mínimas para pedidos
     */
    private function prepararContaTeste(): void
    {
        if ($this->responsavelId !== null) {
            return;
        }

        $cpfUnico = sprintf('777%06d00', random_int(100000, 999999));
        $resp = Responsavel::localizarOuCriarPorCpf([
            'cpf'      => $cpfUnico,
            'nome'     => 'Responsavel Fracionado Teste',
            'whatsapp' => '11988887777'
        ]);
        $this->responsavelId = (int)$resp['id'];

        TransacaoCarteira::creditar(
            $this->responsavelId,
            5000.00,
            TransacaoCarteira::TIPO_RECARGA_PIX,
            'Saldo testes fracionados'
        );

        $turma = Turma::localizarOuCriar('7º Ano Teste Frac', '7º Ano', 'matutino');
        $turmaId = (int)$turma['id'];

        $mat = 'MAT-FRAC-' . random_int(10000, 99999);
        $this->alunoId = Aluno::criar([
            'responsavel_id' => $this->responsavelId,
            'turma_id'       => $turmaId,
            'matricula'      => $mat,
            'nome'           => 'Aluno Fracionado Teste',
            'turno'          => 'matutino',
            'ativo'          => 1,
        ]);
    }

    /**
     * Teste 3: Cenário do Usuário — 15 Salgados Pequenos pedidos
     * Deve consolidar como 7 Salgados Grandes e 1 Pequeno para o fornecedor.
     */
    private function testarCenario15PequenosViram7Grandes1Pequeno(): void
    {
        echo "\n3. Validando Cenário do Usuário: 15 pequenos pedidos -> 7 grandes e 1 pequeno...\n";
        $this->prepararContaTeste();

        $dataConsumo = '2026-11-20';
        $turnoConsumo = 'matutino';

        // Cria produto fracionado
        $cod = 'TEST-FRAC-15-' . strtoupper(substr(uniqid(), -4));
        $prodId = Produto::criar([
            'categoria_id'              => 1,
            'codigo_interno'            => $cod,
            'descricao'                 => 'Coxinha Pequena',
            'preco_venda'               => 4.00,
            'tipo'                      => 'salgado_diario',
            'fracionado_salgado_grande' => 1,
            'turno_disponibilidade'     => 'ambos',
            'estoque_atual'             => 0,
            'status_pausa'              => 'ativo'
        ], [], 1);
        $this->produtosCriados[] = $prodId;

        // Cria 1 pedido com quantidade 15
        $resPedido = Pedido::criarPedido(
            $this->responsavelId,
            $this->alunoId,
            $dataConsumo,
            [['produto_id' => $prodId, 'quantidade' => 15]],
            new \DateTimeImmutable($dataConsumo . ' 07:00:00')
        );
        $this->pedidosCriados[] = $resPedido['id'];

        // Executa consolidação do corte do turno
        $corte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo, new \DateTimeImmutable($dataConsumo . ' 07:30:00'));

        $itensCorte = $corte['itens'];
        
        // Filtra os itens do nosso produto de teste
        $itensProd = array_values(array_filter($itensCorte, fn($it) => $it['produto_id'] === $prodId));

        $this->assert(count($itensProd) === 2, "O corte gerou exatamente 2 linhas para o fornecedor (Grande e Pequeno)");

        $itemGrande = null;
        $itemPequeno = null;
        foreach ($itensProd as $it) {
            if ($it['tamanho_fornecedor'] === 'grande') {
                $itemGrande = $it;
            } elseif ($it['tamanho_fornecedor'] === 'pequeno') {
                $itemPequeno = $it;
            }
        }

        $this->assert($itemGrande !== null, "Linha de Salgado Grande gerada com sucesso");
        $this->assert($itemPequeno !== null, "Linha de Salgado Pequeno gerada com sucesso");

        $this->assert((int)($itemGrande['quantidade_total'] ?? 0) === 7, "Quantidade de salgados grandes é exatamente 7 un (15 / 2 = 7)");
        $this->assert(str_contains($itemGrande['descricao'], 'Grande'), "Descrição do grande foi formatada com 'Grande': {$itemGrande['descricao']}");
        $this->assert((float)$itemGrande['preco_venda'] === 8.00, "Preço unitário do grande é o dobro do pequeno (R$ 8,00)");

        $this->assert((int)($itemPequeno['quantidade_total'] ?? 0) === 1, "Quantidade de salgados pequenos é exatamente 1 un (sobra de 15)");
        $this->assert((float)$itemPequeno['preco_venda'] === 4.00, "Preço unitário do pequeno mantido em R$ 4,00");

        $totalPecasProd = (int)$itemGrande['quantidade_total'] + (int)$itemPequeno['quantidade_total'];
        $this->assert($totalPecasProd === 8, "Total de peças para o fornecedor deste salgado é 8 peças (7 grandes + 1 pequeno)");
    }

    /**
     * Teste 4: Cenário Par — 14 Pequenos pedidos -> 7 grandes e 0 pequenos
     */
    private function testarCenarioPar14PequenosViram7Grandes(): void
    {
        echo "\n4. Validando Cenário Par: 14 pequenos pedidos -> 7 grandes e 0 pequenos...\n";
        $this->prepararContaTeste();

        $dataConsumo = '2026-11-23';
        $turnoConsumo = 'matutino';

        $cod = 'TEST-FRAC-14-' . strtoupper(substr(uniqid(), -4));
        $prodId = Produto::criar([
            'categoria_id'              => 1,
            'codigo_interno'            => $cod,
            'descricao'                 => 'Esfiha de Carne',
            'preco_venda'               => 5.00,
            'tipo'                      => 'salgado_diario',
            'fracionado_salgado_grande' => 1,
            'turno_disponibilidade'     => 'ambos',
            'status_pausa'              => 'ativo'
        ], [], 1);
        $this->produtosCriados[] = $prodId;

        $resPedido = Pedido::criarPedido(
            $this->responsavelId,
            $this->alunoId,
            $dataConsumo,
            [['produto_id' => $prodId, 'quantidade' => 14]],
            new \DateTimeImmutable($dataConsumo . ' 07:00:00')
        );
        $this->pedidosCriados[] = $resPedido['id'];

        $corte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo, new \DateTimeImmutable($dataConsumo . ' 07:30:00'));
        $itensProd = array_values(array_filter($corte['itens'], fn($it) => $it['produto_id'] === $prodId));

        $this->assert(count($itensProd) === 1, "O corte gerou apenas 1 linha para o fornecedor (divisão exata)");
        $this->assert($itensProd[0]['tamanho_fornecedor'] === 'grande', "A linha única gerada é de tamanho grande");
        $this->assert((int)$itensProd[0]['quantidade_total'] === 7, "Quantidade é exatamente 7 grandes (14 / 2 = 7)");
        $this->assert(str_contains($itensProd[0]['descricao'], 'Grande'), "Descrição formatada como Grande: {$itensProd[0]['descricao']}");
    }

    /**
     * Teste 5: Cenário Mínimo — 1 Pequeno pedido -> 0 grandes e 1 pequeno
     */
    private function testarCenario1Pequeno(): void
    {
        echo "\n5. Validando Cenário Mínimo: 1 pequeno pedido -> 0 grandes e 1 pequeno...\n";
        $this->prepararContaTeste();

        $dataConsumo = '2026-11-24';
        $turnoConsumo = 'matutino';

        $cod = 'TEST-FRAC-1-' . strtoupper(substr(uniqid(), -4));
        $prodId = Produto::criar([
            'categoria_id'              => 1,
            'codigo_interno'            => $cod,
            'descricao'                 => 'Enroladinho de Queijo',
            'preco_venda'               => 3.50,
            'tipo'                      => 'salgado_diario',
            'fracionado_salgado_grande' => 1,
            'turno_disponibilidade'     => 'ambos',
            'status_pausa'              => 'ativo'
        ], [], 1);
        $this->produtosCriados[] = $prodId;

        $resPedido = Pedido::criarPedido(
            $this->responsavelId,
            $this->alunoId,
            $dataConsumo,
            [['produto_id' => $prodId, 'quantidade' => 1]],
            new \DateTimeImmutable($dataConsumo . ' 07:00:00')
        );
        $this->pedidosCriados[] = $resPedido['id'];

        $corte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo, new \DateTimeImmutable($dataConsumo . ' 07:30:00'));
        $itensProd = array_values(array_filter($corte['itens'], fn($it) => $it['produto_id'] === $prodId));

        $this->assert(count($itensProd) === 1, "Corte gerou apenas 1 linha de pequeno");
        $this->assert($itensProd[0]['tamanho_fornecedor'] === 'pequeno', "Linha gerada é de tamanho pequeno");
        $this->assert((int)$itensProd[0]['quantidade_total'] === 1, "Quantidade é 1 pequeno");
    }

    /**
     * Teste 6: Mensagem de WhatsApp para Fornecedor
     */
    private function testarMensagemWhatsApp(): void
    {
        echo "\n6. Validando formatação da mensagem para WhatsApp...\n";

        $itensMock = [
            [
                'produto_id'       => 101,
                'codigo_interno'   => 'SAL-01-G',
                'descricao'        => 'Coxinha de Frango (Grande)',
                'quantidade_total' => 7
            ],
            [
                'produto_id'       => 101,
                'codigo_interno'   => 'SAL-01-P',
                'descricao'        => 'Coxinha de Frango (Pequena)',
                'quantidade_total' => 1
            ],
            [
                'produto_id'       => 102,
                'codigo_interno'   => 'SAL-02',
                'descricao'        => 'Pastel Assado de Palmito',
                'quantidade_total' => 5
            ]
        ];

        $txt = Pedido::obterTextoWhatsAppCorte('2026-10-05', 'matutino', $itensMock);

        $this->assert(str_contains($txt, '• *7x* Coxinha de Frango (Grande)'), "WhatsApp contém '• *7x* Coxinha de Frango (Grande)'");
        $this->assert(str_contains($txt, '• *1x* Coxinha de Frango (Pequena)'), "WhatsApp contém '• *1x* Coxinha de Frango (Pequena)'");
        $this->assert(str_contains($txt, '• *5x* Pastel Assado de Palmito'), "WhatsApp contém o item comum");
        $this->assert(str_contains($txt, '*TOTAL GERAL: 13 peças*'), "Total geral de peças do fornecedor calculado corretamente (7 + 1 + 5 = 13)");
    }

    /**
     * Teste 7: Geração de Relatórios A4 em PDF e HTML
     */
    private function testarGeracaoPdfEHtml(): void
    {
        echo "\n7. Validando geração de PDF e HTML com itens fracionados...\n";

        $dadosCorte = [
            'data_consumo'        => '2026-10-05',
            'turno_consumo'       => 'matutino',
            'itens'               => [
                [
                    'codigo_interno'   => 'ESF-G',
                    'descricao'        => 'Esfiha de Carne (Grande)',
                    'categoria_nome'   => 'Salgados Assados',
                    'quantidade_total' => 7
                ],
                [
                    'codigo_interno'   => 'ESF-P',
                    'descricao'        => 'Esfiha de Carne (Pequena)',
                    'categoria_nome'   => 'Salgados Assados',
                    'quantidade_total' => 1
                ]
            ],
            'total_pecas'         => 8,
            'total_pedidos'       => 15,
            'total_alunos'        => 15,
            'status_corte'        => ['rotulo' => 'Corte Fechado']
        ];

        // PDF binário
        $pdf = PdfService::gerarRelatorioCortePdf($dadosCorte);
        $this->assert(str_starts_with($pdf, '%PDF-1.4'), "PDF binário 1.4 gerado com sucesso");
        $this->assert(strlen($pdf) > 1000, "Tamanho do PDF válido (" . strlen($pdf) . " bytes)");

        // HTML A4
        $html = PdfService::gerarHtmlRelatorioCorte($dadosCorte);
        $this->assert(str_contains($html, 'Esfiha de Carne (Grande)'), "HTML contém linha de Grandes");
        $this->assert(str_contains($html, 'Esfiha de Carne (Pequena)'), "HTML contém linha de Pequenos");
        $this->assert(str_contains($html, '8 PEÇAS'), "HTML totaliza 8 peças a entregar");
    }

    /**
     * Teste 8: Conferência de Recebimento Físico com Fator de Conversão
     */
    private function testarRecebimentoFornecedorFracionado(): void
    {
        echo "\n8. Validando confirmação de recebimento físico de itens fracionados...\n";

        $dataConsumo = '2026-11-25';
        $turnoConsumo = 'matutino';

        $cod = 'TEST-REC-' . strtoupper(substr(uniqid(), -4));
        $prodId = Produto::criar([
            'categoria_id'              => 1,
            'codigo_interno'            => $cod,
            'descricao'                 => 'Folhado de Frango Pequeno',
            'preco_venda'               => 4.00,
            'tipo'                      => 'salgado_diario',
            'fracionado_salgado_grande' => 1,
            'turno_disponibilidade'     => 'ambos',
            'estoque_atual'             => 0,
            'status_pausa'              => 'ativo'
        ], [], 1);
        $this->produtosCriados[] = $prodId;

        // O fornecedor entrega 7 grandes (fator 2) e 1 pequeno (fator 1) para atender 15 pedidos
        $itensRecebidos = [
            [
                'produto_id'         => $prodId,
                'qtd_esperada'       => 7,
                'qtd_entregue'       => 7,
                'fator_conversao'    => 2,
                'tamanho_fornecedor' => 'grande'
            ],
            [
                'produto_id'         => $prodId,
                'qtd_esperada'       => 1,
                'qtd_entregue'       => 1,
                'fator_conversao'    => 1,
                'tamanho_fornecedor' => 'pequeno'
            ]
        ];

        $resRec = Pedido::confirmarRecebimentoFornecedor(
            $dataConsumo,
            $turnoConsumo,
            1, // operador admin
            $itensRecebidos,
            'Teste automatizado de recebimento fracionado'
        );

        $this->assert($resRec['total_esperado'] === 8, "Total esperado de peças físicas: 8 peças");
        $this->assert($resRec['total_entregue'] === 8, "Total entregue de peças físicas: 8 peças");
        $this->assert($resRec['total_excedente'] === 0, "Excedente zero (7*2 + 1*1 = 15 unidades atendidas exatamente)");

        // Verifica que houve movimentação em estoque_movimentacoes para o produto
        $stmtMov = $this->pdo->prepare("SELECT SUM(quantidade) AS qtd FROM estoque_movimentacoes WHERE produto_id = :p_id AND tipo_movimentacao = 'entrada_fornecedor'");
        $stmtMov->execute(['p_id' => $prodId]);
        $qtdEntrada = (int)$stmtMov->fetchColumn();

        $this->assert($qtdEntrada === 15, "Entrada em estoque registrou exatamente 15 unidades consumíveis equivalentes");
    }

    private function limparDadosTeste(): void
    {
        echo "\nLimpando dados temporários de teste...\n";

        // Exclui pedidos de teste
        if (!empty($this->pedidosCriados)) {
            $ids = implode(',', array_map('intval', $this->pedidosCriados));
            $this->pdo->exec("DELETE FROM tickets WHERE pedido_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM itens_pedido WHERE pedido_id IN ({$ids})");
            $this->pdo->exec("DELETE FROM pedidos WHERE id IN ({$ids})");
        }

        // Exclui produtos de teste
        if (!empty($this->produtosCriados)) {
            $pIds = implode(',', array_map('intval', $this->produtosCriados));
            $this->pdo->exec("DELETE FROM estoque_movimentacoes WHERE produto_id IN ({$pIds})");
            $this->pdo->exec("DELETE FROM produto_alergenos WHERE produto_id IN ({$pIds})");
            $this->pdo->exec("DELETE FROM produtos WHERE id IN ({$pIds})");
        }

        // Exclui aluno e responsável
        if ($this->alunoId) {
            $this->pdo->exec("DELETE FROM aluno_restricoes WHERE aluno_id = {$this->alunoId}");
            $this->pdo->exec("DELETE FROM alunos WHERE id = {$this->alunoId}");
        }
        if ($this->responsavelId) {
            $this->pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id = {$this->responsavelId}");
            $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$this->responsavelId}");
        }
    }
}

$test = new SalgadosFracionadosTest();
$test->runAll();
