<?php
define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

use App\Services\PdfService;
use App\Controllers\RelatoriosController;

$controller = new RelatoriosController();
$reflection = new ReflectionClass(RelatoriosController::class);
$method = $reflection->getMethod('obterDadosVendasAvancadas');
$method->setAccessible(true);

$cenarios = [
    '1. Padrão: Vendas por alimento com agrupamento por dia' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'agrupar_periodo' => 'sim',
        'agrupar_alimento' => 'sim',
        'emitir_vendas_aluno' => 'nao'
    ],
    '2. Com Horário, Forma de Pagamento e Observação' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'agrupar_periodo' => 'sim',
        'emitir_horario' => 'sim',
        'emitir_forma_pagamento' => 'sim',
        'emitir_observacao' => 'sim',
        'emitir_vendas_aluno' => 'nao'
    ],
    '3. Com Agrupamento por Tipo de Pessoa' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'agrupar_tipo_pessoa' => 'sim',
        'emitir_vendas_aluno' => 'nao'
    ],
    '4. Sem Agrupar por Período (Consolidado Geral do Período)' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'agrupar_periodo' => 'nao',
        'agrupar_alimento' => 'sim',
        'emitir_vendas_aluno' => 'nao'
    ],
    '5. Sem Agrupar por Alimento (Vendas Individuais Detalhadas)' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'agrupar_alimento' => 'nao',
        'emitir_vendas_aluno' => 'nao'
    ],
    '6. Ordenação de Alimentos: Menos Vendidos' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'ordenar_alimentos' => 'menos_vendidos',
        'emitir_vendas_aluno' => 'nao'
    ],
    '7. Vendas por Aluno (Lista Geral Consolidada)' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'emitir_vendas_aluno' => 'sim',
        'agrupar_vendas_aluno' => 'sim',
        'agrupar_serie_turma' => 'nao'
    ],
    '8. Vendas por Aluno Agrupadas por Série/Turma' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'emitir_vendas_aluno' => 'sim',
        'agrupar_vendas_aluno' => 'sim',
        'agrupar_serie_turma' => 'sim'
    ],
    '9. Vendas por Aluno com Quebra de Página por Turma' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'emitir_vendas_aluno' => 'sim',
        'agrupar_vendas_aluno' => 'sim',
        'agrupar_serie_turma' => 'sim',
        'quebrar_pagina_turma' => 'sim'
    ],
    '10. Vendas por Aluno Detalhadas com Compras Linha a Linha' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'sim',
        'emitir_vendas_aluno' => 'sim',
        'agrupar_vendas_aluno' => 'nao',
        'agrupar_serie_turma' => 'sim'
    ],
    '11. Ordenar Alunos por Maior Consumo' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_aluno' => 'sim',
        'ordenar_consumo_aluno' => 'sim',
        'ordenar_por' => 'maior_consumo'
    ],
    '12. Ordenar Alunos por Menor Consumo' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_aluno' => 'sim',
        'ordenar_consumo_aluno' => 'sim',
        'ordenar_por' => 'menor_consumo'
    ],
    '13. Ordenar Alunos Alfabeticamente por Nome' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_aluno' => 'sim',
        'ordenar_consumo_aluno' => 'nao'
    ],
    '14. Apenas Vendas por Aluno (Sem Alimentos)' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'emitir_vendas_alimento' => 'nao',
        'emitir_vendas_aluno' => 'sim',
        'agrupar_serie_turma' => 'sim'
    ],
    '15. Com Estornos Incluídos' => [
        'data_inicio' => '2026-09-01',
        'data_fim' => '2026-09-30',
        'incluir_estornos' => 'sim',
        'emitir_observacao' => 'sim'
    ]
];

echo "=======================================================\n";
echo "   VALIDANDO TODAS AS POSSIBILIDADES DO RELATÓRIO\n";
echo "=======================================================\n\n";

$passou = 0;
$erros = 0;

foreach ($cenarios as $nome => $filtros) {
    echo "Testando: {$nome}...\n";
    $dados = $method->invoke($controller, $filtros);
    $html = PdfService::renderView('relatorios/vendas_alimento_pdf', ['dadosVendas' => $dados]);
    
    // Verificações
    $tam = strlen($html);
    if ($tam < 500) {
        echo "  [ERRO] HTML muito pequeno: {$tam} bytes\n";
        $erros++;
        continue;
    }

    if (isset($filtros['emitir_horario']) && $filtros['emitir_horario'] === 'sim') {
        if (!str_contains($html, 'Horário')) {
            echo "  [ERRO] Deveria conter 'Horário'\n";
            $erros++;
            continue;
        }
    }

    if (isset($filtros['agrupar_serie_turma']) && $filtros['agrupar_serie_turma'] === 'sim' && ($filtros['emitir_vendas_aluno'] ?? 'nao') === 'sim') {
        if (!str_contains($html, 'TURMA:')) {
            echo "  [ERRO] Deveria conter cabeçalho 'TURMA:'\n";
            $erros++;
            continue;
        }
    }

    if (isset($filtros['quebrar_pagina_turma']) && $filtros['quebrar_pagina_turma'] === 'sim') {
        if (!str_contains($html, 'page-break-turma')) {
            echo "  [ERRO] Deveria conter 'page-break-turma'\n";
            $erros++;
            continue;
        }
    }

    if (isset($filtros['emitir_vendas_alimento']) && $filtros['emitir_vendas_alimento'] === 'nao') {
        if (str_contains($html, 'Total do dia :')) {
            echo "  [ERRO] Não deveria conter 'Total do dia :' quando alimentos estiver desligado\n";
            $erros++;
            continue;
        }
    }

    echo "  [OK] HTML gerado com {$tam} bytes e validações aprovadas.\n";

    // Teste de compilação em PDF real
    $dados['operador'] = 'Administração';
    $pdf = PdfService::gerarRelatorioVendasAlimentoPdf($dados);
    $tamPdf = strlen($pdf);
    if ($tamPdf < 1000) {
        echo "  [ERRO] PDF binário gerado muito pequeno ({$tamPdf} bytes)\n";
        $erros++;
        continue;
    }
    echo "  [OK] PDF gerado com {$tamPdf} bytes (Gotenberg Engine).\n";
    $passou++;
}

echo "\n-------------------------------------------------------\n";
echo "Total de cenários testados: " . count($cenarios) . "\n";
echo "Sucessos: {$passou} | Falhas: {$erros}\n";
echo "=======================================================\n";

if ($erros > 0) exit(1);
