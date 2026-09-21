<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador Operacional: Pedidos, Corte de Turno e Fornecedores
 * 
 * Responsável pelo painel de corte do turno, consolidação matemática de salgados,
 * exportação de pedidos para WhatsApp e PDF A4 timbrado, conferência física de
 * mercadorias e liquidação de estoques com trilha de auditoria.
 * 
 * Conforme docs/FSD.md (Área 2, Telas 3.0, 3.1 e 3.2, Fluxo 5) e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Pedido;
use App\Services\PdfService;

class PedidosController extends Controller
{
    /**
     * Normaliza e valida a string de data (suporta YYYY-MM-DD e DD/MM/YYYY)
     */
    private function normalizarDataConsumo(?string $data, string $padrao): string
    {
        if (empty($data) || !is_string($data)) {
            return $padrao;
        }
        $data = trim($data);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $data, $m)) {
            $data = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            return $padrao;
        }
        return $data;
    }

    /**
     * Painel de Corte do Turno (/operacional/corte)
     * Exibe a consolidação quantitativa de salgados encomendados para o turno e data selecionados.
     */
    public function corte(): void
    {
        $hoje = date('Y-m-d');
        $horaAtual = date('H:i');

        // Determina data (padrão hoje)
        $dataConsumo = $this->normalizarDataConsumo($_GET['data'] ?? null, $hoje);

        // Determina turno (padrão matutino ou inferido pelo horário)
        $turnoPadrao = ($horaAtual >= '11:00') ? Pedido::TURNO_VESPERTINO : Pedido::TURNO_MATUTINO;
        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : $turnoPadrao;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        // Consolidação dos pedidos do corte
        $dadosCorte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo);

        // Texto estruturado para WhatsApp
        $textoWhatsApp = Pedido::obterTextoWhatsAppCorte(
            $dataConsumo, 
            $turnoConsumo, 
            $dadosCorte['itens']
        );

        $user = Auth::user();

        $this->render('operacional/corte', [
            'title'             => 'Painel de Corte do Turno',
            'activeMenu'        => 'corte',
            'user'              => $user,
            'dataConsumo'       => $dataConsumo,
            'turnoConsumo'      => $turnoConsumo,
            'dadosCorte'        => $dadosCorte,
            'textoWhatsApp'     => $textoWhatsApp,
            'csrf_token'        => Session::generateCsrfToken(),
            'flashSuccess'      => Session::getFlash('success'),
            'flashError'        => Session::getFlash('error') ?: Session::getFlash('danger'),
            'flashInfo'         => Session::getFlash('info'),
        ], 'admin');
    }

    /**
     * Emissão e download de relatório consolidado em PDF A4 timbrado (/operacional/corte/pdf)
     */
    public function cortePdf(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarDataConsumo($_GET['data'] ?? null, $hoje);

        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : Pedido::TURNO_MATUTINO;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        $dadosCorte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo);
        $pdfContent = PdfService::gerarRelatorioCortePdf($dadosCorte);

        $nomeArquivo = sprintf("pedido_fornecedor_%s_%s.pdf", $dataConsumo, $turnoConsumo);

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
            header('Content-Length: ' . strlen($pdfContent));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }

        echo $pdfContent;
        exit;
    }

    /**
     * Visualização limpa para impressão A4 no navegador (/operacional/corte/imprimir)
     */
    public function corteImprimir(): void
    {
        $hoje = date('Y-m-d');
        $dataConsumo = $this->normalizarDataConsumo($_GET['data'] ?? null, $hoje);

        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : Pedido::TURNO_MATUTINO;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        $dadosCorte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo);
        $html = PdfService::gerarHtmlRelatorioCorte($dadosCorte);

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo $html;
        exit;
    }

    /**
     * Tela de Conferência e Recebimento de Mercadorias (/operacional/recebimento)
     */
    public function recebimento(): void
    {
        $hoje = date('Y-m-d');
        $horaAtual = date('H:i');

        $dataConsumo = $this->normalizarDataConsumo($_GET['data'] ?? null, $hoje);

        $turnoPadrao = ($horaAtual >= '11:00') ? Pedido::TURNO_VESPERTINO : Pedido::TURNO_MATUTINO;
        $turnoConsumo = (isset($_GET['turno']) && is_string($_GET['turno'])) ? trim($_GET['turno']) : $turnoPadrao;
        if (!in_array($turnoConsumo, [Pedido::TURNO_MATUTINO, Pedido::TURNO_VESPERTINO], true)) {
            $turnoConsumo = Pedido::TURNO_MATUTINO;
        }

        $dadosCorte = Pedido::consolidarCorte($dataConsumo, $turnoConsumo);
        $statusRecebimento = Pedido::verificarStatusRecebimento($dataConsumo, $turnoConsumo);

        $user = Auth::user();

        $this->render('operacional/recebimento', [
            'title'             => 'Conferência e Recebimento de Fornecedor',
            'activeMenu'        => 'recebimento',
            'user'              => $user,
            'dataConsumo'       => $dataConsumo,
            'turnoConsumo'      => $turnoConsumo,
            'dadosCorte'        => $dadosCorte,
            'statusRecebimento' => $statusRecebimento,
            'csrf_token'        => Session::generateCsrfToken(),
            'flashSuccess'      => Session::getFlash('success'),
            'flashError'        => Session::getFlash('error') ?: Session::getFlash('danger'),
            'flashInfo'         => Session::getFlash('info'),
        ], 'admin');
    }

    /**
     * Processa a confirmação de recebimento físico e liquidação de demandas (POST /operacional/recebimento/confirmar)
     */
    public function recebimentoConfirmar(): void
    {
        // Validação de CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('error', 'Token de segurança expirado. Atualize a página e tente novamente.');
            $this->redirect('/operacional/recebimento');
            return;
        }

        $dataConsumo = $this->normalizarDataConsumo($_POST['data_consumo'] ?? null, '');
        $turnoConsumo = trim($_POST['turno_consumo'] ?? '');
        $observacao = trim($_POST['observacao'] ?? '');

        if (empty($dataConsumo) || !in_array($turnoConsumo, ['matutino', 'vespertino'], true)) {
            Session::setFlash('error', 'Data ou turno inválidos informados para o recebimento.');
            $this->redirect('/operacional/recebimento');
            return;
        }

        $user = Auth::user();
        $operadorId = (int)($user['id'] ?? 1);
        $ipOrigem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // Coleta os itens entregues
        $itensPost = $_POST['itens'] ?? [];
        $itensFormatados = [];

        if (is_array($itensPost)) {
            foreach ($itensPost as $item) {
                $pId = (int)($item['produto_id'] ?? 0);
                $qtdEsp = (int)($item['qtd_esperada'] ?? 0);
                $qtdEnt = (int)($item['qtd_entregue'] ?? 0);

                if ($pId > 0) {
                    $itensFormatados[] = [
                        'produto_id'   => $pId,
                        'qtd_esperada' => max(0, $qtdEsp),
                        'qtd_entregue' => max(0, $qtdEnt),
                    ];
                }
            }
        }

        if (empty($itensFormatados)) {
            Session::setFlash('error', 'Nenhum item válido informado para conferência de entrega.');
            $this->redirect("/operacional/recebimento?data={$dataConsumo}&turno={$turnoConsumo}");
            return;
        }

        try {
            $resultado = Pedido::confirmarRecebimentoFornecedor(
                $dataConsumo,
                $turnoConsumo,
                $operadorId,
                $itensFormatados,
                $observacao,
                $ipOrigem
            );

            Session::setFlash('success', $resultado['mensagem']);
            $this->redirect("/operacional/recebimento?data={$dataConsumo}&turno={$turnoConsumo}");
        } catch (\DomainException $e) {
            Session::setFlash('error', $e->getMessage());
            $this->redirect("/operacional/recebimento?data={$dataConsumo}&turno={$turnoConsumo}");
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Ocorreu um erro interno ao processar a confirmação de recebimento: ' . $e->getMessage());
            $this->redirect("/operacional/recebimento?data={$dataConsumo}&turno={$turnoConsumo}");
        }
    }
}
