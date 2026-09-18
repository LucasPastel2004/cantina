# Registro Vivo de Erros e Soluções (docs/ERROS.md)

Este documento registra todas as falhas, erros de ambiente, bugs de implementação e problemas encontrados durante o desenvolvimento do sistema **Cantina Web — Colégio Monteiro Lobato**, acompanhados de suas causas raízes, soluções aplicadas e medidas preventivas.

---

## Como Registrar um Erro

Ao encontrar qualquer erro ou comportamento inesperado durante uma etapa de desenvolvimento, registre-o no final deste arquivo utilizando o formato padronizado abaixo:

```text
## <data YYYY-MM-DD> - <título curto do erro>

- Sintoma: Descrição objetiva de como a falha se manifestou (mensagem de erro, tela quebrada, código HTTP, etc.).
- Causa: Diagnóstico técnico da causa raiz do problema.
- Solução aplicada: O que foi alterado ou corrigido para resolver o erro.
- Como evitar no futuro: Diretriz ou boa prática para prevenir a reincidência do problema.
```

---

## Histórico de Ocorrências

## 2026-09-16 - Erro fatal por redeclaração da classe Database em múltiplos includes

- Sintoma: `Fatal error: Cannot declare class Database, because the name is already in use in config/database.php` ao invocar `require 'config/database.php'` repetidas vezes através de models distintos.
- Causa: O arquivo `config/database.php` continha a declaração direta `class Database` e retornava `Database::getInstance()`. Quando múltiplos componentes ou testes exigiram o arquivo via `require` (em vez de `require_once`), o interpretador PHP disparou erro fatal de classe duplicada.
- Solução aplicada: Envolvida a definição da classe com a cláusula protetiva `if (!class_exists('Database')) { class Database { ... } }`, mantendo o retorno da instância singleton em todas as chamadas.
- Como evitar no futuro: Em arquivos de fábrica (factory files) que declaram classes e retornam instâncias via `return`, proteger a declaração com `class_exists()` para assegurar reentrância e idempotência total.

---

## 2026-09-16 - Bloqueio HTTP total ao runner.php por regra genérica no .htaccess

- Sintoma: Erro HTTP 403 retornado diretamente pelo servidor web Apache ao tentar acessar `database/runner.php?token=...`, impedindo a execução de migrações em ambiente web sem SSH (Hostnet).
- Causa: A pasta `database/` continha diretiva irrestrita `Require all denied`, bloqueando categoricamente todo e qualquer arquivo interno antes mesmo de a aplicação PHP inspecionar o token de segurança.
- Solução aplicada: Adicionada exceção específica `<Files "runner.php"> Require all granted </Files>` no `.htaccess` de `database/`, enquanto foi criado `database/migrations/.htaccess` com `Require all denied` absoluto para blindar os arquivos de migração individuais. O arquivo `runner.php` assume a validação mandatória de token com retorno imediato de HTTP 403 para acessos não autorizados.
- Como evitar no futuro: Ao proteger diretórios de persistência ou scripts operacionais com `.htaccess`, aplicar exceções granulares por arquivo `<Files>` mantendo a blindagem estrita de subpastas e dependências internas.

---

## 2026-09-17 - Falha na invocação direta de php na CLI do host Windows

- Sintoma: `php : O termo 'php' não é reconhecido como nome de cmdlet, função, arquivo de script ou programa operável` ao rodar testes pelo PowerShell do host.
- Causa: O ambiente de desenvolvimento configurado no projeto é baseado em contêineres Docker (`cantina_app` e `cantina_db`), de modo que o binário `php` não está configurado na variável de ambiente `PATH` do sistema operacional hospedeiro Windows.
- Solução aplicada: Execução de todos os scripts, testes e comandos CLI do PHP delegada para o contêiner Docker oficial da aplicação via `docker compose exec app php <caminho_do_script>`.
- Como evitar no futuro: Lembrar que neste ambiente de desenvolvimento o interpretador PHP 8.2 e o MySQL 8.0 rodam dentro do Docker Compose; qualquer execução de testes automatizados ou scripts CLI deve ser prefixada com `docker compose exec app php ...`.

---

