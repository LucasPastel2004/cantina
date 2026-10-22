<?php
/**
 * Teste de Renderização do Extrato do Aluno em PDF e Impressão A4
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';

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
use App\Services\PdfService;
use App\Core\Model;

$total = 0;
$pass = 0;

$verificar = function(bool $cond, string $msg) use (&$total, &$pass) {
    $total++;
    if ($cond) {
        $pass++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
};

echo "=== TESTE: RENDERIZAÇÃO E CONTEÚDO DO EXTRATO DO ALUNO NO PDF/IMPRESSÃO ===\n\n";

// Localiza um aluno existente no banco de dados
$pdo = Model::getPdo();
$stmt = $pdo->query("SELECT id, nome, matricula FROM alunos ORDER BY id ASC LIMIT 1");
$aluno = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$aluno) {
    echo "Nenhum aluno encontrado no banco para teste.\n";
    exit(1);
}

$alunoId = (int)$aluno['id'];
echo "Aluno de teste: ID {$alunoId} — {$aluno['nome']} (Matrícula: {$aluno['matricula']})\n\n";

$controller = new RelatoriosController();
$dataInicio = date('Y-m-01');
$dataFim = date('Y-m-d');
$dados = $controller->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim);

$verificar(!empty($dados), "obterDadosExtratoAluno retornou dados com sucesso");
$verificar(!empty($dados['aluno']), "Aluno está presente nos dados do extrato");

// 1. Renderização do HTML do espelho A4
$html = PdfService::renderView('relatorios/extrato_aluno_pdf', ['dadosExtrato' => $dados]);

$verificar(!empty($html), "RenderView de extrato_aluno_pdf gerou HTML com sucesso");

// Verificação de elementos no banner do aluno
$verificar(strpos($html, 'class="student-banner"') !== false, "HTML contém o banner do aluno (.student-banner)");
$verificar(strpos($html, 'class="student-avatar') !== false, "HTML contém o avatar com inicial (.student-avatar)");
$verificar(strpos($html, mb_strtoupper($aluno['nome'], 'UTF-8')) !== false, "HTML contém o nome do aluno em caixa alta");
$verificar(strpos($html, $aluno['matricula']) !== false, "HTML contém a matrícula do aluno");
$verificar(strpos($html, 'Responsável:') !== false, "HTML contém o campo 'Responsável:'");
$verificar(strpos($html, 'Saldo Atual da Carteira') !== false, "HTML contém 'Saldo Atual da Carteira'");
$verificar(strpos($html, 'Saldo integrado familiar') !== false, "HTML contém 'Saldo integrado familiar'");

// Verificação do Stat-Strip com 4 cards
$verificar(strpos($html, 'class="stat-strip"') !== false, "HTML contém o Stat-Strip (.stat-strip)");
$verificar(strpos($html, 'Saldo Inicial') !== false, "HTML contém o card 'Saldo Inicial'");
$verificar(strpos($html, 'Total Recarregado') !== false, "HTML contém o card 'Total Recarregado'");
$verificar(strpos($html, 'Total Consumido') !== false, "HTML contém o card 'Total Consumido'");
$verificar(strpos($html, 'Saldo Atual') !== false, "HTML contém o card 'Saldo Atual'");
$verificar(strpos($html, 'Créditos aportados no período') !== false, "HTML contém legenda 'Créditos aportados no período'");
$verificar(strpos($html, 'Disponível na carteira') !== false, "HTML contém legenda 'Disponível na carteira'");

// Verificação da Seção Analítica de Movimentações
$verificar(strpos($html, 'Extrato Analítico de Consumo e Recargas') !== false, "HTML contém título da seção analítica");
$verificar(strpos($html, 'class="table-extrato"') !== false, "HTML contém a tabela (.table-extrato)");
$verificar(strpos($html, 'Saldo inicial') !== false, "HTML contém a linha de saldo inicial");
$verificar(strpos($html, 'Total gasto no período') !== false, "HTML contém rodapé 'Total gasto no período'");
$verificar(strpos($html, 'Saldo Atual: R$') !== false, "HTML contém rodapé 'Saldo Atual: R$'");

// 2. Geração do PDF via Gotenberg
$pdfBinario = PdfService::gerarExtratoConsumoAlunoPdf($dados);
$verificar(!empty($pdfBinario), "gerarExtratoConsumoAlunoPdf retornou binário de PDF");
$verificar(str_starts_with($pdfBinario, '%PDF-'), "Binário gerado é um arquivo PDF válido (cabeçalho %PDF-)");
$verificar(strlen($pdfBinario) > 1000, "Tamanho do PDF gerado é substancial (> 1000 bytes, gerou " . strlen($pdfBinario) . " bytes)");

// 3. Verificação da tela web de filtro atualizada
$htmlWeb = PdfService::renderView('relatorios/extrato_aluno', [
    'baseUrl' => '',
    'aluno' => null,
    'turmas' => [],
    'turmaId' => 0,
    'dataInicio' => $dataInicio,
    'dataFim' => $dataFim,
    'apenasSaldo' => false,
    'saldoApenasPeriodo' => false,
    'dadosExtrato' => null
]);
$verificar(strpos($htmlWeb, 'action="/relatorios/extrato-aluno/imprimir"') !== false, "Formulário web direciona para /relatorios/extrato-aluno/imprimir");
$verificar(strpos($htmlWeb, 'target="_blank"') !== false, "Formulário web abre em nova aba (target='_blank')");
$verificar(strpos($htmlWeb, 'Imprimir / Salvar PDF (A4)') !== false, "Botão principal da tela é 'Imprimir / Salvar PDF (A4)'");
$verificar(strpos($htmlWeb, 'Baixar PDF') !== false, "Botão 'Baixar PDF' está presente no filtro");
$verificar(strpos($htmlWeb, 'Exportar Planilha (CSV)') !== false, "Botão 'Exportar Planilha (CSV)' está presente no filtro");
$verificar(strpos($htmlWeb, 'Filtrar / Carregar Extrato') === false, "Botão antigo 'Filtrar / Carregar Extrato' foi substituído");

echo "\nResultado: {$pass}/{$total} asserções passaram com sucesso!\n";
if ($pass === $total) {
    echo "TODOS OS TESTES PASSARAM! 100% OK.\n";
    exit(0);
} else {
    echo "HOUVE FALHAS NOS TESTES.\n";
    exit(1);
}
