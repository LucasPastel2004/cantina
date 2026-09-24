<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Domínio: Transações da Carteira Familiar (Livro-Razão Contábil)
 * 
 * Responsável pela integridade contábil, saldo familiar compartilhado,
 * operações atômicas de crédito e débito com bloqueio pessimista (FOR UPDATE)
 * e extrato analítico auditado linha a linha.
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class TransacaoCarteira extends Model
{
    protected string $table = 'transacoes_carteira';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false; // Gerenciado nativamente pela coluna created_at
    protected bool $softDelete = false; // Livro-razão é imutável

    // Tipos de transação oficiais do FSD
    public const TIPO_RECARGA_PIX           = 'recarga_pix';
    public const TIPO_RECARGA_DINHEIRO      = 'recarga_dinheiro';
    public const TIPO_DEBITO_PEDIDO         = 'debito_pedido';
    public const TIPO_ESTORNO_CANCELAMENTO  = 'estorno_cancelamento';
    public const TIPO_DEBITO_CONTESTACAO_MED= 'debito_contestacao_med';
    public const TIPO_ESTORNO_BALCAO        = 'estorno_balcao';
    public const TIPO_AJUSTE_MANUAL         = 'ajuste_manual';

    /**
     * Realiza um crédito atômico na carteira do responsável
     * 
     * @param int $responsavelId ID do titular da conta
     * @param float $valor Valor positivo do crédito
     * @param string $tipo Tipo da transação
     * @param string $descricao Descrição da movimentação
     * @param string|null $referenciaId Identificador externo (ex: txid do Pix)
     * @param int|null $alunoId Dependente associado (opcional)
     * @param int|null $operadorId Operador interno responsável (opcional)
     * @return array Dados completos da transação gravada
     * @throws \InvalidArgumentException|\DomainException|\PDOException
     */
    public static function creditar(
        int $responsavelId,
        float $valor,
        string $tipo = self::TIPO_RECARGA_PIX,
        string $descricao = 'Crédito na Carteira Familiar',
        ?string $referenciaId = null,
        ?int $alunoId = null,
        ?int $operadorId = null
    ): array {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('O valor do crédito deve ser maior que zero.');
        }

        $valor = round($valor, 2);
        $pdo = self::getPdo();
        $iniciouTransacao = false;

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Bloqueia o registro do responsável para leitura e escrita atômica
            $stmt = $pdo->prepare("SELECT id, saldo_atual, bloqueado_med, nome, whatsapp FROM responsaveis WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $responsavelId]);
            $resp = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$resp) {
                throw new \DomainException('Responsável titular não localizado para efetuar o crédito.');
            }

            $saldoAnterior = (float)$resp['saldo_atual'];
            $saldoResultante = round($saldoAnterior + $valor, 2);

            // 2. Atualiza o saldo do responsável no MySQL
            // Se o saldo agora for positivo ou zero e estava bloqueado por MED, avalia desbloqueio
            $stmtUp = $pdo->prepare("UPDATE responsaveis SET saldo_atual = :saldo, updated_at = NOW() WHERE id = :id");
            $stmtUp->execute([
                'saldo' => $saldoResultante,
                'id'    => $responsavelId
            ]);

            // 3. Grava o lançamento no livro-razão imutável da carteira
            $sqlInsert = "INSERT INTO transacoes_carteira (
                responsavel_id, aluno_id, operador_id, tipo_transacao,
                valor, saldo_anterior, saldo_resultante, referencia_id,
                descricao, created_at
            ) VALUES (
                :responsavel_id, :aluno_id, :operador_id, :tipo_transacao,
                :valor, :saldo_anterior, :saldo_resultante, :referencia_id,
                :descricao, NOW()
            )";

            $stmtIns = $pdo->prepare($sqlInsert);
            $stmtIns->execute([
                'responsavel_id'   => $responsavelId,
                'aluno_id'         => $alunoId,
                'operador_id'      => $operadorId,
                'tipo_transacao'   => $tipo,
                'valor'            => $valor,
                'saldo_anterior'   => $saldoAnterior,
                'saldo_resultante' => $saldoResultante,
                'referencia_id'    => $referenciaId,
                'descricao'        => trim($descricao)
            ]);

            $transacaoId = (int)$pdo->lastInsertId();

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'id'               => $transacaoId,
                'responsavel_id'   => $responsavelId,
                'aluno_id'         => $alunoId,
                'operador_id'      => $operadorId,
                'tipo_transacao'   => $tipo,
                'valor'            => $valor,
                'saldo_anterior'   => $saldoAnterior,
                'saldo_resultante' => $saldoResultante,
                'referencia_id'    => $referenciaId,
                'descricao'        => $descricao,
                'created_at'       => date('Y-m-d H:i:s')
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Realiza um débito atômico na carteira do responsável
     * 
     * @param int $responsavelId ID do titular da conta
     * @param float $valor Valor positivo a debitar
     * @param string $tipo Tipo da transação
     * @param string $descricao Descrição do débito
     * @param string|null $referenciaId Identificador (ex: código do pedido)
     * @param int|null $alunoId Dependente beneficiado (opcional)
     * @param int|null $operadorId Operador interno responsável (opcional)
     * @param bool $permitirNegativo Permite saldo negativo apenas em casos excepcionais (ex: MED)
     * @return array Dados da transação gravada
     * @throws \InvalidArgumentException|\DomainException|\PDOException
     */
    public static function debitar(
        int $responsavelId,
        float $valor,
        string $tipo = self::TIPO_DEBITO_PEDIDO,
        string $descricao = 'Débito na Carteira Familiar',
        ?string $referenciaId = null,
        ?int $alunoId = null,
        ?int $operadorId = null,
        bool $permitirNegativo = false
    ): array {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('O valor do débito deve ser maior que zero.');
        }

        $valor = round($valor, 2);
        $pdo = self::getPdo();
        $iniciouTransacao = false;

        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Bloqueia o registro do responsável
            $stmt = $pdo->prepare("SELECT id, saldo_atual, bloqueado_med, nome FROM responsaveis WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $responsavelId]);
            $resp = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$resp) {
                throw new \DomainException('Responsável titular não localizado para efetuar o débito.');
            }

            // 2. Verifica bloqueio de conta por MED (impede novos débitos de pedidos)
            if ((int)$resp['bloqueado_med'] === 1 && !$permitirNegativo) {
                throw new \DomainException('Conta suspensa preventivamente por contestação bancária (MED). Operação bloqueada.');
            }

            $saldoAnterior = (float)$resp['saldo_atual'];

            // 3. Trava compulsória de saldo suficiente (modelo estritamente pré-pago)
            if (!$permitirNegativo && $saldoAnterior < $valor) {
                throw new \DomainException(
                    sprintf(
                        'Saldo insuficiente na carteira familiar. Saldo disponível: R$ %s. Valor requerido: R$ %s.',
                        number_format($saldoAnterior, 2, ',', '.'),
                        number_format($valor, 2, ',', '.')
                    )
                );
            }

            $saldoResultante = round($saldoAnterior - $valor, 2);

            // 4. Atualiza o saldo
            $stmtUp = $pdo->prepare("UPDATE responsaveis SET saldo_atual = :saldo, updated_at = NOW() WHERE id = :id");
            $stmtUp->execute([
                'saldo' => $saldoResultante,
                'id'    => $responsavelId
            ]);

            // 5. Grava no livro-razão
            $sqlInsert = "INSERT INTO transacoes_carteira (
                responsavel_id, aluno_id, operador_id, tipo_transacao,
                valor, saldo_anterior, saldo_resultante, referencia_id,
                descricao, created_at
            ) VALUES (
                :responsavel_id, :aluno_id, :operador_id, :tipo_transacao,
                :valor, :saldo_anterior, :saldo_resultante, :referencia_id,
                :descricao, NOW()
            )";

            $stmtIns = $pdo->prepare($sqlInsert);
            $stmtIns->execute([
                'responsavel_id'   => $responsavelId,
                'aluno_id'         => $alunoId,
                'operador_id'      => $operadorId,
                'tipo_transacao'   => $tipo,
                'valor'            => $valor,
                'saldo_anterior'   => $saldoAnterior,
                'saldo_resultante' => $saldoResultante,
                'referencia_id'    => $referenciaId,
                'descricao'        => trim($descricao)
            ]);

            $transacaoId = (int)$pdo->lastInsertId();

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'id'               => $transacaoId,
                'responsavel_id'   => $responsavelId,
                'aluno_id'         => $alunoId,
                'operador_id'      => $operadorId,
                'tipo_transacao'   => $tipo,
                'valor'            => $valor,
                'saldo_anterior'   => $saldoAnterior,
                'saldo_resultante' => $saldoResultante,
                'referencia_id'    => $referenciaId,
                'descricao'        => $descricao,
                'created_at'       => date('Y-m-d H:i:s')
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Localiza uma transação pelo seu identificador de referência externo (ex: txid do Pix)
     * Essencial para garantir a idempotência no recebimento de webhooks bancários
     */
    public static function buscarPorReferencia(string $referenciaId): ?array
    {
        $sql = "SELECT tc.*, r.nome AS responsavel_nome, r.cpf AS responsavel_cpf 
                FROM transacoes_carteira tc 
                INNER JOIN responsaveis r ON r.id = tc.responsavel_id 
                WHERE tc.referencia_id = :ref 
                LIMIT 1";

        return self::fetchOne($sql, ['ref' => trim($referenciaId)]);
    }

    /**
     * Retorna o resumo consolidado da carteira familiar:
     * Saldo Total, Saldo Reservado em agendamentos futuros e Saldo Disponível
     */
    public static function obterResumoCarteira(int $responsavelId): array
    {
        // 1. Dados cadastrais e saldo atual do responsável
        $resp = self::fetchOne(
            "SELECT id, nome, cpf, whatsapp, saldo_atual, bloqueado_med FROM responsaveis WHERE id = :id LIMIT 1",
            ['id' => $responsavelId]
        );

        if (!$resp) {
            throw new \DomainException('Responsável não localizado.');
        }

        $saldoAtual = (float)$resp['saldo_atual'];
        $bloqueadoMed = (int)$resp['bloqueado_med'] === 1;

        // 2. Saldo Reservado: soma dos pedidos agendados futuros para o turno ou datas posteriores
        $sqlReservado = "SELECT COALESCE(SUM(valor_total), 0) AS total_reservado 
                         FROM pedidos 
                         WHERE responsavel_id = :resp_id 
                         AND status IN ('agendado', 'editado') 
                         AND data_consumo >= CURDATE()";
        $rowReservado = self::fetchOne($sqlReservado, ['resp_id' => $responsavelId]);
        $saldoReservado = (float)($rowReservado['total_reservado'] ?? 0.0);

        // 3. Saldo Disponível: como o valor do pedido já é debitado compulsoriamente
        // no ato do agendamento (Fluxo 3 / FSD), o saldo_atual da carteira familiar já é o saldo líquido
        // disponível para novas compras.
        $saldoDisponivel = $saldoAtual;
        $saldoTotalCustodia = round($saldoAtual + $saldoReservado, 2);

        // 4. Parâmetros globais da cantina
        $configGlobal = Configuracao::obter();
        $alertaSaldoBaixoValor = (float)($configGlobal['alerta_saldo_baixo'] ?? 15.00);
        $recargaMinimaPix = (float)($configGlobal['recarga_minima_pix'] ?? 10.00);

        // Dispara alerta se o saldo disponível for menor que o limite de alerta
        $isSaldoBaixo = ($saldoDisponivel < $alertaSaldoBaixoValor);

        return [
            'responsavel_id'            => $responsavelId,
            'responsavel_nome'          => $resp['nome'],
            'responsavel_cpf'           => $resp['cpf'],
            'responsavel_whatsapp'      => $resp['whatsapp'],
            'saldo_atual'               => $saldoAtual,
            'saldo_atual_fmt'           => 'R$ ' . number_format($saldoAtual, 2, ',', '.'),
            'saldo_reservado'           => $saldoReservado,
            'saldo_reservado_fmt'       => 'R$ ' . number_format($saldoReservado, 2, ',', '.'),
            'saldo_disponivel'          => $saldoDisponivel,
            'saldo_disponivel_fmt'      => 'R$ ' . number_format($saldoDisponivel, 2, ',', '.'),
            'saldo_total'               => $saldoTotalCustodia,
            'saldo_total_fmt'           => 'R$ ' . number_format($saldoTotalCustodia, 2, ',', '.'),
            'bloqueado_med'             => $bloqueadoMed,
            'alerta_saldo_baixo'        => $isSaldoBaixo,
            'alerta_saldo_baixo_valor'  => $alertaSaldoBaixoValor,
            'recarga_minima_pix'        => $recargaMinimaPix,
        ];
    }

    /**
     * Retorna o extrato contábil analítico paginado com filtros opcionais
     */
    public static function obterExtrato(
        int $responsavelId,
        array $filtros = [],
        int $pagina = 1,
        int $porPagina = 25
    ): array {
        $where = ["tc.responsavel_id = :resp_id"];
        $params = ['resp_id' => $responsavelId];

        // Filtro por Aluno / Dependente
        if (!empty($filtros['aluno_id'])) {
            $where[] = "tc.aluno_id = :aluno_id";
            $params['aluno_id'] = (int)$filtros['aluno_id'];
        }

        // Filtro por Tipo de Transação
        if (!empty($filtros['tipo_transacao'])) {
            $where[] = "tc.tipo_transacao = :tipo";
            $params['tipo'] = $filtros['tipo_transacao'];
        }

        // Filtro por Período
        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(tc.created_at) >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(tc.created_at) <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }

        $whereSql = implode(' AND ', $where);

        // 1. Contagem total de registros para paginação
        $countSql = "SELECT COUNT(*) AS total FROM transacoes_carteira tc WHERE {$whereSql}";
        $totalRow = self::fetchOne($countSql, $params);
        $total = (int)($totalRow['total'] ?? 0);

        // 2. Paginação
        $pagina = max(1, $pagina);
        $porPagina = max(5, min(100, $porPagina));
        $totalPaginas = (int)ceil($total / $porPagina);
        $offset = ($pagina - 1) * $porPagina;

        // 3. Consulta dos itens com dados do aluno e operador
        $sql = "SELECT tc.*, 
                       a.nome AS aluno_nome, 
                       a.matricula AS aluno_matricula,
                       u.nome AS operador_nome
                FROM transacoes_carteira tc
                LEFT JOIN alunos a ON a.id = tc.aluno_id
                LEFT JOIN usuarios u ON u.id = tc.operador_id
                WHERE {$whereSql}
                ORDER BY tc.created_at DESC, tc.id DESC
                LIMIT {$porPagina} OFFSET {$offset}";

        $itens = self::fetchAll($sql, $params);

        // Formata os campos para exibição amigável
        foreach ($itens as &$item) {
            $item['valor_num'] = (float)$item['valor'];
            $item['saldo_anterior_num'] = (float)$item['saldo_anterior'];
            $item['saldo_resultante_num'] = (float)$item['saldo_resultante'];
            $item['is_credito'] = in_array($item['tipo_transacao'], [
                self::TIPO_RECARGA_PIX,
                self::TIPO_RECARGA_DINHEIRO,
                self::TIPO_ESTORNO_CANCELAMENTO,
                self::TIPO_ESTORNO_BALCAO
            ]);
            $item['tipo_rotulo'] = self::obterRotuloTipo($item['tipo_transacao']);
            $item['data_fmt'] = date('d/m/Y H:i', strtotime($item['created_at']));
        }

        return [
            'itens'         => $itens,
            'total'         => $total,
            'pagina'        => $pagina,
            'por_pagina'    => $porPagina,
            'total_paginas' => max(1, $totalPaginas)
        ];
    }

    /**
     * Rótulo em linguagem natural e corporativa para os tipos de transação
     */
    public static function obterRotuloTipo(string $tipo): string
    {
        return match ($tipo) {
            self::TIPO_RECARGA_PIX            => 'Recarga Pix Online',
            self::TIPO_RECARGA_DINHEIRO       => 'Recarga Balcão (Dinheiro)',
            self::TIPO_DEBITO_PEDIDO          => 'Agendamento de Lanche',
            self::TIPO_ESTORNO_CANCELAMENTO   => 'Estorno de Cancelamento',
            self::TIPO_DEBITO_CONTESTACAO_MED => 'Estorno Devolução MED',
            self::TIPO_ESTORNO_BALCAO         => 'Estorno de Balcão',
            self::TIPO_AJUSTE_MANUAL          => 'Ajuste Administrativo',
            default                           => ucfirst(str_replace('_', ' ', $tipo)),
        };
    }

    /**
     * Processa atomicamente um estorno bancário MED (Mecanismo Especial de Devolução)
     * 
     * @param int $responsavelId ID do titular da conta familiar
     * @param float $valor Valor contestado e estornado
     * @param string $identificadorMed Protocolo / rtrId / txid da devolução
     * @param string $motivo Justificativa ou motivo da contestação
     * @param int|null $operadorId ID do operador/administrador caso manual
     * @param bool $dispararNotificacao Se deve enviar mensagem no WhatsApp
     * @return array Dados da transação gerada
     */
    public static function processarEstornoMed(
        int $responsavelId,
        float $valor,
        string $identificadorMed,
        string $motivo = 'Mecanismo Especial de Devolução (MED) - Contestação Bancária',
        ?int $operadorId = null,
        bool $dispararNotificacao = true
    ): array {
        // 1. Garantia de Idempotência: verifica se esta devolução já foi processada
        $transacaoExistente = self::fetchOne(
            "SELECT * FROM transacoes_carteira 
             WHERE responsavel_id = :resp_id 
             AND tipo_transacao = :tipo 
             AND referencia_id = :ref 
             LIMIT 1",
            [
                'resp_id' => $responsavelId,
                'tipo'    => self::TIPO_DEBITO_CONTESTACAO_MED,
                'ref'     => trim($identificadorMed)
            ]
        );

        if ($transacaoExistente) {
            $resp = Responsavel::buscarPorId($responsavelId);
            return [
                'sucesso'          => true,
                'ja_processado'    => true,
                'idempotente'      => true,
                'transacao_id'     => (int)$transacaoExistente['id'],
                'responsavel_id'   => $responsavelId,
                'valor'            => (float)$transacaoExistente['valor'],
                'saldo_resultante' => (float)$transacaoExistente['saldo_resultante'],
                'bloqueado_med'    => (bool)($resp['bloqueado_med'] ?? false),
                'mensagem'         => 'Esta devolução MED já foi debitada e processada anteriormente.'
            ];
        }

        // 2. Executa o débito permitindo saldo negativo
        $descricao = sprintf('Contestação Pix (MED) - Ref: %s - %s', $identificadorMed, $motivo);
        $transacao = self::debitar(
            $responsavelId,
            $valor,
            self::TIPO_DEBITO_CONTESTACAO_MED,
            $descricao,
            $identificadorMed,
            null,
            $operadorId,
            true // Permite saldo negativo excepcional
        );

        $saldoResultante = (float)$transacao['saldo_resultante'];
        $foiBloqueado = false;

        // 3. Se o saldo resultante ficou negativo (< 0), ativa bloqueio automático e notifica (FSD Área 6 e Fluxo 11)
        if ($saldoResultante < 0) {
            Responsavel::bloquearMed($responsavelId, $motivo, $operadorId);
            $foiBloqueado = true;

            // Envia WhatsApp urgente
            if ($dispararNotificacao) {
                $resp = Responsavel::buscarPorId($responsavelId);
                if ($resp && !empty($resp['whatsapp'])) {
                    try {
                        $whats = new \App\Services\WhatsAppService();
                        $whats->sendNotificacaoMed(
                            $resp['whatsapp'],
                            $valor,
                            $saldoResultante,
                            $resp['nome'] ?? ''
                        );
                    } catch (\Throwable $e) {
                        error_log('[WHATSAPP_MED_ERROR] ' . $e->getMessage());
                    }
                }
            }
        }

        // 4. Registro compulsório em logs_auditoria
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            self::execute(
                "INSERT INTO logs_auditoria (operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at)
                 VALUES (:operador_id, :acao, :tabela_afetada, :registro_id, :justificativa, :ip, NOW())",
                [
                    'operador_id'   => $operadorId,
                    'acao'          => 'estorno_med',
                    'tabela_afetada'=> 'transacoes_carteira',
                    'registro_id'   => $transacao['id'],
                    'justificativa' => $motivo,
                    'ip'            => $ip
                ]
            );
        } catch (\Throwable $e) {}

        // 5. Trilha em logs_seguranca
        try {
            $resp = Responsavel::buscarPorId($responsavelId);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            self::execute(
                "INSERT INTO logs_seguranca (evento, cpf_ou_usuario, detalhes_json, ip_origem, created_at)
                 VALUES (:evento, :cpf, :detalhes, :ip, NOW())",
                [
                    'evento'   => 'webhook_med_recebido',
                    'cpf'      => $resp['cpf'] ?? 'DESCONHECIDO',
                    'detalhes' => json_encode([
                        'responsavel_id'   => $responsavelId,
                        'identificador_med'=> $identificadorMed,
                        'valor'            => $valor,
                        'saldo_anterior'   => $transacao['saldo_anterior'],
                        'saldo_resultante' => $saldoResultante,
                        'bloqueado_med'    => $foiBloqueado,
                        'motivo'           => $motivo
                    ], JSON_UNESCAPED_UNICODE),
                    'ip'       => $ip
                ]
            );
        } catch (\Throwable $e) {}

        return array_merge($transacao, [
            'sucesso'       => true,
            'ja_processado' => false,
            'idempotente'   => false,
            'bloqueado_med' => $foiBloqueado
        ]);
    }

    /**
     * Processa a quitação de débito e regularização de uma família negativada (FSD Tela 7.3)
     */
    public static function processarQuitacaoMed(
        int $responsavelId,
        float $valor,
        string $formaPagamento = 'presencial',
        string $justificativa = 'Quitação presencial de débito MED',
        ?int $operadorId = null,
        bool $desbloquearSePositivo = true
    ): array {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('O valor da quitação deve ser maior que zero.');
        }

        $protocolo = 'QUIT-' . date('YmdHis') . '-' . $responsavelId;
        $descricao = sprintf(
            'Quitação de Débito MED / Regularização Presencial (%s) - Prot: %s. %s',
            strtoupper($formaPagamento),
            $protocolo,
            trim($justificativa)
        );

        $transacao = self::creditar(
            $responsavelId,
            $valor,
            self::TIPO_AJUSTE_MANUAL,
            $descricao,
            $protocolo,
            null,
            $operadorId
        );

        $saldoResultante = (float)$transacao['saldo_resultante'];
        $desbloqueado = false;

        // Se o saldo agora é não-negativo (>= 0), remove a suspensão MED
        if ($desbloquearSePositivo && $saldoResultante >= 0) {
            Responsavel::desbloquearMed(
                $responsavelId,
                $operadorId,
                "Quitação de débito via {$formaPagamento}. Protocolo {$protocolo}. " . $justificativa
            );
            $desbloqueado = true;

            // Dispara WhatsApp de confirmação de desbloqueio
            $resp = Responsavel::buscarPorId($responsavelId);
            if ($resp && !empty($resp['whatsapp'])) {
                try {
                    $whats = new \App\Services\WhatsAppService();
                    $whats->sendNotificacaoDesbloqueioMed(
                        $resp['whatsapp'],
                        $saldoResultante,
                        $resp['nome'] ?? ''
                    );
                } catch (\Throwable $e) {
                    error_log('[WHATSAPP_DESBLOQUEIO_ERROR] ' . $e->getMessage());
                }
            }
        }

        // Registro em auditoria
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            self::execute(
                "INSERT INTO logs_auditoria (operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at)
                 VALUES (:operador_id, :acao, :tabela, :registro_id, :justificativa, :ip, NOW())",
                [
                    'operador_id'   => $operadorId,
                    'acao'          => 'ajuste_saldo',
                    'tabela_afetada'=> 'transacoes_carteira',
                    'registro_id'   => $transacao['id'],
                    'justificativa' => "Quitação de débito de R$ " . number_format($valor, 2, ',', '.') . " via {$formaPagamento}. " . $justificativa,
                    'ip'            => $ip
                ]
            );
        } catch (\Throwable $e) {}

        return array_merge($transacao, [
            'sucesso'      => true,
            'desbloqueado' => $desbloqueado,
            'protocolo'    => $protocolo
        ]);
    }

    /**
     * Retorna o histórico de eventos e contestações MED
     */
    public static function listarHistoricoMed(array|int $filtros = [], int $limite = 50): array
    {
        if (is_int($filtros)) {
            $limite = $filtros;
            $filtros = [];
        }

        $where = ["(tc.tipo_transacao = 'debito_contestacao_med' OR (tc.tipo_transacao = 'ajuste_manual' AND tc.referencia_id LIKE 'QUIT-%'))"];
        $params = [];

        if (!empty($filtros['data_inicio'])) {
            $where[] = "DATE(tc.created_at) >= :data_inicio";
            $params['data_inicio'] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $where[] = "DATE(tc.created_at) <= :data_fim";
            $params['data_fim'] = $filtros['data_fim'];
        }
        if (!empty($filtros['responsavel_id'])) {
            $where[] = "tc.responsavel_id = :resp_id";
            $params['resp_id'] = (int)$filtros['responsavel_id'];
        }

        $whereSql = implode(' AND ', $where);
        $limite = max(1, min(200, $limite));

        $sql = "SELECT tc.*, 
                       tc.tipo_transacao AS tipo,
                       tc.descricao AS motivo,
                       tc.created_at AS data_hora,
                       r.nome AS titular,
                       r.nome AS responsavel_nome, 
                       r.cpf AS responsavel_cpf, 
                       r.whatsapp AS responsavel_whatsapp,
                       r.saldo_atual AS responsavel_saldo_atual,
                       r.bloqueado_med AS responsavel_bloqueado_med,
                       u.nome AS operador_nome
                FROM transacoes_carteira tc
                INNER JOIN responsaveis r ON r.id = tc.responsavel_id
                LEFT JOIN usuarios u ON u.id = tc.operador_id
                WHERE {$whereSql}
                ORDER BY tc.created_at DESC, tc.id DESC
                LIMIT {$limite}";

        return self::fetchAll($sql, $params);
    }
}

