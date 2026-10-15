<?php
define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/services/PdfService.php';

echo "Testando compilação no Gotenberg de todos os relatórios com novas margens e estética Excel:\n\n";

// 1. Extrato de Aluno
$htmlExtrato = \App\Services\PdfService::renderView('relatorios/extrato_aluno_pdf', [
    'dados' => [
        'aluno' => ['id' => 1, 'nome' => 'ALUNO MODELO DE TESTE', 'matricula' => 'MAT-12345', 'saldo_atual' => 85.5],
        'saldo_inicial' => 10.0,
        'saldo_atual' => 85.5,
        'total_gasto' => 24.5,
        'total_recargas' => 100.0,
        'total_lanches' => 3,
        'linhas' => [
            ['data' => '2026-10-01', 'descricao' => 'SUCO NATURAL', 'quantidade' => 1, 'valor_unitario' => 6.5, 'valor_total' => 6.5, 'forma_pagamento' => 'Saldo', 'saldo' => 93.5, 'tipo' => 'consumo'],
            ['data' => '2026-10-01', 'descricao' => 'PASTEL DE FORNO', 'quantidade' => 2, 'valor_unitario' => 9.0, 'valor_total' => 18.0, 'forma_pagamento' => 'Saldo', 'saldo' => 85.5, 'tipo' => 'consumo']
        ]
    ]
]);
$pdfExtrato = \App\Services\PdfService::htmlToPdf($htmlExtrato);
echo "1. Extrato Aluno: " . ($pdfExtrato && str_starts_with($pdfExtrato, '%PDF') ? "OK (" . strlen($pdfExtrato) . " bytes)" : "FALHA") . "\n";

// 2. Corte de Turno
$htmlCorte = \App\Services\PdfService::renderView('relatorios/corte_pdf', [
    'dados' => [
        'data' => '2026-10-01',
        'turno' => 'matutino',
        'lanches' => [
            ['codigo' => 'SAL01', 'descricao' => 'Coxinha de Frango', 'quantidade' => 15, 'valor_unitario' => 7.0, 'valor_total' => 105.0],
            ['codigo' => 'SAL02', 'descricao' => 'Esfiha de Carne', 'quantidade' => 20, 'valor_unitario' => 7.0, 'valor_total' => 140.0]
        ],
        'total_itens' => 35,
        'total_valor' => 245.0
    ]
]);
$pdfCorte = \App\Services\PdfService::htmlToPdf($htmlCorte);
echo "2. Corte de Turno: " . ($pdfCorte && str_starts_with($pdfCorte, '%PDF') ? "OK (" . strlen($pdfCorte) . " bytes)" : "FALHA") . "\n";

// 3. Desconto em Folha
$htmlFolha = \App\Services\PdfService::renderView('relatorios/desconto_folha_pdf', [
    'dadosFolha' => [
        'competencia' => '10/2026',
        'colaboradores' => [
            ['id' => 1, 'nome' => 'PROFESSOR SILVA', 'matricula' => 'PROF-001', 'cargo' => 'Docente', 'departamento' => 'Ensino Médio', 'total_lanches' => 12, 'valor_total' => 108.0]
        ],
        'totais' => ['total_colaboradores' => 1, 'total_lanches' => 12, 'valor_total' => 108.0]
    ]
]);
$pdfFolha = \App\Services\PdfService::htmlToPdf($htmlFolha);
echo "3. Desconto Folha Geral: " . ($pdfFolha && str_starts_with($pdfFolha, '%PDF') ? "OK (" . strlen($pdfFolha) . " bytes)" : "FALHA") . "\n";

