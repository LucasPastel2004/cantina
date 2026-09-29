<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Fase 18 — Carga e Saneamento de Dados Oficiais da Escola (Matrículas Reais)
 * 
 * Execução: docker compose exec app php tests/fase18_test.php
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/Usuario.php';

use App\Models\Turma;
use App\Models\Responsavel;
use App\Models\Aluno;
use App\Models\Usuario;

$totalAssercoes = 0;
$totalSucessos = 0;

function assertFase18(bool $condicao, string $descricao): void
{
    global $totalAssercoes, $totalSucessos;
    $totalAssercoes++;
    if ($condicao) {
        $totalSucessos++;
        echo "  [OK] " . $descricao . "\n";
    } else {
        echo "  [FALHA] " . $descricao . "\n";
    }
}

echo "========================================================================\n";
echo "   SUÍTE DE TESTES AUTOMATIZADOS — FASE 18 (MATRÍCULAS REAIS)          \n";
echo "========================================================================\n\n";

$pdo = Database::getInstance();

// Carregar dados de referência oficial
$jsonMatriculas = json_decode(file_get_contents(__DIR__ . '/../database/matriculas_reais.json'), true);
$matsEsperadas = [];
$matsMatutino = [];
$matsVespertino = [];
foreach ($jsonMatriculas as $g) {
    foreach ($g['alunos'] as $al) {
        $matsEsperadas[] = $al['matricula'];
        if ($al['turno'] === 'matutino') {
            $matsMatutino[] = $al['matricula'];
        } else {
            $matsVespertino[] = $al['matricula'];
        }
    }
}

// ------------------------------------------------------------------------
// Teste 1: Turmas Oficiais Escolares
// ------------------------------------------------------------------------
echo "--- TESTE 1: TURMAS ESCOLARES OFICIAIS (19 TURMAS) ---\n";

$turmasAtivas = Turma::listarTodas();
assertFase18(count($turmasAtivas) >= 19, "Ao menos 19 turmas ativas cadastradas no sistema");

$turmasEsperadas = [
    'Maternal A' => 'vespertino',
    'Jardim I A' => 'vespertino',
    'Jardim II A' => 'vespertino',
    'Jardim II B' => 'vespertino',
    'Jardim III A' => 'vespertino',
    'Jardim III B' => 'vespertino',
    '1º Ano A' => 'vespertino',
    '1º Ano B' => 'vespertino',
    '2º Ano A' => 'vespertino',
    '3º Ano A' => 'vespertino',
    '3º Ano B' => 'vespertino',
    '4º Ano A' => 'vespertino',
    '5º Ano A' => 'matutino',
    '6º Ano A' => 'matutino',
    '7º Ano A' => 'matutino',
    '8º Ano A' => 'matutino',
    '9º Ano A' => 'matutino',
    '1ª Série A' => 'matutino',
    'Núcleo de Apoio Infantil A' => 'matutino'
];

$turmasEncontradasCount = 0;
foreach ($turmasEsperadas as $nomeEsperado => $turnoEsperado) {
    $encontrada = false;
    foreach ($turmasAtivas as $t) {
        if (strcasecmp($t['nome_turma'], $nomeEsperado) === 0 && $t['turno'] === $turnoEsperado) {
            $encontrada = true;
            $turmasEncontradasCount++;
            break;
        }
    }
    assertFase18($encontrada, "Turma '{$nomeEsperado}' ({$turnoEsperado}) presente e ativa");
}
assertFase18($turmasEncontradasCount === 19, "Todas as 19 turmas oficiais homologadas no sistema");

// ------------------------------------------------------------------------
// Teste 2: Alunos Oficiais da Escola (232 Matrículas)
// ------------------------------------------------------------------------
echo "\n--- TESTE 2: ALUNOS OFICIAIS E MATRÍCULAS (232 ALUNOS) ---\n";

$inOficiais = "'" . implode("','", array_map('addslashes', $matsEsperadas)) . "'";
$totalOficiaisAtivos = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE matricula IN ({$inOficiais}) AND ativo = 1 AND deleted_at IS NULL")->fetchColumn();
assertFase18($totalOficiaisAtivos === 232, "100% dos 232 alunos oficiais da escola estão ativos e cadastrados");

