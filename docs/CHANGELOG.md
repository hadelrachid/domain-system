# Histórico de Versões (Changelog)

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/), 
e este projeto adota o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---
[ 🇺🇸 Read in English ](CHANGELOG-en.md)

## [2.7.0] - 2026-11-12
### Added
- **DomainDrop — Motor Autônomo de Dropshipping com SDK Oficial do AliExpress (`src/Plugins/domain_drop`):**
  - **Integração AliExpress Open Platform SDK (`AliExpressApiClientInterface`):** Comunicação assinada via protocolo `TOP` (`HMAC-SHA256`) e OAuth 2.0 para busca de produtos (`aliexpress.ds.product.get`), feed recomendado (`aliexpress.ds.recommend.feed.get`), cálculo de frete (`aliexpress.logistics.buyer.freight.get`), criação automatizada de ordens (`aliexpress.trade.buy.placeorder`) e rastreamento logístico em tempo real (`queryTrackingInfo`).
  - **Mineração Autônoma & Ciclo Flash de 24 Horas (`ProductMiningServiceInterface`):** Curadoria inteligente com prioridade para estoque no Brasil (Entreposto Nacional), conversão cambial dinâmica (`USD → BRL`), enriquecimento de títulos/descrições por IA, ordenação por atualização recente (`updated_desc`), sincronização do cronômetro regressivo de 24h da Home com o ciclo diário do servidor e cabeçalhos HTTP anti-cache (`CachePolicy`).
  - **Espaço do Cliente Completo (`/minha-conta`, `/entrar`, `/cadastro`):**
    - **🛰️ Acompanhamento de Entrega Integrado:** Linha do tempo visual de 5 etapas dentro da área do cliente, com consulta direta aos **Correios**, **Cainiao Global** e eventos ao vivo do SDK do AliExpress.
    - **💬 Reclamações, Observações & Dúvidas com Vinculação Opcional:** Abertura de chamados com upload seguro de até 4 fotos/PDFs (validação de *magic bytes*) e vinculação opcional de pedido da conta, código manual (`manual_order_ref`) ou produto do catálogo (`catalog_product_id`), identificando automaticamente o produto e o fornecedor (`BR`/`CN`) mesmo antes da compensação bancária.
  - **Gestão Administrativa de Clientes, SLA & Mediação Bilingue (`/admin/dropship`):**
    - **👥 Clientes & Compradores (`/admin/dropship/customers`):** Submenu estilo CRM (semelhante ao módulo de Alunos do Academy) com busca por nome/e-mail/CPF, métricas de compras e chamados, ativação/suspensão de conta e histórico de solicitações recentes.
    - **📦 Pedidos & Mediação 1-Clique (`/admin/dropship/orders`):** Barra de progresso de SLA de entrega e botão único de mediação (`SellerMediationService`) com tradução automática para **Chinês Simplificado (`zh-CN`)** quando o fornecedor é internacional (`CN`) ou **Português (`pt-BR`)** para fornecedores nacionais (`BR`).
- **Isolamento de Temas Nativos no Flex Builder (`"flex_builder": false`):**
  - Adicionado `ThemeDiscoveryService::supportsFlexBuilder()` e constante `ThemePartInterface::TYPE_PAGE`. Temas dedicados como `rachidd_store` mantêm seu cabeçalho e rodapé nativos isolados de substituições acidentais do Flex Builder, exibindo um banner informativo no editor `/admin/builder`.

### Fixed
- **Links de Documentação HTML (`docs/index.html`, `docs/index_en.html`):** Convertidos todos os links que apontavam para arquivos `.md` externos em páginas HTML nativas (`domain-drop.html`, `ai-contract-guide.html`, `flex-builder.html` e versões em inglês) para abertura direta no navegador e no GitHub Pages sem erro 404.

