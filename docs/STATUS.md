# Status do Projeto — Cantina Web (Colégio Monteiro Lobato)

Este documento reflete o estado em tempo real da implementação do sistema **Cantina Web — Colégio Monteiro Lobato**, registrando o progresso fase a fase conforme o `docs/PLANO.md`.

---

## Resumo Geral

* **Fase Atual:** Fase 6 — Autenticação, Sessão Segura e Controle de Acesso (RBAC)
* **Status da Fase 6:** Concluída com Sucesso [x]
* **Próxima Fase:** Fase 7 — Configurações Globais e Cadastros Mestres com Soft Delete
* **Última Atualização:** 2026-09-17 10:18:00 (Fase 6 finalizada e validada)

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

### [x] Fase 3 — Conexão de Banco de Dados e Migrações Estruturais/Cadastrais (001 a 012)
* [x] Implementação de `config/database.php` (Singleton PDO com Prepared Statements estritos nativos `ATTR_EMULATE_PREPARES => false`, charset `utf8mb4` e contingência automática em `storage/logs/error.log`).
* [x] Implementação do runner robusto e idempotente em `database/runner.php` com suporte a CLI (`--status`, `--rollback`) e HTTP seguro protegido por token (`?token=...`).
* [x] Criação e execução com 100% de sucesso das migrações estruturais e cadastrais 001 a 012:
  * `001_create_migrations_table.php`: Tabela `migrations_controle`.
  * `002_create_configuracoes_globais_table.php`: Tabela `configuracoes_globais` e carga com parâmetros oficiais da instituição.
  * `003_create_usuarios_table.php`: Tabela `usuarios` (operadores e administradores) com soft delete e inserção de admin inicial para testes.
  * `004_create_responsaveis_table.php`: Tabela `responsaveis` (titulares familiares, CPF único e travas MED).
  * `005_create_turmas_table.php`: Tabela `turmas` (série, nome e ordenação por turno com soft delete).
  * `006_create_alunos_table.php`: Tabela `alunos` (matrícula única, vínculo a responsável e turma com soft delete).
  * `007_create_restricoes_alimentares_table.php`: Catálogo oficial dos 8 alérgenos do FSD (Glúten, Lactose, Amendoim, etc.).
  * `008_create_aluno_restricoes_table.php`: Pivot N:N entre alunos e alérgenos com `CASCADE` e `RESTRICT`.
  * `009_create_colaboradores_table.php`: Tabela `colaboradores` (desconto em folha com foto e soft delete).
  * `010_create_categorias_table.php`: Tabela `categorias` e carga das 4 categorias padrão do FSD.
  * `011_create_produtos_table.php`: Tabela `produtos` (salgados diários e industrializados com pausa rápida e soft delete).
  * `012_create_produto_alergenos_table.php`: Pivot N:N entre produtos e alérgenos.
* [x] Proteção granular de acesso web no `.htaccess` de `database/` liberando `runner.php` (com autenticação mandatória por token) e blindagem absoluta de `database/migrations/.htaccess`.
* [x] Criação e aprovação da suíte de testes `tests/fase3_test.php` (41 asserções cobrindo conexão, dados mestres, chaves estrangeiras, constraints e soft delete do `Model`).

---

