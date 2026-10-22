<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Estorno de Venda da Cantina
 * 
 * Fluxo e layout alinhados ao sistema legado Nutre / Escolar Manager
 * Conforme docs/FSD.md, docs/DESIGN.md e orientações visuais oficiais
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfToken = \App\Core\Session::generateCsrfToken();
$vendas = $vendas ?? [];
$alunos = $alunos ?? [];
$colaboradores = $colaboradores ?? [];

$vendaAvulsa = $vendaAvulsa ?? 'nao';
$filtrarPor = $filtrarPor ?? 'aluno';
$pessoaId = $pessoaId ?? null;
$alunoId = $alunoId ?? null;
$colaboradorId = $colaboradorId ?? null;
$nomePessoaSelecionada = $nomePessoaSelecionada ?? '';
$dataInicio = $dataInicio ?? date('Y-m-d');
$dataFim = $dataFim ?? date('Y-m-d');
$motivoFiltro = $motivoFiltro ?? '1-ESTORNO';
$carregado = $carregado ?? false;
?>

<!-- Cabeçalho da Página (Título limpo, sem botões soltos no topo, padrão Nutre) -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Estorno de Venda da Cantina</h1>
            <span class="badge bg-white text-primary border border-primary-subtle font-tabular" style="font-size: 11px; font-weight: 600;">
                Módulo Operacional
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Localização e reversão atômica de vendas avulsas de balcão ou encomendas online com reconstituição de estoque e saldo.
        </p>
    </div>
</div>

<!-- Alertas Flash do Sistema -->
<?php require __DIR__ . '/../partials/alert.php'; ?>

