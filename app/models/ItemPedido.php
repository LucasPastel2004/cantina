<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Domínio: Itens do Pedido de Lanche
 * 
 * Responsável pelos itens solicitados em cada agendamento semanal.
 * 
 * Conforme docs/FSD.md (Área 1, Entidade 15)
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class ItemPedido extends Model
{
    protected string $table = 'itens_pedido';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Lista todos os itens de um pedido com dados do produto associado
     * 
     * @param int $pedidoId
     * @return array
     */
    public static function listarPorPedido(int $pedidoId): array
    {
        $sql = "SELECT ip.*, 
                       p.descricao AS produto_descricao, 
                       p.codigo_interno AS produto_codigo,
                       p.tipo AS produto_tipo,
                       p.foto_url AS produto_foto,
                       c.nome AS categoria_nome
                FROM itens_pedido ip
                INNER JOIN produtos p ON p.id = ip.produto_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE ip.pedido_id = :pedido_id
                ORDER BY ip.id ASC";

        return self::fetchAll($sql, ['pedido_id' => $pedidoId]);
    }

    /**
     * Insere um item de pedido
     * 
     * @param int $pedidoId
     * @param int $produtoId
     * @param int $quantidade
     * @param float $precoUnitario
     * @return int ID inserido
     */
    public static function criarItem(int $pedidoId, int $produtoId, int $quantidade, float $precoUnitario): int
    {
        $quantidade = max(1, $quantidade);
        $subtotal = round($quantidade * $precoUnitario, 2);

        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal)
            VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal)
        ");

        $stmt->execute([
            'pedido_id'      => $pedidoId,
            'produto_id'     => $produtoId,
            'quantidade'     => $quantidade,
            'preco_unitario' => $precoUnitario,
            'subtotal'       => $subtotal,
        ]);

        return (int)$pdo->lastInsertId();
    }
}
