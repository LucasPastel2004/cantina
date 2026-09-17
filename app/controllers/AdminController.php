<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador da Retaguarda e Administração da Cantina
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Usuario;

class AdminController extends Controller
{
    /**
     * Painel inicial operacional e administrativo da cantina
     */
    public function index(): void
    {
        $usuario = Auth::user();

        $this->render('admin/index', [
            'pageTitle' => 'Painel da Cantina — Visão Geral',
            'usuario'   => $usuario
        ], 'admin');
    }
}
