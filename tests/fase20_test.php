<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 20: Nova Arquitetura de PDF (HTML+CSS+Gotenberg) & Migração Completa de Relatórios + Fechamento de Caixa
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md, docs/PLANO.md e diretrizes oficiais de fechamento de caixa.
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

use App\Services\PdfService;
use App\Models\SessaoCaixa;
use App\Models\VendaBalcao;
use App\Core\Session;

class Fase20Test
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
        echo "   TESTE FASE 20: NOVA ARQUITETURA DE PDF & RELATÓRIOS\n";
        echo "=======================================================\n\n";

        $this->testarReducaoArquiteturaPdfService();
        $this->testarRenderViewEIsolamento();
        $this->testarEstruturaDadosFechamentoOficial();
        $this->testarEspelhoFechamentoCaixaHtmlEPdf();
        $this->testarRelatorioCorteHtmlEPdf();
        $this->testarTicketsLanche80mm();
        $this->testarCupomBalcao80mm();
        $this->testarComprovanteRecargaDinheiro80mm();
        $this->testarDescontoFolhaRHEIndividual();
        $this->testarRelatoriosAuxiliaresA4();

        echo "\n-------------------------------------------------------\n";
        echo "Resumo: {$this->assercoesSucesso} de {$this->totalAssercoes} asserções aprovadas com sucesso.\n";
        echo "=======================================================\n";

        if ($this->assercoesSucesso !== $this->totalAssercoes) {
            exit(1);
        }
    }

    /**
     * 1. Valida a redução do PdfService e a nova arquitetura limpa
     */
    private function testarReducaoArquiteturaPdfService(): void
    {
        echo "1. Validando redução de complexidade e arquitetura do PdfService...\n";

        $file = __DIR__ . '/../app/services/PdfService.php';
        $content = file_get_contents($file);
        $linhas = count(explode("\n", $content));

        $this->assert($linhas < 1200, "PdfService reduzido de ~5.750 linhas para {$linhas} linhas (< 1.200).");
        $this->assert(str_contains($content, 'renderView'), "Método renderView presente para compilação de views HTML.");
        $this->assert(str_contains($content, 'htmlToPdf'), "Método htmlToPdf presente para integração Gotenberg/Chromium.");
        $this->assert(str_contains($content, 'montarPdfEstruturado'), "Gerador canônico de contingência estruturado implementado.");
        $this->assert(str_contains($content, 'getLogoDataUri'), "Método getLogoDataUri implementado para embutir logotipo local.");
    }

    /**
     * 2. Valida o motor de renderização de views com isolamento de escopo
     */
    private function testarRenderViewEIsolamento(): void
    {
        echo "\n2. Validando PdfService::renderView e escopo de dados...\n";

        $dadosCorteMock = [
            'data_consumo'   => '2026-09-30',
            'turno_consumo'  => 'matutino',
            'total_pecas'    => 120,
            'total_pedidos'  => 45,
            'total_alunos'   => 40,
            'status_corte'   => ['rotulo' => 'Corte Fechado'],
            'itens'          => [
                [
                    'produto_nome' => 'Pão de Queijo Forno de Minas',
                    'tipo'         => 'fornecedor',
                    'categoria'    => 'Salgados Assados',
                    'total_pecas'  => 50
                ]
            ]
        ];

        $html = PdfService::renderView('relatorios/corte_pdf', ['dadosCorte' => $dadosCorteMock]);

        $this->assert(!empty($html), "View corte_pdf renderizada com sucesso.");
        $this->assert(str_contains($html, '<!DOCTYPE html>'), "Estrutura HTML5 com DOCTYPE presente.");
        $this->assert(str_contains($html, 'Pão de Queijo Forno de Minas'), "Dados injetados renderizados corretamente.");
        $this->assert(str_contains($html, '120 un'), "Total de peças computado exibido.");
        $this->assert(str_contains($html, 'font-tabular'), "Classe .font-tabular aplicada para alinhamento contábil.");
    }

    /**
     * 3. Valida a extração contábil oficial das 4 seções de Fechamento de Caixa
     */
    private function testarEstruturaDadosFechamentoOficial(): void
    {
        echo "\n3. Validando SessaoCaixa::obterDadosFechamentoOficial...\n";

        // Busca a última sessão de caixa existente no banco
        $stmt = $this->pdo->query("SELECT id FROM sessoes_caixa ORDER BY id DESC LIMIT 1");
        $sessaoRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sessaoRow) {
            // Cria uma sessão mock para teste se não houver
            $this->pdo->exec("INSERT INTO sessoes_caixa (operador_id, turno, status, saldo_inicial, saldo_final_informado, total_dinheiro, total_cartao_debito, total_cartao_credito, total_pix, quebra_caixa, data_abertura, data_fechamento) VALUES (1, 'matutino', 'fechado', 100.00, 350.00, 150.00, 50.00, 50.00, 0.00, 0.00, NOW(), NOW())");
            $sessaoId = (int)$this->pdo->lastInsertId();
        } else {
            $sessaoId = (int)$sessaoRow['id'];
        }

        $dadosFechamento = SessaoCaixa::obterDadosFechamentoOficial($sessaoId);

        $this->assert(is_array($dadosFechamento), "Dados de fechamento retornados em array.");
        $this->assert(isset($dadosFechamento['sessao']), "Chave 'sessao' presente.");
        $this->assert(isset($dadosFechamento['duplicatas']), "Seção 1: 'duplicatas' presente.");
        $this->assert(isset($dadosFechamento['subtotal_duplicatas']), "Subtotal de duplicatas calculado.");
        $this->assert(isset($dadosFechamento['vendas']), "Chave 'vendas' consolidada presente.");
        $this->assert(isset($dadosFechamento['vendas_cantina_web']), "Chave 'vendas_cantina_web' presente.");
        $this->assert(isset($dadosFechamento['subtotal_cantina_web']), "Subtotal de vendas Cantina Web calculado.");
        $this->assert(isset($dadosFechamento['vendas_balcao']), "Chave 'vendas_balcao' presente.");
        $this->assert(isset($dadosFechamento['subtotal_balcao']), "Subtotal de vendas balcão calculado.");
        $this->assert(isset($dadosFechamento['subtotal_vendas']), "Subtotal geral de vendas calculado.");
        $this->assert(isset($dadosFechamento['pix_recebimentos']), "Seção 3: 'pix_recebimentos' presente.");
        $this->assert(isset($dadosFechamento['total_pix_recebimentos']), "Total de recebimentos Pix calculado.");
        $this->assert(isset($dadosFechamento['resumo_caixa']), "Seção 4: 'resumo_caixa' presente.");
        $this->assert(isset($dadosFechamento['resumo_caixa']['fundo_caixa']), "Resumo do caixa contém 'fundo_caixa'.");
        $this->assert(isset($dadosFechamento['resumo_caixa']['total_entradas']), "Resumo do caixa contém 'total_entradas'.");
        $this->assert(isset($dadosFechamento['resumo_caixa']['total_saidas']), "Resumo do caixa contém 'total_saidas'.");
        $this->assert(isset($dadosFechamento['resumo_caixa']['saldo_atual']), "Resumo do caixa contém 'saldo_atual'.");
    }

    /**
     * 4. Valida o Espelho Oficial A4 de Fechamento de Caixa (HTML e PDF)
     */
    private function testarEspelhoFechamentoCaixaHtmlEPdf(): void
    {
        echo "\n4. Validando Espelho Oficial A4 de Fechamento de Caixa...\n";

        $dadosMock = [
            'sessao' => [
                'id'            => 145,
                'turno'         => 'vespertino',
                'operador_nome' => 'Operador Teste',
                'data_abertura' => '2026-09-30 13:00:00',
                'data_fechamento'=> '2026-09-30 18:00:00',
                'saldo_inicial' => 120.00
            ],
            'duplicatas' => [
                [
                    'codigo'            => 'REC001',
                    'data_hora'         => '2026-09-30 14:10:00',
                    'aluno_responsavel' => 'Enzo Gabriel (Resp: Carla Maria)',
                    'matricula'         => '2026001',
                    'tipo'              => 'Balcão',
                    'forma_pagamento'   => 'Dinheiro',
                    'valor'             => 50.00
                ]
            ],
            'subtotal_duplicatas' => 50.00,
            'vendas' => [
                [
                    'codigo'          => 'VD101',
                    'data_hora'       => '2026-09-30 15:30:00',
                    'cliente_ident'   => 'Balcão Geral',
                    'tipo'            => 'Balcão',
                    'forma_pagamento' => 'Cartão Débito',
                    'valor'           => 18.50
                ],
                [
                    'codigo'          => 'VD102',
                    'data_hora'       => '2026-09-30 16:00:00',
                    'cliente_ident'   => 'Prof. Carlos Santos',
                    'tipo'            => 'Folha RH',
                    'forma_pagamento' => 'Desconto em Folha',
                    'valor'           => 12.00
                ]
            ],
            'subtotal_vendas' => 30.50,
            'pix_recebimentos' => [
                [
                    'transacao_id' => 'E123456789',
                    'data_hora'    => '2026-09-30 14:20:00',
                    'pagador'      => 'Juliana Silva',
                    'aluno'        => 'Lucas Silva',
                    'valor'        => 80.00
                ]
            ],
            'total_pix_recebimentos' => 80.00,
            'resumo_caixa' => [
                'fundo_caixa'    => 120.00,
                'total_entradas' => 160.50,
                'total_saidas'   => 0.00,
                'saldo_atual'    => 280.50
            ]
        ];

        $html = PdfService::gerarHtmlRelatorioFechamentoCaixa($dadosMock['sessao'], $dadosMock);

        $this->assert(!empty($html), "HTML do Fechamento de Caixa gerado com sucesso.");
        $this->assert(str_contains($html, 'Fechamento de Caixa'), "Título oficial presente no HTML.");
        $this->assert(str_contains($html, 'Duplicatas'), "Seção 1 (Duplicatas) identificada no espelho.");
        $this->assert(str_contains($html, 'Vendas Cantina Web'), "Seção Vendas Cantina Web identificada no espelho.");
        $this->assert(str_contains($html, 'Vendas Presenciais no Balcão'), "Seção Vendas Presenciais no Balcão identificada no espelho.");
        $this->assert(!str_contains($html, 'Nutre'), "Termo legado 'Nutre' completamente ausente do espelho.");
        $this->assert(str_contains($html, 'Recebimento em Pix'), "Seção Recebimento em Pix identificada no espelho.");
        $this->assert(str_contains($html, 'Resumo do Caixa'), "Seção Resumo do Caixa identificada no espelho.");
        $this->assert(str_contains($html, 'Fundo de Caixa'), "Card contábil de Fundo de Caixa presente.");
        $this->assert(str_contains($html, 'Entradas'), "Card contábil de Entradas presente.");
        $this->assert(str_contains($html, 'Saldo de Caixa'), "Card contábil de Saldo Final presente.");

        // Gera o PDF
        $pdf = PdfService::gerarRelatorioFechamentoCaixaPdf($dadosMock['sessao'], $dadosMock);
        $this->assert(!empty($pdf), "PDF do Fechamento de Caixa gerado com sucesso.");
        $this->assert(str_starts_with($pdf, '%PDF-1.4'), "Cabeçalho PDF 1.4 válido.");
        $this->assert(str_ends_with(trim($pdf), '%%EOF'), "Finalizador %%EOF íntegro.");
    }

    /**
     * 5. Valida o Relatório de Corte A4 Timbrado
     */
    private function testarRelatorioCorteHtmlEPdf(): void
    {
        echo "\n5. Validando Relatório de Corte A4 Timbrado...\n";

        $dadosCorte = [
            'data_consumo'  => '2026-10-01',
            'turno_consumo' => 'matutino',
            'total_pecas'   => 85,
            'total_pedidos' => 30,
            'total_alunos'  => 28,
            'status_corte'  => ['rotulo' => 'Corte Fechado'],
            'itens'         => [
                [
                    'produto_nome' => 'Mini Pizza Mussarela',
                    'tipo'         => 'fornecedor',
                    'categoria'    => 'Salgados Fornecedor',
                    'total_pecas'  => 35
                ],
                [
                    'produto_nome' => 'Esfiha de Carne',
                    'tipo'         => 'fornecedor',
                    'categoria'    => 'Salgados Fornecedor',
                    'total_pecas'  => 50
                ]
            ]
        ];

        $html = PdfService::gerarHtmlRelatorioCorte($dadosCorte);
        $this->assert(str_contains($html, 'Relatório de Corte') || str_contains($html, 'Consolidação de Compra'), "Título de Corte do Turno presente no HTML.");
        $this->assert(str_contains($html, 'Mini Pizza Mussarela'), "Item de fornecedor listado.");

        $pdf = PdfService::gerarRelatorioCortePdf($dadosCorte);
        $this->assert(!empty($pdf) && str_starts_with($pdf, '%PDF-1.4'), "PDF de Corte 1.4 gerado com sucesso.");
    }

    /**
     * 6. Valida os Tickets de Lanche 80mm
     */
    private function testarTicketsLanche80mm(): void
    {
        echo "\n6. Validando Tickets Térmicos de Lanche (80mm)...\n";

        $tickets = [
            [
                'codigo_ticket'      => 'TCK-2026-0001',
                'aluno_nome'         => 'Bernardo Souza',
                'turma_codigo'       => '5A-EF',
                'produto_descricao'  => 'Hamburguer Bovino com Queijo',
                'tipo_refeicao'      => 'Lanche Matutino',
                'restricoes'         => 'Contém Glúten e Lactose',
                'observacoes'        => 'Sem cebola',
                'data_consumo'       => '2026-10-01',
                'turno'              => 'matutino',
                'data_emissao'       => '2026-09-30 10:00:00'
            ]
        ];

        $html = PdfService::gerarEspelhoTicketsLoteHtml($tickets);
        $this->assert(str_contains($html, 'TCK-2026-0001'), "Código do ticket exibido no HTML.");
        $this->assert(str_contains($html, 'Bernardo Souza'), "Nome do aluno presente no ticket.");
        $this->assert(str_contains($html, '80mm') || str_contains($html, '76mm'), "CSS ajustado para bobina térmica.");

        $pdf = PdfService::gerarTicketsLotePdf($tickets);
        $this->assert(!empty($pdf) && str_starts_with($pdf, '%PDF-1.4'), "PDF de tickets gerado com sucesso.");
    }

    /**
     * 7. Valida o Cupom Balcão PDV 80mm
     */
    private function testarCupomBalcao80mm(): void
    {
        echo "\n7. Validando Comprovante Térmico de Venda Balcão (80mm)...\n";

        $venda = [
            'id'              => 89,
            'created_at'      => '2026-09-30 11:20:00',
            'turno'           => 'matutino',
            'tipo_cliente'    => 'aluno',
            'aluno_nome'      => 'Marina Lima',
            'forma_pagamento' => 'saldo_carteira',
            'valor_total'     => 14.50,
            'itens'           => [
                [
                    'produto_nome'   => 'Coxinha com Catupiry',
                    'quantidade'     => 1,
                    'preco_unitario' => 8.50,
                    'subtotal'       => 8.50
                ],
                [
                    'produto_nome'   => 'Suco de Uva Integral',
                    'quantidade'     => 1,
                    'preco_unitario' => 6.00,
                    'subtotal'       => 6.00
                ]
            ]
        ];

        $html = PdfService::gerarEspelhoComprovanteVendaBalcaoHtml($venda);
        $this->assert(str_contains($html, 'Comprovante de Venda'), "Título de cupom balcão presente.");
        $this->assert(str_contains($html, 'Coxinha com Catupiry'), "Item consumido presente no cupom.");

        $pdf = PdfService::gerarComprovanteVendaBalcaoPdf($venda);
        $this->assert(!empty($pdf) && str_starts_with($pdf, '%PDF-1.4'), "PDF térmico de venda gerado com sucesso.");
    }

    /**
     * 8. Valida o Comprovante de Recarga em Dinheiro 80mm
     */
    private function testarComprovanteRecargaDinheiro80mm(): void
    {
        echo "\n8. Validando Comprovante de Recarga em Dinheiro (80mm)...\n";

        $transacao = [
            'transacao_id'    => 42,
            'sessao_caixa_id' => 10,
            'created_at'      => '2026-09-30 08:45:00',
            'responsavel_nome'=> 'Patrícia Pereira',
            'responsavel_cpf' => '123.456.789-00',
            'valor'           => 100.00,
            'saldo_anterior'  => 15.00,
            'saldo_resultante'=> 115.00,
            'operador_nome'   => 'Operador Central',
            'dependentes'     => [
                ['nome' => 'Gabriel Pereira', 'nome_turma' => '3B-EF']
            ]
        ];

        $html = PdfService::gerarEspelhoComprovanteRecargaDinheiroHtml($transacao);
        $this->assert(str_contains($html, 'Comprovante de Recarga'), "Título do comprovante de recarga presente.");
        $this->assert(str_contains($html, 'Gabriel Pereira'), "Nome do aluno vinculado presente.");

        $pdf = PdfService::gerarComprovanteRecargaDinheiroPdf($transacao);
        $this->assert(!empty($pdf) && str_starts_with($pdf, '%PDF-1.4'), "PDF do comprovante de recarga gerado.");
    }

    /**
     * 9. Valida Desconto em Folha para RH (Geral e Individual)
     */
    private function testarDescontoFolhaRHEIndividual(): void
    {
        echo "\n9. Validando Desconto em Folha para RH (Geral e Individual)...\n";

        $dadosGeral = [
            'competencia'   => '09/2026',
            'operador'      => 'RH Cantina',
            'colaboradores' => [
                [
                    'nome'                => 'Professor Roberto',
                    'matricula_funcional' => 'PROF01',
                    'cargo'               => 'Docente',
                    'total_lanches'       => 5,
                    'valor_total'         => 35.00
                ]
            ],
            'totais' => [
                'total_colaboradores' => 1,
                'total_lanches'       => 5,
                'valor_total'         => 35.00
            ]
        ];

        $htmlGeral = PdfService::renderView('relatorios/desconto_folha_pdf', ['dados' => $dadosGeral]);
        $this->assert(str_contains($htmlGeral, 'Desconto em Folha'), "View de desconto em folha consolidado renderizada.");

        $pdfGeral = PdfService::gerarRelatorioDescontoFolhaPdf($dadosGeral);
        $this->assert(!empty($pdfGeral) && str_starts_with($pdfGeral, '%PDF-1.4'), "PDF de desconto em folha geral gerado.");

        $dadosIndividual = [
            'colaborador' => [
                'id'                  => 1,
                'nome'                => 'Professora Mariana',
                'matricula_funcional' => 'PROF02',
                'cargo'               => 'Coordenadora'
            ],
            'competencia' => '09/2026',
            'itens'       => [
                [
                    'data_venda'     => '2026-09-25 10:15:00',
                    'venda_balcao_id'=> 77,
                    'produto_nome'   => 'Cappuccino Cremoso',
                    'quantidade'     => 1,
                    'preco_unitario' => 7.00,
                    'subtotal'       => 7.00
                ]
            ],
            'totais' => [
                'total_desconto' => 7.00,
                'total_lanches'  => 1,
                'total_compras'  => 1
            ]
        ];

        $htmlIndiv = PdfService::gerarEspelhoDescontoFolhaIndividualHtml($dadosIndividual);
        $this->assert(str_contains($htmlIndiv, 'Demonstrativo Individual para RH'), "Título formal na barra de ação do individual presente.");
        $this->assert(str_contains($htmlIndiv, 'Termo de Ciência do Colaborador'), "Termo de ciência do colaborador presente.");

        $pdfIndiv = PdfService::gerarRelatorioDescontoFolhaIndividualPdf($dadosIndividual);
        $this->assert(!empty($pdfIndiv) && str_starts_with($pdfIndiv, '%PDF-1.4'), "PDF individual gerado com sucesso.");
    }

    /**
     * 10. Valida Relatórios Auxiliares A4 (MED, Vendas por Alimento e Extrato do Aluno)
     */
    private function testarRelatoriosAuxiliaresA4(): void
    {
        echo "\n10. Validando Relatórios Auxiliares A4 (MED, Alimentos e Extrato)...\n";

        // MED / Negativados
        $dadosMed = [
            'familias' => [
                [
                    'responsavel_nome'  => 'Carlos Andrade',
                    'responsavel_cpf'   => '123.456.789-00',
                    'aluno_nome'        => 'Lucas Andrade',
                    'saldo_atual'       => -45.00,
                    'total_estornos_med'=> 45.00
                ]
            ],
            'total_devedor' => 45.00
        ];
        $htmlMed = PdfService::renderView('relatorios/med_negativados_pdf', ['dados' => $dadosMed]);
        $this->assert(str_contains($htmlMed, 'Saldo Devedor') || str_contains($htmlMed, 'MED'), "Relatório de MED renderizado com sucesso.");
        $pdfMed = PdfService::gerarRelatorioMedNegativadosPdf($dadosMed);
        $this->assert(!empty($pdfMed) && str_starts_with($pdfMed, '%PDF-1.4'), "PDF de MED gerado.");

        // Vendas por Alimento
        $dadosAlimento = [
            'periodo_inicio' => '2026-09-01',
            'periodo_fim'    => '2026-09-30',
            'itens' => [
                [
                    'produto_nome' => 'Pastel de Forno de Frango',
                    'categoria'    => 'Salgados',
                    'quantidade'   => 150,
                    'valor_total'  => 1050.00
                ]
            ],
            'total_unidades' => 150,
            'total_faturamento' => 1050.00
        ];
        $htmlAlimento = PdfService::renderView('relatorios/vendas_alimento_pdf', ['dados' => $dadosAlimento]);
        $this->assert(str_contains($htmlAlimento, 'Vendas por Alimento') || str_contains($htmlAlimento, 'Vendas da Cantina por Alimento'), "Relatório de Alimento renderizado.");
        $pdfAlimento = PdfService::gerarRelatorioVendasAlimentoPdf($dadosAlimento);
        $this->assert(!empty($pdfAlimento) && str_starts_with($pdfAlimento, '%PDF-1.4'), "PDF de Alimento gerado.");

        // Extrato do Aluno
        $dadosExtrato = [
            'aluno' => [
                'nome'      => 'Sofia Ferreira',
                'matricula' => '2026045',
                'turma'     => '4A-EF',
                'saldo'     => 62.50
            ],
            'transacoes' => [
                [
                    'data_hora' => '2026-09-28 10:15:00',
                    'tipo'      => 'Consumo',
                    'descricao' => 'Lanche Balcão',
                    'valor'     => -8.00,
                    'saldo_resultante' => 62.50
                ]
            ]
        ];
        $htmlExtrato = PdfService::renderView('relatorios/extrato_aluno_pdf', ['dados' => $dadosExtrato]);
        $this->assert(str_contains($htmlExtrato, 'Extrato Analítico de Consumo'), "Extrato do Aluno renderizado.");
        $pdfExtrato = PdfService::gerarExtratoAlunoPdf($dadosExtrato);
        $this->assert(!empty($pdfExtrato) && str_starts_with($pdfExtrato, '%PDF-1.4'), "PDF de Extrato do Aluno gerado.");
    }
}

// Execução
$test = new Fase20Test();
$test->run();
