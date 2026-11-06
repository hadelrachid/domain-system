<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

use DateTimeImmutable;

/**
 * Contrato de Gestão e Moderação Administrativa de Alunos da Academia (DIP / SOLID).
 */
interface StudentAdminServiceInterface
{
    /**
     * Lista paginada de alunos com dados consolidados de matrícula e progresso.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     total: int,
     *     page: int,
     *     per_page: int
     * }
     */
    public function listStudents(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null
    ): array;

    /**
     * Retorna métricas consolidadas dos alunos do ecossistema.
     *
     * @return array{
     *     total_students: int,
     *     active_students: int,
     *     suspended_students: int,
     *     total_enrollments: int
     * }
     */
    public function getStudentMetrics(): array;

    /**
     * Concede matrícula manual imediata a um curso.
     */
    public function enrollStudent(
        int $studentId,
        int $courseId,
        ?DateTimeImmutable $expiresAt = null,
        ?string $orderRef = null
    ): bool;

    /**
     * Revoga ou cancela a matrícula de um aluno em um curso.
     */
    public function revokeEnrollment(int $studentId, int $courseId): bool;

    /**
     * Atualiza o status do aluno ('active', 'suspended', 'banned').
     */
    public function setStudentStatus(int $studentId, string $status): bool;

    /**
     * Redefine a senha de acesso do aluno.
     */
    public function resetStudentPassword(int $studentId, string $newPassword): bool;
}