## 2026-09-17 - Logotipo institucional desproporcional no Portal da Família e cache de assets

- Sintoma: A imagem do logotipo oficial do colégio estourou as dimensões da topbar no Portal da Família (`/design/portal`), exibindo-se em resolução nativa (mais de 800px) e quebrando o layout da barra superior.
- Causa: Dupla ocorrência: (1) o seletor CSS `.header-logo` estava inicialmente restrito a `.app-header .header-logo` e `.sidebar-brand .header-logo`, não cobrindo o contêiner `.portal-header`; (2) a tag `<img>` no layout não possuía atributos explícitos `width`/`height` nem estilo inline defensivo, deixando a renderização suscetível a cache prévio de folhas de estilo no navegador do usuário.
- Solução aplicada: (1) Generalizado o seletor `.header-logo` em `assets/css/custom.css` como regra universal autônoma de 38x38px com `border-radius: 50%`; (2) Adicionados atributos `width="38" height="38"` e `style="width: 38px; height: 38px; max-width: 38px; max-height: 38px; border-radius: 50%; object-fit: cover;"` diretamente nas tags `<img>` de todos os layouts; (3) Implementada query de versão (`?v=5.0.1`) nas referências a `custom.css` para forçar invalidação imediata de cache no cliente.
- Como evitar no futuro: Sempre definir dimensões explícitas `width` e `height` em tags `<img>` de identidades visuais críticas e utilizar versionamento nos assets CSS (`?v=...`) para prevenir descompasso de cache no navegador.

---

## 2026-09-17 - Warning de cabeçalhos já enviados ao regenerar sessão e limpar cookies em CLI

- Sintoma: `Warning: session_regenerate_id(): Session ID cannot be regenerated after headers have already been sent` e aviso em `setcookie()` na classe `Session`.
- Causa: Em scripts executados via CLI que produzem saída textual antes do ciclo de sessão (ou em testes automatizados), funções nativas de cabeçalhos e cookies disparam avisos quando os cabeçalhos já foram enviados.
- Solução aplicada: Adicionada a guarda defensiva `!headers_sent()` em `Session::regenerate()` e `Session::destroy()`, além de no método `Auth::logout()` efetuar a limpeza das variáveis de autenticação da sessão e regeneração de ID sem destruição forçada da sessão em runtime CLI.
- Como evitar no futuro: Em wrappers de sessão e cookies, sempre checar `!headers_sent()` antes de invocar `setcookie()` ou `session_regenerate_id()`.

---

## 2026-09-17 - Falso positivo de bloqueio de força bruta por compartilhamento de IP

- Sintoma: Falha de autenticação legítima de outros usuários no mesmo IP (como 127.0.0.1 em testes ou ambiente escolar com NAT) após atingir limite de tentativas com usuário de teste.
- Causa: A consulta original de verificação de tentativas em `logs_seguranca` verificava `(cpf_ou_usuario = :identificador OR ip_origem = :ip)`, propagando o bloqueio para todas as contas vindas daquele mesmo IP.
- Solução aplicada: Restrita a checagem de tentativas incorretas ao identificador da conta (`cpf_ou_usuario = :identificador`) e adicionada cláusula temporal que reseta o histórico de falhas caso haja um registro posterior de `login_sucesso`.
- Como evitar no futuro: Em aplicações corporativas onde centenas de usuários acessam a partir do mesmo IP da escola/empresa, aplicar o rate limit de bloqueio primariamente sobre o identificador da conta para evitar negação de serviço a outros usuários legítimos.

---

## 2026-09-17 - TypeError em htmlspecialchars ao renderizar mensagens flash em alert.php

