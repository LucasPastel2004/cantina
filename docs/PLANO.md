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
  - [ ] Implementar `app/core/Auth.php` e `app/core/RBAC.php` com as 4 permissões do FSD (Responsável, Operador, Administrador, Colaborador).
  - [ ] Criar `app/models/Usuario.php` e `app/models/Responsavel.php`.
  - [ ] Desenvolver `app/controllers/AuthController.php`.
  - [ ] Construir telas de login (`app/views/auth/login.php`) com seletor de perfil e logo centralizado.
  - [ ] Construir fluxo de primeiro acesso (`app/views/auth/primeiro_acesso.php`) com código WhatsApp de 6 dígitos.
  - [ ] Construir modal de aceite dos Termos e Políticas da Cantina com registro da data/hora e IP.
  - [ ] Implementar fluxo de recuperação de senha via código temporário no WhatsApp.
  - [ ] Proteger rotas contra acessos não autorizados gravando infrações em `logs_seguranca`.
* **Critérios de Pronto:**
  - Responsáveis acessam exclusivamente o Portal da Família.
  - Operadores e Administradores acessam o Painel Interno conforme seu nível de acesso.
  - Bloqueio por excesso de tentativas de senha com registro em auditoria.
* **Arquivos e Pastas Alterados:**
  - `app/core/Auth.php`, `app/core/RBAC.php`, `app/controllers/AuthController.php`, `app/views/auth/*`, `app/models/Usuario.php`, `app/models/Responsavel.php`.
* **Observações de Dependência:** Depende das Fases 2, 4 e 5.

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
