# Plano de Construção Incremental — Cantina Web (Colégio Monteiro Lobato)

Este documento estabelece o roteiro de desenvolvimento em fases sequenciais, incrementais e auditáveis para a construção do sistema **Cantina Web — Colégio Monteiro Lobato**, rigorosamente estruturado a partir do `docs/FSD.md` e do `docs/DESIGN.md`.

---

## Visão Geral das Fases

```text
Fase 1  - Infraestrutura, Base do Projeto e Assets Locais
Fase 2  - Núcleo Arquitetural MVC e Mecanismo de Logs/Contingência
Fase 3  - Conexão de Banco de Dados e Migrações Estruturais/Cadastrais (001 a 012)
Fase 4  - Migrações Transacionais, Fiscais, Financeiras e Auditoria (013 a 026)
Fase 5  - Identidade Visual Institucional e Layouts Base (DESIGN.md)
Fase 6  - Autenticação, Sessão Segura e Controle de Acesso (RBAC)
Fase 7  - Configurações Globais e Cadastros Mestres com Soft Delete
Fase 8  - Importação Escolar em Lote e Gestão Ativa de Restrições Alimentares
Fase 9  - Carteira Digital Familiar e Gateway Pix Dinâmico
Fase 10 - Agendamento Semanal de Lanches e Regras Rígidas de Corte
Fase 11 - Operação de Encomendas e Gestão de Fornecedores
Fase 12 - Impressão e Gestão de Tickets Térmicos (Bobina 80mm)
Fase 13 - Ponto de Venda (PDV) e Operação de Balcão
Fase 14 - Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas
Fase 15 - Controle Financeiro e Fechamento de Caixas por Turno
Fase 16 - Tratamento de Contestações Bancárias e Mecanismo Pix MED
Fase 17 - Relatórios Gerenciais, Exportações (A4/CSV), Revisão de Segurança e Entrega
Fase 18 - Carga e Saneamento de Dados Oficiais da Escola (Matrículas Reais)
Fase 19 - Módulo Completo (CRUD) de Estorno de Vendas e Invalidação
Fase 20 - Nova Arquitetura de PDF (HTML+CSS+Gotenberg) & Migração Completa de Relatórios + Fechamento de Caixa
Fase 21 - Painel de Filtros Avançados e Relatório Oficial de Vendas por Período / Alimento
Fase 22 - Extrato Analítico de Consumo do Aluno (Filtros, PDF A4 e Portal da Família)
Fase 23 - Relatório Gerencial de Movimentações de Estoque (Retrato e Paisagem)
Fase 24 - Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos
```

---

## Fase 1 — Infraestrutura, Base do Projeto e Assets Locais

* **Objetivo:** Estabelecer a árvore oficial de diretórios, arquivo de entrada `index.php`, arquivos de configuração desacoplados sem `.env`, diretivas de proteção `.htaccess`, ambiente Docker local e assets estáticos (Bootstrap local, Design System e logotipo).
* **Checklist de Tarefas:**
  - [x] Criar estrutura de pastas (`config/`, `app/core/`, `app/controllers/`, `app/models/`, `app/views/`, `app/services/`, `database/migrations/`, `storage/logs/`, `storage/uploads/private/`, `storage/uploads/public/`, `assets/css/`, `assets/js/`, `assets/img/`).
  - [x] Criar `config/config.example.php` e `config/config.php` contendo parâmetros operacionais e de infraestrutura.
  - [x] Criar arquivos `.htaccess` com bloqueio absoluto nas pastas internas (`config/`, `app/`, `database/`, `storage/`) e regras de reescrita no `.htaccess` raiz.
  - [x] Configurar permissão restrita a imagens em `storage/uploads/public/.htaccess` com proibição de scripts.
  - [x] Copiar o logotipo oficial circular do colégio de `docs/logo-colegio.jpg` para `assets/img/logo-colegio.jpg`.
  - [x] Integrar Bootstrap compilado localmente em `assets/css/bootstrap.min.css` e `assets/js/bootstrap.bundle.min.js`.
  - [x] Criar folha de estilo customizada `assets/css/custom.css` com as diretrizes e variáveis do `docs/DESIGN.md`.
  - [x] Criar `assets/js/app.js` com funções base de interface e máscaras monetárias.
  - [x] Criar `docker-compose.yml` para orquestração de Apache, PHP 8.2 e MySQL 8.0.
  - [x] Criar `index.php` inicial com definição da constante `CANTINA_SISTEMA` e tela de verificação de ambiente.
  - [x] Configurar `.gitignore` para proteção de arquivos de log e uploads privados.
* **Critérios de Pronto:**
  - Aplicação executável localmente via `php -S` ou Docker.
  - Acesso direto via web a arquivos internos (`config/`, `app/`, etc.) categoricamente bloqueado por `.htaccess`.
  - Logotipo e Bootstrap servidos localmente sem dependência de internet/CDN.
* **Arquivos e Pastas Alterados:**
  - `index.php`, `config/*`, `assets/*`, `storage/*`, `database/runner.php`, `docker-compose.yml`, `.htaccess`, `.gitignore`.
* **Observações de Dependência:** Fase fundamental e pré-requisito para todo o restante do sistema.

---

## Fase 2 — Núcleo Arquitetural MVC e Mecanismo de Logs/Contingência

