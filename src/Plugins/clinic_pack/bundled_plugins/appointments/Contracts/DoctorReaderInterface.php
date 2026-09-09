<?php

namespace DomainSystem\Plugins\appointments\Contracts;

interface DoctorReaderInterface
{
    /**
     * Retorna todos os médicos no formato:
     * [
     *   ['id' => 1, 'name' => 'Dr. House', 'specialty' => 'Diagnóstico']
     * ]
     */
    public function getAllDoctors(): array;

    /**
     * Retorna um mapa de médicos indexado pelo ID.
     * [ 1 => ['name' => 'Dr. House', 'specialty' => 'Diagnóstico'] ]
     */
    public function getDoctorsMap(): array;

    /**
     * Retorna o nome de um médico específico.
     */
    public function getDoctorName(int $id): string;

    /**
     * Retorna a grade de horários de um médico.
     */
    public function getDoctorSchedules(int $doctorId): array;

    /**
     * Salva a grade de horários de um médico (substitui a anterior).
     */
    public function saveDoctorSchedules(int $doctorId, array $schedules): void;
}
