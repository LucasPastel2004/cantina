<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Dados: Usuários da Equipe Interna (Operadores e Administradores)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Usuario extends Model
{
    protected string $table = 'usuarios';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Localiza um usuário pelo seu identificador de login (ex: 'admin' ou 'operador-maria')
     */
    public function findByUsuario(string $usuario): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE usuario = :usuario 
                AND deleted_at IS NULL 
                LIMIT 1";

        return self::fetchOne($sql, ['usuario' => trim($usuario)]);
    }

    /**
     * Localiza um usuário pelo seu endereço de e-mail
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE email = :email 
                AND deleted_at IS NULL 
                LIMIT 1";

        return self::fetchOne($sql, ['email' => trim($email)]);
    }

    /**
     * Retorna todos os usuários ativos de um determinado perfil ('operador' ou 'administrador')
     */
    public function findByPerfil(string $perfil): array
    {
        $sql = "SELECT id, usuario, nome, email, perfil, ativo, created_at 
                FROM {$this->table} 
                WHERE perfil = :perfil 
                AND deleted_at IS NULL 
                ORDER BY nome ASC";

        return self::fetchAll($sql, ['perfil' => $perfil]);
    }

    /**
     * Retorna todos os usuários ativos da equipe
     */
    public static function listarTodos(): array
    {
        return self::fetchAll("SELECT * FROM usuarios WHERE deleted_at IS NULL ORDER BY nome ASC");
    }

    /**
     * Atualiza a senha criptografada de um usuário
     */
    public function updatePassword(int $id, string $plainPassword): bool
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $sql = "UPDATE {$this->table} 
                SET senha_hash = :senha_hash, updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";

        return self::execute($sql, [
            'senha_hash' => $hash,
            'updated_at' => date('Y-m-d H:i:s'),
            'id'         => $id
        ]) > 0;
    }

    /**
     * Cria um novo usuário com senha criptografada e validações
     */
    public function createUsuario(array $data): int
    {
        if (isset($data['senha'])) {
            $data['senha_hash'] = password_hash($data['senha'], PASSWORD_BCRYPT);
            unset($data['senha']);
        }

        return $this->create($data);
    }
}
