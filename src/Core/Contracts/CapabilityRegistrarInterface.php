<?php

namespace DomainSystem\Core\Contracts;

interface CapabilityRegistrarInterface
{
    public function registerCapability(string $slug, string $context = ''): int;
}
