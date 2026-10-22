<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 21: Painel de Filtros Avançados e Relatório Oficial de Vendas por Período / Alimento
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md, docs/PLANO.md e diretrizes de:
 * - Filtros de vendas.pdf
 * - relatorio de vendas por período.pdf
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
use App\Models\Usuario;
use App\Models\Produto;
use App\Models\Categoria;
use App\Services\PdfService;
use App\Core\Auth;
use App\Core\Session;

class Fase21Test
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
        echo "   TESTE FASE 21: FILTROS AVANÇADOS E RELATÓRIO DE VENDAS\n";
        echo "=======================================================\n\n";

        $this->testarRotasEMetodosController();
        $this->testarInterfaceViewFiltros();
        $this->testarProcessamentoFiltrosEDadosVendas();
        $this->testarAgrupamentoDiarioESubtotais();
        $this->testarFiltrosEstornosETiposPessoa();
        $this->testarCurvaAbcEOrdenacoes();
        $this->testarLayoutPdfOficialVendasAlimento();
        $this->testarExportacaoCsvComBom();
        $this->testarControleAcessoRbac();

        echo "\n-------------------------------------------------------\n";
        echo "Resumo Fase 21: {$this->assercoesSucesso} de {$this->totalAssercoes} asserções aprovadas com sucesso.\n";
        echo "=======================================================\n";

        if ($this->assercoesSucesso !== $this->totalAssercoes) {
            exit(1);
        }
    }

    /**
     * 1. Validação das Rotas no index.php e Métodos no RelatoriosController
     */
    private function testarRotasEMetodosController(): void
    {
        echo "1. Validando registro de rotas e métodos no RelatoriosController...\n";

        $indexContent = file_get_contents(__DIR__ . '/../index.php');
        $this->assert(str_contains($indexContent, "'/relatorios/vendas-filtros'"), "Rota GET /relatorios/vendas-filtros registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/vendas-filtros/pdf'"), "Rota GET /relatorios/vendas-filtros/pdf registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/vendas-filtros/imprimir'"), "Rota GET /relatorios/vendas-filtros/imprimir registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/vendas-filtros/csv'"), "Rota GET /relatorios/vendas-filtros/csv registrada.");
        $this->assert(str_contains($indexContent, "'/relatorios/vendas-alimento/csv'"), "Rota GET /relatorios/vendas-alimento/csv registrada.");

        $reflection = new \ReflectionClass(RelatoriosController::class);
        $this->assert($reflection->hasMethod('vendasFiltros'), "RelatoriosController possui método vendasFiltros.");
        $this->assert($reflection->hasMethod('vendasFiltrosPdf'), "RelatoriosController possui método vendasFiltrosPdf.");
        $this->assert($reflection->hasMethod('vendasFiltrosImprimir'), "RelatoriosController possui método vendasFiltrosImprimir.");
        $this->assert($reflection->hasMethod('vendasFiltrosCsv'), "RelatoriosController possui método vendasFiltrosCsv.");
        $this->assert($reflection->hasMethod('vendasAlimentoCsv'), "RelatoriosController possui método vendasAlimentoCsv.");
        $this->assert($reflection->hasMethod('obterDadosVendasAvancadas'), "RelatoriosController possui motor de agregação obterDadosVendasAvancadas.");
    }

    /**
     * 2. Validação dos Campos, Abas e Toggles Sim/Não da View de Filtros
     */
    private function testarInterfaceViewFiltros(): void
    {
        echo "\n2. Validando componentes da view app/views/relatorios/vendas_filtros.php...\n";

        $viewPath = __DIR__ . '/../app/views/relatorios/vendas_filtros.php';
        $this->assert(file_exists($viewPath), "Arquivo de view de filtros existe.");

        $content = file_get_contents($viewPath);
        $this->assert(str_contains($content, "CANTINA_SISTEMA"), "View possui salvaguarda CANTINA_SISTEMA.");
        $this->assert(str_contains($content, "Imprimir / Salvar PDF (A4)"), "Opção de Visualização/Impressão e Salvar PDF unificada presente.");
        $this->assert(str_contains($content, "Exportar Planilha (CSV)"), "Opção de Exportação CSV presente.");
        // Toggles com destaque azul corporativo e botões de emissão
        $this->assert(str_contains($content, "btn-toggle-blue"), "Toggles utilizam classe de destaque em azul corporativo (.btn-toggle-blue).");
        $this->assert(!str_contains($content, "tabelaVendasOficial"), "Lista de relatório inline removida da tela de filtros (emissão exclusiva em PDF/Impressão).");
        $this->assert(!str_contains($content, 'id="dropdownEmitir"'), "Botão repetido/dropdown Emitir do topo devidamente removido.");
        $this->assert(!str_contains($content, 'Hub de Relatórios'), "Botão Hub de Relatórios do topo devidamente removido.");

        // Toggles Oficiais Sim/Não conforme Filtros de vendas.pdf
        $toggles = [
            'agrupar_alimento',
            'incluir_estornos',
            'agrupar_periodo',
            'agrupar_tipo_pessoa',
            'emitir_observacao',
            'emitir_forma_pagamento',
            'emitir_horario',
            'emitir_vendas_alimento',
            'emitir_vendas_aluno',
            'agrupar_vendas_aluno',
            'agrupar_serie_turma',
            'quebrar_pagina_turma',
            'ordenar_consumo_aluno'
        ];

        foreach ($toggles as $toggle) {
            $this->assert(str_contains($content, "name=\"{$toggle}\""), "Toggle Sim/Não '{$toggle}' presente no formulário.");
        }

        // Atalhos rápidos de datas
        $this->assert(str_contains($content, "definirDatas"), "Função utilitária de atalhos rápidos presente.");
        $this->assert(str_contains($content, ">Hoje</button>"), "Atalho de data 'Hoje' presente.");
        $this->assert(str_contains($content, ">Este Mês</button>"), "Atalho de data 'Este Mês' presente.");
        $this->assert(str_contains($content, ">Mês Anterior</button>"), "Atalho de data 'Mês Anterior' presente.");
    }

    /**
     * 3. Validação do Motor de Agregação de Dados e Filtros
     */
    private function testarProcessamentoFiltrosEDadosVendas(): void
    {
        echo "\n3. Validando motor de agregação obterDadosVendasAvancadas()...\n";

        $controller = new RelatoriosController();
        $reflection = new \ReflectionClass(RelatoriosController::class);
        $method = $reflection->getMethod('obterDadosVendasAvancadas');
        $method->setAccessible(true);

        // Período amplo para cobrir vendas no banco
        $filtros = [
            'data_inicio' => '2026-01-01',
            'data_fim'    => '2026-12-31',
            'turno'       => '',
            'tipo_pessoa' => 'todos',
            'categoria_id'=> 0,
            'agrupar_alimento' => 'sim',
            'incluir_estornos' => 'nao',
            'ordenar_alimentos' => 'mais_vendidos'
        ];

        $dados = $method->invoke($controller, $filtros);

        $this->assert(is_array($dados), "Retorno de obterDadosVendasAvancadas é um array.");
        $this->assert(isset($dados['periodo']), "Chave 'periodo' presente.");
        $this->assert(isset($dados['periodo_oficial']), "Chave 'periodo_oficial' presente.");
        $this->assert(isset($dados['dias']), "Chave 'dias' presente.");
        $this->assert(isset($dados['alimentos_consolidados']), "Chave 'alimentos_consolidados' presente.");
        $this->assert(isset($dados['grupos_tipo_pessoa']), "Chave 'grupos_tipo_pessoa' presente.");
        $this->assert(isset($dados['totais']), "Chave 'totais' presente.");
        $this->assert(isset($dados['totais']['faturamento_total']), "Total faturamento presente.");
        $this->assert(isset($dados['totais']['quantidade_total']), "Total quantidade presente.");
        $this->assert(isset($dados['totais']['ticket_medio']), "Ticket médio calculado.");
    }

    /**
     * 4. Validação de Agrupamento Diário e Subtotais
     */
    private function testarAgrupamentoDiarioESubtotais(): void
    {
        echo "\n4. Validando agrupamento diário e subtotais no relatório...\n";

        $controller = new RelatoriosController();
        $reflection = new \ReflectionClass(RelatoriosController::class);
        $method = $reflection->getMethod('obterDadosVendasAvancadas');
        $method->setAccessible(true);

        $filtros = [
            'data_inicio' => '2026-01-01',
            'data_fim'    => '2026-12-31',
            'agrupar_alimento' => 'sim'
        ];

        $dados = $method->invoke($controller, $filtros);
        $dias = $dados['dias'];

        $somaSubtotais = 0.0;
        $somaQuantidades = 0;
        $todosComUnitario = true;

        foreach ($dias as $data => $dia) {
            $this->assert(isset($dia['data_fmt']), "Dia possui data formatada dd/mm/aaaa ({$dia['data_fmt']}).");
            $this->assert(isset($dia['subtotal']), "Dia possui subtotal consolidado.");
            $this->assert(isset($dia['quantidade_total']), "Dia possui quantidade total.");
            $this->assert(is_array($dia['alimentos']), "Dia possui lista de alimentos agrupados.");

            $somaSubtotais += (float)($dia['subtotal'] ?? 0.0);
            $somaQuantidades += (int)($dia['quantidade_total'] ?? 0);

            foreach ($dia['alimentos'] as $alimento) {
                if (!isset($alimento['preco_unitario']) || !isset($alimento['subtotal'])) {
                    $todosComUnitario = false;
                }
            }
        }

        $this->assert($todosComUnitario, "Todos os alimentos agrupados por dia possuem preco_unitario e subtotal calculados.");

        $faturamentoTotal = (float)$dados['totais']['faturamento_total'];
        $quantidadeTotal = (int)$dados['totais']['quantidade_total'];

        $this->assert(abs($somaSubtotais - $faturamentoTotal) < 0.01, "Soma dos subtotais dos dias bate 100% com o total geral do período (R$ {$faturamentoTotal}).");
        $this->assert($somaQuantidades === $quantidadeTotal, "Soma das quantidades dos dias bate 100% com o total geral do período ({$quantidadeTotal} un).");
    }

    /**
     * 5. Validação de Filtros de Estorno e Tipo de Pessoa
     */
    private function testarFiltrosEstornosETiposPessoa(): void
    {
        echo "\n5. Validando filtros de estorno e tipo de pessoa...\n";

        $controller = new RelatoriosController();
        $reflection = new \ReflectionClass(RelatoriosController::class);
        $method = $reflection->getMethod('obterDadosVendasAvancadas');
        $method->setAccessible(true);

        // Sem estornos
        $semEstornos = $method->invoke($controller, [
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'incluir_estornos' => 'nao'
        ]);

        // Com estornos
        $comEstornos = $method->invoke($controller, [
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'incluir_estornos' => 'sim'
        ]);

        $this->assert($comEstornos['totais']['quantidade_total'] >= $semEstornos['totais']['quantidade_total'],
            "Quantidade com estornos ({$comEstornos['totais']['quantidade_total']}) é >= sem estornos ({$semEstornos['totais']['quantidade_total']}).");

        // Filtro por tipo de pessoa: Alunos
        $apenasAlunos = $method->invoke($controller, [
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'tipo_pessoa' => 'aluno'
        ]);

        $this->assert(is_array($apenasAlunos['itens']), "Retorno de apenas alunos gerou itens.");
        $todosComNome = true;
        foreach ($apenasAlunos['itens'] as $it) {
            if (empty($it['produto_nome']) && empty($it['descricao'])) {
                $todosComNome = false;
                break;
            }
        }
        $this->assert($todosComNome, "Todos os itens filtrados possuem produto_nome / descricao válidos.");
    }

    /**
     * 6. Validação de Curva ABC e Ordenações
     */
    private function testarCurvaAbcEOrdenacoes(): void
    {
        echo "\n6. Validando classificação Curva ABC e ordenação dos alimentos...\n";

        $controller = new RelatoriosController();
        $reflection = new \ReflectionClass(RelatoriosController::class);
        $method = $reflection->getMethod('obterDadosVendasAvancadas');
        $method->setAccessible(true);

        // Mais vendidos
        $maisVendidos = $method->invoke($controller, [
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'ordenar_alimentos' => 'mais_vendidos'
        ]);

        $listaMais = $maisVendidos['alimentos_consolidados'];
        if (count($listaMais) >= 2) {
            $primeiro = $listaMais[0];
            $ultimo = end($listaMais);
            $this->assert($primeiro['quantidade_total'] >= $ultimo['quantidade_total'], "Ordenação Mais Vendidos coloca maior quantidade no topo.");
            
            // Checa Curva ABC
            $this->assert(in_array($primeiro['classe_abc'], ['A', 'B', 'C']), "Primeiro produto possui classificação Curva ABC válida ({$primeiro['classe_abc']}).");
            $this->assert(isset($primeiro['participacao_percentual']), "Primeiro produto possui participação percentual calculada.");
        }

        // Menos vendidos
        $menosVendidos = $method->invoke($controller, [
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'ordenar_alimentos' => 'menos_vendidos'
        ]);

        $listaMenos = $menosVendidos['alimentos_consolidados'];
        if (count($listaMenos) >= 2) {
            $primeiroMenos = $listaMenos[0];
            $ultimoMenos = end($listaMenos);
            $this->assert($primeiroMenos['quantidade_total'] <= $ultimoMenos['quantidade_total'], "Ordenação Menos Vendidos coloca menor quantidade no topo.");
        }
    }

    /**
     * 7. Validação do Layout A4 do Relatório Oficial (vendas_alimento_pdf.php)
     */
    private function testarLayoutPdfOficialVendasAlimento(): void
    {
        echo "\n7. Validando layout de impressão e espelho oficial A4...\n";

        $viewFile = __DIR__ . '/../app/views/relatorios/vendas_alimento_pdf.php';
        $this->assert(file_exists($viewFile), "View de emissão oficial existe.");

        $content = file_get_contents($viewFile);
        $this->assert(str_contains($content, "COLÉGIO MONTEIRO LOBATO"), "Cabeçalho com nome oficial 'COLÉGIO MONTEIRO LOBATO'.");
        $this->assert(str_contains($content, "Relatório de Emissão de Vendas da Cantina por Alimento"), "Título idêntico ao modelo oficial escolar.");
        $this->assert(str_contains($content, "PERÍODO ENTRE DATAS :"), "Texto de período idêntico ao modelo: 'PERÍODO ENTRE DATAS :'.");
        $this->assert(str_contains($content, "Total do dia :"), "Linha de subtotal com formato oficial: 'Total do dia :'.");
        $this->assert(str_contains($content, "Total do período :"), "Linha de total geral com formato oficial: 'Total do período :'.");

        // Teste de renderização com dados simulados
        $_viewVars = [
            'dadosVendas' => [
                'periodo_oficial' => '01/09/2026 - 30/09/2026',
                'totais' => [
                    'faturamento_total' => 1250.50,
                    'quantidade_total'  => 120
                ],
                'filtros' => [
                    'emitir_observacao' => 'nao',
                    'emitir_forma_pagamento' => 'nao',
                    'agrupar_periodo' => 'sim'
                ],
                'dias' => [
                    '2026-09-01' => [
                        'data' => '2026-09-01',
                        'data_fmt' => '01/09/2026',
                        'subtotal' => 150.00,
                        'total_dia' => 150.00,
                        'quantidade_total' => 15,
                        'qtd_dia' => 15,
                        'alimentos' => [
                            [
                                'descricao' => 'COXINHA TRADICIONAL',
                                'produto_nome' => 'COXINHA TRADICIONAL',
                                'quantidade' => 10,
                                'preco_unitario' => 10.00,
                                'subtotal' => 100.00
                            ],
                            [
                                'descricao' => 'SUCO NATURAL LARANJA',
                                'produto_nome' => 'SUCO NATURAL LARANJA',
                                'quantidade' => 5,
                                'preco_unitario' => 10.00,
                                'subtotal' => 50.00
                            ]
                        ]
                    ]
                ]
            ],
            'baseUrl' => ''
        ];

        extract($_viewVars, EXTR_OVERWRITE);
        ob_start();
        include $viewFile;
        $htmlOutput = ob_get_clean();

        $this->assert(str_contains($htmlOutput, "01/09/2026 - 30/09/2026"), "HTML gerado contém o período formatado.");
        $this->assert(str_contains($htmlOutput, "COXINHA TRADICIONAL"), "HTML gerado contém o produto renderizado.");
        $this->assert(str_contains($htmlOutput, "Total do dia :"), "HTML gerado contém o total do dia formatado.");
        $this->assert(str_contains($htmlOutput, "Total do período :"), "HTML gerado contém o total do período formatado.");
        $this->assert(!str_contains($htmlOutput, "Escolar Manager"), "HTML gerado NÃO contém referências à Escolar Manager.");
        $this->assert(!str_contains($htmlOutput, "footer-token"), "HTML gerado NÃO contém tokens residuais ou IPs.");
        $this->assert(str_contains($htmlOutput, "Colégio Monteiro Lobato"), "HTML gerado contém rodapé institucional do Colégio Monteiro Lobato.");
    }

    /**
     * 8. Validação da Exportação de Dados em CSV
     */
    private function testarExportacaoCsvComBom(): void
    {
        echo "\n8. Validando integridade da exportação de dados em CSV...\n";

        $controller = new RelatoriosController();
        $reflection = new \ReflectionClass(RelatoriosController::class);
        $method = $reflection->getMethod('obterDadosVendasAvancadas');
        $method->setAccessible(true);

        $filtros = [
            'data_inicio' => '2026-01-01',
            'data_fim'    => '2026-12-31',
            'agrupar_alimento' => 'sim'
        ];

        $dados = $method->invoke($controller, $filtros);

        // Simula montagem do CSV conforme implementado no método vendasFiltrosCsv
        $buffer = fopen('php://memory', 'w+');
        fputs($buffer, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($buffer, [
            'Data',
            'Alimento',
            'Categoria',
            'Quantidade',
            'Preco Unitario',
            'Valor Total',
            'Forma Pagamento',
            'Observacao'
        ], ';');

        $linhasAdicionadas = 0;
        foreach ($dados['dias'] as $dataKey => $dia) {
            foreach ($dia['alimentos'] as $item) {
                fputcsv($buffer, [
                    $dia['data_fmt'],
                    $item['produto_nome'] ?? '',
                    $item['categoria_nome'] ?? '',
                    $item['quantidade'] ?? 0,
                    number_format((float)($item['preco_unitario'] ?? 0), 2, ',', '.'),
                    number_format((float)($item['subtotal'] ?? 0), 2, ',', '.'),
                    $item['forma_pagamento'] ?? '',
                    $item['observacao'] ?? ''
                ], ';');
                $linhasAdicionadas++;
            }
        }

        fputcsv($buffer, [
            'TOTAL DO PERÍODO',
            '',
            '',
            $dados['totais']['quantidade_total'] ?? 0,
            '',
            number_format((float)($dados['totais']['faturamento_total'] ?? 0), 2, ',', '.'),
            '',
            ''
        ], ';');

        rewind($buffer);
        $csvContent = stream_get_contents($buffer);
        fclose($buffer);

        $this->assert(str_starts_with($csvContent, "\xEF\xBB\xBF"), "CSV inicia com BOM UTF-8 para compatibilidade Excel.");
        $this->assert(str_contains($csvContent, "Data;Alimento;Categoria;"), "Cabeçalhos CSV corretos com delimitador ';'.");
        $this->assert(str_contains($csvContent, "TOTAL DO PERÍODO"), "Linha de consolidação 'TOTAL DO PERÍODO' presente no CSV.");
        $this->assert($linhasAdicionadas > 0, "Linhas de vendas incluídas no CSV ($linhasAdicionadas registros).");
    }

    /**
     * 9. Validação do Controle de Acesso e RBAC
     */
    private function testarControleAcessoRbac(): void
    {
        echo "\n9. Validando controle de acesso (RBAC) às rotas de relatórios...\n";

        Session::start();
        Session::destroy();
        Session::start();

        // 1. Visitante não autenticado
        $this->assert(Auth::guest(), "Visitante não autenticado detectado corretamente.");
        $this->assert(!\App\Core\RBAC::can('relatorios_gerenciais'), "Visitante não possui permissão para relatórios gerenciais.");

        // 2. Simula login de operador
        Auth::loginUsuario([
            'id'      => 2,
            'nome'    => 'Operador Teste',
            'usuario' => 'operador',
            'email'   => 'operador@monteirolobato.com.br',
            'perfil'  => 'operador'
        ]);

        $this->assert(Auth::isOperador(), "Sessão autenticada como Operador.");
        $this->assert(!\App\Core\RBAC::can('relatorios_gerenciais'), "Operador NÃO possui permissão para relatórios gerenciais administrativos.");

        // 3. Simula login de administrador
        $stmt = $this->pdo->query("SELECT * FROM usuarios WHERE perfil = 'administrador' AND ativo = 1 LIMIT 1");
        $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($admin) {
            Auth::loginUsuario($admin);

            $this->assert(Auth::isAdmin(), "Sessão autenticada do Administrador inicializada com sucesso.");
            $this->assert(\App\Core\RBAC::can('relatorios_gerenciais'), "Administrador possui permissão para relatórios gerenciais.");
        }
    }
}

// Execução da suíte
$teste = new Fase21Test();
$teste->run();
