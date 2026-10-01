<?php

namespace DomainSystem\Core\Theme;

use Exception;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;

class ThemeManager implements ThemeManagerInterface
{
    private string $activeThemePath;
    public ?EventDispatcherInterface $dispatcher = null;
    private ?ShortcodeManager $shortcodeManager = null;

    public function __construct(string $activeThemePath, ?ShortcodeManager $shortcodeManager = null)
    {
        $this->activeThemePath = rtrim($activeThemePath, '/\\');
        $this->shortcodeManager = $shortcodeManager;
    }

    public function setActiveThemePath(string $path): void
    {
        $this->activeThemePath = rtrim($path, '/\\');
    }

    public function getActiveThemePath(): string
    {
        return $this->activeThemePath;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function render(string $template, array $args = [], ?string $pluginViewsDir = null): string
    {
        $file = $this->activeThemePath . '/' . $template . '.php';

        if (!file_exists($file)) {
            if ($pluginViewsDir !== null) {
                $file = $pluginViewsDir . '/' . basename($template) . '.php';
            }
            if (!file_exists($file)) {
                throw new Exception("Template '{$template}' not found in theme or plugin.");
            }
        }

        extract($args);
        
        ob_start();
        include $file;
        $content = ob_get_clean();
        
        if ($this->shortcodeManager !== null) {
            $content = $this->shortcodeManager->parse($content);
        }
        
        return $content;
    }

    public function get_header(array $args = []): string
    {
        try {
            $html = $this->render('header', $args);
            echo $html;
            return $html;
        } catch (Exception $e) {
            return '';
        }
    }

    public function get_footer(array $args = []): string
    {
        try {
            $html = $this->render('footer', $args);
            echo $html;
            return $html;
        } catch (Exception $e) {
            return '';
        }
    }

    public function getAvailableThemes(): array
    {
        $themesDir = DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes';
        $validThemes = [];
        
        if (is_dir($themesDir)) {
            foreach (scandir($themesDir) as $folder) {
                if ($folder !== '.' && $folder !== '..' && is_dir($themesDir . '/' . $folder)) {
                    if ($this->validateThemeContract($themesDir . '/' . $folder)) {
                        $manifestPath = $themesDir . '/' . $folder . '/theme.json';
                        $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
                        $validThemes[$folder] = [
                            'folder' => $folder,
                            'name' => $manifest['name'] ?? $folder,
                            'version' => $manifest['version'] ?? '1.0.0',
                            'author' => $manifest['author'] ?? 'Desconhecido',
                            'description' => $manifest['description'] ?? ''
                        ];
                    }
                }
            }
        }
        return $validThemes;
    }

    private function validateThemeContract(string $themePath): bool
    {
        // 1. theme.json é Obrigatório
        if (!file_exists($themePath . '/theme.json')) {
            return false;
        }
        
        // 2. index.php é Obrigatório (Fallback)
        if (!file_exists($themePath . '/index.php')) {
            return false;
        }
        
        // 3. assets/ é Obrigatório
        if (!is_dir($themePath . '/assets')) {
            return false;
        }
        
        // 4. templates/ é Obrigatório (O OS precisa saber onde injetar as views das páginas)
        if (!is_dir($themePath . '/templates')) {
            return false;
        }

        return true;
    }

    public function getAvailableTemplates(?string $themeName = null): array
    {
        if ($themeName) {
            $themeDir = DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes/' . basename($themeName) . '/templates';
        } else {
            $themeDir = $this->activeThemePath . '/templates';
        }
        
        $files = [];
        if (is_dir($themeDir)) {
            foreach (glob($themeDir . '/*.php') as $file) {
                $files[] = basename($file);
            }
        }
        return $files;
    }

    public function getAvailablePages(?string $themeName = null): array
    {
        if ($themeName) {
            $pagesDir = DOMAIN_SYSTEM_ROOT . '/src/Plugins/flextheme/themes/' . basename($themeName) . '/pages';
        } else {
            $pagesDir = $this->activeThemePath . '/pages';
        }
        
        $files = [];
        if (is_dir($pagesDir)) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pagesDir));
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), ['php', 'html'])) {
                    $files[] = 'pages/' . str_replace('\\', '/', str_replace($pagesDir . DIRECTORY_SEPARATOR, '', $file->getPathname()));
                }
            }
        }
        return $files;
    }
}
