<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Estorno de Vendas e Invalidação de Tickets
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e docs/alterações sistema guideline/extorno de vendas.pdf
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfToken = \App\Core\Session::generateCsrfToken();
$filtros = $filtros ?? [];
$vendas = $vendas ?? [];
$alunos = $alunos ?? [];
$colaboradores = $colaboradores ?? [];

$vendaAvulsa = $filtros['venda_avulsa'] ?? 'sim';
$filtrarPor = $filtros['filtrar_por'] ?? 'aluno';
$dataInicio = $filtros['data_inicio'] ?? date('Y-m-d');
$dataFim = $filtros['data_fim'] ?? date('Y-m-d');
$pessoaId = $filtros['pessoa_id'] ?? '';
$motivoFiltro = $filtros['motivo'] ?? '';
?>

<!-- Cabeçalho e Barra de Ações Primárias -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Estorno de Venda da Cantina</h1>
            <span class="badge bg-white text-secondary border font-tabular" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                Módulo de Invalidação e Estorno
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Localização e reversão atômica de vendas avulsas de balcão ou encomendas online com reconstituição de estoque e saldo.
        </p>
    </div>

    <!-- Ações no Topo: Cancelar e Gravar (Fiel ao Escolar Manager) -->
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo $baseUrl; ?>/operacional/estornos" class="btn btn-enterprise btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" title="Limpar seleções e recarregar">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
            </svg>
            Cancelar
        </a>
        <button type="button" id="btnGravarEstornoTopo" class="btn btn-enterprise btn-success btn-sm d-inline-flex align-items-center gap-1" style="background-color: #16a34a; border-color: #16a34a;">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4.414A1 1 0 0 0 14.586 3.707l-2.293-2.293A1 1 0 0 0 11.586 1H2zm10 2v2H4V3h8zm-8 4h8v6H4V7z"/>
            </svg>
            <strong>Gravar</strong>
        </button>
    </div>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-3">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" class="text-success flex-shrink-0">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
        </svg>
        <div class="small text-dark font-weight-medium"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 py-2 mb-3">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" class="text-danger flex-shrink-0">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16zM7.002 11a1 1 0 1 0 2 0 1 1 0 0 0-2 0zm.05-7.95a.5.5 0 0 0-.5.5v5a.5.5 0 0 0 1 0v-5a.5.5 0 0 0-.5-.5z"/>
        </svg>
        <div class="small text-dark font-weight-medium"><?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
<?php endif; ?>

