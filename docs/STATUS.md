# Status do Projeto — Cantina Web (Colégio Monteiro Lobato)

Este documento reflete o estado em tempo real da implementação do sistema **Cantina Web — Colégio Monteiro Lobato**, registrando o progresso fase a fase conforme o `docs/PLANO.md`.

---

## Resumo Geral

* **Fase Atual:** Fase 21 — Painel de Filtros Avançados e Relatório Oficial de Vendas por Período / Alimento
* **Status da Fase 21:** Concluída [x] (100% Homologada)
* **Status Geral do Projeto:** 21 de 24 Fases Concluídas (Planejamento das Fases 18 a 24 oficializado conforme diretrizes e documentos escolares)
* **Última Atualização:** 2026-09-30 12:35:00 (Ajuste visual da versão de impressão e espelho oficial A4: 1) Simulação realista de folha física de papel A4 em tela (`max-width: 200mm`, fundo `#cbd5e1`, sombra elegante e margens padronizadas); 2) Eliminação de menções a software de terceiros e tokens residuais no rodapé, substituindo pela identidade oficial do Colégio Monteiro Lobato • Sistema Cantina Web; 3) Barra de controle de impressão com navegação direta de retorno aos filtros; 4) Suíte `tests/fase21_test.php` expandida com 95 asserções 100% aprovadas).



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
  * Remoção do exemplo de nomes de usuário (`ex: operador-maria ou admin`) no campo de identificador, prevenindo vazamento de padrões de login e tentativas de enumeração de contas.
  * Correção do componente `app/views/partials/alert.php` para desempacotar arrays de mensagens flash de `Session::getFlash()`, eliminando o `TypeError` em `htmlspecialchars()` nas rotas `/portal` e `/login`.
  * Suporte a logout seguro via GET e POST com validação flexível de CSRF em `app/controllers/AuthController.php` e registro de rota em `index.php`.
  * Criação da suíte `tests/auth_flow_test.php` aprovada com 7/7 testes de renderização de alertas, login, logout e código mock.
  * Resolução de persistência de código mock entre requisições HTTP e adição de limpeza automática (teardown) em `tests/fase6_test.php`.
  * **Refinamentos Visuais e Controles Interativos da Barra Superior e Lateral:**
    * Restauração da barra superior (`.app-topbar`) para fundo branco puro (`#ffffff`) com linha divisória sutil e micro-sombra corporativa.
    * Calibração dos chips de turno, caixa e data para fundo suave `#f8fafc` com borda neutra sobre a topbar branca.
    * Implementação de acordeão retrátil com setas de rotação suave (`.nav-section-arrow`) para os blocos de categorias do menu (`Operacional`, `Estoque`, `Administração`) com memorização no `localStorage`.
    * Implementação de botão com seta (`◀`, `.btn-sidebar-collapse`) no cabeçalho da sidebar e botão inteligente com seta (`▶`, `.btn-sidebar-toggle`) na topbar para recolher e expandir a barra lateral inteira no desktop, com transição CSS suave e persistência no navegador.
    * Atualização da versão dos assets nos layouts para `?v=5.0.5` para invalidação imediata de cache no cliente.

---

### [x] Fase 7 — Configurações Globais e Cadastros Mestres com Soft Delete
* [x] Implementação de `app/models/Configuracao.php`:
  * Modelo singleton para obtenção e persistência de parâmetros institucionais em `configuracoes_globais`.
  * Validações de recarga mínima Pix (>= R$ 1,00), alerta de saldo baixo, timeout de sessão e limite de tentativas de login.
* [x] Implementação de `app/models/Categoria.php`:
  * CRUD com soft delete (`deleted_at`), rastreabilidade de auditoria (`created_by`, `updated_by`) e ordenação visual de exibição.
  * Validação de integridade referencial impedindo exclusão de categorias com produtos vinculados.
* [x] Implementação de `app/models/Produto.php`:
  * CRUD completo com suporte a tipo (`salgado_diario`, `industrializado_continuo`), turnos (`matutino`, `vespertino`, `ambos`), preços em reais e controle de estoque mínimo.
  * Botão e método de pausa rápida instantânea `togglePausa()` ("ativo" <-> "pausado") com resposta assíncrona para operação no balcão.
  * Relacionamento N:N com alérgenos (`produto_alergenos`) e sincronização atômica (`syncAlergenos`).
  * Soft delete preservando o histórico contábil de pedidos e caixas anteriores.
* [x] Implementação de `app/models/RestricaoAlimentar.php`:
  * Consulta aos 8 alérgenos oficiais do FSD para vinculação declaratória nos alimentos.
* [x] Implementação de `app/models/Colaborador.php`:
  * Gestão de professores e funcionários para desconto em folha com matrícula funcional única, cargo, status ativo e upload de foto de identificação para conferência presencial no PDV.
* [x] Implementação de `app/models/Turma.php`:
  * Gestão de turmas escolares por turno (`matutino`, `vespertino`), série e ordem de impressão sequencial na bobina térmica de 80mm com soft delete.
* [x] Atualização de `app/controllers/AdminController.php`:
  * Ações de leitura, formulários, gravação com CSRF, soft delete e alternância de pausa de produtos para todos os cadastros mestres.
  * Manipulador seguro de uploads de imagem com validação de extensão, MIME type, limite de 10 MB e renomeação aleatória (hash/UUID) em `storage/uploads/public/`.
* [x] Construção das Telas em `app/views/admin/` alinhadas ao `docs/DESIGN.md` (Anti-Slop Enterprise Edition):
  * `configuracoes.php`: Formulário institucional com prévia de logotipo e dados da cantina.
  * `categorias.php`: Tabela corporativa com ordenação de exibição, status pontual e modal de cadastro/edição.
  * `produtos.php`: Catálogo em alta densidade com miniaturas, badges de alérgenos discretos, valores com fonte tabular, filtros rápidos e botão de pausa instantânea.
  * `colaboradores.php`: Tabela com fotos circulares de identificação para o PDV e matrícula funcional.
  * `turmas.php`: Tabela com série, turno e ordem de impressão contínua.
* [x] Registro e proteção de rotas RBAC em `index.php` e sincronização dos links em `app/views/layouts/sidebar.php`.
* [x] Correção e garantia de visibilidade permanente da opção "Configurações Globais" na seção Administração da sidebar, compatibilizando chaves `role` e `perfil` em `Auth.php`, injetando `$user` automaticamente no `Controller::render()` e adicionando atalhos diretos na página inicial (`app/views/home/index.php`).
* [x] Refinamento visual em `app/views/admin/configuracoes.php` e `assets/css/custom.css` alinhando os campos numéricos à esquerda e eliminando a colisão de numerais com as setas de incremento (spin buttons) nativas do navegador.
* [x] Redesign visual completo e contemporâneo das interfaces de **Cardápio & Produtos** (`app/views/admin/produtos.php`) e **Categorias de Alimentos** (`app/views/admin/categorias.php`):
  * **Unificação e Padronização de Tabelas no CSS (`assets/css/custom.css`):**
    * Equiparação de `.table-enterprise` com `.table-data-grid` (cabeçalhos suaves em `#f8fafc` com texto em caixa alta discreto `#475569`, padding `10px 14px`, linhas de `40px` e hover sutil).
    * Eliminação da quebra feia de código de produtos (`PROD-TEST-F4`) com `white-space: nowrap;` e fonte mono tabular na classe `.code-tag`.
  * **Erradicação de Botões Vermelhos Berrantes ("Anti-Slop"):**
    * Criação do componente `.btn-action`: botões compactos (`28px`), neutros, de fundo branco com borda sutil cinza. O botão de exclusão só ganha leve realce avermelhado no hover (`#fef2f2`, texto `#dc2626`), mantendo a tabela limpa e profissional.
    * Criação do botão pílula interativo `.btn-status-toggle` para alternância rápida de pausa no balcão com bullet pontual verde (`● Ativo`) ou âmbar (`● Pausado`).
  * **Miniatura Elegante de Produto (`.product-thumb`):**
    * Substituição da caixa tosca com traço cinza solto (`&mdash;`) pelo componente `.product-thumb` (`38x38px` com cantos suaves e ícone SVG minimalista de alimento quando não houver foto).
  * **Eliminação de Tabela Esticada em Categorias:**
    * Reestruturação da tela de Categorias em layout de grid corporativo equilibrado de duas colunas (coluna principal com tabela data-grid, cabeçalho de card e contagem; coluna lateral com resumo de métricas da grade, sincronização com PDV e diretrizes de integridade de dados).
  * **Cache Busting:**
    * Atualização da versão dos assets nos layouts para `?v=5.0.7`.
* [x] Correção de compatibilidade de Prepared Statements PDO com MySQL nativo (`ATTR_EMULATE_PREPARES => false`) na busca com filtros de texto de produtos (`Produto.php`), colaboradores (`Colaborador.php`) e turmas (`Turma.php`), eliminando a exceção `SQLSTATE[HY093]: Invalid parameter number` por placeholders duplicados.
* [x] Implementação de **Auto-Dismiss de Alertas de Notificação em 5 Segundos**:
  * Fechamento automático suave com animação CSS (`opacity 0.35s`, `transform: translateY(-8px)`, `max-height 0`, `margin 0`) após 5 segundos em `assets/js/app.js` e `assets/css/custom.css`.
  * Recurso de **Pause on Hover**: o temporizador pausa automaticamente se o usuário passar o cursor do mouse por cima do alerta para leitura, reiniciando apenas quando o cursor sai do elemento.
  * Fechamento manual com botão `x` aprimorado para também disparar a transição suave com `fecharAlerta()`.
  * Atualização da versão de cache dos assets em todos os layouts (`admin.php`, `auth.php`, `portal.php`) para `?v=5.0.8`.
  * Criação da suíte de testes automatizados `tests/alert_auto_dismiss_test.php` com 8/8 asserções aprovadas com 100% de sucesso.
* [x] Bateria de testes automatizados `tests/fase7_test.php` executada com 68 asserções aprovadas com 100% de sucesso.
* [x] Suíte de testes de rotas web HTTP `tests/admin_web_test.php` aprovada com 10/10 requisições validadas (incluindo filtro via query string).
* [x] Suíte de testes de visibilidade da sidebar `tests/sidebar_visibility_test.php` aprovada com 7/7 asserções com 100% de sucesso.
* [x] Suíte de testes de máscaras automáticas `tests/mask_test.php` aprovada com 14/14 asserções com 100% de sucesso.
* [x] Suítes de regressão (Fases 2, 3, 4, 5, 6 e simulação web) reexecutadas e 100% aprovadas.
* [x] **Paginação Institucional Integrada em Turmas e Colaboradores:**
  * Métodos `Turma::listarTodas` e `Colaborador::listarTodos` com suporte a `$limit` e `$offset` e métodos de contagem `Turma::contarTodas` e `Colaborador::contarTodos`;
  * Integração com `Configuracao::getItensPorPagina()` (padrão 20) nos controladores `turmas()` e `colaboradores()`;
  * Componente `app/views/partials/pagination.php` integrado nas views `admin/turmas.php` e `admin/colaboradores.php`;
  * Suíte `tests/turmas_pagination_test.php` criada e homologada com 10/10 asserções aprovadas.


---

### [x] Fase 8 — Importação Escolar em Lote e Gestão Ativa de Restrições Alimentares
* [x] Implementação de `app/services/SpreadsheetService.php` com suporte a streaming CSV (`fgetcsv`), detecção automática de delimitadores (`;` ou `,`), sanitização de BOM UTF-8 e conversão transparente ISO-8859-1 para UTF-8.
* [x] Mapeamento flexível e tolerante de cabeçalhos escolares para identificar colunas mesmo com variações de nomenclatura (`nome_responsavel`, `cpf`, `whatsapp`, `matricula`, `nome_aluno`, `turma`, `turno`, `alergias`).
* [x] Mecanismo de auditoria e conferência prévia (*dry-run*) com validação linha a linha de CPFs (11 dígitos), matrículas duplicadas, colunas faltantes e cálculo prévio de novos cadastros e atualizações.
* [x] Processamento atômico de importação com transação PDO (`beginTransaction`, `commit`, `rollBack`):
  * Criação automática de turmas inexistentes com inferência de turno e ordem.
  * Localização ou criação de responsáveis por CPF único com **preservação absoluta de saldo financeiro (`saldo_atual`), senha criptografada (`senha_hash`) e termos aceitos**.
  * Associação de múltiplos alunos/irmãos ao mesmo responsável (carteira compartilhada).
  * Criação ou atualização de alunos por matrícula única escolar.
  * Sincronização automática de restrições alimentares declaradas na planilha vinculando aos IDs da tabela `restricoes_alimentares`.
* [x] Armazenamento seguro e segregado da cópia física das planilhas importadas em `storage/uploads/private/` com nome ofuscado por timestamp e hash para proteção de dados (LGPD).
* [x] Geração e download de planilha modelo em formato CSV com BOM UTF-8 (`gerarModeloCsv`) para preenchimento escolar padronizado.
* [x] Implementação do modelo `app/models/Aluno.php` com CRUD completo, busca textual, filtros por turma, turno e status, soft delete (`inativar`, `reativar`, `deleted_at`) e métodos de vinculação de alérgenos.
* [x] Extensão do modelo `app/models/Responsavel.php` com métodos `localizarOuCriarPorCpf()` (com salvaguardas de integridade) e `listarParaSelecao()`.
* [x] Extensão do modelo `app/models/Turma.php` com método `localizarOuCriar()`.
* [x] Extensão do modelo `app/models/RestricaoAlimentar.php` com `listarPorAluno()`, `sincronizarAluno()` e `alunoTemRestricao()`.
* [x] Implementação dos métodos do `app/controllers/AdminController.php`:
  * `importacaoEscolar()`: visualização da tela com formulário de upload e orientações.
  * `importacaoEscolarAnalisar()`: processamento do upload, análise prévia e geração do painel de conferência com contadores.
  * `importacaoEscolarProcessar()`: confirmação e processamento atômico definitivo da planilha analisada.
  * `importacaoEscolarCancelar()`: cancelamento do dry-run e expurgo do arquivo temporário.
  * `importacaoEscolarModelo()`: download do modelo CSV oficial.
  * `alunos()`: data-grid completo com filtros de busca, paginação, badges de turno e alérgenos vinculados.
  * `alunosSalvar()`, `alunosInativar()`, `alunosReativar()`: mutações seguras com token CSRF e auditoria.
* [x] Implementação dos métodos do `app/controllers/PortalController.php`:
  * `alergias()`: tela para responsáveis gerenciarem ativamente as restrições alimentares dos seus filhos.
  * `alergiasSalvar()`: validação estrita de titularidade LGPD (impede que uma família altere dados de dependentes de terceiros) e sincronização atômica de alérgenos.
