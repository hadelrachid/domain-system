<?php

namespace DomainSystem\Plugins\clinic_pack\Providers;

use DomainSystem\Core\Contracts\CockpitProviderInterface;

class ReceptionCockpitProvider implements CockpitProviderInterface
{
    public function getRoleName(): string
    {
        return 'receptionist';
    }

    public function getDashboardRoute(): string
    {
        return '/cockpit/reception';
    }

    public function getThemeName(): string
    {
        return __DIR__ . '/../../themes/cockpit_reception';
    }
}
