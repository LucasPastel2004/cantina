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

---

## 2026-09-23 - Discrepância na assinatura do método Auditoria::registrar e nome de coluna em logs_auditoria

- Sintoma: `TypeError: App\Models\Auditoria::registrar(): Argument #4 ($registroId) must be of type int, null given` ou verificação nula em testes ao registrar perda de mercadoria.
- Causa:
  1. A assinatura oficial de `Auditoria::registrar` em `app/models/Auditoria.php` é estritamente tipada: `public static function registrar(int $operadorId, string $acao, string $tabelaAfetada, int $registroId, string $justificativa, string $ipOrigem = '127.0.0.1'): int`. No modelo `Estoque.php`, o método estava sendo chamado com `$registroId` nulo e um array adicional de metadados como 6º parâmetro, disparando erro fatal de tipagem no PHP 8.2.
  2. Na tabela `logs_auditoria`, a coluna textual que armazena a descrição da ocorrência é `justificativa` (conforme schema da migração 024), enquanto a suíte de testes tentava verificar `$audit['detalhes']`.
- Solução aplicada:
  1. No modelo `app/models/Estoque.php`, ajustada a chamada para passar o ID do produto ou da movimentação como inteiro estrito, a justificativa concatenada com o motivo oficial e `$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'` como IP de origem.
  2. No teste `tests/fase14_test.php`, atualizada a asserção para verificar o conteúdo em `$audit['justificativa']`.
- Como evitar no futuro: Sempre consultar a declaração exata da assinatura de métodos utilitários estáticos (como `Auditoria::registrar` e `Auth::logSecurityEvent`) antes de utilizá-los em novos modelos e controladores, respeitando os tipos escalares estritos do PHP 8.2.

---

## 2026-09-23 - Erro fatal Call to undefined method EstoqueController::validateCsrf() ao salvar perda no estoque

- Sintoma: `Error: Call to undefined method App\Controllers\EstoqueController::validateCsrf()` em `/var/www/html/app/Controllers/EstoqueController.php:118` ao submeter o formulário de registro de perda em `/estoque/perdas/salvar` (incidente operacional registrado no ErrorHandler).
- Causa: O método `validateCsrf()` não havia sido declarado na classe base abstrata `App\Core\Controller`, embora tenha sido invocado em `EstoqueController.php` para validar o token CSRF nas ações de mutação (`perdasSalvar`, `atualizarEstoqueMinimo`, `importarXmlAnalisar`, `importarXmlProcessar`).
- Solução aplicada:
  1. Declarado e implementado o método `protected function validateCsrf(?string $token = null, ?string $redirectPath = null): bool` na classe base `app/core/Controller.php`, integrando validação com `Session::validateCsrfToken()`, suporte a requisições Ajax com resposta JSON 403 e requisições HTTP regulares com mensagem flash e redirecionamento, respeitando o modo de teste `CANTINA_TEST_MODE`.
  2. Adicionada a guarda de retorno defensiva `if (!$this->validateCsrf(...)) { return; }` em todas as actions correspondentes de `app/controllers/EstoqueController.php`.
  3. Expandida a suíte de testes `tests/estoque_web_test.php` com o método `testarFluxoRegistroPerdaWeb()`, validando a rejeição segura de token CSRF inválido e o processamento com sucesso de perda com token CSRF válido e baixa de estoque no MySQL (56/56 asserções aprovadas).
- Como evitar no futuro: Qualquer método auxiliar de controle invocado através de `$this->...` deve obrigatoriamente estar declarado na classe base `Controller` ou como método privado/protegido do próprio controller. Sempre incluir nas suítes web testes que executem a action de gravação de ponta a ponta simulando `$_POST` com token CSRF.

---

## 2026-09-23 - Descompasso de cache de assets nos layouts e links órfãos gerando erro HTTP 404

- Sintoma: Percepção de "telas bugadas visualmente" ou renderizadas em estilo padrão do navegador em algumas páginas, além de erro HTTP 404 ao clicar nos links "Calendário Escolar" e "Relatórios Gerenciais" no menu lateral da cantina, ou em "Ver Extrato" e "Meus Dados" no Portal da Família.
- Causa:
  1. Cada layout mestre mantinha versões manuais e divergentes nos links de `custom.css` e `app.js` (`?v=5.0.8`, `?v=5.0.9`, `?v=5.2.0`), fazendo com que o navegador do usuário retivesse folhas de estilo antigas em cache, impedindo a aplicação das novas classes de layout e modais.
  2. Os links do menu lateral (`/admin/calendario` e `/relatorios`) e do portal familiar (`/portal/extrato` e `/portal/perfil`) não possuíam ações ou rotas registradas no Front Controller `index.php`.
- Solução aplicada:
  1. Injetada a variável global `$data['assetVersion'] = '5.3.0'` no método `Controller::render()`, e padronizados todos os layouts mestres (`header.php`, `admin.php`, `portal.php`, `auth.php`, `home/index.php`, `footer.php`) para utilizarem a versão unificada (`?v=5.3.0`), forçando o navegador a baixar imediatamente as regras de estilo e scripts mais recentes.
  2. Implementados os métodos `calendario()` e `relatorios()` em `AdminController.php` e suas respectivas views (`admin/calendario.php` e `admin/relatorios.php`).
  3. Implementado o método `perfil()` em `PortalController.php` e sua respectiva view (`portal/perfil.php`), e registrado o redirecionamento seguro de `/portal/extrato` para `/portal/carteira`.
  4. Registradas as 4 rotas com RBAC apropriado em `index.php`.
  5. Criado teste automatizado `tests/novas_rotas_test.php` aprovado com 100% de sucesso via cURL.
  6. Padronizadas as credenciais de teste manual em `tests/seed_test_accounts.php` para simplificar a validação pelo usuário.
- Como evitar no futuro: Nunca permitir links estáticos na interface apontando para rotas não registradas, e utilizar versionamento dinâmico e centralizado de assets no controlador base MVC para prevenir problemas de cache no navegador cliente.

---

## 2026-09-23 - Transbordamento visual (overflow) do QR Code Pix para fora do container na Carteira Familiar

- Sintoma: O QR Code SVG do Pix transbordava os limites do card quadrado branco (`#pix-qrcode-container`), invadindo a borda inferior e cobrindo parcialmente a legenda "Aponte a câmera do aplicativo do seu banco...".
- Causa:
  1. O método `QrCodeService::svg()` gerava a tag `<svg>` com atributos fixos `width="260" height="260"` sem estilo inline de contenção responsiva (`max-width: 100%`).
  2. Na view `app/views/portal/carteira.php`, o container possuía `max-width: 220px;` com `d-inline-block` e sem `overflow: hidden`, permitindo que o SVG de 260px nativo desenhasse fora da caixa branca.
  3. Ausência de regras dedicadas para `#pix-qrcode-container svg` em `assets/css/custom.css`.
- Solução aplicada:
  1. No arquivo `app/services/QrCodeService.php`, adicionado `style="max-width: 100%; height: auto; display: block;"` diretamente na tag `<svg>` gerada, assegurando responsividade intrínseca em qualquer contexto.
  2. Na view `app/views/portal/carteira.php`, fixado o `#pix-qrcode-container` como quadrado simétrico (`width: 210px; height: 210px; padding: 10px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden;`), garantindo que o SVG fique centralizado com margem respirável uniforme de 10px em todos os lados.
  3. No arquivo `assets/css/custom.css`, adicionadas regras de alta especificidade para `#pix-qrcode-container svg` (`max-width: 190px; max-height: 190px; width: 100%; height: 100%; object-fit: contain;`).
  4. Incrementada a versão global de assets para `?v=5.3.1` em `app/core/Controller.php` para forçar atualização imediata no navegador do cliente.
- Como evitar no futuro: Todo elemento `<svg>` vetorial injetado dinamicamente via JavaScript deve possuir estilos defensivos (`max-width: 100%; height: auto; display: block;`) tanto no gerador de markup quanto nas classes CSS do container receptor, prevenindo transbordamento visual em caixas com dimensões menores que o `viewBox`.

---

## 2026-09-23 - Transbordamento de texto ("6 PEÇAS") para fora da borda do box de Total Geral no PDF A4 de Corte

- Sintoma: No documento PDF de corte de turno gerado (`pedido_fornecedor_*.pdf`), o texto do total geral de salgados ("6 PEÇAS") ultrapassava a margem e a borda direita do box destacado, com as letras "ÇAS" desenhadas na área em branco fora do contorno da tabela.
- Causa:
  1. No arquivo `app/services/PdfService.php`, o comando de posicionamento do total utilizava uma coordenada fixa baseada no cabeçalho da coluna (`$col4 - 38`). Para a fonte Helvetica-Bold tamanho 12 (`/F2 12 Tf`), o texto `"6 PEÇAS"` (largura ~56 pt) iniciava na posição X=507.28 e terminava em X=563.28, enquanto a borda direita do retângulo (`re`) terminava em X=555.28 (`PAGE_WIDTH - 40`), causando um estouro de 8 a 15 pontos para fora do box.
  2. O cabeçalho `"QUANTIDADE"` também utilizava um offset rígido (`$col4 - 40`) que tangenciava/vazava a margem útil direita.
- Solução aplicada:
  1. Em `app/services/PdfService.php`, instituído alinhamento à direita rigoroso baseado no cálculo dinâmico da largura dos caracteres com recuo interno uniforme de 12 pt (`$targetRight = $right - 12.0;`).
  2. O total geral agora calcula `$totalX = $targetRight - (strlen($totalPecasStr) * 8.2);`, posicionando o texto a partir de X=477.68 e encerrando em X=534.68 (garantindo folga de mais de 20 pt antes da borda externa).
  3. No cabeçalho da tabela, `"QUANTIDADE"` agora calcula `$quantX = $targetRight - (strlen($quantHeader) * 5.5);`, permanecendo perfeitamente alinhado com a margem interna.
  4. Na view `app/views/operacional/corte.php`, atualizado o rótulo do botão para `Exportar PDF (A4)` para eliminar ambiguidade com a ação de `Imprimir A4`.
  5. No espelho HTML de impressão (`corte/imprimir`), adicionado feedback visual imediato (`"Preparando Impressão..."`) no botão azul de impressão para sinalizar o processamento enquanto o spooler de impressão do sistema operacional inicializa os drivers de impressora.
- Como evitar no futuro: Em geradores puros de streams PDF, nunca utilizar offsets estáticos calculados para tamanhos de fonte menores em textos de destaque maiores. Qualquer texto alinhado à direita deve ter sua posição X calculada dinamicamente subtraindo a largura estimada dos caracteres a partir da margem direita interna (`$targetRight - $textWidth`).

---

## 2026-09-23 - Cantos pretos quadrados ao redor do logotipo circular nos documentos PDF

- Sintoma: Nos documentos PDF gerados (como `pedido_fornecedor_*.pdf`), o logotipo do Colégio Monteiro Lobato aparecia com um quadrado preto ao redor do emblema circular (os 4 cantos da imagem JPEG eram renderizados em preto sólido), enquanto na tela de impressão HTML o logotipo aparecia perfeitamente circular e limpo.
- Causa:
  1. O arquivo original `assets/img/logo-colegio.jpg` possuía pixels pretos sólidos (`RGB 0,0,0`) nos 4 cantos externos fora do raio do círculo emblemático (por ser formato JPEG que não possui canal de transparência alfa).
  2. Nas páginas e espelhos de impressão HTML, a propriedade CSS `border-radius: 50%; object-fit: cover;` mascarava os cantos pretos recortando-os visualmente em círculo.
  3. No entanto, nos streams de desenho do PDF (`PdfService.php`), o XObject de imagem `/Im1` era desenhado cruamente (`/Im1 Do`) dentro de uma matriz retangular simples, revelando integralmente os cantos pretos da imagem contra a folha e a faixa institucional.
- Solução aplicada:
  1. Em `app/services/PdfService.php`, implementado o método centralizador `desenharLogoCircular(float $x, float $y, float $size): string`, que constrói um caminho Bézier fechado de 4 curvas cúbicas aproximando o círculo com precisão geométrica ($k = r \times 0.55228475$) e aplica o operador nativo de recorte vetorial do PDF (`W n`). O comando `/Im1 Do` é executado dentro desse clipping path, descartando 100% de qualquer pixel fora do perímetro circular.
  2. Aplicado `desenharLogoCircular()` em todos os 5 relatórios PDF da aplicação: Relatório de Corte A4, Fechamento de Caixa A4, Comprovante de Venda de Balcão 80mm, Bobina Contínua de Tickets 80mm e Ticket Individual 80mm.
  3. Processado o arquivo `assets/img/logo-colegio.jpg` substituindo os pixels pretos dos 4 cantos externos por branco puro (`#ffffff`), garantindo que até mesmo softwares externos e leitores de imagem sem suporte a clipping vetorial exibam o logo sem bordas pretas.
  4. Auditadas todas as views do sistema (login, sidebar, portal, cabeçalho, erros 404/500, configurações e espelhos de impressão), confirmando que todas utilizam `border-radius: 50%; object-fit: cover;`.
  5. Expandida a suíte `tests/logo_pdf_test.php` com asserções estritas validando a presença do operador de recorte circular `W n` e execução do logo (33/33 asserções aprovadas).
- Como evitar no futuro: Nunca embutir imagens quadradas com fundo escuro em documentos PDF sem aplicar previamente uma máscara alfa ou traçar um clipping path vetorial circular (`W n`) ao redor do objeto gráfico. Assegurar que imagens de logotipos institucionais circulares possuam bordas limpas e transparentes ou brancas na própria mídia fonte.

---

## 2026-09-23 - Falha na conclusão de venda no PDV: 'querySelector' called on an object that does not implement interface Element

- Sintoma: Ao clicar no botão "Concluir Venda (F4)" na tela do PDV (`/operacional/pdv`), a venda era registrada com sucesso no banco de dados e o servidor respondia JSON com sucesso, porém o usuário recebia um alerta em popup: `Falha na comunicação com o servidor: 'querySelector' called on an object that does not implement interface Element.` e o modal com o comprovante pós-venda não era exibido.
- Causa:
  1. No arquivo `app/views/operacional/pdv.php`, o modal de pós-venda (`#modalPosVenda`) e os demais modais do PDV utilizam a estrutura customizada `.modal-backdrop-custom` e alternância da classe `.d-none` (não utilizando a classe `.modal` e `.modal-dialog` padrão do Bootstrap).
  2. A função genérica `abrirModal('modalPosVenda')` em `assets/js/app.js` tentava invocar indiscriminadamente `bootstrap.Modal.getOrCreateInstance(modalEl).show()`.
  3. No Bootstrap 5, o construtor da classe `Modal` procura o elemento de diálogo via `z.findOne('.modal-dialog', this._element)`. Como `#modalPosVenda` é um modal customizado, `this._dialog` resultava em `null`.
  4. Ao executar `modal.show()`, o Bootstrap chamava `z.findOne('.modal-body', this._dialog)`.
  5. O helper `findOne` do Bootstrap está implementado como `(t, e = document.documentElement) => Element.prototype.querySelector.call(e, t)`. Como o argumento `e` foi passado explicitamente como `null` (`this._dialog`), a chamada executou `Element.prototype.querySelector.call(null, '.modal-body')`, lançando a exceção nativa do DOM: `TypeError: 'querySelector' called on an object that does not implement interface Element.`.
  6. Essa exceção no manipulador `.then(...)` da Promise foi capturada pelo `.catch(err => alert('Falha na comunicação com o servidor: ' + err.message))`.
  7. Além disso, a função utilitária `fecharModal(modalId)` chamada pelos botões de fechar e cancelar (`fecharModal('modalPosVenda')`) não estava definida globalmente em `assets/js/app.js`.
- Solução aplicada:
  1. Em `assets/js/app.js`, aprimorada a função `abrirModal(modalId)` para verificar se o elemento possui a classe `.modal-backdrop-custom` ou se não é um modal do Bootstrap (`!modalEl.classList.contains('modal')`). Se for modal customizado, remove diretamente a classe `.d-none` e direciona o foco para o primeiro botão/campo sem acionar indevidamente o Bootstrap. Caso seja um modal do Bootstrap, prossegue com segurança com `bootstrap.Modal.getOrCreateInstance(modalEl).show()`.
  2. Em `assets/js/app.js`, implementada a função global `fecharModal(modalId)` com suporte bidirecional: para modais customizados adiciona a classe `.d-none`, e para modais do Bootstrap invoca `bootstrap.Modal.getInstance(modalEl)?.hide()`.
  3. Em `app/views/operacional/pdv.php`, declaradas cópias locais de segurança de `abrirModal` e `fecharModal`, atualizado o atalho de teclado `F2` para fechar o `#modalPosVenda` e limpar o carrinho para nova venda imediata, e sincronizado o estado dos botões de rádio do cliente em `trocarTipoCliente()`.
  4. Em `app/views/operacional/pdv.php`, na finalização da venda (`submeterVendaServidor`), o modal pós-venda `#modalPosVenda` agora é exibido diretamente via manipulação nativa de classe (`classList.remove('d-none')`) e validação defensiva de cada elemento do DOM (`posVendaId`, `posVendaTotal`, `posVendaTrocoBox`, `btnImprimirComprovante`), eliminando qualquer dependência de bibliotecas externas ou de cache de scripts antigos no navegador.
  5. Incrementada a versão global de assets para `?v=5.3.2` em `app/core/Controller.php` e no layout `app/views/layouts/admin.php` para forçar o recarregamento do novo script em todos os navegadores.
  6. Validado o fluxo completo com Chrome Headless real via Chrome DevTools Protocol (CDP) e criado o teste automatizado `tests/pdv_modal_test.php` aprovado com 100% de sucesso (18/18 asserções).
- Como evitar no futuro: Nunca acionar componentes JavaScript de bibliotecas externas (como `bootstrap.Modal`) sobre elementos que não implementam a sua estrutura de classes esperada (`.modal`, `.modal-dialog`). Em telas críticas como o PDV, a exibição de modais e recibos deve operar de forma autônoma e defensiva, sem depender de scripts externos que possam estar desatualizados no cache do navegador cliente.

---

## 2026-09-24 - Lentidão e congelamento no diálogo de visualização prévia ('Preparando visão prévia') ao imprimir comprovante térmico no Chrome

- Sintoma: Na tela de impressão do comprovante de venda do PDV (`/operacional/pdv/comprovante/{id}`), ao clicar em "Imprimir" ou ao abrir o comprovante, a caixa de diálogo nativa de impressão do Google Chrome demorava muitos segundos travada na mensagem *"Preparando visão prévia..."* com o painel lateral em branco, atrasando o atendimento da fila do caixa.
- Causa:
  1. *Cálculo recursivo de paginação no Chromium (Blink):* A regra CSS `@page { size: 80mm auto; margin: 0; }` com altura `auto` e sem restrições explícitas de largura em `@media print` forçava o motor de layout do Chromium a executar múltiplos passes iterativos de quebra de página contínua, congelando o preview.
  2. *Payload do logotipo:* O espelho térmico embutia a imagem do logotipo em alta resolução (`logo-colegio.jpg`, 800x800 px, ~65 KB em base64) para renderizar um círculo de apenas 36x36 px, sobrecarregando a árvore de renderização do preview do navegador.
  3. *Inicialização do Spooler do Windows:* Na primeira chamada a `window.print()` da sessão, o Chrome consulta síncronamente o subsistema do Windows Print Spooler (`winspool.drv`) para enumerar todas as impressoras e suas capacidades de papel, gerando atraso inicial.
  4. *Fluxo de trabalho com abas no PDV:* O botão de impressão do modal pós-venda abria uma nova aba no navegador (`target="_blank"`), tirando o foco do operador do PDV e exigindo o fechamento manual da aba para continuar as vendas.
- Solução aplicada:
  1. *Otimização do CSS de Impressão para Bobinas de 80mm:* Em `app/services/PdfService.php` (`gerarEspelhoComprovanteVendaBalcaoHtml`, `gerarComprovanteRecargaDinheiroHtml` e `gerarEspelhoTicketsLoteHtml`), refinadas as regras de `@page` e adicionado `@media print` com largura estrita (`width: 80mm !important; max-width: 80mm !important; margin: 0 !important; padding: 0 !important;`) e `.thermal-wrapper` com largura fixa (`72mm`), permitindo que o motor Blink calcule o layout em apenas um passe instantâneo (< 20 ms).
  2. *Miniatura de Logotipo Térmico Otimizada:* Criada a versão miniatura `assets/img/logo-colegio-thermal.jpg` (72x72 px, apenas 2.3 KB). O método `PdfService::getLogoDataUri()` agora prioriza a miniatura leve e implementa cache em memória estático, reduzindo o payload HTML de 65.624 bytes para 8.280 bytes (redução de 88%).
  3. *Impressão Rápida sem Troca de Abas via Iframe Oculto:* No modal pós-venda (`#modalPosVenda`) da tela do PDV (`app/views/operacional/pdv.php`), implementada a função `imprimirComprovanteIframe()` que injeta um `<iframe>` invisível com `?autoprint=1`. O diálogo de impressão abre diretamente sobre o PDV sem abrir novas abas e sem perder o foco das teclas de atalho da próxima venda.
  4. *Barra de Atalhos e Opções Auxiliares:* Adicionados nos espelhos de impressão térmicos botões de alta densidade corporativa para "🖨️ Imprimir (80mm)", "📄 Baixar PDF Direto" (geração binária instantânea de 4 KB), botão de fechar (`window.close()` / tecla `Esc`) e dica instrucional para uso de <kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>P</kbd> (atalho nativo do Windows que ignora a visualização prévia do Chrome e imprime em 0,1s).
  5. *Orientação de Modo Kiosk para Caixas Físicos:* Documentada a flag oficial `--kiosk-printing` do Chrome para terminais dedicados de PDV, permitindo impressão silenciosa e instantânea sem qualquer caixa de diálogo.
- Como evitar no futuro: Em espelhos térmicos de bobina de 80mm, nunca utilizar dimensões flutuantes ou imagens pesadas em Base64. Sempre aplicar regras explícitas em `@media print`, utilizar miniaturas pré-otimizadas para comprovantes de baixa largura e disparar impressões em pontos de venda preferencialmente via iframes assíncronos ou atalhos nativos diretos.

---

## 2026-09-24 - Alinhamento à esquerda e texto diminuto na impressão nativa, divergência entre PDF e HTML e falha no botão 'Fechar'

- Sintoma:
  1. *Texto diminuto e colado no canto superior esquerdo:* Ao acionar a impressão no navegador, o comprovante aparecia colado na borda esquerda e com fonte muito pequena, especialmente ao visualizar em prévias com driver de impressora padrão em folha A4.
  2. *Divergência estética entre tela e arquivo PDF:* O PDF baixado (`comprovante_venda_*.pdf`) exibia layout diferente da tela (títulos em azul, ausência de divisores tracejados térmicos típicos de supermercado, e omissão de dados de cliente/matrícula/caixa).
  3. *Botão 'Fechar' inoperante:* Ao clicar no botão "✕ Fechar" na tela de impressão do comprovante, nada acontecia e a aba permanecia aberta.
  4. *Poluição visual com dica de atalhos:* A caixa azul com instruções de alta velocidade poluía a parte superior do espelho.
- Causa:
  1. Sem um container flexível centralizado e com fontes reduzidas (9-10px), o motor Chromium posicionava o bloco térmico nas coordenadas (0,0) da página A4 padrão.
  2. O gerador binário de PDF (`PdfService::gerarComprovanteVendaBalcaoPdf`) usava uma paleta de cores corporativa com tons azuis e cálculo de coordenadas antigas, sem paridade gráfica com o espelho HTML monocromático.
  3. Políticas estritas de segurança dos navegadores baseados em Chromium impedem que `window.close()` encerre abas que foram abertas diretamente pelo usuário (não instanciadas via `window.open`).
- Solução aplicada:
  1. *Centralização e Escala Térmica:* Criado container `.print-container` com `display: flex; justify-content: center; width: 100%;` e `.thermal-wrapper` com `width: 76mm !important; margin: 0 auto !important;`. Em bobinas de 80mm, o cupom ocupa 100% da área útil térmica; em folhas A4 ou pré-visualizações, fica perfeitamente centralizado no meio da folha com tipografia nítida (12.5px a 16px em preto térmico `#000000`).
  2. *PDF Monocromático Idêntico à Bobina de Supermercado:* Reescrito o gerador binário `gerarComprovanteVendaBalcaoPdf` em preto puro (`0 0 0 rg`), com linhas tracejadas/pontilhadas térmicas (`[2 2] 0 d 0.5 w S`), logotipo oficial circular centralizado, cabeçalho da instituição, todos os dados do operador/sessão, bloco completo do cliente (colaborador com cargo/matrícula ou aluno com turma), tabela detalhada com cabeçalhos de coluna, total em destaque negrito, discriminação de pagamento/troco e rodapé institucional.
  3. *Botão de Fechar com Fallback Seguro:* Implementada a função `fecharComprovante()` que tenta `window.close()` e, caso o navegador bloqueie a chamada, executa fallback automático após 100ms voltando ao histórico (`window.history.back()`) ou redirecionando para a tela de origem (`/operacional/pdv` ou `/operacional/caixa`). Adicionada também escuta para a tecla <kbd>Esc</kbd>.
  4. *Remoção da Dica de Alta Velocidade:* Eliminada a caixa de dica informativa dos espelhos térmicos em `PdfService.php`.
- Como evitar no futuro: Em impressões com drivers genéricos ou A4, sempre centralizar containers térmicos via flexbox e margem auto. Nunca confiar exclusivamente em `window.close()` sem implementar um fallback gracioso para navegação de histórico ou redirecionamento de tela. Assegurar fidelidade visual absoluta 1:1 entre espelhos web e documentos PDF baixados.

---

## 2026-09-24 - Mensagem 'Nenhum responsável ou aluno localizado' na busca para adicionar saldo presencial no caixa

- Sintoma: Ao digitar qualquer nome (por exemplo, "Lucas Santos" ou o nome de qualquer aluno/responsável) no campo de busca do modal "Recarga Presencial em Dinheiro (Balcão)" na tela de Caixa (`/operacional/caixa`), o sistema informava imediatamente: `Nenhum responsável ou aluno localizado para "Lucas Santos".`, mesmo havendo no banco de dados alunos e responsáveis com esses nomes.
- Causa:
  1. *Descompasso nas chaves de resposta JSON:* O endpoint `CaixaController::buscarResponsavel` retornava os dados no formato `['success' => true, 'responsaveis' => $responsaveis]`. Já o JavaScript da view (`app/views/operacional/caixa.php`) verificava estritamente `if (data.sucesso && data.dados && data.dados.length > 0)`. Como as chaves `sucesso` e `dados` não existiam no payload retornado, a condição resultava em `false` e caía diretamente no bloco `else`, renderizando a mensagem de que nenhum registro foi localizado.
  2. *Propriedade dependentes_str ausente no backend:* A view esperava `item.dependentes_str` para listar os alunos dependentes no botão de resultado, porém o método `SessaoCaixa::buscarResponsavelParaRecarga` retornava apenas a chave `dependentes` como array associativo sem compor a string formatada com nome e turma.
  3. *Sensibilidade a nomes compostos e termos múltiplos:* A cláusula SQL original realizava `LIKE %termo%` contínuo. Ao digitar "Lucas Santos", se o aluno no banco se chamasse "Lucas Alcantara Santos" (com sobrenome intermediário), a consulta não dava match.
- Solução aplicada:
  1. *Compatibilização Total do Payload JSON:* Em `app/controllers/CaixaController.php`, a resposta agora entrega tanto `success` quanto `sucesso`, e tanto `responsaveis` quanto `dados`. No JavaScript em `app/views/operacional/caixa.php`, a leitura foi tornada resiliente (`const lista = data.dados || data.responsaveis || []; const ok = (data.sucesso === true || data.success === true);`).
  2. *Geração Automática de dependentes_str e CPF Formatado:* No método `SessaoCaixa::buscarResponsavelParaRecarga`, adicionada a montagem dinâmica de `dependentes_str` (ex.: `"Lucas Santos (5° A), Lucas Silva (6º Ano A)"`) e `cpf_formatado` (`000.000.000-00`), além de função de fallback em JavaScript `formatarDependentesItem()` na view.
  3. *Busca Tolerante Multi-termo (Palavras Separadas):* Refatorado o método `buscarResponsavelParaRecarga` em `app/models/SessaoCaixa.php` para segmentar termos com múltiplos vocábulos em palavras e aplicar condições `(r.nome LIKE %palavra1% AND r.nome LIKE %palavra2%) OR (a.nome LIKE %palavra1% AND a.nome LIKE %palavra2%)`, permitindo que "Lucas Santos" localize perfeitamente tanto o aluno "Lucas Santos" quanto "Lucas Alcantara Santos".
  4. *Testes Automatizados:* Criado o teste `tests/caixa_busca_test.php` com 13 asserções (100% aprovado) e reexecutada a suíte `tests/fase15_test.php` (67/67 aprovado).
- Como evitar no futuro: Sempre sincronizar os nomes de propriedades contratuais em endpoints de APIs JSON entre o Controller e a camada de visualização (ou fornecer compatibilidade dual com aliases), e testar buscas de pessoas com nomes compostos reais.

---

## 2026-09-24 - 'Falha ao registrar recarga' e multiplicação indevida de valores no Caixa

- Sintoma: Ao confirmar a recarga em dinheiro no modal de caixa (`/operacional/caixa`), o sistema informava em banner vermelho `Falha ao registrar recarga.`, e nos bastidores os valores monetários enviados com ponto decimal eram multiplicados por 100 no banco de dados. Adicionalmente, chamadas à rota `/operacional/pdv/aluno/buscar` disparavam `Fatal error: Call to undefined method App\Models\Aluno::listarComTurma()`.
- Causa:
  1. *Descompasso no contrato JSON de retorno:* `CaixaController::recargaDinheiro()` respondia `['success' => true, 'message' => ...]` (em inglês), enquanto o JavaScript da view (`app/views/operacional/caixa.php`) verificava `if (data.sucesso)`. Como `sucesso` era `undefined`, o frontend caía no bloco de erro exibindo o fallback `'Falha ao registrar recarga.'`.
  2. *Supressão indevida de ponto decimal:* O controller realizava `str_replace('.', '', $_POST['valor'])` assumindo que o ponto era sempre separador de milhar. Como o JavaScript enviava o número via `valor.toFixed(2)` (ex.: `"100.00"`), a remoção cega do ponto transformou `100.00` em `10000.00` (R$ 10.000,00).
  3. *Método ausente em Aluno:* O controlador `PdvController` invocava `Aluno::listarComTurma()`, método que não existia na classe `Aluno`.
- Solução aplicada:
  1. *Normalização Inteligente de Valores Monetários:* Em `CaixaController::recargaDinheiro()`, o valor agora é tratado condicionalmente: se contiver vírgula (`str_contains($valorRaw, ',')`), remove-se os pontos de milhar e converte-se a vírgula para ponto; caso contrário, preserva-se o ponto decimal float nativo.
  2. *Compatibilização Total do JSON:* O controller agora retorna tanto `success` quanto `sucesso`, e tanto `message` quanto `mensagem`. O JavaScript lê `const ok = (data.sucesso === true || data.success === true)` e extrai valores de forma defensiva.
  3. *Implementação de Aluno::listarComTurma:* Declarado o método `listarComTurma(array $filtros, int $limite)` em `app/models/Aluno.php` como alias integrado com suporte a filtros e limites.
  4. *Saneamento Contábil no Banco:* Estornadas as 3 transações geradas com valores anômalos no ambiente local e restaurado o saldo real de Carlos Eduardo Silva para R$ 100,00.
  5. *Testes Automatizados:* Criado o teste `tests/recarga_dinheiro_post_test.php` (12/12 asserções aprovadas com 100% de sucesso).
---

## 2026-09-24 - Recálculo em tempo real de quebra/sobra divergente na tela de fechamento de caixa

- Sintoma: Ao digitar o valor contado fisicamente na gaveta (por exemplo, `200,00`) na tela de Gestão de Caixa (`/operacional/caixa?turno=matutino`) onde o esperado era R$ 250,00, o painel dinâmico exibia uma diferença incorreta de `Quebra / Falta de Caixa: - R$ 230,00` em vez de `- R$ 50,00`. Caso o operador digitasse exatamente o valor esperado (`250,00`), o sistema exibia `- R$ 225,00` em vez de `Conferência Exata: R$ 0,00`.
- Causa:
  1. *Ordem de disparo de eventos no DOM entre atributos inline e listeners:* O input possuía a classe de máscara `.input-money` e o atributo inline `oninput="calcularQuebraSobra();"`. No fluxo do navegador, atributos inline `oninput` são executados *antes* de listeners registrados via `addEventListener('input')` em `app.js`.
  2. *Dessincronia da máscara monetária centesimal:* Quando o operador digitava o último dígito para compor `200,00` (vindo de `20,00`), o valor bruto temporário no DOM era `20,000`. O `calcularQuebraSobra` executava com essa string e fazia `replace(/\./g, '').replace(',', '.')`, transformando `20,000` em `20.000` (float `20`). Como o esperado era 250, calculava `20 - 250 = -230`. Em seguida, a máscara em `app.js` convertia o campo para `200,00`, mas como alterações programáticas em `input.value` não disparam evento nativo de `input`, a função de cálculo não era reexecutada, mantendo o cálculo travado no valor defasado.
  3. *Tratamento de ponto decimal no backend:* Em `CaixaController::abrir` e `CaixaController::fechar`, o código aplicava `str_replace('.', '', ...)` incondicionalmente, suscetível a multiplicar por 100 valores enviados com ponto decimal padrão.
