<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Relatórios Individuais de Desconto em Folha por Professor/Colaborador
 * 
 * Valida:
 * 1. Geração de PDF A4 Timbrado Individual por Professor (PdfService::gerarRelatorioDescontoFolhaIndividualPdf)
 * 2. Geração de Espelho HTML para Impressão Direta do Demonstrativo Individual (PdfService::gerarEspelhoDescontoFolhaIndividualHtml)
 * 3. Geração em Lote de Relatórios Individuais (PdfService::gerarRelatorioDescontoFolhaIndividualLotePdf)
 * 4. Consulta e Extração de Dados Individuais no RelatoriosController
 * 5. Integridade Visual dos Elementos na View desconto_folha.php (Filtro Instantâneo, Botões por Professor, Lote PDF)
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

use App\Core\Auth;
use App\Core\Session;
use App\Services\PdfService;
use App\Controllers\RelatoriosController;
use App\Models\Colaborador;

class DescontoFolhaIndividualTest
{
    private int $pass = 0;
    private int $fail = 0;

    private function assert(bool $condicao, string $mensagem): void
    {
        if ($condicao) {
            $this->pass++;
            echo "  [OK] " . $mensagem . "\n";
        } else {
            $this->fail++;
            echo "  [FAIL] " . $mensagem . "\n";
        }
    }

    public function run(): void
    {
        echo "=======================================================\n";
        echo "   TESTE: RELATÓRIOS INDIVIDUAIS DE DESCONTO EM FOLHA\n";
        echo "=======================================================\n\n";

        // Inicializa sessão de administrador para testes de autorização
        Session::start();
        Session::set('_auth_id', 1);
        Session::set('_auth_user', 'admin');
        Session::set('_auth_nome', 'Administrador Geral');
        Session::set('_auth_role', 'administrador');
        Session::set('_auth_logged', true);

        $this->testarExtracaoDadosIndividuaisController();
        $this->testarGeracaoPdfIndividual();
        $this->testarEspelhoHtmlIndividual();
        $this->testarGeracaoPdfLoteIndividuais();
        $this->testarIntegridadeViewDescontoFolha();

        echo "\n-------------------------------------------------------\n";
        echo "Resumo: {$this->pass} aprovados, {$this->fail} falhas.\n";
        echo "=======================================================\n";

        if ($this->fail > 0) {
            exit(1);
        }
    }

    /**
     * 1. Teste de extração dos dados individuais de consumo do colaborador
     */
    private function testarExtracaoDadosIndividuaisController(): void
    {
        echo "1. Testando extração de dados individuais no RelatoriosController...\n";

        $controller = new RelatoriosController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('obterDadosDescontoFolhaIndividual');
        $method->setAccessible(true);

        // Busca o primeiro colaborador ativo
        $pdo = \App\Core\Model::getPdo();
        $stmt = $pdo->query("SELECT id, nome, matricula_funcional FROM colaboradores WHERE deleted_at IS NULL LIMIT 1");
        $colab = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assert(!empty($colab), "Colaborador encontrado para teste.");
        if (!$colab) {
            return;
        }

        $dados = $method->invoke($controller, (int)$colab['id'], '2026-09');

        $this->assert($dados !== null, "Dados individuais do colaborador retornados com sucesso.");
        $this->assert(isset($dados['colaborador']), "Chave 'colaborador' presente no array de dados.");
        $this->assert(isset($dados['itens']), "Chave 'itens' presente no array de dados.");
        $this->assert(isset($dados['totais']), "Chave 'totais' presente no array de dados.");
        $this->assert(isset($dados['totais']['total_desconto']), "Totalizador 'total_desconto' calculado.");
        $this->assert(isset($dados['totais']['total_lanches']), "Totalizador 'total_lanches' calculado.");
        $this->assert(isset($dados['totais']['total_compras']), "Totalizador 'total_compras' calculado.");
    }

