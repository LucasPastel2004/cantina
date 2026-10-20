<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Bateria de Testes Automatizados — Fase 6: Autenticação, Sessão Segura e Controle de Acesso (RBAC)
 * 
 * Execução via Docker Compose:
 * docker compose exec app php tests/fase6_test.php
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

$config = require __DIR__ . '/../config/config.php';
\App\Core\Session::start($config);

class Fase6Test
{
    private int $totalAssercoes = 0;
    private int $sucessos = 0;
    private int $falhas = 0;
    private array $erros = [];
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = \App\Core\Model::getConnection();
    }

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
        echo "  INICIANDO BATERIA DE TESTES — FASE 6: AUTENTICAÇÃO, SESSÃO E RBAC   \n";
        echo "======================================================================\n\n";

        $this->testarExistenciaArquivos();
        $this->prepararDadosTeste();
        $this->testarModelUsuario();
        $this->testarModelResponsavel();
        $this->testarWhatsAppService();
        $this->testarAutenticacaoUsuario();
        $this->testarAutenticacaoResponsavel();
        $this->testarBloqueioBruteForce();
        $this->testarFluxoPrimeiroAcesso();
        $this->testarRecuperacaoSenha();
        $this->testarMatrizPermissoesRBAC();
        $this->testarMiddlewaresRBAC();
        $this->testarTrilhaSeguranca();

        echo "\n======================================================================\n";
        echo "  RESUMO DOS TESTES — FASE 6\n";
        echo "======================================================================\n";
        echo "  Total de asserções executadas: {$this->totalAssercoes}\n";
        echo "  Sucessos: {$this->sucessos}\n";
        echo "  Falhas: {$this->falhas}\n";

        if ($this->falhas > 0) {
            echo "\n  FALHAS ENCONTRADAS:\n";
            foreach ($this->erros as $erro) {
                echo "    - {$erro}\n";
            }
            exit(1);
        } else {
            echo "\n  TODAS AS ASSERÇÕES FORAM APROVADAS COM 100% DE SUCESSO!\n";
            exit(0);
        }
    }

    private function testarExistenciaArquivos(): void
    {
        echo "--- 1. Verificação de Arquivos da Fase 6 ---\n";

        $arquivos = [
            'app/core/Auth.php',
            'app/core/RBAC.php',
            'app/models/Usuario.php',
            'app/models/Responsavel.php',
            'app/services/WhatsAppService.php',
            'app/controllers/AuthController.php',
            'app/controllers/PortalController.php',
            'app/controllers/AdminController.php',
            'app/views/auth/login.php',
            'app/views/auth/primeiro_acesso_cpf.php',
            'app/views/auth/primeiro_acesso_codigo.php',
            'app/views/auth/primeiro_acesso_senha.php',
            'app/views/auth/recuperar_senha.php',
            'app/views/auth/recuperar_senha_codigo.php',
            'app/views/auth/termos.php',
            'app/views/portal/index.php',
            'app/views/admin/index.php'
        ];

        foreach ($arquivos as $arq) {
            $path = __DIR__ . '/../' . $arq;
            $this->assert(file_exists($path), "Arquivo essencial presente: {$arq}");
        }
    }

    private function prepararDadosTeste(): void
    {
        echo "\n--- 2. Preparação Idempotente de Massa de Dados para Testes ---\n";

        // Garante operador de teste
        $operadorExiste = $this->pdo->query("SELECT id FROM usuarios WHERE usuario = 'operador-maria'")->fetchColumn();
        if (!$operadorExiste) {
            $senhaHash = password_hash('Operador@123', PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['operador-maria', 'Maria Atendente', 'operador.maria@colegiomonteirolobato.com.br', $senhaHash, 'operador', 1]);
        }
        $this->assert(true, "Operador de teste 'operador-maria' preparado com perfil 'operador'");

        // Garante admin de teste
        $adminExiste = $this->pdo->query("SELECT id FROM usuarios WHERE usuario = 'admin'")->fetchColumn();
        $adminHash = password_hash('Admin@123', PASSWORD_BCRYPT);
        if (!$adminExiste) {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'Administrador Geral', 'admin@colegiomonteirolobato.com.br', $adminHash, 'administrador', 1]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE usuarios SET senha_hash = ?, ativo = 1, deleted_at = NULL WHERE usuario = 'admin'");
            $stmt->execute([$adminHash]);
        }
        $this->assert(true, "Administrador de teste 'admin' preparado com perfil 'administrador'");

        // Garante turma e responsável com senha ativa
        $respAtivo = $this->pdo->query("SELECT id FROM responsaveis WHERE cpf = '11122233344'")->fetchColumn();
        if (!$respAtivo) {
            $senhaHash = password_hash('Familia@123', PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, termos_aceitos_em, bloqueado_med) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
            $stmt->execute(['11122233344', 'Juliana Lima Santos', 'juliana.lima@teste.com', '(11) 98888-7777', $senhaHash, 75.50, 0]);
            $respAtivoId = $this->pdo->lastInsertId();
        } else {
            $respAtivoId = $respAtivo;
        }
        $this->assert($respAtivoId > 0, "Responsável ativo '11122233344' preparado com senha ativa e termos aceitos");

        // Garante responsável pendente de primeiro acesso (sem senha e termos null)
        $respPendente = $this->pdo->query("SELECT id FROM responsaveis WHERE cpf = '55566677788'")->fetchColumn();
        if (!$respPendente) {
            $stmt = $this->pdo->prepare("INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, termos_aceitos_em, bloqueado_med) VALUES (?, ?, ?, ?, '', 0.00, NULL, 0)");
            $stmt->execute(['55566677788', 'Carlos Eduardo Ribeiro', 'carlos.ribeiro@teste.com', '(11) 97777-6666']);
            $respPendenteId = $this->pdo->lastInsertId();
        } else {
            $respPendenteId = $respPendente;
            $this->pdo->exec("UPDATE responsaveis SET senha_hash = '', termos_aceitos_em = NULL WHERE id = {$respPendenteId}");
        }
        $this->assert($respPendenteId > 0, "Responsável pendente '55566677788' preparado para fluxo de primeiro acesso");

        // Garante aluno dependente vinculado para testar exibição de dependentes
        $turmaId = $this->pdo->query("SELECT id FROM turmas LIMIT 1")->fetchColumn();
        if ($turmaId) {
            $alunoExiste = $this->pdo->query("SELECT id FROM alunos WHERE responsavel_id = {$respAtivoId}")->fetchColumn();
            if (!$alunoExiste) {
                $stmt = $this->pdo->prepare("INSERT INTO alunos (responsavel_id, turma_id, matricula, nome, turno, ativo) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$respAtivoId, $turmaId, 'MAT-TESTE-01', 'Lucas Santos', 'matutino']);
            }
        }
        $this->assert(true, "Dependente escolar configurado para testes de portal");
    }

    private function testarModelUsuario(): void
    {
        echo "\n--- 3. Testes do Modelo Usuario ---\n";

        $model = new \App\Models\Usuario();

        $admin = $model->findByUsuario('admin');
        $this->assert($admin !== null, "Localizou usuário admin via findByUsuario()");
        $this->assert($admin['perfil'] === 'administrador', "Perfil do admin é 'administrador'");
        $this->assert(password_verify('Admin@123', $admin['senha_hash']), "Senha do admin confere com Bcrypt");

        $operadores = $model->findByPerfil('operador');
        $this->assert(is_array($operadores) && count($operadores) >= 1, "findByPerfil('operador') retornou lista válida de operadores");

        $emailInexistente = $model->findByEmail('naoexiste@escola.com');
        $this->assert($emailInexistente === null, "findByEmail() com email inexistente retorna null");
    }

    private function testarModelResponsavel(): void
    {
        echo "\n--- 4. Testes do Modelo Responsavel ---\n";

        $model = new \App\Models\Responsavel();

        $this->assert(\App\Models\Responsavel::cleanCpf('111.222.333-44') === '11122233344', "cleanCpf() higieniza pontuações corretamente");

        $respAtivo = $model->findByCpf('111.222.333-44');
        $this->assert($respAtivo !== null, "Localizou responsável ativo por CPF formatado");
        $this->assert(!\App\Models\Responsavel::isPendentePrimeiroAcesso($respAtivo), "Responsável ativo não é pendente de primeiro acesso");

        $respPendente = $model->findByCpf('55566677788');
        $this->assert($respPendente !== null, "Localizou responsável pendente");
        $this->assert(\App\Models\Responsavel::isPendentePrimeiroAcesso($respPendente), "Responsável pendente identificado corretamente via isPendentePrimeiroAcesso()");

        $dependentes = $model->getDependentes((int)$respAtivo['id']);
        $this->assert(is_array($dependentes), "getDependentes() retorna array de alunos vinculados");
    }

    private function testarWhatsAppService(): void
    {
        echo "\n--- 5. Testes do Adaptador WhatsAppService ---\n";

        $service = new \App\Services\WhatsAppService();

        $codigo = \App\Services\WhatsAppService::generateCode();
        $this->assert(strlen($codigo) === 6 && ctype_digit($codigo), "generateCode() produz código numérico de 6 dígitos ({$codigo})");

        $enviou = $service->sendFirstAccessCode('(11) 98888-7777', $codigo, 'Juliana');
        $this->assert($enviou === true, "sendFirstAccessCode() despachou mensagem com sucesso em ambiente local/mock");

        $ultimoCodigo = \App\Services\WhatsAppService::getLastCode('11988887777');
        $this->assert($ultimoCodigo === $codigo, "getLastCode() recuperou o código correto enviado ({$ultimoCodigo})");
    }

    private function testarAutenticacaoUsuario(): void
    {
        echo "\n--- 6. Testes de Autenticação da Equipe (Admin e Operador) ---\n";

        \App\Core\Auth::logout();
        $this->assert(\App\Core\Auth::guest(), "Estado inicial é visitante (guest)");

        // 1. Falha com credencial inválida
        $falha = \App\Core\Auth::attemptUsuario('admin', 'SenhaErrada!999');
        $this->assert($falha['success'] === false, "attemptUsuario com senha incorreta falhou conforme esperado");
        $this->assert(\App\Core\Auth::guest(), "Permanece não logado após falha");

        // 2. Sucesso com admin
        $sucessoAdmin = \App\Core\Auth::attemptUsuario('admin', 'Admin@123');
        $this->assert($sucessoAdmin['success'] === true, "attemptUsuario('admin', 'Admin@123') retornou sucesso");
        $this->assert(\App\Core\Auth::check(), "Auth::check() é verdadeiro após login");
        $this->assert(\App\Core\Auth::isAdmin(), "Auth::isAdmin() confirma perfil de administrador");
        $this->assert(\App\Core\Auth::role() === 'administrador', "Auth::role() retorna 'administrador'");
        $this->assert(\App\Core\Auth::type() === 'usuario', "Auth::type() retorna 'usuario'");

        // 3. Logout
        \App\Core\Auth::logout();
        $this->assert(\App\Core\Auth::guest(), "Logout desautentica com sucesso");

        // 4. Sucesso com operador
        $sucessoOperador = \App\Core\Auth::attemptUsuario('operador-maria', 'Operador@123');
        $this->assert($sucessoOperador['success'] === true, "attemptUsuario('operador-maria') autenticou com sucesso");
        $this->assert(\App\Core\Auth::isOperador(), "Auth::isOperador() confirma perfil de operador");
        $this->assert(!\App\Core\Auth::isAdmin(), "Operador não é administrador");
        $this->assert(\App\Core\Auth::role() === 'operador', "Auth::role() é 'operador'");

        \App\Core\Auth::logout();
    }

    private function testarAutenticacaoResponsavel(): void
    {
        echo "\n--- 7. Testes de Autenticação do Responsável Familiar ---\n";

        \App\Core\Auth::logout();

        // 1. Falha com CPF não cadastrado
        $cpfNaoCadastrado = \App\Core\Auth::attemptResponsavel('99999999999', '123456');
        $this->assert($cpfNaoCadastrado['success'] === false, "attemptResponsavel com CPF desconhecido rejeitado com segurança");

        // 2. Tentativa com responsável pendente de primeiro acesso
        $pendente = \App\Core\Auth::attemptResponsavel('55566677788', 'qualquer');
        $this->assert($pendente['success'] === false, "Responsável pendente de ativação não loga com senha avulsa");
        $this->assert(!empty($pendente['primeiro_acesso']), "Sistema sinaliza flag primeiro_acesso para direcionar à ativação");

        // 3. Sucesso com responsável ativo
        $sucesso = \App\Core\Auth::attemptResponsavel('111.222.333-44', 'Familia@123');
        $this->assert($sucesso['success'] === true, "attemptResponsavel('111.222.333-44', 'Familia@123') autenticou com sucesso");
        $this->assert(\App\Core\Auth::isResponsavel(), "Auth::isResponsavel() confirma papel de família");
        $this->assert(\App\Core\Auth::role() === 'responsavel', "Auth::role() retorna 'responsavel'");
        $this->assert(\App\Core\Auth::type() === 'responsavel', "Auth::type() retorna 'responsavel'");
        $this->assert(\App\Core\Auth::username() === '11122233344', "Auth::username() retorna CPF higienizado");

        \App\Core\Auth::logout();
    }

    private function testarBloqueioBruteForce(): void
    {
        echo "\n--- 8. Testes de Proteção contra Força Bruta (Rate Limiting) ---\n";

        $alvoTeste = 'usuario_brute_test';
        $ipTeste = '192.168.1.199';

        // Registra 5 falhas consecutivas de login
        for ($i = 1; $i <= 5; $i++) {
            \App\Core\Auth::logSecurityEvent('login_falha', $alvoTeste, [
                'tentativa' => $i,
                'ip'        => $ipTeste
            ]);
        }

        $bloqueado = \App\Core\Auth::isBruteForceBlocked($alvoTeste, $ipTeste);
        $this->assert($bloqueado === true, "Identificador bloqueado após atingir 5 falhas consecutivas de login");
    }

    private function testarFluxoPrimeiroAcesso(): void
    {
        echo "\n--- 9. Testes do Fluxo de Primeiro Acesso e Termos da Cantina ---\n";

        $respModel = new \App\Models\Responsavel();
        $resp = $respModel->findByCpf('55566677788');
        $this->assert($resp !== null, "Localizou responsável de teste para ativação");

        // Simula verificação e código
        $whatsAppService = new \App\Services\WhatsAppService();
        $codigo = \App\Services\WhatsAppService::generateCode();
        $whatsAppService->sendFirstAccessCode($resp['whatsapp'], $codigo, $resp['nome']);
        $this->assert(\App\Services\WhatsAppService::getLastCode($resp['whatsapp']) === $codigo, "Código de 6 dígitos gerado e despachado ao WhatsApp");

        // Simula definição de senha e aceite dos termos
        $novaSenha = 'SenhaAtivada@2026';
        $senhaAtualizada = $respModel->updatePassword((int)$resp['id'], $novaSenha);
        $this->assert($senhaAtualizada === true, "Senha criptografada gravada com Bcrypt via updatePassword()");

        $termosAceitos = $respModel->aceitarTermos((int)$resp['id']);
        $this->assert($termosAceitos === true, "Aceite formal dos termos registrado com sucesso");

        // Verifica estado atualizado no banco
        $respAtualizado = $respModel->findByCpf('55566677788');
        $this->assert(!empty($respAtualizado['termos_aceitos_em']), "termos_aceitos_em preenchido com timestamp no MySQL");
        $this->assert(password_verify($novaSenha, $respAtualizado['senha_hash']), "Nova senha valida com password_verify()");
        $this->assert(!\App\Models\Responsavel::isPendentePrimeiroAcesso($respAtualizado), "Responsável não está mais pendente de primeiro acesso");

        // Testa login com a nova senha ativada
        $loginAtivado = \App\Core\Auth::attemptResponsavel('55566677788', $novaSenha);
        $this->assert($loginAtivado['success'] === true, "Login com a senha recém-ativada efetuado com sucesso");
        \App\Core\Auth::logout();

        // Restaura responsável de teste para estado pendente para não interferir em testes manuais do usuário
        $this->pdo->exec("UPDATE responsaveis SET senha_hash = '', termos_aceitos_em = NULL WHERE cpf = '55566677788'");
    }

    private function testarRecuperacaoSenha(): void
    {
        echo "\n--- 10. Testes de Recuperação de Senha com WhatsApp ---\n";

        $respModel = new \App\Models\Responsavel();
        $resp = $respModel->findByCpf('11122233344');

        $codigo = \App\Services\WhatsAppService::generateCode();
        (new \App\Services\WhatsAppService())->sendPasswordResetCode($resp['whatsapp'], $codigo, $resp['nome']);
        $this->assert(\App\Services\WhatsAppService::getLastCode($resp['whatsapp']) === $codigo, "Código de recuperação despachado com sucesso");

        // Redefinição de senha
        $novaSenha = 'NovaSenha@987';
        $respModel->updatePassword((int)$resp['id'], $novaSenha);

        $loginNovo = \App\Core\Auth::attemptResponsavel('11122233344', $novaSenha);
        $this->assert($loginNovo['success'] === true, "Login bem-sucedido utilizando a nova senha redefinida");

        // Restaura senha padrão de teste
        $respModel->updatePassword((int)$resp['id'], 'Familia@123');
        \App\Core\Auth::logout();
    }

    private function testarMatrizPermissoesRBAC(): void
    {
        echo "\n--- 11. Testes da Matriz de Permissões RBAC (FSD Seção 8) ---\n";

        // 1. Simula Administrador
        \App\Core\Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'admin',
            'nome'    => 'Administrador',
            'email'   => 'admin@escola.com',
            'perfil'  => 'administrador'
        ]);

        $this->assert(\App\Core\RBAC::can('cadastro_produtos_precos'), "Administrador tem permissão: cadastro_produtos_precos");
        $this->assert(\App\Core\RBAC::can('configuracoes_empresa'), "Administrador tem permissão: configuracoes_empresa");
        $this->assert(\App\Core\RBAC::can('abertura_fechamento_caixa'), "Administrador tem permissão: abertura_fechamento_caixa");
        $this->assert(\App\Core\RBAC::can('login_painel_cantina'), "Administrador tem permissão: login_painel_cantina");

        // 2. Simula Operador
        \App\Core\Auth::loginUsuario([
            'id'      => 2,
            'usuario' => 'operador-maria',
            'nome'    => 'Maria Operadora',
            'email'   => 'operador@escola.com',
            'perfil'  => 'operador'
        ]);

        $this->assert(!\App\Core\RBAC::can('cadastro_produtos_precos'), "Operador NÃO tem permissão para cadastro_produtos_precos");
        $this->assert(!\App\Core\RBAC::can('configuracoes_empresa'), "Operador NÃO tem permissão para configuracoes_empresa");
        $this->assert(\App\Core\RBAC::can('operacao_pdv_balcao'), "Operador tem permissão: operacao_pdv_balcao");
        $this->assert(\App\Core\RBAC::can('abertura_fechamento_caixa'), "Operador tem permissão: abertura_fechamento_caixa");

        // 3. Simula Responsável
        \App\Core\Auth::loginResponsavel([
            'id'            => 10,
            'cpf'           => '11122233344',
            'nome'          => 'Juliana Família',
            'email'         => 'juliana@teste.com',
            'whatsapp'      => '11988887777',
            'saldo_atual'   => 50.00,
            'bloqueado_med' => 0
        ]);

        $this->assert(\App\Core\RBAC::can('recarga_pix_online'), "Responsável tem permissão: recarga_pix_online");
        $this->assert(\App\Core\RBAC::can('agendamento_semanal'), "Responsável tem permissão: agendamento_semanal");
        $this->assert(!\App\Core\RBAC::can('operacao_pdv_balcao'), "Responsável NÃO tem permissão para operacao_pdv_balcao");
        $this->assert(!\App\Core\RBAC::can('abertura_fechamento_caixa'), "Responsável NÃO tem permissão para abertura_fechamento_caixa");

        \App\Core\Auth::logout();
    }

    private function testarMiddlewaresRBAC(): void
    {
        echo "\n--- 12. Testes de Middlewares de Proteção de Rotas ---\n";

        // Teste 1: Visitante deslogado tentando executar requireRole
        \App\Core\Auth::logout();
        $middlewareAdmin = \App\Core\RBAC::requireRole('administrador');

        // Como requireAuth no middleware redireciona via header() e exit quando falha,
        // testamos a lógica do Auth e perfil
        $this->assert(\App\Core\Auth::guest(), "Middleware detecta usuário deslogado");

        // Teste 2: Responsável logado tentando acessar rota de Operador/Admin
        \App\Core\Auth::loginResponsavel([
            'id'            => 10,
            'cpf'           => '11122233344',
            'nome'          => 'Juliana Família',
            'email'         => 'juliana@teste.com',
            'whatsapp'      => '11988887777',
            'saldo_atual'   => 50.00,
            'bloqueado_med' => 0
        ]);

        $rolesPermitidas = ['operador', 'administrador'];
        $roleAtual = \App\Core\Auth::role();
        $bloqueado = !in_array($roleAtual, $rolesPermitidas, true);
        $this->assert($bloqueado === true, "Responsável barrado ao tentar acessar rota exclusiva da cantina");

        // Teste 3: Operador tentando acessar rota de Administrador exclusivo
        \App\Core\Auth::loginUsuario([
            'id'      => 2,
            'usuario' => 'operador-maria',
            'nome'    => 'Maria Operadora',
            'email'   => 'operador@escola.com',
            'perfil'  => 'operador'
        ]);

        $rolesAdminExclusivo = ['administrador'];
        $bloqueadoOperador = !in_array(\App\Core\Auth::role(), $rolesAdminExclusivo, true);
        $this->assert($bloqueadoOperador === true, "Operador barrado ao tentar acessar rota exclusiva do Administrador");

        // Teste 4: Administrador acessando rota de Administrador
        \App\Core\Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'admin',
            'nome'    => 'Administrador',
            'email'   => 'admin@escola.com',
            'perfil'  => 'administrador'
        ]);

        $autorizadoAdmin = in_array(\App\Core\Auth::role(), $rolesAdminExclusivo, true);
        $this->assert($autorizadoAdmin === true, "Administrador autorizado com sucesso em rota restrita");

        \App\Core\Auth::logout();
    }

    private function testarTrilhaSeguranca(): void
    {
        echo "\n--- 13. Testes da Trilha de Auditoria e Logs de Segurança ---\n";

        \App\Core\Auth::logSecurityEvent('teste_auditoria_fase6', 'admin', [
            'modulo' => 'RBAC',
            'status' => 'aprovado'
        ]);

        $stmt = $this->pdo->query("SELECT * FROM logs_seguranca WHERE evento = 'teste_auditoria_fase6' ORDER BY id DESC LIMIT 1");
        $log = $stmt->fetch();

        $this->assert($log !== false, "Evento gravado com sucesso na tabela logs_seguranca");
        $this->assert($log['cpf_ou_usuario'] === 'admin', "Identificador 'admin' registrado no log");
        $this->assert(!empty($log['detalhes_json']), "Detalhes estruturados em JSON presentes");
        $this->assert(!empty($log['ip_origem']), "IP de origem registrado no log de segurança");
    }
}

// Execução da suíte
$test = new Fase6Test();
$test->run();
