<?php

namespace DomainSystem\Plugins\appointments\Services;

use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;

/**
 * O "Escriturário" (Service Object) responsável por verificar a agenda.
 */
class AvailabilityService
{
    private DoctorReaderInterface $doctorReader;
    private AppointmentRepositoryInterface $appointmentRepo;

    public function __construct(DoctorReaderInterface $doctorReader, AppointmentRepositoryInterface $appointmentRepo)
    {
        $this->doctorReader = $doctorReader;
        $this->appointmentRepo = $appointmentRepo;
    }

    /**
     * Pega a função e diz: "Espere! Vou verificar para o Doutor, estou verificando..."
     * E retorna os horários disponíveis e uma mensagem de status.
     * Retorno: ['slots' => array, 'message' => string]
     */
    public function getAvailableSlots(int $doctorId, string $date): array
    {
        $dateObj = $this->parseDate($date);
        if (!$dateObj) {
            return ['slots' => [], 'message' => 'Data inválida.'];
        }
        
        $today = new \DateTime('today');
        if ($dateObj < $today) {
            return ['slots' => [], 'message' => 'Não é possível agendar em datas passadas.'];
        }

        $dayOfWeek = (int) $dateObj->format('w');
        
        // 1. Verifica se o médico tem ALGUMA agenda configurada
        $allSchedules = $this->doctorReader->getDoctorSchedules($doctorId);
        if (empty($allSchedules)) {
            return ['slots' => [], 'message' => '⚠️ O médico não possui horários cadastrados.'];
        }

        // Verifica a grade para o dia específico
        $schedulesForDay = array_filter($allSchedules, function($s) use ($dayOfWeek) {
            if ((int)$s['day_of_week'] !== $dayOfWeek) return false;
            if (!isset($s['is_active'])) return true;
            return (int)$s['is_active'] === 1;
        });

        if (empty($schedulesForDay)) {
            return ['slots' => [], 'message' => 'Horários indisponíveis no momento!'];
        }

        // 2. O Escriturário gera todos os horários que o médico estaria na clínica
        $allSlots = [];
        foreach ($schedulesForDay as $sched) {
            $slotDuration = (int) ($sched['slot_duration'] ?: 30);
            
            $start = $this->parseTime($sched['start_time']);
            $end = $this->parseTime($sched['end_time']);

            if (!$start || !$end) continue;

            while ($start < $end) {
                $allSlots[] = $start->format('H:i');
                $start->modify("+{$slotDuration} minutes");
            }
        }

        if (empty($allSlots)) {
            return ['slots' => [], 'message' => 'Horários indisponíveis no momento!'];
        }

        // 3. O Escriturário cruza a agenda com os pacientes que já marcaram (via Repository)
        $normalizedDateStr = $dateObj->format('Y-m-d');
        $booked = $this->appointmentRepo->getBookedSlots($doctorId, $normalizedDateStr, $allSlots);

        $now = new \DateTime();
        $isToday = ($dateObj->format('Y-m-d') === $now->format('Y-m-d'));

        $available = [];
        foreach ($allSlots as $slot) {
            if (in_array($slot, $booked)) continue; // Já ocupado

            if ($isToday) {
                $slotTime = $this->parseTime($slot);
                // Bloqueia qualquer horário que já passou, ou que esteja a menos de 5 minutos de começar
                $slotTimeCopy = clone $slotTime;
                $slotTimeCopy->modify('-5 minutes');
                if ($now > $slotTimeCopy) continue; // Já passou
            }

            $available[] = $slot;
        }

        sort($available);

        if (empty($available)) {
            return ['slots' => [], 'message' => 'Agenda lotada!'];
        }

        return ['slots' => $available, 'message' => 'Horários disponíveis encontrados com sucesso!'];
    }

    /**
     * O Escriturário entende datas como "2026-09-09", "09/09/2026", "09-09-2026"
     */
    private function parseDate(string $date): ?\DateTime
    {
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'];
        foreach ($formats as $f) {
            $d = \DateTime::createFromFormat($f, $date);
            if ($d !== false && $d->format($f) === $date) {
                $d->setTime(0, 0, 0);
                return $d;
            }
        }
        try {
            $d = new \DateTime($date);
            $d->setTime(0, 0, 0);
            return $d;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * O Escriturário entende tempos como "08:00" ou "08:00:00"
     */
    private function parseTime(string $time): ?\DateTime
    {
        try {
            return new \DateTime($time);
        } catch (\Exception $e) {
            return null;
        }
    }
}
