<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Livro Caixa Diário, Recibos de Pagamento a Fornecedores
 * e Remoção da Seção 4 do Demonstrativo de Entradas.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Fornecedor.php';
require_once __DIR__ . '/../app/models/FornecedorRecibo.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';

$totalTestes = 0;
$testesPassados = 0;

function assertTest(bool $condicao, string $descricao): void {
    global $totalTestes, $testesPassados;
    $totalTestes++;
    if ($condicao) {
        $testesPassados++;
        echo "  [OK] {$descricao}\n";
    } else {
        echo "  [FALHA] {$descricao}\n";
    }
}

echo "======================================================================\n";
echo " TESTES: LIVRO CAIXA DIÁRIO, RECIBOS DE FORNECEDOR & DEMONSTRATIVO\n";
echo "======================================================================\n\n";

$pdo = \App\Core\Model::getPdo();

// -------------------------------------------------------------------------
// 1. Teste do Conversor de Valor por Extenso
// -------------------------------------------------------------------------
echo ">> 1. Testando Conversor Monetário de Valor por Extenso...\n";
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(0) === 'zero reais', "Extenso de 0 é 'zero reais'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(1.00) === 'um real', "Extenso de 1.00 é 'um real'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(2.00) === 'dois reais', "Extenso de 2.00 é 'dois reais'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(10.00) === 'dez reais', "Extenso de 10.00 é 'dez reais'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(15.50) === 'quinze reais e cinquenta centavos', "Extenso de 15.50 é 'quinze reais e cinquenta centavos'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(60.00) === 'sessenta reais', "Extenso de 60.00 é 'sessenta reais'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(100.00) === 'cem reais', "Extenso de 100.00 é 'cem reais'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(237.00) === 'duzentos e trinta e sete reais', "Extenso de 237.00 é 'duzentos e trinta e sete reais'");
assertTest(\App\Models\FornecedorRecibo::valorPorExtenso(297.00) === 'duzentos e noventa e sete reais', "Extenso de 297.00 é 'duzentos e noventa e sete reais'");

// -------------------------------------------------------------------------
// 2. Teste de Criação e Gestão de Recibos de Pagamento a Fornecedores
// -------------------------------------------------------------------------
echo "\n>> 2. Testando Criação e Gestão de Recibos de Fornecedor...\n";

// Garante existência de fornecedores oficiais para teste
$fornSalgado = \App\Models\Fornecedor::buscarPorNome('Salgados da Vovó');
if (!$fornSalgado) {
    $fornId1 = \App\Models\Fornecedor::salvar([
        'nome_fantasia' => 'Salgados da Vovó',
        'razao_social'  => 'Salgados da Vovó Alimentos Ltda',
        'cnpj_cpf'      => '12.345.678/0001-90',
        'telefone'      => '(62) 98888-1111',
        'ativo'         => 1
    ]);
} else {
    $fornId1 = (int)$fornSalgado['id'];
}

$fornCupcake = \App\Models\Fornecedor::buscarPorNome('Doceria Cupcake');
if (!$fornCupcake) {
    $fornId2 = \App\Models\Fornecedor::salvar([
        'nome_fantasia' => 'Doceria Cupcake',
        'razao_social'  => 'Doceria Cupcake Artesanal Ltda',
        'cnpj_cpf'      => '98.765.432/0001-10',
        'telefone'      => '(62) 97777-2222',
        'ativo'         => 1
    ]);
} else {
    $fornId2 = (int)$fornCupcake['id'];
}

// Emite Recibo 1: Salgado (via Pix)
$reciboId1 = \App\Models\FornecedorRecibo::criar([
    'fornecedor_id'   => $fornId1,
    'valor'           => 60.00,
    'data_pagamento'  => '2026-10-14',
    'forma_pagamento' => 'pix',
    'descricao'       => 'Pagamento Salgado',
    'observacoes'     => 'NF 1042 — Fornecimento da semana'
], 1);