* **Objetivo:** Implementar o núcleo do framework interno do projeto em `app/core/`, com resolução de rotas, classes base de controllers, models com PDO e capturador global de erros com fallback em disco.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/core/Router.php` com suporte a verbos HTTP (GET, POST), rotas dinâmicas, middlewares e subpastas.
  - [ ] Implementar `app/core/Controller.php` com métodos auxiliares de renderização de Views (`render`), respostas JSON (`json`) e validação/sanitização de inputs.
  - [ ] Implementar `app/core/Model.php` fornecendo abstração para Prepared Statements via PDO, métodos de transação (`beginTransaction`, `commit`, `rollBack`) e helpers CRUD.
  - [ ] Implementar `app/core/Session.php` com controle de expiração por inatividade (timeout padrão de 60 min), regeneração de ID e geração/validação de tokens CSRF.
  - [ ] Implementar `app/core/ErrorHandler.php` capturando exceções e erros não tratados, com registro estruturado em banco e gravação obrigatória de contingência em `storage/logs/error.log`.
* **Critérios de Pronto:**
  - O roteador direciona requisições para controllers e views corretamente.
  - Sessão e tokens CSRF funcionais.
  - Falha intencional de conexão com o banco resulta em gravação auditável em `storage/logs/error.log`.
* **Arquivos e Pastas Alterados:**
  - `app/core/Router.php`, `app/core/Controller.php`, `app/core/Model.php`, `app/core/Session.php`, `app/core/ErrorHandler.php`, `storage/logs/error.log`.
* **Observações de Dependência:** Depende da Fase 1.

---

## Fase 3 — Conexão de Banco de Dados e Migrações Estruturais/Cadastrais (001 a 012)

* **Objetivo:** Estabelecer a fábrica singleton de conexão PDO em `config/database.php`, o controle de execução em `database/runner.php` e as 12 primeiras migrações com constraints e índices.
* **Checklist de Tarefas:**
  - [x] Implementar `config/database.php` (Singleton PDO com charset utf8mb4 e modo de exceção).
  - [x] Desenvolver `database/runner.php` para gerenciar a tabela `migrations_controle` e aplicar migrações de forma idempotente.
  - [x] Migration 001: Criação da tabela `migrations_controle`.
  - [x] Migration 002: Tabela `configuracoes_globais` (parâmetros da cantina e dados institucionais).
  - [x] Migration 003: Tabela `usuarios` (operadores e administradores da cantina com rastreabilidade).
  - [x] Migration 004: Tabela `responsaveis` (titulares das famílias, CPF, WhatsApp, bloqueio MED e termos).
  - [x] Migration 005: Tabela `turmas` (ano letivo, série, turma, turno com soft delete).
  - [x] Migration 006: Tabela `alunos` (matrícula, nome, turma_id, responsavel_id, turno com soft delete).
  - [x] Migration 007: Tabela `restricoes_alimentares` (catálogo oficial de alérgenos padronizados).
  - [x] Migration 008: Tabela associativa `aluno_restricoes` (relação N:N aluno e alérgeno).
  - [x] Migration 009: Tabela `colaboradores` (professores e funcionários para desconto em folha).
  - [x] Migration 010: Tabela `categorias` (classificação de alimentos com soft delete).
  - [x] Migration 011: Tabela `produtos` (código, nome, tipo salgado/industrializado, preço, estoque, pausa rápida).
  - [x] Migration 012: Tabela associativa `produto_alergenos` (alérgenos presentes nos alimentos).
* **Critérios de Pronto:**
  - Migrações 001 a 012 executadas com sucesso via `php database/runner.php`.
  - Chaves estrangeiras e índices cadastrais criados no MySQL.
* **Arquivos e Pastas Alterados:**
  - `config/database.php`, `database/runner.php`, `database/migrations/001_*.php` a `012_*.php`.
* **Observações de Dependência:** Depende das Fases 1 e 2.

---

## Fase 4 — Migrações Transacionais, Fiscais, Financeiras e Auditoria (013 a 026)

* **Objetivo:** Concluir o schema relacional criando as 14 migrações restantes para pedidos, tickets, caixas, vendas balcão, extrato da carteira, perdas, NF-e e logs.
* **Checklist de Tarefas:**
  - [ ] Migration 013: Tabela `notas_fiscais_xml` (cabeçalho de NF-e importadas).
  - [ ] Migration 014: Tabela `itens_nota_fiscal_xml` (itens da nota com memória de fator de conversão).
  - [ ] Migration 015: Tabela `estoque_movimentacoes` (entradas, saídas balcão e perdas com motivo).
  - [ ] Migration 016: Tabela `pedidos` (agendamentos de lanche, turno, valor, status com índices).
  - [ ] Migration 017: Tabela `itens_pedido` (produtos encomendados e valores unitários).
  - [ ] Migration 018: Tabela `tickets` (bobina térmica 80mm com tag de alérgeno e status ativo/impresso/estornado).
  - [ ] Migration 019: Tabela `sessoes_caixa` (abertura com troco, fechamentos 11:00/16:00 e quebra/sobra).
  - [ ] Migration 020: Tabela `vendas_balcao` (vendas avulsas e consumo de colaboradores).
  - [ ] Migration 021: Tabela `itens_venda_balcao` (detalhamento de itens vendidos no PDV).
  - [ ] Migration 022: Tabela `transacoes_carteira` (extrato financeiro familiar linha a linha com saldo).
  - [ ] Migration 023: Tabela `calendario_letivo` (dias úteis, recessos e cancelamentos em lote).
  - [ ] Migration 024: Tabela `logs_auditoria` (trilha de negócio para estornos, 2ª via e perdas).
  - [ ] Migration 025: Tabela `logs_erros` (registro estruturado de erros e exceções).
  - [ ] Migration 026: Tabela `logs_seguranca` (tentativas de login, bloqueios MED e acessos negados).
* **Critérios de Pronto:**
  - Todas as 26 migrações aplicadas no banco de dados sem erros.
  - Índices compostos de busca criados em `pedidos`, `tickets`, `sessoes_caixa`, `transacoes_carteira`.
* **Arquivos e Pastas Alterados:**
  - `database/migrations/013_*.php` a `026_*.php`.
* **Observações de Dependência:** Depende da Fase 3.

---

## Fase 5 — Identidade Visual Institucional e Layouts Base (DESIGN.md)

* **Objetivo:** Construir as estruturas visuais mestras do sistema em HTML5 e CSS/JS seguindo o Design System utilitário corporativo de alta densidade do Colégio Monteiro Lobato.
* **Checklist de Tarefas:**
  - [x] Criar layout mestre do Portal da Família (`app/views/layouts/portal.php`, `header.php`, `footer.php`).
  - [x] Criar layout mestre do Painel da Cantina com sidebar operacional (`app/views/layouts/admin.php`, `sidebar.php`).
  - [x] Desenvolver componentes parciais (`app/views/partials/alert.php`, `modal.php`, `empty_state.php`, `loading.php`, `stat_strip.php`).
  - [x] Garantir alinhamento de numerais e valores monetários com a classe `.font-tabular`.
  - [x] Implementar visualização da logo institucional circular (`assets/img/logo-colegio.jpg`) no topo e cabeçalhos.
* **Critérios de Pronto:**
  - Layouts responsivos e consistentes renderizados com o Bootstrap local e `custom.css`.
  - Conformidade visual com as cores institucional `#1d4ed8` (acento cobalto), `#15803d`, `#b91c1c`, `#92400e`.
