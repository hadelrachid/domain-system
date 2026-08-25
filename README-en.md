# 🚀 Domain System (Universal Modular Framework)
> **Current Version:** `[v1.1.0]`

[ 🌐 Official Site (Docs) ](https://hadelrachid.github.io/domain-system/) | [ 📄 Documentation ](README-en.md) | [ 📜 Changelog ](docs/CHANGELOG-en.md) | [ 🕵️ Audit Report ](docs/auditoria-en.md)
---
[ 🇧🇷 Ler em Português ](README.md)

A hyper-resilient architectural engine (framework) designed to be infinitely expandable. Similar to the WordPress concept, the Domain System is not just specific software — it transforms into **anything** depending on the active plugins.
It can be a **Medical Clinic System**, a **Financial ERP**, or a **Law Firm Management System**. It all depends on the plugin package connected to the core.

## 🏗️ System Architecture

This project moves away from traditional "spaghetti code" and adopts the **Kernel and Plugins** pattern (inspired by enterprise architectures and modern CMSs), with strict adherence to **SOLID** principles.

### 1. The Kernel and PluginManager
The system's core (Kernel) only provides the basic infrastructure (Database, Template Engine, Dependency Injection, and Routing System). Everything else (Authentication, Patients, Medical Records) are independent **Plugins**.
- Plugins communicate with each other exclusively via `EventDispatcher` (Hooks).
- If a plugin needs to add a menu, it doesn't modify the dashboard directly; it simply listens to the `admin.menu` event and injects its option.

### 2. Circuit Breaker V2
Inspired by modern microservices, the system has a native **Circuit Breaker**. 
If a plugin is activated and its code contains errors (e.g., Syntax Errors, Invalid Calls, Database Failures), the Kernel catches the error, isolates the defective module, and automatically disables it in `plugins.json`. The entire system survives and continues operating without the "white screen of death".

### 3. The "ATS" (Automatic Transfer Switch)
Even against the most catastrophic failures (such as total RAM exhaustion by an infinite loop), the system is protected.
A `register_shutdown_function` (The Last Breath) monitors the modules' boot process. If the server suddenly dies from resource suffocation (Fatal Error E_ERROR), the ATS will surgically eject the plugin causing the crash in the last milliseconds of life. Upon refreshing the page, the system resurrects and leaves an emergency log.

### 4. Sockets and Plugs (Dependency Inversion)
The system was built with the "Socket and Plug" philosophy in mind. 
For example, in the Authentication module, the 2-Step Verification (2FA) process doesn't know how to send emails or read authenticator apps. It merely provides an interface (`TwoFactorProviderInterface`). Other plugins (or independent classes) provide the plugs (`EmailProvider`, `AppProvider`).
This allows a developer to create a "WhatsApp" plugin tomorrow and add the 2FA function to the Login system without changing a single line of the core code!

### 5. Secure Development Environment (Dev Simulator)
To prevent fake emails from leaking during tests, the system has a `dev_simulator` plugin. When activated, it intercepts communication classes (hijacking the wiring via Dependency Injection) and redirects the emails to a local text file (`temp/auth-2fa.txt`). In production, simply disable the plugin and the wiring returns to its natural state.

## 🛠️ Technologies
- **Language:** PHP 8+ (Vanilla / Advanced OOP)
- **Database:** SQLite (with PDO and Custom QueryBuilder)
- **Frontend:** HTML5, Native CSS (Clean architecture without heavy frameworks)
- **Design Patterns Used:** Dependency Injection, Event Dispatcher, Circuit Breaker, Strategy, Adapter, Singleton.

## 👥 Access Control (ACL)
The system isolates profiles:
- **Administrator:** Master and global access.
- **Doctor:** Restricted access only to schedule, appointments, and medical records.
- **Receptionist:** Cannot view medical records (as per privacy laws), operating only scheduling and basic triage.
- **Legal:** Isolated workspace with confidential reports.

---
*This system is a living organism, programmed to survive itself and expand organically.*

## 📖 Versioning and History
The project is currently at version **[1.1.0]**.
To track all evolution and fixes, check our [CHANGELOG-en.md](CHANGELOG-en.md).

## 🤝 Main Collaborators
This system is built by a mixed team of human and artificial intelligence:

- **Rachid** - Software Engineer (Creator, Architecture Visionary, and Product Owner)
- **Antigravity (Google DeepMind)** - AI Architect (Co-developer and Code Auditor)
