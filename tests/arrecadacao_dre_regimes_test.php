<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Arrecadação Financeira & Regimes do DRE (Competência vs Caixa)
 */

declare(strict_types=1);

// Evita bloqueios de ambiente web e CSRF
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoloader PSR-4 nativo
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Dre;
use App\Models\TransacaoCarteira;
use App\Models\Responsavel;
use App\Controllers\DreController;
use App\Controllers\RelatoriosController;
use App\Services\PdfService;

class ArrecadacaoDreRegimesTest
{
    private int $totalTestes = 0;
    private int $testesPassados = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \App\Core\Model::getPdo();
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalTestes++;
        if ($condicao) {
            $this->testesPassados++;
            echo "  [OK] {$descricao}\n";
        } else {
            echo "  [FALHA] {$descricao}\n";
        }
    }

    public function executar(): void
    {
        echo "====================================================================\n";
        echo " INICIANDO SUÍTE DE TESTES: ARRECADAÇÃO & REGIMES DRE (COMPETÊNCIA/CAIXA)\n";
        echo "====================================================================\n\n";

        $this->testarDreRegimes();
        $this->testarRelatorioArrecadacaoDados();
        $this->testarPdfArrecadacaoCompilacao();
        $this->testarRotasEControllers();

        echo "\n--------------------------------------------------------------------\n";
        echo " RESULTADO FINAL: {$this->testesPassados} / {$this->totalTestes} asserções aprovadas\n";
        echo "--------------------------------------------------------------------\n";

        if ($this->testesPassados === $this->totalTestes) {
            echo " >>> SUCESSO TOTAL: 100% DAS ASSERÇÕES APROVADAS! <<<\n";
            exit(0);
        } else {
            echo " >>> FALHA: UMA OU MAIS ASSERÇÕES NÃO PASSARAM! <<<\n";
            exit(1);
        }
    }

    /**
     * Teste 1: Regimes do DRE Gerencial (Competência vs Caixa)
     */
    private function testarDreRegimes(): void
    {
        echo ">> 1. Testando Regimes no DRE Gerencial (Competência vs Caixa)...\n";
        $dreModel = new Dre();

        $inicio = '2026-10-01';
        $fim = '2026-10-31';

        // 1.1 Regime de Competência
        $dreComp = $dreModel->calcularPeriodo($inicio, $fim, 'competencia');
        $this->assert(isset($dreComp['receita_bruta']), "DRE Competência calcula receita_bruta");
        $this->assert(isset($dreComp['receita_online']), "DRE Competência calcula receita_online");
        $this->assert(isset($dreComp['receita_balcao']), "DRE Competência calcula receita_balcao");
        $this->assert(isset($dreComp['lucro_bruto']), "DRE Competência calcula lucro_bruto");
        $this->assert(isset($dreComp['resultado_liquido']), "DRE Competência calcula resultado_liquido");

        // 1.2 Regime de Caixa
        $dreCaixa = $dreModel->calcularPeriodo($inicio, $fim, 'caixa');
        $this->assert(isset($dreCaixa['receita_bruta']), "DRE Caixa calcula receita_bruta consolidada");
        $this->assert(isset($dreCaixa['recargas_pix']), "DRE Caixa apura recargas_pix");
        $this->assert(isset($dreCaixa['recargas_dinheiro']), "DRE Caixa apura recargas_dinheiro");
        $this->assert(isset($dreCaixa['vendas_dinheiro']), "DRE Caixa apura vendas_dinheiro no balcão");
        $this->assert(isset($dreCaixa['vendas_pix']), "DRE Caixa apura vendas_pix no balcão");
        $this->assert(isset($dreCaixa['total_recargas']), "DRE Caixa apura total_recargas da carteira");
        $this->assert(isset($dreCaixa['consumo_saldo_total']), "DRE Caixa apura consumo_saldo_total");
        $this->assert(isset($dreCaixa['diferenca_recarga_consumo']), "DRE Caixa apura diferenca_recarga_consumo");

        // Validação da matemática do Regime de Caixa
        $somaEsperada = (float)$dreCaixa['recargas_pix'] + (float)$dreCaixa['recargas_dinheiro'] + (float)$dreCaixa['vendas_dinheiro'] + (float)$dreCaixa['vendas_pix'];
        $this->assert(
            abs((float)$dreCaixa['receita_bruta'] - $somaEsperada) < 0.01,
            "Receita Bruta no Regime de Caixa é rigorosamente a soma de Recargas (Pix + Dinheiro) e Vendas Balcão (Dinheiro + Pix)"
        );

        $varEsperada = (float)$dreCaixa['total_recargas'] - (float)$dreCaixa['consumo_saldo_total'];
        $this->assert(
            abs((float)$dreCaixa['diferenca_recarga_consumo'] - $varEsperada) < 0.01,
            "Diferença entre Recargas e Consumo é matematicamente exata no Regime de Caixa"
        );

        // 1.3 Ano comparativo nos dois regimes
        $anoComp = $dreModel->calcularAnoComparativo(2026, 'competencia');
        $this->assert(count($anoComp['meses']) === 12, "DRE Anual Competência possui 12 meses");

        $anoCaixa = $dreModel->calcularAnoComparativo(2026, 'caixa');
        $this->assert(count($anoCaixa['meses']) === 12, "DRE Anual Caixa possui 12 meses");
        $this->assert(isset($anoCaixa['totais']['total_recargas']), "DRE Anual Caixa totaliza total_recargas");
        $this->assert(isset($anoCaixa['totais']['consumo_saldo_total']), "DRE Anual Caixa totaliza consumo_saldo_total");

        // 1.4 Dados de Gráficos nos dois regimes
        $grafComp = $dreModel->obterDadosGraficos(2026, $inicio, $fim, 'competencia');
        $this->assert(count($grafComp['labels']) === 12, "Gráficos Competência possuem 12 rótulos");

        $grafCaixa = $dreModel->obterDadosGraficos(2026, $inicio, $fim, 'caixa');
        $this->assert(count($grafCaixa['labels']) === 12, "Gráficos Caixa possuem 12 rótulos");
        $this->assert(count($grafCaixa['receitas']) === 12, "Gráficos Caixa alimentam série de receitas");
    }

    /**
     * Teste 2: Consolidação dos Dados de Arrecadação Financeira (RelatoriosController)
     */
    private function testarRelatorioArrecadacaoDados(): void
    {
        echo "\n>> 2. Testando Consolidação Contábil de Arrecadação Financeira (Cash-In)...\n";
        $ctrl = new RelatoriosController();

        $inicio = '2026-10-01';
        $fim = '2026-10-05';

        $dados = $ctrl->obterDadosArrecadacao($inicio, $fim);

        // Validação da estrutura
        $this->assert(isset($dados['periodo_oficial']), "Dados de arrecadação incluem periodo_oficial");
        $this->assert(isset($dados['recargas_pix_total']), "Dados de arrecadação incluem recargas_pix_total");
        $this->assert(isset($dados['recargas_dinheiro_total']), "Dados de arrecadação incluem recargas_dinheiro_total");
        $this->assert(isset($dados['vendas_dinheiro_total']), "Dados de arrecadação incluem vendas_dinheiro_total");
        $this->assert(isset($dados['vendas_pix_total']), "Dados de arrecadação incluem vendas_pix_total");
        $this->assert(isset($dados['total_arrecadado_bruto']), "Dados de arrecadação incluem total_arrecadado_bruto");
        $this->assert(isset($dados['total_arrecadado_liquido']), "Dados de arrecadação incluem total_arrecadado_liquido");
        $this->assert(isset($dados['total_consumo_saldo']), "Dados de arrecadação incluem total_consumo_saldo");
        $this->assert(isset($dados['variacao_carteira']), "Dados de arrecadação incluem variacao_carteira");

        // Validação da matemática
        $somaBruta = (float)$dados['recargas_pix_total'] + (float)$dados['recargas_dinheiro_total'] + (float)$dados['vendas_dinheiro_total'] + (float)$dados['vendas_pix_total'];
        $this->assert(
            abs((float)$dados['total_arrecadado_bruto'] - $somaBruta) < 0.01,
            "Total Arrecadado Bruto é exatamente a soma de Recargas Pix + Recargas Dinheiro + Vendas Dinheiro + Vendas Pix"
        );

        $varEsperada = (float)$dados['total_recargas'] - (float)$dados['total_consumo_saldo'];
        $this->assert(
            abs((float)$dados['variacao_carteira'] - $varEsperada) < 0.01,
            "Variação da Carteira é exatamente Total de Recargas - Total Consumido em Lanches"
        );

        // Validação dos Canais
        $this->assert(count($dados['canais']) === 4, "Relatório contém os 4 canais oficiais de arrecadação");
        $somaPerc = 0.0;
        foreach ($dados['canais'] as $canal) {
            $somaPerc += (float)$canal['percentual'];
            $this->assert(!empty($canal['canal']), "Canal possui nome preenchido: {$canal['canal']}");
            $this->assert(isset($canal['quantidade']), "Canal possui contagem de transações");
            $this->assert(isset($canal['total']), "Canal possui valor total");
        }
        if ((float)$dados['total_arrecadado_bruto'] > 0) {
            $this->assert(abs($somaPerc - 100.0) < 0.2, "Soma das participações percentuais dos canais fecha em 100%");
        }

        // Validação dos Dias
        $this->assert(count($dados['dias']) === 5, "Período de 01/10 a 05/10 contém exatamente 5 dias apurados");
        $somaDias = 0.0;
        foreach ($dados['dias'] as $dia) {
            $somaDias += (float)$dia['total_arrecadado'];
            $this->assert(!empty($dia['data_formatada']), "Dia possui data formatada");
            $this->assert(!empty($dia['dia_semana']), "Dia possui dia da semana em português ({$dia['dia_semana']})");
            $this->assert(isset($dia['recargas_pix']), "Dia possui recargas_pix");
            $this->assert(isset($dia['total_arrecadado']), "Dia possui total_arrecadado");
        }
        $this->assert(
            abs($somaDias - (float)$dados['total_arrecadado_bruto']) < 0.01,
            "Soma diária de arrecadação coincide perfeitamente com o Total Geral do período"
        );
    }

    /**
     * Teste 3: Compilação de PDF via Gotenberg
     */
    private function testarPdfArrecadacaoCompilacao(): void
    {
        echo "\n>> 3. Testando Compilação do PDF Oficial de Arrecadação via Gotenberg...\n";
        $ctrl = new RelatoriosController();

        $dados = $ctrl->obterDadosArrecadacao('2026-10-01', '2026-10-05');
        $dados['baseUrl'] = '';
        $dados['isPdf'] = true;

        $html = PdfService::renderView('relatorios/arrecadacao_pdf', $dados);
        $this->assert(str_contains($html, 'COLÉGIO MONTEIRO LOBATO'), "HTML gerado contém razão social da instituição");
        $this->assert(str_contains($html, 'DEMONSTRATIVO DE ARRECADAÇÃO FINANCEIRA'), "HTML gerado contém título oficial do relatório");
        $this->assert(str_contains($html, 'table-doc-excel'), "HTML gerado utiliza classe de tabela Excel homologada");

        $pdf = PdfService::htmlToPdf($html, [
            'preferCssPageSize' => 'true',
            'printBackground'   => 'true'
        ]);

        $this->assert($pdf !== null, "Gotenberg respondeu à compilação do PDF");
        $this->assert(is_string($pdf) && str_starts_with($pdf, '%PDF'), "Documento gerado é um binário PDF 1.4 válido (%PDF)");
        $this->assert(strlen($pdf) > 20000, "Tamanho do PDF de Arrecadação gerado é substancial (> 20 KB): " . strlen($pdf) . " bytes");
    }

    /**
     * Teste 4: Rotas e Controladores
     */
    private function testarRotasEControllers(): void
    {
        echo "\n>> 4. Testando Rotas e Retornos dos Controladores...\n";

        // Simula sessão de administrador
        $_SESSION['_auth_id'] = 1;
        $_SESSION['_auth_role'] = 'administrador';
        $_SESSION['_auth_nome'] = 'Administrador de Teste';

        // 4.1 DreController - index com regimes
        $_GET['ano'] = '2026';
        $_GET['data_inicio'] = '2026-10-01';
        $_GET['data_fim'] = '2026-10-31';

        $dreCtrl = new DreController();

        // Modo Competência
        $_GET['regime'] = 'competencia';
        ob_start();
        $dreCtrl->index();
        $htmlComp = ob_get_clean();
        $this->assert(str_contains($htmlComp, 'DRE Gerencial'), "DreController::index renderiza tela DRE");
        $this->assert(str_contains($htmlComp, 'Regime de Competência'), "View DRE exibe opção Regime de Competência");
        $this->assert(str_contains($htmlComp, 'Regime de Caixa'), "View DRE exibe opção Regime de Caixa");

        // Modo Caixa
        $_GET['regime'] = 'caixa';
        ob_start();
        $dreCtrl->index();
        $htmlCaixa = ob_get_clean();
        $this->assert(str_contains($htmlCaixa, 'Demonstrativo sob Regime de Caixa'), "View DRE no modo caixa exibe alerta informativo de regime");
        $this->assert(str_contains($htmlCaixa, 'Recargas:'), "View DRE no modo caixa exibe métricas de recarga");

        // 4.2 DreController::graficosJson com regime
        $_GET['regime'] = 'caixa';
        ob_start();
        $dreCtrl->graficosJson();
        $jsonStr = ob_get_clean();
        $json = json_decode($jsonStr, true);
        $this->assert($json['sucesso'] === true, "DreController::graficosJson retorna sucesso = true");
        $this->assert($json['regime'] === 'caixa', "DreController::graficosJson retorna regime = caixa");

        // 4.3 DreController::imprimir com regime caixa
        $_GET['regime'] = 'caixa';
        ob_start();
        $dreCtrl->imprimir();
        $htmlPrint = ob_get_clean();
        $this->assert(str_contains($htmlPrint, 'REGIME DE CAIXA (RECARGAS & ARRECADAÇÃO)'), "DRE Impressão exibe título Regime de Caixa no cabeçalho");
        $this->assert(str_contains($htmlPrint, 'Recargas Pix Online (Carteira Familiar)'), "DRE Impressão exibe linha de Recargas Pix Online");
        $this->assert(str_contains($htmlPrint, 'INFORMATIVO DE CONTROLE DA CARTEIRA'), "DRE Impressão exibe subseção informativa de confronto com consumo");

        // 4.4 RelatoriosController::arrecadacao tela web
        $relCtrl = new RelatoriosController();
        $_GET['data_inicio'] = '2026-10-01';
        $_GET['data_fim'] = '2026-10-05';
        ob_start();
        $relCtrl->arrecadacao();
        $htmlArrec = ob_get_clean();
        $this->assert(str_contains($htmlArrec, 'Arrecadação Financeira & Fluxo de Entradas'), "RelatoriosController::arrecadacao renderiza view principal");
        $this->assert(str_contains($htmlArrec, 'Total Arrecadado (Cash-In Real)'), "View de Arrecadação exibe stat-item de Cash-In");
        $this->assert(!str_contains($htmlArrec, 'Arrecadação por Canal de Pagamento'), "View de Arrecadação omite bloco de canais para manter tela limpa");
        $this->assert(!str_contains($htmlArrec, 'Controle de Custódia da Carteira'), "View de Arrecadação omite bloco de custódia para manter tela limpa");
        $this->assert(!str_contains($htmlArrec, 'Detalhamento Diário de Entradas e Consumo'), "View de Arrecadação omite listagem diária longa para manter tela limpa");
        $this->assert(preg_match('#href="[^"]*/relatorios/arrecadacao"[^>]*class="nav-link\s+active"#', $htmlArrec) === 1, "Sidebar marca Arrecadação & Entradas como menu ativo");
        $this->assert(preg_match('#href="[^"]*/relatorios"[^>]*class="nav-link\s+active"#', $htmlArrec) === 0, "Sidebar NÃO marca Relatórios Gerenciais como ativo quando na tela de Arrecadação");

        // 4.5 RelatoriosController::arrecadacaoImprimir tela A4
        ob_start();
        $relCtrl->arrecadacaoImprimir();
        $htmlArrecImp = ob_get_clean();
        $this->assert(str_contains($htmlArrecImp, 'DEMONSTRATIVO DE ARRECADAÇÃO FINANCEIRA'), "RelatoriosController::arrecadacaoImprimir renderiza espelho A4 oficial");
        $this->assert(str_contains($htmlArrecImp, 'Resumo por Canais de Entrada Financeira'), "Relatório A4 oficial preserva discriminação por canais");
        $this->assert(str_contains($htmlArrecImp, 'Detalhamento Cronológico Diário de Arrecadação e Consumo'), "Relatório A4 oficial preserva listagem diária detalhada");
    }
}

// Execução
$teste = new ArrecadacaoDreRegimesTest();
$teste->executar();
