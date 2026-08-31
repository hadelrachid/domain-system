# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---
[ 🇧🇷 Ler em Português ](CHANGELOG.md)
## [1.2.0] - 2026-08-30
### Added
- **AI Hub:** New dedicated plugin for API keys management (Gemini, ChatGPT, DeepSeek, Claude).
- **Plugin Builder:** Visual plugin builder integrated with AI Hub to generate AI-based forms.
- **Clinic Pack (`clinic_pack`):** New isolated plugin to organize the "Clinical Management" menu without interfering with the Kernel.

### Fixed
- **PluginManager Circuit Breaker:** Fixed silent failure where plugin `boot()` method was not being called during initialization, plus fixes for `ordered_plugins.json` saving.

## [1.1.0] - The Era of Architectural Resilience - 2026-08-24

This update introduces critical protections to the system's core, along with resolving technical debts identified by a deep audit.

### Added
- **ATS (Automatic Transfer Switch):** A Last Breath function hooked to `register_shutdown_function` to intercept Fatal Errors and Absolute Memory Exhaustion, automatically ejecting the responsible plugin before PHP's thermal death.
- **Generic Upsert Method:** Added `upsert()` to the native SQLite `QueryBuilder`, allowing Object-Oriented "Insert On Conflict Do Update" operations.
- **Medical Record Routes:** New `GET` and `POST` routes for `/admin/appointments/record`.

### Changed
- **2FA Architecture (SOLID Principles):** Total extraction of the sending logic (Email, App) from `TwoFactorService` to their respective interfaces and autonomous provider classes (Plugs).
- **Settings Controller (`SettingsController`):** Removed raw SQL code (PDO) in favor of the new `upsert()` method from QueryBuilder.
- **Uploads Path:** Replaced hardcoded paths (`dirname(__DIR__, 4)`) with the `BASE_PATH` constant.

### Removed
- Residual garbage and loose testing scripts in the root (`test_plugins.php` and `test_record.php`).
- **Unreachable dead code:** Cleaned multiple stray `exit;` commands after returns in `ApiController`.
- Hardcoded doctor mock simulation (converted to TODO for future real integration with WordPress).

### Security and Development
- **Dev Simulator Plugin:** A new isolated plugin capable of hijacking the master email wiring in the development environment, intercepting 2FA codes and writing them to `temp/auth-2fa.txt` to prevent real user data leakage.
- **Bomb Plugin:** Malicious plugin (disabled) kept in the project as living proof to test the activation of the Circuit Breaker V2 and ATS systems.

---

## [1.0.0] - The Birth of the Organic Engine - 2026-08-23

Initial release of the Domain System.

### Added
- Master Architecture based on Kernel & Plugins.
- Custom Dependency Injection Container.
- `EventDispatcher` for decoupled communication between modules (Hooks).
- Native Router and SQLite Support.
- Inaugural base plugins: `auth`, `appointments`, `doctors`, `patients`, `settings`, `triage`, `SystemAdmin`.
- Circuit Breaker V1 (Basic) for isolating Plugin Exception failures.
- Access Control List (ACL) system focused on Workspaces (Doctors, Receptionists, Lawyers).
