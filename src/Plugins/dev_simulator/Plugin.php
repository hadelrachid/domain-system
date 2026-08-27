<?php
namespace DomainSystem\Plugins\dev_simulator;

use DomainSystem\Core\Plugin\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        // Trava de Segurança Crítica: Aborta o sistema inteiro se estiver em Produção!
        $env = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production');
        if ($env === 'production') {
            throw new \Exception("SECURITY ALERT: O plugin 'dev_simulator' JAMAIS deve rodar em produção. Desabilite-o no plugins.json.");
        }

        // Pega a Tomada e o DB (que já foram iniciados pelo plugin 'auth')
        $twoFactorService = $this->container->make(\DomainSystem\Plugins\auth\Services\TwoFactorService::class);
        $queryBuilder = $this->container->make(\DomainSystem\Plugins\Database\QueryBuilder::class);
        
        // Substitui silenciosamente (sequestra) a fiação original pelo nosso mock Hacker
        $twoFactorService->registerProvider('email', new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedEmailProvider($queryBuilder));
        $twoFactorService->registerProvider('app', new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedAppProvider());
    }
}
