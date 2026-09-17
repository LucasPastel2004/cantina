<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View de Homologação: Galeria de Componentes Parciais
 * 
 * Conforme docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
?>

<div class="row g-4 mb-4">
    <!-- Seção 1: Alertas Corporativos -->
    <div class="col-12 col-lg-6">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="h2-title mb-0">Alertas Corporativos (Enterprise Alerts)</span>
                <span class="text-microcopy">WCAG AA &bull; Alto Contraste</span>
            </div>

            <!-- Alerta de Sucesso -->
            <div class="alert-enterprise alert-enterprise-success">
                <div class="alert-enterprise-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.751.751 0 0 1 .018-1.042.751.751 0 0 1 1.042-.018L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"/></svg>
                </div>
                <div class="alert-enterprise-body">
                    <strong class="alert-enterprise-title">Operação Realizada:</strong>
                    <span>Recarga Pix no valor de R$ 50,00 creditada na carteira familiar com sucesso.</span>
                </div>
                <button type="button" class="alert-enterprise-close" onclick="this.closest('.alert-enterprise').remove();" aria-label="Fechar">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/></svg>
                </button>
            </div>

            <!-- Alerta de Erro -->
            <div class="alert-enterprise alert-enterprise-danger">
                <div class="alert-enterprise-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M2.343 13.657A8 8 0 1 1 13.657 2.343 8 8 0 0 1 2.343 13.657ZM6.03 4.97a.75.75 0 0 0-1.06 1.06L6.94 8 4.97 9.97a.749.749 0 0 0 .326 1.275.749.749 0 0 0 .734-.215L8 9.06l1.97 1.97a.749.749 0 0 0 1.275-.326.749.749 0 0 0-.215-.734L9.06 8l1.97-1.97a.749.749 0 0 0-.326-1.275.749.749 0 0 0-.734.215L8 6.94 6.03 4.97Z"/></svg>
                </div>
                <div class="alert-enterprise-body">
                    <strong class="alert-enterprise-title">Trava de Corte Operacional:</strong>
                    <span>O horário limite das 08:00 expirou. Não é possível alterar ou cancelar novos pedidos.</span>
                </div>
                <button type="button" class="alert-enterprise-close" onclick="this.closest('.alert-enterprise').remove();" aria-label="Fechar">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/></svg>
                </button>
            </div>

            <!-- Alerta de Restrição Alimentar -->
            <div class="alert-enterprise alert-enterprise-warning">
                <div class="alert-enterprise-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M6.457 1.047c.659-1.234 2.427-1.234 3.086 0l6.082 11.378A1.75 1.75 0 0 1 14.082 15H1.918a1.75 1.75 0 0 1-1.543-2.575Zm1.763.707a.25.25 0 0 0-.44 0L1.698 13.132a.25.25 0 0 0 .22.368h12.164a.25.25 0 0 0 .22-.368Zm.53 3.996v2.5a.75.75 0 0 1-1.5 0v-2.5a.75.75 0 0 1 1.5 0ZM9 11a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/></svg>
                </div>
                <div class="alert-enterprise-body">
                    <strong class="alert-enterprise-title">Alergia Diagnosticada:</strong>
                    <span>O item selecionado contém derivados de leite e o estudante possui restrição a lactose.</span>
                </div>
                <button type="button" class="alert-enterprise-close" onclick="this.closest('.alert-enterprise').remove();" aria-label="Fechar">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor"><path d="M3.72 3.72a.75.75 0 0 1 1.06 0L8 6.94l3.22-3.22a.749.749 0 0 1 1.275.326.749.749 0 0 1-.215.734L9.06 8l3.22 3.22a.749.749 0 0 1-.326 1.275.749.749 0 0 1-.734-.215L8 9.06l-3.22 3.22a.751.751 0 0 1-1.042-.018.751.751 0 0 1-.018-1.042L6.94 8 3.72 4.78a.75.75 0 0 1 0-1.06Z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Seção 2: Estados Visuais e Modais -->
    <div class="col-12 col-lg-6">
        <div class="card-enterprise h-100">
            <div class="card-enterprise-header">
                <span class="h2-title mb-0">Estados Visuais e Modais</span>
                <span class="text-microcopy">Componentes Reutilizáveis</span>
            </div>

            <!-- Botão de Abertura de Modal -->
            <div class="mb-4">
                <label class="form-label">Modal Corporativo com Foco Acessível:</label>
                <div>
                    <button type="button" class="btn btn-enterprise btn-primary" onclick="abrirModal('modalExemplo')">
                        Abrir Modal de Exemplo
                    </button>
                </div>
            </div>

            <!-- Tags e Indicadores -->
            <div class="mb-4">
                <label class="form-label">Tags de Segurança Alimentar e Métodos:</label>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="tag-alergeno">CONTÉM GLÚTEN</span>
                    <span class="tag-alergeno">CONTÉM LACTOSE</span>
                    <span class="tag-alergeno">CONTÉM AMENDOIM</span>
                    <code class="code-tag">GET /api/v1/corte</code>
                    <code class="code-tag">POST /api/v1/pix</code>
                </div>
            </div>

            <!-- Botões e Feedback Tátil -->
            <div>
                <label class="form-label">Botões do Sistema (com microinteração tátil no clique):</label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-enterprise btn-primary">Primário (Gravar)</button>
                    <button type="button" class="btn btn-enterprise btn-secondary">Secundário</button>
                    <button type="button" class="btn btn-enterprise btn-success">Sucesso</button>
                    <button type="button" class="btn btn-enterprise btn-danger">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seção 3: Estado Vazio e Loading -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <span class="h2-title mb-0">Estado Vazio Padronizado (Empty State)</span>
                <span class="text-microcopy">docs/DESIGN.md</span>
            </div>
            <?php
            $emptyMessage = 'Não há nada para mostrar aqui';
            $emptyDescription = 'Nenhuma perda de estoque foi registrada no turno matutino de hoje.';
            $emptyActionText = 'Registrar Nova Baixa';
            $emptyActionUrl = $baseUrl . '/estoque/perdas';
            require __DIR__ . '/../partials/empty_state.php';
            ?>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card-enterprise">
            <div class="card-enterprise-header">
                <span class="h2-title mb-0">Estado de Carregamento (Loading & Skeleton)</span>
                <span class="text-microcopy">Spinner Azul Cobalto e Skeleton</span>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <span class="text-microcopy mb-2 d-block">Spinner Oficial:</span>
                        <?php
                        $loadingText = 'Carregando extrato...';
                        $isSkeleton = false;
                        require __DIR__ . '/../partials/loading.php';
                        ?>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3">
                        <span class="text-microcopy mb-2 d-block">Skeleton Data Grid:</span>
                        <?php
                        $loadingText = 'Carregando linhas...';
                        $isSkeleton = true;
                        $skeletonRows = 3;
                        require __DIR__ . '/../partials/loading.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Parcial de Demonstração -->
<?php
$modalId = 'modalExemplo';
$modalTitle = 'Janela Modal de Confirmação';
$modalBody = '<p class="text-secondary mb-0">Esta é a estrutura padrão de modal do Cantina Web. Ela utiliza cantos arredondados discretos de 8px, foco com contorno azul cobalto acessível e botões táteis no rodapé.</p>';
$confirmText = 'Gravar Alterações';
$confirmClass = 'btn-primary';
$confirmId = 'btnGravarModalExemplo';
require __DIR__ . '/../partials/modal.php';
?>
