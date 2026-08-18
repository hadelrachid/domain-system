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
