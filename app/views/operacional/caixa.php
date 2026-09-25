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

    <!-- Alternador de Turno e Ações Principais -->
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="btn-group btn-group-sm" role="group" aria-label="Seletor de Turno">
            <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=matutino" 
               class="btn btn-enterprise <?php echo ($turnoAtual === 'matutino') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                Matutino (Manhã)
            </a>
            <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=vespertino" 
               class="btn btn-enterprise <?php echo ($turnoAtual === 'vespertino') ? 'btn-primary' : 'btn-outline-primary'; ?>">
                Vespertino (Tarde)
            </a>
        </div>

        <?php if ($caixaAberto): ?>
            <button type="button" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalRecargaDinheiro">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Recarga em Dinheiro
            </button>
            <a href="<?php echo $baseUrl; ?>/operacional/caixa/detalhes/<?php echo $sessaoAberta['id']; ?>" class="btn btn-enterprise btn-primary btn-sm" title="Extrato Analítico das Operações">
                Extrato da Sessão
            </a>
        <?php else: ?>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" disabled title="Caixa fechado: Abra a sessão do turno para receber recargas presenciais em dinheiro">
                + Recarga em Dinheiro (Caixa Fechado)
            </button>
        <?php endif; ?>

        <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="btn btn-enterprise btn-primary btn-sm" title="Ir para o Terminal do PDV">
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
                                       placeholder="0,00" required autofocus 
                                       oninput="calcularQuebraSobra();" onkeyup="calcularQuebraSobra();" onchange="calcularQuebraSobra();">
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
     FILTRO E HISTÓRICO DE SESSÕES DE CAIXA
     ======================================================================== -->
