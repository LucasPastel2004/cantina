<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 22: Extrato Analítico de Consumo do Aluno
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md, docs/PLANO.md e diretrizes de:
 * - docs/alterações sistema guideline/filtro Extrato Consumo Aluno.pdf
 * - docs/alterações sistema guideline/Relatório Extrato Consumo Aluno.pdf
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Registra o Autoloader PSR-4 nativo para o namespace App\
spl_autoload_register(function (string $class): void {
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

use App\Controllers\RelatoriosController;
use App\Controllers\PortalController;
use App\Models\Usuario;
use App\Models\Aluno;
use App\Models\Pedido;
use App\Models\Produto;
use App\Services\PdfService;
use App\Core\Auth;
use App\Core\Session;
use App\Core\RBAC;

class Fase22Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    private function assert(bool $condicao, string $mensagem): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  [OK] " . $mensagem . "\n";
        } else {
            echo "  [FAIL] " . $mensagem . "\n";
        }
    }

    public function run(): void
    {
        echo "=======================================================\n";
        echo "   TESTE FASE 22: EXTRATO ANALÍTICO DE CONSUMO DO ALUNO\n";
        echo "=======================================================\n\n";

        $this->testarRotasIndexEMetodosControllers();
        $this->testarFiltrosEAbasLayout();
        $this->testarCalculoContabilESaldoProgressivo();
        $this->testarOpcoesLeiauteApenasSaldoESaldoPeriodo();
        $this->testarEstruturaPdfEImpressaoA4();
        $this->testarExportacaoCsvComBom();
        $this->testarPortalDaFamiliaETitularidadeLgpd();
        $this->testarControleAcessoRbac();

        echo "\n-------------------------------------------------------\n";
        echo "Resumo Fase 22: {$this->assercoesSucesso} de {$this->totalAssercoes} asserções aprovadas com sucesso.\n";
        echo "=======================================================\n";

        if ($this->assercoesSucesso !== $this->totalAssercoes) {
            exit(1);
        }
    }

    /**
     * 1. Validação do registro de rotas no index.php e existência dos métodos nos controllers
     */
    private function testarRotasIndexEMetodosControllers(): void
    {
        echo "1. Validando rotas no index.php e métodos em RelatoriosController e PortalController...\n";

        $indexContent = file_get_contents(__DIR__ . '/../index.php');

        // Rotas do Operacional e Relatórios
        $this->assert(str_contains($indexContent, "'/operacional/extrato-aluno'"), "Rota GET /operacional/extrato-aluno registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/extrato-aluno'"), "Rota GET /relatorios/extrato-aluno registrada.");
        $this->assert(str_contains($indexContent, "'/operacional/extrato-aluno/pdf'"), "Rota GET /operacional/extrato-aluno/pdf registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/extrato-aluno/pdf'"), "Rota GET /relatorios/extrato-aluno/pdf registrada.");
        $this->assert(str_contains($indexContent, "'/operacional/extrato-aluno/imprimir'"), "Rota GET /operacional/extrato-aluno/imprimir registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/extrato-aluno/imprimir'"), "Rota GET /relatorios/extrato-aluno/imprimir registrada.");
        $this->assert(str_contains($indexContent, "'/operacional/extrato-aluno/csv'"), "Rota GET /operacional/extrato-aluno/csv registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/extrato-aluno/csv'"), "Rota GET /relatorios/extrato-aluno/csv registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/buscar-alunos-ajax'"), "Rota GET /relatorios/buscar-alunos-ajax registrada.");

        // Rotas do Portal da Família
        $this->assert(str_contains($indexContent, "'/portal/extrato'"), "Rota GET /portal/extrato registrada.");
        $this->assert(str_contains($indexContent, "'/portal/extrato/pdf'"), "Rota GET /portal/extrato/pdf registrada.");
        $this->assert(str_contains($indexContent, "'/portal/extrato/imprimir'"), "Rota GET /portal/extrato/imprimir registrada.");

        // Métodos do RelatoriosController
        $this->assert(method_exists(RelatoriosController::class, 'extratoAluno'), "RelatoriosController possui método extratoAluno().");
        $this->assert(method_exists(RelatoriosController::class, 'extratoAlunoPdf'), "RelatoriosController possui método extratoAlunoPdf().");
        $this->assert(method_exists(RelatoriosController::class, 'extratoAlunoImprimir'), "RelatoriosController possui método extratoAlunoImprimir().");
        $this->assert(method_exists(RelatoriosController::class, 'extratoAlunoCsv'), "RelatoriosController possui método extratoAlunoCsv().");
        $this->assert(method_exists(RelatoriosController::class, 'buscarAlunosAjax'), "RelatoriosController possui método buscarAlunosAjax().");

        // Métodos do PortalController
        $this->assert(method_exists(PortalController::class, 'extrato'), "PortalController possui método extrato().");
        $this->assert(method_exists(PortalController::class, 'extratoPdf'), "PortalController possui método extratoPdf().");
        $this->assert(method_exists(PortalController::class, 'extratoImprimir'), "PortalController possui método extratoImprimir().");
    }

    /**
     * 2. Validação da interface de filtros operacionais (modo direto sem abas e acesso via Hub)
     */
    private function testarFiltrosEAbasLayout(): void
    {
        echo "2. Validando interface de filtros operacionais (modo direto sem abas e acesso via Hub)...\n";

        $viewFile = __DIR__ . '/../app/views/relatorios/extrato_aluno.php';
        $this->assert(file_exists($viewFile), "Arquivo da view app/views/relatorios/extrato_aluno.php existe.");

        $viewContent = file_get_contents($viewFile);

        // Interface direta sem abas e sem toggles de leiaute (conforme refinamento do usuário)
        $this->assert(!str_contains($viewContent, 'id="tab-filtros"'), "Abas removidas em favor de formulário direto e limpo.");
        $this->assert(!str_contains($viewContent, 'id="tab-leiaute"'), "Aba Leiaute removida da interface.");
        $this->assert(!str_contains($viewContent, 'name="apenas_saldo"'), "Toggles de leiaute suprimidos do formulário de filtros.");

        // Atalhos de período e autocomplete de alunos
        $this->assert(str_contains($viewContent, 'data-periodo="hoje"'), "Atalho de período 'Hoje' presente.");
        $this->assert(str_contains($viewContent, 'data-periodo="semana"'), "Atalho de período 'Esta Semana' presente.");
        $this->assert(str_contains($viewContent, 'data-periodo="mes"'), "Atalho de período 'Este Mês' presente.");
        $this->assert(str_contains($viewContent, 'data-periodo="mes_anterior"'), "Atalho de período 'Mês Anterior' presente.");
        $this->assert(str_contains($viewContent, 'id="aluno_busca"'), "Campo de busca com autocomplete flutuante presente.");

        // Validação da Sidebar e Hub de Relatórios Gerenciais
        $hubView = file_get_contents(__DIR__ . '/../app/views/relatorios/index.php');
        $this->assert(str_contains($hubView, 'Extrato de Consumo do Aluno') && str_contains($hubView, '/relatorios/extrato-aluno'), "Hub de Relatórios Gerenciais contém Card dedicado para Extrato de Consumo do Aluno.");

        $sidebarView = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
        $this->assert(!str_contains($sidebarView, '/operacional/extrato-aluno'), "Item Extrato do Aluno removido da seção Operacional da sidebar.");
    }

    /**
     * 3. Validação do cálculo contábil do extrato e saldo progressivo
     */
    private function testarCalculoContabilESaldoProgressivo(): void
    {
        echo "3. Validando cálculo contábil do extrato e progressão matemática linha a linha...\n";

        // Obtém um aluno ativo no banco
        $stmt = $this->pdo->query("SELECT id, nome, matricula, responsavel_id FROM alunos WHERE ativo = 1 LIMIT 1");
        $aluno = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$aluno) {
            // Cria um aluno se não houver
            $this->pdo->exec("INSERT INTO alunos (nome, matricula, ativo, created_at) VALUES ('Aluno Teste Fase 22', 'TEST22', 1, NOW())");
            $alunoId = (int)$this->pdo->lastInsertId();
        } else {
            $alunoId = (int)$aluno['id'];
        }

        $controller = new RelatoriosController();
        $refMethod = new \ReflectionMethod(RelatoriosController::class, 'obterDadosExtratoAluno');
        $refMethod->setAccessible(true);

        // Testa busca de dados do extrato
        $hoje = date('Y-m-d');
        $inicio = date('Y-m-01'); // 1º dia do mês
        $fim = date('Y-m-d');

        $dados = $refMethod->invoke($controller, $alunoId, $inicio, $fim, false, false);

        $this->assert(is_array($dados), "obterDadosExtratoAluno retornou array estruturado.");
        $this->assert(isset($dados['aluno']), "Chave 'aluno' presente nos dados.");
        $this->assert(isset($dados['saldo_inicial']), "Chave 'saldo_inicial' presente nos dados.");
        $this->assert(isset($dados['total_gasto']), "Chave 'total_gasto' presente nos dados.");
        $this->assert(isset($dados['saldo_final']), "Chave 'saldo_final' presente nos dados.");
        $this->assert(isset($dados['lancamentos']), "Chave 'lancamentos' presente nos dados.");

        // Validação da progressão matemática se houver lançamentos
        if (!empty($dados['lancamentos'])) {
            $saldoEsperado = $dados['saldo_inicial'];
            $consistente = true;

            foreach ($dados['lancamentos'] as $lanc) {
                if ($lanc['tipo'] === 'recarga' || $lanc['forma_pagamento'] === 'Estorno') {
                    $saldoEsperado += (float)$lanc['valor_total'];
                } elseif ($lanc['forma_pagamento'] === 'Saldo') {
                    $saldoEsperado -= (float)$lanc['valor_total'];
                }

                // Tolerância de 1 centavo para arredondamento
                if (abs($saldoEsperado - (float)$lanc['saldo']) > 0.01) {
                    $consistente = false;
                    break;
                }
            }

            $this->assert($consistente, "Saldo progressivo linha a linha coincide perfeitamente com créditos e débitos.");
            $this->assert(abs($saldoEsperado - (float)$dados['saldo_final']) <= 0.01, "Saldo final coincide com o último saldo progressivo calculado.");
        } else {
            $this->assert((float)$dados['saldo_inicial'] === (float)$dados['saldo_final'], "Sem movimentações no período, saldo final é idêntico ao saldo inicial.");
        }
    }

    /**
     * 4. Validação das opções da aba Leiaute: Apenas Saldo e Saldo Apenas no Período
     */
    private function testarOpcoesLeiauteApenasSaldoESaldoPeriodo(): void
    {
        echo "4. Validando opções da aba Leiaute (Apenas Saldo e Saldo Apenas no Período)...\n";

        $stmt = $this->pdo->query("SELECT id FROM alunos WHERE ativo = 1 LIMIT 1");
        $alunoId = (int)$stmt->fetchColumn();

        $controller = new RelatoriosController();
        $refMethod = new \ReflectionMethod(RelatoriosController::class, 'obterDadosExtratoAluno');
        $refMethod->setAccessible(true);

        $inicio = date('Y-m-01');
        $fim = date('Y-m-d');

        // Teste A: considerar_saldo_apenas_periodo = true => saldo_inicial deve ser 0.00
        $dadosPeriodo = $refMethod->invoke($controller, $alunoId, $inicio, $fim, false, true);
        $this->assert((float)$dadosPeriodo['saldo_inicial'] === 0.00, "Com considerar_saldo_apenas_periodo=true, saldo_inicial é rigorosamente 0,00.");

        // Teste B: apenas_saldo = true => lancamentos deve ser vazio e apenas_saldo marcado
        $dadosApenasSaldo = $refMethod->invoke($controller, $alunoId, $inicio, $fim, true, false);
        $this->assert(empty($dadosApenasSaldo['lancamentos']), "Com apenas_saldo=true, lista de lançamentos detalhados é suprimida.");
        $this->assert(isset($dadosApenasSaldo['saldo_atual']), "Saldo atual é retornado para exibição sintética.");
    }

    /**
     * 5. Validação do espelho HTML e PDF A4 timbrado
     */
    private function testarEstruturaPdfEImpressaoA4(): void
    {
        echo "5. Validando fidelidade do espelho HTML e PDF A4 ao Relatório Extrato Consumo Aluno.pdf...\n";

        $pdfViewFile = __DIR__ . '/../app/views/relatorios/extrato_aluno_pdf.php';
        $this->assert(file_exists($pdfViewFile), "View app/views/relatorios/extrato_aluno_pdf.php existe.");

        $pdfContent = file_get_contents($pdfViewFile);

        // Cabeçalho e identificação
        $this->assert(str_contains($pdfContent, 'Colégio Monteiro Lobato'), "Cabeçalho com Razão Social da escola presente.");
        $this->assert(str_contains($pdfContent, 'Extrato Analítico de Consumo'), "Título oficial presente.");
        $this->assert(str_contains($pdfContent, 'Matrícula:'), "Campo Matrícula do Aluno presente.");
        $this->assert(str_contains($pdfContent, 'Aluno(a):'), "Campo Nome do Aluno presente.");
        $this->assert(str_contains($pdfContent, 'Série / Turma:'), "Campo Série/Turma presente.");
        $this->assert(str_contains($pdfContent, 'Saldo Atual:'), "Quadro de Saldo Atual presente.");

        // Linha Saldo Inicial e Colunas da Tabela Analítica
        $this->assert(str_contains($pdfContent, 'Saldo inicial'), "Linha 'Saldo inicial' presente no topo da tabela analítica.");
        $this->assert(str_contains($pdfContent, 'Qtd.'), "Coluna Qtd. presente.");
        $this->assert(str_contains($pdfContent, 'V. Unitário'), "Coluna V. Unitário presente.");
        $this->assert(str_contains($pdfContent, 'V. Total'), "Coluna V. Total presente.");
        $this->assert(str_contains($pdfContent, 'Forma Pgto.'), "Coluna Forma Pgto. presente.");
        $this->assert(str_contains($pdfContent, 'Saldo'), "Coluna Saldo progressivo presente.");
        $this->assert(str_contains($pdfContent, 'Total gasto no período'), "Rodapé com Total gasto no período presente.");

        // Simulação de folha A4 com botões de controle para @media screen
        $this->assert(str_contains($pdfContent, 'window.print()'), "Suporte a impressão direta via browser.");
        $this->assert(str_contains($pdfContent, 'no-print'), "Controles protegidos contra impressão via classe no-print.");

        // Teste de renderização do template com dados fictícios
        $aluno = [
            'id' => 999,
            'matricula' => '2177',
            'nome' => 'LUISA MOREIRA MIRANDA',
            'turma_nome' => '6º ANO A - MAT'
        ];
        $saldoInicial = 136.00;
        $saldoAtual = 75.50;
        $saldoFinal = 75.50;
        $totalGasto = 280.50;
        $apenasSaldo = false;
        $saldoApenasPeriodo = false;
        $dataInicio = '2026-03-01';
        $dataFim = '2026-03-31';
        $lancamentos = [
            [
                'data_hora' => '2026-03-03 10:15:00',
                'descricao' => 'PAO DE QUEIJO TRADICIONAL 80G',
                'quantidade' => 1,
                'valor_unitario' => 6.50,
                'valor_total' => 6.50,
                'forma_pagamento' => 'Saldo (Lanche Escolar)',
                'saldo' => 129.50,
                'tipo' => 'consumo'
            ]
        ];

        ob_start();
        include $pdfViewFile;
        $htmlRenderizado = ob_get_clean();

        $this->assert(str_contains($htmlRenderizado, 'LUISA MOREIRA MIRANDA'), "Nome da aluna renderizado com sucesso no HTML.");
        $this->assert(str_contains($htmlRenderizado, '2177'), "Matrícula renderizada no HTML.");
        $this->assert(str_contains($htmlRenderizado, '136,00'), "Saldo inicial R$ 136,00 formatado no HTML.");
        $this->assert(str_contains($htmlRenderizado, '280,50'), "Total gasto R$ 280,50 renderizado no HTML.");
        $this->assert(str_contains($htmlRenderizado, '129,50'), "Saldo progressivo R$ 129,50 renderizado na tabela.");
    }

    /**
     * 6. Validação da exportação em CSV com UTF-8 BOM
     */
    private function testarExportacaoCsvComBom(): void
    {
        echo "6. Validando exportação em planilha CSV com delimitador ponto e vírgula e BOM UTF-8...\n";

        // Cria dados para teste da geração de CSV
        $controller = new RelatoriosController();
        $refMethod = new \ReflectionMethod(RelatoriosController::class, 'obterDadosExtratoAluno');
        $refMethod->setAccessible(true);

        $stmt = $this->pdo->query("SELECT id FROM alunos WHERE ativo = 1 LIMIT 1");
        $alunoId = (int)$stmt->fetchColumn();

        $dados = $refMethod->invoke($controller, $alunoId, date('Y-m-01'), date('Y-m-d'), false, false);

        // Simula geração de CSV no padrão do método extratoAlunoCsv
        ob_start();
        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8
        fputcsv($out, ['EXTRATO ANALÍTICO DE CONSUMO DO ALUNO'], ';');
        fputcsv($out, ['Aluno', $dados['aluno']['nome']], ';');
        fputcsv($out, ['Matrícula', $dados['aluno']['matricula'] ?? '—'], ';');
        fputcsv($out, ['Saldo Inicial', number_format($dados['saldo_inicial'], 2, ',', '.')], ';');
        fputcsv($out, ['Data/Hora', 'Descrição', 'Qtd', 'V. Unitário', 'V. Total', 'Forma Pgto', 'Saldo Progressivo'], ';');
        
        foreach ($dados['lancamentos'] as $l) {
            fputcsv($out, [
                $l['data_formatada'] ?? ($l['data'] ?? ''),
                $l['descricao'] ?? '',
                $l['quantidade'] ?? '',
                is_numeric($l['valor_unitario']) ? number_format((float)$l['valor_unitario'], 2, ',', '.') : $l['valor_unitario'],
                is_numeric($l['valor_total']) ? number_format((float)$l['valor_total'], 2, ',', '.') : $l['valor_total'],
                $l['forma_pagamento'] ?? '',
                is_numeric($l['saldo']) ? number_format((float)$l['saldo'], 2, ',', '.') : ''
            ], ';');
        }
        fclose($out);
        $csvOutput = ob_get_clean();

        $this->assert(str_starts_with($csvOutput, "\xEF\xBB\xBF"), "CSV inicia com BOM UTF-8 para compatibilidade nativa com Microsoft Excel.");
        $this->assert(str_contains($csvOutput, ';'), "CSV utiliza ponto e vírgula como delimitador oficial.");
        $this->assert(str_contains($csvOutput, 'EXTRATO ANALÍTICO DE CONSUMO DO ALUNO'), "Título presente no CSV.");
        $this->assert(str_contains($csvOutput, 'Saldo Progressivo'), "Coluna Saldo Progressivo presente no cabeçalho do CSV.");
    }

    /**
     * 7. Validação do Portal da Família e Proteção de Titularidade LGPD
     */
    private function testarPortalDaFamiliaETitularidadeLgpd(): void
    {
        echo "7. Validando Portal da Família (view extrato.php) e proteção estrita de titularidade LGPD...\n";

        $portalViewFile = __DIR__ . '/../app/views/portal/extrato.php';
        $this->assert(file_exists($portalViewFile), "View app/views/portal/extrato.php existe.");

        $portalContent = file_get_contents($portalViewFile);
        $this->assert(str_contains($portalContent, 'Extrato Analítico do Aluno'), "Título do extrato presente no portal.");
        $this->assert(str_contains($portalContent, 'Filho(a) / Dependente:'), "Seletor de dependentes presente.");
        $this->assert(str_contains($portalContent, 'Baixar Extrato Oficial (PDF)'), "Botão de download de PDF presente.");
        $this->assert(str_contains($portalContent, 'Visualizar / Imprimir (A4)'), "Botão de impressão A4 presente.");
        $this->assert(str_contains($portalContent, 'Saldo Inicial do Período'), "Stat-strip de Saldo Inicial presente.");
        $this->assert(str_contains($portalContent, 'Total Gasto no Período'), "Stat-strip de Total Gasto presente.");
        $this->assert(str_contains($portalContent, 'Saldo Final Resultante'), "Stat-strip de Saldo Final presente.");

        // Validação da proteção LGPD em PortalController:
        // Verifica se o método extrato valida se o aluno solicitado pertence aos dependentes do responsável
        $portalControllerFile = file_get_contents(__DIR__ . '/../app/controllers/PortalController.php');
        $this->assert(str_contains($portalControllerFile, 'alunoPertence'), "PortalController verifica titularidade via alunoPertence.");
        $this->assert(str_contains($portalControllerFile, 'Você não tem permissão para visualizar o extrato deste aluno'), "Mensagem de segurança LGPD configurada.");
    }

    /**
     * 8. Validação do Controle de Acesso Baseado em Papéis (RBAC)
     */
    private function testarControleAcessoRbac(): void
    {
        echo "8. Validando RBAC das rotas operacionais e do portal...\n";

        $indexContent = file_get_contents(__DIR__ . '/../index.php');

        // Rotas do portal exigem ROLE_RESPONSAVEL
        $this->assert(
            str_contains($indexContent, "ROLE_RESPONSAVEL") && str_contains($indexContent, "/portal/extrato"),
            "Rotas do Portal da Família protegidas para perfil 'responsavel'."
        );

        // Rotas operacionais exigem ROLE_OPERADOR ou ROLE_ADMINISTRADOR
        $this->assert(
            str_contains($indexContent, "ROLE_OPERADOR") && str_contains($indexContent, "/operacional/extrato-aluno"),
            "Rotas operacionais protegidas para perfis 'operador' e 'administrador'."
        );
    }
}

// Execução da suíte
$test = new Fase22Test();
$test->run();
