<?php
/**
 * Teste automatizado para o recurso de autocomplete de sugestões e filtros da tela de contestações MED
 */
define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/database.php';
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

echo "=================================================================\n";
echo " TESTES DO AUTOCOMPLETE E FILTROS DE CONTESTAÇÕES MED\n";
echo "=================================================================\n\n";

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

// -----------------------------------------------------------------
// 1. Validação do Filtro por Status em Responsavel::listarNegativadosMed
// -----------------------------------------------------------------
echo "\n--- 1. Testes de Filtro de Status em Responsavel::listarNegativadosMed ---\n";

// Apenas Bloqueados MED (deve vir apenas bloqueado_med = 1)
$resBloqueados = \App\Models\Responsavel::listarNegativadosMed(['status' => 'bloqueados']);
$somenteBloqueados = true;
$carlosEncontrado = false;
$familiaAlertaEncontrada = false;

foreach ($resBloqueados['itens'] as $item) {
    if ((int)$item['bloqueado_med'] !== 1) {
        $somenteBloqueados = false;
    }
    if ($item['nome'] === 'Carlos Eduardo Ribeiro') {
        $carlosEncontrado = true;
    }
    if (str_contains($item['nome'], 'Família Alerta')) {
        $familiaAlertaEncontrada = true;
    }
}

$assertCustom($somenteBloqueados, "Filtro 'bloqueados' traz exclusivamente registros com bloqueado_med = 1");
$assertCustom($carlosEncontrado, "Carlos Eduardo Ribeiro presente em 'bloqueados'");
$assertCustom(!$familiaAlertaEncontrada, "Família Alerta (apenas saldo negativo, sem bloqueio) NÃO aparece em 'bloqueados'");

// Apenas Saldo Devedor (< 0)
$resNegativos = \App\Models\Responsavel::listarNegativadosMed(['status' => 'negativos']);
$somenteNegativos = true;
$carlosNeg = false;
$familiaAlertaNeg = false;

foreach ($resNegativos['itens'] as $item) {
    if ((float)$item['saldo_atual'] >= 0) {
        $somenteNegativos = false;
    }
    if ($item['nome'] === 'Carlos Eduardo Ribeiro') $carlosNeg = true;
    if (str_contains($item['nome'], 'Família Alerta')) $familiaAlertaNeg = true;
}

$assertCustom($somenteNegativos, "Filtro 'negativos' traz exclusivamente registros com saldo_atual < 0");
$assertCustom($carlosNeg && $familiaAlertaNeg, "Tanto Carlos quanto Família Alerta constam em 'negativos'");

// -----------------------------------------------------------------
// 2. Validação da Busca Textual em Responsavel::listarNegativadosMed
// -----------------------------------------------------------------
echo "\n--- 2. Testes de Busca Textual em Responsavel::listarNegativadosMed ---\n";

// Busca por "familia"
$resBuscaFamilia = \App\Models\Responsavel::listarNegativadosMed(['busca' => 'familia']);
$apenasFamilia = true;
foreach ($resBuscaFamilia['itens'] as $item) {
    if (!str_contains(strtolower($item['nome']), 'família') && !str_contains(strtolower($item['nome']), 'familia')) {
        $apenasFamilia = false;
    }
}
$assertCustom($apenasFamilia && count($resBuscaFamilia['itens']) >= 1, "Busca por 'familia' retorna apenas registros compatíveis");

$carlosEmFamilia = false;
foreach ($resBuscaFamilia['itens'] as $item) {
    if ($item['nome'] === 'Carlos Eduardo Ribeiro') $carlosEmFamilia = true;
}
$assertCustom(!$carlosEmFamilia, "Carlos Eduardo Ribeiro NÃO é retornado ao pesquisar 'familia'");

// Busca por "carlos"
$resBuscaCarlos = \App\Models\Responsavel::listarNegativadosMed(['busca' => 'carlos']);
$carlosAchado = false;
foreach ($resBuscaCarlos['itens'] as $item) {
    if (str_contains(strtolower($item['nome']), 'carlos')) $carlosAchado = true;
}
$assertCustom($carlosAchado, "Busca por 'carlos' retorna Carlos Eduardo Ribeiro");

// Busca por dependente "Mariana" (garante vínculo prévio se não existir)
$pdo = \Database::getInstance();
$alunoMariana = $pdo->query("SELECT id FROM alunos WHERE responsavel_id = 10 AND nome LIKE '%Mariana%' LIMIT 1")->fetch();
if (!$alunoMariana) {
    $turmaId = (int)($pdo->query("SELECT id FROM turmas LIMIT 1")->fetchColumn() ?: 1);
    $pdo->exec("INSERT INTO alunos (responsavel_id, turma_id, nome, matricula, created_at, updated_at) VALUES (10, {$turmaId}, 'Mariana Ribeiro', 'MAT-MED-10', NOW(), NOW())");
}

