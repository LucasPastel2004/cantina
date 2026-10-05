<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo: SessaoCaixa (Controle de Sessão e Gaveta de Caixa por Turno)
 * 
 * Conforme docs/FSD.md (Área 4, Área 6, Módulo 5, Módulo 7) e Migration 019
 */

namespace App\Models;

use App\Core\Model;
use App\Models\Auditoria;
use App\Models\TransacaoCarteira;
use App\Models\Responsavel;

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
     * Lista o histórico de sessões de caixa com suporte a filtros de turno e período
     * 
     * @param string|null $turno
     * @param string|null $dataInicio
     * @param string|null $dataFim
     * @param int $limite
     * @param int|null $offset
     * @return array
     */
    public static function listarHistorico(?string $turno = null, ?string $dataInicio = null, ?string $dataFim = null, int $limite = 20, ?int $offset = null): array
    {
        $sql = "
            SELECT sc.*, u.nome AS operador_nome, u.usuario AS operador_login
            FROM sessoes_caixa sc
            INNER JOIN usuarios u ON u.id = sc.operador_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($turno) && in_array($turno, [self::TURNO_MATUTINO, self::TURNO_VESPERTINO], true)) {
            $sql .= " AND sc.turno = :turno";
            $params['turno'] = $turno;
        }

        if (!empty($dataInicio)) {
            $sql .= " AND DATE(sc.aberto_em) >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $sql .= " AND DATE(sc.aberto_em) <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $sql .= " ORDER BY sc.id DESC LIMIT " . (int)$limite;
        if ($offset !== null && $offset >= 0) {
            $sql .= " OFFSET " . (int)$offset;
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Retorna o total de sessões de caixa no histórico para os filtros informados
     * 
     * @param string|null $turno
     * @param string|null $dataInicio
     * @param string|null $dataFim
     * @return int
     */
    public static function contarHistorico(?string $turno = null, ?string $dataInicio = null, ?string $dataFim = null): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM sessoes_caixa sc
            WHERE 1=1
        ";
        $params = [];

        if (!empty($turno) && in_array($turno, [self::TURNO_MATUTINO, self::TURNO_VESPERTINO], true)) {
            $sql .= " AND sc.turno = :turno";
            $params['turno'] = $turno;
        }

        if (!empty($dataInicio)) {
            $sql .= " AND DATE(sc.aberto_em) >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $sql .= " AND DATE(sc.aberto_em) <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $res = self::fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Retorna estatísticas consolidadas das sessões fechadas no período para a Stat-Strip
     * 
     * @param string|null $turno
     * @param string|null $dataInicio
     * @param string|null $dataFim
     * @return array
     */
    public static function obterEstatisticasPeriodo(?string $turno = null, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $sql = "
            SELECT 
                COUNT(*) AS total_sessoes,
                COALESCE(SUM(sc.total_dinheiro_recargas + sc.total_dinheiro_avulso), 0.00) AS faturamento_dinheiro,
                COALESCE(SUM(sc.total_pix_balcao), 0.00) AS faturamento_pix,
                COALESCE(SUM(sc.total_dinheiro_recargas + sc.total_dinheiro_avulso + sc.total_pix_balcao), 0.00) AS faturamento_total,
                COALESCE(SUM(sc.diferenca_quebra_sobra), 0.00) AS saldo_quebra_sobra,
                COUNT(CASE WHEN sc.diferenca_quebra_sobra > 0 THEN 1 END) AS total_com_sobra,
                COUNT(CASE WHEN sc.diferenca_quebra_sobra < 0 THEN 1 END) AS total_com_quebra,
                COUNT(CASE WHEN sc.status = 'fechado' THEN 1 END) AS sessoes_fechadas
            FROM sessoes_caixa sc
            WHERE 1=1
        ";
        $params = [];

        if (!empty($turno) && in_array($turno, [self::TURNO_MATUTINO, self::TURNO_VESPERTINO], true)) {
            $sql .= " AND sc.turno = :turno";
            $params['turno'] = $turno;
        }

        if (!empty($dataInicio)) {
            $sql .= " AND DATE(sc.aberto_em) >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $sql .= " AND DATE(sc.aberto_em) <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $res = self::fetchOne($sql, $params);

        return [
            'total_sessoes'        => (int)($res['total_sessoes'] ?? 0),
            'sessoes_fechadas'     => (int)($res['sessoes_fechadas'] ?? 0),
            'faturamento_dinheiro' => (float)($res['faturamento_dinheiro'] ?? 0.0),
            'faturamento_pix'      => (float)($res['faturamento_pix'] ?? 0.0),
            'faturamento_total'    => (float)($res['faturamento_total'] ?? 0.0),
            'saldo_quebra_sobra'   => (float)($res['saldo_quebra_sobra'] ?? 0.0),
            'total_com_sobra'      => (int)($res['total_com_sobra'] ?? 0),
            'total_com_quebra'     => (int)($res['total_com_quebra'] ?? 0)
        ];
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

    /**
     * Registra uma recarga presencial no balcão da cantina (Dinheiro ou Pix)
     * Conforme Tela 5.4 e Área 6 do docs/FSD.md
     * 
     * @param int $responsavelId ID do titular da conta
     * @param float $valor Valor recebido
     * @param string $formaPagamento 'dinheiro' ou 'pix'
     * @param int $operadorId ID do operador logado
     * @param string|null $observacoes Observações opcionais
     * @param string $ipOrigem IP de origem do operador
     * @return array Dados da transação gerada
     * @throws \DomainException|\InvalidArgumentException
     */
    public static function registrarRecargaBalcao(
        int $responsavelId,
        float $valor,
        string $formaPagamento,
        int $operadorId,
        ?string $observacoes = null,
        string $ipOrigem = '127.0.0.1'
    ): array {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('O valor da recarga deve ser maior que zero.');
        }

        $formaPagamento = (strtolower(trim($formaPagamento)) === 'pix') ? 'pix' : 'dinheiro';

        $turno = self::inferirTurnoAtual();
        $sessaoAberta = self::obterSessaoAberta($turno);
        if (!$sessaoAberta) {
            // Tenta verificar se há outro turno aberto antes de recusar
            $sessaoAberta = self::obterSessaoAberta('matutino') ?? self::obterSessaoAberta('vespertino');
        }

        if (!$sessaoAberta) {
            throw new \DomainException('Operação bloqueada: Não há sessão de caixa aberta para o turno corrente. Abra o caixa antes de efetuar recargas no balcão.');
        }

        $sessaoId = (int)$sessaoAberta['id'];
        $valor = round($valor, 2);

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            if ($formaPagamento === 'pix') {
                // 1. Incrementa total_pix_balcao na sessão de caixa
                self::incrementarTotais($sessaoId, 0.0, $valor, 0.0);

                // 2. Credita o saldo na carteira familiar via TransacaoCarteira
                $descricao = "Recarga Pix no Balcão (Sessão #{$sessaoId})";
                if (!empty($observacoes)) {
                    $descricao .= " - " . trim($observacoes);
                }

                $transacao = TransacaoCarteira::creditar(
                    $responsavelId,
                    $valor,
                    TransacaoCarteira::TIPO_RECARGA_PIX,
                    $descricao,
                    (string)$sessaoId,
                    null,
                    $operadorId
                );

                // 3. Auditoria de negócio
                $justificativa = "Recarga presencial via Pix no balcão no valor de R$ " . number_format($valor, 2, ',', '.') . " creditada na conta do responsável #{$responsavelId} (Sessão de Caixa #{$sessaoId}).";
                if (!empty($observacoes)) {
                    $justificativa .= " Obs: " . trim($observacoes);
                }

                Auditoria::registrar(
                    $operadorId,
                    Auditoria::ACAO_RECARGA_PIX_BALCAO,
                    'transacoes_carteira',
                    (int)$transacao['id'],
                    $justificativa,
                    $ipOrigem
                );
            } else {
                // 1. Incrementa total_dinheiro_recargas na sessão de caixa
                self::incrementarTotais($sessaoId, 0.0, 0.0, $valor);

                // 2. Credita o saldo na carteira familiar via TransacaoCarteira
                $descricao = "Recarga em Dinheiro no Balcão (Sessão #{$sessaoId})";
                if (!empty($observacoes)) {
                    $descricao .= " - " . trim($observacoes);
                }

                $transacao = TransacaoCarteira::creditar(
                    $responsavelId,
                    $valor,
                    TransacaoCarteira::TIPO_RECARGA_DINHEIRO,
                    $descricao,
                    (string)$sessaoId,
                    null,
                    $operadorId
                );

                // 3. Auditoria de negócio
                $justificativa = "Recarga presencial em dinheiro no valor de R$ " . number_format($valor, 2, ',', '.') . " creditada na conta do responsável #{$responsavelId} (Sessão de Caixa #{$sessaoId}).";
                if (!empty($observacoes)) {
                    $justificativa .= " Obs: " . trim($observacoes);
                }

                Auditoria::registrar(
                    $operadorId,
                    Auditoria::ACAO_RECARGA_DINHEIRO_BALCAO,
                    'transacoes_carteira',
                    (int)$transacao['id'],
                    $justificativa,
                    $ipOrigem
                );
            }

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            // Busca dados completos do responsável para retorno
            $resp = Responsavel::buscarPorId($responsavelId);

            return [
                'transacao_id'     => $transacao['id'],
                'sessao_caixa_id'  => $sessaoId,
                'responsavel_id'   => $responsavelId,
                'responsavel_nome' => $resp['nome'] ?? 'Responsável',
                'responsavel_cpf'  => $resp['cpf'] ?? '',
                'forma_pagamento'  => $formaPagamento,
                'tipo_transacao'   => ($formaPagamento === 'pix') ? TransacaoCarteira::TIPO_RECARGA_PIX : TransacaoCarteira::TIPO_RECARGA_DINHEIRO,
                'valor'            => $valor,
                'saldo_anterior'   => $transacao['saldo_anterior'],
                'saldo_resultante' => $transacao['saldo_resultante'],
                'created_at'       => $transacao['created_at'],
                'operador_id'      => $operadorId
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra uma recarga presencial em dinheiro no balcão da cantina (alias retrocompatível)
     * 
     * @param int $responsavelId ID do titular da conta
     * @param float $valor Valor em espécie recebido
     * @param int $operadorId ID do operador logado
     * @param string|null $observacoes Observações opcionais
     * @param string $ipOrigem IP de origem do operador
     * @return array Dados da transação gerada
     */
    public static function registrarRecargaDinheiro(
        int $responsavelId,
        float $valor,
        int $operadorId,
        ?string $observacoes = null,
        string $ipOrigem = '127.0.0.1'
    ): array {
        return self::registrarRecargaBalcao($responsavelId, $valor, 'dinheiro', $operadorId, $observacoes, $ipOrigem);
    }

    /**
     * Busca responsáveis para recarga presencial por CPF ou Nome do responsável,
     * ou por matrícula ou nome do aluno dependente
     * 
     * @param string $termo
     * @param int $limite
     * @return array
     */
    public static function buscarResponsavelParaRecarga(string $termo, int $limite = 10): array
    {
        $termoLimpo = trim($termo);
        if (strlen($termoLimpo) < 2) {
            return [];
        }

        $cpfNumerico = preg_replace('/\D/', '', $termoLimpo);
        $termoLike = '%' . $termoLimpo . '%';
        $cpfLike = !empty($cpfNumerico) ? '%' . $cpfNumerico . '%' : $termoLike;

        $palavras = preg_split('/\s+/', $termoLimpo, -1, PREG_SPLIT_NO_EMPTY);
        $pdo = self::getPdo();

        $params = [
            'cpf' => $cpfLike,
            'termo_exato_resp' => $termoLike,
            'termo_exato_aluno' => $termoLike,
            'matricula' => $termoLike,
        ];

        $whereClauses = [
            'r.cpf LIKE :cpf',
            'r.nome LIKE :termo_exato_resp',
            'a.nome LIKE :termo_exato_aluno',
            'a.matricula LIKE :matricula'
        ];

        // Se houver mais de uma palavra (ex: "Lucas Santos"), adiciona busca cruzada tolerante
        if (count($palavras) > 1 && count($palavras) <= 5) {
            $respWordClauses = [];
            $alunoWordClauses = [];
            foreach ($palavras as $idx => $p) {
                $pLike = '%' . $p . '%';
                $keyResp = 'r_w_' . $idx;
                $keyAluno = 'a_w_' . $idx;
                $respWordClauses[] = "r.nome LIKE :{$keyResp}";
                $alunoWordClauses[] = "a.nome LIKE :{$keyAluno}";
                $params[$keyResp] = $pLike;
                $params[$keyAluno] = $pLike;
            }
            $whereClauses[] = '(' . implode(' AND ', $respWordClauses) . ')';
            $whereClauses[] = '(' . implode(' AND ', $alunoWordClauses) . ')';
        }

        $sqlWhere = implode(' OR ', $whereClauses);

        $sql = "
            SELECT DISTINCT r.id, r.cpf, r.nome, r.whatsapp, r.saldo_atual, r.bloqueado_med
            FROM responsaveis r
            LEFT JOIN alunos a ON a.responsavel_id = r.id AND a.deleted_at IS NULL
            WHERE {$sqlWhere}
            ORDER BY r.nome ASC
            LIMIT " . (int)$limite;

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, \PDO::PARAM_STR);
        }
        $stmt->execute();

        $responsaveis = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Agrega dependentes para cada responsável localizado
        foreach ($responsaveis as &$resp) {
            $stmtAlunos = $pdo->prepare("
                SELECT a.id, a.nome, a.matricula, t.nome_turma, a.turno
                FROM alunos a
                LEFT JOIN turmas t ON t.id = a.turma_id
                WHERE a.responsavel_id = :resp_id AND a.deleted_at IS NULL
                ORDER BY a.nome ASC
            ");
            $stmtAlunos->execute(['resp_id' => $resp['id']]);
            $resp['dependentes'] = $stmtAlunos->fetchAll(\PDO::FETCH_ASSOC);

            // Gera string amigável de dependentes para exibição rápida
            $nomesAlunos = array_map(function($a) {
                return $a['nome'] . (!empty($a['nome_turma']) ? ' (' . $a['nome_turma'] . ')' : '');
            }, $resp['dependentes']);
            $resp['dependentes_str'] = !empty($nomesAlunos) ? implode(', ', $nomesAlunos) : 'Nenhum aluno vinculado';

            // Formatação padrão de CPF
            $cpfLimpo = preg_replace('/\D/', '', (string)($resp['cpf'] ?? ''));
            $resp['cpf_formatado'] = (strlen($cpfLimpo) === 11) 
                ? substr($cpfLimpo, 0, 3) . '.' . substr($cpfLimpo, 3, 3) . '.' . substr($cpfLimpo, 6, 3) . '-' . substr($cpfLimpo, 9, 2)
                : $resp['cpf'];
        }

        return $responsaveis;
    }

    /**
     * Retorna o extrato analítico discriminando todas as vendas, recargas e entradas da sessão
     * 
     * @param int $sessaoId
     * @return array
     */
    public static function obterExtratoAnaliticoSessao(int $sessaoId): array
    {
        $sessao = self::buscarPorId($sessaoId);
        if (!$sessao) {
            return [];
        }

        $pdo = self::getPdo();

        // 1. Vendas no balcão da sessão
        $stmtVendas = $pdo->prepare("
            SELECT vb.*, u.nome AS operador_venda_nome, c.nome AS colaborador_nome, c.matricula_funcional AS colaborador_matricula
            FROM vendas_balcao vb
            LEFT JOIN usuarios u ON u.id = vb.operador_id
            LEFT JOIN colaboradores c ON c.id = vb.colaborador_id
            WHERE vb.sessao_caixa_id = :sessao_id
            ORDER BY vb.id DESC
        ");
        $stmtVendas->execute(['sessao_id' => $sessaoId]);
        $vendas = $stmtVendas->fetchAll(\PDO::FETCH_ASSOC);

        // Itens de cada venda
        foreach ($vendas as &$venda) {
            $stmtItens = $pdo->prepare("
                SELECT ivb.*, p.descricao AS produto_descricao, p.codigo_interno
                FROM itens_venda_balcao ivb
                INNER JOIN produtos p ON p.id = ivb.produto_id
                WHERE ivb.venda_balcao_id = :venda_id
            ");
            $stmtItens->execute(['venda_id' => $venda['id']]);
            $venda['itens'] = $stmtItens->fetchAll(\PDO::FETCH_ASSOC);
        }

        // 2. Recargas presenciais em dinheiro vinculadas à sessão
        $stmtRecargas = $pdo->prepare("
            SELECT tc.*, r.nome AS responsavel_nome, r.cpf AS responsavel_cpf, u.nome AS operador_nome
            FROM transacoes_carteira tc
            INNER JOIN responsaveis r ON r.id = tc.responsavel_id
            LEFT JOIN usuarios u ON u.id = tc.operador_id
            WHERE tc.tipo_transacao IN ('recarga_dinheiro', 'recarga_pix')
              AND tc.referencia_id = :sessao_id
            ORDER BY tc.id DESC
        ");
        $stmtRecargas->execute(['sessao_id' => (string)$sessaoId]);
        $recargas = $stmtRecargas->fetchAll(\PDO::FETCH_ASSOC);

        $totais = self::obterTotaisEsperados($sessao);

        return [
            'sessao'    => $sessao,
            'totais'    => $totais,
            'vendas'    => $vendas,
            'recargas'  => $recargas
        ];
    }

    /**
     * Busca os dados consolidados de uma recarga (dinheiro ou pix no balcão) para emissão de comprovante térmico
     * 
     * @param int $transacaoId
     * @return array|null
     */
    public static function buscarDadosRecarga(int $transacaoId): ?array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            SELECT tc.*, 
                   r.nome AS responsavel_nome, 
                   r.cpf AS responsavel_cpf,
                   u.nome AS operador_nome,
                   sc.turno AS sessao_turno
            FROM transacoes_carteira tc
            INNER JOIN responsaveis r ON r.id = tc.responsavel_id
            LEFT JOIN usuarios u ON u.id = tc.operador_id
            LEFT JOIN sessoes_caixa sc ON sc.id = CAST(tc.referencia_id AS UNSIGNED)
            WHERE tc.id = :id AND tc.tipo_transacao IN ('recarga_dinheiro', 'recarga_pix')
            LIMIT 1
        ");
        $stmt->execute(['id' => $transacaoId]);
        $dados = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$dados) {
            return null;
        }

        // Dependentes do responsável
        $stmtAlunos = $pdo->prepare("
            SELECT a.nome, t.nome_turma 
            FROM alunos a 
            LEFT JOIN turmas t ON t.id = a.turma_id 
            WHERE a.responsavel_id = :resp_id AND a.deleted_at IS NULL
            ORDER BY a.nome ASC
        ");
        $stmtAlunos->execute(['resp_id' => $dados['responsavel_id']]);
        $dados['dependentes'] = $stmtAlunos->fetchAll(\PDO::FETCH_ASSOC);

        return $dados;
    }

    /**
     * Retorna a estrutura oficial completa do Fechamento de Caixa A4
     * com as 4 seções escolares (Duplicatas, Vendas com Online vs Balcão e funcionários unificados,
     * Recebimento Pix de conferência bancária e Resumo Contábil do Caixa)
     * Conforme docs/alterações sistema guideline/28 setembro matutino.pdf e 28 setembro vespertino.pdf
     * 
     * @param int|array $sessaoIdOuArray
     * @return array
     */
    public static function obterDadosFechamentoOficial(int|array $sessaoIdOuArray): array
    {
        if (is_array($sessaoIdOuArray)) {
            $sessao = $sessaoIdOuArray;
            $sessaoId = (int)($sessao['id'] ?? 0);
        } else {
            $sessaoId = (int)$sessaoIdOuArray;
            $sessao = self::buscarPorId($sessaoId) ?? [];
        }

        if (empty($sessao)) {
            return [];
        }

        $pdo = self::getPdo();
        $abertoEm = $sessao['aberto_em'] ?? date('Y-m-d 07:00:00');
        $fechadoEm = $sessao['fechado_em'] ?? date('Y-m-d H:i:s');
        $turno = strtolower($sessao['turno'] ?? 'matutino');
        $dataRef = date('Y-m-d', strtotime($abertoEm));

        // 1. DUPLICATAS / RECARGAS
        $duplicatas = [];
        $subtotalDuplicatas = 0.0;

        $stmtDup = $pdo->prepare("
            SELECT tc.*, r.nome AS responsavel_nome, r.cpf AS responsavel_cpf
            FROM transacoes_carteira tc
            INNER JOIN responsaveis r ON r.id = tc.responsavel_id
            WHERE (
                (tc.tipo_transacao = 'recarga_dinheiro' AND tc.referencia_id = :sessao_id)
                OR (tc.tipo_transacao = 'recarga_pix' AND tc.created_at >= :aberto_em AND tc.created_at <= :fechado_em)
            )
            ORDER BY tc.id ASC
        ");
        $stmtDup->execute([
            'sessao_id'  => (string)$sessaoId,
            'aberto_em'  => $abertoEm,
            'fechado_em' => $fechadoEm
        ]);
        $recargasRaw = $stmtDup->fetchAll(\PDO::FETCH_ASSOC);

        $seqDup = 231917;
        foreach ($recargasRaw as $rec) {
            $vlr = (float)$rec['valor'];
            $subtotalDuplicatas += $vlr;

            $stmtAluno = $pdo->prepare("
                SELECT matricula, nome FROM alunos WHERE responsavel_id = :resp_id AND deleted_at IS NULL ORDER BY id ASC LIMIT 1
            ");
            $stmtAluno->execute(['resp_id' => $rec['responsavel_id']]);
            $aluno = $stmtAluno->fetch(\PDO::FETCH_ASSOC);

            $alunoStr = $aluno 
                ? "{$aluno['matricula']} - {$aluno['nome']}" 
                : ($rec['responsavel_nome'] ?? 'Responsável');

            $duplicatas[] = [
                'numero'         => (string)($seqDup++),
                'aluno'          => $alunoStr,
                'referencia'     => date('m/Y', strtotime($rec['created_at'])),
                'finalidade'     => 'CANTINA',
                'vencimento'     => date('d/m/Y', strtotime($rec['created_at'])),
                'valor_nominal'  => $vlr,
                'multa'          => 0.0,
                'juros'          => 0.0,
                'desconto'       => 0.0,
                'saldo_devedor'  => 0.0,
                'valor_recebido' => $vlr,
                'tipo'           => ($rec['tipo_transacao'] === 'recarga_pix') ? 'Pix' : 'Dinheiro'
            ];
        }

        // 2. VENDAS (CANTINA WEB ONLINE + BALCÃO PRESENCIAL COM SEGREGAÇÃO E FUNCIONÁRIOS UNIFICADOS)
        $vendasItens = [];
        $vendasBalcao = [];
        $vendasOnline = [];
        $subtotalBalcao = 0.0;
        $subtotalOnline = 0.0;
        $subtotalVendas = 0.0;

        $stmtVb = $pdo->prepare("
            SELECT vb.id AS venda_id, vb.tipo_venda, vb.forma_pagamento, vb.colaborador_id, vb.aluno_id,
                   c.nome AS colaborador_nome, c.cargo AS colaborador_cargo,
                   a.nome AS aluno_nome, a.matricula AS aluno_matricula,
                   CONCAT(t.serie, ' ', t.nome_turma) AS turma_nome,
                   ivb.produto_id, ivb.quantidade, ivb.preco_unitario, ivb.subtotal,
                   p.descricao AS produto_descricao
            FROM vendas_balcao vb
            INNER JOIN itens_venda_balcao ivb ON ivb.venda_balcao_id = vb.id
            INNER JOIN produtos p ON p.id = ivb.produto_id
            LEFT JOIN colaboradores c ON c.id = vb.colaborador_id
            LEFT JOIN alunos a ON a.id = vb.aluno_id
            LEFT JOIN turmas t ON t.id = a.turma_id
            WHERE vb.sessao_caixa_id = :sessao_id AND vb.status = 'concluida'
            ORDER BY vb.id ASC, ivb.id ASC
        ");
        $stmtVb->execute(['sessao_id' => $sessaoId]);
        $itensBalcao = $stmtVb->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($itensBalcao as $ib) {
            $sub = (float)$ib['subtotal'];
            $subtotalBalcao += $sub;
            $subtotalVendas += $sub;

            $pessoa = 'Balcão (Presencial)';
            if (!empty($ib['aluno_nome'])) {
                $turmaStr = !empty($ib['turma_nome']) ? " ({$ib['turma_nome']})" : '';
                $pessoa = "{$ib['aluno_nome']}{$turmaStr}";
            } elseif (!empty($ib['colaborador_nome'])) {
                $cargo = !empty($ib['colaborador_cargo']) ? $ib['colaborador_cargo'] : 'Colaborador';
                $pessoa = "{$ib['colaborador_nome']} - {$cargo}";
            }

            $tipoStr = 'Dinheiro';
            if ($ib['forma_pagamento'] === 'pix' || $ib['forma_pagamento'] === 'pix_balcao') {
                $tipoStr = 'Pix';
            } elseif ($ib['forma_pagamento'] === 'saldo') {
                $tipoStr = 'Saldo';
            } elseif ($ib['tipo_venda'] === 'desconto_folha' || $ib['forma_pagamento'] === 'desconto_folha') {
                $tipoStr = 'Folha';
            } elseif ($ib['forma_pagamento'] === 'cartao_debito') {
                $tipoStr = 'Débito';
            } elseif ($ib['forma_pagamento'] === 'cartao_credito') {
                $tipoStr = 'Crédito';
            }

            $itemBalcao = [
                'origem'         => 'balcao',
                'alimento'       => $ib['produto_descricao'],
                'pessoa'         => $pessoa,
                'preco_unitario' => (float)$ib['preco_unitario'],
                'quantidade'     => (int)$ib['quantidade'],
                'valor_pago'     => $sub,
                'tipo'           => $tipoStr
            ];

            $vendasBalcao[] = $itemBalcao;
            $vendasItens[] = $itemBalcao;
        }

        // Encomendas online (Cantina Web)
        $stmtOnline = $pdo->prepare("
            SELECT p.id AS pedido_id, p.data_consumo, p.turno_consumo,
                   a.nome AS aluno_nome, a.matricula AS aluno_matricula,
                   CONCAT(t.serie, ' ', t.nome_turma) AS turma_nome,
                   ip.produto_id, ip.quantidade, ip.preco_unitario, ip.subtotal,
                   pr.descricao AS produto_descricao
            FROM pedidos p
            INNER JOIN alunos a ON a.id = p.aluno_id
            LEFT JOIN turmas t ON t.id = a.turma_id
            INNER JOIN itens_pedido ip ON ip.pedido_id = p.id
            INNER JOIN produtos pr ON pr.id = ip.produto_id
            WHERE p.data_consumo = :data_consumo
              AND p.turno_consumo = :turno
              AND p.status IN ('agendado', 'editado', 'entregue')
            ORDER BY a.nome ASC, ip.id ASC
        ");
        $stmtOnline->execute([
            'data_consumo' => $dataRef,
            'turno'        => $turno
        ]);
        $itensOnline = $stmtOnline->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($itensOnline as $io) {
            $sub = (float)$io['subtotal'];
            $subtotalOnline += $sub;
            $subtotalVendas += $sub;

            $turmaStr = !empty($io['turma_nome']) ? " ({$io['turma_nome']})" : '';

            $itemOnline = [
                'origem'         => 'online',
                'alimento'       => $io['produto_descricao'],
                'pessoa'         => "{$io['aluno_nome']}{$turmaStr}",
                'preco_unitario' => (float)$io['preco_unitario'],
                'quantidade'     => (int)$io['quantidade'],
                'valor_pago'     => $sub,
                'tipo'           => 'Saldo'
            ];

            $vendasOnline[] = $itemOnline;
            $vendasItens[] = $itemOnline;
        }

        // 3. RECEBIMENTO EM PIX
        $pixRecebimentos = [];
        $totalPixRecebimentos = 0.0;

        foreach ($recargasRaw as $rec) {
            if ($rec['tipo_transacao'] === 'recarga_pix') {
                $val = (float)$rec['valor'];
                $totalPixRecebimentos += $val;
                $pixRecebimentos[] = [
                    'banco'      => 'NUBANK - CANTINA',
                    'descricao'  => 'Credito transferência pix via pix',
                    'observacao' => 'via pix',
                    'valor'      => $val
                ];
            }
        }

        $stmtPixBalcao = $pdo->prepare("
            SELECT valor_total FROM vendas_balcao
            WHERE sessao_caixa_id = :sessao_id AND forma_pagamento IN ('pix', 'pix_balcao') AND status = 'concluida'
        ");
        $stmtPixBalcao->execute(['sessao_id' => $sessaoId]);
        $totalPixBalcaoSoma = 0.0;
        while ($row = $stmtPixBalcao->fetch(\PDO::FETCH_ASSOC)) {
            $val = (float)$row['valor_total'];
            $totalPixBalcaoSoma += $val;
            $totalPixRecebimentos += $val;
            $pixRecebimentos[] = [
                'banco'      => 'NUBANK - CANTINA',
                'descricao'  => 'Credito transferência pix',
                'observacao' => 'venda balcão',
                'valor'      => $val
            ];
        }

        // 4. RESUMO DO CAIXA
        $fundoTroco = (float)($sessao['fundo_troco_inicial'] ?? 0.0);
        $dinheiroRecargas = (float)($sessao['total_dinheiro_recargas'] ?? 0.0);
        $dinheiroAvulso = (float)($sessao['total_dinheiro_avulso'] ?? 0.0);

        // Garante que o dinheiro de vendas avulsas reflita a soma real
        $stmtDinheiroBalcao = $pdo->prepare("
            SELECT COALESCE(SUM(valor_total), 0.00) FROM vendas_balcao
            WHERE sessao_caixa_id = :sessao_id AND forma_pagamento = 'dinheiro' AND status = 'concluida'
        ");
        $stmtDinheiroBalcao->execute(['sessao_id' => $sessaoId]);
        $dinheiroBalcaoReal = (float)$stmtDinheiroBalcao->fetchColumn();
        if ($dinheiroBalcaoReal > $dinheiroAvulso) {
            $dinheiroAvulso = $dinheiroBalcaoReal;
        }

        $totalDinheiroEntradas = $dinheiroRecargas + $dinheiroAvulso;
        $totalPixEntradas = max((float)($sessao['total_pix_balcao'] ?? 0.0), $totalPixBalcaoSoma);
        
        $totalEntradas = $totalDinheiroEntradas + $totalPixEntradas;
        $saldoCaixaDinheiro = $fundoTroco + $totalDinheiroEntradas;
        $saldoCaixaPix = $totalPixEntradas;
        $saldoCaixaTotal = $saldoCaixaDinheiro + $saldoCaixaPix;

        $resumoCaixa = [
            'fundo' => [
                'dinheiro' => $fundoTroco,
                'pix'      => 0.0,
                'total'    => $fundoTroco
            ],
            'entradas' => [
                'dinheiro' => $totalDinheiroEntradas,
                'pix'      => $totalPixEntradas,
                'total'    => $totalEntradas
            ],
            'saidas' => [
                'dinheiro' => 0.0,
                'pix'      => 0.0,
                'total'    => 0.0
            ],
            'saldo' => [
                'dinheiro' => $saldoCaixaDinheiro,
                'pix'      => $saldoCaixaPix,
                'total'    => $saldoCaixaTotal
            ],
            'fundo_caixa'    => $fundoTroco,
            'total_entradas' => $totalEntradas,
            'total_saidas'   => 0.0,
            'saldo_atual'    => $saldoCaixaTotal
        ];

        return [
            'sessao'                 => $sessao,
            'duplicatas'             => $duplicatas,
            'subtotal_duplicatas'    => $subtotalDuplicatas,
            'vendas'                 => $vendasItens,
            'vendas_cantina_web'     => $vendasOnline,
            'subtotal_cantina_web'   => $subtotalOnline,
            'vendas_balcao'          => $vendasBalcao,
            'subtotal_balcao'        => $subtotalBalcao,
            'subtotal_vendas'        => $subtotalVendas,
            'pix_recebimentos'       => $pixRecebimentos,
            'total_pix_recebimentos' => $totalPixRecebimentos,
            'resumo_caixa'           => $resumoCaixa,
            'resumo'                 => $resumoCaixa
        ];
    }
}

