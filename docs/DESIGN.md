---
system_name: "Cantina Web - Colégio Monteiro Lobato"
version: "2.0.0"
document_type: "Design System & UI Guidelines (Anti-Slop Enterprise Edition)"
target_surface: "Web Desktop & POS Terminals (1366x768 / 1920x1080)"
theme: "Editorial Utilitarian / High-Density Enterprise"
institution:
  name: "Colégio Monteiro Lobato"
  tagline: "Valores para o Futuro!"
  logo_asset: "logo-colegio.jpg"
---

# Design System & Diretrizes Visuais de Interface (V2 — Anti-Slop)

Este documento estabelece a especificação estética, formal e cromática do sistema **Cantina Web — Colégio Monteiro Lobato**, refinada a partir das diretrizes da skill **`design-taste-frontend`**.

O objetivo é erradicar padrões genéricos de templates de IA ("AI slop" — tais como pílulas fluorescentes espalhadas, cabeçalhos de tabela em azul royal berrante e excesso de caixas cinzas repetitivas), adotando um arquétipo utilitário corporativo contemporâneo, austero, refinado e com identidade de autor (inspirado em referências como Stripe, Linear, GitHub Enterprise e Apple Enterprise).

---

## 1. Identidade Visual e Branding Oficial

### Logo Oficial da Instituição
* **Arquivo:** `assets/img/logo-colegio.jpg` (logo circular oficial com degradê azul celeste, livro aberto, pena e pássaros em revoada).
* **Aplicação na Topbar:** Diâmetro fixo de `36px` a `38px`, perfeitamente circular, com micro-aro de contorno sutil (`box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04)`), garantindo separação elegante sobre o fundo branco.
* **Aplicação em Relatórios A4:** Canto superior esquerdo timbrado, dimensão `48px`, acompanhado pela tipografia institucional.
* **Aplicação na Tela de Login:** Centralizado com `80px` a `88px` acima do título de acesso.

