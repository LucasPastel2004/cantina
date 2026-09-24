<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Gestão de Contestações e Mecanismo Especial de Devolução (MED) Pix
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfField = $csrf_field ?? '';

$totalNegativados = $totalNegativados ?? 0;
$saldoDevedorTotal = $saldoDevedorTotal ?? 0.0;
$totalEstornos = $totalEstornos ?? 0;
$contasRegularizadas = $contasRegularizadas ?? 0;

$negativados = $negativados ?? [];
$historicoMed = $historicoMed ?? [];
$todosResponsaveis = $todosResponsaveis ?? [];

$filtroBusca = $filtroBusca ?? '';
$filtroStatus = $filtroStatus ?? 'todos';
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Contestações Bancárias & Pix MED</h1>
        <p class="text-secondary text-microcopy mb-0">
            Controle de devoluções pelo Mecanismo Especial de Devolução do Banco Central, inadimplências e bloqueios.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/admin/contestacoes/pdf<?php echo !empty($filtroBusca) ? '?busca=' . urlencode($filtroBusca) : ''; ?>" 
           target="_blank" 
           class="btn btn-enterprise btn-secondary" 
           title="Exportar Relatório Oficial em PDF Timbrado">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1" aria-hidden="true">
                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 1.5 13 5H9.5V1.5zM3 14V2a1 1 0 0 1 1-1h5v4.5a.5.5 0 0 0 .5.5H13v8a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/>
            </svg>
            Exportar PDF
        </a>
        <button type="button" 
                class="btn btn-enterprise btn-primary" 
                data-bs-toggle="modal" 
                data-bs-target="#modalContestacaoManual" 
                onclick="abrirModalContestacaoManual()">
            + Lançar Contestação Manual
        </button>
    </div>
</div>

<!-- Faixa de Métricas Operacionais (Stat-strip) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-enterprise h-100 p-3">
            <div class="text-secondary text-microcopy font-weight-semibold text-uppercase mb-1">Famílias Bloqueadas</div>
            <div class="d-flex align-items-baseline justify-content-between">
                <span class="h4 font-weight-bold mb-0 font-tabular <?php echo ($totalNegativados > 0) ? 'text-danger' : 'text-dark'; ?>">
                    <?php echo number_format($totalNegativados, 0, ',', '.'); ?>
                </span>
                <span class="badge <?php echo ($totalNegativados > 0) ? 'bg-danger-subtle text-danger' : 'bg-light text-secondary'; ?> border px-2 py-1" style="font-size: 11px;">
                    MED Ativo
                </span>
            </div>
            <div class="text-secondary mt-1" style="font-size: 11px;">Impedidos de agendar lanches</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-enterprise h-100 p-3">
            <div class="text-secondary text-microcopy font-weight-semibold text-uppercase mb-1">Saldo Devedor Total</div>
            <div class="d-flex align-items-baseline justify-content-between">
                <span class="h4 font-weight-bold mb-0 font-tabular <?php echo ($saldoDevedorTotal > 0) ? 'text-danger' : 'text-dark'; ?>">
                    R$ <?php echo number_format($saldoDevedorTotal, 2, ',', '.'); ?>
                </span>
                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                    A Recuperar
                </span>
            </div>
            <div class="text-secondary mt-1" style="font-size: 11px;">Débitos pendentes de quitação</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-enterprise h-100 p-3">
            <div class="text-secondary text-microcopy font-weight-semibold text-uppercase mb-1">Estornos MED Recebidos</div>
            <div class="d-flex align-items-baseline justify-content-between">
                <span class="h4 font-weight-bold mb-0 font-tabular text-dark">
                    <?php echo number_format($totalEstornos, 0, ',', '.'); ?>
                </span>
                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                    Histórico BACEN
                </span>
            </div>
            <div class="text-secondary mt-1" style="font-size: 11px;">Notificações bancárias processadas</div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card-enterprise h-100 p-3">
            <div class="text-secondary text-microcopy font-weight-semibold text-uppercase mb-1">Contas Regularizadas</div>
            <div class="d-flex align-items-baseline justify-content-between">
                <span class="h4 font-weight-bold mb-0 font-tabular text-success">
                    <?php echo number_format($contasRegularizadas, 0, ',', '.'); ?>
                </span>
                <span class="badge bg-success-subtle text-success border px-2 py-1" style="font-size: 11px;">
                    Quitadas
                </span>
            </div>
            <div class="text-secondary mt-1" style="font-size: 11px;">Débitos acertados e contas liberadas</div>
        </div>
    </div>
