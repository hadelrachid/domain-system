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
                    $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✔️ Plugin ativado com sucesso!'];
                } elseif ($action === 'disable') {
                    $activeStates[$pluginName] = false;
                    $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✔️ Plugin desativado com sucesso.'];
                }
                
                // Save
                file_put_contents($configPath, json_encode($activeStates, JSON_PRETTY_PRINT));
            }
        }

        // Redirect back
        header("Location: " . BASE_URL . "/admin/plugins");
        exit;
    }

    public function uploadPlugin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $basePath = dirname(__DIR__, 4);
        $pluginsPath = $basePath . '/src/Plugins';
        
        if (isset($_FILES['plugin_zip']) && $_FILES['plugin_zip']['error'] === UPLOAD_ERR_OK) {
            $zipFile = $_FILES['plugin_zip']['tmp_name'];
            $hasPluginJson = false;
            $pluginDirName = null;
            $extracted = false;

            try {
                if (class_exists('ZipArchive')) {
                    $zip = new \ZipArchive();
                    if ($zip->open($zipFile) === TRUE) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $filename = $zip->getNameIndex($i);
                            if (preg_match('#^([^/]+)/plugin\.json$#', $filename, $matches)) {
                                $hasPluginJson = true;
                                $pluginDirName = $matches[1];
                                break;
                            }
                        }
                        if ($hasPluginJson) {
                            $zip->extractTo($pluginsPath);
                            $extracted = true;
                        }
                        $zip->close();
                    }
                } else if (class_exists('PharData')) {
                    // Fallback nativo maravilhoso usando PharData
                    $phar = new \PharData($zipFile);
                    
                    // Com PharData (dependendo de como foi zipado), os arquivos podem estar na raiz ou numa pasta
                    foreach ($phar as $file) {
                        if ($file->isDir()) {
                            // Verifica se dentro do dir tem o plugin.json
                            $dirName = $file->getFilename();
                            if (isset($phar[$dirName . '/plugin.json'])) {
                                $hasPluginJson = true;
                                $pluginDirName = $dirName;
                                break;
                            }
                        } else if ($file->getFilename() === 'plugin.json') {
                            // O zip foi feito sem pasta raiz. Precisamos criar a pasta com o nome do plugin
                            // Mas nosso sistema requer que o zip tenha uma pasta raiz.
                            // Vamos assumir que a pessoa zipou com a pasta raiz.
                        }
                    }
                    
                    // Se o Powershell zipou o conteúdo da pasta ao invés da pasta em si:
                    if (isset($phar['plugin.json'])) {
                        // Zipado sem pasta raiz (conteúdo direto)
                        // Vamos ler o plugin.json para pegar o nome
                        $json = file_get_contents($phar['plugin.json']->getPathname());
                        $data = json_decode($json, true);
                        if (isset($data['name'])) {
                            $hasPluginJson = true;
                            $pluginDirName = $data['name'];
                            // Extrair para uma subpasta
                            @mkdir($pluginsPath . '/' . $pluginDirName, 0777, true);
                            $phar->extractTo($pluginsPath . '/' . $pluginDirName);
                            $extracted = true;
                        }
                    } else if ($hasPluginJson) {
                        // Zipado com pasta raiz
                        $phar->extractTo($pluginsPath);
                        $extracted = true;
                    }
                } else {
                    throw new \Exception("Nenhum descompactador disponível (ZipArchive ou PharData).");
                }

                if ($extracted) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '✔️ Plugin instalado com sucesso! A descompactação e ligação foram concluídas.'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'msg' => '❌ ZIP inválido: Não possui um arquivo plugin.json válido no pacote.'];
                }
                
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => '❌ Erro na descompactação: ' . $e->getMessage()];
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
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => '🗑️ Plugin excluído e removido do servidor.'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => '❌ O plugin precisa ser desativado antes de ser excluído.'];
            }
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => '❌ Não é possível excluir plugins core do sistema.'];
        }

        header("Location: " . BASE_URL . "/admin/plugins");
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
