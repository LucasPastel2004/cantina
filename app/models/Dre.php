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
     */
    public function calcularCompetencia(string $competencia): array
    {
        $pdo = self::getConnection();

        // 1. Data inicial e final do mês
        $partes = explode('-', $competencia);
        $ano = (int)($partes[0] ?? date('Y'));
        $mes = (int)($partes[1] ?? date('m'));
        $dataInicio = sprintf('%04d-%02d-01', $ano, $mes);
        $dataFim    = date('Y-m-t', strtotime($dataInicio));

        // 2. Receita Bruta de Vendas Online (Pedidos)
        $sqlOnline = "SELECT 
                        COALESCE(SUM(p.valor_total), 0.00) as total,
                        COUNT(p.id) as qtd_pedidos
                      FROM `pedidos` p
                      WHERE p.data_consumo BETWEEN :dt_inicio AND :dt_fim
                        AND p.status NOT IN ('cancelado', 'estornado')";
        $stmtOnline = $pdo->prepare($sqlOnline);
        $stmtOnline->execute([':dt_inicio' => $dataInicio, ':dt_fim' => $dataFim]);
        $resOnline = $stmtOnline->fetch(\PDO::FETCH_ASSOC);
        $receitaOnline = (float)($resOnline['total'] ?? 0.00);

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
        $receitaBalcao = (float)($resBalcao['total'] ?? 0.00);

        $receitaBruta = $receitaOnline + $receitaBalcao;

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

        $totalDeducoes = $estornosBalcao + $estornosOnline;

        // 5. Receita Líquida de Vendas
        $receitaLiquida = max(0.00, $receitaBruta - $totalDeducoes);

        // 6. Custo das Mercadorias Vendidas (CMV)
        $cmv = $this->calcularCmvPeriodo($dataInicio, $dataFim, $receitaLiquida);

        // 7. Lucro Bruto
        $lucroBruto = $receitaLiquida - $cmv;
        $margemBrutaPercent = $receitaLiquida > 0 ? ($lucroBruto / $receitaLiquida) * 100 : 0.0;

        // 8. Despesas Operacionais por Categoria
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

        $despesasVendas      = (float)($despPorCat[DespesaOperacional::CATEGORIA_VENDAS] ?? 0.00);
        $despesasAdmin       = (float)($despPorCat[DespesaOperacional::CATEGORIA_ADMINISTRATIVA] ?? 0.00);
        $despesasPessoal     = (float)($despPorCat[DespesaOperacional::CATEGORIA_PESSOAL] ?? 0.00);
        $despesasManutencao  = (float)($despPorCat[DespesaOperacional::CATEGORIA_MANUTENCAO] ?? 0.00);
        $despesasFinanceiras = (float)($despPorCat[DespesaOperacional::CATEGORIA_FINANCEIRA] ?? 0.00);
        $despesasOutras      = (float)($despPorCat[DespesaOperacional::CATEGORIA_OUTRAS] ?? 0.00);

        $totalDespesasOperacionais = $despesasVendas + $despesasAdmin + $despesasPessoal + 
                                     $despesasManutencao + $despesasFinanceiras + $despesasOutras;

        // 9. Resultado Líquido do Exercício (Lucro Líquido Real)
        $resultadoLiquido = $lucroBruto - $totalDespesasOperacionais;
        $margemLiquidaPercent = $receitaLiquida > 0 ? ($resultadoLiquido / $receitaLiquida) * 100 : 0.0;

        return [
            'competencia'              => $competencia,
            'ano'                      => $ano,
            'mes'                      => $mes,
            'mes_nome'                 => self::MESES_PT[$mes] ?? "Mês {$mes}",
            'mes_abrev'                => self::MESES_ABREV[$mes] ?? "M{$mes}",
            'data_inicio'              => $dataInicio,
            'data_fim'                 => $dataFim,
            
            // (+) RECEITA BRUTA
            'receita_online'           => $receitaOnline,
            'receita_balcao'           => $receitaBalcao,
            'receita_bruta'            => $receitaBruta,
            
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
            
            // (-) DESPESAS OPERACIONAIS
            'despesas_vendas'          => $despesasVendas,
            'despesas_administrativas' => $despesasAdmin,
            'despesas_pessoal'         => $despesasPessoal,
            'despesas_manutencao'      => $despesasManutencao,
            'despesas_financeiras'     => $despesasFinanceiras,
            'despesas_outras'          => $despesasOutras,
            'total_despesas'           => $totalDespesasOperacionais,
            'despesas_percent'         => $receitaLiquida > 0 ? ($totalDespesasOperacionais / $receitaLiquida) * 100 : 0.0,
            
            // (=) RESULTADO LÍQUIDO
            'resultado_liquido'        => $resultadoLiquido,
            'margem_liquida_percent'   => $margemLiquidaPercent,
            'lucro_positivo'           => $resultadoLiquido >= 0,
        ];
    }

    /**
     * Calcula o DRE anual comparativo mês a mês (Janeiro a Dezembro + Total Consolidado do Ano).
     */
    public function calcularAnoComparativo(int $ano): array
    {
        $meses = [];
        $totais = [
            'receita_online'           => 0.00,
            'receita_balcao'           => 0.00,
            'receita_bruta'            => 0.00,
            'estornos_balcao'          => 0.00,
            'estornos_online'          => 0.00,
            'total_deducoes'           => 0.00,
            'receita_liquida'          => 0.00,
            'cmv'                      => 0.00,
            'lucro_bruto'              => 0.00,
            'despesas_vendas'          => 0.00,
            'despesas_administrativas' => 0.00,
            'despesas_pessoal'         => 0.00,
            'despesas_manutencao'      => 0.00,
            'despesas_financeiras'     => 0.00,
            'despesas_outras'          => 0.00,
            'total_despesas'           => 0.00,
            'resultado_liquido'        => 0.00,
        ];

        for ($m = 1; $m <= 12; $m++) {
            $competencia = sprintf('%04d-%02d', $ano, $m);
            $dadosMes = $this->calcularCompetencia($competencia);
            $meses[$m] = $dadosMes;

            // Acumula totais do ano
            foreach ($totais as $k => $v) {
                if (isset($dadosMes[$k])) {
                    $totais[$k] += $dadosMes[$k];
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
        $totais['cmv_percent'] = $totais['receita_liquida'] > 0 
            ? ($totais['cmv'] / $totais['receita_liquida']) * 100 
            : 0.0;
        $totais['despesas_percent'] = $totais['receita_liquida'] > 0 
            ? ($totais['total_despesas'] / $totais['receita_liquida']) * 100 
            : 0.0;
        $totais['lucro_positivo'] = $totais['resultado_liquido'] >= 0;

        return [
            'ano'    => $ano,
            'meses'  => $meses,
            'totais' => $totais,
        ];
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
     */
    public function obterDadosGraficos(int $ano): array
    {
        $dreAno = $this->calcularAnoComparativo($ano);
        
        $labels = [];
        $receitas = [];
        $despesas = [];
        $lucros = [];
        $online = [];
        $balcao = [];

        foreach ($dreAno['meses'] as $m => $d) {
            $labels[]   = $d['mes_abrev'];
            $receitas[] = round($d['receita_liquida'], 2);
            $despesas[] = round($d['total_despesas'] + $d['cmv'], 2); // Custo total (CMV + Despesas)
            $lucros[]   = round($d['resultado_liquido'], 2);
            $online[]   = round($d['receita_online'], 2);
            $balcao[]   = round($d['receita_balcao'], 2);
        }

        // Totais por categoria de despesas no ano
        $totaisDespesas = [
            'Administrativas' => $dreAno['totais']['despesas_administrativas'],
            'Pessoal & Salários' => $dreAno['totais']['despesas_pessoal'],
            'Vendas & Embalagens' => $dreAno['totais']['despesas_vendas'],
            'Manutenção' => $dreAno['totais']['despesas_manutencao'],
            'Financeiras' => $dreAno['totais']['despesas_financeiras'],
            'Outras Despesas' => $dreAno['totais']['despesas_outras'],
        ];

        // Ranking dos 8 produtos mais rentáveis / vendidos no ano
        $pdo = self::getConnection();
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
            'despesas'          => $despesas,
            'lucros'            => $lucros,
            'online'            => $online,
            'balcao'            => $balcao,
            'despesas_por_cat'  => $totaisDespesas,
            'produtos_rentaveis'=> $rankingFormatado,
            'totais'            => $dreAno['totais'],
        ];
    }
}
