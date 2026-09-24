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

        // Se o payload corresponder a uma devolução ou contestação MED, roteia para o processador MED
        if (PixService::isPayloadDevolucao($rawBody)) {
            $this->processarWebhookDevolucao($headers, $rawBody, $queryToken);
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
     * Endpoint dedicado de recepção de devoluções e contestações Pix MED
     * Rota: POST /webhook/pix/devolucao e POST /webhook/pix/med
     */
    public function devolucao(): void
    {
        $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
        $rawBody = file_get_contents('php://input') ?: '';
        $queryToken = $_GET['token'] ?? null;

        // Se for requisição GET de verificação
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || empty($rawBody)) {
            $this->json([
                'sucesso' => true,
                'servico' => 'Webhook Pix MED Cantina Web',
                'status'  => 'operacional',
                'horario' => date('Y-m-d H:i:s')
            ], 200);
            return;
        }

        $this->processarWebhookDevolucao($headers, $rawBody, $queryToken);
    }

    /**
     * Processa a notificação bancária de devolução ou estorno MED
     */
    private function processarWebhookDevolucao(array $headers, string $rawBody, ?string $queryToken): void
    {
        // 1. Validação de autenticidade da notificação bancária
        if (!PixService::validarWebhook($headers, $rawBody, $queryToken)) {
            $this->registrarLogSeguranca('webhook_med_invalido', [
                'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'desconhecido',
                'headers' => $headers,
                'motivo'  => 'Assinatura ou token de webhook inválido para devolução MED.'
            ]);

            $this->json([
                'sucesso' => false,
                'erro'    => 'Não autorizado. Assinatura ou credencial de webhook inválida.'
            ], 401);
            return;
        }

        // 2. Extração dos dados normalizados de devolução
        $dadosDev = PixService::parseWebhookDevolucaoPayload($rawBody);
        if (!$dadosDev || ($dadosDev['valor'] ?? 0) <= 0) {
            $this->json([
                'sucesso' => false,
                'erro'    => 'Payload de devolução MED malformado ou valor inválido.'
            ], 400);
            return;
        }

        $valor = (float)$dadosDev['valor'];
        $txid = trim((string)($dadosDev['txid'] ?? ''));
        $rtrId = trim((string)($dadosDev['rtr_id'] ?? ('RTR' . date('YmdHis'))));
        $motivo = (string)($dadosDev['motivo'] ?? 'Mecanismo Especial de Devolução (MED) - Banco Central');

        // 3. Identificação do Responsável
        $responsavelId = null;

        if (!empty($dadosDev['responsavel_id'])) {
            $responsavelId = (int)$dadosDev['responsavel_id'];
        }

        // Busca pela transação original usando o txid
        if (!$responsavelId && !empty($txid)) {
            $txOriginal = TransacaoCarteira::buscarPorReferencia($txid);
            if ($txOriginal && !empty($txOriginal['responsavel_id'])) {
                $responsavelId = (int)$txOriginal['responsavel_id'];
            }
        }

        // Busca pelo endToEndId ou no cache do Pix
        if (!$responsavelId && !empty($txid)) {
            $cobrancaCache = PixService::obterCobrancaCache($txid);
            if ($cobrancaCache && !empty($cobrancaCache['responsavel_id'])) {
                $responsavelId = (int)$cobrancaCache['responsavel_id'];
            }
        }

        // Busca por CPF se informado
        if (!$responsavelId && !empty($dadosDev['cpf'])) {
            $respCpf = (new Responsavel())->findByCpf($dadosDev['cpf']);
            if ($respCpf) {
                $responsavelId = (int)$respCpf['id'];
            }
        }

        if (!$responsavelId) {
            $this->registrarLogSeguranca('webhook_med_orfao', [
                'rtr_id'  => $rtrId,
                'txid'    => $txid,
                'valor'   => $valor,
                'payload' => $dadosDev,
                'motivo'  => 'Devolução MED não vinculada a nenhum responsável titular no sistema.'
            ]);

            $this->json([
                'sucesso' => false,
                'erro'    => 'Responsável não identificado para a devolução MED informada.'
            ], 404);
            return;
        }

        // 4. Executa a rotina atômica de estorno MED
        try {
            $resultado = TransacaoCarteira::processarEstornoMed(
                $responsavelId,
                $valor,
                $rtrId,
                $motivo,
                null, // Notificação via webhook sem operador humano
                true  // Dispara notificação urgente via WhatsApp
            );

            $this->json([
                'sucesso'          => true,
                'status'           => $resultado['ja_processado'] ? 'ja_processado' : 'devolvido_med',
                'mensagem'         => $resultado['mensagem'] ?? 'Devolução MED processada e debitada com sucesso da carteira familiar.',
                'rtr_id'           => $rtrId,
                'txid'             => $txid,
                'transacao_id'     => $resultado['id'] ?? $resultado['transacao_id'] ?? null,
                'valor'            => $valor,
                'saldo_resultante' => $resultado['saldo_resultante'],
                'bloqueado_med'    => $resultado['bloqueado_med']
            ], 200);
        } catch (\Throwable $e) {
            $this->registrarLogSeguranca('webhook_med_falha_contabil', [
                'rtr_id' => $rtrId,
                'txid'   => $txid,
                'erro'   => $e->getMessage()
            ]);

            $this->json([
                'sucesso' => false,
                'erro'    => 'Erro interno ao processar a devolução MED: ' . $e->getMessage()
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
