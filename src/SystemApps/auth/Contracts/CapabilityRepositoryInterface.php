<?php

namespace DomainSystem\SystemApps\auth\Contracts;

interface CapabilityRepositoryInterface
{
    public function findAll(): array;
    public function findById(int $id): ?array;
    public function findBySlug(string $slug): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    
    // Check access
    public function getCapabilitiesForRole(int $roleId): array;
    public function userHasCapability(int $userId, string $capabilitySlug): bool;
    
    // Register (Upsert)
    public function registerCapability(string $slug, string $context = ''): int;
}
