<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface CockpitProviderInterface
{
    public function getRoleName(): string;
    public function getDashboardRoute(): string;
    public function getThemeName(): string;
}
