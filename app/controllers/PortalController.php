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
use App\Core\Session;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\RestricaoAlimentar;

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
        Session::set('_auth_saldo', $saldoAtual);

        $this->render('portal/index', [
            'pageTitle'       => 'Portal da Família — Início',
            'activeMenu'      => 'inicio',
            'usuario'         => $usuario,
            'user'            => $usuario,
            'responsavelNome' => $respDb['nome'] ?? $usuario['nome'] ?? 'Responsável',
            'saldoAtual'      => $saldoAtual,
            'dependentes'     => $dependentes,
            'bloqueadoMed'    => (bool)($respDb['bloqueado_med'] ?? false)
        ], 'portal');
    }

    /**
     * Tela de gestão de restrições alimentares / alergias dos dependentes (FSD Área 1 & Módulo 2.5)
     */
    public function alergias(): void
    {
        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];

        $respModel = new Responsavel();
        $respDb = $respModel->find($respId);
        $saldoAtual = (float)($respDb['saldo_atual'] ?? $usuario['saldo'] ?? 0.00);
        Session::set('_auth_saldo', $saldoAtual);

        $dependentes = Aluno::listarPorResponsavel($respId, false);

        if (empty($dependentes)) {
            Session::setFlash('info', 'Nenhum dependente vinculado à sua conta familiar no momento.');
            $this->redirect('/portal');
            return;
        }

        // Seleciona o aluno informado na URL ou o primeiro dependente
        $alunoIdSolicitado = (int)$this->input('aluno', 0);
        $alunoSelecionado  = null;

        if ($alunoIdSolicitado > 0) {
            foreach ($dependentes as $dep) {
                if ((int)$dep['id'] === $alunoIdSolicitado) {
                    $alunoSelecionado = $dep;
                    break;
                }
            }
        }

        if (!$alunoSelecionado) {
            $alunoSelecionado = $dependentes[0];
        }

        $catalogoAlergenos = RestricaoAlimentar::listarTodas();
        $alergenosDoAluno  = Aluno::obterIdsAlergenos((int)$alunoSelecionado['id']);

        $this->render('portal/alergias', [
            'pageTitle'         => 'Restrições Alimentares — Portal da Família',
            'pageHeading'       => 'Gestão Ativa de Restrições Alimentares',
            'activeMenu'        => 'alergias',
            'usuario'           => $usuario,
            'user'              => $usuario,
            'responsavelNome'   => $respDb['nome'] ?? $usuario['nome'] ?? 'Responsável',
            'saldoAtual'        => $saldoAtual,
            'dependentes'       => $dependentes,
            'alunoSelecionado'  => $alunoSelecionado,
            'catalogoAlergenos' => $catalogoAlergenos,
            'alergenosDoAluno'  => $alergenosDoAluno
        ], 'portal');
    }

    /**
     * Salva as restrições alimentares associadas ao dependente
     */
    public function alergiasSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/portal/alergias');
            return;
        }

        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];
        $alunoId = (int)$this->input('aluno_id', 0);

        // Validação estrita de titularidade do dependente (LGPD escolar)
        $aluno = Aluno::buscarPorId($alunoId);
        if (!$aluno || (int)$aluno['responsavel_id'] !== $respId) {
            Session::setFlash('danger', 'Aluno não localizado ou não pertence à sua conta familiar.');
            $this->redirect('/portal/alergias');
            return;
        }

        $alergenos = (array)($this->input('alergenos', []) ?: []);
        $alergenosIds = array_map('intval', $alergenos);

        RestricaoAlimentar::sincronizarAluno($alunoId, $alergenosIds);

        Session::setFlash('success', "Restrições alimentares de [{$aluno['nome']}] foram atualizadas com sucesso.");
        $this->redirect('/portal/alergias?aluno=' . $alunoId);
    }
}
