# Manual de Manutenção do Sistema — Cantina Web (Colégio Monteiro Lobato)

Este documento é a referência definitiva para desenvolvedores e agentes de Inteligência Artificial encarregados de manter, corrigir, otimizar e evoluir o sistema **Cantina Web — Colégio Monteiro Lobato**.

---

## 1. Visão Geral

### O que o sistema faz
O **Cantina Web — Colégio Monteiro Lobato** é uma plataforma web corporativa de gestão operacional, financeira e nutricional desenvolvida especificamente para a cantina escolar de unidade física única do Colégio Monteiro Lobato.

### Para quem ele foi criado
1. **Pais e Responsáveis Financeiros:** Gerenciam uma carteira virtual única compartilhada entre os filhos, recarregam saldo via Pix dinâmico, registram restrições alimentares/alergias, agendam lanches semanais e acompanham o extrato de consumo.
2. **Equipe da Cantina (Atendentes e Operadores):** Operam a consolidação de compras no corte, conferência física de recebimento de salgados, impressão em lote de tickets térmicos por turma, atendimento rápido no recreio por tickets de papel, vendas avulsas e consumo de colaboradores no Ponto de Venda (PDV) e fechamento diário de caixas por turno.
3. **Administradores e Gerentes da Cantina:** Controlam o cardápio, precificação, permissões de usuários, importação de planilhas escolares, gestão de estoque e perdas, importação de notas fiscais XML (NF-e), calendário letivo e relatórios contábeis/DRE.
4. **Colaboradores e Professores da Escola:** Perfil passivo cadastrado para consumo presencial na cantina com faturamento mensal sob a modalidade "Desconto em Folha".

### Quais problemas resolve
- **Fim das filas no recreio:** Atendimento 100% visual no balcão mediante a entrega e descarte de tickets físicos de bobina térmica de 80mm pré-impressos e entregues em sala de aula (zero computadores ou leitores de código de barras na fila de 20 minutos).
- **Zero desperdício de alimentos:** A compra de salgados diários junto a fornecedores baseia-se na consolidação matemática exata das encomendas feitas pelos pais até o horário rígido de corte (08:00 para matutino e 13:30 para vespertino).
- **Erradicação do dinheiro físico entre crianças:** Transações pré-pagas via carteira virtual familiar recarregada instantaneamente por Pix dinâmico.
- **Segurança alimentar e controle de alergias:** Alertas ativos para os 8 alérgenos oficiais (Glúten, Lactose, Amendoim, Ovos, Soja, Frutos do Mar, Nozes, Tartrazina) em agendamentos dos pais e no balcão do PDV.
- **Blindagem financeira e contingência:** Fechamento de caixa segregado por turno (11:00 e 16:00), controle estrito de quebras/sobras e tratamento automatizado do Mecanismo Especial de Devolução (MED) de contestações Pix.

### Principais módulos do sistema
1. **Portal da Família:** Dashboard, carteira compartilhada, recarga Pix com QR Code dinâmico, agendamento semanal com bloqueio de corte, gestão de alergias e extrato analítico.
2. **Operacional da Cantina:** Painel de corte do turno, exportação para fornecedor (WhatsApp/PDF A4), conferência de recebimento físico, impressão em lote de tickets 80mm por série/turma, reimpressão de 2ª via e estorno de balcão.
3. **Ponto de Venda (PDV):** Vendas avulsas (dinheiro e Pix balcão com cálculo de troco), consumo de colaboradores com confirmação visual de foto (desconto em folha) e módulo de estorno de vendas com auditoria.
4. **Estoque e Suprimentos:** Saldo físico contínuo, pausa rápida de produtos, lançamento auditado de perdas com motivos padronizados e importação de XML de NF-e com fator multiplicador de conversão.
5. **Financeiro e Caixas:** Abertura e fechamento de caixas por turno com fundo de troco, relatório de arrecadação diária, DRE Gerencial completo com segregação de Regimes de Caixa e Competência e controle de negativados MED.
6. **Administração e Cadastros Mestres:** Carga escolar em lote (planilhas CSV/XLSX com conferência prévia dry-run), catálogo de produtos, categorias, turmas, colaboradores, usuários internos, calendário escolar com cancelamento em lote e configurações globais.
7. **Relatórios Oficiais e Motor de Impressão:** Relatórios A4 timbrados em PDF de alta fidelidade gerados via Chromium headless (Gotenberg) com margens físicas simétricas de 14mm anti-clipping.

