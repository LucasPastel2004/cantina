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
        echo "   TESTE: PAGINAÇÃO NO DEMONSTRATIVO DE FECHAMENTO DE CAIXA\n";
        echo "=======================================================\n\n";

        // Simula login de administrador na sessão
        Session::start();
        Session::set('_auth_id', 1);
        Session::set('_auth_user', 'admin');
        Session::set('_auth_nome', 'Administrador Geral');
        Session::set('_auth_role', 'administrador');
        Session::set('_auth_logged', true);

        // Limpa parâmetros GET
        $_GET = [];

        // Captura o output do controlador fechamentoCaixa()
        ob_start();
        $controller = new RelatoriosController();
        $controller->fechamentoCaixa();
        $html = ob_get_clean();

        // 1. Verifica se a página renderizou sem erros
        $this->assert(!empty($html), "View de fechamento de caixa renderizou com sucesso.");
        $this->assert(str_contains($html, 'Demonstrativo de Fechamento de Caixa'), "Título da página presente no HTML.");

        // 2. Verifica se o padrão corporativo de paginação está presente
        $this->assert(str_contains($html, 'Mostrando'), "Texto inicial da paginação 'Mostrando' presente.");
        $this->assert(str_contains($html, 'por pág.'), "Texto informativo '(20 por pág.)' presente.");
        $this->assert(str_contains($html, '‹ Anterior'), "Botão de navegação '‹ Anterior' presente.");
        $this->assert(str_contains($html, 'font-tabular'), "Classes numéricas 'font-tabular' presentes nos contadores.");

        // 3. Verifica que a lista antiga simples NÃO está mais presente
        $this->assert(!str_contains($html, '<ul class="pagination pagination-sm mb-0">'), "Estrutura antiga 'pagination-sm mb-0' foi devidamente removida.");
        $this->assert(!str_contains($html, 'Página 1 de 19 (371 registros)'), "Texto antigo 'Página X de Y (Z registros)' foi devidamente substituído.");

        // 4. Teste com página 2 para conferir botão Anterior habilitado e Próxima
        $_GET = ['pagina' => '2'];
        ob_start();
        $controller = new RelatoriosController();
        $controller->fechamentoCaixa();
        $htmlPagina2 = ob_get_clean();

        $this->assert(str_contains($htmlPagina2, 'pagina=1'), "Link para página anterior gerado corretamente com query string.");
        $this->assert(str_contains($htmlPagina2, 'Próxima ›'), "Botão 'Próxima ›' presente.");

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
