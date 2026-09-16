# DOCUMENTO DE REQUISITOS DO PRODUTO (PRD)

---

## 1. Visão Geral do Produto

O **Cantina Web** (nome provisório, passível de alteração futura) é uma plataforma web desenvolvida para modernizar, organizar e garantir segurança financeira e operacional à cantina de um colégio particular de unidade única.

O sistema estrutura-se em duas frentes integradas:
1. **Portal da Família:** ambiente onde pais e responsáveis acessam uma carteira virtual única da família, cadastram restrições alimentares de cada filho, realizam recargas via Pix e agendam antecipadamente os lanches da semana.
2. **Painel Operacional e Administrativo da Cantina:** ambiente interno utilizado pela equipe da cantina para consolidar compras diárias de salgados com fornecedores, imprimir lotes de tickets térmicos organizados por turma, gerenciar estoque físico (com suporte à importação de XML de notas fiscais), registrar entradas de fornecedores, operar vendas avulsas de balcão, controlar o consumo de colaboradores com desconto em folha e realizar o fechamento diário de caixa por turno.

* **Público principal:** Pais e responsáveis pelos alunos, atendentes e operadores de balcão, gestores da cantina e professores/colaboradores da instituição escolar.
* **Principal benefício esperado:** Eliminação de filas e tumultos nos intervalos por meio da entrega rápida baseada em tickets de papel; exatidão na compra diária de salgados com fornecedores (redução de desperdícios); fim do dinheiro físico nas mãos de crianças; rastreabilidade de estoque; e controle integral dos pais sobre os gastos e a segurança alimentar dos filhos.
* **Contexto geral de uso:** Ambiente escolar privado de turno duplo (matutino e vespertino), operando em terminal único de balcão e acessível externamente via web pelos responsáveis.

---

## 2. Problema que o Sistema Resolve

Nas cantinas escolares tradicionais, o intervalo concentra centenas de estudantes em uma janela de aproximadamente 20 minutos, gerando:

* **Filas extensas e tempo perdido:** O manuseio de notas, moedas, conferência de troco e indecisão dos alunos consome o tempo de recreio e atrasa o retorno para a sala de aula.
* **Falta de controle alimentar pelos pais:** Pais entregam dinheiro aos filhos sem saber se o valor foi gasto em itens inadequados ou contendo alérgenos (leite, glúten, castanhas), colocando em risco crianças com restrições alimentares.
* **Prejuízos com compras incorretas de salgados:** Sem saber a demanda exata do dia, a cantina compra salgados por estimativa. Isso resulta em sobras descartadas ao fim do dia (custo financeiro) ou falta de lanches no meio do intervalo (perda de vendas).
* **Falta de controle no consumo de professores ("Caderninho de Fiado"):** Registros manuais de consumo de docentes geram esquecimentos, perdas financeiras e retrabalho na consolidação mensal para o setor de Recursos Humanos.
* **Contestações e fraudes em pagamentos:** Riscos operacionais com comprovantes falsos de Pix ou cancelamentos bancários posteriores sem mecanismo de bloqueio ou ajuste de saldo.
* **Dificuldade na prestação de contas:** Falta de separação entre o dinheiro recebido por adiantamento de saldo (recargas) e a venda de mercadorias no balcão, além de ausência de extratos analíticos para atendimento a famílias com dúvidas.

O **Cantina Web** resolve essas dores antecipando a escolha e o pagamento dos lanches, automatizando o cálculo de compras com o fornecedor, entregando os alimentos via tickets físicos organizados por turma e registrando com precisão cada centavo e item movimentado.

---

## 3. Objetivos do Sistema

### Objetivo principal
Digitalizar, antecipar e auditar todo o processo de encomenda, pagamento e entrega de lanches em uma cantina escolar particular, garantindo atendimento imediato no balcão por tickets, previsibilidade exata de compras e controle financeiro e nutricional para as famílias.

### Objetivos específicos
* **Viabilizar encomendas com horário de corte rígido:** Permitir agendamentos da semana corrente até as 08:00 (turno matutino) e até as 13:30 (turno vespertino).
* **Automatizar a consolidação de compras:** Totalizar a quantidade exata de salgados do turno pontualmente nos horários de corte, fornecendo exportação rápida para WhatsApp e relatório em PDF para o fornecedor.
* **Logística de entrega por tickets térmicos (80mm):** Agrupar e imprimir tickets por série e turma para distribuição prévia pelos professores, viabilizando conferência 100% visual e descarte no balcão.
* **Carteira familiar com Pix dinâmico integrado:** Habilitar recarga mínima de R$ 10,00 sem repasse de tarifas aos pais, com liberação automática de saldo e notificação em tempo real via WhatsApp.
* **Blindagem contra fraudes em Pix (Mecanismo MED):** Detectar contestações bancárias, debitar o valor da carteira, suspender novos pedidos em caso de saldo negativo e alertar os pais via WhatsApp.
* **Segurança alimentar ativa:** Notificar pais sobre a presença de alérgenos cadastrados no ato do pedido online, imprimir avisos destacados nos tickets e alertar o atendente no PDV em vendas avulsas identificadas.
* **Controle rigoroso de sessões de caixa:** Garantir abertura com fundo de troco e fechamento independente dos caixas da manhã (fechamento às 11:00) e da tarde (fechamento às 16:00).
* **Gestão de consumo de colaboradores:** Registrar o consumo presencial de professores com a opção "Desconto em Folha", gerando relatório mensal formatado para o RH da escola.
* **Agilidade na entrada de estoque industrializado:** Permitir upload de arquivos XML de notas fiscais com tela de conferência e conversão de embalagens em unidades de venda.

---

## 4. Personas e Perfis de Usuário

