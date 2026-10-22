<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Carga Sob Demanda em Estoque e Despesas Operacionais
 * 
 * Valida o padrão onde as grades de "Controle de Estoque & Saldos Físicos" (/estoque)
 * e "Despesas Operacionais da Cantina" (/financeiro/despesas) iniciam vazias
 * e somente carregam os registros correspondentes após submissão explícita dos filtros.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

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

use App\Core\Session;
use App\Core\Auth;
use App\Core\RBAC;
use App\Controllers\EstoqueController;
use App\Controllers\DespesasController;

class CargaSobDemandaTest
{
    private int $total = 0;
    private int $pass = 0;
    private \PDO $pdo;
    private ?int $adminId = null;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
        $admin = $this->pdo->query("SELECT id FROM usuarios WHERE perfil = 'administrador' LIMIT 1")->fetch();
        $this->adminId = $admin ? (int)$admin['id'] : null;
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
        echo "=================================================================\n";
        echo " TESTES DE CARGA SOB DEMANDA: ESTOQUE E DESPESAS OPERACIONAIS\n";
        echo "=================================================================\n\n";

        $this->testarViewEstoque();
        $this->testarControllerEstoque();
        $this->testarViewDespesas();
        $this->testarControllerDespesas();

        echo "\n-----------------------------------------------------------------\n";
        echo " RESULTADO: {$this->pass}/{$this->total} testes aprovados.\n";
        echo "-----------------------------------------------------------------\n\n";

