<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use DomainSystem\Plugins\academy\Repositories\PdoCourseEnrollmentRepository;
use DomainSystem\Plugins\academy\Services\CourseEnrollmentService;
use PDO;
use PHPUnit\Framework\TestCase;

class CourseEnrollmentValidationTest extends TestCase
{
    private PDO $pdo;
    private PdoCourseEnrollmentRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->repo = new PdoCourseEnrollmentRepository($this->pdo);
    }

    public function testNonPositiveIdsNeverGrantAccessEvenIfCourseIsFree(): void
    {
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(10)->willReturn([
            'id'      => 10,
            'title'   => 'Curso 100% Gratuito',
            'is_free' => 1,
            'price'   => 0.00,
        ]);

        $service = new CourseEnrollmentService($this->repo, $courseRepo);

        $this->assertFalse($service->hasActiveEnrollment(0, 10), 'ID de aluno 0 não deve ter acesso');
        $this->assertFalse($service->hasActiveEnrollment(-1, 10), 'ID de aluno negativo não deve ter acesso');
        $this->assertFalse($service->hasActiveEnrollment(5, 0), 'ID de curso 0 não deve ter acesso');
        $this->assertFalse($service->hasActiveEnrollment(5, -10), 'ID de curso negativo não deve ter acesso');

        // Aluno válido e positivo tem acesso ao curso gratuito
        $this->assertTrue($service->hasActiveEnrollment(5, 10));
    }

    public function testCorruptedExpirationFailsClosed(): void
    {
        $courseRepo = $this->createMock(CourseRepositoryInterface::class);
        $courseRepo->method('findById')->with(20)->willReturn([
            'id'      => 20,
            'title'   => 'Curso Pago',
            'is_free' => 0,
            'price'   => 197.00,
        ]);

        // Insere diretamente registro com data malformada
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_enrollments (user_id, course_id, status, expires_at, created_at, updated_at)
            VALUES (?, ?, 'active', 'DATA_CORROMPIDA_INVALIDA', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([12, 20]);

        $service = new CourseEnrollmentService($this->repo, $courseRepo);

        // Deve falhar fechado e retornar false
        $this->assertFalse($service->hasActiveEnrollment(12, 20));
    }

    public function testEnrollmentUniquenessAndConcurrencyFallback(): void
    {
        $this->repo->saveEnrollment(8, 50, 'active');
        $this->repo->saveEnrollment(8, 50, 'cancelled');

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM academy_enrollments WHERE user_id = 8 AND course_id = 50");
        $stmt->execute();
        $count = (int)$stmt->fetchColumn();

        $this->assertSame(1, $count, 'Deve existir exatamente 1 registro para o mesmo aluno e curso');

        $enrollment = $this->repo->findByStudentAndCourse(8, 50);
        $this->assertSame('cancelled', $enrollment['status']);
    }
}