assertTest($reciboId1 > 0, "Recibo 1 (Salgado) criado com ID {$reciboId1}");
$recibo1 = \App\Models\FornecedorRecibo::buscarPorId($reciboId1);
assertTest($recibo1 !== null, "Recibo 1 localizado no banco");
assertTest(str_starts_with($recibo1['numero_recibo'], 'REC-'), "Número do recibo possui prefixo oficial: {$recibo1['numero_recibo']}");
assertTest((float)$recibo1['valor'] === 60.00, "Valor do recibo 1 é R$ 60,00");
assertTest($recibo1['forma_pagamento'] === 'pix', "Forma de pagamento é Pix");
assertTest($recibo1['descricao'] === 'Pagamento Salgado', "Descrição do recibo é 'Pagamento Salgado'");
assertTest($recibo1['fornecedor_nome'] === 'Salgados da Vovó', "Favorecido associado é 'Salgados da Vovó'");

// Emite Recibo 2: Cupcake (via Dinheiro)
$reciboId2 = \App\Models\FornecedorRecibo::criar([
    'fornecedor_id'   => $fornId2,
    'valor'           => 100.00,
    'data_pagamento'  => '2026-10-14',
    'forma_pagamento' => 'dinheiro',
    'descricao'       => 'Pagamento Cupcake',
    'observacoes'     => 'Pago em espécie na entrega'
], 1);

assertTest($reciboId2 > 0, "Recibo 2 (Cupcake) criado com ID {$reciboId2}");
$recibo2 = \App\Models\FornecedorRecibo::buscarPorId($reciboId2);
assertTest((float)$recibo2['valor'] === 100.00, "Valor do recibo 2 é R$ 100,00");
assertTest($recibo2['forma_pagamento'] === 'dinheiro', "Forma de pagamento é Dinheiro em espécie");
assertTest($recibo2['descricao'] === 'Pagamento Cupcake', "Descrição do recibo é 'Pagamento Cupcake'");

// Listagem de recibos
$listaRecibos = \App\Models\FornecedorRecibo::listar(['fornecedor_id' => $fornId1]);
assertTest(count($listaRecibos) >= 1, "Listagem filtra corretamente por fornecedor");

// -------------------------------------------------------------------------
// 3. Teste do Livro Caixa Diário (13 Colunas & Apuração Contábil)
// -------------------------------------------------------------------------
echo "\n>> 3. Testando Apuração Contábil do Livro Caixa Diário...\n";

$relCtrl = new \App\Controllers\RelatoriosController();
$dadosLivro = $relCtrl->obterDadosLivroCaixa('2026-10-01', '2026-10-27');

assertTest(isset($dadosLivro['periodo_oficial']), "Dados contêm período oficial");
assertTest(isset($dadosLivro['saldo_anterior']), "Dados contêm apuração de saldo anterior");
assertTest(isset($dadosLivro['saldo_final']), "Dados contêm saldo final acumulado");
assertTest(isset($dadosLivro['linhas']) && is_array($dadosLivro['linhas']), "Dados contêm linhas de movimentação diária");
assertTest(isset($dadosLivro['totais']), "Dados contêm consolidação dos totais");

$totais = $dadosLivro['totais'];
assertTest(isset($totais['recargas_pix_online']), "Totaliza Recargas Pix Online");
assertTest(isset($totais['recargas_pix_pdv']), "Totaliza Recargas Pix PDV");
assertTest(isset($totais['recargas_dinheiro']), "Totaliza Recargas Dinheiro PDV");
assertTest(isset($totais['vendas_pix_pdv']), "Totaliza Vendas Pix PDV");
assertTest(isset($totais['vendas_dinheiro_pdv']), "Totaliza Vendas Dinheiro PDV");
assertTest(isset($totais['pagamento_pix']), "Totaliza Pagamentos Pix (Débitos)");
assertTest(isset($totais['pagamento_dinheiro']), "Totaliza Pagamentos Dinheiro (Débitos)");
assertTest(isset($totais['credito']), "Totaliza Créditos");
assertTest(isset($totais['debito']), "Totaliza Débitos");

// Verifica se os pagamentos aos fornecedores (60.00 Pix e 100.00 Dinheiro) entraram no Livro Caixa
assertTest($totais['pagamento_pix'] >= 60.00, "Pagamento Pix ao fornecedor contabilizado nos débitos (>= R$ 60,00)");
assertTest($totais['pagamento_dinheiro'] >= 100.00, "Pagamento Dinheiro ao fornecedor contabilizado nos débitos (>= R$ 100,00)");
assertTest($totais['debito'] >= 160.00, "Total de Débitos contabiliza as saídas (>= R$ 160,00)");

