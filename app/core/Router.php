<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Mecanismo de Resolução e Despacho de Rotas Amigáveis
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class Router
{
    /**
     * Tabela de rotas registradas organizadas por método HTTP
     */
    private array $routes = [
        'GET'    => [],
        'POST'   => [],
        'PUT'    => [],
        'DELETE' => []
    ];

    /**
     * Caminho base relativo para execução em subpastas (ex: /cantina)
     */
    private string $baseUrl = '';

    /**
     * Construtor do Roteador
     *
     * @param string|null $baseUrl Caminho base opcional (detectado automaticamente se nulo)
     */
    public function __construct(?string $baseUrl = null)
    {
        if ($baseUrl !== null) {
            $this->baseUrl = rtrim($baseUrl, '/');
        } else {
            $this->detectBaseUrl();
        }
    }

    /**
     * Detecta o caminho base relativo a partir do script em execução
     */
    private function detectBaseUrl(): void
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_replace('\\', '/', $scriptDir);
        $this->baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');
    }

    /**
     * Obtém o caminho base da aplicação
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Registra uma rota GET
     */
    public function get(string $path, mixed $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota POST
     */
    public function post(string $path, mixed $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota PUT
     */
    public function put(string $path, mixed $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota DELETE
     */
    public function delete(string $path, mixed $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    /**
     * Registra uma rota para múltiplos métodos
     */
    public function match(array $methods, string $path, mixed $handler, array $middlewares = []): self
    {
        foreach ($methods as $method) {
            $this->addRoute(strtoupper($method), $path, $handler, $middlewares);
        }
        return $this;
    }

    /**
     * Adiciona internamente uma rota com conversão de padrões dinâmicos
     */
    private function addRoute(string $method, string $path, mixed $handler, array $middlewares = []): self
    {
        $normalizedPath = '/' . trim($path, '/');
        if ($normalizedPath !== '/') {
            $normalizedPath = rtrim($normalizedPath, '/');
        }

        // Converte rotas dinâmicas como /alunos/{id} ou /pedidos/{id:\d+} em regex nomeada
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^}]+))?\}/', function ($matches) {
            $name = $matches[1];
            $customRegex = isset($matches[2]) && $matches[2] !== '' ? $matches[2] : '[^/]+';
            return '(?P<' . $name . '>' . $customRegex . ')';
        }, $normalizedPath);

        $pattern = '#^' . $pattern . '$#u';

        $this->routes[$method][] = [
            'path'        => $normalizedPath,
            'pattern'     => $pattern,
            'handler'     => $handler,
            'middlewares' => $middlewares
        ];

        return $this;
    }

    /**
     * Processa a requisição atual e despacha para o controlador correspondente
     *
     * @param string|null $requestMethod Método HTTP da requisição (opcional)
     * @param string|null $requestUri URI da requisição (opcional)
     */
    public function dispatch(?string $requestMethod = null, ?string $requestUri = null): void
    {
        $method = strtoupper($requestMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        // Suporte a emulação de métodos PUT/DELETE via POST com campo _method
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = $requestUri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        // Remove o caminho base (subdiretório) do path analisado
        if ($this->baseUrl !== '' && str_starts_with($path, $this->baseUrl)) {
            $path = substr($path, strlen($this->baseUrl));
        }

        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $route) {
            if (preg_match($route['pattern'], $path, $matches)) {
                // Filtra apenas parâmetros nomeados
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Executa middlewares da rota
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareResult = $this->executeMiddleware($middleware, $params);
                    if ($middlewareResult === false) {
                        return; // Interrompido pelo middleware
                    }
                }

                // Executa o handler da rota
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // Rota não encontrada (404)
        $this->handleNotFound($path);
    }

    /**
     * Executa um middleware
     */
    private function executeMiddleware(mixed $middleware, array $params): bool
    {
        if (is_callable($middleware)) {
            return $middleware($params) !== false;
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                return $instance->handle($params) !== false;
            }
        }

        return true;
    }

    /**
     * Executa o handler registrado
     */
    private function executeHandler(mixed $handler, array $params): void
    {
        // 1. Função anônima / Closure
        if ($handler instanceof \Closure) {
            echo $handler($params);
            return;
        }

        // 2. Formato de array: [ControllerClass, 'method']
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $this->invokeControllerMethod($class, $method, $params);
            return;
        }

        // 3. Formato de string: 'ControllerClass@method'
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $this->invokeControllerMethod($class, $method, $params);
            return;
        }

        throw new \RuntimeException("Handler de rota inválido ou não executável.");
    }

    /**
     * Instancia o controller e executa a action
     */
    private function invokeControllerMethod(string $class, string $method, array $params): void
    {
        // Se a classe não for qualificada com namespace, tenta App\Controllers\
        if (!class_exists($class) && class_exists("App\\Controllers\\{$class}")) {
            $class = "App\\Controllers\\{$class}";
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Controlador [{$class}] não encontrado.");
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new \RuntimeException("Método [{$method}] não encontrado no controlador [{$class}].");
        }

        try {
            $refMethod = new \ReflectionMethod($controller, $method);
            $numParams = $refMethod->getNumberOfParameters();

            if ($numParams === 0) {
                $controller->$method();
                return;
            }

            $firstParam = $refMethod->getParameters()[0];
            $type = $firstParam->getType();

            // Se o primeiro parâmetro espera explicitamente array
            if ($type instanceof \ReflectionNamedType && $type->getName() === 'array') {
                $controller->$method($params);
                return;
            }

            // Se o primeiro parâmetro for o único e aceitar mixed ou não possuir tipo
            if ($numParams === 1 && (!$type || $type->getName() === 'mixed')) {
                $controller->$method($params);
                return;
            }

            // Mapeia parâmetros por nome dos argumentos da rota
            $args = [];
            foreach ($refMethod->getParameters() as $param) {
                $paramName = $param->getName();
                if (array_key_exists($paramName, $params)) {
                    $val = $params[$paramName];
                    $pType = $param->getType();
                    if ($pType instanceof \ReflectionNamedType) {
                        if ($pType->getName() === 'int') {
                            $val = (int)$val;
                        } elseif ($pType->getName() === 'float') {
                            $val = (float)$val;
                        } elseif ($pType->getName() === 'string') {
                            $val = (string)$val;
                        }
                    }
                    $args[] = $val;
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $args[] = null;
                }
            }

            $controller->$method(...$args);
        } catch (\ReflectionException $e) {
            $controller->$method($params);
        }
    }

    /**
     * Trata requisições para rotas não mapeadas (404)
     */
    private function handleNotFound(string $path): void
    {
        if (!headers_sent()) {
            http_response_code(404);
        }

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || str_starts_with($path, '/api/');

        if ($isJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'error'   => true,
                'message' => 'Recurso não encontrado.',
                'path'    => $path
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            return;
        }

        $viewFile = __DIR__ . '/../views/errors/404.php';
        if (file_exists($viewFile)) {
            $baseUrl = $this->baseUrl;
            require $viewFile;
        } else {
            $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
            ?>
            <!DOCTYPE html>
            <html lang="pt-BR">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Página Não Encontrada — Cantina Web</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; color: #0f172a; margin: 0; padding: 40px 20px; display: flex; justify-content: center; align-items: center; min-height: 80vh; }
                    .card { background: #ffffff; border: 1px solid #cbd5e1; border-top: 4px solid #2563eb; border-radius: 6px; padding: 24px; max-width: 500px; width: 100%; }
                    h1 { font-size: 20px; margin: 0 0 10px 0; color: #2563eb; }
                    p { font-size: 13px; line-height: 1.5; color: #475569; margin: 0 0 16px 0; }
                    .path { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; font-family: monospace; font-size: 12px; border-radius: 4px; margin-bottom: 16px; }
                    .btn { display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h1>404 — Página Não Encontrada</h1>
                    <p>O endereço solicitado não foi localizado em nosso servidor.</p>
                    <div class="path"><?php echo $safePath; ?></div>
                    <a href="<?php echo htmlspecialchars($this->baseUrl, ENT_QUOTES, 'UTF-8'); ?>/" class="btn">Retornar ao Início</a>
                </div>
            </body>
            </html>
            <?php
        }
        exit;
    }
}
