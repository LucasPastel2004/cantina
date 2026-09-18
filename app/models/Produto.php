<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Produtos e Alimentos do Cardápio
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Produto extends Model
{
    protected string $table = 'produtos';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Retorna todos os produtos ativos (não excluídos), com suporte a filtros e dados da categoria
     * 
     * @param array $filtros Filtros opcionais (categoria_id, tipo, turno, status_pausa, busca)
     * @return array
     */
    public static function listarTodos(array $filtros = []): array
    {
        $sql = "SELECT p.*, 
                       c.nome AS categoria_nome,
                       GROUP_CONCAT(r.nome ORDER BY r.id SEPARATOR ', ') AS alergenos_nomes,
                       GROUP_CONCAT(r.id ORDER BY r.id SEPARATOR ',') AS alergenos_ids
                FROM produtos p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                LEFT JOIN produto_alergenos pa ON pa.produto_id = p.id
                LEFT JOIN restricoes_alimentares r ON r.id = pa.restricao_id
                WHERE p.deleted_at IS NULL";

        $params = [];

        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND p.categoria_id = :categoria_id";
            $params['categoria_id'] = (int)$filtros['categoria_id'];
        }

        if (!empty($filtros['tipo'])) {
            $sql .= " AND p.tipo = :tipo";
            $params['tipo'] = $filtros['tipo'];
        }

        if (!empty($filtros['turno']) && $filtros['turno'] !== 'todos') {
            $sql .= " AND (p.turno_disponibilidade = :turno OR p.turno_disponibilidade = 'ambos')";
            $params['turno'] = $filtros['turno'];
        }

        if (!empty($filtros['status_pausa'])) {
            $sql .= " AND p.status_pausa = :status_pausa";
            $params['status_pausa'] = $filtros['status_pausa'];
        }

        if (!empty($filtros['busca'])) {
            $termoBusca = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (p.codigo_interno LIKE :busca_cod OR p.descricao LIKE :busca_desc)";
            $params['busca_cod'] = $termoBusca;
            $params['busca_desc'] = $termoBusca;
        }

        $sql .= " GROUP BY p.id ORDER BY c.ordem_exibicao ASC, p.descricao ASC";

        return self::fetchAll($sql, $params);
    }

    /**
     * Busca um produto por ID incluindo a lista de IDs de alérgenos associados
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE p.id = :id AND p.deleted_at IS NULL
                LIMIT 1";

        $produto = self::fetchOne($sql, ['id' => $id]);

        if (!$produto) {
            return null;
        }

        $produto['alergenos_ids'] = self::getAlergenosIds($id);

        return $produto;
    }

    /**
     * Busca produto por código interno
     */
    public static function buscarPorCodigo(string $codigo, ?int $ignorarId = null): ?array
    {
        $sql = "SELECT * FROM produtos WHERE codigo_interno = :codigo AND deleted_at IS NULL";
        $params = ['codigo' => trim($codigo)];

        if ($ignorarId !== null) {
            $sql .= " AND id != :ignorar_id";
            $params['ignorar_id'] = $ignorarId;
        }

        $sql .= " LIMIT 1";

        return self::fetchOne($sql, $params);
    }

    /**
     * Retorna array com os IDs das restrições/alérgenos vinculados ao produto
     */
    public static function getAlergenosIds(int $produtoId): array
    {
        $sql = "SELECT restricao_id FROM produto_alergenos WHERE produto_id = :produto_id";
        $stmt = self::query($sql, ['produto_id' => $produtoId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Cria um novo produto e sincroniza seus alérgenos
     */
    public static function criar(array $dados, array $alergenosIds = [], ?int $usuarioId = null): int
    {
        $now = date('Y-m-d H:i:s');

        $sql = "INSERT INTO produtos (
                    categoria_id, codigo_interno, descricao, preco_venda, tipo,
                    turno_disponibilidade, foto_url, estoque_atual, estoque_reservado,
                    estoque_minimo, status_pausa, created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :categoria_id, :codigo_interno, :descricao, :preco_venda, :tipo,
                    :turno_disponibilidade, :foto_url, :estoque_atual, :estoque_reservado,
                    :estoque_minimo, :status_pausa, :created_by, :updated_by, :created_at, :updated_at
                )";

        self::execute($sql, [
            'categoria_id'          => (int)$dados['categoria_id'],
            'codigo_interno'        => strtoupper(trim($dados['codigo_interno'] ?? '')),
            'descricao'             => trim($dados['descricao'] ?? ''),
            'preco_venda'           => (float)($dados['preco_venda'] ?? 0),
            'tipo'                  => $dados['tipo'] ?? 'salgado_diario',
            'turno_disponibilidade' => $dados['turno_disponibilidade'] ?? 'ambos',
            'foto_url'              => !empty($dados['foto_url']) ? trim($dados['foto_url']) : null,
            'estoque_atual'         => (int)($dados['estoque_atual'] ?? 0),
            'estoque_reservado'     => (int)($dados['estoque_reservado'] ?? 0),
            'estoque_minimo'        => (int)($dados['estoque_minimo'] ?? 5),
            'status_pausa'          => $dados['status_pausa'] ?? 'ativo',
            'created_by'            => $usuarioId,
            'updated_by'            => $usuarioId,
            'created_at'            => $now,
            'updated_at'            => $now
        ]);

        $produtoId = (int)self::lastInsertId();

        self::syncAlergenos($produtoId, $alergenosIds);

        return $produtoId;
    }

    /**
     * Atualiza um produto existente e seus alérgenos
     */
    public static function atualizar(int $id, array $dados, array $alergenosIds = [], ?int $usuarioId = null): bool
    {
        $campos = [
            'categoria_id = :categoria_id',
            'codigo_interno = :codigo_interno',
            'descricao = :descricao',
            'preco_venda = :preco_venda',
            'tipo = :tipo',
            'turno_disponibilidade = :turno_disponibilidade',
            'estoque_atual = :estoque_atual',
            'estoque_minimo = :estoque_minimo',
            'status_pausa = :status_pausa',
            'updated_by = :updated_by',
            'updated_at = :updated_at'
        ];

        $params = [
            'id'                    => $id,
            'categoria_id'          => (int)$dados['categoria_id'],
            'codigo_interno'        => strtoupper(trim($dados['codigo_interno'] ?? '')),
            'descricao'             => trim($dados['descricao'] ?? ''),
            'preco_venda'           => (float)($dados['preco_venda'] ?? 0),
            'tipo'                  => $dados['tipo'] ?? 'salgado_diario',
            'turno_disponibilidade' => $dados['turno_disponibilidade'] ?? 'ambos',
            'estoque_atual'         => (int)($dados['estoque_atual'] ?? 0),
            'estoque_minimo'        => (int)($dados['estoque_minimo'] ?? 5),
            'status_pausa'          => $dados['status_pausa'] ?? 'ativo',
            'updated_by'            => $usuarioId,
            'updated_at'            => date('Y-m-d H:i:s')
        ];

        // Se nova foto foi enviada, atualiza a coluna foto_url
        if (array_key_exists('foto_url', $dados)) {
            $campos[] = 'foto_url = :foto_url';
            $params['foto_url'] = !empty($dados['foto_url']) ? trim($dados['foto_url']) : null;
        }

        $sql = "UPDATE produtos SET " . implode(', ', $campos) . " WHERE id = :id AND deleted_at IS NULL";

        $sucesso = self::execute($sql, $params) >= 0;

        if ($sucesso) {
            self::syncAlergenos($id, $alergenosIds);
        }

        return $sucesso;
    }

    /**
     * Sincroniza as restrições alimentares / alérgenos do produto
     */
    public static function syncAlergenos(int $produtoId, array $alergenosIds): void
    {
        // Remove associações antigas
        self::execute("DELETE FROM produto_alergenos WHERE produto_id = :produto_id", [
            'produto_id' => $produtoId
        ]);

        if (empty($alergenosIds)) {
            return;
        }

        // Insere as novas associações
        $stmt = self::getConnection()->prepare("INSERT INTO produto_alergenos (produto_id, restricao_id) VALUES (?, ?)");
        foreach ($alergenosIds as $restricaoId) {
            $restricaoId = (int)$restricaoId;
            if ($restricaoId > 0) {
                $stmt->execute([$produtoId, $restricaoId]);
            }
        }
    }

    /**
     * Alias em português para syncAlergenos
     */
    public static function sincronizarAlergenos(int $produtoId, array $alergenosIds): void
    {
        self::syncAlergenos($produtoId, $alergenosIds);
    }

    /**
     * Alterna instantaneamente o status de pausa rápida ("ativo" <-> "pausado")
     * 
     * @return string Novo status ('ativo' ou 'pausado') ou null se não encontrado
     */
    public static function togglePausa(int $id, ?int $usuarioId = null): ?string
    {
        $produto = self::buscarPorId($id);
        if (!$produto) {
            return null;
        }

        $novoStatus = ($produto['status_pausa'] === 'ativo') ? 'pausado' : 'ativo';

        $sql = "UPDATE produtos 
                SET status_pausa = :status, updated_by = :updated_by, updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        self::execute($sql, [
            'status'     => $novoStatus,
            'updated_by' => $usuarioId,
            'updated_at' => date('Y-m-d H:i:s'),
            'id'         => $id
        ]);

        return $novoStatus;
    }

    /**
     * Executa exclusão lógica (soft delete) no produto
     */
    public static function excluir(int $id, ?int $usuarioId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE produtos 
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
     * Conta quantos produtos estão no alerta de estoque mínimo ou abaixo
     */
    public static function contarAlertaEstoqueMinimo(): int
    {
        $sql = "SELECT COUNT(*) FROM produtos 
                WHERE deleted_at IS NULL 
                  AND tipo = 'industrializado_continuo' 
                  AND estoque_atual <= estoque_minimo";
        $stmt = self::query($sql);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Lista os produtos disponíveis para o cardápio de um determinado turno
     * 
     * @param string $turno 'matutino' ou 'vespertino'
     * @return array
     */
    public static function listarParaCardapio(string $turno): array
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome, c.ordem_exibicao
                FROM produtos p
                INNER JOIN categorias c ON c.id = p.categoria_id AND c.ativo = 1 AND c.deleted_at IS NULL
                WHERE p.deleted_at IS NULL
                  AND p.status_pausa = 'ativo'
                  AND (p.turno_disponibilidade = :turno OR p.turno_disponibilidade = 'ambos')
                ORDER BY c.ordem_exibicao ASC, c.nome ASC, p.descricao ASC";

        $produtos = self::fetchAll($sql, ['turno' => $turno]);

        foreach ($produtos as &$prod) {
            $prodId = (int)$prod['id'];
            // Calcula estoque disponível para industrializados
            if ($prod['tipo'] === 'industrializado_continuo') {
                $estoqueDisponivel = (int)$prod['estoque_atual'] - (int)$prod['estoque_reservado'];
                $prod['estoque_disponivel'] = max(0, $estoqueDisponivel);
                $prod['esgotado'] = ($estoqueDisponivel <= 0);
            } else {
                $prod['estoque_disponivel'] = null; // Salgados diários sob encomenda
                $prod['esgotado'] = false;
            }

            // Alérgenos vinculados
            $sqlAlerg = "SELECT ra.id, ra.nome 
                         FROM produto_alergenos pa
                         INNER JOIN restricoes_alimentares ra ON ra.id = pa.restricao_id
                         WHERE pa.produto_id = :prod_id
                         ORDER BY ra.nome ASC";
            $prod['alergenos'] = self::fetchAll($sqlAlerg, ['prod_id' => $prodId]);
            $prod['alergenos_ids'] = array_map(fn($a) => (int)$a['id'], $prod['alergenos']);
        }
        unset($prod);

        return $produtos;
    }
}
