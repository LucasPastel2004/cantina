<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Controle de Estoque, Movimentações e Registro de Perdas
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e Migrations 011, 014 e 015
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Estoque extends Model
{
    protected string $table = 'estoque_movimentacoes';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Motivos padronizados oficiais de perda conforme RN-15 do FSD
     */
    public const MOTIVO_AVARIA          = 'avaria';
    public const MOTIVO_VENCIMENTO      = 'vencimento';
    public const MOTIVO_SOBRA_PRODUCAO  = 'sobra_producao';
    public const MOTIVO_AJUSTE_CONTAGEM = 'ajuste_contagem';

    public const MOTIVOS_PERDA = [
        self::MOTIVO_AVARIA          => 'Avaria / Descarte',
        self::MOTIVO_VENCIMENTO      => 'Vencimento',
        self::MOTIVO_SOBRA_PRODUCAO  => 'Produto Não Consumido',
        self::MOTIVO_AJUSTE_CONTAGEM => 'Ajuste de Contagem'
    ];

    /**
     * Tipos de movimentação de estoque da tabela estoque_movimentacoes
     */
    public const TIPOS_MOVIMENTACAO = [
        'entrada_fornecedor'  => 'Entrada por Fornecedor',
        'entrada_xml'         => 'Entrada por XML de NF-e',
        'aproveitamento_sobra'=> 'Aproveitamento de Sobra de Encomenda',
        'saida_venda_avulsa'  => 'Saída por Venda Avulsa (PDV)',
        'saida_encomenda'     => 'Saída por Liquidação de Encomenda',
        'saida_perda'         => 'Baixa por Perda / Descarte'
    ];

    /**
     * Retorna a listagem analítica dos saldos físicos e disponibilidade de cada produto
     */
    public static function listarSaldos(array $filtros = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT p.id,
                       p.codigo_interno,
                       p.descricao,
                       p.preco_venda,
                       p.tipo,
                       p.turno_disponibilidade,
                       p.foto_url,
                       p.estoque_atual,
                       p.estoque_reservado,
                       p.estoque_minimo,
                       p.status_pausa,
                       c.id AS categoria_id,
                       c.nome AS categoria_nome,
                       (p.estoque_atual - p.estoque_reservado) AS estoque_disponivel,
                       CASE 
                           WHEN p.tipo = 'industrializado_continuo' AND (p.estoque_atual - p.estoque_reservado) <= p.estoque_minimo THEN 1
                           ELSE 0
                       END AS em_alerta_minimo
                FROM produtos p
                INNER JOIN categorias c ON c.id = p.categoria_id AND c.deleted_at IS NULL
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

        if (!empty($filtros['status_pausa'])) {
            $sql .= " AND p.status_pausa = :status_pausa";
            $params['status_pausa'] = $filtros['status_pausa'];
        }

        if (isset($filtros['apenas_alerta']) && $filtros['apenas_alerta'] === true) {
            $sql .= " AND p.tipo = 'industrializado_continuo' AND (p.estoque_atual - p.estoque_reservado) <= p.estoque_minimo";
        }

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (p.codigo_interno LIKE :b1 OR p.descricao LIKE :b2 OR c.nome LIKE :b3)";
            $params['b1'] = $termo;
            $params['b2'] = $termo;
            $params['b3'] = $termo;
        }

        $sql .= " ORDER BY em_alerta_minimo DESC, c.ordem_exibicao ASC, p.descricao ASC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null && $offset >= 0) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Retorna o total de produtos cadastrados considerando os filtros de saldo
     */
    public static function contarSaldos(array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM produtos p
                INNER JOIN categorias c ON c.id = p.categoria_id AND c.deleted_at IS NULL
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

        if (!empty($filtros['status_pausa'])) {
            $sql .= " AND p.status_pausa = :status_pausa";
            $params['status_pausa'] = $filtros['status_pausa'];
        }

        if (isset($filtros['apenas_alerta']) && $filtros['apenas_alerta'] === true) {
            $sql .= " AND p.tipo = 'industrializado_continuo' AND (p.estoque_atual - p.estoque_reservado) <= p.estoque_minimo";
        }

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (p.codigo_interno LIKE :b1 OR p.descricao LIKE :b2 OR c.nome LIKE :b3)";
            $params['b1'] = $termo;
            $params['b2'] = $termo;
            $params['b3'] = $termo;
        }

        $res = self::fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Retorna métricas executivas consolidadas do estoque
     */
    public static function obterMetricasGerais(): array
    {
        // 1. Totais de itens e alertas em produtos
        $sqlProd = "SELECT 
                        COUNT(*) AS total_produtos,
                        COALESCE(SUM(estoque_atual), 0) AS total_unidades_fisicas,
                        COALESCE(SUM(estoque_reservado), 0) AS total_unidades_reservadas,
                        COALESCE(SUM(estoque_atual * preco_venda), 0) AS valor_estoque_venda,
                        COUNT(CASE WHEN tipo = 'industrializado_continuo' AND (estoque_atual - estoque_reservado) <= estoque_minimo THEN 1 END) AS total_alertas_minimo
                    FROM produtos 
                    WHERE deleted_at IS NULL";
        $dadosProd = self::fetchOne($sqlProd);

        // 2. Entradas e perdas no mês corrente
        $sqlMov = "SELECT 
                       COALESCE(SUM(CASE WHEN tipo_movimentacao IN ('entrada_fornecedor', 'entrada_xml', 'aproveitamento_sobra') AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN quantidade ELSE 0 END), 0) AS entradas_mes,
                       COALESCE(SUM(CASE WHEN tipo_movimentacao = 'saida_perda' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE()) THEN quantidade ELSE 0 END), 0) AS perdas_mes
                   FROM estoque_movimentacoes";
        $dadosMov = self::fetchOne($sqlMov);

        return [
            'total_produtos'            => (int)($dadosProd['total_produtos'] ?? 0),
            'total_unidades_fisicas'    => (int)($dadosProd['total_unidades_fisicas'] ?? 0),
            'total_unidades_reservadas' => (int)($dadosProd['total_unidades_reservadas'] ?? 0),
            'valor_estoque_venda'       => (float)($dadosProd['valor_estoque_venda'] ?? 0.0),
            'total_alertas_minimo'      => (int)($dadosProd['total_alertas_minimo'] ?? 0),
            'entradas_mes'              => (int)($dadosMov['entradas_mes'] ?? 0),
            'perdas_mes'                => (int)($dadosMov['perdas_mes'] ?? 0)
        ];
    }

    /**
     * Processa atomicamente a entrada física de mercadorias a partir de uma NF-e importada
     * 
     * @param int $notaFiscalId ID do cabeçalho da NF-e
     * @param array $itensMapeados Array com [codigo_fornecedor, descricao_fornecedor, unidade_fornecedor, quantidade_fornecedor, valor_unitario_fornecedor, produto_id, fator_conversao]
     * @param int $operadorId ID do usuário que processou a importação
     * @return bool
     * @throws \DomainException Se houver inconsistências nos dados
     */
    public static function darEntradaXml(int $notaFiscalId, array $itensMapeados, int $operadorId): bool
    {
        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $nota = NotaFiscal::buscarPorId($notaFiscalId);
            if (!$nota) {
                throw new \DomainException("Nota fiscal #{$notaFiscalId} não encontrada.");
            }

            $totalUnidadesAdicionadas = 0;
            $now = date('Y-m-d H:i:s');

            foreach ($itensMapeados as $item) {
                $produtoId = (int)($item['produto_id'] ?? 0);
                if ($produtoId <= 0) {
                    throw new \DomainException("Produto interno obrigatório não informado para o item '{$item['descricao_fornecedor']}'.");
                }

                $produto = Produto::buscarPorId($produtoId);
                if (!$produto) {
                    throw new \DomainException("Produto interno #{$produtoId} não localizado no cadastro.");
                }

                $qtdFornecedor = (float)($item['quantidade_fornecedor'] ?? 0);
                $fatorConversao = max(1, (int)($item['fator_conversao'] ?? 1));
                $qtdConvertida = (int)ceil($qtdFornecedor * $fatorConversao);

                if ($qtdConvertida <= 0) {
                    throw new \DomainException("A quantidade convertida para o produto '{$produto['descricao']}' deve ser maior que zero.");
                }

                // 1. Grava o item mapeado em itens_nota_fiscal_xml
                ItemNotaFiscal::criar([
                    'nota_fiscal_id'            => $notaFiscalId,
                    'codigo_produto_fornecedor' => $item['codigo_produto_fornecedor'],
                    'descricao_fornecedor'      => $item['descricao_fornecedor'],
                    'unidade_fornecedor'        => $item['unidade_fornecedor'],
                    'quantidade_fornecedor'     => $qtdFornecedor,
                    'valor_unitario_fornecedor' => (float)$item['valor_unitario_fornecedor'],
                    'produto_id'                => $produtoId,
                    'fator_conversao'           => $fatorConversao,
                    'quantidade_convertida'     => $qtdConvertida
                ]);

                // 2. Incrementa o saldo físico em produtos.estoque_atual
                $sqlUpd = "UPDATE produtos 
                           SET estoque_atual = estoque_atual + :qtd, 
                               updated_at = :upd,
                               updated_by = :user
                           WHERE id = :prod_id";
                self::execute($sqlUpd, [
                    'qtd'     => $qtdConvertida,
                    'upd'     => $now,
                    'user'    => $operadorId,
                    'prod_id' => $produtoId
                ]);

                // 3. Registra a movimentação física em estoque_movimentacoes
                $sqlMov = "INSERT INTO estoque_movimentacoes (
                               produto_id, tipo_movimentacao, quantidade, nota_fiscal_id,
                               operador_id, justificativa, created_at
                           ) VALUES (
                               :produto_id, 'entrada_xml', :quantidade, :nota_fiscal_id,
                               :operador_id, :justificativa, :created_at
                           )";
                self::execute($sqlMov, [
                    'produto_id'     => $produtoId,
                    'quantidade'     => $qtdConvertida,
                    'nota_fiscal_id' => $notaFiscalId,
                    'operador_id'    => $operadorId,
                    'justificativa'  => "Entrada via NF-e nº {$nota['numero_nota']} (Fator {$fatorConversao}x)",
                    'created_at'     => $now
                ]);

                $totalUnidadesAdicionadas += $qtdConvertida;
            }

            // 4. Trilha de Auditoria
            Auditoria::registrar(
                $operadorId,
                'entrada_xml_nfe',
                'notas_fiscais_xml',
                $notaFiscalId,
                "Importação de NF-e nº {$nota['numero_nota']} do emitente {$nota['razao_social_emitente']} — {$totalUnidadesAdicionadas} unidades adicionadas ao estoque.",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra atomicamente a baixa de perda de estoque com motivo obrigatório e auditoria (RN-15)
     * 
     * @param int $produtoId
     * @param int $quantidade
     * @param string $motivo Um dos 4 motivos oficiais (avaria, vencimento, sobra_producao, ajuste_contagem)
     * @param string $justificativa
     * @param int $operadorId
     * @return bool
     * @throws \DomainException Se houver violação de regra de negócio
     */
    public static function registrarPerda(
        int $produtoId,
        int $quantidade,
        string $motivo,
        string $justificativa,
        int $operadorId
    ): bool {
        if ($quantidade <= 0) {
            throw new \DomainException('A quantidade a ser baixada como perda deve ser maior que zero.');
        }

        if (!array_key_exists($motivo, self::MOTIVOS_PERDA)) {
            throw new \DomainException('O motivo da perda informado é inválido. Selecione um dos motivos padronizados.');
        }

        $justificativaLimpa = trim($justificativa);
        if (mb_strlen($justificativaLimpa, 'UTF-8') < 5) {
            throw new \DomainException('A justificativa da perda é obrigatória e deve conter no mínimo 5 caracteres.');
        }

        // Garante que o operadorId seja válido referencialmente
        if ($operadorId <= 0) {
            $userPadrao = self::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $operadorId = (int)($userPadrao['id'] ?? 1);
        } else {
            $existeUser = self::fetchOne("SELECT id FROM usuarios WHERE id = :id", ['id' => $operadorId]);
            if (!$existeUser) {
                $userPadrao = self::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                $operadorId = (int)($userPadrao['id'] ?? $operadorId);
            }
        }

        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Busca o produto com bloqueio pessimista
            $sqlProd = "SELECT id, codigo_interno, descricao, estoque_atual, estoque_reservado 
                        FROM produtos 
                        WHERE id = :id AND deleted_at IS NULL 
                        FOR UPDATE";
            $produto = self::fetchOne($sqlProd, ['id' => $produtoId]);

            if (!$produto) {
                throw new \DomainException("Produto #{$produtoId} não encontrado ou inativo.");
            }

            $estoqueAtual = (int)$produto['estoque_atual'];
            if ($quantidade > $estoqueAtual) {
                throw new \DomainException(
                    "Quantidade de perda ({$quantidade} un) excede o saldo físico atual em estoque ({$estoqueAtual} un) de '{$produto['descricao']}'."
                );
            }

            $now = date('Y-m-d H:i:s');

            // 2. Decrementa o saldo físico em produtos.estoque_atual
            $sqlUpd = "UPDATE produtos 
                       SET estoque_atual = estoque_atual - :qtd,
                           updated_at = :upd,
                           updated_by = :user
                       WHERE id = :prod_id";
            self::execute($sqlUpd, [
                'qtd'     => $quantidade,
                'upd'     => $now,
                'user'    => $operadorId,
                'prod_id' => $produtoId
            ]);

            // 3. Registra a saída em estoque_movimentacoes com o motivo padronizado
            $sqlMov = "INSERT INTO estoque_movimentacoes (
                           produto_id, tipo_movimentacao, quantidade, motivo_perda,
                           operador_id, justificativa, created_at
                       ) VALUES (
                           :produto_id, 'saida_perda', :quantidade, :motivo_perda,
                           :operador_id, :justificativa, :created_at
                       )";
            self::execute($sqlMov, [
                'produto_id'    => $produtoId,
                'quantidade'    => $quantidade,
                'motivo_perda'  => $motivo,
                'operador_id'   => $operadorId,
                'justificativa' => $justificativaLimpa,
                'created_at'    => $now
            ]);

            $movId = (int)self::lastInsertId();

            // 4. Registra na trilha de auditoria técnica compulsória
            $motivoNome = self::MOTIVOS_PERDA[$motivo];
            Auditoria::registrar(
                $operadorId,
                'perda_estoque',
                'produtos',
                $produtoId,
                "Baixa de {$quantidade} un de '{$produto['descricao']}' por motivo: {$motivoNome}. Justificativa: {$justificativaLimpa}",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Lista o extrato cronológico de movimentações com filtros analíticos
     */
    public static function listarMovimentacoes(array $filtros = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT em.*,
                       p.codigo_interno AS produto_codigo,
                       p.descricao AS produto_descricao,
                       c.nome AS categoria_nome,
                       u.nome AS operador_nome,
                       u.usuario AS operador_usuario,
                       nf.numero_nota,
                       nf.razao_social_emitente
                FROM estoque_movimentacoes em
                INNER JOIN produtos p ON p.id = em.produto_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN usuarios u ON u.id = em.operador_id
                LEFT JOIN notas_fiscais_xml nf ON nf.id = em.nota_fiscal_id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['produto_id'])) {
            $sql .= " AND em.produto_id = :prod_id";
            $params['prod_id'] = (int)$filtros['produto_id'];
        }

        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND p.categoria_id = :cat_id";
            $params['cat_id'] = (int)$filtros['categoria_id'];
        }

        if (!empty($filtros['operador_id'])) {
            $sql .= " AND em.operador_id = :op_id";
            $params['op_id'] = (int)$filtros['operador_id'];
        }

        if (!empty($filtros['tipo_movimentacao'])) {
            $sql .= " AND em.tipo_movimentacao = :tipo_mov";
            $params['tipo_mov'] = $filtros['tipo_movimentacao'];
        }

        if (!empty($filtros['motivo_perda'])) {
            $sql .= " AND em.motivo_perda = :motivo_perda";
            $params['motivo_perda'] = $filtros['motivo_perda'];
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND DATE(em.created_at) >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND DATE(em.created_at) <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (p.codigo_interno LIKE :b1 OR p.descricao LIKE :b2 OR em.justificativa LIKE :b3 OR nf.numero_nota LIKE :b4 OR em.fornecedor LIKE :b5)";
            $params['b1'] = $termo;
            $params['b2'] = $termo;
            $params['b3'] = $termo;
            $params['b4'] = $termo;
            $params['b5'] = $termo;
        }

        $sql .= " ORDER BY em.created_at DESC";

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
     * Retorna o total de movimentações de estoque para os filtros aplicados
     */
    public static function contarMovimentacoes(array $filtros = []): int
    {
        $sql = "SELECT COUNT(*) AS total
                FROM estoque_movimentacoes em
                INNER JOIN produtos p ON p.id = em.produto_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN usuarios u ON u.id = em.operador_id
                LEFT JOIN notas_fiscais_xml nf ON nf.id = em.nota_fiscal_id
                WHERE 1=1";

        $params = [];

        if (!empty($filtros['produto_id'])) {
            $sql .= " AND em.produto_id = :prod_id";
            $params['prod_id'] = (int)$filtros['produto_id'];
        }

        if (!empty($filtros['categoria_id'])) {
            $sql .= " AND p.categoria_id = :cat_id";
            $params['cat_id'] = (int)$filtros['categoria_id'];
        }

        if (!empty($filtros['operador_id'])) {
            $sql .= " AND em.operador_id = :op_id";
            $params['op_id'] = (int)$filtros['operador_id'];
        }

        if (!empty($filtros['tipo_movimentacao'])) {
            $sql .= " AND em.tipo_movimentacao = :tipo_mov";
            $params['tipo_mov'] = $filtros['tipo_movimentacao'];
        }

        if (!empty($filtros['motivo_perda'])) {
            $sql .= " AND em.motivo_perda = :motivo_perda";
            $params['motivo_perda'] = $filtros['motivo_perda'];
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND DATE(em.created_at) >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND DATE(em.created_at) <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $sql .= " AND (p.codigo_interno LIKE :b1 OR p.descricao LIKE :b2 OR em.justificativa LIKE :b3 OR nf.numero_nota LIKE :b4 OR em.fornecedor LIKE :b5)";
            $params['b1'] = $termo;
            $params['b2'] = $termo;
            $params['b3'] = $termo;
            $params['b4'] = $termo;
            $params['b5'] = $termo;
        }

        $res = self::fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Retorna o histórico específico de perdas com paginação e filtros
     */
    public static function listarPerdas(array $filtros = []): array
    {
        $filtros['tipo_movimentacao'] = 'saida_perda';
        return self::listarMovimentacoes($filtros);
    }

    /**
     * Atualiza o ponto de estoque mínimo de segurança de um produto
     */
    public static function atualizarEstoqueMinimo(int $produtoId, int $novoMinimo, int $usuarioId): bool
    {
        if ($novoMinimo < 0) {
            throw new \DomainException('O ponto de estoque mínimo não pode ser negativo.');
        }

        $sql = "UPDATE produtos 
                SET estoque_minimo = :minimo, 
                    updated_at = :upd, 
                    updated_by = :user 
                WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, [
            'minimo' => $novoMinimo,
            'upd'    => date('Y-m-d H:i:s'),
            'user'   => $usuarioId,
            'id'     => $produtoId
        ]) > 0;
    }

    /**
     * Registra entrada manual de mercadorias no estoque em lote (sem NF-e)
     * Executa todas as inserções e atualizações em uma única transação atômica.
     * 
     * @param array  $itens            Lista de itens: [['produto_id' => int, 'quantidade' => int, 'preco_custo' => ?float], ...]
     * @param string $fornecedor       Nome do fornecedor / distribuidor
     * @param string|null $numeroDocumento Nº do documento, recibo ou NF
     * @param string|null $observacoes Observações gerais da movimentação
     * @param int    $operadorId       ID do usuário operador logado
     * @return array Resumo da operação: ['total_produtos' => int, 'total_unidades' => int, 'valor_total' => float, 'produtos' => array]
     */
    public static function darEntradaLoteManual(
        array $itens,
        string $fornecedor,
        ?string $numeroDocumento = null,
        ?string $observacoes = null,
        int $operadorId = 1
    ): array {
        $fornecedorLimpo = trim($fornecedor);
        if (mb_strlen($fornecedorLimpo, 'UTF-8') < 2) {
            throw new \DomainException('O nome do fornecedor é obrigatório (mínimo 2 caracteres).');
        }

        if (empty($itens)) {
            throw new \DomainException('Nenhum item informado para entrada no estoque.');
        }

        // Garante que o operadorId seja válido referencialmente
        if ($operadorId <= 0) {
            $userPadrao = self::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $operadorId = (int)($userPadrao['id'] ?? 1);
        } else {
            $existeUser = self::fetchOne("SELECT id FROM usuarios WHERE id = :id", ['id' => $operadorId]);
            if (!$existeUser) {
                $userPadrao = self::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                $operadorId = (int)($userPadrao['id'] ?? $operadorId);
            }
        }

        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $now = date('Y-m-d H:i:s');
            $totalUnidades = 0;
            $totalValor = 0.0;
            $produtosGravados = [];

            // Monta justificativa padrão com documento e observações
            $partesJustificativa = ["Entrada manual em lote - Fornecedor: {$fornecedorLimpo}"];
            if (!empty($numeroDocumento)) {
                $partesJustificativa[] = "Doc/NF: " . trim($numeroDocumento);
            }
            if (!empty($observacoes)) {
                $partesJustificativa[] = trim($observacoes);
            }
            $justificativaBase = implode(' — ', $partesJustificativa);

            foreach ($itens as $item) {
                $produtoId  = (int)($item['produto_id'] ?? 0);
                $quantidade = (int)($item['quantidade'] ?? 0);
                $precoCusto = isset($item['preco_custo']) && $item['preco_custo'] !== null && $item['preco_custo'] !== '' ? (float)$item['preco_custo'] : null;

                if ($produtoId <= 0) {
                    continue;
                }

                if ($quantidade <= 0) {
                    throw new \DomainException("A quantidade para cada produto na entrada deve ser maior que zero.");
                }

                // 1. Bloqueia e localiza o produto
                $sqlProd = "SELECT id, codigo_interno, descricao, estoque_atual, preco_custo 
                            FROM produtos 
                            WHERE id = :id AND deleted_at IS NULL 
                            FOR UPDATE";
                $produto = self::fetchOne($sqlProd, ['id' => $produtoId]);

                if (!$produto) {
                    throw new \DomainException("Produto ID #{$produtoId} não encontrado ou inativo.");
                }

                // 2. Incrementa o saldo físico (e atualiza preco_custo se informado e > 0)
                if ($precoCusto !== null && $precoCusto > 0) {
                    $sqlUpd = "UPDATE produtos 
                               SET estoque_atual = estoque_atual + :qtd,
                                   preco_custo = :preco_custo,
                                   updated_at = :upd,
                                   updated_by = :user
                               WHERE id = :prod_id";
                    self::execute($sqlUpd, [
                        'qtd'         => $quantidade,
                        'preco_custo' => $precoCusto,
                        'upd'         => $now,
                        'user'        => $operadorId,
                        'prod_id'     => $produtoId
                    ]);
                } else {
                    $sqlUpd = "UPDATE produtos 
                               SET estoque_atual = estoque_atual + :qtd,
                                   updated_at = :upd,
                                   updated_by = :user
                               WHERE id = :prod_id";
                    self::execute($sqlUpd, [
                        'qtd'     => $quantidade,
                        'upd'     => $now,
                        'user'    => $operadorId,
                        'prod_id' => $produtoId
                    ]);
                }

                // 3. Registra em estoque_movimentacoes
                $sqlMov = "INSERT INTO estoque_movimentacoes (
                               produto_id, tipo_movimentacao, quantidade, fornecedor,
                               operador_id, justificativa, created_at
                           ) VALUES (
                               :produto_id, 'entrada_fornecedor', :quantidade, :fornecedor,
                               :operador_id, :justificativa, :created_at
                           )";
                self::execute($sqlMov, [
                    'produto_id'    => $produtoId,
                    'quantidade'    => $quantidade,
                    'fornecedor'    => $fornecedorLimpo,
                    'operador_id'   => $operadorId,
                    'justificativa' => $justificativaBase,
                    'created_at'    => $now
                ]);

                $totalUnidades += $quantidade;
                if ($precoCusto !== null && $precoCusto > 0) {
                    $totalValor += ($quantidade * $precoCusto);
                }
                $produtosGravados[] = $produto['descricao'];

                // 4. Trilha de auditoria técnica
                Auditoria::registrar(
                    $operadorId,
                    'entrada_estoque_manual',
                    'produtos',
                    $produtoId,
                    "Entrada manual em lote de {$quantidade} un de '{$produto['descricao']}' (Fornecedor: {$fornecedorLimpo}). Justificativa: {$justificativaBase}",
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
                );
            }

            if (empty($produtosGravados)) {
                throw new \DomainException("Nenhum item válido foi processado para entrada no estoque.");
            }

            $pdo->commit();

            return [
                'total_produtos' => count($produtosGravados),
                'total_unidades' => $totalUnidades,
                'valor_total'    => $totalValor,
                'produtos'       => $produtosGravados
            ];
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Processa atomicamente a entrada manual de mercadorias no estoque físico
     * 
     * @param int $produtoId ID do produto cadastrado
     * @param int $quantidade Quantidade de unidades a adicionar
     * @param string $fornecedor Nome ou razão social do fornecedor
     * @param float|null $precoCusto Preço de custo unitário opcional
     * @param string|null $observacoes Observações ou justificativa operacional
     * @param int $operadorId ID do usuário que realizou a entrada
     * @return bool
     * @throws \DomainException Se houver inconsistências nos parâmetros
     */
    public static function darEntradaManual(
        int $produtoId,
        int $quantidade,
        string $fornecedor,
        ?float $precoCusto = null,
        ?string $observacoes = null,
        int $operadorId = 1
    ): bool {
        if ($produtoId <= 0) {
            throw new \DomainException('Selecione um produto válido para dar entrada.');
        }

        if ($quantidade <= 0) {
            throw new \DomainException('A quantidade a dar entrada deve ser maior que zero.');
        }

        $fornecedorLimpo = trim($fornecedor);
        if (mb_strlen($fornecedorLimpo, 'UTF-8') < 2) {
            throw new \DomainException('O nome do fornecedor é obrigatório (mínimo 2 caracteres).');
        }

        // Garante que o operadorId seja válido referencialmente
        if ($operadorId <= 0) {
            $userPadrao = self::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
            $operadorId = (int)($userPadrao['id'] ?? 1);
        } else {
            $existeUser = self::fetchOne("SELECT id FROM usuarios WHERE id = :id", ['id' => $operadorId]);
            if (!$existeUser) {
                $userPadrao = self::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
                $operadorId = (int)($userPadrao['id'] ?? $operadorId);
            }
        }

        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Localiza o produto com LOCK FOR UPDATE
            $sqlProd = "SELECT id, codigo_interno, descricao, estoque_atual, preco_custo 
                        FROM produtos 
                        WHERE id = :id AND deleted_at IS NULL 
                        FOR UPDATE";
            $produto = self::fetchOne($sqlProd, ['id' => $produtoId]);

            if (!$produto) {
                throw new \DomainException("Produto #{$produtoId} não encontrado ou inativo.");
            }

            $now = date('Y-m-d H:i:s');
            $justificativa = "Entrada manual - Fornecedor: {$fornecedorLimpo}";
            if (!empty($observacoes)) {
                $justificativa .= " — " . trim($observacoes);
            }

            // 2. Incrementa o saldo físico em produtos (e atualiza preco_custo se informado)
            if ($precoCusto !== null && $precoCusto > 0) {
                $sqlUpd = "UPDATE produtos 
                           SET estoque_atual = estoque_atual + :qtd,
                               preco_custo = :preco_custo,
                               updated_at = :upd,
                               updated_by = :user
                           WHERE id = :prod_id";
                self::execute($sqlUpd, [
                    'qtd'         => $quantidade,
                    'preco_custo' => $precoCusto,
                    'upd'         => $now,
                    'user'        => $operadorId,
                    'prod_id'     => $produtoId
                ]);
            } else {
                $sqlUpd = "UPDATE produtos 
                           SET estoque_atual = estoque_atual + :qtd,
                               updated_at = :upd,
                               updated_by = :user
                           WHERE id = :prod_id";
                self::execute($sqlUpd, [
                    'qtd'     => $quantidade,
                    'upd'     => $now,
                    'user'    => $operadorId,
                    'prod_id' => $produtoId
                ]);
            }

            // 3. Registra em estoque_movimentacoes
            $sqlMov = "INSERT INTO estoque_movimentacoes (
                           produto_id, tipo_movimentacao, quantidade, fornecedor,
                           operador_id, justificativa, created_at
                       ) VALUES (
                           :produto_id, 'entrada_fornecedor', :quantidade, :fornecedor,
                           :operador_id, :justificativa, :created_at
                       )";
            self::execute($sqlMov, [
                'produto_id'    => $produtoId,
                'quantidade'    => $quantidade,
                'fornecedor'    => $fornecedorLimpo,
                'operador_id'   => $operadorId,
                'justificativa' => $justificativa,
                'created_at'    => $now
            ]);

            $movId = (int)self::lastInsertId();

            // 4. Trilha de auditoria técnica
            Auditoria::registrar(
                $operadorId,
                'entrada_estoque_manual',
                'produtos',
                $produtoId,
                "Entrada manual de {$quantidade} un de '{$produto['descricao']}' (Fornecedor: {$fornecedorLimpo}). Justificativa: {$justificativa}",
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            );

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Retorna as últimas entradas de estoque registradas
     */
    public static function listarEntradasRecentes(int $limite = 20): array
    {
        $sql = "SELECT em.*,
                       p.codigo_interno AS produto_codigo,
                       p.descricao AS produto_descricao,
                       c.nome AS categoria_nome,
                       u.nome AS operador_nome
                FROM estoque_movimentacoes em
                INNER JOIN produtos p ON p.id = em.produto_id
                INNER JOIN categorias c ON c.id = p.categoria_id
                INNER JOIN usuarios u ON u.id = em.operador_id
                WHERE em.tipo_movimentacao IN ('entrada_fornecedor', 'entrada_xml', 'aproveitamento_sobra')
                ORDER BY em.created_at DESC
                LIMIT " . (int)$limite;

        return self::fetchAll($sql);
    }

    /**
     * Retorna os vínculos históricos entre fornecedores e produtos (em movimentações e NF-e)
     */
    public static function obterMapaProdutosFornecedores(): array
    {
        $sql = "SELECT DISTINCT LOWER(TRIM(forn)) AS fornecedor_norm, TRIM(forn) AS fornecedor_nome, produto_id
                FROM (
                    SELECT em.fornecedor AS forn, em.produto_id 
                    FROM estoque_movimentacoes em 
                    WHERE em.fornecedor IS NOT NULL AND TRIM(em.fornecedor) != ''
                    UNION
                    SELECT nf.razao_social_emitente AS forn, inf.produto_id 
                    FROM itens_nota_fiscal_xml inf 
                    INNER JOIN notas_fiscais_xml nf ON nf.id = inf.nota_fiscal_id 
                    WHERE inf.produto_id IS NOT NULL AND nf.razao_social_emitente IS NOT NULL AND TRIM(nf.razao_social_emitente) != ''
                ) AS mapa
                WHERE forn IS NOT NULL";

        return self::fetchAll($sql);
    }

    /**
     * Retorna a lista de nomes de fornecedores únicos para autocompletar / datalist
     */
    public static function listarFornecedoresSugestao(): array
    {
        $sql = "SELECT DISTINCT fornecedor FROM (
                    SELECT fornecedor FROM estoque_movimentacoes WHERE fornecedor IS NOT NULL AND TRIM(fornecedor) != ''
                    UNION
                    SELECT razao_social_emitente AS fornecedor FROM notas_fiscais_xml WHERE razao_social_emitente IS NOT NULL AND TRIM(razao_social_emitente) != ''
                ) AS forn_unificados
                ORDER BY fornecedor ASC";

        $rows = self::fetchAll($sql);
        return array_column($rows, 'fornecedor');
    }
}

