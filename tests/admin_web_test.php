<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Simulação Web para Rotas Administrativas da Fase 7
 */

$baseUrl = 'http://localhost';

function makeRequest(string $url, string $method = 'GET', array $data = [], ?string $cookieFile = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);

    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code'    => $httpCode,
        'headers' => $headers,
        'body'    => $body
    ];
}

echo "=== TESTE DE ROTAS WEB DA FASE 7 ===\n";

$cookieAdmin = tempnam(sys_get_temp_dir(), 'admin_cookie_');
$cookieOperador = tempnam(sys_get_temp_dir(), 'op_cookie_');

// 1. Visitante deslogado tentando acessar /admin/configuracoes -> deve redirecionar para /login (302)
$res = makeRequest($baseUrl . '/admin/configuracoes');
$redirectsToLogin = ($res['code'] === 302 && str_contains($res['headers'], 'login'));
echo "1. Visitante em /admin/configuracoes (302 para login): " . ($redirectsToLogin ? "PASS" : "FAIL (code {$res['code']})") . "\n";

// 2. Login como Administrador (admin / Admin@123)
// Primeiro busca tela de login para pegar o CSRF token
$loginPage = makeRequest($baseUrl . '/login', 'GET', [], $cookieAdmin);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage['body'], $matches);
$csrfToken = $matches[1] ?? '';

$loginRes = makeRequest($baseUrl . '/login', 'POST', [
    'csrf_token'  => $csrfToken,
    'tipo_acesso' => 'equipe',
    'usuario'     => 'admin',
    'senha'       => 'Admin@123'
], $cookieAdmin);

$adminLogado = ($loginRes['code'] === 302 && str_contains($loginRes['headers'], 'admin'));
echo "2. Login do Administrador: " . ($adminLogado ? "PASS" : "FAIL (code {$loginRes['code']})") . "\n";

// 3. Administrador acessando /admin/configuracoes
$resConfig = makeRequest($baseUrl . '/admin/configuracoes', 'GET', [], $cookieAdmin);
$okConfig = ($resConfig['code'] === 200 && str_contains($resConfig['body'], 'Configurações Globais'));
echo "3. Admin acessa /admin/configuracoes (200 OK): " . ($okConfig ? "PASS" : "FAIL (code {$resConfig['code']})") . "\n";

// 4. Administrador acessando /admin/categorias
$resCat = makeRequest($baseUrl . '/admin/categorias', 'GET', [], $cookieAdmin);
$okCat = ($resCat['code'] === 200 && str_contains($resCat['body'], 'Categorias de Alimentos'));
echo "4. Admin acessa /admin/categorias (200 OK): " . ($okCat ? "PASS" : "FAIL (code {$resCat['code']})") . "\n";

// 5. Administrador acessando /admin/produtos
$resProd = makeRequest($baseUrl . '/admin/produtos', 'GET', [], $cookieAdmin);
$okProd = ($resProd['code'] === 200 && str_contains($resProd['body'], 'Cardápio'));
echo "5. Admin acessa /admin/produtos (200 OK): " . ($okProd ? "PASS" : "FAIL (code {$resProd['code']})") . "\n";

// 5.1 Administrador filtrando /admin/produtos?busca=teste
$resProdBusca = makeRequest($baseUrl . '/admin/produtos?busca=teste', 'GET', [], $cookieAdmin);
$okProdBusca = ($resProdBusca['code'] === 200 && !str_contains($resProdBusca['body'], 'SQLSTATE'));
echo "5.1 Admin busca /admin/produtos?busca=teste (200 OK sem SQLSTATE): " . ($okProdBusca ? "PASS" : "FAIL (code {$resProdBusca['code']})") . "\n";

// 6. Administrador acessando /admin/colaboradores
$resColab = makeRequest($baseUrl . '/admin/colaboradores', 'GET', [], $cookieAdmin);
$okColab = ($resColab['code'] === 200 && str_contains($resColab['body'], 'Colaboradores'));
echo "6. Admin acessa /admin/colaboradores (200 OK): " . ($okColab ? "PASS" : "FAIL (code {$resColab['code']})") . "\n";

// 7. Administrador acessando /admin/turmas
$resTurmas = makeRequest($baseUrl . '/admin/turmas', 'GET', [], $cookieAdmin);
$okTurmas = ($resTurmas['code'] === 200 && str_contains($resTurmas['body'], 'Turmas Escolares'));
echo "7. Admin acessa /admin/turmas (200 OK): " . ($okTurmas ? "PASS" : "FAIL (code {$resTurmas['code']})") . "\n";

// 8. Login como Operador (operador-maria / Operador@123)
$loginPageOp = makeRequest($baseUrl . '/login', 'GET', [], $cookieOperador);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPageOp['body'], $matchesOp);
$csrfTokenOp = $matchesOp[1] ?? '';

$loginResOp = makeRequest($baseUrl . '/login', 'POST', [
    'csrf_token'  => $csrfTokenOp,
    'tipo_acesso' => 'equipe',
    'usuario'     => 'operador-maria',
    'senha'       => 'Operador@123'
], $cookieOperador);

// 8. Operador tentando acessar /admin/produtos (RBAC bloqueia e redireciona 302 para /admin)
$resProdOp = makeRequest($baseUrl . '/admin/produtos', 'GET', [], $cookieOperador);
$okProdOp = ($resProdOp['code'] === 302 && str_contains($resProdOp['headers'], 'Location: /admin'));
echo "8. Operador bloqueado em /admin/produtos por RBAC (302 para /admin): " . ($okProdOp ? "PASS" : "FAIL (code {$resProdOp['code']})") . "\n";

// 9. Operador tentando acessar /admin/configuracoes (RBAC redireciona com flash para /admin)
$resConfigOp = makeRequest($baseUrl . '/admin/configuracoes', 'GET', [], $cookieOperador);
$okConfigOp = ($resConfigOp['code'] === 302 && str_contains($resConfigOp['headers'], 'Location: /admin'));
echo "9. Operador redirecionado de /admin/configuracoes por RBAC (302 para /admin): " . ($okConfigOp ? "PASS" : "FAIL (code {$resConfigOp['code']})") . "\n";

// 10. Operador acessando /estoque para pausa rápida e saldos (200 OK)
$resEstoqueOp = makeRequest($baseUrl . '/estoque', 'GET', [], $cookieOperador);
$okEstoqueOp = ($resEstoqueOp['code'] === 200 && str_contains($resEstoqueOp['body'], 'Controle de Estoque'));
echo "10. Operador acessa /estoque para saldos e pausa rápida (200 OK): " . ($okEstoqueOp ? "PASS" : "FAIL (code {$resEstoqueOp['code']})") . "\n";

// Limpeza de arquivos temporários de cookie
@unlink($cookieAdmin);
@unlink($cookieOperador);

echo "\nTODOS OS TESTES DE ROTAS WEB DA FASE 7 FORAM EXECUTADOS!\n";
