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

        // 1.3 DRE Unificado (Padrão Oficial — Vendas + Conciliação de Carteira + Caixa Real Sem Duplicidade)
        $dreUnif = $dreModel->calcularPeriodo($inicio, $fim);
        $this->assert($dreUnif['regime'] === 'unificado', "DRE opera por padrão no regime unificado");
        $this->assert(isset($dreUnif['arrecadacao_bruta']), "DRE Unificado apura arrecadacao_bruta");
        $this->assert(isset($dreUnif['arrecadacao_liquida']), "DRE Unificado apura arrecadacao_liquida");
        $this->assert(isset($dreUnif['resultado_caixa']), "DRE Unificado apura resultado_caixa (Resultado Real em Conta)");
        $this->assert(isset($dreUnif['recargas_pix_online']), "DRE Unificado segrega recargas_pix_online");
        $this->assert(isset($dreUnif['recargas_pix_pdv']), "DRE Unificado segrega recargas_pix_pdv");

        // Prova Matemática Anti-Duplicidade:
        // Receita Bruta de Vendas + Variação Líquida de Carteira (Recargas - Gasto Saldo) == Arrecadação Bruta na Conta
        $ponteConciliacao = round((float)$dreUnif['receita_bruta'] + (float)$dreUnif['diferenca_recarga_consumo'], 2);
        $this->assert(
            abs($ponteConciliacao - (float)$dreUnif['arrecadacao_bruta']) < 0.01,
            "Ponte Contábil Unificada: Receita de Vendas + (Recargas - Gasto Saldo) fecha exatamente com Arrecadação Real na Conta (Zero Duplicidade)"
        );

        // Resultado Financeiro em Conta == Resultado Operacional (Vendas) + Variação Líquida de Carteira
        $caixaEsperado = round((float)$dreUnif['resultado_liquido'] + (float)$dreUnif['diferenca_recarga_consumo'], 2);
        $this->assert(
            abs($caixaEsperado - (float)$dreUnif['resultado_caixa']) < 0.01,
            "Fechamento Unificado: Resultado Financeiro em Conta = Lucro Operacional de Vendas + Variação de Saldo em Carteira"
        );

        // 1.4 Ano comparativo nos regimes
        $anoUnif = $dreModel->calcularAnoComparativo(2026);
        $this->assert(count($anoUnif['meses']) === 12, "DRE Anual Unificado possui 12 meses");
        $this->assert(isset($anoUnif['totais']['arrecadacao_liquida']), "DRE Anual Unificado totaliza arrecadacao_liquida");
        $this->assert(isset($anoUnif['totais']['resultado_caixa']), "DRE Anual Unificado totaliza resultado_caixa");

        $anoComp = $dreModel->calcularAnoComparativo(2026, 'competencia');
        $this->assert(count($anoComp['meses']) === 12, "DRE Anual Competência possui 12 meses");

        $anoCaixa = $dreModel->calcularAnoComparativo(2026, 'caixa');
        $this->assert(count($anoCaixa['meses']) === 12, "DRE Anual Caixa possui 12 meses");
        $this->assert(isset($anoCaixa['totais']['total_recargas']), "DRE Anual Caixa totaliza total_recargas");
        $this->assert(isset($anoCaixa['totais']['consumo_saldo_total']), "DRE Anual Caixa totaliza consumo_saldo_total");

        // 1.5 Dados de Gráficos no modelo unificado
        $grafUnif = $dreModel->obterDadosGraficos(2026, $inicio, $fim);
        $this->assert(count($grafUnif['labels']) === 12, "Gráficos Unificados possuem 12 rótulos");
        $this->assert(count($grafUnif['arrecadacoes']) === 12, "Gráficos Unificados incluem série de arrecadações em conta");
        $this->assert(count($grafUnif['resultados_caixa']) === 12, "Gráficos Unificados incluem série de resultado real em conta");
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
        $this->assert(
            !in_array('Vendas PDV Saldo', array_column($dados['canais'], 'canal')),
            "Vendas PDV com saldo NÃO entram nos canais de arrecadação (sem duplicidade com recargas)"
        );
        $this->assert(
            abs((float)$dados['total_arrecadado_bruto'] - ($somaBruta + (float)$dados['total_consumo_saldo'])) > 0.001 || (float)$dados['total_consumo_saldo'] == 0.0,
            "Total Arrecadado (Cash-In) exclui rigorosamente consumo de lanches com saldo"
        );

        $varEsperada = (float)$dados['total_recargas'] - (float)$dados['total_consumo_saldo'];
        $this->assert(
            abs((float)$dados['variacao_carteira'] - $varEsperada) < 0.01,
            "Variação da Carteira é exatamente Total de Recargas - Total Consumido em Lanches"
        );

        // Validação dos Canais
        $this->assert(count($dados['canais']) === 5, "Relatório contém os 5 canais oficiais de arrecadação");
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
     * Teste 3: Espelho A4 para Print to PDF Nativo
     */
    private function testarPdfArrecadacaoCompilacao(): void
    {
        echo "\n>> 3. Testando Espelho A4 do Demonstrativo Financeiro - Canais de Entrada (Print to PDF Nativo)...\n";
        $ctrl = new RelatoriosController();

        $dados = $ctrl->obterDadosArrecadacao('2026-10-01', '2026-10-05');
        $dados['baseUrl'] = '';
        $dados['isPdf'] = true;

        $html = PdfService::renderView('relatorios/arrecadacao_pdf', $dados);
        $this->assert(str_contains($html, 'COLÉGIO MONTEIRO LOBATO'), "HTML gerado contém razão social da instituição");
        $this->assert(str_contains($html, 'DEMONSTRATIVO FINANCEIRO - CANAIS DE ENTRADA'), "HTML gerado contém título oficial do relatório");
        $this->assert(str_contains($html, 'table-doc-excel'), "HTML gerado utiliza classe de tabela Excel homologada");
        $this->assert(str_contains($html, 'Recargas Pix Online (Portal da Família)'), "HTML gerado contém linha de Recargas Pix Online");
        $this->assert(str_contains($html, 'Recarga PDV Pix'), "HTML gerado contém linha de Recarga PDV Pix");
        $this->assert(str_contains($html, 'Recarga PDV Dinheiro'), "HTML gerado contém linha de Recarga PDV Dinheiro");
        $this->assert(str_contains($html, 'Vendas PDV Pix'), "HTML gerado contém linha de Vendas PDV Pix");
        $this->assert(str_contains($html, 'Vendas PDV Dinheiro'), "HTML gerado contém linha de Vendas PDV Dinheiro");
        $this->assert(strlen($html) > 10000, "Espelho HTML gerado possui conteúdo substancial (> 10 KB): " . strlen($html) . " bytes");
        $this->assert(PdfService::htmlToPdf($html) === null, "PdfService::htmlToPdf retorna null garantindo emissão nativa Print to PDF do navegador");
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

        // 4.1 DreController - index no Modelo Unificado Sob Demanda (Sem gráficos fixos na tela)
        unset($_GET['regime']);
        $_GET['ano'] = '2026';
        $_GET['data_inicio'] = '2026-10-01';
        $_GET['data_fim'] = '2026-10-31';

        $dreCtrl = new DreController();

        ob_start();
        $dreCtrl->index();
        $htmlDre = ob_get_clean();
        $this->assert(str_contains($htmlDre, 'Painel Financeiro & DRE Gerencial Unificado'), "DreController::index renderiza tela do DRE Gerencial Unificado");
        $this->assert(!str_contains($htmlDre, 'Seletor de Regime Contábil'), "View DRE unificou os dois métodos e removeu o seletor manual de regimes");
        $this->assert(str_contains($htmlDre, 'Imprimir / Salvar PDF (A4)'), "View DRE substituiu botão filtrar pelo botão Imprimir / Salvar PDF (A4) no filtro");
        $this->assert(str_contains($htmlDre, 'Relatório Texto (DRE Contábil A4)'), "Menu do botão Imprimir oferece opção de Relatório Texto");
        $this->assert(str_contains($htmlDre, 'Relatório Gráfico (DRE Visual + Produtos Rentáveis A4)'), "Menu do botão Imprimir oferece opção de Relatório Gráfico");
        $this->assert(str_contains($htmlDre, 'Exportar Planilha (CSV)'), "Menu do botão Imprimir oferece opção de Exportar Planilha (CSV)");
        $this->assert(!str_contains($htmlDre, 'chartEvolucaoMensal'), "Gráfico em X vermelho (Evolução Mensal) excluído conforme solicitado");
        $this->assert(!str_contains($htmlDre, 'chartProdutosRentaveis'), "Gráfico em X vermelho (Curva de Produtos) excluído conforme solicitado");
        $this->assert(!str_contains($htmlDre, 'btnCsvTopo'), "Botão extra de CSV no topo removido conforme solicitado");
        $this->assert(!str_contains($htmlDre, 'Gerar Relatório Texto (A4)'), "Botões duplicados no rodapé informativo removidos conforme solicitado");

        // 4.2 DreController::graficosJson no modelo unificado
        ob_start();
        $dreCtrl->graficosJson();
        $jsonStr = ob_get_clean();
        $json = json_decode($jsonStr, true);
        $this->assert($json['sucesso'] === true, "DreController::graficosJson retorna sucesso = true");
        $this->assert($json['regime'] === 'unificado', "DreController::graficosJson retorna regime = unificado por padrão");

        // 4.3 DreController::imprimir (Relatório Texto A4 Paisagem)
        ob_start();
        $dreCtrl->imprimir();
        $htmlPrint = ob_get_clean();
        $this->assert(str_contains($htmlPrint, 'DEMONSTRAÇÃO DO RESULTADO DO EXERCÍCIO UNIFICADO (DRE)'), "DRE Impressão Texto exibe título do DRE Unificado no cabeçalho");
        $this->assert(str_contains($htmlPrint, 'Consumo de Saldo Online (Pedidos Web)'), "DRE Impressão Texto exibe linha de Consumo de Saldo Online");
        $this->assert(str_contains($htmlPrint, 'Recargas Pix Online (Portal Família)'), "DRE Impressão Texto exibe linha de Recargas Pix Online");
        $this->assert(str_contains($htmlPrint, 'Abatimento do Saldo Gasto em Vendas'), "DRE Impressão Texto exibe linha de abatimento anti-duplicidade do saldo gasto");
        $this->assert(str_contains($htmlPrint, 'RESULTADO FINANCEIRO EM CONTA'), "DRE Impressão Texto exibe linha final de Resultado Financeiro em Conta");

        // 4.4 DreController::grafico (Relatório Gráfico — DRE Visual Unificado + Lista dos Produtos Mais Rentáveis)
        ob_start();
        $dreCtrl->grafico();
        $htmlGrafico = ob_get_clean();
        $this->assert(str_contains($htmlGrafico, 'DEMONSTRAÇÃO VISUAL DO RESULTADO DO EXERCÍCIO (DRE GRÁFICO UNIFICADO)'), "Relatório Gráfico exibe título oficial do DRE Visual");
        $this->assert(str_contains($htmlGrafico, 'chartCanaisVenda'), "Relatório Gráfico inclui gráfico circular de Vendas Online vs Presencial");
        $this->assert(str_contains($htmlGrafico, 'Estrutura Visual do DRE & Despesas por Categoria'), "Relatório Gráfico une canais de venda e despesas operacionais no DRE Visual");
        $this->assert(str_contains($htmlGrafico, '2. Detalhamento dos Produtos Mais Rentáveis do Período'), "Relatório Gráfico exibe embaixo a lista de Detalhamento dos Produtos Mais Rentáveis");
        $this->assert(!str_contains($htmlGrafico, 'chartEvolucaoMensal'), "Relatório Gráfico não contém gráfico de evolução mensal excluído");
        $this->assert(!str_contains($htmlGrafico, 'chartProdutosRentaveis'), "Relatório Gráfico não contém gráfico de barras de produtos excluído (apenas a lista detalhada embaixo)");

        // 4.4 RelatoriosController::arrecadacao tela web
        $relCtrl = new RelatoriosController();
        $_GET['data_inicio'] = '2026-10-01';
        $_GET['data_fim'] = '2026-10-05';
        ob_start();
        $relCtrl->arrecadacao();
        $htmlArrec = ob_get_clean();
        $this->assert(str_contains($htmlArrec, 'formFiltroArrecadacao'), "RelatoriosController::arrecadacao renderiza view principal");
        $this->assert(str_contains($htmlArrec, 'Imprimir / Salvar PDF (A4)'), "View de Arrecadação exibe botão de emissão direta no formulário de período");
        $this->assert(!str_contains($htmlArrec, 'stat-strip-item'), "View de Arrecadação removeu cartões de métricas do rodapé conforme solicitado");
        $this->assert(!str_contains($htmlArrec, 'Arrecadação por Canal de Pagamento'), "View de Arrecadação omite bloco de canais para manter tela limpa");
        $this->assert(!str_contains($htmlArrec, 'Controle de Custódia da Carteira'), "View de Arrecadação omite bloco de custódia para manter tela limpa");
        $this->assert(!str_contains($htmlArrec, 'Detalhamento Diário de Entradas e Consumo'), "View de Arrecadação omite listagem diária longa para manter tela limpa");
        $this->assert(preg_match('#href="[^"]*/relatorios/arrecadacao"[^>]*class="nav-link\s+active"#', $htmlArrec) === 1, "Sidebar marca Arrecadação & Entradas como menu ativo");
        $this->assert(preg_match('#href="[^"]*/relatorios"[^>]*class="nav-link\s+active"#', $htmlArrec) === 0, "Sidebar NÃO marca Relatórios Gerenciais como ativo quando na tela de Arrecadação");

        // 4.5 RelatoriosController::arrecadacaoImprimir tela A4
        ob_start();
        $relCtrl->arrecadacaoImprimir();
        $htmlArrecImp = ob_get_clean();
        $this->assert(str_contains($htmlArrecImp, 'DEMONSTRATIVO FINANCEIRO - CANAIS DE ENTRADA'), "RelatoriosController::arrecadacaoImprimir renderiza espelho A4 oficial");
        $this->assert(str_contains($htmlArrecImp, 'Síntese dos Indicadores Financeiros do Período'), "Relatório A4 oficial contém tabela própria de síntese dos indicadores");
        $this->assert(str_contains($htmlArrecImp, 'Total Arrecadado (Cash-In Real)'), "Relatório A4 oficial exibe métrica de Total Arrecadado na tabela de síntese");
        $this->assert(str_contains($htmlArrecImp, 'Recargas Pix Online (Portal da Família)'), "Relatório A4 oficial exibe Recargas Pix Online na tabela de síntese");
        $this->assert(str_contains($htmlArrecImp, 'Recarga PDV Pix'), "Relatório A4 oficial exibe Recarga PDV Pix na tabela de síntese");
        $this->assert(str_contains($htmlArrecImp, 'Recarga PDV Dinheiro'), "Relatório A4 oficial exibe Recarga PDV Dinheiro na tabela de síntese");
        $this->assert(str_contains($htmlArrecImp, 'Vendas PDV Pix'), "Relatório A4 oficial exibe Vendas PDV Pix na tabela de síntese");
        $this->assert(str_contains($htmlArrecImp, 'Vendas PDV Dinheiro'), "Relatório A4 oficial exibe Vendas PDV Dinheiro na tabela de síntese");
        $this->assert(str_contains($htmlArrecImp, 'Resumo por Canais de Entrada Financeira'), "Relatório A4 oficial preserva discriminação por canais");
        $this->assert(!str_contains($htmlArrecImp, 'Detalhamento Cronológico Diário'), "Relatório A4 oficial retirou a Seção 4 focando exclusivamente nos canais de entrada e carteira");
    }
}

// Execução
$teste = new ArrecadacaoDreRegimesTest();
$teste->executar();
