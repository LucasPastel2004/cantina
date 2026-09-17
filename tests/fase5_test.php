<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Bateria de Testes Automatizados — Fase 5: Identidade Visual e Layouts Base
 * 
 * Execução via CLI: php tests/fase5_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_START', microtime(true));

// Autoloader PSR-4 nativo
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

// Inicialização de sessão para testes
$config = require __DIR__ . '/../config/config.php';
\App\Core\Session::start($config);

class Fase5Test
{
    private int $totalAssercoes = 0;
    private int $sucessos = 0;
    private int $falhas = 0;
    private array $erros = [];

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->sucessos++;
            echo "  [PASS] {$descricao}\n";
        } else {
            $this->falhas++;
            $this->erros[] = $descricao;
            echo "  [FAIL] {$descricao}\n";
        }
    }

    public function run(): void
    {
        echo "======================================================================\n";
        echo "  INICIANDO SUÍTE DE TESTES — FASE 5: IDENTIDADE VISUAL E LAYOUTS BASE\n";
        echo "======================================================================\n\n";

        $this->testarExistenciaArquivos();
        $this->testarBlindagemAcessoDireto();
        $this->testarConformidadeDesignSystem();
        $this->testarRenderizacaoLayoutPortal();
        $this->testarRenderizacaoLayoutAdmin();
        $this->testarRenderizacaoLayoutAuth();
        $this->testarRenderizacaoComponentesParciais();
        $this->testarShowcaseController();

        echo "\n----------------------------------------------------------------------\n";
        echo "RESULTADO FINAL: {$this->sucessos} de {$this->totalAssercoes} asserções passaram.\n";

        if ($this->falhas > 0) {
            echo "FALHAS ENCONTRADAS ({$this->falhas}):\n";
            foreach ($this->erros as $erro) {
                echo "  - {$erro}\n";
            }
            exit(1);
        } else {
            echo "STATUS: 100% DE SUCESSO — FASE 5 HOMOLOGADA COM SUCESSO!\n";
            echo "----------------------------------------------------------------------\n";
        }
    }

    private function testarExistenciaArquivos(): void
    {
        echo "[1] Verificação de Arquivos Obrigatórios da Fase 5...\n";

        $arquivos = [
            'app/views/layouts/header.php',
            'app/views/layouts/footer.php',
            'app/views/layouts/sidebar.php',
            'app/views/layouts/portal.php',
            'app/views/layouts/admin.php',
            'app/views/layouts/auth.php',
            'app/views/partials/alert.php',
            'app/views/partials/modal.php',
            'app/views/partials/empty_state.php',
            'app/views/partials/loading.php',
            'app/views/partials/stat_strip.php',
            'app/controllers/ShowcaseController.php',
            'app/views/showcase/portal.php',
            'app/views/showcase/admin.php',
            'app/views/showcase/components.php',
            'assets/css/custom.css',
            'assets/js/app.js',
            'assets/img/logo-colegio.jpg',
        ];

        foreach ($arquivos as $relPath) {
            $fullPath = __DIR__ . '/../' . $relPath;
            $this->assert(file_exists($fullPath), "Arquivo obrigatório existe: [{$relPath}]");
        }
    }

    private function testarBlindagemAcessoDireto(): void
    {
        echo "\n[2] Verificação de Blindagem contra Acesso Direto (CANTINA_SISTEMA)...\n";

        $arquivosPhp = [
            'app/views/layouts/header.php',
            'app/views/layouts/footer.php',
            'app/views/layouts/sidebar.php',
            'app/views/layouts/portal.php',
            'app/views/layouts/admin.php',
            'app/views/layouts/auth.php',
            'app/views/partials/alert.php',
            'app/views/partials/modal.php',
            'app/views/partials/empty_state.php',
            'app/views/partials/loading.php',
            'app/views/partials/stat_strip.php',
            'app/controllers/ShowcaseController.php',
            'app/views/showcase/portal.php',
            'app/views/showcase/admin.php',
            'app/views/showcase/components.php',
        ];

        foreach ($arquivosPhp as $relPath) {
            $content = file_get_contents(__DIR__ . '/../' . $relPath);
            $temProtecao = str_contains($content, "defined('CANTINA_SISTEMA') or die");
            $this->assert($temProtecao, "Proteção de segurança presente em [{$relPath}]");
        }
    }

    private function testarConformidadeDesignSystem(): void
    {
        echo "\n[3] Verificação de Diretrizes Visuais em custom.css e app.js...\n";

        $css = file_get_contents(__DIR__ . '/../assets/css/custom.css');

        // Acento único azul cobalto #1d4ed8
        $this->assert(str_contains($css, '#1d4ed8'), "Acento institucional cobalto (#1d4ed8) presente no custom.css");
        
        // Numerais tabulares
        $this->assert(str_contains($css, 'font-variant-numeric: tabular-nums'), "Fonte tabular (tabular-nums) definida");
        $this->assert(str_contains($css, "font-feature-settings: 'tnum' 1"), "Recurso OpenType tnum ativado");

        // Foco acessível
        $this->assert(str_contains($css, ':focus-visible'), "Foco acessível (:focus-visible) definido globalmente");

        // Efeito tátil nos botões
        $this->assert(str_contains($css, 'scale(0.985)'), "Efeito tátil de clique físico presente nos botões");

        // Largura da Sidebar 240px
        $this->assert(str_contains($css, 'width: 240px'), "Sidebar operacional configurada com 240px");

        // Ausência de CDNs externas em layouts
        $layouts = ['portal.php', 'admin.php', 'auth.php', 'header.php', 'footer.php'];
        foreach ($layouts as $l) {
            $lContent = file_get_contents(__DIR__ . '/../app/views/layouts/' . $l);
            $hasCdn = str_contains($lContent, 'cdn.jsdelivr') || str_contains($lContent, 'cdnjs.cloudflare') || str_contains($lContent, 'unpkg.com');
            $this->assert(!$hasCdn, "Layout [{$l}] 100% local (sem dependência de CDN externa)");
        }

        // JavaScript toggle da sidebar
        $js = file_get_contents(__DIR__ . '/../assets/js/app.js');
        $this->assert(str_contains($js, 'function toggleSidebar'), "Função toggleSidebar presente no app.js");
        $this->assert(str_contains($js, 'function abrirModal'), "Função abrirModal presente no app.js");
    }

    private function testarRenderizacaoLayoutPortal(): void
    {
        echo "\n[4] Teste de Renderização do Layout Portal da Família...\n";

        $testController = new class extends \App\Core\Controller {
            public function renderTest(): string {
                ob_start();
                $this->render('showcase/portal', [
                    'pageTitle' => 'Teste Portal',
                    'activeMenu' => 'inicio',
                    'saldoAtual' => 250.75,
                    'saldoReservado' => 15.00,
                    'responsavelNome' => 'Responsável Teste',
                    'dependentes' => [
                        ['nome' => 'Filho 1', 'turma' => '5º A', 'turno' => 'Matutino', 'alergias' => ['Glúten']]
                    ],
                    'pedidosSemana' => []
                ], 'portal');
                return ob_get_clean();
            }
        };

        $html = $testController->renderTest();

        $this->assert(str_contains($html, 'Portal da Família'), "Layout renderiza título do Portal da Família");
        $this->assert(str_contains($html, 'R$ 250,75'), "Layout renderiza saldo familiar com formatação brasileira (R$ 250,75)");
        $this->assert(str_contains($html, 'logo-colegio.jpg'), "Layout renderiza logotipo oficial circular");
        $this->assert(str_contains($html, 'font-tabular'), "Layout inclui classes de tipografia tabular");
        $this->assert(str_contains($html, 'Responsável Teste'), "Layout exibe nome do responsável logado");
    }

    private function testarRenderizacaoLayoutAdmin(): void
    {
        echo "\n[5] Teste de Renderização do Layout Painel da Cantina (com Sidebar)...\n";

        $testController = new class extends \App\Core\Controller {
            public function renderTest(): string {
                ob_start();
                $this->render('showcase/admin', [
                    'pageTitle' => 'Painel Cantina Teste',
                    'pageHeading' => 'Título de Teste',
                    'activeMenu' => 'corte',
                    'turnoAtivo' => 'Matutino',
                    'statusCaixa' => 'Aberto',
                    'user' => ['nome' => 'Operador Teste', 'usuario' => 'operador-teste', 'perfil' => 'operador'],
                    'statItems' => [
                        ['label' => 'Total', 'value' => '50 un', 'status' => 'primary']
                    ],
                    'demandasSalgados' => []
                ], 'admin');
                return ob_get_clean();
            }
        };

        $html = $testController->renderTest();

        $this->assert(str_contains($html, 'app-sidebar'), "Layout admin renderiza estrutura da Sidebar");
        $this->assert(str_contains($html, 'Corte do Turno'), "Sidebar contém link para Corte do Turno");
        $this->assert(str_contains($html, 'Operador Teste'), "Sidebar exibe dados do operador logado");
        $this->assert(str_contains($html, 'Matutino'), "Topbar exibe badge do turno ativo");
        $this->assert(str_contains($html, 'Aberto'), "Topbar exibe status do caixa do turno");
    }

    private function testarRenderizacaoLayoutAuth(): void
    {
        echo "\n[6] Teste de Renderização do Layout Auth...\n";

        // Cria uma view temporária de teste para auth
        $testController = new class extends \App\Core\Controller {
            public function renderTest(): string {
                ob_start();
                // Renderizamos uma view de teste existente dentro do layout auth
                $this->render('showcase/components', [
                    'pageTitle' => 'Acesso Seguro',
                ], 'auth');
                return ob_get_clean();
            }
        };

        $html = $testController->renderTest();

        $this->assert(str_contains($html, 'auth-wrapper'), "Layout auth renderiza container auth-wrapper");
        $this->assert(str_contains($html, 'auth-logo'), "Layout auth renderiza classe auth-logo para imagem de 84px");
        $this->assert(str_contains($html, 'Colégio Monteiro Lobato'), "Layout auth exibe identificação institucional");
    }

    private function testarRenderizacaoComponentesParciais(): void
    {
        echo "\n[7] Teste dos Componentes Parciais (alert, modal, empty_state, loading, stat_strip)...\n";

        // 1. Alerta
        ob_start();
        $message = 'Mensagem de sucesso para teste';
        $type = 'success';
        $title = 'Aprovado';
        require __DIR__ . '/../app/views/partials/alert.php';
        $alertHtml = ob_get_clean();
        $this->assert(str_contains($alertHtml, 'alert-enterprise-success'), "Componente alert.php renderiza classe correta");
        $this->assert(str_contains($alertHtml, 'Mensagem de sucesso para teste'), "Componente alert.php renderiza mensagem");

        // 2. Modal
        ob_start();
        $modalId = 'modalTesteUnitario';
        $modalTitle = 'Título Modal Teste';
        $modalBody = '<p>Conteúdo do modal</p>';
        require __DIR__ . '/../app/views/partials/modal.php';
        $modalHtml = ob_get_clean();
        $this->assert(str_contains($modalHtml, 'id="modalTesteUnitario"'), "Componente modal.php renderiza ID especificado");
        $this->assert(str_contains($modalHtml, 'modal-enterprise'), "Componente modal.php aplica classe modal-enterprise");

        // 3. Empty State
        ob_start();
        $emptyMessage = 'Não há nada para mostrar aqui';
        $emptyDescription = 'Descrição de teste vazia';
        require __DIR__ . '/../app/views/partials/empty_state.php';
        $emptyHtml = ob_get_clean();
        $this->assert(str_contains($emptyHtml, 'Não há nada para mostrar aqui'), "Componente empty_state.php renderiza mensagem padrão");
        $this->assert(str_contains($emptyHtml, 'empty-state-enterprise'), "Componente empty_state.php aplica classe empty-state-enterprise");

        // 4. Loading
        ob_start();
        $loadingText = 'Carregando dados...';
        $isSkeleton = false;
        require __DIR__ . '/../app/views/partials/loading.php';
        $loadingHtml = ob_get_clean();
        $this->assert(str_contains($loadingHtml, 'spinner-blue'), "Componente loading.php renderiza spinner azul");

        ob_start();
        $isSkeleton = true;
        $skeletonRows = 4;
        require __DIR__ . '/../app/views/partials/loading.php';
        $skeletonHtml = ob_get_clean();
        $this->assert(str_contains($skeletonHtml, 'skeleton-line'), "Componente loading.php renderiza skeleton lines");

        // 5. Stat Strip
        ob_start();
        $statItems = [
            ['label' => 'Métrica Teste', 'value' => '100', 'subtext' => 'Subtexto', 'status' => 'success']
        ];
        require __DIR__ . '/../app/views/partials/stat_strip.php';
        $statHtml = ob_get_clean();
        $this->assert(str_contains($statHtml, 'stat-strip'), "Componente stat_strip.php renderiza container stat-strip");
        $this->assert(str_contains($statHtml, 'Métrica Teste'), "Componente stat_strip.php renderiza label da métrica");
        $this->assert(str_contains($statHtml, 'font-tabular'), "Componente stat_strip.php aplica fonte tabular ao valor");
    }

    private function testarShowcaseController(): void
    {
        echo "\n[8] Teste do Controlador ShowcaseController...\n";

        $controller = new \App\Controllers\ShowcaseController();
        $this->assert(is_subclass_of($controller, \App\Core\Controller::class), "ShowcaseController herda de App\\Core\\Controller");
        $this->assert(method_exists($controller, 'portal'), "ShowcaseController possui método portal()");
        $this->assert(method_exists($controller, 'admin'), "ShowcaseController possui método admin()");
        $this->assert(method_exists($controller, 'components'), "ShowcaseController possui método components()");
    }
}

// Execução
$suite = new Fase5Test();
$suite->run();
