<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo: VendaBalcao (Ponto de Venda - PDV e Vendas de Balcão)
 * 
 * Responsável pelo processamento atômico de vendas avulsas e consumo de colaboradores,
 * cálculo de troco, baixa de estoque físico em tempo real e atualização de caixas.
 * 
 * Conforme docs/FSD.md (Área 4, Módulo 5, Seção 11.20) e Migration 020
 */

namespace App\Models;

use App\Core\Model;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class VendaBalcao extends Model
{
    protected string $table = 'vendas_balcao';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false; // Gerenciado por created_at
    protected bool $softDelete = false;

    public const TIPO_AVULSO_ALUNO   = 'avulso_aluno';
    public const TIPO_DESCONTO_FOLHA = 'desconto_folha';

    public const FORMA_DINHEIRO       = 'dinheiro';
    public const FORMA_PIX_BALCAO     = 'pix_balcao';
    public const FORMA_DESCONTO_FOLHA = 'desconto_folha';

    /**
     * Cria e processa atomicamente uma venda no PDV de balcão
     * 
     * @param array $dadosVenda Cabeçalho da venda (tipo_venda, forma_pagamento, aluno_id, colaborador_id, valor_dinheiro_recebido)
     * @param array $itens Lista de itens [{produto_id, quantidade, preco_unitario}]
     * @param int $operadorId ID do operador autenticado
     * @param int $sessaoCaixaId ID da sessão de caixa do turno
     * @return array Detalhes consolidados da venda concluída
     * @throws \InvalidArgumentException|\DomainException|\PDOException
     */
    public static function criarVenda(
        array $dadosVenda,
        array $itens,
        int $operadorId,
        int $sessaoCaixaId
    ): array {
        if (empty($itens)) {
            throw new \InvalidArgumentException('A cesta de compras do PDV não pode estar vazia.');
        }

        // Validação da Sessão de Caixa
        $sessao = SessaoCaixa::buscarPorId($sessaoCaixaId);
        if (!$sessao || $sessao['status'] !== SessaoCaixa::STATUS_ABERTO) {
            throw new \DomainException('A sessão de caixa informada não existe ou já está fechada. Abra o caixa antes de operar o PDV.');
        }

        $tipoVenda = $dadosVenda['tipo_venda'] ?? self::TIPO_AVULSO_ALUNO;
        $formaPagamento = $dadosVenda['forma_pagamento'] ?? self::FORMA_DINHEIRO;
        $alunoId = !empty($dadosVenda['aluno_id']) ? (int)$dadosVenda['aluno_id'] : null;
        $colaboradorId = !empty($dadosVenda['colaborador_id']) ? (int)$dadosVenda['colaborador_id'] : null;

        if ($formaPagamento === self::FORMA_DESCONTO_FOLHA) {
            $tipoVenda = self::TIPO_DESCONTO_FOLHA;
            if (!$colaboradorId) {
                throw new \InvalidArgumentException('Para vendas sob Desconto em Folha, o colaborador deve ser identificado obrigatoriamente.');
            }
            $colaborador = Colaborador::buscarPorId($colaboradorId);
            if (!$colaborador || (int)$colaborador['ativo'] !== 1) {
                throw new \DomainException('Colaborador não localizado ou inativo para compras sob desconto em folha.');
            }
        }

        // Cálculo do valor total e validação dos itens
        $valorTotal = 0.0;
        $itensNormalizados = [];

        foreach ($itens as $item) {
            $prodId = (int)($item['produto_id'] ?? 0);
            $qtd = (int)($item['quantidade'] ?? 0);
            $precoUnit = (float)($item['preco_unitario'] ?? 0.0);

            if ($prodId <= 0 || $qtd <= 0) {
                throw new \InvalidArgumentException('Item inválido na cesta de compras.');
            }

            $produto = Produto::buscarPorId($prodId);
            if (!$produto) {
                throw new \DomainException("Produto ID #{$prodId} não localizado.");
            }

            if ($produto['status_pausa'] === 'pausado') {
                throw new \DomainException("O produto '{$produto['descricao']}' está pausado no cardápio.");
            }

            // Checagem de saldo físico em estoque
            $estoqueAtual = (int)$produto['estoque_atual'];
            if ($estoqueAtual < $qtd) {
                throw new \DomainException(sprintf(
                    "Estoque insuficiente para '%s'. Solicitado: %d un, Disponível em estoque: %d un.",
                    $produto['descricao'],
                    $qtd,
                    max(0, $estoqueAtual)
                ));
            }

            $subtotal = round($qtd * $precoUnit, 2);
            $valorTotal = round($valorTotal + $subtotal, 2);

            $itensNormalizados[] = [
                'produto_id'     => $prodId,
                'descricao'      => $produto['descricao'],
                'quantidade'     => $qtd,
                'preco_unitario' => $precoUnit,
                'subtotal'       => $subtotal
            ];
        }

        // Tratamento financeiro de troco para dinheiro
        $valorRecebido = null;
        $troco = null;

        if ($formaPagamento === self::FORMA_DINHEIRO) {
            $valorRecebido = (float)($dadosVenda['valor_dinheiro_recebido'] ?? $valorTotal);
            $valorRecebido = round($valorRecebido, 2);

            if ($valorRecebido < $valorTotal) {
                throw new \DomainException(sprintf(
                    "Valor recebido (R$ %s) é insuficiente para cobrir o total da venda (R$ %s).",
                    number_format($valorRecebido, 2, ',', '.'),
                    number_format($valorTotal, 2, ',', '.')
                ));
            }

            $troco = round($valorRecebido - $valorTotal, 2);
        }

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Inserir cabeçalho da venda em vendas_balcao
            $stmtVenda = $pdo->prepare("
                INSERT INTO vendas_balcao (
                    sessao_caixa_id, operador_id, tipo_venda, aluno_id, colaborador_id,
                    forma_pagamento, valor_total, valor_dinheiro_recebido, troco_devolvido,
                    created_at
                ) VALUES (
                    :sessao_caixa_id, :operador_id, :tipo_venda, :aluno_id, :colaborador_id,
                    :forma_pagamento, :valor_total, :valor_recebido, :troco,
                    NOW()
                )
            ");

            $stmtVenda->execute([
                'sessao_caixa_id' => $sessaoCaixaId,
                'operador_id'     => $operadorId,
                'tipo_venda'      => $tipoVenda,
                'aluno_id'        => $alunoId,
                'colaborador_id'  => $colaboradorId,
                'forma_pagamento' => $formaPagamento,
                'valor_total'     => $valorTotal,
                'valor_recebido'  => $valorRecebido,
                'troco'           => $troco
            ]);

            $vendaId = (int)$pdo->lastInsertId();

            // 2. Inserir itens em itens_venda_balcao, baixar estoque físico e registrar movimentação
            $stmtItem = $pdo->prepare("
                INSERT INTO itens_venda_balcao (
                    venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal
                ) VALUES (
                    :venda_id, :produto_id, :quantidade, :preco_unitario, :subtotal
                )
            ");

            $stmtBaixaEstoque = $pdo->prepare("
                UPDATE produtos
                SET estoque_atual = estoque_atual - :quantidade
                WHERE id = :produto_id
            ");

            $stmtMovEstoque = $pdo->prepare("
                INSERT INTO estoque_movimentacoes (
                    produto_id, tipo_movimentacao, quantidade, motivo_perda,
                    nota_fiscal_id, operador_id, justificativa, created_at
                ) VALUES (
                    :produto_id, 'saida_venda_avulsa', :quantidade, NULL,
                    NULL, :operador_id, :justificativa, NOW()
                )
            ");

            foreach ($itensNormalizados as $item) {
                // Insere item
                $stmtItem->execute([
                    'venda_id'       => $vendaId,
                    'produto_id'     => $item['produto_id'],
                    'quantidade'     => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'subtotal'       => $item['subtotal']
                ]);

                // Baixa física no estoque
                $stmtBaixaEstoque->execute([
                    'quantidade' => $item['quantidade'],
                    'produto_id' => $item['produto_id']
                ]);

                // Movimentação rastreável de estoque
                $stmtMovEstoque->execute([
                    'produto_id'    => $item['produto_id'],
                    'quantidade'    => $item['quantidade'],
                    'operador_id'   => $operadorId,
                    'justificativa' => "Venda no PDV de Balcão #{$vendaId}"
                ]);
            }

            // 3. Atualizar totalizadores na sessão de caixa correspondente
            if ($formaPagamento === self::FORMA_DINHEIRO) {
                SessaoCaixa::incrementarTotais($sessaoCaixaId, $valorTotal, 0.0, 0.0);
            } elseif ($formaPagamento === self::FORMA_PIX_BALCAO) {
                SessaoCaixa::incrementarTotais($sessaoCaixaId, 0.0, $valorTotal, 0.0);
            }
            // Para 'desconto_folha', o montante não afeta a gaveta física/Pix da sessão de caixa corrente

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return self::buscarPorId($vendaId);
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Busca os dados detalhados de uma venda de balcão por ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        $venda = self::fetchOne("
            SELECT 
                vb.*,
                u.nome AS operador_nome,
                u.usuario AS operador_login,
                c.nome AS colaborador_nome,
                c.matricula_funcional AS colaborador_matricula,
                c.cargo AS colaborador_cargo,
                c.foto_url AS colaborador_foto,
                a.nome AS aluno_nome,
                a.matricula AS aluno_matricula,
                tur.nome_turma AS aluno_turma,
                sc.turno AS sessao_turno,
                sc.aberto_em AS sessao_aberto_em
            FROM vendas_balcao vb
            INNER JOIN usuarios u ON u.id = vb.operador_id
            INNER JOIN sessoes_caixa sc ON sc.id = vb.sessao_caixa_id
            LEFT JOIN colaboradores c ON c.id = vb.colaborador_id
            LEFT JOIN alunos a ON a.id = vb.aluno_id
            LEFT JOIN turmas tur ON tur.id = a.turma_id
            WHERE vb.id = :id
            LIMIT 1
        ", ['id' => $id]);

        if (!$venda) {
            return null;
        }

        $venda['itens'] = ItemVendaBalcao::listarPorVenda($id);

        return $venda;
    }

    /**
     * Lista as vendas recentes realizadas no PDV
     * 
     * @param int $limite
     * @param int|null $sessaoCaixaId
     * @return array
     */
    public static function listarRecentes(int $limite = 20, ?int $sessaoCaixaId = null): array
    {
        $sql = "
            SELECT 
                vb.*,
                u.nome AS operador_nome,
                c.nome AS colaborador_nome,
                c.cargo AS colaborador_cargo,
                a.nome AS aluno_nome,
                COUNT(ivb.id) AS total_itens
            FROM vendas_balcao vb
            INNER JOIN usuarios u ON u.id = vb.operador_id
            LEFT JOIN colaboradores c ON c.id = vb.colaborador_id
            LEFT JOIN alunos a ON a.id = vb.aluno_id
            LEFT JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id
        ";

        $params = [];
        if ($sessaoCaixaId !== null) {
            $sql .= " WHERE vb.sessao_caixa_id = :sessao_id";
            $params['sessao_id'] = $sessaoCaixaId;
        }

        $sql .= "
            GROUP BY vb.id
            ORDER BY vb.id DESC
            LIMIT {$limite}
        ";

        return self::fetchAll($sql, $params);
    }

    /**
     * Retorna métricas resumidas de vendas de hoje para exibição na stat-strip do PDV
     * 
     * @param int|null $sessaoCaixaId
     * @return array
     */
    public static function obterMetricasHoje(?int $sessaoCaixaId = null): array
    {
        $sql = "
            SELECT 
                COUNT(vb.id) AS total_vendas,
                COALESCE(SUM(vb.valor_total), 0.00) AS total_faturado,
                COALESCE(SUM(CASE WHEN vb.forma_pagamento = 'dinheiro' THEN vb.valor_total ELSE 0 END), 0.00) AS total_dinheiro,
                COALESCE(SUM(CASE WHEN vb.forma_pagamento = 'pix_balcao' THEN vb.valor_total ELSE 0 END), 0.00) AS total_pix,
                COALESCE(SUM(CASE WHEN vb.forma_pagamento = 'desconto_folha' THEN vb.valor_total ELSE 0 END), 0.00) AS total_folha
            FROM vendas_balcao vb
            WHERE DATE(vb.created_at) = CURDATE()
        ";

        $params = [];
        if ($sessaoCaixaId !== null) {
            $sql .= " AND vb.sessao_caixa_id = :sessao_id";
            $params['sessao_id'] = $sessaoCaixaId;
        }

        $row = self::fetchOne($sql, $params);

        return [
            'total_vendas'    => (int)($row['total_vendas'] ?? 0),
            'total_faturado'  => (float)($row['total_faturado'] ?? 0.00),
            'total_dinheiro'  => (float)($row['total_dinheiro'] ?? 0.00),
            'total_pix'       => (float)($row['total_pix'] ?? 0.00),
            'total_folha'     => (float)($row['total_folha'] ?? 0.00)
        ];
    }
}
