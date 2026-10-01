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
    /** @var HealthInsuranceInterface[] */
    private array $providers;

    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    public function processConsultation(string $providerName, string $patientId)
    {
        // Padrão Strategy / Chain of Responsibility
        $selectedProvider = null;

        // Varrer a lista de plugues (Injetados via Construtor)
        foreach ($this->providers as $provider) {
            // Em uma arquitetura real, a interface teria um getId() ou supports()
            // Vamos usar o nome para simular a correspondência
            if (stripos($provider->getProviderName(), $providerName) !== false) {
                $selectedProvider = $provider;
                break;
            }
        }

        if (!$selectedProvider) {
            throw new \Exception("Nenhum plugue de convênio conectado para: " . $providerName);
        }

        // 2. Chama os métodos usando a Interface genérica (Polimorfismo!)
        $isAuthorized = $selectedProvider->authorize($patientId);
        $price = $selectedProvider->getConsultationPrice();
        $name = $selectedProvider->getProviderName();

        return [
            'provider' => $name,
            'authorized' => $isAuthorized,
            'price' => $price
        ];
    }
}
