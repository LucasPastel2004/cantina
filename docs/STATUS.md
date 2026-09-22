# Status do Projeto — Cantina Web (Colégio Monteiro Lobato)

Este documento reflete o estado em tempo real da implementação do sistema **Cantina Web — Colégio Monteiro Lobato**, registrando o progresso fase a fase conforme o `docs/PLANO.md`.

---

## Resumo Geral

* **Fase Atual:** Fase 13 — Ponto de Venda (PDV) e Operação de Balcão
* **Status da Fase 13:** Concluída com Sucesso [x]
* **Próxima Fase:** Fase 14 — Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas
* **Última Atualização:** 2026-09-22 09:45:00 (Fase 13 finalizada, testada e validada)

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

Iniciar a **Fase 14 — Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas**, desenvolvendo o importador de XML fiscal de notas de entrada (com associação de itens e fator multiplicador para conversão de caixas/fardos em unidades individuais para venda) e a rotina compulsória de registro de perdas operacionais por avaria, vencimento ou ajuste de inventário com auditoria.




