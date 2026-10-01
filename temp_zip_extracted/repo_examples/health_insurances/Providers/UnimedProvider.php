<?php

namespace DomainSystem\Plugins\health_insurances\Providers;

use DomainSystem\Plugins\health_insurances\Contracts\HealthInsuranceInterface;

/**
 * O Plugue da Unimed.
 */
class UnimedProvider implements HealthInsuranceInterface
{
    public function authorize(string $patientCardId): bool
    {
        // Em um sistema real, aqui faríamos um cURL (API) para os servidores da Unimed
        // Simulando que a Unimed sempre autoriza:
        return true;
    }

    public function getConsultationPrice(): float
    {
        // A Unimed tem uma tabela fixa de R$ 90,00 por consulta
        return 90.00;
    }

    public function getProviderName(): string
    {
        return "Unimed Seguros";
    }
}
