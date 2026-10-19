> **ATENÇÃO ARQUITETURAL / PARADIGM SHIFT (2026-10-04)**
> 
> O Domain System **NÃO** é mais um sistema Ambiente Operacional Web. Ele evoluiu para se tornar um **Ambiente Operacional Web (WOE) independente**.
> 
> No passado, o núcleo foi desenhado focado em separar ambientes de SaaS, mas esse acoplamento limitava o projeto. Hoje, o Domain System funciona como um Ambiente Operacional Web (Web Operating Environment), que pode ser instalado em uma máquina ou container.
> Se for desejado que ele preste serviços Multi-Tenant, isso deverá ser feito virtualmente ou através de um aplicativo (Plugin Ring 3) construído especificamente para isso, **sem afetar o Micro-Kernel (Ring 0)**.

# 🛡️ Architectural and Security Audit (Domain System v2.3)

**Last Updated:** October 18, 2026

This document reflects the historic architectural transition of the Domain System, evolving from a single-case procedural platform to a **SOLID-structured Web Operating Environment Micro-Kernel**.

---

## 🏛️ Architectural Evolution (From v1.0 to v2.3)

The system's core underwent a deep overhaul across all its structural pillars to ensure compatibility with market standards (SOLID, PSRs, advanced Design Patterns).

### 1. Absolute Decoupling (Zero Business Logic in the Core)
- **Before (v1.0):** The Kernel and authentication knew business concepts (Doctor, clinic, patient tables, and hardcoded roles).
- **Now (v2.3):** The Kernel is 100% agnostic. Generic roles (`admin`, `manager`, `subscriber`, `user`) control universal RBAC. All business logic was extracted and transferred to the plugin layer.

### 2. Inversion of Control and Dependency Injection (DIP)
- **Before:** Widespread use of `new Class()` and Service Locators like `$this->db()` scattered throughout controllers.
- **Now:** The system relies on a high-level Dependency Injection (DI) Container. Controllers and Repositories declare contracts (Interfaces) in the constructor, and the Micro-Kernel automatically injects the concrete instance.

### 3. Elimination of Superglobals (Enforced by Architecture Tests)
- **Before:** Broad raw access to `$_SESSION`, `$_POST`, `$_GET`.
- **Now:** All communications flow immutably and sanitized through the `Request` class and `SessionManagerInterface`, continuously enforced by `NoSuperglobalsInControllersTest`.

### 4. Contract-Driven Manual Auto-Discovery (`ManualModuleInterface` - v2.3.0)
- **Architectural Innovation:** The Cockpit (`/admin/manual` and `/manual/index.php`) uses `PluginManualScanner` to automatically scan `manual/module.php` inside installed plugins (`academy`, `flextheme`, `pages`, `seo`). Any plugin implementing `ManualModuleInterface` injects its interactive tutorial and QA homologation checklist items with zero Kernel coupling.

### 5. Hybrid SOLID Architecture in Academy LMS & Automatic `.php` Lesson Factory (`v2.3.0`)
- The `academy` plugin sets the ecosystem gold standard: strict separation between Contracts (`CourseRepositoryInterface`, `LessonFileScaffolderInterface`, `ThemePageResolverInterface`, `LessonBlockRendererInterface`, `HtmlSanitizerInterface`), Services, and Controllers, offering:
  - **Automatic Folder & `.php` Lesson Factory (`LessonFileScaffolderService`):** Scaffolds physical module folders and ready-to-use `.php` files inside the active theme (`themes/<theme>/pages/estrutura-de-dados.php` $\rightarrow$ `/cursos/estrutura-de-dados`).
  - **Hybrid Authoring (4 Modes, 8 Blocks & 3 Mobile Profiles):** Supports Raw HTML/Markdown, 8-block Visual Editor, Fabricated `.php` Lessons with code or blocks, and 3 mobile layout profiles (`drawer`, `bottom_bar`, `cinema`) with a 5-Viewport Multi-Device Simulator.

### 6. Database- and OS-Agnostic Resilience (`SchemaDoctorInterface` & `AtomicJsonStore`) + "Vibe Coding on Rails" (`AI_CONTRACT_GUIDE.md`)
- **Zero Database/OS Coupling in Ring 0:** The Kernel and `MonitorController` operate exclusively through contracts (`SchemaDoctorInterface`, `JsonStoreInterface`), remaining completely oblivious to SQLite/MySQL drivers or Windows/Linux/macOS filesystems.
- **Official AI Contract Guide (`AI_CONTRACT_GUIDE.md`):** IDE rules (`.github/copilot-instructions.md`, `.cursorrules`, `.clinerules`) and CLI generator (`php os-cli.php make:plugin <slug>`) that guide any AI assistant along strict SOLID contract rails.

---

## 🔒 Surgical Audit & Hardening (Patches 2.1.0 to 2.3.0 - Oct/2026)

1. **Ring 0 Route Collision Protection:** Core Kernel routes (`/login`, `/admin/*`) are locked against Ring 3 hijacking (`RouteAlreadyRegisteredException`).
2. **AES-256-GCM Authenticated Encryption:** Upgraded from `AES-256-CBC` to authenticated `AES-256-GCM` with integrity tag verification.
3. **HTTP Session Hardening & Circuit Breaker Developer Mode:** `HttpOnly`, `SameSite=Strict`, `Strict Mode` session cookies, plus a session-scoped Developer Mode bypass for live debugging of tripped plugins.
4. **Route-Level Circuit Breaker Threshold, `AtomicJsonStore` & 1-Click Snapshot:** Concurrency-safe JSON storage (`flock` + `rename`), route-level failure isolation before full plugin ejection, and instant state restore points in `/admin/supervision`.

---

## 🔒 Security and Resilience Audit (Patch 2.0)

Based on external audits (including rigorous OWASP-focused scans), the following protection measures have been definitively integrated into the OS:

### 1. No-Break Shield (Smart Circuit Breaker)
Implemented at the Kernel level, this design pattern prevents the "Domino Effect" and the "White Screen of Death" (WSOD). If a plugin causes a Fatal Error or a memory leak, the breaker intercepts the call, isolates the component in milliseconds, and renders an immutable dashboard for the Super Admin without affecting the processes of other virtual applications or instances on the server.

### 2. "Zip Slip" Protection
- **Previous Flaw:** Plugin installation via `.zip` without path validation, allowing core overwriting.
- **Fix:** The `ZipArchiveExtractor` class now implements a dual layer: extraction in a quarantine zone (`temp/` directory) and rigorous `realpath()` normalization to prevent *Directory Traversal* attacks.

### 3. Automatic Global CSRF Shielding
Anti-CSRF (`Cross-Site Request Forgery`) tokens are now seamlessly issued and injected via middlewares and by the PHP template engine itself in all sessions, shielding Admin Panel forms without the need for direct action by third-party developers.

### 4. Error Codes Dictionary (Telemetry)
Creation of the Troubleshooting artifact (`ERROR_DICTIONARY.md`), packaging raw PHP exceptions into categorized, machine-readable codes (like `ERR_SYS_01`, `ERR_PLG_03`), paving the highway for automated intervention by Artificial Intelligences in the OS.

---

## 📈 Conclusion

The **Domain System v2.3.0** (with an unpacked folder footprint of only **~30MB** on Linux servers) reaches the "Enterprise-Ready & AI-Ready" benchmark. Its agnostic Micro-Kernel is clean, cohesive, tested by **183 automated unit and architecture tests (751 assertions)**, and shielded against cascading crashes.