| Perfil | Descrição simples | Principais ações no sistema | Permissões básicas |
| --- | --- | --- | --- |
| **Responsável (Família)** | Pai, mãe ou tutor legal com dependentes matriculados no colégio. | Realizar primeiro acesso validando CPF e WhatsApp; aceitar termos de uso; gerenciar restrições alimentares dos dependentes; recarregar saldo via Pix; agendar, editar ou cancelar pedidos até o horário de corte; acompanhar extrato detalhado de consumo e saldo. | Acesso restrito aos dados de seus próprios dependentes e carteira familiar. Sem permissão de acesso a módulos operacionais, estoques ou cadastros gerais. |
| **Operador / Atendente** | Funcionário da cantina encarregado da rotina diária de balcão e atendimento. | Acessar o sistema com usuário (`operador-****`) e senha; abrir caixa com fundo de troco; emitir lista de compras para fornecedores; confirmar recebimento físico de salgados; imprimir lotes de tickets; reimprimir 2ª via de tickets; operar PDV para vendas avulsas e desconto em folha; registrar perdas com motivo obrigatório; transferir sobras de encomendas não retiradas para venda avulsa; e fechar o caixa do turno. | Acesso às telas de tickets, lista do fornecedor, PDV de balcão, extrato de consumo de alunos, lançamentos de perdas e fechamento do caixa do próprio turno. Não acessa configurações da empresa, relatórios gerenciais globais nem edição de cadastros mestres. |
| **Administrador / Gerente** | Proprietário da cantina ou gerente com responsabilidade total pelo negócio. | Executa todas as funções do Operador (acumulando o papel de atendente no balcão) e possui acesso exclusivo a: importação de planilhas de turmas/alunos (Excel/CSV); cadastro e alteração de produtos e preços; botão de pausa rápida de itens; entrada de estoque via XML; configuração de estoque mínimo; gestão do calendário escolar e cancelamentos em lote; ajuste manual de saldos e quitação de devedores; relatórios gerenciais e de auditoria; e configuração de dados institucionais e logotipo. | Acesso irrestrito a todas as interfaces, módulos, configurações, auditorias e relatórios do sistema. |
| **Professor / Colaborador** | Docente ou funcionário da escola que consome no balcão da cantina. | Não possui login próprio no sistema na V1. Apresenta-se presencialmente no balcão informando nome ou matrícula para lançamento de lanches na modalidade "Desconto em Folha". | Perfil passivo no sistema; seus registros são gerenciados pela cantina para apuração mensal com a secretaria escolar. |

---

## 5. Escopo da Primeira Versão

### Área Funcional 1: Portal da Família e Carteira Digital
* **Primeiro Acesso e Autenticação:** Acesso por CPF e senha. Fluxo de primeiro acesso validado por código de 6 dígitos enviado via WhatsApp cadastrado na planilha escolar, com aceite obrigatório dos Termos e Regras da Cantina.
* **Recuperação de Acesso:** Envio de código temporário de redefinição de senha para o WhatsApp do responsável.
* **Carteira Digital Familiar Única:** Gestão de saldo único compartilhado entre todos os filhos da família, exibindo saldo atual, débitos de agendamentos e extrato analítico.
* **Recarga Online via Pix Dinâmico:** Geração de QR Code e código Copia e Cola com valor mínimo padrão de R$ 10,00 (configurável pelo Administrador). Saldo liberado no segundo da confirmação bancária sem taxas para o cliente, acompanhado de notificação automática de confirmação no WhatsApp.
* **Alerta Automático de Saldo Baixo:** Disparo de notificação via WhatsApp sempre que o saldo da carteira familiar for inferior a R$ 15,00, além de alerta visual destacado no portal.
* **Agendamento Semanal:** Calendário de compras liberado toda sexta-feira às 18:00 para os dias letivos da semana seguinte. Os pais escolhem a data, o filho e selecionam os produtos disponíveis para o turno do aluno (Matutino ou Vespertino).
* **Edição e Cancelamento Autônomo:** Edição ou cancelamento de itens agendados permitido livremente até as 08:00 (manhã) e 13:30 (tarde). Cancelamentos estornam 100% do valor no mesmo instante. Na edição de itens, substituições por produtos mais caros debitam a diferença da carteira; substituições por produtos mais baratos estornam a diferença imediatamente; e qualquer item removido libera instantaneamente o estoque reservado.
* **Gestão de Restrições Alimentares:** Cadastro de alérgenos pré-definidos (glúten, lactose, ovos, soja, amendoim, etc.) no perfil do aluno. A seleção de itens conflitantes no portal exibe um aviso em destaque na tela, exigindo confirmação consciente do responsável.

### Área Funcional 2: Gestão de Encomendas e Fornecedores
* **Horários de Corte Automáticos:** Bloqueio pontual e irrevogável de novos agendamentos, edições ou cancelamentos às 08:00 (matutino) e às 13:30 (vespertino).
* **Consolidação de Compras de Salgados:** Painel operacional que totaliza a quantidade demandada de cada salgado para o turno corrente.
* **Exportação para Fornecedor:** Botão "Copiar para WhatsApp" com mensagem pré-formatada e download do relatório consolidado em PDF.
* **Recebimento de Encomenda e Entrada no Estoque:** Tela de conferência da entrega física do fornecedor. Ao clicar em "Confirmar Recebimento", o sistema lança a entrada física dos salgados e liquida simultaneamente a demanda dos tickets impressos, deixando o saldo remanescente liberado para vendas avulsas.

### Área Funcional 3: Emissão e Operação de Tickets
* **Geração e Ordenação de Tickets:** Cupons térmicos (bobina 80mm) gerados para cada lanche encomendado, agrupados e ordenados sequencialmente por Série e Turma para distribuição aos professores.
* **Identificação do Ticket:** Impressão contendo Data de Consumo, Turno, Série/Turma, Nome Completo do Aluno, Nome do Produto em destaque e Tag de Alerta de Alérgeno (ex: `[ATENÇÃO: CONTÉM LACTOSE]`).
* **Consulta e Reimpressão de 2ª Via:** Tela de consulta rápida por turma/aluno para localizar pedidos do dia e emitir segunda via identificada como `[2ª VIA]`, gravando a operação no log de auditoria.

### Área Funcional 4: Ponto de Venda (PDV) e Operação de Balcão
* **Entrega Visual de Recreio:** Atendimento imediato baseado na entrega do ticket de papel pelo aluno e descarte imediato na lixeira do balcão, sem uso de computador durante a fila principal.
* **PDV para Vendas Avulsas (Exceções):** Comercialização presencial de itens sobressalentes do estoque físico e sobras de produção com pagamento em Dinheiro (com cálculo de troco) ou Pix no balcão.
* **Verificação Opcional de Restrições no PDV:** O operador pode realizar a venda de forma direta/anônima (sem identificação) para atendimento ultra-rápido; caso digite o nome ou turma do aluno antes de lançar o item, o sistema checa as restrições cadastradas e exibe alerta visual em caso de incompatibilidade.
* **Comprovante Opcional de Balcão:** Venda concluída na tela com botão rápido para emissão de cupom térmico somente mediante solicitação do cliente.
* **Consumo de Colaboradores (Desconto em Folha):** Lançamento de consumo para professores e funcionários via busca por nome ou matrícula, exibindo foto e cargo para confirmação visual antes da gravação.
* **Estorno de Balcão:** Cancelamento manual de tickets com estorno imediato do crédito na carteira da família em caso de falta de produto ou recusa de substituição, exigindo motivo pré-cadastrado e registro em log.
* **Gestão de Sobras de Alunos Faltantes:** Lanches não retirados até o término do intervalo permanecem faturados para a família, podendo ser transferidos para a venda avulsa pelo operador via "Aproveitamento de Sobra de Encomenda" ou baixados como "Sobra de Produção / Não Retirado" no módulo de perdas.

