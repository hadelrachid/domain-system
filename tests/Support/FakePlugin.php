<?php

namespace DomainSystem\Tests\Support;

use DomainSystem\Core\Plugin\PluginInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class FakePlugin implements PluginInterface, OsExtensionInterface
{
    private string $name;
    private array $dependencies;
    private bool $active;
    private bool $core;
    
    public bool $registered = false;
    public bool $booted = false;
    public bool $osRegistered = false;
    public bool $osBooted = false;
    
    public function __construct(string $name = 'fake', array $dependencies = [], bool $active = true, bool $core = false)
    {
        $this->name = $name;
        $this->dependencies = $dependencies;
        $this->active = $active;
        $this->core = $core;
    }

    public function register(): void { $this->registered = true; }
    public function boot(): void { $this->booted = true; }
    public function activate(): void {}
    public function deactivate(): void {}
    public function uninstall(): void {}

    public function getName(): string { return $this->name; }
    public function getVersion(): string { return '1.0.0'; }
    public function getDependencies(): array { return $this->dependencies; }
    public function getSubPluginsPath(): ?string { return null; }
    public function isCore(): bool { return $this->core; }
    public function getDescription(): string { return 'Fake Plugin for tests'; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): void { $this->active = $active; }

    public function osRegister(OsConnectorInterface $connector): void { $this->osRegistered = true; }
    public function osBoot(OsRuntimeInterface $runtime): void { $this->osBooted = true; }
}