---

## 2. Stack Tecnológica e Ambientes

### Linguagem e Arquitetura
* **Linguagem Backend:** PHP 8.2+ orientado a objetos, fortemente tipado, modular e seguindo o padrão arquitetural MVC nativo.
* **Framework:** Framework próprio, enxuto e autônomo (sem uso de frameworks monolíticos externos como Laravel, Symfony ou CodeIgniter), garantindo inicialização ultrarrápida e baixíssimo consumo de memória.
* **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+, utilizando estritamente o driver `PDO_MYSQL` com Prepared Statements nativos sem emulação (`ATTR_EMULATE_PREPARES => false`) e charset `utf8mb4`.

### Frontend
* **HTML5:** Semântico e acessível.
* **CSS3:** Design System corporativo utilitário de alta densidade ("Anti-Slop Enterprise Edition") implementado em `assets/css/custom.css`.
* **JavaScript:** Vanilla JS moderno (ES6+), modular e assíncrono em `assets/js/app.js` (sem React, Vue, jQuery ou runtimes pesados).
* **Bootstrap:** Versão 5.3.3 compilada e servida exclusivamente de arquivos locais (`assets/css/bootstrap.min.css` e `assets/js/bootstrap.bundle.min.js`), com zero dependência de CDNs externas.

### Bibliotecas e Serviços Auxiliares
* **Geração de PDFs:** Microsserviço Chromium headless (Gotenberg 8) em contêiner Docker para relatórios A4 institucionais e espelhos contínuos de bobina de 80mm com precisão tipográfica milimétrica. Fallback contingencial em PHP puro estruturado em `PdfService.php`.
* **Manipulação de Planilhas:** Leitor nativo de streaming CSV via `fgetcsv` otimizado para baixo consumo de memória, com detecção automática de delimitador (`;` ou `,`), remoção de BOM UTF-8 e suporte a arquivos `.xlsx` via `PhpSpreadsheet`.
* **Extração Fiscal (XML NF-e):** Extensões nativas `SimpleXML` e `DOMDocument` para leitura da tag `<infNFe>`, produtos e tributos de notas de compra.
* **Geração de Pix e QR Code:** Classes puras nativas (`PixService.php` e `QrCodeService.php`) com codificação ISO/IEC 18004 Reed-Solomon, padrão EMVCo TLV e polinômio CRC16-CCITT sem bibliotecas externas.

### Ambientes do Projeto
1. **Ambiente Local de Desenvolvimento (Docker):**
   * Orquestrado via `docker-compose.yml`:
     - `cantina_app`: Apache 2.4 com PHP 8.2 (porta 8080).
     - `cantina_db`: MySQL 8.0 (porta 3306).
     - `cantina_gotenberg`: Gotenberg 8 Chromium Headless (porta 3000).
   * URL local de acesso: `http://localhost:8080`
2. **Ambiente de Produção (Hostnet):**
   * Hospedagem gerenciada Linux Apache com PHP 8.2+ e MySQL.
   * Publicação de código via Git ou FTP e execução segura de migrações via `database/runner.php?token=...` ou CLI.
   * Suporte nativo a subdiretórios (ex: `/cantina/` ou `/public_html/cantina/`).

### Comandos Principais

* **Iniciar o ambiente completo:**
  ```bash
  docker compose up -d
  ```
* **Parar o ambiente:**
  ```bash
  docker compose down
  ```
* **Acompanhar logs dos contêineres:**
  ```bash
  docker compose logs -f app
  ```
* **Acessar o terminal bash do contêiner da aplicação:**
  ```bash
  docker compose exec app bash
  ```
* **Executar o verificador e executor de migrações do banco:**
  ```bash
  docker compose exec app php database/runner.php
  # Ou verificar o status das migrações:
  docker compose exec app php database/runner.php --status
  ```
* **Executar suítes de testes automatizados:**
  ```bash
  docker compose exec app php tests/fase2_test.php
  docker compose exec app php tests/fase6_test.php
  docker compose exec app php tests/arrecadacao_dre_regimes_test.php
  docker compose exec app php tests/teste_todos_pdfs.php
  ```

---

## 3. Como Rodar Localmente Passo a Passo