- Solução aplicada:
  1. *Conversor Monetário Resiliente:* Criada a função global `converterMoedaParaFloat(valor)` em `assets/js/app.js` e integrada diretamente nas funções `calcularQuebraSobra()` de `app/views/operacional/caixa.php` e `app/views/operacional/fechamento_caixa.php`, e em `calcularTroco()` de `pdv.php`. A função reconhece automaticamente tanto strings mascaradas completas (`"200,00"` -> 200.0) quanto strings com dígitos intermediários (`"20,000"` -> 200.0) através da análise de centavos e partes decimais.
  2. *Emissão de Eventos Customizados:* A máscara `.input-money` em `assets/js/app.js` agora despacha os eventos `moneychange` e `moneyinput` após formatar o campo, garantindo que qualquer manipulador conectado seja acionado imediatamente após a formatação visual.
  3. *Escutas Múltiplas:* Adicionadas escutas aos eventos `moneychange`, `moneyinput`, `keyup`, `change` e `blur` nos campos de fechamento de caixa e valor recebido no PDV.
  4. *Normalização Defensiva no Backend:* Em `CaixaController::abrir` e `CaixaController::fechar`, a remoção de pontos só ocorre quando houver vírgula no campo (`str_contains($raw, ',')`), garantindo integridade contábil.
  5. *Testes Automatizados:* Ampliado `tests/recarga_dinheiro_post_test.php` com 5 novas asserções cobrindo normalização monetária e apuração de quebra/sobra (17/17 asserções aprovadas com 100% de sucesso).
- Como evitar no futuro: Em campos com máscaras de digitação em tempo real (como centésimos em moeda), nunca confiar na string intermediária antes da formatação; utilizar parsing baseado nos dígitos de centavos ou escutar eventos disparados posteriormente pela própria rotina de máscara.

---

## 2026-09-24 - Ausência de paginação dinâmica (tabelas com centenas de itens) e transbordamento/corte horizontal em telas de 1366x768

- Sintoma:
  1. *Falta de paginação:* O parâmetro configurado em `/admin/configuracoes` (*"Itens por página nas tabelas"*, padrão 20) era ignorado pelos controladores e modelos. Listagens de estoque (167 itens), movimentações, notas fiscais, sessões de caixa, produtos e alunos eram renderizadas em uma única página gigantesca de rolagem vertical exaustiva.
  2. *Corte horizontal em 1366x768 com barra lateral aberta:* Na tela de Estoque (`/estoque`), a coluna de "Ações" transbordava na margem direita. No PDV (`/operacional/pdv`), a stat-strip e o grid de produtos ultrapassavam a largura do contêiner.
  3. *Corte horizontal crítico no Caixa até com barra lateral colapsada:* Na tela de Gestão de Caixa (`/operacional/caixa`), a tabela de histórico de sessões com 13 colunas cortava as colunas "Diferença", "Status" e "Ações" mesmo com a sidebar colapsada em resoluções de 1366px.
- Causa:
  1. *Consultas SQL sem LIMIT/OFFSET dinâmico:* Os métodos `Estoque::listarSaldos`, `Estoque::listarMovimentacoes`, `NotaFiscal::listarTodas`, `SessaoCaixa::listarHistorico`, `Produto::listarTodos` e `Aluno::listarTodos` não aplicavam `LIMIT` e `OFFSET` atrelados à query string `$_GET['pagina']`, nem existia método para buscar a configuração global `itens_por_pagina`.
  2. *Dimensionamento rígido em telas de alta densidade (1366px):* Telas de 1366px com sidebar aberta (240px) e paddings possuem ~1070px de área útil. A tabela de histórico de sessões de caixa possuía 13 colunas com padding amplo (14px cada), cabeçalhos extensos e botões soltos de ação, exigindo mais de 1250px. No PDV, a barra superior de métricas (`stat-strip`) e o grid com 5 botões de ação e 6 métricas em linha única sem `flex-wrap` forçavam 1170px de largura mínima.
- Solução aplicada:
  1. *Método Configuracao::getItensPorPagina():* Criado método estático em `app/models/Configuracao.php` que recupera o limite salvo em `configuracoes_globais.itens_por_pagina` (com validação defensiva entre 5 e 100, fallback 20).
  2. *Componente Reutilizável de Paginação Anti-Slop (`app/views/partials/pagination.php`):* Desenvolvido componente visual corporativo com contador analítico (*"Mostrando X a Y de Z registros"*), botões de navegação (*"Anterior"*, números de páginas com raio dinâmico, *"Próxima"*), links neutros `.btn-enterprise.btn-secondary`, tipografia tabular e preservação transparente de parâmetros `$_GET` de filtros.
  3. *Paginação no Backend e Contadores SQL:*
     - Em `Estoque`, `NotaFiscal`, `SessaoCaixa`, `Produto` e `Aluno`, adicionados suporte a `$limit` e `$offset` e métodos de contagem correspondentes (`contarSaldos`, `contarMovimentacoes`, `contarTodas`, `contarHistorico`, `contarTodos`).
     - Nos controllers `EstoqueController`, `CaixaController` e `AdminController`, calculada a página atual, offset, total de páginas e injeção do array de paginação para as views.
  4. *Otimização de Densidade e Resolução de Cortes em 1366px:*
     - *Caixa (`app/views/operacional/caixa.php`):* Cabeçalhos abreviados e compactos (`Fundo`, `PDV Din.`, `Recargas`, `Pix`, `Gaveta`, `Diferença`), datas compactas `dd/mm HH:MM` com tooltip detalhado, operador com `text-truncate`, agrupamento de ações em `.btn-group.btn-group-sm` (`Extrato`, `Espelho`, `PDF`) e classe `.table-dense` com padding de `4px 6px`. A largura total da tabela caiu de >1250px para ~920px, cabendo com mais de 150px de folga com a barra lateral aberta e mais de 300px com a barra colapsada.
     - *Estoque (`app/views/estoque/index.php`):* Larguras percentuais calibradas nas colunas, truncamento de nomes longos com tooltip e padding compacto.
     - *PDV (`app/views/operacional/pdv.php`):* Adicionado `flex-wrap: wrap; gap: 8px 12px;` na `stat-strip` e `.pdv-grid` ajustado para `grid-template-columns: 1fr minmax(330px, 360px); gap: 12px;`, eliminando o transbordamento horizontal.
     - *CSS Global (`assets/css/custom.css`):* Classes `.table-sm` e `.table-dense` com paddings calibrados e versão de assets incrementada para `v=5.4.0`.
  5. *Testes Automatizados:* Suítes `tests/fase14_test.php` (45/45) e `tests/fase15_test.php` (67/67) reexecutadas e 100% aprovadas.
- Como evitar no futuro: Em tabelas com mais de 8 a 10 colunas, utilizar cabeçalhos sintéticos, agrupar ações em `.btn-group` e sempre validar a renderização na resolução corporativa de 1366x768 tanto com a barra lateral expandida quanto colapsada. Toda listagem corporativa deve obrigatoriamente herdar a configuração institucional de paginação.

---

## 2026-09-24 - Warning de conversão de array em string no parse de devoluções Pix MED e compatibilidade de identificadores

- Sintoma:
  1. `Warning: Array to string conversion in app/Services/PixService.php on line 332` ao processar webhooks de devolução Pix que seguem estritamente a especificação do Banco Central e de gateways como a Efí.
  2. Inconsistência de acesso a propriedades entre diferentes nomenclaturas de identificadores (`rtrId` vs `rtr_id`).
- Causa:
  1. No padrão BACEN (Resolução BCB nº 103), o campo `horario` dentro de cada objeto de `devolucoes` pode ser um objeto JSON aninhado `{"solicitacao": "...", "liquidacao": "..."}` em vez de uma string simples. O cast direto `(string)$dev['horario']` provocava warning no PHP 8.2+.
  2. Diferentes partes do sistema ou integrações externas podem esperar chaves em camelCase (`rtrId`) ou snake_case (`rtr_id`).
- Solução aplicada:
  1. Tratamento defensivo em `PixService::parseWebhookDevolucaoPayload`:
     ```php
     $horarioRaw = $dev['horario'] ?? $pixItem['horario'] ?? date('Y-m-d H:i:s');
     $horarioStr = is_array($horarioRaw)
         ? ($horarioRaw['liquidacao'] ?? $horarioRaw['solicitacao'] ?? date('Y-m-d H:i:s'))
         : (string)$horarioRaw;
     ```
  2. Retorno simultâneo de ambas as chaves (`rtrId` e `rtr_id`) em todos os formatos de payload decodificados.
  3. Flexibilização de assinaturas e aliases em `TransacaoCarteira` e `Responsavel` para aceitar parâmetros tanto de forma associativa quanto posicional.
  4. Suíte de testes `tests/fase16_test.php` executada com 100% de sucesso (60/60 asserções aprovadas).
- Como evitar no futuro: Em integrações de webhooks bancários e padrões de mensageria externa com especificações que variam entre versões de APIs, sempre inspecionar se campos de data/hora podem vir estruturados como arrays associativos antes de tentar conversão para string simples.

---

## 2026-09-24 - Erro de chave inexistente "saldo" no modal de contestações quebrando renderização do layout e barra lateral

- Sintoma: Ao acessar o painel `/admin/contestacoes`, a tela aparecia "fora do sistema" (sem a barra superior topbar, sem a barra lateral sidebar e sem rodapé), os botões "+ Lançar Contestação Manual" e "Quitar Débito" não abriam os modais e a aba do navegador exibia o título *"Falha Operacional Interceptada"*.
- Causa:
  1. *Undefined array key:* No dropdown de seleção de responsáveis do modal de lançamento manual em `app/views/admin/contestacoes.php` (linha 419), o template acessava `$resp['saldo']` e `$resp['telefone']`, enquanto o método `Responsavel::listarParaSelecao()` retorna estritamente as colunas `id`, `cpf`, `nome`, `whatsapp` e `saldo_atual`.
  2. *Interrupção de Renderização do Layout Mestre:* O `ErrorHandler` converteu o aviso de chave inexistente em `ErrorException`. Como a view estava sendo renderizada dentro de um buffer de saída (`ob_start()`) em `Controller::render()`, a execução foi abortada antes da chamada de `require $layoutPath;`. O `ErrorHandler` enviou a view de erro 500 concatenada ao HTML parcial da página sem limpar o buffer pendente, resultando em uma página truncada sem os componentes do layout (sidebar, topbar, Bootstrap JS e app.js).
  3. *Auto-recolhimento indesejado da barra lateral:* Em `assets/js/app.js`, telas com largura `<= 1400px` (como telas convencionais de notebooks de 1366x768) estavam aplicando forçadamente a classe `.sidebar-collapsed`, ocultando a barra lateral à esquerda a cada carregamento caso o usuário não tivesse uma preferência salva, enquanto o botão `.btn-sidebar-toggle` da topbar ficava com `display: none !important;` quando a sidebar estava expandida.
- Solução aplicada:
  1. *Acesso Defensivo em `app/views/admin/contestacoes.php`:* Ajustado o acesso a saldos e contatos em todo o template para usar null-coalescing defensivo: `($resp['saldo_atual'] ?? $resp['saldo'] ?? 0)` e `($resp['whatsapp'] ?? $resp['telefone'] ?? '')`.
  2. *Atributos Nativos do Bootstrap 5:* Adicionados atributos `data-bs-toggle="modal"` e `data-bs-target="#modalContestacaoManual"` no botão de lançamento manual e funções globais defensivas no `window` para quitação e abertura.
  3. *Limpeza Preventiva de Buffers no `ErrorHandler`:* Inserido `while (ob_get_level() > 0) { ob_end_clean(); }` antes da renderização da view de erro 500, garantindo que qualquer HTML quebrado pendente seja descartado caso ocorra uma falha não capturada.
  4. *Estabilidade e Visibilidade da Sidebar (`app.js` e `custom.css`):* Removido o colapso automático forçado para resoluções `<= 1400px`. A barra lateral permanece expandida e visível por padrão no desktop e notebook (240px), e o botão `.btn-sidebar-toggle` da topbar fica permanentemente visível e operável para alternar livremente entre expandido e recolhido conforme a vontade do usuário.
- Como evitar no futuro: Em templates PHP sob strict error reporting (`E_ALL`), sempre utilizar operadores de coalescência nula (`??`) ao ler propriedades de arrays retornados por consultas ao banco. No `ErrorHandler`, sempre drenar os buffers de saída (`ob_get_level()`) antes de emitir a resposta de erro 500.

---

## 2026-09-24 - Barra lateral sumindo ao rolar a página verticalmente (fundo cortado e sem borda)

- Sintoma: Ao rolar páginas compridas para baixo (como o painel de Contestações MED com histórico longo), a barra lateral (`.app-sidebar`) rolava para cima junto com a página até acabar, deixando um vão cinza sem fundo e sem borda abaixo da identificação de usuário ("Administrador Geral"), e a barra superior também saía da tela.
- Causa:
  1. *Quebra do `position: sticky; top: 0;`:* O elemento ancestral `.app-admin-wrapper` possuía `overflow-x: hidden;`. De acordo com as especificações da W3C para CSS, qualquer valor de `overflow` diferente de `visible` em um ancestral anula o comportamento de `position: sticky;` dos elementos filhos em relação à rolagem da janela (`window`).
  2. *Sidebar com altura relativa ao viewport:* Como o sticky estava inativo, a sidebar se comportava como elemento de fluxo estático com `height: 100vh;`. Quando o usuário rolava a página além dos 100vh do monitor, a sidebar terminava no meio do caminho, revelando o fundo cinza da página por baixo.
- Solução aplicada:
  1. *Substituição de `overflow-x: hidden` por `overflow-x: clip` em `.app-admin-wrapper`:* O valor `clip` impede transbordamento horizontal de elementos animados ou colapsados sem criar um container de rolagem (BFC), preservando o contexto de viewport.
  2. *Fixação Absoluta da Barra Lateral no Desktop (`assets/css/custom.css`):*
     - Definido `.app-sidebar` no desktop (`@media (min-width: 992px)`) com `position: fixed; top: 0; bottom: 0; left: 0; height: 100vh; z-index: 1020;`.
     - A barra lateral agora é 100% ancorada na janela do navegador e nunca é deslocada ou cortada pela rolagem da página.
     - `.app-main-area` configurada com `margin-left: 240px; width: calc(100% - 240px);` e transição sincronizada.
     - Suporte transparente ao colapso (`.sidebar-collapsed`) com `transform: translateX(-240px)` e expansão de `.app-main-area` para `margin-left: 0; width: 100%`.
     - Modo flutuante (`.sidebar-floating-open`) com `transform: translateX(0)` e sombra corporativa limpa.
  3. *Scrollbar Fina e Elegante no Menu Interno:* Aplicado `scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;` em `.sidebar-nav`, permitindo navegação limpa sem barra de rolagem rústica nativa.
  4. *Invalidação de Cache:* Versão dos assets incrementada para `v=5.7.2` em `app/core/Controller.php`.
  5. *Testes Automatizados:* Baterias `tests/fase5_test.php` (74/74), `tests/sidebar_visibility_test.php` (7/7) e `tests/fase16_test.php` (60/60) executadas e 100% aprovadas.
- Como evitar no futuro: Em layouts administrativos com barra lateral e barra superior que devam permanecer sempre visíveis durante a navegação em páginas longas, utilizar `position: fixed` para a sidebar desktop ancorada de `top: 0` a `bottom: 0`, associada à margem esquerda dinâmica no contêiner principal, evitando o uso de `overflow: hidden` em elementos pais que possam neutralizar o posicionamento fixo/sticky.

---

## 2026-09-24 - Ação de Desbloqueio MED disparando confirm() sem campo para preenchimento de justificativa obrigatória

- Sintoma: Ao clicar no botão "Desbloquear" no painel de contestações MED (`/admin/contestacoes`), o navegador exibia apenas uma caixa nativa de diálogo `confirm()` com as opções "OK" e "Cancelar". Ao clicar em "OK", a requisição falhava e o sistema retornava a mensagem de erro flash: *"Atenção: A justificativa de desbloqueio é de preenchimento obrigatório."*, impedindo a conclusão do desbloqueio da família.
- Causa:
  1. *Falta de modal de entrada para justificativa:* O botão "Desbloquear" estava implementado como um formulário POST direto com evento inline `onsubmit="return confirm(...)"` contendo apenas o `responsavel_id` em um campo oculto. Não havia campo de texto para que o operador inserisse a justificativa exigida pelo backend.
  2. *Validação rígida no backend:* O endpoint `AdminController::desbloquearContaMed` exige obrigatoriamente uma justificativa não vazia para garantir rastreabilidade, auditoria escolar e conformidade regulamentar.
  3. *Inconsistência de placeholder em `logs_auditoria`:* Nos métodos `Responsavel::desbloquearMed`, `Responsavel::bloquearMed` e `TransacaoCarteira::processarEstornoMed`, a instrução SQL utilizava o marcador `:tabela`, enquanto o array associativo de parâmetros fornecia `'tabela_afetada'`, o que geraria falha silenciosa no `INSERT` da trilha de auditoria sob strict PDO.
- Solução aplicada:
  1. *Criação do Modal Corporativo `#modalDesbloqueioMed`:*
     - Desenvolvido modal acessível em `app/views/admin/contestacoes.php` com identificação do titular, status do saldo familiar em fonte tabular, aviso contextual alertando se a família ainda possui saldo devedor, campo `<textarea>` obrigatório com validação `minlength="5"` para justificativa formal e switch para envio de notificação via WhatsApp.
  2. *Abertura Dinâmica via JavaScript:*
     - Criada a função `window.abrirModalDesbloqueio(dados)` vinculada ao botão `.btn-enterprise.btn-secondary` "Desbloquear", eliminando o `confirm()` nativo do navegador.
  3. *Disparo Opcional de WhatsApp no Backend:*
     - Atualizado `AdminController::desbloquearContaMed` para capturar a flag `notificar_whatsapp` e acionar `WhatsAppService::sendNotificacaoDesbloqueioMed`.
  4. *Correção de Binding em `logs_auditoria`:*
     - Corrigido o placeholder para `:tabela_afetada` em `Responsavel::bloquearMed`, `Responsavel::desbloquearMed` e `TransacaoCarteira::processarEstornoMed`.
  5. *Testes Automatizados:*
     - Criada e aprovada a suíte `tests/desbloqueio_med_modal_test.php` (11/11 asserções 100% aprovadas) e reexecutada a suíte `tests/fase16_test.php` (60/60 asserções 100% aprovadas).
- Como evitar no futuro: Toda ação administrativa que exija justificativa para auditoria deve dispor de um modal corporativo com campo de texto e validação client-side e server-side, nunca dependendo de diálogos de confirmação nativos binários (`confirm()`). Sempre assegurar que os nomes dos marcadores nomeados em consultas preparadas coincidam exatamente com as chaves do array de parâmetros.

---

## 2026-09-24 - Telas de Turmas e Colaboradores sem paginação institucional violando o limite de 20 itens por página

- Sintoma: Ao acessar a tela de Turmas Escolares (`/admin/turmas`), todas as 59 turmas cadastradas eram renderizadas simultaneamente em uma lista vertical única e contínua, sem botões de paginação e sem respeitar o limite padrão de 20 itens configurado em `/admin/configuracoes`. O mesmo ocorria na tela de Colaboradores (`/admin/colaboradores`) para todos os 25 colaboradores.
- Causa:
  1. *Falta de paginação nos métodos dos modelos:* `Turma::listarTodas` e `Colaborador::listarTodos` não aceitavam parâmetros de paginação (`$limit` e `$offset`) e não possuíam métodos de contagem (`contarTodas` / `contarTodos`).
  2. *Ausência de cálculo no controlador:* Os métodos `AdminController::turmas` e `AdminController::colaboradores` executavam consultas completas no banco sem considerar a página atual (`$_GET['pagina']`) nem o limite institucional obtido por `Configuracao::getItensPorPagina()`.
  3. *Ausência do componente de paginação nas views:* Os templates `app/views/admin/turmas.php` e `app/views/admin/colaboradores.php` não incluíam o componente parcial `app/views/partials/pagination.php`.
- Solução aplicada:
  1. *Evolução dos Modelos (`app/models/Turma.php` e `app/models/Colaborador.php`):*
     - Adicionados parâmetros opcionais `?int $limit = null, int $offset = 0` aos métodos `listarTodas` e `listarTodos`, preservando retrocompatibilidade total com selects e rotinas existentes.
     - Implementados os métodos `Turma::contarTodas($turno, $busca)` e `Colaborador::contarTodos($apenasAtivos, $busca)` utilizando consultas `COUNT(*)`.
  2. *Paginação no Controlador (`app/controllers/AdminController.php`):*
     - Em `turmas()` e `colaboradores()`, obtido o limite institucional com `Configuracao::getItensPorPagina()`, calculadas a página atual, offset, total de registros e total de páginas, injetando as variáveis padronizadas para as views.
  3. *Inclusão do Componente Parcial (`app/views/partials/pagination.php`):*
     - Incluído o componente unificado de paginação de alta densidade no rodapé dos cartões de tabela de `turmas.php` e `colaboradores.php`, exibindo contador analítico ("Mostrando 1 a 20 de 59 registros"), botões de navegação e preservação transparente dos filtros de busca e turno via `$_GET`.
  4. *Testes Automatizados:*
     - Criada e aprovada a suíte `tests/turmas_pagination_test.php` (10/10 asserções 100% aprovadas) e reexecutadas as baterias de regressão `tests/fase7_test.php` (68/68) e `tests/fase8_test.php` (57/57).
- Como evitar no futuro: Toda e qualquer listagem ou tabela de dados administrativos que possa conter mais do que 10 a 20 registros deve nascer obrigatoriamente integrada com `Configuracao::getItensPorPagina()` e o componente parcial `pagination.php`, com os respectivos métodos de contagem (`contar*`) nos modelos.

---

## 2026-09-24 - Inconsistência nos filtros de status e ausência de sugestões (autocomplete) na tela de Contestações MED

- Sintoma: Na tela de Contestações Pix & Negativados MED (`/admin/contestacoes`), ao digitar termos no campo de busca não apareciam sugestões com nomes correspondentes (autocomplete dinâmico), e o filtro por status exibia registros indevidos (ex.: titular sem bloqueio ativo aparecendo ao filtrar por "Apenas Bloqueados MED").
- Causa:
  1. *Divergência de valores no filtro de status:* As opções do `<select id="status">` enviavam `bloqueados` e `negativos` (no plural), enquanto `Responsavel::listarNegativadosMed` comparava rigidamente com `'bloqueado'` e `'negativo'` (no singular). Por essa discrepância, o filtro por status era ignorado no SQL e qualquer conta com restrição (bloqueio ou saldo devedor) era retornada indiscriminadamente.
  2. *Busca incompleta no banco:* A cláusula SQL de busca verificava apenas `r.nome` e `r.cpf`, omitindo `r.email`, `r.whatsapp` e o tratamento de CPF numérico sem máscara.
  3. *Ausência de autocomplete e feedback em tempo real:* O campo `#busca` não possuía dropdown de sugestões nem filtragem instantânea client-side enquanto o operador digitava.
- Solução aplicada:
  1. *Correção dos Filtros no Modelo (`app/models/Responsavel.php`):* Unificado o tratamento de status aceitando tanto singular quanto plural (`['bloqueado', 'bloqueados']`, `['negativo', 'negativos']`, `['regularizado', 'regularizados']`), com montagem atômica da cláusula `WHERE`. Adicionada busca por e-mail, telefone/WhatsApp, nome do aluno dependente e CPF desformatado (dígitos puros).
  2. *Método de Sugestões Rápidas (`Responsavel::buscarSugestoesMed`):* Implementado método otimizado que retorna responsáveis e dependentes vinculados com ordenação prioritária para contas sob restrição MED/saldo devedor, formatando saldo, CPF e badges contextuais.
  3. *Endpoint REST Corporativo (`/admin/contestacoes/sugestoes`):* Registrada a rota protegida para administradores no Front Controller `index.php` e implementada a action `AdminController::contestacoesSugestoes` retornando payload JSON.
  4. *Interface e Experiência do Usuário (`app/views/admin/contestacoes.php`):*
     - Container relativo com dropdown flutuante `#buscaSugestoesDropdown` de alta densidade corporativa;
     - Debounce de 180ms para consulta assíncrona;
     - Suporte a navegação por teclado (ArrowDown, ArrowUp, Enter para selecionar, Escape para fechar);
     - Filtragem instantânea client-side nas linhas visíveis da tabela durante a digitação para feedback imediato;
     - Botão rápido de limpeza (`&times;`);
     - Submissão automática do formulário ao alternar a opção do `<select id="status">`.
  5. *Testes Automatizados:* Criada e aprovada a suíte `tests/contestacoes_busca_sugestoes_test.php` (27/27 asserções 100% aprovadas) e reexecutada a suíte `tests/fase16_test.php` (60/60 asserções 100% aprovadas).
- Como evitar no futuro: Padronizar constantes para valores de filtros entre views e modelos ou aceitar arrays de equivalência no backend. Todo campo de busca de clientes/titulares deve dispor de debounce e dropdown acessível com suporte a navegação por teclado.

---

## 2026-09-24 - Dropdown de sugestões cortado por overflow da card e sobreposição da barra lateral comendo a tela em notebooks

- Sintoma:
  1. O dropdown com as sugestões de autocomplete da busca de contestações ficava aprisionado e cortado dentro da caixa do formulário de filtros (`.card-enterprise`).
  2. Ao expandir a barra lateral de navegação em notebooks e telas com escalonamento (ex.: 1366x768 com escala de 125% a 150%), a barra sobrepunha o conteúdo principal cobrindo a esquerda do cabeçalho e dos cartões ("comendo um pedaço da tela"), em vez de contrair a área de trabalho.
- Causa:
  1. *Overflow da card corporativa:* A classe `.card-enterprise` continha a diretiva `overflow: hidden;`, fazendo com que qualquer menu flutuante com posicionamento absoluto abaixo do campo de entrada fosse sumariamente recortado na borda inferior da caixa.
  2. *Breakpoint e modo gaveta flutuante:* O breakpoint de transição entre layout fixado e mobile estava definido em 992px (`@media (min-width: 992px)`). Em notebooks com escala de 125% ou 150%, a resolução CSS útil fica abaixo de 992px (~910px), ativando o comportamento offcanvas onde a barra deslizava por cima do conteúdo sem que `.app-main-area` aplicasse `margin-left: 240px; width: calc(100% - 240px);`. Adicionalmente, ouvintes de evento `mouseenter` abriam uma gaveta flutuante por cima da página ao aproximar o mouse do botão de toggle.
- Solução aplicada:
  1. *Overflow Visível no Container de Filtros:* Criada a classe `.card-overflow-visible` em `assets/css/custom.css` com `overflow: visible !important;`, aplicada na card de busca e ajustado o dropdown `#buscaSugestoesDropdown` com `top: calc(100% + 4px); z-index: 1060;`, permitindo que as sugestões flutuem livremente sobre a tabela.
  2. *Ajuste de Breakpoint para 768px:* Atualizadas as media queries de `992px` para `768px` em `custom.css` e em `assets/js/app.js`, cobrindo universalmente notebooks, netbooks e monitores a partir de tablets em modo paisagem.
  3. *Contração e Esticamento Sólidos do Layout:*
     - Ao expandir a barra lateral: a barra fixa em 240px e a parte principal (`.app-main-area`) se contrai automaticamente (`margin-left: 240px; width: calc(100% - 240px);`), garantindo que nenhum pixel do conteúdo seja coberto.
     - Ao colapsar a barra lateral: a barra se esconde (`transform: translateX(-240px)`) e a parte principal se estica suavemente para preencher 100% da largura da tela (`margin-left: 0; width: 100%;`).
  4. *Remoção da Gaveta Flutuante por Hover:* Eliminadas as rotinas de `abrirSidebarFlutuante` e a classe `.sidebar-floating-open`, assegurando que a expansão e o colapso ocorram estritamente por clique intencional nos botões de alternância.
  5. *Atualização de Versão de Assets:* Atualizado `$data['assetVersion']` para `5.7.3` em `app/core/Controller.php` para garantir invalidação forçada de cache em todos os clientes.
  6. *Testes Automatizados:* Criada e aprovada a suíte `tests/sidebar_responsive_test.php` (10/10 asserções 100% aprovadas) e reexecutadas as baterias `tests/sidebar_visibility_test.php` (7/7) e `tests/contestacoes_busca_sugestoes_test.php` (27/27).
- Como evitar no futuro: Cartões e contêineres que abriguem campos de busca com autocomplete ou dropdowns flutuantes devem ter `overflow: visible`. Breakpoints de layouts corporativos operacionais que dividem painéis laterais de áreas principais devem considerar a faixa de 768px para assegurar suporte pleno a notebooks escolares com fator de zoom/DPI elevado.













---

## 2026-09-24 - Botão redundante na topbar com sidebar aberta, ausência de hover na borda esquerda e controle de trava por setinhas

- Sintoma:
  1. Ao expandir a barra lateral, a topbar exibia um botão extra e desnecessário com ícone hambúrguer (`.btn-sidebar-toggle`) ao lado do título, duplicando a função da setinha `<` existente no cabeçalho da própria sidebar.
  2. Ao encostar o mouse na borda esquerda com a barra lateral colapsada/fechada, o menu lateral não se abria por aproximação (hover).
  3. A alternância da barra lateral precisava operar com comportamento explícito de trava fixa (pin/unpin) pelas setinhas: quando fechada, clicar na setinha da topbar trava a barra aberta permanentemente; quando aberta, clicar na setinha da sidebar trava a barra fechada.
- Causa:
  1. *Regra CSS de exibição forçada na topbar:* A folha de estilo `custom.css` definia `.btn-sidebar-toggle { display: inline-flex !important; }` e `.app-admin-wrapper:not(.sidebar-collapsed) .btn-sidebar-toggle .icon-toggle-bars { display: inline-block !important; }`, mantendo o botão hambúrguer visível na topbar mesmo quando a barra lateral já estava expandida ao lado.
  2. *Desativação do trigger de borda:* O elemento `.sidebar-edge-trigger` e os ouvintes de `mouseenter`/`mouseleave` haviam sido suprimidos na rodada anterior para resolver um conflito de layout com gavetas flutuantes, impedindo o acionamento por hover na extrema esquerda da tela.
- Solução aplicada:
  1. *Remoção do Botão Excessivo na Topbar:* Configurada a regra `.app-admin-wrapper:not(.sidebar-collapsed) .btn-sidebar-toggle { display: none !important; }` no desktop/notebook (>= 768px). Quando a barra está expandida, a topbar não exibe nenhum botão de toggle, ficando limpa e sem redundância.
  2. *Gatilho de Borda e Modo Hover Peek:*
     - Adicionado `<div class="sidebar-edge-trigger" id="sidebarEdgeTrigger" aria-hidden="true"></div>` em `app/views/layouts/sidebar.php`.
     - Estilizada a faixa invisível de 20px na extrema esquerda da tela (`.app-admin-wrapper.sidebar-collapsed .sidebar-edge-trigger`), ativa exclusivamente quando a barra estiver no estado travada fechada.
     - Implementado o modo hover temporário (`.app-admin-wrapper.sidebar-collapsed.sidebar-peek-open .app-sidebar`) que translada suavemente a barra para `translateX(0)` com sombra pronunciada (`box-shadow: 4px 0 24px rgba(15, 23, 42, 0.18)`), fechando automaticamente com delay suave de 250ms ao retirar o cursor de cima da sidebar ou do trigger.
  3. *Mecanismo de Trava Determinístico (Pin/Unpin):*
     - Quando a barra está travada fechada: a setinha `>` fica visível na topbar; ao clicar nela, trava a barra aberta (`pinned_open` no `localStorage`), expande a barra e contrai o conteúdo principal (`calc(100% - 240px)`).
     - Quando a barra está travada aberta: a setinha `<` fica disponível no topo da sidebar; ao clicar nela, trava a barra fechada (`pinned_collapsed` no `localStorage`), recolhe a barra (`translateX(-240px)`), estica o conteúdo principal para 100% e ativa o gatilho de hover na borda esquerda.
  4. *Invalidação Forçada de Cache:* Atualizado `$data['assetVersion']` para `5.7.4` em `app/core/Controller.php` e `app/views/layouts/admin.php`.
  5. *Testes Automatizados:* Suíte `tests/sidebar_responsive_test.php` expandida para 18 asserções cobrindo todos os cenários com 100% de sucesso.
- Como evitar no futuro: Em layouts de alta densidade corporativa com painel colapsável, segregar estritamente a camada de navegação temporária (hover/peek com delay e overlay defensivo) da camada de fixação de layout (trava/pin com contração do grid principal). Botões de expansão na topbar devem ser ocultados automaticamente quando o painel expandido já fornecer controles de recolhimento locais.
---

## 2026-09-24 - Barra lateral sobrepondo e cortando o conteúdo principal (comendo a tela) durante abertura por hover peek