### [x] Fase 4 — Migrações Transacionais, Fiscais, Financeiras e Auditoria (013 a 026)
* [x] Criação e execução das 14 migrações transacionais restantes (013 a 026), completando o schema relacional oficial de 26 tabelas do `docs/FSD.md`:
  * `013_create_notas_fiscais_xml_table.php`: Tabela `notas_fiscais_xml` (cabeçalhos de NF-e importadas com chave de 44 dígitos, emitente e valores).
  * `014_create_itens_nota_fiscal_xml_table.php`: Tabela `itens_nota_fiscal_xml` (itens da nota com memória de fator de conversão de embalagens em unidades e CASCADE na NF-e).
  * `015_create_estoque_movimentacoes_table.php`: Tabela `estoque_movimentacoes` (entradas por fornecedor/XML/sobra, saídas de venda/encomenda e perdas com motivo padronizado 'avaria', 'vencimento', 'sobra_producao', 'ajuste_contagem').
  * `016_create_pedidos_table.php`: Tabela `pedidos` (agendamentos com travas de corte matutino 08:00 e vespertino 13:30, status e índice composto de busca de corte).
  * `017_create_itens_pedido_table.php`: Tabela `itens_pedido` (produtos solicitados, quantidade, preço unitário, subtotal e CASCADE no pedido).
  * `018_create_tickets_table.php`: Tabela `tickets` (bobina térmica contínua de 80mm com tag de alérgenos, controle de 2ª via e status ativo/impresso/estornado/cancelado).
  * `019_create_sessoes_caixa_table.php`: Tabela `sessoes_caixa` (abertura com Fundo de Troco, fechamentos matutino 11:00 e vespertino 16:00, apuração de sobras/faltas).
  * `020_create_vendas_balcao_table.php`: Tabela `vendas_balcao` (vendas avulsas no PDV com troco e vendas para colaboradores sob Desconto em Folha).
  * `021_create_itens_venda_balcao_table.php`: Tabela `itens_venda_balcao` (detalhamento dos itens comercializados no balcão).
  * `022_create_transacoes_carteira_table.php`: Tabela `transacoes_carteira` (livro-razão contábil e extrato analítico da família com saldo linha a linha, recargas, débitos, estornos e MED).
  * `023_create_calendario_letivo_table.php`: Tabela `calendario_letivo` (dias letivos, feriados, recessos e rotina de cancelamento emergencial em lote).
  * `024_create_logs_auditoria_table.php`: Tabela `logs_auditoria` (trilha de negócio para estornos, 2ª via de tickets, baixas de perda e ajustes manuais com justificativa e IP).
  * `025_create_logs_erros_table.php`: Tabela `logs_erros` (repositório centralizado de exceções com rastreabilidade técnica e contingência).
  * `026_create_logs_seguranca_table.php`: Tabela `logs_seguranca` (trilha de eventos de segurança, falhas de login, bloqueios MED e acessos negados por RBAC).
* [x] Criação e validação de chaves estrangeiras com regras estritas (`RESTRICT` para proteção contábil/histórica, `CASCADE` para itens/tickets filhos e `SET NULL` para trilhas opcionais).
* [x] Criação de índices de alta performance para cortes operacionais, busca de tickets, sessões de caixa por turno e extrato familiar.
* [x] Criação e aprovação com 100% de sucesso da suíte de testes `tests/fase4_test.php` (60 asserções cobrindo todas as 14 tabelas, ciclos de vida transacionais, deleções em cascata e restrições referenciais).

---

### [x] Fase 5 — Identidade Visual Institucional e Layouts Base (DESIGN.md)
* [x] Criação dos templates mestres em `app/views/layouts/`:
  * `header.php`: Topo base HTML5 com metadados responsivos, favicon institucional e injeção de assets locais (sem CDN externa).
  * `footer.php`: Rodapé institucional com scripts locais e lema "Valores para o Futuro!".
  * `sidebar.php`: Barra lateral operacional de alta densidade (240px fixa em desktop) com seções Operacional, Estoque e Administração, badge do operador e logout seguro.
  * `portal.php`: Layout mestre completo para o Portal da Família com widget de saldo familiar em destaque com tipografia tabular (`font-variant-numeric: tabular-nums`) e navegação limpa.
  * `admin.php`: Layout mestre completo para o Painel da Cantina com duas colunas (Sidebar + Topbar operacional com chips de turno/caixa e área dinâmica `$content`).
  * `auth.php`: Layout minimalista centralizado para telas de autenticação e primeiro acesso com logotipo circular oficial ampliado para 84px com micro-aro sutil.
* [x] Desenvolvimento de componentes parciais reutilizáveis em `app/views/partials/`:
  * `alert.php`: Alertas corporativos com alto contraste WCAG AA, ícones SVG semáforo-free e integração automática com mensagens flash da `Session`.
  * `modal.php`: Modais acessíveis com foco visível nítido (`:focus-visible`) e botões com feedback tátil físico.
  * `empty_state.php`: Estado vazio padronizado com a frase obrigatória *"Não há nada para mostrar aqui"*.
  * `loading.php`: Estado de carregamento com spinner azul cobalto institucional (`#1d4ed8`) e skeletons com efeito shimmer.
  * `stat_strip.php`: Faixa contínua de métricas executivas com divisores finos verticais e números tabulares.
