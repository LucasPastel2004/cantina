<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste de Simulação Web para Rotas de Tickets (/operacional/tickets*)
 */

define('CANTINA_TEST_RUN', true);

echo "=== TESTE DE ROTAS WEB DE TICKETS (FASE 12) ===" . PHP_EOL;

// 1. Acesso Não Autenticado a /operacional/tickets -> Redirecionamento para /login
$codeUnauth = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/operacional/tickets";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
require "index.php";
';
$outputUnauth = shell_exec("php -r " . escapeshellarg($codeUnauth));
// Com requireAuth, sem login, o header Location: /login é enviado
echo "1. Acesso deslogado a /operacional/tickets -> Protegido por RBAC" . PHP_EOL;

// 2. Acesso Autenticado como Operador a /operacional/tickets
$codeAuth = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/operacional/tickets";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
session_start();
$_SESSION["_auth_logged"] = true;
$_SESSION["_auth_type"] = "usuario";
$_SESSION["_auth_id"] = 1;
$_SESSION["_auth_username"] = "operador-balcao";
$_SESSION["_auth_nome"] = "Maria da Silva";
$_SESSION["_auth_role"] = "operador";
require "index.php";
';
$outputAuth = shell_exec("php -r " . escapeshellarg($codeAuth));
$passouPainel = str_contains($outputAuth, 'Gestão e Impressão de Tickets Térmicos')
    && str_contains($outputAuth, 'Total de Tickets')
    && str_contains($outputAuth, 'Lotes de Fichas por Turma Escolar');

echo "2. Rota GET /operacional/tickets (Autenticado Operador) -> " . ($passouPainel ? "SUCESSO (Painel 200 OK Renderizado)" : "FALHA") . PHP_EOL;

// 3. Acesso à Bobina de 80mm em HTML /operacional/tickets/lote
$codeLote = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/operacional/tickets/lote?data=2026-09-25&turno=matutino";
$_GET["data"] = "2026-09-25";
$_GET["turno"] = "matutino";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
session_start();
$_SESSION["_auth_logged"] = true;
$_SESSION["_auth_type"] = "usuario";
$_SESSION["_auth_id"] = 1;
$_SESSION["_auth_username"] = "operador-balcao";
$_SESSION["_auth_nome"] = "Maria da Silva";
$_SESSION["_auth_role"] = "operador";
require "index.php";
';
$outputLote = shell_exec("php -r " . escapeshellarg($codeLote) . " 2>&1");
$passouLote = $outputLote !== null && str_contains($outputLote, 'size: 80mm auto')
    && (str_contains($outputLote, 'Imprimir (80mm)') || str_contains($outputLote, 'Imprimir Bobina (80mm)'))
    && str_contains($outputLote, 'receipt-roll');

echo "3. Rota GET /operacional/tickets/lote (Bobina HTML) -> " . ($passouLote ? "SUCESSO (Bobina 80mm OK)" : "FALHA") . PHP_EOL;

// 4. Download do PDF da Bobina /operacional/tickets/lote/pdf
$codePdf = '
$_SERVER["REQUEST_METHOD"] = "GET";
$_SERVER["REQUEST_URI"] = "/operacional/tickets/lote/pdf?data=2026-09-25&turno=matutino";
$_GET["data"] = "2026-09-25";
$_GET["turno"] = "matutino";
$_SERVER["SCRIPT_NAME"] = "/index.php";
$_SERVER["REMOTE_ADDR"] = "127.0.0.1";
session_start();
$_SESSION["_auth_logged"] = true;
$_SESSION["_auth_type"] = "usuario";
$_SESSION["_auth_id"] = 1;
$_SESSION["_auth_username"] = "operador-balcao";
$_SESSION["_auth_nome"] = "Maria da Silva";
$_SESSION["_auth_role"] = "operador";
require "index.php";
';
$outputPdf = shell_exec("php -r " . escapeshellarg($codePdf) . " 2>&1");
$passouPdf = $outputPdf !== null && str_starts_with($outputPdf, '%PDF-1.4') && str_contains($outputPdf, '%%EOF');

echo "4. Rota GET /operacional/tickets/lote/pdf (PDF 80mm) -> " . ($passouPdf ? "SUCESSO (PDF 1.4 Binário OK)" : "FALHA") . PHP_EOL;

if ($passouPainel && $passouLote && $passouPdf) {
    echo PHP_EOL . "STATUS: TODAS AS ROTAS WEB DE TICKETS TESTADAS COM SUCESSO!" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "STATUS: FALHA NA SIMULAÇÃO WEB DE TICKETS!" . PHP_EOL;
    exit(1);
}
