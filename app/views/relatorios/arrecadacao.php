<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Relatório Gerencial de Arrecadação Financeira & Fluxo de Entradas (Cash-In)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e diretrizes de design neutro empresarial
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$hoje = date('Y-m-d');
$ontem = date('Y-m-d', strtotime('-1 day'));
$inicioSemana = date('Y-m-d', strtotime('monday this week'));
$fimSemana = date('Y-m-d', strtotime('friday this week'));
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');
$inicioMesAnterior = date('Y-m-d', strtotime('first day of previous month'));
$fimMesAnterior = date('Y-m-d', strtotime('last day of previous month'));

$canais = $dados['canais'] ?? [];
$dias = $dados['dias'] ?? [];
?>

<div class="container-fluid p-0">

    <!-- Topbar Institucional do Relatório -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="<?php echo $baseUrl; ?>/relatorios" class="btn btn-sm btn-enterprise btn-secondary text-decoration-none py-1 px-2" title="Voltar ao Hub de Relatórios">
                    ← Relatórios
                </a>
                <span class="text-secondary text-microcopy text-uppercase font-weight-bold">Módulo Financeiro & Fluxo de Caixa</span>
            </div>
            <h1 class="h4 font-weight-bold mb-0 text-dark">Arrecadação Financeira & Fluxo de Entradas</h1>
            <p class="text-secondary text-microcopy mb-0 mt-1">
                Consolidação contábil de todo o dinheiro real que entrou na cantina (Recargas Pix Online, Recargas Balcão e Vendas Presenciais) no período.
            </p>
        </div>

        <!-- Ações de Exportação e Emissão -->
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao/imprimir?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
               target="_blank" 
               id="btnImprimirA4"
               onclick="executarAcaoExportacao(event, 'imprimir')"
               class="btn btn-sm btn-enterprise btn-secondary d-flex align-items-center gap-2"
               title="Visualizar espelho oficial A4 para conferência e impressão">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                <span>Visualizar / Imprimir (A4)</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao/pdf?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
               id="btnPdfA4"
               onclick="executarAcaoExportacao(event, 'pdf')"
               class="btn btn-sm btn-enterprise btn-secondary d-flex align-items-center gap-2"
               title="Baixar demonstrativo timbrado em PDF de alta qualidade">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                <span>Baixar em PDF (A4)</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao/csv?data_inicio=<?php echo urlencode($dataInicio); ?>&data_fim=<?php echo urlencode($dataFim); ?>" 
               id="btnCsv"
               onclick="executarAcaoExportacao(event, 'csv')"
               class="btn btn-sm btn-enterprise btn-secondary d-flex align-items-center gap-2"
               title="Exportar dados completos para planilha Microsoft Excel">
                <svg width="15" height="15" viewBox="0 0 16 16" fill="currentColor"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/><path d="M4.5 12.5A.5.5 0 0 1 5 12h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 10h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zm0-2A.5.5 0 0 1 5 8h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5z"/></svg>
                <span>Exportar Planilha (CSV)</span>
            </a>
        </div>
    </div>

    <!-- Filtro de Período & Atalhos Corporativos Neutros -->
    <div class="card card-enterprise mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
        <div class="card-body p-3">
            <form method="GET" action="<?php echo $baseUrl; ?>/relatorios/arrecadacao" id="formFiltroArrecadacao">
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
                    
                    <!-- Intervalo de Datas -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div>
                            <label for="dataInicio" class="form-label text-microcopy text-secondary text-uppercase fw-semibold mb-1 d-block">Data Inicial</label>
                            <input type="date" name="data_inicio" id="dataInicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div>
                            <label for="dataFim" class="form-label text-microcopy text-secondary text-uppercase fw-semibold mb-1 d-block">Data Final</label>
                            <input type="date" name="data_fim" id="dataFim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="pt-3">
                            <button type="submit" class="btn btn-sm btn-enterprise btn-primary">
                                Filtrar Período
                            </button>
                        </div>
                    </div>

                    <!-- Atalhos Rápidos de Seleção (Padrão Corporativo Neutro - SEM CORES) -->
                    <div>
                        <span class="text-microcopy text-secondary text-uppercase fw-semibold d-block mb-1">Atalhos de Período</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao?data_inicio=<?php echo $hoje; ?>&data_fim=<?php echo $hoje; ?>" class="btn btn-enterprise btn-secondary btn-atalho-data" data-inicio="<?php echo $hoje; ?>" data-fim="<?php echo $hoje; ?>" onclick="definirDatas(event, '<?php echo $hoje; ?>', '<?php echo $hoje; ?>')">Hoje</a>
                            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao?data_inicio=<?php echo $ontem; ?>&data_fim=<?php echo $ontem; ?>" class="btn btn-enterprise btn-secondary btn-atalho-data" data-inicio="<?php echo $ontem; ?>" data-fim="<?php echo $ontem; ?>" onclick="definirDatas(event, '<?php echo $ontem; ?>', '<?php echo $ontem; ?>')">Ontem</a>
                            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao?data_inicio=<?php echo $inicioSemana; ?>&data_fim=<?php echo $fimSemana; ?>" class="btn btn-enterprise btn-secondary btn-atalho-data" data-inicio="<?php echo $inicioSemana; ?>" data-fim="<?php echo $fimSemana; ?>" onclick="definirDatas(event, '<?php echo $inicioSemana; ?>', '<?php echo $fimSemana; ?>')">Esta Semana</a>
                            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao?data_inicio=<?php echo $inicioMes; ?>&data_fim=<?php echo $fimMes; ?>" class="btn btn-enterprise btn-secondary btn-atalho-data" data-inicio="<?php echo $inicioMes; ?>" data-fim="<?php echo $fimMes; ?>" onclick="definirDatas(event, '<?php echo $inicioMes; ?>', '<?php echo $fimMes; ?>')">Este Mês</a>
                            <a href="<?php echo $baseUrl; ?>/relatorios/arrecadacao?data_inicio=<?php echo $inicioMesAnterior; ?>&data_fim=<?php echo $fimMesAnterior; ?>" class="btn btn-enterprise btn-secondary btn-atalho-data" data-inicio="<?php echo $inicioMesAnterior; ?>" data-fim="<?php echo $fimMesAnterior; ?>" onclick="definirDatas(event, '<?php echo $inicioMesAnterior; ?>', '<?php echo $fimMesAnterior; ?>')">Mês Anterior</a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- Stat-Strip de Indicadores Financeiros de Cash-In -->
    <div class="stat-strip mb-4" style="display: flex; flex-wrap: wrap; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);">
        
        <!-- Cartão 1: Total Real Arrecadado (Cash-In) -->
        <div class="stat-strip-item" style="flex: 1 1 180px; min-width: 170px; padding: 14px 18px; border-right: 1px solid #e2e8f0; border-left: 3px solid #16a34a; background: #ffffff;">
            <span class="stat-strip-label" style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Total Arrecadado (Cash-In Real)</span>
            <span class="stat-strip-value font-tabular" style="font-size: 20px; font-weight: 700; color: #15803d !important; display: block; line-height: 1.25; margin-top: 2px;">
                R$ <?php echo number_format((float)($dados['total_arrecadado_bruto'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="stat-subtext font-tabular" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                <?php echo number_format((int)($dados['total_transacoes_arrecadacao'] ?? 0), 0, ',', '.'); ?> entradas financeiras reais
            </span>
        </div>

        <!-- Cartão 2: Recargas Pix Online (Portal da Família) -->
        <div class="stat-strip-item" style="flex: 1 1 180px; min-width: 170px; padding: 14px 18px; border-right: 1px solid #e2e8f0; background: #ffffff;">
            <span class="stat-strip-label" style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Recargas Pix Online (Portal)</span>
            <span class="stat-strip-value font-tabular text-dark" style="font-size: 18px; font-weight: 700; display: block; line-height: 1.25; margin-top: 2px;">
                R$ <?php echo number_format((float)($dados['recargas_pix_total'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="stat-subtext font-tabular" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                <?php echo number_format((int)($dados['recargas_pix_qtd'] ?? 0), 0, ',', '.'); ?> recargas pagas via QR Code
            </span>
        </div>

        <!-- Cartão 3: Recargas Balcão (Dinheiro Físico) -->
        <div class="stat-strip-item" style="flex: 1 1 180px; min-width: 170px; padding: 14px 18px; border-right: 1px solid #e2e8f0; background: #ffffff;">
            <span class="stat-strip-label" style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Recargas Balcão (Dinheiro)</span>
            <span class="stat-strip-value font-tabular text-dark" style="font-size: 18px; font-weight: 700; display: block; line-height: 1.25; margin-top: 2px;">
                R$ <?php echo number_format((float)($dados['recargas_dinheiro_total'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="stat-subtext font-tabular" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                <?php echo number_format((int)($dados['recargas_dinheiro_qtd'] ?? 0), 0, ',', '.'); ?> inserções presenciais
            </span>
        </div>

        <!-- Cartão 4: Vendas Presenciais Balcão (Dinheiro + Pix) -->
        <div class="stat-strip-item" style="flex: 1 1 180px; min-width: 170px; padding: 14px 18px; border-right: 1px solid #e2e8f0; background: #ffffff;">
            <span class="stat-strip-label" style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Vendas Presenciais Balcão</span>
            <span class="stat-strip-value font-tabular text-dark" style="font-size: 18px; font-weight: 700; display: block; line-height: 1.25; margin-top: 2px;">
                R$ <?php echo number_format((float)(($dados['vendas_dinheiro_total'] ?? 0) + ($dados['vendas_pix_total'] ?? 0)), 2, ',', '.'); ?>
            </span>
            <span class="stat-subtext font-tabular" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                Dinheiro: R$ <?php echo number_format((float)($dados['vendas_dinheiro_total'] ?? 0), 2, ',', '.'); ?> | Pix: R$ <?php echo number_format((float)($dados['vendas_pix_total'] ?? 0), 2, ',', '.'); ?>
            </span>
        </div>

        <!-- Cartão 5: Consumo de Saldo no Período (Informativo de Lanches) -->
        <div class="stat-strip-item" style="flex: 1 1 180px; min-width: 170px; padding: 14px 18px; background: #ffffff;">
            <span class="stat-strip-label" style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; display: block;">Consumo de Saldo (Lanches)</span>
            <span class="stat-strip-value font-tabular text-secondary" style="font-size: 18px; font-weight: 700; display: block; line-height: 1.25; margin-top: 2px;">
                R$ <?php echo number_format((float)($dados['total_consumo_saldo'] ?? 0), 2, ',', '.'); ?>
            </span>
            <span class="stat-subtext font-tabular" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">
                Variação: <strong><?php echo (($dados['variacao_carteira'] ?? 0) >= 0 ? '+' : '') . 'R$ ' . number_format((float)($dados['variacao_carteira'] ?? 0), 2, ',', '.'); ?></strong>
            </span>
        </div>

    </div>

    <!-- Grade com 2 Blocos Analíticos: Resumo por Canais e Conciliação de Carteira -->
    <div class="row g-4 mb-4">
        
        <!-- Bloco 1: Arrecadação por Canais de Entrada -->
        <div class="col-12 col-lg-7">
            <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; height: 100%;">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                    <div>
                        <span class="fw-semibold text-dark text-microcopy text-uppercase">Arrecadação por Canal de Pagamento</span>
                        <span class="text-muted text-microcopy d-block">Origem das entradas financeiras reais no período</span>
                    </div>
                    <span class="code-tag font-tabular"><?php echo $dados['periodo_oficial']; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-enterprise table-hover mb-0">
                        <thead>
                            <tr style="background: #f8fafc; font-size: 11px; text-transform: uppercase; color: #475569;">
                                <th style="padding: 10px 14px;">Canal / Meio</th>
                                <th style="padding: 10px 14px;">Ambiente</th>
                                <th class="text-end" style="padding: 10px 14px;">Transações</th>
                                <th class="text-end" style="padding: 10px 14px;">Total Arrecadado</th>
                                <th class="text-end" style="padding: 10px 14px;">Participação</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 12.5px;">
                            <?php foreach ($canais as $c): ?>
                            <tr>
                                <td style="padding: 10px 14px;">
                                    <strong class="text-dark"><?php echo htmlspecialchars($c['canal'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="text-muted text-microcopy d-block"><?php echo htmlspecialchars($c['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td style="padding: 10px 14px;">
                                    <span class="badge bg-light text-secondary border font-tabular" style="font-size: 10.5px;">
                                        <?php echo htmlspecialchars($c['tipo'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px 14px;" class="text-end font-tabular text-secondary">
                                    <?php echo number_format((int)$c['quantidade'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 10px 14px;" class="text-end font-tabular fw-semibold text-dark">
                                    R$ <?php echo number_format((float)$c['total'], 2, ',', '.'); ?>
                                </td>
                                <td style="padding: 10px 14px;" class="text-end font-tabular text-secondary">
                                    <?php echo number_format((float)$c['percentual'], 1, ',', '.'); ?>%
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="background: #f8fafc; font-weight: 700; font-size: 12.5px; border-top: 2px solid #cbd5e1;">
                            <tr>
                                <td colspan="2" style="padding: 10px 14px;" class="text-uppercase text-dark">
                                    Total Geral Arrecadado (Cash-In)
                                </td>
                                <td style="padding: 10px 14px;" class="text-end font-tabular text-dark">
                                    <?php echo number_format((int)$dados['total_transacoes_arrecadacao'], 0, ',', '.'); ?>
                                </td>
                                <td style="padding: 10px 14px;" class="text-end font-tabular text-success" style="color: #15803d !important;">
                                    R$ <?php echo number_format((float)$dados['total_arrecadado_bruto'], 2, ',', '.'); ?>
                                </td>
                                <td style="padding: 10px 14px;" class="text-end font-tabular text-dark">
                                    100.0%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bloco 2: Conciliação da Carteira Digital (Recargas vs Consumo de Lanches) -->
        <div class="col-12 col-lg-5">
            <div class="card card-enterprise" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; height: 100%;">
                <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
                    <div>
                        <span class="fw-semibold text-dark text-microcopy text-uppercase">Controle de Custódia da Carteira</span>
                        <span class="text-muted text-microcopy d-block">Recargas inseridas vs Lanches consumidos</span>
                    </div>
                </div>
                <div class="p-3">
                    <ul class="list-group list-group-flush mb-3" style="font-size: 13px;">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>(+) Recargas Pix no Portal da Família</span>
                            <span class="font-tabular fw-semibold text-dark">R$ <?php echo number_format((float)$dados['recargas_pix_total'], 2, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span>(+) Recargas no Balcão em Dinheiro</span>
                            <span class="font-tabular fw-semibold text-dark">R$ <?php echo number_format((float)$dados['recargas_dinheiro_total'], 2, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 bg-light rounded px-2 my-1">
                            <strong class="text-dark">(=) Total de Recargas Adicionadas</strong>
                            <strong class="font-tabular text-primary">R$ <?php echo number_format((float)$dados['total_recargas'], 2, ',', '.'); ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 text-secondary">
                            <span>(-) Lanches Consumidos em Saldo no Balcão</span>
                            <span class="font-tabular">R$ <?php echo number_format((float)$dados['vendas_saldo_total'], 2, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 text-secondary">
                            <span>(-) Lanches Consumidos em Pedidos Online</span>
                            <span class="font-tabular">R$ <?php echo number_format((float)$dados['pedidos_saldo_total'], 2, ',', '.'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 bg-light rounded px-2 my-1">
                            <strong class="text-dark">(=) Total de Lanches Entregues</strong>
                            <strong class="font-tabular text-danger">R$ <?php echo number_format((float)$dados['total_consumo_saldo'], 2, ',', '.'); ?></strong>
                        </li>
                    </ul>

                    <!-- Variação Líquida de Saldo -->
                    <div class="p-3 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-microcopy text-uppercase fw-semibold text-secondary">Variação Líquida em Custódia</span>
                            <span class="font-tabular fw-bold <?php echo $dados['variacao_carteira'] >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-size: 15px;">
                                <?php echo ($dados['variacao_carteira'] >= 0 ? '+' : '') . 'R$ ' . number_format((float)$dados['variacao_carteira'], 2, ',', '.'); ?>
                            </span>
                        </div>
                        <p class="text-secondary text-microcopy mb-0" style="line-height: 1.4;">
                            <?php if ($dados['variacao_carteira'] >= 0): ?>
                                As famílias recarregaram mais saldo do que os alunos consumiram em lanches neste período. Esse excedente financeiro permanece seguro na carteira para compras futuras.
                            <?php else: ?>
                                Os alunos consumiram saldo acumulado de recargas anteriores. O valor de lanches entregues superou as novas recargas do período.
                            <?php endif; ?>
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Tabela Detalhada Cronológica: Arrecadação Dia a Dia -->
    <div class="card card-enterprise mb-4" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center py-2.5 px-3 bg-light border-bottom">
            <div>
                <span class="fw-semibold text-dark text-microcopy text-uppercase">Detalhamento Diário de Entradas e Consumo</span>
                <span class="text-muted text-microcopy d-block">Movimentações financeiras e consumo de saldo discriminados data a data</span>
            </div>
            <span class="code-tag font-tabular"><?php echo count($dias); ?> dias apurados</span>
        </div>
        <div class="table-responsive">
            <table class="table table-enterprise table-hover mb-0">
                <thead>
                    <tr style="background: #f8fafc; font-size: 11px; text-transform: uppercase; color: #475569;">
                        <th style="padding: 10px 14px;">Data</th>
                        <th style="padding: 10px 14px;">Dia da Semana</th>
                        <th class="text-end" style="padding: 10px 14px;">Recargas Pix (App)</th>
                        <th class="text-end" style="padding: 10px 14px;">Recargas Dinheiro</th>
                        <th class="text-end" style="padding: 10px 14px;">Vendas Dinheiro</th>
                        <th class="text-end" style="padding: 10px 14px;">Vendas Pix</th>
                        <th class="text-end" style="padding: 10px 14px; background: #f1f5f9; font-weight: 700;">Total Arrecadado (Dia)</th>
                        <th class="text-end" style="padding: 10px 14px;">Consumo Saldo</th>
                        <th class="text-end" style="padding: 10px 14px;">Variação Saldo</th>
                    </tr>
                </thead>
                <tbody style="font-size: 12.5px;">
                    <?php if (empty($dias)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            Não há movimentações para o período selecionado.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($dias as $d): ?>
                        <tr <?php echo ($d['total_arrecadado'] == 0 && $d['consumo_saldo'] == 0) ? 'class="text-muted"' : ''; ?>>
                            <td style="padding: 10px 14px;" class="font-tabular fw-semibold text-dark">
                                <?php echo htmlspecialchars($d['data_formatada'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-secondary">
                                <?php echo htmlspecialchars($d['dia_semana'], ENT_QUOTES, 'UTF-8'); ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-end font-tabular">
                                <?php echo $d['recargas_pix'] > 0 ? 'R$ ' . number_format((float)$d['recargas_pix'], 2, ',', '.') : '&mdash;'; ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-end font-tabular">
                                <?php echo $d['recargas_dinheiro'] > 0 ? 'R$ ' . number_format((float)$d['recargas_dinheiro'], 2, ',', '.') : '&mdash;'; ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-end font-tabular">
                                <?php echo $d['vendas_dinheiro'] > 0 ? 'R$ ' . number_format((float)$d['vendas_dinheiro'], 2, ',', '.') : '&mdash;'; ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-end font-tabular">
                                <?php echo $d['vendas_pix'] > 0 ? 'R$ ' . number_format((float)$d['vendas_pix'], 2, ',', '.') : '&mdash;'; ?>
                            </td>
                            <td style="padding: 10px 14px; background: #f8fafc;" class="text-end font-tabular fw-bold text-dark">
                                R$ <?php echo number_format((float)$d['total_arrecadado'], 2, ',', '.'); ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-end font-tabular text-secondary">
                                <?php echo $d['consumo_saldo'] > 0 ? 'R$ ' . number_format((float)$d['consumo_saldo'], 2, ',', '.') : '&mdash;'; ?>
                            </td>
                            <td style="padding: 10px 14px;" class="text-end font-tabular <?php echo $d['variacao_saldo'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo ($d['variacao_saldo'] >= 0 ? '+' : '') . 'R$ ' . number_format((float)$d['variacao_saldo'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot style="background: #f8fafc; font-weight: 700; font-size: 12.5px; border-top: 2px solid #cbd5e1;">
                    <tr>
                        <td colspan="2" style="padding: 10px 14px;" class="text-uppercase text-dark">
                            Total Geral do Período
                        </td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular">
                            R$ <?php echo number_format((float)$dados['recargas_pix_total'], 2, ',', '.'); ?>
                        </td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular">
                            R$ <?php echo number_format((float)$dados['recargas_dinheiro_total'], 2, ',', '.'); ?>
                        </td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular">
                            R$ <?php echo number_format((float)$dados['vendas_dinheiro_total'], 2, ',', '.'); ?>
                        </td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular">
                            R$ <?php echo number_format((float)$dados['vendas_pix_total'], 2, ',', '.'); ?>
                        </td>
                        <td style="padding: 10px 14px; background: #f1f5f9;" class="text-end font-tabular text-success" style="color: #15803d !important;">
                            R$ <?php echo number_format((float)$dados['total_arrecadado_bruto'], 2, ',', '.'); ?>
                        </td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular text-secondary">
                            R$ <?php echo number_format((float)$dados['total_consumo_saldo'], 2, ',', '.'); ?>
                        </td>
                        <td style="padding: 10px 14px;" class="text-end font-tabular <?php echo $dados['variacao_carteira'] < 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo ($dados['variacao_carteira'] >= 0 ? '+' : '') . 'R$ ' . number_format((float)$dados['variacao_carteira'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

<script>
function atualizarLinksExportacao() {
    var inputInicio = document.getElementById('dataInicio');
    var inputFim = document.getElementById('dataFim');
    var ini = (inputInicio && inputInicio.value) ? inputInicio.value : '<?php echo $dataInicio; ?>';
    var fim = (inputFim && inputFim.value) ? inputFim.value : '<?php echo $dataFim; ?>';
    
    var baseUrl = '<?php echo $baseUrl; ?>';
    var btnImp = document.getElementById('btnImprimirA4');
    var btnPdf = document.getElementById('btnPdfA4');
    var btnCsv = document.getElementById('btnCsv');
    
    var query = '?data_inicio=' + encodeURIComponent(ini) + '&data_fim=' + encodeURIComponent(fim);
    if (btnImp) btnImp.href = baseUrl + '/relatorios/arrecadacao/imprimir' + query;
    if (btnPdf) btnPdf.href = baseUrl + '/relatorios/arrecadacao/pdf' + query;
    if (btnCsv) btnCsv.href = baseUrl + '/relatorios/arrecadacao/csv' + query;
}

function executarAcaoExportacao(event, tipo) {
    var inputInicio = document.getElementById('dataInicio');
    var inputFim = document.getElementById('dataFim');
    var ini = (inputInicio && inputInicio.value) ? inputInicio.value : '<?php echo $dataInicio; ?>';
    var fim = (inputFim && inputFim.value) ? inputFim.value : '<?php echo $dataFim; ?>';
    
    var url = '<?php echo $baseUrl; ?>/relatorios/arrecadacao/' + tipo + '?data_inicio=' + encodeURIComponent(ini) + '&data_fim=' + encodeURIComponent(fim);
    
    if (tipo === 'imprimir') {
        window.open(url, '_blank');
        if (event && event.preventDefault) event.preventDefault();
        return false;
    } else {
        window.location.href = url;
        if (event && event.preventDefault) event.preventDefault();
        return false;
    }
}

function definirDatas(event, inicio, fim) {
    var inputInicio = document.getElementById('dataInicio');
    var inputFim = document.getElementById('dataFim');
    
    if (inputInicio) inputInicio.value = inicio;
    if (inputFim) inputFim.value = fim;
    
    atualizarLinksExportacao();
    
    var form = document.getElementById('formFiltroArrecadacao');
    if (form) {
        if (event && event.preventDefault) event.preventDefault();
        form.submit();
        return false;
    }
}

window.atualizarLinksExportacao = atualizarLinksExportacao;
window.executarAcaoExportacao = executarAcaoExportacao;
window.definirDatas = definirDatas;

document.addEventListener('DOMContentLoaded', function() {
    var inputInicio = document.getElementById('dataInicio');
    var inputFim = document.getElementById('dataFim');
    
    if (inputInicio) {
        inputInicio.addEventListener('change', atualizarLinksExportacao);
        inputInicio.addEventListener('input', atualizarLinksExportacao);
    }
    if (inputFim) {
        inputFim.addEventListener('change', atualizarLinksExportacao);
        inputFim.addEventListener('input', atualizarLinksExportacao);
    }
    
    atualizarLinksExportacao();
});
</script>

