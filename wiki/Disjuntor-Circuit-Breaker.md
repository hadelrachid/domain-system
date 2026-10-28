> 🇧🇷 [Ler em Português](#-disjuntor-circuit-breaker-e-resiliência-de-extensões) | 🇺🇸 [Read in English](#-circuit-breaker-and-extension-resilience)

---

# 🇧🇷 Disjuntor (Circuit Breaker) e Resiliência de Extensões

Em aplicações modulares, a falha não tratada de uma extensão (como erro de sintaxe, dependência ausente ou falha de conexão de rede) pode interromper o ciclo de vida da requisição, causando uma tela de erro fatal global.

Para manter a estabilidade do sistema principal, o **Domain System** implementa o padrão de resiliência **Circuit Breaker** (Disjuntor) no gerenciamento e na execução de plugins.

---

## 🛡️ Contenção de Falhas no Ciclo de Boot

O núcleo do sistema não assume que todas as extensões são infalíveis. O carregamento de plugins (`PluginManager`) é executado dentro de blocos de captura de exceções:

1. **Captura Antecipada:** Se uma extensão lançar uma exceção não tratada durante sua inicialização, o erro é interceptado antes de comprometer o restante da aplicação.
2. **Desativação Temporária:** A extensão causadora da falha é temporariamente desarmada no registro em memória e marcada para inspeção.
3. **Registro de Incidentes:** Um log estruturado é registrado e fica disponível no painel administrativo em **Supervisão do Sistema** (`/admin/supervision`).
4. **Continuidade da Operação:** As demais extensões independentes e os serviços essenciais do sistema continuam executando normalmente.

---

## 🌊 Resolução de Dependências e Desativação em Cascata

Quando uma extensão depende diretamente de outro módulo que falhou ou foi desativado, o carregamento dependente também falharia. Para evitar erros em sequência, o sistema realiza a desativação preventiva em cascata:

```php
// Resolução de dependências no PluginManager
try {
    $this->resolveNode($plugin, $resolved, $unresolved);
} catch (Exception $e) {
    // Caso a dependência necessária não esteja disponível, desativa a extensão dependente
    $this->disable($plugin->getName());
    error_log("Cascata: Plugin '{$plugin->getName()}' desativado. Motivo: " . $e->getMessage());
    $unresolved = [];
}
```

Essa ordenação topológica previne inconsistências de estado e assegura que nenhuma extensão tente interagir com dependências inativas.

---

## ⚙️ Isolamento por Rota e Modo Desenvolvedor

A partir da versão **v2.5.0**, o mecanismo de contenção opera com maior flexibilidade operacional:

- **Isolamento por Rota com Limite de Falhas (`recordRouteFailure`):**
  Uma falha pontual em uma ação específica (por exemplo, um endpoint com dados inconsistentes) **não desativa o plugin inteiro de imediato**. O sistema contabiliza as ocorrências sucessivas; a extensão só é desativada globalmente se atingir o limiar de tolerância configurado (padrão: 3 falhas consecutivas) ou se falhar durante a fase de inicialização (boot).
- **Persistência Segura (`AtomicJsonStore`):**
  O estado das extensões desarmadas é persistido com bloqueio exclusivo (`LOCK_EX`) e gravação atômica, evitando corrupção de arquivos em ambientes com requisições concorrentes.
- **Modo Desenvolvedor (Bypass em Sessão):**
  Quando uma extensão é desarmada preventivamente, o administrador pode ativar o **Modo Desenvolvedor** no painel de Supervisão. Isso contorna o bloqueio exclusivamente para a sessão autenticada do desenvolvedor (`SessionManagerInterface`), permitindo depurar o código diretamente sem expor erros aos visitantes públicos.

---

<br><br><br><hr>

# 🇺🇸 Circuit Breaker and Extension Resilience

In modular applications, unhandled errors within an extension (such as syntax errors, missing dependencies, or external service timeouts) can disrupt the application request lifecycle, resulting in fatal error pages.

To preserve system uptime, **Domain System** adopts the **Circuit Breaker** pattern to isolate and manage extension runtime faults.

---

## 🛡️ Boot-Time Fault Containment

The core runtime initializes third-party extensions within controlled exception boundaries (`PluginManager`):

1. **Early Interception:** If an extension throws an unhandled exception during its registration or boot phase, the failure is caught before impacting the application pipeline.
2. **Temporary Disarming:** The offending extension is temporarily disarmed in the memory registry and flagged for administrative review.
3. **Incident Logging:** Detailed error diagnostics are recorded in the supervision logs, accessible via the administration dashboard (`/admin/supervision`).
4. **Service Continuity:** Core services and healthy extensions continue normal execution uninterrupted.

---

## 🌊 Dependency Resolution & Cascading Deactivation

When an extension requires another module that has been disarmed or failed to initialize, continuing execution would trigger subsequent errors. The core prevents this through cascading dependency resolution:

```php
// Dependency tree resolution in PluginManager
try {
    $this->resolveNode($plugin, $resolved, $unresolved);
} catch (Exception $e) {
    // If a prerequisite module is unavailable, deactivate the dependent extension
    $this->disable($plugin->getName());
    error_log("Cascade: Plugin '{$plugin->getName()}' disabled. Reason: " . $e->getMessage());
    $unresolved = [];
}
```

Topological dependency checking guarantees that inactive or missing components do not produce cascading runtime crashes.

---

## ⚙️ Route-Level Isolation & Developer Debugging Mode

Starting with **v2.5.0**, failure containment provides granular operational controls:

- **Route-Level Thresholds (`recordRouteFailure`):**
  An isolated exception in a specific route does not immediately bring down the entire plugin. The system tracks consecutive route failures, only disarming the whole extension if the error threshold is reached (default: 3 consecutive failures) or if a failure occurs during system boot.
- **Atomic Configuration Persistence (`AtomicJsonStore`):**
  The disarmed extension registry is safely persisted using exclusive file locking (`LOCK_EX`) and atomic replacement, preventing file corruption under concurrent traffic.
- **Session-Scoped Developer Mode:**
  If an extension is tripped by the circuit breaker, administrators can enable **Developer Mode** in the Supervision panel. This temporarily lifts the bypass strictly for the administrator's active session (`SessionManagerInterface`), allowing live debugging while keeping public visitors protected from errors.
