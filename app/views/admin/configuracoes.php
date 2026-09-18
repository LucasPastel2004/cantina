<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Configurações Globais e Parâmetros Institucionais
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$configuracoes = $configuracoes ?? [];
$csrfField = $csrf_field ?? '';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h5 font-weight-bold text-dark mb-1">Configurações Globais</h1>
        <p class="text-secondary text-microcopy mb-0">
            Parâmetros institucionais, operacionais e regras financeiras da cantina escolar.
        </p>
    </div>
</div>

<form action="<?php echo $baseUrl; ?>/admin/configuracoes/salvar" method="POST" enctype="multipart/form-data" class="m-0">
    <?php echo $csrfField; ?>

    <div class="row g-4">
        <!-- Bloco 1: Dados Cadastrais e Institucionais -->
        <div class="col-12 col-lg-6">
            <div class="card-enterprise h-100">
                <div class="card-enterprise-header">
                    <span class="font-weight-bold text-dark">Dados Institucionais da Cantina</span>
                </div>
                <div class="card-enterprise-body p-4">
                    <div class="mb-3">
                        <label for="razao_social" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                            Razão Social <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="razao_social" name="razao_social" 
                               value="<?php echo htmlspecialchars($configuracoes['razao_social'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required maxlength="150">
                    </div>

                    <div class="mb-3">
                        <label for="nome_fantasia" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                            Nome Fantasia <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-sm" id="nome_fantasia" name="nome_fantasia" 
                               value="<?php echo htmlspecialchars($configuracoes['nome_fantasia'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required maxlength="100">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="cnpj" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                CNPJ <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" id="cnpj" name="cnpj" 
                                   value="<?php echo htmlspecialchars($configuracoes['cnpj'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required maxlength="18">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="telefone_contato" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                WhatsApp de Suporte
                            </label>
                            <input type="text" class="form-control form-control-sm font-tabular" id="telefone_contato" name="telefone_contato" 
                                   value="<?php echo htmlspecialchars($configuracoes['telefone_contato'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="20">
                        </div>
                    </div>

                    <div class="pt-2 border-top">
                        <label class="form-label font-weight-semibold text-secondary d-block" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                            Logotipo Oficial da Instituição
                        </label>
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?php echo $baseUrl . '/' . ($configuracoes['logo_path'] ?? 'assets/img/logo-colegio.jpg'); ?>" 
                                 alt="Logotipo Atual" width="48" height="48" 
                                 style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);">
                            <div class="flex-grow-1">
                                <input type="file" class="form-control form-control-sm" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp">
                                <span class="text-muted" style="font-size: 11px;">Formatos aceitos: JPG, PNG ou WebP (máx. 10 MB).</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloco 2: Parâmetros Financeiros e Operacionais -->
        <div class="col-12 col-lg-6">
            <div class="card-enterprise h-100">
                <div class="card-enterprise-header">
                    <span class="font-weight-bold text-dark">Regras Operacionais e Financeiras</span>
                </div>
                <div class="card-enterprise-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="recarga_minima_pix" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                Recarga Mínima Pix (R$) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.50" min="1.00" class="form-control form-control-sm font-tabular" id="recarga_minima_pix" name="recarga_minima_pix" 
                                   value="<?php echo number_format((float)($configuracoes['recarga_minima_pix'] ?? 10.00), 2, '.', ''); ?>" required>
                            <span class="text-muted" style="font-size: 11px;">Valor padrão: R$ 10,00</span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="alerta_saldo_baixo" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                Alerta de Saldo Baixo (R$) <span class="text-danger">*</span>
                            </label>
                            <input type="number" step="0.50" min="0.00" class="form-control form-control-sm font-tabular" id="alerta_saldo_baixo" name="alerta_saldo_baixo" 
                                   value="<?php echo number_format((float)($configuracoes['alerta_saldo_baixo'] ?? 15.00), 2, '.', ''); ?>" required>
                            <span class="text-muted" style="font-size: 11px;">Gatilho de aviso aos pais</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-sm-6">
                            <label for="timeout_sessao_minutos" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                Timeout de Sessão (minutos)
                            </label>
                            <input type="number" min="5" max="480" class="form-control form-control-sm font-tabular" id="timeout_sessao_minutos" name="timeout_sessao_minutos" 
                                   value="<?php echo (int)($configuracoes['timeout_sessao_minutos'] ?? 60); ?>" required>
                            <span class="text-muted" style="font-size: 11px;">Inatividade permitida (padrão: 60)</span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label for="limite_tentativas_login" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                                Limite de Falhas de Login
                            </label>
                            <input type="number" min="3" max="20" class="form-control form-control-sm font-tabular" id="limite_tentativas_login" name="limite_tentativas_login" 
                                   value="<?php echo (int)($configuracoes['limite_tentativas_login'] ?? 5); ?>" required>
                            <span class="text-muted" style="font-size: 11px;">Bloqueio temporário (padrão: 5)</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="itens_por_pagina" class="form-label font-weight-semibold text-secondary" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em;">
                            Itens por Página nas Tabelas
                        </label>
                        <input type="number" min="10" max="100" class="form-control form-control-sm font-tabular" id="itens_por_pagina" name="itens_por_pagina" 
                               value="<?php echo (int)($configuracoes['itens_por_pagina'] ?? 25); ?>" required>
                    </div>

                    <div class="p-3 rounded border bg-light" style="font-size: 12px; color: #475569;">
                        <strong>Informação:</strong> Os parâmetros salvos nesta tela são aplicados imediatamente em todas as rotinas de validação de recarga, alertas e tempos de sessão do sistema.
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão de Ação -->
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-enterprise btn-primary">
                Gravar Configurações
            </button>
        </div>
    </div>
</form>
