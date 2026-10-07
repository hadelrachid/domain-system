<?php

namespace DomainSystem\SystemApps\auth\Contracts;

interface RoleRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?array;
    public function findBySlug(string $slug): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    
    // User relationships
    public function assignToUser(int $userId, int $roleId): bool;
    public function revokeFromUser(int $userId, int $roleId): bool;
    public function getUserRoles(int $userId): array;
    
    // Capability relationships
    public function grantCapability(int $roleId, int $capabilityId): bool;
    public function revokeCapability(int $roleId, int $capabilityId): bool;
}
