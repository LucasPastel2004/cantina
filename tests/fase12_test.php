<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Fase 12: Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
 * 
 * Valida:
 * 1. Schema e Persistência das tabelas tickets e logs_auditoria;
 * 2. Ordenação Sequencial Estrita (Turno > Ordem da Turma > Turma > Aluno > Ticket);
 * 3. Agrupamento por Turma com totalizadores exatos de alunos e alérgenos;
 * 4. Ciclo de Impressão (status 'ativo' -> 'impresso' e gravação de impresso_em);
 * 5. Tag de Advertência de Alérgenos (RN-16 e RN-17);
 * 6. Consulta rápida por aluno, matrícula, turma e código;
 * 7. Reemissão de 2ª Via com Justificativa Compulsória, incremento de via e registro em logs_auditoria;
 * 8. Trilha de Auditoria (Auditoria::registrar e Auditoria::listarPorRegistro);
 * 9. Geração de PDF 1.4 para bobina térmica de 80mm (226.77 pt);
 * 10. Renderização do espelho HTML de 80mm (@media print com size: 80mm auto);
 * 11. Despacho e permissões RBAC no TicketsController;
 * 12. Teardown completo de fixtures.
 */

define('CANTINA_SISTEMA', true);

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
require_once __DIR__ . '/../app/models/Ticket.php';
require_once __DIR__ . '/../app/models/Auditoria.php';

require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/TicketsController.php';

use App\Core\Model;
use App\Core\RBAC;
use App\Core\Auth;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Responsavel;
use App\Models\Turma;
use App\Models\Aluno;
use App\Models\Pedido;
use App\Models\Usuario;
use App\Models\Ticket;
use App\Models\Auditoria;
use App\Services\PdfService;
use App\Controllers\TicketsController;

class Fase12Test
{
    private \PDO $pdo;
    private int $passedTests = 0;
    private int $totalTests = 0;

