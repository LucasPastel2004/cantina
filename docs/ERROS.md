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


