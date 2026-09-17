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
}