### Área Funcional 5: Gestão de Estoque e Compras
* **Controle de Produtos Contínuos (Industrializados):** Baixa automática da quantidade disponível no ato do agendamento online. Itens com estoque zerado ficam em cinza-escuro e desabilitados para clique.
* **Pausa Rápida de Produtos:** Botão ("Ativo / Pausado") na lista de alimentos para ocultar itens do cardápio imediatamente em caso de avaria ou término físico.
* **Entrada de Estoque via XML de NF-e:** Importação de notas fiscais de fornecedores com tela de conferência para associação de produtos e aplicação do multiplicador de conversão de embalagens em unidades.
* **Baixa por Perdas e Descarte:** Registro obrigatório com seleção de motivo: *Avaria/Descarte*, *Vencimento*, *Sobra de Produção / Não Retirado* ou *Ajuste de Contagem*, com gravação de data e operador.
* **Estoque Mínimo:** Alerta visual destacado no painel para produtos cuja quantidade física atinja o limite de segurança configurado.

### Área Funcional 6: Controle Financeiro, Caixas e Segurança Pix
* **Sessões de Caixa por Turno:** Abertura obrigatória com registro do Fundo de Troco. O caixa matutino opera até as 11:00 e o vespertino opera até as 16:00.
* **Obrigatoriedade de Caixa Aberto:** Operações que envolvam dinheiro físico (recargas manuais no balcão e vendas avulsas) só podem ser processadas com uma sessão de caixa aberta. Fora desses períodos, recargas em dinheiro dependem de lançamento do Administrador na retaguarda.
* **Relatório de Fechamento de Caixa:** Confronto entre valores contados e registrados, segregando: Fundo de Troco, Recargas em Dinheiro, Vendas Avulsas em Dinheiro e Entradas em Pix.
* **Tratamento de Contestações Bancárias e Golpes de Pix (Mecanismo MED):**
  * Recepção de eventos/webhooks de devolução do gateway bancário.
  * Débito imediato do valor contestado na carteira familiar.
  * Se o saldo resultante for negativo, aplicação de flag de bloqueio imediato para novos pedidos de todos os dependentes e envio de notificação automática no WhatsApp do responsável comunicando o cancelamento bancário e a suspensão da conta.
  * Disponibilização de botão de estorno/ajuste manual no painel para que o Administrador lance contestações identificadas fora do gateway.

### Área Funcional 7: Administração, Cadastros e Calendário
* **Importação em Lote da Escola:** Carga inicial e atualização anual de alunos, turmas e vínculos de responsáveis via planilha Excel/CSV, preservando histórico e saldos existentes.
* **Cadastro de Alimentos e Categorias:** Gestão do catálogo de itens, preços, fotos (opcionais), turnos de disponibilidade (Matutino, Vespertino ou Ambos) e alérgenos.
* **Inativação de Alunos:** Bloqueio de novos pedidos para alunos transferidos/desligados sem exclusão do histórico financeiro e contábil.
* **Calendário Escolar e Cancelamento em Lote:** Bloqueio fixo de fins de semana e marcação de feriados/recessos. Recurso administrativo de cancelamento em lote que desmarca um dia letivo inteiro, cancela os tickets emitidos e devolve 100% do saldo às famílias de forma automática.
* **Configuração da Empresa:** Cadastro de Nome Fantasia, Razão Social, CNPJ e upload do logotipo para impressão de relatórios timbrados e cabeçalho do portal.

---

## 6. Funcionalidades Fora de Escopo

As seguintes funcionalidades foram avaliadas e **não farão parte da primeira versão**:

* **Aplicativo Móvel Nativo (Android/iOS):** Todo o acesso ocorrerá via navegador web responsivo, eliminando a necessidade de publicação em lojas de aplicativos.
* **Identificação Biométrica ou Reconhecimento Facial:** Rejeitado por custo de hardware, atrito na fila e conformidade com LGPD de menores.
* **Combos e Kits Promocionais Automáticos:** O sistema operará exclusivamente com itens unitários para simplificar o controle de estoque e pedidos na V1.
* **Teto Diário de Gastos por Filho:** O limite de consumo é restrito apenas ao saldo global da carteira familiar; limites personalizados por dependente foram postergados para a V2.
* **Leitura Digital de Tickets no Recreio:** Sem leitores de código de barras ou câmeras no intervalo; a operação de balcão é estritamente visual.
* **Avisos de Entrega de Lanche via WhatsApp:** Não haverá disparo de mensagens na entrega física do lanche; o acompanhamento dá-se pelo extrato do portal.
* **Múltiplas Escolas / Filiais (Multi-tenant):** A plataforma será implementada exclusivamente para uma única unidade escolar física.
* **Ficha Técnica Automatizada com Cálculo Calórico:** O sistema registrará alérgenos de forma declaratória, sem cálculos calóricos dinâmicos.
* **Saque Automatizado de Saldo Residual:** Devoluções de saldo residual (ao fim do ano letivo ou por cancelamento de matrícula) serão realizadas manualmente fora do sistema com dedução administrativa pelo Administrador.

---

## 7. Regras de Negócio

### Agendamento, Prazos e Cancelamentos
* **RN-01 (Janela de Abertura):** O agendamento da semana seguinte abre toda sexta-feira às 18:00, permitindo pedidos para qualquer dia letivo da semana corrente.
* **RN-02 (Horários de Corte):** O encerramento definitivo de compras, edições e cancelamentos online ocorre às 08:00 (turno matutino) e às 13:30 (turno vespertino) do próprio dia agendado.
* **RN-03 (Cancelamento com Estorno Imediato):** O responsável pode cancelar pedidos pelo portal até o horário de corte, recebendo 100% do crédito estornado na carteira virtual imediatamente.
* **RN-04 (Edição de Pedidos):** A edição de pedidos agendados antes do corte valida a diferença de valor. Se o novo item for mais caro, debita a diferença da carteira; se for mais barato, estorna o excedente na hora. Qualquer item removido libera instantaneamente a reserva do estoque industrializado.
* **RN-05 (Ausência do Aluno e Sobras):** Lanches agendados e não cancelados até o horário de corte não são reembolsados caso o aluno falte. Os itens físicos não retirados permanecem faturados e podem ser aproveitados para venda avulsa mediante entrada de "Aproveitamento de Sobra de Encomenda" ou descartados como "Sobra de Produção / Não Retirado".
* **RN-06 (Cancelamento Emergencial de Dia Letivo):** A suspensão de uma data no calendário pelo Administrador cancela todos os pedidos do dia, anula os tickets e devolve o saldo integral a todas as famílias afetadas.