## [2.6.0] - 2026-11-06
### Added
- **DomainCommerce & Gateways Nativos (Mercado Pago, PayPal, Stripe e Afiliados):**
  - **Mercado Pago Oficial (Brasil):** Pix instantâneo direto na tela (QR Code dinâmico e código Copia-e-Cola), checagem em tempo real a cada 3,5s com liberação automática de matrícula, cartão em até 12x (e opção de 2 cartões) e boleto.
  - **PayPal Oficial (Vendas Internacionais):** Suporte à API REST v2 de Checkout para pagamentos internacionais com saldo e cartões globais, com redirecionamento limpo e verificação de retorno.
  - **Injeção de Dependências Condicional:** Os gateways agora só aparecem no checkout se tiverem credenciais válidas configuradas no sistema, evitando erros para o comprador.
  - **Webhooks de Plataformas de Afiliados:** Integração com Hotmart, Eduzz, Monetizze e ClickBank, provisionando automaticamente o aluno ao receber a confirmação de venda.
- **Portal do Aluno e Plataforma de Cursos (Academy LMS):**
  - Área do aluno com tela de login/cadastro, painel do estudante, acompanhamento de progresso percentual e emissão de certificados automáticos.
  - Leitor de PDF embutido para apostilas e materiais de apoio com modos canvas leve e visualização direta.
- **Flex Builder e Menus Hierárquicos:**
  - Construtor visual de menus com suporte a submenus (arraste horizontal e indentação fácil).
  - Gaveta mobile personalizável com 8 estilos de botão hambúrguer.
  - Suporte a cores de texto e tipografia globais vinculadas a Design Tokens.
- **Menus Hierárquicos com Submenus (`Walker` SOLID + Drag & Drop Estilo WordPress):**
  - **Árvore Recursiva (`MenuTreeBuilderInterface` / `MenuTreeBuilder`):** Transformação de itens planos (`parent_id`) em árvore hierárquica com proteção anti-ciclo e profundidade máxima (`MAX_DEPTH = 5`), além de migração automática de `parent_id` em `PdoMenuRepository`.
  - **Interface Drag & Drop Horizontal (`wpNavMenu`) & Prioridade do Menu Principal (`header`):** Arraste horizontal para criar/remover submenus tanto em `/admin/themes/menus` quanto no modal do Flex Builder, com seleção padrão automática do **Menu Principal (`header`)**.
  - **Estilização de Submenus Desktop e Mobile (`NavMenuBlock`):** Controles visuais para dropdowns desktop (fundo, borda, raio, sombra, largura mínima, seta indicadora e cores hover) e submenus mobile (acordeão expansível ou indentado).
- **Personalização da Gaveta Mobile (`ds-mobile-drawer`), 8 Estilos de Hambúrguer & 7 Formatos de Botões Mobile:**
  - **8 Estilos de Botão Toggle (`MobileToggleStyleInterface`):** `bars`, `bars_staggered`, `dots_vertical`, `dots_grid`, `plus_cross`, `chevron_down`, `pill_label` e `minimal_two_lines`, com 4 formatos de caixa (`rounded`, `square`, `pill`, `ghost`) e cores customizáveis.
  - **7 Formatos de Botões de Menu Mobile:** `flat`, `rounded`, `pill`, `outlined`, `filled_soft`, `left_accent` e `underlined`, além de personalização de largura e cores da janela lateral (`ds-mobile-drawer`).
- **Sistema Global de Cores de Texto e Tipografia (Inspirado em Pro-Elements / Elementor):**
  - Suporte a `text_color`, `heading_color`, `link_color` e `link_hover_color` em nível de Seção, Coluna e Bloco via `ElementStyleSchema` e `ElementStyleRenderer`, integrado aos Tokens Globais (`var(--primary-color)`, `var(--secondary-color)`, `var(--text-color)`, `var(--accent-color)`).
