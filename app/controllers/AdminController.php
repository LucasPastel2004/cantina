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
use App\Core\Session;
use App\Models\Configuracao;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\RestricaoAlimentar;
use App\Models\Colaborador;
use App\Models\Turma;
use App\Models\Usuario;
use App\Models\Aluno;
use App\Models\Responsavel;
use App\Models\TransacaoCarteira;
use App\Models\CalendarioEscolar;
use App\Services\SpreadsheetService;
use App\Services\PdfService;
use App\Services\WhatsAppService;

class AdminController extends Controller
{
    /**
     * Painel inicial operacional e administrativo da cantina
     */
    public function index(): void
    {
        $usuario = Auth::user();

        $this->render('admin/index', [
            'pageTitle'   => 'Painel da Cantina — Visão Geral',
            'pageHeading' => 'Painel da Cantina',
            'activeMenu'  => 'visao_geral',
            'usuario'     => $usuario,
            'user'        => $usuario
        ], 'admin');
    }

    // =========================================================================
    // CONFIGURAÇÕES GLOBAIS
    // =========================================================================

    /**
     * Tela de visualização e edição de configurações globais da cantina
     */
    public function configuracoes(): void
    {
        $configuracoes = Configuracao::obter();
        $usuario = Auth::user();

        $this->render('admin/configuracoes', [
            'pageTitle'     => 'Configurações Globais',
            'pageHeading'   => 'Configurações Globais e Parâmetros',
            'activeMenu'    => 'configuracoes',
            'configuracoes' => $configuracoes,
            'usuario'       => $usuario,
            'user'          => $usuario
        ], 'admin');
    }

