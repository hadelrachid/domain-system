# Domain System (v2.6)

**Domain System** is a lightweight CMS and online learning platform (LMS) built with **PHP 8.2+**. It was designed with a simple goal in mind: fast, lightweight, and easy to maintain, without the bloat, slowness, and endless plugin maintenance of traditional CMS platforms like WordPress.

The system uses around **5 MB of RAM** per request and takes up only **~30 MB of disk space**, running smoothly on any inexpensive VPS or standard shared hosting with PHP and MySQL or SQLite.

---

[🇧🇷 Versão em Português](README.md) | [📖 Documentation](https://hadelrachid.github.io/domain-system/) | [🌱 About the Project](ABOUT-en.md) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Developer Guide](DEVELOPER_GUIDE.md)

---

## 💡 What's Included Out of the Box?

Domain System comes ready to launch websites, blogs, e-commerce checkouts, and online schools:

### 🎓 1. Online Course Platform (Academy)
- **Student Dashboard:** Clean, secure login and registration with intuitive navigation.
- **Course & Lesson Structure:** Courses organized into modules and sequential lessons with real-time progress tracking (progress bar and completed lesson marks).
- **Flexible Lesson Content:** Video embedding (YouTube, Vimeo, or direct video URLs), structured text blocks, and an **in-browser PDF lesson reader** (lightweight canvas mode or native viewer).
- **Automated Certificates:** Automatic certificate generation as soon as a student completes 100% of the lessons.

### 💳 2. Payments & Checkout (DomainCommerce)
- **Mercado Pago Integration (Brazil):**
  - **Instant Pix on Screen:** Generates the dynamic QR Code and copy-paste code directly on the checkout screen, with auto-polling every 3 seconds to confirm payment and instantly enroll the student.
  - **Credit Cards:** Installments up to 12x (including split payment across two different credit cards).
  - **Bank Slips (Boleto)** and official Checkout Pro.
- **PayPal Integration (Global Sales):** International checkout with PayPal balance or global credit cards and automatic buyer protection.
- **Affiliate Webhooks:** Automatic processing of sales notifications from Hotmart, Eduzz, Monetizze, and ClickBank, creating the user and granting course access on the spot.

### 🎨 3. Visual Layout Builder (Flex Builder)
- Design headers, footers, and page sections using a flexible 12-column CSS Grid.
- **Responsive Menus:** Create multi-level navigation trees with horizontal drag-and-drop indentation.
- **Mobile Drawer:** Highly customizable mobile side drawer with multiple hamburger button styles.
- **Per-Device Visibility:** Easily decide which blocks appear on Desktop, Laptop, Tablet, or Mobile screens.

### 🔄 4. 1-Click System Updater
- Update the system directly from the admin panel, pulling verified releases from GitHub Releases.
- Integrity verification using SHA-256 checksums and automated database and code backups before applying any updates.

### 🛡️ 5. Stability & Resilience
- **Fault Isolation (Circuit Breaker):** If an extension throws an unexpected error, the system isolates the failure so the rest of the website stays online.
- **Built-in Security:** Automatic CSRF token validation, SQL injection prevention via PDO prepared statements, and strict HTML sanitization against XSS.

---

## 📊 Telemetry and Automated Tests

- **Language:** PHP 8.2 or higher (clean, modern code with `strict_types=1`).
- **Test Suite:** **536 automated unit and integration tests** in PHPUnit with **2,999 assertions** covering security, checkout flows, payment gateways, student enrollments, and routing.
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
- `src/Core/`: Micro-kernel (routing, dependency injection container, and event dispatcher).
- `src/SystemApps/`: Core infrastructure modules (auth, database, admin panel, and updater).
- `src/Plugins/`: Feature modules (Academy LMS, DomainCommerce, theme tools, and SEO).
- `themes/`: Front-end themes and layouts.
- `tests/`: Automated PHPUnit test suite.

---

## 📄 License

This project is open-source software licensed under the [MIT license](LICENSE).
