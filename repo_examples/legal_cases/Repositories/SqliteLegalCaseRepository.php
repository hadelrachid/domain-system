<?php

namespace DomainSystem\Plugins\legal_cases\Repositories;

use DomainSystem\Plugins\legal_cases\Contracts\LegalCaseRepositoryInterface;
use DomainSystem\SystemApps\Database\Connection;
use PDO;

class SqliteLegalCaseRepository implements LegalCaseRepositoryInterface
{
    private PDO $db;

    public function __construct(Connection|PDO $connection)
    {
        $this->db = $connection instanceof Connection ? $connection->getPdo() : $connection;
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM legal_cases ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM legal_cases WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO legal_cases (client_name, case_number, status) VALUES (?, ?, ?)");
        $stmt->execute([$data['client_name'], $data['case_number'], $data['status'] ?? 'Ativo']);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE legal_cases SET client_name = ?, case_number = ?, status = ? WHERE id = ?");
        return $stmt->execute([$data['client_name'], $data['case_number'], $data['status'] ?? 'Ativo', $id]);
    }
}
