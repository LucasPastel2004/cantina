# Contexto e Instruções de Desenvolvimento para Agentes de IA

Este documento orienta agentes de inteligência artificial e desenvolvedores sobre a arquitetura, regras operacionais, segurança, boas práticas e protocolos de trabalho no projeto **Cantina Web — Colégio Monteiro Lobato**.

---

## 1. Idioma e Comunicação

* Responda e comunique-se sempre em **português do Brasil**.
* Comentários de código, mensagens de commit, documentações e logs devem ser redigidos em português do Brasil.

---

## 2. Protocolo Obrigatório dos Arquivos Vivos

Todo agente de IA deve seguir rigorosamente o seguinte fluxo de trabalho em qualquer chat:

### Antes de iniciar qualquer trabalho:
1. Ler `docs/FSD.md`.
2. Ler `docs/DESIGN.md`.
3. Ler `docs/INSUMOS.md`.
4. Ler `docs/PLANO.md`.
5. Ler `docs/STATUS.md`.
6. Ler `docs/ERROS.md`.

> **ATENÇÃO À PORTABILIDADE DOS CAMINHOS:**
> - Use sempre caminhos relativos à raiz do projeto (como `docs/FSD.md`, `app/core/Router.php`).
> - Não transformar estes caminhos em links absolutos.
> - Não usar links `file:///`.
> - Não registrar caminhos locais da máquina atual dentro deste arquivo ou dos arquivos de documentação.

### Ao terminar qualquer trabalho:
1. Atualizar `docs/STATUS.md` com as tarefas concluídas e o próximo passo.
2. Registrar erros e soluções em `docs/ERROS.md`, se houver ocorrências.
3. Informar ao usuário de forma clara o que foi construído ou alterado.
4. Informar como testar e validar a entrega da etapa.

---

## 3. Stack Tecnológica e Arquitetura

Conforme especificado no `docs/FSD.md`:

* **Backend:** PHP 8.2+ orientado a objetos, modular, utilizando o padrão arquitetural MVC. Sem uso de frameworks monolíticos externos (como Laravel ou Symfony).
* **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+, utilizando estritamente o driver nativo `PDO_MYSQL` com Prepared Statements para todas as consultas.
* **Frontend:**
  * HTML5 semântico;
  * CSS3 corporativo de alta densidade e folhas de estilo customizadas baseadas no `docs/DESIGN.md`;
  * JavaScript puro (Vanilla JS, ES6+), sem frameworks reativos ou dependências pesadas de runtime;
  * Bootstrap compilado e servido exclusivamente de arquivos locais em `assets/css/bootstrap.min.css` e `assets/js/bootstrap.bundle.min.js` (sem dependência de links CDN externos).
* **Bibliotecas Auxiliares (integradas localmente):**
  * Geração de documentos PDF: Dompdf integrado localmente para relatórios A4 timbrados e cupons contínuos em bobina de 80mm;
  * Leitura e extração de XML de NF-e: Extensões nativas `SimpleXML` e `DOMDocument`;
  * Leitura de planilhas escolares: Leitor nativo CSV via `fgetcsv` como prioritário e `PhpSpreadsheet` para arquivos `.xlsx`.
* **Integrações Externas:**
  * Gateway Pix dinâmico para recargas com Webhooks bancários e tratamento do Mecanismo Especial de Devolução (MED);
  * API REST para disparo de mensagens transacionais via WhatsApp (código de primeiro acesso, confirmações de recarga, alertas de saldo baixo e notificações MED).

---

## 4. Ambientes do Projeto

* **Desenvolvimento Local:**
  * Docker e Docker Compose (serviços segregados para Apache, PHP 8.2 e MySQL 8.0);
  * Alternativamente: pilhas locais WAMP/XAMPP ou servidor embutido do PHP (`php -S localhost:8080`).
* **Testes e Homologação:**
  * Não há ambiente formal intermediário na versão 1. Testes e validações operacionais são realizados no ambiente local de desenvolvimento.
