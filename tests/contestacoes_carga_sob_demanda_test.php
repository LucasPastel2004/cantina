<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Carga Sob Demanda na Tela de Contestações MED
 * 
 * Valida a regra de negócio onde a listagem de "Contas com Bloqueio ou Saldo Devedor"
 * inicia vazia na primeira visita à página (/admin/contestacoes) e somente renderiza registros
 * após o acionamento dos filtros/busca pelo usuário.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

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

use App\Models\Responsavel;
use App\Models\Usuario;
use App\Controllers\AdminController;
use App\Core\Session;

class ContestacoesCargaSobDemandaTest
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
        echo "=================================================================\n";
        echo " TESTES DE CARGA SOB DEMANDA - CONTESTAÇÕES & BLOQUEIOS MED\n";
        echo "=================================================================\n\n";

        // 1. Verificação estática da view
        echo "--- 1. Análise Estática da View (app/views/admin/contestacoes.php) ---\n";
        $viewPath = __DIR__ . '/../app/views/admin/contestacoes.php';
        $viewContent = file_get_contents($viewPath);

        $this->assert(str_contains($viewContent, '<input type="hidden" name="filtrar" value="1">'), 'Formulário possui input hidden filtrar=1');
        $this->assert(str_contains($viewContent, 'Não há nada para mostrar aqui'), 'View contém estado inicial "Não há nada para mostrar aqui"');
        $this->assert(str_contains($viewContent, '$jaFiltrado ? count($negativados) . \' exibido(s)\' : \'0 exibido(s)\''), 'Badge de exibição contabiliza 0 exibido(s) quando não filtrado');
        $this->assert(str_contains($viewContent, '<?php if ($jaFiltrado): ?>'), 'Botão "Limpar" condicionado à ocorrência de filtragem prévia');

        // 2. Configurar usuário autenticado e dados de teste
        echo "\n--- 2. Preparação de Dados e Sessão ---\n";
        Session::start();
        Session::set('_auth_logged', true);
        Session::set('_auth_type', 'usuario');
        Session::set('_auth_id', 1);
        Session::set('_auth_username', 'admin');
        Session::set('_auth_nome', 'Administrador Teste');
        Session::set('_auth_role', 'administrador');

        // Assegurar ao menos um responsável bloqueado/negativado no banco
        $cpfTeste = '55566677788';
        $carlos = $this->pdo->query("SELECT id FROM responsaveis WHERE cpf = '{$cpfTeste}' LIMIT 1")->fetch();
        if ($carlos) {
            $carlosId = (int)$carlos['id'];
            $this->pdo->exec("UPDATE responsaveis SET saldo_atual = -50.00, bloqueado_med = 1 WHERE id = {$carlosId}");
        } else {
            $this->pdo->exec("INSERT INTO responsaveis (cpf, nome, whatsapp, email, senha_hash, saldo_atual, bloqueado_med) VALUES ('{$cpfTeste}', 'Carlos Eduardo Ribeiro', '(62) 98888-7777', 'carlos@test.com', '', -50.00, 1)");
            $carlosId = (int)$this->pdo->lastInsertId();
        }
        $this->assert($carlosId > 0, "Responsável negativado de teste confirmado no banco (ID {$carlosId})");

        // 3. Simulação de acesso inicial (sem submissão de filtros)
        echo "\n--- 3. Acesso Inicial (/admin/contestacoes sem parâmetros de filtro) ---\n";
        $_GET = [];
        $_POST = [];

        $controller = new AdminController();
        ob_start();
        $controller->contestacoes();
        $htmlInicial = ob_get_clean();

        $this->assert(str_contains($htmlInicial, 'Não há nada para mostrar aqui'), 'Acesso inicial renderiza mensagem corporativa "Não há nada para mostrar aqui"');
        $this->assert(str_contains($htmlInicial, '0 exibido(s)'), 'Badge exibe "0 exibido(s)"');
        $this->assert(!str_contains($htmlInicial, 'id="tabelaContestacoes"'), 'Tabela #tabelaContestacoes NÃO é renderizada no acesso inicial');
        $this->assert(!str_contains($htmlInicial, '<tr class="linha-contestacao"'), 'Linhas de devedores (<tr class="linha-contestacao") não constam no HTML do acesso inicial');

        // 4. Simulação de submissão do formulário com "Filtrar Registros" (filtrar=1)
        echo "\n--- 4. Submissão do Formulário de Filtro (filtrar=1) ---\n";
        $_GET = ['filtrar' => '1'];
        $_POST = [];

        ob_start();
        $controller->contestacoes();
        $htmlFiltrado = ob_get_clean();

        $this->assert(str_contains($htmlFiltrado, 'id="tabelaContestacoes"'), 'Tabela #tabelaContestacoes É renderizada após acionar o filtro');
        $this->assert(!str_contains($htmlFiltrado, 'Não há nada para mostrar aqui'), 'Mensagem de estado vazio inicial NÃO é exibida após filtrar');
        $this->assert(str_contains($htmlFiltrado, 'Carlos Eduardo Ribeiro'), 'Responsável negativado Carlos Eduardo Ribeiro é exibido na listagem');
        $this->assert(!str_contains($htmlFiltrado, '0 exibido(s)'), 'Badge de contagem agora exibe o total real de registros retornados');

        // 5. Simulação de busca textual (busca='Carlos')
        echo "\n--- 5. Submissão com Busca Específica (busca=Carlos) ---\n";
        $_GET = ['filtrar' => '1', 'busca' => 'Carlos'];
        $_POST = [];

        ob_start();
        $controller->contestacoes();
        $htmlBusca = ob_get_clean();

        $this->assert(str_contains($htmlBusca, 'id="tabelaContestacoes"'), 'Tabela #tabelaContestacoes renderizada na busca textual');
        $this->assert(str_contains($htmlBusca, 'Carlos Eduardo Ribeiro'), 'Carlos Eduardo Ribeiro retornado com sucesso');

        echo "\n=======================================================\n";
        echo " RESULTADO: {$this->pass} / {$this->total} testes passaram!\n";
        echo "=======================================================\n\n";

        if ($this->pass === $this->total) {
            echo ">>> TODOS OS TESTES DE CARGA SOB DEMANDA PASSARAM COM SUCESSO! <<<\n";
            exit(0);
        } else {
            echo ">>> ALGUNS TESTES FALHARAM! <<<\n";
            exit(1);
        }
    }
}

$teste = new ContestacoesCargaSobDemandaTest();
$teste->executar();
