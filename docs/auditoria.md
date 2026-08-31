# 🔍 Auditoria do Domain-System

## 🔴 CRÍTICOS

| # | Problema | Arquivo | Linha |
|---|----------|---------|-------|
| 1 | **Loop Infinito (Memory Bomb)** — Plugin de teste ainda ativo no sistema | `src/Plugins/bomb_plugin/Plugin.php` | 14-17 |
| 2 | **Rotas Inacessíveis** — `record()` e `saveRecord()` existem no Controller mas não há rota registrada para eles | `src/Plugins/appointments/Plugin.php` | 45-47 |
| 3 | **Acesso Direto a Superglobais em Controllers** — `$_SESSION`, `$_POST`, `header()` e `die()` espalhados por todos os Controllers sem camada de Request/Response | Todos os Controllers | — |

---

## 🟡 MÉDIOS

| # | Problema | Arquivo | Linha |
|---|----------|---------|-------|
| 1 | **`new` sem Injeção de Dependência** — `new TwoFactorService()`, `new AppProvider()`, `new EmailProvider()` | `src/Plugins/auth/Plugin.php` | 27, 31, 32 |
| 2 | **`new` sem Injeção de Dependência** — `new SimulatedEmailProvider()`, `new SimulatedAppProvider()` | `src/Plugins/dev_simulator/Plugin.php` | 15, 16 |
| 3 | **`new` sem Injeção de Dependência** — `new ReceptionWorkspace($theme)` | `src/Plugins/SystemAdmin/Plugin.php` | 22 |
| 4 | **Plugin consultando tabelas de outro Plugin** — appointments acessa `patients` e `doctors` diretamente | `src/Plugins/appointments/Controllers/AppointmentController.php` | 34, 35 |
| 5 | **Dependência silenciosa entre Plugins** — `dev_simulator` puxa `TwoFactorService` concretamente | `src/Plugins/dev_simulator/Plugin.php` | 11 |
| 6 | **SQL Bruto ao invés do QueryBuilder** — `INSERT INTO ... ON CONFLICT` rodando via PDO cru | `src/Plugins/settings/Controllers/SettingsController.php` | 28, 53, 69 |
| 7 | **Mock de dados fixos hardcoded** — `syncWp()` simula um JSON falso injetado no código | `src/Plugins/doctors/Controllers/DoctorController.php` | 139 |
| 8 | **Fallback provisório esquecido** — `'doctor_id' => $medico_id ?: 1` | `src/Plugins/appointments/Controllers/ApiController.php` | 67 |

---

## 🟢 BAIXOS

| # | Problema | Arquivo |
|---|----------|---------|
| 1 | **Arquivos de teste largados na raiz** | `test_plugins.php`, `test_record.php` |
| 2 | **Código inatingível** — `exit;` após `return` em vários lugares | `src/Plugins/appointments/Controllers/ApiController.php` |
| 3 | **Caminho hardcoded** — `dirname(__DIR__, 4)` para `/public/uploads` | `src/Plugins/settings/Controllers/SettingsController.php` |

---

## ✅ Status de Correção
Quase a totalidade dos débitos críticos e médios listados acima já foi resolvida nas versões `1.1.0` e `1.2.0`.
- **Injeção de Dependências (Médios 1, 2 e 3):** Substituídos pelo Container (`$this->container->make()`).
- **Acesso direto a Superglobais (Crítico 3):** Removidos com sucesso. A comunicação web e a memória agora passam puramente por abstrações como `Request`, `Response` e `SessionManager`.
- **Acesso a banco por Controllers (Repository Pattern):** Repositórios foram introduzidos, isolando regras de negócio e consultas ao banco.

O código é mantido rigorosamente aderente aos princípios SOLID e DIP.