$oficiaisMatutino = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE matricula IN ({$inOficiais}) AND turno = 'matutino' AND ativo = 1 AND deleted_at IS NULL")->fetchColumn();
$oficiaisVespertino = (int)$pdo->query("SELECT COUNT(*) FROM alunos WHERE matricula IN ({$inOficiais}) AND turno = 'vespertino' AND ativo = 1 AND deleted_at IS NULL")->fetchColumn();

assertFase18($oficiaisMatutino === 67, "Turno Matutino oficial possui exatamente 67 alunos reais");
assertFase18($oficiaisVespertino === 165, "Turno Vespertino oficial possui exatamente 165 alunos reais");
assertFase18(($oficiaisMatutino + $oficiaisVespertino) === 232, "Soma de alunos oficiais dos turnos igual a 232");

// Checar formato das matrículas oficiais (8 dígitos numéricos únicos)
$matsValidas = 0;
foreach ($matsEsperadas as $m) {
    if (preg_match('/^\d{8}$/', $m)) {
        $matsValidas++;
    }
}
assertFase18($matsValidas === 232, "100% dos 232 alunos oficiais possuem matrícula com exatamente 8 dígitos numéricos");
assertFase18(count(array_unique($matsEsperadas)) === 232, "100% das 232 matrículas oficiais são estritamente únicas");

// ------------------------------------------------------------------------
// Teste 3: Integridade Referencial (Zero Órfãos)
// ------------------------------------------------------------------------
echo "\n--- TESTE 3: INTEGRIDADE REFERENCIAL E VÍNCULOS RELACIONAIS ---\n";

