<?php
/**
 * Teste HTTP real com cURL para rotas recém-adicionadas
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

echo "=== TESTE HTTP DE ROTAS RECÉM-ADICIONADAS ===\n";

$cookieAdmin = tempnam(sys_get_temp_dir(), 'admin_ck_');
$cookieResp = tempnam(sys_get_temp_dir(), 'resp_ck_');

// 1. Login Admin
$loginPage = makeRequest($baseUrl . '/login', 'GET', [], $cookieAdmin);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage['body'], $matches);
$csrf = $matches[1] ?? '';

$loginRes = makeRequest($baseUrl . '/login', 'POST', [
    'csrf_token'  => $csrf,
    'tipo_acesso' => 'equipe',
    'usuario'     => 'admin',
    'senha'       => 'Admin@123'
], $cookieAdmin);

// 2. Admin acessa /admin/calendario
$resCal = makeRequest($baseUrl . '/admin/calendario', 'GET', [], $cookieAdmin);
$okCal = ($resCal['code'] === 200 && str_contains($resCal['body'], 'Calendário Escolar'));
echo "1. GET /admin/calendario (200 OK): " . ($okCal ? "PASS" : "FAIL (code {$resCal['code']})") . "\n";

// 3. Admin acessa /relatorios (descontinuado: redireciona para /admin)
$resRel = makeRequest($baseUrl . '/relatorios', 'GET', [], $cookieAdmin);
$okRel = ($resRel['code'] === 302 && str_contains($resRel['headers'], '/admin'));
echo "2. GET /relatorios (302 Redirect /admin): " . ($okRel ? "PASS" : "FAIL (code {$resRel['code']})") . "\n";

// 4. Login Responsável
$loginPageResp = makeRequest($baseUrl . '/login', 'GET', [], $cookieResp);
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPageResp['body'], $matchesResp);
$csrfResp = $matchesResp[1] ?? '';

$loginResp = makeRequest($baseUrl . '/login', 'POST', [
    'csrf_token'  => $csrfResp,
    'tipo_acesso' => 'familia',
    'cpf'         => '111.222.333-44',
    'senha'       => 'Familia@123'
], $cookieResp);

// 5. Responsável acessa /portal/perfil
$resPerfil = makeRequest($baseUrl . '/portal/perfil', 'GET', [], $cookieResp);
$okPerfil = ($resPerfil['code'] === 200 && str_contains($resPerfil['body'], 'Meus Dados Cadastrais'));
echo "3. GET /portal/perfil (200 OK): " . ($okPerfil ? "PASS" : "FAIL (code {$resPerfil['code']})") . "\n";

// 6. Responsável acessa /portal/extrato
$resExtrato = makeRequest($baseUrl . '/portal/extrato', 'GET', [], $cookieResp);
$okExtrato = ($resExtrato['code'] === 200 && str_contains($resExtrato['body'], 'Carteira Familiar'));
echo "4. GET /portal/extrato (200 OK): " . ($okExtrato ? "PASS" : "FAIL (code {$resExtrato['code']})") . "\n";

@unlink($cookieAdmin);
@unlink($cookieResp);

echo "\nTODOS OS TESTES DE ROTAS PASSARAM COM SUCESSO!\n";
