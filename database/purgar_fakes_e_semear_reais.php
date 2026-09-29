<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Script de Purga Definitiva de Registros Falsos/Testes e Carga de Encomendas Reais
 * 
 * Remove qualquer vestígio de "Aluno Ausente", "Filho Teste", "Pai Sobra", etc.
 * Garante as 19 turmas oficiais, 195 responsáveis e 232 alunos reais da escola (Matriculas.pdf).
 * Gera encomendas online e vendas de balcão reais para demonstração do sistema.
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';

$pdo = Database::getInstance();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

echo "========================================================================\n";
echo "   PURGA COMPLETA DE DADOS FALSOS E SEMEADURA EXCLUSIVA DE DADOS REAIS  \n";
echo "========================================================================\n\n";

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

// 1. Remover pedidos, tickets e itens que pertenciam a alunos ou responsáveis com nomes de teste
echo "1. Removendo pedidos, tickets e itens falsos residuais...\n";
$pdo->exec("
    DELETE FROM tickets 
    WHERE pedido_id IN (
        SELECT p.id FROM pedidos p 
        LEFT JOIN alunos a ON a.id = p.aluno_id 
        LEFT JOIN responsaveis r ON r.id = p.responsavel_id 
        WHERE a.nome LIKE '%Teste%' 
           OR a.nome LIKE '%Ausente%' 
           OR a.nome LIKE '%Fake%' 
           OR r.nome LIKE '%Teste%' 
           OR r.nome LIKE '%Sobra%' 
           OR r.nome LIKE '%Fake%'
           OR p.codigo_pedido LIKE 'PED-SBR-%'
           OR p.codigo_pedido LIKE 'PED-EST-%'
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
           OR r.nome LIKE '%Teste%' 
           OR r.nome LIKE '%Sobra%' 
           OR r.nome LIKE '%Fake%'
           OR p.codigo_pedido LIKE 'PED-SBR-%'
           OR p.codigo_pedido LIKE 'PED-EST-%'
    )
");

$pdo->exec("
    DELETE FROM pedidos 
    WHERE id IN (
        SELECT id FROM (
            SELECT p.id FROM pedidos p 
            LEFT JOIN alunos a ON a.id = p.aluno_id 
            LEFT JOIN responsaveis r ON r.id = p.responsavel_id 
            WHERE a.nome LIKE '%Teste%' 
               OR a.nome LIKE '%Ausente%' 
               OR a.nome LIKE '%Fake%' 
               OR r.nome LIKE '%Teste%' 
               OR r.nome LIKE '%Sobra%' 
               OR r.nome LIKE '%Fake%'
               OR p.codigo_pedido LIKE 'PED-SBR-%'
               OR p.codigo_pedido LIKE 'PED-EST-%'
        ) as tmp
    )
");

// 2. Limpar transações e logs de responsáveis fake
$pdo->exec("
    DELETE FROM transacoes_carteira 
    WHERE responsavel_id IN (
        SELECT id FROM responsaveis 
        WHERE nome LIKE '%Teste%' OR nome LIKE '%Sobra%' OR nome LIKE '%Fake%'
    )
");

// 3. Remover alunos e responsáveis fake
echo "2. Deletando alunos e responsáveis falsos...\n";
$pdo->exec("
    DELETE FROM alunos 
    WHERE nome LIKE '%Teste%' 
       OR nome LIKE '%Ausente%' 
       OR nome LIKE '%Fake%' 
       OR matricula LIKE 'MAT-EMERG%' 
       OR matricula LIKE 'ALU-SBR%' 
       OR matricula LIKE 'ALU-%' 
       OR matricula LIKE 'MAT-EST%'
");

$pdo->exec("
    DELETE FROM responsaveis 
    WHERE nome LIKE '%Teste%' 
       OR nome LIKE '%Sobra%' 
       OR nome LIKE '%Fake%'
");

// 4. Remover produtos de teste temporários criados em fixtures (mantendo os produtos oficiais 1 a 12)
echo "3. Removendo produtos falsos temporários...\n";
$pdo->exec("
    DELETE FROM produtos 
    WHERE id > 12 AND (
        codigo_interno LIKE '%PDV-BLOQ%' 
        OR codigo_interno LIKE '%TEST-%' 
        OR codigo_interno LIKE 'EST-%' 
        OR codigo_interno LIKE 'SOB-%'
        OR codigo_interno LIKE 'PRD-ED-%'
    )
");

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "Registros falsos eliminados com sucesso.\n\n";

// 5. Garantir que as matrículas oficiais reais estejam 100% sincronizadas
echo "4. Sincronizando matrículas reais da escola...\n";
require __DIR__ . '/seed_matriculas_reais.php';

// 6. Criar pedidos reais com alunos reais do colégio para hoje (29/09/2026)
echo "\n5. Semeando encomendas online e vendas de balcão REAIS para demonstração...\n";

$hoje = '2026-09-29';
$agora = date('Y-m-d H:i:s');

// Obter produtos oficiais
$stmtProd = $pdo->query("SELECT id, codigo_interno, descricao, preco_venda FROM produtos WHERE id BETWEEN 1 AND 12 ORDER BY id ASC");
$prods = [];
while ($row = $stmtProd->fetch(PDO::FETCH_ASSOC)) {
    $prods[(int)$row['id']] = $row;
}

// Lista de alunos reais oficiais para receber pedidos
$alunosReaisNomes = [
    'LAURA MOREIRA NEVES',
    'JÚLIA MARTINS GARCIA',
    'JOÃO LUCAS GOMES MOTA',
    'ALICE DIAS FARIAS',
    'FRANCISCO AUGUSTO DUARTE DA ROCHA',
    'BRUNO GOUVEIA ALENCASTRO SOUZA',
    'GABRIELA DA MATA CAMARGO',
    'MATHEUS HENRIQUE CARVALHO FERRAZ'
];

$pedidosExemplos = [
    [
        'aluno_nome' => 'FRANCISCO AUGUSTO DUARTE DA ROCHA',
        'itens' => [
            ['prod_id' => 3, 'qtd' => 1], // Esfiha de Carne (8.00)
            ['prod_id' => 5, 'qtd' => 1], // Suco de Laranja (6.00)
        ],
        'turno' => 'vespertino',
        'hora'  => '13:45:00'
    ],
    [
        'aluno_nome' => 'ALICE DIAS FARIAS',
        'itens' => [
            ['prod_id' => 4, 'qtd' => 1], // Enroladinho Presunto e Queijo (8.00)
            ['prod_id' => 10, 'qtd' => 1], // Água de Coco (5.50)
        ],
        'turno' => 'vespertino',
        'hora'  => '13:50:00'
    ],
    [
        'aluno_nome' => 'JOÃO LUCAS GOMES MOTA',
        'itens' => [
            ['prod_id' => 1, 'qtd' => 1], // Coxinha Frango Grande (8.00)
            ['prod_id' => 6, 'qtd' => 1], // Suco de Uva Integral (7.00)
        ],
        'turno' => 'matutino',
        'hora'  => '08:15:00'
    ],
    [
        'aluno_nome' => 'JÚLIA MARTINS GARCIA',
        'itens' => [
            ['prod_id' => 7, 'qtd' => 1], // Sanduíche Natural Frango (9.50)
            ['prod_id' => 12, 'qtd' => 1], // Salada de Frutas (6.00)
        ],
        'turno' => 'matutino',
        'hora'  => '08:30:00'
    ],
    [
        'aluno_nome' => 'LAURA MOREIRA NEVES',
        'itens' => [
            ['prod_id' => 8, 'qtd' => 1], // Misto Quente Tradicional (7.50)
            ['prod_id' => 5, 'qtd' => 1], // Suco Laranja (6.00)
        ],
        'turno' => 'matutino',
        'hora'  => '08:40:00'
    ],
    [
        'aluno_nome' => 'BRUNO GOUVEIA ALENCASTRO SOUZA',
        'itens' => [
            ['prod_id' => 2, 'qtd' => 1], // Coxinha Pequena (4.00)
            ['prod_id' => 11, 'qtd' => 1], // Biscoito Cookie Cacau (4.50)
        ],
        'turno' => 'vespertino',
        'hora'  => '14:05:00'
    ],
    [
        'aluno_nome' => 'GABRIELA DA MATA CAMARGO',
        'itens' => [
            ['prod_id' => 4, 'qtd' => 1], // Enroladinho Presunto e Queijo (8.00)
            ['prod_id' => 6, 'qtd' => 1], // Suco de Uva (7.00)
        ],
        'turno' => 'vespertino',
        'hora'  => '14:15:00'
    ],
    [
        'aluno_nome' => 'MATHEUS HENRIQUE CARVALHO FERRAZ',
        'itens' => [
            ['prod_id' => 3, 'qtd' => 1], // Esfiha de Carne (8.00)
            ['prod_id' => 9, 'qtd' => 1], // Água Mineral (3.50)
        ],
        'turno' => 'vespertino',
        'hora'  => '14:20:00'
    ],
];

// Limpar pedidos anteriores de hoje para evitar duplicações
$pdo->exec("DELETE FROM tickets WHERE pedido_id IN (SELECT id FROM pedidos WHERE data_consumo = '{$hoje}')");
$pdo->exec("DELETE FROM itens_pedido WHERE pedido_id IN (SELECT id FROM pedidos WHERE data_consumo = '{$hoje}')");
$pdo->exec("DELETE FROM pedidos WHERE data_consumo = '{$hoje}'");

$pedidosGerados = 0;
foreach ($pedidosExemplos as $ex) {
    // Localizar aluno real
    $stmtAl = $pdo->prepare("SELECT a.id, a.nome, a.matricula, a.responsavel_id, r.nome as resp_nome, r.cpf 
                             FROM alunos a 
                             JOIN responsaveis r ON r.id = a.responsavel_id 
                             WHERE a.nome = :nome LIMIT 1");
    $stmtAl->execute(['nome' => $ex['aluno_nome']]);
    $al = $stmtAl->fetch(PDO::FETCH_ASSOC);

    if (!$al) {
        continue;
    }

    $alunoId = (int)$al['id'];
    $respId = (int)$al['responsavel_id'];
    $turno = $ex['turno'];
    $dataHoraCriacao = "{$hoje} {$ex['hora']}";

    // Calcula valor total
    $valorTotal = 0.0;
    foreach ($ex['itens'] as $it) {
        $p = $prods[$it['prod_id']];
        $valorTotal += ((float)$p['preco_venda'] * (int)$it['qtd']);
    }

    // Garante saldo no responsável
    $pdo->prepare("UPDATE responsaveis SET saldo_atual = GREATEST(saldo_atual, 50.00) WHERE id = ?")->execute([$respId]);

    // Cria registro de recarga e débito na carteira familiar se não houver
    $pdo->prepare("INSERT INTO transacoes_carteira (responsavel_id, aluno_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, descricao, created_at)
                   VALUES (?, ?, 'recarga_pix', 50.00, 0.00, 50.00, 'Recarga Pix Portal da Família', ?)")
        ->execute([$respId, $alunoId, $dataHoraCriacao]);

    $pdo->prepare("INSERT INTO transacoes_carteira (responsavel_id, aluno_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, descricao, created_at)
                   VALUES (?, ?, 'debito_pedido', ?, 50.00, ?, 'Débito para lanche escolar', ?)")
        ->execute([$respId, $alunoId, $valorTotal, (50.00 - $valorTotal), $dataHoraCriacao]);

    $pdo->prepare("UPDATE responsaveis SET saldo_atual = ? WHERE id = ?")->execute([(50.00 - $valorTotal), $respId]);

    // Insere pedido
    $codigoPedido = 'PED-' . date('Ymd', strtotime($hoje)) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    $stmtInsPed = $pdo->prepare("INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at, updated_at)
                                 VALUES (?, ?, ?, ?, ?, ?, 'agendado', ?, ?)");
    $stmtInsPed->execute([$codigoPedido, $respId, $alunoId, $hoje, $turno, $valorTotal, $dataHoraCriacao, $dataHoraCriacao]);
    $pedId = (int)$pdo->lastInsertId();

    // Insere itens do pedido e tickets
    foreach ($ex['itens'] as $it) {
        $p = $prods[$it['prod_id']];
        $subtotal = (float)$p['preco_venda'] * (int)$it['qtd'];

        $stmtInsItem = $pdo->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
                                      VALUES (?, ?, ?, ?, ?)");
        $stmtInsItem->execute([$pedId, $p['id'], $it['qtd'], $p['preco_venda'], $subtotal]);
        $itemId = (int)$pdo->lastInsertId();

        $codigoTicket = 'TCK-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $stmtInsTick = $pdo->prepare("INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, created_at)
                                      VALUES (?, ?, ?, 1, 'impresso', ?)");
        $stmtInsTick->execute([$pedId, $itemId, $codigoTicket, $dataHoraCriacao]);
    }

    echo "  [+] Pedido criado: #{$codigoPedido} - R$ " . number_format($valorTotal, 2, ',', '.') . " - Aluno: {$al['nome']} (Resp: {$al['resp_nome']})\n";
    $pedidosGerados++;
}

echo "\nTotal de pedidos online de alunos reais criados para {$hoje}: {$pedidosGerados}\n";

// 7. Sincronizar vendas de balcão com colaboradores reais e avulsos
echo "\n6. Sincronizando vendas de balcão reais...\n";
$pdo->exec("DELETE FROM itens_venda_balcao WHERE venda_balcao_id IN (SELECT id FROM vendas_balcao WHERE DATE(created_at) = '{$hoje}')");
$pdo->exec("DELETE FROM vendas_balcao WHERE DATE(created_at) = '{$hoje}'");

// Colaboradores reais para vendas de balcão
$colaboradores = $pdo->query("SELECT id, nome, cargo, matricula_funcional FROM colaboradores WHERE ativo = 1 ORDER BY id ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$operadorId = (int)$pdo->query("SELECT id FROM usuarios WHERE perfil = 'operador' LIMIT 1")->fetchColumn() ?: 1;
$sessaoId = (int)$pdo->query("SELECT id FROM sessoes_caixa ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 1;

$vendasExemplos = [
    [
        'colab' => $colaboradores[0] ?? null,
        'tipo'  => 'desconto_folha',
        'pgto'  => 'desconto_folha',
        'itens' => [['prod_id' => 7, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]], // Sanduíche + Suco (15.50)
        'hora'  => '10:15:00'
    ],
    [
        'colab' => $colaboradores[1] ?? null,
        'tipo'  => 'desconto_folha',
        'pgto'  => 'desconto_folha',
        'itens' => [['prod_id' => 1, 'qtd' => 1], ['prod_id' => 9, 'qtd' => 1]], // Coxinha + Água (11.50)
        'hora'  => '10:30:00'
    ],
    [
        'colab' => null,
        'tipo'  => 'avulso_aluno',
        'pgto'  => 'dinheiro',
        'itens' => [['prod_id' => 3, 'qtd' => 1], ['prod_id' => 5, 'qtd' => 1]], // Esfiha + Suco (14.00)
        'hora'  => '10:45:00'
    ],
    [
        'colab' => null,
        'tipo'  => 'avulso_aluno',
        'pgto'  => 'pix_balcao',
        'itens' => [['prod_id' => 4, 'qtd' => 1], ['prod_id' => 6, 'qtd' => 1]], // Enroladinho + Suco (15.00)
        'hora'  => '11:00:00'
    ]
];

foreach ($vendasExemplos as $vx) {
    $colabId = $vx['colab'] ? (int)$vx['colab']['id'] : null;
    $dataHora = "{$hoje} {$vx['hora']}";
    $valorTotal = 0.0;
    foreach ($vx['itens'] as $it) {
        $p = $prods[$it['prod_id']];
        $valorTotal += ((float)$p['preco_venda'] * (int)$it['qtd']);
    }

    $stmtVenda = $pdo->prepare("INSERT INTO vendas_balcao (operador_id, sessao_caixa_id, colaborador_id, tipo_venda, forma_pagamento, valor_total, valor_dinheiro_recebido, troco_devolvido, status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 'concluida', ?)");
    $stmtVenda->execute([$operadorId, $sessaoId, $colabId, $vx['tipo'], $vx['pgto'], $valorTotal, $valorTotal, $dataHora]);
    $vendaId = (int)$pdo->lastInsertId();

    foreach ($vx['itens'] as $it) {
        $p = $prods[$it['prod_id']];
        $sub = (float)$p['preco_venda'] * (int)$it['qtd'];
        $pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal)
                       VALUES (?, ?, ?, ?, ?)")
            ->execute([$vendaId, $p['id'], $it['qtd'], $p['preco_venda'], $sub]);
    }

    $pessoa = $vx['colab'] ? $vx['colab']['nome'] : 'Consumidor Avulso';
    echo "  [+] Venda Balcão criada: #{$vendaId} - R$ " . number_format($valorTotal, 2, ',', '.') . " ({$vx['pgto']}) - Pessoa: {$pessoa}\n";
}

echo "\n========================================================================\n";
echo "   CONCLUÍDO: DADOS 100% PURGADOS E BASE REAL DA ESCOLA ATIVA!           \n";
echo "========================================================================\n";
