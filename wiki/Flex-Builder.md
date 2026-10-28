> 🇧🇷 [Ler em Português](#-flex-builder-fse-v250--motor-visual-em-blocos--grid-auto-reflow) | 🇺🇸 [Read in English](#-flex-builder-fse-v250--visual-block-engine--auto-reflow-grid)

---

# 🇧🇷 Flex Builder FSE (v2.5.0) — Motor Visual em Blocos & Grid Auto-Reflow 🏗️

O **Flex Builder** (`/admin/builder`) é o motor oficial de *Full Site Editing (FSE)* do **Domain System**. Ele permite compor visualmente **Cabeçalhos (`header`)**, **Rodapés (`footer`)** e **Seções Centrais (`section`)** com **Design Tokens Globais**, **14 Blocos Semânticos**, **Visibilidade Responsiva Isolada por Elemento nas 4 Resoluções** e **Reajuste Automático de Dimensões do CSS Grid (Auto-Reflow)**.

---

## 🏛️ 1. Arquitetura SOLID & Contratos (`src/Core/Theme/Parts`)

A estrutura do Flex Builder reside em contratos desacoplados no núcleo (`src/Core/Theme/Parts/Contracts/`), sem dependências diretas de controladores ou superglobais:

| Contrato (Interface) | Implementação Padrão | Responsabilidade |
| :--- | :--- | :--- |
| `ThemePartCompilerInterface` | `ThemePartRenderer` | Caminho único de compilação compartilhado entre o **Preview Ao Vivo do Studio** (`ThemePartPreviewService`) e a **Publicação no Frontend**. |
| `ThemePartRendererInterface` | `ThemePartRenderer` | Orquestra `renderHeader()`, `renderFooter()`, `renderSection()` e `renderTemplateSection()` com fallback automático para `partials/header.php` e `partials/footer.php`, além de proteção contra recursão infinita em shortcodes `[section]`. |
| `ThemePartValidatorInterface` | `ThemePartValidator` | Valida estruturalmente todo o payload antes de qualquer gravação (`type`, `layout_preset`, existência do bloco no `BlockRegistryInterface`, compatibilidade via `getAllowedPartTypes()` e limites de profundidade/tamanho). |
| `LayoutGridInterface` | `LayoutGridRenderer` | Compila as seções em CSS Grid responsivo e recalcula automaticamente o `grid-template-columns` quando colunas inteiras têm seus elementos ocultados em uma resolução. |
| `ElementStyleRendererInterface` | `ElementStyleRenderer` | Higieniza propriedades CSS inline (bloqueando `expression()`, `url(javascript:...)`, `<style>`) e gera as classes de visibilidade `.ds-hide-desktop`, `.ds-hide-laptop`, `.ds-hide-tablet` e `.ds-hide-mobile` por elemento. |
| `BlockRegistryInterface` / `BlockProviderInterface` | `BlockRegistry` / `CoreBlockProvider` | Registro extensível dos 14 blocos nativos e abertura para plugins externos registrarem novos blocos sem alterar o Core (OCP). |
| `HtmlSanitizerInterface` | `SafeHtmlSanitizer` | Sanitização HTML inline com **lista autorizada de esquemas de URL** (`http`, `https`, `mailto`, `tel` e caminhos relativos seguros). |

---

## 📱 2. Visibilidade Responsiva por Elemento & Auto-Reflow do CSS Grid (4 Resoluções)

No Studio do Flex Builder (`/admin/builder/edit`), a barra superior oferece **4 resoluções em tempo real**:
1. **💻 Desktop (`100%` / $\ge 1025\text{px}$)** $\rightarrow$ `.ds-hide-desktop`
2. **💻 Laptop (`1024px` / $769\text{px}\text{–}1024\text{px}$)** $\rightarrow$ `.ds-hide-laptop`
3. **📱 Tablet (`768px` / $481\text{px}\text{–}768\text{px}$)** $\rightarrow$ `.ds-hide-tablet`
4. **📱 Mobile (`375px` / $\le 480\text{px}$)** $\rightarrow$ `.ds-hide-mobile`

### Isolamento por Elemento (Preservação da Seção)
Ao selecionar um elemento (por exemplo, o bloco **Logotipo**) e marcar *"Ocultar no Tablet (768px)"*, apenas o wrapper desse elemento (`.ds-element-wrapper`) recebe `.ds-hide-tablet`. O container da seção (`.ds-section-wrapper`) não é ocultado como um todo, mantendo os demais elementos da seção visíveis normalmente.

### Reajuste Automático de Dimensões (`resolveResponsiveGridTemplate`)
Quando **todos** os elementos de uma coluna ficam ocultos em uma resolução específica:
- A coluna vazia (`.ds-grid-col`) recebe automaticamente `ds-hide-{resolução}`.
- O `LayoutGridRenderer` recalcula a propriedade `grid-template-columns` apenas com as frações das colunas que permaneceram visíveis:
  - Exemplo em **Header Pro (`1fr 2fr 1fr`)**: se a Coluna 1 (Logo) for ocultada em `1024px`, o grid das 2 colunas restantes passa automaticamente para **`2fr 1fr`**.
  - Se a Coluna 1 e a Coluna 3 forem ocultadas em `768px`, restando apenas a Coluna 2 (Menu), o grid se ajusta para **`1fr`** (ocupando 100% da largura disponível).

---

## 🧩 3. Os 14 Blocos Semânticos Nativos

1. **`heading` (Título):** Títulos de H1 a H6 com tipografia integrada aos Design Tokens.
2. **`paragraph` (Parágrafo):** Texto rico sanitizado via `SafeHtmlSanitizer`.
3. **`image` (Imagem):** Upload seguro via `MediaUploadService` (PNG, JPG, WEBP, GIF e SVG validado com `DOMDocument` contra XXE/XSS).
4. **`video` (Vídeo):** Embed 16:9 responsivo para YouTube, Vimeo ou MP4.
5. **`nav_menu` (Menu de Navegação):** Menu com 4 estilos de botão mobile (`classic`, `pill`, `minimal`, `dots`) e 3 alinhamentos (`left`, `center`, `right`).
6. **`logo` (Logotipo):** Imagem ou texto com realce na cor primária.
7. **`button` (Botão Estilizado):** Botão com suporte a ícone SVG opcional e 5 animações de hover (`grow`, `lift`, `glow`, `bounce`, `shimmer`).
8. **`cta_button` (Botão CTA):** Call-to-Action simplificado para cabeçalhos e seções de destaque.
9. **`post_cards_grid` (Grade de Cards / Posts):** Grade responsiva de posts (restrito por contrato exclusivamente a `section`, não permitido em `header` e `footer`).
10. **`shortcode` (Shortcode Dinâmico):** Renderiza shortcodes registrados no sistema.
11. **`divider` (Divisória / Espaçador):** Linha divisória ou espaçamento vertical.
12. **`social_icons` (Redes Sociais):** Ícones vetoriais gerenciados via `SvgIconRegistryInterface`.
13. **`copyright` (Copyright):** Rodapé institucional dinâmico com marcadores `{year}` e `{site_name}`.
14. **`custom_code` (Código Customizado):** Bloco para inserção de fragmentos customizados de HTML/CSS/SVG com sanitização.

---

# 🇺🇸 Flex Builder FSE (v2.5.0) — Visual Block Engine & Auto-Reflow Grid 🏗️

**Flex Builder** (`/admin/builder`) is the official *Full Site Editing (FSE)* visual engine of **Domain System**. It enables visual composition of **Headers (`header`)**, **Footers (`footer`)**, and **Central Sections (`section`)** powered by **Global Design Tokens**, **14 Semantic Blocks**, **Per-Element Responsive Visibility across 4 Resolutions**, and **Automatic CSS Grid Dimension Readjustment (Auto-Reflow)**.

---

## 🏛️ 1. SOLID Architecture & Contracts (`src/Core/Theme/Parts`)

- **Single Compilation Path (`ThemePartCompilerInterface`):** `ThemePartPreviewService` delegates section, column, and block compilation to `ThemePartCompilerInterface` (`ThemePartRenderer`), ensuring consistency between Studio Live Preview and frontend output.
- **Pre-Save Structural Validation (`ThemePartValidatorInterface`):** `ThemePartValidator` checks part types, layout presets, block existence, part-type compatibility (`getAllowedPartTypes()`), and nesting limits before writing to disk.
- **Per-Element Visibility & Auto Grid Reflow (`LayoutGridRenderer`):** Hiding an element in `Desktop (100%)`, `Laptop (1024px)`, `Tablet (768px)`, or `Mobile (375px)` toggles only that `.ds-element-wrapper` (preserving the containing `.ds-section-wrapper`). When all elements in a column are hidden at a given breakpoint, the column collapses and `grid-template-columns` recalculates automatically.
