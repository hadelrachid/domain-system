<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;
use Exception;

class PluginManager
{
    private Container $container;
    private EventDispatcher $dispatcher;
    
    /** @var PluginInterface[] */
    private array $plugins = [];

    public function __construct(Container $container, EventDispatcher $dispatcher)
    {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
    }

    public function addPlugin(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }

    public function discoverPlugins(string $pluginsPath, string $configPath): void
    {
        if (!is_dir($pluginsPath)) {
            return;
        }

        $activeStates = [];
        if (file_exists($configPath)) {
            $activeStates = json_decode(file_get_contents($configPath), true) ?? [];
        }

        $directories = glob($pluginsPath . '/*', GLOB_ONLYDIR);
        
        foreach ($directories as $dir) {
            $pluginClass = "DomainSystem\\Plugins\\" . basename($dir) . "\\Plugin";
            
            if (class_exists($pluginClass)) {
                // If it extends AbstractPlugin, it needs path
                if (is_subclass_of($pluginClass, AbstractPlugin::class)) {
                    /** @var AbstractPlugin $plugin */
                    $plugin = new $pluginClass($this->container, $dir);
                    $pluginName = $plugin->getName();
                    $isActive = $activeStates[$pluginName] ?? false;
                    $plugin->setActive($isActive);
                    $this->addPlugin($plugin);
                } else {
                    // For legacy tests or hardcoded plugins that just take container
                    /** @var PluginInterface $plugin */
                    $plugin = new $pluginClass($this->container);
                    $this->addPlugin($plugin);
                }
            }
        }
    }

    public function bootPlugins(): void
    {
        $orderedPlugins = $this->resolveDependencies();

        foreach ($orderedPlugins as $pluginName) {
            $plugin = $this->plugins[$pluginName];
            
            if ($plugin->isActive()) {
                $plugin->register();
                $this->dispatcher->dispatch('plugin.registered', $plugin->getName());
            }
        }
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    private function resolveDependencies(): array
    {
        $resolved = [];
        $unresolved = [];

        foreach ($this->plugins as $plugin) {
            if ($plugin->isActive()) {
                $this->resolveNode($plugin, $resolved, $unresolved);
            }
        }

        return $resolved;
    }

    private function resolveNode(PluginInterface $plugin, array &$resolved, array &$unresolved): void
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
            if (!isset($this->plugins[$dependencyName]) || !$this->plugins[$dependencyName]->isActive()) {
                throw new Exception("Dependency '{$dependencyName}' for plugin '{$name}' not found.");
            }
            $this->resolveNode($this->plugins[$dependencyName], $resolved, $unresolved);
        }

        $unresolved = array_diff($unresolved, [$name]);
        $resolved[] = $name;
    }
}
