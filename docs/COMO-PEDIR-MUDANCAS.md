# Guia Prático: Como Pedir Mudanças no Sistema para a IA (docs/COMO-PEDIR-MUDANCAS.md)

Este guia foi criado para orientar qualquer pessoa (mesmo sem conhecimentos técnicos de programação) a solicitar ajustes, correções, novos relatórios ou melhorias no sistema **Cantina Web — Colégio Monteiro Lobato** utilizando um assistente de Inteligência Artificial.

---

## 1. Como Funciona e Regra de Ouro

Uma IA de programação trabalha com extrema precisão quando recebe contexto claro sobre o sistema e instruções passo a passo.

> [!IMPORTANT]
> **A Regra de Ouro:**
> Sempre que iniciar uma conversa com a IA para pedir qualquer mudança, exija expressamente que ela leia a documentação viva antes de alterar arquivos:
> * `docs/MANUTENCAO.md`
> * `docs/FSD.md`
> * `docs/DESIGN.md` (se envolver telas ou relatórios visuais)
> * `docs/STATUS.md`
> * `docs/ERROS.md`

Ao fazer isso, você evita que a IA invente bibliotecas desnecessárias, quebre a segurança do banco de dados ou desconfigure o visual institucional do Colégio Monteiro Lobato.

---

## 2. Checklist Antes de Aceitar Qualquer Mudança Entregue pela IA

Antes de considerar uma tarefa concluída e aceitar o código, confira este checklist com a IA:

- [ ] A IA explicou claramente o que alterou e por quê?
- [ ] A IA rodou os testes automatizados relevantes (`docker compose exec app php tests/...`) e eles passaram 100%?
- [ ] A IA preservou as regras de segurança (sem injeção de SQL, com token CSRF nos formulários e com proteção XSS)?
- [ ] Se houve mudança visual, a tela respeita o `docs/DESIGN.md` (sem pílulas fluorescentes, com números tabulares e visual sóbrio)?
- [ ] Se a mudança envolveu exclusão ou encerramento, foi utilizado o modal de confirmação corporativo (sem `confirm()` nativo cinza do navegador)?
- [ ] A IA atualizou o `docs/STATUS.md` registrando o que foi feito?
- [ ] Se aconteceu algum erro no caminho, ele foi registrado em `docs/ERROS.md`?
- [ ] A IA explicou o passo a passo de como você pode testar a mudança no seu navegador?

---

## 3. Modelos de Prompts Prontos para Copiar e Colar

Basta copiar o texto do exemplo desejado, preencher os dados entre colchetes e colar no chat com a IA:

---

### Exemplo 1: Adicionar um campo em um cadastro existente

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/FSD.md
- docs/STATUS.md
- docs/ERROS.md

Preciso adicionar um novo campo no cadastro de [Alunos / Produtos / Colaboradores / Turmas].
O novo campo se chama: [Nome do Campo, ex: Telefone de Recado da Mãe / Código NCM / Observação Nutricional].
Ele é do tipo: [Texto curto / Número / Opção Sim/Não / Valor em Dinheiro].
Ele é de preenchimento: [Obrigatório / Opcional].

Por favor:
1. Crie uma nova migração sequencial em database/migrations/ para alterar a tabela no MySQL sem quebrar dados existentes.
2. Atualize o Model correspondente para incluir o novo campo nos métodos de busca, gravação e edição.
3. Atualize o formulário visual da tela e a tabela de listagem conforme o docs/DESIGN.md.
4. Execute o runner de migrações e valide que tudo continua funcionando.
5. Atualize docs/STATUS.md com o progresso e me ensine a testar no navegador.
```

---

### Exemplo 2: Criar uma nova tela ou funcionalidade

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/FSD.md
- docs/DESIGN.md
- docs/STATUS.md
- docs/ERROS.md

Preciso criar uma nova tela no sistema para: [Descreva o que a tela faz, ex: Histórico de Entregas de Salgados por Fornecedor / Painel de Alunos Transferidos].
Quem terá permissão para acessar essa tela: [Apenas Administrador / Operadores e Administrador / Responsáveis Familiares].
Quais informações principais devem aparecer: [Liste os campos ou colunas desejados].

Por favor:
1. Planeje a rota no index.php protegida pelo middleware RBAC correto.
2. Adicione os métodos necessários no Controller e no Model correspondente com Prepared Statements PDO.
3. Crie a View respeitando o padrão visual do docs/DESIGN.md (alta densidade, cabeçalhos de tabela claros, números com classe font-tabular e botões neutros).
4. Adicione o link no menu lateral ou superior.
5. Crie ou execute um teste automatizado para garantir que a tela abre com sucesso (HTTP 200).
6. Atualize docs/STATUS.md e me explique como validar no navegador.
```

