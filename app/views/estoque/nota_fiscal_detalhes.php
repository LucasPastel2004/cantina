<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Detalhamento e Itens Mapeados de NF-e
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md (Anti-Slop Enterprise Edition)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$nota = $nota ?? [];
$itens = $nota['itens'] ?? [];

$chaveNfe = (string)($nota['chave_nfe'] ?? '');
$chaveFormatada = trim(chunk_split($chaveNfe, 4, ' '));
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Voltar às Notas Fiscais
                </a>
            </div>
            <h1 class="h5 fw-bold text-dark mb-0">Detalhamento da NF-e nº <?php echo htmlspecialchars($nota['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="text-secondary text-microcopy mb-0">Memória de cálculo, conversão de embalagens e histórico fiscal do lote recebido.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1" id="btnAbrirModalXmlDetalhes">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                    <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                </svg>
                Visualizar XML
            </button>
            <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nota['id']; ?>/download" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                </svg>
                Baixar XML Original
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <!-- Metadados da Nota Fiscal em Bento Grid -->
    <div class="card-enterprise p-3 mb-3">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <span class="text-secondary font-11 font-semibold text-uppercase d-block mb-1">Fornecedor / Emitente</span>
                <span class="fw-bold text-dark d-block font-15"><?php echo htmlspecialchars($nota['razao_social_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-secondary font-12 font-tabular">CNPJ: <?php echo htmlspecialchars($nota['cnpj_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-secondary font-11 font-semibold text-uppercase d-block mb-1">Número da Nota</span>
                <span class="fw-bold text-dark font-tabular font-15">Nº <?php echo htmlspecialchars($nota['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-secondary font-11 font-semibold text-uppercase d-block mb-1">Data de Emissão</span>
                <span class="fw-semibold text-dark font-tabular font-13"><?php echo date('d/m/Y H:i', strtotime($nota['data_emissao'])); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-secondary font-11 font-semibold text-uppercase d-block mb-1">Valor Total Faturado</span>
                <span class="fw-bold text-dark font-tabular font-15">R$ <?php echo number_format($nota['valor_total'], 2, ',', '.'); ?></span>
            </div>
            <div class="col-6 col-md-2">
                <span class="text-secondary font-11 font-semibold text-uppercase d-block mb-1">Importado Por</span>
                <span class="fw-semibold text-dark font-12 d-block"><?php echo htmlspecialchars($nota['operador_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="text-muted font-tabular font-11"><?php echo date('d/m/Y H:i', strtotime($nota['created_at'])); ?></span>
            </div>
            <div class="col-12 pt-2 border-top">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="text-secondary font-11 font-semibold text-uppercase">Chave de Acesso da NF-e:</span>
                        <code class="code-tag font-tabular font-12" id="textoChaveNfe" title="<?php echo htmlspecialchars($chaveNfe, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($chaveFormatada, ENT_QUOTES, 'UTF-8'); ?>
                        </code>
                    </div>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btnCopiarChave" data-chave="<?php echo htmlspecialchars($chaveNfe, ENT_QUOTES, 'UTF-8'); ?>">
                        📋 Copiar Chave
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Itens Convertidos e Abastecidos -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold text-dark">Itens Faturados & Memória de Fator de Conversão</span>
            <span class="text-microcopy"><?php echo count($itens); ?> itens processados</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 110px;">Cód. Forn.</th>
                        <th>Descrição no XML do Fornecedor</th>
                        <th style="width: 80px;" class="text-center">Unid.</th>
                        <th style="width: 100px;" class="text-end">Qtd NF</th>
                        <th style="width: 120px;" class="text-end">Valor Un.</th>
                        <th>Produto Interno Vinculado</th>
                        <th style="width: 140px;" class="text-center">Fator Multiplicador</th>
                        <th style="width: 140px;" class="text-end">Qtd no Estoque</th>
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
                                <code class="code-tag font-tabular"><?php echo htmlspecialchars($item['codigo_produto_fornecedor'], ENT_QUOTES, 'UTF-8'); ?></code>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark font-13 d-block"><?php echo htmlspecialchars($item['descricao_fornecedor'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                            <td class="text-center font-tabular font-12 text-secondary">
                                <?php echo htmlspecialchars($item['unidade_fornecedor'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-end font-tabular font-13">
                                <?php echo number_format($item['quantidade_fornecedor'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-end font-tabular text-secondary font-12">
                                R$ <?php echo number_format($item['valor_unitario_fornecedor'], 2, ',', '.'); ?>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark font-13 d-block"><?php echo htmlspecialchars($item['produto_descricao'] ?? 'Produto não localizado', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="text-muted font-11">
                                    Cód: <code class="code-tag font-tabular" style="font-size: 10.5px;"><?php echo htmlspecialchars($item['produto_codigo_interno'] ?? '', ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php if (!empty($item['categoria_nome'])): ?>
                                        <span class="ms-1">(<?php echo htmlspecialchars($item['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="text-center font-tabular">
                                <span class="badge bg-white text-dark border font-tabular font-12" style="border-color: #cbd5e1 !important;">
                                    <?php echo (int)$item['fator_conversao']; ?>x un
                                </span>
                            </td>
                            <td class="text-end font-tabular text-success fw-bold font-13">
                                +<?php echo (int)$item['quantidade_convertida']; ?> un
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <tr>
                        <td colspan="7" class="text-end fw-semibold text-secondary font-12">
                            Total Geral de Unidades Abastecidas no Estoque Físico:
                        </td>
                        <td class="text-end font-tabular font-15 fw-bold text-success">
                            +<?php echo number_format($totalUnidades, 0, ',', '.'); ?> un
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Visualização do XML da Nota -->
<div class="modal fade" id="modalVisualizadorXmlDetalhes" tabindex="-1" aria-labelledby="modalVisualizadorXmlDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content card-enterprise" style="border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header card-enterprise-header d-flex justify-content-between align-items-center py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-weight-bold text-dark" id="modalVisualizadorXmlDetalhesLabel">Arquivo XML — NF-e nº <?php echo htmlspecialchars($nota['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="badge bg-white text-dark border font-tabular" style="font-size: 11px;">Chave: ...<?php echo substr($chaveNfe, -8); ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btnCopiarXmlDetalhes">
                        📋 Copiar XML
                    </button>
                    <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nota['id']; ?>/xml" target="_blank" class="btn btn-enterprise btn-secondary btn-sm">
                        ↗ Abrir em Nova Aba
                    </a>
                    <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nota['id']; ?>/download" class="btn btn-enterprise btn-primary btn-sm">
                        ⬇ Baixar Arquivo
                    </a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-light">
                <div id="xmlLoadingStateDetalhes" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando XML...</span>
                    </div>
                    <p class="text-secondary font-12 mt-2 mb-0">Carregando arquivo fiscal protegido...</p>
                </div>
                <pre id="xmlContentPreDetalhes" class="p-3 bg-white border rounded font-12 text-dark d-none font-tabular" style="max-height: 520px; overflow: auto; white-space: pre; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; line-height: 1.4;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copiar Chave de 44 dígitos
    const btnCopiarChave = document.getElementById('btnCopiarChave');
    if (btnCopiarChave) {
        btnCopiarChave.addEventListener('click', function() {
            const chave = this.getAttribute('data-chave');
            if (!chave) return;
            navigator.clipboard.writeText(chave).then(function() {
                const txtOriginal = btnCopiarChave.textContent;
                btnCopiarChave.textContent = '✓ Chave Copiada!';
                setTimeout(function() { btnCopiarChave.textContent = txtOriginal; }, 2000);
            });
        });
    }

    // Modal de Visualização de XML
    const modalEl = document.getElementById('modalVisualizadorXmlDetalhes');
    let modalBs = null;
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalBs = new bootstrap.Modal(modalEl);
    }

    const btnAbrirModal = document.getElementById('btnAbrirModalXmlDetalhes');
    const xmlPre = document.getElementById('xmlContentPreDetalhes');
    const xmlLoading = document.getElementById('xmlLoadingStateDetalhes');
    const btnCopiarXml = document.getElementById('btnCopiarXmlDetalhes');

    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', function() {
            xmlPre.classList.add('d-none');
            xmlLoading.classList.remove('d-none');
            xmlPre.textContent = '';

            if (modalBs) modalBs.show();

            fetch('<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nota['id']; ?>/xml?format=json', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                xmlLoading.classList.add('d-none');
                xmlPre.classList.remove('d-none');
                if (data && data.xml) {
                    xmlPre.textContent = data.xml;
                } else {
                    xmlPre.textContent = 'Não foi possível carregar o conteúdo do arquivo XML.';
                }
            })
            .catch(err => {
                xmlLoading.classList.add('d-none');
                xmlPre.classList.remove('d-none');
                xmlPre.textContent = 'Erro ao requisitar arquivo XML: ' + err.message;
            });
        });
    }

    if (btnCopiarXml) {
        btnCopiarXml.addEventListener('click', function() {
            if (!xmlPre.textContent) return;
            navigator.clipboard.writeText(xmlPre.textContent).then(function() {
                const txt = btnCopiarXml.textContent;
                btnCopiarXml.textContent = '✓ Copiado!';
                setTimeout(function() { btnCopiarXml.textContent = txt; }, 2000);
            });
        });
    }
});
</script>
