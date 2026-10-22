<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controller: DespesasController
 * 
 * Gerenciamento administrativo de despesas operacionais da cantina (CRUD, filtros, liquidações).
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/FSD.md, docs/DESIGN.md e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Core\RBAC;
use App\Models\DespesaOperacional;
use App\Models\Configuracao;

class DespesasController extends Controller
{
    private DespesaOperacional $model;

    public function __construct()
    {
        RBAC::requireRole(RBAC::ROLE_ADMINISTRADOR);
        $this->model = new DespesaOperacional();
    }

    /**
     * Tela principal de gestão e listagem de despesas operacionais.
     */
    public function index(): void
    {
        $mesAtual = date('Y-m');
        $competencia = isset($_GET['competencia']) && is_string($_GET['competencia']) ? trim($_GET['competencia']) : $mesAtual;
        $categoria = isset($_GET['categoria']) && is_string($_GET['categoria']) ? trim($_GET['categoria']) : '';
        $status = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
        $busca = isset($_GET['busca']) && is_string($_GET['busca']) ? trim($_GET['busca']) : '';
        $filtrar = (bool)($this->input('filtrar', false) || isset($_GET['filtrar']));
        $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $limite = 20;
        $offset = ($pagina - 1) * $limite;

        // Determina se houve interação com os filtros para carga sob demanda
        $jaFiltrado = $filtrar || !empty($categoria) || !empty($status) || !empty($busca) || isset($_GET['pagina']);

        $filtros = [
            'competencia' => $competencia !== 'todas' ? $competencia : '',
            'categoria'   => $categoria,
            'status'      => $status,
            'busca'       => $busca,
        ];

        if ($jaFiltrado) {
            $despesas = $this->model->listar($filtros, $limite, $offset);
            $totalRegistros = $this->model->contar($filtros);
            $totalPaginas = max(1, (int)ceil($totalRegistros / $limite));
        } else {
            $despesas = [];
            $totalRegistros = 0;
            $totalPaginas = 1;
        }

        $resumoCompetencia = $this->model->obterResumoCompetencia($competencia !== 'todas' ? $competencia : $mesAtual);
        $competenciasDisponiveis = $this->model->obterCompetenciasDisponiveis();

        $this->render('financeiro/despesas', [
            'title'                   => 'Despesas Operacionais — Colégio Monteiro Lobato',
            'activeMenu'              => 'despesas',
            'jaFiltrado'              => $jaFiltrado,
            'despesas'                => $despesas,
            'totalRegistros'          => $totalRegistros,
            'paginaAtual'             => $pagina,
            'totalPaginas'            => $totalPaginas,
            'filtros'                 => $filtros,
            'competenciaAtual'        => $competencia,
            'resumo'                  => $resumoCompetencia,
            'competenciasDisponiveis' => $competenciasDisponiveis,
            'categorias'              => DespesaOperacional::getCategorias(),
            'statusList'              => DespesaOperacional::getStatusList(),
            'csrf_token'              => Session::getCsrfToken(),
            'csrf_field'              => Session::csrfField(),
        ], 'admin');
    }

    /**
     * Gravação de nova despesa operacional via POST.
     */
    public function salvar(): void
    {
        $this->validarCsrf();

        try {
            $dados = $this->sanitizarDadosFormulario();
            $usuario = Auth::user();
            $usuarioId = $usuario['id'] ?? null;

            $this->model->criar($dados, $usuarioId);
            Session::setFlash('success', 'Despesa operacional cadastrada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('danger', $e->getMessage());
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Ocorreu um erro interno ao salvar a despesa: ' . $e->getMessage());
        }

        $competencia = $_POST['competencia_mes_ano'] ?? date('Y-m');
        $this->redirect('/financeiro/despesas?filtrar=1&competencia=' . urlencode($competencia));
    }

    /**
     * Retorna dados de uma despesa em JSON para preenchimento do modal de edição via AJAX.
     */
    public function editarJson(array $params = []): void
    {
        $id = (int)($params['id'] ?? ($_GET['id'] ?? 0));
        $despesa = $this->model->buscarPorId($id);

        if (!$despesa) {
            $this->json(['sucesso' => false, 'mensagem' => 'Despesa não localizada.'], 404);
            return;
        }

        $this->json([
            'sucesso' => true,
            'despesa' => $despesa,
        ]);
    }

    /**
     * Atualização de despesa operacional existente via POST.
     */
    public function atualizar(): void
    {
        $this->validarCsrf();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Session::setFlash('danger', 'Identificador de despesa inválido.');
            $this->redirect('/financeiro/despesas');
            return;
        }

        try {
            $dados = $this->sanitizarDadosFormulario();
            $usuario = Auth::user();
            $usuarioId = $usuario['id'] ?? null;

            $this->model->atualizar($id, $dados, $usuarioId);
            Session::setFlash('success', 'Despesa operacional atualizada com sucesso!');
        } catch (\InvalidArgumentException $e) {
            Session::setFlash('danger', $e->getMessage());
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Ocorreu um erro interno ao atualizar a despesa: ' . $e->getMessage());
        }

        $competencia = $_POST['competencia_mes_ano'] ?? date('Y-m');
        $this->redirect('/financeiro/despesas?filtrar=1&competencia=' . urlencode($competencia));
    }

