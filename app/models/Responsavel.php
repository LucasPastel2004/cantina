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
}
