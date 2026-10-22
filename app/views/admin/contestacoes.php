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
$jaFiltrado = (bool)($jaFiltrado ?? false);
$pagina = $pagina ?? 1;
$totalPaginas = $totalPaginas ?? 1;

$paramsMed = [];
if (!empty($filtroBusca)) {
    $paramsMed['busca'] = $filtroBusca;
}
if (!empty($filtroStatus) && $filtroStatus !== 'todos') {
    $paramsMed['status'] = $filtroStatus;
}
$queryStringMed = !empty($paramsMed) ? '?' . http_build_query($paramsMed) : '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Contestações Bancárias & Pix MED</h1>
        <p class="text-secondary text-microcopy mb-0">
            Controle de devoluções pelo Mecanismo Especial de Devolução do Banco Central, inadimplências e bloqueios.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/admin/contestacoes/imprimir<?php echo $queryStringMed; ?>" 
           target="_blank" 
           id="btnImprimirMedA4"
           class="btn btn-enterprise btn-secondary d-inline-flex align-items-center gap-1.5" 
           title="Visualizar espelho oficial para conferência e impressão / salvar PDF em nova aba">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Imprimir / Salvar PDF (A4)
        </a>
        <a href="<?php echo $baseUrl; ?>/relatorios/devedores" 
           id="btnHistoricoMedRelatorio"
           class="btn btn-enterprise btn-secondary d-inline-flex align-items-center gap-1.5" 
           title="Acessar relatório e auditoria completa de histórico de eventos e quitações MED">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.714a6.953 6.953 0 0 0-.439-.27l.493-.87a8.058 8.058 0 0 1 .507.311l-.561.829zm-7.69 0a6.953 6.953 0 0 1-.439-.27l.493-.87a8.058 8.058 0 0 0 .507.311l-.561.829zM16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-1 0A7 7 0 1 0 1 8a7 7 0 0 0 14 0zm-8-5a.5.5 0 0 1 .5.5v4.25l2.75 1.65a.5.5 0 0 1-.515.857L7 8.5V3.5A.5.5 0 0 1 7.5 3z"/>
            </svg>
            Histórico de Eventos MED
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
                <span class="h4 font-weight-bold mb-0 font-tabular text-dark">
                    <?php echo number_format($totalNegativados, 0, ',', '.'); ?>
                </span>
                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
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
                <span class="h4 font-weight-bold mb-0 font-tabular text-dark">
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
                <span class="h4 font-weight-bold mb-0 font-tabular text-dark">
                    <?php echo number_format($contasRegularizadas, 0, ',', '.'); ?>
                </span>
                <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                    Quitadas
                </span>
            </div>
            <div class="text-secondary mt-1" style="font-size: 11px;">Débitos acertados e contas liberadas</div>
        </div>
    </div>
</div>

