<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Bloqueio de Recuperação de Senha para Contas Pendentes de Primeiro Acesso
 * 
 * Execução via Docker Compose:
 * docker compose exec -T app php tests/recuperar_senha_bloqueio_pendente_test.php
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

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

require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/config.php';
\App\Core\Session::start($config);

use App\Core\Session;
use App\Core\Auth;
use App\Models\Responsavel;
use App\Controllers\AuthController;

echo "======================================================================\n";
echo "  TESTE: BLOQUEIO DE RECUPERAÇÃO DE SENHA PARA CONTAS NÃO VERIFICADAS\n";
echo "======================================================================\n\n";

$totalAssercoes = 0;
$sucessos = 0;
$falhas = 0;

function assertTest(bool $condicao, string $descricao, &$totalAssercoes, &$sucessos, &$falhas): void {
    $totalAssercoes++;
    if ($condicao) {
        $sucessos++;
        echo "  [PASS] {$descricao}\n";
    } else {
        $falhas++;
        echo "  [FAIL] {$descricao}\n";
    }
}

$pdo = \Database::getInstance();
$respModel = new Responsavel();

// 1. Prepara dados de teste:
// - Responsável pendente de Primeiro Acesso (CPF 457.366.341-04 - do caso relatado pelo usuário)
$genesio = $respModel->findByCpf('45736634104');
if ($genesio) {
    // Garante que está 100% pendente (sem senha e sem termos)
    $pdo->exec("UPDATE responsaveis SET senha_hash = '', termos_aceitos_em = NULL WHERE cpf = '45736634104'");
    $genesio = $respModel->findByCpf('45736634104');
}
assertTest($genesio !== null, "Localizou cadastro de teste do responsável 45736634104", $totalAssercoes, $sucessos, $falhas);
assertTest(Responsavel::isPendentePrimeiroAcesso($genesio), "Responsável 45736634104 está pendente de primeiro acesso (sem senha e sem termos)", $totalAssercoes, $sucessos, $falhas);

// - Responsável ativo de teste (CPF 111.222.333-44)
$ativo = $respModel->findByCpf('11122233344');
if (!$ativo) {
    $senhaHash = password_hash('Familia@123', PASSWORD_BCRYPT);
    $pdo->exec("INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash, saldo_atual, termos_aceitos_em, bloqueado_med) VALUES ('11122233344', 'Juliana Lima', 'juliana@teste.com', '(11) 98888-7777', '{$senhaHash}', 50.00, NOW(), 0)");
    $ativo = $respModel->findByCpf('11122233344');
}
assertTest($ativo !== null, "Localizou responsável ativo 11122233344", $totalAssercoes, $sucessos, $falhas);
assertTest(!Responsavel::isPendentePrimeiroAcesso($ativo), "Responsável 11122233344 possui conta ativa com senha e termos aceitos", $totalAssercoes, $sucessos, $falhas);

// 2. Teste: Solicitação em /recuperar-senha/solicitar para CPF PENDENTE (457.366.341-04)
echo "\n--- Teste 1: Solicitação de recuperação para conta pendente de primeiro acesso ---\n";
$csrfToken = Session::generateCsrfToken();
$_POST = [
    'csrf_token' => $csrfToken,
    'cpf'        => '457.366.341-04'
];

// Limpa sessão de recuperação prévia
Session::remove('_rec_responsavel_id');
Session::remove('_rec_cpf');
Session::remove('_rec_codigo');
Session::remove('_rec_ultimo_envio');

$authController = new AuthController();
ob_start();
$authController->recuperarSenhaSolicitar();
ob_end_clean();

// Deve bloquear: NÃO deve setar _rec_codigo nem _rec_responsavel_id
assertTest(!Session::has('_rec_codigo'), "Código de recuperação NÃO foi gerado para conta pendente", $totalAssercoes, $sucessos, $falhas);
assertTest(!Session::has('_rec_responsavel_id'), "Sessão de recuperação NÃO foi criada para conta pendente", $totalAssercoes, $sucessos, $falhas);

// Deve emitir aviso direcionando para o Primeiro Acesso
$flashes = Session::getFlash('warning');
$flashMsg = is_array($flashes) ? implode(' ', $flashes) : (string)$flashes;
assertTest(str_contains($flashMsg, 'primeiro acesso') && str_contains($flashMsg, 'senha cadastrada'), "Mensagem flash alerta que conta não possui senha e direciona ao Primeiro Acesso: '{$flashMsg}'", $totalAssercoes, $sucessos, $falhas);

// 3. Teste: Defesa em profundidade na tela /recuperar-senha/codigo
echo "\n--- Teste 2: Defesa em profundidade caso chegue a /recuperar-senha/codigo com conta pendente ---\n";
Session::set('_rec_responsavel_id', (int)$genesio['id']);
Session::set('_rec_cpf', '45736634104');
Session::set('_rec_codigo', '999999');

ob_start();
$authController->recuperarSenhaCodigo();
ob_end_clean();

assertTest(!Session::has('_rec_responsavel_id'), "Sessão de recuperação foi limpa imediatamente ao verificar que a conta é pendente", $totalAssercoes, $sucessos, $falhas);
assertTest(!Session::has('_rec_codigo'), "Código em sessão foi revogado", $totalAssercoes, $sucessos, $falhas);

