<?php
define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance();

echo "=== GERANDO VENDAS E PEDIDOS PARA O DIA DE HOJE ===\n";

// 1. Obter Operador
$operador = $pdo->query("SELECT id, nome FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$operadorId = (int)($operador['id'] ?? 1);

// 2. Garantir sessão de caixa para vincular
$sessao = $pdo->query("SELECT id FROM sessoes_caixa ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$sessaoId = (int)($sessao['id'] ?? 1);

// 3. Obter Aluno e Colaborador
$aluno = $pdo->query("SELECT id, nome, responsavel_id FROM alunos WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$alunoId = (int)($aluno['id'] ?? 1);
$respId = (int)($aluno['responsavel_id'] ?? 1);

$colaborador = $pdo->query("SELECT id, nome FROM colaboradores WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$colaboradorId = (int)($colaborador['id'] ?? 1);

// 4. Obter Produtos
$salgado = $pdo->query("SELECT id, descricao, preco_venda FROM produtos WHERE tipo = 'salgado_diario' AND deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$salgadoId = (int)($salgado['id'] ?? 4);
$salgadoPreco = (float)($salgado['preco_venda'] ?? 8.00);

$industrializado = $pdo->query("SELECT id, descricao, preco_venda FROM produtos WHERE tipo = 'industrializado_continuo' AND deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$indId = (int)($industrializado['id'] ?? 9);
$indPreco = (float)($industrializado['preco_venda'] ?? 4.50);

$agora = date('Y-m-d H:i:s');
$hojeData = date('Y-m-d');

// --- VENDA 1: Venda de Balcão para Aluno (Hoje) ---
$totalVenda1 = $salgadoPreco + $indPreco;
$stmtV1 = $pdo->prepare("
    INSERT INTO vendas_balcao (
        sessao_caixa_id, operador_id, tipo_venda, aluno_id, forma_pagamento,
        valor_total, valor_dinheiro_recebido, troco_devolvido, status, created_at
    ) VALUES (
        :sessao_id, :op_id, 'avulso_aluno', :aluno_id, 'dinheiro',
        :total1, :total2, 0.00, 'concluida', :created_at
    )
");
$stmtV1->execute([
    'sessao_id'  => $sessaoId,
    'op_id'      => $operadorId,
    'aluno_id'   => $alunoId,
    'total1'     => $totalVenda1,
    'total2'     => $totalVenda1,
    'created_at' => $agora
]);
$vendaId1 = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 1, ?, ?)")
    ->execute([$vendaId1, $salgadoId, $salgadoPreco, $salgadoPreco]);
$pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 1, ?, ?)")
    ->execute([$vendaId1, $indId, $indPreco, $indPreco]);

echo "[OK] Venda Balcão Aluno criada: #{$vendaId1} | Aluno: {$aluno['nome']} | Total: R$ " . number_format($totalVenda1, 2, ',', '.') . "\n";

// --- VENDA 2: Venda de Balcão para Colaborador (Hoje) ---
$totalVenda2 = $salgadoPreco;
$stmtV2 = $pdo->prepare("
    INSERT INTO vendas_balcao (
        sessao_caixa_id, operador_id, tipo_venda, colaborador_id, forma_pagamento,
        valor_total, status, created_at
    ) VALUES (
        :sessao_id, :op_id, 'avulso_colaborador', :colab_id, 'pix_balcao',
        :total, 'concluida', :created_at
    )
");
$stmtV2->execute([
    'sessao_id'  => $sessaoId,
    'op_id'      => $operadorId,
    'colab_id'   => $colaboradorId,
    'total'      => $totalVenda2,
    'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
]);
$vendaId2 = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 1, ?, ?)")
    ->execute([$vendaId2, $salgadoId, $salgadoPreco, $salgadoPreco]);

echo "[OK] Venda Balcão Colaborador criada: #{$vendaId2} | Colaborador: {$colaborador['nome']} | Total: R$ " . number_format($totalVenda2, 2, ',', '.') . "\n";

// --- VENDA 3: Venda Avulsa (Consumidor Externo / Sobra / Anônimo) (Hoje) ---
$totalVenda3 = $indPreco * 2;
$stmtV3 = $pdo->prepare("
    INSERT INTO vendas_balcao (
        sessao_caixa_id, operador_id, tipo_venda, aluno_id, colaborador_id, forma_pagamento,
        valor_total, status, created_at
    ) VALUES (
        :sessao_id, :op_id, 'avulso_aluno', NULL, NULL, 'dinheiro',
        :total, 'concluida', :created_at
    )
");
$stmtV3->execute([
    'sessao_id'  => $sessaoId,
    'op_id'      => $operadorId,
    'total'      => $totalVenda3,
    'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
]);
$vendaId3 = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 2, ?, ?)")
    ->execute([$vendaId3, $indId, $indPreco, $totalVenda3]);

echo "[OK] Venda Avulsa Externa criada: #{$vendaId3} | Consumidor Geral | Total: R$ " . number_format($totalVenda3, 2, ',', '.') . "\n";

// --- PEDIDO 4: Pedido Agendado de Aluno para Consumo Hoje ---
$codPedido = 'PED-' . date('Ymd') . '-' . rand(100, 999);
$totalPedido = $salgadoPreco;
$stmtPed = $pdo->prepare("
    INSERT INTO pedidos (
        codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo,
        valor_total, status, created_at
    ) VALUES (
        :cod, :resp_id, :aluno_id, :data_cons, 'matutino',
        :total, 'agendado', :created_at
    )
");
$stmtPed->execute([
    'cod'        => $codPedido,
    'resp_id'    => $respId,
    'aluno_id'   => $alunoId,
    'data_cons'  => $hojeData,
    'total'      => $totalPedido,
    'created_at' => $agora
]);
$pedidoId = (int)$pdo->lastInsertId();

$stmtItemPed = $pdo->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, 1, ?, ?)");
$stmtItemPed->execute([$pedidoId, $salgadoId, $salgadoPreco, $salgadoPreco]);
$itemPedId = (int)$pdo->lastInsertId();

$pdo->prepare("INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, created_at) VALUES (?, ?, ?, 1, 'ativo', NOW())")
    ->execute([$pedidoId, $itemPedId, 'TCK-' . rand(1000, 9999)]);

echo "[OK] Pedido Agendado criado: #{$codPedido} | Aluno: {$aluno['nome']} | Consumo: {$hojeData} | Total: R$ " . number_format($totalPedido, 2, ',', '.') . "\n";

echo "\nSucesso! Vendas e pedidos para a data de hoje ({$hojeData}) gerados com sucesso!\n";
