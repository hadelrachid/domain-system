<?php

namespace DomainSystem\Plugins\patients\Repositories;

use DomainSystem\Plugins\Database\QueryBuilder;
use DomainSystem\Plugins\patients\Contracts\PatientRepositoryInterface;
use Exception;

class SqlitePatientRepository implements PatientRepositoryInterface
{
    private QueryBuilder $db;
    private string $table = 'patients';

    public function __construct(QueryBuilder $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        return $this->db->table($this->table)->get();
    }

    public function findLatest(int $limit): array
    {
        // Pega todos e fatia (Workaround temporário até o motor ter order_by id DESC nativo)
        $all = $this->findAll();
        return array_slice(array_reverse($all), 0, $limit);
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