1. **Pré-requisitos:**
   * Docker e Docker Compose instalados no computador.
   * Git instalado.

2. **Configuração das Credenciais Locais:**
   * O repositório versiona `config/config.example.php`.
   * Crie o arquivo `config/config.php` (caso ainda não exista) com as credenciais do ambiente Docker:
     ```php
     <?php
     defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

     define('DB_HOST', 'db');
     define('DB_NAME', 'cantina');
     define('DB_USER', 'cantina_user');
     define('DB_PASS', 'cantina_secret');
     define('DB_PORT', 3306);

     define('APP_ENV', 'development'); // 'development' ou 'production'
     define('APP_DEBUG', true);
     define('APP_URL', 'http://localhost:8080');
     define('GOTENBERG_URL', 'http://gotenberg:3000');
     define('MIGRATION_TOKEN', 'monteiro_lobato_secret_migration_token_2026');
     ```

3. **Subir os Contêineres:**
   ```bash
   docker compose up -d
   ```

4. **Aplicar as Migrações do Banco:**
   ```bash
   docker compose exec app php database/runner.php
   ```

5. **Acessar no Navegador:**
   * Abra `http://localhost:8080`
   * **Credenciais padrão para testes locais:**
     - **Equipe da Cantina (Administrador):** Usuário `admin` | Senha `admin123`
     - **Equipe da Cantina (Operador):** Usuário `operador-ana` | Senha `operador123`
     - **Portal da Família (Responsável):** CPF `111.222.333-44` | Senha `senha123`

---

## 4. Mapa de Pastas e Arquivos

