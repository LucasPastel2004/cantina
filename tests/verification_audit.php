<?php
/**
 * Script de Auditoria e Verificação Abrangente (ECC Verification Loop & Production Audit)
 */

define('CANTINA_SISTEMA', true);

$start = microtime(true);
echo "=======================================================\n";
echo "   INICIANDO AUDITORIA GLOBAL DA BASE DE CÓDIGO\n";
echo "=======================================================\n\n";

$baseDir = dirname(__DIR__);
$phpFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($it as $file) {
    if (!$file->isFile()) continue;
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getPathname();
    $rel = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path);
    $rel = str_replace('\\', '/', $rel);
    if (str_starts_with($rel, '.git') || str_starts_with($rel, '.agents') || str_starts_with($rel, 'vendor')) {
        continue;
    }
    $phpFiles[] = $rel;
}

echo "1. Verificando Sintaxe PHP (php -l) em " . count($phpFiles) . " arquivos...\n";
$syntaxErrors = 0;
foreach ($phpFiles as $rel) {
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        echo "  [ERRO DE SINTAXE] $rel\n";
        echo "    " . implode("\n    ", $output) . "\n";
        $syntaxErrors++;
    }
    $output = [];
}
if ($syntaxErrors === 0) {
    echo "  [OK] Todos os " . count($phpFiles) . " arquivos PHP possuem sintaxe 100% válida.\n";
} else {
    echo "  [FALHA] Encontrados $syntaxErrors arquivos com erro de sintaxe.\n";
}

echo "\n2. Verificando Salvaguarda de Acesso Direto (CANTINA_SISTEMA)...\n";
$missingGuard = [];
foreach ($phpFiles as $rel) {
    // Arquivos públicos ou runners podem não ter ou ter de forma diferente
    if ($rel === 'index.php' || str_starts_with($rel, 'tests/') || $rel === 'database/runner.php' || str_starts_with($rel, 'config/')) {
        continue;
    }
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $content = file_get_contents($fullPath);
    if (!str_contains($content, 'CANTINA_SISTEMA')) {
        $missingGuard[] = $rel;
    }
}
if (empty($missingGuard)) {
    echo "  [OK] Todas as classes internas e views possuem proteção CANTINA_SISTEMA.\n";
} else {
    echo "  [ALERTA] Arquivos internos sem CANTINA_SISTEMA (" . count($missingGuard) . "):\n";
    foreach ($missingGuard as $f) {
        echo "    - $f\n";
    }
}

echo "\n3. Verificando Rotas Registradas vs Métodos de Controller...\n";
$indexPath = $baseDir . '/index.php';
$indexContent = file_get_contents($indexPath);

// Registrar autoloader para testar classes
spl_autoload_register(function (string $class) use ($baseDir): void {
    $prefix = 'App\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Expressão para pegar $router->get/post/put/delete/any
preg_match_all("/\\\$router->(get|post|put|delete|any)\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*(\[[^\]]+\]|'[^']+'|\"[^\"]+\")/i", $indexContent, $matches, PREG_SET_ORDER);

$routeIssues = [];
$totalRoutes = count($matches);
foreach ($matches as $m) {
    $verb = strtoupper($m[1]);
    $pattern = $m[2];
    $target = $m[3];

    // Se for array [$controller, 'method']
    if (preg_match('/\[\s*([a-zA-Z0-9_\\\\]+)::class\s*,\s*[\'"]([a-zA-Z0-9_]+)[\'"]\s*\]/', $target, $targetMatch)) {
        $controllerClass = $targetMatch[1];
        $method = $targetMatch[2];

        if (!class_exists($controllerClass)) {
            $routeIssues[] = "Classe $controllerClass não encontrada para rota $verb $pattern";
        } elseif (!method_exists($controllerClass, $method)) {
            $routeIssues[] = "Método $controllerClass::$method não encontrado para rota $verb $pattern";
        }
    }
}

if (empty($routeIssues)) {
    echo "  [OK] Todas as $totalRoutes rotas registradas apontam para Controllers e Métodos existentes.\n";
} else {
    echo "  [FALHA] Problemas encontrados em rotas (" . count($routeIssues) . "):\n";
    foreach ($routeIssues as $issue) {
        echo "    - $issue\n";
    }
}

echo "\n4. Verificando Injeção SQL Potencial e Queries sem Prepared Statements...\n";
$sqlIssues = [];
foreach ($phpFiles as $rel) {
    if (str_starts_with($rel, 'tests/')) continue;
    $fullPath = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $content = file_get_contents($fullPath);
    $lines = explode("\n", $content);
    foreach ($lines as $lineNum => $line) {
        // Procurar query( ou exec( com concatenação de variável
        if (preg_match('/\b(query|exec)\s*\(\s*["\'].*\$[a-zA-Z0-9_]+.*["\']\s*\)/', $line) ||
            preg_match('/\$pdo->(query|exec)\s*\(\s*["\'][^"\']*\$[^"\']*["\']\s*\)/', $line)) {
            $sqlIssues[] = "$rel:" . ($lineNum + 1) . " Concatenação direta em query/exec: " . trim($line);
        }
        // Procurar prepare com concatenação de variável de usuário
        if (preg_match('/->prepare\s*\(\s*["\'].*\$_[GET|POST|REQUEST].*["\']\s*\)/i', $line)) {
            $sqlIssues[] = "$rel:" . ($lineNum + 1) . " Concatenação de superglobal em prepare: " . trim($line);
        }
    }
}
if (empty($sqlIssues)) {
    echo "  [OK] Nenhuma concatenação vulnerável detectada em consultas SQL (Prepared Statements estritos).\n";
} else {
    echo "  [ALERTA] Possíveis concatenações SQL suspeitas (" . count($sqlIssues) . "):\n";
    foreach ($sqlIssues as $issue) {
        echo "    - $issue\n";
    }
}

echo "\n5. Executando Todas as Suítes de Testes Automatizados (tests/*_test.php)...\n";
$testFiles = glob($baseDir . '/tests/*_test.php');
sort($testFiles);
$totalSuites = count($testFiles);
$passedSuites = 0;
$failedSuites = 0;
$failedList = [];

foreach ($testFiles as $tf) {
    $tName = basename($tf);
    if ($tName === 'verification_audit.php') continue;
    $tOut = [];
    $tRet = 0;
    exec("php " . escapeshellarg($tf) . " 2>&1", $tOut, $tRet);
    $outStr = implode("\n", $tOut);
    // Verificar se houve falha ou retorno != 0
    if ($tRet !== 0 || str_contains($outStr, '[FAIL]') || str_contains($outStr, 'Falhas Encontradas   : [1-9]') || str_contains($outStr, 'Fatal error') || str_contains($outStr, 'Parse error')) {
        echo "  [FALHA] $tName (ret: $tRet)\n";
        // Mostrar últimas 5 linhas do output
        $lastLines = array_slice($tOut, -6);
        echo "    " . implode("\n    ", $lastLines) . "\n";
        $failedSuites++;
        $failedList[] = $tName;
    } else {
        echo "  [OK] $tName\n";
        $passedSuites++;
    }
}

echo "\nResumo dos Testes: $passedSuites/$totalSuites suítes aprovadas com 100% de sucesso.\n";
if (!empty($failedList)) {
    echo "Suítes com falha (" . count($failedList) . "):\n";
    foreach ($failedList as $fl) {
        echo "  - $fl\n";
    }
}

echo "\n=======================================================\n";
echo "   AUDITORIA CONCLUÍDA EM " . round(microtime(true) - $start, 2) . "s\n";
echo "=======================================================\n";

