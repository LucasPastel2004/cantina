<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Capturador Global de Erros e Exceções com Fallback em Disco
 * 
 * Conforme docs/FSD.md (Seção 19) e docs/DESIGN.md
 */

namespace App\Core;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class ErrorHandler
{
    /**
     * Caminho do arquivo de log de contingência
     */
    private static string $logFilePath = __DIR__ . '/../../storage/logs/error.log';

    /**
     * Tamanho máximo do arquivo de log antes da rotação (5 MB)
     */
    private static int $maxLogSizeBytes = 5242880; // 5 * 1024 * 1024

    /**
     * Flag indicando se o modo de depuração está ativo
     */
    private static bool $debug = false;

    /**
     * Registra os manipuladores globais de erro, exceção e desligamento
     *
     * @param bool $debug Modo de depuração
     */
    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    /**
     * Converte erros nativos do PHP em ErrorException
     */
    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        // Ignora avisos de depreciação do PHP para não interromper requisições operacionais
        if ($level === E_DEPRECATED || $level === E_USER_DEPRECATED) {
            return true;
        }

        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Trata exceções não capturadas
     */
    public static function handleException(\Throwable $exception): void
    {
        $errorCode = self::generateErrorCode();
        $level = ($exception instanceof \ParseError || $exception instanceof \Error) ? 'CRITICAL' : 'ERROR';

        // 1. Registra a ocorrência no arquivo de contingência
        self::logToFile($level, $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception);

        // 2. Se houver banco de dados disponível futuramente, tentará registrar em logs_erros
        self::tryLogToDatabase($errorCode, $level, $exception);

        // 3. Envia resposta segura ao cliente
        self::respondToClient($errorCode, $exception);
    }

    /**
     * Captura erros fatais no encerramento da execução
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            $errorCode = self::generateErrorCode();
            $message = $error['message'] ?? 'Erro fatal desconhecido';
            $file = $error['file'] ?? 'desconhecido';
            $line = $error['line'] ?? 0;

            self::logToFile('CRITICAL', $message, $file, $line);
            self::respondToClient($errorCode, new \ErrorException($message, 0, $error['type'], $file, $line));
        }
    }

    /**
     * Registra o erro no arquivo de texto formatado com rotação em 5 MB
     * 
     * Formato exigido no FSD:
     * [YYYY-MM-DD HH:MM:SS] [NIVEL] [IP] [USUARIO] Mensagem no arquivo X linha Y | URL: /rota
     */
    private static function logToFile(string $level, string $message, string $file, int $line, ?\Throwable $exception = null): void
    {
        try {
            $dir = dirname(self::$logFilePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }

            // Verifica e executa rotação caso ultrapasse 5 MB
            if (file_exists(self::$logFilePath) && filesize(self::$logFilePath) >= self::$maxLogSizeBytes) {
                $rotatedName = $dir . '/error_' . date('Y-m-d_His') . '.log';
                @rename(self::$logFilePath, $rotatedName);
            }

            $date = date('Y-m-d H:i:s');
            $ip = self::getClientIp();
            $user = self::getAuthenticatedUser();
            $url = $_SERVER['REQUEST_URI'] ?? '/';

            // Remove quebras de linha da mensagem principal para manter o padrão tabular por linha
            $cleanMessage = str_replace(["\r", "\n"], ' ', $message);

            $logLine = sprintf(
                "[%s] [%s] [%s] [%s] %s no arquivo %s linha %d | URL: %s" . PHP_EOL,
                $date,
                strtoupper($level),
                $ip,
                $user,
                $cleanMessage,
                $file,
                $line,
                $url
            );

            // Adiciona stack trace identado se houver exceção
            if ($exception !== null) {
                $trace = $exception->getTraceAsString();
                $logLine .= "   Stack trace:" . PHP_EOL . "   " . str_replace("\n", "\n   ", $trace) . PHP_EOL;
            }

            @file_put_contents(self::$logFilePath, $logLine, FILE_APPEND | LOCK_EX);
        } catch (\Throwable) {
            // Em caso extremo de falha de I/O, evita loop de erros
        }
    }

