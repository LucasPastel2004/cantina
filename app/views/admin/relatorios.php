<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Central de Relatórios Gerenciais
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Relatórios Gerenciais</h1>
        <p class="text-secondary text-microcopy mb-0">Central de exportações, relatórios analíticos em PDF A4 timbrado e espelhos operacionais.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Relatório 1: Corte de Encomendas -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Operacional</span>
                <span class="badge bg-primary text-white" style="font-size: 10px;">PDF A4 Timbrado</span>
            </div>
            <div class="card-enterprise-body d-flex flex-column justify-content-between">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-2">Relatório Consolidado de Corte</h2>
                    <p class="text-secondary text-microcopy mb-3">
                        Totalização analítica de salgados encomendados por turno, quantidade exata para compra e alunos beneficiados.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo $baseUrl; ?>/operacional/corte/pdf?data=<?php echo date('Y-m-d'); ?>&turno=matutino" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center" target="_blank">
                        Baixar PDF (Manhã)
                    </a>
                    <a href="<?php echo $baseUrl; ?>/operacional/corte" class="btn btn-enterprise btn-primary btn-sm">
                        Painel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Relatório 2: Fechamento de Caixa -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Financeiro</span>
                <span class="badge bg-primary text-white" style="font-size: 10px;">PDF A4 Timbrado</span>
            </div>
            <div class="card-enterprise-body d-flex flex-column justify-content-between">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-2">Prestação de Contas & Fechamento</h2>
                    <p class="text-secondary text-microcopy mb-3">
                        Espelho detalhado de apuração por turno com contagem de gaveta, dinheiro, Pix balcão e quebra/sobra para assinatura.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                        Histórico de Fechamentos
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Relatório 3: Estoque e Perdas -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Estoque</span>
                <span class="status-indicator status-active"><span class="bullet">●</span> Ativo</span>
            </div>
            <div class="card-enterprise-body d-flex flex-column justify-content-between">
                <div>
                    <h2 class="h6 font-weight-bold text-dark mb-2">Extrato de Movimentações & Perdas</h2>
                    <p class="text-secondary text-microcopy mb-3">
                        Rastreabilidade completa de entradas via NF-e XML, baixas de vendas balcão e auditoria de perdas com justificativa.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo $baseUrl; ?>/estoque/movimentacoes" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                        Ver Movimentações
                    </a>
                    <a href="<?php echo $baseUrl; ?>/estoque" class="btn btn-enterprise btn-primary btn-sm">
                        Inventário
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
