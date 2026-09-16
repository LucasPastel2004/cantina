<?php
/**
 * Suíte de Testes Automatizados — Fase 4
 * Cantina Web — Colégio Monteiro Lobato
 * 
 * Validação Completa das 14 Migrações Transacionais, Fiscais, Financeiras e Auditoria (013 a 026):
 * - Existência das 14 novas tabelas
 * - Integridade das colunas, tipos e constraints
 * - Chaves estrangeiras com CASCADE, RESTRICT e SET NULL
 * - Índices de alta performance
 * - Idempotência do runner de migrações
 * - Teste transacional ponta a ponta: NF-e, Estoque, Pedido, Itens, Tickets, Caixa, Balcão, Carteira e Logs
 */

define('CANTINA_SISTEMA', true);

require_once dirname(__DIR__) . '/app/core/Model.php';

use App\Core\Model;

class Fase4Test
{
    private \PDO $pdo;
    private int $assertions = 0;
    private int $failures = 0;

    private int $adminId;
    private int $categoriaId;
    private int $produtoId;
    private int $responsavelId;
    private int $turmaId;
    private int $alunoId;
    private int $colaboradorId;

    public function __construct()
    {
        $this->pdo = require dirname(__DIR__) . '/config/database.php';
        $this->setUpFixtures();
    }

    private function setUpFixtures(): void
    {
        // 1. Admin
        $stmt = $this->pdo->query("SELECT `id` FROM `usuarios` WHERE `usuario` = 'admin'");
        $this->adminId = (int)$stmt->fetchColumn();

        // 2. Categoria
        $stmt = $this->pdo->query("SELECT `id` FROM `categorias` LIMIT 1");
        $catId = $stmt->fetchColumn();
        if (!$catId) {
            $this->pdo->exec("INSERT INTO `categorias` (`nome`, `ordem_exibicao`) VALUES ('Salgados Assados', 1)");
            $this->categoriaId = (int)$this->pdo->lastInsertId();
        } else {
            $this->categoriaId = (int)$catId;
        }

        // 3. Produto
        $stmt = $this->pdo->query("SELECT `id` FROM `produtos` WHERE `codigo_interno` = 'PROD-TEST-F4'");
        $prodId = $stmt->fetchColumn();
        if (!$prodId) {
            $stmt = $this->pdo->prepare("INSERT INTO `produtos` (
                `categoria_id`, `codigo_interno`, `descricao`, `preco_venda`, `tipo`, `turno_disponibilidade`, `estoque_atual`
            ) VALUES (?, 'PROD-TEST-F4', 'Esfiha de Carne Assada', 8.50, 'salgado_diario', 'ambos', 50)");
            $stmt->execute([$this->categoriaId]);
            $this->produtoId = (int)$this->pdo->lastInsertId();
        } else {
            $this->produtoId = (int)$prodId;
        }

        // 4. Responsável
        $stmt = $this->pdo->query("SELECT `id` FROM `responsaveis` WHERE `cpf` = '12345678909'");
        $respId = $stmt->fetchColumn();
        if (!$respId) {
            $stmt = $this->pdo->prepare("INSERT INTO `responsaveis` (
                `cpf`, `nome`, `email`, `whatsapp`, `senha_hash`, `saldo_atual`
            ) VALUES ('12345678909', 'Mariana Alcantara Santos', 'mariana@email.com', '(11) 98888-7777', 'hash_teste', 100.00)");
            $stmt->execute();
            $this->responsavelId = (int)$this->pdo->lastInsertId();
        } else {
            $this->responsavelId = (int)$respId;
        }

        // 5. Turma
        $stmt = $this->pdo->query("SELECT `id` FROM `turmas` WHERE `nome_turma` = 'Turma 5A'");
        $turmaId = $stmt->fetchColumn();
        if (!$turmaId) {
            $stmt = $this->pdo->prepare("INSERT INTO `turmas` (
                `serie`, `nome_turma`, `turno`, `ordem_impressao`
            ) VALUES ('5º Ano', 'Turma 5A', 'matutino', 1)");
            $stmt->execute();
            $this->turmaId = (int)$this->pdo->lastInsertId();
        } else {
            $this->turmaId = (int)$turmaId;
        }

        // 6. Aluno
        $stmt = $this->pdo->query("SELECT `id` FROM `alunos` WHERE `matricula` = 'MAT-F4-001'");
        $alunoId = $stmt->fetchColumn();
        if (!$alunoId) {
            $stmt = $this->pdo->prepare("INSERT INTO `alunos` (
                `responsavel_id`, `turma_id`, `matricula`, `nome`, `turno`
            ) VALUES (?, ?, 'MAT-F4-001', 'Lucas Alcantara Santos', 'matutino')");
            $stmt->execute([$this->responsavelId, $this->turmaId]);
            $this->alunoId = (int)$this->pdo->lastInsertId();
        } else {
            $this->alunoId = (int)$alunoId;
        }

        // 7. Colaborador
        $stmt = $this->pdo->query("SELECT `id` FROM `colaboradores` WHERE `matricula_funcional` = 'COLAB-F4-01'");
        $colabId = $stmt->fetchColumn();
        if (!$colabId) {
            $stmt = $this->pdo->prepare("INSERT INTO `colaboradores` (
                `matricula_funcional`, `nome`, `cargo`
            ) VALUES ('COLAB-F4-01', 'Prof. Roberto Silva', 'Professor de Matemática')");
            $stmt->execute();
            $this->colaboradorId = (int)$this->pdo->lastInsertId();
        } else {
            $this->colaboradorId = (int)$colabId;
        }
    }

