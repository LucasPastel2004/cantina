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
     * @return array Lista dos 5 dias formatados com metadados
     */
    public static function obterDiasSemanaLetiva(string $tipoSemana = 'atual', ?\DateTimeInterface $agora = null): array
    {
        $now = $agora ? \DateTimeImmutable::createFromInterface($agora) : new \DateTimeImmutable();
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
}
