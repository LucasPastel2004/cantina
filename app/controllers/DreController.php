<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controller: DreController
 * 
 * Gestão do DRE Gerencial (Demonstração do Resultado do Exercício), apuração comparativa
 * mês a mês, gráficos interativos de alta fidelidade e exportações em PDF A4 timbrado e CSV.
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/alterações sistema guideline/Estrutura de uma DRE.pdf e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\RBAC;
use App\Models\Dre;
use App\Models\Configuracao;
use App\Services\PdfService;

class DreController extends Controller
{
    private Dre $dreModel;

    public function __construct()
    {
        RBAC::requireRole(RBAC::ROLE_ADMINISTRADOR);
        $this->dreModel = new Dre();
    }

    /**
     * Resolve o intervalo de datas (data_inicio, data_fim) e o ano de referência.
     */
    private function resolverPeriodo(): array
    {
        $dataInicio = isset($_GET['data_inicio']) && is_string($_GET['data_inicio']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_inicio'])
            ? $_GET['data_inicio']
            : null;
        $dataFim = isset($_GET['data_fim']) && is_string($_GET['data_fim']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['data_fim'])
            ? $_GET['data_fim']
            : null;

        // Se fornecido ano explicitamente via parâmetro 'ano'
        $anoParam = isset($_GET['ano']) ? (int)$_GET['ano'] : 0;
        if ($anoParam >= 2020 && $anoParam <= 2050 && (!$dataInicio || !$dataFim)) {
            $dataInicio = sprintf('%04d-01-01', $anoParam);
            $dataFim    = sprintf('%04d-12-31', $anoParam);
            $ano = $anoParam;
        } elseif ($dataInicio && $dataFim) {
            $ano = (int)date('Y', strtotime($dataInicio));
        } else {
            // Padrão: ano corrente de 01/01 até o final do ano (ou hoje)
            $ano = (int)date('Y');
            $dataInicio = sprintf('%04d-01-01', $ano);
            $dataFim    = date('Y-m-d');
        }

        if ($dataInicio > $dataFim) {
            $temp = $dataInicio;
            $dataInicio = $dataFim;
            $dataFim = $temp;
        }

        return [$dataInicio, $dataFim, $ano];
    }

    /**
     * Tela principal: Painel Visual & Gráficos Interativos sempre abertos, com seletor de período por datas.
     * O demonstrativo formal do DRE é gerado sob demanda via Visualizar/Imprimir ou Baixar PDF.
     * Suporta regime de 'competencia' (vendas de lanches) ou 'caixa' (recargas de saldo + vendas avulsas).
     */
    public function index(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = (isset($_GET['regime']) && $_GET['regime'] === 'caixa') ? 'caixa' : 'competencia';

        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dreAno = $this->dreModel->calcularAnoComparativo($ano, $regime);
        $dadosGraficos = $this->dreModel->obterDadosGraficos($ano, $dataInicio, $dataFim, $regime);
        $config = Configuracao::obter();

        $this->render('financeiro/dre', [
            'title'         => "Painel Financeiro & DRE Gerencial — Colégio Monteiro Lobato",
            'activeMenu'    => 'dre',
            'ano'           => $ano,
            'dataInicio'    => $dataInicio,
            'dataFim'       => $dataFim,
            'regime'        => $regime,
            'drePeriodo'    => $drePeriodo,
            'totais'        => $drePeriodo, // Alimenta os stat-cards com o período filtrado
            'dre'           => $dreAno,
            'graficos'      => $dadosGraficos,
            'config'        => $config,
            'anosLista'     => [(int)date('Y'), (int)date('Y') - 1, (int)date('Y') - 2],
            'mesesAbrev'    => Dre::MESES_ABREV,
            'mesesPt'       => Dre::MESES_PT,
        ], 'admin');
    }

    /**
     * Endpoint API JSON com dados estruturados para renderização dos gráficos interativos.
     */
    public function graficosJson(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = (isset($_GET['regime']) && $_GET['regime'] === 'caixa') ? 'caixa' : 'competencia';
        $dados = $this->dreModel->obterDadosGraficos($ano, $dataInicio, $dataFim, $regime);

        $this->json([
            'sucesso'     => true,
            'ano'         => $ano,
            'regime'      => $regime,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim,
            'dados'       => $dados,
        ]);
    }

    /**
     * Gera e despacha o espelho HTML oficial do DRE para impressão via navegador para o período selecionado.
     */
    public function imprimir(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = (isset($_GET['regime']) && $_GET['regime'] === 'caixa') ? 'caixa' : 'competencia';
        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dreAno = $this->dreModel->calcularAnoComparativo($ano, $regime);
        $config = Configuracao::obter();

        // Injeta logo institucional em base64 para carregamento garantido
        $logoBase64 = $this->obterLogoBase64();

        // Renderiza a view de impressão com escopo protegido
        $this->renderViewDireto('financeiro/dre_pdf', [
            'ano'         => $ano,
            'dataInicio'  => $dataInicio,
            'dataFim'     => $dataFim,
            'regime'      => $regime,
            'drePeriodo'  => $drePeriodo,
            'dre'         => $dreAno,
            'totais'      => $drePeriodo,
            'config'      => $config,
            'logoBase64'  => $logoBase64,
            'dataEmissao' => date('d/m/Y H:i:s'),
            'mesesAbrev'  => Dre::MESES_ABREV,
            'mesesPt'     => Dre::MESES_PT,
            'isPdf'       => false,
            'baseUrl'     => $this->getBaseUrl(),
        ]);
    }

    /**
     * Gera e efetua download direto do relatório DRE em PDF A4 timbrado via Gotenberg/Chromium para o período selecionado.
     */
    public function pdf(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = (isset($_GET['regime']) && $_GET['regime'] === 'caixa') ? 'caixa' : 'competencia';
        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dreAno = $this->dreModel->calcularAnoComparativo($ano, $regime);
        $config = Configuracao::obter();
        $logoBase64 = $this->obterLogoBase64();

        $viewVars = [
            'ano'         => $ano,
            'dataInicio'  => $dataInicio,
            'dataFim'     => $dataFim,
            'regime'      => $regime,
            'drePeriodo'  => $drePeriodo,
            'dre'         => $dreAno,
            'totais'      => $drePeriodo,
            'config'      => $config,
            'logoBase64'  => $logoBase64,
            'dataEmissao' => date('d/m/Y H:i:s'),
            'mesesAbrev'  => Dre::MESES_ABREV,
            'mesesPt'     => Dre::MESES_PT,
            'isPdf'       => true,
            'baseUrl'     => $this->getBaseUrl(),
        ];

        $html = PdfService::renderView('financeiro/dre_pdf', $viewVars);

        // Opções para Gotenberg: documento em modo Paisagem para comportar as colunas perfeitamente
        $opcoes = [
            'landscape'         => true,
            'preferCssPageSize' => true,
            'emulatedMediaType' => 'print',
        ];

        $pdfBinario = PdfService::htmlToPdf($html, $opcoes);

        if ($pdfBinario !== null && strlen($pdfBinario) > 1000) {
            $nomeArquivo = "dre_gerencial_{$dataInicio}_a_{$dataFim}_" . date('Ymd_His') . ".pdf";
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            header('Content-Length: ' . strlen($pdfBinario));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $pdfBinario;
            exit;
        }

        // Fallback: exibe espelho de impressão caso Gotenberg não responda
        $this->imprimir();
    }

    /**
     * Exporta o DRE para planilha no formato CSV com BOM UTF-8 e delimitador ';'.
     */
    public function csv(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = (isset($_GET['regime']) && $_GET['regime'] === 'caixa') ? 'caixa' : 'competencia';
        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dreAno = $this->dreModel->calcularAnoComparativo($ano, $regime);

        $nomeArquivo = "dre_gerencial_{$regime}_{$dataInicio}_a_{$dataFim}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para compatibilidade nativa com Microsoft Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho da Empresa
        $rotuloRegime = ($regime === 'caixa') ? 'REGIME DE CAIXA (RECARGAS & ARRECADAÇÃO)' : 'REGIME DE COMPETÊNCIA (VENDAS & CONSUMO)';
        fputcsv($output, ['COLÉGIO MONTEIRO LOBATO - CANTINA WEB'], ';');
        fputcsv($output, ["DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO (DRE) - {$rotuloRegime}"], ';');
        fputcsv($output, ["PERÍODO: " . date('d/m/Y', strtotime($dataInicio)) . " A " . date('d/m/Y', strtotime($dataFim)) . " (Exercício {$ano})"], ';');
        fputcsv($output, ['Emissão: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($output, [], ';');

        // Linha de Cabeçalho dos Meses
        $cabecalho = ['ESTRUTURA DRE'];
        for ($m = 1; $m <= 12; $m++) {
            $cabecalho[] = Dre::MESES_ABREV[$m] . '/' . substr((string)$ano, 2);
        }
        $cabecalho[] = 'TOTAL DO PERÍODO';
        fputcsv($output, $cabecalho, ';');

        // Função auxiliar para montar linha de valores
        $escreverLinha = function(string $rotulo, string $chave, bool $formatarValor = true) use ($output, $dreAno, $drePeriodo) {
            $linha = [$rotulo];
            for ($m = 1; $m <= 12; $m++) {
                $val = $dreAno['meses'][$m][$chave] ?? 0.00;
                $linha[] = $formatarValor ? number_format((float)$val, 2, ',', '.') : $val;
            }
            $totalVal = $drePeriodo[$chave] ?? 0.00;
            $linha[] = $formatarValor ? number_format((float)$totalVal, 2, ',', '.') : $totalVal;
            fputcsv($output, $linha, ';');
        };

        // 1. Receitas
        $escreverLinha('(+) RECEITA BRUTA DE VENDAS', 'receita_bruta');
        $escreverLinha('    Vendas Cantina Web (Online)', 'receita_online');
        $escreverLinha('    Vendas Presenciais no Balcão', 'receita_balcao');
        
        // 2. Deduções
        $escreverLinha('(-) DEDUÇÕES E ABATIMENTOS', 'total_deducoes');
        $escreverLinha('    Estornos de Balcão', 'estornos_balcao');
        $escreverLinha('    Estornos Pedidos Online', 'estornos_online');
        
        // 3. Receita Líquida
        $escreverLinha('(=) RECEITA LÍQUIDA DE VENDAS', 'receita_liquida');
        
        // 4. CMV
        $escreverLinha('(-) CUSTO DAS MERCADORIAS VENDIDAS (CMV)', 'cmv');
        
        // 5. Lucro Bruto
        $escreverLinha('(=) LUCRO BRUTO', 'lucro_bruto');
        
        // Margem Bruta
        $linhaMargemBruta = ['    Margem Bruta (%)'];
        for ($m = 1; $m <= 12; $m++) {
            $linhaMargemBruta[] = number_format((float)($dreAno['meses'][$m]['margem_bruta_percent'] ?? 0), 1, ',', '.') . '%';
        }
        $linhaMargemBruta[] = number_format((float)($dreAno['totais']['margem_bruta_percent'] ?? 0), 1, ',', '.') . '%';
        fputcsv($output, $linhaMargemBruta, ';');

        // 6. Despesas Operacionais
        $escreverLinha('(-) DESPESAS OPERACIONAIS', 'total_despesas');
        $escreverLinha('    Despesas Administrativas', 'despesas_administrativas');
        $escreverLinha('    Despesas com Pessoal & Salários', 'despesas_pessoal');
        $escreverLinha('    Despesas com Vendas & Embalagens', 'despesas_vendas');
        $escreverLinha('    Manutenção & Equipamentos', 'despesas_manutencao');
        $escreverLinha('    Despesas Financeiras & Tarifas', 'despesas_financeiras');
        $escreverLinha('    Outras Despesas Operacionais', 'despesas_outras');

        // 7. Resultado Líquido
        $escreverLinha('(=) RESULTADO LÍQUIDO DO EXERCÍCIO (LUCRO LÍQUIDO)', 'resultado_liquido');

        // Margem Líquida
        $linhaMargemLiq = ['    Margem Líquida (%)'];
        for ($m = 1; $m <= 12; $m++) {
            $linhaMargemLiq[] = number_format((float)($dreAno['meses'][$m]['margem_liquida_percent'] ?? 0), 1, ',', '.') . '%';
        }
        $linhaMargemLiq[] = number_format((float)($dreAno['totais']['margem_liquida_percent'] ?? 0), 1, ',', '.') . '%';
        fputcsv($output, $linhaMargemLiq, ';');

        fclose($output);
        exit;
    }

    /**
     * Renderiza diretamente uma view de impressão isolando escopo de variáveis.
     */
    private function renderViewDireto(string $viewName, array $dados): void
    {
        $viewPath = __DIR__ . '/../views/' . $viewName . '.php';
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$viewPath}");
        }

        extract($dados, EXTR_OVERWRITE);
        require $viewPath;
    }

    /**
     * Obtém o logotipo oficial em formato base64 Data-URI.
     */
    private function obterLogoBase64(): string
    {
        $caminhoLogo = __DIR__ . '/../../assets/img/logo-colegio.jpg';
        if (file_exists($caminhoLogo)) {
            $conteudo = file_get_contents($caminhoLogo);
            return 'data:image/jpeg;base64,' . base64_encode($conteudo);
        }
        return '';
    }
}
