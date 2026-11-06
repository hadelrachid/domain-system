# Plano de Execução: Auditoria Cirúrgica SOLID (ChatGPT + DeepSeek) & Release de Nova Versão (v2.6.0)

**Data do Planejamento:** 03/11/2026  
**Status:** Concluído em 04/11/2026 (v2.6.0)  
**Fontes da Auditoria:**
- [`auditoria/ChatGPT.txt`](../auditoria/ChatGPT.txt) — *Auditoria Cirúrgica: FlexTheme e Flex Builder*
- [`auditoria/DeepSeek.txt`](../auditoria/DeepSeek.txt) — *Auditoria Cirúrgica de Contratos: Domain System v2.5.0*

---

## 1. Resumo das Entregas Recentes (Gatilhos para Nova Versão)

Antes da execução final dos ajustes de auditoria, o ecossistema **FlexTheme / Flex Builder** recebeu grandes evoluções estruturais que justificam o incremento da versão do sistema (**v2.5.0 → v2.6.0**):

1. **Sistema Global de Cores de Texto e Tipografia (Inspirado em Pro-Elements / Elementor):**
   - Suporte a `text_color`, `heading_color`, `link_color` e `link_hover_color` em nível de Seção, Coluna e Bloco via [`ElementStyleSchema`](../src/Core/Theme/Parts/Validation/ElementStyleSchema.php) e [`ElementStyleRenderer`](../src/Core/Theme/Parts/Services/ElementStyleRenderer.php).
   - Integração com Tokens Globais (`var(--primary-color)`, `var(--secondary-color)`, `var(--text-color)`, `var(--accent-color)`) no Inspector do Flex Builder.
2. **Personalização Completa do Menu Mobile (`ds-mobile-drawer`) e Botão Hambúrguer:**
   - Contrato [`MobileToggleStyleInterface`](../src/Core/Theme/Parts/Contracts/MobileToggleStyleInterface.php) com **8 estilos de botão toggle** (`bars`, `bars_staggered`, `dots_vertical`, `dots_grid`, `plus_cross`, `chevron_down`, `pill_label`, `minimal_two_lines`) e **4 formatos de borda** (`rounded`, `square`, `pill`, `ghost`).
   - Personalização da gaveta lateral mobile (largura, cores de fundo, cabeçalho, botão fechar) e **7 formatos de botões de menu mobile** (`flat`, `rounded`, `pill`, `outlined`, `filled_soft`, `left_accent`, `underlined`) com cores de estado e hover.
3. **Menus Hierárquicos com Submenus (`Walker` SOLID + Drag & Drop Estilo WordPress):**
   - Contrato [`MenuTreeBuilderInterface`](../src/Core/Theme/Parts/Contracts/MenuTreeBuilderInterface.php) e implementação [`MenuTreeBuilder`](../src/Core/Theme/Parts/Support/MenuTreeBuilder.php) com proteção anti-ciclo e limite de profundidade (`MAX_DEPTH = 5`).
   - Migração automática de `parent_id` na tabela `nav_menu_items` em [`PdoMenuRepository`](../src/SystemApps/nav_menus/Repositories/PdoMenuRepository.php).
   - Interface de arrastar e soltar horizontal (estilo `wpNavMenu` do WordPress) em [`admin_menus.php`](../src/SystemApps/nav_menus/views/admin_menus.php) e dentro do modal do Flex Builder em [`builder_editor.php`](../src/Plugins/flextheme/views/admin/builder_editor.php).
   - Estilização dedicada para Submenus Desktop (dropdown flutuante, animação, seta indicadora, hover) e Submenus Mobile (acordeão expansível ou indentado).
   - Priorização automática do **Menu Principal (`header`)** como seleção padrão ao abrir o gerenciador de menus.

---

## 2. Roteiro Cirúrgico de Correções SOLID (Auditoria ChatGPT + DeepSeek)

### Etapa 1 — Criação e Refinamento de Contratos Formais (ISP & DIP)

- [x] **1.1. Criar `ShortcodeManagerInterface`** (`src/Core/Shortcode/Contracts/ShortcodeManagerInterface.php`)
  - Métodos do contrato:
    - `register(string $tag, callable $callback): void`
    - `has(string $tag): bool`
    - `parse(string $content): string`
  - Fazer [`ShortcodeManager`](../src/Core/Shortcode/ShortcodeManager.php) implementar `ShortcodeManagerInterface`.
  - Registrar o binding `ShortcodeManagerInterface::class => ShortcodeManager::class` em [`CoreServiceProvider`](../src/Core/Providers/CoreServiceProvider.php).