- Sintoma: `TypeError: htmlspecialchars(): Argument #1 ($string) must be of type string, array given` em `app/views/partials/alert.php:54` ao logar no Portal da Família (`/portal`) ou ao deslogar (`/login`).
- Causa: O método `Session::getFlash($type)` armazena e retorna mensagens flash agrupadas como um array de strings (`$_SESSION['_flash'][$type] = [msg1, msg2, ...]`). No entanto, o componente `app/views/partials/alert.php` atribuía o retorno diretamente para a chave `message` (`'message' => $flashSuccess`), e em seguida chamava `htmlspecialchars($alert['message'])`. Como `$alert['message']` era um array de strings, o PHP 8.2 disparava erro fatal de tipagem (`TypeError`).
- Solução aplicada: 
  1. Refatorado `app/views/partials/alert.php` para desempacotar e iterar sobre todas as mensagens retornadas por `Session::getFlash()` para cada categoria (`success`, `danger`, `error`, `warning`, `info`), garantindo que cada item seja uma string individual.
  2. Adicionada verificação defensiva de tipo antes da chamada a `htmlspecialchars()`, convertendo para string caso necessário.
  3. Adicionado suporte no `AuthController::logout()` e `index.php` para logout tanto via POST (com CSRF) quanto via GET, assegurando saída limpa sem quebras de sessão.
  4. Criada suíte de testes de validação do ciclo de autenticação em `tests/auth_flow_test.php`, com 6/6 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Métodos que manipulam mensagens flash ou coleções em sessão devem documentar e respeitar o contrato de tipos (array vs string). Todo partial reutilizável deve checar defensivamente se os dados recebidos são arrays antes de passá-los para funções de sanitização de strings como `htmlspecialchars()`.

---

## 2026-09-17 - Efeito colateral em testes automatizados ativando conta de teste de Primeiro Acesso

- Sintoma: Redirecionamento com mensagem `"Informação: Sua conta já está ativada. Informe sua senha cadastrada para entrar."` ao tentar testar o fluxo de Primeiro Acesso com o CPF `555.666.777-88` na interface web.
- Causa: O método `testarFluxoPrimeiroAcesso()` em `tests/fase6_test.php` simulava a ativação completa da conta gravando a nova senha e os termos aceitos no MySQL, porém não restaurava o registro para o estado pendente no encerramento do teste.
- Solução aplicada: 
  1. Executado reset no banco para o CPF `555.666.777-88`, restaurando `senha_hash = ''` e `termos_aceitos_em = NULL`.
  2. Adicionado responsável pendente adicional `999.888.777-66` (Fernanda Oliveira Costa) como contingência de testes.
  3. Adicionado comando de teardown ao final de `testarFluxoPrimeiroAcesso()` em `tests/fase6_test.php` garantindo que a execução da suíte de testes nunca deixe o registro ativado.
- Como evitar no futuro: Sempre assegurar teardown de limpeza ao final de testes de integração que realizem mutações no ciclo de vida de entidades compartilhadas com testes manuais de interface.

---

## 2026-09-17 - Falha de asserção por contagem estática de categorias e colisão de CPF em testes de regressão

- Sintoma: `[FAIL] Catálogo de categorias contém as 4 classificações oficiais do FSD` e `Fatal error: Uncaught PDOException: Duplicate entry '99988877766' for key 'responsaveis.cpf'` ao reexecutar a suíte de testes de regressão `tests/fase3_test.php`.
- Causa: Duas causas identificadas: (1) O teste da Fase 3 executava `SELECT COUNT(*) FROM categorias` esperando estritamente 4 registros, falhando quando os novos testes de CRUD da Fase 7 inseriram novas categorias no banco; (2) O teste de Foreign Key utilizava um CPF estático (`99988877766`) que colidiu com o registro de contingência cadastrado na Fase 6.
- Solução aplicada: (1) Refatorada a asserção de categorias em `tests/fase3_test.php` para verificar especificamente a presença das 4 classificações oficiais do FSD (`Salgados Assados`, `Sucos Naturais`, `Industrializados`, `Sobremesas`) através de `in_array()`; (2) Alterada a geração do CPF de teste para um gerador aleatório dinâmico com prefixo único, garantindo reentrância e idempotência irrestrita.
- Como evitar no futuro: Em suítes de testes de fases anteriores, validar o conteúdo semântico obrigatório (presença de valores essenciais) em vez de contagens brutas que são afetadas por CRUDs de fases posteriores, e utilizar identificadores únicos dinâmicos em testes transacionais.

---

## 2026-09-18 - Opção "Configurações Globais" ausente no menu lateral da administração