### Carteira Familiar, Pix e Segurança Financeira
* **RN-07 (Compartilhamento Familiar):** O saldo pertence à conta do responsável e é utilizado de forma compartilhada entre todos os seus dependentes.
* **RN-08 (Recarga Mínima e Isenção de Taxas):** A recarga online mínima via Pix é de R$ 10,00. Nenhuma taxa de processamento é repassada ao responsável (R$ 10,00 pagos = R$ 10,00 de crédito).
* **RN-09 (Operação Pré-paga Estrita para Alunos):** Alunos só compram se houver saldo suficiente na carteira ou pagamento à vista (dinheiro/Pix); não é permitida a criação de saldo negativo manual para alunos.
* **RN-10 (Tratamento de Contestações / MED de Pix):** Ao receber notificação bancária de contestação ou estorno de Pix (Mecanismo Especial de Devolução), o sistema estorna o saldo da carteira. Se o saldo ficar negativo, o sistema bloqueia novos pedidos para todos os dependentes da família e envia alerta de suspensão via WhatsApp.

### Gestão de Estoque e Compras
* **RN-11 (Disponibilidade por Turno):** Cada produto deve possuir definição explícita de disponibilidade: *Matutino*, *Vespertino* ou *Ambos*.
* **RN-12 (Reserva de Estoque Industrializado):** Produtos contínuos têm sua quantidade disponível abatida no instante do agendamento online.
* **RN-13 (Trava de Estoque Zerado):** Produtos com quantidade disponível igual a zero ficam visíveis em cinza-escuro e bloqueados para clique no portal e no PDV.
* **RN-14 (Ciclo de Salgados - Demanda e Recebimento):** Na emissão dos tickets, os salgados constam no sistema como "Demanda Confirmada". Ao receber a entrega do fornecedor, o operador clica em "Confirmar Recebimento", gerando a entrada das quantidades e liquidando simultaneamente a baixa dos tickets impressos.
* **RN-15 (Motivo Obrigatório de Perda):** Toda baixa de estoque manual não proveniente de venda exige a seleção de um motivo obrigatório: *Avaria/Descarte*, *Vencimento*, *Sobra de Produção / Não Retirado* ou *Ajuste de Contagem*.

### Restrições Alimentares e Balcão
* **RN-16 (Alerta Visual de Restrição no Portal):** A presença de alérgenos não bloqueia terminantemente a compra pelo responsável, mas exige confirmação em modal explicativo antes da inclusão do item.
* **RN-17 (Tag Obrigatória no Ticket):** Tickets contendo produtos com ingredientes conflitantes com a restrição cadastrada do estudante devem conter obrigatoriamente a tag `[ATENÇÃO: CONTÉM ...]`.
* **RN-18 (Restrição no PDV de Balcão):** Na venda avulsa presencial, a identificação do aluno é opcional. Caso o operador informe a turma/aluno, o sistema checa restrições e exibe o alerta; se for venda anônima rápida em dinheiro, a venda é concluída sem validação.
* **RN-19 (Entrega Visual):** A entrega no recreio é feita mediante entrega e descarte do ticket de papel, sem manuseio de terminal na fila.
* **RN-20 (Janela de Caixas e Dinheiro):** O caixa matutino fecha às 11:00 e o vespertino às 16:00. O recebimento de dinheiro físico (recargas ou vendas avulsas) exige uma sessão de caixa formalmente aberta.
* **RN-21 (Consumo de Colaboradores):** O consumo de professores na modalidade "Desconto em Folha" exige identificação obrigatória do profissional no sistema com conferência visual de foto/cargo.

---

## 8. Informações que o Sistema Precisa Controlar

| Informação | Para que serve no sistema | Observações importantes |
| --- | --- | --- |
| **Responsáveis / Famílias** | Identificar o titular da conta financeira, dados de contato e credenciais de acesso. | Armazena: Nome, CPF (chave de vínculo), e-mail, celular/WhatsApp, status de aceite dos termos e flag de bloqueio financeiro. |
| **Alunos / Dependentes** | Identificar o consumidor final e direcionar os tickets de lanche para a turma correta. | Armazena: Matrícula, Nome completo, Foto (opcional), Série, Turma, Turno, Situação (Ativo/Inativo) e vínculo ao CPF do Responsável. |
| **Restrições Alimentares** | Mapear sensibilidades e alergias para exibição de alertas. | Tabela de alérgenos pré-cadastrados vinculados individualmente a cada aluno pelos próprios pais. |
| **Turmas e Séries** | Estruturar a impressão ordenada dos lotes de tickets para entrega aos professores. | Armazena: Série, Turma (ex: 6º Ano B) e Turno escolar correspondente. |
| **Professores / Colaboradores** | Identificar os funcionários autorizados a consumir sob a modalidade "Desconto em Folha". | Armazena: Nome completo, Matrícula funcional, Cargo, Foto (opcional) e status de autorização. |
| **Alimentos / Produtos** | Manter o catálogo de itens comercializados na cantina. | Armazena: Código, Descrição, Categoria, Preço de venda, Turno (Manhã/Tarde/Ambos), Tipo (Salgado diário ou Estoque fixo), Foto (opcional), Status (Ativo/Pausado) e Alérgenos contidos. |
| **Estoque e Movimentações** | Controlar saldos físicos, reservas de agendamentos e auditoria de reposições e perdas. | Armazena: Saldo físico atual, Saldo reservado, Estoque mínimo e histórico de entradas (compras/XML/sobras) e saídas (vendas/perdas com motivo). |
| **Notas Fiscais (XML)** | Registrar compras de fornecedores e armazenar a memória de conversão de embalagens. | Armazena: Chave da NF-e, Fornecedor, Data de emissão, Produtos da nota e histórico de fatores de conversão em unidades. |
| **Pedidos e Itens Agendados** | Controlar as encomendas realizadas pelos responsáveis no portal. | Armazena: Código do pedido, Data da compra, Data agendada, Turno, Aluno, Itens, Valor debitado e Status (Agendado, Editado, Cancelado, Entregue). |
| **Tickets de Lanches** | Identificador físico impresso em bobina térmica para retirada do lanche no balcão. | Armazena: Número do ticket, Vínculo ao pedido, Série/Turma, Tag de alerta de restrição e controle de emissão de 2ª via. |
| **Sessões de Caixa** | Assegurar a responsabilidade financeira sobre os valores físicos e digitais por turno. | Armazena: Turno, Operador responsável, Data/hora de abertura, Fundo de troco, Entradas em dinheiro (recargas vs avulsos), Entradas em Pix, Valores contados, Diferença e Data/hora de fechamento. |
| **Transações da Carteira** | Rastrear o extrato contábil completo de cada conta familiar. | Armazena: Identificador, Data/hora, Tipo (Crédito Pix, Crédito Dinheiro Balcão, Débito por Pedido, Estorno por Cancelamento, Débito por Contestação MED) e Saldo resultante. |
| **Calendário Letivo** | Definir os dias com permissão de agendamento de lanches escolares. | Armazena: Dias úteis liberados, Feriados, Recessos escolares, Férias e registros de cancelamentos em lote. |
| **Logs de Auditoria** | Registrar ações críticas e exceções realizadas pelos operadores. | Armazena: Usuário operador, Data/hora, Tipo de ação (Estorno de balcão, Reimpressão de ticket, Lançamento de perda) e Justificativa informada. |

---