* **Produção:**
  * Hospedagem compartilhada gerenciada com Apache, PHP 8.2+ e MySQL na plataforma Hostnet.
  * Deploy via FTP/Git e execução controlada de migrações via `database/runner.php` ou phpMyAdmin.
* **Suporte a Subdiretórios:**
  * O código é portável e preparado para funcionar tanto na raiz (`/`) quanto em subpastas (como `/cantina/` ou `/public_html/cantina/`).

---

## 5. Estrutura de Diretórios Oficial

```text
├── index.php                        # Ponto único de entrada da aplicação (Front Controller)
├── .htaccess                        # Reescreve URLs para o index.php e protege arquivos ocultos
│
├── config/                          # Configurações do sistema protegidas por .htaccess
│   ├── .htaccess                    # Negação absoluta de acesso via navegador
│   ├── config.example.php           # Modelo versionado de configuração sem segredos
│   ├── config.php                   # Credenciais locais de banco, timeouts e constantes globais
│   └── database.php                 # Fábrica de conexão singleton PDO com MySQL
│
├── app/                             # Núcleo da aplicação MVC protegido por .htaccess
│   ├── .htaccess                    # Negação absoluta de acesso via navegador
│   ├── core/                        # Classes fundamentais (Router, Controller, Model, Session, Auth, RBAC, ErrorHandler)
│   ├── controllers/                 # Controladores por módulo funcional
│   ├── models/                      # Modelos de dados e regras de negócio
│   ├── views/                       # Templates e telas divididos por contexto
│   │   ├── layouts/                 # Estruturas mestras (header, footer, sidebar)
│   │   ├── partials/                # Componentes reutilizáveis (alertas, modais, tabelas)
│   │   ├── auth/                    # Telas de login e primeiro acesso
│   │   ├── portal/                  # Telas do Portal da Família
│   │   ├── operacional/             # Telas da equipe da cantina (corte, tickets, PDV, caixa)
│   │   ├── estoque/                 # Gestão de estoque, XML e perdas
│   │   ├── relatorios/              # Espelhos de impressão A4 e relatórios
│   │   └── admin/                   # Cadastros mestres, calendário letivo e configurações
│   └── services/                    # Adaptadores de integração (Pix, WhatsApp, XML, PDF, Planilhas)
│
├── database/                        # Migrações e infraestrutura de dados
│   ├── .htaccess                    # Negação absoluta de acesso via navegador
│   ├── migrations/                  # 26 migrações sequenciais versionadas
│   └── runner.php                   # Executor seguro de migrações (CLI ou chave interna)
│
├── storage/                         # Arquivos de log, temporários e uploads
│   ├── .htaccess                    # Negação geral de acesso via navegador
│   ├── logs/                        # Logs locais de contingência (error.log, security.log)
│   └── uploads/                     # Armazenamento de arquivos
│       ├── private/                 # Planilhas escolares e XMLs fiscais (100% blindado)
│       └── public/                  # Imagens públicas de produtos e logotipo (apenas imagens)
│
└── assets/                          # Recursos estáticos públicos do frontend
    ├── css/                         # Folhas de estilo locais (bootstrap.min.css, custom.css)
    ├── js/                          # Scripts interativos (bootstrap.bundle.min.js, app.js)
    └── img/                         # Imagens estáticas (logo-colegio.jpg)
```

---

## 6. Comandos Principais

* **Iniciar ambiente Docker:**
  `docker compose up -d`
* **Parar ambiente Docker:**
  `docker compose down`
* **Visualizar logs dos contêineres:**
  `docker compose logs -f`
* **Servidor embutido do PHP para testes locais rápidos:**
  `php -S localhost:8080 index.php`
* **Executar migrações do banco de dados (CLI):**
  `php database/runner.php`

---

## 7. Diretrizes Rigorosas de Segurança

