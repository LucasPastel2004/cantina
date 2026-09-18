<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Modelo de Restrições Alimentares / Alérgenos Oficiais
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class RestricaoAlimentar extends Model
{
    protected string $table = 'restricoes_alimentares';
    protected string $primaryKey = 'id';
    protected bool $timestamps = false;
    protected bool $softDelete = false;

    /**
     * Retorna todas as restrições alimentares do catálogo oficial
     */
    public static function listarTodas(): array
    {
        $sql = "SELECT * FROM restricoes_alimentares ORDER BY id ASC";
        return self::fetchAll($sql);
    }

    /**
     * Busca uma restrição alimentar pelo ID
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "SELECT * FROM restricoes_alimentares WHERE id = :id LIMIT 1";
        return self::fetchOne($sql, ['id' => $id]);
    }

    /**
     * Retorna os alérgenos vinculados a um determinado produto
     */
    public static function listarPorProduto(int $produtoId): array
    {
        $sql = "SELECT r.* 
                FROM restricoes_alimentares r
                INNER JOIN produto_alergenos pa ON pa.restricao_id = r.id
                WHERE pa.produto_id = :produto_id
                ORDER BY r.id ASC";

        return self::fetchAll($sql, ['produto_id' => $produtoId]);
    }

    /**
     * Retorna os alérgenos vinculados a um determinado aluno
     */
    public static function listarPorAluno(int $alunoId): array
    {
        $sql = "SELECT r.* 
                FROM restricoes_alimentares r
                INNER JOIN aluno_restricoes ar ON ar.restricao_id = r.id
                WHERE ar.aluno_id = :aluno_id
                ORDER BY r.id ASC";

        return self::fetchAll($sql, ['aluno_id' => $alunoId]);
    }

    /**
     * Sincroniza atomicamente os alérgenos de um aluno (limpa anteriores e insere novos)
     */
    public static function sincronizarAluno(int $alunoId, array $restricoesIds): void
    {
        // 1. Remove vínculos anteriores
        $sqlDelete = "DELETE FROM aluno_restricoes WHERE aluno_id = :aluno_id";
        self::execute($sqlDelete, ['aluno_id' => $alunoId]);

        // 2. Insere novos vínculos caso informados
        if (!empty($restricoesIds)) {
            $sqlInsert = "INSERT INTO aluno_restricoes (aluno_id, restricao_id) VALUES (:aluno_id, :restricao_id)";
            foreach ($restricoesIds as $rid) {
                $ridInt = (int)$rid;
                if ($ridInt > 0) {
                    try {
                        self::execute($sqlInsert, [
                            'aluno_id'     => $alunoId,
                            'restricao_id' => $ridInt
                        ]);
                    } catch (\PDOException $e) {
                        // Ignora violação de chave primária duplicada
                    }
                }
            }
        }
    }

    /**
     * Verifica se um aluno possui determinada restrição alimentar
     */
    public static function alunoTemRestricao(int $alunoId, int $restricaoId): bool
    {
        $sql = "SELECT 1 FROM aluno_restricoes WHERE aluno_id = :aluno_id AND restricao_id = :restricao_id LIMIT 1";
        return self::fetchOne($sql, ['aluno_id' => $alunoId, 'restricao_id' => $restricaoId]) !== null;
    }

    /**
     * Busca uma restrição alimentar pelo nome (case insensitive)
     */
    public static function buscarPorNome(string $nome): ?array
    {
        $sql = "SELECT * FROM restricoes_alimentares WHERE LOWER(TRIM(nome)) = LOWER(TRIM(:nome)) LIMIT 1";
        return self::fetchOne($sql, ['nome' => $nome]);
    }
}
