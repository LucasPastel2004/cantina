<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Emissão de Extrato de Consumo do Aluno (Cantina)
 * 
 * Conforme docs/alterações sistema guideline/filtro Extrato Consumo Aluno.pdf,
 * Relatório Extrato Consumo Aluno.pdf, FSD Área 6 e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$aluno = $dadosExtrato['aluno'] ?? null;
$turmas = $turmas ?? [];
$turmaId = (int)($turmaId ?? 0);
$dataInicio = $dataInicio ?? date('Y-m-01');
$dataFim = $dataFim ?? date('Y-m-d');
$apenasSaldo = !empty($apenasSaldo);
$saldoApenasPeriodo = !empty($saldoApenasPeriodo);
$alunoId = (int)($alunoId ?? ($aluno['id'] ?? 0));
?>

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">

<!-- Topbar da Página -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Emissão de Extrato de Consumo do Aluno (Cantina)</h1>
        <p class="text-secondary text-microcopy mb-0">
            Consulta transparente de recargas, saldo familiar compartilhado e histórico de consumo individual por período com saldo progressivo.
        </p>
    </div>
</div>

<!-- Card de Filtros de Pesquisa e Emissão -->
<div class="card-enterprise card-overflow-visible mb-4" style="overflow: visible !important; position: relative; z-index: 30;">
    <div class="card-enterprise-body p-3" style="overflow: visible !important;">
        <form action="<?php echo $baseUrl; ?>/relatorios/extrato-aluno/imprimir" method="GET" id="formExtratoAluno" target="_blank" style="position: relative; overflow: visible !important;">
            <input type="hidden" name="apenas_saldo" value="<?php echo $apenasSaldo ? 'sim' : 'nao'; ?>">
            <input type="hidden" name="saldo_apenas_periodo" value="<?php echo $saldoApenasPeriodo ? 'sim' : 'nao'; ?>">
            <div class="row g-3 align-items-end">
                <!-- Localizar Aluno com Autocomplete -->
                <div class="col-12 col-md-5" style="position: relative; z-index: 40;">
                    <label for="aluno_busca" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">
                        Localizar Aluno (Nome ou Matrícula)
                    </label>
                    <div class="search-input-wrapper position-relative" style="position: relative;">
                        <span class="search-icon">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/></svg>
                        </span>
                        <input type="text" id="aluno_busca" name="aluno_busca" class="form-control form-control-sm" style="padding-left: 36px !important;" placeholder="Digite para buscar aluno..." autocomplete="off" value="<?php echo htmlspecialchars($aluno['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="aluno_id" id="hiddenAlunoId" value="<?php echo (int)($alunoId ?? 0); ?>">

                        <!-- Dropdown flutuante de Autocomplete -->
                        <div id="autocompleteDropdown" class="list-group position-absolute rounded shadow-lg d-none" 
                             style="top: calc(100% + 4px); left: 0; right: 0; width: 100%; z-index: 1080; max-height: 280px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2), 0 8px 10px -6px rgba(15, 23, 42, 0.12) !important;">
                        </div>
                    </div>
                </div>

                <!-- Filtro Opcional de Turma -->
                <div class="col-12 col-md-3">
                    <label for="selectTurma" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Turma (Opcional)</label>
                    <select name="turma_id" id="selectTurma" class="form-select form-select-sm">
                        <option value="">Todas as Turmas...</option>
                        <?php foreach ($turmas as $t): ?>
                            <option value="<?php echo (int)$t['id']; ?>" <?php echo ($turmaId === (int)$t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['serie'] . ' ' . $t['nome_turma'] . ' (' . ucfirst($t['turno']) . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Período: Data Inicial -->
                <div class="col-6 col-md-2">
                    <label for="dataInicio" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Inicial</label>
                    <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Período: Data Final -->
                <div class="col-6 col-md-2">
                    <label for="dataFim" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary">Data Final</label>
                    <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <!-- Botões Rápidos de Período e Botão de Emissão de PDF/Impressão -->
            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <span class="text-secondary text-microcopy me-1">Atalhos de Período:</span>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="hoje" onclick="definirPeriodo('hoje')">Hoje</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="semana" onclick="definirPeriodo('semana')">Esta Semana</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="mes" onclick="definirPeriodo('mes_atual')">Este Mês</button>
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm py-0.5 px-2" style="font-size: 11px;" data-periodo="mes_anterior" onclick="definirPeriodo('mes_anterior')">Mês Anterior</button>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Exportar CSV -->
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" onclick="exportarCsv()" title="Exportar Extrato do Aluno em formato CSV para Microsoft Excel">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.5 12.5h7v-1h-7v1zm0-2h7v-1h-7v1zm0-2h7v-1h-7v1z"/></svg>
                        <span>Exportar Planilha (CSV)</span>
                    </button>

                    <!-- Baixar PDF Direto -->
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1.5" onclick="gerarPdfDownload()" title="Fazer download direto do arquivo PDF A4 timbrado">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                        <span>Baixar PDF</span>
                    </button>

                    <!-- Imprimir / Salvar PDF (A4) - Ação Principal -->
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5 px-3" id="btnImprimirPdf" title="Imprimir ou Salvar em PDF (A4) o Extrato do Aluno">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                        <span>Imprimir / Salvar PDF (A4)</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Informações e Instrução para Emissão -->
<div class="card-enterprise p-5 text-center">
    <div class="text-secondary mb-3">
        <svg width="48" height="48" viewBox="0 0 16 16" fill="currentColor" class="text-primary" style="opacity: 0.85;">
            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v4a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
        </svg>
    </div>
    <h2 class="h6 font-weight-bold text-dark mb-1">Emissão Oficial em PDF e Impressão A4</h2>
    <p class="text-secondary text-microcopy mb-0" style="max-width: 520px; margin: 0 auto;">
        Localize o estudante pelo nome ou matrícula nos campos acima, defina o período desejado e clique em <strong>Imprimir / Salvar PDF (A4)</strong> para gerar o documento oficial completo e personalizado com banner de identificação, resumo e movimentações analíticas.
    </p>
</div>

</div>

<script>
// Atalhos rápidos de período
function definirPeriodo(tipo) {
    const hoje = new Date();
    const dataIni = document.getElementById('dataInicio');
    const dataFim = document.getElementById('dataFim');
    if (!dataIni || !dataFim) return;

    function formatar(d) {
        const ano = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, '0');
        const dia = String(d.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    }

    if (tipo === 'hoje') {
        dataIni.value = formatar(hoje);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'semana') {
        const diaSemana = hoje.getDay(); // 0 = Domingo, 1 = Segunda
        const diffSegunda = (diaSemana === 0 ? -6 : 1) - diaSemana;
        const segunda = new Date(hoje);
        segunda.setDate(hoje.getDate() + diffSegunda);
        dataIni.value = formatar(segunda);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'mes_atual') {
        const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        dataIni.value = formatar(primeiroDia);
        dataFim.value = formatar(hoje);
    } else if (tipo === 'mes_anterior') {
        const primeiroDiaAnt = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
        const ultimoDiaAnt = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
        dataIni.value = formatar(primeiroDiaAnt);
        dataFim.value = formatar(ultimoDiaAnt);
    }
}

// Autocomplete de Alunos
document.addEventListener('DOMContentLoaded', function() {
    const inputBusca = document.getElementById('aluno_busca') || document.getElementById('inputBuscaAluno');
    const hiddenAlunoId = document.getElementById('hiddenAlunoId');
    const dropdown = document.getElementById('autocompleteDropdown');
    const selectTurma = document.getElementById('selectTurma');
    const form = document.getElementById('formExtratoAluno');

    if (!inputBusca || !dropdown) return;

    let debounceTimer = null;

    inputBusca.addEventListener('input', function() {
        const termo = this.value.trim();
        clearTimeout(debounceTimer);

        if (termo.length === 0 && hiddenAlunoId) {
            hiddenAlunoId.value = '0';
        }

        if (termo.length < 2) {
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(function() {
            const url = '<?php echo $baseUrl; ?>/relatorios/buscar-alunos-ajax?q=' + encodeURIComponent(termo);
            fetch(url)
                .then(r => r.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (!data.sucesso || !data.alunos || data.alunos.length === 0) {
                        dropdown.innerHTML = '<div class="p-2 text-muted text-microcopy text-center">Nenhum aluno encontrado</div>';
                        dropdown.classList.remove('d-none');
                        return;
                    }

                    data.alunos.forEach(aluno => {
                        const item = document.createElement('a');
                        item.href = '#';
                        item.className = 'list-group-item list-group-item-action py-2 px-3 d-flex justify-content-between align-items-center';
                        item.style.fontSize = '12px';
                        item.innerHTML = `
                            <div>
                                <strong class="text-dark">${aluno.nome}</strong>
                                <span class="text-secondary text-microcopy d-block">Matrícula: ${aluno.matricula} &bull; ${aluno.turma || ''}</span>
                            </div>
                            <span class="badge bg-light text-primary border font-tabular">R$ ${parseFloat(aluno.saldo_atual || 0).toFixed(2).replace('.', ',')}</span>
                        `;

                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            inputBusca.value = aluno.nome;
                            if (hiddenAlunoId) {
                                hiddenAlunoId.value = aluno.id;
                            }
                            dropdown.classList.add('d-none');
                        });

                        dropdown.appendChild(item);
                    });

                    dropdown.classList.remove('d-none');
                })
                .catch(err => {
                    console.error('Erro na busca de alunos:', err);
                });
        }, 250);
    });

    // Validação ao submeter o formulário de impressão
    if (form) {
        form.addEventListener('submit', function(e) {
            const idVal = hiddenAlunoId ? hiddenAlunoId.value : '0';
            if (!idVal || parseInt(idVal) <= 0) {
                e.preventDefault();
                alert('Por favor, digite o nome ou matrícula do aluno e selecione-o na lista antes de emitir o extrato.');
                if (inputBusca) inputBusca.focus();
                return false;
            }
        });
    }

    // Fecha dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!inputBusca.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('d-none');
        }
    });
});

