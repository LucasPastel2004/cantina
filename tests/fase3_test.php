<?php
/**
 * Suíte de Testes Automatizados — Fase 3
 * Cantina Web — Colégio Monteiro Lobato
 * 
 * Validação da Conexão PDO, Migrações 001 a 012, Dados Iniciais,
 * Integridade Referencial e Integração com App\Core\Model.
 */

define('CANTINA_SISTEMA', true);

require_once dirname(__DIR__) . '/app/core/Model.php';

use App\Core\Model;

class Fase3Test
{
    private \PDO $pdo;
    private int $assertions = 0;
    private int $failures = 0;

    public function __construct()
    {
        $this->pdo = require dirname(__DIR__) . '/config/database.php';
    }

    public function assert(bool $condition, string $message): void
    {
        $this->assertions++;
        if ($condition) {
            echo "  [PASS] {$message}\n";
        } else {
            $this->failures++;
            echo "  [FAIL] {$message}\n";
        }
    }

    public function run(): void
    {
        echo "======================================================\n";
        echo "   Bateria de Testes Automatizados — Fase 3\n";
        echo "======================================================\n\n";

        $this->testConexaoSingleton();
        $this->testMigracoesControle();
        $this->testTabelasCriadas();
        $this->testCargasIniciais();
        $this->testIntegridadeReferencial();
        $this->testModelIntegracaoESoftDelete();

        echo "\n------------------------------------------------------\n";
        echo "Resultado: {$this->assertions} asserções executadas.\n";
        if ($this->failures === 0) {
            echo "STATUS: TODOS OS TESTES PASSARAM COM SUCESSO! [100% OK]\n";
        } else {
            echo "STATUS: {$this->failures} FALHA(S) ENCONTRADA(S)!\n";
            exit(1);
        }
        echo "------------------------------------------------------\n";
    }

    private function testConexaoSingleton(): void
    {
        echo "1. Testando Conexão Singleton e Configurações PDO:\n";
        
        $this->assert($this->pdo instanceof \PDO, "Conexão retornou instância válida de PDO");
        $this->assert(Database::getInstance() === $this->pdo, "Database::getInstance() respeita o padrão Singleton retornando a mesma instância");
        
        $errmode = $this->pdo->getAttribute(\PDO::ATTR_ERRMODE);
        $this->assert($errmode === \PDO::ERRMODE_EXCEPTION, "PDO::ATTR_ERRMODE está configurado para ERRMODE_EXCEPTION");

        $emulate = (bool)$this->pdo->getAttribute(\PDO::ATTR_EMULATE_PREPARES);
        $this->assert($emulate === false, "PDO::ATTR_EMULATE_PREPARES está desativado (Prepared Statements estritos nativos)");

        $fetchMode = $this->pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
        $this->assert($fetchMode === \PDO::FETCH_ASSOC, "PDO::ATTR_DEFAULT_FETCH_MODE está configurado para FETCH_ASSOC");
    }

    private function testMigracoesControle(): void
    {
        echo "\n2. Testando Tabela migrations_controle:\n";

        $stmt = $this->pdo->query("SELECT COUNT(*) FROM migrations_controle");
        $total = (int)$stmt->fetchColumn();
        $this->assert($total >= 12, "Tabela migrations_controle registrou pelo menos 12 migrações aplicadas (total atual: {$total})");

        $stmt = $this->pdo->query("SELECT migration_arquivo FROM migrations_controle ORDER BY migration_arquivo ASC");
        $files = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $expectedFiles = [
            '001_create_migrations_table.php',
            '002_create_configuracoes_globais_table.php',
            '003_create_usuarios_table.php',
            '004_create_responsaveis_table.php',
            '005_create_turmas_table.php',
            '006_create_alunos_table.php',
            '007_create_restricoes_alimentares_table.php',
            '008_create_aluno_restricoes_table.php',
            '009_create_colaboradores_table.php',
            '010_create_categorias_table.php',
            '011_create_produtos_table.php',
            '012_create_produto_alergenos_table.php',
        ];

        $contemTodas = (array_intersect($expectedFiles, $files) === $expectedFiles);
        $this->assert($contemTodas, "Todas as 12 migrações 001 a 012 estão registradas no controle");
    }