$resBuscaMariana = \App\Models\Responsavel::listarNegativadosMed(['busca' => 'Mariana']);
$marianaRespAchado = false;
foreach ($resBuscaMariana['itens'] as $item) {
    if ($item['nome'] === 'Carlos Eduardo Ribeiro') $marianaRespAchado = true;
}
$assertCustom($marianaRespAchado, "Busca pelo nome da aluna dependente 'Mariana' retorna o responsável Carlos Eduardo Ribeiro");

// Busca por CPF com pontuação
$resBuscaCpfFormatado = \App\Models\Responsavel::listarNegativadosMed(['busca' => '555.666']);
$assertCustom(!empty($resBuscaCpfFormatado['itens']), "Busca por CPF formatado ('555.666') localiza responsável correspondente");

// Filtro combinado: busca "familia" + status "bloqueados"
$resCombinado = \App\Models\Responsavel::listarNegativadosMed(['busca' => 'familia', 'status' => 'bloqueados']);
$assertCustom(count($resCombinado['itens']) === 0, "Busca 'familia' com status 'bloqueados' retorna 0 itens (nenhuma família bloqueada)");

// -----------------------------------------------------------------
// 3. Validação do Método de Sugestões: Responsavel::buscarSugestoesMed
// -----------------------------------------------------------------
echo "\n--- 3. Testes do Autocomplete Responsavel::buscarSugestoesMed ---\n";

$sugestoesCarlos = \App\Models\Responsavel::buscarSugestoesMed('carlos');
$assertCustom(!empty($sugestoesCarlos), "buscarSugestoesMed('carlos') retorna sugestões");
$primeiroCarlos = $sugestoesCarlos[0] ?? [];
$assertCustom(isset($primeiroCarlos['id'], $primeiroCarlos['nome'], $primeiroCarlos['cpf'], $primeiroCarlos['saldo_fmt'], $primeiroCarlos['badge'], $primeiroCarlos['dependentes']), "Sugestão contém todos os campos necessários para renderização UI");
$assertCustom($primeiroCarlos['badge'] === 'Bloqueado MED', "Carlos possui badge 'Bloqueado MED'");

$sugestoesFamilia = \App\Models\Responsavel::buscarSugestoesMed('familia');
$assertCustom(!empty($sugestoesFamilia), "buscarSugestoesMed('familia') retorna famílias cadastradas");
$assertCustom($sugestoesFamilia[0]['is_negativado'] === true, "Primeira sugestão de família é a que possui restrição/débito");

$sugestoesVazia = \App\Models\Responsavel::buscarSugestoesMed('');
$assertCustom(empty($sugestoesVazia), "buscarSugestoesMed('') com termo vazio retorna array vazio");

// -----------------------------------------------------------------
// 4. Validação da Rota e Controller: AdminController::contestacoesSugestoes
// -----------------------------------------------------------------
echo "\n--- 4. Testes do Endpoint AdminController::contestacoesSugestoes ---\n";

// Simula chamada ao controller
$_GET['q'] = 'carlos';
$adminController = new \App\Controllers\AdminController();

ob_start();
$adminController->contestacoesSugestoes();
$outputJson = ob_get_clean();

$jsonDecoded = json_decode($outputJson, true);
$assertCustom(is_array($jsonDecoded) && !empty($jsonDecoded['sucesso']), "Endpoint de sugestões retornou JSON com sucesso");
$assertCustom(!empty($jsonDecoded['sugestoes']), "Array de sugestões veio preenchido no JSON");
$assertCustom($jsonDecoded['termo'] === 'carlos', "Termo da busca retornado no payload JSON");

// -----------------------------------------------------------------
// 5. Validação da View: app/views/admin/contestacoes.php
// -----------------------------------------------------------------
echo "\n--- 5. Testes da View e Componentes de Interface ---\n";

$viewContent = file_get_contents(__DIR__ . '/../app/views/admin/contestacoes.php');
$assertCustom(str_contains($viewContent, 'id="buscaSugestoesDropdown"'), "Container do dropdown de sugestões presente na view");
$assertCustom(str_contains($viewContent, 'id="btnLimparBuscaInput"'), "Botão de limpar busca rápida presente na view");
$assertCustom(str_contains($viewContent, 'id="formFiltrosContestacoes"'), "ID do formulário de filtros configurado na view");
$assertCustom(str_contains($viewContent, 'id="tabelaContestacoes"'), "ID da tabela operacional configurado na view");
$assertCustom(str_contains($viewContent, 'data-busca='), "Atributo data-busca para filtragem live em tempo real presente nas linhas");
$assertCustom(str_contains($viewContent, 'filtrarTabelaClientSide'), "Função JavaScript de filtragem client-side presente na view");
$assertCustom(str_contains($viewContent, 'buscarSugestoes'), "Função JavaScript de busca assíncrona com debounce presente na view");

echo "\n=======================================================\n";
echo " RESULTADO: {$passou} / {$total} testes passaram!\n";
echo "=======================================================\n\n";

if ($passou === $total) {
    echo ">>> TODOS OS TESTES PASSARAM COM SUCESSO! <<<\n";
    exit(0);
} else {
    echo ">>> ALGUNS TESTES FALHARAM! <<<\n";
    exit(1);
}