---

### Exemplo 3: Corrigir um erro ou comportamento inesperado

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/FSD.md
- docs/STATUS.md
- docs/ERROS.md

Encontrei um erro no sistema e preciso que você investigue e corrija.

O que aconteceu: [Descreva o sintoma, ex: Ao tentar clicar no botão Salvar da tela de Alunos, apareceu uma tela de erro 500 / O valor total do fechamento de caixa está dando uma diferença de R$ 5,00].
Em qual tela ocorreu: [Endereço da tela, ex: /admin/alunos ou /operacional/caixa].
Mensagem de erro (se houver): [Cole o código UUID de erro ou a mensagem do log].

Por favor:
1. Investigue a causa raiz verificando os arquivos de log (storage/logs/error.log) e o código do Controller/Model envolvido.
2. Explique a causa técnica do erro de forma simples antes de fazer a alteração.
3. Aplique a correção cirúrgica sem quebrar nenhuma outra área do sistema.
4. Registre o incidente em docs/ERROS.md no formato oficial (Sintoma, Causa, Solução Aplicada, Como Evitar).
5. Atualize docs/STATUS.md e me oriente em como testar para confirmar que o erro sumiu.
```

---

### Exemplo 4: Alterar uma regra de negócio

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/FSD.md
- docs/STATUS.md
- docs/ERROS.md

A cantina precisa alterar uma regra de funcionamento do sistema.
A regra atual é: [Descreva a regra atual, ex: O horário limite de corte para pedidos do turno vespertino é às 13:30 / O valor mínimo de recarga Pix é R$ 10,00].
A nova regra deve ser: [Descreva a nova regra, ex: O horário limite deve passar a ser às 14:00 / O valor mínimo de recarga Pix deve passar a ser R$ 15,00].

Por favor:
1. Localize exatamente onde essa regra está aplicada (Models, Controllers, Configurações Globais ou Views).
2. Se envolver mais de uma tabela, certifique-se de que a operação utilize transações atômicas no MySQL.
3. Aplique a nova regra garantindo que pedidos ou registros anteriores permaneçam consistentes.
4. Execute os testes automatizados do sistema para garantir que não houve regressão.
5. Atualize docs/STATUS.md e me mostre como testar no ambiente local.
```

---

### Exemplo 5: Ajustar visual ou layout conforme o `docs/DESIGN.md`

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/DESIGN.md
- docs/STATUS.md
- docs/ERROS.md

Preciso que você refine o visual da tela [Nome da tela ou URL, ex: /operacional/corte ou /financeiro/dre].
O que precisa ser ajustado: [Descreva o que não está agradável, ex: As colunas de valores estão desalinhadas / O botão está muito chamativo / Falta respiro entre os blocos].

