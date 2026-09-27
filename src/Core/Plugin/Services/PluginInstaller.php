<?php

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Exception;

class PluginInstaller
{
    private string $basePath;
    private PluginStateManager $stateManager;

    public function __construct(string $basePath, PluginStateManager $stateManager)
    {
        $this->basePath = $basePath;
        $this->stateManager = $stateManager;
    }

    private function getPluginsPath(): string
    {
        return $this->basePath . '/src/Plugins';
    }

    public function installFromZip(string $zipFilePath): string
    {
        $extractor = ExtractorFactory::create();
        return $extractor->extract($zipFilePath, $this->getPluginsPath());
    }

    public function delete(string $pluginName, string $pluginFolder): void
    {
        if ($this->isCore($pluginName)) {
            throw new Exception("Não é possível excluir plugins core do sistema.");
        }

        $states = $this->stateManager->getActiveStates();
        if (!empty($states[$pluginName])) {
            throw new Exception("O plugin precisa ser desativado antes de ser excluído.");
        }

        $pluginPath = $this->getPluginsPath() . '/' . $pluginFolder;
        if (file_exists($pluginPath)) {
            $this->deleteDirectory($pluginPath);
        }
    }

    private function isCore(string $pluginName): bool
    {
        // Tenta ler do plugin.json diretamente
        $jsonPath = $this->getPluginsPath() . '/' . $pluginName . '/plugin.json';
        if (file_exists($jsonPath)) {
            $metadata = json_decode(file_get_contents($jsonPath), true);
            return isset($metadata['core']) && $metadata['core'] === true;
        }

        return false;
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
}
