<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\seo\Services;

use DomainSystem\Plugins\seo\Contracts\AssetMinifierInterface;

class AssetPipeline
{
    private AssetMinifierInterface $minifier;
    private string $cacheDir;
    private string $publicUrlPath;

    private array $cssFiles = [];
    private array $jsFiles = [];

    public function __construct(AssetMinifierInterface $minifier, string $cacheDir, string $publicUrlPath)
    {
        $this->minifier = $minifier;
        $this->cacheDir = rtrim(str_replace('\\', '/', $cacheDir), '/');
        $this->publicUrlPath = rtrim(str_replace('\\', '/', $publicUrlPath), '/');
        
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    public function enqueueCss(string $absolutePath): void
    {
        $normalized = str_replace('\\', '/', $absolutePath);
        if (file_exists($normalized) && !in_array($normalized, $this->cssFiles, true)) {
            $this->cssFiles[] = $normalized;
        }
    }

    public function enqueueJs(string $absolutePath): void
    {
        $normalized = str_replace('\\', '/', $absolutePath);
        if (file_exists($normalized) && !in_array($normalized, $this->jsFiles, true)) {
            $this->jsFiles[] = $normalized;
        }
    }

    public function renderCssTags(): string
    {
        if (empty($this->cssFiles)) {
            return '';
        }
        
        // Gera um hash determinístico baseado no caminho e no timestamp de modificação
        $hash = $this->generateHash($this->cssFiles);
        $filename = "style_min_{$hash}.css";
        $cacheFile = $this->cacheDir . '/' . $filename;
        
        if (!file_exists($cacheFile)) {
            $combined = '';
            foreach ($this->cssFiles as $file) {
                $combined .= file_get_contents($file) . "\n";
            }
            $minified = $this->minifier->minifyCss($combined);
            file_put_contents($cacheFile, $minified);
        }
        
        return "<link rel=\"stylesheet\" href=\"{$this->publicUrlPath}/{$filename}\">\n";
    }

    public function renderJsTags(): string
    {
        if (empty($this->jsFiles)) {
            return '';
        }
        
        $hash = $this->generateHash($this->jsFiles);
        $filename = "script_min_{$hash}.js";
        $cacheFile = $this->cacheDir . '/' . $filename;
        
        if (!file_exists($cacheFile)) {
            $combined = '';
            foreach ($this->jsFiles as $file) {
                $combined .= file_get_contents($file) . "\n";
            }
            $minified = $this->minifier->minifyJs($combined);
            file_put_contents($cacheFile, $minified);
        }
        
        return "<script src=\"{$this->publicUrlPath}/{$filename}\" defer></script>\n";
    }

    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    public function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    private function generateHash(array $files): string
    {
        $data = '';
        foreach ($files as $file) {
            $data .= $file . (file_exists($file) ? filemtime($file) : '0');
        }
        return substr(md5($data), 0, 10);
    }
}
