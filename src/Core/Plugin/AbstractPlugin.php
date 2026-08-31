<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Container\Container;
use Exception;

abstract class AbstractPlugin implements PluginInterface
{
    protected Container $container;
    protected string $path;
    protected array $metadata = [];
    protected bool $isActive = false;

    public function __construct(Container $container, string $path)
    {
        $this->container = $container;
        $this->path = rtrim($path, '/\\');
        $this->loadMetadata();
    }

    private function loadMetadata(): void
    {
        $jsonPath = $this->path . '/plugin.json';
        if (!file_exists($jsonPath)) {
            throw new Exception("Missing plugin.json at {$this->path}");
        }

        $content = file_get_contents($jsonPath);
        $this->metadata = json_decode($content, true) ?? [];
    }

    public function getName(): string
    {
        return $this->metadata['name'] ?? 'unknown';
    }

    public function getVersion(): string
    {
        return $this->metadata['version'] ?? '1.0.0';
    }
    
    public function getDescription(): string
    {
        return $this->metadata['description'] ?? '';
    }

    public function getDependencies(): array
    {
        return $this->metadata['dependencies'] ?? [];
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function isCore(): bool
    {
        return isset($this->metadata['core']) && $this->metadata['core'] === true;
    }
    
    public function setActive(bool $active): void
    {
        $this->isActive = $active;
    }

    protected function events(): \DomainSystem\Core\Events\EventDispatcher
    {
        return $this->container->make(\DomainSystem\Core\Events\EventDispatcher::class);
    }

    protected function db(): \DomainSystem\Plugins\Database\Connection
    {
        return $this->container->make(\DomainSystem\Plugins\Database\Connection::class);
    }

    protected function queryBuilder(): \DomainSystem\Plugins\Database\QueryBuilder
    {
        return $this->container->make(\DomainSystem\Plugins\Database\QueryBuilder::class);
    }

    protected function theme(): \DomainSystem\Core\Theme\ThemeManager
    {
        return $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
    }

    abstract public function register(): void;
    
    // Lifecycle hooks defaults
    public function boot(): void {}
    public function activate(): void {}
    public function deactivate(): void {}
    public function uninstall(): void {}

    public function getSubPluginsPath(): ?string
    {
        return null;
    }
}
