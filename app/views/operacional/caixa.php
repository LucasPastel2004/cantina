<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Gestão de Caixa e Prestação de Contas do Turno
 * 
 * Conforme docs/FSD.md (Área 4, Área 6, Módulo 7) e docs/DESIGN.md (Anti-Slop Enterprise)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$turnoAtual = $turnoAtual ?? 'matutino';
$sessaoAberta = $sessaoAberta ?? null;
$ultimaSessao = $ultimaSessao ?? null;
$totais = $totais ?? null;
$historico = $historico ?? [];
$caixaAberto = ($sessaoAberta !== null);
$csrfToken = \App\Core\Session::generateCsrfToken();

$fundo = (float)($totais['fundo_troco_inicial'] ?? 0.0);
$avulso = (float)($totais['total_dinheiro_avulso'] ?? 0.0);
$recargas = (float)($totais['total_dinheiro_recargas'] ?? 0.0);
$pix = (float)($totais['total_pix_balcao'] ?? 0.0);
$esperadoGaveta = (float)($totais['total_esperado_gaveta'] ?? 0.0);
$totalFaturado = (float)($totais['total_geral_faturado'] ?? 0.0);

$sessaoOutroTurno = $sessaoOutroTurno ?? null;
$outroTurnoNome = $outroTurnoNome ?? '';
$avisoAtrasoFechamento = $avisoAtrasoFechamento ?? false;
$horarioLimiteTurno = $horarioLimiteTurno ?? '11:00';

$statusTurnoTexto = 'Aguardando Abertura';
$statusTurnoCor = '#64748b';
if ($caixaAberto) {
    $statusTurnoTexto = 'Aberto (#' . $sessaoAberta['id'] . ')';
    $statusTurnoCor = '#16a34a';
} elseif ($ultimaSessao !== null && ($ultimaSessao['status'] ?? '') === 'fechado') {
    $statusTurnoTexto = 'Encerrado / Fechado (#' . $ultimaSessao['id'] . ')';
    $statusTurnoCor = '#0284c7';
}
?>

<!-- Cabeçalho da Página e Alternador de Turno -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Gestão de Caixa e Prestação de Contas</h1>
            <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                <span style="color: <?php echo $statusTurnoCor; ?>;">●</span> <?php echo $statusTurnoTexto; ?> — Turno <?php echo ucfirst($turnoAtual); ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Abertura com Fundo de Troco, conciliação física de gaveta e fechamento às 11:00 (matutino) e 16:00 (vespertino).
        </p>
    </div>

    <!-- Alternador de Turno -->
    <div class="d-flex align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group" aria-label="Seletor de Turno">
            <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=matutino" 
               class="btn btn-enterprise <?php echo ($turnoAtual === 'matutino') ? 'btn-primary' : 'btn-secondary'; ?>">
                Matutino (Manhã)
            </a>
            <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=vespertino" 
               class="btn btn-enterprise <?php echo ($turnoAtual === 'vespertino') ? 'btn-primary' : 'btn-secondary'; ?>">
                Vespertino (Tarde)
            </a>
        </div>
        <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="btn btn-enterprise btn-secondary btn-sm" title="Ir para o Terminal do PDV">
            ← Voltar ao PDV
        </a>
    </div>
</div>

<!-- Alerta de Horário Limite de Encerramento Ultrapassado -->
<?php if ($avisoAtrasoFechamento): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3 py-2 px-3" style="font-size: 12px; background: #fef9c3; border: 1px solid #fde047; color: #854d0e; border-radius: 6px;">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink: 0;">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        <span>
            <strong>Horário Previsto Ultrapassado:</strong> O expediente do turno <?php echo ucfirst($turnoAtual); ?> tem previsão de encerramento às <?php echo $horarioLimiteTurno; ?>. Por favor, realize a contagem física da gaveta e conclua a prestação de contas.
        </span>
    </div>
<?php endif; ?>

