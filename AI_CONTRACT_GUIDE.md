# 🤖 DOMAIN-SYSTEM OS (v2.5) — AI CONTRACT & ARCHITECTURE GUIDE ("Vibe Coding on Rails")

> **PURPOSE OF THIS DOCUMENT FOR AI ASSISTANTS (ChatGPT, Claude, DeepSeek, Gemini, GitHub Copilot, Cursor, Cline, Kilo Code):**
> You are generating or refactoring code for **Domain-System OS**, a micro-kernel Web Operating Environment in PHP 8.2+ built on strict **SOLID**, **Dependency Inversion (DIP)**, **Ring 0 / Ring 3 privilege isolation**, and **Contract-Driven Architecture**.
> Any code you generate **MUST** obey the rules and contracts below. Spaghetti code, inline SQL/HTML inside controllers, direct superglobals (`$_POST`, `$_GET`, `$_SESSION`), or PSR-4 case mismatches will be rejected by the automated Architecture Guardrail Tests and the Kernel Gatekeeper.

---

## 🏛️ 1. THE 10 NON-NEGOTIABLE LAWS OF DOMAIN-SYSTEM

1. **`declare(strict_types=1);` in Every PHP File**: Every class, contract, controller, service, and repository must start with `declare(strict_types=1);`.
2. **Strict Ring 0 vs. Ring 3 Separation**:
   - **Ring 0 (`DomainSystem\Core\*` & `DomainSystem\SystemApps\*`)**: Kernel, DI Container, Router, Security, Gatekeeper, Circuit Breaker, SystemMonitor. **User plugins MUST NEVER modify or use `DomainSystem\Core` as their own namespace.**
   - **Ring 3 (`DomainSystem\Plugins\<plugin_slug>\*`)**: All third-party/user plugins live in `src/Plugins/<plugin_slug>/`.
   - **OS Agnosticism**: The OS (Ring 0) does not know about specific databases (MySQL, SQLite, PostgreSQL) or operating systems (Windows, Linux, macOS). All persistence or external capabilities are consumed strictly via **Contracts (Interfaces)** injected by the DI Container.
3. **Zero Superglobals in Controllers or Services**:
   - **FORBIDDEN**: `$_GET`, `$_POST`, `$_SESSION`, `$_FILES`, `$_SERVER`, `$_COOKIE`.
   - **REQUIRED**: Always use the injected `DomainSystem\Core\Http\Request` (`$request->input('key')`, `$request->query('key')`, `$request->file('key')`) and `DomainSystem\Core\Contracts\SessionManagerInterface`.
4. **Zero `new ConcreteService()` Inside Controllers & Zero Dead Dependencies**:
   - Controllers must receive their dependencies via **Constructor Dependency Injection** typed against interfaces in `Contracts/`.
   - Never inject a dependency into a constructor if it is not used by the class.
5. **Strict MVC View Separation (Zero Inline HTML in Controllers)**:
   - Controllers must never concatenate large HTML blocks. Render templates from `src/Plugins/<plugin_slug>/views/*.php` and return a `ViewResponse` or `ResponseInterface`.
6. **XSS & Path Traversal Hardening**:
   - Escape all dynamic outputs in views with `htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8')`.
   - Never use inline `onsubmit="return confirm('... addslashes ...')"`. Use `data-confirm-*` attributes + JavaScript `addEventListener`.
   - Any file path resolution inside themes or storage must verify bounds using `realpath()` + prefix validation.
7. **Contract-Driven Persistence (Database Agnostic)**:
   - Repositories must accept `DatabaseConnectionInterface|PDO` and implement a repository contract (`Contracts/*RepositoryInterface.php`).
   - Database schemas belong in `src/Plugins/<plugin_slug>/migrations/YYYY_MM_DD_NNNNNN_name.php` returning `['up' => fn(PDO $db) => ..., 'down' => fn(PDO $db) => ...]`. Use standard ANSI SQL so migrations work identically on SQLite, MySQL/MariaDB, and PostgreSQL across Linux, Windows, and macOS.
8. **Auto-Discovered Plugin Manual (`ManualModuleInterface`)**:
   - Every plugin should include `src/Plugins/<plugin_slug>/manual/module.php` returning an instance of `DomainSystem\Core\Contracts\ManualModuleInterface`. The Cockpit's `PluginManualScanner` auto-discovers it and injects it into `/admin/manual`.
9. **Exact PSR-4 Case Sensitivity (Linux/Windows/macOS Compatibility)**:
   - If the folder is `src/Plugins/my_plugin/Controllers/ItemController.php`, the namespace **MUST** be `namespace DomainSystem\Plugins\my_plugin\Controllers;` (exact lowercase/uppercase match).
10. **Atomic State & Fault Tolerance**:
    - Never use raw `file_put_contents` on shared JSON state files without locking; use `DomainSystem\Core\Contracts\JsonStoreInterface` (`AtomicJsonStore`).

---

## 📂 2. STANDARD RING 3 PLUGIN DIRECTORY STRUCTURE

When asked to create a plugin named `<slug>` (e.g., `blog` or `store`), always generate this exact structure:

```text
src/Plugins/<slug>/
├── plugin.json                        # Plugin manifest (name, version, author, description, os_version: "2.4")
├── Plugin.php                         # Implements OsExtensionInterface (osRegister & osBoot)
├── Contracts/
│   └── <PascalSlug>RepositoryInterface.php
├── Repositories/
│   └── Pdo<PascalSlug>Repository.php  # Implements <PascalSlug>RepositoryInterface
├── Services/
│   └── <PascalSlug>Service.php        # Pure business logic
├── Controllers/
│   ├── <PascalSlug>AdminController.php
│   └── <PascalSlug>FrontController.php
├── views/
│   ├── admin/
│   │   └── index.php
│   └── front/
│       └── index.php
├── migrations/
│   └── 2026_10_19_000001_create_<slug>_table.php
└── manual/
    └── module.php                     # Returns ManualModuleInterface instance for /admin/manual
```

