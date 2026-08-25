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
    
    /** @var string|null Rastreia qual plugin está inicializando no exato momento (Para o QTA) */
    private ?string $currentBootingPlugin = null;

    public function __construct(Container $container, EventDispatcher $dispatcher)
    {
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        
        // Liga o QTA (Quadro de Transferência Automática) / Disjuntor V2 Extra
        register_shutdown_function([$this, 'handleFatalCrash']);
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
            $jsonPath = $dir . '/plugin.json';
            $pluginName = basename($dir);
            if (file_exists($jsonPath)) {
                $metadata = json_decode(file_get_contents($jsonPath), true);
                if (isset($metadata['name'])) {
                    $pluginName = $metadata['name'];
                }
            }

            // Apenas tentamos instanciar o plugin se ele estiver ATIVO ou se for CORE
            // Isso evita que plugins desativados com erro de sintaxe derrubem o sistema ao usar class_exists()
            $isActive = $activeStates[$pluginName] ?? false;
            $isCore = isset($metadata['core']) && $metadata['core'] === true;

            if ($isActive || $isCore) {
                $pluginClass = "DomainSystem\\Plugins\\" . basename($dir) . "\\Plugin";
                
                if (class_exists($pluginClass)) {
                    // If it extends AbstractPlugin, it needs path
                    if (is_subclass_of($pluginClass, AbstractPlugin::class)) {
                        /** @var AbstractPlugin $plugin */
                        $plugin = new $pluginClass($this->container, $dir);
                        $plugin->setActive(true); // se chegou aqui é porque está ativo ou é core
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
    }

    public function bootPlugins(): void
    {
        $orderedPlugins = $this->resolveDependencies();

        foreach ($orderedPlugins as $pluginName) {
            $plugin = $this->plugins[$pluginName];
            
            if ($plugin->isActive()) {
                try {
                    $this->currentBootingPlugin = $pluginName; // Anota no quadro
                    
                    $plugin->register();
                    $this->dispatcher->dispatch('plugin.registered', $plugin->getName());
                    
                    $this->currentBootingPlugin = null; // Apaga do quadro
                } catch (\Throwable $e) {
                    $this->currentBootingPlugin = null; // Apaga do quadro em caso de Exception capturada
                    
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

    /**
     * O QTA (Automatic Transfer Switch).
     * Roda no último milissegundo caso o servidor PHP desabe (ex: Out of Memory, Parse Error fatal).
     */
    public function handleFatalCrash(): void
    {
        $error = error_get_last();
        
        // Se houve erro e ele é um erro fatal imperdoável
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            
            // Se a queda de energia ocorreu enquanto um plugin tentava inicializar
            if ($this->currentBootingPlugin !== null) {
                
                // Desativa o plugin na fiação rígida (JSON)
                $this->disable($this->currentBootingPlugin);
                
                // Inicia sessão de emergência para deixar o bilhete de aviso
                if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                    session_start();
                }
                
                if (session_status() !== PHP_SESSION_NONE) {
                    $_SESSION['plugin_crashes'][] = [
                        'plugin' => $this->currentBootingPlugin,
                        'error' => "FATAL CRASH (QTA Acionado pelo Gerador): " . $error['message']
                    ];
                }
                
                error_log("QTA ACIONADO! Plugin '{$this->currentBootingPlugin}' sofreu um colapso fatal (Ex: Fim de Memória) e foi ejetado automaticamente. Erro: " . $error['message']);
            }
        }
    }
}