        if ($this->pass !== $this->total) {
            exit(1);
        }
    }

    private function testarViewEstoque(): void
    {
        echo "--- 1. Análise Estática da View (app/views/estoque/index.php) ---\n";
        $viewPath = __DIR__ . '/../app/views/estoque/index.php';
        $content = file_get_contents($viewPath);

        $this->assert(str_contains($content, '<input type="hidden" name="filtrar" value="1">'), 'Formulário de estoque possui input hidden filtrar=1');
        $this->assert(!str_contains($content, 'onchange="this.form.submit()"'), 'Seletores de estoque NÃO possuem onchange="this.form.submit()"');
        $this->assert(str_contains($content, 'Não há nada para mostrar aqui'), 'View de estoque possui estado inicial "Não há nada para mostrar aqui"');
        $this->assert(str_contains($content, 'Utilize os filtros acima e clique em "Filtrar"'), 'Mensagem orienta o usuário a configurar filtros e clicar em "Filtrar"');
        $this->assert(str_contains($content, 'Nenhum produto encontrado com os filtros aplicados'), 'Mensagem de busca sem resultados presente na view');
        $this->assert(str_contains($content, '!empty($jaFiltrado) ? count($produtos) : 0'), 'Contador exibe 0 produtos quando ainda não filtrado');
        $this->assert(str_contains($content, '<?php if (!empty($jaFiltrado)): ?>'), 'Botão "✕" e paginação condicionados a $jaFiltrado');
    }

    private function testarControllerEstoque(): void
    {
        echo "\n--- 2. Comportamento do EstoqueController (/estoque) ---\n";

        Session::start();
        Auth::loginUsuario([
            'id'      => $this->adminId ?: 1,
            'usuario' => 'admin-test',
            'nome'    => 'Admin Teste',
            'email'   => 'admin@teste.com',
            'perfil'  => RBAC::ROLE_ADMINISTRADOR
        ]);

        $controller = new EstoqueController();

        // Cenário A: Acesso inicial sem parâmetros GET
        $_GET = [];
        ob_start();
        $controller->index();
        $htmlInicial = ob_get_clean();

        $this->assert(str_contains($htmlInicial, 'Não há nada para mostrar aqui'), 'Acesso inicial a /estoque exibe mensagem "Não há nada para mostrar aqui"');
        $this->assert(str_contains($htmlInicial, 'Exibindo <span class="font-tabular fw-semibold">0</span> de <span class="font-tabular fw-semibold">0</span> produtos'), 'Acesso inicial exibe contador zerado: "Exibindo 0 de 0 produtos"');
        $this->assert(!str_contains($htmlInicial, 'title="Limpar Filtros">✕</a>'), 'Botão de limpar filtros NÃO aparece no estado inicial');

        // Cenário B: Clique no botão "Filtrar" (filtrar=1)
        $_GET = ['filtrar' => '1'];
        ob_start();
        $controller->index();
        $htmlFiltrado = ob_get_clean();

        $this->assert(!str_contains($htmlFiltrado, 'Não há nada para mostrar aqui'), 'Após clicar em "Filtrar", a mensagem de estado vazio desaparece');
        $this->assert(!str_contains($htmlFiltrado, 'Exibindo <span class="font-tabular fw-semibold">0</span> de <span class="font-tabular fw-semibold">0</span> produtos'), 'Após filtrar, contador reflete quantidade real de produtos');
        $this->assert(str_contains($htmlFiltrado, 'title="Limpar Filtros">✕</a>'), 'Botão de limpar filtros "✕" aparece quando filtrado');

        // Cenário C: Filtrar com busca específica (ex: Salgado)
        $_GET = ['filtrar' => '1', 'busca' => 'Coxinha'];
        ob_start();
        $controller->index();
        $htmlBusca = ob_get_clean();

        $this->assert(str_contains($htmlBusca, 'Coxinha'), 'Busca por "Coxinha" retorna produtos correspondentes');
    }

    private function testarViewDespesas(): void
    {
        echo "\n--- 3. Análise Estática da View (app/views/financeiro/despesas.php) ---\n";
        $viewPath = __DIR__ . '/../app/views/financeiro/despesas.php';
        $content = file_get_contents($viewPath);

        $this->assert(str_contains($content, '<input type="hidden" name="filtrar" value="1">'), 'Formulário de despesas possui input hidden filtrar=1');
        $this->assert(!str_contains($content, 'onchange="this.form.submit()"'), 'Seletores de despesas NÃO possuem onchange="this.form.submit()"');
        $this->assert(str_contains($content, 'Não há nada para mostrar aqui'), 'View de despesas possui estado inicial "Não há nada para mostrar aqui"');
        $this->assert(str_contains($content, 'clique no botão com a lupa'), 'Mensagem orienta o usuário a configurar filtros e clicar na lupa');
        $this->assert(str_contains($content, 'Nenhuma despesa localizada'), 'Mensagem de busca sem resultados presente na view');
        $this->assert(str_contains($content, '<?php if (!empty($jaFiltrado)): ?>'), 'Botão "✕" de limpar filtros condicionado a $jaFiltrado');
        $this->assert(str_contains($content, '<?php if (!empty($jaFiltrado) && $totalPaginas > 1): ?>'), 'Paginação de despesas condicionada a $jaFiltrado');
    }

    private function testarControllerDespesas(): void
    {
        echo "\n--- 4. Comportamento do DespesasController (/financeiro/despesas) ---\n";

        Session::start();
        Session::set('_auth_user_id', $this->adminId ?: 1);
        Session::set('_auth_role', RBAC::ROLE_ADMINISTRADOR);

        $controller = new DespesasController();

        // Cenário A: Acesso inicial sem parâmetros GET
        $_GET = [];
        ob_start();
        $controller->index();
        $htmlInicial = ob_get_clean();

        $this->assert(str_contains($htmlInicial, 'Não há nada para mostrar aqui'), 'Acesso inicial a /financeiro/despesas exibe mensagem "Não há nada para mostrar aqui"');
        $this->assert(str_contains($htmlInicial, 'clique no botão com a lupa'), 'Mensagem inicial orienta a clicar no botão com a lupa');
        $this->assert(str_contains($htmlInicial, '<option value="todas" selected>Todas as Competências</option>'), 'Opção padrão do campo competência é "Todas as Competências"');
        $this->assert(!str_contains($htmlInicial, 'title="Limpar Filtros"'), 'Botão de limpar filtros NÃO aparece no estado inicial');

        // Cenário B: Clique no botão com a lupa (filtrar=1)
        $_GET = ['filtrar' => '1', 'competencia' => 'todas'];
        ob_start();
        $controller->index();
        $htmlFiltrado = ob_get_clean();

        $this->assert(!str_contains($htmlFiltrado, 'Não há nada para mostrar aqui'), 'Após clicar na lupa com competência "todas", o estado vazio inicial desaparece');
        $this->assert(str_contains($htmlFiltrado, 'title="Limpar Filtros"'), 'Botão de limpar filtros "✕" aparece quando filtrado');

        // Cenário C: Filtrar com busca específica
        $_GET = ['filtrar' => '1', 'busca' => 'Folha'];
        ob_start();
        $controller->index();
        $htmlBusca = ob_get_clean();

        $this->assert(str_contains($htmlBusca, 'Folha'), 'Busca por termo retorna despesas correspondentes');
    }
}

$teste = new CargaSobDemandaTest();
$teste->executar();