## 9. Fluxos Principais de Uso

### Fluxo 1: Primeiro Acesso e Criação de Senha do Responsável
1. O responsável acessa a página inicial do portal da cantina e clica em "Primeiro Acesso".
2. O sistema solicita o CPF do responsável.
3. O usuário digita o CPF e clica em "Verificar".
4. O sistema consulta a base de dados importada da escola; ao localizar o CPF, gera um código de verificação de 6 dígitos e o envia via WhatsApp para o telefone cadastrado.
5. O responsável digita o código recebido no portal.
6. O sistema valida o código e exibe a tela com os Termos e Regras da Cantina (prazos de corte, políticas de falta e cancelamento).
7. O responsável marca a opção "Li e concordo com os termos", cadastra sua senha de acesso definitiva e confirma.
8. O sistema efetua a autenticação automática e direciona o usuário para o painel principal, exibindo seus filhos já vinculados.

### Fluxo 2: Recarga da Carteira Familiar via Pix Dinâmico
1. O responsável autenticado clica em "Recarregar Saldo" dentro de sua carteira virtual.
2. O sistema exibe o campo de valor com validação do valor mínimo de R$ 10,00.
3. O responsável preenche o valor desejado e confirma.
4. O sistema gera e exibe o QR Code dinâmico do Pix e o código Copia e Cola.
5. O usuário realiza a transferência pelo aplicativo do seu banco.
6. O gateway bancário confirma o pagamento via webhook em segundo plano.
7. O sistema credita imediatamente o valor integral na carteira familiar e envia uma mensagem de confirmação no WhatsApp do responsável.

### Fluxo 3: Agendamento e Edição de Lanches pelo Responsável
1. O responsável acessa a opção "Agendar Lanche" no portal.
2. O usuário seleciona o filho e clica no dia letivo desejado dentro da semana corrente.
3. O sistema lista os produtos disponíveis para o turno do aluno, apresentando produtos com estoque zerado em cinza-escuro (desabilitados).
4. O responsável seleciona os lanches. Se houver conflito com restrições alimentares cadastradas do estudante, o sistema abre modal com o alerta visual; o pai clica em "Confirmar e prosseguir".
5. O sistema valida se a carteira possui saldo suficiente e processa a reserva imediata dos itens industrializados.
6. O responsável conclui o pedido e o valor é debitado da carteira.
7. *Subfluxo de Edição (antes do horário de corte):* Caso o pai decida alterar o pedido antes das 08:00 (manhã) ou 13:30 (tarde), ele acessa o pedido e substitui os itens. O sistema recalcula o valor: se o novo item for mais caro, debita a diferença; se for mais barato, estorna o excedente na carteira; itens removidos retornam imediatamente ao estoque disponível para outros pais.

### Fluxo 4: Fechamento de Pedidos do Turno e Envio ao Fornecedor
1. Ao atingir o horário de corte (08:00 para manhã ou 13:30 para tarde), o sistema bloqueia novos agendamentos e cancelamentos para aquele turno.
2. O operador da cantina entra na tela "Pedidos do Dia / Fornecedores".
3. O sistema consolida as demandas de salgados do turno e lista a quantidade totalizada por item.
4. O operador clica no botão "Copiar para WhatsApp" (ou "Exportar PDF").
5. O sistema transfere a mensagem estruturada para a área de transferência.
6. O operador envia o pedido ao fornecedor parceiro de salgados.

### Fluxo 5: Recebimento do Fornecedor e Liquidação da Demanda de Salgados
1. O fornecedor entrega a remessa física de salgados encomendados na cantina.
2. O operador entra na tela "Recebimento de Fornecedor".
3. O sistema apresenta a listagem pré-preenchida com os salgados e quantidades solicitadas no corte.
4. O operador confere as quantidades físicas e clica em "Confirmar Recebimento".
5. O sistema registra a entrada física dos itens no estoque e, simultaneamente, liquida a baixa da demanda dos tickets impressos.
6. Eventuais salgados entregues a mais pelo fornecedor ou sobras de produção permanecem registrados no saldo de estoque liberados para o PDV avulso.

### Fluxo 6: Impressão e Distribuição dos Tickets por Turma
1. Após o horário de corte, o operador acessa a tela "Impressão de Tickets".
2. O sistema lista todos os pedidos do turno organizados e ordenados por Série e Turma.
3. O operador clica em "Imprimir Lote do Turno".
4. O sistema gera a impressão contínua em bobina térmica de 80mm com linhas de corte e tags de restrição alimentar destacadas.
5. O operador destaca e organiza os blocos de tickets por turma.
6. Os lotes de tickets são entregues aos professores responsáveis antes do intervalo.
7. Os professores distribuem os tickets aos respectivos alunos em sala de aula.

### Fluxo 7: Entrega do Lanche, Tratamento de Sobras e 2ª Via no Balcão
1. No intervalo, o aluno entrega o ticket térmico de papel ao atendente no balcão.
2. O atendente confere visualmente o produto impresso, entrega o lanche ao estudante e descarta o ticket na lixeira (sem computador na fila).
3. *Tratamento de 2ª Via:* Se o aluno perder o ticket antes do recreio, o atendente busca o nome do aluno na tela "Consulta de Pedidos" e clica em "Reimprimir 2ª Via", emitindo um cupom identificado como `[2ª VIA]` e gravando o log da operação.
4. *Tratamento de Sobras (Aluno Faltante):* Encerrado o intervalo, salgados cujos tickets não foram entregues permanecem faturados. O operador pode transferi-los para venda avulsa via "Aproveitamento de Sobra de Encomenda" ou baixá-los no módulo de perdas como "Sobra de Produção / Não Retirado".

### Fluxo 8: Venda Avulsa Presencial e Desconto em Folha
1. O cliente comparece ao balcão solicitando um lanche presencial dentre os itens sobressalentes do estoque físico.
2. O atendente acessa o "PDV / Balcão" no terminal único da cantina.
3. O atendente seleciona os produtos solicitados.
4. *Se for venda rápida a aluno:* O atendente pode selecionar diretamente "Dinheiro" ou "Pix Balcão" de forma anônima; caso decida pesquisar o nome/turma do aluno, o sistema valida restrições alimentares e exibe alertas se houver incompatibilidade.
5. *Se for venda a professor/colaborador:* O atendente busca o profissional por nome ou matrícula, confirma a foto e o cargo exibidos na tela e seleciona a opção "Desconto em Folha".
6. O atendente clica em "Confirmar Venda".
7. O sistema valida o estoque, efetua a baixa física imediata e conclui a venda, disponibilizando botão para impressão opcional de comprovante.

