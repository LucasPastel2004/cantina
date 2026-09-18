<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador de Integração: Webhooks Bancários
 * 
 * Ponto de recepção assíncrono para notificações de pagamento Pix dinâmico,
 * validação de autenticidade, garantia de idempotência, conciliação contábil
 * atômica e disparo de mensagens no WhatsApp.
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Models\TransacaoCarteira;
use App\Models\Responsavel;
use App\Services\PixService;
use App\Services\WhatsAppService;

class WebhookController extends Controller
{
    /**
     * Endpoint de recepção de notificações de pagamento Pix
     * Rota: POST /webhook/pix
     */
    public function pix(): void
    {
        // Headers HTTP em minúsculas
        $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
        $rawBody = file_get_contents('php://input') ?: '';
        $queryToken = $_GET['token'] ?? null;

        // 1. Validação de autenticidade e assinatura do webhook
        if (!PixService::validarWebhook($headers, $rawBody, $queryToken)) {
            $this->registrarLogSeguranca('webhook_pix_invalido', [
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido',
                'headers' => $headers,
                'motivo'  => 'Assinatura ou token de webhook inválido.'
            ]);

            $this->json([
                'sucesso' => false,
                'erro'    => 'Não autorizado. Assinatura ou credencial de webhook inválida.'
            ], 401);
            return;
        }

        // Se for requisição vazia ou GET de teste de conectividade
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || empty($rawBody)) {
            $this->json([
                'sucesso' => true,
                'servico' => 'Webhook Pix Cantina Web',
                'status'  => 'operacional',
                'horario' => date('Y-m-d H:i:s')
            ], 200);
            return;
        }

        // 2. Extração dos dados normalizados do payload
        $dadosPix = PixService::parseWebhookPayload($rawBody);
        if (!$dadosPix || empty($dadosPix['txid']) || ($dadosPix['valor'] ?? 0) <= 0) {
            $this->json([
                'sucesso' => false,
                'erro'    => 'Payload do webhook malformado ou campos obrigatórios ausentes.'
            ], 400);
            return;
        }

        $txid = trim($dadosPix['txid']);
        $valor = (float)$dadosPix['valor'];
        $endToEndId = $dadosPix['end_to_end_id'] ?? null;

        // 3. Garantia de Idempotência: verifica se a transação já foi liquidada anteriormente
        $transacaoExistente = TransacaoCarteira::buscarPorReferencia($txid);
        if ($transacaoExistente) {
            $this->json([
                'sucesso'          => true,
                'status'           => 'ja_processado',
                'mensagem'         => 'Esta cobrança já foi liquidada e creditada anteriormente.',
                'txid'             => $txid,
                'transacao_id'     => (int)$transacaoExistente['id'],
                'saldo_resultante' => (float)$transacaoExistente['saldo_resultante']
            ], 200);
            return;
        }

        // 4. Localização da cobrança pendente e do responsável associado
        $cobranca = PixService::obterCobrancaCache($txid);
        $responsavelId = null;

        if ($cobranca && !empty($cobranca['responsavel_id'])) {
            $responsavelId = (int)$cobranca['responsavel_id'];
        }

        // Fallback: se o payload trouxe dados do cliente ou CPF
        if (!$responsavelId && !empty($dadosPix['cpf'])) {
            $respCpf = (new Responsavel())->findByCpf($dadosPix['cpf']);
            if ($respCpf) {
                $responsavelId = (int)$respCpf['id'];
            }
        }

        if (!$responsavelId) {
            $this->registrarLogSeguranca('webhook_pix_orfao', [
                'txid'    => $txid,
                'valor'   => $valor,
                'payload' => $dadosPix,
                'motivo'  => 'Cobrança Pix não vinculada a nenhum responsável titular no sistema.'
            ]);

            $this->json([
                'sucesso' => false,
                'erro'    => 'Responsável não identificado para o txid informado.'
            ], 404);
            return;
        }

        // 5. Liquidação Contábil Atômica
        try {
            $descricao = sprintf('Recarga Pix Online - Ref: %s', $txid);
            if (!empty($endToEndId)) {
                $descricao .= ' - E2E: ' . substr($endToEndId, -8);
            }

            $transacao = TransacaoCarteira::creditar(
                $responsavelId,
                $valor,
                TransacaoCarteira::TIPO_RECARGA_PIX,
                $descricao,
                $txid
            );

            // 6. Atualiza o status da cobrança no cache
            if ($cobranca) {
                $cobranca['status'] = 'pago';
                $cobranca['liquidado_em'] = date('Y-m-d H:i:s');
                $cobranca['transacao_id'] = $transacao['id'];
                PixService::salvarCobrancaCache($txid, $cobranca);
            }

            // 7. Notificação Transacional via WhatsApp
            $resp = Responsavel::fetchOne("SELECT nome, whatsapp FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $responsavelId]);
            if ($resp && !empty($resp['whatsapp'])) {
                try {
                    $whatsService = new WhatsAppService();
                    $whatsService->sendRecargaConfirmada(
                        $resp['whatsapp'],
                        $valor,
                        (float)$transacao['saldo_resultante'],
                        $resp['nome'] ?? ''
                    );
                } catch (\Throwable $we) {
                    // Falha de notificação não compromete a transação financeira
                    error_log('[WHATSAPP_NOTIFY_ERROR] ' . $we->getMessage());
                }
            }

            // 8. Registro em auditoria/segurança
            $this->registrarLogSeguranca('webhook_pix_liquidado', [
                'txid'             => $txid,
                'responsavel_id'   => $responsavelId,
                'valor'            => $valor,
                'saldo_anterior'   => $transacao['saldo_anterior'],
                'saldo_resultante' => $transacao['saldo_resultante']
            ]);

            $this->json([
                'sucesso'          => true,
                'status'           => 'liquidado',
                'mensagem'         => 'Pagamento confirmado e saldo creditado com sucesso.',
                'txid'             => $txid,
                'transacao_id'     => $transacao['id'],
                'valor'            => $valor,
                'saldo_resultante' => $transacao['saldo_resultante']
            ], 200);
        } catch (\Throwable $e) {
            $this->registrarLogSeguranca('webhook_pix_falha_contabil', [
                'txid'    => $txid,
                'erro'    => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            $this->json([
                'sucesso' => false,
                'erro'    => 'Erro interno ao processar a liquidação contábil: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registra evento na tabela logs_seguranca com contingência em arquivo
     */
    private function registrarLogSeguranca(string $evento, array $detalhes): void
    {
        try {
            $sql = "INSERT INTO logs_seguranca (evento, cpf_ou_usuario, detalhes_json, ip_origem, created_at)
                    VALUES (:evento, :identificador, :detalhes, :ip, NOW())";

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            Responsavel::execute($sql, [
                'evento'        => $evento,
                'identificador' => 'WEBHOOK_PIX',
                'detalhes'      => json_encode($detalhes, JSON_UNESCAPED_UNICODE),
                'ip'            => $ip
            ]);
        } catch (\Throwable $e) {
            $logPath = __DIR__ . '/../../storage/logs/security.log';
            @file_put_contents(
                $logPath,
                sprintf("[%s] [%s] %s\n", date('Y-m-d H:i:s'), $evento, json_encode($detalhes)),
                FILE_APPEND
            );
        }
    }
}
