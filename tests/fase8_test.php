<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Suíte de Testes da Fase 8: Importação Escolar em Lote e Gestão Ativa de Restrições Alimentares
 * 
 * Execução via Docker: docker compose exec app php tests/fase8_test.php
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/database.php';

// Autoloader manual para testes
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

class Fase8Test
{
    private int $totalAssertions = 0;
    private int $passedAssertions = 0;

    private function assert(bool $condition, string $description): void
    {
        $this->totalAssertions++;
        if ($condition) {
            $this->passedAssertions++;
            echo "  [PASS] {$description}\n";
        } else {
            echo "  [FAIL] {$description}\n";
        }
    }

    public function run(): void
    {
        echo "====================================================================\n";
        echo " INICIANDO TESTES DA FASE 8 — IMPORTAÇÃO ESCOLAR E ALÉRGENOS\n";
        echo "====================================================================\n\n";

        $this->testarUtilitariosSpreadsheet();
        $this->testarAnalisePreviaSpreadsheet();
        $this->testarProcessamentoImportacaoAtomica();
        $this->testarPreservacaoSaldoESenhaResponsavel();
        $this->testarModeloAluno();
        $this->testarVinculacaoAlergenosAluno();
        $this->testarSegurancaTitularidadePortal();

        echo "\n--------------------------------------------------------------------\n";
        echo " RESULTADO DOS TESTES DA FASE 8:\n";
        echo " Total de asserções: {$this->totalAssertions}\n";
        echo " Aprovadas:          {$this->passedAssertions}\n";
        echo " Reprovadas:         " . ($this->totalAssertions - $this->passedAssertions) . "\n";
        echo " Taxa de sucesso:    " . number_format(($this->passedAssertions / $this->totalAssertions) * 100, 1) . "%\n";
        echo "--------------------------------------------------------------------\n";

        if ($this->passedAssertions !== $this->totalAssertions) {
            exit(1);
        }
    }

    private function testarUtilitariosSpreadsheet(): void
    {
        echo "1. Testando Utilitários do SpreadsheetService:\n";

        // Remoção de BOM
        $comBom = "\xEF\xBB\xBFnome_responsavel;cpf";
        $semBom = \App\Services\SpreadsheetService::removerBom($comBom);
        $this->assert($semBom === "nome_responsavel;cpf", "BOM UTF-8 é removido corretamente da string");

        // Normalização de cabeçalho
        $norm = \App\Services\SpreadsheetService::normalizarCabecalho(" Nome do Responsável ");
        $this->assert($norm === "nome_do_responsavel", "Normalização de cabeçalho trata espaços e acentuação");

        // Detecção de delimitador
        $del1 = \App\Services\SpreadsheetService::detectarDelimitador("nome;cpf;matricula;turma");
        $this->assert($del1 === ";", "Detecta delimitador ponto-e-vírgula (;)");

        $del2 = \App\Services\SpreadsheetService::detectarDelimitador("nome,cpf,matricula,turma");
        $this->assert($del2 === ",", "Detecta delimitador vírgula (,)");

        // Mapeamento de colunas
        $cabecalhos = ['Nome do Responsável', 'CPF', 'Telefone WhatsApp', 'E-mail', 'Matrícula Aluno', 'Aluno', 'Série', 'Turma', 'Turno', 'Alergias'];
        $mapa = \App\Services\SpreadsheetService::identificarMapaColunas($cabecalhos);

        $this->assert($mapa['nome_responsavel'] === 0, "Identificou coluna nome_responsavel no índice 0");
        $this->assert($mapa['cpf_responsavel'] === 1, "Identificou coluna cpf_responsavel no índice 1");
        $this->assert($mapa['whatsapp'] === 2, "Identificou coluna whatsapp no índice 2");
        $this->assert($mapa['matricula'] === 4, "Identificou coluna matricula no índice 4");
        $this->assert($mapa['nome_aluno'] === 5, "Identificou coluna nome_aluno no índice 5");
        $this->assert($mapa['turma'] === 7, "Identificou coluna turma no índice 7");
        $this->assert($mapa['alergias'] === 9, "Identificou coluna alergias no índice 9");

        $faltantes = \App\Services\SpreadsheetService::validarColunasObrigatorias($mapa);
        $this->assert(empty($faltantes), "Nenhuma coluna obrigatória está faltante no mapa completo");

        // Modelo CSV
        $modelo = \App\Services\SpreadsheetService::gerarModeloCsv();
        $this->assert(str_starts_with($modelo, "\xEF\xBB\xBF"), "Modelo CSV possui BOM UTF-8 para compatibilidade com Excel");
        $this->assert(str_contains($modelo, "nome_responsavel;cpf_responsavel"), "Modelo CSV contém os cabeçalhos padrão");
        $this->assert(str_contains($modelo, "Lucas Silva"), "Modelo CSV contém registros de exemplo brasileiros");
    }

