> 🇧🇷 [Ler em Português](#-o-disjuntor-circuit-breaker-e-o-efeito-cascata) | 🇺🇸 [Read in English](#-the-circuit-breaker-and-the-cascade-effect)

---

# 🇧🇷 ⚡ O Disjuntor (Circuit Breaker) e o Efeito Cascata

Em sistemas tradicionais monolíticos, se um pequeno módulo falha (como o módulo de chat ou de relatórios), ele costuma gerar um **Fatal Error** na memória do PHP. Isso resulta na famosa "Tela Branca da Morte", derrubando o sistema inteiro e impedindo que todos os funcionários da sistema trabalhem.

Para evitar cenários catastróficos, o **Domain-System** adota um padrão de resiliência herdado da engenharia elétrica e de microserviços: o **Circuit Breaker** (Disjuntor).

## 🛡️ Como funciona a Contenção de Falhas

O Kernel (SystemAdmin) não confia cegamente nos plugins (módulos). Ele possui um gerenciador central (`PluginManager`) que atua como um quadro de energia (QTA).

Quando o sistema está inicializando a árvore de plugins, o processo acontece dentro de uma redoma de segurança (blocos `try/catch`). Se um plugin defeituoso tentar quebrar o sistema (seja por um erro de sintaxe, conexão rejeitada com a API, ou arquivo ausente), o Disjuntor percebe a sobrecarga e **desarma automaticamente**.

### O Fluxo do Desarme:
1. **Interceptação:** O erro crítico é capturado antes de chegar ao navegador do usuário.
2. **Isolamento:** O módulo defeituoso é bloqueado temporariamente na memória.
3. **Log de Supervisão:** Um relatório de incidente é gerado e registrado silenciosamente no painel de Monitoramento (Supervisão).
4. **Sobrevivência:** O restante do sistema ignora o módulo defeituoso e termina de carregar, permitindo que a sistema continue operando.

## 🌊 O Efeito Cascata (Cascade Failure Handling)

Um dos maiores desafios de um sistema modular são as **dependências**. O que acontece se o módulo de "Prontuários" precisa estritamente do módulo "Autenticação", mas a Autenticação sofreu uma pane e o disjuntor a desarmou?

Se o sistema tentasse carregar os Prontuários, ele quebraria de qualquer forma pela ausência do sistema de segurança. Para resolver isso, implementamos o **Desligamento em Cascata**.

Quando a árvore de dependências (Dependency Resolver) está sendo mapeada, se o `PluginManager` percebe que um módulo depende de algo que "morreu", ele age com inteligência artificial instintiva:
> *"O módulo A precisa do B. O B está desativado. Logo, ejetar o módulo A preventivamente para salvar o Kernel!"*

```php
// Trecho real do Core (PluginManager.php) ilustrando a Cascata
try {
    $this->resolveNode($plugin, $resolved, $unresolved);
} catch (Exception $e) {
    // Se o plugin exigia uma dependência morta, ejetamos este plugin também (Efeito Cascata).
    $this->disable($plugin->getName());
    error_log("Cascata: Plugin '{$plugin->getName()}' desativado. Motivo: " . $e->getMessage());
    $unresolved = []; // Limpa o rastro para proteger o próximo plugin
}
```

Essa abordagem garante que uma falha em cadeia seja contida com elegância, provando que o Kernel é uma muralha indestrutível frente às instabilidades de código ou infraestrutura.

## 🛠️ Isolamento Granular por Rota (Threshold) & Modo Desenvolvedor
Na **v2.3.0**, o Disjuntor opera com granularidade cirúrgica e persistência atômica (`AtomicJsonStore` com `flock` + `rename`):
- **Disjuntor por Rota com Threshold (`recordRouteFailure`):** Uma falha isolada em uma rota específica (ex: `/admin/academy/edit/6`) **não derruba o plugin inteiro de imediato**. O Kernel isola apenas a rota falha e contabiliza o incidente; o plugin só é ejetado globalmente se atingir o limite (*threshold*, padrão: 3 falhas consecutivas) ou se falhar durante o boot.
- **Modo Desenvolvedor (Bypass Seguro na Sessão):** Quando um plugin do Ring 3 é desarmado pelo Disjuntor (`temp/disarmed.json`), o administrador pode ativar o **Modo Desenvolvedor** no painel de Supervisão (`/admin/supervision`). Isso contorna o bloqueio apenas para a sessão do administrador (`SessionManagerInterface`), mantendo os visitantes e alunos protegidos enquanto o bug é depurado em tempo real.

<br><br><br><hr>

# 🇺🇸 ⚡ The Circuit Breaker and the Cascade Effect

In traditional monolithic systems, if a small module fails (like the chat or reporting module), it usually generates a **Fatal Error** in PHP's memory. This results in the infamous "White Screen of Death," bringing down the entire system and preventing all users from working.

To avoid catastrophic scenarios, the **Domain-System** adopts a resilience pattern inherited from electrical engineering and microservices: the **Circuit Breaker**.

## 🛡️ How Fault Containment Works

The Kernel (SystemAdmin) does not blindly trust the plugins (modules). It has a central manager (`PluginManager`) that acts as a power distribution board.

When the system is booting the plugin tree, the process happens inside a security dome (`try/catch` blocks). If a defective plugin tries to break the system (whether due to a syntax error, a rejected API connection, or a missing file), the Circuit Breaker detects the overload and **trips automatically**.

### The Tripping Flow:
1. **Interception:** The critical error is caught before reaching the user's browser.
2. **Isolation:** The defective module is temporarily blocked in memory.
3. **Supervision Log:** An incident report is generated and silently registered in the Monitoring panel.
4. **Survival:** The rest of the system ignores the defective module and finishes loading, allowing the platform to keep operating.

## 🌊 The Cascade Effect (Cascade Failure Handling)

One of the biggest challenges of a modular system is **dependencies**. What happens if the "Medical Records" module strictly needs the "Authentication" module, but Authentication suffered a crash and the circuit breaker tripped it?

If the system tried to load the Medical Records, it would break anyway due to the absence of the security system. To solve this, we implemented **Cascade Shutdown**.

When the dependency tree (Dependency Resolver) is being mapped, if the `PluginManager` notices that a module depends on something that "died", it acts with instinctive artificial intelligence:
> *"Module A needs B. B is deactivated. Therefore, preemptively eject module A to save the Kernel!"*

```php
// Real Core snippet (PluginManager.php) illustrating the Cascade
try {
    $this->resolveNode($plugin, $resolved, $unresolved);
} catch (Exception $e) {
    // If the plugin required a dead dependency, we eject this plugin too (Cascade Effect).
    $this->disable($plugin->getName());
    error_log("Cascade: Plugin '{$plugin->getName()}' disabled. Reason: " . $e->getMessage());
    $unresolved = []; // Clears the trail to protect the next plugin
}
```

## 🛠️ Route-Level Isolation (Threshold) & Developer Mode
In **v2.3.0**, the Circuit Breaker operates with surgical granularity and atomic persistence (`AtomicJsonStore` using `flock` + `rename`):
- **Route-Level Circuit Breaker with Threshold (`recordRouteFailure`):** An isolated runtime error in a single route (e.g., `/admin/academy/edit/6`) **does not immediately take down the entire plugin**. The Kernel isolates the failing route first and only ejects the whole plugin if it reaches the consecutive failure threshold (default: 3) or fails during boot.
- **Developer Mode (Session-Scoped Safe Bypass):** When a Ring 3 plugin is tripped by the Circuit Breaker (`temp/disarmed.json`), the administrator can enable **Developer Mode** inside the Supervision panel (`/admin/supervision`). This bypasses the lock exclusively for the administrator's active session (`SessionManagerInterface`), keeping regular visitors and students shielded while the bug is debugged live.

This approach ensures that a chain failure is contained elegantly, proving that the Kernel is an indestructible wall against code or infrastructure instabilities.