// 4. Teste: Defesa em profundidade no POST /recuperar-senha/redefinir para conta pendente
echo "\n--- Teste 3: Tentativa direta de submissão de nova senha em /recuperar-senha/redefinir ---\n";
Session::set('_rec_responsavel_id', (int)$genesio['id']);
Session::set('_rec_cpf', '45736634104');
Session::set('_rec_codigo', '123456');
Session::set('_rec_expira_em', time() + 900);

$_POST = [
    'csrf_token'        => $csrfToken,
    'codigo'            => '123456',
    'senha'             => 'NovaSenha@123',
    'senha_confirmacao' => 'NovaSenha@123'
];

ob_start();
$authController->recuperarSenhaRedefinir();
ob_end_clean();

// A senha do Genesio NÃO deve ter sido gravada!
$genesioCheck = $respModel->findByCpf('45736634104');
assertTest(empty($genesioCheck['senha_hash']), "Senha do responsável pendente NÃO foi alterada por recuperação", $totalAssercoes, $sucessos, $falhas);
assertTest(Responsavel::isPendentePrimeiroAcesso($genesioCheck), "Responsável permanece protegido e pendente de Primeiro Acesso", $totalAssercoes, $sucessos, $falhas);

// 5. Teste: Solicitação com CPF inválido (< 11 dígitos)
echo "\n--- Teste 4: Validação de CPF com formato inválido ---\n";
$_POST = [
    'csrf_token' => $csrfToken,
    'cpf'        => '123.456'
];
ob_start();
$authController->recuperarSenhaSolicitar();
ob_end_clean();
$flashesInvalido = Session::getFlash('warning');
$msgInvalido = is_array($flashesInvalido) ? implode(' ', $flashesInvalido) : (string)$flashesInvalido;
assertTest(str_contains($msgInvalido, '11 dígitos'), "CPF inválido (< 11 dígitos) rejeitado com aviso: '{$msgInvalido}'", $totalAssercoes, $sucessos, $falhas);

// 6. Teste: Conta ATIVA (111.222.333-44) continua funcionando normalmente
echo "\n--- Teste 5: Conta ativa autorizada a recuperar senha com WhatsApp ---\n";
Session::remove('_rec_ultimo_envio');
$_POST = [
    'csrf_token' => $csrfToken,
    'cpf'        => '111.222.333-44'
];
ob_start();
$authController->recuperarSenhaSolicitar();
ob_end_clean();

assertTest(Session::has('_rec_codigo'), "Código gerado com sucesso para responsável com conta ativa", $totalAssercoes, $sucessos, $falhas);
assertTest(Session::get('_rec_responsavel_id') === (int)$ativo['id'], "Sessão vinculada ao responsável ativo", $totalAssercoes, $sucessos, $falhas);

// Redefinição com sucesso para conta ativa
$codigoGerado = Session::get('_rec_codigo');
$_POST = [
    'csrf_token'        => $csrfToken,
    'codigo'            => $codigoGerado,
    'senha'             => 'NovaSenhaValida@2026',
    'senha_confirmacao' => 'NovaSenhaValida@2026'
];
ob_start();
$authController->recuperarSenhaRedefinir();
ob_end_clean();

$ativoAtualizado = $respModel->findByCpf('11122233344');
assertTest(password_verify('NovaSenhaValida@2026', $ativoAtualizado['senha_hash']), "Nova senha gravada com sucesso para conta ativa", $totalAssercoes, $sucessos, $falhas);

// Restaura senha padrão da conta ativa
$respModel->updatePassword((int)$ativo['id'], 'Familia@123');

// 7. Teste de log de segurança
echo "\n--- Teste 6: Auditoria em logs_seguranca ---\n";
$stmtLog = $pdo->prepare("SELECT evento, cpf_ou_usuario, detalhes_json FROM logs_seguranca WHERE evento = 'recuperar_senha_conta_pendente' ORDER BY id DESC LIMIT 1");
$stmtLog->execute();
$logSeguranca = $stmtLog->fetch(PDO::FETCH_ASSOC);

assertTest($logSeguranca !== false, "Evento 'recuperar_senha_conta_pendente' registrado na tabela logs_seguranca", $totalAssercoes, $sucessos, $falhas);
if ($logSeguranca) {
    assertTest($logSeguranca['cpf_ou_usuario'] === '45736634104', "Identificador (cpf_ou_usuario) registrado no log é o CPF correto", $totalAssercoes, $sucessos, $falhas);
}

echo "\n======================================================================\n";
echo "  RESUMO DOS TESTES — BLOQUEIO DE RECUPERAÇÃO PARA CONTAS PENDENTES\n";
echo "======================================================================\n";
echo "  Total de asserções executadas: {$totalAssercoes}\n";
echo "  Sucessos: {$sucessos}\n";
echo "  Falhas: {$falhas}\n";

if ($falhas > 0) {
    echo "\n  RESULTADO: FALHA EM {$falhas} ASSERÇÃO(ÕES)!\n";
    exit(1);
} else {
    echo "\n  RESULTADO: TODAS AS {$totalAssercoes} ASSERÇÕES FORAM APROVADAS COM 100% DE SUCESSO!\n";
    exit(0);
}
