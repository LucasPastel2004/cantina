<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Model: FornecedorRecibo
 * 
 * Gestão e Emissão de Recibos de Pagamento a Fornecedores Parceiros
 * com rastreabilidade, auditoria contábil e integração ao Livro Caixa Diário.
 */

namespace App\Models;

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

use App\Core\Model;

class FornecedorRecibo extends Model
{
    protected string $table = 'fornecedor_recibos';
    protected string $primaryKey = 'id';
    protected bool $timestamps = true;
    protected bool $softDelete = true;

    /**
     * Gera o próximo número oficial de recibo (ex: REC-20261027-0001)
     */
    public static function gerarNumeroRecibo(): string
    {
        $hoje = date('Ymd');
        $prefixo = "REC-{$hoje}-";

        $pdo = self::getConnection();
        $stmt = $pdo->prepare("
            SELECT numero_recibo 
            FROM fornecedor_recibos 
            WHERE numero_recibo LIKE :prefixo 
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute(['prefixo' => "{$prefixo}%"]);
        $ultimo = $stmt->fetchColumn();

        if ($ultimo) {
            $seq = (int)substr($ultimo, -4);
            $novoSeq = str_pad($seq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $novoSeq = '0001';
        }

        return "{$prefixo}{$novoSeq}";
    }

    /**
     * Cria um novo recibo de pagamento a fornecedor
     */
    public static function criar(array $dados, ?int $usuarioId = null): int
    {
        $fornecedorId = (int)($dados['fornecedor_id'] ?? 0);
        $valor = (float)($dados['valor'] ?? 0);
        $dataPagamento = trim($dados['data_pagamento'] ?? date('Y-m-d'));
        $formaPagamento = strtolower(trim($dados['forma_pagamento'] ?? 'pix'));
        $descricao = trim($dados['descricao'] ?? '');
        $observacoes = trim($dados['observacoes'] ?? '');

        if ($fornecedorId <= 0) {
            throw new \InvalidArgumentException('Fornecedor inválido ou não informado.');
        }

        if ($valor <= 0) {
            throw new \InvalidArgumentException('O valor do recibo deve ser maior que zero.');
        }

        if (empty($descricao)) {
            throw new \InvalidArgumentException('A descrição ou referência do pagamento é obrigatória.');
        }

        if (!in_array($formaPagamento, ['pix', 'dinheiro'], true)) {
            $formaPagamento = 'pix';
        }

        // Garante que se o usuário não for válido na tabela, seja null
        if ($usuarioId !== null && $usuarioId > 0) {
            $pdoCheck = self::getConnection();
            $stmtUser = $pdoCheck->prepare("SELECT id FROM usuarios WHERE id = :uid LIMIT 1");
            $stmtUser->execute([':uid' => $usuarioId]);
            if (!$stmtUser->fetchColumn()) {
                $usuarioId = null;
            }
        } else {
            $usuarioId = null;
        }

        $numeroRecibo = self::gerarNumeroRecibo();

        $pdo = self::getConnection();
        $sql = "INSERT INTO `fornecedor_recibos` (
            `numero_recibo`, `fornecedor_id`, `valor`, `data_pagamento`, `forma_pagamento`,
            `descricao`, `observacoes`, `created_by`
        ) VALUES (
            :numero_recibo, :fornecedor_id, :valor, :data_pagamento, :forma_pagamento,
            :descricao, :observacoes, :created_by
        )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':numero_recibo'   => $numeroRecibo,
            ':fornecedor_id'   => $fornecedorId,
            ':valor'           => $valor,
            ':data_pagamento'  => $dataPagamento,
            ':forma_pagamento' => $formaPagamento,
            ':descricao'       => $descricao,
            ':observacoes'     => !empty($observacoes) ? $observacoes : null,
            ':created_by'      => $usuarioId,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Busca um recibo pelo ID com os dados do fornecedor e criador
     */
    public static function buscarPorId(int $id): ?array
    {
        $sql = "
            SELECT r.*, 
                   f.nome_fantasia as fornecedor_nome, 
                   f.razao_social as fornecedor_razao, 
                   f.cnpj_cpf as fornecedor_cnpj, 
                   f.telefone as fornecedor_telefone, 
                   f.email as fornecedor_email,
                   u.nome as usuario_nome
            FROM `fornecedor_recibos` r
            INNER JOIN `fornecedores` f ON f.id = r.fornecedor_id
            LEFT JOIN `usuarios` u ON u.id = r.created_by
            WHERE r.id = :id AND r.deleted_at IS NULL
            LIMIT 1
        ";

        return self::fetchOne($sql, [':id' => $id]) ?: null;
    }

    /**
     * Lista recibos com filtros opcionais
     */
    public static function listar(array $filtros = []): array
    {
        $sql = "
            SELECT r.*, 
                   f.nome_fantasia as fornecedor_nome, 
                   f.razao_social as fornecedor_razao, 
                   f.cnpj_cpf as fornecedor_cnpj,
                   u.nome as usuario_nome
            FROM `fornecedor_recibos` r
            INNER JOIN `fornecedores` f ON f.id = r.fornecedor_id
            LEFT JOIN `usuarios` u ON u.id = r.created_by
            WHERE r.deleted_at IS NULL
        ";
        $params = [];

        if (!empty($filtros['fornecedor_id'])) {
            $sql .= " AND r.fornecedor_id = :fornecedor_id";
            $params[':fornecedor_id'] = (int)$filtros['fornecedor_id'];
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= " AND r.data_pagamento >= :data_inicio";
            $params[':data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= " AND r.data_pagamento <= :data_fim";
            $params[':data_fim'] = $filtros['data_fim'];
        }

        if (!empty($filtros['forma_pagamento'])) {
            $sql .= " AND r.forma_pagamento = :forma_pagamento";
            $params[':forma_pagamento'] = $filtros['forma_pagamento'];
        }

        $sql .= " ORDER BY r.data_pagamento DESC, r.id DESC";

        if (!empty($filtros['limit'])) {
            $limit = (int)$filtros['limit'];
            $offset = (int)($filtros['offset'] ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        return self::fetchAll($sql, $params);
    }

    /**
     * Soft delete auditado de recibo
     */
    public static function excluir(int $id, ?int $usuarioId = null): bool
    {
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `fornecedor_recibos` 
            SET `deleted_at` = NOW() 
            WHERE `id` = :id AND `deleted_at` IS NULL
        ");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Converte valor monetário em reais para texto por extenso em português
     */
    public static function valorPorExtenso(float $valor): string
    {
        $valor = round($valor, 2);
        if ($valor <= 0) {
            return 'zero reais';
        }

        $singular = ['centavo', 'real', 'mil', 'milhão', 'bilhão'];
        $plural   = ['centavos', 'reais', 'mil', 'milhões', 'bilhões'];

        $c = ['', 'cem', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];
        $d = ['', 'dez', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
        $d10 = ['dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];
        $u = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];

        $inteiro = (int)$valor;
        $centavos = (int)round(($valor - $inteiro) * 100);

        $partes = [];
        if ($inteiro > 0) {
            $grupos = [];
            $n = $inteiro;
            while ($n > 0) {
                $grupos[] = $n % 1000;
                $n = (int)($n / 1000);
            }

            $numGrupos = count($grupos);
            for ($i = $numGrupos - 1; $i >= 0; $i--) {
                $grp = $grupos[$i];
                if ($grp === 0) continue;

                $centena = (int)($grp / 100);
                $resto = $grp % 100;
                $dezena = (int)($resto / 10);
                $unidade = $resto % 10;

                $strGrp = [];
                if ($centena === 1 && $resto === 0) {
                    $strGrp[] = 'cem';
                } elseif ($centena > 0) {
                    $strGrp[] = $c[$centena];
                }

                if ($dezena === 1) {
                    $strGrp[] = $d10[$unidade];
                } else {
                    if ($dezena > 1) {
                        $strGrp[] = $d[$dezena];
                    }
                    if ($unidade > 0) {
                        $strGrp[] = $u[$unidade];
                    }
                }

                $textoGrupo = implode(' e ', $strGrp);
                $nomeSufixo = ($grp === 1) ? ($i === 0 ? 'real' : ($i === 1 ? 'mil' : 'milhão')) : ($i === 0 ? 'reais' : ($i === 1 ? 'mil' : 'milhões'));
                
                if ($i === 1 && $grp === 1) {
                    $partes[] = "mil";
                } elseif ($i === 0) {
                    $partes[] = "{$textoGrupo} {$nomeSufixo}";
                } else {
                    $partes[] = "{$textoGrupo} {$nomeSufixo}";
                }
            }
        }

        $textoFinal = implode(' e ', $partes);

        if ($centavos > 0) {
            $strCent = [];
            $dezCent = (int)($centavos / 10);
            $uniCent = $centavos % 10;

            if ($dezCent === 1) {
                $strCent[] = $d10[$uniCent];
            } else {
                if ($dezCent > 1) {
                    $strCent[] = $d[$dezCent];
                }
                if ($uniCent > 0) {
                    $strCent[] = $u[$uniCent];
                }
            }
            $textoCentavos = implode(' e ', $strCent) . ' ' . ($centavos === 1 ? 'centavo' : 'centavos');
            if (empty($textoFinal)) {
                $textoFinal = $textoCentavos;
            } else {
                $textoFinal .= ' e ' . $textoCentavos;
            }
        }

        return $textoFinal;
    }
}
