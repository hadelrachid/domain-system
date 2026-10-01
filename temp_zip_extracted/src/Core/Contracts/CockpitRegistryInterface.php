<?php

namespace DomainSystem\Core\Contracts;

interface CockpitRegistryInterface
{
    public function registerProvider(CockpitProviderInterface $provider): void;
    public function getProviderForRole(string $role): ?CockpitProviderInterface;
}
