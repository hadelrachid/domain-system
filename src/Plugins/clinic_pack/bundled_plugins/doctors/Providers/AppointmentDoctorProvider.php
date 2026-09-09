<?php

namespace DomainSystem\Plugins\doctors\Providers;

use DomainSystem\Plugins\appointments\Contracts\DoctorReaderInterface;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class AppointmentDoctorProvider implements DoctorReaderInterface
{
    private DoctorRepositoryInterface $repository;

    public function __construct(DoctorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getAllDoctors(): array
    {
        return $this->repository->findAll();
    }

    public function getDoctorsMap(): array
    {
        $doctors = $this->getAllDoctors();
        $map = [];
        foreach ($doctors as $d) {
            $map[$d['id']] = $d;
        }
        return $map;
    }

    public function getDoctorName(int $id): string
    {
        $doctor = $this->repository->findById($id);
        return $doctor ? $doctor['name'] : 'Desconhecido';
    }

    public function getDoctorSchedules(int $doctorId): array
    {
        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        $stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function saveDoctorSchedules(int $doctorId, array $schedules): void
    {
        $db = \DomainSystem\Core\Application::getInstance()
            ->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
        
        $db->prepare("DELETE FROM doctor_schedules WHERE doctor_id = ?")->execute([$doctorId]);
        
        $insert = $db->prepare("
            INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        foreach ($schedules as $s) {
            $insert->execute([
                $doctorId, 
                $s['day_of_week'], 
                $s['start_time'], 
                $s['end_time'], 
                $s['slot_duration']
            ]);
        }
    }
}
