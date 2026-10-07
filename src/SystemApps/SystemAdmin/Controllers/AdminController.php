<?php

namespace DomainSystem\SystemApps\SystemAdmin\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Theme\ThemeManager;
use Exception;

class AdminController
{
    private PluginManager $manager;
    private ThemeManagerInterface $theme;
    private \DomainSystem\Core\Theme\ShortcodeManager $shortcodes;
    private \DomainSystem\Core\Contracts\EventDispatcherInterface $dispatcher;

    public function __construct(PluginManager $manager, ThemeManagerInterface $theme, \DomainSystem\Core\Theme\ShortcodeManager $shortcodes, \DomainSystem\Core\Contracts\EventDispatcherInterface $dispatcher, \DomainSystem\Core\Contracts\SessionManagerInterface $session)
    {
        $this->manager = $manager;
        $this->theme = $theme;
        $this->shortcodes = $shortcodes;
        $this->dispatcher = $dispatcher;
        $this->session = $session;
    }

    public function listPlugins(\DomainSystem\Core\Http\Request $request)
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
        $foldersToScan = [dirname(__DIR__, 3) . '/SystemApps' => true, dirname(__DIR__, 3) . '/Plugins' => false];
        foreach ($foldersToScan as $scanPath => $isSystemApp) {
            if (is_dir($scanPath)) {
                $directories = glob($scanPath . '/*', GLOB_ONLYDIR);
                foreach ($directories as $dir) {
                    $jsonPath = $dir . '/plugin.json';
                    if (file_exists($jsonPath)) {
                        $metadata = json_decode(file_get_contents($jsonPath), true);
                        $name = $metadata['name'] ?? basename($dir);
                        $folder = basename($dir);
                        $subplugins = [];
                        if (isset($metadata['components']) && is_array($metadata['components'])) {
                            foreach ($metadata['components'] as $comp) {
                                $subplugins[] = ['name' => $comp['name'] ?? 'Componente', 'version' => $comp['version'] ?? 'Integrado', 'description' => $comp['description'] ?? ''];
                            }
                        }
                        if (is_dir($dir . '/bundled_plugins')) {
                            $subDirs = glob($dir . '/bundled_plugins/*', GLOB_ONLYDIR);
                            foreach ($subDirs as $subDir) {
                                $subJson = $subDir . '/plugin.json';
                                if (file_exists($subJson)) {
                                    $subMeta = json_decode(file_get_contents($subJson), true);
                                    $subplugins[] = ['name' => $subMeta['name'] ?? basename($subDir), 'version' => $subMeta['version'] ?? '1.0.0', 'description' => $subMeta['description'] ?? ''];
                                }
                            }
                        }
                        $allPlugins[] = [
                            'folder' => $folder,
                            'name' => $name,
                            'version' => $metadata['version'] ?? 'N/A',
                            'description' => $metadata['description'] ?? '',
                            'is_active' => $isSystemApp ? true : ($activeStates[$name] ?? ($activeStates[$folder] ?? false)),
                            'is_core' => $isSystemApp ? true : ((isset($metadata['core']) && $metadata['core'] === true) ? true : $this->manager->isCore($name)),
                            'is_disarmed' => isset($disarmedStates[$name]) && !($activeStates[$name] ?? false),
                            'subplugins' => $subplugins
                        ];
                    }
                }
            }
        }

        $allPlugins = $this->dispatcher->applyFilters('admin.plugins.list', $allPlugins);




        // Capture crashes from session
        $crashes = [];

        if ($this->session->has('plugin_crashes')) {
            $crashes = $this->session->get('plugin_crashes');
            $this->session->remove('plugin_crashes');
        }

        $isDevMode = $this->session->get('dev_mode') === true && $this->session->get('dev_mode_expires') > time();

