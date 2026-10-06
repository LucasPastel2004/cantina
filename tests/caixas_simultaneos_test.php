<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Validação: Bloqueio Estrito de Abertura Simultânea de Caixas (Matutino e Vespertino)
 */

namespace Tests;

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Auth;
use App\Core\Session;
use App\Models\SessaoCaixa;
use App\Controllers\CaixaController;
use App\Controllers\PdvController;

class CaixasSimultaneosTest
{
    private \PDO $pdo;
    private int $operadorId = 1;
    private int $totalAssercoes = 0;
    private int $assercoesAprovadas = 0;

    public function __construct()
    {
        $this->pdo = \App\Core\Model::getPdo();
    }

    private function assert(bool $condicao, string $descricao): void
    {
        $this->totalAssercoes++;
        if ($condicao) {
            $this->assercoesAprovadas++;
            echo " [PASS] {$descricao}\n";
        } else {
            echo " [FAIL] {$descricao}\n";
        }
    }

    public function run(): void
    {
        echo "======================================================================\n";
        echo " TESTE DE REGRA DE NEGÓCIO: BLOQUEIO DE CAIXAS CONCORRENTES POR TURNO\n";
        echo "======================================================================\n\n";

        // 1. Limpeza inicial: fecha qualquer sessão aberta
        $this->pdo->exec("UPDATE sessoes_caixa SET status = 'fechado', fechado_em = NOW() WHERE status = 'aberto'");

        $this->assert(SessaoCaixa::obterQualquerSessaoAberta() === null, "Nenhuma sessão de caixa aberta no início do teste");

        // 2. Abertura do turno matutino
        $idMatutino = SessaoCaixa::abrir($this->operadorId, 'matutino', 50.00, 'Abertura Matutina');
        $this->assert($idMatutino > 0, "Caixa matutino aberto com ID #{$idMatutino}");

        $sessaoAbertaGeral = SessaoCaixa::obterQualquerSessaoAberta();
        $this->assert($sessaoAbertaGeral !== null && (int)$sessaoAbertaGeral['id'] === $idMatutino, "obterQualquerSessaoAberta localizou a sessão matutina");

        // 3. Tentativa de abrir vespertino via SessaoCaixa::abrir() enquanto matutino está aberto
        $bloqueioVespertino = false;
        $mensagemExcecao = '';
        try {
            SessaoCaixa::abrir($this->operadorId, 'vespertino', 40.00, 'Tentativa vespertino');
        } catch (\DomainException $e) {
            $bloqueioVespertino = true;
            $mensagemExcecao = $e->getMessage();
        }
        $this->assert($bloqueioVespertino, "SessaoCaixa::abrir bloqueou abertura do turno vespertino com matutino aberto");
        $this->assert(str_contains($mensagemExcecao, 'Não é permitido abrir o caixa do turno Vespertino'), "Mensagem de erro informa claramente bloqueio de turno concorrente");
        $this->assert(str_contains($mensagemExcecao, "Sessão #{$idMatutino}"), "Mensagem de erro cita a sessão matutina aberta");

        // 4. Tentativa de abrir matutino duplicado via SessaoCaixa::abrir()
        $bloqueioDuplicado = false;
        try {
            SessaoCaixa::abrir($this->operadorId, 'matutino', 20.00, 'Tentativa duplicada');
        } catch (\DomainException $e) {
            $bloqueioDuplicado = true;
        }
        $this->assert($bloqueioDuplicado, "SessaoCaixa::abrir bloqueou abertura duplicada no mesmo turno matutino");

        // 5. Teste do Controller: CaixaController::abrirTela com turno=vespertino
        $_GET['turno'] = 'vespertino';
        $_SESSION = [];
        $controller = new CaixaController();
        ob_start();
        $controller->abrirTela();
        ob_end_clean();
        $flashWarning = Session::getFlash('warning');
        $this->assert(!empty($flashWarning), "CaixaController::abrirTela gerou flash warning ao tentar abrir tela de vespertino");

        // 6. Teste do Controller: CaixaController::abrir POST com turno=vespertino
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = Session::generateCsrfToken();
        $_POST = [
            'csrf_token'           => $token,
            'turno'                => 'vespertino',
            'fundo_troco_inicial'  => '50,00',
            'observacoes'          => 'Tentativa via POST'
        ];
        ob_start();
        $controller->abrir();
        ob_end_clean();
        $flashDanger = Session::getFlash('danger');
        $this->assert(!empty($flashDanger), "CaixaController::abrir gerou flash danger e bloqueou POST para vespertino");
        $this->assert(str_contains($flashDanger[0] ?? '', 'Não é permitido abrir o caixa do turno Vespertino'), "Mensagem de flash no controller detalha a restrição");

        // 7. Teste do Controller: PdvController::abrirCaixaRapido POST com turno=vespertino
        $pdvController = new PdvController();
        $tokenPdv = Session::generateCsrfToken();
        $_POST = [
            'csrf_token'           => $tokenPdv,
            'turno'                => 'vespertino',
            'fundo_troco_inicial'  => '50,00'
        ];
        ob_start();
        $pdvController->abrirCaixaRapido();
        ob_end_clean();
        $flashDangerPdv = Session::getFlash('danger');
        $this->assert(!empty($flashDangerPdv), "PdvController::abrirCaixaRapido bloqueou abertura de turno concorrente");

        // 8. Fechar caixa matutino
        $sessaoFechada = SessaoCaixa::fechar($idMatutino, 50.00, 'Fechamento normal', $this->operadorId);
        $this->assert($sessaoFechada['status'] === 'fechado', "Caixa matutino fechado com sucesso");
        $this->assert(SessaoCaixa::obterQualquerSessaoAberta() === null, "Nenhuma sessão aberta após fechamento do matutino");

        // 9. Agora a abertura do turno vespertino DEVE funcionar normalmente
        $idVespertino = SessaoCaixa::abrir($this->operadorId, 'vespertino', 60.00, 'Abertura Vespertina Legítima');
        $this->assert($idVespertino > 0, "Caixa vespertino aberto com sucesso com ID #{$idVespertino}");
        $this->assert(SessaoCaixa::isCaixaAberto('vespertino'), "isCaixaAberto('vespertino') retorna true");

        // 10. Com vespertino aberto, tentativa de abrir matutino deve ser bloqueada
        $bloqueioMatutinoReverso = false;
        try {
            SessaoCaixa::abrir($this->operadorId, 'matutino', 30.00, 'Tentativa matutino reverso');
        } catch (\DomainException $e) {
            $bloqueioMatutinoReverso = true;
            $this->assert(str_contains($e->getMessage(), 'Não é permitido abrir o caixa do turno Matutino'), "Mensagem reversa informa que vespertino está aberto");
        }
        $this->assert($bloqueioMatutinoReverso, "Sistema bloqueia abertura do matutino enquanto vespertino estiver aberto");

        // 11. Fechar o vespertino e garantir término limpo
        SessaoCaixa::fechar($idVespertino, 60.00, 'Fechamento vespertino final', $this->operadorId);
        $this->assert(SessaoCaixa::obterQualquerSessaoAberta() === null, "Nenhuma sessão aberta ao final do teste");

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->assercoesAprovadas}/{$this->totalAssercoes} asserções aprovadas.\n";
        echo "----------------------------------------------------------------------\n";

        if ($this->assercoesAprovadas !== $this->totalAssercoes) {
            exit(1);
        }
    }
}

$test = new CaixasSimultaneosTest();
$test->run();
