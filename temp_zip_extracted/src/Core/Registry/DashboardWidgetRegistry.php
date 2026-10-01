<?php

namespace DomainSystem\Core\Registry;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;

class DashboardWidgetRegistry
{
    private array $providers = [];

    /**
     * Registra um novo provedor de Widgets
     */
    public function registerProvider(DashboardWidgetProviderInterface $provider): void
    {
        $this->providers[get_class($provider)] = $provider;
    }

    /**
     * Retorna todos os provedores registrados
     * @return DashboardWidgetProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Retorna um provedor específico pela sua classe
     */
    public function getProvider(string $class): ?DashboardWidgetProviderInterface
    {
        return $this->providers[$class] ?? null;
    }
}