<!-- Formulário Mestre que engloba Filtros e Execução de Gravação -->
<form id="formEstorno" action="<?php echo $baseUrl; ?>/operacional/estornos/gravar" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <input type="hidden" name="venda_avulsa" value="<?php echo htmlspecialchars($vendaAvulsa, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Painel de Filtros e Critérios de Busca (Baseado no Guideline Oficial) -->
    <div class="card border-0 shadow-sm mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                
                <!-- Venda Avulsa (Sim / Não) -->
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label text-secondary small font-weight-bold mb-1">Venda avulsa:</label>
                    <div class="d-flex gap-3 pt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="filtro_venda_avulsa_radio" id="vaSim" value="sim" <?php echo ($vendaAvulsa === 'sim') ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="vaSim">Sim</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="filtro_venda_avulsa_radio" id="vaNao" value="nao" <?php echo ($vendaAvulsa === 'nao') ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="vaNao">Não</label>
                        </div>
                    </div>
                </div>

                <!-- Filtrar Por (Aluno / Colaborador) -->
                <div class="col-12 col-sm-6 col-md-2" id="boxFiltrarPor" style="<?php echo ($vendaAvulsa === 'nao') ? 'display: none;' : ''; ?>">
                    <label class="form-label text-secondary small font-weight-bold mb-1">Filtrar por:</label>
                    <div class="d-flex gap-3 pt-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="filtro_filtrar_por_radio" id="fpAluno" value="aluno" <?php echo ($filtrarPor === 'aluno') ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="fpAluno">Aluno</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="filtro_filtrar_por_radio" id="fpColaborador" value="colaborador" <?php echo ($filtrarPor === 'colaborador') ? 'checked' : ''; ?>>
                            <label class="form-check-label small" for="fpColaborador">Colaborador</label>
                        </div>
                    </div>
                </div>

                <!-- Seletor de Pessoa (Aluno ou Colaborador conforme filtro) -->
                <div class="col-12 col-sm-12 col-md-4">
                    <label class="form-label text-secondary small font-weight-bold mb-1" id="lblPessoa">
                        <?php echo ($filtrarPor === 'colaborador' && $vendaAvulsa === 'sim') ? 'Colaborador:' : 'Aluno:'; ?>
                    </label>
                    
                    <!-- Select para Alunos -->
                    <div id="selectAlunoContainer" style="<?php echo ($filtrarPor === 'colaborador' && $vendaAvulsa === 'sim') ? 'display: none;' : ''; ?>">
                        <select class="form-select form-select-sm" id="selectAluno" name="pessoa_id_aluno">
                            <option value="">-- Todos os Alunos --</option>
                            <?php foreach ($alunos as $al): ?>
                                <option value="<?php echo $al['id']; ?>" <?php echo ((string)$pessoaId === (string)$al['id'] && $filtrarPor === 'aluno') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($al['nome'] . ' (' . ($al['matricula'] ?: 'S/M') . ' - ' . ($al['nome_turma'] ?? 'Geral') . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Select para Colaboradores -->
                    <div id="selectColaboradorContainer" style="<?php echo ($filtrarPor === 'colaborador' && $vendaAvulsa === 'sim') ? '' : 'display: none;'; ?>">
                        <select class="form-select form-select-sm" id="selectColaborador" name="pessoa_id_colaborador">
                            <option value="">-- Todos os Colaboradores --</option>
                            <?php foreach ($colaboradores as $col): ?>
                                <option value="<?php echo $col['id']; ?>" <?php echo ((string)$pessoaId === (string)$col['id'] && $filtrarPor === 'colaborador') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($col['nome'] . ' (' . ($col['matricula_funcional'] ?: 'S/M') . ' - ' . ($col['cargo'] ?? 'Geral') . ')', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Período: De -->
                <div class="col-6 col-sm-6 col-md-2">
                    <label class="form-label text-secondary small font-weight-bold mb-1">De:</label>
                    <input type="date" class="form-control form-control-sm font-tabular" id="filtroDataInicio" name="data_inicio" value="<?php echo htmlspecialchars($dataInicio, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Período: Até -->
                <div class="col-6 col-sm-6 col-md-2">
                    <div class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label class="form-label text-secondary small font-weight-bold mb-1">Até:</label>
                            <input type="date" class="form-control form-control-sm font-tabular" id="filtroDataFim" name="data_fim" value="<?php echo htmlspecialchars($dataFim, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <button type="button" id="btnFiltrar" class="btn btn-enterprise btn-outline-primary btn-sm flex-shrink-0" title="Pesquisar vendas com estes filtros">
                            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                        </button>
                    </div>
                </div>

            </div>

            <!-- Linha Obrigatória do Motivo do Estorno -->
            <div class="row g-3 mt-1 pt-2 border-top">
                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center">
                        <label for="campoMotivo" class="form-label text-secondary small font-weight-bold mb-0 flex-shrink-0" style="min-width: 140px;">
                            Motivo do estorno: <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm flex-grow-1">
                            <input type="text" class="form-control form-control-sm" id="campoMotivo" name="motivo" 
                                   placeholder="Informe a justificativa obrigatória do estorno (mínimo 5 caracteres)..." 
                                   value="<?php echo htmlspecialchars($motivoFiltro, ENT_QUOTES, 'UTF-8'); ?>" required>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLimparMotivo" title="Limpar justificativa">
                                ✕
                            </button>
                        </div>
                    </div>
                    <div class="text-muted" style="font-size: 11px; margin-left: 148px; margin-top: 3px;">
                        Esta justificativa será registrada de forma imutável nos logs de auditoria e no histórico contábil.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Tabela / Data-Grid Operacional (Fiel ao Escolar Manager) -->
    <div class="card border-0 shadow-sm mb-3" style="background-color: #ffffff; border: 1px solid #e2e8f0 !important;">
        <div class="table-responsive">
            <table class="table table-hover table-striped table-sm mb-0 align-middle">
                <thead style="background-color: #2563eb; color: #ffffff;">
                    <tr style="height: 38px;">
                        <th style="width: 44px; text-align: center;">
                            <input class="form-check-input" type="checkbox" id="checkTodos" title="Selecionar ou desmarcar todas">
                        </th>
                        <th style="width: 110px; font-weight: 600; font-size: 12px;">Data</th>
                        <th style="width: 90px; font-weight: 600; font-size: 12px;">Hora</th>
                        <th style="width: 130px; font-weight: 600; font-size: 12px; text-align: right;">Valor total</th>
                        <th style="font-weight: 600; font-size: 12px;">Pessoa</th>
                        <th style="width: 120px; font-weight: 600; font-size: 12px; text-align: center;">Tipo / Status</th>
                        <th style="width: 80px; font-weight: 600; font-size: 12px; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody id="corpoTabelaVendas">
                    <?php if (empty($vendas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">
                                <div class="py-3">
                                    <svg width="32" height="32" viewBox="0 0 16 16" fill="currentColor" class="text-muted mb-2 opacity-50">
                                        <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                        <path d="M4 4h8v1H4V4zm0 2h8v1H4V6zm0 2h8v1H4V8zm0 2h5v1H4v-1z"/>
                                    </svg>
                                    <div class="small font-weight-medium">Não há vendas concluídas encontradas para os filtros informados.</div>
                                    <div class="text-muted text-microcopy mt-1">Ajuste o período ou o cliente pesquisado.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vendas as $v): 
                            $vId = (int)$v['id'];
                            $valorVenda = (float)($v['valor_total'] ?? 0.0);
                            $dataVenda = !empty($v['data_venda']) ? date('d/m/Y', strtotime($v['data_venda'])) : '--/--/----';
                            $horaVenda = !empty($v['hora_venda']) ? substr($v['hora_venda'], 0, 5) : '--:--';
                            $tipoOrigem = $v['tipo_origem'] ?? 'balcao';
                            $nomePessoa = $v['pessoa_nome'] ?? 'Consumidor Geral';
                            $detalhePessoa = $v['pessoa_detalhe'] ?? '';
                        ?>
                            <tr id="linhaVenda_<?php echo $vId; ?>">
                                <td style="text-align: center;">
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
                                        <div class="text-muted text-microcopy"><?php echo htmlspecialchars($detalhePessoa, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($tipoOrigem === 'balcao'): ?>
                                        <span class="badge bg-light text-primary border" style="font-size: 11px; font-weight: 500;">Balcão</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-purple border" style="font-size: 11px; font-weight: 500; color: #7c3aed; border-color: #ddd6fe !important;">Encomenda</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <!-- Botão Neutro de Detalhes (i) conforme diretrizes Anti-Slop -->
                                    <button type="button" class="btn btn-enterprise btn-secondary btn-sm p-1 btn-ver-detalhes" 
                                            data-id="<?php echo $vId; ?>" 
                                            data-tipo="<?php echo $tipoOrigem; ?>" 
                                            title="Ver itens e detalhes desta venda">
                                        <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Rodapé do Data-Grid: Contadores e Totais da Seleção -->
        <div class="card-footer bg-light border-top p-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-secondary">
                Total de registros: <span class="font-tabular font-weight-bold text-dark" id="txtTotalRegistros"><?php echo count($vendas); ?></span>
            </div>
            <div class="d-flex align-items-center gap-3">
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

<!-- Modal de Detalhes da Venda (Carregado via Ajax) -->
<div class="modal fade" id="modalDetalhesVenda" tabindex="-1" aria-labelledby="modalDetalhesVendaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2" style="background-color: #2563eb; color: #ffffff;">
                <h5 class="modal-title fs-6 font-weight-bold" id="modalDetalhesVendaLabel">
                    <span id="modTitulo">Detalhes da Venda</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-3" id="modCorpoDetalhes">
                <div class="text-center py-4" id="modLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando detalhes...</span>
                    </div>
                    <div class="small text-muted mt-2">Buscando itens da venda...</div>
                </div>
                <div id="modConteudo" style="display: none;">
                    <!-- Preenchido dinamicamente via JS -->
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Estorno -->
<div class="modal fade" id="modalConfirmarEstorno" tabindex="-1" aria-labelledby="modalConfirmarEstornoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 bg-danger text-white">
                <h5 class="modal-title fs-6 font-weight-bold" id="modalConfirmarEstornoLabel">
                    Confirmação de Estorno
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-shrink-0 text-danger mt-1">
                        <svg width="28" height="28" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-weight-bold text-dark mb-1">Deseja realmente estornar as vendas selecionadas?</div>
                        <p class="small text-secondary mb-2" id="txtConfirmarResumo">
                            Você está prestes a estornar X venda(s) no total de R$ 0,00.
                        </p>
                        <div class="alert alert-warning py-1 px-2 mb-2 text-microcopy border-0" style="background-color: #fef9c3; color: #854d0e;">
                            <strong>Atenção:</strong> O estoque físico será reintegrado, saldos/encomendas serão recalculados e tickets térmicos associados serão invalidados permanentemente.
                        </div>
                        <div class="small">
                            <strong>Motivo informado:</strong>
                            <div class="p-2 mt-1 rounded bg-light border text-dark font-italic" id="txtConfirmarMotivo"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal">Voltar</button>
                <button type="button" id="btnConfirmarEstornoFinal" class="btn btn-enterprise btn-danger btn-sm">
                    Sim, Executar Estorno
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = <?php echo json_encode($baseUrl); ?>;
    const formEstorno = document.getElementById('formEstorno');
    const checkTodos = document.getElementById('checkTodos');
    const checksVenda = document.querySelectorAll('.check-venda');
    const txtTotalSelecionados = document.getElementById('txtTotalSelecionados');
    const txtSomaSelecionados = document.getElementById('txtSomaSelecionados');
    const btnGravarEstornoTopo = document.getElementById('btnGravarEstornoTopo');
    const campoMotivo = document.getElementById('campoMotivo');
    const btnLimparMotivo = document.getElementById('btnLimparMotivo');
    const btnFiltrar = document.getElementById('btnFiltrar');

    // Filtros de Alternância
    const radiosVendaAvulsa = document.querySelectorAll('input[name="filtro_venda_avulsa_radio"]');
    const radiosFiltrarPor = document.querySelectorAll('input[name="filtro_filtrar_por_radio"]');
    const boxFiltrarPor = document.getElementById('boxFiltrarPor');
    const lblPessoa = document.getElementById('lblPessoa');
    const selectAlunoContainer = document.getElementById('selectAlunoContainer');
    const selectColaboradorContainer = document.getElementById('selectColaboradorContainer');
    const selectAluno = document.getElementById('selectAluno');
    const selectColaborador = document.getElementById('selectColaborador');
    const filtroDataInicio = document.getElementById('filtroDataInicio');
    const filtroDataFim = document.getElementById('filtroDataFim');

    // Modais
    const modalDetalhesEl = document.getElementById('modalDetalhesVenda');
    const modalDetalhes = modalDetalhesEl ? new bootstrap.Modal(modalDetalhesEl) : null;
    const modalConfirmarEl = document.getElementById('modalConfirmarEstorno');
    const modalConfirmar = modalConfirmarEl ? new bootstrap.Modal(modalConfirmarEl) : null;

    // Função de recalcular totais selecionados
    function recalcularTotais() {
        let qtd = 0;
        let soma = 0.0;
        checksVenda.forEach(function(ck) {
            if (ck.checked) {
                qtd++;
                soma += parseFloat(ck.dataset.valor || 0);
            }
        });

        txtTotalSelecionados.textContent = qtd;
        txtSomaSelecionados.textContent = 'R$ ' + soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

    // Eventos nos checkboxes
    if (checkTodos) {
        checkTodos.addEventListener('change', function() {
            const marcar = checkTodos.checked;
            checksVenda.forEach(function(ck) {
                ck.checked = marcar;
            });
            recalcularTotais();
        });
    }

    checksVenda.forEach(function(ck) {
        ck.addEventListener('change', recalcularTotais);
    });

    // Alternador Venda Avulsa (Sim / Não)
    radiosVendaAvulsa.forEach(function(r) {
        r.addEventListener('change', function() {
            if (this.value === 'nao') {
                boxFiltrarPor.style.display = 'none';
                lblPessoa.textContent = 'Aluno:';
                selectAlunoContainer.style.display = '';
                selectColaboradorContainer.style.display = 'none';
            } else {
                boxFiltrarPor.style.display = '';
                atualizarExibicaoFiltrarPor();
            }
            executarFiltro();
        });
    });

    // Alternador Filtrar Por (Aluno / Colaborador)
    radiosFiltrarPor.forEach(function(r) {
        r.addEventListener('change', function() {
            atualizarExibicaoFiltrarPor();
            executarFiltro();
        });
    });

    function atualizarExibicaoFiltrarPor() {
        const selecionado = document.querySelector('input[name="filtro_filtrar_por_radio"]:checked');
        const tipo = selecionado ? selecionado.value : 'aluno';

        if (tipo === 'colaborador') {
            lblPessoa.textContent = 'Colaborador:';
            selectAlunoContainer.style.display = 'none';
            selectColaboradorContainer.style.display = '';
        } else {
            lblPessoa.textContent = 'Aluno:';
            selectAlunoContainer.style.display = '';
            selectColaboradorContainer.style.display = 'none';
        }
    }

    // Função de navegação / submissão do filtro
    function executarFiltro() {
        const va = document.querySelector('input[name="filtro_venda_avulsa_radio"]:checked').value;
        const fp = document.querySelector('input[name="filtro_filtrar_por_radio"]:checked').value;
        const dtIni = filtroDataInicio.value;
        const dtFim = filtroDataFim.value;
        const motivo = campoMotivo.value;
        
        let pId = '';
        if (va === 'sim' && fp === 'colaborador') {
            pId = selectColaborador.value;
        } else {
            pId = selectAluno.value;
        }

        const params = new URLSearchParams();
        params.set('venda_avulsa', va);
        params.set('filtrar_por', fp);
        if (pId) params.set('pessoa_id', pId);
        if (dtIni) params.set('data_inicio', dtIni);
        if (dtFim) params.set('data_fim', dtFim);
        if (motivo) params.set('motivo', motivo);

        window.location.href = baseUrl + '/operacional/estornos?' + params.toString();
    }

    if (btnFiltrar) {
        btnFiltrar.addEventListener('click', executarFiltro);
    }
    if (selectAluno) {
        selectAluno.addEventListener('change', executarFiltro);
    }
    if (selectColaborador) {
        selectColaborador.addEventListener('change', executarFiltro);
    }
    if (filtroDataInicio) {
        filtroDataInicio.addEventListener('change', executarFiltro);
    }
    if (filtroDataFim) {
        filtroDataFim.addEventListener('change', executarFiltro);
    }

    if (btnLimparMotivo) {
        btnLimparMotivo.addEventListener('click', function() {
            campoMotivo.value = '';
            campoMotivo.focus();
        });
    }

    // Clique no botão Gravar
    if (btnGravarEstornoTopo) {
        btnGravarEstornoTopo.addEventListener('click', function() {
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

            const motivo = campoMotivo.value.trim();
            if (motivo.length < 5) {
                alert('Informe um motivo válido para o estorno com no mínimo 5 caracteres.');
                campoMotivo.focus();
                return;
            }

            // Abre modal de confirmação
            document.getElementById('txtConfirmarResumo').innerHTML = 
                'Você está prestes a estornar <strong>' + selecionadas.length + '</strong> venda(s) no valor total de <strong>R$ ' + soma.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</strong>.';
            document.getElementById('txtConfirmarMotivo').textContent = motivo;

            if (modalConfirmar) {
                modalConfirmar.show();
            } else {
                if (confirm('Confirma o estorno de ' + selecionadas.length + ' venda(s)?')) {
                    formEstorno.submit();
                }
            }
        });
    }

    const btnConfirmarEstornoFinal = document.getElementById('btnConfirmarEstornoFinal');
    if (btnConfirmarEstornoFinal) {
        btnConfirmarEstornoFinal.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Estornando...';
            formEstorno.submit();
        });
    }

    // Modal de Detalhes Ajax
    const btnsVerDetalhes = document.querySelectorAll('.btn-ver-detalhes');
    const modLoading = document.getElementById('modLoading');
    const modConteudo = document.getElementById('modConteudo');
    const modTitulo = document.getElementById('modTitulo');

    btnsVerDetalhes.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const vId = this.dataset.id;
            const vTipo = this.dataset.tipo;

            if (!modalDetalhes) return;

            modTitulo.textContent = 'Detalhes da Venda #' + vId + ' (' + (vTipo === 'balcao' ? 'Balcão' : 'Encomenda Online') + ')';
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

                    const venda = data.dados;
                    const itens = data.itens || [];

                    let itensHtml = '';
                    if (itens.length === 0) {
                        itensHtml = '<tr><td colspan="4" class="text-center text-muted small py-2">Nenhum item discriminado.</td></tr>';
                    } else {
                        itens.forEach(function(item) {
                            const desc = item.nome_produto || item.produto_nome || item.descricao || 'Item';
                            const qtd = parseFloat(item.quantidade || 1);
                            const unit = parseFloat(item.preco_unitario || item.valor_unitario || 0);
                            const sub = parseFloat(item.subtotal || item.valor_total || (qtd * unit));
                            
                            itensHtml += '<tr>' +
                                '<td class="small">' + desc + '</td>' +
                                '<td class="small font-tabular text-center">' + qtd + '</td>' +
                                '<td class="small font-tabular text-end">R$ ' + unit.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</td>' +
                                '<td class="small font-tabular font-weight-bold text-end">R$ ' + sub.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + '</td>' +
                            '</tr>';
                        });
                    }

                    let formaPgto = venda.forma_pagamento || '--';
                    if (formaPgto === 'saldo_carteira') formaPgto = 'Saldo em Carteira (Familiar)';
                    else if (formaPgto === 'folha_pagamento') formaPgto = 'Folha de Pagamento (Colaborador)';
                    else if (formaPgto === 'dinheiro') formaPgto = 'Dinheiro em Espécie';
                    else if (formaPgto === 'pix_balcao') formaPgto = 'Pix no Balcão';

                    let dataFmt = venda.created_at || venda.data_hora || '';
                    let valorFmt = parseFloat(venda.valor_total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 });

                    let html = 
                        '<div class="row g-2 mb-3 bg-light p-2 rounded border">' +
                            '<div class="col-6 col-sm-3"><span class="text-muted small">Data/Hora:</span><div class="small font-tabular font-weight-medium">' + dataFmt + '</div></div>' +
                            '<div class="col-6 col-sm-3"><span class="text-muted small">Forma Pgto:</span><div class="small font-weight-medium">' + formaPgto + '</div></div>' +
                            '<div class="col-6 col-sm-3"><span class="text-muted small">Status:</span><div><span class="badge bg-success" style="font-size: 11px;">' + (venda.status || 'concluida') + '</span></div></div>' +
                            '<div class="col-6 col-sm-3"><span class="text-muted small">Valor Total:</span><div class="fs-6 font-tabular font-weight-bold text-primary">R$ ' + valorFmt + '</div></div>' +
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
                            '<strong>Estorno já efetuado:</strong> ' + venda.motivo_estorno +
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
        });
    });
});
</script>
