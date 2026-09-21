<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Painel de Corte do Turno e Gestão de Fornecedores
 * 
 * Conforme docs/FSD.md (Área 2, Tela 3.1, Fluxo 5) e docs/DESIGN.md (Anti-Slop Enterprise)
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
$statusRecebimento = $dadosCorte['status_recebimento'] ?? null;
$textoWhatsApp = $textoWhatsApp ?? '';

$dataFmt = date('d/m/Y', strtotime($dataConsumo));
$turnoNome = ($turnoConsumo === 'matutino') ? 'Matutino' : 'Vespertino';
$horaCorte = ($turnoConsumo === 'matutino') ? '08:00' : '13:30';
$corteAberto = $statusCorte['pode_alterar'] ?? false;
?>

<!-- Alertas Flash do Sistema -->
<?php require __DIR__ . '/../partials/alert.php'; ?>

<!-- Topo da Página com Título e Ações Rápidas -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Painel de Corte do Turno</h1>
            <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                <span style="color: <?php echo $corteAberto ? '#d97706' : '#64748b'; ?>;">●</span> <?php echo $corteAberto ? 'Agendamento Aberto' : 'Corte Fechado'; ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Consolidação matemática de salgados para encomenda junto ao fornecedor parceiro.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo $baseUrl; ?>/operacional/corte/pdf?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           class="btn btn-enterprise btn-secondary" 
           title="Download do Relatório Consolidado A4 Timbrado em PDF">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1" style="vertical-align: -2px;">
                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg>
            Exportar PDF
        </a>

        <a href="<?php echo $baseUrl; ?>/operacional/corte/imprimir?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           class="btn btn-enterprise btn-secondary" 
           target="_blank" 
           title="Abrir versão de impressão no navegador">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1" style="vertical-align: -2px;">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir A4
        </a>

        <a href="<?php echo $baseUrl; ?>/operacional/recebimento?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           class="btn btn-enterprise btn-success" 
           title="Avançar para a tela de conferência física de entrega">
            Conferir Recebimento &rarr;
        </a>
    </div>
</div>

<!-- Barra de Filtros Operacionais (Data e Turno) -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/operacional/corte" class="row g-3 align-items-center">
            <div class="col-12 col-sm-auto">
                <label for="inputData" class="form-label mb-0 me-2 text-microcopy font-weight-bold text-secondary">DATA:</label>
                <input type="date" id="inputData" name="data" class="form-control form-control-sm d-inline-block font-tabular" 
                       style="width: 155px;" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12 col-sm-auto">
                <label for="selectTurno" class="form-label mb-0 me-2 text-microcopy font-weight-bold text-secondary">TURNO:</label>
                <select id="selectTurno" name="turno" class="form-select form-select-sm d-inline-block" style="width: 145px;">
                    <option value="matutino" <?php echo ($turnoConsumo === 'matutino') ? 'selected' : ''; ?>>Matutino (Manhã)</option>
                    <option value="vespertino" <?php echo ($turnoConsumo === 'vespertino') ? 'selected' : ''; ?>>Vespertino (Tarde)</option>
                </select>
            </div>
            <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm">
                    Atualizar Painel
                </button>
            </div>
            <div class="col-12 col-sm text-sm-end text-muted text-microcopy">
                Corte operacional pontual: <strong><?php echo $horaCorte; ?></strong>
                <?php if ($corteAberto && isset($statusCorte['minutos_restantes'])): ?>
                    <span class="text-amber ms-1">(restam aprox. <?php echo $statusCorte['minutos_restantes']; ?> min)</span>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip Corporativo (Métricas Consolidadas) -->
<?php
$statusFornecLabel = 'Aguardando Entrega';
if ($statusRecebimento) {
    $statusFornecLabel = 'Recebido em ' . $statusRecebimento['data_recebimento_fmt'];
} elseif ($corteAberto) {
    $statusFornecLabel = 'Agendamento em Andamento';
}

$stats = [
    ['label' => 'TURNO / CORTE', 'value' => $turnoNome . ' (' . $dataFmt . ')', 'tabular' => false],
    ['label' => 'TOTAL DE SALGADOS', 'value' => $totalSalgados . ' un', 'tabular' => true],
    ['label' => 'TOTAL DE ITENS', 'value' => $totalPecas . ' peças', 'tabular' => true],
    ['label' => 'DEMANDA ATENDIDA', 'value' => $totalPedidos . ' pedidos (' . $totalAlunos . ' alunos)', 'tabular' => true],
    ['label' => 'STATUS DO FORNECEDOR', 'value' => $statusFornecLabel, 'tabular' => false],
];
require __DIR__ . '/../partials/stat_strip.php';
?>

