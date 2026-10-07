<?php

namespace DomainSystem\SystemApps\auth\Repositories;

use DomainSystem\SystemApps\auth\Contracts\RoleRepositoryInterface;
use DomainSystem\SystemApps\Database\Connection;
use PDO;

class RoleRepository implements RoleRepositoryInterface
{
    private PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function findAll(): array
    {
        return $this->db->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        return $role ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO roles (slug, name, description, is_system_locked) VALUES (:slug, :name, :description, :locked)");
        $stmt->execute([
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'locked' => $data['is_system_locked'] ?? 0
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE roles SET name = :name, description = :description WHERE id = :id AND is_system_locked = 0");
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM roles WHERE id = :id AND is_system_locked = 0");
        return $stmt->execute(['id' => $id]);
    }

    public function assignToUser(int $userId, int $roleId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM user_roles WHERE user_id = :uid AND role_id = :rid");
        $stmt->execute(['uid' => $userId, 'rid' => $roleId]);
        if ($stmt->fetch()) return true;

        $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
        return $stmt->execute(['uid' => $userId, 'rid' => $roleId]);
    }

    public function revokeFromUser(int $userId, int $roleId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = :uid AND role_id = :rid");
        return $stmt->execute(['uid' => $userId, 'rid' => $roleId]);
    }

    public function getUserRoles(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT r.* FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function grantCapability(int $roleId, int $capabilityId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM role_capabilities WHERE role_id = :rid AND capability_id = :cid");
        $stmt->execute(['rid' => $roleId, 'cid' => $capabilityId]);
        if ($stmt->fetch()) return true;

        $stmt = $this->db->prepare("INSERT INTO role_capabilities (role_id, capability_id) VALUES (:rid, :cid)");
        return $stmt->execute(['rid' => $roleId, 'cid' => $capabilityId]);
    }

    public function revokeCapability(int $roleId, int $capabilityId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM role_capabilities WHERE role_id = :rid AND capability_id = :cid");
        return $stmt->execute(['rid' => $roleId, 'cid' => $capabilityId]);
    }
}
