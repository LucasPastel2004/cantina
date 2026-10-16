<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador Operacional: Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16, Seção 11.18, Fluxos 6 e 7, RN-16, RN-17) e docs/DESIGN.md
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\RBAC;
use App\Models\Ticket;
use App\Services\PdfService;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class TicketsController extends Controller
{
    /**
     * Normaliza e valida data no formato YYYY-MM-DD
     */
    protected function normalizarData(?string $data, string $padrao): string
    {
        if (empty($data) || !is_string($data)) {
            return $padrao;
        }

        $d = trim($data);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            [$ano, $mes, $dia] = explode('-', $d);
            if (checkdate((int)$mes, (int)$dia, (int)$ano)) {
                return $d;
            }
        }

        return $padrao;
    }

    /**
     * Normaliza e valida o turno de consumo
     */
    protected function normalizarTurno(?string $turno): string
    {
        if (is_string($turno) && in_array(strtolower(trim($turno)), ['matutino', 'vespertino'], true)) {
            return strtolower(trim($turno));
        }

        $horaAtual = date('H:i');
        return ($horaAtual >= '11:00') ? 'vespertino' : 'matutino';
    }

    /**
     * Painel Principal de Gestão e Impressão de Tickets (/operacional/tickets)
     */
    public function index(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarData($_GET['data'] ?? null, $hoje);
        $turnoConsumo = $this->normalizarTurno($_GET['turno'] ?? null);
        $buscaTermo = trim($_GET['q'] ?? '');

        // Métricas consolidadas do turno
        $metricas = Ticket::obterMetricasTurno($dataConsumo, $turnoConsumo);

        // Listagem de tickets do turno com ordenação sequencial oficial
        $ticketsTurno = Ticket::listarPorTurnoEData($dataConsumo, $turnoConsumo);
        $turmasAgrupadas = Ticket::agruparPorTurma($ticketsTurno);

        // Consulta rápida para emissão de 2ª via
        $ticketsConsulta = Ticket::buscarParaConsulta($buscaTermo, $dataConsumo, $turnoConsumo, 30);

        $user = Auth::user();

        $this->render('operacional/tickets', [
            'title'           => 'Tickets Térmicos (Bobina 80mm)',
            'pageHeading'     => 'Gestão e Impressão de Tickets Térmicos',
            'activeMenu'      => 'tickets',
            'user'            => $user,
            'dataConsumo'     => $dataConsumo,
            'turnoConsumo'    => $turnoConsumo,
            'buscaTermo'      => $buscaTermo,
            'metricas'        => $metricas,
            'turmasAgrupadas' => $turmasAgrupadas,
            'ticketsConsulta' => $ticketsConsulta,
            'csrf_token'      => Session::generateCsrfToken(),
            'flashSuccess'    => Session::getFlash('success'),
            'flashError'      => Session::getFlash('error') ?: Session::getFlash('danger'),
            'flashWarning'    => Session::getFlash('warning'),
            'flashInfo'       => Session::getFlash('info'),
        ], 'admin');
    }

    /**
     * Normaliza e valida o formato de impressão ('a4' ou '80mm')
     */
    protected function normalizarFormato(?string $formato, string $padrao = 'a4'): string
    {
        if (is_string($formato) && in_array(strtolower(trim($formato)), ['a4', '80mm'], true)) {
            return strtolower(trim($formato));
        }
        return $padrao;
    }

    /**
     * Espelho de Impressão de Tickets do Turno (/operacional/tickets/lote)
     * Suporta ?formato=a4 (Folha A4 em grade) e ?formato=80mm (Bobina contínua)
     */
    public function lote(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarData($_GET['data'] ?? null, $hoje);
        $turnoConsumo = $this->normalizarTurno($_GET['turno'] ?? null);
        $formato = $this->normalizarFormato($_GET['formato'] ?? null, 'a4');

        $tickets = Ticket::listarPorTurnoEData($dataConsumo, $turnoConsumo);

        if (empty($tickets)) {
            Session::setFlash('warning', 'Nenhum ticket encontrado para impressão no turno selecionado.');
            $this->redirect('/operacional/tickets?data=' . urlencode($dataConsumo) . '&turno=' . urlencode($turnoConsumo));
            return;
        }

        // Marca automaticamente os tickets como impressos
        $ticketIds = array_column($tickets, 'ticket_id');
        Ticket::marcarComoImpresso($ticketIds);

        $baseUrl = '';
        $parametros = [
            'data'    => $dataConsumo,
            'turno'   => $turnoConsumo,
            'baseUrl' => $baseUrl,
            'formato' => $formato
        ];

        $html = ($formato === '80mm')
            ? PdfService::gerarEspelhoTicketsLoteHtml($tickets, $parametros)
            : PdfService::gerarEspelhoTicketsLoteA4Html($tickets, $parametros);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
        exit;
    }

    /**
     * Redireciona endpoint de PDF para a visualização unificada oficial de impressão e PDF (/operacional/tickets/lote/pdf)
     */
    public function lotePdf(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarData($_GET['data'] ?? null, $hoje);
        $turnoConsumo = $this->normalizarTurno($_GET['turno'] ?? null);
        $formato = $this->normalizarFormato($_GET['formato'] ?? null, 'a4');

        $this->redirect('/operacional/tickets/lote?formato=' . urlencode($formato) . '&data=' . urlencode($dataConsumo) . '&turno=' . urlencode($turnoConsumo));
    }

    /**
     * Impressão de Tickets de uma Turma Específica (/operacional/tickets/turma/{id})
     */
    public function turma(mixed $params): void
    {
        $turmaId = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarData($_GET['data'] ?? null, $hoje);
        $turnoConsumo = $this->normalizarTurno($_GET['turno'] ?? null);
        $formato = $this->normalizarFormato($_GET['formato'] ?? null, 'a4');

        $tickets = Ticket::listarPorTurnoEData($dataConsumo, $turnoConsumo, $turmaId > 0 ? $turmaId : null);

        if (empty($tickets)) {
            Session::setFlash('warning', 'Nenhum ticket encontrado para a turma selecionada.');
            $this->redirect('/operacional/tickets?data=' . urlencode($dataConsumo) . '&turno=' . urlencode($turnoConsumo));
            return;
        }

        // Marca como impressos
        $ticketIds = array_column($tickets, 'ticket_id');
        Ticket::marcarComoImpresso($ticketIds);

        $baseUrl = '';
        $parametros = [
            'data'     => $dataConsumo,
            'turno'    => $turnoConsumo,
            'turma_id' => $turmaId,
            'baseUrl'  => $baseUrl,
            'formato'  => $formato
        ];

        $html = ($formato === '80mm')
            ? PdfService::gerarEspelhoTicketsLoteHtml($tickets, $parametros)
            : PdfService::gerarEspelhoTicketsLoteA4Html($tickets, $parametros);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
        exit;
    }

    /**
     * Redireciona endpoint de PDF para a visualização unificada oficial de impressão e PDF (/operacional/tickets/turma/{id}/pdf)
     */
    public function turmaPdf(mixed $params): void
    {
        $turmaId = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarData($_GET['data'] ?? null, $hoje);
        $turnoConsumo = $this->normalizarTurno($_GET['turno'] ?? null);
        $formato = $this->normalizarFormato($_GET['formato'] ?? null, 'a4');

        $this->redirect('/operacional/tickets/turma/' . $turmaId . '?formato=' . urlencode($formato) . '&data=' . urlencode($dataConsumo) . '&turno=' . urlencode($turnoConsumo));
    }

    /**
     * Processamento de Emissão de 2ª Via com Justificativa Compulsória (POST /operacional/tickets/segunda-via)
     */
    public function segundaVia(): void
    {
        $csrf = $_POST['_token'] ?? $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrf)) {
            Session::setFlash('danger', 'Token de segurança CSRF inválido ou expirado. Tente novamente.');
            $this->redirect('/operacional/tickets');
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $justificativa = trim($_POST['justificativa'] ?? '');

        if ($ticketId <= 0) {
            Session::setFlash('danger', 'Ticket não informado para emissão de 2ª via.');
            $this->redirect('/operacional/tickets');
            return;
        }

        if (mb_strlen($justificativa) < 5) {
            Session::setFlash('danger', 'A justificativa para reemissão de 2ª via é obrigatória e deve conter ao menos 5 caracteres.');
            $this->redirect('/operacional/tickets');
            return;
        }

        $user = Auth::user();
        $operadorId = (int)($user['id'] ?? 1);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        try {
            $ticketAtualizado = Ticket::reimprimirSegundaVia($ticketId, $operadorId, $justificativa, $ip);
            Session::setFlash('success', sprintf('2ª Via emitida com sucesso (Via %d) para o aluno %s!', $ticketAtualizado['numero_via'], $ticketAtualizado['aluno_nome']));
            $this->redirect('/operacional/tickets/imprimir/' . $ticketId);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Falha ao emitir 2ª via: ' . $e->getMessage());
            $this->redirect('/operacional/tickets');
        }
    }

    /**
     * Visualização e Impressão de Ticket Individual / 2ª Via (/operacional/tickets/imprimir/{id})
     */
    public function imprimirTicket(mixed $params): void
    {
        $ticketId = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $ticket = Ticket::buscarPorId($ticketId);

        if (!$ticket) {
            Session::setFlash('danger', 'Ticket não localizado.');
            $this->redirect('/operacional/tickets');
            return;
        }

        $baseUrl = '';
        $html = PdfService::gerarEspelhoTicketIndividualHtml($ticket, [
            'baseUrl' => $baseUrl
        ]);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
        exit;
    }

    /**
     * Redireciona endpoint de PDF para a visualização unificada de impressão e PDF (/operacional/tickets/imprimir/{id}/pdf)
     */
    public function imprimirTicketPdf(mixed $params): void
    {
        $ticketId = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $this->redirect('/operacional/tickets/imprimir/' . $ticketId);
    }
}
