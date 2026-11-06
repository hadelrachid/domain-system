<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DateTimeImmutable;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentInterface;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;

/**
 * Serviço de Negócio de Matrículas e Autorização de Acesso (SRP / DIP).
 */
class CourseEnrollmentService implements CourseEnrollmentInterface
{
    private CourseEnrollmentRepositoryInterface $enrollmentRepo;
    private ?CourseRepositoryInterface $courseRepo;

    public function __construct(
        CourseEnrollmentRepositoryInterface $enrollmentRepo,
        ?CourseRepositoryInterface $courseRepo = null
    ) {
        $this->enrollmentRepo = $enrollmentRepo;
        $this->courseRepo = $courseRepo;
    }

    public function hasActiveEnrollment(int $studentId, int $courseId): bool
    {
        // 1. Validação estrita de entrada: IDs devem ser estritamente positivos
        if ($studentId <= 0 || $courseId <= 0) {
            return false;
        }

        // 2. Se o curso existir e for explicitamente gratuito, libera para o aluno autenticado
        if ($this->courseRepo !== null) {
            $course = $this->courseRepo->findById($courseId);
            if ($course !== null) {
                $isFree = !empty($course['is_free']) || (isset($course['price']) && (float)$course['price'] <= 0.0);
                if ($isFree) {
                    return true;
                }
            }
        }

        // 3. Verifica a existência de matrícula válida e ativa
        $enrollment = $this->enrollmentRepo->findByStudentAndCourse($studentId, $courseId);
        if (!$enrollment) {
            return false;
        }

        if (($enrollment['status'] ?? '') !== 'active') {
            return false;
        }

        // 4. Verificação de expiração: falhar fechado em caso de data inválida/corrompida
        if (!empty($enrollment['expires_at'])) {
            try {
                $now = new DateTimeImmutable();
                $expiresAt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string)$enrollment['expires_at']);
                if ($expiresAt === false) {
                    // Tenta parser genérico antes de invalidar
                    $expiresAt = new DateTimeImmutable((string)$enrollment['expires_at']);
                }

                if ($expiresAt <= $now) {
                    return false;
                }
            } catch (\Throwable $e) {
                // Falha fechada: data malformada não concede acesso
                return false;
            }
        }

        return true;
    }

    public function enroll(
        int $studentId,
        int $courseId,
        ?DateTimeImmutable $expiresAt = null,
        ?string $orderRef = null
    ): bool {
        if ($studentId <= 0 || $courseId <= 0) {
            return false;
        }

        return $this->enrollmentRepo->saveEnrollment($studentId, $courseId, 'active', $expiresAt, $orderRef);
    }

    public function revoke(int $studentId, int $courseId): bool
    {
        if ($studentId <= 0 || $courseId <= 0) {
            return false;
        }

        return $this->enrollmentRepo->updateStatus($studentId, $courseId, 'cancelled');
    }

    public function getActiveCourseIds(int $studentId): array
    {
        if ($studentId <= 0) {
            return [];
        }

        $active = $this->enrollmentRepo->findActiveByStudent($studentId);
        $ids = [];
        foreach ($active as $en) {
            $cId = (int)($en['course_id'] ?? 0);
            if ($cId > 0 && !in_array($cId, $ids, true)) {
                $ids[] = $cId;
            }
        }
        return $ids;
    }

    public function getEnrollment(int $studentId, int $courseId): ?array
    {
        if ($studentId <= 0 || $courseId <= 0) {
            return null;
        }

        return $this->enrollmentRepo->findByStudentAndCourse($studentId, $courseId);
    }
}
