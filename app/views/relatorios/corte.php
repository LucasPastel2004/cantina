<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Consolidado de Corte do Turno para Fornecedores
 * 
 * Conforme docs/FSD.md (Área 6 / Módulo 8, Relatório 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dataConsumo = $dataConsumo ?? date('Y-m-d');
$turnoConsumo = $turnoConsumo ?? 'matutino';
$dadosCorte = $dadosCorte ?? [];
$itens = $dadosCorte['itens'] ?? [];
$totalPecas = (int)($dadosCorte['total_pecas'] ?? 0);
$totalSalgados = (int)($dadosCorte['total_salgados'] ?? 0);
$totalPedidos = (int)($dadosCorte['total_pedidos'] ?? 0);
$totalAlunos = (int)($dadosCorte['total_alunos'] ?? 0);
$totalValor = (float)($dadosCorte['total_valor_estimado'] ?? 0.0);
$statusCorte = $dadosCorte['status_corte'] ?? [];
$textoWhatsApp = $textoWhatsApp ?? '';

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
                Consolidação matemática e analítica de demandas de salgados para compra exata com fornecedores parceiros.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?php echo $baseUrl; ?>/operacional/corte?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
               class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" 
               title="Ir para a tela operacional de conferência e corte">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M1 3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1H1zm7 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>
                    <path d="M0 5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H1a1 1 0 0 1-1-1V5zm3 0a2 2 0 0 1-2 2v4a2 2 0 0 1 2 2h10a2 2 0 0 1 2-2V7a2 2 0 0 1-2-2H3z"/>
                </svg>
                Painel de Corte Operacional
            </a>
        </div>
    </div>

    <!-- Barra de Filtros com Emissão Direta A4 -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-body p-3">
            <form method="GET" action="<?php echo $baseUrl; ?>/relatorios/corte" class="row g-2 align-items-end" id="formFiltroCorte">
                <div class="col-auto">
                    <label for="inputData" class="form-label text-microcopy font-weight-bold text-secondary mb-1 d-block">DATA DO CONSUMO:</label>
                    <input type="date" id="inputData" name="data" class="form-control form-control-sm font-tabular" 
                           style="width: 155px;" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>"
                           onchange="this.form.submit()">
                </div>
                <div class="col-auto">
                    <label for="selectTurno" class="form-label text-microcopy font-weight-bold text-secondary mb-1 d-block">TURNO ESCOLAR:</label>
                    <select id="selectTurno" name="turno" class="form-select form-select-sm" style="width: 165px;"
                            onchange="this.form.submit()">
                        <option value="matutino" <?php echo ($turnoConsumo === 'matutino') ? 'selected' : ''; ?>>Matutino (Manhã)</option>
                        <option value="vespertino" <?php echo ($turnoConsumo === 'vespertino') ? 'selected' : ''; ?>>Vespertino (Tarde)</option>
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" 
                            id="btnAtualizarCorte"
                            formaction="<?php echo $baseUrl; ?>/relatorios/corte"
                            class="btn btn-enterprise btn-secondary btn-sm" 
                            style="height: 34px;">
                        Atualizar Filtros
                    </button>

                    <button type="submit" 
                            id="btnImprimirCorteA4"
                            formaction="<?php echo $baseUrl; ?>/relatorios/corte/imprimir" 
                            formtarget="_blank"
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

    <!-- Stat-Strip Corporativo (Métricas Consolidadas) -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Total de Peças Encomendadas</span>
            <span class="stat-value font-tabular text-dark">
                <?php echo $totalPecas; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">unidades</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Grandes + Pequenos consolidado
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Salgados Solicitados</span>
            <span class="stat-value font-tabular text-dark">
                <?php echo $totalSalgados; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">variedades</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Linhas de produtos distintos
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Pedidos / Alunos Atendidos</span>
            <span class="stat-value font-tabular text-dark">
                <?php echo $totalPedidos; ?> / <?php echo $totalAlunos; ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Lanches agendados no turno
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Custo Estimado da Remessa</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format($totalValor, 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Valor bruto estimado de venda
            </span>
        </div>
    </div>

    <!-- Tabela de Demandas de Produção por Salgado -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center p-3 border-bottom">
            <div>
                <h2 class="h6 font-weight-bold text-dark mb-0">Demandas de Produção por Salgado</h2>
                <span class="text-secondary text-microcopy">Consolidação matemática de salgados para compra junto ao parceiro</span>
            </div>
            <span class="badge bg-light text-secondary border font-tabular px-2 py-1">
                <?php echo count($itens); ?> produtos solicitados
            </span>
        </div>

        <div class="card-enterprise-body p-0">
            <?php if (empty($itens)): ?>
                <div class="p-4 text-center">
                    <div class="mb-2 text-secondary">
                        <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor" class="text-secondary opacity-75">
                            <path d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h12zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2z"/>
                            <path d="M5 8a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5A.5.5 0 0 1 5 8z"/>
                        </svg>
                    </div>
                    <p class="text-secondary mb-0" style="font-size: 13px; font-style: italic;">
                        Não há nada para mostrar aqui
                    </p>
                    <span class="text-muted" style="font-size: 11px;">
                        Nenhum pedido de lanche ou salgado foi registrado para o turno <?php echo htmlspecialchars($turnoNome); ?> em <?php echo htmlspecialchars($dataFmt); ?>.
                    </span>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-enterprise mb-0" id="tabelaRelatorioCorte">
                        <thead>
                            <tr>
                                <th class="cell-center" style="width: 90px;">CÓDIGO</th>
                                <th class="cell-left">PRODUTO / SALGADO</th>
                                <th class="cell-left" style="width: 140px;">CATEGORIA</th>
                                <th class="cell-center" style="width: 130px;">CLASSIFICAÇÃO</th>
                                <th class="cell-right" style="width: 140px;">QTD ENCOMENDADA</th>
                                <th class="cell-right" style="width: 120px;">PREÇO UNIT.</th>
                                <th class="cell-right" style="width: 140px;">SUBTOTAL ESTIMADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <?php 
                                $qtd = (int)($item['quantidade'] ?? 0);
                                $preco = (float)($item['preco'] ?? 0.0);
                                $subtotal = $qtd * $preco;
                                $classificacao = $item['classificacao'] ?? 'Grande';
                                ?>
                                <tr>
                                    <td class="cell-center font-tabular text-secondary" style="font-size: 11px;">
                                        #<?php echo htmlspecialchars((string)($item['codigo'] ?? $item['produto_id'] ?? '-')); ?>
                                    </td>
                                    <td class="cell-left">
                                        <span class="font-weight-semibold text-dark">
                                            <?php echo htmlspecialchars($item['nome'] ?? 'Salgado'); ?>
                                        </span>
                                    </td>
                                    <td class="cell-left text-secondary" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($item['categoria'] ?? 'Lanches'); ?>
                                    </td>
                                    <td class="cell-center">
                                        <span class="badge bg-light text-secondary border font-tabular px-2 py-0" style="font-size: 10px;">
                                            <?php echo htmlspecialchars($classificacao); ?>
                                        </span>
                                    </td>
                                    <td class="cell-right font-tabular font-weight-bold text-dark">
                                        <?php echo $qtd; ?> un
                                    </td>
                                    <td class="cell-right font-tabular text-secondary" style="font-size: 11px;">
                                        R$ <?php echo number_format($preco, 2, ',', '.'); ?>
                                    </td>
                                    <td class="cell-right font-tabular text-dark">
                                        R$ <?php echo number_format($subtotal, 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc; font-weight: 700;">
                                <td colspan="4" class="cell-left text-uppercase font-11 text-secondary">
                                    Total Geral Consolidado (Turno <?php echo htmlspecialchars($turnoNome); ?>):
                                </td>
                                <td class="cell-right font-tabular text-dark font-13">
                                    <?php echo $totalPecas; ?> un
                                </td>
                                <td class="cell-right text-secondary font-11">—</td>
                                <td class="cell-right font-tabular text-dark font-13">
                                    R$ <?php echo number_format($totalValor, 2, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensagem Estruturada para Envio ao Fornecedor via WhatsApp -->
    <?php if (!empty($textoWhatsApp)): ?>
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-0">Mensagem Estruturada para Fornecedor (WhatsApp)</h2>
                    <span class="text-secondary text-microcopy">Texto padronizado para copiar e encaminhar ao parceiro de panificação</span>
                </div>
                <button type="button" class="btn btn-enterprise btn-primary btn-sm" id="btnCopiarWhats" onclick="copiarTextoWhats()">
                    Copiar Texto
                </button>
            </div>
            <div class="card-enterprise-body p-3">
                <textarea id="textoWhatsArea" class="form-control font-monospace" rows="8" readonly style="font-size: 12px; background: #f8fafc; resize: vertical;"><?php echo htmlspecialchars($textoWhatsApp, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
function copiarTextoWhats() {
    var textarea = document.getElementById('textoWhatsArea');
    var btn = document.getElementById('btnCopiarWhats');
    if (!textarea) return;

    navigator.clipboard.writeText(textarea.value).then(function() {
        var originalText = btn.innerHTML;
        btn.innerHTML = '✓ Copiado com Sucesso!';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-primary');
        }, 2200);
    }).catch(function() {
        textarea.select();
        document.execCommand('copy');
        alert('Texto copiado para a área de transferência!');
    });
}
</script>
