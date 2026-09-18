<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado de Buscas com Filtros de Texto
 */

define('CANTINA_SISTEMA', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Produto.php';
require_once __DIR__ . '/../app/models/Colaborador.php';
require_once __DIR__ . '/../app/models/Turma.php';

use App\Models\Produto;
use App\Models\Colaborador;
use App\Models\Turma;

echo "=== TESTANDO BUSCA DE PRODUTOS ===" . PHP_EOL;

// 1. Busca por texto existente
$resultados = Produto::listarTodos(['busca' => 'Coxinha']);
echo "Busca 'Coxinha': " . count($resultados) . " resultado(s) retornado(s)." . PHP_EOL;

// 2. Busca por código
$resultadosCod = Produto::listarTodos(['busca' => 'PROD']);
echo "Busca 'PROD': " . count($resultadosCod) . " resultado(s) retornado(s)." . PHP_EOL;

// 3. Busca por texto inexistente
$resultadosInexistente = Produto::listarTodos(['busca' => 'TEXTO_INEXISTENTE_XYZ']);
echo "Busca 'TEXTO_INEXISTENTE_XYZ': " . count($resultadosInexistente) . " resultado(s)." . PHP_EOL;

echo "=== TESTANDO BUSCA DE COLABORADORES ===" . PHP_EOL;

// 4. Busca colaborador
$colabs = Colaborador::listarTodos(false, 'Prof');
echo "Busca colaborador 'Prof': " . count($colabs) . " resultado(s)." . PHP_EOL;

echo "=== TESTANDO BUSCA DE TURMAS ===" . PHP_EOL;

// 5. Busca turma
$turmas = Turma::listarTodas('', 'Ano');
echo "Busca turma 'Ano': " . count($turmas) . " resultado(s)." . PHP_EOL;

echo "SUCESSO: Todas as consultas de busca foram executadas perfeitamente sem SQLSTATE[HY093]!" . PHP_EOL;
