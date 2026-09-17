<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View de Homologação: Painel Operacional da Cantina
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
?>

<!-- Faixa Contínua de Métricas (Stat Strip) -->
<?php 
if (!empty($statItems)) {
    require __DIR__ . '/../partials/stat_strip.php';
}
?>

<!-- Card Operacional: Lista de Produção / Corte do Turno -->
<div class="card-enterprise">
    <div class="card-enterprise-header">
        <div class="d-flex align-items-center gap-2">
            <span class="h2-title mb-0">Demanda Consolidada de Salgados</span>
            <span class="text-microcopy">&bull; Trava de corte pontual das 08:00</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="alert('Demanda copiada para área de transferência!')">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M0 6.75C0 5.784.784 5 1.75 5h1.5a.75.75 0 0 1 0 1.5h-1.5a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h7.5a.25.25 0 0 0 .25-.25v-1.5a.75.75 0 0 1 1.5 0v1.5A1.75 1.75 0 0 1 9.25 16h-7.5A1.75 1.75 0 0 1 0 14.25Z"/><path d="M5 1.75C5 .784 5.784 0 6.75 0h7.5C15.216 0 16 .784 16 1.75v7.5A1.75 1.75 0 0 1 14.25 11h-7.5A1.75 1.75 0 0 1 5 9.25Zm1.75-.25a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h7.5a.25.25 0 0 0 .25-.25v-7.5a.25.25 0 0 0-.25-.25Z"/></svg>
                Copiar para WhatsApp
            </button>
            <button type="button" class="btn btn-enterprise btn-primary btn-sm" onclick="abrirModal('modalConfirmarRecebimento')">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"/></svg>
                Confirmar Recebimento
            </button>
        </div>
    </div>

    <!-- Tabela de Alta Densidade (Data Grid) -->
    <div class="table-responsive">
        <table class="table-data-grid">
            <thead>
                <tr>
                    <th class="cell-left" style="width: 12%;">Código</th>
                    <th class="cell-left" style="width: 40%;">Produto / Salgado</th>
                    <th class="cell-left" style="width: 20%;">Categoria</th>
                    <th class="cell-right" style="width: 10%;">Qtd Turno</th>
                    <th class="cell-right" style="width: 10%;">Unitário</th>
                    <th class="cell-right" style="width: 18%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalQtd = 0;
                $totalGeral = 0;
                foreach ($demandasSalgados ?? [] as $item): 
                    $totalQtd += $item['qtd'];
                    $totalGeral += $item['total'];
                ?>
                    <tr>
                        <td class="cell-left font-tabular"><code class="code-tag"><?php echo htmlspecialchars($item['codigo'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td class="cell-left fw-semibold"><?php echo htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-left text-muted"><?php echo htmlspecialchars($item['categoria'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="cell-right font-tabular fw-bold"><?php echo $item['qtd']; ?> un</td>
                        <td class="cell-right font-tabular">R$ <?php echo number_format($item['unitario'], 2, ',', '.'); ?></td>
                        <td class="cell-right font-tabular fw-bold text-dark">R$ <?php echo number_format($item['total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="cell-left text-uppercase">Total Consolidado do Turno:</td>
                    <td class="cell-right font-tabular fw-bold"><?php echo $totalQtd; ?> un</td>
                    <td></td>
                    <td class="cell-right font-tabular fw-bold text-success" style="font-size: 14px;">
                        R$ <?php echo number_format($totalGeral, 2, ',', '.'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal Parcial de Demonstração: Confirmação de Recebimento de Mercadorias -->
<?php
$modalId = 'modalConfirmarRecebimento';
$modalTitle = 'Confirmar Recebimento de Mercadorias';
$modalBody = '
<p class="text-secondary mb-3">Ao confirmar o recebimento físico dos salgados entregues pelo fornecedor, o sistema realizará:</p>
<ul class="text-secondary small mb-3">
    <li>Liquidação simultânea da demanda de produção dos tickets impressos;</li>
    <li>Entrada imediata das unidades físicas no saldo de estoque;</li>
    <li>Disponibilização de eventuais unidades excedentes para comercialização avulsa no PDV.</li>
</ul>
<div class="alert-enterprise alert-enterprise-info mb-0">
    <div class="alert-enterprise-icon"><svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M8 1.5a6.5 6.5 0 1 0 0 13 6.5 6.5 0 0 0 0-13ZM0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm6.5-.25A.75.75 0 0 1 7.25 7h1a.75.75 0 0 1 .75.75v2.75h.25a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1 0-1.5h.25v-2h-.25a.75.75 0 0 1-.75-.75ZM8 6a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/></svg></div>
    <div class="alert-enterprise-body">Esta ação é irreversível e gera registro contábil na trilha de auditoria.</div>
</div>';
$confirmText = 'Confirmar Entrada';
$confirmClass = 'btn-success';
$confirmId = 'btnEfetivarRecebimento';
require __DIR__ . '/../partials/modal.php';
?>
