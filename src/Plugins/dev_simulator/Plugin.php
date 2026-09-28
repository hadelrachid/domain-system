<?php
namespace DomainSystem\Plugins\dev_simulator;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO (OS 2.0)
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        // Trava de Segurança Crítica: Aborta o sistema inteiro se estiver em Produção!
        $env = getenv('APP_ENV') ?: (defined('APP_ENV') ? APP_ENV : 'production');
        if ($env === 'production') {
            throw new \Exception("SECURITY ALERT: O plugin 'dev_simulator' JAMAIS deve rodar em produção. Desabilite-o no plugin.json.");
        }
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO (OS 2.0)
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        // Pega a Tomada e a Memória (já resolvidos pelo container no plugin auth)
        $twoFactorService = $this->container->make(\DomainSystem\Plugins\auth\Services\TwoFactorService::class);
        $authenticator = $this->container->make(\DomainSystem\Plugins\auth\Contracts\AuthenticatorInterface::class);
        $codeStore = $this->container->make(\DomainSystem\Plugins\auth\Contracts\TwoFactorCodeStoreInterface::class);
        $fakeSender = new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedEmailSender();
        $twoFactorService->registerProvider('email', new \DomainSystem\Plugins\auth\Services\Providers\EmailProvider($codeStore, $fakeSender));
        
        // E o App passa o Authenticator para o próprio mock
        $twoFactorService->registerProvider('app', new \DomainSystem\Plugins\dev_simulator\Providers\SimulatedAppProvider($authenticator));
    }
}
