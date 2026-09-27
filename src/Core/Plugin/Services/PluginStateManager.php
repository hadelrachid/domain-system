<?php

namespace DomainSystem\Core\Plugin\Services;

class PluginStateManager
{
    private string $configPath;

    public function __construct(string $basePath)
    {
        $this->configPath = $basePath . '/config/plugins.json';
    }

    /**
     * Retorna a lista de estados (ativo/inativo) de todos os plugins.
     */
    public function getActiveStates(): array
    {
        if (file_exists($this->configPath)) {
            return json_decode(file_get_contents($this->configPath), true) ?? [];
        }
        return [];
    }

    /**
     * Salva o array de estados no arquivo JSON.
     */
    public function saveStates(array $states): void
    {
        if (!is_dir(dirname($this->configPath))) {
            mkdir(dirname($this->configPath), 0777, true);
        }
        file_put_contents($this->configPath, json_encode($states, JSON_PRETTY_PRINT));
    }

    /**
     * Ativa um plugin.
     */
    public function enable(string $pluginName): void
    {
        $states = $this->getActiveStates();
        $states[$pluginName] = true;
        $this->saveStates($states);
    }

    /**
     * Desativa um plugin.
     */
    public function disable(string $pluginName): void
    {
        $states = $this->getActiveStates();
        $states[$pluginName] = false;
        $this->saveStates($states);
    }
}