<!-- Painel de Filtros (Padrão Nutre: Venda avulsa Sim/Não, Filtrar por, Aluno, Período De/Até e botão Carregar) -->
<div class="card-enterprise card-overflow-visible mb-3" style="overflow: visible !important; position: relative; z-index: 30;">
    <div class="card-enterprise-body p-3" style="overflow: visible !important;">
        <form id="formFiltrosEstorno" action="<?php echo $baseUrl; ?>/operacional/estornos" method="GET" style="overflow: visible !important;">
            <input type="hidden" name="carregar" value="1">
            
            <!-- Linha 1 de Filtros: Venda avulsa, Filtrar por, Aluno/Pessoa -->
            <div class="row g-3 align-items-end mb-2" style="overflow: visible !important;">
                
                <!-- Venda Avulsa (Sim / Não) - Segmented Control estilo Nutre -->
                <div class="col-12 col-sm-4 col-md-2">
                    <label class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Venda avulsa</label>
                    <div class="btn-group btn-group-sm w-100" role="group" aria-label="Venda Avulsa">
                        <input type="radio" class="btn-check" name="venda_avulsa" id="vaSim" value="sim" autocomplete="off" <?php echo ($vendaAvulsa === 'sim') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-secondary" for="vaSim" style="font-weight: 500;">Sim</label>

                        <input type="radio" class="btn-check" name="venda_avulsa" id="vaNao" value="nao" autocomplete="off" <?php echo ($vendaAvulsa === 'nao') ? 'checked' : ''; ?>>
                        <label class="btn btn-outline-secondary" for="vaNao" style="font-weight: 500;">Não</label>
                    </div>
                </div>

                <!-- Filtrar Por (Dropdown Aluno / Colaborador) -->
                <div class="col-12 col-sm-4 col-md-2" id="boxFiltrarPor">
                    <label for="selectFiltrarPor" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1">Filtrar por</label>
                    <select class="form-select form-select-sm" name="filtrar_por" id="selectFiltrarPor">
                        <option value="aluno" <?php echo ($filtrarPor === 'aluno') ? 'selected' : ''; ?>>Aluno</option>
                        <option value="colaborador" <?php echo ($filtrarPor === 'colaborador') ? 'selected' : ''; ?> <?php echo ($vendaAvulsa === 'nao') ? 'disabled' : ''; ?>>Colaborador</option>
                    </select>
                </div>

                <!-- Aluno / Colaborador (Campo de Busca / Autocomplete sem rolagem) -->
                <div class="col-12 col-sm-4 col-md-8" style="position: relative; z-index: 40;">
                    <label for="inputBuscaPessoa" class="form-label text-microcopy text-uppercase font-weight-bold text-secondary mb-1" id="lblBuscaPessoa">
                        <?php echo ($filtrarPor === 'colaborador' && $vendaAvulsa === 'sim') ? 'Colaborador' : 'Aluno'; ?>
                    </label>
                    
                    <div class="search-input-wrapper position-relative" style="position: relative;">
                        <input type="text" id="inputBuscaPessoa" class="form-control form-control-sm pe-4" 
                               placeholder="<?php echo ($filtrarPor === 'colaborador' && $vendaAvulsa === 'sim') ? 'Selecione ou digite o nome do colaborador...' : 'Selecione ou digite o nome do aluno...'; ?>" 
                               autocomplete="off" 
                               value="<?php echo htmlspecialchars($nomePessoaSelecionada, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="pessoa_id" id="hiddenPessoaId" value="<?php echo htmlspecialchars((string)($pessoaId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="aluno_id" id="hiddenAlunoId" value="<?php echo htmlspecialchars((string)($alunoId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="colaborador_id" id="hiddenColaboradorId" value="<?php echo htmlspecialchars((string)($colaboradorId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

                        <!-- Botão Limpar Seleção -->
                        <button type="button" id="btnLimparPessoa" class="btn btn-sm btn-link text-secondary position-absolute" 
                                style="right: 6px; top: 50%; transform: translateY(-50%); text-decoration: none; padding: 0; line-height: 1; font-size: 14px; <?php echo empty($nomePessoaSelecionada) ? 'display: none;' : ''; ?>" 
                                title="Limpar seleção">
                            ✕
                        </button>

                        <!-- Dropdown Flutuante de Autocomplete -->
                        <div id="autocompleteDropdownPessoa" class="list-group position-absolute rounded shadow-lg d-none" 
                             style="top: calc(100% + 4px); left: 0; right: 0; width: 100%; z-index: 1080; max-height: 260px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2), 0 8px 10px -6px rgba(15, 23, 42, 0.12) !important;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Linha 2 de Filtros: Período (De / Até) e Botão Carregar -->
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-6">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-2 text-sm-start">
                            <span class="text-microcopy text-uppercase font-weight-bold text-secondary">Período</span>
                        </div>
                        <div class="col-6 col-sm-5 d-flex align-items-center gap-1">
                            <label for="filtroDataInicio" class="form-label text-microcopy text-secondary mb-0">De</label>
                            <input type="date" class="form-control form-control-sm font-tabular" id="filtroDataInicio" name="data_inicio" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="col-6 col-sm-5 d-flex align-items-center gap-1">
                            <label for="filtroDataFim" class="form-label text-microcopy text-secondary mb-0">Até</label>
                            <input type="date" class="form-control form-control-sm font-tabular" id="filtroDataFim" name="data_fim" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Botão Carregar (Padrão Nutre: alinhado à direita com ícone de recarga) -->
                <div class="col-12 col-md-6 text-md-end text-end">
                    <button type="submit" id="btnCarregar" class="btn btn-enterprise btn-outline-secondary btn-sm px-3 d-inline-flex align-items-center gap-1.5 shadow-sm" title="Carregar vendas com estes filtros">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                        </svg>
                        <strong>Carregar</strong>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Seção do Estorno pós-carregamento (Motivo do estorno + Botões Cancelar e Gravar, estilo Nutre) -->
<?php if ($carregado): ?>
<div class="card-enterprise mb-3 p-3 bg-white border" id="painelAcoesEstorno">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <!-- Campo Motivo do estorno (Dropdown estilo Nutre: 1-ESTORNO) -->
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 520px;">
            <label for="selectMotivoEstorno" class="form-label text-secondary small font-weight-bold mb-0 text-nowrap">
                Motivo do estorno: <span class="text-danger">*</span>
            </label>
            <select class="form-select form-select-sm font-tabular" id="selectMotivoEstorno" name="motivo_estorno">
                <option value="1-ESTORNO" <?php echo ($motivoFiltro === '1-ESTORNO') ? 'selected' : ''; ?>>1-ESTORNO</option>
                <option value="2-DESISTÊNCIA DO ALUNO" <?php echo ($motivoFiltro === '2-DESISTÊNCIA DO ALUNO') ? 'selected' : ''; ?>>2-DESISTÊNCIA DO ALUNO</option>
                <option value="3-FALTA DE PRODUTO" <?php echo ($motivoFiltro === '3-FALTA DE PRODUTO') ? 'selected' : ''; ?>>3-FALTA DE PRODUTO</option>
                <option value="4-ERRO DE LANÇAMENTO" <?php echo ($motivoFiltro === '4-ERRO DE LANÇAMENTO') ? 'selected' : ''; ?>>4-ERRO DE LANÇAMENTO</option>
                <option value="5-DUPLICIDADE" <?php echo ($motivoFiltro === '5-DUPLICIDADE') ? 'selected' : ''; ?>>5-DUPLICIDADE</option>
                <option value="6-OUTRO" <?php echo ($motivoFiltro === '6-OUTRO' || !in_array($motivoFiltro, ['1-ESTORNO', '2-DESISTÊNCIA DO ALUNO', '3-FALTA DE PRODUTO', '4-ERRO DE LANÇAMENTO', '5-DUPLICIDADE'])) ? 'selected' : ''; ?>>6-OUTRO</option>
            </select>
            <input type="text" class="form-control form-control-sm d-none" id="inputMotivoOutro" placeholder="Especifique o motivo do estorno..." maxlength="200" value="<?php echo (!in_array($motivoFiltro, ['1-ESTORNO', '2-DESISTÊNCIA DO ALUNO', '3-FALTA DE PRODUTO', '4-ERRO DE LANÇAMENTO', '5-DUPLICIDADE', '6-OUTRO'])) ? htmlspecialchars($motivoFiltro, ENT_QUOTES, 'UTF-8') : ''; ?>">
        </div>

        <!-- Botões Cancelar e Gravar (Alinhados à direita junto com o Motivo, padrão Nutre) -->
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo $baseUrl; ?>/operacional/estornos" class="btn btn-enterprise btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" id="btnCancelarEstorno" title="Cancelar seleção e limpar tela">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
                Cancelar
            </a>
            <button type="button" id="btnGravarEstorno" class="btn btn-enterprise btn-primary btn-sm d-inline-flex align-items-center gap-1.5 shadow-sm" title="Gravar o estorno das vendas marcadas">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4.414A1 1 0 0 0 14.586 3.707l-2.293-2.293A1 1 0 0 0 11.586 1H2zm10 2v2H4V3h8zm-8 4h8v6H4V7z"/>
                </svg>
                <strong>Gravar</strong>
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulário que submete a gravação das vendas selecionadas -->
<form id="formGravarEstorno" action="<?php echo $baseUrl; ?>/operacional/estornos/gravar" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="venda_avulsa" value="<?php echo htmlspecialchars($vendaAvulsa, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="tipo_venda" value="<?php echo htmlspecialchars($vendaAvulsa, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="filtrar_por" value="<?php echo htmlspecialchars($filtrarPor, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="pessoa_id" value="<?php echo htmlspecialchars((string)($pessoaId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="aluno_id" value="<?php echo htmlspecialchars((string)($alunoId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="colaborador_id" value="<?php echo htmlspecialchars((string)($colaboradorId ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="data_inicio" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="data_fim" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="motivo_estorno" id="hiddenMotivoEstorno" value="">

    <!-- Tabela de Vendas (Colunas estritas do Nutre: Checkbox | Data | Hora | Valor total | Pessoa) -->
    <div class="card-enterprise mb-3">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm mb-0 align-middle" id="tabelaVendasEstorno">
                <thead style="background-color: #f1f5f9; color: #1e293b; border-bottom: 2px solid #cbd5e1;">
                    <tr style="height: 38px;">
                        <th style="width: 44px; text-align: center;">
                            <input class="form-check-input" type="checkbox" id="checkTodos" title="Selecionar ou desmarcar todas">
                        </th>
                        <th style="width: 120px; font-weight: 600; font-size: 12px;" class="font-tabular">Data</th>
                        <th style="width: 100px; font-weight: 600; font-size: 12px;" class="font-tabular">Hora</th>
                        <th style="width: 130px; font-weight: 600; font-size: 12px; text-align: right;" class="font-tabular">Valor total</th>
                        <th style="font-weight: 600; font-size: 12px;">Pessoa</th>
                    </tr>
                </thead>
                <tbody id="corpoTabelaVendas">
                    <?php if (!$carregado || empty($vendas)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-secondary">
                                <div class="py-3">
                                    <div class="small font-weight-medium text-secondary">
                                        Não há nada para mostrar aqui
                                    </div>
                                    <?php if (!$carregado): ?>
                                        <div class="text-secondary text-microcopy mt-1">Preencha os filtros acima e clique em <strong>Carregar</strong> para visualizar as vendas.</div>
                                    <?php else: ?>
                                        <div class="text-secondary text-microcopy mt-1">Nenhuma venda encontrada para o período e pessoa selecionados.</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendas as $v): 
                            $vId = (int)$v['id'];
                            $valorVenda = (float)($v['valor_total'] ?? 0.0);
                            $dataVenda = !empty($v['data_venda']) 
                                ? date('d/m/Y', strtotime($v['data_venda'])) 
                                : (!empty($v['data_consumo']) 
                                    ? date('d/m/Y', strtotime($v['data_consumo'])) 
                                    : (!empty($v['created_at']) 
                                        ? date('d/m/Y', strtotime($v['created_at'])) 
                                        : '--/--/----'));
                            $horaVenda = !empty($v['hora_venda']) 
                                ? substr($v['hora_venda'], 0, 5) 
                                : (!empty($v['hora_criacao']) 
                                    ? substr($v['hora_criacao'], 0, 5) 
                                    : (!empty($v['created_at']) 
                                        ? date('H:i', strtotime($v['created_at'])) 
                                        : '--:--'));
                            $tipoOrigem = $v['tipo_origem'] ?? ($vendaAvulsa === 'sim' ? 'balcao' : 'pedido');
                            $nomePessoa = $v['pessoa_nome'] ?? ($v['aluno_nome'] ?? ($v['colaborador_nome'] ?? 'Consumidor Geral'));
                            $detalhePessoa = $v['pessoa_detalhe'] ?? '';
                        ?>
                            <tr id="linhaVenda_<?php echo $vId; ?>" class="linha-venda-clicavel" data-id="<?php echo $vId; ?>" data-tipo="<?php echo $tipoOrigem; ?>" style="cursor: pointer;">
                                <td style="text-align: center;" onclick="event.stopPropagation();">
                                    <input class="form-check-input check-venda" type="checkbox" name="selecionadas[]" value="<?php echo $vId; ?>" data-valor="<?php echo $valorVenda; ?>">
                                </td>
                                <td class="font-tabular small"><?php echo $dataVenda; ?></td>
                                <td class="font-tabular small"><?php echo $horaVenda; ?></td>
                                <td class="font-tabular small font-weight-bold text-end" style="color: #0f172a;">
                                    R$ <?php echo number_format($valorVenda, 2, ',', '.'); ?>
                                </td>
                                <td>
                                    <div class="small font-weight-medium text-dark"><?php echo htmlspecialchars($nomePessoa, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if (!empty($detalhePessoa)): ?>
                                        <div class="text-secondary text-microcopy"><?php echo htmlspecialchars($detalhePessoa, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Rodapé do Data-Grid com Barra de Ferramentas (Padrão Nutre: Ícones no rodapé à esquerda com "Informações VENDAS") -->
        <div class="card-enterprise-footer bg-light border-top p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <!-- Barra de Ferramentas / Ações do Grid (Estilo Nutre) -->
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="btnToolbarRecarregar" title="Recarregar">
                    <svg width="13" height="13" viewBox="0 0 16 16" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                        <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                    </svg>
                </button>
                
                <!-- Botão Informações Vendas: Anotado à mão pelo usuário "(i) -> Informações VENDAS" -->
                <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1.5" id="btnInfoVendas" title="Informações VENDAS (Ver detalhes e itens da venda selecionada)">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        <circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.2"/>
                    </svg>
                    <span style="font-size: 12px; font-weight: 500;">Informações VENDAS</span>
                </button>
            </div>

            <!-- Indicadores e Totais da Seleção à direita -->
            <div class="d-flex align-items-center gap-3">
                <div class="small text-secondary">
                    Total de registros: <span class="font-tabular font-weight-bold text-dark" id="txtTotalRegistros"><?php echo count($vendas); ?></span>
                </div>
                <div class="small text-secondary">
                    Selecionados: <span class="font-tabular font-weight-bold text-primary" id="txtTotalSelecionados">0</span>
                </div>
                <div class="small text-secondary">
                    Valor total a estornar: <strong class="font-tabular text-dark fs-6" id="txtSomaSelecionados">R$ 0,00</strong>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal de Informações da Venda (Informações VENDAS) -->
<div class="modal fade" id="modalDetalhesVenda" tabindex="-1" aria-labelledby="modalDetalhesVendaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2" style="background-color: #0f172a; color: #ffffff;">
                <h5 class="modal-title fs-6 font-weight-bold d-flex align-items-center gap-2" id="modalDetalhesVendaLabel">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        <circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.2"/>
                    </svg>
                    <span id="modTitulo">Informações VENDAS</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-3" id="modCorpoDetalhes">
                <div class="text-center py-4" id="modLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando detalhes...</span>
                    </div>
                    <div class="small text-secondary mt-2">Buscando itens da venda...</div>
                </div>
                <div id="modConteudo" style="display: none;">
                    <!-- Preenchido dinamicamente via JS -->
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-enterprise btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação Corporativa de Estorno -->
<div class="modal fade" id="modalConfirmarEstorno" tabindex="-1" aria-labelledby="modalConfirmarEstornoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2" style="background-color: #0f172a; color: #ffffff;">
                <h5 class="modal-title fs-6 font-weight-bold" id="modalConfirmarEstornoLabel">
                    Confirmação de Estorno
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-shrink-0 text-primary mt-1">
                        <svg width="28" height="28" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.05-7.95a.5.5 0 0 0-.5.5v5a.5.5 0 0 0 1 0v-5a.5.5 0 0 0-.5-.5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-weight-bold text-dark mb-1">Deseja realmente estornar as vendas selecionadas?</div>
                        <p class="small text-secondary mb-2" id="txtConfirmarResumo">
                            Você está prestes a estornar 1 venda(s) no total de R$ 0,00.
                        </p>
                        <div class="p-2 mb-2 rounded bg-light border text-microcopy text-secondary">
                            <strong class="text-dark">Atenção:</strong> O estoque físico será reintegrado, saldos/encomendas serão recalculados e tickets térmicos associados serão invalidados permanentemente.
                        </div>
                        <div class="small">
                            <strong>Motivo selecionado:</strong>
                            <div class="p-2 mt-1 rounded bg-light border text-dark font-weight-semibold font-tabular" id="txtConfirmarMotivo">1-ESTORNO</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-enterprise btn-outline-secondary btn-sm" data-bs-dismiss="modal">Voltar</button>
                <button type="button" id="btnConfirmarEstornoFinal" class="btn btn-enterprise btn-primary btn-sm">
                    Confirmar e Gravar Estorno
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = <?php echo json_encode($baseUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const listaAlunos = <?php echo json_encode($alunos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const listaColaboradores = <?php echo json_encode($colaboradores, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    const formFiltros = document.getElementById('formFiltrosEstorno');
    const formGravar = document.getElementById('formGravarEstorno');
    const checkTodos = document.getElementById('checkTodos');
    const checksVenda = document.querySelectorAll('.check-venda');
    const txtTotalSelecionados = document.getElementById('txtTotalSelecionados');
    const txtSomaSelecionados = document.getElementById('txtSomaSelecionados');
    const btnGravarEstorno = document.getElementById('btnGravarEstorno');
    const selectMotivoEstorno = document.getElementById('selectMotivoEstorno');
    const inputMotivoOutro = document.getElementById('inputMotivoOutro');
    const hiddenMotivoEstorno = document.getElementById('hiddenMotivoEstorno');
    const btnToolbarRecarregar = document.getElementById('btnToolbarRecarregar');
    const btnInfoVendas = document.getElementById('btnInfoVendas');

    // Filtros de Alternância
    const radiosVendaAvulsa = document.querySelectorAll('input[name="venda_avulsa"]');
    const selectFiltrarPor = document.getElementById('selectFiltrarPor');
    const lblBuscaPessoa = document.getElementById('lblBuscaPessoa');
    const inputBuscaPessoa = document.getElementById('inputBuscaPessoa');
    const hiddenPessoaId = document.getElementById('hiddenPessoaId');
    const hiddenAlunoId = document.getElementById('hiddenAlunoId');
    const hiddenColaboradorId = document.getElementById('hiddenColaboradorId');
    const btnLimparPessoa = document.getElementById('btnLimparPessoa');
    const dropdownPessoa = document.getElementById('autocompleteDropdownPessoa');

    // Modais
    const modalDetalhesEl = document.getElementById('modalDetalhesVenda');
    const modalDetalhes = modalDetalhesEl ? new bootstrap.Modal(modalDetalhesEl) : null;
    const modalConfirmarEl = document.getElementById('modalConfirmarEstorno');
    const modalConfirmar = modalConfirmarEl ? new bootstrap.Modal(modalConfirmarEl) : null;

    let linhaSelecionadaAtiva = null;

    // Recálculo de Totais Selecionados
    function recalcularTotais() {
        let qtd = 0;
        let soma = 0.0;
        checksVenda.forEach(function(ck) {
            if (ck.checked) {
                qtd++;
                soma += parseFloat(ck.dataset.valor || 0);
            }
        });

        if (txtTotalSelecionados) txtTotalSelecionados.textContent = qtd;
        if (txtSomaSelecionados) txtSomaSelecionados.textContent = 'R$ ' + soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        if (checkTodos) {
            if (checksVenda.length > 0 && qtd === checksVenda.length) {
                checkTodos.checked = true;
                checkTodos.indeterminate = false;
            } else if (qtd > 0) {
                checkTodos.checked = false;
                checkTodos.indeterminate = true;
            } else {
                checkTodos.checked = false;
                checkTodos.indeterminate = false;
            }
        }
    }

    if (checkTodos) {
        checkTodos.addEventListener('change', function() {
            const marcar = checkTodos.checked;
            checksVenda.forEach(function(ck) {
                ck.checked = marcar;
                const tr = ck.closest('tr');
                if (tr) {
                    if (marcar) tr.classList.add('table-primary-subtle');
                    else tr.classList.remove('table-primary-subtle');
                }
            });
            recalcularTotais();
        });
    }

    checksVenda.forEach(function(ck) {
        ck.addEventListener('change', function() {
            const tr = this.closest('tr');
            if (tr) {
                if (this.checked) {
                    tr.classList.add('table-primary-subtle');
                    linhaSelecionadaAtiva = tr;
                } else {
                    tr.classList.remove('table-primary-subtle');
                }
            }
            recalcularTotais();
        });
    });

    // Clique na Linha da Tabela (Seleciona e permite Informações VENDAS)
    const linhasTabela = document.querySelectorAll('.linha-venda-clicavel');
    linhasTabela.forEach(function(tr) {
        tr.addEventListener('click', function(e) {
            // Se clicou no checkbox, já foi tratado
            if (e.target.tagName.toLowerCase() === 'input') return;

            linhasTabela.forEach(function(l) { l.classList.remove('table-active'); });
            this.classList.add('table-active');
            linhaSelecionadaAtiva = this;

            // Alterna o checkbox da linha
            const ck = this.querySelector('.check-venda');
            if (ck) {
                ck.checked = !ck.checked;
                if (ck.checked) this.classList.add('table-primary-subtle');
                else this.classList.remove('table-primary-subtle');
                recalcularTotais();
            }
        });

        // Duplo clique na linha abre direto os detalhes da venda
        tr.addEventListener('dblclick', function() {
            abrirDetalhesVenda(this.dataset.id, this.dataset.tipo);
        });
    });

    // Botão Recarregar Toolbar
    if (btnToolbarRecarregar) {
        btnToolbarRecarregar.addEventListener('click', function() {
            formFiltros.submit();
        });
    }

    // Botão Informações VENDAS
    if (btnInfoVendas) {
        btnInfoVendas.addEventListener('click', function() {
            // Verifica se há linha ativa ou ao menos um checkbox marcado
            let targetTr = linhaSelecionadaAtiva;
            if (!targetTr) {
                const primeiroChecado = document.querySelector('.check-venda:checked');
                if (primeiroChecado) targetTr = primeiroChecado.closest('tr');
            }

            if (!targetTr && linhasTabela.length === 1) {
                targetTr = linhasTabela[0];
            }

            if (!targetTr) {
                alert('Selecione uma venda na tabela para visualizar as informações.');
                return;
            }

            abrirDetalhesVenda(targetTr.dataset.id, targetTr.dataset.tipo);
        });
    }

    // Alternador Venda Avulsa (Sim / Não)
    radiosVendaAvulsa.forEach(function(r) {
        r.addEventListener('change', function() {
            hiddenPessoaId.value = '';
            hiddenAlunoId.value = '';
            hiddenColaboradorId.value = '';
            inputBuscaPessoa.value = '';
            btnLimparPessoa.style.display = 'none';

            if (this.value === 'nao') {
                if (selectFiltrarPor) {
                    selectFiltrarPor.value = 'aluno';
                    const optColab = selectFiltrarPor.querySelector('option[value="colaborador"]');
                    if (optColab) optColab.disabled = true;
                }
                lblBuscaPessoa.textContent = 'Aluno';
                inputBuscaPessoa.placeholder = 'Selecione ou digite o nome do aluno...';
            } else {
                if (selectFiltrarPor) {
                    const optColab = selectFiltrarPor.querySelector('option[value="colaborador"]');
                    if (optColab) optColab.disabled = false;
                }
                atualizarLabelBusca();
            }
        });
    });

    // Alternador Filtrar Por (Aluno / Colaborador)
    if (selectFiltrarPor) {
        selectFiltrarPor.addEventListener('change', function() {
            hiddenPessoaId.value = '';
            hiddenAlunoId.value = '';
            hiddenColaboradorId.value = '';
            inputBuscaPessoa.value = '';
            btnLimparPessoa.style.display = 'none';
            atualizarLabelBusca();
        });
    }

    function atualizarLabelBusca() {
        const tipo = selectFiltrarPor ? selectFiltrarPor.value : 'aluno';
        if (tipo === 'colaborador') {
            lblBuscaPessoa.textContent = 'Colaborador';
            inputBuscaPessoa.placeholder = 'Selecione ou digite o nome do colaborador...';
        } else {
            lblBuscaPessoa.textContent = 'Aluno';
            inputBuscaPessoa.placeholder = 'Selecione ou digite o nome do aluno...';
        }
    }

    // Controle do Dropdown de Motivo de Estorno
    if (selectMotivoEstorno) {
        selectMotivoEstorno.addEventListener('change', function() {
            if (this.value === '6-OUTRO') {
                inputMotivoOutro.classList.remove('d-none');
                inputMotivoOutro.focus();
            } else {
                inputMotivoOutro.classList.add('d-none');
            }
        });
    }

    // AUTOCOMPLETE REATIVO DE ALUNOS OU COLABORADORES
    let debounceTimer = null;
    let resultadosAtuais = [];

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    function fecharDropdown() {
        dropdownPessoa.classList.add('d-none');
        dropdownPessoa.innerHTML = '';
        resultadosAtuais = [];
    }

    function selecionarPessoa(item) {
        hiddenPessoaId.value = item.id;
        const isColab = (selectFiltrarPor && selectFiltrarPor.value === 'colaborador' && document.getElementById('vaSim').checked);
        if (isColab) {
            hiddenColaboradorId.value = item.id;
            hiddenAlunoId.value = '';
        } else {
            hiddenAlunoId.value = item.id;
            hiddenColaboradorId.value = '';
        }

        let desc = item.nome;
        if (item.matricula || item.matricula_funcional) {
            desc += ' (' + (item.matricula || item.matricula_funcional) + ')';
        }
        inputBuscaPessoa.value = desc;
        btnLimparPessoa.style.display = '';
        fecharDropdown();
    }

    if (btnLimparPessoa) {
        btnLimparPessoa.addEventListener('click', function(e) {
            e.preventDefault();
            hiddenPessoaId.value = '';
            hiddenAlunoId.value = '';
            hiddenColaboradorId.value = '';
            inputBuscaPessoa.value = '';
            this.style.display = 'none';
            fecharDropdown();
        });
    }

    if (inputBuscaPessoa) {
        inputBuscaPessoa.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const termo = this.value.trim().toLowerCase();

            if (termo.length === 0) {
                hiddenPessoaId.value = '';
                hiddenAlunoId.value = '';
                hiddenColaboradorId.value = '';
                btnLimparPessoa.style.display = 'none';
                fecharDropdown();
                return;
            }

            btnLimparPessoa.style.display = '';

            debounceTimer = setTimeout(function() {
                const isColab = (selectFiltrarPor && selectFiltrarPor.value === 'colaborador' && document.getElementById('vaSim').checked);
                const listaFonte = isColab ? listaColaboradores : listaAlunos;

                // Filtra localmente
                resultadosAtuais = listaFonte.filter(function(p) {
                    const nome = (p.nome || '').toLowerCase();
                    const mat = (p.matricula || p.matricula_funcional || '').toLowerCase();
                    const extra = (p.nome_turma || p.cargo || '').toLowerCase();
                    return nome.includes(termo) || mat.includes(termo) || extra.includes(termo);
                }).slice(0, 15);

                dropdownPessoa.innerHTML = '';

                if (resultadosAtuais.length > 0) {
                    resultadosAtuais.forEach(function(p) {
                        const itemEl = document.createElement('div');
                        itemEl.className = 'd-flex justify-content-between align-items-center py-2 px-3 border-bottom';
                        itemEl.style.cursor = 'pointer';
                        itemEl.style.backgroundColor = '#ffffff';

                        const infoSec = p.nome_turma 
                            ? 'Matrícula: ' + (p.matricula || 'S/M') + ' • ' + p.nome_turma 
                            : (p.cargo ? 'Matrícula: ' + (p.matricula_funcional || 'S/M') + ' • ' + p.cargo : '');

                        itemEl.innerHTML = 
                            '<div class="text-truncate me-2">' +
                                '<div class="font-weight-semibold text-dark" style="font-size: 13px;">' + escapeHtml(p.nome) + '</div>' +
                                '<div class="text-secondary text-microcopy font-tabular">' + escapeHtml(infoSec) + '</div>' +
                            '</div>' +
                            '<span class="badge bg-light text-primary border font-tabular" style="font-size: 10px;">Selecionar</span>';

                        itemEl.addEventListener('mouseenter', function() { itemEl.style.backgroundColor = '#f1f5f9'; });
                        itemEl.addEventListener('mouseleave', function() { itemEl.style.backgroundColor = '#ffffff'; });

                        itemEl.addEventListener('click', function(ev) {
                            ev.preventDefault();
                            ev.stopPropagation();
                            selecionarPessoa(p);
                        });

                        dropdownPessoa.appendChild(itemEl);
                    });
                    dropdownPessoa.classList.remove('d-none');
                } else {
                    dropdownPessoa.innerHTML = '<div class="p-3 text-secondary text-microcopy text-center">Nenhum registro localizado para "<strong>' + escapeHtml(termo) + '</strong>".</div>';
                    dropdownPessoa.classList.remove('d-none');
                }
            }, 120);
        });

        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            if (!inputBuscaPessoa.contains(e.target) && !dropdownPessoa.contains(e.target)) {
                fecharDropdown();
            }
        });
    }

    // Clique no botão Gravar Estorno
    if (btnGravarEstorno) {
        btnGravarEstorno.addEventListener('click', function() {
            let selecionadas = [];
            let soma = 0.0;
            checksVenda.forEach(function(ck) {
                if (ck.checked) {
                    selecionadas.push(ck.value);
                    soma += parseFloat(ck.dataset.valor || 0);
                }
            });

            if (selecionadas.length === 0) {
                alert('Selecione ao menos uma venda na tabela para estornar.');
                return;
            }

            let motivo = selectMotivoEstorno ? selectMotivoEstorno.value.trim() : '1-ESTORNO';
            if (motivo === '6-OUTRO' && inputMotivoOutro) {
                const espec = inputMotivoOutro.value.trim();
                if (espec.length < 3) {
                    alert('Por favor, especifique o motivo do estorno.');
                    inputMotivoOutro.focus();
                    return;
                }
                motivo = 'OUTRO: ' + espec;
            }

            hiddenMotivoEstorno.value = motivo;

            // Abre modal corporativo de confirmação
            document.getElementById('txtConfirmarResumo').innerHTML = 
                'Você está prestes a estornar <strong>' + selecionadas.length + '</strong> venda(s) no valor total de <strong class="font-tabular">R$ ' + soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>.';
            document.getElementById('txtConfirmarMotivo').textContent = motivo;

            if (modalConfirmar) {
                modalConfirmar.show();
            } else {
                window.confirmarAcao({
                    titulo: 'Confirmação de Estorno',
                    mensagem: 'Confirma o estorno de ' + selecionadas.length + ' venda(s)?',
                    tipo: 'danger',
                    textoConfirmar: 'Sim, Estornar'
                }).then(function(confirmado) {
                    if (confirmado) {
                        formGravar.submit();
                    }
                });
            }
        });
    }

    const btnConfirmarEstornoFinal = document.getElementById('btnConfirmarEstornoFinal');
    if (btnConfirmarEstornoFinal) {
        btnConfirmarEstornoFinal.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gravando estorno...';
            formGravar.submit();
        });
    }

    // Função para abrir modal de Informações da Venda
    function abrirDetalhesVenda(vId, vTipo) {
        if (!modalDetalhes || !vId) return;

        const modLoading = document.getElementById('modLoading');
        const modConteudo = document.getElementById('modConteudo');
        const modTitulo = document.getElementById('modTitulo');

        modTitulo.textContent = 'Informações VENDAS — Venda #' + vId + ' (' + (vTipo === 'balcao' ? 'Balcão' : 'Encomenda Online') + ')';
        modLoading.style.display = '';
        modConteudo.style.display = 'none';
        modConteudo.innerHTML = '';
        modalDetalhes.show();

        fetch(baseUrl + '/operacional/estornos/detalhes?id=' + encodeURIComponent(vId) + '&tipo=' + encodeURIComponent(vTipo))
            .then(function(res) {
                if (!res.ok) throw new Error('Falha ao obter detalhes da venda.');
                return res.json();
            })
            .then(function(data) {
                modLoading.style.display = 'none';
                if (!data.sucesso || !data.dados) {
                    modConteudo.innerHTML = '<div class="alert alert-danger py-2">' + (data.mensagem || 'Dados não localizados.') + '</div>';
                    modConteudo.style.display = '';
                    return;
                }

                const venda = data.dados || {};
                const vInfo = data.venda || {};
                const itens = data.itens || [];

                let itensHtml = '';
                if (itens.length === 0) {
                    itensHtml = '<tr><td colspan="4" class="text-center text-muted small py-2">Nenhum item discriminado.</td></tr>';
                } else {
                    itens.forEach(function(item) {
                        const desc = item.nome_produto || item.produto_nome || item.produto_descricao || item.descricao || 'Item';
                        const qtd = parseFloat(item.quantidade || 1);
                        const unit = parseFloat(item.preco_unitario || item.valor_unitario || 0);
                        const sub = parseFloat(item.subtotal || item.valor_total || (qtd * unit));
                        
                        itensHtml += '<tr>' +
                            '<td class="small">' + escapeHtml(desc) + '</td>' +
                            '<td class="small font-tabular text-center">' + qtd + '</td>' +
                            '<td class="small font-tabular text-end">R$ ' + unit.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</td>' +
                            '<td class="small font-tabular font-weight-bold text-end">R$ ' + sub.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</td>' +
                        '</tr>';
                    });
                }

                let formaPgto = vInfo.forma_pagamento || venda.forma_pagamento || '--';
                if (formaPgto === 'saldo_carteira') formaPgto = 'Saldo em Carteira (Familiar)';
                else if (formaPgto === 'folha_pagamento') formaPgto = 'Folha de Pagamento (Colaborador)';
                else if (formaPgto === 'dinheiro') formaPgto = 'Dinheiro em Espécie';
                else if (formaPgto === 'pix_balcao') formaPgto = 'Pix no Balcão';

                let dataFmt = vInfo.data_hora || (venda.data_consumo ? (venda.data_consumo + (venda.turno_consumo ? ' (' + venda.turno_consumo + ')' : '')) : (venda.created_at || ''));
                let valorFmt = parseFloat(venda.valor_total || vInfo.valor_total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                let pessoaFmt = vInfo.pessoa_nome || venda.aluno_nome || venda.colaborador_nome || 'Consumidor Geral';
                let infoVinculo = '';
                if (vInfo.aluno_turma || venda.aluno_turma || venda.turma_nome || venda.nome_turma) {
                    infoVinculo = 'Turma: ' + (vInfo.aluno_turma || venda.aluno_turma || venda.turma_nome || venda.nome_turma);
                }
                if (vInfo.responsavel || venda.responsavel_nome) {
                    infoVinculo += (infoVinculo ? ' • ' : '') + 'Responsável: ' + (vInfo.responsavel || venda.responsavel_nome);
                } else if (vInfo.operador_nome || venda.operador_nome) {
                    infoVinculo += (infoVinculo ? ' • ' : '') + 'Operador: ' + (vInfo.operador_nome || venda.operador_nome);
                }

                let html = 
                    '<div class="row g-2 mb-3 bg-light p-2 rounded border">' +
                        '<div class="col-6 col-sm-3"><span class="text-secondary small">Data/Hora:</span><div class="small font-tabular font-weight-medium">' + escapeHtml(dataFmt) + '</div></div>' +
                        '<div class="col-6 col-sm-3"><span class="text-secondary small">Forma Pgto:</span><div class="small font-weight-medium">' + escapeHtml(formaPgto) + '</div></div>' +
                        '<div class="col-6 col-sm-3"><span class="text-secondary small">Status:</span><div><span class="badge bg-primary-subtle text-primary border" style="font-size: 11px;">' + escapeHtml(venda.status || 'concluida') + '</span></div></div>' +
                        '<div class="col-6 col-sm-3"><span class="text-secondary small">Valor Total:</span><div class="fs-6 font-tabular font-weight-bold text-primary">R$ ' + valorFmt + '</div></div>' +
                        (pessoaFmt ? ('<div class="col-12 border-top pt-2 mt-2"><span class="text-secondary small">Pessoa / Vínculo:</span> <strong class="small text-dark">' + escapeHtml(pessoaFmt) + '</strong>' + (infoVinculo ? ' <span class="text-secondary text-microcopy font-tabular">(' + escapeHtml(infoVinculo) + ')</span>' : '') + '</div>') : '') +
                    '</div>' +
                    '<h6 class="small font-weight-bold text-secondary mb-2">Itens da Venda:</h6>' +
                    '<div class="table-responsive border rounded">' +
                        '<table class="table table-sm table-striped mb-0">' +
                            '<thead class="table-light">' +
                                '<tr>' +
                                    '<th class="small font-weight-bold">Produto</th>' +
                                    '<th class="small font-weight-bold text-center" style="width: 80px;">Qtd</th>' +
                                    '<th class="small font-weight-bold text-end" style="width: 100px;">Unitário</th>' +
                                    '<th class="small font-weight-bold text-end" style="width: 110px;">Subtotal</th>' +
                                '</tr>' +
                            '</thead>' +
                            '<tbody>' + itensHtml + '</tbody>' +
                        '</table>' +
                    '</div>';

                if (venda.motivo_estorno) {
                    html += '<div class="alert alert-warning mt-3 py-2 small mb-0">' +
                        '<strong>Estorno já efetuado:</strong> ' + escapeHtml(venda.motivo_estorno) +
                    '</div>';
                }

                modConteudo.innerHTML = html;
                modConteudo.style.display = '';
            })
            .catch(function(err) {
                modLoading.style.display = 'none';
                modConteudo.innerHTML = '<div class="alert alert-danger py-2">' + err.message + '</div>';
                modConteudo.style.display = '';
            });
    }
});
</script>