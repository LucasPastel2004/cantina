<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Controlador: EstornoVendasController (Módulo Operacional de Estorno de Vendas)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e docs/alterações sistema guideline/extorno de vendas.pdf
 * Fase 19 — Módulo Completo (CRUD) de Estorno de Vendas e Invalidação
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Models\VendaBalcao;
use App\Models\Pedido;
use App\Models\ItemVendaBalcao;
use App\Models\ItemPedido;
use App\Models\Aluno;
use App\Models\Colaborador;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class EstornoVendasController extends Controller
{
    /**
     * Tela operacional de consulta e seleção de vendas para estorno
     */
    public function index(): void
    {
        // Parâmetros de filtro
        $vendaAvulsa = (isset($_GET['venda_avulsa']) && $_GET['venda_avulsa'] === 'sim') ? 'sim' : 'nao';
        $filtrarPor = (isset($_GET['filtrar_por']) && $_GET['filtrar_por'] === 'colaborador') ? 'colaborador' : 'aluno';
        
        $pessoaId = !empty($_GET['pessoa_id']) ? (int)$_GET['pessoa_id'] : null;
        $alunoId = !empty($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : ($filtrarPor === 'aluno' ? $pessoaId : null);
        $colaboradorId = !empty($_GET['colaborador_id']) ? (int)$_GET['colaborador_id'] : ($filtrarPor === 'colaborador' ? $pessoaId : null);
        
        $hoje = date('Y-m-d');
        $dataInicio = !empty($_GET['data_inicio']) ? trim(strip_tags($_GET['data_inicio'])) : $hoje;
        $dataFim = !empty($_GET['data_fim']) ? trim(strip_tags($_GET['data_fim'])) : $hoje;
        $motivoFiltro = !empty($_GET['motivo_estorno']) ? trim(strip_tags($_GET['motivo_estorno'])) : (!empty($_GET['motivo']) ? trim(strip_tags($_GET['motivo'])) : '1-ESTORNO');

        // Flag de carregamento sob demanda (Fluxo Nutre: só busca se clicar em Carregar ou vier de gravação)
        $carregado = isset($_GET['carregar']) || isset($_GET['data_inicio']);

        // Monta array de filtros
        $filtros = [
            'data_inicio'    => $dataInicio,
            'data_fim'       => $dataFim,
            'filtrar_por'    => $filtrarPor,
            'aluno_id'       => $alunoId,
            'colaborador_id' => $colaboradorId,
            'pessoa_id'      => $pessoaId,
            'venda_avulsa'   => $vendaAvulsa,
        ];

        // Consulta vendas elegíveis para estorno se o usuário acionou Carregar
        $vendas = [];
        if ($carregado) {
            if ($vendaAvulsa === 'sim') {
                // Venda avulsa de balcão: pessoas de fora da escola, anonimato ou compra de sobra de estoque
                $vendas = VendaBalcao::listarParaEstorno($filtros);
            } else {
                // Membros cadastrados da comunidade escolar: Alunos ou Colaboradores
                if ($filtrarPor === 'colaborador') {
                    $vendas = VendaBalcao::listarParaEstorno($filtros);
                } else {
                    // Alunos: busca encomendas online e vendas de balcão do aluno
                    $pedidos = Pedido::listarParaEstorno($filtros);
                    $vendasBalcao = VendaBalcao::listarParaEstorno($filtros);
                    $vendas = array_merge($pedidos, $vendasBalcao);

                    usort($vendas, function ($a, $b) {
                        $dtA = ($a['data_venda'] ?? '') . ' ' . ($a['hora_venda'] ?? '');
                        $dtB = ($b['data_venda'] ?? '') . ' ' . ($b['hora_venda'] ?? '');
                        return strcmp($dtB, $dtA);
                    });
                }
            }
        }

        // Listas para os filtros da tela
        $alunos = Aluno::listarParaSelecao();
        $colaboradores = Colaborador::listarParaSelecao();

        // Localiza nome da pessoa selecionada caso haja filtro ativo
        $nomePessoaSelecionada = '';
        if ($filtrarPor === 'colaborador' && $colaboradorId) {
            foreach ($colaboradores as $col) {
                if ((int)$col['id'] === $colaboradorId) {
                    $nomePessoaSelecionada = $col['nome'] . ' (' . ($col['matricula_funcional'] ?: 'S/M') . ' - ' . ($col['cargo'] ?? '') . ')';
                    break;
                }
            }
        } elseif ($alunoId) {
            foreach ($alunos as $al) {
                if ((int)$al['id'] === $alunoId) {
                    $nomePessoaSelecionada = $al['nome'] . ' (' . ($al['matricula'] ?: 'S/M') . ' - ' . ($al['nome_turma'] ?? '') . ')';
                    break;
                }
            }
        }

        $this->render('operacional/estorno_vendas', [
            'activeMenu'            => 'estornos',
            'pageTitle'             => 'Estorno de Venda da Cantina',
            'pageHeading'           => 'Estorno de Venda da Cantina',
            'vendaAvulsa'           => $vendaAvulsa,
            'filtrarPor'            => $filtrarPor,
            'alunoId'               => $alunoId,
            'colaboradorId'         => $colaboradorId,
            'pessoaId'              => ($filtrarPor === 'colaborador' ? $colaboradorId : $alunoId),
            'nomePessoaSelecionada' => $nomePessoaSelecionada,
            'dataInicio'            => $dataInicio,
            'dataFim'               => $dataFim,
            'motivoFiltro'          => $motivoFiltro,
            'carregado'             => $carregado,
            'vendas'                => $vendas,
            'alunos'                => $alunos,
            'colaboradores'         => $colaboradores,
            'totalVendas'           => count($vendas),
            'user'                  => Auth::user()
        ], 'admin');
    }

    /**
     * Retorna os detalhes e itens consumidos de uma venda via AJAX para o modal de inspeção
     */
    public function detalhesAjax(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $tipo = trim($_GET['tipo'] ?? '');

        if ($id <= 0) {
            $this->json(['sucesso' => false, 'mensagem' => 'Identificador de venda inválido.'], 400);
            return;
        }

        $vendaBalcao = null;
        $pedidoOnline = null;

        // Se o tipo especificado for pedido ou encomenda
        if ($tipo === 'pedido' || $tipo === 'encomenda' || $tipo === 'nao') {
            $pedidoOnline = Pedido::buscarDetalhadoPorId($id) ?? Pedido::buscarPorId($id);
            if (!$pedidoOnline) {
                // Fallback defensivo: se não achar em pedidos, procura em balcão
                $vendaBalcao = VendaBalcao::buscarPorId($id);
            }
        } else {
            // Padrão balcão
            $vendaBalcao = VendaBalcao::buscarPorId($id);
            if (!$vendaBalcao) {
                // Fallback defensivo: se não achou em balcão, procura em pedidos
                $pedidoOnline = Pedido::buscarDetalhadoPorId($id) ?? Pedido::buscarPorId($id);
            }
        }

        if ($vendaBalcao) {
            $itens = ItemVendaBalcao::listarPorVenda($id);
            $this->json([
                'sucesso'  => true,
                'dados'    => $vendaBalcao,
                'tipo'     => 'balcao',
                'venda'    => [
                    'id'              => $vendaBalcao['id'],
                    'identificador'   => "Venda Balcão #{$vendaBalcao['id']}",
                    'data_hora'       => date('d/m/Y H:i', strtotime($vendaBalcao['created_at'])),
                    'valor_total'     => (float)$vendaBalcao['valor_total'],
                    'forma_pagamento' => $vendaBalcao['forma_pagamento'],
                    'tipo_venda'      => $vendaBalcao['tipo_venda'] ?? 'avulso_aluno',
                    'status'          => $vendaBalcao['status'] ?? 'concluida',
                    'pessoa_nome'     => $vendaBalcao['colaborador_nome'] ?? ($vendaBalcao['aluno_nome'] ?? 'Consumidor Avulso'),
                    'operador_nome'   => $vendaBalcao['operador_nome'] ?? 'Operador'
                ],
                'itens'    => $itens
            ]);
            return;
        }

        if ($pedidoOnline) {
            $itens = $pedidoOnline['itens'] ?? ItemPedido::listarPorPedido($id);
            $this->json([
                'sucesso'  => true,
                'dados'    => $pedidoOnline,
                'tipo'     => 'pedido',
                'venda'    => [
                    'id'              => $pedidoOnline['id'],
                    'identificador'   => "Pedido #{$pedidoOnline['codigo_pedido']}",
                    'data_hora'       => date('d/m/Y', strtotime($pedidoOnline['data_consumo'])) . " (" . ucfirst($pedidoOnline['turno_consumo'] ?? 'matutino') . ")",
                    'data_consumo'    => $pedidoOnline['data_consumo'],
                    'created_at'      => $pedidoOnline['created_at'],
                    'valor_total'     => (float)$pedidoOnline['valor_total'],
                    'forma_pagamento' => 'Saldo da Carteira Familiar',
                    'tipo_venda'      => 'agendamento_online',
                    'status'          => $pedidoOnline['status'],
                    'pessoa_nome'     => $pedidoOnline['aluno_nome'] ?? 'Aluno',
                    'aluno_matricula' => $pedidoOnline['aluno_matricula'] ?? '',
                    'aluno_turma'     => $pedidoOnline['nome_turma'] ?? '',
                    'responsavel'     => $pedidoOnline['responsavel_nome'] ?? 'Responsável Titular'
                ],
                'itens'    => $itens
            ]);
            return;
        }

        $this->json(['sucesso' => false, 'mensagem' => "Venda ou pedido #{$id} não localizado no sistema."], 404);
    }

    /**
     * Processa o estorno atômico em lote das vendas selecionadas
     */
    public function gravar(): void
    {
        // Validação de CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            Session::setFlash('danger', 'Token de segurança CSRF expirado. Recarregue a página e tente novamente.');
            $this->redirectBack();
            return;
        }

        $operador = Auth::user();
        $operadorId = (int)($operador['id'] ?? 1);
        $ipOrigem = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $tipoVenda = trim($_POST['tipo_venda'] ?? 'nao'); // 'sim' = avulsa, 'nao' = pedido
        $selecionados = $_POST['selecionados'] ?? [];
        $motivo = trim($_POST['motivo_estorno'] ?? '');

        // Validações de entrada
        if (!is_array($selecionados) || empty($selecionados)) {
            Session::setFlash('warning', 'Atenção: Nenhuma venda foi selecionada para estorno.');
            $this->redirectBack();
            return;
        }

        if (mb_strlen($motivo) < 5) {
            Session::setFlash('warning', 'Atenção: O motivo do estorno é obrigatório e deve conter no mínimo 5 caracteres.');
            $this->redirectBack();
            return;
        }

        $totalEstornados = 0;
        $valorTotalEstornado = 0.0;
        $erros = [];

        $filtrarPor = trim($_POST['filtrar_por'] ?? 'aluno');

        foreach ($selecionados as $vendaItem) {
            $itemTipo = '';
            if (is_string($vendaItem) && str_contains($vendaItem, '_')) {
                [$prefixo, $idStr] = explode('_', $vendaItem, 2);
                $id = (int)$idStr;
                $itemTipo = ($prefixo === 'balcao') ? 'balcao' : 'pedido';
            } else {
                $id = (int)$vendaItem;
                $itemTipo = ($tipoVenda === 'sim' || ($filtrarPor === 'colaborador')) ? 'balcao' : 'pedido';
            }

            if ($id <= 0) continue;

            try {
                if ($itemTipo === 'balcao') {
                    // Estorno de Venda de Balcão (avulsa, sobra de estoque ou colaborador)
                    $vendaEstornada = VendaBalcao::estornarVenda($id, $operadorId, $motivo, $ipOrigem);
                    $totalEstornados++;
                    $valorTotalEstornado += (float)$vendaEstornada['valor_total'];
                } else {
                    // Estorno de Pedido Online
                    $pedidoEstornado = Pedido::estornarPedido($id, $operadorId, $motivo, $ipOrigem);
                    $totalEstornados++;
                    $valorTotalEstornado += (float)$pedidoEstornado['valor_total'];
                }
            } catch (\Throwable $e) {
                // Fallback defensivo: se falhar como pedido, tenta como balcão
                if ($itemTipo === 'pedido') {
                    try {
                        $vendaEstornada = VendaBalcao::estornarVenda($id, $operadorId, $motivo, $ipOrigem);
                        $totalEstornados++;
                        $valorTotalEstornado += (float)$vendaEstornada['valor_total'];
                        continue;
                    } catch (\Throwable $e2) {
                        // mantém erro original
                    }
                }
                $erros[] = "Venda #{$id}: " . $e->getMessage();
            }
        }

        if ($totalEstornados > 0) {
            $msgSucesso = sprintf(
                "Estorno concluído com sucesso! %d venda(s) estornada(s), totalizando R$ %s devolvidos.",
                $totalEstornados,
                number_format($valorTotalEstornado, 2, ',', '.')
            );
            if (!empty($erros)) {
                $msgSucesso .= " (Algumas vendas apresentaram erros: " . implode('; ', $erros) . ")";
            }
            Session::setFlash('success', $msgSucesso);
        } else {
            Session::setFlash('danger', 'Nenhuma venda pôde ser estornada. ' . implode('; ', $erros));
        }

        // Redireciona mantendo os filtros
        $queryParams = http_build_query([
            'venda_avulsa' => $tipoVenda,
            'filtrar_por'  => $_POST['filtrar_por'] ?? 'aluno',
            'aluno_id'     => $_POST['aluno_id'] ?? '',
            'data_inicio'  => $_POST['data_inicio'] ?? date('Y-m-d'),
            'data_fim'     => $_POST['data_fim'] ?? date('Y-m-d'),
        ]);

        $this->redirect('/operacional/estornos?' . $queryParams);
    }

    /**
     * Redireciona para a página anterior ou para a listagem padrão
     */
    private function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/operacional/estornos';
        $this->redirect($referer);
    }
}
