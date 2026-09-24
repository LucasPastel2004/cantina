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
     * Lista turmas ativas com suporte a filtros, contagem de alunos e paginação opcional
     */
    public static function listarTodas(string $turno = '', string $busca = '', ?int $limit = null, int $offset = 0): array
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
            $termoBusca = '%' . trim($busca) . '%';
            $sql .= " AND (t.nome_turma LIKE :busca_nome OR t.serie LIKE :busca_serie)";
            $params['busca_nome'] = $termoBusca;
            $params['busca_serie'] = $termoBusca;
        }

        $sql .= " ORDER BY t.turno ASC, t.ordem_impressao ASC, t.nome_turma ASC";

        if ($limit !== null && $limit > 0) {
            $offset = max(0, $offset);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Conta o total de turmas com suporte aos mesmos filtros
     */
    public static function contarTodas(string $turno = '', string $busca = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM turmas t WHERE t.deleted_at IS NULL";
        $params = [];

        if (!empty($turno) && $turno !== 'todos') {
            $sql .= " AND t.turno = :turno";
            $params['turno'] = $turno;
        }

        if (!empty($busca)) {
            $termoBusca = '%' . trim($busca) . '%';
            $sql .= " AND (t.nome_turma LIKE :busca_nome OR t.serie LIKE :busca_serie)";
            $params['busca_nome'] = $termoBusca;
            $params['busca_serie'] = $termoBusca;
        }

        $row = self::fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
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

    /**
     * Busca uma turma ativa pelo nome da turma e turno
     */
    public static function buscarPorNomeETurno(string $nomeTurma, string $turno): ?array
    {
        $sql = "SELECT * FROM turmas 
                WHERE LOWER(TRIM(nome_turma)) = LOWER(TRIM(:nome_turma))
                AND turno = :turno
                AND deleted_at IS NULL
                LIMIT 1";

        return self::fetchOne($sql, [
            'nome_turma' => $nomeTurma,
            'turno'      => $turno
        ]);
    }

    /**
     * Localiza uma turma existente por nome e turno, ou cria automaticamente durante importação
     * 
     * @return array ['id' => int, 'novo' => bool, 'registro' => array]
     */
    public static function localizarOuCriar(string $serie, string $nomeTurma, string $turno, ?int $usuarioId = null): array
    {
        $nomeTurma = trim($nomeTurma);
        $serie = trim($serie);
        $turno = in_array(strtolower(trim($turno)), ['vespertino', 'tarde']) ? 'vespertino' : 'matutino';

        $existente = self::buscarPorNomeETurno($nomeTurma, $turno);
        if ($existente) {
            // Se série mudou, atualiza
            if (!empty($serie) && $serie !== $existente['serie']) {
                self::atualizar((int)$existente['id'], [
                    'serie'           => $serie,
                    'nome_turma'      => $nomeTurma,
                    'turno'           => $turno,
                    'ordem_impressao' => (int)$existente['ordem_impressao']
                ], $usuarioId);
                $existente['serie'] = $serie;
            }

            return [
                'id'       => (int)$existente['id'],
                'novo'     => false,
                'registro' => $existente
            ];
        }

        // Se não existe, cria com ordem de impressão sequencial
        $maxOrdem = (int)self::fetchOne("SELECT MAX(ordem_impressao) as max_ordem FROM turmas WHERE turno = :turno AND deleted_at IS NULL", ['turno' => $turno])['max_ordem'] ?? 0;
        $novoId = self::criar([
            'serie'           => !empty($serie) ? $serie : $nomeTurma,
            'nome_turma'      => $nomeTurma,
            'turno'           => $turno,
            'ordem_impressao' => $maxOrdem + 1
        ], $usuarioId);

        $novo = self::buscarPorId($novoId);

        return [
            'id'       => $novoId,
            'novo'     => true,
            'registro' => $novo
        ];
    }
}