- Sintoma: A opção "Configurações Globais" não aparecia no menu lateral (seção "Administração") da interface operacional e administrativa da cantina.
- Causa: Dupla ocorrência diagnosticada:
  1. No arquivo `app/views/layouts/sidebar.php`, a visibilidade do item estava estritamente vinculada a `<?php if ($isAdmin): ?>`, onde `$isAdmin` verificava `$user['perfil'] === 'administrador'`. Porém, a classe `Auth::user()` retornava a chave `'role'` e não `'perfil'`, fazendo com que mesmo logado como administrador, a condição avaliasse como `false`.
  2. Na rota de homologação visual (`/design/admin`), o array de mock em `ShowcaseController.php` definia o usuário com perfil `'operador'`, ocultando o item. Além disso, todas as outras 5 opções da seção "Administração" eram exibidas incondicionalmente, criando uma assimetria no menu.
- Solução aplicada:
  1. Em `app/core/Auth.php`, adicionado o alias `'perfil' => Session::get('_auth_role')` ao lado de `'role'` para máxima interoperabilidade.
  2. Em `app/core/Controller.php`, implementada injeção automática de `$data['user'] = Auth::user()` para todas as views quando houver usuário autenticado.
  3. Em `app/views/layouts/sidebar.php`, unificada a checagem de perfil (`$user['role'] ?? $user['perfil']`), corrigida a exibição do papel no rodapé e removida a condicional restritiva, tornando "Configurações Globais" um item fixo e permanente da seção "Administração" (com a proteção real de acesso delegada ao RBAC no backend).
  4. Em `app/controllers/ShowcaseController.php`, atualizado o mock do painel para perfil de Administrador Geral.
  5. Em `app/views/home/index.php`, adicionados atalhos diretos para todas as rotas da Fase 7 (Configurações, Cardápio, Categorias, Colaboradores, Turmas).
  6. Criado teste automatizado `tests/sidebar_visibility_test.php` com 7 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Padronizar e manter retrocompatibilidade em estruturas de dados de usuário entre Core e Views (`role` vs `perfil`), preferir manter menus visíveis delegando a restrição de autorização aos middlewares de RBAC do backend, e garantir testes automatizados de renderização de interface.

---

## 2026-09-18 - Colisão de texto com setas de incremento em inputs type="number" com text-end

- Sintoma: Na tela de Configurações Globais (`app/views/admin/configuracoes.php`), os valores numéricos dos campos com `text-end` ficavam colados nas setinhas (spin buttons) nativas de incremento/decremento do navegador.
- Causa: O uso da classe utilitária do Bootstrap `.text-end` (`text-align: right`) em elementos `<input type="number">` faz com que o texto alinhe encostando no limite direito do campo. Nos navegadores baseados em Chromium (Chrome/Edge), o componente nativo de spin button (`::-webkit-inner-spin-button`) é renderizado internamente no canto direito do input, gerando colisão visual com os numerais.
- Solução aplicada:
  1. Removida a classe `text-end` dos campos de `recarga_minima_pix` e `alerta_saldo_baixo`, padronizando o alinhamento de texto à esquerda de forma consistente com os demais campos de formulário (`timeout_sessao_minutos`, `limite_tentativas_login`, `itens_por_pagina`).
  2. Adicionada regra defensiva em `assets/css/custom.css` com padding lateral de `0.65rem` em `input[type="number"]` e `padding-right: 28px !important` para casos com `.text-end`.
  3. Atualizada a versão de cache do CSS nos layouts (`?v=5.0.6`).
- Como evitar no futuro: Em campos de formulário que utilizem `type="number"`, preferir o alinhamento padrão à esquerda ou aplicar padding direito mínimo de `28px` para evitar sobreposição com os controles nativos de spin do navegador.

---

## 2026-09-18 - Ausência de formatação automática (máscara) em campos de CNPJ e Telefone

