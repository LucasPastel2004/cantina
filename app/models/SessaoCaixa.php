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

    /**
     * Calcula os totais esperados para a sessão de caixa
     * 
     * @param array $sessao
     * @return array
     */
    public static function obterTotaisEsperados(array $sessao): array
    {
        $fundo = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
        $avulso = (float)($sessao['total_dinheiro_avulso'] ?? 0.0);
        $recargas = (float)($sessao['total_dinheiro_recargas'] ?? 0.0);
        $pix = (float)($sessao['total_pix_balcao'] ?? 0.0);
        $esperadoGaveta = round($fundo + $avulso + $recargas, 2);
        $totalFaturado = round($avulso + $pix, 2);

        return [
            'fundo_troco_inicial'     => $fundo,
            'total_dinheiro_avulso'   => $avulso,
            'total_dinheiro_recargas' => $recargas,
            'total_pix_balcao'        => $pix,
            'total_esperado_gaveta'   => $esperadoGaveta,
            'total_geral_faturado'    => $totalFaturado
        ];
    }

    /**
     * Encerra a sessão de caixa com conferência física do dinheiro contado na gaveta
     * 
     * @param int $sessaoId ID da sessão de caixa aberta
     * @param float $totalInformadoGaveta Valor físico em espécie contado pelo operador
     * @param string|null $observacoes Justificativa ou notas do fechamento
     * @param int|null $operadorId ID do operador autenticado que executa o fechamento
     * @param string $ipOrigem
     * @return array Dados atualizados da sessão fechada com quebra/sobra apurada
     * @throws \DomainException|\InvalidArgumentException
     */
    public static function fechar(
        int $sessaoId,
        float $totalInformadoGaveta,
        ?string $observacoes = null,
        ?int $operadorId = null,
        string $ipOrigem = '127.0.0.1'
    ): array {
        $sessao = self::buscarPorId($sessaoId);
        if (!$sessao) {
            throw new \DomainException("A sessão de caixa #{$sessaoId} não foi encontrada.");
        }

        if ($sessao['status'] !== self::STATUS_ABERTO) {
            throw new \DomainException("A sessão de caixa #{$sessaoId} já se encontra fechada desde " . date('d/m/Y H:i', strtotime($sessao['fechado_em'])));
        }

        if ($totalInformadoGaveta < 0) {
            throw new \InvalidArgumentException("O valor em dinheiro informado na gaveta não pode ser negativo.");
        }

        $totalInformado = round($totalInformadoGaveta, 2);
        $totais = self::obterTotaisEsperados($sessao);
        $totalEsperado = $totais['total_esperado_gaveta'];
        $diferenca = round($totalInformado - $totalEsperado, 2);

        $obsFechamento = trim((string)$observacoes);
        $novaObs = null;
        if (!empty($sessao['observacoes']) && !empty($obsFechamento)) {
            $novaObs = $sessao['observacoes'] . ' | Fechamento: ' . $obsFechamento;
        } elseif (!empty($obsFechamento)) {
            $novaObs = $obsFechamento;
        } else {
            $novaObs = $sessao['observacoes'] ?? null;
        }

        $pdo = self::getPdo();

        $stmt = $pdo->prepare("
            UPDATE sessoes_caixa
            SET total_informado_gaveta = :informado,
                diferenca_quebra_sobra = :diferenca,
                status = 'fechado',
                fechado_em = NOW(),
                observacoes = :obs
            WHERE id = :id AND status = 'aberto'
        ");

        $stmt->execute([
            'informado' => $totalInformado,
            'diferenca' => $diferenca,
            'obs'       => $novaObs,
            'id'        => $sessaoId
        ]);

        // Trilha de auditoria compulsória
        if ($operadorId !== null) {
            $difTexto = ($diferenca == 0.0) 
                ? 'Conferência exata' 
                : (($diferenca > 0) ? "Sobra de R$ {$diferenca}" : "Falta/Quebra de R$ " . abs($diferenca));
            
            $justificativa = "Fechamento de caixa #{$sessaoId} ({$sessao['turno']}): Esperado R$ {$totalEsperado}, Contado R$ {$totalInformado}. {$difTexto}.";
            if (!empty($obsFechamento)) {
                $justificativa .= " Obs: {$obsFechamento}";
            }

            Auditoria::registrar(
                $operadorId,
                Auditoria::ACAO_FECHAMENTO_CAIXA,
                'sessoes_caixa',
                $sessaoId,
                $justificativa,
                $ipOrigem
            );
        }

        return self::buscarPorId($sessaoId);
    }

    /**
     * Lista o histórico recente de sessões de caixa
     * 
     * @param int $limite
     * @return array
     */
    public static function listarHistorico(int $limite = 15): array
    {
        return self::fetchAll("
            SELECT sc.*, u.nome AS operador_nome, u.usuario AS operador_login
            FROM sessoes_caixa sc
            INNER JOIN usuarios u ON u.id = sc.operador_id
            ORDER BY sc.id DESC
            LIMIT :limite
        ", ['limite' => $limite]);
    }

    /**
     * Busca a última sessão de caixa de um determinado turno/data
     * 
     * @param string $turno
     * @param string|null $data YYYY-MM-DD (padrão hoje)
     * @return array|null
     */
    public static function obterUltimaSessao(string $turno, ?string $data = null): ?array
    {
        $data = $data ?? date('Y-m-d');

        return self::fetchOne("
            SELECT sc.*, u.nome AS operador_nome, u.usuario AS operador_login
            FROM sessoes_caixa sc
            INNER JOIN usuarios u ON u.id = sc.operador_id
            WHERE sc.turno = :turno
              AND DATE(sc.aberto_em) = :data
            ORDER BY sc.id DESC
            LIMIT 1
        ", [
            'turno' => $turno,
            'data'  => $data
        ]);
    }
}