    // Fixtures para teardown
    private array $turmasCriadas = [];
    private array $responsaveisCriados = [];
    private array $alunosCriados = [];
    private array $produtosCriados = [];
    private array $pedidosCriados = [];
    private array $ticketsCriados = [];
    private array $auditoriasCriadas = [];
    private array $usuariosCriados = [];
    private string $dataTeste;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->dataTeste = '2026-11-20'; // Data futura exclusiva para isolamento
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalTests++;
        if ($condicao) {
            $this->passedTests++;
            echo "  [OK] {$descricao}\n";
        } else {
            echo "  [FAIL] {$descricao}\n";
        }
    }

    public function run(): void
    {
        echo "\n=== INICIANDO BATERIA DE TESTES — FASE 12 (TICKETS TÉRMICOS 80MM) ===\n\n";

        try {
            $this->setupFixtures();

            $this->testarSchemaEPersistencia();
            $this->testarOrdenacaoEstritaELotes();
            $this->testarAgrupamentoPorTurma();
            $this->testarCicloDeImpressao();
            $this->testarTagsAlergenos();
            $this->testarConsultaRapida();
            $this->testarReemissaoSegundaViaEJustificativa();
            $this->testarTrilhaDeAuditoria();
            $this->testarGeracaoPdf80mm();
            $this->testarEspelhoHtml80mm();
            $this->testarControllerEEndpoints();

        } finally {
            $this->teardownFixtures();
        }

        echo "\n=======================================================\n";
        echo "RESULTADO: {$this->passedTests} / {$this->totalTests} testes aprovados.\n";
        if ($this->passedTests === $this->totalTests) {
            echo "STATUS: SUCESSO TOTAL — FASE 12 CONCLUÍDA E HOMOLOGADA!\n";
        } else {
            echo "STATUS: FALHA — ALGUNS TESTES NÃO PASSARAM.\n";
            exit(1);
        }
        echo "=======================================================\n\n";
    }

    private function setupFixtures(): void
    {
        echo "--> Preparando fixtures isoladas para a Fase 12...\n";

        // 1. Cria 2 turmas com ordem de impressão definida
        // Turma B tem ordem_impressao = 1 (deve sair primeiro)
        $stmtTurma = $this->pdo->prepare("
            INSERT INTO turmas (nome_turma, serie, turno, ordem_impressao)
            VALUES (:nome, :serie, 'matutino', :ordem)
        ");

        $stmtTurma->execute(['nome' => '5º Ano B - Teste F12', 'serie' => '5º Ano', 'ordem' => 1]);
        $turmaBId = (int)$this->pdo->lastInsertId();
        $this->turmasCriadas[] = $turmaBId;

        // Turma A tem ordem_impressao = 2 (deve sair depois)
        $stmtTurma->execute(['nome' => '5º Ano A - Teste F12', 'serie' => '5º Ano', 'ordem' => 2]);
        $turmaAId = (int)$this->pdo->lastInsertId();
        $this->turmasCriadas[] = $turmaAId;

        // 2. Cria Responsável com saldo
        $stmtResp = $this->pdo->prepare("
            INSERT INTO responsaveis (nome, cpf, email, whatsapp, senha_hash, saldo_atual)
            VALUES ('Responsável F12', '12345678999', 'resp.f12@monteiro.teste', '11988887777', 'hash', 500.00)
        ");
        $stmtResp->execute();
        $respId = (int)$this->pdo->lastInsertId();
        $this->responsaveisCriados[] = $respId;

        // 3. Cria Alunos:
        // Aluno 1 na Turma B (sem alérgenos)
        $stmtAluno = $this->pdo->prepare("
            INSERT INTO alunos (responsavel_id, turma_id, nome, matricula, turno, ativo)
            VALUES (:resp, :turma, :nome, :mat, 'matutino', 1)
        ");
        $stmtAluno->execute([
            'resp'  => $respId,
            'turma' => $turmaBId,
            'nome'  => 'Bruno Aluno Turma B',
            'mat'   => 'MAT-F12-001'
        ]);
        $aluno1Id = (int)$this->pdo->lastInsertId();
        $this->alunosCriados[] = $aluno1Id;

        // Aluno 2 na Turma A (com restrição alimentar: Glúten)
        $stmtAluno->execute([
            'resp'  => $respId,
            'turma' => $turmaAId,
            'nome'  => 'Ana Aluna Turma A',
            'mat'   => 'MAT-F12-002'
        ]);
        $aluno2Id = (int)$this->pdo->lastInsertId();
        $this->alunosCriados[] = $aluno2Id;

        // Aluno 3 na Turma A (Carlos)
        $stmtAluno->execute([
            'resp'  => $respId,
            'turma' => $turmaAId,
            'nome'  => 'Carlos Aluno Turma A',
            'mat'   => 'MAT-F12-003'
        ]);
        $aluno3Id = (int)$this->pdo->lastInsertId();
        $this->alunosCriados[] = $aluno3Id;

        // 4. Cria Categoria e Produtos (um salgado simples e um salgado que contém glúten)
        $cat = Categoria::fetchOne("SELECT id FROM categorias LIMIT 1");
        $catId = $cat ? (int)$cat['id'] : 1;

        $stmtProd = $this->pdo->prepare("
            INSERT INTO produtos (categoria_id, codigo_interno, descricao, tipo, preco_venda, estoque_atual, status_pausa)
            VALUES (:cat, :cod, :desc, 'salgado_diario', 6.50, 100, 'ativo')
        ");
        $stmtProd->execute(['cat' => $catId, 'cod' => 'SAL-F12-01', 'desc' => 'Esfiha de Carne F12']);
        $prod1Id = (int)$this->pdo->lastInsertId();
        $this->produtosCriados[] = $prod1Id;

        $stmtProd->execute(['cat' => $catId, 'cod' => 'SAL-F12-02', 'desc' => 'Enroladinho de Queijo F12']);
        $prod2Id = (int)$this->pdo->lastInsertId();
        $this->produtosCriados[] = $prod2Id;

        // 5. Cria Pedidos e Itens para cada aluno
        // Pedido 1: Bruno (Turma B, Ordem 1)
        $stmtPed = $this->pdo->prepare("
            INSERT INTO pedidos (responsavel_id, aluno_id, codigo_pedido, data_consumo, turno_consumo, valor_total, status)
            VALUES (:resp, :aluno, :cod, :data, 'matutino', 6.50, 'agendado')
        ");
        $stmtPed->execute([
            'resp'  => $respId,
            'aluno' => $aluno1Id,
            'cod'   => 'PED-F12-0001',
            'data'  => $this->dataTeste
        ]);
        $ped1Id = (int)$this->pdo->lastInsertId();
        $this->pedidosCriados[] = $ped1Id;

        $stmtItem = $this->pdo->prepare("
            INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
            VALUES (:ped, :prod, 1, 6.50, 6.50)
        ");
        $stmtItem->execute(['ped' => $ped1Id, 'prod' => $prod1Id]);
        $item1Id = (int)$this->pdo->lastInsertId();

        // Ticket 1: Bruno
        $stmtTck = $this->pdo->prepare("
            INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, tag_alergenos)
            VALUES (:ped, :item, :codigo_ticket, :numero_via, :status, :tag_alergenos)
        ");
        $stmtTck->execute([
            'ped'           => $ped1Id,
            'item'          => $item1Id,
            'codigo_ticket' => 'TCK-F12-BRUNO-01',
            'numero_via'    => 1,
            'status'        => 'ativo',
            'tag_alergenos' => null
        ]);
        $this->ticketsCriados[] = (int)$this->pdo->lastInsertId();

        // Pedido 2: Ana (Turma A, Ordem 2) - Com tag de alérgeno
        $stmtPed->execute([
            'resp'  => $respId,
            'aluno' => $aluno2Id,
            'cod'   => 'PED-F12-0002',
            'data'  => $this->dataTeste
        ]);
        $ped2Id = (int)$this->pdo->lastInsertId();
        $this->pedidosCriados[] = $ped2Id;

        $stmtItem->execute(['ped' => $ped2Id, 'prod' => $prod2Id]);
        $item2Id = (int)$this->pdo->lastInsertId();

        // Ticket 2: Ana (com tag de alérgeno)
        $stmtTck->execute([
            'ped'           => $ped2Id,
            'item'          => $item2Id,
            'codigo_ticket' => 'TCK-F12-ANA-01',
            'numero_via'    => 1,
            'status'        => 'ativo',
            'tag_alergenos' => '[ATENÇÃO: CONTÉM GLÚTEN]'
        ]);
        $this->ticketsCriados[] = (int)$this->pdo->lastInsertId();

        // Pedido 3: Carlos (Turma A, Ordem 2)
        $stmtPed->execute([
            'resp'  => $respId,
            'aluno' => $aluno3Id,
            'cod'   => 'PED-F12-0003',
            'data'  => $this->dataTeste
        ]);
        $ped3Id = (int)$this->pdo->lastInsertId();
        $this->pedidosCriados[] = $ped3Id;

        $stmtItem->execute(['ped' => $ped3Id, 'prod' => $prod1Id]);
        $item3Id = (int)$this->pdo->lastInsertId();

        // Ticket 3: Carlos
        $stmtTck->execute([
            'ped'           => $ped3Id,
            'item'          => $item3Id,
            'codigo_ticket' => 'TCK-F12-CARLOS-01',
            'numero_via'    => 1,
            'status'        => 'ativo',
            'tag_alergenos' => null
        ]);
        $this->ticketsCriados[] = (int)$this->pdo->lastInsertId();

        // 6. Cria usuário Operador de Teste
        $stmtUser = $this->pdo->prepare("
            INSERT INTO usuarios (nome, usuario, email, senha_hash, perfil, ativo)
            VALUES ('Operador F12 Teste', 'operador-f12', 'operador.f12@monteiro.teste', 'hash', 'operador', 1)
        ");
        $stmtUser->execute();
        $this->usuariosCriados[] = (int)$this->pdo->lastInsertId();

        echo "--> Fixtures preparadas com sucesso!\n\n";
    }

    private function teardownFixtures(): void
    {
        echo "\n--> Executando limpeza (teardown) de fixtures de teste...\n";

        if (!empty($this->auditoriasCriadas)) {
            $ids = implode(',', $this->auditoriasCriadas);
            $this->pdo->exec("DELETE FROM logs_auditoria WHERE id IN ({$ids})");
        }

        if (!empty($this->ticketsCriados)) {
            $ids = implode(',', $this->ticketsCriados);
            $this->pdo->exec("DELETE FROM tickets WHERE id IN ({$ids})");
        }

        if (!empty($this->pedidosCriados)) {
            $ids = implode(',', $this->pedidosCriados);
            $this->pdo->exec("DELETE FROM pedidos WHERE id IN ({$ids})");
        }

        if (!empty($this->produtosCriados)) {
            $ids = implode(',', $this->produtosCriados);
            $this->pdo->exec("DELETE FROM produtos WHERE id IN ({$ids})");
        }

        if (!empty($this->alunosCriados)) {
            $ids = implode(',', $this->alunosCriados);
            $this->pdo->exec("DELETE FROM alunos WHERE id IN ({$ids})");
        }

        if (!empty($this->responsaveisCriados)) {
            $ids = implode(',', $this->responsaveisCriados);
            $this->pdo->exec("DELETE FROM responsaveis WHERE id IN ({$ids})");
        }

        if (!empty($this->turmasCriadas)) {
            $ids = implode(',', $this->turmasCriadas);
            $this->pdo->exec("DELETE FROM turmas WHERE id IN ({$ids})");
        }

        if (!empty($this->usuariosCriados)) {
            $ids = implode(',', $this->usuariosCriados);
            $this->pdo->exec("DELETE FROM usuarios WHERE id IN ({$ids})");
        }

        echo "--> Limpeza concluída com sucesso.\n";
    }

    private function testarSchemaEPersistencia(): void
    {
        echo "1. Testando Schema e Persistência de Tickets e Auditoria...\n";

        $ticket = Ticket::buscarPorId($this->ticketsCriados[0]);
        $this->assert($ticket !== null, "Ticket::buscarPorId localizou o ticket recém-criado");
        $this->assert($ticket['codigo_ticket'] === 'TCK-F12-BRUNO-01', "Código do ticket coincide com TCK-F12-BRUNO-01");
        $this->assert($ticket['aluno_nome'] === 'Bruno Aluno Turma B', "Nome do aluno hidratado via JOIN com sucesso");
        $this->assert((int)$ticket['numero_via'] === 1, "Via inicial é estritamente 1");
        $this->assert($ticket['ticket_status'] === 'ativo', "Status inicial é 'ativo'");

        $ticketPorCod = Ticket::buscarPorCodigo('TCK-F12-BRUNO-01');
        $this->assert($ticketPorCod !== null && (int)$ticketPorCod['ticket_id'] === $this->ticketsCriados[0], "Ticket::buscarPorCodigo localizou com exatidão");
    }

    private function testarOrdenacaoEstritaELotes(): void
    {
        echo "\n2. Testando Ordenação Sequencial Estrita na Bobina Contínua...\n";

        // Regra FSD: turmas.ordem_impressao ASC, turmas.nome_turma ASC, alunos.nome ASC, tickets.id ASC
        // Turma B tem ordem_impressao = 1
        // Turma A tem ordem_impressao = 2, com alunos Ana (A) e Carlos (C)
        $tickets = Ticket::listarPorTurnoEData($this->dataTeste, 'matutino');

        $this->assert(count($tickets) === 3, "Foram retornados exatamente os 3 tickets do turno");

        // Primeiro ticket DEVE ser de Bruno (Turma B, Ordem 1)
        $this->assert($tickets[0]['codigo_ticket'] === 'TCK-F12-BRUNO-01', "1º ticket na bobina é da Turma B (Ordem 1: Bruno)");

        // Segundo ticket DEVE ser de Ana (Turma A, Ordem 2, Nome A)
        $this->assert($tickets[1]['codigo_ticket'] === 'TCK-F12-ANA-01', "2º ticket na bobina é da Turma A (Ordem 2: Ana)");

        // Terceiro ticket DEVE ser de Carlos (Turma A, Ordem 2, Nome C)
        $this->assert($tickets[2]['codigo_ticket'] === 'TCK-F12-CARLOS-01', "3º ticket na bobina é da Turma A (Ordem 2: Carlos)");
    }

    private function testarAgrupamentoPorTurma(): void
    {
        echo "\n3. Testando Agrupamento por Turma para Distribuição em Sala de Aula...\n";

        $tickets = Ticket::listarPorTurnoEData($this->dataTeste, 'matutino');
        $grupos = Ticket::agruparPorTurma($tickets);

        $this->assert(count($grupos) === 2, "Foram identificadas exatamente 2 turmas agrupadas");

        // Grupo 1: Turma B
        $this->assert($grupos[0]['ordem_impressao'] === 1, "Grupo 1 é Turma B com Ordem 1");
        $this->assert($grupos[0]['total_tickets'] === 1, "Turma B tem exatamente 1 ticket");
        $this->assert($grupos[0]['total_alunos'] === 1, "Turma B tem 1 aluno beneficiado");
        $this->assert($grupos[0]['total_com_alergenos'] === 0, "Turma B tem 0 alérgenos");

        // Grupo 2: Turma A
        $this->assert($grupos[1]['ordem_impressao'] === 2, "Grupo 2 é Turma A com Ordem 2");
        $this->assert($grupos[1]['total_tickets'] === 2, "Turma A tem exatamente 2 tickets");
        $this->assert($grupos[1]['total_alunos'] === 2, "Turma A tem 2 alunos distintos");
        $this->assert($grupos[1]['total_com_alergenos'] === 1, "Turma A tem 1 ticket com alerta de alérgenos");
    }

    private function testarCicloDeImpressao(): void
    {
        echo "\n4. Testando Ciclo de Impressão e Transição de Status...\n";

        $ticket1Id = $this->ticketsCriados[0];
        $linhas = Ticket::marcarComoImpresso($ticket1Id);

        $this->assert($linhas === 1, "Ticket::marcarComoImpresso atualizou 1 linha");

        $ticketAtualizado = Ticket::buscarPorId($ticket1Id);
        $this->assert($ticketAtualizado['ticket_status'] === Ticket::STATUS_IMPRESSO, "Status transitou para 'impresso'");
        $this->assert(!empty($ticketAtualizado['impresso_em']), "Data impresso_em foi registrada com timestamp");

        // Teste de métricas consolidadas
        $metricas = Ticket::obterMetricasTurno($this->dataTeste, 'matutino');
        $this->assert($metricas['total_tickets'] === 3, "Métrica total_tickets = 3");
        $this->assert($metricas['total_impressos'] === 1, "Métrica total_impressos = 1");
        $this->assert($metricas['total_pendentes'] === 2, "Métrica total_pendentes = 2");
        $this->assert($metricas['total_com_alergenos'] === 1, "Métrica total_com_alergenos = 1");
    }

    private function testarTagsAlergenos(): void
    {
        echo "\n5. Testando Marcação e Exibição de Tags de Alérgenos (RN-16 e RN-17)...\n";

        $ticketAna = Ticket::buscarPorId($this->ticketsCriados[1]);
        $this->assert(!empty($ticketAna['tag_alergenos']), "Ticket da Ana possui tag de alérgeno");
        $this->assert(str_contains($ticketAna['tag_alergenos'], 'GLÚTEN'), "Tag de alérgenos contém 'GLÚTEN'");

        $ticketCarlos = Ticket::buscarPorId($this->ticketsCriados[2]);
        $this->assert(empty($ticketCarlos['tag_alergenos']), "Ticket do Carlos NÃO possui restrição de alérgenos");
    }

    private function testarConsultaRapida(): void
    {
        echo "\n6. Testando Consulta Rápida de Tickets para Atendimento Presencial...\n";

        // Busca por matrícula
        $resMat = Ticket::buscarParaConsulta('MAT-F12-002', $this->dataTeste, 'matutino');
        $this->assert(count($resMat) === 1, "Busca por matrícula retornou 1 resultado");
        $this->assert($resMat[0]['aluno_nome'] === 'Ana Aluna Turma A', "Localizou aluna Ana pela matrícula");

        // Busca por código do ticket
        $resCod = Ticket::buscarParaConsulta('TCK-F12-BRUNO-01', $this->dataTeste, 'matutino');
        $this->assert(count($resCod) === 1, "Busca por código do ticket retornou 1 resultado");

        // Busca por turma
        $resTurma = Ticket::buscarParaConsulta('5º Ano A', $this->dataTeste, 'matutino');
        $this->assert(count($resTurma) === 2, "Busca por nome de turma retornou 2 alunos");
    }

    private function testarReemissaoSegundaViaEJustificativa(): void
    {
        echo "\n7. Testando Emissão de 2ª Via com Justificativa e Auditoria...\n";

        $ticketId = $this->ticketsCriados[2]; // Carlos
        $operadorId = $this->usuariosCriados[0];

        // 1. Tentar reemitir com justificativa curta (deve falhar)
        $falhouValidacao = false;
        try {
            Ticket::reimprimirSegundaVia($ticketId, $operadorId, 'abc', '127.0.0.1');
        } catch (\InvalidArgumentException $e) {
            $falhouValidacao = true;
        }
        $this->assert($falhouValidacao, "Reemissão com justificativa < 5 caracteres bloqueada com sucesso");

        // 2. Reemitir com justificativa válida
        $ticketReemitido = Ticket::reimprimirSegundaVia(
            $ticketId, 
            $operadorId, 
            'Aluno perdeu o ticket físico em sala de aula durante a aula de artes.', 
            '192.168.1.50'
        );

        $this->assert((int)$ticketReemitido['numero_via'] === 2, "Campo numero_via incrementado de 1 para 2 com sucesso");
        $this->assert($ticketReemitido['ticket_status'] === Ticket::STATUS_IMPRESSO, "Status do ticket reemitido é 'impresso'");
    }

    private function testarTrilhaDeAuditoria(): void
    {
        echo "\n8. Testando Gravação na Trilha Compulsória de Auditoria (logs_auditoria)...\n";

        $ticketId = $this->ticketsCriados[2]; // Carlos
        $logs = Auditoria::listarPorRegistro('tickets', $ticketId);

        $this->assert(count($logs) >= 1, "Registro de auditoria encontrado para o ticket reemitido");
        $this->assert($logs[0]['acao'] === Auditoria::ACAO_REIMPRESSAO_TICKET, "Ação registrada é 'reimpressao_ticket'");
        $this->assert(str_contains($logs[0]['justificativa'], 'Aluno perdeu o ticket físico'), "Justificativa completa preservada em auditoria");
        $this->assert($logs[0]['ip_origem'] === '192.168.1.50', "IP de origem do operador registrado com exatidão");

        // Registra ID para teardown
        foreach ($logs as $l) {
            $this->auditoriasCriadas[] = (int)$l['id'];
        }
    }

    private function testarGeracaoPdf80mm(): void
    {
        echo "\n9. Testando Geração Binária de PDF 1.4 para Bobina Térmica de 80mm...\n";

        $tickets = Ticket::listarPorTurnoEData($this->dataTeste, 'matutino');
        $pdf = PdfService::gerarTicketsLotePdf($tickets, [
            'data'  => $this->dataTeste,
            'turno' => 'matutino'
        ]);

        $this->assert(!empty($pdf), "PDF binário gerado não está vazio");
        $this->assert(str_starts_with($pdf, '%PDF-1.4'), "Header oficial compatível %PDF-1.4 presente");
        $this->assert(str_contains($pdf, '/MediaBox [0 0 226.77'), "Largura da página é exatamente 226.77 pt (80mm)");
        $this->assert(str_contains($pdf, 'MONTEIRO LOBATO'), "Contém cabeçalho institucional do Colégio (MONTEIRO LOBATO)");
        $this->assert(str_contains($pdf, 'startxref'), "Tabela de referências cruzadas xref e trailer presentes");

        // Teste de ticket individual PDF
        $ticketSingle = Ticket::buscarPorId($this->ticketsCriados[2]);
        $pdfSingle = PdfService::gerarTicketIndividualPdf($ticketSingle);
        $this->assert(!empty($pdfSingle) && str_contains($pdfSingle, '/MediaBox [0 0 226.77'), "PDF de ticket individual gerado em 80mm com sucesso");
    }

    private function testarEspelhoHtml80mm(): void
    {
        echo "\n10. Testando Espelho de Impressão HTML 80mm (@media print)...\n";

        $tickets = Ticket::listarPorTurnoEData($this->dataTeste, 'matutino');
        $html = PdfService::gerarEspelhoTicketsLoteHtml($tickets, [
            'data'  => $this->dataTeste,
            'turno' => 'matutino'
        ]);

        $this->assert(str_contains($html, 'size: 80mm auto'), "Regra CSS @page define tamanho 80mm auto");
        $this->assert(str_contains($html, '✂ - - - - - - - - - - - - - - - - - - - -'), "Separador visual de corte presente");
        $this->assert(str_contains($html, '[ATENÇÃO: CONTÉM GLÚTEN]'), "Tag de alérgenos destacada no HTML");
        $this->assert(str_contains($html, 'window.print()'), "Acionamento nativo de impressão via window.print()");
    }

    private function testarControllerEEndpoints(): void
    {
        echo "\n11. Testando Métodos e Permissões do TicketsController...\n";

        $controller = new TicketsController();
        $this->assert(method_exists($controller, 'index'), "TicketsController possui action 'index'");
        $this->assert(method_exists($controller, 'lote'), "TicketsController possui action 'lote'");
        $this->assert(method_exists($controller, 'lotePdf'), "TicketsController possui action 'lotePdf'");
        $this->assert(method_exists($controller, 'turma'), "TicketsController possui action 'turma'");
        $this->assert(method_exists($controller, 'turmaPdf'), "TicketsController possui action 'turmaPdf'");
        $this->assert(method_exists($controller, 'segundaVia'), "TicketsController possui action 'segundaVia'");
        $this->assert(method_exists($controller, 'imprimirTicket'), "TicketsController possui action 'imprimirTicket'");
        $this->assert(method_exists($controller, 'imprimirTicketPdf'), "TicketsController possui action 'imprimirTicketPdf'");

        // Valida RBAC em app/core/RBAC.php
        Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'operador-teste',
            'nome'    => 'Operador Teste',
            'email'   => 'operador@teste.com',
            'perfil'  => RBAC::ROLE_OPERADOR
        ]);
        $this->assert(RBAC::can('impressao_lote_tickets') === true, "Perfil 'operador' tem permissão 'impressao_lote_tickets'");
        $this->assert(RBAC::can('reimpressao_segunda_via') === true, "Perfil 'operador' tem permissão 'reimpressao_segunda_via'");

        Auth::loginUsuario([
            'id'      => 2,
            'usuario' => 'admin-teste',
            'nome'    => 'Admin Teste',
            'email'   => 'admin@teste.com',
            'perfil'  => RBAC::ROLE_ADMINISTRADOR
        ]);
        $this->assert(RBAC::can('impressao_lote_tickets') === true, "Perfil 'administrador' tem permissão 'impressao_lote_tickets'");

        Auth::loginResponsavel([
            'id'            => 3,
            'cpf'           => '11122233344',
            'nome'          => 'Resp Teste',
            'email'         => 'resp@teste.com',
            'whatsapp'      => '11999999999',
            'saldo_atual'   => 0.00,
            'bloqueado_med' => 0
        ]);
        $this->assert(RBAC::can('impressao_lote_tickets') === false, "Perfil 'responsavel' é terminantemente bloqueado");
        Auth::logout();
    }
}

// Execução direta
$teste = new Fase12Test();
$teste->run();
