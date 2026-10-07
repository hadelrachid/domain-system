<?php
namespace DomainSystem\Plugins\clock_widget;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // Fase de Negociação
    public function osRegister(OsConnectorInterface $os): void
    {
        // O Plugin pede permissão para injetar Widgets no Dashboard Admin usando o evento correto
        $os->listenHook('dashboard.register_widgets');
        
        // Exige o IdentityManager para testar se o usuário tem a permissão
        $os->requireLink('core.identity');
    }

    // Fase de Execução
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $identity = $runtime->getLink('core.identity');
        $provider = new ClockWidgetProvider($identity);

        $runtime->onHook('dashboard.register_widgets', function($registry) use ($provider) {
            $registry->registerProvider($provider);
        });
    }

    // Ocorre APENAS quando o SysAdmin clica em "Ativar/Instalar"
    public function activate(OsRuntimeInterface $runtime): void
    {
        // Aqui o Plugin injeta silenciosamente as Capabilities no SO!
        $runtime->registerCapability('clock.view', 'App Relógio Mundial');
        $runtime->registerCapability('clock.configure', 'App Relógio Mundial');
    }
}
