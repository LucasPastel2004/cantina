<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Utilitário Oficial de Reparo e Normalização de Encoding UTF-8
 * 
 * Finalidade:
 * Permite corrigir instantaneamente qualquer corrupção de caracteres (mojibake CP850/Latin1)
 * nas tabelas do MySQL sem depender de reimportação de dumps.
 * 
 * Modos de Execução:
 * 1. CLI:
 *    php database/corrigir_encoding.php --confirmar
 * 
 * 2. HTTP:
 *    https://seudominio.com.br/database/corrigir_encoding.php?token=TOKEN&confirmar=sim
 */

if (!defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

// 1. Carrega configuração oficial
$configFile = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die("Erro fatal: Arquivo de configuração [config/config.php] não encontrado.\n");
}
$config = require $configFile;

$isCli = (php_sapi_name() === 'cli');

// 2. Validação de token de segurança se invocado via Web
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    $token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
    $expectedToken = (string)($config['security']['migration_token'] ?? '');

    if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        die("<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>403 Acesso Negado</title></head><body style='font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;'><h1>403 Acesso Negado</h1><p>Token de segurança inválido ou ausente.</p></body></html>");
    }
}

// 3. Conexão com o banco
try {
    /** @var \PDO $pdo */
    $pdo = require dirname(__DIR__) . '/config/database.php';
} catch (\Throwable $e) {
    http_response_code(500);
    die("Erro de conexão com o banco de dados: " . $e->getMessage() . "\n");
}

$confirmado = false;
if ($isCli) {
    global $argv;
    $confirmado = in_array('--confirmar', $argv ?? [], true);
} else {
    $confirmado = (($_POST['confirmar'] ?? $_GET['confirmar'] ?? '') === 'sim');
}

if (!$confirmado) {
    if ($isCli) {
        echo "Para executar a correção de encoding, use: php database/corrigir_encoding.php --confirmar\n";
    } else {
        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Reparar Encoding UTF-8</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;line-height:1.5;} .btn{padding:10px 18px;background:#1d4ed8;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;text-decoration:none;} .card{background:#fff;border:1px solid #e2e8f0;padding:24px;border-radius:8px;max-width:600px;}</style></head><body><div class='card'><h2>Reparo de Encoding UTF-8 (Cantina Web)</h2><p>Este utilitário fará a varredura e correção de todos os caracteres corrompidos (mojibake CP850/Latin1) no MySQL de produção.</p><form method='POST'><input type='hidden' name='token' value='" . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . "'><input type='hidden' name='confirmar' value='sim'><button type='submit' class='btn'>Executar Correção de Encoding Agora</button></form></div></body></html>";
    }
    exit;
}

// Execução da correção
$relatorio = [];
$totalAfetados = 0;

// 1. Correções Canônicas Essenciais
$stmt1 = $pdo->prepare("UPDATE `restricoes_alimentares` SET `nome` = 'Glúten', `descricao` = 'Presente em derivados de trigo, centeio, cevada e aveia contaminada.' WHERE `id` = 1 OR `nome` LIKE '%ten%' OR `nome` LIKE '%Gl%'");
$stmt1->execute();
$afetados1 = $stmt1->rowCount();
$relatorio[] = "restricoes_alimentares: {$afetados1} linhas corrigidas (Glúten)";
$totalAfetados += $afetados1;

$stmt2 = $pdo->prepare("UPDATE `categorias` SET `nome` = 'Lanches & Sanduíches' WHERE `id` = 3 OR `nome` LIKE '%Sandu%' OR `nome` LIKE '%Lanches%'");
$stmt2->execute();
$afetados2 = $stmt2->rowCount();
$relatorio[] = "categorias: {$afetados2} linhas corrigidas (Lanches & Sanduíches)";
$totalAfetados += $afetados2;

