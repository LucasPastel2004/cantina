<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Início do Painel Operacional da Cantina
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$usuario = $usuario ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Painel da Cantina</h1>
        <p class="text-secondary text-microcopy mb-0">
            Operador logado: <strong><?php echo htmlspecialchars($usuario['nome'] ?? 'Operador', ENT_QUOTES, 'UTF-8'); ?></strong> 
            (Perfil: <?php echo ucfirst($usuario['role'] ?? 'operador'); ?>)
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/operacional/corte" class="btn btn-enterprise btn-primary">
            Painel de Corte
        </a>
    </div>
</div>

<!-- Faixa de Métricas Rápidas (Stat-Strip) -->
<?php
$stats = [
    ['label' => 'TURNO ATUAL', 'value' => 'Matutino', 'tabular' => false],
    ['label' => 'CORTE MATUTINO', 'value' => '08:00', 'tabular' => true],
    ['label' => 'CAIXA MATUTINO', 'value' => 'Fechamento 11:00', 'tabular' => false],
    ['label' => 'PERMISSÃO', 'value' => ucfirst($usuario['role'] ?? 'operador'), 'tabular' => false],
];
require __DIR__ . '/../partials/stat_strip.php';
?>

<div class="row g-4 mt-1">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="font-weight-bold text-dark">Corte de Fornecedor</span>
            </div>
            <div class="card-enterprise-body p-3">
                <p class="text-microcopy text-secondary mb-3">Consolidação automática de salgados encomendados pontualmente no corte.</p>
                <a href="<?php echo $baseUrl; ?>/operacional/corte" class="btn btn-enterprise btn-secondary btn-sm w-100">
                    Acessar Corte
                </a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="font-weight-bold text-dark">Tickets Térmicos</span>
            </div>
            <div class="card-enterprise-body p-3">
                <p class="text-microcopy text-secondary mb-3">Impressão contínua em bobina de 80mm e reemissão de 2ª via com auditoria.</p>
                <a href="<?php echo $baseUrl; ?>/operacional/tickets" class="btn btn-enterprise btn-secondary btn-sm w-100">
                    Imprimir Tickets
                </a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="font-weight-bold text-dark">Ponto de Venda (PDV)</span>
            </div>
            <div class="card-enterprise-body p-3">
                <p class="text-microcopy text-secondary mb-3">Vendas avulsas no balcão e consumo de professores com desconto em folha.</p>
                <a href="<?php echo $baseUrl; ?>/operacional/pdv" class="btn btn-enterprise btn-secondary btn-sm w-100">
                    Abrir PDV
                </a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="font-weight-bold text-dark">Sessão de Caixa</span>
            </div>
            <div class="card-enterprise-body p-3">
                <p class="text-microcopy text-secondary mb-3">Abertura com troco e conferência de fechamento por turno (11:00 / 16:00).</p>
                <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="btn btn-enterprise btn-secondary btn-sm w-100">
                    Gerenciar Caixa
                </a>
            </div>
        </div>
    </div>
</div>