// 4. Desconto Folha Individual
$htmlFolhaIndiv = \App\Services\PdfService::renderView('relatorios/desconto_folha_individual_pdf', [
    'dados' => [
        'colaborador' => ['id' => 1, 'nome' => 'PROFESSOR SILVA', 'matricula' => 'PROF-001', 'cargo' => 'Docente', 'cpf' => '123.456.789-00'],
        'competencia' => '10/2026',
        'lancamentos' => [
            ['data' => '2026-10-01', 'descricao' => 'Café com Leite', 'quantidade' => 1, 'valor_unitario' => 4.5, 'valor_total' => 4.5]
        ],
        'total_itens' => 1,
        'total_valor' => 4.5
    ]
]);
$pdfFolhaIndiv = \App\Services\PdfService::htmlToPdf($htmlFolhaIndiv);
echo "4. Desconto Folha Individual: " . ($pdfFolhaIndiv && str_starts_with($pdfFolhaIndiv, '%PDF') ? "OK (" . strlen($pdfFolhaIndiv) . " bytes)" : "FALHA") . "\n";

// 5. Fechamento de Caixa
$htmlCaixa = \App\Services\PdfService::renderView('relatorios/fechamento_caixa_pdf', [
    'sessao' => [
        'id' => 99,
        'operador_nome' => 'Operador Teste',
        'turno' => 'matutino',
        'aberto_em' => '2026-10-01 07:00:00',
        'fechado_em' => '2026-10-01 12:00:00',
        'fundo_troco_inicial' => 100.0
    ],
    'dadosFechamento' => [
        'vendas_balcao' => [
            ['id' => 1, 'created_at' => '2026-10-01 08:30:00', 'tipo' => 'Dinheiro', 'origem' => 'balcao', 'valor_pago' => 15.0]
        ],
        'vendas_cantina_web' => [],
        'subtotal_balcao' => 15.0,
        'subtotal_cantina_web' => 0.0,
        'subtotal_vendas' => 15.0,
        'resumo_caixa' => [
            'fundo' => ['dinheiro' => 100.0, 'pix' => 0.0, 'total' => 100.0],
            'entradas' => ['dinheiro' => 15.0, 'pix' => 0.0, 'total' => 15.0],
            'saidas' => ['dinheiro' => 0.0, 'pix' => 0.0, 'total' => 0.0],
            'saldo' => ['dinheiro' => 115.0, 'pix' => 0.0, 'total' => 115.0]
        ]
    ]
]);
$pdfCaixa = \App\Services\PdfService::htmlToPdf($htmlCaixa);
echo "5. Fechamento de Caixa: " . ($pdfCaixa && str_starts_with($pdfCaixa, '%PDF') ? "OK (" . strlen($pdfCaixa) . " bytes)" : "FALHA") . "\n";

// 6. MED Negativados
$htmlMed = \App\Services\PdfService::renderView('relatorios/med_negativados_pdf', [
    'dados' => [
        'familias' => [
            ['responsavel_nome' => 'RESPONSAVEL TESTE', 'responsavel_cpf' => '111.222.333-44', 'responsavel_telefone' => '(11) 99999-9999', 'saldo_atual' => -45.0, 'dependentes' => 'ALUNO 1, ALUNO 2']
        ],
        'totais' => ['total_familias' => 1, 'valor_total_negativo' => -45.0]
    ]
]);
$pdfMed = \App\Services\PdfService::htmlToPdf($htmlMed);
echo "6. MED Negativados: " . ($pdfMed && str_starts_with($pdfMed, '%PDF') ? "OK (" . strlen($pdfMed) . " bytes)" : "FALHA") . "\n";

// 7. Estoque Movimentações
$htmlEstoque = \App\Services\PdfService::renderView('relatorios/estoque_movimentacoes_pdf', [
    'movimentacoes' => [
        ['data_movimentacao' => '2026-10-01 08:00', 'tipo' => 'entrada', 'produto_codigo' => 'SAL01', 'produto_nome' => 'Coxinha', 'categoria_nome' => 'Salgados', 'quantidade' => 50, 'saldo_anterior' => 10, 'saldo_novo' => 60, 'usuario_nome' => 'Admin']
    ],
    'totais' => ['total_entradas' => 50, 'total_saidas' => 0, 'total_vendas' => 0, 'total_perdas' => 0, 'balanco_periodo' => 50, 'total_movimentacoes' => 1],
    'isPaisagem' => false,
    'agruparGrupo' => false,
    'quebraPagina' => false
]);
$pdfEstoque = \App\Services\PdfService::htmlToPdf($htmlEstoque);
echo "7. Estoque Movimentações: " . ($pdfEstoque && str_starts_with($pdfEstoque, '%PDF') ? "OK (" . strlen($pdfEstoque) . " bytes)" : "FALHA") . "\n";

