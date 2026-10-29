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
     * Resolve o regime contábil (padrão 'unificado', mantendo suporte retrocompatível).
     */
    private function resolverRegime(): string
    {
        if (isset($_GET['regime']) && in_array($_GET['regime'], ['unificado', 'competencia', 'caixa'], true)) {
            return $_GET['regime'];
        }
        return 'unificado';
    }

    /**
     * Tela principal: Painel Visual & Gráficos Interativos sempre abertos, com seletor de período por datas.
     * Opera no Modelo Contábil Unificado Integrado (Vendas/Consumo + Recargas/Custódia + Resultado em Conta sem duplicação).
     */
    public function index(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = $this->resolverRegime();

        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dreAno = $this->dreModel->calcularAnoComparativo($ano, $regime);
        $dadosGraficos = $this->dreModel->obterDadosGraficos($ano, $dataInicio, $dataFim, $regime);
        $config = Configuracao::obter();

        $this->render('financeiro/dre', [
            'title'         => "Painel Financeiro & DRE Gerencial Unificado — Colégio Monteiro Lobato",
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
        $regime = $this->resolverRegime();
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
     * Gera e despacha o espelho HTML oficial do DRE Unificado (Texto / Grade Contábil) para impressão via navegador.
     */
    public function imprimir(): void
    {
        if (isset($_GET['formato']) && $_GET['formato'] === 'grafico') {
            $this->grafico();
            return;
        }

        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = $this->resolverRegime();
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
     * Gera e despacha o Relatório Gráfico do DRE (DRE Visual Unificado + Lista dos Produtos Mais Rentáveis)
     * em espelho HTML oficial A4 para impressão e Print to PDF nativo.
     */
    public function grafico(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = $this->resolverRegime();
        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dadosGraficos = $this->dreModel->obterDadosGraficos($ano, $dataInicio, $dataFim, $regime);
        $config = Configuracao::obter();
        $logoBase64 = $this->obterLogoBase64();

        $this->renderViewDireto('financeiro/dre_grafico_pdf', [
            'ano'         => $ano,
            'dataInicio'  => $dataInicio,
            'dataFim'     => $dataFim,
            'regime'      => $regime,
            'drePeriodo'  => $drePeriodo,
            'totais'      => $drePeriodo,
            'graficos'    => $dadosGraficos,
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
     * Emissão do relatório DRE para impressão e Print to PDF Nativo
     */
    public function pdf(): void
    {
        $this->imprimir();
    }

    /**
     * Exporta o DRE Unificado para planilha no formato CSV com BOM UTF-8 e delimitador ';'.
     */
    public function csv(): void
    {
        [$dataInicio, $dataFim, $ano] = $this->resolverPeriodo();
        $regime = $this->resolverRegime();
        $drePeriodo = $this->dreModel->calcularPeriodo($dataInicio, $dataFim, $regime);
        $dreAno = $this->dreModel->calcularAnoComparativo($ano, $regime);

        $nomeArquivo = "dre_gerencial_unificado_{$dataInicio}_a_{$dataFim}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para compatibilidade nativa com Microsoft Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho da Empresa
        fputcsv($output, ['COLÉGIO MONTEIRO LOBATO - CANTINA WEB'], ';', '"', "\\");
        fputcsv($output, ['DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO (DRE UNIFICADO — VENDAS, CONSUMO DE SALDO & CAIXA REAL EM CONTA)'], ';', '"', "\\");
        fputcsv($output, ["PERÍODO: " . date('d/m/Y', strtotime($dataInicio)) . " A " . date('d/m/Y', strtotime($dataFim)) . " (Exercício {$ano})"], ';', '"', "\\");
        fputcsv($output, ['Emissão: ' . date('d/m/Y H:i:s')], ';', '"', "\\");
        fputcsv($output, [], ';', '"', "\\");

        // Linha de Cabeçalho dos Meses
        $cabecalho = ['ESTRUTURA DRE UNIFICADO'];
        for ($m = 1; $m <= 12; $m++) {
            $cabecalho[] = Dre::MESES_ABREV[$m] . '/' . substr((string)$ano, 2);
        }
        $cabecalho[] = 'TOTAL DO PERÍODO';
        fputcsv($output, $cabecalho, ';', '"', "\\");

        // Função auxiliar para montar linha de valores
        $escreverLinha = function(string $rotulo, string $chave, bool $formatarValor = true) use ($output, $dreAno, $drePeriodo) {
            $linha = [$rotulo];
            for ($m = 1; $m <= 12; $m++) {
                $val = $dreAno['meses'][$m][$chave] ?? 0.00;
                $linha[] = $formatarValor ? number_format((float)$val, 2, ',', '.') : $val;
            }
            $totalVal = $drePeriodo[$chave] ?? 0.00;
            $linha[] = $formatarValor ? number_format((float)$totalVal, 2, ',', '.') : $totalVal;
            fputcsv($output, $linha, ';', '"', "\\");
        };

        // BLOCO 1: APURAÇÃO OPERACIONAL DE VENDAS E CONSUMO (COMPETÊNCIA)
        $escreverLinha('[+] RECEITA BRUTA DE VENDAS E CONSUMO (LANCHES ENTREGUES)', 'receita_bruta');
        $escreverLinha('    Consumo de Saldo em Pedidos Online (Cantina Web)', 'consumo_saldo_online');
        $escreverLinha('    Consumo de Saldo no Balcão (PDV Carteira)', 'consumo_saldo_balcao');
        $escreverLinha('    Vendas Diretas no Balcão em Dinheiro', 'vendas_dinheiro');
        $escreverLinha('    Vendas Diretas no Balcão em Pix', 'vendas_pix');
        
        // 2. Deduções
        $escreverLinha('[-] DEDUÇÕES E ABATIMENTOS (ESTORNOS)', 'total_deducoes');
        $escreverLinha('    Estornos de Balcão', 'estornos_balcao');
        $escreverLinha('    Estornos Pedidos Online', 'estornos_online');
        
        // 3. Receita Líquida
        $escreverLinha('[=] RECEITA LÍQUIDA DE VENDAS (FATURAMENTO REALIZADO)', 'receita_liquida');
        
        // 4. CMV
        $escreverLinha('[-] CUSTO DAS MERCADORIAS VENDIDAS (CMV)', 'cmv');
        
        // 5. Lucro Bruto
        $escreverLinha('[=] LUCRO BRUTO OPERACIONAL', 'lucro_bruto');
        
        // Margem Bruta
        $linhaMargemBruta = ['    Margem Bruta Operacional (%)'];
        for ($m = 1; $m <= 12; $m++) {
            $linhaMargemBruta[] = number_format((float)($dreAno['meses'][$m]['margem_bruta_percent'] ?? 0), 1, ',', '.') . '%';
        }
        $linhaMargemBruta[] = number_format((float)($drePeriodo['margem_bruta_percent'] ?? 0), 1, ',', '.') . '%';
        fputcsv($output, $linhaMargemBruta, ';', '"', "\\");

        // 5.1 Pagamento de Fornecedores (Recibos Emitidos) — Separado do bloco de Despesas Operacionais
        $escreverLinha('[-] PAGAMENTO DE FORNECEDORES (RECIBOS EMITIDOS)', 'despesas_fornecedores');

        // 6. Despesas Operacionais
        $escreverLinha('[-] DESPESAS OPERACIONAIS', 'despesas_operacionais_puras');
        $escreverLinha('    Despesas Administrativas', 'despesas_administrativas');
        $escreverLinha('    Despesas com Pessoal & Salários', 'despesas_pessoal');
        $escreverLinha('    Despesas com Vendas & Embalagens', 'despesas_vendas');
        $escreverLinha('    Manutenção & Equipamentos', 'despesas_manutencao');
        $escreverLinha('    Despesas Financeiras & Tarifas', 'despesas_financeiras');
        $escreverLinha('    Outras Despesas Operacionais', 'despesas_outras');

        // 7. Resultado Líquido Operacional (Vendas)
        $escreverLinha('[=] RESULTADO LÍQUIDO OPERACIONAL (LUCRO DAS VENDAS)', 'resultado_liquido');

        // Margem Líquida Operacional
        $linhaMargemLiq = ['    Margem Líquida Operacional (%)'];
        for ($m = 1; $m <= 12; $m++) {
            $linhaMargemLiq[] = number_format((float)($dreAno['meses'][$m]['margem_liquida_percent'] ?? 0), 1, ',', '.') . '%';
        }
        $linhaMargemLiq[] = number_format((float)($drePeriodo['margem_liquida_percent'] ?? 0), 1, ',', '.') . '%';
        fputcsv($output, $linhaMargemLiq, ';', '"', "\\");

        // BLOCO 2: CONCILIAÇÃO DE CARTEIRA PRÉ-PAGA & RESULTADO REAL EM CONTA (SEM DUPLICIDADE)
        fputcsv($output, [], ';', '"', "\\");
        fputcsv($output, ['CONCILIAÇÃO DE CARTEIRA PRÉ-PAGA & RESULTADO FINANCEIRO EM CONTA (PONTE COMPETÊNCIA -> CAIXA REAL)'], ';', '"', "\\");
        $escreverLinha('[+] RECARGAS DE SALDO RECEBIDAS NA CONTA (ENTRADA DE CARTEIRAS)', 'total_recargas');
        $escreverLinha('    Recargas Pix Online (Portal da Família)', 'recargas_pix_online');
        $escreverLinha('    Recargas Pix no Balcão (PDV)', 'recargas_pix_pdv');
        $escreverLinha('    Recargas em Dinheiro no Balcão (PDV)', 'recargas_dinheiro');
        $escreverLinha('[-] ABATIMENTO DO SALDO CONSUMIDO EM VENDAS (JÁ COMPUTADO NA RECEITA ACIMA)', 'consumo_saldo_total');
        $escreverLinha('[=] VARIAÇÃO LÍQUIDA DE SALDO EM CUSTÓDIA (RECARGAS - CONSUMO DE SALDO)', 'diferenca_recarga_consumo');
        $escreverLinha('[=] ARRECADAÇÃO LÍQUIDA REAL NA CONTA (RECARGAS + VENDAS DIRETAS - ESTORNOS)', 'arrecadacao_liquida');
        $escreverLinha('[=] RESULTADO FINANCEIRO LÍQUIDO EM CONTA (CAIXA REAL DO EXERCÍCIO)', 'resultado_caixa');

        // Margem Financeira de Caixa
        $linhaMargemCaixa = ['    Margem Financeira em Conta (%)'];
        for ($m = 1; $m <= 12; $m++) {
            $linhaMargemCaixa[] = number_format((float)($dreAno['meses'][$m]['margem_caixa_percent'] ?? 0), 1, ',', '.') . '%';
        }
        $linhaMargemCaixa[] = number_format((float)($drePeriodo['margem_caixa_percent'] ?? 0), 1, ',', '.') . '%';
        fputcsv($output, $linhaMargemCaixa, ';', '"', "\\");

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
