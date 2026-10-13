<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Resiliência de Cobrança Pix no F5 (Recarregamento de Página)
 * 
 * Valida que ao atualizar a página (F5), uma cobrança Pix pendente
 * dentro do prazo de validade (15 min) é recuperada e renderizada
 * com QR Code, código Copia e Cola e cronômetro regressivo contínuo.
 */

define('CANTINA_SISTEMA', true);
define('CANTINA_TEST_MODE', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/core/RBAC.php';
require_once __DIR__ . '/../app/services/QrCodeService.php';
require_once __DIR__ . '/../app/services/PixService.php';
require_once __DIR__ . '/../app/models/Configuracao.php';
require_once __DIR__ . '/../app/models/Responsavel.php';
require_once __DIR__ . '/../app/models/TransacaoCarteira.php';
require_once __DIR__ . '/../app/controllers/CarteiraController.php';

use App\Core\Session;
use App\Services\PixService;
use App\Models\TransacaoCarteira;
use App\Models\Responsavel;
use App\Controllers\CarteiraController;

$totalAssercoes = 0;
$aprovadas = 0;

function assercao(bool $condicao, string $descricao): void {
    global $totalAssercoes, $aprovadas;
    $totalAssercoes++;
    if ($condicao) {
        $aprovadas++;
        echo " [PASS] {$descricao}\n";
    } else {
        echo " [FAIL] {$descricao}\n";
    }
}

// Inicia sessão para o teste se não iniciada
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

echo "\n======================================================================\n";
echo " TESTE: RESILIÊNCIA DE COBRANÇA PIX NO F5 (PERSISTÊNCIA & RECUPERAÇÃO)\n";
echo "======================================================================\n\n";

$responsavelId = 999991; // ID de teste

// 1. Gera uma cobrança Pix
$cobranca = PixService::gerarCobrancaPix($responsavelId, 50.00, 'Recarga Teste F5');
assercao(!empty($cobranca['txid']), "Cobrança Pix gerada com sucesso com txid: {$cobranca['txid']}");
assercao(!empty($cobranca['qr_code_svg']), "Cobrança contém QR Code vetorial SVG");
assercao(!empty($cobranca['copia_e_cola']), "Cobrança contém código Copia e Cola");

// 2. Simula o recarregamento de página (F5): obterCobrancaPendenteResponsavel
$pendente = PixService::obterCobrancaPendenteResponsavel($responsavelId);
assercao($pendente !== null, "PixService::obterCobrancaPendenteResponsavel localizou a cobrança ativa após recarregamento");
assercao($pendente['txid'] === $cobranca['txid'], "txid da cobrança ativa confere com a cobrança gerada");
assercao((float)$pendente['valor'] === 50.00, "Valor da cobrança pendente confere (R$ 50,00)");
assercao(!empty($pendente['qr_code_svg']), "QR Code SVG preservado e pronto para renderização na view");
assercao(!empty($pendente['copia_e_cola']), "Código Copia e Cola preservado e pronto para o input");
assercao(isset($pendente['segundos_restantes']) && $pendente['segundos_restantes'] > 850, "Tempo restante calculado corretamente (~900s)");

// 3. Testa geração de uma NOVA cobrança substituindo a anterior
$novaCobranca = PixService::gerarCobrancaPix($responsavelId, 100.00, 'Nova Recarga R$ 100');
$pendenteNovo = PixService::obterCobrancaPendenteResponsavel($responsavelId);
assercao($pendenteNovo !== null && $pendenteNovo['txid'] === $novaCobranca['txid'], "Nova cobrança substituiu a cobrança anterior como ativa");
assercao((float)$pendenteNovo['valor'] === 100.00, "Valor atualizado para R$ 100,00");

// A anterior deve ter status 'cancelado'
$statusAnterior = PixService::consultarStatus($cobranca['txid']);
assercao($statusAnterior['status'] === 'cancelado', "Cobrança anterior foi marcada como 'cancelado' automaticamente");

// 4. Testa cancelamento voluntário de cobrança pendente
$cancelado = PixService::cancelarCobranca($novaCobranca['txid'], $responsavelId);
assercao($cancelado === true, "PixService::cancelarCobranca executado com sucesso");
$pendenteAposCancelamento = PixService::obterCobrancaPendenteResponsavel($responsavelId);
assercao($pendenteAposCancelamento === null, "Nenhuma cobrança ativa localizada após cancelamento");

// 5. Gera nova cobrança e testa que após pagamento ela não aparece mais como pendente
$cobrancaPaga = PixService::gerarCobrancaPix($responsavelId, 75.00, 'Recarga Paga Teste');
$statusAntes = PixService::consultarStatus($cobrancaPaga['txid']);
assercao($statusAntes['status'] === 'pendente', "Status inicial da nova cobrança é 'pendente'");

// Simula pagamento da cobrança (marca status 'pago' no cache)
$cobrancaPagaCache = PixService::obterCobrancaCache($cobrancaPaga['txid']);
$cobrancaPagaCache['status'] = 'pago';
PixService::salvarCobrancaCache($cobrancaPaga['txid'], $cobrancaPagaCache);

$pendenteAposPgto = PixService::obterCobrancaPendenteResponsavel($responsavelId);
assercao($pendenteAposPgto === null, "Cobrança com status 'pago' não é retornada como pendente no F5");

// 6. Testa expiração de tempo
$cobrancaExpirada = PixService::gerarCobrancaPix($responsavelId, 50.00, 'Recarga Prestes a Expirar');
$cacheExpirar = PixService::obterCobrancaCache($cobrancaExpirada['txid']);
$cacheExpirar['expira_em'] = time() - 10; // Expirou há 10 segundos
PixService::salvarCobrancaCache($cobrancaExpirada['txid'], $cacheExpirar);

$pendenteExpirada = PixService::obterCobrancaPendenteResponsavel($responsavelId);
assercao($pendenteExpirada === null, "Cobrança expirada no tempo não é retornada como pendente no F5");

echo "\n----------------------------------------------------------------------\n";
echo " RESULTADO: {$aprovadas} de {$totalAssercoes} asserções aprovadas.\n";
echo "----------------------------------------------------------------------\n\n";

if ($aprovadas === $totalAssercoes) {
    exit(0);
} else {
    exit(1);
}
