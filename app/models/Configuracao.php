<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Configurações Globais da Cantina
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Configuracao extends Model
{
    protected string $table = 'configuracoes_globais';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Obtém o registro único com os parâmetros globais da cantina
     */
    public static function obter(): array
    {
        $sql = "SELECT * FROM `configuracoes_globais` ORDER BY id ASC LIMIT 1";
        $config = self::fetchOne($sql);

        if (!$config) {
            // Se ainda não existir por algum motivo, inicializa com os padrões oficiais do FSD
            $pdo = self::getConnection();
            $stmt = $pdo->prepare("INSERT INTO `configuracoes_globais` (
                `razao_social`, `nome_fantasia`, `cnpj`, `telefone_contato`, `logo_path`,
                `recarga_minima_pix`, `alerta_saldo_baixo`, `timeout_sessao_minutos`,
                `limite_tentativas_login`, `itens_por_pagina`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                'Colégio Monteiro Lobato Ltda',
                'Cantina Web — Colégio Monteiro Lobato',
                '12.345.678/0001-90',
                '(11) 98765-4321',
                'assets/img/logo-colegio.jpg',
                10.00,
                15.00,
                60,
                5,
                25
            ]);

            $config = self::fetchOne($sql);
        }

        return $config ?: [];
    }

    /**
     * Retorna a quantidade configurada de itens por página para tabelas do sistema
     */
    public static function getItensPorPagina(): int
    {
        $config = self::obter();
        $itens = (int)($config['itens_por_pagina'] ?? 20);
        return ($itens >= 5 && $itens <= 100) ? $itens : 20;
    }

    /**
     * Atualiza os parâmetros globais da instituição
     */
    public static function salvar(array $dados): bool
    {
        $configAtual = self::obter();
        $id = $configAtual['id'] ?? 1;

        $camposPermitidos = [
            'razao_social',
            'nome_fantasia',
            'cnpj',
            'telefone_contato',
            'logo_path',
            'recarga_minima_pix',
            'alerta_saldo_baixo',
            'timeout_sessao_minutos',
            'limite_tentativas_login',
            'itens_por_pagina'
        ];

        $sets = [];
        $params = [];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $dados)) {
                $sets[] = "`{$campo}` = :{$campo}";
                $params[$campo] = $dados[$campo];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params['id'] = $id;
        $sql = "UPDATE `configuracoes_globais` SET " . implode(', ', $sets) . " WHERE `id` = :id";

        return self::execute($sql, $params) >= 0;
    }
}