* [x] Criação das views com Design System Enterprise Anti-Slop:
  * `app/views/admin/importacao_escolar.php`: upload com drag-and-drop, orientações claras, botão de download do modelo e tabela de auditoria prévia (*dry-run*).
  * `app/views/admin/alunos.php`: data-grid de alunos de alta densidade, filtros dinâmicos, modal corporativo de cadastro/edição e botões de ação neutros.
  * `app/views/portal/alergias.php`: seletor de dependente, cards de alérgenos oficiais com checkboxes e salvamento ágil.
* [x] Atualização da navegação lateral em `app/views/layouts/sidebar.php` com links "Importação Escolar" e "Alunos & Matrículas" na seção Administração.
* [x] Registro e blindagem RBAC de todas as novas rotas no Front Controller `index.php`.
* [x] Bateria de testes automatizados `tests/fase8_test.php` criada com 57 asserções cobrindo todos os fluxos com 100% de sucesso.
* [x] Baterias de testes de regressão (Fases 2, 3, 4, 5, 6, 7 e rotas web) executadas com 100% de aprovação.

---

### [x] Fase 9 — Carteira Digital Familiar e Gateway Pix Dinâmico
* [x] Implementação de `app/services/QrCodeService.php`:
  * Gerador nativo e autônomo de QR Code Modelo 2 em PHP puro sem dependências externas.
  * Codificação numérica/alfanumérica/byte com correção de erros Reed-Solomon oficial ISO/IEC 18004.
  * Renderização direta de vetor SVG nítido e geração de Data URI em Base64 para exibição imediata no navegador.
* [x] Implementação de `app/services/PixService.php`:
  * Montagem estrita do padrão BR Code / EMVCo oficial do Banco Central do Brasil com formatação TLV (Tag-Length-Value) e cálculo polinomial CRC16-CCITT (0x1021).
  * Geração de Pix dinâmico com identificador único (`txid`) institucional, nome da instituição recebedora, cidade, valor formatado e chave Pix institucional.
  * Gestão de validade temporária da cobrança (15 minutos / 900 segundos) e armazenamento em cache protegido `storage/cache/pix/`.
  * Consulta em tempo real de status da cobrança (`pendente`, `pago`, `expirado`) e mecanismo de simulação em ambiente local.
* [x] Implementação do Modelo `app/models/TransacaoCarteira.php`:
  * Gestão do livro-razão contábil e financeiro sobre a tabela `transacoes_carteira` (Migration 022).
  * Métodos transacionais atômicos com bloqueio pessimista (`FOR UPDATE`) para `creditar()` e `debitar()`.
  * Rastreabilidade financeira completa linha a linha: `saldo_anterior`, `valor` e `saldo_resultante`.
  * Trava rigorosa do modelo pré-pago contra saldo negativo com disparo de `DomainException`.
  * Consulta do resumo da carteira familiar (`saldo_atual`, `saldo_reservado`, `saldo_disponivel`, alertas de saldo baixo e alertas de bloqueio MED).
  * Extrato analítico paginado com filtros por tipo de lançamento (`recarga_pix`, `debito_pedido`, `estorno_pedido`, `ajuste_manual`, `estorno_med`), badges semânticos e formatação brasileira.
  * Validador de unicidade contábil (`buscarPorReferencia`) para garantia mandatória de idempotência contra repetições de webhook ou chamadas duplicadas.
* [x] Extensão de modelos e serviços auxiliares:
  * Em `app/models/Responsavel.php`: adição dos métodos `consultarSaldo()`, `isBloqueadoMed()`, `definirBloqueioMed()`.
  * Em `app/services/WhatsAppService.php`: adição dos métodos `sendRecargaConfirmada()` e `sendAlertaSaldoBaixo()`.
* [x] Implementação de `app/controllers/WebhookController.php`:
  * Endpoint público seguro `/webhook/pix` para recepção de notificações transacionais de gateways Pix (Mercado Pago, Efí/Gerencianet, etc.).
  * Validação de token de webhook configurado, normalização de payload e conferência de `txid` e valor.
  * Garantia estrita de idempotência: verificação de duplicidade antes do processamento, retornando HTTP 200 `ja_processado` sem duplicar saldo financeiro.
  * Liquidação contábil automática na carteira familiar e disparo imediato de WhatsApp confirmando a recarga.
* [x] Implementação de `app/controllers/CarteiraController.php`:
  * Controle de acesso familiar (RBAC para responsável titular).
  * Action `index()`: painel financeiro consolidado com bento grid de saldos, cards de alerta (saldo baixo / bloqueio MED) e extrato analítico paginado.
  * Action `gerarPix()`: validação de valor mínimo (R$ 1,00), geração do BR Code Pix e QR Code SVG, com retorno JSON estruturado.
  * Action `statusPix()`: endpoint de polling assíncrono para atualização do status em tempo real na interface da família.
  * Action `simularPagamento()`: facilitador seguro de desenvolvimento/testes para liquidação imediata da cobrança Pix via interface web.
* [x] Criação da view `app/views/portal/carteira.php` com Design System Enterprise Anti-Slop:
  * Bento grid de alta densidade com métricas de Saldo Total, Saldo Reservado em Pedidos e Saldo Disponível Livre (`.font-tabular`).
  * Botões de atalho rápido de valor (+R$ 20, +R$ 50, +R$ 100, +R$ 200) e input monetário com máscara de centavos.
  * Visualização de QR Code vetorial SVG nítido centralizado e área de código Copia e Cola com botão ágil de cópia via Clipboard API.
  * Cronômetro regressivo visual em tempo real (15:00) com aviso de expiração da cobrança.
  * Polling assíncrono transparente a cada 2,5 segundos que detecta a liquidação e recarrega a página automaticamente com mensagem de sucesso.
  * Botão corporativo discreto de simulação de pagamento ("Simular Pagamento no Ambiente de Teste") para validação local imediata.
  * Tabela data-grid de extrato analítico com status indicators pontuais, tipo de movimentação, valor formatado e paginação.
* [x] Registro e blindagem de rotas no `index.php`:
  * `/portal/carteira` (GET, RBAC: responsavel)
  * `/portal/recarga/gerar` (POST, CSRF, RBAC: responsavel)
  * `/portal/recarga/status/{txid}` (GET, RBAC: responsavel)
  * `/portal/recarga/simular/{txid}` (POST, CSRF, RBAC: responsavel)
  * `/webhook/pix` (POST, rota pública para gateways bancários)
* [x] Criação da suíte de testes automatizados `tests/fase9_test.php` com 62 asserções cobrindo QrCode, BR Code, CRC16, Transacoes, Trava de Saldo Negativo, Extrato, Idempotência, Webhook e WhatsApp com 100% de sucesso.
* [x] Execução e homologação de 100% das suítes de regressão (Fases 2, 3, 4, 5, 6, 7, 8, testes web, máscaras e visibilidade).

---

---

### [x] Fase 10 — Agendamento Semanal de Lanches e Regras Rígidas de Corte
* [x] Implementação do modelo `app/models/CalendarioEscolar.php`:
  * Determinação precisa de dias letivos, feriados e recessos escolares com consulta integrada à tabela `calendario_letivo`.
  * Regra RN-01: liberação pontual da semana seguinte toda sexta-feira às 18:00 (`isSemanaSeguinteLiberada`).
  * Utilitário `obterDiasSemanaLetiva` retornando a estrutura dos 5 dias úteis (Segunda a Sexta) com metadados de status (passado, hoje, letivo).
* [x] Implementação do modelo `app/models/ItemPedido.php`:
  * Gestão de itens detalhados associados a pedidos (`itens_pedido`) com relacionamentos a produtos e categorias.
* [x] Implementação do modelo central `app/models/Pedido.php`:
  * Trava rígida de corte operacional (RN-02): encerramento pontual às 08:00 para o turno matutino e às 13:30 para o vespertino (`podeSerAlteradoOuCancelado` e `obterStatusCorte`).
  * Criação atômica de pedido (`criarPedido`) com validação de titularidade familiar, verificação de saldo livre na carteira (modelo pré-pago), reserva física de estoque para industrializados (`estoque_reservado`), débito financeiro imediato via `TransacaoCarteira` e geração de tickets térmicos individuais para bobina de 80mm.
  * Cancelamento autônomo (`cancelarPedido`, RN-03) com estorno integral de 100% creditado na carteira familiar no mesmo instante, liberação da reserva de estoque e cancelamento dos tickets.
  * Edição autônoma com compensação contábil imediata (`editarPedido`, RN-04): recálculo financeiro com cobrança adicional se mais caro ou estorno automático se mais barato, e reajuste preciso das reservas de estoque.
  * Detecção preventiva de alérgenos (RN-16 e RN-17): verificação cruzada entre as restrições alimentares do aluno e a composição dos lanches, gravando tag indelével de alerta nos tickets térmicos (`tag_alergenos`).
  * Consultas estruturadas `listarPorResponsavel` e `buscarDetalhado` com enriquecimento de metadados operacionais e status de corte.
* [x] Harmonização contábil em `app/models/TransacaoCarteira.php`:
  * Ajuste na consolidação de saldos da carteira familiar (`obterResumoCarteira`), separando claramente `saldo_atual` (já deduzido do pedido), `saldo_reservado` (total de pedidos futuros agendados) e `saldo_total` em custódia institucional, evitando dupla dedução contábil.
* [x] Expansão do catálogo de produtos em `app/models/Produto.php`:
  * Adição do método `listarParaCardapio(string $turno)` com apuração do estoque disponível (`estoque_atual - estoque_reservado`), flag `esgotado` (RN-13) e agregação de IDs de alérgenos por produto.
  * Adição de alias idiomático `sincronizarAlergenos()`.
* [x] Implementação dos fluxos operacionais no controller `app/controllers/PortalController.php`:
  * `agendamento()`: exibição do catálogo semanal de lanches por dia letivo e dependente familiar.
  * `agendamentoSalvar()`: processamento seguro de agendamento com validação CSRF, checagem de corte e feedback visual.
  * `pedidos()`: listagem de pedidos com filtros por aluno e período, exibição de status tipográfico e ações contextuais.
  * `pedidoCancelar()`: ação de cancelamento autônomo com validação prévia de horário de corte.
  * `pedidoEditar()` e `pedidoEditarSalvar()`: interface e ação de alteração de pedido com demonstrativo de compensação financeira.
* [x] Criação das views com Design System Enterprise Anti-Slop (`docs/DESIGN.md`):
  * `app/views/portal/agendamento.php`: abas de dias da semana com indicador de status e dia atual, seletor de dependentes, grade de produtos com itens esgotados desabilitados (opacidade atenuada 0.5), modal corporativo de advertência em amarelo/âmbar (`#eab308`) para alérgenos com confirmação consciente obrigatória, e resumo lateral flutuante com totalizador tabular.
  * `app/views/portal/meus_pedidos.php`: data-grid de pedidos com indicadores de corte e status tipográficos, botões de ação neutros discretos (`.btn-enterprise`), modal de cancelamento com informação explícita de estorno integral imediato de 100%.
  * `app/views/portal/pedido_editar.php`: tela de edição com comparativo de valores em tempo real, calculando a diferença a pagar ou estorno a receber na carteira familiar.
* [x] Registro e blindagem de 6 novas rotas no `index.php` com RBAC `ROLE_RESPONSAVEL`:
  * `GET /portal/agendamento`
  * `POST /portal/agendamento/salvar`
  * `GET /portal/pedidos`
  * `POST /portal/pedidos/{id}/cancelar`
  * `GET /portal/pedidos/{id}/editar`
  * `POST /portal/pedidos/{id}/editar`
* [x] Criação da suíte automatizada `tests/fase10_test.php` com 71 asserções cobrindo todos os fluxos e regras da Fase 10 (100% de sucesso).
* [x] Execução e homologação de 100% dos testes de regressão das fases anteriores (Fases 7, 8 e 9).

---

### [x] Fase 11 — Operação de Encomendas e Gestão de Fornecedores
* [x] Painel de corte do turno com consolidação matemática de salgados (`app/views/operacional/corte.php` e `PedidosController::corte`).
* [x] Formatação de mensagem estruturada e botão "Copiar para WhatsApp" com feedback tátil de área de transferência.
* [x] Geração de relatório formal A4 timbrado em PDF 1.4 binário puro (`app/services/PdfService.php`) e espelho HTML para impressão `@media print` via navegador.
* [x] Travas rígidas de horário de corte por turno (08:00 matutino e 13:30 vespertino) com status em tempo real e minutos restantes.
* [x] Métodos de negócio especializados em `app/models/Pedido.php`:
  * `consolidarCorte(string $dataConsumo, string $turnoConsumo, ...)`: agregação de pedidos, alunos beneficiados e demanda por produto.
  * `obterTextoWhatsAppCorte(...)`: texto institucional formatado com itens e total de peças.
  * `verificarStatusRecebimento(...)`: checagem de remessa conferida via movimentações de estoque.
  * `confirmarRecebimentoFornecedor(...)`: conferência física atômica, prevenção contra recebimento duplicado, gravação de `entrada_fornecedor`, liquidação de demanda com `saida_encomenda`, atualização automática de excedentes/sobras no estoque para venda avulsa no PDV e trilha em `logs_auditoria`.
* [x] Tela de conferência física de recebimento (`app/views/operacional/recebimento.php` e `PedidosController::recebimento` / `recebimentoConfirmar`):
  * Cálculo dinâmico de diferenças e sobras via Vanilla JS;
  * Botão de atalho para preenchimento rápido com os quantitativos encomendados;
  * Bloqueio contra reenvio de remessas já conferidas;
  * Exibição analítica de quantidades entregues reais, sobras e faltas por item quando a remessa já foi conferida (`statusRecebimento['itens_entregues']`);
  * Atualização reativa instantânea dos painéis operacionais (`corte.php` e `recebimento.php`) via `onchange="this.form.submit()"` nos seletores de data e turno.
* [x] Registro e blindagem de rotas operacionais no `index.php` com RBAC `ROLE_OPERADOR` e `ROLE_ADMINISTRADOR`:
  * `GET /operacional/corte`
  * `GET /operacional/corte/pdf`
  * `GET /operacional/corte/imprimir`
  * `GET /operacional/recebimento`
  * `POST /operacional/recebimento/confirmar`
* [x] Criação da suíte automatizada `tests/fase11_test.php` com 72 asserções cobrindo todos os fluxos e regras da Fase 11 (100% de sucesso).
* [x] Execução e homologação de 100% dos testes de regressão das fases anteriores (Fases 7, 8, 9, 10 e 11).