    private function testarAnalisePreviaSpreadsheet(): void
    {
        echo "\n2. Testando Análise e Conferência Prévia (Dry-Run):\n";

        $tempDir = dirname(__DIR__) . '/storage/uploads/private';
        if (!is_dir($tempDir)) mkdir($tempDir, 0750, true);

        // Cria arquivo CSV temporário de teste
        $testeCsv = $tempDir . '/teste_previa_' . uniqid() . '.csv';
        $conteudo = "nome_responsavel;cpf_responsavel;whatsapp;matricula;nome_aluno;serie;turma;turno;alergias\r\n";
        $conteudo .= "Responsável Válido 1;11122233344;(11) 98888-1111;MAT-TEST-001;Aluno Válido 1;6º Ano;6º Ano A;matutino;Lactose\r\n";
        $conteudo .= "Responsável Inválido 2;123;(11) 98888-2222;MAT-TEST-002;Aluno Inválido 2;6º Ano;6º Ano A;matutino;\r\n"; // CPF inválido
        $conteudo .= "Responsável Válido 3;33344455566;(11) 98888-3333;MAT-TEST-001;Aluno Duplicado;6º Ano;6º Ano A;matutino;\r\n"; // Matrícula duplicada no arquivo

        file_put_contents($testeCsv, $conteudo);

        $analise = \App\Services\SpreadsheetService::analisarArquivo($testeCsv);

        $this->assert($analise['valido'] === true, "Análise do arquivo retornou status válido estruturalmente");
        $this->assert($analise['total_linhas'] === 3, "Identificou 3 linhas de dados na planilha");
        $this->assert($analise['linhas_validas'] === 1, "Apenas 1 linha é 100% válida");
        $this->assert($analise['linhas_com_erro'] === 2, "Identificou 2 linhas com erros (CPF inválido e matrícula repetida)");

        // Verifica os erros identificados
        $errosLinha2 = $analise['amostra_linhas'][1]['erros'];
        $this->assert(str_contains(implode(' ', $errosLinha2), 'CPF inválido'), "Diagnosticou corretamente CPF inválido na linha 2");

        $errosLinha3 = $analise['amostra_linhas'][2]['erros'];
        $this->assert(str_contains(implode(' ', $errosLinha3), 'Matrícula repetida'), "Diagnosticou corretamente matrícula repetida na linha 3");

        @unlink($testeCsv);
    }