    /**
     * Ação rápida para marcar uma despesa como paga.
     */
    public function pagar(): void
    {
        $this->validarCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $dataPagamento = !empty($_POST['data_pagamento']) ? trim($_POST['data_pagamento']) : date('Y-m-d');
        $formaPagamento = !empty($_POST['forma_pagamento']) ? trim($_POST['forma_pagamento']) : null;
        $competencia = !empty($_POST['competencia_redirect']) ? trim($_POST['competencia_redirect']) : date('Y-m');

        if ($id <= 0) {
            Session::setFlash('danger', 'Identificador de despesa inválido.');
            $this->redirect('/financeiro/despesas');
            return;
        }

        $usuario = Auth::user();
        $usuarioId = $usuario['id'] ?? null;

        $sucesso = $this->model->marcarComoPaga($id, $dataPagamento, $formaPagamento, $usuarioId);
        if ($sucesso) {
            Session::setFlash('success', 'Despesa marcada como PAGA com sucesso!');
        } else {
            Session::setFlash('danger', 'Não foi possível liquidar a despesa.');
        }

        $this->redirect('/financeiro/despesas?filtrar=1&competencia=' . urlencode($competencia));
    }

    /**
     * Exclusão lógica (soft delete) de despesa operacional via POST.
     */
    public function excluir(): void
    {
        $this->validarCsrf();

        $id = (int)($_POST['id'] ?? 0);
        $competencia = !empty($_POST['competencia_redirect']) ? trim($_POST['competencia_redirect']) : date('Y-m');

        if ($id <= 0) {
            Session::setFlash('danger', 'Identificador de despesa inválido.');
            $this->redirect('/financeiro/despesas');
            return;
        }

        $usuario = Auth::user();
        $usuarioId = $usuario['id'] ?? null;

        $sucesso = $this->model->excluir($id, $usuarioId);
        if ($sucesso) {
            Session::setFlash('success', 'Despesa operacional removida com sucesso!');
        } else {
            Session::setFlash('danger', 'Não foi possível remover a despesa.');
        }

        $this->redirect('/financeiro/despesas?filtrar=1&competencia=' . urlencode($competencia));
    }

    /**
     * Sanitiza os dados recebidos do formulário de despesa.
     */
    private function sanitizarDadosFormulario(): array
    {
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $subcategoria = trim($_POST['subcategoria'] ?? '');
        $competencia = trim($_POST['competencia_mes_ano'] ?? date('Y-m'));
        $dataVencimento = trim($_POST['data_vencimento'] ?? '');
        $dataPagamento = trim($_POST['data_pagamento'] ?? '');
        $status = trim($_POST['status'] ?? DespesaOperacional::STATUS_PENDENTE);
        $formaPagamento = trim($_POST['forma_pagamento'] ?? '');
        $documentoReferencia = trim($_POST['documento_referencia'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Trata valor em reais (formato brasileiro 1.234,56 ou 1234.56)
        $valorRaw = str_replace(['R$', ' ', '.'], '', $_POST['valor'] ?? '0');
        $valorRaw = str_replace(',', '.', $valorRaw);
        $valor = (float)$valorRaw;

        return [
            'descricao'            => $descricao,
            'categoria'            => $categoria,
            'subcategoria'         => $subcategoria,
            'valor'                => $valor,
            'competencia_mes_ano'  => $competencia,
            'data_vencimento'      => $dataVencimento,
            'data_pagamento'       => !empty($dataPagamento) ? $dataPagamento : null,
            'status'               => $status,
            'forma_pagamento'      => $formaPagamento,
            'documento_referencia' => $documentoReferencia,
            'observacoes'          => $observacoes,
        ];
    }

    /**
     * Valida o token CSRF obrigatório.
     */
    private function validarCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('danger', 'Token de segurança expirado. Por favor, reenvie o formulário.');
            $this->redirect('/financeiro/despesas');
            exit;
        }
    }
}
