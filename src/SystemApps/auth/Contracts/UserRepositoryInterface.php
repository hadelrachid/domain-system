<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Contracts;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;
    public function findById(int $id): ?array;
    public function updateTwoFactor(int $userId, string $type, ?string $secret): void;
    public function getAllUsers(): array;
    public function createUser(array $data): int;
    public function updateTwoFactorSecret(int $userId, ?string $secret): void;
    public function updatePassword(int $userId, string $hash): void;
    public function updateProfile(int $userId, array $data): void;
    public function deleteUser(int $id): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPaginatedUsers(
        int $page = 1,
        int $perPage = 20,
        ?string $role = null,
        ?string $search = null,
        ?string $status = null
    ): array;

    public function countUsers(?string $role = null, ?string $search = null, ?string $status = null): int;

    public function updateStatus(int $userId, string $status): void;
}
