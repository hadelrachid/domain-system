# Domain System (v2.7.0)

> **A full-featured, ultra-lightweight (~30 MB), and fast web operating platform built with clean, modern PHP 8.2 — no heavy frameworks, no bloated plugin mazes.**

Hi! My name is Hadel, and I am a self-taught developer. I started building **Domain System** around a simple question: can we build a full CMS with international dropshipping/e-commerce and an online school that runs blazing fast, uses only **~5 MB of RAM** per request, and takes up just **~30 MB on disk** — running smoothly even on inexpensive shared hosting or a cheap VPS?

No bloat. No massive framework lock-in. No fragile house-of-cards of 50 third-party plugins.

---

[🇧🇷 Versão em Português](README.md) | [🌱 My Story](ABOUT-en.md) | [📚 Official Wiki](https://github.com/hadelrachid/domain-system/wiki) | [📖 Online Docs](https://hadelrachid.github.io/domain-system/) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Developer Guide](DEVELOPER_GUIDE.md) | [🤝 Community](#-i-need-your-help-join-the-project)

---

## 🤝 I need your help: Join the project!

Because I build mostly on my own, I use modern Artificial Intelligence models as my **daily code audit partners**. I have them comb through the code line by line, hunting down logic errors, concurrency issues, edge cases, and security vulnerabilities. Today, the project has a battle-tested suite of **656 automated tests in PHPUnit with over 3,900 assertions**.

But let's be real: **AI only goes so far. Nothing replaces the sharp eyes, hard-earned experience, and sanity checks of real human developers.**

I am opening the doors wide and actively looking for people who want to team up:
- **Code & Security Auditors:** Come try to break the system! Spot weaknesses, point out flaws, and propose fixes.
- **Software Architects & PHP Enthusiasts:** Help me keep the architecture clean, simple, and decoupled, without falling into the trap of overengineering what should be straightforward.
- **Testers:** Spin it up on your local machine or server. Test the dropshipping checkout, create courses, build themes, and tell me what worked and what broke.
- **Plugin & Theme Developers:** Build new extensions using our lightweight, explicit contracts.

If you like the idea of bloat-free software built with care and want to create something cool together, your Pull Request, Issue, or honest critique is truly welcome!

👉 [Read how to get involved in the Developer Guide](DEVELOPER_GUIDE.md)

---

## 💡 What does the system do out of the box?

Domain System comes with everything you need to launch online businesses:

### 📦 1. Autonomous Dropshipping & E-Commerce (DomainDrop)
- **AliExpress Open Platform API Integration:** Secure, direct communication to search products, calculate real freight, place orders, and track shipments live.
- **24-Hour Flash Curadoria:** Prioritizes products already stored in domestic warehouses (for lightning-fast delivery), handles automatic currency conversion (USD to BRL) with custom markup, and rotates daily deals with a 24-hour countdown timer.
- **Customer Portal (`/minha-conta`):** A clean buyer area with 5-stage shipment tracking (postal services + international tracking) and support ticket creation with photo/PDF uploads.
- **Admin Dashboard with 1-Click Translation:** Monitor orders, delivery SLAs, and resolve disputes with an automated translation button for international suppliers.

### 🎓 2. Online Course Platform (Academy LMS)
- **Student Dashboard:** Clean, distraction-free interface with progress bars and lesson tracking.
- **Video, Text & PDF Lessons:** Embed YouTube, Vimeo, or local videos, alongside an embedded, lightweight PDF lesson reader that doesn't drag the browser down.
- **Automated Certificates:** Automatic completion certificates in PDF format as soon as a student reaches 100%.

### 💳 3. Frictionless Payments (DomainCommerce)
- **Mercado Pago (Brazil):**
  - **Instant Pix on Screen:** Dynamic QR Code and Copy-and-Paste code with background polling every few seconds, releasing orders immediately upon confirmation.
  - **Credit Cards:** Installments up to 12x (with support for split payments across 2 cards).
  - **Bank Slips (Boleto).**
- **PayPal:** For international customers purchasing in foreign currencies.
- **Affiliate Webhooks:** Automatic sales listener for Hotmart, Eduzz, Monetizze, and ClickBank, creating accounts and enrolling students instantly.

### 🎨 4. Visual Layout Builder (Flex Builder)
- Design headers, footers, and central sections with a responsive 12-column CSS Grid.
- Easily choose what shows up on desktops, tablets, or smartphones.
- Dedicated e-commerce themes (like `rachidd_store`) isolate their custom headers and footers so visual builder edits don't break the bespoke design.

### 🔄 5. 1-Click System Updater & Safety
- **Direct Updates:** Inspects new releases on GitHub and updates through the admin panel, taking automated backups of both code and database before touching anything.
- **Failure Isolation (Circuit Breaker):** If an auxiliary plugin throws an unexpected error, the system isolates only that plugin and keeps the rest of the store or school online, logging the details for the admin (instead of showing a fatal 500 white screen to your customers).
- **Native Security:** Out-of-the-box CSRF defense, PDO prepared statements for SQL injection prevention, and strict HTML sanitization.

---

## 📊 Tech Stack & Numbers

- **Language:** PHP 8.2 or higher (strict-typed code with zero framework bloat).
- **Automated Tests:** **656 automated tests** in PHPUnit with **3,922 assertions** covering checkouts, security, orders, payment gateways, and routing.
- **Databases:** Works seamlessly with both **MySQL/MariaDB** and **SQLite** (switchable with zero code changes).
- **Zero Heavy Frameworks:** No heavy dependencies in the core. Runs purely on standard PHP extensions: `pdo`, `mbstring`, `zip`, `curl`, and `openssl`.

---

## 🚀 Quick Start (Local Setup)

All you need is PHP 8.2 installed on your machine:

```bash
# 1. Clone the repository
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Start PHP's built-in server
php -S localhost:8000 -t public
```

Open your browser and navigate to:  
👉 `http://localhost:8000`

The setup wizard will walk you through choosing your database and creating your admin account.

To run the automated test suite:
```bash
php phpunit.phar
```

---

## 📁 Codebase Layout

If you want to open the code and explore:

- `public/`: The web entry point (`index.php`) and static assets (CSS, JS, images).
- `src/Core/`: The kernel (router, container, event dispatcher, and resilience).
- `src/Plugins/`: Feature modules (`domain_drop` for dropshipping, `academy` for courses, `commerce` for payments, `flextheme` for visual building).
- `src/SystemApps/`: Internal utilities (admin auth, database doctor, updater).
- `themes/`: Front-end themes and layouts.
- `tests/`: Full PHPUnit test suite.

---

## 📄 License

Domain System is free open-source software licensed under the [MIT License](LICENSE). You are free to use, modify, learn from, and run your business on it.