- **Leitor de PDF Híbrido no Academy LMS (`LessonPdfStorageInterface` & Bloco `pdf`):**
  - Suporte a documentos PDF hospedados na nuvem (`https://...`, CDN, S3, Cloudflare R2, Google Drive `/preview`) ou enviados para a pasta local na Hostinger/servidor (`POST /admin/academy/lessons/{id}/upload-pdf` com validação de assinatura mágica `%PDF-`).
  - **3 Modos de Visualização:** Renderização sob demanda por página via **PDF.js Lazy Canvas** (`pdfjs_canvas` — ideal para apostilas pesadas com gráficos e figuras), **Leitor Nativo / Iframe** (`native_embed`) e **Cartão Compacto Expansível** (`card_modal`).
  - **Recorte Cirúrgico de Páginas (`start_page` & `end_page`) e Proteção de Download (`allow_download`):** Permite abrir diretamente na página de um gráfico/diagrama específico, limitar o intervalo de páginas navegáveis e ocultar/bloquear download e menu de contexto.

## [2.5.0] - 2026-10-27
### Added
- **Flex Builder FSE (Full Site Editing, Compilador Único, Validador Estrutural & Grid Auto-Reflow):**
  - **Caminho Único de Compilação (`ThemePartCompilerInterface` & `ThemePartRenderer`):** O serviço de pré-visualização (`ThemePartPreviewService`) delega 100% da compilação de seções, colunas, blocos, estilos e classes responsivas ao `ThemePartCompilerInterface`, garantindo paridade absoluta entre o Studio Ao Vivo e a publicação no Frontend.
  - **Validação Estrutural Pré-Persistência (`ThemePartValidatorInterface` / `ThemePartValidator`):** Validação estrita de tipo da parte (`header`, `footer`, `section`), existência do preset no `LayoutGridInterface`, existência e compatibilidade de cada bloco no `BlockRegistryInterface` (`getAllowedPartTypes()`), tamanho de IDs/títulos e limites de profundidade/quantidade antes de qualquer gravação.
  - **Compensação Transacional nas Associações (`ThemePartManagementService`):** Verificação obrigatória do resultado booleano de `setGlobalDefault()`, `assignPart()` e `assignTemplateToSection()`, com restauração automática do estado anterior em caso de falha parcial.
  - **Visibilidade Responsiva Isolada por Elemento nas 4 Resoluções (`Desktop 100%`, `1024px`, `768px`, `375px`):** As configurações de ocultação atuam exclusivamente sobre o elemento selecionado (`.ds-element-wrapper` com `.ds-hide-desktop`, `.ds-hide-laptop`, `.ds-hide-tablet`, `.ds-hide-mobile`), nunca ocultando o container da seção inteira (`.ds-section-wrapper`).
  - **Reajuste Automático de Dimensões do CSS Grid (Auto-Reflow):** Quando todos os elementos de uma coluna ficam ocultos em uma resolução, a coluna vazia recebe `.ds-hide-{resolução}` e o `LayoutGridRenderer` recalcula automaticamente o `grid-template-columns` para as colunas visíveis restantes (ex.: `1fr 2fr 1fr` -> `2fr 1fr` ou `1fr`).
  - **Segurança Avançada (`SafeHtmlSanitizer`, `MediaUploadService` & `Request::json()`):** Whitelist positiva de esquemas de URL (`http`, `https`, `mailto`, `tel`), validação XML de SVG via `DOMDocument` (`LIBXML_NONET`) contra XXE/XSS, método nativo `Request::json()` / `Request::withJsonBody()` para isolamento de `php://input` e respostas HTTP controladas (`422` para validação e `500` sem vazamento de detalhes internos).

### Fixed
- **Empacotador de Releases (`release/exclude.txt`):** Exclusão de arquivos de desenvolvimento/ambiente (`.env*`, `project.txt`, `content/`, `manual/`, `resources/`) e estado local (`config/theme_parts/`, `config/theme_parts_associations.json`, `config/active_theme.json`, `config/active_skin.json`, `config/*.lock`) para compatibilidade 100% com `CoreProtectedPaths` e `Sha256PackageVerifier` no botão de atualização da Hostinger.
- **Migrations do Academy LMS:** Remoção de `use PDO;` sem namespace nas migrations do Academy para eliminar warnings no `ErrorHandler`.

