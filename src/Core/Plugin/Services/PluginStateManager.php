<?php

declare(strict_types=1);

namespace DomainSystem\Core\Plugin\Services;

use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;

/**
 * ────────────────────────────────────────────────────────────────────────────
 * CLASSE: PluginStateManager
 * ────────────────────────────────────────────────────────────────────────────
 * Responsabilidade Única (SRP): Gerenciar o estado (ligado/desligado) dos plugins
 * utilizando persistência atômica (AtomicJsonStore) contra race conditions.
 */
class PluginStateManager
{
    private string $configPath;
    private JsonStoreInterface $jsonStore;

    public function __construct(string $basePath, JsonStoreInterface $jsonStore = new AtomicJsonStore())
    {
        $this->configPath = rtrim($basePath, '/\\') . '/config/plugins.json';
        $this->jsonStore = $jsonStore;
    }

    /**
     * Retorna a lista de estados (ativo/inativo) de todos os plugins.
     */
    public function getActiveStates(): array
    {
        return $this->jsonStore->read($this->configPath, []);
    }

    /**
     * Salva o array de estados no arquivo JSON de forma atômica.
     */
    public function saveStates(array $states): void
    {
        $this->jsonStore->write($this->configPath, $states);
    }

    /**
     * Ativa um plugin de forma atômica.
     */
    public function enable(string $pluginName): void
    {
        $this->jsonStore->update($this->configPath, static function (array $states) use ($pluginName): array {
            $states[$pluginName] = true;
            return $states;
        });
    }

    /**
     * Desativa um plugin de forma atômica.
     */
    public function disable(string $pluginName): void
    {
        $this->jsonStore->update($this->configPath, static function (array $states) use ($pluginName): array {
            $states[$pluginName] = false;
            return $states;
        });
    }
}