- [x] **1.2. Criar `ThemePartManagementServiceInterface`** (`src/Plugins/flextheme/Contracts/ThemePartManagementServiceInterface.php`)
  - Métodos do contrato:
    - `savePart(array $data, ?string $activeThemeSlug = null): array`
    - `deletePart(string $id): bool`
    - `duplicatePart(string $id, ?string $targetThemeSlug = null): ?ThemePart`
    - `toggleActive(string $id): ?bool`
    - `activateExclusively(string $id): bool`
    - `exportPart(string $id): ?array`
    - `importPart(array $payload, ?string $activeThemeSlug = null): array`
  - Fazer [`ThemePartManagementService`](../src/Plugins/flextheme/Services/ThemePartManagementService.php) implementar `ThemePartManagementServiceInterface`.

- [x] **1.3. Criar `PageContentRepositoryInterface`** (`src/Plugins/flextheme/BuilderFlex/Contracts/PageContentRepositoryInterface.php`)
  - Métodos do contrato:
    - `findById(int $id): ?array`
    - `update(int $id, array $data): bool`
  - Substituir `private ?object $pageRepo` e as chamadas `method_exists($this->pageRepo, ...)` em [`BuilderAdminController`](../src/Plugins/flextheme/BuilderFlex/Controllers/BuilderAdminController.php) pelo contrato tipado `?PageContentRepositoryInterface $pageRepo = null`.

- [x] **1.4. Tipar `WidgetManagerInterface` e Desacoplar `AbstractWidget`**
  - Em [`WidgetManagerInterface`](../src/Plugins/flextheme/BuilderFlex/Contracts/WidgetManagerInterface.php): alterar `public function bootShortcodes($shortcodeManager): void;` para `public function bootShortcodes(ShortcodeManagerInterface $shortcodeManager): void;`.
  - Atualizar [`WidgetManager`](../src/Plugins/flextheme/BuilderFlex/Services/WidgetManager.php) para respeitar a nova assinatura e proteger contra colisão silenciosa de tags duplicadas.
  - Em [`AbstractWidget`](../src/Plugins/flextheme/BuilderFlex/Support/AbstractWidget.php): permitir injeção opcional de [`DtoRendererInterface`](../src/Plugins/flextheme/BuilderFlex/Contracts/DtoRendererInterface.php) (via setter/construtor gerenciado pelo `WidgetManager`) em vez de instanciar `new DtoRenderer()` diretamente.

---

### Etapa 2 — Desacoplamento Total dos Serviços do FlexTheme (Prioridade P0)

