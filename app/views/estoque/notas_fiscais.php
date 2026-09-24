<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Listagem Histórica de Notas Fiscais de Entrada (NF-e)
 * 
 * Conforme docs/FSD.md (Área 5 e Módulo 6) e docs/DESIGN.md (Anti-Slop Enterprise Edition)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$notas = $notas ?? [];
$metricas = $metricas ?? ['total_notas' => 0, 'valor_acumulado' => 0.0, 'notas_mes_atual' => 0, 'valor_mes_atual' => 0.0];
$filtros = $filtros ?? ['busca' => '', 'data_inicio' => '', 'data_fim' => ''];
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/estoque" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                    </svg>
                    Voltar ao Estoque
                </a>
            </div>
            <h1 class="h5 fw-bold text-dark mb-0">Notas Fiscais de Entrada (NF-e)</h1>
            <p class="text-secondary text-microcopy mb-0">Repositório de notas fiscais importadas e histórico de abastecimento físico do estoque.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo $baseUrl; ?>/estoque/importar-xml" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                </svg>
                Nova Entrada via XML
            </a>
            <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-secondary btn-sm">
                Grade de Saldos
            </a>
        </div>
    </div>

    <!-- Stat-Strip Fiscal Calibrada -->
    <div class="stat-strip mb-3">
        <div class="stat-strip-item">
            <span class="stat-strip-label">Total de Notas</span>
            <span class="stat-strip-value font-tabular"><?php echo number_format($metricas['total_notas'], 0, ',', '.'); ?></span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Valor Total Acumulado</span>
            <span class="stat-strip-value font-tabular">R$ <?php echo number_format($metricas['valor_acumulado'], 2, ',', '.'); ?></span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Notas no Mês Atual</span>
            <span class="stat-strip-value font-tabular text-primary"><?php echo number_format($metricas['notas_mes_atual'], 0, ',', '.'); ?></span>
        </div>
        <div class="stat-strip-item" style="border-right: none;">
            <span class="stat-strip-label">Faturado no Mês</span>
            <span class="stat-strip-value font-tabular">R$ <?php echo number_format($metricas['valor_mes_atual'], 2, ',', '.'); ?></span>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card-enterprise p-3 mb-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Buscar Nota Fiscal:</label>
                <input type="text" name="busca" class="form-control form-control-sm font-12" placeholder="Número da nota, fornecedor, CNPJ ou chave de 44 dígitos..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Data Início:</label>
                <input type="date" name="data_inicio" class="form-control form-control-sm font-tabular font-12" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label font-11 font-semibold text-secondary mb-1">Data Fim:</label>
                <input type="date" name="data_fim" class="form-control form-control-sm font-tabular font-12" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-fill" title="Filtrar">Filtrar</button>
                <?php if (!empty($filtros['busca']) || !empty($filtros['data_inicio']) || !empty($filtros['data_fim'])): ?>
                    <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Notas Fiscais -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold text-dark">Documentos Fiscais Armazenados</span>
            <span class="text-microcopy">Exibindo <?php echo count($notas); ?> notas importadas</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 120px;">Número NF-e</th>
                        <th style="width: 130px;">Data Emissão</th>
                        <th>Fornecedor / Emitente</th>
                        <th style="width: 130px;" class="text-end">Valor Total</th>
                        <th style="width: 130px;" class="text-end">Unid. Inseridas</th>
                        <th style="width: 140px;">Importado Em</th>
                        <th style="width: 190px;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($notas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <em>Não há nada para mostrar aqui</em>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($notas as $nf): ?>
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark font-tabular font-14">Nº <?php echo htmlspecialchars($nf['numero_nota'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <code class="code-tag d-block mt-1 font-tabular" style="font-size: 10px;">Chave: ...<?php echo substr($nf['chave_nfe'], -8); ?></code>
                                </td>
                                <td class="font-tabular text-secondary font-12">
                                    <?php echo date('d/m/Y H:i', strtotime($nf['data_emissao'])); ?>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark d-block font-13"><?php echo htmlspecialchars($nf['razao_social_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-secondary font-11 font-tabular">CNPJ: <?php echo htmlspecialchars($nf['cnpj_emitente'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end font-tabular fw-bold text-dark font-13">
                                    R$ <?php echo number_format($nf['valor_total'], 2, ',', '.'); ?>
                                </td>
                                <td class="text-end font-tabular text-success fw-bold font-13">
                                    +<?php echo number_format($nf['total_unidades_adicionadas'], 0, ',', '.'); ?> un
                                </td>
                                <td class="font-11">
                                    <span class="text-dark font-tabular d-block"><?php echo date('d/m/Y H:i', strtotime($nf['created_at'])); ?></span>
                                    <span class="text-muted">por <?php echo htmlspecialchars($nf['operador_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nf['id']; ?>" class="btn btn-enterprise btn-secondary btn-sm" title="Ver Itens Mapeados e Detalhes">
                                            Detalhes
                                        </a>
                                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm btn-ver-xml" 
                                                data-id="<?php echo $nf['id']; ?>" 
                                                data-numero="<?php echo htmlspecialchars($nf['numero_nota'], ENT_QUOTES, 'UTF-8'); ?>"
                                                title="Visualizar Conteúdo do XML">
                                            Ver XML
                                        </button>
                                        <a href="<?php echo $baseUrl; ?>/estoque/notas-fiscais/<?php echo $nf['id']; ?>/download" class="btn btn-enterprise btn-secondary btn-sm" title="Baixar Arquivo XML">
                                            Baixar
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

<!-- Modal para Visualização do XML -->
<div class="modal fade" id="modalVisualizadorXml" tabindex="-1" aria-labelledby="modalVisualizadorXmlLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content card-enterprise" style="border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);">
            <div class="modal-header card-enterprise-header d-flex justify-content-between align-items-center py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <span class="font-weight-bold text-dark" id="modalVisualizadorXmlLabel">Conteúdo do Arquivo XML da NF-e</span>
                    <span class="badge bg-white text-dark border font-tabular" id="modalXmlNumeroBadge" style="font-size: 11px;">NF-e</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" id="btnCopiarXml">
                        📋 Copiar XML
                    </button>
                    <a href="#" target="_blank" class="btn btn-enterprise btn-secondary btn-sm" id="btnAbrirAbaXml">
                        ↗ Abrir em Nova Aba
                    </a>
                    <a href="#" class="btn btn-enterprise btn-primary btn-sm" id="btnBaixarModalXml">
                        ⬇ Baixar Arquivo
                    </a>
                    <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
            </div>
            <div class="modal-body p-3 bg-light">
                <div id="xmlLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando XML...</span>
                    </div>
                    <p class="text-secondary font-12 mt-2 mb-0">Carregando arquivo fiscal protegido...</p>
                </div>
                <pre id="xmlContentPre" class="p-3 bg-white border rounded font-12 text-dark d-none font-tabular" style="max-height: 520px; overflow: auto; white-space: pre; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; line-height: 1.4;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('modalVisualizadorXml');
    let modalBs = null;
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalBs = new bootstrap.Modal(modalEl);
    }

    const xmlPre = document.getElementById('xmlContentPre');
    const xmlLoading = document.getElementById('xmlLoadingState');
    const badgeNumero = document.getElementById('modalXmlNumeroBadge');
    const btnAbrirAba = document.getElementById('btnAbrirAbaXml');
    const btnBaixar = document.getElementById('btnBaixarModalXml');
    const btnCopiar = document.getElementById('btnCopiarXml');

    document.querySelectorAll('.btn-ver-xml').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const num = this.getAttribute('data-numero');

            badgeNumero.textContent = 'NF-e nº ' + num;
            btnAbrirAba.href = '<?php echo $baseUrl; ?>/estoque/notas-fiscais/' + id + '/xml';
            btnBaixar.href = '<?php echo $baseUrl; ?>/estoque/notas-fiscais/' + id + '/download';

            xmlPre.classList.add('d-none');
            xmlLoading.classList.remove('d-none');
            xmlPre.textContent = '';

            if (modalBs) modalBs.show();

            fetch('<?php echo $baseUrl; ?>/estoque/notas-fiscais/' + id + '/xml?format=json', {
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
    });

    if (btnCopiar) {
        btnCopiar.addEventListener('click', function() {
            if (!xmlPre.textContent) return;
            navigator.clipboard.writeText(xmlPre.textContent).then(function() {
                const txt = btnCopiar.textContent;
                btnCopiar.textContent = '✓ Copiado!';
                setTimeout(function() { btnCopiar.textContent = txt; }, 2000);
            });
        });
    }
});
</script>
