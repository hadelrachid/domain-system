<?php

namespace DomainSystem\Plugins\doctors\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class SqliteDoctorRepository implements DoctorRepositoryInterface
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
}
