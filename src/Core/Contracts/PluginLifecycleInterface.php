<?php

namespace DomainSystem\Core\Contracts;

interface PluginLifecycleInterface
{
    public function register(): void;
    public function boot(): void;
    public function activate(): void;
    public function deactivate(): void;
    public function uninstall(): void;
}