* **Arquivos e Pastas Alterados:**
  - `app/views/layouts/*`, `app/views/partials/*`, `assets/css/custom.css`, `assets/js/app.js`, `app/controllers/ShowcaseController.php`, `app/views/showcase/*`.
* **Observações de Dependência:** Depende das Fases 1 e 2. Concluída com 74 asserções aprovadas.

---

## Fase 6 — Autenticação, Sessão Segura e Controle de Acesso (RBAC)

* **Objetivo:** Construir a autenticação segregada por perfil (Responsável vs Equipe da Cantina), fluxo de primeiro acesso com WhatsApp, termos de uso e middleware RBAC.
* **Checklist de Tarefas:**
  - [x] Implementar `app/core/Auth.php` e `app/core/RBAC.php` com as 4 permissões do FSD (Responsável, Operador, Administrador, Colaborador).
  - [x] Criar `app/models/Usuario.php` e `app/models/Responsavel.php`.
  - [x] Desenvolver `app/controllers/AuthController.php`.
  - [x] Construir telas de login (`app/views/auth/login.php`) com seletor de perfil e logo centralizado.
  - [x] Construir fluxo de primeiro acesso (`app/views/auth/primeiro_acesso_*.php`) com código WhatsApp de 6 dígitos.
  - [x] Construir modal e tela de aceite dos Termos e Políticas da Cantina com registro da data/hora e IP.
  - [x] Implementar fluxo de recuperação de senha via código temporário no WhatsApp.
  - [x] Proteger rotas contra acessos não autorizados gravando infrações em `logs_seguranca`.
* **Critérios de Pronto:**
  - Responsáveis acessam exclusivamente o Portal da Família.
  - Operadores e Administradores acessam o Painel Interno conforme seu nível de acesso.
  - Bloqueio por excesso de tentativas de senha com registro em auditoria.
* **Arquivos e Pastas Alterados:**
  - `app/core/Auth.php`, `app/core/RBAC.php`, `app/core/Session.php`, `app/controllers/AuthController.php`, `app/controllers/PortalController.php`, `app/controllers/AdminController.php`, `app/views/auth/*`, `app/views/portal/*`, `app/views/admin/*`, `app/models/Usuario.php`, `app/models/Responsavel.php`, `app/services/WhatsAppService.php`, `tests/fase6_test.php`.
* **Observações de Dependência:** Depende das Fases 2, 4 e 5. Concluída com 87 asserções aprovadas.

---

## Fase 7 — Configurações Globais e Cadastros Mestres com Soft Delete

* **Objetivo:** Implementar o módulo de administração para edição de parâmetros globais da cantina e gestão dos cadastros mestres com exclusão lógica (`deleted_at`) e auditoria técnica.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/models/Configuracao.php`, `Categoria.php`, `Produto.php`, `Colaborador.php`, `Turma.php`.
  - [ ] Criar `app/controllers/AdminController.php`.
  - [ ] Criar tela de Configurações Globais (`app/views/admin/configuracoes.php`): recarga mínima, saldo baixo, upload de logo e dados da cantina.
  - [ ] Criar telas de CRUD para Categorias e Produtos com controle de estoque e botão "Ativo/Pausado" (`app/views/admin/produtos.php`).
  - [ ] Criar telas de CRUD para Turmas e Colaboradores (com upload de foto e cargo para conferência no balcão).
  - [ ] Implementar soft delete garantindo integridade de registros passados.
* **Critérios de Pronto:**
  - Parâmetros da cantina salvos e aplicados globalmente.
  - Exclusão lógica não apaga fisicamente os registros e impede quebra de histórico financeiro.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/AdminController.php`, `app/models/*`, `app/views/admin/*`.
* **Observações de Dependência:** Depende da Fase 6.

---

## Fase 8 — Importação Escolar em Lote e Gestão Ativa de Restrições Alimentares