    private function testarProcessamentoImportacaoAtomica(): void
    {
        echo "\n3. Testando Processamento Atômico da Importação:\n";

        $tempDir = dirname(__DIR__) . '/storage/uploads/private';
        $testeCsv = $tempDir . '/teste_import_' . uniqid() . '.csv';

        $sufixo = random_int(1000, 9999);
        $cpfResp1 = "888" . sprintf('%06d', random_int(1, 999999)) . "11";
        $cpfResp2 = "777" . sprintf('%06d', random_int(1, 999999)) . "22";
        $matAluno1 = "MAT-IMP-" . $sufixo . "-A";
        $matAluno2 = "MAT-IMP-" . $sufixo . "-B";
        $matAluno3 = "MAT-IMP-" . $sufixo . "-C";

        $conteudo = "nome_responsavel;cpf_responsavel;whatsapp;matricula;nome_aluno;serie;turma;turno;alergias\r\n";
        // Aluno 1 e 2 são irmãos (mesmo CPF de responsável)
        $conteudo .= "Pais dos Irmãos {$sufixo};{$cpfResp1};(11) 98888-0001;{$matAluno1};Irmão Mais Velho {$sufixo};8º Ano;8º Ano B {$sufixo};vespertino;Glúten\r\n";
        $conteudo .= "Pais dos Irmãos {$sufixo};{$cpfResp1};(11) 98888-0001;{$matAluno2};Irmão Mais Novo {$sufixo};6º Ano;6º Ano A {$sufixo};matutino;Lactose\r\n";
        // Aluno 3 com outro responsável
        $conteudo .= "Responsável Outro {$sufixo};{$cpfResp2};(11) 97777-0002;{$matAluno3};Filho Único {$sufixo};9º Ano;9º Ano A {$sufixo};matutino;\r\n";

        file_put_contents($testeCsv, $conteudo);

        $resultado = \App\Services\SpreadsheetService::processarImportacao($testeCsv, 1);

        $this->assert($resultado['sucesso'] === true, "Importação executada com sucesso");
        $this->assert($resultado['linhas_processadas'] === 3, "3 linhas foram processadas");
        $this->assert($resultado['novos_responsaveis'] === 2, "2 novos responsáveis foram criados para os 3 alunos");
        $this->assert($resultado['novos_alunos'] === 3, "3 novos alunos foram cadastrados");
        $this->assert($resultado['novas_turmas'] >= 2, "Novas turmas foram criadas automaticamente");

        // Verifica se arquivo definitivo foi gerado em storage/uploads/private
        $this->assert(file_exists(dirname(__DIR__) . '/' . $resultado['arquivo_salvo']), "Cópia protegida da planilha gravada em storage/uploads/private/");

        // Verifica integridade dos alunos no banco
        $aluno1 = \App\Models\Aluno::buscarPorMatricula($matAluno1);
        $aluno2 = \App\Models\Aluno::buscarPorMatricula($matAluno2);

        $this->assert($aluno1 !== null && $aluno2 !== null, "Ambos os alunos foram persistidos no MySQL");
        $this->assert((int)$aluno1['responsavel_id'] === (int)$aluno2['responsavel_id'], "Irmãos foram vinculados ao mesmo responsável_id (carteira compartilhada)");

        // Verifica alérgenos associados
        $alergenosAluno1 = \App\Models\Aluno::obterIdsAlergenos((int)$aluno1['id']);
        $this->assert(!empty($alergenosAluno1), "Aluno 1 teve alérgeno vinculado a partir da planilha");

        @unlink($testeCsv);
        if (file_exists(dirname(__DIR__) . '/' . $resultado['arquivo_salvo'])) {
            @unlink(dirname(__DIR__) . '/' . $resultado['arquivo_salvo']);
        }
    }

