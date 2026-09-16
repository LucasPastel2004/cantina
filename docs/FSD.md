# DOCUMENTO DE ESPECIFICAÇÃO FUNCIONAL (FSD)

---

## 1. Visão Geral

O **Cantina Web — Colégio Monteiro Lobato** é uma plataforma web integrada de gestão operacional, financeira e nutricional desenvolvida especificamente para a cantina escolar de unidade física única do Colégio Monteiro Lobato. O sistema foi projetado para operar em regime de turno duplo (matutino e vespertino), atendendo às demandas de balcão e retaguarda através de um terminal operacional único na cantina e provendo acesso externo responsivo para as famílias dos estudantes.

### Objetivo Principal
Digitalizar, antecipar e auditar todo o ciclo de encomenda, cobrança e entrega de lanches escolares, eliminando filas e tumultos nos intervalos das aulas por meio de um fluxo de atendimento baseado exclusivamente na entrega e descarte de tickets físicos de papel (bobina térmica de 80mm). O sistema garante precisão matemática na compra diária de salgados junto aos fornecedores parceiros, previne desperdícios de alimentos, erradica a circulação de dinheiro físico entre crianças no ambiente escolar e confere aos pais controle nutricional e financeiro irrestrito sobre a alimentação dos dependentes.

### Resumo do Funcionamento
O sistema é estruturado em duas frentes integradas:
1. **Portal da Família:** Ambiente web externo onde pais e responsáveis realizam a gestão de uma carteira virtual familiar única compartilhada entre os irmãos, efetuam recargas instantâneas via Pix dinâmico (sem cobrança de taxas ao usuário), cadastram restrições alimentares e alergias de cada estudante, realizam agendamentos semanais com trava de corte pontual às 08:00 (matutino) e às 13:30 (vespertino), acompanham extratos analíticos de consumo e recebem notificações transacionais via WhatsApp.
2. **Painel Operacional e Administrativo:** Ambiente web interno utilizado pela equipe da cantina para consolidar compras do turno junto a fornecedores externos, imprimir lotes de tickets térmicos ordenados por série e turma, confirmar recebimento físico de mercadorias com liquidação simultânea da demanda de produção, operar o Ponto de Venda (PDV) para vendas avulsas e consumo de colaboradores com desconto em folha, controlar o estoque físico (com suporte à importação de XML de notas fiscais e registro obrigatório de perdas) e realizar o fechamento diário e independente de caixa por turno (às 11:00 e 16:00).

### Público Usuário
* **Pais e Responsáveis:** Titulares das contas financeiras que realizam recargas, configuram restrições alimentares e agendam lanches para seus dependentes.
* **Operadores e Atendentes de Balcão:** Funcionários da cantina encarregados da rotina presencial de abertura/fechamento de caixa, impressão de tickets, atendimento rápido no recreio, operação de vendas avulsas no PDV e conferência de entregas.
* **Administradores e Gerentes:** Gestores da cantina com acesso irrestrito a configurações globais, precificação, gestão de estoque, calendário escolar, relatórios contábeis e parametrizações institucionais.
* **Professores e Colaboradores da Escola:** Funcionários da instituição autorizados ao consumo presencial no balcão sob a modalidade "Desconto em Folha", atuando como perfil passivo na base de dados.

### Contexto de Uso e Premissas Operacionais
* A instituição opera em dois turnos letivos bem definidos: Matutino (com encerramento do caixa e corte operacional às 08:00 e fechamento às 11:00) e Vespertino (com encerramento do caixa e corte operacional às 13:30 e fechamento às 16:00).
* A entrega física dos lanches encomendados no intervalo (aproximadamente 20 minutos de duração) ocorre de forma estritamente visual no balcão: o aluno entrega o ticket impresso em papel térmico recebido previamente em sala de aula, o atendente confere o item visualmente, entrega o lanche e descarta o cupom na lixeira. Nenhum computador ou leitor óptico é operado na fila do recreio.
* Toda a interface segue o padrão utilitário corporativo de alta densidade (Enterprise ERP/POS), tipografia tabular (`tnum`) e paleta de cores institucional baseada na identidade visual do Colégio Monteiro Lobato.

---

## 2. Documentos do Projeto para Implementação

Para a implementação completa do sistema, a IA codificadora deverá utilizar direta e exclusivamente os seguintes documentos:

1. `docs/FSD.md` (este documento, contendo todas as especificações funcionais, regras de negócio, arquitetura, modelo de dados, fluxos, telas e critérios de segurança);
2. `docs/DESIGN.md` (especificação visual detalhada, paleta de cores institucional, tipografia, espaçamentos, arredondamentos, estilos de botões, tabelas de alta densidade, modais e layouts de impressão).

O presente FSD já consolida todas as decisões técnicas, limites operacionais e requisitos de negócio necessários para a codificação. A IA codificadora não dependerá de nenhum documento preliminar externo ou histórico de conversas anteriores para construir o software.

---

## 3. Stack Definida

A stack tecnológica foi selecionada para garantir confiabilidade operacional, facilidade de manutenção, baixo consumo de memória em hospedagem compartilhada e separação estrita de camadas:

* **Linguagem Backend:** PHP 8.2+ estruturado de forma modular e orientado a objetos, seguindo o padrão arquitetural MVC.
* **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+, utilizando exclusivamente o driver `PDO_MYSQL` com Prepared Statements para todas as operações de leitura e escrita.
* **Tecnologias de Interface (Frontend):**
  * HTML5 semântico;
  * CSS3 utilitário e customizado, implementando as variáveis e componentes do Design System;
  * JavaScript puro (Vanilla JS, ES6+), sem frameworks reativos ou dependências pesadas de runtime;
  * Bootstrap (arquivos CSS e JS compilados e servidos estritamente a partir do repositório local, sem dependência de links CDN externos).
* **Bibliotecas Auxiliares de Backend (integradas localmente):**
  * Biblioteca para geração de documentos PDF: Dompdf integrado localmente para relatórios A4 timbrados e cupons contínuos em bobina de 80mm;
  * Extensão nativa PHP `SimpleXML` / `DOMDocument` para leitura e extração estruturada de arquivos XML de Notas Fiscais Eletrônicas (NF-e);
  * Manipulação de planilhas escolares: Leitor nativo CSV otimizado para streaming via `fgetcsv` como prioritário (sem overhead de memória), complementado por leitor de planilhas `.xlsx` (`PhpSpreadsheet`).
* **Padrão Arquitetural:** Model-View-Controller (MVC) com camada intermediária desacoplada de Serviços e Adaptadores (*Service / Gateway Layer*) para isolamento de chamadas externas de APIs.
* **Restrições Técnicas:**
  * Não utilizar frameworks PHP monolíticos externos (como Laravel ou Symfony), mantendo o código leve, transparente e de inicialização rápida;
  * Não utilizar arquivos `.env` para armazenamento de credenciais sensíveis;
  * Não exigir Node.js ou gerenciadores de pacotes em tempo de execução no servidor de produção.

---

## 4. Ambientes do Projeto

### 1. Desenvolvimento Local
* Execução em ambiente local containerizado com Docker e Docker Compose (serviços segregados para Apache, PHP 8.2 e MySQL 8.0), ou alternativamente sobre pilhas locais WAMP/XAMPP.
* Configuração de portas locais mapeadas, volume montado diretamente na pasta do projeto e emulação de webhooks bancários para testes de conciliação Pix e MED.

### 2. Testes e Homologação
* Não haverá ambiente formal intermediário de homologação na primeira versão do projeto.
* Todos os testes de integração, simulações de fluxo de caixa, validações de corte e cenários de estorno deverão ser executados e validados no ambiente de desenvolvimento antes da publicação.

### 3. Produção
* Hospedagem compartilhada gerenciada com suporte a Apache, PHP 8.2+ e MySQL na plataforma Hostnet.
* Operação em servidor Linux gerenciado, com acesso FTP/Git para implantação e phpMyAdmin/CLI para administração de banco de dados.

### 4. Observações sobre Deploy e Execução
* O deploy consiste na publicação integral do diretório versionado no servidor de produção e na execução controlada das migrações do banco de dados.
* Parâmetros sensíveis e variáveis de infraestrutura (banco de dados, chaves de API) são mantidos isolados em arquivo de configuração nativo PHP protegido contra leitura direta via web.

---

## 5. Arquitetura do Sistema

### Referência Principal de Raiz
A estrutura de arquivos do sistema adota como referência primária de organização a denominação:

`[Diretório do Projeto - Repositório]`

Essa referência representa a raiz do código versionado e foi concebida para funcionar de maneira transparente e desacoplada do caminho físico da hospedagem. O diretório poderá residir dentro de pastas públicas como:
* `htdocs/cantina/` (no XAMPP ou ambientes de desenvolvimento local);
* `www/cantina/` ou `www/` (na hospedagem Hostnet);
* `public_html/cantina/` (em painéis cPanel tradicionais).

A aplicação foi desenhada para operar perfeitamente em subpastas sem quebrar rotas, referências de assets ou redirecionamentos HTTP.

### Organização Baseada no Padrão MVC

O fluxo de execução do sistema é estruturado em responsabilidades bem delimitadas:
* **Model:** Responsável exclusivo pela regra de negócio, cálculos contábeis, manipulação de saldos da carteira familiar, controle de reservas de estoque e comunicação persistente com o banco de dados via PDO. Nenhum Model processa comandos de interface, estilos ou marcação HTML.
* **View:** Responsável pela renderização do HTML5 semântico, apresentação de dados formatados segundo as diretrizes visuais do `docs/DESIGN.md`, formulários, modais operacionais e manipulação do DOM via Vanilla JS. As Views não executam queries SQL nem realizam operações contábeis.
* **Controller:** Intercepta as requisições HTTP, efetua a higienização dos dados de entrada, verifica as permissões do usuário logado via RBAC, aciona os Models e Services competentes e despacha a resposta apropriada (renderizando uma View HTML ou retornando JSON para requisições assíncronas do PDV e webhooks).
* **Service / Gateway Layer:** Classes especializadas que isolam serviços externos (Gateway de pagamento Pix, Webhooks bancários, API de mensageria WhatsApp, Leitor de XML de NF-e e Motor de Geração de PDFs).

### Estrutura de Diretórios do Projeto

```text
[Diretório do Projeto - Repositório]/
│
├── index.php                        # Ponto único de entrada da aplicação (Front Controller)
├── .htaccess                        # Reescreve URLs para o index.php e bloqueia acesso direto a pastas internas
│
├── config/                          # Configurações sensíveis e constantes do sistema
│   ├── .htaccess                    # Negação absoluta de acesso via navegador (Deny from all)
│   ├── config.php                   # Credenciais de banco, chaves de API, timeouts e constantes globais
│   └── database.php                 # Fábrica de conexão singleton PDO com MySQL
│
├── app/                             # Núcleo da aplicação MVC
│   ├── .htaccess                    # Negação absoluta de acesso via navegador (Deny from all)
│   ├── core/                        # Classes fundamentais do framework interno
│   │   ├── Router.php               # Mecanismo de resolução de rotas amigáveis
│   │   ├── Controller.php           # Controlador base com métodos de render, json e validação
│   │   ├── Model.php                # Modelo base com wrapper PDO e Prepared Statements
│   │   ├── Session.php              # Gerenciador seguro de sessões e tokens CSRF
│   │   ├── Auth.php                 # Camada de autenticação e identificação de usuário
│   │   ├── RBAC.php                 # Middleware de controle de permissões por perfil
│   │   └── ErrorHandler.php         # Capturador global de exceções com fallback para arquivo
│   │
│   ├── controllers/                 # Controladores do sistema
│   │   ├── AuthController.php       # Login, logout, primeiro acesso e reset de senha
│   │   ├── PortalController.php     # Dashboard familiar, agendamentos e extrato do aluno
│   │   ├── CarteiraController.php   # Recargas Pix e extrato financeiro familiar
│   │   ├── PedidosController.php    # Consolidação do corte, envio a fornecedores e conferência
│   │   ├── TicketsController.php    # Impressão em lote por turma e reimpressão de 2ª via
│   │   ├── PdvController.php        # Vendas avulsas, desconto em folha e estornos de balcão
│   │   ├── EstoqueController.php    # Saldos, pausa rápida, perdas e importação XML de NF-e
│   │   ├── CaixaController.php      # Abertura, fechamento às 11:00/16:00 e conciliação
│   │   ├── RelatoriosController.php # Extratos A4 timbrados, vendas por alimento, RH e devedores
│   │   ├── AdminController.php      # Importação escolar, calendário letivo e configurações
│   │   └── WebhookController.php    # Recepção de notificações Pix e devoluções MED
│   │
│   ├── models/                      # Modelos de negócio e persistência
│   │   ├── Usuario.php              # Usuários da equipe interna (operadores e administradores)
│   │   ├── Responsavel.php
│   │   ├── Aluno.php
│   │   ├── RestricaoAlimentar.php
│   │   ├── Turma.php
│   │   ├── Colaborador.php
│   │   ├── Categoria.php
│   │   ├── Produto.php
│   │   ├── Estoque.php
│   │   ├── NotaFiscal.php
│   │   ├── ItemNotaFiscal.php
│   │   ├── Pedido.php
│   │   ├── ItemPedido.php
│   │   ├── Ticket.php
│   │   ├── SessaoCaixa.php
│   │   ├── VendaBalcao.php
│   │   ├── ItemVendaBalcao.php
│   │   ├── TransacaoCarteira.php
│   │   ├── CalendarioEscolar.php
│   │   ├── Auditoria.php
│   │   └── Configuracao.php
│   │
│   ├── views/                       # Telas e templates de visualização
│   │   ├── layouts/                 # Estruturas mestras (Portal da Família, Painel da Cantina)
│   │   │   ├── header.php
│   │   │   ├── footer.php
│   │   │   └── sidebar.php
│   │   ├── partials/                # Componentes reutilizáveis (modais, alertas, tabelas)
│   │   ├── auth/                    # Telas de login, primeiro acesso e termos
│   │   ├── portal/                  # Telas do responsável (carteira, agendamento, extrato)
│   │   ├── operacional/             # Telas da cantina (corte, recebimento, tickets, PDV, caixa)
│   │   ├── estoque/                 # Controle de estoque, importação XML e perdas
│   │   ├── relatorios/              # Espelhos de impressão A4 timbrados e relatórios
│   │   └── admin/                   # Cadastros mestres, calendário letivo e configurações
│   │
│   └── services/                    # Adaptadores de integração e utilitários
│       ├── PixService.php           # Comunicação com Gateway Pix (QR Code, Webhooks e MED)
│       ├── WhatsAppService.php      # Disparo de mensagens transacionais via API REST
│       ├── XmlParserService.php     # Extração de dados de NF-e e regras de conversão
│       ├── PdfService.php           # Renderizador de relatórios A4 e cupons contínuos 80mm
│       └── SpreadsheetService.php   # Leitura de planilhas de alunos e turmas
│
├── database/                        # Migrações e infraestrutura de dados
│   ├── .htaccess                    # Negação absoluta de acesso via navegador (Deny from all)
│   ├── migrations/                  # Arquivos de migração versionados sequencialmente (26 migrations)
│   │   ├── 001_create_migrations_table.php
│   │   ├── 002_create_configuracoes_globais_table.php
│   │   ├── 003_create_usuarios_table.php
│   │   ├── 004_create_responsaveis_table.php
│   │   ├── 005_create_turmas_table.php
│   │   ├── 006_create_alunos_table.php
│   │   ├── 007_create_restricoes_alimentares_table.php
│   │   ├── 008_create_aluno_restricoes_table.php
│   │   ├── 009_create_colaboradores_table.php
│   │   ├── 010_create_categorias_table.php
│   │   ├── 011_create_produtos_table.php
│   │   ├── 012_create_produto_alergenos_table.php
│   │   ├── 013_create_notas_fiscais_xml_table.php
│   │   ├── 014_create_itens_nota_fiscal_xml_table.php
│   │   ├── 015_create_estoque_movimentacoes_table.php
│   │   ├── 016_create_pedidos_table.php
│   │   ├── 017_create_itens_pedido_table.php
│   │   ├── 018_create_tickets_table.php
│   │   ├── 019_create_sessoes_caixa_table.php
│   │   ├── 020_create_vendas_balcao_table.php
│   │   ├── 021_create_itens_venda_balcao_table.php
│   │   ├── 022_create_transacoes_carteira_table.php
│   │   ├── 023_create_calendario_letivo_table.php
│   │   ├── 024_create_logs_auditoria_table.php
│   │   ├── 025_create_logs_erros_table.php
│   │   └── 026_create_logs_seguranca_table.php
│   └── runner.php                   # Script seguro para execução das migrações (CLI ou chave interna)
│
├── storage/                         # Arquivos temporários, uploads e logs locais
│   ├── .htaccess                    # Proteção e segregação de acesso
│   ├── logs/                        # Arquivos de log de contingência
│   │   ├── error.log                # Fallback em disco quando o banco falha
│   │   └── security.log             # Registro em disco de ameaças e acessos negados
│   └── uploads/                     # Armazenamento de arquivos
│       ├── private/                 # Planilhas escolares e XMLs fiscais (acesso HTTP bloqueado)
│       └── public/                  # Fotos de produtos e logotipo institucional (nomes com hash/UUID)
│
└── assets/                          # Recursos estáticos do frontend (acesso público liberado)
    ├── css/                         # Folhas de estilo locais baseadas no DESIGN.md
    │   ├── bootstrap.min.css        # Framework CSS local
    │   └── custom.css               # Regras corporativas de alta densidade e paleta institucional
    ├── js/                          # Scripts interativos em Vanilla JS
    │   ├── bootstrap.bundle.min.js  # JavaScript do Bootstrap local
    │   └── app.js                   # Comportamentos do PDV, máscaras monetárias e chamadas assíncronas
    └── img/                         # Imagens estáticas do sistema
        └── logo-colegio.jpg         # Logotipo oficial circular do Colégio Monteiro Lobato
```

### Proteção Rigorosa de Arquivos e Pastas Internas
* As pastas `config/`, `app/`, `database/` e `storage/` possuem diretivas `.htaccess` locais configuradas com `Deny from all` (ou `Require all denied` para Apache 2.4+), impedindo categoricamente qualquer tentativa de navegação direta via URL no browser.
* Todos os arquivos PHP das pastas internas implementam na primeira linha a checagem lógica de constante de inicialização:
  ```php
  <?php
  defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
  ```
  onde a constante `CANTINA_SISTEMA` é definida unicamente no `index.php`.
* A pasta `storage/uploads/private/` armazena dados sujeitos a sigilo fiscal e LGPD escolar (notas fiscais XML e planilhas de alunos), permanecendo 100% blindada contra leitura pública.

---

## 6. Escopo Funcional da Primeira Versão

O escopo do Cantina Web está dividido em 7 áreas funcionais integradas:

