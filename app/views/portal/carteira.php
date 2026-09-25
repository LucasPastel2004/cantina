<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Carteira Digital Familiar e Extrato Analítico
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfToken = \App\Core\Session::generateCsrfToken();
?>

<div class="carteira-container">
    <!-- Cabeçalho Executivo -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-4 border-bottom border-subtle gap-3">
        <div>
            <h1 class="page-title mb-1">Carteira Digital Familiar</h1>
            <p class="text-secondary mb-0" style="font-size: 13px;">
                Gestão centralizada de saldo compartilhado, recargas instantâneas via Pix e extrato contábil analítico.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="#secao-recarga" class="btn btn-enterprise btn-primary btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-qr-code me-1" viewBox="0 0 16 16">
                    <path d="M2 2h2v2H2zM6 2h2v2H6zM10 2h2v2h-2zM2 6h2v2H2zM10 6h2v2h-2zM2 10h2v2H2zM6 10h2v2H6zM10 10h2v2h-2z"/>
                    <path d="M0 0v6h6V0zm5 5H1V1h4zm3-5v6h6V0zm5 5H9V1h4zM0 8v6h6V8zm5 5H1V9h4zm3-5v6h6V8zm5 5H9V9h4z"/>
                </svg>
                Recarregar Saldo (Pix)
            </a>
            <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary btn-sm">
                Agendar Lanches
            </a>
        </div>
    </div>

    <!-- Banner de Alerta MED (se aplicável) -->
    <?php if (!empty($resumo['bloqueado_med'])): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-shield-slash-fill me-2 flex-shrink-0" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M1.79 1.1a.5.5 0 0 0-.68.7L2.4 3.1c-.6.9-1.05 1.95-1.32 3.1a.5.5 0 0 0 .1.43C2.33 8.35 4.8 11.2 8 12.78c1.37-.68 2.56-1.63 3.48-2.77l2.73 2.74a.5.5 0 0 0 .7-.7l-13-13zm12.35 12.35L3.43 2.74A17.4 17.4 0 0 1 8 1.22c3.2 1.58 5.67 4.43 6.82 6.15.15.22.18.5.1.75a13.3 13.3 0 0 1-2.78 4.33z"/>
            </svg>
            <div>
                <strong>Atenção:</strong> Sua conta familiar está temporariamente suspensa devido a uma contestação bancária (Mecanismo MED). Novos agendamentos estão bloqueados preventivamente. Por favor, entre em contato com a secretaria da cantina para regularização.
            </div>
        </div>
    <?php endif; ?>

    <!-- Banner de Saldo Baixo (se aplicável e não MED) -->
    <?php if (empty($resumo['bloqueado_med']) && !empty($resumo['alerta_saldo_baixo'])): ?>
        <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" viewBox="0 0 16 16">
                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            <div>
                <strong>Alerta de Saldo Baixo:</strong> Seu saldo disponível atual de <strong class="font-tabular">R$ <?php echo number_format($resumo['saldo_disponivel'], 2, ',', '.'); ?></strong> está abaixo do limite de segurança recomendado de R$ <?php echo number_format($resumo['alerta_saldo_baixo_valor'], 2, ',', '.'); ?>. Recarregue via Pix para garantir os lanches escolares dos seus dependentes sem interrupções!
            </div>
        </div>
    <?php endif; ?>

    <!-- Bento de Saldos Executivos (3 Métricas Claras) -->
    <div class="row g-3 mb-4">
        <!-- Saldo Total em Conta -->
        <div class="col-12 col-md-4">
            <div class="card-enterprise h-100 p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-secondary" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                        Saldo Total em Conta
                    </span>
                    <span class="status-indicator status-active" style="font-size: 11px;">
                        <span class="bullet">●</span> Ativo
                    </span>
                </div>
                <div class="font-tabular my-1" id="display-saldo-total" style="font-size: 24px; font-weight: 700; color: var(--text-primary, #0f172a);">
                    R$ <?php echo number_format($resumo['saldo_atual'], 2, ',', '.'); ?>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    Montante total depositado na carteira familiar
                </div>
            </div>
        </div>

        <!-- Saldo Reservado (Encomendas Futuras) -->
        <div class="col-12 col-md-4">
            <div class="card-enterprise h-100 p-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-secondary" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                        Saldo Reservado
                    </span>
                    <span class="text-muted font-tabular" style="font-size: 11px;">
                        Agendamentos
                    </span>
                </div>
                <div class="font-tabular my-1 text-secondary" id="display-saldo-reservado" style="font-size: 24px; font-weight: 700;">
                    R$ <?php echo number_format($resumo['saldo_reservado'], 2, ',', '.'); ?>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    Comprometido com lanches encomendados da semana
                </div>
            </div>
        </div>

        <!-- Saldo Disponível para Novos Agendamentos -->
        <div class="col-12 col-md-4">
            <div class="card-enterprise h-100 p-3" style="border-left: 3px solid var(--accent-cobalt, #1d4ed8);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-secondary" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                        Saldo Livre Disponível
                    </span>
                    <span class="font-tabular" style="font-size: 11px; color: var(--accent-cobalt, #1d4ed8); font-weight: 600;">
                        Livre
                    </span>
                </div>
                <div class="font-tabular my-1" id="display-saldo-disponivel" style="font-size: 24px; font-weight: 700; color: #16a34a;">
                    R$ <?php echo number_format($resumo['saldo_disponivel'], 2, ',', '.'); ?>
                </div>
                <div class="text-muted" style="font-size: 11px;">
                    Disponível imediatamente para novos agendamentos
                </div>
            </div>
        </div>
    </div>

    <!-- Seção de Recarga Pix Dinâmica -->
    <div class="card-enterprise p-3 p-md-4 mb-4" id="secao-recarga">
        <div class="border-bottom pb-3 mb-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h2 class="card-title mb-1" style="font-size: 15px; font-weight: 600;">Recarga Imediata via Pix Dinâmico</h2>
                <p class="text-secondary mb-0" style="font-size: 12px;">
                    Crédito liberado no mesmo segundo da confirmação bancária, sem tarifas adicionais.
                </p>
            </div>
            <div class="text-muted" style="font-size: 11px;">
                Mínimo: <strong class="font-tabular">R$ <?php echo number_format($resumo['recarga_minima_pix'], 2, ',', '.'); ?></strong>
            </div>
        </div>

        <div class="row g-4 align-items-start">
            <!-- Formulário de Recarga -->
            <div class="col-12 col-lg-6" id="bloco-formulario-pix">
                <form id="form-gerar-pix" onsubmit="gerarPix(event)">
                    <input type="hidden" name="csrf_token" id="pix-csrf-token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">

                    <!-- Atalhos de Valor Rápido -->
                    <label class="form-label mb-2" style="font-size: 12px; font-weight: 600; color: var(--text-secondary, #475569); text-transform: uppercase;">
                        Selecione um valor sugerido:
                    </label>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm btn-quick-value" onclick="selecionarValor(20)">
                            + R$ 20,00
                        </button>
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm btn-quick-value" onclick="selecionarValor(50)">
                            + R$ 50,00
                        </button>
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm btn-quick-value" onclick="selecionarValor(100)">
                            + R$ 100,00
                        </button>
                        <button type="button" class="btn btn-enterprise btn-secondary btn-sm btn-quick-value" onclick="selecionarValor(200)">
                            + R$ 200,00
                        </button>
                    </div>

                    <!-- Input de Valor Customizado -->
                    <div class="mb-3">
                        <label for="input-valor-pix" class="form-label" style="font-size: 12px; font-weight: 600; color: var(--text-secondary, #475569); text-transform: uppercase;">
                            Ou digite outro valor:
                        </label>
                        <div class="input-group" style="max-width: 280px;">
                            <span class="input-group-text bg-light text-secondary font-tabular" style="font-size: 13px; font-weight: 600;">R$</span>
                            <input type="text" 
                                   id="input-valor-pix" 
                                   name="valor" 
                                   class="form-control font-tabular input-money" 
                                   placeholder="0,00" 
                                   value="50,00" 
                                   required 
                                   style="font-size: 15px; font-weight: 600;">
                        </div>
                        <div class="form-text text-muted" style="font-size: 11px;">
                            Insira um valor igual ou superior a R$ <?php echo number_format($resumo['recarga_minima_pix'], 2, ',', '.'); ?>.
                        </div>
                    </div>

                    <!-- Botão de Ação -->
                    <div class="d-flex align-items-center gap-2 pt-1">
                        <button type="submit" id="btn-submit-pix" class="btn btn-enterprise btn-primary">
                            <span id="btn-pix-text">Gerar Código Pix</span>
                            <span id="btn-pix-spinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>

                    <div id="pix-form-error" class="alert alert-danger mt-3 d-none" role="alert" style="font-size: 12px; padding: 8px 12px;"></div>
                </form>
            </div>

            <!-- Painel de Exibição do QR Code e Copia e Cola -->
            <div class="col-12 col-lg-6">
                <div id="pix-resultado-vazio" class="p-4 text-center border border-dashed rounded bg-light">
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" class="bi bi-qr-code text-muted mb-2" viewBox="0 0 16 16">
                        <path d="M2 2h2v2H2zM6 2h2v2H6zM10 2h2v2h-2zM2 6h2v2H2zM10 6h2v2h-2zM2 10h2v2H2zM6 10h2v2H6zM10 10h2v2h-2z"/>
                        <path d="M0 0v6h6V0zm5 5H1V1h4zm3-5v6h6V0zm5 5H9V1h4zM0 8v6h6V8zm5 5H1V9h4zm3-5v6h6V8zm5 5H9V9h4z"/>
                    </svg>
                    <p class="text-secondary mb-1" style="font-size: 13px; font-weight: 600;">Nenhuma cobrança ativa no momento</p>
                    <p class="text-muted mb-0" style="font-size: 11px;">
                        Escolha o valor ao lado e clique em <strong>"Gerar Código Pix"</strong> para visualizar o QR Code e a chave Copia e Cola.
                    </p>
                </div>

                <!-- Painel Ativo do Pix (inicia oculto) -->
                <div id="pix-resultado-ativo" class="d-none border rounded p-3 bg-white shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle font-tabular" style="font-size: 11px;">
                                ● Pix Aguardando Pagamento
                            </span>
                            <div class="font-tabular fw-bold mt-1" id="pix-valor-display" style="font-size: 17px; color: var(--text-primary, #0f172a);">
                                R$ 0,00
                            </div>
                        </div>
                        <!-- Cronômetro Digital de Validade -->
                        <div class="text-end">
                            <span class="text-muted" style="font-size: 10px; text-transform: uppercase;">Expira em:</span>
                            <div class="font-tabular fw-bold text-danger" id="pix-timer-display" style="font-size: 15px;">
                                15:00
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Centralizado -->
                    <div class="text-center my-3">
                        <div id="pix-qrcode-container" class="border rounded bg-white shadow-sm" style="width: 210px; height: 210px; padding: 10px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; margin: 0 auto; box-sizing: border-box;">
                            <!-- SVG do QR Code injetado via JS -->
                        </div>
                        <div class="text-muted mt-2" style="font-size: 11px;">
                            Aponte a câmera do aplicativo do seu banco para o QR Code acima
                        </div>
                    </div>

                    <!-- Código Pix Copia e Cola -->
                    <div class="mb-3">
                        <label for="pix-copia-cola-input" class="form-label d-flex justify-content-between align-items-center mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text-secondary, #475569);">
                            <span>Código Pix Copia e Cola</span>
                            <span class="text-success fw-normal d-none" id="label-copiado" style="font-size: 11px;">✓ Copiado com sucesso!</span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="pix-copia-cola-input" class="form-control font-monospace" style="font-size: 11px; background-color: #f8fafc;" readonly>
                            <button type="button" class="btn btn-enterprise btn-primary" onclick="copiarPixCodigo()" title="Copiar código Pix">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" class="bi bi-clipboard me-1" viewBox="0 0 16 16">
                                    <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/>
                                    <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/>
                                </svg>
                                Copiar
                            </button>
                        </div>
                    </div>

                    <!-- Status de Polling em Tempo Real -->
                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light" style="font-size: 11px;">
                        <div class="d-flex align-items-center gap-2 text-muted">
                            <span class="spinner-grow spinner-grow-sm text-primary" role="status" aria-hidden="true" style="width: 10px; height: 10px;"></span>
                            <span id="pix-status-texto">Aguardando confirmação bancária...</span>
                        </div>
                        <!-- Botão de Simulação em Ambiente Local Dev -->
                        <div id="bloco-simular-dev" class="d-none">
                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 10px;" onclick="simularPagamentoDev()">
                                ⚡ Simular Pagamento
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Painel de Sucesso Pós-Confirmação -->
                <div id="pix-resultado-sucesso" class="d-none border border-success rounded p-4 bg-white text-center shadow-sm">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle mb-3" style="width: 48px; height: 48px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-lg" viewBox="0 0 16 16">
                            <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/>
                        </svg>
                    </div>
                    <h3 class="fw-bold mb-1" style="font-size: 16px; color: #16a34a;">Recarga Confirmada com Sucesso!</h3>
                    <p class="text-secondary mb-2" style="font-size: 13px;">
                        O valor foi creditado instantaneamente na carteira da sua família.
                    </p>
                    <div class="font-tabular fw-bold text-dark my-2" id="pix-sucesso-novo-saldo" style="font-size: 20px;">
                        Novo Saldo: R$ 0,00
                    </div>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <button type="button" class="btn btn-enterprise btn-primary btn-sm" onclick="reiniciarPainelPix()">
                            Nova Recarga
                        </button>
                        <a href="<?php echo $baseUrl; ?>/portal/agendamento" class="btn btn-enterprise btn-primary btn-sm">
                            Fazer Agendamento Agora
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Extrato Contábil Analítico da Carteira -->
    <div class="card-enterprise p-3 p-md-4 mb-4" id="secao-extrato">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-2 border-bottom">
            <div>
                <h2 class="card-title mb-1" style="font-size: 15px; font-weight: 600;">Extrato Analítico de Lançamentos</h2>
                <p class="text-secondary mb-0" style="font-size: 12px;">
                    Histórico contábil completo de depósitos, débitos de pedidos e estornos auditados linha a linha.
                </p>
            </div>
            <div class="text-muted font-tabular" style="font-size: 12px;">
                Total: <strong><?php echo (int)($extrato['total'] ?? 0); ?></strong> transações
            </div>
        </div>

        <!-- Filtros de Busca -->
        <form method="GET" action="<?php echo $baseUrl; ?>/portal/carteira#secao-extrato" class="row g-2 align-items-end mb-3">
            <!-- Filtro por Dependente -->
            <div class="col-12 col-sm-6 col-md-3">
                <label for="filtro-aluno" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    Dependente:
                </label>
                <select id="filtro-aluno" name="aluno_id" class="form-select form-select-sm" style="font-size: 12px;">
                    <option value="">Todos os Dependentes</option>
                    <?php foreach ($dependentes as $dep): ?>
                        <option value="<?php echo (int)$dep['id']; ?>" <?php echo ((int)($filtros['aluno_id'] ?? 0) === (int)$dep['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep['nome'], ENT_QUOTES); ?> (<?php echo htmlspecialchars($dep['nome_turma'] ?? $dep['serie'] ?? '', ENT_QUOTES); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filtro por Tipo de Transação -->
            <div class="col-12 col-sm-6 col-md-3">
                <label for="filtro-tipo" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    Tipo de Operação:
                </label>
                <select id="filtro-tipo" name="tipo_transacao" class="form-select form-select-sm" style="font-size: 12px;">
                    <option value="">Todas as Operações</option>
                    <option value="recarga_pix" <?php echo (($filtros['tipo_transacao'] ?? '') === 'recarga_pix') ? 'selected' : ''; ?>>Recarga Pix Online</option>
                    <option value="recarga_dinheiro" <?php echo (($filtros['tipo_transacao'] ?? '') === 'recarga_dinheiro') ? 'selected' : ''; ?>>Recarga Balcão (Dinheiro)</option>
                    <option value="debito_pedido" <?php echo (($filtros['tipo_transacao'] ?? '') === 'debito_pedido') ? 'selected' : ''; ?>>Agendamento de Lanche</option>
                    <option value="estorno_cancelamento" <?php echo (($filtros['tipo_transacao'] ?? '') === 'estorno_cancelamento') ? 'selected' : ''; ?>>Estorno de Cancelamento</option>
                    <option value="estorno_balcao" <?php echo (($filtros['tipo_transacao'] ?? '') === 'estorno_balcao') ? 'selected' : ''; ?>>Estorno de Balcão</option>
                </select>
            </div>

            <!-- Filtro por Data Inicial -->
            <div class="col-6 col-sm-3 col-md-2">
                <label for="filtro-data-ini" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    De:
                </label>
                <input type="date" id="filtro-data-ini" name="data_inicio" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES); ?>" style="font-size: 12px;">
            </div>

            <!-- Filtro por Data Final -->
            <div class="col-6 col-sm-3 col-md-2">
                <label for="filtro-data-fim" class="form-label text-secondary mb-1" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                    Até:
                </label>
                <input type="date" id="filtro-data-fim" name="data_fim" class="form-control form-control-sm font-tabular" value="<?php echo htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES); ?>" style="font-size: 12px;">
            </div>

            <!-- Botões de Ação do Filtro -->
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-enterprise btn-primary btn-sm flex-grow-1">
                    Filtrar
                </button>
                <?php if (!empty($filtros['aluno_id']) || !empty($filtros['tipo_transacao']) || !empty($filtros['data_inicio']) || !empty($filtros['data_fim'])): ?>
                    <a href="<?php echo $baseUrl; ?>/portal/carteira#secao-extrato" class="btn btn-enterprise btn-primary btn-sm" title="Limpar filtros">
                        ✕
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tabela Data-Grid do Extrato -->
        <div class="table-responsive">
            <table class="table table-enterprise table-data-grid mb-0">
                <thead>
                    <tr>
                        <th class="cell-center" style="width: 140px;">Data & Hora</th>
                        <th class="cell-left" style="width: 180px;">Tipo de Operação</th>
                        <th class="cell-left">Descrição</th>
                        <th class="cell-left" style="width: 160px;">Beneficiário</th>
                        <th class="cell-right" style="width: 120px;">Valor</th>
                        <th class="cell-right" style="width: 130px;">Saldo Resultante</th>
                    </tr>
                </thead>
                <tbody id="tabela-extrato-tbody">
                    <?php if (empty($extrato['itens'])): ?>
                        <tr>
                            <td colspan="6" class="p-0 border-0">
                                <?php 
                                    $emptyMessage = "Não há nada para mostrar aqui";
                                    $emptySubtext = "Nenhuma movimentação registrada para os critérios informados.";
                                    require __DIR__ . '/../partials/empty_state.php';
                                ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($extrato['itens'] as $item): ?>
                            <tr>
                                <td class="cell-center font-tabular" style="font-size: 12px;">
                                    <?php echo htmlspecialchars($item['data_fmt'], ENT_QUOTES); ?>
                                </td>
                                <td class="cell-left">
                                    <code class="code-tag" style="font-size: 11px;">
                                        <?php echo htmlspecialchars($item['tipo_rotulo'], ENT_QUOTES); ?>
                                    </code>
                                </td>
                                <td class="cell-left" style="font-size: 12px;">
                                    <?php echo htmlspecialchars($item['descricao'], ENT_QUOTES); ?>
                                    <?php if (!empty($item['referencia_id'])): ?>
                                        <div class="text-muted font-tabular" style="font-size: 10px;">
                                            Ref: <?php echo htmlspecialchars(substr($item['referencia_id'], 0, 18), ENT_QUOTES); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-left" style="font-size: 12px;">
                                    <?php if (!empty($item['aluno_nome'])): ?>
                                        <span class="fw-medium text-dark"><?php echo htmlspecialchars($item['aluno_nome'], ENT_QUOTES); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash; Familiar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cell-right font-tabular fw-semibold" style="font-size: 13px; color: <?php echo $item['is_credito'] ? '#16a34a' : 'var(--text-primary, #0f172a)'; ?>;">
                                    <?php echo $item['is_credito'] ? '+ ' : '- '; ?>
                                    R$ <?php echo number_format($item['valor_num'], 2, ',', '.'); ?>
                                </td>
                                <td class="cell-right font-tabular fw-bold" style="font-size: 13px; color: var(--text-primary, #0f172a);">
                                    R$ <?php echo number_format($item['saldo_resultante_num'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if (!empty($extrato['total_paginas']) && $extrato['total_paginas'] > 1): ?>
            <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top" style="font-size: 12px;">
                <div class="text-muted font-tabular">
                    Página <strong><?php echo (int)$extrato['pagina']; ?></strong> de <strong><?php echo (int)$extrato['total_paginas']; ?></strong>
                </div>
                <div class="d-flex gap-1">
                    <?php if ($extrato['pagina'] > 1): ?>
                        <a href="<?php echo $baseUrl; ?>/portal/carteira?pagina=<?php echo ($extrato['pagina'] - 1); ?><?php echo !empty($filtros['aluno_id']) ? '&aluno_id='.$filtros['aluno_id'] : ''; ?><?php echo !empty($filtros['tipo_transacao']) ? '&tipo_transacao='.$filtros['tipo_transacao'] : ''; ?>#secao-extrato" class="btn btn-enterprise btn-secondary btn-sm">
                            &laquo; Anterior
                        </a>
                    <?php endif; ?>

                    <?php if ($extrato['pagina'] < $extrato['total_paginas']): ?>
                        <a href="<?php echo $baseUrl; ?>/portal/carteira?pagina=<?php echo ($extrato['pagina'] + 1); ?><?php echo !empty($filtros['aluno_id']) ? '&aluno_id='.$filtros['aluno_id'] : ''; ?><?php echo !empty($filtros['tipo_transacao']) ? '&tipo_transacao='.$filtros['tipo_transacao'] : ''; ?>#secao-extrato" class="btn btn-enterprise btn-secondary btn-sm">
                            Próxima &raquo;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts Dinâmicos de Polling, Recarga e Cópias de 1 Clique -->
<script>
    let pixTxidAtual = null;
    let pixPollingInterval = null;
    let pixTimerInterval = null;
    let pixTempoRestante = 900; // 15 minutos

    function selecionarValor(valor) {
        const input = document.getElementById('input-valor-pix');
        if (input) {
            input.value = valor.toFixed(2).replace('.', ',');
            input.focus();
        }
    }

    async function gerarPix(event) {
        event.preventDefault();

        const btnSubmit = document.getElementById('btn-submit-pix');
        const btnText = document.getElementById('btn-pix-text');
        const btnSpinner = document.getElementById('btn-pix-spinner');
        const errorDiv = document.getElementById('pix-form-error');
        const inputValor = document.getElementById('input-valor-pix');
        const csrfToken = document.getElementById('pix-csrf-token')?.value || '';

        errorDiv.classList.add('d-none');
        errorDiv.textContent = '';
        btnSubmit.disabled = true;
        btnText.textContent = 'Gerando Pix...';
        btnSpinner.classList.remove('d-none');

        try {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('valor', inputValor.value);

            const response = await fetch('<?php echo $baseUrl; ?>/portal/recarga/gerar', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.sucesso) {
                throw new Error(data.erro || 'Falha ao processar requisição.');
            }

            // Exibe o painel ativo com QR Code e Copia e Cola
            pixTxidAtual = data.txid;
            pixTempoRestante = data.expira_em_segundos || 900;

            document.getElementById('pix-valor-display').textContent = data.valor_formatado;
            document.getElementById('pix-copia-cola-input').value = data.copia_e_cola;
            document.getElementById('pix-qrcode-container').innerHTML = data.qr_code_svg;

            document.getElementById('pix-resultado-vazio').classList.add('d-none');
            document.getElementById('pix-resultado-sucesso').classList.add('d-none');
            document.getElementById('pix-resultado-ativo').classList.remove('d-none');

            // Habilita atalho dev se estiver em modo desenvolvimento
            const blocoDev = document.getElementById('bloco-simular-dev');
            if (blocoDev) {
                if (data.is_dev) {
                    blocoDev.classList.remove('d-none');
                } else {
                    blocoDev.classList.add('d-none');
                }
            }

            // Inicia o cronômetro digital
            iniciarTimerPix();

            // Inicia o polling assíncrono para verificar pagamento
            iniciarPollingPix(data.txid);

        } catch (err) {
            errorDiv.textContent = err.message || 'Erro ao gerar Pix. Tente novamente.';
            errorDiv.classList.remove('d-none');
        } finally {
            btnSubmit.disabled = false;
            btnText.textContent = 'Gerar Código Pix';
            btnSpinner.classList.add('d-none');
        }
    }

    function iniciarTimerPix() {
        if (pixTimerInterval) clearInterval(pixTimerInterval);

        const timerDisplay = document.getElementById('pix-timer-display');
        atualizarDisplayTimer(timerDisplay, pixTempoRestante);

        pixTimerInterval = setInterval(() => {
            pixTempoRestante--;
            if (pixTempoRestante <= 0) {
                clearInterval(pixTimerInterval);
                if (pixPollingInterval) clearInterval(pixPollingInterval);
                timerDisplay.textContent = 'EXPIRADO';
                document.getElementById('pix-status-texto').textContent = 'O tempo para pagamento deste Pix expirou.';
            } else {
                atualizarDisplayTimer(timerDisplay, pixTempoRestante);
            }
        }, 1000);
    }

    function atualizarDisplayTimer(el, segundos) {
        const m = Math.floor(segundos / 60);
        const s = segundos % 60;
        el.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }

    function iniciarPollingPix(txid) {
        if (pixPollingInterval) clearInterval(pixPollingInterval);

        // Consulta o status a cada 2,5 segundos
        pixPollingInterval = setInterval(async () => {
            try {
                const res = await fetch(`<?php echo $baseUrl; ?>/portal/recarga/status/${txid}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;

                const data = await res.json();

                if (data.status === 'pago') {
                    // Pagamento detectado! Para polling e exibe sucesso
                    clearInterval(pixPollingInterval);
                    if (pixTimerInterval) clearInterval(pixTimerInterval);

                    concluirPagamentoComSucesso(data);
                } else if (data.status === 'expirado') {
                    clearInterval(pixPollingInterval);
                    if (pixTimerInterval) clearInterval(pixTimerInterval);
                    document.getElementById('pix-status-texto').textContent = 'Cobrança expirada.';
                }
            } catch (e) {
                // Erros transitórios de rede não interrompem o polling
            }
        }, 2500);
    }

    function concluirPagamentoComSucesso(data) {
        document.getElementById('pix-resultado-ativo').classList.add('d-none');
        const painelSucesso = document.getElementById('pix-resultado-sucesso');
        painelSucesso.classList.remove('d-none');

        if (data.novo_saldo_fmt) {
            document.getElementById('pix-sucesso-novo-saldo').textContent = `Novo Saldo: ${data.novo_saldo_fmt}`;
            
            // Atualiza os contadores na tela instantaneamente
            const displayTotal = document.getElementById('display-saldo-total');
            if (displayTotal) displayTotal.textContent = data.novo_saldo_fmt;

            // Recarrega o extrato após 2 segundos
            setTimeout(() => {
                window.location.reload();
            }, 2500);
        }
    }

    async function simularPagamentoDev() {
        if (!pixTxidAtual) return;
        const btn = document.querySelector('#bloco-simular-dev button') || event?.target;
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Simulando...';
        }

        try {
            const formData = new FormData();
            formData.append('txid', pixTxidAtual);
            formData.append('valor', document.getElementById('input-valor-pix')?.value || '50,00');
            const csrfToken = document.getElementById('pix-csrf-token')?.value || '';
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }

            const res = await fetch(`<?php echo $baseUrl; ?>/portal/recarga/simular/${pixTxidAtual}`, {
                method: 'POST',
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const data = await res.json();
            if (data.sucesso) {
                clearInterval(pixPollingInterval);
                if (pixTimerInterval) clearInterval(pixTimerInterval);
                concluirPagamentoComSucesso({
                    novo_saldo_fmt: data.saldo_fmt
                });
            } else {
                alert(data.erro || 'Falha ao simular pagamento.');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '⚡ Simular Pagamento';
                }
            }
        } catch (e) {
            alert('Erro de conexão ao simular.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = '⚡ Simular Pagamento';
            }
        }
    }

    function copiarPixCodigo() {
        const input = document.getElementById('pix-copia-cola-input');
        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999); // Para dispositivos móveis

        navigator.clipboard.writeText(input.value).then(() => {
            const label = document.getElementById('label-copiado');
            if (label) {
                label.classList.remove('d-none');
                setTimeout(() => {
                    label.classList.add('d-none');
                }, 3000);
            }
        }).catch(() => {
            document.execCommand('copy');
        });
    }

    function reiniciarPainelPix() {
        if (pixPollingInterval) clearInterval(pixPollingInterval);
        if (pixTimerInterval) clearInterval(pixTimerInterval);
        pixTxidAtual = null;

        document.getElementById('pix-resultado-sucesso').classList.add('d-none');
        document.getElementById('pix-resultado-ativo').classList.add('d-none');
        document.getElementById('pix-resultado-vazio').classList.remove('d-none');
    }
</script>