* **Objetivo:** Desenvolver o serviço de leitura de planilhas escolares (CSV/XLSX) para carga inicial de turmas/alunos e a gestão de restrições alimentares com alertas visuais.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/services/SpreadsheetService.php` com leitor nativo CSV em streaming e suporte a `.xlsx`.
  - [ ] Criar tela de importação em lote (`app/views/admin/importacao_escolar.php`) com conferência prévia e validação de duplicidade por CPF/matrícula.
  - [ ] Implementar `app/models/Aluno.php` e `app/models/RestricaoAlimentar.php`.
  - [ ] Criar tela de gestão de alérgenos do aluno no Portal da Família (`app/views/portal/alergias.php`).
  - [ ] Associar alérgenos padronizados (glúten, lactose, soja, etc.) aos produtos no catálogo.
* **Critérios de Pronto:**
  - Importação de planilha de exemplo popula turmas, alunos e responsáveis com sucesso.
  - Alérgenos associados aos dependentes e refletidos no perfil.
* **Arquivos e Pastas Alterados:**
  - `app/services/SpreadsheetService.php`, `app/controllers/AdminController.php`, `app/views/admin/importacao_escolar.php`, `app/views/portal/alergias.php`.
* **Observações de Dependência:** Depende das Fases 6 e 7.

---

## Fase 9 — Carteira Digital Familiar e Gateway Pix Dinâmico

* **Objetivo:** Construir a carteira familiar compartilhada entre irmãos, geração de Pix dinâmico via adapter, processador de Webhooks e atualização de saldo em tempo real.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/services/PixService.php` (geração de QR Code dinâmico, código Copia e Cola e validação de Webhooks).
  - [ ] Implementar `app/models/TransacaoCarteira.php` com cálculo dinâmico de saldo e travas contra saldo negativo não autorizado.
  - [ ] Criar `app/controllers/CarteiraController.php` e `app/controllers/WebhookController.php`.
  - [ ] Criar tela da Carteira Familiar (`app/views/portal/carteira.php`) com saldo disponível, valor em reserva para agendamentos e extrato analítico.
  - [ ] Criar tela de Recarga Pix com validação de valor mínimo (R$ 10,00) e timer de expiração.
  - [ ] Processar confirmação de pagamento via Webhook creditando o saldo instantaneamente e disparando notificação no WhatsApp (`app/services/WhatsAppService.php`).
* **Critérios de Pronto:**
  - Simulação de recarga Pix gera QR Code e Copia e Cola válidos.
  - Webhook de teste liquida a recarga e atualiza o saldo sem necessidade de recarregar a página.
* **Arquivos e Pastas Alterados:**
  - `app/services/PixService.php`, `app/services/WhatsAppService.php`, `app/controllers/CarteiraController.php`, `app/controllers/WebhookController.php`, `app/views/portal/carteira.php`.
* **Observações de Dependência:** Depende das Fases 6 e 7.

---

## Fase 10 — Agendamento Semanal de Lanches e Regras Rígidas de Corte

* **Objetivo:** Construir o módulo de encomendas semanais pelos pais com trava de corte pontual (08:00 e 13:30), compensação financeira em edições/cancelamentos e modal de alerta para alérgenos.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/models/Pedido.php` e `app/models/ItemPedido.php`.
  - [ ] Criar tela de Agendamento Semanal (`app/views/portal/agendamento.php`) com liberação às sextas 18:00 e produtos filtrados por turno do aluno.
  - [ ] Implementar trava de itens com estoque zerado (opacidade 0.5 e clique bloqueado).
  - [ ] Implementar modal amarelo de advertência para produtos incompatíveis com alergias cadastradas do estudante.
  - [ ] Aplicar trava rígida de corte operacional: bloqueio de agendamento, edição e cancelamento às 08:00 (matutino) e 13:30 (vespertino).
  - [ ] Desenvolver cancelamento com estorno automático de 100% para a carteira familiar.
  - [ ] Desenvolver edição de pedidos com compensação de saldo (débito/crédito da diferença) e liberação de reservas no estoque.
* **Critérios de Pronto:**
  - Pedido efetuado debita o saldo da carteira e reserva o estoque de industrializados.
  - Tentativa de cancelamento após o corte é recusada pelo sistema.
  - Conflito de alérgenos exibe modal de advertência com confirmação consciente.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/PortalController.php`, `app/models/Pedido.php`, `app/models/ItemPedido.php`, `app/views/portal/agendamento.php`, `app/views/portal/meus_pedidos.php`.
* **Observações de Dependência:** Depende das Fases 8 e 9.

---

## Fase 11 — Operação de Encomendas e Gestão de Fornecedores

* **Objetivo:** Desenvolver o painel de corte da cantina, consolidação matemática de salgados do turno, exportação estruturada para WhatsApp/PDF e recebimento com liquidação de demanda.
* **Checklist de Tarefas:**
  - [ ] Criar `app/controllers/PedidosController.php`.
  - [ ] Construir Painel de Corte do Turno (`app/views/operacional/corte.php`) exibindo somatório por tipo de salgado.
  - [ ] Implementar ação "Copiar para WhatsApp" com mensagem formatada para fornecedores parceiros.
  - [ ] Gerar relatório consolidado de compras em PDF A4 timbrado via `app/services/PdfService.php`.
  - [ ] Criar tela de Conferência e Recebimento de Mercadorias (`app/views/operacional/recebimento.php`).
  - [ ] Ação de confirmação de entrega: entrada física no estoque e liquidação simultânea da demanda de tickets impressos.
* **Critérios de Pronto:**
  - Consolidação quantitativa exata das demandas de salgados pontualmente às 08:00 e 13:30.
  - Recebimento físico liquida as encomendas e adiciona eventuais sobras à venda avulsa.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/PedidosController.php`, `app/views/operacional/corte.php`, `app/views/operacional/recebimento.php`, `app/services/PdfService.php`.
* **Observações de Dependência:** Depende da Fase 10.

---

## Fase 12 — Impressão e Gestão de Tickets Térmicos (Bobina 80mm)

* **Objetivo:** Desenvolver o módulo de emissão contínua de tickets térmicos para bobina de 80mm, ordenados por turma/série, com tag indelével de alérgenos e controle de 2ª via com auditoria.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/models/Ticket.php`.
  - [ ] Criar `app/controllers/TicketsController.php`.
  - [ ] Criar layout específico de impressão contínua em bobina térmica de 80mm em HTML/CSS e PDF (`app/views/operacional/tickets_lote.php`).
  - [ ] Ordenar cupons sequencialmente por Série e Turma para entrega facilitada aos professores.
  - [ ] Incluir tag obrigatória `[ATENÇÃO: CONTÉM ...]` caso o aluno possua restrição alimentar incompatível.
  - [ ] Desenvolver tela de consulta e busca rápida por aluno/turma com emissão de 2ª via marcada com `[2ª VIA]` e registro obrigatório em `logs_auditoria` com justificativa.
