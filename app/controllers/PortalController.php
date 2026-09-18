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
use App\Models\Produto;
use App\Models\Pedido;
use App\Models\CalendarioEscolar;
use App\Models\TransacaoCarteira;

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

    /**
     * Tela de Agendamento Semanal de Lanches (FSD Tela 2.3 & Fluxo 3)
     */
    public function agendamento(): void
    {
        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];

        $resumoCarteira = TransacaoCarteira::obterResumoCarteira($respId);
        Session::set('_auth_saldo', $resumoCarteira['saldo_disponivel']);

        // Se a conta estiver bloqueada por MED, impede novos agendamentos
        if (!empty($resumoCarteira['bloqueado_med'])) {
            Session::setFlash('danger', 'Sua conta familiar está temporariamente suspensa por contestação bancária (MED). Novos agendamentos estão bloqueados.');
            $this->redirect('/portal/carteira');
            return;
        }

        $dependentes = Aluno::listarPorResponsavel($respId, false);
        if (empty($dependentes)) {
            Session::setFlash('info', 'Cadastre ou aguarde a importação de seus dependentes para agendar lanches.');
            $this->redirect('/portal');
            return;
        }

        // 1. Aluno selecionado
        $alunoIdSolicitado = (int)$this->input('aluno', 0);
        $alunoSelecionado = null;
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

        $alunoId = (int)$alunoSelecionado['id'];
        $turnoAluno = $alunoSelecionado['turno'] ?? 'matutino';
        $alergenosAlunoIds = Aluno::obterIdsAlergenos($alunoId);

        // 2. Semanas letivas (semana atual e opcionalmente semana seguinte se liberada)
        $semanaRef = $this->input('semana', 'atual') === 'seguinte' ? 'seguinte' : 'atual';
        $semanaSeguinteLiberada = CalendarioEscolar::isSemanaSeguinteLiberada();

        if ($semanaRef === 'seguinte' && !$semanaSeguinteLiberada) {
            Session::setFlash('info', 'O cardápio da próxima semana só é liberado na sexta-feira às 18:00.');
            $semanaRef = 'atual';
        }

        $diasSemana = CalendarioEscolar::obterDiasSemanaLetiva($semanaRef);

        // Agrega status de corte de cada dia para o turno do aluno
        foreach ($diasSemana as &$dia) {
            $dia['status_corte'] = Pedido::obterStatusCorte($dia['data'], $turnoAluno);
        }
        unset($dia);

        // 3. Data selecionada
        $dataSolicitada = $this->input('data', '');
        $diaSelecionado = null;

        if (!empty($dataSolicitada)) {
            foreach ($diasSemana as $dia) {
                if ($dia['data'] === $dataSolicitada) {
                    $diaSelecionado = $dia;
                    break;
                }
            }
        }

        // Se não selecionou dia ou dia inválido, escolhe o primeiro dia letivo aberto (ou o primeiro dia da lista)
        if (!$diaSelecionado) {
            foreach ($diasSemana as $dia) {
                if ($dia['is_letivo'] && $dia['status_corte']['pode_alterar']) {
                    $diaSelecionado = $dia;
                    break;
                }
            }
        }
        if (!$diaSelecionado) {
            $diaSelecionado = $diasSemana[0];
        }

        $dataConsumo = $diaSelecionado['data'];
        $statusCorteDia = $diaSelecionado['status_corte'];

        // 4. Produtos do catálogo disponíveis para o turno do aluno
        $produtos = Produto::listarParaCardapio($turnoAluno);

        // Marca para cada produto se ele tem conflito de alérgeno com o aluno
        foreach ($produtos as &$prod) {
            $conflitos = [];
            foreach ($prod['alergenos'] as $alerg) {
                if (in_array((int)$alerg['id'], $alergenosAlunoIds, true)) {
                    $conflitos[] = $alerg['nome'];
                }
            }
            $prod['tem_conflito_alergia'] = !empty($conflitos);
            $prod['conflitos_nomes'] = $conflitos;
        }
        unset($prod);

        // Agrupa produtos por categoria
        $produtosPorCategoria = [];
        foreach ($produtos as $prod) {
            $cat = $prod['categoria_nome'] ?? 'Outros';
            $produtosPorCategoria[$cat][] = $prod;
        }

        // 5. Verifica se o aluno já possui pedido agendado nesta data
        $pedidoExistente = null;
        $sqlPedExist = "SELECT id, codigo_pedido, valor_total, status 
                        FROM pedidos 
                        WHERE aluno_id = :aluno_id AND data_consumo = :data AND status IN ('agendado', 'editado')
                        LIMIT 1";
        $pedidoExistente = Pedido::fetchOne($sqlPedExist, [
            'aluno_id' => $alunoId,
            'data'     => $dataConsumo
        ]);

        $this->render('portal/agendamento', [
            'pageTitle'              => 'Agendamento Semanal de Lanches — Portal da Família',
            'activeMenu'             => 'agendamento',
            'usuario'                => $usuario,
            'user'                   => $usuario,
            'responsavelNome'        => $resumoCarteira['responsavel_nome'] ?? $usuario['nome'] ?? 'Responsável',
            'saldoAtual'             => $resumoCarteira['saldo_atual'],
            'saldoDisponivel'        => $resumoCarteira['saldo_disponivel'],
            'saldoReservado'         => $resumoCarteira['saldo_reservado'],
            'dependentes'            => $dependentes,
            'alunoSelecionado'       => $alunoSelecionado,
            'diasSemana'             => $diasSemana,
            'diaSelecionado'         => $diaSelecionado,
            'semanaRef'              => $semanaRef,
            'semanaSeguinteLiberada' => $semanaSeguinteLiberada,
            'statusCorte'            => $statusCorteDia,
            'produtosPorCategoria'   => $produtosPorCategoria,
            'produtos'               => $produtos,
            'pedidoExistente'        => $pedidoExistente,
        ], 'portal');
    }

    /**
     * Processa a criação e finalização atômica do pedido de lanche
     */
    public function agendamentoSalvar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Por favor, tente novamente.');
            $this->redirect('/portal/agendamento');
            return;
        }

        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];

        $alunoId     = (int)$this->input('aluno_id', 0);
        $dataConsumo = trim((string)$this->input('data_consumo', ''));
        $itensPost   = (array)($this->input('itens', []) ?: []);

        // Formata itens [produto_id => quantidade] para [['produto_id' => ..., 'quantidade' => ...]]
        $itensFormatados = [];
        foreach ($itensPost as $prodId => $qtd) {
            $prodId = (int)$prodId;
            $qtd = (int)$qtd;
            if ($prodId > 0 && $qtd > 0) {
                $itensFormatados[] = [
                    'produto_id' => $prodId,
                    'quantidade' => $qtd
                ];
            }
        }

        if (empty($itensFormatados)) {
            Session::setFlash('warning', 'Selecione pelo menos um item de lanche para agendar.');
            $this->redirect("/portal/agendamento?aluno={$alunoId}&data={$dataConsumo}");
            return;
        }

        try {
            $resultado = Pedido::criarPedido($respId, $alunoId, $dataConsumo, $itensFormatados);

            $dataFormatada = date('d/m/Y', strtotime($dataConsumo));
            Session::setFlash(
                'success', 
                "Pedido #{$resultado['codigo_pedido']} agendado com sucesso para {$resultado['aluno_nome']} no dia {$dataFormatada}!"
            );
            $this->redirect('/portal/pedidos');
        } catch (\DomainException $e) {
            Session::setFlash('danger', $e->getMessage());
            $this->redirect("/portal/agendamento?aluno={$alunoId}&data={$dataConsumo}");
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Ocorreu uma falha ao processar o agendamento. Tente novamente.');
            $this->redirect("/portal/agendamento?aluno={$alunoId}&data={$dataConsumo}");
        }
    }

    /**
     * Tela de Meus Pedidos com histórico, status de corte e botões de ação (FSD Tela 2.4 & Fluxo 4)
     */
    public function pedidos(): void
    {
        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];

        $resumoCarteira = TransacaoCarteira::obterResumoCarteira($respId);
        Session::set('_auth_saldo', $resumoCarteira['saldo_disponivel']);

        $dependentes = Aluno::listarPorResponsavel($respId, false);

        // Filtros
        $alunoFiltro = (int)$this->input('aluno', 0);
        $dataInicio  = $this->input('data_inicio', '');
        $dataFim     = $this->input('data_fim', '');

        $pedidos = Pedido::listarPorResponsavel(
            $respId, 
            $alunoFiltro > 0 ? $alunoFiltro : null, 
            !empty($dataInicio) ? $dataInicio : null, 
            !empty($dataFim) ? $dataFim : null
        );

        $this->render('portal/meus_pedidos', [
            'pageTitle'       => 'Meus Pedidos — Portal da Família',
            'activeMenu'      => 'pedidos',
            'usuario'         => $usuario,
            'user'            => $usuario,
            'responsavelNome' => $resumoCarteira['responsavel_nome'] ?? $usuario['nome'] ?? 'Responsável',
            'saldoAtual'      => $resumoCarteira['saldo_atual'],
            'saldoDisponivel' => $resumoCarteira['saldo_disponivel'],
            'saldoReservado'  => $resumoCarteira['saldo_reservado'],
            'dependentes'     => $dependentes,
            'alunoFiltro'     => $alunoFiltro,
            'dataInicio'      => $dataInicio,
            'dataFim'         => $dataFim,
            'pedidos'         => $pedidos,
        ], 'portal');
    }

    /**
     * Processa o cancelamento de um pedido com estorno de 100% para a carteira familiar
     */
    public function pedidoCancelar(): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect('/portal/pedidos');
            return;
        }

        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];
        $pedidoId = (int)$this->input('pedido_id', 0);

        try {
            $resultado = Pedido::cancelarPedido($pedidoId, $respId);
            Session::setFlash(
                'success', 
                "Pedido #{$resultado['codigo_pedido']} cancelado com sucesso! Valor de R$ " . number_format($resultado['valor_estorno'], 2, ',', '.') . " estornado para sua carteira."
            );
        } catch (\DomainException $e) {
            Session::setFlash('danger', $e->getMessage());
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Não foi possível cancelar o pedido. Tente novamente.');
        }

        $this->redirect('/portal/pedidos');
    }

    /**
     * Tela de Edição de Pedido antes do corte operacional
     */
    public function pedidoEditar(int $id): void
    {
        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];

        $resumoCarteira = TransacaoCarteira::obterResumoCarteira($respId);
        Session::set('_auth_saldo', $resumoCarteira['saldo_disponivel']);

        $pedido = Pedido::buscarDetalhado($id, $respId);

        if (!$pedido) {
            Session::setFlash('danger', 'Pedido não localizado ou não pertence à sua conta.');
            $this->redirect('/portal/pedidos');
            return;
        }

        if ($pedido['status'] === Pedido::STATUS_CANCELADO || $pedido['status'] === Pedido::STATUS_ENTREGUE) {
            Session::setFlash('warning', 'Este pedido não pode ser editado pois já foi finalizado ou cancelado.');
            $this->redirect('/portal/pedidos');
            return;
        }

        if (!$pedido['status_corte']['pode_alterar']) {
            Session::setFlash('danger', "Edição bloqueada: O horário de corte ({$pedido['status_corte']['hora_corte']}) já foi encerrado.");
            $this->redirect('/portal/pedidos');
            return;
        }

        $turnoAluno = $pedido['turno_consumo'];
        $alunoId    = (int)$pedido['aluno_id'];
        $alergenosAlunoIds = Aluno::obterIdsAlergenos($alunoId);

        $produtos = Produto::listarParaCardapio($turnoAluno);
        foreach ($produtos as &$prod) {
            $conflitos = [];
            foreach ($prod['alergenos'] as $alerg) {
                if (in_array((int)$alerg['id'], $alergenosAlunoIds, true)) {
                    $conflitos[] = $alerg['nome'];
                }
            }
            $prod['tem_conflito_alergia'] = !empty($conflitos);
            $prod['conflitos_nomes'] = $conflitos;
        }
        unset($prod);

        // Agrupa por categoria
        $produtosPorCategoria = [];
        foreach ($produtos as $prod) {
            $cat = $prod['categoria_nome'] ?? 'Outros';
            $produtosPorCategoria[$cat][] = $prod;
        }

        // Mapeia quantidades atuais do pedido
        $itensAtuais = [];
        foreach ($pedido['itens'] as $it) {
            $itensAtuais[(int)$it['produto_id']] = (int)$it['quantidade'];
        }

        $this->render('portal/pedido_editar', [
            'pageTitle'            => "Editar Pedido #{$pedido['codigo_pedido']} — Portal da Família",
            'activeMenu'           => 'pedidos',
            'usuario'              => $usuario,
            'user'                 => $usuario,
            'responsavelNome'      => $resumoCarteira['responsavel_nome'] ?? $usuario['nome'] ?? 'Responsável',
            'saldoAtual'           => $resumoCarteira['saldo_atual'],
            'saldoDisponivel'      => $resumoCarteira['saldo_disponivel'],
            'saldoReservado'       => $resumoCarteira['saldo_reservado'],
            'pedido'               => $pedido,
            'itensAtuais'          => $itensAtuais,
            'produtosPorCategoria' => $produtosPorCategoria,
            'produtos'             => $produtos
        ], 'portal');
    }

    /**
     * Processa a gravação da edição do pedido com compensação de saldo
     */
    public function pedidoEditarSalvar(int $id): void
    {
        if (!Session::validateCsrfToken((string)$this->input('csrf_token'))) {
            Session::setFlash('danger', 'Validação de segurança expirou. Tente novamente.');
            $this->redirect("/portal/pedidos/editar/{$id}");
            return;
        }

        $usuario = Auth::user();
        $respId  = (int)$usuario['id'];

        $itensPost = (array)($this->input('itens', []) ?: []);
        $itensFormatados = [];
        foreach ($itensPost as $prodId => $qtd) {
            $prodId = (int)$prodId;
            $qtd = (int)$qtd;
            if ($prodId > 0 && $qtd > 0) {
                $itensFormatados[] = [
                    'produto_id' => $prodId,
                    'quantidade' => $qtd
                ];
            }
        }

        if (empty($itensFormatados)) {
            Session::setFlash('warning', 'O pedido deve conter pelo menos um produto selecionado.');
            $this->redirect("/portal/pedidos/editar/{$id}");
            return;
        }

        try {
            $resultado = Pedido::editarPedido($id, $respId, $itensFormatados);

            $msg = "Pedido #{$resultado['codigo_pedido']} atualizado com sucesso!";
            if ($resultado['diferenca'] > 0) {
                $msg .= " Valor adicional de R$ " . number_format($resultado['diferenca'], 2, ',', '.') . " debitado da carteira.";
            } elseif ($resultado['diferenca'] < 0) {
                $msg .= " Estorno de R$ " . number_format(abs($resultado['diferenca']), 2, ',', '.') . " creditado em sua carteira.";
            }

            Session::setFlash('success', $msg);
            $this->redirect('/portal/pedidos');
        } catch (\DomainException $e) {
            Session::setFlash('danger', $e->getMessage());
            $this->redirect("/portal/pedidos/editar/{$id}");
        } catch (\Throwable $e) {
            Session::setFlash('danger', 'Falha ao editar o pedido. Tente novamente.');
            $this->redirect("/portal/pedidos/editar/{$id}");
        }
    }
}
