<?php
/**
 * Teste Automatizado de Verificação de Reparação de Encoding UTF-8 e Mojibake
 * Cantina Web — Colégio Monteiro Lobato
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../app/core/Sanitizer.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';

use App\Core\Sanitizer;
use App\Models\Categoria;
use App\Models\Produto;

echo "========================================================\n";
echo " TESTE DE VERIFICAÇÃO DE ENCODING E MOJIBAKE (UTF-8)    \n";
echo "========================================================\n\n";

$passou = 0;
$total = 0;

function assertEqual($esperado, $atual, $descricao) {
    global $passou, $total;
    $total++;
    if ($esperado === $atual) {
        echo "[OK] {$descricao}\n";
        $passou++;
    } else {
        echo "[FALHA] {$descricao}\n";
        echo "  Esperado: " . var_export($esperado, true) . "\n";
        echo "  Recebido: " . var_export($atual, true) . "\n";
    }
}

// 1. Testes Unitários do Sanitizer
echo "--- 1. Sanitizer::repararString (CP850) ---\n";
assertEqual('Sanduíches', Sanitizer::repararString('Sandu├¡ches'), "Corrige 'Sandu├¡ches' para 'Sanduíches'");
assertEqual('Glúten', Sanitizer::repararString('Gl├║ten'), "Corrige 'Gl├║ten' para 'Glúten'");
assertEqual('6º Ano A', Sanitizer::repararString('6┬║ Ano A'), "Corrige '6┬║ Ano A' para '6º Ano A'");
assertEqual('1ª Via', Sanitizer::repararString('1┬¬ Via'), "Corrige '1┬¬ Via' para '1ª Via'");
assertEqual('Pão de Açúcar', Sanitizer::repararString('P├úo de A├º├║car'), "Corrige múltiplos caracteres CP850 na mesma frase");
assertEqual('Café Expresso', Sanitizer::repararString('Caf├® Expresso'), "Corrige 'Caf├®' para 'Café'");

echo "\n--- 2. Sanitizer::repararString (Latin1 Dupla Codificação) ---\n";
assertEqual('Sanduíches', Sanitizer::repararString('SanduÃ­ches'), "Corrige 'SanduÃ­ches' para 'Sanduíches'");
assertEqual('Glúten', Sanitizer::repararString('GlÃºten'), "Corrige 'GlÃºten' para 'Glúten'");
assertEqual('6º Ano', Sanitizer::repararString('6Âº Ano'), "Corrige '6Âº Ano' para '6º Ano'");

echo "\n--- 3. Sanitizer::repararUtf8 em Arrays Multidimensionais ---\n";
$dadosSujos = [
    'categoria' => 'Lanches & Sandu├¡ches',
    'itens' => [
        ['descricao' => 'Coxinha de Frango', 'alergenos' => 'Gl├║ten'],
        ['descricao' => 'P├úo de Queijo', 'alergenos' => 'Lactose']
    ]
];
$dadosLimpos = Sanitizer::repararUtf8($dadosSujos);
assertEqual('Lanches & Sanduíches', $dadosLimpos['categoria'], "Array: categoria corrigida");
assertEqual('Glúten', $dadosLimpos['itens'][0]['alergenos'], "Array aninhado: Glúten corrigido");
assertEqual('Pão de Queijo', $dadosLimpos['itens'][1]['descricao'], "Array aninhado: Pão de Queijo corrigido");

echo "\n--- 4. Verificação no Banco de Dados Local ---\n";
$pdo = Database::getInstance();
$nomeGluten = $pdo->query("SELECT nome FROM restricoes_alimentares WHERE id = 1")->fetchColumn();
assertEqual('Glúten', $nomeGluten, "Banco de dados: Alérgeno 1 é 'Glúten'");

$nomeCategoria = $pdo->query("SELECT nome FROM categorias WHERE id = 3")->fetchColumn();
assertEqual('Lanches & Sanduíches', $nomeCategoria, "Banco de dados: Categoria 3 é 'Lanches & Sanduíches'");

echo "\n--- 5. Verificação via Models ---\n";
$cats = Categoria::listarTodas(true);
$cat3 = array_filter($cats, fn($c) => (int)$c['id'] === 3);
$cat3 = reset($cat3);
assertEqual('Lanches & Sanduíches', $cat3['nome'], "Model Categoria::listarTodas retorna nome limpo");

$prods = Produto::listarParaPdv();
$prodCoxinha = array_filter($prods, fn($p) => (int)$p['id'] === 1);
$prodCoxinha = reset($prodCoxinha);
assertEqual('Glúten', $prodCoxinha['alergenos_nomes'], "Model Produto::listarParaPdv retorna alérgeno 'Glúten'");

echo "\n--------------------------------------------------------\n";
echo "Resultado: {$passou} de {$total} asserções passaram com sucesso!\n";
if ($passou === $total) {
    echo "STATUS: TODOS OS TESTES FORAM APROVADOS! (100% SUCESSO)\n";
} else {
    echo "STATUS: HOUVE FALHAS!\n";
    exit(1);
}
