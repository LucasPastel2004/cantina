# Status do Projeto — Cantina Web (Colégio Monteiro Lobato)

Este documento reflete o estado em tempo real da implementação do sistema **Cantina Web — Colégio Monteiro Lobato**, registrando o progresso fase a fase conforme o `docs/PLANO.md`.

---

## Resumo Geral

* **Fase Atual:** Fase 2 — Núcleo Arquitetural MVC e Mecanismo de Logs/Contingência
* **Status da Fase 2:** Concluída com Sucesso [x]
* **Próxima Fase:** Fase 3 — Conexão de Banco de Dados e Migrações Estruturais/Cadastrais (001 a 012)
* **Última Atualização:** 2026-09-16 12:05:00 (Fase 2 finalizada e validada)

---

## Acompanhamento das Fases

### [x] Fase 1 — Infraestrutura, Base do Projeto e Assets Locais
* [x] Árvore oficial de diretórios criada conforme `docs/FSD.md`.
* [x] Configuração centralizada em PHP nativo (`config/config.example.php` e `config/config.php`) sem uso de `.env`.
* [x] Proteção rigorosa de diretórios internos via `.htaccess` (`config/`, `app/`, `database/`, `storage/`).
* [x] Permissão restrita a imagens estáticas e bloqueio de scripts em `storage/uploads/public/.htaccess`.
* [x] Regras de reescrita no `.htaccess` da raiz direcionando requisições ao `index.php`.
* [x] Cópia e validação do logotipo oficial circular do Colégio Monteiro Lobato (`assets/img/logo-colegio.jpg`).
* [x] Download e inclusão local dos arquivos compilados do Bootstrap 5.3.3 (`assets/css/bootstrap.min.css` e `assets/js/bootstrap.bundle.min.js`).
* [x] Criação da folha de estilo corporativa de alta densidade `assets/css/custom.css` com as especificações do `docs/DESIGN.md`.
* [x] Criação de `assets/js/app.js` com utilitários Vanilla JS e máscara monetária.
* [x] Configuração do `docker-compose.yml` (Apache, PHP 8.2 e MySQL 8.0).
* [x] Criação do Front Controller inicial `index.php` com constante de proteção `CANTINA_SISTEMA` e tela de verificação.
* [x] Criação de `database/runner.php` inicial para execução segura de migrações.
* [x] Inicialização de arquivos de contingência `storage/logs/error.log` e `storage/logs/security.log`.
* [x] Criação e reforço do `.gitignore` protegendo credenciais reais (`config/config.php`), logs, uploads privados (LGPD), backups e arquivos sensíveis.
* [x] Criação do `.gitattributes` para padronização de quebras de linha (LF/CRLF) e tratamento correto de mídias/binários.
* [x] Adição de arquivos `.gitkeep` em todos os diretórios vazios da arquitetura MVC para preservação no versionamento.
* [x] Inicialização do repositório Git local com branch padrão `main`.
* [x] Auditoria de segurança pré-commit confirmando que nenhum dado sensível ou segredo será rastreado.
* [x] Execução do primeiro commit do projeto ("Estrutura inicial do projeto").
* [x] Repositório remoto privado criado no GitHub (https://github.com/LucasPastel2004/cantina) e primeiro push realizado com sucesso para origin/main.

---

### [x] Fase 2 — Núcleo Arquitetural MVC e Mecanismo de Logs/Contingência
* [x] Implementação de `app/core/Router.php` com suporte a verbos HTTP (`GET`, `POST`, `PUT`, `DELETE`), rotas dinâmicas `{param}`, middlewares e resolução transparente de subpastas.
* [x] Implementação de `app/core/Controller.php` com métodos de renderização de views com injeção de dados (`render`), respostas estruturadas em JSON (`json`), redirecionamentos seguros (`redirect`), e sanitização/validação com CSRF (`validate`).
* [x] Implementação de `app/core/Model.php` com Prepared Statements estritos nativos PDO, transações atômicas (`beginTransaction`, `commit`, `rollBack`), proteção contra SQL Injection e suporte a soft delete (`deleted_at`).
* [x] Implementação de `app/core/Session.php` com cookies seguros (`HttpOnly`, `SameSite=Lax`, `Secure` em HTTPS), timeout por inatividade (60 min conforme FSD), regeneração de ID, mensagens Flash e tokens CSRF criptográficos (`generateCsrfToken`, `validateCsrfToken`, `csrfField`).
* [x] Implementação de `app/core/ErrorHandler.php` com captura global (`set_exception_handler`, `set_error_handler`, `register_shutdown_function`), tela de erro segura com UUID rastreável e contingência formatada em `storage/logs/error.log` com rotação automática em 5 MB.
* [x] Criação das views de erro institucionais `app/views/errors/404.php` e `app/views/errors/500.php` alinhadas ao `docs/DESIGN.md`.
* [x] Criação de `app/controllers/HomeController.php` e `app/views/home/index.php` demonstrando o ciclo completo do MVC e tela interativa de testes.
* [x] Atualização do Front Controller `index.php` integrando Autoloader PSR-4 nativo, inicialização de ErrorHandler, Session e despacho pelo Router.
* [x] Bateria de testes unitários/integração (`tests/fase2_test.php`) com 23 asserções executadas e aprovadas com 100% de sucesso no PHP 8.2.
* [x] Bateria de simulações de requisições web (`tests/web_simulation_test.php`) validando rotas estáticas, dinâmicas, API JSON, 404 e tela de contingência 500 com código UUID.
* [x] Integração da skill `design-taste-frontend` e elaboração do novo `docs/DESIGN.md` (V2 — Anti-Slop Enterprise Edition): cabeçalhos de tabela neutros claros (`#f8fafc`), eliminação de badges estilo semáforo, indicadores tipográficos com bullet pontual (`.status-indicator`), tags de código sóbrias (`.code-tag`) e acento único no azul cobalto (`#1d4ed8`). Refatoração das views `app/views/home/index.php`, `app/views/errors/404.php` e `app/views/errors/500.php`.

---

### [ ] Fase 3 — Conexão de Banco de Dados e Migrações Estruturais/Cadastrais (001 a 012)
* [ ] Implementação de `config/database.php` (Singleton PDO).
* [ ] Implementação do runner com controle de execução em `database/runner.php`.
* [ ] Execução das migrações 001 a 012 (`migrations_controle`, `configuracoes_globais`, `usuarios`, `responsaveis`, `turmas`, `alunos`, `restricoes_alimentares`, `aluno_restricoes`, `colaboradores`, `categorias`, `produtos`, `produto_alergenos`).

---

### [ ] Fase 4 — Migrações Transacionais, Fiscais, Financeiras e Auditoria (013 a 026)
* [ ] Execução das migrações 013 a 026 (`notas_fiscais_xml`, `itens_nota_fiscal_xml`, `estoque_movimentacoes`, `pedidos`, `itens_pedido`, `tickets`, `sessoes_caixa`, `vendas_balcao`, `itens_venda_balcao`, `transacoes_carteira`, `calendario_letivo`, `logs_auditoria`, `logs_erros`, `logs_seguranca`).
* [ ] Criação de constraints e índices de alta performance nas tabelas transacionais.

---

### [ ] Fase 5 — Identidade Visual Institucional e Layouts Base (DESIGN.md)
* [ ] Criação dos templates mestres em `app/views/layouts/` (`header.php`, `footer.php`, `sidebar.php`).
* [ ] Criação dos componentes visuais em `app/views/partials/` (`alert.php`, `modal.php`, `empty_state.php`, `loading.php`).

---

### [ ] Fase 6 — Autenticação, Sessão Segura e Controle de Acesso (RBAC)
* [ ] Implementação de `app/core/Auth.php` e `app/core/RBAC.php`.
* [ ] Tela de login unificada (`app/views/auth/login.php`).
* [ ] Fluxo de primeiro acesso e recuperação com WhatsApp.
* [ ] Modal de aceite dos Termos e Políticas da Cantina.

---

### [ ] Fase 7 — Configurações Globais e Cadastros Mestres com Soft Delete
* [ ] Tela de Configurações Globais (`app/views/admin/configuracoes.php`).
* [ ] CRUDs com soft delete: Categorias, Produtos (com botão ativo/pausado), Colaboradores e Turmas.

---

### [ ] Fase 8 — Importação Escolar em Lote e Gestão Ativa de Restrições Alimentares
* [ ] Serviço `app/services/SpreadsheetService.php` (CSV/XLSX).
* [ ] Interface de importação escolar em lote.
* [ ] Vínculo de alérgenos aos dependentes e catálogo de produtos.

---

### [ ] Fase 9 — Carteira Digital Familiar e Gateway Pix Dinâmico
* [ ] Serviço `app/services/PixService.php` e `app/controllers/WebhookController.php`.
* [ ] Interface da carteira familiar e recargas com QR Code e Copia e Cola.

---

### [ ] Fase 10 — Agendamento Semanal de Lanches e Regras Rígidas de Corte
* [ ] Catálogo semanal com liberação às sextas 18:00 e travas de corte às 08:00 e 13:30.
* [ ] Cancelamento e edição com estorno/compensação imediata.
* [ ] Modal de alerta amarelo em caso de conflito de alérgenos.

---

### [ ] Fase 11 — Operação de Encomendas e Gestão de Fornecedores
* [ ] Painel de corte do turno com consolidação matemática de salgados.
* [ ] Ação "Copiar para WhatsApp" e relatório em PDF para fornecedores.
* [ ] Tela de conferência física e confirmação de recebimento com liquidação simultânea.

---

### [ ] Fase 12 — Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
* [ ] Emissão em lote ordenada por Série/Turma em bobina 80mm com tag de alérgenos.
* [ ] Tela de consulta e 2ª via com marcação indelével e registro obrigatório em auditoria.

---

### [ ] Fase 13 — Ponto de Venda (PDV) e Operação de Balcão
* [ ] PDV de vendas avulsas em dinheiro (com troco) e Pix balcão.
* [ ] Consumo de colaboradores (Desconto em Folha) com confirmação visual de foto e cargo.
* [ ] Estorno de balcão e aproveitamento de sobras de alunos ausentes.

---

### [ ] Fase 14 — Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas
* [ ] Leitor de XML de NF-e com conferência e fator multiplicador de conversão.
* [ ] Registro obrigatório de perdas com motivo padronizado e auditoria.

---

### [ ] Fase 15 — Controle Financeiro e Fechamento de Caixas por Turno
* [ ] Abertura de caixa com Fundo de Troco por turno.
* [ ] Fechamento de caixa pontual às 11:00 e 16:00 com conciliação físico vs sistema.
* [ ] Espelho de fechamento em PDF timbrado para assinatura.

---

### [ ] Fase 16 — Tratamento de Contestações Bancárias e Mecanismo Pix MED
* [ ] Webhook de notificação MED com débito imediato na carteira.
* [ ] Bloqueio automático de novas compras em caso de saldo negativo e aviso WhatsApp.

---

### [ ] Fase 17 — Relatórios Gerenciais, Exportações (A4/CSV), Revisão de Segurança e Entrega
* [ ] Relatórios em PDF A4 timbrado (extrato de consumo, vendas por produto, fechamento).
* [ ] Exportação mensal em CSV e PDF de colaboradores para o RH.
* [ ] Auditoria final de segurança, validação de acessibilidade e critérios de aceitação.

---

## Próximo Passo Recomendado

Iniciar a **Fase 2 — Núcleo Arquitetural MVC e Mecanismo de Logs/Contingência**, criando as classes base `Router`, `Controller`, `Model`, `Session`, `Auth`, `RBAC` e `ErrorHandler` em `app/core/`.
