# 🛡️ Architecture & Security Guide (Domain System v2.6.0)

This document outlines the architectural decisions, coding patterns, and security practices used throughout **Domain System**.

---

## 🏛️ Architectural Foundations

The system is designed to be modular, fast, and maintainable, following modern PHP best practices:

### 1. Clear Separation of Concerns
- **Core (`src/Core`)**: Contains only what is strictly required to bootstrap and run the runtime (environment-agnostic router, dependency injection container, event dispatcher, and session manager). It contains zero domain-specific business rules.
- **System Apps (`src/SystemApps`)**: Essential platform infrastructure modules (database layer, authentication/RBAC, admin cockpit, and updater).
- **Plugins (`src/Plugins`)**: Feature extensions (such as **Academy** for courses, **DomainCommerce** for payments, and **Flex Builder** for themes).

### 2. Dependency Injection and Contracts
- Instead of instantiating concrete classes inside controllers (`new Service()`), components declare their dependencies as **interfaces (contracts)** in their constructors.
- The DI container resolves and injects the proper concrete instances automatically, simplifying refactoring and unit testing.

### 3. Clean Request Handling (No Raw Superglobals)
- Controllers never touch superglobals (`$_POST`, `$_GET`, `$_SESSION`) directly.
- Every HTTP request is encapsulated in an immutable `Request` object, and session interactions go through `SessionManagerInterface`, ensuring strict typing and sanitized input.

---

## 🧩 Core Features & Modules

### 🎓 Academy LMS
- Student portal with secure authentication, profile management, and progress tracking.
- Courses structured into modules and lessons with support for video embeds, block-based text, and a **built-in PDF handout reader**.
- Real-time progress calculation and automated certificate issuance upon 100% completion.

### 💳 DomainCommerce (Payments & Checkout)
- **Official Mercado Pago (Brazil):** On-screen dynamic Pix (instant QR Code and copy-paste code) with 3.5s real-time payment confirmation and automatic course enrollment, up to 12x credit card installments, and boleto.
- **Official PayPal (Global Sales):** Full integration with PayPal Checkout REST API v2 for international purchases via PayPal balance or global cards.
- **Affiliate Webhooks:** Automatic processing of sales notifications from Hotmart, Eduzz, Monetizze, and ClickBank, creating students and enrolling them on the spot.
- **Strict Gateway Injection:** Only gateways with valid active credentials are made available at checkout.

### 🎨 Flex Builder (Themes & Layouts)
- Visual drag-and-drop layout builder for headers, footers, and page sections using a 12-column CSS Grid.
- Per-breakpoint visibility controls (Desktop, Laptop, Tablet, Mobile) with automatic column recalculation (*Auto-Reflow*).
- Multi-level navigation menus with horizontal drag indentation and a customizable mobile drawer with 8 hamburger styles.

---

## 🔒 Security and Resilience

1. **SQL Injection Protection:** All database queries utilize PDO with parameterized prepared statements.
2. **CSRF Defense:** Mandatory anti-CSRF token verification on all state-mutating HTTP methods (`POST`).
3. **HTML Sanitization:** Content sanitization across rich text and SVG inputs to prevent Cross-Site Scripting (XSS).
4. **Fault Isolation (Circuit Breaker):** Runtime exception traps isolate crashing extensions, keeping the core site available.
5. **Safe Updates:** GitHub Releases verification with SHA-256 integrity checksums and automated pre-update backups.

---

## 🧪 Automated Test Coverage

Platform stability is continuously verified with **PHPUnit**:
- **536 automated unit and integration tests** with **2,999 assertions** covering checkout flows, payment gateways, enrollments, authentication, and architectural contracts.
