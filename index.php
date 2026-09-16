<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Ponto Único de Entrada (Front Controller)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

// Define a constante de segurança do sistema
define('CANTINA_SISTEMA', true);

// Registra horário de início para benchmark leve se necessário
define('CANTINA_START', microtime(true));

// Carrega configurações da aplicação
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    die('Arquivo de configuração [config/config.php] não encontrado. Copie config/config.example.php.');
}
$config = require $configFile;

// Configuração de fuso horário
date_default_timezone_set($config['app']['timezone'] ?? 'America/Sao_Paulo');

// Tratamento de erros de acordo com o ambiente
if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Determina o caminho base relativo para suporte a subdiretórios (ex: /cantina/ ou /)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim(str_replace('\\', '/', $scriptDir), '/');
if ($baseUrl === '') {
    $baseUrl = '';
}

// Roteamento preliminar para Fase 1
// Em fases posteriores, isso acionará o app/core/Router.php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = substr($uri, strlen($baseUrl));
$path = '/' . trim($path, '/');

// Se for rota de API / status
if ($path === '/api/status' || (isset($_GET['format']) && $_GET['format'] === 'json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'      => 'online',
        'app'         => $config['app']['name'],
        'environment' => $config['app']['env'],
        'php_version' => PHP_VERSION,
        'fase_atual'  => 'Fase 1 — Infraestrutura e Base do Projeto',
        'timestamp'   => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Renderização da tela inicial de status da Fase 1
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['app']['name']); ?></title>
    <!-- Bootstrap Local (sem dependência de CDN externa) -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/bootstrap.min.css">
    <!-- Design System Institucional - docs/DESIGN.md -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/custom.css">
</head>
<body>
    <header class="app-header">
        <img src="<?php echo $baseUrl; ?>/assets/img/logo-colegio.jpg" alt="Logo Colégio Monteiro Lobato" class="header-logo">
        <div class="d-flex flex-column">
            <span class="fw-bold" style="font-size: 14px; color: var(--primary-blue);">Colégio Monteiro Lobato</span>
            <span class="text-microcopy">Cantina Web — Gestão Operacional e Nutricional</span>
        </div>
    </header>

    <main class="container my-4" style="max-width: 900px;">
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-status badge-success">✓ Operacional</span>
                    <span>Infraestrutura e Base Técnica Inicial</span>
                </div>
                <span class="text-microcopy">Fase 1 Concluída</span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label">Ambiente do Sistema</label>
                        <div class="fw-semibold text-capitalize"><?php echo htmlspecialchars($config['app']['env']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded border">
                        <label class="form-label">Versão do Interpretador PHP</label>
                        <div class="fw-semibold font-tabular"><?php echo PHP_VERSION; ?></div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table-data-grid">
                    <thead>
                        <tr>
                            <th class="cell-left">Componente Estrutural</th>
                            <th class="cell-center">Status</th>
                            <th class="cell-left">Caminho do Diretório / Arquivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Front Controller</strong></td>
                            <td class="cell-center"><span class="badge-status badge-success">OK</span></td>
                            <td><code>index.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>Configurações Globais</strong></td>
                            <td class="cell-center"><span class="badge-status badge-success">OK</span></td>
                            <td><code>config/config.php</code></td>
                        </tr>
                        <tr>
                            <td><strong>Proteções de Diretórios</strong></td>
                            <td class="cell-center"><span class="badge-status badge-success">OK</span></td>
                            <td><code>config/, app/, database/, storage/ (.htaccess)</code></td>
                        </tr>
                        <tr>
                            <td><strong>Assets Locais (Bootstrap + Design System)</strong></td>
                            <td class="cell-center"><span class="badge-status badge-success">OK</span></td>
                            <td><code>assets/css/, assets/js/, assets/img/</code></td>
                        </tr>
                        <tr>
                            <td><strong>Logotipo Institucional</strong></td>
                            <td class="cell-center"><span class="badge-status badge-success">OK</span></td>
                            <td><code>assets/img/logo-colegio.jpg</code></td>
                        </tr>
                        <tr>
                            <td><strong>Logs e Uploads Segregados</strong></td>
                            <td class="cell-center"><span class="badge-status badge-success">OK</span></td>
                            <td><code>storage/logs/, storage/uploads/private/</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-light rounded border">
                <p class="mb-1 text-secondary-label"><strong>Próximo Passo Recomendado:</strong></p>
                <p class="mb-0">Avançar para a <strong>Fase 2 — Núcleo Arquitetural MVC e Mecanismo de Banco de Dados</strong> conforme o plano de implementação em <code>docs/PLANO.md</code>.</p>
            </div>
        </div>
    </main>

    <!-- Bootstrap Bundle Local (Vanilla JS) -->
    <script src="<?php echo $baseUrl; ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/assets/js/app.js"></script>
</body>
</html>
