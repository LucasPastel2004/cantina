# **DECISÕES TÉCNICAS DO PROJETO**

## **1\. Documentos recebidos**

> * **PRD.md**: Recebido e analisado integralmente. O documento especifica as regras de negócio, os atores, a rotina de balcão, a consolidação de compras, a gestão de estoque com XML e o modelo financeiro de carteira familiar pré-paga com proteção contra contestações.  
> * **DESIGN.md**: Recebido e analisado integralmente. Define os padrões visuais utilitários corporativos de alta densidade (Enterprise ERP/POS), a paleta de cores institucional baseada no Colégio Monteiro Lobato, a tipografia tabular (tnum), a padronização de botões, formulários, tabelas operacionais e layouts de impressão térmica e A4.  
> * **Observações**: O escopo funcional do PRD está em conformidade direta com os padrões de interface do Design System, oferecendo base sólida para a especificação do FSD.

## **2\. Identificação do sistema**

> * **Nome do sistema**: Cantina Web — Colégio Monteiro Lobato.  
> * **Objetivo principal**: Digitalizar, antecipar e auditar todo o ciclo de encomenda, cobrança e entrega de lanches escolares, eliminando filas nos intervalos por meio do atendimento baseado em tickets físicos de papel, assegurando precisão na compra diária de salgados e viabilizando o controle nutricional e financeiro pelas famílias.  
> * **Público usuário**: Pais e responsáveis pelos alunos, atendentes e operadores de balcão, administradores da cantina e colaboradores da instituição escolar.  
> * **Contexto de uso**: Escola particular de unidade física única com dois turnos operacionais (matutino e vespertino), operando em terminal único de balcão na cantina e com acesso externo web pelos responsáveis.  
> * **Resumo funcional**: O sistema opera dividido entre o **Portal da Família** (carteira virtual com Pix dinâmico, agendamento semanal com horário de corte pontual às 08:00 e 13:30, gestão de restrições alimentares e extrato de consumo) e o **Painel Operacional/Administrativo** (consolidação de compras de salgados, impressão em lote de tickets térmicos por turma, PDV para avulsos e consumo em folha de pagamento, controle de estoque contínuo e diário com entrada via XML, gestão de perdas e fechamento de caixa por turno às 11:00 e 16:00).

## **3\. Decisões técnicas confirmadas**

> * **Stack tecnológica**: Backend em PHP com organização baseada em MVC, banco de dados relacional MySQL, interface web em HTML5, CSS3, JavaScript puro (Vanilla JS) e Bootstrap local.  
> * **Ambientes**:  
  * *Desenvolvimento local*: Docker e Docker Compose, orquestrando contêineres para servidor web, PHP e MySQL.  
  * *Testes e homologação*: Não haverá ambiente formal intermediário na V1; testes serão realizados no ambiente Docker local antes da publicação.  
  * *Produção*: Hospedagem compartilhada gerenciada com PHP e MySQL na Hostnet.  
> * **Arquitetura**: Padrão Model-View-Controller (MVC) com separação de responsabilidades e camada intermediária de serviços (*Service / Gateway Adapters*) para integrações externas.  
> * **Autenticação**:  
  * *Responsáveis*: Acesso por CPF e senha com verificação em duas etapas via código transacional no WhatsApp no primeiro acesso e em redefinições de senha.  
  * *Equipe da cantina*: Acesso por identificador nominal no formato operador-\[nome\] (ex: operador-carlos, onde o nome do atendente substitui os asteriscos) e senha criptografada.  
