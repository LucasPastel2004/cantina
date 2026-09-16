<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Integração da Fase 2 (Núcleo MVC e Logs)
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';

// Registra o autoloader
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

\App\Core\Session::start();

$testesPassados = 0;
$testesFalhados = 0;

function assertTest(string $nome, bool $condicao, string $detalhe = ''): void {
    global $testesPassados, $testesFalhados;
    if ($condicao) {
        echo "  [PASS] {$nome}" . PHP_EOL;
        $testesPassados++;
    } else {
        echo "  [FAIL] {$nome}" . ($detalhe ? " - {$detalhe}" : '') . PHP_EOL;
        $testesFalhados++;
    }
}

echo "=== INICIANDO BATERIA DE TESTES DA FASE 2 ===" . PHP_EOL . PHP_EOL;

// 1. Teste de Carregamento de Classes do Core
echo "1. Teste de Autoloading das Classes Core:" . PHP_EOL;
assertTest("App\Core\Session existe", class_exists('App\Core\Session'));
assertTest("App\Core\ErrorHandler existe", class_exists('App\Core\ErrorHandler'));
assertTest("App\Core\Router existe", class_exists('App\Core\Router'));
assertTest("App\Core\Controller existe", class_exists('App\Core\Controller'));
assertTest("App\Core\Model existe", class_exists('App\Core\Model'));
assertTest("App\Controllers\HomeController existe", class_exists('App\Controllers\HomeController'));

// 2. Teste de Sessão e CSRF
echo PHP_EOL . "2. Teste de Segurança da Session e Token CSRF:" . PHP_EOL;
\App\Core\Session::start();
assertTest("Sessão ativa", session_status() === PHP_SESSION_ACTIVE);

$token1 = \App\Core\Session::getCsrfToken();
assertTest("Token CSRF gerado com sucesso (64 chars hex)", strlen($token1) === 64);
assertTest("Validação de token correto retorna true", \App\Core\Session::validateCsrfToken($token1));
assertTest("Validação de token incorreto retorna false", !\App\Core\Session::validateCsrfToken('token_falso_invalido'));
assertTest("Validação de token nulo/vazio retorna false", !\App\Core\Session::validateCsrfToken(null));

// Teste de Flash Messages
\App\Core\Session::setFlash('sucesso', 'Mensagem de teste de sucesso');
assertTest("hasFlash retorna true para mensagem gravada", \App\Core\Session::hasFlash('sucesso'));
$flashes = \App\Core\Session::getFlash('sucesso');
assertTest("getFlash recupera mensagem correta", count($flashes) === 1 && $flashes[0] === 'Mensagem de teste de sucesso');
assertTest("getFlash limpa a mensagem após leitura", !\App\Core\Session::hasFlash('sucesso'));

// 3. Teste do Router
echo PHP_EOL . "3. Teste do Roteador (App\Core\Router):" . PHP_EOL;
$router = new \App\Core\Router('');

$rotaExecutada = false;
$parametroRecebido = null;

$router->get('/teste-simples', function() use (&$rotaExecutada) {
    $rotaExecutada = true;
    return 'ok';
});

$router->get('/aluno/{id}/detalhes', function($params) use (&$parametroRecebido) {
    $parametroRecebido = $params['id'] ?? null;
    return 'aluno_ok';
});

// Despacha rota estática
ob_start();
$router->dispatch('GET', '/teste-simples');
$saida = ob_get_clean();
assertTest("Despacho de rota estática executou handler", $rotaExecutada && $saida === 'ok');

// Despacha rota dinâmica
ob_start();
$router->dispatch('GET', '/aluno/1542/detalhes');
$saidaDinamica = ob_get_clean();
assertTest("Despacho de rota dinâmica capturou parâmetro", $parametroRecebido === '1542' && $saidaDinamica === 'aluno_ok');

// Teste de rota 404 via JSON
$_SERVER['HTTP_ACCEPT'] = 'application/json';
ob_start();
try {
    $router->dispatch('GET', '/rota-inexistente-total');
} catch (\Throwable $e) {
    // Caso ocorra saída antecipada
}
$saida404 = ob_get_clean();
$json404 = json_decode($saida404, true);
assertTest("Rota inexistente com Accept json retorna erro 404 estruturado", isset($json404['error']) && $json404['error'] === true);
unset($_SERVER['HTTP_ACCEPT']);

// 4. Teste de Contingência do ErrorHandler
echo PHP_EOL . "4. Teste de Contingência do ErrorHandler e Log em Arquivo:" . PHP_EOL;
$logPath = __DIR__ . '/../storage/logs/error.log';
$tamanhoAntes = file_exists($logPath) ? filesize($logPath) : 0;

$mensagemErroSimulado = "Simulação de teste automatizado de contingência " . uniqid();
$excecaoSimulada = new \RuntimeException($mensagemErroSimulado);

// Simula chamada de log do ErrorHandler
$method = new ReflectionMethod('App\Core\ErrorHandler', 'logToFile');
$method->setAccessible(true);
$method->invoke(null, 'ERROR', $mensagemErroSimulado, __FILE__, __LINE__, $excecaoSimulada);

$tamanhoDepois = file_exists($logPath) ? filesize($logPath) : 0;
assertTest("Arquivo storage/logs/error.log foi modificado", $tamanhoDepois > $tamanhoAntes);

$conteudoLog = file_get_contents($logPath);
assertTest("Log contém a mensagem de erro simulada", str_contains($conteudoLog, $mensagemErroSimulado));
assertTest("Log possui o formato exigido pelo FSD: [YYYY-MM-DD HH:MM:SS] [NIVEL] ...", (bool)preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \[ERROR\]/', $conteudoLog));

// 5. Teste de Validação do Controller
echo PHP_EOL . "5. Teste do Controller Base e Validações:" . PHP_EOL;
$homeController = new \App\Controllers\HomeController();
$reflectionValidate = new ReflectionMethod('App\Core\Controller', 'validate');
$reflectionValidate->setAccessible(true);

$dadosValidos = [
    'csrf_token' => $token1,
    'nome'       => 'Maria Silva',
    'email'      => 'maria@colegio.com.br',
    'idade'      => 35
];

$regras = [
    'csrf_token' => 'required|csrf',
    'nome'       => 'required|min:3|max:100',
    'email'      => 'required|email',
    'idade'      => 'required|integer'
];

$passouValido = $reflectionValidate->invoke($homeController, $dadosValidos, $regras);
assertTest("Validação de dados corretos retorna true", $passouValido);

$dadosInvalidos = [
    'csrf_token' => 'invalido',
    'nome'       => 'A',
    'email'      => 'email-invalido',
    'idade'      => 'trinta'
];
$passouInvalido = $reflectionValidate->invoke($homeController, $dadosInvalidos, $regras);
assertTest("Validação de dados incorretos retorna false", !$passouInvalido);
$erros = $homeController->getValidationErrors();
assertTest("Erros de validação detalhados por campo", isset($erros['csrf_token'], $erros['nome'], $erros['email'], $erros['idade']));

echo PHP_EOL . "=== RESULTADO DOS TESTES DA FASE 2 ===" . PHP_EOL;
echo "Testes com sucesso: {$testesPassados}" . PHP_EOL;
echo "Testes falhados:    {$testesFalhados}" . PHP_EOL;

if ($testesFalhados === 0) {
    echo "STATUS: TODOS OS TESTES PASSARAM COM SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo "STATUS: HOUVE FALHAS NOS TESTES!" . PHP_EOL;
    exit(1);
}
