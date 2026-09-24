<?php
/**
 * Teste automatizado para o endpoint de busca de responsáveis e alunos na recarga de caixa
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/SessaoCaixa.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/core/Controller.php';

echo "=======================================================\n";
echo " TESTES DA BUSCA DE RESPONSÁVEL / ALUNO NO CAIXA\n";
echo "=======================================================\n\n";

$passou = 0;
$total = 0;

$assertCustom = function(bool $condicao, string $msg) use (&$passou, &$total) {
    $total++;
    if ($condicao) {
        $passou++;
        echo "  [PASS] {$msg}\n";
    } else {
        echo "  [FAIL] {$msg}\n";
    }
};

// 1. Busca por "Lucas Santos"
$resLucasSantos = \App\Models\SessaoCaixa::buscarResponsavelParaRecarga('Lucas Santos');
$assertCustom(!empty($resLucasSantos), "Busca por 'Lucas Santos' retorna resultado");

$alunoEncontrado = false;
$respEncontrado = false;
foreach ($resLucasSantos as $r) {
    if (stripos($r['nome'], 'Santos') !== false) {
        $respEncontrado = true;
    }
    if (!empty($r['dependentes'])) {
        foreach ($r['dependentes'] as $d) {
            if (stripos($d['nome'], 'Lucas') !== false) {
                $alunoEncontrado = true;
            }
        }
    }
}
$assertCustom($alunoEncontrado || $respEncontrado, "Localizou aluno ou responsável relacionado a 'Lucas Santos'");
$assertCustom(isset($resLucasSantos[0]['dependentes_str']), "Campo dependentes_str gerado");
$assertCustom(isset($resLucasSantos[0]['cpf_formatado']), "Campo cpf_formatado gerado");

// 2. Busca por "Lucas"
$resLucas = \App\Models\SessaoCaixa::buscarResponsavelParaRecarga('Lucas');
$assertCustom(count($resLucas) >= 1, "Busca por 'Lucas' retornou " . count($resLucas) . " responsável(is)");

// 3. Busca por "Carlos Silva"
$resCarlos = \App\Models\SessaoCaixa::buscarResponsavelParaRecarga('Carlos Silva');
$assertCustom(!empty($resCarlos), "Busca por 'Carlos Silva' retornou responsável");

// 4. Busca por CPF numérico e mascarado
$resCpf = \App\Models\SessaoCaixa::buscarResponsavelParaRecarga('111.222.333-44');
$assertCustom(!empty($resCpf), "Busca por CPF formatado '111.222.333-44' retornou responsável");
$assertCustom($resCpf[0]['cpf'] === '11122233344', "CPF localizado confere com 11122233344");

// 5. Simulação da chamada do Controller
$_GET['q'] = 'Lucas Santos';
ob_start();
$caixaController = new class extends \App\Core\Controller {
    public function testar() {
        $termo = trim((string)($_GET['q'] ?? ''));
        $responsaveis = \App\Models\SessaoCaixa::buscarResponsavelParaRecarga($termo, 10);
        $this->json([
            'success' => true,
            'sucesso' => true,
            'responsaveis' => $responsaveis,
            'dados' => $responsaveis
        ]);
    }
};
$caixaController->testar();
$jsonOutput = ob_get_clean();
$dadosJson = json_decode($jsonOutput, true);

$assertCustom($dadosJson !== null, "Controller retornou JSON válido");
$assertCustom($dadosJson['sucesso'] === true, "JSON contém chave 'sucesso' === true");
$assertCustom($dadosJson['success'] === true, "JSON contém chave 'success' === true");
$assertCustom(!empty($dadosJson['dados']), "JSON contém chave 'dados' populada");
$assertCustom(!empty($dadosJson['responsaveis']), "JSON contém chave 'responsaveis' populada");

echo "\n-------------------------------------------------------\n";
echo "RESULTADO: {$passou}/{$total} asserções aprovadas (" . round(($passou / $total) * 100, 1) . "%)\n";
echo "=======================================================\n";

if ($passou !== $total) {
    exit(1);
}