    /**
     * 2. Teste da geração do PDF A4 Timbrado Individual
     */
    private function testarGeracaoPdfIndividual(): void
    {
        echo "\n2. Testando geração do PDF A4 Timbrado Individual por Professor...\n";

        // Cria dados mockados com múltiplos itens para testar paginação e assinaturas
        $itensMock = [];
        for ($i = 1; $i <= 8; $i++) {
            $itensMock[] = [
                'venda_id'       => 100 + $i,
                'created_at'     => '2026-09-0' . min(9, $i) . ' 10:15:00',
                'turno'          => 'matutino',
                'operador_nome'  => 'Maria Caixa',
                'item_id'        => 200 + $i,
                'quantidade'     => 1,
                'preco_unitario' => 6.50,
                'subtotal'       => 6.50,
                'produto_nome'   => 'Pão de Queijo Mineiro ' . $i,
                'produto_codigo' => 'SAL0' . $i
            ];
        }

        $dadosMock = [
            'colaborador' => [
                'id'                  => 1,
                'nome'                => 'Professor Roberto da Silva',
                'matricula_funcional' => 'PROF001',
                'cargo'               => 'Professor de Biologia',
                'foto_url'            => ''
            ],
            'competencia'       => 'Setembro de 2026',
            'competencia_chave' => '2026-09',
            'operador'          => 'Administração Geral',
            'emissao'           => '25/09/2026 15:30:00',
            'baseUrl'           => '',
            'itens'             => $itensMock,
            'totais'            => [
                'total_desconto' => 52.00,
                'total_lanches'  => 8,
                'total_compras'  => 8
            ]
        ];

        $pdfBinario = PdfService::gerarRelatorioDescontoFolhaIndividualPdf($dadosMock);

        $this->assert(!empty($pdfBinario), "PDF individual gerado com sucesso.");
        $this->assert(str_starts_with($pdfBinario, '%PDF-1.4'), "Cabeçalho oficial PDF 1.4 detectado.");
        $this->assert(str_ends_with(trim($pdfBinario), '%%EOF'), "Marcador final %%EOF presente.");
        $this->assert(str_contains($pdfBinario, 'DEMONSTRATIVO INDIVIDUAL DE DESCONTO EM FOLHA'), "Título institucional presente no PDF.");
        $this->assert(str_contains($pdfBinario, 'PROF001'), "Matrícula do professor presente no PDF.");
        $this->assert(str_contains($pdfBinario, 'Assinatura do Colaborador'), "Bloco de assinatura do colaborador presente no PDF.");
        $this->assert(str_contains($pdfBinario, 'Visto da Cantina'), "Bloco de visto da diretoria presente no PDF.");
    }

    /**
     * 3. Teste do Espelho HTML para visualização e impressão direta
     */
    private function testarEspelhoHtmlIndividual(): void
    {
        echo "\n3. Testando Espelho HTML de impressão do Demonstrativo Individual...\n";

        $dadosMock = [
            'colaborador' => [
                'id'                  => 2,
                'nome'                => 'Professora Ana Paula Santos',
                'matricula_funcional' => 'PROF002',
                'cargo'               => 'Professora de Língua Portuguesa',
                'foto_url'            => ''
            ],
            'competencia'       => 'Setembro de 2026',
            'competencia_chave' => '2026-09',
            'operador'          => 'Administrador Geral',
            'emissao'           => '25/09/2026 15:35:00',
            'baseUrl'           => '',
            'itens'             => [
                [
                    'venda_id'       => 105,
                    'created_at'     => '2026-09-10 15:40:00',
                    'turno'          => 'vespertino',
                    'operador_nome'  => 'João Atendente',
                    'item_id'        => 210,
                    'quantidade'     => 2,
                    'preco_unitario' => 5.00,
                    'subtotal'       => 10.00,
                    'produto_nome'   => 'Suco Natural de Laranja 300ml',
                    'produto_codigo' => 'BEB02'
                ]
            ],
            'totais' => [
                'total_desconto' => 10.00,
                'total_lanches'  => 2,
                'total_compras'  => 1
            ]
        ];

        $html = PdfService::gerarEspelhoDescontoFolhaIndividualHtml($dadosMock);

        $this->assert(!empty($html), "Espelho HTML gerado com sucesso.");
        $this->assert(str_contains($html, '<!DOCTYPE html>'), "Declaração DOCTYPE presente.");
        $this->assert(str_contains($html, 'Demonstrativo Individual para RH'), "Título da barra de ações presente.");
        $this->assert(str_contains($html, 'window.print()'), "Botão com gatilho window.print() presente.");
        $this->assert(str_contains($html, 'Professora Ana Paula Santos'), "Nome da professora presente no espelho.");
        $this->assert(str_contains($html, 'PROF002'), "Matrícula funcional presente no espelho.");
        $this->assert(str_contains($html, 'Termo de Ciência do Colaborador'), "Termo formal de autorização presente.");
    }

