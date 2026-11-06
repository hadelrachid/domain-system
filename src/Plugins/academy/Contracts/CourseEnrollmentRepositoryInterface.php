<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

use DateTimeImmutable;

/**
 * Contrato de Repositório de Persistência para Matrículas (DIP / SOLID).
 */
interface CourseEnrollmentRepositoryInterface
{
    /**
     * Garante a criação da tabela de matrículas (SQLite e MySQL).
     */
    public function ensureTable(): void;

    /**
     * Busca uma matrícula específica por aluno e curso.
     *
     * @return array<string, mixed>|null
     */
    public function findByStudentAndCourse(int $studentId, int $courseId): ?array;

    /**
     * Retorna todas as matrículas ativas de um aluno.
     *
     * @return array<array<string, mixed>>
     */
    public function findActiveByStudent(int $studentId): array;

    /**
     * Cria ou atualiza uma matrícula para o status ativo.
     */
    public function saveEnrollment(
        int $studentId,
        int $courseId,
        string $status = 'active',
        ?DateTimeImmutable $expiresAt = null,
        ?string $orderRef = null
    ): bool;

    /**
     * Atualiza o status da matrícula (ex: 'cancelled', 'expired').
     */
    public function updateStatus(int $studentId, int $courseId, string $status): bool;

    /**
     * Conta o número total de alunos matriculados em um curso.
     */
    public function countEnrolledInCourse(int $courseId): int;

    /**
     * Conta o total geral de matrículas ativas no sistema.
     */
    public function countActiveEnrollments(): int;
}
