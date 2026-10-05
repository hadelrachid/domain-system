<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Plugin\PluginInterface;
use Exception;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: PluginBootstrapper (O Motor de Inicialização do Kernel)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * Esta classe é o coração da inicialização do Domain System OS. Ela 
 * recebe a Pilha ordenada de plugins (via PluginBootStack) e executa 
 * o ciclo de vida completo de cada módulo em DUAS FASES distintas, 
 * seguindo o padrão de Service Providers (similar ao Laravel/Symfony).
 *
 * O CICLO DE INICIALIZAÇÃO EM 2 FASES:
 * ─────────────────────────────────────
 *
 *   ╔══════════════════════════════════════════════════════════╗
 *   ║  FASE 1: NEGOCIAÇÃO (osRegister)                        ║
 *   ║  ───────────────────────────────                        ║
 *   ║  Cada plugin apenas DECLARA o que precisa e o que       ║
 *   ║  oferece (Hooks, Links). Nenhuma lógica pesada roda.    ║
 *   ║  → Database diz: "Eu forneço core.db"                  ║
 *   ║  → Academy diz: "Eu preciso de core.db"                ║
 *   ╠══════════════════════════════════════════════════════════╣
 *   ║  FASE 2: EXECUÇÃO (osBoot + boot)                      ║
 *   ║  ────────────────────────────────                       ║
 *   ║  Cada plugin roda sua lógica real. Neste ponto, todas   ║
 *   ║  as dependências declaradas na Fase 1 já estão prontas. ║
 *   ║  → Database expõe o PDO no Container                   ║
 *   ║  → Academy busca cursos no banco com segurança          ║
 *   ╚══════════════════════════════════════════════════════════╝
 *
 * INTEGRAÇÃO COM O PROCESSREGISTRY (PIDs):
 * ─────────────────────────────────────────
 * Na Fase 2, antes de ligar cada plugin, o Bootstrapper gera um PID 
 * (Process Identifier) via ProcessRegistry. Isso permite ao SystemMonitor 
 * exibir uma tabela de processos em tempo real (RAM, CPU, Status).
 *
 * INTEGRAÇÃO COM O NO-BREAK SHIELD (Severity Routing):
 * ─────────────────────────────────────────────────────
 * Se um plugin explodir durante o Boot, o Bootstrapper:
 *   1. Finaliza o PID com status 'Crashed'.
 *   2. Consulta o PluginBootStack para saber se é Ring 0 ou Ring 3.
 *   3. Define a severidade: CRITICAL (Ring 0) ou WARNING (Ring 3).
 *   4. Despacha o evento 'os.plugin.crashed' com todos os metadados.
 *   5. Se for Ring 3, desativa o plugin (quarentena).
 *   6. O loop CONTINUA — o sistema sobrevive.
 *
 * PADRÃO DE PROJETO: Template Method / Pipeline
 * PRINCÍPIO SOLID:   SRP + OCP (aberto para extensão via eventos)
 */
