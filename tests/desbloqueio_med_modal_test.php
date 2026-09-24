<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Modal e Fluxo de Desbloqueio MED
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

use App\Models\Responsavel;
use App\Models\Usuario;
use App\Core\Session;

class DesbloqueioMedModalTest
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
        echo "=== TESTE AUTOMATIZADO: MODAL E FLUXO DE DESBLOQUEIO MED ===\n\n";

        // 1. Verificação estática da view
        $viewPath = __DIR__ . '/../app/views/admin/contestacoes.php';
        $viewContent = file_get_contents($viewPath);

        $this->assert(str_contains($viewContent, 'id="modalDesbloqueioMed"'), 'View contém o modal #modalDesbloqueioMed');
        $this->assert(str_contains($viewContent, 'id="desbloqueio_justificativa"'), 'Modal contém campo textarea para justificativa');
        $this->assert(str_contains($viewContent, 'name="justificativa"'), 'Campo textarea possui name="justificativa"');
        $this->assert(str_contains($viewContent, 'abrirModalDesbloqueio'), 'Função JS abrirModalDesbloqueio presente na view');
        $this->assert(!str_contains($viewContent, "confirm('Confirma o desbloqueio manual"), 'Formulário antigo com confirm() nativo sem justificativa foi removido');

        // 2. Criação de responsável de teste bloqueado MED
        $cpfTeste = '999' . str_pad((string)mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        $this->pdo->exec("DELETE FROM responsaveis WHERE email = 'teste_desbloqueio_modal@escola.com'");
        
        $stmt = $this->pdo->prepare("
            INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, bloqueado_med, created_at)
            VALUES (:cpf, 'Responsavel Teste Modal', 'teste_desbloqueio_modal@escola.com', '11988889999', 'hash', -25.00, 1, NOW())
        ");
        $stmt->execute(['cpf' => $cpfTeste]);
        $respId = (int)$this->pdo->lastInsertId();

        $this->assert(Responsavel::isBloqueadoMed($respId), 'Responsável de teste criado com bloqueado_med = 1');

        // 3. Execução do desbloqueio com justificativa
        $adminId = (int)$this->pdo->query("SELECT id FROM usuarios WHERE perfil = 'administrador' AND deleted_at IS NULL LIMIT 1")->fetchColumn();
        if ($adminId <= 0) {
            $adminId = (int)$this->pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();
        }
        $justificativa = 'Comprovante apresentado presencialmente na secretaria em análise';


        $ok = Responsavel::desbloquearMed($respId, $adminId, $justificativa);
        $this->assert($ok, 'Responsavel::desbloquearMed executado com sucesso');
        $this->assert(!Responsavel::isBloqueadoMed($respId), 'Responsável agora está com bloqueado_med = 0');

        // 4. Verificação de auditoria
        $stmtAudit = $this->pdo->prepare("SELECT * FROM logs_auditoria WHERE tabela_afetada = 'responsaveis' AND registro_id = :id ORDER BY id DESC LIMIT 1");
        $stmtAudit->execute(['id' => $respId]);
        $audit = $stmtAudit->fetch(\PDO::FETCH_ASSOC);

        $this->assert(!empty($audit), 'Registro gravado em logs_auditoria');
        $this->assert(($audit['justificativa'] ?? '') === $justificativa, 'Justificativa gravada corretamente no log de auditoria');
        $this->assert(($audit['acao'] ?? '') === 'desbloqueio_med', 'Ação gravada como desbloqueio_med');

        // Limpeza
        $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$respId}");
        $this->pdo->exec("DELETE FROM logs_auditoria WHERE tabela_afetada = 'responsaveis' AND registro_id = {$respId}");

        echo "\n--------------------------------------------------\n";
        echo "RESULTADO: {$this->pass} de {$this->total} asserções passaram.\n";
        if ($this->pass === $this->total) {
            echo "STATUS: 100% DE SUCESSO!\n";
        }
        echo "--------------------------------------------------\n";
    }
}

$test = new DesbloqueioMedModalTest();
$test->executar();
