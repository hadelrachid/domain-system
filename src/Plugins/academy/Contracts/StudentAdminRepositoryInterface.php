<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de Repositório para Gestão de Alunos da Academia (DIP / SOLID).
 */
interface StudentAdminRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPaginatedStudents(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null
    ): array;

    public function countStudents(?string $search = null, ?string $status = null): int;

    public function updateStatus(int $studentId, string $status): void;

    public function updatePassword(int $studentId, string $passwordHash): void;
}
