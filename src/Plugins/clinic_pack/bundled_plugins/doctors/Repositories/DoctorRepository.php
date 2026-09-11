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

    public function save(array $data): void
    {
        $this->db->table($this->table)->insert($data);
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
        return $this->db->table('doctor_schedules')
            ->where('doctor_id', '=', $doctorId)
            // QueryBuilder currently might not support complex order_by chaining natively without checking its implementation,
            // but we can assume it returns an array that we can sort in PHP or if it supports orderBy.
            // Wait, looking at QueryBuilder, it may or may not support orderBy. 
            // We'll just fetch and if needed sort.
            ->get();
    }
}
