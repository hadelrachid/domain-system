<?php

namespace DomainSystem\Plugins\appointments\Contracts;

interface InsuranceRepositoryInterface
{
    public function getAll(): array;
    public function add(string $name): void;
    public function delete(int $id): void;
}
