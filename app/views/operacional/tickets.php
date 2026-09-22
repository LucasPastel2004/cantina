<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
 * 
 * Conforme docs/FSD.md (Área 3, Seção 10.16, Seção 11.18, Fluxos 6 e 7, RN-16, RN-17), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$dataConsumo = $dataConsumo ?? date('Y-m-d');
$turnoConsumo = $turnoConsumo ?? 'matutino';
$buscaTermo = $buscaTermo ?? '';
$metricas = $metricas ?? [
    'total_tickets'       => 0,
    'total_impressos'     => 0,
    'total_pendentes'     => 0,
    'total_com_alergenos' => 0,
    'total_alunos'        => 0,
    'total_turmas'        => 0
];
$turmasAgrupadas = $turmasAgrupadas ?? [];
$ticketsConsulta = $ticketsConsulta ?? [];
$csrf_token = $csrf_token ?? '';

$dataFmt = date('d/m/Y', strtotime($dataConsumo));
$turnoNome = ($turnoConsumo === 'matutino') ? 'Matutino' : 'Vespertino';
?>

<!-- Alertas Flash do Sistema -->
<?php require __DIR__ . '/../partials/alert.php'; ?>

<!-- Topo da Página com Título e Contexto -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Gestão e Impressão de Tickets Térmicos (80mm)</h1>
            <span class="badge bg-light text-dark border" style="font-size: 11px; font-weight: 600;">
                Turno <?php echo htmlspecialchars($turnoNome, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Emissão contínua de fichas de consumo para distribuição prévia em sala de aula e consulta rápida de 2ª via.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo $baseUrl; ?>/operacional/tickets/lote?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           target="_blank"
           class="btn btn-enterprise btn-secondary btn-sm"
           title="Abrir bobina contínua para impressão direta no navegador">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir Bobina do Turno (80mm)
        </a>
        <a href="<?php echo $baseUrl; ?>/operacional/tickets/lote/pdf?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
           class="btn btn-enterprise btn-secondary btn-sm"
           title="Baixar bobina contínua em formato binário PDF">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1">
                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg>
            Baixar Lote em PDF
        </a>
    </div>
</div>

<!-- Barra de Filtros Reativos (Data e Turno) -->
<div class="card card-enterprise mb-3">
    <div class="card-body py-2 px-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/operacional/tickets" class="row g-2 align-items-center" id="formFiltroTickets">
            <div class="col-auto">
                <label for="filtroData" class="form-label text-microcopy mb-0 fw-semibold">Data de Consumo:</label>
            </div>
            <div class="col-auto">
                <input type="date" 
                       id="filtroData" 
                       name="data" 
                       value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>" 
                       class="form-control form-control-sm font-tabular"
                       onchange="this.form.submit()"
                       style="width: 150px;">
            </div>

            <div class="col-auto ms-lg-3">
                <label for="filtroTurno" class="form-label text-microcopy mb-0 fw-semibold">Turno:</label>
            </div>
            <div class="col-auto">
                <select id="filtroTurno" 
                        name="turno" 
                        class="form-select form-select-sm" 
                        onchange="this.form.submit()"
                        style="width: 140px;">
                    <option value="matutino" <?php echo ($turnoConsumo === 'matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="vespertino" <?php echo ($turnoConsumo === 'vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                </select>
            </div>

            <div class="col-auto ms-auto text-secondary text-microcopy">
                <span>Ordenação de Impressão: <strong>Ordem da Turma &gt; Turma &gt; Aluno &gt; Ticket</strong></span>
            </div>
        </form>
    </div>
</div>

<!-- Stat-Strip com Métricas do Turno -->
<div class="stat-strip mb-4">
    <div class="stat-strip-item">
        <span class="stat-strip-label">Total de Tickets</span>
        <span class="stat-strip-value font-tabular"><?php echo (int)$metricas['total_tickets']; ?></span>
        <span class="stat-strip-sub">Fichas para <?php echo htmlspecialchars($dataFmt, ENT_QUOTES, 'UTF-8'); ?></span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Tickets Impressos</span>
        <span class="stat-strip-value font-tabular text-success"><?php echo (int)$metricas['total_impressos']; ?></span>
        <span class="stat-strip-sub">Já gerados na bobina</span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Tickets Pendentes</span>
        <span class="stat-strip-value font-tabular text-dark"><?php echo (int)$metricas['total_pendentes']; ?></span>
        <span class="stat-strip-sub">Aguardando impressão</span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Alertas de Alérgenos</span>
        <span class="stat-strip-value font-tabular <?php echo ($metricas['total_com_alergenos'] > 0) ? 'text-warning' : 'text-secondary'; ?>">
            <?php echo (int)$metricas['total_com_alergenos']; ?>
        </span>
        <span class="stat-strip-sub">Com restrições (RN-16/17)</span>
    </div>

    <div class="stat-strip-item">
        <span class="stat-strip-label">Turmas Atendidas</span>
        <span class="stat-strip-value font-tabular text-dark"><?php echo (int)$metricas['total_turmas']; ?></span>
        <span class="stat-strip-sub">Blocos de entrega</span>
    </div>
</div>

<!-- Seção 1: Lotes por Turma Escolar (Distribuição em Sala de Aula) -->
<div class="card card-enterprise mb-4">
    <div class="card-header d-flex justify-content-between align-items-center py-2 px-3">
        <div>
            <h2 class="h6 mb-0 font-weight-bold text-dark">Lotes de Fichas por Turma Escolar</h2>
            <span class="text-secondary text-microcopy">Separação ordenada por blocos de sala de aula para entrega prévia aos professores</span>
        </div>
        <span class="badge bg-light text-dark border font-tabular" style="font-size: 11px;">
            <?php echo count($turmasAgrupadas); ?> turmas com pedidos
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 80px;" class="text-center">Ordem</th>
                    <th style="width: 200px;">Série / Turma</th>
                    <th style="width: 130px;" class="text-center">Alunos Beneficiados</th>
                    <th style="width: 120px;" class="text-center">Total de Tickets</th>
                    <th style="width: 150px;">Alérgenos</th>
                    <th style="width: 140px;" class="text-center">Status Lote</th>
                    <th style="width: 160px;" class="text-end pe-3">Ações da Turma</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($turmasAgrupadas)): ?>
                    <tr>
                        <td colspan="7" class="empty-state py-4 text-center text-secondary">
                            <div class="text-microcopy">Não há nada para mostrar aqui</div>
                            <div class="text-secondary" style="font-size: 11px; margin-top: 4px;">
                                Nenhum pedido de lanche ativo para o turno e data selecionados.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($turmasAgrupadas as $tg): ?>
                        <tr>
                            <td class="text-center font-tabular fw-bold text-secondary">
                                #<?php echo (int)$tg['ordem_impressao']; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($tg['nome_turma'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy"><?php echo htmlspecialchars($tg['serie'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td class="text-center font-tabular">
                                <?php echo (int)$tg['total_alunos']; ?> alunos
                            </td>
                            <td class="text-center font-tabular fw-bold text-dark">
                                <?php echo (int)$tg['total_tickets']; ?> fichas
                            </td>
                            <td>
                                <?php if ($tg['total_com_alergenos'] > 0): ?>
                                    <span class="badge bg-warning text-dark font-tabular" style="font-size: 11px; font-weight: 600;">
                                        ⚠ <?php echo (int)$tg['total_com_alergenos']; ?> com restrição
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary text-microcopy">Sem restrições</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($tg['total_pendentes'] === 0): ?>
                                    <span class="status-indicator status-active" style="font-size: 11px;">
                                        <span class="bullet">●</span> Impresso
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border font-tabular" style="font-size: 11px;">
                                        <?php echo (int)$tg['total_pendentes']; ?> pendente(s)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?php echo $baseUrl; ?>/operacional/tickets/turma/<?php echo (int)$tg['turma_id']; ?>?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
                                       target="_blank"
                                       class="btn btn-enterprise btn-secondary btn-sm"
                                       title="Imprimir bobina contínua apenas desta turma">
                                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                                            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                                        </svg>
                                    </a>
                                    <a href="<?php echo $baseUrl; ?>/operacional/tickets/turma/<?php echo (int)$tg['turma_id']; ?>/pdf?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
                                       class="btn btn-enterprise btn-secondary btn-sm"
                                       title="Baixar PDF desta turma">
                                        <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Seção 2: Consulta Rápida e Emissão de 2ª Via -->
<div class="card card-enterprise mb-4">
    <div class="card-header py-2 px-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h6 mb-0 font-weight-bold text-dark">Consulta Rápida de Tickets &amp; Emissão de 2ª Via</h2>
                <span class="text-secondary text-microcopy">Localização instantânea para atender alunos que perderam ou danificaram a ficha</span>
            </div>

            <!-- Campo de Busca Instantânea -->
            <form method="GET" action="<?php echo $baseUrl; ?>/operacional/tickets" class="d-flex align-items-center gap-2">
                <input type="hidden" name="data" value="<?php echo htmlspecialchars($dataConsumo, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="turno" value="<?php echo htmlspecialchars($turnoConsumo, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="input-group input-group-sm" style="width: 320px;">
                    <input type="text" 
                           name="q" 
                           class="form-control" 
                           placeholder="Buscar por aluno, matrícula ou código..." 
                           value="<?php echo htmlspecialchars($buscaTermo, ENT_QUOTES, 'UTF-8'); ?>"
                           aria-label="Buscar tickets">
                    <button class="btn btn-enterprise btn-secondary" type="submit">
                        Buscar
                    </button>
                    <?php if (!empty($buscaTermo)): ?>
                        <a href="<?php echo $baseUrl; ?>/operacional/tickets?data=<?php echo urlencode($dataConsumo); ?>&turno=<?php echo urlencode($turnoConsumo); ?>" 
                           class="btn btn-enterprise btn-secondary" 
                           title="Limpar busca">
                            ✕
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-enterprise table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width: 160px;">Código do Ticket</th>
                    <th style="width: 220px;">Aluno / Matrícula</th>
                    <th style="width: 140px;">Turma</th>
                    <th style="width: 220px;">Produto / Salgado</th>
                    <th style="width: 140px;">Alérgenos</th>
                    <th style="width: 100px;" class="text-center">Via</th>
                    <th style="width: 110px;" class="text-center">Status</th>
                    <th style="width: 150px;" class="text-end pe-3">Ação 2ª Via</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ticketsConsulta)): ?>
                    <tr>
                        <td colspan="8" class="empty-state py-4 text-center text-secondary">
                            <div class="text-microcopy">Não há nada para mostrar aqui</div>
                            <div class="text-secondary" style="font-size: 11px; margin-top: 4px;">
                                <?php if (!empty($buscaTermo)): ?>
                                    Nenhum ticket encontrado para o termo "<?php echo htmlspecialchars($buscaTermo, ENT_QUOTES, 'UTF-8'); ?>".
                                <?php else: ?>
                                    Nenhum ticket registrado para este turno de consumo.
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ticketsConsulta as $tc): ?>
                        <tr>
                            <td>
                                <span class="code-tag font-tabular fw-bold"><?php echo htmlspecialchars($tc['codigo_ticket'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                <div class="text-secondary text-microcopy mt-1 font-tabular">Ped: <?php echo htmlspecialchars($tc['codigo_pedido'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($tc['aluno_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy font-tabular">Mat: <?php echo htmlspecialchars($tc['aluno_matricula'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="text-dark"><?php echo htmlspecialchars($tc['nome_turma'] ?? 'Sem Turma', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy"><?php echo htmlspecialchars($tc['serie'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($tc['produto_descricao'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-secondary text-microcopy font-tabular">
                                    R$ <?php echo number_format((float)($tc['produto_preco_venda'] ?? 0), 2, ',', '.'); ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($tc['tag_alergenos'])): ?>
                                    <span class="badge bg-warning text-dark text-wrap" style="font-size: 10px; font-weight: 700; line-height: 1.2;">
                                        <?php echo htmlspecialchars($tc['tag_alergenos'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary text-microcopy">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-tabular">
                                <?php $via = (int)($tc['numero_via'] ?? 1); ?>
                                <?php if ($via > 1): ?>
                                    <span class="badge bg-danger text-white" style="font-size: 10px; font-weight: 700;">
                                        <?php echo $via; ?>ª Via
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;">
                                        1ª Via
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                $st = $tc['ticket_status'] ?? 'ativo';
                                if ($st === 'impresso'): ?>
                                    <span class="status-indicator status-active" style="font-size: 11px;">
                                        <span class="bullet">●</span> Impresso
                                    </span>
                                <?php elseif ($st === 'ativo'): ?>
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;">
                                        Pendente
                                    </span>
                                <?php elseif ($st === 'estornado'): ?>
                                    <span class="badge bg-secondary text-white" style="font-size: 10px;">
                                        Estornado
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border" style="font-size: 10px;">
                                        <?php echo htmlspecialchars(ucfirst($st), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($st !== 'cancelado' && $st !== 'estornado'): ?>
                                    <button type="button" 
                                            class="btn btn-enterprise btn-secondary btn-sm"
                                            onclick="abrirModalSegundaVia(<?php echo (int)$tc['ticket_id']; ?>, '<?php echo htmlspecialchars(addslashes($tc['codigo_ticket']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars(addslashes($tc['aluno_nome']), ENT_QUOTES, 'UTF-8'); ?>', <?php echo (int)($tc['numero_via'] ?? 1); ?>)"
                                            title="Emitir 2ª via com justificativa compulsória">
                                        Reimprimir 2ª Via
                                    </button>
                                <?php else: ?>
                                    <span class="text-secondary text-microcopy italic">Indisponível</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Corporativo: Emissão de 2ª Via com Justificativa Compulsória (RN-16 e Auditoria) -->
<div class="modal fade" id="modalSegundaVia" tabindex="-1" aria-labelledby="modalSegundaViaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?php echo $baseUrl; ?>/operacional/tickets/segunda-via">
                <input type="hidden" name="_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="ticket_id" id="modalTicketId" value="">

                <div class="modal-header py-2 px-3 bg-light border-bottom">
                    <h5 class="modal-title h6 fw-bold text-dark mb-0" id="modalSegundaViaLabel">
                        Reemissão de 2ª Via de Ticket Térmico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body p-3">
                    <div class="alert alert-warning py-2 px-3 mb-3" style="font-size: 11px; line-height: 1.4;">
                        <strong>Aviso de Auditoria:</strong> A emissão de 2ª via substitui o cupom anterior, marca a etiqueta como <code>[2ª VIA]</code> e grava um registro compulsório e indelével na trilha de auditoria (<code>logs_auditoria</code>) com o identificador do operador e IP de origem.
                    </div>

                    <div class="p-2 mb-3 bg-light border rounded" style="font-size: 12px;">
                        <div><strong>Código do Ticket:</strong> <span class="font-tabular fw-bold text-primary" id="modalTicketCodigo">—</span></div>
                        <div><strong>Aluno:</strong> <span id="modalAlunoNome">—</span></div>
                        <div><strong>Via Atual:</strong> <span class="font-tabular" id="modalViaAtual">—</span> &rarr; <span class="text-danger fw-bold" id="modalNovaVia">—</span></div>
                    </div>

                    <div class="mb-2">
                        <label for="modalJustificativa" class="form-label fw-bold text-dark text-microcopy mb-1">
                            Justificativa Operacional Obrigatória: <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" 
                                  id="modalJustificativa" 
                                  name="justificativa" 
                                  rows="3" 
                                  required 
                                  minlength="5"
                                  placeholder="Ex: Aluno informou perda da ficha em sala de aula / Ticket rasgado..."></textarea>
                        <div class="text-secondary text-microcopy mt-1">Mínimo de 5 caracteres.</div>
                    </div>
                </div>

                <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm">
                        Confirmar Reemissão (2ª Via)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalSegundaVia(ticketId, codigoTicket, alunoNome, viaAtual) {
    document.getElementById('modalTicketId').value = ticketId;
    document.getElementById('modalTicketCodigo').textContent = codigoTicket;
    document.getElementById('modalAlunoNome').textContent = alunoNome;
    document.getElementById('modalViaAtual').textContent = viaAtual + 'ª Via';
    document.getElementById('modalNovaVia').textContent = (viaAtual + 1) + 'ª Via (Reimpressão)';
    document.getElementById('modalJustificativa').value = '';

    const modalEl = document.getElementById('modalSegundaVia');
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        setTimeout(() => {
            document.getElementById('modalJustificativa').focus();
        }, 400);
    } else {
        // Fallback defensivo caso modal JS não esteja pronto
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
    }
}
</script>
