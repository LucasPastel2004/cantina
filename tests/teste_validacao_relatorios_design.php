<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Validação de Design e Sintaxe de Todos os Relatórios
 * 
 * Verifica que todos os relatórios renderizam com sucesso via PdfService::renderView
 * e que estão com a estilização padronizada (sem erros, com banners/cabeçalhos neutros/azul escuro, etc.)
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/services/PdfService.php';

echo "=== TESTE DE VALIDAÇÃO DE DESIGN E SINTAXE DOS RELATÓRIOS ===\n\n";

$testes = [
    'Arrecadação Financeira' => [
        'view' => 'relatorios/arrecadacao_pdf',
        'dados' => [
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
        ]
    ],
    'Vendas por Alimento' => [
        'view' => 'relatorios/vendas_alimento_pdf',
        'dados' => [
            'filtros' => ['emitir_vendas_alimento' => 'sim', 'emitir_vendas_aluno' => 'nao'],
            'vendas' => [
                ['produto_codigo' => 'SAL01', 'produto_nome' => 'Coxinha', 'categoria_nome' => 'Salgados', 'quantidade_total' => 20, 'preco_medio' => 7.0, 'subtotal' => 140.0]
            ],
            'totais' => ['quantidade_total' => 20, 'faturamento_total' => 140.0]
        ]
    ],
    'MED Negativados' => [
        'view' => 'relatorios/med_negativados_pdf',
        'dados' => [
            'dados' => [
                'familias' => [
                    ['responsavel_nome' => 'RESPONSÁVEL TESTE', 'responsavel_cpf' => '111.222.333-44', 'responsavel_telefone' => '(11) 99999-9999', 'saldo_atual' => -45.0, 'dependentes' => 'ALUNO 1, ALUNO 2']
                ],
                'totais' => ['total_familias' => 1, 'valor_total_negativo' => -45.0]
            ]
        ]
    ],
    'MED Histórico' => [
        'view' => 'relatorios/med_historico_pdf',
        'dados' => [
            'dados' => [
                'historico' => [
                    ['id' => 1, 'created_at' => '2026-10-01 10:00:00', 'status' => 'sucesso', 'mensagem' => 'Mensagem enviada com sucesso', 'responsavel_nome' => 'RESPONSÁVEL TESTE', 'destinatario' => '5511999999999', 'tipo' => 'notificacao_cobranca']
                ],
                'filtros' => ['status' => 'todos']
            ]
        ]
    ],
    'Corte de Turno' => [
        'view' => 'relatorios/corte_pdf',
        'dados' => [
            'dados' => [
                'data' => '2026-10-01',
                'turno' => 'matutino',
                'lanches' => [
                    ['codigo' => 'SAL01', 'descricao' => 'Coxinha de Frango', 'quantidade' => 15, 'valor_unitario' => 7.0, 'valor_total' => 105.0]
                ],
                'total_itens' => 15,
                'total_valor' => 105.0
            ]
        ]
    ],
    'Fechamento de Caixa' => [
        'view' => 'relatorios/fechamento_caixa_pdf',
        'dados' => [
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
        ]
    ],
    'Extrato do Aluno' => [
        'view' => 'relatorios/extrato_aluno_pdf',
        'dados' => [
            'dados' => [
                'aluno' => ['id' => 1, 'nome' => 'ALUNO MODELO DE TESTE', 'matricula' => 'MAT-12345', 'saldo_atual' => 85.5],
                'saldo_inicial' => 10.0,
                'saldo_atual' => 85.5,
                'total_gasto' => 24.5,
                'total_recargas' => 100.0,
                'total_lanches' => 3,
                'linhas' => [
                    ['data' => '2026-10-01', 'descricao' => 'SUCO NATURAL', 'quantidade' => 1, 'valor_unitario' => 6.5, 'valor_total' => 6.5, 'forma_pagamento' => 'Saldo', 'saldo' => 93.5, 'tipo' => 'consumo']
                ]
            ]
        ]
    ],
    'Estoque Movimentações' => [
        'view' => 'relatorios/estoque_movimentacoes_pdf',
        'dados' => [
            'movimentacoes' => [
                ['data_movimentacao' => '2026-10-01 08:00', 'tipo' => 'entrada', 'produto_codigo' => 'SAL01', 'produto_nome' => 'Coxinha', 'categoria_nome' => 'Salgados', 'quantidade' => 50, 'saldo_anterior' => 10, 'saldo_novo' => 60, 'usuario_nome' => 'Admin']
            ],
            'totais' => ['total_entradas' => 50, 'total_saidas' => 0, 'total_vendas' => 0, 'total_perdas' => 0, 'balanco_periodo' => 50, 'total_movimentacoes' => 1],
            'isPaisagem' => false,
            'agruparGrupo' => false,
            'quebraPagina' => false
        ]
    ],
    'Desconto em Folha Geral' => [
        'view' => 'relatorios/desconto_folha_pdf',
        'dados' => [
            'dadosFolha' => [
                'competencia' => '10/2026',
                'colaboradores' => [
                    ['id' => 1, 'nome' => 'PROFESSOR SILVA', 'matricula' => 'PROF-001', 'cargo' => 'Docente', 'departamento' => 'Ensino Médio', 'total_lanches' => 12, 'valor_total' => 108.0]
                ],
                'totais' => ['total_colaboradores' => 1, 'total_lanches' => 12, 'valor_total' => 108.0]
            ]
        ]
    ],
    'Desconto Folha Individual' => [
        'view' => 'relatorios/desconto_folha_individual_pdf',
        'dados' => [
            'dados' => [
                'colaborador' => ['id' => 1, 'nome' => 'PROFESSOR SILVA', 'matricula' => 'PROF-001', 'cargo' => 'Docente', 'cpf' => '123.456.789-00'],
                'competencia' => '10/2026',
                'lancamentos' => [
                    ['data' => '2026-10-01', 'descricao' => 'Café com Leite', 'quantidade' => 1, 'valor_unitario' => 4.5, 'valor_total' => 4.5]
                ],
                'total_itens' => 1,
                'total_valor' => 4.5
            ]
        ]
    ],
    'Baixas de Mercadorias' => [
        'view' => 'relatorios/baixas_mercadorias_pdf',
        'dados' => [
            'dados' => [
                'baixas' => [
                    ['data_baixa' => '2026-10-01', 'produto_codigo' => 'BEB01', 'produto_nome' => 'Suco Uva', 'quantidade' => 2, 'motivo' => 'Validade expirada', 'observacoes' => 'Descarte', 'usuario_nome' => 'Admin']
                ],
                'totais' => ['total_registros' => 1, 'total_itens' => 2],
                'filtros' => ['data_inicio' => '2026-10-01', 'data_fim' => '2026-10-15', 'motivo' => 'todos']
            ]
        ]
    ],
    'DRE Gerencial Anual' => [
        'view' => 'financeiro/dre_pdf',
        'dados' => [
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
        ]
    ],
    'Recarga em Dinheiro (Cupom 80mm)' => [
        'view' => 'relatorios/recarga_dinheiro_pdf',
        'dados' => [
            'dadosRecarga' => [
                'transacao_id' => 101,
                'sessao_caixa_id' => 5,
                'created_at' => '2026-10-01 10:30:00',
                'operador_nome' => 'Operador Teste',
                'responsavel_nome' => 'Responsável Exemplo',
                'responsavel_cpf' => '123.456.789-00',
                'valor' => 50.0,
                'saldo_anterior' => 20.0,
                'saldo_resultante' => 70.0,
                'dependentes' => [
                    ['nome' => 'Aluno Teste', 'matricula' => 'MAT-01']
                ]
            ]
        ]
    ]
];

