<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Gestão de Caixa e Prestação de Contas do Turno
 * 
 * Conforme docs/FSD.md (Área 4, Área 6, Módulo 7), docs/DESIGN.md (Anti-Slop Enterprise)
 * e modelo oficial do sistema anterior (Escolar Manager).
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$turnoAtual = $turnoAtual ?? 'matutino';
$sessaoAberta = $sessaoAberta ?? null;
$ultimaSessao = $ultimaSessao ?? null;
$totais = $totais ?? null;
$dadosFechamento = $dadosFechamento ?? null;
$historico = $historico ?? [];
$caixaAberto = ($sessaoAberta !== null);
$csrfToken = \App\Core\Session::generateCsrfToken();

$sessaoAtual = $sessaoAberta ?? $ultimaSessao ?? null;
$sessaoIdAtual = $sessaoAtual['id'] ?? null;

$sessaoOutroTurno = $sessaoOutroTurno ?? null;
$outroTurnoNome = $outroTurnoNome ?? '';
$avisoAtrasoFechamento = $avisoAtrasoFechamento ?? false;
$horarioLimiteTurno = $horarioLimiteTurno ?? '11:00';

// Determinação do Operador e Turno em formato oficial em maiúsculas (Ex: DIVINO - MATUTINO)
$operadorNome = 'NENHUM OPERADOR';
if ($sessaoAtual && !empty($sessaoAtual['operador_nome'])) {
    $operadorNome = mb_strtoupper($sessaoAtual['operador_nome'], 'UTF-8');
} elseif (!empty($_SESSION['usuario']['nome'])) {
    $operadorNome = mb_strtoupper($_SESSION['usuario']['nome'], 'UTF-8');
}
$turnoNomeUpper = mb_strtoupper($turnoAtual, 'UTF-8');
$operadorTurnoDisplay = $sessaoAtual ? "{$operadorNome} - {$turnoNomeUpper}" : "— - {$turnoNomeUpper}";

// Situação da Sessão
$situacaoTexto = $caixaAberto ? 'Caixa aberto' : 'Caixa fechado';
$situacaoBulletCor = $caixaAberto ? '#16a34a' : '#64748b';

// Extração contábil dos campos do Grid Oficial
$fundoCaixaValor = 0.0;
$dataHoraAberturaStr = '—';
$entradaDinheiro = 0.0;
$entradaPix = 0.0;
$totalSaidas = 0.0;
$totalEntradas = 0.0;
$totalCaixa = 0.0;
$esperadoGaveta = 0.0;

if ($dadosFechamento) {
    $fundoCaixaValor = (float)($dadosFechamento['resumo']['fundo']['dinheiro'] ?? 0.0);
    $entradaDinheiro = (float)($dadosFechamento['resumo']['entradas']['dinheiro'] ?? 0.0);
    $entradaPix = (float)($dadosFechamento['resumo']['entradas']['pix'] ?? 0.0);
    $totalSaidas = (float)($dadosFechamento['resumo']['saidas']['total'] ?? 0.0);
    $totalEntradas = (float)($dadosFechamento['resumo']['entradas']['total'] ?? ($entradaDinheiro + $entradaPix));
    $totalCaixa = (float)($dadosFechamento['resumo']['saldo']['total'] ?? ($fundoCaixaValor + $totalEntradas - $totalSaidas));
    $esperadoGaveta = (float)($dadosFechamento['resumo']['saldo']['dinheiro'] ?? ($fundoCaixaValor + $entradaDinheiro));
    if (!empty($dadosFechamento['sessao']['aberto_em'])) {
        $dataHoraAberturaStr = date('d/m/Y, H:i', strtotime($dadosFechamento['sessao']['aberto_em']));
    }
} elseif ($totais) {
    $fundoCaixaValor = (float)($totais['fundo_troco_inicial'] ?? 0.0);
    $entradaDinheiro = (float)(($totais['total_dinheiro_avulso'] ?? 0.0) + ($totais['total_dinheiro_recargas'] ?? 0.0));
    $entradaPix = (float)($totais['total_pix_balcao'] ?? 0.0);
    $totalSaidas = 0.0;
    $totalEntradas = $entradaDinheiro + $entradaPix;
    $esperadoGaveta = (float)($totais['total_esperado_gaveta'] ?? ($fundoCaixaValor + $entradaDinheiro));
    $totalCaixa = $fundoCaixaValor + $totalEntradas - $totalSaidas;
    if ($sessaoAtual && !empty($sessaoAtual['aberto_em'])) {
        $dataHoraAberturaStr = date('d/m/Y, H:i', strtotime($sessaoAtual['aberto_em']));
    }
} elseif ($sessaoAtual) {
    $fundoCaixaValor = (float)($sessaoAtual['fundo_troco_inicial'] ?? 0.0);
    $entradaDinheiro = (float)(($sessaoAtual['total_dinheiro_avulso'] ?? 0.0) + ($sessaoAtual['total_dinheiro_recargas'] ?? 0.0));
    $entradaPix = (float)($sessaoAtual['total_pix_balcao'] ?? 0.0);
    $totalSaidas = 0.0;
    $totalEntradas = $entradaDinheiro + $entradaPix;
    $esperadoGaveta = $fundoCaixaValor + $entradaDinheiro;
    $totalCaixa = $fundoCaixaValor + $totalEntradas - $totalSaidas;
    if (!empty($sessaoAtual['aberto_em'])) {
        $dataHoraAberturaStr = date('d/m/Y, H:i', strtotime($sessaoAtual['aberto_em']));
    }
}
?>

