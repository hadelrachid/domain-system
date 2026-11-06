<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DateTimeImmutable;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Plugins\academy\Contracts\CourseEnrollmentRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminRepositoryInterface;
use DomainSystem\Plugins\academy\Contracts\StudentAdminServiceInterface;
use DomainSystem\Plugins\academy\Contracts\StudentProgressRepositoryInterface;
use PDO;
use Throwable;

class StudentAdminService implements StudentAdminServiceInterface
{
    private StudentAdminRepositoryInterface $studentRepo;
    private CourseEnrollmentRepositoryInterface $enrollmentRepo;
    private CourseRepositoryInterface $courseRepo;
    private ?StudentProgressRepositoryInterface $progressRepo;

    public function __construct(
        StudentAdminRepositoryInterface $studentRepo,
        CourseEnrollmentRepositoryInterface $enrollmentRepo,
        CourseRepositoryInterface $courseRepo,
        ?StudentProgressRepositoryInterface $progressRepo = null,
        DatabaseConnectionInterface|PDO|null $db = null
    ) {
        $this->studentRepo = $studentRepo;
        $this->enrollmentRepo = $enrollmentRepo;
        $this->courseRepo = $courseRepo;
        $this->progressRepo = $progressRepo;
    }

    public function listStudents(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null,
        ?string $status = null
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $total = $this->studentRepo->countStudents($search, $status);
        $rawUsers = $this->studentRepo->getPaginatedStudents($page, $perPage, $search, $status);

        // Mapeia cursos disponíveis para exibição de nomes
        $courses = $this->courseRepo->findAll();
        $courseMap = [];
        foreach ($courses as $c) {
            $courseMap[(int)$c['id']] = (string)$c['title'];
        }

        $items = [];
        foreach ($rawUsers as $user) {
            $studentId = (int)$user['id'];
            $activeEnrollments = $this->enrollmentRepo->findActiveByStudent($studentId);

            $enrolledCourses = [];
            foreach ($activeEnrollments as $enr) {
                $cId = (int)$enr['course_id'];
                $enrolledCourses[] = [
                    'course_id' => $cId,
                    'title' => $courseMap[$cId] ?? "Curso #{$cId}",
                    'expires_at' => $enr['expires_at'] ?? null,
                    'order_ref' => $enr['order_ref'] ?? null,
                ];
            }

            $items[] = [
                'id' => $studentId,
                'name' => (string)$user['name'],
                'email' => (string)$user['email'],
                'username' => (string)($user['username'] ?? ''),
                'status' => (string)($user['status'] ?? 'active'),
                'role' => (string)($user['role'] ?? 'user'),
                'courses_count' => count($enrolledCourses),
                'enrolled_courses' => $enrolledCourses,
                'created_at' => (string)($user['created_at'] ?? ''),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    public function getStudentMetrics(): array
    {
        $totalStudents = $this->studentRepo->countStudents(null, null);
        $activeStudents = $this->studentRepo->countStudents(null, 'active');
        $suspendedStudents = $this->studentRepo->countStudents(null, 'suspended')
            + $this->studentRepo->countStudents(null, 'banned');

        $totalEnrollments = $this->enrollmentRepo->countActiveEnrollments();

        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'suspended_students' => $suspendedStudents,
            'total_enrollments' => $totalEnrollments,
        ];
    }

    public function enrollStudent(
        int $studentId,
        int $courseId,
        ?DateTimeImmutable $expiresAt = null,
        ?string $orderRef = null
    ): bool {
        if ($studentId <= 0 || $courseId <= 0) {
            return false;
        }

        return $this->enrollmentRepo->saveEnrollment(
            $studentId,
            $courseId,
            'active',
            $expiresAt,
            $orderRef ?? 'MANUAL_ADMIN'
        );
    }

    public function revokeEnrollment(int $studentId, int $courseId): bool
    {
        if ($studentId <= 0 || $courseId <= 0) {
            return false;
        }

        return $this->enrollmentRepo->updateStatus($studentId, $courseId, 'cancelled');
    }

    public function setStudentStatus(int $studentId, string $status): bool
    {
        if ($studentId <= 0) {
            return false;
        }

        $valid = ['active', 'suspended', 'banned'];
        if (!in_array($status, $valid, true)) {
            return false;
        }

        $this->studentRepo->updateStatus($studentId, $status);
        return true;
    }

    public function resetStudentPassword(int $studentId, string $newPassword): bool
    {
        if ($studentId <= 0 || trim($newPassword) === '') {
            return false;
        }

        $this->studentRepo->updatePassword($studentId, password_hash($newPassword, PASSWORD_DEFAULT));
        return true;
    }
}
