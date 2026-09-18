<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Categorias de Cardápio
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Categoria extends Model
{
    protected string $table = 'categorias';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Retorna todas as categorias ativas (não excluídas logicamente)
     * 
     * @param bool $apenasAtivas Se true, filtra apenas as com ativo = 1
     * @return array
     */
    public static function listarTodas(bool $apenasAtivas = false): array
    {
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM produtos p WHERE p.categoria_id = c.id AND p.deleted_at IS NULL) as total_produtos
                FROM categorias c
                WHERE c.deleted_at IS NULL";

        if ($apenasAtivas) {
            $sql .= " AND c.ativo = 1";
        }

        $sql .= " ORDER BY c.ordem_exibicao ASC, c.nome ASC";

        return self::fetchAll($sql);
    }

    /**
     * Busca uma categoria pelo ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM categorias WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Busca uma categoria pelo Nome
     */
    public static function buscarPorNome(string $nome): ?array
    {
        $sql = "SELECT * FROM categorias WHERE nome = :nome AND deleted_at IS NULL LIMIT 1";
        return self::fetchOne($sql, ['nome' => trim($nome)]);
    }

    /**
     * Cria uma nova categoria
     */
    public static function criar(array $dados, ?int $usuarioId = null): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO categorias (nome, ordem_exibicao, ativo, created_by, updated_by, created_at, updated_at) 
                VALUES (:nome, :ordem_exibicao, :ativo, :created_by, :updated_by, :created_at, :updated_at)";

        self::execute($sql, [
            'nome'           => trim($dados['nome'] ?? ''),
            'ordem_exibicao' => (int)($dados['ordem_exibicao'] ?? 0),
            'ativo'          => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
            'created_by'     => $usuarioId,
            'updated_by'     => $usuarioId,
            'created_at'     => $now,
            'updated_at'     => $now
        ]);

        return (int)self::lastInsertId();
    }

    /**
     * Atualiza os dados de uma categoria existente
     */
    public static function atualizar(int $id, array $dados, ?int $usuarioId = null): bool
    {
        $sql = "UPDATE categorias 
                SET nome = :nome, 
                    ordem_exibicao = :ordem_exibicao, 
                    ativo = :ativo, 
                    updated_by = :updated_by, 
                    updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, [
            'id'             => $id,
            'nome'           => trim($dados['nome'] ?? ''),
            'ordem_exibicao' => (int)($dados['ordem_exibicao'] ?? 0),
            'ativo'          => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
            'updated_by'     => $usuarioId,
            'updated_at'     => date('Y-m-d H:i:s')
        ]) >= 0;
    }

    /**
     * Executa soft delete (exclusão lógica) na categoria
     */
    public static function excluir(int $id, ?int $usuarioId = null): bool
    {
        $sql = "UPDATE categorias 
                SET deleted_at = :deleted_at, 
                    updated_by = :updated_by, 
                    updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        $now = date('Y-m-d H:i:s');
        return self::execute($sql, [
            'id'         => $id,
            'deleted_at' => $now,
            'updated_by' => $usuarioId,
            'updated_at' => $now
        ]) > 0;
    }

    /**
     * Retorna a quantidade de produtos ativos vinculados à categoria
     */
    public static function contarProdutosVinculados(int $id): int
    {
        $sql = "SELECT COUNT(*) FROM produtos WHERE categoria_id = :id AND deleted_at IS NULL";
        $stmt = self::query($sql, ['id' => $id]);
        return (int)$stmt->fetchColumn();
    }
}
