<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Exception;

class PluginManager
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private Services\PluginStateManager $stateManager;
    private Services\PluginDiscoverer $discoverer;
    private Services\PluginBootstrapper $bootstrapper;
    private Services\PluginInstaller $installer;
    
    /** @var PluginInterface[] */
    private array $plugins = [];

    public function __construct(
        ContainerInterface $container, 
        EventDispatcherInterface $dispatcher,
        Services\PluginStateManager $stateManager,
        Services\PluginDiscoverer $discoverer,
        Services\PluginBootstrapper $bootstrapper,
        Services\PluginInstaller $installer
    ) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->stateManager = $stateManager;
        $this->discoverer = $discoverer;
        $this->bootstrapper = $bootstrapper;
        $this->installer = $installer;
        
        // Liga o QTA (Quadro de Transferência Automática) / Disjuntor V2 Extra
        register_shutdown_function([$this->bootstrapper, 'handleFatalCrash']);
    }

    public function addPlugin(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->getName()] = $plugin;
    }

    public function discoverPlugins(string $pluginsPath, string $configPath, bool $forceActive = false): void
    {
        $discovered = $this->discoverer->discover($pluginsPath, $forceActive);
        foreach ($discovered as $plugin) {
            $this->addPlugin($plugin);
        }
    }

    public function bootPlugins(): void
    {
        $this->bootstrapper->bootPlugins($this->plugins, $this->getBasePath());
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getActiveStates(): array
    {
        return $this->stateManager->getActiveStates();
    }

    public function enable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;
        $this->stateManager->enable($pluginName);
    }

    public function disable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;
        $this->stateManager->disable($pluginName);
    }

    public function installFromZip(string $zipFilePath): string
    {
        return $this->installer->installFromZip($zipFilePath);
    }

    public function delete(string $pluginName, string $pluginFolder): void
    {
        $this->installer->delete($pluginName, $pluginFolder);
    }

    public function isCore(string $pluginName): bool
    {
        if (isset($this->plugins[$pluginName])) {
            return $this->plugins[$pluginName]->isCore();
        }
        return false; // Ou usar o installer/discoverer
    }
}

