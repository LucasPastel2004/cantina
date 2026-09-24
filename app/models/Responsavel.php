<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Dados: Responsáveis Familiares (Titulares das Contas)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Responsavel extends Model
{
    protected string $table = 'responsaveis';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = false; // Tabela responsaveis não possui deleted_at conforme migration 004

    /**
     * Remove pontuações e caracteres não numéricos do CPF
     */
    public static function cleanCpf(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf);
    }

    /**
     * Localiza um responsável pelo CPF (limpo ou formatado)
     */
    public function findByCpf(string $cpf): ?array
    {
        $cleanCpf = self::cleanCpf($cpf);
        $sql = "SELECT * FROM {$this->table} WHERE cpf = :cpf LIMIT 1";

        return self::fetchOne($sql, ['cpf' => $cleanCpf]);
    }

    /**
     * Localiza um responsável pelo e-mail
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";

        return self::fetchOne($sql, ['email' => trim($email)]);
    }

    /**
     * Localiza um responsável pelo número de WhatsApp
     */
    public function findByWhatsapp(string $whatsapp): ?array
    {
        $cleanWhatsapp = preg_replace('/\D/', '', $whatsapp);
        $sql = "SELECT * FROM {$this->table} WHERE REPLACE(REPLACE(REPLACE(REPLACE(whatsapp, '(', ''), ')', ''), '-', ''), ' ', '') LIKE :whatsapp LIMIT 1";

        return self::fetchOne($sql, ['whatsapp' => '%' . $cleanWhatsapp]);
    }

    /**
     * Atualiza a senha do responsável com Bcrypt
     */
    public function updatePassword(int $id, string $plainPassword): bool
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE {$this->table} 
                SET senha_hash = :senha_hash, updated_at = :updated_at 
                WHERE id = :id";

        return self::execute($sql, [
            'senha_hash' => $hash,
            'updated_at' => date('Y-m-d H:i:s'),
            'id'         => $id
        ]) > 0;
    }

    /**
     * Registra o aceite formal dos Termos e Políticas da Cantina
     */
    public function aceitarTermos(int $id): bool
    {
        $sql = "UPDATE {$this->table} 
                SET termos_aceitos_em = :termos_aceitos_em, updated_at = :updated_at 
                WHERE id = :id";

        return self::execute($sql, [
            'termos_aceitos_em' => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
            'id'                => $id
        ]) > 0;
    }

    /**
     * Retorna os alunos dependentes vinculados ao responsável
     */
    public function getDependentes(int $responsavelId): array
    {
        $sql = "SELECT a.*, t.serie, t.nome_turma, t.turno AS turno_turma 
                FROM alunos a 
                LEFT JOIN turmas t ON t.id = a.turma_id AND t.deleted_at IS NULL 
                WHERE a.responsavel_id = :responsavel_id 
                AND a.deleted_at IS NULL 
                ORDER BY a.nome ASC";

        return self::fetchAll($sql, ['responsavel_id' => $responsavelId]);
    }

    /**
     * Verifica se o responsável está pendente de primeiro acesso (sem senha ou sem aceite de termos)
     */
    public static function isPendentePrimeiroAcesso(array $responsavel): bool
    {
        return empty($responsavel['senha_hash']) || empty($responsavel['termos_aceitos_em']);
    }

    /**
     * Localiza um responsável pelo CPF ou cria um novo cadastro seguro.
     * Preserva integralmente senha, termos e saldo de contas já existentes.
     * 
     * @return array ['id' => int, 'novo' => bool, 'registro' => array]
     */
    public static function localizarOuCriarPorCpf(array $dados, ?int $usuarioId = null): array
    {
        $cleanCpf = self::cleanCpf($dados['cpf'] ?? '');
        if (empty($cleanCpf)) {
            throw new \InvalidArgumentException('CPF do responsável não informado.');
        }

        $existente = self::fetchOne("SELECT * FROM responsaveis WHERE cpf = :cpf LIMIT 1", ['cpf' => $cleanCpf]);

        if ($existente) {
            $updateFields = [];
            $updateParams = ['id' => $existente['id'], 'updated_at' => date('Y-m-d H:i:s')];

            // Atualiza nome se não estiver vazio
            if (!empty($dados['nome']) && $dados['nome'] !== $existente['nome']) {
                $updateFields[] = "nome = :nome";
                $updateParams['nome'] = trim($dados['nome']);
            }
            // Atualiza whatsapp se fornecido
            if (!empty($dados['whatsapp'])) {
                $cleanWhats = preg_replace('/\D/', '', $dados['whatsapp']);
                if (!empty($cleanWhats)) {
                    $updateFields[] = "whatsapp = :whatsapp";
                    $updateParams['whatsapp'] = trim($dados['whatsapp']);
                }
            }
            // Atualiza email se fornecido
            if (!empty($dados['email']) && filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
                $updateFields[] = "email = :email";
                $updateParams['email'] = trim($dados['email']);
            }

            if (!empty($updateFields)) {
                $updateFields[] = "updated_by = :updated_by";
                $updateParams['updated_by'] = $usuarioId;
                $sql = "UPDATE responsaveis SET " . implode(', ', $updateFields) . ", updated_at = :updated_at WHERE id = :id";
                self::execute($sql, $updateParams);
                $existente = self::fetchOne("SELECT * FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $existente['id']]);
            }

            return [
                'id'       => (int)$existente['id'],
                'novo'     => false,
                'registro' => $existente
            ];
        }

        // Se não existe, cria novo registro com saldo 0.00 e sem senha (pendente de primeiro acesso)
        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO responsaveis (
                    cpf, nome, email, whatsapp, senha_hash,
                    saldo_atual, termos_aceitos_em, bloqueado_med,
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :cpf, :nome, :email, :whatsapp, '',
                    0.00, NULL, 0,
                    :created_by, :updated_by, :created_at, :updated_at
                )";

        self::execute($sql, [
            'cpf'        => $cleanCpf,
            'nome'       => trim($dados['nome'] ?? 'Responsável ' . substr($cleanCpf, 0, 3)),
            'email'      => trim($dados['email'] ?? ''),
            'whatsapp'   => trim($dados['whatsapp'] ?? ''),
            'created_by' => $usuarioId,
            'updated_by' => $usuarioId,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $newId = (int)self::lastInsertId();
        $novo = self::fetchOne("SELECT * FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $newId]);

        return [
            'id'       => $newId,
            'novo'     => true,
            'registro' => $novo
        ];
    }

    /**
     * Lista responsáveis para seleção em formulários
     */
    public static function listarParaSelecao(string $busca = ''): array
    {
        $sql = "SELECT id, cpf, nome, whatsapp, saldo_atual FROM responsaveis";
        $params = [];

        if (!empty($busca)) {
            $termo = '%' . trim($busca) . '%';
            $sql .= " WHERE nome LIKE :busca_nome OR cpf LIKE :busca_cpf";
            $params['busca_nome'] = $termo;
            $params['busca_cpf'] = $termo;
        }

        $sql .= " ORDER BY nome ASC LIMIT 50";

        return self::fetchAll($sql, $params);
    }

    /**
     * Busca um responsável por ID
     */
    public static function buscarPorId(int $id): ?array
    {
        return self::fetchOne("SELECT * FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $id]);
    }

    /**
     * Consulta o saldo atual em tempo real do responsável no banco
     */
    public static function consultarSaldo(int $id): float
    {
        $resp = self::fetchOne("SELECT saldo_atual FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $id]);
        return (float)($resp['saldo_atual'] ?? 0.00);
    }

    /**
     * Verifica se o responsável está com bloqueio preventivo de contestação bancária (MED)
     */
    public static function isBloqueadoMed(int $id): bool
    {
        $resp = self::fetchOne("SELECT bloqueado_med FROM responsaveis WHERE id = :id LIMIT 1", ['id' => $id]);
        return (int)($resp['bloqueado_med'] ?? 0) === 1;
    }

    /**
     * Atualiza a flag de bloqueio MED do responsável
     */
    public static function definirBloqueioMed(int $id, bool $bloquear): bool
    {
        return self::execute(
            "UPDATE responsaveis SET bloqueado_med = :bloqueado, updated_at = NOW() WHERE id = :id",
            ['bloqueado' => $bloquear ? 1 : 0, 'id' => $id]
        ) > 0;
    }

    /**
     * Bloqueia preventivamente o responsável por contestação MED com auditoria
     */
    public static function bloquearMed(int $id, string $motivo = 'Bloqueio preventivo MED por saldo devedor', ?int $operadorId = null): bool
    {
        $resp = self::buscarPorId($id);
        if (!$resp) {
            return false;
        }

        self::definirBloqueioMed($id, true);

        // Trilha em logs_seguranca
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            self::execute(
                "INSERT INTO logs_seguranca (evento, cpf_ou_usuario, detalhes_json, ip_origem, created_at)
                 VALUES (:evento, :cpf, :detalhes, :ip, NOW())",
                [
                    'evento'   => 'conta_bloqueada_med',
                    'cpf'      => $resp['cpf'],
                    'detalhes' => json_encode(['responsavel_id' => $id, 'motivo' => $motivo, 'operador_id' => $operadorId], JSON_UNESCAPED_UNICODE),
                    'ip'       => $ip
                ]
            );
        } catch (\Throwable $e) {}

        // Trilha em logs_auditoria se houver operador
        if ($operadorId) {
            try {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                self::execute(
                    "INSERT INTO logs_auditoria (operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at)
                     VALUES (:operador_id, :acao, :tabela, :registro_id, :justificativa, :ip, NOW())",
                    [
                        'operador_id'   => $operadorId,
                        'acao'          => 'bloqueio_med',
                        'tabela_afetada'=> 'responsaveis',
                        'registro_id'   => $id,
                        'justificativa' => $motivo,
                        'ip'            => $ip
                    ]
                );
            } catch (\Throwable $e) {}
        }

        return true;
    }

    /**
     * Remove o bloqueio MED de um responsável com justificativa e auditoria obrigatória
     */
    public static function desbloquearMed(int $id, ?int $operadorId = null, string $justificativa = 'Desbloqueio administrativo'): bool
    {
        $resp = self::buscarPorId($id);
        if (!$resp) {
            return false;
        }

        self::definirBloqueioMed($id, false);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // 1. Grava em logs_auditoria
        if ($operadorId) {
            try {
                self::execute(
                    "INSERT INTO logs_auditoria (operador_id, acao, tabela_afetada, registro_id, justificativa, ip_origem, created_at)
                     VALUES (:operador_id, :acao, :tabela, :registro_id, :justificativa, :ip, NOW())",
                    [
                        'operador_id'   => $operadorId,
                        'acao'          => 'desbloqueio_med',
                        'tabela_afetada'=> 'responsaveis',
                        'registro_id'   => $id,
                        'justificativa' => $justificativa,
                        'ip'            => $ip
                    ]
                );
            } catch (\Throwable $e) {}
        }

        // 2. Grava em logs_seguranca
        try {
            self::execute(
                "INSERT INTO logs_seguranca (evento, cpf_ou_usuario, detalhes_json, ip_origem, created_at)
                 VALUES (:evento, :cpf, :detalhes, :ip, NOW())",
                [
                    'evento'   => 'conta_desbloqueada_med',
                    'cpf'      => $resp['cpf'],
                    'detalhes' => json_encode([
                        'responsavel_id' => $id,
                        'operador_id'    => $operadorId,
                        'justificativa'  => $justificativa,
                        'saldo_atual'    => $resp['saldo_atual']
                    ], JSON_UNESCAPED_UNICODE),
                    'ip'       => $ip
                ]
            );
        } catch (\Throwable $e) {}

        return true;
    }

    /**
     * Retorna a quantidade de famílias com saldo devedor ou bloqueadas por MED
     */
    public static function contarNegativadosMed(): int
    {
        $sql = "SELECT COUNT(*) AS total FROM responsaveis WHERE bloqueado_med = 1 OR saldo_atual < 0";
        $row = self::fetchOne($sql);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Retorna o somatório de saldo devedor em aberto (valor monetário absoluto)
     */
    public static function somarSaldoDevedorMed(): float
    {
        $sql = "SELECT COALESCE(SUM(ABS(saldo_atual)), 0) AS total FROM responsaveis WHERE saldo_atual < 0";
        $row = self::fetchOne($sql);
        return (float)($row['total'] ?? 0.0);
    }

    /**
     * Retorna a listagem paginada de famílias com saldo devedor ou bloqueadas por MED (FSD Tela 8.4)
     */
    public static function listarNegativadosMed(array|string $filtros = [], int|string $pagina = 1, int $porPagina = 25): array
    {
        if (is_string($filtros)) {
            $status = is_string($pagina) ? $pagina : 'todos';
            $filtros = ['busca' => $filtros, 'status' => $status];
            $pagina = 1;
        }

        $where = ["(r.bloqueado_med = 1 OR r.saldo_atual < 0)"];
        $params = [];

        // Filtro por busca de texto (Nome ou CPF do responsável, ou nome do dependente)
        if (!empty($filtros['busca'])) {
            $termo = '%' . trim($filtros['busca']) . '%';
            $where[] = "(r.nome LIKE :b_nome OR r.cpf LIKE :b_cpf OR EXISTS (
                SELECT 1 FROM alunos a_sub WHERE a_sub.responsavel_id = r.id AND a_sub.nome LIKE :b_aluno
            ))";
            $params['b_nome'] = $termo;
            $params['b_cpf'] = $termo;
            $params['b_aluno'] = $termo;
        }

        // Filtro por status
        if (!empty($filtros['status'])) {
            if ($filtros['status'] === 'bloqueado') {
                $where[] = "r.bloqueado_med = 1";
            } elseif ($filtros['status'] === 'negativo') {
                $where[] = "r.saldo_atual < 0";
            } elseif ($filtros['status'] === 'regularizado') {
                $where[] = "r.bloqueado_med = 0 AND r.saldo_atual >= 0";
            }
        }

        $whereSql = implode(' AND ', $where);

        // 1. Contagem total de registros
        $countSql = "SELECT COUNT(*) AS total FROM responsaveis r WHERE {$whereSql}";
        $totalRow = self::fetchOne($countSql, $params);
        $total = (int)($totalRow['total'] ?? 0);

        // 2. Paginação
        $pagina = max(1, $pagina);
        $porPagina = max(5, min(100, $porPagina));
        $totalPaginas = (int)ceil($total / $porPagina);
        $offset = ($pagina - 1) * $porPagina;

        // 3. Consulta das famílias com dependentes e última data de estorno MED
        $sql = "SELECT r.*,
                       (SELECT MAX(created_at) FROM transacoes_carteira WHERE responsavel_id = r.id AND tipo_transacao = 'debito_contestacao_med') AS data_ultimo_estorno_med,
                       (SELECT COUNT(*) FROM alunos WHERE responsavel_id = r.id AND deleted_at IS NULL) AS total_alunos
                FROM responsaveis r
                WHERE {$whereSql}
                ORDER BY r.saldo_atual ASC, r.bloqueado_med DESC, r.nome ASC
                LIMIT {$porPagina} OFFSET {$offset}";

        $familias = self::fetchAll($sql, $params);

        // Anexa os dependentes matriculados a cada família
        $respModel = new self();
        foreach ($familias as &$fam) {
            $deps = $respModel->getDependentes((int)$fam['id']);
            $fam['dependentes'] = $deps;
            $nomesDeps = array_map(function($d) {
                return $d['nome'] . (!empty($d['nome_turma']) ? ' (' . $d['nome_turma'] . ')' : '');
            }, $deps);
            $fam['dependentes_str'] = !empty($nomesDeps) ? implode(', ', $nomesDeps) : 'Nenhum vinculado';
            $fam['saldo_atual_num'] = (float)$fam['saldo_atual'];
            $fam['saldo_atual_fmt'] = 'R$ ' . number_format($fam['saldo_atual'], 2, ',', '.');
            $fam['cpf_formatado'] = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', str_pad($fam['cpf'], 11, '0', STR_PAD_LEFT));
            $fam['data_estorno_fmt'] = !empty($fam['data_ultimo_estorno_med']) ? date('d/m/Y H:i', strtotime($fam['data_ultimo_estorno_med'])) : '-';
        }
        unset($fam);

        return [
            'itens'         => $familias,
            'registros'     => $familias,
            'total'         => $total,
            'pagina'        => $pagina,
            'por_pagina'    => $porPagina,
            'total_paginas' => max(1, $totalPaginas)
        ];
    }
}

