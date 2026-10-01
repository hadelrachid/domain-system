<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Plugin\PluginInterface;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * CLASSE: PluginDiscoverer
 * ────────────────────────────────────────────────────────────────────────────
 * Responsabilidade Única (SRP): Vasculhar o sistema de arquivos, encontrar
 * os plugins (pastas) e carregar suas instâncias básicas (sem dar o boot).
 * 
 * Na analogia da colmeia, esta é a operária "Batedora". Ela sai voando pelas 
 * pastas (src/Plugins), lê os "feromônios" (plugin.json) e diz ao sistema 
 * quem está lá fora, mas não dá a ordem para eles começarem a trabalhar.
 */
class PluginDiscoverer
{
    private ContainerInterface $container;
    private EventDispatcherInterface $dispatcher;
    private PluginStateManager $stateManager;

    public function __construct(
        ContainerInterface $container, 
        EventDispatcherInterface $dispatcher, 
        PluginStateManager $stateManager
    ) {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->stateManager = $stateManager;
    }

    /**
     * @return PluginInterface[]
     */
    public function discover(string $pluginsPath, bool $forceActive = false): array
    {
        if (!is_dir($pluginsPath)) {
            return [];
        }

        $activeStates = $this->stateManager->getActiveStates();
        $directories = glob($pluginsPath . '/*', GLOB_ONLYDIR);
        $discovered = [];
        $newlyDiscovered = [];

        foreach ($directories as $dir) {
            $jsonPath = $dir . '/plugin.json';
            $pluginName = basename($dir);
            if (file_exists($jsonPath)) {
                $metadata = json_decode(file_get_contents($jsonPath), true);
                if (isset($metadata['name'])) {
                    $pluginName = $metadata['name'];
                }
            }

            $isExplicitlyDisabled = isset($activeStates[$pluginName]) && $activeStates[$pluginName] === false;
            $isActive = (!$isExplicitlyDisabled && $forceActive) || (!empty($activeStates[$pluginName]));
            $isCore = isset($metadata['core']) && $metadata['core'] === true;

            if ($isActive || $isCore) {
                $pluginClass = "DomainSystem\\Plugins\\" . basename($dir) . "\\Plugin";

                if (!class_exists($pluginClass)) {
                    $pluginFile = $dir . '/Plugin.php';
                    if (file_exists($pluginFile)) {
                        $fileContent = file_get_contents($pluginFile);
                        if (preg_match('/namespace\s+([^;]+);/', $fileContent, $matches)) {
                            $inferredClass = $matches[1] . '\\Plugin';
                            require_once $pluginFile;
                            $pluginClass = $inferredClass;
                        } else {
                            require_once $pluginFile;
                        }
                    }
                }

                if (class_exists($pluginClass)) {
                    /** @var PluginInterface $plugin */
                    $plugin = new $pluginClass($this->container, $dir, $this->dispatcher);
                    $plugin->setActive(true);
                    $discovered[$plugin->getName()] = $plugin;
                    $newlyDiscovered[] = $plugin;
                }
            }
        }

        // Descobre sub-plugins recursivamente
        foreach ($newlyDiscovered as $plugin) {
            $subPath = $plugin->getSubPluginsPath();
            if ($subPath && is_dir($subPath)) {
                $subPlugins = $this->discover($subPath, true);
                $discovered = array_merge($discovered, $subPlugins);
            }
        }

        return $discovered;
    }
}
