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

<div class="container-fluid px-2 pb-5" style="max-width: 1400px; margin: 0 auto;">
    <!-- Barra de Subcabeçalho e Contexto da Página -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 pb-2 border-bottom">
        <div>
            <span class="text-microcopy text-uppercase font-weight-bold text-secondary d-block">Diretório Executivo</span>
            <p class="text-secondary text-microcopy mb-0">
                Painel unificado de exportações financeiras, relatórios analíticos em PDF A4 timbrado e demonstrativos de auditoria da cantina.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="code-tag font-tabular">
                Competência: <?php echo htmlspecialchars($mesReferencia, ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>
    </div>

    <!-- Stat-Strip: Indicadores Chave de Desempenho (Mês Atual) -->
    <div class="stat-strip mb-4">
        <div class="stat-item">
            <span class="stat-label">Faturamento Total do Mês</span>
            <span class="stat-value font-tabular text-dark">
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
                Consumo faturado para colaboradores
            </span>
        </div>

        <div class="stat-item">
            <span class="stat-label">Famílias em Débito / MED</span>
            <span class="stat-value font-tabular text-dark">
                <?php echo (int)$totalDevedores; ?> <small class="text-muted" style="font-size: 13px; font-weight: normal;">famílias</small>
            </span>
            <span class="text-secondary text-microcopy mt-1 font-tabular">
                Total Devedor: R$ <?php echo number_format($saldoTotalDevedor, 2, ',', '.'); ?>
            </span>
        </div>
    </div>

    <!-- Diretório de Relatórios em Cards Corporativos com Padrão Visual Oficial -->
    <div class="row g-3 g-xl-4">
        <!-- Relatório 1: Extrato de Consumo do Aluno -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Atendimento & Famílias</span>
                        <span class="code-tag font-tabular">PDF A4 + Cupom</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Extrato de Consumo do Aluno</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Histórico detalhado de recargas (Pix/Dinheiro) e consumo de itens linha a linha, com saldo da carteira, alérgenos e espelho para impressão imediata.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto">
                    <a href="<?php echo $baseUrl; ?>/relatorios/extrato-aluno" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Acessar Extrato
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 2: Vendas por Alimento e Curva ABC -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Desempenho Comercial</span>
                        <span class="code-tag font-tabular">PDF A4 Timbrado</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Vendas por Alimento & Curva ABC</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Parametrização dinâmica com toggles (Sim/Não), agrupamento de consumo por período, ordenação por mais/menos vendidos, Curva ABC e espelho oficial A4 / CSV.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto flex-wrap">
                    <a href="<?php echo $baseUrl; ?>/relatorios/vendas-filtros" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Filtros de Vendas
                    </a>
                    <a href="<?php echo $baseUrl; ?>/relatorios/vendas-alimento" class="btn btn-enterprise btn-secondary btn-sm text-center fw-medium">
                        Curva ABC
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 3: Desconto em Folha para RH -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Recursos Humanos</span>
                        <span class="code-tag font-tabular">CSV Folha + PDF A4</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Desconto em Folha para RH</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Consolidado mensal de compras de professores e colaboradores por matrícula, com exportação CSV para sistemas de folha e relatório PDF com campos para assinaturas.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto">
                    <a href="<?php echo $baseUrl; ?>/relatorios/desconto-folha" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Gerenciar Folha RH
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 4: Fechamento de Caixa & Prestação de Contas -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Controladoria & Caixa</span>
                        <span class="code-tag font-tabular">PDF A4 Timbrado</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Demonstrativo de Fechamento de Caixa</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Auditoria de sessões de caixa por turno, conferência física de gaveta, dinheiro, Pix de balcão e apuração de sobras ou quebras com visto de assinatura.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto">
                    <a href="<?php echo $baseUrl; ?>/relatorios/fechamento-caixa" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Histórico & Prestação de Contas
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 5: Inadimplência e MED Pix -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Contas a Receber</span>
                        <span class="code-tag font-tabular">PDF A4 Timbrado</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Inadimplência & Bloqueios MED</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Relação nominal de famílias com saldo devedor na carteira ou suspensão preventiva decorrente de contestações bancárias do Mecanismo Especial de Devolução (MED).
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto">
                    <a href="<?php echo $baseUrl; ?>/relatorios/devedores" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Relação de Devedores
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 6: Corte do Turno -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Operacional Fornecedor</span>
                        <span class="code-tag font-tabular">PDF A4 Timbrado</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Relatório Consolidado de Corte</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Totalização analítica de salgados e almoços agendados para compra exata com fornecedores locais, prevenindo sobra e falta de merenda.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto">
                    <a href="<?php echo $baseUrl; ?>/operacional/corte" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Painel de Corte
                    </a>
                    <a href="<?php echo $baseUrl; ?>/operacional/corte/pdf?data=<?php echo date('Y-m-d'); ?>&turno=matutino" class="btn btn-enterprise btn-primary btn-sm text-center fw-medium" target="_blank" title="Baixar PDF A4 de Hoje">
                        PDF Hoje
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 7: Movimentações de Estoque (Fase 23) -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Estoque & Logística</span>
                        <span class="code-tag font-tabular">Paisagem / Retrato</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">Movimentações de Estoque</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Demonstrativo analítico de entradas (fornecedor/XML), saídas (balcão/online) e perdas com opções de layout Paisagem/Retrato, agrupamento por grupo/produto e Centro de Custo Cantina Geral.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto flex-wrap">
                    <a href="<?php echo $baseUrl; ?>/relatorios/estoque-filtros" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Filtros & Layout
                    </a>
                    <a href="<?php echo $baseUrl; ?>/relatorios/estoque-movimentacoes/imprimir" class="btn btn-enterprise btn-secondary btn-sm text-center fw-medium" target="_blank" title="Visualizar / Imprimir Relatório Atual">
                        Visualizar A4
                    </a>
                </div>
            </div>
        </div>

        <!-- Relatório 8: DRE Gerencial Completo & Finanças (Fase 24) -->
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card-report" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px 24px; display: flex; flex-direction: column; justify-content: space-between; height: 100%; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-microcopy text-uppercase fw-semibold text-secondary">Controladoria & DRE</span>
                        <span class="code-tag font-tabular">Planilha + Gráficos</span>
                    </div>
                    <h2 class="card-report-title fw-bold text-dark mb-2" style="font-size: 16px; font-weight: 700 !important; color: #0f172a; line-height: 1.35;">DRE Gerencial & Despesas</h2>
                    <p class="text-secondary text-microcopy mb-3" style="line-height: 1.55;">
                        Demonstração do Resultado do Exercício comparativo mês a mês (Receita Bruta, Estornos, CMV, Lucro Bruto, Despesas Operacionais e Lucro Líquido Real) com gráficos e exportações.
                    </p>
                </div>
                <div class="d-flex gap-2 pt-3 border-top mt-auto flex-wrap">
                    <a href="<?php echo $baseUrl; ?>/financeiro/dre" class="btn btn-enterprise btn-primary btn-sm flex-fill text-center fw-medium">
                        Ver DRE Anual
                    </a>
                    <a href="<?php echo $baseUrl; ?>/financeiro/despesas" class="btn btn-enterprise btn-secondary btn-sm text-center fw-medium" title="Gerenciar Despesas Operacionais">
                        Despesas
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