- Sintoma: Os campos de CNPJ e WhatsApp de Suporte na tela de Configurações Globais aceitavam entrada livre de caracteres sem formatar a pontuação de CNPJ (`00.000.000/0000-00`) ou de telefone móvel/fixo (`(00) 00000-0000` / `(00) 0000-0000`).
- Causa: O arquivo `assets/js/app.js` continha apenas a máscara de moeda (`.input-money`), não possuindo manipuladores de formatação para CNPJ, Telefone ou CPF. Além disso, a view `app/views/admin/configuracoes.php` exibia os valores brutos sem aplicar máscara na renderização inicial do PHP.
- Solução aplicada:
  1. Implementadas as funções globais `formatarCnpj()`, `formatarTelefone()` e `formatarCpf()` em `assets/js/app.js`, com escutas dinâmicas nos eventos `input` e `blur`.
  2. Implementada pré-formatação no PHP em `app/views/admin/configuracoes.php` para renderizar os dados já pontuados no carregamento da tela.
  3. Adicionado script defensivo inline em `configuracoes.php` com classes `.input-cnpj` e `.input-phone` e placeholders institucionais.
  4. Atualizada a versão do script no layout (`app.js?v=5.0.6`) para forçar renovação imediata do cache do navegador.
  5. Criado teste automatizado `tests/mask_test.php` com 14/14 asserções aprovadas com 100% de sucesso.
---

## 2026-09-18 - Erro SQLSTATE[HY093] Invalid parameter number na barra de busca de produtos

- Sintoma: `PDOException: SQLSTATE[HY093]: Invalid parameter number` ao digitar qualquer termo na barra de busca de produtos (`/admin/produtos?busca=...`), gerando tela de erro 500 com código UUID de incidente.
- Causa: Com o driver `PDO_MYSQL` configurado com Prepared Statements nativos estritos (`ATTR_EMULATE_PREPARES => false`, conforme estabelecido na arquitetura de segurança do projeto), o motor do MySQL não permite reaproveitar o mesmo identificador de placeholder nomeado (`:busca`) para múltiplos campos na mesma consulta (`codigo_interno LIKE :busca OR descricao LIKE :busca`) quando o array de parâmetros contém apenas uma ocorrência da chave.
- Solução aplicada:
  1. Em `app/models/Produto.php`, renomeados os placeholders para identificadores distintos: `(p.codigo_interno LIKE :busca_cod OR p.descricao LIKE :busca_desc)`, atribuindo o termo de busca para ambas as chaves `$params['busca_cod']` e `$params['busca_desc']`.
  2. Aplicada a mesma correção preventiva em `app/models/Colaborador.php` (`:busca_nome`, `:busca_mat`, `:busca_cargo`) e em `app/models/Turma.php` (`:busca_nome`, `:busca_serie`).
  3. Criado teste automatizado `tests/search_test.php` validando todas as buscas de texto nos três modelos.
  4. Adicionado teste de busca via query string HTTP em `tests/admin_web_test.php`, validando retorno 200 OK sem erros.
- Como evitar no futuro: Em ambientes com `ATTR_EMULATE_PREPARES => false`, nunca reutilizar o mesmo nome de placeholder nomeado em mais de um ponto da query SQL. Sempre declarar nomes distintos (ex: `:campo1_busca`, `:campo2_busca`) e associar o valor a cada um deles no array de parâmetros.

---

## 2026-09-18 - Reconhecimento de colunas compostas no cabeçalho escolar e validação estrita de CPF

- Sintoma: Planilhas escolares contendo títulos de coluna compostos como "Telefone WhatsApp" ou "Celular / WhatsApp" não mapeavam a coluna para o campo obrigatório `whatsapp` durante a importação em lote, e geradores de teste que produziam CPFs de 9 dígitos numéricos causavam descarte das linhas por validação de tamanho.
- Causa: Dupla ocorrência identificada:
  1. No método `SpreadsheetService::identificarMapaColunas()`, a normalização avaliava igualdade estrita em array contra termos simples (`'whatsapp'`, `'celular'`, `'telefone'`). Títulos compostos normalizados para `'telefone_whatsapp'` ou `'celular_whatsapp'` não davam match exato.
  2. Em `tests/fase8_test.php`, o sufixo numérico de 4 dígitos gerava CPFs do tipo `"888" . $sufixo . "11"` (totalizando 9 dígitos numéricos), violando a validação obrigatória do serviço que exige estritamente 11 dígitos numéricos.