* **Critérios de Pronto:**
  - Lotes de cupons formatados para impressão em bobina de 80mm sem corte indevido de linhas.
  - Reimpressão de cupom gera log obrigatório de auditoria associado ao operador logado.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/TicketsController.php`, `app/models/Ticket.php`, `app/views/operacional/tickets_*.php`.
* **Observações de Dependência:** Depende das Fases 10 e 11.

---

## Fase 13 — Ponto de Venda (PDV) e Operação de Balcão

* **Objetivo:** Construir o PDV ágil para comercialização de sobras e avulsos em dinheiro ou Pix balcão, lançamento de consumo de colaboradores sob desconto em folha e estornos manuais.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/models/VendaBalcao.php` e `app/models/ItemVendaBalcao.php`.
  - [ ] Criar `app/controllers/PdvController.php`.
  - [ ] Construir tela de PDV de Balcão (`app/views/operacional/pdv.php`) otimizada para teclado (atalhos Tab, Enter, Esc).
  - [ ] Implementar venda avulsa em dinheiro físico (com cálculo automático de troco) validando caixa aberto.
  - [ ] Implementar venda avulsa em Pix balcão com QR Code dinâmico.
  - [ ] Implementar busca de colaboradores por matrícula/nome, exibição de foto e cargo em tela e gravação com status de débito em folha.
  - [ ] Implementar estorno manual de tickets no balcão com crédito automático na carteira familiar e registro de motivo em `logs_auditoria`.
  - [ ] Implementar transferência de sobras de alunos ausentes para a venda avulsa ao término do intervalo.
* **Critérios de Pronto:**
  - Vendas avulsas registradas com baixa no estoque e no caixa do turno.
  - Consumo de colaborador exige confirmação visual de foto antes de registrar débito.
  - Estorno de balcão credita o saldo da família no mesmo instante.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/PdvController.php`, `app/models/VendaBalcao.php`, `app/views/operacional/pdv.php`.
* **Observações de Dependência:** Depende das Fases 7, 9 e 12.

---

## Fase 14 — Gestão de Estoque, Importação de XML de NF-e e Registro de Perdas

* **Objetivo:** Desenvolver o controle de saldos físicos de estoque, importação de notas fiscais XML com memória de cálculo de conversão de unidades e baixa manual de perdas com motivo.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/services/XmlParserService.php` utilizando `SimpleXML` / `DOMDocument`.
  - [ ] Implementar `app/models/Estoque.php`, `NotaFiscal.php` e `ItemNotaFiscal.php`.
  - [ ] Criar `app/controllers/EstoqueController.php`.
  - [ ] Construir Painel de Estoque (`app/views/estoque/index.php`) com saldos físicos, reservas online e alerta de estoque mínimo.
  - [ ] Criar tela de importação de XML de NF-e (`app/views/estoque/importar_xml.php`) com vinculação de produtos internos e fator de conversão de embalagens em unidades.
  - [ ] Salvar memória de cálculo de conversão na tabela `itens_nota_fiscal_xml`.
  - [ ] Criar tela de lançamento de perdas (`app/views/estoque/perdas.php`) exigindo seleção de motivo obrigatório (*Avaria*, *Vencimento*, *Sobra*, *Ajuste*) com auditoria.
* **Critérios de Pronto:**
  - Importação de NF-e XML converte caixas em unidades e incrementa o estoque físico.
  - Baixas de perda gravadas com justificativa e operador logado.
* **Arquivos e Pastas Alterados:**
  - `app/services/XmlParserService.php`, `app/controllers/EstoqueController.php`, `app/models/Estoque.php`, `app/views/estoque/*`.
* **Observações de Dependência:** Depende das Fases 7 e 13.

---

## Fase 15 — Controle Financeiro e Fechamento de Caixas por Turno

* **Objetivo:** Implementar o controle de sessões de caixa por turno (abertura com troco, fechamento matutino 11:00 e vespertino 16:00) e conciliação entre dinheiro e Pix.
* **Checklist de Tarefas:**
  - [ ] Implementar `app/models/SessaoCaixa.php`.
  - [ ] Criar `app/controllers/CaixaController.php`.
  - [ ] Criar tela de Abertura de Caixa (`app/views/operacional/abertura_caixa.php`) com registro do Fundo de Troco.
  - [ ] Bloquear operações financeiras em espécie se a sessão de caixa do turno estiver fechada.
  - [ ] Criar tela de Fechamento de Caixa (`app/views/operacional/fechamento_caixa.php`) com conferência cega/assistida dos valores contados.
  - [ ] Apuração e registro de sobras ou faltas de caixa (quebra de caixa).
  - [ ] Geração do espelho de fechamento de caixa em PDF A4 timbrado para assinatura do operador e gerente.
* **Critérios de Pronto:**
  - Caixas segregados por turno (matutino fecha às 11:00, vespertino fecha às 16:00).
  - Confronto correto entre valores contados e sistema (troco, recargas em dinheiro, vendas avulsas e Pix).
* **Arquivos e Pastas Alterados:**
  - `app/controllers/CaixaController.php`, `app/models/SessaoCaixa.php`, `app/views/operacional/caixa_*.php`.
* **Observações de Dependência:** Depende das Fases 9, 13 e 14.

---

## Fase 16 — Tratamento de Contestações Bancárias e Mecanismo Pix MED

