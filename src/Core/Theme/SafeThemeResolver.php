<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme;

/**
 * Resolvedor seguro de diretórios de temas.
 * Suporta múltiplos diretórios de busca (raiz /themes e /src/Plugins/flextheme/themes)
 * e previne falhas 404 caso o tema configurado não exista fisicamente no disco.
 */
class SafeThemeResolver
{
    /** @var array<string> */
    private array $searchPaths = [];

    /**
     * @param string|array<string> $themesBasePath Um ou mais caminhos base de temas
     */
    public function __construct(string|array $themesBasePath)
    {
        $paths = is_array($themesBasePath) ? $themesBasePath : [$themesBasePath];
        foreach ($paths as $path) {
            $this->searchPaths[] = rtrim($path, '/\\');
        }
    }

    /**
     * Resolve o caminho absoluto do tema solicitado, com fallback resiliente para 'default'.
     */
    public function resolveThemePath(string $requestedTheme): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $requestedTheme);

        if (!empty($sanitized)) {
            foreach ($this->searchPaths as $basePath) {
                $candidate = $basePath . DIRECTORY_SEPARATOR . $sanitized;
                if (is_dir($candidate)) {
                    return $candidate;
                }
            }
        }

        // Fallback 1: Tema 'default' em qualquer um dos caminhos de busca
        foreach ($this->searchPaths as $basePath) {
            $defaultPath = $basePath . DIRECTORY_SEPARATOR . 'default';
            if (is_dir($defaultPath)) {
                return $defaultPath;
            }
        }

        // Fallback 2: Primeiro tema válido encontrado nos diretórios
        foreach ($this->searchPaths as $basePath) {
            $dirs = glob($basePath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            if (!empty($dirs)) {
                return $dirs[0];
            }
        }

        return !empty($this->searchPaths) ? $this->searchPaths[0] : '';
    }

    /**
     * Verifica se um tema específico existe fisicamente nos diretórios configurados.
     */
    public function themeExists(string $themeName): bool
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $themeName);
        if (empty($sanitized)) {
            return false;
        }

        foreach ($this->searchPaths as $basePath) {
            $candidate = $basePath . DIRECTORY_SEPARATOR . $sanitized;
            if (is_dir($candidate)) {
                return true;
            }
        }

        return false;
    }
}
