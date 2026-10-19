<?php

namespace DomainSystem\Plugins\legal_cases\Contracts;

interface LegalCaseRepositoryInterface
{
    public function all(): array;
    public function findById(int $id): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
}