    private function testTabelasCriadas(): void
    {
        echo "\n3. Testando Existência e Integridade das 12 Tabelas no MySQL:\n";

        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $expectedTables = [
            'migrations_controle',
            'configuracoes_globais',
            'usuarios',
            'responsaveis',
            'turmas',
            'alunos',
            'restricoes_alimentares',
            'aluno_restricoes',
            'colaboradores',
            'categorias',
            'produtos',
            'produto_alergenos'
        ];

        foreach ($expectedTables as $table) {
            $this->assert(in_array($table, $tables), "Tabela [{$table}] existe no banco de dados");
        }
    }

    private function testCargasIniciais(): void
    {
        echo "\n4. Testando Cargas Iniciais e Parâmetros Mestres do FSD:\n";

        // 4.1 Configuracoes globais
        $stmt = $this->pdo->query("SELECT * FROM configuracoes_globais LIMIT 1");
        $config = $stmt->fetch();
        $this->assert(!empty($config), "Registro único de configurações globais encontrado");
        $this->assert((float)$config['recarga_minima_pix'] === 10.00, "Parâmetro recarga_minima_pix configurado para R$ 10,00");
        $this->assert((float)$config['alerta_saldo_baixo'] === 15.00, "Parâmetro alerta_saldo_baixo configurado para R$ 15,00");
        $this->assert((int)$config['timeout_sessao_minutos'] === 60, "Timeout de sessão configurado para 60 minutos conforme FSD");

        // 4.2 Usuário Administrador Inicial
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute(['admin']);
        $admin = $stmt->fetch();
        $this->assert(!empty($admin), "Usuário administrador [admin] cadastrado");
        $this->assert($admin['perfil'] === 'administrador', "Perfil do admin é 'administrador'");
        $this->assert(password_verify('Admin@123', $admin['senha_hash']), "Senha do admin é válida e criptografada via Bcrypt");

        // 4.3 Alérgenos Padronizados (8 alérgenos do FSD)
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM restricoes_alimentares");
        $totalAlergenos = (int)$stmt->fetchColumn();
        $this->assert($totalAlergenos === 8, "Catálogo de restrições alimentares contém exatamente os 8 alérgenos oficiais");

        $stmt = $this->pdo->query("SELECT nome FROM restricoes_alimentares ORDER BY nome ASC");
        $alergenos = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $this->assert(in_array('Glúten', $alergenos), "Alérgeno 'Glúten' presente");
        $this->assert(in_array('Lactose', $alergenos), "Alérgeno 'Lactose' presente");
        $this->assert(in_array('Amendoim', $alergenos), "Alérgeno 'Amendoim' presente");
        $this->assert(in_array('Ovos', $alergenos), "Alérgeno 'Ovos' presente");

        // 4.4 Categorias de Produtos (Verifica a presença das 4 oficiais do FSD)
        $stmt = $this->pdo->query("SELECT nome FROM categorias WHERE deleted_at IS NULL");
        $nomesCategorias = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $possuiAs4Oficiais = in_array('Salgados Assados', $nomesCategorias) 
                          && in_array('Sucos Naturais', $nomesCategorias) 
                          && in_array('Industrializados', $nomesCategorias) 
                          && in_array('Sobremesas', $nomesCategorias);
        $this->assert($possuiAs4Oficiais, "Catálogo de categorias contém as 4 classificações oficiais do FSD");
    }

