<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Utilitário Seguro de Limpeza e Zeramento de Dados de Teste Pré-Lançamento
 * 
 * Finalidade:
 * Permite purgar todos os dados operacionais e movimentações simuladas realizadas
 * durante os testes de homologação, deixando o sistema 100% limpo para a inauguração
 * oficial com clientes e alunos reais.
 * 
 * Modos de Execução:
 * 1. CLI (Linha de Comando via SSH):
 *    php database/reset_dados_teste.php --modo=transacional --confirmar
 *    php database/reset_dados_teste.php --modo=total --confirmar
 * 
 * 2. HTTP (Navegador com Token e Envio via POST):
 *    Acessar via GET para visualizar o checklist de segurança e confirmar via POST.
 */

// Define constante de segurança se invocado diretamente
if (!defined('CANTINA_SISTEMA')) {
    define('CANTINA_SISTEMA', true);
}

// 1. Carrega configuração oficial
$configFile = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die("Erro fatal: Arquivo de configuração [config/config.php] não encontrado.\n");
}
$config = require $configFile;

$isCli = (php_sapi_name() === 'cli');

// 2. Validação rigorosa de segurança por Token (se invocado via Web)
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    $token = (string)($_POST['token'] ?? $_GET['token'] ?? '');
    $expectedToken = (string)($config['security']['migration_token'] ?? '');

    if (empty($expectedToken) || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        die("<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>403 Acesso Negado</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;}</style></head><body><h1>403 Acesso Negado</h1><p>Token de segurança inválido ou ausente.</p></body></html>");
    }

    // Trava de proteção caso o sistema já tenha sido marcado como inaugurado
    if (($config['app']['sistema_inaugurado'] ?? false) === true) {
        http_response_code(403);
        die("<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Bloqueado</title></head><body><h1>Acesso Bloqueado</h1><p>O sistema já se encontra oficialmente inaugurado. O zeramento de dados está desativado.</p></body></html>");
    }
}

