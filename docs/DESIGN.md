---
system_name: "Cantina Web - Colégio Monteiro Lobato"
version: "1.0.0"
document_type: "Design System & UI Guidelines"
target_surface: "Web Desktop & POS Terminals (1366x768 / 1920x1080)"
theme: "Institutional Light / High-Density Enterprise"
institution:
  name: "Colégio Monteiro Lobato"
  tagline: "Valores para o Futuro!"
  logo_asset: "logo-colegio.jpg"
---

# Design System & Diretrizes Visuais de Interface

Este documento estabelece a especificação estética, formal e cromática para orientação de IAs e desenvolvedores frontend. O objetivo é reproduzir a identidade visual, a densidade e os componentes de interface observados no software de referência, integrando formalmente a identidade visual oficial do colégio.

---

## 1. Identidade Visual e Branding Oficial

### Logo Oficial da Instituição
* **Arquivo e Identificação:** A logo oficial é a imagem circular do **Colégio Monteiro Lobato** (`logo-colegio.jpg`), caracterizada pelo fundo em degradê azul celeste, ilustração central do livro aberto em perspectiva com páginas em tons de azul ciano, pena de escrever preta com pássaros em revoada e a tipografia institucional *"Colégio Monteiro Lobato"* com o lema *"Valores para o Futuro!"*.
* **Aplicação no Sistema:**
  * **Header / Barra de Topo:** A logo deve ser exibida no canto superior esquerdo com altura fixa de `36px` a `40px`, mantendo seu formato circular ou inserida sobre fundo branco com borda suave.
  * **Relatórios e Extratos Impressos (A4):** Posicionamento obrigatório no canto superior esquerdo do cabeçalho timbrado, com dimensão de `48px` a `56px`, acompanhada ao lado direito pela Razão Social e dados da unidade.
  * **Modais e Telas de Login:** Exibição centralizada com diâmetro de `80px` a `96px` acima do título da aplicação.

### Conceito Estético Geral
* **Arquétipo Visual:** Interface utilitária corporativa (Enterprise Data-Dense ERP). Foco em contraste claro, leitura rápida de dados tabulares, tipografia compacta e uso estruturado de cores primárias para delimitar cabeçalhos de tabelas e seções.
* **Sensação da Interface:** Limpa, organizada, austera e funcional. Não utiliza sombras difusas, efeitos de vidro (glassmorphism) ou bordas excessivamente arredondadas.

---

## 2. Paleta de Cores

Baseada nas cores extraídas da logo oficial e nas telas de alta densidade observadas no sistema de referência:

### Cores Institucionais e Primárias
* **Primary Royal Blue (Header de Tabelas, Topbar e Seleção):** `#2563eb` (Hover: `#1d4ed8`, Active: `#1e40af`)
* **Brand Light Blue (Tom suave derivado da logo):** `#dbeafe` (Fundo de linhas ativas / seleção)
* **Brand Cyan Accent (Detalhes do livro na logo):** `#0ea5e9`
* **Surface Background (Fundo da aplicação):** `#f1f5f9` (Slate 100 suave)
* **Surface Card / Modal (Branco puro):** `#ffffff`
* **Surface Input Disabled / Fundo Neutro:** `#f8fafc` (Slate 50)
* **Border Default (Divisores e bordas de inputs):** `#cbd5e1` (Slate 300)
* **Border Subtle (Linhas de tabela):** `#e2e8f0` (Slate 200)

### Tipografia
* **Text Primary (Títulos, tabelas e dados):** `#0f172a` (Slate 900)
* **Text Secondary (Labels de formulário e descritores):** `#475569` (Slate 600)
* **Text Muted (Placeholders e textos auxiliares):** `#94a3b8` (Slate 400)
* **Text On-Primary (Texto sobre azul escuro e verde):** `#ffffff`

### Estados e Ações Operacionais
* **Action Positive / Success Green (Confirmar, Gravar, Entrada de Estoque):**
  * Sólido: `#16a34a` (Hover: `#15803d`)
  * Superfície / Tag: `#dcfce7`
  * Texto em tag: `#15803d`
* **Action Negative / Danger Red (Cancelar, Excluir, Estorno, Erro):**
  * Sólido: `#dc2626` (Hover: `#b91c1c`)
  * Superfície / Tag: `#fee2e2`
  * Texto em tag: `#b91c1c`
