<?php

namespace DomainSystem\Core\Contracts;

interface PluginMetadataInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function getDependencies(): array;
    public function getSubPluginsPath(): ?string;
    public function isCore(): bool;
    public function getDescription(): string;
}
