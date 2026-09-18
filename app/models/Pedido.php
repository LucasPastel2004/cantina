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

                // Valida estoque para industrializados (RN-12 e RN-13)
                if ($prod['tipo'] === 'industrializado_continuo') {
                    $estoqueAtual = (int)$prod['estoque_atual'];
                    $estoqueReservado = (int)$prod['estoque_reservado'];
                    $estoqueDisponivel = $estoqueAtual - $estoqueReservado;

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

                if ($prod['tipo'] === 'industrializado_continuo') {
                    $disponivel = (int)$prod['estoque_atual'] - (int)$prod['estoque_reservado'];
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
}
