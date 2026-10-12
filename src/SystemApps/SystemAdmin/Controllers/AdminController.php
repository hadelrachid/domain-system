<?php

namespace DomainSystem\SystemApps\SystemAdmin\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Theme\ThemeManager;
use Exception;

use DomainSystem\Core\Contracts\ThemeActivatorInterface;
use DomainSystem\Core\Theme\ThemeActivator;

class AdminController
{
    private PluginManager $manager;
    private ThemeManagerInterface $theme;
    private \DomainSystem\Core\Theme\ShortcodeManager $shortcodes;
    private \DomainSystem\Core\Contracts\EventDispatcherInterface $dispatcher;
    private \DomainSystem\Core\Contracts\SessionManagerInterface $session;
    private ThemeActivatorInterface $themeActivator;

    public function __construct(
        PluginManager $manager,
        ThemeManagerInterface $theme,
        \DomainSystem\Core\Theme\ShortcodeManager $shortcodes,
        \DomainSystem\Core\Contracts\EventDispatcherInterface $dispatcher,
        \DomainSystem\Core\Contracts\SessionManagerInterface $session,
        ?ThemeActivatorInterface $themeActivator = null
    ) {
        $this->manager = $manager;
        $this->theme = $theme;
        $this->shortcodes = $shortcodes;
        $this->dispatcher = $dispatcher;
        $this->session = $session;
        $basePath = dirname(__DIR__, 4);
        $this->themeActivator = $themeActivator ?? new ThemeActivator($basePath, null, $dispatcher);
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
        $activeTheme = $this->themeActivator->getActiveTheme();
        
        $themes = [];
        $seenFolders = [];

        // 1. Temas do diretório raiz /themes
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
                $isCore = in_array($folder, ['admin', 'manager', 'subscriber', 'user'], true);
                
                if (file_exists($jsonPath)) {
                    $meta = json_decode(file_get_contents($jsonPath), true);
                    $name = $meta['name'] ?? $name;
                    $description = $meta['description'] ?? '';
                    $version = $meta['version'] ?? '1.0.0';
                    $author = $meta['author'] ?? '';
                    $screenshot = $meta['screenshot'] ?? '';
                }
                
                if ($isCore) {
                    continue; // Oculta temas de workspace interno (admin/cockpits de sistema)
                }

                $seenFolders[$folder] = true;
                $isActive = ($folder === $activeTheme);
                $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                $previewUrl = $isActive ? ($baseUrl !== '' ? $baseUrl : '/') : ($baseUrl . '/admin/themes/preview?theme=' . urlencode($folder));
                
                $themes[] = [
                    'folder' => $folder,
                    'name' => $name,
                    'description' => $description,
                    'version' => $version,
                    'author' => $author,
                    'screenshot' => $screenshot,
                    'is_core' => false,
                    'is_bundled' => false,
                    'is_add_new' => false,
                    'is_active' => $isActive,
                    'plugin' => null,
                    'preview_url' => $previewUrl
                ];
            }
        }
        
        // 2. Temas empacotados dentro de Plugins Ativos (Bundled Themes, ex: flextheme)
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
                            if (isset($seenFolders[$folder])) {
                                continue;
                            }
                            $seenFolders[$folder] = true;

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
                            
                            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
                            $isActive = ($folder === $activeTheme);
                            $previewUrl = $isActive ? ($baseUrl !== '' ? $baseUrl : '/') : ($baseUrl . '/admin/themes/preview?theme=' . urlencode($folder));

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
                                'is_active' => $isActive,
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
            'is_active' => false,
            'plugin' => null,
            'preview_url' => '#'
        ];
        
        try {
            return $this->theme->render('themes_panel', [
                'themes' => $themes,
                'active_theme' => $activeTheme
            ]);
        } catch (Exception $e) {
            return "Erro ao renderizar painel de temas: " . $e->getMessage();
        }
    }

    public function activateTheme(\DomainSystem\Core\Http\Request $request)
    {
        $themeFolder = (string)$request->input('theme_folder', '');

        if (empty($themeFolder)) {
            $this->session->setFlash('error', 'Nome do tema não informado.');
            return \DomainSystem\Core\Http\Response::redirect((defined('BASE_URL') ? BASE_URL : '') . "/admin/themes");
        }

        $result = $this->themeActivator->activate($themeFolder);

        if ($result['success']) {
            $this->session->setFlash('success', "✅ " . $result['message']);
        } else {
            $this->session->setFlash('error', "❌ " . $result['message']);
        }

        return \DomainSystem\Core\Http\Response::redirect((defined('BASE_URL') ? BASE_URL : '') . "/admin/themes");
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

    public function previewTheme(\DomainSystem\Core\Http\Request $request): \DomainSystem\Core\Contracts\ResponseInterface
    {
        $themeFolder = (string)$request->input('theme', '');
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        if (empty($themeFolder)) {
            return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/themes");
        }
        
        $resolver = ($this->themeActivator instanceof \DomainSystem\Core\Theme\ThemeActivator)
            ? $this->themeActivator->getResolver()
            : new \DomainSystem\Core\Theme\SafeThemeResolver([
                dirname(__DIR__, 4) . '/themes',
                dirname(__DIR__, 4) . '/src/Plugins/flextheme/themes'
            ]);

        if (!$resolver->themeExists($themeFolder)) {
            $this->session->setFlash('error', "Tema '{$themeFolder}' não foi encontrado para visualização.");
            return \DomainSystem\Core\Http\Response::redirect($baseUrl . "/admin/themes");
        }

        $themeDir = $resolver->resolveThemePath($themeFolder);
        $csrfToken = $this->session->getCsrfToken();

        // Determina o arquivo principal de entrada do tema
        $html = '';
        if (file_exists($themeDir . '/index.php')) {
            ob_start();
            $siteName = 'Domain-System OS';
            require $themeDir . '/index.php';
            $html = ob_get_clean();
        } elseif (file_exists($themeDir . '/templates/home.php')) {
            ob_start();
            $siteName = 'Domain-System OS';
            require $themeDir . '/templates/home.php';
            $innerContent = ob_get_clean();

            if (file_exists($themeDir . '/layouts/main.php')) {
                $content = $innerContent;
                ob_start();
                require $themeDir . '/layouts/main.php';
                $html = ob_get_clean();
            } else {
                $html = "<!DOCTYPE html><html lang='pt-BR'><head><meta charset='UTF-8'><title>Preview - " . htmlspecialchars($themeFolder) . "</title></head><body>" . $innerContent . "</body></html>";
            }
        } else {
            $html = "<div style='padding:50px; text-align:center; font-family:sans-serif;'><h1>Tema: " . htmlspecialchars($themeFolder) . "</h1><p>Nenhum index.php ou templates/home.php encontrado.</p></div>";
        }

        // Processa shortcodes no HTML do tema (ex: [hub_de_cursos], [nav_menu])
        if ($this->shortcodes !== null) {
            $html = $this->shortcodes->parse($html);
        }

        // Barra flutuante de visualização (WordPress-style Live Preview)
        $previewBar = "
        <div id='ds-preview-topbar' style='position:fixed; top:0; left:0; width:100%; height:46px; background:#1d2327; color:#fff; display:flex; justify-content:space-between; align-items:center; padding:0 20px; box-sizing:border-box; z-index:99999999; font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif; font-size:13px; border-bottom:2px solid #2271b1; box-shadow:0 2px 10px rgba(0,0,0,0.6);'>
            <div style='display:flex; align-items:center; gap:12px;'>
                <span style='background:#2271b1; color:#fff; padding:3px 8px; border-radius:3px; font-weight:700; font-size:11px; text-transform:uppercase;'>Preview Ativo</span>
                <span>Visualizando tema: <strong style='color:#45f3ff;'>" . htmlspecialchars($themeFolder) . "</strong></span>
                <span style='color:#8c8f94; font-size:12px;'>(Este tema ainda não é o padrão público do site)</span>
            </div>
            <div style='display:flex; gap:10px; align-items:center;'>
                <form method='POST' action='{$baseUrl}/admin/themes/activate' style='margin:0;'>
                    <input type='hidden' name='csrf_token' value='{$csrfToken}'>
                    <input type='hidden' name='theme_folder' value='" . htmlspecialchars($themeFolder, ENT_QUOTES, 'UTF-8') . "'>
                    <button type='submit' style='background:#00a32a; color:#fff; border:none; padding:6px 14px; border-radius:4px; font-weight:600; cursor:pointer; font-size:12px; transition:opacity 0.2s;' onmouseover=\"this.style.opacity='0.9'\" onmouseout=\"this.style.opacity='1'\">
                        ✓ Ativar este Tema
                    </button>
                </form>
                <a href='{$baseUrl}/admin/themes' style='color:#fff; text-decoration:none; padding:6px 12px; background:rgba(255,255,255,0.12); border-radius:4px; font-size:12px;'>✕ Fechar e Voltar</a>
            </div>
        </div>
        <div style='height:46px;'></div>
        ";

        if (stripos($html, '<body') !== false) {
            $html = preg_replace('/<body([^>]*)>/i', '<body$1>' . $previewBar, $html, 1);
        } else {
            $html = $previewBar . $html;
        }

        return new \DomainSystem\Core\Http\Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
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





