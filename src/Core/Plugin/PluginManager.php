<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Exception;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * CLASSE: PluginManager (O Gestor de Plugins)
 * ────────────────────────────────────────────────────────────────────────────
 * PADRÃO DE PROJETO: FACADE (Fachada)
 * 
 * Antes, esta era uma "God Class" (Classe Deus) que fazia absolutamente TUDO: 
 * lia arquivos, resolvia dependências, extraia ZIPs, interceptava erros 
 * fatais (QTA). Isso violava o SRP (Single Responsibility Principle) e
 * tornava o sistema frágil (como uma colmeia com operárias confusas).
 * 
 * AGORA, o PluginManager é uma "Fachada". Ele apenas RECEBE as operárias 
 * especialistas via Injeção de Dependências (DIP) e repassa os comandos para 
 * elas. 
 * 
 * Se o sistema externo precisa "instalar" um plugin, ele pede para o 
 * PluginManager, que por sua vez pede para o PluginInstaller. O mundo 
 * externo não precisa conhecer as operárias, apenas a Fachada!
 */
class PluginManager
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private Services\PluginStateManager $stateManager;
    private Services\PluginDiscoverer $discoverer;
    private Services\PluginBootstrapper $bootstrapper;
    private Services\PluginBootStack $bootStack;
    private Services\PluginInstaller $installer;
    
    /** @var PluginInterface[] */
    public function __construct(
        ContainerInterface $container, 
        EventDispatcherInterface $dispatcher,
        Services\PluginStateManager $stateManager,
        Services\PluginDiscoverer $discoverer,
        Services\PluginBootstrapper $bootstrapper,
        Services\PluginBootStack $bootStack,
        Services\PluginInstaller $installer
    ) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->stateManager = $stateManager;
        $this->discoverer = $discoverer;
        $this->bootstrapper = $bootstrapper;
        $this->bootStack = $bootStack;
        $this->installer = $installer;
        
        // Liga o QTA (Quadro de Transferência Automática) / Disjuntor V2 Extra
        register_shutdown_function([$this->bootstrapper, 'handleFatalCrash']);
    }

    

    public function discoverPlugins(string $pluginsPath, string $configPath, bool $forceActive = false, bool $isSystemApp = false): void
    {
        $discovered = $this->discoverer->discover($pluginsPath, $forceActive);
        foreach ($discovered as $plugin) {
            if ($isSystemApp) {
                $this->bootStack->pushSystemApp($plugin);
            } else {
                $this->bootStack->pushUserPlugin($plugin);
            }
        }
    }

    public function bootPlugins(): void
    {
        $stack = $this->bootStack->getOrderedStack();
        $this->bootstrapper->bootPlugins($stack);
    }

    public function getPlugins(): array
    {
        return $this->bootStack->getOrderedStack();
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

