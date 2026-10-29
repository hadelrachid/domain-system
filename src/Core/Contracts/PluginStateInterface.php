<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface PluginStateInterface
{
    public function isActive(): bool;
    public function setActive(bool $active): void;
}
