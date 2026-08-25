<?php

namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Theme\ThemeManager;
use Exception;

class AdminController
{
    private PluginManager $manager;
    private ThemeManager $theme;

    public function __construct(PluginManager $manager, ThemeManager $theme)
    {
        $this->manager = $manager;
        $this->theme = $theme;
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

        // Get Disarmed states
        $disarmedStates = [];
        $disarmedPath = $basePath . '/temp/disarmed.json';
        if (file_exists($disarmedPath)) {
            $disarmedStates = json_decode(file_get_contents($disarmedPath), true) ?? [];
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
                        'is_core' => $this->manager->isCore($name),
                        'is_disarmed' => isset($disarmedStates[$name]) && !($activeStates[$name] ?? false)
                    ];
                }
            }
        }

        // Capture crashes from session
        $crashes = [];
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (!empty($_SESSION['plugin_crashes'])) {
            $crashes = $_SESSION['plugin_crashes'];
            unset($_SESSION['plugin_crashes']);
        }

        try {
            return $this->theme->render('plugins', [
                'plugins' => $allPlugins,
                'crashes' => $crashes
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar painel administrativo: " . $e->getMessage();
        }
    }

    public function togglePlugin()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $pluginName = $_POST['plugin_name'] ?? null;
        $action = $_POST['action'] ?? null;

        if ($pluginName && $action) {
            if ($action === 'enable') {
                $this->manager->enable($pluginName);
                
                // Remove from disarmed if exists
                $disarmedPath = dirname(__DIR__, 4) . '/temp/disarmed.json';
                if (file_exists($disarmedPath)) {
                    $disarmed = json_decode(file_get_contents($disarmedPath), true) ?: [];
                    if (isset($disarmed[$pluginName])) {
                        unset($disarmed[$pluginName]);
                        file_put_contents($disarmedPath, json_encode($disarmed));
                    }
                }

                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✅ Plugin ativado com sucesso!'];
            } elseif ($action === 'disable') {
                $this->manager->disable($pluginName);
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
                $this->manager->installFromZip($_FILES['plugin_zip']['tmp_name']);
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
            // Seguranca: sanitizar pasta para evitar directory traversal
            $pluginFolder = basename($pluginFolder);
            try {
                $this->manager->delete($pluginName, $pluginFolder);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✅ Plugin excluído e removido do servidor.'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/plugins");
        exit;
    }

    public function listThemes()
    {
        $basePath = dirname(__DIR__, 4);
        $themesPath = $basePath . '/themes';
        
        $themes = [];
        if (is_dir($themesPath)) {
            $directories = glob($themesPath . '/*', GLOB_ONLYDIR);
            foreach ($directories as $dir) {
                $folder = basename($dir);
                $jsonPath = $dir . '/theme.json';
                
                $name = ucfirst($folder);
                $description = '';
                $version = '1.0.0';
                $author = '';
                $screenshot = '';
                $isCore = in_array($folder, ['admin', 'doctor', 'secretary', 'lawyer', 'default']);
                
                if (file_exists($jsonPath)) {
                    $meta = json_decode(file_get_contents($jsonPath), true);
                    $name = $meta['name'] ?? $name;
                    $description = $meta['description'] ?? '';
                    $version = $meta['version'] ?? '1.0.0';
                    $author = $meta['author'] ?? '';
                    $screenshot = $meta['screenshot'] ?? '';
                }
                
                $themes[] = [
                    'folder' => $folder,
                    'name' => $name,
                    'description' => $description,
                    'version' => $version,
                    'author' => $author,
                    'screenshot' => $screenshot,
                    'is_core' => $isCore
                ];
            }
        }
        
        try {
            return $this->theme->render('themes_panel', [
                'themes' => $themes
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar painel de temas: " . $e->getMessage();
        }
    }

    public function createTheme()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $name = $_POST['theme_name'] ?? '';
        $description = $_POST['theme_description'] ?? '';
        $author = $_POST['theme_author'] ?? '';
        
        if (empty($name)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome do tema é obrigatório.'];
            header("Location: " . \BASE_URL . "/admin/themes");
            exit;
        }
        
        $folder = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $folder = trim($folder, '-');
        
        $basePath = dirname(__DIR__, 4);
        $themeDir = $basePath . '/themes/' . $folder;
        
        if (is_dir($themeDir)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Já existe um tema com esse nome/pasta.'];
            header("Location: " . \BASE_URL . "/admin/themes");
            exit;
        }
        
        mkdir($themeDir, 0777, true);
        
        $json = [
            'name' => $name,
            'description' => $description,
            'version' => '1.0.0',
            'author' => $author,
            'screenshot' => ''
        ];
        
        file_put_contents($themeDir . '/theme.json', json_encode($json, JSON_PRETTY_PRINT));
        
        $layoutHtml = "<!DOCTYPE html>\n<html lang=\"pt-BR\">\n<head>\n    <meta charset=\"UTF-8\">\n    <title>$name</title>\n</head>\n<body>\n    <h1>$name</h1>\n    <?= \$content ?? '' ?>\n</body>\n</html>";
        file_put_contents($themeDir . '/layout.php', $layoutHtml);
        
        $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Tema scaffolding criado com sucesso!'];
        header("Location: " . \BASE_URL . "/admin/themes");
        exit;
    }

    public function deleteTheme()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $folder = $_POST['theme_folder'] ?? '';
        $folder = basename($folder);
        
        if (empty($folder) || in_array($folder, ['admin', 'doctor', 'secretary', 'lawyer', 'default'])) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => '❌ Não é permitido excluir temas core vitais do sistema.'];
            header("Location: " . \BASE_URL . "/admin/themes");
            exit;
        }
        
        $basePath = dirname(__DIR__, 4);
        $themeDir = $basePath . '/themes/' . $folder;
        
        if (is_dir($themeDir)) {
            $this->deleteDirectory($themeDir);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✅ Tema excluído com segurança e apagado do disco.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Tema não encontrado.'];
        }
        
        header("Location: " . \BASE_URL . "/admin/themes");
        exit;
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
