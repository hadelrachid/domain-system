<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato de Gerenciamento de Progresso e Conclusão de Aulas do Aluno (DIP / SOLID).
 */
interface StudentProgressInterface
{
    /**
     * Marca uma aula como concluída pelo aluno.
     */
    public function markLessonCompleted(int $studentId, int $courseId, int $lessonId): bool;

    /**
     * Remove a marcação de conclusão da aula.
     */
    public function markLessonIncomplete(int $studentId, int $courseId, int $lessonId): bool;

    /**
     * Verifica se o aluno já concluiu a aula.
     */
    public function isLessonCompleted(int $studentId, int $lessonId): bool;

    /**
     * Retorna a lista de IDs de aulas concluídas pelo aluno no curso.
     *
     * @return array<int>
     */
    public function getCompletedLessonIds(int $studentId, int $courseId): array;

    /**
     * Calcula o percentual de conclusão do curso pelo aluno (ex: 0.0 a 100.0).
     */
    public function getCourseProgressPercentage(int $studentId, int $courseId): float;
}
