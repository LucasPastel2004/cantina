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
     * Tela principal comparativa do DRE com demonstrativo mês a mês e gráficos interativos.
     */
    public function index(): void
    {
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        if ($ano < 2020 || $ano > 2050) {
            $ano = (int)date('Y');
        }

        $dreAno = $this->dreModel->calcularAnoComparativo($ano);
        $dadosGraficos = $this->dreModel->obterDadosGraficos($ano);
        $config = Configuracao::obter();

        $this->render('financeiro/dre', [
            'title'         => "DRE Gerencial {$ano} — Colégio Monteiro Lobato",
            'activeMenu'    => 'dre',
            'ano'           => $ano,
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
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $dados = $this->dreModel->obterDadosGraficos($ano);

        $this->json([
            'sucesso' => true,
            'ano'     => $ano,
            'dados'   => $dados,
        ]);
    }

    /**
     * Gera e despacha o espelho HTML oficial do DRE para impressão via navegador.
     */
    public function imprimir(): void
    {
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $dreAno = $this->dreModel->calcularAnoComparativo($ano);
        $config = Configuracao::obter();

        // Injeta logo institucional em base64 para carregamento garantido
        $logoBase64 = $this->obterLogoBase64();

        // Renderiza a view de impressão com escopo protegido
        $this->renderViewDireto('financeiro/dre_pdf', [
            'ano'         => $ano,
            'dre'         => $dreAno,
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
     * Gera e efetua download direto do relatório DRE em PDF A4 timbrado via Gotenberg/Chromium.
     */
    public function pdf(): void
    {
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $dreAno = $this->dreModel->calcularAnoComparativo($ano);
        $config = Configuracao::obter();
        $logoBase64 = $this->obterLogoBase64();

        $viewVars = [
            'ano'         => $ano,
            'dre'         => $dreAno,
            'config'      => $config,
            'logoBase64'  => $logoBase64,
            'dataEmissao' => date('d/m/Y H:i:s'),
            'mesesAbrev'  => Dre::MESES_ABREV,
            'mesesPt'     => Dre::MESES_PT,
            'isPdf'       => true,
            'baseUrl'     => $this->getBaseUrl(),
        ];

        $html = PdfService::renderView('financeiro/dre_pdf', $viewVars);

        // Opções para Gotenberg: documento em modo Paisagem para comportar as 13 colunas perfeitamente
        $opcoes = [
            'landscape'         => true,
            'preferCssPageSize' => true,
            'emulatedMediaType' => 'print',
        ];

        $pdfBinario = PdfService::htmlToPdf($html, $opcoes);

        if ($pdfBinario !== null && strlen($pdfBinario) > 1000) {
            $nomeArquivo = "dre_gerencial_{$ano}_" . date('Ymd_His') . ".pdf";
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
     * Exporta o DRE comparativo anual para planilha no formato CSV com BOM UTF-8 e delimitador ';'.
     */
    public function csv(): void
    {
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $dreAno = $this->dreModel->calcularAnoComparativo($ano);

        $nomeArquivo = "dre_gerencial_{$ano}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 para compatibilidade nativa com Microsoft Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalho da Empresa
        fputcsv($output, ['COLÉGIO MONTEIRO LOBATO - CANTINA WEB'], ';');
        fputcsv($output, ["DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO (DRE) - ANO {$ano}"], ';');
        fputcsv($output, ['Emissão: ' . date('d/m/Y H:i:s')], ';');
        fputcsv($output, [], ';');

        // Linha de Cabeçalho dos Meses
        $cabecalho = ['ESTRUTURA DRE'];
        for ($m = 1; $m <= 12; $m++) {
            $cabecalho[] = Dre::MESES_ABREV[$m] . '/' . substr((string)$ano, 2);
        }
        $cabecalho[] = 'TOTAL DO ANO';
        fputcsv($output, $cabecalho, ';');

        // Função auxiliar para montar linha de valores
        $escreverLinha = function(string $rotulo, string $chave, bool $formatarValor = true) use ($output, $dreAno) {
            $linha = [$rotulo];
            for ($m = 1; $m <= 12; $m++) {
                $val = $dreAno['meses'][$m][$chave] ?? 0.00;
                $linha[] = $formatarValor ? number_format((float)$val, 2, ',', '.') : $val;
            }
            $totalVal = $dreAno['totais'][$chave] ?? 0.00;
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