```text
├── index.php                        # Front Controller único da aplicação
├── .htaccess                        # Reescreve URLs e protege diretórios
├── docker-compose.yml               # Definição dos contêineres Docker (App, DB, Gotenberg)
│
├── config/                          # Configurações globais (protegido por .htaccess)
│   ├── config.example.php           # Modelo sem senhas para versionamento
│   ├── config.php                   # Credenciais locais, chaves de API e URLs (ignorado pelo Git)
│   └── database.php                 # Singleton PDO com Prepared Statements nativos
│
├── app/                             # Núcleo MVC da aplicação (protegido por .htaccess)
│   ├── core/                        # Classes fundamentais do framework
│   │   ├── Router.php               # Roteador HTTP (GET, POST, PUT, DELETE) com middlewares
│   │   ├── Controller.php           # Base dos controllers (render, json, redirect, CSRF)
│   │   ├── Model.php                # Base dos models com abstração PDO e transações
│   │   ├── Session.php              # Sessões seguras, expiração e tokens CSRF
│   │   ├── Auth.php                 # Autenticação segregada e controle de força bruta
│   │   ├── RBAC.php                 # Matriz de controle de permissões por perfil
│   │   └── ErrorHandler.php         # Capturador global de erros com UUID e log local
│   │
│   ├── controllers/                 # Lógica de controle por módulo
│   │   ├── AuthController.php       # Login, logout, primeiro acesso WhatsApp e reset de senha
│   │   ├── PortalController.php     # Dashboard familiar, agendamentos e alergias
│   │   ├── CarteiraController.php   # Recargas Pix, extrato analítico e simulação
│   │   ├── PedidosController.php    # Corte de compras, exportação a fornecedor e conferência
│   │   ├── TicketsController.php    # Impressão de cupons 80mm e 2ª via
│   │   ├── PdvController.php        # Vendas avulsas, desconto em folha e estornos
│   │   ├── EstoqueController.php    # Saldos, pausa rápida, perdas e importação XML NF-e
│   │   ├── CaixaController.php      # Abertura, fechamento de turno e conciliação
│   │   ├── RelatoriosController.php # PDFs A4 timbrados, vendas por alimento e devedores MED
│   │   ├── FinanceiroController.php # DRE Gerencial, centro de custos e despesas
│   │   ├── AdminController.php      # Cadastros mestres, importação escolar e calendário
│   │   └── WebhookController.php    # Notificações bancárias Pix e devoluções MED
│   │
│   ├── models/                      # Modelos de dados e regras de negócio
│   │   ├── Usuario.php              # Operadores e administradores
│   │   ├── Responsavel.php          # Titulares de carteira familiar
│   │   ├── Aluno.php                # Alunos com matrícula e turma
│   │   ├── RestricaoAlimentar.php   # Catálogo e vínculos de alérgenos
│   │   ├── Turma.php                # Séries, turmas e turnos
│   │   ├── Colaborador.php          # Professores para desconto em folha
│   │   ├── Categoria.php            # Categorias de produtos
│   │   ├── Produto.php              # Produtos, preços, estoque e pausa
│   │   ├── NotaFiscal.php           # Cabeçalho de NF-e importada
│   │   ├── ItemNotaFiscal.php       # Itens de NF-e com fator multiplicador
│   │   ├── Estoque.php              # Movimentações e saldos físicos
│   │   ├── Pedido.php               # Encomendas semanais e cortes
│   │   ├── ItemPedido.php           # Itens do pedido agendado
│   │   ├── Ticket.php               # Cupons térmicos de 80mm
│   │   ├── SessaoCaixa.php          # Caixas abertos e fechados por turno
│   │   ├── VendaBalcao.php          # Vendas avulsas e colaboradores
│   │   ├── ItemVendaBalcao.php      # Itens da venda avulsa
│   │   ├── TransacaoCarteira.php    # Extrato contábil familiar linha a linha
│   │   ├── Despesa.php              # Lançamento de despesas operacionais e fixas
│   │   ├── CalendarioEscolar.php    # Dias letivos e cancelamento em lote
│   │   ├── Auditoria.php            # Logs de auditoria de operações críticas
│   │   └── Configuracao.php         # Parâmetros operacionais globais
│   │
│   ├── views/                       # Telas e templates
│   │   ├── layouts/                 # Estruturas mestras (admin, portal, auth)
│   │   ├── partials/                # Alertas, modais, empty state, paginação, stat-strip
│   │   ├── auth/                    # Telas de login e primeiro acesso
│   │   ├── portal/                  # Telas dos pais (carteira, agendamento, alergias)
│   │   ├── operacional/             # Telas da cantina (corte, tickets, PDV, caixa)
│   │   ├── estoque/                 # Saldos, perdas e importação XML NF-e
│   │   ├── relatorios/              # Espelhos de impressão A4 timbrados
│   │   ├── financeiro/              # DRE e gestão de despesas
│   │   ├── admin/                   # Cadastros mestres, importação escolar e calendário
│   │   └── errors/                  # Telas 404 e 500 com rastreabilidade UUID
│   │
│   └── services/                    # Adaptadores de serviços externos
│       ├── PixService.php           # Geração de cobranças Pix e payload EMVCo
│       ├── QrCodeService.php        # Gerador autônomo de QR Code em SVG/Base64
│       ├── WhatsAppService.php      # Disparo transacional de códigos de acesso e alertas
│       ├── XmlParserService.php     # Extração de dados fiscais de XML NF-e
│       ├── PdfService.php           # Integração com Gotenberg e renderização A4/80mm
│       └── SpreadsheetService.php   # Leitura em streaming de planilhas CSV e XLSX
│
├── database/                        # Migrações e infraestrutura de dados
│   ├── .htaccess                    # Protege migrations e libera runner via token
│   ├── migrations/                  # 26 migrações sequenciais oficiais (001 a 026)
│   └── runner.php                   # Executor transacional de migrações (CLI ou HTTP)
│
├── storage/                         # Armazenamento e arquivos temporários
│   ├── logs/                        # Logs de contingência (error.log, security.log)
│   ├── cache/                       # Cache temporário de cobranças Pix
│   └── uploads/
│       ├── private/                 # Planilhas escolares e XMLs fiscais (100% blindado)
│       └── public/                  # Imagens e logotipos com hash/UUID
│
├── assets/                          # Recursos públicos do frontend
│   ├── css/                         # bootstrap.min.css e custom.css (Design System)
│   ├── js/                          # bootstrap.bundle.min.js e app.js (Vanilla JS)
│   └── img/                         # logo-colegio.jpg e imagens institucionais
│
├── docs/                            # Documentação técnica e viva do projeto
└── tests/                           # Suítes de testes automatizados e integração
```

