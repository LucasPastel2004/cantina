<?php
/**
 * Suíte de Testes Automatizados — Fase 24
 * Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * 
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/DespesaOperacional.php';
require_once __DIR__ . '/../app/models/Dre.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/DespesasController.php';
require_once __DIR__ . '/../app/controllers/DreController.php';

class Fase24Test
{
    private \PDO $pdo;
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function executarTodos(): void
    {
        echo "======================================================================\n";
        echo "   SUÍTE DE TESTES AUTOMATIZADOS — FASE 24 (DRE & DESPESAS)\n";
        echo "======================================================================\n\n";

        $this->testMigration029();
        $this->testModelDespesaCrud();
        $this->testModelDespesaValidacoes();
        $this->testModelDespesaPagar();
        $this->testModelDreCompetencia();
        $this->testModelDreComparativoAnual();
        $this->testModelDreDadosGraficos();
        $this->testRotasDespesasController();
        $this->testRotasDreController();
        $this->testGotenbergCompilacaoDrePdf();
        $this->testIntegridadeSidebarEHub();

        echo "\n----------------------------------------------------------------------\n";
        echo "RESULTADO FINAL: {$this->assercoesSucesso}/{$this->totalAssercoes} asserções aprovadas com sucesso!\n";
        if ($this->assercoesSucesso === $this->totalAssercoes) {
            echo "STATUS: [PASS] TODOS OS TESTES DA FASE 24 FORAM HOMOLOGADOS COM 100% DE SUCESSO!\n";
        } else {
            echo "STATUS: [FAIL] Alguma asserção falhou. Verifique os erros acima.\n";
            exit(1);
        }
        echo "----------------------------------------------------------------------\n";
    }

    private function assert(bool $condicao, string $mensagem): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  ✓ {$mensagem}\n";
        } else {
            echo "  ✗ [FALHA] {$mensagem}\n";
        }
    }

    private function testMigration029(): void
    {
        echo "[1] Testando Migration 029 (Estrutura de Tabelas & Colunas)...\n";

        // Verifica existência da tabela despesas_operacionais
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'despesas_operacionais'");
        $this->assert($stmt->rowCount() > 0, "Tabela 'despesas_operacionais' existe no banco de dados.");

        // Verifica colunas obrigatórias
        $colunas = $this->pdo->query("SHOW COLUMNS FROM despesas_operacionais")->fetchAll(\PDO::FETCH_COLUMN);
        $colunasEsperadas = [
            'id', 'categoria', 'subcategoria', 'descricao', 'valor',
            'competencia_mes_ano', 'data_vencimento', 'data_pagamento',
            'status', 'forma_pagamento', 'documento_referencia', 'observacoes',
            'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at'
        ];
        foreach ($colunasEsperadas as $col) {
            $this->assert(in_array($col, $colunas, true), "Coluna '{$col}' existe em despesas_operacionais.");
        }

        // Verifica coluna preco_custo em produtos
        $colunasProd = $this->pdo->query("SHOW COLUMNS FROM produtos LIKE 'preco_custo'")->fetchAll();
        $this->assert(!empty($colunasProd), "Coluna 'preco_custo' existe na tabela 'produtos'.");
    }

    private function testModelDespesaCrud(): void
    {
        echo "\n[2] Testando Modelo DespesaOperacional (CRUD & Soft Delete)...\n";

        $model = new \App\Models\DespesaOperacional();

        // 1. Criar
        $dados = [
            'categoria'            => \App\Models\DespesaOperacional::CATEGORIA_ADMINISTRATIVA,
            'subcategoria'         => 'Teste Unidade',
            'descricao'            => 'Conta de Luz Provisória de Teste',
            'valor'                => 150.75,
            'competencia_mes_ano'  => '2026-10',
            'data_vencimento'      => '2026-10-25',
            'status'               => \App\Models\DespesaOperacional::STATUS_PENDENTE,
            'forma_pagamento'      => 'Pix',
            'documento_referencia' => 'TEST-NF-100',
            'observacoes'          => 'Despesa criada em teste automatizado.',
        ];

        $id = $model->criar($dados, 1);
        $this->assert($id > 0, "Despesa criada com sucesso retornando ID {$id}.");

        // 2. Buscar por ID
        $despesa = $model->buscarPorId($id);
        $this->assert($despesa !== null, "Despesa #{$id} localizada com sucesso.");
        $this->assert((float)$despesa['valor'] === 150.75, "Valor da despesa gravado corretamente (150.75).");
        $this->assert($despesa['categoria'] === 'administrativa', "Categoria gravada corretamente.");

        // 3. Atualizar
        $dados['descricao'] = 'Conta de Luz Provisória Alterada';
        $dados['valor'] = 175.50;
        $atualizado = $model->atualizar($id, $dados, 1);
        $this->assert($atualizado === true, "Despesa #{$id} atualizada com sucesso.");

        $despesaAtt = $model->buscarPorId($id);
        $this->assert($despesaAtt['descricao'] === 'Conta de Luz Provisória Alterada', "Descrição atualizada refletida no banco.");
        $this->assert((float)$despesaAtt['valor'] === 175.50, "Valor atualizado refletido no banco.");

        // 4. Soft Delete
        $excluido = $model->excluir($id, 1);
        $this->assert($excluido === true, "Despesa #{$id} excluída logicamente com sucesso.");

        $despesaExcluida = $model->buscarPorId($id);
        $this->assert($despesaExcluida === null, "Despesa excluída não é retornada nas buscas padrão.");

        // Confirma que o registro ainda existe fisicamente com deleted_at preenchido
        $stmtRaw = $this->pdo->prepare("SELECT deleted_at FROM despesas_operacionais WHERE id = :id");
        $stmtRaw->execute([':id' => $id]);
        $deletedAt = $stmtRaw->fetchColumn();
        $this->assert(!empty($deletedAt), "Registro físico preservado com deleted_at = '{$deletedAt}' (integridade histórica).");

        // Limpeza definitiva do teste
        $this->pdo->exec("DELETE FROM despesas_operacionais WHERE id = {$id}");
    }

    private function testModelDespesaValidacoes(): void
    {
        echo "\n[3] Testando Validações de Domínio em DespesaOperacional...\n";

        $model = new \App\Models\DespesaOperacional();

        // 1. Descrição curta
        try {
            $model->criar([
                'descricao' => 'Oi',
                'valor'     => 10.00,
                'categoria' => 'administrativa',
                'competencia_mes_ano' => '2026-10',
                'data_vencimento' => '2026-10-15',
            ]);
            $this->assert(false, "Deveria falhar com descrição curta.");
        } catch (\InvalidArgumentException $e) {
            $this->assert(str_contains($e->getMessage(), 'descrição'), "Validação de descrição curta disparou exceção correta.");
        }

        // 2. Valor negativo ou zero
        try {
            $model->criar([
                'descricao' => 'Despesa Invalida',
                'valor'     => -50.00,
                'categoria' => 'administrativa',
                'competencia_mes_ano' => '2026-10',
                'data_vencimento' => '2026-10-15',
            ]);
            $this->assert(false, "Deveria falhar com valor negativo.");
        } catch (\InvalidArgumentException $e) {
            $this->assert(str_contains($e->getMessage(), 'valor'), "Validação de valor negativo disparou exceção correta.");
        }

        // 3. Categoria inválida
        try {
            $model->criar([
                'descricao' => 'Despesa Invalida',
                'valor'     => 50.00,
                'categoria' => 'categoria_inexistente_123',
                'competencia_mes_ano' => '2026-10',
                'data_vencimento' => '2026-10-15',
            ]);
            $this->assert(false, "Deveria falhar com categoria inválida.");
        } catch (\InvalidArgumentException $e) {
            $this->assert(str_contains($e->getMessage(), 'categoria'), "Validação de categoria inválida disparou exceção correta.");
        }

        // 4. Competência com formato inválido
        try {
            $model->criar([
                'descricao' => 'Despesa Invalida',
                'valor'     => 50.00,
                'categoria' => 'administrativa',
                'competencia_mes_ano' => '2026/13',
                'data_vencimento' => '2026-10-15',
            ]);
            $this->assert(false, "Deveria falhar com competência inválida.");
        } catch (\InvalidArgumentException $e) {
            $this->assert(str_contains($e->getMessage(), 'competência'), "Validação de competência com formato inválido disparou exceção correta.");
        }
    }

    private function testModelDespesaPagar(): void
    {
        echo "\n[4] Testando Quitação / Pagamento de Despesa...\n";

        $model = new \App\Models\DespesaOperacional();

        $id = $model->criar([
            'categoria'            => \App\Models\DespesaOperacional::CATEGORIA_MANUTENCAO,
            'descricao'            => 'Troca de Borracha Freezer Teste',
            'valor'                => 85.00,
            'competencia_mes_ano'  => '2026-10',
            'data_vencimento'      => '2026-10-30',
            'status'               => \App\Models\DespesaOperacional::STATUS_PENDENTE,
        ], 1);

        $this->assert($id > 0, "Despesa pendente criada com ID {$id}.");

        $sucesso = $model->marcarComoPaga($id, '2026-10-20', 'Pix', 1);
        $this->assert($sucesso === true, "Despesa #{$id} marcada como paga.");

        $d = $model->buscarPorId($id);
        $this->assert($d['status'] === 'pago', "Status atualizado para 'pago'.");
        $this->assert($d['data_pagamento'] === '2026-10-20', "Data de pagamento gravada corretamente.");
        $this->assert($d['forma_pagamento'] === 'Pix', "Forma de pagamento registrada corretamente.");

        // Limpeza
        $this->pdo->exec("DELETE FROM despesas_operacionais WHERE id = {$id}");
    }

    private function testModelDreCompetencia(): void
    {
        echo "\n[5] Testando Apuração de DRE Mensal em Modelo Dre...\n";

        $dreModel = new \App\Models\Dre();
        $dados = $dreModel->calcularCompetencia('2026-09');

        $this->assert(isset($dados['receita_bruta']), "DRE apura 'receita_bruta'.");
        $this->assert(isset($dados['receita_online']), "DRE apura 'receita_online'.");
        $this->assert(isset($dados['receita_balcao']), "DRE apura 'receita_balcao'.");
        $this->assert(isset($dados['total_deducoes']), "DRE apura 'total_deducoes'.");
        $this->assert(isset($dados['receita_liquida']), "DRE apura 'receita_liquida'.");
        $this->assert(isset($dados['cmv']), "DRE apura 'cmv'.");
        $this->assert(isset($dados['lucro_bruto']), "DRE apura 'lucro_bruto'.");
        $this->assert(isset($dados['total_despesas']), "DRE apura 'total_despesas'.");
        $this->assert(isset($dados['resultado_liquido']), "DRE apura 'resultado_liquido'.");

        // Validação da matemática contábil
        $calculoRecLiq = $dados['receita_bruta'] - $dados['total_deducoes'];
        $this->assert(abs($dados['receita_liquida'] - max(0, $calculoRecLiq)) < 0.01, "Matemática contábil exata: Receita Líquida = Receita Bruta - Deduções.");

        $calculoLucroBruto = $dados['receita_liquida'] - $dados['cmv'];
        $this->assert(abs($dados['lucro_bruto'] - $calculoLucroBruto) < 0.01, "Matemática contábil exata: Lucro Bruto = Receita Líquida - CMV.");

        $calculoResLiq = $dados['lucro_bruto'] - $dados['total_despesas'];
        $this->assert(abs($dados['resultado_liquido'] - $calculoResLiq) < 0.01, "Matemática contábil exata: Resultado Líquido = Lucro Bruto - Despesas Operacionais.");
    }

    private function testModelDreComparativoAnual(): void
    {
        echo "\n[6] Testando Comparativo Anual Mês a Mês (12 Meses + Totais)...\n";

        $dreModel = new \App\Models\Dre();
        $ano = 2026;
        $dreAno = $dreModel->calcularAnoComparativo($ano);

        $this->assert(isset($dreAno['ano']) && $dreAno['ano'] === $ano, "Ano de exercício configurado para {$ano}.");
        $this->assert(count($dreAno['meses']) === 12, "Matriz possui exatamente 12 meses (Janeiro a Dezembro).");
        $this->assert(isset($dreAno['totais']), "Totais consolidados do ano presentes no DRE.");

        // Verifica se cada mês possui dados
        for ($m = 1; $m <= 12; $m++) {
            $this->assert(isset($dreAno['meses'][$m]['competencia']), "Mês {$m} possui estrutura contábil calculada.");
        }

        // Verifica coerência da soma anual
        $somaReceitas = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $somaReceitas += $dreAno['meses'][$m]['receita_bruta'];
        }
        $this->assert(abs($dreAno['totais']['receita_bruta'] - $somaReceitas) < 0.05, "Total anual de receita bruta coincide com a soma dos 12 meses.");
    }

    private function testModelDreDadosGraficos(): void
    {
        echo "\n[7] Testando Estruturação de Dados para Gráficos Interativos...\n";

        $dreModel = new \App\Models\Dre();
        $graficos = $dreModel->obterDadosGraficos(2026);

        $this->assert(isset($graficos['labels']) && count($graficos['labels']) === 12, "Gráfico possui 12 labels mensais (Jan a Dez).");
        $this->assert(isset($graficos['receitas']) && count($graficos['receitas']) === 12, "Série de receitas possui 12 pontos.");
        $this->assert(isset($graficos['despesas']) && count($graficos['despesas']) === 12, "Série de despesas possui 12 pontos.");
        $this->assert(isset($graficos['lucros']) && count($graficos['lucros']) === 12, "Série de lucros possui 12 pontos.");
        $this->assert(isset($graficos['online']) && isset($graficos['balcao']), "Séries online e balcão disponíveis para gráfico de rosca.");
        $this->assert(isset($graficos['despesas_por_cat']) && is_array($graficos['despesas_por_cat']), "Despesas por categoria agrupadas para gráfico de barras.");
    }

    private function testRotasDespesasController(): void
    {
        echo "\n[8] Testando DespesasController (Interface & Resumo)...\n";

        // Simula sessão de administrador
        \App\Core\Session::start();
        \App\Core\Session::set('_auth_user_id', 1);
        \App\Core\Session::set('_auth_role', \App\Core\RBAC::ROLE_ADMINISTRADOR);

        $controller = new \App\Controllers\DespesasController();

        $_GET['competencia'] = '2026-09';
        ob_start();
        $controller->index();
        $html = ob_get_clean();

        $this->assert(str_contains($html, 'Despesas Operacionais da Cantina'), "View de despesas renderizou título oficial.");
        $this->assert(str_contains($html, 'stat-strip'), "Stat-strip de métricas presente na tela de despesas.");
        $this->assert(str_contains($html, 'modalDespesa'), "Modal de lançamento de despesas presente no HTML.");
        $this->assert(str_contains($html, 'modalPagar'), "Modal de quitação rápida presente no HTML.");
    }

    private function testRotasDreController(): void
    {
        echo "\n[9] Testando DreController (Index, Gráficos JSON e CSV)...\n";

        \App\Core\Session::start();
        \App\Core\Session::set('_auth_user_id', 1);
        \App\Core\Session::set('_auth_role', \App\Core\RBAC::ROLE_ADMINISTRADOR);

        $controller = new \App\Controllers\DreController();

        // 1. Index
        $_GET['ano'] = '2026';
        ob_start();
        $controller->index();
        $html = ob_get_clean();

        $this->assert(str_contains($html, 'Demonstração do Resultado do Exercício — Ano 2026'), "View do DRE renderizou título com o ano corrente.");
        $this->assert(str_contains($html, 'RECEITA BRUTA DE VENDAS'), "Tabela comparativa contém (+) RECEITA BRUTA DE VENDAS.");
        $this->assert(str_contains($html, 'RECEITA LÍQUIDA DE VENDAS'), "Tabela comparativa contém (=) RECEITA LÍQUIDA DE VENDAS.");
        $this->assert(str_contains($html, 'CUSTO DAS MERCADORIAS VENDIDAS (CMV)'), "Tabela comparativa contém (-) CUSTO DAS MERCADORIAS VENDIDAS.");
        $this->assert(str_contains($html, 'LUCRO BRUTO'), "Tabela comparativa contém (=) LUCRO BRUTO.");
        $this->assert(str_contains($html, 'DESPESAS OPERACIONAIS'), "Tabela comparativa contém (-) DESPESAS OPERACIONAIS.");
        $this->assert(str_contains($html, 'RESULTADO LÍQUIDO DO EXERCÍCIO'), "Tabela comparativa contém (=) RESULTADO LÍQUIDO DO EXERCÍCIO.");
        $this->assert(str_contains($html, 'chart.min.js'), "Inclusão da biblioteca local chart.min.js confirmada na view.");

        // 2. Imprimir (A4)
        ob_start();
        $controller->imprimir();
        $htmlPrint = ob_get_clean();
        $this->assert(str_contains($htmlPrint, 'A4 landscape'), "Espelho A4 de impressão configurado com A4 landscape (Paisagem).");
        $this->assert(str_contains($htmlPrint, 'table-dre-doc'), "Grade tipo Excel presente no documento de impressão.");
    }

    private function testGotenbergCompilacaoDrePdf(): void
    {
        echo "\n[10] Testando Compilação de PDF A4 do DRE via Gotenberg (Chromium Headless)...\n";

        $dreModel = new \App\Models\Dre();
        $dreAno = $dreModel->calcularAnoComparativo(2026);
        $config = \App\Models\Configuracao::obter();

        $caminhoLogo = __DIR__ . '/../assets/img/logo-colegio.jpg';
        $logoBase64 = file_exists($caminhoLogo) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($caminhoLogo)) : '';

        $html = \App\Services\PdfService::renderView('financeiro/dre_pdf', [
            'ano'         => 2026,
            'dre'         => $dreAno,
            'config'      => $config,
            'logoBase64'  => $logoBase64,
            'dataEmissao' => date('d/m/Y H:i:s'),
            'mesesAbrev'  => \App\Models\Dre::MESES_ABREV,
            'mesesPt'     => \App\Models\Dre::MESES_PT,
            'isPdf'       => true,
            'baseUrl'     => 'http://localhost:8080',
        ]);

        $pdfBinario = \App\Services\PdfService::htmlToPdf($html, [
            'landscape'         => true,
            'preferCssPageSize' => true,
            'emulatedMediaType' => 'print',
        ]);

        $this->assert($pdfBinario !== null, "Gotenberg compilou o HTML do DRE em binário PDF com sucesso.");
        $tamanho = strlen($pdfBinario ?? '');
        $this->assert($tamanho > 15000, "Arquivo PDF gerado possui tamanho consistente ({$tamanho} bytes > 15 KB).");

        // Salva temporariamente para inspeção óptica se necessário
        file_put_contents(__DIR__ . '/../storage/dre_gerencial_2026_test.pdf', $pdfBinario);
        $this->assert(file_exists(__DIR__ . '/../storage/dre_gerencial_2026_test.pdf'), "Arquivo de homologação salvo em storage/dre_gerencial_2026_test.pdf.");
        @unlink(__DIR__ . '/../storage/dre_gerencial_2026_test.pdf');
    }

    private function testIntegridadeSidebarEHub(): void
    {
        echo "\n[11] Testando Presença de Links na Sidebar e Hub de Relatórios...\n";

        $sidebarHtml = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
        $this->assert(str_contains($sidebarHtml, '/financeiro/despesas'), "Link para 'Despesas da Cantina' presente na sidebar.");
        $this->assert(str_contains($sidebarHtml, '/financeiro/dre'), "Link para 'DRE Gerencial' presente na sidebar.");

        $hubHtml = file_get_contents(__DIR__ . '/../app/views/relatorios/index.php');
        $this->assert(str_contains($hubHtml, 'DRE Gerencial & Despesas'), "Card oficial 'DRE Gerencial & Despesas' presente na Central de Relatórios.");
        $this->assert(str_contains($hubHtml, '/financeiro/dre'), "Link para /financeiro/dre presente no card do Hub.");
    }
}

$suite = new Fase24Test();
$suite->executarTodos();