> * **Usuários e permissões**: Controle de acesso baseado em papéis (RBAC) com 4 perfis: Responsável, Operador/Atendente, Administrador/Gerente e Colaborador/Professor (perfil passivo no balcão).  
> * **Auditoria**: Auditoria técnica com carimbos temporais (created\_at, created\_by, updated\_at, updated\_by) nos cadastros mestres e trilha de auditoria de negócio para operações sensíveis de balcão (estornos manuais, reimpressão de 2ª via e lançamentos de perdas de estoque com justificativa obrigatória).  
> * **Soft delete**: Exclusão lógica (deleted\_at) em todos os cadastros e registros principais (produtos, categorias, colaboradores, operadores e turmas), garantindo a integridade dos relatórios contábeis, extratos e caixas passados.  
> * **Logs técnicos e operacionais**: Log estruturado de erros com mecanismo de contingência em arquivo de texto fora da raiz pública da web; log de segurança para eventos de força bruta, acessos negados por RBAC e bloqueios por contestação MED.  
> * **Configurações globais**: Painel administrativo para parametrização de dados institucionais da cantina, upload de logotipo, valor mínimo de recarga Pix (padrão R$ 10,00), gatilho de alerta de saldo baixo (padrão R$ 15,00), tempo limite de inatividade da sessão (60 minutos), limite de tentativas incorretas de login (5 tentativas) e paginação padrão (25 itens por página).  
> * **Uploads e arquivos**: Suporte a upload de planilhas escolares (.xlsx, .csv), notas fiscais (.xml) e imagens de produtos/logo (.jpg, .jpeg, .png, .webp) com limite de até 10 MB por arquivo, validação rigorosa de MIME type no servidor e armazenamento seguro de dados fiscais/escolares fora do diretório público.  
> * **Relatórios e exportações**: Emissão em PDF timbrado para extrato de consumo do aluno, vendas por produto, fechamento de turno, lista de fornecedores e negativados; exportação em PDF e CSV/Excel para fechamento de consumo de colaboradores (RH); cópia estruturada para WhatsApp; e emissão contínua em impressoras térmicas (bobina 80mm).  
> * **APIs e integrações**: Gateway de pagamento Pix dinâmico com webhook para liquidação imediata e tratamento de contestações bancárias (MED); API REST para envio automático de mensagens transacionais via WhatsApp.  
> * **Segurança da aplicação**: Proteção contra SQL Injection (uso estrito de PDO com Prepared Statements), escape de saídas HTML contra XSS, tokens temporários contra CSRF em formulários e hash de senhas via password\_hash() (Bcrypt ou Argon2id).  
> * **Fora de escopo técnico**: Não haverá desenvolvimento de aplicativo móvel nativo, leituras biométricas/faciais, leitura óptica de código de barras no recreio, suporte multi-escola (multi-tenant) nem rotinas automáticas de devolução bancária (saque Pix).

## **4\. Decisões adotadas por padrão**

> * **Stack**: PHP, MySQL, HTML, CSS, JavaScript puro, Bootstrap local e organização MVC.  
> * **Ambiente de testes**: Ausência de ambiente formal intermediário na primeira versão; validação executada localmente antes da publicação.  
> * **Ambiente de produção**: Hospedagem tradicional compartilhada com PHP e MySQL na Hostnet.  
> * **Arquitetura MVC**: Separação estrutural de responsabilidades sem acoplamento a frameworks pesados de mercado.  
> * **Autenticação**: Padrão de credenciais locais protegidas e fluxo transacional por código via WhatsApp.  
> * **Auditoria técnica**: Uso de created\_at, created\_by, updated\_at e updated\_by nos cadastros principais.  
> * **Soft delete**: Adoção de exclusão lógica (deleted\_at) em todos os registros essenciais para não corromper relacionamentos financeiros anteriores.  
> * **Logs e contingência**: Implementação de logger com fallback para gravação em arquivos .log em disco caso a conexão com o MySQL falhe.  
> * **Configurações operacionais**: Parâmetros com valores padrão recomendados (recarga mínima de R$ 10,00, saldo baixo de R$ 15,00, timeout de sessão de 60 minutos, 5 tentativas de login e paginação de 25 registros).  
> * **Divisão de implementação**: Orientação de desenvolvimento no FSD em etapas pequenas, progressivas e testáveis para a IA codificadora.

## **5\. Stack e ambientes**

