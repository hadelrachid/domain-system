# Domain System (v2.7.0)

**Domain System** is a lightweight CMS, autonomous dropshipping / e-commerce platform (**DomainDrop**), and online learning platform (**Academy LMS**) built with **PHP 8.2+**. It was designed with a simple goal in mind: fast, lightweight, and easy to maintain, without the bloat, slowness, and endless plugin maintenance of traditional CMS platforms like WordPress.

The system uses around **5 MB of RAM** per request and takes up only **~30 MB of disk space**, running smoothly on any inexpensive VPS or standard shared hosting (such as Hostinger) with PHP and MySQL or SQLite.

---

[🇧🇷 Versão em Português](README.md) | [📖 Documentation](https://hadelrachid.github.io/domain-system/) | [🌱 About the Project](ABOUT-en.md) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Developer Guide](DEVELOPER_GUIDE.md)

---

## 💡 What's Included Out of the Box?

Domain System comes ready to launch dropshipping stores, physical e-commerce checkouts, websites, and online schools:

### 📦 1. Autonomous Dropshipping & E-Commerce with AliExpress SDK (DomainDrop)
- **Official AliExpress Open Platform SDK Integration:** Cryptographically signed communication via the `TOP` protocol (`HMAC-SHA256`) and OAuth 2.0 for product lookup (`aliexpress.ds.product.get`), recommendation feeds (`aliexpress.ds.recommend.feed.get`), freight calculation (`aliexpress.logistics.buyer.freight.get`), automated order placement (`aliexpress.trade.buy.placeorder`), and real-time logistics tracking (`queryTrackingInfo`).
- **Autonomous Mining & 24-Hour Flash Cycle:** Smart product curation prioritizing local Brazil warehouse inventory for fast shipping, dynamic `USD → BRL` currency conversion with configurable profit margins, AI-powered copywriting enrichment, and a daily 24h flash rotation synchronized with the storefront countdown timer.
- **Complete Customer Portal (`/minha-conta`, `/entrar`, `/cadastro`):**
  - **🛰️ Built-In Shipment Tracking:** Interactive 5-stage delivery progress bar directly inside the customer account with instant links to **Correios**, **Cainiao Global**, and live AliExpress SDK tracking events.
  - **💬 Complaints, Notes & Questions:** Ticket submission with secure photo and PDF uploads (up to 4 files with magic-byte validation) and **optional order or catalog product linking** — allowing buyers to reference a specific product or order code even before payment settlement.
- **Admin Customer CRM, SLA Monitoring & Bilingual Supplier Mediation (`/admin/dropship`):**
  - **👥 Customers & Buyers (`/admin/dropship/customers`):** CRM directory with search by name/email/CPF, order & ticket metrics, account status controls, and recent customer tickets.
  - **📦 Orders & 1-Click Supplier Mediation (`/admin/dropship/orders`):** Delivery SLA progress bars and a single-click mediation button that automatically translates customer disputes into **Simplified Chinese (`zh-CN`)** for international suppliers (`CN`) or keeps **Portuguese (`pt-BR`)** for domestic suppliers (`BR`).

### 🎓 2. Online Course Platform (Academy LMS)
- **Student Dashboard:** Clean, secure login and registration with intuitive navigation.
- **Course & Lesson Structure:** Courses organized into modules and sequential lessons with real-time progress tracking (progress bar and completed lesson marks).
- **Flexible Lesson Content:** Video embedding (YouTube, Vimeo, or direct video URLs), structured text blocks, and an **in-browser PDF lesson reader** (lightweight canvas mode or native viewer).
- **Automated Certificates:** Automatic certificate generation as soon as a student completes 100% of the lessons.

### 💳 3. Payments & Checkout (DomainCommerce)
- **Mercado Pago Integration (Brazil):**
  - **Instant Pix on Screen:** Generates the dynamic QR Code and copy-paste code directly on the checkout screen, with auto-polling every 3 seconds to confirm payment and instantly fulfill orders or enroll students.
  - **Credit Cards:** Installments up to 12x (including split payment across two different credit cards).
  - **Bank Slips (Boleto)** and official Checkout Pro.
- **PayPal Integration (Global Sales):** International checkout with PayPal balance or global credit cards and automatic buyer protection.
- **Affiliate Webhooks:** Automatic processing of sales notifications from Hotmart, Eduzz, Monetizze, and ClickBank, creating the user and granting course access on the spot.

### 🎨 4. Visual Layout Builder (Flex Builder) & Native Theme Isolation
- Design headers, footers, and page sections using a flexible 12-column CSS Grid.
- **Responsive Menus & Mobile Drawer:** Create multi-level navigation trees with horizontal drag-and-drop indentation and a mobile side drawer with 8 hamburger button styles.
- **Per-Device Visibility with Auto-Reflow:** Easily decide which blocks appear on Desktop, Laptop, Tablet, or Mobile screens.
- **Automatic Native Theme Isolation (`"flex_builder": false`):** Dedicated e-commerce themes (such as `rachidd_store`) keep their native header and footer isolated from Flex Builder part overrides, displaying a clear informational notice in the admin editor.

### 🔄 5. 1-Click System Updater
- Update the system directly from the admin panel, pulling verified releases from GitHub Releases.
- Integrity verification using SHA-256 checksums and automated database and code backups before applying any updates.

### 🛡️ 6. Resilience & The Circuit Breaker (Why Does It Exist?)
- **Addressing Developer Feedback on Circuit Breakers in PHP:** Some developers reasonably ask: *"Why implement a Circuit Breaker pattern for PHP plugins when standard practice is simply letting exceptions bubble up?"*. That is a completely fair critique.
- **The Practical Rationale:** In a production environment (such as an online store with buyers at checkout or a school where students are actively watching lessons), if an auxiliary plugin throws an unhandled error, **the entire site shouldn't crash with a fatal 500 white screen**.
- **The Balance We Struck:**
  - The Circuit Breaker **never hides errors**: it temporarily disables only the faulty plugin and records full stack traces in the error logs and inside the **System Supervision** dashboard (`/admin/supervision`) for prompt investigation.
  - In **Development Mode (`dev`)**, exceptions bubble up immediately with full stack traces on screen for smooth debugging.
  - In **Production**, the priority is business availability and keeping buyers and students served without disruption.
- **Built-in Security:** Automatic CSRF token validation, SQL injection prevention via PDO prepared statements, and strict HTML sanitization against XSS.

---

## 📊 Telemetry and Automated Tests

- **Language:** PHP 8.2 or higher (clean, modern code with `strict_types=1`).
- **Test Suite:** **629 automated unit, architectural, and integration tests** in PHPUnit with **3,619 assertions** covering security, DomainDrop (AliExpress SDK, SLA, disputes, and customers), checkout flows, payment gateways, student enrollments, and routing.
- **Databases:** Seamless support for both **MySQL** and **SQLite**.
- **External Dependencies:** Zero bloated framework dependencies in the core. Runs strictly on standard PHP extensions: `pdo`, `mbstring`, `zip`, `curl`, and `openssl`.

---

## 🚀 Quick Start (Local Setup)

### Requirements
- PHP 8.2 or higher with standard extensions: `pdo`, `mbstring`, `zip`, `curl`, `openssl` (and `pdo_mysql` or `pdo_sqlite`).
- Web server (Apache, Nginx, or the built-in PHP development server).

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Start PHP built-in server
php -S localhost:8000 -t public
```

Open your browser and navigate to:
👉 `http://localhost:8000`

The setup wizard will appear to guide you through database setup and creating your initial admin account.

---

## 🧪 Running the Tests

To run the full automated test suite with PHPUnit:

```bash
php phpunit.phar
```

---

## 📁 Directory Structure

For developers exploring the codebase:

- `public/`: Web server document root (`index.php`) and static assets (CSS, JS, images).
- `src/Core/`: Micro-kernel (routing, HTTP cache policies, dependency injection container, and event dispatcher).
- `src/SystemApps/`: Core infrastructure modules (auth, database, admin panel, and updater).
- `src/Plugins/`: Feature modules (`domain_drop` AliExpress SDK Dropshipping, `academy` LMS, `commerce` Checkout, `flextheme` Themes & Flex Builder, `pages`, and `seo`).
- `themes/`: Front-end themes and layouts.
- `tests/`: Automated PHPUnit test suite.

---

## 📄 License

This project is open-source software licensed under the [MIT license](LICENSE).
