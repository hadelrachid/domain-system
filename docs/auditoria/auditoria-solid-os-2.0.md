# 🔬 Auditoria Cirúrgica — Domain-System OS 2.0
**Data:** 28 de Setembro de 2026  
**Arquivos Analisados:** 32 arquivos do Core  
**Critérios:** SOLID, Alta Coesão, Baixo Acoplamento, Interfaces, Injeção de Dependências

---

## 📊 Nota Geral do Sistema

| Critério | Nota | Observação |
|---|---|---|
| **S** — Responsabilidade Única | ⚠️ 6/10 | 4 classes fazem coisa demais |
| **O** — Aberto/Fechado | ✅ 7/10 | Plugins são extensíveis, mas Router e Application são fechados |
| **L** — Substituição de Liskov | ✅ 8/10 | Contrato OS 2.0 funciona bem, mas coexiste com legado |
| **I** — Segregação de Interface | ✅ 8/10 | `OsExtensionInterface` é enxuta e perfeita; `PluginInterface` é inchada |
| **D** — Inversão de Dependência | ⚠️ 5/10 | Várias classes dependem de concretas em vez de interfaces |
| **Coesão** | ⚠️ 6/10 | Core classes acumulam responsabilidades |
| **Acoplamento** | ⚠️ 6/10 | Service Locator usado em vez de DI puro em alguns pontos |

> **Nota geral: 6.6/10** — O ecossistema de plugins (OS 2.0) está excelente. Os problemas estão concentrados no **Core interno** (Application, Router, ThemeManager, PluginBootstrapper).

---

## ✅ O QUE ESTÁ EXCELENTE (Pode celebrar!)

### 1. O Contrato OS 2.0 (`OsExtensionInterface`)
A interface é **enxuta, focada e elegante**. Apenas 2 métodos: `osRegister()` e `osBoot()`. Isso é **ISP perfeito** (Interface Segregation Principle). O desenvolvedor só precisa implementar o que importa.

### 2. O Sistema de Eventos (`EventDispatcher`)
Zero dependências externas. Implementa `EventDispatcherInterface`. Suporta prioridades. É um **Observer/Mediator puro** — o mais limpo de todo o Core. **Nota: 10/10**.

### 3. O Container de DI (`Container`)
Implementa `ContainerInterface`. Auto-resolve dependências via `ReflectionClass`. Zero acoplamento externo. **Nota: 10/10**.

### 4. A Migração dos Plugins
Todos os 16 plugins (root + bundled) assinam o contrato `OsExtensionInterface` com as duas fases de segurança. O padrão Hub/Sub-Plugin funciona perfeitamente.

### 5. O `LinkRegistry` (Conceito)
O conceito de registro de links ("tomadas") onde plugins pedem recursos e provedores os oferecem é **brilhante** e 100% alinhado com DIP.

### 6. Contratos Existentes (8 interfaces no Core)
```
src/Core/Contracts/
├── OsExtensionInterface.php     ✅ Perfeita
├── ContainerInterface.php       ✅ Usada corretamente
├── EventDispatcherInterface.php ✅ Usada corretamente  
├── RouterInterface.php          ⚠️ Existe mas pouco usada
├── CockpitRegistryInterface.php ✅ Boa
├── CockpitProviderInterface.php ✅ Boa
├── MiddlewareInterface.php      ✅ Boa
└── DashboardWidgetProviderInterface.php ✅ Boa
```

---

## 🔴 PROBLEMAS ENCONTRADOS

---

### PROBLEMA 1: `Application.php` — O "Deus" do Sistema
**Princípio violado:** SRP (Responsabilidade Única)  
**Gravidade:** 🔴 Alta  
**Arquivo:** `src/Core/Application.php`

A classe `Application` faz TUDO:
- Cria ~14 objetos com `new` diretamente
- Age como Singleton (`static $instance`)
- Configura todo o DI Container
- Orquestra o ciclo de vida
- Registra links do kernel

```php
// Dentro de Application::__construct() — 14x "new"
new SessionManager()
new PluginStateManager(...)
new PluginDiscoverer(...)
new PluginBootstrapper(...)
new PluginInstaller(...)
new PluginManager(...)
new Router(...)
new ShortcodeManager(...)
new ThemeManager(...)
new WorkspaceManager(...)
new CockpitRegistry()
new DashboardWidgetRegistry()
new LinkRegistry(...)
new OsConnector()
```

**Impacto:** Se você quiser trocar o `Router` por outro, ou substituir o `ThemeManager`, você precisa **modificar o código-fonte do Kernel**. Isso viola o Princípio Aberto/Fechado.