### Fluxo 9: Abertura e Fechamento de Caixa por Turno
1. No início do turno, o operador acessa "Financeiro > Caixa".
2. O operador informa o valor do Fundo de Troco em dinheiro e confirma a abertura da sessão do turno.
3. O sistema mantém a sessão aberta para registrar recargas manuais e vendas avulsas.
4. Ao término do atendimento (às 11:00 para o matutino e às 16:00 para o vespertino), o operador clica em "Fechar Caixa".
5. O sistema exibe o espelho de conferência discriminando: Fundo Inicial, Recargas em Dinheiro, Vendas Avulsas em Dinheiro e Entradas em Pix.
6. O operador digita os valores físicos contados na gaveta.
7. O sistema calcula quebras/sobras de caixa, encerra a sessão e emite o Relatório de Fechamento de Caixa em PDF para assinatura e prestação de contas.

### Fluxo 10: Importação de Estoque via XML de Nota Fiscal
1. O administrador acessa "Estoque > Entrada via XML".
2. O usuário faz o upload do arquivo XML da NF-e emitida pelo fornecedor.
3. O sistema lê os itens da nota e apresenta a tela de conferência.
4. O administrador associa os produtos da nota aos produtos internos (o sistema relembra mapeamentos anteriores) e informa os multiplicadores de embalagem (ex: 1 fardo com 12 sucos = fator 12).
5. O administrador confirma a operação.
6. O sistema atualiza os saldos físicos dos itens industrializados no estoque e arquiva o histórico da nota fiscal.

### Fluxo 11: Tratamento Automático de Contestação de Pix (Mecanismo MED)
1. O banco/gateway detecta uma contestação, estorno ou acionamento do Mecanismo Especial de Devolução (MED) de uma recarga Pix realizada anteriormente.
2. O sistema recebe o webhook bancário notificando a reversão do valor.
3. O sistema debita imediatamente o valor contestado da carteira virtual da família.
4. O sistema avalia o saldo resultante: caso o saldo fique negativo, ativa automaticamente a flag de bloqueio para todos os alunos vinculados àquela família.
5. O sistema bloqueia a criação de novos agendamentos no portal e dispara uma notificação automática no WhatsApp do responsável informando sobre a contestação bancária e a suspensão temporária da conta.
6. *Opção Manual:* O Administrador também pode acionar o botão de dedução manual no painel caso a constatação da contestação seja feita diretamente pelo extrato bancário fora do gateway.

---

## 10. Histórias de Usuário

* **US-01:** Como **responsável**, eu quero **realizar meu primeiro acesso informando meu CPF e validando um código via WhatsApp** para **definir minha senha com segurança e visualizar meus filhos já vinculados**.
* **US-02:** Como **responsável**, eu quero **recarregar a carteira virtual via Pix** para **disponibilizar saldo imediato para a alimentação escolar dos meus filhos**.
* **US-03:** Como **responsável**, eu quero **agendar os lanches da semana pelo portal** para **garantir a alimentação dos meus dependentes de forma organizada e sem filas**.
* **US-04:** Como **responsável**, eu quero **editar ou cancelar um lanche agendado até as 08:00/13:30** para **substituir itens ou reaver o saldo caso meu filho adoeça antes de ir para o colégio**.
* **US-05:** Como **responsável**, eu quero **cadastrar as alergias alimentares dos meus dependentes** para **ser alertado na tela caso selecione um alimento que contenha alérgenos perigosos à saúde deles**.
* **US-06:** Como **responsável**, eu quero **receber um aviso no WhatsApp quando o saldo estiver abaixo de R$ 15,00** para **recarregar a carteira a tempo e não deixar meus filhos sem lanche**.
* **US-07:** Como **operador da cantina**, eu quero **emitir a lista consolidada de salgados nos horários de corte** para **encomendar a quantidade exata ao fornecedor via WhatsApp**.
* **US-08:** Como **operador da cantina**, eu quero **confirmar o recebimento físico dos salgados entregues pelo fornecedor** para **lançar as entradas no estoque e liquidar a demanda dos tickets simultaneamente**.
* **US-09:** Como **operador da cantina**, eu quero **imprimir os lotes de tickets ordenados por série e turma** para **entregar blocos organizados aos professores antes do recreio**.
* **US-10:** Como **operador da cantina**, eu quero **atender as crianças no recreio recolhendo e descartando o ticket impresso** para **entregar os alimentos com velocidade máxima sem formar filas no balcão**.
* **US-11:** Como **operador da cantina**, eu quero **reimprimir a segunda via de um ticket perdido** para **atender o aluno que extraviou o cupom sem burocracia**.
* **US-12:** Como **operador da cantina**, eu quero **identificar o aluno na venda avulsa de balcão** para **verificar se ele possui alguma restrição alimentar antes de entregar o lanche**.
* **US-13:** Como **operador da cantina**, eu quero **abrir e fechar o caixa separadamente por turno (às 11:00 e 16:00)** para **garantir a conferência correta do dinheiro físico de cada equipe**.
* **US-14:** Como **professor/colaborador**, eu quero **lançar meu lanche de balcão na opção "Desconto em Folha"** para **consumir no intervalo e pagar no contracheque no final do mês**.
* **US-15:** Como **administrador da cantina**, eu quero **importar o XML da nota fiscal do fornecedor** para **alimentar o estoque de produtos industrializados sem digitação manual item a item**.
* **US-16:** Como **administrador da cantina**, eu quero **emitir o Extrato de Consumo do Aluno em PDF** para **apresentar o histórico detalhado de compras e recargas aos pais que solicitarem na escola**.
* **US-17:** Como **administrador da cantina**, eu quero **acompanhar o relatório de famílias negativadas** para **cobrar pendências financeiras geradas por estornos de Pix**.
* **US-18:** Como **administrador da cantina**, eu quero **suspender um dia letivo no calendário** para **cancelar e estornar automaticamente os pedidos agendados em caso de paralisações ou imprevistos escolares**.

---

## 11. Critérios de Aceitação

### Carteira Digital, Pix e Segurança (MED)
- [ ] O sistema impede recargas online via Pix inferiores a R$ 10,00.
- [ ] O saldo pago via Pix é liberado na carteira familiar no momento da confirmação do webhook bancário.
- [ ] O sistema dispara notificação de confirmação no WhatsApp do responsável após o pagamento do Pix.
- [ ] O saldo creditado é integral, sem desconto de taxas do cliente (R$ 10,00 pagos = R$ 10,00 de crédito).
- [ ] Ao receber notificação de estorno/MED de Pix, o sistema debita o valor da carteira e, se o saldo ficar negativo, bloqueia novos agendamentos e envia aviso no WhatsApp do responsável.

### Agendamentos, Horários de Corte e Edição
- [ ] A visualização do cardápio da semana seguinte fica bloqueada até as 18:00 de sexta-feira.
- [ ] O sistema bloqueia novos pedidos, edições e cancelamentos às 08:00 (matutino) e às 13:30 (vespertino).
- [ ] Cancelamentos antes do corte estornam 100% do valor para a carteira virtual de forma instantânea.
- [ ] Na edição de pedidos antes do corte, produtos mais caros debitam a diferença da carteira; produtos mais baratos estornam a diferença imediatamente.
- [ ] Itens removidos na edição de pedidos liberam imediatamente o estoque reservado de volta ao estoque disponível.
- [ ] O sistema impede agendamentos em datas marcadas como feriado, recesso ou suspensas no calendário.