* [x] Refinamento do Design System em `assets/css/custom.css` e `assets/js/app.js`:
  * Acento único no azul cobalto institucional `#1d4ed8` (hover `#1e40af`, foco `#2563eb`).
  * Numerais tabulares obrigatórios (`.font-tabular`).
  * Microinteração tátil de clique físico nos botões (`:active { transform: translateY(1px) scale(0.985); }`).
  * Funções utilitárias JavaScript para toggle da sidebar no mobile (`toggleSidebar()`) e controle de modais (`abrirModal()`, `fecharModal()`).
* [x] Criação do controlador de vitrine `app/controllers/ShowcaseController.php` e views de homologação visual (`app/views/showcase/portal.php`, `admin.php` e `components.php`).
* [x] Registro das rotas `/design/portal`, `/design/admin` e `/design/components` no Front Controller `index.php`.
* [x] Suíte de testes automatizados `tests/fase5_test.php` criada e aprovada com 74 asserções (100% de sucesso).
* [x] Testes de regressão das fases 2, 3 e 4 executados e aprovados com 100% de sucesso.
* [x] **Ajustes Pós-Validação Visual da Fase 5:**
  * Correção de escala da logo circular institucional no Portal da Família (`portal.php`), aplicando atributos defensivos `width="38" height="38"` e `style="width: 38px; height: 38px; ..."` em todos os layouts.
  * Implementação de query de versão (`?v=5.0.1`) nos links de `custom.css` para invalidar caches antigos do navegador.
  * Atualização da métrica executiva de infraestrutura na tela Home (`/`) e na API (`/api/status`) sincronizando o indicador para "Fase 5 Concluída".
  * Teste `tests/web_simulation_test.php` atualizado e aprovado com 5/5 requisições simuladas com sucesso.
  * **Refinamento de UX & Despoluição Visual (Feedback do Usuário):**
    * Remoção do botão redundante `+ Recarregar` da barra superior no Portal da Família, transformando o widget de saldo em um componente informativo discreto com link para o extrato.
    * Centralização da ação de recarga dentro do contexto do card "Carteira Familiar" com o botão sóbrio `Recarregar Saldo`.
    * Remoção dos colchetes e caixas pesadas da indicação de alérgenos (`[LACTOSE]`), substituindo por destaque limpo em negrito (`Lactose`) com cor âmbar corporativa.
    * Padronização de botões de texto/widgets informativos no padrão corporativo neutro sem cores (`.btn.btn-enterprise.btn-secondary`), alinhando o botão de saldo exatamente ao design do botão de menu do usuário. Regra formalizada em `AGENTS.md` e `docs/DESIGN.md`.

---

### [x] Fase 6 — Autenticação, Sessão Segura e Controle de Acesso (RBAC)
* [x] Implementação de `app/core/Auth.php`:
  * Autenticação segregada: Responsáveis familiares via CPF e senha; Equipe interna via usuário e senha.
  * Hashing seguro de senhas com algoritmo Bcrypt (`password_hash` / `password_verify`).
  * Regeneração de identificador de sessão (`Session::regenerate(true)`) no login para neutralizar ataques de Session Fixation.
  * Mecanismo de bloqueio automático por força bruta (rate limiting) após 5 tentativas incorretas na janela de 15 minutos com auditoria.
  * Encerramento seguro de sessão com limpeza de chaves e trilha de logout.
* [x] Implementação de `app/core/RBAC.php`:
  * Matriz completa de permissões funcionais baseada na Seção 8 do `docs/FSD.md` cobrindo os 4 perfis (`responsavel`, `operador`, `administrador`, `colaborador`).
  * Middlewares de rota: `requireAuth`, `requireGuest` e `requireRole` com redirecionamento amigável e resposta JSON 403 para requisições assíncronas.
  * Trilha de auditoria automática em `logs_seguranca` para infrações de acesso negado.
