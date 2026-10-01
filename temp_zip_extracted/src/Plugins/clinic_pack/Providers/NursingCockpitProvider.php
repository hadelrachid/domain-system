<?php

namespace DomainSystem\Plugins\clinic_pack\Providers;

use DomainSystem\Core\Contracts\CockpitProviderInterface;

class NursingCockpitProvider implements CockpitProviderInterface
{
    public function getRoleName(): string
    {
        return 'nurse';
    }

    public function getDashboardRoute(): string
    {
        return '/cockpit/nursing';
    }

    public function getThemeName(): string
    {
        return __DIR__ . '/../themes/cockpit_nursing';
    }
}