---

### [x] Fase 12 — Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
* [x] Implementação do modelo `app/models/Ticket.php` para consultas detalhadas, busca rápida por matrícula/aluno/código, agrupamento de turmas e marcação de impressão.
* [x] Implementação do modelo oficial `app/models/Auditoria.php` para persistência compulsória de eventos operacionais e reemissões na tabela `logs_auditoria`.
* [x] Suporte técnico de impressão térmica contínua de 80mm (226.77 pt) implementado em `app/services/PdfService.php` (`gerarTicketsLotePdf`, `gerarTicketIndividualPdf`, `gerarEspelhoTicketsLoteHtml`, `gerarEspelhoTicketIndividualHtml`).
* [x] Ordenação sequencial estrita por `Turno > turmas.ordem_impressao ASC > turmas.nome_turma ASC > alunos.nome ASC > tickets.id ASC` para entrega organizada aos professores antes do recreio.
* [x] Destaque visual indelével e moldura obrigatória de alérgenos (`[ATENÇÃO: CONTÉM ...]`) nos tickets com restrições alimentares conflitantes (RN-16 e RN-17).
* [x] Emissão de 2ª via com trava de justificativa obrigatória (mínimo 5 caracteres), incremento do `numero_via`, marcação indelével `[2ª VIA]` e registro compulsório em `logs_auditoria`.
* [x] Criação do `app/controllers/TicketsController.php` e registro das 8 rotas operacionais protegidas por RBAC (`/operacional/tickets*`) em `index.php`.
* [x] Telas operacionais construídas seguindo o Design System corporativo Anti-Slop (`app/views/operacional/tickets.php`, `tickets_lote.php` e `ticket_individual.php`).
* [x] Correção de chamada de método flash na sessão: adicionado alias `Session::flash()` em `app/core/Session.php` e padronizado `Session::setFlash()` no `TicketsController` e `CarteiraController`.
* [x] Aprimoramento da redação do modal de 2ª via tornando o termo "indelével" compreensível e autoexplicativo ("registro permanente e obrigatório, que não pode ser apagado").
* [x] Criação e execução com 100% de aprovação da suíte de testes `tests/fase12_test.php` expandida para 65 asserções (65/65 aprovadas).
* [x] Execução e homologação de 100% dos testes de regressão das fases anteriores (Fases 7, 8, 9, 10, 11 e 12).

---

### [x] Fase 13 — Ponto de Venda (PDV) e Operação de Balcão
* [x] Implementação de `app/models/SessaoCaixa.php` gerenciando o ciclo de vida do caixa escolar por turno (abertura com Fundo de Troco, validação de caixa aberto, cálculo de turno e incremento atômico de totais).
* [x] Implementação de `app/models/ItemVendaBalcao.php` e `app/models/VendaBalcao.php` com transações financeiras atômicas, gravação de itens, cálculo de troco sugerido e baixa física imediata em `estoque_movimentacoes` (`saida_venda_avulsa`).
* [x] Trava operacional de caixa fechado: vendas e movimentações em espécie/Pix são bloqueadas se não houver sessão de caixa aberta para o turno correspondente.
* [x] Suporte à identificação de alunos no balcão com alerta visual imediato e bloqueio de venda para produtos com alérgenos cadastrados do aluno.
* [x] Suporte a Consumo de Colaboradores (Desconto em Folha) com busca dinâmica por matrícula ou nome, confirmação visual de foto institucional e cargo funcional, e totalizador segregado.
* [x] Integração com Pix Dinâmico no balcão (QR Code em modal interativo, cópia de Pix Copia e Cola e verificação de status).
* [x] Estorno de Balcão (RN-18) em `app/models/Ticket.php` e `PdvController::estornoBalcao()`: cancelamento de ticket por avaria, falta de produto ou recusa, com devolução imediata de 100% do saldo na carteira digital da família (`transacoes_carteira` do tipo `estorno_balcao`) e trilha compulsória em `logs_auditoria`.
* [x] Aproveitamento de Sobras do Recreio em `Ticket::transferirSobrasNaoRetiradasParaBalcao()`: listagem de tickets não retirados do turno e transferência física das unidades não consumidas para o estoque do PDV (`aproveitamento_sobra`), mantendo a cobrança do aluno ausente.
* [x] Emissão de Comprovante de Venda do Balcão contínuo de 80mm em HTML e PDF nativo 1.4 (`PdfService::gerarComprovanteVendaBalcaoPdf()` e `PdfService::gerarEspelhoComprovanteVendaBalcaoHtml()`).
* [x] Interface operacional utilitária de alta densidade criada em `app/views/operacional/pdv.php` com catálogo visual por categorias, pesquisa em tempo real, teclado de atalhos rápidos (`F2`, `F4`, `F8`, `F9`, `Esc`), cupom de venda lateral com tipografia tabular e modais integrados.
* [x] Registro e blindagem RBAC de 12 rotas operacionais sob `/operacional/pdv*` no Front Controller `index.php`.
* [x] Suíte de testes da Fase 13 em `tests/fase13_test.php` aprovada com 100% de sucesso (52/52 asserções).
* [x] Suíte de testes de rotas web, controllers e RBAC em `tests/pdv_web_test.php` aprovada com 100% de sucesso (30/30 asserções).
* [x] Implementação de `app/controllers/CaixaController.php`, view `app/views/operacional/caixa.php` e rotas `/operacional/caixa*`, resolvendo o erro 404 ao clicar em "Caixa do Turno" no menu lateral, com suporte completo a abertura com fundo de troco, apuração financeira da sessão, fechamento com contagem física de gaveta, cálculo dinâmico de quebra/sobra, histórico e emissão de espelho A4 (HTML/PDF). Suíte `tests/caixa_test.php` com 38/38 asserções aprovadas (100%).
* [x] **Refatoração e Blindagem Visual do PDV (`app/views/operacional/pdv.php` e `assets/css/custom.css`):**
  * Injeção de folha de estilo autônoma e resiliente em `app/views/operacional/pdv.php`, imune a bloqueios de cache prévio do cliente.
  * Atualização e aperfeiçoamento dos estilos em `assets/css/custom.css`: layout de 2 colunas mantido a partir de 960px (ideal para laptops de 1366x768 e terminais POS), cards com miniatura e elevação sutil no hover, botões de categoria com reset de aparência, cupom de venda fixo e estilização completa de todos os modais customizados (`.modal-backdrop-custom`, `.modal-card-custom`).
  * Atualização da versão de cache dos assets para `?v=5.2.0` nos layouts mestre (`admin.php` e `header.php`).
  * Adição de atalhos rápidos `F3` (foco na busca de produtos) e `F10` (conclusão de venda).
* [x] **Harmonização e Paridade Estrita do Espelho de Fechamento de Caixa (`PdfService.php`):**
  * Equalização completa entre a renderização HTML do navegador (`/operacional/caixa/espelho/{id}`) e o arquivo PDF binário baixado (`/operacional/caixa/espelho/{id}/pdf`).
* [x] **Inclusão do Logotipo Oficial em Todos os PDFs e Documentos de Impressão (`PdfService.php`):**
  * Implementação de suporte nativo a imagens XObject JPEG com compressão `/DCTDecode` no gerador autônomo de PDF 1.4 (`montarPdfEstruturado()`), com recálculo automático da tabela `xref` e `trailer`.
  * Criação dos métodos `PdfService::getLogoPath()` e `PdfService::getLogoDataUri()` (codificação base64 Data-URI para suporte total offline e em qualquer impressora/navegador).
  * Inclusão do logotipo oficial circular (`assets/img/logo-colegio.jpg`) em todos os PDFs binários:
    - Relatório Consolidado de Corte Operacional A4 (`gerarRelatorioCortePdf`) com brasão de 42x42 pt no cabeçalho institucional;
    - Prestação de Contas e Fechamento de Caixa A4 (`gerarEspelhoFechamentoCaixaPdf`) com brasão de 42x42 pt ao lado do título e turno;
    - Comprovante de Venda do Balcão PDV 80mm (`gerarComprovanteVendaBalcaoPdf`) com logotipo de 34x34 pt centralizado no topo;
    - Tickets de Recreio em Lote e Individual 80mm (`gerarTicketsLotePdf` e `gerarTicketIndividualPdf`) com brasão de 18x18 pt no cabeçalho de cada ticket individual.
  * Inclusão do logotipo oficial em todos os espelhos HTML de impressão: Relatório de Corte A4, Fechamento de Caixa A4, Comprovante de Balcão 80mm e Bobina de Tickets 80mm.
  * Suíte de testes dedicada `tests/logo_pdf_test.php` criada e aprovada com 100% de sucesso (29/29 asserções).
* [x] **Correção de Resolução de Turnos no PDV e Sincronização de Fuso Horário:**
  * Sincronização de timezone oficial (`America/Sao_Paulo`, UTC-3) entre PHP e MySQL via `\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES ..., time_zone = '-03:00'"`.
  * Eliminação de fallback permissivo no `PdvController.php`: o PDV respeita estritamente o turno inferido pelo relógio (`< 12:00` matutino, `>= 12:00` vespertino) ou selecionado pelo usuário via `?turno=...`.
  * Stat-strip e topbar do PDV devidamente alinhados, exibindo `● Caixa Fechado (Turno Vespertino)` e botão de abertura quando o caixa da tarde ainda não foi aberto.
  * Alerta visual caso o caixa do outro turno (ex: matutino) tenha ficado aberto sem fechamento, com atalho direto para prestação de contas.
  * Seletor rápido de turno (Manhã / Tarde) e atalho `F7` para ir ao Caixa.
  * Limpeza compulsória de sessões de teste na suíte automatizada `tests/fase13_test.php`.
* [x] Homologação das suítes de regressão (Fases 11 e 12) com 100% de aprovação.

---

### [x] Fase 14 — Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas
* [x] Serviço de parsing e leitura segura de NF-e em `app/services/XmlParserService.php` com proteção contra ataques XXE (`LIBXML_NONET`, bloqueio de DTD e entidades externas), extração de chave de acesso de 44 dígitos, número, série, dados completos do emitente/fornecedor, valor total e itens faturados (`cProd`, `xProd`, `uCom`, `qCom`, `vUnCom`, `vProd`).
* [x] Normalização inteligente e sugestão de correspondência automática entre produtos da nota fiscal e produtos cadastrados no sistema por código interno ou similaridade semântica de descrição.
* [x] Modelos de dados fiscais: `app/models/NotaFiscal.php` (persistência em `notas_fiscais_xml`, verificação contra duplicidade de chave de 44 dígitos, filtros analíticos e métricas fiscais) e `app/models/ItemNotaFiscal.php` (persistência em `itens_nota_fiscal_xml`, memória do histórico de fator multiplicador por fornecedor via `buscarUltimoFatorConversao`).
* [x] Modelo de dados e operações atômicas de estoque em `app/models/Estoque.php`:
  * `darEntradaXml`: entrada transacional por lote com conversão matemática de embalagens/fardos em unidades físicas individuais (`quantidade_convertida = quantidade_comercial * fator_multiplicador`), registro em `estoque_movimentacoes` do tipo `entrada_xml` com vínculo ao ID da NF-e, armazenamento físico blindado do arquivo em `storage/uploads/private/` e memória de conversão para próximas compras.
  * `registrarPerda`: baixa imediata do estoque com movimentação `saida_perda`, validação compulsória de saldo físico disponível, validação estrita dos 4 motivos oficiais padronizados pelo FSD (`avaria`, `vencimento`, `sobra_producao`, `ajuste_contagem`) e registro compulsório de trilha de negócio em `logs_auditoria` com operador, justificativa e IP de origem (RN-15).
  * `listarSaldos`: painel analítico com estoque físico, estoque reservado para pedidos online, estoque disponível para venda imediata, status operacional, flag de alerta de reposição (`estoque_atual <= estoque_minimo`) e métricas consolidadas de inventário e valor monetário em venda.
  * `listarMovimentacoes`: extrato cronológico com suporte a múltiplos filtros (produto, tipo de movimentação, motivo de perda, período de datas e busca textual).
  * `listarPerdas`: listagem analítica e histórico recente de descartes e avarias.
  * `atualizarEstoqueMinimo`: ajuste individual de ponto de reposição por produto.
* [x] Controlador completo em `app/controllers/EstoqueController.php` com 12 actions operacionais e fiscais:
  * `index`: painel de saldos com stat-strip, filtros, alertas e alternador rápido de pausa do produto ("Ativo/Pausado");
  * `movimentacoes`: extrato cronológico auditável de entradas, saídas e perdas;
  * `perdas`: tela de lançamento de baixa de mercadoria com validação em tempo real de saldo físico;
  * `perdasSalvar`: processamento do registro de perda com token CSRF, transação e auditoria;
  * `atualizarEstoqueMinimo`: ajuste via AJAX/POST do ponto de reposição;
  * `importarXml`: formulário para upload de arquivo `.xml` de NF-e;
  * `importarXmlAnalisar`: processamento inicial, extração segura via `XmlParserService`, validação de duplicidade e armazenamento temporário em sessão;
  * `importarXmlProcessar`: conferência final de fatores de conversão, vínculo de produtos, gravação atômica da nota e movimentação de estoque;
  * `importarXmlCancelar`: descarte de importação em andamento e limpeza de arquivos temporários;
  * `notasFiscais`: histórico fiscal completo com stat-strip acumulado e filtros por fornecedor, número e período;
  * `notaFiscalDetalhes`: detalhamento visual dos itens faturados, unidades abastecidas e fatores multiplicadores aplicados;
  * `downloadXml`: download seguro do arquivo XML original a partir de `storage/uploads/private/` com headers de anexo (`Content-Disposition: attachment`).
* [x] Criação de 7 visões corporativas de alta densidade no padrão Anti-Slop (`docs/DESIGN.md`):
  * `app/views/estoque/index.php`: Painel Geral de Saldos Físicos & Inventário;
  * `app/views/estoque/movimentacoes.php`: Extrato Cronológico de Movimentações;
  * `app/views/estoque/perdas.php`: Registro Operacional de Perdas & Descarte;
  * `app/views/estoque/importar_xml.php`: Upload da Nota Fiscal Eletrônica (NF-e);
  * `app/views/estoque/conferir_xml.php`: Conferência de Itens e Fatores de Conversão com recálculo reativo em JS;
  * `app/views/estoque/notas_fiscais.php`: Histórico de Documentos Fiscais Armazenados;
  * `app/views/estoque/nota_fiscal_detalhes.php`: Detalhamento e Auditoria dos Itens da NF-e.
