# Contexto e Instruções para Claude Code (Modo Manutenção)

Este projeto utiliza como documento oficial de contexto e regras de arquitetura o arquivo `AGENTS.md`.

Siga estritamente todas as diretrizes do `AGENTS.md` e o protocolo do Modo Manutenção:

### Antes de qualquer alteração:
1. Ler `docs/MANUTENCAO.md`.
2. Ler `docs/FSD.md`.
3. Ler `docs/DESIGN.md`, se a alteração envolver interface.
4. Ler `docs/STATUS.md`.
5. Ler `docs/ERROS.md`.
6. Entender o pedido do usuário.
7. Explicar o plano antes de alterar arquivos.

### Depois de qualquer alteração:
1. Testar o que foi alterado.
2. Atualizar `docs/STATUS.md`.
3. Registrar erro e solução em `docs/ERROS.md`, se houver.
4. Fazer commit ou entregar os comandos.
5. Explicar ao usuário como validar.

Para detalhes completos de arquitetura, stack, comandos e regras de segurança, consulte `AGENTS.md` e `docs/MANUTENCAO.md`.
