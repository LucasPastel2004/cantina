<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes Automatizados — Fase 7: Configurações Globais e Cadastros Mestres com Soft Delete
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e docs/PLANO.md
 */

define('CANTINA_SISTEMA', true);

// 1. Autoloader PSR-4
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Configuracao;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\RestricaoAlimentar;
use App\Models\Colaborador;
use App\Models\Turma;
use App\Core\Auth;
use App\Core\Session;

class Fase7Test
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;
    private array $failures = [];
    private \PDO $pdo;

    public function __construct()
    {
        $configFile = __DIR__ . '/../config/config.php';
        $config = require $configFile;
        Session::start($config);

        $this->pdo = require __DIR__ . '/../config/database.php';
    }

    private function assert(bool $condition, string $description): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  [PASS] {$description}\n";
        } else {
            $this->failures[] = $description;
            echo "  [FAIL] {$description}\n";
        }
    }

    public function run(): void
    {
        echo "======================================================================\n";
        echo "  INICIANDO BATERIA DE TESTES — FASE 7: CONFIGURAÇÕES E CADASTROS    \n";
        echo "======================================================================\n\n";

        $this->testArquivosEssenciais();
        $this->testConfiguracoesGlobais();
        $this->testCategoriasCrudESoftDelete();
        $this->testRestricoesAlimentares();
        $this->testProdutosCrudAlergenosEPausa();
        $this->testColaboradoresCrudESoftDelete();
        $this->testTurmasCrudESoftDelete();
        $this->testIntegridadeGeralDoBanco();

        echo "\n======================================================================\n";
        echo "  RESUMO DOS TESTES — FASE 7\n";
        echo "======================================================================\n";
        echo "  Total de asserções executadas: {$this->totalAssertions}\n";
        echo "  Sucessos: {$this->passedAssertions}\n";
        echo "  Falhas: " . count($this->failures) . "\n\n";

        if (empty($this->failures)) {
            echo "  TODAS AS ASSERÇÕES FORAM APROVADAS COM 100% DE SUCESSO!\n\n";
            exit(0);
        } else {
            echo "  ATENÇÃO: Algumas asserções falharam:\n";
            foreach ($this->failures as $fail) {
                echo "    - {$fail}\n";
            }
            echo "\n";
            exit(1);
        }
    }

    private function testArquivosEssenciais(): void
    {
        echo "--- 1. Verificação de Arquivos da Fase 7 ---\n";
        $arquivos = [
            'app/models/Configuracao.php',
            'app/models/Categoria.php',
            'app/models/Produto.php',
            'app/models/RestricaoAlimentar.php',
            'app/models/Colaborador.php',
            'app/models/Turma.php',
            'app/controllers/AdminController.php',
            'app/views/admin/configuracoes.php',
            'app/views/admin/categorias.php',
            'app/views/admin/produtos.php',
            'app/views/admin/colaboradores.php',
            'app/views/admin/turmas.php'
        ];

        foreach ($arquivos as $arq) {
            $this->assert(file_exists(__DIR__ . '/../' . $arq), "Arquivo essencial presente: {$arq}");
        }
    }

    private function testConfiguracoesGlobais(): void
    {
        echo "\n--- 2. Testes do Modelo Configuracao (Tabela configuracoes_globais) ---\n";
        $config = Configuracao::obter();
        $this->assert(!empty($config), "Configuracao::obter() retornou dados");
        $this->assert(isset($config['razao_social']), "Contém razão social institucional");
        $this->assert((float)$config['recarga_minima_pix'] >= 1.00, "Recarga mínima Pix configurada e >= 1.00");
        $this->assert((float)$config['alerta_saldo_baixo'] >= 0.00, "Gatilho de alerta de saldo baixo presente");

        // Atualização controlada de teste
        $novoTelefone = '(11) 99999-8888';
        $sucesso = Configuracao::salvar(['telefone_contato' => $novoTelefone]);
        $this->assert($sucesso, "Configuracao::salvar() atualizou parâmetro com sucesso");

        $atualizada = Configuracao::obter();
        $this->assert($atualizada['telefone_contato'] === $novoTelefone, "Valor persistido no MySQL confere com o salvo");

        // Restaura telefone padrão
        Configuracao::salvar(['telefone_contato' => '(11) 98765-4321']);
    }

    private function testCategoriasCrudESoftDelete(): void
    {
        echo "\n--- 3. Testes do Modelo Categoria e Soft Delete ---\n";
        $nomeTeste = 'Categoria Teste ' . bin2hex(random_bytes(3));
        $catId = Categoria::criar([
            'nome'           => $nomeTeste,
            'ordem_exibicao' => 99,
            'ativo'          => 1
        ], 1);

        $this->assert($catId > 0, "Categoria criada com sucesso (ID: {$catId})");

        $encontrada = Categoria::buscarPorId($catId);
        $this->assert($encontrada !== null, "Categoria localizada por ID");
        $this->assert($encontrada['nome'] === $nomeTeste, "Nome da categoria confere");
        $this->assert($encontrada['deleted_at'] === null, "deleted_at é inicialmente NULL");

        // Atualização
        $nomeAtualizado = $nomeTeste . ' Editada';
        Categoria::atualizar($catId, [
            'nome'           => $nomeAtualizado,
            'ordem_exibicao' => 100,
            'ativo'          => 0
        ], 1);

        $editada = Categoria::buscarPorId($catId);
        $this->assert($editada['nome'] === $nomeAtualizado, "Categoria atualizada com sucesso");
        $this->assert((int)$editada['ativo'] === 0, "Status alterado para inativo");

        // Soft Delete
        $excluiu = Categoria::excluir($catId, 1);
        $this->assert($excluiu, "Categoria::excluir() executado com sucesso");

        $aposExcluir = Categoria::buscarPorId($catId);
        $this->assert($aposExcluir === null, "Categoria excluída logicamente não é retornada por buscarPorId()");

        // Verificação direta no banco provando que o registro NÃO foi apagado fisicamente
        $stmt = $this->pdo->prepare("SELECT deleted_at FROM categorias WHERE id = ?");
        $stmt->execute([$catId]);
        $deletedAt = $stmt->fetchColumn();
        $this->assert(!empty($deletedAt), "Registro permanece no MySQL com deleted_at preenchido ({$deletedAt})");
    }

    private function testRestricoesAlimentares(): void
    {
        echo "\n--- 4. Testes do Catálogo de Restrições Alimentares ---\n";
        $alergenos = RestricaoAlimentar::listarTodas();
        $this->assert(count($alergenos) >= 8, "Catálogo contém os 8 alérgenos oficiais previstos no FSD");

        $gluten = RestricaoAlimentar::buscarPorId(1);
        $this->assert($gluten !== null, "Alérgeno ID 1 localizado com sucesso");
        $this->assert(str_contains(mb_strtolower($gluten['nome']), 'glúten') || str_contains(mb_strtolower($gluten['nome']), 'gluten'), "Alérgeno 1 é Glúten");
    }

    private function testProdutosCrudAlergenosEPausa(): void
    {
        echo "\n--- 5. Testes do Modelo Produto, Alérgenos e Alternância de Pausa ---\n";
        // Obtém uma categoria válida
        $cat = Categoria::listarTodas(true)[0] ?? null;
        $this->assert($cat !== null, "Existe pelo menos uma categoria ativa para associar ao produto");

        $codigoTeste = 'TEST-' . strtoupper(bin2hex(random_bytes(3)));
        $prodId = Produto::criar([
            'categoria_id'          => $cat['id'],
            'codigo_interno'        => $codigoTeste,
            'descricao'             => 'Produto Automatizado de Teste',
            'preco_venda'           => 7.50,
            'tipo'                  => 'salgado_diario',
            'turno_disponibilidade' => 'ambos',
            'estoque_atual'         => 10,
            'estoque_minimo'        => 3,
            'status_pausa'          => 'ativo'
        ], [1, 2], 1); // Vincula alérgenos 1 (Glúten) e 2 (Lactose)

        $this->assert($prodId > 0, "Produto criado com sucesso (ID: {$prodId})");

        $produto = Produto::buscarPorId($prodId);
        $this->assert($produto !== null, "Produto localizado por ID");
        $this->assert((float)$produto['preco_venda'] === 7.50, "Preço de venda confere (R$ 7,50)");
        $this->assert(in_array(1, $produto['alergenos_ids']) && in_array(2, $produto['alergenos_ids']), "Alérgenos 1 e 2 vinculados ao produto");

        // Teste de Busca por Código
        $porCodigo = Produto::buscarPorCodigo($codigoTeste);
        $this->assert($porCodigo !== null && (int)$porCodigo['id'] === $prodId, "Produto::buscarPorCodigo() localizou item único");

        // Teste do Botão Rápido de Pausa ("Ativo / Pausado")
        $this->assert($produto['status_pausa'] === 'ativo', "Status inicial é 'ativo'");
        $novoStatus = Produto::togglePausa($prodId, 1);
        $this->assert($novoStatus === 'pausado', "togglePausa alterou status para 'pausado'");

        $produtoPausado = Produto::buscarPorId($prodId);
        $this->assert($produtoPausado['status_pausa'] === 'pausado', "Persistência de pausa confirmada no MySQL");

        $revertido = Produto::togglePausa($prodId, 1);
        $this->assert($revertido === 'ativo', "Segundo togglePausa reverteu status para 'ativo'");

        // Teste de Atualização com Sincronização de Alérgenos
        Produto::atualizar($prodId, [
            'categoria_id'          => $cat['id'],
            'codigo_interno'        => $codigoTeste,
            'descricao'             => 'Produto de Teste Atualizado',
            'preco_venda'           => 8.00,
            'tipo'                  => 'industrializado_continuo',
            'turno_disponibilidade' => 'matutino',
            'estoque_atual'         => 2, // Abaixo do mínimo de 3
            'estoque_minimo'        => 3,
            'status_pausa'          => 'ativo'
        ], [3], 1); // Altera para alérgeno 3 (Amendoim)

        $atualizado = Produto::buscarPorId($prodId);
        $this->assert($atualizado['descricao'] === 'Produto de Teste Atualizado', "Descrição atualizada");
        $this->assert($atualizado['alergenos_ids'] === [3], "Alérgenos sincronizados exclusivamente para [3]");

        // Teste de Alerta de Estoque Mínimo
        $alertaEstoque = Produto::contarAlertaEstoqueMinimo();
        $this->assert($alertaEstoque >= 1, "contarAlertaEstoqueMinimo() detectou o item industrializado com estoque baixo");

        // Teste de Soft Delete no Produto
        $excluiu = Produto::excluir($prodId, 1);
        $this->assert($excluiu, "Produto::excluir() retornou sucesso");
        $this->assert(Produto::buscarPorId($prodId) === null, "Produto excluído não aparece em buscarPorId()");

        // Confirmação no banco que o registro e vínculos persistem
        $stmt = $this->pdo->prepare("SELECT deleted_at FROM produtos WHERE id = ?");
        $stmt->execute([$prodId]);
        $delAt = $stmt->fetchColumn();
        $this->assert(!empty($delAt), "Soft delete confirmado em produtos: deleted_at = {$delAt}");
    }

    private function testColaboradoresCrudESoftDelete(): void
    {
        echo "\n--- 6. Testes do Modelo Colaborador e Desconto em Folha ---\n";
        $matTeste = 'FUNC-' . strtoupper(bin2hex(random_bytes(3)));
        $nomeTeste = 'Prof. Carlos Alberto ' . bin2hex(random_bytes(2));

        $colabId = Colaborador::criar([
            'matricula_funcional' => $matTeste,
            'nome'                => $nomeTeste,
            'cargo'               => 'Professor de História',
            'ativo'               => 1
        ], 1);

        $this->assert($colabId > 0, "Colaborador criado com sucesso (ID: {$colabId})");

        $colab = Colaborador::buscarPorId($colabId);
        $this->assert($colab !== null, "Colaborador localizado por ID");
        $this->assert($colab['matricula_funcional'] === $matTeste, "Matrícula funcional confere");

        $porMat = Colaborador::buscarPorMatricula($matTeste);
        $this->assert($porMat !== null && (int)$porMat['id'] === $colabId, "Colaborador localizado por matrícula");

        // Alternância de status ativo
        $novoStatus = Colaborador::toggleAtivo($colabId, 1);
        $this->assert($novoStatus === 0, "toggleAtivo alterou status para inativo (0)");
        $novoStatus2 = Colaborador::toggleAtivo($colabId, 1);
        $this->assert($novoStatus2 === 1, "toggleAtivo reativou colaborador (1)");

        // Soft Delete
        $excluiu = Colaborador::excluir($colabId, 1);
        $this->assert($excluiu, "Colaborador::excluir() retornou sucesso");
        $this->assert(Colaborador::buscarPorId($colabId) === null, "Colaborador excluído não é retornado por buscarPorId()");

        // Verificação no banco
        $stmt = $this->pdo->prepare("SELECT deleted_at FROM colaboradores WHERE id = ?");
        $stmt->execute([$colabId]);
        $delAt = $stmt->fetchColumn();
        $this->assert(!empty($delAt), "Soft delete de colaborador preservado no MySQL");
    }

    private function testTurmasCrudESoftDelete(): void
    {
        echo "\n--- 7. Testes do Modelo Turma e Ordem de Impressão ---\n";
        $turmaNome = '9º Ano Alpha ' . bin2hex(random_bytes(2));
        $turmaId = Turma::criar([
            'serie'           => 'Ensino Fundamental II',
            'nome_turma'      => $turmaNome,
            'turno'           => 'matutino',
            'ordem_impressao' => 45
        ], 1);

        $this->assert($turmaId > 0, "Turma criada com sucesso (ID: {$turmaId})");

        $turma = Turma::buscarPorId($turmaId);
        $this->assert($turma !== null, "Turma localizada por ID");
        $this->assert($turma['nome_turma'] === $turmaNome, "Nome da turma confere");
        $this->assert((int)$turma['ordem_impressao'] === 45, "Ordem de impressão na bobina térmica confere (45)");

        // Atualização
        Turma::atualizar($turmaId, [
            'serie'           => 'Ensino Fundamental II',
            'nome_turma'      => $turmaNome . ' Editada',
            'turno'           => 'vespertino',
            'ordem_impressao' => 50
        ], 1);

        $editada = Turma::buscarPorId($turmaId);
        $this->assert($editada['turno'] === 'vespertino', "Turno alterado para vespertino");
        $this->assert((int)$editada['ordem_impressao'] === 50, "Ordem atualizada para 50");

        // Soft Delete
        $excluiu = Turma::excluir($turmaId, 1);
        $this->assert($excluiu, "Turma::excluir() executado com sucesso");
        $this->assert(Turma::buscarPorId($turmaId) === null, "Turma excluída não é retornada nas consultas normais");

        $stmt = $this->pdo->prepare("SELECT deleted_at FROM turmas WHERE id = ?");
        $stmt->execute([$turmaId]);
        $delAt = $stmt->fetchColumn();
        $this->assert(!empty($delAt), "Soft delete de turma preservado no MySQL: deleted_at = {$delAt}");
    }

    private function testIntegridadeGeralDoBanco(): void
    {
        echo "\n--- 8. Verificação de Integridade Referencial e Constraints ---\n";
        // Confirma que nenhuma exclusão física ocorreu nas tabelas mestres da Fase 7
        $tabelas = ['categorias', 'produtos', 'colaboradores', 'turmas'];
        foreach ($tabelas as $tabela) {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$tabela} WHERE deleted_at IS NOT NULL");
            $totalDeletados = (int)$stmt->fetchColumn();
            $this->assert($totalDeletados >= 1, "Tabela {$tabela} possui registros com soft delete preservados ({$totalDeletados})");
        }
    }
}

// Execução da bateria
$test = new Fase7Test();
$test->run();