> * **Linguagem backend**: PHP 8.2+ (estruturado e modular).  
> * **Banco de dados**: MySQL 8.0+ / MariaDB 10.4+ utilizando o driver PDO\_MYSQL.  
> * **Tecnologias de interface (Frontend)**:  
  * HTML5 semântico;  
  * CSS3 utilitário e folhas de estilo customizadas para reproduzir o Design System do DESIGN.md;  
  * JavaScript puro (Vanilla JS, sem dependências pesadas de runtime);  
  * Framework CSS Bootstrap (arquivos compilados e servidos localmente).  
> * **Bibliotecas auxiliares backend**:  
  * Biblioteca para geração de PDF estruturado (Dompdf, FPDF ou TCPDF) compatível com documentos A4 timbrados e cupons contínuos em bobina de 80mm;  
  * Extensão nativa para leitura e parse de arquivos XML de notas fiscais (SimpleXML / DOMDocument);  
  * Biblioteca para processamento de planilhas escolares em .xlsx e .csv (PhpSpreadsheet ou leitor CSV nativo).  
> * **Ambiente local**:  
  * Docker e Docker Compose configurados com serviços para servidor web Apache, interpretador PHP e banco MySQL com mapeamento de portas e volumes locais.  
> * **Ambiente de testes/homologação**:  
  * Não aplicável na V1. Testes operacionais e simulações de webhooks serão executados localmente no ambiente Docker.  
> * **Ambiente de produção**:  
  * Hospedagem compartilhada com PHP e MySQL gerenciada pela Hostnet.  
> * **Observações sobre deploy**:  
  * O deploy consistirá na publicação dos arquivos via FTP/Git no servidor da Hostnet e importação do schema/migrações do banco de dados pelo phpMyAdmin ou terminal;  
  * Configurações sensíveis (credenciais de banco, chaves de API do Pix e tokens de WhatsApp) deverão ser centralizadas em arquivo de configuração de ambiente (.env ou config.php) inacessível via navegador direto.

## **6\. Arquitetura obrigatória**

O sistema seguirá rigorosamente a arquitetura baseada no padrão **Model-View-Controller (MVC)**:

> * **Model (Modelo)**: Camada responsável pela integridade das regras de negócio, cálculos contábeis, validações financeiras e persistência no banco de dados através de Prepared Statements via PDO. Nenhum elemento de interface ou marcação HTML poderá existir nos Models.  
> * **View (Visão)**: Camada focada na renderização do HTML, incorporação do CSS do design system, manipulação leve do DOM via JavaScript puro e apresentação dos dados enviados pelos Controllers. Não executa consultas SQL nem processa regras de negócio financeiras.  
> * **Controller (Controlador)**: Camada intermediária que intercepta as requisições HTTP, valida os parâmetros de entrada, verifica as permissões do usuário (RBAC), aciona os Models pertinentes e encaminha os dados para a View apropriada ou retorna respostas JSON (para requisições assíncronas do PDV e webhooks).  
> * **Camada de Serviços / Adaptadores (Service / Gateway Layer)**: Módulos dedicados fora dos controllers para abstrair integrações com fornecedores terceiros (Gateway Pix e API de WhatsApp), permitindo trocar de provedor sem impactar as regras de negócio centrais.

## **7\. Recursos estruturais definidos**

> * **Autenticação**: Duplo canal de autenticação (CPF \+ WhatsApp para responsáveis; Usuário nominal operador-\[nome\] \+ Senha para funcionários).  
> * **RBAC (Role-Based Access Control)**: Controle de rotas e interfaces baseado nos 4 perfis mapeados (Responsável, Operador, Administrador e Colaborador).  
> * **Auditoria**: Rastreabilidade com created\_at, created\_by, updated\_at, updated\_by nos cadastros e tabela específica para log de eventos de exceção (estornos manuais, 2ª via e perdas com justificativa obrigatória).  
> * **Soft delete**: Campo temporal deleted\_at aplicado a produtos, categorias, colaboradores, operadores e turmas.  
> * **Log de erros**: Tratamento global de exceções registrando mensagem, arquivo, linha, stack trace e dados contextuais, com gravação em banco e contingência em arquivo texto local.  
> * **Log de segurança**: Registro de acessos negados, múltiplas falhas de autenticação, bloqueios de contas por contestação MED e alterações cadastrais sensíveis.  
> * **Configurações globais**: Registro único em banco contendo parâmetros operacionais, regras financeiras e dados institucionais editáveis pelo Administrador.  
> * **Uploads e anexos**: Diretório protegido para processamento de planilhas da escola e XMLs de NF-e; pasta pública para imagens sanitizadas com IDs únicos.  
> * **Exportações**: Mecanismos nativos de geração de PDF para impressão e relatórios, além de downloads em formato CSV/Excel.  
> * **APIs / Webhooks**: Endpoint RESTful protegido para recepção e processamento de confirmações de pagamento e alertas de estorno/MED do gateway bancário.  
> * **Integrações externas**: Camada desacoplada para comunicação com API do Gateway Pix e API de mensageria WhatsApp.

