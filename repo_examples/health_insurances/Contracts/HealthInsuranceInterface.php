<?php

namespace DomainSystem\Plugins\health_insurances\Contracts;

/**
 * A Tomada Fêmea (O Contrato).
 * 
 * Qualquer classe de convênio que queira se conectar ao nosso sistema 
 * OBRIGATORIAMENTE precisa implementar estes três pinos (métodos).
 * O sistema principal nunca precisa saber se está falando com a Unimed ou Amil,
 * ele só precisa saber que está falando com um "HealthInsuranceInterface".
 */
interface HealthInsuranceInterface
{
    /**
     * Verifica se o convênio autoriza a consulta para este paciente.
     */
    public function authorize(string $patientCardId): bool;

    /**
     * Retorna o valor exato a ser cobrado/faturado por esta consulta.
     */
    public function getConsultationPrice(): float;

    /**
     * Retorna o nome oficial do convênio para exibição no painel.
     */
    public function getProviderName(): string;
}
