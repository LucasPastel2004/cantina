<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo: Auditoria (Trilha de Negócio em logs_auditoria)
 * 
 * Conforme docs/FSD.md (Seção 9.2, Seção 11.24) e Migration 024
 */

namespace App\Models;

use App\Core\Model;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class Auditoria extends Model
{
    protected string $table = 'logs_auditoria';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Ações padronizadas de auditoria de negócio
     */
    public const ACAO_ESTORNO_BALCAO        = 'estorno_balcao';
    public const ACAO_REIMPRESSAO_TICKET    = 'reimpressao_ticket';
    public const ACAO_BAIXA_PERDA           = 'baixa_perda';
    public const ACAO_AJUSTE_SALDO          = 'ajuste_saldo';
    public const ACAO_RECEBIMENTO_FORNECEDOR = 'recebimento_fornecedor';
    public const ACAO_CANCELAMENTO_LETIVO   = 'cancelamento_letivo_lote';
    public const ACAO_ABERTURA_CAIXA        = 'abertura_caixa';
    public const ACAO_FECHAMENTO_CAIXA      = 'fechamento_caixa';
    public const ACAO_RECARGA_DINHEIRO_BALCAO = 'recarga_dinheiro_balcao';

    /**
     * Registra uma ação na trilha de auditoria
     * 
     * @param int $operadorId ID do operador responsável (tabela usuarios)
     * @param string $acao Nome da ação padronizada
     * @param string $tabelaAfetada Nome da tabela relacionada
     * @param int $registroId ID do registro afetado
     * @param string $justificativa Justificativa textual da operação
     * @param string $ipOrigem Endereço IP do operador
     * @return int ID do registro de auditoria gerado
     * @throws \InvalidArgumentException|\PDOException
     */
    public static function registrar(
        int $operadorId,
        string $acao,
        string $tabelaAfetada,
        int $registroId,
        string $justificativa,
        string $ipOrigem = '127.0.0.1'
    ): int {
        $justificativaLimpa = trim($justificativa);
        if (empty($justificativaLimpa)) {
            throw new \InvalidArgumentException('A justificativa é obrigatória para o registro de auditoria.');
        }

        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO logs_auditoria (
                operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at
            ) VALUES (
                :operador_id, :acao, :tabela_afetada, :registro_id, :justificativa, :ip_origem, NOW()
            )
        ");

        $stmt->execute([
            'operador_id'    => $operadorId,
            'acao'           => $acao,
            'tabela_afetada' => $tabelaAfetada,
            'registro_id'    => $registroId,
            'justificativa'  => mb_substr($justificativaLimpa, 0, 255),
            'ip_origem'      => mb_substr($ipOrigem, 0, 45),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Lista os registros de auditoria vinculados a uma entidade específica
     * 
     * @param string $tabelaAfetada
     * @param int $registroId
     * @return array
     */
    public static function listarPorRegistro(string $tabelaAfetada, int $registroId): array
    {
        return self::fetchAll("
            SELECT la.*, u.nome AS operador_nome, u.usuario AS operador_login, u.perfil AS operador_perfil
            FROM logs_auditoria la
            LEFT JOIN usuarios u ON u.id = la.operador_id
            WHERE la.tabela_afetada = :tabela AND la.registro_id = :registro_id
            ORDER BY la.created_at DESC
        ", [
            'tabela'      => $tabelaAfetada,
            'registro_id' => $registroId
        ]);
    }
}