<!-- Tabela de Consolidação de Salgados -->
<div class="card-enterprise mt-4">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <div>
            <span class="font-weight-bold text-dark">Demandas de Produção por Salgado</span>
            <span class="text-secondary text-microcopy ms-2">(<?php echo count($itens); ?> produtos solicitados)</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($statusRecebimento): ?>
                <span class="status-indicator status-active">
                    <span class="bullet">●</span> Remessa Recebida por <?php echo htmlspecialchars($statusRecebimento['operador_nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?>
                </span>
            <?php else: ?>
                <span class="text-muted text-microcopy">
                    Aguardando conferência física
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-enterprise align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 120px;" class="cell-center">Código</th>
                    <th>Produto / Salgado</th>
                    <th style="width: 180px;">Categoria</th>
                    <th style="width: 150px;">Classificação</th>
                    <th style="width: 160px;" class="cell-right">Qtd Encomendada</th>
                    <th style="width: 140px;" class="cell-right">Preço Unit.</th>
                    <th style="width: 150px;" class="cell-right">Subtotal Estimado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($itens)): ?>
                    <tr>
                        <td colspan="7" class="p-0">
                            <?php
                            $emptyTitle = 'Não há nada para mostrar aqui';
                            $emptyDescription = 'Nenhum pedido de lanche ou salgado foi registrado para o turno ' . $turnoNome . ' em ' . $dataFmt . '.';
                            require __DIR__ . '/../partials/empty_state.php';
                            ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td class="cell-center">
                                <code class="code-tag"><?php echo htmlspecialchars($item['codigo_interno'] ?? ('PRD-' . $item['produto_id']), ENT_QUOTES, 'UTF-8'); ?></code>
                            </td>
                            <td>
                                <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($item['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td class="text-secondary">
                                <?php echo htmlspecialchars($item['categoria_nome'] ?? 'Geral', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <?php if (($item['tipo_produto'] ?? '') === 'salgado_diario'): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 11px;">Salgado Diário</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border" style="font-size: 11px;">Industrializado</span>
                                <?php endif; ?>
                            </td>
                            <td class="cell-right font-tabular font-weight-bold" style="font-size: 14px; color: #0f172a;">
                                <?php echo (int)($item['quantidade_total'] ?? 0); ?> un
                            </td>
                            <td class="cell-right font-tabular text-secondary">
                                R$ <?php echo number_format((float)($item['preco_venda'] ?? 0), 2, ',', '.'); ?>
                            </td>
                            <td class="cell-right font-tabular font-weight-bold text-dark">
                                R$ <?php echo number_format((float)($item['valor_total_estimado'] ?? 0), 2, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($itens)): ?>
                <tfoot>
                    <tr style="background-color: #f8fafc; border-top: 2px solid #cbd5e1;">
                        <td colspan="4" class="text-end font-weight-bold text-dark" style="font-size: 13px;">
                            TOTAL DO CORTE:
                        </td>
                        <td class="cell-right font-tabular font-weight-bold text-dark" style="font-size: 15px; color: #0f172a !important;">
                            <?php echo $totalPecas; ?> un
                        </td>
                        <td class="text-end font-weight-bold text-secondary text-microcopy">
                            Valor Faturado:
                        </td>
                        <td class="cell-right font-tabular font-weight-bold text-dark" style="font-size: 14px;">
                            R$ <?php echo number_format($totalValor, 2, ',', '.'); ?>
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- Card de Pré-visualização da Mensagem de WhatsApp -->
<div class="card-enterprise mt-4">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <span class="font-weight-bold text-dark">Mensagem Estruturada para Fornecedor (WhatsApp)</span>
        <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btnCopiarWhatsCard" onclick="copiarPedidoWhatsApp()">
            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor" class="me-1" style="vertical-align: -1px;">
                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592z"/>
            </svg>
            <span id="labelCopiarWhatsCard">Copiar Texto</span>
        </button>
    </div>
    <div class="card-enterprise-body p-3">
        <textarea id="textareaWhatsApp" class="form-control font-monospace" rows="14" readonly 
                  style="background-color: #f8fafc; font-size: 13px; line-height: 1.6; height: 320px; min-height: 280px; padding: 12px 14px; border: 1px solid var(--border-default); border-radius: var(--radius-sm); resize: vertical;"><?php echo htmlspecialchars($textoWhatsApp, ENT_QUOTES, 'UTF-8'); ?></textarea>
        <p class="text-microcopy text-muted mt-2 mb-0">
            Você pode copiar este texto estruturado e colá-lo diretamente na conversa com o fornecedor parceiro no WhatsApp Web ou aplicativo.
        </p>
    </div>
</div>

<!-- Script de Cópia Ágil para Área de Transferência -->
<script>
function copiarPedidoWhatsApp() {
    const textarea = document.getElementById('textareaWhatsApp');
    const label = document.getElementById('labelCopiarWhatsCard');
    const btn = document.getElementById('btnCopiarWhatsCard');

    if (!textarea) return;

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textarea.value).then(feedbackSucesso).catch(fallbackCopiar);
    } else {
        fallbackCopiar();
    }

    function feedbackSucesso() {
        if (label) label.textContent = '✓ Texto Copiado!';
        if (btn) btn.classList.add('border-success');
        setTimeout(() => {
            if (label) label.textContent = 'Copiar Texto';
            if (btn) btn.classList.remove('border-success');
        }, 3000);
    }

    function fallbackCopiar() {
        textarea.select();
        try {
            document.execCommand('copy');
            feedbackSucesso();
        } catch (err) {
            alert('Não foi possível copiar automaticamente. Selecione e copie o texto manualmente.');
        }
    }
}
</script>
