<?php

namespace DomainSystem\Plugins\clinic_pack\Providers;

use DomainSystem\Core\Contracts\CockpitProviderInterface;

class DoctorCockpitProvider implements CockpitProviderInterface
{
    public function getRoleName(): string
    {
        return 'doctor';
    }

    public function getDashboardRoute(): string
    {
        return '/cockpit/doctor';
    }

    public function getThemeName(): string
    {
        return __DIR__ . '/../../themes/cockpit_doctor';
    }
}