**Solução proposta:** Extrair um `ServiceProvider` ou `KernelBootstrapper` que registra os serviços no Container, e o `Application` apenas os consome via interface.

---

### PROBLEMA 2: `OsExtensionInterface` depende de classes concretas
**Princípio violado:** DIP (Inversão de Dependência)  
**Gravidade:** 🟡 Média  
**Arquivo:** `src/Core/Contracts/OsExtensionInterface.php`

```php
// ATUAL (depende de concretas):
public function osRegister(OsConnector $os): void;
public function osBoot(OsRuntime $runtime): void;

// IDEAL (depende de abstrações):
public function osRegister(OsConnectorInterface $os): void;
public function osBoot(OsRuntimeInterface $runtime): void;
```

O contrato do sistema (a interface mais importante) aponta para classes concretas. Se no futuro quisermos criar um `MockOsRuntime` para testes automatizados, não podemos substituir sem quebrar o contrato.

**Solução proposta:** Criar `OsConnectorInterface` e `OsRuntimeInterface`, e fazer as classes concretas implementá-las.

---

### PROBLEMA 3: `LinkRegistry` e `OsRuntime` usam `Container` concreto
**Princípio violado:** DIP  
**Gravidade:** 🟡 Média  
**Arquivos:** `src/Core/Plugin/LinkRegistry.php`, `src/Core/Plugin/OsRuntime.php`

```php
// LinkRegistry.php — ATUAL:
public function __construct(private Container $container) {}

// DEVERIA SER:
public function __construct(private ContainerInterface $container) {}
```

```php
// OsRuntime.php — ATUAL:
public function __construct(
    private Container $container,           // ❌ Concreto
    private OsConnector $connectorManifest, // ❌ Concreto
    private LinkRegistry $linkRegistry,     // ❌ Concreto
    private EventDispatcherInterface $eventDispatcher // ✅ Interface
)
```

3 de 4 dependências são concretas. Apenas o `EventDispatcher` está correto.

**Solução proposta:** Trocar os type hints para `ContainerInterface` (já existe), e criar interfaces para `OsConnector`, `OsRuntime` e `LinkRegistry`.

---

### PROBLEMA 4: `Router` tem Middlewares fixos no código
**Princípio violado:** OCP (Aberto/Fechado)  
**Gravidade:** 🟡 Média  
**Arquivo:** `src/Core/Routing/Router.php`

```php
// Router.php — HARDCODED:
$globalMiddlewares = [
    \DomainSystem\Core\Routing\Middlewares\CsrfMiddleware::class,
    \DomainSystem\Core\Routing\Middlewares\AuthMiddleware::class
];
```

Se um plugin quiser adicionar um `CorsMiddleware` ou `RateLimitMiddleware`, ele não consegue sem mexer no `Router.php`.

**Solução proposta:** Expor um método `$router->addMiddleware(MiddlewareInterface $m)` ou um hook `router.middlewares`.

Além disso, o Router resolve o `EventDispatcher` pelo **nome da classe concreta** em vez da interface:
```php
// ATUAL:
$this->container->make(EventDispatcher::class);  // ❌ Concreto

// CORRETO:
$this->container->make(EventDispatcherInterface::class);  // ✅ Interface
```

---

### PROBLEMA 5: `ThemeManager` depende de concretas
**Princípio violado:** DIP + SRP  
**Gravidade:** 🟡 Média  
**Arquivo:** `src/Core/Theme/ThemeManager.php`

```php
// ThemeManager.php:
public ?EventDispatcher $dispatcher = null;  // ❌ Propriedade pública + Concreto

public function setDispatcher(EventDispatcher $dispatcher) // ❌ Deveria ser EventDispatcherInterface
```

Além disso, o `ThemeManager` faz coisas demais (baixa coesão): resolve caminhos de template, faz fallback, renderiza via output buffer, processa shortcodes e emula funções do WordPress.

**Solução proposta:** Trocar type hint para `EventDispatcherInterface`. Considerar extrair um `TemplateRenderer` separado no futuro.

---

### PROBLEMA 6: `Request`, `Response` e `SessionManager` sem Interface
**Princípio violado:** DIP  
**Gravidade:** 🟢 Baixa (por enquanto)  
**Arquivos:** `src/Core/Http/Request.php`, `src/Core/Http/Response.php`, `src/Core/Http/SessionManager.php`

Estas 3 classes HTTP não implementam nenhuma interface. Atualmente funciona porque não há necessidade de swap, mas no futuro impede mocking para testes unitários.

**Solução proposta:** Criar `RequestInterface`, `ResponseInterface` e `SessionManagerInterface` quando necessário (baixa urgência).

