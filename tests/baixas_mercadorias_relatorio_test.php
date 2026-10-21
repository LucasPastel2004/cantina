<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Relatório e Extrato de Baixas de Mercadorias (Perdas & Descarte)
 * 
 * Valida a reformulação da tela de perdas (/estoque/perdas), o novo relatório em tela cheia,
 * a alteração do rótulo para 'Produto Não Consumido', filtros com atalhos e exportações.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/models/Usuario.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/Estoque.php';
require_once __DIR__ . '/../app/services/PdfService.php';
require_once __DIR__ . '/../app/controllers/RelatoriosController.php';
require_once __DIR__ . '/../app/controllers/EstoqueController.php';

// Inicializa sessão de teste
\App\Core\Session::start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['user_nome'] = 'Administrador de Testes';

$passou = 0;
$total = 0;

function assertTeste($condicao, $mensagem) {
    global $passou, $total;
    $total++;
    if ($condicao) {
        $passou++;
        echo "  [OK] " . $mensagem . "\n";
    } else {
        echo "  [FALHA] " . $mensagem . "\n";
    }
}

echo "=======================================================\n";
echo "   INICIANDO TESTES DO RELATÓRIO DE BAIXAS DE MERCADORIAS\n";
echo "=======================================================\n\n";

// -------------------------------------------------------------
// 1. Validação de Rótulos no Modelo Estoque
// -------------------------------------------------------------
echo "1. Validando constantes e motivos no modelo Estoque...\n";
assertTeste(
    \App\Models\Estoque::MOTIVOS_PERDA[\App\Models\Estoque::MOTIVO_SOBRA_PRODUCAO] === 'Produto Não Consumido',
    "Motivo 'sobra_producao' foi renomeado com sucesso para 'Produto Não Consumido'"
);
assertTeste(
    count(\App\Models\Estoque::MOTIVOS_PERDA) === 4,
    "Existem exatamente os 4 motivos padronizados de perda"
);

// -------------------------------------------------------------
// 2. Criação de Movimentações de Perda para Testes
// -------------------------------------------------------------
echo "\n2. Criando movimentações de perda controladas para auditoria...\n";
$pdo = \App\Core\Model::getPdo();
$produto = \App\Core\Model::fetchOne("SELECT id, codigo_interno, descricao, categoria_id FROM produtos WHERE deleted_at IS NULL LIMIT 1");
$operadorValido = \App\Core\Model::fetchOne("SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1");
$opId = $operadorValido ? (int)$operadorValido['id'] : 1;

