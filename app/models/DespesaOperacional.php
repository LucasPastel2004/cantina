<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Model: DespesaOperacional
 * 
 * Gestão de despesas operacionais da cantina escolar (administrativas, pessoal, vendas, financeiras e manutenção)
 * com rastreabilidade, competência contábil mensal, soft delete e auditoria.
 * 
 * Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
 * Conforme docs/FSD.md, docs/DESIGN.md e Planilha_DRE_Modelo_Aethos_Sistemas.pdf
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class DespesaOperacional extends Model
{
    protected string $table = 'despesas_operacionais';

    public const CATEGORIA_ADMINISTRATIVA = 'administrativa';
    public const CATEGORIA_PESSOAL        = 'pessoal';
    public const CATEGORIA_VENDAS         = 'vendas';
    public const CATEGORIA_FINANCEIRA     = 'financeira';
    public const CATEGORIA_MANUTENCAO     = 'manutencao';
    public const CATEGORIA_OUTRAS         = 'outras';

    public const STATUS_PENDENTE  = 'pendente';
    public const STATUS_PAGO      = 'pago';
    public const STATUS_CANCELADO = 'cancelado';

    /**
     * Retorna o mapa de categorias operacionais oficiais com rótulos amigáveis.
     */
    public static function getCategorias(): array
    {
        return [
            self::CATEGORIA_ADMINISTRATIVA => 'Despesas Administrativas (Energia, Água, Aluguel, Limpeza, Contabilidade)',
            self::CATEGORIA_PESSOAL        => 'Despesas com Pessoal (Salários, Encargos, Pró-Labore, Benefícios)',
            self::CATEGORIA_VENDAS         => 'Despesas com Vendas (Marketing, Embalagens, Descartáveis)',
            self::CATEGORIA_FINANCEIRA     => 'Despesas Financeiras (Tarifas Bancárias, Taxas de Gateway, Juros)',
            self::CATEGORIA_MANUTENCAO     => 'Manutenção & Equipamentos (Consertos de Frezer/Fornos, Reformas)',
            self::CATEGORIA_OUTRAS         => 'Outras Despesas Operacionais',
        ];
    }

    /**
     * Retorna o nome amigável curto de uma categoria.
     */
    public static function getNomeCategoria(string $categoria): string
    {
        $map = [
            self::CATEGORIA_ADMINISTRATIVA => 'Administrativa',
            self::CATEGORIA_PESSOAL        => 'Pessoal & Salários',
            self::CATEGORIA_VENDAS         => 'Vendas & Embalagens',
            self::CATEGORIA_FINANCEIRA     => 'Financeira & Tarifas',
            self::CATEGORIA_MANUTENCAO     => 'Manutenção',
            self::CATEGORIA_OUTRAS         => 'Outras Despesas',
        ];

        return $map[$categoria] ?? ucfirst($categoria);
    }

    /**
     * Retorna o mapa de status de pagamento.
     */
    public static function getStatusList(): array
    {
        return [
            self::STATUS_PENDENTE  => 'Pendente',
            self::STATUS_PAGO      => 'Pago',
            self::STATUS_CANCELADO => 'Cancelado',
        ];
    }

    /**
     * Cria um novo lançamento de despesa operacional com validações.
     */
    public function criar(array $dados, ?int $usuarioId = null): int
    {
        $this->validarDados($dados);

        $pdo = self::getConnection();
        $sql = "INSERT INTO `despesas_operacionais` (
            `categoria`, `subcategoria`, `descricao`, `valor`, `competencia_mes_ano`,
            `data_vencimento`, `data_pagamento`, `status`, `forma_pagamento`,
            `documento_referencia`, `observacoes`, `created_by`
        ) VALUES (
            :categoria, :subcategoria, :descricao, :valor, :competencia,
            :data_vencimento, :data_pagamento, :status, :forma_pagamento,
            :documento_referencia, :observacoes, :created_by
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':categoria'            => $dados['categoria'],
            ':subcategoria'         => !empty($dados['subcategoria']) ? trim($dados['subcategoria']) : null,
            ':descricao'            => trim($dados['descricao']),
            ':valor'                => (float)$dados['valor'],
            ':competencia'          => $dados['competencia_mes_ano'],
            ':data_vencimento'      => $dados['data_vencimento'],
            ':data_pagamento'       => !empty($dados['data_pagamento']) ? $dados['data_pagamento'] : null,
            ':status'               => $dados['status'] ?? self::STATUS_PENDENTE,
            ':forma_pagamento'      => !empty($dados['forma_pagamento']) ? trim($dados['forma_pagamento']) : null,
            ':documento_referencia' => !empty($dados['documento_referencia']) ? trim($dados['documento_referencia']) : null,
            ':observacoes'          => !empty($dados['observacoes']) ? trim($dados['observacoes']) : null,
            ':created_by'           => $usuarioId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Atualiza uma despesa operacional existente.
     */
    public function atualizar(int $id, array $dados, ?int $usuarioId = null): bool
    {
        $this->validarDados($dados, true);

        $pdo = self::getConnection();
        $sql = "UPDATE `despesas_operacionais` SET
            `categoria`            = :categoria,
            `subcategoria`         = :subcategoria,
            `descricao`            = :descricao,
            `valor`                = :valor,
            `competencia_mes_ano`  = :competencia,
            `data_vencimento`      = :data_vencimento,
            `data_pagamento`       = :data_pagamento,
            `status`               = :status,
            `forma_pagamento`      = :forma_pagamento,
            `documento_referencia` = :documento_referencia,
            `observacoes`          = :observacoes,
            `updated_by`           = :updated_by
        WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id'                   => $id,
            ':categoria'            => $dados['categoria'],
            ':subcategoria'         => !empty($dados['subcategoria']) ? trim($dados['subcategoria']) : null,
            ':descricao'            => trim($dados['descricao']),
            ':valor'                => (float)$dados['valor'],
            ':competencia'          => $dados['competencia_mes_ano'],
            ':data_vencimento'      => $dados['data_vencimento'],
            ':data_pagamento'       => !empty($dados['data_pagamento']) ? $dados['data_pagamento'] : null,
            ':status'               => $dados['status'] ?? self::STATUS_PENDENTE,
            ':forma_pagamento'      => !empty($dados['forma_pagamento']) ? trim($dados['forma_pagamento']) : null,
            ':documento_referencia' => !empty($dados['documento_referencia']) ? trim($dados['documento_referencia']) : null,
            ':observacoes'          => !empty($dados['observacoes']) ? trim($dados['observacoes']) : null,
            ':updated_by'           => $usuarioId,
        ]);
    }

    /**
     * Realiza soft delete de uma despesa.
     */
    public function excluir(int $id, ?int $usuarioId = null): bool
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("UPDATE `despesas_operacionais` SET `deleted_at` = NOW(), `updated_by` = :uid WHERE `id` = :id AND `deleted_at` IS NULL");
        return $stmt->execute([
            ':id'  => $id,
            ':uid' => $usuarioId,
        ]);
    }

    /**
     * Busca uma despesa específica por ID.
     */
    public function buscarPorId(int $id): ?array
    {
        $pdo = self::getConnection();
        $sql = "SELECT d.*, u.nome AS criado_por_nome
                FROM `despesas_operacionais` d
                LEFT JOIN `usuarios` u ON d.created_by = u.id
                WHERE d.id = :id AND d.deleted_at IS NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Marca uma despesa como paga diretamente.
     */
    public function marcarComoPaga(int $id, ?string $dataPagamento = null, ?string $formaPagamento = null, ?int $usuarioId = null): bool
    {
        $pdo = self::getConnection();
        $dataPagamento = $dataPagamento ?: date('Y-m-d');
        
        $sql = "UPDATE `despesas_operacionais` SET
            `status` = :status,
            `data_pagamento` = :data_pagamento,
            `forma_pagamento` = COALESCE(:forma_pagamento, `forma_pagamento`),
            `updated_by` = :updated_by
        WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':id'              => $id,
            ':status'          => self::STATUS_PAGO,
            ':data_pagamento'  => $dataPagamento,
            ':forma_pagamento' => $formaPagamento,
            ':updated_by'      => $usuarioId,
        ]);
    }

    /**
     * Lista despesas operacionais com paginação e filtros.
     */
    public function listar(array $filtros = [], int $limite = 50, int $offset = 0): array
    {
        $pdo = self::getConnection();
        $where = ["d.deleted_at IS NULL"];
        $params = [];

        if (!empty($filtros['competencia'])) {
            $where[] = "d.competencia_mes_ano = :competencia";
            $params[':competencia'] = $filtros['competencia'];
        }

        if (!empty($filtros['categoria'])) {
            $where[] = "d.categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['status'])) {
            $where[] = "d.status = :status";
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['ano'])) {
            $where[] = "d.competencia_mes_ano LIKE :ano";
            $params[':ano'] = $filtros['ano'] . '-%';
        }

        if (!empty($filtros['busca'])) {
            $where[] = "(d.descricao LIKE :busca_desc OR d.documento_referencia LIKE :busca_doc OR d.subcategoria LIKE :busca_sub)";
            $buscaTerm = '%' . trim($filtros['busca']) . '%';
            $params[':busca_desc'] = $buscaTerm;
            $params[':busca_doc']  = $buscaTerm;
            $params[':busca_sub']  = $buscaTerm;
        }

        $sql = "SELECT d.*, u.nome AS criado_por_nome
                FROM `despesas_operacionais` d
                LEFT JOIN `usuarios` u ON d.created_by = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY d.competencia_mes_ano DESC, d.data_vencimento ASC, d.id DESC
                LIMIT {$limite} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Conta o total de registros para paginação.
     */
    public function contar(array $filtros = []): int
    {
        $pdo = self::getConnection();
        $where = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($filtros['competencia'])) {
            $where[] = "competencia_mes_ano = :competencia";
            $params[':competencia'] = $filtros['competencia'];
        }

        if (!empty($filtros['categoria'])) {
            $where[] = "categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }

        if (!empty($filtros['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filtros['status'];
        }

        if (!empty($filtros['ano'])) {
            $where[] = "competencia_mes_ano LIKE :ano";
            $params[':ano'] = $filtros['ano'] . '-%';
        }

        if (!empty($filtros['busca'])) {
            $where[] = "(descricao LIKE :busca_desc OR documento_referencia LIKE :busca_doc OR subcategoria LIKE :busca_sub)";
            $buscaTerm = '%' . trim($filtros['busca']) . '%';
            $params[':busca_desc'] = $buscaTerm;
            $params[':busca_doc']  = $buscaTerm;
            $params[':busca_sub']  = $buscaTerm;
        }

        $sql = "SELECT COUNT(*) FROM `despesas_operacionais` WHERE " . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtém resumo financeiro consolidado de uma competência (ou geral se 'todas').
     */
    public function obterResumoCompetencia(string $competencia = 'todas'): array
    {
        $pdo = self::getConnection();
        $where = ["`deleted_at` IS NULL"];
        $params = [];

        if (!empty($competencia) && $competencia !== 'todas') {
            $where[] = "`competencia_mes_ano` = :comp";
            $params[':comp'] = $competencia;
        }

        $sql = "SELECT 
                    COUNT(*) as total_lancamentos,
                    COALESCE(SUM(valor), 0.00) as total_geral,
                    COALESCE(SUM(CASE WHEN status = 'pago' THEN valor ELSE 0 END), 0.00) as total_pago,
                    COALESCE(SUM(CASE WHEN status = 'pendente' THEN valor ELSE 0 END), 0.00) as total_pendente,
                    COALESCE(SUM(CASE WHEN status = 'cancelado' THEN valor ELSE 0 END), 0.00) as total_cancelado
                FROM `despesas_operacionais`
                WHERE " . implode(' AND ', $where);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $resumo = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

        // Totais por categoria na competência
        $whereCat = ["`status` != 'cancelado'", "`deleted_at` IS NULL"];
        $paramsCat = [];

        if (!empty($competencia) && $competencia !== 'todas') {
            $whereCat[] = "`competencia_mes_ano` = :comp";
            $paramsCat[':comp'] = $competencia;
        }

        $sqlCat = "SELECT `categoria`, COALESCE(SUM(valor), 0.00) as total
                   FROM `despesas_operacionais`
                   WHERE " . implode(' AND ', $whereCat) . "
                   GROUP BY `categoria`";
        $stmtCat = $pdo->prepare($sqlCat);
        $stmtCat->execute($paramsCat);
        $categorias = $stmtCat->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];

        $resumo['categorias'] = $categorias;
        return $resumo;
    }

    /**
     * Obtém as competências existentes no banco ordenadas decrescente.
     */
    public function obterCompetenciasDisponiveis(): array
    {
        $pdo = self::getConnection();
        $sql = "SELECT DISTINCT `competencia_mes_ano` 
                FROM `despesas_operacionais` 
                WHERE `deleted_at` IS NULL 
                ORDER BY `competencia_mes_ano` DESC";
        $stmt = $pdo->query($sql);
        $lista = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $mesAtual = date('Y-m');
        if (!in_array($mesAtual, $lista, true)) {
            array_unshift($lista, $mesAtual);
        }

        return $lista;
    }

    /**
     * Valida os campos obrigatórios e formato da despesa.
     */
    private function validarDados(array $dados, bool $isEdicao = false): void
    {
        if (empty($dados['descricao']) || mb_strlen(trim($dados['descricao'])) < 3) {
            throw new \InvalidArgumentException("A descrição da despesa deve conter ao menos 3 caracteres.");
        }

        if (!isset($dados['valor']) || !is_numeric($dados['valor']) || (float)$dados['valor'] <= 0) {
            throw new \InvalidArgumentException("O valor da despesa deve ser um número positivo maior que zero.");
        }

        $categoriasValidas = array_keys(self::getCategorias());
        if (empty($dados['categoria']) || !in_array($dados['categoria'], $categoriasValidas, true)) {
            throw new \InvalidArgumentException("A categoria informada é inválida.");
        }

        if (empty($dados['competencia_mes_ano']) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $dados['competencia_mes_ano'])) {
            throw new \InvalidArgumentException("A competência deve estar no formato AAAA-MM (ex: " . date('Y-m') . ").");
        }

        if (empty($dados['data_vencimento']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_vencimento'])) {
            throw new \InvalidArgumentException("A data de vencimento informada é inválida.");
        }

        if (!empty($dados['data_pagamento']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_pagamento'])) {
            throw new \InvalidArgumentException("A data de pagamento informada é inválida.");
        }

        if (!empty($dados['status'])) {
            $statusValidos = array_keys(self::getStatusList());
            if (!in_array($dados['status'], $statusValidos, true)) {
                throw new \InvalidArgumentException("O status informado é inválido.");
            }
        }
    }
}