class PluginBootstrapper
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private PluginStateManager $stateManager;
    private ProcessRegistry $processRegistry;
    private string $basePath;
    private ?\DomainSystem\Core\Contracts\SessionManagerInterface $sessionManager;
    private ?\DomainSystem\Core\Plugin\LinkRegistry $linkRegistry;

    /** @var string|null Nome do plugin sendo inicializado (para rastreio de crash fatal) */
    private ?string $currentBootingPlugin = null;

    public function __construct(
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher,
        PluginStateManager $stateManager,
        string $basePath,
        ?\DomainSystem\Core\Contracts\SessionManagerInterface $sessionManager = null,
        ?ProcessRegistry $processRegistry = null,
        ?\DomainSystem\Core\Plugin\LinkRegistry $linkRegistry = null
    ) {
        $this->container        = $container;
        $this->dispatcher       = $dispatcher;
        $this->stateManager     = $stateManager;
        $this->basePath         = $basePath;
        $this->sessionManager   = $sessionManager;
        $this->processRegistry  = $processRegistry ?? new ProcessRegistry();
        $this->linkRegistry     = $linkRegistry;
    }

    public function getCurrentBootingPlugin(): ?string
    {
        return $this->currentBootingPlugin;
    }

    public function getProcessRegistry(): ProcessRegistry
    {
        return $this->processRegistry;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  MÉTODO PRINCIPAL: bootPlugins() — O Motor de 2 Fases
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Executa o ciclo de inicialização completo de todos os plugins na 
     * ordem definida pela Pilha (PluginBootStack: Ring 0 → Ring 3).
     *
     * @param array<string, PluginInterface> $plugins Pilha ordenada de plugins
     */
        /**
     * ATENÇÃO ARQUITETURAL:
     * NÃO forneça valor default para os arrays! A separação estrutural entre Ring 0 (systemApps)
     * e Ring 3 (userPlugins) DEVE ser explícita.
     * Se os argumentos forem omitidos, o PHP lançará ArgumentCountError, o que bloqueia
     * o boot e alerta sobre a falha (evitando perda silenciosa de plugins).
     */
    public function bootPlugins(array $systemApps, array $userPlugins): void
    {
        $totalRequested = count($systemApps) + count($userPlugins);

        $resolvedSystemApps = $this->resolveDependencies($systemApps);
        $allPlugins = array_merge($systemApps, $userPlugins);
        $resolvedUserPlugins = $this->resolveDependencies($allPlugins);
        $filteredUserPlugins = array_filter($resolvedUserPlugins, function($name) use ($systemApps) { 
            return !isset($systemApps[$name]); 
        });
        
        $orderedPlugins = array_merge($resolvedSystemApps, $filteredUserPlugins);
        $plugins = $allPlugins;

        // Telemetria: Verifica se a resolução topológica descartou algum módulo (ex: dependência cíclica ou ausente)
        if (count($orderedPlugins) < $totalRequested) {
            $missing = $totalRequested - count($orderedPlugins);
            try {
                $this->handlePluginCrash('Kernel', 'OS-WARN-DEPENDENCY', new Exception(
                    "Alerta de Infraestrutura: {$missing} módulo(s) não foram inicializados " .
                    "devido a dependências cíclicas, dependências não instaladas ou falha silenciosa de parâmetros."
                ));
            } catch (\Throwable $e) {
                // Silencia se o mecanismo de crash falhar tão cedo
            }
        }

        // Prepara o sistema de migrações
        $migrationsPath = $this->basePath . '/temp/migrations.json';
        $migrated = file_exists($migrationsPath)
            ? (json_decode(file_get_contents($migrationsPath), true) ?? [])
            : [];
        $needsSave = false;

        if (!$this->linkRegistry) {
            $this->linkRegistry = new \DomainSystem\Core\Plugin\LinkRegistry($this->container);
        }

        // ──────────────────────────────────────────────────────────────────
        //  FASE 1: NEGOCIAÇÃO (osRegister)
        //  Cada plugin declara suas dependências e capacidades.
        //  Nenhuma lógica pesada roda aqui. Apenas contratos.
        // ──────────────────────────────────────────────────────────────────
        $connectors = [];
        foreach ($orderedPlugins as $pluginName) {
            if (!isset($plugins[$pluginName])) continue;
            $plugin = $plugins[$pluginName];

            if ($plugin->isActive() && $plugin instanceof \DomainSystem\Core\Contracts\OsExtensionInterface) {
                $connector = new \DomainSystem\Core\Plugin\OsConnector();
                try {
                    $this->currentBootingPlugin = $pluginName;
                    $plugin->osRegister($connector);
                    $connectors[$pluginName] = $connector;
                    $this->linkRegistry->registerConnector($pluginName, $connector);
                } catch (\Throwable $e) {
                    // Falha na negociação: registrar mas não abortar o sistema
                    $this->handlePluginCrash($pluginName, 'OS-ERR-REG', $e);
                } finally {
                    $this->currentBootingPlugin = null;
                }
            }
        }

        // ──────────────────────────────────────────────────────────────────
        //  FASE 2: EXECUÇÃO (osBoot + boot)
        //  Cada plugin executa sua lógica real. O ProcessRegistry 
        //  monitora RAM e tempo de cada um. Falhas são isoladas.
        // ──────────────────────────────────────────────────────────────────
        foreach ($orderedPlugins as $pluginName) {
            if (!isset($plugins[$pluginName])) continue;
            $plugin = $plugins[$pluginName];

            if (!$plugin->isActive()) continue;

            // Verifica links não atendidos (dependências insatisfeitas)
            if ($this->linkRegistry) {
                $unmet = $this->linkRegistry->getUnmetLinks($pluginName);
                if (!empty($unmet)) {
                    continue; // Dependência não satisfeita — pula sem explodir
                }
            }

            // Detecta se é SystemApp (Ring 0) ou UserPlugin (Ring 3)
            $isSystemApp = strpos(
                (new \ReflectionClass($plugin))->getFileName(),
                DIRECTORY_SEPARATOR . 'SystemApps' . DIRECTORY_SEPARATOR
            ) !== false;

            // Gera o PID e começa a monitorar RAM/Tempo
            $pid = $this->processRegistry->startProcess($pluginName, $isSystemApp);

            try {
                $this->currentBootingPlugin = $pluginName;

                // Auto-Migrate: Roda as migrações de banco se necessário
                if (method_exists($plugin, 'getMigrations')) {
                    $migrations = $plugin->getMigrations();
                    if (!empty($migrations)) {
                        $pdo = $this->container
                            ->make(\DomainSystem\SystemApps\Database\Connection::class)
                            ->getPdo();
                        foreach ($migrations as $name => $sql) {
                            $key = $pluginName . '_' . $name;
                            if (!in_array($key, $migrated)) {
                                $pdo->exec($sql);
                                $migrated[] = $key;
                                $needsSave = true;
                            }
                        }
                    }
                }

                // OS Boot (Para plugins que implementam OsExtensionInterface)
                if ($plugin instanceof \DomainSystem\Core\Contracts\OsExtensionInterface) {
                    $connector = $connectors[$pluginName] ?? new \DomainSystem\Core\Plugin\OsConnector();
                    $runtime   = new \DomainSystem\Core\Plugin\OsRuntime(
                        $this->container,
                        $connector,
                        $this->linkRegistry,
                        $this->dispatcher
                    );
                    $plugin->osBoot($runtime);
                }

                // Boot clássico (para retrocompatibilidade)
                $plugin->boot();

                // Processo encerrado com sucesso ✓
                $this->processRegistry->endProcess($pid, 'Running');

            } catch (\Throwable $e) {
                // Processo MORREU — registrar crash com PID e Severidade
                $this->processRegistry->endProcess($pid, 'Crashed');
                $this->handlePluginCrash($pluginName, 'OS-ERR-BOOT', $e, $pid, $isSystemApp);
            } finally {
                $this->currentBootingPlugin = null;
            }
        }

        // Persiste as migrações executadas
        if ($needsSave) {
            $dir = dirname($migrationsPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($migrationsPath, json_encode($migrated, JSON_PRETTY_PRINT));
        }

        // Salva a pilha de processos para o Gerenciador de Tarefas
        if ($this->processRegistry) {
            $this->processRegistry->saveSnapshot($this->basePath);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  SEVERITY ROUTING: O Despacho Inteligente de Falhas
    // ════════════════════════════════════════════════════════════════════════

    /**
     * 🛡️ SEVERITY ROUTING & MONITORING STACK (Níveis de Ameaça do OS)
     * ────────────────────────────────────────────────────────────────────
     * O Kernel NÃO trata todos os plugins de forma igual. A severidade 
     * do alerta depende da CAMADA (Ring) onde o plugin se encontra:
     *
     *   ┌──────────────────────────────────────────────────────────────┐
     *   │  CRITICAL (P0) — Ring 0 (SystemApp) caiu                    │
     *   │  O sistema entra em modo "Degraded". O módulo NÃO é         │
     *   │  desativado (é protegido). Futuro: dispara e-mail ao admin. │
     *   ├──────────────────────────────────────────────────────────────┤
     *   │  WARNING  (P2) — Ring 3 (UserPlugin) caiu                   │
     *   │  O plugin é desativado (quarentena). O sistema sobrevive    │
     *   │  normalmente. O admin vê o alerta no painel do Monitor.     │
     *   └──────────────────────────────────────────────────────────────┘
     *
     * @param string     $pluginName  Nome do plugin que falhou
     * @param string     $errorCode   Código do erro (OS-ERR-REG, OS-ERR-BOOT)
     * @param \Throwable $e           A exceção capturada
     * @param string|null $pid        O PID do processo (se disponível)
     * @param bool       $isSystemApp Se o plugin pertence ao Ring 0
     */
    private function handlePluginCrash(
        string $pluginName,
        string $errorCode,
        \Throwable $e,
        ?string $pid = null,
        bool $isSystemApp = false
    ): void {
        $severity = $isSystemApp ? 'CRITICAL' : 'WARNING';

        // 1. Quarentena: Desativa APENAS se for Ring 3 (UserPlugin).
        //    Ring 0 é protegido e NUNCA pode ser desligado.
        if (!$isSystemApp) {
            try {
                $this->stateManager->disable($pluginName);
            } catch (\Throwable $disableEx) {
                // Não pode falhar ao tentar desativar
            }
        }

        // 2. Despacha o evento com metadados completos para o No-Break Shield
        $this->dispatcher->dispatch('os.plugin.crashed', [
            'plugin'     => $pluginName,
            'severity'   => $severity,
            'error_code' => $errorCode,
            'exception'  => $e->getMessage(),
            'file'       => $e->getFile(),
            'line'       => $e->getLine(),
            'pid'        => $pid,
        ]);
        // 3. Grava no Flight Recorder
        try {
            if ($this->container->has(\DomainSystem\Core\Contracts\NotificationManagerInterface::class)) {
                $this->container->make(\DomainSystem\Core\Contracts\NotificationManagerInterface::class)->push(
                    $e->getMessage(),
                    $isSystemApp ? 'error' : 'warning',
                    $pluginName,
                    ['file' => $e->getFile(), 'line' => $e->getLine(), 'severity' => $severity]
                );
            }
        } catch (\Throwable $i) {}

        // 4. Suprime flash de "sucesso" e injeta flash de erro para o Toast
        if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'success') {
            unset($_SESSION['flash_message']);
        }
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'msg'  => "⚠ Plugin \"{$pluginName}\" falhou ao iniciar e foi desativado. Erro: " . $e->getMessage()
        ];

        error_log("[{$severity}] OS Plugin Crash - {$pluginName}: " . $e->getMessage());

    }



    // ════════════════════════════════════════════════════════════════════════
    //  RESOLUÇÃO DE DEPENDÊNCIAS (Ordenação Topológica)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Ordena os plugins por dependência usando busca em profundidade (DFS).
     * Se o plugin A depende do B, o B aparece primeiro na lista.
     * Detecta dependências circulares e lança Exception.
     */
    private function resolveDependencies(array $plugins): array
    {
        $resolved   = [];
        $unresolved = [];

        foreach ($plugins as $name => $plugin) {
            $this->resolvePlugin($name, $plugins, $resolved, $unresolved);
        }

        return $resolved;
    }

    private function resolvePlugin(string $name, array $plugins, array &$resolved, array &$unresolved): void
    {
        if (in_array($name, $resolved)) return;
        if (in_array($name, $unresolved)) {
            throw new Exception("Circular dependency detected involving plugin '{$name}'");
        }

        $unresolved[] = $name;

        if (isset($plugins[$name])) {
            $deps = $plugins[$name]->getDependencies();
            foreach ($deps as $dep) {
                if (!isset($plugins[$dep]) || !$plugins[$dep]->isActive()) {
                    continue;
                }
                $this->resolvePlugin($dep, $plugins, $resolved, $unresolved);
            }
        }

        $unresolved = array_diff($unresolved, [$name]);
        $resolved[] = $name;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  HANDLER DE CRASH FATAL (QTA — Quadro de Transferência Automática)
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Registrado via register_shutdown_function() no PluginManager.
     * Intercepta erros fatais (E_PARSE, E_ERROR) que o try/catch não 
     * consegue capturar (ex: syntax error dentro de um require).
     *
     * Quando ativado:
     *   1. Identifica qual plugin estava sendo carregado ($currentBootingPlugin).
     *   2. Se for Ring 3, desativa o plugin para proteger o próximo reload.
     *   3. Salva o crash na sessão para exibição no painel admin.
     */
    public function handleFatalCrash(): void
    {
        $error = error_get_last();

        if ($error === null) return;
        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) return;
        if ($this->currentBootingPlugin === null) return;

        $pluginName = $this->currentBootingPlugin;

        // Verifica se é SystemApp checando AMBAS as pastas possíveis
        $isCore = false;
        foreach (['SystemApps', 'Plugins'] as $folder) {
            $jsonPath = $this->basePath . "/src/{$folder}/{$pluginName}/plugin.json";
            if (file_exists($jsonPath)) {
                $meta   = json_decode(file_get_contents($jsonPath), true);
                $isCore = !empty($meta['core']);
                break;
            }
        }

        // Ring 3 → Desativa para quarentena. Ring 0 → Mantém ativo.
        if (!$isCore) {
            $this->stateManager->disable($pluginName);
        }

        // Salva na sessão para exibição no painel admin
        // Salva na sessao para painel admin e Flight Recorder
            if ($this->sessionManager) {
                $crashes   = $this->sessionManager->get('plugin_crashes', []);
                $severity  = $isCore ? 'CRITICAL' : 'WARNING';
                $crashes[] = [
                    'plugin'   => $pluginName,
                    'severity' => $severity,
                    'error'    => "FATAL CRASH (QTA): {$error['message']}"
                        . ($isCore ? ' [RING 0 — NÃO DESATIVADO]' : ' [RING 3 — PLUGIN EJETADO]'),
                ];
                $this->sessionManager->set('plugin_crashes', $crashes);
                $this->sessionManager->set("plugin_crashes", $crashes);
                if ($this->container->has(\DomainSystem\Core\Contracts\NotificationManagerInterface::class)) { $this->container->make(\DomainSystem\Core\Contracts\NotificationManagerInterface::class)->push("FATAL: " . $error["message"], "error", $pluginName, ["file" => $error["file"], "line" => $error["line"], "severity" => $severity]); }
            // Dentro de um shutdown function, não podemos falhar
        }

        error_log(
            "[QTA] Fatal crash no plugin '{$pluginName}': {$error['message']}"
        );
    }
}