- Solução aplicada:
  1. Em `app/services/SpreadsheetService.php`, aprimorada a regra de detecção de cabeçalho para utilizar `str_contains()` com termos sinônimos (`whatsapp`, `celular`, `telefone`, `fone`), além de adicionar os termos compostos na lista de matches diretos.
  2. No arquivo de testes `tests/fase8_test.php`, ajustada a geração randômica de CPFs para utilizar formato de 6 dígitos interpolados (`sprintf('%06d', ...)`), garantindo que todo CPF gerado tenha sempre exatamente 11 dígitos numéricos válidos.
- Como evitar no futuro: Sempre utilizar detecção de padrões flexíveis (`str_contains`) combinada com normalização semântica para cabeçalhos de planilhas de terceiros (onde a nomenclatura varia frequentemente entre escolas), e garantir que geradores de teste respeitem estritamente as regras de validação do domínio (11 dígitos para CPF).

---

## 2026-09-18 - Saldo da carteira familiar zerado na visualização de restrições alimentares

- Sintoma: Ao navegar para a aba "Alergias & Restrições" (`/portal/alergias`), o saldo da carteira familiar na barra superior exibia "Saldo: R$ 0,00" em vez do saldo real do responsável (ex: R$ 75,50), além de o menu superior permanecer com a aba "Início" destacada como ativa.
- Causa: Dupla ocorrência identificada:
  1. No controlador `PortalController::alergias()`, os dados passados para a view omitiam as variáveis `'saldoAtual'`, `'responsavelNome'` e `'activeMenu'`.
  2. No layout mestre `app/views/layouts/portal.php`, a variável `$saldoAtual` assumia o valor padrão `0.00` caso não fosse explicitamente fornecida pela action, sem consultar defensivamente o usuário autenticado na sessão.
- Solução aplicada:
  1. No layout `app/views/layouts/portal.php`, implementada resolução defensiva e resiliente: caso `$saldoAtual` ou `$responsavelNome` não sejam fornecidos no array da view, o layout automaticamente recupera o saldo e o nome a partir de `Auth::user()` ou das variáveis de sessão (`_auth_saldo` e `_auth_nome`), garantindo que o saldo nunca seja exibido como zerado por omissão de parâmetros.
  2. Em `app/controllers/PortalController.php`, atualizados os métodos `alergias()` e `index()` para consultar o saldo em tempo real no banco, sincronizar na sessão com `Session::set('_auth_saldo', $saldoAtual)` e repassar explicitamente `'saldoAtual'`, `'responsavelNome'` e `'activeMenu' => 'alergias'`.
  3. Criado o teste automatizado `tests/portal_saldo_test.php` com 9 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Em layouts mestres compartilhados que exibem dados contábeis ou de identidade, sempre implementar fallback automático para as variáveis globais de sessão ou provedor de autenticação (`Auth::user()`), e padronizar o repasse das propriedades de estado de navegação (`activeMenu`) em todos os métodos dos controladores.

---

## 2026-09-18 - Duplicação de escopo ao injetar método alias getPdo() no Model.php

- Sintoma: Erro de sintaxe potencial ou métodos com escopo solto após edição automática contendo fragmentos residuais do método `getConnection()` original.
- Causa: Durante a introdução do método alias `getPdo()` em `app/core/Model.php`, a substituição de bloco de código truncou indevidamente a assinatura original do método, deixando código residual de inicialização de PDO fora de qualquer método de classe.
- Solução aplicada: 
  1. Revisão e limpeza cirúrgica de `app/core/Model.php`, unificando a inicialização de PDO no método `getConnection()` e inserindo o método estático `getPdo(): \PDO` com chamada direta a `self::getConnection()`.
  2. Validação sintática completa via `php -l app/core/Model.php` e execução de toda a suíte de testes com 100% de sucesso.
  3. Atualizada asserção em `tests/mask_test.php` para aceitar cache busting de script via regex (`app.js?v=5.0.[6-9]`), assegurando compatibilidade com futuras atualizações de assets.
- Como evitar no futuro: Sempre inspecionar as linhas adjacentes com ferramentas de visualização antes e depois de substituições parciais em classes fundamentais do sistema (`Model`, `Router`, `Controller`), validando com `php -l` imediatamente após a alteração.


