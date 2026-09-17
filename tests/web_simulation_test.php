<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Simulação de Requisições Web no Front Controller (index.php)
 */

define('CANTINA_TEST_RUN', true);

function testUrl(string $method, string $uri, array $postData = [], array $headers = []): array {
    $script = __DIR__ . '/../index.php';
    
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_POST = $postData;
    $_GET = [];
    
    foreach ($headers as $k => $v) {
        $_SERVER[$k] = $v;
    }
    
    // Captura saída
    ob_start();
    try {
        require $script;
    } catch (\Throwable $e) {
        // Exceções capturadas pelo ErrorHandler encerram com saída renderizada
    }
    $output = ob_get_clean();
    
    return [
        'status' => http_response_code(),
        'output' => $output
    ];
}

echo "=== TESTE DE REQUISIÇÕES VIA INDEX.PHP ===" . PHP_EOL;

// 1. Teste da Raiz /
$_SERVER = [];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
ob_start();
require __DIR__ . '/../index.php';
$html = ob_get_clean();

$passouHome = str_contains($html, 'Colégio Monteiro Lobato') && str_contains($html, 'Fase 5 Concluída');
echo "1. Rota GET / -> " . ($passouHome ? "SUCESSO (HTML Renderizado)" : "FALHA") . PHP_EOL;

// 2. Teste da API /api/status via script separado
$codeApi = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/api/status";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
$_SERVER["HTTP_ACCEPT"] = "application/json";
require "index.php";
';
$outputApi = shell_exec("php -r " . escapeshellarg($codeApi));
$jsonApi = json_decode($outputApi, true);
$passouApi = isset($jsonApi['status']) && $jsonApi['status'] === 'online' && isset($jsonApi['mvc_core']);
echo "2. Rota GET /api/status -> " . ($passouApi ? "SUCESSO (JSON Válido retornado)" : "FALHA") . PHP_EOL;

// 3. Teste de Rota Dinâmica /teste/555
$codeDinamica = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/teste/555";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$outputDinamica = shell_exec("php -r " . escapeshellarg($codeDinamica));
$jsonDinamica = json_decode($outputDinamica, true);
$passouDinamica = isset($jsonDinamica['parametro']) && $jsonDinamica['parametro'] === '555';
echo "3. Rota GET /teste/555 -> " . ($passouDinamica ? "SUCESSO (Parâmetro 555 recebido)" : "FALHA") . PHP_EOL;

// 4. Teste de Rota 404 /pagina-inexistente
$code404 = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/pagina-inexistente";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$output404 = shell_exec("php -r " . escapeshellarg($code404));
$passou404 = str_contains($output404, '404 — Página Não Encontrada');
echo "4. Rota Inexistente /pagina-inexistente -> " . ($passou404 ? "SUCESSO (Tela 404 apresentada)" : "FALHA") . PHP_EOL;

// 5. Teste de Simulação de Erro /teste-erro
$codeErro = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/teste-erro";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$outputErro = shell_exec("php -r " . escapeshellarg($codeErro));
$passouErro = str_contains($outputErro, 'Ocorreu uma falha no processamento') && str_contains($outputErro, 'Identificador do Incidente:');
echo "5. Rota GET /teste-erro -> " . ($passouErro ? "SUCESSO (Tela segura 500 apresentada com código UUID)" : "FALHA") . PHP_EOL;

if ($passouHome && $passouApi && $passouDinamica && $passou404 && $passouErro) {
    echo PHP_EOL . "STATUS: TODAS AS 5 SIMULAÇÕES WEB PASSARAM COM SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "STATUS: FALHA NAS SIMULAÇÕES WEB!" . PHP_EOL;
    exit(1);
}
