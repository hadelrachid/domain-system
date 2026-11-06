<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\commerce\Contracts\ProductFulfillmentInterface;

/**
 * Responsável por atender e liberar a matrícula de cursos vendidos pelo Commerce (SRP / DIP).
 */
class AcademyCourseFulfillment implements ProductFulfillmentInterface
{
    private CourseEnrollmentInterface $enrollmentService;

    public function __construct(CourseEnrollmentInterface $enrollmentService)
    {
        $this->enrollmentService = $enrollmentService;
    }

    public function supports(string $itemType): bool
    {
        return in_array($itemType, ['course', 'academy_course', 'bundle'], true);
    }

    public function fulfill(int $userId, int $itemId, string $orderRef, array $metadata = []): bool
    {
        if ($userId <= 0 || $itemId <= 0) {
            return false;
        }

        $expiresAt = isset($metadata['expires_at']) && $metadata['expires_at'] instanceof \DateTimeImmutable
            ? $metadata['expires_at']
            : null;

        return $this->enrollmentService->enroll($userId, $itemId, $expiresAt, $orderRef);
    }
}
