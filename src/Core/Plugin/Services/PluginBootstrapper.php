<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Plugin\PluginInterface;
use Exception;

class PluginBootstrapper
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private PluginStateManager $stateManager;
    private string $basePath;
    private ?\DomainSystem\Core\Contracts\SessionManagerInterface $sessionManager;
    private ?\DomainSystem\Core\Plugin\LinkRegistry $linkRegistry;

    private ?string $currentBootingPlugin = null;

    public function __construct(
        ContainerInterface $container, 
        EventDispatcherInterface $dispatcher, 
        PluginStateManager $stateManager,
        string $basePath,
        ?\DomainSystem\Core\Contracts\SessionManagerInterface $sessionManager = null,
        ?\DomainSystem\Core\Plugin\LinkRegistry $linkRegistry = null
    ) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->stateManager = $stateManager;
        $this->basePath = $basePath;
        $this->sessionManager = $sessionManager;
        $this->linkRegistry = $linkRegistry;
    }

    public function getCurrentBootingPlugin(): ?string
    {
        return $this->currentBootingPlugin;
    }

    public function bootPlugins(array &$plugins): void
    {
        $orderedPlugins = $this->resolveDependencies($plugins);
        
        $migrationsPath = $this->basePath . '/temp/migrations.json';
        $migrated = file_exists($migrationsPath) ? json_decode(file_get_contents($migrationsPath), true) ?? [] : [];
        $needsSave = false;
        
        if (!$this->linkRegistry) {
            $this->linkRegistry = new \DomainSystem\Core\Plugin\LinkRegistry($this->container);
        }

        // FASE 1: NEGOCIAÇÃO (OS REGISTER)
        $connectors = [];
        foreach ($orderedPlugins as $pluginName) {
            $plugin = $plugins[$pluginName];
            if ($plugin->isActive() && $plugin instanceof \DomainSystem\Core\Contracts\OsExtensionInterface) {
                $connector = new \DomainSystem\Core\Plugin\OsConnector();
                try {
                    $this->currentBootingPlugin = $pluginName;
                    $plugin->osRegister($connector);
                    $connectors[$pluginName] = $connector;
                    $this->linkRegistry->registerConnector($pluginName, $connector);
                } catch (Exception $e) {
                    error_log("Failed to register plugin '{$pluginName}': " . $e->getMessage());
                } finally {
                    $this->currentBootingPlugin = null;
                }
            }
        }

        // FASE 2: BOOT (OS BOOT)
        foreach ($orderedPlugins as $pluginName) {
            $plugin = $plugins[$pluginName];
            
            if ($plugin->isActive()) {
                if ($this->linkRegistry) {
                    $unmet = $this->linkRegistry->getUnmetLinks($pluginName);
                    if (!empty($unmet)) {
                        continue; // Falta de links bloqueia o boot
                    }
                }

                try {
                    $this->currentBootingPlugin = $pluginName;
                    
                    if (method_exists($plugin, 'getMigrations')) {
                        $migrations = $plugin->getMigrations();
                        if (!empty($migrations)) {
                            $pdo = $this->container->make(\DomainSystem\SystemApps\Database\Connection::class)->getPdo();
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

                    if ($plugin instanceof \DomainSystem\Core\Contracts\OsExtensionInterface) {
                        $connector = $connectors[$pluginName] ?? new \DomainSystem\Core\Plugin\OsConnector();
                        $runtime = new \DomainSystem\Core\Plugin\OsRuntime(
                            $this->container,
                            $connector,
                            $this->linkRegistry,
                            $this->dispatcher
                        );
                        $plugin->osBoot($runtime);
                    }

                    $plugin->boot();

                } catch (Exception $e) {
                    error_log("Failed to boot plugin '{$pluginName}': " . $e->getMessage());
                } finally {
                    $this->currentBootingPlugin = null;
                }
            }
        }

        if ($needsSave) {
            if (!is_dir(dirname($migrationsPath))) {
                mkdir(dirname($migrationsPath), 0755, true);
            }
            file_put_contents($migrationsPath, json_encode($migrated));
        }
    }

    private function resolveDependencies(array $plugins): array
    {
        $resolved = [];
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

    public function handleFatalCrash(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if ($this->currentBootingPlugin !== null) {
                
                $jsonPath = $this->basePath . '/src/Plugins/' . $this->currentBootingPlugin . '/plugin.json';
                $isCore = false;
                if (file_exists($jsonPath)) {
                    $meta = json_decode(file_get_contents($jsonPath), true);
                    $isCore = !empty($meta['core']);
                }

                if (!$isCore) {
                    $this->stateManager->disable($this->currentBootingPlugin);
                }
                
                try {
                    if ($this->sessionManager) {
                        $crashes = $this->sessionManager->get('plugin_crashes', []);
                        $crashes[] = [
                            'plugin' => $this->currentBootingPlugin,
                            'error' => "FATAL CRASH (QTA Acionado): " . $error['message'] . ($isCore ? " [ISOLADO MAS NÃO DESATIVADO (CORE)]" : " [PLUGIN EJETADO]")
                        ];
                        $this->sessionManager->set('plugin_crashes', $crashes);
                    }
                } catch (\Throwable $ignored) {}
                
                error_log("QTA ACIONADO! Plugin '{$this->currentBootingPlugin}' sofreu um colapso fatal. Erro: " . $error['message']);
            }
        }
    }
}




