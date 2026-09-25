<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Fechamento de Caixa por Turno e Prestação de Contas
 * 
 * Conforme docs/FSD.md (Módulo 7, Tela 7.2) e docs/DESIGN.md (Anti-Slop Enterprise)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$turnoAtual = $turnoAtual ?? 'matutino';
$turnoAtivo = $turnoAtivo ?? ucfirst($turnoAtual);
$sessaoAberta = $sessaoAberta ?? null;
$totais = $totais ?? [];
$csrfToken = \App\Core\Session::generateCsrfToken();

$fundo = (float)($totais['fundo_troco_inicial'] ?? 0.0);
$avulso = (float)($totais['total_dinheiro_avulso'] ?? 0.0);
$recargas = (float)($totais['total_dinheiro_recargas'] ?? 0.0);
$pix = (float)($totais['total_pix_balcao'] ?? 0.0);
$esperadoGaveta = (float)($totais['total_esperado_gaveta'] ?? 0.0);
$totalFaturado = (float)($totais['total_geral_faturado'] ?? 0.0);
?>

<!-- Cabeçalho -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Fechamento de Caixa e Prestação de Contas</h1>
            <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                <span class="bullet text-success">●</span> Turno <?php echo $turnoAtivo; ?> — Sessão #<?php echo (int)($sessaoAberta['id'] ?? 0); ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Confronto assistido entre os valores em dinheiro contados na gaveta física e os registros computados no sistema.
        </p>
    </div>

    <div>
        <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo urlencode($turnoAtual); ?>" class="btn btn-enterprise btn-primary btn-sm">
            ← Voltar à Gestão de Caixa
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Demonstrativo de Apuração da Sessão -->
    <div class="col-12 col-lg-6">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold text-dark">Demonstrativo da Sessão #<?php echo (int)($sessaoAberta['id'] ?? 0); ?></span>
                <span class="code-tag">Aberto em <?php echo date('d/m/Y H:i', strtotime($sessaoAberta['aberto_em'] ?? 'now')); ?></span>
            </div>
            <div class="card-enterprise-body p-3">
                <table class="table table-enterprise table-sm mb-3">
                    <tbody>
                        <tr>
                            <td class="text-secondary">Operador de Caixa</td>
                            <td class="text-end font-semibold"><?php echo htmlspecialchars($sessaoAberta['operador_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Fundo de Troco Inicial (Gaveta)</td>
                            <td class="text-end font-tabular">R$ <?php echo number_format($fundo, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">(+) Vendas em Espécie no PDV</td>
                            <td class="text-end font-tabular text-success">+ R$ <?php echo number_format($avulso, 2, ',', '.'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">(+) Recargas em Espécie no Balcão</td>
                            <td class="text-end font-tabular text-success">+ R$ <?php echo number_format($recargas, 2, ',', '.'); ?></td>
                        </tr>
                        <tr style="background: #f8fafc; font-weight: 700;">
                            <td style="color: #0f172a;">(=) Total em Dinheiro Esperado na Gaveta</td>
                            <td class="text-end font-tabular" style="font-size: 15px; color: #0f172a;">
                                R$ <?php echo number_format($esperadoGaveta, 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Entradas em Pix no Balcão (Conta Bancária)</td>
                            <td class="text-end font-tabular text-primary">R$ <?php echo number_format($pix, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
                    <span class="font-12 text-secondary">Total Geral Faturado no Balcão:</span>
                    <span class="font-tabular font-semibold font-14 text-dark">R$ <?php echo number_format($totalFaturado, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário de Fechamento -->
    <div class="col-12 col-lg-6">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold text-dark">Conferência Física da Gaveta</span>
                <span class="status-indicator status-danger"><span class="bullet">●</span> Encerramento</span>
            </div>
            <div class="card-enterprise-body p-4">
                <p class="font-12 text-secondary mb-3">
                    Conte todas as cédulas e moedas físicas existentes na gaveta (incluindo o fundo de troco inicial) e insira o valor total contado.
                </p>

                <form action="<?php echo $baseUrl; ?>/operacional/caixa/fechar" method="POST" onsubmit="return confirmarFechamento();">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="sessao_id" value="<?php echo (int)($sessaoAberta['id'] ?? 0); ?>">
                    <input type="hidden" id="valorEsperadoGaveta" value="<?php echo $esperadoGaveta; ?>">

                    <div class="mb-3">
                        <label class="form-label font-12 font-semibold">Valor em Dinheiro Contado Fisicamente na Gaveta</label>
                        <div class="input-group">
                            <span class="input-group-text font-bold">R$</span>
                            <input type="text" name="total_informado_gaveta" id="inputTotalInformado" 
                                   class="form-control form-control-lg font-tabular font-bold input-money" 
                                   placeholder="0,00" required autofocus 
                                   oninput="calcularQuebraSobra();" onkeyup="calcularQuebraSobra();" onchange="calcularQuebraSobra();">
                        </div>
                        <div class="form-text font-11 text-muted">Digite o somatório exato de notas e moedas físicas apuradas.</div>
                    </div>

                    <!-- Card Dinâmico de Quebra / Sobra de Caixa -->
                    <div id="boxDiferencaCaixa" class="p-3 mb-3 rounded border bg-light d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-12 font-semibold" id="labelDiferenca">Diferença de Caixa:</span>
                            <span class="font-16 font-bold font-tabular" id="valorDiferenca">R$ 0,00</span>
                        </div>
                        <div class="font-11 mt-1 text-muted" id="msgDiferenca"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-12 font-semibold">Observações / Justificativa Operacional</label>
                        <textarea name="observacoes" class="form-control form-control-sm" rows="2" 
                                  placeholder="Caso haja quebra ou sobra de caixa, registre aqui a justificativa para a gerência..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo urlencode($turnoAtual); ?>" class="btn btn-enterprise btn-secondary px-3">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-enterprise btn-danger px-4">
                            Encerrar Caixa e Emitir Espelho A4
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function calcularQuebraSobra() {
    const input = document.getElementById('inputTotalInformado');
    const box = document.getElementById('boxDiferencaCaixa');
    const valorEl = document.getElementById('valorDiferenca');
    const labelEl = document.getElementById('labelDiferenca');
    const msgEl = document.getElementById('msgDiferenca');
    const esperado = parseFloat(document.getElementById('valorEsperadoGaveta').value) || 0.0;

    const raw = (input.value || '').trim();
    if (raw === '') {
        box.classList.add('d-none');
        return;
    }

    let informado = 0;
    if (typeof converterMoedaParaFloat === 'function') {
        informado = converterMoedaParaFloat(raw);
    } else {
        if (raw.indexOf(',') !== -1) {
            const partes = raw.split(',');
            if (partes.length === 2 && partes[1].length <= 2) {
                informado = parseFloat(partes[0].replace(/\./g, '') + '.' + partes[1]) || 0;
            } else {
                const digits = raw.replace(/\D/g, '');
                informado = digits ? (parseInt(digits, 10) / 100) : 0;
            }
        } else if (/^\d+(\.\d{1,2})$/.test(raw)) {
            informado = parseFloat(raw) || 0;
        } else {
            const digits = raw.replace(/\D/g, '');
            informado = digits ? (parseInt(digits, 10) / 100) : 0;
        }
    }

    box.classList.remove('d-none');
    let dif = Math.round((informado - esperado) * 100) / 100;

    if (dif === 0) {
        labelEl.textContent = 'Conferência Exata:';
        valorEl.textContent = 'R$ 0,00';
        valorEl.className = 'font-16 font-bold font-tabular text-success';
        msgEl.textContent = 'O valor informado confere exatamente com o saldo esperado pelo sistema.';
    } else if (dif > 0) {
        labelEl.textContent = 'Sobra de Caixa:';
        valorEl.textContent = '+ R$ ' + dif.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        valorEl.className = 'font-16 font-bold font-tabular text-success';
        msgEl.textContent = 'Há mais dinheiro físico na gaveta do que o registrado nas operações.';
    } else {
        labelEl.textContent = 'Quebra / Falta de Caixa:';
        valorEl.textContent = '- R$ ' + Math.abs(dif).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        valorEl.className = 'font-16 font-bold font-tabular text-danger';
        msgEl.textContent = 'Atenção: O valor físico contado é inferior ao total esperado pelas operações.';
    }
}

function confirmarFechamento() {
    return confirm('Atenção: O fechamento de caixa encerrará definitivamente a sessão do turno e emitirá o espelho formal para assinatura. Deseja prosseguir?');
}

document.addEventListener('DOMContentLoaded', function() {
    const inputInf = document.getElementById('inputTotalInformado');
    if (inputInf) {
        inputInf.addEventListener('moneychange', calcularQuebraSobra);
        inputInf.addEventListener('moneyinput', calcularQuebraSobra);
        inputInf.addEventListener('change', calcularQuebraSobra);
        inputInf.addEventListener('blur', calcularQuebraSobra);
    }
});
</script>