<!-- Alerta de Caixa Ativo no Outro Turno -->
<?php if ($sessaoOutroTurno): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3 py-2 px-3" style="font-size: 12px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 6px;">
        <div class="d-flex align-items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink: 0;">
                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
            </svg>
            <span>
                Existe uma sessão aberta no turno <strong><?php echo $outroTurnoNome; ?> (Sessão #<?php echo $sessaoOutroTurno['id']; ?>)</strong>.
            </span>
        </div>
        <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo strtolower($outroTurnoNome); ?>" class="btn btn-enterprise btn-secondary btn-sm" style="font-size: 11px;">
            Ver Turno <?php echo $outroTurnoNome; ?> →
        </a>
    </div>
<?php endif; ?>

<!-- Stat-Strip Executiva da Sessão -->
<div class="stat-strip mb-4">
    <div class="stat-strip-item">
        <span class="stat-strip-label">Status do Turno</span>
        <span class="stat-strip-value">
            <?php if ($caixaAberto): ?>
                <span class="status-indicator status-active"><span class="bullet">●</span> Aberto (#<?php echo $sessaoAberta['id']; ?>)</span>
            <?php elseif ($ultimaSessao !== null && ($ultimaSessao['status'] ?? '') === 'fechado'): ?>
                <span class="status-indicator" style="color: #0284c7;"><span class="bullet" style="color: #0284c7;">●</span> Encerrado</span>
            <?php else: ?>
                <span class="status-indicator status-inactive"><span class="bullet">●</span> Não Aberto</span>
            <?php endif; ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Fundo de Troco</span>
        <span class="stat-strip-value font-tabular">
            R$ <?php echo number_format($fundo, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Vendas Dinheiro</span>
        <span class="stat-strip-value font-tabular text-success">
            R$ <?php echo number_format($avulso, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Recargas Dinheiro</span>
        <span class="stat-strip-value font-tabular">
            R$ <?php echo number_format($recargas, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item">
        <span class="stat-strip-label">Pix no Balcão</span>
        <span class="stat-strip-value font-tabular text-primary">
            R$ <?php echo number_format($pix, 2, ',', '.'); ?>
        </span>
    </div>
    <div class="stat-strip-item" style="border-right: none;">
        <span class="stat-strip-label">Esperado na Gaveta</span>
        <span class="stat-strip-value font-tabular fw-bold" style="color: #0f172a;">
            R$ <?php echo number_format($esperadoGaveta, 2, ',', '.'); ?>
        </span>
    </div>
</div>

<?php if (!$caixaAberto): ?>
    <!-- ====================================================================
         CENÁRIO 1: CAIXA FECHADO — CARD DE ABERTURA DO TURNO
         ==================================================================== -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-7">
            <div class="card-enterprise">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark">Abrir Caixa do Turno <?php echo ucfirst($turnoAtual); ?></span>
                    <span class="code-tag">Controle de Gaveta</span>
                </div>
                <div class="card-enterprise-body p-4">
                    <p class="font-13 text-secondary mb-4">
                        Para iniciar o atendimento no <strong>PDV de Balcão</strong> e aceitar pagamentos em dinheiro ou Pix, é obrigatório registrar a abertura da sessão e declarar o Fundo de Troco alocado fisicamente na gaveta.
                    </p>

                    <form action="<?php echo $baseUrl; ?>/operacional/caixa/abrir" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="turno" value="<?php echo htmlspecialchars($turnoAtual, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label font-12 font-semibold">Turno de Operação</label>
                                <input type="text" class="form-control form-control-sm font-semibold bg-light" 
                                       value="<?php echo ($turnoAtual === 'matutino') ? 'Matutino (Encerra às 11:00)' : 'Vespertino (Encerra às 16:00)'; ?>" readonly>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label font-12 font-semibold">Fundo de Troco Inicial (Gaveta)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">R$</span>
                                    <input type="text" name="fundo_troco_inicial" class="form-control font-tabular input-money font-semibold" value="50,00" required autofocus>
                                </div>
                                <div class="form-text font-11 text-muted">Cédulas e moedas físicas colocadas na gaveta.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label font-12 font-semibold">Observações de Abertura (Opcional)</label>
                            <input type="text" name="observacoes" class="form-control form-control-sm" placeholder="Ex: Notas de R$ 5 e R$ 10 conferidas pelo operador...">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-enterprise btn-success px-4">
                                Confirmar Abertura de Caixa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card-enterprise h-100">
                <div class="card-enterprise-header">
                    <span class="font-weight-bold text-dark">Regras Operacionais de Fechamento</span>
                </div>
                <div class="card-enterprise-body p-4 font-13 text-secondary" style="line-height: 1.6;">
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <span class="badge bg-light border text-dark font-tabular">11:00</span>
                        <div><strong>Fechamento Matutino:</strong> O caixa da manhã deve ser conciliado e encerrado pontualmente às 11:00, antes da preparação para o turno vespertino.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2 mb-3">
                        <span class="badge bg-light border text-dark font-tabular">16:00</span>
                        <div><strong>Fechamento Vespertino:</strong> O caixa da tarde deve ser encerrado às 16:00 com emissão do espelho A4 para conferência do gerente.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-light border text-dark">Trava</span>
                        <div><strong>Prevenção de Erros:</strong> Não é permitida mais de uma sessão aberta simultaneamente no mesmo turno.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ====================================================================
         CENÁRIO 2: CAIXA ABERTO — PAINEL DE APURAÇÃO E FECHAMENTO
         ==================================================================== -->
    <div class="row g-4 mb-4">
        <!-- Coluna 1: Demonstrativo de Apuração da Sessão -->
        <div class="col-12 col-lg-6">
            <div class="card-enterprise">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark">Demonstrativo da Sessão #<?php echo $sessaoAberta['id']; ?></span>
                    <span class="code-tag">Aberto em <?php echo date('d/m/Y H:i', strtotime($sessaoAberta['aberto_em'])); ?></span>
                </div>
                <div class="card-enterprise-body p-3">
                    <table class="table table-enterprise table-sm mb-3">
                        <tbody>
                            <tr>
                                <td class="text-secondary">Operador Responsável</td>
                                <td class="text-end font-semibold"><?php echo htmlspecialchars($sessaoAberta['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                            <tr>
                                <td class="text-secondary">Fundo de Troco Inicial</td>
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
                        <span class="font-12 text-secondary">Total Faturado no Balcão:</span>
                        <span class="font-tabular font-semibold font-14 text-dark">R$ <?php echo number_format($totalFaturado, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna 2: Formulário de Fechamento de Caixa -->
        <div class="col-12 col-lg-6">
            <div class="card-enterprise">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-dark">Prestação de Contas e Fechamento de Caixa</span>
                    <span class="status-indicator status-danger"><span class="bullet">●</span> Encerramento</span>
                </div>
                <div class="card-enterprise-body p-4">
                    <p class="font-12 text-secondary mb-3">
                        Conte as notas e moedas físicas existentes na gaveta e insira o valor exato apurado para confronto com o sistema.
                    </p>

                    <form action="<?php echo $baseUrl; ?>/operacional/caixa/fechar" method="POST" onsubmit="return confirmarFechamento();">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="sessao_id" value="<?php echo (int)$sessaoAberta['id']; ?>">
                        <input type="hidden" id="valorEsperadoGaveta" value="<?php echo $esperadoGaveta; ?>">

                        <div class="mb-3">
                            <label class="form-label font-12 font-semibold">Valor em Dinheiro Contado Fisicamente na Gaveta</label>
                            <div class="input-group">
                                <span class="input-group-text font-bold">R$</span>
                                <input type="text" name="total_informado_gaveta" id="inputTotalInformado" 
                                       class="form-control form-control-lg font-tabular font-bold input-money" 
                                       placeholder="0,00" required autofocus oninput="calcularQuebraSobra();">
                            </div>
                            <div class="form-text font-11 text-muted">Inclua o fundo de troco inicial na contagem das cédulas.</div>
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
                            <button type="submit" class="btn btn-enterprise btn-danger px-4">
                                Encerrar Sessão de Caixa e Emitir Espelho
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ========================================================================
     HISTÓRICO RECENTE DE SESSÕES DE CAIXA
     ======================================================================== -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <span class="font-weight-bold text-dark">Histórico de Sessões de Caixa</span>
        <span class="text-microcopy">Últimos fechamentos registrados</span>
    </div>
    <div class="card-enterprise-body p-0">
        <div class="table-responsive">
            <table class="table table-enterprise table-sm mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">Sessão</th>
                        <th>Turno</th>
                        <th>Operador</th>
                        <th>Abertura</th>
                        <th>Fechamento</th>
                        <th class="text-end">Fundo Inicial</th>
                        <th class="text-end">Dinheiro PDV</th>
                        <th class="text-end">Pix Balcão</th>
                        <th class="text-end">Gaveta Informada</th>
                        <th class="text-end">Diferença</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 140px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">
                                Nenhuma sessão de caixa registrada no histórico recente.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historico as $s): ?>
                            <?php 
                                $dif = ($s['diferenca_quebra_sobra'] !== null) ? (float)$s['diferenca_quebra_sobra'] : null;
                                $isFechado = ($s['status'] === 'fechado');
                            ?>
                            <tr>
                                <td class="font-tabular font-semibold">#<?php echo $s['id']; ?></td>
                                <td>
                                    <span class="badge bg-white text-dark border" style="font-size: 11px;">
                                        <?php echo ucfirst($s['turno']); ?>
                                    </span>
                                </td>
                                <td class="font-12"><?php echo htmlspecialchars($s['operador_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="font-tabular font-11 text-secondary"><?php echo date('d/m/Y H:i', strtotime($s['aberto_em'])); ?></td>
                                <td class="font-tabular font-11 text-secondary">
                                    <?php echo !empty($s['fechado_em']) ? date('d/m/Y H:i', strtotime($s['fechado_em'])) : '<span class="text-muted">—</span>'; ?>
                                </td>
                                <td class="text-end font-tabular">R$ <?php echo number_format((float)$s['fundo_troco_inicial'], 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular">R$ <?php echo number_format((float)$s['total_dinheiro_avulso'], 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular">R$ <?php echo number_format((float)$s['total_pix_balcao'], 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular font-semibold">
                                    <?php echo ($s['total_informado_gaveta'] !== null) ? 'R$ ' . number_format((float)$s['total_informado_gaveta'], 2, ',', '.') : '—'; ?>
                                </td>
                                <td class="text-end font-tabular">
                                    <?php if ($dif === null): ?>
                                        <span class="text-muted">—</span>
                                    <?php elseif ($dif == 0.0): ?>
                                        <span class="text-success font-semibold">R$ 0,00</span>
                                    <?php elseif ($dif > 0): ?>
                                        <span class="text-success font-semibold">+ R$ <?php echo number_format($dif, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-danger font-semibold">- R$ <?php echo number_format(abs($dif), 2, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isFechado): ?>
                                        <span class="status-indicator status-inactive"><span class="bullet">●</span> Fechado</span>
                                    <?php else: ?>
                                        <span class="status-indicator status-active"><span class="bullet">●</span> Aberto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo $s['id']; ?>" 
                                           class="btn btn-enterprise btn-secondary btn-sm" target="_blank" title="Visualizar Espelho A4">
                                            Espelho
                                        </a>
                                        <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo $s['id']; ?>/pdf" 
                                           class="btn btn-enterprise btn-secondary btn-sm" title="Baixar PDF A4">
                                            PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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

    let texto = input.value.replace(/\./g, '').replace(',', '.');
    let informado = parseFloat(texto);

    if (isNaN(informado) || input.value.trim() === '') {
        box.classList.add('d-none');
        return;
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
        msgEl.textContent = 'Há mais dinheiro físico na gaveta do que o registrado nas vendas.';
    } else {
        labelEl.textContent = 'Quebra / Falta de Caixa:';
        valorEl.textContent = '- R$ ' + Math.abs(dif).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        valorEl.className = 'font-16 font-bold font-tabular text-danger';
        msgEl.textContent = 'Atenção: O valor físico contado é inferior ao total esperado pelas operações.';
    }
}

function confirmarFechamento() {
    return confirm('Atenção: O fechamento de caixa encerrará a sessão operacional do turno e emitirá o espelho oficial. Deseja realmente concluir?');
}
</script>
