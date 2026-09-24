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
use App\Services\SpreadsheetService;

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
            'activeMenu'  => 'corte',
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

        $produtos = Produto::listarTodos($filtros);
        $categorias = Categoria::listarTodas(true);
        $alergenos = RestricaoAlimentar::listarTodas();
        $usuario = Auth::user();

        $this->render('admin/produtos', [
            'pageTitle'   => 'Cardápio e Produtos',
            'pageHeading' => 'Gestão de Cardápio e Produtos',
            'activeMenu'  => 'produtos',
            'produtos'    => $produtos,
            'categorias'  => $categorias,
            'alergenos'   => $alergenos,
            'filtros'     => $filtros,
            'usuario'     => $usuario,
            'user'        => $usuario
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
        $statusPausa          = (string)$this->input('status_pausa', 'ativo');
        $alergenosIds         = $_POST['alergenos'] ?? [];

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
            'categoria_id'          => $categoriaId,
            'codigo_interno'        => $codigoInterno,
            'descricao'             => $descricao,
            'preco_venda'           => max(0.01, $precoVenda),
            'tipo'                  => in_array($tipo, ['salgado_diario', 'industrializado_continuo']) ? $tipo : 'salgado_diario',
            'turno_disponibilidade' => in_array($turnoDisponibilidade, ['matutino', 'vespertino', 'ambos']) ? $turnoDisponibilidade : 'ambos',
            'estoque_atual'         => max(0, $estoqueAtual),
            'estoque_minimo'        => max(0, $estoqueMinimo),
            'status_pausa'          => ($statusPausa === 'pausado') ? 'pausado' : 'ativo'
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
        $colaboradores = Colaborador::listarTodos(false, $busca);
        $usuario = Auth::user();

        $this->render('admin/colaboradores', [
            'pageTitle'     => 'Colaboradores Escolares',
            'pageHeading'   => 'Colaboradores e Professores',
            'activeMenu'    => 'colaboradores',
            'colaboradores' => $colaboradores,
            'busca'         => $busca,
            'usuario'       => $usuario,
            'user'          => $usuario
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

        $this->redirect('/admin/colaboradores');
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

        $this->redirect('/admin/colaboradores');
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
        $this->redirect('/admin/colaboradores');
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

        $turmas = Turma::listarTodas($turno, $busca);
        $usuario = Auth::user();

        $this->render('admin/turmas', [
            'pageTitle'   => 'Turmas Escolares',
            'pageHeading' => 'Turmas e Agrupamentos Escolares',
            'activeMenu'  => 'turmas',
            'turmas'      => $turmas,
            'turno'       => $turno,
            'busca'       => $busca,
            'usuario'     => $usuario,
            'user'        => $usuario
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

        $this->redirect('/admin/turmas');
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
        $this->redirect('/admin/turmas');
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

        $filtros = [
            'busca'    => $busca,
            'turma_id' => $turmaId,
            'turno'    => $turno,
            'status'   => in_array($status, ['ativos', 'inativos', 'todos']) ? $status : 'ativos'
        ];

        $alunos       = Aluno::listarTodos($filtros);
        $turmas       = Turma::listarTodas();
        $responsaveis = Responsavel::listarParaSelecao();
        $alergenos    = RestricaoAlimentar::listarTodas();
        $usuario      = Auth::user();

        $this->render('admin/alunos', [
            'pageTitle'    => 'Alunos e Matrículas',
            'pageHeading'  => 'Gestão de Alunos e Matrículas',
            'activeMenu'   => 'alunos',
            'usuario'      => $usuario,
            'user'         => $usuario,
            'alunos'       => $alunos,
            'turmas'       => $turmas,
            'responsaveis' => $responsaveis,
            'alergenos'    => $alergenos,
            'filtros'      => $filtros
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

        $this->redirect('/admin/alunos');
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
        $this->redirect('/admin/alunos');
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
        $this->redirect('/admin/alunos');
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
     * Visualização do Calendário Escolar
     */
    public function calendario(): void
    {
        $diasSemana = \App\Models\CalendarioEscolar::obterDiasSemanaLetiva(date('Y-m-d'));
        
        $this->render('admin/calendario', [
            'pageTitle'  => 'Calendário Escolar & Dias Letivos',
            'activeMenu' => 'calendario',
            'diasSemana' => $diasSemana,
            'hoje'       => date('Y-m-d')
        ], 'admin');
    }

    /**
     * Central de Relatórios Gerenciais
     */
    public function relatorios(): void
    {
        $this->render('admin/relatorios', [
            'pageTitle'  => 'Relatórios Gerenciais',
            'activeMenu' => 'relatorios'
        ], 'admin');
    }
}
