<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Turmas Escolares
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Turma extends Model
{
    protected string $table = 'turmas';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Lista todas as turmas ativas com suporte a filtros e contagem de alunos
     */
    public static function listarTodas(string $turno = '', string $busca = ''): array
    {
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM alunos a WHERE a.turma_id = t.id AND a.deleted_at IS NULL) as total_alunos
                FROM turmas t
                WHERE t.deleted_at IS NULL";
        $params = [];

        if (!empty($turno) && $turno !== 'todos') {
            $sql .= " AND t.turno = :turno";
            $params['turno'] = $turno;
        }

        if (!empty($busca)) {
            $sql .= " AND (t.nome_turma LIKE :busca OR t.serie LIKE :busca)";
            $params['busca'] = '%' . trim($busca) . '%';
        }

        $sql .= " ORDER BY t.turno ASC, t.ordem_impressao ASC, t.nome_turma ASC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Busca turma por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM turmas WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Cadastra uma nova turma escolar
     */
    public static function criar(array $dados, ?int $usuarioId = null): int
    {
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO turmas (
                    serie, nome_turma, turno, ordem_impressao,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :serie, :nome_turma, :turno, :ordem_impressao,
                    :created_by, :updated_by, :created_at, :updated_at
                )";

        self::execute($sql, [
            'serie'           => trim($dados['serie'] ?? ''),
            'nome_turma'      => trim($dados['nome_turma'] ?? ''),
            'turno'           => $dados['turno'] ?? 'matutino',
            'ordem_impressao' => (int)($dados['ordem_impressao'] ?? 0),
            'created_by'      => $usuarioId,
            'updated_by'      => $usuarioId,
            'created_at'      => $now,
            'updated_at'      => $now
        ]);

        return (int)self::lastInsertId();
    }

    /**
     * Atualiza uma turma escolar existente
     */
    public static function atualizar(int $id, array $dados, ?int $usuarioId = null): bool
    {
        $sql = "UPDATE turmas 
                SET serie = :serie,
                    nome_turma = :nome_turma,
                    turno = :turno,
                    ordem_impressao = :ordem_impressao,
                    updated_by = :updated_by,
                    updated_at = :updated_at
                WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, [
            'id'              => $id,
            'serie'           => trim($dados['serie'] ?? ''),
            'nome_turma'      => trim($dados['nome_turma'] ?? ''),
            'turno'           => $dados['turno'] ?? 'matutino',
            'ordem_impressao' => (int)($dados['ordem_impressao'] ?? 0),
            'updated_by'      => $usuarioId,
            'updated_at'      => date('Y-m-d H:i:s')
        ]) >= 0;
    }

    /**
     * Exclui logicamente (soft delete) a turma
     */
    public static function excluir(int $id, ?int $usuarioId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE turmas 
                SET deleted_at = :deleted_at, updated_by = :updated_by, updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, [
            'id'         => $id,
            'deleted_at' => $now,
            'updated_by' => $usuarioId,
            'updated_at' => $now
        ]) > 0;
    }

    /**
     * Conta quantos alunos ativos estão matriculados nesta turma
     */
    public static function contarAlunosVinculados(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM alunos WHERE turma_id = :id AND deleted_at IS NULL";
        $stmt = self::query($sql, ['id' => $id]);
        return (int)$stmt->fetchColumn();
    }
}
