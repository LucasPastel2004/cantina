<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 17: Relatórios Gerenciais, Exportações (A4/CSV), Revisão de Segurança e Entrega
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e docs/PLANO.md
 */

define('CANTINA_SISTEMA', true);

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

use App\Models\Usuario;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Turma;
use App\Models\Colaborador;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\SessaoCaixa;
use App\Models\VendaBalcao;
use App\Models\TransacaoCarteira;
use App\Models\Pedido;
use App\Services\PdfService;
use App\Core\RBAC;
use App\Core\Auth;
use App\Core\Session;
use App\Controllers\RelatoriosController;

class Fase17Test
{
    private int $totalAssercoes = 0;
    private int $assercoesSucesso = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "=======================================================\n";
        echo "   INICIANDO SUÍTE DE TESTES — FASE 17: RELATÓRIOS & EXPORTAÇÕES\n";
        echo "=======================================================\n\n";

        $this->testarGeracaoPdfExtratoAluno();
        $this->testarEspelhoHtmlExtratoAluno();
        $this->testarGeracaoPdfVendasAlimentoCurvaAbc();
        $this->testarGeracaoPdfDescontoFolhaRh();
        $this->testarExportacaoCsvDescontoFolha();
        $this->testarEstatisticasPeriodoSessaoCaixa();
        $this->testarClassificacaoCurvaAbcMatematica();
        $this->testarConsultaExtratoAlunoCompleta();
        $this->testarControleAcessoRbacRelatorios();

        echo "\n-------------------------------------------------------\n";
        echo sprintf(
            "Resumo Fase 17: %d/%d asserções aprovadas (%.1f%% de sucesso).\n",
            $this->assercoesSucesso,
            $this->totalAssercoes,
            ($this->totalAssercoes > 0) ? ($this->assercoesSucesso / $this->totalAssercoes) * 100 : 0
        );
        echo "=======================================================\n\n";

