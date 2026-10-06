<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes: Bloqueio Rigoroso de Recebimento de Fornecedor em Datas Retroativas e Futuras
 * 
 * Valida:
 * 1. Bloqueio no Model (Pedido::confirmarRecebimentoFornecedor) para datas retroativas (DomainException);
 * 2. Bloqueio no Model (Pedido::confirmarRecebimentoFornecedor) para datas futuras (DomainException);
 * 3. Bloqueio no Controller (PedidosController::recebimentoConfirmar) com redirecionamento e flash error;
 * 4. Validação de dados passados para a View (dataHoje, isHoje, podeConferir);
 * 5. Renderização da View para data retroativa não recebida (somente leitura, alerta âmbar, sem inputs, sem botão);
 * 6. Renderização da View para data futura (alerta azul, sem inputs, sem botão);
 * 7. Renderização da View para data de hoje (inputs ativos, botão de preenchimento e confirmação);
 * 8. Renderização da View para data com recebimento já confirmado anteriormente (modo espelho/auditoria).
 */

define('CANTINA_SISTEMA', true);
// NOTA: Intencionalmente NÃO definimos CANTINA_TEST_MODE para testar a trava real de produção!

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/core/Router.php';

require_once __DIR__ . '/../app/models/Categoria.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/Turma.php';
require_once __DIR__ . '/../app/models/Aluno.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/models/CalendarioEscolar.php';
require_once __DIR__ . '/../app/models/ItemPedido.php';
require_once __DIR__ . '/../app/models/Pedido.php';
require_once __DIR__ . '/../app/models/Usuario.php';

require_once __DIR__ . '/../app/controllers/PedidosController.php';

use App\Models\Pedido;
use App\Controllers\PedidosController;
use App\Core\Session;
use App\Core\Auth;

class RecebimentoBloqueioDatasTest
{
    private int $passou = 0;
    private int $falhou = 0;
    private array $erros = [];

    public function executar(): void
    {
        echo "====================================================================\n";
        echo "SUÍTE DE TESTES: TRAVA DE RECEBIMENTO DE FORNECEDOR POR DATA\n";
        echo "====================================================================\n";

        Session::start();

        $this->testarBloqueioModelDataRetroativa();
        $this->testarBloqueioModelDataFutura();
        $this->testarBloqueioControllerDataRetroativa();
        $this->testarBloqueioControllerDataFutura();
        $this->testarRenderizacaoViewDataRetroativaNaoRecebida();
        $this->testarRenderizacaoViewDataFutura();
        $this->testarRenderizacaoViewDataHoje();
        $this->testarRenderizacaoViewDataRecebida();

        echo "\n====================================================================\n";
        echo "RESULTADO FINAL: {$this->passou} aprovados, {$this->falhou} falhas.\n";
        echo "====================================================================\n";

        if ($this->falhou > 0) {
            echo "\nDetalhamento das falhas:\n";
            foreach ($this->erros as $err) {
                echo " - {$err}\n";
            }
            exit(1);
        }
    }

    private function assert(bool $condicao, string $mensagem): void
    {
        if ($condicao) {
            $this->passou++;
            echo "  [OK] {$mensagem}\n";
        } else {
            $this->falhou++;
            $this->erros[] = $mensagem;
            echo "  [FALHA] {$mensagem}\n";
        }
    }

    /**
     * 1. Model: Pedido::confirmarRecebimentoFornecedor deve lançar DomainException para data retroativa
     */
    private function testarBloqueioModelDataRetroativa(): void
    {
        echo "\n--- 1. Trava no Model para Data Retroativa (ex: 2026-09-29) ---\n";

        $dataPassada = '2026-09-29';
        $itensTeste = [
            ['produto_id' => 1, 'qtd_esperada' => 10, 'qtd_entregue' => 10]
        ];

        $excecaoLancada = false;
        $mensagemExcecao = '';

        try {
            Pedido::confirmarRecebimentoFornecedor(
                $dataPassada,
                'matutino',
                1,
                $itensTeste,
                'Tentativa de recebimento retroativo'
            );
        } catch (\DomainException $e) {
            $excecaoLancada = true;
            $mensagemExcecao = $e->getMessage();
        } catch (\Throwable $e) {
            $this->assert(false, "Exceção inesperada: " . get_class($e) . " - " . $e->getMessage());
            return;
        }

        $this->assert($excecaoLancada, "Pedido::confirmarRecebimentoFornecedor bloqueou data passada com DomainException");
        $this->assert(
            str_contains($mensagemExcecao, "só podem ser realizadas na data de hoje"),
            "Mensagem da exceção detalha restrição à data de hoje (" . substr($mensagemExcecao, 0, 80) . "...)"
        );
        $this->assert(
            str_contains($mensagemExcecao, "Não é permitido registrar recebimento físico para datas retroativas"),
            "Mensagem da exceção cita explicitamente a preservação do estoque"
        );
    }

