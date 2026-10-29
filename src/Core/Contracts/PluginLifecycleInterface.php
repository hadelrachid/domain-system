<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface PluginLifecycleInterface
{
    public function register(): void;
    public function boot(): void;
    public function activate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void;
    public function deactivate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void;
    public function uninstall(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void;
}
