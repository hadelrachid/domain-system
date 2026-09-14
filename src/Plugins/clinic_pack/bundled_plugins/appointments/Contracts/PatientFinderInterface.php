<?php

namespace DomainSystem\Plugins\appointments\Contracts;

interface PatientFinderInterface
{
    /**
     * Busca um paciente pelo telefone.
     */
    public function findPatientByPhone(string $phone): ?array;

    /**
     * Busca um paciente pelo email ou telefone (útil no agendamento online).
     */
    public function findPatientByEmailOrPhone(string $email, string $phone): ?array;
}
