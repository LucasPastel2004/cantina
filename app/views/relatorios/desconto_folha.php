<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório de Desconto em Folha para Recursos Humanos (RH)
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.3 & Seção 22.4), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$colaboradores = $dadosFolha['colaboradores'] ?? [];
$competenciaChave = $dadosFolha['competencia_chave'] ?? $competencia;
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Desconto em Folha para Recursos Humanos</h1>
        <p class="text-secondary text-microcopy mb-0">
            Consolidado mensal de consumo de professores e colaboradores para lançamento e conferência em folha de pagamento.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo $baseUrl; ?>/relatorios/desconto-folha/csv?competencia=<?php echo urlencode($competenciaChave); ?>" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5" title="Baixar Arquivo CSV Formatado para Sistemas de Folha / Excel">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
            Exportar CSV para Folha
        </a>
        <a href="<?php echo $baseUrl; ?>/relatorios/desconto-folha/pdf?competencia=<?php echo urlencode($competenciaChave); ?>" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5" title="Baixar Relatório Oficial com Vistos de Assinatura em PDF A4 Timbrado">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/></svg>
            Exportar PDF (A4 Timbrado)
        </a>
    </div>
</div>

<!-- Seletor de Competência (Mês/Ano) -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body">
        <form action="<?php echo $baseUrl; ?>/relatorios/desconto-folha" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="inputCompetencia" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                    Mês de Competência da Folha
                </label>
                <input type="month" name="competencia" id="inputCompetencia" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($competenciaChave, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-md-2 d-flex">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm w-100">
                    Carregar Mês
                </button>
            </div>

            <div class="col-12 col-md-6 text-md-end text-secondary text-microcopy">
                Competência Ativa: <strong class="text-dark font-tabular"><?php echo htmlspecialchars($dadosFolha['competencia'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip: Resumo Financeiro da Competência -->
<div class="stat-strip mb-4">
    <div class="stat-item">
        <span class="stat-label">Total a Descontar em Folha</span>
        <span class="stat-value font-tabular text-primary">
            R$ <?php echo number_format((float)($dadosFolha['total_desconto'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Montante bruto a enviar ao RH
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Colaboradores com Consumo</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo (int)($dadosFolha['total_colaboradores'] ?? 0); ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">pessoas</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Professores e funcionários ativos
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Total de Lanches Consumidos</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo number_format((int)($dadosFolha['total_lanches'] ?? 0), 0, ',', '.'); ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">un</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Itens retirados sob folha no balcão
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Maior Consumo Individual</span>
        <span class="stat-value font-tabular text-dark text-truncate" style="font-size: 16px; max-width: 220px;" title="<?php echo htmlspecialchars($dadosFolha['top_colaborador'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>">
            R$ <?php echo number_format((float)($dadosFolha['maior_consumo'] ?? 0.0), 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular text-truncate" style="max-width: 220px;">
            <?php echo htmlspecialchars($dadosFolha['top_colaborador'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </div>
</div>

<!-- Tabela de Lançamentos de Desconto em Folha -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center">
        <h2 class="h6 font-weight-bold text-dark mb-0">Relação Nominal para Folha de Pagamento</h2>
        <span class="badge bg-light text-secondary border font-tabular">
            <?php echo count($colaboradores); ?> colaborador(es)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0">
            <thead>
                <tr>
                    <th style="width: 140px;">Matrícula</th>
                    <th>Nome Completo do Colaborador</th>
                    <th style="width: 220px;">Cargo / Função</th>
                    <th class="text-end" style="width: 130px;">Qtd Lanches</th>
                    <th class="text-end" style="width: 160px;">Valor a Descontar</th>
                    <th class="text-center" style="width: 120px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($colaboradores)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-secondary text-microcopy">
                            Nenhum consumo faturado sob desconto em folha registrado nesta competência.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($colaboradores as $c): ?>
                        <tr>
                            <td class="font-tabular font-weight-bold text-dark">
                                <?php echo htmlspecialchars($c['matricula_funcional'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($c['foto_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($c['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto" class="rounded-circle border" style="width: 28px; height: 28px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary font-weight-bold" style="width: 28px; height: 28px; font-size: 11px;">
                                            <?php echo htmlspecialchars(strtoupper(substr($c['colaborador_nome'] ?? 'C', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <strong class="text-dark"><?php echo htmlspecialchars($c['colaborador_nome'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                </div>
                            </td>
                            <td class="text-secondary font-tabular">
                                <?php echo htmlspecialchars($c['cargo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td class="text-end font-tabular text-dark">
                                <?php echo number_format((int)$c['total_lanches'], 0, ',', '.'); ?> un
                            </td>
                            <td class="text-end font-tabular font-weight-bold text-primary" style="font-size: 14px;">
                                R$ <?php echo number_format((float)$c['total_desconto'], 2, ',', '.'); ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" onclick="abrirModalDetalhes(<?php echo (int)$c['colaborador_id']; ?>)" title="Ver compras individuais no mês">
                                    Ver Itens
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="table-active font-weight-bold">
                    <td colspan="3" class="text-end text-dark text-uppercase font-tabular">
                        Montante Total a Descontar em Folha:
                    </td>
                    <td class="text-end font-tabular text-dark">
                        <?php echo number_format((int)($dadosFolha['total_lanches'] ?? 0), 0, ',', '.'); ?> un
                    </td>
                    <td class="text-end font-tabular text-dark" style="font-size: 15px;">
                        R$ <?php echo number_format((float)($dadosFolha['total_desconto'] ?? 0.0), 2, ',', '.'); ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

</div>

<!-- Modal: Detalhamento Individual de Compras do Colaborador -->
<div class="modal fade" id="modalDetalhesColaborador" tabindex="-1" aria-labelledby="modalDetalhesColaboradorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-2.5">
                <div>
                    <h5 class="modal-title font-weight-bold h6 mb-0" id="modalDetalhesColaboradorLabel">Detalhamento de Consumo do Colaborador</h5>
                    <span class="text-secondary text-microcopy font-tabular" id="modalSubtitulo">Carregando informações...</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-0" id="modalCorpoDetalhes">
                <div class="p-4 text-center text-secondary">
                    <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                    <div>Consultando registros fiscais de compras...</div>
                </div>
            </div>
            <div class="modal-footer border-top py-2 d-flex justify-content-between align-items-center">
                <div class="font-tabular font-weight-bold text-dark" id="modalTotalRodape"></div>
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalDetalhes(colaboradorId) {
    const modalEl = document.getElementById('modalDetalhesColaborador');
    const subtitulo = document.getElementById('modalSubtitulo');
    const corpo = document.getElementById('modalCorpoDetalhes');
    const totalRodape = document.getElementById('modalTotalRodape');
    const bsModal = new bootstrap.Modal(modalEl);

    subtitulo.textContent = 'Carregando dados da competência...';
    corpo.innerHTML = `
        <div class="p-4 text-center text-secondary">
            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
            <div>Consultando registros fiscais de compras...</div>
        </div>
    `;
    totalRodape.textContent = '';
    bsModal.show();

    fetch('<?php echo $baseUrl; ?>/relatorios/desconto-folha/detalhes-ajax?colaborador_id=' + colaboradorId + '&competencia=<?php echo urlencode($competenciaChave); ?>')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                corpo.innerHTML = `<div class="p-4 text-center text-danger">${data.erro || 'Falha ao obter detalhes.'}</div>`;
                return;
            }

            subtitulo.textContent = `${data.colaborador.nome} • Matrícula: ${data.colaborador.matricula} (${data.colaborador.cargo})`;
            totalRodape.textContent = `Total a Descontar: ${data.total_fmt} (${data.total_itens} item/itens)`;

            if (!data.vendas || data.vendas.length === 0) {
                corpo.innerHTML = `<div class="p-4 text-center text-secondary">Nenhuma compra detalhada encontrada.</div>`;
                return;
            }

            let html = `
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-enterprise table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Data / Hora</th>
                                <th style="width: 90px;">Turno</th>
                                <th>Itens Adquiridos</th>
                                <th>Operador</th>
                                <th class="text-end" style="width: 120px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            data.vendas.forEach(v => {
                let itensStr = v.itens.map(i => `${i.quantidade}x ${i.produto_nome}`).join(', ');
                html += `
                    <tr>
                        <td class="font-tabular font-weight-bold text-dark">${v.data_fmt}</td>
                        <td><span class="code-tag font-tabular">${v.turno}</span></td>
                        <td>
                            <div class="text-dark font-weight-bold" style="font-size: 13px;">${itensStr}</div>
                            <div class="text-secondary text-microcopy font-tabular">Venda PDV #${v.venda_id}</div>
                        </td>
                        <td class="text-secondary text-microcopy font-tabular">${v.operador}</td>
                        <td class="text-end font-tabular font-weight-bold text-dark">${v.valor_total_fmt}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            corpo.innerHTML = html;
        })
        .catch(() => {
            corpo.innerHTML = `<div class="p-4 text-center text-danger">Erro de comunicação com o servidor.</div>`;
        });
}
</script>