## **8\. Perfis e permissões em nível alto**

> * **Responsável (Família)**:  
  * Permissão restrita ao Portal da Família.  
  * Pode gerenciar dados cadastrais e alérgenos dos seus dependentes, gerar QR Code Pix para recargas, agendar lanches até 08:00/13:30, editar pedidos pendentes, cancelar agendamentos com estorno instantâneo e visualizar extratos da sua própria carteira.  
  * Não tem acesso ao PDV, estoques, relatórios da cantina ou dados de outros alunos.  
> * **Operador / Atendente**:  
  * Permissão focada na rotina presencial do balcão da cantina.  
  * Pode abrir e fechar a sessão de caixa do seu respectivo turno (11:00 ou 16:00), imprimir lotes de tickets por turma, reimprimir 2ª via de cupons, consultar a lista consolidada de compras do turno, confirmar recebimento físico de salgados, operar o PDV para vendas avulsas e desconto em folha, registrar perdas de estoque com motivo obrigatório, transferir sobras de alunos faltantes para venda avulsa e estornar tickets com justificativa.  
  * Não tem acesso à alteração de preços, exclusão de produtos, importação de XMLs de NF-e, importação de planilhas escolares, calendário letivo ou relatórios contábeis globais.  
> * **Administrador / Gerente**:  
  * Acesso irrestrito a todas as interfaces e configurações da aplicação.  
  * Acumula integralmente as funções do Operador (atuando diretamente no balcão quando necessário) e possui privilégios exclusivos para: importação de planilhas de alunos/turmas, cadastro e precificação de alimentos, importação de XML de notas fiscais com conversão de unidades, pausa rápida de itens, configuração de estoque mínimo, gestão do calendário escolar (marcação de recessos e cancelamentos em lote de dias letivos), ajustes manuais de saldos, quitação de negativados, relatórios gerenciais e edição dos dados institucionais e logotipo.  
> * **Professor / Colaborador**:  
  * Perfil passivo cadastrado na base de dados para consumo no balcão sob a modalidade "Desconto em Folha".  
  * Não possui interface de login próprio na V1.

## **9\. Entidades prováveis em nível alto**

O FSD deverá estruturar as seguintes entidades principais e suas inter-relações:

