<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Adaptador de Integração: API de Mensageria WhatsApp
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class WhatsAppService
{
    /**
     * Memória estática de códigos enviados para modo mock/testes
     */
    private static array $mockSentCodes = [];

    /**
     * Gera um código numérico de 6 dígitos com segurança criptográfica
     */
    public static function generateCode(): string
    {
        return (string)random_int(100000, 999999);
    }

    /**
     * Envia código de verificação para Primeiro Acesso
     */
    public function sendFirstAccessCode(string $whatsapp, string $code, string $nome = ''): bool
    {
        $primeiroNome = !empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável';
        $mensagem = "Olá, {$primeiroNome}! Seu código de primeiro acesso à Cantina Web do Colégio Monteiro Lobato é: *{$code}*. Válido por 15 minutos.";

        return $this->sendMessage($whatsapp, $mensagem, 'primeiro_acesso', $code);
    }

    /**
     * Envia código de verificação para Redefinição de Senha
     */
    public function sendPasswordResetCode(string $whatsapp, string $code, string $nome = ''): bool
    {
        $primeiroNome = !empty($nome) ? explode(' ', trim($nome))[0] : 'Responsável';
        $mensagem = "Olá, {$primeiroNome}! Você solicitou a redefinição de sua senha na Cantina Web. Seu código de segurança é: *{$code}*. Válido por 15 minutos.";

        return $this->sendMessage($whatsapp, $mensagem, 'reset_senha', $code);
    }

    /**
     * Dispara a mensagem via REST ou armazena no provedor mock para testes
     */
    public function sendMessage(string $whatsapp, string $mensagem, string $tipo = 'geral', ?string $code = null): bool
    {
        $cleanPhone = preg_replace('/\D/', '', $whatsapp);

        // Registra o código enviado no mock para validação em testes
        if ($code !== null) {
            self::$mockSentCodes[$cleanPhone] = [
                'code'       => $code,
                'type'       => $tipo,
                'created_at' => time()
            ];
        }

        // Carrega configurações da aplicação
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];
        $whatsappConfig = $config['integracoes']['whatsapp'] ?? [];
        $provedor = $whatsappConfig['provedor'] ?? 'mock';

        if ($provedor === 'mock' || empty($whatsappConfig['api_url'])) {
            // Em ambiente local/mock, registra no log para contingência e auditoria
            $logPath = __DIR__ . '/../../storage/logs/security.log';
            if (is_dir(dirname($logPath))) {
                $logEntry = sprintf(
                    "[%s] [WHATSAPP_MOCK] [Destino: %s] [Tipo: %s] [Código: %s] [Mensagem: %s]\n",
                    date('Y-m-d H:i:s'),
                    $cleanPhone,
                    $tipo,
                    $code ?? 'N/A',
                    $mensagem
                );
                @file_put_contents($logPath, $logEntry, FILE_APPEND);
            }
            return true;
        }

        // Disparo real via API REST cURL
        $url = rtrim($whatsappConfig['api_url'], '/') . '/send-message';
        $payload = json_encode([
            'number'  => $cleanPhone,
            'message' => $mensagem
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . ($whatsappConfig['api_token'] ?? '')
            ],
            CURLOPT_TIMEOUT        => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Retorna o último código gerado para um determinado número (útil para testes automatizados e emulação)
     */
    public static function getLastCode(string $whatsapp): ?string
    {
        $cleanPhone = preg_replace('/\D/', '', $whatsapp);
        if (isset(self::$mockSentCodes[$cleanPhone]['code'])) {
            return self::$mockSentCodes[$cleanPhone]['code'];
        }

        // Em requisições HTTP segregadas, recupera o último código gravado no log de contingência
        $logPath = __DIR__ . '/../../storage/logs/security.log';
        if (file_exists($logPath)) {
            $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                for ($i = count($lines) - 1; $i >= 0; $i--) {
                    $line = $lines[$i];
                    if (str_contains($line, "[Destino: {$cleanPhone}]") && preg_match('/\[Código: (\d{6})\]/', $line, $matches)) {
                        return $matches[1];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Limpa o histórico de códigos do mock
     */
    public static function clearMockCodes(): void
    {
        self::$mockSentCodes = [];
    }
}
