<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface CapabilityRegistrarInterface
{
    public function registerCapability(string $slug, string $context = ''): int;
}