    /**
     * Tenta persistir no banco de dados na tabela logs_erros (Migration 025)
     */
    private static function tryLogToDatabase(string $errorCode, string $level, \Throwable $exception): void
    {
        try {
            if (!class_exists(\App\Core\Model::class)) {
                return;
            }
            $pdo = \App\Core\Model::getPdo();
            if (!$pdo) {
                return;
            }

            $stmt = $pdo->prepare("
                INSERT INTO logs_erros (nivel, mensagem, arquivo, linha, stack_trace, url_requisicao, usuario_id, ip_origem, created_at)
                VALUES (:nivel, :mensagem, :arquivo, :linha, :stack_trace, :url_requisicao, :usuario_id, :ip_origem, NOW())
            ");

            $nivelDb = (strtolower($level) === 'critical') ? 'critical' : 'error';
            $msg = "[" . $errorCode . "] " . $exception->getMessage();
            $file = substr($exception->getFile(), 0, 255);
            $line = $exception->getLine();
            $trace = $exception->getTraceAsString();
            $url = substr($_SERVER['REQUEST_URI'] ?? '/', 0, 255);
            $user = substr(self::getAuthenticatedUser(), 0, 50);
            $ip = substr(self::getClientIp(), 0, 45);

            $stmt->execute([
                ':nivel'          => $nivelDb,
                ':mensagem'       => $msg,
                ':arquivo'        => $file,
                ':linha'          => $line,
                ':stack_trace'    => $trace,
                ':url_requisicao' => $url,
                ':usuario_id'     => $user,
                ':ip_origem'      => $ip,
            ]);
        } catch (\Throwable) {
            // Se falhar o banco de dados, o logToFile já garantiu a contingência no disco
        }
    }

    /**
     * Renderiza a resposta amigável e segura para o usuário final
     */
    private static function respondToClient(string $errorCode, \Throwable $exception): void
    {
        if (!headers_sent()) {
            http_response_code(500);
        }

        $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/api/'));

        $safeMessage = "Ocorreu uma falha no processamento. Nossa equipe já foi notificada. Código do erro: [{$errorCode}]";

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            $response = [
                'error'   => true,
                'message' => $safeMessage,
                'code'    => $errorCode
            ];

            if (self::$debug) {
                $response['debug'] = [
                    'message' => $exception->getMessage(),
                    'file'    => $exception->getFile(),
                    'line'    => $exception->getLine(),
                    'trace'   => explode("\n", $exception->getTraceAsString())
                ];
            }

            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // Limpa quaisquer buffers de saída pendentes para evitar concatenação de HTML quebrado
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Renderização HTML da tela de erro 500
        $viewFile = __DIR__ . '/../views/errors/500.php';
        if (file_exists($viewFile)) {
            $debug = self::$debug;
            require $viewFile;
        } else {
            self::fallbackHtmlError($safeMessage, $errorCode, $exception);
        }
        exit;
    }

    /**
     * HTML de contingência minimalista caso a view não seja encontrada
     */
    private static function fallbackHtmlError(string $message, string $code, \Throwable $exception): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Erro de Processamento — Cantina Web</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; color: #0f172a; margin: 0; padding: 40px 20px; display: flex; justify-content: center; align-items: center; min-height: 80vh; }
                .card { background: #ffffff; border: 1px solid #cbd5e1; border-top: 4px solid #dc2626; border-radius: 6px; padding: 24px; max-width: 540px; width: 100%; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
                h1 { font-size: 18px; margin: 0 0 12px 0; color: #dc2626; display: flex; align-items: center; gap: 8px; }
                p { font-size: 13px; line-height: 1.5; color: #475569; margin: 0 0 16px 0; }
                .code-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px 12px; font-family: monospace; font-size: 13px; border-radius: 4px; color: #0f172a; font-weight: 600; margin-bottom: 16px; }
                .btn { display: inline-block; background: #2563eb; color: #ffffff; text-decoration: none; padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: 4px; }
                .btn:hover { background: #1d4ed8; }
                .debug-box { margin-top: 16px; padding: 12px; background: #fee2e2; border: 1px solid #fca5a5; font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-all; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>⚠ Falha de Processamento</h1>
                <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="code-box">Código: <?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></div>
                <a href="javascript:history.back()" class="btn">Voltar para a página anterior</a>
                <?php if (self::$debug): ?>
                    <div class="debug-box"><strong>Detalhes de Debug:</strong><br><?php echo htmlspecialchars($exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString(), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Gera um identificador único de erro legível (UUID simplificado)
     */
    private static function generateErrorCode(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Obtém o IP do cliente de forma segura
     */
    private static function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Obtém o identificador do usuário autenticado se disponível
     */
    private static function getAuthenticatedUser(): string
    {
        if (isset($_SESSION['usuario_login'])) {
            return (string)$_SESSION['usuario_login'];
        }
        if (isset($_SESSION['responsavel_cpf'])) {
            return 'CPF:' . (string)$_SESSION['responsavel_cpf'];
        }
        return 'ANONIMO';
    }
}