* [x] Roteamento e blindagem RBAC de 12 rotas sob `/estoque*` no Front Controller `index.php`:
  * Perfis `operador` e `administrador` autorizados para consulta de saldos, extrato de movimentações e registro de perdas;
  * Apenas o perfil `administrador` autorizado para importação de XML de NF-e, configuração de estoque mínimo e gestão de notas fiscais;
  * Perfil `responsavel` terminantemente bloqueado com registro em `logs_seguranca`.
* [x] Suíte de testes da Fase 14 em `tests/fase14_test.php` aprovada com 100% de sucesso (45/45 asserções).
* [x] Suíte de testes de rotas web, controllers e RBAC em `tests/estoque_web_test.php` aprovada com 100% de sucesso (53/53 asserções).
* [x] Validação e aprovação de todas as suítes de regressão anteriores:
  * `tests/fase13_test.php` (PDV e Operação de Balcão) — 52/52 asserções (100%);
  * `tests/pdv_modal_test.php` (Modais do PDV e Prevenção do erro querySelector) — 18/18 asserções (100%);
  * `tests/fase12_test.php` (Tickets Térmicos 80mm) — 65/65 asserções (100%);
  * `tests/fase11_test.php` (Corte Operacional e Encomendas) — 72/72 asserções (100%);
  * `tests/caixa_test.php` (Gestão de Caixa de Turno) — 38/38 asserções (100%);
  * `tests/fase10_test.php` (Agendamento Semanal e Travas de Corte) — 71/71 asserções (100%).

---

### [x] Fase 15 — Controle Financeiro e Fechamento de Caixas por Turno
* [x] **Ciclo de Vida das Sessões de Caixa por Turno (FSD Áreas 4, 6 e Módulo 7):**
  * Abertura de sessão com Fundo de Troco inicial em espécie na gaveta;
  * Travas de turno: Matutino (expediente encerra às 11:00) e Vespertino (expediente encerra às 16:00) com detecção automática de atraso de fechamento e alerta visual;
  * Prevenção de duplicidade: bloqueio rigoroso contra abertura simultânea de mais de uma sessão no mesmo turno;
  * Alerta preventivo se o caixa do outro turno permaneceu aberto sem encerramento.
* [x] **Bloqueio Estrito de Operações Financeiras em Espécie:**
  * Bloqueio contra qualquer venda em dinheiro ou recarga presencial caso a sessão do turno não tenha sido formalmente aberta.
* [x] **Recarga Presencial em Dinheiro no Balcão da Cantina (Tela 5.4 do FSD):**
  * Modal corporativo de alta densidade no padrão Anti-Slop integrado à tela de Caixa (`app/views/operacional/caixa.php`);
  * Mecanismo de busca rápida com debounce de 250ms por nome ou CPF do responsável, ou matrícula/nome do aluno dependente;
  * Card com dados consolidados da família: titular, CPF formatado, alunos dependentes matriculados e Saldo Atual da Carteira;
  * Entrada monetária com máscara em reais e botões de atalho rápido (+R$ 20, +R$ 50, +R$ 100, +R$ 200);
  * Crédito financeiro atômico na carteira familiar via `TransacaoCarteira::creditar` (`tipo_transacao = 'recarga_dinheiro'`);
  * Incremento automático e em tempo real do acumulador `total_dinheiro_recargas` na sessão de caixa aberta;
  * Trilha de auditoria compulsória em `logs_auditoria` com ação `recarga_dinheiro_balcao`, ID do operador, justificativa e IP de origem.
* [x] **Emissão de Comprovante Térmico de Recarga em Bobina de 80mm:**
  * Geração de espelho HTML para impressão térmica rápida (`/operacional/caixa/comprovante-recarga/{id}`) com folha de 80mm, `@media print` e logotipo circular oficial em Data-URI;
  * Geração de PDF 1.4 binário puro (`/operacional/caixa/comprovante-recarga/{id}/pdf`) dimensionado estritamente na largura de bobina térmica (226.77 pt) com logotipo oficial circular vetorial desenhado via clipping path (`desenharLogoCircular`), protocolo, titular, dependentes, valor recebido e novo saldo familiar.
* [x] **Conferência Física de Gaveta e Fechamento Assistido:**
  * Tela dedicada de fechamento (`app/views/operacional/fechamento_caixa.php`) e formulário inline na tela de caixa;
  * Apuração do total de dinheiro em espécie esperado na gaveta: `Fundo Inicial + Vendas Dinheiro Balcão + Recargas em Dinheiro`;
  * Confronto dinâmico com o montante contado fisicamente, com cálculo reativo em tempo real de Quebra / Falta ou Sobra de caixa;
  * Campo obrigatório para justificativa operacional de divergências para a gerência;
  * Encerramento formal da sessão com timestamp de fechamento e gravação de `diferenca_quebra_sobra`.
* [x] **Emissão de Espelho Oficial A4 Timbrado de Fechamento de Caixa:**
  * Espelho A4 em HTML (`/operacional/caixa/espelho/{id}`) formatado para impressão;
  * Documento PDF 1.4 binário puro timbrado em A4 (`/operacional/caixa/espelho/{id}/pdf`) com logotipo oficial circular vetorial, discriminação contábil detalhada e linhas formais para assinatura física do operador de caixa e do gerente/coordenação.
* [x] **Extrato Analítico Detalhado da Sessão de Caixa:**
  * Tela de extrato analítico da sessão (`app/views/operacional/caixa_detalhes.php` e `/operacional/caixa/detalhes/{id}`);
  * Discriminação linha a linha de todas as vendas avulsas no balcão com itens e horários;
  * Discriminação de todas as recargas presenciais em dinheiro com dados dos responsáveis e operadores;
  * Totalizadores de entradas digitais em Pix balcão creditadas na conta bancária.
* [x] **Histórico de Caixas com Filtros Multi-critério e Paginação Dinâmica:**
  * Formulário de filtros por turno (`matutino`, `vespertino`), data de início e data de término;
  * Paginação de banco de dados (`LIMIT`/`OFFSET`) respeitando estritamente o valor configurado em `configuracoes_globais.itens_por_pagina` através de `Configuracao::getItensPorPagina()`;
  * Data-grid corporativo com ações rápidas para visualização de extrato analítico, espelho de impressão e download em PDF.
* [x] **Paginação Dinâmica Universal e Calibragem de Alta Densidade (1366x768):**
  * Criação do componente visual unificado `app/views/partials/pagination.php` com contador analítico (*"Mostrando X a Y de Z registros"*), botões de navegação (*"Anterior"*, números com raio dinâmico, *"Próxima"*), botões corporativos neutros sem cores berrantes (`.btn-enterprise.btn-secondary`), tipografia tabular e suporte transparente a filtros `$_GET`;
  * Implementação de paginação real com contadores SQL em Estoque (`/estoque`, `/estoque/movimentacoes`, `/estoque/notas-fiscais`), Caixa (`/operacional/caixa`), Produtos (`/admin/produtos`) e Alunos (`/admin/alunos`);
  * Otimização da tabela do histórico de caixa para caber perfeitamente em telas de laptop e cantina (1366x768) com a barra lateral aberta (largura reduzida de >1250px para ~920px através de cabeçalhos concisos, datas compactadas, `.btn-group.btn-group-sm` de ações e classe `.table-dense` com padding calibrado);
  * Otimização da tela de PDV (`app/views/operacional/pdv.php`) com `flex-wrap` na `stat-strip` e ajuste no grid para prevenir qualquer corte lateral em resoluções de 1366px.
* [x] **Roteamento e Permissões RBAC:**
  * 12 rotas protegidas sob `/operacional/caixa*` no Front Controller `index.php` autorizadas exclusivamente para `ROLE_OPERADOR` e `ROLE_ADMINISTRADOR`.
* [x] **Homologação Automatizada:**
  * Suíte de testes `tests/fase15_test.php` executada e homologada com 100% de sucesso (67/67 asserções aprovadas);
  * Suítes de regressão (Fases 13 e 14) reexecutadas e 100% aprovadas.

---

### [x] Fase 16 — Tratamento de Contestações Bancárias e Mecanismo Pix MED
* [x] **Tratamento de Webhooks de Devolução MED (Bacen / Efí / REST):**
  * `PixService::isPayloadDevolucao` e `PixService::parseWebhookDevolucaoPayload` com suporte inteligente ao padrão BACEN com array `devolucoes` em `pix[0]`, chave aninhada `devolucao` e payloads em formato plano REST;
  * Extração precisa de `txid`, `endToEndId`, `rtrId` (ID da devolução), valor, status, data/hora e justificativa bancária.
* [x] **Débito Atômico e Permissão Excepcional de Saldo Negativo na Carteira:**
  * Implementação de `TransacaoCarteira::processarEstornoMed` com bloqueio pessimista `FOR UPDATE` na carteira do responsável;
  * Execução do débito com tipo contábil `debito_contestacao_med`, permitindo saldo negativo excepcional conforme determinação regulamentar do BACEN (Resolução BCB nº 103);
  * Idempotência estrita baseada no identificador único de devolução (`referencia_id = rtrId`), impedindo qualquer débito duplicado em caso de reenvio de webhooks.
* [x] **Bloqueio Cautelar Compulsório de Agendamentos e Compras (`bloqueado_med = 1`):**
  * Se o saldo resultante da família for menor que zero (`saldoResultante < 0`), a conta é compulsoriamente marcada como `bloqueado_med = 1`;
  * Bloqueio preventivo automático no modelo `TransacaoCarteira::debitar` e nos métodos `agendamento()` e `agendamentoSalvar()` de `PortalController`, impedindo novos agendamentos para todos os dependentes enquanto perdurar o débito;
  * Mensagem clara de orientação e banner informativo de advertência no Portal da Família.
* [x] **Disparo de Mensagens Transacionais Emergenciais via WhatsApp:**
  * `WhatsAppService::sendNotificacaoMed` com notificação urgente da contestação bancária, saldo devedor atual e orientação para regularização junto à administração;
  * `WhatsAppService::sendNotificacaoDesbloqueioMed` confirmando a quitação do débito e liberação imediata da conta familiar;
  * Trilha de contingência e registro seguro em `storage/logs/security.log`.
* [x] **Painel Administrativo Corporativo de Contestações MED (`/admin/contestacoes`):**
  * Interface de alta densidade no padrão Anti-Slop (`app/views/admin/contestacoes.php`) exclusiva para administradores (`ROLE_ADMINISTRADOR`);
  * Stat-strip com 4 métricas em tempo real: Famílias Bloqueadas MED, Saldo Devedor Total, Estornos MED Processados e Contas Regularizadas;
  * Barra de filtros combinados por busca (nome, e-mail, WhatsApp, alunos dependentes) e status (todos, bloqueados, saldo negativo);
  * Tabela data-grid de inadimplentes com titular, relação de dependentes vinculados com turma, saldo devedor destacado em tipografia tabular (`.font-tabular`), status MED e ações administrativas;
  * Tabela de histórico analítico com trilha de auditoria de estornos bancários e baixas de quitação.
* [x] **Lançamento Manual de Contestações e Quitação de Débitos com Desbloqueio:**
  * Modal corporativo `#modalContestacaoManual` para lançamento de estornos com seleção de responsável, valor, protocolo bancário e justificativa com auditoria;
  * Modal corporativo `#modalQuitacaoMed` para baixa de débito com valor pré-preenchido, forma de pagamento, justificativa e opção de desbloqueio automático se o saldo resultante for >= 0;
  * Ação de desbloqueio manual direto com confirmação e justificativa administrativa (`AdminController::desbloquearContaMed`);
  * Registro de auditoria compulsório em `logs_auditoria` e `logs_seguranca`.
* [x] **Emissão de Relatório Oficial A4 em PDF Timbrado (`/admin/contestacoes/pdf`):**
  * `PdfService::gerarRelatorioNegativadosMedPdf` gerando documento PDF 1.4 binário puro timbrado em papel A4 (595.28 x 841.89 pt);
  * Cabeçalho oficial institucional com brasão circular vetorial oficial (`desenharLogoCircular`), identificação da unidade escolar e data de emissão;
  * Resumo executivo com métricas globais e tabela data-grid de famílias inadimplentes com CPF, titular, WhatsApp, dependentes vinculados e montante devedor alinhado à direita.
* [x] **Navegação e Roteamento:**
  * Registro de item "Contestações MED" na barra lateral de navegação (`app/views/layouts/sidebar.php`);
  * Rotas `/webhook/pix/devolucao` e `/webhook/pix/med` registradas no Front Controller `index.php`;
  * Rotas administrativas `/admin/contestacoes*` protegidas estritamente por RBAC para administradores.
* [x] **Homologação Automatizada e Correção de Layout/Modais:**
  * Suíte de testes dedicada `tests/fase16_test.php` executada e aprovada com 100% de sucesso (60/60 asserções);
  * Suítes de regressão (Fases 9, 10, 13, 14 e 15) reexecutadas e 100% aprovadas;
  * Correção de acesso defensivo a colunas (`saldo_atual` e `whatsapp`) no modal de lançamento manual em `app/views/admin/contestacoes.php`, eliminando interrupção de renderização por erro 500;
  * Integração nativa de data-attributes Bootstrap 5 (`data-bs-toggle="modal"`) e funções globais para os modais de contestação e quitação;
  * Estabilização da barra lateral de navegação: remoção de auto-colapso forçado em notebooks/1366px, sidebar expandida e visível por padrão (240px) e botão `.btn-sidebar-toggle` da topbar permanentemente visível;
  * Resolução do corte de fundo da barra lateral ao rolar a página verticalmente: aplicação de `position: fixed; top: 0; bottom: 0; left: 0; height: 100vh;` no desktop, transição suave sincronizada com `.app-main-area` (`margin-left: 240px`), scrollbar fina corporativa no menu e substituição de `overflow-x: hidden` por `overflow-x: clip` no wrapper. Asset version atualizado para `5.7.2`;
  * Implementação do modal corporativo `#modalDesbloqueioMed` com validação de justificativa formal obrigatória, exibição contextual de saldo devedor, disparo opcional de notificação via WhatsApp e correção dos placeholders de auditoria em `logs_auditoria`. Suíte `tests/desbloqueio_med_modal_test.php` criada e homologada (11/11);
  * Implementação de autocomplete assíncrono com sugestões dinâmicas de responsáveis e dependentes (`/admin/contestacoes/sugestoes`), suporte a navegação por teclado (setas/Enter/Escape), botão rápido de limpeza, filtragem instantânea client-side nas linhas da tabela durante a digitação e correção rigorosa dos filtros por status e busca textual no backend (`Responsavel::listarNegativadosMed`). Suíte `tests/contestacoes_busca_sugestoes_test.php` criada e aprovada (27/27);
  * Resolução do truncamento do dropdown de sugestões por overflow da card de busca (`.card-overflow-visible` com `overflow: visible !important;` e `top: calc(100% + 4px)`); ajuste do layout responsivo da barra lateral para o breakpoint universal de 768px (notebooks e desktops), garantindo que ao expandir a barra lateral a parte principal se contraia (`margin-left: 240px; width: calc(100% - 240px);`) sem cobrir o conteúdo, e ao colapsar ela se estique para 100% (`margin-left: 0; width: 100%;`), eliminando gaveta flutuante por hover. Suíte `tests/sidebar_responsive_test.php` criada e aprovada (10/10). Asset version atualizado para `5.7.3`;
  * Correção milimétrica no PDF do Relatório de Negativados MED (`PdfService::gerarRelatorioNegativadosMedPdf`): recalibragem dos offsets do box de métricas superiores (`col2 = $left + 195.0`, `col3 = $left + 375.0`), eliminando o transbordo da frase `Res. BCB no 103 - MED Pix` sobre a borda lateral direita com 38 pt de margem interna segura; ampliação do respiro entre as colunas `ESTORNO` e `SALDO DEVEDOR` (vão de 21 pt); inclusão do símbolo de grau `'°' => 'o'` em `removerAcentos()` e `escapeText()`; e uso de `mb_substr` UTF-8 de 26 caracteres eliminando quebra e corrupção de turmas de dependentes (`Mariana Ribeiro (5o A)`);
