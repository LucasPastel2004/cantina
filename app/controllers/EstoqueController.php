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

        $produtos   = Estoque::listarSaldos($filtros);
        $metricas   = Estoque::obterMetricasGerais();
        $categorias = Categoria::listarTodas();

        $this->render('estoque/index', [
            'produtos'   => $produtos,
            'metricas'   => $metricas,
            'categorias' => $categorias,
            'filtros'    => $filtros,
            'activeMenu' => 'estoque',
            'title'      => 'Controle de Estoque & Saldos'
        ], 'admin');
    }

    /**
     * Extrato analítico e cronológico de movimentações de estoque
     */
    public function movimentacoes(): void
    {
        $produtoId = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;
        $tipoMov   = isset($_GET['tipo']) ? trim($_GET['tipo']) : null;
        $motivo    = isset($_GET['motivo']) ? trim($_GET['motivo']) : null;
        $dataIni   = isset($_GET['data_inicio']) ? trim($_GET['data_inicio']) : null;
        $dataFim   = isset($_GET['data_fim']) ? trim($_GET['data_fim']) : null;
        $busca     = isset($_GET['busca']) ? trim($_GET['busca']) : '';

        $filtros = [
            'produto_id'        => $produtoId,
            'tipo_movimentacao' => $tipoMov,
            'motivo_perda'      => $motivo,
            'data_inicio'       => $dataIni,
            'data_fim'          => $dataFim,
            'busca'             => $busca,
            'limite'            => 100
        ];

        $movimentacoes = Estoque::listarMovimentacoes($filtros);
        $produtos      = Produto::listarTodos();

        $this->render('estoque/movimentacoes', [
            'movimentacoes' => $movimentacoes,
            'produtos'      => $produtos,
            'filtros'       => $filtros,
            'tiposMov'      => Estoque::TIPOS_MOVIMENTACAO,
            'motivosPerda'  => Estoque::MOTIVOS_PERDA,
            'activeMenu'    => 'estoque',
            'title'         => 'Extrato de Movimentações de Estoque'
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
        $this->validateCsrf();

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
        $this->validateCsrf();

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
        $this->validateCsrf();

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
        $this->validateCsrf();

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
            'data_fim'    => $dataFim,
            'limite'      => 50
        ];

        $notas    = NotaFiscal::listarTodas($filtros);
        $metricas = NotaFiscal::obterMetricas();

        $this->render('estoque/notas_fiscais', [
            'notas'      => $notas,
            'metricas'   => $metricas,
            'filtros'    => $filtros,
            'activeMenu' => 'xml',
            'title'      => 'Notas Fiscais de Entrada (NF-e)'
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

        $caminhoArquivo = __DIR__ . '/../../storage/uploads/private/' . $nota['arquivo_xml_path'];

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
     * Verifica se a requisição atual é assíncrona (Ajax / Fetch)
     */
    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }
}
