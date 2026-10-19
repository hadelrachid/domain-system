<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Contracts\SiteStateInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Theme\EnvSiteState;
use DomainSystem\Core\Theme\SafeThemeResolver;
use DomainSystem\Plugins\academy\Contracts\ThemePageResolverInterface;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ThemePageResolver implements ThemePageResolverInterface
{
    private ?ThemeManagerInterface $themeManager;
    private ?SafeThemeResolver $themeResolver;
    private ?SiteStateInterface $siteState;
    private string $baseRoot;

    public function __construct(
        ?ThemeManagerInterface $themeManager = null,
        ?SafeThemeResolver $themeResolver = null,
        ?SiteStateInterface $siteState = null,
        ?string $baseRoot = null
    ) {
        $this->themeManager = $themeManager;
        $this->baseRoot = $baseRoot ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4));
        $this->themeResolver = $themeResolver ?? new SafeThemeResolver([
            $this->baseRoot . '/themes',
            $this->baseRoot . '/src/Plugins/flextheme/themes',
        ]);
        $this->siteState = $siteState ?? new EnvSiteState();
    }

    public function resolveActiveThemePath(): string
    {
        if ($this->themeManager !== null) {
            $activePath = $this->themeManager->getActiveThemePath();
            if (!empty($activePath) && is_dir($activePath)) {
                return $activePath;
            }
        }

        $activeTheme = $this->siteState !== null ? $this->siteState->getActiveTheme() : '';
        if ($activeTheme !== '' && $this->themeResolver !== null && $this->themeResolver->themeExists($activeTheme)) {
            return $this->themeResolver->resolveThemePath($activeTheme);
        }

        $candidates = ['rachidd', 'default'];
        if ($this->themeResolver !== null) {
            foreach ($candidates as $candidate) {
                if ($this->themeResolver->themeExists($candidate)) {
                    return $this->themeResolver->resolveThemePath($candidate);
                }
            }
        }

        return $this->themeResolver !== null
            ? $this->themeResolver->resolveThemePath($activeTheme)
            : $this->baseRoot . '/themes/default';
    }

    public function resolvePublicThemePages(): array
    {
        $pages = [];

        if ($this->themeManager !== null) {
            $pages = $this->themeManager->getAvailablePages();
        }

        if (empty($pages)) {
            $activePublicTheme = $this->siteState !== null ? $this->siteState->getActiveTheme() : '';

            if ($this->themeManager !== null && $activePublicTheme !== '') {
                $pages = $this->themeManager->getAvailablePages($activePublicTheme);
            }

            if (empty($pages) && $this->themeResolver !== null) {
                $candidateThemes = array_unique(array_filter([$activePublicTheme, 'rachidd', 'default']));
                foreach ($candidateThemes as $themeName) {
                    if ($this->themeResolver->themeExists($themeName)) {
                        $pagesDir = $this->themeResolver->resolveThemePath($themeName) . '/pages';
                        if (is_dir($pagesDir)) {
                            $iterator = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($pagesDir, FilesystemIterator::SKIP_DOTS)
                            );
                            foreach ($iterator as $file) {
                                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['php', 'html'], true)) {
                                    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($pagesDir) + 1));
                                    $pages[] = 'pages/' . ltrim($rel, '/');
                                }
                            }
                            if (!empty($pages)) {
                                break;
                            }
                        }
                    }
                }
            }
        }

        $pages = array_values(array_unique($pages));
        natsort($pages);
        return array_values($pages);
    }

    /**
     * Alias retrocompatível para resolvePublicThemePages().
     *
     * @return array<int, string>
     */
    public function getAvailablePages(): array
    {
        return $this->resolvePublicThemePages();
    }
}