    /**
     * 4. Teste de geração em lote de folhas individuais em um único PDF
     */
    private function testarGeracaoPdfLoteIndividuais(): void
    {
        echo "\n4. Testando geração em Lote de Relatórios Individuais (PDF com múltiplas folhas)...\n";

        $colabsDetalhados = [];
        for ($c = 1; $c <= 3; $c++) {
            $colabsDetalhados[] = [
                'colaborador' => [
                    'id'                  => $c,
                    'nome'                => 'Professor ' . $c,
                    'matricula_funcional' => 'MAT00' . $c,
                    'cargo'               => 'Docente',
                    'foto_url'            => ''
                ],
                'competencia'       => 'Setembro de 2026',
                'competencia_chave' => '2026-09',
                'operador'          => 'RH Central',
                'emissao'           => '25/09/2026 15:40:00',
                'baseUrl'           => '',
                'itens'             => [
                    [
                        'venda_id'       => 300 + $c,
                        'created_at'     => '2026-09-15 09:30:00',
                        'turno'          => 'matutino',
                        'operador_nome'  => 'Operador',
                        'item_id'        => 400 + $c,
                        'quantidade'     => 1,
                        'preco_unitario' => 8.00,
                        'subtotal'       => 8.00,
                        'produto_nome'   => 'Sanduíche Natural de Frango',
                        'produto_codigo' => 'LAN01'
                    ]
                ],
                'totais' => [
                    'total_desconto' => 8.00,
                    'total_lanches'  => 1,
                    'total_compras'  => 1
                ]
            ];
        }

        $dadosLote = [
            'competencia'              => 'Setembro de 2026',
            'competencia_chave'        => '2026-09',
            'colaboradores_detalhados' => $colabsDetalhados
        ];

        $pdfLote = PdfService::gerarRelatorioDescontoFolhaIndividualLotePdf($dadosLote);

        $this->assert(!empty($pdfLote), "PDF do lote individual gerado com sucesso.");
        $this->assert(str_starts_with($pdfLote, '%PDF-1.4'), "Cabeçalho PDF 1.4 válido no lote.");
        $this->assert(str_ends_with(trim($pdfLote), '%%EOF'), "Finalizador %%EOF íntegro no lote.");
        $this->assert(str_contains($pdfLote, 'MAT001'), "Matrícula do Professor 1 presente no lote.");
        $this->assert(str_contains($pdfLote, 'MAT002'), "Matrícula do Professor 2 presente no lote.");
        $this->assert(str_contains($pdfLote, 'MAT003'), "Matrícula do Professor 3 presente no lote.");
    }

    /**
     * 5. Teste de integridade visual e funcional dos elementos da View desconto_folha.php
     */
    private function testarIntegridadeViewDescontoFolha(): void
    {
        echo "\n5. Testando integridade dos elementos na view desconto_folha.php...\n";

        $viewPath = __DIR__ . '/../app/views/relatorios/desconto_folha.php';
        $content = file_get_contents($viewPath);

        $this->assert(str_contains($content, 'id="filtroProfessor"'), "Campo de busca instantânea #filtroProfessor presente.");
        $this->assert(str_contains($content, 'filtrarProfessores(this.value)'), "Gatilho de filtragem por digitação presente.");
        $this->assert(str_contains($content, 'Baixar Todos os Individuais (Lote PDF)'), "Botão de exportação em lote presente no topo.");
        $this->assert(str_contains($content, 'relatorios/desconto-folha/lote-pdf'), "Link para rota de lote PDF presente.");
        $this->assert(str_contains($content, 'relatorios/desconto-folha/individual-pdf'), "Link para PDF Individual por linha presente.");
        $this->assert(str_contains($content, 'relatorios/desconto-folha/individual-imprimir'), "Link para Impressão Individual por linha presente.");
        $this->assert(str_contains($content, 'id="btnModalPdfIndividual"'), "Botão de PDF Individual presente no modal de detalhes.");
        $this->assert(str_contains($content, 'id="btnModalImprimirIndividual"'), "Botão de Impressão presente no modal de detalhes.");
    }
}

// Execução da suíte
$test = new DescontoFolhaIndividualTest();
$test->run();
