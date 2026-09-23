<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Rotas Web, Controller e RBAC da Fase 14 (Gestão de Estoque, XML e Perdas)
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

namespace Tests;

define('CANTINA_SISTEMA', true);

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
        $this->assert(method_exists($controller, 'movimentacoes'), "EstoqueController possui action 'movimentacoes'");
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
            'app/views/estoque/movimentacoes.php' => ['Extrato Cronológico de Movimentações', 'Tipo de Movimentação', 'Voltar ao Estoque'],
            'app/views/estoque/perdas.php' => ['Registro Operacional de Perdas & Descarte', 'Nova Baixa de Mercadoria', 'formPerda'],
            'app/views/estoque/importar_xml.php' => ['Upload da Nota Fiscal Eletrônica (NF-e)', 'arquivo_xml', 'storage/uploads/private/'],
            'app/views/estoque/conferir_xml.php' => ['Conferência de Entrada', 'fator_conversao', 'btnConfirmarEntrada'],
            'app/views/estoque/notas_fiscais.php' => ['Notas Fiscais de Entrada (NF-e)', 'Total de Notas', 'Nova Entrada via XML'],
            'app/views/estoque/nota_fiscal_detalhes.php' => ['Detalhamento da NF-e', 'Baixar XML Original', 'Voltar às Notas Fiscais']
        ];

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
}

$testeWeb = new EstoqueWebTest();
$testeWeb->run();
