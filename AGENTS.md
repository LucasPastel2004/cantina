# Contexto e Instruções de Manutenção para Agentes de IA (Modo Manutenção)

Este documento orienta agentes de inteligência artificial e desenvolvedores sobre a arquitetura, regras operacionais, segurança, boas práticas e protocolos de trabalho no projeto **Cantina Web — Colégio Monteiro Lobato** durante a fase de **manutenção, sustentação e evolução contínua**.

---

## 1. Idioma e Comunicação

* Responda e comunique-se sempre em **português do Brasil**.
* Comentários de código, mensagens de commit, documentações e logs devem ser redigidos em português do Brasil.

---

## 2. Protocolo Obrigatório de Manutenção e Mudanças Futuras

Todo agente de IA deve seguir rigorosamente o seguinte protocolo de trabalho em qualquer chat ou sessão de manutenção:

### Antes de qualquer alteração:
1. Ler `docs/MANUTENCAO.md`.
2. Ler `docs/FSD.md`.
3. Ler `docs/DESIGN.md`, se a alteração envolver interface.
4. Ler `docs/STATUS.md`.
5. Ler `docs/ERROS.md`.
6. Entender o pedido do usuário.
7. Explicar o plano antes de alterar arquivos.

> **ATENÇÃO À PORTABILIDADE DOS CAMINHOS:**
> - Use sempre caminhos relativos à raiz do projeto (como `docs/FSD.md`, `app/core/Router.php`).
> - Não transformar estes caminhos em links absolutos.
> - Não usar links `file:///`.
> - Não registrar caminhos locais da máquina atual dentro deste arquivo ou dos arquivos de documentação.

### Depois de qualquer alteração:
1. Testar o que foi alterado (executar testes automatizados com `docker compose exec app php tests/...` ou via CLI).
2. Atualizar `docs/STATUS.md` com o que foi construído, alterado ou testado.
3. Registrar erro e solução em `docs/ERROS.md`, se houver ocorrências.
4. Fazer commit ou entregar os comandos para o usuário executar no Git.
5. Explicar ao usuário como validar a alteração na prática.

---

## 3. Stack Tecnológica e Arquitetura

* **Backend:** PHP 8.2+ orientado a objetos, modular, utilizando o padrão arquitetural MVC nativo. Sem uso de frameworks monolíticos externos (como Laravel, Symfony ou CodeIgniter).
* **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+, utilizando estritamente o driver nativo `PDO_MYSQL` com Prepared Statements nativos sem emulação (`ATTR_EMULATE_PREPARES => false`) e charset `utf8mb4`.
* **Frontend:**
  * HTML5 semântico;
  * CSS3 corporativo de alta densidade e folhas de estilo customizadas baseadas no `docs/DESIGN.md` (`assets/css/custom.css`);
  * JavaScript puro (Vanilla JS, ES6+) em `assets/js/app.js`, sem frameworks reativos (React, Vue, Angular) ou dependências pesadas de runtime;
  * Bootstrap 5.3.3 compilado e servido exclusivamente de arquivos locais em `assets/css/bootstrap.min.css` e `assets/js/bootstrap.bundle.min.js` (sem dependência de links CDN externos).
* **Bibliotecas e Microsserviços Auxiliares:**
  * Microsserviço de impressão PDF: Gotenberg 8 (Chromium headless) orquestrado via Docker para relatórios A4 timbrados simétricos (14mm) e cupons térmicos contínuos em bobina de 80mm;
  * Leitura e extração de XML de NF-e: Extensões nativas `SimpleXML` e `DOMDocument`;
  * Leitura de planilhas escolares: Leitor nativo CSV via streaming com `fgetcsv` prioritário e `PhpSpreadsheet` para arquivos `.xlsx`;
  * Gerador Pix / QR Code: Codificação EMVCo nativa pura com cálculo polinomial CRC16 e matriz Reed-Solomon em PHP puro.

---

## 4. Ambientes do Projeto e Comandos Principais

* **Desenvolvimento Local:**
  * Docker e Docker Compose (`docker-compose.yml`) com contêineres: `cantina_app` (Apache/PHP 8.2), `cantina_db` (MySQL 8.0) e `cantina_gotenberg` (Chromium PDF).
  * Acesso local: `http://localhost:8080`.
* **Produção:**
  * Hospedagem compartilhada gerenciada com Apache, PHP 8.2+ e MySQL na plataforma Hostnet.
  * O código é 100% portável e opera tanto na raiz quanto em subdiretórios (ex: `/cantina/`).
* **Comandos Principais:**
  * Iniciar ambiente Docker: `docker compose up -d`
  * Parar ambiente Docker: `docker compose down`
  * Logs da aplicação: `docker compose logs -f app`
  * Executar migrações do banco de dados: `docker compose exec app php database/runner.php`
  * Status das migrações: `docker compose exec app php database/runner.php --status`
  * Executar suítes de teste: `docker compose exec app php tests/<arquivo_de_teste>.php`

---

## 5. Resumo da Estrutura de Diretórios

