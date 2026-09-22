<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo: SessaoCaixa (Controle de Sessão e Gaveta de Caixa por Turno)
 * 
 * Conforme docs/FSD.md (Área 4, Área 6, Módulo 5, Módulo 7) e Migration 019
 */

namespace App\Models;

use App\Core\Model;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

class SessaoCaixa extends Model
{
    protected string $table = 'sessoes_caixa';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false; // Gerenciado por aberto_em / fechado_em
    protected bool $softDelete = false;

    public const STATUS_ABERTO  = 'aberto';
    public const STATUS_FECHADO = 'fechado';

    public const TURNO_MATUTINO   = 'matutino';
    public const TURNO_VESPERTINO = 'vespertino';

    /**
     * Obtém a sessão de caixa atualmente aberta para o turno ou para o momento atual
     * 
     * @param string|null $turno 'matutino', 'vespertino' ou null para inferir pelo horário atual
     * @return array|null Dados da sessão aberta ou null se fechada
     */
    public static function obterSessaoAberta(?string $turno = null): ?array
    {
        if ($turno === null) {
            $turno = self::inferirTurnoAtual();
        }

        $hoje = date('Y-m-d');

        return self::fetchOne("
            SELECT sc.*, u.nome AS operador_nome, u.usuario AS operador_login
            FROM sessoes_caixa sc
            INNER JOIN usuarios u ON u.id = sc.operador_id
            WHERE sc.status = 'aberto'
              AND sc.turno = :turno
              AND DATE(sc.aberto_em) = :hoje
            ORDER BY sc.id DESC
            LIMIT 1
        ", [
            'turno' => $turno,
            'hoje'  => $hoje
        ]);
    }

    /**
     * Verifica se há caixa aberto no turno especificado
     * 
     * @param string|null $turno
     * @return bool
     */
    public static function isCaixaAberto(?string $turno = null): bool
    {
        return self::obterSessaoAberta($turno) !== null;
    }

    /**
     * Abre uma nova sessão de caixa para o turno
     * 
     * @param int $operadorId ID do operador logado
     * @param string $turno 'matutino' ou 'vespertino'
     * @param float $fundoTrocoInicial Valor inicial em dinheiro na gaveta
     * @param string|null $observacoes Observações opcionais de abertura
     * @return int ID da nova sessão de caixa
     * @throws \DomainException|\InvalidArgumentException
     */
    public static function abrir(
        int $operadorId,
        string $turno,
        float $fundoTrocoInicial,
        ?string $observacoes = null
    ): int {
        if (!in_array($turno, [self::TURNO_MATUTINO, self::TURNO_VESPERTINO], true)) {
            throw new \InvalidArgumentException('Turno inválido para abertura de caixa.');
        }

        if ($fundoTrocoInicial < 0) {
            throw new \InvalidArgumentException('O fundo de troco inicial não pode ser negativo.');
        }

        $sessaoExistente = self::obterSessaoAberta($turno);
        if ($sessaoExistente) {
            throw new \DomainException(sprintf(
                'Já existe uma sessão de caixa aberta para o turno %s (Sessão #%d aberta por %s).',
                ucfirst($turno),
                $sessaoExistente['id'],
                $sessaoExistente['operador_nome']
            ));
        }

        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO sessoes_caixa (
                operador_id, turno, aberto_em, fundo_troco_inicial,
                total_dinheiro_recargas, total_dinheiro_avulso, total_pix_balcao,
                status, observacoes
            ) VALUES (
                :operador_id, :turno, NOW(), :fundo_troco,
                0.00, 0.00, 0.00,
                'aberto', :observacoes
            )
        ");

        $stmt->execute([
            'operador_id'   => $operadorId,
            'turno'         => $turno,
            'fundo_troco'   => round($fundoTrocoInicial, 2),
            'observacoes'   => !empty($observacoes) ? trim($observacoes) : null
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Incrementa os totalizadores financeiros da sessão de caixa (vendas avulsas, recargas e Pix balcão)
     * 
     * @param int $sessaoCaixaId
     * @param float $dinheiroAvulso
     * @param float $pixBalcao
     * @param float $dinheiroRecarga
     * @return void
     * @throws \DomainException
     */
    public static function incrementarTotais(
        int $sessaoCaixaId,
        float $dinheiroAvulso = 0.0,
        float $pixBalcao = 0.0,
        float $dinheiroRecarga = 0.0
    ): void {
        $dinheiroAvulso = max(0.0, round($dinheiroAvulso, 2));
        $pixBalcao = max(0.0, round($pixBalcao, 2));
        $dinheiroRecarga = max(0.0, round($dinheiroRecarga, 2));

        if ($dinheiroAvulso == 0.0 && $pixBalcao == 0.0 && $dinheiroRecarga == 0.0) {
            return;
        }

        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            UPDATE sessoes_caixa
            SET total_dinheiro_avulso   = total_dinheiro_avulso + :dinheiro_avulso,
                total_pix_balcao        = total_pix_balcao + :pix_balcao,
                total_dinheiro_recargas = total_dinheiro_recargas + :dinheiro_recargas
            WHERE id = :id AND status = 'aberto'
        ");

        $stmt->execute([
            'dinheiro_avulso'   => $dinheiroAvulso,
            'pix_balcao'        => $pixBalcao,
            'dinheiro_recargas' => $dinheiroRecarga,
            'id'                => $sessaoCaixaId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new \DomainException("A sessão de caixa #{$sessaoCaixaId} não foi localizada ou já está fechada.");
        }
    }

    /**
     * Busca os dados completos de uma sessão de caixa por ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function buscarPorId(int $id): ?array
    {
        return self::fetchOne("
            SELECT sc.*, u.nome AS operador_nome, u.usuario AS operador_login
            FROM sessoes_caixa sc
            INNER JOIN usuarios u ON u.id = sc.operador_id
            WHERE sc.id = :id
            LIMIT 1
        ", ['id' => $id]);
    }

    /**
     * Infere o turno operacional corrente pelo horário do relógio da cantina
     * 
     * @return string 'matutino' ou 'vespertino'
     */
    public static function inferirTurnoAtual(): string
    {
        $hora = (int)date('H');
        // Até às 12:00 considera matutino, a partir das 12:00 vespertino
        return ($hora < 12) ? self::TURNO_MATUTINO : self::TURNO_VESPERTINO;
    }
}
