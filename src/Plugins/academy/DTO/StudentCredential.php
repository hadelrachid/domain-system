<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\DTO;

/**
 * DTO Imutável com as credenciais de um aluno (SRP).
 */
final class StudentCredential
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly ?string $username = null,
        public readonly string $status = 'active',
        public readonly string $role = 'student'
    ) {
    }
}
