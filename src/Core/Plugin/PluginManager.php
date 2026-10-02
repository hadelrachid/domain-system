<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use Exception;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: PluginManager (O Gestor de Plugins)
 * ════════════════════════════════════════════════════════════════════════════
 * PADRÃO DE PROJETO: FACADE (Fachada)
 *
 * O PluginManager é a "porta de entrada" para todo o subsistema de plugins.
 * Ele delega responsabilidades para operárias especializadas via DI:
 *   - PluginDiscoverer    → Varre as pastas e encontra plugins.
 *   - PluginBootStack     → Gerencia a Pilha de prioridades (Ring 0 → Ring 3).
 *   - PluginBootstrapper  → Executa o ciclo de Boot em 2 Fases.
 *   - PluginStateManager  → Controla o estado ativo/inativo (plugins.json).
 *   - PluginInstaller     → Instala/remove plugins via ZIP.
 *
 * PRINCÍPIO SOLID: SRP (não faz nada sozinho, apenas delega).
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

    public function __construct(
        ContainerInterface $container,
        EventDispatcherInterface $dispatcher,
        Services\PluginStateManager $stateManager,
        Services\PluginDiscoverer $discoverer,
        Services\PluginBootstrapper $bootstrapper,
        Services\PluginBootStack $bootStack,
        Services\PluginInstaller $installer
    ) {
        $this->container    = $container;
        $this->dispatcher   = $dispatcher;
        $this->stateManager = $stateManager;
        $this->discoverer   = $discoverer;
        $this->bootstrapper = $bootstrapper;
        $this->bootStack    = $bootStack;
        $this->installer    = $installer;

        // Liga o QTA (Quadro de Transferência Automática) / Disjuntor V2 Extra
        register_shutdown_function([$this->bootstrapper, 'handleFatalCrash']);
    }

    /**
     * Descobre plugins numa pasta e os empilha na camada correta.
     *
     * @param string $pluginsPath  Caminho da pasta (SystemApps ou Plugins)
     * @param string $configPath   Caminho do arquivo de configuração
     * @param bool   $forceActive  Se true, força todos como ativos (Ring 0)
     * @param bool   $isSystemApp  Se true, empilha como Ring 0 (protegido)
     */
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

    /**
     * Executa o Boot de todos os plugins na ordem da Pilha (Ring 0 → Ring 3).
     */
    public function bootPlugins(): void
    {
        $this->bootstrapper->bootPlugins(
            $this->bootStack->getSystemApps(),
            $this->bootStack->getUserPlugins()
        );
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

    /**
     * Verifica se um plugin é considerado "core" (protegido).
     * Primeiro checa se está na camada Ring 0 (SystemApps), depois
     * verifica a flag 'core' do próprio plugin.
     */
    public function isCore(string $pluginName): bool
    {
        // Ring 0 → sempre core
        if ($this->bootStack->isSystemApp($pluginName)) {
            return true;
        }

        // Verifica a flag do plugin
        $all = $this->bootStack->getOrderedStack();
        if (isset($all[$pluginName])) {
            return $all[$pluginName]->isCore();
        }

        return false;
    }

    /**
     * Retorna o ProcessRegistry para consulta externa (SystemMonitor).
     */
    public function getProcessRegistry(): Services\ProcessRegistry
    {
        return $this->bootstrapper->getProcessRegistry();
    }
}
