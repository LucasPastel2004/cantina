<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Adaptador de Integração: Gateway Pix Dinâmico e BR Code
 * 
 * Implementação do padrão oficial de Pix Dinâmico / BR Code (Banco Central / EMVCo),
 * geração de QR Code nativo, código Copia e Cola, controle de expiração (15 minutos)
 * e validação de Webhooks bancários.
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Services;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Models\Configuracao;
use App\Models\TransacaoCarteira;

class PixService
{
    private static ?array $config = null;

    /**
     * Carrega a configuração do gateway Pix
     */
    private static function getConfig(): array
    {
        if (self::$config === null) {
            $configFile = __DIR__ . '/../../config/config.php';
            $fullConfig = file_exists($configFile) ? require $configFile : [];
            self::$config = $fullConfig['integracoes']['pix'] ?? [];
        }
        return self::$config;
    }

    /**
     * Diretório seguro de cache para persistência de cobranças temporárias
     */
    private static function getCacheDir(): string
    {
        $dir = __DIR__ . '/../../storage/cache/pix';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
            @chmod(dirname($dir), 0777);
            @chmod($dir, 0777);
            $htaccess = $dir . '/.htaccess';
            if (!file_exists($htaccess)) {
                @file_put_contents($htaccess, "Require all denied\n");
            }
        } elseif (!is_writable($dir)) {
            @chmod($dir, 0777);
        }
        return $dir;
    }

    /**
     * Gera uma nova cobrança Pix dinâmica para recarga da carteira familiar
     * 
     * @param int $responsavelId ID do titular da carteira
     * @param float $valor Valor da recarga em reais
     * @param string $descricao Descrição da operação
     * @param array $dadosResponsavel Informações adicionais do titular (nome, cpf, whatsapp)
     * @return array Dados estruturados da cobrança gerada
     */
    public static function gerarCobrancaPix(
        int $responsavelId,
        float $valor,
        string $descricao = 'Recarga Carteira Cantina',
        array $dadosResponsavel = []
    ): array {
        // Validação de valor mínimo parametrizado nas configurações globais
        $configGlobal = Configuracao::obter();
        $recargaMinima = (float)($configGlobal['recarga_minima_pix'] ?? 10.00);

        if ($valor < $recargaMinima) {
            throw new \InvalidArgumentException(
                sprintf('O valor mínimo para recarga via Pix é de R$ %s.', number_format($recargaMinima, 2, ',', '.'))
            );
        }

        // Gera um identificador único de transação (txid) no padrão do Banco Central
        // Alfanumérico entre 26 e 35 caracteres
        $txid = 'PIX' . date('YmdHis') . strtoupper(bin2hex(random_bytes(5)));

        // Obtém chave Pix institucional e dados do recebedor
        $configPix = self::getConfig();
        $chavePix = !empty($configPix['chave_pix']) ? $configPix['chave_pix'] : 'financeiro@colegiomonteirolobato.com.br';
        $nomeRecebedor = 'COLEGIO MONTEIRO LOBATO';
        $cidadeRecebedor = 'SAO PAULO';

        // Monta o payload no padrão BR Code / EMVCo
        $payloadBrCode = self::montarPayloadBrCode($chavePix, $nomeRecebedor, $cidadeRecebedor, $valor, $txid, $descricao);

        // Gera o QR Code vetorial SVG e Data URI
        $qrCodeSvg = QrCodeService::svg($payloadBrCode, 260);
        $qrCodeDataUri = QrCodeService::dataUri($payloadBrCode, 260);

        // Define tempo de expiração: 15 minutos conforme FSD
        $agora = time();
        $expiraEmSegundos = 15 * 60; // 900 segundos
        $expiraTimestamp = $agora + $expiraEmSegundos;

        $cobrancaData = [
            'txid'               => $txid,
            'responsavel_id'     => $responsavelId,
            'valor'              => round($valor, 2),
            'descricao'          => $descricao,
            'chave_pix'          => $chavePix,
            'dados_responsavel'  => $dadosResponsavel,
            'status'             => 'pendente',
            'created_at'         => $agora,
            'created_at_fmt'     => date('Y-m-d H:i:s', $agora),
            'expira_em'          => $expiraTimestamp,
            'expira_em_fmt'      => date('Y-m-d H:i:s', $expiraTimestamp),
            'expira_em_segundos' => $expiraEmSegundos,
        ];

        // Persiste a cobrança em arquivo seguro no storage
        self::salvarCobrancaCache($txid, $cobrancaData);

        return [
            'sucesso'            => true,
            'txid'               => $txid,
            'valor'              => round($valor, 2),
            'valor_formatado'    => 'R$ ' . number_format($valor, 2, ',', '.'),
            'copia_e_cola'       => $payloadBrCode,
            'qr_code_svg'        => $qrCodeSvg,
            'qr_code_data_uri'   => $qrCodeDataUri,
            'expira_em_segundos' => $expiraEmSegundos,
            'expira_em'          => date('Y-m-d H:i:s', $expiraTimestamp),
        ];
    }

    /**
     * Consulta o status em tempo real de uma cobrança Pix
     * 
     * @param string $txid Identificador da cobrança
     * @return array Informações do status
     */
    public static function consultarStatus(string $txid): array
    {
        // 1. Verifica se já foi liquidada e registrada na tabela oficial transacoes_carteira
        $transacao = TransacaoCarteira::buscarPorReferencia($txid);
        if ($transacao) {
            return [
                'status'         => 'pago',
                'mensagem'       => 'Pagamento confirmado e creditado na carteira familiar!',
                'valor'          => (float)$transacao['valor'],
                'liquidado_em'   => $transacao['created_at'],
                'transacao_id'   => (int)$transacao['id']
            ];
        }

        // 2. Consulta o registro da cobrança em cache
        $cobranca = self::obterCobrancaCache($txid);
        if (!$cobranca) {
            return [
                'status'   => 'nao_encontrado',
                'mensagem' => 'Cobrança não localizada ou inexistente.'
            ];
        }

        // 3. Verifica expiração (15 minutos)
        if (time() > $cobranca['expira_em']) {
            $cobranca['status'] = 'expirado';
            self::salvarCobrancaCache($txid, $cobranca);

            return [
                'status'   => 'expirado',
                'mensagem' => 'O prazo de 15 minutos para pagamento deste Pix expirou.'
            ];
        }

        // 4. Cobrança continua pendente
        return [
            'status'             => 'pendente',
            'mensagem'           => 'Aguardando confirmação de pagamento...',
            'valor'              => (float)$cobranca['valor'],
            'restante_segundos'  => max(0, $cobranca['expira_em'] - time())
        ];
    }

    /**
     * Valida a autenticidade da requisição do Webhook bancário
     * 
     * @param array $headers Cabeçalhos HTTP da requisição
     * @param string $rawBody Corpo bruto da requisição
     * @param string|null $queryToken Token opcional passado via query string
     * @return bool True se autenticado, False caso contrário
     */
    public static function validarWebhook(array $headers, string $rawBody, ?string $queryToken = null): bool
    {
        $config = self::getConfig();
        $expectedSecret = $config['webhook_secret'] ?? '';

        // Se o segredo configurado for mock ou estiver vazio em ambiente de desenvolvimento local
        if (empty($expectedSecret) || $expectedSecret === 'SEU_WEBHOOK_SECRET' || ($config['provedor'] ?? '') === 'mock') {
            return true;
        }

        // 1. Checagem via Header X-Webhook-Secret ou X-Webhook-Token
        $receivedHeader = $headers['x-webhook-secret'] ?? $headers['x-webhook-token'] ?? $headers['authorization'] ?? null;
        if (!empty($receivedHeader)) {
            $receivedHeader = str_replace('Bearer ', '', $receivedHeader);
            if (hash_equals($expectedSecret, trim($receivedHeader))) {
                return true;
            }
        }

        // 2. Checagem via query param
        if (!empty($queryToken) && hash_equals($expectedSecret, trim($queryToken))) {
            return true;
        }

        // 3. Checagem via HMAC SHA-256 no header X-Signature caso o gateway suporte
        $signature = $headers['x-signature'] ?? null;
        if (!empty($signature)) {
            $expectedSignature = hash_hmac('sha256', $rawBody, $expectedSecret);
            if (hash_equals($expectedSignature, trim($signature))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extrai e normaliza os dados de pagamento do payload de Webhook
     * 
     * @param string $rawBody Corpo JSON da notificação
     * @return array|null Array com txid, valor, endToEndId ou null se inválido
     */
    public static function parseWebhookPayload(string $rawBody): ?array
    {
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            return null;
        }

        // Formato Padrão BACEN / Efí (pix array com txid e endToEndId)
        if (isset($data['pix'][0])) {
            $pixItem = $data['pix'][0];
            return [
                'txid'          => $pixItem['txid'] ?? '',
                'valor'         => (float)($pixItem['valor'] ?? 0.0),
                'end_to_end_id' => $pixItem['endToEndId'] ?? '',
                'horario'       => $pixItem['horario'] ?? date('Y-m-d H:i:s'),
                'status'        => 'CONCLUIDA'
            ];
        }

        // Formato Genérico Direto (ex: { "txid": "...", "valor": 50.00, "endToEndId": "..." })
        if (isset($data['txid'])) {
            return [
                'txid'          => (string)$data['txid'],
                'valor'         => (float)($data['valor'] ?? 0.0),
                'end_to_end_id' => (string)($data['endToEndId'] ?? $data['end_to_end_id'] ?? ('E' . date('YmdHis'))),
                'horario'       => $data['horario'] ?? date('Y-m-d H:i:s'),
                'status'        => strtoupper($data['status'] ?? 'CONCLUIDA')
            ];
        }

        return null;
    }

    /**
     * Localiza os dados de uma cobrança no cache temporário (disco com fallback em sessão)
     */
    public static function obterCobrancaCache(string $txid): ?array
    {
        $filename = self::getCacheDir() . '/pix_' . preg_replace('/[^A-Za-z0-9_-]/', '', $txid) . '.json';
        if (file_exists($filename)) {
            $content = @file_get_contents($filename);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // Fallback resiliente: recupera da sessão do usuário se disponível
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['_pix_cache'][$txid])) {
            return $_SESSION['_pix_cache'][$txid];
        }

        return null;
    }

    /**
     * Grava dados de cobrança no cache temporário (disco e sessão)
     */
    public static function salvarCobrancaCache(string $txid, array $data): bool
    {
        $dir = self::getCacheDir();
        $filename = $dir . '/pix_' . preg_replace('/[^A-Za-z0-9_-]/', '', $txid) . '.json';
        $saved = @file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT)) !== false;
        if ($saved) {
            @chmod($filename, 0666);
        }

        // Salva simultaneamente na sessão para resiliência imediata
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['_pix_cache'])) {
                $_SESSION['_pix_cache'] = [];
            }
            $_SESSION['_pix_cache'][$txid] = $data;
        }

        return $saved;
    }

    /**
     * Monta a string do padrão BR Code / EMVCo com todos os campos TLV e cálculo de CRC16
     */
    public static function montarPayloadBrCode(
        string $chavePix,
        string $nomeRecebedor,
        string $cidadeRecebedor,
        float $valor,
        string $txid,
        string $descricao = ''
    ): string {
        // Normalização de textos para o padrão EMV (sem acentos e caracteres especiais)
        $nomeLimpo = self::removerAcentos(strtoupper(substr($nomeRecebedor, 0, 25)));
        $cidadeLimpa = self::removerAcentos(strtoupper(substr($cidadeRecebedor, 0, 15)));
        $valorFmt = number_format($valor, 2, '.', '');

        // 1. Merchant Account Information (Tag 26)
        $gui = self::formatarTlv('00', 'BR.GOV.BCB.PIX');
        $chave = self::formatarTlv('01', $chavePix);
        $desc = !empty($descricao) ? self::formatarTlv('02', substr(self::removerAcentos($descricao), 0, 40)) : '';
        $merchantAccountInfo = self::formatarTlv('26', $gui . $chave . $desc);

        // 2. Additional Data Field Template (Tag 62)
        $referenceLabel = self::formatarTlv('05', $txid);
        $additionalDataField = self::formatarTlv('62', $referenceLabel);

        // 3. Montagem dos campos TLV raiz
        $payload = self::formatarTlv('00', '01') .                 // Payload Format Indicator
                   self::formatarTlv('01', '12') .                 // Point of Initiation Method (12 = Dinâmico)
                   $merchantAccountInfo .                          // Conta do comerciante
                   self::formatarTlv('52', '0000') .               // Merchant Category Code
                   self::formatarTlv('53', '986') .                // Moeda (986 = Real Brasileiro)
                   self::formatarTlv('54', $valorFmt) .            // Valor da transação
                   self::formatarTlv('58', 'BR') .                 // Código do País
                   self::formatarTlv('59', $nomeLimpo) .           // Nome do Comerciante
                   self::formatarTlv('60', $cidadeLimpa) .         // Cidade do Comerciante
                   $additionalDataField;                           // Dados adicionais (txid)

        // 4. Adiciona o identificador da tag CRC16 (Tag 63, tamanho 04)
        $payloadComCrcTag = $payload . '6304';

        // 5. Calcula o CRC16-CCITT (0xFFFF) e anexa ao final
        $crc16 = self::calcularCrc16($payloadComCrcTag);

        return $payloadComCrcTag . $crc16;
    }

    /**
     * Formata um campo no padrão TLV (Tag-Length-Value)
     */
    public static function formatarTlv(string $tag, string $value): string
    {
        $len = strlen($value);
        return sprintf('%s%02d%s', $tag, $len, $value);
    }

    /**
     * Calcula o CRC16-CCITT (polinômio 0x1021, valor inicial 0xFFFF)
     * conforme especificação oficial do Banco Central do Brasil para o Pix
     */
    public static function calcularCrc16(string $payload): string
    {
        $polinomio = 0x1021;
        $resultado = 0xFFFF;

        $length = strlen($payload);
        for ($i = 0; $i < $length; $i++) {
            $resultado ^= (ord($payload[$i]) << 8);
            for ($bit = 0; $bit < 8; $bit++) {
                if (($resultado & 0x8000) !== 0) {
                    $resultado = (($resultado << 1) ^ $polinomio) & 0xFFFF;
                } else {
                    $resultado = ($resultado << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Remove acentos e caracteres fora do alfabeto básico para compatibilidade EMV
     */
    public static function removerAcentos(string $str): string
    {
        $map = [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i',
            'ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ü'=>'u','ç'=>'c',
            'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','É'=>'E','Ê'=>'E','Í'=>'I',
            'Ó'=>'O','Ô'=>'O','Õ'=>'O','Ú'=>'U','Ü'=>'U','Ç'=>'C'
        ];
        return strtr($str, $map);
    }
}
