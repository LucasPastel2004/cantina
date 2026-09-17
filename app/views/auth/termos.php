<?php
/**
 * Cantina Web — Colégio Monteiro Lobato
 * View: Termos de Uso e Políticas da Cantina Escolar
 * 
 * Conforme docs/FSD.md e docs/DESIGN.md
 */

defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

$baseUrl = $baseUrl ?? '';
?>

<div class="auth-header-block text-center mb-4">
    <h2 class="h5 font-weight-bold text-dark mb-1">Termos e Políticas da Cantina</h2>
    <span class="text-secondary text-microcopy">Colégio Monteiro Lobato &bull; Unidade de Ensino</span>
</div>

<div class="termos-content text-left p-3 bg-white border mb-4" style="max-height: 400px; overflow-y: auto; font-size: 13px; line-height: 1.5; color: #334155; border-radius: 4px;">
    <h6 class="font-weight-bold text-dark mb-2">1. Objeto e Funcionamento da Carteira Familiar</h6>
    <p>
        O sistema Cantina Web disponibiliza aos pais e responsáveis legais uma carteira virtual única compartilhada entre os dependentes matriculados no Colégio Monteiro Lobato, utilizada para recargas financeiras, agendamentos semanais de lanches e consulta de extrato analítico de consumo.
    </p>

    <h6 class="font-weight-bold text-dark mb-2">2. Recargas e Meios de Pagamento</h6>
    <p>
        As recargas na carteira familiar são efetuadas preferencialmente via Pix dinâmico com compensação imediata e sem cobrança de taxas de conveniência ao responsável, com valor mínimo de R$ 10,00 por transação. Também é admitida recarga em espécie diretamente no balcão da cantina durante os horários com sessão de caixa aberta.
    </p>

    <h6 class="font-weight-bold text-dark mb-2">3. Horários Rígidos de Corte e Cancelamento</h6>
    <p>
        Para assegurar a produção sob medida e evitar desperdício de alimentos, o sistema opera com travas irrevogáveis de corte:
    </p>
    <ul>
        <li><strong>Turno Matutino:</strong> Novos agendamentos, edições ou cancelamentos de pedidos podem ser realizados até pontualmente as <strong>08:00</strong> do dia do consumo.</li>
        <li><strong>Turno Vespertino:</strong> Novos agendamentos, edições ou cancelamentos de pedidos podem ser realizados até pontualmente as <strong>13:30</strong> do dia do consumo.</li>
    </ul>
    <p>
        Cancelamentos efetuados antes do horário de corte restituem 100% do valor imediatamente à carteira virtual da família. Após o horário de corte, os itens entram na esteira de produção e o valor é faturado normalmente.
    </p>

    <h6 class="font-weight-bold text-dark mb-2">4. Retirada de Lanches e Tickets Térmicos</h6>
    <p>
        A entrega do lanche ocorre durante os intervalos das aulas exclusivamente mediante a apresentação e descarte do ticket físico emitido pela cantina e entregue previamente aos alunos em sala de aula. Não são operados leitores ópticos ou computadores na fila do recreio.
    </p>

    <h6 class="font-weight-bold text-dark mb-2">5. Segurança Alimentar e Alergias</h6>
    <p>
        É dever exclusivo do responsável cadastrar com veracidade as restrições alimentares e alergias do dependente no Portal da Família. O sistema emitirá avisos visuais ao tentar agendar alimentos contendo alérgenos registrados, exigindo confirmação consciente do responsável.
    </p>

    <h6 class="font-weight-bold text-dark mb-2">6. Mecanismo Especial de Devolução (MED) e Contestações Pix</h6>
    <p>
        Caso uma transação de recarga via Pix seja contestada indevidamente junto à instituição bancária via MED, o valor contestado será debitado de imediato da carteira familiar. Se o saldo resultante ficar negativo, a conta e todos os dependentes serão preventivamente suspensos para novos agendamentos até regularização financeira junto à administração da cantina.
    </p>

    <h6 class="font-weight-bold text-dark mb-2">7. Saldo Residual</h6>
    <p>
        Saldos remanescentes ao término do ano letivo permanecem vinculados ao responsável para o ano seguinte. Em caso de transferência escolar ou desligamento do aluno, o resgate do saldo residual pode ser solicitado administrativamente junto à gerência da cantina.
    </p>
</div>

<div class="text-center">
    <a href="javascript:history.back()" class="btn btn-enterprise btn-secondary py-2 px-4">
        &larr; Voltar
    </a>
</div>