* **Injeção SQL:** Utilizar exclusivamente o driver PDO com Prepared Statements e bindings explícitos em todas as operações de banco. Nunca concatenar variáveis diretamente em strings SQL.
* **Cross-Site Scripting (XSS):** Todas as saídas de variáveis dinâmicas em templates HTML devem ser escapadas com `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`.
* **Cross-Site Request Forgery (CSRF):** Todos os formulários HTML que executem mutações (POST) devem incluir token CSRF gerado pela classe `Session`, validado obrigatoriamente antes do processamento no controller.
* **Armazenamento Seguro de Senhas:** Utilizar obrigatoriamente `password_hash()` com algoritmo seguro (Bcrypt ou Argon2id) e verificar com `password_verify()`. Nunca armazenar senhas em texto puro ou com hashes fracos (MD5, SHA1).
* **Proteção de Pastas e Arquivos Internos:**
  * Pastas `config/`, `app/`, `database/` e `storage/` contêm diretivas `.htaccess` bloqueando qualquer requisição direta.
  * Todos os arquivos PHP das pastas internas devem conter na primeira linha de código:
    ```php
    defined('CANTINA_SISTEMA') or die('Acesso direto não permitido.');
    ```
* **Ausência de Arquivos `.env`:** Configurações e credenciais são mantidas em código PHP nativo dentro de `config/config.php`, protegido contra leitura direta.
* **Segregação de Uploads e LGPD:** Planilhas com dados de alunos e notas fiscais XML devem residir exclusivamente em `storage/uploads/private/`, inacessível via HTTP. Apenas imagens estáticas com nomes gerados por hash/UUID são permitidas em `storage/uploads/public/`.
* **Controle de Acesso Baseado em Papéis (RBAC):** Cada rota, ação e tela deve validar no backend se o usuário logado possui a permissão requerida (Responsável, Operador, Administrador ou Colaborador). Acesso negado gera registro imediato em `logs_seguranca`.

---

## 8. Diretrizes de Interface e Design System

* Siga estritamente as especificações do `docs/DESIGN.md`.
* **Arquétipo Visual:** Utilitário corporativo de alta densidade (Enterprise ERP/POS), sem sombras difusas ou decorações desnecessárias.
* **Paleta de Cores:**
  * Azul Primário / Header de Tabelas: `#2563eb` (Hover: `#1d4ed8`)
  * Verde Sucesso / Gravar: `#16a34a` (Hover: `#15803d`, Superfície: `#dcfce7`)
  * Vermelho Perigo / Cancelar: `#dc2626` (Hover: `#b91c1c`, Superfície: `#fee2e2`)
  * Amarelo Alerta / Restrições: `#eab308` (Superfície: `#fef9c3`, Texto: `#854d0e`)
  * Fundo da Aplicação: `#f1f5f9` (Slate 100)
* **Tipografia Tabular:** Para qualquer coluna ou campo numérico, monetário (R$ 0,00) ou quantidade, aplicar obrigatoriamente a classe `.font-tabular` (`font-feature-settings: 'tnum' 1, 'zero' 1;`).
* **Tabelas Operacionais:** Cabeçalho azul `#2563eb` com texto branco, altura de linha entre 36px e 40px, alinhamento de texto à esquerda e valores monetários estritamente à direita.
* **Logotipo Oficial:** Exibir o logotipo circular `assets/img/logo-colegio.jpg` no cabeçalho/sidebar (`36px-40px`), na tela de login centralizada (`80px-96px`) e nos relatórios impressos A4 (`48px-56px`).
* **Estados Visuais:** Tratar sempre estados de lista vazia (*Empty State*: *"Não há nada para mostrar aqui"*), carregamento (*Loading State* com spinner azul) e itens esgotados (opacidade atenuada 0.5 e clique desabilitado).

---

## 9. Boas Práticas de Desenvolvimento

* **Funções e Métodos Pequenos:** Manter responsabilidade única por método e classe.
* **Nomes Descritivos:** Usar nomes autoexplicativos para classes, variáveis e métodos em português ou padrão estabelecido no FSD.
* **Trabalho Dentro do Escopo:** Não inventar funcionalidades fora do que foi especificado no `docs/FSD.md` e `docs/DESIGN.md`.
* **Construção Incremental:** Desenvolver estritamente uma fase por vez conforme definido em `docs/PLANO.md`, sem tentar antecipar fases futuras.