    private function testarPreservacaoSaldoESenhaResponsavel(): void
    {
        echo "\n4. Testando Preservação Absoluta de Saldo e Senha na Reimportação:\n";

        $tempDir = dirname(__DIR__) . '/storage/uploads/private';
        $testeCsv = $tempDir . '/teste_reimport_' . uniqid() . '.csv';

        $sufixo = random_int(1000, 9999);
        $cpfResp = "666" . sprintf('%06d', random_int(1, 999999)) . "33";
        $matAluno = "MAT-PRESERV-" . $sufixo;

        // 1. Cria manualmente o responsável com saldo R$ 75,50 e senha criptografada
        $senhaHash = password_hash('SenhaSegura123', PASSWORD_BCRYPT);
        \App\Core\Model::execute("INSERT INTO responsaveis (
            cpf, nome, whatsapp, email, senha_hash, saldo_atual, termos_aceitos_em, created_at, updated_at
        ) VALUES (
            :cpf, :nome, :whatsapp, :email, :senha_hash, :saldo_atual, :termos_aceitos_em, NOW(), NOW()
        )", [
            'cpf'               => $cpfResp,
            'nome'              => "Titular com Saldo {$sufixo}",
            'whatsapp'          => "(11) 99999-8888",
            'email'             => "titular{$sufixo}@email.com",
            'senha_hash'        => $senhaHash,
            'saldo_atual'       => 75.50,
            'termos_aceitos_em' => date('Y-m-d H:i:s')
        ]);

        $respId = (int)\App\Core\Model::lastInsertId();

        // 2. Monta planilha onde esse responsável aparece trazendo novo aluno
        $conteudo = "nome_responsavel;cpf_responsavel;whatsapp;matricula;nome_aluno;serie;turma;turno;alergias\r\n";
        $conteudo .= "Titular Nome Atualizado {$sufixo};{$cpfResp};(11) 99999-8888;{$matAluno};Novo Filho {$sufixo};1º Ano;1º Ano A {$sufixo};matutino;\r\n";

        file_put_contents($testeCsv, $conteudo);

        // Processa importação
        $res = \App\Services\SpreadsheetService::processarImportacao($testeCsv, 1);

        $this->assert($res['novos_responsaveis'] === 0, "Responsável não foi recriado (novos_responsaveis = 0)");
        $this->assert($res['resp_atualizados'] === 1, "Responsável foi contabilizado como atualizado");

        // 3. Verifica o responsável no banco: saldo e senha devem ser rigorosamente idênticos
        $respVerificado = \App\Core\Model::fetchOne("SELECT * FROM responsaveis WHERE id = :id", ['id' => $respId]);

        $this->assert((float)$respVerificado['saldo_atual'] === 75.50, "Saldo financeiro permaneceu intacto em R$ 75,50");
        $this->assert($respVerificado['senha_hash'] === $senhaHash, "Hash da senha permaneceu intacto");
        $this->assert(!empty($respVerificado['termos_aceitos_em']), "Timestamp de aceite dos termos preservado");
        $this->assert($respVerificado['nome'] === "Titular Nome Atualizado {$sufixo}", "Nome foi atualizado conforme os dados novos");

        @unlink($testeCsv);
        if (file_exists(dirname(__DIR__) . '/' . $res['arquivo_salvo'])) {
            @unlink(dirname(__DIR__) . '/' . $res['arquivo_salvo']);
        }
    }

    private function testarModeloAluno(): void
    {
        echo "\n5. Testando Operações e Ciclo de Vida do Modelo Aluno (Aluno.php):\n";

        $sufixo = random_int(10000, 99999);
        $matricula = "MAT-MOD-" . $sufixo;

        // Pega uma turma e um responsável qualquer para o teste
        $turma = \App\Models\Turma::listarTodas()[0];
        $resp  = \App\Models\Responsavel::fetchOne("SELECT id FROM responsaveis LIMIT 1");

        // 1. Criar aluno
        $alunoId = \App\Models\Aluno::criar([
            'matricula'      => $matricula,
            'nome'           => "Aluno Modelo {$sufixo}",
            'turma_id'       => $turma['id'],
            'responsavel_id' => $resp['id'],
            'turno'          => 'matutino'
        ], 1);

        $this->assert($alunoId > 0, "Aluno criado com sucesso via Aluno::criar (ID: {$alunoId})");

        // 2. Buscar por ID e por Matrícula
        $alunoPorId = \App\Models\Aluno::buscarPorId($alunoId);
        $this->assert($alunoPorId !== null && $alunoPorId['matricula'] === $matricula, "Aluno localizado por ID com sucesso");

        $alunoPorMat = \App\Models\Aluno::buscarPorMatricula($matricula);
        $this->assert($alunoPorMat !== null && (int)$alunoPorMat['id'] === $alunoId, "Aluno localizado por matrícula com sucesso");

        // 3. Atualizar aluno
        $atualizado = \App\Models\Aluno::atualizar($alunoId, [
            'matricula'      => $matricula,
            'nome'           => "Aluno Modelo Nome Editado {$sufixo}",
            'turma_id'       => $turma['id'],
            'responsavel_id' => $resp['id'],
            'turno'          => 'vespertino'
        ], 1);

        $this->assert($atualizado === true, "Aluno::atualizar executado com sucesso");
        $alunoEditado = \App\Models\Aluno::buscarPorId($alunoId);
        $this->assert($alunoEditado['turno'] === 'vespertino', "Turno atualizado para vespertino");

        // 4. Inativar aluno (soft delete)
        $inativado = \App\Models\Aluno::inativar($alunoId, 1);
        $this->assert($inativado === true, "Aluno::inativar executado com sucesso");

        $alunoInativo = \App\Models\Aluno::buscarPorId($alunoId, true);
        $this->assert((int)$alunoInativo['ativo'] === 0 && !empty($alunoInativo['deleted_at']), "Aluno possui ativo = 0 e deleted_at preenchido");

        // Aluno inativo não deve ser retornado em busca que filtra apenas ativos
        $alunoNaoAtivo = \App\Models\Aluno::buscarPorId($alunoId, false);
        $this->assert($alunoNaoAtivo === null, "Busca com apenas ativos omite aluno inativado");

        // 5. Reativar aluno
        $reativado = \App\Models\Aluno::reativar($alunoId, 1);
        $this->assert($reativado === true, "Aluno::reativar executado com sucesso");

        $alunoReativo = \App\Models\Aluno::buscarPorId($alunoId, false);
        $this->assert($alunoReativo !== null && (int)$alunoReativo['ativo'] === 1, "Aluno reativado retornado com sucesso em buscas ativas");

        // 6. Teste de listagem filtrada
        $lista = \App\Models\Aluno::listarTodos(['busca' => $matricula]);
        $this->assert(count($lista) === 1, "Aluno::listarTodos localizou o aluno pelo filtro de busca textual");
    }

    private function testarVinculacaoAlergenosAluno(): void
    {
        echo "\n6. Testando Gestão de Restrições Alimentares (Aluno e RestricaoAlimentar):\n";

        $aluno = \App\Models\Aluno::fetchOne("SELECT id FROM alunos WHERE deleted_at IS NULL LIMIT 1");
        $alunoId = (int)$aluno['id'];

        $catalogo = \App\Models\RestricaoAlimentar::listarTodas();
        $this->assert(count($catalogo) >= 8, "Catálogo oficial contém pelo menos 8 alérgenos padronizados");

        $alergenoGluten  = (int)$catalogo[0]['id'];
        $alergenoLactose = (int)$catalogo[1]['id'];

        // Sincroniza alérgenos: Glúten e Lactose
        \App\Models\RestricaoAlimentar::sincronizarAluno($alunoId, [$alergenoGluten, $alergenoLactose]);

        $idsAluno = \App\Models\Aluno::obterIdsAlergenos($alunoId);
        $this->assert(count($idsAluno) === 2, "Aluno possui 2 alérgenos vinculados em aluno_restricoes");
        $this->assert(in_array($alergenoGluten, $idsAluno, true), "Aluno possui vínculo com Glúten");
        $this->assert(in_array($alergenoLactose, $idsAluno, true), "Aluno possui vínculo com Lactose");

        $temGluten = \App\Models\RestricaoAlimentar::alunoTemRestricao($alunoId, $alergenoGluten);
        $this->assert($temGluten === true, "RestricaoAlimentar::alunoTemRestricao confirma restrição de Glúten");

        // Atualiza sincronizando apenas Lactose
        \App\Models\RestricaoAlimentar::sincronizarAluno($alunoId, [$alergenoLactose]);
        $idsAtualizados = \App\Models\Aluno::obterIdsAlergenos($alunoId);
        $this->assert(count($idsAtualizados) === 1 && in_array($alergenoLactose, $idsAtualizados, true), "Sincronização atômica removeu Glúten e manteve apenas Lactose");

        // Limpa todas as restrições
        \App\Models\RestricaoAlimentar::sincronizarAluno($alunoId, []);
        $idsVazios = \App\Models\Aluno::obterIdsAlergenos($alunoId);
        $this->assert(empty($idsVazios), "Sincronização com array vazio limpou todas as restrições do aluno");
    }

    private function testarSegurancaTitularidadePortal(): void
    {
        echo "\n7. Testando Segurança e Titularidade Familiar (LGPD):\n";

        // Cria dois responsáveis distintos
        $sufixo = random_int(1000, 9999);
        $cpf1 = "121" . sprintf('%06d', random_int(1, 999999)) . "01";
        $cpf2 = "122" . sprintf('%06d', random_int(1, 999999)) . "02";
        $resp1Id = \App\Models\Responsavel::localizarOuCriarPorCpf([
            'cpf' => $cpf1,
            'nome' => "Família Alfa {$sufixo}",
            'whatsapp' => "(11) 91111-0000"
        ])['id'];

        $resp2Id = \App\Models\Responsavel::localizarOuCriarPorCpf([
            'cpf' => $cpf2,
            'nome' => "Família Beta {$sufixo}",
            'whatsapp' => "(11) 92222-0000"
        ])['id'];

        $turma = \App\Models\Turma::listarTodas()[0];

        // Cria filho da Família Alfa
        $filhoAlfaId = \App\Models\Aluno::criar([
            'matricula' => "MAT-ALFA-" . $sufixo,
            'nome' => "Filho Alfa {$sufixo}",
            'turma_id' => $turma['id'],
            'responsavel_id' => $resp1Id,
            'turno' => 'matutino'
        ]);

        // Simula verificação de titularidade do PortalController
        $aluno = \App\Models\Aluno::buscarPorId($filhoAlfaId);

        $podeFamAlfa = ((int)$aluno['responsavel_id'] === $resp1Id);
        $podeFamBeta = ((int)$aluno['responsavel_id'] === $resp2Id);

        $this->assert($podeFamAlfa === true, "Família Alfa tem permissão para gerenciar restrições de seu filho");
        $this->assert($podeFamBeta === false, "Família Beta é categoricamente BLOQUEADA de alterar dados do filho da Família Alfa");

        // Lista de dependentes no Portal da Família Beta não deve incluir Filho Alfa
        $dependentesBeta = \App\Models\Aluno::listarPorResponsavel($resp2Id);
        $idsBeta = array_map(fn($d) => (int)$d['id'], $dependentesBeta);

        $this->assert(!in_array($filhoAlfaId, $idsBeta, true), "Filho da Família Alfa não aparece na lista de dependentes da Família Beta");
    }
}

// Execução da suíte
$test = new Fase8Test();
$test->run();
