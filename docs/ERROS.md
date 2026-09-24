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









