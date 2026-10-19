# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---
[ 🇧🇷 Ler em Português ](CHANGELOG.md)

## [2.4.0] - 2026-10-19
### Added
- **Contract-Driven GitHub Releases Self-Updater (`SystemUpdater` / `CoreUpdater` — Model A):**
  - **25 Contracts in `src/Core/Contracts/Update/` & `UpdateArchitectureTest` Guardrail:** 14-step resumable state machine (`Checked → Downloaded → Verified → Preflighted → BackedUp → MaintenanceOn → Applied → Migrated → SchemaVerified → HealthChecked → Done`) with zero direct `PDO`, `curl_*`, `ZipArchive`, or superglobal usage in services or controllers.
  - **Cryptographic Verification & Anti-Zip-Slip (`Sha256PackageVerifier`, `ZipPackageExtractor`, `HostAllowlist`):** Dual SHA-256 verification (archive + per-file `manifest.json`), isolated quarantine extraction, zip-bomb/symlink defense, and strict HTTPS GitHub host allowlist.
  - **Full Code/Database Backup, Contract Schema Verification & Auto-Rollback (`ZipDirectoryBackup`, `SqliteDatabaseBackup`, `MysqlDatabaseBackup`, `SchemaPlanner`, `SchemaVerifier`):** Automatic pre-apply backups, strict protection for `.env`, `config/`, and `storage/`, two-phase HTTP health check (`CoreHealthCheck`), and automatic rollback on failure.
  - **Global Settings Integration (`/admin/settings/updates`), Dashboard Widget & CLI (`os-cli.php`):** Integrated Updates tab in Global Settings, `System Version` dashboard widget, once-per-version notification, and CLI commands (`system:check`, `system:status`, `system:update`, `system:rollback`, `system:health`).
  - **Multiplatform Resilience (XAMPP/Windows & Linux):** Intelligent fallback to `\PharData` when native `ext-zip` is not loaded in the active PHP SAPI (`ZipPackageExtractor`, `ZipDirectoryBackup`, `ExtensionsCheck`) and execution time limit extension (`@set_time_limit(300)`) during package download and extraction stages.
  - **Deterministic Release Builder (`ReleaseBuilder` & `tools/build-release.php`):** Automated generation of `dist/domain-system-X.Y.Z.zip` and `.sha256` integrated into `.github/workflows/release.yml`, plus standardization of all 18 official `plugin.json` manifests to `2.4.0` (`os_version: 2.4`).