<!-- Migalha de Pão (Breadcrumb) Institucional -->
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb mb-1" style="font-size: 11.5px; color: #64748b;">
        <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>/operacional/pdv" class="text-decoration-none text-secondary">Financeiro</a></li>
        <li class="breadcrumb-item text-secondary">Caixa</li>
        <li class="breadcrumb-item active text-dark font-semibold" aria-current="page">Abertura/fechamento de caixa</li>
    </ol>
</nav>

<!-- Cabeçalho da Página e Alternador de Turno -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Gestão de Caixa e Prestação de Contas</h1>
            <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                <span style="color: <?php echo $situacaoBulletCor; ?>;">●</span> <?php echo $situacaoTexto; ?> — Turno <?php echo ucfirst($turnoAtual); ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Abertura, conciliação de gaveta física, faturamento Cantina Web e prestação de contas do turno.
        </p>
    </div>

    <!-- Alternador de Turno -->
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

<!-- ========================================================================
     PAINEL INSTITUCIONAL: ABERTURA / FECHAMENTO DE CAIXA (MODELO ESCOLAR MANAGER)
     ======================================================================== -->
<div class="card-enterprise mb-4">
    <!-- Barra Superior de Ferramentas / Ações do Caixa -->
    <div class="card-enterprise-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
        <div class="d-flex align-items-center gap-2">
            <!-- Botão Emitir com Ícone de Impressora -->
            <?php if ($sessaoIdAtual): ?>
                <a href="<?php echo $baseUrl; ?>/operacional/caixa/fechamento/pdf?id=<?php echo $sessaoIdAtual; ?>" 
                   target="_blank" 
                   class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1 font-semibold"
                   title="Emitir Relatório Oficial de Fechamento de Caixa (A4 Timbrado)">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    Emitir
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" disabled title="Nenhuma sessão disponível para emissão">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                        <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                    </svg>
                    Emitir
                </button>
            <?php endif; ?>

            <!-- Ações Primárias de Caixa: Abrir ou Fechar -->
            <?php if ($caixaAberto): ?>
                <button type="button" class="btn btn-enterprise btn-danger btn-sm d-inline-flex align-items-center gap-1 font-semibold" data-bs-toggle="modal" data-bs-target="#modalFecharCaixa">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M5 8a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 5 8z"/>
                    </svg>
                    Fechar caixa
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-enterprise btn-success btn-sm d-inline-flex align-items-center gap-1 font-semibold" data-bs-toggle="modal" data-bs-target="#modalAbrirCaixa">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M10.804 8 5 4.633v6.734L10.804 8zm.792-.696a.802.802 0 0 1 0 1.392l-6.363 3.692C4.713 12.69 4 12.345 4 11.692V4.308c0-.653.713-.998 1.233-.696l6.363 3.692z"/>
                    </svg>
                    Abrir caixa
                </button>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($caixaAberto): ?>
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1 font-semibold" data-bs-toggle="modal" data-bs-target="#modalRecargaDinheiro">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                    + Recarga em Dinheiro
                </button>
                <a href="<?php echo $baseUrl; ?>/operacional/caixa/detalhes/<?php echo $sessaoAberta['id']; ?>" class="btn btn-enterprise btn-secondary btn-sm font-semibold" title="Extrato Analítico das Operações">
                    Extrato da Sessão
                </a>
            <?php else: ?>
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" disabled title="Caixa fechado: Abra o caixa para realizar recargas presenciais em dinheiro">
                    + Recarga em Dinheiro
                </button>
            <?php endif; ?>

            <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="btn btn-enterprise btn-secondary btn-sm font-semibold" title="Ir para o PDV de Balcão">
                ← Voltar ao PDV
            </a>
        </div>
    </div>

    <!-- Grid Institucional de Campos do Caixa -->
    <div class="card-enterprise-body p-3">
        <!-- Linha 1: Operador, Situação, Fundo de caixa, Data e hora de abertura -->
        <div class="row g-2 mb-2">
            <div class="col-12 col-md-4">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Operador</label>
                <input type="text" class="form-control form-control-sm bg-light text-dark font-semibold" 
                       value="<?php echo htmlspecialchars($operadorTurnoDisplay, ENT_QUOTES, 'UTF-8'); ?>" readonly>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Situação</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light" style="color: <?php echo $situacaoBulletCor; ?>; font-size: 14px; border-right: none;">●</span>
                    <input type="text" class="form-control form-control-sm bg-light font-semibold" 
                           value="<?php echo $situacaoTexto; ?>" readonly 
                           style="color: <?php echo $caixaAberto ? '#16a34a' : '#475569'; ?>; border-left: none;">
                </div>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Fundo de caixa</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-end font-semibold text-dark" 
                       value="R$ <?php echo number_format($fundoCaixaValor, 2, ',', '.'); ?>" readonly>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Data e hora de abertura</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-center text-dark font-semibold" 
                       value="<?php echo $dataHoraAberturaStr; ?>" readonly>
            </div>
        </div>

        <!-- Linha 2: Entrada dinheiro, Entrada pix, Total saídas -->
        <div class="row g-2 mb-2">
            <div class="col-12 col-md-4">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Entrada dinheiro</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-end font-semibold text-success" 
                       value="R$ <?php echo number_format($entradaDinheiro, 2, ',', '.'); ?>" readonly>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Entrada pix</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-end font-semibold text-primary" 
                       value="R$ <?php echo number_format($entradaPix, 2, ',', '.'); ?>" readonly>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Total saídas</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-end text-muted font-semibold" 
                       value="R$ <?php echo number_format($totalSaidas, 2, ',', '.'); ?>" readonly>
            </div>
        </div>

        <!-- Linha 3: Total entradas, Total caixa -->
        <div class="row g-2">
            <div class="col-12 col-md-6">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Total entradas</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-end font-bold text-dark" 
                       style="font-size: 13.5px;"
                       value="R$ <?php echo number_format($totalEntradas, 2, ',', '.'); ?>" readonly>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Total caixa</label>
                <input type="text" class="form-control form-control-sm bg-light font-tabular text-end font-bold text-dark" 
                       style="font-size: 13.5px;"
                       value="R$ <?php echo number_format($totalCaixa, 2, ',', '.'); ?>" readonly>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================
     DETALHAMENTO ANALÍTICO: VENDAS CANTINA WEB (ONLINE) E BALCÃO (PRESENCIAL)
     ======================================================================== -->
