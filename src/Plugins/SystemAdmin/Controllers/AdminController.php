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

    public function uploadPlugin()
    {
        $basePath = dirname(__DIR__, 4);
        $pluginsPath = $basePath . '/src/Plugins';
        
        if (isset($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
            $zipFile = $_FILES['plugin_zip']['tmp_name'];
            $zip = new \ZipArchive();
            
            if ($zip->open($zipFile) === TRUE) {
                // Ensure it's a valid plugin by checking for plugin.json
                $hasPluginJson = false;
                $pluginDirName = null;

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    // A valid zip usually has one root directory and inside it plugin.json
                    if (preg_match('#^([^/]+)/plugin\.json$#', $filename, $matches)) {
                        $hasPluginJson = true;
                        $pluginDirName = $matches[1];
                        break;
                    }
                }

                if ($hasPluginJson && $pluginDirName) {
                    $zip->extractTo($pluginsPath);
                } else {
                    // Tratar erro: ZIP inválido
                    die("ZIP inválido: Não possui um plugin.json na raiz do pacote.");
                }
                
                $zip->close();
            }
        }

        header("Location: /admin/plugins");
        exit;
    }

    public function deletePlugin()
    {
        $pluginName = $_POST['plugin_name'] ?? null;
        $pluginFolder = $_POST['plugin_folder'] ?? null;

        if ($pluginName && $pluginFolder && !in_array($pluginName, ['database', 'system-admin'])) {
            $basePath = dirname(__DIR__, 4);
            $pluginPath = $basePath . '/src/Plugins/' . $pluginFolder;
            $configPath = $basePath . '/config/plugins.json';

            // Ensure it is inactive before deleting
            $activeStates = [];
            if (file_exists($configPath)) {
                $activeStates = json_decode(file_get_contents($configPath), true) ?? [];
            }

            if (empty($activeStates[$pluginName])) {
                // Recursive delete
                $this->deleteDirectory($pluginPath);
            }
        }

        header("Location: /admin/plugins");
        exit;
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
}