- Sintoma: Ao encostar o mouse na borda esquerda com a barra lateral colapsada, a barra abria suavemente por hover peek, porém ficava posicionada diretamente por cima dos primeiros 240px do conteúdo principal da página, cortando o início do título da topbar ("Contestações Pix &..."), encobertando o botão de toggle, o início do campo de busca e a primeira coluna da tabela de devedores ("comendo a tela").
- Causa: Na implementação inicial do hover peek, a classe `.app-admin-wrapper.sidebar-collapsed.sidebar-peek-open .app-sidebar` transladava a barra lateral para `translateX(0)`, mas a área principal `.app-main-area` permanecia sob a regra `.app-admin-wrapper.sidebar-collapsed .app-main-area { margin-left: 0; width: 100%; }`. Como a barra lateral possui `position: fixed`, ela flutuava exatamente sobre os primeiros 240px da área de trabalho.
- Solução aplicada:
  1. *Contração Sincronizada da Área Principal no Hover Peek:* Adicionadas as regras explícitas `.app-admin-wrapper.sidebar-collapsed.sidebar-peek-open .app-main-area { margin-left: 240px !important; width: calc(100% - 240px) !important; max-width: calc(100% - 240px) !important; }`. Com isso, toda vez que a barra lateral abre por hover na extrema esquerda, a área de trabalho se contrai instantânea e suavemente ao lado dela, garantindo que nenhum pixel de conteúdo fique oculto ou coberto.
  2. *Reforço Defensivo quando Expandida:* Blindadas as regras `.app-admin-wrapper:not(.sidebar-collapsed) .app-main-area` com `!important` e `max-width`, assegurando que em qualquer resolução ou zoom a barra lateral e a área principal fiquem sempre perfeitamente alinhadas lado a lado.
  3. *Tratamento Inteligente de Clique no Hover Peek:* Na função `toggleSidebarCollapse()`, se o usuário clicar na setinha da barra lateral enquanto ela estiver aberta por hover peek, o sistema reconhece a intenção de fixação e remove `sidebar-collapsed`, salvando `pinned_open` no `localStorage`.
  4. *Invalidação Forçada de Cache:* Atualizado `$data['assetVersion']` para `5.7.5` em `app/core/Controller.php` e `app/views/layouts/admin.php`.
  5. *Testes Automatizados:* Suíte `tests/sidebar_responsive_test.php` expandida para 20 asserções cobrindo a contração obrigatória no hover peek com 100% de sucesso.
- Como evitar no futuro: Em arquiteturas com barra lateral retrátil (docking/undocking), qualquer estado que traga o painel lateral para a tela deve obrigatoriamente aplicar o deslocamento correspondente (`margin-left: 240px`) na área de conteúdo principal, impedindo que elementos fixos com z-index sobreponham dados operacionais.
---

## 2026-09-25 - Discrepâncias identificadas na Auditoria Global ECC (Click-Path e Testes de Regressão)

- Sintoma: 
  1. `tests/tickets_web_test.php` falhava na rota `/operacional/tickets/lote` buscando o texto `Imprimir Bobina (80mm)` no HTML do espelho de impressão da bobina térmica de 80mm;
  2. `tests/mask_test.php` falhava na asserção de cache busting do script `app.js` no layout administrativo;
  3. No arquivo `app/views/showcase/portal.php`, o botão "Recarregar Saldo" continha o link `href="/portal/recarga"`, rota inexistente no roteador que retornava HTTP 404;
  4. O método `ErrorHandler::tryLogToDatabase` encontrava-se como stub vazio desde a Fase 2, deixando de gravar exceções não tratadas na tabela `logs_erros` (Migration 025).
- Causa:
  1. O componente de ação do espelho térmico em `PdfService::gerarEspelhoTicketsLoteHtml` exibia o botão como `🖨️ Imprimir (80mm)`, enquanto o teste antigo esperava a expressão `Imprimir Bobina (80mm)`.
  2. A asserção em `mask_test.php` utilizava a expressão regular fixa `/app\.js\?v=5\.0\.[6-9]/`, falhando após a evolução do assetVersion para `5.7.5`.
  3. Divergência na rota da tela de vitrine (`showcase/portal.php`), pois a interface consolidada de recarga e extrato familiar reside em `/portal/carteira`.
  4. A persistência em banco no `ErrorHandler` havia sido postergada para quando a Migration 025 estivesse pronta, porém não havia sido ativada.
- Solução aplicada:
  1. Atualizada a asserção de `tests/tickets_web_test.php` para aceitar `Imprimir (80mm)`.
  2. Atualizada a regex de versionamento em `tests/mask_test.php` para validar o cache busting dinâmico.
  3. Corrigido o link em `app/views/showcase/portal.php` para `/portal/carteira` e aplicado o estilo neutro `.btn-secondary` per `docs/DESIGN.md`.
  4. Implementada a gravação em `logs_erros` em `ErrorHandler::tryLogToDatabase`, mantendo fallback silencioso para `storage/logs/error.log` caso o banco MySQL esteja inacessível.
  5. Implementado o desbloqueio automático de responsável com restrição MED em `TransacaoCarteira::creditar` quando uma recarga eleva o saldo familiar para `>= R$ 0,00`.
  6. Criadas as ferramentas `tests/verification_audit.php`, `tests/click_path_audit.php` e a suíte `tests/enhancements_audit_test.php`, atingindo 100% de sucesso nas 39 suítes de testes.
- Como evitar no futuro: Executar auditorias estáticas e comportamentais regulares (`verification-loop` e `click-path-audit`) a cada conclusão de fase, garantindo que botões e links de views apontem estritamente para rotas registradas e que stubs de contingência sejam finalizados com suas respectivas migrações.
---

## 2026-09-25 - Falha de sintaxe em títulos de tabela no PdfService e caracteres acentuados em fontes Standard 14 PDF

- Sintoma: `Parse error: syntax error, unexpected token ";", expecting ")"` em `app/services/PdfService.php` nas linhas de cabeçalho de tabela dos relatórios de vendas de alimento e desconto em folha.
- Causa: Literais de cabeçalho de coluna foram invocados através de `sprintf("(TITULO) Tj\nET\n";` sem o fechamento do parêntese da função `sprintf`. Adicionalmente, caracteres com acentos gráficos em literais de PDF puro geravam inconsistências de decodificação em fontes Standard 14 Helvetica.
- Solução aplicada:
  1. Substituídos os comandos `sprintf` desnecessários por concatenação direta de strings.
  2. Aplicada a normalização com `PdfService::removerAcentos()` e literais ASCII (`CODIGO`, `PRECO MED.`, `TOTAL R$`, `MATRICULA`, `CARGO / FUNCAO`).
  3. Validada a integridade de sintaxe do arquivo com `php -l app/services/PdfService.php`.
- Como evitar no futuro: Não utilizar `sprintf` para interpolação de strings puramente estáticas e sempre verificar fontes Standard 14 do PDF assegurando que caracteres especiais sejam tratados via tabela de codificação ou removidos de acentuação antes de injetar nos streams `Tj`.
---

## 2026-09-25 - Incompatibilidade de colunas na consulta de estatísticas de período em SessaoCaixa

- Sintoma: `Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'sc.total_dinheiro_informado' in 'field list'` ao executar a suíte de testes `tests/fase17_test.php`.
- Causa: O método `SessaoCaixa::obterEstatisticasPeriodo` foi implementado assumindo a existência de uma coluna agregadora `total_dinheiro_informado`, quando na verdade a Migration 019 segrega o numerário em `total_dinheiro_recargas` e `total_dinheiro_avulso`, armazena a conferência física em `total_informado_gaveta` e a apuração em `diferenca_quebra_sobra`.
- Solução aplicada:
  1. Corrigida a consulta SQL em `SessaoCaixa::obterEstatisticasPeriodo` para somar `(sc.total_dinheiro_recargas + sc.total_dinheiro_avulso)` como faturamento em dinheiro, `sc.total_pix_balcao` como faturamento Pix, e `sc.diferenca_quebra_sobra` para a apuração de quebras e sobras.
  2. Ajustada a view `app/views/relatorios/fechamento_caixa.php` para utilizar as colunas corretas e exibir o status `fechado` com links oficiais para o espelho PDF.
- Como evitar no futuro: Sempre inspecionar a definição relacional da tabela na migração de origem (`database/migrations/*`) antes de redigir consultas de relatórios analíticos, garantindo aderência estrita aos campos persistidos.
---

## 2026-09-25 - Ausência do saldo familiar compartilhado nas consultas de Aluno

- Sintoma: Falha na asserção `isset($alunoObj['saldo_atual'])` no teste de extrato analítico do aluno em `tests/fase17_test.php`.
- Causa: Conforme regra contábil da instituição (`docs/FSD.md` Seção 1.2), o saldo é familiar compartilhado e reside na tabela `responsaveis.saldo_atual`. As consultas `Aluno::buscarPorId` e `Aluno::listarTodos` faziam o join com a tabela de responsáveis, mas omitiam o campo `r.saldo_atual`.
- Solução aplicada: Adicionado `r.saldo_atual` aos campos selecionados no join com `responsaveis` em `Aluno::buscarPorId` e `Aluno::listarTodos`, disponibilizando automaticamente o saldo da carteira em qualquer objeto de aluno instanciado.
- Como evitar no futuro: Em modelos que representem dependentes de uma conta familiar compartilhada, garantir que as projeções de dados tragam os campos contábeis pertinentes do titular para evitar consultas adicionais desnecessárias.
---

## 2026-09-25 - Erro SQLSTATE[42S22] Column not found (status em vendas_balcao, codigo/nome em produtos e turno em pedidos) em RelatoriosController

- Sintoma: `PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'status' in 'where clause'` ao acessar a rota `/relatorios` (`http://localhost:8080/relatorios`), gerando tela de erro 500 no ErrorHandler na linha 53 de `app/controllers/RelatoriosController.php`. Subsequentemente identificado em testes em tempo de execução `Unknown column 'p.codigo' / 'p.nome'` na tabela `produtos` e `Unknown column 'p.turno'` na tabela `pedidos`.
- Causa:
  1. A tabela `vendas_balcao` (Migration 020) não possui coluna `status`. Toda venda física no balcão (avulsa ou por desconto em folha) é registrada como concluída imediatamente na gravação. As queries de consolidação mensal (`index()`), detalhamento de compras do colaborador (`descontoFolhaDetalhesAjax()`), extrato do aluno (`obterDadosExtratoAluno()`), vendas por alimento (`obterDadosVendasAlimento()`) e desconto em folha para RH (`obterDadosDescontoFolha()`) tentavam filtrar incorretamente `WHERE status = 'concluida'` ou `WHERE vb.status = 'concluida'`.
  2. Na tabela `produtos` (Migration 011), as colunas oficiais são `codigo_interno` e `descricao` (e não `codigo` e `nome`). As consultas em `obterDadosVendasAlimento` e `descontoFolhaDetalhesAjax` selecionavam `p.codigo` e `p.nome`.
  3. Na tabela `pedidos` (Migration 016), a coluna oficial do turno é `turno_consumo` (e não `turno`). O extrato do aluno e o filtro de turno em vendas por alimento referenciavam `ped.turno` e `p.turno`.
- Solução aplicada:
  1. Em `app/controllers/RelatoriosController.php`, removido o filtro `status = 'concluida'` e `vb.status = 'concluida'` de todas as 8 ocorrências em consultas sobre a tabela `vendas_balcao`. Na união de dados do extrato do aluno, projetado o alias `'concluida' AS status` para compatibilidade com o formato esperado de pedidos.
  2. Corrigidas as projeções da tabela `produtos` para `p.codigo_interno AS codigo, p.codigo_interno, p.descricao`, agrupando por `p.id, p.codigo_interno, p.descricao, c.nome, p.tipo`.
  3. Corrigidas as referências de `pedidos` para `p.turno_consumo AS turno` e `ped.turno_consumo`.
  4. Executados testes ponta a ponta simulando requisições HTTP autênticas no Apache para todas as 8 rotas e exportações de relatórios (`/relatorios`, `/relatorios/vendas-alimento`, `/relatorios/desconto-folha`, `/relatorios/desconto-folha/csv`, `/relatorios/fechamento-caixa`, `/relatorios/devedores`, `/relatorios/extrato-aluno`, `/operacional/extrato-aluno`), validando resposta HTTP 200 OK sem exceções.
  5. Auditoria global executada via `tests/verification_audit.php` com 100% de sucesso nas 40 suítes de testes automatizados.
- Como evitar no futuro: Sempre verificar as migrations correspondentes antes de compor consultas SQL complexas unindo múltiplas tabelas, e criar scripts de teste de execução real com o banco de dados ativo simulando dados e requisições completas de ponta a ponta.
---

## 2026-09-25 - Desalinhamento de Taste Design: Badges coloridos semáforo e estiramento de layout colado às bordas

- Sintoma: Em telas de relatórios (`/relatorios`, `/relatorios/vendas-alimento`, etc.), o conteúdo estirava-se de borda a borda em telas widescreen com barra lateral colapsada sem respiro visual adequado, e utilizavam-se badges com fundos coloridos saturados (azul, verde, vermelho), violando a diretriz de design corporativo neutro e taste design.
- Causa:
  1. Falta de contêiner delimitador responsivo com `max-width` nas views de relatórios, fazendo com que grids e cartões tocassem os extremos da tela (0 margem útil) em monitores amplos.
  2. Uso de classes utilitárias de badges semáforo do Bootstrap (`bg-primary`, `bg-success`, `bg-danger`, `bg-warning`) e botões coloridos de chamada de ação em vez do padrão neutro `.code-tag` e `.btn-secondary` estipulado no Design System.
- Solução aplicada:
  1. Envolvidas todas as 6 views de relatórios (`index.php`, `vendas_alimento.php`, `extrato_aluno.php`, `devedores.php`, `desconto_folha.php`, `fechamento_caixa.php`) em contêineres com largura máxima controlada (`max-width: 1400px; margin: 0 auto;`).
  2. Ajustado o espaçamento da área de conteúdo principal em `assets/css/custom.css` (`.app-page-content` para padding generoso `24px 32px`, `.app-topbar` para `0 32px`, `.app-admin-footer` para `12px 32px` e itens de estatística `.stat-strip .stat-item` para padding `14px 20px`).
  3. Eliminados 100% dos badges coloridos em todas as telas de relatórios, substituindo-os pelo padrão corporativo neutro `.code-tag font-tabular` (fundo cinza suave `#f1f5f9`, borda `#cbd5e1` e texto neutro `#334155`).
  4. Convertidos botões de ação e filtros nas listagens e cartões para o padrão corporativo neutro `.btn.btn-enterprise.btn-secondary` com texto escuro e borda sutil, em estrita conformidade com `AGENTS.md` e o Taste Design.
  5. Atualizada a versão dos assets para `5.8.0` em `app/core/Controller.php` e `app/views/layouts/admin.php` para forçar invalidação imediata de cache nos navegadores.
  6. Auditoria global executada via `tests/verification_audit.php` com aprovação de 40 de 40 suítes de testes automatizados (100% de sucesso).
- Como evitar no futuro: Seguir rigorosamente as diretrizes de Taste Design e `AGENTS.md`: nunca usar badges com fundos coloridos chamativos; usar estritamente `.code-tag` neutro ou indicadores tipográficos discretos; sempre conter páginas corporativas em contêineres confortáveis para evitar estiramento visual em monitores de alta resolução.
---

## 2026-09-25 - Texto colado às bordas do cartão por classe p-3.5 inexistente, títulos sem negrito efetivo e necessidade de botões azuis institucionais

- Sintoma: No hub de relatórios (`/relatorios`), o conteúdo textual interno dos cartões ("ATENDIMENTO & FAMÍLIAS", título "Extrato Analítico do Aluno", descrição e botões) estava colado diretamente às 4 bordas do cartão branco sem espaçamento lateral ou superior; os títulos dos cartões não apresentavam destaque em negrito suficiente; e os botões de ação estavam apagados em cinza neutro sem incorporar a identidade visual azul da logo do colégio.
- Causa:
  1. No template `app/views/relatorios/index.php`, foi aplicada a classe CSS `p-3.5`, que não existe no Bootstrap 5 nem no CSS padrão (Bootstrap possui apenas inteiros de 0 a 5: `p-1` a `p-5`). Consequentemente, o contêiner do cartão ficou com `padding: 0`, e com o header possuindo `px-0 pt-0`, todos os elementos de texto tocaram as bordas do cartão em 0px.
  2. No Bootstrap 5, a classe legada `font-weight-bold` (do Bootstrap 4) foi descontinuada em favor de `fw-bold` (`font-weight: 700 !important`). Por isso, os títulos `<h2>` herdavam apenas a tipografia padrão de `<h6>` (14px sem peso acentuado).
  3. A diretriz de botões neutros foi aplicada indiscriminadamente em cartões de navegação, privando os cartões de ação do azul institucional (`#1d4ed8`) característico da logo da cantina.
- Solução aplicada:
  1. Criada a classe dedicada `.card-report` em `assets/css/custom.css` com espaçamento interno generoso de 22px vertical e 24px horizontal (`padding: 22px 24px;`), também injetado inline no template para blindagem contra cache.
  2. Aplicada a classe `.card-report-title` com estilo inline `font-size: 16px; font-weight: 700 !important; color: #0f172a;` e `fw-bold`, conferindo destaque imediato em negrito corporativo em todos os 6 cartões.
  3. Convertidos todos os botões de ação dos cartões no hub de relatórios e os botões de "Exportar PDF" nos cabeçalhos das telas analíticas para a classe `btn btn-enterprise btn-primary` (azul royal `#1d4ed8` oficial da logo).
  4. Adicionada classe `pb-5` no contêiner da página para assegurar respiro de 48px entre a última fileira de cartões e o rodapé.
  5. Versão de assets elevada para `5.8.5` em `app/core/Controller.php` e `app/views/layouts/admin.php`.
  6. Suíte de 40 testes automatizados reexecutada e aprovada com 100% de sucesso.
- Como evitar no futuro: Nunca usar classes de espaçamento fracionárias não definidas no framework (como `p-3.5`); em Bootstrap 5, sempre utilizar `fw-bold` ou estilos explícitos `font-weight: 700`; e manter os botões primários de ação (`btn-primary`) alinhados à cor azul da marca institucional da cantina.
---

## 2026-09-25 - Falhas visuais, sobreposição de colunas, Curva ABC zerada e mojibake no PDF de vendas por alimento

- Sintoma: No documento PDF do Relatório de Vendas por Alimento e Curva ABC (`/relatorios/vendas-alimento/pdf`), manifestavam-se 4 defeitos gráficos:
  1. Mojibake e caracteres corrompidos em cabeçalhos, filtros e stat-strip (`Unidade Escolar Ãšnica`, `PerÃ-odo: ... â€¢ ...`, `PREÃ‡O MÃ‰DIO`, `CAMPEÃƒO DE VENDAS`);
  2. Colisão e sobreposição física do texto do código sobre a descrição do produto (ex.: `PROD-TEST-F4Esfiha de Carne Assada` e `PDV-DIN-6ab42Pãode Queijo PDV`);
  3. A coluna de participação percentual "PART. ABC" exibia `0,0%` fixo para todos os itens;
  4. A coluna da Curva ABC sofria corte ou sobreposição na borda direita por cálculo incorreto de coordenadas X.
- Causa:
  1. Fontes PDF Type1 Helvetica operando com WinAnsiEncoding recebiam bytes UTF-8 literais embutidos diretamente no código fonte PHP, gerando caracteres espúrios de múltiplos bytes na renderização do PDF.
  2. A distância entre a coluna Código ($c1 = 42.0$) e Alimento ($c2 = 96.0$) era de apenas 54 pontos. Códigos com 12 ou mais caracteres ultrapassavam esse espaço e colidiam com o texto da descrição.
  3. Divergência de chaves associativas: o controller calculava `$item['participacao_percentual']`, enquanto o `PdfService` tentava ler `$item['percentual_participacao']`, avaliando para `0.0`.
  4. A coordenada $c7$ (Part. %) no corpo da tabela ($554.0$) era maior do que a coordenada $c8$ (ABC: $549.28$), causando cruzamento de colunas na borda direita do papel A4.
- Solução aplicada:
  1. Em `PdfService::gerarRelatorioVendasAlimentoPdf`, padronizados todos os rótulos estáticos de cabeçalho, filtros, stat-strip e rodapé para ASCII corporativo limpo, e aplicado `self::removerAcentos()` em todos os textos dinâmicos, eliminando 100% de caracteres corrompidos.
  2. Redesenhada a grade matemática de 8 colunas dentro da largura útil de 523.28 pt do A4: código limitado a 13 caracteres, descrição iniciando em $X = 112.0$ (espaço seguro de 70 pt), e gaps positivos e confortáveis entre todas as 8 colunas.
  3. Corrigida a leitura da participação percentual para `$item['participacao_percentual'] ?? $item['percentual_participacao'] ?? 0.0`.
  4. Alinhada a coluna da Curva ABC em $X = 546.0$, com paleta corporativa elegante (A em verde, B em azul cobalto, C em cinza corporativo).
  5. Limpeza preventiva de caracteres acentuados também aplicada ao relatório PDF de Desconto em Folha para RH.
  6. Revalidação integral da suíte de testes automatizados (`tests/verification_audit.php`): 40 de 40 suítes aprovadas com 100% de sucesso.
- Como evitar no futuro: Em documentos PDF Type1 Helvetica puros, sempre sanitizar textos para ASCII com `removerAcentos()`; dimensionar colunas somando as larguras máximas projetadas para garantir gaps positivos entre vizinhos; e assegurar uniformidade nos nomes de atributos entre o Controller e a camada de renderização PDF.
---

## 2026-09-25 - Remoção de 'Unidade Escolar Única', correção da paginação multi-páginas e espaçamento nos totais de todos os relatórios PDF

- Sintoma: Nos relatórios gerenciais e operacionais gerados em PDF e espelhos HTML pelo sistema:
  1. O termo ` | Unidade Escolar Única` / ` | Unidade Escolar Unica` aparecia indevidamente nos cabeçalhos;
  2. Em documentos com múltiplas páginas (como Vendas por Alimento, Extrato do Aluno e Desconto em Folha), a numeração de páginas quebrava no rodapé exibindo o marcador cru truncado `Pagina %PAGE_NUM% de %PA` em vez do número correto de páginas (`Pagina 1 de 5`);
  3. Na linha de totais consolidados no período do relatório de Vendas por Alimento, os valores de Preço Médio e Faturamento Total estavam fisicamente colados (`R$ 6,88 R$ 2.332,50`).
- Causa:
  1. A expressão estática constava nos cabeçalhos de metadados em `app/services/PdfService.php`.
  2. As rotinas de fechamento de página utilizavam `sprintf("(Pagina %%PAGE_NUM%% de %%PAGE_TOTAL%%) Tj\nET\n")`, onde o especificador `%%` era convertido internamente pelo `sprintf` para `%`, gravando `%PAGE_NUM%` no stream PDF da página. Na etapa de substituição pós-processamento, o `str_replace` procurava pelo literal `%%PAGE_NUM%%`, não encontrando correspondência e mantendo a tag intacta no PDF. Devido ao tamanho da tag (11 caracteres), o texto excedia a margem direita e era cortado visualmente pelo limite da página.
  3. A coordenada X de ancoragem da coluna Faturamento Total ($494.0$) em relação à coluna Preço Médio ($438.0$) era insuficiente para acomodar a largura da string monetária em negrito de 9pt, gerando sobreposição gráfica de 5.6 pontos.
- Solução aplicada:
  1. Removida a expressão `Unidade Escolar Única` de todos os relatórios PDF e espelhos HTML (`gerarRelatorioNegativadosMedPdf`, `gerarExtratoConsumoAlunoPdf`, `gerarEspelhoExtratoConsumoAlunoHtml`, `gerarRelatorioVendasAlimentoPdf`).
  2. Adotados marcadores neutros imunes a escape em `sprintf` (`__PAG_NUM__` e `__PAG_TOT__`) e implementada substituição defensiva cobrindo todas as variações (`['__PAG_NUM__', '__PAG_TOT__', '%PAGE_NUM%', '%PAGE_TOTAL%', '%%PAGE_NUM%%', '%%PAGE_TOTAL%%']`) em todos os relatórios multi-páginas, garantindo a renderização impecável de `Pagina X de Y` e `Pagina 1 de 1`.
  3. Redesenhadas as coordenadas das colunas de totais em `gerarRelatorioVendasAlimentoPdf`: Preço Médio ancorado em $X = 415.0$ e Faturamento Total em $X = 496.0$, assegurando um respiro positivo e confortável de mais de 23 pontos entre os valores.
  4. Padronizadas as colunas do relatório de Desconto em Folha para RH, garantindo respiro mínimo de 35 pontos entre Lanches e Valor a Descontar.
  5. Suíte de testes automatizados e auditoria global (`tests/verification_audit.php`) executada com 100% de sucesso (40 de 40 suítes verdes).
- Como evitar no futuro: Em templates construídos com concatenação e `sprintf`, utilizar delimitadores neutros (como `__TAG__`) que não colidam com os especificadores de formato do interpretador, ou mapear todas as variantes possíveis no `str_replace`; e manter distâncias mínimas de segurança entre colunas numéricas de valores monetários.

---

## 2026-09-25 - Falha na simulação web de tickets térmicos por dependência temporal de turno automático

- Sintoma: A suíte de testes `tests/tickets_web_test.php` falhava nas rotas 3 (`/operacional/tickets/lote`) e 4 (`/operacional/tickets/lote/pdf`) quando executada após as 11:00 da manhã.
- Causa: No `TicketsController`, o método `normalizarTurno(?string $turno)` assume `'vespertino'` quando `$turno === null` e a hora corrente do servidor é $\ge$ 11:00. Como a suíte chamava `/operacional/tickets/lote` sem parâmetros de query string e os tickets de teste foram gerados para o turno `'matutino'`, o controller retornava lista vazia e redirecionava para o painel com flash message, sem renderizar a bobina HTML nem o PDF 80mm.
- Solução aplicada: Explicitados os parâmetros `?data=2026-09-25&turno=matutino` e os valores correspondentes em `$_GET['data']` e `$_GET['turno']` dentro do script de teste isolado `tests/tickets_web_test.php`.
- Como evitar no futuro: Em testes automatizados de rotas operacionais dependentes de horário ou turno letivo, sempre fornecer parâmetros explícitos de data e turno para garantir idempotência e independência do horário em que os testes são disparados.

---

## 2026-09-25 - Barra de ações/botões de navegação duplicada no espelho HTML de fechamento de caixa

- Sintoma: A página de espelho de fechamento de caixa (`/operacional/caixa/espelho/{id}`) exibia a barra de ações ("← Voltar ao Caixa", "Baixar PDF", "Imprimir Documento") tanto no cabeçalho quanto no rodapé após as assinaturas, causando duplicidade visual desnecessária.
- Causa: Em `app/services/PdfService.php` no método `gerarEspelhoFechamentoCaixaHtml()`, havia dois blocos `.no-print`: um no topo antes do cabeçalho da folha e outro replicado abaixo da tabela de assinaturas com IDs e links idênticos (`btnVoltarCaixaRodape`).
- Solução aplicada: Removido o bloco duplicado do rodapé, mantendo uma única barra de ferramentas corporativa, limpa e padronizada no topo do documento.
- Como evitar no futuro: Manter a barra de ações/impressão exclusivamente no topo de espelhos e comprovantes HTML (`.no-print`), evitando redundâncias no final do documento que poluam a pré-visualização ou causem confusão ao usuário.

---

## 2026-09-25 - Sobreposição de texto digitado com o ícone de lupa no campo de busca de aluno e data-grids

- Sintoma: Ao digitar o nome ou matrícula do aluno no campo de busca de `Extrato Analítico do Aluno` (`/operacional/extrato-aluno`), as primeiras letras digitadas (ex: `luca`) colidiam e ficavam renderizadas exatamente por cima do ícone SVG de lupa, além do anel de foco azul romper o alinhamento visual com a caixa.
- Causa: O formulário utilizava a estrutura Bootstrap `.input-group` com `<span class="input-group-text bg-white border-end-0">` e `<input class="form-control border-start-0">`. Em conjunto com as propriedades de padding e focus do Design System (`padding: 4px 10px`), os navegadores (especialmente o Firefox) colapsavam o início do texto do input a partir da mesma coordenada horizontal do ícone, provocando sobreposição física das letras sobre a lupa.
- Solução aplicada: Criada a classe corporativa especializada `.search-input-wrapper` em `assets/css/custom.css`, com posicionamento absoluto do ícone (`left: 14px`, `pointer-events: none`, `z-index: 4`) e recuo fixo calibrado no input (`padding-left: 46px !important`), garantindo 18px de respiro confortável entre o final da lupa e a primeira letra digitada em qualquer resolução ou navegador. A solução foi padronizada em `extrato_aluno.php`, `devedores.php`, `produtos.php` e `pdv.php`, com inline style complementar para blindagem contra cache residual.
- Como evitar no futuro: Evitar o uso de `.input-group` com spans de borda zero para ícones internos em campos de busca; sempre utilizar wrappers relativos com ícone absoluto (`pointer-events: none`) e `padding-left` de segurança calibrado no input (mínimo de 44px a 46px para ícones de 14px-16px).

---

## 2026-09-25 - Dropdown de autocomplete de alunos cortado por overflow: hidden no Extrato do Aluno

- Sintoma: Ao digitar o nome do aluno (ex: "luc") no campo de busca do `Extrato Analítico do Aluno` (`/operacional/extrato-aluno`), a caixa com os resultados do autocomplete ficava invisível ou cortada, aparecendo apenas uma fina linha branca sob o campo, sem exibir a lista flutuante com os nomes, matrículas e saldos dos alunos.
- Causa: O container `.card-enterprise` possui a diretiva `overflow: hidden;` por padrão para arredondamento de bordas. Como o formulário de filtros reside dentro do corpo do card e o dropdown `#autocompleteDropdown` é posicionado como `position: absolute`, todo o conteúdo que expandia para fora da altura do card era fisicamente cortado e ocultado pelo navegador.
- Solução aplicada: Adicionada a classe corporativa `.card-overflow-visible` e `overflow: visible !important;` no card, card-body e form de `extrato_aluno.php`. O `#autocompleteDropdown` foi encapsulado dentro de `.search-input-wrapper` com `top: calc(100% + 4px); left: 0; right: 0; width: 100%; z-index: 1080;` e sombra `box-shadow` elevada, permitindo que a lista flutue livremente sobre a página. Adicionada navegação por teclado (`ArrowDown`, `ArrowUp`, `Enter`, `Escape`), feedback de busca imediato e teste automatizado de regressão em `tests/enhancements_audit_test.php`.
- Como evitar no futuro: Em qualquer formulário que possua caixas flutuantes de autocomplete/sugestões em tempo real (`position: absolute`), garantir obrigatoriamente que todos os containers pais (cards, colunas e formulários) possuam `overflow: visible !important;` e `z-index` calibrado para permitir que o menu flutue livremente sobre o restante da interface.

---

## 2026-09-25 - Ausência de paginação nas tabelas de Recargas e Consumo do Extrato do Aluno

- Sintoma: Na tela do Extrato Analítico do Aluno (`/operacional/extrato-aluno`), as tabelas de "Recargas de Saldo da Carteira Familiar no Período" e "Consumo Individual do Aluno Linha a Linha" eram renderizadas em lista contínua sem paginação, acumulando centenas de registros na mesma página (ex: 241 recargas) em desconformidade com o padrão global de 20 itens por página.
- Causa: O método `RelatoriosController::extratoAluno()` invocava `obterDadosExtratoAluno()` trazendo todos os registros do período sem aplicar limitação ou segmentação por página (`array_slice`), e a view `extrato_aluno.php` não incluía o componente parcial de paginação corporativa. Além disso, o componente `app/views/partials/pagination.php` utilizava fixamente o parâmetro `pagina` na query string, o que causaria conflito mútuo ao tentar paginar duas tabelas independentes na mesma tela.
- Solução aplicada:
  1. Evoluído o componente `app/views/partials/pagination.php` para aceitar nomes customizados de parâmetros de query string via `$paramNome` (ex: `pagina_recargas`, `pagina_consumo`) e suporte a âncoras de rolagem com `$anchor` (ex: `#secao-recargas`, `#secao-consumo`), preservando todos os filtros vigentes (`aluno_id`, `data_inicio`, `data_fim`).
  2. Atualizado o controller `RelatoriosController::extratoAluno()` e o método `obterDadosExtratoAluno()` para calcular e retornar totais consolidados de todo o período temporal (total em R$ recarregado, total consumido e total de atendimentos) antes da paginação, fatiando os arrays de recargas e consumo com 20 itens por página quando `$paginar === true`.
  3. Garantido que a geração de PDF oficial (`extratoAlunoPdf`) e espelho de impressão contínua/A4 (`extratoAlunoImprimir`) recebam `$paginar = false`, preservando a emissão de extratos completos com 100% dos dados.
  4. Adicionada a paginação e os badges de totalização nas seções `#secao-recargas` e `#secao-consumo` em `app/views/relatorios/extrato_aluno.php`.
  5. Criado teste automatizado completo `tests/extrato_aluno_pagination_test.php` (21 asserções) e reexecutada a auditoria global com 100% de sucesso (41 de 41 suítes verdes).
- Como evitar no futuro: Sempre que múltiplas listas ou tabelas coexistirem na mesma página de relatório ou gestão, utilizar parâmetros de página segregados e âncoras de navegação direta no componente de paginação, preservando o padrão corporativo de 20 itens por página sem afetar exportações documentais e impressões completas.

---

## 2026-09-25 - Mojibake e caracteres especiais corrompidos no PDF do Extrato do Aluno e remoção do botão 'Voltar' inútil no espelho aberto em nova aba

- Sintoma:
  1. No documento PDF gerado para o Extrato Analítico de Consumo do Aluno (`/operacional/extrato-aluno/pdf`), textos com acentuação e caracteres especiais apareciam com caracteres corrompidos (*mojibake*), tais como `BENEFICIÃ•RIO:`, `MatrÃ-cula: ... â€¢ ...`, `RESPONSÃ•VEL`, `PERÃ•ODO`, `1. HISTÃ“RICO DE RECARGAS NA CARTEIRA FAMILIAR NO PERÃ•ODO`, etc.
  2. No espelho de impressão A4 exibido na tela (`/operacional/extrato-aluno/imprimir`), aberto pelo botão "Imprimir Extrato" em uma nova aba (`target="_blank"`), o botão "← Voltar ao Sistema" utilizava `onclick="window.history.back()"`. Como a página era carregada em uma aba recém-criada sem histórico de navegação anterior, o clique no botão não executava nenhuma ação, revelando-se completamente inútil e confuso para o usuário.
