<?php
namespace DomainSystem\Core\Security;

use DomainSystem\Core\Contracts\PasswordPolicyInterface;

class PasswordPolicy implements PasswordPolicyInterface
{
    public function isAcceptable(string $password): bool
    {
        return PasswordAnalyzer::isAcceptable($password);
    }
    
    public function getMissingRequirements(string $password): array
    {
        return PasswordAnalyzer::getMissingRequirements($password);
    }
}