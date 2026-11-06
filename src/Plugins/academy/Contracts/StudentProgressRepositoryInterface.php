<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de Repositório de Persistência de Progresso de Aulas (DIP / SOLID).
 */
interface StudentProgressRepositoryInterface
{
    /**
     * Garante a criação da tabela de progresso de aulas.
     */
    public function ensureTable(): void;

    /**
     * Registra a conclusão da aula.
     */
    public function saveProgress(int $studentId, int $courseId, int $lessonId): bool;

    /**
     * Remove o registro de conclusão da aula.
     */
    public function deleteProgress(int $studentId, int $lessonId): bool;

    /**
     * Verifica se existe registro de conclusão da aula para o aluno.
     */
    public function hasCompleted(int $studentId, int $lessonId): bool;

    /**
     * Retorna a lista de IDs de aulas concluídas pelo aluno em um determinado curso.
     *
     * @return array<int>
     */
    public function findCompletedLessonIds(int $studentId, int $courseId): array;
}