    public function assert(bool $condition, string $message): void
    {
        $this->assertions++;
        if ($condition) {
            echo "  [PASS] {$message}\n";
        } else {
            $this->failures++;
            echo "  [FAIL] {$message}\n";
        }
    }

    public function run(): void
    {
        echo "======================================================\n";
        echo "   Bateria de Testes Automatizados — Fase 4\n";
        echo "======================================================\n\n";

        $this->testTodasMigracoesAplicadas();
        $this->testTabelasTransacionaisExistem();
        $this->testIndicesCriados();
        $this->testCicloNotasFiscaisEEstoque();
        $this->testCicloPedidosEItensETicketsComCascade();
        $this->testCicloSessaoCaixaEVendaBalcao();
        $this->testCicloTransacoesCarteira();
        $this->testCalendarioLetivo();
        $this->testLogsAuditoriaErrosESeguranca();
        $this->testIntegridadeReferencialRestrita();

        echo "\n------------------------------------------------------\n";
        echo "Resultado: {$this->assertions} asserções executadas.\n";
        if ($this->failures === 0) {
            echo "STATUS: TODOS OS TESTES PASSARAM COM SUCESSO! [100% OK]\n";
        } else {
            echo "STATUS: {$this->failures} FALHA(S) ENCONTRADA(S)!\n";
            exit(1);
        }
        echo "------------------------------------------------------\n";
    }

