<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes de Segurança: Prevenção contra Quebra de Titularidade (IDOR) no Portal da Família
 * 
 * Execução via Docker: docker compose exec app php tests/portal_idor_security_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Session;
use App\Core\Auth;
use App\Core\Model;
use App\Controllers\PortalController;

class PortalIdorSecurityTest
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;

    private function assert(bool $condition, string $description): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  [PASS] {$description}\n";
        } else {
            echo "  [FAIL] {$description}\n";
        }
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " INICIANDO TESTES DE SEGURANÇA CONTRA IDOR (PORTAL DA FAMÍLIA)\n";
        echo "====================================================================\n\n";

        Session::start();

        // 1. Setup de teste com 2 responsáveis e 2 alunos distintos
        $sufixo = random_int(1000, 9999);
        $cpfResp1 = "901" . sprintf('%06d', random_int(1, 999999)) . "11";
        $cpfResp2 = "902" . sprintf('%06d', random_int(1, 999999)) . "22";

        $resp1 = \App\Models\Responsavel::localizarOuCriarPorCpf([
            'cpf' => $cpfResp1,
            'nome' => "Responsável Família A {$sufixo}",
            'whatsapp' => "(11) 98888-1111"
        ]);
        $resp1Id = (int)$resp1['id'];

        $resp2 = \App\Models\Responsavel::localizarOuCriarPorCpf([
            'cpf' => $cpfResp2,
            'nome' => "Responsável Família B {$sufixo}",
            'whatsapp' => "(11) 98888-2222"
        ]);
        $resp2Id = (int)$resp2['id'];

        $turma = \App\Models\Turma::listarTodas()[0];

        $aluno1Id = \App\Models\Aluno::criar([
            'matricula' => "MAT-TEST-IDOR-A-{$sufixo}",
            'nome' => "Aluno Família A {$sufixo}",
            'turma_id' => $turma['id'],
            'responsavel_id' => $resp1Id,
            'turno' => 'matutino'
        ]);

        $aluno2Id = \App\Models\Aluno::criar([
            'matricula' => "MAT-TEST-IDOR-B-{$sufixo}",
            'nome' => "Aluno Família B {$sufixo}",
            'turma_id' => $turma['id'],
            'responsavel_id' => $resp2Id,
            'turno' => 'matutino'
        ]);

        // Simula login como Responsável 1 (Família A)
        $_SESSION['_auth_logged'] = true;
        $_SESSION['_auth_type'] = 'responsavel';
        $_SESSION['_auth_id'] = $resp1Id;
        $_SESSION['_auth_role'] = 'responsavel';
        $_SESSION['_auth_username'] = $cpfResp1;
        $_SESSION['_auth_nome'] = "Responsável Família A {$sufixo}";

        $this->testarIdorGetAlergias($resp1Id, $aluno1Id, $aluno2Id, $sufixo);
        $this->testarIdorPostAlergiasSalvar($resp1Id, $aluno1Id, $aluno2Id);
        $this->testarIdorGetAgendamento($resp1Id, $aluno1Id, $aluno2Id);
        $this->testarIdorPostAgendamentoSalvar($resp1Id, $aluno1Id, $aluno2Id);
        $this->testarIdorGetPedidos($resp1Id, $aluno1Id, $aluno2Id);
        $this->testarAcessoLegitimo($resp1Id, $aluno1Id, $sufixo);

        echo "\n--------------------------------------------------------------------\n";
        echo " RESULTADO DOS TESTES DE SEGURANÇA IDOR:\n";
        echo " Total de asserções: {$this->totalAssertions}\n";
        echo " Aprovadas:          {$this->passedAssertions}\n";
        echo " Reprovadas:         " . ($this->totalAssertions - $this->passedAssertions) . "\n";
        echo " Taxa de sucesso:    " . number_format(($this->passedAssertions / $this->totalAssertions) * 100, 1) . "%\n";
        echo "--------------------------------------------------------------------\n";

        if ($this->passedAssertions !== $this->totalAssertions) {
            exit(1);
        }
    }

    private function testarIdorGetAlergias(int $resp1Id, int $aluno1Id, int $aluno2Id, int $sufixo): void
    {
        echo "1. Testando Tentativa de IDOR em GET /portal/alergias?aluno={$aluno2Id}:\n";

        $_SESSION['_flash'] = [];
        $_GET = ['aluno' => $aluno2Id];
        $_POST = [];

        $logCountBefore = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'acesso_negado_idor' AND detalhes_json LIKE '%/portal/alergias%'"
        )['c'];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->alergias();
        $output = ob_get_clean();

        // 1. Deve registrar evento em logs_seguranca
        $logCountAfter = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'acesso_negado_idor' AND detalhes_json LIKE '%/portal/alergias%'"
        )['c'];
        $this->assert($logCountAfter > $logCountBefore, "Tentativa de IDOR em GET /portal/alergias foi registrada em logs_seguranca");

        // 2. Deve disparar mensagem flash de perigo
        $flashes = Session::getFlash('danger');
        $this->assert(!empty($flashes), "Mensagem flash do tipo 'danger' foi gerada");
        $this->assert(
            str_contains($flashes[0] ?? '', 'Você não tem permissão para visualizar ou gerenciar as restrições'),
            "Mensagem informa explicitamente falta de permissão para visualizar restrições do aluno"
        );

        // 3. Ao renderizar a tela limpa com a mensagem flash, o alerta corporativo é exibido e os dados do aluno invasor NUNCA vazam
        Session::setFlash('danger', $flashes[0]);
        $_GET = [];
        ob_start();
        $ctrl->alergias();
        $html = ob_get_clean();

        $this->assert(str_contains($html, 'alert-enterprise-danger'), "Componente alert-enterprise-danger é exibido na tela");
        $this->assert(!str_contains($html, "Aluno Família B {$sufixo}"), "Dados do aluno de outra família NÃO são expostos na view");
        $this->assert(str_contains($html, "Aluno Família A {$sufixo}"), "Dados do aluno legítimo do usuário logado permanecem preservados");
    }

    private function testarIdorPostAlergiasSalvar(int $resp1Id, int $aluno1Id, int $aluno2Id): void
    {
        echo "\n2. Testando Tentativa de IDOR em POST /portal/alergias/salvar (Modificação de aluno_id no HTML):\n";

        $token = Session::generateCsrfToken();
        $_SESSION['_flash'] = [];
        $_POST = [
            'csrf_token' => $token,
            'aluno_id'   => $aluno2Id, // Aluno da Família B
            'alergenos'  => [1, 2]     // Tenta forçar alérgenos para o filho de outra família
        ];
        $_GET = [];

        $logCountBefore = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'tentativa_idor_mutacao' AND detalhes_json LIKE '%/portal/alergias/salvar%'"
        )['c'];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->alergiasSalvar();
        $output = ob_get_clean();

        // 1. Deve registrar evento em logs_seguranca
        $logCountAfter = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'tentativa_idor_mutacao' AND detalhes_json LIKE '%/portal/alergias/salvar%'"
        )['c'];
        $this->assert($logCountAfter > $logCountBefore, "Tentativa de alteração não autorizada (IDOR) registrada em logs_seguranca");

        // 2. Alérgenos do Aluno 2 NÃO devem ter sido alterados
        $alergenosAluno2 = \App\Models\Aluno::obterIdsAlergenos($aluno2Id);
        $this->assert(empty($alergenosAluno2), "Alérgenos do aluno da outra família NÃO foram alterados (blindagem atômica mantida)");

        // 3. Mensagem flash de erro de permissão
        $flashes = Session::getFlash('danger');
        $this->assert(!empty($flashes), "Mensagem flash do tipo 'danger' foi gerada no POST");
        $this->assert(
            str_contains($flashes[0] ?? '', 'Você não tem permissão para alterar as restrições alimentares'),
            "Mensagem flash informa recusa de permissão para alteração"
        );
    }

    private function testarIdorGetAgendamento(int $resp1Id, int $aluno1Id, int $aluno2Id): void
    {
        echo "\n3. Testando Tentativa de IDOR em GET /portal/agendamento?aluno={$aluno2Id}:\n";

        $_SESSION['_flash'] = [];
        $_GET = ['aluno' => $aluno2Id];
        $_POST = [];

        $logCountBefore = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'acesso_negado_idor' AND detalhes_json LIKE '%/portal/agendamento%'"
        )['c'];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->agendamento();
        $output = ob_get_clean();

        $logCountAfter = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'acesso_negado_idor' AND detalhes_json LIKE '%/portal/agendamento%'"
        )['c'];
        $this->assert($logCountAfter > $logCountBefore, "Tentativa de IDOR em agendamento registrada em logs_seguranca");

        $flashes = Session::getFlash('danger');
        $this->assert(!empty($flashes), "Flash danger gerado para agendamento não autorizado");
        $this->assert(
            str_contains($flashes[0] ?? '', 'Você não tem permissão para agendar lanches para este aluno'),
            "Texto da mensagem informa recusa de agendamento para aluno de outra família"
        );
    }

    private function testarIdorPostAgendamentoSalvar(int $resp1Id, int $aluno1Id, int $aluno2Id): void
    {
        echo "\n4. Testando Tentativa de IDOR em POST /portal/agendamento/salvar com aluno_id={$aluno2Id}:\n";

        $token = Session::generateCsrfToken();
        $_SESSION['_flash'] = [];
        $_POST = [
            'csrf_token'   => $token,
            'aluno_id'     => $aluno2Id,
            'data_consumo' => date('Y-m-d', strtotime('+7 days')),
            'itens'        => [1 => 1]
        ];

        $logCountBefore = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'tentativa_idor_mutacao' AND detalhes_json LIKE '%/portal/agendamento/salvar%'"
        )['c'];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->agendamentoSalvar();
        $output = ob_get_clean();

        $logCountAfter = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'tentativa_idor_mutacao' AND detalhes_json LIKE '%/portal/agendamento/salvar%'"
        )['c'];
        $this->assert($logCountAfter > $logCountBefore, "Tentativa de mutação de pedido por IDOR registrada em logs_seguranca");

        $flashes = Session::getFlash('danger');
        $this->assert(!empty($flashes) && str_contains($flashes[0], 'Você não tem permissão para agendar'), "Recusa e bloqueio confirmados com flash");
    }

    private function testarIdorGetPedidos(int $resp1Id, int $aluno1Id, int $aluno2Id): void
    {
        echo "\n5. Testando Tentativa de IDOR em GET /portal/pedidos?aluno={$aluno2Id}:\n";

        $_SESSION['_flash'] = [];
        $_GET = ['aluno' => $aluno2Id];
        $_POST = [];

        $logCountBefore = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'acesso_negado_idor' AND detalhes_json LIKE '%/portal/pedidos%'"
        )['c'];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->pedidos();
        $output = ob_get_clean();

        $logCountAfter = (int)Model::fetchOne(
            "SELECT COUNT(*) as c FROM logs_seguranca WHERE evento = 'acesso_negado_idor' AND detalhes_json LIKE '%/portal/pedidos%'"
        )['c'];
        $this->assert($logCountAfter > $logCountBefore, "Tentativa de filtragem não autorizada em pedidos registrada em logs_seguranca");

        $flashes = Session::getFlash('danger');
        $this->assert(!empty($flashes) && str_contains($flashes[0], 'Você não tem permissão para visualizar pedidos'), "Recusa e bloqueio de filtro confirmados com flash");
    }

    private function testarAcessoLegitimo(int $resp1Id, int $aluno1Id, int $sufixo): void
    {
        echo "\n6. Testando Acesso Legítimo ao Próprio Dependente (GET /portal/alergias?aluno={$aluno1Id}):\n";

        $_SESSION['_flash'] = [];
        $_GET = ['aluno' => $aluno1Id];
        $_POST = [];

        $ctrl = new PortalController();
        ob_start();
        $ctrl->alergias();
        $html = ob_get_clean();

        $this->assert(str_contains($html, "Aluno Família A {$sufixo}"), "Acesso legítimo renderiza o próprio dependente com sucesso");
        $this->assert(!str_contains($html, 'alert-enterprise-danger'), "Nenhum alerta de perigo exibido em navegação legítima");
        $this->assert(empty(Session::getFlash('danger')), "Nenhuma mensagem flash gerada para acesso legítimo");
    }
}

$suite = new PortalIdorSecurityTest();
$suite->run();
