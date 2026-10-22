<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Migração do Botão de Impressão de Corte para a Aba de Relatórios
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

use App\Controllers\PedidosController;
use App\Controllers\RelatoriosController;
use App\Core\Session;

class RelatorioCorteTurnoTest
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
        echo " TESTES DA MIGRAÇÃO DO RELATÓRIO DE CORTE DO TURNO PARA RELATÓRIOS\n";
        echo "=================================================================\n\n";

        // 1. Verificação estática da view operacional/corte.php
        echo "--- 1. Análise da Tela Operacional (/operacional/corte) ---\n";
        $corteOperacionalPath = __DIR__ . '/../app/views/operacional/corte.php';
        $corteOperacionalContent = file_get_contents($corteOperacionalPath);

        $this->assert(str_contains($corteOperacionalContent, 'id="btnRelatorioCorteTurno"'), 'Botão com id btnRelatorioCorteTurno presente na view operacional/corte');
        $this->assert(str_contains($corteOperacionalContent, 'Relatório Corte do Turno'), 'Texto do botão alterado para "Relatório Corte do Turno"');
        $this->assert(str_contains($corteOperacionalContent, '/relatorios/corte?data='), 'Botão redireciona diretamente para /relatorios/corte com parâmetros');
        $this->assert(!str_contains($corteOperacionalContent, 'Imprimir / Salvar PDF (A4)'), 'Botão antigo "Imprimir / Salvar PDF (A4)" removido de operacional/corte');

        // 2. Verificação da Sidebar (Flyout de Relatórios)
        echo "\n--- 2. Análise da Barra Lateral (sidebar.php) ---\n";
        $sidebarPath = __DIR__ . '/../app/views/layouts/sidebar.php';
        $sidebarContent = file_get_contents($sidebarPath);

        $this->assert(str_contains($sidebarContent, '/relatorios/corte'), 'Flyout de Relatórios contém link para /relatorios/corte');
        $this->assert(str_contains($sidebarContent, 'corte_relatorio'), 'Mapeamento $isRelatoriosActive inclui corte_relatorio');

        // 3. Verificação da Central de Relatórios (app/views/relatorios/index.php)
        echo "\n--- 3. Análise da Central de Relatórios (/relatorios) ---\n";
        $relatoriosIndexPath = __DIR__ . '/../app/views/relatorios/index.php';
        $relatoriosIndexContent = file_get_contents($relatoriosIndexPath);

        $this->assert(str_contains($relatoriosIndexContent, 'href="<?php echo $baseUrl; ?>/relatorios/corte"'), 'Card 6 aponta para /relatorios/corte');

        // 4. Configuração de sessão para execução dos controllers
        echo "\n--- 4. Execução do Controller e View (/relatorios/corte) ---\n";
        Session::start();
        Session::set('_auth_logged', true);
        Session::set('_auth_type', 'usuario');
        Session::set('_auth_id', 1);
        Session::set('_auth_username', 'admin');
        Session::set('_auth_nome', 'Administrador');
        Session::set('_auth_role', 'administrador');

        $_GET = [
            'data' => date('Y-m-d'),
            'turno' => 'matutino'
        ];

        $relatoriosController = new RelatoriosController();

        ob_start();
        $relatoriosController->corte();
        $htmlRelatorioCorte = ob_get_clean();

        $this->assert(str_contains($htmlRelatorioCorte, 'Relatório de Corte do Turno'), 'Tela renderizada com título "Relatório de Corte do Turno"');
        $this->assert(str_contains($htmlRelatorioCorte, 'Painel de Corte Operacional'), 'Botão de retorno rápido para o painel operacional presente');
        $this->assert(str_contains($htmlRelatorioCorte, '/operacional/corte'), 'Link de retorno aponta para /operacional/corte');
        $this->assert(str_contains($htmlRelatorioCorte, 'id="btnImprimirCorteA4"'), 'Botão de impressão oficial id btnImprimirCorteA4 presente');
        $this->assert(str_contains($htmlRelatorioCorte, 'Imprimir / Salvar PDF (A4)'), 'Botão "Imprimir / Salvar PDF (A4)" devidamente integrado na tela de relatórios');
        $this->assert(!str_contains($htmlRelatorioCorte, 'Total de Peças Encomendadas'), 'Stat-strip redundante removido da tela de filtros do relatório');
        $this->assert(!str_contains($htmlRelatorioCorte, 'Demandas de Produção por Salgado'), 'Tabela duplicada removida da tela de filtros do relatório');
        $this->assert(!str_contains($htmlRelatorioCorte, 'textoWhatsArea'), 'Card de WhatsApp removido da tela de filtros do relatório');

        // 5. Teste da emissão impressa A4 (/relatorios/corte/imprimir)
        echo "\n--- 5. Execução do Espelho A4 (/relatorios/corte/imprimir) ---\n";
        ob_start();
        $relatoriosController->corteImprimir();
        $htmlImprimirA4 = ob_get_clean();

        $this->assert(str_contains($htmlImprimirA4, 'Relatório de Corte do Turno') || str_contains($htmlImprimirA4, 'Consolidação de Compra de Lanches'), 'Documento A4 timbrado contém cabeçalho oficial de corte');
        $this->assert(str_contains($htmlImprimirA4, 'window.print()'), 'Barra de controle superior com window.print() presente');
        $this->assert(str_contains($htmlImprimirA4, 'Total de Peças'), 'Discriminação de Total de Peças presente no A4');

        echo "\n=======================================================\n";
        echo " RESULTADO: {$this->pass} / {$this->total} testes passaram!\n";
        echo "=======================================================\n\n";

        if ($this->pass === $this->total) {
            echo ">>> TODOS OS TESTES PASSARAM COM 100% DE SUCESSO! <<<\n";
            exit(0);
        } else {
            echo ">>> ALGUNS TESTES FALHARAM! <<<\n";
            exit(1);
        }
    }
}

$test = new RelatorioCorteTurnoTest();
$test->executar();
