> 🇧🇷 [Ler em Português](#-controle-de-acesso-centralizado-declarative-acl) | 🇺🇸 [Read in English](#-centralized-access-control-declarative-acl)

---

# 🇧🇷 Controle de Acesso Centralizado (Declarative ACL)

Em arquiteturas convencionais em PHP, é comum encontrar validações de acesso repetidas manualmente dentro de cada método de controlador:

```php
// Padrão não recomendado: checagem manual espalhada nos controladores
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Acesso Negado');
}
```

Essa abordagem dilui a responsabilidade de autorização, viola o princípio de **Responsabilidade Única (SRP)** e dificulta a manutenção do sistema quando novos perfis ou permissões são introduzidos.

Para resolver essa questão, o **Domain System** centraliza a verificação de permissões diretamente na camada de **Roteamento (Router)**, utilizando uma abordagem **declarativa**.

---

## 🚦 Verificação Declarativa de Rotas

Ao registrar rotas em um módulo ou extensão (`Plugin.php`), o desenvolvedor declara a lista de perfis ou capacidades requeridas diretamente na definição da rota:

```php
// Rotas com papéis declarados no registro
$router->addRoute('GET', '/admin/settings', [SettingsController::class, 'index'], 'settings', ['admin']);
$router->addRoute('GET', '/admin/reports',  [ReportsController::class, 'index'],  'reports',  ['admin', 'manager']);
```

---

## ⚙️ Ciclo de Execução e Validação Antecipada

Antes de instanciar o controlador ou resolver injeções de dependência, o roteador realiza a validação de acesso:

1. **Leitura dos Requisitos da Rota:** O roteador inspeciona a lista de papéis e permissões exigidos pela rota solicitada.
2. **Validação da Sessão:** A sessão é verificada através do contrato `SessionManagerInterface`, evitando o acesso direto a superglobais (`$_SESSION`).
3. **Checagem de Permissões:** O sistema compara os dados do usuário autenticado com as permissões exigidas via `IdentityManager`.
4. **Interrupção Preventiva (403 Forbidden):** Caso o usuário não possua os privilégios necessários, o roteador interrompe o fluxo imediatamente, emitindo a resposta HTTP `403 Forbidden` sem carregar o controlador em memória.
5. **Proteção de Rotas do Núcleo:** O roteador impede a sobrescrita acidental de rotas reservadas do sistema (`/login`, `/admin/*`), lançando `RouteAlreadyRegisteredException` se uma extensão tentar registrá-las novamente.

Dessa forma, os controladores mantêm o foco exclusivo nas regras de negócio da aplicação.

---

<br><br><br><hr>

# 🇺🇸 Centralized Access Control (Declarative ACL)

In traditional PHP applications, access validations are often repeated across controller actions:

```php
// Anti-pattern: manual session checks scattered across controllers
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access Denied');
}
```

This approach scatters authorization concerns, violates the **Single Responsibility Principle (SRP)**, and increases maintenance overhead whenever permissions or roles change.

To address this, **Domain System** centralizes authorization directly within the **Routing layer**, using a **declarative** configuration model.

---

## 🚦 Declarative Route Definitions

When registering routes in an extension or module (`Plugin.php`), allowed roles or capabilities are declared directly in the route registration:

```php
// Routes with declared role requirements
$router->addRoute('GET', '/admin/settings', [SettingsController::class, 'index'], 'settings', ['admin']);
$router->addRoute('GET', '/admin/reports',  [ReportsController::class, 'index'],  'reports',  ['admin', 'manager']);
```

---

## ⚙️ Execution Lifecycle & Early Authorization

Before instantiating the controller or resolving dependency injection bindings, the router evaluates access rules:

1. **Route Requirement Check:** The router inspects the roles and capabilities declared for the target route.
2. **Session Verification:** Current session state is retrieved via `SessionManagerInterface`, preventing unmanaged direct access to `$_SESSION`.
3. **Capability Matching:** Authenticated user capabilities are verified through `IdentityManager`.
4. **Preemptive Guard (403 Forbidden):** If privileges do not match, the request halts immediately with an HTTP `403 Forbidden` response, avoiding unnecessary controller initialization.
5. **Core Route Protection:** Core routes (`/login`, `/admin/*`) are protected against accidental overrides by third-party extensions, throwing `RouteAlreadyRegisteredException` on collision.

This design keeps controllers clean, testable, and strictly focused on application business logic.
