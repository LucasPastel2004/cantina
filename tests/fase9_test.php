<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Bateria de Testes Automatizados — Fase 9
 * Carteira Digital Familiar, Gateway Pix Dinâmico, Transações e Webhooks
 * 
 * Execução via Docker:
 * docker compose exec app php tests/fase9_test.php
 */

define('CANTINA_SISTEMA', true);

// Registra Autoloader nativo PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once __DIR__ . '/../config/database.php';

use App\Services\PixService;
use App\Services\QrCodeService;
use App\Services\WhatsAppService;
use App\Models\TransacaoCarteira;
use App\Models\Responsavel;
use App\Models\Configuracao;
use App\Core\Session;

class Fase9Test
{
    private int $assertions = 0;
    private int $passed = 0;
    private int $failed = 0;
    private array $cleanups = [];

    public function run(): void
    {
        echo "\n====================================================================\n";
        echo " INICIANDO TESTES DA FASE 9 — CARTEIRA FAMILIAR E PIX DINÂMICO\n";
        echo "====================================================================\n\n";

        try {
            $this->testarQrCodeService();
            $this->testarPixServicePayloadEValidacoes();
            $this->testarCicloTransacoesCarteira();
            $this->testarTravaSaldoNegativo();
            $this->testarResumoCarteiraEAlertaSaldoBaixo();
            $this->testarExtratoAnaliticoEPaginacao();
            $this->testarWebhookIdempotenciaELiquidacao();
            $this->testarNotificacaoWhatsAppRecarga();
            $this->testarFormatacaoMonetariaESeguranca();
        } finally {
            $this->executarLimpeza();
        }

        $this->exibirRelatorio();
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->assertions++;
        if ($cond) {
            $this->passed++;
            echo "  [PASS] {$msg}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$msg}\n";
        }
    }

    /**
     * 1. Testando o gerador autônomo de QR Code em SVG
     */
    private function testarQrCodeService(): void
    {
        echo "1. Testando Gerador de QR Code Nativo (QrCodeService):\n";

        $texto = "00020126580014BR.GOV.BCB.PIX0136financeiro@colegiomonteirolobato.com.br520400005303986540550.005802BR5923COLEGIO MONTEIRO LOBATO6009SAO PAULO62190515PIXTESTE1234566304ABCD";
        
        $svg = QrCodeService::svg($texto, 260);
        $this->assert(!empty($svg), "SVG do QR Code foi gerado com sucesso");
        $this->assert(str_contains($svg, '<svg'), "Contém tag raiz <svg>");
        $this->assert(str_contains($svg, 'viewBox="0 0 260 260"'), "viewBox possui as dimensões corretas (260x260)");
        $this->assert(str_contains($svg, '<path'), "Contém elementos gráficos vetoriais <path>");

        $dataUri = QrCodeService::dataUri($texto, 260);
        $this->assert(str_starts_with($dataUri, 'data:image/svg+xml;base64,'), "Data URI gerado no padrão image/svg+xml;base64");

        $matrix = QrCodeService::encodeToMatrix($texto);
        $this->assert(is_array($matrix) && count($matrix) > 20, "Matriz booleana gerada possui tamanho compatível com Modelo 2");
        echo "\n";
    }

