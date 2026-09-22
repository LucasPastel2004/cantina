<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador: CaixaController (Gestão de Sessões de Caixa e Prestação de Contas)
 * 
 * Conforme docs/FSD.md (Área 4, Área 6, Módulo 7) e docs/DESIGN.md
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Models\SessaoCaixa;
use App\Services\PdfService;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class CaixaController extends Controller
{
    /**
     * Tela Principal de Gestão de Caixa do Turno
     */
    public function index(): void
    {
        $turno = $_GET['turno'] ?? SessaoCaixa::inferirTurnoAtual();
        if (!in_array($turno, [SessaoCaixa::TURNO_MATUTINO, SessaoCaixa::TURNO_VESPERTINO], true)) {
            $turno = SessaoCaixa::TURNO_MATUTINO;
        }

        $sessaoAberta = SessaoCaixa::obterSessaoAberta($turno);
        $ultimaSessao = SessaoCaixa::obterUltimaSessao($turno);
        $totais = $sessaoAberta ? SessaoCaixa::obterTotaisEsperados($sessaoAberta) : null;
        $historico = SessaoCaixa::listarHistorico(15);

        $this->render('operacional/caixa', [
            'activeMenu'   => 'caixa',
            'turnoAtual'   => $turno,
            'sessaoAberta' => $sessaoAberta,
            'ultimaSessao' => $ultimaSessao,
            'totais'       => $totais,
            'historico'    => $historico,
            'pageTitle'    => 'Gestão de Caixa e Prestação de Contas'
        ], 'layouts/main');
    }

    /**
     * Abertura de Caixa do Turno
     */
    public function abrir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/operacional/caixa');
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrf)) {
            Session::setFlash('danger', 'Token CSRF inválido ou expirado. Tente novamente.');
            $this->redirect('/operacional/caixa');
            return;
        }

        $turno = trim($_POST['turno'] ?? 'matutino');
        $fundoRaw = $_POST['fundo_troco_inicial'] ?? '0';
        $fundoLimpo = str_replace('.', '', $fundoRaw);
        $fundoLimpo = str_replace(',', '.', $fundoLimpo);
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
     * Encerramento e Fechamento de Caixa com Conferência Física
     */
    public function fechar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/operacional/caixa');
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrf)) {
            Session::setFlash('danger', 'Token CSRF inválido ou expirado. Tente novamente.');
            $this->redirect('/operacional/caixa');
            return;
        }

        $sessaoId = (int)($_POST['sessao_id'] ?? 0);
        $informadoRaw = $_POST['total_informado_gaveta'] ?? '0';
        $informadoLimpo = str_replace('.', '', $informadoRaw);
        $informadoLimpo = str_replace(',', '.', $informadoLimpo);
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
}
