<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Gerenciador Seguro de Sessões e Proteção CSRF
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class Session
{
    /**
     * Duração padrão de inatividade em segundos (60 minutos conforme FSD)
     */
    private static int $timeoutSeconds = 3600;

    /**
     * Flag indicando se a sessão já foi iniciada
     */
    private static bool $started = false;

    /**
     * Flag de proteção contra recursão na inicialização
     */
    private static bool $isInitializing = false;

    /**
     * Inicializa a sessão com cookies e parâmetros seguros
     *
     * @param array $config Configurações da aplicação
     */
    public static function start(array $config = []): void
    {
        if (self::$isInitializing) {
            return;
        }

        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            if (!isset($_SESSION) || !is_array($_SESSION)) {
                $_SESSION = [];
            }
            self::checkInactivity();
            return;
        }

        self::$isInitializing = true;

        // Configura timeout a partir dos parâmetros se fornecido
        $timeoutMinutes = $config['app']['session_timeout'] ?? 60;
        self::$timeoutSeconds = $timeoutMinutes * 60;

        // Detecta se a conexão utiliza HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Configuração de cookies de sessão segura se headers ainda não foram enviados
        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0, // Expira ao fechar o navegador
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        self::$started = true;

        // Verifica controle de inatividade
        self::checkInactivity();

        // Garante que exista um token CSRF ativo
        self::generateCsrfToken();

        self::$isInitializing = false;
    }

    /**
     * Verifica inatividade da sessão e encerra se ultrapassou o limite
     */
    private static function checkInactivity(): void
    {
        $now = time();
        if (isset($_SESSION['_last_activity'])) {
            $inactiveTime = $now - (int)$_SESSION['_last_activity'];
            if ($inactiveTime > self::$timeoutSeconds) {
                self::destroy();
                self::start();
                $_SESSION['_session_expired'] = true;
                return;
            }
        }
        $_SESSION['_last_activity'] = $now;
    }

    /**
     * Regenera o identificador da sessão para prevenir Session Fixation
     *
     * @param bool $deleteOldSession Exclui arquivo de sessão antigo
     */
    public static function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id($deleteOldSession);
        }
    }

    /**
     * Obtém um valor da sessão
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Define um valor na sessão
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Verifica se uma chave existe na sessão
     */
    public static function has(string $key): bool
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove uma chave da sessão
     */
    public static function remove(string $key): void
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Destrói completamente a sessão atual
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies') && !headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Gera ou recupera o token CSRF atual
     */
    public static function generateCsrfToken(): string
    {
        self::ensureStarted();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Retorna o token CSRF atual
     */
    public static function getCsrfToken(): string
    {
        return self::generateCsrfToken();
    }

    /**
     * Valida um token CSRF recebido contra o armazenado na sessão
     */
    public static function validateCsrfToken(?string $token): bool
    {
        self::ensureStarted();
        $stored = $_SESSION['_csrf_token'] ?? '';
        if (empty($stored) || empty($token)) {
            return false;
        }
        return hash_equals($stored, (string)$token);
    }

    /**
     * Gera o campo HTML hidden com o token CSRF
     */
    public static function csrfField(): string
    {
        $token = htmlspecialchars(self::getCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Define uma mensagem flash (exibida apenas na próxima requisição)
     */
    public static function setFlash(string $type, string $message): void
    {
        self::ensureStarted();
        $_SESSION['_flash'][$type][] = $message;
    }

    /**
     * Alias para setFlash (retrocompatibilidade e conveniência)
     */
    public static function flash(string $type, string $message): void
    {
        self::setFlash($type, $message);
    }

    /**
     * Recupera mensagens flash de um determinado tipo e as limpa
     */
    public static function getFlash(string $type): array
    {
        self::ensureStarted();
        $messages = $_SESSION['_flash'][$type] ?? [];
        unset($_SESSION['_flash'][$type]);
        return $messages;
    }

    /**
     * Verifica se há mensagens flash de um determinado tipo
     */
    public static function hasFlash(string $type): bool
    {
        self::ensureStarted();
        return !empty($_SESSION['_flash'][$type]);
    }

    /**
     * Garante que a sessão esteja ativa antes de qualquer leitura ou gravação
     */
    private static function ensureStarted(): void
    {
        if (!self::$started && session_status() === PHP_SESSION_NONE) {
            self::start();
        }
    }
}