### Área 1: Portal da Família e Carteira Digital
* **Primeiro Acesso e Autenticação:** Acesso por CPF e senha. O primeiro acesso é autenticado via código de 6 dígitos enviado por WhatsApp para o telefone cadastrado na base escolar, exigindo aceite formal dos Termos e Políticas da Cantina.
* **Recuperação de Senha:** Envio de código temporário de verificação de 6 dígitos via WhatsApp para redefinição segura de senha.
* **Carteira Digital Familiar Única:** Gestão de saldo unificado para a família, utilizável por todos os filhos cadastrados, com exibição de saldo em tempo real, saldo reservado para agendamentos futuros e extrato financeiro detalhado.
* **Recarga Online via Pix Dinâmico:** Geração de QR Code dinâmico e chave Copia e Cola pelo gateway integrado, com valor mínimo de recarga de R$ 10,00 (parametrizável). A liberação do crédito ocorre no instante da confirmação do webhook bancário, sem repasse de tarifas ao responsável, seguida de mensagem de confirmação no WhatsApp.
* **Alerta de Saldo Baixo:** Disparo automático de aviso via WhatsApp e alerta visual destacado no portal sempre que o saldo disponível atingir valor inferior a R$ 15,00 (parametrizável).
* **Agendamento Semanal de Lanches:** Liberação do cardápio semanal toda sexta-feira às 18:00 para os dias letivos da semana seguinte. O responsável escolhe o dependente, a data e visualiza os produtos disponíveis para o turno escolar do filho (Matutino ou Vespertino).
* **Edição e Cancelamento Autônomo com Travas de Corte:** O responsável pode editar itens ou cancelar pedidos agendados livremente até as 08:00 (para consumo matutino) e até as 13:30 (para consumo vespertino). O cancelamento estorna 100% do valor para a carteira virtual no mesmo instante. Na edição de pedidos, se o novo lanche for mais caro, a diferença é debitada da carteira; se for mais barato, o excedente é devolvido imediatamente à carteira; qualquer item removido libera instantaneamente a reserva de estoque industrializado.
* **Gestão Ativa de Restrições Alimentares:** Cadastro de alérgenos padronizados (glúten, lactose, amendoim, ovos, soja, frutos do mar, nozes, corante tartrazina) vinculado ao perfil do aluno. A tentativa de agendar produto que contenha ingrediente incompatível dispara modal explicativo com alerta em amarelo/âmbar, exigindo confirmação consciente do responsável.

### Área 2: Gestão de Encomendas e Fornecedores
* **Horários de Corte Automáticos:** Encerramento rigoroso e irrevogável de novos agendamentos, alterações ou cancelamentos às 08:00 (turno matutino) e às 13:30 (turno vespertino).
* **Painel de Consolidação de Compras:** Totalização instantânea das quantidades demandadas de cada tipo de salgado do turno no exato momento do corte.
* **Exportação para Fornecedor:** Ação "Copiar para WhatsApp" com mensagem de texto estruturada e download de relatório consolidado em PDF A4 para envio imediato ao fornecedor parceiro.
* **Conferência e Recebimento de Mercadorias:** Tela operacional para conferência da entrega física dos salgados na cantina. Ao clicar em "Confirmar Recebimento", o sistema registra a entrada física dos itens no estoque e liquida simultaneamente a baixa da demanda dos tickets impressos, disponibilizando eventuais excedentes para venda avulsa.

### Área 3: Emissão e Operação de Tickets Térmicos
* **Geração e Ordenação de Tickets:** Emissão de cupons térmicos (bobina contínua de 80mm) para todos os lanches agendados do turno, ordenados sequencialmente por Série e Turma para facilitar a entrega em bloco aos professores.
* **Identificação Completa do Ticket:** Impressão legível contendo: Data de Consumo, Turno, Série/Turma, Nome Completo do Aluno, Descrição do Produto em destaque e Tag de Alerta de Alérgeno destacada (ex: `[ATENÇÃO: CONTÉM LACTOSE]`).
* **Consulta e Reimpressão de 2ª Via:** Tela de consulta rápida para busca de pedidos por aluno/turma e emissão de segunda via com marcação indelével `[2ª VIA]`, gravando a operação no log de auditoria com justificativa.

### Área 4: Ponto de Venda (PDV) e Balcão
* **Entrega Visual no Intervalo:** Atendimento imediato no balcão mediante o recolhimento e descarte do ticket térmico pelo atendente, sem necessidade de operação de computador na fila do recreio.
* **PDV para Vendas Avulsas:** Interface ágil para comercialização de itens sobressalentes do estoque físico e sobras de produção com pagamento em Dinheiro (com cálculo automático de troco) ou Pix no balcão.
* **Verificação Opcional de Alérgenos no PDV:** O operador pode registrar a venda avulsa de forma anônima e rápida; caso informe a matrícula, nome ou turma do estudante, o sistema cruza os alérgenos do produto com o cadastro do aluno e exibe alerta visual em caso de risco.
* **Comprovante Térmico Opcional:** Conclusão imediata da venda avulsa em tela, mantendo botão rápido para emissão de cupom térmico apenas se o cliente solicitar.
* **Consumo de Colaboradores (Desconto em Folha):** Registro do consumo presencial de professores e funcionários via busca por matrícula ou nome, exibindo obrigatoriamente a foto cadastrada e o cargo para confirmação visual do atendente antes da gravação do débito.
* **Estorno de Balcão:** Cancelamento manual de tickets no balcão em caso de falta de produto ou recusa de substituição, com estorno imediato do crédito na carteira da família, exigindo seleção de motivo pré-definido e registro em auditoria.
* **Aproveitamento de Sobras de Alunos Ausentes:** Lanches agendados cujos tickets não forem entregues no recreio permanecem faturados para a família. Ao término do turno, o operador pode transferi-los para a venda avulsa ("Aproveitamento de Sobra de Encomenda") ou registrá-los como perda ("Sobra de Produção / Não Retirado").

### Área 5: Gestão de Estoque e Compras
* **Controle Contínuo de Industrializados:** Baixa automática da quantidade disponível no estoque no momento do agendamento online. Itens com estoque zerado são apresentados com opacidade atenuada (`0.5`), fundo acinzentado e bloqueados para clique no catálogo.
* **Pausa Rápida de Produtos:** Botão de alternância instantânea ("Ativo / Pausado") na listagem de produtos para ocultar itens do cardápio imediatamente em caso de avaria ou término físico inesperado.
* **Entrada de Estoque via XML de NF-e:** Importação de notas fiscais eletrônicas de fornecedores com tela de conferência para associação de códigos de produtos internos e aplicação de fator multiplicador de conversão de embalagens em unidades (ex: 1 fardo com 12 sucos = fator 12), mantendo o histórico de itens e fatores de conversão em tabela dedicada.
* **Lançamento Obrigatório de Perdas:** Registro de baixas manuais exigindo seleção de motivo padronizado (*Avaria/Descarte*, *Vencimento*, *Sobra de Produção / Não Retirado*, *Ajuste de Contagem*), data e identificação do operador logado.
* **Alerta de Estoque Mínimo:** Destaque visual no painel e relatórios para produtos cuja quantidade física atinja o limite de segurança configurado.

### Área 6: Controle Financeiro, Caixas e Segurança Pix
* **Sessões de Caixa por Turno:** Abertura obrigatória com registro do valor do Fundo de Troco. O caixa matutino encerra às 11:00 e o vespertino às 16:00.
* **Obrigatoriedade de Caixa Aberto:** Operações financeiras envolvendo dinheiro físico (recargas manuais no balcão e vendas avulsas) só podem ser processadas com a sessão de caixa do respectivo turno aberta.
* **Relatório de Fechamento de Caixa:** Confronto entre valores contados na gaveta física e valores registrados no sistema, segregando: Fundo de Troco Inicial, Recargas em Dinheiro, Vendas Avulsas em Dinheiro e Entradas em Pix.
* **Tratamento de Contestações Bancárias e Golpes de Pix (Mecanismo MED):**
  * Recepção e processamento de webhooks de contestação bancária / devolução MED;
  * Débito imediato do valor contestado na carteira familiar correspondente;
  * Se o saldo resultante ficar negativo, aplicação automática de bloqueio para novos agendamentos de todos os dependentes vinculados àquela família;
  * Envio automático de notificação via WhatsApp ao responsável comunicando a contestação bancária e a suspensão preventiva da conta;
  * Recurso de ajuste manual de contestação no painel para que o Administrador registre devoluções identificadas fora do gateway.

### Área 7: Administração, Cadastros e Calendário Escolar
* **Importação Escolar em Lote:** Carga inicial e atualização de turmas, alunos e vínculos de responsáveis através de planilhas Excel (.xlsx) ou CSV, preservando históricos e saldos de anos anteriores.
* **Catálogo de Alimentos e Categorias:** Gestão de nomes, descrições, categorias, preços de venda, fotos opcionais, turnos de disponibilidade (Matutino, Vespertino ou Ambos) e ingredientes alérgenos.
* **Inativação de Alunos:** Bloqueio de agendamentos para alunos transferidos ou formados sem exclusão de seus vínculos contábeis ou históricos de extrato.
* **Calendário Escolar e Cancelamento em Lote:** Bloqueio fixo de sábados e domingos, marcação de feriados, recessos e férias. Recurso administrativo de suspensão de dia letivo com cancelamento em lote de todos os pedidos agendados e devolução integral do saldo às famílias afetadas de forma automática.
* **Configurações Institucionais:** Cadastro de Razão Social, Nome Fantasia, CNPJ, telefone, upload do logotipo institucional e parametrizações operacionais globais.

---

## 7. Fora de Escopo

As seguintes funcionalidades e tecnologias **não fazem parte da primeira versão** do Cantina Web:

1. **Aplicativo Móvel Nativo (iOS / Android):** O acesso é realizado exclusivamente via navegador web em layout responsivo, eliminando complexidade de publicação em lojas de aplicativos.
2. **Reconhecimento Facial ou Identificação Biométrica:** Rejeitado por custo de equipamentos, manutenção, atrito na fila e riscos de conformidade com LGPD de menores.
3. **Combos Promocionais e Kits Automáticos:** O sistema comercializa itens de forma unitária na V1 para simplificar o controle de estoque e de produção.
4. **Teto Diário de Gastos Individualizado por Filho:** O controle de consumo baseia-se unicamente no saldo compartilhado da carteira familiar; limites específicos por dependente ficam para versões futuras.
5. **Leitura Óptica de Código de Barras ou QR Code no Recreio:** O atendimento no recreio é 100% visual via ticket de papel, evitando gargalos de filas de 20 minutos com computadores.
6. **Disparo de Mensagens de Entrega do Lanche via WhatsApp:** Não haverá envio de mensagens a cada lanche entregue; o acompanhamento pelos pais dá-se pelo extrato do portal.
7. **Arquitetura Multi-escola (Multi-tenant) ou Redes de Filiais:** O sistema opera com banco de dados dedicado para uma única unidade escolar física.
8. **Ficha Técnica Automatizada com Cálculo Calórico:** Apenas alérgenos declaratórios são mantidos para segurança alimentar, sem cálculo nutricional dinâmico.
9. **Saque Automatizado de Saldo Residual via Pix:** Devoluções de saldos remanescentes (término do ano letivo ou saída da escola) são tratadas administrativamente fora do sistema com lançamento de baixa contábil manual.
10. **Permissão para Saldo Negativo Manual ("Fiado") para Alunos:** O modelo para estudantes é estritamente pré-pago.

---

## 8. Perfis de Usuário e Permissões

O controle de acesso do sistema adota o modelo **RBAC (Role-Based Access Control)** com 4 perfis definidos:

### 1. Responsável (Família)
* **Descrição:** Titular da carteira financeira e responsável legal pelos alunos dependentes.
* **Áreas Acessíveis:** Portal da Família exclusivamente.
* **Ações Permitidas:** Primeiro acesso com validação via WhatsApp; gestão de restrições alimentares dos dependentes; recarga via Pix dinâmico; agendamento semanal; edição e cancelamento de pedidos até as 08:00/13:30; consulta de extrato analítico da própria família.
* **Ações Bloqueadas:** Acesso ao PDV, caixas, estoques, relatórios da cantina, dados de outros alunos ou dados de outros responsáveis.

### 2. Operador / Atendente
* **Descrição:** Funcionário da equipe interna da cantina encarregado da rotina presencial de atendimento e balcão.
* **Áreas Acessíveis:** Módulos de corte de fornecedor, tickets, PDV de balcão, perdas de estoque, extrato de aluno no balcão e sessão de caixa do turno.
* **Ações Permitidas:** Abrir e fechar o caixa do seu turno (11:00 ou 16:00); consultar pedidos e lista de compras; confirmar recebimento de fornecedores; imprimir lotes de tickets; reimprimir 2ª via de cupom; realizar vendas avulsas e vendas para colaboradores; lançar perdas de mercadorias; transferir sobras de alunos faltantes; consultar e imprimir extrato de consumo de alunos para atendimento presencial às famílias; realizar estornos manuais de balcão com justificativa.
* **Ações Bloqueadas:** Alteração de preços ou exclusão de produtos; importação de XML de NF-e; importação de planilhas escolares; gestão do calendário escolar; relatórios contábeis globais; e alteração de parâmetros da empresa.

### 3. Administrador / Gerente
* **Descrição:** Proprietário ou gerente geral da cantina com responsabilidade técnica e contábil irrestrita.
* **Áreas Acessíveis:** Acesso irrestrito a todas as telas, módulos, configurações e relatórios do sistema.
* **Ações Permitidas:** Todas as ações do perfil Operador (acumulando a função no balcão quando necessário); gestão de contas da equipe interna (`usuarios`); importação de planilhas de alunos e turmas; cadastro e precificação de alimentos; ativação/pausa de produtos; importação de XML de NF-e com fator de conversão; gestão do calendário letivo e cancelamento em lote; ajustes manuais de saldo; quitação de negativados; relatórios gerenciais e de RH; parametrização institucional e upload de logotipo.
* **Ações Bloqueadas:** Nenhuma restrição funcional.

### 4. Professor / Colaborador
* **Descrição:** Docente ou funcionário da instituição escolar cadastrado para consumo na modalidade "Desconto em Folha".
* **Áreas Acessíveis:** Nenhuma interface de login na V1. Atua como perfil passivo na base de dados para seleção presencial no PDV.

### Matriz Completa de Permissões (RBAC)

| Módulo / Funcionalidade | Responsável | Operador | Administrador | Colaborador |
| :--- | :---: | :---: | :---: | :---: |
| **Login no Portal da Família** | Sim | Não | Sim | Não |
| **Login no Painel da Cantina** | Não | Sim | Sim | Não |
| **Recarga Pix Online** | Sim | Não | Não | Não |
| **Recarga em Dinheiro no Balcão** | Não | Sim (Turno) | Sim | Não |
| **Agendamento Semanal de Lanches** | Sim | Não | Não | Não |
| **Edição/Cancelamento até 08:00/13:30** | Sim | Não | Não | Não |
| **Gestão de Restrições Alimentares** | Sim | Não | Sim | Não |
| **Extrato da Própria Família** | Sim | Não | Sim | Não |
| **Extrato de Consumo de Aluno (Balcão)** | Não | Sim | Sim | Não |
| **Painel de Corte do Turno (Fornecedor)** | Não | Sim | Sim | Não |
| **Conferência/Recebimento de Fornecedor**| Não | Sim | Sim | Não |
| **Impressão de Lotes de Tickets (80mm)**| Não | Sim | Sim | Não |
| **Reimpressão de 2ª Via de Ticket** | Não | Sim | Sim | Não |
| **Operação do PDV (Venda Avulsa)** | Não | Sim | Sim | Não |
| **Venda com "Desconto em Folha"** | Não | Sim | Sim | Não |
| **Estorno de Balcão com Motivo** | Não | Sim | Sim | Não |
| **Aproveitamento de Sobras de Faltantes**| Não | Sim | Sim | Não |
| **Abertura/Fechamento de Caixa** | Não | Sim (Turno) | Sim (Todos) | Não |
| **Lançamento de Perdas com Motivo** | Não | Sim | Sim | Não |
| **Pausa Rápida de Produtos** | Não | Sim | Sim | Não |
| **Cadastro/Edição de Produtos e Preços**| Não | Não | Sim | Não |
| **Entrada de Estoque via XML de NF-e** | Não | Não | Sim | Não |
| **Importação de Planilha Escolar** | Não | Não | Sim | Não |
| **Gestão de Calendário e Cancelamento Lote**| Não | Não | Sim | Não |
| **Ajuste Manual de Saldo / Quitação** | Não | Não | Sim | Não |
| **Relatórios Gerenciais e de RH** | Não | Não | Sim | Não |
| **Gestão de Usuários da Equipe** | Não | Não | Sim | Não |
| **Configurações da Empresa e Parâmetros**| Não | Não | Sim | Não |
| **Consulta a Logs de Segurança/Erros** | Não | Não | Sim | Não |

---

## 9. Recursos Estruturais do Sistema

### 1. Autenticação e Sessão Segura
* Canal segregado: Responsáveis autenticam-se com CPF e senha na tabela `responsaveis`; equipe interna da cantina autentica-se com identificador `usuario` (formato `operador-[nome]` ou `admin`) e senha na tabela `usuarios`.
* Validação em duas etapas transacional via WhatsApp no primeiro acesso do responsável e na recuperação de credenciais.
* Senhas armazenadas com hash criptográfico forte utilizando `password_hash()` com algoritmo Bcrypt ou Argon2id.
* Regeneração de ID de sessão (`session_regenerate_id(true)`) no login para neutralizar ataques de fixação de sessão.
* Expiração automática de sessão após 60 minutos de inatividade.
* Proteção obrigatória contra Cross-Site Request Forgery (CSRF) através de tokens criptográficos validados em todas as requisições `POST`, `PUT` e `DELETE`.

### 2. Trilha de Auditoria (Audit Trail)
* **Auditoria Técnica:** Todas as tabelas cadastrais possuem os campos temporais e de identificação: `created_at`, `created_by`, `updated_at` e `updated_by`.
* **Auditoria de Negócio:** Tabela dedicada `logs_auditoria` para registrar eventos operacionais sensíveis:
  1. Estornos manuais de tickets no balcão;
  2. Emissão de 2ª via de cupons térmicos;
  3. Registro manual de perdas de estoque;
  4. Ajustes manuais de saldo ou quitações de negativados;
  5. Suspensão de dias letivos com cancelamento em lote.
  Cada registro armazena: operador responsável (`operador_id` referenciando `usuarios`), data/hora, entidade afetada, ID do registro, tipo de ação e justificativa textual obrigatória.

### 3. Exclusão Lógica (Soft Delete)
* Aplicação do campo temporal `deleted_at` em todas as entidades mestres: `produtos`, `categorias`, `colaboradores`, `usuarios` (equipe da cantina), `turmas` e `alunos`.
* Registros marcados como excluídos são omitidos das consultas normais (`WHERE deleted_at IS NULL`), preservando integralmente o histórico contábil de pedidos, caixas passados e extratos anteriores.

