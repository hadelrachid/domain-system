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
        $queryBuilder = $this->queryBuilder();
        
        $authenticator = new \DomainSystem\Plugins\auth\Services\GoogleAuthenticatorAdapter();
        $codeStore = new \DomainSystem\Plugins\auth\Repositories\SqliteTwoFactorCodeStore($queryBuilder);
        
        // Em vez de herdar o banco, o simulador usa a composição limpa e passa um Sender de E-mail Fake
        $fakeSender = new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedEmailSender();
        $twoFactorService->registerProvider('email', new \DomainSystem\Plugins\auth\Services\Providers\EmailProvider($codeStore, $fakeSender));
        
        // E o App passa o Authenticator para o próprio mock
        $twoFactorService->registerProvider('app', new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedAppProvider($authenticator));
    }
}
