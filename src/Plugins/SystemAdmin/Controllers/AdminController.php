<?php

namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Theme\ThemeManager;
use Exception;

class AdminController
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function listPlugins()
    {
        // Define paths
        $basePath = dirname(__DIR__, 4); // DomainSystem root
        $pluginsPath = $basePath . '/src/Plugins';
        $configPath = $basePath . '/config/plugins.json';

        // Get Active states
        $activeStates = [];
        if (file_exists($configPath)) {
            $activeStates = json_decode(file_get_contents($configPath), true) ?? [];
        }

        // Discover all plugins
        $allPlugins = [];
        if (is_dir($pluginsPath)) {
            $directories = glob($pluginsPath . '/*', GLOB_ONLYDIR);
            foreach ($directories as $dir) {
                $jsonPath = $dir . '/plugin.json';
                if (file_exists($jsonPath)) {
                    $metadata = json_decode(file_get_contents($jsonPath), true);
                    $name = $metadata['name'] ?? basename($dir);
                    $allPlugins[] = [
                        'folder' => basename($dir),
                        'name' => $name,
                        'version' => $metadata['version'] ?? 'N/A',
                        'description' => $metadata['description'] ?? '',
                        'is_active' => $activeStates[$name] ?? false,
                        'is_core' => in_array($name, ['database', 'system-admin']) // Prevent disabling core
                    ];
                }
            }
        }

        /** @var ThemeManager $theme */
        $theme = $this->container->make(ThemeManager::class);

        try {
            return $theme->render('admin/plugins', ['plugins' => $allPlugins]);
        } catch (Exception $e) {
            // Fallback if theme template doesn't exist
            return "Erro ao renderizar painel administrativo: " . $e->getMessage();
        }
    }

    public function togglePlugin()
    {
        // Ideally we would use Request object, but for simplicity we'll use $_POST
        $pluginName = $_POST['plugin_name'] ?? null;
        $action = $_POST['action'] ?? null;

        if ($pluginName && $action) {
            $basePath = dirname(__DIR__, 4);
            $configPath = $basePath . '/config/plugins.json';

            $activeStates = [];
            if (file_exists($configPath)) {
                $activeStates = json_decode(file_get_contents($configPath), true) ?? [];
            }

            // Prevent toggling core plugins
            if (!in_array($pluginName, ['database', 'system-admin'])) {
                if ($action === 'enable') {
                    $activeStates[$pluginName] = true;
                } elseif ($action === 'disable') {
                    $activeStates[$pluginName] = false;
                }
                
                // Save
                file_put_contents($configPath, json_encode($activeStates, JSON_PRETTY_PRINT));
            }
        }

        // Redirect back
        header("Location: /admin/plugins");
        exit;
    }
}
