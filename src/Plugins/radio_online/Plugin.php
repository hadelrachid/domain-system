<?php
namespace DomainSystem\Plugins\radio_online;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.identity');
        $os->listenHook('dashboard.register_widgets');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $identity = $runtime->getLink('core.identity');
        
        $runtime->onHook('dashboard.register_widgets', function($registry) use ($identity) {
            $registry->registerProvider(new RadioWidgetProvider($identity));
        });
    }

    public function activate(OsRuntimeInterface $runtime): void
    {
        // Cria a permissão no Banco de Dados para ouvir rádio
        $runtime->registerCapability('radio.listen', 'Ouvir Rádio Online');
    }
}