> * **Responsaveis**: Titulares das contas financeiras (CPF, nome, e-mail, telefone/WhatsApp, senha criptografada, termos aceitos, status de bloqueio por MED).  
> * **Alunos**: Dependentes vinculados a um responsável (matrícula, nome, foto opcional, turma, turno, situação ativo/inativo).  
> * **RestricoesAlimentares**: Catálogo de alérgenos padronizados (glúten, lactose, amendoim, ovos, soja, etc.) associados a alunos específicos.  
> * **Turmas**: Agrupamentos escolares ordenados (série, turma, turno) que guiam a ordenação física dos tickets.  
> * **Colaboradores**: Funcionários e docentes autorizados ao consumo fiado autorizado (matrícula funcional, nome, cargo, foto, status).  
> * **Alimentos / Produtos**: Catálogo comercial (código, descrição, categoria, tipo: salgado diário ou industrializado contínuo, preço de venda, turno permitido, alérgenos contidos, status ativo/pausado, foto opcional).  
> * **Categorias**: Classificação dos itens do cardápio.  
> * **MovimentacoesEstoque**: Rastreabilidade física de entradas (compras, XML, sobras de faltantes) e saídas (vendas balcão, perdas com motivo obrigatório).  
> * **NotasFiscaisXML**: Cabeçalho de notas fiscais importadas e histórico de regras de conversão de embalagens em unidades.  
> * **Pedidos**: Agendamentos realizados no portal (código, data de consumo, turno, aluno, valor total debitado, status).  
> * **ItensPedido**: Detalhamento dos produtos encomendados em cada pedido.  
> * **Tickets**: Registros para impressão física em bobina térmica de 80mm com tag de alérgeno e controle de reimpressão de 2ª via.  
> * **SessoesCaixa**: Controle de caixa por turno (operador, turno, data/hora abertura, fundo de troco, entradas em dinheiro por recarga vs avulso, total Pix, valores contados, sobras/faltas, data/hora fechamento).  
> * **TransacoesCarteira**: Histórico contábil e extrato das carteiras familiares (responsável, data/hora, tipo de transação, valor, saldo resultante).  
> * **CalendarioLetivo**: Dias úteis liberados, feriados, recessos, férias e controle de cancelamento em lote.  
> * **LogsAuditoria**: Rastreamento de ações críticas manuais da equipe da cantina.  
> * **LogsErros**: Registro técnico de exceções da aplicação com mecanismo de contingência.  
> * **LogsSeguranca**: Registro de eventos sensíveis de segurança e acessos.  
> * **ConfiguracoesGlobais**: Tabela de registro único com parâmetros operacionais e institucionais.

## **10\. Módulos, telas e fluxos esperados em nível alto**

O FSD deverá detalhar as seguintes telas, fluxos e agrupamentos de navegação:

> * **Módulo 1: Autenticação e Acesso**:  
  * Tela de login unificada (com seleção de acesso para Família ou Equipe da Cantina);  
  * Fluxo de primeiro acesso do responsável (validação por CPF e código WhatsApp de 6 dígitos);  
  * Tela modal com os Termos e Políticas da Cantina (aceite obrigatório);  
  * Fluxo de recuperação de senha via WhatsApp.  
> * **Módulo 2: Portal da Família**:  
  * Visão geral da Carteira Familiar (saldo disponível, alerta de saldo baixo, histórico analítico);  
  * Tela de Recarga Pix (geração de QR Code dinâmico e código Copia e Cola);  
  * Tela de Agendamento Semanal (calendário, seleção de filho, catálogo com trava de estoque zerado e modal de aviso de alérgenos);  
  * Tela de Meus Pedidos (edição de itens e cancelamento autônomo respeitando a trava das 08:00 e 13:30);  
  * Tela de Perfil e Gestão de Alergias dos Dependentes.  
> * **Módulo 3: Operação de Encomendas e Fornecedores**:  
  * Painel de Corte do Turno (consolidação matemática das demandas de salgados pontualmente às 08:00 e 13:30);  
  * Ação "Copiar para WhatsApp" e emissão da listagem em PDF para o fornecedor;  
  * Tela de Conferência e Recebimento de Fornecedor (entrada das quantidades e liquidação simultânea da demanda de tickets).  
> * **Módulo 4: Impressão e Gestão de Tickets**:  
  * Tela de Impressão em Lote (agrupamento e ordenação sequencial por série e turma para bobina de 80mm);  
  * Tela de Consulta de Pedidos do Turno e Reimpressão de 2ª Via.  
> * **Módulo 5: PDV e Operação de Balcão**:  
  * Ponto de Venda ágil para Vendas Avulsas (pagamento em dinheiro com troco ou Pix balcão, comprovante térmico opcional);  
  * Busca e lançamento de consumo de colaboradores sob modalidade "Desconto em Folha";  
  * Tela de Estorno de Balcão (com seleção de motivo e crédito imediato na carteira);  
  * Ação de Aproveitamento de Sobras de Alunos Faltantes (transferência de itens faturados para a venda avulsa).  
