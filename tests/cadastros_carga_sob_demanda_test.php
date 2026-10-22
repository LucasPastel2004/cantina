<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Carga Sob Demanda em Cadastros Administrativos
 * 
 * Cobre:
 * 1. Colaboradores & Professores (/admin/colaboradores)
 * 2. Turmas Escolares (/admin/turmas)
 * 3. Alunos e Matrículas Escolares (/admin/alunos)
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
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

use App\Core\Auth;
use App\Core\Session;
use App\Controllers\AdminController;

class CadastrosCargaSobDemandaTest
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
        echo "======================================================================\n";
        echo "   TESTE: CARGA SOB DEMANDA EM COLABORADORES, TURMAS E ALUNOS\n";
        echo "======================================================================\n\n";

        // Simula login de administrador na sessão
        Session::start();
        Session::set('_auth_id', 1);
        Session::set('_auth_user', 'admin');
        Session::set('_auth_nome', 'Administrador Geral');
        Session::set('_auth_role', 'administrador');
        Session::set('_auth_logged', true);

        // ====================================================================
        // 1. COLABORADORES & PROFESSORES (/admin/colaboradores)
        // ====================================================================
        echo "--- [1] Testes em /admin/colaboradores ---\n";

        // 1.1 Acesso inicial sem parâmetros
        $_GET = [];
        ob_start();
        $controller = new AdminController();
        $controller->colaboradores();
        $htmlColabInicial = ob_get_clean();

        $this->assert(str_contains($htmlColabInicial, 'Colaboradores & Professores'), "Título da página de colaboradores presente.");
        $this->assert(str_contains($htmlColabInicial, 'Não há nada para mostrar aqui'), "Mensagem de lista vazia padrão presente inicialmente em colaboradores.");
        $this->assert(!str_contains($htmlColabInicial, 'title="Limpar Busca"'), "Botão 'Limpar' NÃO aparece por padrão no acesso inicial de colaboradores.");
        $this->assert(!str_contains($htmlColabInicial, 'Mostrando 1 a 7'), "Paginação/Registros NÃO aparecem na lista vazia inicial de colaboradores.");

        // 1.2 Clique em 'Buscar' de imediato (carga de lista geral)
        $_GET = ['filtrar' => '1'];
        ob_start();
        $controller = new AdminController();
        $controller->colaboradores();
        $htmlColabGeral = ob_get_clean();

        $this->assert(str_contains($htmlColabGeral, 'title="Limpar Busca"'), "Botão 'Limpar' aparece após clique em Buscar.");
        $this->assert(str_contains($htmlColabGeral, 'COL-'), "Matrículas de colaboradores exibidas na lista geral.");
        $this->assert(str_contains($htmlColabGeral, 'Inspetor Ricardo Santos'), "Colaborador Ricardo Santos presente na lista geral.");
        $this->assert(str_contains($htmlColabGeral, 'Mostrando'), "Paginação corporativa exibida com a lista ativa de colaboradores.");

        // 1.3 Busca personalizada
        $_GET = ['filtrar' => '1', 'busca' => 'Carlos'];
        ob_start();
        $controller = new AdminController();
        $controller->colaboradores();
        $htmlColabBusca = ob_get_clean();

        $this->assert(str_contains($htmlColabBusca, 'Prof. Carlos Eduardo'), "Colaborador Carlos Eduardo localizado na busca.");
        $this->assert(str_contains($htmlColabBusca, 'title="Limpar Busca"'), "Botão 'Limpar' presente na busca personalizada.");

        // ====================================================================
        // 2. TURMAS ESCOLARES (/admin/turmas)
        // ====================================================================
        echo "\n--- [2] Testes em /admin/turmas ---\n";

        // 2.1 Acesso inicial sem parâmetros
        $_GET = [];
        ob_start();
        $controller = new AdminController();
        $controller->turmas();
        $htmlTurmasInicial = ob_get_clean();

        $this->assert(str_contains($htmlTurmasInicial, 'Turmas Escolares'), "Título da página de turmas presente.");
        $this->assert(str_contains($htmlTurmasInicial, 'Não há nada para mostrar aqui'), "Mensagem de lista vazia padrão presente inicialmente em turmas.");
        $this->assert(!str_contains($htmlTurmasInicial, 'title="Limpar Filtros"'), "Botão 'Limpar' NÃO aparece por padrão no acesso inicial de turmas.");

        // 2.2 Clique em 'Filtrar' de imediato (carga de lista geral)
        $_GET = ['filtrar' => '1'];
        ob_start();
        $controller = new AdminController();
        $controller->turmas();
        $htmlTurmasGeral = ob_get_clean();

        $this->assert(str_contains($htmlTurmasGeral, 'title="Limpar Filtros"'), "Botão 'Limpar' aparece após clique em Filtrar turmas.");
        $this->assert(str_contains($htmlTurmasGeral, 'Maternal A') || str_contains($htmlTurmasGeral, '5º Ano A'), "Turmas escolares exibidas na lista geral.");
        $this->assert(!str_contains($htmlTurmasGeral, 'Não há nada para mostrar aqui'), "Lista ativa de turmas sem estado vazio.");

        // 2.3 Filtragem personalizada por turno
        $_GET = ['filtrar' => '1', 'turno' => 'matutino'];
        ob_start();
        $controller = new AdminController();
        $controller->turmas();
        $htmlTurmasTurno = ob_get_clean();

        $this->assert(str_contains($htmlTurmasTurno, 'title="Limpar Filtros"'), "Botão 'Limpar' presente no filtro por turno.");
        $this->assert(!str_contains($htmlTurmasTurno, 'Não há nada para mostrar aqui'), "Turmas do turno matutino retornadas.");

        // ====================================================================
        // 3. ALUNOS E MATRÍCULAS ESCOLARES (/admin/alunos)
        // ====================================================================
        echo "\n--- [3] Testes em /admin/alunos ---\n";

        // 3.1 Acesso inicial sem parâmetros
        $_GET = [];
        ob_start();
        $controller = new AdminController();
        $controller->alunos();
        $htmlAlunosInicial = ob_get_clean();

        $this->assert(str_contains($htmlAlunosInicial, 'Alunos e Matrículas'), "Título da página de alunos presente.");
        $this->assert(str_contains($htmlAlunosInicial, 'Não há nada para mostrar aqui'), "Mensagem de lista vazia padrão presente inicialmente em alunos.");
        $this->assert(!str_contains($htmlAlunosInicial, 'title="Limpar Filtros"'), "Botão 'Limpar' NÃO aparece por padrão no acesso inicial de alunos.");
        $this->assert(str_contains($htmlAlunosInicial, 'Exibindo 0 de 0 aluno(s)'), "Contador inicial exibe 0 de 0 aluno(s).");
        $this->assert(!str_contains($htmlAlunosInicial, 'table-data-grid'), "Tabela de dados NÃO é renderizada no estado inicial vazio.");

        // 3.2 Clique em 'Filtrar' de imediato (carga de lista geral)
        $_GET = ['filtrar' => '1'];
        ob_start();
        $controller = new AdminController();
        $controller->alunos();
        $htmlAlunosGeral = ob_get_clean();

        $this->assert(str_contains($htmlAlunosGeral, 'title="Limpar Filtros"'), "Botão 'Limpar' aparece após clique em Filtrar alunos.");
        $this->assert(str_contains($htmlAlunosGeral, 'table-data-grid'), "Tabela de alunos é renderizada com a lista ativa.");
        $this->assert(str_contains($htmlAlunosGeral, 'Exibindo 20 de'), "Contador de alunos ativos e paginação exibidos.");
        $this->assert(str_contains($htmlAlunosGeral, 'Pág. 1 de'), "Indicador de páginas ativo (Pág. 1 de 12).");
        $this->assert(str_contains($htmlAlunosGeral, '00002819') || str_contains($htmlAlunosGeral, '00002820'), "Matrículas de alunos renderizadas na tabela.");

        // 3.3 Navegação para página 2
        $_GET = ['filtrar' => '1', 'pagina' => '2'];
        ob_start();
        $controller = new AdminController();
        $controller->alunos();
        $htmlAlunosP2 = ob_get_clean();

        $this->assert(str_contains($htmlAlunosP2, 'title="Limpar Filtros"'), "Botão 'Limpar' preservado na paginação de alunos.");
        $this->assert(str_contains($htmlAlunosP2, 'Pág. 2 de'), "Página 2 exibida com sucesso.");

        // 3.4 Filtro personalizado por busca
        $_GET = ['filtrar' => '1', 'busca' => 'ANNE'];
        ob_start();
        $controller = new AdminController();
        $controller->alunos();
        $htmlAlunosBusca = ob_get_clean();

        $this->assert(str_contains($htmlAlunosBusca, '00002819'), "Aluna Anne Soares Menezes localizada na busca pela matrícula.");
        $this->assert(str_contains($htmlAlunosBusca, 'title="Limpar Filtros"'), "Botão 'Limpar' presente na busca personalizada de alunos.");

        echo "\n----------------------------------------------------------------------\n";
        echo "Resumo: {$this->pass} aprovados, {$this->fail} falhas.\n";
        echo "======================================================================\n";

        if ($this->fail > 0) {
            exit(1);
        }
    }
}

$teste = new CadastrosCargaSobDemandaTest();
$teste->run();
