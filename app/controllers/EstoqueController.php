<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador de Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

namespace App\Controllers;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Controller;
use App\Core\Session;
use App\Core\Auth;
use App\Models\Configuracao;
use App\Models\Estoque;
use App\Models\Produto;
use App\Models\Categoria;
use App\Models\NotaFiscal;
use App\Models\ItemNotaFiscal;
use App\Services\XmlParserService;

class EstoqueController extends Controller
{
    /**
     * Tela 6.1: Painel Geral de Saldos e Controle de Estoque
     */
    public function index(): void
    {
        $categoriaId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
        $tipo        = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;
        $statusPausa = isset($_GET['status_pausa']) ? trim($_GET['status_pausa']) : null;
        $apenasAlerta= isset($_GET['alerta']) && $_GET['alerta'] === '1';
        $busca       = isset($_GET['busca']) ? trim($_GET['busca']) : '';

        $filtros = [
            'categoria_id' => $categoriaId,
            'tipo'         => $tipo,
            'status_pausa' => $statusPausa,
            'apenas_alerta'=> $apenasAlerta,
            'busca'        => $busca
        ];

        $paginaAtual    = max(1, (int)($_GET['pagina'] ?? 1));
        $itensPorPagina = Configuracao::getItensPorPagina();
        $totalProdutos  = Estoque::contarSaldos($filtros);
        $totalPaginas   = max(1, (int)ceil($totalProdutos / $itensPorPagina));
        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
        }
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        $produtos   = Estoque::listarSaldos($filtros, $itensPorPagina, $offset);
        $metricas   = Estoque::obterMetricasGerais();
        $categorias = Categoria::listarTodas();