---

### PROBLEMA 7: `PluginInterface` é inchada (11 métodos)
**Princípio violado:** ISP (Segregação de Interface)  
**Gravidade:** 🟢 Baixa  
**Arquivo:** `src/Core/Plugin/PluginInterface.php`

```
register, boot, activate, deactivate, uninstall,
getName, getVersion, getDependencies, isActive,
getSubPluginsPath, isCore, getDescription, setActive
```

Plugins são forçados a herdar stubs vazios para métodos que não usam. Contraste com a belíssima `OsExtensionInterface` (só 2 métodos).

**Solução proposta:** Como todos os plugins agora usam `OsExtensionInterface`, a `PluginInterface` pode ser gradualmente simplificada. Baixa urgência.

---

### PROBLEMA 8: Service Locator em vez de DI Puro
**Princípio violado:** DIP  
**Gravidade:** 🟡 Média  
**Arquivos:** Vários

Várias classes recebem o `Container` inteiro e "caçam" recursos:
```php
// Router.php:
$this->container->make(EventDispatcher::class);  // ❌ Concreto + Service Locator

// PluginBootstrapper.php:
$this->container->make(LinkRegistry::class);      // ❌ Service Locator
$this->container->make(SessionManager::class);    // ❌ Service Locator
```

O padrão correto seria receber cada dependência no construtor via Interface.

**Solução proposta:** Injetar as dependências diretamente nos construtores em vez de passar o Container inteiro.

---

## 📋 PLANO DE CORREÇÃO (Prioridade)

### 🔴 Prioridade Alta (Fundação)
| # | Ação | Arquivo |
|---|---|---|
| 1 | Criar `OsConnectorInterface` e `OsRuntimeInterface` | `src/Core/Contracts/` |
| 2 | Atualizar `OsExtensionInterface` para depender das interfaces | `src/Core/Contracts/` |
| 3 | Corrigir `LinkRegistry` → usar `ContainerInterface` | `src/Core/Plugin/` |
| 4 | Corrigir `OsRuntime` → usar `ContainerInterface` | `src/Core/Plugin/` |

### 🟡 Prioridade Média (Qualidade)
| # | Ação | Arquivo |
|---|---|---|
| 5 | Extrair `ServiceProvider` do `Application` | `src/Core/` |
| 6 | Tornar middlewares do Router configuráveis | `src/Core/Routing/` |
| 7 | `ThemeManager` → usar `EventDispatcherInterface` | `src/Core/Theme/` |
| 8 | Criar `SessionManagerInterface` | `src/Core/Contracts/` |

### 🟢 Prioridade Baixa (Futuro)
| # | Ação | Arquivo |
|---|---|---|
| 9 | Criar `RequestInterface` e `ResponseInterface` | `src/Core/Contracts/` |
| 10 | Segregar `PluginInterface` em interfaces menores | `src/Core/Plugin/` |
| 11 | Eliminar Service Locator do Router e Bootstrapper | Vários |

---

## 🏗️ MAPA DE DEPENDÊNCIAS ATUAL

```
              Application (Kernel) — "God Class"
              /    |     |     |     \
           new   new   new   new   new
            |     |     |     |     |
        Router  Theme  Boot  Link  Session
          |     Mgr    strap  Reg   Mgr
          |              |
       [CSRF]         OsConnector ──→ OsRuntime
       [Auth]              │              │
    (hardcoded)            │         EventDispatcherInterface ✅
                           │              │
                      (concrete)     (concrete)
                         ❌              ❌
```

**Legenda:**
- 🟥 `Application` = God Class (cria tudo com `new`)
- 🟧 `OsConnector`, `OsRuntime`, `LinkRegistry` = Dependem de concretas
- 🟩 `EventDispatcher`, `Container` = 100% corretos (usam interfaces)

---

## 🎯 CONCLUSÃO

O **ecossistema de plugins (OS 2.0) está impecável**. A filosofia de duas fases (Negociação → Execução), o sistema de Hubs, e a migração de todos os 16 plugins foram executados com maestria.

Os problemas estão **concentrados no Core interno** — especificamente no `Application.php` (God Class) e em algumas type hints que apontam para classes concretas em vez de interfaces. São correções cirúrgicas que não quebram funcionalidade existente.

**Veredicto:** O sistema está sólido para produção. As correções propostas são de **refinamento arquitetural**, não de emergência. Implementá-las elevará o projeto de "muito bom" para "referência de arquitetura em PHP".

---

*Auditoria realizada por Análise de Código Estático via IA — 32 arquivos do Core analisados em profundidade.*