Por favor:
1. Siga estritamente as diretrizes do docs/DESIGN.md (Anti-Slop Enterprise Edition).
2. Utilize acento único no azul cobalto institucional (#1d4ed8), cabeçalhos de tabela neutros claros (#f8fafc) e fonte tabular (.font-tabular) em todos os valores em reais.
3. Se houver botões ou ações clicáveis neutras, garanta o padrão corporativo neutro sem fundos coloridos (.btn.btn-enterprise.btn-secondary).
4. Incremente a versão do arquivo CSS nos layouts (ex: custom.css?v=...) para forçar o navegador a recarregar o visual imediatamente sem prender no cache.
5. Atualize docs/STATUS.md e me explique como visualizar a tela atualizada com Ctrl + F5.
```

---

### Exemplo 6: Criar um novo relatório em PDF A4 ou filtro analítico

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/FSD.md
- docs/DESIGN.md
- docs/STATUS.md
- docs/ERROS.md

Preciso de um novo relatório oficial para a gestão da cantina.
Assunto do relatório: [Descreva, ex: Relatório de Consumo por Turma no Mês / Demonstrativo de Salgados Entregues por Fornecedor].
Filtros necessários: [ex: Data inicial e final, seleção de turma ou turno].
Formato de entrega: [Visualização na tela + Exportação em PDF A4 timbrado para impressão].

Por favor:
1. Crie as consultas no Model correspondente utilizando Prepared Statements nativos via PDO.
2. Crie a tela de filtros e a visualização em tabela de alta densidade no RelatoriosController.
3. Crie a view de impressão timbrada A4 seguindo o padrão homologado de Print to PDF nativo do navegador (margens físicas simétricas de 14mm para não cortar a borda preta à direita e regras estritas @media print).
4. Assegure que a emissão utilize a visualização/espelho de impressão com window.print() (Print to PDF), sem dependência de microsserviços externos ou contêineres Docker (sem Gotenberg).
5. Atualize docs/STATUS.md e me forneça o link direto para testar a geração do relatório.
```

---

### Exemplo 7: Fazer uma auditoria e revisão de segurança pós-mudança

```text
Responda sempre em português do Brasil.

Antes de iniciar, leia obrigatoriamente:
- docs/MANUTENCAO.md
- docs/STATUS.md
- docs/ERROS.md

Acabamos de fazer uma alteração no módulo [Nome do módulo, ex: Vendas no PDV / Cadastro de Alunos] e preciso de uma auditoria rigorosa de segurança no código que foi alterado.

Por favor, faça um pente-fino verificando:
1. Injeção SQL: Alguma consulta concatena variáveis em string ou todas usam Prepared Statements do PDO?
2. Proteção XSS: Todas as saídas de variáveis dinâmicas no HTML usam htmlspecialchars()?
3. Proteção CSRF: Todos os formulários que executam POST contêm o token de validação?
4. Permissões RBAC: As rotas exigem a autenticação e o perfil de usuário correto?
5. Tratamento de Erros: Existem blocos try/catch gravando exceções no log em vez de expor detalhes técnicos para o usuário?
6. Apresente um resumo objetivo com o que foi revisado e garanta que está 100% seguro.
```

---

### Exemplo 8: Preparar as alterações para commit no Git

```text
Responda sempre em português do Brasil.

Concluímos os testes e as validações da alteração atual no sistema.
Agora, precisamos versionar tudo com segurança no Git.

Por favor:
1. Verifique o status dos arquivos modificados com `git status`.
2. Confirme que nenhum arquivo sensível ou credencial real (como senhas locais ou uploads privados com dados da escola) será adicionado ao commit.
3. Prepare o commit adicionando os arquivos e escrevendo uma mensagem de commit clara, profissional e em português do Brasil no padrão imperativo (ex: "Adiciona campo x no cadastro de alunos").
4. Me mostre o comando executado e me lembre de fazer o `git push` para sincronizar com o GitHub.
```

---

## 4. O que Fazer se a IA Parecer Confusa ou Tentar Mudar a Stack

Se em algum momento a IA sugerir instalar Laravel, Node.js, Tailwind, Vue, ou quiser reescrever o sistema do zero, interrompa imediatamente e envie este comando:

```text
Pare. Você não deve alterar a stack do projeto nem reescrever a arquitetura.
O sistema Cantina Web utiliza estritamente:
- PHP 8.2 puro em arquitetura MVC própria;
- MySQL 8.0 com PDO Prepared Statements;
- Vanilla JS e CSS corporativo local sem frameworks reativos;
- Docker local e deploy em Apache compartilhado na Hostnet.

Por favor, leia atentamente docs/MANUTENCAO.md e resolva o pedido respeitando a arquitetura existente.
```
