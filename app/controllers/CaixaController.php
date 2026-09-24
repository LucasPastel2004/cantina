<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador: CaixaController (Gestão de Sessões de Caixa e Prestação de Contas)
 * 
 * Conforme docs/FSD.md (Área 4 / Tela 5.4, Área 6, Módulo 7) e docs/DESIGN.md
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Configuracao;
use App\Models\SessaoCaixa;
use App\Models\Responsavel;
use App\Services\PdfService;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class CaixaController extends Controller
{
    /**
     * Tela Principal de Gestão de Caixa do Turno e Prestação de Contas
     */
    public function index(): void
    {
        $turno = $_GET['turno'] ?? SessaoCaixa::inferirTurnoAtual();
        if (!in_array($turno, [SessaoCaixa::TURNO_MATUTINO, SessaoCaixa::TURNO_VESPERTINO], true)) {
            $turno = SessaoCaixa::TURNO_MATUTINO;
        }

        $dataInicio = !empty($_GET['data_inicio']) ? trim($_GET['data_inicio']) : null;
        $dataFim = !empty($_GET['data_fim']) ? trim($_GET['data_fim']) : null;
        $filtroTurno = !empty($_GET['filtro_turno']) ? trim($_GET['filtro_turno']) : null;

        $sessaoAberta = SessaoCaixa::obterSessaoAberta($turno);
        $ultimaSessao = SessaoCaixa::obterUltimaSessao($turno);
        $totais = $sessaoAberta ? SessaoCaixa::obterTotaisEsperados($sessaoAberta) : null;
        
        // Paginação do Histórico com base na configuração global
        $paginaAtual    = max(1, (int)($_GET['pagina'] ?? 1));
        $itensPorPagina = Configuracao::getItensPorPagina();
        $totalHistorico = SessaoCaixa::contarHistorico($filtroTurno, $dataInicio, $dataFim);
        $totalPaginas   = max(1, (int)ceil($totalHistorico / $itensPorPagina));
        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
        }
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        $historico = SessaoCaixa::listarHistorico($filtroTurno, $dataInicio, $dataFim, $itensPorPagina, $offset);

        $outroTurno = ($turno === SessaoCaixa::TURNO_MATUTINO) ? SessaoCaixa::TURNO_VESPERTINO : SessaoCaixa::TURNO_MATUTINO;
        $sessaoOutroTurno = SessaoCaixa::obterSessaoAberta($outroTurno);
        $horarioLimiteTurno = ($turno === SessaoCaixa::TURNO_MATUTINO) ? '11:00' : '16:00';
        $horarioAtual = date('H:i');
        $avisoAtrasoFechamento = ($sessaoAberta && $horarioAtual > $horarioLimiteTurno);

        $this->render('operacional/caixa', [
            'title'                 => 'Gestão de Caixa e Prestação de Contas — Cantina Web',
            'pageTitle'             => 'Gestão de Caixa e Prestação de Contas',
            'pageHeading'           => 'Gestão de Caixa e Prestação de Contas',
            'activeMenu'            => 'caixa',
            'turnoAtual'            => $turno,
            'turnoAtivo'            => ucfirst($turno),
            'statusCaixa'           => $sessaoAberta ? 'Aberto' : 'Fechado',
            'sessaoAberta'          => $sessaoAberta,
            'ultimaSessao'          => $ultimaSessao,
            'totais'                => $totais,
            'historico'             => $historico,
            'paginaAtual'           => $paginaAtual,
            'totalPaginas'          => $totalPaginas,
            'totalRegistros'        => $totalHistorico,
            'itensPorPagina'        => $itensPorPagina,
            'sessaoOutroTurno'      => $sessaoOutroTurno,
            'outroTurnoNome'        => ucfirst($outroTurno),
            'avisoAtrasoFechamento' => $avisoAtrasoFechamento,
            'horarioLimiteTurno'    => $horarioLimiteTurno,
            'dataInicioFiltro'      => $dataInicio,
            'dataFimFiltro'         => $dataFim,
            'filtroTurno'           => $filtroTurno
        ], 'admin');
    }

    /**
     * Tela dedicada de Abertura de Caixa
     * Conforme checklist da Fase 15 de docs/PLANO.md
     */
    public function abrirTela(): void
    {
        $turno = $_GET['turno'] ?? SessaoCaixa::inferirTurnoAtual();
        if (!in_array($turno, [SessaoCaixa::TURNO_MATUTINO, SessaoCaixa::TURNO_VESPERTINO], true)) {
            $turno = SessaoCaixa::TURNO_MATUTINO;
        }

        $sessaoAberta = SessaoCaixa::obterSessaoAberta($turno);
        if ($sessaoAberta) {
            Session::setFlash('info', "O caixa do turno " . ucfirst($turno) . " já está aberto (Sessão #{$sessaoAberta['id']}).");
            $this->redirect('/operacional/caixa?turno=' . urlencode($turno));
            return;
        }

        $this->render('operacional/abertura_caixa', [
            'title'       => 'Abertura de Caixa — Cantina Web',
            'pageTitle'   => 'Abertura de Caixa por Turno',
            'activeMenu'  => 'caixa',
            'turnoAtual'  => $turno,
            'turnoAtivo'  => ucfirst($turno)
        ], 'admin');
    }

    /**
     * Processa a Abertura de Caixa do Turno via POST
     */
    public function abrir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/operacional/caixa');
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrf($csrf, '/operacional/caixa')) {
            return;
        }

        $turno = trim($_POST['turno'] ?? 'matutino');
        $fundoRaw = trim((string)($_POST['fundo_troco_inicial'] ?? '0'));
        if (str_contains($fundoRaw, ',')) {
            $fundoLimpo = str_replace('.', '', $fundoRaw);
            $fundoLimpo = str_replace(',', '.', $fundoLimpo);
        } else {
            $fundoLimpo = $fundoRaw;
        }
        $fundoTroco = (float)$fundoLimpo;
        $observacoes = trim($_POST['observacoes'] ?? '');

        try {
            $operadorId = Auth::id() ?? 1;
            $sessaoId = SessaoCaixa::abrir($operadorId, $turno, $fundoTroco, $observacoes);

            Session::setFlash('success', "Caixa do turno " . ucfirst($turno) . " aberto com sucesso (Sessão #{$sessaoId})! Fundo inicial: R$ " . number_format($fundoTroco, 2, ',', '.'));
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Falha ao abrir o caixa: ' . $e->getMessage());
        }

        $this->redirect('/operacional/caixa?turno=' . urlencode($turno));
    }

    /**
     * Tela dedicada de Fechamento de Caixa
     * Conforme checklist da Fase 15 de docs/PLANO.md
     */
    public function fecharTela(): void
    {
        $turno = $_GET['turno'] ?? SessaoCaixa::inferirTurnoAtual();
        if (!in_array($turno, [SessaoCaixa::TURNO_MATUTINO, SessaoCaixa::TURNO_VESPERTINO], true)) {
            $turno = SessaoCaixa::TURNO_MATUTINO;
        }

        $sessaoAberta = SessaoCaixa::obterSessaoAberta($turno);
        if (!$sessaoAberta) {
            Session::setFlash('warning', "Não há sessão de caixa aberta para o turno " . ucfirst($turno) . ".");
            $this->redirect('/operacional/caixa?turno=' . urlencode($turno));
            return;
        }

        $totais = SessaoCaixa::obterTotaisEsperados($sessaoAberta);

        $this->render('operacional/fechamento_caixa', [
            'title'        => 'Fechamento de Caixa — Cantina Web',
            'pageTitle'    => 'Fechamento e Prestação de Contas',
            'activeMenu'   => 'caixa',
            'turnoAtual'   => $turno,
            'turnoAtivo'   => ucfirst($turno),
            'sessaoAberta' => $sessaoAberta,
            'totais'       => $totais
        ], 'admin');
    }

    /**
     * Processa o Encerramento e Fechamento de Caixa com Conferência Física
     */
    public function fechar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/operacional/caixa');
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrf($csrf, '/operacional/caixa')) {
            return;
        }

        $sessaoId = (int)($_POST['sessao_id'] ?? 0);
        $informadoRaw = trim((string)($_POST['total_informado_gaveta'] ?? '0'));
        if (str_contains($informadoRaw, ',')) {
            $informadoLimpo = str_replace('.', '', $informadoRaw);
            $informadoLimpo = str_replace(',', '.', $informadoLimpo);
        } else {
            $informadoLimpo = $informadoRaw;
        }
        $totalInformado = (float)$informadoLimpo;
        $observacoes = trim($_POST['observacoes'] ?? '');

        try {
            $operadorId = Auth::id() ?? 1;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $sessaoFechada = SessaoCaixa::fechar($sessaoId, $totalInformado, $observacoes, $operadorId, $ip);

            $diferenca = (float)($sessaoFechada['diferenca_quebra_sobra'] ?? 0.0);
            $difMsg = ($diferenca == 0.0) 
                ? 'Conferência exata (sem quebra ou sobra).' 
                : (($diferenca > 0) ? "Sobra apurada de R$ " . number_format($diferenca, 2, ',', '.') : "Quebra/Falta apurada de R$ " . number_format(abs($diferenca), 2, ',', '.'));

            Session::setFlash('success', "Sessão de caixa #{$sessaoId} encerrada com sucesso! {$difMsg}");
            $this->redirect('/operacional/caixa/espelho/' . $sessaoId);
            return;
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao encerrar sessão de caixa: ' . $e->getMessage());
            $this->redirect('/operacional/caixa');
        }
    }

    /**
     * Endpoint Ajax para busca de responsável por CPF ou nome do aluno
     * Conforme Tela 5.4 do docs/FSD.md
     */
    public function buscarResponsavel(): void
    {
        $termo = trim((string)($_GET['q'] ?? ($_GET['busca'] ?? '')));
        if (strlen($termo) < 2) {
            $this->json([
                'success' => true,
                'sucesso' => true,
                'responsaveis' => [],
                'dados' => []
            ]);
            return;
        }

        $responsaveis = SessaoCaixa::buscarResponsavelParaRecarga($termo, 10);
        $this->json([
            'success' => true,
            'sucesso' => true,
            'responsaveis' => $responsaveis,
            'dados' => $responsaveis
        ]);
    }

    /**
     * Processa a Recarga em Dinheiro no Balcão
     * Conforme Tela 5.4 e Área 6 do docs/FSD.md
     */
    public function recargaDinheiro(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/operacional/caixa');
            return;
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
               || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        $csrf = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrf($csrf, '/operacional/caixa')) {
            if ($isAjax) {
                $this->json([
                    'success'  => false,
                    'sucesso'  => false,
                    'message'  => 'Token CSRF inválido ou expirado.',
                    'mensagem' => 'Token CSRF inválido ou expirado.'
                ], 403);
            }
            return;
        }

        $responsavelId = (int)($_POST['responsavel_id'] ?? 0);
        $valorRaw = trim((string)($_POST['valor'] ?? '0'));
        if (str_contains($valorRaw, ',')) {
            $valorLimpo = str_replace('.', '', $valorRaw);
            $valorLimpo = str_replace(',', '.', $valorLimpo);
        } else {
            $valorLimpo = $valorRaw;
        }
        $valor = (float)$valorLimpo;
        $observacoes = trim((string)($_POST['observacoes'] ?? ''));

        if ($responsavelId <= 0) {
            $msg = 'Selecione um responsável válido para efetuar a recarga.';
            if ($isAjax) {
                $this->json([
                    'success'  => false,
                    'sucesso'  => false,
                    'message'  => $msg,
                    'mensagem' => $msg
                ], 400);
            } else {
                Session::setFlash('danger', $msg);
                $this->redirect('/operacional/caixa');
            }
            return;
        }

        if ($valor <= 0) {
            $msg = 'Informe um valor válido e positivo para a recarga em dinheiro.';
            if ($isAjax) {
                $this->json([
                    'success'  => false,
                    'sucesso'  => false,
                    'message'  => $msg,
                    'mensagem' => $msg
                ], 400);
            } else {
                Session::setFlash('danger', $msg);
                $this->redirect('/operacional/caixa');
            }
            return;
        }

        try {
            $operadorId = Auth::id() ?? 1;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $resultado = SessaoCaixa::registrarRecargaDinheiro($responsavelId, $valor, $operadorId, $observacoes, $ip);

            $msgSucesso = "Recarga em dinheiro de R$ " . number_format($valor, 2, ',', '.') . " realizada com sucesso para {$resultado['responsavel_nome']}! Novo saldo: R$ " . number_format($resultado['saldo_resultante'], 2, ',', '.');

            if ($isAjax) {
                $this->json([
                    'success'            => true,
                    'sucesso'            => true,
                    'message'            => $msgSucesso,
                    'mensagem'           => $msgSucesso,
                    'transacao_id'       => $resultado['transacao_id'],
                    'comprovante_url'    => '/operacional/caixa/comprovante-recarga/' . $resultado['transacao_id'],
                    'comprovante_pdf'    => '/operacional/caixa/comprovante-recarga/' . $resultado['transacao_id'] . '/pdf',
                    'responsavel_nome'   => $resultado['responsavel_nome'],
                    'valor_creditado'    => $resultado['valor'],
                    'novo_saldo'         => $resultado['saldo_resultante'],
                    'dados'              => $resultado
                ]);
            } else {
                Session::setFlash('success', $msgSucesso);
                $this->redirect('/operacional/caixa?recarga_concluida=' . $resultado['transacao_id']);
            }
        } catch (\Throwable $e) {
            if ($isAjax) {
                $this->json([
                    'success'  => false,
                    'sucesso'  => false,
                    'message'  => $e->getMessage(),
                    'mensagem' => $e->getMessage()
                ], 400);
            } else {
                Session::setFlash('danger', 'Erro ao registrar recarga em dinheiro: ' . $e->getMessage());
                $this->redirect('/operacional/caixa');
            }
        }
    }

    /**
     * Painel analítico e detalhamento de uma sessão de caixa específica
     */
    public function detalhes(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $dadosAnaliticos = SessaoCaixa::obterExtratoAnaliticoSessao($id);

        if (empty($dadosAnaliticos)) {
            Session::setFlash('danger', "Sessão de caixa #{$id} não localizada.");
            $this->redirect('/operacional/caixa');
            return;
        }

        $this->render('operacional/caixa_detalhes', [
            'title'       => "Extrato Analítico da Sessão #{$id} — Cantina Web",
            'pageTitle'   => "Extrato Analítico da Sessão #{$id}",
            'activeMenu'  => 'caixa',
            'sessao'      => $dadosAnaliticos['sessao'],
            'totais'      => $dadosAnaliticos['totais'],
            'vendas'      => $dadosAnaliticos['vendas'],
            'recargas'    => $dadosAnaliticos['recargas']
        ], 'admin');
    }

    /**
     * Espelho HTML formatado para impressão A4 do Fechamento de Caixa
     */
    public function espelhoHtml(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $sessao = SessaoCaixa::buscarPorId($id);

        if (!$sessao) {
            http_response_code(404);
            echo "Sessão de caixa #{$id} não encontrada.";
            exit;
        }

        echo PdfService::gerarEspelhoFechamentoCaixaHtml($sessao);
        exit;
    }

    /**
     * Emissão de PDF A4 timbrado do Fechamento de Caixa
     */
    public function espelhoPdf(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $sessao = SessaoCaixa::buscarPorId($id);

        if (!$sessao) {
            http_response_code(404);
            echo "Sessão de caixa #{$id} não encontrada.";
            exit;
        }

        $pdfBinary = PdfService::gerarEspelhoFechamentoCaixaPdf($sessao);
        $filename = "fechamento_caixa_{$id}_" . date('Ymd_His') . ".pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBinary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBinary;
        exit;
    }

    /**
     * Espelho HTML contínuo (80mm) de Comprovante Térmico de Recarga em Dinheiro
     */
    public function comprovanteRecargaHtml(array $params): void
    {
        $transacaoId = (int)($params['id'] ?? 0);
        $dados = SessaoCaixa::buscarDadosRecarga($transacaoId);

        if (!$dados) {
            http_response_code(404);
            echo "Recarga em dinheiro #{$transacaoId} não encontrada.";
            exit;
        }

        echo PdfService::gerarComprovanteRecargaDinheiroHtml($dados);
        exit;
    }

    /**
     * Emissão de PDF binário (80mm) de Comprovante Térmico de Recarga em Dinheiro
     */
    public function comprovanteRecargaPdf(array $params): void
    {
        $transacaoId = (int)($params['id'] ?? 0);
        $dados = SessaoCaixa::buscarDadosRecarga($transacaoId);

        if (!$dados) {
            http_response_code(404);
            echo "Recarga em dinheiro #{$transacaoId} não encontrada.";
            exit;
        }

        $pdfBinary = PdfService::gerarComprovanteRecargaDinheiroPdf($dados);
        $filename = "recarga_{$transacaoId}_" . date('Ymd_His') . ".pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdfBinary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfBinary;
        exit;
    }
}
