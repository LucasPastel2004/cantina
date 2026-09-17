<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Restrições Alimentares / Alérgenos Oficiais
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class RestricaoAlimentar extends Model
{
    protected string $table = 'restricoes_alimentares';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Retorna todas as restrições alimentares do catálogo oficial
     */
    public static function listarTodas(): array
    {
        $sql = "SELECT * FROM restricoes_alimentares ORDER BY id ASC";
        return self::fetchAll($sql);
    }

    /**
     * Busca uma restrição alimentar pelo ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM restricoes_alimentares WHERE id = :id LIMIT 1";
        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Retorna os alérgenos vinculados a um determinado produto
     */
    public static function listarPorProduto(int $produtoId): array
    {
        $sql = "SELECT r.* 
                FROM restricoes_alimentares r
                INNER JOIN produto_alergenos pa ON pa.restricao_id = r.id
                WHERE pa.produto_id = :produto_id
                ORDER BY r.id ASC";

        return self::fetchAll($sql, ['produto_id' => $produtoId]);
    }
}
