<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Fornecedores Parceiros
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class Fornecedor extends Model
{
    protected string $table = 'fornecedores';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Lista todos os fornecedores com suporte a filtros de busca e status
     */
    public static function listarTodos(bool $apenasAtivos = false, string $busca = '', ?int $limit = null, int $offset = 0): array
    {
        $sql = "SELECT * FROM fornecedores WHERE deleted_at IS NULL";
        $params = [];

        if ($apenasAtivos) {
            $sql .= " AND ativo = 1";
        }

        if (!empty($busca)) {
            $termoBusca = '%' . trim($busca) . '%';
            $sql .= " AND (nome_fantasia LIKE :busca_nome OR razao_social LIKE :busca_razao OR cnpj_cpf LIKE :busca_cnpj OR contato_responsavel LIKE :busca_contato)";
            $params['busca_nome'] = $termoBusca;
            $params['busca_razao'] = $termoBusca;
            $params['busca_cnpj'] = $termoBusca;
            $params['busca_contato'] = $termoBusca;
        }

        $sql .= " ORDER BY nome_fantasia ASC";

        if ($limit !== null && $limit > 0) {
            $offset = max(0, $offset);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Conta o total de fornecedores cadastrados
     */
    public static function contarTodos(bool $apenasAtivos = false, string $busca = ''): int
    {
        $sql = "SELECT COUNT(*) as total FROM fornecedores WHERE deleted_at IS NULL";
        $params = [];

        if ($apenasAtivos) {
            $sql .= " AND ativo = 1";
        }

        if (!empty($busca)) {
            $termoBusca = '%' . trim($busca) . '%';
            $sql .= " AND (nome_fantasia LIKE :busca_nome OR razao_social LIKE :busca_razao OR cnpj_cpf LIKE :busca_cnpj OR contato_responsavel LIKE :busca_contato)";
            $params['busca_nome'] = $termoBusca;
            $params['busca_razao'] = $termoBusca;
            $params['busca_cnpj'] = $termoBusca;
            $params['busca_contato'] = $termoBusca;
        }

        $row = self::fetchOne($sql, $params);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Busca um fornecedor pelo ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM fornecedores WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $res = self::fetchOne($sql, [':id' => $id]);
        return $res ?: null;
    }

    /**
     * Busca fornecedor por nome fantasia ou razão social exata
     */
    public static function buscarPorNome(string $nome): ?array
    {
        $sql = "SELECT * FROM fornecedores 
                WHERE (nome_fantasia = :nome1 OR razao_social = :nome2) 
                  AND deleted_at IS NULL 
                LIMIT 1";
        $res = self::fetchOne($sql, [':nome1' => trim($nome), ':nome2' => trim($nome)]);
        return $res ?: null;
    }

    /**
     * Lista apenas os nomes para sugestões (autocompletes, datalists e dropdowns)
     */
    public static function listarNomesAtivos(): array
    {
        $sql = "SELECT DISTINCT nome_fantasia FROM fornecedores 
                WHERE ativo = 1 AND deleted_at IS NULL 
                ORDER BY nome_fantasia ASC";
        $rows = self::fetchAll($sql);
        return array_column($rows, 'nome_fantasia');
    }

    /**
     * Salva ou atualiza um fornecedor
     */
    public static function salvar(array $dados, ?int $usuarioId = null): int
    {
        $id = !empty($dados['id']) ? (int)$dados['id'] : 0;

        $nomeFantasia = trim((string)($dados['nome_fantasia'] ?? ''));
        $razaoSocial = trim((string)($dados['razao_social'] ?? ''));
        $cnpjCpf = trim((string)($dados['cnpj_cpf'] ?? ''));
        $telefone = trim((string)($dados['telefone'] ?? ''));
        $email = trim((string)($dados['email'] ?? ''));
        $contato = trim((string)($dados['contato_responsavel'] ?? ''));
        $endereco = trim((string)($dados['endereco'] ?? ''));
        $observacoes = trim((string)($dados['observacoes'] ?? ''));
        $ativo = isset($dados['ativo']) ? (int)(bool)$dados['ativo'] : 1;

        if ($razaoSocial === '' && $nomeFantasia !== '') {
            $razaoSocial = $nomeFantasia;
        }

        $now = date('Y-m-d H:i:s');
        $uid = ($usuarioId !== null && $usuarioId > 0) ? $usuarioId : null;

        if ($id > 0) {
            $sql = "UPDATE fornecedores 
                    SET nome_fantasia = :nome_fantasia,
                        razao_social = :razao_social,
                        cnpj_cpf = :cnpj_cpf,
                        telefone = :telefone,
                        email = :email,
                        contato_responsavel = :contato,
                        endereco = :endereco,
                        observacoes = :observacoes,
                        ativo = :ativo,
                        updated_by = :updated_by,
                        updated_at = :updated_at
                    WHERE id = :id AND deleted_at IS NULL";

            self::execute($sql, [
                ':id'            => $id,
                ':nome_fantasia' => $nomeFantasia,
                ':razao_social'  => $razaoSocial,
                ':cnpj_cpf'      => $cnpjCpf,
                ':telefone'      => $telefone,
                ':email'         => $email,
                ':contato'       => $contato,
                ':endereco'      => $endereco,
                ':observacoes'   => $observacoes,
                ':ativo'         => $ativo,
                ':updated_by'    => $uid,
                ':updated_at'    => $now
            ]);

            return $id;
        }

        $sql = "INSERT INTO fornecedores (
                    nome_fantasia, razao_social, cnpj_cpf, telefone, email, 
                    contato_responsavel, endereco, observacoes, ativo, 
                    created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :nome_fantasia, :razao_social, :cnpj_cpf, :telefone, :email, 
                    :contato, :endereco, :observacoes, :ativo, 
                    :created_by, :updated_by, :created_at, :updated_at
                )";

        self::execute($sql, [
            ':nome_fantasia' => $nomeFantasia,
            ':razao_social'  => $razaoSocial,
            ':cnpj_cpf'      => $cnpjCpf,
            ':telefone'      => $telefone,
            ':email'         => $email,
            ':contato'       => $contato,
            ':endereco'      => $endereco,
            ':observacoes'   => $observacoes,
            ':ativo'         => $ativo,
            ':created_by'    => $uid,
            ':updated_by'    => $uid,
            ':created_at'    => $now,
            ':updated_at'    => $now
        ]);

        return (int)self::lastInsertId();
    }

    /**
     * Exclui um fornecedor (Soft Delete)
     */
    public static function excluir(int $id, ?int $usuarioId = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $uid = ($usuarioId !== null && $usuarioId > 0) ? $usuarioId : null;
        $sql = "UPDATE fornecedores 
                SET deleted_at = :deleted_at, 
                    ativo = 0, 
                    updated_by = :uid,
                    updated_at = :updated_at 
                WHERE id = :id AND deleted_at IS NULL";
        
        return self::execute($sql, [
            ':deleted_at' => $now,
            ':uid'        => $uid,
            ':updated_at' => $now,
            ':id'         => $id
        ]) > 0;
    }
}