    /**
     * 2. Model: Pedido::confirmarRecebimentoFornecedor deve lançar DomainException para data futura
     */
    private function testarBloqueioModelDataFutura(): void
    {
        echo "\n--- 2. Trava no Model para Data Futura (ex: 2026-10-20) ---\n";

        $dataFutura = '2026-10-20';
        $itensTeste = [
            ['produto_id' => 1, 'qtd_esperada' => 10, 'qtd_entregue' => 10]
        ];

        $excecaoLancada = false;
        $mensagemExcecao = '';

        try {
            Pedido::confirmarRecebimentoFornecedor(
                $dataFutura,
                'matutino',
                1,
                $itensTeste,
                'Tentativa de recebimento futuro'
            );
        } catch (\DomainException $e) {
            $excecaoLancada = true;
            $mensagemExcecao = $e->getMessage();
        } catch (\Throwable $e) {
            $this->assert(false, "Exceção inesperada: " . get_class($e));
            return;
        }

        $this->assert($excecaoLancada, "Pedido::confirmarRecebimentoFornecedor bloqueou data futura com DomainException");
        $this->assert(
            str_contains($mensagemExcecao, "só podem ser realizadas na data de hoje"),
            "Mensagem de bloqueio de data futura consistente com a política operacional"
        );
    }

    /**
     * 3. Controller: PedidosController::recebimentoConfirmar deve redirecionar e setar flash de erro para data retroativa
     */
    private function testarBloqueioControllerDataRetroativa(): void
    {
        echo "\n--- 3. Trava no Controller (POST /operacional/recebimento/confirmar) para Data Retroativa ---\n";

        $controller = new class extends PedidosController {
            public string $ultimoRedirect = '';
            public function redirect(string $path, int $statusCode = 302): void {
                $this->ultimoRedirect = $path;
            }
        };

        // Simula login de operador
        $_SESSION['usuario'] = [
            'id' => 1,
            'nome' => 'Operador Teste',
            'perfil' => 'operador',
            'email' => 'operador@escola.com'
        ];

        $csrf = Session::generateCsrfToken();
        $_POST = [
            'csrf_token' => $csrf,
            'data_consumo' => '2026-09-29',
            'turno_consumo' => 'matutino',
            'observacao' => 'Teste post retroativo',
            'itens' => [
                ['produto_id' => 1, 'qtd_esperada' => 5, 'qtd_entregue' => 5]
            ]
        ];

        $controller->recebimentoConfirmar();

        $flashErro = Session::getFlash('error');
        if (is_array($flashErro)) {
            $flashErro = implode(' ', $flashErro);
        }

        $this->assert(!empty($flashErro), "Controller gravou flash message de erro ao tentar postar data retroativa");
        $this->assert(
            str_contains($flashErro, "estritamente na data de hoje"),
            "Mensagem de erro do Controller informa restrição à data de hoje ($flashErro)"
        );
        $this->assert(
            str_contains($controller->ultimoRedirect, 'recebimento?data=2026-09-29'),
            "Controller redirecionou de volta para a tela de recebimento ({$controller->ultimoRedirect})"
        );
    }

    /**
     * 4. Controller: PedidosController::recebimentoConfirmar deve redirecionar e setar flash de erro para data futura
     */
    private function testarBloqueioControllerDataFutura(): void
    {
        echo "\n--- 4. Trava no Controller para Data Futura ---\n";

        $controller = new class extends PedidosController {
            public string $ultimoRedirect = '';
            public function redirect(string $path, int $statusCode = 302): void {
                $this->ultimoRedirect = $path;
            }
        };

        $csrf = Session::generateCsrfToken();
        $_POST = [
            'csrf_token' => $csrf,
            'data_consumo' => '2026-10-25',
            'turno_consumo' => 'vespertino',
            'observacao' => 'Teste post futuro',
            'itens' => [
                ['produto_id' => 1, 'qtd_esperada' => 5, 'qtd_entregue' => 5]
            ]
        ];

        $controller->recebimentoConfirmar();

        $flashErro = Session::getFlash('error');
        if (is_array($flashErro)) {
            $flashErro = implode(' ', $flashErro);
        }

        $this->assert(!empty($flashErro), "Controller gravou flash message de erro para data futura");
        $this->assert(
            str_contains($controller->ultimoRedirect, 'recebimento?data=2026-10-25'),
            "Controller redirecionou com segurança para a página solicitada"
        );
    }

