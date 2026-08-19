<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Utils\Archive\ExtractorFactory;
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

        $activeStates = $this->getActiveStates($configPath);

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
                try {
                    $plugin->register();
                    $this->dispatcher->dispatch('plugin.registered', $plugin->getName());
                } catch (\Throwable $e) {
                    // The plugin crashed! We must disable it to save the system.
                    $this->disable($pluginName);
                    
                    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                        session_start();
                    }
                    if (session_status() !== PHP_SESSION_NONE) {
                        $_SESSION['plugin_crashes'][] = [
                            'plugin' => $pluginName,
                            'error' => $e->getMessage()
                        ];
                    }
                    error_log("Plugin '{$pluginName}' crashed during boot and was automatically disabled. Error: " . $e->getMessage());
                }
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

    // --- Instalação e Gerenciamento de Plugins ---

    private function getBasePath(): string
    {
        return dirname(__DIR__, 3); // domain-system root
    }

    private function getConfigPath(): string
    {
        return $this->getBasePath() . '/config/plugins.json';
    }

    private function getPluginsPath(): string
    {
        return $this->getBasePath() . '/src/Plugins';
    }

    public function installFromZip(string $zipFilePath): string
    {
        $extractor = ExtractorFactory::create();
        return $extractor->extract($zipFilePath, $this->getPluginsPath());
    }

    public function getActiveStates(?string $configPath = null): array
    {
        $path = $configPath ?? $this->getConfigPath();
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?? [];
        }
        return [];
    }

    private function saveStates(array $states): void
    {
        $configPath = $this->getConfigPath();
        if (!is_dir(dirname($configPath))) {
            mkdir(dirname($configPath), 0777, true);
        }
        file_put_contents($configPath, json_encode($states, JSON_PRETTY_PRINT));
    }

    public function enable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;

        $states = $this->getActiveStates();
        $states[$pluginName] = true;
        $this->saveStates($states);
    }

    public function disable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;

        $states = $this->getActiveStates();
        unset($states[$pluginName]);
        $this->saveStates($states);
    }

    public function delete(string $pluginName, string $pluginFolder): void
    {
        if ($this->isCore($pluginName)) {
            throw new Exception("Não é possível excluir plugins core do sistema.");
        }

        $states = $this->getActiveStates();
        if (!empty($states[$pluginName])) {
            throw new Exception("O plugin precisa ser desativado antes de ser excluído.");
        }

        $pluginPath = $this->getPluginsPath() . '/' . $pluginFolder;
        if (file_exists($pluginPath)) {
            $this->deleteDirectory($pluginPath);
        }
    }

    public function isCore(string $pluginName): bool
    {
        // Se o plugin já está instanciado/descoberto, podemos perguntar a ele
        if (isset($this->plugins[$pluginName])) {
            return $this->plugins[$pluginName]->isCore();
        }

        // Caso contrário, tentamos ler do plugin.json diretamente
        $jsonPath = $this->getPluginsPath() . '/' . $pluginName . '/plugin.json';
        if (file_exists($jsonPath)) {
            $metadata = json_decode(file_get_contents($jsonPath), true);
            return isset($metadata['core']) && $metadata['core'] === true;
        }

        return false;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
}
