<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Colaboradores (Professores e Funcionários para Desconto em Folha)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Colaborador extends Model
{
    protected string $table = 'colaboradores';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Lista todos os colaboradores ativos na exclusão lógica
     */
    public static function listarTodos(bool $apenasAtivos = false, string $busca = ''): array
    {
        $sql = "SELECT * FROM colaboradores WHERE deleted_at IS NULL";
        $params = [];

        if ($apenasAtivos) {
            $sql .= " AND ativo = 1";
        }

        if (!empty($busca)) {
            $sql .= " AND (nome LIKE :busca OR matricula_funcional LIKE :busca OR cargo LIKE :busca)";
            $params['busca'] = '%' . trim($busca) . '%';
        }

        $sql .= " ORDER BY nome ASC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Busca colaborador por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM colaboradores WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Busca colaborador por Matrícula Funcional
     */
    public static function buscarPorMatricula(string $matricula, ?int $ignorarId = null): ?array
    {
        $sql = "SELECT * FROM colaboradores WHERE matricula_funcional = :matricula AND deleted_at IS NULL";
        $params = ['matricula' => trim($matricula)];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params['ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        return self::fetchOne($sql, $params);
    }

    /**
     * Cadastra um novo colaborador
     */
    public static function criar(array $dados, ?int $usuarioId = null): int
    {
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO colaboradores (
                    matricula_funcional, nome, cargo, foto_url, ativo,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :matricula_funcional, :nome, :cargo, :foto_url, :ativo,
                    :created_by, :updated_by, :created_at, :updated_at
                )";

        self::execute($sql, [
            'matricula_funcional' => strtoupper(trim($dados['matricula_funcional'] ?? '')),
            'nome'                => trim($dados['nome'] ?? ''),
            'cargo'               => trim($dados['cargo'] ?? ''),
            'foto_url'            => !empty($dados['foto_url']) ? trim($dados['foto_url']) : null,
            'ativo'               => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
            'created_by'          => $usuarioId,
            'updated_by'          => $usuarioId,
            'created_at'          => $now,
            'updated_at'          => $now
        ]);

        return (int)self::lastInsertId();
    }

    /**
     * Atualiza dados de um colaborador existente
     */
    public static function atualizar(int $id, array $dados, ?int $usuarioId = null): bool
    {
        $campos = [
            'matricula_funcional = :matricula_funcional',
            'nome = :nome',
            'cargo = :cargo',
            'ativo = :ativo',
            'updated_by = :updated_by',
            'updated_at = :updated_at'
        ];

        $params = [
            'id'                  => $id,
            'matricula_funcional' => strtoupper(trim($dados['matricula_funcional'] ?? '')),
            'nome'                => trim($dados['nome'] ?? ''),
            'cargo'               => trim($dados['cargo'] ?? ''),
            'ativo'               => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
            'updated_by'          => $usuarioId,
            'updated_at'          => date('Y-m-d H:i:s')
        ];

        if (array_key_exists('foto_url', $dados)) {
            $campos[] = 'foto_url = :foto_url';
            $params['foto_url'] = !empty($dados['foto_url']) ? trim($dados['foto_url']) : null;
        }

        $sql = "UPDATE colaboradores SET " . implode(', ', $campos) . " WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, $params) >= 0;
    }

    /**
     * Alterna o status ativo do colaborador
     */
    public static function toggleAtivo(int $id, ?int $usuarioId = null): ?int
    {
        $colaborador = self::buscarPorId($id);
        if (!$colaborador) {
            return null;
        }

        $novoStatus = $colaborador['ativo'] ? 0 : 1;

        $sql = "UPDATE colaboradores 
                SET ativo = :ativo, updated_by = :updated_by, updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        self::execute($sql, [
            'ativo'      => $novoStatus,
            'updated_by' => $usuarioId,
            'updated_at' => date('Y-m-d H:i:s'),
            'id'         => $id
        ]);

        return $novoStatus;
    }

    /**
     * Exclui logicamente (soft delete) o colaborador
     */
    public static function excluir(int $id, ?int $usuarioId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE colaboradores 
                SET deleted_at = :deleted_at, updated_by = :updated_by, updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, [
            'id'         => $id,
            'deleted_at' => $now,
            'updated_by' => $usuarioId,
            'updated_at' => $now
        ]) > 0;
    }
}