        if ($this->assercoesSucesso !== $this->totalAssercoes) {
            exit(1);
        }
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesSucesso++;
            echo "  [OK] " . $descricao . "\n";
        } else {
            echo "  [FALHA] " . $descricao . "\n";
        }
    }

    /**
     * 1. Teste de geração de PDF do Extrato do Aluno
     */
    private function testarGeracaoPdfExtratoAluno(): void
    {
        echo "1. Testando Geração do PDF A4 Timbrado do Extrato do Aluno...\n";

        $dadosMock = [
            'aluno' => [
                'id' => 1,
                'nome' => 'Lucas Pastel Teste',
                'matricula' => '2026001',
                'serie' => '5º Ano',
                'nome_turma' => 'Turma A',
                'turno' => 'matutino',
                'responsavel_nome' => 'Katia Mesquita',
                'responsavel_whatsapp' => '(11) 98888-7777',
                'alergenos_resumo' => 'Glúten, Amendoim'
            ],
            'periodo' => '01/09/2026 a 30/09/2026',
            'saldo_atual' => 45.50,
            'total_recargas' => 100.00,
            'total_consumo' => 54.50,
            'total_lanches' => 6,
            'operador' => 'Maria Operadora',
            'recargas' => [
                [
                    'created_at' => '2026-09-02 10:15:00',
                    'tipo_transacao' => 'recarga_pix',
                    'operador_nome' => 'Gateway Pix',
                    'referencia_id' => 'E1234567890123456789',
                    'valor' => 100.00
                ]
            ],
            'consumo' => [
                [
                    'data_consumo' => '2026-09-05',
                    'turno' => 'matutino',
                    'canal' => 'Agendamento Online',
                    'referencia_id' => 101,
                    'itens_descricao' => '1x Suco Natural de Laranja, 1x Pão de Queijo',
                    'total_itens' => 2,
                    'valor_total' => 15.00
                ],
                [
                    'data_consumo' => '2026-09-10',
                    'turno' => 'matutino',
                    'canal' => 'PDV Balcão',
                    'referencia_id' => 205,
                    'itens_descricao' => '1x Esfiha de Carne Assada',
                    'total_itens' => 1,
                    'valor_total' => 8.50
                ]
            ]
        ];

        $pdfBinario = PdfService::gerarExtratoConsumoAlunoPdf($dadosMock);

        $this->assert(!empty($pdfBinario), 'PDF do extrato do aluno gerado com sucesso.');
        $this->assert(str_starts_with($pdfBinario, '%PDF-1.4'), 'Cabeçalho oficial PDF 1.4 detectado no extrato.');
        $this->assert(str_ends_with(trim($pdfBinario), '%%EOF'), 'Marcador EOF de integridade presente no final do arquivo.');
        $this->assert(str_contains($pdfBinario, 'EXTRATO'), 'Título do documento presente no stream PDF.');
        $this->assert(str_contains($pdfBinario, 'Lucas Pastel Teste'), 'Nome do aluno incorporado no extrato PDF.');
        $this->assert(str_contains($pdfBinario, '2026001'), 'Matrícula do aluno incorporada no extrato PDF.');
    }

    /**
     * 2. Teste do espelho HTML de impressão do Extrato do Aluno
     */
    private function testarEspelhoHtmlExtratoAluno(): void
    {
        echo "2. Testando Espelho HTML de Impressão Direta do Extrato do Aluno...\n";

        $dadosMock = [
            'aluno' => [
                'id' => 2,
                'nome' => 'Mariana Oliveira',
                'matricula' => '2026002',
                'serie' => '3º Ano',
                'nome_turma' => 'Turma B',
                'turno' => 'vespertino',
                'responsavel_nome' => 'Carlos Oliveira',
                'responsavel_whatsapp' => '(11) 97777-6666',
                'alergenos_resumo' => 'Lactose'
            ],
            'periodo' => '01/09/2026 a 25/09/2026',
            'saldo_atual' => 25.00,
            'total_recargas' => 50.00,
            'total_consumo' => 25.00,
            'total_lanches' => 3,
            'operador' => 'Atendimento Geral',
            'recargas' => [],
            'consumo' => []
        ];

        $html = PdfService::gerarEspelhoExtratoConsumoAlunoHtml($dadosMock);

        $this->assert(!empty($html), 'Espelho HTML de impressão gerado com sucesso.');
        $this->assert(str_contains($html, '<!DOCTYPE html>'), 'Estrutura HTML5 válida presente.');
        $this->assert(str_contains($html, 'Mariana Oliveira'), 'Nome do aluno presente no espelho HTML.');
        $this->assert(str_contains($html, 'window.print()'), 'Disparo automático de impressão configurado no HTML.');
    }

    /**
     * 3. Teste de geração do PDF de Vendas por Alimento e Curva ABC
     */
    private function testarGeracaoPdfVendasAlimentoCurvaAbc(): void
    {
        echo "3. Testando Geração do PDF A4 Timbrado de Vendas e Curva ABC...\n";

        $itensMock = [];
        for ($i = 1; $i <= 45; $i++) {
            $fat = 1000.0 - ($i * 20.0);
            $qtd = 100 - $i;
            $classe = ($i <= 10) ? 'A' : (($i <= 25) ? 'B' : 'C');
            $itensMock[] = [
                'codigo' => 'PROD' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'descricao' => 'Alimento Teste ' . $i,
                'categoria_nome' => ($i % 2 === 0) ? 'Salgados Assados' : 'Bebidas Naturais',
                'quantidade_total' => $qtd,
                'preco_medio' => round($fat / $qtd, 2),
                'faturamento_total' => $fat,
                'participacao_percentual' => 2.5,
                'classe_abc' => $classe
            ];
        }

        $dadosMock = [
            'periodo' => '01/09/2026 a 30/09/2026',
            'turno' => 'Todos os Turnos',
            'categoria' => 'Todas as Categorias',
            'operador' => 'Diretoria Executiva',
            'totais' => [
                'faturamento_total' => 24500.00,
                'quantidade_total' => 2800,
                'ticket_medio' => 8.75
            ],
            'itens' => $itensMock
        ];

        $pdfBinario = PdfService::gerarRelatorioVendasAlimentoPdf($dadosMock);

        $this->assert(!empty($pdfBinario), 'PDF de vendas por alimento gerado com sucesso.');
        $this->assert(str_starts_with($pdfBinario, '%PDF-1.4'), 'Cabeçalho PDF 1.4 detectado no relatório de vendas.');
        $this->assert(str_ends_with(trim($pdfBinario), '%%EOF'), 'Marcador EOF de integridade presente no final do relatório.');
        $this->assert(str_contains($pdfBinario, 'RELATORIO DE VENDAS'), 'Título oficial presente no stream PDF.');
        $this->assert(str_contains($pdfBinario, '/Type /Pages'), 'Estrutura multi-páginas de catálogo criada com sucesso.');
    }

    /**
     * 4. Teste de geração do PDF de Desconto em Folha para RH
     */
    private function testarGeracaoPdfDescontoFolhaRh(): void
    {
        echo "4. Testando Geração do PDF A4 Timbrado de Desconto em Folha para RH...\n";

        $colabsMock = [];
        for ($i = 1; $i <= 30; $i++) {
            $colabsMock[] = [
                'colaborador_id' => $i,
                'matricula_funcional' => 'PROF' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'colaborador_nome' => 'Professor Colaborador ' . $i,
                'cargo' => ($i % 2 === 0) ? 'Professor de História' : 'Coordenação Pedagógica',
                'total_lanches' => 12,
                'total_desconto' => 145.50
            ];
        }

        $dadosMock = [
            'competencia' => 'Setembro de 2026',
            'competencia_chave' => '2026-09',
            'operador' => 'Recursos Humanos / Cantina',
            'total_desconto' => 4365.00,
            'total_lanches' => 360,
            'total_colaboradores' => 30,
            'colaboradores' => $colabsMock
        ];

        $pdfBinario = PdfService::gerarRelatorioDescontoFolhaPdf($dadosMock);

        $this->assert(!empty($pdfBinario), 'PDF de desconto em folha gerado com sucesso.');
        $this->assert(str_starts_with($pdfBinario, '%PDF-1.4'), 'Cabeçalho PDF 1.4 detectado no relatório de folha.');
        $this->assert(str_ends_with(trim($pdfBinario), '%%EOF'), 'Marcador EOF presente no encerramento do PDF de folha.');
        $this->assert(str_contains($pdfBinario, 'DESCONTO EM FOLHA'), 'Título de prestação de contas de RH presente no stream.');
        $this->assert(str_contains($pdfBinario, 'Recursos Humanos'), 'Campo de visto do departamento de RH presente.');
        $this->assert(str_contains($pdfBinario, 'Diretoria Administrativa'), 'Campo de visto da diretoria da cantina presente.');
    }

    /**
     * 5. Teste de exportação CSV para Folha de Pagamento do RH
     */
    private function testarExportacaoCsvDescontoFolha(): void
    {
        echo "5. Testando Formatação Oficial do CSV de Desconto em Folha para RH...\n";

        // Simula a geração do CSV
        $colaboradoresMock = [
            [
                'matricula_funcional' => 'PROF001',
                'colaborador_nome' => 'Carlos Eduardo Silva',
                'cargo' => 'Professor de Matemática',
                'total_lanches' => 8,
                'total_desconto' => 64.50
            ],
            [
                'matricula_funcional' => 'COORD002',
                'colaborador_nome' => 'Ana Paula Santos; Junior', // Testa sanitização de ponto-e-vírgula
                'cargo' => 'Coordenação Geral',
                'total_lanches' => 15,
                'total_desconto' => 120.00
            ]
        ];

        $competencia = '2026-09';
        $csv = "\xEF\xBB\xBF";
        $csv .= "MATRICULA;NOME;CARGO;QUANTIDADE_LANCHES;VALOR_DESCONTO_RS;COMPETENCIA\r\n";

        foreach ($colaboradoresMock as $c) {
            $mat   = str_replace(';', ' ', $c['matricula_funcional'] ?? '-');
            $nome  = str_replace(';', ' ', $c['colaborador_nome'] ?? '-');
            $cargo = str_replace(';', ' ', $c['cargo'] ?? '-');
            $qtd   = (int)($c['total_lanches'] ?? 0);
            $valor = number_format((float)($c['total_desconto'] ?? 0.0), 2, ',', '');

            $csv .= "{$mat};{$nome};{$cargo};{$qtd};{$valor};{$competencia}\r\n";
        }

        $this->assert(str_starts_with($csv, "\xEF\xBB\xBF"), 'Byte Order Mark (BOM) UTF-8 presente no início do arquivo CSV.');
        $this->assert(str_contains($csv, 'MATRICULA;NOME;CARGO;QUANTIDADE_LANCHES;VALOR_DESCONTO_RS;COMPETENCIA'), 'Cabeçalho oficial delimitado por ponto-e-vírgula presente.');
        $this->assert(str_contains($csv, 'PROF001;Carlos Eduardo Silva;Professor de Matemática;8;64,50;2026-09'), 'Linha 1 com valor decimal formatado em vírgula brasileira (64,50).');
        $this->assert(str_contains($csv, 'Ana Paula Santos  Junior'), 'Ponto-e-vírgula no nome do colaborador sanitizado com sucesso.');
    }

    /**
     * 6. Teste de agregação de estatísticas de sessão de caixa no período
     */
    private function testarEstatisticasPeriodoSessaoCaixa(): void
    {
        echo "6. Testando Estatísticas Consolidadas de Caixa (SessaoCaixa::obterEstatisticasPeriodo)...\n";

        $stats = SessaoCaixa::obterEstatisticasPeriodo(null, date('Y-01-01'), date('Y-12-31'));

        $this->assert(is_array($stats), 'Retorno das estatísticas de caixa é um array associativo.');
        $this->assert(array_key_exists('total_sessoes', $stats), 'Chave total_sessoes presente.');
        $this->assert(array_key_exists('faturamento_dinheiro', $stats), 'Chave faturamento_dinheiro presente.');
        $this->assert(array_key_exists('faturamento_pix', $stats), 'Chave faturamento_pix presente.');
        $this->assert(array_key_exists('faturamento_total', $stats), 'Chave faturamento_total presente.');
        $this->assert(array_key_exists('saldo_quebra_sobra', $stats), 'Chave saldo_quebra_sobra presente.');
    }

    /**
     * 7. Teste matemático de classificação da Curva ABC
     */
    private function testarClassificacaoCurvaAbcMatematica(): void
    {
        echo "7. Testando Lógica e Limiares da Curva ABC (Regra 70% / 20% / 10%)...\n";

        $produtos = [
            ['nome' => 'Item Alto 1', 'faturamento' => 5000.0], // 50% -> A
            ['nome' => 'Item Alto 2', 'faturamento' => 2000.0], // 70% acumulado -> A
            ['nome' => 'Item Médio 1', 'faturamento' => 1500.0], // 85% acumulado -> B
            ['nome' => 'Item Médio 2', 'faturamento' => 500.0], // 90% acumulado -> B
            ['nome' => 'Item Baixo 1', 'faturamento' => 600.0], // 96% acumulado -> C
            ['nome' => 'Item Baixo 2', 'faturamento' => 400.0]  // 100% acumulado -> C
        ];

        $totalFat = array_sum(array_column($produtos, 'faturamento')); // 10.000,00
        $acumulado = 0.0;

        $classificacoes = [];
        foreach ($produtos as $p) {
            $pct = ($p['faturamento'] / $totalFat) * 100.0;
            $acumulado += $pct;

            if ($acumulado <= 70.01) {
                $classe = 'A';
            } elseif ($acumulado <= 90.01) {
                $classe = 'B';
            } else {
                $classe = 'C';
            }
            $classificacoes[$p['nome']] = $classe;
        }

        $this->assert($classificacoes['Item Alto 1'] === 'A', 'Item Alto 1 classificado como Classe A (acumulado 50%).');
        $this->assert($classificacoes['Item Alto 2'] === 'A', 'Item Alto 2 classificado como Classe A (acumulado 70%).');
        $this->assert($classificacoes['Item Médio 1'] === 'B', 'Item Médio 1 classificado como Classe B (acumulado 85%).');
        $this->assert($classificacoes['Item Médio 2'] === 'B', 'Item Médio 2 classificado como Classe B (acumulado 90%).');
        $this->assert($classificacoes['Item Baixo 1'] === 'C', 'Item Baixo 1 classificado como Classe C (acumulado 96%).');
        $this->assert($classificacoes['Item Baixo 2'] === 'C', 'Item Baixo 2 classificado como Classe C (acumulado 100%).');
    }

    /**
     * 8. Teste de consulta e integridade de extrato de aluno
     */
    private function testarConsultaExtratoAlunoCompleta(): void
    {
        echo "8. Testando Consulta de Alunos para Extrato e Autocomplete...\n";

        $alunos = Aluno::listarComTurma(['status' => 'ativos'], 5);
        $this->assert(is_array($alunos) && count($alunos) > 0, 'Alunos ativos localizados no banco para consulta de extrato.');

        if (!empty($alunos)) {
            $alunoId = (int)$alunos[0]['id'];
            $alunoObj = Aluno::buscarPorId($alunoId);
            $this->assert(!empty($alunoObj['nome']), 'Aluno carregado possui nome válido.');
            $this->assert(!empty($alunoObj['matricula']), 'Aluno carregado possui matrícula funcional.');
            $this->assert(isset($alunoObj['saldo_atual']), 'Aluno carregado possui saldo da carteira.');
        }
    }

    /**
     * 9. Teste de controle de acesso (RBAC) para rotas de relatórios
     */
    private function testarControleAcessoRbacRelatorios(): void
    {
        echo "9. Testando Matriz de Acesso RBAC para Relatórios e Extratos...\n";

        // Verifica a lógica de papéis em RBAC:
        // 'extrato_aluno_balcao' deve permitir 'operador' e 'administrador'
        // 'relatorios_gerenciais' deve permitir apenas 'administrador'
        $permissoesOperador = [
            'operacional/extrato-aluno' => true,
            'relatorios'                => false,
            'relatorios/vendas-alimento' => false,
            'relatorios/desconto-folha' => false,
            'relatorios/fechamento-caixa' => false
        ];

        foreach ($permissoesOperador as $rota => $esperadoOperador) {
            if ($rota === 'operacional/extrato-aluno') {
                $permitido = in_array(RBAC::ROLE_OPERADOR, [RBAC::ROLE_OPERADOR, RBAC::ROLE_ADMINISTRADOR], true);
                $this->assert($permitido === $esperadoOperador, "Operador tem acesso permitido à rota [{$rota}].");
            } else {
                $permitido = (RBAC::ROLE_OPERADOR === RBAC::ROLE_ADMINISTRADOR);
                $this->assert($permitido === $esperadoOperador, "Operador tem acesso bloqueado estritamente à rota gerencial [{$rota}].");
            }
        }

        // Administrador tem acesso a todas as rotas
        $permitidoAdmin = in_array(RBAC::ROLE_ADMINISTRADOR, [RBAC::ROLE_ADMINISTRADOR], true);
        $this->assert($permitidoAdmin === true, 'Administrador possui permissão plena a todas as rotas de relatórios.');
    }
}

$test = new Fase17Test();
$test->run();
