# Domain System (v2.5)

A lightweight modular web platform in PHP 8.2+ for managing pages, services, and themes, engineered around interface contracts and dependency injection.

The project aims to provide a decoupled and uncluttered alternative to traditional CMS engines, maintaining low memory usage (~5 MB of Zend RAM per typical request) and an unpacked footprint of approximately 30 MB on disk.

> **Project Status:** Domain System is in an active cycle of ongoing development, real-world production testing, and continuous improvement. Refactorings, performance optimizations, and architectural enhancements are rolled out regularly based on automated test suites and practical ecosystem feedback.

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

## 🔍 Open Auditing, AI Context & `update-project`

To support collaboration, static analysis, and code auditing by both software engineers and large language models (Claude, ChatGPT, Gemini, etc.), this repository provides consolidated codebase context files at the root:

- 📄 **[`domain-system.txt`](domain-system.txt)** (or **[`project.txt`](project.txt)**)

These files compile the complete directory tree, interface contracts, and source code into a clean, structured document.

### How to audit the codebase with AI without fragmentation:
1. Download `domain-system.txt` (or clone the repository).
2. Attach the file directly to your preferred AI model.
3. Request your technical audit (e.g., verifying interface compliance in `src/Core/Contracts`, input sanitization, or fault-isolation rules). Because the model receives the entire architectural scope at once, it audits with global context and zero fragmentation.

> **Tooling:** This consolidated context file and the clean distribution zip are packaged using [**Update-Project**](https://github.com/hadelrachid/update-project) — an open-source Python utility (CLI & GUI) engineered to pack codebases into structured context files for AI audits. You can freely use it for your own projects.

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