    /**
     * 5. View: Renderização para data retroativa não recebida
     */
    private function testarRenderizacaoViewDataRetroativaNaoRecebida(): void
    {
        echo "\n--- 5. Renderização da View para Data Retroativa Não Conferida ---\n";

        $hoje = date('Y-m-d');
        $dataPassada = '2026-09-29';

        $itensMock = [
            [
                'produto_id' => 10,
                'codigo_interno' => 'PRD-10',
                'descricao' => 'Coxinha de Frango',
                'categoria_nome' => 'Salgados',
                'quantidade_total' => 20
            ]
        ];

        $html = $this->renderizarViewRecebimento([
            'baseUrl'           => '',
            'dataConsumo'       => $dataPassada,
            'turnoConsumo'      => 'matutino',
            'dadosCorte'        => ['itens' => $itensMock, 'total_pecas' => 20],
            'statusRecebimento' => null,
            'dataHoje'          => $hoje,
            'isHoje'            => false,
            'csrf_token'        => 'token-teste'
        ]);

        $this->assert(
            str_contains($html, 'Não Conferido na Data (Bloqueado)'),
            "View exibe badge 'Não Conferido na Data (Bloqueado)'"
        );
        $this->assert(
            str_contains($html, 'Conferência Retroativa Bloqueada'),
            "View exibe título do alerta âmbar 'Conferência Retroativa Bloqueada'"
        );
        $this->assert(
            str_contains($html, 'não é permitido dar entrada retroativa'),
            "View exibe texto explicativo sobre proteção de estoque contábil"
        );
        $this->assert(
            str_contains($html, 'Ir para Recebimento de Hoje'),
            "View exibe botão de atalho rápido 'Ir para Recebimento de Hoje'"
        );
        $this->assert(
            !str_contains($html, '<input type="number"'),
            "View NÃO renderiza inputs numéricos editáveis (<input type=\"number\") para data passada"
        );
        $this->assert(
            !str_contains($html, 'Confirmar Recebimento e Dar Entrada'),
            "View NÃO renderiza botão de submissão 'Confirmar Recebimento e Dar Entrada'"
        );
        $this->assert(
            !str_contains($html, 'Preencher com Valores Esperados'),
            "View NÃO renderiza botão de preenchimento automático para data passada"
        );
    }

    /**
     * 6. View: Renderização para data futura
     */
    private function testarRenderizacaoViewDataFutura(): void
    {
        echo "\n--- 6. Renderização da View para Data Futura ---\n";

        $hoje = date('Y-m-d');
        $dataFutura = '2026-10-20';

        $itensMock = [
            [
                'produto_id' => 10,
                'codigo_interno' => 'PRD-10',
                'descricao' => 'Coxinha de Frango',
                'categoria_nome' => 'Salgados',
                'quantidade_total' => 15
            ]
        ];

        $html = $this->renderizarViewRecebimento([
            'baseUrl'           => '',
            'dataConsumo'       => $dataFutura,
            'turnoConsumo'      => 'matutino',
            'dadosCorte'        => ['itens' => $itensMock, 'total_pecas' => 15],
            'statusRecebimento' => null,
            'dataHoje'          => $hoje,
            'isHoje'            => false,
            'csrf_token'        => 'token-teste'
        ]);

        $this->assert(
            str_contains($html, 'Data Futura (Aguardando Entrega)'),
            "View exibe badge 'Data Futura (Aguardando Entrega)'"
        );
        $this->assert(
            str_contains($html, 'Entrega Futura'),
            "View exibe alerta de Entrega Futura"
        );
        $this->assert(
            str_contains($html, 'Ver no Painel de Corte'),
            "View exibe link direcionando ao Painel de Corte"
        );
        $this->assert(
            !str_contains($html, '<input type="number"'),
            "View NÃO renderiza inputs numéricos (<input type=\"number\") para data futura"
        );
        $this->assert(
            !str_contains($html, 'Confirmar Recebimento e Dar Entrada'),
            "View NÃO renderiza botão de confirmar para data futura"
        );
    }

