<?php

namespace DomainSystem\Plugins\appointments\Contracts;

interface PatientReaderInterface
{
    /**
     * Retorna todos os pacientes no formato:
     * [
     *   ['id' => 1, 'name' => 'João', 'phone' => '...', 'cpf' => '...']
     * ]
     */
    public function getAllPatients(): array;

    /**
     * Retorna um mapa de pacientes indexado pelo ID.
     * [ 1 => ['name' => 'João', 'phone' => '...'] ]
     */
    public function getPatientsMap(): array;

    /**
     * Retorna os dados completos de um único paciente.
     */
    public function getPatientData(int $id): ?array;
    /**
     * Atualiza dados básicos de um paciente.
     */
    public function updatePatientData(int $id, array $data): void;
}