```text
├── index.php                        # Front Controller e ponto único de entrada
├── .htaccess                        # Reescreve URLs para o index.php e protege arquivos ocultos
│
├── config/                          # Configurações do sistema protegidas por .htaccess
│   ├── config.example.php           # Modelo versionado sem segredos
│   ├── config.php                   # Credenciais locais de banco, timeouts e constantes globais (ignorado no Git)
│   └── database.php                 # Fábrica de conexão singleton PDO com MySQL
│
├── app/                             # Núcleo da aplicação MVC protegido por .htaccess
│   ├── core/                        # Classes fundamentais (Router, Controller, Model, Session, Auth, RBAC, ErrorHandler)
│   ├── controllers/                 # Controladores por módulo funcional
│   ├── models/                      # Modelos de dados e regras de negócio com transações PDO
│   ├── views/                       # Templates e telas divididos por contexto (layouts, partials, portal, operacional, admin, etc.)
│   └── services/                    # Adaptadores de integração (Pix, WhatsApp, XML NF-e, PDF/Gotenberg, Planilhas)
│
├── database/                        # Migrações e infraestrutura de dados
│   ├── migrations/                  # 26 migrações sequenciais oficiais (001 a 026)
│   └── runner.php                   # Executor seguro de migrações (CLI ou HTTP com token)
│
├── storage/                         # Arquivos de log, temporários e uploads protegidos
│   ├── logs/                        # Logs locais de contingência (error.log, security.log)
│   └── uploads/
│       ├── private/                 # Planilhas escolares e XMLs fiscais (100% blindado contra acesso HTTP)
│       └── public/                  # Imagens públicas de produtos e logotipo (nomes ofuscados com hash/UUID)
│
├── assets/                          # Recursos estáticos locais do frontend
│   ├── css/                         # Folhas de estilo locais (bootstrap.min.css, custom.css)
│   ├── js/                          # Scripts interativos (bootstrap.bundle.min.js, app.js)
│   └── img/                         # Imagens estáticas e logotipo oficial (logo-colegio.jpg)
│
├── docs/                            # Documentação viva de engenharia e manuais
└── tests/                           # Suítes de testes automatizados e integração
```

---

## 6. Diretrizes Rigorosas de Segurança

* **Injeção SQL:** Utilizar exclusivamente o driver PDO com Prepared Statements nativos e bindings explícitos em todas as operações de banco. Nunca concatenar variáveis diretamente em strings SQL.
* **Prepared Statements Estritos:** Com `ATTR_EMULATE_PREPARES => false`, nunca repetir o mesmo nome de placeholder nomeado (ex: `:busca`) na mesma query; use nomes distintos (`:busca1`, `:busca2`).
* **Cross-Site Scripting (XSS):** Todas as saídas de variáveis dinâmicas em templates HTML devem ser escapadas com `htmlspecialchars($valor, ENT_QUOTES, 'UTF-8')`.
* **Cross-Site Request Forgery (CSRF):** Todos os formulários HTML que executem mutações (POST) devem incluir token CSRF gerado pela classe `Session` (`<?= csrfField() ?>`), validado obrigatoriamente no controller via `Session::validateCsrfToken()`.
* **Armazenamento Seguro de Senhas:** Utilizar obrigatoriamente `password_hash()` com algoritmo Bcrypt e verificar com `password_verify()`. Nunca armazenar senhas em texto puro ou com hashes fracos (MD5, SHA1).
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

## 7. Cuidados para Não Quebrar Funcionalidades Existentes

* **Transações Atômicas:** Sempre que uma regra de negócio envolver alterações em mais de uma tabela (ex: pedidos + carteira + estoque), utilize `$pdo->beginTransaction()`, `$pdo->commit()` e `$pdo->rollBack()`.
* **Soft Delete:** Nunca delete registros diretamente de tabelas contábeis ou cadastros mestres com histórico financeiro (alunos, turmas, produtos, colaboradores); utilize sempre `deleted_at`.
* **Anti-Slop Enterprise UI:** Respeite o padrão visual sóbrio do `docs/DESIGN.md`:
  - Cabeçalhos de tabela neutros claros (`#f8fafc`);
  - Tipografia tabular em todos os valores monetários (`.font-tabular`);
  - Acento único no azul institucional `#1d4ed8`;
  - Nunca utilizar caixas cinzas nativas `confirm()` do navegador: use sempre o modal corporativo assíncrono via atributo `data-confirm` ou `window.confirmarAcao()`.
* **Botões de Texto e Widgets Interativos (Padrão Neutro):** Sempre que um texto, valor ou widget informativo (como exibição de saldo, filtros ou atalhos) também funcionar como botão ou link clicável, **NUNCA usar fundos coloridos** (como verde, amarelo ou azul chamativo). Seguir rigorosamente o padrão corporativo neutro do botão de menu do usuário (`.btn.btn-enterprise.btn-secondary`: fundo branco/neutro, borda sutil cinza `#cbd5e1`, texto neutro escuro `#1e293b` e hover sutil `#f8fafc`).
* **Cache Busting nos Assets:** Ao alterar folhas de estilo em `assets/css/custom.css` ou scripts em `assets/js/app.js`, incremente a versão dos arquivos nos layouts (ex: `custom.css?v=6.1.1`) para evitar inconsistências no navegador do cliente.

---

## 8. Versionamento com Git

* Ao concluir uma tarefa de manutenção, confira `git status`.
* Assegure que nenhum arquivo de segredo (`config/config.php`), uploads privados ou logs seja versionado.
* Crie commits atômicos com mensagens claras em português no modo imperativo (ex: `git commit -m "Corrige alinhamento do relatório de corte"`).
* Lembre o usuário de sincronizar o repositório com `git push`.
