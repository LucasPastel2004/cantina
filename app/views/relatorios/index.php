<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Central de Relatórios Gerenciais (Hub Oficial)
 * 
 * Conforme docs/FSD.md (Área 6, Módulo 8, Tela 8.1), docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Central de Relatórios Gerenciais</h1>
        <p class="text-secondary text-microcopy mb-0">
            Painel unificado de exportações financeiras, relatórios analíticos em PDF A4 timbrado e demonstrativos de auditoria da cantina.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-secondary border font-tabular px-2.5 py-1.5" style="font-size: 11px;">
            Competência de Referência: <?php echo htmlspecialchars($mesReferencia, ENT_QUOTES, 'UTF-8'); ?>
        </span>
    </div>
</div>

<!-- Stat-Strip: Indicadores Chave de Desempenho (Mês Atual) -->
<div class="stat-strip mb-4">
    <div class="stat-item">
        <span class="stat-label">Faturamento Total do Mês</span>
        <span class="stat-value font-tabular text-primary">
            R$ <?php echo number_format($faturamentoMes, 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Pedidos Online + Vendas Balcão
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Lanches Comercializados</span>
        <span class="stat-value font-tabular text-dark">
            <?php echo number_format($lanchesMes, 0, ',', '.'); ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">un</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Volume físico de itens entregues
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Desconto em Folha RH</span>
        <span class="stat-value font-tabular text-dark">
            R$ <?php echo number_format($descontoFolhaMes, 2, ',', '.'); ?>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Consumo faturado para professores/staff
        </span>
    </div>

    <div class="stat-item">
        <span class="stat-label">Famílias em Débito / MED</span>
        <span class="stat-value font-tabular <?php echo ($totalDevedores > 0) ? 'text-danger' : 'text-success'; ?>">
            <?php echo (int)$totalDevedores; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">famílias</small>
        </span>
        <span class="text-secondary text-microcopy mt-1 font-tabular">
            Total Devedor: R$ <?php echo number_format($saldoTotalDevedor, 2, ',', '.'); ?>
        </span>
    </div>
</div>

<!-- Diretório de Relatórios em Cards Corporativos -->
<div class="row g-4">
    <!-- Relatório 1: Extrato Analítico do Aluno -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-enterprise-header d-flex justify-content-between align-items-center mb-2">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Atendimento & Famílias</span>
                    <span class="badge bg-primary text-white" style="font-size: 10px;">PDF A4 + Cupom</span>
                </div>
                <h2 class="h6 font-weight-bold text-dark mb-2">Extrato Analítico do Aluno</h2>
                <p class="text-secondary text-microcopy mb-3">
                    Histórico detalhado de recargas (Pix/Dinheiro) e consumo de itens linha a linha, com saldo da carteira, alérgenos e espelho para impressão imediata.
                </p>
            </div>
            <div class="d-flex gap-2 pt-2 border-top">
                <a href="<?php echo $baseUrl; ?>/operacional/extrato-aluno" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                    Acessar Extrato
                </a>
            </div>
        </div>
    </div>

    <!-- Relatório 2: Vendas por Alimento e Curva ABC -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-enterprise-header d-flex justify-content-between align-items-center mb-2">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Desempenho Comercial</span>
                    <span class="badge bg-primary text-white" style="font-size: 10px;">PDF A4 Timbrado</span>
                </div>
                <h2 class="h6 font-weight-bold text-dark mb-2">Vendas por Alimento & Curva ABC</h2>
                <p class="text-secondary text-microcopy mb-3">
                    Totalização de faturamento por produto e categoria, preço médio ponderado, percentual de participação e classificação ABC (Classes A, B e C).
                </p>
            </div>
            <div class="d-flex gap-2 pt-2 border-top">
                <a href="<?php echo $baseUrl; ?>/relatorios/vendas-alimento" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                    Abrir Relatório & Curva ABC
                </a>
            </div>
        </div>
    </div>

    <!-- Relatório 3: Desconto em Folha para RH -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-enterprise-header d-flex justify-content-between align-items-center mb-2">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Recursos Humanos</span>
                    <span class="badge bg-success text-white" style="font-size: 10px;">CSV Folha + PDF A4</span>
                </div>
                <h2 class="h6 font-weight-bold text-dark mb-2">Desconto em Folha para RH</h2>
                <p class="text-secondary text-microcopy mb-3">
                    Consolidado mensal de compras de professores e colaboradores por matrícula, com exportação CSV para sistemas de folha e relatório PDF com campos para assinaturas.
                </p>
            </div>
            <div class="d-flex gap-2 pt-2 border-top">
                <a href="<?php echo $baseUrl; ?>/relatorios/desconto-folha" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                    Gerenciar Folha RH
                </a>
            </div>
        </div>
    </div>

    <!-- Relatório 4: Fechamento de Caixa & Prestação de Contas -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-enterprise-header d-flex justify-content-between align-items-center mb-2">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Controladoria & Caixa</span>
                    <span class="badge bg-primary text-white" style="font-size: 10px;">PDF A4 Timbrado</span>
                </div>
                <h2 class="h6 font-weight-bold text-dark mb-2">Demonstrativo de Fechamento de Caixa</h2>
                <p class="text-secondary text-microcopy mb-3">
                    Auditoria de sessões de caixa por turno, conferência física de gaveta, dinheiro, Pix de balcão e apuração de sobras ou quebras com visto de assinatura.
                </p>
            </div>
            <div class="d-flex gap-2 pt-2 border-top">
                <a href="<?php echo $baseUrl; ?>/relatorios/fechamento-caixa" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                    Histórico & Prestação de Contas
                </a>
            </div>
        </div>
    </div>

    <!-- Relatório 5: Inadimplência e MED Pix -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-enterprise-header d-flex justify-content-between align-items-center mb-2">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Contas a Receber</span>
                    <span class="badge bg-danger text-white" style="font-size: 10px;">PDF A4 Timbrado</span>
                </div>
                <h2 class="h6 font-weight-bold text-dark mb-2">Inadimplência & Bloqueios MED</h2>
                <p class="text-secondary text-microcopy mb-3">
                    Relação nominal de famílias com saldo devedor na carteira ou suspensão preventiva decorrente de contestações bancárias do Mecanismo Especial de Devolução (MED).
                </p>
            </div>
            <div class="d-flex gap-2 pt-2 border-top">
                <a href="<?php echo $baseUrl; ?>/relatorios/devedores" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center">
                    Relação de Devedores
                </a>
            </div>
        </div>
    </div>

    <!-- Relatório 6: Corte do Turno -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card-enterprise h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="card-enterprise-header d-flex justify-content-between align-items-center mb-2">
                    <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Operacional Fornecedor</span>
                    <span class="badge bg-secondary text-white" style="font-size: 10px;">PDF A4 Timbrado</span>
                </div>
                <h2 class="h6 font-weight-bold text-dark mb-2">Relatório Consolidado de Corte</h2>
                <p class="text-secondary text-microcopy mb-3">
                    Totalização analítica de salgados e almoços agendados para compra exata com fornecedores locais, prevenindo sobra e falta de merenda.
                </p>
            </div>
            <div class="d-flex gap-2 pt-2 border-top">
                <a href="<?php echo $baseUrl; ?>/operacional/corte" class="btn btn-enterprise btn-secondary btn-sm flex-fill text-center">
                    Painel de Corte
                </a>
                <a href="<?php echo $baseUrl; ?>/operacional/corte/pdf?data=<?php echo date('Y-m-d'); ?>&turno=matutino" class="btn btn-enterprise btn-secondary btn-sm" target="_blank" title="Baixar PDF A4 de Hoje">
                    PDF Hoje
                </a>
            </div>
        </div>
    </div>
</div>
