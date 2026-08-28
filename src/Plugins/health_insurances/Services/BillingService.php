<?php

namespace DomainSystem\Plugins\health_insurances\Services;

use DomainSystem\Plugins\health_insurances\Contracts\HealthInsuranceInterface;
use DomainSystem\Plugins\health_insurances\Providers\UnimedProvider;
use DomainSystem\Plugins\health_insurances\Providers\PrivateProvider;

/**
 * O Serviço Gerenciador.
 * Ele recebe qualquer classe que implemente HealthInsuranceInterface.
 * É aqui que a Injeção de Dependência e o Polimorfismo brilham!
 */
class BillingService
{
    /**
     * Esta é a verdadeira mágica (Factory Method simplificado).
     * Dado uma string, ele devolve a Tomada (Interface) já conectada com o Plugue certo.
     */
    private function resolveProvider(string $providerName): HealthInsuranceInterface
    {
        switch (strtolower($providerName)) {
            case 'unimed':
                return new UnimedProvider();
            case 'particular':
            default:
                return new PrivateProvider();
        }
    }

    /**
     * Processa a consulta sem saber qual convênio é!
     * 
     * Observe que `$provider` é tipado como `HealthInsuranceInterface`.
     * O PHP garante que qualquer objeto que chegue aqui terá os 3 métodos obrigatórios.
     */
    public function processConsultation(string $providerName, string $patientId)
    {
        // 1. Pluga o convênio correto na tomada
        $provider = $this->resolveProvider($providerName);

        // 2. Chama os métodos usando a Interface genérica (Polimorfismo!)
        $isAuthorized = $provider->authorize($patientId);
        $price = $provider->getConsultationPrice();
        $name = $provider->getProviderName();

        // (No futuro, aqui é onde o BillingService gritaria 'finance.generate_income')

        return [
            'provider' => $name,
            'authorized' => $isAuthorized,
            'price' => $price
        ];
    }
}