## [2.3.0] - 2026-10-19
### Added
- **Master AI Contract Guide ("Vibe Coding on Rails" - `AI_CONTRACT_GUIDE.md`):** Official root guide for LLMs + automatic IDE rule files (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`), a 1-click **"📋 Copy Master Prompt for AI"** button in the System Manual, and an upgraded CLI generator (`php os-cli.php make:plugin <slug>`) that scaffolds the complete Ring 3 contract directory tree.
- **Contract-Driven Manual Auto-Discovery (`ManualModuleInterface` & `PluginManualScanner`):** Any plugin providing `src/Plugins/<slug>/manual/module.php` (`academy`, `flextheme`, `pages`, `seo`) implementing `ManualModuleInterface` is now automatically discovered and rendered inside both the Cockpit's **Instruction Manual (`/admin/manual`)** and the fullscreen viewer (`/manual/index.php`), including its QA homologation checklist items.
- **Hybrid Academy LMS (v2.3.0), Automatic `.php` Lesson Factory & 3 Mobile Profiles:**
  - **Automatic Folder & `.php` Lesson Factory (`LessonFileScaffolderInterface` / `LessonFileScaffolderService`):** Module folder creation (`createFolder`) and automatic fabrication of physical `.php` files inside the active theme (`themes/<theme>/pages/estrutura-de-dados.php` $\rightarrow$ routed at `/cursos/estrutura-de-dados`), with overwrite and path-traversal protection.
  - **4 Lesson Modes & 8 Block Types:** Hybrid support for **Raw HTML/Markdown** (`raw`), **Structured Block Editor** (`blocks` supporting `heading`, `paragraph`, `code`, `callout`, `video`, `divider`, `image`, `button_cta`), **Auto-Fabricated `.php` Lessons** (code or blocks), and **Existing Theme Pages** (`ThemePageResolverInterface`).
  - **3 Mobile Layout Profiles (`drawer`, `bottom_bar`, `cinema`) & Multi-Device Simulator:** Switch between `🖥️ Desktop` and `📱 Mobile` across 5 standard viewports (`320×568`, `375×667`, `430×932`, `768×1024`, `1024×768`) and 3 per-lesson mobile layout profiles.
  - **Safe Sanitization (`HtmlSanitizerService`) & Folder Import (`CourseFolderImporterService`):** Strips XSS vectors (`<script>`, `on*`, `javascript:`) while preserving trusted video iframes and smart disk synchronization.
- **Advanced Kernel Resilience (100% DB- and OS-Agnostic Ring 0):**
  - **Atomic Persistence (`JsonStoreInterface` / `AtomicJsonStore`):** Concurrency-safe JSON read/write with `flock(LOCK_EX)` + temporary file + atomic `rename()` across `config/*.json` and `temp/*.json`.
  - **Route-Level Circuit Breaker with Threshold (`recordRouteFailure`):** Isolates failing routes individually before tripping the entire plugin (default threshold: 3 consecutive failures).
  - **Schema Doctor (`SchemaDoctorInterface` / `PluginMigrationScanner`):** Automatic discovery and self-healing of plugin migrations using 100% database- and OS-agnostic ANSI SQL.
  - **1-Click Snapshot & Rollback (`StateSnapshotManager`):** Instant creation and restoration of system state restore points inside `/admin/supervision`.

### Changed
- **Ultra-Light Disk Footprint (~30MB on Linux):** The entire unpacked platform folder on Linux servers occupies approximately **30MB**.
- **Default New Course Status:** Changed from `soon` to `published` in the Academy creation modal to prevent accidental 404s when viewing newly created lessons.
- **100% Environment-Agnostic Routing (`public/index.php`):** Removed hardcoded subfolder prefixes so the platform runs seamlessly in any subdirectory or root domain.

## [2.2.0] - 2026-10-12
### Added
- **Decoupled Default "Coming Soon" Landing Page:** Fresh installations now start with `active_theme = ''`, displaying the default Coming Soon screen until the administrator explicitly activates a theme in the Cockpit.
- **Architecture & Security Test Suite (`tests/`):** Automated PHPUnit tests enforcing zero superglobals in Controllers (`NoSuperglobalsInControllersTest`), Ring 0 route protection, and plugin coverage.

## [2.1.0] - 2026-10-05
### Added
- **AES-256-GCM Authenticated Encryption:** Migrated from `AES-256-CBC` to `AES-256-GCM` in `src/Core/helpers.php`.
- **Ring 0 Route Collision Protection:** Prevented Ring 3 plugins from hijacking core Kernel routes (`RouteAlreadyRegisteredException`).
- **HTTP Session Hardening & Circuit Breaker Developer Mode:** `HttpOnly`, `SameSite=Strict` cookies and session-scoped safe bypass for debugging tripped plugins.

## [1.2.0] - 2026-08-30
### Added
- **AI Hub:** New dedicated plugin for API keys management (Gemini, ChatGPT, DeepSeek, Claude).
- **Plugin Builder:** Visual plugin builder integrated with AI Hub to generate AI-based forms.
- **Clinic Pack (`clinic_pack`):** New isolated plugin to organize the "Clinical Management" menu without interfering with the Kernel.
- **SessionManager:** New central class for abstract, object-oriented management of user memory state (Session).

### Changed
- **Centralized ACL (Global Gatekeeper):** All permission verification was moved from business Controllers to the routes (`Router`), using role declarations.
- **Database Repositories:** Complete isolation of database access via the Repository Pattern for Controllers. `QueryBuilder` dependency was fully removed from the base Plugin class (`AbstractPlugin`).

### Fixed
- **PluginManager Circuit Breaker:** Fixed silent failure where plugin `boot()` method was not being called during initialization, plus fixes for `ordered_plugins.json` saving.
- Fixed dirty dependency injection and architectural coupling in the `dev_simulator` plugin after removing `queryBuilder()`.

### Removed
- **Direct Access to Superglobals:** The `$_SESSION` superglobal (and impure functions like `session_start()`) has been completely banned from Controllers.

## [1.1.0] - The Era of Architectural Resilience - 2026-08-24

This update introduces critical protections to the system's core, along with resolving technical debts identified by a deep audit.

### Added
- **ATS (Automatic Transfer Switch):** A Last Breath function hooked to `register_shutdown_function` to intercept Fatal Errors and Absolute Memory Exhaustion, automatically ejecting the responsible plugin before PHP's thermal death.
- **Generic Upsert Method:** Added `upsert()` to the native SQLite `QueryBuilder`, allowing Object-Oriented "Insert On Conflict Do Update" operations.
- **Medical Record Routes:** New `GET` and `POST` routes for `/admin/appointments/record`.

### Changed
- **2FA Architecture (SOLID Principles):** Total extraction of the sending logic (Email, App) from `TwoFactorService` to their respective interfaces and autonomous provider classes (Plugs).
- **Settings Controller (`SettingsController`):** Removed raw SQL code (PDO) in favor of the new `upsert()` method from QueryBuilder.
- **Uploads Path:** Replaced hardcoded paths (`dirname(__DIR__, 4)`) with the `BASE_PATH` constant.

### Removed
- Residual garbage and loose testing scripts in the root (`test_plugins.php` and `test_record.php`).
- **Unreachable dead code:** Cleaned multiple stray `exit;` commands after returns in `ApiController`.
- Hardcoded doctor mock simulation (converted to TODO for future real integration with WordPress).

### Security and Development
- **Dev Simulator Plugin:** A new isolated plugin capable of hijacking the master email wiring in the development environment, intercepting 2FA codes and writing them to `temp/auth-2fa.txt` to prevent real user data leakage.
- **Bomb Plugin:** Malicious plugin (disabled) kept in the project as living proof to test the activation of the Circuit Breaker V2 and ATS systems.

---

## [1.0.0] - The Birth of the Organic Engine - 2026-08-23

Initial release of the Domain System.

### Added
- Master Architecture based on Kernel & Plugins.
- Custom Dependency Injection Container.
- `EventDispatcher` for decoupled communication between modules (Hooks).
- Native Router and SQLite Support.
- Inaugural base plugins: `auth`, `appointments`, `doctors`, `patients`, `settings`, `triage`, `SystemAdmin`.
- Circuit Breaker V1 (Basic) for isolating Plugin Exception failures.
- Access Control List (ACL) system focused on Workspaces (Doctors, Receptionists, Lawyers).