if ($produto && $operadorValido) {
    $hoje = date('Y-m-d H:i:s');
    // Insere perda por produto não consumido
    $stmt = $pdo->prepare("INSERT INTO estoque_movimentacoes (produto_id, tipo_movimentacao, quantidade, motivo_perda, operador_id, justificativa, created_at)
                           VALUES (:p, 'saida_perda', 3, 'sobra_producao', :op, 'Produto sobrou do turno sem consumo de alunos', :data)");
    $stmt->execute(['p' => $produto['id'], 'op' => $opId, 'data' => $hoje]);
    $perdaId = (int)$pdo->lastInsertId();
    assertTeste($perdaId > 0, "Movimentação de perda por 'sobra_producao' registrada com ID #{$perdaId}");
} else {
    echo "  [ALERTA] Nenhum produto ou operador ativo encontrado para teste de inserção.\n";
}

// -------------------------------------------------------------
// 3. Validação do Controller de Relatórios (obterDadosBaixasMercadorias)
// -------------------------------------------------------------
echo "\n3. Testando agregação de dados no RelatoriosController...\n";
$relController = new \App\Controllers\RelatoriosController();
$dados = $relController->obterDadosBaixasMercadorias([
    'motivo_perda' => 'sobra_producao'
]);

assertTeste(is_array($dados), "obterDadosBaixasMercadorias retornou um array");
assertTeste(isset($dados['totais']['total_unidades']), "Métrica de total_unidades presente no consolidado");
assertTeste($dados['totais']['total_unidades'] >= 3, "Total de unidades computou a baixa de 'sobra_producao'");
assertTeste(isset($dados['totais']['por_motivo']['sobra_producao']), "Distribuição por motivo contém 'sobra_producao'");
assertTeste($dados['motivos_perda']['sobra_producao'] === 'Produto Não Consumido', "Array de motivos utiliza o novo rótulo");

// -------------------------------------------------------------
// 4. Validação da Tela de Lançamento de Perdas (/estoque/perdas)
// -------------------------------------------------------------
echo "\n4. Testando a view reformulada de /estoque/perdas...\n";
$produtos = \App\Models\Produto::listarTodos();
$motivosPerda = \App\Models\Estoque::MOTIVOS_PERDA;
ob_start();
include __DIR__ . '/../app/views/estoque/perdas.php';
$htmlPerdas = ob_get_clean();

assertTeste(
    strpos($htmlPerdas, 'Diretriz Operacional — Prevenção de Desperdícios & Auditoria') !== false,
    "Aviso de Diretriz Operacional presente no topo"
);
assertTeste(
    strpos($htmlPerdas, 'Nova Baixa de Mercadoria') !== false,
    "Card de Nova Baixa de Mercadoria presente"
);
assertTeste(
    strpos($htmlPerdas, 'Relatório de Baixas de Mercadorias') !== false,
    "Botão 'Relatório de Baixas de Mercadorias' presente no cabeçalho"
);
assertTeste(
    strpos($htmlPerdas, '/relatorios/baixas-mercadorias') !== false,
    "Botão direciona para /relatorios/baixas-mercadorias"
);
assertTeste(
    strpos($htmlPerdas, 'Produto Não Consumido') !== false,
    "Opção de motivo 'Produto Não Consumido' renderizada no formulário"
);
assertTeste(
    strpos($htmlPerdas, 'Histórico Completo de Perdas') === false,
    "Botão antigo 'Histórico Completo de Perdas' foi devidamente removido"
);
assertTeste(
    strpos($htmlPerdas, 'Grade de Saldos') === false,
    "Botão antigo 'Grade de Saldos' foi devidamente removido"
);
assertTeste(
    strpos($htmlPerdas, 'Últimas Perdas Registradas') === false,
    "Tabela antiga 'Últimas Perdas Registradas' foi devidamente removida da tela de lançamento"
);

// -------------------------------------------------------------
// 5. Validação da Nova View de Relatório (/relatorios/baixas-mercadorias)
// -------------------------------------------------------------
echo "\n5. Testando a nova view de /relatorios/baixas-mercadorias...\n";
$filtros = $dados['filtros'];
$totais = $dados['totais'];
$motivosPerda = $dados['motivos_perda'];
$categorias = \App\Models\Categoria::listarTodas();
$operadores = [['id' => 1, 'nome' => 'Administrador']];
$movimentacoes = \App\Models\Estoque::listarMovimentacoes($filtros, 10, 0);

ob_start();
include __DIR__ . '/../app/views/relatorios/baixas_mercadorias.php';
$htmlRelatorio = ob_get_clean();

assertTeste(
    strpos($htmlRelatorio, 'Relatório de Baixas de Mercadorias (Perdas & Descarte)') !== false,
    "Título oficial presente na nova view de relatório"
);
assertTeste(
    strpos($htmlRelatorio, 'Período: Data Inicial') !== false && strpos($htmlRelatorio, 'Período: Data Final') !== false,
    "Campos de filtro de data inicial e final presentes"
);
assertTeste(
    strpos($htmlRelatorio, "definirPeriodo('hoje')") !== false && strpos($htmlRelatorio, "definirPeriodo('este_mes')") !== false,
    "Atalhos rápidos de período (Hoje, Ontem, Esta Semana, Este Mês, Mês Anterior) presentes"
);
assertTeste(
    strpos($htmlRelatorio, 'Motivo da Perda:') !== false,
    "Filtro de motivo da perda presente"
);
assertTeste(
    strpos($htmlRelatorio, 'Operador:') !== false,
    "Filtro de operador responsável presente"
);
assertTeste(
    strpos($htmlRelatorio, 'Centro de Custo Ativo:') === false,
    "Centro de Custo removido conforme instrução explícita do usuário"
);
assertTeste(
    strpos($htmlRelatorio, '/relatorios/baixas-mercadorias/imprimir') !== false,
    "Ação de impressão / PDF A4 presente no cabeçalho"
);
assertTeste(
    strpos($htmlRelatorio, '/relatorios/baixas-mercadorias/csv') !== false,
    "Ação de exportação CSV presente no cabeçalho"
);
assertTeste(
    strpos($htmlRelatorio, 'Lançamentos Históricos de Baixas') !== false,
    "Tabela analítica de lançamentos históricos de baixas presente"
);

// -------------------------------------------------------------
// 6. Validação da View de Impressão A4 (baixas_mercadorias_pdf.php)
// -------------------------------------------------------------
echo "\n6. Testando a view oficial A4 timbrada de impressão...\n";
ob_start();
$dadosViewPdf = [
    'periodo_oficial' => '01/10/2026 a 21/10/2026',
    'filtros_desc' => ['Motivo: Produto Não Consumido'],
    'movimentacoes' => $movimentacoes,
    'totais' => $totais,
    'motivos_perda' => $motivosPerda
];
include __DIR__ . '/../app/views/relatorios/baixas_mercadorias_pdf.php';
$htmlPdf = ob_get_clean();

assertTeste(
    strpos($htmlPdf, 'COLÉGIO MONTEIRO LOBATO') !== false,
    "Cabeçalho institucional timbrado presente no espelho A4"
);
assertTeste(
    strpos($htmlPdf, 'Relatório Oficial de Baixas de Mercadorias (Perdas & Descarte)') !== false,
    "Subtítulo oficial do relatório timbrado presente"
);
assertTeste(
    strpos($htmlPdf, 'Total de Baixas (Unidades)') !== false,
    "Caixa de resumo de unidades presente"
);
assertTeste(
    strpos($htmlPdf, 'window.print()') !== false,
    "Script nativo de disparo de impressão presente"
);

// -------------------------------------------------------------
// 7. Validação das Rotas e Menus do Sistema
// -------------------------------------------------------------
echo "\n7. Testando rotas registradas e integração com a sidebar...\n";
$indexContent = file_get_contents(__DIR__ . '/../index.php');
assertTeste(
    strpos($indexContent, '/relatorios/baixas-mercadorias') !== false,
    "Rota /relatorios/baixas-mercadorias registrada no index.php"
);
assertTeste(
    strpos($indexContent, '/relatorios/baixas-mercadorias/imprimir') !== false,
    "Rota /relatorios/baixas-mercadorias/imprimir registrada no index.php"
);
assertTeste(
    strpos($indexContent, '/relatorios/baixas-mercadorias/csv') !== false,
    "Rota /relatorios/baixas-mercadorias/csv registrada no index.php"
);

$sidebarContent = file_get_contents(__DIR__ . '/../app/views/layouts/sidebar.php');
assertTeste(
    strpos($sidebarContent, 'Relatório de Baixas') !== false,
    "Link 'Relatório de Baixas' presente no menu Estoque da sidebar"
);
assertTeste(
    strpos($sidebarContent, 'Baixas de Mercadorias') !== false,
    "Link 'Baixas de Mercadorias' presente no menu Relatórios da sidebar"
);

$relIndexContent = file_get_contents(__DIR__ . '/../app/views/relatorios/index.php');
assertTeste(
    strpos($relIndexContent, 'Baixas de Mercadorias') !== false,
    "Card 'Baixas de Mercadorias' presente na Central de Relatórios (/relatorios)"
);

// -------------------------------------------------------------
// Resultado Final
// -------------------------------------------------------------
echo "\n=======================================================\n";
echo "RESULTADO: {$passou} de {$total} asserções aprovadas.\n";
if ($passou === $total) {
    echo "STATUS: SUCESSO TOTAL (100% DE APROVAÇÃO)\n";
} else {
    echo "STATUS: HOUVE FALHAS (" . ($total - $passou) . " falhas)\n";
    exit(1);
}
echo "=======================================================\n";
