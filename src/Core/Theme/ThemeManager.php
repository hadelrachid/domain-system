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
    private ?\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface $partRenderer = null;

    public function __construct(string $activeThemePath, ?ShortcodeManager $shortcodeManager = null, ?\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface $partRenderer = null)
    {
        $this->activeThemePath = rtrim($activeThemePath, '/\\');
        $this->shortcodeManager = $shortcodeManager;
        $this->partRenderer = $partRenderer;
    }

    public function setPartRenderer(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface $renderer): void
    {
        $this->partRenderer = $renderer;
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
        // 1. Verifica se há uma seção modular do FlexBuilder atribuída diretamente a este template
        $renderer = $this->resolvePartRenderer();
        $assignedSection = '';
        if ($renderer !== null) {
            $themeFolder = basename($this->activeThemePath);
            $cleanTemplate = str_replace(['templates/', '.php'], '', $template);
            $assignedSection = $renderer->renderTemplateSection($themeFolder, $cleanTemplate, $args);
        }

        $file = $this->activeThemePath . '/' . ltrim($template, '/') . '.php';
        $realFile = realpath($file);
        $realThemePath = realpath($this->activeThemePath);

        if ($realFile !== false && $realThemePath !== false && str_starts_with($realFile, $realThemePath)) {
            $fileToInclude = $realFile;
        } else {
            $fileToInclude = null;
        }

        if (!$fileToInclude || !file_exists($fileToInclude)) {
            if ($pluginViewsDir !== null) {
                $file = $pluginViewsDir . '/' . basename($template) . '.php';
                $realFile = realpath($file);
                $realPluginDir = realpath($pluginViewsDir);
                if ($realFile !== false && $realPluginDir !== false && str_starts_with($realFile, $realPluginDir)) {
                    $fileToInclude = $realFile;
                }
            }
            if (!$fileToInclude || !file_exists($fileToInclude)) {
                if (trim($assignedSection) !== '') {
                    return $this->shortcodeManager !== null ? $this->shortcodeManager->parse($assignedSection) : $assignedSection;
                }
                throw new \Exception("Template '{$template}' not found in theme or plugin.");
            }
        }

        $args['assignedSection'] = $assignedSection;
        extract($args, EXTR_SKIP);
        
        $obLevel = ob_get_level();
        ob_start();
        try {
            include $fileToInclude;
            $content = ob_get_clean();
        } catch (\Throwable $t) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            throw $t;
        }

        // Se uma seção foi atribuída e o template não a imprimiu internamente, concatena abaixo
        if (trim($assignedSection) !== '' && !str_contains($content, $assignedSection)) {
            $content .= "\n" . $assignedSection;
        }
        
        if ($this->shortcodeManager !== null) {
            $content = $this->shortcodeManager->parse($content);
        }
        
        return $content;
    }

    public function get_header(array $args = []): string
    {
        try {
            $renderer = $this->resolvePartRenderer();
            if ($renderer !== null) {
                $themeFolder = basename($this->activeThemePath);
                $html = $renderer->renderHeader($themeFolder, $args);
                if (trim($html) !== '') {
                    echo $html;
                    return $html;
                }
            }

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
            $renderer = $this->resolvePartRenderer();
            if ($renderer !== null) {
                $themeFolder = basename($this->activeThemePath);
                $html = $renderer->renderFooter($themeFolder, $args);
                if (trim($html) !== '') {
                    echo $html;
                    return $html;
                }
            }

            $html = $this->render('footer', $args);
            echo $html;
            return $html;
        } catch (Exception $e) {
            return '';
        }
    }

    private function resolvePartRenderer(): ?\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface
    {
        if ($this->partRenderer !== null) {
            return $this->partRenderer;
        }

        if (isset($GLOBALS['app'])) {
            try {
                $c = $GLOBALS['app']->getContainer();
                if ($c->has(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class)) {
                    $this->partRenderer = $c->make(\DomainSystem\Core\Theme\Parts\Contracts\ThemePartRendererInterface::class);
                    return $this->partRenderer;
                }
            } catch (\Throwable $e) {}
        }

        return null;
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
        
        // 3. assets/ é Obrigatório (garante diretório se ausente)
        if (!is_dir($themePath . '/assets')) {
            @mkdir($themePath . '/assets', 0777, true);
        }
        if (!is_dir($themePath . '/assets')) {
            return false;
        }
        
        // 4. templates/ é Obrigatório (O OS precisa saber onde injetar as views das páginas)
        if (!is_dir($themePath . '/templates')) {
            return false;
        }

        // 5. pages/ é Obrigatório (Páginas estáticas autônomas, por contrato OS 2.0)
        if (!is_dir($themePath . '/pages')) {
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
