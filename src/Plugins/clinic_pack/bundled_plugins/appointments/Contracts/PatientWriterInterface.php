<?php

namespace DomainSystem\Plugins\appointments\Contracts;

interface PatientWriterInterface
{
    /**
     * Atualiza dados básicos de um paciente.
     */
    public function updatePatientData(int $id, array $data): void;
    
    /**
     * Cria um paciente rápido (nome e telefone)
     */
    public function createPatient(string $name, string $phone): int;

    /**
     * Cria um paciente com todos os dados.
     */
    public function createPatientFull(array $data): int;
}
