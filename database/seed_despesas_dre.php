<?php
/**
 * Seeder Idempotente: Despesas Operacionais e Calibração de Custos para DRE Gerencial
 * 
 * Cantina Web — Colégio Monteiro Lobato
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance();

echo "========================================================\n";
echo "   Cantina Web — Seeder de Despesas Operacionais & DRE\n";
echo "========================================================\n\n";

// 1. Obtém o usuário administrador para autoria
$stmtAdmin = $pdo->query("SELECT id FROM usuarios WHERE perfil = 'administrador' AND deleted_at IS NULL LIMIT 1");
$adminId = $stmtAdmin->fetchColumn() ?: 1;

// 2. Calibra o preco_custo dos produtos se estiver zerado (35% a 45% do preco_venda)
$pdo->exec("
    UPDATE `produtos` 
    SET `preco_custo` = ROUND(`preco_venda` * 0.42, 2) 
    WHERE `preco_custo` IS NULL OR `preco_custo` = 0.00;
");
echo "✓ Preços de custo dos produtos calibrados para apuração de CMV real.\n";

// 3. Verifica se já existem despesas cadastradas
$totalDespesas = (int)$pdo->query("SELECT COUNT(*) FROM `despesas_operacionais` WHERE `deleted_at` IS NULL")->fetchColumn();

if ($totalDespesas > 0) {
    echo "✓ Despesas operacionais já existentes no banco ({$totalDespesas} lançamentos). Nenhuma duplicata gerada.\n";
} else {
    // 4. Cria despesas operacionais representativas para 2026 (Janeiro a Outubro)
    $meses = [
        '2026-01', '2026-02', '2026-03', '2026-04', '2026-05',
        '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'
    ];

    $templateDespesas = [
        // Administrativas
        [
            'categoria'    => 'administrativa',
            'subcategoria' => 'Energia Elétrica',
            'descricao'    => 'Conta de Luz / Energia da Cantina (CEMIG)',
            'valor'        => 380.00,
            'dia_venc'     => 15,
            'forma_pgto'   => 'Débito Automático',
            'doc'          => 'FAT-ENERGIA-',
        ],
        [
            'categoria'    => 'administrativa',
            'subcategoria' => 'Água & Saneamento',
            'descricao'    => 'Conta de Água e Esgoto da Cozinha',
            'valor'        => 120.00,
            'dia_venc'     => 10,
            'forma_pgto'   => 'Boleto',
            'doc'          => 'BOL-AGUA-',
        ],
        [
            'categoria'    => 'administrativa',
            'subcategoria' => 'Comunicação & Internet',
            'descricao'    => 'Link de Internet Fibra e Suporte POS/WhatsApp',
            'valor'        => 149.90,
            'dia_venc'     => 20,
            'forma_pgto'   => 'Pix',
            'doc'          => 'REC-FIBRA-',
        ],
        [
            'categoria'    => 'administrativa',
            'subcategoria' => 'Contabilidade',
            'descricao'    => 'Honorários Contábeis Mensais da Cantina',
            'valor'        => 450.00,
            'dia_venc'     => 25,
            'forma_pgto'   => 'Transferência',
            'doc'          => 'NF-CONTAB-',
        ],

        // Pessoal
        [
            'categoria'    => 'pessoal',
            'subcategoria' => 'Salários & Auxílio',
            'descricao'    => 'Folha de Pagamento da Equipe de Cozinha / Balcão',
            'valor'        => 2800.00,
            'dia_venc'     => 5,
            'forma_pgto'   => 'Transferência',
            'doc'          => 'FOLHA-PGTO-',
        ],
        [
            'categoria'    => 'pessoal',
            'subcategoria' => 'Encargos & FGTS',
            'descricao'    => 'Guia FGTS e Previdência dos Funcionários',
            'valor'        => 420.00,
            'dia_venc'     => 7,
            'forma_pgto'   => 'Boleto',
            'doc'          => 'GUIA-FGTS-',
        ],

        // Vendas & Embalagens
        [
            'categoria'    => 'vendas',
            'subcategoria' => 'Embalagens & Descartáveis',
            'descricao'    => 'Guardanapos, copos biodegradáveis e sacos térmicos',
            'valor'        => 210.00,
            'dia_venc'     => 12,
            'forma_pgto'   => 'Pix',
            'doc'          => 'NF-EMBALAGEM-',
        ],

        // Financeiras
        [
            'categoria'    => 'financeira',
            'subcategoria' => 'Tarifas Bancárias',
            'descricao'    => 'Tarifas de Manutenção de Conta PJ e Gateway Pix',
            'valor'        => 89.90,
            'dia_venc'     => 28,
            'forma_pgto'   => 'Débito Automático',
            'doc'          => 'EXT-BCO-',
        ],

        // Manutenção (bimestral)
        [
            'categoria'    => 'manutencao',
            'subcategoria' => 'Equipamentos & Fornos',
            'descricao'    => 'Revisão preventiva e limpeza de fornos/freezers',
            'valor'        => 280.00,
            'dia_venc'     => 18,
            'forma_pgto'   => 'Pix',
            'doc'          => 'ORD-MANUT-',
            'apenas_pares' => true,
        ],
    ];

    $sqlInsert = "INSERT INTO `despesas_operacionais` (
        `categoria`, `subcategoria`, `descricao`, `valor`, `competencia_mes_ano`,
        `data_vencimento`, `data_pagamento`, `status`, `forma_pagamento`,
        `documento_referencia`, `observacoes`, `created_by`
    ) VALUES (
        :categoria, :subcategoria, :descricao, :valor, :competencia,
        :data_vencimento, :data_pagamento, :status, :forma_pagamento,
        :documento_referencia, :observacoes, :created_by
    )";
    $stmt = $pdo->prepare($sqlInsert);

    $totalInseridas = 0;
    foreach ($meses as $comp) {
        $mesNum = (int)substr($comp, 5, 2);
        $anoNum = (int)substr($comp, 0, 4);

        foreach ($templateDespesas as $item) {
            if (!empty($item['apenas_pares']) && ($mesNum % 2 !== 0)) {
                continue; // pula meses ímpares para despesas bimestrais
            }

            $diaVenc = min((int)$item['dia_venc'], 28);
            $dtVenc = sprintf('%04d-%02d-%02d', $anoNum, $mesNum, $diaVenc);

            // Se for mês passado ou atual antes do dia de vencimento, define pago
            $hoje = date('Y-m-d');
            $status = ($dtVenc <= $hoje) ? 'pago' : 'pendente';
            $dtPgto = ($status === 'pago') ? $dtVenc : null;

            // Pequena variação para não ficar estático artificial
            $fator = 1.0 + (sin($mesNum + $diaVenc) * 0.05);
            $valorFinal = round($item['valor'] * $fator, 2);

            $stmt->execute([
                ':categoria'            => $item['categoria'],
                ':subcategoria'         => $item['subcategoria'],
                ':descricao'            => $item['descricao'],
                ':valor'                => $valorFinal,
                ':competencia'          => $comp,
                ':data_vencimento'      => $dtVenc,
                ':data_pagamento'       => $dtPgto,
                ':status'               => $status,
                ':forma_pagamento'      => $item['forma_pgto'],
                ':documento_referencia' => $item['doc'] . substr($comp, 0, 4) . $mesNum,
                ':observacoes'          => 'Lançamento automático de rotina operacional da cantina.',
                ':created_by'           => $adminId,
            ]);
            $totalInseridas++;
        }
    }

    echo "✓ {$totalInseridas} despesas operacionais inseridas com sucesso para o ano de 2026!\n";
}

echo "\n=> Seeder executado com sucesso absoluto.\n";
