<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Interface: Ponto de Venda (PDV) e Operação de Balcão
 * 
 * Conforme docs/FSD.md (Área 4, Módulo 5), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$sessaoAberta = $sessaoAberta ?? null;
$turnoAtual = $turnoAtual ?? 'matutino';
$sessaoOutroTurno = $sessaoOutroTurno ?? null;
$outroTurnoNome = $outroTurnoNome ?? '';
$categorias = $categorias ?? [];
$produtos = $produtos ?? [];
$vendasRecentes = $vendasRecentes ?? [];
$metricas = $metricas ?? [
    'total_vendas'   => 0,
    'total_faturado' => 0.0,
    'total_dinheiro' => 0.0,
    'total_pix'      => 0.0,
    'total_folha'    => 0.0
];

$caixaAberto = ($sessaoAberta !== null);
$csrfToken = \App\Core\Session::generateCsrfToken();
?>

<style>
/* ==========================================================================
   Ponto de Venda (PDV) — Scoped Enterprise POS Styling
   ========================================================================== */
.pdv-wrapper {
    display: flex;
    flex-direction: column;
    width: 100%;
}

.pdv-wrapper .stat-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 16px;
    gap: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.pdv-wrapper .stat-strip-item {
    display: flex;
    flex-direction: column;
    padding-right: 16px;
    border-right: 1px solid #f1f5f9;
}

.pdv-wrapper .stat-strip-item:last-child {
    border-right: none;
    margin-left: auto;
    padding-right: 0;
}

.pdv-wrapper .stat-strip-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    margin-bottom: 2px;
}

.pdv-wrapper .stat-strip-value {
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
}

/* Grid de 2 Colunas (Terminal POS) */
.pdv-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 16px;
    align-items: start;
    width: 100%;
}

@media (min-width: 1440px) {
    .pdv-grid {
        grid-template-columns: 1fr 440px;
        gap: 20px;
    }
}

@media (max-width: 960px) {
    .pdv-grid {
        grid-template-columns: 1fr;
    }
}

/* Coluna Esquerda: Catálogo */
.pdv-catalog {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.pdv-catalog-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}

.pdv-category-nav {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.pdv-cat-btn {
    appearance: none;
    -webkit-appearance: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 14px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    color: #334155;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
    user-select: none;
    line-height: 1.2;
}

.pdv-cat-btn:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a;
}

.pdv-cat-btn.active {
    background-color: #1d4ed8 !important;
    border-color: #1e40af !important;
    color: #ffffff !important;
    box-shadow: 0 1px 3px rgba(29, 78, 216, 0.25);
}

.pdv-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
    gap: 12px;
    max-height: calc(100vh - 230px);
    overflow-y: auto;
    padding-right: 4px;
}

.pdv-prod-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 165px;
    position: relative;
    transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.pdv-prod-card:hover:not(.disabled) {
    border-color: #94a3b8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

.pdv-prod-card:active:not(.disabled) {
    transform: translateY(0) scale(0.99);
}

.pdv-prod-card.disabled {
    opacity: 0.45;
    cursor: not-allowed;
    background-color: #f8fafc;
    border-style: dashed;
}

.pdv-prod-card .prod-thumb-box {
    width: 100%;
    height: 75px;
    border-radius: 6px;
    background-color: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    margin-bottom: 8px;
    border: 1px solid #f1f5f9;
}

.pdv-prod-card .prod-thumb-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pdv-prod-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.3;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 34px;
}

.tag-alergeno {
    display: inline-block;
    padding: 1px 5px;
    font-size: 10px;
    font-weight: 600;
    background-color: #fffbeb;
    border: 1px solid #fde68a;
    color: #92400e;
    border-radius: 4px;
    margin-bottom: 4px;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pdv-prod-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid #f8fafc;
}

.pdv-prod-price {
    font-size: 14px;
    font-weight: 700;
    color: #1d4ed8;
}

.pdv-prod-stock {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
}

/* Coluna Direita: Cupom de Venda / Painel do Caixa */
.pdv-cart-panel {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 145px);
    min-height: 580px;
    position: sticky;
    top: 70px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}

.pdv-cart-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    background-color: #f8fafc;
    border-radius: 8px 8px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pdv-cart-body {
    flex: 1;
    overflow-y: auto;
    padding: 10px 14px;
}

.pdv-cart-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    gap: 8px;
}

.pdv-cart-item-title {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.25;
}

.pdv-cart-item-desc {
    font-size: 11px;
    color: #64748b;
}

.pdv-cart-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

.pdv-qty-btn {
    width: 26px;
    height: 26px;
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    border-radius: 4px;
    font-weight: 700;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #1e293b;
    user-select: none;
    transition: all 0.1s ease;
}

.pdv-qty-btn:hover {
    background-color: #f1f5f9;
    border-color: #94a3b8;
}

.pdv-qty-btn:active {
    transform: scale(0.95);
}

.pdv-cart-footer {
    border-top: 1px solid #e2e8f0;
    padding: 12px 14px;
    background-color: #ffffff;
    border-radius: 0 0 8px 8px;
}

.pdv-total-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 10px;
}

.pdv-total-label {
    font-size: 12px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.pdv-total-val {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.pdv-pay-methods {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    margin-bottom: 10px;
}

.pdv-pay-btn {
    border: 1px solid #cbd5e1;
    background-color: #ffffff;
    padding: 8px 4px;
    border-radius: 6px;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s ease;
    user-select: none;
    font-size: 12px;
    color: #1e293b;
}

.pdv-pay-btn:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
}

.pdv-pay-btn.active {
    border-color: #1d4ed8;
    background-color: #eff6ff;
    color: #1d4ed8;
    font-weight: 700;
    box-shadow: 0 0 0 1px #1d4ed8;
}

.pdv-shortcut-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background-color: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 1px 5px;
    font-size: 10px;
    font-weight: 700;
    color: #475569;
    font-family: monospace;
    margin-left: 4px;
    line-height: 1.2;
}

/* Modais Customizados do PDV */
.modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(2px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1060;
    padding: 16px;
}

.modal-backdrop-custom.d-none {
    display: none !important;
}

.modal-card-custom {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    border: 1px solid #cbd5e1;
    width: 100%;
    max-width: 520px;
    overflow: hidden;
    animation: modalScaleIn 0.15s ease-out;
}

@keyframes modalScaleIn {
    from { opacity: 0; transform: scale(0.96); }
    to { opacity: 1; transform: scale(1); }
}

.modal-header-custom {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: #f8fafc;
}

.modal-title-custom {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
}

.modal-close-custom {
    background: none;
    border: none;
    color: #64748b;
    font-size: 16px;
    cursor: pointer;
    padding: 4px;
    line-height: 1;
}

.modal-close-custom:hover {
    color: #0f172a;
}

.modal-body-custom {
    padding: 16px;
}

.modal-footer-custom {
    padding: 10px 16px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    background-color: #f8fafc;
}
</style>