        try {
            return $this->theme->render('plugins', [
                'plugins' => $allPlugins,
                'crashes' => $crashes,
                'isDevMode' => $isDevMode
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar painel administrativo: " . $e->getMessage();
        }
    }

    public function togglePlugin(\DomainSystem\Core\Http\Request $request)
    {

        $pluginName = $request->input('plugin_name');
        $action = $request->input('action');

        if ($pluginName && $action) {
            $isDevMode = $this->session->get('dev_mode') === true && $this->session->get('dev_mode_expires') > time();
            $basePath = dirname(__DIR__, 4);
            $isSystemApp = is_dir($basePath . '/src/SystemApps/' . $pluginName);
            
            $isCoreFlag = false;
            if (file_exists($basePath . '/src/Plugins/' . $pluginName . '/plugin.json')) {
                $meta = json_decode(file_get_contents($basePath . '/src/Plugins/' . $pluginName . '/plugin.json'), true);
                $isCoreFlag = !empty($meta['core']);
            }
            $isCore = $isSystemApp || $isCoreFlag || $this->manager->isCore($pluginName);

            if ($action === 'enable') {
                if ($isCore && !$isDevMode) {
                    $this->session->setFlash('error', '❌ Acesso Negado: É necessário o Modo Desenvolvedor para reativar módulos vitais.');
                    return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/plugins");
                }
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

                $this->session->setFlash('success', '✅ Plugin ativado com sucesso!');
            } elseif ($action === 'disable') {
                $this->manager->disable($pluginName);
                $this->session->setFlash('success', '✔️ Plugin desativado com sucesso.');
            }
        }

        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/plugins");
    }

    public function uploadPlugin(\DomainSystem\Core\Http\Request $request)
    {

        
        $file = $request->file('plugin_zip');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $this->manager->installFromZip($file['tmp_name']);
                $this->session->setFlash('success', '✔️ Plugin instalado com sucesso! A descompactação e ligação foram concluídas.');
            } catch (\Exception $e) {
                $this->session->setFlash('error', '❌ Erro na instalação: ' . $e->getMessage());
            }
        }

        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/plugins");
    }

    public function deletePlugin(\DomainSystem\Core\Http\Request $request)
    {

        $pluginName = $request->input('plugin_name');
        $pluginFolder = $request->input('plugin_folder');

        if ($pluginName && $pluginFolder) {
            // Seguranca: sanitizar pasta para evitar directory traversal
            $pluginFolder = basename($pluginFolder);
            try {
                $this->manager->delete($pluginName, $pluginFolder);
                $this->session->setFlash('success', '✅ Plugin excluído e removido do servidor.');
            } catch (\Exception $e) {
                $this->session->setFlash('error', $e->getMessage());
            }
        }

        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/plugins");
    }

    public function listThemes(\DomainSystem\Core\Http\Request $request)
    {
        $basePath = dirname(__DIR__, 4);
        $themesPath = $basePath . '/themes';
        
        $themes = [];
        // 1. Temas do FlexTheme
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
                $isCore = in_array($folder, ['admin', 'manager', 'subscriber', 'user', 'default']);
                
                if (file_exists($jsonPath)) {
                    $meta = json_decode(file_get_contents($jsonPath), true);
                    $name = $meta['name'] ?? $name;
                    $description = $meta['description'] ?? '';
                    $version = $meta['version'] ?? '1.0.0';
                    $author = $meta['author'] ?? '';
                    $screenshot = $meta['screenshot'] ?? '';
                }
                
                if ($isCore) {
                    continue; // Oculta temas Core (como admin) da interface
                }
                
                $themes[] = [
                    'folder' => $folder,
                    'name' => $name,
                    'description' => $description,
                    'version' => $version,
                    'author' => $author,
                    'screenshot' => $screenshot,
                    'is_core' => $isCore,
                    'is_bundled' => false,
                    'is_add_new' => false,
                    'plugin' => null,
                    'preview_url' => \BASE_URL . '/admin' // Ajustar depois para prever
                ];
            }
        }
        
        // 2. Temas empacotados dentro de Plugins Ativos (Bundled Themes)
        $pluginsPath = $basePath . '/src/Plugins';
        $configPath = $basePath . '/config/plugins.json';
        $activeStates = file_exists($configPath) ? (json_decode(file_get_contents($configPath), true) ?? []) : [];

        if (is_dir($pluginsPath)) {
            foreach (glob($pluginsPath . '/*', GLOB_ONLYDIR) as $pluginDir) {
                $pluginName = basename($pluginDir);
                if (isset($activeStates[$pluginName]) && $activeStates[$pluginName] === true) {
                    $pluginThemesPath = $pluginDir . '/themes';
                    if (is_dir($pluginThemesPath)) {
                        foreach (glob($pluginThemesPath . '/*', GLOB_ONLYDIR) as $themeDir) {
                            $folder = basename($themeDir);
                            $jsonPath = $themeDir . '/theme.json';
                            
                            $name = ucfirst($folder);
                            $description = "Tema integrado ao pacote {$pluginName}.";
                            $version = '1.0.0';
                            $author = '';
                            $screenshot = '';
                            
                            if (file_exists($jsonPath)) {
                                $meta = json_decode(file_get_contents($jsonPath), true);
                                $name = $meta['name'] ?? $name;
                                $description = $meta['description'] ?? $description;
                                $version = $meta['version'] ?? '1.0.0';
                                $author = $meta['author'] ?? '';
                                $screenshot = $meta['screenshot'] ?? '';
                            }
                            
                            $previewUrl = \BASE_URL . '/cockpit/' . str_replace('_cockpit', '', str_replace('cockpit_', '', $folder));
                            if ($pluginName === 'flextheme') {
                                $previewUrl = \BASE_URL . '/';
                            } elseif ($folder === 'public_booking') {
                                $previewUrl = \BASE_URL . '/agendamento';
                            }

                            $themes[] = [
                                'folder' => $folder,
                                'name' => $name,
                                'description' => $description,
                                'version' => $version,
                                'author' => $author,
                                'screenshot' => $screenshot,
                                'is_core' => false,
                                'is_bundled' => true,
                                'is_add_new' => false,
                                'plugin' => $pluginName,
                                'preview_url' => $previewUrl
                            ];
                        }
                    }
                }
            }
        }
        
        // 3. Pseudo-theme "Add New"
        $themes[] = [
            'folder' => '',
            'name' => 'Criar Novo Tema',
            'description' => 'Crie uma nova interface pública ou isolada do zero.',
            'version' => '',
            'author' => '',
            'screenshot' => '',
            'is_core' => false,
            'is_bundled' => false,
            'is_add_new' => true,
            'plugin' => null,
            'preview_url' => '#'
        ];
        
        try {
            return $this->theme->render('themes_panel', [
                'themes' => $themes
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar painel de temas: " . $e->getMessage();
        }
    }

    public function uploadTheme(\DomainSystem\Core\Http\Request $request)
    {
        $file = $request->file('theme_zip');
        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $basePath = dirname(__DIR__, 4);
                $themesPath = $basePath . '/themes';
                
                $extractor = \DomainSystem\Core\Utils\Archive\ExtractorFactory::create();
                $extractor->extract($file['tmp_name'], $themesPath, 'theme.json');
                
                $this->session->setFlash('success', '✅ Tema instalado com sucesso! A descompactação foi concluída.');
            } catch (\Exception $e) {
                $this->session->setFlash('error', '❌ Erro na instalação: ' . $e->getMessage());
            }
        } else {
            $this->session->setFlash('error', '❌ Erro no upload do arquivo.');
        }

        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
    }

    public function previewTheme(\DomainSystem\Core\Http\Request $request)
    {
        $themeFolder = $request->input('theme', '');
        if (empty($themeFolder)) {
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
        }
        
        $basePath = dirname(__DIR__, 4);
        $themeDir = $basePath . '/themes/' . basename($themeFolder);
        if (!is_dir($themeDir)) {
            // Fallback para temas em plugins (ex: flextheme)
            $themeDirPlugin = $basePath . '/src/Plugins/flextheme/themes/' . basename($themeFolder);
            if (is_dir($themeDirPlugin)) {
                $themeDir = $themeDirPlugin;
            }
        }

        if (!is_dir($themeDir)) {
            $this->session->setFlash('error', 'Tema não encontrado para preview.');
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
        }
        
        // Temporarily set active theme to the requested preview theme
        $this->theme->setActiveThemePath($themeDir);
        
        try {
            // Render index if it exists, otherwise layout
            if (file_exists($themeDir . '/index.php')) {
                return $this->theme->render('index', []);
            } else {
                return $this->theme->render('layout', ['content' => '<div style="padding: 50px; text-align: center; font-family: sans-serif;"><h1>Preview do Tema: ' . htmlspecialchars($themeFolder) . '</h1><p>Nenhuma view index.php foi encontrada para este tema.</p></div>']);
            }
        } catch (\Exception $e) {
            return "Erro ao renderizar preview do tema: " . $e->getMessage();
        }
    }

    public function createTheme(\DomainSystem\Core\Http\Request $request)
    {

        
        $name = $request->input('theme_name', '');
        $description = $request->input('theme_description', '');
        $author = $request->input('theme_author', '');
        
        if (empty($name)) {
            $this->session->setFlash('error', 'Nome do tema é obrigatório.');
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
        }
        
        $folder = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $folder = trim($folder, '-');
        
        $basePath = dirname(__DIR__, 4);
        $themeDir = $basePath . '/themes/' . $folder;
        
        if (is_dir($themeDir)) {
            $this->session->setFlash('error', 'Já existe um tema com esse nome/pasta.');
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
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
        
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $layoutHtml = "<!DOCTYPE html>\n<html lang=\"pt-BR\">\n<head>\n    <meta charset=\"UTF-8\">\n    <title>{$safeName}</title>\n</head>\n<body>\n    <h1>{$safeName}</h1>\n    <?= \$content ?? '' ?>\n</body>\n</html>";
        file_put_contents($themeDir . '/layout.php', $layoutHtml);
        
        $this->session->setFlash('success', 'Tema scaffolding criado com sucesso!');
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
    }

    public function deleteTheme(\DomainSystem\Core\Http\Request $request)
    {

        
        $folder = $request->input('theme_folder', '');
        $folder = basename(trim($folder));
        if ($folder === '.' || $folder === '..') {
            $this->session->setFlash('error', '❌ Nome de pasta inválido.');
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
        }
        
        if (empty($folder) || in_array($folder, ['admin', 'manager', 'subscriber', 'user', 'default'])) {
            $this->session->setFlash('error', '❌ Não é permitido excluir temas core vitais do sistema.');
            return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
        }
        
        $basePath = dirname(__DIR__, 4);
        $themeDir = $basePath . '/themes/' . $folder;
        
        if (is_dir($themeDir)) {
            $this->deleteDirectory($themeDir);
            $this->session->setFlash('success', '✅ Tema excluído com segurança e apagado do disco.');
        } else {
            $this->session->setFlash('error', 'Tema não encontrado.');
        }
        
        return \DomainSystem\Core\Http\Response::redirect(\BASE_URL . "/admin/themes");
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

    public function listShortcodes(\DomainSystem\Core\Http\Request $request)
    {
        $shortcodes = $this->shortcodes->getRegisteredShortcodes();
        
        try {
            return $this->theme->render('shortcodes', [
                'shortcodes' => $shortcodes
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar catálogo de shortcodes: " . $e->getMessage();
        }
    }
}





