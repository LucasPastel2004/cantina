<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Agendamento Semanal de Lanches pelo Responsável
 * 
 * Conforme docs/FSD.md (Área 1, Tela 2.3, Fluxo 3, RN-01 a RN-06, RN-11 a RN-17),
 * docs/DESIGN.md e skill design-taste-frontend
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
$csrfToken = \App\Core\Session::generateCsrfToken();
?>

<style>
/* Remove terminantemente qualquer seta ou spinner nativo do navegador */
.input-qtd::-webkit-outer-spin-button,
.input-qtd::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
    display: none !important;
}
.input-qtd {
    -moz-appearance: textfield !important;
    appearance: textfield !important;
    text-align: center !important;
}
</style>

<div class="agendamento-container">
    <!-- Cabeçalho Executivo -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between pb-3 mb-4 border-bottom border-subtle gap-3">
        <div>
            <h1 class="page-title mb-1">Agendamento Semanal de Lanches</h1>
            <p class="text-secondary mb-0" style="font-size: 13px;">
                Selecione o dependente, o dia letivo desejado e monte a comanda diária de lanche escolar.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="btn btn-enterprise btn-secondary btn-sm">
                Meus Pedidos
            </a>
            <a href="<?php echo $baseUrl; ?>/portal/carteira" class="btn btn-enterprise btn-secondary btn-sm">
                <span class="text-secondary" style="font-size: 11px;">Saldo Livre:</span>
                <strong class="font-tabular ms-1 text-dark" style="font-size: 12px; color: #0f172a;">R$ <?php echo number_format($saldoDisponivel, 2, ',', '.'); ?></strong>
            </a>
        </div>
    </div>

    <!-- Seletor de Dependente (se houver mais de 1) -->
    <?php if (count($dependentes) > 1): ?>
        <div class="card-enterprise p-3 mb-4">
            <label class="form-label text-secondary mb-2" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                Selecione o Aluno Dependente
            </label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($dependentes as $dep): ?>
                    <?php 
                    $isSel = ((int)$dep['id'] === (int)$alunoSelecionado['id']); 
                    $turnoLabel = ($dep['turno'] === 'matutino') ? 'Manhã (Corte 08:00)' : 'Tarde (Corte 13:30)';
                    ?>
                    <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $dep['id']; ?>&semana=<?php echo $semanaRef; ?>&data=<?php echo $diaSelecionado['data']; ?>" 
                       class="btn btn-enterprise btn-secondary btn-sm d-flex align-items-center gap-2"
                       style="<?php echo $isSel ? 'background: #f8fafc; border-color: #475569; color: #0f172a; font-weight: 600;' : 'background: #ffffff; border-color: #e2e8f0; color: #475569;'; ?>">
                        <span class="bullet" style="color: <?php echo $isSel ? '#0f172a' : '#cbd5e1'; ?>; font-size: 10px;">●</span>
                        <span><?php echo htmlspecialchars($dep['nome'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="text-secondary" style="font-size: 11px; font-weight: 400;">(<?php echo htmlspecialchars($dep['nome_turma'] ?? $dep['serie'] ?? '', ENT_QUOTES, 'UTF-8'); ?> &bull; <?php echo $turnoLabel; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Identificação do Aluno Ativo e Turno -->
    <div class="card-enterprise p-3 mb-4 bg-light border-subtle">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-circle" style="width: 40px; height: 40px; border-radius: 50%; background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; border: 1px solid #cbd5e1;">
                    <?php echo mb_substr($alunoSelecionado['nome'], 0, 1, 'UTF-8'); ?>
                </div>
                <div>
                    <h2 class="mb-0" style="font-size: 15px; font-weight: 600; color: #0f172a;">
                        <?php echo htmlspecialchars($alunoSelecionado['nome'], ENT_QUOTES, 'UTF-8'); ?>
                    </h2>
                    <div class="text-secondary" style="font-size: 12px;">
                        Matrícula: <span class="font-tabular text-dark fw-medium"><?php echo htmlspecialchars($alunoSelecionado['matricula'], ENT_QUOTES, 'UTF-8'); ?></span>
                        &bull; Turma: <span class="text-dark fw-medium"><?php echo htmlspecialchars($alunoSelecionado['nome_turma'] ?? 'Turma Regular', ENT_QUOTES, 'UTF-8'); ?></span>
                        &bull; Turno: <code class="code-tag text-uppercase font-tabular" style="font-size: 11px;"><?php echo htmlspecialchars($alunoSelecionado['turno'], ENT_QUOTES, 'UTF-8'); ?></code>
                    </div>
                </div>
            </div>

            <!-- Alternador de Semana (Semana Atual vs Próxima Semana) -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary" style="font-size: 12px;">Semana:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $alunoSelecionado['id']; ?>&semana=atual" 
                       class="btn btn-enterprise btn-secondary"
                       style="<?php echo ($semanaRef === 'atual') ? 'background: #f8fafc; border-color: #475569; color: #0f172a; font-weight: 600;' : 'background: #ffffff; border-color: #e2e8f0; color: #475569;'; ?>">
                        Semana Corrente
                    </a>
                    <?php if ($semanaSeguinteLiberada): ?>
                        <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $alunoSelecionado['id']; ?>&semana=seguinte" 
                           class="btn btn-enterprise btn-secondary"
                           style="<?php echo ($semanaRef === 'seguinte') ? 'background: #f8fafc; border-color: #475569; color: #0f172a; font-weight: 600;' : 'background: #ffffff; border-color: #e2e8f0; color: #475569;'; ?>">
                            Próxima Semana
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-enterprise btn-secondary disabled" title="Abre sexta-feira às 18:00" style="opacity: 0.6; cursor: not-allowed;">
                            Próxima Semana <span class="text-muted" style="font-size: 10px;">(Sex 18h)</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navegador dos 5 Dias Letivos da Semana -->
    <div class="row g-2 mb-4">
        <?php foreach ($diasSemana as $dia): ?>
            <?php 
            $isDiaSel = ($dia['data'] === $diaSelecionado['data']);
            $corte = $dia['status_corte'];
            $podePedir = ($dia['is_letivo'] && $corte['pode_alterar']);
            ?>
            <div class="col">
                <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $alunoSelecionado['id']; ?>&semana=<?php echo $semanaRef; ?>&data=<?php echo $dia['data']; ?>" 
                   class="card-enterprise h-100 p-2 text-decoration-none d-block transition-all"
                   style="border-radius: 6px; <?php echo $isDiaSel ? 'border: 2px solid #475569; background: #f8fafc;' : 'border: 1px solid #e2e8f0; background: #fff;'; ?> <?php echo !$podePedir ? 'opacity: 0.75;' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-secondary" style="font-size: 11px; font-weight: 600; text-transform: uppercase;">
                            <?php echo $dia['dia_semana_abrev']; ?>
                        </span>
                        <?php if ($dia['is_hoje']): ?>
                            <span class="code-tag font-tabular" style="font-size: 9px; padding: 1px 4px; font-weight: 600; color: #334155;">HOJE</span>
                        <?php endif; ?>
                    </div>
                    <div class="font-tabular mb-1" style="font-size: 16px; font-weight: 700; color: #0f172a;">
                        <?php echo $dia['dia_mes']; ?>
                    </div>
                    
                    <!-- Indicador de Status do Dia -->
                    <div style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?php if (!$dia['is_letivo']): ?>
                            <span class="text-secondary">&bull; <?php echo htmlspecialchars($dia['descricao'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php elseif ($corte['pode_alterar']): ?>
                            <?php if ($dia['is_hoje'] && $corte['minutos_restantes'] !== null && $corte['minutos_restantes'] <= 60): ?>
                                <span class="text-secondary fw-semibold">&bull; Restam <?php echo $corte['minutos_restantes']; ?>m</span>
                            <?php else: ?>
                                <span class="text-secondary">&bull; Aberto</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">&bull; Fechado</span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Seção de Alerta de Corte da Data Selecionada -->
    <div class="card-enterprise p-3 mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3" style="border-left: 4px solid #64748b;">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h3 class="mb-0" style="font-size: 15px; font-weight: 600; color: #0f172a;">
                    Cardápio para <?php echo $diaSelecionado['dia_semana_nome']; ?>, <?php echo $diaSelecionado['data_formatada']; ?>
                </h3>
                <span class="status-indicator" style="font-size: 12px; color: #334155;">
                    <span class="bullet" style="color: #64748b;">●</span> <?php echo htmlspecialchars($statusCorte['rotulo'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </div>
            <p class="text-secondary mb-0" style="font-size: 12px;">
                <?php echo $statusCorte['mensagem']; ?> 
                &bull; Turno do aluno: <strong><?php echo ucfirst($alunoSelecionado['turno']); ?></strong> 
                (horário rígido de corte às <strong><?php echo $statusCorte['hora_corte']; ?></strong>).
            </p>
        </div>
        <div>
            <span class="font-tabular text-secondary" style="font-size: 12px;">
                Data de Consumo: <strong><?php echo $diaSelecionado['data']; ?></strong>
            </span>
        </div>
    </div>

    <!-- Cenário 1: Dia Não Letivo (Feriado / Recesso) -->
    <?php if (!$diaSelecionado['is_letivo']): ?>
        <div class="card-enterprise p-5 text-center my-4">
            <div class="mb-3 text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-calendar-x" viewBox="0 0 16 16">
                    <path d="M6.146 7.146a.5.5 0 0 1 .708 0L8 8.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 9l1.147 1.146a.5.5 0 0 1-.708.708L8 9.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 9 6.146 7.854a.5.5 0 0 1 0-.708"/>
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                </svg>
            </div>
            <h4 class="fw-semibold mb-1" style="font-size: 16px;">Dia Sem Atividades Escolares</h4>
            <p class="text-secondary mb-3" style="font-size: 13px;">
                Esta data é classificada como <strong><?php echo htmlspecialchars($diaSelecionado['descricao'], ENT_QUOTES, 'UTF-8'); ?></strong>. Não haverá funcionamento da cantina.
            </p>
            <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $alunoSelecionado['id']; ?>&semana=<?php echo $semanaRef; ?>" class="btn btn-enterprise btn-secondary btn-sm">
                Selecionar Outro Dia Letivo
            </a>
        </div>

    <!-- Cenário 2: Horário de Corte Fechado ou Dia Passado -->
    <?php elseif (!$statusCorte['pode_alterar']): ?>
        <div class="card-enterprise p-5 text-center my-4">
            <div class="mb-3 text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                    <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.084-.51l.995.106a8 8 0 0 1-.119.764l-.96-.36"/>
                    <path d="M7 3a1 1 0 0 0-1 1v4.586l3.293 3.293a1 1 0 0 0 1.414-1.414L8 7.586V4a1 1 0 0 0-1-1"/>
                </svg>
            </div>
            <h4 class="fw-semibold mb-1" style="font-size: 16px;">Corte Operacional Encerrado</h4>
            <p class="text-secondary mb-3" style="font-size: 13px; max-width: 500px; margin: 0 auto;">
                O horário limite de compras para o turno <?php echo $alunoSelecionado['turno']; ?> (<?php echo $statusCorte['hora_corte']; ?>) foi atingido. Novos agendamentos para esta data foram finalizados.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="btn btn-enterprise btn-primary btn-sm">
                    Consultar Meus Pedidos
                </a>
                <a href="<?php echo $baseUrl; ?>/portal/agendamento?aluno=<?php echo $alunoSelecionado['id']; ?>&semana=<?php echo $semanaRef; ?>" class="btn btn-enterprise btn-secondary btn-sm">
                    Ver Outras Datas
                </a>
            </div>
        </div>

    <!-- Cenário 3: Já Existe Pedido Ativo para Esta Data -->
    <?php elseif ($pedidoExistente): ?>
        <div class="card-enterprise p-4 mb-4 border-subtle bg-light">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width: 42px; height: 42px; border-radius: 6px; background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="mb-1" style="font-size: 15px; font-weight: 600; color: #0f172a;">
                            Já existe um agendamento confirmado para esta data!
                        </h4>
                        <div class="text-secondary" style="font-size: 13px;">
                            Pedido <code class="code-tag font-tabular"><?php echo $pedidoExistente['codigo_pedido']; ?></code> 
                            &bull; Total: <strong class="font-tabular text-dark">R$ <?php echo number_format($pedidoExistente['valor_total'], 2, ',', '.'); ?></strong>
                            &bull; Status: <code class="code-tag text-uppercase font-tabular" style="font-size: 11px;"><?php echo htmlspecialchars($pedidoExistente['status'], ENT_QUOTES, 'UTF-8'); ?></code>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo $baseUrl; ?>/portal/pedidos/editar/<?php echo $pedidoExistente['id']; ?>" class="btn btn-enterprise btn-primary btn-sm">
                        Editar Itens do Pedido
                    </a>
                    <a href="<?php echo $baseUrl; ?>/portal/pedidos" class="btn btn-enterprise btn-secondary btn-sm">
                        Ver em Meus Pedidos
                    </a>
                </div>
            </div>
        </div>

    <!-- Cenário 4: Agendamento Aberto — Exibição do Catálogo e Comanda -->
    <?php else: ?>
        <form action="<?php echo $baseUrl; ?>/portal/agendamento/salvar" method="POST" id="form-agendamento">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="aluno_id" value="<?php echo $alunoSelecionado['id']; ?>">
            <input type="hidden" name="data_consumo" value="<?php echo $diaSelecionado['data']; ?>">

            <div class="row g-4">
                <!-- Coluna Principal: Grade de Produtos por Categoria -->
                <div class="col-12 col-lg-8">
                    <?php if (empty($produtosPorCategoria)): ?>
                        <div class="card-enterprise p-5 text-center">
                            <p class="text-secondary mb-0" style="font-size: 13px;">
                                Não há itens cadastrados para o turno <?php echo $alunoSelecionado['turno']; ?> no momento.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($produtosPorCategoria as $categoriaNome => $produtosCat): ?>
                            <div class="card-enterprise p-3 p-md-4 mb-4">
                                <h3 class="card-title pb-2 mb-3 border-bottom border-subtle d-flex justify-content-between align-items-center" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: #475569;">
                                    <span><?php echo htmlspecialchars($categoriaNome, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="badge bg-light text-secondary border font-tabular" style="font-size: 11px;">
                                        <?php echo count($produtosCat); ?> itens
                                    </span>
                                </h3>

                                <div class="row g-3">
                                    <?php foreach ($produtosCat as $prod): ?>
                                        <?php 
                                        $esgotado = (bool)$prod['esgotado']; 
                                        $temConflito = (bool)$prod['tem_conflito_alergia'];
                                        $conflitosStr = implode(', ', $prod['conflitos_nomes']);
                                        ?>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 border rounded-3 h-100 d-flex flex-column justify-content-between transition-all product-card <?php echo $esgotado ? 'bg-light border-subtle' : 'bg-white'; ?>"
                                                 id="product-box-<?php echo $prod['id']; ?>"
                                                 style="<?php echo $esgotado ? 'opacity: 0.55; cursor: not-allowed;' : ''; ?>">
                                                
                                                <div>
                                                    <!-- Topo do Card de Produto -->
                                                    <div class="d-flex align-items-start gap-2 mb-2">
                                                        <!-- Miniatura / Thumbnail -->
                                                        <div class="product-thumb flex-shrink-0" style="width: 44px; height: 44px; border-radius: 6px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                                            <?php if (!empty($prod['foto_url'])): ?>
                                                                <img src="<?php echo $baseUrl; ?>/<?php echo htmlspecialchars($prod['foto_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                            <?php else: ?>
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#94a3b8" viewBox="0 0 16 16">
                                                                    <path d="M8 1a5 5 0 0 0-5 5v1h10V6a5 5 0 0 0-5-5m4 7H4c-.55 0-1 .45-1 1v4a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9c0-.55-.45-1-1-1"/>
                                                                </svg>
                                                            <?php endif; ?>
                                                        </div>

                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <h4 class="mb-0 text-dark fw-semibold" style="font-size: 13px; line-height: 1.3;">
                                                                    <?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>
                                                                </h4>
                                                            </div>
                                                            <div class="text-secondary mt-1" style="font-size: 11px;">
                                                                Código: <code class="code-tag font-tabular" style="font-size: 10px;"><?php echo htmlspecialchars($prod['codigo_interno'], ENT_QUOTES, 'UTF-8'); ?></code>
                                                                &bull; <?php echo ($prod['tipo'] === 'salgado_diario') ? 'Salgado Diário' : 'Industrializado'; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Tags de Alérgenos -->
                                                    <?php if ($temConflito): ?>
                                                        <div class="mb-2 p-1 px-2 rounded" style="background: #fffbeb; border: 1px solid #fde68a; font-size: 11px; color: #92400e;">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-exclamation-triangle-fill me-1" viewBox="0 0 16 16">
                                                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                                                            </svg>
                                                            <strong>Contém:</strong> <?php echo htmlspecialchars($conflitosStr, ENT_QUOTES, 'UTF-8'); ?> (incompatível)
                                                        </div>
                                                    <?php elseif (!empty($prod['alergenos'])): ?>
                                                        <div class="text-muted mb-2" style="font-size: 10px;">
                                                            Alérgenos: <?php echo htmlspecialchars(implode(', ', array_column($prod['alergenos'], 'nome')), ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Rodapé do Card: Preço e Seletor de Quantidade -->
                                                <div class="d-flex align-items-center justify-content-between pt-2 border-top border-subtle mt-2">
                                                    <div class="font-tabular" style="font-size: 15px; font-weight: 700; color: #0f172a;">
                                                        R$ <?php echo number_format($prod['preco_venda'], 2, ',', '.'); ?>
                                                    </div>

                                                    <?php if ($esgotado): ?>
                                                        <code class="code-tag text-uppercase font-tabular" style="font-size: 10px; color: #64748b;">Esgotado</code>
                                                    <?php else: ?>
                                                        <!-- Controles de Quantidade -->
                                                        <div class="d-flex align-items-center gap-1">
                                                            <button type="button" 
                                                                    class="btn btn-enterprise btn-secondary btn-sm btn-qtd-diminuir" 
                                                                    data-id="<?php echo $prod['id']; ?>"
                                                                    style="width: 28px; height: 28px; padding: 0;">-</button>
                                                            
                                                            <input type="text" 
                                                                   inputmode="numeric" 
                                                                   pattern="[0-9]*" 
                                                                   name="itens[<?php echo $prod['id']; ?>]" 
                                                                   id="qtd-<?php echo $prod['id']; ?>" 
                                                                   value="0" 
                                                                   min="0" 
                                                                   max="<?php echo ($prod['tipo'] === 'industrializado_continuo') ? $prod['estoque_disponivel'] : 10; ?>" 
                                                                   class="form-control form-control-sm text-center font-tabular input-qtd" 
                                                                   style="width: 44px; height: 28px; font-weight: 600; padding: 0 4px; text-align: center; -moz-appearance: textfield; appearance: textfield;"
                                                                   data-id="<?php echo $prod['id']; ?>"
                                                                   data-nome="<?php echo htmlspecialchars($prod['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                                                                   data-preco="<?php echo $prod['preco_venda']; ?>"
                                                                   data-conflito="<?php echo $temConflito ? '1' : '0'; ?>"
                                                                   data-conflitos-msg="<?php echo htmlspecialchars($conflitosStr, ENT_QUOTES, 'UTF-8'); ?>"
                                                                   readonly>

                                                            <button type="button" 
                                                                    class="btn btn-enterprise btn-secondary btn-sm btn-qtd-aumentar" 
                                                                    data-id="<?php echo $prod['id']; ?>"
                                                                    style="width: 28px; height: 28px; padding: 0;">+</button>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Coluna Lateral: Comanda do Pedido / Resumo Financeiro -->
                <div class="col-12 col-lg-4">
                    <div class="card-enterprise p-3 p-md-4 sticky-top" style="top: 20px; z-index: 10;">
                        <h3 class="card-title pb-2 mb-3 border-bottom border-subtle d-flex align-items-center justify-content-between" style="font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; color: #475569;">
                            <span>Comanda do Lanche</span>
                            <span class="badge bg-light text-secondary border font-tabular" id="badge-itens-count" style="font-size: 11px;">0 itens</span>
                        </h3>

                        <div class="mb-3">
                            <div class="text-secondary" style="font-size: 12px;">Aluno:</div>
                            <div class="fw-semibold text-dark" style="font-size: 13px;"><?php echo htmlspecialchars($alunoSelecionado['nome'], ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-secondary mt-1" style="font-size: 12px;">Data de Consumo:</div>
                            <div class="font-tabular fw-semibold text-dark" style="font-size: 13px;"><?php echo $diaSelecionado['dia_semana_nome']; ?>, <?php echo $diaSelecionado['data_formatada']; ?></div>
                        </div>

                        <!-- Lista de Itens Adicionados -->
                        <div class="border rounded p-2 mb-3 bg-light" id="comanda-itens-container" style="min-height: 120px; max-height: 250px; overflow-y: auto;">
                            <div class="text-center text-muted py-4" id="comanda-vazia-msg" style="font-size: 12px;">
                                Nenhum lanche adicionado ainda. Escolha os itens ao lado.
                            </div>
                            <ul class="list-unstyled mb-0 d-none" id="comanda-lista">
                                <!-- Preenchido dinamicamente via JS -->
                            </ul>
                        </div>

                        <!-- Resumo Financeiro -->
                        <div class="pt-2 border-top border-subtle mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1 text-secondary" style="font-size: 12px;">
                                <span>Saldo Livre Disponível:</span>
                                <span class="font-tabular fw-medium text-dark">R$ <?php echo number_format($saldoDisponivel, 2, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 16px; font-weight: 700; color: #0f172a;">
                                <span>Total do Pedido:</span>
                                <span class="font-tabular text-dark" style="color: #0f172a;" id="comanda-total-display">R$ 0,00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center text-secondary" style="font-size: 11px;">
                                <span>Saldo após o pedido:</span>
                                <span class="font-tabular fw-medium" id="comanda-saldo-restante">R$ <?php echo number_format($saldoDisponivel, 2, ',', '.'); ?></span>
                            </div>
                        </div>

                        <!-- Alerta se o Saldo for Insuficiente -->
                        <div class="alert alert-danger p-2 mb-3 d-none" id="alerta-saldo-insuficiente" style="font-size: 11px;">
                            <strong>Saldo Insuficiente:</strong> O valor do pedido excede seu saldo disponível livre. 
                            <a href="<?php echo $baseUrl; ?>/portal/carteira" class="fw-semibold text-danger text-decoration-underline">Recarregue via Pix</a> para continuar.
                        </div>

                        <!-- Botão de Finalização -->
                        <button type="submit" 
                                class="btn btn-enterprise btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2" 
                                id="btn-concluir-pedido" 
                                disabled
                                style="font-size: 13px; font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check2-circle" viewBox="0 0 16 16">
                                <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0"/>
                                <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                            </svg>
                            Concluir Agendamento
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Modal de Advertência de Segurança Alimentar (RN-16 & FSD Tela 2.3) -->
<div class="modal fade" id="modal-alergia-advertencia" tabindex="-1" aria-labelledby="modalAlergiaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 8px;">
            <div class="modal-header border-0 pb-0" style="background: #fffbeb;">
                <div class="d-flex align-items-center gap-2 text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#d97706" class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg>
                    <h5 class="modal-title fw-bold" id="modalAlergiaLabel" style="font-size: 15px; color: #92400e;">
                        Aviso de Restrição Alimentar
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="mb-3 text-secondary" style="font-size: 13px; line-height: 1.5;" id="modal-alergia-texto">
                    <!-- Preenchido via JavaScript -->
                </p>
                <div class="p-2 rounded bg-light border text-muted font-tabular" style="font-size: 11px;">
                    O ticket térmico impresso conterá a indicação obrigatória de alérgenos para conferência visual na entrega.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-enterprise btn-secondary btn-sm" data-bs-dismiss="modal" id="btn-alergia-cancelar">
                    Cancelar
                </button>
                <button type="button" class="btn btn-enterprise btn-primary btn-sm" id="btn-alergia-confirmar" style="background: #d97706; border-color: #b45309;">
                    Confirmar Inclusão Consciente
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const saldoDisponivel = <?php echo (float)$saldoDisponivel; ?>;
    const nomeAluno = <?php echo json_encode($alunoSelecionado['nome']); ?>;

    const modalAlergiaEl = document.getElementById('modal-alergia-advertencia');
    const modalAlergia = modalAlergiaEl ? new bootstrap.Modal(modalAlergiaEl) : null;
    const modalTexto = document.getElementById('modal-alergia-texto');
    const btnAlergiaConfirmar = document.getElementById('btn-alergia-confirmar');

    let produtoPendente = null;

    // Utilitário para formatar moeda Real
    function formatarMoeda(valor) {
        return 'R$ ' + valor.toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    }

    // Recalcula totais da comanda
    function recalcularComanda() {
        const inputs = document.querySelectorAll('.input-qtd');
        let total = 0;
        let totalItens = 0;
        const itensSelecionados = [];

        inputs.forEach(input => {
            const qtd = parseInt(input.value) || 0;
            if (qtd > 0) {
                const id = input.dataset.id;
                const nome = input.dataset.nome;
                const preco = parseFloat(input.dataset.preco) || 0;
                const subtotal = qtd * preco;

                total += subtotal;
                totalItens += qtd;

                itensSelecionados.push({ id, nome, qtd, preco, subtotal });
            }
        });

        // Atualiza elementos visuais da comanda
        const comandaVaziaMsg = document.getElementById('comanda-vazia-msg');
        const comandaLista = document.getElementById('comanda-lista');
        const comandaTotalDisplay = document.getElementById('comanda-total-display');
        const comandaSaldoRestante = document.getElementById('comanda-saldo-restante');
        const badgeCount = document.getElementById('badge-itens-count');
        const alertaSaldo = document.getElementById('alerta-saldo-insuficiente');
        const btnConcluir = document.getElementById('btn-concluir-pedido');

        if (!comandaTotalDisplay) return;

        comandaTotalDisplay.textContent = formatarMoeda(total);
        badgeCount.textContent = totalItens + (totalItens === 1 ? ' item' : ' itens');

        const saldoRestante = saldoDisponivel - total;
        comandaSaldoRestante.textContent = formatarMoeda(Math.max(0, saldoRestante));

        if (saldoRestante < 0) {
            comandaSaldoRestante.classList.add('text-danger');
            comandaSaldoRestante.textContent = '-' + formatarMoeda(Math.abs(saldoRestante));
            alertaSaldo.classList.remove('d-none');
            btnConcluir.disabled = true;
        } else {
            comandaSaldoRestante.classList.remove('text-danger');
            alertaSaldo.classList.add('d-none');
            btnConcluir.disabled = (totalItens === 0);
        }

        // Renderiza lista de itens
        if (totalItens === 0) {
            comandaVaziaMsg.classList.remove('d-none');
            comandaLista.classList.add('d-none');
            comandaLista.innerHTML = '';
        } else {
            comandaVaziaMsg.classList.add('d-none');
            comandaLista.classList.remove('d-none');
            
            let html = '';
            itensSelecionados.forEach(it => {
                html += `
                    <li class="d-flex justify-content-between align-items-center py-1 border-bottom border-subtle" style="font-size: 12px;">
                        <div>
                            <strong>${it.qtd}x</strong> ${it.nome}
                        </div>
                        <span class="font-tabular fw-semibold text-dark">${formatarMoeda(it.subtotal)}</span>
                    </li>
                `;
            });
            comandaLista.innerHTML = html;
        }
    }

    // Manipuladores de botões + e -
    document.querySelectorAll('.btn-qtd-aumentar').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const input = document.getElementById('qtd-' + id);
            if (!input) return;

            const max = parseInt(input.getAttribute('max')) || 99;
            let current = parseInt(input.value) || 0;

            if (current >= max) {
                alert('Limite máximo disponível atingido para este item.');
                return;
            }

            // Checagem preventiva de alérgenos se estiver adicionando o primeiro item
            if (current === 0 && input.dataset.conflito === '1') {
                produtoPendente = input;
                const conflitos = input.dataset.conflitosMsg || 'ingredientes restritos';
                modalTexto.innerHTML = `Atenção: Este produto contém <strong>${conflitos}</strong>, incompatível com o cadastro de <strong>${nomeAluno}</strong>. Deseja realmente incluir este item?`;
                modalAlergia.show();
                return;
            }

            input.value = current + 1;
            recalcularComanda();
        });
    });

    document.querySelectorAll('.btn-qtd-diminuir').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const input = document.getElementById('qtd-' + id);
            if (!input) return;

            let current = parseInt(input.value) || 0;
            if (current > 0) {
                input.value = current - 1;
                recalcularComanda();
            }
        });
    });

    // Confirmação consciente de alérgeno
    if (btnAlergiaConfirmar) {
        btnAlergiaConfirmar.addEventListener('click', function() {
            if (produtoPendente) {
                produtoPendente.value = 1;
                produtoPendente = null;
                recalcularComanda();
            }
            modalAlergia.hide();
        });
    }

    // Inicializa comanda
    recalcularComanda();
});
</script>