$orfaosResp = (int)$pdo->query("SELECT COUNT(*) FROM alunos a 
                                 LEFT JOIN responsaveis r ON r.id = a.responsavel_id 
                                 WHERE a.deleted_at IS NULL AND a.ativo = 1 AND r.id IS NULL")->fetchColumn();
assertFase18($orfaosResp === 0, "Zero alunos órfãos de responsável financeiro");

$orfaosTurma = (int)$pdo->query("SELECT COUNT(*) FROM alunos a 
                                  LEFT JOIN turmas t ON t.id = a.turma_id 
                                  WHERE a.deleted_at IS NULL AND a.ativo = 1 AND t.id IS NULL")->fetchColumn();
assertFase18($orfaosTurma === 0, "Zero alunos órfãos de turma escolar");

$alunosComTurmaInativa = (int)$pdo->query("SELECT COUNT(*) FROM alunos a 
                                           INNER JOIN turmas t ON t.id = a.turma_id 
                                           WHERE a.matricula IN ({$inOficiais}) AND a.deleted_at IS NULL AND a.ativo = 1 AND t.deleted_at IS NOT NULL")->fetchColumn();
assertFase18($alunosComTurmaInativa === 0, "Todos os 232 alunos oficiais vinculados a turmas ativas da escola");

// ------------------------------------------------------------------------
// Teste 4: Responsáveis Financeiros Familiares e Carteira Unificada de Irmãos
// ------------------------------------------------------------------------
echo "\n--- TESTE 4: RESPONSÁVEIS FAMILIARES E CARTEIRA UNIFICADA (IRMÃOS) ---\n";

$totalResponsaveis = (int)$pdo->query("SELECT COUNT(*) FROM responsaveis")->fetchColumn();
assertFase18($totalResponsaveis >= 195, "Pelo menos 195 responsáveis financeiros cadastrados no sistema (total: {$totalResponsaveis})");

// Validação de CPFs com 11 dígitos
$cpfsInvalidos = (int)$pdo->query("SELECT COUNT(*) FROM responsaveis WHERE LENGTH(cpf) != 11 OR cpf REGEXP '[^0-9]'")->fetchColumn();
assertFase18($cpfsInvalidos === 0, "100% dos responsáveis possuem CPF numérico limpo de 11 dígitos");

// Verificar irmãos com mesma carteira (ex: Helena e Heitor - CPF 03135221113)
$respCintia = Responsavel::buscarPorCpf('03135221113');
assertFase18($respCintia !== null, "Responsável familiar Cíntia Campos Moreira localizada com sucesso por CPF");

if ($respCintia) {
    $dependentesCintia = (new Responsavel())->getDependentes((int)$respCintia['id']);
    assertFase18(count($dependentesCintia) >= 2, "Responsável possui múltiplos dependentes vinculados na mesma carteira (encontrados: " . count($dependentesCintia) . ")");
    $nomesDep = array_column($dependentesCintia, 'nome');
    $temHelena = false;
    $temHeitor = false;
    foreach ($nomesDep as $n) {
        if (stripos($n, 'HELENA') !== false) $temHelena = true;
        if (stripos($n, 'HEITOR') !== false) $temHeitor = true;
    }
    assertFase18($temHelena && $temHeitor, "Irmãos Helena e Heitor devidamente vinculados ao mesmo responsável financeiro");
}

// ------------------------------------------------------------------------
// Teste 5: Preservação de Usuários da Cantina e Dados Operacionais
// ------------------------------------------------------------------------
echo "\n--- TESTE 5: PRESERVAÇÃO DE USUÁRIOS E DADOS OPERACIONAIS DA CANTINA ---\n";

$usuarios = Usuario::listarTodos();
assertFase18(count($usuarios) >= 2, "Usuários da equipe da cantina preservados intactos");

$temAdmin = false;
$temOperador = false;
foreach ($usuarios as $u) {
    if ($u['perfil'] === 'administrador') $temAdmin = true;
    if ($u['perfil'] === 'operador') $temOperador = true;
}
assertFase18($temAdmin, "Usuário com perfil administrador preservado e ativo");
assertFase18($temOperador, "Usuário com perfil operador de caixa preservado e ativo");

$totalProdutos = (int)$pdo->query("SELECT COUNT(*) FROM produtos WHERE deleted_at IS NULL")->fetchColumn();
assertFase18($totalProdutos > 0, "Catálogo de produtos da cantina preservado intacto (produtos ativos: {$totalProdutos})");

// ------------------------------------------------------------------------
// Teste 6: Consulta e Compatibilidade com Models da Aplicação
// ------------------------------------------------------------------------
echo "\n--- TESTE 6: CONSULTA E LISTAGEM VIA MODELS DO SISTEMA ---\n";

// Buscar aluno por matrícula
$alunoLaura = Aluno::buscarPorMatricula('00001713');
assertFase18($alunoLaura !== null, "Aluno 'LAURA MOREIRA NEVES' (00001713) localizado por matrícula");
assertFase18($alunoLaura && stripos($alunoLaura['nome'], 'LAURA') !== false, "Nome do aluno confere com registro oficial");

// Buscar turma do 8º Ano
$turma8 = Turma::buscarPorNomeETurno('8º Ano A', 'matutino');
assertFase18($turma8 !== null, "Turma '8º Ano A' (matutino) localizada via Turma::buscarPorNomeETurno()");
if ($turma8) {
    $alunosTurma8 = Aluno::listarTodos(['turma_id' => $turma8['id'], 'status' => 'ativos']);
    assertFase18(count($alunosTurma8) > 0, "Turma 8º Ano A possui alunos matriculados (total: " . count($alunosTurma8) . ")");
}

// Teste de busca textual de alunos
$buscaAlunos = Aluno::listarTodos(['busca' => 'LAURA']);
assertFase18(count($buscaAlunos) > 0, "Busca textual por 'LAURA' retorna registros com sucesso");

echo "\n========================================================================\n";
echo "   RESULTADO FINAL DOS TESTES DA FASE 18: {$totalSucessos} / {$totalAssercoes} ASSERÇÕES APROVADAS\n";
echo "========================================================================\n";

if ($totalSucessos === $totalAssercoes) {
    echo ">>> TODOS OS TESTES DA FASE 18 FORAM APROVADOS COM 100% DE SUCESSO! <<<\n";
    exit(0);
} else {
    echo ">>> ATENÇÃO: HOUVE FALHA EM UMA OU MAIS ASSERÇÕES DA FASE 18! <<<\n";
    exit(1);
}