    private function testIntegridadeReferencial(): void
    {
        echo "\n5. Testando Integridade Referencial (Foreign Keys & Constraints):\n";

        // 5.1 Tentativa de inserir aluno com responsável inexistente (deve falhar por FK)
        $violouFk = false;
        try {
            $stmt = $this->pdo->prepare("INSERT INTO alunos (responsavel_id, turma_id, matricula, nome, turno) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([999999, 999999, 'MAT_TESTE_ERR', 'Aluno Teste', 'matutino']);
        } catch (\PDOException $e) {
            $violouFk = true;
        }
        $this->assert($violouFk, "Inserção com chave estrangeira inexistente foi bloqueada pelo MySQL (Foreign Key Constraint)");

        // 5.2 Criação controlada de registro para teste com CPF dinâmico
        $cpfTeste = '88' . str_pad((string)random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        $stmt = $this->pdo->prepare("INSERT INTO responsaveis (cpf, nome, email, whatsapp, senha_hash) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$cpfTeste, 'Pai de Teste FK', 'pai@teste.com', '(11) 99999-0000', 'hash_teste']);
        $respId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare("INSERT INTO turmas (serie, nome_turma, turno, ordem_impressao) VALUES (?, ?, ?, ?)");
        $stmt->execute(['1º Ano', 'Turma A Teste', 'matutino', 1]);
        $turmaId = (int)$this->pdo->lastInsertId();

        $matTeste = 'MAT_FK_' . time();
        $stmt = $this->pdo->prepare("INSERT INTO alunos (responsavel_id, turma_id, matricula, nome, turno) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$respId, $turmaId, $matTeste, 'Filho de Teste FK', 'matutino']);
        $alunoId = (int)$this->pdo->lastInsertId();

        // 5.3 Vincula alérgeno ao aluno
        $stmt = $this->pdo->query("SELECT id FROM restricoes_alimentares LIMIT 1");
        $restrId = (int)$stmt->fetchColumn();
        $stmt = $this->pdo->prepare("INSERT INTO aluno_restricoes (aluno_id, restricao_id) VALUES (?, ?)");
        $stmt->execute([$alunoId, $restrId]);

        // 5.4 Testa ON DELETE RESTRICT: tentar excluir o responsável tendo aluno associado
        $bloqueouExclusao = false;
        try {
            $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$respId}");
        } catch (\PDOException $e) {
            $bloqueouExclusao = true;
        }
        $this->assert($bloqueouExclusao, "Exclusão de Responsável com Aluno vinculado bloqueada por ON DELETE RESTRICT");

        // 5.5 Testa ON DELETE CASCADE: ao excluir o aluno, o vínculo em aluno_restricoes deve ser removido
        $this->pdo->exec("DELETE FROM alunos WHERE id = {$alunoId}");
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM aluno_restricoes WHERE aluno_id = ?");
        $stmt->execute([$alunoId]);
        $vinculoRestante = (int)$stmt->fetchColumn();
        $this->assert($vinculoRestante === 0, "Vínculo em aluno_restricoes removido automaticamente via ON DELETE CASCADE");

        // Limpeza dos dados de teste
        $this->pdo->exec("DELETE FROM responsaveis WHERE id = {$respId}");
        $this->pdo->exec("DELETE FROM turmas WHERE id = {$turmaId}");
    }

    private function testModelIntegracaoESoftDelete(): void
    {
        echo "\n6. Testando Integração com App\\Core\\Model e Soft Delete:\n";

        // Modelo anônimo para testar operações CRUD via App\Core\Model
        $categoriaModel = new class extends Model {
            protected string $table = 'categorias';
            protected bool $softDelete = true;
        };

        // 6.1 Create
        $catNome = 'Categoria Teste Model ' . time();
        $novoId = $categoriaModel->create([
            'nome'           => $catNome,
            'ordem_exibicao' => 99,
            'ativo'          => 1
        ]);
        $this->assert($novoId > 0, "Model::create() inseriu registro e retornou ID gerado ({$novoId})");

        // 6.2 Find
        $registro = $categoriaModel->find($novoId);
        $this->assert($registro !== null && $registro['nome'] === $catNome, "Model::find() localizou o registro inserido");

        // 6.3 Update
        $nomeAtualizado = $catNome . ' (Atualizada)';
        $afetados = $categoriaModel->update($novoId, ['nome' => $nomeAtualizado]);
        $this->assert($afetados === 1, "Model::update() atualizou 1 linha com sucesso");

        $registroAtualizado = $categoriaModel->find($novoId);
        $this->assert($registroAtualizado['nome'] === $nomeAtualizado, "Model refletiu a alteração do registro");

        // 6.4 Soft Delete
        $categoriaModel->delete($novoId);
        $buscaAtiva = $categoriaModel->find($novoId);
        $this->assert($buscaAtiva === null, "Model::find() omitiu o registro após soft delete (WHERE deleted_at IS NULL)");

        // Consulta direta no banco para confirmar que o registro ainda existe com deleted_at preenchido
        $stmt = $this->pdo->prepare("SELECT deleted_at FROM categorias WHERE id = ?");
        $stmt->execute([$novoId]);
        $deletedAt = $stmt->fetchColumn();
        $this->assert(!empty($deletedAt), "Registro físico preservado no banco com timestamp preenchido em deleted_at ({$deletedAt})");

        // Limpeza do registro de teste
        $this->pdo->exec("DELETE FROM categorias WHERE id = {$novoId}");
    }
}

// Executa a bateria de testes
$suite = new Fase3Test();
$suite->run();
