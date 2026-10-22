<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Domínio: Pedidos e Agendamento Semanal de Lanches
 * 
 * Responsável pelo ciclo de vida das encomendas semanais:
 * validação estrita de travas de corte (08:00 matutino e 13:30 vespertino),
 * débito atômico na carteira familiar, reserva e liberação de estoque,
 * detecção de conflitos de alérgenos e geração de tickets térmicos.
 * 
 * Conforme docs/FSD.md (Área 1, Telas 2.3 e 2.4, Fluxos 3 e 4, RN-01 a RN-06, RN-11 a RN-17)
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Pedido extends Model
{
    protected string $table = 'pedidos';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = false;

    // Status oficiais de Pedido
    public const STATUS_AGENDADO  = 'agendado';
    public const STATUS_EDITADO   = 'editado';
    public const STATUS_CANCELADO = 'cancelado';
    public const STATUS_ENTREGUE  = 'entregue';
    public const STATUS_ESTORNADO = 'estornado';

    // Turnos oficiais
    public const TURNO_MATUTINO   = 'matutino';
    public const TURNO_VESPERTINO = 'vespertino';

    // Horários rígidos de corte operacional (RN-02)
    public const CORTE_MATUTINO_HORA   = '08:00';
    public const CORTE_VESPERTINO_HORA = '13:30';

    /**
     * Verifica se um pedido para a data e turno especificados pode ser alterado ou cancelado
     * 
     * Regra RN-02:
     * - Turno Matutino: encerra às 08:00 do dia do consumo
     * - Turno Vespertino: encerra às 13:30 do dia do consumo
     * - Dias futuros (> hoje): permitidos
     * - Dias passados (< hoje): bloqueados
     * 
     * @param string $dataConsumo YYYY-MM-DD
     * @param string $turnoConsumo 'matutino' ou 'vespertino'
     * @param \DateTimeInterface|null $agora Permite simulação precisa em testes
     * @return bool
     */
    public static function podeSerAlteradoOuCancelado(
        string $dataConsumo, 
        string $turnoConsumo, 
        ?\DateTimeInterface $agora = null
    ): bool {
        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();
        $hoje = $now->format('Y-m-d');
        $horaAtual = $now->format('H:i');

        // Dias passados já sofreram corte
        if ($dataConsumo < $hoje) {
            return false;
        }

        // Dias futuros estão abertos
        if ($dataConsumo > $hoje) {
            return true;
        }

        // Se for hoje, avalia a hora de corte do turno
        if ($turnoConsumo === self::TURNO_MATUTINO) {
            return ($horaAtual < self::CORTE_MATUTINO_HORA);
        }

        if ($turnoConsumo === self::TURNO_VESPERTINO) {
            return ($horaAtual < self::CORTE_VESPERTINO_HORA);
        }

        return false;
    }

    /**
     * Retorna o status detalhado do corte operacional para uma data e turno
     * 
     * @param string $dataConsumo
     * @param string $turnoConsumo
     * @param \DateTimeInterface|null $agora
     * @return array
     */
    public static function obterStatusCorte(
        string $dataConsumo, 
        string $turnoConsumo, 
        ?\DateTimeInterface $agora = null
    ): array {
        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();
        $hoje = $now->format('Y-m-d');
        $horaLimite = ($turnoConsumo === self::TURNO_MATUTINO) 
            ? self::CORTE_MATUTINO_HORA 
            : self::CORTE_VESPERTINO_HORA;

        $pode = self::podeSerAlteradoOuCancelado($dataConsumo, $turnoConsumo, $now);

        if ($dataConsumo < $hoje) {
            return [
                'pode_alterar'        => false,
                'status'              => 'passado',
                'hora_corte'          => $horaLimite,
                'minutos_restantes'   => 0,
                'rotulo'              => 'Corte Encerrado',
                'mensagem'            => 'Data de consumo anterior à data corrente.',
            ];
        }

        if ($dataConsumo > $hoje) {
            return [
                'pode_alterar'        => true,
                'status'              => 'futuro_aberto',
                'hora_corte'          => $horaLimite,
                'minutos_restantes'   => null,
                'rotulo'              => 'Agendamento Aberto',
                'mensagem'            => "Corte encerra no dia às {$horaLimite}.",
            ];
        }

        // É hoje
        if ($pode) {
            $limiteDt = new \DateTimeImmutable("{$hoje} {$horaLimite}:00");
            $diffSegundos = max(0, $limiteDt->getTimestamp() - $now->getTimestamp());
            $minutosRestantes = (int)ceil($diffSegundos / 60);

            return [
                'pode_alterar'        => true,
                'status'              => 'hoje_aberto',
                'hora_corte'          => $horaLimite,
                'minutos_restantes'   => $minutosRestantes,
                'rotulo'              => 'Aberto (Hoje)',
                'mensagem'            => "Corte pontual às {$horaLimite} (restam aprox. {$minutosRestantes} min).",
            ];
        }

        return [
            'pode_alterar'        => false,
            'status'              => 'corte_fechado',
            'hora_corte'          => $horaLimite,
            'minutos_restantes'   => 0,
            'rotulo'              => 'Corte Fechado',
            'mensagem'            => "Horário de corte ({$horaLimite}) atingido para o turno {$turnoConsumo}.",
        ];
    }

    /**
     * Gera um código de pedido amigável e único (ex: PED-2026-A1B2C)
     */
    public static function gerarCodigoPedido(): string
    {
        $ano = date('Y');
        do {
            $sufixo = strtoupper(bin2hex(random_bytes(3))); // 6 caracteres hexadecimais
            $codigo = "PED-{$ano}-{$sufixo}";
            $existe = self::fetchOne("SELECT id FROM pedidos WHERE codigo_pedido = :cod LIMIT 1", ['cod' => $codigo]);
        } while ($existe);

        return $codigo;
    }

    /**
     * Gera um código de ticket térmico único (ex: TCK-2026-A1B2-01)
     */
    public static function gerarCodigoTicket(int $seq = 1): string
    {
        $ano = date('Y');
        do {
            $sufixo = strtoupper(bin2hex(random_bytes(3)));
            $codigo = sprintf("TCK-%s-%s-%02d", $ano, $sufixo, $seq);
            $existe = self::fetchOne("SELECT id FROM tickets WHERE codigo_ticket = :cod LIMIT 1", ['cod' => $codigo]);
        } while ($existe);

        return $codigo;
    }

    /**
     * Realiza a criação atômica e completa de um agendamento semanal de lanche
     * 
     * Executa em transação PDO com bloqueio pessimista:
     * 1. Validação estrita de titularidade e atividade do aluno
     * 2. Validação da data (dia letivo e trava rígida de corte operacional)
     * 3. Validação dos itens (produtos ativos, compatibilidade de turno e estoque)
     * 4. Validação de saldo e débito atômico na carteira familiar
     * 5. Reserva de estoque para itens industrializados
     * 6. Gravação de pedidos, itens_pedido e tickets com tag de alérgenos
     * 
     * @param int $responsavelId
     * @param int $alunoId
     * @param string $dataConsumo YYYY-MM-DD
     * @param array $itens Lista de itens no formato [['produto_id' => int, 'quantidade' => int], ...]
     * @param \DateTimeInterface|null $agora
     * @return array Dados do pedido criado
     * @throws \InvalidArgumentException|\DomainException|\PDOException
     */
    public static function criarPedido(
        int $responsavelId,
        int $alunoId,
        string $dataConsumo,
        array $itens,
        ?\DateTimeInterface $agora = null
    ): array {
        if (empty($itens)) {
            throw new \InvalidArgumentException('O pedido deve conter pelo menos um item de lanche.');
        }

        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Valida Aluno e Titularidade
            $stmtAluno = $pdo->prepare("
                SELECT a.id, a.nome, a.matricula, a.turno, a.ativo, a.responsavel_id,
                       t.nome_turma, t.serie
                FROM alunos a
                LEFT JOIN turmas t ON t.id = a.turma_id
                WHERE a.id = :id AND a.deleted_at IS NULL
                FOR UPDATE
            ");
            $stmtAluno->execute(['id' => $alunoId]);
            $aluno = $stmtAluno->fetch(\PDO::FETCH_ASSOC);

            if (!$aluno) {
                throw new \DomainException('Aluno não localizado ou inativo.');
            }

            if ((int)$aluno['responsavel_id'] !== $responsavelId) {
                throw new \DomainException('Aluno dependente não pertence à conta familiar autenticada.');
            }

            if ((int)$aluno['ativo'] !== 1) {
                throw new \DomainException("O aluno [{$aluno['nome']}] encontra-se inativo para novos agendamentos.");
            }

            $turnoConsumo = $aluno['turno'] ?? self::TURNO_MATUTINO;

            // 2. Valida se a data é um dia letivo
            if (!CalendarioEscolar::isDiaLetivo($dataConsumo)) {
                $infoData = CalendarioEscolar::obterInfoData($dataConsumo);
                throw new \DomainException("A data {$dataConsumo} não é um dia letivo ({$infoData['descricao']}).");
            }

            // 3. Valida trava de corte operacional (RN-02)
            if (!self::podeSerAlteradoOuCancelado($dataConsumo, $turnoConsumo, $now)) {
                $statusCorte = self::obterStatusCorte($dataConsumo, $turnoConsumo, $now);
                throw new \DomainException(
                    "Agendamento bloqueado: Horário de corte atingido para o turno {$turnoConsumo} ({$statusCorte['hora_corte']})."
                );
            }

            // 4. Valida se a data pertence à semana seguinte e se já abriu (Sexta 18:00)
            $hojeIso = $now->format('Y-m-d');
            $diasSemanaAtual = array_column(CalendarioEscolar::obterDiasSemanaLetiva('atual', $now), 'data');
            if (!in_array($dataConsumo, $diasSemanaAtual, true)) {
                // É semana seguinte ou além
                if (!CalendarioEscolar::isSemanaSeguinteLiberada($now)) {
                    throw new \DomainException('O cardápio da próxima semana só é liberado na sexta-feira às 18:00.');
                }
            }

            // 5. Consulta alérgenos do aluno para marcação preventiva nos tickets
            $alergenosAlunoIds = Aluno::obterIdsAlergenos($alunoId);

            // 6. Processa e valida cada item do pedido
            $itensValidados = [];
            $valorTotal = 0.00;

            foreach ($itens as $item) {
                $prodId = (int)($item['produto_id'] ?? 0);
                $qtd = max(1, (int)($item['quantidade'] ?? 1));

                if ($prodId <= 0) {
                    continue;
                }

                // Busca produto com bloqueio
                $stmtProd = $pdo->prepare("
                    SELECT p.*, c.nome AS categoria_nome 
                    FROM produtos p
                    LEFT JOIN categorias c ON c.id = p.categoria_id
                    WHERE p.id = :id AND p.deleted_at IS NULL
                    FOR UPDATE
                ");
                $stmtProd->execute(['id' => $prodId]);
                $prod = $stmtProd->fetch(\PDO::FETCH_ASSOC);

                if (!$prod) {
                    throw new \DomainException("Produto #{$prodId} não localizado.");
                }

                if ($prod['status_pausa'] === 'pausado') {
                    throw new \DomainException("O produto [{$prod['descricao']}] está temporariamente pausado.");
                }

                // Valida compatibilidade de turno (RN-11)
                if ($prod['turno_disponibilidade'] !== 'ambos' && $prod['turno_disponibilidade'] !== $turnoConsumo) {
                    throw new \DomainException(
                        "O produto [{$prod['descricao']}] não está disponível para o turno {$turnoConsumo}."
                    );
                }

                // Valida limite de quantidade para salgados diários
                if ($prod['tipo'] === 'salgado_diario' && $qtd > 10) {
                    throw new \DomainException(
                        "Quantidade máxima permitida para o produto [{$prod['descricao']}] é de 10 unidades por pedido."
                    );
                }

                // Valida estoque para industrializados (RN-12 e RN-13)
                if ($prod['tipo'] === 'industrializado_continuo') {
                    $estoqueAtual = (int)$prod['estoque_atual'];
                    $estoqueReservado = (int)$prod['estoque_reservado'];
                    $estoqueDisponivel = $estoqueAtual - $estoqueReservado;

                    if ($estoqueDisponivel <= 0) {
                        throw new \DomainException(
                            "O produto [{$prod['descricao']}] encontra-se esgotado no estoque."
                        );
                    }

                    if ($estoqueDisponivel < $qtd) {
                        throw new \DomainException(
                            "O produto [{$prod['descricao']}] não possui estoque suficiente. Disponível: {$estoqueDisponivel} un."
                        );
                    }
                }

                $precoUnitario = (float)$prod['preco_venda'];
                $subtotal = round($qtd * $precoUnitario, 2);
                $valorTotal = round($valorTotal + $subtotal, 2);

                // Consulta alérgenos do produto
                $stmtProdAlerg = $pdo->prepare("
                    SELECT ra.id, ra.nome 
                    FROM produto_alergenos pa
                    INNER JOIN restricoes_alimentares ra ON ra.id = pa.restricao_id
                    WHERE pa.produto_id = :prod_id
                ");
                $stmtProdAlerg->execute(['prod_id' => $prodId]);
                $alergenosProd = $stmtProdAlerg->fetchAll(\PDO::FETCH_ASSOC);

                // Detecta se há alérgenos conflitantes com o aluno
                $conflitos = [];
                foreach ($alergenosProd as $alerg) {
                    if (in_array((int)$alerg['id'], $alergenosAlunoIds, true)) {
                        $conflitos[] = mb_strtoupper($alerg['nome'], 'UTF-8');
                    }
                }

                $tagAlergenos = null;
                if (!empty($conflitos)) {
                    $tagAlergenos = '[ATENÇÃO: CONTÉM ' . implode(', ', $conflitos) . ']';
                }

                $itensValidados[] = [
                    'produto'        => $prod,
                    'produto_id'     => $prodId,
                    'quantidade'     => $qtd,
                    'preco_unitario' => $precoUnitario,
                    'subtotal'       => $subtotal,
                    'tag_alergenos'  => $tagAlergenos,
                ];
            }

            if (empty($itensValidados)) {
                throw new \DomainException('Nenhum item válido foi informado para o pedido.');
            }

            // 7. Gera código único do pedido
            $codigoPedido = self::gerarCodigoPedido();

            // 8. Débito compulsório na carteira familiar (modelo pré-pago)
            TransacaoCarteira::debitar(
                $responsavelId,
                $valorTotal,
                TransacaoCarteira::TIPO_DEBITO_PEDIDO,
                "Agendamento do pedido #{$codigoPedido} para {$aluno['nome']}",
                $codigoPedido,
                $alunoId
            );

            // 9. Atualiza reserva de estoque para itens industrializados (RN-12)
            foreach ($itensValidados as $iv) {
                if ($iv['produto']['tipo'] === 'industrializado_continuo') {
                    $stmtUpEstoque = $pdo->prepare("
                        UPDATE produtos 
                        SET estoque_reservado = estoque_reservado + :qtd, updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtUpEstoque->execute([
                        'qtd' => $iv['quantidade'],
                        'id'  => $iv['produto_id']
                    ]);
                }
            }

            // 10. Insere o registro mestre em pedidos
            $stmtPedido = $pdo->prepare("
                INSERT INTO pedidos (
                    codigo_pedido, responsavel_id, aluno_id, data_consumo,
                    turno_consumo, valor_total, status, created_at, updated_at
                ) VALUES (
                    :codigo, :resp_id, :aluno_id, :data_consumo,
                    :turno, :total, :status, NOW(), NOW()
                )
            ");
            $stmtPedido->execute([
                'codigo'       => $codigoPedido,
                'resp_id'      => $responsavelId,
                'aluno_id'     => $alunoId,
                'data_consumo' => $dataConsumo,
                'turno'        => $turnoConsumo,
                'total'        => $valorTotal,
                'status'       => self::STATUS_AGENDADO,
            ]);

            $pedidoId = (int)$pdo->lastInsertId();

            // 11. Insere cada item em itens_pedido e emite os tickets correspondentes (RN-17)
            $seqTicket = 1;
            $ticketsGerados = [];

            foreach ($itensValidados as $iv) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO itens_pedido (
                        pedido_id, produto_id, quantidade, preco_unitario, subtotal
                    ) VALUES (
                        :pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal
                    )
                ");
                $stmtItem->execute([
                    'pedido_id'      => $pedidoId,
                    'produto_id'     => $iv['produto_id'],
                    'quantidade'     => $iv['quantidade'],
                    'preco_unitario' => $iv['preco_unitario'],
                    'subtotal'       => $iv['subtotal']
                ]);

                $itemPedidoId = (int)$pdo->lastInsertId();

                // Gera 1 ticket por quantidade do item
                for ($k = 0; $k < $iv['quantidade']; $k++) {
                    $codTicket = self::gerarCodigoTicket($seqTicket++);
                    $stmtTicket = $pdo->prepare("
                        INSERT INTO tickets (
                            pedido_id, item_pedido_id, codigo_ticket, numero_via,
                            status, tag_alergenos, created_at
                        ) VALUES (
                            :pedido_id, :item_id, :cod_ticket, 1,
                            'ativo', :tag_alergenos, NOW()
                        )
                    ");
                    $stmtTicket->execute([
                        'pedido_id'     => $pedidoId,
                        'item_id'       => $itemPedidoId,
                        'cod_ticket'    => $codTicket,
                        'tag_alergenos' => $iv['tag_alergenos']
                    ]);

                    $ticketsGerados[] = [
                        'ticket_id'     => (int)$pdo->lastInsertId(),
                        'codigo_ticket' => $codTicket,
                        'tag_alergenos' => $iv['tag_alergenos']
                    ];
                }
            }

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'id'            => $pedidoId,
                'codigo_pedido' => $codigoPedido,
                'responsavel_id'=> $responsavelId,
                'aluno_id'      => $alunoId,
                'aluno_nome'    => $aluno['nome'],
                'data_consumo'  => $dataConsumo,
                'turno_consumo' => $turnoConsumo,
                'valor_total'   => $valorTotal,
                'status'        => self::STATUS_AGENDADO,
                'itens_count'   => count($itensValidados),
                'tickets_count' => count($ticketsGerados)
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancela autonomamente um pedido agendado pelo responsável
     * 
     * Regra RN-03:
     * - Apenas até as 08:00 (matutino) ou 13:30 (vespertino) da data de consumo
     * - Estorno de 100% do valor para a carteira familiar no mesmo instante
     * - Liberação imediata da reserva de estoque de industrializados
     * - Cancelamento de todos os tickets associados
     * 
     * @param int $pedidoId
     * @param int $responsavelId
     * @param \DateTimeInterface|null $agora
     * @return array
     * @throws \DomainException|\PDOException
     */
    public static function cancelarPedido(
        int $pedidoId, 
        int $responsavelId, 
        ?\DateTimeInterface $agora = null
    ): array {
        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Busca o pedido com bloqueio
            $stmt = $pdo->prepare("
                SELECT p.*, a.nome AS aluno_nome 
                FROM pedidos p
                INNER JOIN alunos a ON a.id = p.aluno_id
                WHERE p.id = :id AND p.responsavel_id = :resp_id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $pedidoId, 'resp_id' => $responsavelId]);
            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pedido) {
                throw new \DomainException('Pedido não localizado ou não pertence à sua conta familiar.');
            }

            if ($pedido['status'] === self::STATUS_CANCELADO) {
                throw new \DomainException('Este pedido já se encontra cancelado.');
            }

            if ($pedido['status'] === self::STATUS_ENTREGUE) {
                throw new \DomainException('Não é possível cancelar um pedido que já foi entregue.');
            }

            // 2. Validação estrita de corte (RN-02)
            if (!self::podeSerAlteradoOuCancelado($pedido['data_consumo'], $pedido['turno_consumo'], $now)) {
                $horaCorte = ($pedido['turno_consumo'] === self::TURNO_MATUTINO) 
                    ? self::CORTE_MATUTINO_HORA 
                    : self::CORTE_VESPERTINO_HORA;
                throw new \DomainException(
                    "Cancelamento bloqueado: o horário de corte do turno ({$horaCorte}) já foi atingido."
                );
            }

            // 3. Atualiza status do pedido
            $stmtUpPedido = $pdo->prepare("
                UPDATE pedidos 
                SET status = :status, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtUpPedido->execute([
                'status' => self::STATUS_CANCELADO,
                'id'     => $pedidoId
            ]);

            // 4. Cancela os tickets vinculados
            $stmtUpTickets = $pdo->prepare("
                UPDATE tickets 
                SET status = 'cancelado' 
                WHERE pedido_id = :pedido_id AND status != 'estornado'
            ");
            $stmtUpTickets->execute(['pedido_id' => $pedidoId]);

            // 5. Libera as reservas de estoque de produtos industrializados (RN-12)
            $stmtItens = $pdo->prepare("
                SELECT ip.quantidade, p.id AS produto_id, p.tipo 
                FROM itens_pedido ip
                INNER JOIN produtos p ON p.id = ip.produto_id
                WHERE ip.pedido_id = :pedido_id
            ");
            $stmtItens->execute(['pedido_id' => $pedidoId]);
            $itens = $stmtItens->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($itens as $it) {
                if ($it['tipo'] === 'industrializado_continuo') {
                    $stmtLiberar = $pdo->prepare("
                        UPDATE produtos 
                        SET estoque_reservado = GREATEST(0, estoque_reservado - :qtd), updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtLiberar->execute([
                        'qtd' => (int)$it['quantidade'],
                        'id'  => (int)$it['produto_id']
                    ]);
                }
            }

            // 6. Estorno integral de 100% na carteira familiar (RN-03)
            $valorEstorno = (float)$pedido['valor_total'];
            TransacaoCarteira::creditar(
                $responsavelId,
                $valorEstorno,
                TransacaoCarteira::TIPO_ESTORNO_CANCELAMENTO,
                "Estorno integral por cancelamento do pedido #{$pedido['codigo_pedido']}",
                $pedido['codigo_pedido'],
                (int)$pedido['aluno_id']
            );

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'id'            => $pedidoId,
                'codigo_pedido' => $pedido['codigo_pedido'],
                'valor_estorno' => $valorEstorno,
                'status'        => self::STATUS_CANCELADO,
                'mensagem'      => "Pedido #{$pedido['codigo_pedido']} cancelado com sucesso. Valor estornado na carteira."
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Edita autonomamente os itens de um pedido agendado pelo responsável
     * 
     * Regra RN-04:
     * - Apenas antes do horário de corte (08:00 matutino e 13:30 vespertino)
     * - Se o novo total for mais caro: valida saldo e debita a diferença da carteira
     * - Se o novo total for mais barato: estorna a diferença imediatamente na carteira
     * - Libera reservas antigas e reserva novas quantidades de industrializados
     * - Atualiza itens_pedido e regera tickets
     * 
     * @param int $pedidoId
     * @param int $responsavelId
     * @param array $novosItens Lista de itens no formato [['produto_id' => int, 'quantidade' => int], ...]
     * @param \DateTimeInterface|null $agora
     * @return array
     * @throws \InvalidArgumentException|\DomainException|\PDOException
     */
    public static function editarPedido(
        int $pedidoId, 
        int $responsavelId, 
        array $novosItens, 
        ?\DateTimeInterface $agora = null
    ): array {
        if (empty($novosItens)) {
            throw new \InvalidArgumentException('O pedido editado deve conter pelo menos um item.');
        }

        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Busca o pedido com bloqueio
            $stmt = $pdo->prepare("
                SELECT p.*, a.nome AS aluno_nome, a.turno 
                FROM pedidos p
                INNER JOIN alunos a ON a.id = p.aluno_id
                WHERE p.id = :id AND p.responsavel_id = :resp_id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $pedidoId, 'resp_id' => $responsavelId]);
            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pedido) {
                throw new \DomainException('Pedido não localizado ou não pertence à sua conta familiar.');
            }

            if ($pedido['status'] === self::STATUS_CANCELADO) {
                throw new \DomainException('Não é possível editar um pedido cancelado.');
            }

            if ($pedido['status'] === self::STATUS_ENTREGUE) {
                throw new \DomainException('Não é possível editar um pedido já entregue.');
            }

            // 2. Valida trava de corte operacional (RN-02)
            if (!self::podeSerAlteradoOuCancelado($pedido['data_consumo'], $pedido['turno_consumo'], $now)) {
                $horaCorte = ($pedido['turno_consumo'] === self::TURNO_MATUTINO) 
                    ? self::CORTE_MATUTINO_HORA 
                    : self::CORTE_VESPERTINO_HORA;
                throw new \DomainException(
                    "Edição bloqueada: o horário de corte do turno ({$horaCorte}) já foi atingido."
                );
            }

            $turnoConsumo = $pedido['turno_consumo'];
            $alunoId = (int)$pedido['aluno_id'];
            $alergenosAlunoIds = Aluno::obterIdsAlergenos($alunoId);

            // 3. Libera temporariamente as reservas de estoque dos itens antigos para recálculo
            $stmtOldItens = $pdo->prepare("
                SELECT ip.quantidade, p.id AS produto_id, p.tipo 
                FROM itens_pedido ip
                INNER JOIN produtos p ON p.id = ip.produto_id
                WHERE ip.pedido_id = :pedido_id
            ");
            $stmtOldItens->execute(['pedido_id' => $pedidoId]);
            $oldItens = $stmtOldItens->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($oldItens as $oi) {
                if ($oi['tipo'] === 'industrializado_continuo') {
                    $stmtLiberar = $pdo->prepare("
                        UPDATE produtos 
                        SET estoque_reservado = GREATEST(0, estoque_reservado - :qtd), updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtLiberar->execute([
                        'qtd' => (int)$oi['quantidade'],
                        'id'  => (int)$oi['produto_id']
                    ]);
                }
            }

            // 4. Valida os novos itens
            $itensValidados = [];
            $novoTotal = 0.00;

            foreach ($novosItens as $ni) {
                $prodId = (int)($ni['produto_id'] ?? 0);
                $qtd = max(1, (int)($ni['quantidade'] ?? 1));

                if ($prodId <= 0) {
                    continue;
                }

                $stmtProd = $pdo->prepare("
                    SELECT p.*, c.nome AS categoria_nome 
                    FROM produtos p
                    LEFT JOIN categorias c ON c.id = p.categoria_id
                    WHERE p.id = :id AND p.deleted_at IS NULL
                    FOR UPDATE
                ");
                $stmtProd->execute(['id' => $prodId]);
                $prod = $stmtProd->fetch(\PDO::FETCH_ASSOC);

                if (!$prod) {
                    throw new \DomainException("Produto #{$prodId} não localizado.");
                }

                if ($prod['status_pausa'] === 'pausado') {
                    throw new \DomainException("O produto [{$prod['descricao']}] está pausado.");
                }

                if ($prod['turno_disponibilidade'] !== 'ambos' && $prod['turno_disponibilidade'] !== $turnoConsumo) {
                    throw new \DomainException("O produto [{$prod['descricao']}] não está disponível para o turno {$turnoConsumo}.");
                }

                if ($prod['tipo'] === 'salgado_diario' && $qtd > 10) {
                    throw new \DomainException("Quantidade máxima permitida para o produto [{$prod['descricao']}] é de 10 unidades por pedido.");
                }

                if ($prod['tipo'] === 'industrializado_continuo') {
                    $disponivel = (int)$prod['estoque_atual'] - (int)$prod['estoque_reservado'];
                    if ($disponivel <= 0) {
                        throw new \DomainException("O produto [{$prod['descricao']}] encontra-se esgotado no estoque.");
                    }
                    if ($disponivel < $qtd) {
                        throw new \DomainException("Estoque insuficiente para [{$prod['descricao']}]. Disponível: {$disponivel} un.");
                    }
                }

                $precoUnitario = (float)$prod['preco_venda'];
                $subtotal = round($qtd * $precoUnitario, 2);
                $novoTotal = round($novoTotal + $subtotal, 2);

                // Conflitos de alérgenos
                $stmtProdAlerg = $pdo->prepare("
                    SELECT ra.id, ra.nome 
                    FROM produto_alergenos pa
                    INNER JOIN restricoes_alimentares ra ON ra.id = pa.restricao_id
                    WHERE pa.produto_id = :prod_id
                ");
                $stmtProdAlerg->execute(['prod_id' => $prodId]);
                $alergenosProd = $stmtProdAlerg->fetchAll(\PDO::FETCH_ASSOC);

                $conflitos = [];
                foreach ($alergenosProd as $alerg) {
                    if (in_array((int)$alerg['id'], $alergenosAlunoIds, true)) {
                        $conflitos[] = mb_strtoupper($alerg['nome'], 'UTF-8');
                    }
                }

                $tagAlergenos = !empty($conflitos) ? '[ATENÇÃO: CONTÉM ' . implode(', ', $conflitos) . ']' : null;

                $itensValidados[] = [
                    'produto'        => $prod,
                    'produto_id'     => $prodId,
                    'quantidade'     => $qtd,
                    'preco_unitario' => $precoUnitario,
                    'subtotal'       => $subtotal,
                    'tag_alergenos'  => $tagAlergenos,
                ];
            }

            if (empty($itensValidados)) {
                throw new \DomainException('Nenhum item válido informado para o pedido.');
            }

            // 5. Compensação financeira (RN-04)
            $totalAntigo = (float)$pedido['valor_total'];
            $diferenca = round($novoTotal - $totalAntigo, 2);

            if ($diferenca > 0) {
                // Novo pedido é mais caro: debita a diferença da carteira
                TransacaoCarteira::debitar(
                    $responsavelId,
                    $diferenca,
                    TransacaoCarteira::TIPO_DEBITO_PEDIDO,
                    "Diferença por adição de itens no pedido #{$pedido['codigo_pedido']}",
                    $pedido['codigo_pedido'],
                    $alunoId
                );
            } elseif ($diferenca < 0) {
                // Novo pedido é mais barato: estorna a diferença para a carteira
                $valorEstorno = abs($diferenca);
                TransacaoCarteira::creditar(
                    $responsavelId,
                    $valorEstorno,
                    TransacaoCarteira::TIPO_ESTORNO_CANCELAMENTO,
                    "Estorno de diferença na edição do pedido #{$pedido['codigo_pedido']}",
                    $pedido['codigo_pedido'],
                    $alunoId
                );
            }

            // 6. Aplica as novas reservas de estoque para industrializados
            foreach ($itensValidados as $iv) {
                if ($iv['produto']['tipo'] === 'industrializado_continuo') {
                    $stmtUpEstoque = $pdo->prepare("
                        UPDATE produtos 
                        SET estoque_reservado = estoque_reservado + :qtd, updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtUpEstoque->execute([
                        'qtd' => $iv['quantidade'],
                        'id'  => $iv['produto_id']
                    ]);
                }
            }

            // 7. Remove itens antigos e tickets antigos (o CASCADE do foreign key remove tickets)
            $stmtDelTickets = $pdo->prepare("DELETE FROM tickets WHERE pedido_id = :pedido_id");
            $stmtDelTickets->execute(['pedido_id' => $pedidoId]);

            $stmtDelItens = $pdo->prepare("DELETE FROM itens_pedido WHERE pedido_id = :pedido_id");
            $stmtDelItens->execute(['pedido_id' => $pedidoId]);

            // 8. Insere novos itens e regera tickets
            $seqTicket = 1;
            foreach ($itensValidados as $iv) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO itens_pedido (
                        pedido_id, produto_id, quantidade, preco_unitario, subtotal
                    ) VALUES (
                        :pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal
                    )
                ");
                $stmtItem->execute([
                    'pedido_id'      => $pedidoId,
                    'produto_id'     => $iv['produto_id'],
                    'quantidade'     => $iv['quantidade'],
                    'preco_unitario' => $iv['preco_unitario'],
                    'subtotal'       => $iv['subtotal']
                ]);

                $itemPedidoId = (int)$pdo->lastInsertId();

                for ($k = 0; $k < $iv['quantidade']; $k++) {
                    $codTicket = self::gerarCodigoTicket($seqTicket++);
                    $stmtTicket = $pdo->prepare("
                        INSERT INTO tickets (
                            pedido_id, item_pedido_id, codigo_ticket, numero_via,
                            status, tag_alergenos, created_at
                        ) VALUES (
                            :pedido_id, :item_id, :cod_ticket, 1,
                            'ativo', :tag_alergenos, NOW()
                        )
                    ");
                    $stmtTicket->execute([
                        'pedido_id'     => $pedidoId,
                        'item_id'       => $itemPedidoId,
                        'cod_ticket'    => $codTicket,
                        'tag_alergenos' => $iv['tag_alergenos']
                    ]);
                }
            }

            // 9. Atualiza o cabeçalho do pedido
            $stmtUpHead = $pdo->prepare("
                UPDATE pedidos 
                SET valor_total = :total, status = :status, updated_at = NOW() 
                WHERE id = :id
            ");
            $stmtUpHead->execute([
                'total'  => $novoTotal,
                'status' => self::STATUS_EDITADO,
                'id'     => $pedidoId
            ]);

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'id'            => $pedidoId,
                'codigo_pedido' => $pedido['codigo_pedido'],
                'total_antigo'  => $totalAntigo,
                'novo_total'    => $novoTotal,
                'diferenca'     => $diferenca,
                'status'        => self::STATUS_EDITADO,
                'mensagem'      => 'Pedido atualizado com sucesso.'
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lista pedidos do responsável com filtros e metadados
     */
    public static function listarPorResponsavel(
        int $responsavelId, 
        ?int $alunoId = null, 
        ?string $dataInicio = null, 
        ?string $dataFim = null
    ): array {
        $sql = "SELECT p.*, 
                       a.nome AS aluno_nome, 
                       a.matricula AS aluno_matricula,
                       t.nome_turma, t.serie,
                       (SELECT COUNT(*) FROM itens_pedido ip WHERE ip.pedido_id = p.id) AS total_itens
                FROM pedidos p
                INNER JOIN alunos a ON a.id = p.aluno_id
                LEFT JOIN turmas t ON t.id = a.turma_id
                WHERE p.responsavel_id = :resp_id";

        $params = ['resp_id' => $responsavelId];

        if ($alunoId !== null && $alunoId > 0) {
            $sql .= " AND p.aluno_id = :aluno_id";
            $params['aluno_id'] = $alunoId;
        }

        if (!empty($dataInicio)) {
            $sql .= " AND p.data_consumo >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $sql .= " AND p.data_consumo <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $sql .= " ORDER BY p.data_consumo DESC, p.id DESC";

        $pedidos = self::fetchAll($sql, $params);

        // Agrega itens detalhados e status de corte para cada pedido
        foreach ($pedidos as &$p) {
            $p['itens'] = ItemPedido::listarPorPedido((int)$p['id']);
            $p['status_corte'] = self::obterStatusCorte($p['data_consumo'], $p['turno_consumo']);
        }
        unset($p);

        return $pedidos;
    }

    /**
     * Busca um pedido simples por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM pedidos WHERE id = :id LIMIT 1";
        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Busca um pedido detalhado por ID e responsável
     */
    public static function buscarDetalhado(int $pedidoId, int $responsavelId): ?array
    {
        $sql = "SELECT p.*, 
                       a.nome AS aluno_nome, 
                       a.matricula AS aluno_matricula,
                       t.nome_turma, t.serie
                FROM pedidos p
                INNER JOIN alunos a ON a.id = p.aluno_id
                LEFT JOIN turmas t ON t.id = a.turma_id
                WHERE p.id = :id AND p.responsavel_id = :resp_id
                LIMIT 1";

        $pedido = self::fetchOne($sql, ['id' => $pedidoId, 'resp_id' => $responsavelId]);

        if (!$pedido) {
            return null;
        }

        $pedido['itens'] = ItemPedido::listarPorPedido($pedidoId);
        $pedido['status_corte'] = self::obterStatusCorte($pedido['data_consumo'], $pedido['turno_consumo']);

        return $pedido;
    }

    /**
     * Busca um pedido detalhado por ID (uso operacional/administrativo)
     */
    public static function buscarDetalhadoPorId(int $pedidoId): ?array
    {
        $sql = "SELECT p.*, 
                       a.nome AS aluno_nome, 
                       a.matricula AS aluno_matricula,
                       t.nome_turma, t.serie,
                       r.nome AS responsavel_nome,
                       r.cpf AS responsavel_cpf,
                       r.whatsapp AS responsavel_whatsapp
                FROM pedidos p
                INNER JOIN alunos a ON a.id = p.aluno_id
                LEFT JOIN turmas t ON t.id = a.turma_id
                INNER JOIN responsaveis r ON r.id = p.responsavel_id
                WHERE p.id = :id
                LIMIT 1";

        $pedido = self::fetchOne($sql, ['id' => $pedidoId]);

        if (!$pedido) {
            return null;
        }

        $pedido['itens'] = ItemPedido::listarPorPedido($pedidoId);
        $pedido['status_corte'] = self::obterStatusCorte($pedido['data_consumo'], $pedido['turno_consumo']);

        return $pedido;
    }

    /**
     * Consolida a demanda de itens/salgados de um determinado turno e data
     * Agrupa pedidos agendados ou editados para envio ao fornecedor
     * 
     * @param string $dataConsumo YYYY-MM-DD
     * @param string $turnoConsumo 'matutino' ou 'vespertino'
     * @param \DateTimeInterface|null $agora
     * @return array
     */
    public static function consolidarCorte(
        string $dataConsumo, 
        string $turnoConsumo, 
        ?\DateTimeInterface $agora = null
    ): array {
        $sql = "SELECT p.id AS produto_id, 
                       p.codigo_interno, 
                       p.descricao, 
                       p.tipo AS tipo_produto, 
                       p.fracionado_salgado_grande,
                       p.preco_venda, 
                       c.nome AS categoria_nome,
                       SUM(ip.quantidade) AS quantidade_total,
                       SUM(ip.subtotal) AS valor_total_estimado
                FROM itens_pedido ip
                INNER JOIN pedidos ped ON ped.id = ip.pedido_id
                INNER JOIN produtos p ON p.id = ip.produto_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE ped.data_consumo = :data 
                  AND ped.turno_consumo = :turno 
                  AND ped.status IN ('agendado', 'editado')
                  AND p.deleted_at IS NULL
                GROUP BY p.id, p.codigo_interno, p.descricao, p.tipo, p.fracionado_salgado_grande, p.preco_venda, c.nome, c.ordem_exibicao
                ORDER BY c.ordem_exibicao ASC, p.descricao ASC";

        $itensBrutos = self::fetchAll($sql, [
            'data'  => $dataConsumo,
            'turno' => $turnoConsumo
        ]);

        $itensConsolidados = [];
        $totalPecas = 0;
        $totalSalgados = 0;
        $totalValorEstimado = 0.0;
        $totalDemandaAlunos = 0;

        foreach ($itensBrutos as $it) {
            $qtdOriginal = (int)$it['quantidade_total'];
            $totalDemandaAlunos += $qtdOriginal;
            $totalValorEstimado += (float)$it['valor_total_estimado'];
            $isFracionado = !empty($it['fracionado_salgado_grande']);

            if ($isFracionado) {
                $grandes = intdiv($qtdOriginal, 2);
                $pequenos = $qtdOriginal % 2;

                // Formatação semântica dos nomes Grande e Pequeno
                $descOriginal = trim($it['descricao']);
                if (preg_match('/\bpequen([oa])s?\b/ui', $descOriginal)) {
                    $descGrande = preg_replace('/\bpequen([oa])s?\b/ui', 'Grande', $descOriginal);
                    $descPequeno = $descOriginal;
                } else {
                    $descGrande = $descOriginal . ' (Grande)';
                    $descPequeno = $descOriginal . ' (Pequeno)';
                }

                $precoUnitarioPequeno = (float)$it['preco_venda'];
                $precoUnitarioGrande = round($precoUnitarioPequeno * 2, 2);

                if ($grandes > 0) {
                    $itemGrande = [
                        'produto_id'                => (int)$it['produto_id'],
                        'codigo_interno'           => $it['codigo_interno'] . '-G',
                        'descricao'                => $descGrande,
                        'tipo_produto'             => $it['tipo_produto'],
                        'preco_venda'              => $precoUnitarioGrande,
                        'categoria_nome'           => $it['categoria_nome'],
                        'quantidade_total'         => $grandes,
                        'valor_total_estimado'     => round($grandes * $precoUnitarioGrande, 2),
                        'fracionado_salgado_grande'=> 1,
                        'tamanho_fornecedor'       => 'grande',
                        'fator_conversao'          => 2,
                        'qtd_pedida_original'      => $qtdOriginal,
                        'obs_conversao'            => "Convertido: {$qtdOriginal} pedidos pequenos = {$grandes} grande(s)" . ($pequenos > 0 ? " + {$pequenos} pequeno" : "")
                    ];
                    $itensConsolidados[] = $itemGrande;
                    $totalPecas += $grandes;
                    if ($it['tipo_produto'] === 'salgado_diario') {
                        $totalSalgados += $grandes;
                    }
                }

                if ($pequenos > 0) {
                    $itemPequeno = [
                        'produto_id'                => (int)$it['produto_id'],
                        'codigo_interno'           => $it['codigo_interno'] . '-P',
                        'descricao'                => $descPequeno,
                        'tipo_produto'             => $it['tipo_produto'],
                        'preco_venda'              => $precoUnitarioPequeno,
                        'categoria_nome'           => $it['categoria_nome'],
                        'quantidade_total'         => $pequenos,
                        'valor_total_estimado'     => round($pequenos * $precoUnitarioPequeno, 2),
                        'fracionado_salgado_grande'=> 1,
                        'tamanho_fornecedor'       => 'pequeno',
                        'fator_conversao'          => 1,
                        'qtd_pedida_original'      => $qtdOriginal,
                        'obs_conversao'            => "Sobra: {$pequenos} pequeno (de {$qtdOriginal} pedidos fracionados)"
                    ];
                    $itensConsolidados[] = $itemPequeno;
                    $totalPecas += $pequenos;
                    if ($it['tipo_produto'] === 'salgado_diario') {
                        $totalSalgados += $pequenos;
                    }
                }
            } else {
                $it['produto_id'] = (int)$it['produto_id'];
                $it['quantidade_total'] = $qtdOriginal;
                $it['valor_total_estimado'] = (float)$it['valor_total_estimado'];
                $it['fracionado_salgado_grande'] = 0;
                $it['tamanho_fornecedor'] = null;
                $it['fator_conversao'] = 1;
                $it['qtd_pedida_original'] = $qtdOriginal;
                $it['obs_conversao'] = null;

                $itensConsolidados[] = $it;
                $totalPecas += $qtdOriginal;
                if ($it['tipo_produto'] === 'salgado_diario') {
                    $totalSalgados += $qtdOriginal;
                }
            }
        }

        // Contagem de pedidos e alunos envolvidos
        $sqlTotais = "SELECT COUNT(DISTINCT ped.id) AS total_pedidos,
                             COUNT(DISTINCT ped.aluno_id) AS total_alunos
                      FROM pedidos ped
                      WHERE ped.data_consumo = :data 
                        AND ped.turno_consumo = :turno 
                        AND ped.status IN ('agendado', 'editado')";
        $totais = self::fetchOne($sqlTotais, [
            'data'  => $dataConsumo,
            'turno' => $turnoConsumo
        ]) ?: ['total_pedidos' => 0, 'total_alunos' => 0];

        $statusCorte = self::obterStatusCorte($dataConsumo, $turnoConsumo, $agora);
        $statusRecebimento = self::verificarStatusRecebimento($dataConsumo, $turnoConsumo);

        return [
            'data_consumo'        => $dataConsumo,
            'turno_consumo'       => $turnoConsumo,
            'itens'               => $itensConsolidados,
            'itens_originais'     => $itensBrutos,
            'total_pecas'         => $totalPecas,
            'total_salgados'      => $totalSalgados,
            'total_pedidos'       => (int)($totais['total_pedidos'] ?? 0),
            'total_alunos'        => (int)($totais['total_alunos'] ?? 0),
            'total_demanda_itens' => $totalDemandaAlunos,
            'total_valor_estimado'=> round($totalValorEstimado, 2),
            'status_corte'        => $statusCorte,
            'status_recebimento'  => $statusRecebimento,
        ];
    }

    /**
     * Formata mensagem de texto estruturada para envio direto ao WhatsApp do fornecedor
     */
    public static function obterTextoWhatsAppCorte(
        string $dataConsumo,
        string $turnoConsumo,
        array $itensConsolidados
    ): string {
        $dataFmt = date('d/m/Y', strtotime($dataConsumo));
        $turno = ucfirst($turnoConsumo);
        $totalPecas = 0;

        $msg = "*COLÉGIO MONTEIRO LOBATO — CANTINA ESCOLAR*\n";
        $msg .= "*PEDIDO DE SALGADOS — CORTE DO TURNO*\n\n";
        $msg .= "📅 *Data de Consumo:* {$dataFmt}\n";
        $msg .= "⏰ *Turno:* {$turno}\n\n";
        $msg .= "*RELAÇÃO DE ITENS ENCOMENDADOS:*\n";

        if (empty($itensConsolidados)) {
            $msg .= "_(Nenhum pedido de salgado registrado para este corte)_\n";
        } else {
            foreach ($itensConsolidados as $item) {
                $qtd = (int)($item['quantidade_total'] ?? 0);
                $totalPecas += $qtd;
                $codigo = !empty($item['codigo_interno']) ? " ({$item['codigo_interno']})" : '';
                $msg .= "• *{$qtd}x* {$item['descricao']}{$codigo}\n";
            }
        }

        $msg .= "\n*TOTAL GERAL: {$totalPecas} peças*\n\n";
        $msg .= "Favor confirmar o recebimento deste pedido.\n";
        $msg .= "_Gerado via Cantina Web em " . date('d/m/Y \à\s H:i:s') . "_";

        return $msg;
    }

    /**
     * Verifica se a entrega física do corte já foi confirmada e recebida
     */
    public static function verificarStatusRecebimento(string $dataConsumo, string $turnoConsumo): ?array
    {
        $prefixo = "Recebimento Fornecedor - {$dataConsumo} - {$turnoConsumo}%";

        $sql = "SELECT em.created_at, em.operador_id, em.justificativa,
                       u.nome AS operador_nome,
                       COUNT(DISTINCT em.produto_id) AS total_produtos_recebidos,
                       SUM(em.quantidade) AS total_quantidade_recebida
                FROM estoque_movimentacoes em
                LEFT JOIN usuarios u ON u.id = em.operador_id
                WHERE em.tipo_movimentacao = 'entrada_fornecedor'
                  AND em.justificativa LIKE :prefixo
                GROUP BY em.created_at, em.operador_id, em.justificativa, u.nome
                ORDER BY em.created_at DESC
                LIMIT 1";

        $reg = self::fetchOne($sql, ['prefixo' => $prefixo]);

        if (!$reg) {
            return null;
        }

        // Detalhamento quantitativo dos produtos entregues nesta remessa
        $sqlItens = "SELECT em.produto_id, SUM(em.quantidade) AS quantidade_entregue
                     FROM estoque_movimentacoes em
                     WHERE em.tipo_movimentacao = 'entrada_fornecedor'
                       AND em.justificativa LIKE :prefixo
                     GROUP BY em.produto_id";
        $itensMov = self::fetchAll($sqlItens, ['prefixo' => $prefixo]);
        $itensEntregues = [];
        $totalEntregueReal = 0;
        foreach ($itensMov as $im) {
            $pId = (int)$im['produto_id'];
            $qtd = (int)$im['quantidade_entregue'];
            $itensEntregues[$pId] = $qtd;
            $totalEntregueReal += $qtd;
        }

        return [
            'recebido'             => true,
            'operador_nome'        => $reg['operador_nome'] ?? 'Operador',
            'operador_id'          => (int)$reg['operador_id'],
            'data_recebimento'     => $reg['created_at'],
            'data_recebimento_fmt' => date('d/m/Y H:i', strtotime($reg['created_at'])),
            'total_produtos'       => (int)$reg['total_produtos_recebidos'],
            'total_quantidade'     => (int)$reg['total_quantidade_recebida'],
            'total_entregue_real'  => $totalEntregueReal,
            'itens_entregues'      => $itensEntregues,
            'justificativa'        => $reg['justificativa'],
        ];
    }

    /**
     * Registra atomicamente a conferência física e confirmação de recebimento da entrega do fornecedor
     * 
     * Efetua:
     * 1. Entrada física em estoque_movimentacoes ('entrada_fornecedor')
     * 2. Liquidação da demanda de tickets ('saida_encomenda')
     * 3. Atualização de estoque_atual em produtos com eventuais sobras/excedentes
     * 4. Registro na trilha de auditoria em logs_auditoria
     * 
     * @param string $dataConsumo YYYY-MM-DD
     * @param string $turnoConsumo 'matutino' ou 'vespertino'
     * @param int $operadorId ID do operador logado na tabela usuarios
     * @param array $itensRecebidos [['produto_id' => int, 'qtd_esperada' => int, 'qtd_entregue' => int], ...]
     * @param string|null $observacao
     * @param string $ipOrigem
     * @return array
     * @throws \DomainException|\InvalidArgumentException|\PDOException
     */
    public static function confirmarRecebimentoFornecedor(
        string $dataConsumo,
        string $turnoConsumo,
        int $operadorId,
        array $itensRecebidos,
        ?string $observacao = null,
        string $ipOrigem = '127.0.0.1'
    ): array {
        if (empty($itensRecebidos)) {
            throw new \InvalidArgumentException('Nenhum item informado para conferência de recebimento.');
        }

        // Trava rigorosa: recebimento físico só pode ser conferido e confirmado na data de consumo (hoje)
        $hoje = date('Y-m-d');
        if (!defined('CANTINA_TEST_MODE') && $dataConsumo !== $hoje) {
            throw new \DomainException(
                "A conferência física e confirmação de recebimento de fornecedor só podem ser realizadas na data de hoje (" . 
                date('d/m/Y') . "). Não é permitido registrar recebimento físico para datas retroativas ou futuras para preservar a integridade do estoque."
            );
        }

        // Verifica se já foi recebido anteriormente para evitar duplicidade
        $jaRecebido = self::verificarStatusRecebimento($dataConsumo, $turnoConsumo);
        if ($jaRecebido) {
            throw new \DomainException(
                "O recebimento da remessa do turno {$turnoConsumo} de " . 
                date('d/m/Y', strtotime($dataConsumo)) . 
                " já foi confirmado anteriormente por [{$jaRecebido['operador_nome']}]."
            );
        }

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            $totalEsperado = 0;
            $totalEntregue = 0;
            $totalExcedente = 0;
            $itensProcessados = [];

            $justificativaBaseEntrada = "Recebimento Fornecedor - {$dataConsumo} - {$turnoConsumo}";
            $justificativaBaseSaida = "Liquidação Demanda Encomenda - {$dataConsumo} - {$turnoConsumo}";

            if (!empty($observacao)) {
                $justificativaBaseEntrada .= " - Obs: " . mb_substr($observacao, 0, 100, 'UTF-8');
            }

            // Agrupa itens recebidos por produto_id para harmonizar salgados fracionados (grandes e pequenos)
            $itensAgrupados = [];
            foreach ($itensRecebidos as $item) {
                $produtoId = (int)($item['produto_id'] ?? 0);
                if ($produtoId <= 0) {
                    continue;
                }
                $fator = max(1, (int)($item['fator_conversao'] ?? 1));
                $qtdEsperada = max(0, (int)($item['qtd_esperada'] ?? 0));
                $qtdEntregue = max(0, (int)($item['qtd_entregue'] ?? 0));

                if (!isset($itensAgrupados[$produtoId])) {
                    $itensAgrupados[$produtoId] = [
                        'produto_id'            => $produtoId,
                        'total_esperado_pecas'  => 0,
                        'total_entregue_pecas'  => 0,
                        'demanda_esperada_unid' => 0,
                        'demanda_entregue_unid' => 0,
                    ];
                }

                $itensAgrupados[$produtoId]['total_esperado_pecas'] += $qtdEsperada;
                $itensAgrupados[$produtoId]['total_entregue_pecas'] += $qtdEntregue;
                $itensAgrupados[$produtoId]['demanda_esperada_unid'] += ($qtdEsperada * $fator);
                $itensAgrupados[$produtoId]['demanda_entregue_unid'] += ($qtdEntregue * $fator);
            }

            foreach ($itensAgrupados as $produtoId => $agrupado) {
                // Bloqueio pessimista do produto
                $stmtP = $pdo->prepare("SELECT id, codigo_interno, descricao, estoque_atual, tipo FROM produtos WHERE id = :id FOR UPDATE");
                $stmtP->execute(['id' => $produtoId]);
                $prod = $stmtP->fetch(\PDO::FETCH_ASSOC);

                if (!$prod) {
                    continue;
                }

                $totalEsperado += $agrupado['total_esperado_pecas'];
                $totalEntregue += $agrupado['total_entregue_pecas'];

                $qtdDemandaEsperada = $agrupado['demanda_esperada_unid'];
                $qtdDemandaEntregue = $agrupado['demanda_entregue_unid'];
                $excedente = $qtdDemandaEntregue - $qtdDemandaEsperada;

                if ($excedente > 0) {
                    $totalExcedente += $excedente;
                }

                // 1. Registra entrada da remessa física do fornecedor (em unidades de lanche consumíveis)
                if ($qtdDemandaEntregue > 0) {
                    $stmtEntrada = $pdo->prepare("
                        INSERT INTO estoque_movimentacoes (
                            produto_id, tipo_movimentacao, quantidade, operador_id, justificativa, created_at
                        ) VALUES (
                            :prod_id, 'entrada_fornecedor', :qtd, :op_id, :just, NOW()
                        )
                    ");
                    $stmtEntrada->execute([
                        'prod_id' => $produtoId,
                        'qtd'     => $qtdDemandaEntregue,
                        'op_id'   => $operadorId,
                        'just'    => $justificativaBaseEntrada
                    ]);
                }

                // 2. Registra baixa/liquidação da demanda das encomendas do corte
                if ($qtdDemandaEsperada > 0) {
                    $stmtSaida = $pdo->prepare("
                        INSERT INTO estoque_movimentacoes (
                            produto_id, tipo_movimentacao, quantidade, operador_id, justificativa, created_at
                        ) VALUES (
                            :prod_id, 'saida_encomenda', :qtd, :op_id, :just, NOW()
                        )
                    ");
                    $stmtSaida->execute([
                        'prod_id' => $produtoId,
                        'qtd'     => $qtdDemandaEsperada,
                        'op_id'   => $operadorId,
                        'just'    => $justificativaBaseSaida
                    ]);
                }

                // 3. Atualiza o estoque físico para venda avulsa:
                // Se o fornecedor entregou sobras/excedentes além da demanda,
                // essas unidades extras ficam disponíveis imediatamente no estoque_atual do balcão!
                if ($excedente != 0) {
                    $stmtUpEstoque = $pdo->prepare("
                        UPDATE produtos 
                        SET estoque_atual = GREATEST(0, estoque_atual + :diff), updated_at = NOW() 
                        WHERE id = :id
                    ");
                    $stmtUpEstoque->execute([
                        'diff' => $excedente,
                        'id'   => $produtoId
                    ]);
                }

                $itensProcessados[] = [
                    'produto_id'     => $produtoId,
                    'codigo_interno' => $prod['codigo_interno'],
                    'descricao'      => $prod['descricao'],
                    'qtd_esperada'   => $agrupado['total_esperado_pecas'],
                    'qtd_entregue'   => $agrupado['total_entregue_pecas'],
                    'excedente'      => $excedente,
                ];
            }

            // 4. Registra na trilha de auditoria (logs_auditoria)
            $stmtAudit = $pdo->prepare("
                INSERT INTO logs_auditoria (
                    operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at
                ) VALUES (
                    :op_id, 'recebimento_fornecedor', 'estoque_movimentacoes', 0, :just, :ip, NOW()
                )
            ");
            $justAudit = sprintf(
                "Recebimento da remessa %s (%s). Esperado: %d un, Entregue: %d un, Excedente: %d un.%s",
                $dataConsumo,
                $turnoConsumo,
                $totalEsperado,
                $totalEntregue,
                $totalExcedente,
                $observacao ? " Obs: {$observacao}" : ""
            );
            $stmtAudit->execute([
                'op_id' => $operadorId,
                'just'  => mb_substr($justAudit, 0, 255, 'UTF-8'),
                'ip'    => $ipOrigem
            ]);

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'sucesso'          => true,
                'data_consumo'     => $dataConsumo,
                'turno_consumo'    => $turnoConsumo,
                'total_esperado'   => $totalEsperado,
                'total_entregue'   => $totalEntregue,
                'total_excedente'  => $totalExcedente,
                'itens'            => $itensProcessados,
                'mensagem'         => "Recebimento confirmado com sucesso! Foram registradas {$totalEntregue} unidades entregues."
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lista pedidos agendados/online elegíveis para estorno conforme filtros informados
     * 
     * @param array $filtros [data_inicio, data_fim, aluno_id, status]
     * @return array
     */
    public static function listarParaEstorno(array $filtros = []): array
    {
        $whereClauses = ["p.status NOT IN ('cancelado', 'estornado')"];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $whereClauses[] = "p.data_consumo >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $whereClauses[] = "p.data_consumo <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        if (!empty($filtros['aluno_id'])) {
            $whereClauses[] = "p.aluno_id = :aluno_id";
            $params['aluno_id'] = (int)$filtros['aluno_id'];
        }

        $whereSql = implode(' AND ', $whereClauses);

        $sql = "
            SELECT 
                p.id,
                p.codigo_pedido,
                p.data_consumo,
                p.created_at,
                DATE(p.created_at) AS data_criacao,
                TIME_FORMAT(p.created_at, '%H:%i') AS hora_criacao,
                COALESCE(p.data_consumo, DATE(p.created_at)) AS data_venda,
                TIME_FORMAT(p.created_at, '%H:%i') AS hora_venda,
                'pedido' AS tipo_origem,
                p.turno_consumo,
                p.valor_total,
                p.status,
                p.aluno_id,
                a.nome AS aluno_nome,
                a.nome AS pessoa_nome,
                CONCAT('Aluno: ', a.nome, ' (', COALESCE(a.matricula, 'S/M'), ' - ', COALESCE(tur.nome_turma, 'Geral'), ') • Resp: ', r.nome) AS pessoa_detalhe,
                a.matricula AS aluno_matricula,
                tur.nome_turma AS aluno_turma,
                p.responsavel_id,
                r.nome AS responsavel_nome,
                r.cpf AS responsavel_cpf,
                COUNT(ip.id) AS total_itens
            FROM pedidos p
            INNER JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas tur ON tur.id = a.turma_id
            INNER JOIN responsaveis r ON r.id = p.responsavel_id
            LEFT JOIN itens_pedido ip ON ip.pedido_id = p.id
            WHERE {$whereSql}
            GROUP BY p.id
            ORDER BY p.data_consumo DESC, p.id DESC
        ";

        return self::fetchAll($sql, $params);
    }

    /**
     * Executa o estorno atômico de um pedido online pela equipe da cantina
     * 
     * @param int $pedidoId
     * @param int $operadorId
     * @param string $motivo
     * @param string $ipOrigem
     * @return array Dados do pedido estornado
     * @throws \DomainException|\InvalidArgumentException|\Throwable
     */
    public static function estornarPedido(
        int $pedidoId,
        int $operadorId,
        string $motivo,
        string $ipOrigem = '127.0.0.1',
        bool $produtoConsumido = false
    ): array {
        $motivoLimpo = trim($motivo);
        if (mb_strlen($motivoLimpo) < 5) {
            throw new \InvalidArgumentException('O motivo do estorno deve conter no mínimo 5 caracteres.');
        }

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Busca pedido com bloqueio pessimista
            $stmt = $pdo->prepare("
                SELECT p.*, a.nome AS aluno_nome, r.nome AS responsavel_nome
                FROM pedidos p
                INNER JOIN alunos a ON a.id = p.aluno_id
                INNER JOIN responsaveis r ON r.id = p.responsavel_id
                WHERE p.id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $pedidoId]);
            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pedido) {
                throw new \DomainException("Pedido #{$pedidoId} não foi localizado.");
            }

            if ($pedido['status'] === self::STATUS_CANCELADO || $pedido['status'] === self::STATUS_ESTORNADO) {
                throw new \DomainException("O pedido #{$pedido['codigo_pedido']} já se encontra cancelado ou estornado.");
            }

            // 2. Atualiza status do pedido
            $stmtUpPedido = $pdo->prepare("
                UPDATE pedidos
                SET status = :status,
                    estornado_em = NOW(),
                    estornado_por = :op_id,
                    motivo_estorno = :motivo,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpPedido->execute([
                'status' => self::STATUS_ESTORNADO,
                'op_id'  => $operadorId,
                'motivo' => mb_substr($motivoLimpo, 0, 255),
                'id'     => $pedidoId
            ]);

            // 3. Estorno integral de 100% creditado na carteira familiar
            $valorTotal = (float)$pedido['valor_total'];
            $descricaoTransacao = sprintf(
                "Estorno da cantina - Pedido: %s - Aluno: %s (Motivo: %s)",
                $pedido['codigo_pedido'],
                $pedido['aluno_nome'],
                $motivoLimpo
            );

            TransacaoCarteira::creditar(
                (int)$pedido['responsavel_id'],
                $valorTotal,
                TransacaoCarteira::TIPO_ESTORNO_BALCAO,
                $descricaoTransacao,
                $pedido['codigo_pedido'],
                (int)$pedido['aluno_id'],
                $operadorId
            );

            // 4. Invalida tickets térmicos vinculados
            $stmtTickets = $pdo->prepare("
                UPDATE tickets
                SET status = 'estornado',
                    estornado_em = NOW(),
                    motivo_estorno = :motivo
                WHERE pedido_id = :pedido_id
                  AND status != 'cancelado'
            ");
            $stmtTickets->execute([
                'motivo'    => mb_substr("Estorno operacional: " . $motivoLimpo, 0, 255),
                'pedido_id' => $pedidoId
            ]);

            // 5. Tratamento de estoque
            // Regra de Negócio: Pedidos de datas anteriores já foram consumidos/descartados.
            // Um salgado ou lanche de 15 dias atrás não pode voltar ao estoque físico atual.
            // Pedidos futuros liberam reserva; pedidos já consumidos ou de datas passadas mantêm o estoque físico intacto.
            $stmtItens = $pdo->prepare("
                SELECT ip.*, prod.tipo, prod.descricao AS produto_descricao, prod.estoque_reservado
                FROM itens_pedido ip
                INNER JOIN produtos prod ON prod.id = ip.produto_id
                WHERE ip.pedido_id = :pedido_id
            ");
            $stmtItens->execute(['pedido_id' => $pedidoId]);
            $itens = $stmtItens->fetchAll(\PDO::FETCH_ASSOC);

            $ehConsumoHoje = ($pedido['data_consumo'] === date('Y-m-d'));
            $ehConsumoFuturo = ($pedido['data_consumo'] > date('Y-m-d'));

            foreach ($itens as $item) {
                $prodId = (int)$item['produto_id'];
                $qtd = (int)$item['quantidade'];

                // Se era industrializado e tinha reserva para hoje ou consumo futuro, libera a reserva
                if ($item['tipo'] === 'industrializado_continuo' && ($ehConsumoHoje || $ehConsumoFuturo)) {
                    $stmtLiberaReserva = $pdo->prepare("
                        UPDATE produtos
                        SET estoque_reservado = GREATEST(0, estoque_reservado - :qtd),
                            updated_at = NOW()
                        WHERE id = :prod_id
                    ");
                    $stmtLiberaReserva->execute([
                        'qtd'     => $qtd,
                        'prod_id' => $prodId
                    ]);
                }

                // Devolução ao estoque físico: APENAS no próprio dia de hoje E se o produto NÃO foi consumido ainda (antes do aluno pegar)
                if ($ehConsumoHoje && !$produtoConsumido && $pedido['status'] !== self::STATUS_ENTREGUE && $item['tipo'] === 'industrializado_continuo') {
                    $stmtDevolveFisico = $pdo->prepare("
                        UPDATE produtos
                        SET estoque_atual = estoque_atual + :qtd,
                            updated_at = NOW()
                        WHERE id = :prod_id
                    ");
                    $stmtDevolveFisico->execute([
                        'qtd'     => $qtd,
                        'prod_id' => $prodId
                    ]);

                    $stmtMov = $pdo->prepare("
                        INSERT INTO estoque_movimentacoes (
                            produto_id, tipo_movimentacao, quantidade, motivo_perda,
                            nota_fiscal_id, operador_id, justificativa, created_at
                        ) VALUES (
                            :prod_id, 'entrada_estorno', :qtd, NULL,
                            NULL, :op_id, :just, NOW()
                        )
                    ");
                    $stmtMov->execute([
                        'prod_id' => $prodId,
                        'qtd'     => $qtd,
                        'op_id'   => $operadorId,
                        'just'    => "Reintegração por estorno do Pedido #{$pedido['codigo_pedido']} (não consumido hoje): {$motivoLimpo}"
                    ]);
                } elseif ($pedido['data_consumo'] < date('Y-m-d') || $produtoConsumido || $pedido['status'] === self::STATUS_ENTREGUE) {
                    // Passou do dia do pedido OU produto já consumido hoje: não adiciona ao estoque físico atual
                    // e registra o item como perda de estoque
                    $stmtPerda = $pdo->prepare("
                        INSERT INTO estoque_movimentacoes (
                            produto_id, tipo_movimentacao, quantidade, motivo_perda,
                            nota_fiscal_id, operador_id, justificativa, created_at
                        ) VALUES (
                            :prod_id, 'saida_perda', :qtd, 'sobra_producao',
                            NULL, :op_id, :just, NOW()
                        )
                    ");
                    $motivoPerdaTexto = ($ehConsumoHoje && $produtoConsumido)
                        ? "produto consumido hoje antes do estorno"
                        : "item não reintegrado fisicamente por decurso de prazo/consumo";

                    $stmtPerda->execute([
                        'prod_id' => $prodId,
                        'qtd'     => $qtd,
                        'op_id'   => $operadorId,
                        'just'    => "Perda por estorno do Pedido #{$pedido['codigo_pedido']} (" . date('d/m/Y', strtotime($pedido['data_consumo'])) . "): {$motivoPerdaTexto}. Motivo: {$motivoLimpo}"
                    ]);
                }
            }

            // 6. Trilha obrigatória de auditoria (logs_auditoria)
            $obsEstoqueRetroativo = ($pedido['data_consumo'] < date('Y-m-d'))
                ? " [Estorno financeiro retroativo: estoque físico mantido sem reintegração por decurso de prazo/consumo prévio]"
                : ($produtoConsumido ? " [Produto consumido hoje: estoque físico não alterado (registrado como perda de estoque)]" : " [Estoque físico reintegrado]");

            Auditoria::registrar(
                $operadorId,
                Auditoria::ACAO_ESTORNO_BALCAO,
                'pedidos',
                $pedidoId,
                sprintf(
                    "Estorno operacional de pedido #%s (Aluno: %s, Valor: R$ %s).%s Motivo: %s",
                    $pedido['codigo_pedido'],
                    $pedido['aluno_nome'],
                    number_format($valorTotal, 2, ',', '.'),
                    $obsEstoqueRetroativo,
                    $motivoLimpo
                ),
                $ipOrigem
            );

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return self::buscarPorId($pedidoId);
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