// 3. Conecta ao banco de dados via Singleton PDO
try {
    /** @var \PDO $pdo */
    $pdo = require dirname(__DIR__) . '/config/database.php';
} catch (\Throwable $e) {
    if ($isCli) {
        die("Erro de conexão com o banco de dados: " . $e->getMessage() . "\n");
    } else {
        $date = date('Y-m-d H:i:s');
        @file_put_contents(dirname(__DIR__) . '/storage/logs/error.log', "[{$date}] [CRITICAL] [RESET_DADOS_DB] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        die("<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Erro de Banco</title></head><body><h1>500 Erro de Conexão</h1><p>Não foi possível conectar ao banco de dados. O incidente foi registrado em log seguro.</p></body></html>");
    }
}

// 4. Parâmetros da Ação
$modo = 'transacional'; // 'transacional' (padrão) ou 'total'
$confirmado = false;

if ($isCli) {
    global $argv;
    foreach ($argv ?? [] as $arg) {
        if ($arg === '--confirmar') {
            $confirmado = true;
        } elseif (str_starts_with($arg, '--modo=')) {
            $modo = substr($arg, 7);
        }
    }
} else {
    // Via Web, mutação destrutiva SÓ é aceita via método HTTP POST
    $modo = (string)($_POST['modo'] ?? $_GET['modo'] ?? 'transacional');
    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    $confirmado = $isPost && (strtolower((string)($_POST['confirmar'] ?? '')) === 'sim');
}

if (!in_array($modo, ['transacional', 'total'], true)) {
    $modo = 'transacional';
}

// 5. Se não foi confirmado explicitamente, exibe aviso de segurança com formulário POST
if (!$confirmado) {
    if ($isCli) {
        echo "====================================================================\n";
        echo "   ALERTA DE SEGURANÇA: LIMPEZA DE DADOS DE TESTE — CANTINA WEB    \n";
        echo "====================================================================\n";
        echo "Modo selecionado: [{$modo}]\n\n";
        echo "ATENÇÃO: Esta operação removerá os registros de movimentação de teste.\n";
        echo "Para executar de fato, forneça o argumento: --confirmar\n";
        echo "Exemplo: php database/reset_dados_teste.php --modo={$modo} --confirmar\n";
        echo "====================================================================\n";
        exit(0);
    } else {
        $tokenSafe = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
        $modoSafe = htmlspecialchars($modo, ENT_QUOTES, 'UTF-8');
        echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Confirmação de Limpeza — Cantina Web</title>";
        echo "<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;padding:40px;color:#0f172a;} .card{background:#fff;max-width:680px;margin:auto;padding:32px;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.08);} h1{color:#b91c1c;font-size:20px;margin-top:0;} .btn{display:inline-block;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:600;font-size:14px;cursor:pointer;border:none;} .btn-danger{background:#dc2626;color:#fff;} .btn-secondary{background:#64748b;color:#fff;margin-left:10px;} ul{font-size:14px;color:#334155;line-height:1.7;} .alert-box{background:#fef2f2;border:1px solid #fecaca;border-radius:6px;padding:12px 16px;margin:16px 0;font-size:13px;color:#991b1b;}</style></head><body>";
        echo "<div class='card'>";
        echo "<h1>Atenção: Confirmação de Zeramento de Dados de Teste</h1>";
        echo "<div class='alert-box'><strong>Ambiente de Homologação / Pré-Lançamento:</strong> Esta ação purgará os registros simulados para deixar o banco de dados pronto para a inauguração oficial com alunos e clientes reais.</div>";
        echo "<p>Modo de limpeza selecionado: <strong>" . strtoupper($modoSafe) . "</strong></p>";
        echo "<p><strong>O que será limpo e zerado:</strong></p>";
        echo "<ul>";
        echo "<li>Todas as vendas de balcão e itens do PDV</li>";
        echo "<li>Todos os pedidos semanais e itens encomendados</li>";
        echo "<li>Todos os tickets térmicos emitidos</li>";
        echo "<li>Todas as sessões de caixa abertas/fechadas</li>";
        echo "<li>Todas as movimentações e extratos de carteira (saldos das famílias voltarão a R$ 0,00)</li>";
        echo "<li>Estoque atual e reservado dos produtos (serão redefinidos para 0)</li>";
        echo "<li>Todas as movimentações de estoque, perdas registradas e notas fiscais XML</li>";
        echo "<li>Todas as despesas operacionais simuladas</li>";
        echo "<li>Trilhas de auditoria e logs de erros/segurança</li>";
        if ($modo === 'total') {
            echo "<li style='color:#b91c1c;font-weight:bold;'>MODO TOTAL: Cadastro de alunos, responsáveis e vínculos de turmas também serão excluídos.</li>";
        }
        echo "</ul>";
        echo "<p><strong>O que SERÁ PRESERVADO e mantido 100% intacto:</strong></p>";
        echo "<ul>";
        echo "<li>Usuários administradores e operadores da cantina</li>";
        echo "<li>Catálogo de produtos, preços de venda, categorias e alérgenos</li>";
        echo "<li>Configurações institucionais do colégio</li>";
        if ($modo !== 'total') {
            echo "<li>Cadastros de turmas, alunos e responsáveis (apenas saldos e pedidos serão zerados)</li>";
        }
        echo "<li>Tabela de controle das 30 migrações do banco</li>";
        echo "</ul>";
        echo "<form method='POST' action='?modo={$modoSafe}' style='margin-top:28px;' onsubmit='return confirm(\"Tem certeza absoluta que deseja zerar os dados de teste agora? Esta ação não pode ser desfeita.\");'>";
        echo "<input type='hidden' name='token' value='{$tokenSafe}'>";
        echo "<input type='hidden' name='modo' value='{$modoSafe}'>";
        echo "<input type='hidden' name='confirmar' value='sim'>";
        echo "<button type='submit' class='btn btn-danger'>Confirmar e Executar Limpeza Agora</button>";
        echo "<a href='/login' class='btn btn-secondary'>Cancelar e Sair</a>";
        echo "</form>";
        echo "</div></body></html>";
        exit;
    }
}

// 6. Execução da Limpeza Controlada
$relatorio = [];
$totalExcluidos = 0;

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // Tabelas transacionais que sempre são limpas no reset
    $tabelasTransacionais = [
        'itens_venda_balcao',
        'vendas_balcao',
        'itens_pedido',
        'tickets',
        'pedidos',
        'transacoes_carteira',
        'sessoes_caixa',
        'itens_nota_fiscal_xml',
        'notas_fiscais_xml',
        'estoque_movimentacoes',
        'despesas_operacionais',
        'logs_auditoria',
        'logs_erros',
        'logs_seguranca'
    ];

    foreach ($tabelasTransacionais as $tabela) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$tabela}`")->fetchColumn();
        $pdo->exec("TRUNCATE TABLE `{$tabela}`;");
        $relatorio[$tabela] = $count;
        $totalExcluidos += $count;
    }

    // Zera o saldo das carteiras de todos os responsáveis para R$ 0,00
    $pdo->exec("UPDATE `responsaveis` SET `saldo_atual` = 0.00;");

    // Redefine estoque operacional dos produtos para 0 (colunas existentes na migração 011)
    $pdo->exec("UPDATE `produtos` SET `estoque_atual` = 0, `estoque_reservado` = 0;");

    // Se o modo for 'total', zera também o cadastro de alunos e responsáveis
    if ($modo === 'total') {
        $tabelasCadastrais = [
            'aluno_restricoes',
            'alunos',
            'responsaveis',
            'colaboradores'
        ];

        foreach ($tabelasCadastrais as $tabela) {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$tabela}`")->fetchColumn();
            $pdo->exec("TRUNCATE TABLE `{$tabela}`;");
            $relatorio[$tabela] = $count;
            $totalExcluidos += $count;
        }
    }

} catch (\Throwable $e) {
    if ($isCli) {
        die("Falha crítica ao executar limpeza: " . $e->getMessage() . "\n");
    } else {
        $date = date('Y-m-d H:i:s');
        @file_put_contents(dirname(__DIR__) . '/storage/logs/error.log', "[{$date}] [CRITICAL] [RESET_DADOS] " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        die("<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>500 Erro Interno</title></head><body><h1>500 Erro Interno</h1><p>Ocorreu uma falha ao processar a limpeza. O erro foi registrado em log seguro.</p></body></html>");
    }
} finally {
    // Garante restauração absoluta da integridade referencial
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    } catch (\Throwable $t) {
        // Ignora em caso de conexão já fechada
    }
}

