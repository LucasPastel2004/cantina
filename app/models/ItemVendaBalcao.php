<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo: ItemVendaBalcao (Itens Comercializados no PDV de Balcão)
 * 
 * Conforme docs/FSD.md (Área 4, Módulo 5) e Migration 021
 */

namespace App\Models;

use App\Core\Model;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class ItemVendaBalcao extends Model
{
    protected string $table = 'itens_venda_balcao';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Lista todos os itens vinculados a uma venda de balcão
     * 
     * @param int $vendaBalcaoId
     * @return array
     */
    public static function listarPorVenda(int $vendaBalcaoId): array
    {
        return self::fetchAll("
            SELECT 
                ivb.*,
                p.codigo_interno,
                p.descricao AS produto_descricao,
                p.tipo AS produto_tipo,
                p.foto_url AS produto_foto,
                c.nome AS categoria_nome
            FROM itens_venda_balcao ivb
            INNER JOIN produtos p ON p.id = ivb.produto_id
            LEFT JOIN categorias c ON c.id = p.categoria_id
            WHERE ivb.venda_balcao_id = :venda_id
            ORDER BY ivb.id ASC
        ", ['venda_id' => $vendaBalcaoId]);
    }
}
