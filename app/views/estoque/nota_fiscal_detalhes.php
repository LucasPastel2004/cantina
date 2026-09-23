<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Detalhamento e Itens Mapeados de NF-e
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$itens = $nota['itens'] ?? [];
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/></svg>
                    Voltar às Notas Fiscais
                </a>
            </div>
            <h1 class="h5 fw-bold text-slate-900 mb-0">Detalhamento da NF-e nº <?php echo htmlspecialchars($nota['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nota['id']; ?>/download" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                Baixar XML Original
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <!-- Metadados da Nota Fiscal -->
    <div class="card-enterprise p-3 mb-3">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Fornecedor / Emitente</span>
                <span class="fw-bold text-slate-900 d-block fs-6"><?php echo htmlspecialchars($nota['razao_social_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-secondary small font-tabular">CNPJ: <?php echo htmlspecialchars($nota['cnpj_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Número da Nota</span>
                <span class="fw-bold text-slate-900 font-tabular fs-6"><?php echo htmlspecialchars($nota['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Data de Emissão</span>
                <span class="fw-semibold text-slate-900 font-tabular"><?php echo date('d/m/Y H:i', strtotime($nota['data_emissao'])); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Valor Total</span>
                <span class="fw-bold text-slate-900 font-tabular fs-6">R$ <?php echo number_format($nota['valor_total'], 2, ',', '.'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-muted d-block small" style="font-size: 11px; text-transform: uppercase; font-weight: 600;">Importado Por</span>
                <span class="fw-semibold text-slate-800 small d-block"><?php echo htmlspecialchars($nota['operador_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-muted font-tabular" style="font-size: 11px;"><?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?></span>
            </div>
            <div class="col-12 pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">Chave de Acesso da NF-e:</span>
                    <code class="code-tag font-tabular" style="font-size: 11px;"><?php echo htmlspecialchars($nota['chave_nfe'], ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Itens Convertidos e Abastecidos -->
    <div class="card-enterprise mb-3">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="card-enterprise-title">Itens Faturados & Memória de Fator de Conversão</span>
            <span class="text-muted small"><?php echo count($itens); ?> itens processados</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 100px;">Cód. Forn.</th>
                        <th>Descrição no XML do Fornecedor</th>
                        <th style="width: 70px;" class="text-center">Unid.</th>
                        <th style="width: 90px;" class="text-end">Qtd NF</th>
                        <th style="width: 110px;" class="text-end">Valor Un.</th>
                        <th>Produto Interno Vinculado</th>
                        <th style="width: 120px;" class="text-center">Fator Multiplicador</th>
                        <th style="width: 130px;" class="text-end">Qtd no Estoque</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalUnidades = 0;
                    foreach ($itens as $item): 
                        $totalUnidades += (int)$item['quantidade_convertida'];
                    ?>
                        <tr>
                            <td>
                                <code class="code-tag"><?php echo htmlspecialchars($item['codigo_produto_fornecedor'], ENT_QUOTES, 'UTF-8'); ?></code>
                            </td>
                            <td>
                                <span class="fw-semibold text-slate-900 small d-block"><?php echo htmlspecialchars($item['descricao_fornecedor'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td class="text-center font-tabular small text-muted">
                                <?php echo htmlspecialchars($item['unidade_fornecedor'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-end font-tabular">
                                <?php echo number_format($item['quantidade_fornecedor'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular text-muted small">
                                R$ <?php echo number_format($item['valor_unitario_fornecedor'], 2, ',', '.'); ?>
                            </td>
                            <td>
                                <span class="fw-semibold text-slate-900 small d-block"><?php echo htmlspecialchars($item['produto_descricao'] ?? 'Produto excluído', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="text-muted small">Cód: <?php echo htmlspecialchars($item['produto_codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars($item['categoria_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>)</span>
                            </td>
                            <td class="text-center font-tabular">
                                <span class="badge bg-secondary-soft text-slate-800 fw-semibold" style="font-size: 11px;">
                                    <?php echo (int)$item['fator_conversao']; ?>x un
                                </span>
                            </td>
                            <td class="text-end font-tabular text-success fw-bold">
                                +<?php echo (int)$item['quantidade_convertida']; ?> un
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="7" class="text-end fw-semibold text-muted small">
                            Total Geral de Unidades Abastecidas:
                        </td>
                        <td class="text-end font-tabular fs-6 fw-bold text-success">
                            +<?php echo number_format($totalUnidades, 0, ',', '.'); ?> un
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