* [x] **Auditoria Global ECC, Hardening e Otimização da Base de Código:**
  * Execução dos protocolos das skills do ECC (`production-audit`, `verification-loop`, `click-path-audit` e `error-handling`);
  * Criação do script de auditoria e verificação `tests/verification_audit.php` validando sintaxe em 181 arquivos PHP, proteção `CANTINA_SISTEMA`, integridade de todas as 128 rotas no `Router` e ausência de SQL injection;
  * Criação do script de auditoria comportamental `tests/click_path_audit.php` inspecionando 181 links internos e 51 formulários nas views;
  * Correção de rota inexistente (`/portal/recarga` -> `/portal/carteira`) e alinhamento de botão neutro em `app/views/showcase/portal.php` conforme `docs/DESIGN.md`;
  * Implementação da persistência estruturada de exceções não tratadas em `logs_erros` (Migration 025) no `ErrorHandler::tryLogToDatabase`, mantendo fallback silencioso para `error.log` em disco caso o banco esteja indisponível;
  * Implementação de desbloqueio automático de responsável com restrição MED em `TransacaoCarteira::creditar` quando uma recarga (Pix ou balcão) regulariza o saldo da família para `>= R$ 0,00`, com auditoria e notificação WhatsApp;
  * Correção de testes de regressão em `tests/mask_test.php` e `tests/tickets_web_test.php`;
  * Criação da suíte `tests/enhancements_audit_test.php` (10/10 asserções aprovadas);
  * 100% de aprovação em todas as 39 suítes de testes automatizados do projeto.

---

### [x] Fase 17 — Relatórios Gerenciais, Exportações (A4/CSV), Revisão de Segurança e Entrega
* [x] **Controlador Oficial de Relatórios (`app/controllers/RelatoriosController.php`):**
  * `index()`: Central / Hub de Relatórios Gerenciais com Stat-Strip de indicadores em tempo real (Faturamento Bruto do Mês, Peças Comercializadas, Desconto em Folha RH e Famílias Inadimplentes/MED) e diretório em cards navegáveis;
  * `extratoAluno()`: Extrato analítico do aluno com busca inteligente com autocomplete, filtro por período de datas, banner de identificação e alérgenos, tabela de recargas da família e tabela de consumo individual linha a linha;
  * `extratoAlunoPdf()`: Download do extrato do aluno em PDF A4 timbrado oficial;
  * `extratoAlunoImprimir()`: Espelho HTML limpo para impressão direta rápida (`window.print()`);
  * `buscarAlunosAjax()`: Endpoint JSON assíncrono para autocomplete com nome, matrícula, turma e saldo;
  * `vendasAlimento()`: Relatório analítico de vendas com agrupamento de itens, ticket médio ponderado, filtros combinados (período, turno, categoria e tipo) e classificação Curva ABC (A: até 70%, B: 70-90%, C: 90-100%);
  * `vendasAlimentoPdf()`: Emissão do relatório de vendas e Curva ABC em PDF A4 timbrado com suporte a múltiplas páginas;
  * `descontoFolha()`: Relatório mensal de consumo de professores e colaboradores por competência (mês/ano), stat-strip financeira e tabela com total a descontar;
  * `descontoFolhaCsv()`: Exportação oficial em CSV delimitado por ponto-e-vírgula (`;`), Byte Order Mark UTF-8 (`\xEF\xBB\xBF`) e vírgula decimal para folha de pagamento do RH / Excel;
  * `descontoFolhaPdf()`: Emissão do relatório de desconto em folha em PDF A4 timbrado com campos formais de assinatura/visto da diretoria da cantina e do departamento de RH;
  * `descontoFolhaDetalhesAjax()`: Endpoint JSON assíncrono com detalhamento linha a linha de todas as compras de um colaborador no mês para o modal corporativo;
  * `fechamentoCaixa()`: Demonstrativo consolidado de fechamento de caixa e prestação de contas por turno com apuração de gaveta, dinheiro, Pix balcão e quebras/sobras;
  * `devedores()`: Relatório nominal executivo de contas a receber e inadimplência familiar.
* [x] **Motor de PDF A4 Multi-páginas e Templates Oficiais (`app/services/PdfService.php`):**
  * `montarPdfMultiPaginas`: Construtor nativo PDF 1.4 multi-páginas calculando offsets, tabelas `/XRef`, objetos `/Page` e paginação dinâmica (`Página X de Y`);
  * `gerarExtratoConsumoAlunoPdf`: Layout timbrado A4 com dados da instituição, alérgenos, saldo atual, tabela de recargas e consumo com totalizadores;
  * `gerarEspelhoExtratoConsumoAlunoHtml`: Template HTML responsivo limpo para visualização e impressão imediata em navegadores;
  * `gerarRelatorioVendasAlimentoPdf`: Layout A4 em grade de alta densidade com totalizadores consolidados, filtros aplicados e badges de Curva ABC;
  * `gerarRelatorioDescontoFolhaPdf`: Layout contábil A4 com relação nominal de colaboradores, matrícula funcional, lanches e campos de assinatura formal.
* [x] **Views Corporativas de Alta Densidade (`app/views/relatorios/*`):**
  * `index.php`: Hub oficial de relatórios com Stat-Strip e cartões de acesso rápido;
  * `extrato_aluno.php`: Interface de consulta de aluno com busca interativa com debounce, seleção de período, perfil com alérgenos, tabelas e botões de exportação;
  * `vendas_alimento.php`: Data-grid analítico com filtros avançados, stat-strip, legenda e badges da Curva ABC e totalizadores em `<tfoot>`;
  * `desconto_folha.php`: Interface de prestação de contas com seletor de mês de competência, botões de exportação CSV e PDF, e modal `#modalDetalhesColaborador` para conferência item a item;
  * `fechamento_caixa.php`: Listagem auditada de sessões de caixa por turno com stat-strip e links diretos para espelho A4;
  * `devedores.php`: Painel de monitoramento de inadimplência e bloqueios preventivos MED.
* [x] **Matriz de Permissões RBAC e Navegação:**
  * Atualização da barra lateral (`app/views/layouts/sidebar.php`) incluindo "Extrato do Aluno" no menu Operacional;
  * Registro de 16 rotas no Front Controller `index.php` com RBAC estrito (`ROLE_OPERADOR` e `ROLE_ADMINISTRADOR` para extrato do aluno; `ROLE_ADMINISTRADOR` para relatórios gerenciais e financeiros).
* [x] **Suíte de Testes Automatizados, Auditoria Global e Hotfix de Colunas:**
  * Criação da suíte `tests/fase17_test.php` com 47 asserções aprovadas com 100% de sucesso (PDFs, espelho HTML, CSV UTF-8 BOM, Curva ABC matemática, consultas e RBAC);
  * Correção pontual de colunas em `app/controllers/RelatoriosController.php`:
    * Remoção de referências inexistentes a `status = 'concluida'` na tabela `vendas_balcao` (que não possui coluna `status`);
    * Correção de projeções da tabela `produtos` para `codigo_interno` e `descricao` (e não `codigo` e `nome`);
    * Correção de referência da tabela `pedidos` para `turno_consumo` (e não `turno`);
    * Testes HTTP ponta a ponta com sessão autenticada confirmando retorno HTTP 200 OK sem exceções em todas as rotas e exportações de relatórios.
  * Execução do script de auditoria `tests/verification_audit.php`:
    * Sintaxe PHP válida em 192 arquivos (100%);
    * Proteção `CANTINA_SISTEMA` em 100% dos arquivos internos;
    * 143 rotas no `Router` verificadas e vinculadas a métodos válidos;
    * Prepared Statements estritos em 100% das consultas SQL;
    * 40 de 40 suítes de testes automatizados aprovadas com 100% de sucesso.
* [x] **Refatoração Taste Design & Eliminação de Badges Coloridos (Hub e Telas de Relatórios):**
  * Envolvimento de todas as 6 views de relatórios (`index.php`, `vendas_alimento.php`, `extrato_aluno.php`, `devedores.php`, `desconto_folha.php`, `fechamento_caixa.php`) em contêineres responsivos com contenção equilibrada (`max-width: 1400px; margin: 0 auto;`), eliminando estiramento visual de ponta a ponta em monitores widescreen com a barra lateral recolhida;
  * Ampliação do espaçamento e respiro visual em `assets/css/custom.css` (`.app-page-content` para padding `24px 32px`, `.app-topbar` para `0 32px`, `.app-admin-footer` para `12px 32px` e `.stat-strip .stat-item` para `14px 20px`);
  * Eliminação completa de badges coloridos (vermelho, verde e azul) em todas as telas de relatórios, substituindo-os pelo componente corporativo neutro `.code-tag font-tabular` (fundo cinza `#f1f5f9`, borda `#cbd5e1` e texto `#334155`);
  * Conversão de botões de ação e filtros para o padrão corporativo neutro `.btn.btn-enterprise.btn-secondary`, em conformidade com as diretrizes do `AGENTS.md` e do Design System;
  * Invalidação de cache nos navegadores através da atualização da versão de assets para `5.8.0`.
* [x] **Ajuste de Tipografia, Botões Institucionais Azuis e Espaçamento dos Cards de Relatórios:**
  * Resolução do texto colado às bordas nos cartões do diretório de relatórios (`app/views/relatorios/index.php`): substituição da classe inexistente `p-3.5` por padding explícito e classe `.card-report` (`padding: 22px 24px;`);
  * Aplicação de destaque tipográfico evidente com negrito real nos títulos dos cards (`font-weight: 700 !important; font-size: 16px;`), corrigindo a perda de peso causada pela depreciação do Bootstrap 4 `font-weight-bold` em favor do Bootstrap 5 `fw-bold`;
  * Conversão dos botões de ação dos cards e dos botões primários de exportação para azul royal institucional (`.btn.btn-enterprise.btn-primary`), alinhados à paleta da marca e logotipo do colégio, mantendo todos os badges e chips de status em cinza neutro soberbo (`.code-tag font-tabular`);
  * Aplicação de respiro inferior amplo nos contêineres de relatórios (`pb-5` / 48px) evitando proximidade ou colisão com o rodapé da página;
  * Incremento da versão de cache dos assets para `5.8.5` em `app/core/Controller.php` e `app/views/layouts/admin.php`;
* [x] **Correção Global de Relatórios PDF (Unidade Escolar, Paginação e Totais):**
  * Remoção definitiva do texto `Unidade Escolar Única` / `Unidade Escolar Unica` de todos os cabeçalhos de relatórios PDF e espelhos HTML do sistema (`gerarRelatorioNegativadosMedPdf`, `gerarExtratoConsumoAlunoPdf`, `gerarEspelhoExtratoConsumoAlunoHtml`, `gerarRelatorioVendasAlimentoPdf`);
  * Correção definitiva da numeração de páginas em relatórios multi-páginas através do uso de marcadores neutros `__PAG_NUM__` e `__PAG_TOT__` imunes ao escape de `sprintf` e substituição com suporte a variantes no `PdfService`, eliminando o defeito visual de texto truncado `Pagina %PAGE_NUM% de %PA` e exibindo perfeitamente `Pagina X de Y`;
  * Ajuste de espaçamento visual na linha de totais consolidados no relatório de Vendas por Alimento, com Preço Médio ancorado em $X = 415.0$ e Faturamento Total em $X = 496.0$, garantindo mais de 23 pontos de respiro entre os números e impedindo qualquer colisão;
  * Alinhamento estrito e respiro mínimo de 35 pontos nas colunas do relatório contábil de Desconto em Folha para RH;