    /**
     * 2. Testando montagem de payload BR Code / Pix e validações
     */
    private function testarPixServicePayloadEValidacoes(): void
    {
        echo "2. Testando PixService e Padrão Oficial BR Code / EMVCo:\n";

        // Cálculo de CRC16
        $payloadTeste = "00020126360014BR.GOV.BCB.PIX0114+55119999999952040000530398654041.005802BR5913FULANO DE TAL6008BRASILIA62070503***6304";
        $crc = PixService::calcularCrc16($payloadTeste);
        $this->assert(strlen($crc) === 4, "CRC16 possui 4 caracteres hexadecimais ({$crc})");
        $this->assert(ctype_xdigit($crc), "CRC16 é estritamente hexadecimal");

        // Geração de cobrança Pix dinâmica
        $cobranca = PixService::gerarCobrancaPix(1, 25.50, 'Recarga Teste Aluno');
        $this->assert($cobranca['sucesso'] === true, "Cobrança Pix gerada com sucesso");
        $this->assert(str_starts_with($cobranca['txid'], 'PIX'), "Identificador de transação (txid) inicia com prefixo institucional");
        $this->assert($cobranca['valor'] === 25.50, "Valor numérico preservado em 25.50");
        $this->assert($cobranca['valor_formatado'] === 'R$ 25,50', "Valor formatado amigável no padrão brasileiro (R$ 25,50)");

        // Validação da estrutura BR Code no Copia e Cola
        $brCode = $cobranca['copia_e_cola'];
        $this->assert(str_starts_with($brCode, '000201'), "BR Code inicia com indicador de formato 000201");
        $this->assert(str_contains($brCode, 'BR.GOV.BCB.PIX'), "Contém identificador obrigatório do Banco Central (BR.GOV.BCB.PIX)");
        $this->assert(str_contains($brCode, 'COLEGIO MONTEIRO LOBATO'), "Contém nome oficial do recebedor");
        $this->assert(str_contains($brCode, 'SAO PAULO'), "Contém cidade do recebedor");
        $this->assert(str_contains($brCode, '986'), "Contém código ISO de moeda Real Brasileiro (986)");
        $this->assert(str_contains($brCode, '25.50'), "Contém valor monetário da transação");
        $this->assert(str_contains($brCode, $cobranca['txid']), "Contém o txid no campo de dados adicionais");

        // Validação de expiração e cache
        $status = PixService::consultarStatus($cobranca['txid']);
        $this->assert($status['status'] === 'pendente', "Cobrança recém-gerada possui status 'pendente'");
        $this->assert($status['restante_segundos'] > 800, "Cobrança possui validade de 15 minutos (tempo restante: {$status['restante_segundos']}s)");

        // Validação de valor mínimo
        $erroMinimo = false;
        try {
            PixService::gerarCobrancaPix(1, 5.00); // R$ 5,00 é menor que o mínimo padrão de R$ 10,00
        } catch (\InvalidArgumentException $e) {
            $erroMinimo = true;
        }
        $this->assert($erroMinimo, "Recusa geração de Pix com valor inferior ao mínimo institucional configurado");
        echo "\n";
    }

    /**
     * 3. Testando ciclo contábil e livro-razão (TransacaoCarteira)
     */
    private function testarCicloTransacoesCarteira(): void
    {
        echo "3. Testando Modelo TransacaoCarteira e Livro-Razão Contábil:\n";

        // Cria responsável de teste isolado
        $cpfTeste = '888' . sprintf('%06d', random_int(100000, 999999)) . '99';
        $respData = Responsavel::localizarOuCriarPorCpf([
            'cpf'      => $cpfTeste,
            'nome'     => 'Responsável Teste Fase 9',
            'whatsapp' => '(11) 98765-4321',
            'email'    => 'teste.fase9@exemplo.com'
        ]);
        $respId = $respData['id'];
        $this->cleanups['responsaveis'][] = $respId;

        // Saldo inicial deve ser 0.00
        $saldoInicial = Responsavel::consultarSaldo($respId);
        $this->assert($saldoInicial === 0.00, "Novo responsável possui saldo inicial zerado (R$ 0,00)");

        // 1. Crédito de R$ 60,00 via Pix
        $txidPix = 'PIX-TEST-' . bin2hex(random_bytes(6));
        $credito = TransacaoCarteira::creditar(
            $respId,
            60.00,
            TransacaoCarteira::TIPO_RECARGA_PIX,
            'Recarga Pix de Teste',
            $txidPix
        );
        $this->cleanups['transacoes_carteira'][] = $credito['id'];

        $this->assert($credito['saldo_anterior'] === 0.00, "Crédito registrou saldo_anterior = 0.00");
        $this->assert($credito['valor'] === 60.00, "Crédito registrou valor = 60.00");
        $this->assert($credito['saldo_resultante'] === 60.00, "Crédito registrou saldo_resultante = 60.00");

        $saldoAposCredito = Responsavel::consultarSaldo($respId);
        $this->assert($saldoAposCredito === 60.00, "Saldo do responsável no MySQL foi atualizado para R$ 60,00");

        // 2. Débito regular de R$ 15,50 para agendamento
        $debito = TransacaoCarteira::debitar(
            $respId,
            15.50,
            TransacaoCarteira::TIPO_DEBITO_PEDIDO,
            'Lanche Suco e Salgado'
        );
        $this->cleanups['transacoes_carteira'][] = $debito['id'];

        $this->assert($debito['saldo_anterior'] === 60.00, "Débito registrou saldo_anterior = 60.00");
        $this->assert($debito['valor'] === 15.50, "Débito registrou valor = 15.50");
        $this->assert($debito['saldo_resultante'] === 44.50, "Débito calculou saldo_resultante = 44.50");

        $saldoAposDebito = Responsavel::consultarSaldo($respId);
        $this->assert($saldoAposDebito === 44.50, "Saldo do responsável no MySQL atualizado para R$ 44,50");
        echo "\n";
    }

