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
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $pluginName = $_POST['plugin_name'] ?? null;
        $action = $_POST['action'] ?? null;

        if ($pluginName && $action) {
            /** @var \DomainSystem\Core\Plugin\PluginManager $manager */
            $manager = $this->container->make(\DomainSystem\Core\Plugin\PluginManager::class);
            
            if ($action === 'enable') {
                $manager->enable($pluginName);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✔️ Plugin ativado com sucesso!'];
            } elseif ($action === 'disable') {
                $manager->disable($pluginName);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✔️ Plugin desativado com sucesso.'];
            }
        }

        header("Location: " . BASE_URL . "/admin/plugins");
        exit;
    }

    public function uploadPlugin()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        if (isset($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
            try {
                /** @var \DomainSystem\Core\Plugin\PluginManager $manager */
                $manager = $this->container->make(\DomainSystem\Core\Plugin\PluginManager::class);
                $manager->installFromZip($_FILES['plugin_zip']['tmp_name']);
                
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✔️ Plugin instalado com sucesso! A descompactação e ligação foram concluídas.'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => '❌ Erro na instalação: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/plugins");
        exit;
    }

    public function deletePlugin()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $pluginName = $_POST['plugin_name'] ?? null;
        $pluginFolder = $_POST['plugin_folder'] ?? null;

        if ($pluginName && $pluginFolder) {
            try {
                /** @var \DomainSystem\Core\Plugin\PluginManager $manager */
                $manager = $this->container->make(\DomainSystem\Core\Plugin\PluginManager::class);
                $manager->delete($pluginName, $pluginFolder);
                
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '🗑️ Plugin excluído e removido do servidor.'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/plugins");
        exit;
    }
}
