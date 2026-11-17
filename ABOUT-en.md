# Building a Web Platform from Scratch: Why I Started It and Why I Need Your Help

> 🇧🇷 [Ler em Português](ABOUT.md) | 🇺🇸 [Read in English](ABOUT-en.md)

---

Hi! My name is Hadel, and I am a self-taught developer.

Some time ago, I decided to tackle a big personal challenge: building my own web operating environment and platform in pure, modern PHP, named **Domain System**.

It all started from frustration and a simple question: why do we nowadays need multi-gigabyte frameworks with hundreds of third-party packages, or traditional CMSs that chew through hundreds of megabytes of RAM and break every time you install an unvetted plugin, just to launch an e-commerce store or an online school?

I wanted the exact opposite: a straightforward, ultra-lightweight system (taking up **~30 MB on disk** and using **~5 MB of RAM** per request), fast, secure, and running smoothly even on the cheapest shared hosting.

The source code is completely open for everyone to inspect and test on GitHub:  
👉 [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

---

## No Fluff: I Am Self-Taught and Learn by Doing

I don't have a computer science degree, and I don't work at a big tech company. Everything I know comes from reading documentation, breaking things on real servers, spending late hours hunting down bugs, and rewriting code until it actually works well.

Because I don't have a large engineering team beside me, I use modern **Artificial Intelligence models (like Claude, ChatGPT, Nemotron, and DeepSeek) as my daily code audit partners**.

My day-to-day workflow is simple:
1. I think through the problem, design the solution, and write the code.
2. Then, I set different AI models to **relentlessly audit the code line by line**.
3. I ask them to act as tough code reviewers: hunting for race conditions, SQL injection risks, CSRF flaws, unhandled business edge cases, redundancies, and dead code.
4. For every issue discovered, I write regression tests in PHPUnit to make sure that bug can never quietly sneak back in.

Today, this daily habit has produced a test suite of **656 automated tests with 3,922 assertions** covering checkouts, stock control, encryption, payment webhooks, and core security.

Yet, here is the honest truth: **AI has strict limits. It is great at pattern matching, but it lacks the real-world intuition, experience, and sanity checks of an experienced human programmer.**

---

## Why I Need a Community of Developers

No developer working alone, no matter how dedicated, can anticipate every real-world scenario of a system operating at scale.

That is why I am being transparent and making this **open call**:

I genuinely need people to join forces with me to make Domain System even more stable, secure, lightweight, and fast.

### How can you help?
- **Audit the code:** Look at the repository with a critical eye. If you spot a line that looks brittle or a piece of logic that could fail, open an *Issue* or submit a *Pull Request*. Honest, direct critiques are deeply appreciated!
- **Review the architecture:** The system relies on explicit interfaces and dependency injection, but sometimes I wonder if a component could be cleaner or if we are overcomplicating what should be simple. Your technical opinion is gold to me.
- **Test in real-world environments:** Run the system on different setups (Nginx, Apache, Docker, VPS, cheap shared hosting) and try using it for real. Found a warning or did something break? Let me know.
- **Contribute code:** If you'd like to build a new plugin, improve theme capabilities, or refine payment gateways, the project is 100% free software (MIT License).

---

## What the System Does Today (Without the Marketing Speak)

- **Kernel with zero third-party dependencies:** Fast routing, dependency injection, and event dispatching without pulling in heavy Composer packages.
- **Dropshipping Engine with AliExpress API (DomainDrop):** Live freight calculation, automated order placement, daily deal rotation, and a full customer area with parcel tracking.
- **Online Course Platform (Academy LMS):** Video lessons, a built-in lightweight PDF reader, and automated certificate generation.
- **Checkout & Payments (DomainCommerce):** Instant Pix, credit cards up to 12x via Mercado Pago, PayPal for international sales, and affiliate webhooks (Hotmart, Eduzz, etc.).
- **Visual Page Builder (Flex Builder):** Drag-and-drop 12-column CSS Grid for building responsive headers, footers, and page sections for desktop and mobile.
- **Fault Tolerance (Circuit Breaker):** If an auxiliary plugin throws an unexpected error in production, it won't crash your whole site with a white screen of death. It isolates only the broken plugin, logs the issue, and keeps the rest of your business online.

---

## Let's Connect!

If you like the idea of getting back to what PHP does best — being simple, direct, fast, and running anywhere without friction —, come be a part of this.

- GitHub Repository: [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)
- Personal Email: `hadelrachid@gmail.com`

Every contribution, suggestion, or honest critique is truly welcome. Thanks for reading!