### Conceito Estético: Editorial Utilitarian Enterprise
* **Sensação Geral:** Austera, nítida, de alta densidade de dados, confiável e profissional.
* **Proibições Estritas ("Anti-AI Slop"):**
  * Proibido o uso de cabeçalhos de tabela em azul elétrico saturado (#2563eb sólido) que cansa a vista e parece software antigo.
  * Proibido espalhar badges em formato de pílula colorida ("semáforo de brinquedo") em cada texto ou coluna.
  * Proibido uso de sombras difusas caóticas ou efeitos de vidro ("glassmorphism").
  * Proibido layouts de "3 cards cinzas iguais" repetitivos sem hierarquia.

---

## 2. Paleta de Cores Calibrada (Regra de Acento Único)

A paleta prioriza bases neutras de alto contraste com um **único azul nobre institucional** como cor de acento (*accent color*):

### Base Neutra Estrutural
* **Surface Canvas (Fundo geral da página):** `#f8fafc` (Slate 50 — tom sutil, limpo e repousante)
* **Surface Card / Table (Superfície dos blocos):** `#ffffff` (Branco puro)
* **Surface Header / Hover (Fundo de cabeçalhos de tabela e inputs neutros):** `#f1f5f9` (Slate 100)
* **Surface Subtle Hover (Passagem de mouse em linhas):** `#f8fafc`
* **Border Default (Divisores de cards e inputs):** `#e2e8f0` (Slate 200 — linha nítida e fina)
* **Border Strong (Divisores com ênfase):** `#cbd5e1` (Slate 300)

### Tipografia com Contraste Rigoroso (WCAG AA 4.5:1+)
* **Text Primary (Títulos, tabelas, dados e valores):** `#0f172a` (Slate 900 — quase preto)
* **Text Secondary (Labels de campos, descrições secundárias):** `#475569` (Slate 600 — contraste comprovado > 4.5:1)
* **Text Muted (Micro-cópia técnica, legendas auxiliares):** `#64748b` (Slate 500 — contraste > 4.5:1 em branco)
* **Text Inverted (Texto sobre azul escuro ou verde sólido):** `#ffffff`

### Acento Institucional Nobre (Accent Color)
* **Cobalt Blue Institucional (Acento Único):**
  * Sólido (Botões primários, links principais, abas ativas): `#1d4ed8` (Hover: `#1e40af`, Active: `#172554`)
  * Linha de Acento / Foco Acessível: `#2563eb`
  * Superfície de Destaque Sutil: `#eff6ff` (Azul 50 suave)

### Cores Semânticas Funcionais (Uso Discreto e Pontual)
As cores semânticas são utilizadas **apenas** quando há informação crítica ou de segurança alimentar, nunca como decoração:
* **Sucesso (Entradas, confirmações):**
  * Texto/Ícone: `#15803d` (Verde escuro) | Fundo pontual: `#f0fdf4` | Borda: `#bbf7d0`
* **Perigo / Erro (Cancelamentos, estornos, incidentes):**
  * Texto/Ícone: `#b91c1c` (Vermelho escuro) | Fundo pontual: `#fef2f2` | Borda: `#fecaca`
* **Alerta / Alérgeno (Atenção a ingredientes, travas de corte):**
  * Texto/Ícone: `#92400e` (Âmbar escuro) | Fundo pontual: `#fffbeb` | Borda: `#fde68a`

---

## 3. Tipografia e Numerais Tabulares

* **Família Tipográfica Principal:** `'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif`
* **Alinhamento Numérico e Monetário Obrigatório:**
  * Toda moeda (`R$ 0,00`), quantidade, data, hora, matrícula e código de auditoria deve utilizar a classe `.font-tabular` ou tag `<code class="font-tabular">`:
    `font-feature-settings: 'tnum' 1, 'zero' 1; font-variant-numeric: tabular-nums;`
* **Escala Tipográfica:**
  * **H1 (Título de Módulo / Página):** `17px` a `18px`, peso `700`, line-height `1.25`, tracking `-0.015em`, cor `#0f172a`.
  * **H2 (Título de Bloco / Modal):** `14px` a `15px`, peso `600`, line-height `1.3`, cor `#0f172a`.
  * **Table Header (`<th>`):** `11px` a `12px`, peso `600`, tracking `+0.04em`, texto `#475569`, text-transform: uppercase.
  * **Table Body (`<td>`) e Parágrafos:** `13px`, peso `400`, line-height `1.45`, cor `#0f172a`.
  * **Labels de Formulário:** `11px` a `12px`, peso `600`, tracking `+0.02em`, cor `#475569`, text-transform: uppercase sutil.
  * **Valores em Destaque (Saldos, Totais de Caixa):** `20px` a `24px`, peso `700`, cor `#0f172a`.
  * **Micro-cópia e Legendas:** `11px`, peso `400`, cor `#64748b`.

---

## 4. Estilo e Microinterações de Botões

Os botões possuem altura fixa, tipografia nítida e **feedback tátil físico**:

* **Efeito Tátil Físico Obrigatório:**
  ```css
  .btn-enterprise:active {
    transform: translateY(1px) scale(0.985);
  }
  ```
* **Foco Acessível em Toda a Aplicação:**
  ```css
  :focus-visible {
    outline: 2px solid #1d4ed8 !important;
    outline-offset: 2px !important;
  }
  ```
* **Variantes de Botão:**
  1. **Primário (`.btn-primary` / `.btn-enterprise.btn-primary`):**
     Fundo `#1d4ed8`, borda `#1e40af`, texto `#ffffff`. Para ações definitivas ("Gravar", "Confirmar", "Avançar").
  2. **Secundário Neutro (`.btn-secondary` / `.btn-enterprise.btn-secondary`):**
     Fundo `#ffffff`, borda `#cbd5e1`, texto `#1e293b`. Hover: fundo `#f8fafc`, borda `#94a3b8`.
  3. **Perigo (`.btn-danger` / `.btn-enterprise.btn-danger`):**
     Fundo `#dc2626`, borda `#b91c1c`, texto `#ffffff`. Para cancelamentos e estornos.
  4. **Sucesso (`.btn-success` / `.btn-enterprise.btn-success`):**
     Fundo `#16a34a`, borda `#15803d`, texto `#ffffff`. Para liquidações e aprovações.
  5. **Tamanho Compacto (`.btn-sm`):**
     Altura `30px`, padding `0 10px`, fonte `12px`.

---

## 5. Tabelas Operacionais Contemporâneas (Data Grid)

Ao contrário dos templates antigos com faixas azuis pesadas, a tabela adota o padrão contemporâneo:
* **Cabeçalho (`<th>`):** Fundo cinza suave `#f8fafc` ou `#f1f5f9`, borda inferior de `1px solid #cbd5e1`, texto em `11px`, peso `600`, tracking `+0.04em`, cor `#475569`, caixa alta leve.
* **Linhas (`<td>`):** Fundo branco, altura entre `36px` e `38px`, padding horizontal de `12px` a `16px`, borda inferior `1px solid #e2e8f0`.
* **Hover de Linha:** Fundo sutil `#f8fafc` ou `#f1f5f9` (sem azul fluorescente).
* **Alinhamento Rigoroso:**
  * Texto descritivo e nomes: à esquerda (`cell-left`).
  * Códigos, datas e métodos: centralizados (`cell-center`).
  * Valores monetários e quantidades: estritamente à direita (`cell-right`) com fonte tabular.

---

## 6. Tratamento Discreto de Status (Substituição de Pílulas)

* **Status Ativo / Sucesso:** Em vez de uma pílula verde chamativa, utiliza-se um indicador tipográfico refinado com bullet discreto:
  `<span class="status-indicator status-active"><span class="bullet">●</span> Ativo</span>`
  (Texto `#15803d`, sem fundo pesado).
* **Tags de Código e Métodos HTTP:**
  Em vez de pílulas coloridas estilo "brinquedo", códigos e verbos HTTP usam tipografia monospace tabular com borda fina neutra:
  `<code class="code-tag">GET</code>`, `<code class="code-tag">POST</code>` (Fundo `#f1f5f9`, borda `#e2e8f0`, texto `#334155`).
* **Tags de Alérgenos (Segurança Alimentar):**
  Utilizadas apenas quando há risco real ao aluno:
  `<span class="tag-alergeno">[CONTÉM LACTOSE]</span>` (Borda âmbar fina, fundo `#fffbeb`, texto `#92400e`).

---

## 7. Estrutura de Seções: Stat-Strip e Bento Dividido

* **Métricas Principais (`.stat-strip`):**
  Faixa contínua com divisores verticais finos em vez de cards flutuantes soltos. Cada item contém rótulo em `11px` maiúsculo e valor em `14px` negrito com fonte tabular.
* **Containers de Seção (`.card-enterprise`):**
  Superfície em `#ffffff`, borda sutil `1px solid #e2e8f0`, sem sombras difusas, cantos discretos de `6px`.
* **Divisão de Conteúdo:**
  Privilegiar `border-top` e linhas divisórias limpas em vez de criar caixas dentro de caixas.

---

## 8. Regras de Aplicação e Pré-Flight Check

1. **Anti-Slop Audit:** A tela parece ter sido gerada por um gerador automático de dashboard? Se sim, remova cores decorativas, remova pílulas desnecessárias e unifique a paleta no acento azul nobre.
2. **Contraste Mínimo 4.5:1:** Todo texto contra qualquer superfície deve atender ao critério WCAG AA.
3. **Sem Quebra de Linha em Ações:** Textos de botões devem caber em uma única linha no desktop.
4. **Fonte Tabular:** Valores em reais e quantidades sempre alinhados verticalmente com `font-variant-numeric: tabular-nums;`.