### 4. Sistema de Logs e Contingência
* **Log de Erros:** Captura centralizada de exceções em `logs_erros` contendo mensagem, arquivo, linha, stack trace e dados de requisição.
* **Mecanismo de Contingência de Erros:** Caso ocorra falha de conexão com o banco de dados MySQL ou a gravação em banco falhe, o sistema grava automaticamente o evento no arquivo `storage/logs/error.log` em disco, garantindo que falhas críticas não sejam perdidas.
* **Log de Segurança:** Tabela `logs_seguranca` para rastrear tentativas de login com erro, bloqueios preventivos por força bruta, acessos negados por RBAC e alertas de contestação Pix MED recebidos do gateway.

### 5. Configurações Globais Centralizadas
* Tabela de registro único `configuracoes_globais` contendo parâmetros operacionais editáveis pelo Administrador via interface visual.
* Armazenamento das credenciais de banco e chaves de API restrito a arquivo PHP nativo (`config/config.php`), vedado o uso de arquivos `.env`.

### 6. Uploads e Tratamento Seguro de Arquivos
* Suporte a arquivos até 10 MB nos formatos estritos: `.csv`, `.xlsx`, `.xml`, `.jpg`, `.jpeg`, `.png` e `.webp`.
* Validação em backend por checagem rigorosa de MIME type e verificação de assinatura binária (magic bytes).
* Gravação de arquivos fiscais e escolares em diretório privado inacessível pela web (`storage/uploads/private/`).
* Renomeação obrigatória de arquivos públicos (imagens) utilizando hash aleatório ou UUID, eliminando nomes originais e impedindo a injeção de scripts maliciosos.

### 7. Exportações e Emissões
* Geração nativa de documentos em PDF timbrados no formato A4 com cabeçalho oficial da escola.
* Geração de cupons contínuos para bobina térmica de 80mm.
* Exportação em formatos CSV e Excel para integração contábil e de folha de pagamento junto ao RH da instituição.
* Cópia formatada de texto estruturado para a área de transferência para envio rápido ao WhatsApp.

### 8. Integrações e Webhooks
* Gateway de pagamento Pix dinâmico com processamento em segundo plano via Webhook seguro.
* Tratamento de contestações bancárias pelo Mecanismo Especial de Devolução (MED) do Banco Central.
* API RESTful de mensageria para envio de mensagens transacionais no WhatsApp.

---

## 10. Entidades do Sistema

### 1. Usuarios (Equipe da Cantina)
* **Finalidade:** Representa os operadores, atendentes e administradores internos da cantina escolar.
* **Atributos Principais:** `id`, `usuario` (identificador único no formato `operador-[nome]` ou `admin`), `nome`, `email`, `senha_hash`, `perfil` (`operador`, `administrador`), `ativo` (booleano), soft delete (`deleted_at`), auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Responsável por N Sessões de Caixa; autor de N Movimentações de Estoque; autor de N Ações em Logs de Auditoria; operador de N Vendas de Balcão.
* **Regras:** Usuário único; autenticação via senha hash; soft delete obrigatório.

### 2. Responsaveis
* **Finalidade:** Representa o titular da conta financeira familiar e guardião legal dos alunos.
* **Atributos Principais:** `id`, `cpf` (chave única de vínculo escolar), `nome`, `email`, `whatsapp`, `senha_hash`, `saldo_atual`, `termos_aceitos_em`, `bloqueado_med` (booleano), auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Possui 1 ou mais Alunos; possui 1 Carteira Familiar; possui múltiplos Pedidos e Transações.
* **Regras:** CPF único; primeiro acesso exige validação WhatsApp de 6 dígitos; exclusão física bloqueada caso haja movimentação financeira.

### 3. Alunos
* **Finalidade:** Representa o estudante dependente que recebe o lanche na cantina.
* **Atributos Principais:** `id`, `responsavel_id`, `turma_id`, `matricula` (única), `nome`, `foto_url` (opcional), `turno` (matutino/vespertino), `ativo` (booleano), soft delete (`deleted_at`), auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Pertence a 1 Responsável; pertence a 1 Turma; possui N Restrições Alimentares; possui N Pedidos e Tickets.
* **Regras:** Alunos inativados não podem receber novos agendamentos; dados históricos são preservados integralmente.

### 4. RestricoesAlimentares
* **Finalidade:** Catálogo dos alérgenos e sensibilidades padronizados.
* **Atributos Principais:** `id`, `nome` (ex: Glúten, Lactose, Amendoim, Ovos, Soja, Frutos do Mar, Nozes, Corante Tartrazina), `descricao`.
* **Relacionamentos:** Vinculada a Alunos e a Produtos através das tabelas associativas `AlunoRestricoes` e `ProdutoAlergenos`.

### 5. AlunoRestricoes (Pivot)
* **Finalidade:** Mapeamento associativo entre cada estudante e seus alérgenos diagnosticados.
* **Atributos Principais:** `aluno_id`, `restricao_id`.

### 6. Turmas
* **Finalidade:** Agrupamento escolar estruturado que direciona a ordenação dos tickets térmicos.
* **Atributos Principais:** `id`, `serie`, `nome_turma` (ex: 6º Ano B), `turno` (matutino/vespertino), `ordem_impressao`, soft delete (`deleted_at`), auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Possui múltiplos Alunos.
* **Regras:** Utiliza soft delete para não comprometer históricos de matrículas anteriores.

### 7. Colaboradores
* **Finalidade:** Docentes e funcionários autorizados ao consumo fiado autorizado ("Desconto em Folha").
* **Atributos Principais:** `id`, `matricula_funcional` (única), `nome`, `cargo`, `foto_url`, `ativo` (booleano), soft delete, auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Possui N Vendas de Balcão registradas em seu nome.
* **Regras:** Requer foto cadastrada para conferência visual no PDV; soft delete obrigatório.

### 8. Categorias
* **Finalidade:** Segmentação dos produtos do cardápio (Salgados Assados, Sucos Naturais, Industrializados, Sobremesas).
* **Atributos Principais:** `id`, `nome`, `ordem_exibicao`, `ativo`, soft delete, auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Possui N Produtos vinculados.

### 9. Produtos (Alimentos)
* **Finalidade:** Catálogo comercial dos itens comercializados na cantina.
* **Atributos Principais:** `id`, `categoria_id`, `codigo_interno`, `descricao`, `preco_venda`, `tipo` (salgado_diario / industrializado_continuo), `turno_disponibilidade` (matutino / vespertino / ambos), `foto_url`, `estoque_atual`, `estoque_reservado`, `estoque_minimo`, `status_pausa` (ativo / pausado), soft delete, auditoria (`created_at`, `created_by`, `updated_at`, `updated_by`).
* **Relacionamentos:** Pertence a 1 Categoria; possui N Alérgenos vinculados; possui N Movimentações de Estoque; compõe N Itens de Pedido e Venda de Balcão.
* **Regras:** Salgados diários seguem demanda consolidada e liquidação no recebimento; industrializados abatem saldo físico no agendamento; estoque zerado trava compra online.

### 10. ProdutoAlergenos (Pivot)
* **Finalidade:** Mapeamento associativo entre produtos do cardápio e alérgenos contidos.
* **Atributos Principais:** `produto_id`, `restricao_id`.

### 11. NotasFiscaisXML
* **Finalidade:** Cabeçalho de notas fiscais de fornecedores importadas.
* **Atributos Principais:** `id`, `chave_nfe` (44 dígitos), `numero_nota`, `cnpj_emitente`, `razao_social_emitente`, `data_emissao`, `valor_total`, `arquivo_xml_path`, `importado_por` (referência a `usuarios`), `created_at`.
* **Relacionamentos:** Possui N Itens de Nota Fiscal associados.

### 12. ItensNotaFiscalXML
* **Finalidade:** Registro de itens importados do XML da NF-e e memória de conversão de embalagem em unidades.
* **Atributos Principais:** `id`, `nota_fiscal_id`, `codigo_produto_fornecedor`, `descricao_fornecedor`, `unidade_fornecedor`, `quantidade_fornecedor`, `valor_unitario_fornecedor`, `produto_id`, `fator_conversao`, `quantidade_convertida`, `created_at`.
* **Relacionamentos:** Pertence a 1 Nota Fiscal XML; associado a 1 Produto interno.

### 13. MovimentacoesEstoque
* **Finalidade:** Trilha contábil de todas as entradas e saídas físicas de produtos.
* **Atributos Principais:** `id`, `produto_id`, `tipo_movimentacao` (entrada_fornecedor, entrada_xml, aproveitamento_sobra, saida_venda_avulsa, saida_encomenda, saida_perda), `quantidade`, `motivo_perda` (avaria, vencimento, sobra_producao, ajuste_contagem), `nota_fiscal_id` (nulo se manual), `operador_id` (referência a `usuarios`), `justificativa`, `created_at`.
* **Relacionamentos:** Pertence a 1 Produto; vinculado opcionalmente a 1 Nota Fiscal e a 1 Usuário Operador.

### 14. Pedidos
* **Finalidade:** Registro das encomendas semanais realizadas pelas famílias no portal.
* **Atributos Principais:** `id`, `codigo_pedido` (alfanumérico único), `responsavel_id`, `aluno_id`, `data_consumo`, `turno_consumo`, `valor_total`, `status` (agendado, editado, cancelado, entregue), `created_at`, `updated_at`.
* **Relacionamentos:** Pertence a 1 Responsável e a 1 Aluno; possui N Itens de Pedido; gera N Tickets térmicos.
* **Regras:** Travas de corte às 08:00 e 13:30 impedem cancelamentos ou edições posteriores.

### 15. ItensPedido
* **Finalidade:** Detalhamento individual dos produtos solicitados em cada pedido agendado.
* **Atributos Principais:** `id`, `pedido_id`, `produto_id`, `quantidade`, `preco_unitario`, `subtotal`.

### 16. Tickets
* **Finalidade:** Registro físico emitido em bobina térmica de 80mm para retirada do lanche.
* **Atributos Principais:** `id`, `pedido_id`, `item_pedido_id`, `codigo_ticket` (único para conferência), `numero_via` (padrão 1, incrementa em 2ª via), `status` (ativo, impresso, estornado, cancelado), `tag_alergenos` (texto descritivo), `impresso_em`, `estornado_em`, `motivo_estorno`, `created_at`.
* **Relacionamentos:** Vinculado a 1 Pedido e a 1 Item de Pedido.
* **Regras:** 2ª via grava registro compulsório em auditoria com justificativa do atendente; estorno no balcão atualiza o status do ticket para `estornado`.

### 17. SessoesCaixa
* **Finalidade:** Assegurar a responsabilidade sobre valores monetários físicos e digitais por turno.
* **Atributos Principais:** `id`, `operador_id` (referência a `usuarios`), `turno` (matutino/vespertino), `aberto_em`, `fechado_em`, `fundo_troco_inicial`, `total_dinheiro_recargas`, `total_dinheiro_avulso`, `total_pix_balcao`, `total_informado_gaveta`, `diferenca_quebra_sobra`, `status` (aberto / fechado), `observacoes`.
* **Relacionamentos:** Pertence a 1 Usuário Operador; agrupa vendas de balcão do período.

### 18. VendasBalcao
* **Finalidade:** Registro de vendas avulsas e consumo de colaboradores no balcão presencial.
* **Atributos Principais:** `id`, `sessao_caixa_id`, `operador_id` (referência a `usuarios`), `tipo_venda` (avulso_aluno, desconto_folha), `aluno_id` (opcional), `colaborador_id` (opcional), `forma_pagamento` (dinheiro, pix_balcao, desconto_folha), `valor_total`, `valor_dinheiro_recebido`, `troco_devolvido`, `created_at`.
* **Relacionamentos:** Pertence a 1 Sessão de Caixa e a 1 Usuário Operador; associado opcionalmente a 1 Aluno ou 1 Colaborador; possui N Itens de Venda de Balcão.

### 19. ItensVendaBalcao
* **Finalidade:** Itens individualizados comercializados no PDV presencial.
* **Atributos Principais:** `id`, `venda_balcao_id`, `produto_id`, `quantidade`, `preco_unitario`, `subtotal`.

### 20. TransacoesCarteira
* **Finalidade:** Livro-razão contábil e extrato analítico da carteira familiar.
* **Atributos Principais:** `id`, `responsavel_id`, `aluno_id` (opcional, para vínculo de consumo), `operador_id` (opcional, para auditoria de recargas balcão e ajustes manuais), `tipo_transacao` (recarga_pix, recarga_dinheiro, debito_pedido, estorno_cancelamento, debito_contestacao_med, estorno_balcao, ajuste_manual), `valor`, `saldo_anterior`, `saldo_resultante`, `referencia_id` (pedido, webhook ou sessão), `descricao`, `created_at`.
* **Relacionamentos:** Pertence a 1 Responsável; associado opcionalmente a 1 Aluno e a 1 Usuário Operador.
* **Regras:** Saldo resultante é auditado linha a linha de forma imutável.

### 21. CalendarioLetivo
* **Finalidade:** Parametrização dos dias liberados para compras escolares e bloqueio de recessos.
* **Atributos Principais:** `id`, `data`, `tipo` (letivo, feriado, recesso, ferias, cancelado_emergencial), `descricao`, `cancelado_em`, `cancelado_por`.
* **Regras:** Cancelamento emergencial aciona rotina em lote de anulação de tickets e estorno financeiro.

### 22. LogsAuditoria
* **Finalidade:** Rastreamento de operações críticas e ações discricionárias da equipe.
* **Atributos Principais:** `id`, `operador_id` (referência a `usuarios`), `acao` (estorno_balcao, reimpressao_ticket, baixa_perda, ajuste_saldo, cancelamento_letivo_lote), `tabela_afetada`, `registro_id`, `justificativa`, `ip_origem`, `created_at`.

### 23. LogsErros
* **Finalidade:** Repositório técnico de exceções para diagnóstico e suporte.
* **Atributos Principais:** `id`, `nivel` (error, critical), `mensagem`, `arquivo`, `linha`, `stack_trace`, `url_requisicao`, `usuario_id`, `ip_origem`, `created_at`.

### 24. LogsSeguranca
* **Finalidade:** Registro de eventos hostis, falhas de autenticação e alertas regulatórios.
* **Atributos Principais:** `id`, `evento` (falha_login, bloqueio_ip_forca_bruta, acesso_negado_rbac, webhook_med_recebido, conta_bloqueada), `cpf_ou_usuario`, `detalhes_json`, `ip_origem`, `created_at`.

### 25. ConfiguracoesGlobais
* **Finalidade:** Tabela de linha única contendo parâmetros de negócio e institucionais.
* **Atributos Principais:** `id`, `razao_social`, `nome_fantasia`, `cnpj`, `telefone_contato`, `logo_path`, `recarga_minima_pix`, `alerta_saldo_baixo`, `timeout_sessao_minutos`, `limite_tentativas_login`, `itens_por_pagina`.

### 26. MigrationsControle
* **Finalidade:** Rastreabilidade de scripts de banco de dados executados para prevenir execuções duplicadas.
* **Atributos Principais:** `id`, `migration_arquivo` (ex: `001_create_migrations_table.php`), `executado_em`.

---

## 11. Modelo de Dados Proposto

### Dicionário de Dados das Tabelas (26 Tabelas)

#### 1. `configuracoes_globais`
Armazena os parâmetros operacionais únicos da cantina.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `razao_social` VARCHAR(150) NOT NULL
* `nome_fantasia` VARCHAR(100) NOT NULL
* `cnpj` VARCHAR(18) NOT NULL
* `telefone_contato` VARCHAR(20) NOT NULL
* `logo_path` VARCHAR(255) DEFAULT 'assets/img/logo-colegio.jpg'
* `recarga_minima_pix` DECIMAL(10,2) NOT NULL DEFAULT 10.00
* `alerta_saldo_baixo` DECIMAL(10,2) NOT NULL DEFAULT 15.00
* `timeout_sessao_minutos` INT NOT NULL DEFAULT 60
* `limite_tentativas_login` INT NOT NULL DEFAULT 5
* `itens_por_pagina` INT NOT NULL DEFAULT 25
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

#### 2. `migrations_controle`
Tabela técnica de versionamento do banco de dados.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `migration_arquivo` VARCHAR(191) NOT NULL UNIQUE
* `executado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

#### 3. `usuarios`
Contas de acesso dos funcionários internos da cantina (operadores e administradores).
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `usuario` VARCHAR(50) NOT NULL UNIQUE  -- formato: 'operador-[nome]' ou 'admin'
* `nome` VARCHAR(100) NOT NULL
* `email` VARCHAR(100) NOT NULL
* `senha_hash` VARCHAR(255) NOT NULL
* `perfil` ENUM('operador', 'administrador') NOT NULL DEFAULT 'operador'
* `ativo` TINYINT(1) NOT NULL DEFAULT 1
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* `deleted_at` DATETIME NULL
* *Foreign Keys:*
  * `fk_usuarios_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_usuarios_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índice Único:* `idx_usuarios_login` (`usuario`)

#### 4. `responsaveis`
Contas titulares das famílias.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `cpf` VARCHAR(11) NOT NULL UNIQUE
* `nome` VARCHAR(100) NOT NULL
* `email` VARCHAR(100) NOT NULL
* `whatsapp` VARCHAR(20) NOT NULL
* `senha_hash` VARCHAR(255) NOT NULL
* `saldo_atual` DECIMAL(10,2) NOT NULL DEFAULT 0.00
* `termos_aceitos_em` DATETIME NULL
* `bloqueado_med` TINYINT(1) NOT NULL DEFAULT 0
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* *Foreign Keys:*
  * `fk_responsaveis_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_responsaveis_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_responsaveis_cpf` (`cpf`), `idx_responsaveis_bloqueado` (`bloqueado_med`)

#### 5. `turmas`
Agrupamentos escolares para ordenação de tickets.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `serie` VARCHAR(50) NOT NULL
* `nome_turma` VARCHAR(50) NOT NULL
* `turno` ENUM('matutino', 'vespertino') NOT NULL
* `ordem_impressao` INT NOT NULL DEFAULT 0
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* `deleted_at` DATETIME NULL
* *Foreign Keys:*
  * `fk_turmas_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_turmas_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_turmas_turno_ordem` (`turno`, `ordem_impressao`)

#### 6. `alunos`
Estudantes vinculados aos responsáveis.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `responsavel_id` INT UNSIGNED NOT NULL
* `turma_id` INT UNSIGNED NOT NULL
* `matricula` VARCHAR(30) NOT NULL UNIQUE
* `nome` VARCHAR(100) NOT NULL
* `foto_url` VARCHAR(255) NULL
* `turno` ENUM('matutino', 'vespertino') NOT NULL
* `ativo` TINYINT(1) NOT NULL DEFAULT 1
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* `deleted_at` DATETIME NULL
* *Foreign Keys:*
  * `fk_alunos_responsavel` (`responsavel_id`) REFERENCES `responsaveis`(`id`) ON DELETE RESTRICT
  * `fk_alunos_turma` (`turma_id`) REFERENCES `turmas`(`id`) ON DELETE RESTRICT
  * `fk_alunos_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_alunos_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_alunos_resp` (`responsavel_id`), `idx_alunos_turma` (`turma_id`), `idx_alunos_nome` (`nome`)

