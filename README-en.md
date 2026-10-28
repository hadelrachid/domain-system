> **ARCHITECTURAL ALIGNMENT / PARADIGM SHIFT (2026-10-07)**
> 
> The Domain System has evolved its conceptual identity. It is a **Web Application Execution Platform** and an **Ecosystem Framework**. 
> It acts primarily as a **Web Page Manager (Advanced CMS)** and a **Service and API Orchestrator**. 
> Although it adopts the *architecture and jargon* of Operating Systems (Micro-Kernel, Protection Rings, Process Isolation, IPC), it is not a bare-metal OS (like Linux/Windows), but rather a **Web Operating Environment** running on top of the PHP/Linux stack.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.5.0-blueviolet?style=for-the-badge" alt="Version 2.5.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Weight-~30MB-orange?style=for-the-badge" alt="Weight">
  <br><br>
  <h1>🛸 Domain System (v2.5)</h1>
  <p><strong>A lightweight, modular, and resilient Web Platform alternative built on SOLID principles and Contracts.</strong></p>
</div>

---

[🇧🇷 Ler em Português](README.md) | [📖 Documentation & Tutorials](https://hadelrachid.github.io/domain-system/) | [🤖 AI Contract Guide](AI_CONTRACT_GUIDE.md) | [🛡️ Architectural Audit](docs/auditoria.md)

## 🧠 What is Domain System?

**Domain System** is not just another off-the-shelf CMS or Framework. It was created as a **lightweight, modular, and resilient alternative** in the web ecosystem, currently occupying only **~30 MB** of unpacked disk space on Linux servers.

Built under the strict rules of **SOLID** and purely utilizing **Dependency Injection (DIP)**, it behaves as a **Web Operating Environment**.
The limit is your imagination:
- The **Core (Micro-Kernel / Ring 0)** provides low-level infrastructure that is 100% database-agnostic and OS-agnostic (Linux, Windows, or macOS): Environment-Agnostic Dynamic Routing (Subfolder or Root), Real-Time Telemetry, Atomic JSON Persistence (`AtomicJsonStore`), Contract-Driven Diagnostics (`SchemaDoctorInterface`), 1-Click Snapshot & Rollback, Capability-based Security (ACL, Anti-CSRF), and Contract-Driven Manual Auto-Discovery (`ManualModuleInterface`).
- All **business logic** and **page/service management** is injected through **Modules (Ring 3)** via strict **Contracts (Interfaces)**. The kernel never knows what the application does or which database engine is used; it merely orchestrates the contracts!

## 🚀 Key Features

### 🛡️ 1. Ring 0 / Ring 3 Architectural Governance & Fault Isolation (DI Boundary)
Inspired by layered Operating System design, the platform organizes its modules into two architectural tiers within the PHP runtime:
- **SystemApps (`src/SystemApps` — Ring 0):** Essential infrastructure modules (Database, Auth, Admin, SystemMonitor, SystemUpdater). They receive priority in the topological boot queue and **are never auto-disabled** by the Circuit Breaker during transient failures.
- **UserPlugins (`src/Plugins` — Ring 3):** Domain modules and extensions that interact with the core through the `OsRuntime` mediator (which enforces dependencies and hooks declared during `osRegister()` before resolving DI Container services) and are subject to **automatic quarantine by the Circuit Breaker** if they throw fatal errors during boot or route execution. *(Technical note: because all PHP code executes within the same Zend process, this separation provides architectural dependency governance and fault isolation rather than an OS-level process sandbox — only install plugins from trusted sources, just like Composer packages or WordPress plugins).*

### 🛡️ 2. Gatekeeper & Safe Quarantine Extraction (`ZipArchiveExtractor`)
Strict structural validation when uploading plugin and theme `.zip` archives: the extractor blocks *Zip-Slip* vectors (relative traversal `../`, Unix/Windows absolute paths `/`, `\`, `C:`, stream wrappers `phar://`, and UNIX *symlinks*), performs stream-based extraction into a `realpath()`-verified quarantine directory, and prevents namespace collisions with `DomainSystem\SystemApps` or overwriting protected `core` modules.

### 🔋 3. No-Break Shield, Atomic Persistence & 1-Click Snapshots
Forget the "White Screen of Death" (WSOD). Domain System runs with the **No-Break Shield**: a centralized *Circuit Breaker* (at boot and per-route with configurable failure threshold) backed by `AtomicJsonStore` (`LOCK_EX` + atomic rename), **Schema Doctor** (`SchemaDoctorInterface`), and **1-Click Snapshot & Rollback** in `/admin/monitor`.

### 🏗️ 4. Flex Builder FSE (Full Site Editing, 14 Blocks, 4 Resolutions & Automatic CSS Grid Reflow)
The ecosystem includes **Flex Builder (`/admin/builder`)** engineered on a 100% SOLID architecture (`src/Core/Theme/Parts`):
- **Unified Compiler (`ThemePartCompilerInterface`) & Structural Validator (`ThemePartValidatorInterface`):** Both the Studio Live Preview and the published Frontend delegate to a single compilation engine, backed by pre-save validation of part types, presets, block compatibility, and transactional association compensation.
- **Per-Element Responsive Visibility (4 Resolutions) + Auto Grid Reflow:** Hide individual elements across **Desktop (`100%`)**, **Laptop (`1024px`)**, **Tablet (`768px`)**, and **Mobile (`375px`)** without ever hiding the entire section container. When all elements in a column are hidden at a given breakpoint, `LayoutGridRenderer` collapses the empty column and automatically recalculates `grid-template-columns` for the remaining visible columns (`1fr 2fr 1fr` $\rightarrow$ `2fr 1fr` or `1fr`).
- **14 Semantic Blocks, Global Design Tokens & Direct Template Binding:** Compose *Headers*, *Footers*, and *Central Sections* bound directly to theme templates (`home.php`, `sobre.php`, etc.) or embedded via `[section id="..."]`, with 100% safe fallback to native `partials/header.php` and `partials/footer.php`. Learn more in [wiki/Flex-Builder.md](wiki/Flex-Builder.md).

### 🎓 5. Hybrid Academy LMS, Automatic PHP File Factory & Multi-Device Simulator
The ecosystem includes **Academy (Courses)** built on a 100% SOLID architecture:
- **Automatic Theme Folder & PHP Lesson Factory (`LessonFileScaffolderInterface`):** Create physical module folders (`📁 Create Folder`) and automatically fabricate pre-configured `.php` lesson files (with responsive Desktop, Tablet, and Mobile CSS/JS) directly from the lesson Title. For example, saving a lesson titled **"Estrutura de Dados"** automatically generates `pages/estrutura-de-dados.php` and exposes the clean route `/cursos/estrutura-de-dados`.
- **Hybrid Lesson Modes:** Supports a **Structured 8-Block Visual Editor** (`header`, `paragraph`, `video`, `code`, `callout`, `list`, `image`, `delimiter`), **Fabricated/Theme PHP Files** (`ThemePageResolverInterface`), and **1-Click Theme Folder Import**.
- **Multi-Device Simulator & 3 Mobile Layout Profiles:** Switch between `🖥️ Desktop` and `📱 Mobile` across **5 standard device presets** (`320×568`, `375×667`, `430×932`, `768×1024`, `1024×768`) and choose among **3 Mobile Layout Profiles**: **Off-Canvas Drawer (`drawer`)**, **Fixed Bottom App Bar (`bottom_bar`)**, and **Sticky 16:9 Cinema Mode (`cinema`)**.

### 🤖 6. "Vibe Coding on Rails" (Official AI Developer Experience)
Domain System is engineered for AI-assisted development without allowing spaghetti code:
- **Master AI Contract Guide ([`AI_CONTRACT_GUIDE.md`](AI_CONTRACT_GUIDE.md))** + native IDE rules (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`): any AI (ChatGPT, Claude, DeepSeek, Gemini, Copilot, Cursor, Kilo Code) reads the **10 Non-Negotiable Laws of Ring 3** and generates clean, contract-compliant code.
- **CLI Boilerplate Scaffolder (`os-cli.php make:plugin <name>`):** Scaffolds the full Ring 3 contract structure (`Plugin.php`, `Contracts/`, `Controllers/`, `views/`, `manual/module.php`) in seconds.

### 📖 7. Contract-Driven Manual Auto-Discovery (`ManualModuleInterface`)
Any plugin providing `src/Plugins/<slug>/manual/module.php` implementing `ManualModuleInterface` is automatically discovered and rendered by `PluginManualScanner` inside the **Cockpit Instruction Manual & QA (`/admin/manual` and `/manual`)**, injecting interactive documentation and QA checklist items with zero Kernel coupling.

### 🎨 8. Content Manager, SEO & Anti-CSRF / Capability Security
Manage web pages (CMS), Atomic Dynamic Menus, OpenGraph/Schema.org SEO, and **Capability Security (ACL)** with native Anti-CSRF, Anti-XSS (positive URL scheme whitelist and `DOMDocument` SVG XML sanitization), and 2FA protection.

### 🔄 9. Contract-Driven GitHub Releases Self-Updater (`SystemUpdater` — Model A)
Automatic new-version notifications (Global Settings tab, dashboard widget, and notification bell), streaming download with dual SHA-256 + `manifest.json` verification, preflight checks, automatic code and database backups (MySQL/SQLite with intelligent `\PharData` fallback for Windows/XAMPP), atomic file replacement preserving `.env`/`config/`/`storage/`, contract-verified additive migrations (`SchemaVerifier`), and automatic rollback via UI or CLI (`php os-cli.php system:update`). Learn more in [wiki/Atualizacoes.md](wiki/Atualizacoes.md).

## ⚙️ How to Install

1. **Clone the repository** into the public folder of your Apache/Nginx server (e.g., `htdocs` or `www`):
   ```bash
   git clone https://github.com/hadelrachid/domain-system.git
   ```

2. **Permissions (Linux/Mac):** Ensure the folder has write permissions for the web server.
   ```bash
   chmod -R 755 domain-system
   ```

3. **Start the Installer:** Access the project folder via your browser (e.g., `http://localhost/domain-system`). You will be redirected to the **Setup Wizard**.

4. **Follow the Steps:** Enter your database details (MySQL/MariaDB or SQLite via PDO). Create your Master user, and you're done. The Setup will self-destruct after configuration for security reasons.

## 👨‍💻 For Developers & AI Agents (SDK)

The system features an **Event-Driven Hooks** and **Contracts (`src/Core/Contracts`)** engine verified by **433 automated tests (2,340+ assertions)** in PHPUnit.
To build your own module (manually or with AI), refer to:
- [Plugin Development Guide (Humans)](DEVELOPER_GUIDE.md)
- [Official AI Contract Guide (`AI_CONTRACT_GUIDE.md`)](AI_CONTRACT_GUIDE.md)
