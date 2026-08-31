<?php

namespace DomainSystem\Plugins\clinic_pack\Providers;

use DomainSystem\Core\Contracts\CockpitProviderInterface;

class SecretaryCockpitProvider implements CockpitProviderInterface
{
    public function getRoleName(): string
    {
        return 'receptionist';
    }

    public function getDashboardRoute(): string
    {
        return '/cockpit/secretary';
    }

    public function getThemeName(): string
    {
        return __DIR__ . '/../themes/cockpit_secretary';
    }
}
