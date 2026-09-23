<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Itens de Nota Fiscal e Fatores de Conversão
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e Migration 014
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class ItemNotaFiscal extends Model
{
    protected string $table = 'itens_nota_fiscal_xml';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Insere um item processado de nota fiscal com seu fator multiplicador de conversão
     */
    public static function criar(array $dados): int
    {
        $sql = "INSERT INTO itens_nota_fiscal_xml (
                    nota_fiscal_id, codigo_produto_fornecedor, descricao_fornecedor,
                    unidade_fornecedor, quantidade_fornecedor, valor_unitario_fornecedor,
                    produto_id, fator_conversao, quantidade_convertida, created_at
                ) VALUES (
                    :nota_fiscal_id, :codigo_produto_fornecedor, :descricao_fornecedor,
                    :unidade_fornecedor, :quantidade_fornecedor, :valor_unitario_fornecedor,
                    :produto_id, :fator_conversao, :quantidade_convertida, :created_at
                )";

        self::execute($sql, [
            'nota_fiscal_id'            => (int)$dados['nota_fiscal_id'],
            'codigo_produto_fornecedor' => trim($dados['codigo_produto_fornecedor']),
            'descricao_fornecedor'      => trim($dados['descricao_fornecedor']),
            'unidade_fornecedor'        => strtoupper(trim($dados['unidade_fornecedor'])),
            'quantidade_fornecedor'     => (float)$dados['quantidade_fornecedor'],
            'valor_unitario_fornecedor' => (float)$dados['valor_unitario_fornecedor'],
            'produto_id'                => (int)$dados['produto_id'],
            'fator_conversao'           => max(1, (int)($dados['fator_conversao'] ?? 1)),
            'quantidade_convertida'     => (int)$dados['quantidade_convertida'],
            'created_at'                => date('Y-m-d H:i:s')
        ]);

        return (int)self::lastInsertId();
    }

    /**
     * Lista todos os itens de uma nota fiscal com os dados completos do produto associado
     */
    public static function listarPorNota(int $notaFiscalId): array
    {
        $sql = "SELECT inf.*, 
                       p.codigo_interno AS produto_codigo_interno,
                       p.descricao AS produto_descricao,
                       p.preco_venda AS produto_preco_venda,
                       c.nome AS categoria_nome
                FROM itens_nota_fiscal_xml inf
                LEFT JOIN produtos p ON p.id = inf.produto_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE inf.nota_fiscal_id = :nf_id
                ORDER BY inf.id ASC";

        return self::fetchAll($sql, ['nf_id' => $notaFiscalId]);
    }

    /**
     * Busca na base de notas anteriores o último produto associado e fator de conversão utilizado
     * para o mesmo produto do mesmo fornecedor (memória de conversão)
     */
    public static function buscarUltimoFatorConversao(string $codigoFornecedor, string $cnpjEmitente): ?array
    {
        $cnpjLimpo = preg_replace('/\D/', '', $cnpjEmitente);

        $sql = "SELECT inf.produto_id, inf.fator_conversao
                FROM itens_nota_fiscal_xml inf
                INNER JOIN notas_fiscais_xml nf ON nf.id = inf.nota_fiscal_id
                WHERE inf.codigo_produto_fornecedor = :cod_forn
                  AND REPLACE(REPLACE(REPLACE(REPLACE(nf.cnpj_emitente, '.', ''), '-', ''), '/', ''), ' ', '') = :cnpj
                ORDER BY inf.created_at DESC
                LIMIT 1";

        return self::fetchOne($sql, [
            'cod_forn' => trim($codigoFornecedor),
            'cnpj'     => $cnpjLimpo
        ]);
    }
}