<!-- Filtros e Busca -->
<div class="card-enterprise card-overflow-visible mb-4" style="overflow: visible !important;">
    <div class="card-enterprise-body p-3" style="overflow: visible !important;">
        <form id="formFiltrosContestacoes" action="<?php echo $baseUrl; ?>/admin/contestacoes" method="GET" class="row g-2 align-items-end" style="position: relative;">
            <input type="hidden" name="filtrar" value="1">
            <div class="col-12 col-sm-6 col-md-5 position-relative" style="position: relative;">
                <label for="busca" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Buscar Responsável ou Titular
                </label>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control form-control-sm" id="busca" name="busca" 
                           placeholder="Nome, e-mail, telefone ou dependente..." 
                           value="<?php echo htmlspecialchars($filtroBusca, ENT_QUOTES, 'UTF-8'); ?>"
                           autocomplete="off">
                    <button type="button" class="btn btn-enterprise btn-primary btn-sm" id="btnLimparBuscaInput" 
                            style="<?php echo empty($filtroBusca) ? 'display: none;' : ''; ?>" title="Limpar busca rápida">
                        &times;
                    </button>
                </div>
                <!-- Dropdown flutuante de sugestões em tempo real (flutua livremente para fora da caixa) -->
                <div id="buscaSugestoesDropdown" class="shadow-sm position-absolute rounded" 
                     style="top: calc(100% + 4px); left: 0; right: 0; z-index: 1060; max-height: 280px; overflow-y: auto; display: none; background: #ffffff; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.18), 0 8px 10px -6px rgba(15, 23, 42, 0.1) !important;">
                </div>
            </div>

            <div class="col-12 col-sm-3 col-md-3">
                <label for="status" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase;">
                    Filtrar por Status
                </label>
                <select class="form-select form-select-sm" id="status" name="status">
                    <option value="todos" <?php echo ($filtroStatus === 'todos' || empty($filtroStatus)) ? 'selected' : ''; ?>>Todos os Registros</option>
                    <option value="bloqueados" <?php echo ($filtroStatus === 'bloqueados') ? 'selected' : ''; ?>>Apenas Bloqueados MED</option>
                    <option value="negativos" <?php echo ($filtroStatus === 'negativos') ? 'selected' : ''; ?>>Saldo Devedor (< R$ 0)</option>
                    <option value="regularizados" <?php echo ($filtroStatus === 'regularizados') ? 'selected' : ''; ?>>Contas Regularizadas (Quitadas)</option>
                </select>
            </div>

            <div class="col-12 col-sm-3 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1">
                    Filtrar Registros
                </button>
                <?php if ($jaFiltrado): ?>
                    <a href="<?php echo $baseUrl; ?>/admin/contestacoes" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">
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
            <?php echo $jaFiltrado ? count($negativados) . ' exibido(s)' : '0 exibido(s)'; ?>
        </span>
    </div>

    <div class="card-enterprise-body p-0">
        <?php if (!$jaFiltrado): ?>
            <div class="p-4 text-center">
                <p class="text-secondary mb-0" style="font-size: 13px; font-style: italic;">
                    Não há nada para mostrar aqui
                </p>
                <span class="text-muted" style="font-size: 11px;">
                    Utilize os filtros acima e clique em "Filtrar Registros" para exibir as contas com bloqueio ou saldo devedor.
                </span>
            </div>
        <?php elseif (empty($negativados)): ?>
            <div class="p-4 text-center">
                <div class="mb-2 text-secondary">
                    <svg width="40" height="40" viewBox="0 0 16 16" fill="currentColor" class="text-secondary opacity-75">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <h3 class="h6 font-weight-semibold text-dark mb-1">Nenhuma inadimplência MED encontrada</h3>
                <p class="text-secondary text-microcopy mb-0">Não há famílias negativadas ou bloqueadas por contestação bancária no momento.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-enterprise mb-0" id="tabelaContestacoes">
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
                    <tbody id="tabelaContestacoesBody">
                        <?php foreach ($negativados as $fam): ?>
                            <?php 
                            $saldo = (float)($fam['saldo_atual'] ?? $fam['saldo'] ?? 0);
                            $isBloqueado = !empty($fam['bloqueado_med']);
                            $dependentes = $fam['dependentes'] ?? [];
                            $contatoFam = $fam['whatsapp'] ?? $fam['telefone'] ?? '';
                            $cpfLimpo = preg_replace('/\D/', '', $fam['cpf'] ?? '');
                            $nomesDeps = array_map(fn($d) => is_array($d) ? ($d['nome'] ?? '') : (string)$d, $dependentes);
                            $termoBuscaRow = strtolower(trim($fam['nome'] . ' ' . ($fam['email'] ?? '') . ' ' . $contatoFam . ' ' . ($fam['cpf'] ?? '') . ' ' . $cpfLimpo . ' ' . implode(' ', $nomesDeps)));
                            ?>
                            <tr class="linha-contestacao" data-busca="<?php echo htmlspecialchars($termoBuscaRow, ENT_QUOTES, 'UTF-8'); ?>" data-nome="<?php echo htmlspecialchars($fam['nome'], ENT_QUOTES, 'UTF-8'); ?>">
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

                                <td class="cell-right font-tabular font-weight-bold text-dark">
                                    R$ <?php echo number_format($saldo, 2, ',', '.'); ?>
                                </td>

                                <td class="cell-center">
                                    <?php if ($isBloqueado): ?>
                                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
                                            Bloqueado MED
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 11px;">
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

            <!-- Paginação Corporativa Oficial (Padrão Enterprise Anti-Slop) -->
            <?php 
                $paginaAtual    = (int)($paginacao['pagina_atual'] ?? $pagina ?? 1);
                $totalRegistros = (int)($paginacao['total_itens'] ?? 0);
                $totalPaginas   = (int)($paginacao['total_paginas'] ?? $totalPaginas ?? 1);
                $itensPorPagina = (int)($paginacao['itens_por_pag'] ?? 20);
                require __DIR__ . '/../partials/pagination.php'; 
            ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Lançamento Manual de Contestação MED -->
