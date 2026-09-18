<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador do Portal da Família: Carteira Digital e Recargas Pix
 * 
 * Gerencia a visualização do saldo unificado familiar, geração de cobranças Pix
 * com QR Code dinâmico, polling em tempo real e extrato contábil analítico.
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\TransacaoCarteira;
use App\Models\Responsavel;
use App\Models\Configuracao;
use App\Services\PixService;
use App\Services\WhatsAppService;

class CarteiraController extends Controller
{
    /**
     * Painel da Carteira Digital Familiar e Extrato Analítico
     * Rota: GET /portal/carteira
     */
    public function index(): void
    {
        $user = Auth::user();
        $responsavelId = (int)$user['id'];

        // Sincroniza o saldo mais recente na sessão
        $saldoReal = Responsavel::consultarSaldo($responsavelId);
        Session::set('_auth_saldo', $saldoReal);

        // Resumo consolidado da carteira
        $resumo = TransacaoCarteira::obterResumoCarteira($responsavelId);

        // Dependentes vinculados
        $responsavelModel = new Responsavel();
        $dependentes = $responsavelModel->getDependentes($responsavelId);

        // Filtros de busca do extrato
        $filtros = [
            'aluno_id'       => !empty($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : null,
            'tipo_transacao' => !empty($_GET['tipo_transacao']) ? trim($_GET['tipo_transacao']) : null,
            'data_inicio'    => !empty($_GET['data_inicio']) ? trim($_GET['data_inicio']) : null,
            'data_fim'       => !empty($_GET['data_fim']) ? trim($_GET['data_fim']) : null,
        ];

        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 15; // Densidade corporativa calibrada

        $extrato = TransacaoCarteira::obterExtrato($responsavelId, $filtros, $pagina, $porPagina);

        $this->render('portal/carteira', [
            'title'           => 'Carteira Familiar & Extrato — Cantina Web',
            'activeMenu'      => 'carteira',
            'user'            => $user,
            'responsavelNome' => $user['name'] ?? $resumo['responsavel_nome'],
            'saldoAtual'      => $resumo['saldo_atual'],
            'resumo'          => $resumo,
            'dependentes'     => $dependentes,
            'extrato'         => $extrato,
            'filtros'         => $filtros,
            'pagina'          => $pagina,
        ], 'portal');
    }

    /**
     * Geração de Cobrança Pix Dinâmica com QR Code e Copia e Cola
     * Rota: POST /portal/recarga/gerar
     */
    public function gerarPix(): void
    {
        $user = Auth::user();
        $responsavelId = (int)$user['id'];

        // 1. Validação de Token CSRF
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            $this->responderErro('Token de segurança expirado. Por favor, atualize a página.', 403);
            return;
        }

        // 2. Extração e sanitização do valor monetário
        $valorBruto = $_POST['valor'] ?? '';
        $valorLimpo = $this->sanitizarValorMonetario($valorBruto);

        // 3. Validação de valor mínimo
        $configGlobal = Configuracao::obter();
        $recargaMinima = (float)($configGlobal['recarga_minima_pix'] ?? 10.00);

        if ($valorLimpo < $recargaMinima) {
            $this->responderErro(
                sprintf('O valor mínimo para recarga via Pix é de R$ %s.', number_format($recargaMinima, 2, ',', '.')),
                422
            );
            return;
        }

        if ($valorLimpo > 5000.00) {
            $this->responderErro('Por razões de segurança escolar, o valor máximo por recarga é de R$ 5.000,00.', 422);
            return;
        }

        try {
            $nomeTitular = $user['name'] ?? 'Titular';
            $descricao = sprintf('Recarga Cantina - %s', substr($nomeTitular, 0, 20));

            $cobranca = PixService::gerarCobrancaPix(
                $responsavelId,
                $valorLimpo,
                $descricao,
                [
                    'nome'     => $nomeTitular,
                    'whatsapp' => $user['whatsapp'] ?? '',
                ]
            );

            // Verifica se o ambiente está em modo de desenvolvimento local para habilitar atalho de simulação
            $configFile = __DIR__ . '/../../config/config.php';
            $config = file_exists($configFile) ? require $configFile : [];
            $isDev = ($config['app']['env'] ?? 'development') === 'development';

            $cobranca['is_dev'] = $isDev;

            $this->json([
                'sucesso'            => true,
                'txid'               => $cobranca['txid'],
                'valor'              => $cobranca['valor'],
                'valor_formatado'    => $cobranca['valor_formatado'],
                'copia_e_cola'       => $cobranca['copia_e_cola'],
                'qr_code_svg'        => $cobranca['qr_code_svg'],
                'qr_code_data_uri'   => $cobranca['qr_code_data_uri'],
                'expira_em_segundos' => $cobranca['expira_em_segundos'],
                'expira_em'          => $cobranca['expira_em'],
                'is_dev'             => $isDev,
            ], 200);
        } catch (\Throwable $e) {
            $this->responderErro('Falha ao gerar cobrança Pix: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Endpoint de polling assíncrono para verificar se a cobrança foi liquidada
     * Rota: GET /portal/recarga/status/{txid}
     */
    public function statusPix(array $params = []): void
    {
        $user = Auth::user();
        $responsavelId = (int)$user['id'];
        $txid = $params['txid'] ?? $_GET['txid'] ?? '';

        if (empty($txid) || !preg_match('/^[A-Za-z0-9_-]{10,50}$/', $txid)) {
            $this->json([
                'status'   => 'invalido',
                'mensagem' => 'Identificador de transação Pix inválido.'
            ], 400);
            return;
        }

        $status = PixService::consultarStatus($txid);

        // Se o status for 'pago', atualiza o saldo na sessão imediatamente
        if ($status['status'] === 'pago') {
            $novoSaldo = Responsavel::consultarSaldo($responsavelId);
            Session::set('_auth_saldo', $novoSaldo);
            $status['novo_saldo'] = $novoSaldo;
            $status['novo_saldo_fmt'] = 'R$ ' . number_format($novoSaldo, 2, ',', '.');
        }

        $this->json($status, 200);
    }

    /**
     * Simula a liquidação de um Pix para testes e homologação local
     * Rota: POST /portal/recarga/simular/{txid}
     */
    public function simularPagamento(array $params = []): void
    {
        // Apenas em ambiente local/desenvolvimento
        $configFile = __DIR__ . '/../../config/config.php';
        $config = file_exists($configFile) ? require $configFile : [];
        if (($config['app']['env'] ?? 'development') !== 'development' && ($config['integracoes']['pix']['provedor'] ?? '') !== 'mock') {
            $this->json(['sucesso' => false, 'erro' => 'Operação permitida apenas em ambiente de testes.'], 403);
            return;
        }

        $user = Auth::user();
        $responsavelId = (int)$user['id'];
        $txid = $params['txid'] ?? $_POST['txid'] ?? '';

        if (empty($txid)) {
            $this->json(['sucesso' => false, 'erro' => 'txid não informado.'], 400);
            return;
        }

        $cobranca = PixService::obterCobrancaCache($txid);
        if (!$cobranca) {
            $this->json(['sucesso' => false, 'erro' => 'Cobrança não localizada.'], 404);
            return;
        }

        try {
            // Liquida simulando o webhook do banco
            $transacao = TransacaoCarteira::creditar(
                $responsavelId,
                (float)$cobranca['valor'],
                TransacaoCarteira::TIPO_RECARGA_PIX,
                sprintf('Recarga Pix (Simulação Local) - Ref: %s', $txid),
                $txid
            );

            $cobranca['status'] = 'pago';
            $cobranca['liquidado_em'] = date('Y-m-d H:i:s');
            $cobranca['transacao_id'] = $transacao['id'];
            PixService::salvarCobrancaCache($txid, $cobranca);

            // Sincroniza sessão
            Session::set('_auth_saldo', (float)$transacao['saldo_resultante']);

            // Notifica mock WhatsApp
            $resp = Responsavel::fetchOne("SELECT nome, whatsapp FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $responsavelId]);
            if ($resp && !empty($resp['whatsapp'])) {
                (new WhatsAppService())->sendRecargaConfirmada(
                    $resp['whatsapp'],
                    (float)$cobranca['valor'],
                    (float)$transacao['saldo_resultante'],
                    $resp['nome'] ?? ''
                );
            }

            $this->json([
                'sucesso'          => true,
                'status'           => 'liquidado',
                'mensagem'         => 'Pagamento simulado com sucesso! Saldo creditado na carteira familiar.',
                'saldo_resultante' => $transacao['saldo_resultante'],
                'saldo_fmt'        => 'R$ ' . number_format($transacao['saldo_resultante'], 2, ',', '.')
            ], 200);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => $e->getMessage()], 500);
        }
    }

    /**
     * Sanitiza valores monetários com máscara (ex: "R$ 50,00" -> 50.00)
     */
    private function sanitizarValorMonetario(string $valor): float
    {
        $limpo = trim(str_replace(['R$', ' ', ' '], '', $valor));
        // Se contém vírgula, assume padrão brasileiro (pontos são milhar, vírgula é decimal)
        if (str_contains($limpo, ',')) {
            $limpo = str_replace('.', '', $limpo);
            $limpo = str_replace(',', '.', $limpo);
        }
        return round((float)$limpo, 2);
    }

    /**
     * Despacha resposta de erro estruturada em JSON ou Flash
     */
    private function responderErro(string $mensagem, int $statusCode = 400): void
    {
        if ($this->isJsonRequest()) {
            $this->json(['sucesso' => false, 'erro' => $mensagem], $statusCode);
        } else {
            Session::flash('error', $mensagem);
            $this->redirect('/portal/carteira');
        }
    }

    /**
     * Verifica se a requisição atual espera resposta JSON
     */
    private function isJsonRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xRequested = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json') ||
               strtolower($xRequested) === 'xmlhttprequest' ||
               str_contains($contentType, 'application/json');
    }
}