<div class="pdv-wrapper">
    <!-- Stat-Strip Executiva & Status do Caixa -->
    <div class="stat-strip mb-3">
        <div class="stat-strip-item">
            <span class="stat-strip-label">Status da Sessão</span>
            <span class="stat-strip-value">
                <?php if ($caixaAberto): ?>
                    <span class="status-indicator status-active"><span class="bullet">●</span> Caixa Aberto (Turno <?php echo ucfirst($sessaoAberta['turno']); ?> #<?php echo (int)$sessaoAberta['id']; ?>)</span>
                <?php else: ?>
                    <span class="status-indicator status-inactive"><span class="bullet">●</span> Caixa Fechado (Turno <?php echo ucfirst($turnoAtual); ?>)</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Fundo Inicial</span>
            <span class="stat-strip-value font-tabular">
                R$ <?php echo number_format((float)($sessaoAberta['fundo_troco_inicial'] ?? 0.0), 2, ',', '.'); ?>
            </span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Vendas Hoje</span>
            <span class="stat-strip-value font-tabular">
                <?php echo (int)$metricas['total_vendas']; ?> ped. (R$ <?php echo number_format((float)$metricas['total_faturado'], 2, ',', '.'); ?>)
            </span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Em Espécie (Balcão)</span>
            <span class="stat-strip-value font-tabular text-success">
                R$ <?php echo number_format((float)$metricas['total_dinheiro'], 2, ',', '.'); ?>
            </span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Pix Balcão</span>
            <span class="stat-strip-value font-tabular text-primary">
                R$ <?php echo number_format((float)$metricas['total_pix'], 2, ',', '.'); ?>
            </span>
        </div>
        <div class="stat-strip-item">
            <span class="stat-strip-label">Desconto em Folha</span>
            <span class="stat-strip-value font-tabular text-muted">
                R$ <?php echo number_format((float)$metricas['total_folha'], 2, ',', '.'); ?>
            </span>
        </div>
        <div class="stat-strip-item d-flex align-items-center gap-2">
            <!-- Alternador de Turno -->
            <div class="btn-group btn-group-sm me-1" role="group" aria-label="Seletor de Turno PDV">
                <a href="<?php echo $baseUrl; ?>/operacional/pdv?turno=matutino" class="btn btn-enterprise <?php echo ($turnoAtual === 'matutino') ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 11px; padding: 4px 8px;">Manhã</a>
                <a href="<?php echo $baseUrl; ?>/operacional/pdv?turno=vespertino" class="btn btn-enterprise <?php echo ($turnoAtual === 'vespertino') ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 11px; padding: 4px 8px;">Tarde</a>
            </div>
            <?php if (!$caixaAberto): ?>
                <button type="button" class="btn btn-enterprise btn-primary btn-sm" onclick="abrirModal('modalAbrirCaixa')">
                    Abrir Caixa <?php echo ucfirst($turnoAtual); ?>
                </button>
            <?php endif; ?>
            <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo urlencode($turnoAtual); ?>" class="btn btn-enterprise btn-secondary btn-sm" title="Gestão e Fechamento de Caixa [F7]">
                Caixa <span class="pdv-shortcut-tag">F7</span>
            </a>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="abrirModal('modalEstornoBalcao')" title="Estorno de Ticket [F8]">
                Estorno <span class="pdv-shortcut-tag">F8</span>
            </button>
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="abrirModalSobras()" title="Aproveitamento de Sobras do Recreio [F9]">
                Sobras <span class="pdv-shortcut-tag">F9</span>
            </button>
        </div>
    </div>

    <!-- Alerta Operacional: Sessão do Outro Turno ainda Aberta -->
    <?php if ($sessaoOutroTurno): ?>
        <div class="alert alert-info d-flex align-items-center justify-content-between mb-3 py-2 px-3" style="font-size: 12px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 6px;">
            <div class="d-flex align-items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" style="flex-shrink: 0;"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/></svg>
                <span><strong>Alerta Operacional:</strong> A sessão de caixa do turno <strong><?php echo $outroTurnoNome; ?> (Sessão #<?php echo (int)$sessaoOutroTurno['id']; ?>)</strong> ainda está aberta. Recomendamos realizar a prestação de contas na tela de Caixa.</span>
            </div>
            <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo strtolower($outroTurnoNome); ?>" class="btn btn-enterprise btn-secondary btn-sm" style="font-size: 11px;">
                Conferir Caixa <?php echo $outroTurnoNome; ?> →
            </a>
        </div>
    <?php endif; ?>

    <!-- Alerta de Caixa Fechado para o Turno Atual -->
    <?php if (!$caixaAberto): ?>
        <div class="alert alert-enterprise alert-warning d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.17.17 0 0 1-.066.017H1.146a.17.17 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                <span><strong>Atenção:</strong> A sessão de caixa do turno <strong><?php echo ucfirst($turnoAtual); ?></strong> está fechada. Vendas em dinheiro físico exigem a abertura prévia da sessão de caixa.</span>
            </div>
            <button type="button" class="btn btn-enterprise btn-primary btn-sm" onclick="abrirModal('modalAbrirCaixa')">
                Abrir Caixa do Turno <?php echo ucfirst($turnoAtual); ?>
            </button>
        </div>
    <?php endif; ?>

    <!-- Grid Principal do PDV -->
    <div class="pdv-grid">
        <!-- Coluna Esquerda: Catálogo e Seleção Rápida -->
        <div class="pdv-catalog">
            <div class="pdv-catalog-header">
                <!-- Filtro por Categoria -->
                <div class="pdv-category-nav" id="pdvCategoryNav">
                    <button type="button" class="pdv-cat-btn active" data-cat="todas" onclick="filtrarCategoria('todas', this)">
                        Todas as Categorias
                    </button>
                    <?php foreach ($categorias as $cat): ?>
                        <button type="button" class="pdv-cat-btn" data-cat="<?php echo (int)$cat['id']; ?>" onclick="filtrarCategoria(<?php echo (int)$cat['id']; ?>, this)">
                            <?php echo htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Campo de Busca Rápida -->
                <div style="min-width: 240px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="#64748b"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                        </span>
                        <input type="text" id="pdvBuscaInput" class="form-control border-start-0" placeholder="Buscar produto... (F3)" autocomplete="off" oninput="filtrarTexto(this.value)">
                    </div>
                </div>
            </div>

            <!-- Grade de Produtos -->
            <div class="pdv-products-grid" id="pdvProductsGrid">
                <?php foreach ($produtos as $prod): ?>
                    <?php 
                    $esgotado = (bool)$prod['esgotado'];
                    $estoque = (int)$prod['estoque_atual'];
                    $precoFormatado = number_format((float)$prod['preco_venda'], 2, ',', '.');
                    ?>
                    <div class="pdv-prod-card <?php echo $esgotado ? 'disabled' : ''; ?>"
                         data-id="<?php echo (int)$prod['id']; ?>"
                         data-codigo="<?php echo htmlspecialchars($prod['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?>"
                         data-nome="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                         data-preco="<?php echo (float)$prod['preco_venda']; ?>"
                         data-categoria="<?php echo (int)$prod['categoria_id']; ?>"
                         data-estoque="<?php echo $estoque; ?>"
                         data-alergenos="<?php echo htmlspecialchars($prod['alergenos_ids'] ? implode(',', $prod['alergenos_ids']) : '', ENT_QUOTES, 'UTF-8'); ?>"
                         data-alergenos-nomes="<?php echo htmlspecialchars($prod['alergenos_nomes'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                         onclick="adicionarAoCarrinho(<?php echo (int)$prod['id']; ?>)">
                        
                        <div class="prod-thumb-box">
                            <?php if (!empty($prod['foto_url'])): ?>
                                <img src="<?php echo $baseUrl . '/' . htmlspecialchars($prod['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                            <?php else: ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                                </svg>
                            <?php endif; ?>
                        </div>

                        <div>
                            <div class="pdv-prod-title" title="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php if (!empty($prod['alergenos_nomes'])): ?>
                                <span class="tag-alergeno" title="Contém alérgenos"><?php echo htmlspecialchars($prod['alergenos_nomes'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="pdv-prod-footer">
                            <span class="pdv-prod-price font-tabular">R$ <?php echo $precoFormatado; ?></span>
                            <span class="pdv-prod-stock font-tabular">
                                <?php if ($esgotado): ?>
                                    <span class="text-danger font-semibold">Esgotado</span>
                                <?php else: ?>
                                    <?php echo $estoque; ?> un
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Coluna Direita: Cupom de Venda Ativo -->
        <div class="pdv-cart-panel" id="pdvCartPanel">
            <div class="pdv-cart-header">
                <div>
                    <strong style="font-size: 14px; color: #0f172a;">Cupom de Venda</strong>
                    <span class="badge bg-secondary font-tabular ms-1" id="cartItemCount">0 itens</span>
                </div>
                <button type="button" class="btn btn-link text-danger p-0 font-12" onclick="limparCarrinho()" title="Limpar Cesta [F2]">
                    Limpar <span class="pdv-shortcut-tag">F2</span>
                </button>
            </div>

            <!-- Identificação do Cliente (Anônimo / Aluno / Colaborador) -->
            <div class="px-3 pt-2 pb-2 bg-light border-bottom">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="font-11 text-uppercase text-secondary font-semibold">Identificação do Cliente</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="clienteTipo" id="tipoAnonimo" value="anonimo" checked onchange="trocarTipoCliente('anonimo')">
                        <label class="btn btn-outline-secondary py-0 px-2 font-11" for="tipoAnonimo">Anônimo</label>

                        <input type="radio" class="btn-check" name="clienteTipo" id="tipoAluno" value="aluno" onchange="trocarTipoCliente('aluno')">
                        <label class="btn btn-outline-secondary py-0 px-2 font-11" for="tipoAluno">Aluno</label>

                        <input type="radio" class="btn-check" name="clienteTipo" id="tipoColaborador" value="colaborador" onchange="trocarTipoCliente('colaborador')">
                        <label class="btn btn-outline-secondary py-0 px-2 font-11" for="tipoColaborador">Colaborador</label>
                    </div>
                </div>

                <!-- Painel Aluno -->
                <div id="boxClienteAluno" class="d-none mt-2">
                    <div class="input-group input-group-sm">
                        <input type="text" id="buscaAlunoPdv" class="form-control" placeholder="Digite nome ou matrícula do aluno..." oninput="buscarAlunosLive(this.value)">
                        <button type="button" class="btn btn-outline-secondary" onclick="limparSelecaoAluno()">✕</button>
                    </div>
                    <div id="listaSugestoesAluno" class="list-group position-absolute shadow-sm d-none" style="z-index: 1050; max-width: 380px;"></div>
                    <div id="alunoSelecionadoCard" class="d-none mt-1 p-2 bg-white border rounded">
                        <div class="d-flex justify-content-between">
                            <strong id="alunoSelNome" class="font-12"></strong>
                            <span id="alunoSelTurma" class="code-tag"></span>
                        </div>
                        <div id="alunoSelAlergias" class="mt-1 font-11 text-danger"></div>
                    </div>
                </div>

                <!-- Painel Colaborador -->
                <div id="boxClienteColaborador" class="d-none mt-2">
                    <div class="input-group input-group-sm">
                        <input type="text" id="buscaColabPdv" class="form-control" placeholder="Buscar professor ou funcionário..." oninput="buscarColaboradoresLive(this.value)">
                        <button type="button" class="btn btn-outline-secondary" onclick="limparSelecaoColaborador()">✕</button>
                    </div>
                    <div id="listaSugestoesColab" class="list-group position-absolute shadow-sm d-none" style="z-index: 1050; max-width: 380px;"></div>
                    <div id="colabSelecionadoCard" class="d-none mt-2 p-2 bg-white border rounded">
                        <div class="d-flex align-items-center gap-2">
                            <img id="colabSelFoto" src="<?php echo $baseUrl; ?>/assets/img/avatar-placeholder.png" width="44" height="44" class="rounded border" style="object-fit: cover;">
                            <div class="flex-grow-1">
                                <div id="colabSelNome" class="font-12 font-semibold"></div>
                                <div id="colabSelCargo" class="font-11 text-secondary"></div>
                                <div class="font-10 text-muted">Matrícula: <span id="colabSelMat" class="font-tabular font-semibold"></span></div>
                            </div>
                        </div>
                        <div class="form-check mt-2 pt-1 border-top">
                            <input class="form-check-input" type="checkbox" id="chkConfirmaVisual" onchange="atualizarBotaoDescontoFolha()">
                            <label class="form-check-label font-11 text-dark" for="chkConfirmaVisual">
                                <strong>Confirmo a identidade do colaborador pela foto institucional</strong>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Itens do Carrinho -->
            <div class="pdv-cart-body" id="pdvCartBody">
                <div id="cartEmptyState" class="text-center py-4 text-muted">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" class="mb-2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <div class="font-12">Cesta de compras vazia.</div>
                    <div class="font-11 text-secondary">Selecione os produtos ao lado para iniciar.</div>
                </div>
                <div id="cartItemsList"></div>
            </div>

            <!-- Rodapé e Pagamento -->
            <div class="pdv-cart-footer">
                <div class="pdv-total-row">
                    <span class="pdv-total-label">Total a Pagar</span>
                    <span class="pdv-total-val font-tabular text-primary" id="cartTotalDisplay">R$ 0,00</span>
                </div>

                <!-- Botões de Forma de Pagamento -->
                <div class="pdv-pay-methods">
                    <div class="pdv-pay-btn active" id="btnPayDinheiro" onclick="selecionarFormaPagamento('dinheiro')">
                        <div><strong>Dinheiro</strong></div>
                        <span class="font-10 text-muted">Troco auto</span>
                    </div>
                    <div class="pdv-pay-btn" id="btnPayPix" onclick="selecionarFormaPagamento('pix_balcao')">
                        <div><strong>Pix Balcão</strong></div>
                        <span class="font-10 text-muted">QR Code</span>
                    </div>
                    <div class="pdv-pay-btn" id="btnPayFolha" onclick="selecionarFormaPagamento('desconto_folha')" style="opacity: 0.5; pointer-events: none;">
                        <div><strong>Em Folha</strong></div>
                        <span class="font-10 text-muted">Colaborador</span>
                    </div>
                </div>

                <!-- Detalhes de Pagamento em Dinheiro -->
                <div id="payDetailsDinheiro" class="mb-2">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <label class="font-11 text-secondary text-uppercase font-semibold mb-0" style="min-width: 95px;">Valor Recebido:</label>
                        <input type="text" id="inputValorRecebido" class="form-control form-control-sm font-tabular text-end input-money" placeholder="R$ 0,00" oninput="calcularTroco()">
                    </div>
                    <div class="d-flex gap-1 mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-11 py-1" onclick="definirRecebidoExato()">Exato</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-11 py-1 font-tabular" onclick="somarRecebido(10)">+10</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-11 py-1 font-tabular" onclick="somarRecebido(20)">+20</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill font-11 py-1 font-tabular" onclick="somarRecebido(50)">+50</button>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border">
                        <span class="font-12 font-semibold text-secondary">Troco Sugerido:</span>
                        <span class="font-14 font-tabular font-bold text-success" id="displayTroco">R$ 0,00</span>
                    </div>
                </div>

                <!-- Botão Principal de Conclusão -->
                <button type="button" id="btnFinalizarVenda" class="btn btn-enterprise btn-success w-100 py-2 font-14 font-semibold" onclick="processarFinalizacaoVenda()" <?php echo !$caixaAberto ? 'disabled' : ''; ?>>
                    Concluir Venda <span class="pdv-shortcut-tag">F4</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================
     MODAIS OPERACIONAIS DO PDV
     ======================================================================== -->

<!-- Modal: Abertura Rápida de Caixa -->
<div class="modal-backdrop-custom d-none" id="modalAbrirCaixa">
    <div class="modal-card-custom" style="max-width: 420px;">
        <div class="modal-header-custom">
            <h2 class="modal-title-custom">Abertura de Caixa do Turno</h2>
            <button type="button" class="modal-close-custom" onclick="fecharModal('modalAbrirCaixa')">✕</button>
        </div>
        <form action="<?php echo $baseUrl; ?>/operacional/pdv/caixa/abrir" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="modal-body-custom">
                <div class="mb-3">
                    <label class="form-label font-12 font-semibold">Turno de Operação</label>
                    <select name="turno" class="form-select form-select-sm">
                        <option value="matutino" <?php echo ($turnoAtual === 'matutino') ? 'selected' : ''; ?>>Matutino (Manhã)</option>
                        <option value="vespertino" <?php echo ($turnoAtual === 'vespertino') ? 'selected' : ''; ?>>Vespertino (Tarde)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-12 font-semibold">Fundo de Troco Inicial (Gaveta)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">R$</span>
                        <input type="text" name="fundo_troco_inicial" class="form-control font-tabular input-money" value="50,00" required>
                    </div>
                    <div class="form-text font-11 text-muted">Informe o valor físico em dinheiro alocado na gaveta no início do turno.</div>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="fecharModal('modalAbrirCaixa')">Cancelar</button>
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm">Confirmar Abertura</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Estorno de Balcão [F8] -->
<div class="modal-backdrop-custom d-none" id="modalEstornoBalcao">
    <div class="modal-card-custom" style="max-width: 480px;">
        <div class="modal-header-custom">
            <h2 class="modal-title-custom text-danger">Estorno Manual no Balcão [F8]</h2>
            <button type="button" class="modal-close-custom" onclick="fecharModal('modalEstornoBalcao')">✕</button>
        </div>
        <form action="<?php echo $baseUrl; ?>/operacional/pdv/estorno" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="modal-body-custom">
                <p class="font-12 text-secondary mb-3">
                    O estorno de balcão cancela o ticket do lanche e <strong>credita 100% do valor integral de volta na carteira digital da família no mesmo instante</strong>.
                </p>
                <div class="mb-3">
                    <label class="form-label font-12 font-semibold">Código do Ticket de Papel</label>
                    <input type="text" name="codigo_ticket" class="form-control form-control-sm font-monospace text-uppercase" placeholder="Ex: TCK-2026-..." required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label font-12 font-semibold">Motivo do Estorno</label>
                    <select name="motivo" class="form-select form-select-sm" required>
                        <option value="">Selecione o motivo padronizado...</option>
                        <option value="Falta de Ingrediente/Produto">Falta de Ingrediente/Produto</option>
                        <option value="Recusa de Substituição">Recusa de Substituição</option>
                        <option value="Avaria no Lanche">Avaria no Lanche / Alimento impróprio</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label font-12 font-semibold">Justificativa Operacional (mínimo 5 caracteres)</label>
                    <textarea name="justificativa" class="form-control form-control-sm" rows="2" placeholder="Descreva brevemente o ocorrido para auditoria..." required></textarea>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="fecharModal('modalEstornoBalcao')">Voltar</button>
                <button type="submit" class="btn btn-enterprise btn-danger btn-sm">Confirmar Estorno e Devolver Crédito</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Aproveitamento de Sobras do Recreio [F9] -->
<div class="modal-backdrop-custom d-none" id="modalSobrasRecreio">
    <div class="modal-card-custom" style="max-width: 620px;">
        <div class="modal-header-custom">
            <h2 class="modal-title-custom">Aproveitamento de Sobras de Alunos Ausentes [F9]</h2>
            <button type="button" class="modal-close-custom" onclick="fecharModal('modalSobrasRecreio')">✕</button>
        </div>
        <form action="<?php echo $baseUrl; ?>/operacional/pdv/sobras/transferir" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="modal-body-custom">
                <p class="font-12 text-secondary mb-2">
                    Lanches agendados cujos cupons não foram retirados no recreio permanecem cobrados da família. Ao término do intervalo, você pode transferi-los para o saldo avulso do PDV para comercialização.
                </p>
                <div class="table-responsive border rounded mb-2" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-enterprise table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width: 32px;"><input type="checkbox" id="chkMarcarTodasSobras" onchange="alternarTodasSobras(this)"></th>
                                <th>Ticket</th>
                                <th>Aluno / Turma</th>
                                <th>Produto</th>
                                <th class="text-end">Qtd</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaSobrasBody">
                            <tr><td colspan="5" class="text-center py-3 text-muted">Carregando itens não retirados...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="fecharModal('modalSobrasRecreio')">Cancelar</button>
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm" id="btnConfirmarSobras" disabled>Transferir Selecionados para Venda Avulsa</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Pix Dinâmico no Balcão -->
<div class="modal-backdrop-custom d-none" id="modalPixBalcao">
    <div class="modal-card-custom text-center" style="max-width: 400px;">
        <div class="modal-header-custom">
            <h2 class="modal-title-custom text-primary">Pagamento Pix Balcão</h2>
            <button type="button" class="modal-close-custom" onclick="fecharModal('modalPixBalcao')">✕</button>
        </div>
        <div class="modal-body-custom">
            <div class="font-12 text-secondary mb-2">Apresente o QR Code abaixo ao cliente:</div>
            <div class="p-2 border rounded bg-white d-inline-block mb-2">
                <img id="pixQrCodeImg" src="" alt="QR Code Pix" width="200" height="200" style="object-fit: contain;">
            </div>
            <div class="font-18 font-bold text-dark font-tabular mb-2" id="pixValorDisplay">R$ 0,00</div>
            <div class="input-group input-group-sm mb-2">
                <input type="text" id="pixBrcodeInput" class="form-control font-monospace font-11" readonly>
                <button type="button" class="btn btn-outline-secondary" onclick="copiarPixBrcode()">Copiar</button>
            </div>
            <div class="font-11 text-muted" id="pixStatusMsg">Aguardando liquidação bancária...</div>
        </div>
        <div class="modal-footer-custom justify-content-center">
            <button type="button" class="btn btn-enterprise btn-secondary btn-sm" onclick="fecharModal('modalPixBalcao')">Cancelar</button>
            <button type="button" class="btn btn-enterprise btn-success btn-sm" onclick="confirmarVendaPixAposPagamento()">Confirmar e Finalizar Venda</button>
        </div>
    </div>
</div>

<!-- Modal: Pós-Venda (Sucesso e Comprovante) -->
<div class="modal-backdrop-custom d-none" id="modalPosVenda">
    <div class="modal-card-custom text-center" style="max-width: 380px;">
        <div class="modal-header-custom">
            <h2 class="modal-title-custom text-success">✓ Venda Concluída!</h2>
            <button type="button" class="modal-close-custom" onclick="fecharModal('modalPosVenda')">✕</button>
        </div>
        <div class="modal-body-custom py-3">
            <div class="font-13 text-secondary mb-1">Venda <span id="posVendaId" class="code-tag font-semibold">#0</span></div>
            <div class="font-22 font-bold text-dark font-tabular mb-2" id="posVendaTotal">R$ 0,00</div>
            
            <div id="posVendaTrocoBox" class="p-2 bg-light border rounded mb-3 d-none">
                <div class="font-11 text-secondary">Troco a Devolver:</div>
                <div class="font-20 font-bold text-success font-tabular" id="posVendaTroco">R$ 0,00</div>
            </div>

            <div class="d-flex flex-column gap-2">
                <button type="button" id="btnImprimirComprovante" class="btn btn-enterprise btn-secondary btn-sm py-2" onclick="imprimirComprovanteIframe()">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/></svg>
                    Imprimir Comprovante Térmico (80mm)
                </button>
                <div class="d-flex justify-content-center gap-3 font-11 mt-1">
                    <a id="btnComprovanteAba" href="#" target="_blank" class="text-secondary text-decoration-none" title="Abrir em aba separada">
                        ↗ Abrir em nova aba
                    </a>
                    <span class="text-muted">|</span>
                    <a id="btnComprovantePdf" href="#" target="_blank" class="text-secondary text-decoration-none" title="Baixar arquivo PDF">
                        ↓ Baixar PDF Direto
                    </a>
                </div>
                <button type="button" class="btn btn-enterprise btn-primary btn-sm py-2 mt-2" onclick="fecharModal('modalPosVenda')">
                    Nova Venda (F2)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================
     SCRIPTS JAVASCRIPT REATIVOS DO PDV
     ======================================================================== -->
<script>
let carrinho = [];
let formaPagamentoAtual = 'dinheiro';
let clienteTipoAtual = 'anonimo';
let alunoSelecionado = null;
let colaboradorSelecionado = null;
let sessaoCaixaId = <?php echo (int)($sessaoAberta['id'] ?? 0); ?>;
let caixaAberto = <?php echo $caixaAberto ? 'true' : 'false'; ?>;
let csrfToken = "<?php echo $csrfToken; ?>";
let baseUrl = "<?php echo $baseUrl; ?>";
let ultimaVendaIdConcluida = null;

/**
 * Dispara a impressão do comprovante térmico sem abrir novas abas ou bloquear o operador
 */
function imprimirComprovanteIframe(vendaId = null) {
    const id = vendaId || ultimaVendaIdConcluida;
    if (!id) return;

    let iframe = document.getElementById('iframeImpressaoPdv');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'iframeImpressaoPdv';
        iframe.style.position = 'fixed';
        iframe.style.top = '-9999px';
        iframe.style.left = '-9999px';
        iframe.style.width = '1px';
        iframe.style.height = '1px';
        iframe.style.border = 'none';
        iframe.style.opacity = '0';
        document.body.appendChild(iframe);
    }

    iframe.src = `${baseUrl}/operacional/pdv/comprovante/${id}?autoprint=1`;
}

// Inicia escutas de atalhos de teclado (F2, F3, F4, F8, F9, F10, Esc)
document.addEventListener('keydown', function(e) {
    if (e.key === 'F2') {
        e.preventDefault();
        fecharModal('modalPosVenda');
        limparCarrinho();
        const inp = document.getElementById('pdvBuscaInput');
        if (inp) {
            inp.focus();
            inp.select();
        }
    } else if (e.key === 'F3') {
        e.preventDefault();
        const inp = document.getElementById('pdvBuscaInput');
        if (inp) {
            inp.focus();
            inp.select();
        }
    } else if (e.key === 'F4' || e.key === 'F10') {
        e.preventDefault();
        processarFinalizacaoVenda();
    } else if (e.key === 'F7') {
        e.preventDefault();
        window.location.href = `${baseUrl}/operacional/caixa?turno=<?php echo urlencode($turnoAtual); ?>`;
    } else if (e.key === 'F8') {
        e.preventDefault();
        abrirModal('modalEstornoBalcao');
    } else if (e.key === 'F9') {
        e.preventDefault();
        abrirModalSobras();
    } else if (e.key === 'Escape') {
        fecharTodosModais();
    }
});

function fecharTodosModais() {
    document.querySelectorAll('.modal-backdrop-custom').forEach(m => m.classList.add('d-none'));
}

function fecharModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    if (modal.classList.contains('modal-backdrop-custom') || !modal.classList.contains('modal')) {
        modal.classList.add('d-none');
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const m = bootstrap.Modal.getInstance(modal);
        if (m) m.hide();
    }
}
window.fecharModal = fecharModal;

function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    if (modal.classList.contains('modal-backdrop-custom') || !modal.classList.contains('modal')) {
        modal.classList.remove('d-none');
        return;
    }
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
}
window.abrirModal = abrirModal;

function filtrarCategoria(catId, btn) {
    document.querySelectorAll('.pdv-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.pdv-prod-card');
    cards.forEach(c => {
        if (catId === 'todas' || c.getAttribute('data-categoria') == catId) {
            c.style.display = 'flex';
        } else {
            c.style.display = 'none';
        }
    });
}

function filtrarTexto(termo) {
    termo = termo.toLowerCase().trim();
    const cards = document.querySelectorAll('.pdv-prod-card');
    cards.forEach(c => {
        const nome = (c.getAttribute('data-nome') || '').toLowerCase();
        const codigo = (c.getAttribute('data-codigo') || '').toLowerCase();
        if (!termo || nome.includes(termo) || codigo.includes(termo)) {
            c.style.display = 'flex';
        } else {
            c.style.display = 'none';
        }
    });
}

function adicionarAoCarrinho(prodId) {
    const card = document.querySelector(`.pdv-prod-card[data-id="${prodId}"]`);
    if (!card || card.classList.contains('disabled')) return;

    const nome = card.getAttribute('data-nome');
    const preco = parseFloat(card.getAttribute('data-preco'));
    const estoque = parseInt(card.getAttribute('data-estoque'), 10);
    const alergenos = card.getAttribute('data-alergenos') || '';

    const index = carrinho.findIndex(it => it.produto_id === prodId);
    if (index >= 0) {
        if (carrinho[index].quantidade + 1 > estoque) {
            alert(`Apenas ${estoque} unidades disponíveis em estoque.`);
            return;
        }
        carrinho[index].quantidade += 1;
    } else {
        if (estoque < 1) {
            alert('Item sem saldo disponível no momento.');
            return;
        }
        carrinho.push({
            produto_id: prodId,
            descricao: nome,
            preco_unitario: preco,
            quantidade: 1,
            estoque_max: estoque,
            alergenos: alergenos
        });
    }

    renderizarCarrinho();
    verificarAlergenosComAluno();
}

function alterarQuantidadeItem(prodId, delta) {
    const index = carrinho.findIndex(it => it.produto_id === prodId);
    if (index < 0) return;

    const novaQtd = carrinho[index].quantidade + delta;
    if (novaQtd <= 0) {
        carrinho.splice(index, 1);
    } else if (novaQtd > carrinho[index].estoque_max) {
        alert(`Apenas ${carrinho[index].estoque_max} unidades disponíveis em estoque.`);
        return;
    } else {
        carrinho[index].quantidade = novaQtd;
    }

    renderizarCarrinho();
}

function removerItemCarrinho(prodId) {
    carrinho = carrinho.filter(it => it.produto_id !== prodId);
    renderizarCarrinho();
}

function limparCarrinho() {
    carrinho = [];
    renderizarCarrinho();
}

function renderizarCarrinho() {
    const itemsList = document.getElementById('cartItemsList');
    const emptyState = document.getElementById('cartEmptyState');
    const countBadge = document.getElementById('cartItemCount');
    const totalDisplay = document.getElementById('cartTotalDisplay');

    if (carrinho.length === 0) {
        itemsList.innerHTML = '';
        emptyState.classList.remove('d-none');
        countBadge.innerText = '0 itens';
        totalDisplay.innerText = 'R$ 0,00';
        calcularTroco();
        return;
    }

    emptyState.classList.add('d-none');
    let total = 0;
    let html = '';

    carrinho.forEach(it => {
        const subtotal = it.quantidade * it.preco_unitario;
        total += subtotal;

        html += `
            <div class="pdv-cart-item">
                <div style="flex: 1; padding-right: 8px;">
                    <div class="pdv-cart-item-title">${it.descricao}</div>
                    <div class="pdv-cart-item-desc font-tabular">R$ ${it.preco_unitario.toFixed(2).replace('.', ',')} un</div>
                </div>
                <div class="pdv-cart-controls">
                    <button type="button" class="pdv-qty-btn" onclick="alterarQuantidadeItem(${it.produto_id}, -1)">-</button>
                    <span class="font-tabular font-semibold px-1 font-12">${it.quantidade}</span>
                    <button type="button" class="pdv-qty-btn" onclick="alterarQuantidadeItem(${it.produto_id}, 1)">+</button>
                    <span class="font-tabular font-semibold font-13 text-primary ms-2" style="min-width: 65px; text-align: right;">
                        R$ ${subtotal.toFixed(2).replace('.', ',')}
                    </span>
                    <button type="button" class="btn btn-link text-danger p-0 ms-1" onclick="removerItemCarrinho(${it.produto_id})" title="Remover">✕</button>
                </div>
            </div>
        `;
    });

    itemsList.innerHTML = html;
    countBadge.innerText = `${carrinho.length} item(ns)`;
    totalDisplay.innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
    calcularTroco();
}

function trocarTipoCliente(tipo) {
    clienteTipoAtual = tipo;
    const rad = document.getElementById('tipo' + tipo.charAt(0).toUpperCase() + tipo.slice(1));
    if (rad) rad.checked = true;

    document.getElementById('boxClienteAluno').classList.toggle('d-none', tipo !== 'aluno');
    document.getElementById('boxClienteColaborador').classList.toggle('d-none', tipo !== 'colaborador');

    const btnFolha = document.getElementById('btnPayFolha');
    if (tipo === 'colaborador') {
        btnFolha.style.pointerEvents = 'auto';
        btnFolha.style.opacity = '1';
    } else {
        btnFolha.style.pointerEvents = 'none';
        btnFolha.style.opacity = '0.5';
        if (formaPagamentoAtual === 'desconto_folha') {
            selecionarFormaPagamento('dinheiro');
        }
    }
}

function selecionarFormaPagamento(forma) {
    formaPagamentoAtual = forma;
    document.querySelectorAll('.pdv-pay-btn').forEach(b => b.classList.remove('active'));

    if (forma === 'dinheiro') {
        document.getElementById('btnPayDinheiro').classList.add('active');
        document.getElementById('payDetailsDinheiro').classList.remove('d-none');
    } else if (forma === 'pix_balcao') {
        document.getElementById('btnPayPix').classList.add('active');
        document.getElementById('payDetailsDinheiro').classList.add('d-none');
    } else if (forma === 'desconto_folha') {
        document.getElementById('btnPayFolha').classList.add('active');
        document.getElementById('payDetailsDinheiro').classList.add('d-none');
    }
}

function calcularTroco() {
    const total = carrinho.reduce((acc, it) => acc + (it.quantidade * it.preco_unitario), 0);
    const recInput = document.getElementById('inputValorRecebido');
    const displayTroco = document.getElementById('displayTroco');

    if (!recInput) return;
    
    let recValor = 0;
    const raw = (recInput.value || '').trim();
    if (typeof converterMoedaParaFloat === 'function') {
        recValor = converterMoedaParaFloat(raw);
    } else {
        const digits = raw.replace(/\D/g, '');
        recValor = digits ? (parseInt(digits, 10) / 100) : 0;
    }

    if (recValor >= total && total > 0) {
        const troco = recValor - total;
        displayTroco.innerText = `R$ ${troco.toFixed(2).replace('.', ',')}`;
        displayTroco.className = 'font-14 font-tabular font-bold text-success';
    } else if (recValor > 0 && recValor < total) {
        const falta = total - recValor;
        displayTroco.innerText = `Falta R$ ${falta.toFixed(2).replace('.', ',')}`;
        displayTroco.className = 'font-14 font-tabular font-bold text-danger';
    } else {
        displayTroco.innerText = 'R$ 0,00';
        displayTroco.className = 'font-14 font-tabular font-bold text-muted';
    }
}

function definirRecebidoExato() {
    const total = carrinho.reduce((acc, it) => acc + (it.quantidade * it.preco_unitario), 0);
    const recInput = document.getElementById('inputValorRecebido');
    if (recInput) {
        recInput.value = total.toFixed(2).replace('.', ',');
        calcularTroco();
    }
}

function somarRecebido(valor) {
    const recInput = document.getElementById('inputValorRecebido');
    if (!recInput) return;
    const raw = (recInput.value || '').trim();
    let atual = 0;
    if (typeof converterMoedaParaFloat === 'function') {
        atual = converterMoedaParaFloat(raw);
    } else {
        const digits = raw.replace(/\D/g, '');
        atual = digits ? (parseInt(digits, 10) / 100) : 0;
    }
    recInput.value = (atual + valor).toFixed(2).replace('.', ',');
    calcularTroco();
}

// Busca Live de Colaboradores
let timerBuscaColab = null;
function buscarColaboradoresLive(termo) {
    clearTimeout(timerBuscaColab);
    const lista = document.getElementById('listaSugestoesColab');
    if (!termo || termo.trim().length < 2) {
        lista.classList.add('d-none');
        return;
    }

    timerBuscaColab = setTimeout(() => {
        fetch(`${baseUrl}/operacional/pdv/colaborador/buscar?busca=${encodeURIComponent(termo)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.sucesso || !data.dados || data.dados.length === 0) {
                    lista.innerHTML = '<div class="list-group-item font-11 text-muted">Nenhum colaborador localizado.</div>';
                    lista.classList.remove('d-none');
                    return;
                }

                let html = '';
                data.dados.forEach(c => {
                    html += `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2" onclick='selecionarColaborador(${JSON.stringify(c)})'>
                            <div class="d-flex align-items-center justify-content-between">
                                <strong class="font-12">${c.nome}</strong>
                                <span class="badge bg-light text-dark font-10">${c.cargo || 'Funcionário'}</span>
                            </div>
                            <div class="font-10 text-muted">Mat: ${c.matricula_funcional}</div>
                        </a>
                    `;
                });
                lista.innerHTML = html;
                lista.classList.remove('d-none');
            });
    }, 250);
}

function selecionarColaborador(colab) {
    colaboradorSelecionado = colab;
    document.getElementById('listaSugestoesColab').classList.add('d-none');
    document.getElementById('buscaColabPdv').value = colab.nome;

    document.getElementById('colabSelNome').innerText = colab.nome;
    document.getElementById('colabSelCargo').innerText = colab.cargo || 'Funcionário';
    document.getElementById('colabSelMat').innerText = colab.matricula_funcional;
    if (colab.foto_url) {
        document.getElementById('colabSelFoto').src = `${baseUrl}/${colab.foto_url}`;
    }
    document.getElementById('chkConfirmaVisual').checked = false;
    document.getElementById('colabSelecionadoCard').classList.remove('d-none');

    selecionarFormaPagamento('desconto_folha');
    atualizarBotaoDescontoFolha();
}

function limparSelecaoColaborador() {
    colaboradorSelecionado = null;
    document.getElementById('buscaColabPdv').value = '';
    document.getElementById('colabSelecionadoCard').classList.add('d-none');
    document.getElementById('listaSugestoesColab').classList.add('d-none');
    selecionarFormaPagamento('dinheiro');
}

function atualizarBotaoDescontoFolha() {
    const chk = document.getElementById('chkConfirmaVisual');
    const btnFolha = document.getElementById('btnPayFolha');
    if (chk && chk.checked) {
        btnFolha.classList.add('active');
    }
}

// Busca Live de Alunos
let timerBuscaAluno = null;
function buscarAlunosLive(termo) {
    clearTimeout(timerBuscaAluno);
    const lista = document.getElementById('listaSugestoesAluno');
    if (!termo || termo.trim().length < 2) {
        lista.classList.add('d-none');
        return;
    }

    timerBuscaAluno = setTimeout(() => {
        fetch(`${baseUrl}/operacional/pdv/aluno/buscar?busca=${encodeURIComponent(termo)}`)
            .then(res => res.json())
            .then(data => {
                if (!data.sucesso || !data.dados || data.dados.length === 0) {
                    lista.innerHTML = '<div class="list-group-item font-11 text-muted">Nenhum aluno localizado.</div>';
                    lista.classList.remove('d-none');
                    return;
                }

                let html = '';
                data.dados.forEach(a => {
                    html += `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action py-2" onclick='selecionarAluno(${JSON.stringify(a)})'>
                            <div class="d-flex align-items-center justify-content-between">
                                <strong class="font-12">${a.nome}</strong>
                                <span class="badge bg-light text-dark font-10">${a.nome_turma || ''}</span>
                            </div>
                            <div class="font-10 text-muted">Matrícula: ${a.matricula}</div>
                        </a>
                    `;
                });
                lista.innerHTML = html;
                lista.classList.remove('d-none');
            });
    }, 250);
}

function selecionarAluno(aluno) {
    alunoSelecionado = aluno;
    document.getElementById('listaSugestoesAluno').classList.add('d-none');
    document.getElementById('buscaAlunoPdv').value = aluno.nome;

    document.getElementById('alunoSelNome').innerText = aluno.nome;
    document.getElementById('alunoSelTurma').innerText = aluno.nome_turma || 'Geral';

    const alergiasDiv = document.getElementById('alunoSelAlergias');
    if (aluno.restricoes_nomes) {
        alergiasDiv.innerText = `Alergias cadastradas: ${aluno.restricoes_nomes}`;
    } else {
        alergiasDiv.innerText = 'Sem restrições alimentares cadastradas.';
        alergiasDiv.className = 'mt-1 font-11 text-success';
    }

    document.getElementById('alunoSelecionadoCard').classList.remove('d-none');
    verificarAlergenosComAluno();
}

function limparSelecaoAluno() {
    alunoSelecionado = null;
    document.getElementById('buscaAlunoPdv').value = '';
    document.getElementById('alunoSelecionadoCard').classList.add('d-none');
    document.getElementById('listaSugestoesAluno').classList.add('d-none');
}

function verificarAlergenosComAluno() {
    if (!alunoSelecionado || !alunoSelecionado.restricoes_nomes) return;

    const alergiasAluno = alunoSelecionado.restricoes_nomes.toLowerCase().split(',').map(s => s.trim());
    let conflito = [];

    carrinho.forEach(it => {
        const prodAlerg = (it.alergenos || '').split(',').filter(Boolean);
        if (prodAlerg.length > 0) {
            conflito.push(it.descricao);
        }
    });

    if (conflito.length > 0) {
        alert(`[ALERTA DE ALERGIA]\nO aluno ${alunoSelecionado.nome} possui restrições alimentares registradas!\nItens no pedido com possíveis alérgenos:\n- ${conflito.join('\n- ')}`);
    }
}

// Finalização da Venda
function processarFinalizacaoVenda() {
    if (!caixaAberto) {
        alert('O caixa do turno está fechado. Abra a sessão de caixa antes de realizar vendas.');
        abrirModal('modalAbrirCaixa');
        return;
    }

    if (carrinho.length === 0) {
        alert('Adicione itens à cesta de compras antes de finalizar.');
        return;
    }

    const total = carrinho.reduce((acc, it) => acc + (it.quantidade * it.preco_unitario), 0);

    if (formaPagamentoAtual === 'dinheiro') {
        const recInput = document.getElementById('inputValorRecebido');
        const recValor = parseFloat(recInput ? recInput.value.replace(/\./g, '').replace(',', '.') : '0') || 0;
        if (recValor < total) {
            alert(`O valor recebido em dinheiro (R$ ${recValor.toFixed(2).replace('.', ',')}) é menor que o total da venda (R$ ${total.toFixed(2).replace('.', ',')}).`);
            return;
        }
        submeterVendaServidor(recValor);
    } else if (formaPagamentoAtual === 'pix_balcao') {
        abrirModalPixBalcao(total);
    } else if (formaPagamentoAtual === 'desconto_folha') {
        if (!colaboradorSelecionado) {
            alert('Para lançar em Desconto em Folha, selecione um colaborador.');
            return;
        }
        const chk = document.getElementById('chkConfirmaVisual');
        if (!chk || !chk.checked) {
            alert('É obrigatório marcar a confirmação visual da foto do colaborador antes de lançar em folha.');
            return;
        }
        submeterVendaServidor(0.0);
    }
}

function submeterVendaServidor(valorRecebido) {
    const btn = document.getElementById('btnFinalizarVenda');
    btn.disabled = true;
    btn.innerText = 'Processando venda...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('sessao_caixa_id', sessaoCaixaId);
    formData.append('forma_pagamento', formaPagamentoAtual);
    formData.append('tipo_venda', (formaPagamentoAtual === 'desconto_folha') ? 'desconto_folha' : 'avulso_aluno');
    formData.append('aluno_id', alunoSelecionado ? alunoSelecionado.id : '');
    formData.append('colaborador_id', colaboradorSelecionado ? colaboradorSelecionado.id : '');
    formData.append('confirmacao_visual', document.getElementById('chkConfirmaVisual')?.checked ? '1' : '0');
    formData.append('valor_dinheiro_recebido', valorRecebido);
    formData.append('itens', JSON.stringify(carrinho));

    fetch(`${baseUrl}/operacional/pdv/finalizar`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Concluir Venda (F4)';

        if (!data.sucesso) {
            alert('Erro ao finalizar venda: ' + (data.mensagem || 'Falha desconhecida.'));
            return;
        }

        // Exibe modal pós-venda com segurança defensiva total
        const elPosId = document.getElementById('posVendaId');
        if (elPosId) elPosId.innerText = `#${data.venda_id}`;

        const elPosTotal = document.getElementById('posVendaTotal');
        if (elPosTotal) elPosTotal.innerText = `R$ ${parseFloat(data.total).toFixed(2).replace('.', ',')}`;

        const trocoBox = document.getElementById('posVendaTrocoBox');
        if (trocoBox) {
            if (formaPagamentoAtual === 'dinheiro' && parseFloat(data.troco) > 0) {
                const elTroco = document.getElementById('posVendaTroco');
                if (elTroco) elTroco.innerText = `R$ ${parseFloat(data.troco).toFixed(2).replace('.', ',')}`;
                trocoBox.classList.remove('d-none');
            } else {
                trocoBox.classList.add('d-none');
            }
        }

        ultimaVendaIdConcluida = data.venda_id;

        const elBtnAba = document.getElementById('btnComprovanteAba');
        if (elBtnAba) {
            elBtnAba.href = `${baseUrl}/operacional/pdv/comprovante/${data.venda_id}`;
        }
        const elBtnPdf = document.getElementById('btnComprovantePdf');
        if (elBtnPdf) {
            elBtnPdf.href = `${baseUrl}/operacional/pdv/comprovante/${data.venda_id}/pdf`;
        }
        
        limparCarrinho();
        limparSelecaoColaborador();
        limparSelecaoAluno();
        trocarTipoCliente('anonimo');

        // Abre o modal diretamente pela classe d-none sem risco de dependências externas
        const modalPos = document.getElementById('modalPosVenda');
        if (modalPos) {
            modalPos.classList.remove('d-none');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Concluir Venda (F4)';
        console.error('Falha na venda:', err);
        alert('Falha na comunicação com o servidor: ' + err.message);
    });
}

function abrirModalPixBalcao(valor) {
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('valor', valor);

    fetch(`${baseUrl}/operacional/pdv/pix/gerar`, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (!data.sucesso) {
            alert('Erro ao gerar Pix balcão: ' + data.mensagem);
            return;
        }

        document.getElementById('pixQrCodeImg').src = data.qr_code_uri;
        document.getElementById('pixValorDisplay').innerText = `R$ ${data.valor_formatado}`;
        document.getElementById('pixBrcodeInput').value = data.brcode;
        abrirModal('modalPixBalcao');
    });
}

function copiarPixBrcode() {
    const input = document.getElementById('pixBrcodeInput');
    input.select();
    navigator.clipboard.writeText(input.value);
    alert('Código Pix Copia e Cola copiado para a área de transferência!');
}

function confirmarVendaPixAposPagamento() {
    fecharModal('modalPixBalcao');
    submeterVendaServidor(0.0);
}

function abrirModalSobras() {
    fetch(`${baseUrl}/operacional/pdv/sobras`)
        .then(res => res.json())
        .then(data => {
            const tbody = document.getElementById('tabelaSobrasBody');
            if (!data.sucesso || !data.dados || data.dados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Não há lanches não retirados pendentes para este turno.</td></tr>';
                document.getElementById('btnConfirmarSobras').disabled = true;
            } else {
                let html = '';
                data.dados.forEach(s => {
                    html += `
                        <tr>
                            <td><input type="checkbox" name="ticket_ids[]" value="${s.ticket_id}" class="chk-sobra" onchange="atualizarCheckSobras()"></td>
                            <td><span class="code-tag">${s.codigo_ticket}</span></td>
                            <td><strong>${s.aluno_nome}</strong> <span class="font-11 text-muted">(${s.nome_turma})</span></td>
                            <td>${s.produto_descricao}</td>
                            <td class="text-end font-tabular">${s.item_quantidade} un</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
                atualizarCheckSobras();
            }
            abrirModal('modalSobrasRecreio');
        });
}

function alternarTodasSobras(master) {
    document.querySelectorAll('.chk-sobra').forEach(chk => chk.checked = master.checked);
    atualizarCheckSobras();
}

function atualizarCheckSobras() {
    const selecionados = document.querySelectorAll('.chk-sobra:checked').length;
    document.getElementById('btnConfirmarSobras').disabled = (selecionados === 0);
}
</script>
