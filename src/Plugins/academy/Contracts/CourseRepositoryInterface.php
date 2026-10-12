<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

interface CourseRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * @return array<string, mixed>|null
     */
    public function findBySlug(string $slug): ?array;

    /**
     * @param array{limit?: int, status?: string} $filters
     * @return array<int, array<string, mixed>>
     */
    public function findPublished(array $filters = []): array;

    /**
     * @param array<string, mixed> $data
     * @return int ID of the newly created course
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;
}
