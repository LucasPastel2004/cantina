<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Fase 11: Operação de Encomendas e Gestão de Fornecedores
 * 
 * Valida:
 * 1. Consolidação matemática de salgados do turno (corte matutino e vespertino);
 * 2. Formatação da mensagem estruturada para envio via WhatsApp;
 * 3. Geração de relatório consolidado em PDF A4 timbrado puro via PdfService;
 * 4. Espelho de impressão para navegador em HTML/CSS;
 * 5. Conferência física e confirmação atômica de entrega do fornecedor;
 * 6. Atualização de estoque_movimentacoes (entrada_fornecedor e saida_encomenda);
 * 7. Crédito de sobras/excedentes no estoque_atual para venda avulsa;
 * 8. Trilha de auditoria em logs_auditoria;
 * 9. Bloqueio estrito contra recebimento duplicado da mesma remessa;
 * 10. Rotas web HTTP do PedidosController.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/core/Router.php';

require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/Usuario.php';

require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/services/WhatsAppService.php';
require_once __DIR__ . '/../app/controllers/PedidosController.php';

use App\Core\Model;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Responsavel;
use App\Models\Turma;
use App\Models\Aluno;
use App\Models\TransacaoCarteira;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Services\PdfService;
use App\Controllers\PedidosController;

class Fase11Test
{
    private \PDO $pdo;
    private int $passedTests = 0;
    private int $totalTests = 0;

    // IDs de fixture para limpeza
    private array $produtosCriados = [];
    private array $pedidosCriados = [];
    private array $responsaveisCriados = [];
    private array $alunosCriados = [];
    private array $turmasCriadas = [];
    private array $usuariosCriados = [];
    private string $dataTeste;

    public function __construct()
    {
        $this->pdo = Model::getPdo();
        $this->dataTeste = '2026-11-20'; // Data futura isolada para testes
    }

    public function run(): void
    {
        echo "=================================================================\n";
        echo "   SUÍTE DE TESTES — FASE 11: ENCOMENDAS E GESTÃO DE FORNECEDORES \n";
        echo "   Cantina Web — Colégio Monteiro Lobato                           \n";
        echo "=================================================================\n\n";

        try {
            $this->testarConexaoInfraestrutura();
            $this->setupFixtures();
            $this->testarConsolidacaoCorteMatutinoEVespertino();
            $this->testarMensagemWhatsApp();
            $this->testarGeradorPdfService();
            $this->testarEspelhoImpressaoHtml();
            $this->testarStatusCorteOperacional();
            $this->testarConfirmacaoRecebimentoExato();
            $this->testarBloqueioRecebimentoDuplicado();
            $this->testarConfirmacaoRecebimentoComExcedente();
            $this->testarRotasWebPedidosController();
        } finally {
            $this->teardown();
        }

        echo "\n=================================================================\n";
        echo "   RESULTADO FINAL: {$this->passedTests} de {$this->totalTests} asserções APROVADAS!\n";
        if ($this->passedTests === $this->totalTests) {
            echo "   STATUS: 100% DE SUCESSO — FASE 11 HOMOLOGADA!\n";
        } else {
            echo "   STATUS: FALHAS ENCONTRADAS NA FASE 11.\n";
        }
        echo "=================================================================\n\n";

        if ($this->passedTests !== $this->totalTests) {
            exit(1);
        }
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalTests++;
        if ($condicao) {
            $this->passedTests++;
            echo " [OK] {$descricao}\n";
        } else {
            echo " [FAIL] {$descricao}\n";
        }
    }