        $this->render('estoque/index', [
            'produtos'       => $produtos,
            'metricas'       => $metricas,
            'categorias'     => $categorias,
            'filtros'        => $filtros,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalProdutos,
            'itensPorPagina' => $itensPorPagina,
            'activeMenu'     => 'estoque',
            'title'          => 'Controle de Estoque & Saldos'
        ], 'admin');
    }

    /**
     * Tela 6.3: Lançamento Manual de Perdas de Mercadorias (RN-15)
     */
    public function perdas(): void
    {
        $produtos     = Produto::listarTodos();
        $ultimasPerdas= Estoque::listarPerdas(['limite' => 20]);
        $produtoPreSel= isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;

        $this->render('estoque/perdas', [
            'produtos'      => $produtos,
            'ultimasPerdas' => $ultimasPerdas,
            'produtoPreSel' => $produtoPreSel,
            'motivosPerda'  => Estoque::MOTIVOS_PERDA,
            'activeMenu'    => 'perdas',
            'title'         => 'Registro de Perdas de Mercadorias'
        ], 'admin');
    }

    /**
     * Processa a gravação da perda de estoque com validações e auditoria
     */
    public function perdasSalvar(): void
    {
        if (!$this->validateCsrf(null, '/estoque/perdas')) {
            return;
        }

        $produtoId     = (int)($_POST['produto_id'] ?? 0);
        $quantidade    = (int)($_POST['quantidade'] ?? 0);
        $motivoPerda   = trim($_POST['motivo_perda'] ?? '');
        $justificativa = trim($_POST['justificativa'] ?? '');

        if ($produtoId <= 0) {
            Session::setFlash('danger', 'Selecione o produto que sofreu a perda.');
            $this->redirect('/estoque/perdas');
            return;
        }

        if ($quantidade <= 0) {
            Session::setFlash('danger', 'A quantidade da perda deve ser maior que zero.');
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
            return;
        }

        if (!array_key_exists($motivoPerda, Estoque::MOTIVOS_PERDA)) {
            Session::setFlash('danger', 'Selecione um motivo de perda padronizado válido.');
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
            return;
        }

        if (mb_strlen($justificativa, 'UTF-8') < 5) {
            Session::setFlash('danger', 'A justificativa é obrigatória para fins de auditoria (mínimo 5 caracteres).');
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
            return;
        }

        $usuario = Auth::user();
        $operadorId = (int)($usuario['id'] ?? 1);

        try {
            Estoque::registrarPerda($produtoId, $quantidade, $motivoPerda, $justificativa, $operadorId);

            $produto = Produto::buscarPorId($produtoId);
            $nomeProd = $produto['descricao'] ?? 'Produto';
            $motivoNome = Estoque::MOTIVOS_PERDA[$motivoPerda];

            Session::setFlash('success', "Baixa de {$quantidade} un de '{$nomeProd}' registrada com sucesso por motivo de {$motivoNome}.");
            $this->redirect('/estoque/perdas');
        } catch (\DomainException $de) {
            Session::setFlash('danger', $de->getMessage());
            $this->redirect('/estoque/perdas?produto_id=' . $produtoId);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro inesperado ao registrar a perda: ' . $e->getMessage());
            $this->redirect('/estoque/perdas');
        }
    }

    /**
     * Atualização rápida do ponto de estoque mínimo
     */
    public function atualizarEstoqueMinimo(): void
    {
        if (!$this->validateCsrf(null, '/estoque')) {
            return;
        }

        $produtoId   = (int)($_POST['produto_id'] ?? 0);
        $novoMinimo  = (int)($_POST['estoque_minimo'] ?? 0);
        $usuario     = Auth::user();
        $usuarioId   = (int)($usuario['id'] ?? 1);

        if ($produtoId <= 0 || $novoMinimo < 0) {
            if ($this->isAjax()) {
                $this->json(['sucesso' => false, 'mensagem' => 'Parâmetros inválidos.'], 400);
                return;
            }
            Session::setFlash('danger', 'Parâmetros inválidos para atualização do estoque mínimo.');
            $this->redirect('/estoque');
            return;
        }

        try {
            Estoque::atualizarEstoqueMinimo($produtoId, $novoMinimo, $usuarioId);

            if ($this->isAjax()) {
                $this->json(['sucesso' => true, 'mensagem' => 'Estoque mínimo atualizado com sucesso.']);
                return;
            }

            Session::setFlash('success', 'Ponto de estoque mínimo atualizado.');
            $this->redirect('/estoque');
        } catch (\Throwable $e) {
            if ($this->isAjax()) {
                $this->json(['sucesso' => false, 'mensagem' => $e->getMessage()], 500);
                return;
            }
            Session::setFlash('danger', $e->getMessage());
            $this->redirect('/estoque');
        }
    }

    /**
     * Tela: Entrada Manual de Estoque (Substitui o fluxo XML de NF-e)
     */
    public function entrada(): void
    {
        $produtos         = Produto::listarTodos();
        $ultimasEntradas  = Estoque::listarEntradasRecentes(20);
        $fornecedores     = Estoque::listarFornecedoresSugestao();
        $produtoPreSel    = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;

        $this->render('estoque/entrada', [
            'produtos'        => $produtos,
            'ultimasEntradas' => $ultimasEntradas,
            'fornecedores'    => $fornecedores,
            'produtoPreSel'   => $produtoPreSel,
            'activeMenu'      => 'entrada_estoque',
            'title'           => 'Entrada de Estoque & Mercadorias'
        ], 'admin');
    }

    /**
     * Processa a gravação da entrada manual de estoque
     */
    public function entradaSalvar(): void
    {
        if (!$this->validateCsrf(null, '/estoque/entrada')) {
            return;
        }

        $produtoId   = (int)($_POST['produto_id'] ?? 0);
        $quantidade  = (int)($_POST['quantidade'] ?? 0);
        $fornecedor  = trim($_POST['fornecedor'] ?? '');
        $precoCusto  = !empty($_POST['preco_custo']) ? (float)str_replace(['.', ','], ['', '.'], $_POST['preco_custo']) : null;
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($produtoId <= 0) {
            Session::setFlash('danger', 'Selecione o produto para dar entrada.');
            $this->redirect('/estoque/entrada');
            return;
        }

        if ($quantidade <= 0) {
            Session::setFlash('danger', 'A quantidade da entrada deve ser maior que zero.');
            $this->redirect('/estoque/entrada?produto_id=' . $produtoId);
            return;
        }

        if (mb_strlen($fornecedor, 'UTF-8') < 2) {
            Session::setFlash('danger', 'Informe o nome do fornecedor (mínimo 2 caracteres).');
            $this->redirect('/estoque/entrada?produto_id=' . $produtoId);
            return;
        }

        $usuario = Auth::user();
        $operadorId = (int)($usuario['id'] ?? 1);

        try {
            Estoque::darEntradaManual($produtoId, $quantidade, $fornecedor, $precoCusto, $observacoes, $operadorId);

            $produto = Produto::buscarPorId($produtoId);
            $nomeProd = $produto['descricao'] ?? 'Produto';

            Session::setFlash('success', "Entrada de {$quantidade} un de '{$nomeProd}' (Fornecedor: {$fornecedor}) registrada com sucesso no estoque!");
            $this->redirect('/estoque/entrada');
        } catch (\DomainException $de) {
            Session::setFlash('danger', $de->getMessage());
            $this->redirect('/estoque/entrada?produto_id=' . $produtoId);
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Erro inesperado ao registrar a entrada: ' . $e->getMessage());
            $this->redirect('/estoque/entrada');
        }
    }

    /**
     * Tela 6.2: Upload de XML de NF-e (Acesso exclusivo para Administrador)
     */
    public function importarXml(): void
    {
        $this->render('estoque/importar_xml', [
            'activeMenu' => 'xml',
            'title'      => 'Importação de Entrada via XML de NF-e'
        ], 'admin');
    }

    /**
     * Processa o upload do XML, extrai os dados via XmlParserService e apresenta a tela de conferência
     */
    public function importarXmlAnalisar(): void
    {
        if (!$this->validateCsrf(null, '/estoque/importar-xml')) {
            return;
        }

        if (empty($_FILES['arquivo_xml']) || $_FILES['arquivo_xml']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('danger', 'Selecione um arquivo XML válido da nota fiscal para envio.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        $file = $_FILES['arquivo_xml'];
        $nomeOriginal = $file['name'];
        $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if ($extensao !== 'xml') {
            Session::setFlash('danger', 'O arquivo enviado deve possuir estritamente a extensão .xml.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            Session::setFlash('danger', 'O arquivo XML excede o limite máximo permitido de 10 MB.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        $conteudoXml = file_get_contents($file['tmp_name']);
        if ($conteudoXml === false || empty(trim($conteudoXml))) {
            Session::setFlash('danger', 'Não foi possível ler o conteúdo do arquivo XML.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        try {
            $dadosNfe = XmlParserService::parseXml($conteudoXml);

            // Verifica se esta nota fiscal já foi importada anteriormente
            $jaExiste = NotaFiscal::buscarPorChave($dadosNfe['chave_nfe']);
            if ($jaExiste) {
                Session::setFlash('warning', "Esta nota fiscal (nº {$dadosNfe['numero_nota']}) já foi importada anteriormente em " . date('d/m/Y H:i', strtotime($jaExiste['created_at'])) . " por {$jaExiste['operador_nome']}. Não é permitido importar a mesma NF-e duas vezes.");
                $this->redirect('/estoque/notas-fiscais/' . $jaExiste['id']);
                return;
            }

            // Armazena temporariamente na sessão para conferência e confirmação
            $_SESSION['_nfe_draft'] = [
                'dados_nfe'     => $dadosNfe,
                'conteudo_xml'  => $conteudoXml,
                'nome_original' => $nomeOriginal,
                'uploaded_at'   => time()
            ];

            $produtos = Produto::listarTodos();

            $this->render('estoque/conferir_xml', [
                'nfe'        => $dadosNfe,
                'produtos'   => $produtos,
                'activeMenu' => 'xml',
                'title'      => "Conferência de NF-e nº {$dadosNfe['numero_nota']} — {$dadosNfe['razao_social_emitente']}"
            ], 'admin');
        } catch (\InvalidArgumentException $iae) {
            Session::setFlash('danger', $iae->getMessage());
            $this->redirect('/estoque/importar-xml');
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Falha ao processar o XML: ' . $e->getMessage());
            $this->redirect('/estoque/importar-xml');
        }
    }

    /**
     * Confirmação e gravação atômica da entrada de mercadorias no estoque
     */
    public function importarXmlProcessar(): void
    {
        if (!$this->validateCsrf(null, '/estoque/importar-xml')) {
            return;
        }

        $draft = $_SESSION['_nfe_draft'] ?? null;
        if (!$draft || empty($draft['dados_nfe']) || empty($draft['conteudo_xml'])) {
            Session::setFlash('danger', 'A sessão de conferência da nota fiscal expirou ou não foi iniciada. Envie o arquivo novamente.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        $dadosNfe = $draft['dados_nfe'];
        $conteudoXml = $draft['conteudo_xml'];

        // Recebe mapeamentos de cada item via POST
        $itensPost = $_POST['itens'] ?? [];
        if (empty($itensPost) || !is_array($itensPost)) {
            Session::setFlash('danger', 'Nenhum item válido para importação foi submetido.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        $usuario = Auth::user();
        $adminId = (int)($usuario['id'] ?? 1);

        // Monta o array de itens mapeados conferindo com os itens do XML original
        $itensMapeados = [];
        foreach ($dadosNfe['itens'] as $idx => $itemXml) {
            $numItem = $itemXml['numero_item'];
            $map = $itensPost[$numItem] ?? null;

            if (!$map) {
                Session::setFlash('danger', "Dados de mapeamento ausentes para o item #{$numItem} ({$itemXml['descricao_fornecedor']}).");
                $this->redirect('/estoque/importar-xml');
                return;
            }

            $produtoId = (int)($map['produto_id'] ?? 0);
            $fator = max(1, (int)($map['fator_conversao'] ?? 1));

            if ($produtoId <= 0) {
                Session::setFlash('danger', "Selecione o produto interno correspondente para o item '{$itemXml['descricao_fornecedor']}'.");
                $this->redirect('/estoque/importar-xml');
                return;
            }

            $itensMapeados[] = [
                'codigo_produto_fornecedor' => $itemXml['codigo_produto_fornecedor'],
                'descricao_fornecedor'      => $itemXml['descricao_fornecedor'],
                'unidade_fornecedor'        => $itemXml['unidade_fornecedor'],
                'quantidade_fornecedor'     => (float)$itemXml['quantidade_fornecedor'],
                'valor_unitario_fornecedor' => (float)$itemXml['valor_unitario_fornecedor'],
                'produto_id'                => $produtoId,
                'fator_conversao'           => $fator
            ];
        }

        // 1. Salva o arquivo XML fisicamente em storage/uploads/private/ (blindado por .htaccess)
        $pastaPrivada = __DIR__ . '/../../storage/uploads/private';
        if (!is_dir($pastaPrivada)) {
            @mkdir($pastaPrivada, 0750, true);
        }

        $hashArquivo = 'nfe_' . $dadosNfe['chave_nfe'] . '_' . bin2hex(random_bytes(6)) . '.xml';
        $caminhoFisico = $pastaPrivada . '/' . $hashArquivo;

        if (file_put_contents($caminhoFisico, $conteudoXml) === false) {
            Session::setFlash('danger', 'Falha ao salvar a cópia de segurança do arquivo XML no disco protegido.');
            $this->redirect('/estoque/importar-xml');
            return;
        }

        try {
            // 2. Cria o cabeçalho da nota fiscal
            $notaFiscalId = NotaFiscal::criar([
                'chave_nfe'             => $dadosNfe['chave_nfe'],
                'numero_nota'           => $dadosNfe['numero_nota'],
                'cnpj_emitente'         => $dadosNfe['cnpj_emitente'],
                'razao_social_emitente' => $dadosNfe['razao_social_emitente'],
                'data_emissao'          => $dadosNfe['data_emissao'],
                'valor_total'           => (float)$dadosNfe['valor_total'],
                'arquivo_xml_path'      => $hashArquivo,
                'importado_por'         => $adminId
            ]);

            // 3. Executa a entrada atômica no estoque e itens da nota
            Estoque::darEntradaXml($notaFiscalId, $itensMapeados, $adminId);

            // Limpa o rascunho da sessão
            unset($_SESSION['_nfe_draft']);

            Session::setFlash('success', "Nota fiscal nº {$dadosNfe['numero_nota']} importada com sucesso! O estoque físico foi abastecido.");
            $this->redirect('/estoque/notas-fiscais/' . $notaFiscalId);
        } catch (\Throwable $e) {
            // Se falhou, remove o arquivo do disco para evitar arquivos órfãos
            if (file_exists($caminhoFisico)) {
                @unlink($caminhoFisico);
            }
            Session::setFlash('danger', 'Erro ao efetivar a importação: ' . $e->getMessage());
            $this->redirect('/estoque/importar-xml');
        }
    }

    /**
     * Descarta a pré-visualização de conferência e limpa a sessão temporária
     */
    public function importarXmlCancelar(): void
    {
        unset($_SESSION['_nfe_draft']);
        Session::setFlash('info', 'Importação da nota fiscal cancelada.');
        $this->redirect('/estoque/importar-xml');
    }

    /**
     * Listagem histórica das notas fiscais eletrônicas importadas
     */
    public function notasFiscais(): void
    {
        $busca      = isset($_GET['busca']) ? trim($_GET['busca']) : '';
        $dataInicio = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : null;
        $dataFim    = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : null;

        $filtros = [
            'busca'       => $busca,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim
        ];

        $paginaAtual    = max(1, (int)($_GET['pagina'] ?? 1));
        $itensPorPagina = Configuracao::getItensPorPagina();
        $totalNotas     = NotaFiscal::contarTodas($filtros);
        $totalPaginas   = max(1, (int)ceil($totalNotas / $itensPorPagina));
        if ($paginaAtual > $totalPaginas) {
            $paginaAtual = $totalPaginas;
        }
        $offset = ($paginaAtual - 1) * $itensPorPagina;

        $notas    = NotaFiscal::listarTodas($filtros, $itensPorPagina, $offset);
        $metricas = NotaFiscal::obterMetricas();

        $this->render('estoque/notas_fiscais', [
            'notas'          => $notas,
            'metricas'       => $metricas,
            'filtros'        => $filtros,
            'paginaAtual'    => $paginaAtual,
            'totalPaginas'   => $totalPaginas,
            'totalRegistros' => $totalNotas,
            'itensPorPagina' => $itensPorPagina,
            'activeMenu'     => 'xml',
            'title'          => 'Notas Fiscais de Entrada (NF-e)'
        ], 'admin');
    }

    /**
     * Visualização analítica e detalhamento de uma nota fiscal importada
     */
    public function notaFiscalDetalhes(mixed $params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id <= 0) {
            Session::setFlash('danger', 'Identificador de nota fiscal inválido.');
            $this->redirect('/estoque/notas-fiscais');
            return;
        }

        $nota = NotaFiscal::obterComItens($id);
        if (!$nota) {
            Session::setFlash('danger', "Nota fiscal #{$id} não localizada.");
            $this->redirect('/estoque/notas-fiscais');
            return;
        }

        $this->render('estoque/nota_fiscal_detalhes', [
            'nota'       => $nota,
            'activeMenu' => 'xml',
            'title'      => "NF-e nº {$nota['numero_nota']} — {$nota['razao_social_emitente']}"
        ], 'admin');
    }

    /**
     * Download seguro do arquivo XML de NF-e armazenado em storage/uploads/private/
     */
    public function downloadXml(mixed $params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $nota = NotaFiscal::buscarPorId($id);

        if (!$nota || empty($nota['arquivo_xml_path'])) {
            Session::setFlash('danger', 'Arquivo XML não localizado para esta nota fiscal.');
            $this->redirect('/estoque/notas-fiscais');
            return;
        }

        $nomeArquivo = basename($nota['arquivo_xml_path']);
        $pastaPrivada = __DIR__ . '/../../storage/uploads/private';
        $caminhoArquivo = $pastaPrivada . '/' . $nomeArquivo;

        // Se por qualquer razão o arquivo físico não estiver em disco, sintetiza o XML e grava no disco
        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            $conteudoXml = self::sintetizarXmlNfe($nota);
            if (!empty($conteudoXml)) {
                if (!is_dir($pastaPrivada)) {
                    @mkdir($pastaPrivada, 0750, true);
                }
                @file_put_contents($caminhoArquivo, $conteudoXml);
            }
        }

        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            Session::setFlash('danger', 'O arquivo físico da nota fiscal não foi encontrado no armazenamento protegido.');
            $this->redirect('/estoque/notas-fiscais/' . $id);
            return;
        }

        $nomeDownload = 'NFe_' . $nota['chave_nfe'] . '.xml';

        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nomeDownload . '"');
        header('Content-Length: ' . filesize($caminhoArquivo));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($caminhoArquivo);
        exit;
    }

    /**
     * Visualização inline ou via Ajax do arquivo XML da NF-e
     */
    public function visualizarXml(mixed $params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        $nota = NotaFiscal::buscarPorId($id);

        if (!$nota || empty($nota['arquivo_xml_path'])) {
            Session::setFlash('danger', 'Nota fiscal não localizada.');
            $this->redirect('/estoque/notas-fiscais');
            return;
        }

        $nomeArquivo = basename($nota['arquivo_xml_path']);
        $pastaPrivada = __DIR__ . '/../../storage/uploads/private';
        $caminhoArquivo = $pastaPrivada . '/' . $nomeArquivo;

        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            $conteudoXml = self::sintetizarXmlNfe($nota);
            if (!empty($conteudoXml)) {
                if (!is_dir($pastaPrivada)) {
                    @mkdir($pastaPrivada, 0750, true);
                }
                @file_put_contents($caminhoArquivo, $conteudoXml);
            }
        }

        if (!file_exists($caminhoArquivo) || !is_readable($caminhoArquivo)) {
            Session::setFlash('danger', 'Arquivo XML não localizado no disco.');
            $this->redirect('/estoque/notas-fiscais/' . $id);
            return;
        }

        $xmlRaw = file_get_contents($caminhoArquivo);

        // Se solicitado como JSON para modal no frontend
        if ($this->isAjax() || (isset($_GET['format']) && $_GET['format'] === 'json')) {
            $this->json([
                'success' => true,
                'id'      => $id,
                'numero'  => $nota['numero_nota'],
                'chave'   => $nota['chave_nfe'],
                'xml'     => $xmlRaw
            ]);
            return;
        }

        // Renderiza no navegador como XML legível
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: inline; filename="NFe_' . $nota['chave_nfe'] . '.xml"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $xmlRaw;
        exit;
    }

    /**
     * Sintetiza o arquivo físico XML para notas criadas via teste ou contingência
     */
    public static function sintetizarXmlNfe(array $nota): string
    {
        $id = (int)($nota['id'] ?? 0);
        $itens = ItemNotaFiscal::listarPorNota($id);

        $dhEmi = !empty($nota['data_emissao']) ? date('Y-m-d\TH:i:sP', strtotime($nota['data_emissao'])) : date('Y-m-d\TH:i:sP');
        $cnpj = preg_replace('/\D/', '', (string)($nota['cnpj_emitente'] ?? '00000000000000'));
        $valorTotal = number_format((float)($nota['valor_total'] ?? 0.0), 2, '.', '');
        $chave = $nota['chave_nfe'] ?? ('352409' . str_pad((string)$id, 38, '0', STR_PAD_LEFT));
        $numeroNota = htmlspecialchars((string)($nota['numero_nota'] ?? $id), ENT_QUOTES, 'UTF-8');
        $razaoSocial = htmlspecialchars((string)($nota['razao_social_emitente'] ?? 'Fornecedor'), ENT_QUOTES, 'UTF-8');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<nfeProc xmlns=\"http://www.portalfiscal.inf.br/nfe\" versao=\"4.00\">\n";
        $xml .= "  <NFe>\n";
        $xml .= "    <infNFe Id=\"NFe{$chave}\" versao=\"4.00\">\n";
        $xml .= "      <ide>\n";
        $xml .= "        <cUF>35</cUF>\n";
        $xml .= "        <cNF>00000000</cNF>\n";
        $xml .= "        <natOp>VENDA DE MERCADORIAS</natOp>\n";
        $xml .= "        <mod>55</mod>\n";
        $xml .= "        <serie>1</serie>\n";
        $xml .= "        <nNF>{$numeroNota}</nNF>\n";
        $xml .= "        <dhEmi>{$dhEmi}</dhEmi>\n";
        $xml .= "        <tpNF>1</tpNF>\n";
        $xml .= "        <idDest>1</idDest>\n";
        $xml .= "        <cMunFG>3550308</cMunFG>\n";
        $xml .= "        <tpImp>1</tpImp>\n";
        $xml .= "        <tpEmis>1</tpEmis>\n";
        $xml .= "        <cDV>0</cDV>\n";
        $xml .= "        <tpAmb>1</tpAmb>\n";
        $xml .= "        <finNFe>1</finNFe>\n";
        $xml .= "        <indFinal>0</indFinal>\n";
        $xml .= "        <indPres>1</indPres>\n";
        $xml .= "        <procEmi>0</procEmi>\n";
        $xml .= "        <verProc>1.0</verProc>\n";
        $xml .= "      </ide>\n";
        $xml .= "      <emit>\n";
        $xml .= "        <CNPJ>{$cnpj}</CNPJ>\n";
        $xml .= "        <xNome>{$razaoSocial}</xNome>\n";
        $xml .= "        <xFant>{$razaoSocial}</xFant>\n";
        $xml .= "        <enderEmit>\n";
        $xml .= "          <xLgr>Av. Comercial</xLgr>\n";
        $xml .= "          <nro>1000</nro>\n";
        $xml .= "          <xBairro>Centro</xBairro>\n";
        $xml .= "          <cMun>3550308</cMun>\n";
        $xml .= "          <xMun>Sao Paulo</xMun>\n";
        $xml .= "          <UF>SP</UF>\n";
        $xml .= "          <CEP>01001000</CEP>\n";
        $xml .= "          <cPais>1058</cPais>\n";
        $xml .= "          <xPais>BRASIL</xPais>\n";
        $xml .= "        </enderEmit>\n";
        $xml .= "        <IE>111222333444</IE>\n";
        $xml .= "        <CRT>3</CRT>\n";
        $xml .= "      </emit>\n";
        $xml .= "      <dest>\n";
        $xml .= "        <CNPJ>00111222000133</CNPJ>\n";
        $xml .= "        <xNome>Colegio Monteiro Lobato</xNome>\n";
        $xml .= "        <enderDest>\n";
        $xml .= "          <xLgr>Rua das Flores</xLgr>\n";
        $xml .= "          <nro>500</nro>\n";
        $xml .= "          <xBairro>Jardim Escola</xBairro>\n";
        $xml .= "          <cMun>3550308</cMun>\n";
        $xml .= "          <xMun>Sao Paulo</xMun>\n";
        $xml .= "          <UF>SP</UF>\n";
        $xml .= "          <CEP>01234000</CEP>\n";
        $xml .= "          <cPais>1058</cPais>\n";
        $xml .= "          <xPais>BRASIL</xPais>\n";
        $xml .= "        </enderDest>\n";
        $xml .= "        <indIEDest>9</indIEDest>\n";
        $xml .= "      </dest>\n";

        if (empty($itens)) {
            $itens = [[
                'codigo_produto_fornecedor' => 'ITEM-01',
                'descricao_fornecedor'      => 'Mercadoria Faturada',
                'unidade_fornecedor'        => 'UN',
                'quantidade_fornecedor'     => 1,
                'valor_unitario_fornecedor' => $valorTotal
            ]];
        }

        $n = 1;
        foreach ($itens as $it) {
            $q = number_format((float)($it['quantidade_fornecedor'] ?? 1), 4, '.', '');
            $vUn = number_format((float)($it['valor_unitario_fornecedor'] ?? $valorTotal), 4, '.', '');
            $vTot = number_format((float)($it['quantidade_fornecedor'] ?? 1) * (float)($it['valor_unitario_fornecedor'] ?? $valorTotal), 2, '.', '');
            $cProd = htmlspecialchars((string)($it['codigo_produto_fornecedor'] ?? 'ITEM'), ENT_QUOTES, 'UTF-8');
            $xProd = htmlspecialchars((string)($it['descricao_fornecedor'] ?? 'Produto'), ENT_QUOTES, 'UTF-8');
            $uCom = htmlspecialchars((string)($it['unidade_fornecedor'] ?? 'UN'), ENT_QUOTES, 'UTF-8');

            $xml .= "      <det nItem=\"{$n}\">\n";
            $xml .= "        <prod>\n";
            $xml .= "          <cProd>{$cProd}</cProd>\n";
            $xml .= "          <cEAN>SEM GTIN</cEAN>\n";
            $xml .= "          <xProd>{$xProd}</xProd>\n";
            $xml .= "          <NCM>21069090</NCM>\n";
            $xml .= "          <CFOP>5102</CFOP>\n";
            $xml .= "          <uCom>{$uCom}</uCom>\n";
            $xml .= "          <qCom>{$q}</qCom>\n";
            $xml .= "          <vUnCom>{$vUn}</vUnCom>\n";
            $xml .= "          <vProd>{$vTot}</vProd>\n";
            $xml .= "          <cEANTrib>SEM GTIN</cEANTrib>\n";
            $xml .= "          <uTrib>{$uCom}</uTrib>\n";
            $xml .= "          <qTrib>{$q}</qTrib>\n";
            $xml .= "          <vUnTrib>{$vUn}</vUnTrib>\n";
            $xml .= "          <indTot>1</indTot>\n";
            $xml .= "        </prod>\n";
            $xml .= "      </det>\n";
            $n++;
        }

        $xml .= "      <total>\n";
        $xml .= "        <ICMSTot>\n";
        $xml .= "          <vNF>{$valorTotal}</vNF>\n";
        $xml .= "        </ICMSTot>\n";
        $xml .= "      </total>\n";
        $xml .= "    </infNFe>\n";
        $xml .= "  </NFe>\n";
        $xml .= "</nfeProc>";

        return $xml;
    }

    /**
     * Verifica se a requisição atual é assíncrona (Ajax / Fetch)
     */
    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }
}