* **Warning Yellow / Alert (Avisos, Diálogos de Atenção):**
  * Sólido / Ícone: `#eab308` ou `#ca8a04`
  * Superfície: `#fef9c3`
  * Texto: `#854d0e`

---

## 3. Tipografia Sugerida

* **Família Tipográfica Principal:** `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`
* **Modo Numérico:** Ativação obrigatória de fontes tabulares para alinhamento vertical de moedas e quantidades: `font-feature-settings: 'tnum' 1, 'zero' 1;`
* **Escala Tipográfica:**
  * **H1 (Título de Página / Cabeçalho de Módulo):** `18px` (`1.125rem`), peso `700`, line-height `1.25`
  * **H2 (Título de Modal / Seção):** `15px` (`0.9375rem`), peso `600`, line-height `1.3`
  * **Table Header (`<th>`):** `11px` a `12px`, peso `600`, tracking `+0.025em`, texto `#ffffff`
  * **Table Body (`<td>`) e Parágrafos:** `13px` (`0.8125rem`), peso `400`, line-height `1.4`
  * **Labels de Formulário:** `12px`, peso `500`, cor `#475569`
  * **Valores em Destaque (Totais, Saldos em Tela):** `18px` a `22px`, peso `700`, cor `#0f172a`
  * **Microcopy / Legendas:** `11px`, peso `400`, cor `#64748b`

---

## 4. Espaçamentos (Spacing Grid)

A escala segue módulos compactos de **4px** para acomodar alto volume de informações em tela única:

* `space-1`: `4px` (gap entre ícone e texto, margem inferior de labels)
* `space-2`: `8px` (padding vertical de inputs e células de tabela)
* `space-3`: `12px` (padding horizontal de inputs, distância entre colunas de formulário)
* `space-4`: `16px` (padding interno de cards, divisões principais)
* `space-5`: `20px` (distanciamento entre blocos de filtro e tabelas)
* `space-6`: `24px` (padding interno de caixas de diálogo modais)

---

## 5. Arredondamento de Bordas (Border Radius)

Estilo discreto e estruturado, evitando curvas pronunciadas:

* `radius-none`: `0px` (utilizado em células de tabelas e divisores retos)
* `radius-sm`: `3px` a `4px` (padrão obrigatório para botões, inputs, selects e badges)
* `radius-md`: `6px` (cards de agrupamento e painéis secundários)
* `radius-lg`: `8px` (modais e janelas flutuantes)
* `radius-full`: `9999px` (avatares circulares e tags de status tipo pílula)

---

## 6. Estilo de Botões

Os botões priorizam altura padronizada de **32px a 36px**, fonte de peso **600**, ícones à esquerda e espaçamento horizontal proporcional:

### Variantes de Botão
1. **Botão Primário de Sistema (`.btn-primary`):**
   * Background: `#2563eb`, Texto: `#ffffff`, Borda: `1px solid #1d4ed8`
   * Hover: `#1d4ed8` | Active: `#1e40af`
2. **Botão Positivo / Gravar (`.btn-success`):**
   * Background: `#16a34a`, Texto: `#ffffff`, Borda: `1px solid #15803d`
   * Hover: `#15803d` | Active: `#166534`
3. **Botão Destrutivo / Cancelar (`.btn-danger`):**
   * Background: `#dc2626` (ou branco com borda e texto `#dc2626`), Texto: `#ffffff`, Borda: `1px solid #b91c1c`
   * Hover: `#b91c1c`
4. **Botão Neutro / Secundário (`.btn-secondary`):**
   * Background: `#ffffff`, Texto: `#334155`, Borda: `1px solid #cbd5e1`
   * Hover: `#f8fafc`, Borda: `#94a3b8`
5. **Ações Rápidas de Tabela (`.btn-icon`):**
   * Quadrados de `26px x 26px`, fundo transparente ou cinza suave (`#f1f5f9`), ícones monocromáticos (lápis, lixeira, olho).

---

## 7. Estilo de Formulários

Layout estruturado em grade de 2 a 4 colunas com rótulos fixados no topo do campo:

* **Labels:** Posicionados acima do input com `margin-bottom: 4px;`, estilo `font-size: 12px; font-weight: 500; color: #475569;`. Asterisco vermelho em campos requeridos.
* **Inputs de Texto, Busca e Numéricos:**
  * Altura fixa: `34px`
  * Fundo: `#ffffff`
  * Borda: `1px solid #cbd5e1`
  * Raio da borda: `4px`
  * Padding: `4px 10px`
  * Foco: Borda `#2563eb` com halo `box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);`