* [x] **Harmonização Global de Botões de Ação, Filtros e Controles no Azul Institucional Oficial:**
  * Conversão integral de todos os botões de ação, submissão/filtro, confirmação, cabeçalhos de ação e controles de alternância das telas operacionais, de estoque, administrativas, portal da família e relatórios para a classe primária `.btn.btn-enterprise.btn-primary` e `.btn-outline-primary` (azul royal `#1d4ed8` oficial da marca e do logotipo do Colégio Monteiro Lobato):
    * **Administração:**
      * `app/views/admin/produtos.php`: botão "Gerenciar Categorias", "Filtrar" e "Limpar";
      * `app/views/admin/turmas.php`: botões "Filtrar" e "Limpar";
      * `app/views/admin/alunos.php`: botão "Importação em Lote", "Filtrar" e "&times;" (Limpar);
      * `app/views/admin/categorias.php`: botão de navegação "&larr; Cardápio";
      * `app/views/admin/colaboradores.php`: botões "Buscar" e "Limpar";
      * `app/views/admin/contestacoes.php`: botão de ação "Exportar PDF", "Filtrar Registros", "Limpar" e botão inline de limpar busca rápida;
      * `app/views/admin/importacao_escolar.php`: botões "Baixar Planilha Modelo (.csv)" e "Ver Lista de Alunos";
      * `app/views/admin/index.php`: botões de atalho dos cards executivos ("Acessar Corte", "Imprimir Tickets", "Abrir PDV", "Gerenciar Caixa");
      * `app/views/admin/relatorios.php`: botões de atalho dos relatórios gerenciais ("Painel", "Ver Movimentações", "Inventário");
    * **Estoque:**
      * `app/views/estoque/index.php`: botões "Extrato de Movimentações", "Lançar Perda", "Filtrar" e "&times;" (Limpar);
      * `app/views/estoque/movimentacoes.php`: botões "Lançar Perda", "Ver Saldos Atuais", "Limpar Filtros" e "Filtrar Movimentações";
      * `app/views/estoque/notas_fiscais.php`: botão "Grade de Saldos" e "&times;" (Limpar);
      * `app/views/estoque/nota_fiscal_detalhes.php`: botões "Visualizar XML", "Baixar XML Original", "&larr; Grade de Saldos" e "Copiar Chave";
      * `app/views/estoque/perdas.php`: botões "Histórico Completo de Perdas" e "Grade de Saldos";
      * `app/views/estoque/importar_xml.php`: botões "Ver Notas Importadas" e "Grade de Saldos";
      * `app/views/estoque/conferir_xml.php`: botão de confirmação "Confirmar Entrada de Mercadorias e Abastecer Estoque";
    * **Operacional:**
      * `app/views/operacional/corte.php`: botões "Exportar PDF (A4)", "Imprimir A4", "Conferir Recebimento &rarr;", "Atualizar Painel" e "Copiar Texto" (WhatsApp);
      * `app/views/operacional/recebimento.php`: botões "&larr; Painel de Corte", "Preencher com Valores Esperados" e "Confirmar Recebimento e Dar Entrada";
      * `app/views/operacional/tickets.php`: botões "Imprimir Bobina do Turno (80mm)", "Baixar Lote em PDF" e "Buscar";
      * `app/views/operacional/pdv.php`: seletores de turno "Manhã" e "Tarde", abas de cliente "Anônimo", "Aluno" e "Colaborador", atalhos de topo "Caixa [F7]", "Estorno [F8]", "Sobras [F9]", botão "Conferir Caixa outro turno", botão principal "Concluir Venda (F4)" e botão de impressão "Imprimir Comprovante Térmico (80mm)";
      * `app/views/operacional/caixa.php`: alternador de turno "Matutino" / "Vespertino", botão "Extrato da Sessão", "&larr; Voltar ao PDV", botão "Limpar" e ações dos modais de recarga ("Baixar Comprovante em PDF", "Concluir e Atualizar Caixa", "Confirmar Recarga em Dinheiro");
      * `app/views/operacional/caixa_detalhes.php`: botões "Visualizar Espelho A4", "Baixar PDF A4" e "&larr; Voltar ao Caixa";
      * `app/views/operacional/abertura_caixa.php`: botões "&larr; Voltar à Gestão de Caixa" e "Confirmar Abertura de Caixa";
      * `app/views/operacional/fechamento_caixa.php`: botão "&larr; Voltar à Gestão de Caixa";
    * **Portal da Família:**
      * `app/views/portal/index.php`: botões dos cards "Recarregar Saldo" e "Ver Extrato";
      * `app/views/portal/carteira.php`: botões "Agendar Lanches", "Copiar" (código Pix), "Nova Recarga", "Filtrar" e "&times;" (Limpar);
      * `app/views/portal/meus_pedidos.php`: botões "+ Novo Agendamento", "Extrato Financeiro", "Filtrar" e "&times;" (Limpar);
      * `app/views/portal/perfil.php`: botão "&larr; Voltar ao Início";
      * `app/views/portal/alergias.php`: botão "&larr; Voltar ao Início";
      * `app/views/portal/agendamento.php`: botão "Meus Pedidos", seletores de semana "Semana Corrente" / "Próxima Semana" e botões dos cards de estado vazio/dia não letivo ("Selecionar Outro Dia Letivo", "Ver Outras Datas", "Ver em Meus Pedidos");
    * **Relatórios:**
      * `app/views/relatorios/vendas_alimento.php`: botão "Filtrar";
      * `app/views/relatorios/desconto_folha.php`: botões "Exportar CSV para Folha" e "Carregar Mês";
      * `app/views/relatorios/fechamento_caixa.php`: botões "Operação de Caixa Hoje", "Filtrar" e "Limpar";
      * `app/views/relatorios/devedores.php`: botões "Filtrar" e "Limpar";
      * `app/views/relatorios/extrato_aluno.php`: botões "Imprimir Extrato" e "Filtrar";
  * **Regras de Preservação Mantidas Rigorosamente:**
    * Botões utilitários de linhas de tabelas/listas internas (`<td>...</td>`, ex: "Ver", "Editar", "Excluir", "Perda", "Mín", "Quitar Débito", "Desbloquear", "Reimprimir 2ª Via") foram mantidos intocados no padrão neutro/secundário discreto, atendendo estritamente ao pedido do usuário ("não converta os botões dentro das lista");
    * Botões de cancelamento/fechamento de modais (`data-bs-dismiss="modal"`, "Cancelar", "Fechar", "Voltar") mantidos em padrão neutro;
  * **Eliminação de Duplicidade em Espelhos HTML:**
    * Removida a barra de ações/botões redundante que aparecia no rodapé após as assinaturas no espelho de fechamento de caixa (`/operacional/caixa/espelho/{id}`), centralizando os botões de retorno, download de PDF e impressão exclusivamente na barra de ferramentas do topo;
  * **Correção e Calibração de Espaçamento em Campos de Busca com Ícone Interno (`.search-input-wrapper`):**
    * Criada e calibrada a classe `.search-input-wrapper` em `assets/css/custom.css` com ícone absoluto (`left: 14px`, `pointer-events: none`) e `padding-left: 46px !important` no input (com inline style complementar), garantindo respiro visual amplo e confortável de 18px entre a lupa e o texto digitado no Extrato do Aluno (`/operacional/extrato-aluno`), Devedores/MED, Cardápio/Produtos e PDV;
  * **Desbloqueio de Overflow do Autocomplete Flutuante no Extrato do Aluno:**
    * Adicionada a classe `card-overflow-visible` e `overflow: visible !important;` no card de filtros, card-body e form de `app/views/relatorios/extrato_aluno.php`, eliminando o corte físico do dropdown de sugestões;
    * Posicionado `#autocompleteDropdown` de forma flutuante (`top: calc(100% + 4px); z-index: 1080;`) sobre a página com sombra e bordas arredondadas, exibindo a lista completa de alunos com nome, turma, matrícula e saldo;
    * Adicionada navegação por teclado (`ArrowDown`, `ArrowUp`, `Enter`, `Escape`), feedback de busca imediato e teste automatizado de regressão em `tests/enhancements_audit_test.php`;
  * **Padronização Global de Paginação com 20 Itens por Página no Extrato do Aluno:**
    * Atualizado o componente parcial `app/views/partials/pagination.php` para suportar nome de parâmetro customizado (`$paramNome`, ex: `pagina_recargas`, `pagina_consumo`) e âncora de rolagem direta (`$anchor`, ex: `#secao-recargas`, `#secao-consumo`), preservando 100% dos filtros da query string;
    * Implementada a paginação no controller `RelatoriosController::extratoAluno()` e `obterDadosExtratoAluno()` com fatiamento de 20 itens por página para ambas as seções, mantendo o cálculo consolidado dos totais gerais (R$ total recarregado, R$ total consumido e total de atendimentos) sobre todo o período filtrado;
    * Garantido que as rotas de emissão de PDF A4 timbrado (`extratoAlunoPdf`) e espelho de impressão contínua/A4 (`extratoAlunoImprimir`) recebam `$paginar = false`, preservando a impressão e exportação com a totalidade dos registros;
    * Adicionados os blocos de paginação e badges dinâmicos de contagem total em `app/views/relatorios/extrato_aluno.php`;
    * Criada a suíte de testes automatizados `tests/extrato_aluno_pagination_test.php` com 21 asserções cobrindo integridade estática e funcional;
  * **Correção de Codificação de Caracteres Especiais no PDF do Extrato do Aluno:**
    * Mapeamento expandido em `PdfService::removerAcentos()` para pontuações tipográficas e símbolos (`•`, `—`, `–`, `“`, `”`, `‘`, `’`, `º`, `ª`);
    * Sanitização estrita em `PdfService::escapeText()` para ASCII imprimível (`\x20-\x7E`) com transliteração `iconv`;
    * Padronização de todos os cabeçalhos, rótulos e seções de `gerarExtratoConsumoAlunoPdf()` em ASCII limpo (`BENEFICIARIO:`, `RESPONSAVEL FINANCEIRO:`, `Matricula: ... | ...`, `RECARGAS NO PERIODO`, `CONSUMO NO PERIODO`, `PERIODO CONSULTADO`, `COLEGIO MONTEIRO LOBATO - EXTRATO DE CONSUMO - CONTINUACAO`, `HISTORICO...`, `UNITARIO`, etc.), eliminando 100% dos caracteres corrompidos (*mojibake*);
    * Correção em `gerarComprovanteVendaBalcaoPdf()` substituindo `Sessão Caixa:`, `Matrícula:` e `Venda Balcão` por versões ASCII puras;
  * **Remoção de Botão Inútil 'Voltar' no Espelho de Impressão:**
    * Removido o botão `← Voltar ao Sistema` com `window.history.back()` em `PdfService::gerarEspelhoExtratoConsumoAlunoHtml()`, eliminando um botão inoperante e mantendo exclusivamente a ação de destaque `🖨️ Imprimir Extrato` quando aberto em nova aba (`target="_blank"`);
  * **Otimização de Alta Performance da Visão Prévia de Impressão (Chromium):**
    * Substituição da imagem original em Base64 (65.5 KB) pela miniatura otimizada de 2.3 KB em `PdfService::gerarEspelhoExtratoConsumoAlunoHtml()` (`self::getLogoDataUri(true)`), reduzindo o payload de imagem em > 63 KB;
    * Eliminação de `display: grid` nos blocos `.student-box` e `.stat-strip`, substituindo por Flexbox (`display: flex;`) com `break-inside: avoid; page-break-inside: avoid;`, sanando o bug de paginação do motor Blink;
    * Aplicação de `table-layout: fixed !important;` e `thead { display: table-header-group !important; }` na tabela `.table-relatorio`, em conjunto com `break-inside: avoid !important; page-break-inside: avoid !important;` nas linhas `<tr>`, permitindo cálculo O(1) de largura e paginação instantânea;
    * Despacho assíncrono de `window.print()` via `setTimeout(..., 50)` com feedback no botão ("Preparando..."), liberando o event loop do navegador;
    * Inclusão do botão "📄 Baixar PDF Direto" na barra de ações para download imediato do extrato em PDF A4 oficial;
  * **Padronização Corporativa da Barra de Paginação em Relatórios (FSD & DESIGN.md):**
    * Substituição da paginação simples anterior de `fechamento_caixa.php` pelo componente corporativo unificado `partials/pagination.php`, exibindo contador analítico com numerais tabulares ("Mostrando 1 a 20 de X registros (20 por pág.)"), botão "‹ Anterior" (com desabilitação semântica quando na primeira página), botões numéricos compactos com microinteração tátil, botão "Próxima ›", reticências e salto direto para a última página;
    * Padronização idêntica aplicada em `app/views/relatorios/devedores.php` e `app/views/admin/contestacoes.php`;
    * Defesa contra inconsistências com fallbacks em `pagination.php` para `$pagina` e `$totalHistorico`;
    * Criação da suíte `tests/fechamento_caixa_pagination_test.php` com 10 asserções automatizadas cobrindo a renderização do novo layout;
  * **Implementação de Relatórios Individuais de Desconto em Folha por Professor/Colaborador (FSD & Pedido do Usuário):**
    * Evolução da tela de Desconto em Folha para RH (`/relatorios/desconto-folha`) atendendo ao requisito operacional de individualização de conferência e entrega aos professores e colaboradores;
    * Criação do método `PdfService::obterStreamsPaginasIndividualColaborador()` gerando folhas multipáginas com cabeçalho oficial timbrado, miniatura circular do logotipo, box de identificação do colaborador (matrícula, nome, cargo, foto), Stat-Strip de totais, tabela discriminada de itens (Data/Hora, Turno, Venda #, Produto/Lanche, Qtd, Preço Unitário e Subtotal), linha totalizadora, termo formal de ciência do colaborador e campos de assinatura para o Professor e Visto da Cantina / Diretoria;
    * Criação do método `PdfService::gerarRelatorioDescontoFolhaIndividualPdf()` gerando PDF 1.4 binário puro para cada colaborador com numeração de páginas dinâmica resolvida;
    * Criação do método `PdfService::gerarRelatorioDescontoFolhaIndividualLotePdf()` gerando lote único consolidado contendo as folhas individuais de todos os professores/colaboradores do mês selecionado em páginas segregadas;
    * Criação do método `PdfService::gerarEspelhoDescontoFolhaIndividualHtml()` gerando espelho responsivo limpo para visualização em navegador com barra de ferramentas flutuante, botão de impressão direta `@media print`, botão para download de PDF e regras de estilo corporativas;
    * Implementação dos endpoints e rotas protegidas sob `ROLE_ADMINISTRADOR`:
      * `GET /relatorios/desconto-folha/individual-pdf` (`RelatoriosController::descontoFolhaIndividualPdf`);
      * `GET /relatorios/desconto-folha/individual-imprimir` (`RelatoriosController::descontoFolhaIndividualImprimir`);
      * `GET /relatorios/desconto-folha/lote-pdf` (`RelatoriosController::descontoFolhaLotePdf`);
    * Atualização da view `app/views/relatorios/desconto_folha.php`:
      * Adicionado campo de busca instantânea `#filtroProfessor` com filtragem ao vivo por nome ou matrícula funcional sem recarregar a tela;
      * Adicionado botão de topo "Baixar Todos os Individuais (Lote PDF)";
      * Expansão da coluna de ações da tabela ("Relatório & Ações") com 3 botões dedicados e discretos por colaborador: "Ver Itens" (abre modal AJAX), "PDF Individual" (baixa PDF A4 timbrado) e "Imprimir" (abre espelho HTML em nova aba);
      * Inclusão dos botões "Baixar PDF Individual" e "Imprimir" dentro do rodapé do modal `#modalDetalhesColaborador`;
  * **Implementação de Salgados Fracionados de Salgado Grande (1 Grande = 2 Pequenos para Fornecedor):**
    * Atendimento ao requisito de negócio onde salgados pequenos são comprados e preparados pela cantina cortando um salgado grande ao meio;
    * **Migração 027:** Criada e executada `database/migrations/027_add_fracionado_grande_to_produtos.php` adicionando `fracionado_salgado_grande TINYINT(1) NOT NULL DEFAULT 0` na tabela `produtos`;
    * **Model Produto & AdminController:** Atualizados métodos `criar()` e `atualizar()` em `Produto.php` e `produtosSalvar()` em `AdminController.php` para captura e persistência do campo;
    * **Interface Administrativa de Produtos (`app/views/admin/produtos.php`):**
      * Adicionado switch/checkbox corporativo `#prod_fracionado_grande` com texto explicativo e microcopy contextual ("Fracionar de Salgado Grande para Fornecedor (1 Grande = 2 Pequenos)");
      * Adicionado badge sutil `½ Salgado Grande` na listagem de produtos para conferência visual imediata pelo administrador;
      * Sincronização nos manipuladores JavaScript `abrirModalProduto()` e `editarProduto(prod)`;
    * **Consolidação do Corte do Turno (`app/models/Pedido.php`):**
      * Método `consolidarCorte()` atualizado para identificar produtos marcados com a flag;
      * Se quantidade for 15 pequenos pedidos: divide em 7 salgados grandes (`intdiv(15, 2) = 7`) com sufixo `-G` e descrição semântica ("Grande") com preço unitário proporcional dobrado, e 1 salgado pequeno restante (`15 % 2 = 1`) com sufixo `-P` e descrição semântica ("Pequeno");
      * Se a quantidade for par (ex: 14 pequenos): gera exatamente 7 salgados grandes e 0 pequenos;
      * Se a quantidade for ímpar unitária (ex: 1 pequeno): gera 0 grandes e 1 pequeno;
      * Recálculo exato de `total_pecas` (peças físicas a encomendar) e `total_demanda_unidades` (lanches a servir aos alunos);
    * **Comunicação e Exportações Operacionais:**
      * Formatação de mensagem para WhatsApp (`Pedido::obterTextoWhatsAppCorte`) totalizando peças reais do fornecedor;
      * Geração de PDF A4 timbrado e espelho HTML (`PdfService`) exibindo as linhas discriminadas de Grandes e Pequenos com seus respectivos códigos e quantitativos;
    * **Conferência Física e Baixa de Estoque (`confirmarRecebimentoFornecedor` e `app/views/operacional/recebimento.php`):**
      * Inputs ocultos `fator_conversao` (fator 2 para grande e fator 1 para pequeno) enviados na conferência física;
      * `confirmarRecebimentoFornecedor()` agrupa itens por `produto_id` e multiplica as peças recebidas pelo fator de conversão;
      * `estoque_movimentacoes` registra a entrada física equivalente e a liquidação exata da demanda dos alunos em unidades de consumo;
    * **Testes e Validação:**
      * Criação da suíte `tests/salgados_fracionados_test.php` com 36 asserções aprovadas com 100% de sucesso;
      * Regressão da Fase 11 (`tests/fase11_test.php`) com 72/72 asserções aprovadas;
      * Regressão da Fase 7 (`tests/fase7_test.php`) com 68/68 asserções aprovadas.
  * **Saneamento do Catálogo de Produtos e Pedidos Reais de Teste para Hoje (28/09/2026):**
    * Arquivamento via soft delete de 618 produtos acumulados de baterias de testes (como `Item Teste Bloq`, `Lanche Teste Edição`, múltiplos registros idênticos de Coxinhas e Pães de Queijo), eliminando toda duplicidade e deixando exatamente 1 registro limpo de cada item;
    * Em **Salgados Assados**, preservados exclusivamente o Salgado Grande (`Coxinha de Frango Grande` #252 - R$ 8,00) e seu correspondente pequeno (`Coxinha de Frango pequena` #666 - R$ 4,00 com flag fracionada ativa);
    * Criação de 2 pedidos agendados para a data de hoje (28/09/2026 - Turno Matutino) para os alunos Lucas Alcantara Santos (10 un) e Lucas Silva (5 un), totalizando 15 unidades de lanches pequenos;
    * Validação em tempo real do Corte do Turno matutino de hoje exibindo exclusivamente 7 Salgados Grandes e 1 Salgado Pequeno, totalizando 8 peças a encomendar do fornecedor;
  * **Blindagem e Ocultação Absoluta dos Módulos Administrativos para o Perfil Operador (RBAC na Interface):**
    * Identificada a causa raiz: a barra lateral (`app/views/layouts/sidebar.php`) renderizava o bloco `navSectionAdmin` ("ADMINISTRAÇÃO") e a opção "Entrada XML NF-e" sem condicional de perfil, confiando apenas no bloqueio RBAC do backend ao clicar;
    * Implementada verificação `$isAdmin = ($userRole === 'administrador');` envolvendo a opção "Entrada XML NF-e" em `navSectionEstoque` e a seção completa `navSectionAdmin` em `app/views/layouts/sidebar.php`;
    * Quando o usuário logado possui perfil `operador` (ex.: Maria Atendente / Operador do Caixa), a seção inteira "ADMINISTRAÇÃO" e o link "Entrada XML NF-e" são 100% omitidos do DOM/HTML, exibindo exclusivamente os módulos autorizados: **Operacional** (Corte do Turno, Conferência Fornecedor, Tickets Térmicos, PDV Balcão, Caixa do Turno, Extrato do Aluno) e **Estoque** (Saldos & Pausa, Lançamento de Perdas);
    * Restrita a rota `GET /admin/produtos` no `index.php` exclusivamente a administradores (`ROLE_ADMINISTRADOR`), mantendo o endpoint `POST /admin/produtos/toggle-pausa` para alternâncias de pausa rápida acionadas a partir de `/estoque`;
    * Atualizadas as suítes de testes automatizados `tests/sidebar_visibility_test.php` (14/14 asserções) e `tests/admin_web_test.php` (10/10 asserções), e criada a suíte E2E `tests/operator_ui_sanitization_test.php` com 16/16 asserções aprovadas com 100% de sucesso.
  * Incremento do assetVersion para `5.9.9` em `app/core/Controller.php` e `app/views/layouts/admin.php` para invalidação imediata de cache nos navegadores.
  * **Evolução do Módulo de Calendário Escolar Interativo (`/admin/calendario`):**
    * **Diagnóstico Inicial:** A aba "Calendário Escolar" era estática, exibindo apenas a semana fixa de segunda a sexta como dias letivos sem controles de edição ou navegação;
    * **Navegação Semanal Dinâmica (`CalendarioEscolar::obterDiasSemanaLetiva`):**
      * Suporte ao parâmetro opcional `$dataBase` (formato YYYY-MM-DD), permitindo transitar por qualquer semana do ano civil;
      * Controles de navegação na view: botão `‹ Anterior`, botão neutro `Hoje`, botão `Próxima ›`, e seletor rápido de semanas com datepicker (`?semana=YYYY-MM-DD`);
    * **Gestão de Exceções de Calendário (Feriados, Recessos e Férias):**
      * Métodos `salvarExcecao(string $data, string $tipo, string $descricao, ?int $usuarioId = null)` e `removerExcecao(string $data)` com UPSERT idempotente em `calendario_letivo`;
      * Método `listarExcecoes(?int $ano = null)` e `contarMetricasAno(?int $ano = null)` com agregação de feriados, recessos e suspensões;
      * Tabela anual com badges sóbrios, motivo/descrição, usuário responsável pelo cadastro e ação de exclusão/restauração do dia letivo;
    * **Rotina Atômica de Suspensão Emergencial com Estorno Automático em Lote:**
      * Método `CalendarioEscolar::cancelamentoEmergencial(string $data, string $motivo, int $usuarioId, string $ipOrigem)`;
      * Execução dentro de transação PDO atômica com locks pessimistas:
        1. Localiza todos os pedidos com status `agendado` ou `editado` para a data da suspensão;
        2. Altera os pedidos para `cancelado` e cancela todos os tickets térmicos associados;
        3. Libera o estoque reservado de produtos industrializados;
        4. Realiza o estorno de 100% dos valores pagos para as carteiras digitais dos responsáveis via `TransacaoCarteira::creditar(tipo: 'estorno_cancelamento')`;
        5. Cadastra a data em `calendario_letivo` como `cancelado_emergencial`;
        6. Grava trilha de auditoria imutável em `logs_auditoria` com detalhes do cancelamento em lote;
    * **Integração Automática com o Portal da Família (`/portal/agendamento`):**
      * O método `CalendarioEscolar::isDiaLetivo($data)` retorna `false` para qualquer data marcada como feriado, recesso ou suspensão emergencial;
      * O Portal da Família bloqueia imediatamente a seleção de lanches e agendamento para essas datas de forma nativa e automática;
    * **Interface Alinhada ao Design System Anti-Slop:**
      * Stat-Strip institucional no topo com 4 cartões de indicadores (Dias Letivos Semana, Feriados Ano, Recessos Escolares, Suspensões Ativas);
      * Tabela da semana com cards limpos, badges sóbrios (`● Dia Letivo Regular`, `● Feriado`, `● Recesso`, `● Suspensão Emergencial`) e botão de ação rápida por dia;
      * Modais Bootstrap 5 nativos e responsivos: `#modalExcecao` (para feriado/recesso) e `#modalEmergencial` (com alerta de cancelamento e estorno em lote);
    * **Testes e Validação:**
      * Criação da suíte `tests/calendario_escolar_test.php` com 31 asserções aprovadas com 100% de sucesso cobrindo navegação, persistência, métricas, remoção, cancelamento emergencial em lote com estorno e renderização da view;
      * Regressão da sanitização da UI do operador (`tests/operator_ui_sanitization_test.php`) com 16/16 asserções aprovadas;
      * Regressão da Fase 10 (`tests/fase10_test.php`) com 71/71 asserções aprovadas.

---

### [x] Fase 18 — Carga e Saneamento de Dados Oficiais da Escola (Matrículas Reais)
* [x] **Script de Extração e Parsing Oficial (`database/parse_matriculas.py`):**
  * Extração e parsing completo do documento oficial `docs/alterações sistema guideline/Matriculas.pdf` (23 páginas);
  * Identificação de todas as 19 turmas escolares oficiais por turno (67 alunos matutinos e 165 alunos vespertinos);
  * Reconhecimento exato de turmas especiais e núcleos inclusivos (ex.: `Núcleo de Apoio Infantil A - MAT`);
  * Agrupamento de responsáveis financeiros por CPF, tratando casos de múltiplos dependentes com carteira compartilhada;
  * Geração do arquivo estruturado `database/matriculas_reais.json` com integridade 100% validada.
* [x] **Seeder Idempotente de Alta Confiabilidade (`database/seed_matriculas_reais.php`):**
  * Inserção/atualização idempotente usando transações PDO;
  * Sincronização de 19 turmas escolares oficiais com séries, nomes de turma e turnos padronizados;
  * Sincronização de 195 responsáveis financeiros únicos (197 agrupamentos), garantindo unicidade de CPF (11 dígitos), e-mail de contingência e senha temporária segura;
  * Sincronização de 232 alunos reais com matrículas oficiais únicas de 8 dígitos, vínculos relacionais completos e zero órfãos;
  * Reconciliação harmônica de turmas e alunos legados preservando suítes de testes anteriores;
  * Preservação integral e intocada dos operadores, administradores e produtos existentes da cantina.
* [x] **Evolução de Models do Sistema:**
  * `App\Models\Turma`: criação de métodos `localizarOuCriar` e `buscarPorNomeETurno`;
  * `App\Models\Responsavel`: criação de métodos `localizarOuCriarPorCpf` e alias `buscarPorCpf`;
  * `App\Models\Aluno`: criação de métodos `localizarOuCriarPorMatricula` e `buscarPorMatricula`;
  * `App\Models\Usuario`: criação do método `listarTodos`.
* [x] **Testes Automatizados e Auditoria Global:**
  * Criação da suíte oficial `tests/fase18_test.php` com 44 asserções executadas e 100% aprovadas;
  * Execução da auditoria global de verificação (`tests/verification_audit.php`): 47/47 suítes aprovadas com 100% de sucesso.

---

### [x] Fase 19 — Módulo Completo (CRUD) de Estorno de Vendas e Invalidação
* [x] **Migration 028 (`database/migrations/028_add_estorno_fields_to_vendas_balcao.php`):**
  * Adição das colunas `status` ENUM('concluida', 'estornada'), `estornado_em`, `estornado_por` (FK para `usuarios`) e `motivo_estorno` na tabela `vendas_balcao`;
  * Expansão do ENUM `status` de `pedidos` para incluir `'estornado'`, e adição das colunas `estornado_em`, `estornado_por` e `motivo_estorno`;
  * Expansão do ENUM `tipo_movimentacao` de `estoque_movimentacoes` para incluir `'entrada_estorno'`.
* [x] **Modelos de Dados Atualizados:**
  * `app/models/VendaBalcao.php`: métodos `listarParaEstorno(array $filtros)` e `estornarVenda(int $vendaId, int $operadorId, string $motivo, string $ipOrigem)` com transação atômica PDO, devolução física de estoque via `entrada_estorno`, ajuste da gaveta do caixa corrente e auditoria imutável;
  * `app/models/Pedido.php`: métodos `listarParaEstorno(array $filtros)` e `estornarPedido(int $pedidoId, int $operadorId, string $motivo, string $ipOrigem)` com reembolso integral na carteira familiar (`TransacaoCarteira::creditar`), cancelamento de tickets térmicos associados e liberação de reservas;
  * `app/models/Aluno.php`: método `listarParaSelecao()` para dropdowns operacionais rápidos;
  * `app/models/Colaborador.php`: método `listarParaSelecao()` para dropdowns operacionais rápidos.
* [x] **Controlador (`app/controllers/EstornoVendasController.php`):**
  * Ação `index`: processamento de filtros (Venda avulsa Sim/Não, Filtrar por Aluno/Colaborador, Pessoa, Período De/Até e Motivo) e renderização da tela;
  * Ação `detalhesAjax`: endpoint JSON assíncrono para exibição dos produtos e histórico da venda no modal informativo;
  * Ação `gravar`: validação de CSRF, motivo obrigatório (mínimo 5 caracteres), execução de estornos em lote e feedback tátil corporativo.
* [x] **View Operacional (`app/views/operacional/estorno_vendas.php`):**
  * Layout no padrão visual corporativo do sistema com botões no padrão azul primário (`btn-primary` e `btn-outline-primary`);
  * Ações de cabeçalho: botões `✕ Cancelar` e `💾 Gravar`;
  * Barra de digitação inteligente com autocomplete instantâneo para Alunos e Colaboradores (dispensa rolagem em listas extensas de pessoas);
  * Card de filtros com toggles segmentados de Venda Avulsa (Sim / Não) e Aluno / Colaborador;
  * Tabela operacional de alta densidade com cabeçalho azul cobalto (`#2563eb`), checkbox mestre de seleção múltipla, tipografia tabular nos valores (`.font-tabular`), colunas normalizadas de data/hora para Balcão e Encomendas Online e botão informativo `(i)`;
  * Modal de detalhes assíncrono com suporte nativo a Vendas Balcão e Pedidos Online (com fallback defensivo de identificador e exibição discriminada de itens e vínculo de turma/responsável);
  * Modal de confirmação pré-estorno com resumo financeiro e validação de justificativa obrigatória.
* [x] **Navegação e Roteamento:**
  * Rotas `/operacional/estornos`, `/operacional/estornos/detalhes` e `/operacional/estornos/gravar` registradas em `index.php` protegidas por RBAC (`ROLE_OPERADOR` e `ROLE_ADMINISTRADOR`);
  * Link oficial "Estorno de Vendas" adicionado na sidebar operacional (`app/views/layouts/sidebar.php`).
* [x] **Testes Automatizados e Auditoria Global:**
  * Criação da suíte oficial `tests/fase19_test.php` com 34 asserções executadas e 100% aprovadas;
  * Execução da auditoria global de verificação (`tests/verification_audit.php`): 48/48 suítes aprovadas com 100% de sucesso;
  * **Purga Definitiva de Registros Falsos e Blindagem de Testes:** Criação do script `database/purgar_fakes_e_semear_reais.php` eliminando todos os alunos/responsáveis/pedidos residuais de teste e gerando encomendas online e vendas balcão reais exclusivamente com os 232 alunos oficiais de `Matriculas.pdf`; adição de limpezas defensivas automáticas em `tests/fase13_test.php`, `tests/pedido_editar_web_test.php` e `tests/calendario_escolar_test.php` para impedir que novas execuções de testes voltem a poluir o banco de dados.

---

### [x] Fase 20 — Nova Arquitetura de PDF (HTML+CSS+Gotenberg) & Migração Completa de Relatórios + Fechamento de Caixa Oficial
* [x] **Infraestrutura Gotenberg & Fallbacks Robustos:**
  * Adicionado o contêiner `gotenberg:8` no `docker-compose.yml` (porta 3000:3000) com Chromium headless integrado;
  * Chaves de configuração adicionadas em `config/config.example.php` e `config/config.php` (`pdf.driver => 'gotenberg'`, `pdf.gotenberg_url => 'http://gotenberg:3000'`);
  * Refatoração completa de `app/services/PdfService.php` reduzindo de 5.751 linhas para ~925 linhas limpas, desacoplando o desenho do PDF através de views HTML + CSS compiladas e convertidas via API HTTP multipart do Gotenberg (`/forms/chromium/convert/html`);
  * Motor de contingência nativo e estruturado (`montarPdfEstruturado` e `montarPdfMultiPaginas`) garantindo geração de binários PDF válidos mesmo caso o serviço externo de renderização esteja indisponível;
  * Isolamento de escopo no `renderView()` com parâmetro protegido `$_viewVars` evitando colisão de variáveis em templates.
* [x] **Espelho Oficial A4 de Fechamento de Caixa:**
  * Implementada a view oficial `app/views/relatorios/fechamento_caixa_pdf.php` alinhada aos modelos reais `28 setembro matutino.pdf` e `28 setembro vespertino.pdf`;
  * Exibição das 4 seções contábeis oficiais:
    1. **Duplicatas:** Relação nominal de vendas faturadas em duplicata com parcelamento e vencimentos;
    2. **Vendas Cantina Web & Vendas Presenciais no Balcão:** Segregação e destaque analítico entre *Vendas Cantina Web (Online)* e *Vendas Presenciais no Balcão*, com funcionários/colaboradores unificados;
    3. **Recebimento em Pix:** Registros discriminados de recargas e pagamentos via Pix com valor líquido apurado;
    4. **Resumo do Caixa:** Consolidação com as 4 colunas mestre: Fundo de Troco Inicial, Entradas, Saídas e Saldo Final em Dinheiro na Gaveta;
  * Identificador de controle `#ID` da sessão, cabeçalho institucional com logotipo do colégio e área de assinaturas do Operador de Caixa e Gerente da Cantina;
  * Modelo `app/models/SessaoCaixa.php` enriquecido com o método `obterDadosFechamentoOficial()`.
* [x] **Migração de Relatórios e Cupons Térmicos para Views HTML:**
  * `app/views/relatorios/corte_pdf.php`: Relatório A4 do Corte do Turno com tipografia tabular (`.font-tabular`), discriminação de salgados grandes vs pequenos (`PEÇAS`) e agrupamentos por turno;
  * `app/views/tickets/ticket_80mm.php`: Bobina térmica de 80mm com contêiner `.receipt-roll`, botão de impressão direta, destaque `[ATENÇÃO: ...]` e separador visual de corte `✂ - - - - - - - - - - - - - - - - - - - -`;
  * `app/views/tickets/cupom_pdv_80mm.php`: Comprovante de venda balcão em bobina contínua 80mm com `Comprovante de Venda #ID` e vínculo do estudante;
  * `app/views/relatorios/recarga_dinheiro_pdf.php`: Comprovante térmico de recarga em dinheiro com título institucional `COMPROVANTE DE RECARGA — Carteira Familiar`;
  * `app/views/relatorios/desconto_folha_pdf.php`, `desconto_folha_individual_pdf.php`, `med_negativados_pdf.php`, `vendas_alimento_pdf.php`, `extrato_aluno_pdf.php`: Todos migrados e compatíveis com a nova arquitetura.
* [x] **Aprimoramentos de Caixa Operacional e Recargas Presenciais (Balcão):**
  * Botão de ação rápida `Emitir` no topo da tela `/operacional/caixa` evoluído para dropdown com opções claras: "Visualizar / Imprimir (A4)" (espelho HTML com `window.print`) e "Baixar em PDF (A4)" (download direto de PDF A4 timbrado);
  * Botão de recarga atualizado para `+ Recarga`, abrindo modal flexível com seleção entre **Dinheiro Físico** (espécie somada à gaveta do caixa) e **Pix no Balcão** (transferência/chave confirmada pelo operador);
  * Backend (`SessaoCaixa::registrarRecargaBalcao` e `CaixaController::recargaDinheiro`) parametrizado para registrar créditos atômicos com os tipos oficiais (`recarga_dinheiro` ou `recarga_pix`), incrementando os totais de sessão respectivos (`total_dinheiro_recargas` ou `total_pix_balcao`) e gerando comprovante térmico personalizado em bobina 80mm;
  * Remoção do painel e tabela de histórico de sessões anteriores da tela operacional (`/operacional/caixa`), preservando a segregação de funções e restringindo a listagem histórica aos relatórios gerenciais/administrativos;
  * Testes automatizados `tests/caixa_test.php` (59 asserções) e `tests/recarga_dinheiro_post_test.php` (22 asserções) 100% aprovados.
* [x] **Retrocompatibilidade e Homologação Global:**
  * Preservação de todos os métodos legados em `PdfService.php` (`gerarRelatorioCorte`, `gerarTicket80mm`, `gerarCupomPDV80mm`, `gerarRelatorioDescontoFolha`, etc.) como wrappers sem quebra de contrato;
  * Criação da suíte oficial `tests/fase20_test.php` com 71 asserções executadas e 100% aprovadas;
  * Execução da auditoria global de verificação (`tests/verification_audit.php`): **49 de 49 suítes automatizadas aprovadas com 100% de sucesso**.

---

### [x] Fase 21 — Painel de Filtros Avançados e Relatório Oficial de Vendas por Período / Alimento
* [x] **Interface e Filtros Dinâmicos Estilo Escolar Manager:**
  * View `app/views/relatorios/vendas_filtros.php` implementada com todas as opções e toggles Sim/Não conforme `docs/alterações sistema guideline/Filtros de vendas.pdf`:
    * Toggle `agrupar_alimento` (Sim/Não) e ordenação por Mais Vendidos / Menos Vendidos;
    * Toggle `incluir_estornos` (Sim/Não) com tratamento analítico no faturamento líquido e identificação visual de itens estornados;
    * Toggle `agrupar_periodo` (Sim/Não) e `agrupar_tipo_pessoa` (Sim/Não);
    * Toggles de detalhamento: `emitir_observacao`, `emitir_forma_pagamento`, `emitir_horario`, `emitir_vendas_alimento`, `emitir_vendas_aluno`, `agrupar_vendas_aluno`, `agrupar_serie_turma`, `quebrar_pagina_turma` e ordenações por consumo do aluno;
    * Abas funcionais no topo ("Filtro & Parâmetros" e "Opções de Agrupamento");
    * Botões rápidos de período: "Hoje", "Ontem", "Este Mês" e "Mês Anterior";
    * Dropdown corporativo "Emitir" com opções "Visualizar / Imprimir (A4)", "Baixar em PDF (A4)" e "Exportar Planilha (CSV)".
* [x] **Motor de Agregação e Processamento no Backend:**
  * Métodos `vendasFiltros()`, `vendasFiltrosPdf()`, `vendasFiltrosImprimir()`, `vendasFiltrosCsv()`, `vendasAlimentoCsv()` e `obterDadosVendasAvancadas()` implementados no `app/controllers/RelatoriosController.php`;
  * Unificação de dados transacionais de pedidos agendados/entregues (`pedidos` + `itens_pedido`) e vendas avulsas de balcão (`vendas_balcao` + `itens_venda_balcao`);
  * Agrupamento diário com data formatada `dd/mm/aaaa`, consolidação de subtotais diários (`total_dia` / `subtotal`) e quantidade diária;
  * Classificação automática por Curva ABC (classes A, B e C) com base no faturamento acumulado e participação percentual;
  * Segregação por tipo de pessoa (Aluno, Funcionário, Consumidor Avulso) e consolidação nominal por aluno.
* [x] **Espelho A4 Oficial e Impressão Timbrada:**
  * View `app/views/relatorios/vendas_alimento_pdf.php` atualizada para espelhar fielmente o layout de `docs/alterações sistema guideline/relatorio de vendas por período.pdf`:
    * Cabeçalho escolar oficial com `COLÉGIO MONTEIRO LOBATO`, data/hora de emissão e `PERÍODO ENTRE DATAS : dd/mm/aaaa - dd/mm/aaaa`;
    * Tabela com colunas `Data`, `Alimento`, `Quantidade`, `Valor Unitário` e `Valor Total`;
    * Linhas divisórias de consolidação com `Total do dia : dd/mm/aaaa :` e `Total do período :`;
    * Simulação visual de folha A4 em tela (`max-width: 200mm; background: #ffffff; padding: 12mm 15mm; margin: 0 auto; box-shadow: ...`) sobre fundo cinza neutro;
    * Rodapé institucional limpo e autêntico: `Colégio Monteiro Lobato • Sistema Cantina Web` com numeração de página, sem referências a softwares terceiros nem tokens residuais;
    * Barra superior de controle com atalhos de `Voltar aos Filtros`, `Imprimir Documento` e `Fechar Janela`.
* [x] **Exportação para Planilha CSV:**
  * Suporte a exportação nativa em CSV com UTF-8 BOM (`\xEF\xBB\xBF`) e delimitador `;`, compatível com Microsoft Excel;
  * Cabeçalho padronizado e linha de consolidação geral no rodapé (`TOTAL DO PERÍODO`).
* [x] **Roteamento e Testes Automatizados:**
  * 5 novas rotas registradas em `index.php` protegidas por RBAC (`ROLE_ADMINISTRADOR`);
  * Suíte oficial `tests/fase21_test.php` expandida com **95 de 95 asserções aprovadas com 100% de sucesso**.

---

### [ ] Fase 22 — Extrato Analítico de Consumo do Aluno (Filtros, PDF A4 e Portal da Família)
* [ ] Tela de filtro com "Emitir apenas saldo do Aluno" e "Considerar saldo apenas no período" conforme `filtro Extrato Consumo Aluno.pdf`.
* [ ] View e emissão do PDF A4 "Extrato de Consumo do Aluno" com saldo progressivo linha a linha e total gasto conforme `Relatório Extrato Consumo Aluno.pdf`.
* [ ] Disponibilização do extrato analítico no Portal da Família (`app/views/portal/extrato.php`) com botão de download/impressão direta.
* [ ] Bateria de testes automatizados `tests/fase22_test.php`.

---

### [ ] Fase 23 — Relatório Gerencial de Movimentações de Estoque (Retrato e Paisagem)
* [ ] Tela de opções de leiaute com seleção de orientação Retrato/Paisagem e agrupamentos dinâmicos conforme `filtro movimentação estoque.pdf`.
* [ ] View e emissão do PDF A4 em orientações Retrato e Paisagem (`app/views/relatorios/estoque_movimentacoes_pdf.php`).
* [ ] Apuração consolidada de entradas, saídas e perdas justificadas com centro de custo único ("Cantina Geral").
* [ ] Bateria de testes automatizados `tests/fase23_test.php`.

---

### [ ] Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
* [ ] Migration 027 criando a tabela `despesas_operacionais`.
* [ ] CRUD administrativo de despesas operacionais (`app/controllers/DespesasController.php`).
* [ ] Motor de apuração contábil de DRE em `app/models/Dre.php` com apuração de Receita Bruta, Deduções, CMV automático, Lucro Bruto e Abatimento de Despesas conforme `Estrutura de uma DRE.pdf` e `Planilha_DRE_Modelo_Aethos_Sistemas.pdf`.
* [ ] Tela comparativa mensal do DRE com exportações em PDF e CSV.
* [ ] Painel com gráficos visuais interativos locais (`assets/js/chart.min.js` ou SVG nativo de alta densidade): evolução mensal, faturamento online vs presencial e produtos mais rentáveis.
* [ ] Bateria de testes automatizados `tests/fase24_test.php`.

---

## Próximo Passo Recomendado

Executar a **Fase 22 — Extrato Analítico de Consumo do Aluno (Filtros, PDF A4 e Portal da Família)**:
1. Construir a tela de filtro com opções "Emitir apenas saldo do Aluno" e "Considerar saldo apenas no período" conforme `filtro Extrato Consumo Aluno.pdf`;
2. Desenvolver a view e emissão do PDF A4 oficial "Extrato de Consumo do Aluno" com saldo progressivo linha a linha, créditos, débitos e total gasto conforme `Relatório Extrato Consumo Aluno.pdf`;
3. Disponibilizar o extrato analítico no Portal da Família (`app/views/portal/extrato.php`) com opções de visualização na tela e download do PDF A4;
4. Validar todas as regras de saldo, paginação e permissões familiares com a nova suíte `tests/fase22_test.php` e regressão global.







