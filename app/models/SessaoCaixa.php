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
     * @return array
     */
    public static function listarHistorico(?string $turno = null, ?string $dataInicio = null, ?string $dataFim = null, int $limite = 20): array
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

        return self::fetchAll($sql, $params);
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
     * Registra uma recarga presencial em dinheiro no balcão da cantina
     * Conforme Tela 5.4 e Área 6 do docs/FSD.md
     * 
     * @param int $responsavelId ID do titular da conta
     * @param float $valor Valor em espécie recebido
     * @param int $operadorId ID do operador logado
     * @param string|null $observacoes Observações opcionais
     * @param string $ipOrigem IP de origem do operador
     * @return array Dados da transação gerada
     * @throws \DomainException|\InvalidArgumentException
     */
    public static function registrarRecargaDinheiro(
        int $responsavelId,
        float $valor,
        int $operadorId,
        ?string $observacoes = null,
        string $ipOrigem = '127.0.0.1'
    ): array {
        if ($valor <= 0) {
            throw new \InvalidArgumentException('O valor da recarga deve ser maior que zero.');
        }

        $turno = self::inferirTurnoAtual();
        $sessaoAberta = self::obterSessaoAberta($turno);
        if (!$sessaoAberta) {
            // Tenta verificar se há outro turno aberto antes de recusar
            $sessaoAberta = self::obterSessaoAberta('matutino') ?? self::obterSessaoAberta('vespertino');
        }

        if (!$sessaoAberta) {
            throw new \DomainException('Operação bloqueada: Não há sessão de caixa aberta para o turno corrente. Abra o caixa antes de receber valores em dinheiro.');
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
            WHERE tc.tipo_transacao = 'recarga_dinheiro'
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
     * Busca os dados consolidados de uma recarga em dinheiro para emissão de comprovante térmico
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
            WHERE tc.id = :id AND tc.tipo_transacao = 'recarga_dinheiro'
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
}
