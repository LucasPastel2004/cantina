<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Paginação Oficial no Demonstrativo de Fechamento de Caixa
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
use App\Controllers\RelatoriosController;

class FechamentoCaixaPaginationTest
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
        echo "   TESTE: CARGA SOB DEMANDA & PAGINAÇÃO EM FECHAMENTO DE CAIXA\n";
        echo "=======================================================\n\n";

        // Simula login de administrador na sessão
        Session::start();
        Session::set('_auth_id', 1);
        Session::set('_auth_user', 'admin');
        Session::set('_auth_nome', 'Administrador Geral');
        Session::set('_auth_role', 'administrador');
        Session::set('_auth_logged', true);

        // -------------------------------------------------------------
        // CENÁRIO 1: Acesso inicial à página sem parâmetros (carga vazia padrão)
        // -------------------------------------------------------------
        $_GET = [];

        ob_start();
        $controller = new RelatoriosController();
        $controller->fechamentoCaixa();
        $htmlInicial = ob_get_clean();

        $this->assert(!empty($htmlInicial), "View de fechamento de caixa renderizou com sucesso no acesso inicial.");
        $this->assert(str_contains($htmlInicial, 'Demonstrativo de Fechamento de Caixa'), "Título da página presente no HTML.");
        $this->assert(str_contains($htmlInicial, 'Não há nada para mostrar aqui'), "Mensagem de lista vazia padrão presente inicialmente.");
        $this->assert(str_contains($htmlInicial, '0 sessão(ões) registrada(s)'), "Contador inicial exibe 0 sessões registradas.");
        $this->assert(!str_contains($htmlInicial, 'title="Limpar Filtros"'), "Botão 'Limpar' NÃO é exibido por padrão no acesso inicial.");
        $this->assert(!str_contains($htmlInicial, 'stat-strip'), "Stat-strip com métricas NÃO é exibida antes da filtragem.");
        $this->assert(!str_contains($htmlInicial, 'Mostrando'), "Paginação NÃO é exibida com a lista vazia.");

        // -------------------------------------------------------------
        // CENÁRIO 2: Usuário clica em 'Filtrar' de imediato (gera lista geral com todos os dados)
        // -------------------------------------------------------------
        $_GET = ['filtrar' => '1'];

        ob_start();
        $controller = new RelatoriosController();
        $controller->fechamentoCaixa();
        $htmlFiltradoGeral = ob_get_clean();

        $this->assert(str_contains($htmlFiltradoGeral, 'title="Limpar Filtros"'), "Botão 'Limpar' é exibido após a filtragem estar ativa.");
        $this->assert(str_contains($htmlFiltradoGeral, 'stat-strip'), "Stat-strip de métricas é devidamente exibida após a filtragem.");
        $this->assert(str_contains($htmlFiltradoGeral, 'Faturamento Total Declarado'), "Métrica de faturamento total presente.");
        $this->assert(str_contains($htmlFiltradoGeral, '#CX-'), "Linhas de sessões de caixa (#CX-) renderizadas com sucesso.");
        $this->assert(str_contains($htmlFiltradoGeral, 'Mostrando'), "Paginação corporativa exibida com a lista ativa.");
        $this->assert(str_contains($htmlFiltradoGeral, 'por pág.'), "Texto informativo '(20 por pág.)' presente na paginação.");
        $this->assert(str_contains($htmlFiltradoGeral, 'font-tabular'), "Classes numéricas 'font-tabular' presentes nos contadores.");

        // -------------------------------------------------------------
        // CENÁRIO 3: Navegação na paginação (Página 2 com lista ativa)
        // -------------------------------------------------------------
        $_GET = ['filtrar' => '1', 'pagina' => '2'];

        ob_start();
        $controller = new RelatoriosController();
        $controller->fechamentoCaixa();
        $htmlPagina2 = ob_get_clean();

        $this->assert(str_contains($htmlPagina2, 'pagina=1'), "Link para página anterior gerado corretamente preservando filtro.");
        $this->assert(str_contains($htmlPagina2, 'Próxima ›'), "Botão 'Próxima ›' presente na navegação.");
        $this->assert(str_contains($htmlPagina2, 'title="Limpar Filtros"'), "Botão 'Limpar' preservado durante a navegação entre páginas.");

        // -------------------------------------------------------------
        // CENÁRIO 4: Filtragem personalizada (Turno Matutino)
        // -------------------------------------------------------------
        $_GET = ['filtrar' => '1', 'turno' => 'matutino'];

        ob_start();
        $controller = new RelatoriosController();
        $controller->fechamentoCaixa();
        $htmlTurnoMatutino = ob_get_clean();

        $this->assert(str_contains($htmlTurnoMatutino, 'title="Limpar Filtros"'), "Botão 'Limpar' presente no filtro personalizado.");
        $this->assert(str_contains($htmlTurnoMatutino, 'Matutino'), "Resultados contêm sessões do turno Matutino.");
        $this->assert(!str_contains($htmlTurnoMatutino, 'Não há nada para mostrar aqui'), "Lista ativa com itens correspondentes ao filtro.");

        echo "\n-------------------------------------------------------\n";
        echo "Resumo: {$this->pass} aprovados, {$this->fail} falhas.\n";
        echo "=======================================================\n";

        if ($this->fail > 0) {
            exit(1);
        }
    }
}

$teste = new FechamentoCaixaPaginationTest();
$teste->run();
