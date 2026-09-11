<?php

namespace DomainSystem\Plugins\appointments\Repositories;

use DomainSystem\Plugins\Database\Connection;
use DomainSystem\Plugins\appointments\Contracts\InsuranceRepositoryInterface;

class InsuranceRepository implements InsuranceRepositoryInterface
{
    private \PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM health_insurances ORDER BY id DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function add(string $name): void
    {
        $stmt = $this->db->prepare("INSERT INTO health_insurances (name, active) VALUES (?, 1)");
        $stmt->execute([$name]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM health_insurances WHERE id = ?");
        $stmt->execute([$id]);
    }
}