### Guia de quando mexer em cada pasta
- **`app/controllers/`**: Ao criar novos endpoints, fluxos de requisição ou preparar variáveis para novas telas. Sempre valide permissões com RBAC e proteja formulários com CSRF.
- **`app/models/`**: Ao alterar lógica de negócio, consultas SQL ou cálculos matemáticos. Sempre utilize Prepared Statements com `$this->query()` ou `$this->execute()`.
- **`app/views/`**: Ao alterar marcação HTML ou adicionar novos campos e tabelas. Siga estritamente o `docs/DESIGN.md`, utilizando fonte tabular (`.font-tabular`), modal corporativo (`data-confirm`) e botões com feedback tátil.
- **`app/services/`**: Ao alterar integrações externas (Pix, WhatsApp, Gotenberg, XML, planilhas). Nunca introduza dependências de runtime externas que exijam Node.js ou Composer em produção.
- **`database/migrations/`**: Ao alterar a estrutura de dados. Nunca edite migrações antigas já aplicadas; crie sempre um novo arquivo sequencial com o próximo número disponível.

---

## 5. Banco de Dados e Persistência

### Arquitetura de Migrações
* As migrações ficam em `database/migrations/` numeradas de forma sequencial de `001_` a `026_`.
* A tabela `migrations_controle` no MySQL registra o nome de cada migração executada e o respectivo timestamp.
* O arquivo `database/runner.php` aplica as migrações pendentes em ordem sequencial com controle de transação individual.

### Como aplicar alterações estruturais
1. Para rodar as migrações pendentes via terminal:
   ```bash
   docker compose exec app php database/runner.php
   ```
2. Para verificar o status das migrações sem executar:
   ```bash
   docker compose exec app php database/runner.php --status
   ```
3. Para executar em ambiente de produção (Hostnet) via navegador com token:
   `https://seudominio.com.br/database/runner.php?token=monteiro_lobato_secret_migration_token_2026`

### Rotina Segura de Limpeza de Dados de Teste Pré-Lançamento
O sistema dispõe do utilitário `database/reset_dados_teste.php` para purgar e zerar dados operacionais gerados durante os testes e homologação antes da inauguração oficial com clientes reais.

* **Modos de Operação:**
  * `transacional` (Padrão): Limpa pedidos, vendas de balcão, sessões de caixa, extratos e movimentações de carteira (saldos voltam para R$ 0,00), estoque atual (redefinido para 0), despesas, perdas e logs. Mantém intactos: usuários da cantina, turmas, alunos, responsáveis, catálogo de produtos e migrações.
  * `total`: Executa o modo transacional e também zera cadastros de alunos, vínculos familiares e colaboradores para reset completo de fábrica.
* **Execução via Terminal (SSH na Hostnet ou Docker local):**
  ```bash
  # Simulação / Verificação de segurança (não altera nada):
  /usr/bin/php8.2 database/reset_dados_teste.php --modo=transacional

  # Execução efetiva com confirmação:
  /usr/bin/php8.2 database/reset_dados_teste.php --modo=transacional --confirmar
  ```
* **Execução via Navegador (HTTP com Token e Confirmação via POST Seguro):**
  * Acesse no navegador a tela de checklist e confirmação:
    `https://seudominio.com.br/database/reset_dados_teste.php?token=SEU_MIGRATION_TOKEN&modo=transacional`
  * O sistema exibe o resumo completo do que será purgado e do que será preservado, exigindo a submissão do formulário via método `POST` para prevenir disparos acidentais por bots, crawlers ou prefetching de navegadores.



### Como criar uma nova migração
1. Crie um arquivo em `database/migrations/` seguindo a sequência numérica (ex: `027_adicionar_campo_x_na_tabela_y.php`).
2. Utilize o esqueleto padrão das migrações do projeto:
   ```php
   <?php
   defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');

   return new class {
       public function up(\PDO $pdo): void {
           $sql = "ALTER TABLE alunos ADD COLUMN novo_campo VARCHAR(100) NULL AFTER nome";
           $pdo->exec($sql);
       }

       public function down(\PDO $pdo): void {
           $sql = "ALTER TABLE alunos DROP COLUMN novo_campo";
           $pdo->exec($sql);
       }
   };
   ```

### Cuidados indispensáveis com dados
* **Integridade Referencial:** Tabelas contábeis e históricas (`pedidos`, `vendas_balcao`, `transacoes_carteira`, `sessoes_caixa`) utilizam `ON DELETE RESTRICT` nas chaves estrangeiras. Nunca exclua registros fisicamente; utilize o campo `deleted_at` (soft delete) presente nos models.
* **Prepared Statements Estritos:** Com `ATTR_EMULATE_PREPARES => false`, nunca reutilize o mesmo placeholder nomeado (ex: `:busca`) mais de uma vez na mesma query. Crie nomes distintos como `:busca1`, `:busca2` e passe ambos no array de parâmetros.