> * **Módulo 6: Gestão de Estoque e XML**:  
  * Painel de Controle de Estoque (saldos físicos, reservas online, pontos de estoque mínimo e botão "Ativo/Pausado");  
  * Tela de Importação de XML de NF-e (conferência, vinculação de itens e fator multiplicador de conversão);  
  * Tela de Lançamento de Perdas (baixa manual com motivo obrigatório).  
> * **Módulo 7: Controle Financeiro e Caixas**:  
  * Tela de Abertura de Caixa (registro do fundo de troco por turno);  
  * Tela de Fechamento de Caixa (conferência segregada de valores físicos e digitais às 11:00 e 16:00);  
  * Tela de Ajuste Manual de Saldo e Quitação de Devedores (acesso exclusivo do Administrador).  
> * **Módulo 8: Relatórios Gerenciais**:  
  * Relatório de Extrato de Consumo do Aluno (modelo timbrado com cabeçalho oficial);  
  * Relatório de Vendas por Alimento (filtros por período, turno e categoria);  
  * Relatório de Fechamento de Caixa por Turno (espelho para assinatura);  
  * Relatório de Desconto em Folha (mensal consolidado em PDF e CSV para o RH);  
  * Relatório de Famílias/Alunos com Saldo Devedor.  
> * **Módulo 9: Administração e Cadastros**:  
  * Tela de Importação em Lote de Alunos/Turmas via Planilha;  
  * Catálogo de Produtos e Categorias;  
  * Gestão do Calendário Escolar e Cancelamento em Lote de Dias Letivos;  
  * Tela de Configurações da Empresa e Parâmetros Globais.

## **11\. Alertas para relatórios, consultas, exportações e desempenho**

> * **Consultas e relatórios críticos**:  
  * Listagem consolidada de salgados no corte (08:00 e 13:30);  
  * Emissão contínua de lotes de tickets térmicos ordenados por série/turma;  
  * Extrato de consumo do aluno com cálculo dinâmico de saldo linha a linha;  
  * Relatório de conciliação de caixa com segregação de tipos de recebimento;  
  * Apuração mensal de consumo de colaboradores.  
> * **Filtros frequentes**:  
  * Intervalos de datas (data\_consumo, created\_at);  
  * Turno escolar (matutino, vespertino);  
  * Turma e série escolar;  
  * Chaves estrangeiras de relacionamento (responsavel\_id, aluno\_id, colaborador\_id);  
  * Status e tipos de movimentação.  
> * **Alertas de desempenho para o FSD**:  
  * O FSD deverá prever a criação de índices compostos em tabelas transacionais críticas (especialmente pedidos, tickets, movimentacoes\_estoque e transacoes\_carteira) para evitar buscas sequenciais completas (*table scans*) durante os horários de pico;  
  * A geração de relatórios de grande volume e lotes de tickets deverá ser otimizada para streaming ou paginação em memória no PHP para evitar estouro do limite de memory\_limit na hospedagem compartilhada.

## **12\. Alertas para uploads, anexos e arquivos**

> * **Locais de uso**:  
  * Importação inicial e atualização anual de alunos/turmas (planilhas .xlsx, .csv);  
  * Entrada de mercadorias no estoque físico (arquivos .xml de NF-e);  
  * Configuração institucional (logotipo da cantina/colégio);  
  * Cadastro de alimentos, alunos e colaboradores (fotos opcionais).  
> * **Regras técnicas consolidadas**:  
  * Tamanho máximo permitido de até **10 MB** por arquivo;  
  * Extensões aceitas estritamente limitadas a .csv, .xlsx, .xml, .jpg, .jpeg, .png e .webp;  
  * Validação obrigatória no backend por verificação de MIME type e assinatura de cabeçalho binário (magic bytes), rejeitando arquivos com extensões forçadas;  
  * Uploads de planilhas e XMLs restritos ao perfil Administrador.  
