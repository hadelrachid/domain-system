<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.0.0-blueviolet?style=for-the-badge" alt="Version 2.0.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <br><br>
  <h1>🚀 Domain System OS (v2.0)</h1>
  <p><strong>The Ultimate Web Operating System. A pure, hyper-resilient, multi-tenant Micro-Kernel SaaS designed to compete with the giants.</strong></p>
</div>

---

[🇧🇷 Leia em Português](README.md) | [📚 Documentation & Tutorials]( https://hadelrachid.github.io/domain-system/) | [🛡️ Architectural Audit](docs/auditoria-en.md)

## 🌐 What is Domain System?

The **Domain System** has evolved. It is no longer a rigid system, but a **true Web Operating System (SaaS Core)**, written purely in PHP with clean architecture (SOLID and Dependency Injection). It is a modern, ultra-fast alternative to WordPress and frameworks like Laravel.

The limit is your imagination! You can run **absolutely any application** on top of it:
- The **Core (Micro-Kernel)** provides low-level, completely agnostic infrastructure: Database, Dynamic Routing, CSRF Security, Sessions, Multi-tenant (Isolated Subdomains), and an Event Dispatcher (Event-Driven).
- All **business logic** (whether a legal system, an e-commerce, or a blog) is injected via independent **Plugins**. The core never knows what the application does; it just orchestrates!

## ✨ Key Features

### ⚡ 1. Native Multi-Tenant Architecture
The OS is built to be the foundation of a SaaS (Software as a Service). Through subdomain mapping (`tenants.json`), the Kernel routes different clients to fully isolated databases, sharing resources efficiently and safely without mixing data.

### 🛡️ 2. No-Break Shield (The Immortal Circuit Breaker)
Forget the "White Screen of Death" (WSOD). Domain System runs with the **No-Break Shield**: a Kernel-level Circuit Breaker that intercepts fatal crashes caused by poorly written plugins, disables the faulty execution, displays a rich telemetry panel (Error Supervision with standardized codes), and keeps the rest of the OS running perfectly.

### 🎨 3. Builder-Flex and Dynamic Page Engine
With the `pages` plugin and theme engine, the system dynamically converts requests based on the URI, coupling front-end templates from your theme to data blocks. The ground is prepared for the introduction of **Builder-Flex** (a complete drag-and-drop visual builder).

### 🔐 4. Global 2-Factor Authentication (2FA) & RBAC
The security core (`Auth` plugin) includes native support for Google Authenticator (TOTP) and access control based on universal roles (`admin`, `manager`, `subscriber`, `user`), allowing you to build any permission hierarchy generically.

### 🌑 5. Zero-Friction Installation and Skin Engine
Browser-assisted installer (Wizard) that self-destructs its own routes after setting up the database. In the Admin Panel, you navigate an interface powered by a Cyberpunk *Skin Engine* based on CSS variables and native Dark-First mode.

---

## 🚀 How to Install

1. **Clone the repository** to your Apache/Nginx public server folder (e.g., `htdocs` or `www`):
   ```bash
   git clone https://github.com/hadelrachid/domain-system.git
   ```

2. **Permissions (Linux/Mac):** Ensure the folder has write permissions for the web server.
   ```bash
   chmod -R 777 domain-system/storage
   chmod -R 777 domain-system/config
   ```

3. **Open in Browser:**
   Access `http://localhost/domain-system` (or your domain).

4. **Wizard Installer:**
   The system will detect it's not configured (missing `config/installed.lock`) and guide you through the **Installation Wizard**. Follow the steps (database, admin user), and the OS will be ready to use!

---

## 📂 Architecture (Overview)

```text
/
├── config/              # Tenant Center, Plugin & DB Configurations
├── public/              # Public directory (assets, entrypoint index.php)
├── storage/             # Persistent files (error logs, cache)
└── src/
    ├── Core/            # OS Micro-Kernel (Router, Events, Container, Shield)
    └── Plugins/         # Ecosystem of business extensions
        ├── auth/        # Base RBAC and 2FA system 
        ├── pages/       # Dynamic web page generator 
        ├── system-admin/# OS control interface (Dashboard and Widgets)
        └── installer/   # Smart installation wizard
```

## 🛠️ Contributing and Tutorials

Our goal is to standardize and unify web development in PHP. 
Access our **[Developer Guide (DEVELOPER_GUIDE.md)](DEVELOPER_GUIDE.md)** to master Dependency Injection and hook creation in the OS. The `docs/` folder is constantly updated with manuals and architecture dictionaries.

Made with ☕ and forged in architectural resilience.
