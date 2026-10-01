<?php

namespace DomainSystem\Plugins\doctors\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class DoctorRepository implements DoctorRepositoryInterface
{
    private QueryBuilder $db;
    private string $table = 'doctors';

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        return $this->db->table($this->table)->get();
    }

    public function findById(int $id): ?array
    {
        $result = $this->db->table($this->table)->where('id', '=', $id)->get();
        return !empty($result) ? $result[0] : null;
    }

    public function save(array $data): int
    {
        return (int)$this->db->table($this->table)->insert($data);
    }

    public function update(int $id, array $data): void
    {
        $this->db->table($this->table)->where('id', '=', $id)->update($data);
    }

    public function delete(int $id): void
    {
        $this->db->table($this->table)->where('id', '=', $id)->delete();
    }

    public function getSchedules(int $doctorId): array
    {
        $db = $this->db->getPdo();
        $stmt = $db->prepare("SELECT * FROM doctor_schedules WHERE doctor_id = ? ORDER BY day_of_week, start_time");
        $stmt->execute([$doctorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function saveSchedules(int $doctorId, array $schedules): void
    {
        $db = $this->db->getPdo();
        
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

    public function getAllActiveSchedules(): array
    {
        $db = $this->db->getPdo();
        $stmt = $db->query("SELECT doctor_id, day_of_week FROM doctor_schedules WHERE is_active = 1");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
