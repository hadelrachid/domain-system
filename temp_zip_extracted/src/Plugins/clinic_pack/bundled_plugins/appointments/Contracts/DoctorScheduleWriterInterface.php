<?php

namespace DomainSystem\Plugins\appointments\Contracts;

/**
 * Interface para escrita de horários de médicos.
 * Segregada do DoctorReaderInterface (ISP).
 * Consumida apenas por quem precisa SALVAR schedules (ex: ScheduleController).
 */
interface DoctorScheduleWriterInterface
{
    /**
     * Salva a grade de horários de um médico (substitui a anterior).
     */
    public function saveDoctorSchedules(int $doctorId, array $schedules): void;
}
