<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\CourseStructureRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressRepositoryInterface;

/**
 * Serviço de Cálculo de Progresso de Cursos e Conclusão de Aulas (SRP / DIP).
 */
class StudentProgressService implements StudentProgressInterface
{
    private StudentProgressRepositoryInterface $progressRepo;
    private ?CourseStructureRepositoryInterface $structureRepo;

    public function __construct(
        StudentProgressRepositoryInterface $progressRepo,
        ?CourseStructureRepositoryInterface $structureRepo = null
    ) {
        $this->progressRepo = $progressRepo;
        $this->structureRepo = $structureRepo;
    }

    public function markLessonCompleted(int $studentId, int $courseId, int $lessonId): bool
    {
        if ($studentId <= 0 || $courseId <= 0 || $lessonId <= 0) {
            return false;
        }

        return $this->progressRepo->saveProgress($studentId, $courseId, $lessonId);
    }

    public function markLessonIncomplete(int $studentId, int $courseId, int $lessonId): bool
    {
        if ($studentId <= 0 || $lessonId <= 0) {
            return false;
        }

        return $this->progressRepo->deleteProgress($studentId, $lessonId);
    }

    public function isLessonCompleted(int $studentId, int $lessonId): bool
    {
        if ($studentId <= 0 || $lessonId <= 0) {
            return false;
        }

        return $this->progressRepo->hasCompleted($studentId, $lessonId);
    }

    public function getCompletedLessonIds(int $studentId, int $courseId): array
    {
        if ($studentId <= 0 || $courseId <= 0) {
            return [];
        }

        return $this->progressRepo->findCompletedLessonIds($studentId, $courseId);
    }

    public function getCourseProgressPercentage(int $studentId, int $courseId): float
    {
        if ($studentId <= 0 || $courseId <= 0 || $this->structureRepo === null) {
            return 0.0;
        }

        $allLessons = $this->structureRepo->getLessons($courseId);
        $publishedCount = 0;
        foreach ($allLessons as $l) {
            if (($l['status'] ?? 'published') === 'published') {
                $publishedCount++;
            }
        }

        if ($publishedCount === 0) {
            return 0.0;
        }

        $completedIds = $this->progressRepo->findCompletedLessonIds($studentId, $courseId);
        $completedCount = count($completedIds);

        $pct = ($completedCount / $publishedCount) * 100.0;
        return round(min(100.0, max(0.0, $pct)), 1);
    }
}