### Restrições Alimentares
- [ ] O sistema exibe modal com aviso destacado caso o responsável agende um produto contendo alérgenos cadastrados no perfil do aluno.
- [ ] A conclusão do pedido com alérgenos exige confirmação consciente explícita do responsável.
- [ ] O ticket térmico impresso exibe obrigatoriamente a tag `[ATENÇÃO: CONTÉM ...]` em destaque caso o item contenha o alérgeno cadastrado do aluno.
- [ ] No PDV de balcão, caso o atendente identifique o aluno por nome ou turma, o sistema valida restrições e exibe alerta visual na tela.

### Tickets e Encomendas de Salgados
- [ ] A listagem para o fornecedor totaliza a soma de salgados do turno, bloqueando novas adições.
- [ ] O botão "Copiar para WhatsApp" transfere para a área de transferência o texto estruturado com os totais de salgados.
- [ ] Ao clicar em "Confirmar Recebimento do Fornecedor", o sistema lança a entrada física dos salgados e liquida simultaneamente a baixa dos tickets impressos.
- [ ] Os tickets térmicos de 80mm são impressos ordenados por Série e Turma, com dados legíveis de Aluno, Data, Turno, Produto e Tags de Alérgenos.
- [ ] A tela de consulta permite reimprimir tickets com marcação `[2ª VIA]`, gravando a ocorrência no log de auditoria.

### Estoque e Perdas
- [ ] Agendamentos online de itens industrializados abatem imediatamente o saldo disponível em estoque.
- [ ] Produtos com saldo disponível igual a zero ficam visíveis em cinza-escuro e desabilitados para clique.
- [ ] O botão ("Ativo / Pausado") oculta o alimento do cardápio online imediatamente.
- [ ] A importação de XML de NF-e permite associar itens a produtos internos e aplicar multiplicadores de conversão de embalagem em unidades.
- [ ] O registro de saídas por perda exige a seleção obrigatória de um dos motivos padronizados e grava data e operador.
- [ ] Lanches não retirados por alunos ausentes podem ser aproveitados para venda avulsa via "Aproveitamento de Sobra de Encomenda" ou baixados como perda.

### Caixas e Balcão
- [ ] O sistema exige o preenchimento do Fundo de Troco para liberar a abertura de caixa do turno.
- [ ] O fechamento do caixa matutino é realizado às 11:00 e o do vespertino às 16:00.
- [ ] O sistema impede recebimento de dinheiro físico (recargas ou vendas avulsas) se a sessão de caixa do turno estiver fechada.
- [ ] O relatório de fechamento de caixa segrega: Fundo Inicial, Recargas em Dinheiro, Vendas Avulsas em Dinheiro e Entradas em Pix.
- [ ] Conclusões de vendas avulsas dão baixa imediata no estoque físico e disponibilizam botão de impressão opcional de comprovante.
- [ ] Vendas na modalidade "Desconto em Folha" exigem seleção obrigatória de um colaborador cadastrado.

---

## 12. Consultas, Relatórios e Indicadores

### Dashboard Inicial (Painel da Cantina)
* **Informações exibidas:** Cards com totais do turno atual: quantidade de tickets a imprimir, resumo da lista de salgados para o fornecedor, avisos de itens abaixo do estoque mínimo e status financeiro da sessão de caixa aberta.
* **Filtros:** Alternância entre Turno Matutino e Turno Vespertino.
* **Perfis com acesso:** Operador e Administrador.
* **Decisão apoiada:** Visão operacional rápida no início de cada turno para envio de compras e impressão de tickets.

### Relatório de Extrato de Consumo por Aluno (Modelo do Vídeo Operacional)
* **Informações exibidas:** Cabeçalho institucional timbrado, dados do aluno (Nome, Matrícula, Turma, Turno), saldo atual da carteira, histórico detalhado de recargas (Data, Tipo de Pagamento, Valor) e histórico de consumo detalhado (Data, Hora, Descrição do Lanche, Quantidade, Valor Unitário, Valor Total e Saldo Restante após cada compra).
* **Filtros e buscas:** Busca por nome do aluno ou turma, e filtro por período de datas (De/Até).
* **Perfis com acesso:** Operador e Administrador.
* **Decisão apoiada:** Atendimento a responsáveis que comparecem presencialmente à cantina solicitando esclarecimento ou conferência impressa de gastos.

### Relatório de Emissão de Vendas por Alimento (Modelo do Vídeo)
* **Informações exibidas:** Tabela analítica listando: Data, Código, Descrição do Produto, Unidade de Medida, Quantidade Comercializada, Preço Unitário e Faturamento Total, com totalizadores consolidados ao final da página.
* **Filtros e buscas:** Período de datas (De/Até), Turno (Matutino/Vespertino/Ambos) e Categoria de Alimento.
* **Perfis com acesso:** Administrador.
* **Decisão apoiada:** Avaliação de lucratividade, curva de vendas de produtos e planejamento de cardápio.

### Relatório de Fechamento de Caixa por Turno
* **Informações exibidas:** Timbre da empresa, Data, Turno, Nome do Operador, Horário de Abertura, Horário de Fechamento, Fundo de Troco Inicial, Recargas em Dinheiro, Vendas Avulsas em Dinheiro, Total Geral em Dinheiro, Entradas em Pix, Valores Contados na Gaveta, Sobras/Faltas e campo para assinatura.
* **Filtros e buscas:** Data específica e turno.
* **Perfis com acesso:** Operador (apenas do seu turno) e Administrador (todos).
* **Decisão apoiada:** Prestação de contas diária, controle de sangrias e conciliação de gaveta física.

### Relatório de Desconto em Folha (Colaboradores e Professores)
* **Informações exibidas:** Resumo mensal consolidado listando: Matrícula Funcional, Nome do Colaborador, Cargo e Valor Total a Descontar, com opção de extrato analítico com os itens consumidos.
* **Filtros e buscas:** Mês/Ano de referência e busca por nome ou matrícula.
* **Perfis com acesso:** Administrador.
* **Decisão apoiada:** Envio de dados fechados em PDF e Excel/CSV para a secretaria/RH processar as deduções nos contracheques.

### Relatório de Famílias / Alunos com Saldo Devedor (Negativados)
* **Informações exibidas:** Matrícula, Nome do Aluno, Série, Turma, Nome do Responsável, Telefone/WhatsApp de contato e Valor Negativo em aberto.
* **Filtros e buscas:** Filtro por série/turma e ordenação por maior valor devedor.
* **Perfis com acesso:** Administrador.
* **Decisão apoiada:** Ações de cobrança da cantina ou secretaria escolar para recuperação de valores de Pix contestados.

