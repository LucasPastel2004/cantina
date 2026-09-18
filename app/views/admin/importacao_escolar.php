<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Importação Escolar em Lote (Alunos, Turmas e Responsáveis)
 * 
 * Conforme docs/FSD.md, docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$previa  = $previa ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1 text-microcopy">
                <li class="breadcrumb-item"><a href="<?php echo $baseUrl; ?>/admin">Administração</a></li>
                <li class="breadcrumb-item active" aria-current="page">Importação Escolar em Lote</li>
            </ol>
        </nav>
        <h1 class="h5 font-weight-bold text-dark mb-0">Importação Escolar em Lote</h1>
        <p class="text-secondary text-microcopy mb-0">Carga massiva e atualização de turmas, alunos e vínculos de responsáveis via planilha CSV.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo $baseUrl; ?>/admin/importacao-escolar/modelo" class="btn btn-enterprise btn-secondary">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="currentColor" class="me-1"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
            Baixar Planilha Modelo (.csv)
        </a>
        <a href="<?php echo $baseUrl; ?>/admin/alunos" class="btn btn-enterprise btn-secondary">
            Ver Lista de Alunos
        </a>
    </div>
</div>

<?php if (empty($previa)): ?>
    <!-- ETAPA 1: SELEÇÃO E UPLOAD DA PLANILHA -->
    <div class="row g-4">
        <!-- Card de Upload -->
        <div class="col-12 col-lg-7">
            <div class="card-enterprise">
                <div class="card-enterprise-header">
                    <h2 class="h6 font-weight-bold text-dark mb-0">Enviar Arquivo de Planilha</h2>
                    <span class="text-microcopy text-muted">Formato suportado: .csv</span>
                </div>
                <div class="card-enterprise-body">
                    <form action="<?php echo $baseUrl; ?>/admin/importacao-escolar/analisar" method="POST" enctype="multipart/form-data">
                        <?php if (!empty($csrf_field)) echo $csrf_field; ?>

                        <div class="mb-4 p-4 border rounded text-center bg-light" style="border-style: dashed !important; border-color: #cbd5e1 !important;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5" class="mb-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <h3 class="h6 font-weight-semibold text-dark mb-1">Selecione o arquivo CSV escolar</h3>
                            <p class="text-microcopy text-muted mb-3">Tamanho máximo de até 10 MB. Delimitadores aceitos: ponto-e-vírgula (;) ou vírgula (,).</p>
                            
                            <div class="d-inline-block text-start">
                                <input type="file" name="planilha" id="inputPlanilha" accept=".csv,text/csv,text/plain" required class="form-control form-control-sm">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-enterprise btn-primary">
                                Analisar e Conferir Prévia &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Card de Diretrizes e Orientações Técnicas -->
        <div class="col-12 col-lg-5">
            <div class="card-enterprise">
                <div class="card-enterprise-header">
                    <h2 class="h6 font-weight-bold text-dark mb-0">Diretrizes de Importação</h2>
                    <span class="status-indicator status-active"><span class="bullet">●</span> Preservação Contábil</span>
                </div>
                <div class="card-enterprise-body">
                    <ul class="list-unstyled text-microcopy text-secondary mb-0" style="line-height: 1.6;">
                        <li class="mb-2">
                            <strong class="text-dark">1. Colunas Obrigatórias:</strong> Nome do Responsável, CPF do Responsável, WhatsApp, Matrícula do Aluno, Nome do Aluno e Turma.
                        </li>
                        <li class="mb-2">
                            <strong class="text-dark">2. Chave Única Familiar:</strong> O CPF é o identificador único do responsável. Se o responsável já existir, a importação <strong>NUNCA</strong> sobrescreverá sua senha cadastrada nem alterará o saldo da carteira.
                        </li>
                        <li class="mb-2">
                            <strong class="text-dark">3. Irmãos e Dependentes:</strong> Múltiplos alunos com o mesmo CPF de responsável serão automaticamente associados à mesma carteira familiar única.
                        </li>
                        <li class="mb-2">
                            <strong class="text-dark">4. Atualização de Alunos:</strong> Caso a matrícula do aluno já exista, seus dados cadastrais (turma e turno) serão atualizados e preservará todo o histórico de compras.
                        </li>
                        <li class="mb-0">
                            <strong class="text-dark">5. Coluna de Alérgenos (Opcional):</strong> Ingredientes ou alérgenos diagnosticados podem ser informados na coluna <code>alergias</code> separados por vírgula (ex: <em>Lactose, Glúten</em>).
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ETAPA 2: PAINEL DE CONFERÊNCIA PRÉVIA (DRY-RUN) -->
    <div class="card-enterprise mb-4">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h6 font-weight-bold text-dark mb-0">Resumo da Análise Prévia</h2>
                <span class="text-microcopy text-muted">Confira as métricas estimadas antes de confirmar a gravação no banco de dados</span>
            </div>
            <div class="d-flex gap-2">
                <form action="<?php echo $baseUrl; ?>/admin/importacao-escolar/cancelar" method="POST" class="d-inline m-0">
                    <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                    <button type="submit" class="btn btn-enterprise btn-secondary btn-sm">
                        Cancelar Prévia
                    </button>
                </form>
                <?php if ($previa['linhas_validas'] > 0): ?>
                    <form action="<?php echo $baseUrl; ?>/admin/importacao-escolar/processar" method="POST" class="d-inline m-0">
                        <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                        <button type="submit" class="btn btn-enterprise btn-primary btn-sm">
                            Confirmar e Processar Importação
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-enterprise-body p-0">
            <!-- Stat-Strip Executivo -->
            <div class="stat-strip">
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Total Linhas</span>
                    <span class="stat-strip-value font-tabular"><?php echo $previa['total_linhas']; ?></span>
                </div>
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Aptas p/ Importar</span>
                    <span class="stat-strip-value font-tabular text-success"><?php echo $previa['linhas_validas']; ?></span>
                </div>
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Linhas com Erro</span>
                    <span class="stat-strip-value font-tabular text-danger"><?php echo $previa['linhas_com_erro']; ?></span>
                </div>
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Novos Alunos</span>
                    <span class="stat-strip-value font-tabular text-primary">+<?php echo $previa['resumo']['novos_alunos']; ?></span>
                </div>
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Alunos Atualizados</span>
                    <span class="stat-strip-value font-tabular"><?php echo $previa['resumo']['alunos_atualizados']; ?></span>
                </div>
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Novos Responsáveis</span>
                    <span class="stat-strip-value font-tabular text-primary">+<?php echo $previa['resumo']['novos_responsaveis']; ?></span>
                </div>
                <div class="stat-strip-item">
                    <span class="stat-strip-label">Novas Turmas</span>
                    <span class="stat-strip-value font-tabular text-primary">+<?php echo $previa['resumo']['novas_turmas']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela Data-Grid de Pré-visualização das Linhas -->
    <div class="card-enterprise">
        <div class="card-enterprise-header d-flex justify-content-between align-items-center">
            <h2 class="h6 font-weight-bold text-dark mb-0">Detalhamento das Linhas Analisadas</h2>
            <span class="text-microcopy text-muted">Exibindo até 100 linhas da amostra</span>
        </div>
        <div class="card-enterprise-body p-0">
            <div class="table-responsive">
                <table class="table table-enterprise table-data-grid mb-0">
                    <thead>
                        <tr>
                            <th class="cell-center" style="width: 50px;">Linha</th>
                            <th style="width: 80px;">Status</th>
                            <th>Aluno</th>
                            <th>Matrícula</th>
                            <th>Turma / Turno</th>
                            <th>Responsável</th>
                            <th>CPF</th>
                            <th>Diagnóstico / Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($previa['amostra_linhas'] as $linha): ?>
                            <tr>
                                <td class="cell-center font-tabular text-muted"><?php echo $linha['linha_num']; ?></td>
                                <td>
                                    <?php if ($linha['status'] === 'ok'): ?>
                                        <span class="status-indicator status-active"><span class="bullet">●</span> Apto</span>
                                    <?php elseif ($linha['status'] === 'aviso'): ?>
                                        <span class="status-indicator status-warning"><span class="bullet">▲</span> Aviso</span>
                                    <?php else: ?>
                                        <span class="status-indicator status-danger"><span class="bullet">✕</span> Erro</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-weight-semibold text-dark"><?php echo htmlspecialchars($linha['nome_aluno'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if ($linha['aluno_status'] === 'novo'): ?>
                                        <span class="badge bg-primary-subtle text-primary border" style="font-size: 10px;">Novo Aluno</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 10px;">Atualização</span>
                                    <?php endif; ?>
                                    <?php if (!empty($linha['alergias'])): ?>
                                        <span class="tag-alergeno ms-1" style="font-size: 10px;"><?php echo htmlspecialchars($linha['alergias'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="code-tag"><?php echo htmlspecialchars($linha['matricula'], ENT_QUOTES, 'UTF-8'); ?></code>
                                </td>
                                <td>
                                    <div class="font-weight-medium text-dark"><?php echo htmlspecialchars($linha['turma'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy text-muted"><?php echo ucfirst($linha['turno']); ?></div>
                                </td>
                                <td>
                                    <div class="text-dark"><?php echo htmlspecialchars($linha['nome_responsavel'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="text-microcopy text-muted"><?php echo htmlspecialchars($linha['whatsapp'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <span class="font-tabular text-secondary"><?php echo htmlspecialchars($linha['cpf_responsavel'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($linha['erros'])): ?>
                                        <div class="text-danger text-microcopy">
                                            <?php foreach ($linha['erros'] as $err): ?>
                                                <div>&bull; <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php elseif (!empty($linha['avisos'])): ?>
                                        <div class="text-warning text-microcopy" style="color: #92400e !important;">
                                            <?php foreach ($linha['avisos'] as $av): ?>
                                                <div>&bull; <?php echo htmlspecialchars($av, ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted text-microcopy">Dados validados com sucesso.</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-enterprise-footer d-flex justify-content-between align-items-center p-3 border-top">
            <span class="text-microcopy text-muted">Total de registros analisados: <?php echo $previa['total_linhas']; ?></span>
            <div class="d-flex gap-2">
                <form action="<?php echo $baseUrl; ?>/admin/importacao-escolar/cancelar" method="POST" class="d-inline m-0">
                    <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                    <button type="submit" class="btn btn-enterprise btn-secondary">
                        Cancelar Prévia
                    </button>
                </form>
                <?php if ($previa['linhas_validas'] > 0): ?>
                    <form action="<?php echo $baseUrl; ?>/admin/importacao-escolar/processar" method="POST" class="d-inline m-0">
                        <?php if (!empty($csrf_field)) echo $csrf_field; ?>
                        <button type="submit" class="btn btn-enterprise btn-primary">
                            Confirmar e Processar Importação
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