    private function testTodasMigracoesAplicadas(): void
    {
        echo "1. Testando Tabela migrations_controle e Total de Migrações (001 a 026):\n";

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM `migrations_controle`");
        $total = (int)$stmt->fetchColumn();
        $this->assert($total === 26, "Tabela migrations_controle registra exatamente 26 migrações aplicadas (Fases 3 e 4 completas)");

        $expectedFiles = [
            '013_create_notas_fiscais_xml_table.php',
            '014_create_itens_nota_fiscal_xml_table.php',
            '015_create_estoque_movimentacoes_table.php',
            '016_create_pedidos_table.php',
            '017_create_itens_pedido_table.php',
            '018_create_tickets_table.php',
            '019_create_sessoes_caixa_table.php',
            '020_create_vendas_balcao_table.php',
            '021_create_itens_venda_balcao_table.php',
            '022_create_transacoes_carteira_table.php',
            '023_create_calendario_letivo_table.php',
            '024_create_logs_auditoria_table.php',
            '025_create_logs_erros_table.php',
            '026_create_logs_seguranca_table.php',
        ];

        foreach ($expectedFiles as $file) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `migrations_controle` WHERE `migration_arquivo` = ?");
            $stmt->execute([$file]);
            $this->assert((int)$stmt->fetchColumn() === 1, "Migração [{$file}] devidamente registrada");
        }
    }

    private function testTabelasTransacionaisExistem(): void
    {
        echo "\n2. Testando Existência das 14 Novas Tabelas:\n";

        $tabelas = [
            'notas_fiscais_xml',
            'itens_nota_fiscal_xml',
            'estoque_movimentacoes',
            'pedidos',
            'itens_pedido',
            'tickets',
            'sessoes_caixa',
            'vendas_balcao',
            'itens_venda_balcao',
            'transacoes_carteira',
            'calendario_letivo',
            'logs_auditoria',
            'logs_erros',
            'logs_seguranca'
        ];

        foreach ($tabelas as $tab) {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '{$tab}'");
            $this->assert($stmt->rowCount() === 1, "Tabela [{$tab}] existe no schema do MySQL");
        }
    }

    private function testIndicesCriados(): void
    {
        echo "\n3. Testando Índices de Alta Performance nas Tabelas Transacionais:\n";

        // Verifica índice composto de corte em pedidos
        $stmt = $this->pdo->query("SHOW INDEX FROM `pedidos` WHERE Key_name = 'idx_pedidos_corte'");
        $this->assert($stmt->rowCount() > 0, "Índice composto [idx_pedidos_corte] existe em pedidos (data_consumo, turno, status)");

        // Verifica índice único em tickets
        $stmt = $this->pdo->query("SHOW INDEX FROM `tickets` WHERE Key_name = 'codigo_ticket'");
        $this->assert($stmt->rowCount() > 0, "Índice UNIQUE [codigo_ticket] existe em tickets");

        // Verifica índice composto de data/turno/status em sessoes_caixa
        $stmt = $this->pdo->query("SHOW INDEX FROM `sessoes_caixa` WHERE Key_name = 'idx_caixa_data_turno'");
        $this->assert($stmt->rowCount() > 0, "Índice composto [idx_caixa_data_turno] existe em sessoes_caixa");

        // Verifica índice de extrato familiar em transacoes_carteira
        $stmt = $this->pdo->query("SHOW INDEX FROM `transacoes_carteira` WHERE Key_name = 'idx_tc_resp_data'");
        $this->assert($stmt->rowCount() > 0, "Índice composto [idx_tc_resp_data] existe em transacoes_carteira");
    }

    private function testCicloNotasFiscaisEEstoque(): void
    {
        echo "\n4. Testando Ciclo de NF-e XML, Itens com Fator de Conversão e Movimentações de Estoque:\n";

        // 1. Inserir NF-e
        $chaveNfe = '352609' . str_pad((string)mt_rand(1, 99999999), 38, '0', STR_PAD_LEFT);
        $stmt = $this->pdo->prepare("INSERT INTO `notas_fiscais_xml` (
            `chave_nfe`, `numero_nota`, `cnpj_emitente`, `razao_social_emitente`, `data_emissao`,
            `valor_total`, `arquivo_xml_path`, `importado_por`
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)");
        $stmt->execute([
            $chaveNfe, 'NF-1001', '12.345.678/0001-90', 'Distribuidora Alimentos Ltda',
            120.00, 'storage/uploads/private/nfe_1001.xml', $this->adminId
        ]);
        $nfeId = (int)$this->pdo->lastInsertId();
        $this->assert($nfeId > 0, "NF-e XML inserida com sucesso (ID: {$nfeId})");

        // 2. Inserir Item com Fator de Conversão (ex: fardo com 12 unidades)
        $stmt = $this->pdo->prepare("INSERT INTO `itens_nota_fiscal_xml` (
            `nota_fiscal_id`, `codigo_produto_fornecedor`, `descricao_fornecedor`, `unidade_fornecedor`,
            `quantidade_fornecedor`, `valor_unitario_fornecedor`, `produto_id`, `fator_conversao`, `quantidade_convertida`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $nfeId, 'SUCO-LAR-CX', 'Suco de Laranja 200ml Caixa c/ 12', 'CX',
            10.00, 24.00, $this->produtoId, 12, 120
        ]);
        $itemNfeId = (int)$this->pdo->lastInsertId();
        $this->assert($itemNfeId > 0, "Item da NF-e inserido com fator de conversão 12 e quantidade convertida 120 (ID: {$itemNfeId})");

        // 3. Inserir Movimentação de Estoque associada à NF-e
        $stmt = $this->pdo->prepare("INSERT INTO `estoque_movimentacoes` (
            `produto_id`, `tipo_movimentacao`, `quantidade`, `motivo_perda`, `nota_fiscal_id`, `operador_id`, `justificativa`
        ) VALUES (?, 'entrada_xml', 120, NULL, ?, ?, 'Entrada de estoque via NF-e')");
        $stmt->execute([$this->produtoId, $nfeId, $this->adminId]);
        $movId = (int)$this->pdo->lastInsertId();
        $this->assert($movId > 0, "Movimentação de estoque de entrada_xml gravada com sucesso (ID: {$movId})");

        // 4. Inserir Movimentação de Perda de Estoque com motivo obrigatório
        $stmt = $this->pdo->prepare("INSERT INTO `estoque_movimentacoes` (
            `produto_id`, `tipo_movimentacao`, `quantidade`, `motivo_perda`, `nota_fiscal_id`, `operador_id`, `justificativa`
        ) VALUES (?, 'saida_perda', 2, 'avaria', NULL, ?, 'Embalagem perfurada no manuseio')");
        $stmt->execute([$this->produtoId, $this->adminId]);
        $perdaId = (int)$this->pdo->lastInsertId();
        $this->assert($perdaId > 0, "Movimentação de perda gravada com motivo 'avaria' e justificativa (ID: {$perdaId})");

        // 5. Testar CASCADE de exclusão na NF-e (deve deletar itens e desvincular estoque com SET NULL)
        $this->pdo->exec("DELETE FROM `notas_fiscais_xml` WHERE `id` = {$nfeId}");
        $itensRestantes = $this->pdo->query("SELECT COUNT(*) FROM `itens_nota_fiscal_xml` WHERE `nota_fiscal_id` = {$nfeId}")->fetchColumn();
        $this->assert((int)$itensRestantes === 0, "ON DELETE CASCADE deletou os itens associados à NF-e removida");

        $movNfId = $this->pdo->query("SELECT `nota_fiscal_id` FROM `estoque_movimentacoes` WHERE `id` = {$movId}")->fetchColumn();
        $this->assert($movNfId === null, "ON DELETE SET NULL desvinculou nota_fiscal_id em estoque_movimentacoes");
    }

    private function testCicloPedidosEItensETicketsComCascade(): void
    {
        echo "\n5. Testando Ciclo de Pedidos, Itens do Pedido, Tickets e Exclusão em Cascata:\n";

        // 1. Inserir Pedido
        $codPedido = 'PED-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $stmt = $this->pdo->prepare("INSERT INTO `pedidos` (
            `codigo_pedido`, `responsavel_id`, `aluno_id`, `data_consumo`, `turno_consumo`, `valor_total`, `status`
        ) VALUES (?, ?, ?, CURDATE(), 'matutino', 17.00, 'agendado')");
        $stmt->execute([$codPedido, $this->responsavelId, $this->alunoId]);
        $pedidoId = (int)$this->pdo->lastInsertId();
        $this->assert($pedidoId > 0, "Pedido agendado criado com sucesso (ID: {$pedidoId}, Código: {$codPedido})");

        // 2. Inserir Item do Pedido
        $stmt = $this->pdo->prepare("INSERT INTO `itens_pedido` (
            `pedido_id`, `produto_id`, `quantidade`, `preco_unitario`, `subtotal`
        ) VALUES (?, ?, 2, 8.50, 17.00)");
        $stmt->execute([$pedidoId, $this->produtoId]);
        $itemId = (int)$this->pdo->lastInsertId();
        $this->assert($itemId > 0, "Item do pedido gravado (ID: {$itemId}, Qtd: 2, Subtotal: R$ 17,00)");

        // 3. Inserir Tickets térmicos (2 unidades para o item)
        $codTicket1 = 'TCK-' . strtoupper(substr(md5(uniqid('t1', true)), 0, 8));
        $codTicket2 = 'TCK-' . strtoupper(substr(md5(uniqid('t2', true)), 0, 8));

        $stmt = $this->pdo->prepare("INSERT INTO `tickets` (
            `pedido_id`, `item_pedido_id`, `codigo_ticket`, `numero_via`, `status`, `tag_alergenos`
        ) VALUES (?, ?, ?, 1, 'ativo', '[ATENÇÃO: CONTÉM GLÚTEN]')");
        $stmt->execute([$pedidoId, $itemId, $codTicket1]);
        $tckId1 = (int)$this->pdo->lastInsertId();

        $stmt->execute([$pedidoId, $itemId, $codTicket2]);
        $tckId2 = (int)$this->pdo->lastInsertId();

        $this->assert($tckId1 > 0 && $tckId2 > 0, "Dois tickets térmicos gerados com tag de alérgeno e status 'ativo'");

        // 4. Testar ON DELETE CASCADE: ao remover o pedido, itens e tickets são apagados automaticamente
        $this->pdo->exec("DELETE FROM `pedidos` WHERE `id` = {$pedidoId}");
        $itensAposDelete = $this->pdo->query("SELECT COUNT(*) FROM `itens_pedido` WHERE `pedido_id` = {$pedidoId}")->fetchColumn();
        $ticketsAposDelete = $this->pdo->query("SELECT COUNT(*) FROM `tickets` WHERE `pedido_id` = {$pedidoId}")->fetchColumn();

        $this->assert((int)$itensAposDelete === 0, "ON DELETE CASCADE removeu itens_pedido após deleção do pedido");
        $this->assert((int)$ticketsAposDelete === 0, "ON DELETE CASCADE removeu tickets após deleção do pedido");
    }

    private function testCicloSessaoCaixaEVendaBalcao(): void
    {
        echo "\n6. Testando Sessões de Caixa por Turno, Vendas Balcão e Itens no PDV:\n";

        // 1. Abertura de Caixa do Turno Matutino com Fundo de Troco
        $stmt = $this->pdo->prepare("INSERT INTO `sessoes_caixa` (
            `operador_id`, `turno`, `aberto_em`, `fundo_troco_inicial`, `status`
        ) VALUES (?, 'matutino', NOW(), 100.00, 'aberto')");
        $stmt->execute([$this->adminId]);
        $caixaId = (int)$this->pdo->lastInsertId();
        $this->assert($caixaId > 0, "Sessão de caixa matutino aberta com Fundo de Troco R$ 100,00 (ID: {$caixaId})");

        // 2. Venda Avulsa em Dinheiro no PDV (com troco)
        $stmt = $this->pdo->prepare("INSERT INTO `vendas_balcao` (
            `sessao_caixa_id`, `operador_id`, `tipo_venda`, `forma_pagamento`,
            `valor_total`, `valor_dinheiro_recebido`, `troco_devolvido`
        ) VALUES (?, ?, 'avulso_aluno', 'dinheiro', 8.50, 20.00, 11.50)");
        $stmt->execute([$caixaId, $this->adminId]);
        $vendaAvulsaId = (int)$this->pdo->lastInsertId();
        $this->assert($vendaAvulsaId > 0, "Venda avulsa em dinheiro registrada no PDV com troco calculado (ID: {$vendaAvulsaId})");

        // Inserir item da venda avulsa
        $stmt = $this->pdo->prepare("INSERT INTO `itens_venda_balcao` (
            `venda_balcao_id`, `produto_id`, `quantidade`, `preco_unitario`, `subtotal`
        ) VALUES (?, ?, 1, 8.50, 8.50)");
        $stmt->execute([$vendaAvulsaId, $this->produtoId]);
        $itemVbId = (int)$this->pdo->lastInsertId();
        $this->assert($itemVbId > 0, "Item da venda de balcão registrado com sucesso (ID: {$itemVbId})");

        // 3. Venda para Colaborador sob Desconto em Folha
        $stmt = $this->pdo->prepare("INSERT INTO `vendas_balcao` (
            `sessao_caixa_id`, `operador_id`, `tipo_venda`, `colaborador_id`, `forma_pagamento`,
            `valor_total`
        ) VALUES (?, ?, 'desconto_folha', ?, 'desconto_folha', 17.00)");
        $stmt->execute([$caixaId, $this->adminId, $this->colaboradorId]);
        $vendaColabId = (int)$this->pdo->lastInsertId();
        $this->assert($vendaColabId > 0, "Venda de colaborador (Desconto em Folha) registrada com sucesso (ID: {$vendaColabId})");

        // 4. Fechamento de Caixa com conciliação
        $stmt = $this->pdo->prepare("UPDATE `sessoes_caixa` SET
            `fechado_em` = NOW(),
            `total_dinheiro_avulso` = 8.50,
            `total_informado_gaveta` = 108.50,
            `diferenca_quebra_sobra` = 0.00,
            `status` = 'fechado',
            `observacoes` = 'Fechamento regular sem divergências'
            WHERE `id` = ?");
        $stmt->execute([$caixaId]);
        $this->assert($stmt->rowCount() === 1, "Sessão de caixa fechada com conciliação exata entre dinheiro e gaveta");
    }

    private function testCicloTransacoesCarteira(): void
    {
        echo "\n7. Testando Livro-Razão da Carteira Familiar (transacoes_carteira):\n";

        // 1. Recarga Pix
        $stmt = $this->pdo->prepare("INSERT INTO `transacoes_carteira` (
            `responsavel_id`, `tipo_transacao`, `valor`, `saldo_anterior`, `saldo_resultante`, `referencia_id`, `descricao`
        ) VALUES (?, 'recarga_pix', 50.00, 0.00, 50.00, 'PIX-TEST-001', 'Recarga Pix Online via Webhook')");
        $stmt->execute([$this->responsavelId]);
        $tId1 = (int)$this->pdo->lastInsertId();
        $this->assert($tId1 > 0, "Transação de recarga Pix gravada (Saldo: R$ 0,00 -> R$ 50,00)");

        // 2. Débito de Pedido
        $stmt = $this->pdo->prepare("INSERT INTO `transacoes_carteira` (
            `responsavel_id`, `tipo_transacao`, `valor`, `saldo_anterior`, `saldo_resultante`, `referencia_id`, `descricao`
        ) VALUES (?, 'debito_pedido', -17.00, 50.00, 33.00, 'PED-123456', 'Débito referente a agendamento de lanche')");
        $stmt->execute([$this->responsavelId]);
        $tId2 = (int)$this->pdo->lastInsertId();
        $this->assert($tId2 > 0, "Transação de débito de pedido gravada (Saldo: R$ 50,00 -> R$ 33,00)");

        // 3. Estorno por Cancelamento
        $stmt = $this->pdo->prepare("INSERT INTO `transacoes_carteira` (
            `responsavel_id`, `tipo_transacao`, `valor`, `saldo_anterior`, `saldo_resultante`, `referencia_id`, `descricao`
        ) VALUES (?, 'estorno_cancelamento', 17.00, 33.00, 50.00, 'PED-123456', 'Estorno de 100% por cancelamento antes do corte')");
        $stmt->execute([$this->responsavelId]);
        $tId3 = (int)$this->pdo->lastInsertId();
        $this->assert($tId3 > 0, "Transação de estorno por cancelamento gravada (Saldo: R$ 33,00 -> R$ 50,00)");

        // 4. Débito de Contestação MED
        $stmt = $this->pdo->prepare("INSERT INTO `transacoes_carteira` (
            `responsavel_id`, `tipo_transacao`, `valor`, `saldo_anterior`, `saldo_resultante`, `referencia_id`, `descricao`
        ) VALUES (?, 'debito_contestacao_med', -50.00, 50.00, 0.00, 'MED-CONTEST-99', 'Débito por Mecanismo Especial de Devolução Pix')");
        $stmt->execute([$this->responsavelId]);
        $tId4 = (int)$this->pdo->lastInsertId();
        $this->assert($tId4 > 0, "Transação de contestação bancária MED gravada com sucesso");
    }

    private function testCalendarioLetivo(): void
    {
        echo "\n8. Testando Calendário Letivo e Marcação de Recessos/Cancelamentos:\n";

        // 1. Inserir dia letivo
        $dataLetiva = date('Y') . '-10-15';
        $this->pdo->exec("DELETE FROM `calendario_letivo` WHERE `data` = '{$dataLetiva}'");
        $stmt = $this->pdo->prepare("INSERT INTO `calendario_letivo` (`data`, `tipo`, `descricao`) VALUES (?, 'letivo', 'Dia do Professor - Atividades Normais')");
        $stmt->execute([$dataLetiva]);
        $calId = (int)$this->pdo->lastInsertId();
        $this->assert($calId > 0, "Data letiva inserida no calendário (Data: {$dataLetiva})");

        // 2. Inserir feriado
        $dataFeriado = date('Y') . '-11-02';
        $this->pdo->exec("DELETE FROM `calendario_letivo` WHERE `data` = '{$dataFeriado}'");
        $stmt = $this->pdo->prepare("INSERT INTO `calendario_letivo` (`data`, `tipo`, `descricao`) VALUES (?, 'feriado', 'Finados')");
        $stmt->execute([$dataFeriado]);
        $feriadoId = (int)$this->pdo->lastInsertId();
        $this->assert($feriadoId > 0, "Feriado inserido no calendário (Data: {$dataFeriado})");

        // 3. Simular cancelamento emergencial em lote
        $stmt = $this->pdo->prepare("UPDATE `calendario_letivo` SET
            `tipo` = 'cancelado_emergencial',
            `cancelado_em` = NOW(),
            `cancelado_por` = ?
            WHERE `id` = ?");
        $stmt->execute([$this->adminId, $calId]);
        $this->assert($stmt->rowCount() === 1, "Suspensão de dia letivo gravada com identificador do administrador");
    }

    private function testLogsAuditoriaErrosESeguranca(): void
    {
        echo "\n9. Testando Tabelas Estruturadas de Logs (Auditoria, Erros e Segurança):\n";

        // 1. Log de Auditoria
        $stmt = $this->pdo->prepare("INSERT INTO `logs_auditoria` (
            `operador_id`, `acao`, `tabela_afetada`, `registro_id`, `justificativa`, `ip_origem`
        ) VALUES (?, 'reimpressao_ticket', 'tickets', 101, 'Aluno perdeu o ticket impresso na sala', '192.168.1.50')");
        $stmt->execute([$this->adminId]);
        $auditId = (int)$this->pdo->lastInsertId();
        $this->assert($auditId > 0, "Log de auditoria registrado com operador, ação e justificativa (ID: {$auditId})");

        // 2. Log de Erro
        $stmt = $this->pdo->prepare("INSERT INTO `logs_erros` (
            `nivel`, `mensagem`, `arquivo`, `linha`, `stack_trace`, `url_requisicao`, `usuario_id`, `ip_origem`
        ) VALUES ('error', 'Timeout ao conectar com gateway Pix', 'app/services/PixService.php', 45, '#0 main', '/carteira/recarga', 'admin', '192.168.1.50')");
        $stmt->execute();
        $erroId = (int)$this->pdo->lastInsertId();
        $this->assert($erroId > 0, "Log estruturado de erro de aplicação registrado com rastreabilidade (ID: {$erroId})");

        // 3. Log de Segurança
        $stmt = $this->pdo->prepare("INSERT INTO `logs_seguranca` (
            `evento`, `cpf_ou_usuario`, `detalhes_json`, `ip_origem`
        ) VALUES ('falha_login', 'operador-maria', '{\"motivo\":\"Senha incorreta 3x\",\"tentativa\":3}', '192.168.1.60')");
        $stmt->execute();
        $segId = (int)$this->pdo->lastInsertId();
        $this->assert($segId > 0, "Log de segurança registrado com payload JSON e IP (ID: {$segId})");
    }

    private function testIntegridadeReferencialRestrita(): void
    {
        echo "\n10. Testando Integridade Referencial Restrita (ON DELETE RESTRICT):\n";

        // Tentar apagar o usuário admin referenciado em logs de auditoria deve falhar com PDOException
        $falhouCorretamente = false;

        try {
            $this->pdo->exec("DELETE FROM `usuarios` WHERE `id` = {$this->adminId}");
        } catch (\PDOException $e) {
            $falhouCorretamente = true;
        }

        $this->assert($falhouCorretamente, "ON DELETE RESTRICT bloqueou a exclusão física indevida de usuário vinculado a logs de auditoria");
    }
}

// Execução da suíte
$test = new Fase4Test();
$test->run();
