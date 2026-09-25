<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador: RelatoriosController (Central de Relatórios Gerenciais e Exportações)
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Telas 8.1 a 8.5) e docs/DESIGN.md
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Aluno;
use App\Models\Colaborador;
use App\Models\Configuracao;
use App\Models\Categoria;
use App\Models\Responsavel;
use App\Models\SessaoCaixa;
use App\Models\TransacaoCarteira;
use App\Services\PdfService;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class RelatoriosController extends Controller
{
    /**
     * Central / Hub de Relatórios Gerenciais (FSD Tela 8.1)
     */
    public function index(): void
    {
        $mesAtual = date('m');
        $anoAtual = date('Y');

        // Métricas consolidadas do mês corrente para a Stat-Strip do Hub
        $pdo = \App\Core\Model::getPdo();

        // 1. Faturamento Total do Mês (Pedidos agendados/entregues + Vendas balcão realizadas)
        $sqlFat = "
            SELECT 
                (
                    SELECT COALESCE(SUM(valor_total), 0)
                    FROM pedidos
                    WHERE status IN ('agendado', 'editado', 'entregue')
                      AND MONTH(data_consumo) = :mes1 AND YEAR(data_consumo) = :ano1
                ) + (
                    SELECT COALESCE(SUM(valor_total), 0)
                    FROM vendas_balcao
                    WHERE MONTH(created_at) = :mes2 AND YEAR(created_at) = :ano2
                ) AS faturamento_total
        ";
        $stmt = $pdo->prepare($sqlFat);
        $stmt->execute([
            'mes1' => $mesAtual, 'ano1' => $anoAtual,
            'mes2' => $mesAtual, 'ano2' => $anoAtual
        ]);
        $fatRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        $faturamentoMes = (float)($fatRow['faturamento_total'] ?? 0.0);

        // 2. Total de Peças/Lanches Comercializados no Mês
        $sqlLanches = "
            SELECT 
                (
                    SELECT COALESCE(SUM(ip.quantidade), 0)
                    FROM itens_pedido ip
                    INNER JOIN pedidos p ON p.id = ip.pedido_id
                    WHERE p.status IN ('agendado', 'editado', 'entregue')
                      AND MONTH(p.data_consumo) = :mes1 AND YEAR(p.data_consumo) = :ano1
                ) + (
                    SELECT COALESCE(SUM(ivb.quantidade), 0)
                    FROM itens_venda_balcao ivb
                    INNER JOIN vendas_balcao vb ON vb.id = ivb.venda_balcao_id
                    WHERE MONTH(vb.created_at) = :mes2 AND YEAR(vb.created_at) = :ano2
                ) AS lanches_total
        ";
        $stmtLanches = $pdo->prepare($sqlLanches);
        $stmtLanches->execute([
            'mes1' => $mesAtual, 'ano1' => $anoAtual,
            'mes2' => $mesAtual, 'ano2' => $anoAtual
        ]);
        $lanchesRow = $stmtLanches->fetch(\PDO::FETCH_ASSOC);
        $lanchesMes = (int)($lanchesRow['lanches_total'] ?? 0);

        // 3. Montante de Desconto em Folha para RH no Mês
        $sqlFolha = "
            SELECT COALESCE(SUM(valor_total), 0) AS total_folha
            FROM vendas_balcao
            WHERE tipo_venda = 'desconto_folha'
              AND MONTH(created_at) = :mes AND YEAR(created_at) = :ano
        ";
        $stmtFolha = $pdo->prepare($sqlFolha);
        $stmtFolha->execute(['mes' => $mesAtual, 'ano' => $anoAtual]);
        $folhaRow = $stmtFolha->fetch(\PDO::FETCH_ASSOC);
        $descontoFolhaMes = (float)($folhaRow['total_folha'] ?? 0.0);

        // 4. Famílias com Saldo Devedor ou Bloqueadas por MED
        $totalDevedores = Responsavel::contarNegativadosMed();
        $saldoTotalDevedor = Responsavel::somarSaldoDevedorMed();

        $this->render('relatorios/index', [
            'pageTitle'         => 'Central de Relatórios Gerenciais',
            'activeMenu'        => 'relatorios',
            'user'              => Auth::user(),
            'faturamentoMes'    => $faturamentoMes,
            'lanchesMes'        => $lanchesMes,
            'descontoFolhaMes'  => $descontoFolhaMes,
            'totalDevedores'    => $totalDevedores,
            'saldoTotalDevedor' => $saldoTotalDevedor,
            'mesReferencia'     => date('m/Y')
        ], 'admin');
    }

    /**
     * Extrato Analítico de Consumo e Recargas do Aluno (FSD Tela 8.5 & Seção 22.1)
     */
    public function extratoAluno(): void
    {
        $alunoId = (int)$this->input('aluno_id', 0);
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));

        $dadosExtrato = null;
        if ($alunoId > 0) {
            $dadosExtrato = $this->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim);
        }

        $this->render('relatorios/extrato_aluno', [
            'pageTitle'    => 'Extrato Analítico do Aluno',
            'activeMenu'   => 'extrato_aluno',
            'user'         => Auth::user(),
            'alunoId'      => $alunoId,
            'dataInicio'   => $dataInicio,
            'dataFim'      => $dataFim,
            'dadosExtrato' => $dadosExtrato
        ], 'admin');
    }

    /**
     * Download do PDF A4 Timbrado do Extrato do Aluno
     */
    public function extratoAlunoPdf(): void
    {
        $alunoId = (int)$this->input('aluno_id', 0);
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));

        if ($alunoId <= 0) {
            Session::setFlash('danger', 'Selecione um aluno para gerar o extrato em PDF.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $dadosExtrato = $this->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim);
        if (!$dadosExtrato) {
            Session::setFlash('danger', 'Aluno não localizado.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $usuario = Auth::user();
        $dadosExtrato['operador'] = $usuario['nome'] ?? 'Atendimento Cantina';

        $pdfBinario = PdfService::gerarExtratoConsumoAlunoPdf($dadosExtrato);
        $matricula = preg_replace('/[^A-Za-z0-9]/', '', $dadosExtrato['aluno']['matricula'] ?? 'aluno');
        $nomeArquivo = 'extrato_aluno_' . $matricula . '_' . date('Ymd_His') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($pdfBinario));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBinario;
        exit;
    }

    /**
     * Espelho HTML de Impressão Direta do Extrato do Aluno
     */
    public function extratoAlunoImprimir(): void
    {
        $alunoId = (int)$this->input('aluno_id', 0);
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));

        if ($alunoId <= 0) {
            Session::setFlash('danger', 'Selecione um aluno para imprimir o extrato.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $dadosExtrato = $this->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim);
        if (!$dadosExtrato) {
            Session::setFlash('danger', 'Aluno não localizado.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $usuario = Auth::user();
        $dadosExtrato['operador'] = $usuario['nome'] ?? 'Atendimento Cantina';

        echo PdfService::gerarEspelhoExtratoConsumoAlunoHtml($dadosExtrato);
        exit;
    }

    /**
     * Endpoint AJAX para busca de alunos com autocomplete
     */
    public function buscarAlunosAjax(): void
    {
        $termo = trim((string)$this->input('q', (string)$this->input('busca', '')));
        $limite = max(1, min(25, (int)$this->input('limite', 12)));

        if (empty($termo)) {
            $this->json(['sucesso' => true, 'alunos' => []]);
            return;
        }

        $alunos = Aluno::listarComTurma(['busca' => $termo, 'status' => 'ativos'], $limite);

        $resultado = array_map(function($a) {
            return [
                'id'         => (int)$a['id'],
                'nome'       => $a['nome'],
                'matricula'  => $a['matricula'],
                'turma'      => ($a['serie'] ?? '') . ' ' . ($a['nome_turma'] ?? ''),
                'turno'      => ucfirst($a['turno'] ?? 'matutino'),
                'saldo'      => (float)($a['saldo_atual'] ?? 0.0),
                'saldo_fmt'  => 'R$ ' . number_format((float)($a['saldo_atual'] ?? 0.0), 2, ',', '.'),
                'foto_url'   => $a['foto_url'] ?? null
            ];
        }, $alunos);

        $this->json(['sucesso' => true, 'alunos' => $resultado]);
    }

    /**
     * Relatório de Vendas por Alimento e Curva ABC (FSD Tela 8.2 & Seção 22.2)
     */
    public function vendasAlimento(): void
    {
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));
        $turno = trim((string)$this->input('turno', ''));
        $categoriaId = (int)$this->input('categoria_id', 0);
        $tipo = trim((string)$this->input('tipo', ''));

        $dadosRelatorio = $this->obterDadosVendasAlimento($dataInicio, $dataFim, $turno, $categoriaId, $tipo);
        $categorias = Categoria::listarTodas(true);

        $this->render('relatorios/vendas_alimento', [
            'pageTitle'      => 'Vendas por Alimento & Curva ABC',
            'activeMenu'     => 'relatorios',
            'user'           => Auth::user(),
            'dataInicio'     => $dataInicio,
            'dataFim'        => $dataFim,
            'turno'          => $turno,
            'categoriaId'    => $categoriaId,
            'tipo'           => $tipo,
            'categorias'     => $categorias,
            'dadosRelatorio' => $dadosRelatorio
        ], 'admin');
    }

    /**
     * Download do PDF A4 Timbrado do Relatório de Vendas e Curva ABC
     */
    public function vendasAlimentoPdf(): void
    {
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));
        $turno = trim((string)$this->input('turno', ''));
        $categoriaId = (int)$this->input('categoria_id', 0);
        $tipo = trim((string)$this->input('tipo', ''));

        $dadosRelatorio = $this->obterDadosVendasAlimento($dataInicio, $dataFim, $turno, $categoriaId, $tipo);
        $usuario = Auth::user();
        $dadosRelatorio['operador'] = $usuario['nome'] ?? 'Administração';

        $pdfBinario = PdfService::gerarRelatorioVendasAlimentoPdf($dadosRelatorio);
        $nomeArquivo = 'relatorio_vendas_alimentos_' . date('Ymd_His') . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($pdfBinario));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBinario;
        exit;
    }

    /**
     * Relatório de Desconto em Folha para RH (FSD Tela 8.3 & Seção 22.4)
     */
    public function descontoFolha(): void
    {
        $competencia = trim((string)$this->input('competencia', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
            $competencia = date('Y-m');
        }

        $dadosFolha = $this->obterDadosDescontoFolha($competencia);

        $this->render('relatorios/desconto_folha', [
            'pageTitle'   => 'Desconto em Folha para Recursos Humanos',
            'activeMenu'  => 'relatorios',
            'user'        => Auth::user(),
            'competencia' => $competencia,
            'dadosFolha'  => $dadosFolha
        ], 'admin');
    }

    /**
     * Download do PDF A4 Timbrado do Relatório de Desconto em Folha
     */
    public function descontoFolhaPdf(): void
    {
        $competencia = trim((string)$this->input('competencia', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
            $competencia = date('Y-m');
        }

        $dadosFolha = $this->obterDadosDescontoFolha($competencia);
        $usuario = Auth::user();
        $dadosFolha['operador'] = $usuario['nome'] ?? 'Administração Geral';

        $pdfBinario = PdfService::gerarRelatorioDescontoFolhaPdf($dadosFolha);
        $nomeArquivo = 'desconto_folha_rh_' . str_replace('-', '_', $competencia) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($pdfBinario));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBinario;
        exit;
    }

    /**
     * Exportação CSV Oficial para Lançamento em Folha de Pagamento do RH
     */
    public function descontoFolhaCsv(): void
    {
        $competencia = trim((string)$this->input('competencia', date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $competencia)) {
            $competencia = date('Y-m');
        }

        $dadosFolha = $this->obterDadosDescontoFolha($competencia);
        $colaboradores = $dadosFolha['colaboradores'] ?? [];

        // UTF-8 BOM para garantir correta acentuação e abertura automática no Excel
        $csv = "\xEF\xBB\xBF";
        $csv .= "MATRICULA;NOME;CARGO;QUANTIDADE_LANCHES;VALOR_DESCONTO_RS;COMPETENCIA\r\n";

        foreach ($colaboradores as $c) {
            $mat   = str_replace(';', ' ', $c['matricula_funcional'] ?? '-');
            $nome  = str_replace(';', ' ', $c['colaborador_nome'] ?? '-');
            $cargo = str_replace(';', ' ', $c['cargo'] ?? '-');
            $qtd   = (int)($c['total_lanches'] ?? 0);
            $valor = number_format((float)($c['total_desconto'] ?? 0.0), 2, ',', '');

            $csv .= "{$mat};{$nome};{$cargo};{$qtd};{$valor};{$competencia}\r\n";
        }

        $nomeArquivo = 'desconto_folha_rh_' . str_replace('-', '_', $competencia) . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $csv;
        exit;
    }

    /**
     * Endpoint AJAX para detalhamento linha a linha de compras de um colaborador
     */
    public function descontoFolhaDetalhesAjax(): void
    {
        $colaboradorId = (int)$this->input('colaborador_id', 0);
        $competencia = trim((string)$this->input('competencia', date('Y-m')));

        if ($colaboradorId <= 0) {
            $this->json(['sucesso' => false, 'erro' => 'Colaborador inválido.']);
            return;
        }

        [$ano, $mes] = explode('-', $competencia);
        $ano = (int)$ano;
        $mes = (int)$mes;

        $colaborador = Colaborador::buscarPorId($colaboradorId);
        if (!$colaborador) {
            $this->json(['sucesso' => false, 'erro' => 'Colaborador não localizado.']);
            return;
        }

        $pdo = \App\Core\Model::getPdo();
        $sqlVendas = "
            SELECT 
                vb.id AS venda_id,
                vb.created_at,
                vb.valor_total,
                sc.turno,
                u.nome AS operador_nome
            FROM vendas_balcao vb
            INNER JOIN sessoes_caixa sc ON sc.id = vb.sessao_caixa_id
            INNER JOIN usuarios u ON u.id = vb.operador_id
            WHERE vb.colaborador_id = :colab_id
              AND vb.tipo_venda = 'desconto_folha'
              AND YEAR(vb.created_at) = :ano
              AND MONTH(vb.created_at) = :mes
            ORDER BY vb.created_at DESC
        ";
        $stmtVendas = $pdo->prepare($sqlVendas);
        $stmtVendas->execute(['colab_id' => $colaboradorId, 'ano' => $ano, 'mes' => $mes]);
        $vendas = $stmtVendas->fetchAll(\PDO::FETCH_ASSOC);

        $vendasDetalhadas = [];
        $totalGeral = 0.0;
        $totalItens = 0;

        foreach ($vendas as $v) {
            $sqlItens = "
                SELECT 
                    ivb.quantidade,
                    ivb.preco_unitario,
                    ivb.subtotal,
                    p.descricao AS produto_nome,
                    p.codigo_interno AS produto_codigo
                FROM itens_venda_balcao ivb
                INNER JOIN produtos p ON p.id = ivb.produto_id
                WHERE ivb.venda_balcao_id = :venda_id
            ";
            $stmtItens = $pdo->prepare($sqlItens);
            $stmtItens->execute(['venda_id' => $v['venda_id']]);
            $itens = $stmtItens->fetchAll(\PDO::FETCH_ASSOC);

            $qtdVenda = array_sum(array_column($itens, 'quantidade'));
            $totalItens += $qtdVenda;
            $totalGeral += (float)$v['valor_total'];

            $vendasDetalhadas[] = [
                'venda_id'        => (int)$v['venda_id'],
                'data_fmt'        => date('d/m/Y H:i', strtotime($v['created_at'])),
                'turno'           => ucfirst($v['turno']),
                'operador'        => $v['operador_nome'],
                'valor_total'     => (float)$v['valor_total'],
                'valor_total_fmt' => 'R$ ' . number_format((float)$v['valor_total'], 2, ',', '.'),
                'total_itens'     => $qtdVenda,
                'itens'           => $itens
            ];
        }

        $this->json([
            'sucesso'     => true,
            'colaborador' => [
                'id'        => $colaborador['id'],
                'nome'      => $colaborador['nome'],
                'matricula' => $colaborador['matricula_funcional'],
                'cargo'     => $colaborador['cargo']
            ],
            'competencia' => $competencia,
            'total_geral' => $totalGeral,
            'total_fmt'   => 'R$ ' . number_format($totalGeral, 2, ',', '.'),
            'total_itens' => $totalItens,
            'vendas'      => $vendasDetalhadas
        ]);
    }

    /**
     * Demonstrativo de Fechamento de Caixa e Prestação de Contas (FSD Tela 5.4 & Módulo 7.2)
     */
    public function fechamentoCaixa(): void
    {
        $turno = trim((string)$this->input('turno', ''));
        $dataInicio = trim((string)$this->input('data_inicio', ''));
        $dataFim = trim((string)$this->input('data_fim', ''));
        $pagina = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        $filtroTurno = in_array($turno, [SessaoCaixa::TURNO_MATUTINO, SessaoCaixa::TURNO_VESPERTINO], true) ? $turno : null;
        $totalHistorico = SessaoCaixa::contarHistorico($filtroTurno, $dataInicio ?: null, $dataFim ?: null);
        $totalPaginas = max(1, (int)ceil($totalHistorico / $itensPorPagina));
        $offset = ($pagina - 1) * $itensPorPagina;

        $sessoes = SessaoCaixa::listarHistorico($filtroTurno, $dataInicio ?: null, $dataFim ?: null, $itensPorPagina, $offset);

        // Agregação de estatísticas para a Stat-Strip
        $totaisPeriodo = SessaoCaixa::obterEstatisticasPeriodo($filtroTurno, $dataInicio ?: null, $dataFim ?: null);

        $this->render('relatorios/fechamento_caixa', [
            'pageTitle'      => 'Demonstrativo de Fechamento de Caixa',
            'activeMenu'     => 'relatorios',
            'user'           => Auth::user(),
            'turno'          => $turno,
            'dataInicio'     => $dataInicio,
            'dataFim'        => $dataFim,
            'pagina'         => $pagina,
            'totalPaginas'   => $totalPaginas,
            'totalHistorico' => $totalHistorico,
            'sessoes'        => $sessoes,
            'totaisPeriodo'  => $totaisPeriodo
        ], 'admin');
    }

    /**
     * Relatório de Devedores, Inadimplência e MED (FSD Tela 8.4)
     */
    public function devedores(): void
    {
        $status = trim((string)$this->input('status', 'todos'));
        $busca = trim((string)$this->input('busca', ''));
        $pagina = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        $dados = Responsavel::listarNegativadosMed([
            'status' => $status,
            'busca'  => $busca
        ], $pagina, $itensPorPagina);

        $totalDevedor = Responsavel::somarSaldoDevedorMed();
        $totalDevedoresContagem = Responsavel::contarNegativadosMed();

        $this->render('relatorios/devedores', [
            'pageTitle'              => 'Relatório de Inadimplência e Bloqueios MED',
            'activeMenu'             => 'relatorios',
            'user'                   => Auth::user(),
            'status'                 => $status,
            'busca'                  => $busca,
            'pagina'                 => $pagina,
            'totalPaginas'           => $dados['total_paginas'],
            'totalRegistros'         => $dados['total'],
            'familias'               => $dados['itens'],
            'totalDevedor'           => $totalDevedor,
            'totalDevedoresContagem' => $totalDevedoresContagem
        ], 'admin');
    }

    // =========================================================================
    // MÉTODOS AUXILIARES DE CONSULTA E PROCESSAMENTO (DATABASE ENGINE)
    // =========================================================================

    /**
     * Consolida dados completos para o Extrato do Aluno
     */
    private function obterDadosExtratoAluno(int $alunoId, string $dataInicio, string $dataFim): ?array
    {
        $aluno = Aluno::buscarPorId($alunoId);
        if (!$aluno) {
            return null;
        }

        $pdo = \App\Core\Model::getPdo();

        // 1. Recargas de Saldo da Carteira Familiar no Período
        $sqlRecargas = "
            SELECT 
                tc.*,
                u.nome AS operador_nome
            FROM transacoes_carteira tc
            LEFT JOIN usuarios u ON u.id = tc.operador_id
            WHERE (tc.aluno_id = :aluno_id OR tc.responsavel_id = :resp_id)
              AND tc.tipo_transacao IN ('recarga_pix', 'recarga_dinheiro', 'estorno_cancelamento', 'estorno_balcao')
              AND DATE(tc.created_at) BETWEEN :data_ini AND :data_fim
            ORDER BY tc.created_at DESC
        ";
        $stmtRec = $pdo->prepare($sqlRecargas);
        $stmtRec->execute([
            'aluno_id' => $alunoId,
            'resp_id'  => (int)$aluno['responsavel_id'],
            'data_ini' => $dataInicio,
            'data_fim' => $dataFim
        ]);
        $recargas = $stmtRec->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Consumo Detalhado (Pedidos Agendados/Entregues)
        $sqlPedidos = "
            SELECT 
                p.id AS referencia_id,
                p.data_consumo,
                p.turno_consumo AS turno,
                p.status,
                p.valor_total,
                p.created_at,
                'Agendamento Online' AS canal,
                GROUP_CONCAT(CONCAT(ip.quantidade, 'x ', pr.descricao) SEPARATOR ', ') AS itens_descricao,
                COALESCE(SUM(ip.quantidade), 0) AS total_itens
            FROM pedidos p
            INNER JOIN itens_pedido ip ON ip.pedido_id = p.id
            INNER JOIN produtos pr ON pr.id = ip.produto_id
            WHERE p.aluno_id = :aluno_id
              AND p.status IN ('agendado', 'editado', 'entregue')
              AND p.data_consumo BETWEEN :data_ini AND :data_fim
            GROUP BY p.id, p.data_consumo, p.turno_consumo, p.status, p.valor_total, p.created_at
        ";
        $stmtPed = $pdo->prepare($sqlPedidos);
        $stmtPed->execute([
            'aluno_id' => $alunoId,
            'data_ini' => $dataInicio,
            'data_fim' => $dataFim
        ]);
        $pedidos = $stmtPed->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Consumo Detalhado (Vendas de Balcão PDV)
        $sqlBalcao = "
            SELECT 
                vb.id AS referencia_id,
                DATE(vb.created_at) AS data_consumo,
                sc.turno,
                'concluida' AS status,
                vb.valor_total,
                vb.created_at,
                'PDV Balcão' AS canal,
                GROUP_CONCAT(CONCAT(ivb.quantidade, 'x ', pr.descricao) SEPARATOR ', ') AS itens_descricao,
                COALESCE(SUM(ivb.quantidade), 0) AS total_itens
            FROM vendas_balcao vb
            INNER JOIN sessoes_caixa sc ON sc.id = vb.sessao_caixa_id
            INNER JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id
            INNER JOIN produtos pr ON pr.id = ivb.produto_id
            WHERE vb.aluno_id = :aluno_id
              AND DATE(vb.created_at) BETWEEN :data_ini AND :data_fim
            GROUP BY vb.id, vb.created_at, sc.turno, vb.valor_total
        ";
        $stmtBal = $pdo->prepare($sqlBalcao);
        $stmtBal->execute([
            'aluno_id' => $alunoId,
            'data_ini' => $dataInicio,
            'data_fim' => $dataFim
        ]);
        $balcao = $stmtBal->fetchAll(\PDO::FETCH_ASSOC);

        // Mescla e ordena o consumo por data mais recente
        $consumo = array_merge($pedidos, $balcao);
        usort($consumo, function($a, $b) {
            $cmp = strcmp($b['data_consumo'], $a['data_consumo']);
            if ($cmp !== 0) return $cmp;
            return strcmp($b['created_at'], $a['created_at']);
        });

        $totalRecargas = 0.0;
        foreach ($recargas as $r) {
            $totalRecargas += (float)$r['valor'];
        }

        $totalConsumo = 0.0;
        $totalLanches = 0;
        foreach ($consumo as $c) {
            $totalConsumo += (float)$c['valor_total'];
            $totalLanches += (int)$c['total_itens'];
        }

        $periodoFmt = date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim));

        return [
            'aluno'          => $aluno,
            'periodo'        => $periodoFmt,
            'data_inicio'    => $dataInicio,
            'data_fim'       => $dataFim,
            'saldo_atual'    => (float)($aluno['saldo_atual'] ?? 0.0),
            'total_recargas' => $totalRecargas,
            'total_consumo'  => $totalConsumo,
            'total_lanches'  => $totalLanches,
            'recargas'       => $recargas,
            'consumo'        => $consumo
        ];
    }

    /**
     * Consolida dados para o Relatório de Vendas por Alimento e Curva ABC
     */
    private function obterDadosVendasAlimento(string $dataInicio, string $dataFim, string $turno, int $categoriaId, string $tipo): array
    {
        $pdo = \App\Core\Model::getPdo();

        $params = [
            'ini1' => $dataInicio, 'fim1' => $dataFim,
            'ini2' => $dataInicio, 'fim2' => $dataFim
        ];

        $whereTurno1 = "";
        $whereTurno2 = "";
        if (!empty($turno) && in_array($turno, ['matutino', 'vespertino'], true)) {
            $whereTurno1 = " AND ped.turno_consumo = :turno1";
            $whereTurno2 = " AND sc.turno = :turno2";
            $params['turno1'] = $turno;
            $params['turno2'] = $turno;
        }

        $whereCat = "";
        if ($categoriaId > 0) {
            $whereCat = " AND p.categoria_id = :cat_id";
            $params['cat_id'] = $categoriaId;
        }

        $whereTipo = "";
        if (!empty($tipo)) {
            $whereTipo = " AND p.tipo = :tipo";
            $params['tipo'] = $tipo;
        }

        $sql = "
            SELECT 
                p.id AS produto_id,
                p.codigo_interno AS codigo,
                p.codigo_interno,
                p.descricao,
                COALESCE(c.nome, 'Sem Categoria') AS categoria_nome,
                p.tipo,
                SUM(v.quantidade) AS quantidade_total,
                SUM(v.subtotal) AS faturamento_total,
                (SUM(v.subtotal) / SUM(v.quantidade)) AS preco_medio
            FROM (
                -- Itens de pedidos agendados/entregues
                SELECT 
                    ip.produto_id,
                    ip.quantidade,
                    ip.subtotal
                FROM itens_pedido ip
                INNER JOIN pedidos ped ON ped.id = ip.pedido_id
                WHERE ped.status IN ('agendado', 'editado', 'entregue')
                  AND ped.data_consumo BETWEEN :ini1 AND :fim1
                  {$whereTurno1}

                UNION ALL

                -- Itens de vendas avulsas e colaboradores no balcão
                SELECT 
                    ivb.produto_id,
                    ivb.quantidade,
                    ivb.subtotal
                FROM itens_venda_balcao ivb
                INNER JOIN vendas_balcao vb ON vb.id = ivb.venda_balcao_id
                INNER JOIN sessoes_caixa sc ON sc.id = vb.sessao_caixa_id
                WHERE DATE(vb.created_at) BETWEEN :ini2 AND :fim2
                  {$whereTurno2}
            ) AS v
            INNER JOIN produtos p ON p.id = v.produto_id
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE 1=1
              {$whereCat}
              {$whereTipo}
            GROUP BY p.id, p.codigo_interno, p.descricao, c.nome, p.tipo
            ORDER BY faturamento_total DESC, quantidade_total DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $itens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Faturamento Total consolidado para cálculo de percentual e Curva ABC
        $faturamentoGeral = 0.0;
        $quantidadeGeral = 0;
        foreach ($itens as $item) {
            $faturamentoGeral += (float)$item['faturamento_total'];
            $quantidadeGeral += (int)$item['quantidade_total'];
        }

        $acumuladoPercentual = 0.0;
        foreach ($itens as &$item) {
            $fat = (float)$item['faturamento_total'];
            $qtd = (int)$item['quantidade_total'];

            $partPercent = ($faturamentoGeral > 0) ? ($fat / $faturamentoGeral) * 100.0 : 0.0;
            $acumuladoPercentual += $partPercent;

            // Classificação Curva ABC (FSD Seção 22.2):
            // Classe A: até 70% do faturamento acumulado (alta criticidade/receita)
            // Classe B: entre 70% e 90%
            // Classe C: acima de 90% (cauda longa)
            if ($acumuladoPercentual <= 70.01) {
                $classeAbc = 'A';
            } elseif ($acumuladoPercentual <= 90.01) {
                $classeAbc = 'B';
            } else {
                $classeAbc = 'C';
            }

            $item['participacao_percentual'] = $partPercent;
            $item['classe_abc'] = $classeAbc;
        }
        unset($item);

        $periodoFmt = date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim));
        $turnoStr = !empty($turno) ? ucfirst($turno) : 'Todos os Turnos';

        $catNome = 'Todas as Categorias';
        if ($categoriaId > 0) {
            $catObj = Categoria::buscarPorId($categoriaId);
            if ($catObj) {
                $catNome = $catObj['nome'];
            }
        }

        return [
            'periodo'   => $periodoFmt,
            'turno'     => $turnoStr,
            'categoria' => $catNome,
            'itens'     => $itens,
            'totais'    => [
                'faturamento_total' => $faturamentoGeral,
                'quantidade_total'  => $quantidadeGeral,
                'ticket_medio'      => ($quantidadeGeral > 0) ? ($faturamentoGeral / $quantidadeGeral) : 0.0
            ]
        ];
    }

    /**
     * Consolida dados para o Relatório de Desconto em Folha para RH
     */
    private function obterDadosDescontoFolha(string $competencia): array
    {
        [$ano, $mes] = explode('-', $competencia);
        $ano = (int)$ano;
        $mes = (int)$mes;

        $pdo = \App\Core\Model::getPdo();

        // 1. Consulta dos Colaboradores com Compras Faturadas sob Desconto em Folha
        $sqlColabs = "
            SELECT 
                c.id AS colaborador_id,
                c.matricula_funcional,
                c.nome AS colaborador_nome,
                c.cargo,
                c.foto_url,
                COUNT(DISTINCT vb.id) AS total_compras,
                COALESCE(SUM(vb.valor_total), 0.00) AS total_desconto
            FROM colaboradores c
            INNER JOIN vendas_balcao vb ON vb.colaborador_id = c.id
            WHERE vb.tipo_venda = 'desconto_folha'
              AND YEAR(vb.created_at) = :ano
              AND MONTH(vb.created_at) = :mes
            GROUP BY c.id, c.matricula_funcional, c.nome, c.cargo, c.foto_url
            ORDER BY c.nome ASC
        ";
        $stmt = $pdo->prepare($sqlColabs);
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        $colaboradores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // 2. Consulta da contagem real de lanches/itens por colaborador
        $sqlLanches = "
            SELECT 
                vb.colaborador_id,
                COALESCE(SUM(ivb.quantidade), 0) AS total_lanches
            FROM itens_venda_balcao ivb
            INNER JOIN vendas_balcao vb ON vb.id = ivb.venda_balcao_id
            WHERE vb.tipo_venda = 'desconto_folha'
              AND YEAR(vb.created_at) = :ano
              AND MONTH(vb.created_at) = :mes
            GROUP BY vb.colaborador_id
        ";
        $stmtLanches = $pdo->prepare($sqlLanches);
        $stmtLanches->execute(['ano' => $ano, 'mes' => $mes]);
        $lanchesMap = $stmtLanches->fetchAll(\PDO::FETCH_KEY_PAIR);

        $totalDescontoGeral = 0.0;
        $totalLanchesGeral = 0;
        $maiorConsumo = 0.0;
        $topColaborador = '-';

        foreach ($colaboradores as &$c) {
            $colabId = (int)$c['colaborador_id'];
            $c['total_lanches'] = (int)($lanchesMap[$colabId] ?? 0);

            $desc = (float)$c['total_desconto'];
            $totalDescontoGeral += $desc;
            $totalLanchesGeral += $c['total_lanches'];

            if ($desc > $maiorConsumo) {
                $maiorConsumo = $desc;
                $topColaborador = $c['colaborador_nome'];
            }
        }
        unset($c);

        $mesesNome = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        $competenciaExtensa = ($mesesNome[$mes] ?? 'Mês ' . $mes) . ' de ' . $ano;

        return [
            'competencia'          => $competenciaExtensa,
            'competencia_chave'    => $competencia,
            'colaboradores'        => $colaboradores,
            'total_desconto'       => $totalDescontoGeral,
            'total_lanches'        => $totalLanchesGeral,
            'total_colaboradores'  => count($colaboradores),
            'maior_consumo'        => $maiorConsumo,
            'top_colaborador'      => $topColaborador
        ];
    }
}