- [x] **2.1. Simplificar [`ThemePartPreviewService`](../src/Plugins/flextheme/Services/ThemePartPreviewService.php) (ChatGPT #4 / DeepSeek P0)**
  - Remover o union type `ThemePartCompilerInterface|BlockRegistryInterface $compilerOrRegistry` e toda a ramificação legada que instancia `new ThemePartRenderer(new JsonThemePartRepository(), new JsonThemePartAssociation(), ...)`.
  - O construtor deve exigir exclusivamente `ThemePartCompilerInterface $compiler`:
    ```php
    public function __construct(
        private ThemePartCompilerInterface $compiler
    ) {}
    ```

- [x] **2.2. Eliminar Fallback Concreto em [`ThemePartManagementService`](../src/Plugins/flextheme/Services/ThemePartManagementService.php) (ChatGPT #3)**
  - Registrar [`ThemePartValidatorInterface`](../src/Core/Theme/Parts/Contracts/ThemePartValidatorInterface.php) em [`CoreServiceProvider`](../src/Core/Providers/CoreServiceProvider.php) e [`BuilderFlexBootstrapper`](../src/Plugins/flextheme/Bootstrappers/BuilderFlexBootstrapper.php).
  - Exigir `ThemePartValidatorInterface $validator` diretamente no construtor de `ThemePartManagementService`, removendo `$validator ?? new ThemePartValidator(...)`.

---

### Etapa 3 — Limpeza de Controllers e Bootstrappers (Prioridade P0 / P1)

- [x] **3.1. Refatorar [`ThemePartsAdminController`](../src/Plugins/flextheme/Controllers/ThemePartsAdminController.php) (ChatGPT #2 / DeepSeek P0)**
  - Substituir os type-hints concretos por `ShortcodeManagerInterface` e `ThemePartManagementServiceInterface`.
  - Remover os fallbacks `?? new ThemeDiscoveryService()`, `?? new MediaUploadService()`, `?? new Translator()`, `?? new ElementStyleRenderer()`, `?? new ThemePartPreviewService()`, `?? new ThemePartManagementService()` e `?? new BaseUrlResolver()` do construtor.
  - Garantir que [`BuilderFlexBootstrapper`](../src/Plugins/flextheme/Bootstrappers/BuilderFlexBootstrapper.php) injete todas as dependências via contratos resolvidos pelo Container.

- [x] **3.2. Remover `new SafeThemeResolver` e `new EnvSiteState` de [`CourseFrontController`](../src/Plugins/academy/Controllers/CourseFrontController.php) e [`ThemeController`](../src/Plugins/flextheme/Controllers/ThemeController.php) (DeepSeek P0 / P1)**
  - Em `CourseFrontController::resolveActiveThemePath()`: usar exclusivamente `$this->pageResolver` (`ThemePageResolverInterface`) e `$this->themeManager` injetados via construtor, removendo a instanciação direta de `SafeThemeResolver` e `EnvSiteState`.
  - Em `ThemeController`: injetar `ThemePageResolverInterface` no construtor e utilizá-lo em `renderHome()` e `renderDoc()` em vez de `new SafeThemeResolver(...)`.

---

### Etapa 4 — Refinamentos de Core (`ThemePartRenderer`, `ThemePart`, `ThemePartValidator`)

- [x] **4.1. Estado de Instância em [`ThemePartRenderer`](../src/Core/Theme/Parts/Services/ThemePartRenderer.php) (DeepSeek P1)**
  - Alterar `private static bool $tokensInjected = false;` para propriedade de instância (`private bool $tokensInjected = false;`) mantendo o método `resetTokensInjection(): void` para testes e múltiplos ciclos de renderização.

- [x] **4.2. Consistência de `layoutPreset` em [`ThemePart`](../src/Core/Theme/Parts/Models/ThemePart.php) e [`ThemePartRenderer`](../src/Core/Theme/Parts/Services/ThemePartRenderer.php) (DeepSeek P1)**
  - Em `ThemePartRenderer::compileDynamicPart()`: usar `$layoutPreset = (string) ($section['layout_preset'] ?? $part->getLayoutPreset() ?: '1_col');`.
  - Em `ThemePart::__construct()`: sincronizar `$this->layoutPreset`, `$this->containerSettings` e `$this->columnsData` a partir de `$sections[0]` apenas quando os argumentos explícitos da raiz estiverem em seu estado default/vazio, evitando sobrescrita silenciosa.

- [x] **4.3. Validação de Dados de Bloco contra `getSchema()` em [`ThemePartValidator`](../src/Core/Theme/Parts/Validation/ThemePartValidator.php) (DeepSeek P2)**
  - Em `ThemePartValidator::validateColumnsData()`: validar se `data` (quando informado) é um `array` e verificar compatibilidade básica dos campos declarados em `$block->getSchema()`.

---

### Etapa 5 — Atualização de Versão (`v2.5.0` → `v2.6.0`) e Sincronização de Documentação

Após todos os testes do PHPUnit passarem com **0 falhas** e **0 violações de DIP**, incrementar a versão oficial do sistema para **`2.6.0`** e revisar todos os arquivos abaixo:

#### 5.1. Código-Fonte e Configuração
- [x] [`src/Core/Providers/CoreServiceProvider.php`](../src/Core/Providers/CoreServiceProvider.php) — Atualizar fallback da constante/configuração de versão (`'2.6.0'`).
- [x] [`manual/index.php`](../manual/index.php) — Atualizar badge e referências de versão no manual interativo.

#### 5.2. Documentação Raiz e Guias Técnicos
- [x] [`README.md`](../README.md) e [`README-en.md`](../README-en.md) — Atualizar versão para `v2.6.0` e incluir resumo dos novos recursos do Flex Builder (Menus Hierárquicos, Mobile Drawer customizável, Cores Globais Pro-Elements e Conformidade 100% SOLID).
- [x] [`ABOUT.md`](../ABOUT.md) e [`ABOUT-en.md`](../ABOUT-en.md) — Atualizar versão e métricas de testes/contratos.
- [x] [`ROADMAP.md`](../ROADMAP.md) — Marcar as melhorias do Flex Builder e Auditoria SOLID como concluídas na `v2.6`.
- [x] [`DEVELOPER_GUIDE.md`](../DEVELOPER_GUIDE.md) — Atualizar versão e exemplos de injeção de contratos.
- [x] [`AI_CONTRACT_GUIDE.md`](../AI_CONTRACT_GUIDE.md) — Remover menções residuais a `$this->events()`, `$this->db()` e `$this->queryBuilder()` (conforme apontado pelo DeepSeek P2) e atualizar versão para `v2.6.0`.
- [x] [`.github/copilot-instructions.md`](../.github/copilot-instructions.md) — Atualizar versão de referência para `v2.6.0`.

#### 5.3. Pasta `docs/` e `wiki/`
- [x] [`docs/CHANGELOG.md`](../docs/CHANGELOG.md) e [`docs/CHANGELOG-en.md`](../docs/CHANGELOG-en.md) — Adicionar seção `## [2.6.0] - 2026-11-04` detalhando todas as entregas e correções SOLID.
- [x] [`docs/README.md`](../docs/README.md), [`docs/auditoria.md`](../docs/auditoria.md) e [`docs/auditoria-en.md`](../docs/auditoria-en.md) — Atualizar status de conformidade e versão.
- [x] [`wiki/Home.md`](../wiki/Home.md) e [`wiki/Flex-Builder.md`](../wiki/Flex-Builder.md) — Documentar os novos controles de Submenus, Mobile Drawer e Cores Globais.
- [x] Regenerar os arquivos consolidados de contexto (`domain-system.txt` / `project.txt`), caso aplicável.
