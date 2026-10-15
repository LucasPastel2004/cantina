<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão do Calendário Escolar e Dias Letivos (Enterprise Anti-Slop)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$diasSemana = $diasSemana ?? [];
$semanaAnterior = $semanaAnterior ?? date('Y-m-d', strtotime('-7 days'));
$semanaProxima = $semanaProxima ?? date('Y-m-d', strtotime('+7 days'));
$semanaAtualHoje = $semanaAtualHoje ?? date('Y-m-d');
$dataSelecionada = $dataSelecionada ?? ($diasSemana[0]['data'] ?? date('Y-m-d'));
$excecoesAno = $excecoesAno ?? [];
$metricas = $metricas ?? ['feriados' => 0, 'recessos' => 0, 'ferias' => 0, 'cancelamentos' => 0, 'total_excecoes' => 0];
$anoAtual = $anoAtual ?? (int)date('Y');
$hoje = $hoje ?? date('Y-m-d');
$csrfField = $csrf_field ?? '';
$csrfToken = $csrf_token ?? '';

$primeiroDiaFmt = !empty($diasSemana[0]['data']) ? date('d/m', strtotime($diasSemana[0]['data'])) : '';
$ultimoDiaFmt = !empty($diasSemana[4]['data']) ? date('d/m/Y', strtotime($diasSemana[4]['data'])) : '';
?>

<!-- Cabeçalho da Página -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Calendário Escolar & Dias Letivos</h1>
        <p class="text-secondary text-microcopy mb-0">
            Parametrização de feriados, recessos escolares, períodos letivos e suspensão emergencial com estorno em lote.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1" onclick="abrirModalExcecao()">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M14 0H2a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM1 3.857C1 3.384 1.448 3 2 3h12c.552 0 1 .384 1 .857v10.286c0 .473-.448.857-1 .857H2c-.552 0-1-.384-1-.857V3.857z"/>
                <path d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7z"/>
            </svg>
            + Novo Feriado / Recesso
        </button>
        <button type="button" class="btn btn-enterprise btn-secondary btn-sm d-inline-flex align-items-center gap-1 text-danger" onclick="abrirModalEmergencial()" title="Suspender aulas e estornar pedidos agendados">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
            </svg>
            Suspensão Emergencial
        </button>
    </div>
</div>

<!-- Faixa de Métricas Executivas (Stat-Strip) -->
<?php
$stats = [
    ['label' => 'FERIADOS NO ANO', 'value' => (string)($metricas['feriados'] ?? 0), 'tabular' => true],
    ['label' => 'RECESSOS & FÉRIAS', 'value' => (string)(($metricas['recessos'] ?? 0) + ($metricas['ferias'] ?? 0)), 'tabular' => true],
    ['label' => 'SUSPENSÕES EMERGENCIAIS', 'value' => (string)($metricas['cancelamentos'] ?? 0), 'tabular' => true],
    ['label' => 'SEMANA VISUALIZADA', 'value' => "{$primeiroDiaFmt} a {$ultimoDiaFmt}", 'tabular' => false],
];
require __DIR__ . '/../partials/stat_strip.php';
?>

