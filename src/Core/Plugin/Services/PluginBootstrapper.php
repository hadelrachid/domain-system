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

    /** @var string|null */
    private ?string $currentBootingPlugin = null;

    public function __construct(
        ContainerInterface $container, 
        EventDispatcherInterface $dispatcher, 
        PluginStateManager $stateManager
    ) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->stateManager = $stateManager;
    }

    public function getCurrentBootingPlugin(): ?string
    {
        return $this->currentBootingPlugin;
    }

    /**
     * @param PluginInterface[] $plugins
     */
    public function bootPlugins(array &$plugins, string $basePath): void
    {
        $orderedPlugins = $this->resolveDependencies($plugins);
        
        $migrationsPath = $basePath . '/temp/migrations.json';
        $migrated = file_exists($migrationsPath) ? json_decode(file_get_contents($migrationsPath), true) ?? [] : [];
        $needsSave = false;

        foreach ($orderedPlugins as $pluginName) {
            $plugin = $plugins[$pluginName];
            
            if ($plugin->isActive()) {
                try {
                    $this->currentBootingPlugin = $pluginName; // Anota no quadro
                    
                    // Executa a migração (activate) apenas uma vez na vida do plugin
                    if (!isset($migrated[$pluginName])) {
                        if (method_exists($plugin, 'activate')) {
                            $plugin->activate();
                        }
                        $migrated[$pluginName] = true;
                        $needsSave = true;
                    }
                    
                    $plugin->register();
                    $plugin->boot();
                    $this->dispatcher->dispatch('plugin.registered', $plugin->getName());
                    
                    $this->currentBootingPlugin = null; // Apaga do quadro
                } catch (\Throwable $e) {
                    $this->currentBootingPlugin = null;
                    
                    $this->stateManager->disable($pluginName);
                    
                    try {
                        $session = $this->container->make(\DomainSystem\Core\Http\SessionManager::class);
                        $crashes = $session->get('plugin_crashes', []);
                        $crashes[] = [
                            'plugin' => $pluginName,
                            'error' => $e->getMessage()
                        ];
                        $session->set('plugin_crashes', $crashes);
                    } catch (\Throwable $ignored) {}

                    error_log("Plugin '{$pluginName}' crashed during boot and was automatically disabled. Error: " . $e->getMessage());
                    file_put_contents(dirname($migrationsPath) . '/boot_crashes.txt', date('Y-m-d H:i:s') . " - {$pluginName} crashed: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
                }
            }
        }
        
        if ($needsSave) {
            if (!is_dir(dirname($migrationsPath))) {
                mkdir(dirname($migrationsPath), 0777, true);
            }
            file_put_contents($migrationsPath, json_encode($migrated, JSON_PRETTY_PRINT));
        }
    }

    /**
     * @param PluginInterface[] $plugins
     */
    private function resolveDependencies(array &$plugins): array
    {
        $resolved = [];
        $unresolved = [];

        foreach ($plugins as $plugin) {
            if ($plugin->isActive()) {
                try {
                    $this->resolveNode($plugin, $plugins, $resolved, $unresolved);
                } catch (Exception $e) {
                    $this->stateManager->disable($plugin->getName());
                    error_log("Cascata: Plugin '{$plugin->getName()}' desativado. Motivo: " . $e->getMessage());
                    $unresolved = [];
                }
            }
        }

        return $resolved;
    }

    /**
     * @param PluginInterface[] $plugins
     */
    private function resolveNode(PluginInterface $plugin, array &$plugins, array &$resolved, array &$unresolved): void
    {
        $name = $plugin->getName();

        if (in_array($name, $resolved)) {
            return;
        }

        if (in_array($name, $unresolved)) {
            throw new Exception("Circular dependency detected for plugin '{$name}'.");
        }

        $unresolved[] = $name;

        foreach ($plugin->getDependencies() as $dependencyName) {
            if (!isset($plugins[$dependencyName]) || !$plugins[$dependencyName]->isActive()) {
                throw new Exception("Dependency '{$dependencyName}' for plugin '{$name}' not found or inactive.");
            }
            $this->resolveNode($plugins[$dependencyName], $plugins, $resolved, $unresolved);
        }

        $unresolved = array_diff($unresolved, [$name]);
        $resolved[] = $name;
    }

    public function handleFatalCrash(): void
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if ($this->currentBootingPlugin !== null) {
                $this->stateManager->disable($this->currentBootingPlugin);
                
                try {
                    $session = $this->container->make(\DomainSystem\Core\Http\SessionManager::class);
                    $crashes = $session->get('plugin_crashes', []);
                    $crashes[] = [
                        'plugin' => $this->currentBootingPlugin,
                        'error' => "FATAL CRASH (QTA Acionado pelo Gerador): " . $error['message']
                    ];
                    $session->set('plugin_crashes', $crashes);
                } catch (\Throwable $ignored) {}
                
                error_log("QTA ACIONADO! Plugin '{$this->currentBootingPlugin}' sofreu um colapso fatal (Ex: Fim de Memória) e foi ejetado automaticamente. Erro: " . $error['message']);
            }
        }
    }
}
