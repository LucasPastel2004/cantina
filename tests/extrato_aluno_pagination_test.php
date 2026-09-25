<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Paginação do Extrato do Aluno (20 itens por página)
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';

// PSR-4 Autoloader
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

class ExtratoAlunoPaginationTest
{
    private int $total = 0;
    private int $pass = 0;

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->pass++;
            echo "  [PASS] {$msg}\n";
        } else {
            echo "  [FAIL] {$msg}\n";
        }
    }

    public function executar(): void
    {
        echo "=== TESTE AUTOMATIZADO: PAGINAÇÃO DO EXTRATO DO ALUNO (20 ITENS POR PÁGINA) ===\n\n";

        // 1. Verificação estática da view app/views/relatorios/extrato_aluno.php
        $viewExtrato = file_get_contents(__DIR__ . '/../app/views/relatorios/extrato_aluno.php');
        $this->assert(
            strpos($viewExtrato, 'id="secao-recargas"') !== false,
            'View extrato_aluno.php possui âncora id="secao-recargas"'
        );
        $this->assert(
            strpos($viewExtrato, 'id="secao-consumo"') !== false,
            'View extrato_aluno.php possui âncora id="secao-consumo"'
        );
        $this->assert(
            strpos($viewExtrato, '$paramNome      = \'pagina_recargas\';') !== false,
            'View extrato_aluno.php configura paramNome como pagina_recargas para recargas'
        );
        $this->assert(
            strpos($viewExtrato, '$paramNome      = \'pagina_consumo\';') !== false,
            'View extrato_aluno.php configura paramNome como pagina_consumo para consumo'
        );
        $this->assert(
            strpos($viewExtrato, 'total_registros_recargas') !== false,
            'View extrato_aluno.php exibe contagem total de registros de recargas no header e stats'
        );
        $this->assert(
            strpos($viewExtrato, 'total_registros_consumo') !== false,
            'View extrato_aluno.php exibe contagem total de registros de consumo no header e stats'
        );
        $this->assert(
            substr_count($viewExtrato, 'require __DIR__ . \'/../partials/pagination.php\';') >= 2,
            'View extrato_aluno.php inclui o partial pagination.php em ambas as seções'
        );

        // 2. Verificação estática do partial app/views/partials/pagination.php
        $partialPag = file_get_contents(__DIR__ . '/../app/views/partials/pagination.php');
        $this->assert(
            strpos($partialPag, '$paramNome = $paramNome ?? \'pagina\';') !== false,
            'Partial pagination.php suporta nome customizado de parâmetro de query string'
        );
        $this->assert(
            strpos($partialPag, '$anchor    = $anchor ?? \'\';') !== false,
            'Partial pagination.php suporta âncora de navegação customizada'
        );

        // 3. Verificação estática do controller app/controllers/RelatoriosController.php
        $controllerRel = file_get_contents(__DIR__ . '/../app/controllers/RelatoriosController.php');
        $this->assert(
            strpos($controllerRel, '$paginaRecargas = max(1, (int)$this->input(\'pagina_recargas\'') !== false,
            'RelatoriosController::extratoAluno lê parâmetro de pagina_recargas'
        );
        $this->assert(
            strpos($controllerRel, '$paginaConsumo  = max(1, (int)$this->input(\'pagina_consumo\'') !== false,
            'RelatoriosController::extratoAluno lê parâmetro de pagina_consumo'
        );
        $this->assert(
            strpos($controllerRel, '$itensPorPagina = 20;') !== false,
            'RelatoriosController::extratoAluno define padrão global de 20 itens por página'
        );
        $this->assert(
            strpos($controllerRel, '$this->obterDadosExtratoAluno($alunoId, $dataInicio, $dataFim, 1, 1, 20, false);') !== false,
            'RelatoriosController desabilita paginação ($paginar = false) para geração de PDF e impressão'
        );

        // 4. Teste funcional através de Reflection em obterDadosExtratoAluno
        $refMethod = new \ReflectionMethod(RelatoriosController::class, 'obterDadosExtratoAluno');
        $refMethod->setAccessible(true);
        $controller = new RelatoriosController();

        // Localizar um aluno existente para validação funcional
        $pdo = \Database::getInstance();
        $stmt = $pdo->query("SELECT id FROM alunos ORDER BY id ASC LIMIT 1");
        $alunoRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($alunoRow) {
            $alunoId = (int)$alunoRow['id'];
            $dataInicio = '2000-01-01';
            $dataFim = '2099-12-31';

            // Chamada paginada (padrão 20 por página)
            $dadosPaginados = $refMethod->invoke($controller, $alunoId, $dataInicio, $dataFim, 1, 1, 20, true);
            $this->assert(
                is_array($dadosPaginados),
                'obterDadosExtratoAluno retorna array estruturado com sucesso'
            );
            $this->assert(
                isset($dadosPaginados['itens_por_pagina']) && $dadosPaginados['itens_por_pagina'] === 20,
                'itens_por_pagina configurado como 20'
            );
            $this->assert(
                count($dadosPaginados['recargas']) <= 20,
                'Lista de recargas da página 1 contém no máximo 20 itens'
            );
            $this->assert(
                count($dadosPaginados['consumo']) <= 20,
                'Lista de consumo da página 1 contém no máximo 20 itens'
            );

            // Chamada não paginada ($paginar = false, para PDF e Impressão)
            $dadosCompletos = $refMethod->invoke($controller, $alunoId, $dataInicio, $dataFim, 1, 1, 20, false);
            $this->assert(
                count($dadosCompletos['recargas']) === $dadosCompletos['total_registros_recargas'],
                'Chamada com $paginar = false retorna 100% dos registros de recargas para o PDF'
            );
            $this->assert(
                count($dadosCompletos['consumo']) === $dadosCompletos['total_registros_consumo'],
                'Chamada com $paginar = false retorna 100% dos registros de consumo para o PDF'
            );
            $this->assert(
                $dadosPaginados['total_recargas'] === $dadosCompletos['total_recargas'],
                'Total recarregado (R$) é idêntico em chamadas paginadas e completas (período integral)'
            );
            $this->assert(
                $dadosPaginados['total_consumo'] === $dadosCompletos['total_consumo'],
                'Total consumido (R$) é idêntico em chamadas paginadas e completas (período integral)'
            );
        } else {
            $this->assert(true, 'Nenhum aluno no banco, validação funcional pulada');
        }

        echo "\nResultado: {$this->pass}/{$this->total} asserções passaram.\n";
        if ($this->pass !== $this->total) {
            exit(1);
        }
    }
}

$teste = new ExtratoAlunoPaginationTest();
$teste->executar();
