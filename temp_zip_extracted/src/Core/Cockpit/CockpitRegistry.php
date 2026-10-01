<?php

namespace DomainSystem\Core\Cockpit;

use DomainSystem\Core\Contracts\CockpitRegistryInterface;
use DomainSystem\Core\Contracts\CockpitProviderInterface;

class CockpitRegistry implements CockpitRegistryInterface
{
    private array $providers = [];

    public function registerProvider(CockpitProviderInterface $provider): void
    {
        $this->providers[$provider->getRoleName()] = $provider;
    }

    public function getProviderForRole(string $role): ?CockpitProviderInterface
    {
        return $this->providers[$role] ?? null;
    }
}
