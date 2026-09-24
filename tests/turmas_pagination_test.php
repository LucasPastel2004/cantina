<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Paginação para Turmas e Colaboradores
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

use App\Models\Turma;
use App\Models\Colaborador;
use App\Models\Configuracao;

class TurmasPaginationTest
{
    private int $total = 0;
    private int $pass = 0;
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

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
        echo "=== TESTE AUTOMATIZADO: PAGINAÇÃO DE TURMAS E COLABORADORES ===\n\n";

        // 1. Verificação estática das views
        $viewTurmas = file_get_contents(__DIR__ . '/../app/views/admin/turmas.php');
        $this->assert(str_contains($viewTurmas, "require __DIR__ . '/../partials/pagination.php'"), 'View admin/turmas.php inclui o componente pagination.php');

        $viewColabs = file_get_contents(__DIR__ . '/../app/views/admin/colaboradores.php');
        $this->assert(str_contains($viewColabs, "require __DIR__ . '/../partials/pagination.php'"), 'View admin/colaboradores.php inclui o componente pagination.php');

        // 2. Verificação de configuração de itens por página
        $itensPadrao = Configuracao::getItensPorPagina();
        $this->assert($itensPadrao >= 5 && $itensPadrao <= 100, "Configuracao::getItensPorPagina() retorna valor válido ({$itensPadrao})");

        // 3. Teste do Modelo Turma com paginação
        $totalTurmas = Turma::contarTodas();
        $this->assert($totalTurmas >= 0, "Turma::contarTodas() executado com sucesso: total = {$totalTurmas}");

        $pagina1 = Turma::listarTodas('', '', 20, 0);
        $this->assert(count($pagina1) <= 20, "Turma::listarTodas com limite 20 retornou " . count($pagina1) . " itens (<= 20)");

        if ($totalTurmas > 20) {
            $pagina2 = Turma::listarTodas('', '', 20, 20);
            $this->assert(count($pagina2) > 0, "Turma::listarTodas página 2 com offset 20 retornou itens");
            $this->assert($pagina1[0]['id'] !== $pagina2[0]['id'], "Página 1 e Página 2 possuem itens distintos");
        }

        // Teste de filtros com contagem de Turma
        $totalMatutino = Turma::contarTodas('matutino');
        $totalVespertino = Turma::contarTodas('vespertino');
        $this->assert(($totalMatutino + $totalVespertino) <= $totalTurmas, "Soma dos turnos ({$totalMatutino} + {$totalVespertino}) é consistente com o total ({$totalTurmas})");

        // 4. Teste do Modelo Colaborador com paginação
        $totalColabs = Colaborador::contarTodos();
        $this->assert($totalColabs >= 0, "Colaborador::contarTodos() executado com sucesso: total = {$totalColabs}");

        $colabsP1 = Colaborador::listarTodos(false, '', 20, 0);
        $this->assert(count($colabsP1) <= 20, "Colaborador::listarTodos com limite 20 retornou " . count($colabsP1) . " itens (<= 20)");

        echo "\n--------------------------------------------------\n";
        echo "RESULTADO: {$this->pass} de {$this->total} asserções passaram.\n";
        if ($this->pass === $this->total) {
            echo "STATUS: 100% DE SUCESSO!\n";
        }
        echo "--------------------------------------------------\n";
    }
}

$test = new TurmasPaginationTest();
$test->executar();
