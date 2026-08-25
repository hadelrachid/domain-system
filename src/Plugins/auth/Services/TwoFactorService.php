<?php

namespace DomainSystem\Plugins\auth\Services;

use DomainSystem\Plugins\auth\Services\Providers\TwoFactorProviderInterface;

class TwoFactorService
{
    private array $providers = [];

    /**
     * Conecta um plugue na tomada
     */
    public function registerProvider(string $type, TwoFactorProviderInterface $provider): void
    {
        $this->providers[$type] = $provider;
    }

    /**
     * Retorna o plugue conectado, ou nulo se não existir
     */
    public function getProvider(string $type): ?TwoFactorProviderInterface
    {
        return $this->providers[$type] ?? null;
    }
}