* **Objetivo:** Implementar o tratamento contra contestações bancárias e fraudes de Pix (MED), com débito na carteira familiar, bloqueio automático de pedidos se saldo negativo e aviso via WhatsApp.
* **Checklist de Tarefas:**
  - [ ] Atualizar `app/controllers/WebhookController.php` para receber notificações de contestação bancária / devolução MED.
  - [ ] Desenvolver rotina de estorno: débito imediato na carteira familiar e registro de transação com tipo `estorno_med`.
  - [ ] Se o saldo da família ficar negativo: aplicar bloqueio automático de compras para todos os filhos vinculados.
  - [ ] Disparar mensagem automática de advertência via WhatsApp comunicando a suspensão preventiva da conta.
  - [ ] Criar interface no painel administrativo para registro e quitação manual de contestações pelo Gerente.
* **Critérios de Pronto:**
  - Simulação de evento MED debita o saldo da família e bloqueia agendamentos de todos os dependentes.
  - Notificação de advertência enviada via WhatsApp.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/WebhookController.php`, `app/models/TransacaoCarteira.php`, `app/models/Responsavel.php`, `app/views/admin/contestações.php`.
* **Observações de Dependência:** Depende das Fases 9, 10 e 15.

---

## Fase 17 — Relatórios Gerenciais, Exportações (A4/CSV), Revisão de Segurança e Entrega

* **Objetivo:** Implementar os relatórios analíticos, exportações em PDF timbrado A4 e planilhas CSV para o RH, revisão completa de conformidade com o FSD e DESIGN.md e validação de entrega.
* **Checklist de Tarefas:**
  - [ ] Criar `app/controllers/RelatoriosController.php`.
  - [ ] Desenvolver Relatório de Extrato de Consumo do Aluno em PDF A4 timbrado com logo oficial do colégio.
  - [ ] Desenvolver Relatório de Vendas por Alimento (filtros por período, turno e categoria).
  - [ ] Desenvolver Relatório Mensal de Consumo de Colaboradores (Desconto em Folha) em PDF e planilha CSV para o RH.
  - [ ] Desenvolver Relatório de Famílias com Saldo Devedor.
  - [ ] Auditoria de segurança final: verificação contra SQL Injection, XSS, CSRF, permissões RBAC e proteção de rotas.
  - [ ] Verificação visual completa de aderência ao `docs/DESIGN.md` (botões, densidade, fontes `tnum`, estados vazios e alertas).
* **Critérios de Pronto:**
  - Relatórios em PDF gerados com layout A4 timbrado e dados da instituição.
  - Planilha CSV de desconto em folha gerada e validada para importação contábil.
  - Todos os 26 critérios de aceitação do `docs/FSD.md` cumpridos integralmente.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/RelatoriosController.php`, `app/views/relatorios/*`, `app/services/PdfService.php`.
* **Observações de Dependência:** Depende de todas as fases anteriores (Fases 1 a 16).

---

## Fase 18 — Carga e Saneamento de Dados Oficiais da Escola (Matrículas Reais)

* **Objetivo:** Importar e sanear toda a base real de alunos, turmas e responsáveis do Colégio Monteiro Lobato a partir do documento oficial `docs/alterações sistema guideline/Matriculas.pdf` (23 páginas), substituindo os dados de teste/demonstração.
* **Checklist de Tarefas:**
  - [ ] Implementar script de extração/parser dos dados de `docs/alterações sistema guideline/Matriculas.pdf`.
  - [ ] Criar seeder oficial `database/seed_matriculas_reais.php` com suporte a idempotência e preservação de usuários administrativos.
  - [ ] Cadastrar as turmas escolares reais por turno (Maternal, Jardins, Ensino Fundamental I e II, Ensino Médio).
  - [ ] Cadastrar os ~197 responsáveis reais (financeiros e pedagógicos) com CPF limpo, telefones formatados e endereços.
  - [ ] Cadastrar os ~232 alunos reais com matrícula oficial, vínculo ao responsável e à turma correspondente.
  - [ ] Validar a integridade referencial, constraints e unicidade de matrícula e CPF.
* **Critérios de Pronto:**
  - Banco de dados de desenvolvimento populado com 100% dos alunos, turmas e responsáveis reais da escola.
  - Testes de integridade aprovados sem falhas de chaves estrangeiras.
* **Arquivos e Pastas Alterados:**
  - `database/seed_matriculas_reais.php`, `tests/fase18_test.php`.
* **Observações de Dependência:** Depende da Fase 3 e Fase 7.

---

## Fase 19 — Módulo Completo (CRUD) de Estorno de Vendas e Invalidação

* **Objetivo:** Construir a interface operacional completa de estorno de vendas da cantina conforme o modelo de `docs/alterações sistema guideline/extorno de vendas.pdf`, com reversão financeira (carteira/caixa), reintegração automática de estoque e invalidação de tickets térmicos.
* **Checklist de Tarefas:**
  - [ ] Criar rota `/operacional/estornos` e controller `app/controllers/EstornoVendasController.php`.
  - [ ] Construir view `app/views/operacional/estorno_vendas.php` com filtros: Venda avulsa (Sim/Não), busca por Pessoa (Aluno/Colaborador), período (De/Até) e campo obrigatório de Justificativa/Motivo do estorno.
  - [ ] Construir tabela de seleção múltipla com checkboxes (Data, Hora, Valor Total, Pessoa, Detalhes).
  - [ ] Implementar rotina transacional PDO atômica de gravação de estornos em lote:
    - Se venda debitada em saldo: devolução do crédito na carteira da família com registro em `transacoes_carteira`;
    - Se venda em dinheiro no balcão: registro de cancelamento/saída no caixa do turno atual;
    - Reintegração automática das quantidades de itens ao estoque físico em `estoque_movimentacoes`;
    - Invalidação/cancelamento de tickets térmicos impressos correspondentes;
    - Auditoria em `logs_auditoria` com operador logado, motivo, IP e data/hora.
