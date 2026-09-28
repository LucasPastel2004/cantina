<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Domínio: Calendário Escolar e Janelas de Agendamento
 * 
 * Responsável por determinar dias letivos, feriados, recessos,
 * janelas de abertura semanal (sextas-feiras às 18:00) e datas disponíveis.
 * 
 * Conforme docs/FSD.md (Área 1, Área 7, RN-01 e RN-06)
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class CalendarioEscolar extends Model
{
    protected string $table = 'calendario_letivo';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    public const TIPO_LETIVO                = 'letivo';
    public const TIPO_FERIADO               = 'feriado';
    public const TIPO_RECESSO               = 'recesso';
    public const TIPO_FERIAS                = 'ferias';
    public const TIPO_CANCELADO_EMERGENCIAL = 'cancelado_emergencial';

    /**
     * Nomes dos dias da semana em português
     */
    public const DIAS_SEMANA = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        7 => 'Domingo',
    ];

    public const DIAS_SEMANA_ABREV = [
        1 => 'Seg',
        2 => 'Ter',
        3 => 'Qua',
        4 => 'Qui',
        5 => 'Sex',
        6 => 'Sáb',
        7 => 'Dom',
    ];

    /**
     * Verifica se uma data específica é um dia letivo no colégio
     * 
     * @param string $data Formato YYYY-MM-DD
     * @return bool True se letivo, False se sábado, domingo, feriado ou recesso
     */
    public static function isDiaLetivo(string $data): bool
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $data);
        if (!$dt) {
            return false;
        }

        $diaSemana = (int)$dt->format('N'); // 1 (segunda) a 7 (domingo)

        // Sábados e domingos nunca são dias letivos regulares
        if ($diaSemana >= 6) {
            return false;
        }

        // Consulta registro na tabela calendario_letivo
        $registro = self::fetchOne(
            "SELECT tipo FROM calendario_letivo WHERE data = :data LIMIT 1",
            ['data' => $data]
        );

        if ($registro) {
            return ($registro['tipo'] === self::TIPO_LETIVO);
        }

        // Se não houver registro específico e for de segunda a sexta, é dia letivo padrão
        return true;
    }

    /**
     * Obtém informações detalhadas sobre uma data
     * 
     * @param string $data Formato YYYY-MM-DD
     * @return array
     */
    public static function obterInfoData(string $data): array
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $data);
        if (!$dt) {
            return [
                'data'            => $data,
                'is_letivo'       => false,
                'tipo'            => 'invalido',
                'descricao'       => 'Data inválida',
                'dia_semana'      => 0,
                'dia_semana_nome' => '',
            ];
        }

        $diaSemana = (int)$dt->format('N');
        $nomeSemana = self::DIAS_SEMANA[$diaSemana] ?? '';

        if ($diaSemana >= 6) {
            return [
                'data'            => $data,
                'is_letivo'       => false,
                'tipo'            => 'fim_de_semana',
                'descricao'       => $nomeSemana,
                'dia_semana'      => $diaSemana,
                'dia_semana_nome' => $nomeSemana,
            ];
        }

        $registro = self::fetchOne(
            "SELECT tipo, descricao FROM calendario_letivo WHERE data = :data LIMIT 1",
            ['data' => $data]
        );

        if ($registro) {
            return [
                'data'            => $data,
                'is_letivo'       => ($registro['tipo'] === self::TIPO_LETIVO),
                'tipo'            => $registro['tipo'],
                'descricao'       => $registro['descricao'],
                'dia_semana'      => $diaSemana,
                'dia_semana_nome' => $nomeSemana,
            ];
        }

        return [
            'data'            => $data,
            'is_letivo'       => true,
            'tipo'            => self::TIPO_LETIVO,
            'descricao'       => 'Dia Letivo Regular',
            'dia_semana'      => $diaSemana,
            'dia_semana_nome' => $nomeSemana,
        ];
    }

    /**
     * Verifica se a semana seguinte já está liberada para agendamento
     * Regra RN-01: Abre toda sexta-feira às 18:00
     * 
     * @param \DateTimeInterface|null $agora
     * @return bool
     */
    public static function isSemanaSeguinteLiberada(?\DateTimeInterface $agora = null): bool
    {
        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();
        $diaSemana = (int)$now->format('N'); // 1 a 7
        $horaMinuto = $now->format('H:i');

        // Se for sexta-feira (5) a partir das 18:00, ou sábado (6), ou domingo (7)
        if ($diaSemana === 5 && $horaMinuto >= '18:00') {
            return true;
        }

        if ($diaSemana >= 6) {
            return true;
        }

        return false;
    }

    /**
     * Retorna a lista de 5 dias úteis (Segunda a Sexta) de uma semana
     * 
     * @param string $tipoSemana 'atual' ou 'seguinte'
     * @param \DateTimeInterface|null $agora
     * @param string|null $dataBase Data de referência no formato YYYY-MM-DD (para navegação)
     * @return array Lista dos 5 dias formatados com metadados
     */
    public static function obterDiasSemanaLetiva(string $tipoSemana = 'atual', ?\DateTimeInterface $agora = null, ?string $dataBase = null): array
    {
        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();

        if ($dataBase !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataBase)) {
            $dtRef = \DateTimeImmutable::createFromFormat('Y-m-d', $dataBase);
            if ($dtRef) {
                $diaSemanaRef = (int)$dtRef->format('N');
                $diasDesdeSegunda = ($diaSemanaRef - 1);
                $segunda = $dtRef->modify("-{$diasDesdeSegunda} days");
            } else {
                $diaSemana = (int)$now->format('N');
                $diasDesdeSegunda = ($diaSemana - 1);
                $segunda = $now->modify("-{$diasDesdeSegunda} days");
            }
        } else {
            $diaSemana = (int)$now->format('N'); // 1 a 7

            // Determina a Segunda-feira da semana de referência
            if ($tipoSemana === 'seguinte') {
                // Próxima segunda-feira
                $diasAteProximaSegunda = (8 - $diaSemana);
                $segunda = $now->modify("+{$diasAteProximaSegunda} days");
            } else {
                // Segunda-feira da semana corrente
                $diasDesdeSegunda = ($diaSemana - 1);
                $segunda = $now->modify("-{$diasDesdeSegunda} days");
            }
        }

        $dias = [];
        for ($i = 0; $i < 5; $i++) {
            $diaDt = $segunda->modify("+{$i} days");
            $dataIso = $diaDt->format('Y-m-d');
            $info = self::obterInfoData($dataIso);

            $dias[] = [
                'data'            => $dataIso,
                'data_formatada'  => $diaDt->format('d/m/Y'),
                'dia_mes'         => $diaDt->format('d/m'),
                'dia_numero'      => $diaDt->format('d'),
                'dia_semana'      => (int)$diaDt->format('N'),
                'dia_semana_nome' => self::DIAS_SEMANA[(int)$diaDt->format('N')],
                'dia_semana_abrev'=> self::DIAS_SEMANA_ABREV[(int)$diaDt->format('N')],
                'is_letivo'       => $info['is_letivo'],
                'tipo'            => $info['tipo'],
                'descricao'       => $info['descricao'],
                'is_hoje'         => ($dataIso === $now->format('Y-m-d')),
                'is_passado'      => ($dataIso < $now->format('Y-m-d')),
            ];
        }

        return $dias;
    }

    /**
     * Lista todas as exceções cadastradas (feriados, recessos, férias, cancelamentos)
     * 
     * @param int|null $ano Ano para filtro (ex: 2026)
     * @return array
     */
    public static function listarExcecoes(?int $ano = null): array
    {
        $pdo = self::getPdo();
        $sql = "SELECT cl.*, u.nome as cancelador_nome 
                FROM calendario_letivo cl 
                LEFT JOIN usuarios u ON u.id = cl.cancelado_por ";
        $params = [];
        if ($ano !== null) {
            $sql .= "WHERE YEAR(cl.data) = :ano ";
            $params['ano'] = $ano;
        }
        $sql .= "ORDER BY cl.data ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Cadastra ou atualiza uma exceção no calendário letivo
     * 
     * @param string $data Data no formato YYYY-MM-DD
     * @param string $tipo 'letivo', 'feriado', 'recesso', 'ferias', 'cancelado_emergencial'
     * @param string $descricao Descrição da data (ex: 'Feriado de Tiradentes')
     * @param int|null $usuarioId ID do operador/administrador
     * @return array
     */
    public static function salvarExcecao(string $data, string $tipo, string $descricao, ?int $usuarioId = null): array
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $data);
        if (!$dt) {
            throw new \InvalidArgumentException('Data em formato inválido. Utilize o formato AAAA-MM-DD.');
        }

        $tiposPermitidos = [
            self::TIPO_LETIVO,
            self::TIPO_FERIADO,
            self::TIPO_RECESSO,
            self::TIPO_FERIAS,
            self::TIPO_CANCELADO_EMERGENCIAL,
        ];

        if (!in_array($tipo, $tiposPermitidos, true)) {
            throw new \InvalidArgumentException('Tipo de dia inválido para o calendário.');
        }

        $descricao = trim($descricao);
        if (empty($descricao)) {
            $descricao = match ($tipo) {
                self::TIPO_FERIADO => 'Feriado Escolar',
                self::TIPO_RECESSO => 'Recesso Escolar',
                self::TIPO_FERIAS => 'Férias Escolares',
                self::TIPO_CANCELADO_EMERGENCIAL => 'Suspensão de Aulas',
                default => 'Dia Letivo Regular'
            };
        }

        $pdo = self::getPdo();
        $diaSemana = (int)$dt->format('N');

        // Se for de segunda a sexta e o tipo for 'letivo', apenas remove da tabela de exceções
        if ($tipo === self::TIPO_LETIVO && $diaSemana < 6) {
            $stmt = $pdo->prepare("DELETE FROM calendario_letivo WHERE data = :data");
            $stmt->execute(['data' => $data]);
            return [
                'sucesso' => true,
                'acao'    => 'removido',
                'mensagem'=> "A data " . date('d/m/Y', strtotime($data)) . " retornou ao status letivo padrão."
            ];
        }

        $sql = "INSERT INTO calendario_letivo (data, tipo, descricao, cancelado_em, cancelado_por)
                VALUES (:data, :tipo, :descricao, NULL, :usuario_id)
                ON DUPLICATE KEY UPDATE 
                    tipo = VALUES(tipo),
                    descricao = VALUES(descricao),
                    cancelado_por = VALUES(cancelado_por)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'data'       => $data,
            'tipo'       => $tipo,
            'descricao'  => mb_substr($descricao, 0, 100),
            'usuario_id' => $usuarioId
        ]);

        return [
            'sucesso' => true,
            'acao'    => 'salvo',
            'mensagem'=> "Data " . date('d/m/Y', strtotime($data)) . " cadastrada como " . ucfirst($tipo) . " ({$descricao})."
        ];
    }

    /**
     * Remove uma exceção do calendário, restaurando o dia ao padrão
     * 
     * @param string $data Formato YYYY-MM-DD
     * @return bool
     */
    public static function removerExcecao(string $data): bool
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("DELETE FROM calendario_letivo WHERE data = :data");
        return $stmt->execute(['data' => $data]);
    }

    /**
     * Executa o Cancelamento Emergencial em Lote para uma data:
     * - Marca a data como 'cancelado_emergencial'
     * - Cancela todos os pedidos agendados e tickets
     * - Libera reservas de estoque de industrializados
     * - Estorna 100% dos valores na carteira familiar de cada responsável
     * - Registra trilha completa de auditoria (logs_auditoria)
     * 
     * @param string $data Formato YYYY-MM-DD
     * @param string $motivo Justificativa formal da suspensão
     * @param int $usuarioId Administrador responsável
     * @param string $ipOrigem Endereço IP
     * @return array Resumo dos pedidos e valores estornados
     */
    public static function cancelamentoEmergencial(string $data, string $motivo, int $usuarioId, string $ipOrigem = '127.0.0.1'): array
    {
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $data);
        if (!$dt) {
            throw new \InvalidArgumentException('Data em formato inválido para cancelamento.');
        }

        $motivo = trim($motivo);
        if (empty($motivo)) {
            throw new \InvalidArgumentException('Informe a justificativa do cancelamento emergencial.');
        }

        $pdo = self::getPdo();
        $iniciouTransacao = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 1. Grava no calendário_letivo como cancelado_emergencial
            $sqlCal = "INSERT INTO calendario_letivo (data, tipo, descricao, cancelado_em, cancelado_por)
                       VALUES (:data, 'cancelado_emergencial', :motivo, NOW(), :usuario_id)
                       ON DUPLICATE KEY UPDATE
                           tipo = 'cancelado_emergencial',
                           descricao = :motivo_up,
                           cancelado_em = NOW(),
                           cancelado_por = :usuario_id_up";
            $stmtCal = $pdo->prepare($sqlCal);
            $stmtCal->execute([
                'data'           => $data,
                'motivo'         => mb_substr($motivo, 0, 100),
                'usuario_id'     => $usuarioId,
                'motivo_up'      => mb_substr($motivo, 0, 100),
                'usuario_id_up'  => $usuarioId
            ]);

            // 2. Busca todos os pedidos ativos para esta data
            $stmtPed = $pdo->prepare("SELECT id, responsavel_id, valor_total, status 
                                      FROM pedidos 
                                      WHERE data_consumo = :data AND status != 'cancelado' 
                                      FOR UPDATE");
            $stmtPed->execute(['data' => $data]);
            $pedidos = $stmtPed->fetchAll(\PDO::FETCH_ASSOC);

            $pedidosCancelados = 0;
            $totalEstornado = 0.00;

            foreach ($pedidos as $ped) {
                $pedidoId = (int)$ped['id'];
                $responsavelId = (int)$ped['responsavel_id'];
                $valorTotal = (float)$ped['valor_total'];

                // 2.1. Libera reserva de estoque para industrializados
                $stmtItens = $pdo->prepare("SELECT ip.produto_id, ip.quantidade, p.tipo as produto_tipo
                                            FROM itens_pedido ip
                                            JOIN produtos p ON p.id = ip.produto_id
                                            WHERE ip.pedido_id = :pedido_id");
                $stmtItens->execute(['pedido_id' => $pedidoId]);
                $itens = $stmtItens->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($itens as $item) {
                    if ($item['produto_tipo'] === 'industrializado_continuo') {
                        $stmtEstoque = $pdo->prepare("UPDATE produtos 
                                                      SET estoque_reservado = GREATEST(0, estoque_reservado - :qtd) 
                                                      WHERE id = :prod_id");
                        $stmtEstoque->execute([
                            'qtd'     => (int)$item['quantidade'],
                            'prod_id' => (int)$item['produto_id']
                        ]);
                    }
                }

                // 2.2. Cancela os tickets térmicos associados
                $stmtTickets = $pdo->prepare("UPDATE tickets SET status = 'cancelado' 
                                              WHERE pedido_id = :pedido_id AND status != 'cancelado'");
                $stmtTickets->execute(['pedido_id' => $pedidoId]);

                // 2.3. Atualiza status do pedido para cancelado
                $stmtCanc = $pdo->prepare("UPDATE pedidos SET status = 'cancelado', updated_at = NOW() 
                                           WHERE id = :pedido_id");
                $stmtCanc->execute(['pedido_id' => $pedidoId]);

                // 2.4. Estorno financeiro de 100% para a carteira familiar
                if ($valorTotal > 0) {
                    TransacaoCarteira::creditar(
                        responsavelId: $responsavelId,
                        valor: $valorTotal,
                        tipo: TransacaoCarteira::TIPO_ESTORNO_CANCELAMENTO,
                        descricao: "Estorno emergencial - Suspensão letiva em " . date('d/m/Y', strtotime($data)) . " ({$motivo})",
                        referenciaId: "emergencial_pedido_{$pedidoId}",
                        operadorId: $usuarioId
                    );
                    $totalEstornado += $valorTotal;
                }

                // 2.5. Trilha de auditoria por pedido
                $stmtAudit = $pdo->prepare("INSERT INTO logs_auditoria 
                    (operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at)
                    VALUES (:op, 'cancelamento_letivo_lote', 'pedidos', :reg, :just, :ip, NOW())");
                $stmtAudit->execute([
                    'op'   => $usuarioId,
                    'reg'  => $pedidoId,
                    'just' => "Suspensão de aulas em " . date('d/m/Y', strtotime($data)) . ": {$motivo}",
                    'ip'   => $ipOrigem
                ]);

                $pedidosCancelados++;
            }

            // 3. Auditoria mestre da operação de cancelamento no calendário
            $stmtAuditMaster = $pdo->prepare("INSERT INTO logs_auditoria 
                (operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at)
                VALUES (:op, 'cancelamento_letivo_lote', 'calendario_letivo', 0, :just, :ip, NOW())");
            $stmtAuditMaster->execute([
                'op'   => $usuarioId,
                'just' => "Suspensão emergencial da data " . date('d/m/Y', strtotime($data)) . ": {$motivo}. Pedidos afetados: {$pedidosCancelados}, Total estornado: R$ " . number_format($totalEstornado, 2, ',', '.'),
                'ip'   => $ipOrigem
            ]);

            if ($iniciouTransacao) {
                $pdo->commit();
            }

            return [
                'sucesso'            => true,
                'data'               => $data,
                'pedidos_cancelados' => $pedidosCancelados,
                'total_estornado'    => $totalEstornado,
                'mensagem'           => "Cancelamento emergencial aplicado para " . date('d/m/Y', strtotime($data)) . ". {$pedidosCancelados} pedido(s) cancelado(s) e R$ " . number_format($totalEstornado, 2, ',', '.') . " estornado(s) com sucesso aos responsáveis."
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Retorna contadores analíticos para o calendário do ano
     * 
     * @param int|null $ano
     * @return array
     */
    public static function contarMetricasAno(?int $ano = null): array
    {
        $ano = $ano ?? (int)date('Y');
        $pdo = self::getPdo();

        $stmt = $pdo->prepare("SELECT tipo, COUNT(*) as qtd 
                               FROM calendario_letivo 
                               WHERE YEAR(data) = :ano 
                               GROUP BY tipo");
        $stmt->execute(['ano' => $ano]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $metricas = [
            'feriados'         => 0,
            'recessos'         => 0,
            'ferias'           => 0,
            'cancelamentos'    => 0,
            'total_excecoes'   => 0
        ];

        foreach ($rows as $r) {
            $tipo = $r['tipo'];
            $qtd = (int)$r['qtd'];
            $metricas['total_excecoes'] += $qtd;

            if ($tipo === self::TIPO_FERIADO) {
                $metricas['feriados'] += $qtd;
            } elseif ($tipo === self::TIPO_RECESSO) {
                $metricas['recessos'] += $qtd;
            } elseif ($tipo === self::TIPO_FERIAS) {
                $metricas['ferias'] += $qtd;
            } elseif ($tipo === self::TIPO_CANCELADO_EMERGENCIAL) {
                $metricas['cancelamentos'] += $qtd;
            }
        }

        return $metricas;
    }
}

