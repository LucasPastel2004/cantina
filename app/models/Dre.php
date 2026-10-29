<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Model: Dre (Demonstração do Resultado do Exercício)
 * 
 * Motor contábil e analítico para apuração de Receitas (Online e Balcão), Deduções (Estornos),
 * Custo das Mercadorias Vendidas (CMV), Lucro Bruto, Despesas Operacionais por Categoria
 * e Resultado Líquido do Exercício com comparativo mês a mês.
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/alterações sistema guideline/Estrutura de uma DRE.pdf e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Dre extends Model
{
    /**
     * Nomes oficiais dos meses em português.
     */
    public const MESES_PT = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];

    public const MESES_ABREV = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez',
    ];

    /**
     * Calcula o DRE consolidado de uma competência mensal específica (YYYY-MM).
     * Opera por padrão no Modelo Contábil Unificado (Duplo Estágio com Ponte de Conciliação de Carteira:
     * Apuração de Vendas/Consumo + Conciliação de Recargas/Custódia + Resultado Financeiro em Conta sem duplicidade).
     * Mantém suporte retrocompatível aos parâmetros 'competencia' e 'caixa'.
     */
    public function calcularCompetencia(string $competencia, string $regime = 'unificado'): array
    {
        $pdo = self::getConnection();

        // 1. Data inicial e final do mês
        $partes = explode('-', $competencia);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim    = date('Y-m-t', strtotime($dataInicio));

        // 2. Receita Bruta de Vendas Online (Pedidos) — 100% via Saldo da Carteira
        $sqlOnline = "SELECT 
                        COALESCE(SUM(p.valor_total), 0.00) as total,
                        COUNT(p.id) as qtd_pedidos
                      FROM `pedidos` p
                      WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim
                        AND p.status NOT IN ('cancelado', 'estornado')";
        $stmtOnline = $pdo->prepare($sqlOnline);
        $stmtOnline->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $resOnline = $stmtOnline->fetch(\PDO::FETCH_ASSOC);
        $vendasOnline = (float)($resOnline['total'] ?? 0.00);

        // 3. Receita Bruta de Vendas Presenciais (Balcão)
        $sqlBalcao = "SELECT 
                        COALESCE(SUM(vb.valor_total), 0.00) as total,
                        COUNT(vb.id) as qtd_vendas
                      FROM `vendas_balcao` vb
                      WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                        AND (vb.status != 'estornada' OR vb.status IS NULL)
                        AND vb.estornado_em IS NULL";
        $stmtBalcao = $pdo->prepare($sqlBalcao);
        $stmtBalcao->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $resBalcao = $stmtBalcao->fetch(\PDO::FETCH_ASSOC);
        $vendasBalcaoTotal = (float)($resBalcao['total'] ?? 0.00);

        // Vendas Balcão em Saldo (Consumo de Carteira no PDV)
        $sqlBalcaoSaldo = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                           FROM `vendas_balcao` vb
                           WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                             AND vb.forma_pagamento = 'saldo'
                             AND (vb.status != 'estornada' OR vb.status IS NULL)
                             AND vb.estornado_em IS NULL";
        $stmtBS = $pdo->prepare($sqlBalcaoSaldo);
        $stmtBS->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $consumoSaldoBalcao = (float)$stmtBS->fetchColumn();
        $consumoSaldoOnline = $vendasOnline;
        $consumoSaldoTotal  = round($consumoSaldoOnline + $consumoSaldoBalcao, 2);

        // Vendas Diretas no Balcão (Dinheiro e Pix — Entrada Direta sem passar por Recarga)
        $sqlBalcaoDinheiro = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                              FROM `vendas_balcao` vb
                              WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                                AND vb.forma_pagamento = 'dinheiro'
                                AND (vb.status != 'estornada' OR vb.status IS NULL)
                                AND vb.estornado_em IS NULL";
        $stmtBD = $pdo->prepare($sqlBalcaoDinheiro);
        $stmtBD->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $vendasBalcaoDinheiro = (float)$stmtBD->fetchColumn();

        $sqlBalcaoPix = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                         FROM `vendas_balcao` vb
                         WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                           AND vb.forma_pagamento IN ('pix', 'pix_balcao')
                           AND (vb.status != 'estornada' OR vb.status IS NULL)
                           AND vb.estornado_em IS NULL";
        $stmtBP = $pdo->prepare($sqlBalcaoPix);
        $stmtBP->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $vendasBalcaoPix = (float)$stmtBP->fetchColumn();

        $vendasDiretasBalcao = round($vendasBalcaoDinheiro + $vendasBalcaoPix, 2);

        // Recargas da Carteira no Mês (Cash-In Real Depositado na Conta/Caixa)
        $sqlRecargasPix = "SELECT COALESCE(SUM(tc.valor), 0.00)
                           FROM `transacoes_carteira` tc
                           WHERE tc.tipo_transacao = 'recarga_pix'
                             AND DATE(tc.created_at) BETWEEN :dt_inicio AND :dt_fim";
        $stmtRP = $pdo->prepare($sqlRecargasPix);
        $stmtRP->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $recargasPix = (float)$stmtRP->fetchColumn();

        // Segregação de Recargas Pix: Online (Portal da Família) vs Presencial (Balcão/PDV)
        $sqlRecargasPixPdv = "SELECT COALESCE(SUM(tc.valor), 0.00)
                              FROM `transacoes_carteira` tc
                              WHERE tc.tipo_transacao = 'recarga_pix'
                                AND (tc.operador_id IS NOT NULL OR tc.descricao LIKE '%Balcão%' OR tc.descricao LIKE '%PDV%')
                                AND DATE(tc.created_at) BETWEEN :dt_inicio AND :dt_fim";
        $stmtRPPdv = $pdo->prepare($sqlRecargasPixPdv);
        $stmtRPPdv->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $recargasPixPdv = (float)$stmtRPPdv->fetchColumn();
        $recargasPixOnline = max(0.00, round($recargasPix - $recargasPixPdv, 2));

        $sqlRecargasDinheiro = "SELECT COALESCE(SUM(tc.valor), 0.00)
                                FROM `transacoes_carteira` tc
                                WHERE tc.tipo_transacao = 'recarga_dinheiro'
                                  AND DATE(tc.created_at) BETWEEN :dt_inicio AND :dt_fim";
        $stmtRD = $pdo->prepare($sqlRecargasDinheiro);
        $stmtRD->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $recargasDinheiro = (float)$stmtRD->fetchColumn();

        $recargasBalcao = round($recargasPixPdv + $recargasDinheiro, 2);
        $totalRecargas  = round($recargasPix + $recargasDinheiro, 2);

        // Arrecadação Real na Conta (Cash-In Bruto: Recargas de Saldo + Vendas Diretas no Balcão)
        // Nota: O consumo de saldo NÃO entra aqui porque o dinheiro já entrou via Recargas.
        $arrecadacaoBruta = round($totalRecargas + $vendasDiretasBalcao, 2);

        // Faturamento Bruto de Vendas e Consumo (Lanches Entregues: Consumo de Saldo + Vendas Diretas)
        $receitaVendasBruta = round($vendasOnline + $vendasBalcaoTotal, 2);

        if ($regime === 'caixa') {
            $receitaOnline = $recargasPix;
            $receitaBalcao = round($recargasDinheiro + $vendasDiretasBalcao, 2);
            $receitaBruta  = $arrecadacaoBruta;
        } else {
            // No DRE Unificado (e Competência): A linha de topo do Bloco 1 é a Receita Bruta de Vendas (Consumo Real)
            $receitaOnline = $vendasOnline;
            $receitaBalcao = $vendasBalcaoTotal;
            $receitaBruta  = $receitaVendasBruta;
        }

        // 4. Deduções e Abatimentos (Estornos)
        // 4.1 Estornos de Balcão
        $sqlEstornoBalcao = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                             FROM `vendas_balcao` vb
                             WHERE DATE(vb.estornado_em) BETWEEN :dt_inicio AND :dt_fim
                                OR (vb.status = 'estornada' AND DATE(vb.created_at) BETWEEN :dt_inicio2 AND :dt_fim2)";
        $stmtEstornoB = $pdo->prepare($sqlEstornoBalcao);
        $stmtEstornoB->execute([
            ':dt_inicio'  => $dataInicio,
            ':dt_fim'     => $dataFim,
            ':dt_inicio2' => $dataInicio,
            ':dt_fim2'    => $dataFim,
        ]);
        $estornosBalcao = (float)$stmtEstornoB->fetchColumn();

        // 4.2 Estornos / Cancelamentos de Pedidos Online
        $sqlEstornoOnline = "SELECT COALESCE(SUM(p.valor_total), 0.00)
                             FROM `pedidos` p
                             WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim
                               AND (p.status = 'estornado' OR p.estornado_em IS NOT NULL)";
        $stmtEstornoO = $pdo->prepare($sqlEstornoOnline);
        $stmtEstornoO->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $estornosOnline = (float)$stmtEstornoO->fetchColumn();

        $totalDeducoes = round($estornosBalcao + $estornosOnline, 2);

        // 5. Receita Líquida de Vendas e Arrecadação Líquida em Conta
        $receitaLiquida     = max(0.00, round($receitaBruta - $totalDeducoes, 2));
        $receitaVendasLiq   = max(0.00, round($receitaVendasBruta - $totalDeducoes, 2));
        $arrecadacaoLiquida = max(0.00, round($arrecadacaoBruta - $totalDeducoes, 2));

        // 6. Pagamento de Fornecedores (Recibos Emitidos em fornecedor_recibos) — Substitui o CMV teórico (Zero Duplicidade)
        $despesasFornecedores = $this->calcularRecibosFornecedoresPeriodo($dataInicio, $dataFim);
        $cmv = $despesasFornecedores;

        // 7. Lucro Bruto Operacional (Receita Líquida - Pagamento de Fornecedores)
        $lucroBruto = round($receitaLiquida - $cmv, 2);
        $margemBrutaPercent = $receitaLiquida > 0 ? ($lucroBruto / $receitaLiquida) * 100 : 0.0;

        // 8. Despesas Operacionais por Categoria (Bloco separado de Pagamento de Fornecedores)
        $sqlDespesas = "SELECT 
                            d.categoria,
                            COALESCE(SUM(d.valor), 0.00) as total
                        FROM `despesas_operacionais` d
                        WHERE d.competencia_mes_ano = :comp
                          AND d.status != 'cancelado'
                          AND d.deleted_at IS NULL
                        GROUP BY d.categoria";
        $stmtDesp = $pdo->prepare($sqlDespesas);
        $stmtDesp->execute([':comp' => $competencia]);
        $despPorCat = $stmtDesp->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        $despesasVendas       = (float)($despPorCat[DespesaOperacional::CATEGORIA_VENDAS] ?? 0.00);
        $despesasAdmin        = (float)($despPorCat[DespesaOperacional::CATEGORIA_ADMINISTRATIVA] ?? 0.00);
        $despesasPessoal      = (float)($despPorCat[DespesaOperacional::CATEGORIA_PESSOAL] ?? 0.00);
        $despesasManutencao   = (float)($despPorCat[DespesaOperacional::CATEGORIA_MANUTENCAO] ?? 0.00);
        $despesasFinanceiras  = (float)($despPorCat[DespesaOperacional::CATEGORIA_FINANCEIRA] ?? 0.00);
        $despesasOutras       = (float)($despPorCat[DespesaOperacional::CATEGORIA_OUTRAS] ?? 0.00);

        $subtotalDespesasBloco = round(
            $despesasVendas + $despesasAdmin + $despesasPessoal + 
            $despesasManutencao + $despesasFinanceiras + $despesasOutras,
            2
        );

        // Total do bloco de despesas operacionais (sem duplicar fornecedores, que já foi abatido no Lucro Bruto)
        $totalDespesasOperacionais = $subtotalDespesasBloco;

        // 9. Resultado Líquido Operacional (Vendas & Consumo)
        $resultadoLiquido = round($lucroBruto - $totalDespesasOperacionais, 2);
        $margemLiquidaPercent = $receitaLiquida > 0 ? ($resultadoLiquido / $receitaLiquida) * 100 : 0.0;

        // 10. Ponte de Conciliação da Carteira Pré-Paga e Resultado Financeiro em Conta (Caixa Real)
        // Variação Líquida de Saldo em Conta = (+) Recargas Recebidas - (-) Consumo de Saldo em Vendas
        $diferencaRecargaConsumo = round($totalRecargas - $consumoSaldoTotal, 2);

        // Resultado Operacional de Vendas puro (independente do filtro de regime legado)
        $lucroVendasPuro = round(($receitaVendasLiq - $cmv) - $totalDespesasOperacionais, 2);
        // Resultado Financeiro Líquido em Conta = Lucro Operacional de Vendas + Variação Líquida de Saldo em Carteira
        // Matematicamente idêntico a: (Recargas + Vendas Diretas - Deduções) - CMV - Despesas Operacionais (ZERO duplicação)
        $resultadoCaixa = round($lucroVendasPuro + $diferencaRecargaConsumo, 2);
        $margemCaixaPercent = $arrecadacaoLiquida > 0 ? ($resultadoCaixa / $arrecadacaoLiquida) * 100 : 0.0;

        return [
            'competencia'              => $competencia,
            'regime'                   => $regime,
            'ano'                      => $ano,
            'mes'                      => $mes,
            'mes_nome'                 => self::MESES_PT[$mes] ?? "Mês {$mes}",
            'mes_abrev'                => self::MESES_ABREV[$mes] ?? "M{$mes}",
            'data_inicio'              => $dataInicio,
            'data_fim'                 => $dataFim,
            
            // (+) RECEITA BRUTA (Vendas & Consumo no modo Unificado/Competência)
            'receita_online'           => $receitaOnline,
            'receita_balcao'           => $receitaBalcao,
            'receita_bruta'            => $receitaBruta,
            'receita_vendas_bruta'     => $receitaVendasBruta,
            'receita_vendas_liquida'   => $receitaVendasLiq,

            // Conciliação de Carteira Pré-Paga (Recargas na Conta vs Gasto do Saldo em Vendas)
            'recargas_pix_online'      => $recargasPixOnline,
            'recargas_pix_pdv'         => $recargasPixPdv,
            'recargas_pix'             => $recargasPix,
            'recargas_dinheiro'        => $recargasDinheiro,
            'recargas_balcao'          => $recargasBalcao,
            'total_recargas'           => $totalRecargas,
            'vendas_dinheiro'          => $vendasBalcaoDinheiro,
            'vendas_pix'               => $vendasBalcaoPix,
            'vendas_diretas_balcao'    => $vendasDiretasBalcao,
            'consumo_saldo_online'     => $consumoSaldoOnline,
            'consumo_saldo_balcao'     => $consumoSaldoBalcao,
            'consumo_saldo_total'      => $consumoSaldoTotal,
            'diferenca_recarga_consumo'=> $diferencaRecargaConsumo,
            'arrecadacao_bruta'        => $arrecadacaoBruta,
            'arrecadacao_liquida'      => $arrecadacaoLiquida,
            
            // (-) DEDUÇÕES
            'estornos_balcao'          => $estornosBalcao,
            'estornos_online'          => $estornosOnline,
            'total_deducoes'           => $totalDeducoes,
            
            // (=) RECEITA LÍQUIDA
            'receita_liquida'          => $receitaLiquida,
            
            // (-) CMV
            'cmv'                      => $cmv,
            'cmv_percent'              => $receitaLiquida > 0 ? ($cmv / $receitaLiquida) * 100 : 0.0,
            
            // (=) LUCRO BRUTO
            'lucro_bruto'              => $lucroBruto,
            'margem_bruta_percent'     => $margemBrutaPercent,
            
            // (-) DESPESAS OPERACIONAIS & PAGAMENTO DE FORNECEDORES (RECIBOS)
            'despesas_fornecedores'    => $despesasFornecedores,
            'despesas_operacionais_puras' => $subtotalDespesasBloco,
            'despesas_vendas'          => $despesasVendas,
            'despesas_administrativas' => $despesasAdmin,
            'despesas_pessoal'         => $despesasPessoal,
            'despesas_manutencao'      => $despesasManutencao,
            'despesas_financeiras'     => $despesasFinanceiras,
            'despesas_outras'          => $despesasOutras,
            'total_despesas'           => $totalDespesasOperacionais,
            'despesas_percent'         => $receitaLiquida > 0 ? ($totalDespesasOperacionais / $receitaLiquida) * 100 : 0.0,
            
            // (=) RESULTADO LÍQUIDO OPERACIONAL (VENDAS) & RESULTADO FINANCEIRO EM CONTA (CAIXA REAL)
            'resultado_liquido'        => $resultadoLiquido,
            'margem_liquida_percent'   => $margemLiquidaPercent,
            'lucro_positivo'           => $resultadoLiquido >= 0,
            'resultado_caixa'          => $resultadoCaixa,
            'margem_caixa_percent'     => $margemCaixaPercent,
            'caixa_positivo'           => $resultadoCaixa >= 0,
        ];
    }

    /**
     * Calcula o DRE consolidado para um intervalo arbitrário de datas (data_inicio a data_fim).
     * Opera por padrão no Modelo Contábil Unificado (Apuração de Vendas/Consumo + Conciliação de Recargas/Custódia + Resultado em Conta).
     * Mantém suporte retrocompatível aos parâmetros 'competencia' e 'caixa'.
     */
    public function calcularPeriodo(string $dataInicio, string $dataFim, string $regime = 'unificado'): array
    {
        $pdo = self::getConnection();

        // 1. Receita Bruta de Vendas Online (Pedidos) — 100% via Saldo da Carteira
        $sqlOnline = "SELECT 
                        COALESCE(SUM(p.valor_total), 0.00) as total,
                        COUNT(p.id) as qtd_pedidos
                      FROM `pedidos` p
                      WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim
                        AND p.status NOT IN ('cancelado', 'estornado')";
        $stmtOnline = $pdo->prepare($sqlOnline);
        $stmtOnline->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $resOnline = $stmtOnline->fetch(\PDO::FETCH_ASSOC);
        $vendasOnline = (float)($resOnline['total'] ?? 0.00);

        // 2. Receita Bruta de Vendas Presenciais (Balcão)
        $sqlBalcao = "SELECT 
                        COALESCE(SUM(vb.valor_total), 0.00) as total,
                        COUNT(vb.id) as qtd_vendas
                      FROM `vendas_balcao` vb
                      WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                        AND (vb.status != 'estornada' OR vb.status IS NULL)
                        AND vb.estornado_em IS NULL";
        $stmtBalcao = $pdo->prepare($sqlBalcao);
        $stmtBalcao->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $resBalcao = $stmtBalcao->fetch(\PDO::FETCH_ASSOC);
        $vendasBalcaoTotal = (float)($resBalcao['total'] ?? 0.00);

        // Vendas Balcão em Saldo (Consumo de Carteira no PDV)
        $sqlBalcaoSaldo = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                           FROM `vendas_balcao` vb
                           WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                             AND vb.forma_pagamento = 'saldo'
                             AND (vb.status != 'estornada' OR vb.status IS NULL)
                             AND vb.estornado_em IS NULL";
        $stmtBS = $pdo->prepare($sqlBalcaoSaldo);
        $stmtBS->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $consumoSaldoBalcao = (float)$stmtBS->fetchColumn();
        $consumoSaldoOnline = $vendasOnline;
        $consumoSaldoTotal  = round($consumoSaldoOnline + $consumoSaldoBalcao, 2);

        // Vendas Diretas no Balcão (Dinheiro e Pix — Entrada Direta sem passar por Recarga)
        $sqlBalcaoDinheiro = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                              FROM `vendas_balcao` vb
                              WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                                AND vb.forma_pagamento = 'dinheiro'
                                AND (vb.status != 'estornada' OR vb.status IS NULL)
                                AND vb.estornado_em IS NULL";
        $stmtBD = $pdo->prepare($sqlBalcaoDinheiro);
        $stmtBD->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $vendasBalcaoDinheiro = (float)$stmtBD->fetchColumn();

        $sqlBalcaoPix = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                         FROM `vendas_balcao` vb
                         WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                           AND vb.forma_pagamento IN ('pix', 'pix_balcao')
                           AND (vb.status != 'estornada' OR vb.status IS NULL)
                           AND vb.estornado_em IS NULL";
        $stmtBP = $pdo->prepare($sqlBalcaoPix);
        $stmtBP->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $vendasBalcaoPix = (float)$stmtBP->fetchColumn();

        $vendasDiretasBalcao = round($vendasBalcaoDinheiro + $vendasBalcaoPix, 2);

        // Recargas da Carteira no Período (Cash-In Real Depositado na Conta/Caixa)
        $sqlRecargasPix = "SELECT COALESCE(SUM(tc.valor), 0.00)
                           FROM `transacoes_carteira` tc
                           WHERE tc.tipo_transacao = 'recarga_pix'
                             AND DATE(tc.created_at) BETWEEN :dt_inicio AND :dt_fim";
        $stmtRP = $pdo->prepare($sqlRecargasPix);
        $stmtRP->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $recargasPix = (float)$stmtRP->fetchColumn();

        // Segregação de Recargas Pix: Online (Portal da Família) vs Presencial (Balcão/PDV)
        $sqlRecargasPixPdv = "SELECT COALESCE(SUM(tc.valor), 0.00)
                              FROM `transacoes_carteira` tc
                              WHERE tc.tipo_transacao = 'recarga_pix'
                                AND (tc.operador_id IS NOT NULL OR tc.descricao LIKE '%Balcão%' OR tc.descricao LIKE '%PDV%')
                                AND DATE(tc.created_at) BETWEEN :dt_inicio AND :dt_fim";
        $stmtRPPdv = $pdo->prepare($sqlRecargasPixPdv);
        $stmtRPPdv->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $recargasPixPdv = (float)$stmtRPPdv->fetchColumn();
        $recargasPixOnline = max(0.00, round($recargasPix - $recargasPixPdv, 2));

        $sqlRecargasDinheiro = "SELECT COALESCE(SUM(tc.valor), 0.00)
                                FROM `transacoes_carteira` tc
                                WHERE tc.tipo_transacao = 'recarga_dinheiro'
                                  AND DATE(tc.created_at) BETWEEN :dt_inicio AND :dt_fim";
        $stmtRD = $pdo->prepare($sqlRecargasDinheiro);
        $stmtRD->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $recargasDinheiro = (float)$stmtRD->fetchColumn();

        $recargasBalcao = round($recargasPixPdv + $recargasDinheiro, 2);
        $totalRecargas  = round($recargasPix + $recargasDinheiro, 2);

        // Arrecadação Real na Conta (Cash-In Bruto: Recargas de Saldo + Vendas Diretas no Balcão)
        $arrecadacaoBruta = round($totalRecargas + $vendasDiretasBalcao, 2);

        // Faturamento Bruto de Vendas e Consumo (Lanches Entregues: Consumo de Saldo + Vendas Diretas)
        $receitaVendasBruta = round($vendasOnline + $vendasBalcaoTotal, 2);

        if ($regime === 'caixa') {
            $receitaOnline = $recargasPix;
            $receitaBalcao = round($recargasDinheiro + $vendasDiretasBalcao, 2);
            $receitaBruta  = $arrecadacaoBruta;
        } else {
            // No DRE Unificado (e Competência): Receita Bruta do Bloco 1 são as Vendas de Lanches
            $receitaOnline = $vendasOnline;
            $receitaBalcao = $vendasBalcaoTotal;
            $receitaBruta  = $receitaVendasBruta;
        }

        // 3. Deduções e Abatimentos (Estornos)
        $sqlEstornoBalcao = "SELECT COALESCE(SUM(vb.valor_total), 0.00)
                             FROM `vendas_balcao` vb
                             WHERE DATE(vb.estornado_em) BETWEEN :dt_inicio AND :dt_fim
                                OR (vb.status = 'estornada' AND DATE(vb.created_at) BETWEEN :dt_inicio2 AND :dt_fim2)";
        $stmtEstornoB = $pdo->prepare($sqlEstornoBalcao);
        $stmtEstornoB->execute([
            ':dt_inicio'  => $dataInicio,
            ':dt_fim'     => $dataFim,
            ':dt_inicio2' => $dataInicio,
            ':dt_fim2'    => $dataFim,
        ]);
        $estornosBalcao = (float)$stmtEstornoB->fetchColumn();

        $sqlEstornoOnline = "SELECT COALESCE(SUM(p.valor_total), 0.00)
                             FROM `pedidos` p
                             WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim
                               AND (p.status = 'estornado' OR p.estornado_em IS NOT NULL)";
        $stmtEstornoO = $pdo->prepare($sqlEstornoOnline);
        $stmtEstornoO->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $estornosOnline = (float)$stmtEstornoO->fetchColumn();

        $totalDeducoes      = round($estornosBalcao + $estornosOnline, 2);
        $receitaLiquida     = max(0.00, round($receitaBruta - $totalDeducoes, 2));
        $receitaVendasLiq   = max(0.00, round($receitaVendasBruta - $totalDeducoes, 2));
        $arrecadacaoLiquida = max(0.00, round($arrecadacaoBruta - $totalDeducoes, 2));

        // 4. Pagamento de Fornecedores (Recibos Emitidos em fornecedor_recibos) — Substitui o CMV teórico (Zero Duplicidade)
        $despesasFornecedores = $this->calcularRecibosFornecedoresPeriodo($dataInicio, $dataFim);
        $cmv = $despesasFornecedores;
        $lucroBruto = round($receitaLiquida - $cmv, 2);
        $margemBrutaPercent = $receitaLiquida > 0 ? ($lucroBruto / $receitaLiquida) * 100 : 0.0;

        // 5. Despesas Operacionais por Categoria no Período (Bloco separado de Pagamento de Fornecedores)
        $compIni = substr($dataInicio, 0, 7);
        $compFim = substr($dataFim, 0, 7);
        $sqlDespesas = "SELECT 
                            d.categoria,
                            COALESCE(SUM(d.valor), 0.00) as total
                        FROM `despesas_operacionais` d
                        WHERE (
                            (d.competencia_mes_ano BETWEEN :comp_ini AND :comp_fim)
                            OR (d.data_vencimento BETWEEN :dt_inicio AND :dt_fim)
                            OR (d.data_pagamento BETWEEN :dt_inicio2 AND :dt_fim2)
                        )
                          AND d.status != 'cancelado'
                          AND d.deleted_at IS NULL
                        GROUP BY d.categoria";
        $stmtDesp = $pdo->prepare($sqlDespesas);
        $stmtDesp->execute([
            ':comp_ini'   => $compIni,
            ':comp_fim'   => $compFim,
            ':dt_inicio'  => $dataInicio,
            ':dt_fim'     => $dataFim,
            ':dt_inicio2' => $dataInicio,
            ':dt_fim2'    => $dataFim,
        ]);
        $despPorCat = $stmtDesp->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        $despesasVendas       = (float)($despPorCat[DespesaOperacional::CATEGORIA_VENDAS] ?? 0.00);
        $despesasAdmin        = (float)($despPorCat[DespesaOperacional::CATEGORIA_ADMINISTRATIVA] ?? 0.00);
        $despesasPessoal      = (float)($despPorCat[DespesaOperacional::CATEGORIA_PESSOAL] ?? 0.00);
        $despesasManutencao   = (float)($despPorCat[DespesaOperacional::CATEGORIA_MANUTENCAO] ?? 0.00);
        $despesasFinanceiras  = (float)($despPorCat[DespesaOperacional::CATEGORIA_FINANCEIRA] ?? 0.00);
        $despesasOutras       = (float)($despPorCat[DespesaOperacional::CATEGORIA_OUTRAS] ?? 0.00);

        $subtotalDespesasBloco = round(
            $despesasVendas + $despesasAdmin + $despesasPessoal + 
            $despesasManutencao + $despesasFinanceiras + $despesasOutras,
            2
        );

        // Total do bloco de despesas operacionais (sem duplicar fornecedores, que já foi abatido no Lucro Bruto)
        $totalDespesasOperacionais = $subtotalDespesasBloco;

        $resultadoLiquido = round($lucroBruto - $totalDespesasOperacionais, 2);
        $margemLiquidaPercent = $receitaLiquida > 0 ? ($resultadoLiquido / $receitaLiquida) * 100 : 0.0;

        // 6. Ponte de Conciliação da Carteira Pré-Paga e Resultado Financeiro em Conta (Caixa Real)
        $diferencaRecargaConsumo = round($totalRecargas - $consumoSaldoTotal, 2);
        $lucroVendasPuro = round(($receitaVendasLiq - $cmv) - $totalDespesasOperacionais, 2);
        $resultadoCaixa = round($lucroVendasPuro + $diferencaRecargaConsumo, 2);
        $margemCaixaPercent = $arrecadacaoLiquida > 0 ? ($resultadoCaixa / $arrecadacaoLiquida) * 100 : 0.0;

        // Identifica os meses participantes do período
        $mesesParticipantes = [];
        $inicioDt = new \DateTime($dataInicio);
        $fimDt    = new \DateTime($dataFim);
        $cursorDt = new \DateTime($inicioDt->format('Y-m-01'));
        $limiteDt = new \DateTime($fimDt->format('Y-m-01'));

        while ($cursorDt <= $limiteDt) {
            $comp = $cursorDt->format('Y-m');
            $mesNum = (int)$cursorDt->format('n');
            $anoNum = (int)$cursorDt->format('Y');
            $mesesParticipantes[$comp] = [
                'competencia' => $comp,
                'ano'         => $anoNum,
                'mes'         => $mesNum,
                'mes_abrev'   => self::MESES_ABREV[$mesNum] ?? "M{$mesNum}",
                'mes_nome'    => self::MESES_PT[$mesNum] ?? "Mês {$mesNum}",
                'dados'       => $this->calcularCompetencia($comp, $regime),
            ];
            $cursorDt->modify('+1 month');
        }

        return [
            'regime'                   => $regime,
            'data_inicio'              => $dataInicio,
            'data_fim'                 => $dataFim,
            'data_inicio_formatada'    => date('d/m/Y', strtotime($dataInicio)),
            'data_fim_formatada'       => date('d/m/Y', strtotime($dataFim)),
            'meses_participantes'      => $mesesParticipantes,
            'qtd_meses'                => count($mesesParticipantes),

            // (+) RECEITA BRUTA
            'receita_online'           => $receitaOnline,
            'receita_balcao'           => $receitaBalcao,
            'receita_bruta'            => $receitaBruta,
            'receita_vendas_bruta'     => $receitaVendasBruta,
            'receita_vendas_liquida'   => $receitaVendasLiq,

            // Conciliação de Carteira Pré-Paga (Recargas na Conta vs Gasto do Saldo em Vendas)
            'recargas_pix_online'      => $recargasPixOnline,
            'recargas_pix_pdv'         => $recargasPixPdv,
            'recargas_pix'             => $recargasPix,
            'recargas_dinheiro'        => $recargasDinheiro,
            'recargas_balcao'          => $recargasBalcao,
            'total_recargas'           => $totalRecargas,
            'vendas_dinheiro'          => $vendasBalcaoDinheiro,
            'vendas_pix'               => $vendasBalcaoPix,
            'vendas_diretas_balcao'    => $vendasDiretasBalcao,
            'consumo_saldo_online'     => $consumoSaldoOnline,
            'consumo_saldo_balcao'     => $consumoSaldoBalcao,
            'consumo_saldo_total'      => $consumoSaldoTotal,
            'diferenca_recarga_consumo'=> $diferencaRecargaConsumo,
            'arrecadacao_bruta'        => $arrecadacaoBruta,
            'arrecadacao_liquida'      => $arrecadacaoLiquida,

            // (-) DEDUÇÕES
            'estornos_balcao'          => $estornosBalcao,
            'estornos_online'          => $estornosOnline,
            'total_deducoes'           => $totalDeducoes,

            // (=) RECEITA LÍQUIDA
            'receita_liquida'          => $receitaLiquida,

            // (-) CMV
            'cmv'                      => $cmv,
            'cmv_percent'              => $receitaLiquida > 0 ? ($cmv / $receitaLiquida) * 100 : 0.0,

            // (=) LUCRO BRUTO
            'lucro_bruto'              => $lucroBruto,
            'margem_bruta_percent'     => $margemBrutaPercent,

            // (-) DESPESAS OPERACIONAIS & PAGAMENTO DE FORNECEDORES (RECIBOS)
            'despesas_fornecedores'    => $despesasFornecedores,
            'despesas_operacionais_puras' => $subtotalDespesasBloco,
            'despesas_vendas'          => $despesasVendas,
            'despesas_administrativas' => $despesasAdmin,
            'despesas_pessoal'         => $despesasPessoal,
            'despesas_manutencao'      => $despesasManutencao,
            'despesas_financeiras'     => $despesasFinanceiras,
            'despesas_outras'          => $despesasOutras,
            'total_despesas'           => $totalDespesasOperacionais,
            'despesas_percent'         => $receitaLiquida > 0 ? ($totalDespesasOperacionais / $receitaLiquida) * 100 : 0.0,

            // (=) RESULTADO LÍQUIDO OPERACIONAL (VENDAS) & RESULTADO FINANCEIRO EM CONTA (CAIXA REAL)
            'resultado_liquido'        => $resultadoLiquido,
            'margem_liquida_percent'   => $margemLiquidaPercent,
            'lucro_positivo'           => $resultadoLiquido >= 0,
            'resultado_caixa'          => $resultadoCaixa,
            'margem_caixa_percent'     => $margemCaixaPercent,
            'caixa_positivo'           => $resultadoCaixa >= 0,
        ];
    }

    /**
     * Calcula o DRE anual comparativo mês a mês (Janeiro a Dezembro + Total Consolidado do Ano).
     * Opera por padrão no Modelo Contábil Unificado.
     */
    public function calcularAnoComparativo(int $ano, string $regime = 'unificado'): array
    {
        $meses = [];
        $totais = [
            'receita_online'           => 0.00,
            'receita_balcao'           => 0.00,
            'receita_bruta'            => 0.00,
            'receita_vendas_bruta'     => 0.00,
            'receita_vendas_liquida'   => 0.00,
            'recargas_pix_online'      => 0.00,
            'recargas_pix_pdv'         => 0.00,
            'recargas_pix'             => 0.00,
            'recargas_dinheiro'        => 0.00,
            'recargas_balcao'          => 0.00,
            'total_recargas'           => 0.00,
            'vendas_dinheiro'          => 0.00,
            'vendas_pix'               => 0.00,
            'vendas_diretas_balcao'    => 0.00,
            'consumo_saldo_online'     => 0.00,
            'consumo_saldo_balcao'     => 0.00,
            'consumo_saldo_total'      => 0.00,
            'diferenca_recarga_consumo'=> 0.00,
            'arrecadacao_bruta'        => 0.00,
            'arrecadacao_liquida'      => 0.00,
            'estornos_balcao'          => 0.00,
            'estornos_online'          => 0.00,
            'total_deducoes'           => 0.00,
            'receita_liquida'          => 0.00,
            'cmv'                      => 0.00,
            'lucro_bruto'              => 0.00,
            'despesas_fornecedores'    => 0.00,
            'despesas_operacionais_puras' => 0.00,
            'despesas_vendas'          => 0.00,
            'despesas_administrativas' => 0.00,
            'despesas_pessoal'         => 0.00,
            'despesas_manutencao'      => 0.00,
            'despesas_financeiras'     => 0.00,
            'despesas_outras'          => 0.00,
            'total_despesas'           => 0.00,
            'resultado_liquido'        => 0.00,
            'resultado_caixa'          => 0.00,
        ];

        for ($m = 1; $m <= 12; $m++) {
            $competencia = sprintf('%04d-%02d', $ano, $m);
            $dadosMes = $this->calcularCompetencia($competencia, $regime);
            $meses[$m] = $dadosMes;

            // Acumula totais do ano
            foreach ($totais as $k => $v) {
                if (isset($dadosMes[$k])) {
                    $totais[$k] = round($totais[$k] + $dadosMes[$k], 2);
                }
            }
        }

        // Percentuais anuais consolidados
        $totais['margem_bruta_percent'] = $totais['receita_liquida'] > 0 
            ? ($totais['lucro_bruto'] / $totais['receita_liquida']) * 100 
            : 0.0;
        $totais['margem_liquida_percent'] = $totais['receita_liquida'] > 0 
            ? ($totais['resultado_liquido'] / $totais['receita_liquida']) * 100 
            : 0.0;
        $totais['margem_caixa_percent'] = $totais['arrecadacao_liquida'] > 0
            ? ($totais['resultado_caixa'] / $totais['arrecadacao_liquida']) * 100
            : 0.0;
        $totais['cmv_percent'] = $totais['receita_liquida'] > 0 
            ? ($totais['cmv'] / $totais['receita_liquida']) * 100 
            : 0.0;
        $totais['despesas_percent'] = $totais['receita_liquida'] > 0 
            ? ($totais['total_despesas'] / $totais['receita_liquida']) * 100 
            : 0.0;
        $totais['lucro_positivo'] = $totais['resultado_liquido'] >= 0;
        $totais['caixa_positivo'] = $totais['resultado_caixa'] >= 0;

        return [
            'ano'    => $ano,
            'regime' => $regime,
            'meses'  => $meses,
            'totais' => $totais,
        ];
    }

    /**
     * Apura o valor total de recibos de pagamento a fornecedores (tabela fornecedor_recibos)
     * emitidos e ativos no período especificado, mantendo essa despesa separada do bloco geral.
     */
    private function calcularRecibosFornecedoresPeriodo(string $dataInicio, string $dataFim): float
    {
        try {
            $pdo = self::getConnection();
            $sql = "SELECT COALESCE(SUM(r.valor), 0.00)
                    FROM `fornecedor_recibos` r
                    WHERE r.deleted_at IS NULL
                      AND r.data_pagamento BETWEEN :dt_inicio AND :dt_fim";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':dt_inicio' => $dataInicio,
                ':dt_fim'    => $dataFim,
            ]);
            return round((float)$stmt->fetchColumn(), 2);
        } catch (\Throwable $e) {
            return 0.00;
        }
    }

    /**
     * Calcula o CMV do período.
     * 1. Tenta apurar a partir dos itens vendidos multiplicados pelo preco_custo dos produtos.
     * 2. Se o total apurado de preco_custo for 0 ou insignificante, verifica as entradas de NF-e no período.
     * 3. Como fallback para cantinas escolares, se não houver custos registrados nas mercadorias,
     *    adota margem padrão de custo de 45% sobre a receita líquida (ou 0 se não houver vendas).
     */
    private function calcularCmvPeriodo(string $dataInicio, string $dataFim, float $receitaLiquida): float
    {
        $pdo = self::getConnection();

        // 1. CMV dos Pedidos Online
        $sqlCustoOnline = "SELECT 
                            COALESCE(SUM(ip.quantidade * COALESCE(pr.preco_custo, 0.00)), 0.00) as custo_produtos
                           FROM `itens_pedido` ip
                           INNER JOIN `pedidos` p ON ip.pedido_id = p.id
                           INNER JOIN `produtos` pr ON ip.produto_id = pr.id
                           WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim
                             AND p.status NOT IN ('cancelado', 'estornado')";
        $stmtCO = $pdo->prepare($sqlCustoOnline);
        $stmtCO->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $custoOnline = (float)$stmtCO->fetchColumn();

        // 2. CMV das Vendas Balcão
        $sqlCustoBalcao = "SELECT 
                            COALESCE(SUM(ivb.quantidade * COALESCE(pr.preco_custo, 0.00)), 0.00) as custo_produtos
                           FROM `itens_venda_balcao` ivb
                           INNER JOIN `vendas_balcao` vb ON ivb.venda_balcao_id = vb.id
                           INNER JOIN `produtos` pr ON ivb.produto_id = pr.id
                           WHERE DATE(vb.created_at) BETWEEN :dt_inicio AND :dt_fim
                             AND (vb.status != 'estornada' OR vb.status IS NULL)
                             AND vb.estornado_em IS NULL";
        $stmtCB = $pdo->prepare($sqlCustoBalcao);
        $stmtCB->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $custoBalcao = (float)$stmtCB->fetchColumn();

        $custoApurado = $custoOnline + $custoBalcao;

        // Se o custo direto apurado nos produtos for maior que zero, utiliza o custo real
        if ($custoApurado > 0.00) {
            return $custoApurado;
        }

        // 3. Fallback: Se não houver preco_custo cadastrado, busca pelas entradas de fornecedor no período
        $sqlEntradas = "SELECT COALESCE(SUM(quantidade * 3.50), 0.00) 
                        FROM `estoque_movimentacoes`
                        WHERE DATE(created_at) BETWEEN :dt_inicio AND :dt_fim
                          AND tipo_movimentacao IN ('entrada_fornecedor', 'entrada_xml')";
        $stmtEnt = $pdo->prepare($sqlEntradas);
        $stmtEnt->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $custoEntradas = (float)$stmtEnt->fetchColumn();

        if ($custoEntradas > 0.00) {
            return $custoEntradas;
        }

        // 4. Se a receita líquida for positiva e ainda não houver nenhum custo registrado,
        // aplica margem padrão de custo de insumo da cantina de 45% da receita líquida
        if ($receitaLiquida > 0.00) {
            return round($receitaLiquida * 0.45, 2);
        }

        return 0.00;
    }

    /**
     * Retorna os dados analíticos formatados para os gráficos interativos da tela de DRE.
     * Suporta agregação por ano ou por período personalizado (data_inicio a data_fim) no modelo unificado.
     */
    public function obterDadosGraficos(int $ano, ?string $dataInicio = null, ?string $dataFim = null, string $regime = 'unificado'): array
    {
        $dreAno = $this->calcularAnoComparativo($ano, $regime);
        
        $labels = [];
        $receitas = [];
        $arrecadacoes = [];
        $despesas = [];
        $lucros = [];
        $resultadosCaixa = [];
        $recargas = [];
        $consumoSaldo = [];
        $vendasDiretas = [];
        $online = [];
        $balcao = [];

        foreach ($dreAno['meses'] as $m => $d) {
            $labels[]          = $d['mes_abrev'];
            $receitas[]        = round($d['receita_liquida'], 2);
            $arrecadacoes[]    = round($d['arrecadacao_liquida'] ?? 0.00, 2);
            $despesas[]        = round($d['total_despesas'] + $d['cmv'], 2); // Custo total (CMV + Despesas)
            $lucros[]          = round($d['resultado_liquido'], 2);
            $resultadosCaixa[] = round($d['resultado_caixa'] ?? 0.00, 2);
            $recargas[]        = round($d['total_recargas'] ?? 0.00, 2);
            $consumoSaldo[]    = round($d['consumo_saldo_total'] ?? 0.00, 2);
            $vendasDiretas[]   = round($d['vendas_diretas_balcao'] ?? 0.00, 2);
            $online[]          = round($d['receita_online'], 2);
            $balcao[]          = round($d['receita_balcao'], 2);
        }

        // Se datas foram fornecidas, apura os totais específicos do período selecionado
        if ($dataInicio && $dataFim) {
            $drePeriodo = $this->calcularPeriodo($dataInicio, $dataFim, $regime);
            $totais = $drePeriodo;
        } else {
            $totais = $dreAno['totais'];
        }

        // Totais por categoria de despesas no período selecionado
        $totaisDespesas = [
            'Pagamento de Fornecedores' => $totais['despesas_fornecedores'] ?? 0.00,
            'Administrativas' => $totais['despesas_administrativas'] ?? 0.00,
            'Pessoal & Salários' => $totais['despesas_pessoal'] ?? 0.00,
            'Vendas & Embalagens' => $totais['despesas_vendas'] ?? 0.00,
            'Manutenção' => $totais['despesas_manutencao'] ?? 0.00,
            'Financeiras' => $totais['despesas_financeiras'] ?? 0.00,
            'Outras Despesas' => $totais['despesas_outras'] ?? 0.00,
        ];

        // Ranking dos 8 produtos mais rentáveis / vendidos no período
        $pdo = self::getConnection();
        if ($dataInicio && $dataFim) {
            $sqlRanking = "SELECT 
                            pr.id,
                            pr.codigo_interno,
                            pr.descricao,
                            pr.preco_venda,
                            COALESCE(pr.preco_custo, pr.preco_venda * 0.45) as preco_custo,
                            SUM(t.qtd) as total_qtd,
                            SUM(t.subtotal) as total_receita
                           FROM (
                               SELECT ip.produto_id, ip.quantidade as qtd, ip.subtotal
                               FROM itens_pedido ip
                               INNER JOIN pedidos p ON ip.pedido_id = p.id
                               WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim AND p.status NOT IN ('cancelado', 'estornado')
                               UNION ALL
                               SELECT ivb.produto_id, ivb.quantidade as qtd, ivb.subtotal
                               FROM itens_venda_balcao ivb
                               INNER JOIN vendas_balcao vb ON ivb.venda_balcao_id = vb.id
                               WHERE DATE(vb.created_at) BETWEEN :dt_inicio2 AND :dt_fim2 AND (vb.status != 'estornada' OR vb.status IS NULL)
                           ) t
                           INNER JOIN produtos pr ON t.produto_id = pr.id
                           GROUP BY pr.id, pr.codigo_interno, pr.descricao, pr.preco_venda, pr.preco_custo
                           ORDER BY total_receita DESC
                           LIMIT 8";
            $stmtR = $pdo->prepare($sqlRanking);
            $stmtR->execute([
                ':dt_inicio'  => $dataInicio,
                ':dt_fim'     => $dataFim,
                ':dt_inicio2' => $dataInicio,
                ':dt_fim2'    => $dataFim,
            ]);
        } else {
            $sqlRanking = "SELECT 
                            pr.id,
                            pr.codigo_interno,
                            pr.descricao,
                            pr.preco_venda,
                            COALESCE(pr.preco_custo, pr.preco_venda * 0.45) as preco_custo,
                            SUM(t.qtd) as total_qtd,
                            SUM(t.subtotal) as total_receita
                           FROM (
                               SELECT ip.produto_id, ip.quantidade as qtd, ip.subtotal
                               FROM itens_pedido ip
                               INNER JOIN pedidos p ON ip.pedido_id = p.id
                               WHERE YEAR(p.data_consumo) = :ano AND p.status NOT IN ('cancelado', 'estornado')
                               UNION ALL
                               SELECT ivb.produto_id, ivb.quantidade as qtd, ivb.subtotal
                               FROM itens_venda_balcao ivb
                               INNER JOIN vendas_balcao vb ON ivb.venda_balcao_id = vb.id
                               WHERE YEAR(vb.created_at) = :ano2 AND (vb.status != 'estornada' OR vb.status IS NULL)
                           ) t
                           INNER JOIN produtos pr ON t.produto_id = pr.id
                           GROUP BY pr.id, pr.codigo_interno, pr.descricao, pr.preco_venda, pr.preco_custo
                           ORDER BY total_receita DESC
                           LIMIT 8";
            $stmtR = $pdo->prepare($sqlRanking);
            $stmtR->execute([':ano' => $ano, ':ano2' => $ano]);
        }
        $ranking = $stmtR->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $rankingFormatado = [];
        foreach ($ranking as $p) {
            $rec = (float)$p['total_receita'];
            $custo = (float)$p['preco_custo'] * (int)$p['total_qtd'];
            $lucro = $rec - $custo;
            $rankingFormatado[] = [
                'descricao'     => $p['descricao'],
                'quantidade'    => (int)$p['total_qtd'],
                'receita'       => round($rec, 2),
                'custo'         => round($custo, 2),
                'lucro_estimado'=> round($lucro, 2),
            ];
        }

        return [
            'ano'               => $ano,
            'labels'            => $labels,
            'receitas'          => $receitas,
            'arrecadacoes'      => $arrecadacoes,
            'despesas'          => $despesas,
            'lucros'            => $lucros,
            'resultados_caixa'  => $resultadosCaixa,
            'recargas'          => $recargas,
            'consumo_saldo'     => $consumoSaldo,
            'vendas_diretas'    => $vendasDiretas,
            'online'            => $online,
            'balcao'            => $balcao,
            'despesas_por_cat'  => $totaisDespesas,
            'produtos_rentaveis'=> $rankingFormatado,
            'totais'            => $dreAno['totais'],
        ];
    }
}
