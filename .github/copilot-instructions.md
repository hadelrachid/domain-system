# GitHub Copilot & AI Coding Instructions — Domain-System OS (v2.5)

Always read and strictly follow [`AI_CONTRACT_GUIDE.md`](../AI_CONTRACT_GUIDE.md) at the root of this repository before generating or modifying any code.

## Core Rules Summary
1. **`declare(strict_types=1);`** at the top of every PHP file.
2. **Ring 0 vs Ring 3 Isolation**: User plugins live in `src/Plugins/<slug>/` under `namespace DomainSystem\Plugins\<slug>\...` (exact case match). Never touch or use `DomainSystem\Core` for user plugins. The OS (Ring 0) must remain 100% database-agnostic and OS-agnostic via contracts (`src/Core/Contracts/`).
3. **Zero Superglobals in Controllers/Services**: Never use `$_GET`, `$_POST`, `$_SESSION`, `$_FILES`. Always use `DomainSystem\Core\Http\Request` and `SessionManagerInterface`.
4. **Pure Constructor DI via Contracts**: Inject interfaces from `Contracts/`, never instantiate services with `new` inside controller methods, and keep HTML inside `views/*.php`.
5. **Auto-Discovered Manuals & Migrations**: Include `manual/module.php` (`ManualModuleInterface`) and ANSI SQL migrations in `migrations/`.