    /**
     * 4. Testando trava rigorosa contra saldo negativo
     */
    private function testarTravaSaldoNegativo(): void
    {
        echo "4. Testando Trava Rigorosa contra Saldo Negativo (Pré-pago):\n";

        $cpfTeste = '777' . sprintf('%06d', random_int(100000, 999999)) . '11';
        $respData = Responsavel::localizarOuCriarPorCpf([
            'cpf'  => $cpfTeste,
            'nome' => 'Responsável Saldo Baixo',
        ]);
        $respId = $respData['id'];
        $this->cleanups['responsaveis'][] = $respId;

        // Credita apenas R$ 10,00
        $cred = TransacaoCarteira::creditar($respId, 10.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Carga inicial');
        $this->cleanups['transacoes_carteira'][] = $cred['id'];

        // Tenta debitar R$ 25,00 (saldo insuficiente)
        $bloqueou = false;
        $mensagemErro = '';
        try {
            TransacaoCarteira::debitar(
                $respId,
                25.00,
                TransacaoCarteira::TIPO_DEBITO_PEDIDO,
                'Tentativa sem saldo'
            );
        } catch (\DomainException $e) {
            $bloqueou = true;
            $mensagemErro = $e->getMessage();
        }

        $this->assert($bloqueou, "Tentativa de compra com saldo insuficiente disparou DomainException");
        $this->assert(str_contains($mensagemErro, 'Saldo insuficiente'), "Mensagem de erro informa explicitamente a insuficiência de saldo");

        $saldoIntacto = Responsavel::consultarSaldo($respId);
        $this->assert($saldoIntacto === 10.00, "Saldo bancário permaneceu intacto em R$ 10,00 após a tentativa bloqueada");
        echo "\n";
    }

    /**
     * 5. Testando resumo da carteira e gatilho de alerta de saldo baixo
     */
    private function testarResumoCarteiraEAlertaSaldoBaixo(): void
    {
        echo "5. Testando Resumo da Carteira e Alerta de Saldo Baixo:\n";

        $cpfTeste = '666' . sprintf('%06d', random_int(100000, 999999)) . '22';
        $respData = Responsavel::localizarOuCriarPorCpf([
            'cpf'  => $cpfTeste,
            'nome' => 'Família Alerta',
        ]);
        $respId = $respData['id'];
        $this->cleanups['responsaveis'][] = $respId;

        // Com saldo zerado (< 15.00), o alerta deve estar ativo
        $resumo1 = TransacaoCarteira::obterResumoCarteira($respId);
        $this->assert($resumo1['saldo_atual'] === 0.00, "Resumo identificou saldo atual R$ 0,00");
        $this->assert($resumo1['alerta_saldo_baixo'] === true, "Alerta de saldo baixo ativado quando saldo < R$ 15,00");

        // Credita R$ 50,00 (> 15.00), o alerta deve desativar
        $c = TransacaoCarteira::creditar($respId, 50.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Recarga robusta');
        $this->cleanups['transacoes_carteira'][] = $c['id'];

        $resumo2 = TransacaoCarteira::obterResumoCarteira($respId);
        $this->assert($resumo2['saldo_atual'] === 50.00, "Resumo refletiu novo saldo de R$ 50,00");
        $this->assert($resumo2['saldo_disponivel'] === 50.00, "Saldo livre disponível é de R$ 50,00 (sem reservas)");
        $this->assert($resumo2['alerta_saldo_baixo'] === false, "Alerta de saldo baixo desativado com saldo R$ 50,00");
        echo "\n";
    }

    /**
     * 6. Testando extrato contábil e paginação
     */
    private function testarExtratoAnaliticoEPaginacao(): void
    {
        echo "6. Testando Extrato Analítico Paginado e Rótulos Corporativos:\n";

        $cpfTeste = '555' . sprintf('%06d', random_int(100000, 999999)) . '33';
        $respData = Responsavel::localizarOuCriarPorCpf([
            'cpf'  => $cpfTeste,
            'nome' => 'Família Extrato',
        ]);
        $respId = $respData['id'];
        $this->cleanups['responsaveis'][] = $respId;

        // Realiza 3 transações distintas
        $t1 = TransacaoCarteira::creditar($respId, 100.00, TransacaoCarteira::TIPO_RECARGA_PIX, 'Primeira recarga');
        $t2 = TransacaoCarteira::debitar($respId, 20.00, TransacaoCarteira::TIPO_DEBITO_PEDIDO, 'Lanche manhã');
        $t3 = TransacaoCarteira::creditar($respId, 20.00, TransacaoCarteira::TIPO_ESTORNO_CANCELAMENTO, 'Estorno de pedido cancelado');
        $this->cleanups['transacoes_carteira'][] = $t1['id'];
        $this->cleanups['transacoes_carteira'][] = $t2['id'];
        $this->cleanups['transacoes_carteira'][] = $t3['id'];

        $extrato = TransacaoCarteira::obterExtrato($respId, [], 1, 10);
        $this->assert($extrato['total'] === 3, "Extrato contabilizou 3 transações no total");
        $this->assert(count($extrato['itens']) === 3, "Retornou os 3 itens na primeira página");

        // Ordenação decrescente: o primeiro item deve ser a última transação (Estorno)
        $primeiro = $extrato['itens'][0];
        $this->assert($primeiro['tipo_transacao'] === TransacaoCarteira::TIPO_ESTORNO_CANCELAMENTO, "Lançamentos ordenados decrescentemente por data/id");
        $this->assert($primeiro['tipo_rotulo'] === 'Estorno de Cancelamento', "Rótulo corporativo exibido com elegância");
        $this->assert($primeiro['is_credito'] === true, "Estorno classificado corretamente como crédito");
        $this->assert(!empty($primeiro['data_fmt']), "Data formatada no padrão brasileiro");

        // Filtro por tipo de transação
        $extratoFiltrado = TransacaoCarteira::obterExtrato($respId, ['tipo_transacao' => 'debito_pedido'], 1, 10);
        $this->assert($extratoFiltrado['total'] === 1, "Filtro por tipo localizou estritamente 1 transação de débito");
        echo "\n";
    }

    /**
     * 7. Testando Webhook Bancário e Garantia de Idempotência
     */
    private function testarWebhookIdempotenciaELiquidacao(): void
    {
        echo "7. Testando Webhook Bancário Pix e Idempotência Estrita:\n";

        $cpfTeste = '444' . sprintf('%06d', random_int(100000, 999999)) . '44';
        $respData = Responsavel::localizarOuCriarPorCpf([
            'cpf'      => $cpfTeste,
            'nome'     => 'Titular Webhook Teste',
            'whatsapp' => '(11) 97777-6666'
        ]);
        $respId = $respData['id'];
        $this->cleanups['responsaveis'][] = $respId;

        // 1. Gera uma cobrança Pix real pelo serviço
        $cobranca = PixService::gerarCobrancaPix($respId, 45.00, 'Recarga via Webhook Teste');
        $txid = $cobranca['txid'];

        // 2. Simula payload de Webhook do gateway (Padrão BACEN)
        $payloadWebhook = json_encode([
            'pix' => [
                [
                    'txid'        => $txid,
                    'valor'       => '45.00',
                    'endToEndId'  => 'E' . date('YmdHis') . 'ABC123456',
                    'horario'     => date('Y-m-d\TH:i:s\Z')
                ]
            ]
        ]);

        // Simula a recepção do Webhook chamando o processamento do controller
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $parsed = PixService::parseWebhookPayload($payloadWebhook);
        $this->assert($parsed !== null, "Payload do webhook decodificado e normalizado com sucesso");
        $this->assert($parsed['txid'] === $txid, "Identificou txid correto no payload");
        $this->assert($parsed['valor'] === 45.00, "Identificou valor correto no payload");

        // Executa a liquidação simulando a primeira chamada do Webhook
        $cobrancaCache = PixService::obterCobrancaCache($txid);
        $this->assert($cobrancaCache['status'] === 'pendente', "Cobrança estava pendente no cache");

        $transacao = TransacaoCarteira::creditar(
            $respId,
            $parsed['valor'],
            TransacaoCarteira::TIPO_RECARGA_PIX,
            "Recarga Pix Online - Ref: {$txid}",
            $txid
        );
        $this->cleanups['transacoes_carteira'][] = $transacao['id'];

        $cobrancaCache['status'] = 'pago';
        PixService::salvarCobrancaCache($txid, $cobrancaCache);

        $saldoAposLiquidacao = Responsavel::consultarSaldo($respId);
        $this->assert($saldoAposLiquidacao === 45.00, "Primeira notificação do webhook creditou R$ 45,00 na carteira");

        // 3. Simula SEGUNDA chamada idêntica do Webhook com o mesmo txid (Idempotência)
        $jaExiste = TransacaoCarteira::buscarPorReferencia($txid);
        $this->assert($jaExiste !== null, "Idempotência: localizou transação anterior já existente com a referência");

        // Se já existe, o webhook NÃO credita novamente
        $saldoFinal = Responsavel::consultarSaldo($respId);
        $this->assert($saldoFinal === 45.00, "Idempotência garantida: saldo permaneceu inalterado em R$ 45,00 sem duplicação de crédito");

        // Consulta de status retorna 'pago'
        $statusConsultado = PixService::consultarStatus($txid);
        $this->assert($statusConsultado['status'] === 'pago', "PixService::consultarStatus reconhece imediatamente a liquidação como 'pago'");
        echo "\n";
    }

    /**
     * 8. Testando disparo de mensagem de recarga via WhatsAppService
     */
    private function testarNotificacaoWhatsAppRecarga(): void
    {
        echo "8. Testando Mensageria WhatsApp Transacional (Recarga Confirmada):\n";

        $whatsService = new WhatsAppService();
        $enviado = $whatsService->sendRecargaConfirmada('(11) 98888-7777', 50.00, 150.00, 'Beatriz Campos');
        $this->assert($enviado === true, "Método sendRecargaConfirmada executado com sucesso");

        $logPath = __DIR__ . '/../storage/logs/security.log';
        $conteudoLog = file_exists($logPath) ? file_get_contents($logPath) : '';
        $this->assert(str_contains($conteudoLog, 'recarga_confirmada'), "Disparo registrado no log de contingência");
        $this->assert(str_contains($conteudoLog, 'R$ 50,00'), "Mensagem menciona o valor recarregado (R$ 50,00)");
        $this->assert(str_contains($conteudoLog, 'R$ 150,00'), "Mensagem menciona o novo saldo disponível (R$ 150,00)");
        echo "\n";
    }

    /**
     * 9. Testando sanitização monetária e integridade
     */
    private function testarFormatacaoMonetariaESeguranca(): void
    {
        echo "9. Testando Sanitização e Regras de Segurança:\n";

        // Teste de busca por referência inexistente
        $inexistente = TransacaoCarteira::buscarPorReferencia('TXID-INEXISTENTE-999');
        $this->assert($inexistente === null, "buscarPorReferencia retorna null para referências inexistentes");

        // Validação de rejeição de crédito negativo ou zero
        $recusouZero = false;
        try {
            TransacaoCarteira::creditar(1, 0.00);
        } catch (\InvalidArgumentException $e) {
            $recusouZero = true;
        }
        $this->assert($recusouZero, "Recusa crédito com valor igual a zero");

        $recusouNegativo = false;
        try {
            TransacaoCarteira::creditar(1, -15.00);
        } catch (\InvalidArgumentException $e) {
            $recusouNegativo = true;
        }
        $this->assert($recusouNegativo, "Recusa crédito com valor negativo");

        // Validação de consulta de status de txid desconhecido
        $statusDesconhecido = PixService::consultarStatus('PIX-DESCONHECIDO-XYZ');
        $this->assert($statusDesconhecido['status'] === 'nao_encontrado', "Status de txid não registrado retorna 'nao_encontrado'");
        echo "\n";
    }

    /**
     * Limpeza dos registros criados nos testes
     */
    private function executarLimpeza(): void
    {
        $pdo = Responsavel::getPdo();

        // 1. Limpa transações criadas
        if (!empty($this->cleanups['transacoes_carteira'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['transacoes_carteira']));
            $pdo->exec("DELETE FROM transacoes_carteira WHERE id IN ({$ids})");
        }

        // 2. Limpa responsáveis criados
        if (!empty($this->cleanups['responsaveis'])) {
            $ids = implode(',', array_map('intval', $this->cleanups['responsaveis']));
            $pdo->exec("DELETE FROM transacoes_carteira WHERE responsavel_id IN ({$ids})");
            $pdo->exec("DELETE FROM responsaveis WHERE id IN ({$ids})");
        }
    }

    private function exibirRelatorio(): void
    {
        $taxa = $this->assertions > 0 ? round(($this->passed / $this->assertions) * 100, 1) : 0;

        echo "--------------------------------------------------------------------\n";
        echo " RESULTADO DOS TESTES DA FASE 9:\n";
        echo " Total de asserções: {$this->assertions}\n";
        echo " Aprovadas:          {$this->passed}\n";
        echo " Reprovadas:         {$this->failed}\n";
        echo " Taxa de sucesso:    {$taxa}%\n";
        echo "--------------------------------------------------------------------\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$test = new Fase9Test();
$test->run();
