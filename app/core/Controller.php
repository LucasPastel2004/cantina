<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador Base da Aplicação MVC
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

abstract class Controller
{
    /**
     * Erros acumulados de validação
     */
    protected array $validationErrors = [];

    /**
     * Renderiza um template de visualização (View) com dados encapsulados
     *
     * @param string $view Nome do arquivo relativo a app/views/ (ex: 'home/index')
     * @param array $data Variáveis disponibilizadas para a View
     * @param string|null $layout Nome do layout mestre em app/views/layouts/ (opcional)
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        $viewPath = __DIR__ . '/../views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View [{$view}] não encontrada em [{$viewPath}].");
        }

        // Determina o baseUrl para links da View
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_replace('\\', '/', $scriptDir);
        $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');

        // Helpers e variáveis automáticas para todas as views
        $data['baseUrl'] = $baseUrl;
        $data['csrf_token'] = Session::getCsrfToken();
        $data['csrf_field'] = Session::csrfField();
        $data['assetVersion'] = '5.6.0';
        if (!isset($data['user']) && Auth::check()) {
            $data['user'] = Auth::user();
        }

        // Extrai variáveis com escopo isolado
        extract($data, EXTR_SKIP);

        // Se houver um layout definido e o arquivo existir, renderiza a view dentro do layout
        if ($layout !== null) {
            // Remove prefixo redundante 'layouts/' se fornecido
            $layoutName = preg_replace('#^layouts[\\/]#', '', $layout);
            $layoutPath = __DIR__ . '/../views/layouts/' . $layoutName . '.php';

            if (!file_exists($layoutPath)) {
                // Fallback caso o desenvolvedor aponte para outro caminho relativo em app/views/
                $altPath = __DIR__ . '/../views/' . $layout . '.php';
                if (file_exists($altPath)) {
                    $layoutPath = $altPath;
                }
            }

            if (file_exists($layoutPath)) {
                // Isola a renderização da view em uma closure para evitar vazamento de variáveis internas para o layout
                $renderViewIsolated = function(string $__viewPath, array $__viewData): string {
                    extract($__viewData, EXTR_SKIP);
                    ob_start();
                    require $__viewPath;
                    return ob_get_clean();
                };

                $content = $renderViewIsolated($viewPath, $data);

                // No escopo do layout, extrai estritamente os dados originais e o conteúdo compilado
                extract($data, EXTR_SKIP);
                require $layoutPath;
                return;
            }
        }

        // Renderização direta da view
        require $viewPath;
    }

    /**
     * Retorna uma resposta estruturada em formato JSON
     *
     * @param mixed $data Conteúdo a ser serializado
     * @param int $statusCode Código de status HTTP
     */
    protected function json(mixed $data, int $statusCode = 200): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!defined('CANTINA_TEST_MODE') || !CANTINA_TEST_MODE) {
            exit;
        }
    }

    /**
     * Redireciona a requisição para outra rota respeitando subdiretórios
     *
     * @param string $path Caminho de destino (ex: '/login' ou 'login')
     * @param int $statusCode Código HTTP de redirecionamento (302 padrão)
     */
    protected function redirect(string $path, int $statusCode = 302): void
    {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_replace('\\', '/', $scriptDir);
        $baseUrl = ($base === '/' || $base === '.') ? '' : rtrim($base, '/');

        $target = $baseUrl . '/' . ltrim($path, '/');

        if (!headers_sent()) {
            header("Location: {$target}", true, $statusCode);
        } else {
            echo "<script>window.location.href=" . json_encode($target) . ";</script>";
        }
        if (!defined('CANTINA_TEST_MODE') || !CANTINA_TEST_MODE) {
            exit;
        }
    }

    /**
     * Valida o token de proteção contra CSRF.
     * Caso o token seja inválido ou ausente, encerra a requisição retornando JSON 403 (se Ajax)
     * ou redireciona com mensagem flash de segurança (se requisição web regular).
     *
     * @param string|null $token Token a validar (se null, busca em $_POST['csrf_token'] ou header X-CSRF-TOKEN)
     * @param string|null $redirectPath Rota alternativa para redirecionamento em falha
     * @return bool Retorna true se o token for válido
     */
    protected function validateCsrf(?string $token = null, ?string $redirectPath = null): bool
    {
        $token = $token ?? (string)($this->input('csrf_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

        if (!Session::validateCsrfToken($token)) {
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                      (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            if ($isAjax) {
                $this->json([
                    'sucesso'  => false,
                    'mensagem' => 'Sessão expirada ou token de segurança inválido. Recarregue a página.'
                ], 403);
                if (!defined('CANTINA_TEST_MODE') || !CANTINA_TEST_MODE) {
                    exit;
                }
                return false;
            }

            Session::setFlash('danger', 'Sessão expirada ou token de segurança CSRF inválido. Por favor, tente novamente.');

            if ($redirectPath !== null) {
                $this->redirect($redirectPath);
            } else {
                $referer = $_SERVER['HTTP_REFERER'] ?? '';
                if (!empty($referer)) {
                    if (!headers_sent()) {
                        header('Location: ' . $referer);
                    }
                    if (!defined('CANTINA_TEST_MODE') || !CANTINA_TEST_MODE) {
                        exit;
                    }
                    return false;
                }
                $this->redirect('/');
            }
            if (!defined('CANTINA_TEST_MODE') || !CANTINA_TEST_MODE) {
                exit;
            }
            return false;
        }

        return true;
    }

    /**
     * Obtém um parâmetro de entrada (POST ou GET) de forma segura
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? ($_GET[$key] ?? $default);
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    /**
     * Retorna todos os dados recebidos na requisição mesclando GET e POST
     */
    protected function all(): array
    {
        $data = array_merge($_GET, $_POST);
        array_walk_recursive($data, function (&$item) {
            if (is_string($item)) {
                $item = trim($item);
            }
        });
        return $data;
    }

    /**
     * Valida um array de dados contra regras especificadas
     * 
     * Regras suportadas:
     * - required
     * - csrf
     * - numeric
     * - integer
     * - email
     * - min:X
     * - max:X
     * - in:a,b,c
     *
     * @param array $data Dados a serem validados
     * @param array $rules Regras associadas a cada campo
     * @return bool Verdadeiro se passou em todas as validações
     */
    protected function validate(array $data, array $rules): bool
    {
        $this->validationErrors = [];

        foreach ($rules as $field => $fieldRules) {
            $ruleList = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $ruleParam = null;
                if (str_contains($rule, ':')) {
                    [$rule, $ruleParam] = explode(':', $rule, 2);
                }

                switch ($rule) {
                    case 'required':
                        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                            $this->addValidationError($field, "O campo {$field} é obrigatório.");
                        }
                        break;

                    case 'csrf':
                        if (!Session::validateCsrfToken((string)$value)) {
                            $this->addValidationError($field, "A validação de segurança CSRF falhou. Recarregue a página.");
                        }
                        break;

                    case 'numeric':
                        if ($value !== null && $value !== '' && !is_numeric($value)) {
                            $this->addValidationError($field, "O campo {$field} deve ser numérico.");
                        }
                        break;

                    case 'integer':
                        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                            $this->addValidationError($field, "O campo {$field} deve ser um número inteiro.");
                        }
                        break;

                    case 'email':
                        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addValidationError($field, "O campo {$field} deve conter um e-mail válido.");
                        }
                        break;

                    case 'min':
                        $min = (int)$ruleParam;
                        if (is_string($value) && mb_strlen($value) < $min) {
                            $this->addValidationError($field, "O campo {$field} deve ter no mínimo {$min} caracteres.");
                        } elseif (is_numeric($value) && $value < $min) {
                            $this->addValidationError($field, "O campo {$field} deve ser maior ou igual a {$min}.");
                        }
                        break;

                    case 'max':
                        $max = (int)$ruleParam;
                        if (is_string($value) && mb_strlen($value) > $max) {
                            $this->addValidationError($field, "O campo {$field} deve ter no máximo {$max} caracteres.");
                        } elseif (is_numeric($value) && $value > $max) {
                            $this->addValidationError($field, "O campo {$field} deve ser menor ou igual a {$max}.");
                        }
                        break;

                    case 'in':
                        $allowed = explode(',', (string)$ruleParam);
                        if ($value !== null && !in_array($value, $allowed)) {
                            $this->addValidationError($field, "O valor selecionado para {$field} é inválido.");
                        }
                        break;
                }
            }
        }

        return empty($this->validationErrors);
    }

    /**
     * Adiciona um erro à lista de validação
     */
    protected function addValidationError(string $field, string $message): void
    {
        $this->validationErrors[$field][] = $message;
    }

    /**
     * Obtém os erros de validação
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Verifica se houve falhas de validação
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }
}
