<?php

namespace DomainSystem\SystemApps\auth\Repositories;

use DomainSystem\SystemApps\auth\Contracts\CapabilityRepositoryInterface;
use DomainSystem\SystemApps\Database\Connection;
use PDO;

class CapabilityRepository implements CapabilityRepositoryInterface
{
    private PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function findAll(): array
    {
        return $this->db->query("SELECT * FROM capabilities")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM capabilities WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $cap = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cap ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM capabilities WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $cap = $stmt->fetch(PDO::FETCH_ASSOC);
        return $cap ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO capabilities (slug, context) VALUES (:slug, :context)");
        $stmt->execute([
            'slug' => $data['slug'],
            'context' => $data['context'] ?? null
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE capabilities SET context = :context WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'context' => $data['context'] ?? null
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM capabilities WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getCapabilitiesForRole(int $roleId): array
    {
        $stmt = $this->db->prepare("SELECT c.* FROM capabilities c JOIN role_capabilities rc ON c.id = rc.capability_id WHERE rc.role_id = :rid");
        $stmt->execute(['rid' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registerCapability(string $slug, string $context = ''): int
    {
        $existing = $this->findBySlug($slug);
        if ($existing) {
            return $existing['id'];
        }
        return $this->create(['slug' => $slug, 'context' => $context]);
    }

    public function userHasCapability(int $userId, string $capabilitySlug): bool
    {
        // User is directly checked if any of their roles has this capability or the master '*' capability
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM user_roles ur
            JOIN role_capabilities rc ON ur.role_id = rc.role_id
            JOIN capabilities c ON rc.capability_id = c.id
            WHERE ur.user_id = :uid AND (c.slug = :cap OR c.slug = '*')
        ");
        $stmt->execute(['uid' => $userId, 'cap' => $capabilitySlug]);
        return (bool) $stmt->fetch();
    }
}
