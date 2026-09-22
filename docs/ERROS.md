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

---

## 2026-09-18 - View da Carteira Familiar renderizada em HTML puro sem layout master e CSS

- Sintoma: A página `/portal/carteira` renderizava texto corrido sem folha de estilo ("straight html"), fontes serifadas padrão do navegador (Times New Roman), links sem estilização e sem o cabeçalho, navbar ou rodapé da aplicação.
- Causa: Dupla ocorrência identificada:
  1. Em `app/controllers/CarteiraController.php`, o método `render()` foi invocado com o argumento de layout `'layouts/portal'` (`$this->render('portal/carteira', [...], 'layouts/portal')`).
  2. No método `Controller::render()`, o path do layout era construído concatenando `__DIR__ . '/../views/layouts/' . $layout . '.php'`, resultando no caminho inexistente `app/views/layouts/layouts/portal.php`.
  3. Como o arquivo não era localizado por `file_exists()`, o `Controller` efetuava o fallback silencioso para renderização direta do fragmento da view (`require $viewPath;`), sem a estrutura HTML mestre, sem a tag `<head>` e sem os arquivos de estilo (`bootstrap.min.css` e `custom.css`).
- Solução aplicada:
  1. Em `app/controllers/CarteiraController.php`, corrigido o argumento para `'portal'`.
  2. Em `app/core/Controller.php`, aprimorada a resolução de layouts de forma defensiva: normalização automática removendo prefixos redundantes (`layouts/` ou `layouts\`) via expressão regular (`preg_replace('#^layouts[\\/]#', '', $layout)`) e fallback inteligente para caminhos alternativos relativos em `app/views/`, garantindo que qualquer invocação funcione perfeitamente.
  3. Atualizado o teste automatizado `tests/portal_saldo_test.php` com asserções estritas validando a presença de `<!DOCTYPE html>`, `bootstrap.min.css`, `custom.css`, `portal-header` e a aba ativa da carteira no HTML entregue pelo servidor.
- Como evitar no futuro: Padronizar e flexibilizar métodos de infraestrutura (como `render`) para aceitar variações comuns de sintaxe de desenvolvedores, além de criar testes automatizados de integração que verifiquem não apenas o status HTTP, mas também a presença da tag raiz e das folhas de estilo essenciais no HTML gerado.

---

## 2026-09-18 - Alerta "Não há nada para mostrar aqui" disparado indevidamente no topo do Portal da Família

- Sintoma: Ao acessar o Portal da Família ou a Carteira Digital Familiar com extrato vazio, um banner de alerta azul com o texto `Portal da Família — Colégio Monteiro Lobato: Não há nada para mostrar aqui` era exibido indevidamente no topo da página.
- Causa: Colisão e vazamento de escopo de variáveis:
  1. Na view `portal/carteira.php`, quando a lista de transações estava vazia, era incluído o componente `partials/empty_state.php`.
  2. Dentro de `empty_state.php`, a variável `$message` recebia o título do estado vazio (`"Não há nada para mostrar aqui"`). Como arquivos incluídos via `require` compartilham o escopo de variáveis da função `render()`, `$message` permaneceu viva na memória.
  3. Ao compilar o layout master `portal.php`, o componente `partials/alert.php` era chamado. Ele verifica se `$message` não está vazio para renderizar alertas explícitos.
  4. Como `$message` continha a string do `empty_state` e `$title` continha o título da página, o `alert.php` interpretou erroneamente como uma instrução para exibir um alerta no topo da página.
- Solução aplicada:
  1. Em `app/views/partials/empty_state.php`, renomeada a variável `$message` para `$emptyTitle` e adicionado bloco defensivo de limpeza (`unset`) para todas as variáveis locais do componente.
  2. Em `app/core/Controller.php`, implementado isolamento léxico estrito: a renderização do arquivo de view foi encapsulada dentro de uma closure anônima (`$renderViewIsolated = function(...)`), garantindo que nenhuma variável criada internamente na view ou em seus partials vaze para o escopo do layout master.
  3. Em `app/views/partials/alert.php`, adicionada limpeza ao final da renderização para eliminar referências temporárias.
  4. Criado teste de regressão em `tests/portal_saldo_test.php` garantindo que mesmo com `empty_state` ativo no corpo da tabela, o alerta de topo permanece 100% limpo e ausente (22/22 asserções aprovadas).
- Como evitar no futuro: Sempre isolar o escopo de renderização entre views e layouts master utilizando closures anônimas em frameworks MVC próprios, e evitar o uso de nomes de variáveis genéricos (como `$message`, `$data`, `$title`) em componentes parciais reutilizáveis.

---

## 2026-09-18 - "Cobrança não localizada" ao clicar em Simular Pagamento na Carteira Pix

- Sintoma: Ao gerar uma cobrança Pix dinâmica e clicar no botão "Simular Pagamento no Ambiente de Teste", um popup alertava `Cobrança não localizada.` (HTTP 404), impedindo a liquidação do Pix no teste do navegador.
- Causa: Dupla ocorrência identificada:
  1. Permissão restrita na pasta de cache: O diretório `storage/cache/pix` havia sido criado via CLI com permissões de `root:root (0755)`. Como as requisições HTTP do navegador rodam sob o usuário do Apache (`www-data`), o método `PixService::salvarCobrancaCache()` não conseguia escrever o arquivo JSON da cobrança em disco, falhando silenciosamente devido ao operador `@file_put_contents`.
  2. Ausência de fallback e reconstituição de cobrança: Ao chamar `/portal/recarga/simular/{txid}`, como o arquivo em disco não existia, o controller retornava 404 sem consultar a sessão do usuário ou reconstituir os dados da cobrança em modo de desenvolvimento local. Além disso, o JavaScript não enviava o valor da cobrança nem o token CSRF no FormData.
- Solução aplicada:
  1. Permissões ajustadas no contêiner para `storage/cache` (`chmod -R 777` e `chown -R www-data:www-data`), e no método `PixService::getCacheDir()` reforçada a garantia de permissões `0777` em runtime.
  2. Implementada redundância de persistência: `PixService::salvarCobrancaCache()` grava o arquivo em disco e, simultaneamente, armazena no array de sessão `$_SESSION['_pix_cache'][$txid]`, permitindo leitura imediata mesmo se houver falhas no filesystem.
  3. Em `CarteiraController::simularPagamento()`, adicionada reconstituição defensiva: se o ambiente estiver em modo de desenvolvimento local (`$isDev === true`) e o arquivo temporário não estiver disponível, o controller reconstitui a cobrança a partir do valor enviado no POST (com fallback para R$ 50,00) e liquida a transação normalmente.
  4. Atualizado o JavaScript `simularPagamentoDev()` em `app/views/portal/carteira.php` para repassar `valor` e `csrf_token`.
  5. Adicionado teste automatizado na suíte `tests/fase9_test.php` cobrindo a action de simulação tanto para cobranças em cache quanto para cobranças avulsas (69/69 asserções aprovadas).
---

## 2026-09-18 - Métodos utilitários ausentes e sensibilidade temporal na suíte de testes da Fase 10

- Sintoma: Erros fatais disparados durante a execução de `tests/fase10_test.php`: (1) `Call to undefined method App\Models\Categoria::buscarPorNome()`; (2) `DomainException: O cardápio da próxima semana só é liberado na sexta-feira às 18:00`; (3) `Call to undefined method App\Models\Pedido::buscarPorId()`; (4) `Call to undefined method App\Models\Produto::sincronizarAlergenos()`.
- Causa:
  1. Os modelos `Categoria` e `Pedido` possuíam métodos especializados (`listarTodas`, `buscarDetalhado`), mas não continham os métodos de consulta padronizados `buscarPorNome` e `buscarPorId`.
  2. O modelo `Produto` continha o método `syncAlergenos`, enquanto a suíte de testes e o padrão em português adotavam `sincronizarAlergenos`.
  3. A regra RN-01 de liberação semanal restringe agendamentos da semana seguinte para sextas-feiras a partir das 18:00. Como a suíte de testes calculava datas para a semana seguinte e era executada às sextas-feiras pela manhã (antes das 18:00), a exceção de negócio legítima era disparada devido à hora real do relógio do servidor.
- Solução aplicada:
  1. Implementado `Categoria::buscarPorNome(string $nome): ?array` em `app/models/Categoria.php`.
  2. Implementado `Pedido::buscarPorId(int $id): ?array` em `app/models/Pedido.php`.
  3. Adicionado o alias em português `Produto::sincronizarAlergenos(int $produtoId, array $alergenosIds): void` em `app/models/Produto.php`.
  4. Adicionado o método auxiliar `obterMomentoAberto(string $dataConsumo): \DateTimeImmutable` em `tests/fase10_test.php` para simular momentos canônicos válidos em que a semana está aberta e antes do horário de corte, tornando a suíte de testes determinística e independente da hora exata em que for executada.
---

## 2026-09-21 - TypeError ao invocar pedidoEditar() no Router por incompatibilidade de tipo (array vs int)

- Sintoma: Erro HTTP 500 com tela de incidente operacional exibindo `TypeError: App\Controllers\PortalController::pedidoEditar(): Argument #1 ($id) must be of type int, array given, called in /var/www/html/app/core/Router.php on line 259` ao clicar no botão "Editar" de um pedido agendado.
- Causa: O roteador HTTP (`Router.php`) extrai parâmetros dinâmicos da URL (como `{id}`) e repassava como um array associativo (`['id' => '...']`) para a action do controller. As actions `pedidoEditar(int $id)` e `pedidoEditarSalvar(int $id)` no `PortalController.php` declaravam tipagem estrita `int $id`, disparando erro fatal de tipo no PHP 8.2.
- Solução aplicada:
  1. Em `app/controllers/PortalController.php`, alteradas as assinaturas de `pedidoEditar` e `pedidoEditarSalvar` para `mixed $params`, resolvendo defensivamente o identificador com `$id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params`.
  2. Em `app/core/Router.php`, aprimorado o método `invokeControllerMethod` via introspecção com `ReflectionMethod`, mapeando e convertendo automaticamente parâmetros tipados (int, float, string) a partir dos nomes dos argumentos da rota.
  3. Em `app/models/Responsavel.php`, implementado o método estático `buscarPorId(int $id): ?array`.
  4. Criada suíte de testes de integração em `tests/pedido_editar_web_test.php` validando o ciclo completo de GET (renderização da tela de edição) e POST (gravação da alteração com compensação financeira).
- Como evitar no futuro: Em roteadores MVC próprios, suportar flexibilidade de injeção de parâmetros (tanto array de parâmetros quanto argumentos nomeados tipados via Reflection). Em controllers, garantir resolução defensiva de parâmetros que possam vir encapsulados em arrays.

---

## 2026-09-21 - Incompatibilidade com sql_mode=only_full_group_by no MySQL 8.0 em verificarStatusRecebimento()

- Sintoma: `SQLSTATE[42000]: Syntax error or access violation: 1055 Expression #1 of SELECT list is not in GROUP BY clause and contains nonaggregated column 'cantina.em.id'` ao invocar `Pedido::verificarStatusRecebimento()`.
- Causa: A consulta SQL de verificação de recebimento selecionava a coluna `em.id` sem função de agregação enquanto utilizava agrupamento por data, violando a diretiva padrão `sql_mode=only_full_group_by` do MySQL 8.0+.
- Solução aplicada: Refatorada a consulta em `app/models/Pedido.php` para selecionar apenas as colunas agrupadas e métricas agregadas (`COUNT(*)`, `SUM(em.quantidade)`), ordenando por `em.created_at DESC` com `LIMIT 1` para recuperar o registro mais recente da remessa.
- Como evitar no futuro: Em ambientes MySQL 8.0+, nunca incluir colunas não agregadas no `SELECT` quando utilizar `GROUP BY`. Em queries de busca de status mais recente, preferir `ORDER BY ... DESC LIMIT 1`.

## 2026-09-21 - filter_input(INPUT_GET) retornando null em testes automatizados / CLI

- Sintoma: Filtros de data e turno na tela de corte em `app/controllers/PedidosController.php` recaiam no valor padrão (data de hoje e turno matutino) durante a execução de testes automatizados e simulações web, mesmo com `$_GET['data']` devidamente preenchido.
- Causa: Em PHP, a função nativa `filter_input(INPUT_GET, ...)` lê diretamente dos buffers internos do SAPI do servidor web (Apache/FPM) e não da superglobal `$_GET`. Quando testes executados via CLI populam manualmente `$_GET`, `filter_input()` não enxerga essas alterações e retorna `null`.
- Solução aplicada: Substituído o uso de `filter_input(INPUT_GET, ...)` por verificação e sanitização direta sobre a superglobal: `(isset($_GET['campo']) && is_string($_GET['campo'])) ? trim(strip_tags($_GET['campo'])) : $padrao`.
- Como evitar no futuro: Em controllers de aplicações que precisam ser testáveis via CLI/PHPUnit, evitar `filter_input()` e utilizar sanitização defensiva sobre as superglobais (`$_GET`, `$_POST`) ou encapsular a requisição em um objeto Request injetável.

---

## 2026-09-21 - Tabela de conferência de recebimento exibindo valores estáticos para remessas já conferidas e ausência de submissão automática ao mudar datas

- Sintoma: Na tela `/operacional/recebimento`, ao trocar entre datas distintas (como 21/09 e 22/09) com remessas já recebidas, a tabela inferior parecia não atualizar, exibindo sempre a quantidade esperada no lugar da entregue, "Exato (0)" nas diferenças e "0 sobra" no rodapé consolidador, ignorando as quantidades físicas reais apuradas na entrega. Além disso, a troca de data no calendário nativo exigia clique manual no botão "Carregar Itens", causando a percepção de que a tabela não respondia à mudança de dia.
- Causa: Dupla ocorrência:
  1. No template `app/views/operacional/recebimento.php`, quando `$jaRecebido` era verdadeiro, o código renderizava a variável `$qtdEsp` na coluna de quantidade entregue, com texto estático "Exato (0)" na coluna de diferenças e "0 sobra" no rodapé consolidador, pois o método `verificarStatusRecebimento()` em `app/models/Pedido.php` trazia apenas totais agregados e não o detalhamento dos produtos entregues gravados em `estoque_movimentacoes`.
  2. No formulário de filtro por data e turno em `corte.php` e `recebimento.php`, os campos `<input type="date">` e `<select>` não possuíam o gatilho `onchange="this.form.submit()"`, e formatos de data `DD/MM/YYYY` não eram convertidos para `YYYY-MM-DD` no controller, caindo no fallback da data atual.
- Solução aplicada:
  1. Em `app/models/Pedido.php`, aprimorado `verificarStatusRecebimento()` para buscar e mapear os quantitativos reais entregues por produto a partir de `estoque_movimentacoes` (`itens_entregues`), permitindo que a view apresente exatamente o que foi entregue, sobras, faltas e excedentes reais de cada item e no rodapé.
  2. Em `app/views/operacional/recebimento.php`, implementado o cálculo analítico com base nos itens entregues da remessa para exibir a quantidade efetiva, status de diferença (`Exato (0)`, `+X un (sobra)` ou `-X un (falta)`) e o total consolidado real com excedente/falta destacados.
  3. Adicionado `onchange="this.form.submit()"` nos campos de data e turno de `corte.php` e `recebimento.php` para atualização reativa instantânea ao selecionar o dia ou turno.
  4. Em `app/controllers/PedidosController.php`, criado o método `normalizarDataConsumo()` para aceitar e converter de forma transparente tanto `YYYY-MM-DD` quanto `DD/MM/YYYY`.
---

## 2026-09-22 - Erro HTTP 404 em /operacional/tickets por ausência de registro prévio de rotas e erro de declaração estática em Model::$table

- Sintoma: Ao navegar para `http://localhost:8080/operacional/tickets` clicando no menu lateral ou digitando a URL, a aplicação retornava a página corporativa de erro `404 — Página Não Encontrada`. Em seguida, na instanciação de models, ocorria o erro fatal `Fatal error: Cannot redeclare non static App\Core\Model::$table as static App\Models\Ticket::$table`.
- Causa: Dupla ocorrência:
  1. A rota `/operacional/tickets` (e seus sub-recursos `/lote`, `/turma`, `/segunda-via`, `/imprimir`) não estava registrada no Front Controller `index.php`, pois a Fase 12 ainda não havia sido concluída quando a rota foi acessada.
  2. Nos modelos `Ticket.php` e `Auditoria.php`, a propriedade `$table` havia sido tipada incorretamente como `protected static string $table`, conflitando com a assinatura não-estática `protected string $table` declarada na classe base abstrata `App\Core\Model`.
- Solução aplicada:
  1. Registradas e protegidas por RBAC todas as 8 rotas operacionais do módulo de tickets em `index.php` (`/operacional/tickets`, `/operacional/tickets/lote`, `/operacional/tickets/lote/pdf`, `/operacional/tickets/turma/{id}`, `/operacional/tickets/turma/{id}/pdf`, `/operacional/tickets/segunda-via`, `/operacional/tickets/imprimir/{id}`, `/operacional/tickets/imprimir/{id}/pdf`), vinculadas ao `TicketsController`.
---

## 2026-09-22 - Erro fatal 500 (Incidente Operacional) ao emitir 2ª via de ticket térmico por método inexistente Session::flash()

- Sintoma: Ao submeter o formulário de reemissão de 2ª via no modal da tela `/operacional/tickets` (POST `/operacional/tickets/segunda-via`), o sistema retornava a tela corporativa de erro `500 — Incidente Operacional` com identificador de incidente (ex.: `148e201e-1cd8-0656`), e o log registrava: `[CRITICAL] Call to undefined method App\Core\Session::flash() no arquivo app/Controllers/TicketsController.php linha 279`.
- Causa: Dupla ocorrência:
  1. A classe `App\Core\Session` implementava o método com o nome `setFlash(string $type, string $message)` e não possuía o alias `flash()`. Em `TicketsController.php` (e em uma linha residual de `CarteiraController.php`), o código chamava `Session::flash()`.
  2. A persistência no banco de dados da 2ª via (incremento do campo `numero_via` para 2 e gravação do registro em `logs_auditoria`) havia ocorrido com sucesso absoluto. Porém, imediatamente após o commit, ao tentar registrar a mensagem de sucesso para a próxima tela com `Session::flash()`, o PHP disparava um `Error: Call to undefined method`. O bloco `catch (\Throwable $e)` capturava esse erro e tentava registrar a mensagem de erro novamente com `Session::flash()`, disparando um segundo erro fatal não capturado que acionava a tela 500 do `ErrorHandler`.
- Solução aplicada:
  1. Em `app/Core/Session.php`, criado o método estático `public static function flash(string $type, string $message): void` como um alias transparente para `self::setFlash($type, $message)`, garantindo compatibilidade total e prevenção contra chamadas com essa nomenclatura.
  2. Em `app/controllers/TicketsController.php` e `app/controllers/CarteiraController.php`, padronizadas todas as chamadas para `Session::setFlash()`.
  3. No modal de reemissão de 2ª via em `app/views/operacional/tickets.php`, o texto de aviso de auditoria foi aprimorado para explicar em linguagem amigável que o registro é permanente e não pode ser apagado (esclarecendo o significado do termo "indelével").
  4. Criado teste de integração em `tests/teste_segunda_via_post.php` validando o fluxo ponta a ponta (POST, transação, auditoria, sessão flash e redirecionamento para impressão).
---

## 2026-09-22 - Erro HTTP 404 ao acessar tela de Caixa de Turno (/operacional/caixa) via menu lateral ou dashboard

- Sintoma: Ao clicar no item "Caixa do Turno" no menu lateral (`app/views/layouts/sidebar.php`) ou no botão "Gerenciar Caixa" no painel administrativo (`app/views/admin/index.php`), a aplicação retornava a tela de erro `404 — Página Não Encontrada` para a rota solicitada `/operacional/caixa`.
- Causa: Os links de navegação para `/operacional/caixa` já estavam presentes no sidebar e no painel administrativo desde as fases de layout base e cadastros mestres. No entanto, o `CaixaController` e o registro das rotas de gestão de caixa haviam sido previstos para a Fase 15. Com a implementação do modelo `SessaoCaixa` e a exigência de caixa aberto introduzida na Fase 13 (PDV), os operadores tentavam acessar a rota de caixa pelo menu e encontravam 404.
- Solução aplicada:
  1. Criado `app/controllers/CaixaController.php` com as actions `index` (painel analítico do caixa, alternador de turno e histórico), `abrir` (abertura com fundo de troco), `fechar` (conferência física da gaveta, apuração de quebra/sobra e auditoria), `espelhoHtml` (espelho A4 de prestação de contas com visualização e acionamento de impressão) e `espelhoPdf` (geração de PDF 1.4 A4 timbrado para assinatura do operador e da gerência).
  2. Implementados no modelo `app/models/SessaoCaixa.php` os métodos `fechar()`, `obterTotaisEsperados()`, `listarHistorico()` e `obterUltimaSessao()`, com registro automático em `logs_auditoria`.
  3. Adicionados em `app/services/PdfService.php` os métodos `gerarEspelhoFechamentoCaixaHtml()` e `gerarEspelhoFechamentoCaixaPdf()`.
  4. Criada a view institucional `app/views/operacional/caixa.php` alinhada ao `docs/DESIGN.md` com calculadora dinâmica de quebra/sobra de caixa em JavaScript Vanilla.
  5. Registradas as 5 rotas sob `/operacional/caixa*` no Front Controller `index.php`, protegidas pela permissão RBAC dos perfis `operador` e `administrador`.
  6. Criada a suíte de testes automatizados `tests/caixa_test.php` com 38/38 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Ao incluir itens de menu ou atalhos de navegação para módulos de fases posteriores nos templates mestre (`sidebar.php`, `header.php`), registrar rotas placeholder funcionais ou garantir que todos os links expostos na interface tenham seus controladores e views operacionais correspondentes prontos para uso.

---

## 2026-09-22 - Tela do PDV do Balcão sem estilos (HTML puro) por cache de versão do CSS e ausência de regras autônomas nos componentes

- Sintoma: Ao navegar para `http://localhost:8080/operacional/pdv`, o cabeçalho e sidebar carregavam normalmente, mas a área de conteúdo do PDV (botões de categoria, campo de busca, catálogo de produtos, cupom de venda e modais) exibia botões cinzas padrão do navegador e blocos esticados verticalmente sem grid ("html puro pai").
- Causa:
  1. No layout mestre `app/views/layouts/admin.php` e `header.php`, a folha de estilo mantinha a tag `<link rel="stylesheet" href=".../custom.css?v=5.1.1">`. Como a versão `v=5.1.1` já havia sido baixada e armazenada no cache do navegador nas fases anteriores, o navegador do usuário continuou utilizando a versão antiga em cache que não continha as regras `.pdv-*` adicionadas na Fase 13.
  2. As classes dos modais customizados (`.modal-backdrop-custom`, `.modal-card-custom`, etc.) não haviam sido declaradas no CSS global.
  3. A media query do grid `.pdv-grid` utilizava quebra em 1100px (`@media (max-width: 1100px)`), o que causava o colapso indevido do terminal de duas colunas para uma coluna em notebooks com resolução padrão de 1366x768 ou escala de 125%/150%, empurrando o cupom de venda para baixo dos produtos.
- Solução aplicada:
  1. Embutida folha de estilo dedicada e autônoma `<style>` diretamente no topo da view `app/views/operacional/pdv.php`, garantindo que o PDV seja 100% imune a falhas de cache de proxy ou navegador.
  2. Refatorado o bloco `.pdv-*` em `assets/css/custom.css` com estilos de botões com reset de aparência, cards com miniaturas e hover tátil suave, cupom de venda responsivo, atalhos visuais e todos os modais operacionais.
  3. Ajustada a responsividade do `.pdv-grid` para manter o layout corporativo de 2 colunas (catálogo à esquerda + cupom/caixa à direita) em qualquer tela a partir de 960px, ideal para laptops de 1366x768 e terminais POS.
  4. Atualizada a query de versão dos assets para `?v=5.2.0` em `app/views/layouts/admin.php` e `app/views/layouts/header.php` para forçar invalidação imediata de cache no cliente.
  5. Adicionado atalho de teclado `F3` (foco na busca de produtos) e `F10` (conclusão de venda).
- Como evitar no futuro: Sempre que novos módulos ou estilos críticos forem adicionados a `custom.css`, incrementar o versionamento de cache (`?v=...`) nos layouts correspondentes e incluir regras de contingência em telas operacionais de alta densidade como o PDV.

---

## 2026-09-22 - Divergência visual e de conteúdo entre o Espelho HTML e o arquivo PDF de Fechamento de Caixa

- Sintoma: Ao confrontar o espelho web de fechamento (`/operacional/caixa/espelho/{id}`) com o documento PDF baixado (`/operacional/caixa/espelho/{id}/pdf`), constatou-se que o PDF apresentava layout divergente (barra azul vertical em vez da linha horizontal institucional), ausência de cabeçalho na tabela financeira, strings sem acentuação (*"Balcao"*, *"Diferenca"*), sem cor semântica na quebra de caixa e omissão completa do bloco de observações/justificativas registradas.
- Causa: O método `gerarEspelhoFechamentoCaixaHtml` utilizava template HTML/CSS contemporâneo com tabela meta-grid em 4 colunas, acentuação UTF-8 e bloco condicional para o campo `observacoes`. Por sua vez, o gerador binário nativo `gerarEspelhoFechamentoCaixaPdf` utilizava primitivas vetoriais manuais (`BT ... ET`) com strings estáticas resumidas, sem a grade de metadados, sem o cabeçalho da tabela de dados e sem a leitura da variável `$sessao['observacoes']`.
- Solução aplicada:
  1. Refatorado o método `PdfService::gerarEspelhoFechamentoCaixaPdf()` para espelhar com exatidão a apresentação e os dados do HTML.
  2. Implementado no PDF o cabeçalho institucional com linha azul cobalto (#1d4ed8) de 2pt, grade de metadados de 4 colunas x 2 linhas com bordas e rótulos técnicos, cabeçalho da tabela (`DISCRIMINAÇÃO DOS RECURSOS FINANCEIROS` | `VALOR APURADO`), termos acentuados oficiais em português via Windows-1252, cálculo de quebra/sobra com destaque colorido em vermelho/verde e renderização dedicada para o quadro de observações/justificativas registradas no fechamento.
  3. Alinhado o bloco de assinaturas do Operador de Caixa e da Coordenação Administrativa.
- Como evitar no futuro: Ao implementar geradores duais de documentos (HTML para tela/impressão rápida e PDF binário para download), assegurar paridade estrita de dicionário de dados, rótulos, campos condicionais (como observações) e identidade visual corporativa.

---

## 2026-09-22 - Conflito de Turno no PDV e Sessão Matutina Residual Aberta à Tarde

- Sintoma: No terminal de PDV (`/operacional/pdv`) no período da tarde (16:16), o cabeçalho superior informava `Turno: Vespertino | Caixa: Aberto`, enquanto o corpo do PDV exibia `● Caixa Aberto (Turno Matutino)`. Ao mesmo tempo, na tela de Caixa (`/operacional/caixa`), o turno matutino aparecia com sessão aberta (#40) e o vespertino como encerrado (#46).
- Causa:
  1. No `PdvController.php` (linhas 36-40), havia um fallback indevido:
     ```php
     $turnoAtual = SessaoCaixa::inferirTurnoAtual();
     $sessaoAberta = SessaoCaixa::obterSessaoAberta($turnoAtual);
     if (!$sessaoAberta) {
         $sessaoAberta = SessaoCaixa::obterSessaoAberta('matutino') ?? SessaoCaixa::obterSessaoAberta('vespertino');
     }
     ```
     Quando o operador acessava o PDV à tarde e o caixa vespertino ainda não havia sido aberto, o código "sequestrava" a sessão matutina que ainda constava aberta, associando as vendas vespertinas à sessão errada da manhã.
  2. A suíte de testes automatizados `tests/fase13_test.php` abria a sessão matutina #40 para simular vendas de balcão e não a encerrava ao término da execução, deixando-a como resíduo aberto no banco de desenvolvimento local. Enquanto isso, `tests/caixa_test.php` abria e encerrava o turno vespertino para validar apuração de quebra e sobra, deixando o turno da tarde como fechado.
  3. Descompasso de timezone no contêiner Docker: a conexão PDO utilizava o timezone UTC padrão do MySQL (`+00:00`), enquanto o horário de Brasília opera em UTC-3 (`America/Sao_Paulo`), gerando discrepâncias entre `NOW()` do MySQL e `date()` do PHP.
- Solução aplicada:
  1. Configurado o fuso horário padrão oficial do Brasil (`America/Sao_Paulo`) e sincronizado o MySQL via `\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$collation}, time_zone = '-03:00'"` em `config/database.php`.
  2. Removido o fallback permissivo de `PdvController.php`. O PDV agora respeita estritamente o turno inferido pelo horário do relógio (`< 12:00` matutino, `>= 12:00` vespertino) ou o turno explicitamente solicitado via `?turno=...`.
  3. No template `app/views/operacional/pdv.php`, se o caixa do turno atual estiver fechado, exibe claramente `● Caixa Fechado (Turno Vespertino)` e o botão "Abrir Caixa do Turno Vespertino".
  4. Adicionado alerta operacional no PDV caso o caixa do outro turno (ex: matutino) tenha sido esquecido aberto, com link direto para encerramento e prestação de contas na tela de Caixa.
  5. Adicionado seletor rápido de turno (Manhã / Tarde) no stat-strip do PDV e atalho de teclado `F7` para ir à tela de Caixa.
  6. Refatorada a suíte `tests/fase13_test.php` para encerrar atomicamente qualquer sessão aberta de teste ao finalizar, garantindo que o banco de dados não mantenha sessões fantasmas.
- Como evitar no futuro: Em sistemas financeiros com segregação por turno, nunca permitir que a interface ou os modelos reutilizem sessões de turnos distintos automaticamente via fallback. Cada turno deve ter seu próprio ciclo de vida explícito de abertura, suprimento, vendas e fechamento cego com prestação de contas.










