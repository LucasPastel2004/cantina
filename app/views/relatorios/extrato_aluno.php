<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Extrato Analítico de Consumo e Recargas do Aluno
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.5 & Seção 22.1), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$aluno = $dadosExtrato['aluno'] ?? null;
?>

<div class="container-fluid px-0" style="max-width: 1400px; margin: 0 auto;">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Extrato Analítico do Aluno</h1>
        <p class="text-secondary text-microcopy mb-0">
            Consulta transparente de recargas, saldo familiar compartilhado e histórico de consumo individual por período.
        </p>
    </div>
    <?php if ($aluno): ?>
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo $baseUrl; ?>/operacional/extrato-aluno/imprimir?aluno_id=<?php echo (int)$aluno['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" target="_blank" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Visualizar espelho para impressão contínua ou A4">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                Imprimir Extrato
            </a>
            <a href="<?php echo $baseUrl; ?>/operacional/extrato-aluno/pdf?aluno_id=<?php echo (int)$aluno['id']; ?>&data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" title="Baixar Extrato em PDF A4 Timbrado Oficial">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                Exportar PDF (A4)
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Filtros de Seleção de Aluno e Período de Datas -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body">
        <form action="<?php echo $baseUrl; ?>/operacional/extrato-aluno" method="GET" class="row g-3 align-items-end" id="formExtratoAluno">
            <div class="col-12 col-md-5 position-relative">
                <label for="inputBuscaAluno" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                    Localizar Aluno (Nome ou Matrícula)
                </label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="#64748b"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                    </span>
                    <input type="text" id="inputBuscaAluno" class="form-control form-control-sm border-start-0" placeholder="Digite para buscar aluno..." autocomplete="off" value="<?php echo htmlspecialchars($aluno['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="aluno_id" id="hiddenAlunoId" value="<?php echo (int)($alunoId ?? 0); ?>">
                </div>
                <!-- Dropdown de Autocomplete -->
                <div id="autocompleteDropdown" class="list-group position-absolute w-100 shadow-sm border mt-1 d-none" style="z-index: 1050; max-height: 240px; overflow-y: auto;"></div>
            </div>

            <div class="col-6 col-md-3">
                <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-6 col-md-3">
                <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-md-1 d-flex">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm w-100">
                    Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($aluno): ?>
    <!-- Banner de Perfil e Identificação do Aluno -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-body d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <?php if (!empty($aluno['foto_url'])): ?>
                    <img src="<?php echo htmlspecialchars($aluno['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto do aluno" class="rounded-circle border" style="width: 52px; height: 52px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-secondary font-weight-bold font-tabular" style="width: 52px; height: 52px; font-size: 18px;">
                        <?php echo htmlspecialchars(strtoupper(substr($aluno['nome'] ?? 'A', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h6 font-weight-bold text-dark mb-0"><?php echo htmlspecialchars($aluno['nome'], ENT_QUOTES, 'UTF-8'); ?></h2>
                        <span class="code-tag font-tabular" style="font-size: 11px;">
                            Matrícula: <?php echo htmlspecialchars($aluno['matricula'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span class="code-tag font-tabular" style="font-size: 11px;">
                            <?php echo htmlspecialchars(($aluno['serie'] ?? '') . ' ' . ($aluno['nome_turma'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> (<?php echo htmlspecialchars(ucfirst($aluno['turno'] ?? 'matutino'), ENT_QUOTES, 'UTF-8'); ?>)
                        </span>
                    </div>
                    <div class="text-secondary text-microcopy mt-1">
                        Responsável Titular: <strong class="text-dark"><?php echo htmlspecialchars($aluno['responsavel_nome'] ?? 'Não informado', ENT_QUOTES, 'UTF-8'); ?></strong> 
                        <?php if (!empty($aluno['responsavel_whatsapp'])): ?>
                            • WhatsApp: <span class="font-tabular"><?php echo htmlspecialchars($aluno['responsavel_whatsapp'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($aluno['alergenos_resumo'])): ?>
                        <div class="mt-1 d-flex align-items-center gap-1.5">
                            <span class="code-tag font-tabular" style="font-size: 10px;">
                                Restrições / Alérgenos: <?php echo htmlspecialchars($aluno['alergenos_resumo'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-end">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Saldo Atual da Carteira</span>
                <span class="font-tabular h5 font-weight-bold <?php echo ($dadosExtrato['saldo_atual'] < 0) ? 'text-danger' : 'text-dark'; ?> mb-0">
                    R$ <?php echo number_format($dadosExtrato['saldo_atual'], 2, ',', '.'); ?>
                </span>
                <span class="text-microcopy text-secondary d-block">Compartilhado entre dependentes</span>
            </div>
        </div>
    </div>

    <!-- Stat-Strip: Resumo do Período -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Total Recarregado no Período</span>
            <span class="stat-value font-tabular text-success">
                R$ <?php echo number_format($dadosExtrato['total_recargas'], 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                <?php echo count($dadosExtrato['recargas']); ?> recarga(s) efetuada(s)
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Total Consumido no Período</span>
            <span class="stat-value font-tabular text-dark">
                R$ <?php echo number_format($dadosExtrato['total_consumo'], 2, ',', '.'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Pedidos agendados + vendas no balcão
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Lanches / Itens Consumidos</span>
            <span class="stat-value font-tabular text-primary">
                <?php echo (int)$dadosExtrato['total_lanches']; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">un</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                <?php echo count($dadosExtrato['consumo']); ?> atendimento(s) no total
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Período Selecionado</span>
            <span class="stat-value font-tabular text-dark" style="font-size: 16px;">
                <?php echo htmlspecialchars($dadosExtrato['periodo'], ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Filtro temporal ativo
            </span>
        </div>
    </div>

    <!-- Seção 1: Recargas da Carteira Familiar no Período -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <h2 class="h6 font-weight-bold text-dark mb-0">Recargas de Saldo da Carteira Familiar no Período</h2>
            <span class="badge bg-light text-secondary border font-tabular">
                <?php echo count($dadosExtrato['recargas']); ?> registro(s)
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 140px;">Data / Hora</th>
                        <th style="width: 180px;">Tipo de Operação</th>
                        <th>Operador / Canal</th>
                        <th>Referência / Transação</th>
                        <th class="text-end" style="width: 140px;">Valor Creditado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dadosExtrato['recargas'])): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary text-microcopy">
                                Nenhuma recarga de saldo registrada para esta família no período especificado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dadosExtrato['recargas'] as $r): ?>
                            <tr>
                                <td class="font-tabular">
                                    <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ($r['tipo_transacao'] === 'recarga_pix'): ?>
                                        <span class="code-tag font-tabular">Recarga Pix Dinâmico</span>
                                    <?php elseif ($r['tipo_transacao'] === 'recarga_dinheiro'): ?>
                                        <span class="code-tag font-tabular">Recarga Dinheiro Balcão</span>
                                    <?php else: ?>
                                        <span class="code-tag font-tabular"><?php echo htmlspecialchars($r['tipo_transacao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($r['operador_nome'] ?? 'Gateway Automatizado', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="font-tabular text-secondary text-microcopy">
                                    <?php echo htmlspecialchars($r['referencia_id'] ?? ('#TRX-' . $r['id']), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="text-end font-tabular font-weight-bold text-success">
                                    + R$ <?php echo number_format((float)$r['valor'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Seção 2: Consumo Detalhado Linha a Linha -->
    <div class="card-enterprise">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <h2 class="h6 font-weight-bold text-dark mb-0">Consumo Individual do Aluno Linha a Linha</h2>
            <span class="badge bg-light text-secondary border font-tabular">
                <?php echo count($dadosExtrato['consumo']); ?> atendimento(s)
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 120px;">Data</th>
                        <th style="width: 100px;">Turno</th>
                        <th style="width: 160px;">Canal de Compra</th>
                        <th>Itens / Alimentos Consumidos</th>
                        <th style="width: 110px;">Status</th>
                        <th class="text-end" style="width: 130px;">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dadosExtrato['consumo'])): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-secondary text-microcopy">
                                Nenhum consumo registrado para este aluno no período especificado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dadosExtrato['consumo'] as $c): ?>
                            <tr>
                                <td class="font-tabular font-weight-bold text-dark">
                                    <?php echo date('d/m/Y', strtotime($c['data_consumo'])); ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border font-tabular">
                                        <?php echo ucfirst($c['turno'] ?? 'matutino'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($c['canal'] === 'Agendamento Online'): ?>
                                        <span class="code-tag font-tabular">
                                            Online #PED-<?php echo (int)$c['referencia_id']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="code-tag font-tabular">
                                            Balcão #VND-<?php echo (int)$c['referencia_id']; ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-dark font-weight-bold"><?php echo htmlspecialchars($c['itens_descricao'] ?? 'Itens avulsos', ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-secondary text-microcopy d-block">
                                        Total de <?php echo (int)$c['total_itens']; ?> unidade(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="status-indicator status-active">
                                        <span class="bullet">●</span> <?php echo ucfirst($c['status'] ?? 'concluida'); ?>
                                    </span>
                                </td>
                                <td class="text-end font-tabular font-weight-bold text-dark">
                                    R$ <?php echo number_format((float)$c['valor_total'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active font-weight-bold">
                        <td colspan="5" class="text-end text-dark text-uppercase font-tabular">
                            Total Consumido no Período:
                        </td>
                        <td class="text-end font-tabular text-dark" style="font-size: 14px;">
                            R$ <?php echo number_format($dadosExtrato['total_consumo'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- Empty State: Nenhum aluno selecionado -->
    <div class="card-enterprise text-center py-5">
        <div class="card-enterprise-body">
            <div class="mb-3 text-secondary">
                <svg width="48" height="48" viewBox="0 0 16 16" fill="currentColor" style="opacity: 0.4;">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                </svg>
            </div>
            <h2 class="h6 font-weight-bold text-dark mb-1">Nenhum aluno selecionado</h2>
            <p class="text-secondary text-microcopy mb-3" style="max-width: 480px; margin: 0 auto;">
                Utilize o campo de busca acima digitando o nome ou matrícula do aluno para visualizar o extrato detalhado de recargas, saldo atual e consumo linha a linha.
            </p>
        </div>
    </div>
<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputBusca = document.getElementById('inputBuscaAluno');
    const hiddenId = document.getElementById('hiddenAlunoId');
    const dropdown = document.getElementById('autocompleteDropdown');
    const form = document.getElementById('formExtratoAluno');
    let debounceTimer = null;

    if (inputBusca && dropdown) {
        inputBusca.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const termo = inputBusca.value.trim();

            if (termo.length < 2) {
                dropdown.innerHTML = '';
                dropdown.classList.add('d-none');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch('<?php echo $baseUrl; ?>/relatorios/buscar-alunos-ajax?q=' + encodeURIComponent(termo))
                    .then(r => r.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.alunos && data.alunos.length > 0) {
                            data.alunos.forEach(aluno => {
                                const item = document.createElement('a');
                                item.href = '#';
                                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';
                                item.innerHTML = `
                                    <div>
                                        <div class="font-weight-bold text-dark" style="font-size: 13px;">${aluno.nome}</div>
                                        <div class="text-secondary text-microcopy font-tabular">Matrícula: ${aluno.matricula} • ${aluno.turma} (${aluno.turno})</div>
                                    </div>
                                    <span class="code-tag font-tabular">${aluno.saldo_fmt}</span>
                                `;
                                item.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    hiddenId.value = aluno.id;
                                    inputBusca.value = aluno.nome;
                                    dropdown.classList.add('d-none');
                                    form.submit();
                                });
                                dropdown.appendChild(item);
                            });
                            dropdown.classList.remove('d-none');
                        } else {
                            dropdown.innerHTML = '<div class="p-2 text-secondary text-microcopy text-center">Nenhum aluno localizado.</div>';
                            dropdown.classList.remove('d-none');
                        }
                    })
                    .catch(() => {
                        dropdown.classList.add('d-none');
                    });
            }, 250);
        });

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            if (!inputBusca.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });
    }
});
</script>