<div class="card-enterprise mb-3">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/operacional/caixa" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="turno" value="<?php echo htmlspecialchars($turnoAtual, ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="col-12 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Filtrar Histórico por Turno</label>
                <select name="filtro_turno" class="form-select form-select-sm font-12">
                    <option value="" <?php echo empty($filtroTurno) ? 'selected' : ''; ?>>Todos os Turnos</option>
                    <option value="matutino" <?php echo (($filtroTurno ?? '') === 'matutino') ? 'selected' : ''; ?>>Matutino (Manhã)</option>
                    <option value="vespertino" <?php echo (($filtroTurno ?? '') === 'vespertino') ? 'selected' : ''; ?>>Vespertino (Tarde)</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Data Início</label>
                <input type="date" name="data_inicio" class="form-control form-control-sm font-tabular font-12" value="<?php echo htmlspecialchars($dataInicio ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Data Fim</label>
                <input type="date" name="data_fim" class="form-control form-control-sm font-tabular font-12" value="<?php echo htmlspecialchars($dataFim ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-fill">
                    Filtrar
                </button>
                <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo $turnoAtual; ?>" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">
                    Limpar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card-enterprise mb-4">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold text-dark">Histórico de Sessões de Caixa</span>
            <span class="text-microcopy ms-2">Registros e conciliações de gaveta</span>
        </div>
        <span class="text-muted small">
            Exibindo <span class="font-tabular fw-semibold"><?php echo count($historico); ?></span> de <span class="font-tabular fw-semibold"><?php echo $totalRegistros ?? count($historico); ?></span> sessões
            <?php if (!empty($totalPaginas) && $totalPaginas > 1): ?>
                (Pág. <span class="font-tabular"><?php echo $paginaAtual; ?></span> de <span class="font-tabular"><?php echo $totalPaginas; ?></span>)
            <?php endif; ?>
        </span>
    </div>
    <div class="card-enterprise-body p-0">
        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table table-enterprise table-sm mb-0 align-middle" style="font-size: 11.5px; white-space: nowrap;">
                <thead>
                    <tr>
                        <th style="min-width: 50px; width: 5%;">Sessão</th>
                        <th style="min-width: 60px; width: 6%;">Turno</th>
                        <th style="min-width: 100px;">Operador</th>
                        <th style="min-width: 75px; width: 8%;">Abertura</th>
                        <th style="min-width: 75px; width: 8%;">Fechamento</th>
                        <th class="text-end" style="min-width: 65px; width: 7%;">Fundo</th>
                        <th class="text-end" style="min-width: 70px; width: 8%;">PDV Din.</th>
                        <th class="text-end" style="min-width: 70px; width: 8%;">Recargas</th>
                        <th class="text-end" style="min-width: 60px; width: 7%;">Pix</th>
                        <th class="text-end" style="min-width: 70px; width: 8%;">Gaveta</th>
                        <th class="text-end" style="min-width: 70px; width: 8%;">Diferença</th>
                        <th class="text-center" style="min-width: 70px; width: 7%;">Status</th>
                        <th class="text-end" style="min-width: 120px; width: 12%;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historico)): ?>
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted">
                                Nenhuma sessão de caixa encontrada para os filtros aplicados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historico as $s): ?>
                            <?php 
                                $dif = ($s['diferenca_quebra_sobra'] !== null) ? (float)$s['diferenca_quebra_sobra'] : null;
                                $isFechado = ($s['status'] === 'fechado');
                                $recargasSessao = (float)($s['total_dinheiro_recargas'] ?? 0.0);
                                $abertoStr = date('d/m/Y H:i', strtotime($s['aberto_em']));
                                $abertoCurto = date('d/m H:i', strtotime($s['aberto_em']));
                                $fechadoStr = !empty($s['fechado_em']) ? date('d/m/Y H:i', strtotime($s['fechado_em'])) : null;
                                $fechadoCurto = !empty($s['fechado_em']) ? date('d/m H:i', strtotime($s['fechado_em'])) : null;
                            ?>
                            <tr>
                                <td class="font-tabular font-semibold">#<?php echo $s['id']; ?></td>
                                <td>
                                    <span class="badge bg-white text-dark border px-1" style="font-size: 10px;">
                                        <?php echo ($s['turno'] === 'matutino') ? 'Manhã' : 'Tarde'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 160px;" title="<?php echo htmlspecialchars($s['operador_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($s['operador_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td class="font-tabular text-secondary" title="<?php echo $abertoStr; ?>"><?php echo $abertoCurto; ?></td>
                                <td class="font-tabular text-secondary">
                                    <?php if ($fechadoCurto): ?>
                                        <span title="<?php echo $fechadoStr; ?>"><?php echo $fechadoCurto; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end font-tabular">R$ <?php echo number_format((float)$s['fundo_troco_inicial'], 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular">R$ <?php echo number_format((float)$s['total_dinheiro_avulso'], 2, ',', '.'); ?></td>
                                <td class="text-end font-tabular text-success font-semibold">R$ <?php echo number_format($recargasSessao, 2, ',', '.'); ?></td>
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
                                        <span class="text-success font-semibold">+<?php echo number_format($dif, 2, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span class="text-danger font-semibold">-<?php echo number_format(abs($dif), 2, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isFechado): ?>
                                        <span class="status-indicator status-inactive" style="font-size: 11px;"><span class="bullet">●</span> Fechado</span>
                                    <?php else: ?>
                                        <span class="status-indicator status-active" style="font-size: 11px;"><span class="bullet">●</span> Aberto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="<?php echo $baseUrl; ?>/operacional/caixa/detalhes/<?php echo $s['id']; ?>" 
                                           class="btn btn-enterprise btn-secondary px-2 py-0" style="font-size: 11px;" title="Extrato Analítico Detalhado">
                                            Extrato
                                        </a>
                                        <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo $s['id']; ?>" 
                                           class="btn btn-enterprise btn-secondary px-2 py-0" target="_blank" style="font-size: 11px;" title="Visualizar Espelho A4">
                                            Espelho
                                        </a>
                                        <a href="<?php echo $baseUrl; ?>/operacional/caixa/espelho/<?php echo $s['id']; ?>/pdf" 
                                           class="btn btn-enterprise btn-secondary px-2 py-0" style="font-size: 11px;" title="Baixar PDF A4">
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
        <!-- Paginação Dinâmica Conforme Configuração Oficial -->
        <?php require __DIR__ . '/../partials/pagination.php'; ?>
    </div>
</div>

<!-- ========================================================================
     MODAL DE RECARGA PRESENCIAL EM DINHEIRO NO BALCÃO (Tela 5.4 do FSD)
     ======================================================================== -->
<div class="modal fade" id="modalRecargaDinheiro" tabindex="-1" aria-labelledby="modalRecargaDinheiroLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content card-enterprise" style="border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header card-enterprise-header d-flex justify-content-between align-items-center py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-weight-bold text-dark" id="modalRecargaDinheiroLabel">Recarga Presencial em Dinheiro (Balcão)</span>
                    <span class="badge bg-white text-dark border" style="font-size: 11px;">
                        Turno <?php echo ucfirst($turnoAtual); ?> — Caixa #<?php echo (int)($sessaoAberta['id'] ?? 0); ?>
                    </span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body p-4">
                <!-- Painel de Erros do Modal -->
                <div id="recargaErroBox" class="alert alert-danger font-12 py-2 px-3 mb-3 d-none"></div>

                <!-- ETAPA 1 E 2: BUSCA E SELEÇÃO DE RESPONSÁVEL -->
                <div id="recargaEtapaSelecao">
                    <label class="form-label font-12 font-semibold text-secondary">
                        1. Localizar Titular da Carteira Familiar (Responsável ou Aluno)
                    </label>
                    <div class="input-group mb-2">
                        <span class="input-group-text bg-light">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </span>
                        <input type="text" id="recargaInputBusca" class="form-control form-control-sm font-13" 
                               placeholder="Digite nome do responsável, CPF ou nome/matrícula do aluno..." 
                               autocomplete="off" autofocus>
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="recargaBtnLimparBusca" style="display: none;">
                            Limpar
                        </button>
                    </div>

                    <!-- Indicador de Carregamento -->
                    <div id="recargaLoadingBusca" class="text-center py-2 text-secondary font-12 d-none">
                        <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span> Buscando responsáveis...
                    </div>

                    <!-- Lista de Resultados -->
                    <div id="recargaListaResultados" class="list-group mb-3 border rounded" style="max-height: 200px; overflow-y: auto; display: none;"></div>

                    <!-- Card de Responsável Selecionado -->
                    <div id="recargaCardSelecionado" class="p-3 mb-3 rounded border bg-light d-none">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-primary text-white font-11 mb-1">Responsável Selecionado</span>
                                <h6 class="font-weight-bold text-dark mb-0 font-14" id="recargaNomeSelecionado">—</h6>
                                <div class="font-11 text-secondary mt-1">
                                    <span>CPF: <strong id="recargaCpfSelecionado" class="font-tabular">—</strong></span>
                                    <span class="ms-2">Email: <span id="recargaEmailSelecionado">—</span></span>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="font-11 text-secondary d-block">Saldo Atual da Família</span>
                                <span class="font-tabular font-16 font-bold text-dark" id="recargaSaldoAtualSelecionado">R$ 0,00</span>
                            </div>
                        </div>

                        <!-- Dependentes Vinculados -->
                        <div class="pt-2 border-top">
                            <span class="font-11 font-semibold text-secondary">Alunos Dependentes:</span>
                            <div id="recargaDependentesSelecionados" class="font-11 text-dark mt-1">—</div>
                        </div>

                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="recargaBtnTrocarResponsavel">
                                Trocar Titular
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ETAPA 3: VALOR EM DINHEIRO DA RECARGA -->
                <div id="recargaEtapaValor" class="d-none">
                    <hr class="my-3 text-muted">
                    <label class="form-label font-12 font-semibold text-secondary">
                        2. Valor em Dinheiro Recebido no Balcão
                    </label>

                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text font-bold font-14">R$</span>
                                <input type="text" id="recargaInputValor" class="form-control form-control-lg font-tabular font-bold input-money" 
                                       placeholder="0,00" required>
                            </div>
                            <div class="form-text font-11 text-muted">
                                O valor será creditado imediatamente na carteira familiar e somado à gaveta do caixa.
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-11 text-secondary mb-1">Atalhos de Valor:</label>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="btn btn-enterprise btn-secondary btn-sm font-tabular btn-atalho-valor" data-valor="20,00">R$ 20</button>
                                <button type="button" class="btn btn-enterprise btn-secondary btn-sm font-tabular btn-atalho-valor" data-valor="50,00">R$ 50</button>
                                <button type="button" class="btn btn-enterprise btn-secondary btn-sm font-tabular btn-atalho-valor" data-valor="100,00">R$ 100</button>
                                <button type="button" class="btn btn-enterprise btn-secondary btn-sm font-tabular btn-atalho-valor" data-valor="200,00">R$ 200</button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-11 font-semibold text-secondary">Observações da Recarga (Opcional)</label>
                        <input type="text" id="recargaInputObservacoes" class="form-control form-control-sm font-12" 
                               placeholder="Ex: Entregue em cédulas de R$ 50 pelo responsável na entrada...">
                    </div>
                </div>

                <!-- ETAPA 4: PAINEL DE SUCESSO E COMPROVANTE -->
                <div id="recargaPainelSucesso" class="d-none text-center py-3">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white" style="width: 48px; height: 48px;">
                            <svg width="24" height="24" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
                            </svg>
                        </div>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-1">Recarga em Dinheiro Confirmada!</h5>
                    <p class="font-13 text-secondary mb-3" id="recargaMsgSucessoTexto">
                        O crédito foi adicionado com sucesso à carteira familiar.
                    </p>

                    <div class="card p-3 bg-light border text-start mx-auto mb-4" style="max-width: 460px;">
                        <div class="d-flex justify-content-between font-12 mb-1">
                            <span class="text-secondary">Responsável:</span>
                            <span class="font-semibold text-dark" id="recargaResumoNome">—</span>
                        </div>
                        <div class="d-flex justify-content-between font-12 mb-1">
                            <span class="text-secondary">Valor Recebido em Espécie:</span>
                            <span class="font-tabular font-bold text-success font-13" id="recargaResumoValor">+ R$ 0,00</span>
                        </div>
                        <div class="d-flex justify-content-between font-12 mb-1">
                            <span class="text-secondary">Novo Saldo da Carteira:</span>
                            <span class="font-tabular font-bold text-dark font-14" id="recargaResumoNovoSaldo">R$ 0,00</span>
                        </div>
                        <div class="d-flex justify-content-between font-12 pt-1 border-top mt-1">
                            <span class="text-secondary">Protocolo / Transação:</span>
                            <span class="font-tabular text-muted" id="recargaResumoProtocolo">#0</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-enterprise btn-primary px-3" id="recargaBtnImprimirHtml">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                            </svg>
                            Imprimir Comprovante Térmico (80mm)
                        </button>
                        <a href="#" target="_blank" class="btn btn-enterprise btn-primary px-3" id="recargaBtnBaixarPdf">
                            Baixar Comprovante em PDF
                        </a>
                        <button type="button" class="btn btn-enterprise btn-primary px-3" onclick="window.location.reload();">
                            Concluir e Atualizar Caixa
                        </button>
                    </div>
                    <div class="d-flex justify-content-center gap-3 font-11 mt-2">
                        <a id="recargaBtnAba" href="#" target="_blank" class="text-secondary text-decoration-none" title="Abrir comprovante em nova aba">
                            ↗ Abrir em nova aba
                        </a>
                    </div>
                </div>
            </div>

            <div class="modal-footer card-enterprise-footer py-2 px-4 d-flex justify-content-between" id="recargaModalFooter">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-enterprise btn-primary btn-sm px-4" id="recargaBtnConfirmarRecarga" disabled>
                    Confirmar Recarga em Dinheiro
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================================================
// CONFERÊNCIA FÍSICA E QUEBRA/SOBRA DE CAIXA
// ============================================================================
function calcularQuebraSobra() {
    const input = document.getElementById('inputTotalInformado');
    const box = document.getElementById('boxDiferencaCaixa');
    const valorEl = document.getElementById('valorDiferenca');
    const labelEl = document.getElementById('labelDiferenca');
    const msgEl = document.getElementById('msgDiferenca');
    const esperado = parseFloat(document.getElementById('valorEsperadoGaveta')?.value) || 0.0;

    if (!input || !box) return;

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
        msgEl.textContent = 'Há mais dinheiro físico na gaveta do que o registrado nas vendas.';
    } else {
        labelEl.textContent = 'Quebra / Falta de Caixa:';
        valorEl.textContent = '- R$ ' + Math.abs(dif).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        valorEl.className = 'font-16 font-bold font-tabular text-danger';
        msgEl.textContent = 'Atenção: O valor físico contado é inferior ao total esperado pelas operações.';
    }
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

function confirmarFechamento() {
    return confirm('Atenção: O fechamento de caixa encerrará a sessão operacional do turno e emitirá o espelho oficial. Deseja realmente concluir?');
}

// ============================================================================
// MODAL DE RECARGA PRESENCIAL EM DINHEIRO NO BALCÃO (Tela 5.4 do FSD)
// ============================================================================
(function() {
    let responsavelSelecionado = null;
    let timerBusca = null;
    let transacaoConcluidaId = null;

    const inputBusca = document.getElementById('recargaInputBusca');
    const btnLimparBusca = document.getElementById('recargaBtnLimparBusca');
    const loadingBusca = document.getElementById('recargaLoadingBusca');
    const listaResultados = document.getElementById('recargaListaResultados');
    const cardSelecionado = document.getElementById('recargaCardSelecionado');
    const etapaValor = document.getElementById('recargaEtapaValor');
    const inputValor = document.getElementById('recargaInputValor');
    const inputObs = document.getElementById('recargaInputObservacoes');
    const btnConfirmar = document.getElementById('recargaBtnConfirmarRecarga');
    const btnTrocar = document.getElementById('recargaBtnTrocarResponsavel');
    const erroBox = document.getElementById('recargaErroBox');
    const painelSucesso = document.getElementById('recargaPainelSucesso');
    const etapaSelecao = document.getElementById('recargaEtapaSelecao');
    const modalFooter = document.getElementById('recargaModalFooter');
    const btnImprimirHtml = document.getElementById('recargaBtnImprimirHtml');
    const btnBaixarPdf = document.getElementById('recargaBtnBaixarPdf');

    if (!inputBusca) return;

    // Busca com debounce de 250ms
    inputBusca.addEventListener('input', function() {
        const termo = this.value.trim();
        btnLimparBusca.style.display = (termo.length > 0) ? 'block' : 'none';

        if (timerBusca) clearTimeout(timerBusca);

        if (termo.length < 2) {
            listaResultados.innerHTML = '';
            listaResultados.style.display = 'none';
            loadingBusca.classList.add('d-none');
            return;
        }

        loadingBusca.classList.remove('d-none');
        listaResultados.style.display = 'none';

        timerBusca = setTimeout(async function() {
            try {
                const url = '<?php echo $baseUrl; ?>/operacional/caixa/buscar-responsavel?q=' + encodeURIComponent(termo);
                const resp = await fetch(url);
                const data = await resp.json();

                loadingBusca.classList.add('d-none');

                const lista = data.dados || data.responsaveis || [];
                const ok = (data.sucesso === true || data.success === true);

                if (ok && lista.length > 0) {
                    renderizarResultados(lista);
                } else {
                    listaResultados.innerHTML = '<div class="p-3 text-center text-muted font-12">Nenhum responsável ou aluno localizado para "' + escapeHtml(termo) + '".</div>';
                    listaResultados.style.display = 'block';
                }
            } catch (err) {
                loadingBusca.classList.add('d-none');
                listaResultados.innerHTML = '<div class="p-3 text-center text-danger font-12">Erro ao consultar responsáveis. Tente novamente.</div>';
                listaResultados.style.display = 'block';
            }
        }, 250);
    });

    btnLimparBusca.addEventListener('click', function() {
        inputBusca.value = '';
        btnLimparBusca.style.display = 'none';
        listaResultados.innerHTML = '';
        listaResultados.style.display = 'none';
        inputBusca.focus();
    });

    function formatarDependentesItem(item) {
        if (item.dependentes_str && item.dependentes_str.trim().length > 0) {
            return item.dependentes_str;
        }
        if (Array.isArray(item.dependentes) && item.dependentes.length > 0) {
            return item.dependentes.map(function(d) {
                return d.nome + (d.nome_turma ? ' (' + d.nome_turma + ')' : '');
            }).join(', ');
        }
        return 'Nenhum aluno vinculado';
    }

    function renderizarResultados(lista) {
        listaResultados.innerHTML = '';
        lista.forEach(function(item) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action p-2 text-start';

            const saldoFormatado = parseFloat(item.saldo_atual || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            const dependentes = formatarDependentesItem(item);
            const cpfExibicao = item.cpf_formatado || item.cpf || '—';

            btn.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="font-13 text-dark">${escapeHtml(item.nome)}</strong>
                        <span class="text-secondary font-11 ms-2">CPF: ${escapeHtml(cpfExibicao)}</span>
                        <div class="font-11 text-muted mt-1">Dependentes: ${escapeHtml(dependentes)}</div>
                    </div>
                    <div class="text-end ps-2">
                        <span class="font-11 text-secondary d-block">Saldo</span>
                        <span class="font-tabular font-semibold text-dark font-12">R$ ${saldoFormatado}</span>
                    </div>
                </div>
            `;

            btn.addEventListener('click', function() {
                selecionarResponsavel(item);
            });

            listaResultados.appendChild(btn);
        });
        listaResultados.style.display = 'block';
    }

    function selecionarResponsavel(item) {
        responsavelSelecionado = item;
        listaResultados.style.display = 'none';
        inputBusca.value = '';
        inputBusca.closest('.input-group').classList.add('d-none');

        const cpfExibicao = item.cpf_formatado || item.cpf || 'Não informado';
        const dependentes = formatarDependentesItem(item);

        document.getElementById('recargaNomeSelecionado').textContent = item.nome;
        document.getElementById('recargaCpfSelecionado').textContent = cpfExibicao;
        document.getElementById('recargaEmailSelecionado').textContent = item.email || item.whatsapp || '—';
        document.getElementById('recargaSaldoAtualSelecionado').textContent = 'R$ ' + parseFloat(item.saldo_atual || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        document.getElementById('recargaDependentesSelecionados').textContent = dependentes;

        cardSelecionado.classList.remove('d-none');
        etapaValor.classList.remove('d-none');
        btnConfirmar.disabled = false;
        inputValor.focus();
    }

    btnTrocar.addEventListener('click', function() {
        responsavelSelecionado = null;
        cardSelecionado.classList.add('d-none');
        etapaValor.classList.add('d-none');
        btnConfirmar.disabled = true;
        inputBusca.closest('.input-group').classList.remove('d-none');
        inputBusca.value = '';
        inputBusca.focus();
    });

    // Atalhos de valor rápido
    document.querySelectorAll('.btn-atalho-valor').forEach(function(btn) {
        btn.addEventListener('click', function() {
            inputValor.value = this.dataset.valor;
            inputValor.focus();
        });
    });

    // Submissão da recarga
    btnConfirmar.addEventListener('click', async function() {
        if (!responsavelSelecionado) {
            mostrarErro('Selecione o responsável para a recarga.');
            return;
        }

        const valorTexto = inputValor.value.replace(/\./g, '').replace(',', '.').trim();
        const valor = parseFloat(valorTexto);

        if (isNaN(valor) || valor <= 0) {
            mostrarErro('Informe um valor de recarga válido maior que zero.');
            inputValor.focus();
            return;
        }

        ocultarErro();
        btnConfirmar.disabled = true;
        btnConfirmar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Registrando Recarga...';

        try {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo $csrfToken; ?>');
            formData.append('responsavel_id', responsavelSelecionado.id);
            formData.append('valor', valor.toFixed(2));
            formData.append('observacoes', inputObs.value.trim());

            const url = '<?php echo $baseUrl; ?>/operacional/caixa/recarga-dinheiro';
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await resp.json();

            const ok = (data.sucesso === true || data.success === true);

            if (ok) {
                transacaoConcluidaId = data.transacao_id;
                exibirSucessoRecarga(data);
            } else {
                mostrarErro(data.mensagem || data.message || 'Falha ao registrar recarga.');
                btnConfirmar.disabled = false;
                btnConfirmar.innerHTML = 'Confirmar Recarga em Dinheiro';
            }
        } catch (err) {
            mostrarErro('Erro de conexão ao processar recarga. Verifique a rede.');
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = 'Confirmar Recarga em Dinheiro';
        }
    });

    function exibirSucessoRecarga(data) {
        etapaSelecao.classList.add('d-none');
        etapaValor.classList.add('d-none');
        modalFooter.classList.add('d-none');
        painelSucesso.classList.remove('d-none');

        const nomeResp = data.responsavel_nome || (data.dados ? data.dados.responsavel_nome : (responsavelSelecionado ? responsavelSelecionado.nome : '—'));
        const valorCred = data.valor_creditado ?? (data.dados ? data.dados.valor : 0);
        const novoSaldo = data.novo_saldo ?? (data.dados ? data.dados.saldo_resultante : 0);

        document.getElementById('recargaResumoNome').textContent = nomeResp;
        document.getElementById('recargaResumoValor').textContent = '+ R$ ' + parseFloat(valorCred).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        document.getElementById('recargaResumoNovoSaldo').textContent = 'R$ ' + parseFloat(novoSaldo).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
        document.getElementById('recargaResumoProtocolo').textContent = '#' + data.transacao_id;

        const urlHtml = '<?php echo $baseUrl; ?>/operacional/caixa/comprovante-recarga/' + data.transacao_id;
        const urlPdf = '<?php echo $baseUrl; ?>/operacional/caixa/comprovante-recarga/' + data.transacao_id + '/pdf';

        btnImprimirHtml.onclick = function() {
            imprimirComprovanteRecargaIframe(data.transacao_id);
        };

        btnBaixarPdf.href = urlPdf;

        const btnAba = document.getElementById('recargaBtnAba');
        if (btnAba) {
            btnAba.href = urlHtml;
        }
    }

    function imprimirComprovanteRecargaIframe(transacaoId) {
        const id = transacaoId || transacaoConcluidaId;
        if (!id) return;

        let iframe = document.getElementById('iframeImpressaoRecarga');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'iframeImpressaoRecarga';
            iframe.style.position = 'fixed';
            iframe.style.top = '-9999px';
            iframe.style.left = '-9999px';
            iframe.style.width = '1px';
            iframe.style.height = '1px';
            iframe.style.border = 'none';
            iframe.style.opacity = '0';
            document.body.appendChild(iframe);
        }

        iframe.src = `${baseUrl}/operacional/caixa/comprovante-recarga/${id}?autoprint=1`;
    }

    function mostrarErro(msg) {
        erroBox.textContent = msg;
        erroBox.classList.remove('d-none');
    }

    function ocultarErro() {
        erroBox.textContent = '';
        erroBox.classList.add('d-none');
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
</script>
