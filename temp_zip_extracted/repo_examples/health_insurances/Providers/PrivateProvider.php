<?php

namespace DomainSystem\Plugins\health_insurances\Providers;

use DomainSystem\Plugins\health_insurances\Contracts\HealthInsuranceInterface;

/**
 * O Plugue Particular (Sem Convênio).
 */
class PrivateProvider implements HealthInsuranceInterface
{
    public function authorize(string $patientCardId): bool
    {
        // Se é particular (no dinheiro/pix), a autorização é sempre imediata
        return true;
    }

    public function getConsultationPrice(): float
    {
        // A consulta particular na clínica custa R$ 250,00
        return 250.00;
    }

    public function getProviderName(): string
    {
        return "Consulta Particular";
    }
}
