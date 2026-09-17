<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador do Portal da Família (Ambiente dos Responsáveis)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Responsavel;

class PortalController extends Controller
{
    /**
     * Painel inicial do Portal da Família
     */
    public function index(): void
    {
        $usuario = Auth::user();
        $respModel = new Responsavel();
        $dependentes = $respModel->getDependentes((int)$usuario['id']);

        // Atualiza saldo mais recente direto do banco
        $respDb = $respModel->find((int)$usuario['id']);
        $saldoAtual = (float)($respDb['saldo_atual'] ?? $usuario['saldo'] ?? 0.00);

        $this->render('portal/index', [
            'pageTitle'    => 'Portal da Família — Início',
            'usuario'      => $usuario,
            'saldoAtual'   => $saldoAtual,
            'dependentes'  => $dependentes,
            'bloqueadoMed' => (bool)($respDb['bloqueado_med'] ?? false)
        ], 'portal');
    }
}
