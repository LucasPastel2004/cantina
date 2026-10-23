<?php
defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';
require_once __DIR__ . '/../app/services/PdfService.php';

$pdo = \App\Core\Model::getPdo();
$stmt = $pdo->prepare("SELECT a.id, a.nome, a.matricula FROM alunos a WHERE a.nome LIKE ? LIMIT 1");
$stmt->execute(['%ANNE%SOARES%']);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    $stmt = $pdo->prepare("SELECT a.id, a.nome, a.matricula FROM alunos a WHERE a.nome LIKE ? LIMIT 1");
    $stmt->execute(['%SOARES%']);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
}

echo "Aluno testado: ID " . $aluno['id'] . " - " . $aluno['nome'] . " (Matrícula: " . $aluno['matricula'] . ")\n";

$controller = new \App\Controllers\RelatoriosController();
$dadosExtrato = $controller->obterDadosExtratoAluno((int)$aluno['id'], '2026-10-01', '2026-10-23', false, false, 1, 1, 50, false);
$dadosExtrato['operador'] = 'Atendimento Cantina';

$html = \App\Services\PdfService::gerarEspelhoExtratoConsumoAlunoHtml($dadosExtrato);

assert(!empty($html), "HTML gerado não pode ser vazio");
assert(str_contains($html, mb_strtoupper($aluno['nome'], 'UTF-8')), "HTML deve conter o nome do aluno em maiúsculas");
assert(!str_contains($html, 'ALUNO NÃO IDENTIFICADO'), "HTML NÃO pode conter 'ALUNO NÃO IDENTIFICADO'");
assert(str_contains($html, $aluno['matricula']), "HTML deve conter a matrícula do aluno");

echo "[SUCESSO] O espelho de impressão identificou corretamente o aluno: " . $aluno['nome'] . "\n";
echo "Nome presente no HTML: " . (str_contains($html, mb_strtoupper($aluno['nome'], 'UTF-8')) ? 'SIM' : 'NÃO') . "\n";
echo "Contém 'ALUNO NÃO IDENTIFICADO': " . (str_contains($html, 'ALUNO NÃO IDENTIFICADO') ? 'SIM' : 'NÃO') . "\n";
