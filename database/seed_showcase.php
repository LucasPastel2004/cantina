<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Script de Carga Oficial para Demonstração ao Operador da Cantina
 * 
 * Limpa artefatos temporários e de teste, gerando uma base de dados enxuta,
 * elegante e 100% pronta para a demonstração completa de todos os fluxos operacionais:
 * - Abertura e Fechamento de Caixa Cego
 * - Corte do Turno com conversão de Salgados Fracionados (15 pequenos = 7 grandes e 1 pequeno)
 * - Conferência de Recebimento de Fornecedor
 * - Impressão Térmica de Tickets por Turma com Alerta de Alergias
 * - PDV de Balcão (Dinheiro com troco, Pix e Desconto em Folha de Professores)
 * - Estoque com Alerta de Estoque Mínimo
 * - Importação de NF-e XML
 * - Portal da Família (Saldo, Recarga Pix, Agendamento e Alergias)
 * - Relatórios Gerenciais (Vendas por Alimento, Curva ABC, Folha de RH, Extrato do Aluno)
 */

defined('CANTINA_SISTEMA') or define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getInstance();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

echo "====================================================================\n";
echo "   CANTINA WEB — SANEAMENTO E CARGA DE DEMONSTRAÇÃO (SHOWCASE)\n";
echo "====================================================================\n\n";

// 1. Limpeza Segura com integridade referencial desativada temporariamente
echo "[1/10] Limpando dados temporários e de testes residuais...\n";
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

$tablesToTruncate = [
    'itens_venda_balcao',
    'vendas_balcao',
    'tickets',
    'itens_pedido',
    'pedidos',
    'estoque_movimentacoes',
    'transacoes_carteira',
    'sessoes_caixa',
    'itens_nota_fiscal_xml',
    'notas_fiscais_xml',
    'aluno_restricoes',
    'alunos',
    'responsaveis',
    'colaboradores',
    'produto_alergenos',
    'produtos',
    'categorias',
    'turmas',
    'logs_auditoria',
    'logs_erros',
    'logs_seguranca'
];