---

## 6. Autenticação, Autorização e Usuários

### Mecanismo de Login Segregado
O sistema implementa dois pontos de entrada autônomos na mesma tela unificada (`/login`):
1. **Portal da Família:**
   * Usuário autentica-se com **CPF** e **Senha**.
   * Consulta a tabela `responsaveis`.
   * Primeiro acesso exige ativação via código de 6 dígitos no WhatsApp e aceite formal dos Termos e Políticas da Cantina.
2. **Equipe da Cantina:**
   * Usuário autentica-se com **Identificador** (`admin` ou `operador-[nome]`) e **Senha**.
   * Consulta a tabela `usuarios`.

### Perfis de Usuário (RBAC)
O controle de acesso é gerenciado por `app/core/RBAC.php` e abrange 4 perfis:
1. `responsavel`: Acessa exclusivamente rotas do Portal da Família (`/portal/*`).
2. `operador`: Acessa a rotina presencial da cantina (corte, tickets, recebimento, PDV, caixas do turno e perdas). Bloqueado para edição de preços, importações fiscais/escolares e relatórios contábeis globais.
3. `administrador`: Acesso total e irrestrito a todos os módulos, configurações, relatórios, cadastros e parametrizações institucionais.
4. `colaborador`: Perfil passivo na base de dados para seleção presencial no PDV sob a modalidade Desconto em Folha (sem acesso a telas de login na V1).

### Como Proteger Novas Rotas
No arquivo `index.php`, registre a rota aplicando o middleware correspondente:
```php
// Rota acessível apenas por administradores
$router->add('GET', '/admin/minha-nova-tela', 'AdminController::minhaNovaTela', [RBAC::class, 'requireAdmin']);

// Rota acessível por operadores e administradores
$router->add('GET', '/operacional/minha-tela', 'OperacionalController::minhaTela', [RBAC::class, 'requireOperador']);

// Rota acessível apenas por responsáveis familiares autenticados
$router->add('GET', '/portal/minha-tela', 'PortalController::minhaTela', [RBAC::class, 'requireResponsavel']);
```

---

## 7. Como Adicionar uma Nova Tela

Para adicionar uma nova funcionalidade com interface visual, siga este fluxo:

1. **Definir o Controller e a Action (`app/controllers/`):**
   * Adicione o método no controlador apropriado (ou crie um novo se for um módulo independente).
   * Verifique permissões com RBAC e recupere dados do Model:
     ```php
     public function novaFuncionalidade(): void {
         $itens = MinhaEntidade::listarTodos();
         $this->render('admin/nova_funcionalidade', [
             'title' => 'Minha Nova Tela — Cantina Monteiro Lobato',
             'activeMenu' => 'minha_tela',
             'itens' => $itens
         ], 'admin');
     }
     ```
2. **Criar a View (`app/views/`):**
   * Crie o arquivo PHP na subpasta correspondente (ex: `app/views/admin/nova_funcionalidade.php`).
   * Siga o Design System: use cabeçalhos de tabela claros (`#f8fafc`), números alinhados à direita com `.font-tabular`, botões neutros (`.btn.btn-enterprise.btn-secondary`) e ações destrutivas com confirmação via modal corporativo `data-confirm`.
3. **Registrar a Rota (`index.php`):**
   * Adicione a resolução de URL com o middleware adequado:
     ```php
     $router->add('GET', '/admin/nova-funcionalidade', 'AdminController::novaFuncionalidade', [RBAC::class, 'requireAdmin']);
     ```
4. **Adicionar o Link de Navegação (`app/views/layouts/sidebar.php` ou `portal.php`):**
   * Adicione o item no menu correspondente utilizando o helper de classe ativa:
     ```php
     <a href="<?= baseUrl('/admin/nova-funcionalidade') ?>" class="nav-item <?= ($activeMenu ?? '') === 'minha_tela' ? 'active' : '' ?>">
         Minha Tela
     </a>
     ```
5. **Testar Manualmente e via Script:**
   * Crie um teste em `tests/` validando o status HTTP 200, a renderização e o controle de acesso.

