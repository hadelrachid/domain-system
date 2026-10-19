> **ARCHITECTURAL ALIGNMENT / PARADIGM SHIFT (2026-10-07)**
> 
> The Domain System has evolved its conceptual identity. It is a **Web Application Execution Platform** and an **Ecosystem Framework**. 
> It acts primarily as a **Web Page Manager (Advanced CMS)** and a **Service and API Orchestrator**. 
> Although it adopts the *architecture and jargon* of Operating Systems (Micro-Kernel, Protection Rings, Process Isolation, IPC), it is not a bare-metal OS (like Linux/Windows), but rather a **Web Operating Environment** running on top of the PHP/Linux stack.

<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.4.0-blueviolet?style=for-the-badge" alt="Version 2.4.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Weight-~30MB-orange?style=for-the-badge" alt="Weight">
  <br><br>
  <h1>🛸 Domain System (v2.4)</h1>
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

### 🛡️ 1. Ring 0 / Ring 3 Architecture (Isolated Privileges)
Inspired by real Operating System security, the system isolates its extensions into two privilege rings. **SystemApps (Ring 0)** are vital system components (e.g., Database, Identity/Auth, Admin Panel, SystemMonitor) that manage essential services. **UserPlugins (Ring 3)** are third-party additions running with limited privileges and are prevented from injecting or hijacking core instances.

### 🛡️ 2. Gatekeeper (Installation Guardian)
Total protection against malicious ZIPs. The installer does not blindly trust uploads; it extracts the package into a quarantine area, scans the source code (looking for Path Traversal/Zip-Slip vulnerabilities), and prevents any Privilege Escalation attempt (e.g., plugins trying to usurp the Ring 0 namespace).

### 🔋 3. No-Break Shield, Atomic Persistence & 1-Click Snapshots
Forget the "White Screen of Death" (WSOD). Domain System runs with the **No-Break Shield**: a centralized *Circuit Breaker* (at boot and per-route with configurable failure threshold) backed by `AtomicJsonStore` (`LOCK_EX` + atomic rename), **Schema Doctor** (`SchemaDoctorInterface`), and **1-Click Snapshot & Rollback** in `/admin/monitor`.

### 🎓 4. Hybrid Academy LMS, Automatic PHP File Factory & Multi-Device Simulator
The ecosystem includes **Academy (Courses)** built on a 100% SOLID architecture:
- **Automatic Theme Folder & PHP Lesson Factory (`LessonFileScaffolderInterface`):** Create physical module folders (`📁 Create Folder`) and automatically fabricate pre-configured `.php` lesson files (with responsive Desktop, Tablet, and Mobile CSS/JS) directly from the lesson Title. For example, saving a lesson titled **"Estrutura de Dados"** automatically generates `pages/estrutura-de-dados.php` and exposes the clean route `/cursos/estrutura-de-dados`.
- **Hybrid Lesson Modes:** Supports a **Structured 8-Block Visual Editor** (`header`, `paragraph`, `video`, `code`, `callout`, `list`, `image`, `delimiter`), **Fabricated/Theme PHP Files** (`ThemePageResolverInterface`), and **1-Click Theme Folder Import**.
- **Multi-Device Simulator & 3 Mobile Layout Profiles:** Switch between `🖥️ Desktop` and `📱 Mobile` across **5 standard device presets** (`320×568`, `375×667`, `430×932`, `768×1024`, `1024×768`) and choose among **3 Mobile Layout Profiles**: **Off-Canvas Drawer (`drawer`)**, **Fixed Bottom App Bar (`bottom_bar`)**, and **Sticky 16:9 Cinema Mode (`cinema`)**.

### 🤖 5. "Vibe Coding on Rails" (Official AI Developer Experience)
Domain System is engineered for AI-assisted development without allowing spaghetti code:
- **Master AI Contract Guide ([`AI_CONTRACT_GUIDE.md`](AI_CONTRACT_GUIDE.md))** + native IDE rules (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`): any AI (ChatGPT, Claude, DeepSeek, Gemini, Copilot, Cursor, Kilo Code) reads the **10 Non-Negotiable Laws of Ring 3** and generates clean, contract-compliant code.
- **CLI Boilerplate Scaffolder (`os-cli.php make:plugin <name>`):** Scaffolds the full Ring 3 contract structure (`Plugin.php`, `Contracts/`, `Controllers/`, `views/`, `manual/module.php`) in seconds.

### 📖 6. Contract-Driven Manual Auto-Discovery (`ManualModuleInterface`)
Any plugin providing `src/Plugins/<slug>/manual/module.php` implementing `ManualModuleInterface` is automatically discovered and rendered by `PluginManualScanner` inside the **Cockpit Instruction Manual & QA (`/admin/manual` and `/manual`)**, injecting interactive documentation and QA checklist items with zero Kernel coupling.

### 🎨 7. Content Manager, SEO & Anti-CSRF / Capability Security
Manage web pages (CMS), Atomic Dynamic Menus, OpenGraph/Schema.org SEO, and **Capability Security (ACL)** with native Anti-CSRF, Anti-XSS, and 2FA protection.

### 🔄 8. Contract-Driven GitHub Releases Self-Updater (`SystemUpdater` — Model A)
Automatic new-version notifications (Global Settings tab, dashboard widget, and notification bell), streaming download with dual SHA-256 + `manifest.json` verification, preflight checks, automatic code and database backups, atomic file replacement preserving `.env`/`config/`/`storage/`, contract-verified additive migrations (`SchemaVerifier`), and automatic rollback via UI or CLI (`php os-cli.php system:update`).

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

The system features an **Event-Driven Hooks** and **Contracts (`src/Core/Contracts`)** engine verified by **309 automated tests (1,400 assertions)** in PHPUnit.
To build your own module (manually or with AI), refer to:
- [Plugin Development Guide (Humans)](DEVELOPER_GUIDE.md)
- [Official AI Contract Guide (`AI_CONTRACT_GUIDE.md`)](AI_CONTRACT_GUIDE.md)
