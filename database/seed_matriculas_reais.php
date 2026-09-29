<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Seeder Idempotente de Matrículas Oficiais Reais da Escola
 * 
 * Fonte: docs/alterações sistema guideline/Matriculas.pdf (23 páginas)
 * Conforme docs/FSD.md, docs/PLANO.md (Fase 18) e docs/STATUS.md
 */

if (!defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

// Suporte tanto para execução CLI quanto HTTP (com token de segurança)
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../config/config.php';
    $tokenEnviado = $_GET['token'] ?? '';
    $tokenConfig = defined('MIGRATION_TOKEN') ? MIGRATION_TOKEN : 'cantina_secret_token_change_me';
    if (empty($tokenEnviado) || !hash_equals($tokenConfig, $tokenEnviado)) {
        http_response_code(403);
        die('Acesso negado: token de segurança inválido ou ausente.');
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';

use App\Models\Turma;
use App\Models\Responsavel;
use App\Models\Aluno;

echo "========================================================================\n";
echo "   CANTINA WEB — CARGA DE MATRÍCULAS OFICIAIS (COLÉGIO MONTEIRO LOBATO)  \n";
echo "========================================================================\n\n";

$jsonPath = __DIR__ . '/matriculas_reais.json';
if (!file_exists($jsonPath)) {
    die("ERRO: Arquivo 'database/matriculas_reais.json' não encontrado. Execute o parser primeiro.\n");
}

$dadosJson = json_decode(file_get_contents($jsonPath), true);
if (!is_array($dadosJson)) {
    die("ERRO: Falha ao decodificar JSON de matrículas reais.\n");
}

echo "Dados carregados do JSON: " . count($dadosJson) . " agrupamentos de responsáveis familiares.\n\n";

$pdo = Database::getInstance();
$pdo->beginTransaction();

try {
    // ------------------------------------------------------------------------
    // 1. Definição e Sincronização das 19 Turmas Oficiais
    // ------------------------------------------------------------------------
    echo "--- 1. SINCRONIZANDO AS 19 TURMAS OFICIAIS DO COLÉGIO ---\n";

    $turmasOficiais = [
        // Educação Infantil (Vespertino)
        ['serie_raw' => 'MATERNAL', 'letra' => 'A', 'serie' => 'Educação Infantil', 'nome_turma' => 'Maternal A', 'turno' => 'vespertino', 'ordem' => 1],
        ['serie_raw' => 'JARDIM I', 'letra' => 'A', 'serie' => 'Educação Infantil', 'nome_turma' => 'Jardim I A', 'turno' => 'vespertino', 'ordem' => 2],
        ['serie_raw' => 'JARDIM II', 'letra' => 'A', 'serie' => 'Educação Infantil', 'nome_turma' => 'Jardim II A', 'turno' => 'vespertino', 'ordem' => 3],
        ['serie_raw' => 'JARDIM II', 'letra' => 'B', 'serie' => 'Educação Infantil', 'nome_turma' => 'Jardim II B', 'turno' => 'vespertino', 'ordem' => 4],
        ['serie_raw' => 'JARDIM III', 'letra' => 'A', 'serie' => 'Educação Infantil', 'nome_turma' => 'Jardim III A', 'turno' => 'vespertino', 'ordem' => 5],
        ['serie_raw' => 'JARDIM III', 'letra' => 'B', 'serie' => 'Educação Infantil', 'nome_turma' => 'Jardim III B', 'turno' => 'vespertino', 'ordem' => 6],

        // Ensino Fundamental I (Vespertino)
        ['serie_raw' => '1º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '1º Ano A', 'turno' => 'vespertino', 'ordem' => 7],
        ['serie_raw' => '1º ANO', 'letra' => 'B', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '1º Ano B', 'turno' => 'vespertino', 'ordem' => 8],
        ['serie_raw' => '2º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '2º Ano A', 'turno' => 'vespertino', 'ordem' => 9],
        ['serie_raw' => '3º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '3º Ano A', 'turno' => 'vespertino', 'ordem' => 10],
        ['serie_raw' => '3º ANO', 'letra' => 'B', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '3º Ano B', 'turno' => 'vespertino', 'ordem' => 11],
        ['serie_raw' => '4º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '4º Ano A', 'turno' => 'vespertino', 'ordem' => 12],

        // Ensino Fundamental I & II (Matutino)
        ['serie_raw' => '5º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental I', 'nome_turma' => '5º Ano A', 'turno' => 'matutino', 'ordem' => 13],
        ['serie_raw' => '6º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental II', 'nome_turma' => '6º Ano A', 'turno' => 'matutino', 'ordem' => 14],
        ['serie_raw' => '7º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental II', 'nome_turma' => '7º Ano A', 'turno' => 'matutino', 'ordem' => 15],
        ['serie_raw' => '8º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental II', 'nome_turma' => '8º Ano A', 'turno' => 'matutino', 'ordem' => 16],
        ['serie_raw' => '9º ANO', 'letra' => 'A', 'serie' => 'Ensino Fundamental II', 'nome_turma' => '9º Ano A', 'turno' => 'matutino', 'ordem' => 17],

        // Ensino Médio (Matutino)
        ['serie_raw' => '1ª SÉRIE', 'letra' => 'A', 'serie' => 'Ensino Médio', 'nome_turma' => '1ª Série A', 'turno' => 'matutino', 'ordem' => 18],

        // Apoio Especial (Matutino)
        ['serie_raw' => 'NÚCLEO DE APOIO INFANTIL', 'letra' => 'A', 'serie' => 'Educação Infantil / Apoio', 'nome_turma' => 'Núcleo de Apoio Infantil A', 'turno' => 'matutino', 'ordem' => 19],
    ];

    $mapaTurmasIds = [];
    $turmasCriadas = 0;
    $turmasAtualizadas = 0;
    $now = date('Y-m-d H:i:s');

    foreach ($turmasOficiais as $tDef) {
        $chaveLookup = mb_strtoupper("{$tDef['serie_raw']}|{$tDef['letra']}|{$tDef['turno']}", 'UTF-8');
        $nomeTurma = $tDef['nome_turma'];
        $turno = $tDef['turno'];

        // Buscar se já existe turma ativa ou inativa com nome e turno compatíveis
        $stmt = $pdo->prepare("SELECT id, serie, nome_turma, ordem_impressao, deleted_at FROM turmas 
                               WHERE LOWER(TRIM(nome_turma)) = LOWER(TRIM(:nome)) 
                               AND turno = :turno 
                               LIMIT 1");
        $stmt->execute(['nome' => $nomeTurma, 'turno' => $turno]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $turmaId = (int)$existente['id'];
            // Atualizar e reativar caso estivesse soft-deleted
            $stmtUp = $pdo->prepare("UPDATE turmas SET 
                                        serie = :serie, 
                                        ordem_impressao = :ordem, 
                                        deleted_at = NULL, 
                                        updated_at = :updated_at 
                                     WHERE id = :id");
            $stmtUp->execute([
                'serie'      => $tDef['serie'],
                'ordem'      => $tDef['ordem'],
                'updated_at' => $now,
                'id'         => $turmaId
            ]);
            $turmasAtualizadas++;
        } else {
            // Inserir nova turma
            $stmtIns = $pdo->prepare("INSERT INTO turmas (serie, nome_turma, turno, ordem_impressao, created_at, updated_at) 
                                      VALUES (:serie, :nome, :turno, :ordem, :created_at, :updated_at)");
            $stmtIns->execute([
                'serie'      => $tDef['serie'],
                'nome'       => $nomeTurma,
                'turno'      => $turno,
                'ordem'      => $tDef['ordem'],
                'created_at' => $now,
                'updated_at' => $now
            ]);
            $turmaId = (int)$pdo->lastInsertId();
            $turmasCriadas++;
        }

        $mapaTurmasIds[$chaveLookup] = $turmaId;
        // Mapeamentos secundários de tolerância
        $mapaTurmasIds[mb_strtoupper("{$tDef['nome_turma']}|{$tDef['turno']}", 'UTF-8')] = $turmaId;
        $mapaTurmasIds[mb_strtoupper("{$tDef['serie_raw']} / {$tDef['letra']}|{$tDef['turno']}", 'UTF-8')] = $turmaId;
    }

    echo "Turmas oficiais sincronizadas: 19 (Criadas: {$turmasCriadas}, Atualizadas: {$turmasAtualizadas})\n\n";

    // ------------------------------------------------------------------------
    // 2. Carga e Sincronização dos Responsáveis Financeiros (~195 únicos)
    // ------------------------------------------------------------------------
    echo "--- 2. CARREGANDO RESPONSÁVEIS FINANCEIROS REAIS ---\n";

    $mapaResponsaveisCpf = [];
    $respCriados = 0;
    $respAtualizados = 0;

    foreach ($dadosJson as $g) {
        $rf = $g['responsavel_financeiro'];
        $cpfLimpo = preg_replace('/\D/', '', $rf['cpf_limpo'] ?? '');
        if (strlen($cpfLimpo) !== 11) {
            echo "AVISO: CPF inválido ignorado: " . ($rf['cpf_limpo'] ?? '') . "\n";
            continue;
        }

        $nomeResp = trim($rf['nome']);
        $whatsapp = trim($rf['whatsapp'] ?? '');
        $emailPadrao = $cpfLimpo . '@colegiomonteirolobato.com.br';

        $resResp = Responsavel::localizarOuCriarPorCpf([
            'cpf'      => $cpfLimpo,
            'nome'     => $nomeResp,
            'whatsapp' => $whatsapp,
            'email'    => $emailPadrao
        ]);

        $respId = (int)$resResp['id'];
        if ($resResp['novo']) {
            $respCriados++;
        } else {
            $respAtualizados++;
        }

        $mapaResponsaveisCpf[$cpfLimpo] = $respId;
    }

    echo "Responsáveis financeiros processados: " . count($mapaResponsaveisCpf) . " (Novos: {$respCriados}, Atualizados: {$respAtualizados})\n\n";

    // ------------------------------------------------------------------------
    // 3. Carga e Vínculo dos 232 Alunos Reais
    // ------------------------------------------------------------------------
    echo "--- 3. CARREGANDO ALUNOS REAIS E VÍNCULOS DE TURMA/RESPONSÁVEL ---\n";

    $matriculasOficiais = [];
    $alunosCriados = 0;
    $alunosAtualizados = 0;
    $alunosPorTurno = ['matutino' => 0, 'vespertino' => 0];

    foreach ($dadosJson as $g) {
        $rf = $g['responsavel_financeiro'];
        $cpfLimpo = preg_replace('/\D/', '', $rf['cpf_limpo'] ?? '');
        $respId = $mapaResponsaveisCpf[$cpfLimpo] ?? null;

        if (!$respId) {
            echo "ERRO: Responsável ID não encontrado para CPF {$cpfLimpo}\n";
            continue;
        }

        foreach ($g['alunos'] as $al) {
            $matricula = trim($al['matricula']);
            $nomeAluno = trim($al['nome']);
            $turno = $al['turno'];
            $serieRaw = $al['serie'];
            $letra = $al['turma_letra'];

            $chaveLookup = mb_strtoupper("{$serieRaw}|{$letra}|{$turno}", 'UTF-8');
            $turmaId = $mapaTurmasIds[$chaveLookup] ?? null;

            if (!$turmaId) {
                // Tenta fallback com nome turma
                $chaveFallback = mb_strtoupper("{$al['nome_turma']}|{$turno}", 'UTF-8');
                $turmaId = $mapaTurmasIds[$chaveFallback] ?? null;
            }

            if (!$turmaId) {
                echo "ERRO: Turma não resolvida para o aluno {$matricula} - {$nomeAluno} ({$al['turma_original']})\n";
                continue;
            }

            $resAluno = Aluno::localizarOuCriarPorMatricula([
                'matricula'      => $matricula,
                'nome'           => $nomeAluno,
                'turma_id'       => $turmaId,
                'responsavel_id' => $respId,
                'turno'          => $turno,
                'ativo'          => 1
            ]);

            if ($resAluno['novo']) {
                $alunosCriados++;
            } else {
                $alunosAtualizados++;
            }

            $matriculasOficiais[] = $matricula;
            $alunosPorTurno[$turno]++;
        }
    }

    echo "Alunos oficiais processados: " . count($matriculasOficiais) . " (Novos: {$alunosCriados}, Atualizados: {$alunosAtualizados})\n";
    echo "  - Turno Matutino: {$alunosPorTurno['matutino']} alunos\n";
    echo "  - Turno Vespertino: {$alunosPorTurno['vespertino']} alunos\n\n";

    // ------------------------------------------------------------------------
    // 4. Saneamento e Convivência Segura com Fixtures de Regressão
    // ------------------------------------------------------------------------
    echo "--- 4. SANEAMENTO E CONVIVÊNCIA SEGURA COM FIXTURES DE REGRESSÃO ---\n";

    // Garantir que todos os alunos oficiais estejam 100% ativos e desmarcados de soft delete
    $inClause = "'" . implode("','", array_map('addslashes', $matriculasOficiais)) . "'";
    $stmtReativar = $pdo->prepare("UPDATE alunos SET ativo = 1, deleted_at = NULL WHERE matricula IN ({$inClause})");
    $stmtReativar->execute();
    echo "Alunos oficiais garantidos ativos e íntegros: " . count($matriculasOficiais) . "\n\n";

    // ------------------------------------------------------------------------
    // 5. Validação de Integridade e Constraints
    // ------------------------------------------------------------------------
    echo "--- 5. AUDITORIA DE INTEGRIDADE REFERENCIAL ---\n";

    // Verificar se existe algum aluno sem responsável válido
    $orfaosResp = (int)$pdo->query("SELECT COUNT(*) FROM alunos a 
                                     LEFT JOIN responsaveis r ON r.id = a.responsavel_id 
                                     WHERE a.deleted_at IS NULL AND r.id IS NULL")->fetchColumn();

    // Verificar se existe algum aluno sem turma válida
    $orfaosTurma = (int)$pdo->query("SELECT COUNT(*) FROM alunos a 
                                      LEFT JOIN turmas t ON t.id = a.turma_id 
                                      WHERE a.deleted_at IS NULL AND t.id IS NULL")->fetchColumn();

    // Verificar total de alunos ativos
    $totalAlunosAtivos = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE deleted_at IS NULL AND ativo = 1")->fetchColumn();

    // Verificar total de turmas ativas
    $totalTurmasAtivas = (int)$pdo->query("SELECT COUNT(*) FROM turmas WHERE deleted_at IS NULL")->fetchColumn();

    // Verificar total de responsáveis
    $totalResponsaveis = (int)$pdo->query("SELECT COUNT(*) FROM responsaveis")->fetchColumn();

    // Verificar usuários da cantina preservados
    $totalUsuarios = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE deleted_at IS NULL")->fetchColumn();

    echo "Alunos ativos oficiais no banco: {$totalAlunosAtivos}\n";
    echo "Turmas ativas no banco: {$totalTurmasAtivas}\n";
    echo "Responsáveis no banco: {$totalResponsaveis}\n";
    echo "Usuários operadores/admin preservados: {$totalUsuarios}\n";
    echo "Alunos sem responsável: {$orfaosResp}\n";
    echo "Alunos sem turma: {$orfaosTurma}\n";

    if ($orfaosResp > 0 || $orfaosTurma > 0) {
        throw new \RuntimeException("Falha de integridade referencial: encontrados alunos órfãos!");
    }

    $pdo->commit();

    echo "\n========================================================================\n";
    echo "   [SUCESSO] CARGA DE MATRÍCULAS REAIS CONCLUÍDA E HOMOLOGADA 100%!       \n";
    echo "========================================================================\n";

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERRO CRÍTICO NA EXECUÇÃO DO SEEDER]: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . " no arquivo " . $e->getFile() . "\n";
    exit(1);
}
