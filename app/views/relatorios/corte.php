<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Consolidado de Corte do Turno (Filtros e Emissão A4 Oficial)
 * 
 * Conforme docs/FSD.md (Área 6 / Módulo 8, Relatório 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dataConsumo = $dataConsumo ?? date('Y-m-d');
$turnoConsumo = $turnoConsumo ?? 'matutino';
$dadosCorte = $dadosCorte ?? [];
$statusCorte = $statusCorte ?? ($dadosCorte['status_corte'] ?? []);

$dataFmt = date('d/m/Y', strtotime($dataConsumo));
$turnoNome = ($turnoConsumo === 'matutino') ? 'Matutino' : 'Vespertino';
$horaCorte = ($turnoConsumo === 'matutino') ? '08:00' : '13:30';
$corteAberto = $statusCorte['pode_alterar'] ?? false;
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

    <!-- Alertas Flash do Sistema -->
    <?php require __DIR__ . '/../partials/alert.php'; ?>

    <!-- Topo da Página com Título e Ações Rápidas -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h5 font-weight-bold text-dark mb-0">Relatório de Corte do Turno</h1>
                <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                    <span style="color: <?php echo $corteAberto ? '#d97706' : '#64748b'; ?>;">●</span> <?php echo $corteAberto ? 'Agendamento Aberto' : 'Corte Fechado'; ?>
                </span>
            </div>
            <p class="text-secondary text-microcopy mb-0">
                Selecione a data e o turno escolar para emitir o relatório consolidado timbrado oficial para fornecedores parceiros.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?php echo $baseUrl; ?>/operacional/corte?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
               class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" 
               title="Ir para o painel operacional de conferência e corte">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                    <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                </svg>
                Painel de Corte Operacional
            </a>
        </div>
    </div>

    <!-- Bloco de Filtros com Emissão Direta A4 -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-body p-3">
            <form method="GET" action="<?php echo $baseUrl; ?>/relatorios/corte/imprimir" target="_blank" class="row g-2 align-items-end" id="formFiltroCorte">
                <div class="col-auto">
                    <label for="inputData" class="form-label text-microcopy font-weight-bold text-secondary mb-1 d-block">DATA DO CONSUMO:</label>
                    <input type="date" id="inputData" name="data" class="form-control form-control-sm font-tabular" 
                           style="width: 155px;" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="col-auto">
                    <label for="selectTurno" class="form-label text-microcopy font-weight-bold text-secondary mb-1 d-block">TURNO ESCOLAR:</label>
                    <select id="selectTurno" name="turno" class="form-select form-select-sm" style="width: 165px;">
                        <option value="matutino" <?php echo ($turnoConsumo === 'matutino') ? 'selected' : ''; ?>>Matutino (Manhã)</option>
                        <option value="vespertino" <?php echo ($turnoConsumo === 'vespertino') ? 'selected' : ''; ?>>Vespertino (Tarde)</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" 
                            id="btnImprimirCorteA4"
                            class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5" 
                            style="height: 34px;"
                            title="Gerar e abrir espelho oficial A4 timbrado para conferência e impressão / salvar PDF">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                        </svg>
                        Imprimir / Salvar PDF (A4)
                    </button>
                </div>
                <div class="col text-end text-muted text-microcopy pb-2">
                    Corte operacional pontual: <strong><?php echo $horaCorte; ?></strong>
                    <?php if ($corteAberto && isset($statusCorte['minutos_restantes'])): ?>
                        <span class="text-amber ms-1">(restam aprox. <?php echo $statusCorte['minutos_restantes']; ?> min)</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

</div>