#### 7. `restricoes_alimentares`
Catálogo oficial de alérgenos.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome` VARCHAR(50) NOT NULL UNIQUE
* `descricao` VARCHAR(255) NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

#### 8. `aluno_restricoes`
Tabela associativa de alérgenos por estudante.
* `aluno_id` INT UNSIGNED NOT NULL
* `restricao_id` INT UNSIGNED NOT NULL
* PRIMARY KEY (`aluno_id`, `restricao_id`)
* *Foreign Keys:*
  * `fk_ar_aluno` (`aluno_id`) REFERENCES `alunos`(`id`) ON DELETE CASCADE
  * `fk_ar_restricao` (`restricao_id`) REFERENCES `restricoes_alimentares`(`id`) ON DELETE RESTRICT

#### 9. `colaboradores`
Professores e funcionários para consumo em folha.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `matricula_funcional` VARCHAR(30) NOT NULL UNIQUE
* `nome` VARCHAR(100) NOT NULL
* `cargo` VARCHAR(50) NOT NULL
* `foto_url` VARCHAR(255) NULL
* `ativo` TINYINT(1) NOT NULL DEFAULT 1
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* `deleted_at` DATETIME NULL
* *Foreign Keys:*
  * `fk_colaboradores_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_colaboradores_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_colaboradores_mat` (`matricula_funcional`), `idx_colaboradores_nome` (`nome`)

#### 10. `categorias`
Classificação de cardápio.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nome` VARCHAR(50) NOT NULL UNIQUE
* `ordem_exibicao` INT NOT NULL DEFAULT 0
* `ativo` TINYINT(1) NOT NULL DEFAULT 1
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* `deleted_at` DATETIME NULL
* *Foreign Keys:*
  * `fk_categorias_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_categorias_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL

#### 11. `produtos`
Catálogo comercial de alimentos e bebidas.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `categoria_id` INT UNSIGNED NOT NULL
* `codigo_interno` VARCHAR(30) NOT NULL UNIQUE
* `descricao` VARCHAR(100) NOT NULL
* `preco_venda` DECIMAL(10,2) NOT NULL
* `tipo` ENUM('salgado_diario', 'industrializado_continuo') NOT NULL
* `turno_disponibilidade` ENUM('matutino', 'vespertino', 'ambos') NOT NULL DEFAULT 'ambos'
* `foto_url` VARCHAR(255) NULL
* `estoque_atual` INT NOT NULL DEFAULT 0
* `estoque_reservado` INT NOT NULL DEFAULT 0
* `estoque_minimo` INT NOT NULL DEFAULT 5
* `status_pausa` ENUM('ativo', 'pausado') NOT NULL DEFAULT 'ativo'
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `created_by` INT UNSIGNED NULL
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* `updated_by` INT UNSIGNED NULL
* `deleted_at` DATETIME NULL
* *Foreign Keys:*
  * `fk_produtos_categoria` (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT
  * `fk_produtos_created_by` (`created_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
  * `fk_produtos_updated_by` (`updated_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_produtos_cat` (`categoria_id`), `idx_produtos_tipo` (`tipo`), `idx_produtos_status` (`status_pausa`, `turno_disponibilidade`)

#### 12. `produto_alergenos`
Tabela associativa de ingredientes críticos por alimento.
* `produto_id` INT UNSIGNED NOT NULL
* `restricao_id` INT UNSIGNED NOT NULL
* PRIMARY KEY (`produto_id`, `restricao_id`)
* *Foreign Keys:*
  * `fk_pa_produto` (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE CASCADE
  * `fk_pa_restricao` (`restricao_id`) REFERENCES `restricoes_alimentares`(`id`) ON DELETE RESTRICT

#### 13. `notas_fiscais_xml`
Documentos fiscais importados para estoque.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `chave_nfe` VARCHAR(44) NOT NULL UNIQUE
* `numero_nota` VARCHAR(20) NOT NULL
* `cnpj_emitente` VARCHAR(18) NOT NULL
* `razao_social_emitente` VARCHAR(150) NOT NULL
* `data_emissao` DATETIME NOT NULL
* `valor_total` DECIMAL(10,2) NOT NULL
* `arquivo_xml_path` VARCHAR(255) NOT NULL
* `importado_por` INT UNSIGNED NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_nfe_usuario` (`importado_por`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT

#### 14. `itens_nota_fiscal_xml`
Memória de cálculo e histórico de conversão das embalagens importadas por nota fiscal.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nota_fiscal_id` INT UNSIGNED NOT NULL
* `codigo_produto_fornecedor` VARCHAR(50) NOT NULL
* `descricao_fornecedor` VARCHAR(150) NOT NULL
* `unidade_fornecedor` VARCHAR(10) NOT NULL
* `quantidade_fornecedor` DECIMAL(10,2) NOT NULL
* `valor_unitario_fornecedor` DECIMAL(10,2) NOT NULL
* `produto_id` INT UNSIGNED NOT NULL
* `fator_conversao` INT NOT NULL DEFAULT 1
* `quantidade_convertida` INT NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_inf_nfe` (`nota_fiscal_id`) REFERENCES `notas_fiscais_xml`(`id`) ON DELETE CASCADE
  * `fk_inf_produto` (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT
* *Índices:* `idx_inf_nfe` (`nota_fiscal_id`), `idx_inf_prod` (`produto_id`)

#### 15. `estoque_movimentacoes`
Rastreamento físico de entradas, saídas e perdas.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `produto_id` INT UNSIGNED NOT NULL
* `tipo_movimentacao` ENUM('entrada_fornecedor', 'entrada_xml', 'aproveitamento_sobra', 'saida_venda_avulsa', 'saida_encomenda', 'saida_perda') NOT NULL
* `quantidade` INT NOT NULL
* `motivo_perda` ENUM('avaria', 'vencimento', 'sobra_producao', 'ajuste_contagem') NULL
* `nota_fiscal_id` INT UNSIGNED NULL
* `operador_id` INT UNSIGNED NOT NULL
* `justificativa` VARCHAR(255) NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_mov_produto` (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT
  * `fk_mov_nf` (`nota_fiscal_id`) REFERENCES `notas_fiscais_xml`(`id`) ON DELETE SET NULL
  * `fk_mov_usuario` (`operador_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
* *Índices:* `idx_mov_prod_tipo` (`produto_id`, `tipo_movimentacao`), `idx_mov_data` (`created_at`)

#### 16. `pedidos`
Agendamentos de lanches pelas famílias.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `codigo_pedido` VARCHAR(20) NOT NULL UNIQUE
* `responsavel_id` INT UNSIGNED NOT NULL
* `aluno_id` INT UNSIGNED NOT NULL
* `data_consumo` DATE NOT NULL
* `turno_consumo` ENUM('matutino', 'vespertino') NOT NULL
* `valor_total` DECIMAL(10,2) NOT NULL
* `status` ENUM('agendado', 'editado', 'cancelado', 'entregue') NOT NULL DEFAULT 'agendado'
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_pedidos_responsavel` (`responsavel_id`) REFERENCES `responsaveis`(`id`) ON DELETE RESTRICT
  * `fk_pedidos_aluno` (`aluno_id`) REFERENCES `alunos`(`id`) ON DELETE RESTRICT
* *Índices Compostos:* `idx_pedidos_corte` (`data_consumo`, `turno_consumo`, `status`), `idx_pedidos_aluno` (`aluno_id`, `data_consumo`)

#### 17. `itens_pedido`
Itens que compõem cada agendamento.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `pedido_id` INT UNSIGNED NOT NULL
* `produto_id` INT UNSIGNED NOT NULL
* `quantidade` INT NOT NULL DEFAULT 1
* `preco_unitario` DECIMAL(10,2) NOT NULL
* `subtotal` DECIMAL(10,2) NOT NULL
* *Foreign Keys:*
  * `fk_itens_pedido` (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
  * `fk_itens_produto` (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT

#### 18. `tickets`
Cupons gerados para impressão térmica de 80mm.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `pedido_id` INT UNSIGNED NOT NULL
* `item_pedido_id` INT UNSIGNED NOT NULL
* `codigo_ticket` VARCHAR(25) NOT NULL UNIQUE
* `numero_via` INT NOT NULL DEFAULT 1
* `status` ENUM('ativo', 'impresso', 'estornado', 'cancelado') NOT NULL DEFAULT 'ativo'
* `tag_alergenos` VARCHAR(150) NULL
* `impresso_em` DATETIME NULL
* `estornado_em` DATETIME NULL
* `motivo_estorno` VARCHAR(255) NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_tickets_pedido` (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE
  * `fk_tickets_item` (`item_pedido_id`) REFERENCES `itens_pedido`(`id`) ON DELETE CASCADE
* *Índices:* `idx_tickets_cod` (`codigo_ticket`), `idx_tickets_status` (`status`)

#### 19. `sessoes_caixa`
Sessões de controle de gaveta por turno.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `operador_id` INT UNSIGNED NOT NULL
* `turno` ENUM('matutino', 'vespertino') NOT NULL
* `aberto_em` DATETIME NOT NULL
* `fechado_em` DATETIME NULL
* `fundo_troco_inicial` DECIMAL(10,2) NOT NULL
* `total_dinheiro_recargas` DECIMAL(10,2) NOT NULL DEFAULT 0.00
* `total_dinheiro_avulso` DECIMAL(10,2) NOT NULL DEFAULT 0.00
* `total_pix_balcao` DECIMAL(10,2) NOT NULL DEFAULT 0.00
* `total_informado_gaveta` DECIMAL(10,2) NULL
* `diferenca_quebra_sobra` DECIMAL(10,2) NULL
* `status` ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto'
* `observacoes` TEXT NULL
* *Foreign Keys:*
  * `fk_caixa_usuario` (`operador_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
* *Índices:* `idx_caixa_data_turno` (`aberto_em`, `turno`, `status`)

#### 20. `vendas_balcao`
Registro de vendas avulsas e desconto em folha no PDV.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `sessao_caixa_id` INT UNSIGNED NOT NULL
* `operador_id` INT UNSIGNED NOT NULL
* `tipo_venda` ENUM('avulso_aluno', 'desconto_folha') NOT NULL
* `aluno_id` INT UNSIGNED NULL
* `colaborador_id` INT UNSIGNED NULL
* `forma_pagamento` ENUM('dinheiro', 'pix_balcao', 'desconto_folha') NOT NULL
* `valor_total` DECIMAL(10,2) NOT NULL
* `valor_dinheiro_recebido` DECIMAL(10,2) NULL
* `troco_devolvido` DECIMAL(10,2) NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_vb_caixa` (`sessao_caixa_id`) REFERENCES `sessoes_caixa`(`id`) ON DELETE RESTRICT
  * `fk_vb_usuario` (`operador_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
  * `fk_vb_aluno` (`aluno_id`) REFERENCES `alunos`(`id`) ON DELETE SET NULL
  * `fk_vb_colaborador` (`colaborador_id`) REFERENCES `colaboradores`(`id`) ON DELETE RESTRICT
* *Índices:* `idx_vb_colab_data` (`colaborador_id`, `created_at`), `idx_vb_data` (`created_at`), `idx_vb_caixa` (`sessao_caixa_id`)

#### 21. `itens_venda_balcao`
Itens comercializados no PDV.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `venda_balcao_id` INT UNSIGNED NOT NULL
* `produto_id` INT UNSIGNED NOT NULL
* `quantidade` INT NOT NULL
* `preco_unitario` DECIMAL(10,2) NOT NULL
* `subtotal` DECIMAL(10,2) NOT NULL
* *Foreign Keys:*
  * `fk_ivb_venda` (`venda_balcao_id`) REFERENCES `vendas_balcao`(`id`) ON DELETE CASCADE
  * `fk_ivb_produto` (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT

#### 22. `transacoes_carteira`
Extrato contábil analítico da família.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `responsavel_id` INT UNSIGNED NOT NULL
* `aluno_id` INT UNSIGNED NULL
* `operador_id` INT UNSIGNED NULL
* `tipo_transacao` ENUM('recarga_pix', 'recarga_dinheiro', 'debito_pedido', 'estorno_cancelamento', 'debito_contestacao_med', 'estorno_balcao', 'ajuste_manual') NOT NULL
* `valor` DECIMAL(10,2) NOT NULL
* `saldo_anterior` DECIMAL(10,2) NOT NULL
* `saldo_resultante` DECIMAL(10,2) NOT NULL
* `referencia_id` VARCHAR(50) NULL
* `descricao` VARCHAR(255) NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_tc_responsavel` (`responsavel_id`) REFERENCES `responsaveis`(`id`) ON DELETE RESTRICT
  * `fk_tc_aluno` (`aluno_id`) REFERENCES `alunos`(`id`) ON DELETE SET NULL
  * `fk_tc_operador` (`operador_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_tc_resp_data` (`responsavel_id`, `created_at`), `idx_tc_aluno` (`aluno_id`), `idx_tc_operador` (`operador_id`)

#### 23. `calendario_letivo`
Controle de datas e suspensões em lote.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `data` DATE NOT NULL UNIQUE
* `tipo` ENUM('letivo', 'feriado', 'recesso', 'ferias', 'cancelado_emergencial') NOT NULL
* `descricao` VARCHAR(100) NOT NULL
* `cancelado_em` DATETIME NULL
* `cancelado_por` INT UNSIGNED NULL
* *Foreign Keys:*
  * `fk_cal_usuario` (`cancelado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
* *Índices:* `idx_cal_data_tipo` (`data`, `tipo`)

#### 24. `logs_auditoria`
Trilha de operações discricionárias de balcão e retaguarda.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `operador_id` INT UNSIGNED NOT NULL
* `acao` VARCHAR(50) NOT NULL
* `tabela_afetada` VARCHAR(50) NOT NULL
* `registro_id` INT UNSIGNED NOT NULL
* `justificativa` VARCHAR(255) NOT NULL
* `ip_origem` VARCHAR(45) NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
* *Foreign Keys:*
  * `fk_audit_usuario` (`operador_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
* *Índices:* `idx_audit_acao` (`acao`, `created_at`)

#### 25. `logs_erros`
Repositório centralizado de falhas da aplicação.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `nivel` ENUM('error', 'critical') NOT NULL DEFAULT 'error'
* `mensagem` TEXT NOT NULL
* `arquivo` VARCHAR(255) NOT NULL
* `linha` INT NOT NULL
* `stack_trace` MEDIUMTEXT NULL
* `url_requisicao` VARCHAR(255) NULL
* `usuario_id` VARCHAR(50) NULL
* `ip_origem` VARCHAR(45) NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

#### 26. `logs_seguranca`
Trilha de segurança da informação e bloqueios.
* `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `evento` VARCHAR(50) NOT NULL
* `cpf_ou_usuario` VARCHAR(50) NULL
* `detalhes_json` TEXT NULL
* `ip_origem` VARCHAR(45) NOT NULL
* `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

---

### Estratégia de Migrations do Projeto

O sistema adota uma arquitetura interna de **Migrations** versionadas para criar, atualizar e manter toda a estrutura do banco de dados sem exigir que o operador ou desenvolvedor execute comandos SQL manuais no phpMyAdmin.

1. **Localização Segura:** Todos os scripts residem em `database/migrations/`, protegidos contra acesso web via `.htaccess` e restrição de constante PHP.
2. **Nomenclatura Sequencial:** Arquivos nomeados com prefixo numérico de 3 dígitos correspondentes às 26 tabelas da aplicação (`001_create_migrations_table.php` a `026_create_logs_seguranca_table.php`).
3. **Controle contra Execução Duplicada:** O executor verifica a tabela `migrations_controle`. Apenas arquivos cujo nome não esteja gravado na tabela são executados. Ao concluir com sucesso, o nome é registrado com timestamp.
4. **Modo de Execução Controlado:**
   * **Via Linha de Comando (CLI):** `php database/runner.php`
   * **Via Rota Web Administrativa Restrita:** Acessível apenas pelo Administrador logado sob confirmação de senha master ou token secreto interno (`?key=CHAVE_MESTRA_CONFIG`), executando em transação protegida.
5. **Transações Atômicas:** Cada script executa suas instruções DDL e o registro de auditoria dentro de transações seguras do MySQL (quando compatível) com rollback automático em caso de erro.

---

## 12. Módulos e Telas

Todas as interfaces devem seguir fielmente os padrões de layout, tipografia, espaçamento e paleta de cores estipulados no `docs/DESIGN.md`:
* **Tema:** *Institutional Light / High-Density Enterprise ERP*;
* **Tipografia:** Família Inter com ativação obrigatória de fontes tabulares (`font-feature-settings: 'tnum' 1, 'zero' 1;`) para colunas monetárias e quantidades;
* **Cores Principais:** Azul Royal Primário (`#2563eb`), Fundo de Seleção Azul Suave (`#dbeafe`), Superfície Cinza Suave (`#f1f5f9`), Cartões Brancos (`#ffffff`), Ação Positiva Verde (`#16a34a`), Ação Destrutiva Vermelha (`#dc2626`) e Alerta Amarelo (`#eab308`).
* **Logotipo:** Exibição da imagem circular do Colégio Monteiro Lobato (`assets/img/logo-colegio.jpg`) no topo esquerdo do painel (`36px` a `40px`) e centralizado na tela de login (`80px` a `96px`).
* **Estados Visuais Padronizados (DESIGN.md):**
  * **Empty State (Listagens e Tabelas Vazias):** Quando uma consulta ou filtro não retornar registros, exibir contêiner centralizado com espaçamento generoso (`padding: 32px`), tipografia em cinza neutro (`#64748b`) e a frase padronizada: *"Não há nada para mostrar aqui"*.
  * **Loading State (Processamento Assíncrono):** Em operações assíncronas (geração de QR Code Pix, conferência de XML de NF-e e processamento de impressões em lote), exibir modal centralizado com título *"Processamento em Andamento"*, spinner animado em tom azul (`#3b82f6`) e cronômetro digital em tempo real no formato `00:00:01` posicionado abaixo do ícone animado.

---

### Módulo 1: Autenticação e Acesso

#### Tela 1.1: Login Unificado
* **Objetivo:** Ponto de entrada de todos os perfis.
* **Usuários:** Responsável, Operador e Administrador.
* **Elementos Visuais:** Card centralizado branco (`radius-lg: 8px`), logotipo oficial circular do Colégio Monteiro Lobato centralizado (`96px`), abas de seleção: "Acesso Família" e "Equipe da Cantina".
* **Campos (Família):** CPF (com máscara `000.000.000-00`), Senha, Link "Primeiro Acesso", Link "Esqueci minha senha".
* **Campos (Equipe):** Usuário (formato `operador-[nome]` ou `admin`), Senha.
* **Ações:** Botão Primário "Entrar no Sistema" (`.btn-primary`, `#2563eb`).
* **Mensagens:** Banner vermelho (`#fee2e2`, texto `#991b1b`) em caso de credenciais inválidas ou conta bloqueada por MED.

#### Tela 1.2: Modal de Primeiro Acesso
* **Objetivo:** Validação do responsável e ativação de senha.
* **Fluxo em 2 Etapas:**
  1. Solicitação do CPF; consulta na base e disparo de código WhatsApp de 6 dígitos.
  2. Digitação do código de 6 dígitos com contagem regressiva de 60 segundos para reenvio.
* **Sucesso:** Abre imediatamente o Modal de Termos e Regras da Cantina.

#### Tela 1.3: Modal de Termos e Regras da Cantina
* **Objetivo:** Garantir ciência das regras operacionais da escola.
* **Informações Exibidas:** Texto com regras de corte (08:00 e 13:30), política de ausência do aluno (alimento não retirado permanece cobrado) e funcionamento pré-pago da carteira.
* **Ação:** Checkbox obrigatório "Li e concordo com os termos da cantina", campo de definição de senha (mínimo 6 dígitos) e confirmação de senha. Botão Verde "Concluir Cadastro e Acessar" (`.btn-success`, `#16a34a`).

#### Tela 1.4: Recuperação de Senha
* **Objetivo:** Restabelecer credenciais do responsável.
* **Campos:** Digitação do CPF cadastrado.
* **Ação:** Envio de código transacional no WhatsApp e tela para cadastro da nova senha.

---

### Módulo 2: Portal da Família

#### Tela 2.1: Painel Geral da Família (Dashboard)
* **Objetivo:** Visão consolidada da carteira e dos dependentes.
* **Usuários:** Responsável.
* **Elementos:**
  * **Card de Saldo Familiar:** Saldo Atual em destaque (`22px`, negrito tabular), Saldo Reservado em agendamentos futuros e Saldo Disponível para novas compras.
  * **Alerta Visual de Saldo Baixo:** Card amarelo (`#fef9c3`, borda `#ca8a04`) quando o saldo for inferior a R$ 15,00.
  * **Banner de Bloqueio MED:** Exibido em vermelho caso a família esteja bloqueada por contestação bancária, com orientação para regularização.
  * **Lista de Dependentes:** Cartões compactos de cada filho com nome, série/turma, turno e badges com os alérgenos cadastrados.
  * **Ações Rápidas:** Botão Verde "Recarregar Saldo (Pix)", Botão Azul "Agendar Lanches da Semana", Botão Neutro "Ver Extrato Completo".

#### Tela 2.2: Tela de Recarga Pix Dinâmico
* **Objetivo:** Inserção de créditos imediatos na carteira.
* **Campos:** Campo de valor numérico em reais (com prefixo `R$`), validação de valor mínimo (R$ 10,00) e botões de atalho rápido (+R$ 20, +R$ 50, +R$ 100).
* **Exibição Pós-Confirmação:** QR Code dinâmico renderizado em tela, código alfanumérico "Pix Copia e Cola" com botão de cópia com um clique e cronômetro de validade (15 minutos).
* **Feedback:** Atualização em tempo real via polling/SSE para confirmação com modal verde de sucesso assim que o webhook liquidar o pagamento.

#### Tela 2.3: Tela de Agendamento Semanal
* **Objetivo:** Escolha de lanches da semana corrente.
* **Elementos:**
  * Seletor do Aluno (caso haja mais de um dependente);
  * Abas de navegação dos dias letivos da semana corrente (Segunda a Sexta-feira); dias bloqueados no calendário aparecem em cinza com etiqueta "Recesso" ou "Feriado";
  * Alerta de Horário de Corte: Indicador destacado do tempo restante para o encerramento do turno (08:00 para manhã e 13:30 para tarde);
  * Catálogo de Alimentos em grade com foto miniatura, título, preço em reais e tags de alérgenos;
  * **Itens Esgotados:** Exibidos com opacidade atenuada (`0.5`), fundo acinzentado, badge "Esgotado" e clique desabilitado;
  * **Modal de Aviso de Alérgenos:** Caso o pai selecione item conflitante com restrição do filho, exibe modal de advertência com triângulo amarelo centralizado (`#eab308`) e texto: *"Atenção: Este produto contém [Alérgeno], incompatível com o cadastro de [Nome do Aluno]. Deseja realmente incluir este item?"*, com botões "Cancelar" e "Confirmar Inclusão".
  * Barra inferior fixa de resumo com total do pedido e botão "Concluir Agendamento".

#### Tela 2.4: Tela de Meus Pedidos e Edição
* **Objetivo:** Acompanhamento e alteração de compras agendadas.
* **Listagem:** Tabela agrupada por data e aluno com status (*Agendado*, *Entregue*, *Cancelado*).
* **Ações:**
  * Botão "Editar Itens" e Botão "Cancelar Pedido" ativos exclusivamente antes do horário de corte (08:00 ou 13:30).
  * Após o horário de corte, os botões são substituídos pelo status *"Em Preparação / Corte Fechado"*.

#### Tela 2.5: Gestão de Restrições Alimentares dos Filhos
* **Objetivo:** Parametrização preventiva de saúde.
* **Interface:** Checkbox listando todos os alérgenos padronizados para cada aluno com botão de gravação imediata.

---

### Módulo 3: Operação de Encomendas e Fornecedores

#### Tela 3.0: Dashboard Operacional do Turno
* **Objetivo:** Painel de controle inicial e visão unificada dos indicadores operacionais em tempo real para a equipe da cantina.
* **Usuários:** Operador e Administrador.
* **Filtros e Controles de Topo:** Alternador rápido entre Turno Matutino e Turno Vespertino; exibição da data corrente com indicador de status da sessão de caixa (Aberto / Fechado).
* **Cards de Indicadores Operacionais (Métricas em Destaque):**
  * **Card 1 (Tickets a Imprimir):** Total de lanches agendados no turno aguardando impressão física, com link de atalho direto para a Tela de Impressão em Lote.
  * **Card 2 (Resumo do Fornecedor):** Somatório consolidado de salgados demandados para o corte do turno, com link para o Painel de Corte e status de envio (Pendente / Enviado / Recebido).
  * **Card 3 (Alerta de Estoque Mínimo):** Quantidade de itens cujo saldo físico atingiu ou ficou abaixo do ponto de segurança (`estoque_minimo`), com badge amarelo de advertência e link para o Painel de Estoque.
  * **Card 4 (Status do Caixa do Turno):** Informação visual do caixa atual (Aberto com Fundo de Troco R$ X,XX / Fechado), com total acumulado em dinheiro e Pix no turno e botão de ação rápida para Abertura ou Fechamento.
* **Ações Rápidas de Navegação:** Botões de acesso direto ao PDV de Balcão, Conferência de Recebimento de Salgados e Extrato Rápido do Aluno.

#### Tela 3.1: Painel de Corte do Turno
* **Objetivo:** Consolidação quantitativa dos salgados encomendados.
* **Usuários:** Operador e Administrador.
* **Horários de Acionamento:** Pontualmente às 08:00 (matutino) e às 13:30 (vespertino).
* **Elementos:**
  * Indicador de status do corte (Aberto / Fechado);
  * Tabela de alta densidade listando: Código, Nome do Salgado, Categoria, Quantidade Total Encomendada;
  * Rodapé com somatório total de peças;
  * Botão Primário "Copiar para WhatsApp": transfere para a área de transferência a mensagem pronta para envio ao fornecedor;
  * Botão Secundário "Exportar Pedido Fornecedor (PDF)": emite relatório A4 timbrado com cabeçalho da cantina.

#### Tela 3.2: Conferência e Recebimento de Fornecedor
* **Objetivo:** Entrada física de mercadorias e liquidação da demanda de tickets.
* **Campos:** Tabela pré-preenchida com os salgados do corte, coluna de quantidade esperada e coluna editável de "Quantidade Efetivamente Entregue".
* **Ação:** Botão Verde "Confirmar Recebimento e Dar Entrada".
* **Comportamento:** Atualiza os saldos físicos no estoque e converte a demanda de tickets de "Pendente" para "Liquidada/Disponível".

---

### Módulo 4: Impressão e Gestão de Tickets

#### Tela 4.1: Impressão em Lote do Turno
* **Objetivo:** Geração dos blocos de cupons térmicos para sala de aula.
* **Filtros:** Turno (Matutino / Vespertino) e Data.
* **Tabela de Pré-visualização:** Agrupamento ordenado por Série e Turma, listando quantidade de alunos e tickets a serem impressos.
* **Ação:** Botão de Destaque "Imprimir Lote do Turno (Bobina 80mm)". Dispara a geração contínua do fluxo de impressão térmica com quebra de página por ticket e separador por turma.
* **Padrão Técnico de Impressão Térmica:** A impressão contínua em bobina de 80mm é suportada nativamente através de folha de estilo CSS `@media print` configurada com `@page { size: 80mm auto; margin: 0; }`, permitindo disparo direto pelo diálogo do navegador (`window.print()`), ou alternativamente via download de arquivo PDF contínuo renderizado pelo `PdfService`.

#### Tela 4.2: Consulta de Pedidos do Dia e 2ª Via
* **Objetivo:** Localizar tickets de alunos que perderam o papel antes do recreio.
* **Filtros de Busca Rápida:** Campo de texto com busca instantânea por Nome do Aluno, Matrícula ou Turma.
* **Ações:** Botão de Ação Rápida "Reimprimir 2ª Via".
* **Comportamento:** Abre modal solicitando a justificativa obrigatória do atendente (ex: "Ticket extraviado pelo estudante em sala"), grava a ocorrência no log de auditoria e emite cupom idêntico contendo a inscrição destacada `[2ª VIA]`.

---

### Módulo 5: Ponto de Venda (PDV) e Balcão

#### Tela 5.1: Terminal Único de PDV / Vendas Avulsas
* **Objetivo:** Atendimento de balcão para itens sobressalentes e colaboradores.
* **Layout:** Dividido em duas colunas:
  * **Coluna Esquerda:** Grade de produtos rápidos com filtro por categoria e busca textual. Itens sem estoque físico aparecem desabilitados em cinza.
  * **Coluna Direita (Cupom de Venda):** Lista de itens adicionados, quantidade, subtotal e total geral.
* **Seção de Identificação:**
  * Opção 1: Venda Anônima Rápida (padrão, sem identificação);
  * Opção 2: Identificar Aluno (busca rápida por nome/turma; checagem instantânea de alérgenos com alerta em modal amarelo);
  * Opção 3: Identificar Colaborador / Professor (busca por matrícula ou nome; exibição da foto e cargo cadastrados para confirmação visual obrigatória).
* **Painel de Pagamento:** Botões de atalho: "Dinheiro" (abre campo de valor recebido e calcula troco em tempo real), "Pix Balcão" e "Desconto em Folha" (habilitado apenas se colaborador selecionado).
* **Ação:** Botão Verde "Concluir Venda". Baixa estoque físico imediatamente.
* **Pós-Venda:** Modal rápido com valor do troco e botão opcional "Imprimir Comprovante Térmico".

#### Tela 5.2: Modal de Estorno de Balcão
* **Objetivo:** Cancelar ticket de papel no balcão por falta de produto.
* **Campos:** Digitação do código do ticket, seleção do motivo (*Falta de Ingrediente/Produto*, *Recusa de Substituição*, *Avaria no Lanche*) e justificativa.
* **Ação:** Botão Vermelho "Confirmar Estorno e Devolver Crédito". O sistema credita imediatamente o valor integral na carteira familiar e cancela o ticket.

#### Tela 5.3: Aproveitamento de Sobras de Alunos Faltantes
* **Objetivo:** Liberar itens de alunos ausentes para o PDV após o recreio.
* **Ação:** O operador seleciona os pedidos não retirados e clica em "Transferir para Venda Avulsa", gerando entrada de estoque de sobressalentes.

#### Tela 5.4: Modal de Recarga em Dinheiro no Balcão
* **Objetivo:** Inserção presencial de créditos na carteira familiar mediante pagamento em dinheiro físico no balcão da cantina ou retaguarda.
* **Usuários:** Operador (apenas com sessão de caixa do turno aberta) e Administrador.
* **Condição Obrigatória:** Validação de sessão de caixa aberta para o turno corrente. Caso o caixa esteja fechado, a operação é bloqueada para o Operador, exibindo alerta explicativo.
* **Campos do Modal:**
  * Campo de busca rápida do Responsável por CPF (com máscara) ou Nome do Aluno dependente;
  * Exibição do Titular localizado, dependentes matriculados e Saldo Atual da carteira em destaque tabular;
  * Campo de valor em dinheiro recebido (com prefixo monetário `R$`);
  * Campo opcional de observação.
* **Ações:** Botão Verde "Confirmar Recarga em Dinheiro".
* **Comportamento do Sistema:**
  * Credita imediatamente o valor integral em `responsaveis.saldo_atual`;
  * Incrementa o totalizador `sessoes_caixa.total_dinheiro_recargas` da sessão de caixa aberta;
  * Registra a movimentação em `transacoes_carteira` com `tipo_transacao = 'recarga_dinheiro'`, gravando `operador_id` para auditoria;
  * Exibe modal de confirmação com botão opcional "Imprimir Comprovante Térmico (80mm)" para entrega física ao responsável.

---

### Módulo 6: Gestão de Estoque e XML

#### Tela 6.1: Painel de Saldos e Controle de Estoque
* **Objetivo:** Gestão centralizada de saldos físicos e reservas.
* **Colunas da Tabela:** Código, Descrição, Categoria, Tipo (Diário / Contínuo), Saldo Físico, Saldo Reservado Online, Saldo Disponível para Venda, Ponto de Estoque Mínimo, Status.
* **Ações Rápidas por Linha:**
  * Botão Switch ("Ativo / Pausado") para ocultação imediata do cardápio online;
  * Botão Ícone Lápis para edição de estoque mínimo e preços (restrito a Administrador).

#### Tela 6.2: Importação de XML de NF-e
* **Objetivo:** Entrada de mercadorias industrializadas por nota fiscal.
* **Usuários:** Administrador exclusivamente.
* **Etapas:**
  1. Upload do arquivo `.xml` da nota fiscal emitida pelo fornecedor;
  2. Parse automático dos dados: CNPJ do fornecedor, número da nota e relação de produtos;
  3. Tabela de Mapeamento: O sistema associa os itens da nota aos produtos cadastrados e solicita o Fator Multiplicador de Embalagem (ex: 1 fardo com 12 unidades = fator 12);
  4. Botão "Confirmar Entrada de Nota Fiscal". Grava o cabeçalho em `notas_fiscais_xml`, os itens mapeados em `itens_nota_fiscal_xml`, atualiza o saldo físico dos produtos em `produtos.estoque_atual` e gera os lançamentos correspondentes em `estoque_movimentacoes`.

#### Tela 6.3: Lançamento Manual de Perdas
* **Objetivo:** Baixa física de itens avariados ou vencidos.
* **Campos:** Seleção do Produto, Quantidade Baixada, Seleção Obrigatória do Motivo (*Avaria/Descarte*, *Vencimento*, *Sobra de Produção / Não Retirado*, *Ajuste de Contagem*) e Justificativa textual.
* **Ação:** Botão "Registrar Baixa de Perda". Grava no estoque e na auditoria.

#### Tela 6.4: Cadastro e Edição de Produtos (Catálogo Comercial)
* **Objetivo:** Inclusão e manutenção completa dos itens comercializados na cantina.
* **Usuários:** Administrador exclusivamente.
* **Campos do Formulário:**
  * Código Interno (identificador único, ex: `SAL-001`, `SUC-010`);
  * Descrição / Nome Comercial do Alimento (ex: "Coxinha de Frango com Catupiry");
  * Categoria (dropdown populado a partir da tabela `categorias`);
  * Tipo de Produto: Seleção por radio button entre *Salgado Diário* (demanda consolidada no corte) ou *Industrializado Contínuo* (estoque físico contínuo com reserva online);
  * Turno de Disponibilidade: Seleção entre *Matutino*, *Vespertino* ou *Ambos*;
  * Preço de Venda em Reais (com máscara monetária e alinhamento à direita);
  * Ponto de Estoque Mínimo (numérico inteiro, padrão 5 unidades);
  * Upload de Imagem do Produto (formatos `.jpg`, `.jpeg`, `.png`, `.webp`, limite de 10 MB, com renomeação criptográfica automática);
  * Restrições Alimentares / Alérgenos Contidos: Lista de checkboxes populada a partir da tabela `restricoes_alimentares` (Glúten, Lactose, Amendoim, Ovos, Soja, Frutos do Mar, Nozes, Corante Tartrazina), mapeando para a tabela associativa `produto_alergenos`.
* **Ações:** Botão Verde "Gravar Produto" e Botão Vermelho "Inativar / Excluir (Soft Delete)" com preenchimento de `deleted_at`.

#### Tela 6.5: Cadastro e Gestão de Categorias
* **Objetivo:** Classificação e ordenação dos grupos de alimentos no cardápio e no PDV.
* **Usuários:** Administrador exclusivamente.
* **Campos:** Nome da Categoria (ex: "Salgados Assados", "Bebidas e Sucos", "Sobremesas"), Ordem de Exibição (inteiro para ordenação visual) e Status (Ativo / Inativo).
* **Ações:** Botão "Salvar Categoria" e suporte a exclusão lógica via `deleted_at`.

---

### Módulo 7: Controle Financeiro e Caixas

#### Tela 7.1: Abertura de Caixa por Turno
* **Objetivo:** Liberar operações de balcão com controle de troco.
* **Campos:** Seleção do Turno (Matutino ou Vespertino) e Valor em Dinheiro do Fundo de Troco Inicial.
* **Ação:** Botão "Confirmar Abertura de Caixa".

#### Tela 7.2: Fechamento e Prestação de Contas de Caixa
* **Objetivo:** Fechamento dos caixas às 11:00 (manhã) e 16:00 (tarde).
* **Espelho de Conferência:**
  * Fundo de Troco Inicial: R$ X,XX
  * Recargas em Dinheiro: R$ X,XX
  * Vendas Avulsas em Dinheiro: R$ X,XX
  * Total em Dinheiro Esperado na Gaveta: R$ X,XX
  * Total de Entradas em Pix Balcão: R$ X,XX
* **Campo Operacional:** "Valor em Dinheiro Efetivamente Contado na Gaveta".
* **Cálculo Automático:** Exibição da Quebra de Caixa (falta) ou Sobra de Caixa em tempo real.
* **Ação:** Botão "Encerrar Sessão de Caixa e Emitir Relatório". Emite espelho em PDF para assinatura do operador.

#### Tela 7.3: Ajustes Manuais e Quitação de Negativados
* **Objetivo:** Regularização administrativa de saldos.
* **Usuários:** Administrador exclusivamente.
* **Recursos:** Busca por responsável com saldo devedor gerado por MED, campo para quitação (após pagamento na secretaria) e desbloqueio manual da conta.

---

### Módulo 8: Relatórios Gerenciais e de RH

#### Tela 8.1: Extrato de Consumo por Aluno (Modelo Timbrado A4)
* **Objetivo:** Atendimento a pais para prestação de contas física no balcão da cantina ou pela administração.
* **Layout Visual:** Cabeçalho timbrado com logotipo circular oficial do Colégio Monteiro Lobato à esquerda, Razão Social, CNPJ e dados da escola; identificação do aluno (Nome, Matrícula, Turma, Turno); saldo atual; tabela analítica de consumo e recargas com data, hora, descrição do item, quantidade, valor e saldo resultante após a transação.
* **Filtros:** Período de Datas (De/Até) e Busca por Aluno.
* **Permissões:** Operador e Administrador.

#### Tela 8.2: Relatório de Vendas por Alimento
* **Objetivo:** Curva ABC e avaliação de vendas gerais (encomendas e vendas avulsas no PDV).
* **Colunas:** Código, Descrição, Categoria, Quantidade Vendida, Preço Médio e Faturamento Total no período.
* **Filtros:** Intervalo de datas, turno e categoria.
* **Permissões:** Administrador.

#### Tela 8.3: Relatório de Desconto em Folha (Colaboradores / RH)
* **Objetivo:** Envio mensal para dedução nos contracheques da instituição.
* **Colunas:** Matrícula Funcional, Nome Completo do Colaborador, Cargo, Quantidade de Lanches Consumidos e Valor Total a Descontar.
* **Formatos de Exportação:** Download simultâneo em PDF estruturado e arquivo CSV/Excel para importação no software de folha de pagamento da escola.
* **Permissões:** Administrador.

#### Tela 8.4: Relatório de Famílias com Saldo Devedor (Negativados MED)
* **Objetivo:** Acompanhamento de fraudes e contestações de Pix.
* **Colunas:** CPF do Responsável, Nome do Titular, WhatsApp de Contato, Nomes dos Alunos Vinculados, Data do Estorno Bancário e Valor Negativo em Aberto.
* **Formatos:** Tabela interativa com botão de quitação e exportação em PDF.
* **Permissões:** Administrador.

---

### Módulo 9: Administração, Cadastros e Calendário

#### Tela 9.1: Importação em Lote de Alunos e Turmas
* **Objetivo:** Carga inicial e virada de ano letivo via planilha (.xlsx / .csv).
* **Interface:** Upload de arquivo, tela de validação prévia de colunas (Nome Responsável, CPF, WhatsApp, Matrícula Aluno, Nome Aluno, Turma, Turno) e relatório de importação com resumo de novos cadastros e atualizações.

#### Tela 9.2: Gestão do Calendário Escolar
* **Objetivo:** Bloqueio de datas e cancelamentos emergenciais.
* **Visualização:** Calendário mensal interativo. Clique sobre um dia permite definir: *Letivo*, *Feriado*, *Recesso* ou *Férias*.
* **Ação Crítica de Cancelamento em Lote:** Botão "Suspender Dia Letivo". Exige confirmação explícita de senha do Administrador; aciona rotina que cancela todos os agendamentos da data, invalida os tickets gerados e estorna automaticamente 100% dos valores para as carteiras de todas as famílias impactadas.

#### Tela 9.3: Gestão de Usuários da Equipe da Cantina
* **Objetivo:** Cadastro e manutenção das contas dos funcionários da cantina.
* **Campos:** Nome completo, e-mail, identificador de usuário (`operador-[nome]` ou `admin`), senha e perfil (`operador` ou `administrador`), com suporte a inativação/soft delete.

#### Tela 9.4: Configurações Globais e Dados da Empresa
* **Objetivo:** Parametrização institucional.
* **Campos:** Razão Social, Nome Fantasia, CNPJ, Telefone WhatsApp de suporte da cantina, upload do arquivo de logotipo (`assets/img/logo-colegio.jpg`), valor mínimo de recarga Pix (padrão R$ 10,00) e gatilho de saldo baixo (padrão R$ 15,00).

#### Tela 9.5: Gestão e Cadastro de Colaboradores (Professores e Funcionários)
* **Objetivo:** Manutenção dos profissionais autorizados ao consumo na modalidade "Desconto em Folha".
* **Usuários:** Administrador exclusivamente.
* **Campos do Formulário:**
  * Matrícula Funcional (única na instituição);
  * Nome Completo do Colaborador;
  * Cargo / Função (ex: "Professor de Matemática", "Coordenadora Pedagógica", "Inspetor de Pátio");
  * Upload da Foto de Identificação (obrigatória para exibição no PDV de balcão e prevenção de fraudes presenciais);
  * Status (Ativo / Inativo).
* **Ações:** Botão "Gravar Colaborador", Botão de Inativação e Botão de Exclusão Lógica (`deleted_at`).

#### Tela 9.6: Consulta e Inativação de Alunos
* **Objetivo:** Manutenção individual de cadastros de estudantes, troca de turmas e inativação de alunos desligados ou formados.
* **Usuários:** Administrador exclusivamente.
* **Filtros e Busca:** Campo de busca por Nome do Aluno, Matrícula ou Responsável, e filtros por Série e Turma.
* **Ações Operacionais:**
  * Edição de dados cadastrais e reatribuição de turma ou turno escolar;
  * Botão de Inativação Lógica (`ativo = 0` e `deleted_at` preenchido): Bloqueia imediatamente novos agendamentos online para o aluno, preservando 100% de seus vínculos históricos com pedidos anteriores, tickets e extratos familiares;
  * Botão de Reativação para alunos com matrículas renovadas.

---

## 13. Fluxos Funcionais

### Fluxo 1: Primeiro Acesso e Ativação do Responsável
1. O responsável acessa a URL pública da cantina no navegador e clica no link "Primeiro Acesso".
2. O sistema exibe o campo para digitação do CPF com máscara.
3. O responsável digita o CPF e submete o formulário.
4. O sistema consulta a tabela `responsaveis`. Se o CPF existir e não possuir senha definitiva, gera um código aleatório numérico de 6 dígitos (validade de 10 minutos) e aciona o `WhatsAppService` para envio ao número cadastrado.
5. O sistema apresenta a tela de verificação de código.
6. O responsável digita o código de 6 dígitos.
7. O sistema valida o código. Se correto, exibe o Modal com os Termos e Políticas da Cantina.
8. O responsável lê as condições, marca o checkbox de aceite, digita sua senha e confirmação e clica em "Concluir Cadastro".
9. O sistema registra o timestamp em `termos_aceitos_em`, criptografa a senha via `password_hash()`, inicia a sessão e redireciona o usuário para o Portal da Família.

### Fluxo 2: Recarga da Carteira Familiar via Pix Dinâmico
1. No Portal da Família, o responsável clica no botão "Recarregar Saldo".
2. O sistema abre o modal solicitando o valor desejado.
3. O responsável preenche o valor (mínimo R$ 10,00) e clica em "Gerar Pix".
4. O `CarteiraController` aciona o `PixService`, que se comunica com o gateway bancário e retorna o QR Code em base64 e a string "Copia e Cola", com validade de 15 minutos.
5. O sistema exibe o QR Code e o código alfanumérico com botão de cópia.
6. O responsável realiza o pagamento no aplicativo do seu banco.
7. O gateway bancário notifica o endpoint `WebhookController` via requisição `POST` segura.
8. O `WebhookController` valida a assinatura do webhook, confere a idempotência da transação e registra a entrada contábil na tabela `transacoes_carteira`.
9. O saldo do responsável em `responsaveis.saldo_atual` é incrementado no valor integral sem desconto de tarifas.
10. O sistema aciona o `WhatsAppService` e envia notificação de confirmação do pagamento no WhatsApp do responsável.

### Fluxo 3: Agendamento Semanal de Lanches pelo Responsável
1. O responsável clica em "Agendar Lanche" no Portal da Família.
2. O sistema valida se o dia letivo está aberto (dias da semana seguinte são liberados toda sexta-feira às 18:00; dias da semana corrente permanecem liberados até os horários de corte).
3. O responsável seleciona o filho dependente e o dia desejado.
4. O sistema lista os produtos disponíveis para o turno do aluno (matutino ou vespertino).
5. O responsável adiciona os lanches. Se houver incompatibilidade entre os alérgenos do produto e os cadastrados no perfil do estudante, o sistema exibe o modal de alerta amarelo; o responsável confirma a ciência para prosseguir.
6. O responsável clica em "Confirmar Pedido".
7. O sistema valida atomicamente se `saldo_atual >= valor_total`.
8. O sistema realiza a reserva de estoque para itens industrializados (`estoque_reservado = estoque_reservado + quantidade`).
9. O sistema debita o valor da carteira, insere o registro em `pedidos`, detalha em `itens_pedido` e grava o débito em `transacoes_carteira`.
10. O agendamento é concluído com sucesso e exibido em "Meus Pedidos".

### Fluxo 4: Edição ou Cancelamento Autônomo pelo Responsável
1. O responsável acessa a tela "Meus Pedidos" antes do horário de corte (08:00 para manhã ou 13:30 para tarde).
2. **Cenário A: Cancelamento:**
   * O responsável clica em "Cancelar Pedido".
   * O sistema cancela o pedido (`status = 'cancelado'`), estorna 100% do valor para `responsaveis.saldo_atual`, registra o estorno em `transacoes_carteira` e libera o estoque reservado de volta para o estoque disponível.
3. **Cenário B: Edição:**
   * O responsável clica em "Editar Pedido" e altera itens do cardápio.
   * Se o novo total for superior, o sistema valida e debita a diferença da carteira;
   * Se o novo total for inferior, o sistema estorna a diferença imediatamente na carteira;
   * Produtos removidos têm suas reservas de estoque liberadas instantaneamente.
4. Ao atingir o horário de corte pontual (08:00 ou 13:30), o sistema bloqueia os botões de ação e impede qualquer alteração.

### Fluxo 5: Corte Operacional, Envio ao Fornecedor e Recebimento
1. Ao atingir 08:00 (matutino) ou 13:30 (vespertino), o sistema consolida todos os agendamentos do turno.
2. O operador acessa o "Painel de Corte do Turno" na cantina.
3. O sistema exibe o quantitativo exato de cada salgado demandado.
4. O operador clica em "Copiar para WhatsApp" e envia o pedido ao fornecedor parceiro.
5. O fornecedor entrega a remessa física de salgados na cantina.
6. O operador acessa "Conferência e Recebimento", confere as quantidades físicas e clica em "Confirmar Recebimento".
7. O sistema grava a entrada em `estoque_movimentacoes` e liquida simultaneamente a demanda dos tickets emitidos. Salgados excedentes entregues pelo fornecedor ficam disponíveis para a venda avulsa.

### Fluxo 6: Impressão e Distribuição dos Tickets por Turma
1. Após o horário de corte, o operador acessa "Impressão de Tickets".
2. O operador seleciona o turno e clica em "Imprimir Lote do Turno".
3. O sistema busca todos os pedidos ativos do turno, ordena as informações por `turmas.ordem_impressao`, `turmas.nome_turma` e `alunos.nome`, gerando o layout para bobina contínua de 80mm com tag de alérgenos em destaque.
4. A impressora térmica imprime o bloco sequencial com divisores visuais entre as turmas.
5. O operador destaca os lotes e entrega cada bloco ao professor responsável antes do recreio.
6. O professor distribui os tickets aos alunos em sala de aula.

### Fluxo 7: Atendimento no Recreio e Tratamento de Sobras
1. No intervalo escolar, o aluno entrega o ticket impresso no balcão da cantina.
2. O atendente confere o nome do lanche visualmente, entrega o produto e descarta o papel na lixeira (sem operar computador na fila).
3. **Tratamento de 2ª Via:** Se um aluno perder o ticket antes do recreio, o atendente pesquisa o nome do aluno na tela de consulta, clica em "Reimprimir 2ª Via", informa a justificativa e imprime o cupom com a inscrição `[2ª VIA]`, gravando a operação em `logs_auditoria`.
4. **Tratamento de Alunos Faltantes:** Ao término do intervalo, salgados não retirados permanecem faturados para a família. O operador pode transferi-los para venda avulsa no PDV via "Aproveitamento de Sobra de Encomenda" ou baixá-los no módulo de perdas como "Sobra de Produção / Não Retirado".

### Fluxo 8: Venda Avulsa e Consumo de Colaboradores no PDV
1. O cliente comparece ao balcão solicitando lanches sobressalentes.
2. O atendente seleciona os produtos no PDV.
3. **Se for Venda a Aluno:** O atendente seleciona pagamento em Dinheiro (digita o valor recebido e o sistema exibe o troco) ou Pix no balcão. Caso pesquise o aluno, o sistema valida restrições alimentares preventivamente.
4. **Se for Venda a Professor / Colaborador:** O atendente pesquisa o nome ou matrícula funcional, confere a foto e cargo exibidos em tela e seleciona a opção "Desconto em Folha".
5. O atendente clica em "Concluir Venda".
6. O sistema grava a transação em `vendas_balcao` e `itens_venda_balcao`, debita o estoque físico imediatamente, registra a movimentação e encerra a venda, disponibilizando botão para emissão opcional de comprovante.

### Fluxo 9: Abertura e Fechamento de Caixa por Turno
1. No início do turno, o operador acessa "Caixa > Abertura", digita o valor do Fundo de Troco e inicia a sessão.
2. O sistema vincula todas as vendas avulsas e recargas em dinheiro àquela sessão de caixa aberta.
3. Às 11:00 (matutino) ou às 16:00 (vespertino), o operador acessa "Caixa > Fechamento".
4. O sistema apresenta o espelho contábil com o total esperado na gaveta.
5. O operador conta as cédulas e moedas físicas e digita o valor total apurado.
6. O sistema calcula automaticamente eventuais sobras ou quebras de caixa, grava o encerramento em `sessoes_caixa` e gera o relatório formal em PDF para conferência e assinatura.

### Fluxo 10: Importação de Estoque via XML de NF-e
1. O Administrador acessa "Estoque > Entrada via XML".
2. O Administrador realiza o upload do arquivo `.xml` da nota fiscal emitida pelo distribuidor.
3. O `XmlParserService` lê a chave de 44 dígitos, o CNPJ e os itens da nota.
4. A tela de conferência exibe a correspondência com produtos cadastrados e solicita o fator de conversão de embalagem em unidades.
5. O Administrador confirma a operação.
6. O sistema armazena o XML de forma protegida em `storage/uploads/private/`, grava os dados de cabeçalho em `notas_fiscais_xml`, detalha os itens e regras em `itens_nota_fiscal_xml`, incrementa os saldos físicos dos produtos em `produtos.estoque_atual` e gera os registros correspondentes em `estoque_movimentacoes`.

### Fluxo 11: Tratamento de Contestação Pix (Mecanismo MED) e Bloqueio
1. O gateway bancário envia notificação de webhook indicando a contestação/estorno de uma recarga Pix pelo Mecanismo Especial de Devolução (MED).
2. O `WebhookController` intercepta o evento, identifica o responsável correspondente e debita o valor contestado da carteira, gravando em `transacoes_carteira` como `debito_contestacao_med`.
3. O sistema avalia o saldo resultante em `responsaveis.saldo_atual`:
   * Se o saldo for menor que zero (`saldo_atual < 0`), ativa a flag `bloqueado_med = 1` no cadastro do responsável;
   * O bloqueio impede imediatamente a criação de novos agendamentos para todos os alunos dependentes vinculados àquele responsável;
   * O sistema aciona o `WhatsAppService` e envia notificação urgente no WhatsApp do responsável comunicando a contestação bancária e a suspensão preventiva da conta.
4. O Administrador acompanha os inadimplentes pelo "Relatório de Negativados" e realiza a baixa manual e desbloqueio após a quitação presencial na secretaria da escola.

---

## 14. Validações e Regras de Negócio

### Agendamento, Prazos e Cancelamento
* **RN-01 (Janela de Abertura Semanal):** O agendamento da semana seguinte abre impreterivelmente toda sexta-feira às 18:00, disponibilizando os dias letivos da semana seguinte.
* **RN-02 (Horários de Corte Rigorosos):** O encerramento definitivo de pedidos, edições e cancelamentos ocorre às 08:00 para o turno matutino e às 13:30 para o turno vespertino da data agendada.
* **RN-03 (Cancelamento com Estorno Integral):** Cancelamentos efetuados pelo portal até o horário de corte estornam 100% do valor imediatamente na carteira familiar e liberam o estoque reservado.
* **RN-04 (Edição de Pedidos):** Edições antes do corte realizam a compensação financeira imediata: novos itens mais caros debitam a diferença da carteira; novos itens mais baratos estornam a diferença na hora. Itens removidos voltam imediatamente ao estoque disponível para outros pais.
* **RN-05 (Ausência do Aluno e Sobras):** Lanches agendados e não cancelados até o horário de corte não são reembolsados caso o aluno falte. Os itens permanecem faturados e podem ser aproveitados para venda avulsa ou baixados como perda.
* **RN-06 (Cancelamento Emergencial de Dia Letivo):** A suspensão de uma data no calendário pelo Administrador anula todos os pedidos do dia, invalida os tickets e devolve 100% do saldo a todas as famílias afetadas de forma automática.

### Carteira Familiar, Pix e Segurança Financeira
* **RN-07 (Compartilhamento Familiar):** O saldo pertence ao responsável titular e é compartilhado entre todos os dependentes matriculados.
* **RN-08 (Recarga Mínima e Isenção de Taxas):** O valor mínimo para recarga online via Pix é de R$ 10,00. Nenhuma taxa é descontada do cliente (R$ 10,00 pagos = R$ 10,00 de crédito).
* **RN-09 (Modelo Estritamente Pré-pago para Alunos):** Alunos só compram se houver saldo suficiente na carteira ou pagamento à vista (dinheiro ou Pix balcão). Não é permitida a criação manual de saldo negativo ("fiado") para estudantes.
* **RN-10 (Tratamento de Contestações / MED):** Contestações bancárias debitam o valor contestado da carteira. Saldo negativo gera bloqueio automático de novos agendamentos para todos os dependentes e disparo de notificação no WhatsApp.

### Estoque e Compras
* **RN-11 (Disponibilidade por Turno):** Cada produto deve possuir classificação explícita de turno: *Matutino*, *Vespertino* ou *Ambos*.
* **RN-12 (Reserva de Estoque Industrializado):** Produtos contínuos têm sua quantidade disponível abatida no ato do agendamento online.
* **RN-13 (Trava de Estoque Zerado):** Produtos com quantidade disponível igual a zero ficam visíveis em cinza-escuro (`opacity: 0.5`) e bloqueados para clique no portal e no PDV.
* **RN-14 (Ciclo de Salgados):** Salgados são demandados no corte (08:00 e 13:30). A entrada física e a liquidação da demanda de tickets ocorrem simultaneamente no clique de "Confirmar Recebimento".
* **RN-15 (Motivo Obrigatório de Perda):** Toda baixa manual de estoque fora de venda exige motivo padronizado (*Avaria/Descarte*, *Vencimento*, *Sobra de Produção / Não Retirado*, *Ajuste de Contagem*) e identificação do operador logado.

### Restrições Alimentares e Balcão
* **RN-16 (Alerta Visual de Restrição):** A seleção de produto com alérgeno incompatível com o dependente exige confirmação em modal explicativo antes da inclusão no pedido.
* **RN-17 (Tag Obrigatória no Ticket):** Tickets de produtos conflitantes com o cadastro do aluno devem conter a inscrição destacada `[ATENÇÃO: CONTÉM ...]`.
* **RN-18 (Restrições no PDV de Balcão):** Na venda avulsa, a identificação do aluno é opcional. Se informada, o sistema valida restrições e emite alerta visual.
* **RN-19 (Entrega Visual):** A entrega no recreio baseia-se unicamente no recolhimento e descarte do ticket de papel, sem manuseio de terminal na fila.
* **RN-20 (Janela de Caixas e Dinheiro):** O caixa matutino fecha às 11:00 e o vespertino às 16:00. O recebimento de dinheiro físico exige sessão de caixa aberta.
* **RN-21 (Consumo de Colaboradores):** Vendas na modalidade "Desconto em Folha" exigem identificação obrigatória do funcionário com conferência visual de foto e cargo no PDV.

---

## 15. Autenticação e Sessão

### Tipo de Autenticação e Canais
* **Canal Família (Responsáveis):** Autenticação por CPF (11 dígitos, sem pontuação) e senha alfanumérica na tabela `responsaveis`.
* **Canal Equipe (Operadores e Administrador):** Autenticação por identificador nominal `usuario` (formato `operador-[nome]` ou `admin`) e senha criptografada na tabela `usuarios`.

### Fluxo de Primeiro Acesso e Validação por WhatsApp
* Ao informar o CPF na tela de primeiro acesso, o sistema consulta a existência do registro na tabela `responsaveis`.
* Gera um código de verificação numérico de 6 dígitos e despacha via WhatsApp para o telefone cadastrado.
* A digitação correta do código dentro do prazo de 10 minutos desbloqueia a tela de aceite dos Termos da Cantina e cadastro de senha definitiva.

### Recuperação de Senha
* Processada pelo mesmo mecanismo: envio de código de 6 dígitos via WhatsApp para o telefone do responsável, permitindo redefinir a senha com segurança.

### Políticas de Proteção e Sessão
* **Armazenamento de Senhas:** Todas as senhas são armazenadas como hash seguro gerado via `password_hash($senha, PASSWORD_DEFAULT)` (Bcrypt ou Argon2id).
* **Mitigação de Fixação de Sessão:** Toda autenticação bem-sucedida executa obrigatoriamente `session_regenerate_id(true)`.
* **Tempo Limite de Sessão:** A sessão expira automaticamente após **60 minutos de inatividade** contínua.
* **Bloqueio por Tentativas Incorretas (Força Bruta):** O limite padrão é de **5 tentativas incorretas consecutivas** de login. Atingido o limite, o sistema bloqueia temporariamente autenticações para aquele usuário/IP pelo período de 15 minutos, gravando a ocorrência na tabela `logs_seguranca`.
* **Proteção CSRF:** Geração de token criptográfico único por sessão (`$_SESSION['csrf_token']`), cuja validação é obrigatória em todas as requisições que alterem dados (`POST`, `PUT`, `DELETE`).

---

## 16. Controle de Acesso

### Papéis e Estrutura de Menus
O sistema implementa **RBAC (Role-Based Access Control)** com menus e rotas segregados dinamicamente:

* **Menu do Responsável (Portal da Família):**
  * Início / Saldo da Carteira
  * Recarregar Saldo (Pix)
  * Agendar Lanches
  * Meus Pedidos
  * Extrato de Consumo
  * Dados dos Dependentes e Alergias
  * Sair

* **Menu do Operador (Painel da Cantina):**
  * Dashboard Operacional do Turno
  * Encomendas e Fornecedores (Corte e Recebimento)
  * Impressão de Tickets (Lotes e 2ª Via)
  * Ponto de Venda (PDV de Balcão e Colaboradores)
  * Controle de Estoque e Perdas
  * Caixa do Turno (Abertura e Fechamento)
  * Extrato de Alunos no Balcão (Consulta Rápida)
  * Sair

* **Menu do Administrador (Gestão Completa):**
  * Acumula todos os itens do menu do Operador;
  * Gestão de Usuários da Equipe da Cantina (`usuarios`);
  * Importação Escolar (Alunos, Turmas e Responsáveis);
  * Catálogo de Produtos e Categorias;
  * Entrada de Estoque via XML de NF-e;
  * Calendário Escolar e Cancelamento em Lote;
  * Ajustes de Saldo e Negativados MED;
  * Relatórios Gerenciais (Vendas por Alimento, Extratos e RH);
  * Logs de Auditoria, Erros e Segurança;
  * Configurações da Empresa e Parâmetros Globais;
  * Sair.

### Validação de Acesso no Backend
* Cada Controller herda de uma classe base que verifica a sessão e o papel do usuário (`Auth::check()`, `RBAC::authorize('perfil')`).
* Caso um usuário tente acessar diretamente uma rota não autorizada via digitação de URL no navegador, o sistema aborta a requisição, grava o evento na tabela `logs_seguranca` e renderiza uma página de erro HTTP 403 padronizada de acordo com o Design System (*"Acesso Negado: Você não possui permissão para acessar este recurso"*).

---

## 17. Auditoria e Histórico

### Auditoria Técnica nos Cadastros
Todas as tabelas de entidades mestres possuem rastreabilidade estrutural:
* `created_at`: Data e hora de criação do registro;
* `created_by`: Identificador do usuário/operador criador;
* `updated_at`: Data e hora da última modificação;
* `updated_by`: Identificador do usuário que realizou a última alteração.

### Trilha de Auditoria de Negócio (`logs_auditoria`)
As seguintes operações manuais e discricionárias exigem gravação obrigatória na tabela `logs_auditoria`, incluindo identificação do operador (`operador_id` referenciando `usuarios`), data/hora, endereço IP e texto explicativo de justificativa:
1. **Estorno Manual de Balcão:** Justificativa da devolução do valor do ticket (ex: falta de produto no momento do recreio);
2. **Emissão de 2ª Via de Cupom Térmico:** Justificativa da reimpressão do ticket (ex: perda física pelo aluno em sala);
3. **Lançamento Manual de Perdas de Mercadoria:** Motivo e justificativa do descarte ou quebra de itens de estoque;
4. **Ajuste Manual de Saldo e Quitação:** Justificativa da alteração de saldo financeiro de famílias pelo Administrador;
5. **Suspensão Emergencial de Dia Letivo:** Motivo do cancelamento em lote das compras da escola.

A tela de consulta aos logs de auditoria é de visualização exclusiva do perfil Administrador, apresentando filtros por operador, ação e período.

---

## 18. Soft Delete e Exclusões

### Entidades Abrangidas
O mecanismo de exclusão lógica (**Soft Delete**) através do preenchimento do campo temporal `deleted_at` é aplicado obrigatoriamente nas seguintes entidades:
* `produtos`
* `categorias`
* `colaboradores`
* `turmas`
* `alunos`
* `usuarios` (operadores e administradores da equipe da cantina)

### Regras de Operação e Integridade Contábil
* **Preservação de Vínculos:** Nenhuma entidade que possua relacionamento contábil, pedido histórico, ticket emitido ou movimentação de caixa anterior pode ser excluída fisicamente do banco de dados (bloqueio por integridade referencial `ON DELETE RESTRICT`).
* **Permissão de Exclusão e Restauração:** Somente o perfil Administrador pode executar a exclusão lógica e a restauração de registros.
* **Comportamento das Consultas:** Todas as rotinas operacionais, listagens de cardápio, buscas do PDV e seletores de agendamento incorporam obrigatoriamente o filtro `WHERE deleted_at IS NULL`.
* **Visualização Administrativa:** O Administrador dispõe de um filtro "Exibir Inativos / Excluídos" no catálogo de produtos, colaboradores e usuários, permitindo visualizar registros arquivados e restaurá-los mediante ação explícita.

---

## 19. Logs do Sistema

### 1. Log de Erros
* **Eventos Registrados:** Exceções não tratadas de PHP, erros de sintaxe capturados em runtime, falhas de execução de queries PDO, falhas de geração de PDF e erros de integração com APIs externas.
* **Informações Gravadas:** Nível da ocorrência (`error` ou `critical`), mensagem detalhada do erro, arquivo de origem, número da linha, stack trace completo, URL da requisição HTTP, usuário autenticado e endereço IP.
* **Mensagem Segura ao Usuário:** A aplicação intercepta o erro através da classe `ErrorHandler`. O usuário visualiza apenas uma mensagem amigável no padrão visual do Design System (*"Ocorreu uma falha no processamento. Nossa equipe já foi notificada. Código do erro: [UUID]"*), sem nunca expor senhas, queries SQL ou caminhos de arquivos.
* **Consulta:** Painel restrito ao Administrador com paginação e busca por nível e data.

### Estratégia de Contingência do Log de Erros em Arquivo
Caso o banco de dados MySQL esteja indisponível, a conexão falhe ou o próprio comando de inserção em `logs_erros` dispare uma exceção, o sistema ativa imediatamente o mecanismo de fallback:
* Gravação da ocorrência em arquivo de texto formatado: `storage/logs/error.log`;
* Formato da linha de log: `[YYYY-MM-DD HH:MM:SS] [NIVEL] [IP] [USUARIO] Mensagem no arquivo X linha Y | URL: /rota`;
* A pasta `storage/logs/` é blindada contra qualquer acesso web direto;
* Rotação automática de arquivos caso o log ultrapasse 5 MB.

### 2. Log de Segurança
* **Eventos Registrados:** Tentativas de login inválidas com identificação do CPF/usuário informado; bloqueios automáticos por limite de tentativas; tentativas de acesso a URLs restritas (violação de RBAC); eventos de webhook de contestação bancária Pix MED recebidos; e alterações cadastrais sensíveis.
* **Armazenamento:** Persistido na tabela `logs_seguranca` e espelhado em arquivo texto `storage/logs/security.log` para conferência de incidentes.

---

## 20. Configurações Globais

### Parâmetros Operacionais e Institucionais (Banco de Dados)
Os parâmetros de negócio são mantidos no registro único da tabela `configuracoes_globais`:
* `razao_social`: Razão Social da empresa da cantina;
* `nome_fantasia`: Nome Fantasia exibido nas telas e cupons;
* `cnpj`: Cadastro Nacional da Pessoa Jurídica;
* `telefone_contato`: Telefone WhatsApp de atendimento e suporte;
* `logo_path`: Caminho do arquivo de logotipo institucional circular (`assets/img/logo-colegio.jpg`);
* `recarga_minima_pix`: Valor mínimo em reais para geração de Pix dinâmico (valor padrão: **R$ 10,00**);
* `alerta_saldo_baixo`: Gatilho financeiro em reais para emissão de alerta no portal e no WhatsApp (valor padrão: **R$ 15,00**);
* `timeout_sessao_minutos`: Tempo limite de inatividade da sessão de usuário (valor padrão: **60 minutos**);
* `limite_tentativas_login`: Quantidade máxima de falhas de autenticação antes do bloqueio temporário (valor padrão: **5 tentativas**);
* `itens_por_pagina`: Quantidade padrão de registros por página nas tabelas operacionais (valor padrão: **25 itens**).

Esses valores são editados exclusivamente pelo Administrador no menu de Configurações, contando com validação de tipos e valores mínimos.

### Configuração Técnica do Projeto (Arquivo em Código)
Para evitar riscos de segurança inerentes à exposição indevida de arquivos `.env` como texto puro em servidores web mal configurados, **não será utilizado arquivo `.env`**.

As credenciais de infraestrutura e chaves criptográficas são mantidas em arquivo PHP nativo:

`config/config.php`

Estrutura técnica do arquivo:
```php
<?php
defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

return [
    'app' => [
        'name'        => 'Cantina Web - Colégio Monteiro Lobato',
        'env'         => 'production', // 'development' ou 'production'
        'debug'       => false,
        'base_url'    => 'http://localhost/cantina', // Ajustável ao ambiente (XAMPP / Hostnet)
        'timezone'    => 'America/Sao_Paulo',
    ],
    'database' => [
        'driver'      => 'mysql',
        'host'        => 'localhost',
        'port'        => 3306,
        'database'    => 'cantina_db',
        'username'    => 'usuario_banco',
        'password'    => 'senha_forte_banco',
        'charset'     => 'utf8mb4',
        'collation'   => 'utf8mb4_unicode_ci',
    ],
    'pix_gateway' => [
        'provider'    => 'mercado_pago', // ou 'efi', 'asaas'
        'client_id'   => 'CHAVE_CLIENT_ID',
        'client_secret' => 'CHAVE_CLIENT_SECRET',
        'webhook_secret' => 'CHAVE_SECRETA_WEBHOOK',
    ],
    'whatsapp_api' => [
        'endpoint'    => 'https://api.provedor-whatsapp.com/v1/messages',
        'token'       => 'TOKEN_AUTENTICACAO_WHATSAPP',
        'sender'      => '5511999999999',
    ],
    'security' => [
        'master_key'  => 'CHAVE_CRIPTOGRAFICA_DE_SESSAO',
        'csrf_secret' => 'CHAVE_GERACAO_TOKENS_CSRF',
    ],
    'paths' => [
        'private_storage' => __DIR__ . '/../storage/uploads/private/',
        'public_storage'  => __DIR__ . '/../storage/uploads/public/',
        'logs_storage'    => __DIR__ . '/../storage/logs/',
    ]
];
```
O arquivo reside em pasta protegida por diretiva `.htaccess` (`Deny from all`) e é carregado internamente através de `require` encapsulado na classe de bootstrap da aplicação.

---

## 21. Uploads, Anexos e Arquivos

### Áreas de Aplicação de Arquivos
1. **Importação Escolar:** Carga anual de alunos, turmas e responsáveis via planilhas `.xlsx` ou `.csv`;
2. **Entrada de Estoque:** Notas fiscais eletrônicas de distribuidores em formato `.xml`;
3. **Logotipo Institucional:** Imagem da marca circular da instituição escolar (`.jpg`, `.jpeg`, `.png`, `.webp`);
4. **Fotos de Cadastro:** Fotos opcionais de identificação para alunos, colaboradores e produtos do cardápio (`.jpg`, `.jpeg`, `.png`, `.webp`).

### Regras de Validação e Segurança
* **Tamanho Máximo Permitido:** Limite rigoroso de até **10 MB** por arquivo submetido.
* **Extensões Aceitas:** Limitadas categoricamente a `.csv`, `.xlsx`, `.xml`, `.jpg`, `.jpeg`, `.png` e `.webp`. Qualquer outra extensão é rejeitada no upload.
* **Verificação de MIME Type e Assinatura Binária:** O backend não confia na extensão enviada pelo cliente. Executa checagem de tipo MIME real via `finfo_file()` e validação dos bytes mágicos de cabeçalho do arquivo, rejeitando arquivos executáveis ou scripts PHP mascarados com extensões falsas.
* **Segregação de Armazenamento:**
  * **Arquivos Privados (`storage/uploads/private/`):** Planilhas com dados pessoais da escola e arquivos XML de notas fiscais contêm dados fiscais e estão sujeitos à LGPD. São gravados em pasta com bloqueio total de leitura pública via `.htaccess`. O download só é possível via controller autenticado com perfil Administrador.
  * **Arquivos Públicos (`storage/uploads/public/`):** Imagens de produtos, fotos de alunos/colaboradores e logotipo. Ao serem gravadas, o nome original é descartado e substituído por hash criptográfico ou UUID aleatório (ex: `img_9f83b2a1c4e7.webp`).

---

## 22. Relatórios, Consultas e Exportações

### 1. Extrato de Consumo por Aluno (A4 Timbrado)
* **Objetivo:** Documento oficial de prestação de contas impresso para pais e responsáveis no balcão da cantina ou retaguarda.
* **Cabeçalho:** Logotipo circular oficial do Colégio Monteiro Lobato posicionado à esquerda (`48px` a `56px`), Razão Social, CNPJ, Telefone e Endereço da unidade escolar.
* **Dados do Aluno:** Nome Completo, Matrícula, Turma e Turno.
* **Resumo Financeiro:** Saldo Atual em destaque.
* **Corpo Analítico:**
  * Bloco de Recargas: Data, Hora, Forma de Pagamento (Pix / Dinheiro) e Valor;
  * Bloco de Consumo: Data, Hora, Descrição do Produto, Quantidade, Valor Unitário, Subtotal e Saldo Restante linha a linha.
* **Filtros:** Período de Datas (De/Até) e Seleção do Aluno.
* **Formatos:** Visualização em tela e exportação para PDF A4 de alta qualidade para impressão física.
* **Permissões:** Operador e Administrador.

### 2. Relatório de Emissão de Vendas por Alimento
* **Objetivo:** Avaliação comercial, controle de demanda e curva de vendas consolidando encomendas e balcão.
* **Colunas:** Código Interno, Descrição do Produto, Categoria, Unidade de Venda, Quantidade Comercializada no Período, Preço Unitário Médio e Faturamento Bruto.
* **Totalizadores:** Quantidade total de itens comercializados e Faturamento consolidado no rodapé da tabela (`<tfoot>`).
* **Filtros:** Período de datas (De/Até), Turno (Matutino / Vespertino / Ambos) e Categoria.
* **Formatos:** Visualização em tabela de alta densidade e exportação em PDF A4.
* **Permissões:** Administrador.

### 3. Relatório de Fechamento de Caixa por Turno
* **Objetivo:** Prestação de contas diária e conferência de valores físicos na gaveta.
* **Informações:** Timbre oficial da empresa, Data, Turno, Operador Responsável, Horário de Abertura, Horário de Encerramento, Fundo de Troco Inicial, Entradas em Dinheiro (Recargas vs Vendas Avulsas), Entradas em Pix Balcão, Valor Total Contado na Gaveta, Quebra/Sobra de Caixa e campo demarcado para assinatura do operador.
* **Filtros:** Data específica e Turno.
* **Formatos:** Emissão em PDF estruturado.
* **Permissões:** Operador (apenas de sua sessão) e Administrador (todas as sessões).

### 4. Relatório de Desconto em Folha (Colaboradores / RH)
* **Objetivo:** Apuração mensal de consumo de docentes e funcionários para envio ao departamento de Recursos Humanos da escola.
* **Colunas:** Matrícula Funcional, Nome Completo do Colaborador, Cargo, Quantidade de Lanches Adquiridos e Valor Total Consolidado para Desconto.
* **Filtros:** Mês e Ano de competência.
* **Formatos:** Emissão em PDF formatado para conferência visual e exportação em planilha CSV/Excel estruturada para importação contábil.
* **Permissões:** Administrador.

### 5. Relatório de Famílias com Saldo Devedor (Negativados MED)
* **Objetivo:** Gestão e cobrança de inadimplências decorrentes de contestações bancárias de Pix.
* **Colunas:** CPF do Responsável, Nome do Titular, WhatsApp de Contato, Nomes dos Alunos Vinculados, Data do Estorno Bancário e Valor Negativo em Aberto.
* **Filtros:** Busca por nome/turma e ordenação por maior débito.
* **Formatos:** Tabela interativa com botão de quitação e exportação em PDF.
* **Permissões:** Administrador.

### 6. Emissão de Cupons e Lotes Térmicos (Bobina 80mm)
* **Objetivo:** Distribuição de tickets físicos de lanches para as turmas escolares e comprovantes opcionais de balcão.
* **Formatação:** Largura fixa de 80mm, margens laterais de 2mm, tipografia limpa de alto contraste, linhas de corte tracejadas entre tickets e quebras de bloco por turma escolar.
* **Dados por Cupom:** Data de Consumo, Turno, Série/Turma, Nome Completo do Aluno, Nome do Produto em caixa alta negrito e Tag de Alérgenos em destaque.
* **Estratégia Técnica de Impressão:** Geração de saída contínua formatada para impressoras térmicas ESC/POS padrão de 80mm através de:
  1. Estilos CSS `@media print` com `@page { size: 80mm auto; margin: 0; }` para disparo nativo direto pelo navegador (`window.print()`);
  2. Alternativamente, geração de fluxo PDF contínuo de 80mm pelo `PdfService` renderizado em aba auxiliar ou modal para envio à impressora padrão do sistema operacional.

### 7. Exportação Rápida para WhatsApp
* **Objetivo:** Envio instantâneo da demanda de salgados para o fornecedor parceiro.
* **Comportamento:** Botão no Painel de Corte que formata e transfere o texto estruturado para a área de transferência do computador:
  ```text
  *PEDIDO DE SALGADOS - CANTINA MONTEIRO LOBATO*
  Data: 15/09/2026 | Turno: Matutino
  ----------------------------------------
  - Coxinha de Frango com Catupiry: 42 un
  - Enroladinho de Presunto e Queijo: 28 un
  - Esfiha de Carne Assada: 35 un
  - Empada de Palmito: 15 un
  ----------------------------------------
  TOTAL DO TURNO: 120 salgados
  Horário de Entrega: até 09:30
  ```

---

## 23. APIs e Integrações Externas

### 1. Gateway de Pagamento Pix Dinâmico
* **Objetivo:** Geração de cobranças imediatas e liquidação automatizada de créditos.
* **Endpoints Utilizados:**
  * `POST /v1/pix/qrcodes`: Envio do valor e referência para geração de QR Code dinâmico e chave Copia e Cola com validade de 15 minutos;
  * `POST /webhook/pix`: Endpoint seguro na aplicação Cantina Web que recebe confirmações bancárias de liquidação;
  * `POST /webhook/pix/devolucoes`: Endpoint para recepção de notificações de contestações e acionamento do Mecanismo Especial de Devolução (MED).
* **Segurança e Idempotência:** Todas as requisições de webhook validam o segredo de autenticação (`X-Signature` ou token de cabeçalho). O identificador da transação (`txid` ou `e2eid`) é gravado de forma única para impedir liquidação ou débito duplicado em caso de retentativas automáticas do gateway bancário.
* **Tratamento de Falhas e Retries:** O gateway possui política de retentativas automáticas com espaçamento exponencial. Requisições recebidas respondem HTTP 200 imediatamente após o processamento da transação.

### 2. API de Mensageria WhatsApp
* **Objetivo:** Disparo automático de notificações transacionais para os números dos responsáveis.
* **Eventos Disparados:**
  1. Envio de código de verificação de 6 dígitos no primeiro acesso e recuperação de senha;
  2. Confirmação instantânea de crédito após aprovação de recarga Pix;
  3. Alerta preventivo quando o saldo da carteira for inferior a R$ 15,00;
  4. Comunicado urgente de suspensão de conta familiar em caso de contestação de Pix (MED).
* **Isolamento via Adapter:** A aplicação consome a API através da classe `WhatsAppService`. Falhas na entrega de WhatsApp não impedem a conclusão do fluxo principal no sistema, sendo registradas na tabela `logs_erros` para reenvio manual ou auditoria.

---

## 24. Segurança Funcional

O Cantina Web implementa padrões rígidos de segurança em todas as camadas funcionais:

1. **Prevenção Total contra SQL Injection:** Toda e qualquer interação com o banco de dados MySQL é realizada através de Prepared Statements via PDO com associação estrita de tipos de parâmetros (`bindValue` / `bindParam`). A interpolação direta de variáveis em strings SQL é categoricamente proibida.
2. **Prevenção contra Cross-Site Scripting (XSS):** Todo dado dinâmico exibido nas telas HTML passa obrigatoriamente pela função nativa de escape de caracteres: `htmlspecialchars($dado, ENT_QUOTES | ENT_HTML5, 'UTF-8')`.
3. **Prevenção contra Cross-Site Request Forgery (CSRF):** Todos os formulários HTML e chamadas assíncronas enviam um token CSRF único validado no backend antes do processamento.
4. **Proteção de Diretórios Sensíveis:** Diretórios contendo lógica de negócio, configurações e dados fiscais (`app/`, `config/`, `database/`, `storage/`) possuem regras restritivas no `.htaccess` e validação da constante interna `CANTINA_SISTEMA`.
5. **Conformidade com a LGPD Escolar:** Dados cadastrais de crianças (nomes, fotos, turmas e alérgenos) são manipulados sob controle de acesso estrito e armazenados com salvaguardas técnicas. Pais visualizam apenas seus próprios dependentes e operadores de balcão visualizam apenas dados essenciais à entrega física e segurança alimentar.
6. **Proteção de Uploads contra Execução Remota de Código:** Todos os uploads passam por verificação de MIME type e assinatura de cabeçalho binário (magic bytes). Arquivos públicos são renomeados para nomes criptografados ou UUIDs aleatórios.
7. **Tratamento Seguro de Mensagens de Erro:** Em ambiente de produção, stack traces detalhados e erros internos de banco de dados são omitidos da interface do usuário e gravados exclusivamente na tabela `logs_erros` ou em arquivo de contingência em disco.

---

## 25. Organização Sugerida da Implementação

Para orientar a IA codificadora em um desenvolvimento linear, consistente e testável a partir do ambiente local até o deploy em produção, recomenda-se a divisão do trabalho nas seguintes 24 etapas sequenciais:

1. **Etapa 1: Estruturação do `[Diretório do Projeto - Repositório]`:** Criação da árvore de pastas (`app/`, `config/`, `database/`, `storage/`, `assets/`), configuração inicial do arquivo de entrada `index.php` e diretivas `.htaccess` de proteção.
2. **Etapa 2: Configuração Centralizada em Código:** Criação do arquivo `config/config.php` contendo constantes globais, parâmetros de infraestrutura, conexão de banco e segredos criptográficos, sem o uso de `.env`.
3. **Etapa 3: Núcleo Arquitetural MVC:** Implementação das classes fundamentais em `app/core/` (`Router`, `Controller`, `Model`, `Session`, `Auth`, `RBAC`, `ErrorHandler`).
4. **Etapa 4: Mecanismo de Migrations e Banco de Dados:** Criação da tabela `migrations_controle`, da fábrica de conexão PDO singleton (`config/database.php`) e do script executor de migrações (`database/runner.php`).
5. **Etapa 5: Migrações das Tabelas Estruturais e Cadastrais:** Elaboração e execução das migrations 001 a 012 (`configuracoes_globais`, `usuarios`, `responsaveis`, `turmas`, `alunos`, `restricoes_alimentares`, `aluno_restricoes`, `colaboradores`, `categorias`, `produtos`, `produto_alergenos`).
6. **Etapa 6: Migrações Fiscais, Transacionais e Logs:** Elaboração e execução das migrations 013 a 026 (`notas_fiscais_xml`, `itens_nota_fiscal_xml`, `estoque_movimentacoes`, `pedidos`, `itens_pedido`, `tickets`, `sessoes_caixa`, `vendas_balcao`, `itens_venda_balcao`, `transacoes_carteira`, `calendario_letivo`, `logs_auditoria`, `logs_erros`, `logs_seguranca`).
7. **Etapa 7: Mecanismo de Logs e Contingência em Arquivo:** Implementação da classe `ErrorHandler` com gravação em `logs_erros` e fallback automático para arquivo em disco `storage/logs/error.log`.
8. **Etapa 8: Identidade Visual e Layouts Base (DESIGN.md):** Integração dos arquivos locais do Bootstrap, criação do CSS customizado corporativo de alta densidade e montagem dos templates mestre de layout (`header.php`, `footer.php`, `sidebar.php`).
9. **Etapa 9: Módulo de Autenticação e Sessão Segura:** Construção da tela de login unificada (Família e Equipe da Cantina), fluxo de primeiro acesso com envio de código transacional de 6 dígitos via WhatsApp e modal de termos da cantina.
10. **Etapa 10: Controle de Acesso (RBAC) e Menus Dinâmicos:** Configuração das permissões para Responsável, Operador e Administrador, bloqueio de rotas no backend e montagem dos menus por perfil.
11. **Etapa 11: Módulo de Administração e Configurações Globais:** Interface visual para parametrização institucional da cantina, cadastro de usuários da equipe (`usuarios`), upload do logotipo institucional e limites financeiros (recarga mínima e alerta de saldo).
12. **Etapa 12: Cadastros Mestres com Soft Delete:** Implementação dos CRUDs de Categorias, Produtos (com trava de estoque zerado e botão ativo/pausado), Colaboradores, Turmas e Usuários da Cantina.
13. **Etapa 13: Importação Escolar em Lote via Planilha:** Desenvolvimento do leitor de planilhas `.xlsx`/`.csv` para cadastro massivo de alunos, turmas e vínculos familiares.
14. **Etapa 14: Gestão Ativa de Restrições Alimentares:** Interface para associação de alérgenos aos dependentes e implementação do modal explicativo de alerta amarelo na seleção de itens incompatíveis.
15. **Etapa 15: Carteira Familiar e Integração Pix:** Construção da tela de recarga, geração do QR Code dinâmico/Copia e Cola, receptor de Webhook e atualização de saldo em tempo real.
16. **Etapa 16: Agendamento Semanal e Regras de Corte:** Tela de pedidos da semana, trava de abertura às 18:00 de sexta-feira, travamento pontual às 08:00 e 13:30, edição e cancelamento com estorno automático.
17. **Etapa 17: Encomendas e Fornecedores:** Painel de consolidação quantitativa de salgados no corte, botão "Copiar para WhatsApp", download de PDF e tela de recebimento físico com liquidação de demanda.
18. **Etapa 18: Impressão e Gestão de Tickets Térmicos (80mm):** Geração de lotes contínuos ordenados por série/turma com tags de alérgenos e rotina de 2ª via com gravação compulsória em auditoria.
19. **Etapa 19: Ponto de Venda (PDV) e Operação de Balcão:** Interface de vendas avulsas em dinheiro/Pix, consumo de colaboradores sob modalidade "Desconto em Folha" com confirmação de foto/cargo e estorno de balcão.
20. **Etapa 20: Gestão de Estoque, XML e Perdas:** Controle de saldos de industrializados, importação de XML de NF-e com memória de cálculo em `itens_nota_fiscal_xml` e registro manual de perdas com motivo obrigatório.
21. **Etapa 21: Controle Financeiro de Caixas por Turno:** Abertura com fundo de troco, fechamento segregado às 11:00 e 16:00, apuração de quebras/sobras e relatório de prestação de contas.
22. **Etapa 22: Tratamento de Contestações Pix (Mecanismo MED):** Recepção de webhook de estorno, débito na carteira familiar, bloqueio automático de novas compras se saldo negativo e aviso via WhatsApp.
23. **Etapa 23: Relatórios Gerenciais e Exportações:** Implementação do Extrato de Consumo do Aluno A4 timbrado, Relatório de Vendas por Alimento, Relatório de Fechamento de Turno e Relatório Mensal para o RH (PDF e CSV).
24. **Etapa 24: Revisão Geral de Segurança, Acessibilidade e Entrega:** Sanitização de entradas, testes de integridade contábil, verificação visual com o `docs/DESIGN.md` e preparação do pacote para deploy.

---

## 26. Critérios de Aceitação Técnica e Funcional

Para que a implementação do sistema seja considerada aprovada e pronta para entrada em produção, a IA codificadora deverá garantir o cumprimento dos seguintes critérios:

* [ ] **Estrutura de Diretórios:** Organizada a partir da referência `[Diretório do Projeto - Repositório]`, operando com suporte a subdiretórios locais e na hospedagem sem caminhos absolutos rígidos ou dependência de nomes fixos como `public_html`, `public`, `htdocs` ou `www`.
* [ ] **Configuração Segura sem `.env`:** Arquivo de configuração em código nativo PHP `config/config.php` devidamente protegido contra acesso direto pelo navegador via `.htaccess` e validação de constante PHP.
* [ ] **Proteção de Pastas Internas:** Pastas `config/`, `app/`, `database/` e `storage/` 100% blindadas contra leitura ou download direto via browser.
* [ ] **Migrations Versionadas:** Banco de dados totalmente construído através de 26 migrações sequenciais em `database/migrations/`, com mecanismo de prevenção contra execução duplicada via tabela `migrations_controle` e execução segura e não pública.
* [ ] **Autenticação Segregada Completa:** Contas de responsáveis armazenadas em `responsaveis` e contas da equipe da cantina armazenadas em `usuarios`, com hashes criptográficos seguros e perfis RBAC.
* [ ] **Arquitetura MVC Estrita:** Separação inequívoca entre dados/regras (Models), controle/rotas (Controllers) e renderização visual (Views), com camada isolada de serviços (`services/`).
* [ ] **Banco de Dados e Desempenho:** Chaves primárias, estrangeiras e constraints devidamente criadas, com índices compostos implementados nas tabelas transacionais críticas (`pedidos`, `tickets`, `sessoes_caixa`, `vendas_balcao`, `transacoes_carteira`).
* [ ] **Sistema de Logs e Contingência:** Erros de aplicação capturados e persistidos em `logs_erros`, com contingência comprovada para gravação em arquivo `storage/logs/error.log` em caso de falha de conexão com o MySQL.
* [ ] **Log de Segurança:** Tentativas incorretas de login, bloqueios preventivos e notificações MED devidamente registradas em `logs_seguranca`.
* [ ] **Auditoria Técnica Completa:** Presença e gravação de `created_at`, `created_by`, `updated_at` e `updated_by` em todas as tabelas cadastrais mestres (`usuarios`, `responsaveis`, `turmas`, `alunos`, `colaboradores`, `categorias`, `produtos`), assegurando rastreabilidade de autoria.
* [ ] **Trilha de Auditoria de Negócio:** Gravação obrigatória em `logs_auditoria` com vínculo ao operador logado para estornos manuais de balcão, emissão de 2ª via de cupons, baixas manuais de perda de estoque e ajustes de saldo.
* [ ] **Controle de Estado de Tickets:** Campo `status` operante na tabela `tickets` (`ativo`, `impresso`, `estornado`, `cancelado`) para viabilizar estornos parciais de itens no balcão e cancelamentos emergenciais de dias letivos.
* [ ] **Soft Delete Operante:** Exclusão lógica com `deleted_at` funcional em `produtos`, `colaboradores`, `categorias`, `turmas`, `alunos` e `usuarios`, preservando integridade contábil histórica.
* [ ] **Horários de Corte Rigorosos:** Bloqueio automático e pontual de novos pedidos, edições ou cancelamentos às 08:00 (matutino) e às 13:30 (vespertino).
* [ ] **Compensação Financeira Automática:** Cancelamentos antes do corte estornam 100% do valor imediatamente na carteira. Edições compensam diferenças de valor e liberam reservas de estoque no mesmo instante.
* [ ] **Proteção Pix e MED:** Liberação automática de recargas via webhook sem taxas ao usuário; contestação MED debita a carteira, bloqueia dependentes caso o saldo fique negativo e envia alerta via WhatsApp.
* [ ] **Recarga Presencial em Dinheiro:** Validação estrita de sessão de caixa aberta para recebimento em espécie no balcão, incremento de `total_dinheiro_recargas` e registro na carteira familiar com vínculo ao operador.
* [ ] **Segurança Alimentar:** Modal de advertência amarelo exibido preventivamente caso o responsável agende item com alérgeno incompatível; impressão compulsória da tag `[ATENÇÃO: CONTÉM ...]` no ticket de papel.
* [ ] **Operação de Tickets 80mm:** Emissão contínua de cupons térmicos agrupados e ordenados por Série e Turma para distribuição em sala de aula; atendimento de recreio 100% visual sem computador na fila.
* [ ] **Controle de Caixas por Turno:** Abertura com fundo de troco, fechamento do caixa matutino às 11:00 e do vespertino às 16:00, apuração de quebra/sobra e emissão de espelho para assinatura.
* [ ] **Consumo de Colaboradores:** Lançamento no PDV com seleção obrigatória de colaborador, conferência visual de foto e cargo em tela e emissão de relatório mensal consolidado em PDF e CSV para o RH.
* [ ] **Gestão de Estoque com XML:** Importação de notas fiscais com tela de conferência, persistência dos itens e fator multiplicador em `itens_nota_fiscal_xml` e registro obrigatório de perdas com motivo pré-cadastrado.
* [ ] **Conformidade Visual com DESIGN.md:** Aderência estrita à paleta institucional (Azul `#2563eb`, Verde `#16a34a`, Vermelho `#dc2626`, Amarelo `#eab308`), fontes tabulares (`tnum`), estados padronizados de *Empty State* e *Loading State*, alta densidade e logotipo oficial circular do Colégio Monteiro Lobato.

---

## 27. Pontos Pendentes e Decisões Futuras

Não foram identificadas pendências para iniciar a codificação com base neste FSD.

Todas as decisões técnicas, fluxos operacionais, regras financeiras, restrições nutricionais, componentes visuais e modelos de dados estão completamente consolidados e estabilizados para implementação imediata.

---

## 28. Conclusão

O presente **Documento de Especificação Funcional (FSD)** está formalmente concluído, validado e plenamente capacitado para orientar com autonomia, segurança e precisão a IA codificadora na implementação completa do sistema **Cantina Web — Colégio Monteiro Lobato**.

Para a etapa de desenvolvimento e codificação do software, devem ser entregues à IA codificadora única e exclusivamente os seguintes documentos:

1. `docs/FSD.md` (este documento de especificação funcional e técnica);
2. `docs/DESIGN.md` (documento de diretrizes de design e padrões visuais de interface).

A IA codificadora não necessita de documentos de concepção preliminar, tampouco do histórico de conversas anteriores, visto que todas as definições e requisitos foram integralmente sintetizados, detalhados e consolidados neste documento.
