<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Dados: Alunos (Estudantes Dependentes)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Aluno extends Model
{
    protected string $table = 'alunos';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Lista alunos com paginação/filtros, dados de turma, responsável e alérgenos
     * 
     * @param array $filtros Filtros de busca: 'busca', 'turma_id', 'turno', 'status' ('ativos', 'inativos', 'todos')
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     */
    public static function listarTodos(array $filtros = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT a.*,
                       t.serie, t.nome_turma, t.turno AS turno_turma,
                       r.nome AS responsavel_nome, r.cpf AS responsavel_cpf, r.whatsapp AS responsavel_whatsapp,
                       (
                           SELECT GROUP_CONCAT(ra.nome ORDER BY ra.id SEPARATOR ', ')
                           FROM aluno_restricoes ar
                           INNER JOIN restricoes_alimentares ra ON ra.id = ar.restricao_id
                           WHERE ar.aluno_id = a.id
                       ) AS alergenos_resumo
                FROM alunos a
                INNER JOIN turmas t ON t.id = a.turma_id
                INNER JOIN responsaveis r ON r.id = a.responsavel_id
                WHERE 1=1";

        $params = [];

        // Filtro por status
        $status = $filtros['status'] ?? 'ativos';
        if ($status === 'ativos') {
            $sql .= " AND a.ativo = 1 AND a.deleted_at IS NULL";
        } elseif ($status === 'inativos') {
            $sql .= " AND (a.ativo = 0 OR a.deleted_at IS NOT NULL)";
        }

        // Filtro por turma
        if (!empty($filtros['turma_id']) && (int)$filtros['turma_id'] > 0) {
            $sql .= " AND a.turma_id = :turma_id";
            $params['turma_id'] = (int)$filtros['turma_id'];
        }

        // Filtro por turno
        if (!empty($filtros['turno']) && in_array($filtros['turno'], ['matutino', 'vespertino'])) {
            $sql .= " AND a.turno = :turno";
            $params['turno'] = $filtros['turno'];
        }

        // Filtro de busca textual (placeholders distintos para evitar SQLSTATE[HY093])
        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (a.nome LIKE :busca_nome OR a.matricula LIKE :busca_mat OR r.nome LIKE :busca_resp OR r.cpf LIKE :busca_cpf)";
            $params['busca_nome'] = $termo;
            $params['busca_mat']  = $termo;
            $params['busca_resp'] = $termo;
            $params['busca_cpf']  = $termo;
        }

        $sql .= " ORDER BY t.ordem_impressao ASC, t.nome_turma ASC, a.nome ASC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null && $offset >= 0) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Retorna o total de alunos encontrados com os filtros
     */
    public static function contarTodos(array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM alunos a
                INNER JOIN turmas t ON t.id = a.turma_id
                INNER JOIN responsaveis r ON r.id = a.responsavel_id
                WHERE 1=1";

        $params = [];

        $status = $filtros['status'] ?? 'ativos';
        if ($status === 'ativos') {
            $sql .= " AND a.ativo = 1 AND a.deleted_at IS NULL";
        } elseif ($status === 'inativos') {
            $sql .= " AND (a.ativo = 0 OR a.deleted_at IS NOT NULL)";
        }

        if (!empty($filtros['turma_id']) && (int)$filtros['turma_id'] > 0) {
            $sql .= " AND a.turma_id = :turma_id";
            $params['turma_id'] = (int)$filtros['turma_id'];
        }

        if (!empty($filtros['turno']) && in_array($filtros['turno'], ['matutino', 'vespertino'])) {
            $sql .= " AND a.turno = :turno";
            $params['turno'] = $filtros['turno'];
        }

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (a.nome LIKE :busca_nome OR a.matricula LIKE :busca_mat OR r.nome LIKE :busca_resp OR r.cpf LIKE :busca_cpf)";
            $params['busca_nome'] = $termo;
            $params['busca_mat']  = $termo;
            $params['busca_resp'] = $termo;
            $params['busca_cpf']  = $termo;
        }

        $res = self::fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Alias para busca rápida de alunos com turma e responsável (utilizado pelo PDV)
     *
     * @param array $filtros
     * @param int $limite
     * @return array
     */
    public static function listarComTurma(array $filtros = [], int $limite = 10): array
    {
        if (isset($filtros['status']) && $filtros['status'] === 'ativo') {
            $filtros['status'] = 'ativos';
        }
        $resultado = self::listarTodos($filtros);
        if ($limite > 0 && count($resultado) > $limite) {
            return array_slice($resultado, 0, $limite);
        }
        return $resultado;
    }


    /**
     * Busca um aluno pelo ID trazendo dados completos
     */
    public static function buscarPorId(int $id, bool $incluirInativos = true): ?array
    {
        $sql = "SELECT a.*,
                       t.serie, t.nome_turma, t.turno AS turno_turma,
                       r.nome AS responsavel_nome, r.cpf AS responsavel_cpf, r.whatsapp AS responsavel_whatsapp, r.email AS responsavel_email,
                       (
                           SELECT GROUP_CONCAT(ra.nome ORDER BY ra.id SEPARATOR ', ')
                           FROM aluno_restricoes ar
                           INNER JOIN restricoes_alimentares ra ON ra.id = ar.restricao_id
                           WHERE ar.aluno_id = a.id
                       ) AS alergenos_resumo
                FROM alunos a
                INNER JOIN turmas t ON t.id = a.turma_id
                INNER JOIN responsaveis r ON r.id = a.responsavel_id
                WHERE a.id = :id";

        if (!$incluirInativos) {
            $sql .= " AND a.ativo = 1 AND a.deleted_at IS NULL";
        }

        $sql .= " LIMIT 1";

        $aluno = self::fetchOne($sql, ['id' => $id]);
        if ($aluno) {
            $aluno['alergenos_ids'] = self::obterIdsAlergenos($id);
        }

        return $aluno;
    }

    /**
     * Busca um aluno pela matrícula
     */
    public static function buscarPorMatricula(string $matricula, bool $incluirInativos = true): ?array
    {
        $sql = "SELECT * FROM alunos WHERE matricula = :matricula";
        if (!$incluirInativos) {
            $sql .= " AND ativo = 1 AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";

        return self::fetchOne($sql, ['matricula' => trim($matricula)]);
    }

    /**
     * Retorna os alunos ativos vinculados a um responsável
     */
    public static function listarPorResponsavel(int $responsavelId, bool $apenasAtivos = true): array
    {
        $sql = "SELECT a.*, t.serie, t.nome_turma, t.turno AS turno_turma,
                       (
                           SELECT GROUP_CONCAT(ra.nome ORDER BY ra.id SEPARATOR ', ')
                           FROM aluno_restricoes ar
                           INNER JOIN restricoes_alimentares ra ON ra.id = ar.restricao_id
                           WHERE ar.aluno_id = a.id
                       ) AS alergenos_resumo
                FROM alunos a
                INNER JOIN turmas t ON t.id = a.turma_id
                WHERE a.responsavel_id = :responsavel_id";

        if ($apenasAtivos) {
            $sql .= " AND a.ativo = 1 AND a.deleted_at IS NULL";
        }

        $sql .= " ORDER BY a.nome ASC";

        return self::fetchAll($sql, ['responsavel_id' => $responsavelId]);
    }

    /**
     * Cadastra um novo aluno
     */
    public static function criar(array $dados, ?int $usuarioId = null): int
    {
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO alunos (
                    responsavel_id, turma_id, matricula, nome,
                    foto_url, turno, ativo,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :responsavel_id, :turma_id, :matricula, :nome,
                    :foto_url, :turno, :ativo,
                    :created_by, :updated_by, :created_at, :updated_at
                )";

        self::execute($sql, [
            'responsavel_id' => (int)$dados['responsavel_id'],
            'turma_id'       => (int)$dados['turma_id'],
            'matricula'      => trim($dados['matricula']),
            'nome'           => trim($dados['nome']),
            'foto_url'       => $dados['foto_url'] ?? null,
            'turno'          => in_array($dados['turno'] ?? '', ['matutino', 'vespertino']) ? $dados['turno'] : 'matutino',
            'ativo'          => isset($dados['ativo']) ? (int)(bool)$dados['ativo'] : 1,
            'created_by'     => $usuarioId,
            'updated_by'     => $usuarioId,
            'created_at'     => $now,
            'updated_at'     => $now
        ]);

        $novoId = (int)self::lastInsertId();

        // Se houver alérgenos informados, vincula
        if (isset($dados['alergenos']) && is_array($dados['alergenos'])) {
            RestricaoAlimentar::sincronizarAluno($novoId, $dados['alergenos']);
        }

        return $novoId;
    }

    /**
     * Atualiza os dados de um aluno
     */
    public static function atualizar(int $id, array $dados, ?int $usuarioId = null): bool
    {
        $sql = "UPDATE alunos 
                SET responsavel_id = :responsavel_id,
                    turma_id       = :turma_id,
                    matricula      = :matricula,
                    nome           = :nome,
                    turno          = :turno,
                    updated_by     = :updated_by,
                    updated_at     = :updated_at";

        $params = [
            'id'             => $id,
            'responsavel_id' => (int)$dados['responsavel_id'],
            'turma_id'       => (int)$dados['turma_id'],
            'matricula'      => trim($dados['matricula']),
            'nome'           => trim($dados['nome']),
            'turno'          => in_array($dados['turno'] ?? '', ['matutino', 'vespertino']) ? $dados['turno'] : 'matutino',
            'updated_by'     => $usuarioId,
            'updated_at'     => date('Y-m-d H:i:s')
        ];

        if (array_key_exists('foto_url', $dados)) {
            $sql .= ", foto_url = :foto_url";
            $params['foto_url'] = $dados['foto_url'];
        }

        if (array_key_exists('ativo', $dados)) {
            $sql .= ", ativo = :ativo";
            $params['ativo'] = (int)(bool)$dados['ativo'];
        }

        $sql .= " WHERE id = :id";

        $sucesso = self::execute($sql, $params) >= 0;

        if (isset($dados['alergenos']) && is_array($dados['alergenos'])) {
            RestricaoAlimentar::sincronizarAluno($id, $dados['alergenos']);
        }

        return $sucesso;
    }

    /**
     * Inativação lógica do aluno (bloqueia agendamentos mas preserva histórico)
     */
    public static function inativar(int $id, ?int $usuarioId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE alunos 
                SET ativo = 0, deleted_at = :deleted_at, updated_by = :updated_by, updated_at = :updated_at 
                WHERE id = :id";

        return self::execute($sql, [
            'id'         => $id,
            'deleted_at' => $now,
            'updated_by' => $usuarioId,
            'updated_at' => $now
        ]) > 0;
    }

    /**
     * Reativa um aluno previamente inativado
     */
    public static function reativar(int $id, ?int $usuarioId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE alunos 
                SET ativo = 1, deleted_at = NULL, updated_by = :updated_by, updated_at = :updated_at 
                WHERE id = :id";

        return self::execute($sql, [
            'id'         => $id,
            'updated_by' => $usuarioId,
            'updated_at' => $now
        ]) > 0;
    }

    /**
     * Localiza um aluno pela matrícula ou cria um novo durante importação em lote
     * 
     * @return array ['id' => int, 'novo' => bool, 'registro' => array]
     */
    public static function localizarOuCriarPorMatricula(array $dados, ?int $usuarioId = null): array
    {
        $matricula = trim($dados['matricula'] ?? '');
        if (empty($matricula)) {
            throw new \InvalidArgumentException('Matrícula do aluno não informada.');
        }

        $existente = self::buscarPorMatricula($matricula, true);

        if ($existente) {
            // Atualiza turma, turno, responsável e nome caso informados
            $updateData = [
                'responsavel_id' => $dados['responsavel_id'] ?? $existente['responsavel_id'],
                'turma_id'       => $dados['turma_id'] ?? $existente['turma_id'],
                'matricula'      => $matricula,
                'nome'           => !empty($dados['nome']) ? trim($dados['nome']) : $existente['nome'],
                'turno'          => in_array($dados['turno'] ?? '', ['matutino', 'vespertino']) ? $dados['turno'] : $existente['turno'],
                'ativo'          => 1 // reativa se estava inativo
            ];

            // Reativa limpando deleted_at se estava excluído logicamente
            if (!empty($existente['deleted_at'])) {
                self::reativar((int)$existente['id'], $usuarioId);
            }

            self::atualizar((int)$existente['id'], $updateData, $usuarioId);
            $atualizado = self::buscarPorId((int)$existente['id']);

            return [
                'id'       => (int)$existente['id'],
                'novo'     => false,
                'registro' => $atualizado
            ];
        }

        // Se novo, cria
        $novoId = self::criar($dados, $usuarioId);
        $novo = self::buscarPorId($novoId);

        return [
            'id'       => $novoId,
            'novo'     => true,
            'registro' => $novo
        ];
    }

    /**
     * Retorna os IDs dos alérgenos vinculados a um aluno
     */
    public static function obterIdsAlergenos(int $alunoId): array
    {
        $sql = "SELECT restricao_id FROM aluno_restricoes WHERE aluno_id = :aluno_id";
        $rows = self::fetchAll($sql, ['aluno_id' => $alunoId]);
        return array_map(fn($r) => (int)$r['restricao_id'], $rows);
    }
}
