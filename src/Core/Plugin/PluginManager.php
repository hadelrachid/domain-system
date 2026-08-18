<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Utils\Archive\ExtractorFactory;
use Exception;

class PluginManager
{
    private string $pluginsPath;
    private string $configPath;

    public function __construct(string $basePath)
    {
        $this->pluginsPath = $basePath . '/src/Plugins';
        $this->configPath = $basePath . '/config/plugins.json';
    }

    public function installFromZip(string $zipFilePath): string
    {
        $extractor = ExtractorFactory::create();
        return $extractor->extract($zipFilePath, $this->pluginsPath);
    }

    public function getActiveStates(): array
    {
        if (file_exists($this->configPath)) {
            return json_decode(file_get_contents($this->configPath), true) ?? [];
        }
        return [];
    }

    private function saveStates(array $states): void
    {
        if (!is_dir(dirname($this->configPath))) {
            mkdir(dirname($this->configPath), 0777, true);
        }
        file_put_contents($this->configPath, json_encode($states, JSON_PRETTY_PRINT));
    }

    public function enable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;

        $states = $this->getActiveStates();
        $states[$pluginName] = true;
        $this->saveStates($states);
    }

    public function disable(string $pluginName): void
    {
        if ($this->isCore($pluginName)) return;

        $states = $this->getActiveStates();
        unset($states[$pluginName]);
        $this->saveStates($states);
    }

    public function delete(string $pluginName, string $pluginFolder): void
    {
        if ($this->isCore($pluginName)) {
            throw new Exception("Não é possível excluir plugins core do sistema.");
        }

        $states = $this->getActiveStates();
        if (!empty($states[$pluginName])) {
            throw new Exception("O plugin precisa ser desativado antes de ser excluído.");
        }

        $pluginPath = $this->pluginsPath . '/' . $pluginFolder;
        if (file_exists($pluginPath)) {
            $this->deleteDirectory($pluginPath);
        }
    }

    public function isCore(string $pluginName): bool
    {
        return in_array($pluginName, ['database', 'system-admin']);
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
