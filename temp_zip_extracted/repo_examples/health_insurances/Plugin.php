<?php

namespace DomainSystem\Plugins\health_insurances;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Plugins\health_insurances\Services\BillingService;
use DomainSystem\Plugins\health_insurances\Providers\UnimedProvider;
use DomainSystem\Plugins\health_insurances\Providers\PrivateProvider;
use DomainSystem\Core\Theme\ThemeManager;

class Plugin extends AbstractPlugin
{
    public function register(): void
    {
        /** @var EventDispatcher $events */
        $events = $this->events();

        // Bind do Serviço de Faturamento (O Filtro de Linha)
        $this->container->bind(BillingService::class, function() {
            // Instanciamos os plugues. Isso sim é Injeção de Dependência!
            $providers = [
                new UnimedProvider(),
                new PrivateProvider()
            ];
            return new BillingService($providers);
        });

        // Registrar o shortcode de prova de conceito
        $events->addListener('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $shortcodes) {
            // 3. Registrar o Shortcode
            $shortcodes->add('teste_faturamento', function($atts) {
                $convenio = $atts['convenio'] ?? 'particular';
                $paciente = $atts['paciente'] ?? 'João da Silva';
                
                /** @var BillingService $billingService */
                $billingService = $this->container->make(BillingService::class);
                $result = $billingService->processConsultation($convenio, $paciente);

                /** @var ThemeManager $theme */
                $theme = $this->theme();
                return $theme->render('shortcode_test', ['result' => $result], __DIR__ . '/views');
            }, 'Gera uma caixa de simulação de faturamento com polimorfismo.');
        });
    }
}