</div>

<!-- Filtros e Busca -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-body p-3">
        <form action="<?php echo $baseUrl; ?>/admin/contestacoes" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-5">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Buscar Responsável ou Titular
                </label>
                <input type="text" class="form-control form-control-sm" id="busca" name="busca" 
                       placeholder="Nome, e-mail ou telefone..." value="<?php echo htmlspecialchars($filtroBusca, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="col-12 col-sm-3 col-md-3">
                <label for="status" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Filtrar por Status
                </label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="todos" <?php echo ($filtroStatus === 'todos') ? 'selected' : ''; ?>>Todos os Registros</option>
                    <option value="bloqueados" <?php echo ($filtroStatus === 'bloqueados') ? 'selected' : ''; ?>>Apenas Bloqueados MED</option>
                    <option value="negativos" <?php echo ($filtroStatus === 'negativos') ? 'selected' : ''; ?>>Saldo Devedor (< R$ 0)</option>
                </select>
            </div>

            <div class="col-12 col-sm-3 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-secondary btn-sm flex-grow-1">
                    Filtrar Registros
                </button>
                <?php if (!empty($filtroBusca) || $filtroStatus !== 'todos'): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/contestacoes" class="btn btn-enterprise btn-secondary btn-sm" title="Limpar Filtros">
                        Limpar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Seção Principal: Famílias Bloqueadas e Inadimplentes MED -->