---

## 13. Permissões e Segurança Funcional

| Perfil | Pode fazer | Não pode fazer | Observações |
| --- | --- | --- | --- |
| **Responsável** | Realizar primeiro acesso com validação via WhatsApp; aceitar termos de uso; cadastrar restrições alimentares dos dependentes; recarregar saldo via Pix; agendar, editar e cancelar lanches até as 08:00/13:30; consultar extrato financeiro e nutricional da família. | Não pode visualizar dados de outras famílias; não pode acessar telas de estoque, caixas ou PDV da cantina; não pode comprar fiado; não pode alterar pedidos após o horário de corte. | Segurança baseada no vínculo estrito entre o CPF autenticado e os alunos cadastrados na planilha escolar. |
| **Operador / Atendente** | Abrir e fechar o caixa do seu turno (11:00 e 16:00); consultar pedidos do turno; emitir lista consolidada para fornecedores; confirmar recebimento físico de salgados; imprimir lotes de tickets; reimprimir 2ª via de tickets com registro em log; operar PDV de vendas avulsas (identificadas ou anônimas) e desconto em folha; emitir comprovante opcional; registrar perdas com motivo obrigatório; transferir sobras de alunos faltantes para venda avulsa; emitir extrato de consumo de alunos; estornar tickets no balcão com justificativa. | Não pode alterar cadastros mestres de produtos ou preços; não pode importar XML de notas fiscais; não pode gerenciar calendário escolar ou suspender dias letivos; não pode acessar relatórios financeiros globais nem dados de outros turnos fechados. | Usuário focado no atendimento e rotina do balcão; todas as exceções (estornos/2ª via/perdas) geram logs de auditoria. |
| **Administrador / Gerente** | Todas as ações do perfil Operador, acumulando o perfil de atendente; cadastrar, editar e pausar produtos; gerenciar categorias; importar e atualizar planilhas de alunos/turmas; importar XML de notas fiscais e configurar conversões; gerenciar calendário letivo e cancelamentos em lote; acessar todos os relatórios gerenciais e de auditoria; realizar ajustes de saldo manuais e quitações de devedores; lançar recargas em dinheiro na retaguarda; configurar dados e logotipo da empresa. | Não há restrições funcionais no sistema da cantina. | Perfil master de gestão do negócio; acumula a operação de balcão quando o proprietário atua diretamente no atendimento. |

---

## 14. Limitações da Primeira Versão

* **Unidade Escolar Única:** Desenvolvido exclusivamente para a operação de um colégio físico, sem suporte a múltiplas filiais na mesma instalação.
* **Plataforma Exclusivamente Web:** Sem aplicativos nativos em lojas de apps (Google Play ou Apple Store); acesso via navegador web responsivo em computadores, tablets ou celulares.
* **Conferência Física Desconectada no Recreio:** Sem leitura óptica de códigos de barras ou QR Codes na entrega dos lanches no balcão; a operação apoia-se unicamente no recolhimento e descarte do ticket impresso em bobina térmica de 80mm.
* **Comercialização Estrita de Produtos Unitários:** Sem mecanismo de montagem de combos promocionais automáticos (ex: salgado + suco por preço fechado); cada alimento é cadastrado e debitado individualmente.
* **Ausência de Gateway de Saque Automático:** Devoluções de saldo residual (ao fim do ano letivo ou por cancelamento de matrícula) são realizadas manualmente fora do sistema com dedução administrativa pelo Administrador.
* **Disparos de Mensagens Restritos:** Mensagens automáticas via WhatsApp limitam-se a: código de primeiro acesso/recuperação de senha, confirmação de recarga Pix aprovada, alerta de saldo baixo (< R$ 15,00) e aviso de bloqueio por contestação de Pix (MED). Não haverá avisos de entrega de lanche por WhatsApp.
* **Contingência Manual em Quedas de Conexão:** Por ser 100% web em nuvem, caso a internet da escola oscile, a entrega principal de lanches não é afetada (tickets já impressos), mas vendas avulsas de balcão deverão ser anotadas manualmente pela equipe para posterior lançamento.

---

## 15. Pontos Pendentes Antes do FSD

Todas as regras operacionais, fluxos de estoque, dinâmicas de caixa, tratamento de alérgenos e políticas de cancelamento e segurança financeira foram discutidas, detalhadas e aprovadas.

Os pontos que transitam para definição direta na etapa de elaboração do **FSD (Especificação Funcional e Técnica de Software)** são de natureza puramente técnica de integração:
1. **Gateway de Pagamento Pix e Webhook MED:** Definição do provedor bancário (ex: Mercado Pago, EFI/Gerencianet, Asaas ou banco tradicional) e mapeamento técnico do endpoint de recebimento do webhook de contestação/devolução (Mecanismo Especial de Devolução do BACEN).
2. **Provedor de API de WhatsApp:** Seleção do serviço de mensageria para envio das notificações transacionais automáticas (confirmações, códigos de primeiro acesso e alertas de saldo).
3. **Driver e Spooler de Impressão Térmica (80mm):** Especificação do mecanismo de comunicação direta entre o navegador web e a impressora de bobina térmica (impressão nativa via diálogo do navegador ou uso de utilitário local de spooler silencioso para bobinas térmicas ESC/POS).

---

## 16. Resumo Final do PRD

O **Cantina Web** é uma solução completa para a gestão de cantinas de colégios particulares que elimina filas nos intervalos e garante previsibilidade e segurança financeira.

* **O que será construído:** Sistema web composto pelo Portal da Família (carteira virtual com Pix, agendamento de lanches, gestão de alergias e extratos) e pelo Painel da Cantina (consolidação de compras para fornecedores, impressão de tickets térmicos por turma, PDV de balcão para avulsos e professores, controle de estoque com XML e fechamento de caixa por turno).
* **Quem usará:** Pais e responsáveis, atendentes de balcão, gerentes/administradores e professores/colaboradores da escola.
* **Principais funcionalidades:** Agendamento com cortes rígidos (08:00 e 13:30); entrega visual no recreio via tickets térmicos de 80mm com avisos de alérgenos; exportação de pedidos para fornecedores via WhatsApp; liquidação contábil simultânea no recebimento físico de salgados; caixas separados por turno com fechamento às 11:00 e 16:00; tratamento de contestações Pix (MED); entrada de estoque via XML de NF-e; aproveitamento de sobras de alunos ausentes; consumo de professores com desconto em folha; e emissão do Extrato de Consumo do Aluno idêntico ao modelo operacional validado.
* **Fora de escopo na V1:** Aplicativo nativo, biometria/facial, combos promocionais, leitura de QR Code na entrega do lanche e gestão de múltiplas filiais.
* **Status do Projeto:** O levantamento e refinamento funcional estão **100% concluídos e validados**, estando o projeto oficialmente pronto e aprovado para avançar para a criação do FSD (Functional & Technical Specification Document).