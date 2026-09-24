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
     * Lista todos os colaboradores com suporte a filtros e paginação opcional
     */
    public static function listarTodos(bool $apenasAtivos = false, string $busca = '', ?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT * FROM colaboradores WHERE deleted_at IS NULL";
        $params = [];

        if ($apenasAtivos) {
            $sql .= " AND ativo = 1";
        }

        if (!empty($busca)) {
            $termoBusca = '%' . trim($busca) . '%';
            $sql .= " AND (nome LIKE :busca_nome OR matricula_funcional LIKE :busca_mat OR cargo LIKE :busca_cargo)";
            $params['busca_nome'] = $termoBusca;
            $params['busca_mat'] = $termoBusca;
            $params['busca_cargo'] = $termoBusca;
        }

        $sql .= " ORDER BY nome ASC";

        if ($limit !== null && $limit > 0) {
            $offset = max(0, $offset);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Conta o total de colaboradores com suporte aos mesmos filtros
     */
    public static function contarTodos(bool $apenasAtivos = false, string $busca = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM colaboradores WHERE deleted_at IS NULL";
        $params = [];

        if ($apenasAtivos) {
            $sql .= " AND ativo = 1";
        }

        if (!empty($busca)) {
            $termoBusca = '%' . trim($busca) . '%';
            $sql .= " AND (nome LIKE :busca_nome OR matricula_funcional LIKE :busca_mat OR cargo LIKE :busca_cargo)";
            $params['busca_nome'] = $termoBusca;
            $params['busca_mat'] = $termoBusca;
            $params['busca_cargo'] = $termoBusca;
        }

        $row = self::fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
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

    /**
     * Busca colaboradores ativos para o PDV por matrícula funcional ou nome
     * 
     * @param string $termo
     * @param int $limite
     * @return array
     */
    public static function buscarParaPdv(string $termo, int $limite = 10): array
    {
        $termoLimpo = trim($termo);
        if (empty($termoLimpo)) {
            return [];
        }

        $param = '%' . $termoLimpo . '%';
        $sql = "SELECT id, matricula_funcional, nome, cargo, foto_url, ativo
                FROM colaboradores
                WHERE deleted_at IS NULL
                  AND ativo = 1
                  AND (matricula_funcional LIKE :busca_mat OR nome LIKE :busca_nome OR cargo LIKE :busca_cargo)
                ORDER BY nome ASC
                LIMIT {$limite}";

        return self::fetchAll($sql, [
            'busca_mat'   => $param,
            'busca_nome'  => $param,
            'busca_cargo' => $param
        ]);
    }
}