> * **Segurança e armazenamento**:  
  * Planilhas escolares e arquivos XML contêm dados protegidos por sigilo fiscal e LGPD escolar, devendo ser gravados em diretório protegido fora da raiz pública da web (ex: storage/uploads/private/) ou blindados via regra de negação total no .htaccess;  
  * Arquivos de imagem pública (fotos e logotipo) deverão ser renomeados no momento da gravação utilizando hash criptográfico ou UUID aleatório, eliminando nomes originais e impedindo a injeção/execução de scripts maliciosos.

## **13\. Alertas para logs, auditoria e segurança**

> * **Regras de log de erros**:  
  * O FSD deverá padronizar uma classe global de captura de erros (ErrorHandler / ExceptionHandler);  
  * Erros de execução deverão ser persistidos no banco de dados na tabela logs\_erros;  
  * **Estratégia de contingência**: Caso ocorra falha de conexão com o MySQL ou o próprio comando de inserção falhe, o sistema deverá realizar fallback imediato escrevendo a linha de log em arquivo de texto protegido (storage/logs/error.log) com rotação de arquivos;  
  * A tela do usuário nunca deve expor stack traces técnicos ou mensagens de banco de dados, exibindo mensagens de erro seguras e padronizadas de acordo com o Design System.  
> * **Regras de log de segurança**:  
  * Gravação obrigatória de eventos como: tentativas incorretas de login, bloqueios por excesso de tentativas, tentativas de acesso a URLs não permitidas por perfil (RBAC) e eventos de bloqueio automático de famílias via webhook MED (contestação de Pix).  
> * **Regras de auditoria de negócio**:  
  * Gravação obrigatória de operador, timestamp e motivo textual selecionado nas seguintes ações de balcão:  
    1. Estorno manual de tickets;  
    2. Emissão de 2ª via de cupons térmicos;  
    3. Registro manual de perdas de mercadorias no estoque;  
    4. Ajustes manuais de saldo ou quitações executadas pelo Administrador.  
> * **Segurança da sessão**:  
  * Destruição e expiração automática de sessão após 60 minutos de inatividade;  
  * Regeneração do ID de sessão (session\_regenerate\_id(true)) no momento do login para mitigar riscos de Session Fixation.

## **14\. Itens que não devem ser inventados**

Os seguintes recursos **não devem ser incluídos nem especificados no FSD**, pois foram categoricamente descartados ou deixados fora de escopo na V1:

> * Aplicativo móvel nativo para download nas lojas App Store ou Google Play;  
> * Reconhecimento facial, leitura biométrica ou totens de autoatendimento;  
> * Montagem de combos ou kits promocionais automáticos;  
> * Limite ou teto diário de gastos individualizado por dependente;  
> * Leitura óptica de código de barras ou QR Code no momento da entrega dos lanches no recreio;  
> * Disparo de mensagens no WhatsApp no instante da entrega do lanche;  
> * Arquitetura multi-escola (multi-tenant) ou gestão de filiais;  
> * Ficha técnica automatizada com cálculo calórico dinâmico;  
> * Gateway de saque automático via Pix para devolução de saldo residual;  
> * Permissão para criação manual de saldo negativo ("fiado") para alunos.

## **15\. Pendências não bloqueantes**

Não foram identificadas pendências não bloqueantes para a criação do FSD.

Todas as decisões arquiteturais, limites operacionais, componentes visuais, stack tecnológica e diretrizes de integração estão 100% definidas e estabilizadas.

## **16\. Pronto para o FSD**

As decisões técnicas e estruturais do projeto estão consolidadas e o projeto está oficialmente pronto para a elaboração da Especificação Funcional e Técnica de Software.

O próximo passo do fluxo de engenharia é a criação do documento:

docs/FSD.md

O documento FSD.md deverá ser elaborado tomando como base direta e exclusiva:

> * **PRD.md**: como fonte definitiva de requisitos de negócio, regras operacionais e fluxos funcionais;  
> * **DECISOES\_TECNICAS.md**: como guia de arquitetura, stack, ambientes, logs, segurança e limites técnicos;  
> * **docs/DESIGN.md**: como especificação visual para a estrutura de telas, layout, tabelas, componentes e identidade do Colégio Monteiro Lobato.