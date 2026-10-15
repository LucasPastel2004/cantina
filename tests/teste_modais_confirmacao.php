<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Teste Automatizado: Auditoria de Eliminação de Caixas de Diálogo Nativas confirm()
 * 
 * Valida se 100% dos formulários, botões e rotinas operacionais utilizam o
 * Modal Corporativo Enterprise de Confirmação em vez da caixa padrão do navegador.
 */

declare(strict_types=1);

$totalPass = 0;
$totalFail = 0;

function assertCustom(bool $cond, string $msg): void {
    global $totalPass, $totalFail;
    if ($cond) {
        echo "  [OK] " . $msg . "\n";
        $totalPass++;
    } else {
        echo "  [FALHA] " . $msg . "\n";
        $totalFail++;
    }
}

echo "====================================================================\n";
echo " AUDITORIA DO SISTEMA CORPORATIVO DE CONFIRMAÇÃO DE AÇÕES\n";
echo "====================================================================\n\n";

// 1. Verificar implementação no assets/js/app.js
$appJs = file_get_contents(__DIR__ . '/../assets/js/app.js');
assertCustom(str_contains($appJs, 'function getOrCreateConfirmModal()'), 'assets/js/app.js implementa getOrCreateConfirmModal()');
assertCustom(str_contains($appJs, 'window.confirmarAcao = function'), 'assets/js/app.js implementa window.confirmarAcao()');
assertCustom(str_contains($appJs, 'window.confirmModal = window.confirmarAcao'), 'assets/js/app.js define alias window.confirmModal');
assertCustom(str_contains($appJs, 'function inicializarConfirmacaoCorporativa()'), 'assets/js/app.js implementa inicializarConfirmacaoCorporativa()');
assertCustom(str_contains($appJs, 'modalConfirmacaoSistema'), 'Modal singleton #modalConfirmacaoSistema definido em app.js');

// 2. Verificar estilos no custom.css
$customCss = file_get_contents(__DIR__ . '/../assets/css/custom.css');
assertCustom(str_contains($customCss, '.modal-confirm-enterprise'), 'assets/css/custom.css define classe .modal-confirm-enterprise');
assertCustom(str_contains($customCss, 'z-index: 1075 !important'), 'Modal de confirmação possui z-index 1075 para sobreposição perfeita');

// 3. Verificar tela do Fechamento de Caixa (operacional/caixa.php e operacional/fechamento_caixa.php)
$caixaPhp = file_get_contents(__DIR__ . '/../app/views/operacional/caixa.php');
assertCustom(!str_contains($caixaPhp, "return confirm("), 'operacional/caixa.php não contém chamadas nativas confirm()');
assertCustom(str_contains($caixaPhp, "window.confirmarAcao({"), 'operacional/caixa.php invoca window.confirmarAcao');
assertCustom(str_contains($caixaPhp, "titulo: 'Encerramento de Caixa'"), 'operacional/caixa.php define título corporativo de encerramento');

$fechamentoPhp = file_get_contents(__DIR__ . '/../app/views/operacional/fechamento_caixa.php');
assertCustom(!str_contains($fechamentoPhp, "return confirm("), 'operacional/fechamento_caixa.php não contém chamadas nativas confirm()');
assertCustom(str_contains($fechamentoPhp, "window.confirmarAcao({"), 'operacional/fechamento_caixa.php invoca window.confirmarAcao');

// 4. Verificar módulos de Admin (alunos, turmas, colaboradores, produtos, categorias, calendário)
$arquivosAdmin = [
    'app/views/admin/alunos.php' => 'data-confirm',
    'app/views/admin/turmas.php' => 'data-confirm',
    'app/views/admin/colaboradores.php' => 'data-confirm',
    'app/views/admin/produtos.php' => 'data-confirm',
    'app/views/admin/categorias.php' => 'data-confirm',
    'app/views/admin/calendario.php' => 'data-confirm',
];

foreach ($arquivosAdmin as $arq => $tag) {
    $conteudo = file_get_contents(__DIR__ . '/../' . $arq);
    assertCustom(!str_contains($conteudo, "onsubmit=\"return confirm("), "$arq não possui onsubmit com confirm()");
    assertCustom(!str_contains($conteudo, "onclick=\"return confirm("), "$arq não possui onclick com confirm()");
    assertCustom(str_contains($conteudo, $tag), "$arq utiliza interceptador corporativo declarativo $tag");
}

// 5. Verificar módulos de Estoque (perdas, conferir_xml)
$perdasPhp = file_get_contents(__DIR__ . '/../app/views/estoque/perdas.php');
assertCustom(!str_contains($perdasPhp, "return confirm("), 'estoque/perdas.php não possui chamada nativa confirm()');
assertCustom(str_contains($perdasPhp, "window.confirmarAcao({"), 'estoque/perdas.php invoca window.confirmarAcao');

$conferirXmlPhp = file_get_contents(__DIR__ . '/../app/views/estoque/conferir_xml.php');
assertCustom(!str_contains($conferirXmlPhp, "onclick=\"return confirm("), 'estoque/conferir_xml.php não possui onclick com confirm()');
assertCustom(str_contains($conferirXmlPhp, "data-confirm="), 'estoque/conferir_xml.php utiliza data-confirm nos botões de cancelar');

// 6. Varredura recursiva de segurança: NENHUM arquivo PHP ou JS além de app.js pode chamar confirm()
$viewFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app/views'));
$arquivosComConfirm = [];

foreach ($viewFiles as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (preg_match('/(?<!\w)confirm\s*\(/i', $content)) {
            $arquivosComConfirm[] = $file->getPathname();
        }
    }
}

assertCustom(count($arquivosComConfirm) === 0, 'Varredura exaustiva: ZERO arquivos de views contêm confirm() nativo (' . count($arquivosComConfirm) . ' encontrados)');

echo "\n--------------------------------------------------------------------\n";
echo " RESULTADO: $totalPass / " . ($totalPass + $totalFail) . " asserções aprovadas\n";
echo "--------------------------------------------------------------------\n";

if ($totalFail === 0) {
    echo " >>> SUCESSO TOTAL: CONFIRMAÇÃO CORPORATIVA 100% HOMOLOGADA! <<<\n";
    exit(0);
} else {
    echo " >>> FALHA: OCORRÊNCIAS DE CONFIRM NATIVO AINDA PRESENTES! <<<\n";
    exit(1);
}
