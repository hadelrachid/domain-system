# Domain System (v2.8.4)

> **A lean, fast, and complete web operating platform written in clean modern PHP 8.2 — no bulky frameworks, no endless plugin bloat.**

Hi! My name is Hadel, and I am a self-taught software creator. I started building **Domain System** around a straightforward conviction: you can build an online school, an e-commerce store, and a full content management system that runs smoothly on just **~5 MB of RAM** and takes up barely **~30 MB on disk**. It spins up quickly on any standard shared hosting account or a simple $5 virtual machine.

No bloated vendor folders. No fragile house of cards made of 50 conflicting libraries.

---

[🇧🇷 Versão em Português](README.md) | [🌱 My Story](ABOUT-en.md) | [🔍 Inside the System](docs/documentacao-sistema/en/README.md) | [📚 Official Wiki](https://github.com/hadelrachid/domain-system/wiki) | [📖 Online Docs](https://hadelrachid.github.io/domain-system/) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Developer Guide](DEVELOPER_GUIDE.md) | [🤝 Community](#-i-need-your-help-join-the-project)

---

## 🏛️ The Three Pillars Architecture

To keep this main repository lightweight and fast to clone, we separated the platform into three clean pieces:

1. **The Operating Core (Domain-System Cockpit OS — This Repository):**  
   The solid foundation. It houses the system kernel, the administration cockpit, the user authentication engine, security defenses, and lightweight starter extensions (`clock_widget`, `radio_online`, and the `flextheme` layout engine).
2. **The Extensions Repository ([domain-system-plugins](https://github.com/hadelrachid/domain-system-plugins)):**  
   Where standalone business applications live:
   * **DomainDrop:** AliExpress product discovery, profit margin calculation, and 24-hour order sync.
   * **DomainCommerce:** Checkout processing with instant Pix, multi-card installments via Mercado Pago, global PayPal payments, and affiliate webhook receivers (Hotmart, Eduzz, Monetizze).
   * **Academy LMS:** Online school management with a distraction-free student portal and automatic completion certificates.
3. **The Themes Repository ([domain-system-themes](https://github.com/hadelrachid/domain-system-themes)):**  
   Where storefront themes live, such as the official **RachidD Store** dropshipping template.

You pick what your project needs and install it with a single click inside the admin panel.

---

## 🤝 I need your help: Join the project!

Because I work on this by myself most days, I use AI tools as assistant auditors to comb through lines of code looking for things human eyes might miss after midnight. Today, the project has a suite of **766 automated tests in PHPUnit with over 4,600 assertions**.

Still, let's face facts: **nothing beats the intuition, street smarts, and hands-on experience of real human developers.**

I would love to collaborate with you:
* **Security Auditors:** Try to break things! Find edge cases, spot vulnerabilities, and help us fortify the boundaries.
* **PHP Developers:** Help keep the codebase clean, legible, and maintainable.
* **Testers & Store Owners:** Run the code locally or on a server, try out checkout flows, and tell me what worked and what felt clunky.
* **Theme & Plugin Authors:** Build creative extensions using our explicit interfaces.

If you appreciate lean software that respects server resources, your Pull Request, Issue, or straightforward feedback is always welcome.

👉 [Learn how to contribute in the Developer Guide](DEVELOPER_GUIDE.md)  
🔍 [Inside Domain System — Architecture and Server Startup Walkthrough](docs/documentacao-sistema/en/README.md)

---

## 💡 What the Cockpit gives you out of the box

* **Notification Bell:**  
  The top-right bell lights up automatically whenever an official update or plugin release is published on GitHub.
* **One-Click Plugin Updates (WordPress-Style):**  
  Click "Update Now" on any extension. The system downloads the signed ZIP from GitHub Releases, checks its cryptographic SHA-256 fingerprint, and extracts it safely.
* **Safety Circuit Breaker:**  
  If a third-party plugin encounters an unhandled fatal error, the system does not crash your entire store. It turns off only that broken module, logs the details, and keeps your pages alive.
* **Visual Flex Builder:**  
  Construct custom headers, footers, and page sections using a 12-column responsive layout grid, picking what appears on laptops and what appears on mobile screens.
* **Default Security Protections:**  
  Built-in defense against CSRF attacks, type-safe SQL queries through PDO, and strict output sanitization against XSS.

---

## 📊 System Requirements & Stats

* **Language:** PHP 8.2 or higher.
* **Database:** Runs out of the box with **SQLite** (zero installation needed) or connect to **MySQL/MariaDB**.
* **Test Coverage:** **766 automated tests** covering authentication, routing, and payment lifecycles.
* **Zero Heavy Framework Dependencies:** Standard PHP extensions only (`pdo`, `mbstring`, `zip`, `curl`, `openssl`).

---

## 🚀 Running locally in 2 minutes

All you need is PHP 8.2+ installed on your computer:

```bash
# 1. Clone the repository
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Start PHP's built-in web server
php -S localhost:8000 -t public
```

Now visit in your browser:  
👉 `http://localhost:8000`

The setup wizard will pop right up so you can configure your database and create your admin account.

To run the complete test suite:
```bash
php phpunit.phar
```

---

## 📁 Directory Structure

If you want to explore the source code:

* `public/`: The web root (`index.php`) and front-end assets (CSS, JavaScript, images).
* `src/Core/`: The central engine (routing, dependency container, event bus, and update checker).
* `src/SystemApps/`: Essential internal administrative applications (login, access control, updater).
* `src/Plugins/`: Directory where installed plugins live.
* `themes/`: Location of active site themes.
* `tests/`: Automated PHPUnit test files.

---

## 📄 License

Domain System is free open-source software under the [MIT](LICENSE) license. You are welcome to use it, inspect it, modify it, and run your business on it without surprise restrictions.
