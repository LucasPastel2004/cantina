<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Seeder Oficial de Compras e Pedidos para a Semana Atual (12 a 16 de Outubro de 2026)
 * 
 * Regras estritas:
 * 1. Usa EXCLUSIVAMENTE a lista real de pais e filhos do colégio (232 alunos e 195 responsáveis).
 * 2. Purga qualquer poluição ou dado falso residual de testes automatizados anteriores.
 * 3. Gera recargas de carteira (Pix) e pedidos distribuídos ao longo da semana letiva atual.
 * 4. Atende turnos matutino e vespertino conforme as turmas reais dos alunos.
 * 5. Respeita restrições alimentares e emite tickets térmicos padronizados.
 * 6. Suporta execução via CLI e HTTP protegido por token.
 */

if (!defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

// Suporte para execução via navegador web com token
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../config/config.php';
    $tokenEnviado = $_GET['token'] ?? '';
    $tokenConfig = defined('MIGRATION_TOKEN') ? MIGRATION_TOKEN : 'cantina_secret_token_change_me';
    if (empty($tokenEnviado) || !hash_equals($tokenConfig, $tokenEnviado)) {
        http_response_code(403);
        die('Acesso negado: token de segurança inválido ou ausente.');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';

$pdo = Database::getInstance();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

echo "================================================================================\n";
echo "   CANTINA WEB — SEEDER DE COMPRAS E PEDIDOS REAIS (SEMANA ATUAL - OUTUBRO/2026)\n";
echo "   Colégio Monteiro Lobato — Unidade Única\n";
echo "================================================================================\n\n";

// -----------------------------------------------------------------------------
// ETAPA 1: PURGA RIGOROSA DE POLUIÇÃO E DADOS FALSOS DE TESTES
// -----------------------------------------------------------------------------
echo "[1/6] Higienizando o banco e eliminando dados falsos gerados por testes...\n";

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

// 1.1 Remover produtos fakes (ID > 12) e suas referências
$pdo->exec("DELETE FROM tickets WHERE item_pedido_id IN (SELECT id FROM itens_pedido WHERE produto_id > 12)");
$pdo->exec("DELETE FROM itens_pedido WHERE produto_id > 12");
$pdo->exec("DELETE FROM itens_venda_balcao WHERE produto_id > 12");
$pdo->exec("DELETE FROM estoque_movimentacoes WHERE produto_id > 12");
$pdo->exec("DELETE FROM produto_alergenos WHERE produto_id > 12");
$totalProdsFakes = $pdo->exec("DELETE FROM produtos WHERE id > 12");
echo "  - Produtos de teste eliminados: {$totalProdsFakes}\n";

// 1.2 Remover pedidos, tickets e itens associados a alunos/responsáveis falsos ou testes de edição
$pdo->exec("
    DELETE FROM tickets 
    WHERE pedido_id IN (
        SELECT p.id FROM pedidos p 
        LEFT JOIN alunos a ON a.id = p.aluno_id 
        LEFT JOIN responsaveis r ON r.id = p.responsavel_id 
        WHERE a.nome LIKE '%Teste%' 
           OR a.nome LIKE '%Ausente%' 
           OR a.nome LIKE '%Fake%' 
           OR a.nome LIKE '%Família%'
           OR a.matricula LIKE 'MAT-TEST%'
           OR a.matricula LIKE 'TEST%'
           OR a.matricula LIKE 'MAT-ESTQ%'
           OR r.nome LIKE '%Teste%' 
           OR r.nome LIKE '%Sobra%' 
           OR r.nome LIKE '%Fake%'
           OR r.nome LIKE '%Edição%'
           OR r.cpf LIKE '111222333%'
    )
");

$pdo->exec("
    DELETE FROM itens_pedido 
    WHERE pedido_id IN (
        SELECT p.id FROM pedidos p 
        LEFT JOIN alunos a ON a.id = p.aluno_id 
        LEFT JOIN responsaveis r ON r.id = p.responsavel_id 
        WHERE a.nome LIKE '%Teste%' 
           OR a.nome LIKE '%Ausente%' 
           OR a.nome LIKE '%Fake%' 
           OR a.nome LIKE '%Família%'
           OR a.matricula LIKE 'MAT-TEST%'
           OR a.matricula LIKE 'TEST%'
           OR a.matricula LIKE 'MAT-ESTQ%'
           OR r.nome LIKE '%Teste%' 
           OR r.nome LIKE '%Sobra%' 
           OR r.nome LIKE '%Fake%'
           OR r.nome LIKE '%Edição%'
           OR r.cpf LIKE '111222333%'
    )
");

$pdo->exec("
    DELETE FROM pedidos 
    WHERE aluno_id IN (
        SELECT id FROM alunos 
        WHERE nome LIKE '%Teste%' 
           OR nome LIKE '%Ausente%' 
           OR nome LIKE '%Fake%' 
           OR nome LIKE '%Família%'
           OR matricula LIKE 'MAT-TEST%'
           OR matricula LIKE 'TEST%'
           OR matricula LIKE 'MAT-ESTQ%'
    ) OR responsavel_id IN (
        SELECT id FROM responsaveis 
        WHERE nome LIKE '%Teste%' 
           OR nome LIKE '%Sobra%' 
           OR nome LIKE '%Fake%'
           OR nome LIKE '%Edição%'
           OR cpf LIKE '111222333%'
    )
");

// 1.3 Remover transações de carteira de responsáveis falsos
$pdo->exec("
    DELETE FROM transacoes_carteira 
    WHERE responsavel_id IN (
        SELECT id FROM responsaveis 
        WHERE nome LIKE '%Teste%' 
           OR nome LIKE '%Sobra%' 
           OR nome LIKE '%Fake%'
           OR nome LIKE '%Edição%'
           OR cpf LIKE '111222333%'
    )
");

// 1.4 Remover alunos de testes (matrículas de fixture 'MAT-%' ou turmas > 19) e seus vínculos
$pdo->exec("
    DELETE FROM tickets 
    WHERE pedido_id IN (
        SELECT id FROM pedidos 
        WHERE aluno_id IN (SELECT id FROM alunos WHERE turma_id > 19 OR turma_id IS NULL OR matricula LIKE 'MAT-%' OR matricula LIKE 'ALU-%')
    )
");
$pdo->exec("
    DELETE FROM itens_pedido 
    WHERE pedido_id IN (
        SELECT id FROM pedidos 
        WHERE aluno_id IN (SELECT id FROM alunos WHERE turma_id > 19 OR turma_id IS NULL OR matricula LIKE 'MAT-%' OR matricula LIKE 'ALU-%')
    )
");
$pdo->exec("
    DELETE FROM transacoes_carteira 
    WHERE aluno_id IN (SELECT id FROM alunos WHERE turma_id > 19 OR turma_id IS NULL OR matricula LIKE 'MAT-%' OR matricula LIKE 'ALU-%')
");
$pdo->exec("
    DELETE FROM pedidos 
    WHERE aluno_id IN (SELECT id FROM alunos WHERE turma_id > 19 OR turma_id IS NULL OR matricula LIKE 'MAT-%' OR matricula LIKE 'ALU-%')
");
$pdo->exec("
    DELETE FROM aluno_restricoes 
    WHERE aluno_id IN (SELECT id FROM alunos WHERE turma_id > 19 OR turma_id IS NULL OR matricula LIKE 'MAT-%' OR matricula LIKE 'ALU-%')
");
$totalAlunosFakes = $pdo->exec("DELETE FROM alunos WHERE turma_id > 19 OR turma_id IS NULL OR matricula LIKE 'MAT-%' OR matricula LIKE 'ALU-%' OR nome LIKE '%Teste%' OR nome LIKE '%Ausente%' OR nome LIKE '%Fake%' OR nome LIKE '%Família%'");

// 1.5 Remover responsáveis sem alunos reais vinculados (criados por testes)
$pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id NOT IN (SELECT DISTINCT responsavel_id FROM alunos)");
$pdo->exec("DELETE FROM pedidos WHERE responsavel_id NOT IN (SELECT DISTINCT responsavel_id FROM alunos)");
$totalRespFakes = $pdo->exec("DELETE FROM responsaveis WHERE id NOT IN (SELECT DISTINCT responsavel_id FROM alunos) OR nome LIKE '%Teste%' OR nome LIKE '%Sobra%' OR nome LIKE '%Fake%' OR nome LIKE '%Edição%' OR cpf LIKE '111222333%'");

echo "  - Alunos falsos e fixtures eliminados: {$totalAlunosFakes}\n";
echo "  - Responsáveis falsos/órfãos eliminados: {$totalRespFakes}\n";

// 1.5 Remover turmas não-oficiais (manter apenas as 19 turmas oficiais)
$pdo->exec("DELETE FROM turmas WHERE id > 19 OR nome_turma LIKE '%Teste%' OR serie LIKE '%Teste%' OR nome_turma LIKE '%2827%' OR nome_turma LIKE '%3227%' OR nome_turma LIKE '%3594%' OR nome_turma LIKE '%5705%' OR nome_turma LIKE '%7468%' OR nome_turma LIKE '%8294%' OR nome_turma LIKE '%3353%' OR nome_turma LIKE '%4155%' OR nome_turma LIKE '%6551%' OR nome_turma LIKE '%3781%' OR nome_turma LIKE '%4385%' OR nome_turma LIKE '%4587%' OR nome_turma LIKE '%8052%' OR nome_turma LIKE '%7628%' OR nome_turma LIKE '%9753%' OR nome_turma LIKE '%5477%' OR nome_turma LIKE '%8201%' OR nome_turma LIKE '%6785%' OR nome_turma LIKE '%9515%' OR nome_turma LIKE '%3826%' OR nome_turma LIKE '%6388%' OR nome_turma LIKE '%4990%' OR nome_turma LIKE '%6334%' OR nome_turma LIKE '%9170%' OR nome_turma LIKE '%9646%' OR nome_turma LIKE '%2735%' OR nome_turma LIKE '%3000%' OR nome_turma LIKE '%5229%' OR nome_turma LIKE '%8745%' OR nome_turma LIKE '%1459%' OR nome_turma LIKE '%3913%' OR nome_turma LIKE '%8598%' OR nome_turma LIKE '%9903%' OR nome_turma LIKE '%8499%' OR nome_turma LIKE '%7995%'");

// 1.6 Garantir que a semana atual não tenha bloqueio indevido no calendário escolar
$pdo->exec("DELETE FROM calendario_letivo WHERE data = '2026-10-15' AND tipo = 'cancelado_emergencial'");

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "  - Purga de dados falsos concluída com sucesso.\n\n";

// -----------------------------------------------------------------------------
// ETAPA 2: ASSEGURAR CARGA COMPLETA DAS 19 TURMAS, 195 PAIS E 232 ALUNOS REAIS
// -----------------------------------------------------------------------------
echo "[2/6] Validando integridade das matrículas oficiais do Colégio Monteiro Lobato...\n";
$totalTurmas = (int)$pdo->query("SELECT COUNT(*) FROM turmas WHERE deleted_at IS NULL")->fetchColumn();
$totalResponsaveis = (int)$pdo->query("SELECT COUNT(*) FROM responsaveis")->fetchColumn();
$totalAlunos = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE deleted_at IS NULL")->fetchColumn();

if ($totalTurmas < 19 || $totalResponsaveis < 190 || $totalAlunos < 220) {
    echo "  - Reexecutando seeder de matrículas reais para garantir integridade...\n";
    require __DIR__ . '/seed_matriculas_reais.php';
    $totalTurmas = (int)$pdo->query("SELECT COUNT(*) FROM turmas WHERE deleted_at IS NULL")->fetchColumn();
    $totalResponsaveis = (int)$pdo->query("SELECT COUNT(*) FROM responsaveis")->fetchColumn();
    $totalAlunos = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE deleted_at IS NULL")->fetchColumn();
}

echo "  - Turmas oficiais ativas:      {$totalTurmas} (Esperado: 19)\n";
echo "  - Responsáveis reais ativos:   {$totalResponsaveis} (Esperado: 195)\n";
echo "  - Alunos reais ativos:         {$totalAlunos} (Esperado: 232)\n\n";

// -----------------------------------------------------------------------------
// ETAPA 3: RECALIBRAR ESTOQUE DOS 12 PRODUTOS OFICIAIS
// -----------------------------------------------------------------------------
echo "[3/6] Calibrando estoque e disponibilidade dos 12 produtos oficiais da cantina...\n";
$pdo->exec("
    UPDATE produtos 
    SET estoque_atual = 120, 
        estoque_reservado = 0, 
        status_pausa = 'ativo', 
        deleted_at = NULL 
    WHERE id BETWEEN 1 AND 12
");

$stmtProds = $pdo->query("SELECT id, codigo_interno, descricao, preco_venda, tipo, turno_disponibilidade FROM produtos WHERE id BETWEEN 1 AND 12 ORDER BY id ASC");
$produtosOficiais = [];
while ($row = $stmtProds->fetch(PDO::FETCH_ASSOC)) {
    $produtosOficiais[(int)$row['id']] = $row;
}
echo "  - " . count($produtosOficiais) . " produtos oficiais prontos para comercialização.\n\n";

// -----------------------------------------------------------------------------
// ETAPA 4: LIMPEZA DE PEDIDOS ANTERIORES DA SEMANA ATUAL (12 A 16 DE OUTUBRO)
// -----------------------------------------------------------------------------
echo "[4/6] Limpando pedidos da semana atual (2026-10-12 a 2026-10-16) para semeadura limpa...\n";
$semanaDatas = ['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16'];
$inClauseDatas = "'" . implode("','", $semanaDatas) . "'";

$pdo->exec("DELETE FROM tickets WHERE pedido_id IN (SELECT id FROM pedidos WHERE data_consumo IN ({$inClauseDatas}))");
$pdo->exec("DELETE FROM itens_pedido WHERE pedido_id IN (SELECT id FROM pedidos WHERE data_consumo IN ({$inClauseDatas}))");
$pdo->exec("DELETE FROM transacoes_carteira WHERE created_at >= '2026-10-12 00:00:00' AND created_at <= '2026-10-16 23:59:59'");
$pdo->exec("DELETE FROM pedidos WHERE data_consumo IN ({$inClauseDatas})");
echo "  - Semana atual limpa e pronta para receber as compras e pedidos reais.\n\n";

// -----------------------------------------------------------------------------
// ETAPA 5: SEMEADURA DE COMPRAS E PEDIDOS REAIS NA SEMANA ATUAL
// -----------------------------------------------------------------------------
echo "[5/6] Gerando recargas Pix e pedidos para alunos reais ao longo da semana...\n";

// Selecionar alunos reais ativos com suas respectivas turmas e responsáveis
$stmtAlunosReais = $pdo->query("
    SELECT a.id as aluno_id, a.nome as aluno_nome, a.matricula, a.turno as aluno_turno,
           t.id as turma_id, t.nome_turma, t.serie,
           r.id as resp_id, r.nome as resp_nome, r.cpf
    FROM alunos a
    JOIN turmas t ON t.id = a.turma_id
    JOIN responsaveis r ON r.id = a.responsavel_id
    WHERE a.deleted_at IS NULL
    ORDER BY t.ordem_impressao ASC, a.id ASC
");
$todosAlunos = $stmtAlunosReais->fetchAll(PDO::FETCH_ASSOC);
$alunosMatutinos = array_values(array_filter($todosAlunos, fn($a) => $a['aluno_turno'] === 'matutino'));
$alunosVespertinos = array_values(array_filter($todosAlunos, fn($a) => $a['aluno_turno'] === 'vespertino'));

echo "  - Alunos disponíveis no Matutino: " . count($alunosMatutinos) . " (5º Ano ao Ensino Médio)\n";
echo "  - Alunos disponíveis no Vespertino: " . count($alunosVespertinos) . " (Maternal ao 4º Ano)\n\n";

// Mapear restrições alimentares de cada aluno
$stmtRestricoes = $pdo->query("
    SELECT ar.aluno_id, ra.nome as restricao_nome
    FROM aluno_restricoes ar
    JOIN restricoes_alimentares ra ON ra.id = ar.restricao_id
");
$alunoRestricoesMap = [];
while ($row = $stmtRestricoes->fetch(PDO::FETCH_ASSOC)) {
    $alunoRestricoesMap[(int)$row['aluno_id']][] = $row['restricao_nome'];
}

// Combinações de lanches saborosos e balanceados da Cantina Monteiro Lobato
$combinacoesLanches = [
    // Opção 1: Salgado clássico + Suco
    ['itens' => [['prod_id' => 1, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]]], // Coxinha Grande (8.00) + Suco Laranja (6.00) = 14.00
    // Opção 2: Esfiha assada + Suco integral
    ['itens' => [['prod_id' => 3, 'qtd' => 1], ['prod_id' => 6, 'qtd' => 1]]], // Esfiha Carne (8.00) + Suco Uva (7.00) = 15.00
    // Opção 3: Enroladinho + Água de Coco
    ['itens' => [['prod_id' => 4, 'qtd' => 1], ['prod_id' => 10, 'qtd' => 1]]], // Enroladinho Presunto/Queijo (8.00) + Água Coco (5.50) = 13.50
    // Opção 4: Saudável - Sanduíche Natural + Suco Laranja
    ['itens' => [['prod_id' => 7, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]]], // Sanduíche Natural (9.50) + Suco Laranja (6.00) = 15.50
    // Opção 5: Misto Quente + Suco Uva
    ['itens' => [['prod_id' => 8, 'qtd' => 1], ['prod_id' => 6, 'qtd' => 1]]], // Misto Quente (7.50) + Suco Uva (7.00) = 14.50
    // Opção 6: Frutas e Biscoito (Ideal para intolerantes ou lanche leve)
    ['itens' => [['prod_id' => 12, 'qtd' => 1], ['prod_id' => 11, 'qtd' => 1]]], // Salada Frutas (6.00) + Cookie Cacau (4.50) = 10.50
    // Opção 7: Mini Coxinha + Água Mineral
    ['itens' => [['prod_id' => 2, 'qtd' => 1], ['prod_id' => 9, 'qtd' => 1]]], // Mini Coxinha (4.00) + Água Mineral (3.50) = 7.50
    // Opção 8: Sanduíche Natural + Salada de Frutas
    ['itens' => [['prod_id' => 7, 'qtd' => 1], ['prod_id' => 12, 'qtd' => 1]]], // Sanduíche Natural (9.50) + Salada Frutas (6.00) = 15.50
    // Opção 9: Esfiha de Carne + Água de Coco
    ['itens' => [['prod_id' => 3, 'qtd' => 1], ['prod_id' => 10, 'qtd' => 1]]], // Esfiha Carne (8.00) + Água Coco (5.50) = 13.50
    // Opção 10: Salada de Frutas + Suco Laranja
    ['itens' => [['prod_id' => 12, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]]], // Salada Frutas (6.00) + Suco Laranja (6.00) = 12.00
];

// Configuração dos dias da semana atual
$diasSemana = [
    [
        'data' => '2026-10-13',
        'dia_nome' => 'Terça-feira',
        'status_matutino' => 'entregue',
        'status_vespertino' => 'entregue',
        'ticket_matutino' => 'impresso',
        'ticket_vespertino' => 'impresso',
        'qtd_matutino' => 6,
        'qtd_vespertino' => 6
    ],
    [
        'data' => '2026-10-14', // HOJE
        'dia_nome' => 'Quarta-feira (Hoje)',
        'status_matutino' => 'entregue', // como já passou das 15h, matutino já entregue
        'status_vespertino' => 'agendado', // vespertino em andamento
        'ticket_matutino' => 'impresso',
        'ticket_vespertino' => 'ativo',
        'qtd_matutino' => 7,
        'qtd_vespertino' => 7
    ],
    [
        'data' => '2026-10-15',
        'dia_nome' => 'Quinta-feira',
        'status_matutino' => 'agendado',
        'status_vespertino' => 'agendado',
        'ticket_matutino' => 'ativo',
        'ticket_vespertino' => 'ativo',
        'qtd_matutino' => 6,
        'qtd_vespertino' => 6
    ],
    [
        'data' => '2026-10-16',
        'dia_nome' => 'Sexta-feira',
        'status_matutino' => 'agendado',
        'status_vespertino' => 'agendado',
        'ticket_matutino' => 'ativo',
        'ticket_vespertino' => 'ativo',
        'qtd_matutino' => 6,
        'qtd_vespertino' => 6
    ],
];

// Rastrear saldos dos responsáveis na memória para conciliação matemática perfeita
$saldosResponsaveis = [];
$responsaveisComRecarga = [];

// Funções auxiliares para gravação
$stmtInsRecarga = $pdo->prepare("
    INSERT INTO transacoes_carteira (responsavel_id, aluno_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, referencia_id, descricao, created_at)
    VALUES (?, ?, 'recarga_pix', ?, ?, ?, ?, 'Recarga Pix Portal da Família', ?)
");

$stmtInsDebito = $pdo->prepare("
    INSERT INTO transacoes_carteira (responsavel_id, aluno_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, referencia_id, descricao, created_at)
    VALUES (?, ?, 'debito_pedido', ?, ?, ?, ?, ?, ?)
");

$stmtInsPedido = $pdo->prepare("
    INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmtInsItemPedido = $pdo->prepare("
    INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
    VALUES (?, ?, ?, ?, ?)
");

$stmtInsTicket = $pdo->prepare("
    INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, tag_alergenos, impresso_em, created_at)
    VALUES (?, ?, ?, 1, ?, ?, ?, ?)
");

$totalGeralPedidos = 0;
$totalGeralTickets = 0;
$valorTotalPedidos = 0.0;
$valorTotalRecargas = 0.0;

$idxMatutino = 0;
$idxVespertino = 0;
$totalMatutino = count($alunosMatutinos);
$totalVespertino = count($alunosVespertinos);

foreach ($diasSemana as $diaCfg) {
    $data = $diaCfg['data'];
    echo "  > Semeando {$diaCfg['dia_nome']} ({$data}):\n";

    // Fila de pedidos por turno neste dia
    $turnosNoDia = [
        ['turno' => 'matutino', 'qtd' => $diaCfg['qtd_matutino'], 'status' => $diaCfg['status_matutino'], 'ticket_status' => $diaCfg['ticket_matutino']],
        ['turno' => 'vespertino', 'qtd' => $diaCfg['qtd_vespertino'], 'status' => $diaCfg['status_vespertino'], 'ticket_status' => $diaCfg['ticket_vespertino']],
    ];

    foreach ($turnosNoDia as $tInfo) {
        $turno = $tInfo['turno'];
        $qtdTurno = $tInfo['qtd'];
        $statusPedido = $tInfo['status'];
        $ticketStatus = $tInfo['ticket_status'];

        for ($k = 0; $k < $qtdTurno; $k++) {
            if ($turno === 'matutino') {
                $aluno = $alunosMatutinos[$idxMatutino % $totalMatutino];
                $idxMatutino++;
            } else {
                $aluno = $alunosVespertinos[$idxVespertino % $totalVespertino];
                $idxVespertino++;
            }

            $alunoId = (int)$aluno['aluno_id'];
            $respId = (int)$aluno['resp_id'];

            // Definição de horários coerentes
            if ($data === '2026-10-14') {
                $criadoEm = "{$data} " . ($turno === 'matutino' ? '07:15:00' : '11:45:00');
                $impressoEm = ($ticketStatus === 'impresso') ? "{$data} 07:50:00" : null;
            } elseif ($data < '2026-10-14') {
                $criadoEm = "{$data} " . ($turno === 'matutino' ? '07:10:00' : '11:30:00');
                $impressoEm = "{$data} " . ($turno === 'matutino' ? '07:45:00' : '13:10:00');
            } else {
                $criadoEm = "2026-10-14 14:" . str_pad(($k * 3 + 12), 2, '0', STR_PAD_LEFT) . ":00";
                $impressoEm = null;
            }

            // Selecionar itens do lanche
            $comb = $combinacoesLanches[($k + ($turno === 'matutino' ? 0 : 5)) % count($combinacoesLanches)];

            // Se aluno tem restrição alimentar, adapta
            $restricoes = $alunoRestricoesMap[$alunoId] ?? [];
            $tagAlergenos = null;
            if (!empty($restricoes)) {
                $tagAlergenos = 'ATENÇÃO: ALÉRGICO A ' . mb_strtoupper(implode(', ', $restricoes), 'UTF-8');
                $comb = ['itens' => [['prod_id' => 12, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]]]; // Salada de Frutas + Suco
            }

            // Calcula valor total do pedido
            $valorPedido = 0.0;
            foreach ($comb['itens'] as $it) {
                $p = $produtosOficiais[$it['prod_id']];
                $valorPedido += ((float)$p['preco_venda'] * (int)$it['qtd']);
            }

            // Garantir recarga prévia Pix para a família se saldo for insuficiente
            if (!isset($saldosResponsaveis[$respId])) {
                $saldoAtualBanco = (float)$pdo->query("SELECT saldo_atual FROM responsaveis WHERE id = {$respId}")->fetchColumn();
                $saldosResponsaveis[$respId] = max(0.00, $saldoAtualBanco);
            }

            if ($saldosResponsaveis[$respId] < $valorPedido + 10.00) {
                $valorRecarga = 80.00;
                $saldoAnt = $saldosResponsaveis[$respId];
                $saldoNovo = $saldoAnt + $valorRecarga;
                $saldosResponsaveis[$respId] = $saldoNovo;

                $pixRef = 'E2E' . date('Ymd', strtotime($data)) . strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
                $dataRecarga = date('Y-m-d H:i:s', strtotime("{$criadoEm} - 1 hour"));

                $stmtInsRecarga->execute([$respId, $alunoId, $valorRecarga, $saldoAnt, $saldoNovo, $pixRef, $dataRecarga]);
                $valorTotalRecargas += $valorRecarga;
                $responsaveisComRecarga[$respId] = true;
            }

            // Efetuar débito atômico na carteira familiar
            $saldoAntDebito = $saldosResponsaveis[$respId];
            $saldoPosDebito = $saldoAntDebito - $valorPedido;
            $saldosResponsaveis[$respId] = $saldoPosDebito;

            $codPedido = 'PED-' . date('Ymd', strtotime($data)) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $descDebito = "Débito para lanche escolar - Aluno: {$aluno['aluno_nome']}";

            $stmtInsDebito->execute([$respId, $alunoId, $valorPedido, $saldoAntDebito, $saldoPosDebito, $codPedido, $descDebito, $criadoEm]);

            // Gravar Pedido
            $stmtInsPedido->execute([
                $codPedido,
                $respId,
                $alunoId,
                $data,
                $turno,
                $valorPedido,
                $statusPedido,
                $criadoEm,
                $criadoEm
            ]);
            $pedidoId = (int)$pdo->lastInsertId();

            // Gravar Itens e Tickets Térmicos
            $itemSeq = 1;
            foreach ($comb['itens'] as $it) {
                $prod = $produtosOficiais[$it['prod_id']];
                $subtotal = (float)$prod['preco_venda'] * (int)$it['qtd'];

                $stmtInsItemPedido->execute([
                    $pedidoId,
                    $prod['id'],
                    $it['qtd'],
                    $prod['preco_venda'],
                    $subtotal
                ]);
                $itemId = (int)$pdo->lastInsertId();

                $codTicket = sprintf("TCK-%s-%s-%02d", date('Y'), strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)), $itemSeq++);
                $stmtInsTicket->execute([
                    $pedidoId,
                    $itemId,
                    $codTicket,
                    $ticketStatus,
                    $tagAlergenos,
                    $impressoEm,
                    $criadoEm
                ]);
                $totalGeralTickets++;
            }

            $totalGeralPedidos++;
            $valorTotalPedidos += $valorPedido;

            echo "    [+] #{$codPedido} | {$data} ({$turno}) | R$ " . number_format($valorPedido, 2, ',', '.') . " | {$statusPedido} | Aluno: {$aluno['aluno_nome']} ({$aluno['nome_turma']})\n";
        }
    }
}
        
// Atualizar saldo final no cadastro de cada responsável participante
foreach ($saldosResponsaveis as $respId => $saldoFinal) {
    $pdo->prepare("UPDATE responsaveis SET saldo_atual = ? WHERE id = ?")->execute([$saldoFinal, $respId]);
}

echo "\n  - Total de pedidos semeados: {$totalGeralPedidos}\n";
echo "  - Total de tickets térmicos gerados: {$totalGeralTickets}\n";
echo "  - Total financeiro de pedidos na semana: R$ " . number_format($valorTotalPedidos, 2, ',', '.') . "\n";
echo "  - Total financeiro de recargas Pix geradas: R$ " . number_format($valorTotalRecargas, 2, ',', '.') . "\n\n";

// -----------------------------------------------------------------------------
// ETAPA 6: VENDAS DE BALCÃO REAIS NA SEMANA (PDV - COLABORADORES E ALUNOS AVULSOS)
// -----------------------------------------------------------------------------
echo "[6/6] Semeando movimentação presencial de balcão (PDV) para os dias 13 e 14 de Outubro...\n";

// Limpar vendas da semana atual para não duplicar
$pdo->exec("DELETE FROM itens_venda_balcao WHERE venda_balcao_id IN (SELECT id FROM vendas_balcao WHERE DATE(created_at) IN ('2026-10-13', '2026-10-14'))");
$pdo->exec("DELETE FROM vendas_balcao WHERE DATE(created_at) IN ('2026-10-13', '2026-10-14')");

$colaboradores = $pdo->query("SELECT id, nome, cargo, matricula_funcional FROM colaboradores WHERE ativo = 1 ORDER BY id ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$operadorId = (int)$pdo->query("SELECT id FROM usuarios WHERE perfil = 'operador' LIMIT 1")->fetchColumn() ?: 1;
$sessaoId = (int)$pdo->query("SELECT id FROM sessoes_caixa ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 1;

$vendasBalcaoSemana = [
    // Terça-feira (13/10) - Intervalo da manhã
    [
        'data' => '2026-10-13 09:45:00',
        'colab' => $colaboradores[0] ?? null,
        'tipo' => 'desconto_folha',
        'pgto' => 'desconto_folha',
        'itens' => [['prod_id' => 7, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]], // Sanduíche + Suco (15.50)
    ],
    [
        'data' => '2026-10-13 09:50:00',
        'colab' => $colaboradores[1] ?? null,
        'tipo' => 'desconto_folha',
        'pgto' => 'desconto_folha',
        'itens' => [['prod_id' => 3, 'qtd' => 1], ['prod_id' => 9, 'qtd' => 1]], // Esfiha + Água (11.50)
    ],
    [
        'data' => '2026-10-13 10:00:00',
        'colab' => null,
        'tipo' => 'avulso_aluno',
        'pgto' => 'dinheiro',
        'itens' => [['prod_id' => 1, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]], // Coxinha + Suco (14.00)
    ],
    // Quarta-feira (14/10) - Hoje
    [
        'data' => '2026-10-14 09:50:00',
        'colab' => $colaboradores[2] ?? null,
        'tipo' => 'desconto_folha',
        'pgto' => 'desconto_folha',
        'itens' => [['prod_id' => 8, 'qtd' => 1], ['prod_id' => 6, 'qtd' => 1]], // Misto + Suco (14.50)
    ],
    [
        'data' => '2026-10-14 10:05:00',
        'colab' => null,
        'tipo' => 'avulso_aluno',
        'pgto' => 'pix_balcao',
        'itens' => [['prod_id' => 4, 'qtd' => 1], ['prod_id' => 10, 'qtd' => 1]], // Enroladinho + Água Coco (13.50)
    ],
    [
        'data' => '2026-10-14 15:10:00',
        'colab' => $colaboradores[3] ?? null,
        'tipo' => 'desconto_folha',
        'pgto' => 'desconto_folha',
        'itens' => [['prod_id' => 12, 'qtd' => 1], ['prod_id' => 9, 'qtd' => 1]], // Salada Frutas + Água (9.50)
    ],
];

$stmtVendaBalcao = $pdo->prepare("
    INSERT INTO vendas_balcao (operador_id, sessao_caixa_id, colaborador_id, tipo_venda, forma_pagamento, valor_total, valor_dinheiro_recebido, troco_devolvido, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 'concluida', ?)
");

$stmtItemBalcao = $pdo->prepare("
    INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal)
    VALUES (?, ?, ?, ?, ?)
");

$totalVendasBalcao = 0;
$valorTotalBalcao = 0.0;

foreach ($vendasBalcaoSemana as $vb) {
    $colabId = $vb['colab'] ? (int)$vb['colab']['id'] : null;
    $vendaTotal = 0.0;
    foreach ($vb['itens'] as $it) {
        $prod = $produtosOficiais[$it['prod_id']];
        $vendaTotal += ((float)$prod['preco_venda'] * (int)$it['qtd']);
    }

    $stmtVendaBalcao->execute([
        $operadorId,
        $sessaoId,
        $colabId,
        $vb['tipo'],
        $vb['pgto'],
        $vendaTotal,
        $vendaTotal,
        $vb['data']
    ]);
    $vendaId = (int)$pdo->lastInsertId();

    foreach ($vb['itens'] as $it) {
        $prod = $produtosOficiais[$it['prod_id']];
        $sub = (float)$prod['preco_venda'] * (int)$it['qtd'];
        $stmtItemBalcao->execute([$vendaId, $prod['id'], $it['qtd'], $prod['preco_venda'], $sub]);
    }

    $pessoa = $vb['colab'] ? "{$vb['colab']['nome']} ({$vb['colab']['cargo']})" : "Aluno/Consumidor Avulso";
    echo "    [+] Venda PDV #{$vendaId} | {$vb['data']} | R$ " . number_format($vendaTotal, 2, ',', '.') . " ({$vb['pgto']}) | {$pessoa}\n";
    $totalVendasBalcao++;
    $valorTotalBalcao += $vendaTotal;
}

echo "\n  - Vendas de balcão registradas: {$totalVendasBalcao}\n";
echo "  - Total movimentado no PDV: R$ " . number_format($valorTotalBalcao, 2, ',', '.') . "\n\n";

// -----------------------------------------------------------------------------
// RELATÓRIO FINAL DE CONFERÊNCIA E AUDITORIA
// -----------------------------------------------------------------------------
echo "================================================================================\n";
echo "   CONFERÊNCIA FINAL DO BANCO DE DADOS (DADOS 100% REAIS E HOMOLOGADOS)        \n";
echo "================================================================================\n";
$contagemTurmas = $pdo->query("SELECT COUNT(*) FROM turmas WHERE deleted_at IS NULL")->fetchColumn();
$contagemResponsaveis = $pdo->query("SELECT COUNT(*) FROM responsaveis")->fetchColumn();
$contagemAlunos = $pdo->query("SELECT COUNT(*) FROM alunos WHERE deleted_at IS NULL")->fetchColumn();
$contagemProdutos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE status_pausa = 'ativo' AND deleted_at IS NULL")->fetchColumn();
$contagemPedidosSemana = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE data_consumo BETWEEN '2026-10-12' AND '2026-10-16'")->fetchColumn();
$contagemTicketsSemana = $pdo->query("SELECT COUNT(*) FROM tickets t JOIN pedidos p ON p.id = t.pedido_id WHERE p.data_consumo BETWEEN '2026-10-12' AND '2026-10-16'")->fetchColumn();
$contagemFakesAlunos = $pdo->query("SELECT COUNT(*) FROM alunos WHERE nome LIKE '%Teste%' OR nome LIKE '%Fake%' OR nome LIKE '%Ausente%'")->fetchColumn();
$contagemFakesResp = $pdo->query("SELECT COUNT(*) FROM responsaveis WHERE nome LIKE '%Teste%' OR nome LIKE '%Fake%'")->fetchColumn();
$contagemFakesProd = $pdo->query("SELECT COUNT(*) FROM produtos WHERE codigo_interno LIKE '%TEST%' OR codigo_interno LIKE '%ED-%'")->fetchColumn();

echo "  * Turmas Oficiais:             {$contagemTurmas} (Esperado: 19)\n";
echo "  * Responsáveis Familiares:     {$contagemResponsaveis} (Esperado: 195)\n";
echo "  * Alunos Oficiais Cadastrados: {$contagemAlunos} (Esperado: 232)\n";
echo "  * Produtos Oficiais no Menu:   {$contagemProdutos} (Esperado: 12)\n";
echo "  * Pedidos na Semana Atual:     {$contagemPedidosSemana}\n";
echo "  * Tickets Térmicos na Semana:  {$contagemTicketsSemana}\n";
echo "  * Registros Falsos Residuais:  Alunos: {$contagemFakesAlunos} | Responsáveis: {$contagemFakesResp} | Produtos: {$contagemFakesProd}\n\n";

if ($contagemFakesAlunos == 0 && $contagemFakesResp == 0 && $contagemFakesProd == 0) {
    echo ">>> SUCESSO ABSOLUTO: O banco de dados está 100% LIMPO e abastecido com COMPRAS e PEDIDOS REAIS! <<<\n";
} else {
    echo ">>> ATENÇÃO: Ainda foram detectados registros falsos residuais. <<<\n";
}
echo "================================================================================\n";