// Valida fechamento matemático de cada linha
$saldoEsperado = $dadosLivro['saldo_anterior'];
$saldoConsistente = true;
foreach ($dadosLivro['linhas'] as $idx => $lin) {
    if ($lin['tipo'] === 'credito') {
        $saldoEsperado += $lin['credito'];
    } elseif ($lin['tipo'] === 'debito_recibo' || $lin['tipo'] === 'debito_despesa') {
        $saldoEsperado -= $lin['debito'];
    }
    if (abs($saldoEsperado - $lin['saldo']) > 0.001) {
        $saldoConsistente = false;
        break;
    }
}
assertTest($saldoConsistente, "Cálculo do Saldo progressivo linha a linha fecha perfeitamente com Saldo Anterior + Créditos - Débitos");
assertTest(abs($saldoEsperado - $dadosLivro['saldo_final']) < 0.001, "Saldo final acumulado coincide rigorosamente com o encerramento do período");

// -------------------------------------------------------------------------
// 4. Teste de Remoção da Seção 4 no Demonstrativo de Entradas
// -------------------------------------------------------------------------
echo "\n>> 4. Testando Remoção da Seção 4 no Demonstrativo Financeiro de Entradas...\n";

ob_start();
$relCtrl->arrecadacaoImprimir();
$htmlEntradas = ob_get_clean();

assertTest(str_contains($htmlEntradas, 'COLÉGIO MONTEIRO LOBATO'), "Demonstrativo de Entradas mantém cabeçalho oficial");
assertTest(str_contains($htmlEntradas, 'DEMONSTRATIVO FINANCEIRO - CANAIS DE ENTRADA'), "Demonstrativo mantém título oficial");
assertTest(str_contains($htmlEntradas, '1. Síntese dos Indicadores Financeiros do Período'), "Demonstrativo preserva Seção 1");
assertTest(str_contains($htmlEntradas, '2. Resumo por Canais de Entrada Financeira (Cash-In Real)'), "Demonstrativo preserva Seção 2");
assertTest(str_contains($htmlEntradas, '3. Demonstrativo de Controle e Conciliação da Carteira Digital'), "Demonstrativo preserva Seção 3");
assertTest(!str_contains($htmlEntradas, '4. Detalhamento Cronológico Diário de Entradas e Consumo'), "Seção 4 foi estritamente REMOVIDA do demonstrativo de entradas");
assertTest(!str_contains($htmlEntradas, 'Consumo Saldo</th>'), "Tabela da Seção 4 não existe mais no demonstrativo de entradas");

// -------------------------------------------------------------------------
// 5. Teste do Espelho A4 do Livro Caixa e Rotas
// -------------------------------------------------------------------------
echo "\n>> 5. Testando Espelho de Impressão A4 e Rotas do Livro Caixa...\n";

ob_start();
$relCtrl->livroCaixaImprimir();
$htmlLivro = ob_get_clean();

assertTest(str_contains($htmlLivro, 'LIVRO CAIXA DIÁRIO — ESCRITURAÇÃO FINANCEIRA'), "Espelho A4 do Livro Caixa contém título oficial");
assertTest(str_contains($htmlLivro, 'table-doc-excel'), "Utiliza a classe oficial homologada table-doc-excel");
assertTest(str_contains($htmlLivro, 'Pix Online'), "Espelho contém coluna Pix Online");
assertTest(str_contains($htmlLivro, 'Rec. PDV Pix'), "Espelho contém coluna Rec. PDV Pix");
assertTest(str_contains($htmlLivro, 'Rec. PDV Din'), "Espelho contém coluna Rec. PDV Din");
assertTest(str_contains($htmlLivro, 'Venda Pix'), "Espelho contém coluna Venda Pix");
assertTest(str_contains($htmlLivro, 'Venda Din'), "Espelho contém coluna Venda Din");
assertTest(str_contains($htmlLivro, 'Pgto Pix'), "Espelho contém coluna Pgto Pix");
assertTest(str_contains($htmlLivro, 'Pgto Din'), "Espelho contém coluna Pgto Din");
assertTest(str_contains($htmlLivro, 'Crédito'), "Espelho contém coluna Crédito");
assertTest(str_contains($htmlLivro, 'Débito'), "Espelho contém coluna Débito");
assertTest(str_contains($htmlLivro, 'Saldo'), "Espelho contém coluna Saldo");
assertTest(str_contains($htmlLivro, 'Pagamento Salgado'), "Espelho exibe a linha discriminada do Pagamento Salgado");
assertTest(str_contains($htmlLivro, 'Pagamento Cupcake'), "Espelho exibe a linha discriminada do Pagamento Cupcake");