## [2.4.0] - 2026-10-19
### Added
- **Atualizador Automático via GitHub Releases por Contrato (`SystemUpdater` / `CoreUpdater` — Modelo A):**
  - **25 Contratos em `src/Core/Contracts/Update/` & Auditoria `UpdateArchitectureTest`:** Máquina de estados de 14 etapas (`Checked → Downloaded → Verified → Preflighted → BackedUp → MaintenanceOn → Applied → Migrated → SchemaVerified → HealthChecked → Done`) 100% desacoplada de infraestrutura (`PDO`, `curl_*`, `ZipArchive` e superglobais banidos fora dos adaptadores).
  - **Segurança Criptográfica & Anti-Zip-Slip (`Sha256PackageVerifier`, `ZipPackageExtractor`, `HostAllowlist`):** Validação dupla SHA-256 (do pacote `.zip` e de cada arquivo em `manifest.json`), quarentena isolada, proteção contra *zip bomb*/symlinks e allowlist estrita HTTPS para o GitHub.
  - **Backup Completo, Migrações por Contrato & Auto-Rollback (`ZipDirectoryBackup`, `SqliteDatabaseBackup`, `MysqlDatabaseBackup`, `SchemaPlanner`, `SchemaVerifier`):** Criação automática de ponto de restauração de código e banco antes da aplicação, preservação estrita de `.env`, `config/` e `storage/`, verificação de saúde em duas requisições HTTP (`CoreHealthCheck`) e rollback automático em caso de falha.
  - **Interface Integrada em Configurações Globais (`/admin/settings/updates`), Widget no Painel & Comandos CLI (`os-cli.php`):** Aba dedicada em Configurações Globais, widget `Versão do Sistema` no Dashboard, notificação única por versão no sino e comandos `system:check`, `system:status`, `system:update`, `system:rollback` e `system:health`.
  - **Resiliência Multiplataforma (XAMPP/Windows & Linux):** Fallback inteligente para `\PharData` caso a extensão nativa `ext-zip` não esteja carregada no SAPI ativo (`ZipPackageExtractor`, `ZipDirectoryBackup`, `ExtensionsCheck`) e extensão de tempo limite (`@set_time_limit(300)`) em operações de download e descompactação de pacotes.
  - **Empacotador Determinístico de Releases (`ReleaseBuilder` & `tools/build-release.php`):** Geração automatizada de `dist/domain-system-X.Y.Z.zip` e `.sha256` integrada ao `.github/workflows/release.yml` e padronização de todos os 18 manifestos `plugin.json` oficiais em `2.4.0` (`os_version: 2.4`).