// 2. Mapeamento Geral de Mojibake
$substituicoes = [
    '├║' => 'ú', '├¡' => 'í', '├ú' => 'ã', '├®' => 'é', '├ó' => 'â',
    '├¬' => 'ê', '├º' => 'ç', '├│' => 'ó', '├┤' => 'ô', '├á' => 'à',
    '├í' => 'á', '┬║' => 'º', '┬¬' => 'ª', '├ü' => 'Á', '├Ç' => 'À',
    '├â' => 'Ã', '├é' => 'Â', '├ë' => 'É', '├è' => 'Ê', '├ì' => 'Í',
    '├ô' => 'Ó', '├ö' => 'Ô', '├ò' => 'Õ', '├Ü' => 'Ú', '├ç' => 'Ç',
    '├Á' => 'õ',
    'Ãº' => 'ú', 'Ã­' => 'í', 'Ã£' => 'ã', 'Ã©' => 'é', 'Ã¢' => 'â',
    'Ãª' => 'ê', 'Ã§' => 'ç', 'Ã³' => 'ó', 'Ã´' => 'ô', 'Ã ' => 'à',
    'Ã¡' => 'á', 'Âº' => 'º', 'Âª' => 'ª', 'Ã ' => 'Á', 'Ã€' => 'À',
    'Ãƒ' => 'Ã', 'Ã‚' => 'Â', 'Ã‰' => 'É', 'ÃŠ' => 'Ê', 'Ã ' => 'Í',
    'Ã“' => 'Ó', 'Ã”' => 'Ô', 'Ã•' => 'Õ', 'Ãš' => 'Ú', 'Ã‡' => 'Ç',
    'Ãµ' => 'õ'
];

$alvos = [
    'restricoes_alimentares' => ['nome', 'descricao'],
    'categorias'             => ['nome'],
    'produtos'               => ['descricao'],
    'turmas'                 => ['serie', 'nome_turma'],
    'alunos'                 => ['nome'],
    'responsaveis'           => ['nome'],
    'colaboradores'          => ['nome', 'cargo'],
    'tickets'                => ['tag_alergenos', 'motivo_estorno'],
    'configuracoes_globais'  => ['razao_social', 'nome_fantasia']
];

foreach ($alvos as $tabela => $colunas) {
    $existe = $pdo->query("SHOW TABLES LIKE '{$tabela}'")->fetchColumn();
    if (!$existe) {
        continue;
    }

    $tabelaAfetados = 0;
    foreach ($colunas as $col) {
        foreach ($substituicoes as $de => $para) {
            $stmt = $pdo->prepare("UPDATE `{$tabela}` SET `{$col}` = REPLACE(`{$col}`, ?, ?) WHERE `{$col}` LIKE ?");
            $stmt->execute([$de, $para, "%{$de}%"]);
            $tabelaAfetados += $stmt->rowCount();
        }
    }
    if ($tabelaAfetados > 0) {
        $relatorio[] = "{$tabela}: {$tabelaAfetados} campos normalizados";
        $totalAfetados += $tabelaAfetados;
    }
}

// Saída do resultado
if ($isCli) {
    echo "======================================================\n";
    echo "  Cantina Web — Resultado da Correção de Encoding     \n";
    echo "======================================================\n";
    foreach ($relatorio as $linha) {
        echo " * {$linha}\n";
    }
    echo "------------------------------------------------------\n";
    echo "Sucesso: Total de {$totalAfetados} atualizações aplicadas.\n";
} else {
    echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Correção Concluída</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;} .card{background:#fff;border:1px solid #e2e8f0;padding:24px;border-radius:8px;max-width:600px;} .success{color:#15803d;font-weight:700;} ul{line-height:1.8;}</style></head><body><div class='card'><h2 class='success'>✓ Correção de Encoding UTF-8 Concluída!</h2><p>As atualizações foram aplicadas com sucesso no banco de dados:</p><ul>";
    foreach ($relatorio as $linha) {
        echo "<li>" . htmlspecialchars($linha, ENT_QUOTES, 'UTF-8') . "</li>";
    }
    echo "</ul><p><strong>Total de operações:</strong> {$totalAfetados}</p><p><a href='/operacional/pdv' style='color:#1d4ed8;font-weight:600;'>← Voltar para o PDV</a></p></div></body></html>";
}