---

## 8. Como Adicionar um Novo Campo em um Cadastro Existente

Para adicionar um campo a uma entidade existente (por exemplo, adicionar `telefone_secundario` em Alunos ou `observacao` em Produtos):

1. **Criar a Migração no Banco:**
   * Crie o arquivo `database/migrations/027_add_campo_tabela.php` adicionando a coluna via `ALTER TABLE` e execute o runner.
2. **Atualizar o Model (`app/models/`):**
   * Adicione o novo campo aos métodos de consulta (`SELECT ...`), criação (`criar`), atualização (`atualizar`) e nos arrays de parâmetros de bind PDO.
3. **Atualizar a View de Formulário (`app/views/`):**
   * Adicione o campo `<input>`, `<select>` ou `<textarea>` com label semântico e estilização `.form-control` ou `.form-control-enterprise`.
   * Adicione atributos de formatação se for telefone, CPF ou valor monetário (`.input-phone`, `.input-cpf`, `.input-money`).
4. **Atualizar a Validação no Controller:**
   * No método que processa o POST, sanitize e valide a entrada com `trim()`, `htmlspecialchars()` ou regras de domínio antes de repassar ao Model.
5. **Atualizar a Listagem/Tabela (se aplicável):**
   * Adicione a nova coluna na tabela de listagem, garantindo alinhamento coerente (texto à esquerda, códigos centralizados, valores numéricos à direita com `.font-tabular`).
6. **Atualizar a Documentação:**
   * Registre o novo campo no `docs/STATUS.md`.

---

## 9. Como Adicionar ou Alterar uma Regra de Negócio

1. **Consultar o FSD:** Leia a seção correspondente em `docs/FSD.md` para entender as premissas, limites operacionais e impactos financeiros/nutricionais.
2. **Localizar o Ponto de Aplicação:**
   * Regras de cálculo contábil ou saldo: `app/models/TransacaoCarteira.php`, `Pedido.php` ou `SessaoCaixa.php`.
   * Regras de corte operacional: `app/models/Pedido.php` (métodos de checagem de horário de corte 08:00 e 13:30).
   * Regras de validação alimentar: `app/models/RestricaoAlimentar.php`.
3. **Usar Transações PDO em Operações Multitabela:**
   * Se a regra envolver mais de uma tabela (ex: debitar carteira + criar pedido + reservar estoque), envolva obrigatoriamente a operação em uma transação atômica:
     ```php
     $pdo = Model::getPdo();
     $pdo->beginTransaction();
     try {
         // Executa passos da regra
         $pdo->commit();
     } catch (\Throwable $e) {
         $pdo->rollBack();
         throw $e;
     }
     ```
4. **Testar Cenários de Borda e Erro:**
   * Teste com saldo insuficiente, produtos com estoque zerado, horários imediatamente antes e depois do corte e alérgenos incompatíveis.

---

## 10. Como Testar Alterações

### Testes Automatizados
* Execute as suítes de testes automatizados dentro do contêiner Docker:
  ```bash
  # Testes de infraestrutura e rotas
  docker compose exec app php tests/fase2_test.php
  docker compose exec app php tests/web_simulation_test.php

  # Testes de autenticação e RBAC
  docker compose exec app php tests/fase6_test.php
  docker compose exec app php tests/auth_flow_test.php

  # Testes de PDFs e impressão A4
  docker compose exec app php tests/teste_todos_pdfs.php

  # Testes de arrecadação e DRE
  docker compose exec app php tests/arrecadacao_dre_regimes_test.php

  # Teste de confirmação corporativa (sem confirm nativo)
  docker compose exec app php tests/teste_modais_confirmacao.php
  ```

### Testes Manuais no Navegador
1. **Fluxo da Família:**
   * Acesse `http://localhost:8080/login`, entre como Responsável (`111.222.333-44` / `senha123`), acesse a carteira, faça uma simulação de recarga Pix, agende um lanche e verifique o extrato.
2. **Fluxo Operacional da Cantina:**
   * Entre como Operador (`operador-ana` / `operador123`), acesse `/operacional/corte`, confira a lista consolidada do turno, emita o lote de tickets em `/operacional/tickets` e realize uma venda avulsa no PDV `/operacional/pdv`.