## [2.3.0] - 2026-10-19
### Added
- **Guia Mestre de Contratos para IA ("Vibe Coding on Rails" - `AI_CONTRACT_GUIDE.md`):** Documentação oficial para LLMs na raiz + regras automáticas para IDEs (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`), botão 1-clique **"📋 Copiar Prompt Mestre para IA"** no Manual do Sistema e gerador CLI (`php os-cli.php make:plugin <slug>`) que cria a árvore completa de contratos do Ring 3.
- **Auto-Descoberta de Manuais por Contrato (`ManualModuleInterface` & `PluginManualScanner`):** Qualquer plugin em `src/Plugins/<slug>/manual/module.php` (`academy`, `flextheme`, `pages`, `seo`) que implemente `ManualModuleInterface` agora é descoberto e renderizado automaticamente no menu **Manual de Instruções (`/admin/manual`)** e na versão tela cheia (`/manual/index.php`), incluindo seus itens de checklist de homologação (QA).
- **Academy LMS Híbrido (v2.3.0), Fábrica Automática de `.php` & 3 Perfis Mobile:**
  - **Fábrica Automática de Pastas e Aulas `.php` (`LessonFileScaffolderInterface` / `LessonFileScaffolderService`):** Criação de subpastas de módulos (`createFolder`) e fabricação automática de arquivos `.php` físicos no tema ativo (`themes/<tema>/pages/estrutura-de-dados.php` $\rightarrow$ acessível em `/cursos/estrutura-de-dados`), com proteção contra sobrescrita e contra *Path Traversal*.
  - **4 Modos de Aula & 8 Tipos de Blocos:** Suporte híbrido para **HTML/Markdown Puro** (`raw`), **Editor em Blocos Estruturado** (`blocks` com `heading`, `paragraph`, `code`, `callout`, `video`, `divider`, `image`, `button_cta`), **Aula `.php` Fabricada Automaticamente** (código ou blocos) e **Página Existente do Tema** (`ThemePageResolverInterface`).
  - **3 Perfis de Layout Mobile (`drawer`, `bottom_bar`, `cinema`) & Simulador Multi-Dispositivos:** Alternância entre `🖥️ Desktop` e `📱 Mobile` com 5 viewports padrão (`320×568`, `375×667`, `430×932`, `768×1024`, `1024×768`) e 3 perfis de navegação mobile por aula.
  - **Sanitização Segura (`HtmlSanitizerService`) & Importação (`CourseFolderImporterService`):** Bloqueio de XSS (`<script>`, `on*`, `javascript:`) preservando iframes confiáveis e sincronização inteligente de pastas do disco.
- **Resiliência Avançada do Kernel (Ring 0 100% Agnóstico a Banco e SO):**
  - **Persistência Atômica (`JsonStoreInterface` / `AtomicJsonStore`):** Escrita concorrente segura com `flock(LOCK_EX)` + arquivo temporário + `rename()` atômico em `config/*.json` e `temp/*.json`.
  - **Disjuntor Granular por Rota com Threshold (`recordRouteFailure`):** Isola rotas problemáticas individualmente antes de desarmar o plugin inteiro (threshold padrão de 3 falhas consecutivas).
  - **Schema Doctor (`SchemaDoctorInterface` / `PluginMigrationScanner`):** Diagnóstico e auto-reparo de migrations de plugins com SQL ANSI 100% agnóstico a banco de dados e SO.
  - **Snapshot & Rollback 1-Clique (`StateSnapshotManager`):** Criação e restauração instantânea de pontos de estado no painel `/admin/supervision`.

### Changed
- **Pegada de Disco Ultraleve (~30MB no Linux):** Todo o ecossistema descompactado em servidores Linux ocupa apenas aproximadamente **30MB**.
- **Status Padrão de Novos Cursos:** Alterado de `soon` (*Em Breve*) para `published` (*Publicado*) no modal de criação do Academy para evitar erros 404 acidentais ao visualizar aulas recém-criadas.
- **Roteamento 100% Agnóstico de Ambiente (`public/index.php`):** Remoção de prefixos hardcoded para suportar qualquer subpasta ou domínio raiz automaticamente.

## [2.2.0] - 2026-10-12
### Added
- **Página Padrão "Em Breve" Desacoplada:** O instalador agora inicia com `active_theme = ''` por padrão, exibindo a vitrine "Em Breve" até que o administrador ative explicitamente um tema no Cockpit.
- **Suíte de Testes de Arquitetura e Segurança (`tests/`):** Testes automatizados garantindo ausência de superglobais em Controllers (`NoSuperglobalsInControllersTest`), proteção de rotas Ring 0 e cobertura de plugins.

## [2.1.0] - 2026-10-05
### Added
- **Criptografia Autenticada AES-256-GCM:** Migração de `AES-256-CBC` para `AES-256-GCM` em `src/Core/helpers.php`.
- **Proteção contra Colisão de Rotas (Ring 0 Lock):** Bloqueio de sobrescrita de rotas vitais do Kernel por plugins Ring 3 (`RouteAlreadyRegisteredException`).
- **Hardening de Sessão HTTP & Modo Desenvolvedor do Disjuntor:** Cookies `HttpOnly`, `SameSite=Strict` e bypass seguro de sessão para depuração de plugins desarmados.

## [1.2.0] - 2026-08-30
### Added
- **Cérebro I.A. (AI Hub):** Novo plugin dedicado para gerenciamento de chaves de API (Gemini, ChatGPT, DeepSeek, Claude).
- **Plugin Builder:** Construtor visual de plugins integrado ao Cérebro I.A. para gerar formulários baseados em IA.
- **Gestão sistema (`clinic_pack`):** Novo plugin isolado para organizar o menu "Gestão sistema" sem interferir no Kernel.
- **SessionManager:** Nova classe central para o gerenciamento abstrato e orientado a objetos do estado da memória do usuário (Sessão).

### Changed
- **ACL Centralizada (Global Gatekeeper):** Toda a verificação de permissões foi movida dos Controllers de negócio para as rotas (`Router`), usando uma declaração de roles.
- **Repositórios de Banco de Dados:** Isolamento completo do acesso ao banco de dados via Repository Pattern para Controllers. A dependência do `QueryBuilder` foi totalmente removida da classe base de Plugins (`AbstractPlugin`).

### Fixed
- **PluginManager Circuit Breaker:** Corrigida falha silenciosa onde o método `boot()` de plugins não estava sendo chamado durante a inicialização, além de ajustes no salvamento do `ordered_plugins.json`.
- Correção de injeção de dependência suja e dependência arquitetural no plugin `dev_simulator` após remoção do `queryBuilder()`.

### Removed
- **Acesso direto a Superglobais:** A superglobal `$_SESSION` (e funções impuras como `session_start()`) foi completamente banida dos Controllers.

## [1.1.0] - A Era da Resiliência Arquitetural - 2026-08-24

Esta atualização introduz proteções críticas no núcleo do sistema, além de resolver débitos técnicos identificados por auditoria profunda.

### Adicionado
- **QTA (Automatic Transfer Switch):** Função de Último Suspiro acoplada ao `register_shutdown_function` para interceptar Erros Fatais e Quedas de Memória Absoluta, ejetando automaticamente o plugin responsável antes da morte térmica do PHP.
- **Método genérico de Upsert:** Adicionado `upsert()` ao `QueryBuilder` nativo do banco de dados SQLite, permitindo operações "Insert On Conflict Do Update" de forma orientada a objetos.
- **Rotas de Prontuário administrador:** Novas rotas `GET` e `POST` para `/admin/appointments/record`.

### Modificado
- **Arquitetura 2FA (Princípios SOLID):** Extração total da lógica de envio (E-mail, Aplicativo) do `TwoFactorService` para suas respectivas interfaces e classes provedoras autônomas (Plugs).
- **Controlador de Configurações (SettingsController):** Remoção de código SQL cruzado (PDO) a favor do novo método `upsert()` do QueryBuilder.
- **Caminho de Uploads:** Substituição de caminhos engessados (`dirname(__DIR__, 4)`) pela constante `BASE_PATH`.

### Removido
- Lixo residual e scripts soltos de testes na raiz (`test_plugins.php` e `test_record.php`).
- **Código morto inatingível:** Limpeza de múltiplos comandos `exit;` perdidos após retornos na `ApiController`.
- Simulação de mock hardcoded de administradors (convertido para TODO para futura integração real com WordPress).

### Segurança e Desenvolvimento
- **Dev Simulator Plugin:** Um novo plugin isolado capaz de sequestrar a fiação mestre de e-mails em ambiente de desenvolvimento, interceptando códigos 2FA e gravando-os em `temp/auth-2fa.txt` para prevenir vazamento de dados reais de usuários.
- **Bomb Plugin:** Plugin malicioso (desativado) mantido no projeto como prova viva para testar a ativação dos sistemas Disjuntor V2 e QTA.

---

## [1.0.0] - O Nascimento do Motor Orgânico - 2026-08-23

Lançamento inicial do Domain System.

### Adicionado
- Arquitetura Mestre baseada em Kernel & Plugins.
- Container de Injeção de Dependências Customizado.
- `EventDispatcher` para comunicação desacoplada entre módulos (Hooks).
- Roteador Nativo e Suporte a SQLite.
- Plugins base inaugurais: `auth`, `appointments`, `doctors`, `patients`, `settings`, `triage`, `SystemAdmin`.
- Disjuntor V1 (Circuit Breaker básico) para isolamento de falhas de Exceptions de Plugins.
- Sistema de controle de acesso (ACL) focado em Workspaces (administradors, Recepcionistas, Advogados).
