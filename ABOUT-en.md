# Building My Own Modular PHP Platform: Lessons Learned Along the Way

> 🇧🇷 [Ler em Português](ABOUT.md) | 🇺🇸 [Read in English](ABOUT-en.md)

---

I am a self-taught developer and, for some time now, I have been building a project called **Domain System**.

It all started with a simple question: what if I could design my own PHP web platform where core foundations and application plugins have strictly defined boundaries, without excessive coupling or brittle dependencies?

As work progressed, the codebase grew significantly. I had to solve practical architecture challenges: how to organize services, how plugins should communicate with the core runtime, how to isolate runtime errors, and how to prevent existing features from breaking unexpectedly.

I won't pretend I have all the answers. I am still learning, making mistakes, and continuously iterating to improve things. That journey of problem-solving is precisely what makes this project so rewarding to me.

You can explore the open source repository here:  
👉 [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

---

## Why I Started Thinking Differently About PHP

While designing the architecture, I became fascinated by how modern operating systems isolate core system services from user applications running on top of them.

I wondered: can we bring that architectural discipline into a modular PHP web platform?

In **Domain System**, I aim to keep two elements clearly decoupled:

- **The Core:** Fundamental services necessary for application life-cycle — dependency injection, routing, atomic storage, and event dispatching.
- **The Plugins:** Components providing business capabilities (such as the Academy LMS, visual layout builder, navigation menus, and SEO pipelines).

The idea is straightforward: the core should have zero awareness of plugin business details. Communication occurs exclusively through interfaces and contracts; this ensures one part can be refactored without breaking the rest of the application.

In practice, getting this right is much harder than it sounds. One can define interfaces and still end up with excessive coupling between concrete classes. That is an area I pay closer attention to with every iteration.

---

## Areas I Have Been Focusing On

### Safer Plugin Installation

Allowing users to upload zip packages required careful defensive design.

When extracting an archive, one cannot assume every file is safe or intended for extraction.

I introduced pre-extraction inspections that sanitize filenames and paths, guarding against *path traversal* and *Zip Slip* attacks.

While no single check guarantees immunity against every security threat, defensive measures and constant vigilance are essential practices in software engineering.

### Resilience and Graceful Recovery

Another constant challenge is handling runtime errors without leaving the system in an inconsistent or corrupted state.

*Domain System* incorporates atomic JSON persistence (`AtomicJsonStore`), exclusive file-locking (`LOCK_EX`), database schema diagnostics (`SchemaDoctorInterface`), extension-level fault isolation (Circuit Breaker), and automated pre-update backups with rollback capability.

The objective is simple: make issues easier to diagnose and recover from. Unforeseen problems will always happen in production, but building recovery paths into the architecture is better than assuming nothing will ever fail.

### Building a Custom Visual Page Builder (Flex Builder)

I have also been developing **Flex Builder**, a visual Full Site Editing (FSE) engine for headers, footers, and page sections.

A primary requirement was ensuring that the Studio live preview matched the published frontend output down to the pixel. Both environments share the exact same compilation pipeline through `ThemePartCompilerInterface`.

Additionally, the builder implements a 12-column CSS Grid with responsive visibility controls across 4 breakpoints (Desktop, Laptop, Tablet, Mobile) and automatic column readjustment (*auto-reflow*), eliminating awkward blank spaces when elements are hidden.

Building an interactive block editor involves hundreds of subtle edge cases, making it a tremendous learning experience.

### Leveraging AI Without Losing Control of the Codebase

I use AI coding tools as an accelerator throughout development.

They are remarkably productive, especially across a repository spanning tens of thousands of lines. However, practical experience taught me that code that works in isolation does not necessarily integrate well into an existing architecture.

An AI-generated snippet might resolve an immediate bug while introducing hidden coupling that makes future changes painful.

To prevent architectural drift, I created strict contract guidelines ([`AI_CONTRACT_GUIDE.md`](AI_CONTRACT_GUIDE.md)) and automated architectural tests (`UpdateArchitectureTest`, `RingIsolationTest`). I also built a CLI generator (`php os-cli.php make:plugin`) to bootstrap plugins with proper contract compliance.

I continue to explore how best to leverage AI without sacrificing maintainability. Detailed documentation helps, but human oversight, thorough code reviews, and automated test suites remain irreplaceable.

---

## The Tech Stack

Domain System is built on **PHP 8.2+**. It natively supports MySQL/MariaDB and SQLite via PDO, along with concurrency-safe atomic JSON persistence.

The test suite is powered by **PHPUnit**, currently featuring **438 unit tests** and over **2,320 automated assertions** covering lifecycle, security guards, routing, contracts, and visual compilation.

Test metrics provide confidence, but they don't tell the whole story. Green tests are important, but writing code that is clear, well-structured, and maintainable over time is what truly matters.

---

## Sharing to Learn and Grow

Building a platform from scratch is challenging; keeping it clean, secure, and comprehensible as it grows is an even greater challenge.

Honest feedback, constructive critiques, and architectural discussions from fellow developers are always genuinely appreciated!