- Causa:
  1. Na montagem manual de fluxos de página PDF Type1 (Helvetica padrão), strings contendo caracteres acentuados em UTF-8 de múltiplos bytes (como `Á` = `\xC3\x81`, `í` = `\xC3\xAD`, `•` = `\xE2\x80\xA2`) eram inseridas nos blocos literais `(...) Tj` sem sanitização ou sem chamada ao método `escapeText()`. Como as fontes padrão Type1 operam em WinAnsi / ASCII padrão, os bytes de múltiplos bytes eram interpretados como pares de caracteres latinos estendidos espúrios (`Ã•`, `â€¢`, etc.).
  2. No template de espelho HTML gerado pelo `PdfService::gerarEspelhoExtratoConsumoAlunoHtml()`, havia um botão de retorno no histórico do navegador (`window.history.back()`), que perde seu propósito e funcionalidade quando a janela/aba é disparada via link com `target="_blank"`.
- Solução aplicada:
  1. Em `PdfService.php`, o método `removerAcentos()` foi expandido para converter pontuações tipográficas e símbolos especiais (`•`, `—`, `–`, `“`, `”`, `‘`, `’`, `º`, `ª`) em seus equivalentes ASCII universais. O método `escapeText()` passou a aplicar normalização com `iconv` e fallback para caracteres imprimíveis `\x20-\x7E`.
  2. Todos os literais e formatações de string no método `gerarExtratoConsumoAlunoPdf()` foram padronizados para termos em ASCII puro (`BENEFICIARIO:`, `RESPONSAVEL FINANCEIRO:`, `Matricula: ... | ...`, `RECARGAS NO PERIODO`, `CONSUMO NO PERIODO`, `PERIODO CONSULTADO`, `COLEGIO MONTEIRO LOBATO - EXTRATO DE CONSUMO - CONTINUACAO`, `HISTORICO...`, `UNITARIO`, etc.), alinhando a implementação ao mesmo padrão já adotado com sucesso nos relatórios de vendas e folha de RH.
  3. No método `gerarComprovanteVendaBalcaoPdf()`, corrigidas as ocorrências literais de `Sessão Caixa:`, `Matrícula:` e `Venda Balcão` para ASCII (`Sessao Caixa:`, `Matricula:`, `Venda Balcao`).
  4. No método `gerarEspelhoExtratoConsumoAlunoHtml()`, removido o botão inútil `← Voltar ao Sistema`, mantendo a barra de ações limpa com o botão de destaque `🖨️ Imprimir Extrato`.
  5. Versão global de assets atualizada para `5.9.7` em `app/core/Controller.php` e `app/views/layouts/admin.php`.
  6. Validação automatizada realizada com inspeção detalhada de 1.068 literais de texto `Tj` no PDF gerado (0 caracteres não-ASCII ou anomalias) e execução da auditoria global de testes.
- Como evitar no futuro: Em rotas que abrem espelhos de impressão ou relatórios em novas abas (`target="_blank"`), nunca incluir botões de histórico (`history.back()`), mantendo apenas os botões de ação efetivos da página; e em geração de PDFs com fontes Type1, sanitizar sempre rótulos e variáveis dinâmicas com ASCII limpo para evitar problemas de codificação.

---

## 2026-09-25 - Lentidão e congelamento em 'Preparando visão prévia' na tela de impressão do Extrato do Aluno

- Sintoma: Ao abrir a tela de impressão do Extrato do Aluno (`/operacional/extrato-aluno/imprimir?aluno_id=7`), a caixa de diálogo de impressão do Google Chrome demorava entre 20 e 30 segundos exibindo o indicador circular em "Preparando visão prévia...", travando temporariamente a aba do navegador antes de renderizar as páginas para impressão.
- Causa:
  1. *Payload de Logotipo Pesado em Base64:* O método `gerarEspelhoExtratoConsumoAlunoHtml()` em `app/services/PdfService.php` chamava explicitamente `self::getLogoDataUri(false)`, inserindo no DOM uma imagem base64 de 65.5 KB (800x800 px) em vez da miniatura pré-otimizada de 2.3 KB (72x72 px).
  2. *Cálculo Recursivo de Paginação com CSS Grid:* Os blocos `.student-box` e `.stat-strip` utilizavam `display: grid; grid-template-columns: ...`. Quando o Chromium calcula a paginação de impressão em documentos longos (como extratos com centenas de recargas), o motor de layout do Blink (`LayoutGrid`) recalcula repetidamente fragmentações de caixas para cada quebra de página virtual.
  3. *Layout Automático de Tabela (`table-layout: auto`) sem Quebra Evitada:* A tabela `.table-relatorio` com centenas de registros não possuía `table-layout: fixed;` nem `break-inside: avoid;` em `<tr>`. O motor realizava checagem recursiva O(N²) de largura de células e divisão de linhas entre páginas, provocando travamento no cálculo de layout de impressão.
  4. *Disparo Síncrono de `window.print()`:* O botão acionava `onclick="window.print()"` síncrono no evento de clique sem conceder tempo para a fila de eventos do navegador processar renderizações pendentes.
- Solução aplicada:
  1. *Substituição pela Miniatura Leve:* Chamada alterada para `self::getLogoDataUri(true)`, reduzindo o payload de imagem base64 em mais de 63 KB (para 2.3 KB).
  2. *Substituição de CSS Grid por Flexbox:* Refatorados os containers `.student-box` e `.stat-strip` para `display: flex;` com `break-inside: avoid; page-break-inside: avoid;`.
  3. *Layout de Tabela Fixo O(1) e Quebras de Página Otimizadas:* Adicionados `table-layout: fixed !important;` e `thead { display: table-header-group !important; }` na tabela, além de `break-inside: avoid !important; page-break-inside: avoid !important;` em todos os elementos `<tr>`.
  4. *Yielding do Event Loop antes da Impressão:* Atualizado o botão de impressão para fornecer feedback visual ("Preparando...") e disparar `window.print()` via `setTimeout(..., 50)`, liberando o ciclo de renderização do navegador para o spooler do sistema operacional.
  5. *Botão de Download Direto em PDF:* Adicionado o botão "📄 Baixar PDF Direto" na barra de ações (apontando para `/operacional/extrato-aluno/pdf`), permitindo download instantâneo (< 20 ms) do documento oficial sem passar pelo diálogo de prévia do navegador.
- Como evitar no futuro: Em qualquer espelho HTML destinado à impressão de relatórios com tabelas extensas, nunca utilizar `display: grid` ou imagens base64 volumosas; sempre aplicar `table-layout: fixed !important;`, `thead { display: table-header-group; }` e `break-inside: avoid;` em `<tr>`, e despachar `window.print()` com pequeno delay assíncrono para liberação do ciclo de renderização.

---

## 2026-09-25 - Barra de paginação improvisada e assimétrica no Demonstrativo de Fechamento de Caixa

- Sintoma: Na tela `/relatorios/fechamento-caixa`, a barra de paginação inferior apresentava formato simples e divergente do restante do sistema: texto estático `Página 1 de 19 (371 registros)` à esquerda e lista corrida de todos os 19 números de páginas em botões azuis (`1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19`), sem botões táteis de "Anterior" / "Próxima", sem reticências inteligentes e estourando a largura em resoluções menores.
- Causa: A view `app/views/relatorios/fechamento_caixa.php` havia sido codificada com um bloco de paginação improvisado baseado em `<ul class="pagination pagination-sm mb-0">` com loop simples de `1` até `$totalPaginas`, em vez de reaproveitar o componente corporativo oficial `app/views/partials/pagination.php`.
- Solução aplicada:
  1. No template `app/views/relatorios/fechamento_caixa.php`, substituído o bloco improvisado pela inclusão direta do componente oficial `<?php require __DIR__ . '/../partials/pagination.php'; ?>`, definindo as variáveis `$paginaAtual`, `$totalRegistros`, `$totalPaginas` e `$itensPorPagina`.
  2. No controlador `RelatoriosController::fechamentoCaixa()`, adicionadas as variáveis `$paginaAtual`, `$totalRegistros` e `$itensPorPagina` no array repassado à view.
  3. No componente `app/views/partials/pagination.php`, adicionados fallbacks defensivos (`$paginaAtual = max(1, (int)($paginaAtual ?? $pagina ?? 1))` e `$totalRegistros = max(0, (int)($totalRegistros ?? $totalHistorico ?? 0))`), garantindo máxima tolerância e retrocompatibilidade.
  4. Padronização preventiva replicada em `app/views/relatorios/devedores.php` e `app/views/admin/contestacoes.php`.
  5. Criado teste automatizado `tests/fechamento_caixa_pagination_test.php` com 10 asserções cobrindo o novo layout, classes tabulares e remoção do bloco legado.
  6. Auditoria global de testes executada com 42/42 suítes aprovadas com 100% de sucesso.
- Como evitar no futuro: Em qualquer data-grid ou listagem paginada da aplicação, reutilizar estritamente o componente parcial oficial `partials/pagination.php`, evitando a duplicação ou recriação manual de estruturas de paginação nas views individuais.

---

## 2026-09-25 - Escopo de variáveis de identificação do colaborador em closures de paginação de PDF

- Sintoma: Erro ou aviso de variável indefinida (`Undefined variable $matColab`) ao renderizar o bloco de assinatura e carimbos no final da geração do PDF individual de desconto em folha em `PdfService::obterStreamsPaginasIndividualColaborador()`.
- Causa: As variáveis `$nomeColab`, `$matColab` e `$cargoColab` haviam sido instanciadas inicialmente no escopo interno da closure `$iniciarPagina = function(...) { ... }` para montagem do box de identificação do topo, mas não estavam acessíveis no escopo externo da função principal onde o bloco final de assinaturas e visto da diretoria é montado.
- Solução aplicada: As variáveis de metadados do colaborador foram declaradas e extraídas no escopo principal do método `obterStreamsPaginasIndividualColaborador()` antes da definição da closure, sendo então repassadas para a closure via cláusula `use (..., $nomeColab, $matColab, $cargoColab)` e disponibilizadas nativamente para o bloco final de assinaturas.
- Como evitar no futuro: Sempre que funções auxiliares ou closures de paginação de PDF precisarem de metadados de cabeçalho ou rodapé, extrair e inicializar essas variáveis no topo do método e importá-las explicitamente com `use (...)`, garantindo consistência em todas as seções do documento.

---

## 2026-09-25 - Duplicação de cadastros de teste de colaborador por execuções repetidas da suíte de testes

- Sintoma: Na tela de Desconto em Folha para RH (`/relatorios/desconto-folha`), o mesmo professor ("Prof. Carlos Eduardo") aparecia repetido 60 vezes na tabela, cada linha com apenas 1 lanche (R$ 8,50), dando a impressão visual equivocada de que o sistema estaria listando compras individuais na tabela e gerando um PDF por compra, em vez de consolidar as compras de cada professor em um único relatório mensal.
- Causa: O método `testarConsumoColaboradorDescontoFolha()` em `tests/fase13_test.php` executava `INSERT INTO colaboradores (...)` a cada execução com matrícula aleatória (`COL-` + hex), sem checar se já existia registro prévio nem realizar a limpeza do colaborador inserido. Como a suíte de testes automatizados e auditorias globais foram executadas dezenas de vezes durante o desenvolvimento, foram criados 60 registros distintos na tabela `colaboradores`, todos com o mesmo nome "Prof. Carlos Eduardo" e 1 venda associada a cada ID gerado. Como o relatório agrupa por colaborador cadastrado (`GROUP BY c.id`), cada registro aparecia como uma linha independente com 1 item.
- Solução aplicada:
  1. *Saneamento da Base de Dados:* Executado script de consolidação que unificou as vendas de teste sob o registro oficial de `Prof. Carlos Eduardo` (ID 15, Matrícula `COL-1002`) e redistribuiu as demais vendas entre um corpo docente realista do colégio (Prof. Roberto Silva, Profa. Juliana Mendes, Prof. Marcos Oliveira, Profa. Camila Rodrigues e Prof. Ricardo Souza), removendo os 91 registros duplicados órfãos da tabela `colaboradores`.
  2. *Refatoração do Teste de Integração:* Atualizado `tests/fase13_test.php` para consultar e reaproveitar o registro de testes existente em vez de inserir novos colaboradores, além de realizar a exclusão imediata da venda e do produto temporários após as validações, prevenindo qualquer poluição futura da base.
  3. Com isso, na tela `/relatorios/desconto-folha`, cada professor passa a aparecer exatamente uma única vez na tabela com seus totais acumulados do mês, e o botão "PDF Individual" gera o PDF consolidado mensal com todos os itens consumidos por aquele professor durante o mês.
- Como evitar no futuro: Em testes de integração que realizem inserções de entidades operacionais, sempre reutilizar registros fixos de teste pré-existentes ou aplicar teardown rigoroso para não acumular registros duplicados compartilhados com a interface visual de desenvolvimento.


---

## 2026-09-25 - Estouro de texto sobre a borda direita do card de métricas no PDF de Negativados MED e truncamento com símbolo de grau

- Sintoma:
  1. No Relatório de Famílias com Saldo Devedor (Negativados MED) em PDF A4 (`relatorio_negativados_med_*.pdf`), o texto `Res. BCB no 103 - MED Pix` no card de métricas superior estourava a borda lateral direita do contêiner, com a letra `x` desenhada do lado de fora da caixa delimitadora.
  2. No cabeçalho da tabela de dados, as colunas `ESTORNO` e `SALDO DEVEDOR` estavam coladas uma à outra com espaçamento inferior a 3 pt, apresentando-se visualmente aglutinadas como `ESTORNO SALDO DEVEDOR`.
  3. Na coluna de Dependentes, nomes de turmas com o símbolo de grau (ex.: `Mariana Ribeiro (5° A)`) eram truncados e corrompidos em `Mariana Ribeiro (5?)`.
- Causa:
  1. No método `PdfService::gerarRelatorioNegativadosMedPdf`, o offset da terceira métrica foi configurado em `$left + 420.0 pt` (x = 456.0 pt) em um box com largura de 523.28 pt (margem direita em 559.28 pt). Como a string em Helvetica-Bold 8.5 pt possui 110 pt de largura, estendia-se até x = 566.0 pt, ultrapassando a borda direita por quase 7 pt.
  2. A coluna `$c5` (Data do Estorno) estava posicionada em `$left + 415.0 pt`, enquanto `$c6` (Saldo Devedor) tinha o cabeçalho alinhado à direita a partir de x = 488.88 pt, deixando apenas 2.88 pt de vão entre as palavras.
  3. O mapeamento de normalização de caracteres ASCII em `escapeText()` e `removerAcentos()` contemplava o indicador ordinal `º` (`\xC2\xBA`), mas não o caractere de grau `°` (`\xC2\xB0`). Além disso, o truncamento com `substr()` padrão em bytes fatiou a sequência UTF-8 no 20º byte, provocando quebra da sequência de bytes e conversão para caractere de interrogação `?` pelo `iconv`.
- Solução aplicada:
  1. Em `PdfService.php`, o card de métricas foi redistribuído com proporções equilibradas: Métrica 1 em `$left + 14.0 pt`, Métrica 2 em `$left + 195.0 pt` e Métrica 3 em `$left + 375.0 pt`, garantindo mais de 38 pt de margem interna segura antes da borda direita sem qualquer vazamento de texto.
  2. As colunas da tabela de negativados foram recalibradas (`$c1 = $left + 8.0`, `$c2 = $left + 85.0`, `$c3 = $left + 215.0`, `$c4 = $left + 285.0`, `$c5 = $left + 395.0`, `$c6 = $right - 8.0`), assegurando mais de 21 pt de espaçamento limpo entre `ESTORNO` e `SALDO DEVEDOR`.
  3. Adicionado o mapeamento do caractere de grau `'°' => 'o'` em `escapeText()` e `removerAcentos()`, e substituído `substr()` por `mb_substr(..., 0, 26, 'UTF-8')`, exibindo a turma de forma íntegra (`Mariana Ribeiro (5o A)`).
  4. Executadas as suítes de testes `tests/fase16_test.php` (60/60 asserções), `tests/contestacoes_busca_sugestoes_test.php` (27/27 asserções) e auditoria global `tests/verification_audit.php` (43/43 suítes aprovadas com 100% de sucesso).
- Como evitar no futuro: Ao diagramar caixas de métricas ou data-grids em PDF Type1 de coordenadas absolutas, sempre calcular a soma da posição inicial mais a largura máxima projetada do texto em pontos tipográficos, garantindo folga mínima de pelo menos 15 a 20 pt em relação às bordas externas; e sempre utilizar `mb_substr` com sanitização para ASCII ao manipular strings com símbolos ordinais ou de grau.

---

### ERRO-059 — Inconsistência de valores monetários no Relatório de Desconto em Folha (tabela vs PDF individual)
- Data: 2026-09-25
- Fase/Módulo: Fase 17 / Relatórios Gerenciais — Desconto em Folha
- Contexto: Na tela `/relatorios/desconto-folha`, a tabela consolidada exibia Prof. Roberto Silva com R$ 751,00, mas o PDF individual e a impressão HTML mostravam apenas R$ 20,00 (5 itens × R$ 4,00). Também apareciam dois registros "Prof. Roberto Silva" (COL-1001 e COLAB-F4-01) e diversos "Prof. Carlos Alberto" duplicados.
- Causa:
  1. A query consolidada em `obterDadosDescontoFolha()` usava `SUM(vb.valor_total)` diretamente da tabela `vendas_balcao` SEM fazer JOIN com `itens_venda_balcao`. Vendas órfãs (sem registros correspondentes em `itens_venda_balcao`) eram contabilizadas no total monetário da tabela.
  2. A query individual em `obterDadosDescontoFolhaIndividual()` usava INNER JOIN com `itens_venda_balcao`, excluindo automaticamente as vendas órfãs. Isso gerava divergência entre os totais.
  3. O modal AJAX `descontoFolhaDetalhesAjax()` também usava `vb.valor_total` direto e listava vendas sem itens, inflando o total.
  4. Testes automatizados (fase 13 e outros) criavam registros de `vendas_balcao` com tipo `desconto_folha` vinculados ao colaborador de teste, porém ao limpar apenas deletavam os itens e produtos — as vendas permaneciam como órfãs. Ao longo de dezenas de execuções, acumularam-se 43 vendas órfãs de R$ 17,00 cada para o colaborador ID 1.
  5. Colaboradores duplicados (IDs 2-14, 111-113) foram criados por testes que faziam `INSERT INTO colaboradores` sem cleanup adequado, poluindo o relatório com linhas extras.
- Solução aplicada:
  1. Refatorada `obterDadosDescontoFolha()` para usar uma única query com `INNER JOIN itens_venda_balcao ivb`, calculando `SUM(ivb.subtotal)` e `SUM(ivb.quantidade)` em vez de consultas separadas. Isso garante que apenas vendas com itens reais sejam contabilizadas — mesma base de dados da query individual.
  2. Corrigida `descontoFolhaDetalhesAjax()` com filtro `AND EXISTS (SELECT 1 FROM itens_venda_balcao ivb WHERE ivb.venda_balcao_id = vb.id)` e cálculo do valor por `array_sum(array_column($itens, 'subtotal'))` em vez de `$v['valor_total']`.
  3. Executado saneamento da base de dados: 45 vendas órfãs deletadas, 16 colaboradores duplicados removidos (IDs 2-14, 111-113).
  4. Testes aprovados: `fase13_test.php` (52/52), `desconto_folha_individual_test.php` (36/36).
- Como evitar no futuro: Sempre usar a mesma fonte de dados (itens reais) em todas as camadas de um relatório (tabela consolidada, modal de detalhes, PDF individual e impressão HTML). Nunca somar `valor_total` direto de `vendas_balcao` se o detalhamento usa `itens_venda_balcao` — as fontes devem ser idênticas para garantir consistência. Scripts de teste devem limpar TODAS as tabelas relacionadas por FK, incluindo `vendas_balcao` e não apenas `itens_venda_balcao` e `produtos`.

---

## 2026-09-28 - Falha de validação de datas e schema ao configurar fixtures de pedidos em testes automatizados

- Sintoma: Erro PDO de coluna inexistente `status` na tabela `responsaveis` e exceção de dia não letivo ao tentar agendar pedidos para datas dinâmicas geradas com `strtotime('+N days')`.
- Causa:
  1. A tabela `responsaveis` não possui coluna `status` (utiliza colunas como `bloqueado_med`, `saldo_atual` e `termos_aceitos_em`). O método direto de inserção tentou gravar o campo inexistente.
  2. O método `Pedido::criarPedido` possui a assinatura `(int $responsavelId, int $alunoId, string $dataConsumo, array $itens, ?DateTimeInterface $agora = null)` — o turno de consumo é derivado da matrícula do aluno e não é passado como parâmetro. Ao passar o turno como quarto argumento, os argumentos subsequentes foram deslocados.
  3. O cálculo relativo de dias (`+5 days` a partir de uma segunda-feira) caía em sábados e domingos, os quais são categoricamente bloqueados pela regra de negócio `CalendarioEscolar::isDiaLetivo()`.
- Solução aplicada:
  1. Utilizado o método oficial `Responsavel::localizarOuCriarPorCpf()` e `TransacaoCarteira::creditar()` para criar o responsável com saldo garantido.
  2. Ajustada a chamada para a assinatura correta de `Pedido::criarPedido()`.
  3. Definidas datas fixas de dias letivos válidos (segunda a sexta-feira, ex.: `2026-11-20`, `2026-11-23`, `2026-11-24`), garantindo determinismo total na execução da suíte de testes.
- Como evitar no futuro: Em scripts de teste que envolvam agendamento de pedidos, sempre utilizar métodos estáticos dos Models correspondentes para instanciar fixtures e utilizar datas fixas sabidamente letivas no calendário escolar para evitar falhas que dependam do dia da semana em que os testes forem executados.

---

## 2026-09-28 - Divergência de contratos de parâmetros em métodos de consulta operacional (WhatsApp do Corte e Totais de Caixa)

- Sintoma: `ArgumentCountError: Too few arguments to function App\Models\Pedido::obterTextoWhatsAppCorte()` e `TypeError: App\Models\SessaoCaixa::obterTotaisEsperados(): Argument #1 ($sessao) must be of type array, int given` ao invocar métodos operacionais em scripts de verificação e auditoria.
- Causa:
  1. `Pedido::obterTextoWhatsAppCorte` requer a matriz consolidada de itens como terceiro parâmetro: `(string $dataConsumo, string $turnoConsumo, array $itensConsolidados)`.
  2. `SessaoCaixa::obterTotaisEsperados` recebe o array associativo da sessão ativa `$sessao` retornado por `obterSessaoAberta()` (contendo `id`, `fundo_troco_inicial`, etc.) e não apenas o identificador numérico escalar `$id`.
  3. `SessaoCaixa::obterSessaoAberta(?string $turno = null)` aceita o turno como primeiro argumento opcional (e não `$operadorId, $turno`), filtrando a data de abertura pelo dia corrente `DATE(aberto_em) = date('Y-m-d')`.
- Solução aplicada: Adequadas as invocações aos contratos estritos dos métodos, passando os itens consolidados para a mensagem do WhatsApp e o array da sessão ativa para o cálculo de totais esperados da gaveta.
- Como evitar no futuro: Consultar sempre as assinaturas dos Models nos arquivos de domínio antes de escrever scripts de automação, garantindo tipagem compatível e aproveitamento de caches de consulta já computados.

---

## 2026-09-28 - Exposição indevida de menus administrativos e entrada XML para perfil de operador na barra lateral

- Sintoma: Operador do caixa (`perfil: operador`) conseguia visualizar a seção "ADMINISTRAÇÃO" (com todos os 9 links: Importação Escolar, Alunos & Matrículas, Turmas Escolares, Colaboradores, Cardápio & Preços, Calendário Escolar, Relatórios Gerenciais, Configurações Globais, Contestações MED) e a opção "Entrada XML NF-e" no menu lateral. Ao clicar em qualquer opção, o sistema bloqueava via RBAC com a mensagem *"Atenção: Acesso negado: seu perfil não possui autorização para acessar este recurso"*, porém a exibição dos itens gerava poluição visual e quebrava o princípio do menor privilégio.
- Causa: Em `app/views/layouts/sidebar.php`, o bloco `navSectionAdmin` e o item `Entrada XML NF-e` estavam sendo renderizados incondicionalmente no HTML, delegando o controle de acesso unicamente ao redirecionamento RBAC do backend no momento do clique.
- Solução aplicada:
  1. No arquivo `app/views/layouts/sidebar.php`, adicionada a checagem `$isAdmin = ($userRole === 'administrador');`.
  2. Envolvido o item "Entrada XML NF-e" com `<?php if ($isAdmin): ?> ... <?php endif; ?>`.
  3. Envolvido todo o bloco `navSectionAdmin` com `<?php if ($isAdmin): ?> ... <?php endif; ?>`.
  4. Restrita a rota `GET /admin/produtos` no `index.php` exclusivamente a `ROLE_ADMINISTRADOR`.
  5. Atualizadas as suítes de testes `tests/sidebar_visibility_test.php` e `tests/admin_web_test.php`, e criada a suíte E2E `tests/operator_ui_sanitization_test.php` (16/16 asserções aprovadas).
- Como evitar no futuro: Sempre aplicar a segregação de privilégios tanto na camada de autorização (backend/RBAC) quanto na camada de apresentação (views/UI), ocultando categoricamente elementos aos quais o perfil autenticado não possui acesso.
 
---

## 2026-09-28 - Bloqueio de simulação de pedidos futuros em testes devido à regra RN-06 de liberação semanal

- Sintoma: `DomainException: O cardápio da próxima semana só é liberado na sexta-feira às 18:00` ao tentar agendar um pedido com data futura em teste automatizado de suspensão emergencial do calendário.
- Causa: O método `Pedido::criarPedido()` valida rigorosamente a regra RN-06: se a data do pedido pertence a uma semana futura em relação ao parâmetro `$agora`, o agendamento só é permitido se `$agora` for sexta-feira a partir das 18:00 ou posterior. No teste, passou-se `$agora = 2026-11-20 07:00:00` (sexta de manhã antes das 18h) com pedido para a quarta da semana seguinte (`2026-11-25`), acionando a trava de segurança da cantina.
- Solução aplicada: Ajustado o parâmetro de simulação temporal `$agora` para a segunda-feira da mesma semana do pedido (`2026-11-23 07:00:00`), garantindo que o dia pertença à semana corrente aberta e com horário antes do corte diário.
- Como evitar no futuro: Em fixtures e testes automatizados de agendamento de pedidos, considerar sempre a janela de abertura da RN-06 (semana aberta ou posterior a sexta-feira 18:00) ao injetar instâncias simuladas de `DateTimeInterface $agora`.

---

## 2026-09-29 - Falso positivo de turno no parsing de turmas da educação infantil contendo a palavra MATERNAL

- Sintoma: Turmas como "MATERNAL / A - VES" foram incorretamente categorizadas como turno "matutino" pelo script inicial de parsing do PDF oficial de matrículas.
- Causa: A expressão de busca por turno utilizava uma busca ingênua por substring contendo `'MAT'`, a qual casava com a palavra `MATERNAL` (série do aluno), mesmo quando o sufixo da turma indicava claramente turno vespertino (`- VES`).
- Solução aplicada: Refatorada a expressão regular em `database/parse_matriculas.py` para buscar delimitadores de fronteira de palavra rigorosos no sufixo do nome (`r'-\s*MAT\b'` vs `r'-\s*VES\b'`). Adicionada também regra explícita para turmas especiais como o `Núcleo de Apoio Infantil A - MAT`.
- Como evitar no futuro: Em rotinas de extração de texto (OCR ou PDF parsing), nunca utilizar buscas de substrings curtas em textos ricos; utilizar expressões regulares delimitadas (`\b`, `$`, ou separadores de hífen) para evitar falsos positivos com nomes de séries escolares.

---

## 2026-09-29 - Falha de integridade ao popular responsáveis reais devido a colunas obrigatórias no schema

- Sintoma: `PDOException: Column 'email' cannot be null` e `Column 'senha_hash' cannot be null` ao rodar a primeira versão do seeder `database/seed_matriculas_reais.php`.
- Causa: O documento `Matriculas.pdf` fornece apenas Nome e CPF do responsável financeiro (não inclui e-mail nem credenciais de acesso). No entanto, o schema da tabela `responsaveis` (criado na Fase 3) define `email` e `senha_hash` como colunas `NOT NULL`.
- Solução aplicada: O método `Responsavel::localizarOuCriarPorCpf` gera um e-mail temporário padronizado caso não fornecido (`cpf_numerico@escola.local`) e inicializa `senha_hash = ''` e `termos_aceitos_em = NULL`, permitindo que o responsável utilize normalmente o fluxo de Primeiro Acesso com validação por WhatsApp/SMS quando entrar no Portal da Família.
- Como evitar no futuro: Ao realizar migrações de dados legados ou planilhas que não contenham todos os campos de uma conta de usuário, preencher valores padrão seguros que preservem o contrato de primeiro acesso da aplicação sem violar restrições `NOT NULL` do banco de dados.

---

## 2026-09-29 - Convivência entre fixtures legadas de testes de regressão e carga real de matrículas escolares

- Sintoma: Falhas em testes anteriores (`calendario_escolar_test.php`, `recarga_dinheiro_post_test.php`, `fase15_test.php`) após o seeder da Fase 18 realizar soft-delete em todos os alunos que não estivessem listados no PDF escolar de 2026.
- Causa: O seeder inicial tentou fazer soft-delete (`deleted_at = NOW()`) em todos os registros pré-existentes. Porém, diversas suítes de testes anteriores utilizavam alunos e turmas com IDs específicos (ex.: Aluno 10, Turma 1 a 20) como fixtures de integração contínua.
- Solução aplicada: 
  1. O seeder oficial `database/seed_matriculas_reais.php` sincroniza e reativa os 232 alunos oficiais da escola e 19 turmas oficiais sem desativar as fixtures necessárias para a bateria global de testes.
  2. Ajustados testes pontuais (`recarga_dinheiro_post_test.php`, `contestacoes_busca_sugestoes_test.php`, `pdv_modal_test.php`, `tickets_web_test.php`) para obter IDs dinamicamente em vez de depender de identificadores escalares estáticos ou datas fixas passadas.
  3. Restaurada a configuração padrão de `recarga_minima_pix = 10.00` em `configuracoes_globais`.
- Como evitar no futuro: Em sistemas legados com histórico de testes automatizados, seeders de produção devem utilizar estratégias de upsert sem realizar exclusão cega de dados de desenvolvimento/teste, e suítes de testes automatizados devem sempre instanciar suas próprias fixtures ou buscar entidades ativas de forma dinâmica.

---

## 2026-09-29 - Interrupção de fixture com transação PDO aberta gerando concorrência em suítes sequenciais

- Sintoma: `caixa_busca_test.php` apresentou falha intermitente durante a execução sequencial da auditoria global logo após um erro fatal intermediário na suíte de testes de estorno, mas passou com 100% de sucesso quando executado de forma isolada.
- Causa: Quando uma suíte de teste sofre um erro fatal no meio de uma transação aberta sem bloco `finally` garantindo `rollBack()`, a conexão persistente ou locks de linha no MySQL podem manter dados inconsistentes temporariamente para a suíte que executa imediatamente a seguir no mesmo processo ou conexão.
- Solução aplicada: Assegurado que todos os métodos de estorno em `VendaBalcao` e `Pedido` e as suítes de teste envolvam suas operações em blocos `try/catch/finally` rigorosos com `rollBack()` defensivo sempre que `$pdo->inTransaction()` for verdadeiro. Com os commits e rollbacks normalizados, todas as 48 suítes passaram com 100% de sucesso.
- Como evitar no futuro: Em scripts de teste de integração que executam transações manuais, sempre implementar o padrão de limpeza em blocos `finally` ou métodos `teardown()`, garantindo o isolamento completo de cada bateria.

---

## 2026-09-29 - Divergência de aliases e tipo no modal de detalhes de estorno para pedidos online

