<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

interface PasswordPolicyInterface
{
    public function isAcceptable(string $password): bool;
    public function getMissingRequirements(string $password): array;
}