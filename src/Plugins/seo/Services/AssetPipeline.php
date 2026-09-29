<?php

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
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->publicUrlPath = rtrim($publicUrlPath, '/');
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function enqueueCss(string $absolutePath): void
    {
        if (file_exists($absolutePath)) {
            $this->cssFiles[] = $absolutePath;
        }
    }

    public function enqueueJs(string $absolutePath): void
    {
        if (file_exists($absolutePath)) {
            $this->jsFiles[] = $absolutePath;
        }
    }

    public function renderCssTags(): string
    {
        if (empty($this->cssFiles)) return '';
        
        // Generate a hash based on file contents and modified times
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
        if (empty($this->jsFiles)) return '';
        
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

    private function generateHash(array $files): string
    {
        $data = '';
        foreach ($files as $file) {
            $data .= $file . filemtime($file);
        }
        return substr(md5($data), 0, 10);
    }
}