<!-- Card Principal: Grade Semanal com Navegação -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
        <div class="d-flex align-items-center gap-2">
            <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Semana Letiva</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="Navegação de Semanas">
                <a href="<?php echo $baseUrl; ?>/admin/calendario?semana=<?php echo $semanaAnterior; ?>" class="btn btn-enterprise btn-secondary btn-sm py-1 px-2" title="Semana Anterior">
                    &lsaquo; Anterior
                </a>
                <a href="<?php echo $baseUrl; ?>/admin/calendario?semana=<?php echo $semanaAtualHoje; ?>" class="btn btn-enterprise btn-secondary btn-sm py-1 px-2" title="Voltar para a Semana Atual">
                    Hoje
                </a>
                <a href="<?php echo $baseUrl; ?>/admin/calendario?semana=<?php echo $semanaProxima; ?>" class="btn btn-enterprise btn-secondary btn-sm py-1 px-2" title="Próxima Semana">
                    Próxima &rsaquo;
                </a>
            </div>
            <span class="text-dark font-weight-semibold font-tabular ms-2" style="font-size: 13px;">
                Semana de <?php echo $primeiroDiaFmt; ?> a <?php echo $ultimoDiaFmt; ?>
            </span>
        </div>

        <form action="<?php echo $baseUrl; ?>/admin/calendario" method="GET" class="d-flex align-items-center gap-2 m-0">
            <label for="semanaPicker" class="text-microcopy text-secondary mb-0 d-none d-sm-inline">Ir para data:</label>
            <input type="date" id="semanaPicker" name="semana" class="form-control form-control-sm font-tabular py-1" style="max-width: 140px; font-size: 12px;" value="<?php echo htmlspecialchars($dataSelecionada, ENT_QUOTES, 'UTF-8'); ?>" onchange="this.form.submit()">
        </form>
    </div>

    <div class="card-enterprise-body p-0">
        <div class="table-responsive">
            <table class="table-enterprise mb-0">
                <thead>
                    <tr>
                        <th class="cell-left" style="width: 140px;">Data</th>
                        <th class="cell-left" style="width: 150px;">Dia da Semana</th>
                        <th class="cell-left">Status & Motivo</th>
                        <th class="cell-center" style="width: 170px;">Operação Cantina</th>
                        <th class="cell-right" style="width: 220px;">Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diasSemana as $dia): ?>
                        <?php 
                        $isHoje = ($dia['data'] === $hoje);
                        $tipo = $dia['tipo'] ?? 'letivo';
                        $isLetivo = !empty($dia['is_letivo']);
                        $isEmergencial = ($tipo === 'cancelado_emergencial');
                        $isFeriado = ($tipo === 'feriado');
                        $isRecesso = in_array($tipo, ['recesso', 'ferias'], true);
                        ?>
                        <tr class="<?php echo $isHoje ? 'table-active' : ''; ?> <?php echo $isEmergencial ? 'bg-danger-subtle' : ''; ?>">
                            <!-- Data -->
                            <td class="cell-left font-tabular font-weight-semibold">
                                <?php echo date('d/m/Y', strtotime($dia['data'])); ?>
                                <?php if ($isHoje): ?>
                                    <span class="badge bg-primary text-white ms-1" style="font-size: 10px; font-weight: 500;">Hoje</span>
                                <?php endif; ?>
                            </td>

                            <!-- Dia da Semana -->
                            <td class="cell-left text-dark">
                                <?php echo htmlspecialchars($dia['dia_semana_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                            </td>

                            <!-- Status & Motivo -->
                            <td class="cell-left">
                                <?php if ($isLetivo): ?>
                                    <span class="status-indicator status-active">
                                        <span class="bullet" aria-hidden="true">●</span> Dia Letivo Regular
                                    </span>
                                <?php elseif ($isFeriado): ?>
                                    <span class="status-indicator" style="color: #b45309;">
                                        <span class="bullet" style="color: #d97706;" aria-hidden="true">●</span> 
                                        <strong>Feriado:</strong> <?php echo htmlspecialchars($dia['descricao'] ?? 'Feriado Escolar', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php elseif ($isRecesso): ?>
                                    <span class="status-indicator" style="color: #475569;">
                                        <span class="bullet" style="color: #64748b;" aria-hidden="true">●</span> 
                                        <strong>Recesso:</strong> <?php echo htmlspecialchars($dia['descricao'] ?? 'Recesso Escolar', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php elseif ($isEmergencial): ?>
                                    <span class="status-indicator text-danger font-weight-semibold">
                                        <span class="bullet" style="color: #dc2626;" aria-hidden="true">●</span> 
                                        <strong>Suspensão Emergencial:</strong> <?php echo htmlspecialchars($dia['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-indicator text-muted">
                                        <span class="bullet" aria-hidden="true">○</span> Não Letivo
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Operação Cantina -->
                            <td class="cell-center">
                                <?php if ($isLetivo): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 11px;">
                                        Matutino &amp; Vespertino
                                    </span>
                                <?php elseif ($isEmergencial): ?>
                                    <span class="badge bg-danger text-white px-2 py-1" style="font-size: 11px;">
                                        Aulas Suspensas
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                                        Cantina Fechada
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Ações -->
                            <td class="cell-right">
                                <div class="d-inline-flex gap-1 align-items-center">
                                    <?php if ($isLetivo): ?>
                                        <button type="button" class="btn btn-action" title="Marcar como Feriado ou Recesso"
                                                onclick="abrirModalExcecao('<?php echo $dia['data']; ?>', 'feriado')">
                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                            </svg>
                                        </button>
                                        <button type="button" class="btn btn-action text-danger" title="Suspensão emergencial nesta data"
                                                onclick="abrirModalEmergencial('<?php echo $dia['data']; ?>')">
                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                                                <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-action" title="Editar Exceção"
                                                onclick="abrirModalExcecao('<?php echo $dia['data']; ?>', '<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($dia['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                                            <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                            </svg>
                                        </button>
                                        <form action="<?php echo $baseUrl; ?>/admin/calendario/excluir" method="POST" class="d-inline m-0"
                                              data-confirm="Deseja remover esta exceção e restaurar o dia letivo normal?"
                                              data-confirm-title="Restaurar Dia Letivo"
                                              data-confirm-type="success"
                                              data-confirm-btn="Sim, Restaurar">
                                            <?php echo $csrfField; ?>
                                            <input type="hidden" name="data" value="<?php echo $dia['data']; ?>">
                                            <button type="submit" class="btn btn-action text-success" title="Restaurar como Dia Letivo Regular">
                                                <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                                                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                                                </svg>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Grade Inferior: Tabela Anual de Exceções e Box de Regras -->
<div class="row g-4">
    <!-- Coluna Esquerda: Todas as Exceções Cadastradas no Ano -->
    <div class="col-12 col-lg-8">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">
                    Feriados e Recessos Cadastrados (<?php echo $anoAtual; ?>)
                </span>
                <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">
                    <?php echo count($excecoesAno); ?> <?php echo count($excecoesAno) === 1 ? 'registro' : 'registros'; ?>
                </span>
            </div>
            <div class="card-enterprise-body p-0">
                <?php if (empty($excecoesAno)): ?>
                    <div class="p-4 text-center">
                        <?php
                        $emptyTitle = "Nenhum feriado ou recesso cadastrado para {$anoAtual}";
                        $emptyText = "Todas as semanas de segunda a sexta estão operando como letivas normais. Clique no botão acima para adicionar datas especiais.";
                        require __DIR__ . '/../partials/empty_state.php';
                        ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table-enterprise mb-0">
                            <thead>
                                <tr>
                                    <th class="cell-left" style="width: 110px;">Data</th>
                                    <th class="cell-left" style="width: 120px;">Classificação</th>
                                    <th class="cell-left">Descrição / Motivo</th>
                                    <th class="cell-left" style="width: 140px;">Registrado Por</th>
                                    <th class="cell-right" style="width: 80px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($excecoesAno as $exc): ?>
                                    <?php
                                    $tipoExc = $exc['tipo'];
                                    $isEmerg = ($tipoExc === 'cancelado_emergencial');
                                    ?>
                                    <tr class="<?php echo $isEmerg ? 'table-danger' : ''; ?>">
                                        <td class="cell-left font-tabular font-weight-semibold">
                                            <?php echo date('d/m/Y', strtotime($exc['data'])); ?>
                                        </td>
                                        <td class="cell-left">
                                            <?php if ($tipoExc === 'feriado'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 10px;">
                                                    Feriado
                                                </span>
                                            <?php elseif ($tipoExc === 'recesso'): ?>
                                                <span class="badge bg-secondary-subtle text-secondary-emphasis border px-2 py-1" style="font-size: 10px;">
                                                    Recesso
                                                </span>
                                            <?php elseif ($tipoExc === 'ferias'): ?>
                                                <span class="badge bg-info-subtle text-info-emphasis border px-2 py-1" style="font-size: 10px;">
                                                    Férias
                                                </span>
                                            <?php elseif ($isEmerg): ?>
                                                <span class="badge bg-danger text-white px-2 py-1" style="font-size: 10px;">
                                                    Suspensão
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border px-2 py-1" style="font-size: 10px;">
                                                    Letivo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cell-left text-dark">
                                            <?php echo htmlspecialchars($exc['descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cell-left text-secondary text-microcopy">
                                            <?php echo htmlspecialchars($exc['cancelador_nome'] ?? 'Administrador', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cell-right">
                                            <form action="<?php echo $baseUrl; ?>/admin/calendario/excluir" method="POST" class="d-inline m-0"
                                                  data-confirm="Deseja remover esta data e restaurá-la como dia letivo regular?"
                                                  data-confirm-title="Restaurar Dia Letivo"
                                                  data-confirm-type="success"
                                                  data-confirm-btn="Sim, Restaurar">
                                                <?php echo $csrfField; ?>
                                                <input type="hidden" name="data" value="<?php echo $exc['data']; ?>">
                                                <button type="submit" class="btn btn-action text-danger" title="Excluir exceção">
                                                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Coluna Direita: Regras de Corte e Políticas da Escola -->
    <div class="col-12 col-lg-4">
        <div class="card-enterprise mb-4">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Regras e Políticas Oficiais</span>
            </div>
            <div class="card-enterprise-body">
                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-microcopy text-muted d-block">Liberação do Cardápio Semanal (RN-01)</span>
                    <strong class="text-dark">Sexta-feira às 18:00</strong>
                    <p class="text-microcopy text-secondary mt-1 mb-0">
                        O agendamento para a semana seguinte é destravado automaticamente para os responsáveis neste momento.
                    </p>
                </div>

                <div class="mb-3 pb-3 border-bottom">
                    <span class="text-microcopy text-muted d-block">Cortes Operacionais por Turno (RN-02)</span>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-microcopy text-dark">Matutino:</span>
                        <strong class="font-tabular text-dark">08:00 pontual</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <span class="text-microcopy text-dark">Vespertino:</span>
                        <strong class="font-tabular text-dark">13:30 pontual</strong>
                    </div>
                </div>

                <div>
                    <span class="text-microcopy text-muted d-block">Suspensão de Aulas em Lote</span>
                    <strong class="text-danger" style="font-size: 13px;">Estorno Automático 100%</strong>
                    <p class="text-microcopy text-secondary mt-1 mb-0">
                        Em caso de paralisação ou imprevisto, o cancelamento emergencial anula tickets térmicos e credita 100% do valor no saldo familiar instantaneamente.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Cadastro / Edição de Feriado ou Recesso -->
<div class="modal fade" id="modalExcecao" tabindex="-1" aria-labelledby="modalExcecaoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-enterprise border-0 shadow">
            <div class="modal-header card-enterprise-header">
                <h5 class="modal-title font-weight-bold text-dark fs-6" id="modalExcecaoTitulo">
                    Definir Feriado / Recesso no Calendário
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/calendario/salvar" method="POST" class="m-0">
                <?php echo $csrfField; ?>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="excData" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Data</label>
                        <input type="date" class="form-control font-tabular" id="excData" name="data" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="excTipo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Classificação</label>
                        <select class="form-select" id="excTipo" name="tipo" required>
                            <option value="feriado">Feriado Escolar / Nacional / Municipal</option>
                            <option value="recesso">Recesso Escolar / Emenda</option>
                            <option value="ferias">Férias Escolares</option>
                            <option value="letivo">Dia Letivo Regular (Reposição aos Sábados)</option>
                        </select>
                        <small class="text-muted" style="font-size: 11px;">
                            Datas marcadas como feriado, recesso ou férias não permitem agendamento de lanche pelos pais.
                        </small>
                    </div>

                    <div class="mb-2">
                        <label for="excDescricao" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Descrição / Nome do Feriado</label>
                        <input type="text" class="form-control" id="excDescricao" name="descricao" maxlength="100" placeholder="Ex: Dia das Crianças / N. Sra. Aparecida" required>
                    </div>
                </div>
                <div class="modal-footer card-enterprise-footer p-2 bg-light d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Salvar no Calendário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Suspensão Emergencial de Aulas com Estorno Automático -->
<div class="modal fade" id="modalEmergencial" tabindex="-1" aria-labelledby="modalEmergencialTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content card-enterprise border-0 shadow">
            <div class="modal-header card-enterprise-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold fs-6 text-white d-flex align-items-center gap-2" id="modalEmergencialTitulo">
                    <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                        <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                    </svg>
                    Suspensão Emergencial &amp; Estorno Automático
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/calendario/emergencial" method="POST" class="m-0"
                  data-confirm="Confirma a suspensão de aulas e o estorno imediato dos pedidos para todos os responsáveis?"
                  data-confirm-title="Suspensão Emergencial &amp; Estorno"
                  data-confirm-type="danger"
                  data-confirm-btn="Sim, Suspender e Estornar">
                <?php echo $csrfField; ?>
                <div class="modal-body p-3">
                    <div class="alert alert-warning border border-warning-subtle d-flex align-items-start gap-2 mb-3" style="font-size: 12px; line-height: 1.4;">
                        <span class="text-warning fs-5">⚠️</span>
                        <div>
                            <strong>Atenção operacional e contábil:</strong> Esta ação cancela todos os pedidos agendados para a data selecionada, anula os tickets térmicos e <strong>estorna 100% dos valores na carteira dos responsáveis</strong> no mesmo instante.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="emergData" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Data da Suspensão</label>
                        <input type="date" class="form-control font-tabular" id="emergData" name="data" required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-2">
                        <label for="emergMotivo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">Justificativa / Motivo Formal</label>
                        <textarea class="form-control" id="emergMotivo" name="motivo" rows="3" maxlength="100" placeholder="Ex: Temporal severo com falta de energia na escola / Ordem da Secretaria" required></textarea>
                    </div>
                </div>
                <div class="modal-footer card-enterprise-footer p-2 bg-light d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Voltar</button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm">Confirmar Suspensão &amp; Estornar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalExcecao(data = '', tipo = 'feriado', descricao = '') {
    if (data) {
        document.getElementById('excData').value = data;
    }
    if (tipo) {
        document.getElementById('excTipo').value = tipo;
    }
    if (descricao) {
        document.getElementById('excDescricao').value = descricao;
    } else {
        document.getElementById('excDescricao').value = '';
    }
    const modalEl = document.getElementById('modalExcecao');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function abrirModalEmergencial(data = '') {
    if (data) {
        document.getElementById('emergData').value = data;
    }
    document.getElementById('emergMotivo').value = '';
    const modalEl = document.getElementById('modalEmergencial');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}
</script>
