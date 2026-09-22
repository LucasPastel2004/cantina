<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador: PdvController (Ponto de Venda e Operação de Balcão)
 * 
 * Conforme docs/FSD.md (Área 4, Módulo 5) e docs/DESIGN.md
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\VendaBalcao;
use App\Models\SessaoCaixa;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\Colaborador;
use App\Models\Aluno;
use App\Models\Ticket;
use App\Services\PixService;
use App\Services\PdfService;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class PdvController extends Controller
{
    /**
     * Tela principal do Ponto de Venda (PDV)
     */
    public function index(): void
    {
        $turnoAtual = SessaoCaixa::inferirTurnoAtual();
        $sessaoAberta = SessaoCaixa::obterSessaoAberta($turnoAtual);

        // Se não houver sessão aberta para o turno atual, tenta qualquer sessão aberta hoje
        if (!$sessaoAberta) {
            $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino') ?? SessaoCaixa::obterSessaoAberta('vespertino');
        }

        $categorias = Categoria::listarTodas(true);
        $produtos = Produto::listarParaPdv();
        $sessaoId = $sessaoAberta ? (int)$sessaoAberta['id'] : null;

        $vendasRecentes = VendaBalcao::listarRecentes(10, $sessaoId);
        $metricas = VendaBalcao::obterMetricasHoje($sessaoId);

        $this->render('operacional/pdv', [
            'activeMenu'     => 'pdv',
            'sessaoAberta'   => $sessaoAberta,
            'turnoAtual'     => $turnoAtual,
            'categorias'     => $categorias,
            'produtos'       => $produtos,
            'vendasRecentes' => $vendasRecentes,
            'metricas'       => $metricas,
            'title'          => 'Ponto de Venda (PDV) — Cantina Web'
        ], 'admin');
    }

    /**
     * Abre uma sessão de caixa rápida diretamente no terminal de PDV
     */
    public function abrirCaixaRapido(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('danger', 'Token de segurança inválido. Tente novamente.');
            $this->redirect('/operacional/pdv');
            return;
        }

        $turno = trim($_POST['turno'] ?? SessaoCaixa::inferirTurnoAtual());
        $fundoTroco = (float)str_replace(',', '.', preg_replace('/[^\d,.]/', '', $_POST['fundo_troco_inicial'] ?? '0'));
        $operadorId = (int)Auth::id();

        try {
            $sessaoId = SessaoCaixa::abrir($operadorId, $turno, $fundoTroco, 'Abertura rápida no terminal de PDV');
            Session::setFlash('success', "Caixa do turno " . ucfirst($turno) . " aberto com sucesso! Fundo de troco: R$ " . number_format($fundoTroco, 2, ',', '.'));
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao abrir caixa: ' . $e->getMessage());
        }

        $this->redirect('/operacional/pdv');
    }

    /**
     * Finaliza atomicamente uma venda no PDV (Dinheiro, Pix Balcão ou Desconto em Folha)
     */
    public function finalizarVenda(): void
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            $msg = 'Sessão expirada ou token de segurança inválido.';
            if ($isAjax) {
                $this->json(['sucesso' => false, 'mensagem' => $msg], 403);
                return;
            }
            Session::setFlash('danger', $msg);
            $this->redirect('/operacional/pdv');
            return;
        }

        $sessaoCaixaId = (int)($_POST['sessao_caixa_id'] ?? 0);
        $formaPagamento = trim($_POST['forma_pagamento'] ?? 'dinheiro');
        $tipoVenda = trim($_POST['tipo_venda'] ?? 'avulso_aluno');
        $colaboradorId = !empty($_POST['colaborador_id']) ? (int)$_POST['colaborador_id'] : null;
        $alunoId = !empty($_POST['aluno_id']) ? (int)$_POST['aluno_id'] : null;
        $confirmacaoVisual = (int)($_POST['confirmacao_visual'] ?? 0);
        $operadorId = (int)Auth::id();

        // Tratamento de itens (podem vir via JSON string ou array POST)
        $itensBrutos = $_POST['itens'] ?? '[]';
        if (is_string($itensBrutos)) {
            $itens = json_decode($itensBrutos, true) ?? [];
        } else {
            $itens = (array)$itensBrutos;
        }

        if (empty($itens)) {
            $msg = 'Adicione ao menos um item na cesta antes de finalizar.';
            if ($isAjax) {
                $this->json(['sucesso' => false, 'mensagem' => $msg], 400);
                return;
            }
            Session::setFlash('warning', $msg);
            $this->redirect('/operacional/pdv');
            return;
        }

        // Validação estrita de colaborador
        if ($formaPagamento === VendaBalcao::FORMA_DESCONTO_FOLHA) {
            $tipoVenda = VendaBalcao::TIPO_DESCONTO_FOLHA;
            if (!$colaboradorId) {
                $msg = 'Para vendas sob Desconto em Folha, é obrigatório selecionar o colaborador.';
                if ($isAjax) {
                    $this->json(['sucesso' => false, 'mensagem' => $msg], 400);
                    return;
                }
                Session::setFlash('danger', $msg);
                $this->redirect('/operacional/pdv');
                return;
            }

            if ($confirmacaoVisual !== 1) {
                $msg = 'É obrigatório realizar a confirmação visual da foto do colaborador antes de lançar em folha.';
                if ($isAjax) {
                    $this->json(['sucesso' => false, 'mensagem' => $msg], 400);
                    return;
                }
                Session::setFlash('danger', $msg);
                $this->redirect('/operacional/pdv');
                return;
            }
        }

        $valorRecebido = 0.0;
        if ($formaPagamento === VendaBalcao::FORMA_DINHEIRO) {
            $valRecRaw = $_POST['valor_dinheiro_recebido'] ?? '0';
            $valorRecebido = (float)str_replace(',', '.', preg_replace('/[^\d,.]/', '', $valRecRaw));
        }

        try {
            $venda = VendaBalcao::criarVenda([
                'tipo_venda'              => $tipoVenda,
                'forma_pagamento'         => $formaPagamento,
                'aluno_id'                => $alunoId,
                'colaborador_id'          => $colaboradorId,
                'valor_dinheiro_recebido' => $valorRecebido
            ], $itens, $operadorId, $sessaoCaixaId);

            $msgSucesso = sprintf("Venda #%d concluída com sucesso! Total: R$ %s", $venda['id'], number_format((float)$venda['valor_total'], 2, ',', '.'));

            if ($isAjax) {
                $this->json([
                    'sucesso'   => true,
                    'mensagem'  => $msgSucesso,
                    'venda_id'  => $venda['id'],
                    'total'     => $venda['valor_total'],
                    'troco'     => $venda['troco_devolvido'] ?? 0.0,
                    'venda'     => $venda
                ]);
                return;
            }

            Session::setFlash('success', $msgSucesso);
            $this->redirect('/operacional/pdv');
        } catch (\Throwable $e) {
            if ($isAjax) {
                $this->json(['sucesso' => false, 'mensagem' => $e->getMessage()], 400);
                return;
            }
            Session::setFlash('danger', 'Falha ao processar venda: ' . $e->getMessage());
            $this->redirect('/operacional/pdv');
        }
    }

    /**
     * Gera cobrança Pix dinâmica para pagamento no balcão
     */
    public function pixGerar(): void
    {
        $valorRaw = $_POST['valor'] ?? '0';
        $valor = (float)str_replace(',', '.', preg_replace('/[^\d,.]/', '', $valorRaw));

        if ($valor <= 0) {
            $this->json(['sucesso' => false, 'mensagem' => 'Valor da cobrança deve ser maior que zero.'], 400);
            return;
        }

        $txid = 'PDV' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $descricao = 'Cantina Monteiro Lobato - Venda Balcão PDV';

        try {
            $cobranca = PixService::gerarCobranca($valor, $txid, $descricao, 900);
            $qrCodeUri = PixService::gerarQrCodeDataUri($cobranca['payload_brcode']);

            $this->json([
                'sucesso'          => true,
                'txid'             => $cobranca['txid'],
                'brcode'           => $cobranca['payload_brcode'],
                'qr_code_uri'      => $qrCodeUri,
                'valor'            => $valor,
                'valor_formatado'  => number_format($valor, 2, ',', '.')
            ]);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'mensagem' => 'Erro ao gerar cobrança Pix: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Consulta status do Pix dinâmico do balcão
     */
    public function pixStatus(mixed $params): void
    {
        $txid = is_array($params) ? ($params['txid'] ?? '') : (string)$params;
        $status = PixService::consultarStatusCobranca($txid);

        $this->json([
            'sucesso' => true,
            'txid'    => $txid,
            'status'  => $status
        ]);
    }

    /**
     * Busca rápida de colaboradores por matrícula ou nome com dados de foto e cargo
     */
    public function buscarColaborador(): void
    {
        $termo = (isset($_GET['busca']) && is_string($_GET['busca'])) ? trim(strip_tags($_GET['busca'])) : '';
        $colaboradores = Colaborador::buscarParaPdv($termo, 8);

        $this->json([
            'sucesso' => true,
            'dados'   => $colaboradores
        ]);
    }

    /**
     * Busca rápida de alunos para verificação preventiva de alérgenos no PDV
     */
    public function buscarAluno(): void
    {
        $termo = (isset($_GET['busca']) && is_string($_GET['busca'])) ? trim(strip_tags($_GET['busca'])) : '';
        if (empty($termo)) {
            $this->json(['sucesso' => true, 'dados' => []]);
            return;
        }

        $alunos = Aluno::listarComTurma([
            'busca'  => $termo,
            'status' => 'ativo'
        ], 8);

        $this->json([
            'sucesso' => true,
            'dados'   => $alunos
        ]);
    }

    /**
     * Processa o cancelamento / estorno de ticket no balcão com crédito imediato à família
     */
    public function estornoBalcao(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('danger', 'Token de segurança inválido.');
            $this->redirect('/operacional/pdv');
            return;
        }

        $codigoTicket = trim($_POST['codigo_ticket'] ?? '');
        $motivo = trim($_POST['motivo'] ?? '');
        $justificativa = trim($_POST['justificativa'] ?? '');
        $operadorId = (int)Auth::id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (empty($codigoTicket)) {
            Session::setFlash('warning', 'Informe o código do ticket para estorno.');
            $this->redirect('/operacional/pdv');
            return;
        }

        $ticket = Ticket::buscarPorCodigo($codigoTicket);
        if (!$ticket) {
            Session::setFlash('danger', "Ticket com código '{$codigoTicket}' não foi localizado.");
            $this->redirect('/operacional/pdv');
            return;
        }

        try {
            $resultado = Ticket::estornarNoBalcao((int)$ticket['ticket_id'], $operadorId, $motivo, $justificativa, $ip);
            $valorDevolvido = number_format((float)($resultado['transacao_estorno']['valor'] ?? 0.0), 2, ',', '.');
            Session::setFlash('success', "Ticket {$codigoTicket} estornado com sucesso! Valor de R$ {$valorDevolvido} devolvido imediatamente à carteira da família.");
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Falha ao estornar ticket: ' . $e->getMessage());
        }

        $this->redirect('/operacional/pdv');
    }

    /**
     * Lista lanches de alunos ausentes do turno corrente para aproveitamento de sobras
     */
    public function sobras(): void
    {
        $turno = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : SessaoCaixa::inferirTurnoAtual();
        $data = (isset($_GET['data']) && is_string($_GET['data'])) ? trim($_GET['data']) : date('Y-m-d');

        $sobras = Ticket::listarNaoRetiradosTurno($data, $turno);

        $this->json([
            'sucesso' => true,
            'data'    => $data,
            'turno'   => $turno,
            'dados'   => $sobras
        ]);
    }

    /**
     * Transfere as sobras de lanches não retirados para a venda avulsa do PDV
     */
    public function transferirSobras(): void
    {
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrfToken)) {
            Session::setFlash('danger', 'Token de segurança inválido.');
            $this->redirect('/operacional/pdv');
            return;
        }

        $ticketIds = $_POST['ticket_ids'] ?? [];
        if (empty($ticketIds) || !is_array($ticketIds)) {
            Session::setFlash('warning', 'Selecione ao menos um ticket de sobra para transferir.');
            $this->redirect('/operacional/pdv');
            return;
        }

        $operadorId = (int)Auth::id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            $resultado = Ticket::transferirSobrasNaoRetiradasParaBalcao($ticketIds, $operadorId, $ip);
            Session::setFlash('success', "{$resultado['total_pecas_transferidas']} itens de sobra transferidos com sucesso para a venda avulsa no PDV!");
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao transferir sobras: ' . $e->getMessage());
        }

        $this->redirect('/operacional/pdv');
    }

    /**
     * Espelho HTML de impressão do comprovante térmico de 80mm
     */
    public function comprovanteHtml(mixed $params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $venda = VendaBalcao::buscarPorId($id);

        if (!$venda) {
            http_response_code(404);
            echo "Venda #{$id} não localizada.";
            return;
        }

        echo PdfService::gerarEspelhoComprovanteVendaBalcaoHtml($venda);
    }

    /**
     * Emissão em PDF binário do comprovante térmico de 80mm
     */
    public function comprovantePdf(mixed $params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $venda = VendaBalcao::buscarPorId($id);

        if (!$venda) {
            http_response_code(404);
            echo "Venda #{$id} não localizada.";
            return;
        }

        $pdfBin = PdfService::gerarComprovanteVendaBalcaoPdf($venda);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="comprovante_venda_' . $id . '.pdf"');
        header('Content-Length: ' . strlen($pdfBin));
        echo $pdfBin;
    }
}
