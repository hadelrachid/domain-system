# Domain System (v2.5)

A lightweight modular web platform in PHP 8.2+ for managing pages, services, and themes, engineered around interface contracts and dependency injection.

The project aims to provide a decoupled and uncluttered alternative to traditional CMS engines, maintaining low memory usage (~5 MB of Zend RAM per typical request) and an unpacked footprint of approximately 30 MB on disk.

---

[🇧🇷 Versão em Português](README.md) | [📖 Documentation](https://hadelrachid.github.io/domain-system/) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Developer Guide](DEVELOPER_GUIDE.md)

---

## 🎯 Architectural Overview

The system adheres to **Clean Architecture** and **Dependency Inversion (DIP)** principles:

1. **Agnostic Core (`src/Core`)**:
   - Provides low-level infrastructure services: dependency injection container, event dispatcher (hooks), environment-agnostic routing (root or subfolder), atomic configuration storage, and database schema checkers.
   - The core contains zero business logic and has no tight coupling with specific database engines.

2. **Layer Separation (`SystemApps` and `Plugins`)**:
   - **`src/SystemApps` (Infrastructure Modules)**: Fundamental structural components (database connection, authentication/RBAC, admin cockpit, monitoring, and auto-updater). They receive boot sequence priority and are never automatically disabled on transient exceptions.
   - **`src/Plugins` (Domain Extensions)**: Application-level modules (such as theme builder, LMS, pages, and SEO). They interact with the core through the `OsRuntime` mediator, which validates declared permissions and services before resolving DI container resources.

3. **Fault Tolerance and Circuit Breaker**:
   - Exception handling throughout module lifecycles: if a domain extension encounters a fatal error during boot or route dispatch, the failure is isolated and the rest of the application remains available.

4. **Flex Builder (Full Site Editing)**:
   - Visual layout engine for headers, footers, and page sections based on a 12-column CSS Grid.
   - Supports per-element responsive visibility across 4 breakpoints (Desktop, Laptop 1024px, Tablet 768px, and Mobile 375px) with automatic grid column recalculation (*Auto-Reflow*).
   - Structural payload validation prior to storage and strict sanitization of HTML and SVG vectors against XSS.

5. **Integrated Auto-Updater (`SystemUpdater`)**:
   - Automated release retrieval and verification against GitHub Releases using SHA-256 checksums.
   - Atomic file swap mechanism with preflight environment checks, preserving local configuration files (`.env`, `config/`, database, and media uploads).

---

## 🔍 Open Auditing & AI Analysis: `project.txt`

To support open collaboration, static analysis, and code auditing by both software engineers and large language models (LLMs), this repository includes an aggregated source map at the root:

📄 **[`project.txt`](project.txt)**

This file compiles the system structure, namespaces, interface contracts, and source code into a single structured document.

> **Tip for Code Review and AI Auditing:** If you wish to audit the architecture or analyze how contracts communicate, you can load `project.txt` directly into your AI tool of choice (Claude, ChatGPT, Gemini, etc.) or static code analyzer. This provides full global context of the codebase without fragmented assumptions.

Technical feedback, discussions, and pull requests are welcome in GitHub *Issues* and *Discussions*.

---

## 📊 Telemetry and Test Suite

- **Language:** PHP 8.2+ (strictly typed with `declare(strict_types=1)`).
- **Automated Tests:** 438 unit and integration tests running on PHPUnit (over 2,350 assertions covering contracts, sanitization, migrations, and updater).
- **Memory Consumption:** ~4.94 MB active Zend engine memory on standard requests (~12 MB peak OS allocation).
- **Production Dependencies:** Zero third-party runtime dependencies in the core (utilizing standard PHP extensions: PDO, Zip, Mbstring, OpenSSL, and cURL).

---

## 🚀 Quick Start

### Requirements
- PHP 8.2 or higher with `pdo`, `pdo_sqlite` (or `pdo_mysql`), `mbstring`, `zip`, `curl`, and `openssl` extensions.
- Web server (Apache, Nginx, or PHP built-in CLI server).

### Local Execution
```bash
# 1. Clone the repository
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Start PHP built-in server for testing
php -S localhost:8000 -t public
```

Open `http://localhost:8000` in your browser to proceed with the setup wizard.

---

## 🧪 Running the Tests

To run the complete automated test suite with PHPUnit:

```bash
php phpunit.phar
```

---

## 📄 License

This project is open-source software licensed under the [MIT License](LICENSE).
