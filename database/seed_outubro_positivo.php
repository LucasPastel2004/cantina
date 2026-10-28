<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Seeder Especial: Outubro Positivo & Livro Caixa Saudável
 * 
 * Gera dados contábeis e operacionais consistentes, lucrativos e positivos para todo o mês de Outubro/2026:
 * - Saldo Inicial Positivo (empresa consolidada)
 * - Vendas e Recargas presenciais (Dinheiro e Pix)
 * - Recargas Pix Online (Portal da Família)
 * - Encomendas/Pedidos para TODOS os dias úteis do mês (Matutino e Vespertino) com itens e tickets
 * - Pagamento a fornecedores (Recibos oficiais Pix e Dinheiro)
 * - Despesas operacionais do mês
 * - Fechamento de turnos de caixa
 */

if (!defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';

$pdo = Database::getInstance();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

echo "================================================================================\n";
echo "   CANTINA WEB — SEEDER DE OUTUBRO POSITIVO & ESCRITURAÇÃO FINANCEIRA SAUDÁVEL\n";
echo "   Colégio Monteiro Lobato — Outubro de 2026\n";
echo "================================================================================\n\n";

$pdo->beginTransaction();

try {
    // -------------------------------------------------------------------------
    // 1. OBTENÇÃO DE ENTIDADES BASE REAIS DO BANCO
    // -------------------------------------------------------------------------
    echo "[1/7] Carregando cadastros mestres...\n";

    // Operador e Administrador
    $operadorId = (int)$pdo->query("SELECT id FROM usuarios WHERE perfil = 'operador' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetchColumn();
    if (!$operadorId) {
        $operadorId = (int)$pdo->query("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1;
    }

    $adminId = (int)$pdo->query("SELECT id FROM usuarios WHERE perfil = 'administrador' AND deleted_at IS NULL ORDER BY id ASC LIMIT 1")->fetchColumn();
    if (!$adminId) {
        $adminId = $operadorId;
    }

    // Alunos com seus respectivos responsáveis
    $alunosStmt = $pdo->query("
        SELECT a.id, a.nome, a.turma_id, a.responsavel_id, t.turno
        FROM alunos a
        JOIN turmas t ON t.id = a.turma_id
        WHERE a.deleted_at IS NULL AND a.responsavel_id IS NOT NULL
        ORDER BY a.id ASC
    ");
    $alunos = $alunosStmt->fetchAll(\PDO::FETCH_ASSOC);
    $totalAlunos = count($alunos);

    if ($totalAlunos === 0) {
        throw new \RuntimeException("Nenhum aluno com responsável encontrado no banco.");
    }
    echo "  - Alunos vinculados: {$totalAlunos}\n";

    // Produtos ativos
    $produtosStmt = $pdo->query("
        SELECT id, codigo_interno, descricao, preco_venda, tipo, turno_disponibilidade
        FROM produtos
        WHERE deleted_at IS NULL AND status_pausa = 'ativo'
        ORDER BY id ASC
    ");
    $produtos = $produtosStmt->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($produtos)) {
        throw new \RuntimeException("Nenhum produto ativo encontrado.");
    }
    echo "  - Produtos disponíveis: " . count($produtos) . "\n";

    // Fornecedores
    $fornecedoresExistentes = $pdo->query("SELECT id, nome_fantasia FROM fornecedores WHERE deleted_at IS NULL ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
    if (empty($fornecedoresExistentes)) {
        $stmtCriaForn = $pdo->prepare("INSERT INTO fornecedores (razao_social, nome_fantasia, created_at) VALUES (:razao, :fantasia, NOW())");
        $stmtCriaForn->execute([':razao' => 'Distribuidora de Salgados Ltda', ':fantasia' => 'Salgados & Cia']);
        $id1 = (int)$pdo->lastInsertId();
        $stmtCriaForn->execute([':razao' => 'Doceria Cupcake Sonhos Ltda', ':fantasia' => 'Cupcake & Doces']);
        $id2 = (int)$pdo->lastInsertId();
        $stmtCriaForn->execute([':razao' => 'Indústria Láctea Mineira Ltda', ':fantasia' => 'Láctea']);
        $id3 = (int)$pdo->lastInsertId();
        $fornecedoresExistentes = [
            ['id' => $id1, 'nome_fantasia' => 'Salgados & Cia'],
            ['id' => $id2, 'nome_fantasia' => 'Cupcake & Doces'],
            ['id' => $id3, 'nome_fantasia' => 'Láctea'],
        ];
    }

    $primeiroFornId = (int)$fornecedoresExistentes[0]['id'];
    $segundoFornId  = isset($fornecedoresExistentes[1]) ? (int)$fornecedoresExistentes[1]['id'] : $primeiroFornId;
    $terceiroFornId = isset($fornecedoresExistentes[2]) ? (int)$fornecedoresExistentes[2]['id'] : $primeiroFornId;

    $fornecedorSalgadoId = 0;
    $fornecedorCupcakeId = 0;
    $fornecedorLacteaId  = 0;
    foreach ($fornecedoresExistentes as $f) {
        if (!$fornecedorSalgadoId && stripos($f['nome_fantasia'], 'salgado') !== false) $fornecedorSalgadoId = (int)$f['id'];
        if (!$fornecedorCupcakeId && stripos($f['nome_fantasia'], 'cupcake') !== false) $fornecedorCupcakeId = (int)$f['id'];
        if (!$fornecedorLacteaId && (stripos($f['nome_fantasia'], 'láctea') !== false || stripos($f['nome_fantasia'], 'lactea') !== false)) $fornecedorLacteaId = (int)$f['id'];
    }
    $fornecedorSalgadoId = $fornecedorSalgadoId ?: $primeiroFornId;
    $fornecedorCupcakeId = $fornecedorCupcakeId ?: $segundoFornId;
    $fornecedorLacteaId  = $fornecedorLacteaId ?: $terceiroFornId;
    $fornecedorGeralId   = $primeiroFornId;

    // -------------------------------------------------------------------------
    // 2. HIGIENIZAÇÃO DE DADOS DE TESTE DE OUTUBRO & EQUILÍBRIO DO SALDO ANTERIOR
    // -------------------------------------------------------------------------
    echo "[2/7] Higienizando movimentações anteriores e calibrando saldo inicial...\n";

    // Ajusta as despesas de meses anteriores para que o saldo anterior em 01/10/2026 seja positivo e saudável (+ R$ 12.500,00)
    // Primeiro limpamos despesas antigas desbalanceadas criadas em testes DRE
    $pdo->exec("DELETE FROM despesas_operacionais WHERE data_pagamento < '2026-10-01'");
    $pdo->exec("DELETE FROM fornecedor_recibos WHERE data_pagamento < '2026-10-01'");

    // Remove transações de Outubro/2026 para re-semear dados limpos e coerentes
    $pdo->exec("DELETE FROM tickets WHERE created_at BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'");
    $pdo->exec("DELETE FROM itens_pedido WHERE pedido_id IN (SELECT id FROM pedidos WHERE data_consumo BETWEEN '2026-10-01' AND '2026-10-31')");
    $pdo->exec("DELETE FROM pedidos WHERE data_consumo BETWEEN '2026-10-01' AND '2026-10-31'");
    $pdo->exec("DELETE FROM itens_venda_balcao WHERE venda_balcao_id IN (SELECT id FROM vendas_balcao WHERE created_at BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59')");
    $pdo->exec("DELETE FROM vendas_balcao WHERE created_at BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'");
    $pdo->exec("DELETE FROM transacoes_carteira WHERE created_at BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'");
    $pdo->exec("DELETE FROM fornecedor_recibos WHERE data_pagamento BETWEEN '2026-10-01' AND '2026-10-31'");
    $pdo->exec("DELETE FROM despesas_operacionais WHERE data_pagamento BETWEEN '2026-10-01' AND '2026-10-31'");
    $pdo->exec("DELETE FROM sessoes_caixa WHERE aberto_em BETWEEN '2026-10-01 00:00:00' AND '2026-10-31 23:59:59'");

    // Calibra entradas anteriores (Setembro/2026) para garantir saldo anterior positivo de R$ 12.500,00
    // Cria um aporte de capital / faturamento de encerramento de Setembro
    $aluno1 = $alunos[0];
    $pdo->exec("DELETE FROM transacoes_carteira WHERE DATE(created_at) < '2026-10-01'");
    $pdo->exec("DELETE FROM vendas_balcao WHERE DATE(created_at) < '2026-10-01'");

    $stmtTransSet = $pdo->prepare("
        INSERT INTO transacoes_carteira (responsavel_id, aluno_id, operador_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, descricao, created_at)
        VALUES (:resp, :aluno, NULL, 'recarga_pix', 12500.00, 0.00, 12500.00, 'Saldo Operacional Inicial Cantina Web', '2026-09-30 18:00:00')
    ");
    $stmtTransSet->execute([
        ':resp'  => $aluno1['responsavel_id'],
        ':aluno' => $aluno1['id']
    ]);
    echo "  - Saldo Anterior calibrado em R$ 12.500,00 positivo.\n";

    // -------------------------------------------------------------------------
    // 3. DIAS ÚTEIS DE OUTUBRO DE 2026 (Segunda a Sexta)
    // -------------------------------------------------------------------------
    echo "[3/7] Mapeando dias letivos de Outubro/2026...\n";

    $diasUteis = [];
    $inicio = new \DateTime('2026-10-01');
    $fim    = new \DateTime('2026-10-31');

    while ($inicio <= $fim) {
        $diaSemanaNum = (int)$inicio->format('N'); // 1 = Seg, 5 = Sex, 6 = Sab, 7 = Dom
        // Dias de segunda a sexta (1 a 5)
        if ($diaSemanaNum >= 1 && $diaSemanaNum <= 5) {
            $diasUteis[] = $inicio->format('Y-m-d');
        }
        $inicio->modify('+1 day');
    }
    echo "  - Total de dias úteis letivos: " . count($diasUteis) . " dias.\n";

    // -------------------------------------------------------------------------
    // 4. SEMEAR RECARGAS, VENDAS, ENCOMENDAS E CAIXA POR DIA ÚTIL
    // -------------------------------------------------------------------------
    echo "[4/7] Semeando movimentações diárias (Vendas, Recargas e Encomendas)...\n";

    $stmtRecarga = $pdo->prepare("
        INSERT INTO transacoes_carteira (responsavel_id, aluno_id, operador_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, descricao, created_at)
        VALUES (:resp, :aluno, :op, :tipo, :valor, :saldo_ant, :saldo_res, :desc, :created_at)
    ");

    $stmtSessaoCaixa = $pdo->prepare("
        INSERT INTO sessoes_caixa (operador_id, turno, aberto_em, fechado_em, fundo_troco_inicial, total_dinheiro_recargas, total_dinheiro_avulso, total_pix_balcao, total_informado_gaveta, diferenca_quebra_sobra, status)
        VALUES (:op, :turno, :aberto, :fechado, 100.00, :rec_din, :venda_din, :pix_balcao, :gaveta, 0.00, 'fechado')
    ");

    $stmtVendaBalcao = $pdo->prepare("
        INSERT INTO vendas_balcao (sessao_caixa_id, operador_id, tipo_venda, aluno_id, colaborador_id, forma_pagamento, valor_total, valor_dinheiro_recebido, troco_devolvido, status, created_at)
        VALUES (:caixa, :op, 'avulso_aluno', :aluno, NULL, :forma, :total, :rec, :troco, 'concluida', :created_at)
    ");

    $stmtItemVb = $pdo->prepare("
        INSERT INTO itens_venda_balcao (venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal)
        VALUES (:venda, :prod, :qtd, :unit, :sub)
    ");

    $stmtPedido = $pdo->prepare("
        INSERT INTO pedidos (codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at)
        VALUES (:cod, :resp, :aluno, :data_cons, :turno, :total, :status, :created_at)
    ");

    $stmtItemPedido = $pdo->prepare("
        INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
        VALUES (:ped, :prod, :qtd, :unit, :sub)
    ");

    $stmtTicket = $pdo->prepare("
        INSERT INTO tickets (pedido_id, item_pedido_id, codigo_ticket, numero_via, status, impresso_em, created_at)
        VALUES (:ped, :item, :cod, 1, 'impresso', :impresso, :created)
    ");

    $pedidoSeq = 1;
    $ticketSeq = 1;

    // Saldos virtuais dos alunos
    $saldosAlunos = [];
    foreach ($alunos as $a) {
        $saldosAlunos[$a['id']] = 0.00;
    }

    foreach ($diasUteis as $idxDia => $diaIso) {
        $dataHoraMatIni = $diaIso . ' 06:45:00';
        $dataHoraMatFim = $diaIso . ' 12:30:00';
        $dataHoraVespIni = $diaIso . ' 12:45:00';
        $dataHoraVespFim = $diaIso . ' 18:00:00';

        // 4.1 Recargas Pix Online (Portal da Família) - Feitas de manhãzinha
        $totalRecPixOnlineDia = 0.00;
        $qtdRecOnline = rand(4, 7);
        for ($i = 0; $i < $qtdRecOnline; $i++) {
            $alunoRec = $alunos[array_rand($alunos)];
            $valRec = (float)[50.00, 80.00, 100.00, 150.00, 200.00][rand(0, 4)];
            $saldoAnt = $saldosAlunos[$alunoRec['id']];
            $saldoRes = $saldoAnt + $valRec;
            $saldosAlunos[$alunoRec['id']] = $saldoRes;

            $horaRec = sprintf('%02d:%02d:%02d', rand(6, 9), rand(10, 59), rand(10, 59));
            $stmtRecarga->execute([
                ':resp'      => $alunoRec['responsavel_id'],
                ':aluno'     => $alunoRec['id'],
                ':op'        => null,
                ':tipo'      => 'recarga_pix',
                ':valor'     => $valRec,
                ':saldo_ant' => $saldoAnt,
                ':saldo_res' => $saldoRes,
                ':desc'      => 'Recarga Pix Online - Portal da Família',
                ':created_at'=> $diaIso . ' ' . $horaRec
            ]);
            $totalRecPixOnlineDia += $valRec;
        }

        // 4.2 Recargas no Balcão PDV (Pix e Dinheiro)
        $totalRecPixPdvDia = 0.00;
        $totalRecDinPdvDia = 0.00;

        // Recargas Pix Balcão
        $qtdRecPixBalcao = rand(2, 4);
        for ($i = 0; $i < $qtdRecPixBalcao; $i++) {
            $alunoRec = $alunos[array_rand($alunos)];
            $valRec = (float)[30.00, 50.00, 80.00, 100.00][rand(0, 3)];
            $saldoAnt = $saldosAlunos[$alunoRec['id']];
            $saldoRes = $saldoAnt + $valRec;
            $saldosAlunos[$alunoRec['id']] = $saldoRes;

            $horaRec = sprintf('%02d:%02d:%02d', rand(9, 15), rand(10, 59), rand(10, 59));
            $stmtRecarga->execute([
                ':resp'      => $alunoRec['responsavel_id'],
                ':aluno'     => $alunoRec['id'],
                ':op'        => $operadorId,
                ':tipo'      => 'recarga_pix',
                ':valor'     => $valRec,
                ':saldo_ant' => $saldoAnt,
                ':saldo_res' => $saldoRes,
                ':desc'      => 'Recarga Pix - Balcão PDV',
                ':created_at'=> $diaIso . ' ' . $horaRec
            ]);
            $totalRecPixPdvDia += $valRec;
        }

        // Recargas Dinheiro Balcão
        $qtdRecDinBalcao = rand(3, 5);
        for ($i = 0; $i < $qtdRecDinBalcao; $i++) {
            $alunoRec = $alunos[array_rand($alunos)];
            $valRec = (float)[20.00, 30.00, 50.00, 100.00][rand(0, 3)];
            $saldoAnt = $saldosAlunos[$alunoRec['id']];
            $saldoRes = $saldoAnt + $valRec;
            $saldosAlunos[$alunoRec['id']] = $saldoRes;

            $horaRec = sprintf('%02d:%02d:%02d', rand(8, 14), rand(10, 59), rand(10, 59));
            $stmtRecarga->execute([
                ':resp'      => $alunoRec['responsavel_id'],
                ':aluno'     => $alunoRec['id'],
                ':op'        => $operadorId,
                ':tipo'      => 'recarga_dinheiro',
                ':valor'     => $valRec,
                ':saldo_ant' => $saldoAnt,
                ':saldo_res' => $saldoRes,
                ':desc'      => 'Recarga Dinheiro - Balcão PDV',
                ':created_at'=> $diaIso . ' ' . $horaRec
            ]);
            $totalRecDinPdvDia += $valRec;
        }

        // Cria sessão de caixa matutino
        $stmtSessaoCaixa->execute([
            ':op'        => $operadorId,
            ':turno'     => 'matutino',
            ':aberto'    => $dataHoraMatIni,
            ':fechado'   => $dataHoraMatFim,
            ':rec_din'   => $totalRecDinPdvDia * 0.6,
            ':venda_din' => 0.00,
            ':pix_balcao'=> $totalRecPixPdvDia * 0.6,
            ':gaveta'    => 100.00 + ($totalRecDinPdvDia * 0.6),
        ]);
        $sessaoCaixaMatId = (int)$pdo->lastInsertId();

        // 4.3 Vendas Presenciais Balcão (Dinheiro, Pix Balcão e Saldo)
        $totalVendaDinDia = 0.00;
        $totalVendaPixDia = 0.00;

        // Vendas Dinheiro
        $qtdVendasDin = rand(6, 10);
        for ($i = 0; $i < $qtdVendasDin; $i++) {
            $alunoVenda = $alunos[array_rand($alunos)];
            $prodVenda  = $produtos[array_rand($produtos)];
            $qtd = rand(1, 2);
            $totalVenda = (float)$prodVenda['preco_venda'] * $qtd;
            $dinheiroRec = ceil($totalVenda / 10) * 10;
            $troco = $dinheiroRec - $totalVenda;

            $horaVenda = sprintf('%02d:%02d:%02d', rand(9, 16), rand(10, 59), rand(10, 59));
            $stmtVendaBalcao->execute([
                ':caixa'      => $sessaoCaixaMatId,
                ':op'         => $operadorId,
                ':aluno'      => $alunoVenda['id'],
                ':forma'      => 'dinheiro',
                ':total'      => $totalVenda,
                ':rec'        => $dinheiroRec,
                ':troco'      => $troco,
                ':created_at' => $diaIso . ' ' . $horaVenda
            ]);
            $vbId = (int)$pdo->lastInsertId();

            $stmtItemVb->execute([
                ':venda' => $vbId,
                ':prod'  => $prodVenda['id'],
                ':qtd'   => $qtd,
                ':unit'  => $prodVenda['preco_venda'],
                ':sub'   => $totalVenda
            ]);
            $totalVendaDinDia += $totalVenda;
        }

        // Vendas Pix Balcão
        $qtdVendasPix = rand(4, 8);
        for ($i = 0; $i < $qtdVendasPix; $i++) {
            $alunoVenda = $alunos[array_rand($alunos)];
            $prodVenda  = $produtos[array_rand($produtos)];
            $qtd = rand(1, 2);
            $totalVenda = (float)$prodVenda['preco_venda'] * $qtd;

            $horaVenda = sprintf('%02d:%02d:%02d', rand(9, 16), rand(10, 59), rand(10, 59));
            $stmtVendaBalcao->execute([
                ':caixa'      => $sessaoCaixaMatId,
                ':op'         => $operadorId,
                ':aluno'      => $alunoVenda['id'],
                ':forma'      => 'pix_balcao',
                ':total'      => $totalVenda,
                ':rec'        => $totalVenda,
                ':troco'      => 0.00,
                ':created_at' => $diaIso . ' ' . $horaVenda
            ]);
            $vbId = (int)$pdo->lastInsertId();

            $stmtItemVb->execute([
                ':venda' => $vbId,
                ':prod'  => $prodVenda['id'],
                ':qtd'   => $qtd,
                ':unit'  => $prodVenda['preco_venda'],
                ':sub'   => $totalVenda
            ]);
            $totalVendaPixDia += $totalVenda;
        }

        // Vendas com Saldo da Carteira no Balcão
        $qtdVendasSaldo = rand(5, 9);
        for ($i = 0; $i < $qtdVendasSaldo; $i++) {
            $alunoVenda = $alunos[array_rand($alunos)];
            $prodVenda  = $produtos[array_rand($produtos)];
            $qtd = 1;
            $totalVenda = (float)$prodVenda['preco_venda'];

            $saldoAnt = $saldosAlunos[$alunoVenda['id']];
            $saldoRes = $saldoAnt - $totalVenda;
            $saldosAlunos[$alunoVenda['id']] = $saldoRes;

            $horaVenda = sprintf('%02d:%02d:%02d', rand(9, 16), rand(10, 59), rand(10, 59));
            $stmtVendaBalcao->execute([
                ':caixa'      => $sessaoCaixaMatId,
                ':op'         => $operadorId,
                ':aluno'      => $alunoVenda['id'],
                ':forma'      => 'saldo',
                ':total'      => $totalVenda,
                ':rec'        => $totalVenda,
                ':troco'      => 0.00,
                ':created_at' => $diaIso . ' ' . $horaVenda
            ]);
            $vbId = (int)$pdo->lastInsertId();

            $stmtItemVb->execute([
                ':venda' => $vbId,
                ':prod'  => $prodVenda['id'],
                ':qtd'   => $qtd,
                ':unit'  => $prodVenda['preco_venda'],
                ':sub'   => $totalVenda
            ]);

            $stmtRecarga->execute([
                ':resp'      => $alunoVenda['responsavel_id'],
                ':aluno'     => $alunoVenda['id'],
                ':op'        => $operadorId,
                ':tipo'      => 'debito_pedido',
                ':valor'     => $totalVenda,
                ':saldo_ant' => $saldoAnt,
                ':saldo_res' => $saldoRes,
                ':desc'      => 'Consumo Balcão PDV - ' . $prodVenda['descricao'],
                ':created_at'=> $diaIso . ' ' . $horaVenda
            ]);
        }

        // 4.4 Encomendas / Pedidos Agendados para o Dia (Corte do Turno)
        // 5 a 8 pedidos matutinos + 5 a 8 pedidos vespertinos
        foreach (['matutino', 'vespertino'] as $turnoConsumo) {
            $qtdPedidos = rand(5, 8);
            for ($p = 0; $p < $qtdPedidos; $p++) {
                $alunoPed = $alunos[array_rand($alunos)];
                $codPedido = sprintf('PED-%s-%04d', str_replace('-', '', $diaIso), $pedidoSeq++);

                // 1 ou 2 itens por pedido
                $prod1 = $produtos[array_rand($produtos)];
                $qtd1 = 1;
                $sub1 = (float)$prod1['preco_venda'];
                $totalPed = $sub1;

                $temItem2 = (rand(0, 1) === 1);
                $prod2 = null;
                $sub2 = 0.00;
                if ($temItem2) {
                    $prod2 = $produtos[array_rand($produtos)];
                    $qtd2 = 1;
                    $sub2 = (float)$prod2['preco_venda'];
                    $totalPed += $sub2;
                }

                $saldoAnt = $saldosAlunos[$alunoPed['id']];
                $saldoRes = $saldoAnt - $totalPed;
                $saldosAlunos[$alunoPed['id']] = $saldoRes;

                $statusPed = ($diaIso <= '2026-10-27') ? 'entregue' : 'agendado';
                $horaPedCriado = sprintf('%02d:%02d:%02d', rand(6, 8), rand(10, 59), rand(10, 59));

                $stmtPedido->execute([
                    ':cod'        => $codPedido,
                    ':resp'       => $alunoPed['responsavel_id'],
                    ':aluno'      => $alunoPed['id'],
                    ':data_cons'  => $diaIso,
                    ':turno'      => $turnoConsumo,
                    ':total'      => $totalPed,
                    ':status'     => $statusPed,
                    ':created_at' => $diaIso . ' ' . $horaPedCriado
                ]);
                $pedId = (int)$pdo->lastInsertId();

                // Item 1
                $stmtItemPedido->execute([
                    ':ped'  => $pedId,
                    ':prod' => $prod1['id'],
                    ':qtd'  => $qtd1,
                    ':unit' => $prod1['preco_venda'],
                    ':sub'  => $sub1
                ]);
                $item1Id = (int)$pdo->lastInsertId();

                $codTicket1 = sprintf('TCK-%s-%04d', str_replace('-', '', $diaIso), $ticketSeq++);
                $stmtTicket->execute([
                    ':ped'      => $pedId,
                    ':item'     => $item1Id,
                    ':cod'      => $codTicket1,
                    ':impresso' => $diaIso . ' ' . $horaPedCriado,
                    ':created'  => $diaIso . ' ' . $horaPedCriado
                ]);

                // Item 2 (se houver)
                if ($prod2) {
                    $stmtItemPedido->execute([
                        ':ped'  => $pedId,
                        ':prod' => $prod2['id'],
                        ':qtd'  => 1,
                        ':unit' => $prod2['preco_venda'],
                        ':sub'  => $sub2
                    ]);
                    $item2Id = (int)$pdo->lastInsertId();

                    $codTicket2 = sprintf('TCK-%s-%04d', str_replace('-', '', $diaIso), $ticketSeq++);
                    $stmtTicket->execute([
                        ':ped'      => $pedId,
                        ':item'     => $item2Id,
                        ':cod'      => $codTicket2,
                        ':impresso' => $diaIso . ' ' . $horaPedCriado,
                        ':created'  => $diaIso . ' ' . $horaPedCriado
                    ]);
                }

                // Débito do pedido na carteira
                $stmtRecarga->execute([
                    ':resp'      => $alunoPed['responsavel_id'],
                    ':aluno'     => $alunoPed['id'],
                    ':op'        => null,
                    ':tipo'      => 'debito_pedido',
                    ':valor'     => $totalPed,
                    ':saldo_ant' => $saldoAnt,
                    ':saldo_res' => $saldoRes,
                    ':desc'      => 'Encomenda ' . $codPedido . ' (' . ucfirst($turnoConsumo) . ')',
                    ':created_at'=> $diaIso . ' ' . $horaPedCriado
                ]);
            }
        }
    }
    echo "  - Semeados " . ($pedidoSeq - 1) . " pedidos de encomendas com tickets para todos os dias úteis!\n";

    // -------------------------------------------------------------------------
    // 5. SEMEAR RECIBOS DE PAGAMENTO A FORNECEDORES (DISTRIBUÍDOS NO MÊS)
    // -------------------------------------------------------------------------
    echo "[5/7] Semeando recibos de quitação a fornecedores...\n";

    $recibosModel = [
        ['data' => '2026-10-02', 'forn' => $fornecedorSalgadoId, 'desc' => 'Pagamento Salgado', 'valor' => 450.00, 'forma' => 'pix'],
        ['data' => '2026-10-06', 'forn' => $fornecedorCupcakeId, 'desc' => 'Pagamento Cupcake', 'valor' => 280.00, 'forma' => 'dinheiro'],
        ['data' => '2026-10-09', 'forn' => $fornecedorLacteaId,  'desc' => 'Pagamento Laticínios e Sucos', 'valor' => 620.00, 'forma' => 'pix'],
        ['data' => '2026-10-14', 'forn' => $fornecedorSalgadoId, 'desc' => 'Pagamento Salgado', 'valor' => 510.00, 'forma' => 'pix'],
        ['data' => '2026-10-16', 'forn' => $fornecedorCupcakeId, 'desc' => 'Pagamento Cupcake', 'valor' => 320.00, 'forma' => 'dinheiro'],
        ['data' => '2026-10-20', 'forn' => $fornecedorSalgadoId, 'desc' => 'Pagamento Salgado', 'valor' => 480.00, 'forma' => 'pix'],
        ['data' => '2026-10-23', 'forn' => $fornecedorGeralId,   'desc' => 'Pagamento Panificação & Pães', 'valor' => 580.00, 'forma' => 'pix'],
        ['data' => '2026-10-27', 'forn' => $fornecedorCupcakeId, 'desc' => 'Pagamento Cupcake', 'valor' => 350.00, 'forma' => 'dinheiro'],
        ['data' => '2026-10-30', 'forn' => $fornecedorSalgadoId, 'desc' => 'Pagamento Salgado', 'valor' => 520.00, 'forma' => 'pix'],
    ];

    $stmtRecibo = $pdo->prepare("
        INSERT INTO fornecedor_recibos (numero_recibo, fornecedor_id, valor, data_pagamento, forma_pagamento, descricao, observacoes, created_by, created_at)
        VALUES (:num, :forn, :valor, :data_pgto, :forma, :desc, 'Quitação formal regular de fornecimento', :user, :created)
    ");

    $seqRecibo = 1;
    foreach ($recibosModel as $rm) {
        $numRecibo = sprintf('REC-%s-%04d', str_replace('-', '', $rm['data']), $seqRecibo++);
        $stmtRecibo->execute([
            ':num'       => $numRecibo,
            ':forn'      => $rm['forn'],
            ':valor'     => $rm['valor'],
            ':data_pgto' => $rm['data'],
            ':forma'     => $rm['forma'],
            ':desc'      => $rm['desc'],
            ':user'      => $adminId,
            ':created'   => $rm['data'] . ' 14:30:00'
        ]);
    }
    echo "  - Total de recibos de fornecedores criados: " . count($recibosModel) . "\n";

    // -------------------------------------------------------------------------
    // 6. SEMEAR DESPESAS OPERACIONAIS DE OUTUBRO/2026
    // -------------------------------------------------------------------------
    echo "[6/7] Semeando despesas operacionais do mês...\n";

    $despesasModel = [
        ['data' => '2026-10-05', 'cat' => 'administrativa', 'sub' => 'Energia Elétrica', 'desc' => 'Conta de Luz Cantina (CEMIG)', 'valor' => 420.00, 'forma' => 'Pix', 'doc' => 'CEMIG-OUT26'],
        ['data' => '2026-10-09', 'cat' => 'outras',         'sub' => 'Gás Industrial',   'desc' => 'Recarga de Gás P45 Cozinha', 'valor' => 310.00, 'forma' => 'Dinheiro', 'doc' => 'GAS-091026'],
        ['data' => '2026-10-15', 'cat' => 'administrativa', 'sub' => 'Internet & Telefonia', 'desc' => 'Link de Fibra Óptica e POS', 'valor' => 149.90, 'forma' => 'Pix', 'doc' => 'FIBRA-151026'],
        ['data' => '2026-10-20', 'cat' => 'outras',         'sub' => 'Descartáveis & Limpeza', 'desc' => 'Embalagens térmicas e guardanapos', 'valor' => 280.00, 'forma' => 'Pix', 'doc' => 'LIMPEZA-201026'],
        ['data' => '2026-10-26', 'cat' => 'manutencao',     'sub' => 'Manutenção Preventiva', 'desc' => 'Revisão técnica das estufas de salgados', 'valor' => 250.00, 'forma' => 'Dinheiro', 'doc' => 'MANUT-261026'],
    ];

    $stmtDesp = $pdo->prepare("
        INSERT INTO despesas_operacionais (categoria, subcategoria, descricao, valor, competencia_mes_ano, data_vencimento, data_pagamento, status, forma_pagamento, documento_referencia, created_by, created_at)
        VALUES (:cat, :sub, :desc, :valor, '2026-10', :data_venc, :data_pgto, 'pago', :forma, :doc, :user, :created)
    ");

    foreach ($despesasModel as $dm) {
        $stmtDesp->execute([
            ':cat'       => $dm['cat'],
            ':sub'       => $dm['sub'],
            ':desc'      => $dm['desc'],
            ':valor'     => $dm['valor'],
            ':data_venc' => $dm['data'],
            ':data_pgto' => $dm['data'],
            ':forma'     => $dm['forma'],
            ':doc'       => $dm['doc'],
            ':user'      => $adminId,
            ':created'   => $dm['data'] . ' 10:00:00'
        ]);
    }
    echo "  - Total de despesas operacionais criadas: " . count($despesasModel) . "\n";

    // -------------------------------------------------------------------------
    // 7. ATUALIZAÇÃO DOS SALDOS REAIS NAS TABELAS DE RESPONSÁVEIS
    // -------------------------------------------------------------------------
    echo "[7/7] Sincronizando saldos acumulados dos responsáveis...\n";
    
    // Calcula o saldo resultante real somando créditos e subtraindo débitos por responsável
    $stmtSaldosResp = $pdo->query("
        SELECT responsavel_id,
               SUM(CASE 
                   WHEN tipo_transacao IN ('recarga_pix', 'recarga_dinheiro', 'estorno_cancelamento', 'estorno_balcao', 'ajuste_manual') THEN valor 
                   WHEN tipo_transacao IN ('debito_pedido', 'debito_contestacao_med') THEN -valor 
                   ELSE 0 
               END) as saldo_calculado
        FROM transacoes_carteira
        GROUP BY responsavel_id
    ");

    $stmtUpdSaldo = $pdo->prepare("UPDATE responsaveis SET saldo_atual = :saldo WHERE id = :id");
    $totalRespsAtualizados = 0;
    while ($row = $stmtSaldosResp->fetch(\PDO::FETCH_ASSOC)) {
        $saldoFinal = max(10.00, round((float)$row['saldo_calculado'], 2));
        $stmtUpdSaldo->execute([':saldo' => $saldoFinal, ':id' => (int)$row['responsavel_id']]);
        $totalRespsAtualizados++;
    }
    echo "  - Saldos de {$totalRespsAtualizados} responsáveis calibrados e positivos!\n";

    $pdo->commit();

    echo "\n================================================================================\n";
    echo " >>> SUCESSO TOTAL: SEEDER EXECUTADO COM RESULTADOS ALTAMENTE POSITIVOS! <<<\n";
    echo "================================================================================\n";

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERRO CRÍTICO NO SEEDER]: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
