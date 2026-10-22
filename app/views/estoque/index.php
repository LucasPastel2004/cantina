<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Tela 6.1: Painel Geral de Saldos e Controle de Estoque
 * 
 * Conforme docs/FSD.md (Módulo 6) e docs/DESIGN.md (Anti-Slop Enterprise Edition)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$user = $user ?? (\App\Core\Auth::user() ?: []);
$isAdmin = ($user['role'] ?? $user['perfil'] ?? '') === 'administrador';
?>

<div class="container-fluid px-0">
    <!-- Cabeçalho de Módulo -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h5 fw-bold text-slate-900 mb-1">Controle de Estoque & Saldos Físicos</h1>
            <p class="text-muted small mb-0">Gestão de saldos em tempo real, movimentações físicas e pontos de reposição.</p>
        </div>
    </div>

    <!-- Stat-Strip Executivo -->
    <div class="stat-strip mb-3">
        <div class="stat-strip-item">
            <span class="stat-strip-label">Total de Itens</span>
            <span class="stat-strip-value font-tabular"><?php echo number_format($metricas['total_produtos'], 0, ',', '.'); ?></span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Unidades Físicas</span>
            <span class="stat-strip-value font-tabular"><?php echo number_format($metricas['total_unidades_fisicas'], 0, ',', '.'); ?> un</span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Entradas no Mês</span>
            <span class="stat-strip-value font-tabular text-success">+<?php echo number_format($metricas['entradas_mes'], 0, ',', '.'); ?> un</span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Perdas no Mês</span>
            <span class="stat-strip-value font-tabular text-danger">-<?php echo number_format($metricas['perdas_mes'], 0, ',', '.'); ?> un</span>
        </div>
        <div class="stat-strip-item" style="border-right: none;">
            <span class="stat-strip-label">Alerta Mínimo</span>
            <span class="stat-strip-value font-tabular <?php echo $metricas['total_alertas_minimo'] > 0 ? 'text-warning' : 'text-slate-900'; ?>">
                <?php echo $metricas['total_alertas_minimo']; ?> <?php echo $metricas['total_alertas_minimo'] === 1 ? 'item' : 'itens'; ?>
            </span>
        </div>
    </div>

    <!-- Barra de Filtros e Busca Rápida -->
    <div class="card-enterprise p-3 mb-3">
        <form method="GET" action="<?php echo $baseUrl; ?>/estoque" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar por código, produto ou categoria..." value="<?php echo htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-6 col-md-3">
                <select name="categoria_id" class="form-select form-select-sm">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ((int)($filtros['categoria_id'] ?? 0) === (int)$cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Todos os Tipos</option>
                    <option value="industrializado_continuo" <?php echo (($filtros['tipo'] ?? '') === 'industrializado_continuo') ? 'selected' : ''; ?>>Industrializados</option>
                    <option value="salgado_diario" <?php echo (($filtros['tipo'] ?? '') === 'salgado_diario') ? 'selected' : ''; ?>>Salgados Diários</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="alerta" class="form-select form-select-sm">
                    <option value="">Status Geral</option>
                    <option value="1" <?php echo (!empty($filtros['apenas_alerta'])) ? 'selected' : ''; ?>>Apenas em Alerta</option>
                </select>
            </div>
            <div class="col-6 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm w-100" title="Filtrar">
                    Filtrar
                </button>
                <?php if (!empty($filtros['busca']) || !empty($filtros['categoria_id']) || !empty($filtros['tipo']) || !empty($filtros['apenas_alerta'])): ?>
                    <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-primary btn-sm" title="Limpar Filtros">✕</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Saldos de Alta Densidade -->
    <div class="card-enterprise">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <span class="card-enterprise-title">Grade de Produtos & Inventário Físico</span>
            <span class="text-muted small">
                Exibindo <span class="font-tabular fw-semibold"><?php echo count($produtos); ?></span> de <span class="font-tabular fw-semibold"><?php echo $totalRegistros ?? count($produtos); ?></span> produtos
                <?php if (!empty($totalPaginas) && $totalPaginas > 1): ?>
                    (Pág. <span class="font-tabular"><?php echo $paginaAtual; ?></span> de <span class="font-tabular"><?php echo $totalPaginas; ?></span>)
                <?php endif; ?>
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover align-middle mb-0" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th style="width: 75px;">Código</th>
                        <th>Descrição do Produto</th>
                        <th style="width: 115px;">Categoria</th>
                        <th style="width: 95px;" class="text-center">Tipo</th>
                        <th style="width: 65px;" class="text-end">Físico</th>
                        <th style="width: 65px;" class="text-end">Reserva</th>
                        <th style="width: 75px;" class="text-end">Disponível</th>
                        <th style="width: 65px;" class="text-end">Mínimo</th>
                        <th style="width: 90px;" class="text-center">Pausa</th>
                        <th style="width: 105px;" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtos)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-secondary">
                                <p class="mb-1 fst-italic" style="font-size: 13px;">Não há nada para mostrar aqui</p>
                                <span class="text-muted small">Nenhum produto localizado para os critérios informados.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $p): ?>
                            <?php 
                            $isAlerta = (bool)$p['em_alerta_minimo']; 
                            $isIndustrializado = ($p['tipo'] === 'industrializado_continuo');
                            $disp = (int)$p['estoque_disponivel'];
                            ?>
                            <tr class="<?php echo $isAlerta ? 'bg-amber-soft' : ''; ?>">
                                <td>
                                    <code class="code-tag" style="font-size: 11px; padding: 1px 4px;"><?php echo htmlspecialchars($p['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($p['foto_url'])): ?>
                                            <img src="<?php echo $baseUrl . '/' . htmlspecialchars($p['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded" style="width: 24px; height: 24px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div style="min-width: 0;">
                                            <span class="fw-semibold text-slate-900 text-truncate d-inline-block" style="max-width: 260px;"><?php echo htmlspecialchars($p['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if ($isAlerta): ?>
                                                <span class="tag-alergeno ms-1" style="font-size: 9.5px; padding: 1px 5px;">Baixo</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-secondary small text-truncate d-inline-block" style="max-width: 110px;"><?php echo htmlspecialchars($p['categoria_nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border" style="font-size: 10.5px; font-weight: normal;">
                                        <?php echo $isIndustrializado ? 'Indust.' : 'Diário'; ?>
                                    </span>
                                </td>
                                <td class="text-end font-tabular fw-semibold">
                                    <?php echo (int)$p['estoque_atual']; ?>
                                </td>
                                <td class="text-end font-tabular text-muted">
                                    <?php echo (int)$p['estoque_reservado']; ?>
                                </td>
                                <td class="text-end font-tabular fw-bold <?php echo ($disp <= 0 && $isIndustrializado) ? 'text-danger' : ($isAlerta ? 'text-warning' : 'text-slate-900'); ?>">
                                    <?php echo $isIndustrializado ? $disp : '&mdash;'; ?>
                                </td>
                                <td class="text-end font-tabular text-muted">
                                    <?php echo $isIndustrializado ? (int)$p['estoque_minimo'] : '&mdash;'; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn-status-toggle <?php echo ($p['status_pausa'] === 'ativo') ? 'active' : 'paused'; ?>" 
                                            onclick="togglePausaProduto(<?php echo $p['id']; ?>, this)"
                                            title="Clique para alternar pausa instantânea do produto"
                                            style="padding: 2px 6px; font-size: 10.5px;">
                                        <span class="status-bullet">●</span>
                                        <span class="status-text"><?php echo ($p['status_pausa'] === 'ativo') ? 'Ativo' : 'Pausado'; ?></span>
                                    </button>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?php echo $baseUrl; ?>/estoque/perdas?produto_id=<?php echo $p['id']; ?>" 
                                            class="btn btn-enterprise btn-secondary btn-sm px-2 py-1" 
                                            title="Lançar perda deste produto" style="font-size: 11px;">
                                            Perda
                                        </a>
                                        <?php if ($isAdmin && $isIndustrializado): ?>
                                            <button type="button" 
                                                    class="btn btn-enterprise btn-secondary btn-sm px-2 py-1" 
                                                    onclick="abrirModalMinimo(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['descricao'], ENT_QUOTES, 'UTF-8'); ?>', <?php echo (int)$p['estoque_minimo']; ?>)"
                                                    title="Alterar ponto de estoque mínimo" style="font-size: 11px;">
                                                Mín.
                                            </button>
                                        <?php endif; ?>
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

<!-- Modal para Ajuste Rápido de Ponto de Estoque Mínimo (Admin) -->
<?php if ($isAdmin): ?>
<div class="modal fade" id="modalEstoqueMinimo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <form method="POST" action="<?php echo $baseUrl; ?>/estoque/minimo">
                <input type="hidden" name="csrf_token" value="<?php echo \App\Core\Session::getCsrfToken(); ?>">
                <input type="hidden" name="produto_id" id="minimoProdutoId" value="">
                <div class="modal-header py-2 px-3 border-bottom">
                    <h5 class="modal-title fs-6 fw-bold">Estoque Mínimo de Segurança</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="small text-muted mb-2" id="minimoProdutoNome">Produto</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ponto de Reposição (unidades):</label>
                        <input type="number" name="estoque_minimo" id="minimoQtdInput" class="form-control form-control-sm font-tabular" min="0" max="9999" required>
                        <div class="form-text" style="font-size: 11px;">O sistema emitirá alerta no painel quando o saldo livre atingir ou ficar abaixo deste valor.</div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 border-top bg-light">
                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Salvar Mínimo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function togglePausaProduto(produtoId, btnElement) {
    btnElement.disabled = true;
    const csrfToken = '<?php echo \App\Core\Session::getCsrfToken(); ?>';

    fetch('<?php echo $baseUrl; ?>/admin/produtos/toggle-pausa', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + encodeURIComponent(produtoId) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(r => r.json())
    .then(data => {
        btnElement.disabled = false;
        if (data.sucesso) {
            if (data.novo_status === 'ativo') {
                btnElement.className = 'btn-status-toggle active';
                btnElement.querySelector('.status-text').textContent = 'Ativo';
            } else {
                btnElement.className = 'btn-status-toggle paused';
                btnElement.querySelector('.status-text').textContent = 'Pausado';
            }
        } else {
            alert(data.mensagem || 'Falha ao alternar pausa do produto.');
        }
    })
    .catch(() => {
        btnElement.disabled = false;
        alert('Erro de comunicação ao alternar pausa do produto.');
    });
}

function abrirModalMinimo(produtoId, descricao, minimoAtual) {
    document.getElementById('minimoProdutoId').value = produtoId;
    document.getElementById('minimoProdutoNome').textContent = descricao;
    document.getElementById('minimoQtdInput').value = minimoAtual;
    const modal = new bootstrap.Modal(document.getElementById('modalEstoqueMinimo'));
    modal.show();
}
</script>