<div class="modal fade" id="modalContestacaoManual" tabindex="-1" aria-labelledby="modalContestacaoManualLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2.5 px-3 border-bottom bg-light">
                <h5 class="modal-title h6 mb-0 font-weight-bold text-dark" id="modalContestacaoManualLabel">
                    Lançar Contestação Bancária Manual (MED)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
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
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">
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
            <div class="modal-header py-2.5 px-3 border-bottom bg-light">
                <h5 class="modal-title h6 mb-0 font-weight-bold text-dark" id="modalQuitacaoMedLabel">
                    Quitação e Regularização de Saldo Devedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form action="<?php echo $baseUrl; ?>/admin/contestacoes/quitar" method="POST" id="formQuitacaoMed">
                <?php echo $csrfField; ?>
                <input type="hidden" id="quitacao_responsavel_id" name="responsavel_id" value="">

                <div class="modal-body p-3">
                    <div class="p-2 mb-3 bg-light border rounded">
                        <div class="text-secondary text-microcopy font-weight-semibold text-uppercase">Titular Selecionado:</div>
                        <div class="font-weight-bold text-dark" id="quitacao_nome_titular">—</div>
                        <div class="text-secondary font-tabular mt-1" style="font-size: 12px;">
                            Saldo devedor atual: <span id="quitacao_saldo_atual" class="font-weight-bold text-dark">R$ 0,00</span>
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
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">
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
            <div class="modal-header py-2.5 px-3 border-bottom bg-light">
                <h5 class="modal-title h6 mb-0 font-weight-bold text-dark" id="modalDesbloqueioMedLabel">
                    Desbloqueio Manual de Conta Familiar (MED)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
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
            elSaldo.className = 'font-weight-bold font-tabular text-dark';
        }
        if (elAviso) {
            elAviso.className = 'alert alert-warning py-2 px-3 text-microcopy mb-3';
            elAviso.innerHTML = '<strong>Atenção:</strong> Esta conta possui <strong>saldo devedor de ' + saldoFormatado + '</strong>. O desbloqueio manual liberará os agendamentos escolares mesmo sem a quitação integral do débito.';
        }
    } else {
        if (elSaldo) {
            elSaldo.textContent = saldoFormatado + ' (Regular)';
            elSaldo.className = 'font-weight-bold font-tabular text-dark';
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

// =========================================================================
// AUTOCOMPLETE DE SUGESTÕES E FILTRO LIVE PARA CONTESTAÇÕES MED
// =========================================================================
(function() {
    var inputBusca = document.getElementById('busca');
    var dropdown = document.getElementById('buscaSugestoesDropdown');
    var btnLimpar = document.getElementById('btnLimparBuscaInput');
    var selectStatus = document.getElementById('status');
    var formFiltros = document.getElementById('formFiltrosContestacoes');
    var tabelaBody = document.getElementById('tabelaContestacoesBody');
    var baseUrl = '<?php echo $baseUrl; ?>';

    if (!inputBusca || !dropdown) return;

    var debounceTimer = null;
    var itemAtivoIndex = -1;
    var sugestoesAtuais = [];

    // Auto-submete ao alterar o status do filtro
    if (selectStatus && formFiltros) {
        selectStatus.addEventListener('change', function() {
            formFiltros.submit();
        });
    }

    // Botão rápido para limpar o campo de busca
    if (btnLimpar) {
        btnLimpar.addEventListener('click', function() {
            inputBusca.value = '';
            btnLimpar.style.display = 'none';
            fecharDropdown();
            filtrarTabelaClientSide('');
            inputBusca.focus();
        });
    }

    // Filtragem instantânea client-side nas linhas visíveis da tabela
    function filtrarTabelaClientSide(termo) {
        if (!tabelaBody) return;
        var termoNorm = termo.toLowerCase().trim();
        var linhas = tabelaBody.querySelectorAll('tr.linha-contestacao');
        var visiveis = 0;

        linhas.forEach(function(linha) {
            var dataBusca = (linha.getAttribute('data-busca') || '').toLowerCase();
            if (!termoNorm || dataBusca.indexOf(termoNorm) !== -1) {
                linha.style.display = '';
                visiveis++;
            } else {
                linha.style.display = 'none';
            }
        });

        // Alerta de nenhum resultado na página corrente
        var msgLinha = document.getElementById('linhaSemResultadosFiltro');
        if (termoNorm && visiveis === 0 && linhas.length > 0) {
            if (!msgLinha) {
                msgLinha = document.createElement('tr');
                msgLinha.id = 'linhaSemResultadosFiltro';
                msgLinha.innerHTML = '<td colspan="6" class="p-3 text-center text-secondary text-microcopy">' +
                    'Nenhum registro nesta página corresponde a "<strong>' + escapeHtml(termo) + '</strong>". ' +
                    'Pressione <strong>Enter</strong> para consultar em todo o cadastro ou selecione uma sugestão acima.' +
                    '</td>';
                tabelaBody.appendChild(msgLinha);
            } else {
                msgLinha.innerHTML = '<td colspan="6" class="p-3 text-center text-secondary text-microcopy">' +
                    'Nenhum registro nesta página corresponde a "<strong>' + escapeHtml(termo) + '</strong>". ' +
                    'Pressione <strong>Enter</strong> para consultar em todo o cadastro ou selecione uma sugestão acima.' +
                    '</td>';
                msgLinha.style.display = '';
            }
        } else if (msgLinha) {
            msgLinha.style.display = 'none';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function fecharDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        itemAtivoIndex = -1;
        sugestoesAtuais = [];
    }

    function destacarItem(index) {
        var itens = dropdown.querySelectorAll('.sugestao-med-item');
        itens.forEach(function(el, idx) {
            if (idx === index) {
                el.style.backgroundColor = '#f1f5f9';
                el.setAttribute('aria-selected', 'true');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.style.backgroundColor = '#ffffff';
                el.setAttribute('aria-selected', 'false');
            }
        });
    }

    function selecionarSugestao(item) {
        if (!item) return;
        inputBusca.value = item.nome;
        fecharDropdown();
        if (btnLimpar) btnLimpar.style.display = '';
        // Submete o formulário com o responsável selecionado para sincronizar filtros e paginação
        if (formFiltros) {
            formFiltros.submit();
        }
    }

    function buscarSugestoes(termo) {
        if (!termo || termo.length < 1) {
            fecharDropdown();
            return;
        }

        dropdown.innerHTML = '<div class="p-2 text-center text-secondary text-microcopy">' +
            '<span class="spinner-border spinner-border-sm me-1 text-primary" role="status"></span> Buscando titulares e alunos...</div>';
        dropdown.style.display = 'block';

        fetch(baseUrl + '/admin/contestacoes/sugestoes?q=' + encodeURIComponent(termo))
            .then(function(res) {
                if (!res.ok) throw new Error('Falha na requisição');
                return res.json();
            })
            .then(function(data) {
                var lista = data.sugestoes || [];
                sugestoesAtuais = lista;
                itemAtivoIndex = -1;

                if (lista.length === 0) {
                    dropdown.innerHTML = '<div class="p-2 text-center text-secondary text-microcopy">' +
                        'Nenhum titular ou dependente localizado para "' + escapeHtml(termo) + '".</div>';
                    dropdown.style.display = 'block';
                    return;
                }

                var html = '';
                lista.forEach(function(item, idx) {
                    var saldoNum = parseFloat(item.saldo) || 0;
                    var saldoCor = 'text-dark';

                    html += '<div class="sugestao-med-item px-3 py-2 border-bottom text-start" data-idx="' + idx + '" role="option" tabindex="0" style="cursor: pointer; background: #ffffff;">' +
                        '<div class="d-flex justify-content-between align-items-center mb-1">' +
                            '<div>' +
                                '<span class="font-weight-semibold text-dark font-13">' + escapeHtml(item.nome) + '</span>' +
                                '<span class="' + (item.badge_class || 'badge bg-light text-secondary border') + ' ms-2 px-2 py-0" style="font-size: 10px;">' + escapeHtml(item.badge) + '</span>' +
                            '</div>' +
                            '<span class="font-tabular font-weight-bold font-12 ' + saldoCor + '">' + escapeHtml(item.saldo_fmt) + '</span>' +
                        '</div>' +
                        '<div class="d-flex justify-content-between text-secondary text-microcopy">' +
                            '<span>CPF: <strong class="font-tabular">' + escapeHtml(item.cpf) + '</strong> &bull; ' + escapeHtml(item.contato || 'Sem contato') + '</span>' +
                            '<span class="text-truncate ms-2" style="max-width: 180px;">Alunos: ' + escapeHtml(item.dependentes) + '</span>' +
                        '</div>' +
                    '</div>';
                });

                dropdown.innerHTML = html;
                dropdown.style.display = 'block';

                // Eventos de clique e hover
                dropdown.querySelectorAll('.sugestao-med-item').forEach(function(el) {
                    el.addEventListener('mouseenter', function() {
                        var idx = parseInt(el.getAttribute('data-idx'), 10);
                        itemAtivoIndex = idx;
                        destacarItem(idx);
                    });
                    el.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        var idx = parseInt(el.getAttribute('data-idx'), 10);
                        selecionarSugestao(sugestoesAtuais[idx]);
                    });
                });
            })
            .catch(function(err) {
                dropdown.innerHTML = '<div class="p-2 text-center text-secondary text-microcopy">Erro ao carregar sugestões.</div>';
                dropdown.style.display = 'block';
            });
    }

    // Digitação no input
    inputBusca.addEventListener('input', function() {
        var termo = inputBusca.value;
        if (btnLimpar) {
            btnLimpar.style.display = termo.trim() ? '' : 'none';
        }

        // Live-filtering imediato na tabela
        filtrarTabelaClientSide(termo);

        // Busca assíncrona debounced para sugestões
        clearTimeout(debounceTimer);
        if (!termo.trim()) {
            fecharDropdown();
            return;
        }

        debounceTimer = setTimeout(function() {
            buscarSugestoes(termo.trim());
        }, 180);
    });

    // Teclado no input de busca
    inputBusca.addEventListener('keydown', function(e) {
        var total = sugestoesAtuais.length;

        if (dropdown.style.display === 'block' && total > 0) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                itemAtivoIndex = (itemAtivoIndex + 1) >= total ? 0 : itemAtivoIndex + 1;
                destacarItem(itemAtivoIndex);
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                itemAtivoIndex = (itemAtivoIndex - 1) < 0 ? total - 1 : itemAtivoIndex - 1;
                destacarItem(itemAtivoIndex);
                return;
            }
            if (e.key === 'Enter') {
                if (itemAtivoIndex >= 0 && itemAtivoIndex < total) {
                    e.preventDefault();
                    selecionarSugestao(sugestoesAtuais[itemAtivoIndex]);
                    return;
                }
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                fecharDropdown();
                return;
            }
        }
    });

    // Fecha ao clicar fora
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== inputBusca && e.target !== btnLimpar) {
            fecharDropdown();
        }
    });
})();
</script>