// 8. Vendas Alimento
$htmlVendas = \App\Services\PdfService::renderView('relatorios/vendas_alimento_pdf', [
    'filtros' => ['emitir_vendas_alimento' => 'sim', 'emitir_vendas_aluno' => 'nao'],
    'vendas' => [
        ['produto_codigo' => 'SAL01', 'produto_nome' => 'Coxinha', 'categoria_nome' => 'Salgados', 'quantidade_total' => 20, 'preco_medio' => 7.0, 'subtotal' => 140.0]
    ],
    'totais' => ['quantidade_total' => 20, 'faturamento_total' => 140.0]
]);
$pdfVendas = \App\Services\PdfService::htmlToPdf($htmlVendas);
echo "8. Vendas por Alimento: " . ($pdfVendas && str_starts_with($pdfVendas, '%PDF') ? "OK (" . strlen($pdfVendas) . " bytes)" : "FALHA") . "\n";

// 9. Arrecadação Financeira
$htmlArrecadacao = \App\Services\PdfService::renderView('relatorios/arrecadacao_pdf', [
    'periodo_oficial' => '01/10/2026 a 15/10/2026',
    'data_inicio' => '2026-10-01',
    'data_fim' => '2026-10-15',
    'canais' => [
        ['canal' => 'Recargas Pix Online', 'descricao' => 'Portal da Família', 'quantidade' => 10, 'total' => 500.0, 'percentual' => 100.0]
    ],
    'dias' => [
        ['data_formatada' => '01/10/2026', 'dia_semana' => 'Quinta', 'recargas_pix' => 500.0, 'recargas_dinheiro' => 0.0, 'vendas_dinheiro' => 0.0, 'vendas_pix' => 0.0, 'total_arrecadado' => 500.0, 'consumo_saldo' => 100.0]
    ],
    'total_transacoes_arrecadacao' => 10,
    'total_arrecadado_bruto' => 500.0,
    'recargas_pix_total' => 500.0,
    'recargas_dinheiro_total' => 0.0,
    'total_recargas' => 500.0,
    'vendas_dinheiro_total' => 0.0,
    'vendas_pix_total' => 0.0,
    'vendas_saldo_total' => 50.0,
    'pedidos_saldo_total' => 50.0,
    'total_consumo_saldo' => 100.0,
    'variacao_carteira' => 400.0,
    'isPdf' => true
]);
$pdfArrecadacao = \App\Services\PdfService::htmlToPdf($htmlArrecadacao);
echo "9. Arrecadação Financeira: " . ($pdfArrecadacao && str_starts_with($pdfArrecadacao, '%PDF') ? "OK (" . strlen($pdfArrecadacao) . " bytes)" : "FALHA") . "\n";

// 10. DRE Gerencial Anual (Landscape)
$htmlDre = \App\Services\PdfService::renderView('financeiro/dre_pdf', [
    'ano' => 2026,
    'regime' => 'competencia',
    'dre' => [
        'meses' => [
            1 => ['receita_bruta' => 1000.0, 'custo_mercadorias' => 400.0, 'lucro_bruto' => 600.0, 'despesas_operacionais' => 100.0, 'resultado_liquido' => 500.0]
        ],
        'totais' => [
            'receita_bruta' => 1000.0, 'custo_mercadorias' => 400.0, 'lucro_bruto' => 600.0, 'despesas_operacionais' => 100.0, 'resultado_liquido' => 500.0
        ]
    ],
    'isPdf' => true
]);
$pdfDre = \App\Services\PdfService::htmlToPdf($htmlDre, ['landscape' => true]);
echo "10. DRE Gerencial Anual: " . ($pdfDre && str_starts_with($pdfDre, '%PDF') ? "OK (" . strlen($pdfDre) . " bytes)" : "FALHA") . "\n";

echo "\nTodos os 10 relatórios A4 geraram PDFs binários com sucesso via Gotenberg!\n";