// Ação de download direto do PDF (A4)
function gerarPdfDownload() {
    const hiddenAlunoId = document.getElementById('hiddenAlunoId');
    const inputBusca = document.getElementById('aluno_busca') || document.getElementById('inputBuscaAluno');
    const idVal = hiddenAlunoId ? hiddenAlunoId.value : '0';
    if (!idVal || parseInt(idVal) <= 0) {
        alert('Por favor, digite o nome ou matrícula do aluno e selecione-o na lista antes de baixar o PDF.');
        if (inputBusca) inputBusca.focus();
        return;
    }
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    const turmaId = document.getElementById('selectTurma').value;
    const url = '<?php echo $baseUrl; ?>/relatorios/extrato-aluno/pdf?aluno_id=' + encodeURIComponent(idVal) +
                '&data_inicio=' + encodeURIComponent(dataInicio) +
                '&data_fim=' + encodeURIComponent(dataFim) +
                (turmaId ? '&turma_id=' + encodeURIComponent(turmaId) : '');
    window.location.href = url;
}

// Ação de exportação para planilha CSV
function exportarCsv() {
    const hiddenAlunoId = document.getElementById('hiddenAlunoId');
    const inputBusca = document.getElementById('aluno_busca') || document.getElementById('inputBuscaAluno');
    const idVal = hiddenAlunoId ? hiddenAlunoId.value : '0';
    if (!idVal || parseInt(idVal) <= 0) {
        alert('Por favor, digite o nome ou matrícula do aluno e selecione-o na lista antes de exportar a planilha CSV.');
        if (inputBusca) inputBusca.focus();
        return;
    }
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;
    const turmaId = document.getElementById('selectTurma').value;
    const url = '<?php echo $baseUrl; ?>/relatorios/extrato-aluno/csv?aluno_id=' + encodeURIComponent(idVal) +
                '&data_inicio=' + encodeURIComponent(dataInicio) +
                '&data_fim=' + encodeURIComponent(dataFim) +
                (turmaId ? '&turma_id=' + encodeURIComponent(turmaId) : '');
    window.location.href = url;
}
</script>
