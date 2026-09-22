<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Rotas Web, Controller e RBAC da Fase 13 (PDV e Operação de Balcão)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace Tests;

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\RBAC;
use App\Core\Auth;
use App\Core\Session;
use App\Controllers\PdvController;

class PdvWebTest
{
    private \PDO $pdo;
    private int $total = 0;
    private int $aprovados = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO TESTES DE CONTROLADOR E RBAC — FASE 13 (PDV)\n";
        echo "======================================================================\n\n";

        $this->testarEstruturaMetodosController();
        $this->testarPermissoesRbac();
        $this->testarAcessoVisitanteHttpReal();
        $this->testarRenderizacaoViewPdv();

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->aprovados}/{$this->total} testes aprovados.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->aprovados !== $this->total) {
            exit(1);
        }
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->aprovados++;
            echo " [PASS] {$msg}\n";
        } else {
            echo " [FAIL] {$msg}\n";
        }
    }

    private function testarEstruturaMetodosController(): void
    {
        echo "--- [1] Estrutura e Ações do PdvController ---\n";
        $controller = new PdvController();

        $this->assert(method_exists($controller, 'index'), "PdvController possui action 'index'");
        $this->assert(method_exists($controller, 'abrirCaixaRapido'), "PdvController possui action 'abrirCaixaRapido'");
        $this->assert(method_exists($controller, 'finalizarVenda'), "PdvController possui action 'finalizarVenda'");
        $this->assert(method_exists($controller, 'pixGerar'), "PdvController possui action 'pixGerar'");
        $this->assert(method_exists($controller, 'pixStatus'), "PdvController possui action 'pixStatus'");
        $this->assert(method_exists($controller, 'buscarColaborador'), "PdvController possui action 'buscarColaborador'");
        $this->assert(method_exists($controller, 'buscarAluno'), "PdvController possui action 'buscarAluno'");
        $this->assert(method_exists($controller, 'estornoBalcao'), "PdvController possui action 'estornoBalcao'");
        $this->assert(method_exists($controller, 'sobras'), "PdvController possui action 'sobras'");
        $this->assert(method_exists($controller, 'transferirSobras'), "PdvController possui action 'transferirSobras'");
        $this->assert(method_exists($controller, 'comprovanteHtml'), "PdvController possui action 'comprovanteHtml'");
        $this->assert(method_exists($controller, 'comprovantePdf'), "PdvController possui action 'comprovantePdf'");
    }

    private function testarPermissoesRbac(): void
    {
        echo "\n--- [2] Teste de Permissões RBAC para o PDV ---\n";

        // Operador tem acesso
        Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'operador-caixa',
            'nome'    => 'Operador Caixa',
            'email'   => 'operador@escola.com',
            'perfil'  => RBAC::ROLE_OPERADOR
        ]);
        $this->assert(RBAC::can('operacao_pdv_balcao') === true, "Perfil 'operador' tem permissão 'operacao_pdv_balcao'");
        $this->assert(RBAC::can('estorno_balcao') === true, "Perfil 'operador' tem permissão 'estorno_balcao'");
        $this->assert(RBAC::can('aproveitamento_sobras') === true, "Perfil 'operador' tem permissão 'aproveitamento_sobras'");

        // Administrador tem acesso
        Auth::loginUsuario([
            'id'      => 2,
            'usuario' => 'admin-cantina',
            'nome'    => 'Administrador Geral',
            'email'   => 'admin@escola.com',
            'perfil'  => RBAC::ROLE_ADMINISTRADOR
        ]);
        $this->assert(RBAC::can('operacao_pdv_balcao') === true, "Perfil 'administrador' tem permissão 'operacao_pdv_balcao'");
        $this->assert(RBAC::can('estorno_balcao') === true, "Perfil 'administrador' tem permissão 'estorno_balcao'");

        // Responsável (Família) é bloqueado
        Auth::loginResponsavel([
            'id'            => 10,
            'cpf'           => '12345678901',
            'nome'          => 'Responsável Bloqueado',
            'email'         => 'bloq@familia.com',
            'whatsapp'      => '11999999999',
            'saldo_atual'   => 100.00,
            'bloqueado_med' => 0
        ]);
        $this->assert(RBAC::can('operacao_pdv_balcao') === false, "Perfil 'responsavel' é bloqueado para 'operacao_pdv_balcao'");
        $this->assert(RBAC::can('estorno_balcao') === false, "Perfil 'responsavel' é bloqueado para 'estorno_balcao'");
        $this->assert(RBAC::can('aproveitamento_sobras') === false, "Perfil 'responsavel' é bloqueado para 'aproveitamento_sobras'");
        Auth::logout();
    }

    private function testarAcessoVisitanteHttpReal(): void
    {
        echo "\n--- [3] Simulação HTTP Real (cURL) em /operacional/pdv ---\n";

        $ch = curl_init('http://localhost/operacional/pdv');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assert(
            $httpCode === 302,
            "Requisição HTTP direta de visitante anônimo retorna HTTP 302 Redirection"
        );
        $this->assert(
            str_contains($response, 'Location: /login'),
            "Cabeçalho de redirecionamento aponta para '/login'"
        );
    }

    private function testarRenderizacaoViewPdv(): void
    {
        echo "\n--- [4] Renderização e Integridade do Template do PDV ---\n";

        $viewPath = __DIR__ . '/../app/views/operacional/pdv.php';
        $this->assert(file_exists($viewPath), "Arquivo de view operacional/pdv.php existe");

        $viewContent = file_get_contents($viewPath);
        $this->assert(str_contains($viewContent, 'Cupom de Venda'), "Template contém painel de Cupom de Venda");
        $this->assert(str_contains($viewContent, 'pdv-grid'), "Template contém container .pdv-grid");
        $this->assert(str_contains($viewContent, 'modalEstornoBalcao'), "Template inclui modal de Estorno de Balcão (F8)");
        $this->assert(str_contains($viewContent, 'modalSobrasRecreio'), "Template inclui modal de Aproveitamento de Sobras (F9)");
        $this->assert(str_contains($viewContent, 'modalAbrirCaixa'), "Template inclui modal de Abertura de Caixa");
        $this->assert(str_contains($viewContent, 'modalPosVenda'), "Template inclui modal de Pós-Venda e Comprovante");
        $this->assert(str_contains($viewContent, 'modalPixBalcao'), "Template inclui modal de Pix Dinâmico no Balcão");
    }
}

$teste = new PdvWebTest();
$teste->run();
