# 🚀 Domain System — The Business Engine That Never Dies

> **Current Version:** `v1.2.0` — *"The Era of Architectural Resilience"*

[🌐 Official Site (Docs)](https://hadelrachid.github.io/domain-system/) | [📄 Documentation](README-en.md) | [📜 Changelog](docs/CHANGELOG-en.md) | [🕵️ Audit](docs/auditoria-en.md)

---

[🇧🇷 Leia em Português](README.md)

## 💡 What is Domain System?

**Domain System** is not just another framework or management system. It is a **hyper-resilient business engine**, designed to be the foundation of any enterprise application, from a medical clinic to a law firm or a financial ERP.

It works like an **operating system for your business**: the core (Kernel) provides the essential infrastructure (database, security, routing, dependency injection), while all business logic is encapsulated in independent, interchangeable **Plugins**.

This project combines the best of both worlds: the **simplicity and performance of pure PHP** with the **robustness and scalability of enterprise architectures** (like microservices and SOLID principles).

---

## 🏗️ Architecture: An Engineering Masterpiece

The Domain System architecture was built to be **immune to chaos**. It is based on three fundamental pillars:

### 1. The Immortal Kernel (Core)
The heart of the system is minimalist and contains no business logic. Its only function is to orchestrate plugins and provide secure tools (DI Container, Event Dispatcher, Router, Session Manager). The Kernel is the universal "socket" where any plugin can connect. It acts as a **Global Gatekeeper**, managing memory and validating routes preemptively so Controllers never have to worry about security implementations.

### 2. The Plugin Ecosystem (Modules)
Every feature — from authentication to billing — is an isolated Plugin. Plugins communicate **exclusively via events** (Event-Driven Architecture), ensuring that the failure of one does not compromise the whole. They are the "payload" that transforms the Kernel into a clinic, legal, or financial system. Everything works through Dependency Injection and strong decoupling (DIP and SRP).

### 3. The Presentation Layer (Themes & Cockpits)
The user interface is fully decoupled. Themes (frontend) consume data from Plugins via **Shortcodes** and a **Workspace** system (user profiles). This allows designers and frontend developers to work independently, without ever touching business logic.

---

## 🛡️ Resilience: The System That Never Dies

Failures happen. In Domain System, they are **contained, logged, and fixed without taking down the system**.

- **Circuit Breaker V2:** If a plugin causes a fatal error (syntax, database, memory), the Kernel automatically deactivates it and logs the incident. The rest of the system keeps running.
- **Emergency Hatch:** If the authentication module fails, administrators can still access the system via a secure emergency route, using the server's `APP_KEY`.
- **Cascade Effect:** If Plugin A depends on Plugin B, and B is deactivated by the Circuit Breaker, A is also deactivated to prevent chain failures.

---

## ✨ Key Features

- **100% PHP 8+** — Modern, clean, and object-oriented code.
- **SOLID Architecture** — Every class has a single, well-defined responsibility.
- **Event-Driven** — Plugins communicate via events, ensuring low coupling.
- **Dependency Injection** — DI Container with autowiring.
- **Repository Pattern** — Controllers never touch the database directly.
- **Multi-Workspace** — Native support for multiple user profiles (Admin, Doctor, Receptionist, Lawyer).
- **Audit & Monitoring** — Error supervision panel with detailed logs and "copy stack trace" button.
- **AI Hub Integrated** — Plugin and form generator powered by Artificial Intelligence (Gemini, ChatGPT, etc.).
- **Modular & Extensible** — Add or remove features without affecting the core.

---

## 🧩 Who Is This Project For?

- **Clinics & Medical Offices** — Manage patients, appointments, medical records, and billing.
- **Law Firms** — Track cases, deadlines, and clients with a dedicated workspace.
- **Enterprise Developers** — Build white-label solutions quickly without reinventing the wheel.
- **Software Architects** — A living laboratory of best practices (SOLID, DDD, Event Sourcing).

---

## 🛠️ Technologies Used

| Layer           | Technology                                  |
|-----------------|---------------------------------------------|
| **Backend**     | PHP 8+ (Vanilla, Advanced OOP)              |
| **Database**    | SQLite (with native PostgreSQL support)     |
| **Frontend**    | HTML5, CSS3, JavaScript (Vanilla)           |
| **Design Patterns** | DI, Event Dispatcher, Repository, Strategy, Adapter, Factory, Circuit Breaker |

---

## 📖 Documentation & Resources

- **[Developer Guide](DEVELOPER_GUIDE.md)** — Learn how to create plugins and themes from scratch.
- **[Project Wiki](wiki/Home.md)** — Advanced concepts like Circuit Breaker, Emergency Hatch, and ACL.
- **[Changelog](docs/CHANGELOG-en.md)** — Complete version history and fixes.
- **[Roadmap](ROADMAP.md)** — The future of Domain System.

---

## 🤝 Collaborators

This project is built by a mixed team of human and artificial intelligence:

- **Rachid Hadel** — Software Engineer, Architect, and Product Owner.
- **Antigravity (Google DeepMind)** — Co-Developer, AI Architect, and Code Auditor.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).
## 🌌 The Theme Multiverse
The CockPit lives in an authentic multiverse. Read about our Multi-Theme architecture and how we host countless visuals (Public World, Doctor, Kiosk) running isolated and simultaneously in the same application by accessing [docs/multiverse.md](docs/multiverse.md).