    private function testarConexaoInfraestrutura(): void
    {
        echo "--- 1. Infraestrutura e Persistência da Fase 11 ---\n";
        $this->assert($this->pdo instanceof \PDO, 'Conexão singleton PDO ativa');

        $tabelas = ['pedidos', 'itens_pedido', 'produtos', 'estoque_movimentacoes', 'logs_auditoria', 'usuarios'];
        foreach ($tabelas as $tab) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tab}'");
            $this->assert($stmt->rowCount() > 0, "Tabela [{$tab}] presente no schema");
        }
    }

    private function setupFixtures(): void
    {
        echo "\n--- 2. Criação de Dados e Fixtures para Testes ---\n";

        // Cria operador para auditoria
        $stmtOp = $this->pdo->prepare("
            INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo, created_at)
            VALUES (:usr, :nome, :email, :hash, 'operador', 1, NOW())
        ");
        $usrCode = 'op-f11-' . bin2hex(random_bytes(3));
        $stmtOp->execute([
            'usr'   => $usrCode,
            'nome'  => 'Operador de Testes F11',
            'email' => "{$usrCode}@monteirolobato.teste",
            'hash'  => password_hash('Senha@123', PASSWORD_BCRYPT)
        ]);
        $operadorId = (int)$this->pdo->lastInsertId();
        $this->usuariosCriados[] = $operadorId;
        $this->assert($operadorId > 0, "Operador de teste criado (ID {$operadorId})");

        // Cria Turma Matutina e Vespertina
        $turmaMat = Turma::localizarOuCriar('7º Ano A', '7º Ano', 'matutino');
        $turmaVesp = Turma::localizarOuCriar('7º Ano B', '7º Ano', 'vespertino');
        $this->turmasCriadas[] = (int)$turmaMat['id'];
        $this->turmasCriadas[] = (int)$turmaVesp['id'];

        // Cria Responsável Familiar com saldo
        $cpfUnico = sprintf('888%06d11', random_int(100000, 999999));
        $resp = Responsavel::localizarOuCriarPorCpf([
            'cpf'      => $cpfUnico,
            'nome'     => 'Pai Teste Fase 11',
            'whatsapp' => '11988887777'
        ]);
        $respId = (int)$resp['id'];
        $this->responsaveisCriados[] = $respId;

        // Credita R$ 500,00 para garantir saldo pré-pago livre
        TransacaoCarteira::creditar($respId, 500.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Saldo de testes F11');

        // Cria 2 alunos (1 matutino e 1 vespertino)
        $matrMat = 'MAT-F11-' . random_int(10000, 99999);
        $matrVesp = 'VES-F11-' . random_int(10000, 99999);
        $alunoMatId = Aluno::criar([
            'responsavel_id' => $respId,
            'turma_id'       => (int)$turmaMat['id'],
            'matricula'      => $matrMat,
            'nome'           => 'Aluno Matutino F11',
            'turno'          => 'matutino',
            'ativo'          => 1,
        ]);
        $alunoVespId = Aluno::criar([
            'responsavel_id' => $respId,
            'turma_id'       => (int)$turmaVesp['id'],
            'matricula'      => $matrVesp,
            'nome'           => 'Aluno Vespertino F11',
            'turno'          => 'vespertino',
            'ativo'          => 1,
        ]);
        $this->alunosCriados[] = $alunoMatId;
        $this->alunosCriados[] = $alunoVespId;

        // Cria Categoria e Produtos de teste
        $cat = Categoria::buscarPorNome('Salgados Assados');
        $catId = $cat ? (int)$cat['id'] : 1;

        // Produto 1: Coxinha (Salgado Diário)
        $cod1 = 'SAL-F11-1-' . random_int(100, 999);
        $prod1Id = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $cod1,
            'descricao'      => 'Coxinha Assada Teste F11',
            'preco_venda'    => 7.50,
            'tipo'           => 'salgado_diario',
            'turno_disponibilidade' => 'ambos',
            'estoque_atual'  => 0, // Inicia zerado, pois é produzido sob demanda
            'estoque_minimo' => 5,
            'status_pausa'   => 'ativo',
        ]);
        $this->produtosCriados[] = $prod1Id;

        // Produto 2: Esfiha (Salgado Diário)
        $cod2 = 'SAL-F11-2-' . random_int(100, 999);
        $prod2Id = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $cod2,
            'descricao'      => 'Esfiha de Carne Teste F11',
            'preco_venda'    => 8.00,
            'tipo'           => 'salgado_diario',
            'turno_disponibilidade' => 'ambos',
            'estoque_atual'  => 0,
            'estoque_minimo' => 5,
            'status_pausa'   => 'ativo',
        ]);
        $this->produtosCriados[] = $prod2Id;

        // Produto 3: Suco (Industrializado)
        $cod3 = 'BEB-F11-3-' . random_int(100, 999);
        $prod3Id = Produto::criar([
            'categoria_id'   => $catId,
            'codigo_interno' => $cod3,
            'descricao'      => 'Suco Natural Teste F11',
            'preco_venda'    => 6.00,
            'tipo'           => 'industrializado_continuo',
            'turno_disponibilidade' => 'ambos',
            'estoque_atual'  => 50,
            'estoque_minimo' => 5,
            'status_pausa'   => 'ativo',
        ]);
        $this->produtosCriados[] = $prod3Id;

        // Momento simulado de abertura (antes do corte)
        $momentoAberto = new \DateTimeImmutable($this->dataTeste . ' 07:00:00');

        // Cria Pedido 1 (Matutino): 15x Coxinha + 5x Suco
        $ped1 = Pedido::criarPedido(
            $respId,
            $alunoMatId,
            $this->dataTeste,
            [
                ['produto_id' => $prod1Id, 'quantidade' => 15],
                ['produto_id' => $prod3Id, 'quantidade' => 5],
            ],
            $momentoAberto
        );
        $this->pedidosCriados[] = (int)$ped1['id'];

        // Cria Pedido 2 (Matutino): 10x Coxinha + 20x Esfiha
        $ped2 = Pedido::criarPedido(
            $respId,
            $alunoMatId,
            $this->dataTeste,
            [
                ['produto_id' => $prod1Id, 'quantidade' => 10],
                ['produto_id' => $prod2Id, 'quantidade' => 20],
            ],
            $momentoAberto
        );
        $this->pedidosCriados[] = (int)$ped2['id'];

        // Cria Pedido 3 (Vespertino): 12x Esfiha
        $ped3 = Pedido::criarPedido(
            $respId,
            $alunoVespId,
            $this->dataTeste,
            [
                ['produto_id' => $prod2Id, 'quantidade' => 12],
            ],
            $momentoAberto
        );
        $this->pedidosCriados[] = (int)$ped3['id'];

        $this->assert(count($this->pedidosCriados) === 3, '3 pedidos de teste criados para a data ' . $this->dataTeste);
    }

    private function testarConsolidacaoCorteMatutinoEVespertino(): void
    {
        echo "\n--- 3. Consolidação Matemática do Corte do Turno ---\n";

        // Consolidação do Turno Matutino
        $corteMat = Pedido::consolidarCorte($this->dataTeste, 'matutino');

        $this->assert($corteMat['data_consumo'] === $this->dataTeste, 'Data do corte matutino correta');
        $this->assert($corteMat['turno_consumo'] === 'matutino', 'Turno do corte matutino correto');
        $this->assert($corteMat['total_pedidos'] === 2, 'Total de pedidos matutinos consolidado: 2');
        $this->assert($corteMat['total_alunos'] === 1, 'Total de alunos matutinos consolidado: 1');

        // Itens esperados no Matutino:
        // Coxinha: 15 + 10 = 25 un
        // Esfiha: 20 un
        // Suco: 5 un
        // Total Peças: 25 + 20 + 5 = 50 un
        // Total Salgados Diários: 25 + 20 = 45 un
        $this->assert($corteMat['total_pecas'] === 50, 'Total geral de peças matutinas exato: 50 un');
        $this->assert($corteMat['total_salgados'] === 45, 'Total de salgados diários matutinos exato: 45 un');

        // Mapeia quantidades por produto_id
        $mapQtd = [];
        foreach ($corteMat['itens'] as $item) {
            $mapQtd[(int)$item['produto_id']] = (int)$item['quantidade_total'];
        }

        $prodCoxinhaId = $this->produtosCriados[0];
        $prodEsfihaId  = $this->produtosCriados[1];
        $prodSucoId    = $this->produtosCriados[2];

        $this->assert(($mapQtd[$prodCoxinhaId] ?? 0) === 25, 'Demanda exata de Coxinhas no corte: 25 un (15 + 10)');
        $this->assert(($mapQtd[$prodEsfihaId] ?? 0) === 20, 'Demanda exata de Esfihas no corte: 20 un');
        $this->assert(($mapQtd[$prodSucoId] ?? 0) === 5, 'Demanda exata de Sucos no corte: 5 un');

        // Consolidação do Turno Vespertino
        $corteVesp = Pedido::consolidarCorte($this->dataTeste, 'vespertino');
        $this->assert($corteVesp['turno_consumo'] === 'vespertino', 'Turno do corte vespertino correto');
        $this->assert($corteVesp['total_pedidos'] === 1, 'Total de pedidos vespertinos: 1');
        $this->assert($corteVesp['total_pecas'] === 12, 'Total geral de peças vespertinas: 12 un');
        $this->assert($corteVesp['total_salgados'] === 12, 'Total de salgados vespertinos: 12 un');
    }

    private function testarMensagemWhatsApp(): void
    {
        echo "\n--- 4. Formatação de Mensagem Estruturada para WhatsApp ---\n";

        $corteMat = Pedido::consolidarCorte($this->dataTeste, 'matutino');
        $txtWhats = Pedido::obterTextoWhatsAppCorte($this->dataTeste, 'matutino', $corteMat['itens']);

        $this->assert(str_contains($txtWhats, 'COLÉGIO MONTEIRO LOBATO'), 'Mensagem contém cabeçalho institucional');
        $this->assert(str_contains($txtWhats, 'PEDIDO DE SALGADOS — CORTE DO TURNO'), 'Mensagem contém identificador do pedido');
        $this->assert(str_contains($txtWhats, date('d/m/Y', strtotime($this->dataTeste))), 'Mensagem contém data formatada');
        $this->assert(str_contains($txtWhats, 'Matutino'), 'Mensagem contém turno');
        $this->assert(str_contains($txtWhats, '25x') && str_contains($txtWhats, 'Coxinha'), 'Mensagem contém 25x Coxinha');
        $this->assert(str_contains($txtWhats, '20x') && str_contains($txtWhats, 'Esfiha'), 'Mensagem contém 20x Esfiha');
        $this->assert(str_contains($txtWhats, 'TOTAL GERAL: 50 peças'), 'Mensagem totaliza 50 peças com precisão');
    }

    private function testarGeradorPdfService(): void
    {
        echo "\n--- 5. Geração de Relatório A4 Timbrado em PDF 1.4 ---\n";

        $corteMat = Pedido::consolidarCorte($this->dataTeste, 'matutino');
        $pdf = PdfService::gerarRelatorioCortePdf($corteMat);

        $this->assert(is_string($pdf) && strlen($pdf) > 500, 'PDF binário gerado com tamanho válido (> 500 bytes)');
        $this->assert(str_starts_with($pdf, "%PDF-"), 'PDF possui assinatura de cabeçalho padrão %PDF');
        $this->assert(str_contains($pdf, '/Catalog') || str_contains($pdf, '/Root'), 'PDF contém objeto Catalog ou Root');
        $this->assert(str_contains($pdf, '/Pages'), 'PDF contém objeto Pages');
        $this->assert(str_contains($pdf, '%%EOF'), 'PDF encerra com marcador padrão %%EOF');
    }

    private function testarEspelhoImpressaoHtml(): void
    {
        echo "\n--- 6. Espelho de Impressão HTML / CSS @media print ---\n";

        $corteMat = Pedido::consolidarCorte($this->dataTeste, 'matutino');
        $html = PdfService::gerarHtmlRelatorioCorte($corteMat);

        $this->assert(str_contains($html, '<!DOCTYPE html>'), 'Espelho contém doctype HTML5');
        $this->assert(str_contains($html, 'size: A4 portrait;'), 'CSS contém definição de página A4 portrait');
        $this->assert(str_contains($html, 'Colégio Monteiro Lobato'), 'HTML contém cabeçalho institucional');
        $this->assert(str_contains($html, 'window.print()'), 'HTML possui botão com acionamento nativo de impressão');
        $this->assert(str_contains($html, '50 PEÇAS'), 'HTML exibe o total consolidado de 50 peças');
    }

    private function testarStatusCorteOperacional(): void
    {
        echo "\n--- 7. Travas Rígidas de Corte Operacional ---\n";

        $hoje = date('Y-m-d');

        // Turno Matutino: Antes das 08:00 (07:30) deve estar ABERTO
        $antesCorteMat = new \DateTimeImmutable($hoje . ' 07:30:00');
        $stMatAberto = Pedido::obterStatusCorte($hoje, 'matutino', $antesCorteMat);
        $this->assert($stMatAberto['pode_alterar'] === true, 'Corte matutino antes das 08:00 (07:30) permanece ABERTO');
        $this->assert($stMatAberto['status'] === 'hoje_aberto', 'Status de corte hoje_aberto identificado');
        $this->assert($stMatAberto['minutos_restantes'] === 30, 'Restam 30 minutos calculados com exatidão');

        // Turno Matutino: Pontualmente às 08:00 deve estar FECHADO
        $noCorteMat = new \DateTimeImmutable($hoje . ' 08:00:00');
        $stMatFechado = Pedido::obterStatusCorte($hoje, 'matutino', $noCorteMat);
        $this->assert($stMatFechado['pode_alterar'] === false, 'Corte matutino pontualmente às 08:00 é FECHADO');
        $this->assert($stMatFechado['status'] === 'corte_fechado', 'Status corte_fechado identificado');

        // Turno Vespertino: Antes das 13:30 (13:00) deve estar ABERTO
        $antesCorteVesp = new \DateTimeImmutable($hoje . ' 13:00:00');
        $stVespAberto = Pedido::obterStatusCorte($hoje, 'vespertino', $antesCorteVesp);
        $this->assert($stVespAberto['pode_alterar'] === true, 'Corte vespertino antes das 13:30 (13:00) permanece ABERTO');

        // Turno Vespertino: A partir das 13:30 (13:31) deve estar FECHADO
        $posCorteVesp = new \DateTimeImmutable($hoje . ' 13:31:00');
        $stVespFechado = Pedido::obterStatusCorte($hoje, 'vespertino', $posCorteVesp);
        $this->assert($stVespFechado['pode_alterar'] === false, 'Corte vespertino às 13:31 é FECHADO');
    }

    private function testarConfirmacaoRecebimentoExato(): void
    {
        echo "\n--- 8. Conferência e Confirmação de Recebimento Físico (Exato) ---\n";

        $operadorId = $this->usuariosCriados[0];
        $prodCoxinhaId = $this->produtosCriados[0];
        $prodEsfihaId  = $this->produtosCriados[1];

        // Status antes do recebimento: deve ser nulo
        $statusAntes = Pedido::verificarStatusRecebimento($this->dataTeste, 'matutino');
        $this->assert($statusAntes === null, 'Nenhum recebimento registrado antes da conferência física');

        // Recebimento exato:
        // Coxinha esperada: 25 un, entregue: 25 un
        // Esfiha esperada: 20 un, entregue: 20 un
        $itensRecebimento = [
            ['produto_id' => $prodCoxinhaId, 'qtd_esperada' => 25, 'qtd_entregue' => 25],
            ['produto_id' => $prodEsfihaId,  'qtd_esperada' => 20, 'qtd_entregue' => 20],
        ];

        $res = Pedido::confirmarRecebimentoFornecedor(
            $this->dataTeste,
            'matutino',
            $operadorId,
            $itensRecebimento,
            'Conferido pelo Operador Teste'
        );

        $this->assert($res['sucesso'] === true, 'Confirmação de recebimento concluída com sucesso');
        $this->assert($res['total_esperado'] === 45, 'Total esperado processado: 45 un');
        $this->assert($res['total_entregue'] === 45, 'Total entregue processado: 45 un');
        $this->assert($res['total_excedente'] === 0, 'Excedente nulo quando a entrega é exata');

        // Verifica registros em estoque_movimentacoes:
        // Deve haver entrada_fornecedor e saida_encomenda para coxinha e esfiha
        $stmtMov = $this->pdo->prepare("
            SELECT tipo_movimentacao, produto_id, quantidade, justificativa 
            FROM estoque_movimentacoes 
            WHERE justificativa LIKE :just
        ");
        $stmtMov->execute(['just' => "%{$this->dataTeste} - matutino%"]);
        $movs = $stmtMov->fetchAll(\PDO::FETCH_ASSOC);

        $this->assert(count($movs) >= 4, 'Gravadas entradas e saídas de liquidação em estoque_movimentacoes (ao menos 4 registros)');

        $tiposMov = array_column($movs, 'tipo_movimentacao');
        $this->assert(in_array('entrada_fornecedor', $tiposMov, true), 'Movimentação do tipo entrada_fornecedor registrada');
        $this->assert(in_array('saida_encomenda', $tiposMov, true), 'Movimentação do tipo saida_encomenda (liquidação de demanda) registrada');

        // Verifica auditoria em logs_auditoria
        $stmtAudit = $this->pdo->prepare("
            SELECT * FROM logs_auditoria 
            WHERE acao = 'recebimento_fornecedor' AND justificativa LIKE :data
        ");
        $stmtAudit->execute(['data' => "%{$this->dataTeste}%"]);
        $audit = $stmtAudit->fetch(\PDO::FETCH_ASSOC);

        $this->assert($audit !== false, 'Registro de auditoria criado com acao = recebimento_fornecedor');
        $this->assert((int)$audit['operador_id'] === $operadorId, 'Operador responsável registrado corretamente na auditoria');

        // Consulta de status de recebimento atualizada
        $statusDepois = Pedido::verificarStatusRecebimento($this->dataTeste, 'matutino');
        $this->assert($statusDepois !== null && $statusDepois['recebido'] === true, 'Status de recebimento agora retorna recebido = true');
        $this->assert($statusDepois['operador_id'] === $operadorId, 'Nome/ID do operador conferente disponível');
    }

    private function testarBloqueioRecebimentoDuplicado(): void
    {
        echo "\n--- 9. Prevenção Contra Recebimento Duplicado ---\n";

        $operadorId = $this->usuariosCriados[0];
        $prodCoxinhaId = $this->produtosCriados[0];

        $disparouExcecao = false;
        try {
            Pedido::confirmarRecebimentoFornecedor(
                $this->dataTeste,
                'matutino',
                $operadorId,
                [['produto_id' => $prodCoxinhaId, 'qtd_esperada' => 25, 'qtd_entregue' => 25]]
            );
        } catch (\DomainException $e) {
            $disparouExcecao = true;
            $this->assert(str_contains($e->getMessage(), 'já foi confirmado anteriormente'), 'Mensagem explicativa de duplicidade retornada');
        }

        $this->assert($disparouExcecao, 'Tentativa de recebimento duplicado da mesma remessa categoricamente bloqueada');
    }

    private function testarConfirmacaoRecebimentoComExcedente(): void
    {
        echo "\n--- 10. Recebimento com Excedente (Sobra para Venda Avulsa no PDV) ---\n";

        $operadorId = $this->usuariosCriados[0];
        $prodEsfihaId = $this->produtosCriados[1];

        // Para o turno vespertino: Esperado 12 esfihas, entregues 15 esfihas (+3 sobras)
        $pAntes = Produto::buscarPorId($prodEsfihaId);
        $estoqueAntes = (int)$pAntes['estoque_atual'];

        $resVesp = Pedido::confirmarRecebimentoFornecedor(
            $this->dataTeste,
            'vespertino',
            $operadorId,
            [['produto_id' => $prodEsfihaId, 'qtd_esperada' => 12, 'qtd_entregue' => 15]],
            'Fornecedor enviou 3 unidades a mais como sobra'
        );

        $this->assert($resVesp['sucesso'] === true, 'Recebimento vespertino com excedente processado');
        $this->assert($resVesp['total_excedente'] === 3, 'Total excedente apurado com precisão: 3 un');

        // Confere se estoque_atual em produtos incrementou exatamente pelas 3 unidades excedentes
        $pDepois = Produto::buscarPorId($prodEsfihaId);
        $estoqueDepois = (int)$pDepois['estoque_atual'];

        $this->assert($estoqueDepois === ($estoqueAntes + 3), "Estoque físico atualizado: de {$estoqueAntes} para {$estoqueDepois} (+3 un disponíveis para venda avulsa)");
    }

    private function testarRotasWebPedidosController(): void
    {
        echo "\n--- 11. Teste das Rotas Web e Controlador PedidosController ---\n";

        // Autentica como operador na sessão
        $_SESSION['_auth_user_id'] = $this->usuariosCriados[0];
        $_SESSION['_auth_role'] = 'operador';
        $_SESSION['_auth_nome'] = 'Operador Teste';

        $_GET['data'] = $this->dataTeste;
        $_GET['turno'] = 'matutino';

        $controller = new PedidosController();

        // 1. Renderização do Painel de Corte
        ob_start();
        $controller->corte();
        $htmlCorte = ob_get_clean();

        $this->assert(str_contains($htmlCorte, 'Painel de Corte do Turno'), 'View operacional/corte renderizada com sucesso');
        $this->assert(str_contains($htmlCorte, 'Mensagem Estruturada para Fornecedor (WhatsApp)'), 'Card de WhatsApp presente no HTML');
        $this->assert(str_contains($htmlCorte, 'Exportar PDF'), 'Link de exportação PDF presente');
        $this->assert(str_contains($htmlCorte, 'Conferir Recebimento'), 'Botão para conferência de recebimento presente');
        $this->assert(str_contains($htmlCorte, 'Demandas de Produção por Salgado'), 'Data-grid de demandas exibido');

        // 2. Renderização da Tela de Conferência e Recebimento
        ob_start();
        $controller->recebimento();
        $htmlRec = ob_get_clean();

        $this->assert(str_contains($htmlRec, 'Conferência e Recebimento de Fornecedor'), 'View operacional/recebimento renderizada com sucesso');
        $this->assert(str_contains($htmlRec, 'Recebimento Físico Concluído'), 'Banner de remessa recebida renderizado para o turno matutino já conferido');
    }

    private function teardown(): void
    {
        echo "\n--- Limpeza de Dados de Teste (Teardown) ---\n";

        // Remove movimentações de estoque de teste
        $stmtMov = $this->pdo->prepare("DELETE FROM estoque_movimentacoes WHERE justificativa LIKE :data");
        $stmtMov->execute(['data' => "%{$this->dataTeste}%"]);

        // Remove auditoria de teste
        $stmtAud = $this->pdo->prepare("DELETE FROM logs_auditoria WHERE justificativa LIKE :data");
        $stmtAud->execute(['data' => "%{$this->dataTeste}%"]);

        // Remove pedidos criados (CASCADE remove itens e tickets)
        foreach ($this->pedidosCriados as $pedId) {
            $this->pdo->exec("DELETE FROM tickets WHERE pedido_id = {$pedId}");
            $this->pdo->exec("DELETE FROM itens_pedido WHERE pedido_id = {$pedId}");
            $this->pdo->exec("DELETE FROM pedidos WHERE id = {$pedId}");
        }

        // Remove transações da carteira dos responsáveis criados
        foreach ($this->responsaveisCriados as $respId) {
            $this->pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id = {$respId}");
            $this->pdo->exec("DELETE FROM alunos WHERE responsavel_id = {$respId}");
            $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$respId}");
        }

        // Remove produtos criados
        foreach ($this->produtosCriados as $prodId) {
            $this->pdo->exec("DELETE FROM produtos WHERE id = {$prodId}");
        }

        // Remove turmas criadas
        foreach ($this->turmasCriadas as $turmaId) {
            $this->pdo->exec("DELETE FROM turmas WHERE id = {$turmaId}");
        }

        // Remove operador criado
        foreach ($this->usuariosCriados as $uId) {
            $this->pdo->exec("DELETE FROM usuarios WHERE id = {$uId}");
        }

        echo " Teardown concluído: fixtures de teste expurgadas com sucesso.\n";
    }
}

// Execução da Suíte
$test = new Fase11Test();
$test->run();
