<?php
namespace DomainSystem\Core\Contracts;

interface PasswordPolicyInterface
{
    public function isAcceptable(string $password): bool;
    public function getMissingRequirements(string $password): array;
}