3. **Fluxo de Caixa:**
   * Abra a sessão de caixa com fundo de troco, registre movimentações e feche o caixa gerando o espelho em PDF.

### Como Verificar Erros e Logs
* **No Banco de Dados:** Tabela `logs_erros` e tabela `logs_seguranca`.
* **Em Disco (Contingência):**
  - `storage/logs/error.log`: Registra falhas de PHP e exceções com traceback e código UUID.
  - `storage/logs/security.log`: Registra códigos de primeiro acesso e bloqueios.
* **Logs do Docker:** `docker compose logs -f app`.

---

## 11. Diretrizes Rígidas de Segurança

Qualquer desenvolvedor ou IA que faça alterações no código deve manter inegociavelmente as seguintes garantias de segurança:

1. **Prevenção de Injeção SQL:**
   * Use estritamente Prepared Statements nativos via PDO com bindings explícitos em todas as consultas.
   * **NUNCA** concatene variáveis diretamente em comandos SQL (`$sql = "SELECT * FROM ... WHERE id = " . $id` é proibido).
2. **Prevenção de Cross-Site Scripting (XSS):**
   * Toda saída dinâmica de texto em HTML deve ser escapada com `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`.
3. **Prevenção de CSRF:**
   * Todo formulário com mutação (POST) deve incluir o helper `<?= csrfField() ?>`.
   * Todo controller deve validar o token antes de processar os dados:
     ```php
     if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
         Session::flash('error', 'Token de segurança expirado. Tente novamente.');
         $this->redirect('/rota-anterior');
     }
     ```
4. **Armazenamento Seguro de Senhas:**
   * Utilize exclusivamente `password_hash($senha, PASSWORD_BCRYPT)` e verifique com `password_verify($senha, $hash)`.
5. **Proteção de Arquivos Internos:**
   * Todos os scripts PHP internos nas pastas `app/`, `config/`, `database/` e `storage/` devem iniciar com:
     ```php
     defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
     ```
6. **Segregação de Uploads e LGPD:**
   * Planilhas escolares com dados de alunos e notas fiscais XML devem residir exclusivamente em `storage/uploads/private/`, que possui `.htaccess` com bloqueio absoluto de acesso HTTP.
   * Apenas fotos de produtos e logotipo residem em `storage/uploads/public/`, com nomes anonimizados via hash/UUID e proibição de execução de scripts PHP.
7. **Controle de Confirmações de Interface:**
   * Nunca utilize `confirm()` nativo do navegador. Utilize o padrão corporativo declarativo `<form data-confirm="Mensagem..." data-confirm-type="danger">` ou chame assincronamente `window.confirmarAcao()`.

---

## 12. Como Registrar o Progresso

Após qualquer alteração relevante no sistema:
1. **Atualizar `docs/STATUS.md`:**
   * Registre detalhadamente o que foi construído, refatorado ou testado.
   * Indique o status da etapa e o próximo passo recomendado.
2. **Atualizar `docs/ERROS.md`:**
   * Caso tenha ocorrido qualquer erro, bug ou comportamento inesperado, registre-o no final de `docs/ERROS.md` no padrão oficial (Data, Sintoma, Causa, Solução Aplicada, Como Evitar).

---

## 13. O que NÃO Fazer (Regras de Ouro)

- **NÃO reescreva o sistema** nem troque a arquitetura MVC por frameworks externos (como Laravel, CodeIgniter ou Symfony).
- **NÃO altere a stack** (PHP 8.2 + MySQL 8.0 + Vanilla JS + Bootstrap local) sem solicitação e aprovação explícita.
- **NÃO introduza dependências de runtime** que exijam Node.js, npm, Webpack, Vite ou Composer no ambiente de produção.
- **NÃO remova proteções de segurança** (como CSRF, Prepared Statements ou verificação RBAC) com o pretexto de "resolver rápido".
- **NÃO versione senhas ou segredos reais** (o arquivo `config/config.php` deve permanecer no `.gitignore`).
- **NÃO use CDNs externas** para scripts ou folhas de estilo (Bootstrap, fontes e ícones devem ser servidos localmente).
- **NÃO altere layouts de impressão A4 sem testar a simetria** (margens laterais de 14mm físicas para evitar clipping de bordas no Chromium/Gotenberg).
- **NÃO faça alterações em múltiplos módulos simultaneamente** sem explicar o impacto de cada uma delas ao usuário.
