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
use App\Models\Pedido;
use App\Models\SessaoCaixa;
use App\Models\TransacaoCarteira;
use App\Services\PdfService;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class RelatoriosController extends Controller
{
    /**
     * Sanitiza célula de planilha para prevenir CSV / Formula Injection no Excel e Calc
     */
    private static function sanitizarCsvCelula(mixed $valor): mixed
    {
        if (!is_string($valor)) {
            return $valor;
        }
        $trim = trim($valor);
        if ($trim === '') {
            return $valor;
        }

        // 1. Remove sinal de '+' inicial antes de dígitos (ex: '+150,00' -> '150,00', '+50 un' -> '50 un')
        if (str_starts_with($trim, '+') && preg_match('/^\+\s*\d/', $trim)) {
            return ltrim($trim, '+');
        }

        // 2. Preserva números negativos legítimos no padrão brasileiro (ex: '-50,00', '-1.234,56', '-10%')
        if (str_starts_with($trim, '-') && preg_match('/^-[\d\.,\s]+%?$/', $trim)) {
            return $trim;
        }

        // 3. Converte marcadores contábeis iniciais que usam parênteses para colchetes
        // Isso impede que o Excel tente calcular como fórmula matemática
        if (str_starts_with($trim, '(=)')) {
            return '[=]' . substr($trim, 3);
        }
        if (str_starts_with($trim, '(+)')) {
            return '[+]' . substr($trim, 3);
        }
        if (str_starts_with($trim, '(-)')) {
            return '[-]' . substr($trim, 3);
        }

        // 4. Se começar com traço textual avulso (ex: '- Produto'), substitui por traço gráfico '–'
        if (str_starts_with($trim, '- ')) {
            return '– ' . substr($trim, 2);
        }
        if ($trim === '-') {
            return '';
        }

        // 5. Prevenção de CSV Formula Injection: se começar com '=', '+', '-', '@' em texto livre
        $primeiro = $trim[0];
        if (in_array($primeiro, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return ' ' . $valor; // Espaço inicial anula a interpretação de fórmula no Excel de forma limpa e invisível
        }

        return $valor;
    }

    /**
     * Sanitiza todas as células de uma linha de CSV
     */
    private static function sanitizarCsvLinha(array $linha): array
    {
        return array_map([self::class, 'sanitizarCsvCelula'], $linha);
    }

    /**
     * Central de Relatórios Gerenciais (Descontinuada — Redireciona para o Painel da Cantina)
     */
    public function index(): void
    {
        $this->redirect('/admin');
    }

    /**
     * Extrato Analítico de Consumo e Recargas do Aluno (FSD Tela 8.5 & Seção 22.1)
     */
    public function extratoAluno(): void
    {
        $alunoId = (int)$this->input('aluno_id', 0);
        $turmaId = (int)$this->input('turma_id', 0);
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));

        $apenasSaldo = ($this->input('apenas_saldo', 'nao') === 'sim');
        $saldoApenasPeriodo = ($this->input('saldo_apenas_periodo', 'nao') === 'sim');
        $abaAtiva = trim((string)$this->input('aba', 'filtros'));
        if (!in_array($abaAtiva, ['filtros', 'leiaute', 'layout'], true)) {
            $abaAtiva = 'filtros';
        }

        $paginaRecargas = max(1, (int)$this->input('pagina_recargas', (int)$this->input('pagina', 1)));
        $paginaConsumo  = max(1, (int)$this->input('pagina_consumo', 1));
        $itensPorPagina = 50;

        $dadosExtrato = null;
        if ($alunoId > 0) {
            $dadosExtrato = $this->obterDadosExtratoAluno(
                $alunoId,
                $dataInicio,
                $dataFim,
                $apenasSaldo,
                $saldoApenasPeriodo,
                $paginaRecargas,
                $paginaConsumo,
                $itensPorPagina,
                false
            );
        }

        $turmas = \App\Models\Turma::listarTodas();

        $this->render('relatorios/extrato_aluno', [
            'pageTitle'           => 'Extrato Analítico do Aluno',
            'activeMenu'          => 'extrato_aluno',
            'user'                => Auth::user(),
            'alunoId'             => $alunoId,
            'turmaId'             => $turmaId,
            'turmas'              => $turmas,
            'dataInicio'          => $dataInicio,
            'dataFim'             => $dataFim,
            'apenasSaldo'         => $apenasSaldo,
            'saldoApenasPeriodo'  => $saldoApenasPeriodo,
            'abaAtiva'            => $abaAtiva,
            'dadosExtrato'        => $dadosExtrato
        ], 'admin');
    }

    /**
     * Download do PDF A4 Timbrado do Extrato do Aluno
     */
    /**
     * Emissão do Extrato do Aluno para Impressão e Print to PDF Nativo
     */
    public function extratoAlunoPdf(): void
    {
        $this->extratoAlunoImprimir();
    }

    /**
     * Espelho HTML de Impressão Direta do Extrato do Aluno
     */
    public function extratoAlunoImprimir(): void
    {
        $alunoId = (int)$this->input('aluno_id', 0);
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));
        $apenasSaldo = ($this->input('apenas_saldo', 'nao') === 'sim');
        $saldoApenasPeriodo = ($this->input('saldo_apenas_periodo', 'nao') === 'sim');

        if ($alunoId <= 0) {
            Session::setFlash('danger', 'Selecione um aluno para imprimir o extrato.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $dadosExtrato = $this->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim, $apenasSaldo, $saldoApenasPeriodo, 1, 1, 50, false);
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
     * Exportação em Planilha CSV do Extrato do Aluno
     */
    public function extratoAlunoCsv(): void
    {
        $alunoId = (int)$this->input('aluno_id', 0);
        $dataInicio = trim((string)$this->input('data_inicio', date('Y-m-01')));
        $dataFim = trim((string)$this->input('data_fim', date('Y-m-d')));
        $apenasSaldo = ($this->input('apenas_saldo', 'nao') === 'sim');
        $saldoApenasPeriodo = ($this->input('saldo_apenas_periodo', 'nao') === 'sim');

        if ($alunoId <= 0) {
            Session::setFlash('danger', 'Selecione um aluno para exportar o extrato em planilha.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $dadosExtrato = $this->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim, $apenasSaldo, $saldoApenasPeriodo, 1, 1, 50, false);
        if (!$dadosExtrato) {
            Session::setFlash('danger', 'Aluno não localizado.');
            $this->redirect('/relatorios/extrato-aluno');
            return;
        }

        $matricula = preg_replace('/[^A-Za-z0-9]/', '', $dadosExtrato['aluno']['matricula'] ?? 'aluno');
        $nomeArquivo = 'extrato_aluno_' . $matricula . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM para Excel
        $delimitador = ';';

        fputcsv($out, self::sanitizarCsvLinha(['COLÉGIO MONTEIRO LOBATO - EXTRATO DE CONSUMO DO ALUNO']), $delimitador, '"', "\\");
        fputcsv($out, self::sanitizarCsvLinha(['Período:', $dadosExtrato['periodo']]), $delimitador, '"', "\\");
        fputcsv($out, self::sanitizarCsvLinha(['Aluno:', $dadosExtrato['aluno']['nome'], 'Matrícula:', $dadosExtrato['aluno']['matricula'], 'Turma:', ($dadosExtrato['aluno']['serie'] ?? '') . ' ' . ($dadosExtrato['aluno']['nome_turma'] ?? '')]), $delimitador, '"', "\\");
        fputcsv($out, self::sanitizarCsvLinha(['Saldo Atual:', 'R$ ' . number_format($dadosExtrato['saldo_atual'], 2, ',', '.')]), $delimitador, '"', "\\");
        fputcsv($out, [], $delimitador, '"', "\\");

        if ($apenasSaldo) {
            fputcsv($out, self::sanitizarCsvLinha(['Resumo Financeiro do Aluno']), $delimitador, '"', "\\");
            fputcsv($out, self::sanitizarCsvLinha(['Saldo Inicial:', number_format($dadosExtrato['saldo_inicial'], 2, ',', '.')]), $delimitador, '"', "\\");
            fputcsv($out, self::sanitizarCsvLinha(['Total Recarregado no Período:', number_format($dadosExtrato['total_recargas'], 2, ',', '.')]), $delimitador, '"', "\\");
            fputcsv($out, self::sanitizarCsvLinha(['Total Gasto no Período:', number_format($dadosExtrato['total_gasto'], 2, ',', '.')]), $delimitador, '"', "\\");
            fputcsv($out, self::sanitizarCsvLinha(['Saldo Atual da Carteira:', number_format($dadosExtrato['saldo_atual'], 2, ',', '.')]), $delimitador, '"', "\\");
        } else {
            fputcsv($out, self::sanitizarCsvLinha(['Data', 'Descrição', 'Qtd', 'V. Unitário (R$)', 'V. Total (R$)', 'Forma Pagamento', 'Saldo (R$)']), $delimitador, '"', "\\");
            fputcsv($out, self::sanitizarCsvLinha(['', 'Saldo inicial', '', '', '', '', number_format($dadosExtrato['saldo_inicial'], 2, ',', '.')]), $delimitador, '"', "\\");

            foreach ($dadosExtrato['linhas'] as $l) {
                fputcsv($out, self::sanitizarCsvLinha([
                    $l['data_formatada'] ?? '',
                    $l['descricao'] ?? '',
                    $l['quantidade'] ?? '',
                    is_numeric($l['valor_unitario']) ? number_format((float)$l['valor_unitario'], 2, ',', '.') : $l['valor_unitario'],
                    is_numeric($l['valor_total']) ? number_format((float)$l['valor_total'], 2, ',', '.') : $l['valor_total'],
                    $l['forma_pagamento'] ?? '',
                    is_numeric($l['saldo']) ? number_format((float)$l['saldo'], 2, ',', '.') : ''
                ]), $delimitador, '"', "\\");
            }

            fputcsv($out, self::sanitizarCsvLinha(['', 'Total gasto no periodo', '', '', number_format($dadosExtrato['total_gasto'], 2, ',', '.'), '', '']), $delimitador, '"', "\\");
        }

        fclose($out);
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
                'turma_id'   => (int)($a['turma_id'] ?? 0),
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
            'pageTitle'      => 'Vendas por Alimento',
            'activeMenu'     => 'vendas_filtros',
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
     * Exportação CSV Oficial de Vendas por Alimento (Fase 21)
     */
    public function vendasAlimentoCsv(): void
    {
        $this->vendasFiltrosCsv();
    }

    /**
     * Painel de Filtros Avançados de Vendas da Cantina (Fase 21 — Conforme Filtros de vendas.pdf)
     */
    public function vendasFiltros(): void
    {
        $filtros = $this->extrairFiltrosVendas();
        $categorias = Categoria::listarTodas(true);

        $this->render('relatorios/vendas_filtros', [
            'pageTitle'      => 'Filtros de Vendas & Relatório por Alimento',
            'activeMenu'     => 'vendas_filtros',
            'user'           => Auth::user(),
            'filtros'        => $filtros,
            'categorias'     => $categorias,
            'dadosRelatorio' => []
        ], 'admin');
    }

    /**
     * Emissão do PDF Oficial do Relatório de Vendas (Fase 21 — Conforme relatorio de vendas por período.pdf)
     */
    public function vendasFiltrosPdf(): void
    {
        $filtros = $this->extrairFiltrosVendas();
        $dadosRelatorio = $this->obterDadosVendasAvancadas($filtros);
        $usuario = Auth::user();
        $dadosRelatorio['operador'] = $usuario['nome'] ?? 'Administração Geral';

        $pdfBinario = PdfService::gerarRelatorioVendasAlimentoPdf($dadosRelatorio);
        $nomeArquivo = 'relatorio_vendas_' . str_replace('-', '', $filtros['data_inicio']) . '_' . str_replace('-', '', $filtros['data_fim']) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . strlen($pdfBinario));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBinario;
        exit;
    }

    /**
     * Espelho HTML para Visualização e Impressão Direta (window.print)
     */
    public function vendasFiltrosImprimir(): void
    {
        $filtros = $this->extrairFiltrosVendas();
        $dadosRelatorio = $this->obterDadosVendasAvancadas($filtros);
        $usuario = Auth::user();
        $dadosRelatorio['operador'] = $usuario['nome'] ?? 'Administração Geral';

        $html = PdfService::renderView('relatorios/vendas_alimento_pdf', ['dadosVendas' => $dadosRelatorio]);

        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $html;
        exit;
    }

    /**
     * Exportação CSV Oficial dos Dados Filtrados de Vendas
     */
    public function vendasFiltrosCsv(): void
    {
        $filtros = $this->extrairFiltrosVendas();
        $dadosRelatorio = $this->obterDadosVendasAvancadas($filtros);

        $nomeArquivo = 'vendas_cantina_' . str_replace('-', '', $filtros['data_inicio']) . '_' . str_replace('-', '', $filtros['data_fim']) . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM para Excel

        $emitirObs = ($filtros['emitir_observacao'] === 'sim');
        $emitirForma = ($filtros['emitir_forma_pagamento'] === 'sim');
        $emitirHorario = ($filtros['emitir_horario'] === 'sim');
        $agruparTipo = ($filtros['agrupar_tipo_pessoa'] === 'sim');

        // Cabeçalho CSV
        $cabecalho = ["DATA"];
        if ($emitirHorario) $cabecalho[] = "HORARIO";
        if ($agruparTipo) $cabecalho[] = "TIPO_PESSOA";
        $cabecalho[] = "ALIMENTO";
        $cabecalho[] = "CATEGORIA";
        $cabecalho[] = "QUANTIDADE";
        $cabecalho[] = "VALOR_UNITARIO_RS";
        $cabecalho[] = "VALOR_TOTAL_RS";
        if ($emitirForma) $cabecalho[] = "FORMA_PAGAMENTO";
        if ($emitirObs) $cabecalho[] = "OBSERVACAO";

        fputcsv($output, $cabecalho, ';', '"', "\\");

        // Linhas de dados
        $itens = $dadosRelatorio['itens'] ?? [];
        foreach ($itens as $item) {
            $linha = [];
            $linha[] = $item['data_fmt'] ?? ($item['data_venda'] ?? '');
            if ($emitirHorario) $linha[] = $item['hora_venda'] ?? '';
            if ($agruparTipo) $linha[] = $item['tipo_pessoa'] ?? 'Aluno';
            $linha[] = $item['descricao'] ?? ($item['produto_nome'] ?? '');
            $linha[] = $item['categoria_nome'] ?? '';
            $linha[] = (int)($item['quantidade'] ?? ($item['quantidade_total'] ?? 0));
            $linha[] = number_format((float)($item['preco_unitario'] ?? ($item['preco_medio'] ?? 0.0)), 2, ',', '.');
            $linha[] = number_format((float)($item['subtotal'] ?? ($item['valor_total'] ?? 0.0)), 2, ',', '.');
            if ($emitirForma) $linha[] = $item['forma_pagamento'] ?? '';
            if ($emitirObs) $linha[] = str_replace(["\r", "\n"], ' ', $item['observacao'] ?? '');

            fputcsv($output, self::sanitizarCsvLinha($linha), ';', '"', "\\");
        }

        // Totais do período no CSV com correspondência exata de colunas
        $totalFat = number_format((float)($dadosRelatorio['totais']['faturamento_total'] ?? 0.0), 2, ',', '.');
        $totalQtd = (int)($dadosRelatorio['totais']['quantidade_total'] ?? 0);

        $linhaTotal = array_fill(0, count($cabecalho), '');
        $linhaTotal[0] = 'TOTAL DO PERÍODO';
        
        // Localiza índices das colunas QUANTIDADE e VALOR_TOTAL_RS
        $idxQtd = array_search('QUANTIDADE', $cabecalho);
        $idxTotal = array_search('VALOR_TOTAL_RS', $cabecalho);
        if ($idxQtd !== false) $linhaTotal[$idxQtd] = $totalQtd;
        if ($idxTotal !== false) $linhaTotal[$idxTotal] = $totalFat;

        fputcsv($output, self::sanitizarCsvLinha($linhaTotal), ';', '"', "\\");
        fclose($output);
        exit;
    }

    /**
     * Extrai e sanitiza os parâmetros de filtros de vendas da requisição
     */
    private function extrairFiltrosVendas(): array
    {
        $hoje = date('Y-m-d');
        $inicioPadrao = trim((string)$this->input('data_inicio', ''));
        if (empty($inicioPadrao)) {
            $inicioPadrao = $hoje;
        }

        $fimPadrao = trim((string)$this->input('data_fim', ''));
        if (empty($fimPadrao)) {
            $fimPadrao = $hoje;
        }

        return [
            'data_inicio'             => $inicioPadrao,
            'data_fim'                => $fimPadrao,
            'turno'                   => trim((string)$this->input('turno', '')),
            'categoria_id'            => (int)$this->input('categoria_id', 0),
            'tipo'                    => trim((string)$this->input('tipo', '')),
            'emitir_observacao'       => trim((string)$this->input('emitir_observacao', 'nao')),
            'emitir_forma_pagamento'  => trim((string)$this->input('emitir_forma_pagamento', 'nao')),
            'incluir_estornos'        => trim((string)$this->input('incluir_estornos', 'nao')),
            'agrupar_tipo_pessoa'     => trim((string)$this->input('agrupar_tipo_pessoa', 'nao')),
            'agrupar_periodo'         => trim((string)$this->input('agrupar_periodo', 'sim')),
            'emitir_horario'          => trim((string)$this->input('emitir_horario', 'nao')),
            'emitir_vendas_alimento'  => trim((string)$this->input('emitir_vendas_alimento', 'sim')),
            'agrupar_alimento'        => trim((string)$this->input('agrupar_alimento', 'sim')),
            'ordenar_alimentos'       => trim((string)$this->input('ordenar_alimentos', 'mais_vendidos')),
            'emitir_vendas_aluno'     => trim((string)$this->input('emitir_vendas_aluno', 'nao')),
            'agrupar_vendas_aluno'    => trim((string)$this->input('agrupar_vendas_aluno', 'nao')),
            'agrupar_serie_turma'     => trim((string)$this->input('agrupar_serie_turma', 'nao')),
            'quebrar_pagina_turma'    => trim((string)$this->input('quebrar_pagina_turma', 'nao')),
            'ordenar_consumo_aluno'   => trim((string)$this->input('ordenar_consumo_aluno', 'nao')),
            'ordenar_por'             => trim((string)$this->input('ordenar_por', 'maior_consumo')),
            'aba_ativa'               => trim((string)$this->input('aba', 'filtro'))
        ];
    }

    /**
     * Relatório de Desconto em Folha para RH (Descontinuado conforme diretrizes da cantina)
     */
    public function descontoFolha(): void
    {
        Session::setFlash('warning', 'O relatório de desconto em folha foi descontinuado do sistema.');
        $this->redirect('/admin');
    }

    /**
     * Download do PDF A4 Timbrado do Relatório de Desconto em Folha (Descontinuado)
     */
    public function descontoFolhaPdf(): void
    {
        Session::setFlash('warning', 'O relatório de desconto em folha foi descontinuado do sistema.');
        $this->redirect('/admin');
    }

    /**
     * Exportação CSV Oficial para Lançamento em Folha de Pagamento do RH (Descontinuado)
     */
    public function descontoFolhaCsv(): void
    {
        Session::setFlash('warning', 'O relatório de desconto em folha foi descontinuado do sistema.');
        $this->redirect('/admin');
    }

    /**
     * Endpoint AJAX para detalhamento de compras de um colaborador (Descontinuado)
     */
    public function descontoFolhaDetalhesAjax(): void
    {
        $this->json(['sucesso' => false, 'erro' => 'Módulo de desconto em folha descontinuado.']);
    }

    /**
     * Download do PDF Individual de Desconto em Folha (Descontinuado)
     */
    public function descontoFolhaIndividualPdf(): void
    {
        Session::setFlash('warning', 'O relatório de desconto em folha foi descontinuado do sistema.');
        $this->redirect('/admin');
    }

    /**
     * Espelho HTML do Demonstrativo Individual (Descontinuado)
     */
    public function descontoFolhaIndividualImprimir(): void
    {
        Session::setFlash('warning', 'O relatório de desconto em folha foi descontinuado do sistema.');
        $this->redirect('/admin');
    }

    /**
     * Geração em Lote de Relatórios Individuais (Descontinuado)
     */
    public function descontoFolhaLotePdf(): void
    {
        Session::setFlash('warning', 'O relatório de desconto em folha foi descontinuado do sistema.');
        $this->redirect('/admin');
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

        // Carga sob demanda: ativa ao clicar em 'Filtrar' (mesmo com filtros padrão) ou ao paginar/filtrar
        $filtrar = (bool)($this->input('filtrar', false) || isset($_GET['filtrar']));
        $jaFiltrado = $filtrar || !empty($turno) || !empty($dataInicio) || !empty($dataFim) || isset($_GET['pagina']);

        $filtroTurno = in_array($turno, [SessaoCaixa::TURNO_MATUTINO, SessaoCaixa::TURNO_VESPERTINO], true) ? $turno : null;

        if ($jaFiltrado) {
            $totalHistorico = SessaoCaixa::contarHistorico($filtroTurno, $dataInicio ?: null, $dataFim ?: null);
            $totalPaginas = max(1, (int)ceil($totalHistorico / $itensPorPagina));
            $offset = ($pagina - 1) * $itensPorPagina;

            $sessoes = SessaoCaixa::listarHistorico($filtroTurno, $dataInicio ?: null, $dataFim ?: null, $itensPorPagina, $offset);

            // Agregação de estatísticas para a Stat-Strip
            $totaisPeriodo = SessaoCaixa::obterEstatisticasPeriodo($filtroTurno, $dataInicio ?: null, $dataFim ?: null);
        } else {
            $totalHistorico = 0;
            $totalPaginas = 1;
            $sessoes = [];
            $totaisPeriodo = [
                'faturamento_total'    => 0.0,
                'faturamento_dinheiro' => 0.0,
                'faturamento_pix'      => 0.0,
                'saldo_quebra_sobra'   => 0.0,
                'total_com_sobra'      => 0,
                'total_com_quebra'     => 0,
            ];
        }

        $this->render('relatorios/fechamento_caixa', [
            'pageTitle'      => 'Demonstrativo de Fechamento de Caixa',
            'activeMenu'     => 'fechamento_caixa_relatorio',
            'user'           => Auth::user(),
            'turno'          => $turno,
            'dataInicio'     => $dataInicio,
            'dataFim'        => $dataFim,
            'pagina'         => $pagina,
            'paginaAtual'    => $pagina,
            'totalPaginas'   => $totalPaginas,
            'totalHistorico' => $totalHistorico,
            'totalRegistros' => $totalHistorico,
            'itensPorPagina' => $itensPorPagina,
            'sessoes'        => $sessoes,
            'totaisPeriodo'  => $totaisPeriodo,
            'jaFiltrado'     => $jaFiltrado
        ], 'admin');
    }

    /**
     * Relatório de Devedores, Inadimplência e MED (FSD Tela 8.4)
     */
    /**
     * Relatório de Histórico de Eventos MED e Quitações (Auditoria Financeira)
     */
    public function devedores(): void
    {
        $tipo = trim((string)$this->input('tipo', 'todos'));
        $busca = trim((string)$this->input('busca', ''));
        $dataInicio = trim((string)$this->input('data_inicio', ''));
        $dataFim = trim((string)$this->input('data_fim', ''));
        $pagina = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        $filtros = [
            'tipo'        => $tipo,
            'busca'       => $busca,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim
        ];

        $resultado = TransacaoCarteira::listarHistoricoMedPaginado($filtros, $pagina, $itensPorPagina);

        $this->render('relatorios/devedores', [
            'pageTitle'           => 'Relatório de Histórico de Eventos MED & Quitações',
            'activeMenu'          => 'devedores',
            'user'                => Auth::user(),
            'tipo'                => $tipo,
            'busca'               => $busca,
            'dataInicio'          => $dataInicio,
            'dataFim'             => $dataFim,
            'pagina'              => $resultado['pagina'],
            'paginaAtual'         => $resultado['pagina'],
            'totalPaginas'        => $resultado['total_paginas'],
            'totalRegistros'      => $resultado['total'],
            'itensPorPagina'      => $itensPorPagina,
            'eventos'             => $resultado['itens'],
            'totalEstornosValor'  => $resultado['total_estornos'],
            'totalQuitacoesValor' => $resultado['total_quitacoes'],
            'filtros'             => $filtros
        ], 'admin');
    }

    /**
     * Emissão do Relatório A4 para Impressão e Print to PDF Nativo do Histórico de Eventos MED e Quitações
     */
    public function devedoresPdf(): void
    {
        $this->devedoresImprimir();
    }

    /**
     * Espelho HTML para visualização / impressão rápida em folha A4 do Histórico MED
     */
    public function devedoresImprimir(): void
    {
        $tipo = trim((string)$this->input('tipo', 'todos'));
        $busca = trim((string)$this->input('busca', ''));
        $dataInicio = trim((string)$this->input('data_inicio', ''));
        $dataFim = trim((string)$this->input('data_fim', ''));

        $filtros = [
            'tipo'        => $tipo,
            'busca'       => $busca,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim
        ];

        $resultado = TransacaoCarteira::listarHistoricoMedPaginado($filtros, 1, 1000);
        $usuario = Auth::user();

        echo PdfService::renderView('relatorios/med_historico_pdf', [
            'dadosMed' => [
                'eventos'         => $resultado['itens'],
                'total_eventos'   => $resultado['total'],
                'total_estornos'  => $resultado['total_estornos'],
                'total_quitacoes' => $resultado['total_quitacoes'],
                'filtros'         => $filtros,
                'operador'        => $usuario['nome'] ?? 'Administração'
            ]
        ]);
        if (!defined('CANTINA_TEST_MODE')) {
            exit;
        }
    }

    /**
     * Relatório de Corte do Turno (Painel de Parâmetros e Emissão Oficial A4 para Fornecedores)
     */
    public function corte(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = (isset($_GET['data']) && is_string($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'])) 
            ? $_GET['data'] 
            : $hoje;

        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : Pedido::TURNO_MATUTINO;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        $statusCorte = Pedido::obterStatusCorte($dataConsumo, $turnoConsumo);

        $this->render('relatorios/corte', [
            'pageTitle'    => 'Relatório Consolidado de Corte do Turno',
            'activeMenu'   => 'corte_relatorio',
            'user'         => Auth::user(),
            'dataConsumo'  => $dataConsumo,
            'turnoConsumo' => $turnoConsumo,
            'statusCorte'  => $statusCorte
        ], 'admin');
    }

    /**
     * Redireciona endpoint de PDF para a visualização unificada oficial de impressão e PDF (/relatorios/corte/pdf)
     */
    public function cortePdf(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = (isset($_GET['data']) && is_string($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'])) 
            ? $_GET['data'] 
            : $hoje;

        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : Pedido::TURNO_MATUTINO;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        $this->redirect('/relatorios/corte/imprimir?data=' . urlencode($dataConsumo) . '&turno=' . urlencode($turnoConsumo));
    }

    /**
     * Visualização limpa para impressão A4 no navegador (/relatorios/corte/imprimir)
     */
    public function corteImprimir(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = (isset($_GET['data']) && is_string($_GET['data']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data'])) 
            ? $_GET['data'] 
            : $hoje;

        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : Pedido::TURNO_MATUTINO;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        $dadosCorte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo);
        $html = PdfService::gerarHtmlRelatorioCorte($dadosCorte);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
        if (!defined('CANTINA_TEST_MODE')) {
            exit;
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES DE CONSULTA E PROCESSAMENTO (DATABASE ENGINE)
    // =========================================================================

    /**
     * Consolida dados completos para o Extrato do Aluno com cálculo de saldo progressivo linha a linha
     * Conforme FSD Tela 8.5 & Seção 22.1 e Relatório Extrato Consumo Aluno.pdf
     */
    public function obterDadosExtratoAluno(
        int $alunoId, 
        string $dataInicio, 
        string $dataFim, 
        bool $apenasSaldo = false,
        bool $saldoApenasPeriodo = false,
        int $paginaRecargas = 1, 
        int $paginaConsumo = 1, 
        int $porPagina = 50, 
        bool $paginar = false
    ): ?array
    {
        $pdo = \App\Core\Model::getPdo();

        // 1. Localiza Aluno com Turma e Responsável
        $sqlAluno = "
            SELECT 
                a.*,
                t.nome_turma,
                t.serie,
                t.turno AS turma_turno,
                r.nome AS responsavel_nome,
                r.cpf AS responsavel_cpf,
                r.whatsapp AS responsavel_whatsapp,
                r.saldo_atual AS responsavel_saldo
            FROM alunos a
            LEFT JOIN turmas t ON t.id = a.turma_id
            LEFT JOIN responsaveis r ON r.id = a.responsavel_id
            WHERE a.id = :id
        ";
        $stmtA = $pdo->prepare($sqlAluno);
        $stmtA->execute(['id' => $alunoId]);
        $aluno = $stmtA->fetch(\PDO::FETCH_ASSOC);

        if (!$aluno) {
            return null;
        }

        $respId = (int)($aluno['responsavel_id'] ?? 0);
        $saldoAtual = (float)($aluno['responsavel_saldo'] ?? 0.0);

        // 2. Itens individuais de Pedidos Online Agendados / Entregues
        $sqlPedidos = "
            SELECT 
                ip.id AS item_id,
                p.id AS referencia_id,
                p.data_consumo,
                p.created_at,
                COALESCE(NULLIF(TRIM(pr.descricao), ''), 'Item Sem Descrição') AS produto_nome,
                ip.quantidade,
                ip.preco_unitario,
                ip.subtotal AS valor_total,
                'Saldo' AS forma_pagamento,
                'consumo' AS tipo,
                p.status,
                'Agendamento Online' AS canal
            FROM pedidos p
            INNER JOIN itens_pedido ip ON ip.pedido_id = p.id
            INNER JOIN produtos pr ON pr.id = ip.produto_id
            WHERE p.aluno_id = :aluno_id
              AND p.status IN ('agendado', 'editado', 'entregue')
              AND p.data_consumo BETWEEN :data_ini AND :data_fim
        ";
        $stmtPed = $pdo->prepare($sqlPedidos);
        $stmtPed->execute([
            'aluno_id' => $alunoId,
            'data_ini' => $dataInicio,
            'data_fim' => $dataFim
        ]);
        $pedidos = $stmtPed->fetchAll(\PDO::FETCH_ASSOC);

        // 3. Itens individuais de Vendas de Balcão (PDV)
        $sqlBalcao = "
            SELECT 
                ivb.id AS item_id,
                vb.id AS referencia_id,
                DATE(vb.created_at) AS data_consumo,
                vb.created_at,
                COALESCE(NULLIF(TRIM(pr.descricao), ''), 'Item Sem Descrição') AS produto_nome,
                ivb.quantidade,
                ivb.preco_unitario,
                ivb.subtotal AS valor_total,
                CASE 
                    WHEN vb.forma_pagamento = 'dinheiro' THEN 'Dinheiro'
                    WHEN vb.forma_pagamento = 'pix_balcao' THEN 'Pix'
                    ELSE 'Saldo'
                END AS forma_pagamento,
                'consumo' AS tipo,
                'concluida' AS status,
                'PDV Balcão' AS canal
            FROM vendas_balcao vb
            INNER JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id
            INNER JOIN produtos pr ON pr.id = ivb.produto_id
            WHERE vb.aluno_id = :aluno_id
              AND (vb.status IS NULL OR vb.status = 'concluida')
              AND DATE(vb.created_at) BETWEEN :data_ini AND :data_fim
        ";
        $stmtBal = $pdo->prepare($sqlBalcao);
        $stmtBal->execute([
            'aluno_id' => $alunoId,
            'data_ini' => $dataInicio,
            'data_fim' => $dataFim
        ]);
        $balcao = $stmtBal->fetchAll(\PDO::FETCH_ASSOC);

        // 4. Recargas e Créditos da Carteira no Período
        $sqlRecargas = "
            SELECT 
                tc.id AS referencia_id,
                DATE(tc.created_at) AS data_consumo,
                tc.created_at,
                CASE 
                    WHEN tc.tipo_transacao IN ('recarga_pix', 'recarga_dinheiro') THEN 'Recarga'
                    WHEN tc.tipo_transacao = 'estorno_cancelamento' THEN 'Estorno de Cancelamento'
                    WHEN tc.tipo_transacao = 'estorno_balcao' THEN 'Estorno de Balcão'
                    ELSE 'Ajuste de Saldo'
                END AS produto_nome,
                '~' AS quantidade,
                '~' AS preco_unitario,
                tc.valor AS valor_total,
                CASE 
                    WHEN tc.tipo_transacao = 'recarga_pix' THEN 'Pix'
                    WHEN tc.tipo_transacao = 'recarga_dinheiro' THEN 'Dinheiro'
                    WHEN tc.tipo_transacao LIKE '%estorno%' THEN 'Estorno'
                    ELSE 'Saldo'
                END AS forma_pagamento,
                'recarga' AS tipo,
                'concluida' AS status,
                'Carteira Familiar' AS canal,
                tc.tipo_transacao,
                tc.descricao AS transacao_desc
            FROM transacoes_carteira tc
            WHERE (tc.aluno_id = :aluno_id OR tc.responsavel_id = :resp_id)
              AND tc.tipo_transacao IN ('recarga_pix', 'recarga_dinheiro', 'estorno_cancelamento', 'estorno_balcao', 'ajuste_manual')
              AND DATE(tc.created_at) BETWEEN :data_ini AND :data_fim
        ";
        $stmtRec = $pdo->prepare($sqlRecargas);
        $stmtRec->execute([
            'aluno_id' => $alunoId,
            'resp_id'  => $respId,
            'data_ini' => $dataInicio,
            'data_fim' => $dataFim
        ]);
        $recargas = $stmtRec->fetchAll(\PDO::FETCH_ASSOC);

        // 5. Mescla todas as movimentações e ordena cronologicamente crescente (ASC)
        $movimentacoes = array_merge($pedidos, $balcao, $recargas);
        usort($movimentacoes, function($a, $b) {
            $cmp = strcmp($a['data_consumo'], $b['data_consumo']);
            if ($cmp !== 0) return $cmp;
            // Recargas antes de consumo na mesma data
            if ($a['tipo'] === 'recarga' && $b['tipo'] !== 'recarga') return -1;
            if ($b['tipo'] === 'recarga' && $a['tipo'] !== 'recarga') return 1;
            return strcmp($a['created_at'], $b['created_at']);
        });

        // 6. Cálculo do Saldo Inicial anterior ao período
        $saldoInicial = 0.0;
        if ($saldoApenasPeriodo) {
            $saldoInicial = 0.0;
        } else {
            // Busca última transação antes da data_inicio
            $sqlSaldoAnt = "
                SELECT saldo_resultante 
                FROM transacoes_carteira 
                WHERE responsavel_id = :resp_id 
                  AND created_at < :data_ini_ts
                ORDER BY created_at DESC, id DESC 
                LIMIT 1
            ";
            $stmtAnt = $pdo->prepare($sqlSaldoAnt);
            $stmtAnt->execute([
                'resp_id'     => $respId,
                'data_ini_ts' => $dataInicio . ' 00:00:00'
            ]);
            $rowAnt = $stmtAnt->fetch(\PDO::FETCH_ASSOC);
            if ($rowAnt && isset($rowAnt['saldo_resultante'])) {
                $saldoInicial = (float)$rowAnt['saldo_resultante'];
            } else {
                // Cálculo retroativo seguro
                $sqlMovPosterior = "
                    SELECT 
                        COALESCE(SUM(CASE WHEN tipo_transacao IN ('recarga_pix', 'recarga_dinheiro', 'estorno_cancelamento', 'estorno_balcao', 'ajuste_manual') THEN valor ELSE 0 END), 0) AS total_cred,
                        COALESCE(SUM(CASE WHEN tipo_transacao IN ('debito_pedido', 'debito_contestacao_med') THEN valor ELSE 0 END), 0) AS total_deb
                    FROM transacoes_carteira
                    WHERE responsavel_id = :resp_id
                      AND created_at >= :data_ini_ts
                ";
                $stmtPost = $pdo->prepare($sqlMovPosterior);
                $stmtPost->execute([
                    'resp_id'     => $respId,
                    'data_ini_ts' => $dataInicio . ' 00:00:00'
                ]);
                $rowPost = $stmtPost->fetch(\PDO::FETCH_ASSOC);
                if ($rowPost) {
                    $saldoInicial = max(0.0, $saldoAtual - (float)$rowPost['total_cred'] + (float)$rowPost['total_deb']);
                } else {
                    $saldoInicial = $saldoAtual;
                }
            }
        }

        // 7. Cálculo do Saldo Progressivo Linha a Linha e Totais
        $saldoProg = $saldoInicial;
        $totalRecargas = 0.0;
        $totalGasto = 0.0;
        $totalLanches = 0;

        $linhas = [];
        foreach ($movimentacoes as $m) {
            $valor = (float)$m['valor_total'];
            $ehCredito = ($m['tipo'] === 'recarga' || $m['forma_pagamento'] === 'Estorno');

            if ($ehCredito) {
                $saldoProg += $valor;
                $totalRecargas += $valor;
            } else {
                if ($m['forma_pagamento'] === 'Saldo') {
                    $saldoProg -= $valor;
                }
                $totalGasto += $valor;
                $totalLanches += (int)($m['quantidade'] ?? 1);
            }

            $linhas[] = [
                'data'            => $m['data_consumo'],
                'data_formatada'  => date('d/m/Y', strtotime($m['data_consumo'])),
                'created_at'      => $m['created_at'],
                'tipo'            => $m['tipo'],
                'descricao'       => mb_strtoupper($m['produto_nome'], 'UTF-8'),
                'quantidade'      => $m['quantidade'],
                'valor_unitario'  => $m['preco_unitario'],
                'valor_total'     => $valor,
                'forma_pagamento' => $m['forma_pagamento'],
                'saldo'           => $saldoProg,
                'canal'           => $m['canal'] ?? 'Cantina Web',
                'status'          => $m['status'] ?? 'concluida'
            ];
        }

        // Se a opção da aba Leiaute "Emitir apenas saldo do Aluno" estiver ativa,
        // suprime a listagem analítica detalhada dos itens conforme guideline oficial
        if ($apenasSaldo) {
            $linhas = [];
        }

        // Listas legadas agrupadas para manter retrocompatibilidade com telas antigas
        $consumoAgrupado = array_merge($pedidos, $balcao);
        $totalRegistrosRecargas = count($recargas);
        $totalRegistrosConsumo  = count($consumoAgrupado);

        $totalPaginasRecargas = (int)ceil(max(1, $totalRegistrosRecargas) / $porPagina);
        $totalPaginasConsumo  = (int)ceil(max(1, $totalRegistrosConsumo) / $porPagina);

        $periodoFmt = date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim));

        return [
            'aluno'                    => $aluno,
            'periodo'                  => $periodoFmt,
            'data_inicio'              => $dataInicio,
            'data_fim'                 => $dataFim,
            'apenas_saldo'             => $apenasSaldo,
            'saldo_apenas_periodo'     => $saldoApenasPeriodo,
            'saldo_inicial'            => $saldoInicial,
            'saldo_atual'              => $saldoAtual,
            'saldo_final'              => $saldoProg,
            'total_recargas'           => $totalRecargas,
            'total_consumo'            => $totalGasto,
            'total_gasto'              => $totalGasto,
            'total_lanches'            => $totalLanches,
            'linhas'                   => $linhas,
            'movimentacoes'            => $linhas,
            'lancamentos'              => $linhas,
            'recargas'                 => $recargas,
            'consumo'                  => $consumoAgrupado,
            'pagina_recargas'          => $paginaRecargas,
            'pagina_consumo'           => $paginaConsumo,
            'total_paginas_recargas'   => $totalPaginasRecargas,
            'total_paginas_consumo'    => $totalPaginasConsumo,
            'total_registros_recargas' => $totalRegistrosRecargas,
            'total_registros_consumo'  => $totalRegistrosConsumo,
            'itens_por_pagina'         => $porPagina
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

        // Consulta unificada: total de compras, total de lanches e valor total
        // calculados a partir dos itens reais em itens_venda_balcao (INNER JOIN),
        // garantindo consistência com o relatório individual por colaborador.
        $sqlColabs = "
            SELECT 
                c.id AS colaborador_id,
                c.matricula_funcional,
                c.nome AS colaborador_nome,
                c.cargo,
                c.foto_url,
                COUNT(DISTINCT vb.id) AS total_compras,
                COALESCE(SUM(ivb.subtotal), 0.00) AS total_desconto,
                COALESCE(SUM(ivb.quantidade), 0) AS total_lanches
            FROM colaboradores c
            INNER JOIN vendas_balcao vb ON vb.colaborador_id = c.id
            INNER JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id
            WHERE vb.tipo_venda = 'desconto_folha'
              AND YEAR(vb.created_at) = :ano
              AND MONTH(vb.created_at) = :mes
            GROUP BY c.id, c.matricula_funcional, c.nome, c.cargo, c.foto_url
            ORDER BY c.nome ASC
        ";
        $stmt = $pdo->prepare($sqlColabs);
        $stmt->execute(['ano' => $ano, 'mes' => $mes]);
        $colaboradores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalDescontoGeral = 0.0;
        $totalLanchesGeral = 0;
        $maiorConsumo = 0.0;
        $topColaborador = '-';

        foreach ($colaboradores as &$c) {
            $c['total_lanches'] = (int)$c['total_lanches'];

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

    /**
     * Consolida dados detalhados para o Demonstrativo Individual de Desconto em Folha
     */
    private function obterDadosDescontoFolhaIndividual(int $colaboradorId, string $competencia): ?array
    {
        $colaborador = Colaborador::buscarPorId($colaboradorId);
        if (!$colaborador) {
            return null;
        }

        [$ano, $mes] = explode('-', $competencia);
        $ano = (int)$ano;
        $mes = (int)$mes;

        $pdo = \App\Core\Model::getPdo();
        $sql = "
            SELECT 
                vb.id AS venda_id,
                vb.created_at,
                vb.valor_total AS venda_valor_total,
                sc.turno,
                u.nome AS operador_nome,
                ivb.id AS item_id,
                ivb.quantidade,
                ivb.preco_unitario,
                ivb.subtotal,
                p.descricao AS produto_nome,
                p.codigo_interno AS produto_codigo
            FROM vendas_balcao vb
            INNER JOIN sessoes_caixa sc ON sc.id = vb.sessao_caixa_id
            INNER JOIN usuarios u ON u.id = vb.operador_id
            INNER JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id
            INNER JOIN produtos p ON p.id = ivb.produto_id
            WHERE vb.colaborador_id = :colab_id
              AND vb.tipo_venda = 'desconto_folha'
              AND YEAR(vb.created_at) = :ano
              AND MONTH(vb.created_at) = :mes
            ORDER BY vb.created_at ASC, vb.id ASC, ivb.id ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['colab_id' => $colaboradorId, 'ano' => $ano, 'mes' => $mes]);
        $itens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $totalGeral = 0.0;
        $totalItens = 0;
        $vendasIds = [];

        foreach ($itens as $item) {
            $totalGeral += (float)$item['subtotal'];
            $totalItens += (int)$item['quantidade'];
            $vendasIds[$item['venda_id']] = true;
        }

        $mesesNome = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        $competenciaExtensa = ($mesesNome[$mes] ?? 'Mês ' . $mes) . ' de ' . $ano;
        $usuario = Auth::user();

        return [
            'colaborador'       => $colaborador,
            'competencia'       => $competenciaExtensa,
            'competencia_chave' => $competencia,
            'operador'          => $usuario['nome'] ?? 'Administração Geral',
            'emissao'           => date('d/m/Y H:i:s'),
            'baseUrl'           => defined('BASE_URL') ? BASE_URL : '',
            'itens'             => $itens,
            'totais'            => [
                'total_desconto' => $totalGeral,
                'total_lanches'  => $totalItens,
                'total_compras'  => count($vendasIds)
            ]
        ];
    }

    /**
     * Consolida dados analíticos avançados de vendas com suporte a todos os filtros e leiautes
     * Conforme docs/alterações sistema guideline/Filtros de vendas.pdf e relatorio de vendas por período.pdf
     */
    private function obterDadosVendasAvancadas(array $filtros): array
    {
        $pdo = \App\Core\Model::getPdo();

        $dataInicio = $filtros['data_inicio'] ?? date('Y-m-d');
        $dataFim    = $filtros['data_fim'] ?? date('Y-m-d');
        $turno      = $filtros['turno'] ?? '';
        $categoriaId = (int)($filtros['categoria_id'] ?? 0);
        $tipo       = $filtros['tipo'] ?? '';
        $incluirEstornos = ($filtros['incluir_estornos'] ?? 'nao') === 'sim';
        $agruparPeriodo  = ($filtros['agrupar_periodo'] ?? 'sim') === 'sim';
        $agruparAlimento = ($filtros['agrupar_alimento'] ?? 'sim') === 'sim';
        $ordenarAlimentos = ($filtros['ordenar_alimentos'] ?? 'mais_vendidos') === 'menos_vendidos' ? 'ASC' : 'DESC';
        $agruparTipoPessoa = ($filtros['agrupar_tipo_pessoa'] ?? 'nao') === 'sim';
        $emitirVendasAluno = ($filtros['emitir_vendas_aluno'] ?? 'nao') === 'sim';
        $ordenarPor = ($filtros['ordenar_por'] ?? 'maior_consumo') === 'menor_consumo' ? 'ASC' : 'DESC';

        $params = [
            'ini1' => $dataInicio, 'fim1' => $dataFim,
            'ini2' => $dataInicio, 'fim2' => $dataFim
        ];

        // Filtro de status
        $statusPedidos = $incluirEstornos 
            ? "('agendado', 'editado', 'entregue', 'estornado', 'cancelado')"
            : "('agendado', 'editado', 'entregue')";

        $statusBalcao = $incluirEstornos 
            ? "('concluida', 'estornada')"
            : "('concluida')";

        // Filtro de turno
        $whereTurno1 = "";
        $whereTurno2 = "";
        if (!empty($turno) && in_array($turno, ['matutino', 'vespertino'], true)) {
            $whereTurno1 = " AND p.turno_consumo = :turno1";
            $whereTurno2 = " AND sc.turno = :turno2";
            $params['turno1'] = $turno;
            $params['turno2'] = $turno;
        }

        // Filtro de categoria
        $whereCat1 = "";
        $whereCat2 = "";
        if ($categoriaId > 0) {
            $whereCat1 = " AND pr.categoria_id = :cat1";
            $whereCat2 = " AND pr2.categoria_id = :cat2";
            $params['cat1'] = $categoriaId;
            $params['cat2'] = $categoriaId;
        }

        // Filtro de tipo de produto
        $whereTipo1 = "";
        $whereTipo2 = "";
        if (!empty($tipo)) {
            $whereTipo1 = " AND pr.tipo = :tipo1";
            $whereTipo2 = " AND pr2.tipo = :tipo2";
            $params['tipo1'] = $tipo;
            $params['tipo2'] = $tipo;
        }

        // Consulta unificada de todas as movimentações de venda de produtos
        $sql = "
            SELECT 
                'online' AS origem,
                p.id AS venda_id,
                p.data_consumo AS data_venda,
                TIME(p.created_at) AS hora_venda,
                p.turno_consumo AS turno,
                p.status AS status_venda,
                'Saldo Cantina Web' AS forma_pagamento,
                'Aluno' AS tipo_pessoa,
                a.id AS aluno_id,
                COALESCE(a.nome, 'Aluno') AS pessoa_nome,
                COALESCE(a.matricula, '-') AS pessoa_identificador,
                COALESCE(t.serie, '-') AS pessoa_serie,
                COALESCE(t.nome_turma, '-') AS pessoa_turma,
                COALESCE(p.motivo_estorno, '') AS observacao,
                ip.produto_id,
                pr.codigo_interno,
                COALESCE(NULLIF(TRIM(pr.descricao), ''), 'Item Sem Descrição') AS produto_nome,
                COALESCE(c.nome, 'Geral') AS categoria_nome,
                pr.tipo AS produto_tipo,
                ip.quantidade,
                ip.preco_unitario,
                ip.subtotal
            FROM itens_pedido ip
            INNER JOIN pedidos p ON p.id = ip.pedido_id
            LEFT JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas t ON t.id = a.turma_id
            INNER JOIN produtos pr ON pr.id = ip.produto_id
            LEFT JOIN categorias c ON c.id = pr.categoria_id
            WHERE p.data_consumo BETWEEN :ini1 AND :fim1
              AND p.status IN {$statusPedidos}
              {$whereTurno1}
              {$whereCat1}
              {$whereTipo1}

            UNION ALL

            SELECT 
                'balcao' AS origem,
                vb.id AS venda_id,
                DATE(vb.created_at) AS data_venda,
                TIME(vb.created_at) AS hora_venda,
                sc.turno AS turno,
                vb.status AS status_venda,
                CASE 
                    WHEN vb.tipo_venda = 'desconto_folha' THEN 'Desconto em Folha'
                    WHEN vb.tipo_venda = 'pix' THEN 'Pix Balcão'
                    WHEN vb.tipo_venda = 'dinheiro' THEN 'Dinheiro Balcão'
                    ELSE 'Balcão'
                END AS forma_pagamento,
                CASE 
                    WHEN vb.colaborador_id IS NOT NULL THEN 'Funcionário'
                    WHEN vb.aluno_id IS NOT NULL THEN 'Aluno'
                    ELSE 'Consumidor Avulso'
                END AS tipo_pessoa,
                vb.aluno_id,
                CASE 
                    WHEN vb.colaborador_id IS NOT NULL THEN col.nome
                    WHEN vb.aluno_id IS NOT NULL THEN a2.nome
                    ELSE 'Consumidor Avulso'
                END AS pessoa_nome,
                CASE 
                    WHEN vb.colaborador_id IS NOT NULL THEN col.matricula_funcional
                    WHEN vb.aluno_id IS NOT NULL THEN a2.matricula
                    ELSE '-'
                END AS pessoa_identificador,
                CASE 
                    WHEN vb.aluno_id IS NOT NULL THEN t2.serie
                    ELSE '-'
                END AS pessoa_serie,
                CASE 
                    WHEN vb.aluno_id IS NOT NULL THEN t2.nome_turma
                    ELSE '-'
                END AS pessoa_turma,
                COALESCE(vb.motivo_estorno, '') AS observacao,
                ivb.produto_id,
                pr2.codigo_interno,
                COALESCE(NULLIF(TRIM(pr2.descricao), ''), 'Item Sem Descrição') AS produto_nome,
                COALESCE(c2.nome, 'Geral') AS categoria_nome,
                pr2.tipo AS produto_tipo,
                ivb.quantidade,
                ivb.preco_unitario,
                ivb.subtotal
            FROM itens_venda_balcao ivb
            INNER JOIN vendas_balcao vb ON vb.id = ivb.venda_balcao_id
            INNER JOIN sessoes_caixa sc ON sc.id = vb.sessao_caixa_id
            LEFT JOIN colaboradores col ON col.id = vb.colaborador_id
            LEFT JOIN alunos a2 ON a2.id = vb.aluno_id
            LEFT JOIN turmas t2 ON t2.id = a2.turma_id
            INNER JOIN produtos pr2 ON pr2.id = ivb.produto_id
            LEFT JOIN categorias c2 ON c2.id = pr2.categoria_id
            WHERE DATE(vb.created_at) BETWEEN :ini2 AND :fim2
              AND vb.status IN {$statusBalcao}
              {$whereTurno2}
              {$whereCat2}
              {$whereTipo2}

            ORDER BY data_venda ASC, hora_venda ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registrosBrutos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Processamento analítico e agregação
        $faturamentoGeral = 0.0;
        $quantidadeGeral  = 0;

        // Estrutura de agrupamento por dia conforme relatorio de vendas por período.pdf
        $diasAgrupados = [];
        $alimentosConsolidados = [];
        $alunosConsolidados = [];
        $gruposTipoPessoa = [
            'Aluno' => ['titulo' => 'Alunos', 'itens' => [], 'total' => 0.0, 'quantidade' => 0],
            'Funcionário' => ['titulo' => 'Funcionários / Colaboradores', 'itens' => [], 'total' => 0.0, 'quantidade' => 0],
            'Consumidor Avulso' => ['titulo' => 'Consumidores Avulsos', 'itens' => [], 'total' => 0.0, 'quantidade' => 0]
        ];

        foreach ($registrosBrutos as $reg) {
            $dataVenda = $reg['data_venda'];
            $dataFmt   = date('d/m/Y', strtotime($dataVenda));
            $prodId    = (int)$reg['produto_id'];
            $qtd       = (int)$reg['quantidade'];
            $subtotal  = (float)$reg['subtotal'];
            $tipoPessoa = $reg['tipo_pessoa'] ?? 'Aluno';

            $faturamentoGeral += $subtotal;
            $quantidadeGeral  += $qtd;

            // 1. Agrupamento por dia (para o relatório oficial)
            if (!isset($diasAgrupados[$dataVenda])) {
                $diasAgrupados[$dataVenda] = [
                    'data'             => $dataVenda,
                    'data_fmt'         => $dataFmt,
                    'alimentos'        => [],
                    'total_dia'        => 0.0,
                    'subtotal'         => 0.0,
                    'qtd_dia'          => 0,
                    'quantidade_total' => 0
                ];
            }

            if ($agruparAlimento) {
                if (!isset($diasAgrupados[$dataVenda]['alimentos'][$prodId])) {
                    $diasAgrupados[$dataVenda]['alimentos'][$prodId] = [
                        'produto_id'       => $prodId,
                        'codigo_interno'   => $reg['codigo_interno'],
                        'descricao'        => mb_strtoupper($reg['produto_nome'], 'UTF-8'),
                        'produto_nome'     => $reg['produto_nome'],
                        'categoria_nome'   => $reg['categoria_nome'],
                        'quantidade'       => 0,
                        'quantidade_total' => 0,
                        'subtotal'         => 0.0,
                        'valor_total'      => 0.0,
                        'formas_pagamento' => [],
                        'observacoes'      => []
                    ];
                }
                $diasAgrupados[$dataVenda]['alimentos'][$prodId]['quantidade']       += $qtd;
                $diasAgrupados[$dataVenda]['alimentos'][$prodId]['quantidade_total'] += $qtd;
                $diasAgrupados[$dataVenda]['alimentos'][$prodId]['subtotal']         += $subtotal;
                $diasAgrupados[$dataVenda]['alimentos'][$prodId]['valor_total']      += $subtotal;
                if (!empty($reg['forma_pagamento']) && !in_array($reg['forma_pagamento'], $diasAgrupados[$dataVenda]['alimentos'][$prodId]['formas_pagamento'], true)) {
                    $diasAgrupados[$dataVenda]['alimentos'][$prodId]['formas_pagamento'][] = $reg['forma_pagamento'];
                }
                if (!empty($reg['observacao']) && !in_array($reg['observacao'], $diasAgrupados[$dataVenda]['alimentos'][$prodId]['observacoes'], true)) {
                    $diasAgrupados[$dataVenda]['alimentos'][$prodId]['observacoes'][] = $reg['observacao'];
                }
            } else {
                // Não agrupado por alimento dentro do dia: mantém linha individual
                $diasAgrupados[$dataVenda]['alimentos'][] = [
                    'produto_id'       => $prodId,
                    'codigo_interno'   => $reg['codigo_interno'],
                    'descricao'        => mb_strtoupper($reg['produto_nome'], 'UTF-8'),
                    'produto_nome'     => $reg['produto_nome'],
                    'categoria_nome'   => $reg['categoria_nome'],
                    'quantidade'       => $qtd,
                    'quantidade_total' => $qtd,
                    'preco_unitario'   => (float)$reg['preco_unitario'],
                    'subtotal'         => $subtotal,
                    'valor_total'      => $subtotal,
                    'hora_venda'       => substr($reg['hora_venda'], 0, 5),
                    'forma_pagamento'  => $reg['forma_pagamento'],
                    'tipo_pessoa'      => $tipoPessoa,
                    'pessoa_nome'      => $reg['pessoa_nome'],
                    'observacao'       => $reg['observacao']
                ];
            }

            $diasAgrupados[$dataVenda]['total_dia']        += $subtotal;
            $diasAgrupados[$dataVenda]['subtotal']         += $subtotal;
            $diasAgrupados[$dataVenda]['qtd_dia']          += $qtd;
            $diasAgrupados[$dataVenda]['quantidade_total'] += $qtd;

            // 2. Consolidação geral de alimentos no período
            if (!isset($alimentosConsolidados[$prodId])) {
                $alimentosConsolidados[$prodId] = [
                    'produto_id'       => $prodId,
                    'codigo'           => $reg['codigo_interno'],
                    'codigo_interno'   => $reg['codigo_interno'],
                    'descricao'        => mb_strtoupper($reg['produto_nome'], 'UTF-8'),
                    'produto_nome'     => $reg['produto_nome'],
                    'categoria_nome'   => $reg['categoria_nome'],
                    'tipo'             => $reg['produto_tipo'],
                    'quantidade_total' => 0,
                    'quantidade'       => 0,
                    'faturamento_total'=> 0.0,
                    'subtotal'         => 0.0,
                    'valor_total'      => 0.0,
                    'data_fmt'         => $dataFmt,
                    'forma_pagamento'  => $reg['forma_pagamento'],
                    'observacao'       => $reg['observacao']
                ];
            }
            $alimentosConsolidados[$prodId]['quantidade_total'] += $qtd;
            $alimentosConsolidados[$prodId]['quantidade']       += $qtd;
            $alimentosConsolidados[$prodId]['faturamento_total']+= $subtotal;
            $alimentosConsolidados[$prodId]['subtotal']         += $subtotal;
            $alimentosConsolidados[$prodId]['valor_total']      += $subtotal;

            // 3. Agrupamento por tipo de pessoa
            if (!isset($gruposTipoPessoa[$tipoPessoa])) {
                $gruposTipoPessoa[$tipoPessoa] = [
                    'titulo' => $tipoPessoa,
                    'itens'  => [],
                    'total'  => 0.0,
                    'quantidade' => 0
                ];
            }
            $gruposTipoPessoa[$tipoPessoa]['total']      += $subtotal;
            $gruposTipoPessoa[$tipoPessoa]['quantidade'] += $qtd;

            // 4. Consolidação por aluno (quando aplicável)
            if ($tipoPessoa === 'Aluno' && !empty($reg['pessoa_identificador'])) {
                $alunoKey = $reg['pessoa_identificador'];
                if (!isset($alunosConsolidados[$alunoKey])) {
                    $serie = trim($reg['pessoa_serie'] ?? '');
                    $turma = trim($reg['pessoa_turma'] ?? '');
                    $serieTurma = trim("{$serie} {$turma}");
                    if (empty($serieTurma) || $serieTurma === '- -' || $serieTurma === '-') {
                        $serieTurma = 'Turma Não Informada';
                    }

                    $alunosConsolidados[$alunoKey] = [
                        'aluno_id'      => (int)$reg['aluno_id'],
                        'matricula'     => $reg['pessoa_identificador'],
                        'nome'          => $reg['pessoa_nome'],
                        'serie'         => $serie,
                        'turma'         => $turma,
                        'serie_turma'   => $serieTurma,
                        'quantidade'    => 0,
                        'total_consumo' => 0.0,
                        'compras'       => []
                    ];
                }
                $alunosConsolidados[$alunoKey]['quantidade']    += $qtd;
                $alunosConsolidados[$alunoKey]['total_consumo'] += $subtotal;
                $alunosConsolidados[$alunoKey]['compras'][] = [
                    'data_fmt'        => $dataFmt,
                    'hora_venda'      => substr($reg['hora_venda'] ?? '', 0, 5),
                    'produto_nome'    => $reg['produto_nome'],
                    'quantidade'      => $qtd,
                    'preco_unitario'  => (float)$reg['preco_unitario'],
                    'subtotal'        => $subtotal,
                    'forma_pagamento' => $reg['forma_pagamento'] ?? 'Saldo'
                ];
            }
        }

        // Ordenação dos alimentos de cada dia e cálculo de preço unitário
        $itensPlano = [];
        foreach ($diasAgrupados as $dataKey => &$dia) {
            if ($agruparAlimento) {
                // Transforma o mapa de IDs em array indexado
                $listaAlimentosDia = array_values($dia['alimentos']);
                foreach ($listaAlimentosDia as &$alDia) {
                    $alDia['preco_unitario'] = ($alDia['quantidade'] > 0) ? ($alDia['subtotal'] / $alDia['quantidade']) : 0.0;
                    $alDia['forma_pagamento']= implode(', ', $alDia['formas_pagamento'] ?? []);
                    $alDia['observacao']     = implode(', ', array_filter($alDia['observacoes'] ?? []));
                    $alDia['data_fmt']       = $dia['data_fmt'];
                    $alDia['data_venda']     = $dia['data'];
                }
                unset($alDia);

                // Ordenação conforme o parâmetro: mais vendidos ou menos vendidos
                usort($listaAlimentosDia, function($a, $b) use ($ordenarAlimentos) {
                    $cmp = ($ordenarAlimentos === 'DESC') 
                        ? ($b['quantidade'] <=> $a['quantidade']) 
                        : ($a['quantidade'] <=> $b['quantidade']);
                    if ($cmp !== 0) return $cmp;
                    return ($ordenarAlimentos === 'DESC') 
                        ? ($b['subtotal'] <=> $a['subtotal']) 
                        : ($a['subtotal'] <=> $b['subtotal']);
                });

                $dia['alimentos'] = $listaAlimentosDia;

                foreach ($listaAlimentosDia as $itemAl) {
                    $itensPlano[] = $itemAl;
                }
            } else {
                foreach ($dia['alimentos'] as $itemAl) {
                    $itemAl['data_fmt'] = $dia['data_fmt'];
                    $itemAl['data_venda'] = $dia['data'];
                    $itensPlano[] = $itemAl;
                }
            }
        }
        unset($dia);

        // Preço médio e classificação Curva ABC para a consolidação geral
        $acumuladoPercentual = 0.0;
        $listaAlimentosGeral = array_values($alimentosConsolidados);
        usort($listaAlimentosGeral, function($a, $b) use ($ordenarAlimentos) {
            $cmp = ($ordenarAlimentos === 'DESC') 
                ? ($b['quantidade_total'] <=> $a['quantidade_total']) 
                : ($a['quantidade_total'] <=> $b['quantidade_total']);
            if ($cmp !== 0) return $cmp;
            return ($ordenarAlimentos === 'DESC') 
                ? ($b['faturamento_total'] <=> $a['faturamento_total']) 
                : ($a['faturamento_total'] <=> $b['faturamento_total']);
        });

        foreach ($listaAlimentosGeral as &$itemG) {
            $fat = (float)$itemG['faturamento_total'];
            $qtd = (int)$itemG['quantidade_total'];
            $itemG['preco_medio'] = ($qtd > 0) ? ($fat / $qtd) : 0.0;
            $itemG['preco_unitario'] = $itemG['preco_medio'];

            $partPercent = ($faturamentoGeral > 0) ? ($fat / $faturamentoGeral) * 100.0 : 0.0;
            $acumuladoPercentual += $partPercent;

            if ($acumuladoPercentual <= 70.01) {
                $itemG['classe_abc'] = 'A';
            } elseif ($acumuladoPercentual <= 90.01) {
                $itemG['classe_abc'] = 'B';
            } else {
                $itemG['classe_abc'] = 'C';
            }
            $itemG['participacao_percentual'] = $partPercent;
        }
        unset($itemG);

        // Ordenação dos alunos (por consumo ou alfabética por nome)
        $ordenarConsumoAluno = ($filtros['ordenar_consumo_aluno'] ?? 'nao') === 'sim';
        $listaAlunos = array_values($alunosConsolidados);
        if ($ordenarConsumoAluno) {
            usort($listaAlunos, function($a, $b) use ($ordenarPor) {
                return ($ordenarPor === 'DESC') 
                    ? ($b['total_consumo'] <=> $a['total_consumo']) 
                    : ($a['total_consumo'] <=> $b['total_consumo']);
            });
        } else {
            usort($listaAlunos, function($a, $b) {
                return strcoll(mb_strtolower($a['nome'], 'UTF-8'), mb_strtolower($b['nome'], 'UTF-8'));
            });
        }

        // Agrupamento por série/turma
        $turmasAgrupadas = [];
        foreach ($listaAlunos as $al) {
            $turmaKey = !empty($al['serie_turma']) ? $al['serie_turma'] : 'Turma Não Informada';
            if (!isset($turmasAgrupadas[$turmaKey])) {
                $turmasAgrupadas[$turmaKey] = [
                    'turma_nome'       => $turmaKey,
                    'serie'            => $al['serie'],
                    'turma'            => $al['turma'],
                    'alunos'           => [],
                    'total_quantidade' => 0,
                    'total_consumo'    => 0.0
                ];
            }
            $turmasAgrupadas[$turmaKey]['alunos'][] = $al;
            $turmasAgrupadas[$turmaKey]['total_quantidade'] += (int)$al['quantidade'];
            $turmasAgrupadas[$turmaKey]['total_consumo']    += (float)$al['total_consumo'];
        }
        ksort($turmasAgrupadas, SORT_NATURAL | SORT_FLAG_CASE);

        $periodoFmt = date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim));
        $periodoOficial = date('d/m/Y', strtotime($dataInicio)) . ' - ' . date('d/m/Y', strtotime($dataFim));

        $catNome = 'Todas as Categorias';
        if ($categoriaId > 0) {
            $catObj = Categoria::buscarPorId($categoriaId);
            if ($catObj) {
                $catNome = $catObj['nome'];
            }
        }

        return [
            'periodo'                  => $periodoFmt,
            'periodo_oficial'          => $periodoOficial,
            'data_inicio'              => $dataInicio,
            'data_fim'                 => $dataFim,
            'turno'                    => !empty($turno) ? ucfirst($turno) : 'Todos os Turnos',
            'categoria'                => $catNome,
            'filtros'                  => $filtros,
            'dias'                     => $diasAgrupados,
            'itens'                    => !empty($itensPlano) ? $itensPlano : $listaAlimentosGeral,
            'alimentos_consolidados'   => $listaAlimentosGeral,
            'grupos_tipo_pessoa'       => $gruposTipoPessoa,
            'alunos'                   => $listaAlunos,
            'turmas'                   => array_values($turmasAgrupadas),
            'totais'                   => [
                'faturamento_total'    => $faturamentoGeral,
                'quantidade_total'     => $quantidadeGeral,
                'ticket_medio'         => ($quantidadeGeral > 0) ? ($faturamentoGeral / $quantidadeGeral) : 0.0
            ]
        ];
    }

    /**
     * Tela de Opções de Layout e Filtros de Movimentações de Estoque (Fase 23)
     * Conforme docs/alterações sistema guideline/filtro movimentação estoque.pdf
     */
    public function estoqueFiltros(): void
    {
        $categorias = Categoria::listarTodas();
        $produtos = \App\Models\Produto::listarTodos();

        $dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $dataFim    = $_GET['data_fim'] ?? date('Y-m-d');
        $categoriaId = (int)($_GET['categoria_id'] ?? 0);
        $produtoId   = (int)($_GET['produto_id'] ?? 0);
        $tipoMov     = trim($_GET['tipo_movimentacao'] ?? '');
        $subtipoEntrada = trim($_GET['subtipo_entrada'] ?? '');
        $subtipoSaida   = trim($_GET['subtipo_saida'] ?? '');
        $motivoPerda = trim($_GET['motivo_perda'] ?? '');

        $paramFormato      = $_GET['formato_layout'] ?? $_GET['formato_leiaute'] ?? '';
        $formatoLayout     = in_array(strtolower($paramFormato), ['paisagem', 'retrato']) ? strtolower($paramFormato) : 'retrato';
        $agruparGrupo      = ($_GET['agrupar_grupo'] ?? 'ocultar') === 'mostrar' ? 'mostrar' : 'ocultar';
        $agruparProduto    = ($_GET['agrupar_produto'] ?? 'ocultar') === 'mostrar' ? 'mostrar' : 'ocultar';
        $quebraPagina      = ($_GET['quebra_pagina'] ?? 'ocultar') === 'mostrar' ? 'mostrar' : 'ocultar';

        $this->render('relatorios/estoque_filtros', [
            'title'             => 'Emissão de Movimentações de Estoque — Colégio Monteiro Lobato',
            'activeMenu'        => 'movimentacoes_estoque',
            'categorias'        => $categorias,
            'produtos'          => $produtos,
            'filtros'           => [
                'data_inicio'          => $dataInicio,
                'data_fim'             => $dataFim,
                'categoria_id'         => $categoriaId,
                'produto_id'           => $produtoId,
                'tipo_movimentacao'    => $tipoMov,
                'subtipo_entrada'      => $subtipoEntrada,
                'subtipo_saida'        => $subtipoSaida,
                'motivo_perda'         => $motivoPerda,
                'formato_layout'       => $formatoLayout,
                'formato_leiaute'      => $formatoLayout, // retrocompatibilidade
                'agrupar_centro_custo' => 'ocultar',
                'agrupar_grupo'        => $agruparGrupo,
                'agrupar_produto'      => $agruparProduto,
                'quebra_pagina'        => $quebraPagina,
            ]
        ], 'admin');
    }

    /**
     * Visualização em tela / Espelho HTML A4 de Movimentações de Estoque
     */
    public function estoqueMovimentacoes(): void
    {
        $dados = $this->obterDadosMovimentacoesEstoqueAvancadas($_GET);
        $dados['baseUrl'] = $this->getBaseUrl();

        echo PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dados);
        exit;
    }

    /**
     * Alias direto para visualização e impressão
     */
    public function estoqueMovimentacoesImprimir(): void
    {
        $this->estoqueMovimentacoes();
    }

    /**
     * Emissão do Relatório Oficial de Movimentações de Estoque para Impressão e Print to PDF Nativo
     */
    public function estoqueMovimentacoesPdf(): void
    {
        $dados = $this->obterDadosMovimentacoesEstoqueAvancadas($_GET);
        $dados['baseUrl'] = $this->getBaseUrl();

        $html = PdfService::renderView('relatorios/estoque_movimentacoes_pdf', $dados);
        echo $html;
        exit;
    }

    /**
     * Exportação analítica de movimentações de estoque em planilha CSV
     */
    public function estoqueMovimentacoesCsv(): void
    {
        $dados = $this->obterDadosMovimentacoesEstoqueAvancadas($_GET);

        $dataInicio = preg_replace('/[^0-9\-]/', '', $dados['data_inicio'] ?? date('Y-m-d'));
        $dataFim    = preg_replace('/[^0-9\-]/', '', $dados['data_fim'] ?? date('Y-m-d'));
        $filename   = "movimentacoes_estoque_{$dataInicio}_{$dataFim}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');

        // Emite BOM UTF-8 para correta interpretação de acentos no Excel
        fwrite($output, "\xEF\xBB\xBF");

        // Cabeçalho institucional do Colégio
        fputcsv($output, ['COLÉGIO MONTEIRO LOBATO — SISTEMA CANTINA WEB'], ';', '"', "\\");
        fputcsv($output, ['RELATÓRIO GERENCIAL DE MOVIMENTAÇÕES DE ESTOQUE'], ';', '"', "\\");
        fputcsv($output, ['PERÍODO:', $dados['periodo_oficial'], 'CENTRO DE CUSTO:', 'Cantina Geral', 'EMISSÃO:', date('d/m/Y H:i:s')], ';', '"', "\\");
        fputcsv($output, [], ';', '"', "\\");

        // Linha de cabeçalho das colunas
        fputcsv($output, [
            'Data',
            'Hora',
            'Centro de Custo',
            'Grupo / Categoria',
            'Código',
            'Produto',
            'Tipo de Movimentação',
            'Entrada (un)',
            'Saída (un)',
            'Operador Responsável',
            'Documento / NF',
            'Justificativa / Motivo'
        ], ';', '"', "\\");

        foreach ($dados['movimentacoes'] as $m) {
            fputcsv($output, self::sanitizarCsvLinha([
                $m['data_formatada'],
                $m['hora_formatada'],
                $m['centro_custo'],
                $m['categoria_nome'],
                $m['produto_codigo'],
                $m['produto_descricao'],
                $m['tipo_label'],
                $m['qtd_entrada'] > 0 ? $m['qtd_entrada'] : '',
                $m['qtd_saida'] > 0 ? $m['qtd_saida'] : '',
                $m['operador_nome'],
                $m['documento_ref'],
                $m['justificativa_completa']
            ]), ';', '"', "\\");
        }

        // Totais no rodapé da planilha
        fputcsv($output, [], ';', '"', "\\");
        fputcsv($output, self::sanitizarCsvLinha([
            'TOTAIS DO PERÍODO',
            '',
            '',
            '',
            '',
            '',
            '',
            $dados['totais']['total_entradas'],
            $dados['totais']['total_saidas'],
            'Balanço Líquido:',
            $dados['totais']['balanco_periodo'] . ' un',
            ''
        ]), ';', '"', "\\");

        fclose($output);
        exit;
    }

    /**
     * Processa e consolida as movimentações de estoque conforme filtros e layout
     * 
     * @param array $filtros Parâmetros de requisição ($_GET)
     * @return array Matriz estruturada com agrupamentos, subtotais e totais
     */
    public function obterDadosMovimentacoesEstoqueAvancadas(array $filtros = []): array
    {
        $dataInicio = $filtros['data_inicio'] ?? date('Y-m-01');
        $dataFim    = $filtros['data_fim'] ?? date('Y-m-d');

        if ($dataInicio > $dataFim) {
            $tmp = $dataInicio;
            $dataInicio = $dataFim;
            $dataFim = $tmp;
        }

        $paramFormato  = $filtros['formato_layout'] ?? $filtros['formato_leiaute'] ?? '';
        $formatoLayout = in_array(strtolower($paramFormato), ['paisagem', 'retrato']) ? strtolower($paramFormato) : 'retrato';
        $formatoLeiaute = $formatoLayout; // alias de compatibilidade
        $agruparGrupo   = ($filtros['agrupar_grupo'] ?? 'ocultar') === 'mostrar';
        $agruparProduto = ($filtros['agrupar_produto'] ?? 'ocultar') === 'mostrar';
        $quebraPagina   = ($filtros['quebra_pagina'] ?? 'ocultar') === 'mostrar';

        $categoriaId = (int)($filtros['categoria_id'] ?? 0);
        $produtoId   = (int)($filtros['produto_id'] ?? 0);
        $tipoMov     = trim($filtros['tipo_movimentacao'] ?? '');
        $subtipoEntrada = trim($filtros['subtipo_entrada'] ?? '');
        $subtipoSaida   = trim($filtros['subtipo_saida'] ?? '');
        $motivoPerda = trim($filtros['motivo_perda'] ?? '');

        // Normalização e retrocompatibilidade com links e requisições legadas
        if ($tipoMov === 'entrada_fornecedor') {
            $tipoMov = 'entrada';
            $subtipoEntrada = $subtipoEntrada ?: 'entrada_salgados';
        } elseif ($tipoMov === 'aproveitamento_sobra') {
            $tipoMov = 'entrada';
            $subtipoEntrada = $subtipoEntrada ?: 'aproveitamento_sobra';
        } elseif ($tipoMov === 'saida_venda_avulsa') {
            $tipoMov = 'saida';
            $subtipoSaida = $subtipoSaida ?: 'saida_venda_avulsa';
        } elseif ($tipoMov === 'saida_encomenda') {
            $tipoMov = 'saida';
            $subtipoSaida = $subtipoSaida ?: 'saida_encomenda';
        } elseif ($tipoMov === 'entrada_estorno') {
            $tipoMov = 'estorno';
        }

        $pdo = \App\Core\Model::getPdo();

        $sql = "
            SELECT 
                em.id,
                em.produto_id,
                em.tipo_movimentacao,
                em.quantidade,
                em.motivo_perda,
                em.nota_fiscal_id,
                em.operador_id,
                em.justificativa,
                em.created_at,
                p.codigo_interno AS produto_codigo,
                p.descricao AS produto_descricao,
                p.tipo AS produto_tipo,
                p.estoque_atual AS produto_estoque_atual,
                c.id AS categoria_id,
                c.nome AS categoria_nome,
                c.ordem_exibicao AS categoria_ordem,
                u.nome AS operador_nome,
                u.usuario AS operador_usuario,
                nf.numero_nota,
                nf.razao_social_emitente
            FROM estoque_movimentacoes em
            INNER JOIN produtos p ON p.id = em.produto_id
            INNER JOIN categorias c ON c.id = p.categoria_id
            INNER JOIN usuarios u ON u.id = em.operador_id
            LEFT JOIN notas_fiscais_xml nf ON nf.id = em.nota_fiscal_id
            WHERE DATE(em.created_at) >= :data_inicio 
              AND DATE(em.created_at) <= :data_fim
        ";

        $params = [
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim
        ];

        // Regra de negócio: Exclui permanentemente movimentações originadas por NF-e (XML descontinuado)
        $sql .= " AND em.tipo_movimentacao != 'entrada_xml'";

        if ($categoriaId > 0) {
            $sql .= " AND p.categoria_id = :categoria_id";
            $params['categoria_id'] = $categoriaId;
        }

        if ($produtoId > 0) {
            $sql .= " AND em.produto_id = :produto_id";
            $params['produto_id'] = $produtoId;
        }

        if ($tipoMov === 'entrada') {
            if ($subtipoEntrada === 'entrada_salgados') {
                $sql .= " AND em.tipo_movimentacao = 'entrada_fornecedor' AND p.tipo = 'salgado_diario'";
            } elseif ($subtipoEntrada === 'entrada_manual') {
                $sql .= " AND em.tipo_movimentacao = 'entrada_fornecedor' AND p.tipo != 'salgado_diario'";
            } elseif ($subtipoEntrada === 'aproveitamento_sobra') {
                $sql .= " AND em.tipo_movimentacao = 'aproveitamento_sobra'";
            } else {
                $sql .= " AND em.tipo_movimentacao IN ('entrada_fornecedor', 'aproveitamento_sobra')";
            }
        } elseif ($tipoMov === 'estorno') {
            $sql .= " AND em.tipo_movimentacao = 'entrada_estorno'";
        } elseif ($tipoMov === 'saida') {
            if ($subtipoSaida === 'saida_venda_avulsa') {
                $sql .= " AND em.tipo_movimentacao = 'saida_venda_avulsa'";
            } elseif ($subtipoSaida === 'saida_encomenda') {
                $sql .= " AND em.tipo_movimentacao = 'saida_encomenda'";
            } else {
                $sql .= " AND em.tipo_movimentacao IN ('saida_venda_avulsa', 'saida_encomenda')";
            }
        } elseif ($tipoMov === 'saida_perda') {
            $sql .= " AND em.tipo_movimentacao = 'saida_perda'";
            if (!empty($motivoPerda)) {
                $sql .= " AND em.motivo_perda = :motivo_perda";
                $params['motivo_perda'] = $motivoPerda;
            }
        }

        // Ordenação inteligente conforme opções de agrupamento
        if ($agruparGrupo) {
            $sql .= " ORDER BY c.ordem_exibicao ASC, c.nome ASC, p.descricao ASC, em.created_at ASC";
        } elseif ($agruparProduto) {
            $sql .= " ORDER BY p.descricao ASC, em.created_at ASC";
        } else {
            $sql .= " ORDER BY em.created_at ASC, em.id ASC";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $linhas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $tiposEntrada = ['entrada_fornecedor', 'aproveitamento_sobra', 'entrada_estorno'];

        $labelsTipo = [
            'aproveitamento_sobra' => 'Aproveitamento de Sobra',
            'entrada_estorno'      => 'Reintegração por Estorno',
            'saida_venda_avulsa'   => 'Saída Venda Balcão (PDV)',
            'saida_encomenda'      => 'Saída Encomenda Cantina Web',
            'saida_perda'          => 'Baixa por Perda / Descarte'
        ];

        $labelsMotivo = [
            'avaria'          => 'Avaria / Descarte',
            'vencimento'      => 'Vencimento',
            'sobra_producao'  => 'Produto Não Consumido',
            'ajuste_contagem' => 'Ajuste de Contagem'
        ];

        $movimentacoes = [];
        $totalEntradas = 0;
        $totalSaidas   = 0;
        $totalPerdas   = 0;
        $totalVendas   = 0;

        foreach ($linhas as $row) {
            $isEntrada = in_array($row['tipo_movimentacao'], $tiposEntrada, true);
            $qtd = (int)$row['quantidade'];

            $qtdEntrada = $isEntrada ? $qtd : 0;
            $qtdSaida   = !$isEntrada ? $qtd : 0;

            if ($isEntrada) {
                $totalEntradas += $qtd;
            } else {
                $totalSaidas += $qtd;
                if ($row['tipo_movimentacao'] === 'saida_perda') {
                    $totalPerdas += $qtd;
                } else {
                    $totalVendas += $qtd;
                }
            }

            // Descrição e detalhamento do documento
            $docRef = '—';
            if ($row['tipo_movimentacao'] === 'entrada_fornecedor') {
                $docRef = ($row['produto_tipo'] === 'salgado_diario') ? 'Corte Salgados' : 'Entrada Manual';
            } elseif (!empty($row['numero_nota'])) {
                $docRef = 'NF ' . $row['numero_nota'];
            } elseif ($row['tipo_movimentacao'] === 'saida_encomenda') {
                $docRef = 'Cantina Web';
            } elseif ($row['tipo_movimentacao'] === 'saida_venda_avulsa') {
                $docRef = 'PDV Balcão';
            } elseif ($row['tipo_movimentacao'] === 'entrada_estorno') {
                $docRef = 'Estorno';
            } elseif ($row['tipo_movimentacao'] === 'saida_perda') {
                $docRef = 'Baixa / Perda';
            }

            // Justificativa e motivo
            $justCompleta = trim($row['justificativa'] ?? '');
            if (!empty($row['motivo_perda'])) {
                $motivoTxt = $labelsMotivo[$row['motivo_perda']] ?? ucfirst($row['motivo_perda']);
                $justCompleta = 'Motivo: ' . $motivoTxt . (!empty($justCompleta) ? ' — ' . $justCompleta : '');
            } elseif (empty($justCompleta) && !empty($row['razao_social_emitente'])) {
                $justCompleta = $row['razao_social_emitente'];
            }

            // Rótulo amigável com diferenciação entre salgados de corte e reposição manual
            if ($row['tipo_movimentacao'] === 'entrada_fornecedor') {
                $tipoLabel = ($row['produto_tipo'] === 'salgado_diario') 
                    ? 'Entrada de Salgados (Corte)' 
                    : 'Entrada Manual (Reposição)';
            } else {
                $tipoLabel = $labelsTipo[$row['tipo_movimentacao']] ?? ucfirst(str_replace('_', ' ', $row['tipo_movimentacao']));
            }

            $movimentacoes[] = [
                'id'                    => (int)$row['id'],
                'data_hora'             => $row['created_at'],
                'data_formatada'        => date('d/m/Y', strtotime($row['created_at'])),
                'hora_formatada'        => date('H:i', strtotime($row['created_at'])),
                'centro_custo'          => 'Cantina Geral',
                'categoria_id'          => (int)$row['categoria_id'],
                'categoria_nome'        => $row['categoria_nome'],
                'produto_id'            => (int)$row['produto_id'],
                'produto_codigo'        => $row['produto_codigo'],
                'produto_descricao'     => $row['produto_descricao'],
                'produto_tipo'          => $row['produto_tipo'],
                'tipo_movimentacao'     => $row['tipo_movimentacao'],
                'tipo_label'            => $tipoLabel,
                'is_entrada'            => $isEntrada,
                'qtd_entrada'           => $qtdEntrada,
                'qtd_saida'             => $qtdSaida,
                'operador_nome'         => $row['operador_nome'],
                'documento_ref'         => $docRef,
                'justificativa_completa'=> $justCompleta
            ];
        }

        // Agrupamento por Categoria / Grupo
        $gruposCategoria = [];
        if ($agruparGrupo) {
            foreach ($movimentacoes as $m) {
                $catId = $m['categoria_id'];
                if (!isset($gruposCategoria[$catId])) {
                    $gruposCategoria[$catId] = [
                        'categoria_id'   => $catId,
                        'categoria_nome' => $m['categoria_nome'],
                        'itens'          => [],
                        'total_entradas' => 0,
                        'total_saidas'   => 0,
                        'balanco'        => 0
                    ];
                }
                $gruposCategoria[$catId]['itens'][] = $m;
                $gruposCategoria[$catId]['total_entradas'] += $m['qtd_entrada'];
                $gruposCategoria[$catId]['total_saidas']   += $m['qtd_saida'];
                $gruposCategoria[$catId]['balanco']        = $gruposCategoria[$catId]['total_entradas'] - $gruposCategoria[$catId]['total_saidas'];
            }
        }

        // Agrupamento por Produto
        $gruposProduto = [];
        if ($agruparProduto) {
            foreach ($movimentacoes as $m) {
                $prodId = $m['produto_id'];
                if (!isset($gruposProduto[$prodId])) {
                    $gruposProduto[$prodId] = [
                        'produto_id'        => $prodId,
                        'produto_codigo'    => $m['produto_codigo'],
                        'produto_descricao' => $m['produto_descricao'],
                        'categoria_nome'    => $m['categoria_nome'],
                        'itens'             => [],
                        'total_entradas'    => 0,
                        'total_saidas'      => 0,
                        'balanco'           => 0
                    ];
                }
                $gruposProduto[$prodId]['itens'][] = $m;
                $gruposProduto[$prodId]['total_entradas'] += $m['qtd_entrada'];
                $gruposProduto[$prodId]['total_saidas']   += $m['qtd_saida'];
                $gruposProduto[$prodId]['balanco']        = $gruposProduto[$prodId]['total_entradas'] - $gruposProduto[$prodId]['total_saidas'];
            }
        }

        $periodoOficial = date('d/m/Y', strtotime($dataInicio)) . ' - ' . date('d/m/Y', strtotime($dataFim));

        $catNome = 'Todas as Categorias';
        if ($categoriaId > 0) {
            $catObj = Categoria::buscarPorId($categoriaId);
            if ($catObj) {
                $catNome = $catObj['nome'];
            }
        }

        $prodNome = 'Todos os Produtos';
        if ($produtoId > 0) {
            $prodObj = \App\Models\Produto::buscarPorId($produtoId);
            if ($prodObj) {
                $prodNome = "[{$prodObj['codigo_interno']}] {$prodObj['descricao']}";
            }
        }

        return [
            'periodo_oficial'          => $periodoOficial,
            'data_inicio'              => $dataInicio,
            'data_fim'                 => $dataFim,
            'categoria_filtro'         => $catNome,
            'produto_filtro'           => $prodNome,
            'centro_custo'             => 'Cantina Geral',
            'formato_layout'           => $formatoLayout,
            'formato_leiaute'          => $formatoLayout, // retrocompatibilidade
            'agrupar_grupo'            => $agruparGrupo,
            'agrupar_produto'          => $agruparProduto,
            'quebra_pagina'            => $quebraPagina,
            'filtros'                  => $filtros,
            'movimentacoes'            => $movimentacoes,
            'grupos_categoria'         => array_values($gruposCategoria),
            'grupos_produto'           => array_values($gruposProduto),
            'totais'                   => [
                'total_entradas'       => $totalEntradas,
                'total_saidas'         => $totalSaidas,
                'total_vendas'         => $totalVendas,
                'total_perdas'         => $totalPerdas,
                'balanco_periodo'      => ($totalEntradas - $totalSaidas),
                'total_movimentacoes'  => count($movimentacoes)
            ]
        ];
    }

    /**
     * Consolida todos os dados de arrecadação financeira real (Cash-In) e conciliação de carteira no período.
     */
    public function obterDadosArrecadacao(string $dataInicio, string $dataFim): array
    {
        $pdo = \App\Core\Model::getPdo();

        // 1. Recargas Pix Online (Portal da Família / Webhook Pix)
        $sqlRecPix = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor), 0) AS total
            FROM transacoes_carteira
            WHERE tipo_transacao = 'recarga_pix'
              AND DATE(created_at) BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlRecPix);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $recPix = $stmt->fetch(\PDO::FETCH_ASSOC);
        $recargasPixTotal = (float)($recPix['total'] ?? 0);
        $recargasPixQtd   = (int)($recPix['qtd'] ?? 0);

        // 2. Recargas no Balcão em Dinheiro Físico
        $sqlRecDin = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor), 0) AS total
            FROM transacoes_carteira
            WHERE tipo_transacao = 'recarga_dinheiro'
              AND DATE(created_at) BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlRecDin);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $recDin = $stmt->fetch(\PDO::FETCH_ASSOC);
        $recargasDinheiroTotal = (float)($recDin['total'] ?? 0);
        $recargasDinheiroQtd   = (int)($recDin['qtd'] ?? 0);

        // 3. Vendas Balcão em Dinheiro Físico
        $sqlVbDin = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor_total), 0) AS total
            FROM vendas_balcao
            WHERE forma_pagamento = 'dinheiro'
              AND status != 'estornada'
              AND DATE(created_at) BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlVbDin);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $vbDin = $stmt->fetch(\PDO::FETCH_ASSOC);
        $vendasDinheiroTotal = (float)($vbDin['total'] ?? 0);
        $vendasDinheiroQtd   = (int)($vbDin['qtd'] ?? 0);

        // 4. Vendas Balcão em Pix (QR Code Balcão / Maquininha)
        $sqlVbPix = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor_total), 0) AS total
            FROM vendas_balcao
            WHERE forma_pagamento IN ('pix', 'pix_balcao')
              AND status != 'estornada'
              AND DATE(created_at) BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlVbPix);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $vbPix = $stmt->fetch(\PDO::FETCH_ASSOC);
        $vendasPixTotal = (float)($vbPix['total'] ?? 0);
        $vendasPixQtd   = (int)($vbPix['qtd'] ?? 0);

        // 5. Estornos MED (Devolução Bancária Pix)
        $sqlMed = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor), 0) AS total
            FROM transacoes_carteira
            WHERE tipo_transacao = 'estorno_med'
              AND DATE(created_at) BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlMed);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $med = $stmt->fetch(\PDO::FETCH_ASSOC);
        $estornosMedTotal = (float)($med['total'] ?? 0);
        $estornosMedQtd   = (int)($med['qtd'] ?? 0);

        // 6. Total Geral Arrecadado (Cash-In)
        $totalArrecadadoBruto = $recargasPixTotal + $recargasDinheiroTotal + $vendasDinheiroTotal + $vendasPixTotal;
        $totalArrecadadoLiquido = $totalArrecadadoBruto - $estornosMedTotal;
        $totalTransacoesArrecadacao = $recargasPixQtd + $recargasDinheiroQtd + $vendasDinheiroQtd + $vendasPixQtd;

        // 7. Consumo de Saldo no Período (Lanches Entregues por Carteira)
        // 7a. Vendas Balcão em Saldo
        $sqlVbSaldo = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor_total), 0) AS total
            FROM vendas_balcao
            WHERE forma_pagamento = 'saldo'
              AND status != 'estornada'
              AND DATE(created_at) BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlVbSaldo);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $vbSaldo = $stmt->fetch(\PDO::FETCH_ASSOC);
        $vendasSaldoTotal = (float)($vbSaldo['total'] ?? 0);
        $vendasSaldoQtd   = (int)($vbSaldo['qtd'] ?? 0);

        // 7b. Pedidos Online em Saldo
        $sqlPedSaldo = "
            SELECT 
                COUNT(*) AS qtd,
                COALESCE(SUM(valor_total), 0) AS total
            FROM pedidos
            WHERE status IN ('agendado', 'editado', 'entregue')
              AND data_consumo BETWEEN :ini AND :fim
        ";
        $stmt = $pdo->prepare($sqlPedSaldo);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        $pedSaldo = $stmt->fetch(\PDO::FETCH_ASSOC);
        $pedidosSaldoTotal = (float)($pedSaldo['total'] ?? 0);
        $pedidosSaldoQtd   = (int)($pedSaldo['qtd'] ?? 0);

        $totalConsumoSaldo = $vendasSaldoTotal + $pedidosSaldoTotal;
        $totalQtdConsumoSaldo = $vendasSaldoQtd + $pedidosSaldoQtd;

        // 8. Confronto da Carteira (Recargas vs Consumo)
        $totalRecargas = $recargasPixTotal + $recargasDinheiroTotal;
        $variacaoCarteira = $totalRecargas - $totalConsumoSaldo;

        // 9. Agrupamento Diário (Linha a linha de cada dia do período)
        $diasPeriodo = [];
        $inicioObj = new \DateTime($dataInicio);
        $fimObj    = new \DateTime($dataFim);
        $fimObj->modify('+1 day');
        $intervalo = new \DatePeriod($inicioObj, new \DateInterval('P1D'), $fimObj);

        // a) Recargas Pix Diárias
        $mapRecPix = [];
        $sqlMapRecPix = "
            SELECT DATE(created_at) as dia, COALESCE(SUM(valor), 0) as total, COUNT(*) as qtd
            FROM transacoes_carteira
            WHERE tipo_transacao = 'recarga_pix' AND DATE(created_at) BETWEEN :ini AND :fim
            GROUP BY DATE(created_at)
        ";
        $stmt = $pdo->prepare($sqlMapRecPix);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $mapRecPix[$r['dia']] = (float)$r['total'];
        }

        // b) Recargas Dinheiro Diárias
        $mapRecDin = [];
        $sqlMapRecDin = "
            SELECT DATE(created_at) as dia, COALESCE(SUM(valor), 0) as total, COUNT(*) as qtd
            FROM transacoes_carteira
            WHERE tipo_transacao = 'recarga_dinheiro' AND DATE(created_at) BETWEEN :ini AND :fim
            GROUP BY DATE(created_at)
        ";
        $stmt = $pdo->prepare($sqlMapRecDin);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $mapRecDin[$r['dia']] = (float)$r['total'];
        }

        // c) Vendas Balcão Dinheiro Diárias
        $mapVbDin = [];
        $sqlMapVbDin = "
            SELECT DATE(created_at) as dia, COALESCE(SUM(valor_total), 0) as total, COUNT(*) as qtd
            FROM vendas_balcao
            WHERE forma_pagamento = 'dinheiro' AND status != 'estornada' AND DATE(created_at) BETWEEN :ini AND :fim
            GROUP BY DATE(created_at)
        ";
        $stmt = $pdo->prepare($sqlMapVbDin);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $mapVbDin[$r['dia']] = (float)$r['total'];
        }

        // d) Vendas Balcão Pix Diárias
        $mapVbPix = [];
        $sqlMapVbPix = "
            SELECT DATE(created_at) as dia, COALESCE(SUM(valor_total), 0) as total, COUNT(*) as qtd
            FROM vendas_balcao
            WHERE forma_pagamento IN ('pix', 'pix_balcao') AND status != 'estornada' AND DATE(created_at) BETWEEN :ini AND :fim
            GROUP BY DATE(created_at)
        ";
        $stmt = $pdo->prepare($sqlMapVbPix);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $mapVbPix[$r['dia']] = (float)$r['total'];
        }

        // e) Consumo de Saldo Diário (Vendas Balcão Saldo + Pedidos)
        $mapConsumoSaldo = [];
        $sqlMapVbSaldo = "
            SELECT DATE(created_at) as dia, COALESCE(SUM(valor_total), 0) as total
            FROM vendas_balcao
            WHERE forma_pagamento = 'saldo' AND status != 'estornada' AND DATE(created_at) BETWEEN :ini AND :fim
            GROUP BY DATE(created_at)
        ";
        $stmt = $pdo->prepare($sqlMapVbSaldo);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $mapConsumoSaldo[$r['dia']] = ($mapConsumoSaldo[$r['dia']] ?? 0.0) + (float)$r['total'];
        }

        $sqlMapPed = "
            SELECT data_consumo as dia, COALESCE(SUM(valor_total), 0) as total
            FROM pedidos
            WHERE status IN ('agendado', 'editado', 'entregue') AND data_consumo BETWEEN :ini AND :fim
            GROUP BY data_consumo
        ";
        $stmt = $pdo->prepare($sqlMapPed);
        $stmt->execute(['ini' => $dataInicio, 'fim' => $dataFim]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $mapConsumoSaldo[$r['dia']] = ($mapConsumoSaldo[$r['dia']] ?? 0.0) + (float)$r['total'];
        }

        $diasSemanaPt = [
            'Sunday'    => 'Domingo',
            'Monday'    => 'Segunda',
            'Tuesday'   => 'Terça',
            'Wednesday' => 'Quarta',
            'Thursday'  => 'Quinta',
            'Friday'    => 'Sexta',
            'Saturday'  => 'Sábado'
        ];

        foreach ($intervalo as $dt) {
            $diaIso = $dt->format('Y-m-d');
            $diaSemanaIngles = $dt->format('l');
            $diaSemana = $diasSemanaPt[$diaSemanaIngles] ?? $diaSemanaIngles;

            $rPix = $mapRecPix[$diaIso] ?? 0.0;
            $rDin = $mapRecDin[$diaIso] ?? 0.0;
            $vDin = $mapVbDin[$diaIso] ?? 0.0;
            $vPix = $mapVbPix[$diaIso] ?? 0.0;
            $totalDia = $rPix + $rDin + $vDin + $vPix;

            $cSaldo = $mapConsumoSaldo[$diaIso] ?? 0.0;
            $varSaldoDia = ($rPix + $rDin) - $cSaldo;

            $diasPeriodo[] = [
                'data_iso'           => $diaIso,
                'data_formatada'     => $dt->format('d/m/Y'),
                'dia_semana'         => $diaSemana,
                'recargas_pix'       => $rPix,
                'recargas_dinheiro'  => $rDin,
                'vendas_dinheiro'    => $vDin,
                'vendas_pix'         => $vPix,
                'total_arrecadado'   => $totalDia,
                'consumo_saldo'      => $cSaldo,
                'variacao_saldo'     => $varSaldoDia,
            ];
        }

        // Canais de arrecadação ordenados por representatividade
        $canais = [
            [
                'canal'             => 'Recargas Pix Online',
                'descricao'         => 'Portal da Família / Gateway Pix Dinâmico',
                'tipo'              => 'Online',
                'quantidade'        => $recargasPixQtd,
                'total'             => $recargasPixTotal,
                'percentual'        => $totalArrecadadoBruto > 0 ? ($recargasPixTotal / $totalArrecadadoBruto) * 100 : 0
            ],
            [
                'canal'             => 'Recargas no Balcão em Dinheiro',
                'descricao'         => 'Inserção física de créditos na carteira do aluno',
                'tipo'              => 'Balcão',
                'quantidade'        => $recargasDinheiroQtd,
                'total'             => $recargasDinheiroTotal,
                'percentual'        => $totalArrecadadoBruto > 0 ? ($recargasDinheiroTotal / $totalArrecadadoBruto) * 100 : 0
            ],
            [
                'canal'             => 'Vendas no Balcão em Pix',
                'descricao'         => 'Recebimento presencial instantâneo via Pix',
                'tipo'              => 'Balcão',
                'quantidade'        => $vendasPixQtd,
                'total'             => $vendasPixTotal,
                'percentual'        => $totalArrecadadoBruto > 0 ? ($vendasPixTotal / $totalArrecadadoBruto) * 100 : 0
            ],
            [
                'canal'             => 'Vendas no Balcão em Dinheiro',
                'descricao'         => 'Recebimento presencial em espécie / troco',
                'tipo'              => 'Balcão',
                'quantidade'        => $vendasDinheiroQtd,
                'total'             => $vendasDinheiroTotal,
                'percentual'        => $totalArrecadadoBruto > 0 ? ($vendasDinheiroTotal / $totalArrecadadoBruto) * 100 : 0
            ]
        ];

        return [
            'periodo_oficial'              => date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)),
            'data_inicio'                  => $dataInicio,
            'data_fim'                     => $dataFim,
            'recargas_pix_total'           => $recargasPixTotal,
            'recargas_pix_qtd'             => $recargasPixQtd,
            'recargas_dinheiro_total'      => $recargasDinheiroTotal,
            'recargas_dinheiro_qtd'        => $recargasDinheiroQtd,
            'vendas_dinheiro_total'        => $vendasDinheiroTotal,
            'vendas_dinheiro_qtd'          => $vendasDinheiroQtd,
            'vendas_pix_total'             => $vendasPixTotal,
            'vendas_pix_qtd'               => $vendasPixQtd,
            'estornos_med_total'           => $estornosMedTotal,
            'estornos_med_qtd'             => $estornosMedQtd,
            'total_arrecadado_bruto'       => $totalArrecadadoBruto,
            'total_arrecadado_liquido'     => $totalArrecadadoLiquido,
            'total_transacoes_arrecadacao' => $totalTransacoesArrecadacao,
            'vendas_saldo_total'           => $vendasSaldoTotal,
            'vendas_saldo_qtd'             => $vendasSaldoQtd,
            'pedidos_saldo_total'          => $pedidosSaldoTotal,
            'pedidos_saldo_qtd'            => $pedidosSaldoQtd,
            'total_consumo_saldo'          => $totalConsumoSaldo,
            'total_qtd_consumo_saldo'      => $totalQtdConsumoSaldo,
            'total_recargas'               => $totalRecargas,
            'variacao_carteira'            => $variacaoCarteira,
            'canais'                       => $canais,
            'dias'                         => $diasPeriodo,
            'data_emissao'                 => date('d/m/Y H:i:s')
        ];
    }

    /**
     * Tela Web: Relatório de Arrecadação Financeira & Fluxo de Entradas
     */
    public function arrecadacao(): void
    {
        $hoje = date('Y-m-d');
        $primeiroDiaMes = date('Y-m-01');

        $dataInicio = $_GET['data_inicio'] ?? $primeiroDiaMes;
        $dataFim    = $_GET['data_fim'] ?? $hoje;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) {
            $dataInicio = $primeiroDiaMes;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
            $dataFim = $hoje;
        }
        if ($dataInicio > $dataFim) {
            $tmp = $dataInicio;
            $dataInicio = $dataFim;
            $dataFim = $tmp;
        }

        $dados = $this->obterDadosArrecadacao($dataInicio, $dataFim);
        $dados['baseUrl'] = $this->getBaseUrl();

        $this->render('relatorios/arrecadacao', [
            'title'       => 'Arrecadação Financeira & Fluxo de Entradas — Colégio Monteiro Lobato',
            'activeMenu'  => 'arrecadacao',
            'dados'       => $dados,
            'dataInicio'  => $dataInicio,
            'dataFim'     => $dataFim,
            'baseUrl'     => $this->getBaseUrl()
        ], 'admin');
    }

    /**
     * Emissão do Relatório de Arrecadação para Impressão e Print to PDF Nativo
     */
    public function arrecadacaoPdf(): void
    {
        $this->arrecadacaoImprimir();
    }

    /**
     * Espelho HTML Oficial para Visualização e Impressão A4
     */
    public function arrecadacaoImprimir(): void
    {
        $hoje = date('Y-m-d');
        $primeiroDiaMes = date('Y-m-01');
        $dataInicio = $_GET['data_inicio'] ?? $primeiroDiaMes;
        $dataFim    = $_GET['data_fim'] ?? $hoje;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) {
            $dataInicio = $primeiroDiaMes;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
            $dataFim = $hoje;
        }
        if ($dataInicio > $dataFim) {
            $tmp = $dataInicio;
            $dataInicio = $dataFim;
            $dataFim = $tmp;
        }

        $dados = $this->obterDadosArrecadacao($dataInicio, $dataFim);
        $dados['baseUrl'] = $this->getBaseUrl();
        $dados['isPdf'] = false;
        $dados['dados'] = $dados;

        echo PdfService::renderView('relatorios/arrecadacao_pdf', $dados);
        if (!defined('CANTINA_TEST_MODE')) {
            exit;
        }
    }

    /**
     * Exportação analítica em CSV para Excel
     */
    public function arrecadacaoCsv(): void
    {
        $hoje = date('Y-m-d');
        $primeiroDiaMes = date('Y-m-01');
        $dataInicio = $_GET['data_inicio'] ?? $primeiroDiaMes;
        $dataFim    = $_GET['data_fim'] ?? $hoje;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataInicio)) {
            $dataInicio = $primeiroDiaMes;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
            $dataFim = $hoje;
        }
        if ($dataInicio > $dataFim) {
            $tmp = $dataInicio;
            $dataInicio = $dataFim;
            $dataFim = $tmp;
        }

        $dados = $this->obterDadosArrecadacao($dataInicio, $dataFim);

        $filename = "arrecadacao_financeira_{$dataInicio}_{$dataFim}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($output, ['COLÉGIO MONTEIRO LOBATO — SISTEMA CANTINA WEB'], ';', '"', "\\");
        fputcsv($output, ['RELATÓRIO DE ARRECADAÇÃO FINANCEIRA & FLUXO DE ENTRADAS (CASH-IN)'], ';', '"', "\\");
        fputcsv($output, ['PERÍODO:', $dados['periodo_oficial'], 'EMISSÃO:', date('d/m/Y H:i:s')], ';', '"', "\\");
        fputcsv($output, [], ';', '"', "\\");

        fputcsv($output, ['RESUMO POR CANAIS DE ARRECADAÇÃO'], ';', '"', "\\");
        fputcsv($output, ['Canal / Meio de Pagamento', 'Descrição', 'Ambiente', 'Qtd Transações', 'Total Arrecadado (R$)', 'Participação (%)'], ';', '"', "\\");
        foreach ($dados['canais'] as $c) {
            fputcsv($output, self::sanitizarCsvLinha([
                $c['canal'],
                $c['descricao'],
                $c['tipo'],
                $c['quantidade'],
                number_format($c['total'], 2, ',', '.'),
                number_format($c['percentual'], 1, ',', '.') . '%'
            ]), ';', '"', "\\");
        }
        fputcsv($output, [
            'TOTAL GERAL ARRECADADO',
            '',
            '',
            $dados['total_transacoes_arrecadacao'],
            number_format($dados['total_arrecadado_bruto'], 2, ',', '.'),
            '100.0%'
        ], ';', '"', "\\");
        fputcsv($output, [], ';', '"', "\\");

        fputcsv($output, ['DETALHAMENTO DIÁRIO DE ENTRADAS E CONSUMO'], ';', '"', "\\");
        fputcsv($output, [
            'Data',
            'Dia da Semana',
            'Recargas Pix Online (R$)',
            'Recargas Balcão Dinheiro (R$)',
            'Vendas Balcão Dinheiro (R$)',
            'Vendas Balcão Pix (R$)',
            'Total Arrecadado Dia (R$)',
            'Consumo em Saldo (R$)',
            'Variação Saldo (R$)'
        ], ';', '"', "\\");

        foreach ($dados['dias'] as $d) {
            fputcsv($output, self::sanitizarCsvLinha([
                $d['data_formatada'],
                $d['dia_semana'],
                number_format($d['recargas_pix'], 2, ',', '.'),
                number_format($d['recargas_dinheiro'], 2, ',', '.'),
                number_format($d['vendas_dinheiro'], 2, ',', '.'),
                number_format($d['vendas_pix'], 2, ',', '.'),
                number_format($d['total_arrecadado'], 2, ',', '.'),
                number_format($d['consumo_saldo'], 2, ',', '.'),
                number_format($d['variacao_saldo'], 2, ',', '.')
            ]), ';', '"', "\\");
        }

        fputcsv($output, [], ';', '"', "\\");
        fputcsv($output, self::sanitizarCsvLinha([
            'TOTAIS CONSOLIDADOS DO PERÍODO',
            '',
            number_format($dados['recargas_pix_total'], 2, ',', '.'),
            number_format($dados['recargas_dinheiro_total'], 2, ',', '.'),
            number_format($dados['vendas_dinheiro_total'], 2, ',', '.'),
            number_format($dados['vendas_pix_total'], 2, ',', '.'),
            number_format($dados['total_arrecadado_bruto'], 2, ',', '.'),
            number_format($dados['total_consumo_saldo'], 2, ',', '.'),
            number_format($dados['variacao_carteira'], 2, ',', '.')
        ]), ';', '"', "\\");

        fclose($output);
        if (!defined('CANTINA_TEST_MODE')) {
            exit;
        }
    }

    /**
     * Retorna a URL base normalizada da aplicação
     */
    protected function getBaseUrl(): string
    {
        if (defined('BASE_URL')) {
            return BASE_URL;
        }
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_replace('\\', '/', $scriptDir);
        return ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
    }
}



