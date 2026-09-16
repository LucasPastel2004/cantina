# Inventário de Insumos do Projeto

Este documento cataloga todos os arquivos presentes na pasta `docs/` e identifica os insumos do projeto **Cantina Web — Colégio Monteiro Lobato**, especificando sua finalidade, se serão utilizados pelo sistema em tempo de execução e a necessidade de cópia para a área pública/assets durante a construção.

---

## Tabela de Insumos

| Arquivo | O que é | Usado pelo sistema em execução? | Onde será usado | Observações |
|---|---|---|---|---|
| `docs/FSD.md` | Documento de Especificação Funcional e Técnica (FSD). Contém requisitos funcionais, regras de negócio, modelo de dados (26 tabelas), arquitetura MVC, fluxos operacionais e critérios de aceitação. | Não | Documentação técnica e referência arquitetural para a IA codificadora. | Documento normativo primário do projeto. Não deve ser copiado para pastas públicas. |
| `docs/DESIGN.md` | Documento de Diretrizes Visuais e Design System. Especifica a identidade visual, paleta de cores institucional, tipografia Inter tabular (`tnum`), espaçamentos, botões, tabelas de alta densidade e layouts de impressão (A4 e bobina 80mm). | Não | Referência visual e de estilização para construção dos templates HTML e arquivos CSS/JS. | Documento normativo visual. Não deve ser copiado para pastas públicas. |
| `docs/PRD.md` | Documento de Requisitos do Produto (PRD). Registro inicial de escopo, personas, dores do negócio e objetivos gerais do sistema. | Não | Documentação de histórico de concepção do produto. | Conteúdo integralmente consolidado e refinado no `docs/FSD.md`. Não é consumido em runtime. |
| `docs/logo-colegio.jpg` | Logotipo oficial circular do Colégio Monteiro Lobato (fundo degradê azul celeste, livro aberto em perspectiva, pena de escrever com pássaros e lema institucional "Valores para o Futuro!"). | Sim | Tela de login centralizado (`80px-96px`), cabeçalho/sidebar (`36px-40px`) e relatórios timbrados A4 (`48px-56px`). | **Disponibilizado na pasta `docs/`.** Deverá ser copiado durante a construção para `assets/img/logo-colegio.jpg` conforme a arquitetura da aplicação. |


---

## Diretrizes de Gestão de Insumos

1. **Segregação de Pastas:** A pasta `docs/` é estritamente documental e de apoio ao desenvolvimento. Ela não é nem deve ser tratada como pasta pública da aplicação web.
2. **Transferência de Assets em Execução:** Arquivos gráficos (como logos, ícones institucionais ou imagens estáticas) que forem adicionados a `docs/` e forem necessários para a interface em tempo de execução serão copiados para `assets/img/` no momento oportuno da implementação.
3. **Privacidade e Segurança:** Nenhuma planilha com dados reais de alunos nem arquivos XML com dados fiscais devem permanecer na pasta pública do sistema; o armazenamento durante o uso em produção será restrito a `storage/uploads/private/`.