// -------------------------------------------------------------------------
// 6. Teste do Dock Lateral e Menus
// -------------------------------------------------------------------------
echo "\n>> 6. Testando Navegação do Dock Lateral...\n";
$sidebarContent = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
assertTest(str_contains($sidebarContent, '/financeiro/livro-caixa'), "Sidebar contém link para o Livro Caixa Diário");
assertTest(str_contains($sidebarContent, 'Livro Caixa Diário'), "Sidebar exibe o rótulo 'Livro Caixa Diário'");
assertTest(str_contains($sidebarContent, "'livro_caixa'"), "Array de menus ativos do Financeiro inclui 'livro_caixa'");

// -------------------------------------------------------------------------
// 7. Teste do Espelho A4 do Recibo de Fornecedor
// -------------------------------------------------------------------------
echo "\n>> 7. Testando Espelho A4 do Recibo de Fornecedor...\n";
$adminCtrl = new \App\Controllers\AdminController();
ob_start();
$adminCtrl->fornecedoresReciboImprimir($reciboId1);
$htmlRecibo = ob_get_clean();

assertTest(str_contains($htmlRecibo, 'Recibo Oficial de Pagamento a Fornecedor'), "Espelho do recibo contém cabeçalho oficial");
assertTest(str_contains($htmlRecibo, '1ª Via — Cantina / Financeiro'), "Espelho do recibo contém 1ª Via");
assertTest(str_contains($htmlRecibo, '2ª Via — Fornecedor Parceiro'), "Espelho do recibo contém 2ª Via com via dupla");
assertTest(str_contains($htmlRecibo, 'sessenta reais'), "Espelho do recibo contém valor por extenso ('sessenta reais')");
assertTest(str_contains($htmlRecibo, 'Salgados da Vovó'), "Espelho do recibo identifica o fornecedor favorecido");

// -------------------------------------------------------------------------
// 8. Teste de Renderização da View com Layout Admin Completo (Anti-Quebra)
// -------------------------------------------------------------------------
echo "\n>> 8. Testando Renderização com Layout Mestre Admin...\n";
$_GET['data_inicio'] = '2026-10-01';
$_GET['data_fim'] = '2026-10-27';
$_GET['filtrar'] = '1';

ob_start();
$relCtrl->livroCaixa();
$htmlLivroWeb = ob_get_clean();

assertTest(str_contains($htmlLivroWeb, '<!DOCTYPE html>'), "View do Livro Caixa renderiza com <!DOCTYPE html> do layout admin");
assertTest(str_contains($htmlLivroWeb, 'bootstrap.min.css'), "View inclui CSS do Bootstrap local");
assertTest(str_contains($htmlLivroWeb, 'custom.css'), "View inclui CSS corporativo custom.css");
assertTest(str_contains($htmlLivroWeb, 'app-admin-wrapper'), "View está encapsulada no wrapper do layout admin");
assertTest(str_contains($htmlLivroWeb, 'sidebar-dock'), "View renderiza a barra lateral dock de navegação");
assertTest(str_contains($htmlLivroWeb, 'app-page-content'), "View renderiza o container principal app-page-content");
assertTest(str_contains($htmlLivroWeb, 'Livro Caixa Diário'), "View exibe o título oficial Livro Caixa Diário");
assertTest(str_contains($htmlLivroWeb, 'stat-strip'), "View filtrada renderiza os indicadores stat-strip");
assertTest(str_contains($htmlLivroWeb, 'table-enterprise'), "View filtrada renderiza a tabela corporativa de 13 colunas");

echo "\n----------------------------------------------------------------------\n";
echo " RESUMO FINAL: {$testesPassados} / {$totalTestes} asserções aprovadas\n";
echo "======================================================================\n";
if ($testesPassados === $totalTestes) {
    echo " >>> SUCESSO TOTAL: 100% DAS ASSERÇÕES APROVADAS! <<<\n";
    exit(0);
} else {
    echo " >>> ATENÇÃO: Houve falhas nos testes. <<<\n";
    exit(1);
}