<div class="card-enterprise mb-4">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <div>
            <h2 class="h6 font-weight-bold text-dark mb-0">Contas com Bloqueio ou Saldo Devedor</h2>
            <span class="text-secondary text-microcopy">Famílias sob restrição decorrente de contestação de recarga Pix</span>
        </div>
        <span class="badge bg-light text-secondary border font-tabular px-2 py-1">
            <?php echo count($negativados); ?> exibido(s)
        </span>
    </div>

    <div class="card-enterprise-body p-0">
        <?php if (empty($negativados)): ?>
            <div class="p-4 text-center">
                <div class="mb-2 text-secondary">
                    <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor" class="text-success opacity-75">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <h3 class="h6 font-weight-semibold text-dark mb-1">Nenhuma inadimplência MED encontrada</h3>
                <p class="text-secondary text-microcopy mb-0">Não há famílias negativadas ou bloqueadas por contestação bancária no momento.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-left" style="width: 250px;">Responsável Titular</th>
                            <th class="cell-left">Dependentes Vinculados</th>
                            <th class="cell-right" style="width: 140px;">Saldo Atual</th>
                            <th class="cell-center" style="width: 130px;">Status MED</th>
                            <th class="cell-center" style="width: 140px;">Última Notificação</th>
                            <th class="cell-right" style="width: 200px;">Ações Administrativas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($negativados as $fam): ?>
                            <?php 
                            $saldo = (float)($fam['saldo_atual'] ?? $fam['saldo'] ?? 0);
                            $isBloqueado = !empty($fam['bloqueado_med']);
                            $dependentes = $fam['dependentes'] ?? [];
                            $contatoFam = $fam['whatsapp'] ?? $fam['telefone'] ?? '';
                            ?>
                            <tr>
                                <td class="cell-left">
                                    <div class="font-weight-semibold text-dark">
                                        <?php echo htmlspecialchars($fam['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-secondary text-microcopy">
                                        <?php echo htmlspecialchars($fam['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if (!empty($contatoFam)): ?>
                                            &bull; <?php echo htmlspecialchars($contatoFam, ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="cell-left">
                                    <?php if (empty($dependentes)): ?>
                                        <span class="text-secondary text-microcopy fst-italic">Sem dependentes ativos</span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($dependentes as $dep): ?>
                                                <span class="badge bg-light text-dark border font-tabular" style="font-size: 11px; font-weight: normal;">
                                                    <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!empty($dep['turma_nome'])): ?>
                                                        <span class="text-secondary">(<?php echo htmlspecialchars($dep['turma_nome'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="cell-right font-tabular font-weight-bold <?php echo ($saldo < 0) ? 'text-danger' : 'text-success'; ?>">
                                    R$ <?php echo number_format($saldo, 2, ',', '.'); ?>
                                </td>

                                <td class="cell-center">
                                    <?php if ($isBloqueado): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size: 11px;">
                                            Bloqueado MED
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" style="font-size: 11px;">
                                            Saldo Negativo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="cell-center font-tabular text-secondary" style="font-size: 11px;">
                                    <?php 
                                    $dataEstorno = $fam['data_ultimo_estorno_med'] ?? $fam['data_estorno'] ?? null;
                                    echo !empty($dataEstorno) ? date('d/m/Y H:i', strtotime($dataEstorno)) : '—'; 
                                    ?>
                                </td>

                                <td class="cell-right">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" 
                                                class="btn btn-enterprise btn-secondary btn-sm" 
                                                onclick='abrirModalQuitacao(<?php echo json_encode([
                                                    "id" => $fam["id"],
                                                    "nome" => $fam["nome"],
                                                    "saldo" => (float)($fam["saldo_atual"] ?? $fam["saldo"] ?? 0)
                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                title="Lançar quitação e regularizar carteira">
                                            Quitar Débito
                                        </button>

                                        <?php if ($isBloqueado): ?>
                                            <button type="button" 
                                                    class="btn btn-enterprise btn-secondary btn-sm" 
                                                    onclick='abrirModalDesbloqueio(<?php echo json_encode([
                                                        "id" => $fam["id"],
                                                        "nome" => $fam["nome"],
                                                        "saldo" => (float)($fam["saldo_atual"] ?? $fam["saldo"] ?? 0)
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'
                                                    title="Liberar agendamento de pedidos com justificativa formal">
                                                Desbloquear
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <?php if ($totalPaginas > 1): ?>
                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <span class="text-secondary text-microcopy">
                        Página <?php echo $pagina; ?> de <?php echo $totalPaginas; ?>
                    </span>
                    <nav aria-label="Navegação da tabela">
                        <ul class="pagination pagination-sm m-0">
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?php echo ($i === $pagina) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $baseUrl; ?>/admin/contestacoes?pagina=<?php echo $i; ?>&busca=<?php echo urlencode($filtroBusca); ?>&status=<?php echo urlencode($filtroStatus); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Seção Secundária: Histórico Recente de Ocorrências e Auditoria MED -->
<div class="card-enterprise">
    <div class="card-enterprise-header d-flex justify-content-between align-items-center p-3 border-bottom">
        <div>
            <h2 class="h6 font-weight-bold text-dark mb-0">Histórico de Eventos MED e Quitações</h2>
            <span class="text-secondary text-microcopy">Trilha de auditoria das movimentações bancárias e baixas manuais</span>
        </div>
        <span class="badge bg-light text-secondary border font-tabular px-2 py-1">
            Últimos registros
        </span>
    </div>

    <div class="card-enterprise-body p-0">
        <?php if (empty($historicoMed)): ?>
            <div class="p-4 text-center">
                <p class="text-secondary text-microcopy mb-0">Nenhum evento registrado no histórico.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 140px;">Data/Hora</th>
                            <th class="cell-left" style="width: 220px;">Titular / Família</th>
                            <th class="cell-center" style="width: 150px;">Tipo de Operação</th>
                            <th class="cell-right" style="width: 120px;">Valor</th>
                            <th class="cell-right" style="width: 130px;">Saldo Pós-Operação</th>
                            <th class="cell-left">Motivo / Protocolo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historicoMed as $h): ?>
                            <?php 
                            $isDebito = ($h['tipo'] === 'debito_contestacao_med');
                            $val = (float)$h['valor'];
                            $saldoPos = (float)$h['saldo_resultante'];
                            ?>
                            <tr>
                                <td class="cell-center font-tabular text-secondary" style="font-size: 11px;">
                                    <?php echo date('d/m/Y H:i', strtotime($h['data_hora'])); ?>
                                </td>

                                <td class="cell-left">
                                    <div class="font-weight-semibold text-dark" style="font-size: 12px;">
                                        <?php echo htmlspecialchars($h['titular'] ?? 'Desconhecido', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-secondary" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($h['titular_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>

                                <td class="cell-center">
                                    <?php if ($isDebito): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-tabular" style="font-size: 10px;">
                                            Estorno MED BACEN
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-tabular" style="font-size: 10px;">
                                            Quitação / Ajuste
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="cell-right font-tabular font-weight-semibold <?php echo $isDebito ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $isDebito ? '-' : '+'; ?> R$ <?php echo number_format($val, 2, ',', '.'); ?>
                                </td>

                                <td class="cell-right font-tabular <?php echo ($saldoPos < 0) ? 'text-danger font-weight-bold' : 'text-dark'; ?>">
                                    R$ <?php echo number_format($saldoPos, 2, ',', '.'); ?>
                                </td>

                                <td class="cell-left text-secondary" style="font-size: 11px;">
                                    <div class="text-truncate" style="max-width: 320px;" title="<?php echo htmlspecialchars($h['motivo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($h['motivo'] ?? 'Sem justificativa informada', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($h['referencia_id'])): ?>
                                        <div class="text-microcopy text-muted font-tabular">
                                            Ref: <code><?php echo htmlspecialchars($h['referencia_id'], ENT_QUOTES, 'UTF-8'); ?></code>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Lançamento Manual de Contestação MED -->
<div class="modal fade" id="modalContestacaoManual" tabindex="-1" aria-labelledby="modalContestacaoManualLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2 px-3">
                <h5 class="modal-title h6 mb-0 font-weight-bold" id="modalContestacaoManualLabel">
                    Lançar Contestação Bancária Manual (MED)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/contestacoes/manual" method="POST" id="formContestacaoManual">
                <?php echo $csrfField; ?>
                <div class="modal-body p-3">
                    <div class="alert alert-warning py-2 px-3 text-microcopy mb-3">
                        <strong>Atenção:</strong> O valor será debitado da carteira familiar mesmo se o saldo for insuficiente, podendo deixar a conta negativa e aplicar bloqueio imediato de novos pedidos.
                    </div>

                    <div class="mb-3">
                        <label for="responsavel_id" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Responsável Familiar <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-sm" id="responsavel_id" name="responsavel_id" required>
                            <option value="">Selecione o responsável titular...</option>
                            <?php foreach ($todosResponsaveis as $resp): ?>
                                <option value="<?php echo (int)$resp['id']; ?>">
                                    <?php echo htmlspecialchars($resp['nome'], ENT_QUOTES, 'UTF-8'); ?> 
                                    (Saldo atual: R$ <?php echo number_format((float)($resp['saldo_atual'] ?? $resp['saldo'] ?? 0), 2, ',', '.'); ?>)
                                    <?php 
                                    $contatoResp = $resp['whatsapp'] ?? $resp['telefone'] ?? '';
                                    echo !empty($contatoResp) ? ' - ' . htmlspecialchars($contatoResp, ENT_QUOTES, 'UTF-8') : ''; 
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="valor_estorno" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Valor do Estorno (R$) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm font-tabular" 
                                   id="valor_estorno" name="valor" placeholder="0,00" required>
                        </div>
                        <div class="col-6">
                            <label for="protocolo_med" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                                Protocolo / rtrId
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" 
                                   id="protocolo_med" name="protocolo" placeholder="Ex: D000000000...">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="motivo_estorno" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Motivo / Descrição do Ofício Bancário <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="motivo_estorno" name="motivo" rows="2" 
                                  placeholder="Informe o número do processo, motivação informada pela instituição ou motivo da devolução..." required></textarea>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="notificar_whatsapp_med" name="notificar_whatsapp" value="1" checked>
                        <label class="form-check-label text-secondary" for="notificar_whatsapp_med" style="font-size: 12px;">
                            Enviar notificação automática e advertência urgente via WhatsApp
                        </label>
                    </div>
                </div>

                <div class="modal-footer p-2 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-danger btn-sm">
                        Executar Débito MED
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Quitação de Débito MED -->
<div class="modal fade" id="modalQuitacaoMed" tabindex="-1" aria-labelledby="modalQuitacaoMedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-2 px-3">
                <h5 class="modal-title h6 mb-0 font-weight-bold" id="modalQuitacaoMedLabel">
                    Quitação e Regularização de Saldo Devedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/contestacoes/quitar" method="POST" id="formQuitacaoMed">
                <?php echo $csrfField; ?>
                <input type="hidden" id="quitacao_responsavel_id" name="responsavel_id" value="">

                <div class="modal-body p-3">
                    <div class="p-2 mb-3 bg-light border rounded">
                        <div class="text-secondary text-microcopy font-weight-semibold text-uppercase">Titular Selecionado:</div>
                        <div class="font-weight-bold text-dark" id="quitacao_nome_titular">—</div>
                        <div class="text-danger font-tabular mt-1" style="font-size: 12px;">
                            Saldo devedor atual: <span id="quitacao_saldo_atual" class="font-weight-bold">R$ 0,00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="quitacao_valor" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Valor Recebido para Quitação (R$) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm font-tabular" 
                               id="quitacao_valor" name="valor" placeholder="0,00" required>
                    </div>

                    <div class="mb-3">
                        <label for="quitacao_motivo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Observações do Pagamento / Comprovante <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="quitacao_motivo" name="motivo" rows="2" 
                                  placeholder="Ex: Acordo presencial na secretaria, pagamento via TED/PIX direto, etc." required></textarea>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="quitacao_desbloquear" name="desbloquear_se_positivo" value="1" checked>
                        <label class="form-check-label text-secondary" for="quitacao_desbloquear" style="font-size: 12px;">
                            Desbloquear automaticamente a conta familiar caso o saldo fique zerado ou positivo
                        </label>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="quitacao_whatsapp" name="notificar_whatsapp" value="1" checked>
                        <label class="form-check-label text-secondary" for="quitacao_whatsapp" style="font-size: 12px;">
                            Enviar mensagem de confirmação e recibo via WhatsApp
                        </label>
                    </div>
                </div>

                <div class="modal-footer p-2 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-success btn-sm">
                        Confirmar Quitação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Desbloqueio Manual de Conta Familiar (MED) -->
<div class="modal fade" id="modalDesbloqueioMed" tabindex="-1" aria-labelledby="modalDesbloqueioMedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h5 class="modal-title h6 mb-0 font-weight-bold" id="modalDesbloqueioMedLabel">
                    Desbloqueio Manual de Conta Familiar (MED)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/contestacoes/desbloquear" method="POST" id="formDesbloqueioMed">
                <?php echo $csrfField; ?>
                <input type="hidden" id="desbloqueio_responsavel_id" name="responsavel_id" value="">

                <div class="modal-body p-3">
                    <div class="p-2 mb-3 bg-light border rounded">
                        <div class="text-secondary text-microcopy font-weight-semibold text-uppercase">Titular Selecionado:</div>
                        <div class="font-weight-bold text-dark" id="desbloqueio_nome_titular">—</div>
                        <div class="mt-1" style="font-size: 12px;">
                            Situação da carteira: <span id="desbloqueio_saldo_atual" class="font-weight-bold font-tabular">R$ 0,00</span>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 text-microcopy mb-3" id="desbloqueio_aviso_saldo">
                        <strong>Nota Operacional:</strong> A liberação manual cancela a suspensão preventiva da conta e permite que a família volte a realizar agendamentos de lanches no portal.
                    </div>

                    <div class="mb-3">
                        <label for="desbloqueio_justificativa" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                            Motivo / Justificativa do Desbloqueio <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" 
                                  id="desbloqueio_justificativa" 
                                  name="justificativa" 
                                  rows="3" 
                                  placeholder="Informe o motivo formal do desbloqueio (ex: autorização da diretoria escolar, comprovação de pagamento apresentada em análise, acordo presencial, etc.)" 
                                  required 
                                  minlength="5"></textarea>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="desbloqueio_whatsapp" name="notificar_whatsapp" value="1" checked>
                        <label class="form-check-label text-secondary" for="desbloqueio_whatsapp" style="font-size: 12px;">
                            Enviar notificação de liberação e confirmação via WhatsApp para o responsável
                        </label>
                    </div>
                </div>

                <div class="modal-footer p-2 bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">
                        Confirmar Desbloqueio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funções globais de abertura e controle dos modais corporativos
window.abrirModalContestacaoManual = function () {
    var modalEl = document.getElementById('modalContestacaoManual');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.abrirModalQuitacao = function (dados) {
    if (!dados || !dados.id) return;

    var elId = document.getElementById('quitacao_responsavel_id');
    var elTitular = document.getElementById('quitacao_nome_titular');
    var elValor = document.getElementById('quitacao_valor');
    var elSaldo = document.getElementById('quitacao_saldo_atual');

    if (elId) elId.value = dados.id;
    if (elTitular) elTitular.textContent = dados.nome || 'Responsável';

    var saldoNum = parseFloat(dados.saldo) || 0;
    var saldoFormatado = 'R$ ' + Math.abs(saldoNum).toFixed(2).replace('.', ',');
    if (saldoNum < 0) {
        saldoFormatado = '-' + saldoFormatado;
        if (elValor) elValor.value = Math.abs(saldoNum).toFixed(2);
    } else {
        if (elValor) elValor.value = '';
    }
    if (elSaldo) elSaldo.textContent = saldoFormatado;

    var modalEl = document.getElementById('modalQuitacaoMed');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};

window.abrirModalDesbloqueio = function (dados) {
    if (!dados || !dados.id) return;

    var elId = document.getElementById('desbloqueio_responsavel_id');
    var elTitular = document.getElementById('desbloqueio_nome_titular');
    var elSaldo = document.getElementById('desbloqueio_saldo_atual');
    var elJustificativa = document.getElementById('desbloqueio_justificativa');
    var elAviso = document.getElementById('desbloqueio_aviso_saldo');

    if (elId) elId.value = dados.id;
    if (elTitular) elTitular.textContent = dados.nome || 'Responsável';
    if (elJustificativa) elJustificativa.value = '';

    var saldoNum = parseFloat(dados.saldo) || 0;
    var saldoFormatado = 'R$ ' + Math.abs(saldoNum).toFixed(2).replace('.', ',');
    if (saldoNum < 0) {
        saldoFormatado = '-' + saldoFormatado;
        if (elSaldo) {
            elSaldo.textContent = saldoFormatado + ' (Saldo Devedor)';
            elSaldo.className = 'font-weight-bold font-tabular text-danger';
        }
        if (elAviso) {
            elAviso.className = 'alert alert-warning py-2 px-3 text-microcopy mb-3';
            elAviso.innerHTML = '<strong>Atenção:</strong> Esta conta possui <strong>saldo devedor de ' + saldoFormatado + '</strong>. O desbloqueio manual liberará os agendamentos escolares mesmo sem a quitação integral do débito.';
        }
    } else {
        if (elSaldo) {
            elSaldo.textContent = saldoFormatado + ' (Regular)';
            elSaldo.className = 'font-weight-bold font-tabular text-success';
        }
        if (elAviso) {
            elAviso.className = 'alert alert-info py-2 px-3 text-microcopy mb-3';
            elAviso.innerHTML = '<strong>Nota Operacional:</strong> A liberação manual cancela a suspensão preventiva da conta e permite que a família volte a realizar agendamentos de lanches no portal.';
        }
    }

    var modalEl = document.getElementById('modalDesbloqueioMed');
    if (modalEl && window.bootstrap && window.bootstrap.Modal) {
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }
};
</script>