* **Critérios de Pronto:**
  - O operador consegue pesquisar vendas por aluno/data, selecionar múltiplos itens e confirmar estorno com 1 clique.
  - Saldo, caixa, estoque e tickets são atualizados atomicamente com registro de auditoria.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/EstornoVendasController.php`, `app/views/operacional/estorno_vendas.php`, `app/models/VendaBalcao.php`, `app/models/Ticket.php`, `app/models/Estoque.php`, `tests/fase19_test.php`.
* **Observações de Dependência:** Depende das Fases 12, 13, 14 e 15.

---

## Fase 20 — Nova Arquitetura de PDF (HTML+CSS+Gotenberg) & Migração Completa de Relatórios + Fechamento de Caixa

* **Objetivo:** Migrar 100% da geração de PDFs do sistema (relatórios antigos e novos) para a arquitetura limpa de Views HTML + CSS com motor Chromium via Gotenberg (Docker/API) e fallback para `@media print` no navegador, implementando o novo espelho A4 oficial de Fechamento de Caixa conforme `docs/alterações sistema guideline/28 setembro matutino.pdf` e `28 setembro vespertino.pdf`.
* **Checklist de Tarefas:**
  - [ ] Adicionar container do Gotenberg (`gotenberg/gotenberg:8`) em `docker-compose.yml` (porta 3000).
  - [ ] Adicionar parâmetros de configuração de PDF e Gotenberg em `config/config.php` e `config/config.example.php`.
  - [ ] Refatorar radicalmente `app/services/PdfService.php` para eliminar as ~6.000 linhas de bytecode de baixo nível, transformando-o em um cliente enxuto e robusto:
    - Renderizador de views HTML com dados injetados;
    - Cliente cURL para a API do Gotenberg (`/forms/chromium/convert/html`);
    - Fallback automático para modo impressão no navegador (`window.print()`).
  - [ ] Migrar todos os relatórios e documentos existentes para views HTML limpas em `app/views/relatorios/` e `app/views/tickets/`:
    - Relatório de Corte do Turno (A4);
    - Cupom Térmico de Balcão (80mm);
    - Tickets de Lanche de Alunos com Alérgenos (80mm);
    - Extrato de Carteira e Desconto em Folha para RH.
  - [ ] Construir a view HTML do novo espelho oficial A4 de Fechamento de Caixa (`app/views/relatorios/fechamento_caixa_pdf.php`) com as 4 seções escolares:
    1. Duplicatas / Recargas (Aluno, Matrícula, Vencimento, Valor, Meio de Pagamento);
    2. Vendas (Itens consumidos com segregação explícita entre Vendas Online vs Vendas Balcão, e funcionários unificados);
    3. Recebimento Pix de conferência bancária;
    4. Resumo Contábil do Caixa (Fundo de Troco, Entradas por espécie, Saídas e Saldo de Caixa).
* **Critérios de Pronto:**
  - `PdfService.php` reduzido a um serviço enxuto e desacoplado.
  - Todos os documentos gerados em alta fidelidade via Gotenberg e via tela de impressão.
  - Espelho de fechamento de caixa A4 emitido com as 4 seções exatas das referências reais.
* **Arquivos e Pastas Alterados:**
  - `docker-compose.yml`, `config/config.php`, `config/config.example.php`, `app/services/PdfService.php`, `app/views/relatorios/*`, `app/views/tickets/*`, `tests/fase20_test.php`.
* **Observações de Dependência:** Depende das Fases 11, 12, 13 e 15.

---

## Fase 21 — Painel de Filtros Avançados e Relatório Oficial de Vendas por Período / Alimento

* **Objetivo:** Implementar o painel interativo de parametrização dinâmica de vendas e o gerador de PDF oficial de vendas por produto consolidando pedidos online e presenciais conforme `docs/alterações sistema guideline/Filtros de vendas.pdf` e `relatorio de vendas por período.pdf`.
* **Checklist de Tarefas:**
  - [ ] Construir interface de filtros de vendas em `app/views/relatorios/vendas_filtros.php` com toggles corporativos (Sim/Não):
    - Emitir observação / forma de pagamento / incluir estorno;
    - Agrupar por tipo de pessoa (Aluno, Funcionário);
    - Agrupar por período / emitir horário da venda;
    - Emitir vendas por alimento (agrupamento e ordenação por mais/menos vendidos);
    - Emitir vendas por aluno (agrupamento e quebra de página por série/turma e ordenação por consumo);
  - [ ] Implementar no `app/controllers/RelatoriosController.php` o processamento dos filtros dinâmicos.
  - [ ] Criar view HTML `app/views/relatorios/vendas_alimento_pdf.php` seguindo fielmente o layout de `relatorio de vendas por período.pdf` (cabeçalho institucional, período, Data, Alimento, Quantidade, Preço Unitário, Valor Total e totalizadores).
  - [ ] Suporte à exportação dos dados filtrados para planilha CSV.
* **Critérios de Pronto:**
  - A cantina consegue aplicar qualquer combinação de filtros e emitir o PDF oficial de vendas consolidado ou baixar a planilha CSV.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/RelatoriosController.php`, `app/views/relatorios/vendas_filtros.php`, `app/views/relatorios/vendas_alimento_pdf.php`, `tests/fase21_test.php`.
* **Observações de Dependência:** Depende da Fase 20.

---

## Fase 22 — Extrato Analítico de Consumo do Aluno (Filtros, PDF A4 e Portal da Família)

* **Objetivo:** Implementar a tela de filtro de extrato e a geração do PDF oficial com saldo progressivo para a administração e para o Portal da Família, conforme `docs/alterações sistema guideline/filtro Extrato Consumo Aluno.pdf` e `Relatório Extrato Consumo Aluno.pdf`.
* **Checklist de Tarefas:**
  - [ ] Criar tela de filtro `app/views/relatorios/extrato_aluno_filtro.php` com as opções:
    - "Emitir apenas saldo do Aluno" (Sim/Não);
    - "Considerar saldo apenas no período" (Sim/Não).
  - [ ] Desenvolver view HTML do extrato A4 `app/views/relatorios/extrato_aluno_pdf.php`:
    - Cabeçalho: Matrícula, Aluno, Série/Turma, Saldo Atual;
    - Saldo inicial anterior ao período;
    - Linha a linha com Data, Descrição (alimento ou recarga Pix), Quantidade, Valor Unitário, Valor Total, Forma de Pagamento e coluna de Saldo progressivo;
    - Rodapé com "Total gasto no período".
  - [ ] Adicionar botão direto de "Baixar Extrato Oficial (PDF)" no Portal da Família (`app/views/portal/extrato.php`).
* **Critérios de Pronto:**
  - PDF emitido com saldo progressivo idêntico à referência escolar.
  - Pais conseguem baixar o extrato em PDF diretamente pelo portal do responsável.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/RelatoriosController.php`, `app/controllers/PortalController.php`, `app/views/relatorios/extrato_aluno_*.php`, `app/views/portal/extrato.php`, `tests/fase22_test.php`.
* **Observações de Dependência:** Depende das Fases 9 e 20.

---

## Fase 23 — Relatório Gerencial de Movimentações de Estoque (Retrato e Paisagem)

* **Objetivo:** Desenvolver o gerador de relatórios de estoque com parâmetros de leiaute (Retrato/Paisagem) e agrupamentos conforme `docs/alterações sistema guideline/filtro movimentação estoque.pdf`, operando com centro de custo único ("Cantina Geral").
* **Checklist de Tarefas:**
  - [ ] Construir tela de opções de leiaute `app/views/relatorios/estoque_filtros.php`:
    - Formato do leiaute: Paisagem / Retrato;
    - Agrupamento por grupo de produtos (Mostrar/Ocultar);
    - Agrupamento por produto (Mostrar/Ocultar);
    - Quebra de página (Mostrar/Ocultar).
  - [ ] Criar view HTML `app/views/relatorios/estoque_movimentacoes_pdf.php` com suporte a orientações Retrato e Paisagem.
  - [ ] Demonstrar analiticamente todas as entradas (fornecedores/XML), saídas (pedidos online e balcão) e baixas de perdas justificadas com posição de saldo físico.
* **Critérios de Pronto:**
  - Relatório gerado com perfeição nas orientações Paisagem e Retrato com agrupamentos dinâmicos.
* **Arquivos e Pastas Alterados:**
  - `app/controllers/RelatoriosController.php`, `app/views/relatorios/estoque_filtros.php`, `app/views/relatorios/estoque_movimentacoes_pdf.php`, `tests/fase23_test.php`.
* **Observações de Dependência:** Depende das Fases 14 e 20.

---

## Fase 24 — Módulo Financeiro de Despesas, DRE Gerencial Completo e Painel de Gráficos Interativos

* **Objetivo:** Implementar o módulo financeiro de despesas operacionais, motor de apuração de DRE comparativo mês a mês e painel com gráficos visuais no dashboard e no relatório, conforme `docs/alterações sistema guideline/Estrutura de uma DRE.pdf` e `Planilha_DRE_Modelo_Aethos_Sistemas.pdf`.
* **Checklist de Tarefas:**
  - [ ] Migration 027: Criação da tabela `despesas_operacionais` (categoria: administrativa, pessoal, financeira, manutenção; descrição; valor; competência_mes_ano; data_vencimento; data_pagamento; status; soft delete).
  - [ ] Criar CRUD administrativo de Despesas Operacionais (`app/controllers/DespesasController.php` e views associadas).
  - [ ] Implementar motor de DRE gerencial em `app/models/Dre.php`:
    - (+) Receita Bruta de Vendas (Vendas Online + Vendas Balcão);
    - (-) Deduções e Abatimentos (Estornos e taxas de meios de pagamento);
    - (=) Receita Líquida;
    - (-) Custo das Mercadorias Vendidas (CMV) / Insumos;
    - (=) Lucro Bruto;
    - (-) Despesas Operacionais (Administrativas, Financeiras, Manutenção, etc.);
    - (=) Resultado Líquido do Exercício (Lucro Líquido Real).
  - [ ] Construir tela comparativa de DRE mês a mês no estilo da planilha modelo Aethos (`app/views/financeiro/dre.php`) com exportação para PDF e CSV.
  - [ ] Integrar biblioteca de gráficos servida estritamente de arquivo local (`assets/js/chart.min.js` ou SVG nativo de alta densidade sem dependências de rede):
    - Gráfico de linha de evolução mensal (Receitas vs Despesas vs Lucro Líquido);
    - Gráfico de pizza/barra destacando a proporção de Vendas Online (foco principal) vs Presenciais;
    - Gráfico de curva de produtos mais lucrativos.
* **Critérios de Pronto:**
  - Gestor consegue lançar contas/despesas e visualizar o DRE consolidado mês a mês com apuração automática de CMV e lucro líquido real.
  - Dashboard gerencial enriquecido com gráficos interativos locais de alta fidelidade visual.
* **Arquivos e Pastas Alterados:**
  - `database/migrations/027_create_despesas_operacionais_table.php`, `app/models/DespesaOperacional.php`, `app/models/Dre.php`, `app/controllers/DespesasController.php`, `app/controllers/DreController.php`, `app/views/financeiro/*`, `assets/js/chart.min.js`, `tests/fase24_test.php`.
* **Observações de Dependência:** Depende de todas as fases anteriores (Fases 1 a 23).