* **Campos Financeiros / Moeda (`R$`):**
  * Prefixo monetário fixado à esquerda com fundo neutro (`#f1f5f9`).
  * Numerais alinhados à direita com fonte tabular.
* **Dropdowns / Selects:**
  * Altura idêntica (`34px`), fundo branco, seta cinza discreta alinhada à direita.

---

## 8. Tabelas, Cards, Modais e Componentes Principais

### Tabelas Operacionais (Data Grid)
* **Cabeçalho (`<th>`):** Fundo sólido azul `#2563eb`, texto branco em caixa alta leve ou normal, peso `600`, altura de `36px`, ícones ilustrativos brancos alinhados ao texto da coluna.
* **Linhas (`<tr>` / `<td>`):** Altura compacta de `36px` a `40px`. Zebra striping opcional alternando linhas brancas (`#ffffff`) e cinza muito claro (`#f8fafc`).
* **Bordas:** Linha horizontal de separação inferior com `1px solid #e2e8f0`.
* **Hover de Linha:** Fundo levemente azulado (`#eff6ff`) com cursor pointer quando a linha for selecionável.
* **Alinhamento:** Textos descritivos à esquerda; códigos, status e datas centralizados; valores monetários e quantidades estritamente alinhados à direita.
* **Rodapé de Totalização (`<tfoot>`):** Fundo cinza `#e2e8f0` com textos em negrito destacando somatórios.

### Cards e Contêineres de Seção
* Superfície em branco `#ffffff`, borda perimetral sutil de `1px solid #e2e8f0`, cantos de `6px`.
* Cabeçalho de bloco com separador fino e título em `14px` peso `600`.

### Caixas de Diálogo Modais
* Backdrop neutro escurecido com `rgba(15, 23, 42, 0.45)`.
* Corpo do modal centralizado em fundo branco com bordas de `8px`.
* Cabeçalho com barra de título limpa e botão de fechamento `✕` no canto superior direito.
* Rodapé do modal com botões de ação (botão de confirmação em verde ou azul com destaque primário).

---

## 9. Estados Visuais

### Sucesso (Success)
* Modal central com ícone circular verde de verificação (`✓`), texto de confirmação claro e botão de fechamento verde.
* Badges de status: fundo `#dcfce7`, borda `#bbf7d0`, texto `#15803d`.

### Erro (Error)
* Banner com fundo `#fee2e2`, borda `#fca5a5`, texto `#991b1b` e ícone de exclamação vermelho.
* Inputs com erro recebem borda `#dc2626` e mensagem de ajuda inferior em `11px` vermelha.

### Alerta (Warning)
* Modal de advertência exibindo triângulo amarelo centralizado com ponto de exclamação sobre fundo branco, com opções afirmativa e negativa.
* Tags de restrição alimentar ou atenção: fundo `#fef3c7`, borda `#fcd34d`, texto `#92400e`.

### Vazio (Empty State)
* Centralizado em tabelas ou listagens quando não houver dados.
* Padding generoso (`32px`), tipografia em `#64748b`, frase padronizada: *"Não há nada para mostrar aqui"*.

### Carregamento (Loading State)
* Modal de processamento centralizado com título *"Processamento em Andamento"*.
* Spinner circular ou ícone de engrenagem em tom azul claro (`#3b82f6`) com rotação contínua.
* Cronômetro digital em tempo real no formato `00:00:01` exibido abaixo do ícone animado.

---

## 10. Regras de Aplicação para Manter Consistência

1. **Prioridade de Navegação:**
   * A hierarquia visual deve privilegiar leitura rápida e foco claro em inputs para comandos de teclado (`Tab` e `Enter`).
2. **Formatação Monetária e Numérica:**
   * Qualquer valor monetário exibido deve utilizar o padrão brasileiro: `R$ 0,00` com alinhamento à direita em colunas tabulares.
3. **Itens Esgotados ou Desabilitados:**
   * Devem manter visibilidade em opacidade atenuada (`0.5`), com fundo acinzentado e cursor `not-allowed`.
4. **Padronização de Relatórios Impressos:**
   * Cabeçalho institucional padronizado contendo a logo oficial circular do Colégio Monteiro Lobato à esquerda, tipografia em preto/tons de cinza e grades com linhas contínuas de 0.5pt para nitidez na impressão física.