<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\Services;

use DomainSystem\Plugins\flextheme\Contracts\ThemeDiscoveryServiceInterface;

/**
 * Serviço responsável pela descoberta de temas disponíveis e resolução do tema ativo.
 *
 * Desacopla a exploração de diretórios do sistema de arquivos e arquivos de configuração
 * dos controladores HTTP (respeito estrito ao SRP - Single Responsibility Principle).
 */
class ThemeDiscoveryService implements ThemeDiscoveryServiceInterface
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = rtrim($basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4)), '/\\');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getAvailableThemes(): array
    {
        $themes = [];
        $seen = [];

        // 1. Diretório raiz /themes
        $themesDir = $this->basePath . '/themes';
        if (is_dir($themesDir)) {
            foreach (glob($themesDir . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $folder = basename($dir);
                if (in_array($folder, ['admin', 'manager'], true)) {
                    continue;
                }
                $seen[$folder] = true;
                $themeJson = $dir . '/theme.json';
                $json = file_exists($themeJson) ? json_decode((string)file_get_contents($themeJson), true) : [];
                $themes[] = [
                    'folder' => $folder,
                    'name'   => is_array($json) && isset($json['name']) && is_string($json['name']) ? $json['name'] : ucfirst($folder),
                ];
            }
        }

        // 2. Bundled themes dentro de plugins
        $pluginsDir = $this->basePath . '/src/Plugins';
        if (is_dir($pluginsDir)) {
            foreach (glob($pluginsDir . '/*/themes/*', GLOB_ONLYDIR) ?: [] as $dir) {
                $folder = basename($dir);
                if (isset($seen[$folder])) {
                    continue;
                }
                $seen[$folder] = true;
                $themeJson = $dir . '/theme.json';
                $json = file_exists($themeJson) ? json_decode((string)file_get_contents($themeJson), true) : [];
                $themes[] = [
                    'folder' => $folder,
                    'name'   => is_array($json) && isset($json['name']) && is_string($json['name']) ? $json['name'] : ucfirst($folder),
                ];
            }
        }

        return $themes;
    }

    public function getActiveThemeFolder(): string
    {
        $activeFile = $this->basePath . '/config/active_theme.json';
        if (file_exists($activeFile)) {
            $data = json_decode((string)file_get_contents($activeFile), true);
            $theme = $data['active_theme'] ?? ($data['active'] ?? null);
            if (is_string($theme) && trim($theme) !== '') {
                return basename(trim($theme));
            }
        }

        return 'default';
    }
}