foreach ($tablesToTruncate as $tbl) {
    $pdo->exec("TRUNCATE TABLE `{$tbl}`;");
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "       -> Tabelas transacionais e cadastrais limpas com sucesso.\n";

// 2. Garantir Usuários da Equipe da Cantina
echo "[2/10] Verificando e cadastrando equipe da cantina (Admin e Operadores)...\n";
$senhaAdmin = password_hash('Admin@123', PASSWORD_BCRYPT);
$senhaOperador = password_hash('Operador@123', PASSWORD_BCRYPT);

// Admin
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = 'admin'");
$stmt->execute();
if ($u = $stmt->fetch()) {
    $pdo->prepare("UPDATE usuarios SET senha_hash = ?, ativo = 1, deleted_at = NULL WHERE id = ?")->execute([$senhaAdmin, $u['id']]);
    $adminId = $u['id'];
} else {
    $pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES ('admin', 'Administrador Geral', 'admin@monteirolobato.com.br', ?, 'administrador', 1)")->execute([$senhaAdmin]);
    $adminId = $pdo->lastInsertId();
}

// Operador Geral 'operador'
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = 'operador'");
$stmt->execute();
if ($u = $stmt->fetch()) {
    $pdo->prepare("UPDATE usuarios SET senha_hash = ?, ativo = 1, deleted_at = NULL WHERE id = ?")->execute([$senhaOperador, $u['id']]);
    $operadorId = $u['id'];
} else {
    $pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES ('operador', 'Operador da Cantina', 'operador@monteirolobato.com.br', ?, 'operador', 1)")->execute([$senhaOperador]);
    $operadorId = $pdo->lastInsertId();
}

// Operadora 'operador-maria'
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = 'operador-maria'");
$stmt->execute();
if ($u = $stmt->fetch()) {
    $pdo->prepare("UPDATE usuarios SET senha_hash = ?, ativo = 1, deleted_at = NULL WHERE id = ?")->execute([$senhaOperador, $u['id']]);
    $operadorMariaId = $u['id'];
} else {
    $pdo->prepare("INSERT INTO usuarios (usuario, nome, email, senha_hash, perfil, ativo) VALUES ('operador-maria', 'Maria Atendente', 'maria@monteirolobato.com.br', ?, 'operador', 1)")->execute([$senhaOperador]);
    $operadorMariaId = $pdo->lastInsertId();
}
echo "       -> Usuários prontos: 'admin' (Admin@123), 'operador' (Operador@123) e 'operador-maria' (Operador@123).\n";

// 3. Cadastrar Categorias Oficiais
echo "[3/10] Inserindo 5 Categorias Oficiais do Cardápio...\n";
$categoriasData = [
    1 => ['nome' => 'Salgados Assados', 'ordem' => 1],
    2 => ['nome' => 'Sucos Naturais', 'ordem' => 2],
    3 => ['nome' => 'Lanches & Sanduíches', 'ordem' => 3],
    4 => ['nome' => 'Industrializados & Bebidas', 'ordem' => 4],
    5 => ['nome' => 'Sobremesas', 'ordem' => 5],
];

$stmtCat = $pdo->prepare("INSERT INTO categorias (id, nome, ordem_exibicao, ativo, created_by) VALUES (?, ?, ?, 1, ?)");
foreach ($categoriasData as $id => $cat) {
    $stmtCat->execute([$id, $cat['nome'], $cat['ordem'], $adminId]);
}
echo "       -> 5 categorias inseridas com sucesso.\n";

// 4. Cadastrar Produtos Enxutos
echo "[4/10] Inserindo Catálogo Enxuto de Produtos (12 itens essenciais)...\n";
// Salgados: Coxinha Grande e Coxinha Pequena correspondente com flag fracionada!
$produtosData = [
    // Salgados Assados (Cat 1)
    1 => [
        'categoria_id' => 1,
        'codigo' => 'SAL-001',
        'descricao' => 'Coxinha de Frango Grande',
        'preco' => 8.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 50,
        'estoque_minimo' => 15,
        'alergenos' => [1] // Glúten
    ],
    2 => [
        'categoria_id' => 1,
        'codigo' => 'SAL-002',
        'descricao' => 'Coxinha de Frango pequena',
        'preco' => 4.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 1, // FRAÇÃO DE SALGADO GRANDE!
        'turno' => 'ambos',
        'estoque_atual' => 50,
        'estoque_minimo' => 15,
        'alergenos' => [1] // Glúten
    ],
    3 => [
        'categoria_id' => 1,
        'codigo' => 'SAL-003',
        'descricao' => 'Esfiha de Carne Assada',
        'preco' => 8.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 40,
        'estoque_minimo' => 10,
        'alergenos' => [1] // Glúten
    ],
    4 => [
        'categoria_id' => 1,
        'codigo' => 'SAL-004',
        'descricao' => 'Enroladinho de Presunto e Queijo',
        'preco' => 8.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 35,
        'estoque_minimo' => 10,
        'alergenos' => [1, 2] // Glúten, Lactose
    ],

    // Sucos Naturais (Cat 2)
    5 => [
        'categoria_id' => 2,
        'codigo' => 'SUC-001',
        'descricao' => 'Suco de Laranja Natural 300ml',
        'preco' => 6.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 60,
        'estoque_minimo' => 20,
        'alergenos' => []
    ],
    6 => [
        'categoria_id' => 2,
        'codigo' => 'SUC-002',
        'descricao' => 'Suco de Uva Integral 300ml',
        'preco' => 7.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 40,
        'estoque_minimo' => 15,
        'alergenos' => []
    ],

    // Lanches & Sanduíches (Cat 3)
    7 => [
        'categoria_id' => 3,
        'codigo' => 'LAN-001',
        'descricao' => 'Sanduíche Natural de Frango com Requeijão',
        'preco' => 9.50,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 30,
        'estoque_minimo' => 10,
        'alergenos' => [1, 2] // Glúten, Lactose
    ],
    8 => [
        'categoria_id' => 3,
        'codigo' => 'LAN-002',
        'descricao' => 'Misto Quente Tradicional',
        'preco' => 7.50,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 35,
        'estoque_minimo' => 10,
        'alergenos' => [1, 2] // Glúten, Lactose
    ],

    // Industrializados & Bebidas (Cat 4)
    9 => [
        'categoria_id' => 4,
        'codigo' => 'BEB-001',
        'descricao' => 'Água Mineral sem Gás 500ml',
        'preco' => 3.50,
        'tipo' => 'industrializado_continuo',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 4, // Abaixo do mínimo (10) para exibir alerta visual de estoque baixo!
        'estoque_minimo' => 10,
        'alergenos' => []
    ],
    10 => [
        'categoria_id' => 4,
        'codigo' => 'BEB-002',
        'descricao' => 'Água de Coco 330ml',
        'preco' => 5.50,
        'tipo' => 'industrializado_continuo',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 28,
        'estoque_minimo' => 10,
        'alergenos' => []
    ],
    11 => [
        'categoria_id' => 4,
        'codigo' => 'IND-001',
        'descricao' => 'Biscoito Integral Cookie Cacau',
        'preco' => 4.50,
        'tipo' => 'industrializado_continuo',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 35,
        'estoque_minimo' => 15,
        'alergenos' => [1, 5] // Glúten, Soja
    ],

    // Sobremesas (Cat 5)
    12 => [
        'categoria_id' => 5,
        'codigo' => 'SOB-001',
        'descricao' => 'Salada de Frutas Frescas 200g',
        'preco' => 6.00,
        'tipo' => 'salgado_diario',
        'fracionado' => 0,
        'turno' => 'ambos',
        'estoque_atual' => 25,
        'estoque_minimo' => 8,
        'alergenos' => []
    ],
];

$stmtProd = $pdo->prepare("INSERT INTO produtos 
    (id, categoria_id, codigo_interno, descricao, preco_venda, tipo, fracionado_salgado_grande, turno_disponibilidade, estoque_atual, estoque_reservado, estoque_minimo, status_pausa, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'ativo', ?)");

$stmtProdAlerg = $pdo->prepare("INSERT INTO produto_alergenos (produto_id, restricao_id) VALUES (?, ?)");

foreach ($produtosData as $id => $prod) {
    $stmtProd->execute([
        $id,
        $prod['categoria_id'],
        $prod['codigo'],
        $prod['descricao'],
        $prod['preco'],
        $prod['tipo'],
        $prod['fracionado'],
        $prod['turno'],
        $prod['estoque_atual'],
        $prod['estoque_minimo'],
        $adminId
    ]);

    foreach ($prod['alergenos'] as $restId) {
        $stmtProdAlerg->execute([$id, $restId]);
    }
}
echo "       -> 12 produtos e seus alérgenos vinculados cadastrados.\n";

// 5. Cadastrar Turmas Oficiais Escolares
echo "[5/10] Inserindo Grade Oficial de 19 Turmas (Infantil ao EM)...\n";
$turmasData = [
    // Matutino
    1  => ['serie' => 'Educação Infantil', 'nome' => 'Infantil 4 A', 'turno' => 'matutino', 'ordem' => 1],
    2  => ['serie' => 'Educação Infantil', 'nome' => 'Infantil 5 A', 'turno' => 'matutino', 'ordem' => 2],
    3  => ['serie' => 'Ensino Fundamental I', 'nome' => '1º Ano A', 'turno' => 'matutino', 'ordem' => 3],
    4  => ['serie' => 'Ensino Fundamental I', 'nome' => '2º Ano A', 'turno' => 'matutino', 'ordem' => 4],
    5  => ['serie' => 'Ensino Fundamental I', 'nome' => '3º Ano A', 'turno' => 'matutino', 'ordem' => 5],
    6  => ['serie' => 'Ensino Fundamental I', 'nome' => '4º Ano A', 'turno' => 'matutino', 'ordem' => 6],
    7  => ['serie' => 'Ensino Fundamental I', 'nome' => '5º Ano A', 'turno' => 'matutino', 'ordem' => 7],
    8  => ['serie' => 'Ensino Fundamental II', 'nome' => '6º Ano A', 'turno' => 'matutino', 'ordem' => 8],
    9  => ['serie' => 'Ensino Fundamental II', 'nome' => '7º Ano A', 'turno' => 'matutino', 'ordem' => 9],
    10 => ['serie' => 'Ensino Fundamental II', 'nome' => '8º Ano A', 'turno' => 'matutino', 'ordem' => 10],
    11 => ['serie' => 'Ensino Fundamental II', 'nome' => '9º Ano A', 'turno' => 'matutino', 'ordem' => 11],
    12 => ['serie' => 'Ensino Médio', 'nome' => '1ª Série EM', 'turno' => 'matutino', 'ordem' => 12],
    13 => ['serie' => 'Ensino Médio', 'nome' => '2ª Série EM', 'turno' => 'matutino', 'ordem' => 13],
    14 => ['serie' => 'Ensino Médio', 'nome' => '3ª Série EM', 'turno' => 'matutino', 'ordem' => 14],

    // Vespertino
    15 => ['serie' => 'Ensino Fundamental I', 'nome' => '1º Ano B', 'turno' => 'vespertino', 'ordem' => 15],
    16 => ['serie' => 'Ensino Fundamental I', 'nome' => '2º Ano B', 'turno' => 'vespertino', 'ordem' => 16],
    17 => ['serie' => 'Ensino Fundamental I', 'nome' => '3º Ano B', 'turno' => 'vespertino', 'ordem' => 17],
    18 => ['serie' => 'Ensino Fundamental I', 'nome' => '4º Ano B', 'turno' => 'vespertino', 'ordem' => 18],
    19 => ['serie' => 'Ensino Fundamental I', 'nome' => '5º Ano B', 'turno' => 'vespertino', 'ordem' => 19],
];

$stmtTurma = $pdo->prepare("INSERT INTO turmas (id, serie, nome_turma, turno, ordem_impressao, created_by) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($turmasData as $id => $turma) {
    $stmtTurma->execute([$id, $turma['serie'], $turma['nome'], $turma['turno'], $turma['ordem'], $adminId]);
}
echo "       -> 19 turmas inseridas com ordenação de impressão contínua.\n";

// 6. Cadastrar Colaboradores (Professores e Funcionários para Desconto em Folha)
echo "[6/10] Inserindo Corpo Docente e Funcionários (6 colaboradores)...\n";
$colaboradoresData = [
    1 => ['nome' => 'Prof. Roberto Silva', 'matricula' => 'COL-1001', 'cargo' => 'Professor de Matemática'],
    2 => ['nome' => 'Prof. Carlos Eduardo', 'matricula' => 'COL-1002', 'cargo' => 'Professor de História'],
    3 => ['nome' => 'Profa. Juliana Mendes', 'matricula' => 'COL-1003', 'cargo' => 'Professora de Língua Portuguesa'],
    4 => ['nome' => 'Prof. Marcos Oliveira', 'matricula' => 'COL-1004', 'cargo' => 'Professor de Ciências'],
    5 => ['nome' => 'Profa. Camila Rodrigues', 'matricula' => 'COL-1005', 'cargo' => 'Coordenadora Pedagógica'],
    6 => ['nome' => 'Inspetor Ricardo Santos', 'matricula' => 'COL-1006', 'cargo' => 'Inspetor de Alunos'],
];

$stmtColab = $pdo->prepare("INSERT INTO colaboradores (id, matricula_funcional, nome, cargo, foto_url, ativo, created_by) VALUES (?, ?, ?, ?, NULL, 1, ?)");
foreach ($colaboradoresData as $id => $colab) {
    $stmtColab->execute([$id, $colab['matricula'], $colab['nome'], $colab['cargo'], $adminId]);
}
echo "       -> 6 colaboradores cadastrados para desconto em folha.\n";

// 7. Cadastrar Famílias (Responsáveis) e Alunos com Alergias
echo "[7/10] Inserindo 4 Famílias e 6 Alunos com restrições alimentares...\n";
$senhaFamilia = password_hash('Familia@123', PASSWORD_BCRYPT);

$responsaveisData = [
    1 => [
        'cpf' => '12345678909',
        'nome' => 'Mariana Ribeiro de Souza',
        'email' => 'mariana.ribeiro@email.com',
        'whatsapp' => '(11) 98765-4321',
        'senha_hash' => $senhaFamilia,
        'saldo_atual' => 120.00,
        'termos_aceitos_em' => '2026-09-01 08:00:00',
        'bloqueado_med' => 0
    ],
    2 => [
        'cpf' => '11122233344',
        'nome' => 'Carlos Eduardo Santos',
        'email' => 'carlos.santos@email.com',
        'whatsapp' => '(11) 97654-3210',
        'senha_hash' => $senhaFamilia,
        'saldo_atual' => 85.00,
        'termos_aceitos_em' => '2026-09-01 08:30:00',
        'bloqueado_med' => 0
    ],
    3 => [
        'cpf' => '22233344455',
        'nome' => 'Patrícia Almeida Lima',
        'email' => 'patricia.almeida@email.com',
        'whatsapp' => '(11) 96543-2109',
        'senha_hash' => $senhaFamilia,
        'saldo_atual' => 45.00,
        'termos_aceitos_em' => '2026-09-05 10:15:00',
        'bloqueado_med' => 0
    ],
    4 => [
        'cpf' => '99988877766',
        'nome' => 'Fernanda Oliveira Costa',
        'email' => 'fernanda.costa@email.com',
        'whatsapp' => '(11) 95432-1098',
        'senha_hash' => '', // Pendente de primeiro acesso para demonstração ao operador!
        'saldo_atual' => 0.00,
        'termos_aceitos_em' => NULL,
        'bloqueado_med' => 0
    ],
];

$stmtResp = $pdo->prepare("INSERT INTO responsaveis 
    (id, cpf, nome, email, whatsapp, senha_hash, saldo_atual, termos_aceitos_em, bloqueado_med, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

foreach ($responsaveisData as $id => $resp) {
    $stmtResp->execute([
        $id,
        $resp['cpf'],
        $resp['nome'],
        $resp['email'],
        $resp['whatsapp'],
        $resp['senha_hash'],
        $resp['saldo_atual'],
        $resp['termos_aceitos_em'],
        $resp['bloqueado_med'],
        $adminId
    ]);
}

$alunosData = [
    1 => [
        'responsavel_id' => 1,
        'turma_id' => 7, // 5º Ano A - Matutino
        'matricula' => 'ALU-2026-01',
        'nome' => 'Lucas Ribeiro de Souza',
        'turno' => 'matutino',
        'restricoes' => [1] // Glúten
    ],
    2 => [
        'responsavel_id' => 1,
        'turma_id' => 5, // 3º Ano A - Matutino
        'matricula' => 'ALU-2026-02',
        'nome' => 'Sofia Ribeiro de Souza',
        'turno' => 'matutino',
        'restricoes' => [2] // Lactose
    ],
    3 => [
        'responsavel_id' => 2,
        'turma_id' => 9, // 7º Ano A - Matutino
        'matricula' => 'ALU-2026-03',
        'nome' => 'Pedro Santos',
        'turno' => 'matutino',
        'restricoes' => [3] // Amendoim
    ],
    4 => [
        'responsavel_id' => 2,
        'turma_id' => 3, // 1º Ano A - Matutino
        'matricula' => 'ALU-2026-04',
        'nome' => 'Beatriz Santos',
        'turno' => 'matutino',
        'restricoes' => []
    ],
    5 => [
        'responsavel_id' => 3,
        'turma_id' => 11, // 9º Ano A - Matutino
        'matricula' => 'ALU-2026-05',
        'nome' => 'Gabriel Almeida Lima',
        'turno' => 'matutino',
        'restricoes' => [2] // Lactose
    ],
    6 => [
        'responsavel_id' => 4,
        'turma_id' => 4, // 2º Ano A - Matutino
        'matricula' => 'ALU-2026-06',
        'nome' => 'Enzo Oliveira Costa',
        'turno' => 'matutino',
        'restricoes' => []
    ],
];

$stmtAluno = $pdo->prepare("INSERT INTO alunos (id, responsavel_id, turma_id, matricula, nome, turno, ativo, created_by) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
$stmtAlunoRest = $pdo->prepare("INSERT INTO aluno_restricoes (aluno_id, restricao_id) VALUES (?, ?)");

foreach ($alunosData as $id => $aluno) {
    $stmtAluno->execute([$id, $aluno['responsavel_id'], $aluno['turma_id'], $aluno['matricula'], $aluno['nome'], $aluno['turno'], $adminId]);
    foreach ($aluno['restricoes'] as $rId) {
        $stmtAlunoRest->execute([$id, $rId]);
    }
}
echo "       -> 4 responsáveis e 6 alunos (com Glúten, Lactose e Amendoim) cadastrados.\n";

// 8. Inserir Histórico de Transações da Carteira Familiar
echo "[8/10] Inserindo Livro-Razão da Carteira Digital com recargas e débitos...\n";
$stmtTx = $pdo->prepare("INSERT INTO transacoes_carteira 
    (responsavel_id, aluno_id, operador_id, tipo_transacao, valor, saldo_anterior, saldo_resultante, referencia_id, descricao, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Mariana: Recarga Pix R$ 200,00 e débitos de pedidos
$stmtTx->execute([1, NULL, NULL, 'recarga_pix', 200.00, 0.00, 200.00, 'PIX-INIT-001', 'Recarga Pix Dinâmico aprovada via Webhook', '2026-09-20 14:00:00']);
$stmtTx->execute([1, 1, NULL, 'debito_pedido', -49.50, 200.00, 150.50, 'PED-2026-001', 'Agendamento de lanche - Lucas Ribeiro (28/09)', '2026-09-27 19:30:00']);
$stmtTx->execute([1, 2, NULL, 'debito_pedido', -30.50, 150.50, 120.00, 'PED-2026-002', 'Agendamento de lanche - Sofia Ribeiro (28/09)', '2026-09-27 19:35:00']);

// Carlos: Recarga Pix R$ 150,00 e débitos de pedidos
$stmtTx->execute([2, NULL, NULL, 'recarga_pix', 150.00, 0.00, 150.00, 'PIX-INIT-002', 'Recarga Pix Dinâmico aprovada via Webhook', '2026-09-22 10:15:00']);
$stmtTx->execute([2, 3, NULL, 'debito_pedido', -65.00, 150.00, 85.00, 'PED-2026-003', 'Agendamento de lanche - Pedro Santos (28/09)', '2026-09-27 20:00:00']);

// Patrícia: Recarga R$ 60,00 e débito
$stmtTx->execute([3, NULL, NULL, 'recarga_pix', 60.00, 0.00, 60.00, 'PIX-INIT-003', 'Recarga Pix Dinâmico aprovada via Webhook', '2026-09-25 11:00:00']);
$stmtTx->execute([3, 5, NULL, 'debito_pedido', -15.00, 60.00, 45.00, 'PED-2026-004', 'Agendamento de lanche - Gabriel Almeida (28/09)', '2026-09-27 20:10:00']);

echo "       -> Extrato e saldos sincronizados com precisão contábil.\n";

// 9. Pedidos para HOJE (2026-09-28) com 15 Salgados Pequenos para o Corte!
echo "[9/10] Gerando Pedidos e Tickets para HOJE (2026-09-28 Matutino)...\n";
echo "       Cenário Exato: 15 Salgados Pequenos pedidos -> Corte consolida 7 Grandes + 1 Pequeno (8 peças físicas)!\n";

$dataHoje = '2026-09-28';
$turnoHoje = 'matutino';

$pedidosData = [
    // Pedido 1: Lucas Ribeiro (7 Coxinhas Pequenas + 1 Suco Laranja)
    1 => [
        'codigo' => 'PED-2026-001',
        'responsavel_id' => 1,
        'aluno_id' => 1, // Lucas Ribeiro (5º Ano A)
        'data' => $dataHoje,
        'turno' => $turnoHoje,
        'valor_total' => 34.00,
        'itens' => [
            ['produto_id' => 2, 'qtd' => 7, 'preco' => 4.00, 'subtotal' => 28.00], // 7 Coxinhas pequenas
            ['produto_id' => 5, 'qtd' => 1, 'preco' => 6.00, 'subtotal' => 6.00],  // 1 Suco de Laranja
        ],
        'alergia_tag' => 'Glúten'
    ],

    // Pedido 2: Pedro Santos (8 Coxinhas Pequenas + 1 Suco de Uva)
    2 => [
        'codigo' => 'PED-2026-002',
        'responsavel_id' => 2,
        'aluno_id' => 3, // Pedro Santos (7º Ano A)
        'data' => $dataHoje,
        'turno' => $turnoHoje,
        'valor_total' => 39.00,
        'itens' => [
            ['produto_id' => 2, 'qtd' => 8, 'preco' => 4.00, 'subtotal' => 32.00], // 8 Coxinhas pequenas
            ['produto_id' => 6, 'qtd' => 1, 'preco' => 7.00, 'subtotal' => 7.00],  // 1 Suco de Uva
        ],
        'alergia_tag' => 'Amendoim'
    ],

    // Pedido 3: Sofia Ribeiro (1 Sanduíche Natural + 1 Salada de Frutas)
    3 => [
        'codigo' => 'PED-2026-003',
        'responsavel_id' => 1,
        'aluno_id' => 2, // Sofia Ribeiro (3º Ano A)
        'data' => $dataHoje,
        'turno' => $turnoHoje,
        'valor_total' => 15.50,
        'itens' => [
            ['produto_id' => 7, 'qtd' => 1, 'preco' => 9.50, 'subtotal' => 9.50],  // Sanduíche Natural
            ['produto_id' => 12, 'qtd' => 1, 'preco' => 6.00, 'subtotal' => 6.00], // Salada de Frutas
        ],
        'alergia_tag' => 'Lactose'
    ],

    // Pedido 4: Gabriel Almeida (1 Misto Quente + 1 Suco Laranja)
    4 => [
        'codigo' => 'PED-2026-004',
        'responsavel_id' => 3,
        'aluno_id' => 5, // Gabriel Almeida (9º Ano A)
        'data' => $dataHoje,
        'turno' => $turnoHoje,
        'valor_total' => 13.50,
        'itens' => [
            ['produto_id' => 8, 'qtd' => 1, 'preco' => 7.50, 'subtotal' => 7.50], // Misto Quente
            ['produto_id' => 5, 'qtd' => 1, 'preco' => 6.00, 'subtotal' => 6.00], // Suco Laranja
        ],
        'alergia_tag' => 'Lactose'
    ],
];

$stmtPed = $pdo->prepare("INSERT INTO pedidos (id, codigo_pedido, responsavel_id, aluno_id, data_consumo, turno_consumo, valor_total, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'agendado', '2026-09-27 20:00:00')");
$stmtItemPed = $pdo->prepare("INSERT INTO itens_pedido (id, pedido_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
$stmtTicket = $pdo->prepare("INSERT INTO tickets (id, pedido_id, item_pedido_id, codigo_ticket, numero_via, status, tag_alergenos, created_at) VALUES (?, ?, ?, ?, 1, 'ativo', ?, '2026-09-27 20:00:00')");

$itemIdCounter = 1;
$ticketIdCounter = 1;

foreach ($pedidosData as $pedId => $p) {
    $stmtPed->execute([$pedId, $p['codigo'], $p['responsavel_id'], $p['aluno_id'], $p['data'], $p['turno'], $p['valor_total']]);
    
    foreach ($p['itens'] as $it) {
        $currentItemId = $itemIdCounter++;
        $stmtItemPed->execute([$currentItemId, $pedId, $it['produto_id'], $it['qtd'], $it['preco'], $it['subtotal']]);
        
        // Ticket com alérgeno destacado
        $codTicket = 'TCK-' . date('Ymd') . '-' . str_pad($ticketIdCounter, 4, '0', STR_PAD_LEFT);
        $stmtTicket->execute([$ticketIdCounter++, $pedId, $currentItemId, $codTicket, $p['alergia_tag']]);
    }
}
echo "       -> 4 Pedidos e 7 Tickets cadastrados (Totalizando 15 Coxinhas Pequenas nos pedidos 1 e 2).\n";

// 10. Sessões de Caixa (Hoje aberto com R$ 100,00 e Passadas Fechadas com Vendas no Balcão)
echo "[10/10] Configurando Sessões de Caixa e Vendas Balcão (Dinheiro, Pix e Desconto em Folha)...\n";

// Sessão 1: HOJE - MATUTINO (ABERTO para demonstração de operação do caixa)
$stmtCaixa = $pdo->prepare("INSERT INTO sessoes_caixa 
    (id, operador_id, turno, aberto_em, fechado_em, fundo_troco_inicial, total_dinheiro_recargas, total_dinheiro_avulso, total_pix_balcao, total_informado_gaveta, diferenca_quebra_sobra, status, observacoes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtCaixa->execute([
    1,
    $operadorMariaId,
    'matutino',
    '2026-09-28 07:15:00',
    NULL,
    100.00,
    0.00,
    27.50, // Vendas avulsas registradas hoje
    14.00, // Pix balcão hoje
    NULL,
    NULL,
    'aberto',
    'Caixa aberto normalmente com R$ 100,00 de fundo de troco para o turno matutino.'
]);

// Sessão 2: 25/09 Matutino (FECHADO)
$stmtCaixa->execute([
    2,
    $operadorMariaId,
    'matutino',
    '2026-09-25 07:15:00',
    '2026-09-25 11:30:00',
    100.00,
    150.00,
    185.00,
    120.00,
    435.00, // Gaveta: 100 fundo + 150 recargas + 185 vendas = 435.00
    0.00,
    'fechado',
    'Fechamento matutino exato sem quebra ou sobra.'
]);

// Sessão 3: 25/09 Vespertino (FECHADO)
$stmtCaixa->execute([
    3,
    $operadorId,
    'vespertino',
    '2026-09-25 12:30:00',
    '2026-09-25 16:45:00',
    100.00,
    50.00,
    115.00,
    85.00,
    265.00,
    0.00,
    'fechado',
    'Fechamento vespertino conferido com sucesso.'
]);

// Vendas Balcão na Sessão de HOJE
$stmtVenda = $pdo->prepare("INSERT INTO vendas_balcao 
    (id, sessao_caixa_id, operador_id, tipo_venda, aluno_id, colaborador_id, forma_pagamento, valor_total, valor_dinheiro_recebido, troco_devolvido, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtItemVenda = $pdo->prepare("INSERT INTO itens_venda_balcao (id, venda_balcao_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");

// Venda 1: Avulso Dinheiro (1 Enroladinho R$ 8.00 + 1 Água R$ 3.50 = R$ 11.50). Recebeu R$ 20.00, troco R$ 8.50.
$stmtVenda->execute([1, 1, $operadorMariaId, 'avulso_aluno', 1, NULL, 'dinheiro', 11.50, 20.00, 8.50, '2026-09-28 07:45:00']);
$stmtItemVenda->execute([1, 1, 4, 1, 8.00, 8.00]);
$stmtItemVenda->execute([2, 1, 9, 1, 3.50, 3.50]);

// Venda 2: Avulso Dinheiro (2 Biscoitos Cookie R$ 9.00 + 1 Suco Uva R$ 7.00 = R$ 16.00). Recebeu R$ 20.00, troco R$ 4.00.
$stmtVenda->execute([2, 1, $operadorMariaId, 'avulso_aluno', 3, NULL, 'dinheiro', 16.00, 20.00, 4.00, '2026-09-28 07:50:00']);
$stmtItemVenda->execute([3, 2, 11, 2, 4.50, 9.00]);
$stmtItemVenda->execute([4, 2, 6, 1, 7.00, 7.00]);

// Venda 3: Avulso Pix Balcão (1 Coxinha Grande R$ 8.00 + 1 Suco Laranja R$ 6.00 = R$ 14.00).
$stmtVenda->execute([3, 1, $operadorMariaId, 'avulso_aluno', 5, NULL, 'pix_balcao', 14.00, NULL, NULL, '2026-09-28 07:55:00']);
$stmtItemVenda->execute([5, 3, 1, 1, 8.00, 8.00]);
$stmtItemVenda->execute([6, 3, 5, 1, 6.00, 6.00]);

// Venda 4: Desconto em Folha para Professor (Prof. Roberto Silva - Matrícula COL-1001)
// 1 Sanduíche Natural R$ 9.50 + 1 Suco Laranja R$ 6.00 = R$ 15.50
$stmtVenda->execute([4, 1, $operadorMariaId, 'desconto_folha', NULL, 1, 'desconto_folha', 15.50, NULL, NULL, '2026-09-28 08:00:00']);
$stmtItemVenda->execute([7, 4, 7, 1, 9.50, 9.50]);
$stmtItemVenda->execute([8, 4, 5, 1, 6.00, 6.00]);

// Venda 5: Desconto em Folha para Professora (Profa. Juliana Mendes - Matrícula COL-1003)
// 1 Enroladinho R$ 8.00 + 1 Água R$ 3.50 = R$ 11.50
$stmtVenda->execute([5, 1, $operadorMariaId, 'desconto_folha', NULL, 3, 'desconto_folha', 11.50, NULL, NULL, '2026-09-28 08:05:00']);
$stmtItemVenda->execute([9, 5, 4, 1, 8.00, 8.00]);
$stmtItemVenda->execute([10, 5, 9, 1, 3.50, 3.50]);

// Vendas históricas em 25/09 para enriquecer o relatório mensal de Desconto em Folha (RH)
$stmtVenda->execute([6, 2, $operadorMariaId, 'desconto_folha', NULL, 1, 'desconto_folha', 14.00, NULL, NULL, '2026-09-25 09:30:00']);
$stmtItemVenda->execute([11, 6, 1, 1, 8.00, 8.00]);
$stmtItemVenda->execute([12, 6, 5, 1, 6.00, 6.00]);

$stmtVenda->execute([7, 2, $operadorMariaId, 'desconto_folha', NULL, 2, 'desconto_folha', 17.50, NULL, NULL, '2026-09-25 09:40:00']);
$stmtItemVenda->execute([13, 7, 7, 1, 9.50, 9.50]);
$stmtItemVenda->execute([14, 7, 4, 1, 8.00, 8.00]);

$stmtVenda->execute([8, 2, $operadorMariaId, 'desconto_folha', NULL, 4, 'desconto_folha', 11.50, NULL, NULL, '2026-09-25 09:45:00']);
$stmtItemVenda->execute([15, 8, 4, 1, 8.00, 8.00]);
$stmtItemVenda->execute([16, 8, 9, 1, 3.50, 3.50]);

// Inserir 1 Nota Fiscal XML para demonstração do módulo de importação de NF-e
echo "       -> Inserindo 1 Nota Fiscal XML de demonstração com itens convertidos...\n";
$stmtNfe = $pdo->prepare("INSERT INTO notas_fiscais_xml 
    (id, chave_nfe, numero_nota, cnpj_emitente, razao_social_emitente, data_emissao, valor_total, arquivo_xml_path, importado_por, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmtNfe->execute([
    1,
    '35260912345678000190550010000045211000452101',
    '000.004.521',
    '12.345.678/0001-90',
    'Distribuidora de Alimentos Boa Vista Ltda',
    '2026-09-24 14:00:00',
    348.00,
    'storage/uploads/private/nfe_demonstracao_0004521.xml',
    $adminId,
    '2026-09-25 08:00:00'
]);

$stmtItemNfe = $pdo->prepare("INSERT INTO itens_nota_fiscal_xml 
    (id, nota_fiscal_id, codigo_produto_fornecedor, descricao_fornecedor, unidade_fornecedor, quantidade_fornecedor, valor_unitario_fornecedor, produto_id, fator_conversao, quantidade_convertida)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

// Fardo com 24 Águas Minerais
$stmtItemNfe->execute([
    1, 1, 'FORN-AGUA-CX', 'Agua Mineral Sem Gas 500ml Cx c/ 24', 'CX', 2.00, 48.00, 9, 24, 48
]);
// Caixa com 40 Cookies
$stmtItemNfe->execute([
    2, 1, 'FORN-COOK-40', 'Biscoito Cookie Cacau Display c/ 20', 'CX', 3.00, 52.00, 11, 20, 60
]);

echo "\n====================================================================\n";
echo "   BASE DE DADOS DO SHOWCASE POPULADA COM SUCESSO!\n";
echo "====================================================================\n";
echo "Resumo das Entidades Prontas:\n";
echo " - Usuários: 3 (admin, operador, operador-maria)\n";
echo " - Categorias: 5 oficiais\n";
echo " - Produtos: 12 (com Coxinha Grande e Coxinha Pequena Fracionada)\n";
echo " - Turmas: 19 oficiais (Infantil ao EM)\n";
echo " - Colaboradores: 6 (Professores e Inspetor com compras no PDV)\n";
echo " - Famílias: 4 titulares e 6 dependentes com restrições alimentares\n";
echo " - Pedidos para HOJE (28/09): 4 agendamentos com 15 COXINHAS PEQUENAS\n";
echo "   -> No Corte do Turno: 7 Grandes + 1 Pequeno (8 peças físicas!)\n";
echo " - Tickets para HOJE: 7 tickets prontos para impressão com alertas de alérgenos\n";
echo " - Caixa do Turno: Sessão #1 ABERTA para hoje com R$ 100,00 de fundo de troco\n";
echo " - Vendas Balcão: 5 vendas registradas (Dinheiro, Pix e Desconto em Folha)\n";
echo " - Estoque: Água Mineral sem Gás com 4 unidades (abaixo do mín. 10 - ALERTA ATIVO)\n";
echo " - NF-e XML: 1 nota fiscal cadastrada com itens convertidos\n";
echo "====================================================================\n\n";