<?php if ($dadosFechamento && (!empty($dadosFechamento['vendas_online']) || !empty($dadosFechamento['vendas_balcao']) || !empty($dadosFechamento['recargas']))): ?>
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2 px-3">
            <span class="font-weight-bold text-dark">Discriminação das Vendas do Turno</span>
            <span class="text-microcopy">
                Cantina Web (Online) + Presencial no Balcão
            </span>
        </div>
        <div class="card-enterprise-body p-3">
            <ul class="nav nav-tabs nav-tabs-enterprise mb-3" id="abasVendas" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active font-12 font-semibold" id="tab-online-tab" data-bs-toggle="tab" data-bs-target="#tab-online" type="button" role="tab">
                        Vendas Cantina Web (Online) 
                        <span class="badge bg-light text-dark border ms-1 font-tabular"><?php echo count($dadosFechamento['vendas_online'] ?? []); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link font-12 font-semibold" id="tab-balcao-tab" data-bs-toggle="tab" data-bs-target="#tab-balcao" type="button" role="tab">
                        Vendas Presenciais no Balcão 
                        <span class="badge bg-light text-dark border ms-1 font-tabular"><?php echo count($dadosFechamento['vendas_balcao'] ?? []); ?></span>
                    </button>
                </li>
                <?php if (!empty($dadosFechamento['recargas'])): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link font-12 font-semibold" id="tab-recargas-tab" data-bs-toggle="tab" data-bs-target="#tab-recargas" type="button" role="tab">
                            Recargas no Balcão 
                            <span class="badge bg-light text-dark border ms-1 font-tabular"><?php echo count($dadosFechamento['recargas']); ?></span>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content" id="abasVendasContent">
                <!-- Aba 1: Vendas Cantina Web -->
                <div class="tab-pane fade show active" id="tab-online" role="tabpanel">
                    <?php if (empty($dadosFechamento['vendas_online'])): ?>
                        <p class="text-muted text-center py-3 font-12 mb-0">Nenhum pedido online agendado para este turno.</p>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-enterprise table-sm mb-0" style="font-size: 11.5px;">
                                <thead>
                                    <tr>
                                        <th>Alimento / Produto</th>
                                        <th>Aluno / Turma</th>
                                        <th class="text-end">Preço Unit.</th>
                                        <th class="text-center">Qtd.</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dadosFechamento['vendas_online'] as $vo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vo['alimento'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($vo['pessoa'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-end font-tabular">R$ <?php echo number_format($vo['preco_unitario'], 2, ',', '.'); ?></td>
                                            <td class="text-center font-tabular"><?php echo $vo['quantidade']; ?></td>
                                            <td class="text-end font-tabular font-semibold">R$ <?php echo number_format($vo['valor_pago'], 2, ',', '.'); ?></td>
                                            <td class="text-center"><span class="badge bg-white text-dark border px-1" style="font-size: 10px;"><?php echo $vo['tipo']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Aba 2: Vendas Balcão -->
                <div class="tab-pane fade" id="tab-balcao" role="tabpanel">
                    <?php if (empty($dadosFechamento['vendas_balcao'])): ?>
                        <p class="text-muted text-center py-3 font-12 mb-0">Nenhuma venda presencial direta registrada no balcão neste turno.</p>
                    <?php else: ?>
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-enterprise table-sm mb-0" style="font-size: 11.5px;">
                                <thead>
                                    <tr>
                                        <th>Alimento / Produto</th>
                                        <th>Pessoa / Identificação</th>
                                        <th class="text-end">Preço Unit.</th>
                                        <th class="text-center">Qtd.</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dadosFechamento['vendas_balcao'] as $vb): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($vb['alimento'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($vb['pessoa'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-end font-tabular">R$ <?php echo number_format($vb['preco_unitario'], 2, ',', '.'); ?></td>
                                            <td class="text-center font-tabular"><?php echo $vb['quantidade']; ?></td>
                                            <td class="text-end font-tabular font-semibold">R$ <?php echo number_format($vb['valor_pago'], 2, ',', '.'); ?></td>
                                            <td class="text-center"><span class="badge bg-white text-dark border px-1" style="font-size: 10px;"><?php echo $vb['tipo']; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Aba 3: Recargas no Balcão -->
                <?php if (!empty($dadosFechamento['recargas'])): ?>
                    <div class="tab-pane fade" id="tab-recargas" role="tabpanel">
                        <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-enterprise table-sm mb-0" style="font-size: 11.5px;">
                                <thead>
                                    <tr>
                                        <th>Horário</th>
                                        <th>Responsável</th>
                                        <th>Tipo / Forma</th>
                                        <th class="text-end">Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dadosFechamento['recargas'] as $rec): ?>
                                        <tr>
                                            <td class="font-tabular text-secondary"><?php echo date('H:i', strtotime($rec['criado_em'])); ?></td>
                                            <td><?php echo htmlspecialchars($rec['responsavel_nome'] ?? 'Responsável', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge bg-white text-dark border px-1" style="font-size: 10px;">
                                                    <?php echo ($rec['tipo_transacao'] === 'recarga_dinheiro') ? 'Dinheiro (Gaveta)' : 'Pix'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end font-tabular font-semibold text-success">R$ <?php echo number_format((float)$rec['valor'], 2, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ========================================================================
     MODAL DE ABERTURA DE CAIXA
     ======================================================================== -->
<div class="modal fade" id="modalAbrirCaixa" tabindex="-1" aria-labelledby="modalAbrirCaixaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-enterprise" style="border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header card-enterprise-header d-flex justify-content-between align-items-center py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-weight-bold text-dark" id="modalAbrirCaixaLabel">Abrir Caixa do Turno <?php echo ucfirst($turnoAtual); ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/operacional/caixa/abrir" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="turno" value="<?php echo htmlspecialchars($turnoAtual, ENT_QUOTES, 'UTF-8'); ?>">

                    <p class="font-12 text-secondary mb-3">
                        Inicie o expediente da cantina. Caso haja cédulas ou moedas de troco físico na gaveta, declare o valor abaixo.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-11 font-semibold text-secondary">Turno de Operação</label>
                            <input type="text" class="form-control form-control-sm font-semibold bg-light" 
                                   value="<?php echo ($turnoAtual === 'matutino') ? 'Matutino (Manhã)' : 'Vespertino (Tarde)'; ?>" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-11 font-semibold text-secondary">Fundo de Troco Inicial (Gaveta)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">R$</span>
                                <input type="text" name="fundo_troco_inicial" class="form-control font-tabular input-money font-semibold" value="0,00" required autofocus>
                            </div>
                            <div class="form-text font-11 text-muted">Padrão R$ 0,00 (caso não utilize troco físico).</div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label font-11 font-semibold text-secondary">Observações de Abertura (Opcional)</label>
                        <input type="text" name="observacoes" class="form-control form-control-sm font-12" placeholder="Ex: Abertura do turno matutino sem troco inicial...">
                    </div>
                </div>
                <div class="modal-footer card-enterprise-footer py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-success btn-sm px-3">
                        Confirmar Abertura de Caixa
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================
     MODAL DE FECHAMENTO DE CAIXA
     ======================================================================== -->
<?php if ($caixaAberto): ?>
<div class="modal fade" id="modalFecharCaixa" tabindex="-1" aria-labelledby="modalFecharCaixaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content card-enterprise" style="border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header card-enterprise-header d-flex justify-content-between align-items-center py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-weight-bold text-dark" id="modalFecharCaixaLabel">Fechamento de Caixa — Turno <?php echo ucfirst($turnoAtual); ?> (#<?php echo (int)$sessaoAberta['id']; ?>)</span>
                    <span class="badge bg-danger text-white font-11">Encerramento</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/operacional/caixa/fechar" method="POST" onsubmit="return confirmarFechamento();">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="sessao_id" value="<?php echo (int)$sessaoAberta['id']; ?>">
                    <input type="hidden" id="valorEsperadoGaveta" value="<?php echo $esperadoGaveta; ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <div class="p-2 border rounded bg-light">
                                <span class="font-11 text-secondary d-block">Fundo de Troco:</span>
                                <span class="font-tabular font-semibold text-dark font-13">R$ <?php echo number_format($fundoCaixaValor, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2 border rounded bg-light">
                                <span class="font-11 text-secondary d-block">Entradas Dinheiro:</span>
                                <span class="font-tabular font-semibold text-success font-13">+ R$ <?php echo number_format($entradaDinheiro, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="p-2 border rounded bg-light">
                                <span class="font-11 text-secondary d-block">Esperado na Gaveta:</span>
                                <span class="font-tabular font-bold text-dark font-14">= R$ <?php echo number_format($esperadoGaveta, 2, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-12 font-semibold text-secondary">Valor em Dinheiro Contado Fisicamente na Gaveta</label>
                        <div class="input-group">
                            <span class="input-group-text font-bold">R$</span>
                            <input type="text" name="total_informado_gaveta" id="inputTotalInformado" 
                                   class="form-control form-control-lg font-tabular font-bold input-money" 
                                   placeholder="0,00" required autofocus 
                                   oninput="calcularQuebraSobra();" onkeyup="calcularQuebraSobra();" onchange="calcularQuebraSobra();">
                        </div>
                        <div class="form-text font-11 text-muted">Informe as cédulas e moedas físicas presentes na gaveta. Se não houver dinheiro físico, informe 0,00.</div>
                    </div>

                    <!-- Card Dinâmico de Quebra / Sobra de Caixa -->
                    <div id="boxDiferencaCaixa" class="p-3 mb-3 rounded border bg-light d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-12 font-semibold" id="labelDiferenca">Diferença de Caixa:</span>
                            <span class="font-16 font-bold font-tabular" id="valorDiferenca">R$ 0,00</span>
                        </div>
                        <div class="font-11 mt-1 text-muted" id="msgDiferenca"></div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label font-11 font-semibold text-secondary">Observações / Justificativa Operacional (Opcional)</label>
                        <textarea name="observacoes" class="form-control form-control-sm font-12" rows="2" 
                                  placeholder="Caso haja diferença ou observações sobre o encerramento do turno, registre aqui..."></textarea>
                    </div>
                </div>
                <div class="modal-footer card-enterprise-footer py-2 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm px-3 font-semibold">
                        Encerrar Sessão de Caixa e Emitir Espelho
                    </button>
                </div>
            </form>
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
