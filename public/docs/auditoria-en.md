# 🔍 Domain-System Audit Report

## 🔴 CRITICAL

| # | Issue | File | Line |
|---|----------|---------|-------|
| 1 | **Infinite Loop (Memory Bomb)** — Test plugin still active in the system | `src/Plugins/bomb_plugin/Plugin.php` | 14-17 |
| 2 | **Unreachable Routes** — `record()` and `saveRecord()` exist in the Controller but have no registered route | `src/Plugins/appointments/Plugin.php` | 45-47 |
| 3 | **Direct Access to Superglobals in Controllers** — `$_SESSION`, `$_POST`, `header()` and `die()` scattered across all Controllers without a Request/Response layer | All Controllers | — |

---

## 🟡 MEDIUM

| # | Issue | File | Line |
|---|----------|---------|-------|
| 1 | **`new` without Dependency Injection** — `new TwoFactorService()`, `new AppProvider()`, `new EmailProvider()` | `src/Plugins/auth/Plugin.php` | 27, 31, 32 |
| 2 | **`new` without Dependency Injection** — `new SimulatedEmailProvider()`, `new SimulatedAppProvider()` | `src/Plugins/dev_simulator/Plugin.php` | 15, 16 |
| 3 | **`new` without Dependency Injection** — `new ReceptionWorkspace($theme)` | `src/Plugins/SystemAdmin/Plugin.php` | 22 |
| 4 | **Plugin querying tables from another Plugin** — appointments accesses `patients` and `doctors` directly | `src/Plugins/appointments/Controllers/AppointmentController.php` | 34, 35 |
| 5 | **Silent dependency between Plugins** — `dev_simulator` pulls concrete `TwoFactorService` | `src/Plugins/dev_simulator/Plugin.php` | 11 |
| 6 | **Raw SQL instead of QueryBuilder** — `INSERT INTO ... ON CONFLICT` running via raw PDO | `src/Plugins/settings/Controllers/SettingsController.php` | 28, 53, 69 |
| 7 | **Hardcoded mock data** — `syncWp()` simulates a fake JSON injected into the code | `src/Plugins/doctors/Controllers/DoctorController.php` | 139 |
| 8 | **Forgotten temporary fallback** — `'doctor_id' => $medico_id ?: 1` | `src/Plugins/appointments/Controllers/ApiController.php` | 67 |

---

## 🟢 LOW

| # | Issue | File |
|---|----------|---------|
| 1 | **Testing files dumped in root** | `test_plugins.php`, `test_record.php` |
| 2 | **Unreachable code** — `exit;` after `return` in multiple places | `src/Plugins/appointments/Controllers/ApiController.php` |
| 3 | **Hardcoded path** — `dirname(__DIR__, 4)` for `/public/uploads` | `src/Plugins/settings/Controllers/SettingsController.php` |

---

## ✅ Resolution Status
Almost all of the critical and medium debts listed above have been resolved in versions `1.1.0` and `1.2.0`.
- **Dependency Injection (Medium 1, 2, and 3):** Replaced by the Container (`$this->container->make()`).
- **Direct Access to Superglobals (Critical 3):** Successfully removed. Web communication and memory now pass purely through abstractions like `Request`, `Response`, and `SessionManager`.
- **Database Access by Controllers (Repository Pattern):** Repositories were introduced, isolating business rules and database queries.

The code is strictly maintained adhering to SOLID and DIP principles.
