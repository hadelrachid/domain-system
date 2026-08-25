<?php
namespace DomainSystem\Plugins\dev_simulator;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Pega a Tomada e o DB (que já foram iniciados pelo plugin 'auth')
        $twoFactorService = $this->container->make(\DomainSystem\Plugins\auth\Services\TwoFactorService::class);
        $queryBuilder = $this->container->make(\DomainSystem\Plugins\Database\QueryBuilder::class);
        
        // Substitui silenciosamente (sequestra) a fiação original pelo nosso mock Hacker
        $twoFactorService->registerProvider('email', new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedEmailProvider($queryBuilder));
        $twoFactorService->registerProvider('app', new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedAppProvider());
    }
}
