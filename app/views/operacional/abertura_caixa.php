<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View Operacional: Abertura de Caixa por Turno
 * 
 * Conforme docs/FSD.md (Módulo 7, Tela 7.1) e docs/DESIGN.md (Anti-Slop Enterprise)
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$turnoAtual = $turnoAtual ?? 'matutino';
$turnoAtivo = $turnoAtivo ?? ucfirst($turnoAtual);
$csrfToken = \App\Core\Session::generateCsrfToken();
?>

<!-- Cabeçalho -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h1 class="h5 font-weight-bold text-dark mb-0">Abertura de Caixa por Turno</h1>
            <span class="badge bg-white text-dark border" style="font-size: 11px; font-weight: 600; border-color: #cbd5e1 !important;">
                <span class="bullet" style="color: #64748b;">●</span> Turno <?php echo $turnoAtivo; ?>
            </span>
        </div>
        <p class="text-secondary text-microcopy mb-0">
            Libere as operações em dinheiro e Pix no PDV declarando o Fundo de Troco disponível fisicamente na gaveta.
        </p>
    </div>

    <div>
        <a href="<?php echo $baseUrl; ?>/operacional/caixa?turno=<?php echo urlencode($turnoAtual); ?>" class="btn btn-enterprise btn-primary btn-sm">
            ← Voltar à Gestão de Caixa
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Formulário de Abertura -->
    <div class="col-12 col-lg-7">
        <div class="card-enterprise">
            <div class="card-enterprise-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold text-dark">Dados de Abertura da Sessão</span>
                <span class="code-tag">Controle de Gaveta</span>
            </div>
            <div class="card-enterprise-body p-4">
                <p class="font-13 text-secondary mb-4">
                    Para iniciar o atendimento presencial no <strong>PDV de Balcão</strong>, informe o turno operacional e conte as cédulas e moedas físicas colocadas na gaveta como Fundo de Troco inicial.
                </p>

                <form action="<?php echo $baseUrl; ?>/operacional/caixa/abrir" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label font-12 font-semibold">Turno Operacional</label>
                            <select name="turno" class="form-select form-select-sm" onchange="window.location.href='<?php echo $baseUrl; ?>/operacional/caixa/abertura?turno=' + this.value;">
                                <option value="matutino" <?php echo ($turnoAtual === 'matutino') ? 'selected' : ''; ?>>
                                    Matutino (Encerra às 11:00)
                                </option>
                                <option value="vespertino" <?php echo ($turnoAtual === 'vespertino') ? 'selected' : ''; ?>>
                                    Vespertino (Encerra às 16:00)
                                </option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label font-12 font-semibold">Fundo de Troco Inicial (Gaveta)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text font-bold">R$</span>
                                <input type="text" name="fundo_troco_inicial" class="form-control font-tabular input-money font-semibold" value="50,00" required autofocus>
                            </div>
                            <div class="form-text font-11 text-muted">Contagem das notas e moedas físicas na gaveta.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label font-12 font-semibold">Observações de Abertura (Opcional)</label>
                        <input type="text" name="observacoes" class="form-control form-control-sm" placeholder="Ex: Moedas de R$ 0,50 e notas trocadas conferidas...">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?php echo $baseUrl; ?>/operacional/caixa" class="btn btn-enterprise btn-secondary px-3">
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-enterprise btn-primary px-4">
                            Confirmar Abertura de Caixa
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Regras e Recomendações -->
    <div class="col-12 col-lg-5">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="font-weight-bold text-dark">Diretrizes Institucionais de Caixa</span>
            </div>
            <div class="card-enterprise-body p-4 font-13 text-secondary" style="line-height: 1.6;">
                <div class="d-flex align-items-start gap-2 mb-3">
                    <span class="badge bg-light border text-dark font-tabular">11:00</span>
                    <div>
                        <strong>Fechamento do Turno Matutino:</strong>
                        O caixa da manhã deve ser conciliado e encerrado pontualmente às 11:00 pelo operador de serviço.
                    </div>
                </div>
                <div class="d-flex align-items-start gap-2 mb-3">
                    <span class="badge bg-light border text-dark font-tabular">16:00</span>
                    <div>
                        <strong>Fechamento do Turno Vespertino:</strong>
                        O caixa da tarde deve ser encerrado às 16:00 com emissão do espelho A4 timbrado para assinatura.
                    </div>
                </div>
                <div class="d-flex align-items-start gap-2 mb-3">
                    <span class="badge bg-light border text-dark">Trava</span>
                    <div>
                        <strong>Obrigatoriedade de Caixa Aberto:</strong>
                        Vendas avulsas e recargas em dinheiro só podem ser processadas com sessão de caixa aberta.
                    </div>
                </div>
                <div class="d-flex align-items-start gap-2">
                    <span class="badge bg-light border text-dark">Unicidade</span>
                    <div>
                        Não é permitida mais de uma sessão aberta simultaneamente no mesmo turno.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