    /**
     * 7. View: Renderização para data de hoje (Fluxo Ativo de Conferência)
     */
    private function testarRenderizacaoViewDataHoje(): void
    {
        echo "\n--- 7. Renderização da View para Data de Hoje (Aguardando Conferência) ---\n";

        $hoje = date('Y-m-d');

        $itensMock = [
            [
                'produto_id' => 10,
                'codigo_interno' => 'PRD-10',
                'descricao' => 'Coxinha de Frango',
                'categoria_nome' => 'Salgados',
                'quantidade_total' => 25
            ]
        ];

        $html = $this->renderizarViewRecebimento([
            'baseUrl'           => '',
            'dataConsumo'       => $hoje,
            'turnoConsumo'      => 'matutino',
            'dadosCorte'        => ['itens' => $itensMock, 'total_pecas' => 25],
            'statusRecebimento' => null,
            'dataHoje'          => $hoje,
            'isHoje'            => true,
            'csrf_token'        => 'token-teste'
        ]);

        $this->assert(
            str_contains($html, 'Aguardando Conferência (Hoje)'),
            "View exibe badge 'Aguardando Conferência (Hoje)'"
        );
        $this->assert(
            str_contains($html, 'input-entregue'),
            "View renderiza inputs numéricos (.input-entregue) para a data de hoje"
        );
        $this->assert(
            str_contains($html, 'Preencher com Valores Esperados'),
            "View renderiza botão auxiliar 'Preencher com Valores Esperados'"
        );
        $this->assert(
            str_contains($html, 'Confirmar Recebimento e Dar Entrada'),
            "View renderiza botão principal 'Confirmar Recebimento e Dar Entrada'"
        );
    }

    /**
     * 8. View: Renderização para remessa já recebida anteriormente
     */
    private function testarRenderizacaoViewDataRecebida(): void
    {
        echo "\n--- 8. Renderização da View para Remessa Já Recebida ---\n";

        $dataPassada = '2026-09-29';
        $hoje = date('Y-m-d');

        $itensMock = [
            [
                'produto_id' => 10,
                'codigo_interno' => 'PRD-10',
                'descricao' => 'Coxinha de Frango',
                'categoria_nome' => 'Salgados',
                'quantidade_total' => 20
            ]
        ];

        $statusRecebimentoMock = [
            'recebido' => true,
            'operador_nome' => 'Maria Silva',
            'data_recebimento_fmt' => '29/09/2026 08:15',
            'itens_entregues' => [10 => 22] // Sobra de 2 un
        ];

        $html = $this->renderizarViewRecebimento([
            'baseUrl'           => '',
            'dataConsumo'       => $dataPassada,
            'turnoConsumo'      => 'matutino',
            'dadosCorte'        => ['itens' => $itensMock, 'total_pecas' => 20],
            'statusRecebimento' => $statusRecebimentoMock,
            'dataHoje'          => $hoje,
            'isHoje'            => false,
            'csrf_token'        => 'token-teste'
        ]);

        $this->assert(
            str_contains($html, 'Recebimento Confirmado'),
            "View exibe status indicator 'Recebimento Confirmado'"
        );
        $this->assert(
            str_contains($html, 'Recebimento Físico Concluído'),
            "View exibe alerta de sucesso com dados do operador que conferiu"
        );
        $this->assert(
            str_contains($html, 'Maria Silva'),
            "View cita o operador responsável pela conferência"
        );
        $this->assert(
            str_contains($html, '+2 un (sobra)'),
            "View calcula e exibe sobra conferida de 2 unidades"
        );
        $this->assert(
            !str_contains($html, 'Confirmar Recebimento e Dar Entrada'),
            "View NÃO renderiza botão de confirmação para remessa já finalizada"
        );
    }

    /**
     * Auxiliar de renderização de view via buffer de saída
     */
    private function renderizarViewRecebimento(array $vars): string
    {
        extract($vars);
        ob_start();
        require __DIR__ . '/../app/views/operacional/recebimento.php';
        return ob_get_clean() ?: '';
    }
}

// Execução
$suite = new RecebimentoBloqueioDatasTest();
$suite->executar();