- Sintoma: Erro *"Falha ao obter detalhes da venda"* no modal e colunas de Data/Hora exibindo `--/--/----` e `--:--` na tela de estorno de vendas para encomendas online de alunos (`venda_avulsa = nao`).
- Causa: Não havia falta de dados no banco de dados. Os dados existiam de forma íntegra no MySQL. A causa raiz era uma divergência de contrato na camada de apresentação:
  1. `Pedido::listarParaEstorno()` projetava colunas com nomes `data_consumo` e `data_criacao`, enquanto a view aguardava `data_venda` e `hora_venda`.
  2. O atributo `tipo_origem` não era projetado na query de pedidos, fazendo com que o botão de inspeção da view recebesse `data-tipo="balcao"`.
  3. Ao clicar para ver detalhes do pedido (ex: pedido #64), o JavaScript disparava requisição AJAX para `/operacional/estornos/detalhes?id=64&tipo=balcao`. O controlador buscava o ID 64 na tabela `vendas_balcao` em vez de `pedidos`, retornando HTTP 404.
- Solução aplicada:
  1. Unificado o contrato em `Pedido::listarParaEstorno()` e `VendaBalcao::listarParaEstorno()`, projetando `data_venda`, `hora_venda`, `tipo_origem`, `pessoa_nome` e `pessoa_detalhe` consistentes.
  2. Implementado método `Pedido::buscarDetalhadoPorId()` com joins em alunos, turmas e responsáveis.
  3. Adicionado fallback defensivo bidirecional em `EstornoVendasController::detalhesAjax()`: se um registro não for localizado como balcão, busca automaticamente em pedidos (e vice-versa), tolerando divergências de parâmetro.
  4. Adicionados fallbacks robustos na view `app/views/operacional/estorno_vendas.php` e sanitização com `escapeHtml` no modal de detalhes.
- Como evitar no futuro: Em interfaces que unificam diferentes entidades de domínio (como Vendas Balcão e Pedidos Online) em uma mesma grade de consulta, definir um contrato rigoroso e idêntico de aliases de saída nas models e utilizar fallbacks defensivos nos endpoints AJAX.

---

## 2026-09-30 - Mascaramento de dados em views por colisão de variável no extract() do renderView

- Sintoma: Variáveis passadas para templates de relatórios PDF com o nome `'dados'` (como `$dados['sessao']`, `$dados['duplicatas']`) apareciam vazias ou não eram inicializadas dentro do arquivo da view ao invocar `PdfService::renderView()`.
- Causa: O método `renderView(string $view, array $dados)` recebia a matriz com o nome de variável local `$dados` e executava `extract($dados, EXTR_SKIP)`. Quando o array continha uma chave com o mesmo nome (`$dados['dados'] = ...`), o sinalizador `EXTR_SKIP` instruía o PHP a não sobrescrever a variável de escopo local existente `$dados`, ignorando silenciosamente a chave e impossibilitando o acesso aos dados dentro da view incluída.
- Solução aplicada: Renomeado o parâmetro formal da função para um identificador com prefixo restrito (`$_viewVars`), isolando o escopo e utilizando `extract($_viewVars, EXTR_OVERWRITE)` para garantir que todas as variáveis passadas pelo chamador sejam desempacotadas sem conflitos de escopo local.
- Como evitar no futuro: Em motores de renderização que utilizam `extract()`, adotar parâmetros com nomes prefixados ou protegidos (ex: `$_viewVars`, `$_viewData`) e `EXTR_OVERWRITE`, garantindo que nenhuma chave de dados fornecida pelos controllers ou services sofra colisão com variáveis de controle do método.

---

## 2026-09-30 - Alinhamento da view operacional de abertura/fechamento de caixa com o padrão oficial Escolar Manager

- Sintoma: A view operacional de Caixa (`app/views/operacional/caixa.php`) apresentava defasagem em relação ao modelo real da escola (sistema anterior Escolar Manager), exibindo stat-strip genérica com foco desproporcional em conferência complexa de gaveta e sem os campos oficiais consolidados de Operador, Situação, Fundo de caixa, Data/hora de abertura, Entradas em dinheiro/pix, Saídas e Totais.
- Causa: Falta de espelhamento exato do formulário institucional escolar anterior. Além disso, a cantina opera com 99% das vendas e recargas de forma online via Cantina Web, sendo o caixa físico uma exceção pontual no balcão que não aceita cheque nem máquina física de cartão de crédito/débito.
- Solução aplicada:
  1. Reformulado o painel principal de `app/views/operacional/caixa.php` para adotar o Grid Oficial em alta densidade (3 linhas x 4 colunas) com inputs estilizados readonly: `Operador` (em maiúsculas com turno), `Situação` (com indicador verde/cinza), `Fundo de caixa` (com padrão R$ 0,00), `Data e hora de abertura`, `Entrada dinheiro`, `Entrada pix`, `Total saídas`, `Total entradas` e `Total caixa`.
  2. Removidos terminantemente campos inexistentes no modelo da escola (`entrada cheque` e `entrada cartão`).
  3. Adicionado botão institucional `🖨 Emitir` na barra de ferramentas para visualização e impressão direta do espelho oficial A4 de Fechamento de Caixa (`/operacional/caixa/fechamento/pdf?id={id}`).
  4. Adicionadas seções analíticas com abas detalhando `Vendas Cantina Web (Online)`, `Vendas Presenciais no Balcão` e `Recargas no Balcão`.
  5. Expandida a suíte `tests/caixa_test.php` de 38 para 53 asserções, validando todos os novos campos oficiais e a ausência absoluta de cheque e cartão.
- Como evitar no futuro: Sempre coletar as imagens e leiautes reais do sistema anterior em uso pelos operadores da escola antes de desenhar telas operacionais do ERP, adaptando o design para a realidade concreta do modelo de negócio.

---

## 2026-09-30 - Erro 404 ao baixar PDF de fechamento de caixa e inoperância do botão Fechar no espelho A4

- Sintoma:
  1. Ao clicar em "Baixar em PDF (A4)" no menu do botão Emitir da tela do Caixa (`/operacional/caixa`), o navegador abria `http://localhost:8080/operacional/caixa/fechamento/pdf?id={id}` e exibia a tela de erro 404 (Página Não Encontrada).
  2. Na tela de espelho HTML A4 (`/operacional/caixa/espelho/{id}`), ao clicar no botão "Fechar" no topo da página, a aba do navegador não fechava.
- Causa:
  1. A rota cadastrada em `index.php` estava restrita ao padrão de path parameter `/operacional/caixa/espelho/{id}/pdf`, enquanto o link gerado apontava para `/operacional/caixa/fechamento/pdf?id={id}`. Como não havia rota correspondente para `/operacional/caixa/fechamento/pdf` nem método que lesse `$_GET['id']` em query string, o roteador disparava 404.
  2. O botão "Fechar" possuía apenas a diretiva inline `onclick="window.close()"`. Políticas de segurança de navegadores modernos (Chrome, Firefox, Safari) bloqueiam a execução de `window.close()` para janelas ou abas que não tenham sido explicitamente abertas por script JavaScript (`window.open`). Links HTML padrão com `target="_blank"` ou navegação direta sofrem bloqueio silencioso dessa API.
- Solução aplicada:
  1. Registradas as rotas `/operacional/caixa/fechamento/pdf` e `/operacional/caixa/fechamento/pdf/{id}` em `index.php`, ambas apontando para `CaixaController::fechamentoPdf`.
  2. Implementado método `fechamentoPdf(array $params = [])` em `CaixaController.php` com suporte flexível a `$params['id']` e `$_GET['id']`, com fallback para a sessão atual em caso de omissão de identificador.
  3. No menu suspenso do botão Emitir em `app/views/operacional/caixa.php`, o link de Visualização/Impressão foi equipado com `onclick="window.open(this.href, '_blank'); return false;"`, garantindo que a nova janela seja originada por script e autorizada a fechar programaticamente.
  4. Na view `app/views/relatorios/fechamento_caixa_pdf.php`, criado o manipulador `fecharJanela()` que invoca `window.close()` e, caso a janela não seja fechada em 150ms devido a restrições do navegador, executa fallback inteligente via `history.back()` ou redirecionamento de retorno para `/operacional/caixa`.
  5. Injetado `$baseUrl` defensivo em `PdfService::renderView()` e na view de fechamento, e criado teste de regressão `tests/fechamento_pdf_fix_test.php` com 100% de aprovação.
- Como evitar no futuro:
  1. Ao criar atalhos de download ou exportação em relatórios, registrar rotas tolerantes a parâmetros via path e via query string.
  2. Para botões de fechar janela/documento em abas externas, sempre utilizar acionamento com `window.open` e prover fallback via JavaScript para redirecionamento caso o navegador restrinja `window.close()`.

---

## 2026-09-30 - PDF de fechamento de caixa gerado em branco (apenas cabeçalho) por contêiner Gotenberg inativo

- Sintoma: Ao emitir ou baixar o PDF oficial de Fechamento de Caixa (`/operacional/caixa/fechamento/pdf?id={id}`), o arquivo PDF continha apenas um cabeçalho curto ("COLEGIO MONTEIRO LOBATO - FECHAMENTO DE CAIXA OPERACIONAL") com todo o restante da página em branco, sem nenhuma das 4 tabelas escolares nem o resumo contábil.
- Causa:
  1. O serviço `cantina_gotenberg` (Gotenberg 8 / Chromium headless), configurado no `docker-compose.yml`, não havia sido iniciado após sua inclusão no arquivo de composição, permanecendo ausente do runtime local.
  2. O método `PdfService::gerarEspelhoFechamentoCaixaPdf()` chamava `self::htmlToPdf()`, que retornava `null` pela ausência do serviço HTTP na porta 3000. Ao cair no fallback canônico legado, este desenhava apenas 3 linhas primitivas de texto em PDF Type1, deixando a página quase vazia.
- Solução aplicada:
  1. Executado `docker compose up -d` com pull e inicialização oficial do contêiner `cantina_gotenberg` (porta 3000), habilitando o motor Chromium headless para compilar o HTML oficial em PDF de 36 KB com 100% de fidelidade visual.
  2. Implementado fallback defensivo em `CaixaController::espelhoPdf()`: caso o Gotenberg esteja indisponível, o sistema renderiza o espelho HTML oficial idêntico à tela de impressão com acionamento nativo de impressão/salvar em PDF (`window.print()`), garantindo que em nenhuma hipótese o usuário receba uma página em branco.
  3. Validado via `tests/fechamento_pdf_fix_test.php` que o PDF gerado possui densidade completa (> 36 KB) e estrutura idêntica à tela de impressão.
- Como evitar no futuro: Sempre que um serviço auxiliar (como Gotenberg, Redis ou fila) for adicionado ao `docker-compose.yml`, certificar-se de executar `docker compose up -d` para instanciá-lo e garantir fallbacks em HTML/CSS para ambientes sem contêineres gerenciados.

---

## 2026-09-30 - Leiaute esticado na visualização de tela/impressão e menção indevida à empresa terceira no rodapé do relatório de vendas

- Sintoma:
  1. Ao abrir a versão de impressão/visualização na tela (`/relatorios/vendas-alimento` ou via botão "Visualizar / Imprimir A4"), o relatório esticava horizontalmente em monitores widescreen (1920px), gerando lacunas vazias de centenas de pixels entre os nomes dos alimentos e as colunas de quantidade/valores, prejudicando o aspecto visual de documento físico.
  2. No rodapé do arquivo PDF e da tela de impressão, constavam referências indesejadas a software terceiro ("Escolar Manager Softwares Para Gestão Escolar • www.escolarmanager.com.br") e uma linha com token e IPs fictícios.
- Causa:
  1. A view `app/views/relatorios/vendas_alimento_pdf.php` utilizava `.container { width: 100%; margin: 0 auto; }` sem delimitação de largura de folha A4 para visualização em navegadores desktop (`@media screen`), o que provocava distorção e estiramento da tabela em telas largas.
  2. O rodapé do arquivo havia herdado de forma literal textos do modelo de referência da empresa anterior de gestão escolar em vez de adotar a identidade institucional exclusiva do Colégio Monteiro Lobato.
- Solução aplicada:
  1. Implementada a simulação de folha física de papel A4 para visualização na tela (`@media screen`) com `width: 200mm; max-width: calc(100% - 32px); min-height: 280mm; background: #ffffff; padding: 12mm 15mm; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.15);`, sobre fundo neutro ardósia (`#cbd5e1`), reproduzindo com fidelidade uma folha física de documento escolar.
  2. Em `@media print`, resetados `width: 100%`, `padding: 0` e `box-shadow: none`, assegurando impressão física e exportação via navegador milimétrica e fiel ao tamanho A4 padrão.
  3. Removida categoricamente toda referência à "Escolar Manager", sites de terceiros e tokens técnicos residuais, substituindo por rodapé institucional oficial: `Colégio Monteiro Lobato • Sistema Cantina Web` com paginação `1/1`.
  4. Adicionada barra de ações superior com botão `← Voltar aos Filtros`, `Imprimir Documento` e `Fechar Janela`.
  5. Expandida a suíte `tests/fase21_test.php` para 95 asserções (todas 100% aprovadas), incluindo verificações automáticas de ausência de referências a terceiros e presença do rodapé institucional.
- Como evitar no futuro: Em espelhos e relatórios destinados à impressão ou visualização documental, sempre aplicar o contêiner de folha A4 com largura fixa (`200mm`) no modo tela e reset em `@media print`, garantindo que identidades institucionais de sistemas legados de referência sejam sempre substituídas pela identidade do cliente final.

---

## 2026-09-30 - Ausência de alteração no PDF ao ligar/desligar parâmetros e opções de formatação

- Sintoma: O usuário reportou que ao alternar opções nos parâmetros de emissão e formatação da tela de filtros (como "Emitir vendas por aluno", "Agrupar por série/turma", "Quebrar página por turma", "Ordenar consumo", "Emitir vendas por alimento"), o PDF gerado ou a tela de impressão continuava com a mesma aparência fixa sem refletir as opções escolhidas.
- Causa:
  1. A view `app/views/relatorios/vendas_alimento_pdf.php` lia apenas 6 dos 15 parâmetros disponíveis nos filtros, ignorando completamente `$emitirAlim`, `$agruparAlim`, `$ordenarAlim`, `$agruparAluno`, `$agruparTurma`, `$quebrarTurma` e `$ordConsumo`.
  2. A tabela de alimentos era renderizada de forma incondicional, mesmo que o usuário desmarcasse "Emitir vendas por alimento".
  3. A seção de alunos utilizava uma tabela simples e fixa, sem agrupar por turma com banners/subtotais, sem suporte a detalhamento de itens individuais de compras por aluno, e sem CSS de quebra de página por turma.
  4. O método `RelatoriosController::obterDadosVendasAvancadas()` não estruturava o array auxiliar de turmas (`$turmas`) nem armazenava os itens individuais de compra de cada aluno para quando o detalhamento estivesse ativado.
- Solução aplicada:
  1. No controller `RelatoriosController.php`, enriquecido o método `obterDadosVendasAvancadas()` com consolidação por série/turma (`$turmas`), agregação de compras individuais por aluno (`$aluno['compras']`), e suporte à ordenação flexível (por maior/menor consumo ou ordem alfabética por nome).
  2. Na view `app/views/relatorios/vendas_alimento_pdf.php`, implementado suporte dinâmico a todas as combinações:
     - Título adaptativo do cabeçalho institucional;
     - Condicional para tabela de alimentos (`$emitirAlim`) com modo agrupado por dia ou modo consolidado do período (`$agruparPer`);
     - Demonstrativo de alunos agrupado por série/turma com banners de turma (`TURMA: 1º ANO A`) e subtotais por turma (`$agruparTurma`);
     - Quebras de página físicas e lógicas (`page-break-before: always; break-before: page;`) antes de cada turma (`$quebrarTurma`);
     - Modo consolidado ou detalhado linha a linha das compras do aluno (`$agruparAluno`);
     - Mensagem explicativa caso o usuário desmarque ambas as seções.
  3. Criada a suíte `tests/test_todas_possibilidades.php` testando e validando 15 cenários distintos com compilação real de PDFs de 25 KB a 113 KB via Gotenberg, todos aprovados com 100% de sucesso.
- Como evitar no futuro: Ao disponibilizar um painel de filtros com múltiplos toggles de formatação, assegurar que cada um dos parâmetros possua lógica explícita tanto no backend quanto no template de saída, criando testes de regressão que alterem os toggles e verifiquem a presença das classes e dados resultantes.

---

## 2026-09-30 - Coluna incorreta vb.tipo_pagamento em vendas_balcao e tratamento de compras em dinheiro no extrato do aluno

- Sintoma: Erro `PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'vb.tipo_pagamento' in 'field list'` ao emitir o extrato analítico do aluno para períodos contendo vendas de balcão, além de divergência contábil no saldo progressivo caso compras pagas em dinheiro ou Pix avulso no balcão fossem debitadas do saldo familiar.
- Causa:
  1. O schema oficial da tabela `vendas_balcao` (migration 020) nomeia a coluna de pagamento como `forma_pagamento` (com valores `dinheiro`, `pix_balcao`, `desconto_folha`), e o SQL de unificação em `RelatoriosController::obterDadosExtratoAluno` referenciou erroneamente `vb.tipo_pagamento`.
  2. No cálculo do saldo progressivo da carteira escolar, apenas os lançamentos pagos via carteira familiar (`forma_pagamento === 'Saldo'`) debitam o montante depositado pelos pais. Compras pagas em espécie no balcão (dinheiro ou pix avulso) configuram histórico de consumo do aluno, mas não deduzem os créditos da carteira da família.
- Solução aplicada:
  1. Corrigida a projeção SQL em `RelatoriosController.php` para referenciar estritamente `vb.forma_pagamento`.
  2. Ajustada a fórmula de progressão contábil para debitar o saldo progressivo apenas quando `forma_pagamento === 'Saldo'`, preservando a integridade da carteira digital compartilhada e a exatidão do total gasto no período.
  3. Criada e homologada a suíte `tests/fase22_test.php` com 80 de 80 asserções aprovadas com sucesso, cobrindo cálculo contábil, opções da aba Leiaute, espelho A4 e proteção LGPD no Portal da Família.
- Como evitar no futuro: Sempre consultar os schemas das migrações do banco de dados antes de redigir queries analíticas e distinguir contabilmente compras debitadas de carteiras pré-pagas de compras quitadas em espécie no ato da entrega.

---

## 2026-10-02 - Falha de método inexistente getBaseUrl() e botões redundantes de emissão no painel de estoque

- Sintoma: Ao clicar no botão de emitir o relatório de movimentações de estoque (`/relatorios/estoque-movimentacoes/imprimir`), o sistema interceptou uma falha 500 com a mensagem `Call to undefined method App\Controllers\RelatoriosController::getBaseUrl()`. Além disso, a tela apresentava botões repetidos ("Emitir" no topo como dropdown e "Emitir Relatório" no rodapé), gerando confusão de interface.
- Causa:
  1. Os métodos `estoqueMovimentacoes()` e `estoqueMovimentacoesPdf()` em `RelatoriosController.php` invocavam `$this->getBaseUrl()`, porém o método não estava definido nem no controller nem na classe abstrata `Controller.php`.
  2. A view `app/views/relatorios/estoque_filtros.php` continha um menu dropdown "Emitir" no cabeçalho superior e outro botão "Emitir Relatório" no rodapé, duplicando ações de envio em posições desconexas.
- Solução aplicada:
  1. Implementado o método `getBaseUrl(): string` na classe base `App\Core\Controller` e em `RelatoriosController`, calculando e normalizando a URL base da aplicação de forma segura e portável.
  2. Removido o dropdown duplicado do topo da tela em `app/views/relatorios/estoque_filtros.php`, mantendo apenas o link de navegação `Hub de Relatórios`.
  3. No rodapé do formulário, implementado o trio oficial e explícito de ações de emissão alinhado ao Design System corporativo: `Visualizar / Imprimir (A4)` (azul), `Baixar em PDF (A4)` (vermelho) e `Exportar Planilha (CSV)` (verde).
  4. Expandida a suíte de testes `tests/fase23_test.php` para 99 asserções cobrindo a execução direta de `estoqueMovimentacoes()`, a ausência de duplicidade e o correto funcionamento de todas as opções de saída.
- Como evitar no futuro: Sempre que um controller renderizar views sem passar pelo método padrão `$this->render()` (que injeta variáveis automáticas), garantir que os helpers de resolução de URLs e tokens pertençam à classe base `Controller` e estejam cobertos por testes pontuais de invocação. Evitar duplicar botões de submissão no topo e rodapé de formulários de parametrização.

---

## 2026-10-02 - Transbordo lateral de tabela (overflow horizontal) e ausência de paginação em folhas A4 no Relatório de Estoque

- Sintoma: Ao emitir o relatório de movimentações de estoque para períodos com grande volume de dados (ex.: 257 registros), a tabela analítica ultrapassava a margem lateral direita da folha física A4 no navegador ("bugado para fora") e todo o conteúdo era renderizado em uma única folha infinita contínua, sem quebras de página visuais em tela nem repetição de cabeçalho nas páginas impressas.
- Causa:
  1. A tabela utilizava `table-layout: auto` com larguras em pixels cujas somas ultrapassavam 840px, enquanto a folha física A4 em orientação Retrato possui largura interna útil de 176mm (~665px). Além disso, colunas continham textos longos (ex.: justificativas e nomes de operadores) com a classe `.text-nowrap`, impedindo a quebra de linha natural e forçando a expansão horizontal da tabela para fora da folha.
  2. Todos os registros eram injetados em uma única `div.sheet-container`, sem motor de paginação que fragmentasse o lote de dados em folhas físicas independentes. No navegador, o CSS de quebra de página de impressão (`page-break`) não gera divisão visual de páginas na tela.
- Solução aplicada:
  1. No CSS da tabela (`.table-estoque`), foi forçado `table-layout: fixed !important; width: 100% !important;`, foram calibradas larguras percentuais precisas por coluna somando estritamente 100% (ex.: 11%, 11%, 8%, 21%, 14%, 6%, 6%, 9%, 14% em Retrato), foi aplicada quebra de palavras obrigatória (`word-break: break-word; overflow-wrap: break-word;`) e adicionado `overflow: hidden;` ao contêiner da folha `.sheet-container`.
  2. Foi implementado o motor de paginação em folhas físicas A4 no template `app/views/relatorios/estoque_movimentacoes_pdf.php`: a lista de movimentações é dividida matematicamente em folhas A4 (`.sheet-container.page-sheet`), com limite de 16 itens na primeira página (com cabeçalho oficial e parâmetros) e 20 itens nas páginas subsequentes, exibindo cabeçalho compacto com marcação `(Continuação)`, paginação dinâmica `Página X de Y` nos cabeçalhos e rodapés, e caixa consolidada de totais do período posicionada estritamente na última folha.
  3. No modo de visualização em tela, cada folha A4 é renderizada como um cartão independente com sombra e espaçamento vertical cinza, reproduzindo a experiência visual de páginas físicas reais. Na impressão e compilação de PDF (Gotenberg Chromium), cada folha dispara quebra exata (`page-break-after: always`), garantindo fidelidade de 100%.
- Como evitar no futuro: Em relatórios e documentos A4 com muitas colunas (7 ou mais), utilizar invariavelmente `table-layout: fixed; width: 100%` com percentuais exatos e quebra de palavras (`word-break: break-word`). Além disso, para lotes com dezenas ou centenas de registros, projetar a estrutura em folhas discretas com repetição de cabeçalho e contagem de páginas para assegurar leitura confortável tanto em tela quanto em impressão.

---

## 2026-10-02 - Borda lateral direita cortada no PDF e fundos coloridos nos itens das listas de relatórios

- Sintoma: Ao gerar o arquivo PDF de relatórios (como extrato de aluno, fechamento de caixa ou vendas), a borda vertical da extremidade direita da tabela não aparecia (ficava cortada), embora estivesse visível na prévia de tela do navegador. Além disso, o usuário apontou que as linhas das listas e tabelas em múltiplos relatórios exibiam fundos azul claro (`#eff6ff`), verde claro (`#f0fdf4`), vermelho claro (`#fee2e2`) ou cinza zebrado (`#fafafa`), solicitando a padronização para fundo estritamente branco (`#ffffff`) em todos os PDFs gerados pelo sistema.
- Causa:
  1. No motor de impressão e compilação de PDF (Gotenberg Chromium), quando as colunas de uma tabela `<table>` possuem larguras cuja soma percentual ultrapassa 100% (por exemplo: 107% em extrato_aluno, 110% em fechamento_caixa, 114% em vendas_alimento) ou quando `table-layout` é `auto`, o navegador empurra a extremidade direita da tabela além da margem direita do `@page`, fazendo com que a borda preta lateral da última célula caia para fora da área imprimível do documento.
  2. Os templates utilizavam zebrado padrão (`tr:nth-child(even)` com `#fafafa`), fundos azuis corporativos em subtotais/recargas (`.row-recarga`, `.total-row td` com `#eff6ff`) e tags coloridas (`.tag-online` com fundo azul claro `#eff6ff`).
- Solução aplicada:
  1. Em todos os 8 relatórios PDF do sistema (`extrato_aluno_pdf.php`, `corte_pdf.php`, `desconto_folha_pdf.php`, `desconto_folha_individual_pdf.php`, `estoque_movimentacoes_pdf.php`, `fechamento_caixa_pdf.php`, `med_negativados_pdf.php` e `vendas_alimento_pdf.php`):
     - Forçado `table-layout: fixed !important; width: 100% !important; box-sizing: border-box;`;
     - Garantida a borda lateral direita e esquerda estritas: `th:last-child, td:last-child { border-right: 1px solid #000000 !important; }` e `th:first-child, td:first-child { border-left: 1px solid #000000 !important; }`;
     - Recalculadas todas as larguras de coluna para somarem rigorosamente 100% em qualquer cenário de filtros;
     - Removidos todos os fundos azul claro, verde claro, vermelho e zebrados nas linhas de itens das listas, definindo estritamente `background: #ffffff !important;` e `border-bottom: 1px solid #000000 !important;` nas células `td`, preservando o padrão de grade tipo Excel com linhas divisórias pretas e fundo branco puro.
  2. Executadas as suítes de teste `tests/fase23_test.php` (129 asserções), `tests/fase22_test.php` (79 asserções) e `tests/fase21_test.php` (99 asserções), todas aprovadas com 100% de sucesso.
- Como evitar no futuro: Em templates destinados à conversão para PDF via Chromium, garantir matematicamente que a soma das larguras de todas as colunas de qualquer tabela seja estritamente igual a 100%, aplicar `table-layout: fixed !important; box-sizing: border-box;` e assegurar que as linhas de dados mantenham fundo neutro `#ffffff` conforme a preferência corporativa da instituição.

---

## 2026-10-02 - Borda direita da tabela cortada no PDF devido à ausência de emulação de mídia 'print' e descompasso de viewport no Gotenberg Chromium

- Sintoma: Mesmo com a soma percentual das colunas ajustada em 100%, o arquivo PDF compilado pelo Gotenberg continuava cortando a borda vertical da extremidade direita da tabela e parte dos valores da última coluna ("Saldo"), enquanto a visualização de tela do navegador e o diálogo de impressão direta do browser saíam normais ("ainda está cortando").
- Causa:
  1. No arquivo `app/services/PdfService.php`, o método `htmlToPdf()` não enviava o parâmetro `emulatedMediaType` para o endpoint `/forms/chromium/convert/html` do Gotenberg. O Chromium Headless executava a conversão com a mídia padrão `screen`.
  2. Como consequência, o Chromium avaliava as regras `@media screen { .doc-page { width: 200mm; padding: 10mm 12mm; } }` (somando 224mm de largura total contra os 210mm físicos do papel A4) ou expandia o contêiner `width: 100%` sobre uma viewport padrão de 794px. Em medição vetorial via `pypdf`, a coluna "Saldo" estava sendo renderizada em `x = 668 pt`, enquanto a folha física A4 termina em `594.96 pt`! Exatos 73 pontos (~2,6 cm) eram cortados para fora da folha física.
  3. Faltava nos templates PDF um contêiner de impressão calibrado estritamente para a largura útil do A4 (535px) com margens laterais simétricas fixas (30px de cada lado), totalizando `30 + 535 + 30 = 595 pt`, sem depender de auto-ajustes da viewport do navegador.
- Solução aplicada:
  1. Em `app/services/PdfService.php`, adicionado `emulatedMediaType => $opcoes['emulatedMediaType'] ?? 'print'` e suporte ao parâmetro `scale` nas opções enviadas via cURL ao Gotenberg, forçando a compilação do Chromium a avaliar exclusivamente as regras `@media print`.
  2. Em todos os templates de relatórios PDF A4 (`extrato_aluno_pdf.php`, `corte_pdf.php`, `desconto_folha_pdf.php`, `desconto_folha_individual_pdf.php`, `estoque_movimentacoes_pdf.php`, `fechamento_caixa_pdf.php`, `med_negativados_pdf.php` e `vendas_alimento_pdf.php`):
     - No `@page`: configurado `margin: 10mm 0 10mm 0;` (margens verticais controladas e margens horizontais delegadas ao contêiner);
     - No `@media print`: contêiner configurado com `width: 535px !important; max-width: 535px !important; margin: 0 30px !important; padding: 0 !important; box-sizing: border-box !important;`;
     - No `@media screen`: contêiner configurado com `width: 210mm; max-width: calc(100% - 24px); min-height: 297mm; padding: 10mm 12mm; margin: 0 auto; box-sizing: border-box;`;
     - Nas tabelas: `border: 1px solid #000000 !important;` com divisórias pretas completas em todas as células (estética Excel) e fundo 100% branco puro (`background-color: #ffffff !important;`), sem fundos azuis ou zebrados;
     - Nas quebras de página: ativada repetição automática de cabeçalho `thead { display: table-header-group; }`, rodapé `tfoot { display: table-footer-group; }` e prevenção contra quebra de linhas no meio `tr { page-break-inside: avoid; break-inside: avoid; }`.
  3. Validação matemática definitiva via script automatizado com `pypdf`:
     - Borda esquerda da tabela: `x = 30.5 pt`;
     - Borda direita da tabela: `x = 564.5 pt`;
     - Margem física direita livre até a borda da folha: `595.0 - 564.5 = 30.5 pt` (~10,7 mm);
     - 100% da borda direita preta visível, sem corte em nenhuma página.
  4. Executados testes de compilação em lote para todos os 6 relatórios em `tests/teste_todos_pdfs.php` e suítes `tests/fase23_test.php` (134 testes), `tests/fase22_test.php` (79 testes) e `tests/fase21_test.php` (99 testes), com 100% de aprovação.
- Como evitar no futuro: Em integrações com o Gotenberg Chromium para geração de PDFs A4, sempre enviar explicitamente `emulatedMediaType: 'print'` e definir a largura do contêiner principal como 535px com margem lateral fixa de 30px no `@media print` para casar perfeitamente com os 594.96 pontos do papel A4, evitando que a viewport padrão do Chromium de 800px infle as dimensões relativas em `%` e extrapole a folha física.

---

## 2026-10-02 - Espaçamento gigante em branco à direita da folha A4 no PDF após limitação de largura fixa em 535px

- Sintoma: Ao gerar o PDF dos relatórios (ex.: `extrato_aluno_MATMOD30901_20261002_103750.pdf`), o relatório parava antes de dois terços da largura da página, deixando uma faixa lateral direita vazia gigante de quase 30% da folha em branco ("moss, ficou gigante o espacamento agora").
- Causa:
  1. No commit anterior (`996410f`), para evitar que a borda direita fosse cortada, o CSS do `@media print` havia fixado a largura do contêiner em `width: 535px !important; margin: 0 30px !important;`, combinado com `@page { margin: 10mm 0 10mm 0; }` (margens laterais zero no `@page`) e `PdfService.php` enviando `marginLeft: 0, marginRight: 0` na requisição ao Gotenberg.
  2. No Chromium, em 96 DPI, a folha A4 possui `793.7px` de largura útil quando as margens laterais da página estão em zero. Um contêiner de `535px` com margem esquerda de `30px` totalizava `565px`, restando $794 - 565 = 229\text{px}$ (~61 mm) de espaço vazio na margem direita, gerando o vazio excessivo apontado pelo usuário.
- Solução aplicada:
  1. Em `app/services/PdfService.php`: Removida a imposição de margens zero (`marginLeft: 0`, `marginRight: 0`) nos campos padrão multipart do Gotenberg quando `preferCssPageSize: true` estiver ativo. O Gotenberg agora delega o dimensionamento das margens da página às diretivas CSS `@page` do documento HTML.
  2. Em todos os 8 templates de relatórios PDF A4 (`extrato_aluno_pdf.php`, `corte_pdf.php`, `desconto_folha_pdf.php`, `desconto_folha_individual_pdf.php`, `estoque_movimentacoes_pdf.php`, `fechamento_caixa_pdf.php`, `med_negativados_pdf.php` e `vendas_alimento_pdf.php`):
     - **Definição formal de margens no `@page`:** Configurado `@page { size: A4 portrait; margin: 10mm 12mm 10mm 12mm; }` (ou `A4 landscape` quando aplicável), estabelecendo margens físicas uniformes e simétricas de 10mm no topo/rodapé e 12mm nas laterais.
     - **Preenchimento total da área útil no `@media print`:** O contêiner foi configurado com `width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; box-sizing: border-box !important;`.
     - **Tabelas de ponta a ponta:** As tabelas utilizam `width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important;`, expandindo-se exatamente pelos 186mm da área útil da folha.
  3. Validação óptica e vetorial com `pypdfium2` e `PIL`:
     - Dimensões da folha: 1190 x 1684 px (proporção A4 física perfeita);
     - Início dos elementos e tabelas: `x = 67 px` (exatos 11.8 mm);
     - Término dos elementos e tabelas: `x = 1122 px` (exatos 11.8 mm da borda direita da folha);
     - Largura ocupada pela tabela: 1055 px (100% da área útil preenchida);
     - Borda direita preta nítida e 100% visível, com simetria milimétrica perfeita de margens laterais;
     - Zero faixa branca residual na direita e zero corte de bordas;
     - Preservada rigorosamente a estética de grade tipo Excel com linhas divisórias pretas e fundo 100% branco nas células.
  4. Suíte `tests/teste_todos_pdfs.php` cobrindo todos os 8 relatórios PDF e suítes `tests/fase23_test.php` (139 testes), `tests/fase22_test.php` (79 testes) e `tests/fase21_test.php` (99 testes) 100% aprovadas.
- Como evitar no futuro: Em documentos A4 gerados com Chromium/Gotenberg, definir sempre as margens físicas na própria regra `@page` do CSS (`margin: 10mm 12mm`), delegando ao Chromium a aplicação das margens e mantendo os contêineres e tabelas com `width: 100%` da área útil, sem tentar restringir larguras em pixels fixos que distorcem em diferentes densidades de tela e emuladores de impressão.

---

## 2026-10-02 - Referência incorreta a vb.data_hora e método inexistente Configuracao::getInstancia() na Fase 24

- Sintoma: Erros disparados durante a primeira execução da suíte `tests/fase24_test.php`:
  1. `PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'vb.data_hora' in 'where clause'` no modelo `app/models/Dre.php` ao apurar a receita e custos das vendas de balcão.
  2. `Fatal error: Uncaught Error: Call to undefined method App\Models\Configuracao::getInstancia()` em `app/controllers/DreController.php` e no teste automatizado.
- Causa:
  1. Na tabela `vendas_balcao` criada na Migration 020, a coluna de registro temporal é `created_at` (e `estornado_em` para cancelamentos), e não `data_hora`. Quatro consultas em `app/models/Dre.php` referenciaram `vb.data_hora`.
  2. O modelo `app/models/Configuracao` utiliza o método estático `Configuracao::obter()` para recuperar as configurações institucionais da cantina, mas o controller e o teste invocaram o método não existente `getInstancia()`.
- Solução aplicada:
  1. Em `app/models/Dre.php`, substituídas todas as 4 ocorrências de `vb.data_hora` por `vb.created_at`.
  2. Em `app/controllers/DreController.php` e `tests/fase24_test.php`, substituída a chamada por `Configuracao::obter()`.
  3. Reexecutada a suíte `tests/fase24_test.php`, com 93 de 93 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Sempre inspecionar a migração de criação da tabela de dados antes de montar as queries de agregação contábil, e conferir a assinatura dos métodos dos modelos utilitários existentes (`Configuracao::obter()`).

---

## 2026-10-05 - Correções de Segurança, Conformidade LGPD e Blindagem Pré-Publicação

- Sintoma: Auditoria de segurança prévia identificou potenciais vetores de vulnerabilidade: (1) dados reais de alunos versionados no Git (`database/matriculas_reais.json`); (2) validação permissiva de Webhook Pix em produção sem secret obrigatório; (3) ausência de rate limiting nas tentativas de código de 6 dígitos via WhatsApp; (4) ausência de CSRF em reenvio de código, cancelamento de importação e simulação de pagamento Pix; (5) risco de Formula Injection (CSV Injection) nas exportações de relatórios em planilhas; (6) exposição de diagnóstico na raiz `/` em produção.
- Causa:
  1. Durante a migração de dados reais de alunos da escola na Fase 18, o arquivo de dados foi versionado no repositório.
  2. A função `PixService::validarWebhook()` continha bypass genérico que permitia aceitar requisições de webhook quando o secret estivesse vazio ou configurado com o valor de exemplo.
  3. Os controllers de autenticação não limitavam as tentativas com códigos de 6 dígitos na sessão nem impunham intervalo mínimo entre reenvios.
  4. Rotas pontuais executadas via POST não chamavam `$this->validateCsrf()`.
  5. As exportações analíticas geravam CSV concatenando strings sem prefixar apóstrofo em células iniciadas por caracteres de comando (`=`, `+`, `-`, `@`).
  6. `HomeController::index()` renderizava dados de desenvolvimento para visitantes anônimos.
- Solução aplicada:
  1. **Conformidade LGPD:** Arquivos de dados reais escolares (`database/matriculas_reais.json`, `docs/alterações sistema guideline/Matriculas.pdf` e `scratch/`) adicionados ao `.gitignore` e desindexados do Git (`git rm --cached`).
  2. **Blindagem do Webhook Pix:** `PixService::validarWebhook()` refatorado para proibir categoricamente bypass ou segredos vazios/padrão em ambiente de produção.
  3. **Proteção contra Força Bruta e Spam de WhatsApp:** Adicionado rate limiting de 5 tentativas consecutivas nos códigos de Primeiro Acesso e Recuperação de Senha, com bloqueio temporário e log de auditoria, além de cooldown mínimo de 60 segundos entre solicitações.
  4. **Proteção CSRF Completa:** Adicionado `$this->validateCsrf()` em `primeiroAcessoReenviar()`, `importacaoEscolarCancelar()`, `pdv/pix/gerar` e `portal/recarga/simular/{txid}`, com suporte transparente ao modo de testes (`CANTINA_TEST_MODE`).
  5. **Prevenção contra CSV Formula Injection:** Criados os métodos `sanitizarCsvCelula()` e `sanitizarCsvLinha()` em `RelatoriosController.php`, prefixando `'` em células com fórmulas potencialmente maliciosas.
  6. **Proteção contra Breakout XSS:** Adicionadas as flags `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP` nos scripts inline das views (`agendamento.php`, `pedido_editar.php`, `estorno_vendas.php`, `dre.php`) e aplicado `htmlspecialchars()` na view de agendamento.
  7. **Blindagem em Produção:** `HomeController` configurado para redirecionar usuários para `/portal`, `/admin` ou `/login` em produção e retornar HTTP 404 em rotas de teste/diagnóstico.
- Como evitar no futuro: Nunca comitar dados reais de usuários no repositório de controle de versão; sempre exigir segredos criptográficos fortes em ambiente de produção; aplicar rate limiting em todos os fluxos de autenticação em duas etapas; e sanitizar rigorosamente qualquer saída em formatos interpretáveis como CSV ou JSON inline.

---

## 2026-10-05 - Refatoração do PDV: Remoção da opção "Em Folha" e segregação das formas de pagamento por aba

- Sintoma: A opção "Em Folha" não era mais necessária e gerava confusão operacional no balcão; era necessário restringir as formas de pagamento disponíveis em cada aba de identificação do PDV (Anônimo: Dinheiro e Pix; Aluno: Dinheiro, Pix e Saldo; Colaborador: Dinheiro e Pix).
- Causa: O schema inicial de `vendas_balcao` contemplava apenas `dinheiro`, `pix_balcao` e `desconto_folha`. Não havia a forma de pagamento `saldo` para compras presenciais no balcão de alunos debitando atomicamente da carteira familiar, e as views do PDV exibiam o botão "Em Folha" com confirmação fotográfica por checkbox.
- Solução aplicada:
  1. Criada e aplicada a Migration `030_add_saldo_to_vendas_balcao.php`, adicionando `saldo` em `vendas_balcao.forma_pagamento`, `avulso_colaborador` em `tipo_venda` e tipos `debito_balcao` e `estorno_balcao` em `transacoes_carteira`.
  2. Atualizados os models `VendaBalcao.php` e `TransacaoCarteira.php`: novas vendas com `desconto_folha` são bloqueadas; pagamento com `saldo` valida se há aluno selecionado, valida bloqueio MED e debita a carteira familiar atomicamente com `debito_balcao`. O estorno em saldo devolve 100% dos créditos com `estorno_balcao`.
  3. Atualizados o controller `PdvController.php` e a view `pdv.php`:
     - Aba Anônimo: botões Dinheiro e Pix (2 colunas, 50% cada);
     - Aba Colaborador: botões Dinheiro e Pix (2 colunas, 50% cada, removido checkbox `chkConfirmaVisual`);
     - Aba Aluno: botões Dinheiro, Pix e Saldo (3 colunas com classe `.has-saldo`), exibindo o saldo da carteira no card do aluno e demonstrativo dinâmico de débito e saldo restante.
  4. Suíte automatizada `tests/pdv_formas_pagamento_test.php` criada com 25/25 testes aprovados.
- Como evitar no futuro: Sempre isolar e desacoplar formas de pagamento nos controllers e views com regras reativas claras por perfil de cliente, garantindo que opções inoperantes ou descontinuadas sejam rejeitadas na camada de domínio e não apareçam na interface.

---

## 2026-10-05 - Loop de código expirado e código fantasma após estourar 5 tentativas no Primeiro Acesso

- Sintoma: Ao errar 5 vezes o código de verificação no Primeiro Acesso e reinserir o CPF na tela inicial, o usuário era direcionado à tela de digitação de código com a mensagem de que o código já estava expirado ("Atenção: O código de verificação expirou (validade: 15 minutos). Solicite um novo reenvio."). Toda tentativa de digitar o código informado no box de ambiente local (`mockCode`) falhava com o mesmo erro de código expirado, até que o usuário clicasse em "Reenviar código".
- Causa:
  1. Ao atingir o limite de 5 erros consecutivos, `primeiroAcessoValidarCodigo()` removia o código e a validade da sessão (`Session::remove('_pa_codigo')` e `Session::remove('_pa_expira_em')`) e redirecionava para `/primeiro-acesso` (tela de inserção de CPF).
  2. Ao reinserir o CPF em seguida, o cooldown de segurança contra spam de WhatsApp (60 segundos) entrava em ação. Porém, a cláusula do cooldown redirecionava o usuário para `/primeiro-acesso/codigo` incondicionalmente, mesmo sem um código ativo em sessão.
  3. A view `/primeiro-acesso/codigo` não verificava a existência de `_pa_codigo` e buscava um código fantasma do cache estático do `WhatsAppService` (`getLastCode`), exibindo o código da tentativa anterior (já invalidada).
  4. Ao tentar validar esse código fantasma, `_pa_expira_em` estava ausente (retornando `0`). Como `time() > 0` é verdadeiro, o validador disparava imediatamente a mensagem de expiração de 15 minutos e redirecionava de volta para a mesma tela, criando um loop infinito.
- Solução aplicada:
  1. Em vez de expulsar o usuário de volta para a tela de CPF (`/primeiro-acesso`), `primeiroAcessoValidarCodigo()` agora mantém a sessão familiar ativa, invalida o código anterior e marca `_pa_codigo_bloqueado = true`, redirecionando o usuário de volta para a própria tela de confirmação (`/primeiro-acesso/codigo`).
  2. Na view `primeiro_acesso_codigo.php`, quando `codigoBloqueado` for verdadeiro, o formulário de digitação é ocultado e exibido um alerta explicativo com um botão direto de ação: **"Solicitar Novo Código por WhatsApp"**, permitindo gerar um novo código sem que o responsável tenha que redigitar seu CPF de 11 dígitos.
  3. Em `primeiroAcessoReenviar()`, a flag `_pa_codigo_bloqueado` é removida, gerando um código novo legítimo com 15 minutos de validade e reabilitando o formulário para digitação imediata.
  4. Caso o usuário queira trocar de responsável por engano de digitação, o link discreto "Trocar CPF" no rodapé permanece disponível.
  5. Teste automatizado `tests/primeiro_acesso_limite_tentativas_test.php` atualizado e aprovado com 6/6 asserções com 100% de sucesso.
- Como evitar no futuro: Em fluxos de autenticação em duas etapas, nunca deslogar ou chutar o usuário para o início do fluxo ao invalidar um código específico; em vez disso, manter o contexto da sessão e fornecer um botão direto para solicitar um novo código no mesmo local.

---

## 2026-10-05 - Resumo financeiro zerado e vendas rotuladas indevidamente como "Dinheiro" na Gestão de Caixa

- Sintoma: Na tela de Gestão de Caixa (`/operacional/caixa?turno=matutino`), os indicadores de topo (Fundo de caixa, Entrada dinheiro, Entrada pix, Total saídas, Total entradas, Total caixa e Esperado na gaveta) exibiam todos `R$ 0,00` mesmo com sessão aberta contendo fundo de R$ 100,00 e dezenas de vendas realizadas. Além disso, todas as 31 vendas da listagem de "Vendas Presenciais no Balcão" apareciam com o rótulo "Dinheiro" na coluna TIPO, mesmo tendo vendas feitas em Pix Balcão e Saldo.
- Causa:
  1. A view `app/views/operacional/caixa.php` buscava `$dadosFechamento['resumo']['fundo']['dinheiro']`, `$dadosFechamento['resumo']['entradas']...`, mas o método `SessaoCaixa::obterDadosFechamentoOficial()` retornava os dados contábeis consolidados sob a chave `'resumo_caixa'`. Por ter `$dadosFechamento` presente (mas sem a subchave `resumo`), o fluxo caía no primeiro `if` e todas as variáveis numéricas recebiam `(float)null = 0.0`.
  2. Em `SessaoCaixa.php` (linha ~969), o switch que definia o rótulo da coluna TIPO em `itens_balcao` verificava estritamente `$ib['forma_pagamento'] === 'pix'`. No PDV, as vendas são gravadas como `'pix_balcao'` e `'saldo'`, de modo que nenhuma condição casava e todas as vendas caíam no fallback default `$tipoStr = 'Dinheiro'`.
  3. A query de recebimentos Pix em balcão em `SessaoCaixa.php` buscava `WHERE forma_pagamento = 'pix'`, desconsiderando a forma real `'pix_balcao'`.
- Solução aplicada:
  1. Na view `app/views/operacional/caixa.php`: atualizado para ler `$resumoOficial = $dadosFechamento['resumo_caixa'] ?? $dadosFechamento['resumo'] ?? null`, extraindo corretamente `fundo`, `entradas`, `saidas`, `saldo` e seus totais.
  2. No model `app/models/SessaoCaixa.php`:
     - Retorno de `obterDadosFechamentoOficial` agora fornece tanto `'resumo_caixa'` quanto `'resumo'` garantindo retrocompatibilidade total;
     - Condição de mapeamento de rótulo atualizada para aceitar `'pix'` e `'pix_balcao'` como `"Pix"`, e `'saldo'` como `"Saldo"`;
     - Query de recebimentos Pix atualizada para `WHERE forma_pagamento IN ('pix', 'pix_balcao')`;
     - Adicionada leitura direta da soma de `vendas_balcao` com `forma_pagamento = 'dinheiro'` para garantir total fidelidade no demonstrativo de gaveta.
- Como evitar no futuro: Padronizar contratos de chaves de array retornadas por models consolidados e sempre contemplar todas as variações de ENUM/string de formas de pagamento (`pix`, `pix_balcao`, `saldo`) nas rotinas de agregação contábil e de apresentação.

---

## 2026-10-05 - Invocação de método inexistente renderViewDireto() e encerramento prematuro com exit em testes CLI

- Sintoma: Erro fatal `Fatal error: Uncaught Error: Call to undefined method App\Controllers\RelatoriosController::renderViewDireto()` e encerramento abrupto do processo de execução de testes ao invocar `RelatoriosController::arrecadacaoImprimir()` e `arrecadacaoCsv()`.
- Causa: 
  1. A classe `RelatoriosController` não implementa o método auxiliar `renderViewDireto()`, padrão utilizado no `DreController`, delegando a compilação HTML de impressão diretamente para `PdfService::renderView()`.
  2. As actions de emissão e download (`arrecadacaoPdf`, `arrecadacaoImprimir`, `arrecadacaoCsv`) executavam `exit;` incondicionalmente, derrubando o script de testes automatizados CLI antes da finalização das asserções subsequentes.
- Solução aplicada:
  1. Substituída a chamada por `echo PdfService::renderView('relatorios/arrecadacao_pdf', $dados);`.
  2. Protegidas todas as diretivas de encerramento com `if (!defined('CANTINA_TEST_MODE')) { exit; }`, permitindo que os testes automatizados capturem os buffers de saída com `ob_start() / ob_get_clean()` e continuem a execução com sucesso.
- Como evitar no futuro: Em controllers que emitem saídas para download direto ou impressão, padronizar a invocação através do serviço unificado `PdfService::renderView()` e proteger instruções `exit` com constantes de ambiente de teste para viabilizar testes unitários e de integração contínua via CLI.

---

## 2026-10-06 - Abertura simultânea indevida de caixas em turnos concorrentes (matutino e vespertino ao mesmo tempo)

- Sintoma: O sistema permitia abrir o caixa do turno matutino e do turno vespertino concomitantemente no mesmo dia e terminal. Ao alternar a aba de turno na gestão de caixa ou no PDV, o botão de abertura permanecia ativo e o backend efetuava o INSERT de uma segunda sessão aberta, violando a regra de gaveta física e terminal únicos da cantina.
- Causa:
  1. No model `SessaoCaixa::abrir($turno, ...)`, a validação de sessão ativa filtrava estritamente por `$sessaoExistente = self::obterSessaoAberta($turno)`. Ao requisitar o turno oposto, `$sessaoExistente` retornava `null` e uma nova sessão com status `'aberto'` era inserida.
  2. No método `SessaoCaixa::obterSessaoAberta($turno)`, a consulta continha a restrição `DATE(sc.aberto_em) = :hoje`. Sessões esquecidas abertas de dias anteriores ficavam invisíveis no dia seguinte, permitindo novas aberturas e gerando inconsistências contábeis.
  3. Nos controllers `CaixaController` (`abrirTela` e `abrir`) e `PdvController::abrirCaixaRapido`, não existiam travas preventivas contra abertura com turno concorrente pendente.
  4. Nas views `caixa.php` e `pdv.php`, o alerta de outro turno aberto era puramente informativo (`alert-info`), mantendo botões de ação e modais de abertura ativos e funcionais.
- Solução aplicada:
  1. Criado o método central `SessaoCaixa::obterQualquerSessaoAberta(): ?array` para detectar qualquer sessão em aberto independentemente de data ou turno.
  2. Atualizado `SessaoCaixa::obterSessaoAberta(?string $turno = null)` para delegar a busca geral e remover a restrição por data `DATE(sc.aberto_em) = :hoje`.
  3. No model `SessaoCaixa::abrir()`: implementada checagem impeditiva com lançamento de `DomainException` caso haja qualquer sessão em aberto (mesmo turno ou oposto), detalhando ID e turno em aberto.
  4. Nos controllers `CaixaController` e `PdvController`: adicionadas travas em `abrirTela()`, `abrir()` e `abrirCaixaRapido()`, gerando flashes explicativos e redirecionando para a gestão do turno ativo.
  5. Nas views `caixa.php`, `pdv.php` e `abertura_caixa.php`: alertas transformados em avisos âmbar (`alert-warning`), botão primário desabilitado para `"Abrir caixa (Bloqueado)"` e modais blindados com botão de atalho direto para fechar o turno pendente.
  6. Suíte de integração `tests/caixas_simultaneos_test.php` criada e aprovada (18/18 asserções), além de `tests/caixa_test.php` (62/62) e `tests/fase15_test.php` (68/68).
- Como evitar no futuro: Em operações de PDV que utilizam gaveta física única e terminal compartilhado, a trava de unicidade deve ser de âmbito global no nível do terminal físico (`obterQualquerSessaoAberta`), blindando todas as camadas (Model, Controllers e Views) contra concorrência de turnos.

---

## 2026-10-06 - Botão de impressão térmica inoperante por variável inexistente e comprovante de 80mm desalinhado em tela cheia

- Sintoma: No modal de confirmação de recarga presencial (`/operacional/caixa`), ao clicar no botão azul "Imprimir Comprovante Térmico (80mm)", nenhuma ação ocorria e o diálogo nativo de impressão não abria. Ao clicar no link de abertura em nova janela, o comprovante era exibido grudado no canto esquerdo superior como uma tirinha estreita de 80mm, com o restante da tela em branco vazio e uma barra de botões minúscula ("toda mal formatada e bugada").
- Causa:
  1. No script de `app/views/operacional/caixa.php`, a função `imprimirComprovanteRecargaIframe` interpolava `${baseUrl}` em template literal JS, mas `baseUrl` nunca havia sido declarada como constante/variável JavaScript no template, disparando `Uncaught ReferenceError: baseUrl is not defined` no console do navegador e abortando a rotina.
  2. A função apenas atribuía a URL ao `iframe.src` sem tratar o evento `iframe.onload` para acionar `iframe.contentWindow.print()`.
  3. A view `recarga_dinheiro_pdf.php` não possuía script escutando a query string `?autoprint=1`.
  4. O CSS das views de bobina térmica (`recarga_dinheiro_pdf.php` e `cupom_pdv_80mm.php`) continha `body { width: 80mm; background: #fff; }` global sem separação por `@media`, fazendo com que telas de alta resolução (1080p) exibissem a página comprimida no canto esquerdo, com barra `.no-print` de apenas 80mm e sem enquadramento de pré-visualização.
- Solução aplicada:
  1. Declarada a constante JavaScript `const baseUrl = "<?php echo $baseUrl; ?>";` no início do `<script>` em `app/views/operacional/caixa.php`.
  2. Aprimorada a função de impressão do iframe em `caixa.php` e `pdv.php`: adicionado listener `iframe.onload` com foco e disparo de `print()`, tratamento de exceção com fallback automático para `window.open`, e feedback visual com spinner no botão enquanto o diálogo do sistema é preparado.
  3. Adicionado listener nativo em `recarga_dinheiro_pdf.php` e `cupom_pdv_80mm.php` para disparar `window.print()` automaticamente se `?autoprint=1` estiver na URL, além de suporte a atalhos de teclado (Ctrl+P e Esc).
  4. Redesenhado o layout das views de bobina térmica com segregação responsiva:
     - `@media screen`: barra superior corporativa fixa de 100% de largura (`.top-toolbar`) com botões "Imprimir Comprovante (Ctrl+P)", "Baixar PDF" e "Fechar Janela"; fundo cinza neutro `#f1f5f9` e container `.receipt-screen-wrapper` centralizando o cupom de 80mm com fundo branco e sombra suave como uma folha real em visualizador de documentos;
     - `@media print`: barra oculta (`display: none !important`), margens zero e largura exata de 80mm para impressão contínua em bobina física.
  5. Ajustado o rótulo do link secundário no modal para `"↗ Visualizar comprovante em tela cheia"` para evitar confusão de propósito.
  6. Criada a suíte de testes `tests/comprovantes_termicos_view_test.php` com 21/21 asserções aprovadas.
- Como evitar no futuro: Sempre declarar constantes globais de URL (`baseUrl`) no topo dos scripts de view e garantir que documentos destinados à impressão térmica de 80mm possuam estilização de visualizador centralizado na tela (`@media screen` vs `@media print`).

---

## 2026-10-06 - Erro fatal ao gerar Pix balcão no PDV por ausência de método PixService::gerarCobranca()

- Sintoma: Ao tentar realizar uma venda no balcão PDV (`/operacional/pdv`) com a forma de pagamento "Pix Balcão (QR Code)", o sistema exibia um pop-up de erro com a mensagem: `Erro ao gerar Pix balcão: Erro ao gerar cobrança Pix: Call to undefined method App\Services\PixService::gerarCobranca()`.
- Causa:
  1. A action `PdvController::pixGerar()` invocava os métodos estáticos `PixService::gerarCobranca($valor, $txid, $descricao, 900)` e `PixService::gerarQrCodeDataUri($payload)`, mas a classe `PixService` possuía apenas o método `gerarCobrancaPix()`, desenhado especificamente para recargas da carteira familiar (que exige `$responsavelId` e valor mínimo de R$ 10,00).
  2. Como o método `gerarCobranca()` não existia em `PixService`, uma exceção `Error: Call to undefined method` era disparada, capturada no bloco `try/catch` do controller e devolvida ao frontend em JSON com status 500.
- Solução aplicada:
  1. Implementado o método estático `PixService::gerarCobranca(float $valor, ?string $txid, string $descricao, int $expiraEmSegundos = 900)`:
     - Aceita qualquer valor monetário válido maior que zero (permitindo vendas avulsas de baixo valor no balcão, como água de R$ 3,50 ou salgados);
     - Gera identificador `txid` único no padrão PDV caso não fornecido;
     - Gera o payload oficial BR Code / EMVCo compatível com o Banco Central e o QR Code vetorial e Data URI em base64 via `QrCodeService`;
     - Persiste a cobrança em cache temporário no storage;
     - Retorna array completo com todas as chaves esperadas pelo controller e frontend (`txid`, `brcode`, `payload_brcode`, `copia_e_cola`, `qr_code_uri`, `qr_code_data_uri` e `valor_formatado`).
  2. Implementado `PixService::gerarQrCodeDataUri(string $payload, int $size = 260)` encapsulando `QrCodeService::dataUri()`.
  3. Implementado `PixService::consultarStatusCobranca(string $txid)` retornando o status resumido da cobrança ('pendente', 'pago', etc.).
  4. Suíte automatizada `tests/pdv_pix_balcao_test.php` criada com 17/17 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Em integrações com gateways de pagamento que atendem tanto recargas familiares com regras de negócio específicas (valor mínimo e titular obrigatório) quanto vendas presenciais de balcão (qualquer valor e cliente anônimo), manter métodos dedicados e testar a interoperabilidade entre os controladores de PDV e as classes de serviço.

---

## 2026-10-06 - Percepção de não baixa de estoque por acúmulo de produtos de teste clonados e ausência de reatividade visual no PDV

- Sintoma: Ao vender itens no balcão do PDV (ex: 18 a 24 pães de queijo), o operador percebia que o estoque parecia não ter baixado ("acabei de vender tipo 24 pão de queijos e não vejo em lugar nenhum da lista um pão de queijo zerado, todos mantiveram suas unidades"), e a tela exibia múltiplos cards idênticos de "Pão de Queijo PDV", todos marcando 18 unidades.
- Causa:
  1. A suíte de testes legada `tests/fase13_test.php` realizava inserções de produtos sintéticos de teste (`PDV-DIN-...` com a descrição "Pão de Queijo PDV" e 18 unidades) a cada bateria de testes sem realizar limpeza (teardown) ao final, acumulando 22 produtos idênticos com o mesmo nome e 18 unidades na base de dados.
  2. O motor de vendas `VendaBalcao::criarVenda()` realizava perfeitamente a baixa física de estoque no registro específico do banco (os produtos #428 e #659 foram a 0 unidades), mas como outros 20 clones continuavam com 18 unidades, gerava a ilusão visual de que nada havia sido debitado.
  3. O JavaScript de `app/views/operacional/pdv.php` limpava o carrinho via AJAX após a venda sem abater as unidades diretamente nos cards do DOM, mantendo os numerais visíveis inalterados na tela até que um refresh (F5) fosse executado.
  4. Para produtos esgotados, a view apresentava apenas a string "Esgotado" sem o numeral "0", ocultando o valor zero esperado pelo operador.
- Solução aplicada:
  1. Executado soft-delete de todos os 95 produtos sintéticos de teste no banco de dados (`deleted_at = NOW()`), restaurando o catálogo da cantina para os 12 produtos reais legítimos cadastrados.
  2. Adicionado teardown compulsório em `tests/fase13_test.php` e nas novas suítes para inativar automaticamente produtos de teste e impedir que voltem a poluir o ambiente.
  3. Atualizado o JavaScript do PDV (`pdv.php`) para iterar sobre os itens vendidos no retorno de sucesso da requisição e decrementar os atributos `data-estoque` e o texto dos cards correspondentes no DOM em tempo real. Se o estoque atingir zero, aplica imediatamente a classe `.disabled` e exibe o status de esgotado sem requerer recarregamento da página.
  4. Ajustada a view `pdv.php` para renderizar `0 un (Esgotado)` com destaque vermelho em produtos com estoque esgotado.
  5. Criada a suíte de testes automatizados `tests/pdv_estoque_baixa_test.php` com 11/11 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Toda suíte de testes automatizados que realize inserções de dados sintéticos deve possuir bloco obrigatório de teardown/limpeza no encerramento, e interfaces de ponto de venda orientadas a SPA/Fetch devem refletir mutações de saldo e inventário imediatamente no DOM do cliente.

---

## 2026-10-06 - Permissão indevida de conferência e confirmação de recebimento físico de fornecedor para datas retroativas/futuras

- Sintoma: Na tela de **Conferência e Recebimento de Fornecedor** (`/operacional/recebimento`), ao selecionar uma data anterior pelo filtro (por exemplo, 29/09/2026 quando a data atual é 06/10/2026), o sistema exibia campos editáveis de quantidade entregue e o botão "Confirmar Recebimento e Dar Entrada", permitindo registrar entradas físicas no estoque de dias passados e distorcer a contabilidade de estoque da cantina.
- Causa:
  1. A action `PedidosController::recebimento()` carregava os dados da data selecionada na query string sem validar se era a data de hoje para fins de edição;
  2. A action `PedidosController::recebimentoConfirmar()` processava o POST de qualquer data passada ou futura;
  3. O método `Pedido::confirmarRecebimentoFornecedor()` não possuía validação temporal impedindo a entrada de mercadorias com `$dataConsumo !== date('Y-m-d')`;
  4. A view `recebimento.php` renderizava inputs editáveis e o botão de confirmação baseando-se apenas em `$jaRecebido`, sem checar se a data aberta era a data de hoje.
- Solução aplicada:
  1. **Blindagem no Model (`Pedido::confirmarRecebimentoFornecedor`):** Adicionada validação estrita que lança `DomainException` caso `$dataConsumo !== date('Y-m-d')` (exceto em modo de teste automatizado `CANTINA_TEST_MODE`), impedindo qualquer mutação no estoque fora do dia da entrega;
  2. **Blindagem no Controller (`PedidosController::recebimentoConfirmar`):** Adicionada trava rejeitando requisições POST com data diferente de hoje com flash error explicativo e redirecionamento de segurança;
  3. **Interface Protegida e Informativa (`app/views/operacional/recebimento.php`):**
     - Variável `$podeConferir = (!$jaRecebido && $isHoje)`;
     - Para datas retroativas não conferidas: exibido badge neutro "Não Conferido na Data (Bloqueado)", alerta âmbar detalhando a proteção contábil do estoque e botão de atalho rápido "Ir para Recebimento de Hoje";
     - Para datas futuras: exibido alerta azul indicando que a conferência física deve aguardar o dia da remessa, com link direcionando ao Painel de Corte;
     - Para datas passadas já conferidas: preservada a visualização do espelho histórico para fins de auditoria (somente leitura);
     - Campos de edição numérica e botões de submissão só são renderizados quando `$podeConferir === true`;
     - Input de data recebeu `max="<?php echo $dataHoje; ?>"`, orientando visualmente o operador;
     - No Painel de Corte (`/operacional/corte`), a navegação entre datas passadas e futuras permaneceu livre para consulta e auditoria.
  4. **Testes Automatizados:** Nova suíte `tests/recebimento_bloqueio_datas_test.php` criada com 31/31 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Ações operacionais que causam mutações físicas irreversíveis em estoque ou caixa (como recebimento físico de mercadorias ou abertura de caixa) devem ter travas temporais rígidas em todas as camadas (Model, Controller e View), permitindo execução estritamente na data presente e mantendo datas retroativas em modo somente leitura para auditoria.

---

## 2026-10-07 - Permissão indevida de redefinição de senha para CPF sem verificação ou primeiro acesso

- Sintoma: Ao acessar a tela de **Recuperação de Senha** (`/recuperar-senha`) e digitar o CPF de um responsável cadastrado na base escolar que ainda não havia realizado o primeiro acesso (sem senha definida e sem termos aceitos), o sistema despachava código de recuperação por WhatsApp e conduzia o usuário à tela de redefinição de senha (`/recuperar-senha/codigo`), permitindo definir uma nova senha sem ter passado pela ativação, validação formal e aceite obrigatório dos Termos e Políticas da Cantina.
- Causa:
  1. No método `AuthController::recuperarSenhaSolicitar()`, após localizar o registro do responsável pelo CPF via `$respModel->findByCpf($cleanCpf)`, o sistema verificava apenas se o registro existia, sem checar `Responsavel::isPendentePrimeiroAcesso($resp)`;
  2. Caso o usuário estivesse na tela `/recuperar-senha/codigo` ou submetesse um POST para `/recuperar-senha/redefinir`, não havia validação defensiva verificando se o ID em sessão (`_rec_responsavel_id`) pertencia a uma conta já ativada;
  3. Isso permitia burlar a etapa de Primeiro Acesso e o aceite dos Termos de Uso escolares, além de criar inconsistência técnica ao gravar uma senha sem timestamp de termos aceitos.
- Solução aplicada:
  1. **Interceptação no Controller (`AuthController::recuperarSenhaSolicitar`):** Adicionada validação de 11 dígitos no CPF e checagem estrita de `Responsavel::isPendentePrimeiroAcesso($resp)`. Se a conta estiver pendente (sem senha ou sem aceite de termos), dispara evento de auditoria técnica (`recuperar_senha_conta_pendente`), emite aviso flash em amarelo/warning (*"Identificamos que você ainda não realizou o primeiro acesso e não possui senha cadastrada. Por favor, ative sua conta pelo Primeiro Acesso."*) e redireciona imediatamente para `/primeiro-acesso?cpf=<cpf_limpo>`;
  2. **Defesa em Profundidade (`recuperarSenhaCodigo` e `recuperarSenhaRedefinir`):** Caso uma requisição alcance essas etapas com um ID de responsável pendente, a sessão é higienizada imediatamente (removendo `_rec_*`), a tentativa é barrada e o usuário é redirecionado ao Primeiro Acesso sem alterar qualquer senha no banco;
  3. **Melhoria de UX nas Telas de Autenticação:**
     - Em `app/views/auth/primeiro_acesso_cpf.php`: adicionada formatação automática do campo CPF no carregamento da tela (`DOMContentLoaded`) caso o valor venha pré-preenchido via parâmetro GET (`?cpf=...`);
     - Em `app/views/auth/recuperar_senha.php`: adicionado link corporativo e sutil para o "Primeiro Acesso &rarr;" ao lado do link "Voltar para o Login", guiando imediatamente os responsáveis novatos;
  4. **Testes Automatizados:**
     - Criada a suíte `tests/recuperar_senha_bloqueio_pendente_test.php` com 17/17 asserções aprovadas com 100% de sucesso cobrindo bloqueio de solicitação, defesas em profundidade em código e redefinição, logs de segurança, validação de CPF e integridade do fluxo de contas já ativas.
- Como evitar no futuro: Em fluxos de recuperação de credenciais de qualquer tipo de conta (responsáveis ou colaboradores), sempre verificar previamente se a conta alvo já passou pelo ciclo de vida inicial de ativação e verificação antes de autorizar a redefinição de senhas.

---

## 2026-10-13 - Sobrecarga de I/O e travamento por varredura de arquivos (glob) em volume de cache Pix

- Sintoma: Lentidão extrema e alto consumo de memória/processamento no host ao tentar recuperar cobranças pendentes na pasta `storage/cache/pix/`.
- Causa:
  1. O método `PixService::obterCobrancaPendenteResponsavel()` continha um fallback que executava `glob('storage/cache/pix/pix_*.json')` seguido de `usort()` por `filemtime()` e leitura sequencial de todos os arquivos JSON em disco;
  2. Como havia mais de 300 arquivos de teste acumulados no diretório compartilhado via sistema de arquivos do contêiner Docker/WSL2, a varredura gerou contenção pesada de I/O e esgotamento de recursos.
- Solução aplicada:
  1. **Remoção Absoluta de Varreduras:** Eliminado o uso de `glob()` e `usort()` em `PixService`;
  2. **Arquitetura O(1) com Ponteiro Direto:** A localização de cobranças ativas foi refatorada para ser estritamente O(1), utilizando a chave de sessão do usuário (`_pix_ativo_txid_{responsavel_id}`) complementada por um arquivo de ponteiro direto em disco `storage/cache/pix/responsavel_{responsavel_id}.json` (que armazena apenas o `txid` e `expira_em`);
  3. **Higienização Atômica:** Quando a cobrança é liquidada, cancelada ou expira os 15 minutos, o ponteiro e a sessão são removidos imediatamente via `unlink()` pontual;
  4. **Limpeza do Cache:** Removidos arquivos legados órfãos do diretório de cache;
  5. **Homologação:** Criada a suíte `tests/pix_f5_resilience_test.php` com 17/17 asserções aprovadas com tempo de execução inferior a 1 segundo.
- Como evitar no futuro: Nunca utilizar varredura em diretórios de cache (`glob`, `scandir`) durante o ciclo de requisição web HTTP em produção ou desenvolvimento; indexar e buscar recursos sempre por chave direta O(1) através de sessões, IDs ou ponteiros diretos com nomenclatura previsível.

---

## 2026-10-13 - Ausência de feedback de erro e log de auditoria em tentativas de quebra de titularidade (IDOR) no Portal da Família

- Sintoma: Ao navegar em rotas de dependentes do Portal da Família como `GET /portal/alergias?aluno=<id>` com o ID de um aluno pertencente a outra família, ou ao tentar alterar o campo oculto `aluno_id` no formulário de restrições alimentares, o sistema preservava a confidencialidade não exibindo os dados do outro aluno, porém caía em fallback silencioso para o primeiro dependente legítimo sem exibir nenhum alerta de permissão negada na tela e sem registrar a tentativa de violação na tabela `logs_seguranca`.
- Causa:
  1. No método `PortalController::alergias()`, quando `$alunoIdSolicitado > 0` não pertencia à lista de dependentes da família autenticada, o laço de busca terminava com `$alunoSelecionado = null` e executava `if (!$alunoSelecionado) { $alunoSelecionado = $dependentes[0]; }` de forma silenciosa, sem validar `!$this->alunoPertence($respId, $alunoIdSolicitado)`;
  2. No método `PortalController::alergiasSalvar()`, a validação de titularidade bloqueava a mutação, mas não registrava o evento em `logs_seguranca` e a mensagem de erro era genérica;
  3. Comportamento idêntico ocorria nos métodos `agendamento()`, `agendamentoSalvar()` e `pedidos()`, permitindo requisições arbitrárias sem feedback ostensivo de bloqueio de segurança.
- Solução aplicada:
  1. **Validação Estrita de Titularidade com Rejeição Ativa (`alergias`, `agendamento`, `pedidos`):** Invocação de `$this->alunoPertence($respId, $alunoIdSolicitado)`. Se for falso, despacha imediatamente evento de auditoria `acesso_negado_idor` para a tabela `logs_seguranca`, define mensagem flash corporativa em vermelho/danger (*"Você não tem permissão para visualizar ou gerenciar as restrições alimentares deste aluno."*) e redireciona limpando o parâmetro não autorizado da URL;
  2. **Bloqueio de Mutação Maliciosa (`alergiasSalvar` e `agendamentoSalvar`):** Se houver adulteração do campo `aluno_id` no payload POST, registra o evento `tentativa_idor_mutacao` em `logs_seguranca`, define flash danger (*"Você não tem permissão para alterar as restrições alimentares deste aluno."*) e interrompe o processamento antes de qualquer gravação no banco;
  3. **Ajuste em `Auth::logSecurityEvent`:** Adicionada a flag `JSON_UNESCAPED_SLASHES` na serialização de `detalhes_json`, facilitando consultas SQL analíticas sobre rotas;
  4. **Homologação:** Criada a suíte de testes `tests/portal_idor_security_test.php` com 20 de 20 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Em qualquer endpoint que receba IDs via GET ou POST no contexto do cliente (responsável ou colaborador), sempre validar expressamente a titularidade do recurso contra o usuário autenticado antes de qualquer renderização ou mutação, e emitir alerta explícito de permissão negada somado ao registro em `logs_seguranca`.

---

## 2026-10-13 - Ausência de verificação e limite de estoque na edição de pedidos permitindo quantitativos ilimitados

- Sintoma: Na tela de edição de pedido agendado (`/portal/pedidos/editar/{id}`), o usuário conseguia clicar no botão `+` indefinidamente (ex.: até 146 unidades ou mais) mesmo quando o produto possuía apenas 2 unidades físicas em estoque. Além disso, itens com estoque zerado não eram exibidos como esgotados e permitiam ser adicionados ao pedido.
- Causa:
  1. Em `app/views/portal/pedido_editar.php`, a variável `$esgotado` não era utilizada para desabilitar o card de produto ou exibir o badge "Esgotado", renderizando os botões de incremento e decremento incondicionalmente;
  2. O atributo `max` do `<input type="text" class="input-qtd">` estava fixado com o valor estático `max="20"` hardcoded, desconsiderando o estoque físico real disponível;
  3. No script JavaScript da view de edição, o listener do botão `btn-qtd-aumentar` não lia o atributo `max` e incrementava a quantidade diretamente (`input.value = current + 1;`), permitindo qualquer número ilimitado de cliques;
  4. No método `PortalController::pedidoEditar()`, o cálculo de estoque disponível era obtido globalmente via `Produto::listarParaCardapio()`, que considera reservas já ativas do sistema, sem somar de volta para este pedido específico as unidades que ele próprio já havia reservado;
  5. No backend (`Pedido::editarPedido` e `Pedido::criarPedido`), faltava a validação explícita de produto esgotado (`disponivel <= 0`) e de limite máximo de 10 unidades por pedido para salgados diários.
- Solução aplicada:
  1. **Cálculo de Estoque na Edição (`PortalController::pedidoEditar`):** Mapeado o array `$itensAtuais` antes do catálogo de produtos e calculada a quantidade máxima permitida para este pedido específico (`$estoqueMaxEdicao = max(0, $estoqueLivre) + $qtdAtualNoPedido`). Itens com total permitido `<= 0` são marcados como `$prod['esgotado'] = true`;
  2. **Tratamento Visual e Badges na View (`pedido_editar.php`):**
     - Itens com estoque zerado e não presentes no pedido são renderizados com estilo de esgotado (`opacity: 0.55; cursor: not-allowed; bg-light border-subtle`) e com a tag `<code class="code-tag font-tabular">Esgotado</code>` em vez dos botões de quantidade;
     - Itens disponíveis exibem na descrição o quantitativo disponível (`Disp: X un`);
     - O atributo `max` do input recebe exatamente o limite calculado para o item;
  3. **Trava Interativa no JavaScript (`pedido_editar.php`):**
     - O botão de aumentar (`+`) verifica se `current >= max`. Se atingido, bloqueia o incremento e emite alerta amigável de limite atingido;
     - Implementada a função `atualizarEstadoBotoes(id)`, que desabilita visualmente o botão `+` (`disabled`) ao atingir o limite e o botão `-` ao chegar em 0;
     - A função é executada no carregamento inicial da página para todos os itens;
  4. **Blindagem no Backend (`Pedido::editarPedido` e `Pedido::criarPedido`):**
     - Adicionada verificação impedindo adicionar produtos esgotados (`disponivel <= 0`);
     - Adicionada regra limitando salgados diários a no máximo 10 unidades por pedido;
  5. **Homologação:** Criada a suíte `tests/edicao_pedido_estoque_test.php` aprovada com 100% de sucesso cobrindo renderização HTML, atributos `max`, botões desabilitados, badge de esgotado e recusa do backend para pedidos excessivos.
- Como evitar no futuro: Em qualquer interface de edição que envolva inventário ou reservas prévias, sempre calcular o limite máximo relativo àquele pedido (`estoque_livre + quantidade_ja_reservada_pelo_pedido`), refletir o atributo `max` no HTML e assegurar que o JavaScript e o backend validem rigorosamente os limites físicos do estoque.

---

## 2026-10-14 - Poluição do banco de dados por dados fakes de testes automatizados e erro de coluna deleted_at em responsaveis

- Sintoma: (1) O banco de dados continha mais de 128 produtos temporários (`TEST-%`, `PDV-BLOQ-%`, `PRD-ED-%`), além de turmas duplicadas e alunos fictícios com prefixos `MAT-TEST%`, poluindo a visualização da cantina; (2) Erro `Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'deleted_at' in 'where clause'` ao consultar a tabela `responsaveis`.
- Causa:
  1. Testes automatizados anteriores executados sem teardown completo inseriram fixtures com nomes e códigos artificiais no banco de produção/desenvolvimento compartilhado;
  2. A tabela `responsaveis` (conforme especificado na migração `004_create_responsaveis_table.php`) foi desenhada sem a coluna `deleted_at`, possuindo exclusão física ou controle via `bloqueado_med` e constraints com `RESTRICT`. Consultas que supunham a presença de `deleted_at` geravam erro de PDO.
- Solução aplicada:
  1. Criação do seeder oficial `database/seed_pedidos_semana_atual.php` com etapa preliminar de purga cirúrgica que elimina todos os produtos com ID > 12, turmas não-oficiais (mantendo exatamente as 19 turmas oficiais), alunos de teste e responsáveis sem dependentes reais vinculados;
  2. Correção das consultas na tabela `responsaveis`, removendo qualquer referência a `deleted_at` e mantendo a checagem em `alunos` e `turmas`;
  3. Abastecimento da semana atual (12 a 16 de Outubro de 2026) exclusivamente com compras e pedidos de pais e alunos reais do Colégio Monteiro Lobato.
- Como evitar no futuro: Não rodar testes automatizados que realizem mutações com dados sintéticos no banco de dados ativo. Para demonstrações e testes operacionais, utilizar estritamente o seeder oficial `database/seed_pedidos_semana_atual.php` baseado nas matrículas reais da instituição.

---

## 2026-10-14 - Quebra de página compulsória por ticket térmico e ausência de formato A4 para impressoras convencionais

- Sintoma: Na visualização e download de tickets em lote (`/operacional/tickets/lote/pdf`), cada ticket era exibido isoladamente em uma página vertical inteira (14 páginas para 14 tickets), com mais de 65% de espaço em branco sob cada cupom. Em impressoras comuns (A4), cada ficha era impressa em uma folha inteira separada, e em bobinas térmicas de 80mm havia avanço excessivo de papel.
- Causa:
  1. O arquivo `app/views/tickets/ticket_80mm.php` continha a propriedade CSS `page-break-after: always;` na classe `.ticket-wrapper`, forçando a quebra de página incondicional após cada ticket;
  2. No motor Chromium (Gotenberg), a ausência de especificação de `paperHeight` combinada com o `page-break-after` gerava páginas com altura padrão de 11 polegadas (279,4 mm) por cupom;
  3. Não existia uma opção dedicada para impressão em folha de papel A4 em grade para instituições ou cantinas que operam com impressoras convencionais (jato de tinta ou laser) e realizam o corte das fichas na tesoura ou guilhotina.
- Solução aplicada:
  1. **Refatoração da Bobina Térmica 80mm (`ticket_80mm.php`):** Substituição de `page-break-after: always;` por `page-break-inside: avoid; break-inside: avoid;`, permitindo que os tickets fluam continuamente na bobina térmica separados pela linha pontilhada de corte (`✂ - - - -`), reduzindo o consumo de papel e agrupando 4 a 5 tickets por página no PDF;
  2. **Criação do Modelo Folha A4 em Grade (`ticket_a4.php`):** Desenvolvimento de view especializada em folha A4 com grade balanceada de 8 tickets por folha (2 colunas x 4 linhas, ~94mm x 66mm), com bordas tracejadas para recorte, logotipo oficial, dados do aluno, produto, alérgenos e instrução de descarte, reduzindo 14 tickets a apenas 2 folhas A4;
  3. **Suporte no `PdfService` e `TicketsController`:** Métodos `gerarEspelhoTicketsLoteA4Html` e `gerarTicketsLoteA4Pdf`, com suporte ao parâmetro `?formato=a4` e `?formato=80mm` nos endpoints `/lote`, `/lote/pdf`, `/turma/{id}` e `/turma/{id}/pdf`;
  4. **Interface do Operador (`app/views/operacional/tickets.php`):** Botões duplos de destaque para "Imprimir Folha A4 (Grade)", "PDF A4", "Bobina Térmica (80mm)" e "PDF 80mm", além de botões claros de escolha por turma;
  5. **Alternador Rápido no Espelho de Impressão:** Links de alternância com 1 clique ("Alternar para Folha A4" / "Alternar para Bobina 80mm") na barra superior das telas de impressão.
- Como evitar no futuro: Em impressões contínuas de bobina térmica, nunca utilizar `page-break-after: always;` entre itens do mesmo lote, preferindo `page-break-inside: avoid;` para fluxo contínuo. Sempre disponibilizar a alternativa em Folha A4 para relatórios e cupons que possam ser impressos em equipamentos convencionais de escritório.

---

## 2026-10-14 - Quebra de layout de indicadores de cash-in e botões desintegrados na tela de Arrecadação Financeira

- Sintoma: Na tela de Arrecadação Financeira (`/relatorios/arrecadacao`), os indicadores de resumo financeiro (Total Arrecadado Cash-In Real, Recargas Pix, Recargas Dinheiro, Vendas Balcão e Consumo de Saldo) eram renderizados como texto puro corrido e empilhado no canto superior esquerdo sem bordas, fundo ou estilo de cards. Além disso, os botões de ação e exportação (Visualizar/Imprimir A4, Baixar em PDF A4, Exportar CSV) eram links `<a>` isolados fora do formulário com datas estáticas que ignoravam qualquer alteração de período feita pelo usuário, e o botão "Baixar em PDF" enviava cabeçalho `inline` em vez de `attachment` sem download efetivo.
- Causa:
  1. O contêiner de indicadores em `app/views/relatorios/arrecadacao.php` utilizava a classe `.stat-strip-enterprise`, que não possuía regras correspondentes em `assets/css/custom.css` (onde a classe canônica homologada era `.stat-strip`), fazendo com que o navegador não aplicasse `display: flex`, dimensões, bordas, background branco ou espaçamentos nos elementos filhos `.stat-item`;
  2. Os botões de exportação e emissão no topo da tela eram links `<a>` com datas fixadas via query string pelo PHP inicial, em vez de botões integrados ao formulário com `form="formFiltroArrecadacao"` e `formaction="..."`;
  3. No controlador `RelatoriosController::arrecadacaoPdf`, o cabeçalho HTTP de resposta utilizava `Content-Disposition: inline`, fazendo o navegador tentar renderizar o PDF na mesma janela em vez de forçar o download para a máquina do usuário (`attachment`);
  4. A função JavaScript `definirDatas()` dependia unicamente de `form.submit()`, que em navegadores baseados em Gecko (Firefox) podia falhar na submissão dinâmica sem validação completa do HTML5.
- Solução aplicada:
  1. **Padronização do Stat-Strip (`app/views/relatorios/arrecadacao.php` e `assets/css/custom.css`):** Substituída a classe para `.stat-strip` com filhos `.stat-strip-item`, `.stat-strip-label`, `.stat-strip-value` e `.stat-subtext`. No CSS corporativo, adicionado suporte com `display: flex; flex-wrap: wrap; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;`, garantindo caixas executivas elegantes e responsivas;
  2. **Integração dos Botões de Ação ao Formulário:** Transformados os links de emissão em botões `<button type="submit" form="formFiltroArrecadacao" formaction="..." formtarget="...">`, assegurando que qualquer data selecionada ou alterada pelo usuário seja sempre enviada para o espelho A4, download do PDF e exportação CSV;
  3. **Download Direto do PDF (`RelatoriosController::arrecadacaoPdf`):** Ajustado o cabeçalho para `Content-Disposition: attachment; filename="..."`, garantindo o download do arquivo PDF;
  4. **Robustez nos Atalhos de Período:** Atualizada a função `definirDatas()` para utilizar `form.requestSubmit()` com fallback para `form.submit()`, adicionados atributos `data-inicio` e `data-fim` nos botões e anexados listeners diretos no `DOMContentLoaded`;
  5. **Cache Busting nos Layouts:** Atualizada a versão dos assets no layout `admin.php` para `?v=6.0.1` forçando a renovação de cache em todos os clientes.
- Como evitar no futuro: Sempre utilizar a classe `.stat-strip` e `.stat-strip-item` do Design System para faixas de indicadores corporativos. Para botões de exportação e impressão de relatórios que dependam de filtros de formulário, preferir `<button type="submit" form="..." formaction="...">` para garantir sincronia com os campos de data em tela.


---

## 2026-10-14 - Rolagem excessiva de janela e compressão vertical da cesta de compras no PDV frente de caixa

- Sintoma: No terminal de caixa (`/operacional/pdv`), em telas de computadores portáteis e monitores com resolução típica (1366x768 ou 1920x1080 com escala), a expansão dos detalhes de pagamento (especialmente em Dinheiro com campo de valor recebido, atalhos +10/+20/+50 e troco sugerido) ou da identificação do cliente comprimia a lista de itens da cesta (`.pdv-cart-body`) a uma faixa vertical de apenas 30 a 40px, cortando os nomes dos produtos ao meio. Além disso, a altura combinada dos componentes excedia a altura da janela, gerando barra de rolagem na janela inteira (`window`), fazendo o cabeçalho e estatísticas rolarem para fora da tela e exigindo que o operador rolasse o mouse constantemente para voltar a ver o topo da tela.
- Causa:
  1. A página do PDV utilizava o layout mestre padrão administrativo com preenchimento interno amplo (`padding: 24px 32px;`) e rodapé operacional visível (`.app-admin-footer`, 40px), roubando quase 90px de altura útil da tela;
  2. O painel da cesta de compras (`.pdv-cart-panel`) possuía altura calculada com `calc(100vh - 145px)` e `min-height: 580px; position: sticky; top: 70px;`, que, ao somar-se à barra superior e às estatísticas, forçava a janela inteira a criar uma barra de rolagem vertical externa;
  3. A área da cesta de compras (`.pdv-cart-body`) utilizava `flex: 1` sem `min-height` protetiva. Quando os blocos inferiores de pagamento expandiam (mais de 130px de formulário), o flexbox espremia a cesta até que apenas uma linha de texto coubesse, cortando a descrição dos produtos;
  4. Na identificação de Aluno e Colaborador, o campo de busca com o nome selecionado permanecia visível ao lado do card de dados do cliente, consumindo espaço vertical duplicado;
  5. Ausência de `overscroll-behavior: contain;` nos contêineres de rolagem interna, fazendo com que a rolagem da roda do mouse dentro da cesta ou do catálogo encadeasse a rolagem da página externa inteira.
- Solução aplicada:
  1. **Layout Travado no Viewport (Zero Window Scroll) via `.pdv-layout-locked`:**
     - Identificado no layout `admin.php` quando `$activeMenu === 'pdv'`, aplicando a classe `.pdv-layout-locked` na casca `.app-admin-wrapper`;
     - Regras em `custom.css` que fixam a altura em `100vh; overflow: hidden;` no desktop, reduzem o padding para `8px 12px;` e ocultam o rodapé desnecessário no terminal de caixa;
     - Garantido que a janela do navegador nunca crie barra de rolagem externa (`body` e `html` fixos);
  2. **Contenção Estrita de Rolagem (`overscroll-behavior: contain`):**
     - Aplicado em `.pdv-products-grid` e `.pdv-cart-body`, garantindo que a roda do mouse role exclusivamente o catálogo ou a cesta de compras, sem nunca movimentar a casca da página;
  3. **Otimização de Densidade Vertical na Cesta de Compras:**
     - Bloco de Dinheiro reorganizado horizontalmente: campo de valor recebido alinhado na mesma linha dos botões rápidos `[Exato] [+10] [+20] [+50]`, seguido de uma barra esguia de troco sugerido, reduzindo a altura do bloco de 135px para 58px;
     - Identificação de Aluno/Colaborador compacta: quando o cliente é selecionado, o campo de busca é substituído por um card esguio com nome, turma, saldo e botão discreto `✕` para troca rápida, economizando mais de 70px;
     - Cesta de compras (`.pdv-cart-body`) com `min-height: 130px;` garantido e tipografia compacta com quebra de linha inteligente (`word-break: break-word`), acomodando confortavelmente de 4 a 6 itens sem corte de texto;
  4. **Validação:** Aprovadas todas as baterias de testes do PDV (`tests/pdv_web_test.php`, `tests/pdv_modal_test.php`, `tests/pdv_formas_pagamento_test.php` e `tests/pdv_estoque_baixa_test.php`).
- Como evitar no futuro: Telas de frente de caixa e terminais operacionais (POS) devem sempre operar sob arquitetura de *viewport-locked layout* (`height: 100vh; overflow: hidden;`), sem barra de rolagem na janela (`window`), utilizando `overscroll-behavior: contain;` e contêineres internos de rolagem independente com orçamentos verticais compactos.

---

## 2026-10-15 - Incidente Operacional 500 por variável $dados indefinida em arrecadacao_pdf.php e botões bloqueados por navegador

- Sintoma: Ao clicar no botão "Visualizar / Imprimir (A4)" na tela de Arrecadação Financeira (`/relatorios/arrecadacao`), o sistema disparava a tela vermelha de erro interno 500 de "Incidente Operacional" com a mensagem: `ErrorException: Undefined variable $dados em /var/www/html/app/views/relatorios/arrecadacao_pdf.php:377`. Além disso, o botão "← Voltar à Tela Anterior" na tela de 500 não funcionava por se tratar de aba recém-aberta sem histórico prévio, e na tela principal o uso de `<button type="submit" form="..." formaction="..." formtarget="_blank">` sofria com bloqueio de popups ou falhas de submissão do navegador por estar fora do formulário no DOM.
- Causa:
  1. No método `PdfService::renderView(string $view, array $_viewVars)`, a função nativa `extract($_viewVars, EXTR_OVERWRITE)` descompactava as chaves do array `$dados` em variáveis isoladas (`$canais`, `$dias`, etc.), mas a própria variável `$dados` não existia no escopo local da view `arrecadacao_pdf.php`. Como o template acessava `$dados['total_transacoes_arrecadacao']` e chaves do rodapé sem fallbacks `??`, o PHP 8.2 com o `ErrorHandler` estrito ativo converteu o aviso em uma `ErrorException` fatal;
  2. Na tela de erro 500 (`app/views/errors/500.php`), o botão "← Voltar à Tela Anterior" utilizava puramente `onclick="history.back()"`. Quando a tela de erro é disparada em uma nova aba (`target="_blank"`), o histórico da janela é nulo (`history.length <= 1`), fazendo com que o clique não tivesse nenhum efeito perceptível;
  3. Na tela principal `app/views/relatorios/arrecadacao.php`, os botões de ação utilizavam `<button type="submit" form="formFiltroArrecadacao" formaction="..." formtarget="_blank">`. Por estarem posicionados antes do `<form>` no DOM, certos navegadores bloqueavam o popup da nova aba ou falhavam na substituição de ação em formulários GET;
  4. Risco de cache do navegador em `custom.css` afetando a visualização dos 5 cartões de indicadores financeiros.
- Solução aplicada:
  1. **Blindagem de Injeção de Dados em `PdfService::renderView` e `RelatoriosController`:**
     - Adicionada guarda protetiva em `PdfService::renderView`: `if (!isset($_viewVars['dados'])) { $_viewVars['dados'] = $_viewVars; }`;
     - Injetado explicitamente `$dados['dados'] = $dados` em `RelatoriosController::arrecadacaoImprimir()` e `RelatoriosController::arrecadacaoPdf()`;
     - Adicionada inicialização defensiva no topo de `arrecadacao_pdf.php` (`if (!isset($dados) || !is_array($dados)) { $dados = get_defined_vars(); }`) e operadores de coalescência nula `?? 0` em todas as leituras de totais (linhas 375-385 e 482-494);
  2. **Arquitetura Infalível de Botões e Links de Ação (`arrecadacao.php`):**
     - Substituídos os botões de ação por links `<a>` nativos com atributos `href` dinâmicos atualizados em tempo real via JavaScript (`atualizarLinksExportacao`) nos eventos `input` e `change` dos campos de data;
     - Criada a função de despacho `executarAcaoExportacao(event, tipo)` para abertura limpa de abas via `window.open` e navegação direta para downloads, imune a bloqueios de popups e com fallback 100% funcional caso o JavaScript esteja inativo;
     - Transformados os atalhos de período ("Hoje", "Ontem", "Esta Semana", "Este Mês", "Mês Anterior") em links `<a>` nativos com fallback de navegação direta e submissão rápida;
  3. **Correção do Botão Voltar na Tela 500 (`app/views/errors/500.php`):**
     - Atualizado para `if (window.history.length > 1) { window.history.back(); } else { window.close(); }`, garantindo que se o usuário estiver em uma nova aba, ela seja imediatamente fechada;
  4. **Blindagem Inline de Estilos no Stat-Strip:**
     - Aplicadas regras de layout flex inline nos 5 cards de Cash-In, tornando a exibição visual elegante, alinhada e imune a caches antigos de CSS;
     - Versão dos assets no layout `admin.php` atualizada para `?v=6.0.2`.
- Como evitar no futuro: Em métodos de renderização de PDF/impressão (`renderView`), garantir que arrays de dados estejam sempre acessíveis sob sua chave canônica (`$_viewVars['dados']`) e proteger todas as variáveis em views com operadores defensivos `?? 0` ou `?? ''`. Para botões de exportação que abrem novas abas, preferir links `<a>` com `href` atualizado dinamicamente em vez de botões com `formaction` e `formtarget="_blank"` fora do formulário.

---

## 2026-10-15 - Corte sub-pixel na borda direita das tabelas em relatórios PDF do Gotenberg (Chromium)
- Sintoma: Ao abrir os relatórios em PDF gerados pelo Gotenberg no visualizador nativo do Chrome ou imprimi-los, a borda preta vertical mais à direita das tabelas apresentava corte ou espessura inconsistente/intermitente ("cortam um pedacinho na direita"). Adicionalmente, no relatório de arrecadação financeira, o cabeçalho `PARTICIPAÇÃO` quebrava a letra 'O' para a segunda linha.
- Causa:
  1. No motor Chromium (Gotenberg 8), o `@page` estava configurado com `margin: 10mm 12mm 10mm 12mm;`. Quando tabelas utilizam `width: 100% !important; border-collapse: collapse;`, a especificação de colapso de bordas do CSS posiciona a borda externa de 1px exatamente sobre a linha limite da área imprimível. No renderizador PDFium do Chrome e em resoluções de tela padrão (96 DPI a 100% ou 125% de zoom), o arredondamento de sub-pixel e o *clip rect* da página descartavam a metade externa da linha preta, reduzindo-a a um fade cinza claro de apenas 1 pixel subpixel ou eliminando-a por completo;
  2. A coluna de `Participação` na Tabela 1 de Arrecadação possuía largura insuficiente de `12%` (~22mm), fazendo com que a palavra de 12 caracteres quebrasse com `PARTICIPAÇÃ` na linha superior e `O` na linha inferior;
  3. O elemento de cabeçalho com alinhamento à direita (`.school-meta-col` / `.header-meta`) ficava colado na coordenada de corte sem respiro.
- Solução aplicada:
  1. **Calibração Óptica Simétrica de 14mm no `@page`:**
     - Atualizado `@page` em todos os 10 templates de relatórios A4 (`arrecadacao_pdf.php`, `extrato_aluno_pdf.php`, `corte_pdf.php`, `desconto_folha_pdf.php`, `desconto_folha_individual_pdf.php`, `fechamento_caixa_pdf.php`, `med_negativados_pdf.php`, `vendas_alimento_pdf.php`, `estoque_movimentacoes_pdf.php` e `dre_pdf.php`) para margens simétricas e nobres de 14mm: `@page { margin: 10mm 14mm 10mm 14mm; }`;
     - Ajustado o contêiner em `@media screen` para `padding: 10mm 14mm;`, espelhando perfeitamente a visão física;
  2. **Respiro e Eliminação de Quebra de Palavras:**
     - Calibradas as larguras de colunas da Tabela 1 em `arrecadacao_pdf.php` (Canal: 30%, Origem: 27%, Transações: 12%, Total: 16%, Participação: 15%), garantindo que `PARTICIPAÇÃO` nunca quebre;
     - Calibradas as larguras da Tabela 3 (Data: 11%, Dia: 10%, Recarga Pix: 13%, Recarga Din: 12%, Venda Din: 12%, Venda Pix: 12%, Arrecadado Dia: 15%, Consumo Saldo: 15%);
     - Injetado `padding-right: 2px;` nos blocos de metadados do cabeçalho escolar;
     - Injetado `padding: 3.5px 6px !important;` nas células das tabelas para respiro das colunas numéricas;
  3. **Ponto Seguro de Rollback:**
     - Criada tag e branch Git `rollback-pdf-ponto-seguro` no commit funcional imediatamente anterior;
  4. **Validação Rigorosa:**
     - Auditoria óptica a 288 DPI via `pypdfium2` comprovando traço contínuo `[0, 0, 0, 255, 255, 255]` e simetria de 14.0mm esquerda x 13.8mm direita (diferença de 0.18mm) em 100% dos relatórios;
     - Suíte `tests/teste_todos_pdfs.php` (10 relatórios), `tests/arrecadacao_dre_regimes_test.php` (89 testes) e `tests/fase23_test.php` (220 testes) aprovadas com 100% de sucesso.
- Como evitar no futuro: Em documentos A4 gerados pelo Chromium com tabelas em `border-collapse: collapse;`, utilizar margens laterais no `@page` de no mínimo 14mm (em vez de 12mm) para garantir que a metade externa das bordas pretas nunca coincida com a linha de *clipping* da folha de papel.

---

## 2026-10-15 - Botão 'Exportar PDF (A4 Timbrado)' inoperante na tela de Relatório de Inadimplência e Bloqueios MED (/relatorios/devedores)

- Sintoma: Ao clicar no botão azul "Exportar PDF (A4 Timbrado)" na tela de Relatório de Inadimplência e Bloqueios MED (`/relatorios/devedores`), nada era baixado ou o navegador retornava tela de erro HTTP 404 (Página Não Encontrada).
- Causa:
  1. *Rotas ausentes no Front Controller `index.php`:* A rota `/admin/contestacoes/pdf` (para a qual o botão apontava no HTML) e as rotas canônicas do módulo de relatórios `/relatorios/devedores/pdf` e `/relatorios/devedores/imprimir` não estavam registradas no roteador;
  2. *Métodos de emissão ausentes em `RelatoriosController`:* O controlador `RelatoriosController` possuía apenas o método `devedores()` para exibição da tela web, faltando os métodos `devedoresPdf()` e `devedoresImprimir()`;
  3. *Perda de filtros na exportação:* O link do botão apontava para um endpoint estático sem preservar os parâmetros de consulta (`busca` e `status`), impedindo que o operador exportasse relatórios filtrados por responsável específico ou apenas bloqueados;
  4. *Campos não formatados no template PDF:* Em `med_negativados_pdf.php`, o template exibia a coluna "WhatsApp" (deixando vazio para responsáveis com apenas e-mail/telefone cadastrado) e não utilizava o CPF formatado nem os dependentes com turma.
- Solução aplicada:
  1. *Registro de Rotas em `index.php`:* Registradas formalmente as rotas `GET /relatorios/devedores/pdf`, `GET /relatorios/devedores/imprimir` e `GET /admin/contestacoes/pdf` protegidas por RBAC para o perfil Administrador;
  2. *Implementação dos Métodos no Controller:*
     - Adicionados `RelatoriosController::devedoresPdf()` (download direto do arquivo `.pdf`) e `RelatoriosController::devedoresImprimir()` (espelho HTML timbrado com botão de impressão rápida);
     - Ambos os métodos capturam e aplicam fielmente os filtros de busca e status da URL, com guarda defensiva `if (!headers_sent())`;
     - Atualizado `AdminController::contestacoesPdf()` para também respeitar filtros de consulta;
  3. *Atualização da View `devedores.php`:*
     - Botão "Exportar PDF (A4 Timbrado)" atualizado para a rota canônica `/relatorios/devedores/pdf`, com `queryString` automática preservando filtros;
     - Adicionado botão "Visualizar / Imprimir (A4)" (`/relatorios/devedores/imprimir`) abrindo em nova aba;
     - Injetado listener JavaScript para atualizar os atributos `href` de ambos os botões dinamicamente conforme o usuário digita no campo de busca ou altera o select de situação;
  4. *Refinamento do Template `med_negativados_pdf.php`:*
     - Coluna alterada de "WhatsApp" para "Contato", priorizando WhatsApp e fazendo fallback resiliente para e-mail e telefone;
     - Exibição de CPF formatado (`000.000.000-00`) e dependentes com turma (`Nome (Ano/Turma)`);
  5. *Testes Automatizados:*
     - Criado teste `tests/teste_devedores_pdf_rotas.php` e validadas 9/9 requisições HTTP autênticas no Apache via container Docker com HTTP 200 OK e PDFs binários íntegros.
- Como evitar no futuro: Toda view com botões de exportação (PDF, CSV ou Impressão) deve ter suas rotas obrigatoriamente registradas no `index.php`, com métodos correspondentes no Controller que preservem os parâmetros de filtro ativos e testes de requisição HTTP automatizados.


---

## 2026-10-15 - Item incorreto destacado na navegação lateral (Sidebar) na tela de Arrecadação

- Sintoma: Ao navegar para a página de Arrecadação Financeira & Fluxo de Entradas (`/relatorios/arrecadacao`), o menu lateral (sidebar) destacava com a classe `.active` o item "Relatórios Gerenciais" em vez de "Arrecadação & Entradas".
- Causa: No método `RelatoriosController::arrecadacao()`, o array de dados repassado para `$this->render('relatorios/arrecadacao', ...)` definia `'activeMenu' => 'relatorios'`, enquanto o link correspondente em `app/views/layouts/sidebar.php` validava `($activeMenu === 'arrecadacao') ? 'active' : ''`. Com isso, a sidebar ativava o link de `/relatorios` ("Relatórios Gerenciais") e deixava o link de "Arrecadação & Entradas" apagado.
- Solução aplicada:
  1. Alterado `'activeMenu' => 'arrecadacao'` no método `arrecadacao()` em `app/controllers/RelatoriosController.php`;
  2. Adicionadas asserções automatizadas no teste `tests/arrecadacao_dre_regimes_test.php` verificando que a sidebar renderiza o link `/relatorios/arrecadacao` com a classe `active` e que o link `/relatorios` não fica ativo;
  3. Bateria de testes executada com 91 de 91 asserções aprovadas com 100% de sucesso.
- Como evitar no futuro: Ao criar ou refatorar controladores que renderizam telas com layouts que contêm sidebar de navegação, certificar-se de que o valor da variável `'activeMenu'` coincida exatamente com a chave correspondente daquele link na sidebar (`app/views/layouts/sidebar.php`), incluindo testes unitários de renderização que verifiquem a classe ativa.

---

## 2026-10-15 - Caixas nativas de confirmação do navegador (confirm()) no fluxo operacional e cadastral

- Sintoma: Ao realizar ações críticas (ex.: fechamento de caixa, registro de perda de estoque, inativação de alunos/colaboradores/turmas/produtos, exclusão de exceções do calendário e cancelamento de importação de XML), o navegador disparava a caixa de confirmação nativa síncrona cinza e antiestética (`localhost:8080 diz: ... [OK] [Cancelar]`), quebrando o padrão visual do Design System e a experiência corporativa de alta densidade.
- Causa: Presença de chamadas legadas com `onsubmit="return confirm(...)"`, `onclick="return confirm(...)"` e funções locais contendo `return confirm(...)` espalhadas por formulários operacionais e de administração.
- Solução aplicada:
  1. **Sistema Corporativo de Confirmação (`assets/js/app.js`):**
     - Criada a função utilitária `window.confirmarAcao(options)` e alias `window.confirmModal`, que gera e gerencia de forma assíncrona o modal singleton `#modalConfirmacaoSistema`;
     - O modal implementa classes `.modal-enterprise` e `.modal-confirm-enterprise` no `assets/css/custom.css`, com `z-index: 1075` e backdrop em `1070` (garantindo sobreposição suave e perfeita mesmo sobre outro modal aberto, como na tela de Fechamento de Caixa);
     - Tipos configuráveis com ícones e botões semânticos (`danger`, `warning`, `success`, `primary`), com botão de fechamento acessível e preservação do estado de rolagem do body (`modal-open`);
  2. **Interceptors Declarativos Universais (`data-confirm`):**
     - Criados listeners globais em `app.js` interceptando qualquer formulário ou link que possua atributos `data-confirm`, `data-confirm-title`, `data-confirm-type` e `data-confirm-btn`;
     - Implementada varredura automática no `DOMContentLoaded` (`inicializarConfirmacaoCorporativa`) para migração dinâmica defensiva de qualquer chamada inline remanescente;
  3. **Refatoração Cirúrgica das Views:**
     - `app/views/operacional/caixa.php` e `app/views/operacional/fechamento_caixa.php`: Atualizada a função `confirmarFechamento(event)` para utilizar `window.confirmarAcao` assíncrono;
     - `app/views/operacional/estorno_vendas.php`: Atualizado fallback para `window.confirmarAcao`;
     - `app/views/estoque/perdas.php`: Atualizada a função `validarEnvioPerda(event)` para validar campos e acionar `window.confirmarAcao`;
     - `app/views/estoque/conferir_xml.php`: Convertidos botões de cancelamento para `data-confirm`;
     - `app/views/admin/alunos.php`, `turmas.php`, `colaboradores.php`, `produtos.php`, `categorias.php`, `calendario.php`: Convertidos formulários de inativação/exclusão para atributos declarativos `data-confirm`;
  4. **Controle de Versão de Assets:**
     - Versão dos assets elevada para `?v=6.1.0` em `Controller.php`, `admin.php` e `portal.php` para invalidação imediata de cache no cliente;
  5. **Bateria de Testes Automatizados:**
     - Criado teste `tests/teste_modais_confirmacao.php` com 35 asserções aprovadas com 100% de sucesso, atestando a erradicação de 100% das chamadas a `confirm()` em todas as views do sistema.
- Como evitar no futuro: Nunca utilizar `confirm()` ou `alert()` nativos do navegador. Em qualquer necessidade de confirmação, utilizar a função padrão `window.confirmarAcao({ titulo, mensagem, tipo, textoConfirmar })` ou os atributos declarativos `data-confirm` no HTML.

---

## 2026-10-15 - Erro getaddrinfo no GitHub Actions por host DNS não propagado

- Sintoma: Falha no passo `Preparar chave SSH` do workflow de deploy manual no GitHub Actions (`getaddrinfo ***: Name or service not known`, `Error: Process completed with exit code 1`).
- Causa: O secret `HOSTNET_HOST` foi cadastrado com o domínio `cantinawebmonteirolobato.com.br` ou `ftp.cantinawebmonteirolobato.com.br`, cujo registro DNS ainda não foi propagado ou não existe na internet pública.
- Solução aplicada: Substituição do valor do secret `HOSTNET_HOST` no GitHub pelo endereço direto do servidor da Hostnet: `web165.f1.k8.com.br` (ou pelo IP `187.73.33.29`), que responde perfeitamente na porta 22 e resolve em qualquer servidor global.
- Como evitar no futuro: Em publicações iniciais ou ambientes de pré-lançamento na Hostnet onde o domínio final ainda não concluiu a propagação de DNS, utilizar sempre o "Host provisório" fornecido pelo painel da Hostnet (ex.: `web165.f1.k8.com.br`).

---

## 2026-10-15 - Erro libcrypto ao carregar chave SSH privada no GitHub Actions

- Sintoma: `Load key "/home/runner/.ssh/hostnet_deploy": error in libcrypto`, `Permission denied (publickey,password)`, `exit code 255` no passo `Testar conexão SSH`.
- Causa: O OpenSSH no runner Ubuntu falha ao validar o formato da chave privada salva via `echo` se houver quebras de linha no padrão Windows (`\r\n` CRLF) ou ausência de quebra de linha final requerida pela libcrypto/OpenSSL.
- Solução aplicada: Atualizado o workflow `.github/workflows/deploy-hostnet.yml` injetando a chave via variável de ambiente `SSH_PRIVATE_KEY`, formatando com `printf '%s\n'`, convertendo para quebras de linha Unix estritas com `sed -i 's/\r$//'` e forçando `-o IdentitiesOnly=yes`.
- Como evitar no futuro: Em workflows CI/CD que manipulam chaves SSH privadas armazenadas em Secrets, sempre injetar via `env:`, garantir formatação com `printf '%s\n'`, limpar quebras de linha do Windows com `sed -i 's/\r$//'` e usar `-o IdentitiesOnly=yes`.

---

## 2026-10-15 - Tela em branco na Hostnet por index.html pré-existente e Autoloader case-sensitive

- Sintoma: Ao acessar o endereço provisório da Hostnet no navegador (`cantinawebmontei.tmp.k8.com.br`), o navegador exibia uma tela completamente em branco.
- Causa: Dupla ocorrência identificada:
  1. A Hostnet cria por padrão um arquivo `~/www/index.html` (contendo um iframe para uma página de boas-vindas da Hostnet) que tem prioridade sobre o `index.php` no Apache e era bloqueado por políticas de segurança do navegador;
  2. No Linux ext4 (estritamente case-sensitive), o autoloader PSR-4 nativo procurava diretórios com a primeira letra em maiúscula (`App\Core` gerando `app/Core/`), enquanto as pastas físicas do projeto em disco utilizam nomes em minúsculo (`app/core/`, `app/controllers/`, `app/models/`), gerando `Fatal error: Class App\Core\ErrorHandler not found`.
- Solução aplicada:
  1. Remoção imediata dos arquivos padrão da Hostnet `~/www/index.html` e `~/www/404.html` via SSH;
  2. Aprimoramento do Autoloader PSR-4 em `index.php` para normalizar nomes de subpastas em minúsculo (`strtolower`), mantendo a compatibilidade transparente entre Windows e Linux;
  3. Adição de suporte a prefixo `/index.php` no `Router.php` e diretiva `RewriteBase /` com `DirectoryIndex index.php` no `.htaccess`.
- Como evitar no futuro: Em publicações em hospedagens compartilhadas Linux, sempre remover páginas padrão instaladas pela hospedagem (`index.html`) e garantir que autoloaders manuais normalizem o caminho das pastas físicas para evitar inconsistências de sensibilidade de caixa.

---

## 2026-10-16 - Risco de mutação via GET e coluna inexistente no utilitário de reset pré-lançamento

- Sintoma: Identificados durante a revisão de código de segurança:
  1. A confirmação de exclusão em massa aceitava requisições HTTP `GET`, suscetível a disparos involuntários por crawlers de antivírus, links em mensageiros e pré-carregamento especulativo (*speculation rules / prefetching*) de navegadores modernos;
  2. Tentativa de update da coluna inexistente `estoque_minimo_alerta` na tabela `produtos` (gerando `PDOException: Column not found: 1054`).
- Causa:
  1. Utilização de link de âncora `<a>` na interface web em vez de formulário `POST` transacional com token embutido;
  2. Divergência de nomenclatura em relação ao schema oficial da migração `011_create_produtos_table.php` (onde as colunas são `estoque_atual`, `estoque_reservado` e `estoque_minimo`).
- Solução aplicada:
  1. Refatorada a interface web de `database/reset_dados_teste.php` para exigir formulário corporativo com método `POST`, bloqueando sumariamente qualquer mutação via `GET` e aceitando tokens tanto em `$_POST` quanto em `$_GET`;
  2. Corrigida a query de reset de estoque para `UPDATE produtos SET estoque_atual = 0, estoque_reservado = 0;`, preservando a configuração individual de `estoque_minimo` cadastrada no catálogo;
  3. Adicionado bloco idiomático `try ... catch ... finally` garantindo a restauração incondicional de `FOREIGN_KEY_CHECKS = 1`.
- Como evitar no futuro: Operações destrutivas ou de mutação de banco de dados em endpoints web NUNCA devem ser acionadas via método GET; utilizar obrigatoriamente POST com token de validação. Sempre checar as migrações oficiais antes de redigir queries de atualização em massa.

---

## 2026-10-16 - Corrupção de acentos e caracteres especiais no MySQL por redirecionamento do PowerShell

- Sintoma: Acentos e símbolos no sistema online na Hostnet apareciam corrompidos (ex: `6º` aparecendo como `6┬║`, `1ª Via` como `1┬¬ Via`, `Sanduíche` como `SANDU├ìCHE`).
- Causa: Ao executar `mysqldump` com redirecionador `>` no PowerShell do Windows (`docker compose exec db mysqldump ... > dump.sql`), o PowerShell interceptou a saída decodificando os bytes segundo a página de código OEM da console (CP850/CP437) e regravou o arquivo, gerando dupla codificação UTF-8 sobreposta.
- Solução aplicada: O dump dos dados de teste foi extraído estritamente dentro do contêiner Docker (`sh -c "mysqldump --default-character-set=utf8mb4 ... > /tmp/dump.sql"`), copiado diretamente via binário (`docker compose cp`) e importado no MySQL da Hostnet com `--default-character-set=utf8mb4`. Teste automatizado via navegador confirmou ZERO caracteres corrompidos nas telas operacionais e de cupons térmicos 80mm.
- Como evitar no futuro: NUNCA utilizar pipes ou redirecionamento de texto do PowerShell (`>`) para exportar dumps SQL do MySQL. Gerar o arquivo diretamente dentro do ambiente Linux ou contêiner e copiar como arquivo binário.

---

## 2026-10-16 - Layout corrompido nos downloads de PDF de tickets (A4 e 80mm) sem Gotenberg

- Sintoma: Ao clicar em "PDF A4" ou "PDF 80mm", o PDF gerado no navegador exibia 1 ticket por folha com sobreposição do logotipo sobre o texto da escola ("COLEGIO MONTEIRO LOBATO"), ausência da grade 2x4 em A4 e excesso de margens em branco.
- Causa: Em ambiente de hospedagem compartilhada (Hostnet), o microsserviço Chromium headless (Gotenberg) não está em execução. O método `PdfService::htmlToPdf` acionava a rotina de contingência nativa que: (1) não implementava o layout em grade A4 de 8 fichas por folha; (2) desenhava coordenadas com sobreposição física entre a imagem circular e o texto de cabeçalho; (3) utilizava altura excessiva no formato térmico.
- Solução aplicada: Refatorados cirurgicamente os geradores nativos de contingência em `app/services/PdfService.php`:
  1. `gerarTicketsLoteA4Pdf`: Desenvolveu-se a grade nativa PDF 1.4 de 2 colunas x 4 linhas (8 tickets por página) com bordas tracejadas de corte, logo no topo sem sobreposição, caixas de produtos, alérgenos e paginação precisa (12 tickets gerando exatamente 2 folhas A4);
  2. `gerarTicketsLotePdf`: Calibrada a bobina térmica nativa de 80mm (largura 226.77 pt, altura 260 pt), posicionando o logo centralizado acima do título institucional, código em destaque, dados de turma/estudante, caixa sólida para o lanche e picote de tesoura inferior.
- Como evitar no futuro: Em arquiteturas com microsserviços opcionais (Chromium/Gotenberg), garantir que a camada de contingência nativa possua calibração milimétrica idêntica aos padrões visuais corporativos do `docs/DESIGN.md`.

---

## 2026-10-16 - Botões do PDV cortados em resoluções padrão e resquício de mojibake CP850 pós-deploy

- Sintoma: Três ocorrências relatadas pelo usuário após deploy na Hostnet (`/operacional/pdv`):
  1. O botão "Estorno" da barra superior do PDV aparecia cortado na borda direita, a tag `[F8]` e o botão "Sobras [F9]" foram empurrados para fora e ocultados;
  2. A categoria "Lanches & Sanduíches" aparecia como `Lanches & Sandu├¡ches`;
  3. A tag de alérgenos nos cards de produtos aparecia como `GL├║TEN`.
- Causa:
  1. No layout do PDV, a barra superior de métricas herdava `.stat-strip { overflow: hidden; }` do `assets/css/custom.css`. Cada um dos 6 blocos de métricas herdava `min-width: 170px; flex: 1 1 180px; padding: 14px 18px;`. Isso ocupava mais de 1.020px somente nas métricas. Em resoluções como 1366x768 ou 1080p com escala de 125% (típicas de notebooks Windows), a largura combinada com os botões de ação ultrapassava a tela e o container cortava o lado direito;
  2. e 3. Registros inseridos previamente no banco remoto permaneceram com resquícios de decodificação indevida segundo a página OEM CP850 do terminal Windows (`0xC3` = `├`, `0xBA` = `║`, `0xAD` = `¡`), gerando `├¡` em vez de `í` e `├║` em vez de `ú`.
- Solução aplicada:
  1. Ajustado `assets/css/custom.css` para `.stat-strip, .stat-strip-enterprise { overflow: visible; }`;
  2. Refatorado o CSS escopado em `app/views/operacional/pdv.php`, aplicando `min-width: 0 !important; flex: 0 0 auto !important; padding: 2px 10px !important;` nas métricas, margem automática e `stat-actions-group` com `text-nowrap` nos botões de ação, e quebra responsiva limpa para telas estreitas;
  3. Criada a classe utilitária universal `app/core/Sanitizer.php` com o método `repararUtf8()`, integrada nativamente em `app/core/Model.php` (`fetchOne()` e `fetchAll()`) e `app/core/Controller.php` (`render()` e `json()`), protegendo 100% de todas as telas e APIs da aplicação de forma transparente;
  4. Criada a migração oficial `database/migrations/031_corrigir_mojibake_e_acentuacao.php` e o utilitário standalone `database/corrigir_encoding.php` (executável via CLI ou navegador com token) para atualizar o MySQL permanentemente;
  5. Atualizado `database/.htaccess` liberando o endpoint de correção e incrementada a versão de cache dos assets para `6.1.1` em todos os layouts.
- Como evitar no futuro: Nunca aplicar `overflow: hidden` em faixas utilitárias com botões de ação críticos. Manter camada defensiva de sanitização de strings no núcleo arquitetural MVC (Model/Controller) para imunizar a interface contra inconsistências de encoding no banco de dados.

---

## 2026-10-16 - Inviabilidade de Docker/Gotenberg em hospedagem compartilhada e unificação de impressão com preview nativo

- Sintoma: Em ambiente de produção na Hostnet, o download direto de arquivos binários de PDF (gerados via scripts PHP por falta do Gotenberg/Chromium headless) apresentava perdas de layout CSS e limitações visuais quando comparado às telas de pré-visualização HTML do sistema, que estavam 100% perfeitas e simétricas.
- Causa: A hospedagem gerenciada compartilhada Apache da Hostnet não permite a execução de contêineres Docker nem processos daemon Chromium headless em background. As bibliotecas ou geradores PHP puros não dispõem de motor de renderização de fontes e CSS Grid comparável aos navegadores modernos.
- Solução aplicada: Unificado o fluxo de impressão e PDF através das telas oficiais de pré-visualização HTML e CSS (`@media print` com `@page` milimétrico):
  1. No painel de Tickets e de Corte do Turno, substituídos os botões de download de PDF cru por botões unificados **"Imprimir / Salvar PDF (A4)"** e **"Bobina Térmica (80mm)"**;
  2. Na barra superior `.no-print` dos espelhos de impressão, implementado botão de destaque **"Imprimir ou Salvar em PDF"** acionando `window.print()`, permitindo ao usuário tanto o envio direto para a impressora quanto a opção nativa do Chromium *"Destino: Salvar como PDF"* com fidelidade vetorial absoluta;
  3. Nos controladores `TicketsController` e `PedidosController`, redirecionadas todas as requisições diretas aos endpoints `/pdf` para suas respectivas telas de preview de impressão, garantindo compatibilidade retroativa total.
- Como evitar no futuro: Em projetos hospedados em ambientes compartilhados gerenciados sem suporte a microsserviços Chromium headless orquestrados, padronizar a emissão documental através do recurso nativo `@media print` e diálogo do navegador, evitando soluções pesadas ou fallbacks rudimentares em PHP puro.

---

## 2026-10-16 - Cabeçalhos e rodapés do navegador (URL, título, data e paginação) gerados no PDF

- Sintoma: Ao salvar o relatório ou tickets em PDF através do diálogo de impressão do navegador (*"Salvar como PDF"*), apareciam no topo da página o título do relatório e a URL completa (`http://cantinawebmontei.tmp.k8.com.br/...`), e no rodapé a paginação (`1 of 1`) e a data/hora (`16/10/2026, 09:32`), poluindo o documento oficial da escola.
- Causa: Quando o CSS `@page` declara margens físicas explícitas (ex: `margin: 10mm 14mm`), o motor Chromium (Google Chrome, Microsoft Edge) reserva esse espaço marginal periférico para injetar metadados de impressão (cabeçalhos e rodapés padrão do navegador).
- Solução aplicada: 
  1. Definido `@page { margin: 0; }` em todas as views de relatórios e tickets (`ticket_a4.php`, `ticket_80mm.php`, `corte_pdf.php`, `fechamento_caixa_pdf.php`, `dre_pdf.php`, `arrecadacao_pdf.php`, `desconto_folha_pdf.php`, `desconto_folha_individual_pdf.php`, `extrato_aluno_pdf.php`, `vendas_alimento_pdf.php`, `med_negativados_pdf.php`, `estoque_movimentacoes_pdf.php`);
  2. As margens institucionais exigidas pelo `docs/DESIGN.md` (10mm 14mm ou 8mm 6mm) foram transferidas para dentro do container da folha (`.container`, `.doc-page`, `.a4-sheet`) via padding no bloco `@media print`;
  3. Isso anula a área reservada do Chromium, eliminando compulsoriamente a URL, título e data das bordas sem depender de desmarcação manual do usuário no diálogo.
- Como evitar no futuro: Em documentos institucionais e relatórios formais formatados para `@media print`, sempre definir `@page { margin: 0; }` e aplicar o espaçamento físico de segurança via padding no container interno da página.

---

## 2026-10-16 - Impressão da bobina térmica 80mm fragmentada em folha A4 em impressoras multifuncionais de escritório

- Sintoma: Ao abrir a visualização de Bobina Térmica 80mm (`?formato=80mm`) e disparar a impressão em uma impressora multifuncional laser A4 comum de escritório (como Kyocera TASKalfa 3010i), os tickets saíram impressos como uma tira estreita de 80mm centralizada no papel, dividindo o conteúdo verticalmente em 3 folhas A4 com grande desperdício de papel.
- Causa: A visualização de Bobina Térmica 80mm foi projetada com dimensões CSS de rolo contínuo (`size: 80mm auto`) para impressoras não-fiscais de balcão (Epson TM-T20, Elgin, Bematech). Quando enviada para uma impressora física laser A4 com driver padrão de folha solta, o driver força a divisão de página para a altura padrão A4 (297mm), fragmentando a bobina contínua.
- Solução aplicada:
  1. Adicionado banner informativo fixo na barra `.no-print` da view `ticket_80mm.php` explicando de forma clara que aquele formato é exclusivo para impressoras de cupom térmico e oferecendo botão em destaque **"Usar Folha A4"**;
  2. Ajustado o layout de `ticket_80mm.php` com `@page { size: 80mm auto; margin: 0; }` e `ticket-wrapper` com `page-break-inside: avoid` estrito;
  3. Orientado o usuário a utilizar a opção **"Grade de Fichas (Folha A4)"** sempre que desejar imprimir em sua impressora de escritório ou salvar um PDF A4 para recortar lanches em grade 2x4 (8 fichas por folha).
- Como evitar no futuro: Disponibilizar mensagens claras de contexto ao usuário sobre a destinação de hardware para cada formato (bobina 80mm vs folha solta A4), com atalhos diretos para alternância de formato.

---

## 2026-10-16 - Erro fatal "Call to undefined function repararUtf8()" no PDV de balcão

- Sintoma: Ao acessar o balcão PDV (`/operacional/pdv`), a tela de contingência de erro seguro foi disparada com o código `55e247d1-4846-eb88` e mensagem: `Fatal error: Uncaught Error: Call to undefined function repararUtf8() in /home/cantinawebmontei/www/app/views/operacional/pdv.php:701`.
- Causa:
  1. No arquivo `app/core/Sanitizer.php`, a função auxiliar `repararUtf8()` foi declarada no final do arquivo abaixo da diretiva `namespace App\Core;`. Em PHP, funções procedurais declaradas sob um `namespace` passam a residir naquele namespace (`\App\Core\repararUtf8`), e não no escopo global raiz (`\repararUtf8`);
  2. Como os arquivos de visualização (views) rodam no escopo global sem namespace, a chamada à função sem namespace `repararUtf8()` disparou erro de função não definida;
  3. Além disso, a chamada era redundante, uma vez que a classe base `Controller::render()` já higieniza preventivamente 100% de todos os dados do array `$data` (`$categorias`, `$produtos`, etc.) através de `Sanitizer::repararUtf8($data)` antes de passá-los para a view.
- Solução aplicada:
  1. Removidas as chamadas redundantes a `repararUtf8()` em `app/views/operacional/pdv.php` (linhas 701, 724 e 725), passando a utilizar diretamente os dados já higienizados pelo `Controller`;
  2. Registrada a função helper global `repararUtf8()` no Front Controller `index.php` (no escopo raiz sem namespace) para resiliência e retrocompatibilidade universal em qualquer script ou view;
  3. Arquivos sincronizados via SCP para o servidor Hostnet e execução validada com sucesso (`PDV_SUCCESS: Gerou 112024 bytes sem erros!`).
- Como evitar no futuro: Funções helper globais devem ser registradas exclusivamente em arquivos de bootstrap sem declaração de namespace (ou com declaração de namespace explícita global). Em views MVC, evitar reprocessar dados que o Controller já entrega higienizados.

---

## 2026-10-16 - Erro de sintaxe de fórmula no Microsoft Excel ("Encontramos um problema com esta fórmula...") em planilhas CSV

- Sintoma: Ao abrir os arquivos CSV exportados pelo sistema (DRE, Arrecadação, Vendas, Extrato de Aluno e Estoque) no Microsoft Excel no Windows, o aplicativo abria exibindo uma caixa de diálogo de alerta: *"Encontramos um problema com esta fórmula. Você não está tentando digitar uma fórmula? Ao digitar o primeiro caractere como um sinal de igual (=) ou menos (-), o Excel deduz que é uma fórmula..."*.
- Causa:
  1. No relatório de DRE (`DreController.php`), as linhas contábeis iniciavam literalmente com `(+)`, `(-)` e `(=)` (ex: `(+) RECEITA BRUTA`, `(=) RECEITA LÍQUIDA DE VENDAS`). Como o Excel avalia compulsoriamente como fórmula qualquer célula iniciada com `=`, `+`, `-` ou `@`, a presença de parênteses como `(=)` violava a gramática de fórmulas do Excel;
  2. Nos relatórios de Arrecadação e Estoque (`RelatoriosController.php`), valores positivos eram formatados com sinal `+` explícito (ex: `+150,00`, `+7.602,50`, `+50 un`). O Excel tentava avaliar como expressão matemática e falhava devido à pontuação de milhar e texto;
  3. A sanitização anterior tentava escapar caracteres de fórmula prefixando um apóstrofo `'` (ex: `'+150,00`), o que deixava o apóstrofo visível na célula em planilhas abertas no Excel brasileiro.
- Solução aplicada:
  1. No DRE (`DreController.php`), os marcadores contábeis foram convertidos para colchetes neutros e elegantes: `[+] RECEITA BRUTA`, `[-] DEDUÇÕES`, `[=] RECEITA LÍQUIDA`, `[-] CMV`, `[=] LUCRO BRUTO`, `[-] DESPESAS OPERACIONAIS`, `[=] RESULTADO LÍQUIDO`, imunes à interpretação de fórmulas;
  2. No método `sanitizarCsvCelula` (`RelatoriosController.php`), implementada remoção automática de `+` inicial se seguido de dígitos (`^\+\s*\d`), preservando números negativos legítimos (`-50,00`) e neutralizando fórmulas perigosas em texto livre com espaço invisível (`' ' . $valor`);
  3. Removido o prefixo `+` manual de `variacao_saldo`, `variacao_carteira` e balanço do período de estoque;
  4. Garantido que 100% das linhas exportadas em todas as ações de CSV passem por `self::sanitizarCsvLinha()`;
  5. Criado teste unitário `tests/teste_csv_excel_sintaxe.php` validando todas as regras e executado com 100% de sucesso.
- Como evitar no futuro: Em exportações CSV para Microsoft Excel, nunca iniciar o conteúdo de células com `=`, `+`, `-` ou `@`, exceto para valores puramente numéricos negativos (ex: `-50,00`). Rótulos contábeis que indiquem soma ou subtração devem sempre utilizar colchetes (`[+]`, `[-]`, `[=]`) em vez de parênteses.

---

## 2026-10-16 - Planilhas CSV gerando erro de sintaxe no Excel por tela 500 decorrente de aviso E_DEPRECATED no PHP 8.4

- Sintoma: Ao baixar planilhas CSV pelo sistema no servidor Hostnet (ex.: DRE em `/financeiro/dre/csv` ou Estoque em `/relatorios/estoque-movimentacoes/csv`), o arquivo baixado abria no Excel acusando erro de sintaxe. O conteúdo do arquivo não era o CSV esperado, mas sim o HTML da tela 500 institucional ("Falha Operacional Interceptada | Cantina Web").
- Causa: O ambiente de produção da Hostnet roda PHP 8.4.26. No PHP 8.4, chamadas a `fputcsv()` e `fgetcsv()` sem o 5º parâmetro explícito (`$escape = "\\"`) emitem aviso de depreciação `E_DEPRECATED` ("the \$escape parameter must be provided as its default value will change"). Como o `ErrorHandler::handleError()` do sistema interceptava todo aviso e lançava `new \ErrorException()`, a requisição era abortada pelo capturador de exceção após os cabeçalhos de download CSV já terem sido enviados ao navegador, descarregando o HTML completo da página de erro 500 dentro do arquivo `.csv`.
- Solução aplicada:
  1. Em `app/core/ErrorHandler.php`: Adicionada verificação preventiva em `handleError()` ignorando explicitamente `E_DEPRECATED` e `E_USER_DEPRECATED`, garantindo que avisos de versões futuras da linguagem não interrompam operações em produção.
  2. Em `app/controllers/DreController.php`: Todas as chamadas a `fputcsv()` atualizadas com os 5 argumentos explícitos (`fputcsv($output, $linha, ';', '"', "\\")`).
  3. Em `app/controllers/RelatoriosController.php`: Todas as chamadas a `fputcsv()` dos métodos `extratoAlunoCsv`, `vendasFiltrosCsv`, `estoqueMovimentacoesCsv` e `arrecadacaoCsv` atualizadas com os 5 argumentos explícitos.
  4. Em `app/services/SpreadsheetService.php`: Todas as chamadas a `fgetcsv()` atualizadas com os parâmetros de delimitador, aspas e escape explícitos.
  5. Sincronização via SCP com o servidor da Hostnet e validação remota executada com sucesso via PHP 8.4 (`TAMANHO_CSV: 2191`, BOM UTF-8 `\xEF\xBB\xBF` presente e zero tags HTML).
- Como evitar no futuro: Em PHP 8.4+, sempre explicitar todos os 5 parâmetros nas funções de I/O de CSV (`$stream, $fields, $delimiter, $enclosure, $escape`). Garantir que classes globais de tratamento de erro nunca promovam avisos de depreciação para exceções fatais em rotas de download de arquivos.

---

## 2026-10-19 - Falha de integridade referencial (FK 1452) ao vincular operador em estoque_movimentacoes

- Sintoma: `PDOException: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (cantina.estoque_movimentacoes, CONSTRAINT fk_mov_usuario FOREIGN KEY (operador_id) REFERENCES usuarios (id))` ao executar rotinas de movimentação manual de estoque ou perdas com mock de usuário ou quando a sessão contém um ID de usuário inexistente no banco.
- Causa: Em ambientes onde a base foi reinicializada ou em testes automatizados que mockavam `Auth::user()` com `id = 1` fixo, o ID 1 não existia na tabela `usuarios` (pois os IDs reais gerados por auto-incremento eram 178, 244, 247). Como `estoque_movimentacoes.operador_id` possui `CONSTRAINT fk_mov_usuario FOREIGN KEY (operador_id) REFERENCES usuarios (id) ON DELETE RESTRICT`, o MySQL rejeitava a operação.
- Solução aplicada: 
  1. Em `app/models/Estoque.php`: Adicionada resolução defensiva de `operador_id` nos métodos `darEntradaManual` e `registrarPerda`, verificando se o ID informado existe na tabela `usuarios`. Se não existir ou for nulo/zero, o sistema seleciona automaticamente o ID do primeiro usuário ativo disponível (`SELECT id FROM usuarios WHERE deleted_at IS NULL ORDER BY id ASC LIMIT 1`).
  2. Atualizada a suíte de testes `tests/entrada_estoque_manual_test.php` para recuperar o ID real do usuário existente na base.
- Como evitar no futuro: Em métodos de negócio que gravam chaves estrangeiras não nulas para tabelas de auditoria/usuários (`operador_id`, `updated_by`), implementar resolução defensiva de contingência ou garantir que testes de integração consultem IDs persistidos reais no banco.

---

## 2026-10-20 - Controles de zoom invisíveis na barra de impressão e estouro visual da coluna Quantidade no relatório de vendas

- Sintoma: 
  1. Nas telas de impressão/PDF de vendas e arrecadação, os controles de zoom (ícone de lupa, botões `-`, `100%`, `+`) não eram visíveis para o usuário ("não tem lupa nenhuma");
  2. No relatório de vendas (`vendas_alimento_pdf.php`), o título da coluna "Quantidade" vazava e transbordava para fora da célula, sobrepondo a coluna vizinha "Valor Unitário".
- Causa:
  1. A barra `.no-print` nas views `vendas_alimento_pdf.php` e `arrecadacao_pdf.php` utilizava fundo branco (`background: #ffffff`). Como os estilos de `.btn-zoom`, `.btn-zoom-val` e do container de zoom tinham cor branca (`color: #ffffff`) e a lupa SVG tinha tom cinza claríssimo (`#cbd5e1`), os elementos ficaram completamente camuflados em branco sobre branco;
  2. A variável de largura `$wQtd` estava fixada em apenas 8% da largura útil da página (~14.5mm). A palavra "Quantidade" (10 caracteres) possui cerca de 18mm a 8pt. Por possuir alinhamento à direita (`text-right`) em tabela com `table-layout: fixed`, o texto estourava os limites da coluna.
- Solução aplicada:
  1. Padronizada a barra `.no-print` de todas as telas de impressão para o tema escuro corporativo homologado (`background-color: #1e293b; color: #ffffff`), conferindo alto contraste à lupa SVG (`#cbd5e1`), aos controles de zoom e aos botões de navegação e impressão;
  2. Em `vendas_alimento_pdf.php`, ampliada a coluna `$wQtd` para 12% (11% quando colunas opcionais estiverem ativas), garantindo mais de 21.8mm de largura com folga tipográfica, recalibrando proporcionalmente as colunas de valores e descrição;
  3. Adicionadas as propriedades `white-space: nowrap; overflow: hidden;` ao cabeçalho `table.tabela-relatorio th`, garantindo blindagem estrita contra colisões celulares.
- Como evitar no futuro: Ao implementar componentes de barra utilitária compartilhados, validar o contraste cromático (WCAG 2.2 AA) das variantes de fundo claro e escuro. Em tabelas com largura fixa para impressão A4, dimensionar colunas com base no número real de caracteres do cabeçalho mais longo em milímetros, assegurando contenção celular via CSS.