// 7. Emissão do Relatório de Execução
$dataHora = date('d/m/Y H:i:s');

if ($isCli) {
    echo "====================================================================\n";
    echo "   LIMPEZA DE DADOS CONCLUÍDA COM SUCESSO! — CANTINA WEB           \n";
    echo "   Data/Hora: {$dataHora} | Modo: " . strtoupper($modo) . "\n";
    echo "====================================================================\n";
    foreach ($relatorio as $tabela => $qtd) {
        echo sprintf(" - %-26s : %5d registros removidos\n", $tabela, $qtd);
    }
    echo "--------------------------------------------------------------------\n";
    echo " Total de registros de teste eliminados: {$totalExcluidos}\n";
    echo " Saldos das carteiras dos responsáveis redefinidos para: R$ 0,00\n";
    echo " Estoque atual e reservado dos produtos redefinidos para: 0\n";
    echo " Usuários da equipe, cardápio e parâmetros mantidos 100% preservados.\n";
    echo "====================================================================\n";
} else {
    echo "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Limpeza Concluída — Cantina Web</title>";
    echo "<style>body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f8fafc;padding:40px;color:#0f172a;} .card{background:#fff;max-width:680px;margin:auto;padding:32px;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.08);} h1{color:#15803d;font-size:20px;margin-top:0;} table{width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;} th,td{padding:8px 12px;border-bottom:1px solid #f1f5f9;text-align:left;} th{background:#f8fafc;font-weight:600;} .btn{display:inline-block;padding:10px 20px;border-radius:6px;text-decoration:none;font-weight:600;background:#1d4ed8;color:#fff;margin-top:15px;}</style></head><body>";
    echo "<div class='card'>";
    echo "<h1>✓ Limpeza de Dados de Teste Concluída com Sucesso!</h1>";
    echo "<p>Ambiente limpo e preparado para o lançamento oficial em: <strong>{$dataHora}</strong></p>";
    echo "<p>Modo executado: <strong>" . strtoupper($modo) . "</strong> | Total de registros removidos: <strong>{$totalExcluidos}</strong></p>";
    echo "<table><thead><tr><th>Tabela Purgada</th><th style='text-align:right;'>Registros Removidos</th></tr></thead><tbody>";
    foreach ($relatorio as $tabela => $qtd) {
        echo "<tr><td><code>{$tabela}</code></td><td style='text-align:right;font-family:monospace;'>{$qtd}</td></tr>";
    }
    echo "</tbody></table>";
    echo "<p style='color:#475569;font-size:13px;'>✓ Saldos das carteiras dos pais redefinidos para <strong>R$ 0,00</strong>.<br>✓ Estoque de produtos ajustado para <strong>0</strong>.<br>✓ Cadastros mestres e logins de administradores <strong>100% preservados</strong>.</p>";
    echo "<a href='/login' class='btn'>Ir para a Tela de Login</a>";
    echo "</div></body></html>";
}
