# Histórico de Versões (Changelog)

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/), 
e este projeto adota o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---
[ 🇺🇸 Read in English ](CHANGELOG-en.md)

## [Unreleased]
### Added
- **Atualizador Automático via GitHub Releases por Contrato (`SystemUpdater` / `CoreUpdater` — Modelo A):**
  - **25 Contratos em `src/Core/Contracts/Update/` & Auditoria `UpdateArchitectureTest`:** Máquina de estados de 14 etapas (`Checked → Downloaded → Verified → Preflighted → BackedUp → MaintenanceOn → Applied → Migrated → SchemaVerified → HealthChecked → Done`) 100% desacoplada de infraestrutura (`PDO`, `curl_*`, `ZipArchive` e superglobais banidos fora dos adaptadores).
  - **Segurança Criptográfica & Anti-Zip-Slip (`Sha256PackageVerifier`, `ZipPackageExtractor`, `HostAllowlist`):** Validação dupla SHA-256 (do pacote `.zip` e de cada arquivo em `manifest.json`), quarentena isolada, proteção contra *zip bomb*/symlinks e allowlist estrita HTTPS para o GitHub.
  - **Backup Completo, Migrações por Contrato & Auto-Rollback (`ZipDirectoryBackup`, `SqliteDatabaseBackup`, `MysqlDatabaseBackup`, `SchemaPlanner`, `SchemaVerifier`):** Criação automática de ponto de restauração de código e banco antes da aplicação, preservação estrita de `.env`, `config/` e `storage/`, verificação de saúde em duas requisições HTTP (`CoreHealthCheck`) e rollback automático em caso de falha.
  - **Interface Integrada em Configurações Globais (`/admin/settings/updates`), Widget no Painel & Comandos CLI (`os-cli.php`):** Aba dedicada em Configurações Globais, widget `Versão do Sistema` no Dashboard, notificação única por versão no sino e comandos `system:check`, `system:status`, `system:update`, `system:rollback` e `system:health`.
  - **Empacotador Determinístico de Releases (`ReleaseBuilder` & `tools/build-release.php`):** Geração automatizada de `dist/domain-system-X.Y.Z.zip` e `.sha256` integrada ao `.github/workflows/release.yml` e padronização de todos os 17 manifestos `plugin.json` oficiais em `2.3.0`.

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