    /**
     * Salva alterações nas configurações globais da cantina
     */
    public function configuracoesSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/configuracoes');
            return;
        }

        $razaoSocial           = trim((string)$this->input('razao_social', ''));
        $nomeFantasia          = trim((string)$this->input('nome_fantasia', ''));
        $cnpj                  = trim((string)$this->input('cnpj', ''));
        $telefoneContato       = trim((string)$this->input('telefone_contato', ''));
        $recargaMinimaPix      = (float)str_replace(',', '.', (string)$this->input('recarga_minima_pix', '10.00'));
        $alertaSaldoBaixo      = (float)str_replace(',', '.', (string)$this->input('alerta_saldo_baixo', '15.00'));
        $timeoutSessaoMinutos  = (int)$this->input('timeout_sessao_minutos', 60);
        $limiteTentativasLogin = (int)$this->input('limite_tentativas_login', 5);
        $itensPorPagina        = (int)$this->input('itens_por_pagina', 25);

        if (empty($razaoSocial) || empty($nomeFantasia) || empty($cnpj)) {
            Session::setFlash('danger', 'Razão Social, Nome Fantasia e CNPJ são campos obrigatórios.');
            $this->redirect('/admin/configuracoes');
            return;
        }

        if ($recargaMinimaPix < 1.00) {
            Session::setFlash('danger', 'O valor mínimo de recarga Pix deve ser de pelo menos R$ 1,00.');
            $this->redirect('/admin/configuracoes');
            return;
        }

        $dados = [
            'razao_social'            => $razaoSocial,
            'nome_fantasia'           => $nomeFantasia,
            'cnpj'                    => $cnpj,
            'telefone_contato'        => $telefoneContato,
            'recarga_minima_pix'      => $recargaMinimaPix,
            'alerta_saldo_baixo'      => $alertaSaldoBaixo,
            'timeout_sessao_minutos'  => max(5, $timeoutSessaoMinutos),
            'limite_tentativas_login' => max(3, $limiteTentativasLogin),
            'itens_por_pagina'        => max(10, $itensPorPagina)
        ];

        // Processa upload de novo logotipo caso tenha sido enviado
        $novoLogo = $this->uploadImagem('logo', 'logo');
        if ($novoLogo !== null) {
            $dados['logo_path'] = $novoLogo;
        }

        Configuracao::salvar($dados);

        Session::setFlash('success', 'Configurações institucionais atualizadas com sucesso.');
        $this->redirect('/admin/configuracoes');
    }

    // =========================================================================
    // CATEGORIAS DE CARDÁPIO
    // =========================================================================

    /**
     * Listagem e gestão de categorias
     */
    public function categorias(): void
    {
        $categorias = Categoria::listarTodas(false);
        $usuario = Auth::user();

        $this->render('admin/categorias', [
            'pageTitle'   => 'Categorias de Cardápio',
            'pageHeading' => 'Categorias de Alimentos',
            'activeMenu'  => 'produtos',
            'categorias'  => $categorias,
            'usuario'     => $usuario,
            'user'        => $usuario
        ], 'admin');
    }

    /**
     * Criação ou atualização de categoria
     */
    public function categoriasSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/categorias');
            return;
        }

        $id            = (int)$this->input('id', 0);
        $nome          = trim((string)$this->input('nome', ''));
        $ordemExibicao = (int)$this->input('ordem_exibicao', 0);
        $ativo         = (int)$this->input('ativo', 1);

        if (empty($nome)) {
            Session::setFlash('danger', 'O nome da categoria é obrigatório.');
            $this->redirect('/admin/categorias');
            return;
        }

        $usuarioId = Auth::id();

        if ($id > 0) {
            Categoria::atualizar($id, [
                'nome'           => $nome,
                'ordem_exibicao' => $ordemExibicao,
                'ativo'          => $ativo
            ], $usuarioId);
            Session::setFlash('success', "Categoria [{$nome}] atualizada com sucesso.");
        } else {
            Categoria::criar([
                'nome'           => $nome,
                'ordem_exibicao' => $ordemExibicao,
                'ativo'          => $ativo
            ], $usuarioId);
            Session::setFlash('success', "Categoria [{$nome}] cadastrada com sucesso.");
        }

        $this->redirect('/admin/categorias');
    }

    /**
     * Exclusão lógica (soft delete) de categoria
     */
    public function categoriasExcluir(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/categorias');
            return;
        }

        $id = (int)$this->input('id', 0);
        $categoria = Categoria::buscarPorId($id);

        if (!$categoria) {
            Session::setFlash('danger', 'Categoria não encontrada.');
            $this->redirect('/admin/categorias');
            return;
        }

        $totalProdutos = Categoria::contarProdutosVinculados($id);
        if ($totalProdutos > 0) {
            Session::setFlash('warning', "A categoria [{$categoria['nome']}] possui {$totalProdutos} produto(s) vinculado(s) e não pode ser excluída. Inative-a ou desvincule os produtos antes.");
            $this->redirect('/admin/categorias');
            return;
        }

        Categoria::excluir($id, Auth::id());
        Session::setFlash('success', "Categoria [{$categoria['nome']}] removida com sucesso (exclusão lógica).");
        $this->redirect('/admin/categorias');
    }

    // =========================================================================
    // PRODUTOS E CARDÁPIO
    // =========================================================================

    /**
     * Listagem geral de produtos com filtros e ações rápidas
     */
    public function produtos(): void
    {
        $filtros = [
            'categoria_id' => $this->input('categoria_id'),
            'tipo'         => $this->input('tipo'),
            'turno'        => $this->input('turno'),
            'status_pausa' => $this->input('status_pausa'),
            'busca'        => $this->input('busca')
        ];

        $paginaAtual    = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();
        $totalProdutos  = Produto::contarTodos($filtros);
        $totalPaginas   = max(1, (int)ceil($totalProdutos / $itensPorPagina));
        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
        }
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        $produtos   = Produto::listarTodos($filtros, $itensPorPagina, $offset);
        $categorias = Categoria::listarTodas(true);
        $alergenos  = RestricaoAlimentar::listarTodas();
        $usuario    = Auth::user();

        $this->render('admin/produtos', [
            'pageTitle'      => 'Cardápio e Produtos',
            'pageHeading'    => 'Gestão de Cardápio e Produtos',
            'activeMenu'     => 'produtos',
            'produtos'       => $produtos,
            'categorias'     => $categorias,
            'alergenos'      => $alergenos,
            'filtros'        => $filtros,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalProdutos,
            'itensPorPagina' => $itensPorPagina,
            'usuario'        => $usuario,
            'user'           => $usuario
        ], 'admin');
    }

    /**
     * Criação ou atualização de produto
     */
    public function produtosSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/produtos');
            return;
        }

        $id                   = (int)$this->input('id', 0);
        $categoriaId          = (int)$this->input('categoria_id', 0);
        $codigoInterno        = strtoupper(trim((string)$this->input('codigo_interno', '')));
        $descricao            = trim((string)$this->input('descricao', ''));
        $precoVendaRaw        = (string)$this->input('preco_venda', '0,00');
        $tipo                 = (string)$this->input('tipo', 'salgado_diario');
        $turnoDisponibilidade = (string)$this->input('turno_disponibilidade', 'ambos');
        $estoqueAtual         = (int)$this->input('estoque_atual', 0);
        $estoqueMinimo        = (int)$this->input('estoque_minimo', 5);
        $statusPausa             = (string)$this->input('status_pausa', 'ativo');
        $fracionadoSalgadoGrande = !empty($this->input('fracionado_salgado_grande')) ? 1 : 0;
        $alergenosIds            = $_POST['alergenos'] ?? [];

        // Converte formato monetário 'R$ 8,50' ou '8,50' para float 8.50
        $precoLimpo = preg_replace('/[^\d,.]/', '', $precoVendaRaw);
        $precoLimpo = str_replace(',', '.', $precoLimpo);
        $precoVenda = (float)$precoLimpo;

        if (empty($codigoInterno) || empty($descricao) || $categoriaId <= 0) {
            Session::setFlash('danger', 'Código interno, descrição e categoria são campos obrigatórios.');
            $this->redirect('/admin/produtos');
            return;
        }

        // Verifica duplicidade de código interno
        $existente = Produto::buscarPorCodigo($codigoInterno, $id > 0 ? $id : null);
        if ($existente) {
            Session::setFlash('danger', "O código de produto [{$codigoInterno}] já está em uso pelo item [{$existente['descricao']}].");
            $this->redirect('/admin/produtos');
            return;
        }

        $dados = [
            'categoria_id'              => $categoriaId,
            'codigo_interno'            => $codigoInterno,
            'descricao'                 => $descricao,
            'preco_venda'               => max(0.01, $precoVenda),
            'tipo'                      => in_array($tipo, ['salgado_diario', 'industrializado_continuo']) ? $tipo : 'salgado_diario',
            'fracionado_salgado_grande' => $fracionadoSalgadoGrande,
            'turno_disponibilidade'     => in_array($turnoDisponibilidade, ['matutino', 'vespertino', 'ambos']) ? $turnoDisponibilidade : 'ambos',
            'estoque_atual'             => max(0, $estoqueAtual),
            'estoque_minimo'            => max(0, $estoqueMinimo),
            'status_pausa'              => ($statusPausa === 'pausado') ? 'pausado' : 'ativo'
        ];

        // Processa upload de imagem opcional do produto
        $fotoUrl = $this->uploadImagem('foto', 'prod');
        if ($fotoUrl !== null) {
            $dados['foto_url'] = $fotoUrl;
        }

        $usuarioId = Auth::id();

        if ($id > 0) {
            Produto::atualizar($id, $dados, is_array($alergenosIds) ? $alergenosIds : [], $usuarioId);
            Session::setFlash('success', "Produto [{$descricao}] atualizado com sucesso.");
        } else {
            Produto::criar($dados, is_array($alergenosIds) ? $alergenosIds : [], $usuarioId);
            Session::setFlash('success', "Produto [{$descricao}] cadastrado com sucesso no cardápio.");
        }

        $this->redirect('/admin/produtos');
    }

    /**
     * Alterna rapidamente o status de pausa ("ativo" <-> "pausado")
     */
    public function produtosTogglePausa(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
            }
            Session::setFlash('danger', 'Validação de segurança expirou.');
            $this->redirect('/admin/produtos');
            return;
        }

        $id = (int)$this->input('id', 0);
        $novoStatus = Produto::togglePausa($id, Auth::id());

        if (!$novoStatus) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Produto não encontrado.'], 404);
            }
            Session::setFlash('danger', 'Produto não encontrado.');
            $this->redirect('/admin/produtos');
            return;
        }

        if ($this->isAjax()) {
            $this->json([
                'success'     => true,
                'novo_status' => $novoStatus,
                'label'       => ucfirst($novoStatus)
            ]);
        }

        $msg = ($novoStatus === 'ativo') ? 'Produto ativado para vendas.' : 'Produto pausado temporariamente no cardápio.';
        Session::setFlash('info', $msg);
        $this->redirect('/admin/produtos');
    }

    /**
     * Exclusão lógica de produto
     */
    public function produtosExcluir(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/produtos');
            return;
        }

        $id = (int)$this->input('id', 0);
        $produto = Produto::buscarPorId($id);

        if (!$produto) {
            Session::setFlash('danger', 'Produto não encontrado.');
            $this->redirect('/admin/produtos');
            return;
        }

        Produto::excluir($id, Auth::id());
        Session::setFlash('success', "Produto [{$produto['descricao']}] inativado com sucesso (exclusão lógica preservada).");
        $this->redirect('/admin/produtos');
    }

    // =========================================================================
    // COLABORADORES (PROFESSORES E FUNCIONÁRIOS)
    // =========================================================================

    /**
     * Listagem e gestão de colaboradores
     */
    public function colaboradores(): void
    {
        $busca = (string)$this->input('busca', '');
        $filtrar = (bool)($this->input('filtrar', false) || isset($_GET['filtrar']));
        $jaFiltrado = $filtrar || !empty($busca) || isset($_GET['pagina']);

        $paginaAtual    = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        if ($jaFiltrado) {
            $totalColabs  = Colaborador::contarTodos(false, $busca);
            $totalPaginas = max(1, (int)ceil($totalColabs / $itensPorPagina));
            if ($paginaAtual > $totalPaginas) {
                $paginaAtual = $totalPaginas;
            }
            $offset = ($paginaAtual - 1) * $itensPorPagina;
            $colaboradores = Colaborador::listarTodos(false, $busca, $itensPorPagina, $offset);
        } else {
            $totalColabs  = 0;
            $totalPaginas = 1;
            $colaboradores = [];
        }

        $usuario = Auth::user();

        $this->render('admin/colaboradores', [
            'pageTitle'      => 'Colaboradores Escolares',
            'pageHeading'    => 'Colaboradores e Professores',
            'activeMenu'     => 'colaboradores',
            'colaboradores'  => $colaboradores,
            'busca'          => $busca,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalColabs,
            'itensPorPagina' => $itensPorPagina,
            'jaFiltrado'     => $jaFiltrado,
            'usuario'        => $usuario,
            'user'           => $usuario
        ], 'admin');
    }

    /**
     * Criação ou atualização de colaborador
     */
    public function colaboradoresSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/colaboradores');
            return;
        }

        $id                 = (int)$this->input('id', 0);
        $matriculaFuncional = strtoupper(trim((string)$this->input('matricula_funcional', '')));
        $nome               = trim((string)$this->input('nome', ''));
        $cargo              = trim((string)$this->input('cargo', ''));
        $ativo              = (int)$this->input('ativo', 1);

        if (empty($matriculaFuncional) || empty($nome) || empty($cargo)) {
            Session::setFlash('danger', 'Matrícula funcional, nome completo e cargo são obrigatórios.');
            $this->redirect('/admin/colaboradores');
            return;
        }

        // Valida unicidade da matrícula funcional
        $existente = Colaborador::buscarPorMatricula($matriculaFuncional, $id > 0 ? $id : null);
        if ($existente) {
            Session::setFlash('danger', "A matrícula funcional [{$matriculaFuncional}] já está cadastrada para o colaborador [{$existente['nome']}].");
            $this->redirect('/admin/colaboradores');
            return;
        }

        $dados = [
            'matricula_funcional' => $matriculaFuncional,
            'nome'                => $nome,
            'cargo'               => $cargo,
            'ativo'               => $ativo
        ];

        // Processa upload de foto de identificação para o PDV
        $fotoUrl = $this->uploadImagem('foto', 'colab');
        if ($fotoUrl !== null) {
            $dados['foto_url'] = $fotoUrl;
        }

        $usuarioId = Auth::id();

        if ($id > 0) {
            Colaborador::atualizar($id, $dados, $usuarioId);
            Session::setFlash('success', "Colaborador [{$nome}] atualizado com sucesso.");
        } else {
            Colaborador::criar($dados, $usuarioId);
            Session::setFlash('success', "Colaborador [{$nome}] cadastrado com sucesso.");
        }

        $this->redirect('/admin/colaboradores?filtrar=1');
    }

    /**
     * Alterna o status ativo de um colaborador
     */
    public function colaboradoresToggleAtivo(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou.');
            $this->redirect('/admin/colaboradores');
            return;
        }

        $id = (int)$this->input('id', 0);
        $novoStatus = Colaborador::toggleAtivo($id, Auth::id());

        if ($novoStatus === null) {
            Session::setFlash('danger', 'Colaborador não encontrado.');
        } else {
            $msg = $novoStatus ? 'Colaborador ativado com sucesso.' : 'Colaborador inativado com sucesso.';
            Session::setFlash('info', $msg);
        }

        $this->redirect('/admin/colaboradores?filtrar=1');
    }

    /**
     * Exclusão lógica de colaborador
     */
    public function colaboradoresExcluir(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/colaboradores');
            return;
        }

        $id = (int)$this->input('id', 0);
        $colaborador = Colaborador::buscarPorId($id);

        if (!$colaborador) {
            Session::setFlash('danger', 'Colaborador não encontrado.');
            $this->redirect('/admin/colaboradores');
            return;
        }

        Colaborador::excluir($id, Auth::id());
        Session::setFlash('success', "Colaborador [{$colaborador['nome']}] removido com sucesso (histórico preservado).");
        $this->redirect('/admin/colaboradores?filtrar=1');
    }

    // =========================================================================
    // TURMAS ESCOLARES
    // =========================================================================

    /**
     * Listagem e gestão de turmas
     */
    public function turmas(): void
    {
        $turno = (string)$this->input('turno', '');
        $busca = (string)$this->input('busca', '');
        $filtrar = (bool)($this->input('filtrar', false) || isset($_GET['filtrar']));
        $jaFiltrado = $filtrar || !empty($busca) || !empty($turno) || isset($_GET['pagina']);

        $paginaAtual    = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        if ($jaFiltrado) {
            $totalTurmas  = Turma::contarTodas($turno, $busca);
            $totalPaginas = max(1, (int)ceil($totalTurmas / $itensPorPagina));
            if ($paginaAtual > $totalPaginas) {
                $paginaAtual = $totalPaginas;
            }
            $offset = ($paginaAtual - 1) * $itensPorPagina;
            $turmas = Turma::listarTodas($turno, $busca, $itensPorPagina, $offset);
        } else {
            $totalTurmas  = 0;
            $totalPaginas = 1;
            $turmas       = [];
        }

        $usuario = Auth::user();

        $this->render('admin/turmas', [
            'pageTitle'      => 'Turmas Escolares',
            'pageHeading'    => 'Turmas e Agrupamentos Escolares',
            'activeMenu'     => 'turmas',
            'turmas'         => $turmas,
            'turno'          => $turno,
            'busca'          => $busca,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalTurmas,
            'itensPorPagina' => $itensPorPagina,
            'jaFiltrado'     => $jaFiltrado,
            'usuario'        => $usuario,
            'user'           => $usuario
        ], 'admin');
    }

    /**
     * Criação ou atualização de turma
     */
    public function turmasSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/turmas');
            return;
        }

        $id             = (int)$this->input('id', 0);
        $serie          = trim((string)$this->input('serie', ''));
        $nomeTurma      = trim((string)$this->input('nome_turma', ''));
        $turno          = (string)$this->input('turno', 'matutino');
        $ordemImpressao = (int)$this->input('ordem_impressao', 0);

        if (empty($serie) || empty($nomeTurma)) {
            Session::setFlash('danger', 'Série e Nome da Turma são campos obrigatórios.');
            $this->redirect('/admin/turmas');
            return;
        }

        $dados = [
            'serie'           => $serie,
            'nome_turma'      => $nomeTurma,
            'turno'           => in_array($turno, ['matutino', 'vespertino']) ? $turno : 'matutino',
            'ordem_impressao' => max(0, $ordemImpressao)
        ];

        $usuarioId = Auth::id();

        if ($id > 0) {
            Turma::atualizar($id, $dados, $usuarioId);
            Session::setFlash('success', "Turma [{$nomeTurma}] atualizada com sucesso.");
        } else {
            Turma::criar($dados, $usuarioId);
            Session::setFlash('success', "Turma [{$nomeTurma}] cadastrada com sucesso.");
        }

        $this->redirect('/admin/turmas?filtrar=1');
    }

    /**
     * Exclusão lógica de turma
     */
    public function turmasExcluir(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/turmas');
            return;
        }

        $id = (int)$this->input('id', 0);
        $turma = Turma::buscarPorId($id);

        if (!$turma) {
            Session::setFlash('danger', 'Turma não encontrada.');
            $this->redirect('/admin/turmas');
            return;
        }

        $totalAlunos = Turma::contarAlunosVinculados($id);
        if ($totalAlunos > 0) {
            Session::setFlash('warning', "A turma [{$turma['nome_turma']}] possui {$totalAlunos} aluno(s) vinculado(s) e não pode ser excluída. Remaneje os alunos antes.");
            $this->redirect('/admin/turmas');
            return;
        }

        Turma::excluir($id, Auth::id());
        Session::setFlash('success', "Turma [{$turma['nome_turma']}] removida com sucesso (histórico preservado).");
        $this->redirect('/admin/turmas?filtrar=1');
    }

    // =========================================================================
    // IMPORTAÇÃO ESCOLAR EM LOTE (Fase 8 — FSD Área 7 & Módulo 9.1)
    // =========================================================================

    /**
     * Tela inicial de importação escolar em lote
     */
    public function importacaoEscolar(): void
    {
        $usuario = Auth::user();
        $previa  = Session::get('_import_previa', null);
        $arquivoTemp = Session::get('_import_temp_file', null);

        // Se não houver arquivo físico correspondente ao temp, limpa a prévia
        if ($arquivoTemp && !file_exists($arquivoTemp)) {
            Session::remove('_import_previa');
            Session::remove('_import_temp_file');
            $previa = null;
        }

        $this->render('admin/importacao_escolar', [
            'pageTitle'   => 'Importação Escolar em Lote',
            'pageHeading' => 'Carga e Atualização de Alunos e Turmas',
            'activeMenu'  => 'importacao',
            'usuario'     => $usuario,
            'user'        => $usuario,
            'previa'      => $previa
        ], 'admin');
    }

    /**
     * Processa upload e executa a conferência prévia (dry-run)
     */
    public function importacaoEscolarAnalisar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        if (!isset($_FILES['planilha']) || !is_array($_FILES['planilha']) || $_FILES['planilha']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('danger', 'Selecione um arquivo CSV válido para realizar a importação.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        $file = $_FILES['planilha'];

        // Limite de 10 MB
        if ($file['size'] > 10 * 1024 * 1024) {
            Session::setFlash('danger', 'O arquivo excede o limite máximo permitido de 10 MB.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            Session::setFlash('danger', 'Formato de arquivo inválido. A importação suporta planilhas em formato CSV.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        // Salva temporariamente em storage/uploads/private/
        $privDir = dirname(__DIR__, 2) . '/storage/uploads/private';
        if (!is_dir($privDir)) {
            mkdir($privDir, 0750, true);
        }

        $tempNome = 'temp_previa_' . uniqid() . '_' . bin2hex(random_bytes(4)) . '.csv';
        $tempPath = $privDir . '/' . $tempNome;

        if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
            Session::setFlash('danger', 'Falha ao processar arquivo temporário no servidor.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        // Executa análise prévia
        $previa = SpreadsheetService::analisarArquivo($tempPath);

        if (!$previa['valido']) {
            @unlink($tempPath);
            Session::setFlash('danger', implode('<br>', $previa['erros_estruturais']));
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        Session::set('_import_temp_file', $tempPath);
        Session::set('_import_previa', $previa);

        Session::setFlash('info', "Análise concluída: {$previa['linhas_validas']} linha(s) apta(s) para importação.");
        $this->redirect('/admin/importacao-escolar');
    }

    /**
     * Confirmação da conferência prévia e execução da importação atômica
     */
    public function importacaoEscolarProcessar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        $tempPath = Session::get('_import_temp_file');
        if (!$tempPath || !file_exists($tempPath)) {
            Session::setFlash('danger', 'Nenhuma conferência prévia ativa para processar. Envie a planilha novamente.');
            $this->redirect('/admin/importacao-escolar');
            return;
        }

        try {
            $resultado = SpreadsheetService::processarImportacao($tempPath, Auth::id());

            // Remove o arquivo temporário da prévia
            @unlink($tempPath);
            Session::remove('_import_temp_file');
            Session::remove('_import_previa');

            $msg = "Importação concluída com sucesso! Processados: {$resultado['linhas_processadas']} registro(s). ";
            $msg .= "({$resultado['novos_alunos']} novos alunos, {$resultado['alunos_atualizados']} alunos atualizados, ";
            $msg .= "{$resultado['novos_responsaveis']} novos responsáveis, {$resultado['novas_turmas']} novas turmas).";

            Session::setFlash('success', $msg);
            $this->redirect('/admin/alunos');
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao processar importação: ' . $e->getMessage());
            $this->redirect('/admin/importacao-escolar');
        }
    }

    /**
     * Cancela a conferência prévia atual e limpa a sessão
     */
    public function importacaoEscolarCancelar(): void
    {
        if (!defined('CANTINA_TEST_MODE') || !CANTINA_TEST_MODE) {
            $this->validateCsrf();
        }

        $tempPath = Session::get('_import_temp_file');
        if ($tempPath && file_exists($tempPath)) {
            @unlink($tempPath);
        }
        Session::remove('_import_temp_file');
        Session::remove('_import_previa');

        Session::setFlash('info', 'Conferência prévia cancelada.');
        $this->redirect('/admin/importacao-escolar');
    }

    /**
     * Download do modelo CSV oficial de importação de alunos
     */
    public function importacaoEscolarModelo(): void
    {
        $csv = SpreadsheetService::gerarModeloCsv();
        $filename = 'modelo_importacao_alunos_monteiro_lobato.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $csv;
        exit;
    }

    // =========================================================================
    // GESTÃO DE ALUNOS (Fase 8 — FSD Área 7 & Módulo 9.6)
    // =========================================================================

    /**
     * Listagem e busca de alunos com filtros
     */
    public function alunos(): void
    {
        $busca    = (string)$this->input('busca', '');
        $turmaId  = (int)$this->input('turma_id', 0);
        $turno    = (string)$this->input('turno', '');
        $status   = (string)$this->input('status', 'ativos');
        $filtrar  = (bool)($this->input('filtrar', false) || isset($_GET['filtrar']));

        // Carga sob demanda: ativa ao clicar em 'Filtrar' ou ao interagir com filtros/paginação
        $jaFiltrado = $filtrar || !empty($busca) || $turmaId > 0 || !empty($turno) || (isset($_GET['status']) && $_GET['status'] !== 'ativos') || isset($_GET['pagina']);

        $filtros = [
            'busca'    => $busca,
            'turma_id' => $turmaId,
            'turno'    => $turno,
            'status'   => in_array($status, ['ativos', 'inativos', 'todos']) ? $status : 'ativos'
        ];

        $paginaAtual    = max(1, (int)$this->input('pagina', 1));
        $itensPorPagina = Configuracao::getItensPorPagina();

        if ($jaFiltrado) {
            $totalAlunos  = Aluno::contarTodos($filtros);
            $totalPaginas = max(1, (int)ceil($totalAlunos / $itensPorPagina));
            if ($paginaAtual > $totalPaginas) {
                $paginaAtual = $totalPaginas;
            }
            $offset = ($paginaAtual - 1) * $itensPorPagina;
            $alunos = Aluno::listarTodos($filtros, $itensPorPagina, $offset);
        } else {
            $totalAlunos  = 0;
            $totalPaginas = 1;
            $alunos       = [];
        }

        $turmas       = Turma::listarTodas();
        $responsaveis = Responsavel::listarParaSelecao();
        $alergenos    = RestricaoAlimentar::listarTodas();
        $usuario      = Auth::user();

        $this->render('admin/alunos', [
            'pageTitle'      => 'Alunos e Matrículas',
            'pageHeading'    => 'Gestão de Alunos e Matrículas',
            'activeMenu'     => 'alunos',
            'usuario'        => $usuario,
            'user'           => $usuario,
            'alunos'         => $alunos,
            'turmas'         => $turmas,
            'responsaveis'   => $responsaveis,
            'alergenos'      => $alergenos,
            'filtros'        => $filtros,
            'jaFiltrado'     => $jaFiltrado,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalAlunos,
            'itensPorPagina' => $itensPorPagina
        ], 'admin');
    }

    /**
     * Salva ou atualiza os dados cadastrais de um aluno
     */
    public function alunosSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/alunos');
            return;
        }

        $id            = (int)$this->input('id', 0);
        $matricula     = trim((string)$this->input('matricula', ''));
        $nome          = trim((string)$this->input('nome', ''));
        $turmaId       = (int)$this->input('turma_id', 0);
        $responsavelId = (int)$this->input('responsavel_id', 0);
        $turno         = (string)$this->input('turno', 'matutino');
        $alergenos     = (array)($this->input('alergenos', []) ?: []);

        if (empty($matricula) || empty($nome) || $turmaId <= 0 || $responsavelId <= 0) {
            Session::setFlash('danger', 'Matrícula, Nome do Aluno, Turma e Responsável são campos obrigatórios.');
            $this->redirect('/admin/alunos');
            return;
        }

        // Verifica unicidade de matrícula
        $existente = Aluno::buscarPorMatricula($matricula, true);
        if ($existente && (int)$existente['id'] !== $id) {
            Session::setFlash('danger', "A matrícula [{$matricula}] já pertence a outro aluno cadastrado.");
            $this->redirect('/admin/alunos');
            return;
        }

        $dados = [
            'matricula'      => $matricula,
            'nome'           => $nome,
            'turma_id'       => $turmaId,
            'responsavel_id' => $responsavelId,
            'turno'          => in_array($turno, ['matutino', 'vespertino']) ? $turno : 'matutino',
            'alergenos'      => array_map('intval', $alergenos)
        ];

        $usuarioId = Auth::id();

        if ($id > 0) {
            Aluno::atualizar($id, $dados, $usuarioId);
            Session::setFlash('success', "Aluno [{$nome}] atualizado com sucesso.");
        } else {
            Aluno::criar($dados, $usuarioId);
            Session::setFlash('success', "Aluno [{$nome}] cadastrado com sucesso.");
        }

        $this->redirect('/admin/alunos?filtrar=1');
    }

    /**
     * Inativação lógica do aluno (bloqueia agendamentos mantendo históricos)
     */
    public function alunosInativar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/alunos');
            return;
        }

        $id = (int)$this->input('id', 0);
        $aluno = Aluno::buscarPorId($id);

        if (!$aluno) {
            Session::setFlash('danger', 'Aluno não encontrado.');
            $this->redirect('/admin/alunos');
            return;
        }

        Aluno::inativar($id, Auth::id());
        Session::setFlash('success', "Aluno [{$aluno['nome']}] foi inativado. Novos agendamentos foram bloqueados.");
        $this->redirect('/admin/alunos?filtrar=1');
    }

    /**
     * Reativa um aluno inativo
     */
    public function alunosReativar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/alunos');
            return;
        }

        $id = (int)$this->input('id', 0);
        $aluno = Aluno::buscarPorId($id);

        if (!$aluno) {
            Session::setFlash('danger', 'Aluno não encontrado.');
            $this->redirect('/admin/alunos');
            return;
        }

        Aluno::reativar($id, Auth::id());
        Session::setFlash('success', "Aluno [{$aluno['nome']}] reativado com sucesso.");
        $this->redirect('/admin/alunos?filtrar=1');
    }

    // =========================================================================
    // UTILITÁRIOS INTERNOS
    // =========================================================================

    /**
     * Processa upload seguro de imagem estática para a pasta pública
     * 
     * @param string $fileKey Nome do campo input do tipo file
     * @param string $prefixo Prefixo para o nome único do arquivo
     * @return string|null Caminho relativo do arquivo gravado ou null se nenhum arquivo válido enviado
     */
    private function uploadImagem(string $fileKey, string $prefixo = 'img'): ?string
    {
        if (!isset($_FILES[$fileKey]) || !is_array($_FILES[$fileKey])) {
            return null;
        }

        $file = $_FILES[$fileKey];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        // Limite de 10 MB conforme Seção 9.6 do FSD
        $maxTamanho = 10 * 1024 * 1024;
        if ($file['size'] > $maxTamanho) {
            Session::setFlash('danger', 'O arquivo de imagem excede o tamanho máximo permitido de 10 MB.');
            return null;
        }

        // Validação estrita de extensão
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $extensoesPermitidas, true)) {
            Session::setFlash('danger', 'Formato de imagem inválido. Formatos aceitos: JPG, PNG e WebP.');
            return null;
        }

        // Validação estrita de MIME type
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $mimesPermitidos, true)) {
            Session::setFlash('danger', 'Assinatura MIME de arquivo inválida.');
            return null;
        }

        // Se for upload de logo institucional, pode salvar em assets/img ou storage/uploads/public
        $diretorioDestino = dirname(__DIR__, 2) . '/storage/uploads/public';
        if (!is_dir($diretorioDestino)) {
            mkdir($diretorioDestino, 0755, true);
        }

        // Renomeação criptográfica (hash/UUID) para neutralizar execução remota
        $nomeAleatorio = $prefixo . '_' . bin2hex(random_bytes(12)) . '.' . $ext;
        $caminhoCompleto = $diretorioDestino . '/' . $nomeAleatorio;

        if (move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
            return 'storage/uploads/public/' . $nomeAleatorio;
        }

        return null;
    }

    /**
     * Verifica se a requisição atual é assíncrona / AJAX
     */
    private function isAjax(): bool
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strtolower($requestedWith) === 'xmlhttprequest' || str_contains($accept, 'application/json');
    }

    /**
     * Visualização e Gestão do Calendário Escolar
     */
    public function calendario(): void
    {
        $semanaParam = $this->input('semana');
        $dataBase = null;
        if (!empty($semanaParam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaParam)) {
            $dataBase = $semanaParam;
        }

        $diasSemana = CalendarioEscolar::obterDiasSemanaLetiva('atual', null, $dataBase);

        // Identifica a segunda-feira da semana em visualização
        $primeiroDia = $diasSemana[0]['data'] ?? date('Y-m-d');
        $dtSegunda = new \DateTimeImmutable($primeiroDia);
        $semanaAnterior = $dtSegunda->modify('-7 days')->format('Y-m-d');
        $semanaProxima = $dtSegunda->modify('+7 days')->format('Y-m-d');
        $semanaAtualHoje = date('Y-m-d');

        $anoAtual = (int)$dtSegunda->format('Y');
        $excecoesAno = CalendarioEscolar::listarExcecoes($anoAtual);
        $metricas = CalendarioEscolar::contarMetricasAno($anoAtual);

        $this->render('admin/calendario', [
            'pageTitle'       => 'Calendário Escolar & Dias Letivos',
            'pageHeading'     => 'Calendário Escolar',
            'activeMenu'      => 'calendario',
            'diasSemana'      => $diasSemana,
            'semanaAnterior'  => $semanaAnterior,
            'semanaProxima'   => $semanaProxima,
            'semanaAtualHoje' => $semanaAtualHoje,
            'dataSelecionada' => $primeiroDia,
            'excecoesAno'     => $excecoesAno,
            'metricas'        => $metricas,
            'anoAtual'        => $anoAtual,
            'hoje'            => date('Y-m-d'),
        ], 'admin');
    }

    /**
     * Cadastra ou atualiza uma exceção no calendário letivo (Feriado, Recesso ou Férias)
     */
    public function calendarioSalvar(): void
    {
        $this->validateCsrf();

        $data = (string)$this->input('data');
        $tipo = (string)$this->input('tipo');
        $descricao = (string)$this->input('descricao');

        if (empty($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            Session::setFlash('warning', 'Data inválida para o calendário.');
            $this->redirect('/admin/calendario');
            return;
        }

        try {
            $res = CalendarioEscolar::salvarExcecao($data, $tipo, $descricao, (int)Auth::id());
            Session::setFlash('success', $res['mensagem']);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao salvar data no calendário: ' . $e->getMessage());
        }

        $this->redirect('/admin/calendario?semana=' . urlencode($data));
    }

    /**
     * Remove uma exceção do calendário, restaurando o dia ao padrão regular
     */
    public function calendarioExcluir(): void
    {
        $this->validateCsrf();

        $data = (string)$this->input('data');

        if (empty($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            Session::setFlash('warning', 'Data inválida.');
            $this->redirect('/admin/calendario');
            return;
        }

        try {
            CalendarioEscolar::removerExcecao($data);
            Session::setFlash('success', 'Exceção removida com sucesso. A data ' . date('d/m/Y', strtotime($data)) . ' voltou a operar normalmente.');
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao remover data do calendário: ' . $e->getMessage());
        }

        $this->redirect('/admin/calendario?semana=' . urlencode($data));
    }

    /**
     * Executa a rotina de Cancelamento Emergencial em Lote para uma data
     */
    public function calendarioEmergencial(): void
    {
        $this->validateCsrf();

        $data = (string)$this->input('data');
        $motivo = (string)$this->input('motivo');

        if (empty($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            Session::setFlash('warning', 'Informe uma data válida para a suspensão emergencial.');
            $this->redirect('/admin/calendario');
            return;
        }

        if (empty(trim($motivo))) {
            Session::setFlash('warning', 'A justificativa para suspensão emergencial é obrigatória.');
            $this->redirect('/admin/calendario?semana=' . urlencode($data));
            return;
        }

        try {
            $res = CalendarioEscolar::cancelamentoEmergencial($data, $motivo, (int)Auth::id(), Auth::getClientIp());
            Session::setFlash('success', $res['mensagem']);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Falha ao processar cancelamento emergencial: ' . $e->getMessage());
        }

        $this->redirect('/admin/calendario?semana=' . urlencode($data));
    }

    /**
     * Central de Relatórios Gerenciais (Descontinuada)
     */
    public function relatorios(): void
    {
        $this->redirect('/admin');
    }

    /**
     * Painel de Gestão de Contestações Bancárias e Negativados MED (FSD Módulo 7.3 & Tela 8.4)
     */
    public function contestacoes(): void
    {
        $usuario = Auth::user();
        $busca   = trim((string)$this->input('busca', ''));
        $status  = trim((string)$this->input('status', ''));
        $filtrar = (bool)$this->input('filtrar', false);
        $pagina  = max(1, (int)$this->input('pagina', 1));

        $itensPorPagina = Configuracao::getItensPorPagina();

        // Determina se houve interação com os filtros para carga sob demanda
        $jaFiltrado = $filtrar || !empty($busca) || (!empty($status) && $status !== 'todos') || isset($_GET['pagina']);

        $filtros = [];
        if (!empty($busca)) {
            $filtros['busca'] = $busca;
        }
        if (!empty($status)) {
            $filtros['status'] = $status;
        }

        if ($jaFiltrado) {
            $resultado = Responsavel::listarNegativadosMed($filtros, $pagina, $itensPorPagina);
        } else {
            $resultado = [
                'itens' => [],
                'total' => 0,
                'pagina' => 1,
                'total_paginas' => 1,
                'por_pagina' => $itensPorPagina
            ];
        }

        // Métricas do Stat-Strip
        $totalNegativados   = Responsavel::contarNegativadosMed();
        $totalSaldoDevedor  = Responsavel::somarSaldoDevedorMed();
        $historicoEventos   = TransacaoCarteira::listarHistoricoMed([], 15);
        $totalContestacoes  = (int)(Responsavel::fetchOne("SELECT COUNT(*) AS total FROM transacoes_carteira WHERE tipo_transacao = 'debito_contestacao_med'")['total'] ?? 0);
        $totalRegularizados = (int)(Responsavel::fetchOne("SELECT COUNT(*) AS total FROM logs_auditoria WHERE acao IN ('desbloqueio_med', 'quitacao_med')")['total'] ?? 0);

        // Monta estrutura de paginação
        $paginacao = [
            'pagina_atual'  => $resultado['pagina'],
            'total_paginas' => $resultado['total_paginas'],
            'total_itens'   => $resultado['total'],
            'itens_por_pag' => $resultado['por_pagina'],
            'url_base'      => '/admin/contestacoes'
        ];

        // Responsáveis para o dropdown de lançamento manual
        $responsaveisParaSelecao = Responsavel::listarParaSelecao();

        $this->render('admin/contestacoes', [
            'pageTitle'           => 'Contestações Pix & Negativados MED',
            'pageHeading'         => 'Mecanismo Especial de Devolução (MED)',
            'activeMenu'          => 'contestacoes',
            'jaFiltrado'          => $jaFiltrado,
            'negativados'         => $resultado['itens'],
            'familias'            => $resultado['itens'],
            'totalNegativados'    => $totalNegativados,
            'saldoDevedorTotal'   => $totalSaldoDevedor,
            'totalSaldoDevedor'   => $totalSaldoDevedor,
            'totalEstornos'       => $totalContestacoes,
            'totalContestacoes'   => $totalContestacoes,
            'contasRegularizadas' => $totalRegularizados,
            'totalRegularizados'  => $totalRegularizados,
            'historicoMed'        => $historicoEventos,
            'historicoEventos'    => $historicoEventos,
            'todosResponsaveis'   => $responsaveisParaSelecao,
            'responsaveisSelect'  => $responsaveisParaSelecao,
            'filtroBusca'         => $busca,
            'busca'               => $busca,
            'filtroStatus'        => $status,
            'statusFiltro'        => $status,
            'pagina'              => $resultado['pagina'],
            'totalPaginas'        => $resultado['total_paginas'],
            'paginacao'           => $paginacao,
            'usuario'             => $usuario,
            'user'                => $usuario
        ], 'admin');
    }

    /**
     * Processa o lançamento manual de contestação MED pelo Administrador (FSD Tela 7.3)
     */
    public function contestacaoManualSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        $responsavelId = (int)$this->input('responsavel_id', 0);
        $valorRaw      = trim((string)$this->input('valor', ''));
        $rtrId         = trim((string)$this->input('rtr_id', ''));
        $motivo        = trim((string)$this->input('motivo', ''));
        $justificativa = trim((string)$this->input('justificativa', ''));
        $notificar     = (bool)$this->input('notificar_whatsapp', true);

        // Normalização de moeda
        if (str_contains($valorRaw, ',')) {
            $valorLimpo = str_replace('.', '', $valorRaw);
            $valorLimpo = str_replace(',', '.', $valorLimpo);
            $valor = (float)$valorLimpo;
        } else {
            $valor = (float)$valorRaw;
        }

        if ($responsavelId <= 0 || $valor <= 0) {
            Session::setFlash('danger', 'Selecione um responsável válido e informe um valor maior que zero.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        $resp = Responsavel::buscarPorId($responsavelId);
        if (!$resp) {
            Session::setFlash('danger', 'Responsável titular não localizado no sistema.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        if (empty($rtrId)) {
            $rtrId = 'MAN-MED-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));
        }

        $motivoCompleto = !empty($justificativa) ? ($motivo . ' - ' . $justificativa) : ($motivo ?: 'Contestação MED Lançada Manualmente pela Administração');
        $operadorId = Auth::id() ?: 1;

        try {
            $res = TransacaoCarteira::processarEstornoMed(
                $responsavelId,
                $valor,
                $rtrId,
                $motivoCompleto,
                $operadorId,
                $notificar
            );

            if (!empty($res['ja_processado'])) {
                Session::setFlash('warning', "Esta contestação já havia sido lançada anteriormente (Ref: {$rtrId}).");
            } else {
                $statusMsg = $res['bloqueado_med'] 
                    ? "Contestação de R$ " . number_format($valor, 2, ',', '.') . " registrada com sucesso. A conta familiar ficou com saldo devedor e foi suspensa preventivamente."
                    : "Contestação de R$ " . number_format($valor, 2, ',', '.') . " debitada com sucesso da carteira familiar.";

                Session::setFlash('success', $statusMsg);
            }
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao processar contestação: ' . $e->getMessage());
        }

        $this->redirect('/admin/contestacoes');
    }

    /**
     * Processa a quitação de saldo devedor e regularização da família (FSD Tela 7.3)
     */
    public function quitacaoMedSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        $responsavelId  = (int)$this->input('responsavel_id', 0);
        $valorRaw       = trim((string)$this->input('valor', ''));
        $formaPagamento = trim((string)$this->input('forma_pagamento', 'dinheiro'));
        $justificativa  = trim((string)$this->input('justificativa', ''));
        $desbloquear    = (bool)$this->input('desbloquear_se_positivo', true);

        // Normalização de moeda
        if (str_contains($valorRaw, ',')) {
            $valorLimpo = str_replace('.', '', $valorRaw);
            $valorLimpo = str_replace(',', '.', $valorLimpo);
            $valor = (float)$valorLimpo;
        } else {
            $valor = (float)$valorRaw;
        }

        if ($responsavelId <= 0 || $valor <= 0) {
            Session::setFlash('danger', 'Informe um valor de quitação válido maior que zero.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        $operadorId = Auth::id() ?: 1;

        try {
            $res = TransacaoCarteira::processarQuitacaoMed(
                $responsavelId,
                $valor,
                $formaPagamento,
                $justificativa,
                $operadorId,
                $desbloquear
            );

            $msg = "Quitação de R$ " . number_format($valor, 2, ',', '.') . " registrada com sucesso (Protocolo: {$res['protocolo']}).";
            if (!empty($res['desbloqueado'])) {
                $msg .= " A conta familiar foi devidamente regularizada e desbloqueada.";
            }

            Session::setFlash('success', $msg);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro ao processar quitação: ' . $e->getMessage());
        }

        $this->redirect('/admin/contestacoes');
    }

    /**
     * Remove manualmente o bloqueio MED de uma família com justificativa
     */
    public function desbloquearContaMed(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        $responsavelId = (int)$this->input('responsavel_id', 0);
        $justificativa = trim((string)$this->input('justificativa', ''));
        $notificarWhatsapp = (bool)$this->input('notificar_whatsapp', false);

        if ($responsavelId <= 0 || empty($justificativa)) {
            Session::setFlash('danger', 'A justificativa de desbloqueio é de preenchimento obrigatório.');
            $this->redirect('/admin/contestacoes');
            return;
        }

        $operadorId = Auth::id() ?: 1;
        Responsavel::desbloquearMed($responsavelId, $operadorId, $justificativa);

        if ($notificarWhatsapp) {
            $resp = Responsavel::buscarPorId($responsavelId);
            if ($resp && !empty($resp['whatsapp'])) {
                try {
                    $ws = new \App\Services\WhatsAppService();
                    $ws->sendNotificacaoDesbloqueioMed($resp['whatsapp'], (float)$resp['saldo_atual'], $resp['nome'] ?? '');
                } catch (\Throwable $e) {}
            }
        }

        Session::setFlash('success', 'Suspensão preventiva MED removida com sucesso para o responsável.');
        $this->redirect('/admin/contestacoes');
    }

    /**
     * Retorna sugestões rápidas em JSON para autocomplete na busca de contestações MED
     */
    public function contestacoesSugestoes(): void
    {
        $termo = trim((string)$this->input('q', (string)$this->input('busca', '')));
        $limite = max(1, min(20, (int)$this->input('limite', 10)));

        $sugestoes = Responsavel::buscarSugestoesMed($termo, $limite);

        $this->json([
            'sucesso'   => true,
            'success'   => true,
            'termo'     => $termo,
            'sugestoes' => $sugestoes,
            'total'     => count($sugestoes)
        ]);
    }

    /**
     * Espelho HTML para visualização / impressão rápida em folha A4 e salvar em PDF
     */
    public function contestacoesImprimir(): void
    {
        $busca = trim((string)$this->input('busca', ''));
        $status = trim((string)$this->input('status', 'todos'));
        $filtros = [];
        if (!empty($busca)) {
            $filtros['busca'] = $busca;
        }
        if (!empty($status) && $status !== 'todos') {
            $filtros['status'] = $status;
        }

        $dados = Responsavel::listarNegativadosMed($filtros, 1, 1000);
        $totalDevedor = Responsavel::somarSaldoDevedorMed();
        $usuario = Auth::user();

        echo \App\Services\PdfService::renderView('relatorios/med_negativados_pdf', [
            'dadosMed' => [
                'familias'       => $dados['itens'],
                'total_devedor'  => $totalDevedor,
                'total_familias' => $dados['total'],
                'operador'       => $usuario['nome'] ?? 'Administração'
            ]
        ]);
        if (!defined('CANTINA_TEST_MODE')) {
            exit;
        }
    }

    /**
     * Emissão do Relatório A4 em PDF Timbrado de Negativados MED (FSD Tela 8.4)
     */
    /**
     * Emissão do Relatório de Famílias Negativadas MED para Impressão e Print to PDF Nativo
     */
    public function contestacoesPdf(): void
    {
        $this->contestacoesImprimir();
    }
}

