<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * Componente Parcial: Paginação Corporativa de Alta Densidade
 * 
 * Conforme docs/DESIGN.md e regras de botões neutros em AGENTS.md
 * 
 * Variáveis esperadas:
 * - $paginaAtual   (int): Página atual (ex: 1)
 * - $totalPaginas  (int): Total de páginas disponíveis (ex: 5)
 * - $totalRegistros(int): Total de registros encontrados (ex: 87)
 * - $itensPorPagina(int): Limite por página (ex: 20)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$paginaAtual    = max(1, (int)($paginaAtual ?? 1));
$totalPaginas   = max(1, (int)($totalPaginas ?? 1));
$totalRegistros = max(0, (int)($totalRegistros ?? 0));
$itensPorPagina = max(1, (int)($itensPorPagina ?? 20));

if ($totalRegistros === 0) {
    return; // Não renderiza paginação se não houver registros
}

$inicioRegistro = (($paginaAtual - 1) * $itensPorPagina) + 1;
$fimRegistro    = min($inicioRegistro + $itensPorPagina - 1, $totalRegistros);

// Reconstrói a query string preservando filtros existentes
$params = $_GET;
unset($params['pagina']);
$queryString = http_build_query($params);

$gerarUrl = function(int $numPagina) use ($queryString): string {
    $sep = !empty($queryString) ? '&' : '';
    $prefix = !empty($queryString) ? '?' . $queryString . $sep : '?';
    return $prefix . 'pagina=' . $numPagina;
};

// Determina o intervalo de botões numéricos (exibe até 5 páginas ao redor da atual)
$raio = 2;
$pagInicio = max(1, $paginaAtual - $raio);
$pagFim = min($totalPaginas, $paginaAtual + $raio);

if ($pagInicio > 1) {
    $pagFim = min($totalPaginas, $pagFim + ($raio - ($paginaAtual - $pagInicio)));
}
if ($pagFim < $totalPaginas) {
    $pagInicio = max(1, $pagInicio - ($raio - ($pagFim - $paginaAtual)));
}
?>

<div class="card-footer bg-white border-top py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 12px; border-color: #e2e8f0;">
    <!-- Contador Analítico de Registros -->
    <div class="text-secondary d-flex align-items-center gap-1">
        <span>Mostrando</span>
        <strong class="font-tabular text-dark"><?php echo number_format($inicioRegistro, 0, ',', '.'); ?></strong>
        <span>a</span>
        <strong class="font-tabular text-dark"><?php echo number_format($fimRegistro, 0, ',', '.'); ?></strong>
        <span>de</span>
        <strong class="font-tabular text-dark"><?php echo number_format($totalRegistros, 0, ',', '.'); ?></strong>
        <span><?php echo ($totalRegistros === 1) ? 'registro' : 'registros'; ?></span>
        <span class="text-muted ms-1">(<?php echo $itensPorPagina; ?> por pág.)</span>
    </div>

    <!-- Navegação de Páginas -->
    <?php if ($totalPaginas > 1): ?>
        <nav aria-label="Navegação da listagem" class="d-flex align-items-center gap-1">
            <!-- Botão Primeira Página (se distante) -->
            <?php if ($paginaAtual > 2 && $pagInicio > 1): ?>
                <a href="<?php echo htmlspecialchars($gerarUrl(1), ENT_QUOTES, 'UTF-8'); ?>" 
                   class="btn btn-enterprise btn-secondary btn-sm px-2 py-1" 
                   title="Primeira Página" style="font-size: 11.5px; min-width: 28px;">
                    « 1
                </a>
                <?php if ($pagInicio > 2): ?>
                    <span class="text-muted px-1">…</span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Botão Anterior -->
            <?php if ($paginaAtual > 1): ?>
                <a href="<?php echo htmlspecialchars($gerarUrl($paginaAtual - 1), ENT_QUOTES, 'UTF-8'); ?>" 
                   class="btn btn-enterprise btn-secondary btn-sm px-2 py-1" 
                   style="font-size: 11.5px;">
                    ‹ Anterior
                </a>
            <?php else: ?>
                <span class="btn btn-enterprise btn-secondary btn-sm px-2 py-1 disabled opacity-50" 
                      style="font-size: 11.5px; pointer-events: none;">
                    ‹ Anterior
                </span>
            <?php endif; ?>

            <!-- Botões Numéricos -->
            <?php for ($p = $pagInicio; $p <= $pagFim; $p++): ?>
                <?php if ($p === $paginaAtual): ?>
                    <span class="btn btn-enterprise btn-primary btn-sm px-2 py-1 font-tabular font-semibold" 
                          style="font-size: 11.5px; min-width: 28px; cursor: default;">
                        <?php echo $p; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($gerarUrl($p), ENT_QUOTES, 'UTF-8'); ?>" 
                       class="btn btn-enterprise btn-secondary btn-sm px-2 py-1 font-tabular" 
                       style="font-size: 11.5px; min-width: 28px;">
                        <?php echo $p; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Botão Próxima -->
            <?php if ($paginaAtual < $totalPaginas): ?>
                <a href="<?php echo htmlspecialchars($gerarUrl($paginaAtual + 1), ENT_QUOTES, 'UTF-8'); ?>" 
                   class="btn btn-enterprise btn-secondary btn-sm px-2 py-1" 
                   style="font-size: 11.5px;">
                    Próxima ›
                </a>
            <?php else: ?>
                <span class="btn btn-enterprise btn-secondary btn-sm px-2 py-1 disabled opacity-50" 
                      style="font-size: 11.5px; pointer-events: none;">
                    Próxima ›
                </span>
            <?php endif; ?>

            <!-- Botão Última Página (se distante) -->
            <?php if ($paginaAtual < ($totalPaginas - 1) && $pagFim < $totalPaginas): ?>
                <?php if ($pagFim < ($totalPaginas - 1)): ?>
                    <span class="text-muted px-1">…</span>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($gerarUrl($totalPaginas), ENT_QUOTES, 'UTF-8'); ?>" 
                   class="btn btn-enterprise btn-secondary btn-sm px-2 py-1" 
                   title="Última Página (<?php echo $totalPaginas; ?>)" style="font-size: 11.5px; min-width: 28px;">
                    <?php echo $totalPaginas; ?> »
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</div>
