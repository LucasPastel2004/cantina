<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Rotas Web, Controller e RBAC da Fase 14 (Gestão de Estoque, XML e Perdas)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace Tests;

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoloader PSR-4
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\RBAC;
use App\Core\Auth;
use App\Core\Session;
use App\Models\Produto;
use App\Controllers\EstoqueController;

class EstoqueWebTest
{
    private \PDO $pdo;
    private int $total = 0;
    private int $aprovados = 0;

    public function __construct()
    {
        $this->pdo = \Database::getInstance();
    }

    public function run(): void
    {
        echo "\n======================================================================\n";
        echo " INICIANDO TESTES DE CONTROLADOR E RBAC — FASE 14 (ESTOQUE & XML)\n";
        echo "======================================================================\n\n";

        $this->testarEstruturaMetodosController();
        $this->testarPermissoesRbac();
        $this->testarAcessoVisitanteHttpReal();
        $this->testarIntegridadeViewsEstoque();
        $this->testarFluxoRegistroPerdaWeb();

        echo "\n----------------------------------------------------------------------\n";
        echo " RESULTADO: {$this->aprovados}/{$this->total} testes aprovados.\n";
        echo "----------------------------------------------------------------------\n\n";

        if ($this->aprovados !== $this->total) {
            exit(1);
        }
    }

    private function assert(bool $cond, string $msg): void
    {
        $this->total++;
        if ($cond) {
            $this->aprovados++;
            echo " [PASS] {$msg}\n";
        } else {
            echo " [FAIL] {$msg}\n";
        }
    }

    private function testarEstruturaMetodosController(): void
    {
        echo "--- [1] Estrutura e Ações do EstoqueController ---\n";
        $controller = new EstoqueController();

        $this->assert(method_exists($controller, 'index'), "EstoqueController possui action 'index'");
        $this->assert(!method_exists($controller, 'movimentacoes'), "EstoqueController NÃO possui action 'movimentacoes' (removida conforme solicitação)");
        $this->assert(method_exists($controller, 'perdas'), "EstoqueController possui action 'perdas'");
        $this->assert(method_exists($controller, 'perdasSalvar'), "EstoqueController possui action 'perdasSalvar'");
        $this->assert(method_exists($controller, 'atualizarEstoqueMinimo'), "EstoqueController possui action 'atualizarEstoqueMinimo'");
        $this->assert(method_exists($controller, 'importarXml'), "EstoqueController possui action 'importarXml'");
        $this->assert(method_exists($controller, 'importarXmlAnalisar'), "EstoqueController possui action 'importarXmlAnalisar'");
        $this->assert(method_exists($controller, 'importarXmlProcessar'), "EstoqueController possui action 'importarXmlProcessar'");
        $this->assert(method_exists($controller, 'importarXmlCancelar'), "EstoqueController possui action 'importarXmlCancelar'");
        $this->assert(method_exists($controller, 'notasFiscais'), "EstoqueController possui action 'notasFiscais'");
        $this->assert(method_exists($controller, 'notaFiscalDetalhes'), "EstoqueController possui action 'notaFiscalDetalhes'");
        $this->assert(method_exists($controller, 'downloadXml'), "EstoqueController possui action 'downloadXml'");
    }