---

## 🧩 3. CANONICAL CODE TEMPLATES (COPY & ADAPT)

### 3.1. `plugin.json`
```json
{
    "name": "my_plugin",
    "version": "1.0.0",
    "author": "Developer",
    "description": "Clean Ring 3 module following Domain-System SOLID contracts.",
    "core": false,
    "os_version": "2.4"
}
```

### 3.2. `Plugin.php` (`OsExtensionInterface`)
```php
<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\my_plugin;

use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Plugins\my_plugin\Contracts\MyPluginRepositoryInterface;
use DomainSystem\Plugins\my_plugin\Controllers\MyPluginAdminController;
use DomainSystem\Plugins\my_plugin\Repositories\PdoMyPluginRepository;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->singleton(MyPluginRepositoryInterface::class, function () use ($runtime) {
            return new PdoMyPluginRepository($runtime->getLink('core.db'));
        });

        $runtime->onHook('admin.menu', function (array $menus, string $role = 'guest'): array {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Meu Plugin',
                    'url'   => '/admin/my-plugin',
                    'icon'  => '🧩',
                ];
            }
            return $menus;
        });

        $runtime->onHook('router.register', function (Router $router): void {
            $router->addRoute('GET', '/admin/my-plugin', [MyPluginAdminController::class, 'index'], 'my_plugin', ['admin']);
        });
    }
}
```

### 3.3. `manual/module.php` (`ManualModuleInterface`)
```php
<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string { return 'my_plugin'; }
    public function getTitle(): string { return 'Meu Plugin'; }
    public function getSubtitle(): string { return 'Guia de uso e validação homologado via ManualModuleInterface.'; }
    public function getBadge(): string { return 'Módulo Auto-Descoberto'; }
    public function getAccentColor(): string { return '#06b6d4'; }
    public function getAdminUrl(): ?string { return '/admin/my-plugin'; }

    public function getChecklistItems(): array
    {
        return [
            ['id' => 'my-plugin-boot', 'label' => 'Acessar /admin/my-plugin e validar renderização sem erros.'],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        return '<p>Documentação oficial auto-descoberta pelo Cockpit.</p>';
    }
};
```

---

## 🎨 4. CREATING THEME PAGES & ACADEMY LESSONS (`LessonFileScaffolderInterface`)

- Theme pages live in `themes/<active_theme>/pages/` (or `src/Plugins/flextheme/themes/<active_theme>/pages/`).
- In the **Academy Plugin**, lessons can be created via the **Block Editor** (`content`), linked to a theme `.php` file (`theme_file`), or **automatically fabricated** via `LessonFileScaffolderInterface`:
  - Creating a folder/module via `"📁 Criar Pasta (ou Módulo)"` creates `pages/<folder-slug>/`.
  - Saving a lesson titled `"Estrutura de Dados"` with PHP fabrication enabled automatically generates `pages/estrutura-de-dados.php` (pre-configured with responsive Desktop/Tablet/Mobile CSS & JS and embedded `DS_BLOCKS_B64` block state) and exposes the public route `/cursos/estrutura-de-dados`.
  - Academy lessons support 3 Mobile Layout Profiles: `drawer` (Off-Canvas Drawer), `bottom_bar` (Fixed Bottom App Bar), and `cinema` (Sticky 16:9 Video Cinema Mode).

---

## 🔄 5. RELEASES & MIGRATIONS (SELF-UPDATER CONTRACT RULES)

1. **Additive Migrations Only (`type: additive`)**: Within the same major version (`2.x`), migrations in `src/Core/Database/migrations/` and `src/Plugins/<slug>/migrations/` must only add tables or columns (`CREATE TABLE IF NOT EXISTS`, `ALTER TABLE ADD COLUMN`). Never drop or rename columns in minor updates (MySQL implicitly commits DDL).
2. **Update `release/schema-expects.json`**: Whenever a new migration adds required tables or columns, register them in `release/schema-expects.json` so `SchemaVerifier` can enforce the post-migration schema contract.
3. **Version Parity (`Tag = DS_VERSION = composer.json`)**: `DS_VERSION` in `bootstrap.php`, `version` in `composer.json`, and all official bundled `plugin.json` manifests must match (`VersionConsistencyTest`).
4. **Atomic State Persistence (`AtomicJsonStore`)**: Always use `JsonStoreInterface` / `AtomicJsonStore` for JSON state files (`storage/update/`, `config/*.json`).

---

## ✅ 6. PRE-DELIVERY CHECKLIST FOR AI AGENTS

Before returning code to the user, verify:
- [ ] Does every `.php` file start with `declare(strict_types=1);`?
- [ ] Does the PSR-4 namespace match the exact directory case (`DomainSystem\Plugins\<exact_folder>\...`)?
- [ ] Are controllers 100% free of `$_GET`, `$_POST`, `$_SESSION`, `$_FILES`, and inline HTML?
- [ ] Are all dependencies injected via interfaces (`Contracts/*Interface.php`)?
- [ ] Is there a `manual/module.php` implementing `ManualModuleInterface`?
- [ ] Do all tests pass (`php -dextension=zip phpunit.phar`)?