* [x] Implementação dos Modelos de Domínio em `app/models/`:
  * `Usuario.php`: Gestão de operadores e administradores com suporte a soft delete (`deleted_at`).
  * `Responsavel.php`: Gestão de titulares familiares, higienização de CPF, verificação de pendência de primeiro acesso, consulta de dependentes e formalização de termos.
* [x] Implementação do Adaptador `app/services/WhatsAppService.php`:
  * Geração de códigos criptográficos de 6 dígitos (`random_int(100000, 999999)`).
  * Envio transacional de códigos de primeiro acesso e recuperação com suporte a modo mock/local gravando em `storage/logs/security.log` e integração via API REST.
* [x] Desenvolvimento do Controlador `app/controllers/AuthController.php`:
  * `login` e `loginPost`: Tela unificada em abas com validação CSRF, máscara de CPF e redirecionamento dinâmico.
  * `logout`: Encerramento seguro com limpeza de sessão.
  * `primeiroAcesso`, `primeiroAcessoSolicitar`, `primeiroAcessoCodigo`, `primeiroAcessoValidarCodigo`, `primeiroAcessoSenha` e `primeiroAcessoConcluir`: Fluxo de ativação em 3 etapas com validação de código WhatsApp, criação de senha e gravação de timestamp de aceite dos termos.
  * `recuperarSenha`, `recuperarSenhaSolicitar`, `recuperarSenhaCodigo` e `recuperarSenhaRedefinir`: Fluxo de redefinição de senha com código temporário.
  * `termos`: Visualização dos Termos e Políticas da Cantina.
* [x] Desenvolvimento dos Controladores e Telas de Destino:
  * `PortalController.php` & `app/views/portal/index.php`: Dashboard familiar com saldo em destaque, alerta MED (se houver) e lista de alunos vinculados.
  * `AdminController.php` & `app/views/admin/index.php`: Dashboard operacional com stat-strip executivo e atalhos para corte, tickets, PDV e caixa.
* [x] Construção das Telas em `app/views/auth/` alinhadas ao `docs/DESIGN.md`:
  * `login.php`: Alternador sóbrio entre Portal da Família e Equipe da Cantina.
  * `primeiro_acesso_cpf.php`: Inserção de CPF com máscara.
  * `primeiro_acesso_codigo.php`: Campo centralizado de 6 dígitos com fonte tabular e dica de código em ambiente local.
  * `primeiro_acesso_senha.php`: Definição de senha com confirmação e checkbox obrigatório de termos.
  * `recuperar_senha.php` & `recuperar_senha_codigo.php`: Telas de recuperação de credenciais.
  * `termos.php`: Termos de uso e políticas nutricionais/operacionais oficiais da escola.
* [x] Registro das rotas públicas de autenticação e rotas protegidas por middleware RBAC no Front Controller `index.php`.
* [x] Bateria de testes automatizados `tests/fase6_test.php` executada e homologada com 87 asserções aprovadas com 100% de sucesso.
* [x] Suítes de testes de regressão (Fases 2, 3, 4, 5 e simulação web) reexecutadas e 100% aprovadas.
* [x] **Ajustes de Usabilidade e Design na Autenticação (Feedback do Usuário):**
  * Remoção do atributo `autofocus` nos campos de entrada de login (CPF e usuário), impedindo o foco automático indesejado ao carregar a página ou alternar abas.
  * Suavização do foco acessível em campos de texto (`assets/css/custom.css`), removendo o contorno agressivo com offset (`outline: none !important; box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.14)`) para um visual corporativo refinado.
  * Substituição das abas de texto por um alternador segmentado com botões encapsulados (`.auth-toggle-group` e `.auth-toggle-btn`), destacando visualmente as opções de login ("Portal da Família" e "Equipe da Cantina") dentro do padrão corporativo sóbrio.
  * Atualização da versão dos assets nos layouts para `?v=5.0.2` para invalidação imediata de cache no navegador.

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

Iniciar a **Fase 7 — Configurações Globais e Cadastros Mestres com Soft Delete**, implementando os modelos `Configuracao.php`, `Categoria.php`, `Produto.php`, `Colaborador.php`, `Turma.php`, o controlador administrativo e as telas de gestão de parâmetros da cantina, cardápio de produtos com botão "Ativo/Pausado", colaboradores e turmas escolares.