    private function testarPermissoesRbac(): void
    {
        echo "\n--- [2] Teste de Permissões RBAC para Estoque & XML ---\n";

        Session::start();

        // 1. Operador tem acesso a lançamento de perdas e pausa rápida de produtos
        Auth::loginUsuario([
            'id'      => 2,
            'usuario' => 'operador-estoque',
            'nome'    => 'Operador Estoque',
            'email'   => 'operador@escola.com',
            'perfil'  => RBAC::ROLE_OPERADOR
        ]);
        $this->assert(RBAC::can('lancamento_perdas') === true, "Perfil 'operador' tem permissão 'lancamento_perdas'");
        $this->assert(RBAC::can('pausa_rapida_produtos') === true, "Perfil 'operador' tem permissão 'pausa_rapida_produtos'");
        $this->assert(RBAC::can('login_painel_cantina') === true, "Perfil 'operador' tem permissão 'login_painel_cantina'");
        $this->assert(RBAC::can('entrada_xml_nfe') === false, "Perfil 'operador' NÃO tem permissão 'entrada_xml_nfe'");

        // 2. Administrador tem acesso pleno
        Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'admin-cantina',
            'nome'    => 'Administrador Geral',
            'email'   => 'admin@escola.com',
            'perfil'  => RBAC::ROLE_ADMINISTRADOR
        ]);
        $this->assert(RBAC::can('lancamento_perdas') === true, "Perfil 'administrador' tem permissão 'lancamento_perdas'");
        $this->assert(RBAC::can('pausa_rapida_produtos') === true, "Perfil 'administrador' tem permissão 'pausa_rapida_produtos'");
        $this->assert(RBAC::can('entrada_xml_nfe') === true, "Perfil 'administrador' tem permissão 'entrada_xml_nfe'");
        $this->assert(RBAC::can('cadastro_produtos_precos') === true, "Perfil 'administrador' tem permissão 'cadastro_produtos_precos'");

        // 3. Responsável é bloqueado em todas as ações de estoque
        Auth::loginResponsavel([
            'id'            => 10,
            'cpf'           => '12345678901',
            'nome'          => 'Responsável Bloqueado',
            'email'         => 'bloq@familia.com',
            'whatsapp'      => '11999999999',
            'saldo_atual'   => 100.00,
            'bloqueado_med' => 0
        ]);
        $this->assert(RBAC::can('lancamento_perdas') === false, "Perfil 'responsavel' é bloqueado para 'lancamento_perdas'");
        $this->assert(RBAC::can('pausa_rapida_produtos') === false, "Perfil 'responsavel' é bloqueado para 'pausa_rapida_produtos'");
        $this->assert(RBAC::can('entrada_xml_nfe') === false, "Perfil 'responsavel' é bloqueado para 'entrada_xml_nfe'");

        Auth::logout();
    }

    private function testarAcessoVisitanteHttpReal(): void
    {
        echo "\n--- [3] Simulação HTTP Real (cURL) em /estoque ---\n";

        $ch = curl_init('http://localhost/estoque');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assert(
            $httpCode === 302,
            "Requisição HTTP direta de visitante anônimo para /estoque retorna HTTP 302 Redirection"
        );
        $this->assert(
            str_contains((string)$response, 'Location: /login'),
            "Cabeçalho de redirecionamento aponta para '/login'"
        );
    }

    private function testarIntegridadeViewsEstoque(): void
    {
        echo "\n--- [4] Integridade das Telas e Componentes da Fase 14 ---\n";

        $views = [
            'app/views/estoque/index.php' => ['Controle de Estoque & Saldos Físicos', 'Grade de Produtos & Inventário Físico', 'Total de Itens'],
            'app/views/estoque/perdas.php' => ['Registro Operacional de Perdas & Descarte', 'Nova Baixa de Mercadoria', 'formPerda'],
            'app/views/estoque/entrada.php' => ['Entrada de Estoque', 'fornecedor', 'btnGravarLote'],
            'app/views/estoque/conferir_xml.php' => ['Conferência de Entrada', 'fator_conversao', 'btnConfirmarEntrada'],
            'app/views/estoque/notas_fiscais.php' => ['Notas Fiscais de Entrada (NF-e)', 'Total de Notas'],
            'app/views/estoque/nota_fiscal_detalhes.php' => ['Detalhamento da NF-e', 'Baixar XML Original', 'Voltar às Notas Fiscais']
        ];

        $movimentacoesView = __DIR__ . '/../app/views/estoque/movimentacoes.php';
        $this->assert(!file_exists($movimentacoesView), "Visão app/views/estoque/movimentacoes.php foi excluída com sucesso");

        $importarXmlView = __DIR__ . '/../app/views/estoque/importar_xml.php';
        $this->assert(!file_exists($importarXmlView), "Visão app/views/estoque/importar_xml.php foi excluída com sucesso conforme solicitação");

        // Verifica que os 3 botões do cabeçalho de /estoque foram removidos
        $indexEstoque = file_get_contents(__DIR__ . '/../app/views/estoque/index.php');
        $this->assert(!str_contains($indexEstoque, 'Extrato de Movimentações'), "Botão 'Extrato de Movimentações' removido de app/views/estoque/index.php");
        $this->assert(!str_contains($indexEstoque, 'href="<?php echo $baseUrl; ?>/estoque/perdas" class="btn btn-enterprise btn-primary'), "Botão '+ Lançar Perda' do cabeçalho removido de app/views/estoque/index.php");
        $this->assert(!str_contains($indexEstoque, 'href="<?php echo $baseUrl; ?>/estoque/entrada" class="btn btn-enterprise btn-primary'), "Botão '+ Entrada de Estoque' do cabeçalho removido de app/views/estoque/index.php");

        foreach ($views as $caminhoRelativo => $palavrasChave) {
            $caminhoCompleto = __DIR__ . '/../' . $caminhoRelativo;
            $this->assert(file_exists($caminhoCompleto), "Arquivo de visão {$caminhoRelativo} existe");

            $conteudo = file_get_contents($caminhoCompleto);
            foreach ($palavrasChave as $chave) {
                $this->assert(
                    str_contains($conteudo, $chave),
                    "Visão {$caminhoRelativo} contém marcador essencial: '{$chave}'"
                );
            }
        }
    }

    private function testarFluxoRegistroPerdaWeb(): void
    {
        echo "\n--- [5] Fluxo Web Completo de Registro de Perda com Validação CSRF ---\n";

        Session::start();

        // Autentica como Administrador
        Auth::loginUsuario([
            'id'      => 1,
            'usuario' => 'admin-cantina',
            'nome'    => 'Administrador Geral',
            'email'   => 'admin@escola.com',
            'perfil'  => RBAC::ROLE_ADMINISTRADOR
        ]);

        // Cria produto de teste isolado
        $codUnico = 'TEST-PERDA-' . uniqid();
        $this->pdo->prepare("INSERT INTO produtos (categoria_id, codigo_interno, descricao, preco_venda, estoque_atual, estoque_reservado, estoque_minimo, tipo, status_pausa) 
                             VALUES (2, :cod, 'Produto Teste Perda Web', 4.00, 20, 0, 2, 'industrializado_continuo', 'ativo')")
                  ->execute([':cod' => $codUnico]);
        $prodId = (int)$this->pdo->lastInsertId();
        $saldoInicial = 20;

        $controller = new EstoqueController();

        // 1. Teste de rejeição por CSRF inválido
        $_POST = [
            'csrf_token'    => 'token_invalido_123',
            'produto_id'    => $prodId,
            'quantidade'    => 1,
            'motivo_perda'  => 'avaria',
            'justificativa' => 'Tentativa de invasão ou token expirado'
        ];

        // Limpa flashes anteriores
        Session::getFlash('danger');
        Session::getFlash('success');

        $controller->perdasSalvar();

        $flashesDanger = Session::getFlash('danger');
        $this->assert(
            !empty($flashesDanger) && str_contains($flashesDanger[0], 'CSRF'),
            "Action perdasSalvar bloqueia com segurança token CSRF inválido"
        );

        // 2. Teste de sucesso com CSRF válido
        $tokenValido = Session::getCsrfToken();
        $_POST = [
            'csrf_token'    => $tokenValido,
            'produto_id'    => $prodId,
            'quantidade'    => 2,
            'motivo_perda'  => 'avaria',
            'justificativa' => 'Produto caiu da prateleira e quebrou embalagem'
        ];

        $controller->perdasSalvar();

        $flashesSuccess = Session::getFlash('success');
        $this->assert(
            !empty($flashesSuccess) && str_contains($flashesSuccess[0], 'sucesso'),
            "Action perdasSalvar processa com sucesso a perda quando CSRF e dados são válidos"
        );

        // Verifica débito do estoque no MySQL
        $stmtSaldo = $this->pdo->prepare("SELECT estoque_atual FROM produtos WHERE id = :id");
        $stmtSaldo->execute([':id' => $prodId]);
        $saldoFinal = (int)$stmtSaldo->fetchColumn();

        $this->assert(
            $saldoFinal === ($saldoInicial - 2),
            "Saldo físico do produto foi debitado com exatidão no banco de dados (de {$saldoInicial} para {$saldoFinal})"
        );

        // Teardown
        $this->pdo->prepare("DELETE FROM estoque_movimentacoes WHERE produto_id = :id")->execute([':id' => $prodId]);
        $this->pdo->prepare("DELETE FROM logs_auditoria WHERE tabela_afetada = 'produtos' AND registro_id = :id")->execute([':id' => $prodId]);
        $this->pdo->prepare("DELETE FROM produtos WHERE id = :id")->execute([':id' => $prodId]);

        Auth::logout();
    }
}

$testeWeb = new EstoqueWebTest();
$testeWeb->run();
