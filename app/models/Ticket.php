<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo: Ticket (Bobina Térmica 80mm e Fichas de Consumo)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16, Seção 11.18, Fluxos 6 e 7, RN-16, RN-17)
 */

namespace App\Models;

use App\Core\Model;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class Ticket extends Model
{
    protected string $table = 'tickets';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = false;

    public const STATUS_ATIVO     = 'ativo';
    public const STATUS_IMPRESSO  = 'impresso';
    public const STATUS_ESTORNADO = 'estornado';
    public const STATUS_CANCELADO = 'cancelado';

    /**
     * Busca um ticket detalhado pelo seu ID interno
     * 
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        return self::fetchOne("
            SELECT 
                t.id AS ticket_id,
                t.pedido_id,
                t.item_pedido_id,
                t.codigo_ticket,
                t.numero_via,
                t.status AS ticket_status,
                t.tag_alergenos,
                t.impresso_em,
                t.estornado_em,
                t.motivo_estorno,
                t.created_at AS ticket_created_at,
                p.codigo_pedido,
                p.data_consumo,
                p.turno_consumo,
                p.status AS pedido_status,
                p.valor_total AS pedido_valor_total,
                ip.quantidade AS item_quantidade,
                ip.preco_unitario AS item_preco_unitario,
                prod.id AS produto_id,
                prod.codigo_interno,
                prod.descricao AS produto_descricao,
                prod.tipo AS produto_tipo,
                prod.preco_venda AS produto_preco_venda,
                cat.nome AS categoria_nome,
                a.id AS aluno_id,
                a.nome AS aluno_nome,
                a.matricula AS aluno_matricula,
                a.turno AS aluno_turno,
                tur.id AS turma_id,
                tur.nome_turma,
                tur.serie,
                tur.ordem_impressao,
                r.id AS responsavel_id,
                r.nome AS responsavel_nome,
                r.cpf AS responsavel_cpf,
                r.whatsapp AS responsavel_whatsapp
            FROM tickets t
            INNER JOIN pedidos p ON p.id = t.pedido_id
            INNER JOIN itens_pedido ip ON ip.id = t.item_pedido_id
            INNER JOIN produtos prod ON prod.id = ip.produto_id
            LEFT JOIN categorias cat ON cat.id = prod.categoria_id
            INNER JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas tur ON tur.id = a.turma_id
            INNER JOIN responsaveis r ON r.id = p.responsavel_id
            WHERE t.id = :id
            LIMIT 1
        ", ['id' => $id]);
    }

    /**
     * Busca um ticket detalhado pelo código alfanumérico (ex: TCK-2026-AB12-01)
     * 
     * @param string $codigo
     * @return array|null
     */
    public static function buscarPorCodigo(string $codigo): ?array
    {
        return self::fetchOne("
            SELECT 
                t.id AS ticket_id,
                t.pedido_id,
                t.item_pedido_id,
                t.codigo_ticket,
                t.numero_via,
                t.status AS ticket_status,
                t.tag_alergenos,
                t.impresso_em,
                t.estornado_em,
                t.motivo_estorno,
                t.created_at AS ticket_created_at,
                p.codigo_pedido,
                p.data_consumo,
                p.turno_consumo,
                p.status AS pedido_status,
                p.valor_total AS pedido_valor_total,
                ip.quantidade AS item_quantidade,
                ip.preco_unitario AS item_preco_unitario,
                prod.id AS produto_id,
                prod.codigo_interno,
                prod.descricao AS produto_descricao,
                prod.tipo AS produto_tipo,
                cat.nome AS categoria_nome,
                a.id AS aluno_id,
                a.nome AS aluno_nome,
                a.matricula AS aluno_matricula,
                a.turno AS aluno_turno,
                tur.id AS turma_id,
                tur.nome_turma,
                tur.serie,
                tur.ordem_impressao,
                r.id AS responsavel_id,
                r.nome AS responsavel_nome,
                r.cpf AS responsavel_cpf
            FROM tickets t
            INNER JOIN pedidos p ON p.id = t.pedido_id
            INNER JOIN itens_pedido ip ON ip.id = t.item_pedido_id
            INNER JOIN produtos prod ON prod.id = ip.produto_id
            LEFT JOIN categorias cat ON cat.id = prod.categoria_id
            INNER JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas tur ON tur.id = a.turma_id
            INNER JOIN responsaveis r ON r.id = p.responsavel_id
            WHERE t.codigo_ticket = :cod
            LIMIT 1
        ", ['cod' => trim($codigo)]);
    }

    /**
     * Lista todos os tickets de um turno e data, ordenados sequencialmente para a bobina contínua
     * 
     * Regra de ordenação oficial do FSD:
     * turmas.ordem_impressao ASC, turmas.nome_turma ASC, alunos.nome ASC, tickets.id ASC
     * 
     * @param string $dataConsumo YYYY-MM-DD
     * @param string $turno matutino / vespertino
     * @param int|null $turmaId Filtro opcional por turma
     * @param string|null $status Filtro opcional por status do ticket ('ativo', 'impresso', etc.)
     * @return array Lista de tickets detalhados
     */
    public static function listarPorTurnoEData(
        string $dataConsumo,
        string $turno,
        ?int $turmaId = null,
        ?string $status = null
    ): array {
        $params = [
            'data'  => $dataConsumo,
            'turno' => $turno,
        ];

        $whereTurma = "";
        if ($turmaId !== null && $turmaId > 0) {
            $whereTurma = " AND tur.id = :turma_id";
            $params['turma_id'] = $turmaId;
        }

        $whereStatus = "";
        if ($status !== null && in_array($status, [self::STATUS_ATIVO, self::STATUS_IMPRESSO, self::STATUS_ESTORNADO, self::STATUS_CANCELADO], true)) {
            $whereStatus = " AND t.status = :status";
            $params['status'] = $status;
        }

        $sql = "
            SELECT 
                t.id AS ticket_id,
                t.pedido_id,
                t.item_pedido_id,
                t.codigo_ticket,
                t.numero_via,
                t.status AS ticket_status,
                t.tag_alergenos,
                t.impresso_em,
                t.estornado_em,
                t.motivo_estorno,
                t.created_at AS ticket_created_at,
                p.codigo_pedido,
                p.data_consumo,
                p.turno_consumo,
                p.status AS pedido_status,
                prod.id AS produto_id,
                prod.codigo_interno,
                prod.descricao AS produto_descricao,
                prod.tipo AS produto_tipo,
                prod.preco_venda AS produto_preco_venda,
                cat.nome AS categoria_nome,
                a.id AS aluno_id,
                a.nome AS aluno_nome,
                a.matricula AS aluno_matricula,
                a.turno AS aluno_turno,
                COALESCE(tur.id, 0) AS turma_id,
                COALESCE(tur.nome_turma, 'Sem Turma') AS nome_turma,
                COALESCE(tur.serie, 'Geral') AS serie,
                COALESCE(tur.ordem_impressao, 999) AS ordem_impressao,
                r.id AS responsavel_id,
                r.nome AS responsavel_nome,
                r.cpf AS responsavel_cpf
            FROM tickets t
            INNER JOIN pedidos p ON p.id = t.pedido_id
            INNER JOIN itens_pedido ip ON ip.id = t.item_pedido_id
            INNER JOIN produtos prod ON prod.id = ip.produto_id
            LEFT JOIN categorias cat ON cat.id = prod.categoria_id
            INNER JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas tur ON tur.id = a.turma_id
            INNER JOIN responsaveis r ON r.id = p.responsavel_id
            WHERE p.data_consumo = :data
              AND p.turno_consumo = :turno
              AND p.status != 'cancelado'
              AND t.status != 'cancelado'
              {$whereTurma}
              {$whereStatus}
            ORDER BY 
                COALESCE(tur.ordem_impressao, 999) ASC,
                COALESCE(tur.nome_turma, 'ZZZ') ASC,
                a.nome ASC,
                t.id ASC
        ";

        return self::fetchAll($sql, $params);
    }

    /**
     * Agrupa uma lista de tickets por Turma/Série para separação em blocos de sala de aula
     * 
     * @param array $tickets Lista plana de tickets retornada por listarPorTurnoEData
     * @return array Array de turmas agrupadas com metadados e seus respectivos tickets
     */
    public static function agruparPorTurma(array $tickets): array
    {
        $grupos = [];

        foreach ($tickets as $t) {
            $turmaId = (int)$t['turma_id'];
            $chave = $turmaId > 0 ? "turma_{$turmaId}" : "turma_sem_turma";

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = [
                    'turma_id'            => $turmaId,
                    'nome_turma'          => $t['nome_turma'],
                    'serie'               => $t['serie'],
                    'ordem_impressao'     => (int)$t['ordem_impressao'],
                    'total_tickets'       => 0,
                    'total_alunos'        => 0,
                    'total_com_alergenos' => 0,
                    'total_impressos'     => 0,
                    'total_pendentes'     => 0,
                    'alunos_ids'          => [],
                    'tickets'             => []
                ];
            }

            $grupos[$chave]['total_tickets']++;
            $grupos[$chave]['tickets'][] = $t;

            if (!empty($t['tag_alergenos'])) {
                $grupos[$chave]['total_com_alergenos']++;
            }

            if ($t['ticket_status'] === self::STATUS_IMPRESSO) {
                $grupos[$chave]['total_impressos']++;
            } else {
                $grupos[$chave]['total_pendentes']++;
            }

            $alunoId = (int)$t['aluno_id'];
            if (!in_array($alunoId, $grupos[$chave]['alunos_ids'], true)) {
                $grupos[$chave]['alunos_ids'][] = $alunoId;
                $grupos[$chave]['total_alunos']++;
            }
        }

        return array_values($grupos);
    }

    /**
     * Marca uma lista de tickets como impressos ('impresso') e registra a data/hora
     * 
     * @param array|int $ticketIds
     * @return int Quantidade de tickets atualizados
     */
    public static function marcarComoImpresso(array|int $ticketIds): int
    {
        $ids = is_array($ticketIds) ? array_map('intval', $ticketIds) : [(int)$ticketIds];
        $ids = array_filter($ids, fn($id) => $id > 0);

        if (empty($ids)) {
            return 0;
        }

        $pdo = self::getPdo();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // Apenas tickets que ainda não foram estornados ou cancelados
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET status = 'impresso',
                impresso_em = COALESCE(impresso_em, NOW())
            WHERE id IN ({$placeholders})
              AND status IN ('ativo', 'impresso')
        ");

        $stmt->execute(array_values($ids));

        return $stmt->rowCount();
    }

    /**
     * Realiza a busca rápida de tickets do dia para atendimento presencial e 2ª via
     * 
     * @param string $termo Nome do aluno, matrícula, turma ou código do ticket
     * @param string|null $data Consumo (padrão hoje)
     * @param string|null $turno (opcional)
     * @param int $limite
     * @return array
     */
    public static function buscarParaConsulta(
        string $termo,
        ?string $data = null,
        ?string $turno = null,
        int $limite = 50
    ): array {
        $termoLimpo = trim($termo);
        $dataConsumo = $data ?? date('Y-m-d');

        $whereClausula = "p.data_consumo = :data_cons AND p.status != 'cancelado'";
        $params = [
            'data_cons' => $dataConsumo,
        ];

        if ($turno !== null && in_array($turno, ['matutino', 'vespertino'], true)) {
            $whereClausula .= " AND p.turno_consumo = :turno_cons";
            $params['turno_cons'] = $turno;
        }

        if (!empty($termoLimpo)) {
            $like = '%' . $termoLimpo . '%';
            // Utiliza identificadores de parâmetros únicos para Prepared Statements estritos
            $whereClausula .= " AND (
                a.nome LIKE :busca_nome 
                OR a.matricula LIKE :busca_mat 
                OR tur.nome_turma LIKE :busca_turma 
                OR t.codigo_ticket LIKE :busca_cod 
                OR p.codigo_pedido LIKE :busca_ped
            )";
            $params['busca_nome']  = $like;
            $params['busca_mat']   = $like;
            $params['busca_turma'] = $like;
            $params['busca_cod']   = $like;
            $params['busca_ped']   = $like;
        }

        $sql = "
            SELECT 
                t.id AS ticket_id,
                t.codigo_ticket,
                t.numero_via,
                t.status AS ticket_status,
                t.tag_alergenos,
                t.impresso_em,
                t.estornado_em,
                t.motivo_estorno,
                t.created_at AS ticket_created_at,
                p.id AS pedido_id,
                p.codigo_pedido,
                p.data_consumo,
                p.turno_consumo,
                p.status AS pedido_status,
                prod.id AS produto_id,
                prod.codigo_interno,
                prod.descricao AS produto_descricao,
                prod.tipo AS produto_tipo,
                prod.preco_venda AS produto_preco_venda,
                cat.nome AS categoria_nome,
                a.id AS aluno_id,
                a.nome AS aluno_nome,
                a.matricula AS aluno_matricula,
                tur.id AS turma_id,
                COALESCE(tur.nome_turma, 'Sem Turma') AS nome_turma,
                COALESCE(tur.serie, 'Geral') AS serie,
                r.nome AS responsavel_nome,
                r.whatsapp AS responsavel_whatsapp
            FROM tickets t
            INNER JOIN pedidos p ON p.id = t.pedido_id
            INNER JOIN itens_pedido ip ON ip.id = t.item_pedido_id
            INNER JOIN produtos prod ON prod.id = ip.produto_id
            LEFT JOIN categorias cat ON cat.id = prod.categoria_id
            INNER JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas tur ON tur.id = a.turma_id
            INNER JOIN responsaveis r ON r.id = p.responsavel_id
            WHERE {$whereClausula}
            ORDER BY 
                t.id DESC
            LIMIT {$limite}
        ";

        return self::fetchAll($sql, $params);
    }

    /**
     * Executa a emissão de 2ª via com incremento de via e registro obrigatório em auditoria
     * 
     * @param int $ticketId
     * @param int $operadorId
     * @param string $justificativa
     * @param string $ipOrigem
     * @return array Dados atualizados do ticket para impressão
     * @throws \DomainException|\InvalidArgumentException|\PDOException
     */
    public static function reimprimirSegundaVia(
        int $ticketId,
        int $operadorId,
        string $justificativa,
        string $ipOrigem = '127.0.0.1'
    ): array {
        $justificativaLimpa = trim($justificativa);
        if (mb_strlen($justificativaLimpa) < 5) {
            throw new \InvalidArgumentException('A justificativa da 2ª via deve conter no mínimo 5 caracteres.');
        }

        $ticket = self::buscarPorId($ticketId);
        if (!$ticket) {
            throw new \DomainException('Ticket não localizado no sistema.');
        }

        if ($ticket['ticket_status'] === self::STATUS_CANCELADO) {
            throw new \DomainException('Não é possível emitir 2ª via de um ticket com pedido cancelado.');
        }

        if ($ticket['ticket_status'] === self::STATUS_ESTORNADO) {
            throw new \DomainException('Não é possível emitir 2ª via de um ticket estornado no balcão.');
        }

        $novaVia = ((int)$ticket['numero_via']) + 1;

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Atualiza o ticket para a nova via
            $stmt = $pdo->prepare("
                UPDATE tickets 
                SET numero_via = :nova_via,
                    status = 'impresso',
                    impresso_em = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                'nova_via' => $novaVia,
                'id'       => $ticketId
            ]);

            // 2. Grava registro na trilha compulsória de auditoria (logs_auditoria)
            Auditoria::registrar(
                $operadorId,
                Auditoria::ACAO_REIMPRESSAO_TICKET,
                'tickets',
                $ticketId,
                sprintf("Emissão de 2ª via (Via %d) do ticket %s para o aluno %s. Justificativa: %s", 
                    $novaVia, 
                    $ticket['codigo_ticket'], 
                    $ticket['aluno_nome'], 
                    $justificativaLimpa
                ),
                $ipOrigem
            );

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return self::buscarPorId($ticketId);
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Calcula as métricas consolidadas de tickets para o turno especificado
     * 
     * @param string $dataConsumo
     * @param string $turno
     * @return array
     */
    public static function obterMetricasTurno(string $dataConsumo, string $turno): array
    {
        $dados = self::fetchOne("
            SELECT 
                COUNT(t.id) AS total_tickets,
                SUM(CASE WHEN t.status = 'impresso' THEN 1 ELSE 0 END) AS total_impressos,
                SUM(CASE WHEN t.status = 'ativo' THEN 1 ELSE 0 END) AS total_pendentes,
                SUM(CASE WHEN t.tag_alergenos IS NOT NULL AND t.tag_alergenos != '' THEN 1 ELSE 0 END) AS total_com_alergenos,
                COUNT(DISTINCT p.aluno_id) AS total_alunos,
                COUNT(DISTINCT a.turma_id) AS total_turmas
            FROM tickets t
            INNER JOIN pedidos p ON p.id = t.pedido_id
            INNER JOIN alunos a ON a.id = p.aluno_id
            WHERE p.data_consumo = :data
              AND p.turno_consumo = :turno
              AND p.status != 'cancelado'
              AND t.status != 'cancelado'
        ", [
            'data'  => $dataConsumo,
            'turno' => $turno
        ]);

        return [
            'total_tickets'       => (int)($dados['total_tickets'] ?? 0),
            'total_impressos'     => (int)($dados['total_impressos'] ?? 0),
            'total_pendentes'     => (int)($dados['total_pendentes'] ?? 0),
            'total_com_alergenos' => (int)($dados['total_com_alergenos'] ?? 0),
            'total_alunos'        => (int)($dados['total_alunos'] ?? 0),
            'total_turmas'        => (int)($dados['total_turmas'] ?? 0),
        ];
    }
}
