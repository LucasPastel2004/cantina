<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Livro Caixa Diário
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e padrão de ouro de carga sob demanda
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$fimSemana = date('Y-m-d', strtotime('friday this week'));
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');
$inicioMesAnterior = date('Y-m-d', strtotime('first day of previous month'));
$fimMesAnterior = date('Y-m-d', strtotime('last day of previous month'));

$dadosLivroCaixa = $dadosLivroCaixa ?? null;
$jaFiltrado = $jaFiltrado ?? false;
$linhas = $dadosLivroCaixa['linhas'] ?? [];
$totais = $dadosLivroCaixa['totais'] ?? [];
$saldoAnterior = (float)($dadosLivroCaixa['saldo_anterior'] ?? 0.0);
$saldoFinal = (float)($dadosLivroCaixa['saldo_final'] ?? 0.0);
?>

<div class="container-fluid p-0">

    <!-- Topbar Institucional do Livro Caixa -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="text-secondary text-microcopy text-uppercase font-weight-bold">Módulo Financeiro & Escrituração Contábil</span>
            </div>
            <h1 class="h4 font-weight-bold mb-0 text-dark">Livro Caixa Diário</h1>
            <p class="text-secondary text-microcopy mb-0 mt-1">
                Registro cronológico detalhado de todas as entradas financeiras (Pix Online, PDV Pix e Dinheiro) e saídas (pagamentos a fornecedores e despesas) com saldo progressivo.
            </p>
        </div>

        <!-- Ações de Exportação e Emissão -->
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo $baseUrl; ?>/financeiro/livro-caixa/csv?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
               class="btn btn-sm btn-enterprise btn-secondary d-flex align-items-center gap-2"
               title="Exportar dados do Livro Caixa para planilha Microsoft Excel">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.5 12.5A.5.5 0 0 1 5 12h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
                <span>Exportar Planilha (CSV)</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/financeiro/livro-caixa/imprimir?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
               target="_blank"
               class="btn btn-sm btn-enterprise btn-primary d-inline-flex align-items-center gap-2" 
               title="Imprimir ou Salvar documento oficial em PDF no padrão A4">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                <span>Imprimir / Salvar PDF (A4)</span>
            </a>
        </div>
    </div>

    <!-- Filtro de Período com Carga Sob Demanda -->
    <div class="card card-enterprise mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <div class="card-body p-3">
            <form method="GET" action="<?php echo $baseUrl; ?>/financeiro/livro-caixa" id="formFiltroLivroCaixa">
                <input type="hidden" name="filtrar" value="1">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                    
                    <!-- Intervalo de Datas e Botões de Filtragem -->
                    <div class="d-flex flex-wrap align-items-end gap-2">
                        <div>
                            <label for="dataInicio" class="form-label text-microcopy text-secondary text-uppercase fw-semibold mb-1 d-block">Data Inicial</label>
                            <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div>
                            <label for="dataFim" class="form-label text-microcopy text-secondary text-uppercase fw-semibold mb-1 d-block">Data Final</label>
                            <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-enterprise btn-primary d-inline-flex align-items-center gap-1.5" title="Filtrar movimentações do Livro Caixa">
                                <span>Filtrar</span>
                            </button>
                            <?php if ($jaFiltrado): ?>
                                <a href="<?php echo $baseUrl; ?>/financeiro/livro-caixa" class="btn btn-sm btn-enterprise btn-secondary" title="Limpar Filtros e Ocultar Tabela">
                                    Limpar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Atalhos Rápidos de Seleção (Padrão Corporativo Neutro) -->
                    <div>
                        <span class="text-microcopy text-secondary text-uppercase fw-semibold d-block mb-1">Atalhos de Período</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-enterprise btn-secondary" onclick="definirDatas(event, '<?php echo $hoje; ?>', '<?php echo $hoje; ?>')">Hoje</button>
                            <button type="button" class="btn btn-enterprise btn-secondary" onclick="definirDatas(event, '<?php echo $ontem; ?>', '<?php echo $ontem; ?>')">Ontem</button>
                            <button type="button" class="btn btn-enterprise btn-secondary" onclick="definirDatas(event, '<?php echo $inicioSemana; ?>', '<?php echo $fimSemana; ?>')">Esta Semana</button>
                            <button type="button" class="btn btn-enterprise btn-secondary" onclick="definirDatas(event, '<?php echo $inicioMes; ?>', '<?php echo $fimMes; ?>')">Este Mês</button>
                            <button type="button" class="btn btn-enterprise btn-secondary" onclick="definirDatas(event, '<?php echo $inicioMesAnterior; ?>', '<?php echo $fimMesAnterior; ?>')">Mês Anterior</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <?php if (!$jaFiltrado): ?>
        <!-- Estado Vazio Inicial (Padrão de Carga Sob Demanda) -->
        <?php 
        $emptyMessage = 'Aguardando filtragem para exibição';
        $emptyDescription = 'Selecione o período desejado nos campos acima e clique em "Filtrar" para apurar as entradas, saídas e saldos progressivos do Livro Caixa, ou em "Imprimir / Salvar PDF" para emissão direta.';
        require __DIR__ . '/../partials/empty_state.php'; 
        ?>
    <?php else: ?>

        <!-- Stat-Strip: Síntese Financeira do Livro Caixa -->
        <div class="stat-strip mb-4">
            <div class="stat-strip-item">
                <span class="stat-strip-label">Saldo Inicial / Anterior</span>
                <span class="stat-strip-value font-tabular <?php echo $saldoAnterior < 0 ? 'text-danger' : 'text-dark'; ?>">
                    R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">Posição antes de <?php echo date('d/m/Y', strtotime($dataInicio)); ?></span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">(+) Total de Entradas (Créditos)</span>
                <span class="stat-strip-value font-tabular text-success">
                    + R$ <?php echo number_format((float)($totais['credito'] ?? 0), 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">Recargas Pix/Dinheiro e Vendas PDV</span>
            </div>
            <div class="stat-strip-item">
                <span class="stat-strip-label">(-) Total de Saídas (Débitos)</span>
                <span class="stat-strip-value font-tabular text-danger">
                    - R$ <?php echo number_format((float)($totais['debito'] ?? 0), 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">
                    Pix: R$ <?php echo number_format((float)($totais['pagamento_pix'] ?? 0), 2, ',', '.'); ?> • Dinheiro: R$ <?php echo number_format((float)($totais['pagamento_dinheiro'] ?? 0), 2, ',', '.'); ?>
                </span>
            </div>
            <div class="stat-strip-item" style="border-right: none;">
                <span class="stat-strip-label">(=) Saldo Final Acumulado</span>
                <span class="stat-strip-value font-tabular <?php echo $saldoFinal < 0 ? 'text-danger' : 'text-primary'; ?>" style="font-size: 19px; font-weight: 800;">
                    R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?>
                </span>
                <span class="text-secondary text-microcopy mt-1 font-tabular">Em <?php echo date('d/m/Y', strtotime($dataFim)); ?></span>
            </div>
        </div>

        <!-- Tabela Estrutural do Livro Caixa (13 Colunas) -->
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark">Movimentações do Livro Caixa</span>
                    <span class="badge-count"><?php echo count($linhas); ?> lançamentos</span>
                </div>
                <div class="text-secondary font-microcopy">
                    Período: <strong><?php echo htmlspecialchars($dadosLivroCaixa['periodo_oficial'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <div class="card-enterprise-body p-0">
                <div class="table-responsive">
                    <table class="table-enterprise mb-0" style="font-size: 12px;">
                        <thead>
                            <tr style="background-color: #f8fafc;">
                                <th class="cell-center" style="width: 75px;">Data</th>
                                <th class="cell-left" style="width: 70px;">Dia</th>
                                <th class="cell-left" style="min-width: 170px;">Descrição / Histórico</th>
                                <th class="cell-right" style="width: 90px; color: #1e293b;">Pix Online</th>
                                <th class="cell-right" style="width: 85px; color: #1e293b;">Rec. PDV Pix</th>
                                <th class="cell-right" style="width: 85px; color: #1e293b;">Rec. DH PDV</th>
                                <th class="cell-right" style="width: 85px; color: #1e293b;">Venda Pix</th>
                                <th class="cell-right" style="width: 85px; color: #1e293b;">Venda DH</th>
                                <th class="cell-right" style="width: 90px; color: #b91c1c;">Pgto Pix</th>
                                <th class="cell-right" style="width: 90px; color: #b91c1c;">Pgto DH</th>
                                <th class="cell-right" style="width: 95px; background-color: #f1f5f9; font-weight: 700; color: #15803d;">Crédito</th>
                                <th class="cell-right" style="width: 95px; background-color: #fef2f2; font-weight: 700; color: #b91c1c;">Débito</th>
                                <th class="cell-right" style="width: 105px; background-color: #f8fafc; font-weight: 800; color: #0f172a;">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Linha de Saldo Inicial -->
                            <tr style="background-color: #f8fafc; font-weight: 600;">
                                <td class="cell-center font-tabular text-secondary">—</td>
                                <td class="cell-left text-secondary">—</td>
                                <td class="cell-left text-dark">
                                    <strong>SALDO INICIAL ANTERIOR</strong> 
                                    <span class="text-secondary font-microcopy">(Posição em <?php echo date('d/m/Y', strtotime($dataInicio)); ?>)</span>
                                </td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right text-muted font-tabular">—</td>
                                <td class="cell-right font-tabular <?php echo $saldoAnterior < 0 ? 'text-danger' : 'text-dark'; ?>" style="font-size: 13px;">
                                    <strong>R$ <?php echo number_format($saldoAnterior, 2, ',', '.'); ?></strong>
                                </td>
                            </tr>

                            <?php if (empty($linhas)): ?>
                                <tr>
                                    <td colspan="13" class="text-center py-4 text-muted">
                                        Nenhuma movimentação registrada no período informado.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($linhas as $l): ?>
                                    <tr style="<?php echo ($l['tipo'] === 'debito_recibo' || $l['tipo'] === 'debito_despesa') ? 'background-color: #fffaf0;' : ''; ?>">
                                        <td class="cell-center font-tabular" style="font-size: 11.5px;"><?php echo htmlspecialchars($l['data'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="cell-left text-secondary" style="font-size: 11px;"><?php echo htmlspecialchars($l['dia_semana'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="cell-left">
                                            <?php if ($l['tipo'] === 'credito'): ?>
                                                <span class="font-weight-semibold text-dark"><?php echo htmlspecialchars($l['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php elseif ($l['tipo'] === 'debito_recibo'): ?>
                                                <span class="text-danger font-weight-semibold">🧾 <?php echo htmlspecialchars($l['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php elseif ($l['tipo'] === 'debito_despesa'): ?>
                                                <span class="text-danger font-weight-medium">💳 <?php echo htmlspecialchars($l['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted font-microcopy"><?php echo htmlspecialchars($l['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <!-- Entradas -->
                                        <td class="cell-right font-tabular"><?php echo $l['recargas_pix_online'] > 0 ? number_format($l['recargas_pix_online'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="cell-right font-tabular"><?php echo $l['recargas_pix_pdv'] > 0 ? number_format($l['recargas_pix_pdv'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="cell-right font-tabular"><?php echo $l['recargas_dinheiro'] > 0 ? number_format($l['recargas_dinheiro'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="cell-right font-tabular"><?php echo $l['vendas_pix_pdv'] > 0 ? number_format($l['vendas_pix_pdv'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="cell-right font-tabular"><?php echo $l['vendas_dinheiro_pdv'] > 0 ? number_format($l['vendas_dinheiro_pdv'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?></td>

                                        <!-- Saídas -->
                                        <td class="cell-right font-tabular <?php echo $l['pagamento_pix'] > 0 ? 'text-danger font-weight-medium' : ''; ?>">
                                            <?php echo $l['pagamento_pix'] > 0 ? number_format($l['pagamento_pix'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?>
                                        </td>
                                        <td class="cell-right font-tabular <?php echo $l['pagamento_dinheiro'] > 0 ? 'text-danger font-weight-medium' : ''; ?>">
                                            <?php echo $l['pagamento_dinheiro'] > 0 ? number_format($l['pagamento_dinheiro'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?>
                                        </td>

                                        <!-- Crédito / Débito / Saldo -->
                                        <td class="cell-right font-tabular" style="background-color: #f8fafc; font-weight: 700; color: #15803d;">
                                            <?php echo $l['credito'] > 0 ? '+ ' . number_format($l['credito'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?>
                                        </td>
                                        <td class="cell-right font-tabular" style="background-color: #fff1f2; font-weight: 700; color: #b91c1c;">
                                            <?php echo $l['debito'] > 0 ? '- ' . number_format($l['debito'], 2, ',', '.') : '<span class="text-muted">—</span>'; ?>
                                        </td>
                                        <td class="cell-right font-tabular" style="background-color: #f8fafc; font-weight: 700; font-size: 12.5px; color: <?php echo $l['saldo'] < 0 ? '#b91c1c' : '#0f172a'; ?>;">
                                            R$ <?php echo number_format($l['saldo'], 2, ',', '.'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="border-top-2">
                            <tr style="background-color: #f1f5f9; font-weight: 700; border-top: 2px solid #cbd5e1;">
                                <td colspan="3" class="cell-left text-uppercase font-weight-bold">
                                    TOTAIS CONSOLIDADOS DO PERÍODO
                                </td>
                                <td class="cell-right font-tabular"><?php echo number_format($totais['recargas_pix_online'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular"><?php echo number_format($totais['recargas_pix_pdv'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular"><?php echo number_format($totais['recargas_dinheiro'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular"><?php echo number_format($totais['vendas_pix_pdv'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular"><?php echo number_format($totais['vendas_dinheiro_pdv'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular text-danger"><?php echo number_format($totais['pagamento_pix'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular text-danger"><?php echo number_format($totais['pagamento_dinheiro'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="cell-right font-tabular text-success" style="font-size: 13px;">
                                    + R$ <?php echo number_format($totais['credito'] ?? 0, 2, ',', '.'); ?>
                                </td>
                                <td class="cell-right font-tabular text-danger" style="font-size: 13px;">
                                    - R$ <?php echo number_format($totais['debito'] ?? 0, 2, ',', '.'); ?>
                                </td>
                                <td class="cell-right font-tabular <?php echo $saldoFinal < 0 ? 'text-danger' : 'text-primary'; ?>" style="font-size: 14px; font-weight: 800;">
                                    R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
function definirDatas(e, inicio, fim) {
    if (e) e.preventDefault();
    document.getElementById('dataInicio').value = inicio;
    document.getElementById('dataFim').value = fim;
}
</script>
