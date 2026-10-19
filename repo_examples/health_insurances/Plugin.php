<?php

namespace DomainSystem\Plugins\health_insurances;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Theme\ShortcodeManager;
use DomainSystem\Plugins\health_insurances\Services\BillingService;
use DomainSystem\Plugins\health_insurances\Providers\UnimedProvider;
use DomainSystem\Plugins\health_insurances\Providers\PrivateProvider;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.theme');
        $os->listenHook('shortcodes.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        $runtime->singleton(BillingService::class, function () {
            return new BillingService([
                new UnimedProvider(),
                new PrivateProvider(),
            ]);
        });

        $runtime->onHook('shortcodes.register', function (ShortcodeManager $shortcodes) use ($runtime) {
            $shortcodes->add('teste_faturamento', function ($atts) use ($runtime) {
                $convenio = $atts['convenio'] ?? 'particular';
                $paciente = $atts['paciente'] ?? 'João da Silva';

                /** @var BillingService $billingService */
                $billingService = $runtime->make(BillingService::class);
                $result = $billingService->processConsultation($convenio, $paciente);

                $theme = $runtime->getLink('core.theme');
                return $theme->render('shortcode_test', ['result' => $result], __DIR__ . '/views');
            }, 'Gera uma caixa de simulação de faturamento com polimorfismo.');
        });
    }
}
