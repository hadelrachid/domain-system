# Inside Domain System (v2.7.0)

> [🇧🇷 Versão em Português](../README.md) | 🇺🇸 **English**

> **A plain-English, zero-jargon walkthrough of how Domain System is built from the inside out — from the very first millisecond of a request all the way to finding logic bugs and security holes.**

If you are just getting started in programming, starting university, or have never studied "Design Patterns" or "SOLID Architecture", **this guide was written for you**.

The goal here is not to sound academic. The goal is to pop the hood open on Domain System piece by piece, show **how it was built**, **why each file exists**, and **what really happens** every time someone opens a page in their browser.

Once you understand the actual path a request travels through the code, spotting **logic bugs** and **security vulnerabilities** stops feeling like magic and becomes obvious.

---

## Table of Contents

Read the chapters in order. Each chapter picks up right where the previous one left off, following the real life of a single user click:

| Ch. | Chapter | What you will understand | Real files covered | Status |
| :---: | :--- | :--- | :--- | :---: |
| **00** | [Introduction — Map of the System (No Hard Words)](00-introduction.md) | What Domain System is, where each folder lives, and a plain-English translator for the technical words used in the codebase. | Project overview | ✅ Done |
| **01** | [Chapter 01 — The "Start": What Happens When Someone Opens the Site](01-server-start.md) | How Apache redirects requests, why everything lands in `public/index.php`, how URLs are cleaned, how maintenance mode locks the site, and **4 real issues found right at the front door**. | `.htaccess`, `public/.htaccess`, `public/index.php`, `public/maintenance.php`, `public/reset_opcache.php` | ✅ Done |
| **02** | Chapter 02 — Waking Up the System (`bootstrap.php`) | How `.env` is loaded, how PHP finds classes automatically (Autoload), and how the "airbag" for crashes is armed (`ErrorHandler` & `CircuitBreaker`). | `bootstrap.php`, `composer.json`, `src/Core/Error/` | ⏭️ Next |
| **03** | Chapter 03 — The "Single Brain" (Singleton & `Application.php`) | What a Singleton actually is, why there can only be one `Application`, how it is coded, and where its limits are. | `src/Core/Application.php` | ⏳ Coming soon |
| **04** | Chapter 04 — The "Tool Room" (`Container`) & "Wall Outlets" (`Contracts`) | How the system builds and shares objects without turning into spaghetti, and how to swap parts without breaking everything (SOLID in real life). | `src/Core/Container/`, `src/Core/Providers/`, `src/Core/Contracts/` | ⏳ Coming soon |
| **05** | Chapter 05 — Starting the Modules (`SystemApps`, `Plugins` & Events) | How the system discovers plugins, starts them in the right order, and uses the "walkie-talkie" (`EventDispatcher`) to communicate loosely. | `src/Core/Plugin/`, `src/Core/Events/`, `src/SystemApps/` | ⏳ Coming soon |
| **06** | Chapter 06 — Journey of a Request (`Request`, `HttpKernel`, `Router`, `Middlewares` & `Response`) | How a raw request becomes an object, passes through security guards (middlewares), hits the right `Controller`, and returns HTML or JSON. | `src/Core/Http/`, `src/Core/Routing/` | ⏳ Coming soon |
| **07** | Chapter 07 — Security in Practice (Login, Sessions, Permissions, CSRF & Safe Redirects) | How the system knows who is logged in, protects admin routes, blocks CSRF/XSS attacks, and stops fake redirects. | `src/Core/Security/`, `src/SystemApps/auth/` | ⏳ Coming soon |
| **08** | Chapter 08 — Database, Safe JSON Files, Themes & Workspaces | How data is stored (SQLite/MySQL + atomic JSON writes) and how the final screen is assembled for each user role. | `src/Core/Database/`, `src/Core/Storage/`, `src/Core/Theme/`, `src/Core/Workspace/` | ⏳ Coming soon |
| **09** | Chapter 09 — Final Audit Notebook (Full Checklist) | Every logic bug and security hole found across the entire series, organized by priority. | Full summary | ⏳ Coming soon |

---

## How to get the most out of this guide

- **Don't try to memorize buzzwords:** whenever a technical word shows up (*Front Controller*, *Singleton*, *Middleware*), it is immediately explained in everyday language.
- **Keep the real code open side by side:** whenever a chapter mentions something like `public/index.php (line 64)`, open that file in your editor and see it with your own eyes.
- **Read the "Audit Lens" section at the end of every chapter:** that is where we record real bugs and security gaps discovered directly in the source code, explaining **what is wrong, what could go wrong in production, and how to fix it**.
