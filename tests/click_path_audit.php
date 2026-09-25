<?php
define('CANTINA_SISTEMA', true);

$baseDir = dirname(__DIR__);

// 1. Extrai todas as rotas registradas em index.php
$indexContent = file_get_contents($baseDir . '/index.php');
preg_match_all('/\$router->(get|post|put|delete|any)\s*\(\s*[\'"]([^\'"]+)[\'"]/i', $indexContent, $matches, PREG_SET_ORDER);

$registeredRoutes = [];
foreach ($matches as $m) {
    $verb = strtoupper($m[1]);
    $pattern = $m[2];
    $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '[^/]+', $pattern);
    $registeredRoutes[] = [
        'verb' => $verb,
        'pattern' => $pattern,
        'regex' => '#^' . $regex . '$#'
    ];
}

echo "Total de rotas registradas no Router: " . count($registeredRoutes) . "\n\n";

// 2. Escaneia todos os arquivos de view em app/views/
$viewsDir = $baseDir . '/app/views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
$brokenLinks = [];
$brokenForms = [];
$totalLinks = 0;
$totalForms = 0;

foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $content = file_get_contents($file->getPathname());
    $relView = str_replace($viewsDir . DIRECTORY_SEPARATOR, '', $file->getPathname());
    $relView = str_replace('\\', '/', $relView);

    $cleanContent = str_replace('<?php echo $baseUrl; ?>', '', $content);
    $cleanContent = str_replace('<?= $baseUrl ?>', '', $cleanContent);
    $cleanContent = str_replace('<?php echo $baseUrl ?>', '', $cleanContent);
    // Substitui tags PHP por identificador fictício '1' para rotas com {id}
    $cleanContent = preg_replace('/<\?php.*?\?>/s', '1', $cleanContent);
    $cleanContent = preg_replace('/<\?=.*?\?>/s', '1', $cleanContent);

    // Encontrar hrefs internos
    preg_match_all('/href=["\'](\/[^"\'?#\s]*)["\'?#]/i', $cleanContent, $hrefMatches);
    if (!empty($hrefMatches[1])) {
        foreach ($hrefMatches[1] as $href) {
            if (str_starts_with($href, '/assets') || str_starts_with($href, '/storage') || str_starts_with($href, '/favicon') || $href === '#') {
                continue;
            }
            $totalLinks++;

            $matched = false;
            foreach ($registeredRoutes as $r) {
                if (in_array($r['verb'], ['GET', 'ANY']) && preg_match($r['regex'], $href)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $brokenLinks[] = [
                    'view' => $relView,
                    'href' => $href
                ];
            }
        }
    }

    // Encontrar form actions
    preg_match_all('/<form[^>]+action=["\'](\/[^"\'?#\s]*)["\'?#][^>]*method=["\'](POST|GET)["\']/i', $cleanContent, $formMatches, PREG_SET_ORDER);
    if (!empty($formMatches)) {
        foreach ($formMatches as $fm) {
            $action = $fm[1];
            $method = strtoupper($fm[2]);
            if (str_starts_with($action, '/assets') || $action === '#') {
                continue;
            }
            $totalForms++;

            $matched = false;
            foreach ($registeredRoutes as $r) {
                if (($r['verb'] === $method || $r['verb'] === 'ANY') && preg_match($r['regex'], $action)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $brokenForms[] = [
                    'view' => $relView,
                    'action' => $action,
                    'method' => $method
                ];
            }
        }
    }
}

echo "=== RESULTADO DO CLICK-PATH AUDIT ===\n";
echo "Links inspecionados: $totalLinks\n";
echo "Formulários inspecionados: $totalForms\n\n";

if (!empty($brokenLinks)) {
    echo "[!] LINKS PARA ROTAS NÃO REGISTRADAS (" . count($brokenLinks) . "):\n";
    foreach ($brokenLinks as $bl) {
        echo "  - Na view [{$bl['view']}]: href=\"{$bl['href']}\"\n";
    }
} else {
    echo "[OK] Todos os links internos apontam para rotas GET válidas no Router!\n";
}

if (!empty($brokenForms)) {
    echo "\n[!] FORMULÁRIOS COM ACTION NÃO REGISTRADA (" . count($brokenForms) . "):\n";
    foreach ($brokenForms as $bf) {
        echo "  - Na view [{$bf['view']}]: method=\"{$bf['method']}\" action=\"{$bf['action']}\"\n";
    }
} else {
    echo "[OK] Todos os formulários possuem rotas POST/GET registradas e correspondentes!\n";
}