$erros = 0;
$sucessos = 0;

foreach ($testes as $nome => $teste) {
    try {
        $html = \App\Services\PdfService::renderView($teste['view'], $teste['dados']);
        if (empty($html) || strlen($html) < 200) {
            echo "[FALHA] {$nome}: Retornou HTML vazio ou muito curto.\n";
            $erros++;
            continue;
        }

        // Checar ausência de estilos vermelhos de texto/cabeçalho nos relatórios A4
        if ($teste['view'] !== 'relatorios/recarga_dinheiro_pdf') {
            if (stripos($html, 'background: #b91c1c') !== false || stripos($html, 'color: #b91c1c') !== false) {
                echo "[ALERTA] {$nome}: Contém cor vermelha #b91c1c.\n";
            }
        }

        // Checar presença da barra de controle no-print (#1e293b)
        if (stripos($html, '#1e293b') === false && stripos($html, 'no-print') !== false) {
            echo "[AVISO] {$nome}: Barra no-print não contém cor #1e293b.\n";
        }

        echo "[OK] {$nome} (HTML: " . strlen($html) . " bytes)\n";
        $sucessos++;
    } catch (\Throwable $e) {
        echo "[ERRO] {$nome}: Exceção: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n";
        $erros++;
    }
}

echo "\nResultado final: {$sucessos} sucessos, {$erros} erros.\n";
exit($erros === 0 ? 0 : 1);
