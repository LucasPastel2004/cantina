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

## 2026-09-16 - Recursão e estouro de memória na inicialização de Session em ambiente CLI

- Sintoma: `Fatal error: Allowed memory size exhausted in app/Core/Session.php` durante a execução de simulações de requisições CLI.
- Causa: Chamada cruzada de `Session::start()` com `Session::generateCsrfToken()` -> `Session::ensureStarted()` antes de a propriedade `$started` ser consolidada, gerando chamadas recursivas infinitas caso `@session_start()` não ativasse imediatamente a sessão nativa em tempo de teste CLI.
- Solução aplicada: Adicionada flag de proteção reentrante `$isInitializing`, verificação de `!headers_sent()` antes da configuração de cookies e fallback garantindo `$_SESSION = []` em ambiente CLI.
- Como evitar no futuro: Em classes singleton ou manipuladoras de estado global (como Sessão e Roteador), implementar flags explícitas de inicialização em andamento para neutralizar recursões circulares ao chamar métodos auxiliares internos.

