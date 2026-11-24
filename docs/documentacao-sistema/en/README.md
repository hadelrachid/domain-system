# Inside Domain System (v2.8.3)

> 🇺🇸 **English** | [🇧🇷 Versão em Português](../README.md)

> **"Architecture is not about the code you write today. It is about the connections you forge, the pain you prevent, and the future you build."**  
> *— From the book ["Understanding SOLID Architecture: From Object to Contract, From Coupling to Architecture"](https://www.amazon.com.br/dp/B0H74L5GFL) (available on Amazon Kindle / KDP)*

---

If you are starting out in programming, have never studied "Design Patterns", or always found academic explanations of **SOLID** tedious and filled with sterile examples (*"Dogs that bark"* and *"Cats that meow"*), **this guide was tailored specifically for you**.

Here, we lift the hood of **Domain System** component by component using **real-world physical and biological analogies**:
- **The Wall Socket and the Plug:** *"First the socket (contract/interface), then the plugs (concrete classes/implementations)"*.
- **The Arduino Motherboard:** The Kernel doesn't need to know if you connected an LED or an ultrasonic sensor; it only knows the standard pin interface.
- **Nature's Beehive:** The Kernel acts as the **single Queen (Singleton)**, essential worker bees act as **Eager Loading**, on-demand drones act as **Lazy Loading**, and interfaces and events operate as **chemical pheromones** communicating with zero tight coupling.
- **The Immortal Circuit Breaker:** An immune system that trips only the faulty extension so the colony never dies.

By understanding the authentic lifecycle of a request as it traverses the system, finding **logical flaws** and **security vulnerabilities** ceases to be a mystery and becomes straightforward.

---

## 🗂️ Table of Contents

Read in sequential order. Each chapter follows the real lifecycle of an HTTP request:

| Ch. | Chapter | Concepts & Analogies Covered | Inspected Source Files | Status |
| :---: | :--- | :--- | :--- | :---: |
| **00** | [Introduction — System Map & Architectural Philosophy](00-introduction.md) | The pain of tight coupling, the revelation of wall sockets and Arduino, Alan Kay's biological metaphor, SOLID in real life, and project layout. | Global architecture overview | ✅ Ready |
| **01** | [Chapter 01 — The "Start": Request Birth & The Single Gateway](01-server-start.md) | The silence before the Big Bang, `.htaccess` filters, the *Front Controller* (`public/index.php`), maintenance locks, and **4 audit findings**. | `.htaccess`, `public/.htaccess`, `public/index.php`, `public/maintenance.php`, `public/reset_opcache.php` | ✅ Ready |
| **02** | [Chapter 02 — The Bootstrap Ritual](02-the-bootstrap-ritual.md) | The system BIOS ritual, `.env` loading, Composer autoloading mechanics, and the fail-safe error handling immune system (`ErrorHandler`). | `bootstrap.php`, `composer.json`, `src/Core/Error/` | ✅ Ready |
| **03** | [Chapter 03 — The Hive Queen](03-the-hive-queen.md) | Why multiple queens mean total collapse, how `Application` was armored against cloning and unserialization, and Container DI throne registration. | `src/Core/Application.php` | ✅ Ready |
| **04** | Chapter 04 — The Central Warehouse (`Container`) & Wall Sockets (`Contracts`) | Dependency Inversion (DIP) in action, service binding without coupling, and swapping implementations without touching the engine. | `src/Core/Container/`, `src/Core/Providers/`, `src/Core/Contracts/` | ⏭️ Next |
| **05** | Chapter 05 — Awakening the Hive (`boot()`, Plugins & Pheromones) | How `PluginManager` discovers manifests (`plugin.json`), wakes worker extensions, and uses `EventDispatcher` as a broadcast radio. | `src/Core/Plugin/`, `src/Core/Events/`, `src/SystemApps/` | ⏳ Soon |
| **06** | Chapter 06 — The Global Gatekeeper & Request Dispatching | The Router as a subway turnstile (OCP), preemptive ACL/CSRF verification, reflection-based DI, and Controller execution. | `src/Core/Routing/`, `src/Core/Http/` | ⏳ Soon |
| **07** | Chapter 07 — Layered Security (Auth, Session, ACL & Guards) | Session management, safeguarding administrative routes, mitigating open redirects, session hijacking, and CSRF attacks. | `src/Core/Security/`, `src/SystemApps/auth/` | ⏳ Soon |
| **08** | Chapter 08 — Decoupled Persistence & Workspace Layouts | The Repository Pattern (Controller asks *"what"*, Repository answers *"how"*), atomic JSON storage, and role-based user workspaces. | `src/Core/Database/`, `src/Core/Storage/`, `src/Core/Theme/`, `src/Core/Workspace/` | ⏳ Soon |
| **09** | Chapter 09 — Consolidated Security & Quality Audit Log | Complete audit dossier detailing every vulnerability, performance bottleneck, and logic issue discovered with actionable fixes. | System-wide audit overview | ⏳ Soon |

---

## 💡 How to Get the Most Out of This Guide

1. **Keep the actual codebase open:** when you see a reference to a specific file or line, open it in your editor and inspect the implementation.
2. **Focus on connections rather than syntax:** ask yourself *who depends on whom*, *who instantiates what*, and *what happens when a component needs to be swapped*.
3. **Review the Audit Spotlight at the end of each chapter:** that is where theoretical principles translate into the practical skill of catching bugs and vulnerabilities before production.
