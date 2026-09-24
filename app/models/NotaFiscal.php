<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Notas Fiscais Eletrônicas (NF-e)
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e Migration 013
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class NotaFiscal extends Model
{
    protected string $table = 'notas_fiscais_xml';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Cria o registro de cabeçalho de uma nota fiscal importada
     * 
     * @param array $dados Dados estruturados da nota fiscal
     * @return int ID da nota fiscal inserida
     */
    public static function criar(array $dados): int
    {
        $sql = "INSERT INTO notas_fiscais_xml (
                    chave_nfe, numero_nota, cnpj_emitente, razao_social_emitente,
                    data_emissao, valor_total, arquivo_xml_path, importado_por, created_at
                ) VALUES (
                    :chave_nfe, :numero_nota, :cnpj_emitente, :razao_social_emitente,
                    :data_emissao, :valor_total, :arquivo_xml_path, :importado_por, :created_at
                )";

        self::execute($sql, [
            'chave_nfe'             => trim($dados['chave_nfe']),
            'numero_nota'           => trim($dados['numero_nota']),
            'cnpj_emitente'         => trim($dados['cnpj_emitente']),
            'razao_social_emitente' => trim($dados['razao_social_emitente']),
            'data_emissao'          => $dados['data_emissao'],
            'valor_total'           => (float)$dados['valor_total'],
            'arquivo_xml_path'      => trim($dados['arquivo_xml_path']),
            'importado_por'         => (int)$dados['importado_por'],
            'created_at'            => date('Y-m-d H:i:s')
        ]);

        return (int)self::lastInsertId();
    }

    /**
     * Busca uma nota fiscal pela chave de 44 dígitos da NF-e
     */
    public static function buscarPorChave(string $chave): ?array
    {
        $chaveLimpa = preg_replace('/\D/', '', $chave);
        $sql = "SELECT nf.*, u.nome AS operador_nome
                FROM notas_fiscais_xml nf
                LEFT JOIN usuarios u ON u.id = nf.importado_por
                WHERE nf.chave_nfe = :chave
                LIMIT 1";

        return self::fetchOne($sql, ['chave' => $chaveLimpa]);
    }

    /**
     * Busca uma nota fiscal pelo ID interno
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT nf.*, u.nome AS operador_nome
                FROM notas_fiscais_xml nf
                LEFT JOIN usuarios u ON u.id = nf.importado_por
                WHERE nf.id = :id
                LIMIT 1";

        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Retorna a nota fiscal completa com todos os itens mapeados e detalhes dos produtos
     */
    public static function obterComItens(int $id): ?array
    {
        $nota = self::buscarPorId($id);
        if (!$nota) {
            return null;
        }

        $nota['itens'] = ItemNotaFiscal::listarPorNota($id);

        return $nota;
    }

    /**
     * Lista notas fiscais importadas com filtros opcionais por fornecedor, período ou busca
     */
    public static function listarTodas(array $filtros = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT nf.*, 
                       u.nome AS operador_nome,
                       COUNT(inf.id) AS total_itens_convertidos,
                       COALESCE(SUM(inf.quantidade_convertida), 0) AS total_unidades_adicionadas
                FROM notas_fiscais_xml nf
                LEFT JOIN usuarios u ON u.id = nf.importado_por
                LEFT JOIN itens_nota_fiscal_xml inf ON inf.nota_fiscal_id = nf.id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (nf.numero_nota LIKE :b1 OR nf.razao_social_emitente LIKE :b2 OR nf.cnpj_emitente LIKE :b3 OR nf.chave_nfe LIKE :b4)";
            $params['b1'] = $termo;
            $params['b2'] = $termo;
            $params['b3'] = $termo;
            $params['b4'] = $termo;
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND DATE(nf.data_emissao) >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND DATE(nf.data_emissao) <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        $sql .= " GROUP BY nf.id ORDER BY nf.created_at DESC";

        $limiteEfetivo = $limit ?? (!empty($filtros['limite']) ? (int)$filtros['limite'] : null);
        if ($limiteEfetivo !== null && $limiteEfetivo > 0) {
            $sql .= " LIMIT " . (int)$limiteEfetivo;
            if ($offset !== null && $offset >= 0) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Retorna o total de notas fiscais importadas para os filtros
     */
    public static function contarTodas(array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM notas_fiscais_xml nf
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (nf.numero_nota LIKE :b1 OR nf.razao_social_emitente LIKE :b2 OR nf.cnpj_emitente LIKE :b3 OR nf.chave_nfe LIKE :b4)";
            $params['b1'] = $termo;
            $params['b2'] = $termo;
            $params['b3'] = $termo;
            $params['b4'] = $termo;
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND DATE(nf.data_emissao) >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND DATE(nf.data_emissao) <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        $res = self::fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Retorna métricas globais de notas fiscais importadas
     */
    public static function obterMetricas(): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total_notas,
                    COALESCE(SUM(valor_total), 0) AS valor_acumulado,
                    COUNT(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN 1 END) AS notas_mes_atual,
                    COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN valor_total ELSE 0 END), 0) AS valor_mes_atual
                FROM notas_fiscais_xml";

        $dados = self::fetchOne($sql);
        return [
            'total_notas'      => (int)($dados['total_notas'] ?? 0),
            'valor_acumulado'  => (float)($dados['valor_acumulado'] ?? 0.0),
            'notas_mes_atual'  => (int)($dados['notas_mes_atual'] ?? 0),
            'valor_mes_atual'  => (float)($dados['valor_mes_atual'] ?? 0.0)
        ];
    }
}
